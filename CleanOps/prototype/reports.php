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

// 1. Fetch Total Orders & Revenue
$orders_query = $conn->query("SELECT COUNT(*) as total_orders, AVG(total_amount) as avg_order FROM orders WHERE shop_id = $shop_id");
$order_stats = $orders_query->fetch_assoc();
$total_orders = $order_stats['total_orders'] ?? 0;
$avg_order = $order_stats['avg_order'] ?? 0.00;

// 2. Fetch Active Customers
$customers_query = $conn->query("SELECT COUNT(DISTINCT customer_name) as active_customers FROM orders WHERE shop_id = $shop_id");
$active_customers = $customers_query->fetch_assoc()['active_customers'] ?? 0;

// 3. Fetch Total Services Offered
$services_query = $conn->query("SELECT COUNT(*) as total_services FROM services WHERE shop_id = $shop_id");
$total_services = $services_query->fetch_assoc()['total_services'] ?? 0;

// 4. Top Services by Revenue
$top_services = $conn->query("
    SELECT 
        s.service_name,
        s.category,
        COUNT(oi.id) as order_count,
        SUM(oi.price * oi.quantity) as total_revenue 
    FROM order_items oi 
    JOIN services s ON oi.service_id = s.id 
    JOIN orders o ON oi.order_id = o.id 
    WHERE o.shop_id = $shop_id 
    GROUP BY s.id 
    ORDER BY total_revenue DESC 
    LIMIT 5
");

// 5. Top Customers by Spending
$top_customers = $conn->query("
    SELECT 
        o.customer_name,
        COUNT(o.id) as total_orders,
        SUM(o.total_amount) as total_spent,
        (
            SELECT s.category
            FROM orders o2
            JOIN order_items oi2 ON o2.id = oi2.order_id
            JOIN services s ON oi2.service_id = s.id
            WHERE o2.customer_name = o.customer_name
            GROUP BY s.category
            ORDER BY COUNT(*) DESC
            LIMIT 1
        ) as top_category
    FROM orders o
    WHERE o.shop_id = $shop_id
    GROUP BY o.customer_name
    ORDER BY total_spent DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | <?php echo htmlspecialchars($shop['shop_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
    --sidebar-width: 260px;

    --bg-main: #0F2027;
    --bg-page: linear-gradient(135deg, #0F2027, #132f36);
    --bg-card: #1B2E35;
    --bg-input: #203A43;
    --bg-hover: rgba(46, 196, 182, 0.12);

    --primary: #2EC4B6;
    --primary-hover: #3DD6C6;
    --accent: #FF7A59;

    --text-main: #E0FBFC;
    --text-muted: #9FBFC2;
    --text-dark: #0F2027;

    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;

    --border: rgba(46, 196, 182, 0.15);
    --shadow: 0 8px 25px rgba(0,0,0,0.25);
    --shadow-strong: 0 10px 30px rgba(0,0,0,0.30);
}

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body {
    margin: 0;
    padding: 0;
    display: flex;
    min-height: 100vh;
    background: var(--bg-page);
    color: var(--text-main);
    font-family: 'Inter', sans-serif;
}

        /* Sidebar - Same as Order Reports */
        /* SIDEBAR (UNIFIED DESIGN) */
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

        /* Main Content */
        .main { margin-left: var(--sidebar-width); flex: 1; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; }
        .header h1 { font-size: 1.75rem; font-weight: 700; }
        .header p { color: var(--text-muted); font-size: 0.95rem; }

        .btn-download {
    background-color: #000;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 8px;
    }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px; margin-bottom: 32px; }
    .stat-card {
        background: var(--bg-card);
        padding: 22px;
        border-radius: 14px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        transition: 0.25s ease;
    }        

    .stat-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-strong);
    }

    

.icon-box {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    font-size: 1.2rem;
}

.bg-blue { background: rgba(59, 130, 246, 0.15); color: var(--info); }
.bg-green { background: rgba(16, 185, 129, 0.15); color: var(--success); }
.bg-orange { background: rgba(245, 158, 11, 0.15); color: var(--warning); }
.bg-purple { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; }

.stat-card h3 {
    font-size: 0.75rem;
    color: var(--primary);
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.stat-card .value {
    font-size: 1.9rem;
    font-weight: 700;
}
.stat-card .trend-label {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 6px;
}

        /* Table/Rankings Grid */
.rank-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-top: 20px;
}
.rank-card {
    background: var(--bg-card);
    padding: 22px;
    border-radius: 14px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    transition: 0.25s ease;
}
.rank-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-strong);
}

.rank-card h4 {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 18px;
}

