<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'chatapp');
define('DB_PASS', 'your_password');
define('DB_NAME', 'chat_experiment');

// API Keys (Replace with actual keys for production)
define('OPENAI_API_KEY', 'your_openai_api_key_here');
define('TWILIO_ACCOUNT_SID', 'your_twilio_account_sid_here');
define('TWILIO_AUTH_TOKEN', 'your_twilio_auth_token_here');
define('TWILIO_PHONE_NUMBER', 'your_twilio_phone_number');

// Application settings
define('BASE_URL', 'http://localhost/chat-experiment');
define('APP_NAME', 'Chat Experiment');
define('ADMIN_EMAIL', 'admin@example.com');

// Session settings
define('SESSION_LIFETIME', 3600); // 1 hour

// Experiment settings
define('AI_MODEL', 'gpt-4'); // OpenAI model to use for AI chat
define('CHAT_TIMEOUT', 1800); // 30 minutes
define('MIN_CHAT_LENGTH', 10); // Minimum number of messages in a chat

// Survey settings
define('SURVEY_TIMEOUT', 600); // 10 minutes

// Debug mode (set to false in production)
define('DEBUG', true);
?> 