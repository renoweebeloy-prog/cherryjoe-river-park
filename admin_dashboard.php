<?php
session_start();
require 'db_connect.php';

// KUNG WALA LOG-IN O DILI ADMIN, E-KICK OUT PAINGON SA INDEX
if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'admin@cherryjoe.com') {
    header("Location: index.php");
    exit();
}

$message = '';

// KUNG MAG ADD OG PAGKAON ANG ADMIN (Gamit ang PDO)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_menu'])) {
    $category = $_POST['category'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $image_url = $_POST['image_url'];

    // Kung walay gibutang nga image, gamitan og placeholder
    if (empty($image_url)) {
        $image_url = 'https://placehold.co/400x250?text=No+Image';
    }

    try {
        $stmt = $conn->prepare("INSERT INTO menu_items (category, name, description, price, image_url) VALUES (:category, :name, :desc, :price, :image_url)");
        $stmt->execute([
            'category' => $category,
            'name' => $name,
            'desc' => $desc,
            'price' => $price,
            'image_url' => $image_url
        ]);
        $message = "<div class='success-msg'><i class='fas fa-check-circle'></i> Menu item added successfully!</div>";
    } catch(PDOException $e) {
        $message = "<div class='error-msg'><i class='fas fa-exclamation-circle'></i> Error adding menu: " . $e->getMessage() . "</div>";
    }
}

// KUNG MAG DELETE OG PAGKAON ANG ADMIN (Gamit ang PDO)
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

// Kuhaon ang tanang menu gikan sa database para i-display
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
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, sans-serif; }
        
        body { 
            background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(5,150,105,0.2)), url('imagesgallery7.jpg') center/cover fixed; 
            padding: 40px 20px;
            color: #1e293b;
        }

        .dashboard-container { 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(16px); 
            -webkit-backdrop-filter: blur(16px);
            padding: 40px; 
            border-radius: 24px; 
            box-shadow: 0 25px 50px rgba(0,0,0,0.1); 
            max-width: 1000px; 
            margin: 0 auto;
        }

        .header-flex { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid rgba(16, 185, 129, 0.2); padding-bottom: 20px; margin-bottom: 30px; }
        .header-flex h1 { color: #059669; font-size: 28px; display: flex; align-items: center; gap: 10px; }
        
        .back-btn { 
            background: rgba(255, 255, 255, 0.8); 
            color: #475569; 
            text-decoration: none; 
            padding: 10px 20px; 
            border-radius: 50px; 
            font-weight: 600; 
            font-size: 14px; 
            border: 1px solid #cbd5e1;
            transition: 0.3s;
        }
        .back-btn:hover { background: #f8fafc; color: #1e293b; border-color: #94a3b8; }

        .error-msg { background: #fee2e2; color: #ef4444; padding: 12px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; border: 1px solid #fca5a5; display: flex; align-items: center; gap: 8px; font-weight: 600;}
        .success-msg { background: #d1fae5; color: #059669; padding: 12px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; border: 1px solid #a7f3d0; display: flex; align-items: center; gap: 8px; font-weight: 600;}

        /* FORM STYLES */
        .add-form-box { background: #f8fafc; padding: 30px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 40px; }
        .add-form-box h3 { color: #1e293b; margin-bottom: 20px; font-size: 20px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;}
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 15px; outline: none; transition: 0.3s; 
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }
        .full-width { grid-column: span 2; }

        .submit-btn { 
            background: linear-gradient(135deg, #10b981, #059669); 
            color: white; border: none; padding: 15px 30px; border-radius: 50px; font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.3s ease; box-shadow: 0 10px 20px rgba(16, 185, 129, 0.25); display: inline-flex; align-items: center; gap: 8px;
        }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 15px 25px rgba(16, 185, 129, 0.4); }

        /* TABLE STYLES */
        .table-header-title { font-size: 22px; color: #1e293b; margin-bottom: 15px; }
        .table-responsive { overflow-x: auto; background: white; border-radius: 15px; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th { background: #f8fafc; color: #475569; font-weight: 700; font-size: 13px; text-transform: uppercase; padding: 15px; text-align: left; border-bottom: 2px solid #e2e8f0; }
        td { padding: 15px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; font-size: 15px; }
        tbody tr:hover { background: #f8fafc; }
        .item-img { width: 60px; height: 60px; object-fit: cover; border-radius: 10px; border: 1px solid #cbd5e1; }
        .category-badge { background: rgba(16, 185, 129, 0.1); color: #059669; padding: 5px 10px; border-radius: 50px; font-size: 12px; font-weight: 700; }
        
        .delete-btn { 
            background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; padding: 8px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s;
        }
        .delete-btn:hover { background: #ef4444; color: white; }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .header-flex { flex-direction: column; gap: 15px; align-items: flex-start; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="header-flex">
        <h1><i class="fas fa-cogs"></i> Admin Menu Dashboard</h1>
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Main Website</a>
    </div>
    
    <?php echo $message; ?>

    <!-- ADD MENU FORM -->
    <div class="add-form-box">
        <h3><i class="fas fa-plus-circle" style="color: #059669;"></i> Add New Food / Drink</h3>
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="Specialties">Specialties</option>
                        <option value="Combo Meal">Combo Meal</option>
                        <option value="Finger Foods">Finger Foods</option>
                        <option value="Drinks">Drinks & Beverages</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="name" required placeholder="Ex: Halo-Halo">
                </div>
                <div class="form-group">
                    <label>Price</label>
                    <input type="text" name="price" required placeholder="Ex: ₱150">
                </div>
                <div class="form-group">
                    <label>Image Filename / URL (Optional)</label>
                    <input type="text" name="image_url" placeholder="Ex: imageshalohalo.jpg">
                </div>
                <div class="form-group full-width">
                    <label>Description (Optional, good for combo meals)</label>
                    <textarea name="description" rows="2" placeholder="Ex: With ice cream on top"></textarea>
                </div>
            </div>
            <button type="submit" name="add_menu" class="submit-btn"><i class="fas fa-save"></i> Save Item to Menu</button>
        </form>
    </div>

    <!-- DISPLAY MENU LIST -->
    <h3 class="table-header-title"><i class="fas fa-list"></i> Current Menu Items</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Category</th>
                    <th>Name & Description</th>
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
                            <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                            <span style="color: #64748b; font-size: 13px;"><?php echo htmlspecialchars($row['description']); ?></span>
                        </td>
                        <td style="font-weight: bold; color: #059669;"><?php echo htmlspecialchars($row['price']); ?></td>
                        <td>
                            <a href="admin_dashboard.php?delete=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this item?');">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b; padding: 30px;">No menu items found. Add one above!</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
