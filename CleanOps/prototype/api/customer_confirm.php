<?php
require_once '../conn/conn.php';
header('Content-Type: application/json');

// Catch the data sent from Android Retrofit
$order_id = $_POST['order_id'] ?? 0;

if ($order_id > 0) {
    // 1. Save the Customer's Tick
    $stmt = $conn->prepare("UPDATE orders SET customer_confirmed = 1 WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    // 2. Check if the Staff has already ticked
    $check_stmt = $conn->prepare("SELECT staff_confirmed FROM orders WHERE id = ?");
    $check_stmt->bind_param("i", $order_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();

    if ($result && $result['staff_confirmed'] == 1) {
        // BOTH have ticked! Make it Officially Completed.
        $conn->query("UPDATE orders SET status = 'Completed' WHERE id = $order_id");
        echo json_encode(["success" => true, "message" => "Order Completed Successfully!"]);
    } else {
        // Only customer ticked, waiting on staff
        echo json_encode(["success" => true, "message" => "Confirmed! Waiting for staff verification."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid Order ID."]);
}
?>