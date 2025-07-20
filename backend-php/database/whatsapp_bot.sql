-- =============================================
-- WhatsApp AI Bot Database Schema
-- Database: whatsapp_bot
-- =============================================

-- Buat database
CREATE DATABASE IF NOT EXISTS whatsapp_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE whatsapp_bot;

-- =============================================
-- Tabel users untuk autentikasi
-- =============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- Tabel whatsapp_connections untuk status koneksi WhatsApp
-- =============================================
CREATE TABLE whatsapp_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('disconnected', 'connecting', 'connected', 'error') DEFAULT 'disconnected',
    phone_number VARCHAR(20) NULL,
    qr_code TEXT NULL,
    session_data LONGTEXT NULL,
    last_connected_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- Tabel ai_configurations untuk konfigurasi AI
-- =============================================
CREATE TABLE ai_configurations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ai_enabled BOOLEAN DEFAULT FALSE,
    ai_model VARCHAR(100) DEFAULT 'mixtral-8x7b-32768',
    system_prompt TEXT DEFAULT 'Anda adalah asisten WhatsApp yang membantu menjawab pesan dengan ramah dan informatif.',
    keywords_trigger JSON NULL,
    auto_reply_delay INT DEFAULT 3000,
    groq_api_key VARCHAR(255) NULL,
    max_tokens INT DEFAULT 500,
    temperature DECIMAL(3,2) DEFAULT 0.70,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- Tabel chat_messages untuk menyimpan pesan WhatsApp
-- =============================================
CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message_id VARCHAR(255) NULL,
    contact_number VARCHAR(20) NOT NULL,
    contact_name VARCHAR(255) NULL,
    message_text TEXT NOT NULL,
    message_type ENUM('text', 'image', 'audio', 'video', 'document') DEFAULT 'text',
    is_from_me BOOLEAN DEFAULT FALSE,
    ai_replied BOOLEAN DEFAULT FALSE,
    ai_reply_status ENUM('pending', 'sent', 'delivered', 'read', 'failed') NULL,
    ai_reply_text TEXT NULL,
    message_timestamp TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- Tabel auto_reply_logs untuk log balasan otomatis
-- =============================================
CREATE TABLE auto_reply_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    original_message TEXT NOT NULL,
    ai_response TEXT NOT NULL,
    success BOOLEAN DEFAULT TRUE,
    error_message TEXT NULL,
    response_time_ms INT NULL,
    tokens_used INT NULL,
    model_used VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- Tabel sessions untuk manajemen session
-- =============================================
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NULL,
    session_data TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- Tabel contacts untuk menyimpan kontak WhatsApp
-- =============================================
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    name VARCHAR(255) NULL,
    profile_pic_url TEXT NULL,
    is_blocked BOOLEAN DEFAULT FALSE,
    last_message_at TIMESTAMP NULL,
    message_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_contact (user_id, phone_number)
);

-- =============================================
-- Tabel message_templates untuk template pesan
-- =============================================
CREATE TABLE message_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- Indexes untuk optimasi performa
-- =============================================
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_active ON users(is_active);

CREATE INDEX idx_whatsapp_user ON whatsapp_connections(user_id);
CREATE INDEX idx_whatsapp_status ON whatsapp_connections(status);

CREATE INDEX idx_ai_config_user ON ai_configurations(user_id);
CREATE INDEX idx_ai_config_enabled ON ai_configurations(ai_enabled);

CREATE INDEX idx_messages_user_time ON chat_messages(user_id, created_at);
CREATE INDEX idx_messages_contact ON chat_messages(contact_number);
CREATE INDEX idx_messages_timestamp ON chat_messages(message_timestamp);
CREATE INDEX idx_messages_ai_replied ON chat_messages(ai_replied);

CREATE INDEX idx_logs_user_time ON auto_reply_logs(user_id, created_at);
CREATE INDEX idx_logs_success ON auto_reply_logs(success);

CREATE INDEX idx_sessions_user ON user_sessions(user_id);
CREATE INDEX idx_sessions_expires ON user_sessions(expires_at);

CREATE INDEX idx_contacts_user ON contacts(user_id);
CREATE INDEX idx_contacts_phone ON contacts(phone_number);
CREATE INDEX idx_contacts_last_message ON contacts(last_message_at);

CREATE INDEX idx_templates_user ON message_templates(user_id);
CREATE INDEX idx_templates_active ON message_templates(is_active);

-- =============================================
-- Data sample untuk testing
-- =============================================
INSERT INTO users (email, password, full_name, role, email_verified) VALUES 
('admin@whatsappbot.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', TRUE),
('test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test User', 'user', TRUE),
('demo@whatsappbot.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo User', 'user', TRUE);

-- Password untuk semua user di atas adalah: password

