<?php
session_start();
require_once '../conn/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

$current_shop_id = $_SESSION['shop_id'];
$message = "";

// Handle Update Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    $payment_status = $_POST['payment_status'];

    $update_query = $conn->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ? AND shop_id = ?");
    $update_query->bind_param("ssii", $new_status, $payment_status, $order_id, $current_shop_id);
    
    if ($update_query->execute()) {
        $message = "<div class='alert success'>Order #$order_id updated successfully!</div>";
    } else {
        $message = "<div class='alert error'>Error updating order.</div>";
    }
}

// Fetch all active orders for this shop to populate a dropdown/list
$orders_query = $conn->prepare("SELECT id, customer_name, status, payment_status FROM orders WHERE shop_id = ? AND status != 'Completed' AND status != 'Canceled' ORDER BY id DESC");
$orders_query->bind_param("i", $current_shop_id);
$orders_query->execute();
$orders_result = $orders_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Status | CleanOps</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* INHERITED CSS VARIABLES FROM IMAGE/DASHBOARD */
        :root {
            --sidebar-width: 260px;
            --bg-main: #0F2027;
            --bg-card: #1B2E35;
            --bg-input: #203A43;
            --primary: #2EC4B6;
            --text-main: #E0FBFC;
            --text-muted: #9FBFC2;
            --border: rgba(46, 196, 182, 0.15);
            --shadow: 0 8px 25px rgba(0,0,0,0.25);
            --accent: #FF7A59;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--bg-main); color: var(--text-main); font-family: 'Inter', sans-serif; display: flex; }

        /* SIDEBAR STYLES (Kept for alignment) */
            .sidebar {
        width: var(--sidebar-width);
        background: var(--bg-card);
        border-right: 1px solid var(--border);
        height: 100vh;
        position: fixed;
        display: flex;
        flex-direction: column;
        padding: 24px 14px;
        }

            .brand {
        margin-bottom: 30px;
        padding-left: 14px;
        border-left: 4px solid var(--primary);
        }
            .brand h2 {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--primary);
        }
            .brand p {
        font-size: 0.75rem;
        color: var(--text-muted);
        }
            .nav-list {
        list-style: none;
        flex-grow: 1;
        }
        .nav-item-wrapper {
        display: flex;
        align-items: center;
        justify-content: space-between; /* KEY FIX */
        padding: 11px 12px;
        border-radius: 8px;
        transition: background 0.25s ease;
        cursor: pointer;
        gap: 10px;
        position: relative;
        }

        .nav-item {
        display: flex;
        align-items: center;
        color: var(--text-muted);
        text-decoration: none;
        font-size: 0.88rem;
        transition: 0.25s ease;
        cursor: pointer;
        gap: 10px;
        }

        .nav-item i {
        width: 22px;
        font-size: 0.95rem;
        color: var(--primary);
        }
            .nav-item-wrapper:hover {
        background: rgba(46, 196, 182, 0.12);
        }
            .nav-item-wrapper:has(.nav-item.active),
        .nav-item-wrapper.active-parent {
        background: var(--primary);
        }
            .nav-item-wrapper:has(.nav-item.active)::before,
        .nav-item-wrapper.active-parent::before {
        content: "";
        position: absolute;
        left: 0;
        top: 6px;
        bottom: 6px;
        width: 4px;
        background: #ffffff;
        border-radius: 4px;
        }
            .nav-item-wrapper:has(.nav-item.active) .nav-item,
        .nav-item-wrapper.active-parent .nav-item {
        color: #0F2027;
        }
            .nav-item-wrapper:has(.nav-item.active) i,
        .nav-item-wrapper.active-parent i {
        color: #0F2027;
        }
            .sub-menu {
        list-style: none;
        margin-left: 32px;
        margin-top: 6px;
        display: none;
        flex-direction: column;
        gap: 6px;
        }
            .sub-menu.open {
        display: flex;
        }
            .sub-item {
        font-size: 0.82rem;
        color: var(--text-muted);
        text-decoration: none;
        padding: 6px 0;
        transition: 0.2s ease;
        }

        .sub-item:hover {
        color: var(--primary);
        }

        /* ARROW */
        .rotate-icon {
        transition: 0.3s ease;
        color: var(--primary);
        }

        .rotate-icon.active {
        transform: rotate(180deg);
        }
    .logout-btn {
    border-top: 1px solid var(--border);
    padding-top: 16px;
    margin-top: 10px;
    color: var(--accent);
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: 0.25s ease;
}

