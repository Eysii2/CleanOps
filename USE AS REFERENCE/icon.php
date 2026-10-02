<?php
// Connect to the database
require_once 'conn/conn.php'; 

$message = "";

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["shop_logo"])) {
    
    $shop_id = intval($_POST["shop_id"]); // Which shop is uploading this?
    $target_dir = "images/";
    
    // Create the images folder if it doesn't exist yet
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Create a safe, unique filename (e.g., "shop_17_logo.png")
    $filename = "shop_" . $shop_id . "_" . basename($_FILES["shop_logo"]["name"]);
    $target_file = $target_dir . $filename;

    // Try to move the uploaded file into the images folder
    if (move_uploaded_file($_FILES["shop_logo"]["tmp_name"], $target_file)) {
        
        // Tell the database about the new icon!
        $sql = "UPDATE shops SET icon_name = '$filename' WHERE id = $shop_id";
        
        if ($conn->query($sql) === TRUE) {
            $message = "<div style='color: green; font-weight: bold;'>✅ Logo uploaded successfully! The app will now show this image.</div>";
        } else {
            $message = "<div style='color: red;'>❌ Database Error: " . $conn->error . "</div>";
        }
    } else {
        $message = "<div style='color: red;'>❌ Failed to move the uploaded file. Check folder permissions.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>settings</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 40px; }
        .admin-card { background: white; padding: 30px; border-radius: 10px; max-width: 400px; margin: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        input, select, button { width: 100%; margin-top: 10px; margin-bottom: 20px; padding: 10px; box-sizing: border-box; }
        button { background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>

    <div class="admin-card">
        <h2>Shop Profile Settings</h2>
        <p>Upload a new logo for your shop.</p>
        
        <?php if($message != "") echo $message; ?>

        <form action="admin.php" method="POST" enctype="multipart/form-data">
            
            <label for="shop_id"><b>Select Your Shop:</b></label>
            <select name="shop_id" id="shop_id" required>
                <option value="17">Washing Machine (ID: 17)</option>
            </select>

            <label for="shop_logo"><b>Choose Logo Image:</b></label>
            <input type="file" name="shop_logo" id="shop_logo" accept="image/*" required>

            <button type="submit">Upload Image</button>
        </form>
    </div>

</body>
</html>