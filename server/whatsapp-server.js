const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const cors = require('cors');
const axios = require('axios');
require('dotenv').config();

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
  cors: {
    origin: "http://localhost:5173",
    methods: ["GET", "POST"]
  }
});

app.use(cors());
app.use(express.json());

// Store WhatsApp clients for multiple users
const clients = new Map();
const userSessions = new Map();

// Initialize WhatsApp client for a user
function initializeWhatsAppClient(userId, socketId) {
  console.log(`Initializing WhatsApp client for user: ${userId}`);
  
  const client = new Client({
    authStrategy: new LocalAuth({
      clientId: `user_${userId}`
    }),
    puppeteer: {
      headless: true,
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-accelerated-2d-canvas',
        '--no-first-run',
        '--no-zygote',
        '--single-process',
        '--disable-gpu'
      ]
    }
  });

  // QR Code generation
  client.on('qr', (qr) => {
    console.log('QR Code generated for user:', userId);
    qrcode.generate(qr, { small: true });
    
    // Send QR code to frontend
    io.to(socketId).emit('qr-code', {
      qr: qr,
      userId: userId
    });
  });

  // Client ready
  client.on('ready', async () => {
    console.log(`WhatsApp client ready for user: ${userId}`);
    const clientInfo = client.info;
    
    io.to(socketId).emit('client-ready', {
      userId: userId,
      phoneNumber: clientInfo.wid.user,
      pushname: clientInfo.pushname
    });

    // Update database connection status
    await updateConnectionStatus(userId, 'connected', clientInfo.wid.user);
  });

  // Authentication success
  client.on('authenticated', () => {
    console.log(`Client authenticated for user: ${userId}`);
    io.to(socketId).emit('authenticated', { userId });
  });

  // Authentication failure
  client.on('auth_failure', (msg) => {
    console.error(`Authentication failed for user ${userId}:`, msg);
    io.to(socketId).emit('auth-failure', { userId, error: msg });
  });

  // Client disconnected
  client.on('disconnected', (reason) => {
    console.log(`Client disconnected for user ${userId}:`, reason);
    io.to(socketId).emit('disconnected', { userId, reason });
    
    // Update database connection status
    updateConnectionStatus(userId, 'disconnected');
    
    // Remove client from memory
    clients.delete(userId);
  });

  // Incoming messages
  client.on('message', async (message) => {
    console.log(`New message for user ${userId}:`, message.body);
    
    // Store message in database
    await storeMessage(userId, message);
    
    // Check if AI auto-reply is enabled
    const aiConfig = await getAIConfiguration(userId);
    if (aiConfig && aiConfig.ai_enabled) {
      await handleAutoReply(userId, message, aiConfig, client);
    }
  });

  // Store client
  clients.set(userId, client);
  userSessions.set(userId, socketId);

  // Initialize client
  client.initialize();
  
  return client;
}

// Handle AI auto-reply
async function handleAutoReply(userId, message, aiConfig, client) {
  try {
    // Skip if message is from the bot itself
    if (message.fromMe) return;
    
    // Check keywords trigger if configured
    if (aiConfig.keywords_trigger && aiConfig.keywords_trigger.length > 0) {
      const messageText = message.body.toLowerCase();
      const hasKeyword = aiConfig.keywords_trigger.some(keyword => 
        messageText.includes(keyword.toLowerCase())
      );
      
      if (!hasKeyword) return;
    }

    // Add delay before replying
    await new Promise(resolve => setTimeout(resolve, aiConfig.auto_reply_delay || 3000));

    // Generate AI response using Groq
    const aiResponse = await generateGroqResponse(message.body, aiConfig);
    
    if (aiResponse) {
      // Send reply
      await client.sendMessage(message.from, aiResponse);
      
      // Log the auto-reply
      await logAutoReply(userId, message.from, message.body, aiResponse, true);
      
      console.log(`Auto-reply sent for user ${userId}: ${aiResponse}`);
    }
    
  } catch (error) {
    console.error(`Error in auto-reply for user ${userId}:`, error);
    await logAutoReply(userId, message.from, message.body, '', false, error.message);
  }
}

