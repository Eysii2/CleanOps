<?php
session_start();
require_once '../conn/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

$current_shop_id = $_SESSION['shop_id'] ?? 0;

// 1. FETCH CUSTOMER LIST (With Total Orders and Last Order Date like the picture)
$customers_query = $conn->prepare("
    SELECT c.id, c.fullname, c.phone, c.address, 
           COUNT(o.id) as total_orders, 
           MAX(o.created_at) as last_order
    FROM customers c
    JOIN orders o ON c.id = o.customer_id
    WHERE o.shop_id = ? 
    GROUP BY c.id
    ORDER BY c.fullname ASC
");
$customers_query->bind_param("i", $current_shop_id);
$customers_query->execute();
$customers_result = $customers_query->get_result();

// 2. FETCH SPECIFIC CUSTOMER DETAILS & SUMMARY
$selected_customer = null;
$stats = ['total' => 0, 'completed' => 0, 'progress' => 0, 'cancelled' => 0];

if (isset($_GET['customer_id'])) {
    $customer_id = intval($_GET['customer_id']);
    
    // FIXED: Removed the extra 'i' and $current_shop_id since the SQL only has one '?'
    $details_query = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $details_query->bind_param("i", $customer_id);
    $details_query->execute();
    $selected_customer = $details_query->get_result()->fetch_assoc();

    if ($selected_customer) {
        // Fetch Order Summary Stats for the boxes in the picture
        $stats_query = $conn->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status NOT IN ('Completed', 'Cancelled') THEN 1 ELSE 0 END) as progress
            FROM orders 
            WHERE customer_id = ? AND shop_id = ?
        ");
        $stats_query->bind_param("ii", $customer_id, $current_shop_id);
        $stats_query->execute();
        $stats = $stats_query->get_result()->fetch_assoc();
    }
}
?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>Customer Details | CleanOps</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">



<style>

<?php /* === REUSE YOUR DASHBOARD ROOT COLORS === */ ?>

:root {

    --sidebar-width: 260px;

    --bg-main: #0F2027;

    --bg-card: #1B2E35;

    --bg-input: #203A43;

    --primary: #2EC4B6;

    --accent: #FF7A59;

    --text-main: #E0FBFC;

    --text-muted: #9FBFC2;

    --border: rgba(46,196,182,0.15);

    --shadow: 0 8px 25px rgba(0,0,0,0.25);

}



* { margin:0; padding:0; box-sizing:border-box; }

body {

    display:flex;

    font-family:'Inter',sans-serif;

    background:linear-gradient(135deg,#0F2027,#132f36);

    color:var(--text-main);

}

/* Stats Grid for the Right Panel */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-top: 20px;
}
.stat-box {
    background: var(--bg-input);
    padding: 15px;
    border-radius: 10px;
    text-align: center;
    border: 1px solid var(--border);
}
.stat-box h4 { font-size: 1.2rem; margin-bottom: 5px; }
.stat-box span { font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; }

/* Avatar Circle */
.avatar-large {
    width: 80px;
    height: 80px;
    background: var(--primary);
    color: #0F2027;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 15px;
}

/* Status Pill */
.badge-active {
    background: rgba(46,196,182,0.2);
    color: var(--primary);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    margin-left: 10px;
}


/* ================= SIDEBAR ================= */

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
.nav-list {
    list-style: none;
    flex-grow: 1;
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
    letter-spacing: 0.5px;
}

