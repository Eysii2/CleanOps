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

// 1. Get Total Revenue (Only from 'Paid' orders)
$rev_query = $conn->query("SELECT SUM(total_amount) as total_revenue FROM orders WHERE shop_id = $shop_id AND payment_status = 'Paid'");
$total_revenue = $rev_query->fetch_assoc()['total_revenue'] ?? 0.00;

// 2. Get Total Expenses
$exp_query = $conn->query("SELECT SUM(amount) as total_expenses FROM expenses WHERE shop_id = $shop_id");
$total_expenses = $exp_query->fetch_assoc()['total_expenses'] ?? 0.00;

// 3. Calculate Profit and Margin
$net_profit = $total_revenue - $total_expenses;
$profit_margin = ($total_revenue > 0) ? ($net_profit / $total_revenue) * 100 : 0;

// 4. Monthly Breakdown
$monthly_rev = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month_year, SUM(total_amount) as rev 
    FROM orders 
    WHERE shop_id = $shop_id AND payment_status = 'Paid' 
    GROUP BY month_year
");
$revenue_data = [];
while($row = $monthly_rev->fetch_assoc()) {
    $revenue_data[$row['month_year']] = $row['rev'];
}

$monthly_exp = $conn->query("
    SELECT DATE_FORMAT(expense_date, '%Y-%m') as month_year, SUM(amount) as exp 
    FROM expenses 
    WHERE shop_id = $shop_id 
    GROUP BY month_year
");
$expense_data = [];
while($row = $monthly_exp->fetch_assoc()) {
    $expense_data[$row['month_year']] = $row['exp'];
}

$all_months = array_unique(array_merge(array_keys($revenue_data), array_keys($expense_data)));
rsort($all_months); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Dashboard | <?php echo htmlspecialchars($shop['shop_name']); ?></title>
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
    felx: 1;
    width: 100%;
    padding: 40px;
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
    display: flex;
    gap: 22px;
    margin: 30px 0;
    flex-wrap: nowrap;
}

.stat-card {
    flex: 1;
    background: var(--bg-card);
    padding: 22px;
    border-radius: 14px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    transition: 0.25s ease;
    min-width: 0;
}

.stat-card:hover {
    transform: translateY(-6px);
}

.stat-card h3 {
    font-size: 0.75rem;
    color: var(--primary);
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stat-card .value {
    font-size: 1.8rem;
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

    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-header h3 {
    font-size: 0.95rem;
    color: var(--primary);
    font-weight: 600;
}

.btn-action {
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

.btn-action:hover {
    background: #111;
    transform: translateY(-2px);
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
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 12px;
}
</style>
</head>
<div class="theme-toggle">
    <input type="checkbox" id="themeSwitch">
    <label for="themeSwitch" class="toggle-label"></label>
</div>
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
            <a href="revenue.php" class="nav-item active"><i class="fa-solid fa-dollar-sign"></i> Revenue</a>
            <a href="reports.php" class="nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>
            <a href="orders.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> Order History</a>
            <a href="manage.php" class="nav-item"><i class="fa-solid fa-gear"></i> Manage</a>
            <a href="billing.php" class="nav-item"><i class="fa-solid fa-credit-card"></i> Billing</a>
            <a href="add_staff.php" class="nav-item"><i class="fa-solid fa-user-plus"></i> Add Staff</a>
        </nav>
        <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </aside>

    <main class="main">
        <header class="header">
    <div>
        <h1>Revenue</h1>
        <p>Track your earnings and income summary</p>
    </div>

    <div class="header-right">
    <button class="btn-action" onclick="window.print()">
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
                <div class="icon-box bg-blue"><i class="fa-solid fa-sack-dollar"></i></div>
                <h3>Total Revenue</h3>
                <div class="value">₱<?php echo number_format($total_revenue, 2); ?></div>
                <p class="trend-label">From Paid Orders</p>
            </div>
            <div class="stat-card">
                <div class="icon-box bg-green"><i class="fa-solid fa-chart-line"></i></div>
                <h3>Net Profit</h3>
                <div class="value" style="color: <?php echo $net_profit < 0 ? '#ef4444' : 'inherit'; ?>;">
                    ₱<?php echo number_format($net_profit, 2); ?>
                </div>
                <p class="trend-label">Revenue minus Expenses</p>
            </div>
            <div class="stat-card">
                <div class="icon-box bg-orange"><i class="fa-solid fa-arrow-trend-down"></i></div>
                <h3>Total Expenses</h3>
                <div class="value">₱<?php echo number_format($total_expenses, 2); ?></div>
                <p class="trend-label">Restocks & Utilities</p>
            </div>
            <div class="stat-card">
                <div class="icon-box bg-purple"><i class="fa-solid fa-percent"></i></div>
                <h3>Profit Margin</h3>
                <div class="value"><?php echo number_format($profit_margin, 1); ?>%</div>
                <p class="trend-label">Business Efficiency</p>
            </div>
        </div>

        <section class="table-section">
            <div class="table-header">
                <h3>Monthly Breakdown</h3>
                <button class="btn-action" onclick="alert('Expense logging coming soon!')">+ Log Expense</button>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Revenue</th>
                        <th>Expenses</th>
                        <th>Profit</th>
                        <th>Margin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_months)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 48px; color: var(--text-muted); font-style: italic;">
                                No financial records found for this shop.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($all_months as $month): 
                            $m_rev = $revenue_data[$month] ?? 0;
                            $m_exp = $expense_data[$month] ?? 0;
                            $m_profit = $m_rev - $m_exp;
                            $m_margin = ($m_rev > 0) ? ($m_profit / $m_rev) * 100 : 0;
                            $display_date = date('F Y', strtotime($month . '-01'));
                        ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo $display_date; ?></td>
                                <td style="color: #10b981; font-weight: 600;">₱<?php echo number_format($m_rev, 2); ?></td>
                                <td style="color: #ef4444;">₱<?php echo number_format($m_exp, 2); ?></td>
                                <td style="font-weight: 700;">₱<?php echo number_format($m_profit, 2); ?></td>
                                <td><?php echo number_format($m_margin, 1); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
<script>
    const toggle = document.getElementById("themeSwitch");

// load saved theme
if (localStorage.getItem("theme") === "light") {
    document.body.classList.add("light-mode");
    if (toggle) toggle.checked = true;
}

// toggle switch
if (toggle) {
    toggle.addEventListener("change", function () {
        document.body.classList.toggle("light-mode", this.checked);
        localStorage.setItem("theme", this.checked ? "light" : "dark");
    });
}

function openChatSupport() {
    window.location.href = "support.php";
}
function openChatSupport() {
    window.location.href = "support.php";
}
</script>


</html>