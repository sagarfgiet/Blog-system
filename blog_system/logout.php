<?php
require_once 'includes/functions.php';

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to home with success message
$_SESSION['flash_message'] = 'You have been logged out successfully';
$_SESSION['flash_class'] = 'alert-success';
redirect('index.php');
?>