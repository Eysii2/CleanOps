<?php
session_start();
require_once 'conn/conn.php';

// Set timezone to ensure CURDATE() matches your local time in the Philippines
date_default_timezone_set('Asia/Manila');

// --- DEV FALLBACK SYSTEM START ---
// Replaces the old redirect. If no session exists, it auto-logs you in as admin.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// --- DEV FALLBACK SYSTEM END ---

$user_id = $_SESSION['user_id'];
$display_shop_name = "Clean & Fresh Laundry";

// 1. Get Shop Info first so we have the $shop_id
$query = "SELECT id, shop_name FROM shops WHERE user_id = ?";
$stmt = $conn->prepare($query);

$stmt->bind_param("i", $user_id);
$stmt->execute();
$shop = $stmt->get_result()->fetch_assoc();

// Initialize variables
$total_orders = 0;
$total_revenue = 0.00;
$active_staff = 0;
$recent_orders = [];
$timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : 'total';

if ($shop) {
    $shop_id = $shop['id'];
    $_SESSION['shop_id'] = $shop_id; 
    $display_shop_name = $shop['shop_name'];

    // 2. Prepare the Date Filter string once
    $date_sql = "";
    if ($timeframe === 'daily') {
        $date_sql = " AND DATE(created_at) = CURDATE()";
    } elseif ($timeframe === 'weekly') {
        $date_sql = " AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($timeframe === 'monthly') {
        $date_sql = " AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
    } elseif ($timeframe === 'yearly') {
        $date_sql = " AND YEAR(created_at) = YEAR(CURDATE())";
    }

    // 3. Fetch Filtered Total Orders
    $count_query = "SELECT COUNT(*) as total FROM orders WHERE shop_id = ?" . $date_sql;
    $stmt_count = $conn->prepare($count_query);
    $stmt_count->bind_param("i", $shop_id);
    $stmt_count->execute();
    $total_orders = $stmt_count->get_result()->fetch_assoc()['total'];

    // 4. Fetch Filtered Total Revenue (Only from Completed orders)
    $rev_query = "SELECT SUM(total_amount) as total FROM orders WHERE shop_id = ? AND status = 'Completed'" . $date_sql;
    $stmt_rev = $conn->prepare($rev_query);
    $stmt_rev->bind_param("i", $shop_id);
    $stmt_rev->execute();
    $total_revenue = $stmt_rev->get_result()->fetch_assoc()['total'] ?? 0.00;

    // 5. Fetch Active Staff count (ONLY those logged in)
    $staff_query = "SELECT COUNT(*) as total FROM users WHERE shop_id = ? AND role = 'staff' AND is_online = 1";
    $stmt_staff = $conn->prepare($staff_query);
    $stmt_staff->bind_param("i", $shop_id);
    $stmt_staff->execute();
    $active_staff = $stmt_staff->get_result()->fetch_assoc()['total'];

    // 6. Fetch 5 Most Recent Orders (Usually no date filter here so you can see recent activity)
    $orders_query = "SELECT id, customer_name, category, status, total_amount FROM orders WHERE shop_id = ? ORDER BY id DESC LIMIT 5";
    $stmt_orders = $conn->prepare($orders_query);
    $stmt_orders->bind_param("i", $shop_id);
    $stmt_orders->execute();
    $recent_orders = $stmt_orders->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($display_shop_name); ?> - Dashboard</title>

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
    padding: 11px 12px;
    margin-bottom: 6px;
    border-radius: 8px;
    color: var(--text-muted);
    text-decoration: none;
    font-size: 0.88rem;
    transition: 0.25s ease;
}


.nav-item i {
    width: 22px;
    font-size: 0.95rem;
    margin-right: 10px;
    color: var(--primary);
}

/* Hover */
.nav-item:hover {
    background: rgba(46, 196, 182, 0.12);
    color: var(--text-main);
}

/* Active */
.nav-item.active {
    background: var(--primary);
    color: #0F2027;
    font-weight: 600;
    position: relative;
    overflow: hidden;
}

.nav-item.active::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    width: 4px;
    height: 100%;
    background: #ffffff;
}

.nav-item.active i {
    color: #0F2027;
}

/* LOGOUT BUTTON STYLE */
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

/* Hover Effect */
.logout-btn:hover {
    color: #ff9475;
    transform: translateX(4px);
}

/* MAIN */
.main {
    margin-left: var(--sidebar-width);
    flex: 1;
    padding: 40px;
    width: auto;       /* important fix */
    min-height: 100vh;
    box-sizing: border-box;
}

/* HEADER */
.header h1 {
    font-size: 1.9rem;
    font-weight: 800;
    letter-spacing: -0.5px;
}

.header p {
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
}

.stat-card:hover {
    transform: translateY(-6px);
}

