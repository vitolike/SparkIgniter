<?php
#[\AllowDynamicProperties]
class IdGenerator
{
    /**
     * Gera um GUID (Global Unique Identifier) / UUID v4.
     * Prioriza a função nativa de COM se disponível (apenas Windows), 
     * ou usa mt_rand() para gerar uma string UUID v4 compatível.
     */
    public function guid(): string
    {
        // 1. Otimização: Uso da função nativa 'uuid_create' se disponível (mais comum em ambientes não-COM).
        // Embora com_create_guid seja a sua função original, uuid_create é a alternativa moderna em muitas extensões.
        if (function_exists('uuid_create')) {
            return trim(uuid_create(UUID_TYPE_RANDOM));
        }
        
        // 2. Mantém a lógica original do COM para compatibilidade máxima (apenas Windows)
        if (function_exists('com_create_guid')) {
            return trim(com_create_guid(), '{}');
        }

        // 3. Fallback (UUID v4) otimizado:
        // Uso de Hexadecimal (X) maiúsculo, conforme o formato GUID do Windows.
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0x4000, 0x4FFF), // Versão 4
            mt_rand(0x8000, 0xBFFF), // Variação 10xx
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF)
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Gera um UUID (Universally Unique Identifier) versão 4 (aleatório).
     * Otimizado para clareza e com uso da função nativa do PHP 8.
     */
    public function uuid(): string
    {
        // 1. OTIMIZAÇÃO E PADRONIZAÇÃO:
        // Desde o PHP 7.0, a melhor forma é usar a extensão 'random' ou,
        // se o PHP 8 estiver com as extensões OpenSSL instaladas, 
        // a função 'openssl_random_pseudo_bytes' é preferível a mt_rand() para criptografia/identificação.

        if (function_exists('openssl_random_pseudo_bytes')) {
            $data = openssl_random_pseudo_bytes(16);
            
            // Define os bits de Versão 4 e Variação DCE (RFC 4122)
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100 (v4)
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        // 2. Fallback (UUID v4): Mantém sua lógica, mas corrige os ranges para serem V4 puros.
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // Time Low
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), 

            // Time Mid
            mt_rand(0, 0xffff), 

            // Time High and Version (UUID v4)
            mt_rand(0x4000, 0x4fff), // (0x4) é a versão 4
            
            // Clock Seq and Reserved (10xx é a variação)
            mt_rand(0x8000, 0xbfff),
            
            // Node
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    // --------------------------------------------------------------------------

    /**
     * Gera um ID de rastreamento (Trace ID) personalizado no formato: LLLLLLDDXXXX
     * (6 Letras maiúsculas, 2 Dígitos, 4 Dígitos randômicos).
     */
    public function traceid(): string
    {
        // 1. OTIMIZAÇÃO: Uso de random_int (Criptograficamente seguro) e array_map/implode (mais limpo)

        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits = '0123456789';
        $letter_len = strlen($letters) - 1;
        $digit_len = strlen($digits) - 1;

        // Gera 6 letras
        $letters_part = implode('', array_map(fn() => $letters[random_int(0, $letter_len)], range(1, 6)));

        // Gera 2 dígitos
        $digits_part = implode('', array_map(fn() => $digits[random_int(0, $digit_len)], range(1, 2)));

        // Gera 4 dígitos randômicos (com segurança)
        $suffix_part = random_int(1000, 9999);

        return $letters_part . $digits_part . $suffix_part;
    }
       // --------------------------------------------------------------------------
    // NOVOS TIPOS DE HASH E TOKEN
    // --------------------------------------------------------------------------

    /**
     * Gera um Token Hexadecimal de comprimento criptograficamente seguro.
     * Ideal para tokens de sessão, chaves de recuperação de senha, etc.
     * @param int $length O comprimento do token final em caracteres (deve ser par).
     */
    public function tokenHex(int $length = 40): string
    {
        if ($length <= 0 || $length % 2 !== 0) {
            throw new InvalidArgumentException("Length must be a positive even number.");
        }
        
        // Usa a função nativa mais segura do PHP para gerar bytes aleatórios.
        // O número de bytes é metade do comprimento do token hexadecimal.
        $bytes = (int)($length / 2);
        
        try {
            // PHP 8.1+ ou extensões ativas (random_bytes é criptograficamente seguro)
            return bin2hex(random_bytes($bytes));
        } catch (\Exception $e) {
            // Fallback: embora random_bytes seja padrão, incluímos um fallback robusto.
            return bin2hex(openssl_random_pseudo_bytes($bytes));
        }
    }
    
    /**
     * Gera um Token Criptograficamente Seguro e URL-Safe em Base64.
     * Ideal para chaves de API (API Keys) ou tokens que precisam ser passados em URLs.
     * @param int $bytes O número de bytes brutos a serem gerados (ex: 32 bytes geram ~44 chars Base64).
     */
    public function tokenBase64(int $bytes = 32): string
    {
        if ($bytes <= 0) {
            throw new InvalidArgumentException("Bytes must be a positive number.");
        }

        try {
            $randomBytes = random_bytes($bytes);
        } catch (\Exception $e) {
            $randomBytes = openssl_random_pseudo_bytes($bytes);
        }
        
        // Converte para Base64. Opcionalmente, remove padding '=' e torna URL-safe.
        $token = base64_encode($randomBytes);
        
        // Remove caracteres que podem causar problemas em URLs (URL-safe)
        $token = strtr($token, '+/', '-_');
        
        // Remove padding (opcional, mas comum para tokens)
        return rtrim($token, '=');
    }

    /**
     * Gera um hash alfanumérico curto (ex: 8 caracteres).
     * Ideal para encurtadores de URL ou chaves de referência curtas.
     * NÃO deve ser usado para segurança (apenas unicidade/conveniência).
     * @param int $length Comprimento do hash final.
     */
    public function hashShortUrl(int $length = 8): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $chars_len = strlen($chars);
        $hash = '';
        
        // Uso de random_int para melhor distribuição e segurança
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[random_int(0, $chars_len - 1)];
        }
        
        return $hash;
    }
}