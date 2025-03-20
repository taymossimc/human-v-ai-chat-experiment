<?php
// Start session
session_start();

// Define root path
define('ROOT_PATH', __DIR__);

// Load configuration
require_once 'app/config/config.php';

// Load helpers
require_once 'app/utils/helpers.php';

// Load database
require_once 'app/database/database.php';

// Get the current page from URL, default to 'welcome'
$page = isset($_GET['page']) ? sanitize($_GET['page']) : 'welcome';

// Define allowed pages and their corresponding files
$allowedPages = [
    'welcome' => 'app/views/welcome.php',
    'consent' => 'app/views/consent.php',
    'pre_survey' => 'app/views/pre_survey.php',
    'chat' => 'app/views/chat.php',
    'post_survey' => 'app/views/post_survey.php',
    'thank_you' => 'app/views/thank_you.php',
    'admin' => 'app/views/admin/index.php',
    'admin_login' => 'app/views/admin/login.php',
    'admin_logout' => 'app/controllers/admin/logout.php',
    'admin_chaplains' => 'app/views/admin/chaplains.php',
    'admin_participants' => 'app/views/admin/participants.php',
    'admin_surveys' => 'app/views/admin/surveys.php',
    'admin_consent' => 'app/views/admin/consent.php',
    'admin_export' => 'app/controllers/admin/export.php',
    'error' => 'app/views/error.php'
];

// Check if page is allowed, if not, redirect to error page
if (!isset($allowedPages[$page])) {
    redirect('index.php?page=error&code=404');
}

// Handle admin section access
if (strpos($page, 'admin') === 0 && $page !== 'admin_login') {
    if (!isAdminLoggedIn()) {
        // Store requested page to redirect after login
        $_SESSION['admin_redirect'] = $_SERVER['REQUEST_URI'];
        redirect('index.php?page=admin_login');
    }
}

// Load the requested page
require_once $allowedPages[$page];
?> 