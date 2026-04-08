<?php
// app/core/Loader.php
#[\AllowDynamicProperties]
class Loader {
    private Controller $controller;

    public function __construct(Controller $controller) {
        $this->controller = $controller;
    }

    public function model(string $name): void {
        $class = $this->normalize($name);
        $file = APP_PATH . '/models/' . $class . '.php';
        if (!file_exists($file)) {
            throw new RuntimeException("Model not found: $class");
        }
        require_once $file;
        $this->controller->$class = new $class($this->controller->db);
    }

    public function library(string $name): void {
        $class = $this->normalize($name);
        $paths = [
            APP_PATH . '/libraries/' . $class . '.php',
            APP_PATH . '/core/' . $class . '.php', // allow core libs like JWT
        ];
        $found = false;
        foreach ($paths as $file) {
            if (file_exists($file)) { require_once $file; $found = true; break; }
        }
        if (!$found) throw new RuntimeException("Library not found: $class");
        $this->controller->{"\${$class}"} = null; // prevent notices
        $this->controller->$class = new $class();
    }

    public function helper(string $name): void {
        $file = APP_PATH . '/helpers/' . $name . '_helper.php';

        if (!file_exists($file)) {
            // fallback: try with string concat to avoid + mistake
            $file = APP_PATH . '/helpers/' . $name . '_helper.php';
            if (!file_exists($file)) {
                throw new RuntimeException("Helper not found: $name");
            }
        }
        require_once $file;
    }

    public function service(string $name): void {
        $class = $this->normalize($name);
        $file = APP_PATH . '/services/' . $class . '.php';

        $base = APP_PATH . '/core/Services.php';
        if (file_exists($base) && !class_exists('Service', false)) {
            require_once $base;
        }

        if (!file_exists($file)) {
            throw new RuntimeException("services not found: $class");
        }
        require_once $file;
        $this->controller->$class = new $class($this->controller->db);
    }

    public function autoload(array $cfg): void {
        foreach ($cfg['helpers'] ?? [] as $h) $this->helper($h);
        foreach ($cfg['libraries'] ?? [] as $l) $this->library($l);
        foreach ($cfg['models'] ?? [] as $m) $this->model($m);
    }

    private function normalize(string $name): string {
        $name = str_replace(['/', '\\'], '', $name);
        if (str_ends_with(strtolower($name), '.php')) $name = substr($name, 0, -4);
        return $name;
    }
}