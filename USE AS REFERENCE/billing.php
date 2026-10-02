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

// 1. Fetch Billing Stats
$stats_query = $conn->query("
    SELECT 
        SUM(CASE WHEN payment_status = 'Paid' THEN total_amount ELSE 0 END) as total_collected,
        SUM(CASE WHEN payment_status = 'Paid' THEN 1 ELSE 0 END) as collected_count,
        SUM(CASE WHEN payment_status = 'Unpaid' THEN total_amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN payment_status = 'Unpaid' THEN 1 ELSE 0 END) as pending_count,
        COUNT(*) as total_transactions
    FROM orders WHERE shop_id = $shop_id
");
$stats = $stats_query->fetch_assoc();

// 2. Fetch Transactions (Orders)
$billing_query = $conn->query("
    SELECT id, customer_name, created_at, payment_method, payment_status, total_amount 
    FROM orders 
    WHERE shop_id = $shop_id 
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing | <?php echo htmlspecialchars($shop['shop_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
    --sidebar-width: 260px;

    /* Backgrounds (from revenue.php theme) */
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
    background: linear-gradient(135deg, #0F2027, #132f36);
    color: var(--text-main);
}

/* SIDEBAR (revenue style) */
.sidebar {
    width: var(--sidebar-width);
    background: var(--bg-card);
    border-right: 1px solid var(--border);
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
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
    margin-right: 10px;
    color: var(--primary);
}

.nav-item:hover {
    background: rgba(46, 196, 182, 0.12);
    color: var(--text-main);
}

/* ACTIVE SIDEBAR ITEM (MATCH ORDER.PHP EXACTLY) */
.nav-item.active {
    background: var(--primary);
    color: #0F2027;
    font-weight: 600;
}

/* ICON INSIDE ACTIVE ITEM */
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

/* MAIN CONTENT (IMPORTANT FIX) */
.main-content {
    margin-left: var(--sidebar-width);
    flex-grow: 1;
    width: 100%;
    padding: 40px;
}

/* HEADER */
.header-section h1 {
    font-size: 1.75rem;
    font-weight: 700;
}

.header-section p {
    color: var(--text-muted);
    font-size: 0.95rem;
}

/* STATS GRID (billing style kept, but themed) */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    box-shadow: var(--shadow);
}

.stat-card .icon-box {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}

.stat-card h3 {
    font-size: 0.85rem;
    color: var(--text-muted);
    text-transform: uppercase;
}

.stat-card .value {
    font-size: 1.75rem;
    font-weight: 700;
}

/* FILTER BAR (UNCHANGED STRUCTURE) */
.filter-bar {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
    align-items: center;
    background: var(--bg-card);
    padding: 16px;
    border-radius: 12px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
}

.search-wrapper {
    flex-grow: 1;
    position: relative;
}

.search-wrapper i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
}

.search-wrapper input {
    width: 100%;
    padding: 10px 10px 10px 40px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--bg-input);
    color: var(--text-main);
}

.filter-select {
    padding: 10px 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--bg-input);
    color: var(--text-main);
}

/* BUTTON */
.export-btn {
    padding: 10px 20px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--bg-card);
    color: var(--text-main);
    cursor: pointer;
}

/* =======================
   TABLE (MATCH order.php)
======================= */

.table-container {
    background: var(--bg-card);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
}

/* HEADER */
th {
    padding: 14px 20px;
    font-size: 0.7rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    border-bottom: 1px solid var(--border);
    background: var(--bg-input); /* IMPORTANT: ORDER.PHP uses header background */
}

/* CELLS */
td {
    padding: 16px 20px;
    font-size: 0.85rem;
    border-bottom: 1px solid var(--border);
    color: var(--text-main);
}

/* ROW HOVER (IMPORTANT MATCH) */
tr:hover {
    background: rgba(46, 196, 182, 0.06);
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 50px;
    color: var(--text-muted);
    font-size: 0.9rem;
}

/* ORDER STATUS */
.status-Pending {
    background: rgba(255, 165, 0, 0.12);
    color: #FFA500;
    border-color: rgba(255, 165, 0, 0.3);
}

/* METHOD BADGE (NEW - MATCH STYLE) */
method-Cash {
    background: rgba(46, 196, 182, 0.12);
    color: var(--primary);
    border-color: rgba(46, 196, 182, 0.3);
}

.method-GCash {
    background: rgba(0, 123, 255, 0.12);
    color: #4DA3FF;
    border-color: rgba(0, 123, 255, 0.3);
}

.method-Card {
    background: rgba(255, 122, 89, 0.12);
    color: #FF7A59;
    border-color: rgba(255, 122, 89, 0.3);
}

