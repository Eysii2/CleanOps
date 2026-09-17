<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delivery & Pickup Assignment | Staff Dashboard</title>

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
    background: var(--bg-main);
    color: var(--text-main);
    min-height: 100vh;
}

/* =======================
   SIDEBAR (MATCHED EXACT)
======================= */
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
/* =========================
   FIX: TOGGLE ICON VISIBILITY
========================= */

.menu-toggle {
    margin-left: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    cursor: pointer;
    z-index: 2;
}

/* make sure icon ALWAYS visible */
.rotate-icon {
    font-size: 0.9rem;
    color: var(--primary);
    opacity: 1;
    visibility: visible;
    display: inline-block;
}

/* when active (expanded) */
.nav-item-wrapper.active-parent .rotate-icon {
    color: #0F2027; /* match dark text on active bg */
    opacity: 1;
}

/* optional hover feedback */
.menu-toggle:hover {
    background: rgba(255,255,255,0.15);
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

/* SAME AS YOUR PREVIOUS PAGES */
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

.nav-item-wrapper:hover {
    background: rgba(46, 196, 182, 0.12);
}

.nav-item {
    display: flex;
    align-items: center;
    color: var(--text-muted);
    text-decoration: none;
    font-size: 0.88rem;
    gap: 10px;
}

.nav-item i {
    width: 22px;
    color: var(--primary);
}

/* ACTIVE */
.nav-item-wrapper.active-parent {
    background: var(--primary);
}

.nav-item-wrapper.active-parent .nav-item {
    color: #0F2027;
}

/* SUBMENU */
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
}

.sub-item:hover {
    color: var(--primary);
}

/* ARROW */
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
    color: var(--text-muted);
    margin-top: 6px;
}

/* CARD */
.drivers-card, .assign-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 20px;
    box-shadow: var(--shadow);
}

.assignment-grid {
    margin-top: 20px;
}

.form-field {
    margin-bottom: 14px;
}

.form-field label {
    font-size: 0.8rem;
    color: var(--text-muted);
    display: block;
    margin-bottom: 6px;
}

.select-input {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    background: var(--bg-input);
    border: 1px solid var(--border);
    color: var(--text-main);
}

.confirm-btn {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: none;
    background: var(--primary);
    color: #0F2027;
    font-weight: 600;
    cursor: pointer;
}

.confirm-btn:hover {
    background: var(--primary-hover);
}
</style>
</head>

<body>

<!-- ================= SIDEBAR (FIXED MATCH VERSION) ================= -->
<aside class="sidebar">
    <div class="brand">
        <h2>CleanOps</h2>
        <p>Staff Member</p>
    </div>

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

        <!-- ORDERS -->
        <div class="nav-item-wrapper" id="orders-wrapper">
    <a href="#" class="nav-item" style="pointer-events:none;">
        <i class="fa-solid fa-clipboard-list"></i>
        <span>Orders</span>
    </a>

    <div class="menu-toggle" style="margin-left:auto;">
        <i class="fa-solid fa-chevron-down rotate-icon" id="arrow-icon"></i>
    </div>
</div>

<div class="sub-menu" id="orders-sub-menu">
    <a href="view_details.php" class="sub-item">View Details</a>
    <a href="update_status.php" class="sub-item">Update Status</a>
    <a href="staff_markorders.php" class="sub-item">Mark as Done/Canceled</a>
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

<!-- ================= MAIN ================= -->
<main class="main-container">

    <header class="header-section">
        <h1>Delivery & Pickup Assignment</h1>
        <p>Assign drivers for delivery and pickup orders</p>
    </header>

    <div class="assignment-grid">

        <div class="assign-card">

            <div class="form-field">
                <label>Assign Driver</label>
                <select class="select-input">
                    <option>Select driver</option>
                </select>
            </div>

            <div class="form-field">
                <label>Select Date</label>
                <input type="date" class="select-input">
            </div>

            <div class="form-field">
                <label>Time Slot</label>
                <select class="select-input">
                    <option>Select time</option>
                </select>
            </div>

            <button class="confirm-btn">
                <i class="fa-solid fa-circle-check"></i> Confirm Assignment
            </button>

        </div>

    </div>
</main>

<!-- ================= SCRIPT (SAME FUNCTION AS YOUR DASHBOARD) ================= -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    const wrapper = document.getElementById("orders-wrapper");
    const submenu = document.getElementById("orders-sub-menu");
    const arrow = document.getElementById("arrow-icon");

    let isOpen = false;

    function updateMenu(state) {
        isOpen = state;

        submenu.classList.toggle("open", isOpen);
        wrapper.classList.toggle("active-parent", isOpen);
        arrow.classList.toggle("active", isOpen);
    }

    // start CLOSED (no highlight)
    updateMenu(false);

    wrapper.addEventListener("click", function (e) {

        if (e.target.closest(".sub-menu")) return;

        updateMenu(!isOpen);
    });

});
</script>

</body>
</html>