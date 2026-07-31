<?php 
session_start();
require 'db_connect.php';

// KUNG WALA NAKA LOG-IN, E-KICK OUT PAINGON SA LOGIN PAGE
if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'admin@cherryjoe.com') {
    header("Location: index.php");
    exit();
}

$message = '';
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

// ==========================================
// GAME SLOTS MANAGEMENT
// ==========================================
$slots_file = 'game_slots.txt';
if(!file_exists($slots_file)) { file_put_contents($slots_file, "10"); } 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_slots'])) {
    $new_slots = (int)$_POST['game_slots'];
    file_put_contents($slots_file, $new_slots);
    $message = "<div class='success-msg'><i class='fas fa-gamepad'></i> Game slots successfully updated to $new_slots!</div>";
}
$current_slots = (int)file_get_contents($slots_file);

// ==========================================
// MAINTENANCE MODE MANAGEMENT
// ==========================================
$maintenance_file = 'maintenance_mode.txt';
if(!file_exists($maintenance_file)) { file_put_contents($maintenance_file, "0"); }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_maintenance'])) {
    $current_m = file_get_contents($maintenance_file);
    $new_m = ($current_m === "1") ? "0" : "1";
    file_put_contents($maintenance_file, $new_m);
    $status_text = ($new_m === "1") ? "ENABLED" : "DISABLED";
    $message = "<div class='success-msg'><i class='fas fa-tools'></i> Maintenance mode $status_text!</div>";
}
$is_maintenance = (file_get_contents($maintenance_file) === "1");

function uploadFile($fileInputName, $uploadDir) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.-]/", "_", $_FILES[$fileInputName]['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetPath)) { return $targetPath; }
    }
    return false;
}

// ==========================================
// BOOKING & RESERVATION MANAGEMENT 
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id']) && isset($_POST['new_status'])) {
    $b_id = $_POST['booking_id'];
    $n_status = $_POST['new_status']; 

    try {
        $getStmt = $conn->prepare("SELECT * FROM bookings WHERE id = :id");
        $getStmt->execute(['id' => $b_id]);
        $b_data = $getStmt->fetch();

        if($b_data) {
            $updateStmt = $conn->prepare("UPDATE bookings SET status = :status WHERE id = :id");
            $updateStmt->execute(['status' => $n_status, 'id' => $b_id]);
            
            $google_app_script_url = 'https://script.google.com/macros/s/AKfycbzraWE7fbxFfwI8mm5ixTHT9NLQUxLqcjlwfPpkl7yfe3-4F-t44fRosm3EL7sDj1ju4w/exec'; 
            
            $action_type = '';
            if ($n_status === 'Confirmed') { $action_type = 'confirm'; }
            elseif ($n_status === 'Cancelled') { $action_type = 'reject'; }
            elseif ($n_status === 'Verified') { $action_type = 'verify'; }
            
            $url = $google_app_script_url . "?action=" . $action_type . 
                   "&email=" . urlencode($b_data['user_email']) . 
                   "&name=" . urlencode($b_data['user_name']) . 
                   "&cottage=" . urlencode($b_data['cottage_type']) . 
                   "&check_in=" . urlencode($b_data['check_in']) . 
                   "&check_out=" . urlencode($b_data['check_out']) .
                   "&booking_id=" . urlencode($b_id);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch); curl_close($ch);

            if ($n_status === 'Verified') {
                $message = "<div class='success-msg'><i class='fas fa-check-double'></i> Guest Checked-In successfully! Welcome email sent.</div>";
            } else {
                $message = "<div class='success-msg'><i class='fas fa-check-circle'></i> Booking ID #$b_id updated to $n_status.</div>";
            }
        } else { $message = "<div class='error-msg'>Booking not found.</div>"; }
    } catch(PDOException $e) { $message = "<div class='error-msg'>Failed to update booking.</div>"; }
}

