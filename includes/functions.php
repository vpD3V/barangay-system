<?php
/**
 * Reusable Functions
 * Security and helper functions for the system
 * 
 * @author Barangay System Developer
 * @version 1.0
 */

// Hash password securely
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password securely
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Sanitize input to prevent XSS
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Generate unique verification code
function generateVerificationCode() {
    return strtoupper(substr(md5(uniqid()), 0, 8));
}
?>