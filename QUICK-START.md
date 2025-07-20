# 🚀 Quick Start - WhatsApp AI Bot (PHP + React)

Panduan cepat untuk menjalankan WhatsApp AI Bot di local development.

## 📋 Prerequisites

- **PHP 7.4+** dengan extensions: PDO, PDO_MySQL, cURL, JSON, mbstring
- **MySQL 5.7+** atau **MariaDB**
- **Node.js 16+** dan **npm**
- **Groq API Key** (gratis dari [console.groq.com](https://console.groq.com/keys))

## ⚡ Quick Setup (5 Menit)

### 1. Setup Database
```bash
# Login ke MySQL
mysql -u root -p

# Buat database
CREATE DATABASE whatsapp_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# Import schema
mysql -u root -p whatsapp_bot < backend-php/database/whatsapp_bot.sql
```

### 2. Setup Backend PHP
```bash
# Masuk ke direktori backend
cd backend-php

# Jalankan setup otomatis
php setup.php

# Edit konfigurasi database (jika perlu)
nano .env
# Update DB_PASS dengan password MySQL Anda
```

### 3. Test Backend
```bash
# Test semua komponen
php test.php

# Start development server
php server.php
# Server akan berjalan di: http://localhost:8080
```

### 4. Setup Frontend
```bash
# Kembali ke root directory
cd ..

# Install dependencies
npm install

# Start development server
npm run dev
# Frontend akan berjalan di: http://localhost:5173
```

## 🎯 Akses Aplikasi

1. **Buka browser**: http://localhost:5173
2. **Login dengan akun default**:
   - Email: `test@example.com`
   - Password: `password`

## ⚙️ Konfigurasi AI

1. **Dapatkan Groq API Key**:
   - Kunjungi: https://console.groq.com/keys
   - Daftar/login dan buat API key baru

2. **Setup AI di Dashboard**:
   - Login ke aplikasi
   - Buka **AI Settings**
   - Masukkan **Groq API Key**
   - Atur **System Prompt**: "Anda adalah asisten WhatsApp yang ramah"
   - Pilih **Model**: Mixtral 8x7B (default)
   - **Enable AI Auto-Reply**
   - Klik **Save Configuration**

3. **Test AI**:
   - Gunakan fitur **Test AI Reply**
   - Masukkan pesan test: "Halo, apa kabar?"
   - Lihat respons AI

## 📱 Koneksi WhatsApp

1. **Generate QR Code**:
   - Klik **Connect WhatsApp**
   - QR Code akan muncul

2. **Scan dengan WhatsApp Mobile**:
   - Buka WhatsApp di HP
   - Menu > Linked Devices > Link a Device
   - Scan QR Code

3. **Status Connected**:
   - Status akan berubah ke "Connected"
   - Bot siap menerima dan membalas pesan

## 🧪 Testing

### Test Backend API
```bash
# Test database connection
curl http://localhost:8080/test/db

# Test API endpoints
curl http://localhost:8080/test

# Test Groq API
curl -X POST http://localhost:8080/test/groq \
  -H "Content-Type: application/json" \
  -d '{
    "api_key": "YOUR_GROQ_API_KEY",
    "message": "Hello",
    "model": "mixtral-8x7b-32768"
  }'
```

### Test Frontend
- Buka http://localhost:5173
- Test login/register
- Test AI configuration
- Test WhatsApp connection

## 📁 Struktur Project

```
whatsapp-ai-bot/
├── backend-php/           # Backend PHP
│   ├── setup.php         # Setup script
│   ├── server.php        # Development server
│   ├── test.php          # Test script
│   ├── index.php         # Main API
│   ├── .env              # Environment config
│   └── ...
├── src/                  # Frontend React
│   ├── services/api.ts   # API service
│   ├── hooks/useAuthPHP.tsx # Auth hook
│   └── ...
└── QUICK-START.md        # This guide
```

## 🔧 Troubleshooting

### Backend Issues

**Database Connection Error:**
```bash
# Check MySQL service
sudo systemctl status mysql
# atau
brew services list | grep mysql

# Test connection
mysql -u root -p -e "SELECT 1"
```

**Permission Error:**
```bash
# Fix storage permissions
chmod -R 755 backend-php/storage/
```

**PHP Extensions Missing:**
```bash
# Ubuntu/Debian
sudo apt install php-pdo php-mysql php-curl php-json php-mbstring

# macOS (Homebrew)
brew install php
```

### Frontend Issues

**API Connection Failed:**
- Pastikan backend berjalan di http://localhost:8080
- Check CORS settings di backend-php/.env
- Periksa browser console untuk error details

**Build Error:**
```bash
# Clear cache dan reinstall
rm -rf node_modules package-lock.json
npm install
```

## 🚀 Production Deployment

### Backend
1. Upload ke web server (Apache/Nginx)
2. Update .env dengan production settings
3. Disable debug mode
4. Setup HTTPS
5. Configure database

### Frontend
```bash
# Build for production
npm run build

# Deploy dist/ folder ke web server
```

## 📞 Support

**Jika mengalami masalah:**

1. **Check Logs:**
   - Backend: `backend-php/storage/logs/`
   - Frontend: Browser developer console

2. **Run Diagnostics:**
   ```bash
   # Backend
   cd backend-php
   php test.php
   
   # Frontend
   npm run dev
   ```

3. **Common Solutions:**
   - Restart MySQL service
   - Clear browser cache
   - Check file permissions
   - Verify API endpoints

## 🎉 Selesai!

Aplikasi WhatsApp AI Bot sudah siap digunakan!

**Default Features:**
- ✅ User authentication
- ✅ WhatsApp QR connection
- ✅ AI auto-reply dengan Groq
- ✅ Message management
- ✅ Real-time dashboard
- ✅ Statistics & logging

**Next Steps:**
- Customize AI prompts
- Add more AI models
- Implement message templates
- Add webhook integrations
- Scale for production

---

**Happy Coding! 🚀**
