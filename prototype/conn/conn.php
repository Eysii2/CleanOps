<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "prototype";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ==========================================
// Hardcoded Admin Email Configuration
// ==========================================
if (!defined('HARDCODED_ADMIN_EMAIL')) {
    define('HARDCODED_ADMIN_EMAIL', 'admin@cleanops.com');
}

if (!defined('HARDCODED_ADMIN_PASSWORD')) {
    define('HARDCODED_ADMIN_PASSWORD', 'admin123');
}

if (!defined('AUTHORIZED_ADMIN_EMAILS')) {
    define('AUTHORIZED_ADMIN_EMAILS', [
        'admin@cleanops.com',
        'jasminesarion04@gmail.com'
    ]);
}

/**
 * Check if a given email is a hardcoded/authorized admin email
 */
if (!function_exists('isAdminEmail')) {
    function isAdminEmail($email) {
        if (empty($email)) return false;
        $trimmed = strtolower(trim($email));
        $authorized = array_map('strtolower', AUTHORIZED_ADMIN_EMAILS);
        return in_array($trimmed, $authorized);
    }
}
?>