<?php
// Sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate email format
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generate a random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Randomize participant to AI or human condition
function randomizeParticipant() {
    // 50/50 chance of being assigned to human or AI
    return (rand(0, 1) === 0) ? 'human' : 'ai';
}

// Log errors to file
function logError($message, $context = []) {
    $logFile = __DIR__ . '/../logs/errors.log';
    $timestamp = date('Y-m-d H:i:s');
    
    // Create log directory if it doesn't exist
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    
    $contextStr = empty($context) ? '' : ' ' . json_encode($context);
    $logMessage = "[$timestamp] $message$contextStr" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Redirect to a specific page
function redirect($location) {
    header("Location: $location");
    exit;
}

// Check if user is logged in as admin
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Format date/time for display
function formatDateTime($datetime, $format = 'M j, Y g:i A') {
    $dt = new DateTime($datetime);
    return $dt->format($format);
}

// Check if a string is valid JSON
function isValidJson($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

// Export data to JSON file
function exportToJson($data, $filename) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Get the current active consent policy
function getActiveConsentPolicy($db) {
    $policy = $db->fetchOne("SELECT policy_text FROM consent_policy WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    return $policy ? $policy['policy_text'] : '';
}

// Get survey questions by type (pre or post)
function getSurveyQuestions($db, $type) {
    return $db->fetchAll(
        "SELECT * FROM survey_questions WHERE survey_type = ? ORDER BY order_num ASC",
        [$type]
    );
}

// Check if a chaplain is available
function isChaplainAvailable($db) {
    $result = $db->fetchOne(
        "SELECT COUNT(*) as count FROM chaplains WHERE status = 'active'"
    );
    return $result['count'] > 0;
}

// Format error messages for display
function displayError($message) {
    return '<div class="alert alert-danger">' . $message . '</div>';
}

// Format success messages for display
function displaySuccess($message) {
    return '<div class="alert alert-success">' . $message . '</div>';
}
?> 