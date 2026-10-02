<?php
session_start();

require_once __DIR__ . '/../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $change = intval($_POST['change']);

    // Ensure connection exists
    if (!$conn) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE inventory SET quantity = GREATEST(0, quantity + ?) WHERE id = ?");
    $stmt->bind_param("ii", $change, $id);
    
    if ($stmt->execute()) {
        $check = $conn->prepare("SELECT quantity FROM inventory WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $res = $check->get_result()->fetch_assoc();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'new_qty' => $res['quantity']
        ]);
    }
    $stmt->close();
}
?>