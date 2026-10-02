<?php
// Load local configuration if available
$config_file = __DIR__ . '/../config.local.php';
if (file_exists($config_file)) {
    require_once $config_file;
}

$host     = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'db.ocyojmaobggmkarjuglo.supabase.co');
$port     = defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: '5432');
$dbname   = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'postgres');
$user     = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'postgres');
$password = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: '');

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Testing message (optional)
    // echo "<h1>Successfully connected to Supabase!</h1>";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
