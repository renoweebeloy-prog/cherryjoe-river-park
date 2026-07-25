<?php
session_start();
require 'db_connect.php';

// KUNG WALA LOG-IN O DILI ADMIN, E-KICK OUT PAINGON SA INDEX
if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'admin@cherryjoe.com') {
    header("Location: index.php");
    exit();
}

$message = '';

// --- 1. KUNG MAG ADD OG BAG-ONG PAGKAON ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_menu'])) {
    $category = $_POST['category'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $image_url = $_POST['image_url'];

    if (empty($image_url)) {
        $image_url = 'https://placehold.co/400x250?text=No+Image';
    }

    try {
        $stmt = $conn->prepare("INSERT INTO menu_items (category, name, description, price, image_url) VALUES (:category, :name, :desc, :price, :image_url)");
        $stmt->execute(['category' => $category, 'name' => $name, 'desc' => $desc, 'price' => $price, 'image_url' => $image_url]);
        $message = "<div class='success-msg'><i class='fas fa-check-circle'></i> Menu item added successfully!</div>";
    } catch(PDOException $e) {
        $message = "<div class='error-msg'><i class='fas fa-exclamation-circle'></i> Error adding menu: " . $e->getMessage() . "</div>";
    }
}

// --- 2. KUNG MAG UPDATE/EDIT OG PAGKAON ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_menu'])) {
    $id = $_POST['item_id'];
    $category = $_POST['category'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $image_url = $_POST['image_url'];

    if (empty($image_url)) {
        $image_url = 'https://placehold.co/400x250?text=No+Image';
    }

    try {
        $stmt = $conn->prepare("UPDATE menu_items SET category = :category, name = :name, description = :desc, price = :price, image_url = :image_url WHERE id = :id");
        $stmt->execute(['category' => $category, 'name' => $name, 'desc' => $desc, 'price' => $price, 'image_url' => $image_url, 'id' => $id]);
        $message = "<div class='success-msg'><i class='fas fa-check-circle'></i> Menu item updated successfully!</div>";
    } catch(PDOException $e) {
        $message = "<div class='error-msg'><i class='fas fa-exclamation-circle'></i> Error updating menu: " . $e->getMessage() . "</div>";
    }
}

// --- 3. KUNG MAG DELETE OG PAGKAON ---
if (isset($_GET['delete'])) {
    $id_to_delete = $_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = :id");
        $stmt->execute(['id' => $id_to_delete]);
        header("Location: admin_dashboard.php");
        exit();
    } catch(PDOException $e) {
        $message = "<div class='error-msg'><i class='fas fa-exclamation-circle'></i> Error deleting item.</div>";
    }
}

// --- 4. I-CHECK KUNG NAA BA SA "EDIT MODE" ---
$is_editing = false;
$edit_id = ''; $edit_category = 'Specialties'; $edit_name = ''; $edit_price = ''; $edit_desc = ''; $edit_image_url = '';

if (isset($_GET['edit'])) {
    $is_editing = true;
    $edit_id = $_GET['edit'];
    try {
        $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = :id");
        $stmt->execute(['id' => $edit_id]);
        $itemToEdit = $stmt->fetch();
        if ($itemToEdit) {
            $edit_category = $itemToEdit['category'];
            $edit_name = $itemToEdit['name'];
            $edit_price = $itemToEdit['price'];
            $edit_desc = $itemToEdit['description'];
            $edit_image_url = $itemToEdit['image_url'];
        }
    } catch(PDOException $e) {
        $message = "<div class='error-msg'>Error loading item.</div>";
    }
}

