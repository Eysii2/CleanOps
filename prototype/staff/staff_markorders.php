<?php
session_start();
require_once '../conn/conn.php';
require_once '../api/send_notif.php';

// 1. Security Check & Shop ID Retrieval
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

$shop_id = $_SESSION['shop_id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['action'])) {
    $order_id = intval($_POST['order_id']);
    $action = $_POST['action'];

    if ($action === 'Staff_Tick') {
        // SECURITY CHECK
        $check_stmt = $conn->prepare("SELECT customer_confirmed FROM orders WHERE id = ?");
        $check_stmt->bind_param("i", $order_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result()->fetch_assoc();

        if ($result && $result['customer_confirmed'] == 1) {
            // Both sides are verified! Complete the order.
            $stmt = $conn->prepare("UPDATE orders SET staff_confirmed = 1, status = 'Completed' WHERE id = ? AND shop_id = ?");
            sendPushNotification("Your order #{$order_id} is officially complete. Thank you for choosing Clean & Fresh!");
            $stmt->bind_param("ii", $order_id, $shop_id);
            $stmt->execute();
            
            $message = "<div style='padding:16px; background:#dcfce7; color:#166534; border-radius:8px; margin-bottom:24px; font-weight: 500;'>
                            <i class='fa-solid fa-check-double'></i> Order #{$order_id} is officially Completed!
                        </div>";
        } else {
            // This catches someone trying to cheat by altering the HTML
            $message = "<div style='padding:16px; background:#fee2e2; color:#991b1b; border-radius:8px; margin-bottom:24px;'>
                            <i class='fa-solid fa-triangle-exclamation'></i> Error: The customer must confirm receipt first!
                        </div>";
        }
    } elseif ($action === 'Ready_For_Pickup') {
        // Notify the customer first
        $stmt = $conn->prepare("UPDATE orders SET status = 'Ready for Pickup' WHERE id = ? AND shop_id = ?");
        $stmt->bind_param("ii", $order_id, $shop_id);
        $stmt->execute();
        sendPushNotification("Great news! Order #{$order_id} is washed, folded, and Ready for Pickup.");
        
        $message = "<div style='padding:16px; background:#dcfce7; color:#166534; border-radius:8px; margin-bottom:24px; font-weight: 500;'>
                        <i class='fa-solid fa-bell'></i> Customer notified that Order #{$order_id} is ready!
                    </div>";
    } elseif ($action === 'Canceled') {
        // We need to keep the Cancel logic so the red Cancel button still works!
        $stmt = $conn->prepare("UPDATE orders SET status = 'Canceled' WHERE id = ? AND shop_id = ?");
        $stmt->bind_param("ii", $order_id, $shop_id);
        $stmt->execute();
        $message = "<div style='padding:16px; background:#fee2e2; color:#991b1b; border-radius:8px; margin-bottom:24px;'>Order #{$order_id} Canceled.</div>";
    }
}

// 3. Fetch Orders (Added staff_confirmed and customer_confirmed here!)
$fetch_stmt = $conn->prepare("SELECT id, customer_name, category, status, staff_confirmed, customer_confirmed FROM orders WHERE shop_id = ? AND status NOT IN ('Completed', 'Canceled') ORDER BY id ASC");
$fetch_stmt->bind_param("i", $shop_id);
$fetch_stmt->execute();
$orders_result = $fetch_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Orders | Staff Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
    --sidebar-width: 260px;

    /* Backgrounds */
    --bg-main: #0F2027;
    --bg-card: #1B2E35;
    --bg-input: #203A43;

    /* Accent */
    --primary: #2EC4B6;
    --primary-hover: #3DD6C6;
    --accent: #FF7A59;

    /* Text */
    --text-main: #E0FBFC;
    --text-muted: #9FBFC2;

    /* UI */
    --border: rgba(46, 196, 182, 0.15);
    --shadow: 0 8px 25px rgba(0,0,0,0.25);
}

/* RESET */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', sans-serif;
}

body {
    display: flex;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    transition: background 0.3s ease, color 0.3s ease;
}

/* SIDEBAR (MATCH STAFF DASHBOARD EXACTLY) */
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

/* BRAND */
.brand {
    margin-bottom: 30px;
    padding-left: 14px;
    border-left: 4px solid var(--primary);
}

.brand h2 {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--primary);
    letter-spacing: 0.5px;
}

.brand p {
    font-size: 0.75rem;
    color: var(--text-muted);
}