.brand p {
    font-size: 0.75rem;
    color: var(--text-muted);
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
    justify-content: space-between;
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

.nav-item:hover { background:rgba(46,196,182,0.12); }

.nav-item.active {

    background:var(--primary);

    color:#0F2027;

    font-weight:600;

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

.sub-item:hover,
.sub-item.active {
    color: var(--primary);
}

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
}



/* ================= MAIN ================= */

.main {

    margin-left:var(--sidebar-width);

    padding:40px;

    flex:1;

}



.header {

    margin-bottom:30px;

}



.layout {

    display:grid;

    grid-template-columns: 1.3fr 1fr;

    gap:25px;

}



/* CUSTOMER LIST */

.card {

    background:var(--bg-card);

    border-radius:14px;

    border:1px solid var(--border);

    box-shadow:var(--shadow);

    overflow:hidden;

}



.card-header {

    padding:18px 20px;

    background:var(--bg-input);

    font-weight:600;

    color:var(--primary);

}



.customer-row {

    padding:15px 20px;

    border-bottom:1px solid var(--border);

    display:flex;

    justify-content:space-between;

    align-items:center;

    transition:0.2s;

}



.customer-row:hover {

    background:rgba(46,196,182,0.07);

}



.customer-info span {

    display:block;

    font-size:0.82rem;

    color:var(--text-muted);

}



.customer-row a {

    color:var(--primary);

    text-decoration:none;

    font-weight:500;

}



/* DETAILS PANEL */

.details {

    padding:20px;

}



.details h3 {

    margin-bottom:18px;

    color:var(--primary);

}



.detail-item {

    margin-bottom:14px;

}



.detail-item label {

    font-size:0.75rem;

    color:var(--text-muted);

    display:block;

}



.detail-item span {

    font-size:0.9rem;

}



.empty-state {

    padding:30px;

    text-align:center;

    color:var(--text-muted);

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
    position: fixed;
    top: 20px;
    right: 25px;
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
<!-- THEME TOGGLE -->
<div class="theme-toggle">
    <input type="checkbox" id="themeSwitch">
    <label for="themeSwitch" class="toggle-label"></label>
</div>



</style>

</head>

<body>



<aside class="sidebar">
    <a href="staff_dashboard.php" style="text-decoration: none; color: inherit;">
        <div class="brand">
            <h2>CleanOps</h2>
            <p>Staff Member</p>
        </div>
    </a>

    <nav class="nav-list">
        

        <!-- Dashboard -->
        <div class="nav-item-wrapper">
            <a href="staff_dashboard.php" class="nav-item">
                <i class="fa-solid fa-house"></i>
                <span>Dashboard</span>
            </a>
        </div>
        <!-- Task -->
<div class="nav-item-wrapper">
    <a href="task.html" class="nav-item">
        <i class="fa-solid fa-list-check"></i>
        <span>Task Overview</span>
    </a>
</div>

        <!-- Orders (ACTIVE PARENT HERE) -->
        <div class="nav-item-wrapper active-parent">
            <a href="#" class="nav-item" style="pointer-events: none;">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Orders</span>
            </a>

            <div class="menu-toggle" id="orders-toggle">
                <i class="fa-solid fa-chevron-down rotate-icon active" id="arrow-icon"></i>
            </div>
        </div>

        <!-- Submenu (OPEN + View Details ACTIVE) -->
<div class="sub-menu open" id="orders-sub-menu">
    <a href="view_details.php" class="sub-item active">View Details</a>
    <a href="update_status.php" class="sub-item">Update Status</a>
    <a href="staff_markorders.php" class="sub-item">Mark as Done/Canceled</a>

    <!-- BILLING / POS (NOW INSIDE ORDERS) -->
    <a href="billing_pos.php" class="sub-item">
        Billing / POS
    </a>
</div>

        <!-- Delivery -->
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



<main class="main">
    <div class="header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1>Customer Details</h1>
        <p>View and manage customer information.</p>
    </div>

    <div class="theme-toggle">
        <input type="checkbox" id="themeSwitch">
        <label for="themeSwitch" class="toggle-label"></label>
    </div>
</div>

    <div class="layout">
        <!-- LEFT: CUSTOMER LIST -->
        <div class="card">
            <div class="card-header">Customer List</div>
            <?php while($row = $customers_result->fetch_assoc()): ?>
                <div class="customer-row">
                    <div class="customer-info">
                        <strong><?= htmlspecialchars($row['fullname']) ?></strong>
                        <span>Orders: <?= $row['total_orders'] ?> | Last: <?= date('M d, Y', strtotime($row['last_order'])) ?></span>
                    </div>
                    <a href="?customer_id=<?= $row['id'] ?>"><i class="fa-solid fa-chevron-right"></i></a>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- RIGHT: ENHANCED DETAILS (Like your image) -->
        <div class="card details">
            <?php if ($selected_customer): ?>
                <div class="avatar-large">
                    <?= strtoupper(substr($selected_customer['fullname'], 0, 2)) ?>
                </div>
                <h2><?= htmlspecialchars($selected_customer['fullname']) ?> <span class="badge-active">Active</span></h2>
                <p style="color:var(--text-muted); margin-bottom:20px;"><?= htmlspecialchars($selected_customer['email'] ?? 'No Email') ?></p>

                <div class="detail-item">
                    <label><i class="fa-solid fa-phone"></i> Contact Number</label>
                    <span><?= htmlspecialchars($selected_customer['phone']) ?></span>
                </div>
                <div class="detail-item">
                    <label><i class="fa-solid fa-location-dot"></i> Address</label>
                    <span><?= htmlspecialchars($selected_customer['address']) ?></span>
                </div>

                <!-- Order Summary Section from your image -->
                <h4 style="margin-top:30px; color:var(--primary);"><i class="fa-solid fa-box"></i> Order Summary</h4>
                <div class="stats-grid">
                    <div class="stat-box">
                        <h4><?= $stats['total'] ?></h4>
                        <span>Total</span>
                    </div>
                    <div class="stat-box">
                        <h4 style="color:var(--primary)"><?= $stats['completed'] ?></h4>
                        <span>Done</span>
                    </div>
                    <div class="stat-box">
                        <h4 style="color:var(--accent)"><?= $stats['progress'] ?></h4>
                        <span>In Progress</span>
                    </div>
                    <div class="stat-box">
                        <h4 style="color:#ff4d4d"><?= $stats['cancelled'] ?></h4>
                        <span>Cancelled</span>
                    </div>
                </div>

                <a href="staff_orders.php?customer_id=<?= $selected_customer['id'] ?>" class="nav-item active" style="margin-top:25px; justify-content:center;">
                    View All Orders
                </a>

            <?php else: ?>
                <div class="empty-state">Select a customer from the left to view their profile and order history.</div>
            <?php endif; ?>
        </div>
    </div>
</main>



<script>
document.addEventListener("DOMContentLoaded", function () {

    const ordersWrapper = document.querySelector(".nav-item-wrapper.active-parent");
    const ordersToggle = document.getElementById("orders-toggle");
    const subMenu = document.getElementById("orders-sub-menu");
    const arrowIcon = document.getElementById("arrow-icon");

    let ordersOpen = true;

    function openMenu() {
        subMenu.classList.add("open");
        arrowIcon.classList.add("active");
        ordersOpen = true;
    }

    function closeMenu() {
        subMenu.classList.remove("open");
        arrowIcon.classList.remove("active");
        ordersOpen = false;
    }

    ordersToggle.addEventListener("click", function (e) {
        e.stopPropagation();

        if (ordersOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    });
    const billingToggle = document.getElementById("billing-toggle");
const billingMenu = document.getElementById("billing-sub-menu");
const billingArrow = document.getElementById("billing-arrow");

let billingOpen = true;

billingToggle.addEventListener("click", function (e) {
    e.stopPropagation();

    billingOpen = !billingOpen;

    if (billingOpen) {
        billingMenu.classList.add("open");
        billingArrow.classList.add("active");
    } else {
        billingMenu.classList.remove("open");
        billingArrow.classList.remove("active");
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

    // Apply saved theme
    const saved = localStorage.getItem("theme");
    if (saved === "light") {
        document.body.classList.add("light-mode");
        if (switchEl) switchEl.checked = true;
    }

    // Add click event
    if (switchEl) {
        switchEl.addEventListener("change", toggleTheme);
    }
});
</script>



</body>

</html>