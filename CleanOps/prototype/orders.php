<?php
session_start();
require_once 'conn/conn.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get Shop ID
$shop_stmt = $conn->prepare("SELECT id, shop_name FROM shops WHERE user_id = ?");
$shop_stmt->bind_param("i", $user_id);
$shop_stmt->execute();
$shop = $shop_stmt->get_result()->fetch_assoc();
$shop_id = $shop['id'];

// 1. Fetch Stats
$stats_query = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status IN ('Processing', 'Ready') THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
    FROM orders WHERE shop_id = $shop_id
");
$stats = $stats_query->fetch_assoc();

// 2. Fetch Orders
$orders_query = $conn->query("
    SELECT o.id, o.customer_name, o.created_at, o.status, o.payment_status, o.total_amount,
            GROUP_CONCAT(s.service_name SEPARATOR ', ') as services
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN services s ON oi.service_id = s.id
    WHERE o.shop_id = $shop_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Reports | <?php echo htmlspecialchars($shop['shop_name']); ?></title>
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
    margin: 0;
    height: 100vh;
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
    top: 0;
    left: 0;

    display: flex;
    flex-direction: column;   /* stack vertically */
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
    padding: 40px ;
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
.header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-row h1 {
    font-size: 2rem;
    font-weight: 800;
}

.header-row p {
    font-size: 0.95rem;
    color: var(--text-muted);
    margin-top: 6px;
}

/* MODAL BACKDROP */
#addModal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 999;
}

/* MODAL BOX */
.modal-content {
    background: var(--bg-card);
    width: 420px;
    padding: 30px;
    border-radius: 14px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    animation: fadeIn 0.2s ease;
}

.modal-content h2 {
    margin-bottom: 20px;
    font-size: 1.3rem;
}

/* FORM */
.modal-content label {
    display: block;
    font-size: 0.8rem;
    margin-bottom: 6px;
    margin-top: 12px;
    color: var(--text-muted);
}

.modal-content input,
.modal-content select {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--bg-input);
    color: var(--text-main);
    outline: none;
}

.modal-content input:focus,
.modal-content select:focus {
    border-color: var(--primary);
}

/* Cancel Button Fix */
.modal-content button[type="button"] {
    background: #2a2a2a;
    color: white;
}

/* Animation */
@keyframes fadeIn {
    from { transform: translateY(-10px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* STATS */
.stats-grid {
    display: flex;
    gap: 22px;
    margin: 30px 0;
    flex-wrap: nowrap; /* prevents wrapping to next line */
}

.stat-card {
    background: var(--bg-card);
    padding: 22px;
    border-radius: 14px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    transition: 0.25s ease;
    flex: 1;
    min-width: 0;

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
        flex-wrap: wrap; /* allow stacking on small screens */
    }
    .stat-card {
        flex: 1 1 100%;
    }
}

.main-content {
    margin-left: var(--sidebar-width);
    flex: 1;
    padding: 40px;
    width: auto;
    height: auto;
    overflow: visible;
}

.filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
    gap: 12px;
}

.filter-actions {
    display: flex;
    justify-content: flex-end;
}
.btn-export {
    background: #000;
    color: #fff;
    border: none;
    padding: 12px 18px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: 0.2s ease;
}

.btn-export:hover {
    background: #111;
    transform: translateY(-2px);
}

/* SEARCH CONTAINER */
.search-container {
    position: relative;
    width: 320px;
}

/* SEARCH INPUT */
.search-container input {
    width: 100%;
    padding: 12px 18px 12px 45px;
    border-radius: 50px; /* circular */
    border: 1px solid var(--border);
    background: transparent;
    color: var(--text-main);
    outline: none;
    font-size: 0.9rem;
    transition: 0.25s ease;
}

/* Placeholder */
.search-container input::placeholder {
    color: var(--text-muted);
}

/* Focus Effect */
.search-container input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(46, 196, 182, 0.15);
}

/* SEARCH ICON */
.search-container i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 0.85rem;
}

/* ACTION SELECT */
.action-select {
    padding: 8px 14px;
    border-radius: 50px; /* circular container */
    border: 1px solid var(--border);
    background: transparent;
    color: var(--text-main);
    font-size: 0.8rem;
    cursor: pointer;
    outline: none;
    transition: 0.25s ease;
    appearance: none; /* removes default browser style */
    -webkit-appearance: none;
    -moz-appearance: none;
}

/* Dropdown list background */
.action-select option {
    background-color: var(--bg-card);
    color: var(--text-main);
}

/* When option is selected */
.action-select option:checked {
    background-color: var(--primary);
    color: #0F2027;
}

/* Improve dropdown appearance (for modern browsers) */
.action-select {
    background-color: var(--bg-card);
}

/* Hover */
.action-select:hover {
    border-color: var(--primary);
}

/* Focus */
.action-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(46, 196, 182, 0.15);
}

/* Optional: Add custom dropdown arrow */
.action-select {
    background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg fill='%239FBFC2' height='20' viewBox='0 0 20 20' width='20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M5 7l5 5 5-5H5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 14px;
    padding-right: 35px;
}

.badge {
    padding: 6px 12px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
    border: 1px solid transparent;
}

/* ORDER STATUS */
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

