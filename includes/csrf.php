<?php
// Make sure a session exists
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create the token once per session
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Hidden field to place inside every form
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="'
         . htmlspecialchars(csrf_token()) . '">';
}

// Stop the request if the token is missing or wrong
function csrf_check(): void {
    $sent = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(403);
        die('Invalid or expired form. Go back, refresh the page and try again.');
    }
}
