<?php
session_start();
require_once '../conn/conn.php';

$config_file = __DIR__ . '/../config.local.php';
if (file_exists($config_file)) {
    require_once $config_file;
}

header('Content-Type: application/json');

// --- ONE SIGNAL PUSH NOTIFICATION FUNCTION ---
function sendPushNotification($message) {
    $app_id = defined('ONESIGNAL_APP_ID') ? ONESIGNAL_APP_ID : (getenv('ONESIGNAL_APP_ID') ?: 'YOUR_ONESIGNAL_APP_ID');
    $api_key = defined('ONESIGNAL_ORG_KEY') ? ONESIGNAL_ORG_KEY : (getenv('ONESIGNAL_ORG_KEY') ?: 'YOUR_ONESIGNAL_ORG_KEY');

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
// ---------------------------------------------

// Security Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    // Support BOTH the old dashboard (action) and the new Task Module (new_status)
    $action = '';
    if (isset($_POST['action']) && !empty($_POST['action'])) {
        $action = trim($_POST['action']);
    } elseif (isset($_POST['new_status']) && !empty($_POST['new_status'])) {
        $action = trim($_POST['new_status']);
    }

    if ($order_id > 0 && !empty($action)) {
        
        // 1. UPDATE ORDER STATUS (Combines Old Statuses + New Task Module Statuses)
        $allowed_statuses = [
            'Pending', 'Processing', 'Ready', 'Completed', 'Cancelled', 
            'Pending_Pickup', 'Ready_to_Wash', 'Washing_Done', 'Drying_Done', 'Ready_for_Delivery'
        ];

        if (in_array($action, $allowed_statuses)) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $action, $order_id);
            
            if ($stmt->execute()) {
                // Trigger Push Notification for status changes
                $formatted_id = str_pad($order_id, 4, '0', STR_PAD_LEFT);
                
                // Clean up underscores for the push notification text (e.g., "Ready_to_Wash" -> "Ready to Wash")
                $display_status = str_replace('_', ' ', $action);
                sendPushNotification("Update: Order #ORD-{$formatted_id} is now {$display_status}!");
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
            $stmt->close();
            
        // 2. UPDATE PAYMENT STATUS (Paid, Unpaid)
        } elseif (in_array($action, ['Paid', 'Unpaid'])) {
            $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
            $stmt->bind_param("si", $action, $order_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
            $stmt->close();
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>