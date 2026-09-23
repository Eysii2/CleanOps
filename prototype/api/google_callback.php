<?php
// google_callback.php
session_start();
require_once '../conn/conn.php';
require_once 'google_config.php';

// Force database errors to show
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (isset($_GET['code'])) {
    // Exchange code for Access Token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);
        
        // Get profile info
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $email = $google_account_info->email;
        $name = $google_account_info->name;

        try {
            // Check if email exists
            $check_stmt = $conn->prepare("SELECT id, username, role FROM users WHERE email = ?");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            $is_admin = isAdminEmail($email);

            if ($result->num_rows > 0) {
                // ACCOUNT EXISTS: Log them in
                $user = $result->fetch_assoc();
                
                // If email is in authorized admin list, ensure admin role
                $effective_role = $is_admin ? 'admin' : $user['role'];

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $effective_role;
                
                $redirect = ($effective_role === 'admin') ? "../admin_dashboard.php" : "../staff/staff_dashboard.php";
                header("Location: " . $redirect);
                exit();
                
            } else {
                // NEW ACCOUNT: Registration starts here
                $conn->begin_transaction();

                $role = $is_admin ? 'admin' : 'staff';
                // Random password hash for DB security
                $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

                // 1. Create the User
                $stmt1 = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
                $stmt1->bind_param("ssss", $name, $email, $random_password, $role);
                $stmt1->execute();
                $new_user_id = $conn->insert_id;

                if ($role === 'admin') {
                    // 2. Create the Shop record with NULLs for address/contact
                    $placeholder_name = $name . "'s Shop";
                    $stmt2 = $conn->prepare("INSERT INTO shops (user_id, shop_name, address, contact_number) VALUES (?, ?, NULL, NULL)");
                    $stmt2->bind_param("is", $new_user_id, $placeholder_name);
                    $stmt2->execute();

                    $conn->commit();

                    // 3. Set Session Data
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['username'] = $name;
                    $_SESSION['role'] = $role;
                    $_SESSION['needs_setup'] = true; 

                    header("Location: ../admin_dashboard.php?setup=required");
                    exit();
                } else {
                    // Staff user: link to existing default shop
                    $shop_check = $conn->query("SELECT id FROM shops LIMIT 1");
                    $default_shop_id = ($shop_check && $s_row = $shop_check->fetch_assoc()) ? $s_row['id'] : 1;
                    $conn->query("UPDATE users SET shop_id = $default_shop_id WHERE id = $new_user_id");

                    $conn->commit();

                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['username'] = $name;
                    $_SESSION['role'] = 'staff';
                    $_SESSION['shop_id'] = $default_shop_id;

                    header("Location: ../staff/staff_dashboard.php");
                    exit();
                }
            }

        } catch (Exception $e) {
            if ($conn->in_transaction) {
                $conn->rollback();
            }
            echo "Database Error: " . $e->getMessage();
        }
    } else {
        echo "Google Authentication Failed: " . htmlspecialchars($token['error_description'] ?? 'Unknown Error');
    }
} else {
    header("Location: ../index.html");
    exit();
}
?>