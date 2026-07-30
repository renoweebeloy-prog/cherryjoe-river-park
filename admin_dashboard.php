<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'admin@cherryjoe.com') {
    header("Location: index.php");
    exit();
}

$message = '';
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

function uploadFile($fileInputName, $uploadDir) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.-]/", "_", $_FILES[$fileInputName]['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetPath)) { return $targetPath; }
    }
    return false;
}

// ==========================================
// 0. BOOKING & RESERVATION MANAGEMENT (NEW)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id']) && isset($_POST['new_status'])) {
    $b_id = $_POST['booking_id'];
    $n_status = $_POST['new_status']; // Pwede 'Confirmed' o 'Cancelled'

    try {
        $getStmt = $conn->prepare("SELECT * FROM bookings WHERE id = :id");
        $getStmt->execute(['id' => $b_id]);
        $b_data = $getStmt->fetch();

        if($b_data) {
            $updateStmt = $conn->prepare("UPDATE bookings SET status = :status WHERE id = :id");
            $updateStmt->execute(['status' => $n_status, 'id' => $b_id]);
            
            // IPADALA ANG EMAIL PINAAGI SA GOOGLE APPS SCRIPT
            $google_app_script_url = 'https://script.google.com/macros/s/AKfycbz93x4b45fndZ6PTebbwNoaN9Xga8CeHtj3G7dCs0G8qQM6UAbeH41fyloDP0BWtCmhMg/exec'; // ⚠️ ILISI KINI SA IMONG SCRIPT URL! ⚠️
            
            $action_type = ($n_status === 'Confirmed') ? 'confirm' : 'reject';
            
            $url = $google_app_script_url . "?action=" . $action_type . 
                   "&email=" . urlencode($b_data['user_email']) . 
                   "&name=" . urlencode($b_data['user_name']) . 
                   "&cottage=" . urlencode($b_data['cottage_type']) . 
                   "&check_in=" . urlencode($b_data['check_in']) . 
                   "&check_out=" . urlencode($b_data['check_out']) .
                   "&booking_id=" . urlencode($b_id); // GIPASA ANG ID PARA SA QR!

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch); curl_close($ch);

            $message = "<div class='success-msg'><i class='fas fa-check-circle'></i> Booking ID #$b_id has been successfully $n_status. An email was sent to the guest.</div>";
        } else { $message = "<div class='error-msg'>Booking not found.</div>"; }
    } catch(PDOException $e) { $message = "<div class='error-msg'>Failed to update booking.</div>"; }
}

// ==========================================
// 1. MENU MANAGEMENT (CRUD + File Upload)
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
// 2. GALLERY MANAGEMENT
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_gallery'])) {
    if ($photo_path = uploadFile('gallery_photo', $upload_dir)) { try { $conn->prepare("INSERT INTO gallery (image_path) VALUES (?)")->execute([$photo_path]); $message = "<div class='success-msg'>Photo added to Gallery!</div>"; } catch(PDOException $e) {} }
}
if (isset($_GET['delete_gallery'])) { try { $conn->prepare("DELETE FROM gallery WHERE id=?")->execute([$_GET['delete_gallery']]); header("Location: admin_dashboard.php"); exit(); } catch(PDOException $e) {} }

// ==========================================
// 3. VIDEO TOUR MANAGEMENT
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_video'])) {
    if ($video_path = uploadFile('resort_video', $upload_dir)) { try { $conn->prepare("INSERT INTO videos (title, video_path) VALUES (?, ?)")->execute([$_POST['video_title'], $video_path]); $message = "<div class='success-msg'>Video added!</div>"; } catch(PDOException $e) {} }
}
if (isset($_GET['delete_video'])) { try { $conn->prepare("DELETE FROM videos WHERE id=?")->execute([$_GET['delete_video']]); header("Location: admin_dashboard.php"); exit(); } catch(PDOException $e) {} }

