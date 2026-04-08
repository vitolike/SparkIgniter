<?php
#[\AllowDynamicProperties]

class Home extends RestController {
    
    public function index(): void {
        date_default_timezone_set('America/Sao_Paulo');
        $dbOk = $this->db !== null;

        log_message('INFO', 'Acessou SparkIgniter no php ' . PHP_VERSION . ' com banco ' . ($dbOk ? 'OK' : 'NOK'));
        $this->response([
            'app' => 'SparkIgniter',
            'message' => 'Hello World of SparkIgniter!',
            'php' => PHP_VERSION,
            'db'  => $dbOk ? 'connected' : 'not_connected',
            'ip' => $this->input->ip(),
            'timestamp' => date('Y-m-d H:i:s')
        ], self::HTTP_OK);
    }
     
}