.logout-btn i {
    font-size: 1rem;
}
            .logout-btn:hover {
        color: #ff9475;
        }

        .active-parent { background: var(--primary) !important; color: #0F2027 !important; border-radius: 8px; }
        .active-parent .nav-item { color: #0F2027; }

        /* FORM CONTENT */
        .main-container { margin-left: var(--sidebar-width); flex: 1; padding: 40px; }
        .header-section h1 { font-size: 1.9rem; margin-bottom: 10px; }
        
        .update-card {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 14px;
            border: 1px solid var(--border);
            max-width: 600px;
            box-shadow: var(--shadow);
        }

        .form-group { margin-bottom: 20px; }
        label { display: block; color: var(--primary); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px; }
        
        select, input {
            width: 100%;
            background: var(--bg-input);
            border: 1px solid var(--border);
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-family: inherit;
        }

        .btn-update {
            background: var(--primary);
            color: #0F2027;
            border: none;
            padding: 14px 20px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            transition: 0.3s;
        }

        .btn-update:hover { background: #3DD6C6; transform: translateY(-2px); }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .success { background: rgba(46, 196, 182, 0.2); color: var(--primary); border: 1px solid var(--primary); }
        /* Make ALL sub-items behave like hover when parent is active */
.nav-item-wrapper.active-parent ~ .sub-menu .sub-item,
.sub-menu.open .sub-item {
    transition: 0.2s ease;
}

/* ACTIVE PAGE (Update Status link) */
.sub-item.active,
.sub-menu.open .sub-item:nth-child(2) {
    color: var(--primary);
    font-weight: 600;
}

/* FORCE SAME COLOR AS HOVER WHEN OPEN */
.sub-menu.open .sub-item:hover {
    color: var(--primary);
}
:root {
    --bg: #0F2027;
    --card: #1b2e35;
    --text: #E0FBFC;
    --muted: #9FBFC2;
    --primary: #2EC4B6;
}

/* LIGHT MODE OVERRIDE */
body.light-mode {
    --bg: #f5f7fb;
    --card: #ffffff;
    --text: #111827;
    --muted: #6b7280;
    --primary: #2563eb;
}
body.light-mode .stat-card {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.06);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
}
body.light-mode .stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
}
body.light-mode .stat-label {
    color: #2563eb;
}
body.light-mode .stat-value {
    color: #111827;
}
/* =========================
   LIGHT MODE TABLE FIX
========================= */
body.light-mode .task-card {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.06);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
}

