<?php
/**
 * Basic Test Script (tanpa database)
 * Untuk test komponen dasar sebelum setup database
 */

echo "=== WhatsApp AI Bot - Basic Test ===\n\n";

// Test 1: PHP Version
echo "1. PHP Version Check...\n";
$phpVersion = phpversion();
echo "   PHP Version: {$phpVersion}\n";
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "   ✅ PHP version OK\n";
} else {
    echo "   ❌ PHP 7.4+ required\n";
}

echo "\n";

// Test 2: PHP Extensions
echo "2. PHP Extensions Check...\n";
$extensions = [
    'pdo' => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'curl' => extension_loaded('curl'),
    'json' => extension_loaded('json'),
    'mbstring' => extension_loaded('mbstring')
];

foreach ($extensions as $ext => $loaded) {
    if ($loaded) {
        echo "   ✅ {$ext}: LOADED\n";
    } else {
        echo "   ❌ {$ext}: NOT LOADED\n";
    }
}

echo "\n";

// Test 3: Directory Creation
echo "3. Directory Setup...\n";
$directories = [
    'storage',
    'storage/logs',
    'storage/sessions',
    'storage/qr',
    'storage/uploads'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "   ✅ Created: {$dir}\n";
        } else {
            echo "   ❌ Failed to create: {$dir}\n";
        }
    } else {
        echo "   ✅ Exists: {$dir}\n";
    }
    
    if (is_writable($dir)) {
        echo "      ✅ Writable\n";
    } else {
        echo "      ❌ Not writable\n";
    }
}

echo "\n";

// Test 4: Configuration Files
echo "4. Configuration Files...\n";
$config_files = [
    '.env' => 'Environment configuration',
    'config/config.php' => 'Main configuration',
    'config/database.php' => 'Database configuration',
    'classes/Auth.php' => 'Authentication class',
    'classes/AIService.php' => 'AI Service class',
    'classes/WhatsAppBot.php' => 'WhatsApp Bot class'
];

foreach ($config_files as $file => $desc) {
    if (file_exists($file)) {
        echo "   ✅ {$file}: EXISTS ({$desc})\n";
    } else {
        echo "   ❌ {$file}: MISSING ({$desc})\n";
    }
}

echo "\n";

// Test 5: Basic PHP Server Test
echo "5. PHP Built-in Server Test...\n";
$host = 'localhost';
$port = '8080';
echo "   📋 Server will run at: http://{$host}:{$port}\n";
echo "   📋 Document root: " . __DIR__ . "\n";

// Test 6: Environment Variables
echo "\n6. Environment Configuration...\n";
if (file_exists('.env')) {
    echo "   ✅ .env file found\n";
    $env = parse_ini_file('.env');
    echo "   📋 App Name: " . ($env['APP_NAME'] ?? 'Not set') . "\n";
    echo "   📋 App Version: " . ($env['APP_VERSION'] ?? 'Not set') . "\n";
    echo "   📋 Debug Mode: " . ($env['APP_DEBUG'] ?? 'Not set') . "\n";
    echo "   📋 Frontend URL: " . ($env['FRONTEND_URL'] ?? 'Not set') . "\n";
} else {
    echo "   ❌ .env file not found\n";
}

echo "\n";

// Installation Instructions
echo "=== INSTALLATION INSTRUCTIONS ===\n\n";

if (!extension_loaded('pdo_mysql')) {
    echo "🔧 MISSING PHP EXTENSION: pdo_mysql\n";
    echo "   Install commands:\n";
    echo "   • Ubuntu/Debian: sudo apt install php-mysql\n";
    echo "   • CentOS/RHEL: sudo yum install php-mysql\n";
    echo "   • macOS (Homebrew): brew install php (usually included)\n";
    echo "   • Windows (XAMPP): Already included\n";
    echo "   • Docker: Use php:8.2-apache image\n\n";
}

echo "📊 DATABASE SETUP:\n";
echo "1. Install MySQL/MariaDB\n";
echo "2. Create database:\n";
echo "   mysql -u root -p -e \"CREATE DATABASE whatsapp_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\"\n";
echo "3. Import schema:\n";
echo "   mysql -u root -p whatsapp_bot < database/whatsapp_bot.sql\n\n";

echo "🚀 START DEVELOPMENT SERVER:\n";
echo "1. Fix PHP extensions (if needed)\n";
echo "2. Setup database\n";
echo "3. Run: php server.php\n";
echo "4. Or: php -S localhost:8080\n\n";

echo "🌐 FRONTEND SETUP:\n";
echo "1. cd ../\n";
echo "2. npm install\n";
echo "3. npm run dev\n\n";

echo "🧪 TEST API:\n";
echo "   http://localhost:8080/test\n\n";

echo "📱 ACCESS APP:\n";
echo "   http://localhost:5173\n\n";

// Summary
$all_good = extension_loaded('pdo') && 
           extension_loaded('curl') && 
           extension_loaded('json') && 
           extension_loaded('mbstring') &&
           file_exists('.env') &&
           file_exists('config/config.php');

if ($all_good && extension_loaded('pdo_mysql')) {
    echo "🎉 ALL CHECKS PASSED! Ready to run.\n";
    echo "   Next: php server.php\n";
} elseif ($all_good) {
    echo "⚠️  ALMOST READY! Just install pdo_mysql extension.\n";
    echo "   Then: php server.php\n";
} else {
    echo "❌ SETUP INCOMPLETE. Please fix the issues above.\n";
}

echo "\n================================================\n";
?>
