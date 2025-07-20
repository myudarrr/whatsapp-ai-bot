# WhatsApp AI Bot - Full Stack (PHP Backend + React Frontend)

WhatsApp AI Bot dengan backend PHP dan frontend React yang dapat terhubung ke WhatsApp secara nyata menggunakan Groq AI untuk auto-reply.

## 🚀 Fitur Lengkap

### Backend (PHP)
- ✅ **REST API** - Endpoint lengkap untuk semua fitur
- ✅ **MySQL Database** - Penyimpanan data terstruktur
- ✅ **Autentikasi** - Session-based authentication
- ✅ **WhatsApp Integration** - Koneksi dan manajemen pesan
- ✅ **Groq AI Integration** - Auto-reply dengan AI
- ✅ **Security** - CORS, input validation, SQL injection protection
- ✅ **Logging** - System logging untuk debugging

### Frontend (React)
- ✅ **Modern UI** - React + TypeScript + Tailwind CSS
- ✅ **Real-time Dashboard** - Status koneksi dan statistik
- ✅ **AI Configuration** - Setup Groq API dan system prompt
- ✅ **Message Management** - Lihat dan kelola pesan
- ✅ **User Management** - Profile dan password management

## 📁 Struktur Project

```
whatsapp-ai-bot/
├── backend-php/              # Backend PHP
│   ├── classes/              # PHP Classes
│   │   ├── Auth.php         # Autentikasi
│   │   ├── AIService.php    # Groq AI Integration
│   │   └── WhatsAppBot.php  # WhatsApp Operations
│   ├── config/              # Konfigurasi
│   │   ├── config.php       # App config
│   │   └── database.php     # Database config
│   ├── database/            # Database schema
│   │   └── whatsapp_bot.sql # SQL schema
│   ├── storage/             # File storage
│   │   ├── logs/           # Log files
│   │   ├── sessions/       # WhatsApp sessions
│   │   ├── qr/            # QR codes
│   │   └── uploads/       # File uploads
│   ├── .htaccess           # Apache config
│   ├── .env               # Environment variables
│   ├── index.php          # Main API endpoint
│   ├── test.php           # Test script
│   └── README.md          # Backend documentation
├── src/                    # Frontend React
│   ├── components/        # React components
│   ├── hooks/            # Custom hooks
│   ├── services/         # API services
│   ├── pages/           # Page components
│   └── ...
├── public/              # Static files
└── README-PHP.md       # This documentation
```

## 🛠 Instalasi Lengkap

### 1. Requirements

**Backend:**
- PHP 7.4+ dengan extensions: PDO, cURL, JSON, mbstring
- MySQL 5.7+
- Apache dengan mod_rewrite

**Frontend:**
- Node.js 16+
- npm atau yarn

### 2. Setup Database

1. **Buat database MySQL:**
```sql
CREATE DATABASE whatsapp_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Import schema:**
```bash
mysql -u root -p whatsapp_bot < backend-php/database/whatsapp_bot.sql
```

3. **Update konfigurasi database:**
Edit `backend-php/config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'whatsapp_bot';
private $username = 'root';
private $password = 'your_password';
```

### 3. Setup Backend PHP

1. **Copy ke web server directory:**
```bash
# Untuk XAMPP
cp -r backend-php /xampp/htdocs/

# Untuk WAMP
cp -r backend-php /wamp64/www/

# Atau setup virtual host
```

2. **Set permissions:**
```bash
chmod -R 755 backend-php/storage/
```

3. **Update konfigurasi:**
Edit `backend-php/config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'whatsapp_bot');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('CORS_ORIGIN', 'http://localhost:5173');
```

4. **Test backend:**
```bash
# Via command line
php backend-php/test.php

# Via browser
http://localhost/backend-php/test
```

### 4. Setup Frontend React

1. **Install dependencies:**
```bash
npm install
```

2. **Update API URL:**
Edit `src/services/api.ts`:
```typescript
const API_BASE_URL = 'http://localhost/backend-php';
```

3. **Start development server:**
```bash
npm run dev
```

## 🔧 Konfigurasi

### Backend Configuration

**File: `backend-php/config/config.php`**
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

// Groq API
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
```

### Frontend Configuration

**File: `src/services/api.ts`**
```typescript
const API_BASE_URL = 'http://localhost/backend-php';
```

## 📚 API Documentation

### Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Register user baru |
| POST | `/auth/login` | Login user |
| POST | `/auth/logout` | Logout user |
| GET | `/auth/me` | Get user info |
| PUT | `/auth/profile` | Update profile |
| PUT | `/auth/password` | Change password |

### WhatsApp Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/whatsapp/status` | Get connection status |
| POST | `/whatsapp/connect` | Generate QR code |
| POST | `/whatsapp/disconnect` | Disconnect WhatsApp |
| POST | `/whatsapp/send` | Send message |
| GET | `/whatsapp/contacts` | Get contacts |

### AI Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/ai/config` | Get AI configuration |
| PUT | `/ai/config` | Update AI configuration |
| GET | `/ai/models` | Get available models |
| POST | `/ai/test` | Test AI response |
| GET | `/ai/stats` | Get AI statistics |

## 🎯 Cara Penggunaan

### 1. Akses Aplikasi
Buka browser dan akses: `http://localhost:5173`