.action-select {
    padding: 6px 10px;
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 0.8rem;
    cursor: pointer;
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

.pay-Unpaid {
    background: rgba(220, 53, 69, 0.12);
    color: #FF6B6B;
    border-color: rgba(220, 53, 69, 0.3);
}

.badge {
    padding: 6px 12px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
    border: 1px solid transparent;
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
            <a href="orders.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> Order History</a>
            <a href="manage.php" class="nav-item"><i class="fa-solid fa-gear"></i> Manage</a>
            <a href="billing.php" class="nav-item active"><i class="fa-solid fa-credit-card"></i> Billing</a>
            <a href="add_staff.php" class="nav-item"><i class="fa-solid fa-user-plus"></i> Add Staff</a>
        </nav>
        
        <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </aside>

    <main class="main-content">
        <div class="header-section" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1>Billing & Transactions</h1>
        <p>Monitor collected revenue and manage pending payments.</p>
    </div>

    <!-- THEME TOGGLE -->
    <div class="theme-toggle">
        <input type="checkbox" id="themeSwitch" onchange="toggleTheme()">
        <label for="themeSwitch" class="toggle-label"></label>
    </div>
</div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon-box" style="background: #f0fdf4; color: #16a34a;"><i class="fas fa-coins"></i></div>
                <h3>Total Collected</h3>
                <div class="value">₱<?php echo number_format($stats['total_collected'] ?? 0, 2); ?></div>
                <div class="sub-info" style="color: #16a34a;"><?php echo $stats['collected_count'] ?? 0; ?> settled orders</div>
            </div>
            <div class="stat-card">
                <div class="icon-box" style="background: #fffbeb; color: #d97706;"><i class="fas fa-hourglass-half"></i></div>
                <h3>Pending Payments</h3>
                <div class="value">₱<?php echo number_format($stats['pending_amount'] ?? 0, 2); ?></div>
                <div class="sub-info" style="color: var(--text-muted);"><?php echo $stats['pending_count'] ?? 0; ?> awaiting payment</div>
            </div>
            <div class="stat-card">
                <div class="icon-box" style="background: #eff6ff; color: #3b82f6;"><i class="fas fa-receipt"></i></div>
                <h3>Total Transactions</h3>
                <div class="value"><?php echo number_format($stats['total_transactions'] ?? 0); ?></div>
                <div class="sub-info" style="color: var(--text-muted);">Total order volume</div>
            </div>
        </div>

        <div class="filter-bar">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search by customer or order ID...">
            </div>
            <select class="filter-select" id="statusFilter" onchange="filterTable()">
                <option value="all">All Status</option>
                <option value="Paid">Paid</option>
                <option value="Unpaid">Unpaid</option>
                <option value="Refunded">Refunded</option>
            </select>
            <button class="export-btn" onclick="window.print()">
                <i class="fas fa-print"></i> Print Records
            </button>
        </div>

        <div class="table-container">
            <table id="billingTable">
                <thead>
                    <tr>
                        <th>Transaction Ref</th>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date & Time</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Quick Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($billing_query->num_rows == 0): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 60px; color: var(--text-muted);">No transactions found.</td>
                        </tr>
                    <?php else: ?>
                        <?php while($txn = $billing_query->fetch_assoc()): 
                            $badge_class = strtolower($txn['payment_status']);
                            $order_id_formatted = str_pad($txn['id'], 4, '0', STR_PAD_LEFT);
                        ?>
                            <tr class="billing-row" data-status="<?php echo $txn['payment_status']; ?>">
                                <td style="color: var(--text-muted); font-size: 0.8rem;">#TXN-<?php echo $order_id_formatted; ?></td>
                                <td><a href="orders.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">#ORD-<?php echo $order_id_formatted; ?></a></td>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($txn['customer_name']); ?></td>
                                <td style="color: var(--text-muted);"><?php echo date('M d, Y g:i A', strtotime($txn['created_at'])); ?></td>
                                <td>
                                    <?php 
                                        $method = $txn['payment_method'];
                                        
                                        if (empty($method)) {
                                            $method_class = "status-Pending"; // EXACT same as orders.php
                                            $method_label = "Pending";
                                        } else {
                                            $method_class = "method-" . $method;
                                            $method_label = $method;
                                        }
                                    ?>
                                    <span class="badge <?php echo $method_class; ?>">
                                        <?php echo $method_label; ?>
                                    </span>
                                </td>                                <td>
                                    <span class="badge pay-<?php echo $txn['payment_status']; ?>">
                                        <?php echo $txn['payment_status']; ?>
                                    </span>
                                </td>                                
                                <td style="font-weight: 700;">₱<?php echo number_format($txn['total_amount'], 2); ?></td>
                                <td>
                                    <?php if($txn['payment_status'] === 'Unpaid'): ?>
                                        <select class="action-select" onchange="updatePayment(<?php echo $txn['id']; ?>, this.value)">
                                            <option value="">Update...</option>
                                            <option value="Cash">Cash Payment</option>
                                            <option value="GCash">GCash Transfer</option>
                                            <option value="Card">Debit/Credit Card</option>
                                        </select>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.75rem;">Settled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        function toggleTheme() {
    document.body.classList.toggle("light-mode");

    // optional: save preference
    if (document.body.classList.contains("light-mode")) {
        localStorage.setItem("theme", "light");
    } else {
        localStorage.setItem("theme", "dark");
    }
}

// load saved theme on page load
window.onload = function () {
    if (localStorage.getItem("theme") === "light") {
        document.body.classList.add("light-mode");
        document.getElementById("themeSwitch").checked = true;
    }
};
        function filterTable() {
            let searchInput = document.getElementById("searchInput").value.toLowerCase();
            let statusFilter = document.getElementById("statusFilter").value;
            let rows = document.querySelectorAll(".billing-row");

            rows.forEach(row => {
                let textMatch = row.innerText.toLowerCase().includes(searchInput);
                let statusMatch = (statusFilter === 'all') || (row.getAttribute('data-status') === statusFilter);
                row.style.display = (textMatch && statusMatch) ? "" : "none";
            });
        }

        function updatePayment(orderId, method) {
            if(!method) return;
            
            if(confirm(`Confirm payment settlement via ${method}?`)) {
                let formData = new FormData();
                formData.append('order_id', orderId);
                formData.append('action', 'Paid');
                formData.append('method', method);

                fetch('api/update_payment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) location.reload();
                    else alert('Failed to update payment.');
                });
            }
        }
        function openChatSupport() {
    window.location.href = "support.php";
    }
    </script>
</body>
</html>