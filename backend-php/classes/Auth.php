<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Class Auth
 * Mengelola autentikasi user (login, register, session)
 */
class Auth {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new DatabaseHelper();
    }

    /**
     * Register user baru
     * @param string $email
     * @param string $password
     * @param string $full_name
     * @return array
     */
    public function register($email, $password, $full_name) {
        try {
            // Validasi input
            if (empty($email) || empty($password) || empty($full_name)) {
                throw new Exception('Semua field harus diisi');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Format email tidak valid');
            }

            if (strlen($password) < 6) {
                throw new Exception('Password minimal 6 karakter');
            }

            // Cek apakah email sudah terdaftar
            if ($this->db->exists('users', 'email = :email', ['email' => $email])) {
                throw new Exception('Email sudah terdaftar');
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user baru
            $user_id = $this->db->insert('users', [
                'email' => $email,
                'password' => $hashed_password,
                'full_name' => $full_name,
                'role' => 'user',
                'is_active' => true,
                'email_verified' => false
            ]);

            // Buat konfigurasi AI default
            $this->db->insert('ai_configurations', [
                'user_id' => $user_id,
                'ai_enabled' => false,
                'system_prompt' => 'Anda adalah asisten WhatsApp yang membantu menjawab pesan dengan ramah dan informatif.'
            ]);

            // Buat koneksi WhatsApp default
            $this->db->insert('whatsapp_connections', [
                'user_id' => $user_id,
                'status' => 'disconnected'
            ]);

            log_message('INFO', 'User registered successfully', ['email' => $email, 'user_id' => $user_id]);

            return [
                'success' => true,
                'message' => 'Registrasi berhasil',
                'user_id' => $user_id
            ];

        } catch (Exception $e) {
            log_message('ERROR', 'Registration failed', ['email' => $email, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Login user
     * @param string $email
     * @param string $password
     * @return array
     */
    public function login($email, $password) {
        try {
            // Validasi input
            if (empty($email) || empty($password)) {
                throw new Exception('Email dan password harus diisi');
            }

            // Cari user berdasarkan email
            $user = $this->db->fetchOne(
                'SELECT * FROM users WHERE email = :email AND is_active = 1',
                ['email' => $email]
            );

            if (!$user) {
                throw new Exception('Email atau password salah');
            }

            // Verifikasi password
            if (!password_verify($password, $user['password'])) {
                throw new Exception('Email atau password salah');
            }

            // Generate session
            $session_id = $this->generateSession($user['id']);

            // Update last login
            $this->db->update('users', 
                ['updated_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $user['id']]
            );

            log_message('INFO', 'User logged in successfully', ['email' => $email, 'user_id' => $user['id']]);

            return [
                'success' => true,
                'message' => 'Login berhasil',
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role']
                ],
                'session_id' => $session_id
            ];

        } catch (Exception $e) {
            log_message('ERROR', 'Login failed', ['email' => $email, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Logout user
     * @param string $session_id
     * @return array
     */
    public function logout($session_id) {
        try {
            // Hapus session
            $this->db->delete('user_sessions', 'id = :session_id', ['session_id' => $session_id]);

            log_message('INFO', 'User logged out', ['session_id' => $session_id]);

            return [
                'success' => true,
                'message' => 'Logout berhasil'
            ];

        } catch (Exception $e) {
            log_message('ERROR', 'Logout failed', ['session_id' => $session_id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Verifikasi session
     * @param string $session_id
     * @return array|false
     */
    public function verifySession($session_id) {
        try {
            $session = $this->db->fetchOne(
                'SELECT s.*, u.id, u.email, u.full_name, u.role 
                 FROM user_sessions s 
                 JOIN users u ON s.user_id = u.id 
                 WHERE s.id = :session_id AND s.expires_at > NOW() AND u.is_active = 1',
                ['session_id' => $session_id]
            );

            if ($session) {
                // Update last activity
                $this->db->update('user_sessions',
                    ['last_activity' => date('Y-m-d H:i:s')],
                    'id = :session_id',
                    ['session_id' => $session_id]
                );

                return [
                    'id' => $session['id'],
                    'email' => $session['email'],
                    'full_name' => $session['full_name'],
                    'role' => $session['role']
                ];
            }

            return false;

        } catch (Exception $e) {
            log_message('ERROR', 'Session verification failed', ['session_id' => $session_id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Generate session baru
     * @param int $user_id
     * @return string
     */
    private function generateSession($user_id) {
        $session_id = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        
        // Hapus session lama user ini
        $this->db->delete('user_sessions', 'user_id = :user_id', ['user_id' => $user_id]);

        // Buat session baru
        $this->db->insert('user_sessions', [
            'id' => $session_id,
            'user_id' => $user_id,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'expires_at' => $expires_at
        ]);

        return $session_id;
    }

    /**
     * Middleware untuk cek autentikasi
     * @return array
     */
    public function requireAuth() {
        $headers = getallheaders();
        $session_id = $headers['Authorization'] ?? $_COOKIE['session_id'] ?? null;

        if (!$session_id) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized - No session']);
            exit;
        }

        // Remove "Bearer " prefix jika ada
        $session_id = str_replace('Bearer ', '', $session_id);

        $user = $this->verifySession($session_id);
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized - Invalid session']);
            exit;
        }

        return $user;
    }

    /**
     * Change password
     * @param int $user_id
     * @param string $old_password
     * @param string $new_password
     * @return array
     */
    public function changePassword($user_id, $old_password, $new_password) {
        try {
            // Get current user
            $user = $this->db->fetchOne('SELECT password FROM users WHERE id = :id', ['id' => $user_id]);
            
            if (!$user) {
                throw new Exception('User tidak ditemukan');
            }

            // Verify old password
            if (!password_verify($old_password, $user['password'])) {
                throw new Exception('Password lama salah');
            }

            if (strlen($new_password) < 6) {
                throw new Exception('Password baru minimal 6 karakter');
            }

            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $this->db->update('users',
                ['password' => $hashed_password],
                'id = :id',
                ['id' => $user_id]
            );

            log_message('INFO', 'Password changed successfully', ['user_id' => $user_id]);

            return [
                'success' => true,
                'message' => 'Password berhasil diubah'
            ];

        } catch (Exception $e) {
            log_message('ERROR', 'Password change failed', ['user_id' => $user_id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update profile
     * @param int $user_id
     * @param array $data
     * @return array
     */
    public function updateProfile($user_id, $data) {
        try {
            $allowed_fields = ['full_name', 'email'];
            $update_data = [];

            foreach ($allowed_fields as $field) {
                if (isset($data[$field]) && !empty($data[$field])) {
                    if ($field === 'email' && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                        throw new Exception('Format email tidak valid');
                    }
                    $update_data[$field] = $data[$field];
                }
            }

            if (empty($update_data)) {
                throw new Exception('Tidak ada data yang diupdate');
            }

            // Cek email duplikat jika email diubah
            if (isset($update_data['email'])) {
                if ($this->db->exists('users', 'email = :email AND id != :id', 
                    ['email' => $update_data['email'], 'id' => $user_id])) {
                    throw new Exception('Email sudah digunakan');
                }
            }

            $this->db->update('users', $update_data, 'id = :id', ['id' => $user_id]);

            log_message('INFO', 'Profile updated successfully', ['user_id' => $user_id]);

            return [
                'success' => true,
                'message' => 'Profile berhasil diupdate'
            ];

        } catch (Exception $e) {
            log_message('ERROR', 'Profile update failed', ['user_id' => $user_id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
?>