### 2. Register/Login
- Buat akun baru atau login dengan akun existing
- Default users (password: `password`):
  - `admin@whatsappbot.com` (Admin)
  - `test@example.com` (User)
  - `demo@whatsappbot.com` (User)

### 3. Konfigurasi AI
1. Masuk ke dashboard
2. Buka bagian **AI Settings**
3. Masukkan **Groq API Key** (dapatkan dari [Groq Console](https://console.groq.com/keys))
4. Atur **System Prompt** sesuai kebutuhan
5. Pilih **AI Model** (default: Mixtral 8x7B)
6. Set **Keywords Trigger** (opsional)
7. Atur **Auto Reply Delay**
8. **Enable AI Auto-Reply**
9. Klik **Save Configuration**

### 4. Koneksi WhatsApp
1. Klik **Connect WhatsApp**
2. Scan QR Code dengan WhatsApp mobile
3. Tunggu status berubah ke "Connected"

### 5. Test AI
- Gunakan **Test AI Reply** untuk menguji respons
- Bot akan otomatis membalas pesan sesuai konfigurasi

## 🔍 Testing

### Backend Testing

```bash
# Test semua komponen
php backend-php/test.php

# Test database connection
curl http://localhost/backend-php/test/db

# Test Groq API
curl -X POST http://localhost/backend-php/test/groq \
  -H "Content-Type: application/json" \
  -d '{
    "api_key": "your_groq_key",
    "message": "Hello",
    "model": "mixtral-8x7b-32768"
  }'
```

### Frontend Testing

```bash
# Start development server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview
```

## 🔒 Security

### Backend Security
- Input validation dan sanitization
- Prepared statements (SQL injection protection)
- Session-based authentication
- CORS configuration
- File upload validation
- Directory browsing disabled

### Frontend Security
- XSS protection
- CSRF protection via session validation
- Secure API communication
- Input validation

## 📊 Database Schema

### Tabel Utama

**users** - Data user
- `id`, `email`, `password`, `full_name`, `role`, `is_active`

**whatsapp_connections** - Status koneksi WhatsApp
- `user_id`, `status`, `phone_number`, `qr_code`, `session_data`

**ai_configurations** - Konfigurasi AI
- `user_id`, `ai_enabled`, `ai_model`, `system_prompt`, `groq_api_key`

**chat_messages** - Pesan WhatsApp
- `user_id`, `contact_number`, `message_text`, `ai_replied`, `ai_reply_text`

**auto_reply_logs** - Log balasan otomatis
- `user_id`, `contact_number`, `original_message`, `ai_response`, `success`

## 🚨 Troubleshooting

### Backend Issues

**Database Connection Error:**
- Periksa konfigurasi di `config/database.php`
- Pastikan MySQL service berjalan
- Periksa username/password database

**CORS Error:**
- Update `CORS_ORIGIN` di `config/config.php`
- Pastikan frontend URL sesuai

**Permission Error:**
- Set permission: `chmod -R 755 backend-php/storage/`
- Pastikan web server dapat menulis ke direktori storage

**API Not Working:**
- Periksa Apache mod_rewrite enabled
- Periksa file `.htaccess` ada dan readable
- Periksa error log Apache

### Frontend Issues

**API Connection Failed:**
- Periksa API_BASE_URL di `src/services/api.ts`
- Pastikan backend berjalan
- Periksa CORS configuration

**Build Error:**
- Jalankan `npm install` untuk update dependencies
- Periksa TypeScript errors
- Clear cache: `rm -rf node_modules package-lock.json && npm install`

## 🚀 Production Deployment

### Backend Production

1. **Security Checklist:**
   - [ ] Ganti JWT_SECRET dengan key yang aman
   - [ ] Disable debug mode
   - [ ] Setup HTTPS
   - [ ] Konfigurasi firewall
   - [ ] Regular backup database

2. **Performance:**
   - Enable PHP opcache
   - Setup database indexing
   - Configure Apache/Nginx caching
   - Monitor resource usage

### Frontend Production

```bash
# Build for production
npm run build

# Deploy ke web server
cp -r dist/* /var/www/html/
```

## 📝 Development

### Adding New Features

1. **Backend (PHP):**
   - Tambah method di class yang sesuai
   - Update API endpoint di `index.php`
   - Test dengan `test.php`

2. **Frontend (React):**
   - Tambah method di `src/services/api.ts`
   - Update components yang diperlukan
   - Test di development server

### Database Changes

1. Update schema di `database/whatsapp_bot.sql`
2. Create migration script jika diperlukan
3. Update model classes di backend
4. Test dengan sample data

## 🤝 Contributing

1. Fork repository
2. Create feature branch
3. Make changes
4. Test thoroughly
5. Submit pull request

## 📄 License

MIT License - see LICENSE file for details

## 🆘 Support

Jika mengalami masalah:

1. **Check Logs:**
   - Backend: `backend-php/storage/logs/`
   - Frontend: Browser developer console

2. **Test Components:**
   - Database: `php backend-php/test.php`
   - API: `http://localhost/backend-php/test`
   - Frontend: `npm run dev`

3. **Common Solutions:**
   - Restart web server (Apache/Nginx)
   - Clear browser cache
   - Check file permissions
   - Verify database connection

---

**WhatsApp AI Bot - Full Stack PHP + React v1.0.0**

*Dibuat dengan ❤️ untuk automasi WhatsApp yang powerful dan mudah digunakan*