/* PAYMENT STATUS */
.pay-Paid {
    background: rgba(40, 167, 69, 0.12);
    color: #4ADE80;
    border: 1px solid rgba(40, 167, 69, 0.3);
    padding: 6px 12px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
}

.pay-Unpaid {
    background: rgba(220, 53, 69, 0.12);
    color: #FF6B6B;
    border: 1px solid rgba(220, 53, 69, 0.3);
    padding: 6px 12px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
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
            <h2><?php echo htmlspecialchars($shop['shop_name']); ?></h2>
        <p>Admin: <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></p>
        </div>
        <nav class="nav-list">
            <a href="admin_dashboard.php" class="nav-item"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="stock.php" class="nav-item"><i class="fa-solid fa-box"></i> Stock Management</a>
            <a href="revenue.php" class="nav-item"><i class="fa-solid fa-dollar-sign"></i> Revenue</a>
            <a href="reports.php" class="nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>
            <a href="orders.php" class="nav-item active"><i class="fa-solid fa-clipboard-list"></i> Order History</a>
            <a href="manage.php" class="nav-item"><i class="fa-solid fa-gear"></i> Manage</a>
            <a href="billing.php" class="nav-item"><i class="fa-solid fa-credit-card"></i> Billing</a>
            <a href="add_staff.php" class="nav-item"><i class="fa-solid fa-user-plus"></i> Add Staff</a>
        </nav>
        <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </aside>

    <main class="main">
        <header class="header header-row">
    <div>
        <h1>Order Reports</h1>
        <p>Detailed list and history of customer orders</p>
    </div>

    <div class="theme-toggle">
        <input type="checkbox" id="themeSwitch">
        <label for="themeSwitch" class="toggle-label"></label>
    </div>
</header>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="value"><?php echo $stats['total'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Completed</h3>
                <div class="value"><?php echo $stats['completed'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>In Progress</h3>
                <div class="value"><?php echo $stats['in_progress'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending</h3>
                <div class="value"><?php echo $stats['pending'] ?? 0; ?></div>
            </div>
        </div>

        <div class="filter-bar">
    <div class="search-container">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search by customer name or service...">
    </div>

    <div class="filter-actions">
        <button class="btn-export" onclick="window.print()">
            <i class="fa-solid fa-file-pdf"></i> Export PDF
        </button>
    </div>
</div>

        <div class="table-container">
            <table id="orderTable">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders_query->num_rows == 0): ?>
                        <tr><td colspan="8" style="text-align: center; padding: 48px; color: var(--text-muted);">No order records found.</td></tr>
                    <?php else: ?>
                        <?php while($order = $orders_query->fetch_assoc()): ?>
                            <tr>
                                <td><span style="color: var(--text-muted); font-weight: 500;">#ORD-<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></span></td>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['services'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                
                                <!-- ADDED ID TO STATUS BADGE -->
                                <td><span id="status-badge-<?php echo $order['id']; ?>" class="badge status-<?php echo $order['status']; ?>"><?php echo $order['status']; ?></span></td>
                                
                                <!-- ADDED ID TO PAYMENT BADGE -->
                                <td><span id="payment-badge-<?php echo $order['id']; ?>" class="pay-<?php echo $order['payment_status']; ?>"><?php echo $order['payment_status']; ?></span></td>
                                
                                <td style="font-weight: 700;">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <select class="action-select" onchange="updatePayment(<?php echo $order['id']; ?>, this.value, this)">
                                        <option value="">Payment...</option>
                                        <option value="Paid">Mark as Paid</option>
                                        <option value="Unpaid">Mark as Unpaid</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        const themeSwitch = document.getElementById("themeSwitch");

// Load saved theme
if (localStorage.getItem("theme") === "light") {
    document.body.classList.add("light-mode");
    themeSwitch.checked = true;
}

// Toggle theme
themeSwitch.addEventListener("change", function () {
    if (this.checked) {
        document.body.classList.add("light-mode");
        localStorage.setItem("theme", "light");
    } else {
        document.body.classList.remove("light-mode");
        localStorage.setItem("theme", "dark");
    }
});
        function searchTable() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let rows = document.getElementById("orderTable").getElementsByTagName("tr");
            for (let i = 1; i < rows.length; i++) {
                let text = rows[i].innerText.toLowerCase();
                rows[i].style.display = text.includes(input) ? "" : "none";
            }
        }

        function updateStatus(orderId, action) {
            if(!action) return;
            fetch('api/update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `order_id=${orderId}&action=${action}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert('Error updating order');
                }
            });
        }
        function openChatSupport() {
    window.location.href = "support.php";
    }
        

        function updatePayment(orderId, action, selectElement) {
    if(!action) return;
    
    fetch('api/update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=${orderId}&action=${action}`
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            // Update only the Payment Badge
            let paymentBadge = document.getElementById('payment-badge-' + orderId);
            if (paymentBadge) {
                paymentBadge.className = 'pay-' + action;         // Updates the color to red/green
                paymentBadge.innerText = action;                  // Updates the text to Paid/Unpaid
            }

            // Reset the dropdown back to "Payment..."
            selectElement.value = ""; 
            
        } else {
            alert('Error updating payment: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to connect to the server.');
    });
}
    </script>   
</body>
</html>