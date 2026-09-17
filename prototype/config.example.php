<?php
// Configuration Template
// Copy this file to config.local.php and fill in your actual credentials

// Supabase Database Credentials
define('DB_HOST', 'db.your-project.supabase.co');
define('DB_PORT', '5432');
define('DB_NAME', 'postgres');
define('DB_USER', 'postgres');
define('DB_PASS', 'YOUR_SUPABASE_DB_PASSWORD');

// Google OAuth Credentials
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', 'http://localhost/prototype/api/google_callback.php');

// OneSignal API Credentials
define('ONESIGNAL_APP_ID', 'YOUR_ONESIGNAL_APP_ID');
define('ONESIGNAL_APP_KEY', 'YOUR_ONESIGNAL_APP_KEY');
define('ONESIGNAL_ORG_KEY', 'YOUR_ONESIGNAL_ORG_KEY');
