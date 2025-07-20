<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Class AIService
 * Mengelola integrasi dengan Groq AI API
 */
class AIService {
    private $db;

    public function __construct() {
        $this->db = new DatabaseHelper();
    }

    /**
     * Get AI configuration untuk user
     * @param int $user_id
     * @return array|false
     */
    public function getAIConfig($user_id) {
        return $this->db->fetchOne(
            'SELECT * FROM ai_configurations WHERE user_id = :user_id',
            ['user_id' => $user_id]
        );
    }

    /**
     * Update AI configuration
     * @param int $user_id
     * @param array $config
     * @return array
     */
    public function updateAIConfig($user_id, $config) {
        try {
            $allowed_fields = [
                'ai_enabled', 'ai_model', 'system_prompt', 
                'keywords_trigger', 'auto_reply_delay', 'groq_api_key',
                'max_tokens', 'temperature'
            ];

            $update_data = [];
            foreach ($allowed_fields as $field) {
                if (isset($config[$field])) {
                    if ($field === 'keywords_trigger' && is_array($config[$field])) {
                        $update_data[$field] = json_encode($config[$field]);
                    } elseif ($field === 'ai_enabled') {
                        $update_data[$field] = (bool) $config[$field];
                    } elseif ($field === 'auto_reply_delay') {
                        $update_data[$field] = max(1000, (int) $config[$field]); // Min 1 detik
                    } elseif ($field === 'max_tokens') {
                        $update_data[$field] = min(2000, max(50, (int) $config[$field])); // 50-2000
                    } elseif ($field === 'temperature') {
                        $update_data[$field] = min(2.0, max(0.0, (float) $config[$field])); // 0.0-2.0
                    } else {
                        $update_data[$field] = $config[$field];
                    }
                }
            }

            if (empty($update_data)) {
                throw new Exception('Tidak ada data yang diupdate');
            }

            // Cek apakah config sudah ada
            $existing = $this->getAIConfig($user_id);
            
            if ($existing) {
                $this->db->update('ai_configurations', $update_data, 'user_id = :user_id', ['user_id' => $user_id]);
            } else {
                $update_data['user_id'] = $user_id;
                $this->db->insert('ai_configurations', $update_data);
            }

            log_message('INFO', 'AI configuration updated', ['user_id' => $user_id]);

            return [
                'success' => true,
                'message' => 'Konfigurasi AI berhasil diupdate'
            ];

        } catch (Exception $e) {
            log_message('ERROR', 'AI config update failed', ['user_id' => $user_id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Generate AI response menggunakan Groq API
     * @param string $message
     * @param array $config
     * @return string
     */
    public function generateResponse($message, $config) {
        try {
            if (empty($config['groq_api_key'])) {
                throw new Exception('Groq API key tidak ditemukan');
            }

            $start_time = microtime(true);

            // Prepare request data
            $request_data = [
                'model' => $config['ai_model'] ?? GROQ_DEFAULT_MODEL,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $config['system_prompt'] ?? 'Anda adalah asisten WhatsApp yang membantu menjawab pesan dengan ramah dan informatif.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ],
                'max_tokens' => $config['max_tokens'] ?? GROQ_MAX_TOKENS,
                'temperature' => $config['temperature'] ?? GROQ_TEMPERATURE,
                'stream' => false
            ];

            // Setup cURL
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => GROQ_API_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($request_data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $config['groq_api_key']
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($curl_error) {
                throw new Exception('cURL Error: ' . $curl_error);
            }

            if ($http_code !== 200) {
                $error_data = json_decode($response, true);
                $error_message = $error_data['error']['message'] ?? 'HTTP Error ' . $http_code;
                throw new Exception('Groq API Error: ' . $error_message);
            }

            $response_data = json_decode($response, true);
            
            if (!isset($response_data['choices'][0]['message']['content'])) {
                throw new Exception('Invalid response format from Groq API');
            }

            $ai_response = trim($response_data['choices'][0]['message']['content']);
            $response_time = round((microtime(true) - $start_time) * 1000); // ms

            log_message('INFO', 'AI response generated successfully', [
                'model' => $request_data['model'],
                'response_time_ms' => $response_time,
                'tokens_used' => $response_data['usage']['total_tokens'] ?? 0
            ]);

            return [
                'response' => $ai_response,
                'response_time_ms' => $response_time,
                'tokens_used' => $response_data['usage']['total_tokens'] ?? 0,
                'model_used' => $request_data['model']
            ];

        } catch (Exception $e) {
            log_message('ERROR', 'AI response generation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Test AI response (untuk testing di dashboard)
     * @param int $user_id
     * @param string $message
     * @return array
     */
    public function testAIResponse($user_id, $message) {
        try {
            $config = $this->getAIConfig($user_id);
            
            if (!$config) {
                throw new Exception('Konfigurasi AI tidak ditemukan');
            }

            if (!$config['ai_enabled']) {
                throw new Exception('AI belum diaktifkan');
            }

            $result = $this->generateResponse($message, $config);

            return [
                'success' => true,
                'response' => $result['response'],
                'response_time_ms' => $result['response_time_ms'],
                'tokens_used' => $result['tokens_used'],
                'model_used' => $result['model_used']
            ];

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Process auto reply untuk pesan WhatsApp
     * @param int $user_id
     * @param string $contact_number
     * @param string $message
     * @return array|false
     */
    public function processAutoReply($user_id, $contact_number, $message) {
        try {
            $config = $this->getAIConfig($user_id);
            
            if (!$config || !$config['ai_enabled']) {
                return false;
            }

            // Cek keyword trigger jika ada
            if (!empty($config['keywords_trigger'])) {
                $keywords = json_decode($config['keywords_trigger'], true);
                if (is_array($keywords)) {
                    $message_lower = strtolower($message);
                    $has_keyword = false;
                    
                    foreach ($keywords as $keyword) {
                        if (strpos($message_lower, strtolower(trim($keyword))) !== false) {
                            $has_keyword = true;
                            break;
                        }
                    }
                    
                    if (!$has_keyword) {
                        return false;
                    }
                }
            }

            // Generate AI response
            $result = $this->generateResponse($message, $config);

            // Log auto reply
            $this->logAutoReply($user_id, $contact_number, $message, $result['response'], true, null, $result);

            return [
                'response' => $result['response'],
                'delay' => $config['auto_reply_delay'] ?? 3000
            ];

        } catch (Exception $e) {
            // Log failed auto reply
            $this->logAutoReply($user_id, $contact_number, $message, '', false, $e->getMessage());
            
            log_message('ERROR', 'Auto reply processing failed', [
                'user_id' => $user_id,
                'contact' => $contact_number,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Log auto reply ke database
     * @param int $user_id
     * @param string $contact_number
     * @param string $original_message
     * @param string $ai_response
     * @param bool $success
     * @param string|null $error_message
     * @param array|null $metadata
     */
    private function logAutoReply($user_id, $contact_number, $original_message, $ai_response, $success, $error_message = null, $metadata = null) {
        try {
            $log_data = [
                'user_id' => $user_id,
                'contact_number' => $contact_number,
                'original_message' => $original_message,
                'ai_response' => $ai_response,
                'success' => $success,
                'error_message' => $error_message
            ];

            if ($metadata) {
                $log_data['response_time_ms'] = $metadata['response_time_ms'] ?? null;
                $log_data['tokens_used'] = $metadata['tokens_used'] ?? null;
                $log_data['model_used'] = $metadata['model_used'] ?? null;
            }

            $this->db->insert('auto_reply_logs', $log_data);

        } catch (Exception $e) {
            log_message('ERROR', 'Failed to log auto reply', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get auto reply statistics
     * @param int $user_id
     * @param int $days
     * @return array
     */
    public function getAutoReplyStats($user_id, $days = 30) {
        try {
            $date_from = date('Y-m-d', strtotime("-{$days} days"));

            // Total auto replies
            $total_replies = $this->db->fetchOne(
                'SELECT COUNT(*) as count FROM auto_reply_logs WHERE user_id = :user_id AND created_at >= :date_from',
                ['user_id' => $user_id, 'date_from' => $date_from]
            )['count'];

            // Successful replies
            $successful_replies = $this->db->fetchOne(
                'SELECT COUNT(*) as count FROM auto_reply_logs WHERE user_id = :user_id AND success = 1 AND created_at >= :date_from',
                ['user_id' => $user_id, 'date_from' => $date_from]
            )['count'];

            // Average response time
            $avg_response_time = $this->db->fetchOne(
                'SELECT AVG(response_time_ms) as avg_time FROM auto_reply_logs WHERE user_id = :user_id AND success = 1 AND created_at >= :date_from',
                ['user_id' => $user_id, 'date_from' => $date_from]
            )['avg_time'];

            // Total tokens used
            $total_tokens = $this->db->fetchOne(
                'SELECT SUM(tokens_used) as total FROM auto_reply_logs WHERE user_id = :user_id AND success = 1 AND created_at >= :date_from',
                ['user_id' => $user_id, 'date_from' => $date_from]
            )['total'];

            return [
                'total_replies' => (int) $total_replies,
                'successful_replies' => (int) $successful_replies,
                'success_rate' => $total_replies > 0 ? round(($successful_replies / $total_replies) * 100, 2) : 0,
                'avg_response_time_ms' => $avg_response_time ? round($avg_response_time) : 0,
                'total_tokens_used' => (int) $total_tokens
            ];

        } catch (Exception $e) {
            log_message('ERROR', 'Failed to get auto reply stats', ['user_id' => $user_id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get available AI models
     * @return array
     */
    public function getAvailableModels() {
        return [
            [
                'value' => 'mixtral-8x7b-32768',
                'label' => 'Mixtral 8x7B',
                'description' => 'Model cepat dan efisien untuk percakapan umum'
            ],
            [
                'value' => 'llama2-70b-4096',
                'label' => 'Llama2 70B',
                'description' => 'Model besar dengan kemampuan pemahaman yang lebih baik'
            ],
            [
                'value' => 'gemma-7b-it',
                'label' => 'Gemma 7B',
                'description' => 'Model Google yang ringan dan responsif'
            ],
            [
                'value' => 'llama-3.1-70b-versatile',
                'label' => 'Llama 3.1 70B',
                'description' => 'Model terbaru dengan kemampuan serbaguna'
            ]
        ];
    }
}
?>
