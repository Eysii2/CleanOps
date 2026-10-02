<?php
header('Content-Type: application/json');
require_once '../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Capture incoming data
    $fullname = trim($_POST['fullname'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    
    // Check for BOTH common key names to prevent mismatches
    $password = $_POST['password'] ?? $_POST['password_hash'] ?? ''; 

    // 2. Validation Check
    if (empty($fullname) || empty($email) || empty($password)) {
        echo json_encode([
            "success" => false, 
            "message" => "Please fill in all required fields.",
            "debug_info" => [
                "received_keys" => array_keys($_POST), // This tells you exactly what Android sent
                "fullname_status" => empty($fullname) ? "missing" : "received",
                "email_status" => empty($email) ? "missing" : "received",
                "password_status" => empty($password) ? "missing" : "received"
            ]
        ]);
        exit();
    }

    // 3. Encrypt the password for storage
    $password_to_store = password_hash($password, PASSWORD_DEFAULT);

    try {
        // 4. Prepare SQL (Ensure your DB table columns match these exactly)
        $stmt = $conn->prepare("INSERT INTO customers (fullname, phone, address, email, password_hash) VALUES (?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("sssss", $fullname, $phone, $address, $email, $password_to_store);

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true, 
                "message" => "Registration successful",
                "customer_id" => $conn->insert_id 
            ]);
        } else {
            // Likely a duplicate email error
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        echo json_encode([
            "success" => false, 
            "message" => "Database Error: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method. Please use POST."]);
}
?>