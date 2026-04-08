<?php
declare(strict_types=1);

/**
 * SparkIgniter - Input helper (PHP 8.4)
 *
 * Versão final e totalmente compatível com o PHP 8.4, utilizando tipagem, 
 * operadores modernos (match) e funções nativas seguras (filter_var, random_int).
 */
class Input
{
    /** @var bool Se false, $_GET é limpo no construtor (compat CI3) */
    protected readonly bool $allowGetArray;

    /** @var bool Se true, normaliza \r\n em \n */
    protected readonly bool $standardizeNewlines;

    /** @var bool Se true, aplica filtro básico em todos os fetch/all por padrão */
    protected readonly bool $enableSanitize;

    /** @var list<string> Lista de proxies confiáveis (IPs ou CIDR) para detectar IP real */
    protected readonly array $trustedProxies;

    /** @var string|null Cache do php://input */
    protected ?string $rawInput = null;

    /** @var array|null Cache do corpo JSON já decodificado */
    protected ?array $jsonBody = null;

    public function __construct(
        array $options = []
    ) {
        // Uso de readonly para propriedades que não mudam após o construct
        $this->allowGetArray       = (bool)($options['allow_get_array']    ?? true);
        $this->standardizeNewlines = (bool)($options['standardize_newlines'] ?? false);
        $this->enableSanitize      = (bool)($options['sanitize']             ?? false);
        $this->trustedProxies      = array_values($options['trusted_proxies'] ?? []);

        if (!$this->allowGetArray) {
            $_GET = [];
        }

        $this->sanitizeGlobalArrays();
    }

    /* ==========================
     * Básicos (GET/POST/COOKIE/SERVER)
     * ========================== */

    public function get(?string $key = null, mixed $default = null, ?bool $sanitize = null): mixed
    {
        return $this->fetchFrom($_GET, $key, $default, $sanitize);
    }

    public function post(?string $key = null, mixed $default = null, ?bool $sanitize = null): mixed
    {
        return $this->fetchFrom($_POST, $key, $default, $sanitize);
    }

    public function cookie(?string $key = null, mixed $default = null, ?bool $sanitize = null): mixed
    {
        return $this->fetchFrom($_COOKIE, $key, $default, $sanitize);
    }

    public function server(string $key, mixed $default = null, ?bool $sanitize = null): mixed
    {
        return $this->fetchFrom($_SERVER, $key, $default, $sanitize);
    }

    /** Compat CI3: tenta POST, senão GET */
    public function get_post(string $key, mixed $default = null, ?bool $sanitize = null): mixed
    {
        $val = $this->post($key, default: null, sanitize: $sanitize);
        return $val !== null ? $val : $this->get($key, $default, $sanitize);
    }

    /** Todos os dados de input (GET+POST) */
    public function all(?bool $sanitize = null): array
    {
        $data = array_merge($_GET, $_POST);
        return $this->maybeSanitizeArray($data, $sanitize);
    }

    /* ==========================
     * Tipados convenientes (Otimizados com filter_var)
     * ========================== */

    public function getInt(string $key, int $default = 0): int
    {
        $v = $this->get($key, $default, sanitize: false);
        $filtered = filter_var($v, FILTER_VALIDATE_INT);
        return $filtered === false ? $default : (int)$filtered;
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        $v = $this->get($key, $default, sanitize: false);
        $filtered = filter_var($v, FILTER_VALIDATE_FLOAT);
        return $filtered === false ? $default : (float)$filtered;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $v = $this->get($key, $default, sanitize: false);
        $filtered = filter_var($v, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        return $filtered ?? $default;
    }

    /* ==========================
     * Corpo / método / headers
     * ========================== */

    /** Retorna raw php://input (com cache) */
    public function raw(): string
    {
        if ($this->rawInput === null) {
            $this->rawInput = file_get_contents('php://input') ?: '';
            
            if ($this->standardizeNewlines) {
                $this->rawInput = str_replace(["\r\n", "\r"], "\n", $this->rawInput);
            }
        }
        return $this->rawInput;
    }

    /** Compat CI3: input_stream() para PUT/PATCH/DELETE de form-urlencoded */
    public function input_stream(?string $key = null, mixed $default = null, ?bool $sanitize = null): mixed
    {
        parse_str($this->raw(), $parsed);
        if ($key === null) {
            return $this->maybeSanitizeArray($parsed, $sanitize);
        }
        return $this->fetchFrom($parsed, $key, $default, $sanitize);
    }

    /** Se Content-Type JSON, retorna array (decodifica uma única vez) */
    public function json(): ?array
    {
        if ($this->jsonBody !== null) {
            return $this->jsonBody;
        }
        
        $contentType = $this->contentType();
        if ($contentType !== null && str_contains($contentType, 'application/json')) {
            if (is_array($this->jsonBody = json_decode($this->raw(), true))) {
                return $this->jsonBody;
            }
        }
        return null;
    }

    /** Método HTTP, respeitando X-HTTP-Method-Override (Otimizado com match) */
    public function method(): string
    {
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $override = $this->header('X-HTTP-Method-Override');
        
        if ($override) {
            $ov = strtoupper($override);
            return match ($ov) {
                'GET','POST','PUT','PATCH','DELETE','OPTIONS','HEAD' => $ov,
                default => $method,
            };
        }
        return $method;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($_SERVER[$key])) return $_SERVER[$key];

        // Fallback para Authorization
        if (strcasecmp($name, 'Authorization') === 0) {
            if (isset($_SERVER['Authorization'])) return $_SERVER['Authorization'];
            $apache = function_exists('apache_request_headers') ? apache_request_headers() : null;
            if ($apache && isset($apache['Authorization'])) return $apache['Authorization'];
        }
        return $default;
    }