// Kuhaon ang tanang menu gikan sa database para i-display sa table
try {
    $stmt = $conn->query("SELECT * FROM menu_items ORDER BY id DESC");
    $menu_list = $stmt->fetchAll();
} catch(PDOException $e) {
    $menu_list = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CherryJoe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* --- PREMIUM UI CONFIG & RESET --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif; }
        body { background: linear-gradient(135deg, rgba(16,185,129,0.05), rgba(5,150,105,0.1)); padding: 40px 20px; color: #1e293b; min-height: 100vh; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #10b981; }

        .dashboard-container { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); padding: 40px; border-radius: 24px; box-shadow: 0 25px 50px rgba(0,0,0,0.05); max-width: 1100px; margin: 0 auto; border: 1px solid rgba(0,0,0,0.05); }
        .header-flex { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid rgba(16, 185, 129, 0.1); padding-bottom: 20px; margin-bottom: 30px; }
        .header-flex h1 { color: #059669; font-size: 28px; display: flex; align-items: center; gap: 12px; font-weight: 800; }
        
        .back-btn { background: #ffffff; color: #475569; text-decoration: none; padding: 12px 24px; border-radius: 50px; font-weight: 600; font-size: 14px; border: 1px solid #cbd5e1; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .back-btn:hover { background: #f8fafc; color: #059669; border-color: #059669; transform: translateY(-2px); box-shadow: 0 10px 15px rgba(0,0,0,0.05);}

        .error-msg { background: #fee2e2; color: #ef4444; padding: 15px; border-radius: 12px; font-size: 14px; margin-bottom: 25px; border: 1px solid #fca5a5; display: flex; align-items: center; gap: 10px; font-weight: 600;}
        .success-msg { background: #d1fae5; color: #059669; padding: 15px; border-radius: 12px; font-size: 14px; margin-bottom: 25px; border: 1px solid #a7f3d0; display: flex; align-items: center; gap: 10px; font-weight: 600;}

        /* ADD/EDIT FORM STYLES */
        .add-form-box { background: #ffffff; padding: 30px; border-radius: 20px; border: 1px solid rgba(0,0,0,0.06); margin-bottom: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border-top: 5px solid #059669;}
        .add-form-box.edit-mode { border-top: 5px solid #f59e0b; } /* Orange top border for Edit Mode */
        .add-form-box h3 { color: #1e293b; margin-bottom: 20px; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px;}
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 5px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;}
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 14px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 15px; outline: none; transition: 0.3s; background: #f8fafc; color: #1e293b; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #10b981; background: #ffffff; box-shadow: 0 0 0 4px rgba(16,185,129,0.1); }
        .full-width { grid-column: span 2; }
        .help-text { font-size: 11px; color: #94a3b8; display: block; margin-top: 5px; }

        .submit-btn { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 15px 35px; border-radius: 50px; font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.3s ease; box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.3); display: inline-flex; align-items: center; gap: 8px; margin-top: 20px; }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 15px 30px -5px rgba(16, 185, 129, 0.5); }
        
        .update-btn { background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 10px 25px -5px rgba(245, 158, 11, 0.3); }
        .update-btn:hover { box-shadow: 0 15px 30px -5px rgba(245, 158, 11, 0.5); }
        
        .cancel-btn { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 14px 25px; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 14px; margin-left: 10px; transition: 0.2s;}
        .cancel-btn:hover { background: #e2e8f0; color: #1e293b; }

        /* TABLE STYLES */
        .table-header-title { font-size: 20px; color: #1e293b; margin-bottom: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px;}
        .table-responsive { overflow-x: auto; background: #ffffff; border-radius: 20px; border: 1px solid rgba(0,0,0,0.06); box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th { background: #f8fafc; color: #64748b; font-weight: 700; font-size: 12px; text-transform: uppercase; padding: 18px 20px; text-align: left; border-bottom: 1px solid rgba(0,0,0,0.05); letter-spacing: 0.5px;}
        td { padding: 18px 20px; border-bottom: 1px solid rgba(0,0,0,0.03); vertical-align: middle; font-size: 15px; color: #475569; }
        tbody tr:hover { background: #f8fafc; }
        
        .item-img { width: 65px; height: 65px; object-fit: cover; border-radius: 12px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .category-badge { background: rgba(16, 185, 129, 0.1); color: #059669; padding: 6px 14px; border-radius: 50px; font-size: 12px; font-weight: 700; display: inline-block;}
        .item-name { font-weight: 700; color: #1e293b; font-size: 16px; margin-bottom: 4px; display: block;}
        .item-desc { font-size: 13px; color: #94a3b8; line-height: 1.4; }
        .price-tag { font-weight: 800; color: #059669; font-size: 16px;}
        
        .action-btns { display: flex; gap: 8px; }
        .edit-action-btn { background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; padding: 8px 12px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s;}
        .edit-action-btn:hover { background: #3b82f6; color: white; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(59, 130, 246, 0.2);}
        
        .delete-action-btn { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; padding: 8px 12px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s;}
        .delete-action-btn:hover { background: #ef4444; color: white; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(239, 68, 68, 0.2);}

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; gap: 15px;}
            .full-width { grid-column: span 1; }
            .header-flex { flex-direction: column; gap: 15px; align-items: flex-start; }
            .dashboard-container { padding: 25px 20px; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="header-flex">
        <h1><i class="fas fa-cogs"></i> Admin Dashboard</h1>
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Main App</a>
    </div>
    
    <?php echo $message; ?>

    <!-- ADD / EDIT MENU FORM -->
    <div class="add-form-box <?php echo $is_editing ? 'edit-mode' : ''; ?>" id="menu-form">
        <h3>
            <?php if($is_editing): ?>
                <i class="fas fa-edit" style="color: #f59e0b;"></i> Update Menu Item
            <?php else: ?>
                <i class="fas fa-plus-circle" style="color: #059669;"></i> Add New Menu Item
            <?php endif; ?>
        </h3>
        
        <form method="POST" action="admin_dashboard.php">
            <?php if($is_editing): ?>
                <!-- Hidden input para sa ID nga i-update -->
                <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($edit_id); ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="Specialties" <?php if($edit_category=='Specialties') echo 'selected'; ?>>Specialties</option>
                        <option value="Combo Meal" <?php if($edit_category=='Combo Meal') echo 'selected'; ?>>Combo Meal</option>
                        <option value="Finger Foods" <?php if($edit_category=='Finger Foods') echo 'selected'; ?>>Finger Foods</option>
                        <option value="Drinks" <?php if($edit_category=='Drinks') echo 'selected'; ?>>Drinks & Beverages</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="name" required placeholder="Ex: Grilled Panga" value="<?php echo htmlspecialchars($edit_name); ?>">
                </div>
                <div class="form-group">
                    <label>Price</label>
                    <input type="text" name="price" required placeholder="Ex: ₱250" value="<?php echo htmlspecialchars($edit_price); ?>">
                </div>
                <div class="form-group">
                    <label>Picture Link / Filename</label>
                    <input type="text" name="image_url" placeholder="Ex: https://link-to-picture.com/img.jpg" value="<?php echo htmlspecialchars($edit_image_url); ?>">
                    <span class="help-text">Tip: I-paste ang URL sa picture gikan sa Facebook/Imgur, o i-type ang filename (ex: images/food.jpg) kung na-upload nimo sa Github.</span>
                </div>
                <div class="form-group full-width">
                    <label>Description (Optional)</label>
                    <textarea name="description" rows="2" placeholder="Ex: Freshly grilled tuna with spices..."><?php echo htmlspecialchars($edit_desc); ?></textarea>
                </div>
            </div>
            
            <?php if($is_editing): ?>
                <button type="submit" name="update_menu" class="submit-btn update-btn"><i class="fas fa-save"></i> Update Item</button>
                <a href="admin_dashboard.php" class="cancel-btn">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_menu" class="submit-btn"><i class="fas fa-plus"></i> Add Item to Menu</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- DISPLAY MENU LIST -->
    <h3 class="table-header-title"><i class="fas fa-list-ul" style="color: #059669;"></i> Current Menu Details</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Picture</th>
                    <th>Category</th>
                    <th>Food/Drink Details</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($menu_list) > 0): ?>
                    <?php foreach($menu_list as $row): ?>
                    <tr>
                        <td>
                            <img src="<?php echo htmlspecialchars($row['image_url']); ?>" class="item-img" onerror="this.src='https://placehold.co/400x250?text=No+Image'">
                        </td>
                        <td><span class="category-badge"><?php echo htmlspecialchars($row['category']); ?></span></td>
                        <td>
                            <span class="item-name"><?php echo htmlspecialchars($row['name']); ?></span>
                            <?php if(!empty($row['description'])): ?>
                                <div class="item-desc"><?php echo htmlspecialchars($row['description']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="price-tag"><?php echo htmlspecialchars($row['price']); ?></span></td>
                        <td>
                            <div class="action-btns">
                                <!-- EDIT BUTTON -->
                                <a href="admin_dashboard.php?edit=<?php echo $row['id']; ?>#menu-form" class="edit-action-btn">
                                    <i class="fas fa-pen"></i> Edit
                                </a>
                                <!-- DELETE BUTTON -->
                                <a href="admin_dashboard.php?delete=<?php echo $row['id']; ?>" class="delete-action-btn" onclick="return confirm('Are you sure you want to delete <?php echo addslashes($row['name']); ?>?');">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 40px; font-weight: 600;">
                            <i class="fas fa-folder-open" style="font-size: 30px; margin-bottom: 10px; display: block;"></i>
                            No menu items found. Add one above!
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
