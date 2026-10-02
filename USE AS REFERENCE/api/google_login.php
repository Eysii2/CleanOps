<?php
// api/google_login.php
require_once 'google_config.php';

// Generate the URL to Google's OAuth consent screen
$authUrl = $client->createAuthUrl();

// Redirect the user to Google
header("Location: " . filter_var($authUrl, FILTER_SANITIZE_URL));
exit();
?>