# WhatsApp AI Bot - Backend PHP

Backend API untuk WhatsApp AI Bot menggunakan PHP dan MySQL.

## Fitur

- ✅ **REST API** - Endpoint lengkap untuk semua fitur
- ✅ **Autentikasi** - Login, register, session management
- ✅ **WhatsApp Integration** - Koneksi dan manajemen pesan
- ✅ **AI Integration** - Groq API untuk auto-reply
- ✅ **Database MySQL** - Penyimpanan data terstruktur
- ✅ **Logging** - System logging untuk debugging
- ✅ **Security** - CORS, input validation, SQL injection protection

## Struktur Direktori

```
backend-php/
├── classes/           # Class-class utama
│   ├── Auth.php      # Autentikasi dan session
│   ├── AIService.php # Integrasi AI/Groq
│   └── WhatsAppBot.php # WhatsApp operations
├── config/           # Konfigurasi
│   ├── config.php    # Konfigurasi aplikasi
│   └── database.php  # Konfigurasi database
├── database/         # Database schema
│   └── whatsapp_bot.sql # SQL untuk membuat database
├── storage/          # File storage (auto-created)
│   ├── logs/         # Log files
│   ├── sessions/     # WhatsApp sessions
│   ├── qr/          # QR codes
│   └── uploads/     # File uploads
├── .htaccess        # Apache URL rewriting
├── index.php        # Main API endpoint
└── README.md        # Dokumentasi ini
```

## Instalasi

### 1. Requirements

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Apache dengan mod_rewrite enabled
- cURL extension enabled
- JSON extension enabled

### 2. Setup Database

1. Buat database MySQL:
```sql
CREATE DATABASE whatsapp_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import schema database:
```bash
mysql -u root -p whatsapp_bot < database/whatsapp_bot.sql
```

3. Update konfigurasi database di `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'whatsapp_bot';
private $username = 'root';
private $password = 'your_password';
```

### 3. Konfigurasi

Edit file `config/config.php` sesuai kebutuhan:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'whatsapp_bot');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');

// CORS untuk frontend
define('CORS_ORIGIN', 'http://localhost:5173');

// JWT Secret (ganti dengan key yang aman)
define('JWT_SECRET', 'your-secret-key-here');
```

### 4. Permissions

Pastikan direktori storage dapat ditulis:
```bash
chmod -R 755 storage/
```

### 5. Apache Virtual Host (Opsional)

Buat virtual host untuk API:
```apache
<VirtualHost *:80>
    ServerName whatsapp-api.local
    DocumentRoot /path/to/backend-php
    
    <Directory /path/to/backend-php>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Register user baru |
| POST | `/auth/login` | Login user |
| POST | `/auth/logout` | Logout user |
| GET | `/auth/me` | Get user info |
| PUT | `/auth/profile` | Update profile |
| PUT | `/auth/password` | Change password |

### WhatsApp

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/whatsapp/status` | Get connection status |
| POST | `/whatsapp/connect` | Generate QR code |
| POST | `/whatsapp/disconnect` | Disconnect WhatsApp |
| POST | `/whatsapp/send` | Send message |
| GET | `/whatsapp/contacts` | Get contacts |
| PUT | `/whatsapp/block` | Block/unblock contact |

### AI Configuration

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/ai/config` | Get AI config |
| PUT | `/ai/config` | Update AI config |
| GET | `/ai/models` | Get available models |
| POST | `/ai/test` | Test AI response |
| GET | `/ai/stats` | Get AI statistics |

### Messages

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/messages` | Get messages |
| POST | `/messages/webhook` | Webhook for incoming messages |

### Statistics

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/stats` | Get dashboard stats |

### Test

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/test` | API info |
| GET | `/test/db` | Test database connection |
| POST | `/test/groq` | Test Groq API |

## Contoh Penggunaan

### Register User

```bash
curl -X POST http://localhost/backend-php/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "full_name": "John Doe"
  }'
```

### Login

```bash
curl -X POST http://localhost/backend-php/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

### Update AI Config

```bash
curl -X PUT http://localhost/backend-php/ai/config \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_SESSION_ID" \
  -d '{
    "ai_enabled": true,
    "ai_model": "mixtral-8x7b-32768",
    "system_prompt": "Anda adalah asisten WhatsApp yang ramah",
    "groq_api_key": "your_groq_api_key",
    "auto_reply_delay": 3000
  }'
```

### Test AI Response

```bash
curl -X POST http://localhost/backend-php/ai/test \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_SESSION_ID" \
  -d '{
    "message": "Halo, apa kabar?"
  }'
```

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Success message",
  "data": {
    // Response data
  }
}
```

