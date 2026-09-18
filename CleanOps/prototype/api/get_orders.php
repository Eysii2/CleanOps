<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conn/conn.php';

// 1. Check which parameter the app/web is sending
// CHANGED: We now look for customer_id instead of customer_name
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;
$shop_id = isset($_GET['shop_id']) ? intval($_GET['shop_id']) : null;

// 2. The base query
// CHANGED: Added a LEFT JOIN to the customers table to grab their real name!
$base_sql = "SELECT 
            o.id, 
            c.fullname AS customer_name, 
            o.category,
            o.total_amount, 
            o.status, 
            o.staff_confirmed,
            o.customer_confirmed,
            s.shop_name,
            o.created_at
        FROM orders o
        JOIN shops s ON o.shop_id = s.id 
        LEFT JOIN customers c ON o.customer_id = c.id ";

$orders = array();

// 3. Dynamically add the WHERE clause based on who is asking
if ($customer_id !== null && $customer_id > 0) {
    // Mobile App asking for a specific customer's orders
    $sql = $base_sql . "WHERE o.customer_id = ? ORDER BY o.id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    
} elseif ($shop_id !== null && $shop_id > 0) {
    // Web Dashboard asking for a specific shop's orders
    $sql = $base_sql . "WHERE o.shop_id = ? ORDER BY o.id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $shop_id);
    
} else {
    // Fallback: If no parameters are sent, just fetch everything
    $sql = $base_sql . "ORDER BY o.id DESC";
    $stmt = $conn->prepare($sql);
}

// 4. Execute and fetch
if ($stmt->execute()) {
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    // Kept your exact JSON wrapper!
    echo json_encode([
        "status" => "success",
        "data" => $orders
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Could not retrieve orders: " . $conn->error
    ]);
}
?>