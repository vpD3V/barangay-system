<?php
/**
 * Authentication Check
 * Protects pages from unauthorized access
 * 
 * @author Barangay System Developer
 * @version 1.0
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
?>