<?php
/**
 * Konfigurasi Aplikasi WhatsApp AI Bot
 */

// Konfigurasi aplikasi
define('APP_NAME', 'WhatsApp AI Bot');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost');
define('APP_DEBUG', true);

// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_NAME', 'whatsapp_bot');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Konfigurasi session
define('SESSION_LIFETIME', 3600); // 1 jam
define('SESSION_NAME', 'WHATSAPP_BOT_SESSION');
define('SESSION_COOKIE_SECURE', false); // Set true untuk HTTPS
define('SESSION_COOKIE_HTTPONLY', true);

// Konfigurasi JWT
define('JWT_SECRET', 'whatsapp-bot-secret-key-change-in-production-2024');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 86400); // 24 jam

// Konfigurasi CORS
define('CORS_ORIGIN', 'http://localhost:5173');
define('CORS_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('CORS_HEADERS', 'Content-Type, Authorization, X-Requested-With');

// Konfigurasi WhatsApp
define('WHATSAPP_SESSION_PATH', __DIR__ . '/../storage/sessions/');
define('WHATSAPP_QR_PATH', __DIR__ . '/../storage/qr/');
define('WHATSAPP_TIMEOUT', 30); // detik

// Konfigurasi Groq API
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
define('GROQ_DEFAULT_MODEL', 'mixtral-8x7b-32768');
define('GROQ_MAX_TOKENS', 500);
define('GROQ_TEMPERATURE', 0.7);

// Konfigurasi file upload
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_PATH', __DIR__ . '/../storage/uploads/');
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Konfigurasi logging
define('LOG_PATH', __DIR__ . '/../storage/logs/');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_MAX_FILES', 30); // Simpan log 30 hari

// Konfigurasi rate limiting
define('RATE_LIMIT_REQUESTS', 100); // requests per window
define('RATE_LIMIT_WINDOW', 3600); // 1 jam

// Konfigurasi email (untuk notifikasi)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@whatsappbot.com');
define('SMTP_FROM_NAME', 'WhatsApp Bot');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Autoload classes
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../classes/',
        __DIR__ . '/../models/',
        __DIR__ . '/../controllers/',
        __DIR__ . '/../services/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Helper functions
function response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function error_response($message, $status = 400) {
    response(['error' => $message, 'status' => $status], $status);
}

function success_response($data = [], $message = 'Success') {
    response(['success' => true, 'message' => $message, 'data' => $data]);
}

function validate_required($data, $required_fields) {
    $missing = [];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        error_response('Missing required fields: ' . implode(', ', $missing), 422);
    }
}

function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function log_message($level, $message, $context = []) {
    $log_file = LOG_PATH . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? ' ' . json_encode($context) : '';
    $log_entry = "[{$timestamp}] {$level}: {$message}{$context_str}" . PHP_EOL;
    
    // Buat direktori jika belum ada
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

function create_directories() {
    $dirs = [
        WHATSAPP_SESSION_PATH,
        WHATSAPP_QR_PATH,
        UPLOAD_PATH,
        LOG_PATH
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// Buat direktori yang diperlukan
create_directories();

// Set CORS headers
function set_cors_headers() {
    header('Access-Control-Allow-Origin: ' . CORS_ORIGIN);
    header('Access-Control-Allow-Methods: ' . CORS_METHODS);
    header('Access-Control-Allow-Headers: ' . CORS_HEADERS);
    header('Access-Control-Allow-Credentials: true');
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    set_cors_headers();
    http_response_code(200);
    exit;
}

// Set CORS headers untuk semua request
set_cors_headers();
?>
