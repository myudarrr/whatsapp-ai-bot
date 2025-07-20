<?php
/**
 * Test Script untuk WhatsApp AI Bot Backend
 * Jalankan: php test.php
 */

require_once 'config/config.php';

echo "=== WhatsApp AI Bot Backend Test ===\n\n";

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
try {
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        echo "   ✅ Database connection: SUCCESS\n";
        
        // Test query
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "   ✅ Users table accessible: " . $result['count'] . " users found\n";
    } else {
        echo "   ❌ Database connection: FAILED\n";
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Directory Permissions
echo "2. Testing Directory Permissions...\n";
$directories = [
    'storage/logs',
    'storage/sessions', 
    'storage/qr',
    'storage/uploads'
];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "   ✅ $dir: WRITABLE\n";
        } else {
            echo "   ❌ $dir: NOT WRITABLE\n";
        }
    } else {
        echo "   ❌ $dir: DIRECTORY NOT FOUND\n";
    }
}

echo "\n";

// Test 3: PHP Extensions
echo "3. Testing PHP Extensions...\n";
$required_extensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'mbstring'];

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ $ext: LOADED\n";
    } else {
        echo "   ❌ $ext: NOT LOADED\n";
    }
}

echo "\n";

// Test 4: Configuration
echo "4. Testing Configuration...\n";
echo "   📋 App Name: " . APP_NAME . "\n";
echo "   📋 App Version: " . APP_VERSION . "\n";
echo "   📋 Database: " . DB_HOST . "/" . DB_NAME . "\n";
echo "   📋 CORS Origin: " . CORS_ORIGIN . "\n";
echo "   📋 Log Path: " . LOG_PATH . "\n";

echo "\n";

// Test 5: Classes
echo "5. Testing Classes...\n";
try {
    $auth = new Auth();
    echo "   ✅ Auth class: LOADED\n";
} catch (Exception $e) {
    echo "   ❌ Auth class: ERROR - " . $e->getMessage() . "\n";
}

try {
    $ai = new AIService();
    echo "   ✅ AIService class: LOADED\n";
} catch (Exception $e) {
    echo "   ❌ AIService class: ERROR - " . $e->getMessage() . "\n";
}

try {
    $whatsapp = new WhatsAppBot();
    echo "   ✅ WhatsAppBot class: LOADED\n";
} catch (Exception $e) {
    echo "   ❌ WhatsAppBot class: ERROR - " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Sample Data
echo "6. Testing Sample Data...\n";
try {
    $db_helper = new DatabaseHelper();
    
    // Check sample users
    $users = $db_helper->fetchAll("SELECT email, full_name FROM users LIMIT 3");
    echo "   📋 Sample Users:\n";
    foreach ($users as $user) {
        echo "      - " . $user['email'] . " (" . $user['full_name'] . ")\n";
    }
    
    // Check AI models
    $ai = new AIService();
    $models = $ai->getAvailableModels();
    echo "   📋 Available AI Models: " . count($models) . " models\n";
    
} catch (Exception $e) {
    echo "   ❌ Sample data error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 7: Logging
echo "7. Testing Logging...\n";
try {
    log_message('INFO', 'Test log message from test script');
    $log_file = LOG_PATH . date('Y-m-d') . '.log';
    if (file_exists($log_file)) {
        echo "   ✅ Logging: SUCCESS - Log file created\n";
        echo "   📋 Log file: $log_file\n";
    } else {
        echo "   ❌ Logging: FAILED - Log file not created\n";
    }
} catch (Exception $e) {
    echo "   ❌ Logging error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 8: API Endpoints (jika dijalankan via web server)
if (isset($_SERVER['HTTP_HOST'])) {
    echo "8. Testing API Endpoints...\n";
    $base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
    
    // Test basic endpoint
    $test_url = $base_url . "/test";
    echo "   📋 Test URL: $test_url\n";
    echo "   💡 Buka URL tersebut di browser untuk test API\n";
} else {
    echo "8. API Endpoint Test (Skipped - not running via web server)\n";
    echo "   💡 Jalankan via web server untuk test API endpoints\n";
}

echo "\n";

// Summary
echo "=== TEST SUMMARY ===\n";
echo "✅ Jika semua test SUCCESS, backend siap digunakan\n";
echo "❌ Jika ada yang FAILED, perbaiki sesuai error message\n";
echo "\n";
echo "Next Steps:\n";
echo "1. Import database: mysql -u root -p whatsapp_bot < database/whatsapp_bot.sql\n";
echo "2. Update config di config/config.php sesuai environment\n";
echo "3. Test API via browser: http://localhost/backend-php/test\n";
echo "4. Update frontend untuk connect ke backend PHP\n";
echo "\n";
echo "=== END TEST ===\n";
?>
