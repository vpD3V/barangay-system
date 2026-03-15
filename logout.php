<?php
/**
 * Logout Function
 * Destroys session and redirects to home page
 * 
 * @author Barangay System Developer
 * @version 1.0
 */

session_start();
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to index page
header("Location: index.php");
exit();
?>