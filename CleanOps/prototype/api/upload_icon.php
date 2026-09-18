<?php
header('Content-Type: application/json');
require_once '../conn/conn.php';

// 1. Check if an image and a shop_id were actually sent
if (isset($_FILES['image']) && isset($_POST['shop_id'])) {
    
    $shop_id = intval($_POST['shop_id']);
    $target_dir = "../images/";
    
    // Create a unique name so shops don't overwrite each other's files
    // Example: "shop_17_logo.png"
    $filename = "shop_" . $shop_id . "_" . basename($_FILES["image"]["name"]);
    $target_file = $target_dir . $filename;

    // 2. Move the file from temporary storage to the images folder
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        
        // 3. Update the database!
        $sql = "UPDATE shops SET icon_name = '$filename' WHERE id = $shop_id";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "message" => "Icon updated perfectly!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database error."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to move the uploaded file."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing image or shop ID."]);
}
?>