### Error Response
```json
{
  "error": "Error message",
  "status": 400
}
```

## Database Schema

### Users
- `id` - Primary key
- `email` - Email user (unique)
- `password` - Hashed password
- `full_name` - Nama lengkap
- `role` - Role user (admin/user)
- `is_active` - Status aktif
- `created_at`, `updated_at` - Timestamps

### WhatsApp Connections
- `id` - Primary key
- `user_id` - Foreign key ke users
- `status` - Status koneksi (disconnected/connecting/connected/error)
- `phone_number` - Nomor WhatsApp
- `qr_code` - QR code untuk koneksi
- `session_data` - Data session WhatsApp
- `last_connected_at` - Waktu terakhir terhubung

### AI Configurations
- `id` - Primary key
- `user_id` - Foreign key ke users
- `ai_enabled` - Status AI aktif
- `ai_model` - Model AI yang digunakan
- `system_prompt` - System prompt untuk AI
- `keywords_trigger` - Keywords trigger (JSON)
- `auto_reply_delay` - Delay auto reply (ms)
- `groq_api_key` - API key Groq

### Chat Messages
- `id` - Primary key
- `user_id` - Foreign key ke users
- `message_id` - ID pesan WhatsApp
- `contact_number` - Nomor kontak
- `contact_name` - Nama kontak
- `message_text` - Isi pesan
- `message_type` - Tipe pesan (text/image/audio/etc)
- `is_from_me` - Apakah pesan dari user
- `ai_replied` - Apakah sudah dibalas AI
- `ai_reply_text` - Teks balasan AI
- `message_timestamp` - Waktu pesan

### Auto Reply Logs
- `id` - Primary key
- `user_id` - Foreign key ke users
- `contact_number` - Nomor kontak
- `original_message` - Pesan asli
- `ai_response` - Respons AI
- `success` - Status berhasil
- `error_message` - Pesan error (jika ada)
- `response_time_ms` - Waktu respons (ms)
- `tokens_used` - Token yang digunakan
- `model_used` - Model yang digunakan

## Security

### Input Validation
- Semua input divalidasi dan disanitasi
- Prepared statements untuk mencegah SQL injection
- CSRF protection dengan session validation

### Authentication
- Session-based authentication
- Password hashing dengan bcrypt
- Session timeout dan cleanup

### CORS
- Konfigurasi CORS untuk frontend
- Whitelist origin yang diizinkan

### File Security
- Proteksi file sensitif (.sql, .log, .env)
- Directory browsing disabled
- File upload validation

## Logging

Log disimpan di `storage/logs/` dengan format:
```
[2024-01-01 12:00:00] INFO: Message logged {"context": "data"}
```

Level log:
- `DEBUG` - Debug information
- `INFO` - General information
- `WARNING` - Warning messages
- `ERROR` - Error messages

## Troubleshooting

### Database Connection Error
- Periksa konfigurasi database di `config/database.php`
- Pastikan MySQL service berjalan
- Periksa username/password database

### CORS Error
- Update `CORS_ORIGIN` di `config/config.php`
- Pastikan frontend URL sesuai

### Permission Error
- Periksa permission direktori `storage/`
- Pastikan web server dapat menulis ke direktori tersebut

### API Not Working
- Periksa Apache mod_rewrite enabled
- Periksa file `.htaccess` ada dan readable
- Periksa error log Apache

## Development

### Testing
```bash
# Test database connection
curl http://localhost/backend-php/test/db

# Test API
curl http://localhost/backend-php/test

# Test Groq API
curl -X POST http://localhost/backend-php/test/groq \
  -H "Content-Type: application/json" \
  -d '{
    "api_key": "your_groq_key",
    "message": "Hello",
    "model": "mixtral-8x7b-32768"
  }'
```

### Debugging
- Enable debug mode di `config/config.php`
- Periksa log di `storage/logs/`
- Gunakan browser developer tools untuk CORS issues

## Production Deployment

### Security Checklist
- [ ] Ganti JWT_SECRET dengan key yang aman
- [ ] Disable debug mode
- [ ] Setup HTTPS
- [ ] Konfigurasi firewall
- [ ] Regular backup database
- [ ] Monitor log files
- [ ] Update dependencies

### Performance
- Enable opcache PHP
- Setup database indexing
- Configure Apache/Nginx caching
- Monitor resource usage

## Support

Jika mengalami masalah:
1. Periksa log error di `storage/logs/`
2. Periksa konfigurasi database dan API
3. Test endpoint dengan curl
4. Periksa permission file dan direktori

---

**WhatsApp AI Bot Backend PHP v1.0.0**