/* NAV */
.nav-list {
    list-style: none;
    flex-grow: 1;
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

.nav-item i {
    width: 22px;
    font-size: 0.95rem;
    color: var(--primary);
}

/* HOVER */
.nav-item-wrapper:hover {
    background: rgba(46, 196, 182, 0.12);
}

/* ACTIVE */
.nav-item-wrapper:has(.nav-item.active),
.nav-item-wrapper.active-parent {
    background: var(--primary);
}

/* Left white indicator bar */
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

/* Dark text when active */
.nav-item-wrapper:has(.nav-item.active) .nav-item,
.nav-item-wrapper.active-parent .nav-item {
    color: #0F2027;
}

.nav-item-wrapper:has(.nav-item.active) i,
.nav-item-wrapper.active-parent i {
    color: #0F2027;
}

/* SUB MENU */
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

.sub-item:hover,
.sub-item.active {
    color: var(--primary);
}

/* Arrow */
.menu-toggle {
    display: flex;
    align-items: center;
    cursor: pointer;
    margin-left: auto;
}

.rotate-icon {
    transition: 0.3s ease;
    color: var(--primary);
}

.rotate-icon.active {
    transform: rotate(180deg);
}

/* LOGOUT */
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
    transform: translateX(4px);
}

/* MAIN */
.main-container {
    margin-left: var(--sidebar-width);
    flex: 1;
    padding: 40px;
}

.header-section h1 {
    font-size: 1.9rem;
    font-weight: 800;
}

.header-section p {
    font-size: 0.9rem;
    color: var(--text-muted);
    margin-top: 6px;
}

/* INFO BANNER */
.info-banner {
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 32px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.875rem;
    color: var(--text-main);
}

/* TABLE CARD */
.task-card {
    background: var(--bg-card);
    border-radius: 14px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    overflow: hidden;
    margin-bottom: 24px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    padding: 16px 24px;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border);
}

td {
    padding: 16px 24px;
    font-size: 0.875rem;
    border-bottom: 1px solid var(--border);
}

/* BUTTONS */
.btn-done {
    background: var(--primary);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 600;
    transition: 0.2s;
}

.btn-done:hover {
    background: var(--primary-hover);
}

.btn-cancel {
    background: transparent;
    color: #ef4444;
    border: 1px solid #ef4444;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 600;
}

.btn-cancel:hover {
    background: rgba(239, 68, 68, 0.1);
}

/* STATUS BADGE */
.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.75rem;
    background: var(--bg-input);
    color: var(--text-main);
}

/* EMPTY STATE */
.empty-state-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 60px 24px;
    text-align: center;
    box-shadow: var(--shadow);
}
.chat-support-btn {
    position: fixed;
    bottom: 25px;
    right: 25px;
    background: #2EC4B6;
    color: #0F2027;
    border: none;
    border-radius: 50px;
    padding: 14px 18px;
    font-size: 0.9rem;
    font-weight: 600;
    box-shadow: 0 8px 25px rgba(0,0,0,0.35);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: 0.3s ease;
    z-index: 999;
    animation: pulseBtn 2.5s infinite;
}

.chat-support-btn i {
    font-size: 1rem;
}

.chat-support-btn:hover {
    background: #3DD6C6;
    transform: translateY(-4px);
}

