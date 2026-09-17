<?php
session_start();
header('Content-Type: application/json');

// Make sure this path correctly points to your conn.php
require_once '../conn/conn.php'; 

// 1. Security Check: Ensure the user is logged in and has a shop assigned
if (!isset($_SESSION['user_id']) || !isset($_SESSION['shop_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access. Please log in."]);
    exit;
}

$shop_id = $_SESSION['shop_id'];

// 2. Validate POST inputs
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

$order_id = $_POST['order_id'] ?? '';
$new_status = $_POST['new_status'] ?? '';

if (empty($order_id) || empty($new_status)) {
    echo json_encode(["success" => false, "message" => "Missing order ID or new status."]);
    exit;
}

// 3. Security Check: Validate that the status is an allowed ENUM value
$allowed_statuses = [
    'Pending', 'Pending_Pickup', 'Ready_to_Wash', 
    'Washing_Done', 'Drying_Done', 'Ready_for_Delivery', 
    'Processing', 'Ready', 'Completed', 'Cancelled'
];

if (!in_array($new_status, $allowed_statuses)) {
    echo json_encode(["success" => false, "message" => "Invalid status provided."]);
    exit;
}

try {
    // 4. Update the database
    // Note: We enforce shop_id so a staff member can't maliciously update orders from another branch
    $sql = "UPDATE orders SET status = ? WHERE id = ? AND shop_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $new_status, $order_id, $shop_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "Task updated successfully."]);
        } else {
            // It succeeds even if 0 rows changed, because it might just mean 
            // someone else already clicked the button a second ago.
            echo json_encode(["success" => true, "message" => "No changes needed."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Database update failed."]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server Error: " . $e->getMessage()]);
}
?>