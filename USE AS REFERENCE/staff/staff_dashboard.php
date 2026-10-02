<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); 
    exit();
}
if ($_SESSION['role'] !== 'staff') {
    echo "<script>alert('Access Denied.'); window.location.href='../index.html';</script>";
    exit();
}

// --- NEW CODE: FETCH DATA ---
require_once '../conn/conn.php';

// Get the shop_id we saved during login
$current_shop_id = $_SESSION['shop_id'];

// 1. Fetch Stats (Total & Pending) ONLY for this shop
$stats_query = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders, 
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders 
    FROM orders 
    WHERE shop_id = ?
");
$stats_query->bind_param("i", $current_shop_id);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();

// 2. Fetch the Task List (Latest 10 orders) ONLY for this shop
$orders_query = $conn->prepare("SELECT id, customer_name, category, status FROM orders WHERE shop_id = ? ORDER BY id DESC LIMIT 10");
$orders_query->bind_param("i", $current_shop_id);
$orders_query->execute();
$orders_result = $orders_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | CleanOps</title>
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
}

body {
    display: flex;
    background: linear-gradient(135deg, #0F2027, #132f36);
    color: var(--text-main);
    font-family: 'Inter', sans-serif;
}

/* SIDEBAR */
/* SIDEBAR (MATCH ADMIN DASHBOARD EXACTLY) */
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
    padding: 11px 12px;
    justify-content: space-between; /* KEY FIX */
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

/* Icon color */
.nav-item-wrapper:has(+ .sub-menu.open) .nav-item i {
    color: #0F2027;
}
/* HOVER — FULL WRAPPER */
.nav-item-wrapper:hover {
    background: rgba(46, 196, 182, 0.12);
}

/* ACTIVE — FULL WRAPPER */
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

/* Make text + icon dark when active */
.nav-item-wrapper:has(.nav-item.active) .nav-item,
.nav-item-wrapper.active-parent .nav-item {
    color: #0F2027;
}

.nav-item-wrapper:has(.nav-item.active) i,
.nav-item-wrapper.active-parent i {
    color: #0F2027;
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
    color: #ff9475;
    transform: translateX(4px);
}

/* MAIN */
.main-container {
    margin-left: var(--sidebar-width);
    flex: 1;
    padding: 40px;
    min-height: 100vh;
}
.menu-toggle {
    display: flex;
    align-items: center;
    cursor: pointer;
}
/* HEADER */
.header-section h1 {
    font-size: 1.9rem;
    font-weight: 800;
}

.header-section p {
    font-size: 0.9rem;
    color: var(--text-muted);
    margin-top: 6px;
}

/* STATS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
    margin: 30px 0;
}

.stat-card {
    background: var(--bg-card);
    padding: 22px;
    border-radius: 14px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    transition: 0.25s ease;
    min-height: 110px;   /* ensures same visual height */
}

.stat-card:hover {
    transform: translateY(-6px);
}

