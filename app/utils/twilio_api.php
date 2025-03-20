<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';

class TwilioAPI {
    private $accountSid;
    private $authToken;
    private $fromNumber;
    
    public function __construct() {
        $this->accountSid = TWILIO_ACCOUNT_SID;
        $this->authToken = TWILIO_AUTH_TOKEN;
        $this->fromNumber = TWILIO_PHONE_NUMBER;
    }
    
    /**
     * Send an SMS message using Twilio API
     * 
     * @param string $to Recipient phone number (must be in E.164 format)
     * @param string $message Message text
     * @return bool True if successful, false otherwise
     */
    public function sendSMS($to, $message) {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
        
        $data = [
            'From' => $this->fromNumber,
            'To' => $to,
            'Body' => $message
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->accountSid}:{$this->authToken}");
        
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err) {
            logError("Twilio API error: " . $err);
            return false;
        }
        
        $responseData = json_decode($response, true);
        
        if ($statusCode >= 400) {
            logError("Twilio API error: " . ($responseData['message'] ?? 'Unknown error'), [
                'status' => $statusCode,
                'response' => $responseData
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Send a chaplain availability check message
     * 
     * @param string $chaplainPhone Chaplain's phone number
     * @param int $sessionId Session ID
     * @return bool True if successful, false otherwise
     */
    public function sendChaplainAvailabilityCheck($chaplainPhone, $sessionId) {
        $message = "A participant is waiting for spiritual care chat. Are you available to respond? " .
            "Reply YES to accept or NO to decline. Session ID: {$sessionId}";
        
        return $this->sendSMS($chaplainPhone, $message);
    }
    
    /**
     * Relay a message from participant to chaplain
     * 
     * @param string $chaplainPhone Chaplain's phone number
     * @param string $message Message from participant
     * @param int $sessionId Session ID
     * @return bool True if successful, false otherwise
     */
    public function relayMessageToChaplain($chaplainPhone, $message, $sessionId) {
        $formattedMessage = "Session {$sessionId}: {$message}";
        return $this->sendSMS($chaplainPhone, $formattedMessage);
    }
    
    /**
     * Relay a message from chaplain to participant (via web interface)
     * 
     * @param string $fromPhone Chaplain's phone number
     * @param string $message SMS body
     * @return array Message data for processing or null on error
     */
    public function processIncomingSMS($fromPhone, $message) {
        // This would normally be called by a webhook from Twilio
        // For testing/development purposes, we're simulating this functionality
        
        // Check if this is a response to an availability check
        if (strtoupper(trim($message)) === 'YES' || strtoupper(trim($message)) === 'NO') {
            return [
                'type' => 'availability_response',
                'available' => strtoupper(trim($message)) === 'YES',
                'phone' => $fromPhone
            ];
        }
        
        // Extract session ID if present (format: "Session 123: message text")
        if (preg_match('/^Session\s+(\d+):\s*(.*)$/i', $message, $matches)) {
            return [
                'type' => 'chat_message',
                'session_id' => $matches[1],
                'message' => $matches[2],
                'phone' => $fromPhone
            ];
        }
        
        // If no recognizable format, treat as a regular message
        return [
            'type' => 'unknown',
            'message' => $message,
            'phone' => $fromPhone
        ];
    }
    
    /**
     * Test the Twilio API connection
     * 
     * @return bool True if connection is successful, false otherwise
     */
    public function testConnection() {
        // For testing, we'll just check if we have valid credentials
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}.json";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->accountSid}:{$this->authToken}");
        
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err || $statusCode >= 400) {
            return false;
        }
        
        return true;
    }
}
?> 