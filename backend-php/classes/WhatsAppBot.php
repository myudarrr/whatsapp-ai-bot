<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Class WhatsAppBot
 * Mengelola koneksi dan operasi WhatsApp
 */
class WhatsAppBot {
    private $db;

    public function __construct() {
        $this->db = new DatabaseHelper();
    }

    /**
     * Get WhatsApp connection status
     * @param int $user_id
     * @return array|false
     */
    public function getConnectionStatus($user_id) {
        return $this->db->fetchOne(
            'SELECT * FROM whatsapp_connections WHERE user_id = :user_id',
            ['user_id' => $user_id]
        );
    }

    /**
     * Update connection status
     * @param int $user_id
     * @param string $status
     * @param array $data
     * @return array
     */
    public function updateConnectionStatus($user_id, $status, $data = []) {
        try {
            $update_data = ['status' => $status];
            
            if (isset($data['phone_number'])) {
                $update_data['phone_number'] = $data['phone_number'];
            }
            
            if (isset($data['qr_code'])) {
                $update_data['qr_code'] = $data['qr_code'];
            }
            
            if (isset($data['session_data'])) {
                $update_data['session_data'] = $data['session_data'];
            }
            
            if ($status === 'connected') {
                $update_data['last_connected_at'] = date('Y-m-d H:i:s');
                $update_data['qr_code'] = null; // Clear QR code when connected
            }
            
            if ($status === 'disconnected') {
                $update_data['phone_number'] = null;
                $update_data['qr_code'] = null;
                $update_data['session_data'] = null;
            }

            // Check if connection exists
            $existing = $this->getConnectionStatus($user_id);
            
            if ($existing) {
                $this->db->update('whatsapp_connections', $update_data, 'user_id = :user_id', ['user_id' => $user_id]);
            } else {
                $update_data['user_id'] = $user_id;
                $this->db->insert('whatsapp_connections', $update_data);
            }

            log_message('INFO', 'WhatsApp connection status updated', [
                'user_id' => $user_id,
                'status' => $status
            ]);

            return [
                'success' => true,
                'message' => 'Status koneksi berhasil diupdate',
                'status' => $status
            ];

        } catch (Exception $e) {
            log_message('ERROR', 'Failed to update connection status', [
                'user_id' => $user_id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate QR Code untuk koneksi WhatsApp
     * @param int $user_id
     * @return array
     */
    public function generateQRCode($user_id) {
        try {
            // Simulate QR code generation
            // Dalam implementasi nyata, ini akan menggunakan whatsapp-web.js atau library serupa
            $qr_data = 'whatsapp-qr-' . $user_id . '-' . time();
            $qr_code = base64_encode($qr_data);

            // Update status ke connecting
            $this->updateConnectionStatus($user_id, 'connecting', [
                'qr_code' => $qr_code
            ]);

            log_message('INFO', 'QR Code generated', ['user_id' => $user_id]);

            return [
                'success' => true,
                'qr_code' => $qr_code,
                'message' => 'QR Code berhasil dibuat'
            ];

        } catch (Exception $e) {
            log_message('ERROR', 'QR Code generation failed', [
                'user_id' => $user_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Disconnect WhatsApp
     * @param int $user_id
     * @return array
     */
    public function disconnect($user_id) {
        try {
            // Update status ke disconnected
            $this->updateConnectionStatus($user_id, 'disconnected');

            log_message('INFO', 'WhatsApp disconnected', ['user_id' => $user_id]);

            return [
                'success' => true,
                'message' => 'WhatsApp berhasil diputus'
            ];

        } catch (Exception $e) {
            log_message('ERROR', 'WhatsApp disconnect failed', [
                'user_id' => $user_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Save incoming message
     * @param int $user_id
     * @param array $message_data
     * @return int
     */
    public function saveMessage($user_id, $message_data) {
        try {
            $data = [
                'user_id' => $user_id,
                'message_id' => $message_data['id'] ?? null,
                'contact_number' => $message_data['from'],
                'contact_name' => $message_data['contact_name'] ?? null,
                'message_text' => $message_data['body'],
                'message_type' => $message_data['type'] ?? 'text',
                'is_from_me' => $message_data['fromMe'] ?? false,
                'message_timestamp' => date('Y-m-d H:i:s', $message_data['timestamp'] ?? time())
            ];

            $message_id = $this->db->insert('chat_messages', $data);

            log_message('INFO', 'Message saved', [
                'user_id' => $user_id,
                'message_id' => $message_id,
                'from' => $message_data['from']
            ]);

            return $message_id;

        } catch (Exception $e) {
            log_message('ERROR', 'Failed to save message', [
                'user_id' => $user_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update message with AI reply
     * @param int $message_id
     * @param string $ai_reply
     * @param string $status
     * @return bool
     */
    public function updateMessageWithReply($message_id, $ai_reply, $status = 'sent') {
        try {
            $this->db->update('chat_messages', [
                'ai_replied' => true,
                'ai_reply_text' => $ai_reply,
                'ai_reply_status' => $status
            ], 'id = :id', ['id' => $message_id]);

            return true;

        } catch (Exception $e) {
            log_message('ERROR', 'Failed to update message with reply', [
                'message_id' => $message_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get messages for user
     * @param int $user_id
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getMessages($user_id, $limit = 50, $offset = 0) {
        try {
            return $this->db->fetchAll(
                'SELECT * FROM chat_messages 
                 WHERE user_id = :user_id 
                 ORDER BY message_timestamp DESC 
                 LIMIT :limit OFFSET :offset',
                [
                    'user_id' => $user_id,
                    'limit' => $limit,
                    'offset' => $offset
                ]
            );

        } catch (Exception $e) {
            log_message('ERROR', 'Failed to get messages', [
                'user_id' => $user_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get contacts for user
     * @param int $user_id
     * @return array
     */
    public function getContacts($user_id) {
        try {
            return $this->db->fetchAll(
                'SELECT * FROM contacts 
                 WHERE user_id = :user_id 
                 ORDER BY last_message_at DESC',
                ['user_id' => $user_id]
            );

        } catch (Exception $e) {
            log_message('ERROR', 'Failed to get contacts', [
                'user_id' => $user_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get message statistics
     * @param int $user_id
     * @param int $days
     * @return array
     */
    public function getMessageStats($user_id, $days = 30) {
        try {
            $date_from = date('Y-m-d', strtotime("-{$days} days"));

            // Total messages
            $total_messages = $this->db->fetchOne(
                'SELECT COUNT(*) as count FROM chat_messages WHERE user_id = :user_id AND created_at >= :date_from',
                ['user_id' => $user_id, 'date_from' => $date_from]
            )['count'];

            // AI replies
            $ai_replies = $this->db->fetchOne(
                'SELECT COUNT(*) as count FROM chat_messages WHERE user_id = :user_id AND ai_replied = 1 AND created_at >= :date_from',
                ['user_id' => $user_id, 'date_from' => $date_from]
            )['count'];

            // Today's messages
            $today_messages = $this->db->fetchOne(
                'SELECT COUNT(*) as count FROM chat_messages WHERE user_id = :user_id AND DATE(created_at) = CURDATE()',
                ['user_id' => $user_id]
            )['count'];

            // Today's AI replies
            $today_ai_replies = $this->db->fetchOne(
                'SELECT COUNT(*) as count FROM chat_messages WHERE user_id = :user_id AND ai_replied = 1 AND DATE(created_at) = CURDATE()',
                ['user_id' => $user_id]
            )['count'];

            // Total contacts
            $total_contacts = $this->db->fetchOne(
                'SELECT COUNT(*) as count FROM contacts WHERE user_id = :user_id',
                ['user_id' => $user_id]
            )['count'];

            return [
                'total_messages' => (int) $total_messages,
                'ai_replies' => (int) $ai_replies,
                'success_rate' => $total_messages > 0 ? round(($ai_replies / $total_messages) * 100, 2) : 0,
                'today_messages' => (int) $today_messages,
                'today_ai_replies' => (int) $today_ai_replies,
                'total_contacts' => (int) $total_contacts
            ];

        } catch (Exception $e) {
            log_message('ERROR', 'Failed to get message stats', [
                'user_id' => $user_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send message (placeholder untuk implementasi nyata)
     * @param int $user_id
     * @param string $to
     * @param string $message
     * @return array
     */
    public function sendMessage($user_id, $to, $message) {
        try {
            // Dalam implementasi nyata, ini akan mengirim pesan melalui WhatsApp Web API
            // Untuk sekarang, kita hanya simulate
            
            // Save outgoing message
            $message_data = [
                'id' => 'msg_' . time(),
                'from' => $to,
                'body' => $message,
                'type' => 'text',
                'fromMe' => true,
                'timestamp' => time()
            ];

            $message_id = $this->saveMessage($user_id, $message_data);

            log_message('INFO', 'Message sent', [
                'user_id' => $user_id,
                'to' => $to,
                'message_id' => $message_id
            ]);

            return [
                'success' => true,
                'message_id' => $message_id,
                'message' => 'Pesan berhasil dikirim'
            ];

        } catch (Exception $e) {
            log_message('ERROR', 'Failed to send message', [
                'user_id' => $user_id,
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Block/unblock contact
     * @param int $user_id
     * @param string $phone_number
     * @param bool $is_blocked
     * @return array
     */
    public function blockContact($user_id, $phone_number, $is_blocked = true) {
        try {
            $this->db->execute(
                'INSERT INTO contacts (user_id, phone_number, is_blocked) VALUES (:user_id, :phone_number, :is_blocked)
                 ON DUPLICATE KEY UPDATE is_blocked = :is_blocked',
                [
                    'user_id' => $user_id,
                    'phone_number' => $phone_number,
                    'is_blocked' => $is_blocked
                ]
            );

            $action = $is_blocked ? 'blocked' : 'unblocked';
            log_message('INFO', "Contact {$action}", [
                'user_id' => $user_id,
                'phone_number' => $phone_number
            ]);

            return [
                'success' => true,
                'message' => "Kontak berhasil " . ($is_blocked ? 'diblokir' : 'dibuka blokir')
            ];

        } catch (Exception $e) {
            log_message('ERROR', 'Failed to block/unblock contact', [
                'user_id' => $user_id,
                'phone_number' => $phone_number,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
?>