/* Label */
.stat-card h3 {
    font-size: 0.75rem;
    color: var(--primary);
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Value */
.stat-card .value {
    font-size: 1.9rem;
    font-weight: 700;
}

/* TABLE */
.table-container {
    background: var(--bg-card);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

/* TABLE HEADER */
.table-header {
    padding: 18px 20px;
    background: var(--bg-input);
    border-bottom: 1px solid var(--border);
}

.table-header h3 {
    font-size: 0.95rem;
    color: var(--primary);
    font-weight: 600;
}

/* TABLE */
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

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 50px;
    color: var(--text-muted);
    font-size: 0.9rem;
}

/* RESPONSIVE */
@media (max-width: 900px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .main {
        padding: 20px;
            padding: 40px 50px;

    }
}

/* CHAT SUPPORT BUTTON */
.chat-support-btn {
    position: fixed;
    bottom: 25px;
    right: 25px;
    background: var(--primary);
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
}

.chat-support-btn i {
    font-size: 1rem;
}

.chat-support-btn:hover {
    background: var(--primary-hover);
    transform: translateY(-4px);
}
.stat-card {
    position: relative;
    overflow: hidden;
}

.stat-card::after {
    content: "";
    position: absolute;
    top: -100%;
    left: -100%;
    width: 200%;
    height: 200%;
    background: linear-gradient(
        120deg,
        transparent 40%,
        rgba(255,255,255,0.05),
        transparent 60%
    );
    transform: rotate(25deg);
    transition: 0.7s;
}

.stat-card:hover::after {
    top: 100%;
    left: 100%;
}
tbody tr {
    transition: 0.3s ease;
}

tbody tr:hover {
    background: rgba(46, 196, 182, 0.08);
    transform: scale(1.01);
}
.status-badge {
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.status-Completed {
    background: rgba(46, 196, 182, 0.15);
    color: #2EC4B6;
}

.status-Pending {
    background: rgba(255, 122, 89, 0.15);
    color: #FF7A59;
}

.status-Processing {
    background: rgba(255, 193, 7, 0.15);
    color: #FFC107;
}
.main {
    animation: fadeInPage 0.8s ease;
}

@keyframes fadeInPage {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.chat-support-btn {
    animation: pulseBtn 2.5s infinite;
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
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
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

<div class="theme-toggle">
    <input type="checkbox" id="themeSwitch">
    <label for="themeSwitch" class="toggle-label"></label>
</div>

    <button class="chat-support-btn" onclick="openChatSupport()">
    <i class="fa-solid fa-comment-dots"></i> Chats
</button>

<aside class="sidebar">
    <div class="brand">
        <h2><?php echo htmlspecialchars($display_shop_name); ?></h2>
        <p>Admin: <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></p>
    </div>

    <nav class="nav-list">
        <a href="admin_dashboard.php" class="nav-item active"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="stock.php" class="nav-item"><i class="fa-solid fa-box"></i> Stock Management</a>
        <a href="revenue.php" class="nav-item"><i class="fa-solid fa-dollar-sign"></i> Revenue</a>
        <a href="reports.php" class="nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>
        <a href="orders.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> Order History</a>
        <a href="manage.php" class="nav-item"><i class="fa-solid fa-gear"></i> Manage</a>
        <a href="billing.php" class="nav-item"><i class="fa-solid fa-credit-card"></i> Transaction</a>
        <a href="add_staff.php" class="nav-item"><i class="fa-solid fa-user-plus"></i> Add Staff</a>
    </nav>

    <a href="logout.php" class="logout-btn">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
</aside>

<main class="main">
    <div class="header">
    <div>
        <h1>Dashboard</h1>
        <p>Welcome back! Here is your shop overview.</p>
    </div>

    <div class="theme-toggle">
        <input type="checkbox" id="themeSwitch">
        <label for="themeSwitch" class="toggle-label"></label>
    </div>
</div>

    <div class="stats-grid"> 
        <div class="stat-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h3 style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">Total Orders</h3>
                
                <form method="GET" style="margin: 0;">
                    <select name="timeframe" onchange="this.form.submit()" 
                            style="padding: 2px 6px; border-radius: 6px; border: 1px solid var(--border); font-size: 0.75rem; background: #fff; cursor: pointer;">
                        <option value="total" <?= ($timeframe == 'total') ? 'selected' : '' ?>>All Time</option>
                        <option value="daily" <?= ($timeframe == 'daily') ? 'selected' : '' ?>>Today</option> <option value="weekly" <?= ($timeframe == 'weekly') ? 'selected' : '' ?>>This Week</option>
                        <option value="monthly" <?= ($timeframe == 'monthly') ? 'selected' : '' ?>>This Month</option>
                        <option value="yearly" <?= ($timeframe == 'yearly') ? 'selected' : '' ?>>This Year</option>
                    </select>
                </form>
            </div>
            
            <div class="value" style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);">
                <?= $total_orders ?>
            </div>
        </div>

        <div class="stat-card">
            <h3 style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                Total Revenue (<?= ucfirst($timeframe) ?>)
            </h3>
            <div class="value" style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin-top: 8px;">
                ₱<?= number_format($total_revenue, 2) ?>
            </div>
        </div>

        <div class="stat-card">
            <h3>Active Employees</h3>
            <div class="value"><?= $active_staff ?></div>
        </div>
    </div> <div class="table-container">
        <div class="table-header">
            <h3>Recent Orders</h3>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Amount</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($shop && isset($recent_orders) && $recent_orders->num_rows > 0): ?>
                    <?php while($row = $recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['customer_name']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td>
                                    <span class="status-badge status-<?= htmlspecialchars($row['status']) ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty-state" style="text-align: center; padding: 40px; color: #9ca3af;">
                            No recent orders for <?= htmlspecialchars($display_shop_name); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
const toggle = document.getElementById("themeSwitch");

toggle.addEventListener("change", function () {
    document.body.classList.toggle("light-mode", this.checked);
});
</script>

</body>
</html>