.rank-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.rank-item:last-child {
    border-bottom: none;
}
.rank-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.rank-number {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(46, 196, 182, 0.15);
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
}
.rank-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-main);
}
.rank-sub {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 2px;
}
.rank-value {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text-main);
}
.rank-number.green {
    background: rgba(16, 185, 129, 0.15);
    color: var(--success);
}
.rank-number.blue {
    background: rgba(59, 130, 246, 0.15);
    color: var(--info);
}
.rank-number.orange {
    background: rgba(245, 158, 11, 0.15);
    color: var(--warning);
}

        .bg-blue-light { background: #eff6ff; color: #3b82f6; }
        .bg-green-light { background: #ecfdf5; color: #10b981; }
        .bg-purple-light { background: #f5f3ff; color: #8b5cf6; }
        .bg-orange-light { background: #fff7ed; color: #f97316; }

        @media print { .sidebar { display: none; } .main { margin-left: 0; padding: 0; } .btn-download { display: none; } }
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
    margin-bottom: 0;
}
.theme-toggle input {
    display: none;
}

.toggle-label {
    width: 60px;
    height: 30px;
    background: #2EC4B6; /* force visible */
    display: block;
    border-radius: 50px;
    position: relative;
    cursor: pointer;
    transition: 0.3s;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
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
    font-size: 14px;
}

body.light-mode .toggle-label::after {
    content: "☀️";
    transform: translateX(30px);
}
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    gap: 20px;
}

.header-left h1 {
    font-size: 1.75rem;
    font-weight: 700;
}

.header-left p {
    color: var(--text-muted);
    font-size: 0.95rem;
    margin-top: 4px;
}

.header-right {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
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
            <a href="reports.php" class="nav-item active"><i class="fa-solid fa-chart-bar"></i> Reports</a>
            <a href="orders.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> Order History</a>
            <a href="manage.php" class="nav-item"><i class="fa-solid fa-gear"></i> Manage</a>
            <a href="billing.php" class="nav-item"><i class="fa-solid fa-credit-card"></i> Billing</a>
            <a href="add_staff.php" class="nav-item"><i class="fa-solid fa-user-plus"></i> Add Staff</a>
        </nav>
        <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </aside>

    <main class="main">
        <header class="header">
    <div class="header-left">
        <h1>Business Reports</h1>
        <p>Overview of your shop's overall performance</p>
    </div>

    <div class="header-right">
    <button class="btn-download" onclick="window.print()">
        <i class="fa-solid fa-file-pdf"></i> Print / Save PDF
    </button>

    <div class="theme-toggle">
        <input type="checkbox" id="themeSwitch">
        <label for="themeSwitch" class="toggle-label"></label>
    </div>
</div>
</header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon-box bg-blue-light"><i class="fa-solid fa-chart-line"></i></div>
                <h3>Total Orders</h3>
                <div class="value"><?php echo number_format($total_orders); ?></div>
                <div class="trend-none">Across all dates</div>
            </div>
            <div class="stat-card">
                <div class="icon-box bg-green-light"><i class="fa-solid fa-users"></i></div>
                <h3>Active Customers</h3>
                <div class="value"><?php echo number_format($active_customers); ?></div>
                <div class="trend-none">Unique client base</div>
            </div>
            <div class="stat-card">
                <div class="icon-box bg-purple-light"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <h3>Avg Order Value</h3>
                <div class="value">₱<?php echo number_format($avg_order, 2); ?></div>
                <div class="trend-none">Per transaction</div>
            </div>
            <div class="stat-card">
                <div class="icon-box bg-orange-light"><i class="fa-solid fa-layer-group"></i></div>
                <h3>Services Offered</h3>
                <div class="value"><?php echo number_format($total_services); ?></div>
                <div class="trend-none">Active menu items</div>
            </div>
        </div>

        <div class="rank-grid">
            <div class="rank-card">
                <h4>Top Services by Revenue</h4>
                <?php if ($top_services->num_rows == 0): ?>
                    <div style="color: var(--text-muted); font-size: 0.875rem; padding: 12px 0;">No sales data available yet.</div>
                <?php else: ?>
                    <?php $rank = 1; while($service = $top_services->fetch_assoc()): ?>
                    <div class="rank-item">
                        <div class="rank-info">
                            <div class="rank-number"><?php echo $rank++; ?></div>
                            <div>
                                <div class="rank-name"><?php echo htmlspecialchars($service['service_name']); ?></div>
<div class="rank-sub">
    <?php echo $service['order_count']; ?> orders fulfilled<br>
    <span style="font-size: 0.7rem; color: var(--text-muted);">
        Category: <?php echo htmlspecialchars($service['category'] ?? 'Uncategorized'); ?>
    </span>
</div>                            </div>
                        </div>
                        <div class="rank-value">₱<?php echo number_format($service['total_revenue'], 2); ?></div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>

            <div class="rank-card">
                <h4>Top Customers</h4>
                <?php if ($top_customers->num_rows == 0): ?>
                    <div style="color: var(--text-muted); font-size: 0.875rem; padding: 12px 0;">No customer data found.</div>
                <?php else: ?>
                    <?php $rank = 1; while($customer = $top_customers->fetch_assoc()): ?>
                    <div class="rank-item">
                        <div class="rank-info">
                            <div class="rank-number" style="background: #ecfdf5; color: #10b981;"><?php echo $rank++; ?></div>
                            <div>
                                <div class="rank-name"><?php echo htmlspecialchars($customer['customer_name']); ?></div>
                    <div class="rank-sub">
                        <span style="font-size: 0.7rem; color: var(--text-muted);">
                            Category: <?php echo htmlspecialchars($customer['top_category'] ?? 'Uncategorized'); ?>
                        </span>
                    </div>
                            </div>
                        </div>
                        <div class="rank-value">₱<?php echo number_format($customer['total_spent'], 2); ?></div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        const toggle = document.getElementById("themeSwitch");

// load saved theme
if (localStorage.getItem("theme") === "light") {
    document.body.classList.add("light-mode");
    if (toggle) toggle.checked = true;
}

if (toggle) {
    toggle.addEventListener("change", function () {
        document.body.classList.toggle("light-mode", this.checked);
        localStorage.setItem("theme", this.checked ? "light" : "dark");
    });
}
function openChatSupport() {
    window.location.href = "support.php";
}

</script>
</body>
</html>