body.light-mode .task-header {
    background: #f3f4f6;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

body.light-mode table {
    background: #ffffff;
}

body.light-mode th {
    color: #6b7280;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

body.light-mode td {
    color: #111827;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

/* Hover effect for rows (optional but nice) */
body.light-mode tr:hover {
    background: rgba(37, 99, 235, 0.05);
}

/* Make "manage" link visible in light mode */
body.light-mode td a {
    color: #2563eb !important;
}

/* APPLY VARIABLES */
body {
    background: var(--bg);
    color: var(--text);
    transition: 0.3s ease;
}

/* Example reusable components */
.card {
    background: var(--card);
    color: var(--text);
    transition: 0.3s ease;
}

.text-muted {
    color: var(--muted);
}

.btn-primary {
    background: var(--primary);
    color: white;
}
.theme-toggle {
    position: relative;
}

.theme-toggle input {
    display: none;
}

.toggle-label {
    width: 60px;
    height: 30px;
    background: var(--primary);
    display: block;
    border-radius: 50px;
    position: relative;
    cursor: pointer;
    transition: 0.3s;
}

.toggle-label::after {
    content: "🌙";
    position: absolute;
    width: 26px;
    height: 26px;
    background: white;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.3s;
}

body.light-mode .toggle-label::after {
    content: "☀️";
    transform: translateX(30px);
}
    </style>
</head>
<body>
    

    <aside class="sidebar">
    <div class="brand">
        <h2>CleanOps</h2>
        <p>Staff Member</p>
    </div>

    <nav class="nav-list">
        
        <div class="nav-item-wrapper">
            <a href="staff_dashboard.php" class="nav-item">
                <i class="fa-solid fa-house"></i> 
                <span>Dashboard</span>
            </a>
        </div>
        <!-- TASK OVERVIEW -->
<div class="nav-item-wrapper">
    <a href="task.html" class="nav-item">
        <i class="fa-solid fa-list-check"></i>
        <span>Task Overview</span>
    </a>
</div>

        <!-- ORDERS -->
<div class="nav-item-wrapper active-parent" id="orders-wrapper">
    <a href="#" class="nav-item" style="pointer-events:none;">
        <i class="fa-solid fa-clipboard-list"></i>
        <span>Orders</span>
    </a>

    <div class="menu-toggle" id="orders-toggle">
        <i class="fa-solid fa-chevron-down rotate-icon active" id="arrow-icon"></i>
    </div>
</div>

<!-- SUBMENU -->
<div class="sub-menu open" id="orders-sub-menu">
    <a href="view_details.php" class="sub-item">View Details</a>
    <a href="update_status.php" class="sub-item active">Update Status</a>
    <a href="staff_markorders.php" class="sub-item">Mark as Done/Canceled</a>
    <a href="billing_pos.php" class="sub-item">Billing / POS</a>
</div>

        <div class="nav-item-wrapper">
            <a href="staff_delivery.php" class="nav-item">
                <i class="fa-solid fa-truck-fast"></i> 
                <span>Delivery/Pickup</span>
            </a>
        </div>

    </nav>

    <a href="../logout.php" class="logout-btn">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
</aside>

    <main class="main-container">
        
        <header class="header-section" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
    <div>
        <h1>Update Order Status</h1>
        <p class="text-muted">Modify customer order progress and payment records</p>
    </div>

    <div class="theme-toggle">
        <input type="checkbox" id="themeSwitch">
        <label for="themeSwitch" class="toggle-label"></label>
    </div>
</header>

        <?= $message ?>

        <div class="update-card">
            <form method="POST">
                <!-- Select Order -->
                <div class="form-group">
                    <label>Select Active Order</label>
                    <select name="order_id" required>
                        <option value="">-- Choose Order ID --</option>
                        <?php while($row = $orders_result->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>">
                                #<?= $row['id'] ?> - <?= htmlspecialchars($row['customer_name'] ?? 'Unknown') ?> (Current: <?= $row['status'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Update Progress Status -->
                <div class="form-group">
                    <label>Order Progress Status</label>
                    <select name="status">
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Ready">Ready for Pickup/Delivery</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>

                <!-- Update Payment Method/Status -->
                <div class="form-group">
                    <label>Payment Method & Status</label>
                    <select name="payment_status">
                        <option value="Unpaid">Unpaid</option>
                        <option value="Paid - Cash">Paid - Cash</option>
                        <option value="Paid - GCash">Paid - GCash</option>
                    </select>
                </div>

                <button type="submit" name="update_order" class="btn-update">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </form>
        </div>
    </main>
<script>
document.addEventListener("DOMContentLoaded", function () {

    const ordersWrapper = document.getElementById("orders-wrapper");
    const subMenu = document.getElementById("orders-sub-menu");
    const arrowIcon = document.getElementById("arrow-icon");

    let isOpen = true;

    // force correct initial state
    subMenu.classList.add("open");
    arrowIcon.classList.add("active");
    ordersWrapper.classList.add("active-parent");

    ordersWrapper.addEventListener("click", function (e) {

        if (e.target.closest(".sub-menu")) return;

        isOpen = !isOpen;

        if (isOpen) {
            subMenu.classList.add("open");
            arrowIcon.classList.add("active");
            ordersWrapper.classList.add("active-parent");
        } else {
            subMenu.classList.remove("open");
            arrowIcon.classList.remove("active");
            ordersWrapper.classList.remove("active-parent");
        }
    });

});
function setTheme(mode) {
    if (mode === "light") {
        document.body.classList.add("light-mode");
        localStorage.setItem("theme", "light");
    } else {
        document.body.classList.remove("light-mode");
        localStorage.setItem("theme", "dark");
    }
}

function toggleTheme() {
    const isLight = document.body.classList.contains("light-mode");
    setTheme(isLight ? "dark" : "light");
}

document.addEventListener("DOMContentLoaded", function () {

    const switchEl = document.getElementById("themeSwitch");

    // Load saved theme
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme === "light") {
        document.body.classList.add("light-mode");
        if (switchEl) switchEl.checked = true;
    }

    if (switchEl) {
        switchEl.addEventListener("change", toggleTheme);
    }
});
</script>
</body>
</html>