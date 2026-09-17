<?php
session_start();
require_once __DIR__ . '/../conn/conn.php';

// Security Check: Only admins allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'save_service':
        $shop_id = $_POST['shop_id'];
        $name = $_POST['service_name'];
        $cat = $_POST['category'];
        $price = $_POST['price'];
        $is_promo = isset($_POST['is_promo']) ? 1 : 0;
        $desc = $_POST['promo_description'];

        // Start a transaction so if the recipe fails, the service isn't saved half-broken
        $conn->begin_transaction();

        try {
            // 1. Insert the main Service
            $stmt = $conn->prepare("INSERT INTO services (shop_id, service_name, category, price, is_promo, promo_description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdis", $shop_id, $name, $cat, $price, $is_promo, $desc);
            $stmt->execute();
            
            // Grab the ID of the service we just created
            $new_service_id = $conn->insert_id; 

            // 2. Insert the Recipe Items (If the admin added any)
            if (isset($_POST['recipe_items']) && isset($_POST['recipe_qty'])) {
                $items = $_POST['recipe_items'];
                $qtys = $_POST['recipe_qty'];
                
                $recipe_stmt = $conn->prepare("INSERT INTO service_recipes (service_id, item_name, quantity_per_kg) VALUES (?, ?, ?)");
                
                // Loop through all the dynamic rows the admin added
                for ($i = 0; $i < count($items); $i++) {
                    $item_name = $items[$i];
                    $qty = floatval($qtys[$i]);
                    
                    // Only insert if the row wasn't left blank
                    if (!empty($item_name) && $qty > 0) {
                        $recipe_stmt->bind_param("isd", $new_service_id, $item_name, $qty);
                        $recipe_stmt->execute();
                    }
                }
            }

            $conn->commit();
            header("Location: ../manage.php?success=service_added");

        } catch (Exception $e) {
            $conn->rollback();
            die("Error saving service and recipe: " . $e->getMessage());
        }
        break;

    case 'delete_service':
        $id = intval($_GET['id']);
        // Note: Because we used ON DELETE CASCADE when making the service_recipes table, 
        // deleting the service here will automatically delete its recipes too!
        $conn->query("DELETE FROM services WHERE id = $id");
        header("Location: ../manage.php?success=service_deleted");
        break;

    case 'update_settings':
        $user_id = $_SESSION['user_id'];
        $name = $_POST['shop_name'];
        $addr = $_POST['address'];
        $contact = $_POST['contact']; 
        $open = $_POST['opening_time'];
        $close = $_POST['closing_time'];
        
        $icon_path = null;

        // Handle Image Upload
        if (isset($_FILES['icon_name']) && $_FILES['icon_name']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_ext = strtolower(pathinfo($_FILES["icon_name"]["name"], PATHINFO_EXTENSION));
            $new_filename = 'shop_' . $user_id . '_' . time() . '.' . $file_ext;
            $target_file = $upload_dir . $new_filename;

            if (in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
                if (move_uploaded_file($_FILES["icon_name"]["tmp_name"], $target_file)) {
                    $icon_path = 'uploads/' . $new_filename; 
                }
            }
        }

        if ($icon_path) {
            $stmt = $conn->prepare("UPDATE shops SET shop_name = ?, address = ?, contact_number = ?, opening_time = ?, closing_time = ?, icon_name = ? WHERE user_id = ?");
            $stmt->bind_param("ssssssi", $name, $addr, $contact, $open, $close, $icon_path, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE shops SET shop_name = ?, address = ?, contact_number = ?, opening_time = ?, closing_time = ? WHERE user_id = ?");
            $stmt->bind_param("sssssi", $name, $addr, $contact, $open, $close, $user_id);
        }
        
        if ($stmt->execute()) {
            unset($_SESSION['needs_setup']);
            header("Location: ../manage.php?updated=1");
        } else {
            echo "Error: " . $conn->error;
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid Action']);
        break;
}
?>