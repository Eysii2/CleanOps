<?php
session_start();
header('Content-Type: application/json');

// Make sure this path correctly points to your conn.php
require_once '../conn/conn.php'; 

// Security check: Ensure the user is logged in and has a shop assigned
if (!isset($_SESSION['user_id']) || !isset($_SESSION['shop_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized or no shop selected."]);
    exit;
}

$shop_id = $_SESSION['shop_id'];

try {
    // 1. Query the database for orders belonging to THIS shop that need action
    $sql = "SELECT id, order_number, status, order_source, customer_name 
            FROM orders 
            WHERE shop_id = ? 
            AND status IN ('Pending_Pickup', 'Ready_to_Wash', 'Washing_Done', 'Drying_Done', 'Ready_for_Delivery')
            ORDER BY created_at ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $tasks = [];

    // 2. Translate database statuses into UI Tasks
    while ($order = $result->fetch_assoc()) {
        $task = [
            'order_id' => $order['id'],
            // If order_number is empty, fallback to the database ID just in case
            'order_number' => !empty($order['order_number']) ? $order['order_number'] : 'ORD-' . $order['id'],
            'customer_name' => !empty($order['customer_name']) ? $order['customer_name'] : 'Walk-in Customer',
            'source' => $order['order_source'],
            'raw_status' => $order['status']
        ];

        // The "Rules" Engine: What should the staff do based on the status?
        switch ($order['status']) {
            case 'Pending_Pickup':
                $task['title'] = 'New Pickup Request';
                $task['desc'] = 'Driver needs to pick up laundry from customer.';
                $task['icon'] = 'fa-truck';
                $task['btn_text'] = 'Mark as Received';
                $task['next_status'] = 'Ready_to_Wash';
                $task['type'] = 'urgent'; // Red border
                break;
                
            case 'Ready_to_Wash':
                $task['title'] = 'Load Washer';
                $task['desc'] = 'Laundry is checked in and ready to be washed.';
                $task['icon'] = 'fa-jug-detergent';
                $task['btn_text'] = 'Start Washing';
                $task['next_status'] = 'Washing_Done';
                $task['type'] = 'ready'; // Teal border
                break;

            case 'Washing_Done':
                $task['title'] = 'Transfer to Dryer';
                $task['desc'] = 'Washing complete. Machine is idle.';
                $task['icon'] = 'fa-stopwatch';
                $task['btn_text'] = 'Start Drying';
                $task['next_status'] = 'Drying_Done';
                $task['type'] = 'urgent';
                break;

            case 'Drying_Done':
                $task['title'] = 'Fold & Package';
                $task['desc'] = 'Laundry is dry and ready for folding.';
                $task['icon'] = 'fa-shirt';
                $task['btn_text'] = 'Mark as Folded';
                
                // If it's an app order, it needs delivery. If walk-in, it just waits for pickup.
                $task['next_status'] = ($order['order_source'] === 'app') ? 'Ready_for_Delivery' : 'Ready';
                $task['type'] = 'ready';
                break;

            case 'Ready_for_Delivery':
                $task['title'] = 'Assign Delivery Driver';
                $task['desc'] = 'Order is packed and needs to be returned to customer.';
                $task['icon'] = 'fa-truck-fast';
                $task['btn_text'] = 'Dispatch Order';
                $task['next_status'] = 'Completed';
                $task['type'] = 'ready';
                break;
        }

        $tasks[] = $task;
    }

    // 3. Send it to the frontend as JSON
    echo json_encode(["success" => true, "tasks" => $tasks]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database Error: " . $e->getMessage()]);
}
?>