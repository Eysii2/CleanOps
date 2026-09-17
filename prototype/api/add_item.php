<?php
require_once __DIR__ . '/../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_id = $_POST['shop_id'];
    $name = $_POST['item_name'];
    $qty = $_POST['quantity'];
    $unit = $_POST['unit'];
    $min = $_POST['min_stock'];

    $stmt = $conn->prepare("INSERT INTO inventory (shop_id, item_name, quantity, unit, min_stock) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isisi", $shop_id, $name, $qty, $unit, $min);
    
    if($stmt->execute()) {
        header("Location: ../stock.php");
    } else {
        echo "Error: " . $conn->error;
    }
}