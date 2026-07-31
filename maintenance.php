<?php
session_start();

$maintenance_file = 'maintenance_mode.txt';
$is_maintenance = file_exists($maintenance_file) && file_get_contents($maintenance_file) === "1";

// Kung gi-OFF na sa Admin ang maintenance, i-balik sila diretso sa login page
if (!$is_maintenance) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance - CherryJoe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { margin: 0; padding: 0; height: 100vh; display: flex; justify-content: center; align-items: center; background: linear-gradient(135deg, #f8fafc, #e2e8f0); font-family: "Segoe UI", Tahoma, sans-serif; text-align: center; color: #1e293b; }
        .maintenance-box { background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 15px 30px rgba(0,0,0,0.05); max-width: 450px; border: 2px dashed #cbd5e1; margin: 20px; position: relative; }
        .icon { font-size: 60px; color: #059669; margin-bottom: 20px; animation: bounce 2s infinite; }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        h1 { font-size: 28px; margin-bottom: 10px; font-weight: 900; }
        p { color: #64748b; font-size: 16px; line-height: 1.6; margin-bottom: 20px; }
        
        /* SEKRETO NGA BUTTON PARA SA ADMIN */
        .secret-admin { position: absolute; bottom: 10px; right: 15px; color: #e2e8f0; text-decoration: none; font-size: 16px; transition: 0.3s; padding: 10px; }
        .secret-admin:hover { color: #059669; transform: scale(1.2); }
    </style>
</head>
<body>
    <div class="maintenance-box">
        <div class="icon"><i class="fas fa-tools"></i></div>
        <h1>Under Maintenance</h1>
        <p>We are currently upgrading CherryJoe River Park website to serve you better. Please check back soon!</p>
        
        <!-- I-CLICK LANG NING PADLOCK PARA MAKA-SULOD KA SA LOGIN.PHP -->
        <a href="login.php?admin=true" class="secret-admin" title="Admin Bypass"><i class="fas fa-lock"></i></a>
    </div>
</body>
</html>
