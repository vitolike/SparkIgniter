<?php
// app/core/RestController.php
// Inspirado no REST_Controller (Phil Sturgeon / Chris Kacerguis) para ergonomia CI3,
// adaptado para o SparkIgniter (Controller + Input + JWT).

#[\AllowDynamicProperties]
class RestController extends Controller
{
    /* ==============================
     * HTTP Status Codes (subset)
     * ============================== */
    public const HTTP_OK                    = 200;
    public const HTTP_CREATED               = 201;
    public const HTTP_NO_CONTENT            = 204;
    public const HTTP_BAD_REQUEST           = 400;
    public const HTTP_UNAUTHORIZED          = 401;
    public const HTTP_FORBIDDEN             = 403;
    public const HTTP_NOT_FOUND             = 404;
    public const HTTP_METHOD_NOT_ALLOWED    = 405;
    public const HTTP_UNPROCESSABLE_ENTITY  = 422;
    public const HTTP_TOO_MANY_REQUESTS     = 429;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    /** @var string Resposta padrão em JSON */
    protected string $defaultFormat = 'json';

    public function __construct()
    {
        parent::__construct();

        // Força JSON por padrão
        header('Content-Type: application/json; charset=utf-8');

        // CORS básico (ajuste conforme necessário)
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /* ==============================
     * Helpers CI-like: get/post/put/patch/delete
     * ============================== */

    /** GET params */
    protected function get($index = null, $default = null)
    {
        if (isset($this->input)) {
            return $this->input->get($index, $default);
        }
        // Fallback sem Input
        $src = $_GET ?? [];
        return $this->fetch($src, $index, $default);
    }

    /** POST params (inclui JSON em POST) */
    protected function post($index = null, $default = null)
    {
        if (isset($this->input)) {
            return $this->input->post($index, $default);
        }
        $src = $_POST ?? [];
        $json = $this->jsonBody();
        if (is_array($json)) $src = array_merge($src, $json);
        return $this->fetch($src, $index, $default);
    }

    /** PUT body */
    protected function put($index = null, $default = null)
    {
        if (isset($this->input)) {
            return $this->input->put($index, $default);
        }
        $src = $this->jsonBody() ?? [];
        return $this->fetch($src, $index, $default);
    }

    /** PATCH body */
    protected function patch($index = null, $default = null)
    {
        if (isset($this->input)) {
            return $this->input->patch($index, $default);
        }
        $src = $this->jsonBody() ?? [];
        return $this->fetch($src, $index, $default);
    }

    /** DELETE body */
    protected function delete($index = null, $default = null)
    {
        if (isset($this->input)) {
            return $this->input->delete($index, $default);
        }
        $src = $this->jsonBody() ?? [];
        return $this->fetch($src, $index, $default);
    }

    /** Método HTTP atual */
    protected function method(): string
    {
        if (isset($this->input)) return $this->input->method();
        $m = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? ($_REQUEST['_method'] ?? null);
        if ($override) {
            $om = strtoupper($override);
            if (in_array($om, ['GET','POST','PUT','PATCH','DELETE'], true)) $m = $om;
        }
        return $m;
    }

    /* ==============================
     * Response helpers
     * ============================== */

    /** Envia resposta (alias de set_response) */
    protected function response($data, int $http_code = self::HTTP_OK): void
    {
        $this->set_response($data, $http_code);
    }

    /** Define e envia resposta no formato negociado (json por padrão) */
    protected function set_response($data, int $http_code = self::HTTP_OK): void
    {
        http_response_code($http_code);

        $format = $this->negotiateFormat();
        switch ($format) {
            case 'json':
            default:
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
        }
        // Importante: encerrar execução após devolver API
        exit;
    }

    /** Negociação simples via Accept (suporta só JSON por ora) */
    protected function negotiateFormat(): string
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (stripos($accept, 'application/json') !== false || $accept === '' || $accept === '*/*') {
            return 'json';
        }
        // fallback
        return $this->defaultFormat;
    }

    /* ==============================
     * Utilitários adicionais
     * ============================== */

    /** Decodifica corpo JSON seguro */
    protected function jsonBody(): ?array
    {
        $raw = file_get_contents('php://input') ?: '';
        if ($raw === '') return null;
        $ctype = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($ctype, 'application/json') === false) {
            // Alguns clients enviam urlencoded em PUT/PATCH/DELETE
            $parsed = [];
            parse_str($raw, $parsed);
            return $parsed ?: null;
        }
        if (function_exists('json_validate') && !json_validate($raw)) {
            return null;
        }
        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Atalho de validação: 405 quando método não permitido */
    protected function require_methods(array $allowed): void
    {
        $m = $this->method();
        if (!in_array($m, $allowed, true)) {
            header('Allow: ' . implode(', ', $allowed));
            $this->response(['error' => 'Method Not Allowed', 'allowed' => $allowed], self::HTTP_METHOD_NOT_ALLOWED);
        }
    }

    /** Ajuda a extrair listas de campos: igual ao CI3 */
    private function fetch(array $src, $index, $default)
    {
        if ($index === null) return $src;
        if (is_array($index)) {
            $out = [];
            foreach ($index as $k) $out[$k] = $src[$k] ?? $default;
            return $out;
        }
        return $src[$index] ?? $default;
    }

    /* ==============================
     * Auth helper (opcional)
     * ============================== */
    protected function require_auth(): void
    {
        // Reaproveita o middleware do Controller::requireAuthJWT() se existir
        if (method_exists($this, 'requireAuthJWT')) {
            $this->requireAuthJWT();
            return;
        }
        // Fallback básico JWT
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($auth, 'Bearer ')) {
            $this->response(['error' => 'Unauthorized'], self::HTTP_UNAUTHORIZED);
        }
        $token = substr($auth, 7);
        try {
            $jwt = new JWT();
            $payload = $jwt->decode($token);
            $this->user = $payload;
        } catch (\Throwable $e) {
            log_message('ERROR', 'JWT decode failed: ' . $e->getMessage());
            $this->response(['error' => 'Unauthorized'], self::HTTP_UNAUTHORIZED);
        }
    }
}