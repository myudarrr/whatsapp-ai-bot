<?php
/**
 * Setup Script untuk WhatsApp AI Bot
 * Jalankan: php setup.php
 */

echo "=== WhatsApp AI Bot - Setup Script ===\n\n";

// Check PHP version
echo "1. Checking PHP Version...\n";
$phpVersion = phpversion();
echo "   PHP Version: {$phpVersion}\n";
if (version_compare($phpVersion, '7.4.0', '<')) {
    echo "   ❌ PHP 7.4+ required\n";
    exit(1);
} else {
    echo "   ✅ PHP version OK\n";
}

echo "\n";

// Check PHP extensions
echo "2. Checking PHP Extensions...\n";
$required_extensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'mbstring'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ {$ext}: LOADED\n";
    } else {
        echo "   ❌ {$ext}: NOT LOADED\n";
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    echo "\n   ❌ Missing extensions: " . implode(', ', $missing_extensions) . "\n";
    echo "   Please install missing PHP extensions and try again.\n";
    exit(1);
}

echo "\n";

// Create directories
echo "3. Creating Directories...\n";
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
    
    // Check if writable
    if (is_writable($dir)) {
        echo "      ✅ Writable\n";
    } else {
        echo "      ❌ Not writable - please fix permissions\n";
    }
}

echo "\n";

// Load environment
echo "4. Loading Environment Configuration...\n";
if (file_exists('.env')) {
    echo "   ✅ .env file found\n";
    $env = parse_ini_file('.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
} else {
    echo "   ❌ .env file not found\n";
    echo "   Creating .env from .env.example...\n";
    if (file_exists('.env.example')) {
        copy('.env.example', '.env');
        echo "   ✅ .env created from .env.example\n";
    } else {
        echo "   ❌ .env.example not found\n";
    }
}

echo "\n";

// Test database connection
echo "5. Testing Database Connection...\n";
try {
    require_once 'config/config.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn) {
        echo "   ✅ Database connection: SUCCESS\n";
        
        // Check if tables exist
        $tables = ['users', 'whatsapp_connections', 'ai_configurations', 'chat_messages'];
        $existing_tables = [];
        
        foreach ($tables as $table) {
            $stmt = $conn->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->rowCount() > 0) {
                $existing_tables[] = $table;
                echo "   ✅ Table '{$table}': EXISTS\n";
            } else {
                echo "   ❌ Table '{$table}': NOT FOUND\n";
            }
        }
        
        if (count($existing_tables) < count($tables)) {
            echo "\n   💡 Some tables are missing. Please import database schema:\n";
            echo "      mysql -u root -p whatsapp_bot < database/whatsapp_bot.sql\n";
        } else {
            // Check sample data
            $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch();
            echo "   📊 Users in database: " . $result['count'] . "\n";
        }
        
    } else {
        echo "   ❌ Database connection: FAILED\n";
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
    echo "   💡 Please check database configuration in config/database.php\n";
}

echo "\n";

// Test classes
echo "6. Testing Classes...\n";
try {
    require_once 'config/config.php';
    
    $auth = new Auth();
    echo "   ✅ Auth class: OK\n";
    
    $ai = new AIService();
    echo "   ✅ AIService class: OK\n";
    
    $whatsapp = new WhatsAppBot();
    echo "   ✅ WhatsAppBot class: OK\n";
    
} catch (Exception $e) {
    echo "   ❌ Class loading error: " . $e->getMessage() . "\n";
}

echo "\n";

// Configuration summary
echo "7. Configuration Summary...\n";
echo "   📋 App Name: " . ($_ENV['APP_NAME'] ?? 'WhatsApp AI Bot') . "\n";
echo "   📋 App Version: " . ($_ENV['APP_VERSION'] ?? '1.0.0') . "\n";
echo "   📋 Environment: " . ($_ENV['APP_ENV'] ?? 'development') . "\n";
echo "   📋 Debug Mode: " . ($_ENV['APP_DEBUG'] ?? 'true') . "\n";
echo "   📋 Database: " . ($_ENV['DB_HOST'] ?? 'localhost') . "/" . ($_ENV['DB_NAME'] ?? 'whatsapp_bot') . "\n";
echo "   📋 Frontend URL: " . ($_ENV['FRONTEND_URL'] ?? 'http://localhost:5173') . "\n";
echo "   📋 Local Server: " . ($_ENV['LOCAL_SERVER_HOST'] ?? 'localhost') . ":" . ($_ENV['LOCAL_SERVER_PORT'] ?? '8080') . "\n";

echo "\n";

// Next steps
echo "=== SETUP COMPLETE ===\n\n";
echo "Next Steps:\n";
echo "1. 📊 Import Database (if not done yet):\n";
echo "   mysql -u root -p whatsapp_bot < database/whatsapp_bot.sql\n\n";

echo "2. ⚙️  Update Configuration:\n";
echo "   Edit .env file with your database credentials\n\n";

echo "3. 🚀 Start Development Server:\n";
echo "   php server.php\n";
echo "   or\n";
echo "   php -S localhost:8080\n\n";

echo "4. 🌐 Start Frontend:\n";
echo "   cd ../\n";
echo "   npm run dev\n\n";

echo "5. 🧪 Test API:\n";
echo "   Open: http://localhost:8080/test\n\n";

echo "6. 📱 Access Application:\n";
echo "   Open: http://localhost:5173\n\n";

echo "Default Login Credentials:\n";
echo "   Email: test@example.com\n";
echo "   Password: password\n\n";

echo "🎉 Setup completed successfully!\n";
echo "================================================\n";
?>