// ==========================================
// MENU MANAGEMENT
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_menu'])) {
    $image_url = uploadFile('photo', $upload_dir) ?: 'https://placehold.co/400x250?text=No+Image';
    try { $conn->prepare("INSERT INTO menu_items (category, name, description, price, image_url) VALUES (?, ?, ?, ?, ?)")->execute([$_POST['category'], $_POST['name'], $_POST['description'], $_POST['price'], $image_url]); $message = "<div class='success-msg'>Menu item added!</div>"; } catch(PDOException $e) {}
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_menu'])) {
    $image_url = uploadFile('photo', $upload_dir) ?: $_POST['existing_image'];
    try { $conn->prepare("UPDATE menu_items SET category=?, name=?, description=?, price=?, image_url=? WHERE id=?")->execute([$_POST['category'], $_POST['name'], $_POST['description'], $_POST['price'], $image_url, $_POST['item_id']]); $message = "<div class='success-msg'>Menu item updated!</div>"; } catch(PDOException $e) {}
}
if (isset($_GET['delete_menu'])) { try { $conn->prepare("DELETE FROM menu_items WHERE id=?")->execute([$_GET['delete_menu']]); header("Location: admin_dashboard.php"); exit(); } catch(PDOException $e) {} }

// ==========================================
// GALLERY MANAGEMENT
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_gallery'])) {
    if ($photo_path = uploadFile('gallery_photo', $upload_dir)) { try { $conn->prepare("INSERT INTO gallery (image_path) VALUES (?)")->execute([$photo_path]); $message = "<div class='success-msg'>Photo added to Gallery!</div>"; } catch(PDOException $e) {} }
}
if (isset($_GET['delete_gallery'])) { try { $conn->prepare("DELETE FROM gallery WHERE id=?")->execute([$_GET['delete_gallery']]); header("Location: admin_dashboard.php"); exit(); } catch(PDOException $e) {} }

// ==========================================
// VIDEO TOUR MANAGEMENT
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_video'])) {
    if ($video_path = uploadFile('resort_video', $upload_dir)) { try { $conn->prepare("INSERT INTO videos (title, video_path) VALUES (?, ?)")->execute([$_POST['video_title'], $video_path]); $message = "<div class='success-msg'>Video added!</div>"; } catch(PDOException $e) {} }
}
if (isset($_GET['delete_video'])) { try { $conn->prepare("DELETE FROM videos WHERE id=?")->execute([$_GET['delete_video']]); header("Location: admin_dashboard.php"); exit(); } catch(PDOException $e) {} }

// FETCH DATA FOR TABLES & EDITING
$is_editing = false; $edit_data = ['id'=>'', 'category'=>'Specialties', 'name'=>'', 'price'=>'', 'description'=>'', 'image_url'=>''];
if (isset($_GET['edit_menu'])) { 
    $is_editing = true; 
    try { 
        $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id=?"); 
        $stmt->execute([$_GET['edit_menu']]); 
        if($item = $stmt->fetch()) $edit_data = $item; 
    } catch(PDOException $e) {} 
}

