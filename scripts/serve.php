<?php

$envFile = __DIR__ . '/../.env';

if (!file_exists($envFile)) {
    die(".env file not found\n");
}

$env = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$config = [];

foreach ($env as $line) {
    $line = trim($line);

    // Skip comment
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }

    // Ambil KEY=VALUE
    [$key, $value] = array_pad(explode('=', $line, 2), 2, null);

    if ($key !== null && $value !== null) {
        $config[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
    }
}

$host = $config['APP_HOST'] ?? '127.0.0.1';
$port = $config['APP_PORT'] ?? '8000';

echo "Starting PHP server on {$host}:{$port}\n";

passthru(
    PHP_BINARY . " -S {$host}:{$port} -t " . escapeshellarg(__DIR__ . '/../public')
);
