<?php
header('Content-Type: application/json');
require_once '../conn/conn.php';

// 1. Check if the Android app actually sent a shop_id
if (isset($_GET['shop_id'])) {
    
    // 2. Clean the ID so it's safe
    $shop_id = intval($_GET['shop_id']);
    
    // 3. Filter the database! ONLY grab services where the shop_id matches
    $sql = "SELECT id, service_name, price, category FROM services WHERE shop_id = $shop_id";
    $result = $conn->query($sql);

    $services = array();

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
        // Send the filtered list back to the phone
        echo json_encode(["status" => "success", "data" => $services]);
    } else {
        // If this shop has no services yet, send an empty list so the app doesn't crash
        echo json_encode(["status" => "success", "data" => []]); 
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing shop_id"]);
}
?>