@keyframes pulseBtn {
    0% { box-shadow: 0 0 0 0 rgba(46,196,182,0.5); }
    70% { box-shadow: 0 0 0 15px rgba(46,196,182,0); }
    100% { box-shadow: 0 0 0 0 rgba(46,196,182,0); }
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
    <button class="chat-support-btn" onclick="openChatSupport()">
    <i class="fa-solid fa-comment-dots"></i> Chats
    </button>
    <aside class="sidebar">
        <a href="staff_dashboard.php" style="text-decoration: none; color: inherit;">
            <div class="brand">
                <h2>CleanOps</h2>
                <p>Staff Member</p>
            </div>
        </a>

        <nav class="nav-list">
            <div class="nav-item-wrapper">
                <a href="staff_dashboard.php" class="nav-item">
                    <i class="fa-solid fa-house"></i> 
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="nav-item-wrapper">
            <a href="task.html" class="nav-item">
            <i class="fa-solid fa-list-check"></i>
            <span>Task Overview</span>
            </a>
            </div>
            
            <div class="nav-item-wrapper active-parent">
                <a href="#" class="nav-item" style="pointer-events: none;">
                    <i class="fa-solid fa-clipboard-list"></i> 
                    <span>Orders</span>
                </a>
                <div class="menu-toggle" id="orders-toggle">
                    <i class="fa-solid fa-chevron-down" id="arrow-icon"></i>
                </div>
            </div>
            
            <div class="sub-menu" id="orders-sub-menu">
    <a href="view_details.php" class="sub-item">View Details</a>
    <a href="update_status.php" class="sub-item">Update Status</a>
    <a href="staff_markorders.php" class="sub-item active">Mark as Done/Canceled</a>
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
        <h1>Mark Orders</h1>
        <p class="text-muted">Mark orders as done or canceled</p>
    </div>

    <!-- THEME TOGGLE -->
    <div class="theme-toggle">
        <input type="checkbox" id="themeSwitch">
        <label for="themeSwitch" class="toggle-label"></label>
    </div>
</header>

        <?= $message ?>

        <div class="info-banner">
            <i class="fa-solid fa-circle-info"></i>
            <span><strong>Note:</strong> Marking an order as done will complete the order and notify the customer. Canceling an order cannot be undone.</span>
        </div>

        <?php if ($orders_result->num_rows > 0): ?>
            <div class="task-card">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Service Type</th>
                            <th>Current Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $orders_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $row['id'] ?></strong></td>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td>
                                    <div style="font-size: 0.8rem; margin-bottom: 4px;">
                                        Staff: <?= ($row['staff_confirmed'] == 1) ? '✅' : '❌' ?> | 
                                        Customer: <?= ($row['customer_confirmed'] == 1) ? '✅' : '⏳' ?>
                                    </div>
                                    <?= htmlspecialchars($row['category']) ?>
                                </td>
                                <td>
                                    <span class="status-badge" style="background: #f3f4f6; color: #4b5563;">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status'] !== 'Ready for Pickup'): ?>
                                        <form method="POST" class="action-form">
                                            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="action" value="Ready_For_Pickup">
                                            <button type="submit" class="btn-done" style="background: #3b82f6;">
                                                <i class="fa-solid fa-bell"></i> Notify Ready
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (isset($row['customer_confirmed']) && $row['customer_confirmed'] == 0): ?>
                                        <button class="btn-done" style="background: #9ca3af; cursor: not-allowed; display: inline-block; margin-right: 8px;" disabled>
                                            <i class="fa-solid fa-hourglass-half"></i> Awaiting Customer
                                        </button>

                                    <?php elseif (isset($row['staff_confirmed']) && $row['staff_confirmed'] == 0): ?>
                                        <form method="POST" class="action-form">
                                            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="action" value="Staff_Tick">
                                            <button type="submit" class="btn-done"><i class="fa-solid fa-check-double"></i> Verify & Complete</button>
                                        </form>
                                        
                                    <?php else: ?>
                                        <button class="btn-done" style="background: #9ca3af; cursor: not-allowed; display: inline-block; margin-right: 8px;" disabled>
                                            <i class="fa-solid fa-check-double"></i> Verified
                                        </button>
                                    <?php endif; ?>

                                    <form method="POST" class="action-form" onsubmit="return confirm('WARNING: Are you sure you want to CANCEL Order #<?= $row['id'] ?>? This cannot be undone.');">
                                        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="action" value="Canceled">
                                        <button type="submit" class="btn-cancel"><i class="fa-solid fa-xmark"></i> Cancel</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state-card">
                <i class="fa-solid fa-clipboard-check"></i>
                <h3>No orders to process</h3>
                <p>Assigned orders requiring completion or cancellation will appear here once available.</p>
            </div>
        <?php endif; ?>
        
    </main>

    <script>
document.addEventListener("DOMContentLoaded", function () {

    const ordersWrapper = document.querySelector(".nav-item-wrapper.active-parent");
    const subMenu = document.getElementById("orders-sub-menu");

    let isOpen = true;

    function setMenu(state) {
        isOpen = state;
        subMenu.classList.toggle("open", isOpen);
    }

    setMenu(true);

    ordersWrapper.addEventListener("click", function (e) {
        if (e.target.closest(".sub-menu")) return;
        setMenu(!isOpen);
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

document.addEventListener("DOMContentLoaded", function () {

    const switchEl = document.getElementById("themeSwitch");

    // Load saved theme
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme === "light") {
        document.body.classList.add("light-mode");
        if (switchEl) switchEl.checked = true;
    }

    if (switchEl) {
        switchEl.addEventListener("change", function () {
            if (this.checked) {
                setTheme("light");
            } else {
                setTheme("dark");
            }
        });
    }
});
</script>

</body>
</html>