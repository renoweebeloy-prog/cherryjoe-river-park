<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'admin@cherryjoe.com') {
    header("Location: index.php");
    exit();
}

$message = '';
$upload_dir = 'uploads/';

// Siguroha nga naay 'uploads' folder
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Function para sayon ang pag-upload sa file
function uploadFile($fileInputName, $uploadDir) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.-]/", "_", $_FILES[$fileInputName]['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetPath)) {
            return $targetPath;
        }
    }
    return false;
}

// ==========================================
// 1. MENU MANAGEMENT (CRUD + File Upload)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_menu'])) {
    $category = $_POST['category'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    
    // I-upload ang photo
    $image_url = uploadFile('photo', $upload_dir);
    if (!$image_url) $image_url = 'https://placehold.co/400x250?text=No+Image';

    try {
        $stmt = $conn->prepare("INSERT INTO menu_items (category, name, description, price, image_url) VALUES (:category, :name, :desc, :price, :image_url)");
        $stmt->execute(['category' => $category, 'name' => $name, 'desc' => $desc, 'price' => $price, 'image_url' => $image_url]);
        $message = "<div class='success-msg'><i class='fas fa-check-circle'></i> Menu item added successfully!</div>";
    } catch(PDOException $e) {
        $message = "<div class='error-msg'>Error adding menu: " . $e->getMessage() . "</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_menu'])) {
    $id = $_POST['item_id'];
    $category = $_POST['category'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $image_url = $_POST['existing_image'];

    // Kung naay gi-upload nga bag-ong photo, ilisan ang daan
    $new_photo = uploadFile('photo', $upload_dir);
    if ($new_photo) {
        $image_url = $new_photo;
    }

    try {
        $stmt = $conn->prepare("UPDATE menu_items SET category = :category, name = :name, description = :desc, price = :price, image_url = :image_url WHERE id = :id");
        $stmt->execute(['category' => $category, 'name' => $name, 'desc' => $desc, 'price' => $price, 'image_url' => $image_url, 'id' => $id]);
        $message = "<div class='success-msg'><i class='fas fa-check-circle'></i> Menu item updated!</div>";
    } catch(PDOException $e) {
        $message = "<div class='error-msg'>Error updating menu.</div>";
    }
}

if (isset($_GET['delete_menu'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = :id");
        $stmt->execute(['id' => $_GET['delete_menu']]);
        header("Location: admin_dashboard.php"); exit();
    } catch(PDOException $e) {}
}

// ==========================================
// 2. GALLERY MANAGEMENT
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_gallery'])) {
    $photo_path = uploadFile('gallery_photo', $upload_dir);
    if ($photo_path) {
        try {
            $stmt = $conn->prepare("INSERT INTO gallery (image_path) VALUES (:path)");
            $stmt->execute(['path' => $photo_path]);
            $message = "<div class='success-msg'><i class='fas fa-check-circle'></i> Photo added to Gallery!</div>";
        } catch(PDOException $e) {}
    } else {
        $message = "<div class='error-msg'>Failed to upload photo.</div>";
    }
}

if (isset($_GET['delete_gallery'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM gallery WHERE id = :id");
        $stmt->execute(['id' => $_GET['delete_gallery']]);
        header("Location: admin_dashboard.php"); exit();
    } catch(PDOException $e) {}
}

// ==========================================
// 3. VIDEO TOUR MANAGEMENT
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_video'])) {
    $title = $_POST['video_title'];
    $video_path = uploadFile('resort_video', $upload_dir);
    if ($video_path) {
        try {
            $stmt = $conn->prepare("INSERT INTO videos (title, video_path) VALUES (:title, :path)");
            $stmt->execute(['title' => $title, 'path' => $video_path]);
            $message = "<div class='success-msg'><i class='fas fa-check-circle'></i> Video added to Explore Section!</div>";
        } catch(PDOException $e) {}
    } else {
        $message = "<div class='error-msg'>Failed to upload video. Ensure file size is small.</div>";
    }
}

if (isset($_GET['delete_video'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM videos WHERE id = :id");
        $stmt->execute(['id' => $_GET['delete_video']]);
        header("Location: admin_dashboard.php"); exit();
    } catch(PDOException $e) {}
}

// --- FETCH DATA FOR DISPLAY ---
$is_editing = false;
$edit_data = ['id'=>'', 'category'=>'Specialties', 'name'=>'', 'price'=>'', 'description'=>'', 'image_url'=>''];

if (isset($_GET['edit_menu'])) {
    $is_editing = true;
    try {
        $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = :id");
        $stmt->execute(['id' => $_GET['edit_menu']]);
        if ($item = $stmt->fetch()) $edit_data = $item;
    } catch(PDOException $e) {}
}

$menu_list = $conn->query("SELECT * FROM menu_items ORDER BY id DESC")->fetchAll();
$gallery_list = $conn->query("SELECT * FROM gallery ORDER BY id DESC")->fetchAll();
$video_list = $conn->query("SELECT * FROM videos ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CherryJoe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, sans-serif; }
        body { background: linear-gradient(135deg, rgba(16,185,129,0.05), rgba(5,150,105,0.1)); padding: 40px 20px; color: #1e293b; }
        .dashboard-container { background: #fff; padding: 40px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); max-width: 1100px; margin: 0 auto; }
        .header-flex { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid rgba(16, 185, 129, 0.1); padding-bottom: 20px; margin-bottom: 30px; }
        .header-flex h1 { color: #059669; font-size: 28px; display: flex; align-items: center; gap: 12px; }
        .back-btn { background: #f8fafc; color: #475569; padding: 10px 20px; border-radius: 50px; text-decoration: none; border: 1px solid #cbd5e1; font-weight: 600; }
        .back-btn:hover { background: #059669; color: white; }
        
        .section-title { font-size: 20px; margin: 40px 0 15px 0; color: #1e293b; border-left: 5px solid #059669; padding-left: 10px;}
        .form-box { background: #f8fafc; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0; margin-bottom: 20px;}
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full-width { grid-column: span 2; }
        label { font-size: 13px; font-weight: bold; color: #64748b; display: block; margin-bottom: 5px; text-transform: uppercase;}
        input[type="text"], input[type="file"], select, textarea { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: white; }
        .btn-green { background: #059669; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 15px; }
        .btn-green:hover { background: #047857; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: white; border: 1px solid #e2e8f0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f1f5f9; color: #475569; }
        .item-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .delete-btn { color: #ef4444; text-decoration: none; font-weight: bold; font-size: 14px;}
        .edit-btn { color: #3b82f6; text-decoration: none; font-weight: bold; margin-right: 10px; font-size: 14px;}
        
        .success-msg { background: #d1fae5; color: #059669; padding: 15px; border-radius: 8px; margin-bottom: 20px;}
        .error-msg { background: #fee2e2; color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px;}
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="header-flex">
        <h1><i class="fas fa-cogs"></i> Admin Content Manager</h1>
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to App</a>
    </div>
    
    <?php echo $message; ?>

    <!-- ============================================== -->
    <!-- SECTION 1: FOOD MENU -->
    <!-- ============================================== -->
    <h2 class="section-title"><i class="fas fa-utensils"></i> Manage Food Menu</h2>
    <div class="form-box">
        <h3><?php echo $is_editing ? 'Edit Menu Item' : 'Add New Menu Item'; ?></h3>
        <!-- IMPORTANTE ANG enctype="multipart/form-data" PARA MAKA UPLOAD OG FILE -->
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

    <!-- ============================================== -->
    <!-- SECTION 2: GALLERY PHOTOS -->
    <!-- ============================================== -->
    <h2 class="section-title" style="margin-top: 60px;"><i class="fas fa-images"></i> Manage Gallery Photos</h2>
    <div class="form-box">
        <form method="POST" enctype="multipart/form-data">
            <label>Upload Photo for Gallery</label>
            <input type="file" name="gallery_photo" accept="image/*" required style="margin-bottom:10px;">
            <button type="submit" name="add_gallery" class="btn-green"><i class="fas fa-upload"></i> Upload to Gallery</button>
        </form>
    </div>

    <table>
        <tr><th>Gallery Photo</th><th>Action</th></tr>
        <?php foreach($gallery_list as $img): ?>
        <tr>
            <td><img src="<?php echo $img['image_path']; ?>" class="item-img" style="width: 100px; height: 60px;"></td>
            <td><a href="admin_dashboard.php?delete_gallery=<?php echo $img['id']; ?>" class="delete-btn">Delete</a></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- ============================================== -->
    <!-- SECTION 3: EXPLORE VIDEOS -->
    <!-- ============================================== -->
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
</body>
</html>
