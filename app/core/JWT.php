<?php
// app/core/JWT.php - Simple HS256 JWT

/**
 * Guia de Uso da Classe JWT (JSON Web Token - HS256)
 *
 * Esta classe é usada para criar, assinar e validar tokens de acesso
 * e refresh em sua aplicação. É essencial para autenticação stateless (sem estado).
 *
 * 1. Pré-requisitos:
 * - A classe depende da classe estática 'Env' para obter as configurações.
 * - A chave secreta é definida por 'JWT_SECRET'.
 * - Os tempos de expiração são definidos por 'JWT_EXPIRE' (acesso) e 'JWT_REFRESH_EXPIRE' (refresh).
 *
 * 2. Inicialização:
 * Instancie a classe (geralmente injetada em um Controller ou Service):
 * Ex: $jwt_handler = new JWT();
 *
 * 3. Geração de Tokens:
 *
 * 3.1. encode(array $payload, ?int $expSeconds): string
 * Cria um token com um payload customizado e tempo de vida específico.
 * Ex: $token = $this->jwt->encode(['id' => 101, 'role' => 'user'], 3600); // 1 hora
 *
 * 3.2. issueTokens(array $claims=[]): array
 * Método recomendado para logins. Gera um par de tokens (Access e Refresh)
 * com base nas configurações do ambiente (JWT_EXPIRE).
 * Ex: $tokens = $this->jwt->issueTokens(['user_id' => 102]);
 * Retorna: ['access_token' => '...', 'refresh_token' => '...', 'expires_in' => 3600]
 *
 * 4. Validação e Decodificação:
 *
 * 4.1. decode(string $jwt): array
 * Valida a assinatura do token e verifica a expiração ('exp'). Lança uma Exception
 * em caso de falha (Assinatura inválida, Token expirado, JSON malformado).
 *
 * Exemplo de uso em um Middleware:
 * try {
 * $token = $this->input->header('Authorization', default: '');
 * $token = str_replace('Bearer ', '', $token);
 * * $payload = $this->jwt->decode($token);
 * * // Token válido: $payload contém os dados (ex: $payload['user_id'])
 * $this->controller->auth_user = $payload;
 * * } catch (\Exception $e) {
 * // Falha na validação ou expiração. Retorna 401 Unauthorized.
 * $this->response->json(['error' => $e->getMessage()], 401);
 * }
 */

#[\AllowDynamicProperties]
class JWT {
    private string $secret;
    private string $algo = 'HS256';

    public function __construct() {
        $this->secret = Env::get('JWT_SECRET', 'changeme');
    }

    private function b64url($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function b64url_dec($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public function encode(array $payload, ?int $expSeconds = null): string {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $now = time();
        if (!isset($payload['iat'])) $payload['iat'] = $now;
        if ($expSeconds !== null) $payload['exp'] = $now + $expSeconds;

        $segments = [
            $this->b64url(json_encode($header)),
            $this->b64url(json_encode($payload))
        ];
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $this->secret, true);
        $segments[] = $this->b64url($signature);
        return implode('.', $segments);
    }

    public function decode(string $jwt): array {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) throw new Exception('Invalid token');
        [$h, $p, $s] = $parts;
        $json = $this->b64url_dec($p);
        if (function_exists('json_validate') && !json_validate($json)) {
            throw new Exception('Invalid payload JSON');
        }
        try {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new Exception('Invalid payload JSON');
        }
        $sig = $this->b64url_dec($s);
        $valid = hash_equals($sig, hash_hmac('sha256', "$h.$p", $this->secret, true));
        if (!$valid) throw new Exception('Signature verification failed');
        if (isset($payload['exp']) && time() >= (int)$payload['exp']) throw new Exception('Token expired');
        return $payload;
    }

    // Helper for access + refresh
    public function issueTokens(array $claims=[]): array {
        $accessExp = (int)Env::get('JWT_EXPIRE', 3600);
        $refreshExp = (int)Env::get('JWT_REFRESH_EXPIRE', 604800);
        $access = $this->encode(array_merge(['type'=>'access'], $claims), $accessExp);
        $refresh = $this->encode(array_merge(['type'=>'refresh'], $claims), $refreshExp);
        return ['access_token' => $access, 'refresh_token' => $refresh, 'expires_in' => $accessExp];
    }
}