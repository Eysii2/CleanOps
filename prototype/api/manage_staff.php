<?php
session_start();
require_once __DIR__ . '/../conn/conn.php';

$action = $_GET['action'] ?? '';

if ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $shop_id = $_POST['shop_id'];
    $username = $_POST['username'];
    $email = trim($_POST['email'] ?? '');
    $phone = $_POST['phone_number'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Security: Prevent staff from using reserved Admin emails
    if (isAdminEmail($email)) {
        echo "<script>
                alert('Cannot add staff: " . htmlspecialchars($email) . " is reserved as an Administrator email.'); 
                window.history.back();
              </script>";
        exit();
    }
    
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
    
    // Check if the user to delete has a reserved admin email
    $check_user = $conn->query("SELECT email FROM users WHERE id = $id");
    if ($check_user && $u = $check_user->fetch_assoc()) {
        if (isAdminEmail($u['email'])) {
            echo "<script>
                    alert('Security Notice: Cannot delete the Primary Admin account.'); 
                    window.location.href = '../add_staff.php';
                  </script>";
            exit();
        }
    }

    // Security: Only delete if it's not an admin
    $conn->query("DELETE FROM users WHERE id = $id AND role != 'admin'");
    header("Location: ../add_staff.php?deleted=true");
}
?>