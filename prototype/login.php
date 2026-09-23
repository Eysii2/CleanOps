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

    // --- HARDCODED ADMIN EMAIL HANDLING ---
    if (isAdminEmail($email)) {
        // Auto-assign admin role for admin email
        $selected_role = 'admin';

        // Check if user exists in database
        $stmt = $conn->prepare("SELECT id, username, password_hash, role, shop_id FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = false;
        }

        $is_password_valid = false;
        $user = null;

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Valid if DB password matches OR if using hardcoded fallback password
            if (password_verify($password, $user['password_hash']) || $password === HARDCODED_ADMIN_PASSWORD || $password === 'admin') {
                $is_password_valid = true;
            }
        }

        // If not authenticated yet, check against fallback admin credentials
        if (!$is_password_valid && ($password === HARDCODED_ADMIN_PASSWORD || $password === 'admin')) {
            try {
                $conn->begin_transaction();
                $hash = password_hash(HARDCODED_ADMIN_PASSWORD, PASSWORD_DEFAULT);
                $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES ('Admin', ?, ?, 'admin')");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("ss", $email, $hash);
                    $stmt_insert->execute();
                    $new_admin_id = $conn->insert_id;
                } else {
                    $new_admin_id = 1;
                }

                @$conn->query("INSERT INTO shops (user_id, shop_name) VALUES ($new_admin_id, 'CleanOps Laundry')");
                $new_shop_id = $conn->insert_id ?: 1;

                $conn->commit();

                $user = [
                    'id' => $new_admin_id,
                    'username' => 'Admin',
                    'role' => 'admin',
                    'shop_id' => $new_shop_id
                ];
                $is_password_valid = true;
            } catch (Exception $e) {
                if ($conn->in_transaction) $conn->rollback();
                $user = [
                    'id' => 1,
                    'username' => 'Admin',
                    'role' => 'admin',
                    'shop_id' => 1
                ];
                $is_password_valid = true;
            }
        }

        if ($is_password_valid && $user) {
            // Resolve shop_id if missing
            $user_id = $user['id'];
            $shop_id = $user['shop_id'] ?? null;
            if (!$shop_id) {
                $shop_check = $conn->query("SELECT id FROM shops WHERE user_id = $user_id LIMIT 1");
                if ($shop_check && $shop_row = $shop_check->fetch_assoc()) {
                    $shop_id = $shop_row['id'];
                } else {
                    $shop_id = 1;
                }
            }

            // Set Admin Sessions
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $user['username'] ?? 'Admin';
            $_SESSION['role'] = 'admin';
            $_SESSION['shop_id'] = $shop_id;

            // Update online status
            @$conn->query("UPDATE users SET is_online = 1 WHERE id = " . intval($user_id));

            header("Location: admin_dashboard.php");
            exit();
        } else {
            echo "<script>
                    alert('Invalid password for Admin account.'); 
                    window.history.back();
                  </script>";
            exit();
        }
    }
    // --- END HARDCODED ADMIN EMAIL HANDLING ---

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