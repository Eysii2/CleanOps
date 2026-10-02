<?php
// register.php
require_once 'conn/conn.php';

// Force mysqli to throw exceptions for database errors
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect Form Data
    $shop_name = $_POST['shop_name'];
    $username = $_POST['username'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = 'admin'; 

    try {
        // Check if the username or email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_stmt->store_result(); 

        if ($check_stmt->num_rows > 0) {
            echo "<script>alert('Registration Failed: Username or Email is already taken.'); window.history.back();</script>";
            $check_stmt->close();
            $conn->close();
            exit(); 
        }
        $check_stmt->close();

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Start Transaction
        $conn->begin_transaction();

        // 1. Insert into Users table
        $stmt1 = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt1->bind_param("ssss", $username, $email, $hashed_password, $role);
        $stmt1->execute();
        
        // Get the ID of the user we just created
        $user_id = $conn->insert_id;

        // 2. Insert into Shops table using that ID
        $stmt2 = $conn->prepare("INSERT INTO shops (user_id, shop_name, address, contact_number) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("isss", $user_id, $shop_name, $address, $contact);
        $stmt2->execute();

        // If both succeed, commit changes
        $conn->commit();
        // Assuming index.html is in the root directory, outside the api folder
        echo "<script>alert('Registration Successful!'); window.location.href='index.html';</script>";

    } catch (Exception $e) {
        // If anything fails, undo everything
        $conn->rollback();
        echo "<script>alert('Database Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    }

    $conn->close();
}
?>