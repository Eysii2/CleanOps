<?php
session_start();
require_once 'conn/conn.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


// Get Shop details
$shop_res = $conn->query("SELECT id, shop_name FROM shops WHERE user_id = $user_id")->fetch_assoc();
$shop_id = $shop_res['id'];

// Fetch staff data
$staff_query = $conn->query("SELECT * FROM users WHERE shop_id = $shop_id AND role IN ('staff', 'supervisor') ORDER BY created_at DESC");
$total_staff = $staff_query->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Management | <?php echo htmlspecialchars($shop_res['shop_name']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
    --sidebar-width: 260px;

    --bg-main: #0F2027;
    --bg-card: #1B2E35;
    --bg-input: #203A43;

    --primary: #2EC4B6;
    --primary-hover: #3DD6C6;
    --accent: #FF7A59;

    --text-main: #E0FBFC;
    --text-muted: #9FBFC2;

    --border: rgba(46, 196, 182, 0.15);
    --shadow: 0 8px 25px rgba(0,0,0,0.25);
}

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
    flex-grow: 1;
    padding: 40px;
}

/* HEADER */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
}

.header h1 {
    font-size: 1.75rem;
    font-weight: 700;
}

.header p {
    color: var(--text-muted);
    font-size: 0.95rem;
}

/* BTN-BLACK — copied from manage.php btn-primary */
.btn-black {
    background: #000;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.875rem;

    display: flex;
    align-items: center;
    gap: 8px;
}

/* STATS (MATCH ORDERS.PHP STYLE) */
.stats-row {
    display: flex;
    gap: 22px;
    margin-bottom: 30px;
}

.stat-box {
    flex: 1;
    background: var(--bg-card);
    padding: 22px;
    border-radius: 14px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
}

.stat-box h3 {
    font-size: 0.75rem;
    color: var(--primary);
    text-transform: uppercase;
    margin-bottom: 10px;
}

.stat-box .val {
    font-size: 1.9rem;
    font-weight: 700;
}

/* STAFF GRID */
.staff-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 24px;
}

.staff-card {
    background: var(--bg-card);
    padding: 24px;
    border-radius: 14px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    position: relative;
}

.staff-card:hover {
    border-color: var(--primary);
}

.avatar {
    width: 48px;
    height: 48px;
    background: var(--bg-input);
    color: var(--primary);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    margin-right: 16px;
}

.card-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.role-badge {
    font-size: 0.7rem;
    background: rgba(46,196,182,0.12);
    color: var(--primary);
    padding: 4px 10px;
    border-radius: 6px;
}

.info-row {
    font-size: 0.9rem;
    color: var(--text-muted);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* TAGS */
.tag {
    font-size: 0.75rem;
    background: var(--bg-input);
    padding: 4px 10px;
    border-radius: 6px;
    border: 1px solid var(--border);
}

/* MODAL */
#addModal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: var(--bg-card);
    width: 450px;
    padding: 32px;
    border-radius: 16px;
    border: 1px solid var(--border);
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--bg-input);
    color: var(--text-main);
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

/* KEEP YOUR EXISTING JS + HTML UNCHANGED */
</style>
</head>
<body>
    <button class="chat-support-btn" onclick="openChatSupport()">
    <i class="fa-solid fa-comment-dots"></i> Chats
    </button>

    <aside class="sidebar">
        <div class="brand">
            <h2><?php echo htmlspecialchars($shop_res['shop_name']); ?></h2>
        <p>Admin: <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></p>
        </div>
        <nav class="nav-list">
            <a href="admin_dashboard.php" class="nav-item"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="stock.php" class="nav-item"><i class="fa-solid fa-box"></i> Stock Management</a>
            <a href="revenue.php" class="nav-item"><i class="fa-solid fa-dollar-sign"></i> Revenue</a>
            <a href="reports.php" class="nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>
            <a href="orders.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> Order History</a>
            <a href="manage.php" class="nav-item"><i class="fa-solid fa-gear"></i> Manage</a>
            <a href="billing.php" class="nav-item"><i class="fa-solid fa-credit-card"></i> Billing</a>
            <a href="add_staff.php" class="nav-item active"><i class="fa-solid fa-user-plus"></i> Add Staff</a>
        </nav>
        <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </aside>

    <main class="main">
        <div class="header">
    <div>
        <h1>Staff Management</h1>
        <p>Monitor your team and access controls</p>
    </div>

    <!-- THEME TOGGLE (ADD THIS) -->
    <div class="theme-toggle">
        <input type="checkbox" id="themeSwitch" onchange="toggleTheme()">
        <label for="themeSwitch" class="toggle-label"></label>
    </div>