try { $menu_list = $conn->query("SELECT * FROM menu_items ORDER BY category ASC, id DESC")->fetchAll(); } catch(PDOException $e) { $menu_list = []; }
try { $gallery_list = $conn->query("SELECT * FROM gallery ORDER BY id DESC")->fetchAll(); } catch(PDOException $e) { $gallery_list = []; }
try { $video_list = $conn->query("SELECT * FROM videos ORDER BY id DESC")->fetchAll(); } catch(PDOException $e) { $video_list = []; }
try { $all_bookings = $conn->query("SELECT * FROM bookings ORDER BY created_at DESC")->fetchAll(); } catch(PDOException $e) { $all_bookings = []; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CherryJoe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, sans-serif; }
        body { background: linear-gradient(135deg, rgba(16,185,129,0.05), rgba(5,150,105,0.1)); padding: 20px; color: #1e293b; }
        
        .header-flex { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid rgba(16, 185, 129, 0.1); padding-bottom: 20px; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;}
        .header-flex h1 { color: #059669; font-size: 28px; display: flex; align-items: center; gap: 12px; margin: 0;}
        .back-btn { background: #f8fafc; color: #475569; padding: 10px 20px; border-radius: 50px; text-decoration: none; border: 1px solid #cbd5e1; font-weight: 600; }
        
        /* TABS NAVIGATION */
        .admin-tabs-nav { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 20px; scrollbar-width: none; }
        .admin-tabs-nav::-webkit-scrollbar { display: none; }
        .admin-tab-btn { background: #f1f5f9; color: #64748b; padding: 12px 20px; border-radius: 12px; font-weight: bold; cursor: pointer; text-align: center; border: 2px solid transparent; transition: 0.3s; flex: 1; min-width: max-content; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 15px;}
        .admin-tab-btn.active { background: #d1fae5; color: #059669; border-color: #059669; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.15); }
        .admin-tab-btn:hover:not(.active) { background: #e2e8f0; }

        /* SECTIONS */
        .admin-section { display: none; animation: fadeIn 0.4s; background: #ffffff; padding: 25px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .admin-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .section-title { font-size: 18px; margin-bottom: 20px; color: #1e293b; border-left: 5px solid #059669; padding-left: 10px; display: flex; justify-content: space-between; align-items: center;}
        
        .btn-scan { background: #1e293b; color: white; border: none; padding: 8px 15px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 14px;}
        .btn-scan:hover { background: #0f172a; }
        .scanner-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; justify-content: center; align-items: center; padding: 20px;}
        .scanner-box { background: white; padding: 20px; border-radius: 15px; width: 100%; max-width: 500px; text-align: center; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;}
        .full-width { grid-column: span 2; }
        label { font-size: 13px; font-weight: bold; color: #64748b; display: block; margin-bottom: 5px; text-transform: uppercase;}
        input[type="text"], input[type="number"], input[type="file"], select, textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc; }
        .btn-green { background: #059669; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 10px; display: inline-block;}
        
        .table-container { overflow-x: auto; background: white; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 20px;}
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f1f5f9; color: #475569; font-size: 13px; text-transform: uppercase; }
        .delete-btn { color: white; background: #ef4444; padding: 6px 10px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-block; margin-top: 5px;}
        .edit-btn { color: white; background: #3b82f6; padding: 6px 10px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-right: 5px; font-size: 12px; display: inline-block; margin-top: 5px;}
        
        .status-badge { padding: 6px 12px; border-radius: 50px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .Pending { background: #fef3c7; color: #d97706; }
        .Confirmed { background: #d1fae5; color: #059669; }
        .Cancelled { background: #fee2e2; color: #ef4444; }
        .Verified { background: #e0e7ff; color: #2563eb; } 
        .success-msg { background: #d1fae5; color: #059669; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; border: 1px solid #a7f3d0;}
        .error-msg { background: #fee2e2; color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }

        .slots-box { background: #fffbeb; border: 2px dashed #f59e0b; padding: 15px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;}
        .slots-box h3 { margin-bottom: 5px; font-size: 16px;}
        .slots-box p { font-size: 13px; margin-top: 5px;}

        /* FOOD MENU GRID DISPLAY (STRICTLY 4 COLUMNS) */
        .food-manager-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .food-item-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; position: relative; display: flex; flex-direction: column; justify-content: space-between; }
        .food-item-card img { width: 100%; height: 120px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; cursor: zoom-in; }
        .food-item-card h4 { color: #1e293b; font-size: 15px; margin-bottom: 5px; }
        .food-item-card .cat-price { display: flex; justify-content: space-between; font-size: 12px; color: #64748b; font-weight: bold; margin-bottom: 5px;}
        .food-item-card .price { color: #059669; }
        .food-item-card .actions { display: flex; justify-content: space-between; margin-top: 10px; border-top: 1px dashed #cbd5e1; padding-top: 10px;}

        /* LIGHTBOX WITHOUT DOWNLOAD BUTTON */
        .lightbox { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); display: none; justify-content: center; align-items: center; z-index: 99999; opacity: 0; transition: opacity 0.3s ease; cursor: zoom-out; }
        .lightbox img { max-width: 95%; max-height: 85%; border-radius: 16px; box-shadow: 0 30px 60px rgba(0,0,0,0.3); transform: scale(0.8); transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); border: 2px solid #ffffff; }
        .lightbox.show { opacity: 1; }
        .lightbox.show img { transform: scale(1); }

        /* ==========================================
           MOBILE RESPONSIVE TWEAKS
           ========================================== */
        @media screen and (max-width: 768px) {
            .header-flex h1 { font-size: 22px; }
            .section-title { font-size: 16px; flex-direction: column; align-items: flex-start; gap: 10px; }
            .btn-scan { width: 100%; text-align: center; }
            .admin-section { padding: 15px; }
            
            /* FORCE EXACTLY 4 COLUMNS ON MOBILE */
            .food-manager-grid { grid-template-columns: repeat(4, 1fr) !important; gap: 8px; }
            .food-item-card { padding: 8px; }
            .food-item-card img { height: 80px; }
            .food-item-card h4 { font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .food-item-card .cat-price { flex-direction: column; font-size: 10px; gap: 2px; }
            .food-item-card .actions { flex-direction: column; gap: 5px; align-items: stretch;}
            .food-item-card .actions a { text-align: center; width: 100%; padding: 4px; font-size: 10px;}
            
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }

            /* FOR BOOKING TABLES (CARD STYLE) */
            table, thead, tbody, th, td, tr { display: block; width: 100%; }
            thead tr { display: none; } 
            tr { border: 1px solid #cbd5e1; border-radius: 12px; margin-bottom: 15px; padding: 10px; }
            td { border: none; border-bottom: 1px dashed #e2e8f0; padding: 10px 0; text-align: left; position: relative;}
            td:last-child { border-bottom: 0; text-align: center; } 
            td::before { content: attr(data-label) ": "; font-weight: bold; color: #64748b; font-size: 12px; display: block; margin-bottom: 4px; }
        }
    </style>
</head>
<body>

<div style="max-width: 1200px; margin: auto;">
    
    <div class="header-flex">
        <h1><i class="fas fa-cogs"></i> Admin Dashboard</h1>
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Admin</a>
    </div>
    
    <?php echo $message; ?>

    <!-- TABS NAVIGATION PARA DI NA SIGE'G SCROLL -->
    <div class="admin-tabs-nav">
        <div class="admin-tab-btn active" id="btn-reservations" onclick="switchAdminTab('reservations')">
            <i class="fas fa-calendar-check"></i> Reservations
        </div>
        <div class="admin-tab-btn" id="btn-menu" onclick="switchAdminTab('menu')">
            <i class="fas fa-utensils"></i> Food Menu
        </div>
        <div class="admin-tab-btn" id="btn-gallery" onclick="switchAdminTab('gallery')">
            <i class="fas fa-images"></i> Gallery
        </div>
        <div class="admin-tab-btn" id="btn-videos" onclick="switchAdminTab('videos')">
            <i class="fas fa-video"></i> Videos
        </div>
        <div class="admin-tab-btn" id="btn-settings" onclick="switchAdminTab('settings')">
            <i class="fas fa-cogs"></i> System Settings
        </div>
    </div>

    <!-- TAB 1: RESERVATION MANAGEMENT -->
    <div id="tab-reservations" class="admin-section active">
        <h2 class="section-title">
            <span><i class="fas fa-calendar-check"></i> Reservation Management</span>
            <button class="btn-scan" onclick="openScanner()"><i class="fas fa-qrcode"></i> Scan Guest QR</button>
        </h2>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Guest Details</th><th>Facility & Dates</th><th>GCash Ref</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($all_bookings) > 0): ?>
                        <?php foreach ($all_bookings as $booking): ?>
                        <?php 
                            $today = date('Y-m-d');
                            $is_expired = (strtotime($booking['check_out']) < strtotime($today));
                        ?>
                        <tr>
                            <td data-label="Booking ID"><b>#<?php echo htmlspecialchars($booking['id'] ?? ''); ?></b></td>
                            <td data-label="Guest">
                                <div style="font-weight: bold; font-size: 15px;"><?php echo htmlspecialchars($booking['user_name'] ?? ''); ?></div>
                                <div style="font-size: 12px; color: #64748b;"><?php echo htmlspecialchars($booking['user_email'] ?? ''); ?></div>
                            </td>
                            <td data-label="Facility">
                                <div style="font-weight: bold; color: #059669; font-size: 14px;"><?php echo htmlspecialchars($booking['cottage_type'] ?? ''); ?></div>
                                <div style="font-size: 12px; color: #475569;"><b>In:</b> <?php echo htmlspecialchars($booking['check_in'] ?? ''); ?> <br> <b>Out:</b> <?php echo htmlspecialchars($booking['check_out'] ?? ''); ?></div>
                            </td>
                            <td data-label="GCash Ref"><span style="background: #e2e8f0; padding: 4px 8px; border-radius: 6px; font-family: monospace; font-weight: bold; font-size: 13px;"><?php echo htmlspecialchars($booking['gcash_ref'] ?? 'N/A'); ?></span></td>
                            <td data-label="Status">
                                <span class="status-badge <?php echo htmlspecialchars($booking['status'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($booking['status'] ?? ''); ?>
                                </span>
                            </td>
                            <td data-label="Actions">
                                <?php if ($booking['status'] === 'Pending' && !$is_expired): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Approve booking?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="new_status" value="Confirmed">
                                        <button type="submit" style="background:#10b981; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer;" title="Confirm Booking"><i class="fas fa-check"></i></button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Reject booking?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="new_status" value="Cancelled">
                                        <button type="submit" style="background:#ef4444; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer;" title="Reject Booking"><i class="fas fa-times"></i></button>
                                    </form>
                                <?php elseif ($is_expired && $booking['status'] === 'Pending'): ?>
                                    <span style="color: #ef4444; font-size: 12px; font-weight: bold; font-style: italic;"><i class="fas fa-ban"></i> Expired</span>
                                <?php elseif ($booking['status'] === 'Verified'): ?>
                                    <span style="color: #2563eb; font-size: 12px; font-weight: bold;"><i class="fas fa-check-double"></i> Checked-In</span>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 12px; font-style: italic;">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">No reservations found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: FOOD MENU MANAGEMENT -->
    <div id="tab-menu" class="admin-section">
        <h2 class="section-title"><i class="fas fa-utensils"></i> Manage Food Menu</h2>
        
        <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px dashed #cbd5e1; margin-bottom: 20px;">
            <h3 style="margin-bottom: 15px; color: #059669; font-size: 16px;"><i class="fas fa-plus-circle"></i> <?php echo $is_editing ? 'Edit Menu Item' : 'Add New Item'; ?></h3>
            <form method="POST" enctype="multipart/form-data">
                <?php if($is_editing): ?>
                    <input type="hidden" name="item_id" value="<?php echo $edit_data['id']; ?>">
                    <input type="hidden" name="existing_image" value="<?php echo $edit_data['image_url']; ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div>
                        <label>Category</label>
                        <select name="category">
                            <option value="Specialties" <?php if(($edit_data['category'] ?? '')=='Specialties') echo 'selected'; ?>>Specialties</option>
                            <option value="Combo Meals" <?php if(($edit_data['category'] ?? '')=='Combo Meals') echo 'selected'; ?>>Combo Meals</option>
                            <option value="Finger Foods" <?php if(($edit_data['category'] ?? '')=='Finger Foods') echo 'selected'; ?>>Finger Foods</option>
                            <option value="Drinks" <?php if(($edit_data['category'] ?? '')=='Drinks') echo 'selected'; ?>>Drinks</option>
                        </select>
                    </div>
                    <div>
                        <label>Food/Drink Name</label>
                        <input type="text" name="name" required value="<?php echo htmlspecialchars($edit_data['name'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Price</label>
                        <input type="text" name="price" required value="<?php echo htmlspecialchars($edit_data['price'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Upload Photo</label>
                        <input type="file" name="photo" accept="image/*">
                    </div>
                    <div class="full-width">
                        <label>Description (Optional)</label>
                        <textarea name="description" rows="2"><?php echo htmlspecialchars($edit_data['description'] ?? ''); ?></textarea>
                    </div>
                </div>
                <button type="submit" name="<?php echo $is_editing ? 'update_menu' : 'add_menu'; ?>" class="btn-green">
                    <i class="fas fa-save"></i> <?php echo $is_editing ? 'Save Changes' : 'Add to Menu'; ?>
                </button>
                <?php if($is_editing): ?> 
                    <a href="admin_dashboard.php" style="margin-left:15px; color:#475569; font-weight:bold; text-decoration:none;">Cancel</a> 
                <?php endif; ?>
            </form>
        </div>

        <div class="food-manager-grid">
            <?php if(count($menu_list) > 0): ?>
                <?php foreach($menu_list as $row): ?>
                <div class="food-item-card">
                    <img src="<?php echo $row['image_url']; ?>" onclick="showImage(this.src)" onerror="this.src='https://placehold.co/400x250?text=No+Image'">
                    <h4><?php echo htmlspecialchars($row['name'] ?? ''); ?></h4>
                    <div class="cat-price">
                        <span><?php echo htmlspecialchars($row['category'] ?? ''); ?></span>
                        <span class="price"><?php echo htmlspecialchars($row['price'] ?? ''); ?></span>
                    </div>
                    <div class="actions">
                        <a href="admin_dashboard.php?edit_menu=<?php echo $row['id']; ?>" class="edit-btn"><i class="fas fa-edit"></i> Edit</a>
                        <a href="admin_dashboard.php?delete_menu=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Delete this item?');"><i class="fas fa-trash"></i> Delete</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #64748b; padding: 20px; width: 100%; grid-column: 1 / -1;">No menu items added yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB 3: GALLERY MANAGEMENT -->
    <div id="tab-gallery" class="admin-section">
        <h2 class="section-title"><i class="fas fa-images"></i> Manage Gallery Photos</h2>
        
        <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="flex: 1; min-width: 250px;">
                <label>Upload Photo for Gallery</label>
                <input type="file" name="gallery_photo" accept="image/*" required style="background:#f8fafc;">
            </div>
            <button type="submit" name="add_gallery" class="btn-green" style="margin-top: 0;"><i class="fas fa-upload"></i> Upload</button>
        </form>

        <div class="food-manager-grid">
            <?php if(count($gallery_list) > 0): ?>
                <?php foreach($gallery_list as $img): ?>
                <div class="food-item-card">
                    <img src="<?php echo $img['image_path']; ?>" onclick="showImage(this.src)" style="height: 100px;">
                    <a href="admin_dashboard.php?delete_gallery=<?php echo $img['id']; ?>" class="delete-btn" style="text-align:center;" onclick="return confirm('Delete this photo?');"><i class="fas fa-trash"></i> Delete Photo</a>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #64748b; padding: 20px; width: 100%; grid-column: 1 / -1;">No gallery photos added yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB 4: VIDEO TOUR MANAGEMENT -->
    <div id="tab-videos" class="admin-section">
        <h2 class="section-title"><i class="fas fa-video"></i> Manage Resort Videos</h2>
        <form method="POST" enctype="multipart/form-data" style="margin-bottom: 20px;">
            <div class="form-grid">
                <div>
                    <label>Video Title</label>
                    <input type="text" name="video_title" required placeholder="Ex: Pool Amenities">
                </div>
                <div>
                    <label>Upload Video (MP4)</label>
                    <input type="file" name="resort_video" accept="video/mp4" required style="background:#f8fafc;">
                </div>
            </div>
            <button type="submit" name="add_video" class="btn-green"><i class="fas fa-upload"></i> Upload Video</button>
        </form>

        <div class="table-container">
            <table>
                <thead><tr><th>Video Title</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if(count($video_list) > 0): ?>
                        <?php foreach($video_list as $vid): ?>
                        <tr>
                            <td data-label="Title"><strong><?php echo htmlspecialchars($vid['title'] ?? ''); ?></strong></td>
                            <td data-label="Action"><a href="admin_dashboard.php?delete_video=<?php echo $vid['id']; ?>" class="delete-btn" onclick="return confirm('Delete this video?');"><i class="fas fa-trash"></i> Delete</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2" style="text-align: center; color: #64748b; padding: 20px;">No videos added yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 5: SYSTEM SETTINGS (GAME SLOTS & MAINTENANCE) -->
    <div id="tab-settings" class="admin-section">
        <h2 class="section-title"><i class="fas fa-cogs"></i> System Settings</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
            <!-- GAME SLOTS MANAGER -->
            <div class="slots-box" style="border-color: #f59e0b; background: #fffbeb;">
                <div>
                    <h3 style="color: #d97706;"><i class="fas fa-gamepad"></i> 3D Game Prizes</h3>
                    <p style="color: #b45309;">Prize slots available today. (Current: <b><?php echo $current_slots; ?></b>)</p>
                </div>
                <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                    <input type="number" name="game_slots" value="<?php echo $current_slots; ?>" min="0" required style="width: 80px; text-align: center; font-weight: bold; font-size: 16px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 8px;">
                    <button type="submit" name="update_slots" class="btn-green" style="margin-top: 0; padding: 10px 15px;">Update</button>
                </form>
            </div>

            <!-- MAINTENANCE MODE MANAGER -->
            <div class="slots-box" style="border-color: #3b82f6; background: #eff6ff;">
                <div>
                    <h3 style="color: #1d4ed8;"><i class="fas fa-tools"></i> Maintenance Mode</h3>
                    <p style="color: #1e3a8a;">Turn off website for users. (Status: <b><?php echo $is_maintenance ? '<span style="color:#ef4444;">ON</span>' : '<span style="color:#10b981;">OFF</span>'; ?></b>)</p>
                </div>
                <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                    <?php if($is_maintenance): ?>
                        <button type="submit" name="toggle_maintenance" class="btn-green" style="background:#ef4444; margin-top:0; padding: 10px 15px;">Disable Maintenance</button>
                    <?php else: ?>
                        <button type="submit" name="toggle_maintenance" class="btn-green" style="background:#3b82f6; margin-top:0; padding: 10px 15px;">Enable Maintenance</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- QR SCANNER MODAL -->
<div class="scanner-modal" id="scannerModal">
    <div class="scanner-box">
        <h3 style="color:#059669; margin-bottom:15px;"><i class="fas fa-camera"></i> Scan Guest QR Code</h3>
        <div id="reader" style="width:100%; border-radius:10px; overflow:hidden;"></div>
        <button onclick="closeScanner()" style="margin-top:20px; background:#ef4444; color:white; border:none; padding:10px 20px; border-radius:50px; font-weight:bold; cursor:pointer; width:100%;">Close Camera</button>
    </div>
</div>

<!-- LIGHTBOX (NO DOWNLOAD BUTTON) -->
<div class="lightbox" id="lightbox" onclick="hideImage()">
    <img id="lightbox-img" alt="Enlarged Preview">
</div>

<script>
    // ==========================================
    // TABS LOGIC PARA DI NA MAG SCROLL
    // ==========================================
    function switchAdminTab(tabId) {
        document.querySelectorAll('.admin-section').forEach(sec => sec.classList.remove('active'));
        document.querySelectorAll('.admin-tab-btn').forEach(btn => btn.classList.remove('active'));
        
        document.getElementById('tab-' + tabId).classList.add('active');
        document.getElementById('btn-' + tabId).classList.add('active');
        
        // I-save sa browser aron kung mo-add/delete ta, di mobalik sa Reservations nga tab
        localStorage.setItem('adminLastTab', tabId);
    }

    // Kung naay gipindot nga edit, siguradong adto sa menu tab mobalik
    <?php if($is_editing): ?>
        localStorage.setItem('adminLastTab', 'menu');
    <?php endif; ?>

    window.addEventListener('load', () => {
        let lastTab = localStorage.getItem('adminLastTab');
        if(lastTab) { switchAdminTab(lastTab); }
    });

    // ==========================================
    // QR SCANNER LOGIC
    // ==========================================
    const bookingsData = <?php echo json_encode($all_bookings); ?>;
    const todayDate = "<?php echo date('Y-m-d'); ?>";
    let html5QrcodeScanner;

    function openScanner() {
        document.getElementById('scannerModal').style.display = 'flex';
        html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    }

    function closeScanner() {
        document.getElementById('scannerModal').style.display = 'none';
        if (html5QrcodeScanner) { html5QrcodeScanner.clear(); }
    }

    function onScanSuccess(decodedText, decodedResult) {
        if (decodedText.startsWith('CJRP-')) {
            let bookingId = decodedText.split('-')[1];
            html5QrcodeScanner.clear(); document.getElementById('scannerModal').style.display = 'none';
            
            let booking = bookingsData.find(b => b.id == bookingId);
            if (booking) {
                if (booking.check_out < todayDate) { alert("❌ QR Code Expired!"); return; }
                if (booking.status === 'Cancelled') { alert("❌ Invalid! Cancelled booking."); return; }
                if (booking.status === 'Verified') { alert("✅ Guest is already Checked-In / Verified!"); return; }

                if (confirm("✅ QR Code Detected!\nBooking ID: #" + bookingId + "\nGuest Name: " + booking.user_name + "\n\nDo you want to VERIFY and CHECK-IN this guest now?")) {
                    let form = document.createElement('form'); form.method = 'POST';
                    form.innerHTML = `<input type="hidden" name="booking_id" value="${bookingId}"><input type="hidden" name="new_status" value="Verified">`;
                    document.body.appendChild(form); form.submit();
                }
            } else { alert("❌ Booking ID not found."); }
        } else if (decodedText.startsWith('WINTIX-')) {
            html5QrcodeScanner.clear(); document.getElementById('scannerModal').style.display = 'none';
            alert("🎉 GAME PRIZE TICKET!\n\nTicket Code: " + decodedText + "\n\nKini nga guest nakadaog og premyo gikan sa 3D Game!");
        } else {
            alert("❌ Invalid QR Code!");
        }
    }
    function onScanFailure(error) {}

    // ==========================================
    // LIGHTBOX SCRIPT FOR IMAGES
    // ==========================================
    function showImage(src) { 
        const box = document.getElementById('lightbox');
        document.getElementById('lightbox-img').src = src;
        box.style.display = 'flex'; 
        setTimeout(() => box.classList.add('show'), 15);
    }
    
    function hideImage() { 
        const box = document.getElementById('lightbox');
        box.classList.remove('show');
        setTimeout(() => box.style.display = 'none', 300);
    }
</script>
</body>
</html>
