<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$conn_file = __DIR__ . '/conn/conn.php';
require_once $conn_file;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $selected_role = $_POST['role'] ?? '';

    if (!isset($conn)) {
        die("Database connection variable not found in conn.php");
    }

    $stmt = $conn->prepare("SELECT id, username, password_hash, role, shop_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password_hash'])) {

            if ($user['role'] === $selected_role) {

                // ✅ Sessions
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['shop_id'] = $user['shop_id'];

                // ✅ FIXED: Update online status
                $update_status = $conn->prepare("UPDATE users SET is_online = 1 WHERE id = ?");
                $update_status->bind_param("i", $user['id']);
                $update_status->execute();

                // ✅ Redirect
                $location = ($user['role'] === 'admin') 
                    ? "admin_dashboard.php" 
                    : "staff/staff_dashboard.php";

                header("Location: " . $location);
                exit();

            } else {
                // FIXED: Alert and redirect back
                echo "<script>
                        alert('Access Denied: You selected the wrong role.'); 
                        window.history.back();
                      </script>";
            }

        } else {
            // FIXED: Alert and redirect back
            echo "<script>
                    alert('Invalid password.'); 
                    window.history.back();
                  </script>";
        }

    } else {
        // FIXED: Alert and redirect back
        echo "<script>
                alert('No account found with that email.'); 
                window.history.back();
              </script>";
    }
}
?>