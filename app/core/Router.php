<?php
// app/core/Router.php
#[\AllowDynamicProperties]
class Router {
    private array $config;

    public function __construct(array $config = []) {
        $this->config = $config;
    }

    private function cleanUri(string $uri): string {
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $uri = parse_url($uri, PHP_URL_PATH);
        if ($scriptDir && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }
        return trim($uri, '/');
    }

    public function dispatch(): void {
        $uri = $this->cleanUri($_SERVER['REQUEST_URI'] ?? '/');
        $segments = $uri ? explode('/', $uri) : [];

        // API prefix support: /api/Controller/method/...
        $isApi = false;
        if (!empty($segments) && strtolower($segments[0]) === 'api') {
            $isApi = true;
            array_shift($segments);
        }

        $controllerName = $segments[0] ?? 'Home';
        $method = $segments[1] ?? 'index';
        $params = array_slice($segments, 2);

        // Normalize controller class
        $controllerClass = $this->studly($controllerName);
        $controllerFile = $isApi
            ? APP_PATH . "/controllers/api/{$controllerClass}.php"
            : APP_PATH . "/controllers/{$controllerClass}.php";

        if (!file_exists($controllerFile)) {
            log_message('ERROR', "Not found: $controllerClass");
            $this->notFound("This '$controllerClass' not found");
            return;
        }
        require_once $controllerFile;

        if (!class_exists($controllerClass)) {
            log_message('ERROR', "Controller class missing: $controllerClass");
            $this->notFound("Controller class '$controllerClass' missing");
            return;
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            log_message('ERROR', "Method not found: $controllerClass::$method");
            $this->notFound("Method '$method' not found in controller '$controllerClass'");
            return;
        }

        call_user_func_array([$controller, $method], $params);
    }

    private function notFound(string $msg): void {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not Found', 'message' => $msg]);
    }

    private function studly(string $value): string {
        $value = str_replace(['-', '_'], ' ', $value);
        $value = ucwords($value);
        return str_replace(' ', '', $value);
    }
}