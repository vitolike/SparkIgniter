<?php
// app/core/Controller.php
#[\AllowDynamicProperties]
class Controller {
    protected Input $input;
    protected Loader $load;
    public ?PDO $db;
    protected ?DB $qb = null;
    protected IdGenerator $idGen;
    protected JWT $jwt;
    protected HttpClient $httpClient;
    protected Session $session;
    protected ?array $user = null; // set by auth middleware

    private static array $autoload = [
        'libraries' => [],
        'helpers' => [],
        'models' => [],
    ];

    public function __construct() {
        
        $this->input = new Input();
        $this->idGen = new IdGenerator();
        $this->session = new Session();
        $this->httpClient = new HttpClient();
        $this->jwt = new JWT();
        
        $this->load = new Loader($this);
        $this->db = Database::getInstance();  // PDO
        $this->qb = $this->db ? new DB($this->db) : null;        // Query Builder
        $this->load->autoload(self::$autoload);
    }


    public static function setAutoload(array $autoload): void {
        self::$autoload = $autoload;
    }

    protected function view(string $view, array $data = []): void {
        $viewPath = APP_PATH . '/views/' . $view . '.php';
        if (!file_exists($viewPath)) {
            throw new RuntimeException("View not found: $view");
        }
        extract($data);
        require $viewPath;
    }

    // Basic auth middleware for MVC controllers too
    protected function requireAuth(): void {
        $jwt = new JWT();
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($auth, 'Bearer ')) {
            $token = substr($auth, 7);
            try {
                $payload = $jwt->decode($token);
                $this->user = $payload;
                return;
            } catch (Throwable $e) {}
        }
        http_response_code(401);
        echo 'Unauthorized';
        exit;
    }
}