</div>

        <div class="stats-row">
            <div class="stat-box">
                <h3>Total Staff</h3>
                <div class="val"><?php echo $total_staff; ?></div>
            </div>
            <div class="stat-box">
                <h3>Active Now</h3>
                <div class="val" style="color:#10b981;"><?php echo $total_staff; ?></div>
            </div>
            <div class="stat-box">
                <h3>System Access</h3>
                <div class="val">Full</div>
            </div>
        </div>

        <div class="staff-grid">
            <?php while($staff = $staff_query->fetch_assoc()): 
                // Better initial logic for single names
                $name_parts = explode(" ", trim($staff['username']));
                $initials = strtoupper(substr($name_parts[0], 0, 1));
                if(count($name_parts) > 1) $initials .= strtoupper(substr(end($name_parts), 0, 1));
            ?>
            <div class="staff-card">
                <div style="position:absolute; top:24px; right:25px; display:flex; gap:12px;">
                    <i class="fas fa-edit" style="color:#94a3b8; cursor:pointer;" title="Edit"></i>
                    <i class="fas fa-trash" style="color:#f87171; cursor:pointer;" onclick="deleteStaff(<?php echo $staff['id']; ?>)" title="Delete"></i>
                </div>
                <div class="status-pill">Active</div>
                
                <div class="card-header">
                    <div class="avatar"><?php echo $initials; ?></div>
                    <div>
                        <div style="font-weight:700; font-size: 1rem;"><?php echo htmlspecialchars($staff['username']); ?></div>
                        <span class="role-badge"><?php echo $staff['role']; ?></span>
                    </div>
                </div>
                
                <div class="info-row"><i class="far fa-envelope"></i> <?php echo htmlspecialchars($staff['email']); ?></div>
                <div class="info-row"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($staff['phone_number'] ?: 'No phone'); ?></div>
                <div class="info-row"><i class="far fa-calendar"></i> Joined <?php echo date('M d, Y', strtotime($staff['created_at'])); ?></div>
                
                <div style="margin-top:16px; font-size:0.75rem; font-weight:600; color:var(--text-muted); text-transform: uppercase;">Work Schedule:</div>
                <div class="schedule-tags">
                    <?php 
                    $days = explode(',', $staff['work_schedule']);
                    foreach($days as $day) {
                        if(!empty($day)) echo "<span class='tag'>$day</span>";
                    }
                    ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>

    <div id="addModal">
        <div class="modal-content">
            <span onclick="closeModal()" style="position:absolute; right:24px; top:24px; cursor:pointer; font-size: 1.5rem; color: var(--text-muted);">&times;</span>
            <h2 style="margin-top:0; font-size:1.25rem; font-weight: 700; margin-bottom: 24px;">Add New Staff Member</h2>
            <form action="api/manage_staff.php?action=add" method="POST">
                <input type="hidden" name="shop_id" value="<?php echo $shop_id; ?>">
                <div class="form-group"><label>Full Name</label><input type="text" name="username" placeholder="e.g. John Doe" required></div>
                <div class="form-group"><label>Email Address</label><input type="email" name="email" placeholder="staff@cleanfresh.com" required></div>
                <div class="form-group"><label>Phone Number</label><input type="text" name="phone_number" placeholder="09123456789"></div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role"><option value="staff">Staff</option><option value="supervisor">Supervisor</option></select>
                </div>
                <div class="form-group"><label>Initial Password</label><input type="password" name="password" required></div>
                
                <label style="font-size:0.875rem; font-weight:600; display: block; margin-bottom: 8px;">Work Schedule</label>
                <div class="schedule-grid">
                    <label><input type="checkbox" name="schedule[]" value="Mon"> Mon</label>
                    <label><input type="checkbox" name="schedule[]" value="Tue"> Tue</label>
                    <label><input type="checkbox" name="schedule[]" value="Wed"> Wed</label>
                    <label><input type="checkbox" name="schedule[]" value="Thu"> Thu</label>
                    <label><input type="checkbox" name="schedule[]" value="Fri"> Fri</label>
                    <label><input type="checkbox" name="schedule[]" value="Sat"> Sat</label>
                    <label><input type="checkbox" name="schedule[]" value="Sun"> Sun</label>
                </div>
                <button type="submit" class="btn-black" style="width:100%; margin-top:24px; justify-content: center;">Add Staff Member</button>
            </form>
        </div>
    </div>

    <script>
        function toggleTheme() {
    document.body.classList.toggle("light-mode");

    if (document.body.classList.contains("light-mode")) {
        localStorage.setItem("theme", "light");
    } else {
        localStorage.setItem("theme", "dark");
    }
}

window.onload = function () {
    if (localStorage.getItem("theme") === "light") {
        document.body.classList.add("light-mode");
        document.getElementById("themeSwitch").checked = true;
    }
};
        function openModal() { document.getElementById('addModal').style.display = 'flex'; }
        function closeModal() { document.getElementById('addModal').style.display = 'none'; }
        window.onclick = function(event) {
            let modal = document.getElementById('addModal');
            if (event.target == modal) closeModal();
        }
        function deleteStaff(id) { 
            if(confirm('Are you sure you want to remove this staff member? This action cannot be undone.')) {
                window.location.href = `api/manage_staff.php?action=delete&id=${id}`; 
            }
        }
        function openChatSupport() {
    window.location.href = "support.php";
    }
    </script>
</body>
</html>