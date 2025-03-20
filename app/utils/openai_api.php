<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';

class OpenAIAPI {
    private $apiKey;
    private $model;
    
    public function __construct() {
        $this->apiKey = OPENAI_API_KEY;
        $this->model = AI_MODEL;
    }
    
    /**
     * Generate a chat response using OpenAI API
     * 
     * @param array $messages Array of message objects with role and content
     * @return string|null The AI response or null on error
     */
    public function generateChatResponse($messages) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];
        
        $data = [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => 500,
            'temperature' => 0.7
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err) {
            logError("OpenAI API error: " . $err);
            return null;
        }
        
        $responseData = json_decode($response, true);
        
        if (isset($responseData['error'])) {
            logError("OpenAI API error: " . $responseData['error']['message']);
            return null;
        }
        
        if (isset($responseData['choices'][0]['message']['content'])) {
            return $responseData['choices'][0]['message']['content'];
        }
        
        logError("Unexpected OpenAI API response", ['response' => $responseData]);
        return null;
    }
    
    /**
     * Format chat history into messages array for OpenAI API
     * 
     * @param array $chatHistory Array of chat messages with sender_type and message
     * @return array Formatted messages for OpenAI API
     */
    public function formatChatHistoryForAPI($chatHistory) {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a compassionate chaplain providing spiritual care and support. Respond with empathy, wisdom, and care. Your goal is to provide pastoral care and spiritual guidance. Avoid giving medical advice.'
            ]
        ];
        
        foreach ($chatHistory as $chat) {
            $role = ($chat['sender_type'] === 'participant') ? 'user' : 'assistant';
            $messages[] = [
                'role' => $role,
                'content' => $chat['message']
            ];
        }
        
        return $messages;
    }
    
    /**
     * Test the OpenAI API connection
     * 
     * @return bool True if connection is successful, false otherwise
     */
    public function testConnection() {
        $testMessages = [
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant.'
            ],
            [
                'role' => 'user',
                'content' => 'Hello, this is a test message. Please respond with "Connection successful".'
            ]
        ];
        
        $response = $this->generateChatResponse($testMessages);
        
        return $response !== null;
    }
}
?> 