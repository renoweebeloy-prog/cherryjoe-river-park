<?php
session_start();
require 'db_connect.php';

// KUNG WALA LOG-IN O DILI ADMIN, E-KICK OUT PAINGON SA INDEX
if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'admin@cherryjoe.com') {
    header("Location: index.php");
    exit();
}

// ... (ug ang sunod nimo nga PHP/HTML codes para sa admin)
$message = '';

// KUNG MAG ADD OG PAGKAON ANG ADMIN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_menu'])) {
    $category = $_POST['category'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $image_url = $_POST['image_url'];

    $stmt = $conn->prepare("INSERT INTO menu_items (category, name, description, price, image_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $category, $name, $desc, $price, $image_url);
    
    if ($stmt->execute()) {
        $message = "<div style='color: green; padding: 10px; background: #d1fae5; border-radius: 5px;'>Menu item added successfully!</div>";
    } else {
        $message = "<div style='color: red; padding: 10px; background: #fee2e2; border-radius: 5px;'>Error adding menu item.</div>";
    }
    $stmt->close();
}

// KUNG MAG DELETE OG PAGKAON ANG ADMIN
if (isset($_GET['delete'])) {
    $id_to_delete = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// Kuhaon ang tanang menu gikan sa database para i-display
$menu_list = $conn->query("SELECT * FROM menu_items ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CherryJoe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; padding: 20px; color: #1e293b; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        h1 { color: #059669; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 5px; }
        .btn { background: #059669; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn:hover { background: #047857; }
        .back-btn { display: inline-block; margin-bottom: 20px; color: #64748b; text-decoration: none; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { padding: 12px; border: 1px solid #e2e8f0; text-align: left; }
        th { background: #f1f5f9; color: #334155; }
        .delete-btn { color: white; background: #ef4444; padding: 5px 10px; text-decoration: none; border-radius: 5px; font-size: 12px; }
        .delete-btn:hover { background: #dc2626; }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Main Website</a>
    <h1><i class="fas fa-cogs"></i> Admin Dashboard - Menu Manager</h1>
    
    <?php echo $message; ?>

    <!-- ADD MENU FORM -->
    <div style="background: #f8fafc; padding: 20px; border-radius: 10px; margin-top: 20px; border: 1px solid #e2e8f0;">
        <h3>Add New Food / Drink</h3>
        <form method="POST" action="">
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
                <label>Description (Optional, good for combo meals)</label>
                <textarea name="description" rows="2" placeholder="Ex: With ice cream on top"></textarea>
            </div>
            <div class="form-group">
                <label>Price</label>
                <input type="text" name="price" required placeholder="Ex: ₱150">
            </div>
            <div class="form-group">
                <label>Image Filename / URL</label>
                <input type="text" name="image_url" placeholder="Ex: imageshalohalo.jpg" value="https://placehold.co/400x250?text=Food">
            </div>
            <button type="submit" name="add_menu" class="btn"><i class="fas fa-plus"></i> Add Item</button>
        </form>
    </div>

    <!-- DISPLAY MENU LIST -->
    <h3 style="margin-top: 40px;">Current Menu Items</h3>
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Category</th>
                <th>Name</th>
                <th>Price</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $menu_list->fetch_assoc()): ?>
            <tr>
                <td><img src="<?php echo htmlspecialchars($row['image_url']); ?>" width="50" style="border-radius:5px;"></td>
                <td><?php echo htmlspecialchars($row['category']); ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['price']); ?></td>
                <td>
                    <a href="admin_dashboard.php?delete=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this item?');">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
