<?php
declare(strict_types=1);

/**
 * Session - Implementação estilo CodeIgniter 3 para PHP 8.4
 *
 * Esta classe atua como um wrapper seguro para o array $_SESSION do PHP.
 */

/**
 * Guia Rápido de Uso da Classe Session (CI3-like no PHP 8.4)
 *
 * Esta classe simula o comportamento de $this->session do CodeIgniter,
 * centralizando o gerenciamento de sessões com segurança de ponta do PHP 8.4.
 *
 * 1. Configurar/Inicializar:
 * A instância da classe Session deve ser criada uma única vez (normalmente no index.php)
 * e injetada no Controller ou Service principal, geralmente acessível via $this->session.
 * Isso garante que as configurações de segurança (como session.cookie_secure e
 * session.use_strict_mode) sejam aplicadas imediatamente e o session_start()
 * seja chamado de forma segura.
 *
 * 2. Definir Dados (set_userdata):
 * Use set_userdata() para adicionar ou atualizar valores na sessão.
 * - Sintaxe por par: $this->session->set_userdata('user_id', 42);
 * - Sintaxe por array: $this->session->set_userdata(['nome' => 'João', 'logado' => true]);
 *
 * 3. Obter Dados (userdata, has_userdata, all_userdata):
 * - Obter valor: $id = $this->session->userdata('user_id'); // Retorna o valor ou null
 * - Verificar existência: if ($this->session->has_userdata('logado')) { ... }
 * - Obter todos: $all = $this->session->all_userdata(); // Retorna array com todos os dados
 *
 * 4. Remover Dados (unset_userdata, sess_destroy):
 * - Remover chave: $this->session->unset_userdata('username');
 * - Remover múltiplas: $this->session->unset_userdata(['item1', 'item2']);
 * - Destruir Tudo: $this->session->sess_destroy(); // Encerra a sessão, limpa dados e cookies.
 */
class Session
{
    /**
     * @var bool Flag para saber se a sessão já foi iniciada.
     */
    protected static bool $is_started = false;

    /**
     * Inicia a sessão PHP e aplica configurações de segurança recomendadas.
     * Deve ser chamado antes de qualquer output.
     */
    public function __construct()
    {
        if (self::$is_started === false) {
            // 1. Configurações de Segurança essenciais no PHP 8.4
            // Garante que o ID da sessão só seja transmitido via cookie (e não URL)
            ini_set('session.use_only_cookies', '1'); 
            
            // Garante que o cookie só seja enviado em conexões HTTPS
            ini_set('session.cookie_secure', '1'); 
            
            // Impede acesso ao cookie via JavaScript (mitiga XSS)
            ini_set('session.cookie_httponly', '1'); 
            
            // Rejeita IDs de sessão não inicializados (mitiga Session Fixation)
            ini_set('session.use_strict_mode', '1');

            // 2. Inicia a sessão
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            self::$is_started = true;
            
            // 3. Boa Prática: Regenerar o ID após a primeira carga
            // Isso previne ataques de fixação de sessão.
            if (!isset($_SESSION['__session_initialized'])) {
                session_regenerate_id(true);
                $_SESSION['__session_initialized'] = true;
            }
        }
    }

    /* ----------------------------------------------------------------------
     * SETTERS
     * -------------------------------------------------------------------- */

    /**
     * Adiciona dados à sessão. Simula set_userdata($key, $value) ou set_userdata($array).
     */
    public function set_userdata(array|string $key, mixed $value = null): void
    {
        if (is_array($key)) {
            // Se for um array associativo
            $_SESSION = array_merge($_SESSION, $key);
            return;
        }

        // Se for key/value
        $_SESSION[$key] = $value;
    }

    /* ----------------------------------------------------------------------
     * GETTERS / CHECKERS
     * -------------------------------------------------------------------- */

    /**
     * Obtém um valor da sessão. Simula $this->session->userdata('key')
     */
    public function userdata(string $key): mixed
    {
        // Retorna o valor ou null se não existir (melhor que o FALSE do CI3 original)
        return $_SESSION[$key] ?? null; 
    }
    
    /**
     * Verifica se uma chave existe na sessão. Simula has_userdata('key').
     */
    public function has_userdata(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Obtém todos os dados da sessão (excluindo os internos do sistema, se houver)
     */
    public function all_userdata(): array
    {
        // Retorna uma cópia do array de sessão
        $data = $_SESSION;
        
        // Remove as chaves internas que não devem ser visíveis ao usuário
        unset($data['__session_initialized']); 
        
        return $data;
    }

    /* ----------------------------------------------------------------------
     * REMOÇÃO
     * -------------------------------------------------------------------- */

    /**
     * Remove dados da sessão. Simula unset_userdata('key') ou unset_userdata($array).
     */
    public function unset_userdata(array|string $key): void
    {
        $keys = is_array($key) ? $key : [$key];

        foreach ($keys as $k) {
            if (isset($_SESSION[$k])) {
                unset($_SESSION[$k]);
            }
        }
    }

    /**
     * Destrói a sessão completamente.
     */
    public function sess_destroy(): void
    {
        // Limpa todas as variáveis de sessão
        $_SESSION = [];
        
        // Se usar cookies, remove o cookie de sessão do navegador
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        // Finalmente, destrói a sessão no servidor
        session_destroy();
        self::$is_started = false;
    }
}