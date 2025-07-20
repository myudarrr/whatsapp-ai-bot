/**
 * API Service untuk backend PHP
 */

const API_BASE_URL = 'http://localhost/backend-php';

interface ApiResponse<T = any> {
  success: boolean;
  message: string;
  data?: T;
  error?: string;
  status?: number;
}

class ApiService {
  private baseUrl: string;
  private sessionId: string | null = null;

  constructor(baseUrl: string = API_BASE_URL) {
    this.baseUrl = baseUrl;
    this.sessionId = localStorage.getItem('session_id');
  }

  private async request<T = any>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<ApiResponse<T>> {
    const url = `${this.baseUrl}/${endpoint}`;
    
    const defaultHeaders: HeadersInit = {
      'Content-Type': 'application/json',
    };

    if (this.sessionId) {
      defaultHeaders['Authorization'] = `Bearer ${this.sessionId}`;
    }

    const config: RequestInit = {
      ...options,
      headers: {
        ...defaultHeaders,
        ...options.headers,
      },
    };

    try {
      const response = await fetch(url, config);
      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || `HTTP ${response.status}`);
      }

      return data;
    } catch (error) {
      console.error('API Request failed:', error);
      throw error;
    }
  }

  // Authentication
  async register(email: string, password: string, fullName: string) {
    return this.request('auth/register', {
      method: 'POST',
      body: JSON.stringify({
        email,
        password,
        full_name: fullName,
      }),
    });
  }

  async login(email: string, password: string) {
    const response = await this.request('auth/login', {
      method: 'POST',
      body: JSON.stringify({
        email,
        password,
      }),
    });

    if (response.success && response.data?.session_id) {
      this.sessionId = response.data.session_id;
      localStorage.setItem('session_id', this.sessionId);
    }

    return response;
  }

  async logout() {
    const response = await this.request('auth/logout', {
      method: 'POST',
    });

    if (response.success) {
      this.sessionId = null;
      localStorage.removeItem('session_id');
    }

    return response;
  }

  async getMe() {
    return this.request('auth/me');
  }

  async updateProfile(data: { full_name?: string; email?: string }) {
    return this.request('auth/profile', {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  async changePassword(oldPassword: string, newPassword: string) {
    return this.request('auth/password', {
      method: 'PUT',
      body: JSON.stringify({
        old_password: oldPassword,
        new_password: newPassword,
      }),
    });
  }

  // WhatsApp
  async getWhatsAppStatus() {
    return this.request('whatsapp/status');
  }

  async connectWhatsApp() {
    return this.request('whatsapp/connect', {
      method: 'POST',
    });
  }

  async disconnectWhatsApp() {
    return this.request('whatsapp/disconnect', {
      method: 'POST',
    });
  }

  async sendMessage(to: string, message: string) {
    return this.request('whatsapp/send', {
      method: 'POST',
      body: JSON.stringify({
        to,
        message,
      }),
    });
  }

  async getContacts() {
    return this.request('whatsapp/contacts');
  }

  async blockContact(phoneNumber: string, isBlocked: boolean) {
    return this.request('whatsapp/block', {
      method: 'PUT',
      body: JSON.stringify({
        phone_number: phoneNumber,
        is_blocked: isBlocked,
      }),
    });
  }

  // AI Configuration
  async getAIConfig() {
    return this.request('ai/config');
  }

  async updateAIConfig(config: {
    ai_enabled?: boolean;
    ai_model?: string;
    system_prompt?: string;
    keywords_trigger?: string[];
    auto_reply_delay?: number;
    groq_api_key?: string;
    max_tokens?: number;
    temperature?: number;
  }) {
    return this.request('ai/config', {
      method: 'PUT',
      body: JSON.stringify(config),
    });
  }

  async getAvailableModels() {
    return this.request('ai/models');
  }

  async testAI(message: string) {
    return this.request('ai/test', {
      method: 'POST',
      body: JSON.stringify({
        message,
      }),
    });
  }

  async getAIStats(days: number = 30) {
    return this.request(`ai/stats?days=${days}`);
  }

  // Messages
  async getMessages(limit: number = 50, offset: number = 0) {
    return this.request(`messages?limit=${limit}&offset=${offset}`);
  }

  // Statistics
  async getStats(days: number = 30) {
    return this.request(`stats?days=${days}`);
  }

  // Test endpoints
  async testAPI() {
    return this.request('test');
  }

  async testDatabase() {
    return this.request('test/db');
  }

  async testGroq(apiKey: string, message: string, model?: string, systemPrompt?: string) {
    return this.request('test/groq', {
      method: 'POST',
      body: JSON.stringify({
        api_key: apiKey,
        message,
        model: model || 'mixtral-8x7b-32768',
        system_prompt: systemPrompt || 'You are a helpful assistant.',
      }),
    });
  }

  // Utility methods
  setSessionId(sessionId: string) {
    this.sessionId = sessionId;
    localStorage.setItem('session_id', sessionId);
  }

  clearSession() {
    this.sessionId = null;
    localStorage.removeItem('session_id');
  }

  isAuthenticated(): boolean {
    return !!this.sessionId;
  }
}

// Export singleton instance
export const apiService = new ApiService();
export default apiService;

// Export types
export type { ApiResponse };
