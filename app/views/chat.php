<?php
$pageTitle = "Chat";
require_once 'app/views/layout/header.php';

// Include necessary files
require_once 'app/database/database.php';
require_once 'app/utils/helpers.php';
require_once 'app/utils/openai_api.php';
require_once 'app/utils/twilio_api.php';

// Check if user has a valid session
if (!isset($_SESSION['participant_id']) || !isset($_SESSION['session_id'])) {
    redirect('index.php');
}

// Initialize database connection
$db = Database::getInstance();

// Get session information
$sessionId = $_SESSION['session_id'];
$sessionType = $_SESSION['session_type'];

// Check if session exists and pre-survey is completed
$session = $db->fetchOne(
    "SELECT * FROM sessions WHERE id = ? AND status IN ('pre_survey_completed', 'chat_started', 'chat_completed')",
    [$sessionId]
);

if (!$session) {
    redirect('index.php');
}

// Get or create chat session
$chatSession = $db->fetchOne(
    "SELECT * FROM chat_sessions WHERE session_id = ?",
    [$sessionId]
);

if (!$chatSession) {
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // If human session, find an available chaplain
        $chaplainId = null;
        if ($sessionType === 'human') {
            $chaplain = $db->fetchOne(
                "SELECT id FROM chaplains WHERE status = 'active' ORDER BY RAND() LIMIT 1"
            );
            
            if ($chaplain) {
                $chaplainId = $chaplain['id'];
            } else {
                // If no chaplains available, fallback to AI (for development purposes)
                if (DEBUG) {
                    $_SESSION['session_type'] = $sessionType = 'ai';
                    $_SESSION['warning_message'] = "No human chaplains are available. Falling back to AI chat for demonstration.";
                } else {
                    throw new Exception("No chaplains are available at this time.");
                }
            }
        }
        
        // Create chat session
        $chatSessionId = $db->insert(
            "INSERT INTO chat_sessions (session_id, chaplain_id, start_time, status) VALUES (?, ?, NOW(), ?)",
            [$sessionId, $chaplainId, ($sessionType === 'human' && $chaplainId) ? 'waiting' : 'active']
        );
        
        // Update session status
        $db->update(
            "UPDATE sessions SET status = 'chat_started' WHERE id = ?",
            [$sessionId]
        );
        
        // If human session, notify chaplain
        if ($sessionType === 'human' && $chaplainId) {
            $chaplainDetails = $db->fetchOne(
                "SELECT * FROM chaplains WHERE id = ?",
                [$chaplainId]
            );
            
            if ($chaplainDetails) {
                // Send SMS to chaplain
                $twilioApi = new TwilioAPI();
                $messageResult = $twilioApi->sendChaplainAvailabilityCheck(
                    $chaplainDetails['phone'],
                    $chatSessionId
                );
                
                if (!$messageResult && !DEBUG) {
                    throw new Exception("Unable to contact chaplain. Please try again later.");
                }
            }
        }
        
        // Commit transaction
        $db->commit();
        
        // Reload chat session data
        $chatSession = $db->fetchOne(
            "SELECT * FROM chat_sessions WHERE id = ?",
            [$chatSessionId]
        );
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        
        // Handle error
        $_SESSION['error_message'] = $e->getMessage();
        logError("Chat session creation error: " . $e->getMessage());
        redirect('index.php?page=error');
    }
}

// Add system welcome message if no messages exist
$messages = $db->fetchAll(
    "SELECT * FROM chat_messages WHERE chat_session_id = ? ORDER BY timestamp ASC",
    [$chatSession['id']]
);

if (empty($messages)) {
    // Add welcome message
    $welcomeMessage = $sessionType === 'human' 
        ? "Welcome to the spiritual care chat. A chaplain will be with you shortly. Please feel free to share what's on your mind."
        : "Welcome to the spiritual care chat. I'm here to listen and support you. Please feel free to share what's on your mind or any spiritual concerns you'd like to discuss.";
    
    $senderType = $sessionType === 'human' ? 'chaplain' : 'ai';
    
    $db->insert(
        "INSERT INTO chat_messages (chat_session_id, sender_type, message, timestamp) VALUES (?, ?, ?, NOW())",
        [$chatSession['id'], $senderType, $welcomeMessage]
    );
    
    // Refresh messages
    $messages = $db->fetchAll(
        "SELECT * FROM chat_messages WHERE chat_session_id = ? ORDER BY timestamp ASC",
        [$chatSession['id']]
    );
}

