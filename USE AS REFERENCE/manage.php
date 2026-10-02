<?php
session_start();
require_once 'conn/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get shop data
$shop_query = $conn->prepare("SELECT * FROM shops WHERE user_id = ?");
$shop_query->bind_param("i", $user_id);
$shop_query->execute();
$shop = $shop_query->get_result()->fetch_assoc();
$shop_id = $shop['id'];

// Fetch Services
$services = $conn->query("SELECT * FROM services WHERE shop_id = $shop_id ORDER BY id DESC");

// --- NEW: Fetch Inventory for the Recipe Builder ---
$inventory_query = $conn->query("SELECT item_name, unit FROM inventory WHERE shop_id = $shop_id");
$inventory_items = [];
while($item = $inventory_query->fetch_assoc()) {
    $inventory_items[] = $item;
}
// --------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shop | <?php echo htmlspecialchars($shop['shop_name']); ?></title>
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
            --promo-gold: #fbbf24;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { display: flex; background: linear-gradient(135deg, #0F2027, #132f36); color: var(--text-main); min-height: 100vh; }
        
        /* SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: var(--bg-card); border-right: 1px solid var(--border); height: 100vh; position: fixed; display: flex; flex-direction: column; padding: 24px 14px; }
        .brand { margin-bottom: 30px; padding-left: 14px; border-left: 4px solid var(--primary); }
        .brand h2 { font-size: 1.05rem; font-weight: 700; color: var(--primary); letter-spacing: 0.5px; }
        .brand p { font-size: 0.75rem; color: var(--text-muted); }
        .nav-list { list-style: none; flex-grow: 1; }
        .nav-item { display: flex; align-items: center; padding: 11px 12px; margin-bottom: 6px; border-radius: 8px; color: var(--text-muted); text-decoration: none; font-size: 0.88rem; transition: 0.25s ease; }
        .nav-item i { width: 22px; font-size: 0.95rem; margin-right: 10px; color: var(--primary); }
        .nav-item:hover { background: rgba(46, 196, 182, 0.12); color: var(--text-main); }
        .nav-item.active { background: var(--primary); color: #0F2027; font-weight: 600; }
        .nav-item.active i { color: #0F2027; }
        .logout-btn { border-top: 1px solid var(--border); padding-top: 16px; margin-top: 10px; color: var(--accent); text-decoration: none; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 10px; transition: 0.25s ease; }
        .logout-btn:hover { color: #ff9475; transform: translateX(4px); }

        /* MAIN CONTENT */
        .main-content { margin-left: var(--sidebar-width); flex-grow: 1; padding: 40px; }
        .header-section { margin-bottom: 32px; }
        .header-section h1 { font-size: 1.75rem; font-weight: 700; }
        .tabs-container { display: flex; gap: 12px; margin-bottom: 32px; border-bottom: 1px solid var(--border); padding-bottom: 12px; }
        .tab { padding: 8px 18px; border-radius: 20px; font-size: 0.875rem; font-weight: 600; cursor: pointer; color: var(--text-muted); transition: 0.2s; }
        .tab.active { background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border); }
        .section-title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .btn-primary { background: #000; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; gap: 8px; }

        /* SERVICES */
        .services-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; }
        .service-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 24px; position: relative; box-shadow: var(--shadow); }
        .service-card.promo { border: 2px solid var(--promo-gold); background: #2a3e45; }
        .promo-badge { background: var(--promo-gold); color: #92400e; font-size: 10px; font-weight: 800; padding: 2px 10px; border-radius: 12px; position: absolute; top: 15px; right: 15px; }
        .price-tag { font-size: 1.5rem; font-weight: 700; color: var(--primary); margin: 12px 0; }
        .delete-btn { color: #ef4444; background: none; border: none; cursor: pointer; font-size: 0.85rem; font-weight: 600; margin-top: 15px; padding: 0; display: flex; align-items: center; gap: 6px; }

        /* SETTINGS CARD */
        .settings-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 35px; max-width: 700px; box-shadow: var(--shadow); margin-top: 30px; margin-bottom: 30px; }
        .settings-card .form-group { margin-bottom: 20px; }
        .settings-card label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; outline: none; background: var(--bg-input); color: var(--text-main); }

        /* MODAL */
        #serviceModal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-box { background: var(--bg-card); padding: 32px; border-radius: 12px; width: 95%; max-width: 500px; box-shadow: var(--shadow); max-height: 90vh; overflow-y: auto; }
        
        .chat-support-btn { position: fixed; bottom: 25px; right: 25px; background: #2EC4B6; color: #0F2027; border: none; border-radius: 50px; padding: 14px 18px; font-size: 0.9rem; font-weight: 600; box-shadow: 0 8px 25px rgba(0,0,0,0.35); cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s ease; z-index: 999; animation: pulseBtn 2.5s infinite; }
        .chat-support-btn:hover { background: #3DD6C6; transform: translateY(-4px); }
        @keyframes pulseBtn { 0% { box-shadow: 0 0 0 0 rgba(46,196,182,0.5); } 70% { box-shadow: 0 0 0 15px rgba(46,196,182,0); } 100% { box-shadow: 0 0 0 0 rgba(46,196,182,0); } }

        /* TOAST */
        .toast { position: fixed; top: 30px; right: 30px; background: var(--bg-card); color: var(--text-main); padding: 16px 24px; border-radius: 8px; border-left: 4px solid var(--primary); box-shadow: 0 10px 30px rgba(0,0,0,0.5); display: flex; align-items: center; gap: 12px; z-index: 9999; font-size: 0.95rem; font-weight: 500; transform: translateX(150%); animation: slideInToast 0.5s forwards, fadeOutToast 0.5s 3.5s forwards; }
        .toast i { color: var(--primary); font-size: 1.2rem; }
        @keyframes slideInToast { to { transform: translateX(0); } }
        @keyframes fadeOutToast { to { opacity: 0; visibility: hidden; } }

        /* NEW: Recipe Styling */
        .recipe-row { display: flex; gap: 10px; margin-bottom: 10px; }
        .recipe-input { flex: 1; padding: 10px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-input); color: white; }
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
    display: flex;
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
    background: #000 !important;   /* Always black */
    color: #fff !important;
    border: none;
}
.btn-primary:hover {
    background: #111 !important;
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
    <!-- TOAST NOTIFICATIONS -->
    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
        <div class="toast"><i class="fas fa-check-circle"></i> Shop profile updated successfully!</div>
    <?php elseif (isset($_GET['success']) && $_GET['success'] == 'service_added'): ?>
        <div class="toast"><i class="fas fa-check-circle"></i> New service added!</div>
    <?php elseif (isset($_GET['success']) && $_GET['success'] == 'service_deleted'): ?>
        <div class="toast"><i class="fas fa-trash-alt"></i> Service permanently removed.</div>
    <?php endif; ?>

    <button class="chat-support-btn" onclick="openChatSupport()"><i class="fa-solid fa-comment-dots"></i> Chats</button>

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
            <a href="manage.php" class="nav-item active"><i class="fa-solid fa-gear"></i> Manage</a>
            <a href="billing.php" class="nav-item"><i class="fa-solid fa-credit-card"></i> Billing</a>
            <a href="add_staff.php" class="nav-item"><i class="fa-solid fa-user-plus"></i> Add Staff</a>
        </nav>
        <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </aside>

    <main class="main-content">
        <header class="header-section" style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Manage Shop</h1>

    <div class="theme-toggle">
        <input type="checkbox" id="themeSwitch">
        <label for="themeSwitch" class="toggle-label"></label>
    </div>
</header>

        <div class="tabs-container">
            <div class="tab active" id="tab-services" onclick="switchTab('services')">Services & Pricing</div>
            <div class="tab" id="tab-settings" onclick="switchTab('settings')">Shop Settings</div>
        </div>

        <section id="content-services">
            <div class="section-title-row">
                <h2 style="font-size: 1.25rem;">Available Services</h2>
                <button class="btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Add Service</button>
            </div>

            <div class="services-grid">
                <?php if ($services->num_rows == 0): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px; border: 2px dashed var(--border); border-radius: 12px; color: var(--text-muted);">
                        <i class="fas fa-concierge-bell fa-2x" style="margin-bottom: 12px;"></i>
                        <p>No services found. Add your first laundry service to start accepting orders.</p>
                    </div>
                <?php else: ?>
                    <?php while($row = $services->fetch_assoc()): ?>
                        <div class="service-card <?php echo $row['is_promo'] ? 'promo' : ''; ?>">
                            <?php if($row['is_promo']): ?><span class="promo-badge">PROMO</span><?php endif; ?>
                            <h3 style="font-size: 1.1rem; font-weight: 700;"><?php echo htmlspecialchars($row['service_name']); ?></h3>
                            <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;"><?php echo $row['category']; ?></span>
                            
                            <div class="price-tag">₱<?php echo number_format($row['price'], 2); ?></div>
                            
                            <?php if($row['promo_description']): ?>
                                <p style="font-size: 0.8rem; color: #92400e; background: rgba(251, 191, 36, 0.2); padding: 8px 12px; border-radius: 8px;">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($row['promo_description']); ?>
                                </p>
                            <?php endif; ?>

                            <button class="delete-btn" onclick="deleteService(<?php echo $row['id']; ?>)">
                                <i class="fas fa-trash-alt"></i> Remove Service
                            </button>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>

        <section id="content-settings" style="display: none;">
            <div class="section-title-row">
                <h2 style="font-size: 1.25rem;">Shop Profile</h2>
            </div>

            <?php if (empty($shop['address']) || empty($shop['contact_number'])): ?>
                <div style="background: rgba(255, 122, 89, 0.15); border: 1px solid var(--accent); padding: 16px; border-radius: 10px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; color: var(--text-main);">
                    <i class="fas fa-circle-exclamation" style="color: var(--accent); font-size: 1.2rem;"></i>
                    <div>
                        <strong style="display: block; color: var(--accent);">Setup Required</strong>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">Please provide your business address and contact number to complete your registration.</span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="settings-card">
                <form action="api/manage_api.php?action=update_settings" method="POST" enctype="multipart/form-data">
                    <div class="form-group" style="margin-bottom: 25px;">
                        <label>Shop Logo / Icon</label>
                        <?php if (!empty($shop['icon_name'])): ?>
                            <div style="margin-bottom: 12px;">
                                <img src="<?php echo htmlspecialchars($shop['icon_name']); ?>" alt="Shop Logo" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border);">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="icon_name" accept="image/png, image/jpeg, image/jpg" style="padding: 10px; border: 1px dashed var(--border); border-radius: 8px; width: 100%; background: var(--bg-input); color: var(--text-muted); cursor: pointer;">
                        <small style="color: var(--text-muted); display: block; margin-top: 5px;">Recommended: 200x200px (JPG/PNG)</small>
                    </div>

                    <div class="form-group">
                        <label>Shop Name</label>
                        <input type="text" name="shop_name" value="<?php echo htmlspecialchars($shop['shop_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="tel" name="contact" value="<?php echo htmlspecialchars($shop['contact_number'] ?? ''); ?>" placeholder="e.g. 09123456789" required>
                    </div>

                    <div class="form-group">
                        <label>Business Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($shop['address'] ?? ''); ?>" placeholder="Street, City, Zip" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Opening Time</label>
                            <input type="time" name="opening_time" value="<?php echo $shop['opening_time'] ?? '08:00'; ?>">
                        </div>
                        <div class="form-group">
                            <label>Closing Time</label>
                            <input type="time" name="closing_time" value="<?php echo $shop['closing_time'] ?? '20:00'; ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top: 15px; width: 100%; justify-content: center;">
                        <i class="fas fa-save"></i> Save Shop Changes
                    </button>
                </form>
            </div>
        </section>
    </main>

    <div id="serviceModal">
        <div class="modal-box">
            <h3 style="margin-bottom: 24px; font-size: 1.2rem;">Add New Service</h3>
            <form action="api/manage_api.php?action=save_service" method="POST">
                <input type="hidden" name="shop_id" value="<?php echo $shop_id; ?>">
                
                <div class="form-group">
                    <label>Service Name</label>
                    <input type="text" name="service_name" placeholder="e.g. Wash, Dry & Fold" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="Full Service">Full Service (Wash & Dry)</option>
                            <option value="Wash Only">Wash Only</option>
                            <option value="Dry Only">Dry Only</option>
                            <option value="Ironing">Ironing</option>
                            <option value="Special Items">Special Items</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Price (₱)</label>
                        <input type="number" step="0.01" name="price" placeholder="0.00" required>
                    </div>
                </div>

                <!-- --- NEW: RECIPE BUILDER SECTION --- -->
                <div class="form-group" style="background: rgba(46, 196, 182, 0.05); padding: 15px; border-radius: 8px; border: 1px solid var(--border); margin-top: 10px;">
                    <label style="color: var(--primary); font-weight: 700;"><i class="fa-solid fa-flask"></i> Recipe (Per kg)</label>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 12px;">Define what ingredient is needed per kg ordered.</p>
                    
                    <div id="recipe-container">
                        <!-- Dynamic Recipe Rows will appear here -->
                    </div>
                    
                    <button type="button" onclick="addRecipeRow()" style="background: none; border: 1px dashed var(--primary); color: var(--primary); width: 100%; padding: 10px; border-radius: 6px; cursor: pointer; margin-top: 5px; font-weight: 600; font-size: 0.85rem;">
                        <i class="fa-solid fa-plus"></i> Add Recipe
                    </button>
                </div>
                <!-- ----------------------------------- -->
                
                <div class="form-group" style="margin-top: 15px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="is_promo" value="1" style="width: 18px; height: 18px;"> Mark as Promo
                    </label>
                </div>
                
                <div class="form-group">
                    <input type="text" name="promo_description" placeholder="Promo description e.g. Weekend Special: ₱20 off">
                </div>
                
                <div style="display: flex; gap: 12px; margin-top: 25px;">
                    <button type="submit" class="btn-primary" style="flex: 1; justify-content: center;">Add Service</button>
                    <button type="button" onclick="closeModal()" style="flex: 1; border: 1px solid var(--border); background: transparent; color: white; border-radius: 8px; cursor: pointer; font-weight: 600;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const themeSwitch = document.getElementById("themeSwitch");

// Load saved theme
if (localStorage.getItem("theme") === "light") {
    document.body.classList.add("light-mode");
    if(themeSwitch) themeSwitch.checked = true;
}

// Toggle theme
if(themeSwitch){
    themeSwitch.addEventListener("change", function () {
        if (this.checked) {
            document.body.classList.add("light-mode");
            localStorage.setItem("theme", "light");
        } else {
            document.body.classList.remove("light-mode");
            localStorage.setItem("theme", "dark");
        }
    });
}
        // --- NEW: RECIPE JAVASCRIPT ---
        const inventoryItems = <?php echo json_encode($inventory_items); ?>;

        function addRecipeRow() {
            const container = document.getElementById('recipe-container');
            const row = document.createElement('div');
            row.className = 'recipe-row';
            
            let options = '<option value="">Select Supply...</option>';
            inventoryItems.forEach(item => {
                options += `<option value="${item.item_name}">${item.item_name} (${item.unit})</option>`;
            });

            row.innerHTML = `
                <select name="recipe_items[]" class="recipe-input" style="flex: 2;" required>
                    ${options}
                </select>
                <input type="number" step="0.001" name="recipe_qty[]" placeholder="Qty per kg" class="recipe-input" style="flex: 1;" required>
                <button type="button" onclick="this.parentElement.remove()" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 0 12px; border-radius: 6px; cursor: pointer;">
                    <i class="fa-solid fa-trash"></i>
                </button>
            `;
            container.appendChild(row);
        }
        // ------------------------------

        if (window.history.replaceState) {
            const url = new URL(window.location.href);
            if (url.searchParams.has('updated') || url.searchParams.has('success')) {
                url.searchParams.delete('updated');
                url.searchParams.delete('success');
                window.history.replaceState({path: url.href}, '', url.href);
            }
        }

        function switchTab(tabName) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.getElementById('tab-' + tabName).classList.add('active');
            
            document.getElementById('content-services').style.display = tabName === 'services' ? 'block' : 'none';
            document.getElementById('content-settings').style.display = tabName === 'settings' ? 'block' : 'none';
        }

        function openModal() { 
            // Add a blank row automatically if it's empty
            if(document.getElementById('recipe-container').children.length === 0) {
                addRecipeRow();
            }
            document.getElementById('serviceModal').style.display = 'flex'; 
        }

        function closeModal() { 
            document.getElementById('serviceModal').style.display = 'none'; 
            // Clear the form when closed so it's fresh next time
            document.getElementById('recipe-container').innerHTML = '';
        }
        
        function deleteService(id) {
            if(confirm('Are you sure you want to permanently remove this service?')) {
                window.location.href = `api/manage_api.php?action=delete_service&id=${id}`;
            }
        }

        window.onclick = function(event) {
            let modal = document.getElementById('serviceModal');
            if (event.target == modal) closeModal();
        }
        function openChatSupport() {
            window.location.href = "support.php";
        }
    </script>
</body>
</html>