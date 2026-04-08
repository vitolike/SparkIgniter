<?php
#[\AllowDynamicProperties]
class Service
{
    protected Input $input;
    public ?PDO $db;
    protected DB $qb;
    protected IdGenerator $idGen;
    protected ?array $user = null; // set by auth middleware
    public function __construct() {
        
        $this->input = new Input();
        $this->idGen = new IdGenerator();
        $this->db = Database::getInstance();  // PDO
        $this->qb = new DB($this->db);        // Query Builder
    }
    
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

}