// Handle send message (AJAX request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => ''];
    
    try {
        $userMessage = sanitize($_POST['message'] ?? '');
        
        if (empty($userMessage)) {
            throw new Exception("Message cannot be empty.");
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        // Insert user message
        $db->insert(
            "INSERT INTO chat_messages (chat_session_id, sender_type, message, timestamp) VALUES (?, 'participant', ?, NOW())",
            [$chatSession['id'], $userMessage]
        );
        
        // If AI session, generate response
        if ($sessionType === 'ai') {
            // Get chat history
            $chatHistory = $db->fetchAll(
                "SELECT * FROM chat_messages WHERE chat_session_id = ? ORDER BY timestamp ASC",
                [$chatSession['id']]
            );
            
            // Initialize OpenAI API
            $openaiApi = new OpenAIAPI();
            
            // Format messages for API
            $apiMessages = $openaiApi->formatChatHistoryForAPI($chatHistory);
            
            // Generate response
            $aiResponse = $openaiApi->generateChatResponse($apiMessages);
            
            if ($aiResponse) {
                // Insert AI response
                $db->insert(
                    "INSERT INTO chat_messages (chat_session_id, sender_type, message, timestamp) VALUES (?, 'ai', ?, NOW())",
                    [$chatSession['id'], $aiResponse]
                );
            } else {
                throw new Exception("Unable to generate AI response. Please try again.");
            }
        } else {
            // For human session, relay message to chaplain
            $chaplainDetails = $db->fetchOne(
                "SELECT c.* FROM chaplains c 
                 JOIN chat_sessions cs ON c.id = cs.chaplain_id 
                 WHERE cs.id = ?",
                [$chatSession['id']]
            );
            
            if ($chaplainDetails) {
                // Send SMS to chaplain
                $twilioApi = new TwilioAPI();
                $messageResult = $twilioApi->relayMessageToChaplain(
                    $chaplainDetails['phone'],
                    $userMessage,
                    $chatSession['id']
                );
                
                if (!$messageResult && !DEBUG) {
                    throw new Exception("Unable to relay message to chaplain.");
                }
            }
        }
        
        // Commit transaction
        $db->commit();
        
        $response['success'] = true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        
        $response['message'] = $e->getMessage();
        logError("Send message error: " . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

// Handle get messages (AJAX request)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_messages') {
    header('Content-Type: application/json');
    
    try {
        // Get last message timestamp from request
        $lastTimestamp = isset($_GET['last_timestamp']) ? sanitize($_GET['last_timestamp']) : null;
        
        $params = [$chatSession['id']];
        $sql = "SELECT * FROM chat_messages WHERE chat_session_id = ?";
        
        if ($lastTimestamp) {
            $sql .= " AND timestamp > ?";
            $params[] = $lastTimestamp;
        }
        
        $sql .= " ORDER BY timestamp ASC";
        
        // Get new messages
        $newMessages = $db->fetchAll($sql, $params);
        
        echo json_encode(['success' => true, 'messages' => $newMessages]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        logError("Get messages error: " . $e->getMessage());
    }
    
    exit;
}

// Handle end chat (AJAX request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'end_chat') {
    header('Content-Type: application/json');
    
    try {
        // Update chat session status
        $db->update(
            "UPDATE chat_sessions SET status = 'completed', end_time = NOW() WHERE id = ?",
            [$chatSession['id']]
        );
        
        // Update session status
        $db->update(
            "UPDATE sessions SET status = 'chat_completed', end_time = NOW() WHERE id = ?",
            [$sessionId]
        );
        
        echo json_encode(['success' => true, 'redirect' => 'index.php?page=post_survey']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        logError("End chat error: " . $e->getMessage());
    }
    
    exit;
}

// For development only: Simulate chaplain response
if (DEBUG && $sessionType === 'human' && isset($_GET['simulate_chaplain'])) {
    $simulatedMessage = "This is a simulated chaplain response for testing. How can I help you today?";
    
    $db->insert(
        "INSERT INTO chat_messages (chat_session_id, sender_type, message, timestamp) VALUES (?, 'chaplain', ?, NOW())",
        [$chatSession['id'], $simulatedMessage]
    );
    
    redirect('index.php?page=chat');
}

// Set up extra scripts for AJAX chat functionality
$extraScripts = '
<script>
    $(document).ready(function() {
        const chatContainer = $(".chat-container");
        let lastMessageTimestamp = "";
        
        // Function to scroll to bottom of chat
        function scrollToBottom() {
            chatContainer.scrollTop(chatContainer[0].scrollHeight);
        }
        
        // Initial scroll to bottom
        scrollToBottom();
        
        // Function to format messages
        function formatMessage(message) {
            const messageClass = message.sender_type === "participant" ? "message-participant" : 
                               (message.sender_type === "chaplain" ? "message-chaplain" : "message-ai");
            
            const formattedTime = new Date(message.timestamp).toLocaleTimeString([], {hour: "2-digit", minute:"2-digit"});
            
            return `
                <div class="message ${messageClass}">
                    <div>${message.message}</div>
                    <small class="text-muted">${formattedTime}</small>
                </div>
            `;
        }
        
        // Function to load new messages
        function loadNewMessages() {
            $.ajax({
                url: "index.php?page=chat&action=get_messages",
                method: "GET",
                data: {
                    last_timestamp: lastMessageTimestamp
                },
                dataType: "json",
                success: function(response) {
                    if (response.success && response.messages.length > 0) {
                        // Update last message timestamp
                        lastMessageTimestamp = response.messages[response.messages.length - 1].timestamp;
                        
                        // Append messages
                        response.messages.forEach(function(message) {
                            chatContainer.append(formatMessage(message));
                        });
                        
                        // Scroll to bottom
                        scrollToBottom();
                    }
                },
                error: function() {
                    console.error("Error loading messages");
                }
            });
        }
        
        // Set last message timestamp
        if ($(".message").length > 0) {
            lastMessageTimestamp = $(".message:last").data("timestamp");
        }
        
        // Poll for new messages every 3 seconds
        setInterval(loadNewMessages, 3000);
        
        // Send message form handling
        $("#message-form").submit(function(e) {
            e.preventDefault();
            
            const messageInput = $("#message-input");
            const message = messageInput.val().trim();
            
            if (message === "") {
                return;
            }
            
            // Disable form while sending
            $("#send-button").prop("disabled", true).html("<span class=\'loading-spinner\'></span> Sending...");
            
            $.ajax({
                url: "index.php?page=chat",
                method: "POST",
                data: {
                    action: "send_message",
                    message: message
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        messageInput.val("");
                        loadNewMessages();
                    } else {
                        alert("Error: " + (response.message || "Failed to send message"));
                    }
                },
                error: function() {
                    alert("Error: Failed to send message");
                },
                complete: function() {
                    $("#send-button").prop("disabled", false).text("Send");
                }
            });
        });
        
        // End chat button handling
        $("#end-chat-btn").click(function() {
            if (confirm("Are you sure you want to end this chat? You will be redirected to a post-chat survey.")) {
                $.ajax({
                    url: "index.php?page=chat",
                    method: "POST",
                    data: {
                        action: "end_chat"
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success && response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            alert("Error: " + (response.message || "Failed to end chat"));
                        }
                    },
                    error: function() {
                        alert("Error: Failed to end chat");
                    }
                });
            }
        });
    });
</script>
';
?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="card-title mb-4">
                    Spiritual Care Chat
                    <?php if ($sessionType === 'human'): ?>
                        <span class="badge bg-primary">Human Chaplain</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">AI Chaplain</span>
                    <?php endif; ?>
                </h3>
                
                <?php if (isset($_SESSION['warning_message'])): ?>
                    <div class="alert alert-warning">
                        <?php echo $_SESSION['warning_message']; ?>
                        <?php unset($_SESSION['warning_message']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="chat-container d-flex flex-column">
                    <?php foreach ($messages as $message): ?>
                        <div class="message message-<?php echo $message['sender_type']; ?>" data-timestamp="<?php echo $message['timestamp']; ?>">
                            <div><?php echo $message['message']; ?></div>
                            <small class="text-muted"><?php echo date('g:i A', strtotime($message['timestamp'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <form id="message-form" class="mt-3">
                    <div class="input-group">
                        <textarea id="message-input" class="form-control" placeholder="Type your message..." rows="2" required></textarea>
                        <button id="send-button" type="submit" class="btn btn-primary">Send</button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <button id="end-chat-btn" class="btn btn-outline-secondary">End Chat & Continue to Survey</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'app/views/layout/footer.php';
?> 