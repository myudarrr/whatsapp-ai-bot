# WhatsApp AI Bot - Real Connection

WhatsApp bot yang nyata dengan integrasi AI menggunakan Groq API. Bot ini dapat terhubung ke WhatsApp Web secara langsung tanpa menggunakan API resmi WhatsApp.

## Fitur

- ✅ **Koneksi WhatsApp Nyata** - Menggunakan whatsapp-web.js untuk koneksi langsung ke WhatsApp Web
- ✅ **AI Auto-Reply** - Balasan otomatis menggunakan Groq AI API
- ✅ **QR Code Scanner** - Scan QR code untuk menghubungkan WhatsApp
- ✅ **Dashboard Web** - Interface web untuk mengatur bot
- ✅ **Real-time Status** - Status koneksi real-time menggunakan Socket.IO
- ✅ **Keyword Filtering** - Filter pesan berdasarkan kata kunci
- ✅ **Custom System Prompt** - Atur perilaku AI sesuai kebutuhan
- ✅ **Message Statistics** - Statistik pesan dan balasan
- ✅ **Multi-user Support** - Mendukung multiple user dengan session terpisah

## Teknologi

### Backend
- **Node.js** - Runtime JavaScript
- **Express.js** - Web framework
- **Socket.IO** - Real-time communication
- **whatsapp-web.js** - WhatsApp Web API
- **Groq API** - AI Language Model
- **Supabase** - Database dan Authentication

### Frontend
- **React + TypeScript** - UI Framework
- **Vite** - Build tool
- **Tailwind CSS** - Styling
- **Shadcn/ui** - UI Components
- **Socket.IO Client** - Real-time communication

## Instalasi

### 1. Clone Repository
```bash
git clone <repository-url>
cd whatsapp-ai-bot
```

### 2. Install Dependencies

#### Frontend
```bash
npm install
```

#### Backend
```bash
cd server
npm install
```

### 3. Setup Environment

#### Backend (.env)
```bash
cd server
cp .env.example .env
```

Edit `server/.env`:
```env
PORT=3001
GROQ_API_KEY=your_groq_api_key_here
SUPABASE_URL=your_supabase_url
SUPABASE_ANON_KEY=your_supabase_anon_key
```

### 4. Setup Database (Supabase)

Buat tabel-tabel berikut di Supabase:

#### Tabel `profiles`
```sql
CREATE TABLE profiles (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
  email TEXT,
  full_name TEXT,
  role TEXT DEFAULT 'user',
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

#### Tabel `whatsapp_connections`
```sql
CREATE TABLE whatsapp_connections (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
  status TEXT DEFAULT 'disconnected',
  phone_number TEXT,
  qr_code TEXT,
  last_connected_at TIMESTAMP WITH TIME ZONE,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

#### Tabel `ai_configurations`
```sql
CREATE TABLE ai_configurations (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
  ai_enabled BOOLEAN DEFAULT false,
  ai_model TEXT DEFAULT 'mixtral-8x7b-32768',
  system_prompt TEXT DEFAULT 'Anda adalah asisten WhatsApp yang membantu menjawab pesan dengan ramah dan informatif.',
  keywords_trigger TEXT[],
  auto_reply_delay INTEGER DEFAULT 3000,
  groq_api_key TEXT,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

#### Tabel `chat_messages`
```sql
CREATE TABLE chat_messages (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
  message_id TEXT,
  contact_number TEXT NOT NULL,
  contact_name TEXT,
  message_text TEXT NOT NULL,
  is_from_me BOOLEAN DEFAULT false,
  ai_replied BOOLEAN DEFAULT false,
  ai_reply_status TEXT,
  ai_reply_text TEXT,
  timestamp TIMESTAMP WITH TIME ZONE NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

#### Tabel `auto_reply_logs`
```sql
CREATE TABLE auto_reply_logs (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
  contact_number TEXT NOT NULL,
  original_message TEXT NOT NULL,
  ai_response TEXT NOT NULL,
  success BOOLEAN DEFAULT true,
  error_message TEXT,
  response_time_ms INTEGER,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

### 5. Dapatkan Groq API Key

1. Kunjungi [Groq Console](https://console.groq.com/keys)
2. Buat akun atau login
3. Generate API key baru
4. Copy API key untuk digunakan di aplikasi

## Menjalankan Aplikasi

### 1. Jalankan Backend Server
```bash
cd server
npm start
# atau untuk development
npm run dev
```

Server akan berjalan di `http://localhost:3001`

### 2. Jalankan Frontend
```bash
# Di root directory
npm run dev
```

Frontend akan berjalan di `http://localhost:5173`

## Cara Penggunaan

### 1. Register/Login
- Buka `http://localhost:5173`
- Daftar akun baru atau login dengan akun existing

### 2. Konfigurasi AI
- Masuk ke dashboard
- Isi **Groq API Key** di bagian AI Settings
- Atur **System Prompt** sesuai kebutuhan
- Pilih **AI Model** (default: Mixtral 8x7B)
- Atur **Keywords Trigger** (opsional)
- Atur **Auto Reply Delay**
- **Enable AI Auto-Reply**
- Klik **Save Configuration**

### 3. Hubungkan WhatsApp
- Klik **Connect WhatsApp** di dashboard
- Scan QR Code yang muncul dengan WhatsApp mobile
- Tunggu hingga status berubah menjadi "Connected"

### 4. Test AI Response
- Gunakan fitur **Test AI Reply** untuk menguji respons AI
- Masukkan pesan test dan lihat respons yang dihasilkan

### 5. Bot Aktif
- Bot akan otomatis membalas pesan WhatsApp sesuai konfigurasi
- Monitor statistik pesan di dashboard
- Lihat log auto-reply untuk tracking

## Model AI yang Tersedia

- **Mixtral 8x7B** - Model default, cepat dan efisien
- **Llama2 70B** - Model yang lebih besar dengan kemampuan lebih baik
- **Gemma 7B** - Model Google yang ringan

## Troubleshooting

### WhatsApp tidak terhubung
- Pastikan QR code di-scan dengan benar
- Coba refresh dan generate QR code baru
- Pastikan WhatsApp mobile dalam kondisi online

### AI tidak merespons
- Periksa Groq API key sudah benar
- Pastikan AI Auto-Reply sudah diaktifkan
- Cek keyword trigger jika digunakan
- Lihat console log untuk error details

### Server error
- Periksa semua environment variables sudah diset
- Pastikan port 3001 tidak digunakan aplikasi lain
- Restart server jika diperlukan

## Keamanan

- API key disimpan terenkripsi di database
- Session WhatsApp tersimpan lokal per user
- Autentikasi menggunakan Supabase Auth
- CORS dikonfigurasi untuk keamanan

## Kontribusi

1. Fork repository
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## Lisensi

Distributed under the MIT License. See `LICENSE` for more information.

## Support

Jika mengalami masalah atau butuh bantuan:
1. Cek dokumentasi di atas
2. Lihat Issues di GitHub
3. Buat Issue baru dengan detail lengkap

---

**⚠️ Disclaimer**: Bot ini menggunakan WhatsApp Web yang bukan API resmi. Gunakan dengan bijak dan patuhi Terms of Service WhatsApp.
