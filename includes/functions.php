<?php
/**
 * Sanitize user input
 * 
 * @param string $data The data to sanitize
 * @return string The sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has admin role
 * 
 * @return bool True if user is admin, false otherwise
 */
function is_admin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Check if user has staff role
 * 
 * @return bool True if user is staff, false otherwise
 */
function is_staff() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'staff';
}

/**
 * Check if user has student role
 * 
 * @return bool True if user is student, false otherwise
 */
function is_student() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student';
}

/**
 * Check if user has external role
 * 
 * @return bool True if user is external, false otherwise
 */
function is_external() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'external';
}

/**
 * Redirect user if not logged in
 * 
 * @param string $redirect_url URL to redirect to if not logged in
 */
function require_login($redirect_url = '../login.php') {
    if (!is_logged_in()) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Redirect user if not admin
 * 
 * @param string $redirect_url URL to redirect to if not admin
 */
function require_admin($redirect_url = '../login.php') {
    require_login($redirect_url);
    if (!is_admin()) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Redirect user if not staff
 * 
 * @param string $redirect_url URL to redirect to if not staff
 */
function require_staff($redirect_url = '../login.php') {
    require_login($redirect_url);
    if (!is_staff()) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Redirect user if not student
 * 
 * @param string $redirect_url URL to redirect to if not student
 */
function require_student($redirect_url = '../login.php') {
    require_login($redirect_url);
    if (!is_student()) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Redirect user if not external
 * 
 * @param string $redirect_url URL to redirect to if not external
 */
function require_external($redirect_url = '../login.php') {
    require_login($redirect_url);
    if (!is_external()) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Generate a random string
 * 
 * @param int $length Length of the random string
 * @return string Random string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Format date to a readable format
 * 
 * @param string $date Date string
 * @param string $format Format string
 * @return string Formatted date
 */
function format_date($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

