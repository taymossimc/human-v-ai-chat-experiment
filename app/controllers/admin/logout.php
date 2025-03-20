<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include helper functions
require_once 'app/utils/helpers.php';

// Unset admin-related session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_role']);

// Set success message
$_SESSION['success_message'] = "You have been successfully logged out.";

// Redirect to login page
redirect('index.php?page=admin_login');
?> 