    public function contentType(): ?string
    {
        // Uso de coalescência dupla de null para concisão
        return $this->server('CONTENT_TYPE') ?? $this->server('HTTP_CONTENT_TYPE') ?? null;
    }

    public function isAjax(): bool
    {
        return strtolower((string)$this->header('X-Requested-With')) === 'xmlhttprequest';
    }

    public function isSecure(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
        if (($this->header('X-Forwarded-Proto') ?? '') === 'https') return true;
        return false;
    }

    /* ==========================
     * IP / User Agent / URL partes
     * ========================== */

    public function ip(): string
    {
        $remote = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        if ($this->isFromTrustedProxy($remote)) {
            $spoof = $this->header('X-Forwarded-For');
            if ($spoof) {
                $parts = array_map('trim', explode(',', $spoof));
                $candidate = $parts[0] ?? $remote;
                if ($this->validIp($candidate)) {
                    return $candidate;
                }
            }
            $real = $this->header('X-Real-IP');
            if ($real && $this->validIp($real)) {
                return $real;
            }
        }

        return $this->validIp($remote) ? $remote : '0.0.0.0';
    }

    public function userAgent(): string
    {
        return (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    }

    public function scheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    public function host(): string
    {
        return (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
    }

    public function port(): int
    {
        if (isset($_SERVER['SERVER_PORT']) && is_numeric($_SERVER['SERVER_PORT'])) {
            return (int)$_SERVER['SERVER_PORT'];
        }
        return $this->isSecure() ? 443 : 80;
    }

    public function uri(): string
    {
        return (string)($_SERVER['REQUEST_URI'] ?? '/');
    }

    public function queryString(): string
    {
        return (string)($_SERVER['QUERY_STRING'] ?? '');
    }

    /* ==========================
     * Checks utilitários
     * ========================== */

    public function isCli(): bool
    {
        return PHP_SAPI === 'cli' || defined('STDIN');
    }

    public function isJson(): bool
    {
        $ct = $this->contentType();
        return $ct !== null && str_contains($ct, 'application/json');
    }

    /* ==========================
     * Internos (Otimizados)
     * ========================== */

    protected function fetchFrom(array $source, ?string $key, mixed $default, ?bool $sanitize): mixed
    {
        $applySanitize = is_bool($sanitize) ? $sanitize : $this->enableSanitize;

        if ($key === null) {
            return $this->maybeSanitizeArray($source, $applySanitize);
        }

        if (!array_key_exists($key, $source)) {
            return $default;
        }

        $value = $source[$key];
        return $applySanitize ? $this->sanitizeValue($value) : $value;
    }

    protected function maybeSanitizeArray(array $data, ?bool $sanitize = null): array
    {
        $apply = is_bool($sanitize) ? $sanitize : $this->enableSanitize;
        if (!$apply) return $data;

        // Uso de array_map e arrow function para sanitização concisa (PHP 7.4+)
        return array_map(fn($v) => $this->sanitizeValue($v), $data);
    }

    protected function sanitizeValue(mixed $v): mixed
    {
        if (is_array($v)) {
            return $this->maybeSanitizeArray($v, true); 
        }
        if (is_string($v)) {
            $v = str_replace("\0", '', $v); // Remove NUL
            
            if ($this->standardizeNewlines) {
                $v = str_replace(["\r\n", "\r"], "\n", $v);
            }
            
            // FILTER_SANITIZE_SPECIAL_CHARS protege contra XSS básico
            return filter_var($v, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
        return $v;
    }

    protected function sanitizeGlobalArrays(): void
    {
        // Função de filtro usando arrow function e referência (&)
        $filterKeys = function (array &$arr): void {
            foreach (array_keys($arr) as $k) {
                if (!preg_match('#^[a-zA-Z0-9:_\-/|]+$#', (string)$k)) {
                    unset($arr[$k]);
                }
            }
        };
        $filterKeys($_GET);
        $filterKeys($_POST);
        $filterKeys($_COOKIE);
        $filterKeys($_SERVER);
    }

    protected function isFromTrustedProxy(string $ip): bool
    {
        if (!$this->trustedProxies) return false;
        foreach ($this->trustedProxies as $entry) {
            if ($this->ipInRange($ip, $entry)) return true;
        }
        return false;
    }

    protected function validIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Verifica se $ip pertence à $range (IP exato ou CIDR)
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            // Uso de hash_equals para comparação segura contra ataque de tempo
            return hash_equals($ip, $range);
        }
        [$subnet, $mask] = explode('/', $range, 2);
        $mask = (int)$mask;

        // Suporte IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipLong     = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $maskLong   = -1 << (32 - $mask);
            $subnetLow  = $subnetLong & $maskLong;
            $subnetHigh = $subnetLow + ~ $maskLong;
            return ($ipLong >= $subnetLow && $ipLong <= $subnetHigh);
        }

        // Suporte IPv6 (Otimizado para PHP 7+)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipBin     = inet_pton($ip);
            $subnetBin = inet_pton($subnet);
            $bytes = intdiv($mask, 8);
            $bits  = $mask % 8;

            if (strncmp((string)$ipBin, (string)$subnetBin, $bytes) !== 0) {
                return false;
            }
            if ($bits === 0) return true;

            $maskByte = ~((1 << (8 - $bits)) - 1) & 0xFF;
            return ((ord($ipBin[$bytes]) & $maskByte) === (ord($subnetBin[$bytes]) & $maskByte));
        }

        return false;
    }
}