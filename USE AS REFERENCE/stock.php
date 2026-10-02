<?php
session_start();
require_once 'conn/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$shop_query = $conn->prepare("SELECT id, shop_name FROM shops WHERE user_id = ?");
$shop_query->bind_param("i", $user_id);
$shop_query->execute();
$shop = $shop_query->get_result()->fetch_assoc();
$shop_id = $shop['id'];

// Fetch real inventory
$inventory = $conn->query("SELECT * FROM inventory WHERE shop_id = $shop_id ORDER BY item_name ASC");
$low_stock_list = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management - <?php echo htmlspecialchars($shop['shop_name']); ?></title>
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
    overflow: hidden; /* removes page scroll */
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

/* ACTION BUTTON STYLE */
.btn-action {
    background: #000;
    color: #fff;
    border: none;
    padding: 12px 18px;
    border-radius: 10px;

    font-weight: 600;
    font-size: 0.85rem;

    cursor: pointer;

    display: inline-flex;
    align-items: center;
    gap: 8px;

    transition: 0.2s ease;
}

.btn-action:hover {
    background: #111;
    transform: translateY(-2px);
}

.btn-action:active {
    transform: translateY(0);
}

.btn-action i {
    font-size: 0.9rem;
}
/* ADD BUTTON — copied from manage.php btn-primary */
.btn-add {
    background: #000;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;

    font-weight: 600;
    font-size: 0.875rem;

    cursor: pointer;

    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-add i {
    font-size: 0.9rem;
}

.btn-add:hover {
    opacity: 0.9;
}

.btn-add:active {
    transform: translateY(1px);
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
    margin-bottom: 40px;
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
}

.main-content {
    margin-left: var(--sidebar-width);
    flex: 1;
    padding: 40px;
    width: auto;
    height: auto;
    overflow: visible;
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
.header-actions {
    display: flex;
    align-items: center;
    gap: 15px;
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
            <a href="stock.php" class="nav-item active"><i class="fa-solid fa-box"></i> Stock Management </a>
            <a href="revenue.php" class="nav-item"><i class="fa-solid fa-dollar-sign"></i> Revenue</a>
            <a href="reports.php" class="nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>
            <a href="orders.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> Order History</a>
            <a href="manage.php" class="nav-item"><i class="fa-solid fa-gear"></i> Manage</a>
            <a href="billing.php" class="nav-item"><i class="fa-solid fa-credit-card"></i> Billing</a>
            <a href="add_staff.php" class="nav-item"><i class="fa-solid fa-user-plus"></i> Add Staff</a>
        </nav>
        <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </aside>

    <div class="main-content">
<div class="header-row">
    <div>
        <h1>Stock Management</h1>
        <p>Manage and track your laundry supplies</p>
    </div>

    <div class="header-actions">

        <!-- ADD BUTTON FIRST -->
        <button class="btn-add" onclick="toggleModal(true)">
            <i class="fa-solid fa-plus"></i> Add Stock Item
        </button>

        <!-- THEME TOGGLE AFTER (RIGHT SIDE) -->
        <div class="theme-toggle">
            <input type="checkbox" id="themeSwitch">
            <label for="themeSwitch" class="toggle-label"></label>
        </div>

    </div>
</div>

        <div class="stock-grid">
            <?php while($item = $inventory->fetch_assoc()): 
                $is_low = ($item['quantity'] <= $item['min_stock']);
                if($is_low) $low_stock_list[] = $item['item_name'];
            ?>
            <div class="stock-card <?php echo $is_low ? 'low-stock' : ''; ?>" id="card-<?php echo $item['id']; ?>">
                <p class="item-name">
                    <i class="fa-solid fa-box-open" style="color: var(--primary);"></i> 
                    <?php echo htmlspecialchars($item['item_name']); ?>
                </p>
                <div class="quantity-display">
                    <span class="qty-val" id="qty-<?php echo $item['id']; ?>" style="color: <?php echo $is_low ? 'var(--warning)' : 'inherit'; ?>">
                        <?php echo $item['quantity']; ?>
                    </span>
                    <span class="qty-unit"><?php echo $item['unit']; ?></span>
                </div>
                <div class="controls">
                    <button class="ctrl-btn" onclick="updateStock(<?php echo $item['id']; ?>, -1)"><i class="fa-solid fa-minus"></i></button>
                    <button class="btn-restock-action" onclick="updateStock(<?php echo $item['id']; ?>, 10)">Restock +10</button>
                    <button class="ctrl-btn" onclick="updateStock(<?php echo $item['id']; ?>, 1)"><i class="fa-solid fa-plus"></i></button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div id="addModal">
        <div class="modal-content">
            <h2>New Stock Item</h2>
            <form action="api/add_item.php" method="POST">
                <input type="hidden" name="shop_id" value="<?php echo $shop_id; ?>">
                
                <label>Item Name</label>
                <input type="text" name="item_name" placeholder="e.g. Fabric Softener" required>
                
                <label>Initial Quantity</label>
                <input type="number" name="quantity" value="0">
                
                <label>Unit</label>
                <select name="unit">
                    <option value="kg">Kilograms (kg)</option>
                    <option value="liters">Liters (L)</option>
                    <option value="pcs">Pieces (pcs)</option>
                </select>
                
                <label>Low Stock Alert Level</label>
                <input type="number" name="min_stock" value="10">
                
                <div style="display:flex; gap:12px; margin-top:10px;">
                    <button type="submit" class="btn-add" style="flex:1; justify-content: center;">Save Item</button>
                    <button type="button" onclick="toggleModal(false)" style="flex:1; background:#f3f4f6; border:none; border-radius:8px; cursor:pointer; font-weight:600;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
function toggleModal(show) {
    const modal = document.getElementById('addModal');
    modal.style.display = show ? 'flex' : 'none';
    document.body.style.overflow = show ? 'hidden' : 'auto';
}

function openChatSupport() {
    window.location.href = "support.php";
}

function updateStock(id, change) {
    fetch('api/update_stock.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&change=${change}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById(`qty-${id}`).innerText = data.new_qty;
            if (data.new_qty <= data.min_stock) location.reload();
        }
    });
}

/* =========================
   THEME TOGGLE (FIXED)
========================= */
const toggle = document.getElementById("themeSwitch");

// Load saved theme
if (localStorage.getItem("theme") === "light") {
    document.body.classList.add("light-mode");
    if (toggle) toggle.checked = true;
}

// Toggle event
if (toggle) {
    toggle.addEventListener("change", function () {
        document.body.classList.toggle("light-mode", this.checked);
        localStorage.setItem("theme", this.checked ? "light" : "dark");
    });
}
</script>
</body>
</html>