$is_editing = false; $edit_data = ['id'=>'', 'category'=>'Specialties', 'name'=>'', 'price'=>'', 'description'=>'', 'image_url'=>''];
if (isset($_GET['edit_menu'])) { $is_editing = true; try { $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id=?"); $stmt->execute([$_GET['edit_menu']]); if($item = $stmt->fetch()) $edit_data = $item; } catch(PDOException $e) {} }

$menu_list = $conn->query("SELECT * FROM menu_items ORDER BY id DESC")->fetchAll();
$gallery_list = $conn->query("SELECT * FROM gallery ORDER BY id DESC")->fetchAll();
$video_list = $conn->query("SELECT * FROM videos ORDER BY id DESC")->fetchAll();

// Fetch Bookings
try { $all_bookings = $conn->query("SELECT * FROM bookings ORDER BY created_at DESC")->fetchAll(); } catch(PDOException $e) { $all_bookings = []; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CherryJoe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- CAMERA SCANNER SCRIPT -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, sans-serif; }
        body { background: linear-gradient(135deg, rgba(16,185,129,0.05), rgba(5,150,105,0.1)); padding: 40px 20px; color: #1e293b; }
        .dashboard-container { background: #fff; padding: 40px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); max-width: 1100px; margin: 0 auto; }
        .header-flex { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid rgba(16, 185, 129, 0.1); padding-bottom: 20px; margin-bottom: 30px; }
        .header-flex h1 { color: #059669; font-size: 28px; display: flex; align-items: center; gap: 12px; }
        .back-btn { background: #f8fafc; color: #475569; padding: 10px 20px; border-radius: 50px; text-decoration: none; border: 1px solid #cbd5e1; font-weight: 600; }
        .section-title { font-size: 20px; margin: 40px 0 15px 0; color: #1e293b; border-left: 5px solid #059669; padding-left: 10px; display: flex; justify-content: space-between; align-items: center;}
        
        .btn-scan { background: #1e293b; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-scan:hover { background: #0f172a; }
        .scanner-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; justify-content: center; align-items: center; padding: 20px;}
        .scanner-box { background: white; padding: 20px; border-radius: 15px; width: 100%; max-width: 500px; text-align: center; }

        .form-box { background: #f8fafc; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0; margin-bottom: 20px;}
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full-width { grid-column: span 2; }
        label { font-size: 13px; font-weight: bold; color: #64748b; display: block; margin-bottom: 5px; text-transform: uppercase;}
        input[type="text"], input[type="file"], select, textarea { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: white; }
        .btn-green { background: #059669; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 15px; }
        .action-form { display: inline-block; margin-right: 5px; }
        .btn-action { padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 12px; transition: 0.2s; color: white; }
        .btn-confirm { background: #10b981; } .btn-reject { background: #ef4444; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: white; border: 1px solid #e2e8f0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f1f5f9; color: #475569; font-size: 13px; text-transform: uppercase; }
        .item-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .delete-btn { color: #ef4444; text-decoration: none; font-weight: bold; font-size: 14px;}
        .edit-btn { color: #3b82f6; text-decoration: none; font-weight: bold; margin-right: 10px; font-size: 14px;}
        
        .status-badge { padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .Pending { background: #fef3c7; color: #d97706; }
        .Confirmed { background: #d1fae5; color: #059669; }
        .Cancelled { background: #fee2e2; color: #ef4444; }
        .success-msg { background: #d1fae5; color: #059669; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .error-msg { background: #fee2e2; color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="header-flex">
        <h1><i class="fas fa-cogs"></i> Admin System Panel</h1>
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Website</a>
    </div>
    
    <?php echo $message; ?>

    <!-- BOOKINGS & RESERVATIONS -->
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
                        // I-check kung expired na ba ang petsa (base sa check_out date)
                        $today = date('Y-m-d');
                        $is_expired = (strtotime($booking['check_out']) < strtotime($today));
                    ?>
                    <tr>
                        <td><b>#<?php echo htmlspecialchars($booking['id']); ?></b></td>
                        <td>
                            <div style="font-weight: bold;"><?php echo htmlspecialchars($booking['user_name']); ?></div>
                            <div style="font-size: 12px; color: #64748b;"><?php echo htmlspecialchars($booking['user_email']); ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #059669;"><?php echo htmlspecialchars($booking['cottage_type']); ?></div>
                            <div style="font-size: 12px; color: #475569;"><b>In:</b> <?php echo htmlspecialchars($booking['check_in']); ?> | <b>Out:</b> <?php echo htmlspecialchars($booking['check_out']); ?></div>
                            <?php if ($is_expired && $booking['status'] !== 'Cancelled'): ?>
                                <span style="color: #ef4444; font-weight: bold; font-size: 11px;"><i class="fas fa-exclamation-circle"></i> Date Passed</span>
                            <?php endif; ?>
                        </td>
                        <td><span style="background: #e2e8f0; padding: 4px 8px; border-radius: 6px; font-family: monospace; font-weight: bold;"><?php echo htmlspecialchars($booking['gcash_ref'] ?? 'N/A'); ?></span></td>
                        <td>
                            <span class="status-badge <?php echo htmlspecialchars($booking['status']); ?>">
                                <?php echo htmlspecialchars($booking['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($booking['status'] === 'Pending' && !$is_expired): ?>
                                <form method="POST" class="action-form" onsubmit="return confirm('Approve booking? An email with the QR code will be sent to the guest.');">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <input type="hidden" name="new_status" value="Confirmed">
                                    <button type="submit" class="btn-action btn-confirm"><i class="fas fa-check"></i> Approve</button>
                                </form>
                                <form method="POST" class="action-form" onsubmit="return confirm('Reject booking? A cancellation email will be sent.');">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <input type="hidden" name="new_status" value="Cancelled">
                                    <button type="submit" class="btn-action btn-reject"><i class="fas fa-times"></i> Reject</button>
                                </form>
                            <?php elseif ($is_expired && $booking['status'] === 'Pending'): ?>
                                <span style="color: #ef4444; font-size: 12px; font-weight: bold; font-style: italic;"><i class="fas fa-ban"></i> Expired</span>
                            <?php else: ?>
                                <span style="color: #94a3b8; font-size: 12px; font-style: italic;">No actions</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align: center; color: #64748b;">No reservations found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- MENU MANAGEMENT -->
    <h2 class="section-title" style="margin-top: 60px;"><i class="fas fa-utensils"></i> Manage Food Menu</h2>
    <div class="form-box">
        <h3><?php echo $is_editing ? 'Edit Menu Item' : 'Add New Menu Item'; ?></h3>
        <form method="POST" enctype="multipart/form-data">
            <?php if($is_editing): ?>
                <input type="hidden" name="item_id" value="<?php echo $edit_data['id']; ?>">
                <input type="hidden" name="existing_image" value="<?php echo $edit_data['image_url']; ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div>
                    <label>Category</label>
                    <select name="category">
                        <option value="Specialties" <?php if($edit_data['category']=='Specialties') echo 'selected'; ?>>Specialties</option>
                        <option value="Combo Meal" <?php if($edit_data['category']=='Combo Meal') echo 'selected'; ?>>Combo Meal</option>
                        <option value="Finger Foods" <?php if($edit_data['category']=='Finger Foods') echo 'selected'; ?>>Finger Foods</option>
                        <option value="Drinks" <?php if($edit_data['category']=='Drinks') echo 'selected'; ?>>Drinks</option>
                    </select>
                </div>
                <div>
                    <label>Food/Drink Name</label>
                    <input type="text" name="name" required value="<?php echo htmlspecialchars($edit_data['name']); ?>">
                </div>
                <div>
                    <label>Price</label>
                    <input type="text" name="price" required value="<?php echo htmlspecialchars($edit_data['price']); ?>">
                </div>
                <div>
                    <label>Upload Photo</label>
                    <input type="file" name="photo" accept="image/*">
                    <?php if($is_editing) echo "<small style='color:green;'>Leave blank to keep existing photo.</small>"; ?>
                </div>
                <div class="full-width">
                    <label>Description</label>
                    <textarea name="description" rows="2"><?php echo htmlspecialchars($edit_data['description']); ?></textarea>
                </div>
            </div>
            <button type="submit" name="<?php echo $is_editing ? 'update_menu' : 'add_menu'; ?>" class="btn-green">
                <i class="fas fa-save"></i> <?php echo $is_editing ? 'Update Item' : 'Add to Menu'; ?>
            </button>
            <?php if($is_editing): ?> <a href="admin_dashboard.php" style="margin-left:10px; color:#475569;">Cancel</a> <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <table>
            <tr><th>Image</th><th>Name & Price</th><th>Action</th></tr>
            <?php foreach($menu_list as $row): ?>
            <tr>
                <td><img src="<?php echo $row['image_url']; ?>" class="item-img" onerror="this.src='https://placehold.co/100'"></td>
                <td><strong><?php echo $row['name']; ?></strong><br><span style="color:#059669;"><?php echo $row['price']; ?></span></td>
                <td>
                    <a href="admin_dashboard.php?edit_menu=<?php echo $row['id']; ?>" class="edit-btn">Edit</a>
                    <a href="admin_dashboard.php?delete_menu=<?php echo $row['id']; ?>" class="delete-btn">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- GALLERY MANAGEMENT -->
    <h2 class="section-title" style="margin-top: 60px;"><i class="fas fa-images"></i> Manage Gallery Photos</h2>
    <div class="form-box">
        <form method="POST" enctype="multipart/form-data">
            <label>Upload Photo for Gallery</label>
            <input type="file" name="gallery_photo" accept="image/*" required style="margin-bottom:10px;">
            <button type="submit" name="add_gallery" class="btn-green"><i class="fas fa-upload"></i> Upload to Gallery</button>
        </form>
    </div>

    <div class="table-container">
        <table>
            <tr><th>Gallery Photo</th><th>Action</th></tr>
            <?php foreach($gallery_list as $img): ?>
            <tr>
                <td><img src="<?php echo $img['image_path']; ?>" class="item-img" style="width: 100px; height: 60px;"></td>
                <td><a href="admin_dashboard.php?delete_gallery=<?php echo $img['id']; ?>" class="delete-btn">Delete</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- VIDEO TOUR MANAGEMENT -->
    <h2 class="section-title" style="margin-top: 60px;"><i class="fas fa-video"></i> Manage Resort Videos</h2>
    <div class="form-box">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div>
                    <label>Video Title</label>
                    <input type="text" name="video_title" required placeholder="Ex: Pool Amenities">
                </div>
                <div>
                    <label>Upload Video (MP4)</label>
                    <input type="file" name="resort_video" accept="video/mp4" required>
                </div>
            </div>
            <button type="submit" name="add_video" class="btn-green"><i class="fas fa-upload"></i> Upload Video</button>
        </form>
    </div>

    <div class="table-container">
        <table>
            <tr><th>Video Title</th><th>Action</th></tr>
            <?php foreach($video_list as $vid): ?>
            <tr>
                <td><strong><?php echo $vid['title']; ?></strong></td>
                <td><a href="admin_dashboard.php?delete_video=<?php echo $vid['id']; ?>" class="delete-btn">Delete</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
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

<!-- IPASA ANG BOOKING DATA GIKAN SA PHP PAINGON SA JAVASCRIPT ARON MA-CHECK ANG EXPIRATION INIG SCAN -->
<script>
    // JSON Data gikan sa database
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
            html5QrcodeScanner.clear();
            document.getElementById('scannerModal').style.display = 'none';
            
            // PANGITAON ANG BOOKING DATA SA ARRAY
            let booking = bookingsData.find(b => b.id == bookingId);

            if (booking) {
                // E-CHECK KUNG EXPIRED NA BA (Check-Out Date < Karon nga Petsa)
                if (booking.check_out < todayDate) {
                    alert("❌ QR Code Expired!\n\nThis QR Code belongs to a past date (" + booking.check_out + ") and can no longer be approved.");
                    return; // PAHUNONGON ANG CODE DIRI
                }

                // E-CHECK KUNG GI-CANCEL NA DAAN
                if (booking.status === 'Cancelled') {
                    alert("❌ Invalid!\n\nThis booking has already been cancelled.");
                    return; // PAHUNONGON ANG CODE DIRI
                }

                // KUNG OKAY ANG PETSA, IPANGUTANA KUNG I-APPROVE BA
                if (confirm("✅ QR Code Detected!\nBooking ID: #" + bookingId + "\nGuest Name: " + booking.user_name + "\n\nDo you want to APPROVE this reservation now?")) {
                    let form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `<input type="hidden" name="booking_id" value="${bookingId}"><input type="hidden" name="new_status" value="Confirmed">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            } else {
                alert("❌ Booking ID #" + bookingId + " not found in the system.");
            }
        } else {
            alert("❌ Invalid QR Code! This is not a CherryJoe River Park reservation.");
        }
    }
    
    function onScanFailure(error) {
        // I-ignore ang errors while nag-scan pa ang camera
    }
</script>
</body>
</html>
