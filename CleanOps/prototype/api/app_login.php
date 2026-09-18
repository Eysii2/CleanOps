<?php
header('Content-Type: application/json');
require_once '../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. Validation Check
    if (empty($email) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Please enter both email and password."]);
        exit;
    }

    // 2. Database Lookup
    try {
        $stmt = $conn->prepare("SELECT id, password_hash FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // 3. Verify Password
            if (password_verify($password, $row['password_hash'])) {
                echo json_encode([
                    "success" => true, 
                    "customer_id" => $row['id'],
                    "message" => "Login successful"
                ]);
                exit;
            }
        }

        // If no user found or password mismatch
        echo json_encode(["success" => false, "message" => "Invalid email or password."]);

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>