.stat-label {
    font-size: 0.75rem;
    color: var(--primary);
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stat-value {
    font-size: 1.9rem;
    font-weight: 700;
}

/* TABLE */
.task-card {
    background: var(--bg-card);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

.task-header {
    padding: 18px 20px;
    background: var(--bg-input);
    border-bottom: 1px solid var(--border);
}

.task-header h3 {
    font-size: 0.95rem;
    color: var(--primary);
    font-weight: 600;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    padding: 14px 20px;
    font-size: 0.7rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    border-bottom: 1px solid var(--border);
}

td {
    padding: 16px 20px;
    font-size: 0.85rem;
    border-bottom: 1px solid var(--border);
}

@media (max-width: 900px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .main-container {
        padding: 20px;
    }
}

/* SUB MENU DEFAULT (HIDDEN) */
.sub-menu {
    list-style: none;
    margin-left: 32px;
    margin-top: 6px;
    display: none;
    flex-direction: column;
    gap: 6px;
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


/* WHEN ACTIVE */
.sub-menu.open {
    display: flex;
}

/* Arrow animation */
.rotate-icon {
    transition: 0.3s ease;
    color: var(--primary);
}

.rotate-icon.active {
    transform: rotate(180deg);
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

/* ORDER STATUS BADGES */
.badge {
    padding: 6px 12px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
    border: 1px solid transparent;
}
.status-Pending {
    background: rgba(255, 165, 0, 0.12);
    color: #FFA500;
    border-color: rgba(255, 165, 0, 0.3);
}
.status-Processing {
    background: rgba(0, 123, 255, 0.12);
    color: #4DA3FF;
    border-color: rgba(0, 123, 255, 0.3);
}
.status-Ready {
    background: rgba(46, 196, 182, 0.12);
    color: var(--primary);
    border-color: rgba(46, 196, 182, 0.3);
}
.status-Completed {
    background: rgba(40, 167, 69, 0.12);
    color: #4ADE80;
    border-color: rgba(40, 167, 69, 0.3);
}
td {
    padding: 16px 20px;
    font-size: 0.85rem;
    border-bottom: 1px solid var(--border);
    text-align: center; /* Add this line */
}
/* =========================
   THEME SYSTEM (GLOBAL)
========================= */

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
    </style>
</head>
<body>
    <button class="chat-support-btn" onclick="openChatSupport()">
    <i class="fa-solid fa-comment-dots"></i> Chats
    </button>

    <aside class="sidebar">
        <div class="brand">
            <h2>CleanOps</h2>
            <p>Staff Member</p>
        </div>

        <div class="theme-toggle">
    <input type="checkbox" id="themeSwitch" onchange="toggleTheme()">
    <label for="themeSwitch" class="toggle-label"></label>
</div>

<nav class="nav-list">


            <div class="nav-item-wrapper">
                        <a href="staff_dashboard.php" class="nav-item active">
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


            
            
            <div class="nav-item-wrapper" id="orders-wrapper">

            <a class="nav-item" id="orders-link">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Orders</span>
            </a>

            <div class="menu-toggle" id="orders-toggle">
                <i class="fa-solid fa-chevron-down rotate-icon" id="arrow-icon"></i>
            </div>

        </div>

        <div class="sub-menu" id="orders-sub-menu">
        <a href="view_details.php" class="sub-item">View Details</a>
        <a href="update_status.php" class="sub-item">Update Status</a>
        <a href="staff_markorders.php" class="sub-item">Mark as Done/Canceled</a>
        <a href="create_neworder.html" class="sub-item">BILLING/POS</a>
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
        <header class="header-section">
            <h1>Staff Dashboard</h1>
            <p>Overview of your workload and assigned tasks</p>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-purple"><i class="fa-solid fa-clipboard-list"></i></div>
                <span class="stat-label">Total Assigned Orders</span>
                <div class="stat-value"><?= $stats['total_orders'] ?? 0 ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-orange"><i class="fa-solid fa-circle-exclamation"></i></div>
                <span class="stat-label">Pending Tasks</span>
                <div class="stat-value"><?= $stats['pending_orders'] ?? 0 ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="fa-solid fa-comment"></i></div>
                <span class="stat-label">Active Chats</span>
                <div class="stat-value">--</div>
            </div>
        </div>

        

        <div class="task-card">
            <div class="task-header">
                <h3>Current Task List</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Service Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders_result->num_rows > 0): ?>
                        <?php while($row = $orders_result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td>
                                    <span class="badge status-<?= $row['status'] ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="staff_order.php?id=<?= $row['id'] ?>" style="color: var(--primary); text-decoration: none; font-weight: 500;">manage</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No current tasks to display.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

<script>
document.addEventListener("DOMContentLoaded", function () {

    // =========================
    // ORDERS DROPDOWN MENU
    // =========================
    const ordersWrapper = document.getElementById("orders-wrapper");
    const ordersLink = document.getElementById("orders-link");
    const subMenu = document.getElementById("orders-sub-menu");
    const arrowIcon = document.getElementById("arrow-icon");

    if (ordersWrapper) {
        ordersWrapper.addEventListener("click", function () {
            const isOpen = subMenu.classList.contains("open");

            if (isOpen) {
                subMenu.classList.remove("open");
                arrowIcon.classList.remove("active");
                ordersLink.classList.remove("active");
                ordersWrapper.classList.remove("active-parent");
            } else {
                subMenu.classList.add("open");
                arrowIcon.classList.add("active");
                ordersLink.classList.add("active");
                ordersWrapper.classList.add("active-parent");
            }
        });
    }

    // =========================
    // LOAD SAVED THEME
    // =========================
    const savedTheme = localStorage.getItem("theme");
    const themeSwitch = document.getElementById("themeSwitch");

    if (savedTheme === "light") {
        document.body.classList.add("light-mode");
        if (themeSwitch) themeSwitch.checked = true;
    }

});

// =========================
// THEME FUNCTIONS (GLOBAL)
// =========================

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

// =========================
// CHAT SUPPORT BUTTON
// =========================
function openChatSupport() {
    window.location.href = "support.php";
}
</script>
</body>
</html>