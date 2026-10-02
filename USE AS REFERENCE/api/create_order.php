<?php
header('Access-Control-Allow-Origin: *'); 
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../conn/conn.php';
session_start(); // Needed for Walk-in orders to identify the shop

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true) ?: $_POST;

    // --- 1. SMART DATA EXTRACTION ---
    // It seamlessly handles different variable names from Android vs Web
    $shop_id      = isset($data['shop_id']) ? intval($data['shop_id']) : ($_SESSION['shop_id'] ?? 0);
    $service_id   = isset($data['service_id']) ? intval($data['service_id']) : 0;
    $weight       = isset($data['weight']) ? floatval($data['weight']) : 1.0; // Default to 1kg if App doesn't send weight
    $category     = isset($data['category']) ? trim($data['category']) : 'Uncategorized';
    
    // Accept either 'total_amount' (App) or 'total' (POS)
    $total_amount = isset($data['total_amount']) ? floatval($data['total_amount']) : (isset($data['total']) ? floatval($data['total']) : 0.00);
    $payment_method = isset($data['payment_method']) ? trim($data['payment_method']) : (isset($data['method']) ? trim($data['method']) : 'Pending');
    
    // --- 2. ROUTING LOGIC (App vs Walk-in) ---
    // We check if the request told us it's a walk-in. If not, assume it's from the App.
    $order_source = isset($data['source']) ? trim($data['source']) : 'app'; 

    if ($order_source === 'walk-in') {
        // POS Walk-in Rules
        $status = 'Ready_to_Wash'; 
        $payment_status = 'Paid';
        $customer_name = !empty($data['customer_name']) ? trim($data['customer_name']) : 'Walk-in Customer';
        $customer_id = null; 
        $order_number = 'POS-' . time();
    } else {
        // Mobile App Rules
        $status = 'Pending_Pickup';
        $payment_status = 'Unpaid';
        $customer_name = !empty($data['customer_name']) ? trim($data['customer_name']) : 'App Customer';
        $customer_id = isset($data['customer_id']) ? intval($data['customer_id']) : null;
        $order_number = 'APP-' . time();
    }

    if ($shop_id == 0 || $service_id == 0) {
        echo json_encode(["success" => false, "message" => "Invalid shop or service selected."]);
        exit;
    }

    $conn->begin_transaction();

    try {
        // --- 3. UNIVERSAL INVENTORY RECIPE CHECK ---
        $recipe_stmt = $conn->prepare("SELECT item_name, quantity_per_kg FROM service_recipes WHERE service_id = ?");
        $recipe_stmt->bind_param("i", $service_id);
        $recipe_stmt->execute();
        $recipes = $recipe_stmt->get_result();

        // Check and deduct every supply needed for this service based on the weight
        while ($recipe = $recipes->fetch_assoc()) {
            $item = $recipe['item_name'];
            $total_needed = $recipe['quantity_per_kg'] * $weight;

            $check_stock = $conn->prepare("SELECT quantity FROM inventory WHERE shop_id = ? AND item_name = ?");
            $check_stock->bind_param("is", $shop_id, $item);
            $check_stock->execute();
            $result = $check_stock->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Inventory Error: '$item' is not registered in your stock.");
            }
            
            $current_stock = $result->fetch_assoc()['quantity'];

            if ($current_stock < $total_needed) {
                throw new Exception("Insufficient Stock! Need $total_needed of $item, but only $current_stock left.");
            }

            // Deduct exact fractional amount
            $update_stock = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE shop_id = ? AND item_name = ?");
            $update_stock->bind_param("dis", $total_needed, $shop_id, $item);
            $update_stock->execute();
        }

        // --- 4. CREATE ORDER ---
        $stmt = $conn->prepare("
            INSERT INTO orders (shop_id, order_number, customer_id, customer_name, total_amount, status, order_source, payment_status, payment_method, category) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isisdsssss", $shop_id, $order_number, $customer_id, $customer_name, $total_amount, $status, $order_source, $payment_status, $payment_method, $category);
        $stmt->execute();
        $new_order_id = $conn->insert_id;

        // --- 5. CREATE ORDER ITEM ---
        $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, service_id, quantity, price) VALUES (?, ?, ?, ?)");
        $item_stmt->bind_param("iidd", $new_order_id, $service_id, $weight, $total_amount);
        $item_stmt->execute();

        $conn->commit();
        
        // Return a response that both Android and Web can understand
        echo json_encode([
            "status" => "success",
            "success" => true,
            "message" => "Order #$new_order_id placed successfully!",
            "order_id" => $new_order_id,
            "order_number" => $order_number
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "success" => false, "message" => $e->getMessage()]);
    }
}
?>