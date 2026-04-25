<?php
/**
 * db.php — Database connection helper
 * Loads credentials from .env file so they are never hardcoded.
 * Include this file in api.php and auth.php.
 */
function getDbConnection(): mysqli {
    // Load .env file if it exists
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }

    $host   = $_ENV['DB_HOST'] ?? 'localhost';
    $user   = $_ENV['DB_USER'] ?? '';
    $pass   = $_ENV['DB_PASS'] ?? '';
    $dbname = $_ENV['DB_NAME'] ?? '';

    $conn = new mysqli($host, $user, $pass, $dbname);

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["error" => "Database connection failed"]);
        exit;
    }

    return $conn;
}
?>
