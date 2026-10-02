<?php
require_once __DIR__ . '/../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $change = $_POST['change'];

    $conn->query("UPDATE inventory SET quantity = GREATEST(0, quantity + $change) WHERE id = $id");
    
    $res = $conn->query("SELECT quantity, min_stock FROM inventory WHERE id = $id")->fetch_assoc();
    echo json_encode(['success' => true, 'new_qty' => $res['quantity'], 'min_stock' => $res['min_stock']]);
}