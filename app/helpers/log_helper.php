<?php
// app/helpers/log_helper.php
if (!function_exists('log_message')) {
    function log_message(string $level, string $message): void {
        $level = strtoupper($level);
        $allowed = ['INFO','DEBUG','ERROR'];
        if (!in_array($level, $allowed)) $level = 'INFO';

        $timestamp = date('Y-m-d H:i:s');
        $line = '[' . $timestamp . "] $level: $message" . PHP_EOL;

        // Determine channel
        $channel = null;
        if (class_exists('Env')) {
            $channel = Env::get('LOG_CHANNEL', null); // file|stdout|stderr|both|auto
        }
        if ($channel === null) {
            $channel = getenv('LOG_CHANNEL') ?: 'auto';
        }

        // Auto detection: prefer stderr on managed cloud runtimes
        if ($channel === 'auto') {
            $cloudHints = [
                'K_SERVICE',               // Cloud Run
                'GAE_INSTANCE',            // App Engine
                'AWS_EXECUTION_ENV',       // AWS (Lambda / Fargate)
                'ECS_CONTAINER_METADATA_URI', // AWS ECS
                'WEBSITE_INSTANCE_ID',     // Azure App Service
                'DYNO',                    // Heroku
            ];
            $isCloud = false;
            foreach ($cloudHints as $hint) {
                if (getenv($hint)) { $isCloud = True; break; }
            }
            $channel = $isCloud ? 'stderr' : 'file';
        }

        // Ensure log dir for file channel
        if (in_array($channel, ['file','both'], true)) {
            $target = defined('LOG_PATH') ? LOG_PATH : (__DIR__ . '/../../storage/logs/app.log');
            $dir = dirname($target);
            if (!is_dir($dir)) @mkdir($dir, 0777, true);
            @file_put_contents($target, $line, FILE_APPEND);
        }

        // Write to stderr if needed
        if (in_array($channel, ['stderr','both'], true)) {
            // error_log writes to the SAPI error log, typically stderr in containers
            @error_log(rtrim($line, PHP_EOL));
        }

        // Allow pure stdout if explicitly requested
        if ($channel === 'stdout') {
            $fp = @fopen('php://stdout', 'w');
            if ($fp) { @fwrite($fp, $line); @fclose($fp); }
        }
    }
}
