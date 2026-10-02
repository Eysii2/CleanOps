<?php
header('Content-Type: application/json');
require_once '../conn/conn.php';

// Notice we added icon_name right here!
$sql = "SELECT id, shop_name, icon_name FROM shops"; 
$result = $conn->query($sql);

$shops = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $shops[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $shops]);
} else {
    echo json_encode(["status" => "error", "message" => "No shops found"]);
}
?>