-- Sample AI configuration
INSERT INTO ai_configurations (user_id, ai_enabled, system_prompt) VALUES 
(2, FALSE, 'Anda adalah asisten WhatsApp yang membantu menjawab pesan dengan ramah dan informatif. Selalu gunakan bahasa Indonesia yang sopan dan profesional.'),
(3, FALSE, 'Saya adalah bot WhatsApp yang siap membantu Anda 24/7. Silakan tanyakan apa saja yang Anda butuhkan!');

-- Sample message templates
INSERT INTO message_templates (user_id, name, content) VALUES 
(2, 'Salam Pembuka', 'Halo! Terima kasih telah menghubungi kami. Ada yang bisa saya bantu?'),
(2, 'Jam Operasional', 'Jam operasional kami adalah Senin-Jumat 08:00-17:00 WIB. Di luar jam tersebut, pesan Anda akan dibalas pada hari kerja berikutnya.'),
(2, 'Terima Kasih', 'Terima kasih atas pertanyaan Anda. Semoga informasi yang diberikan bermanfaat!'),
(3, 'Auto Reply', 'Halo! Saat ini saya sedang tidak tersedia. Pesan Anda penting bagi saya dan akan saya balas secepatnya.');

-- =============================================
-- Views untuk reporting
-- =============================================
CREATE VIEW v_user_stats AS
SELECT 
    u.id,
    u.full_name,
    u.email,
    wc.status as whatsapp_status,
    wc.phone_number,
    ac.ai_enabled,
    COUNT(DISTINCT cm.id) as total_messages,
    COUNT(DISTINCT CASE WHEN cm.ai_replied = TRUE THEN cm.id END) as ai_replies,
    COUNT(DISTINCT c.id) as total_contacts,
    u.created_at as user_since
FROM users u
LEFT JOIN whatsapp_connections wc ON u.id = wc.user_id
LEFT JOIN ai_configurations ac ON u.id = ac.user_id
LEFT JOIN chat_messages cm ON u.id = cm.user_id
LEFT JOIN contacts c ON u.id = c.user_id
GROUP BY u.id;

CREATE VIEW v_daily_stats AS
SELECT 
    DATE(created_at) as date,
    user_id,
    COUNT(*) as total_messages,
    COUNT(CASE WHEN ai_replied = TRUE THEN 1 END) as ai_replies,
    COUNT(CASE WHEN is_from_me = FALSE THEN 1 END) as incoming_messages,
    COUNT(CASE WHEN is_from_me = TRUE THEN 1 END) as outgoing_messages
FROM chat_messages
GROUP BY DATE(created_at), user_id
ORDER BY date DESC;

-- =============================================
-- Stored Procedures
-- =============================================
DELIMITER //

CREATE PROCEDURE GetUserDashboardStats(IN p_user_id INT)
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM chat_messages WHERE user_id = p_user_id) as total_messages,
        (SELECT COUNT(*) FROM chat_messages WHERE user_id = p_user_id AND ai_replied = TRUE) as ai_replies,
        (SELECT COUNT(*) FROM contacts WHERE user_id = p_user_id) as total_contacts,
        (SELECT COUNT(*) FROM chat_messages WHERE user_id = p_user_id AND DATE(created_at) = CURDATE()) as today_messages,
        (SELECT COUNT(*) FROM auto_reply_logs WHERE user_id = p_user_id AND success = TRUE) as successful_replies,
        (SELECT AVG(response_time_ms) FROM auto_reply_logs WHERE user_id = p_user_id AND success = TRUE) as avg_response_time;
END //

CREATE PROCEDURE CleanupExpiredSessions()
BEGIN
    DELETE FROM user_sessions WHERE expires_at < NOW();
END //

DELIMITER ;

-- =============================================
-- Triggers
-- =============================================
DELIMITER //

CREATE TRIGGER update_contact_stats 
AFTER INSERT ON chat_messages
FOR EACH ROW
BEGIN
    INSERT INTO contacts (user_id, phone_number, name, last_message_at, message_count)
    VALUES (NEW.user_id, NEW.contact_number, NEW.contact_name, NEW.message_timestamp, 1)
    ON DUPLICATE KEY UPDATE
        name = COALESCE(NEW.contact_name, name),
        last_message_at = NEW.message_timestamp,
        message_count = message_count + 1;
END //

DELIMITER ;

-- =============================================
-- Events untuk maintenance otomatis
-- =============================================
SET GLOBAL event_scheduler = ON;

CREATE EVENT cleanup_expired_sessions
ON SCHEDULE EVERY 1 HOUR
DO
  CALL CleanupExpiredSessions();

CREATE EVENT cleanup_old_logs
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL 1 DAY
DO
  DELETE FROM auto_reply_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- =============================================
-- Selesai
-- =============================================
