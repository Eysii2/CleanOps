<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $update_status = $conn->prepare("UPDATE users SET is_online = 0 WHERE id = ?");
    $update_status->bind_param("i", $user_id);
    $update_status->execute();
}

// Destroy the session
session_destroy();


// Redirect to login page
header("Location: index.html");
exit();
?>