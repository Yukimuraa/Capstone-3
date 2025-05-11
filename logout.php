<?php
session_start();

// Check if a specific user type is being logged out
$user_type = $_GET['user_type'] ?? $_SESSION['active_user_type'] ?? null;

if ($user_type && isset($_SESSION['user_sessions'][$user_type])) {
    // Remove only the specified user type session
    unset($_SESSION['user_sessions'][$user_type]);
    
    // If this was the active user type, reset the active user type
    if (isset($_SESSION['active_user_type']) && $_SESSION['active_user_type'] === $user_type) {
        // Set another user type as active if available
        if (!empty($_SESSION['user_sessions'])) {
            $_SESSION['active_user_type'] = array_key_first($_SESSION['user_sessions']);
        } else {
            unset($_SESSION['active_user_type']);
        }
    }
    
    // Clear the direct session variables if they match the logged out user
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === $user_type) {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_type']);
    }
} else {
    // If no specific user type or no sessions exist, log out all users
    unset($_SESSION['user_sessions']);
    unset($_SESSION['active_user_type']);
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
    unset($_SESSION['user_type']);
}

// Determine the absolute path to the login page
// Get the directory name of the current script
$current_script_dir = dirname($_SERVER['SCRIPT_NAME']);

// Remove any user type subdirectory if present
$base_path = preg_replace('/(\/admin|\/student|\/staff|\/external)$/', '', $current_script_dir);

// If we're already at the root, don't add an extra slash
if ($base_path === '') {
    $login_path = '/login.php';
} else {
    // Make sure we have a trailing slash
    if (substr($base_path, -1) !== '/') {
        $base_path .= '/';
    }
    $login_path = $base_path . 'login.php';
}

// Redirect to login page
header("Location: $login_path");
exit();
