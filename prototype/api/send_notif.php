<?php
$config_file = __DIR__ . '/../config.local.php';
if (file_exists($config_file)) {
    require_once $config_file;
}

function sendPushNotification($message) {
    $app_id = defined('ONESIGNAL_APP_ID') ? ONESIGNAL_APP_ID : (getenv('ONESIGNAL_APP_ID') ?: 'YOUR_ONESIGNAL_APP_ID');
    $api_key = defined('ONESIGNAL_APP_KEY') ? ONESIGNAL_APP_KEY : (getenv('ONESIGNAL_APP_KEY') ?: 'YOUR_ONESIGNAL_APP_KEY');

    $content = array("en" => $message);
    $fields = array(
        'app_id' => $app_id,
        'included_segments' => array('All'),
        'contents' => $content
    );

    $fields = json_encode($fields);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic ' . $api_key
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_exec($ch);
    curl_close($ch);
}

// If the dashboard button is clicked, it runs this part
if (isset($_POST['message'])) {
    sendPushNotification($_POST['message']);
}
?>