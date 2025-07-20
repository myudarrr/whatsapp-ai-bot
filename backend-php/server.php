<?php
/**
 * Local Development Server untuk WhatsApp AI Bot
 * Jalankan: php server.php
 */

// Load environment variables
if (file_exists('.env')) {
    $env = parse_ini_file('.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

$host = $_ENV['LOCAL_SERVER_HOST'] ?? 'localhost';
$port = $_ENV['LOCAL_SERVER_PORT'] ?? '8080';
$docroot = __DIR__;

echo "=== WhatsApp AI Bot - Local Development Server ===\n";
echo "Starting server at: http://{$host}:{$port}\n";
echo "Document root: {$docroot}\n";
echo "Frontend URL: " . ($_ENV['FRONTEND_URL'] ?? 'http://localhost:5173') . "\n";
echo "\nPress Ctrl+C to stop the server\n";
echo "================================================\n\n";

// Start PHP built-in server
$command = "php -S {$host}:{$port} -t {$docroot}";
echo "Executing: {$command}\n\n";

// Execute the server
passthru($command);
?>