// Generate response using Groq API
async function generateGroqResponse(messageText, aiConfig) {
  try {
    const response = await axios.post('https://api.groq.com/openai/v1/chat/completions', {
      model: aiConfig.ai_model || 'mixtral-8x7b-32768',
      messages: [
        {
          role: 'system',
          content: aiConfig.system_prompt || 'Anda adalah asisten WhatsApp yang membantu menjawab pesan dengan ramah dan informatif.'
        },
        {
          role: 'user',
          content: messageText
        }
      ],
      max_tokens: 500,
      temperature: 0.7
    }, {
      headers: {
        'Authorization': `Bearer ${process.env.GROQ_API_KEY}`,
        'Content-Type': 'application/json'
      }
    });

    return response.data.choices[0].message.content;
  } catch (error) {
    console.error('Error generating Groq response:', error.response?.data || error.message);
    return null;
  }
}

// Database helper functions (you'll need to implement these with your Supabase client)
async function updateConnectionStatus(userId, status, phoneNumber = null) {
  // Implement Supabase update for whatsapp_connections table
  console.log(`Update connection status for user ${userId}: ${status}`);
}

async function storeMessage(userId, message) {
  // Implement Supabase insert for chat_messages table
  console.log(`Store message for user ${userId}: ${message.body}`);
}

async function getAIConfiguration(userId) {
  // Implement Supabase query for ai_configurations table
  // For now, return a mock configuration
  return {
    ai_enabled: true,
    ai_model: 'mixtral-8x7b-32768',
    system_prompt: 'Anda adalah asisten WhatsApp yang membantu menjawab pesan dengan ramah dan informatif.',
    keywords_trigger: null,
    auto_reply_delay: 3000
  };
}

async function logAutoReply(userId, contactNumber, originalMessage, aiResponse, success, errorMessage = null) {
  // Implement Supabase insert for auto_reply_logs table
  console.log(`Log auto-reply for user ${userId}: ${success ? 'Success' : 'Failed'}`);
}

// Socket.IO connection handling
io.on('connection', (socket) => {
  console.log('Client connected:', socket.id);

  // Initialize WhatsApp for user
  socket.on('initialize-whatsapp', (data) => {
    const { userId } = data;
    console.log(`Initialize WhatsApp request for user: ${userId}`);
    
    if (!clients.has(userId)) {
      initializeWhatsAppClient(userId, socket.id);
    } else {
      socket.emit('already-initialized', { userId });
    }
  });

  // Disconnect WhatsApp
  socket.on('disconnect-whatsapp', async (data) => {
    const { userId } = data;
    console.log(`Disconnect WhatsApp request for user: ${userId}`);
    
    const client = clients.get(userId);
    if (client) {
      await client.destroy();
      clients.delete(userId);
      userSessions.delete(userId);
      
      socket.emit('whatsapp-disconnected', { userId });
    }
  });

  // Send message
  socket.on('send-message', async (data) => {
    const { userId, to, message } = data;
    const client = clients.get(userId);
    
    if (client) {
      try {
        await client.sendMessage(to, message);
        socket.emit('message-sent', { userId, to, message });
      } catch (error) {
        socket.emit('message-error', { userId, error: error.message });
      }
    }
  });

  // Get client status
  socket.on('get-client-status', (data) => {
    const { userId } = data;
    const client = clients.get(userId);
    
    socket.emit('client-status', {
      userId,
      connected: client ? true : false,
      state: client ? client.getState() : 'DISCONNECTED'
    });
  });

  socket.on('disconnect', () => {
    console.log('Client disconnected:', socket.id);
  });
});

// REST API endpoints
app.get('/api/health', (req, res) => {
  res.json({ status: 'OK', message: 'WhatsApp Server is running' });
});

app.post('/api/test-groq', async (req, res) => {
  try {
    const { message, apiKey, model, systemPrompt } = req.body;
    
    const response = await axios.post('https://api.groq.com/openai/v1/chat/completions', {
      model: model || 'mixtral-8x7b-32768',
      messages: [
        {
          role: 'system',
          content: systemPrompt || 'Anda adalah asisten WhatsApp yang membantu menjawab pesan dengan ramah dan informatif.'
        },
        {
          role: 'user',
          content: message
        }
      ],
      max_tokens: 500,
      temperature: 0.7
    }, {
      headers: {
        'Authorization': `Bearer ${apiKey}`,
        'Content-Type': 'application/json'
      }
    });

    res.json({
      success: true,
      response: response.data.choices[0].message.content
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.response?.data?.error?.message || error.message
    });
  }
});

const PORT = process.env.PORT || 3001;
server.listen(PORT, () => {
  console.log(`WhatsApp Server running on port ${PORT}`);
});
