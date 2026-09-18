<?php
session_start();
require_once __DIR__ . '/../conn/conn.php';

$action = $_GET['action'] ?? '';

if ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $shop_id = $_POST['shop_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone_number'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // This part takes the checkboxes (Mon, Tue, etc.) and turns them into a string
    $schedule_array = isset($_POST['schedule']) ? $_POST['schedule'] : [];
    $schedule_string = implode(',', $schedule_array); 

    $stmt = $conn->prepare("INSERT INTO users (username, email, phone_number, password_hash, role, work_schedule, shop_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    // 6 strings (s) and 1 integer (i)
    $stmt->bind_param("ssssssi", $username, $email, $phone, $password, $role, $schedule_string, $shop_id);
    
    if($stmt->execute()) {
        header("Location: ../add_staff.php?success=true");
    } else {
        echo "Error: " . $conn->error;
    }
}

if ($action == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Security: Only delete if it's not the main admin
    $conn->query("DELETE FROM users WHERE id = $id AND role != 'admin'");
    header("Location: ../add_staff.php?deleted=true");
}
?>