<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conn/conn.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $query = "SELECT sender_name, message FROM chat_messages ORDER BY id ASC";
    $result = $conn->query($query);
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $messages]);

} elseif ($method === 'POST') {
    $sender = $_POST['sender_name'] ?? 'Customer';
    $msg = $_POST['message'] ?? '';
    
    if (!empty($msg)) {
        $stmt = $conn->prepare("INSERT INTO chat_messages (sender_name, message) VALUES (?, ?)");
        $stmt->bind_param("ss", $sender, $msg);
        $stmt->execute();
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Empty message"]);
    }
}
?>