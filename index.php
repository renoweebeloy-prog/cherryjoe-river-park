<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// 1. MAINTENANCE MODE & ADMIN BYPASS
// ==========================================
if (isset($_GET['admin']) && $_GET['admin'] === 'true') { 
    $_SESSION['admin_bypass'] = true; 
}
if (empty($_SESSION['admin_bypass'])) { 
    header("Location: maintenance.php"); 
    exit(); 
}
// ==========================================
// API: SERVER-SIDE GAME DATA SYNC
// ==========================================
$slots_file = 'game_slots.txt';
$stats_file = 'game_stats.json';
$today = date('Y-m-d');

if (!file_exists($stats_file)) { file_put_contents($stats_file, json_encode(['date' => $today, 'users' => []])); }
$stats_data = json_decode(file_get_contents($stats_file), true);

if ($stats_data['date'] !== $today) {
    $stats_data = ['date' => $today, 'users' => []];
    file_put_contents($stats_file, json_encode($stats_data), LOCK_EX);
}

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'deduct_slot') {
        $s = file_exists($slots_file) ? (int)file_get_contents($slots_file) : 0;
        if ($s > 0) file_put_contents($slots_file, $s - 1, LOCK_EX);
        exit("OK");
    }
    if ($_GET['action'] == 'update_stat' && isset($_GET['email'])) {
        $em = $_GET['email'];
        $type = $_GET['type'];
        $val = $_GET['val'];
        
        if (!isset($stats_data['users'][$em])) {
            $stats_data['users'][$em] = ['attempts' => 5, 'goal' => 2000, 'highscore' => 0];
        }
        if ($type == 'attempt') $stats_data['users'][$em]['attempts'] = (int)$val;
        if ($type == 'goal') $stats_data['users'][$em]['goal'] = (int)$val;
        if ($type == 'highscore') $stats_data['users'][$em]['highscore'] = (int)$val;
        
        file_put_contents($stats_file, json_encode($stats_data), LOCK_EX);
        exit("OK");
    }
}

require 'db_connect.php';

// ==========================================
// MAINTENANCE MODE CHECKER
// ==========================================
$maintenance_file = 'maintenance_mode.txt';
$is_maintenance = file_exists($maintenance_file) && file_get_contents($maintenance_file) === "1";

$isAdmin = (isset($_SESSION['email']) && $_SESSION['email'] === 'admin@cherryjoe.com');

if ($is_maintenance && !$isAdmin) {
    header("Location: maintenance.php");
    exit();
}

// ==========================================
// LOGIN STATUS
// ==========================================
$isLoggedIn = isset($_SESSION['user_id']);

// HANDLE PROFILE PICTURE UPLOAD (LOGGED-IN USERS ONLY)
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_pic'])) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
    $fileName = time() . '_' . basename($_FILES['profile_pic']['name']);
    $targetFilePath = $uploadDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    $allowTypes = array('jpg','png','jpeg','gif');
    if(in_array(strtolower($fileType), $allowTypes)){
        if(move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFilePath)){
            $updateStmt = $conn->prepare("UPDATE users SET profile_pic = :pic WHERE id = :id");
            $updateStmt->execute(['pic' => $targetFilePath, 'id' => $_SESSION['user_id']]);
        }
    }
}

// ==========================================
// USER DATA - GUEST OR LOGGED-IN USER
// ==========================================
$currentUser = null;
$userName = 'Guest';
$userEmail = '';
$profilePic = null;
$userRole = 'Visitor';

if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT full_name, email, profile_pic FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $currentUser = $stmt->fetch();

    $userName = $currentUser['full_name'] ?? 'Guest';
    $userEmail = $currentUser['email'] ?? '';
    $profilePic = $currentUser['profile_pic'] ?? null;
    $userRole = $isAdmin ? 'Admin' : 'Visitor';
}

$nameParts = explode(' ', trim($userName));
$initials = strtoupper(substr($nameParts[0] ?? 'G', 0, 1));
if (isset($nameParts[1])) {
    $initials .= strtoupper(substr($nameParts[1], 0, 1));
}

// ==========================================
// USER BOOKINGS
// ==========================================
$userBookings = [];
if ($isLoggedIn) {
    try {
        $bookingStmt = $conn->prepare("SELECT * FROM bookings WHERE user_id = :uid ORDER BY created_at DESC");
        $bookingStmt->execute(['uid' => $_SESSION['user_id']]);
        $userBookings = $bookingStmt->fetchAll();
    } catch(PDOException $e) {}
}

// ==========================================
// GAME STATS
// ==========================================
$u_attempts = 5;
$u_goal = 2000;
$u_highscore = 0;

if ($isLoggedIn && !empty($userEmail)) {
    if (!isset($stats_data['users'][$userEmail])) {
        $stats_data['users'][$userEmail] = ['attempts' => 5, 'goal' => 2000, 'highscore' => 0];
        file_put_contents($stats_file, json_encode($stats_data), LOCK_EX);
    }

    $u_attempts = $stats_data['users'][$userEmail]['attempts'];
    $u_goal = $stats_data['users'][$userEmail]['goal'];
    $u_highscore = $stats_data['users'][$userEmail]['highscore'];
}

$current_game_slots = file_exists($slots_file) ? (int)file_get_contents($slots_file) : 10;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CherryJoe River Park</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif; -webkit-tap-highlight-color: transparent; }
        html { scroll-behavior: smooth; }
        body { background: #ffffff; color: #1e293b; padding-top: 60px; overflow-x: hidden; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #ffffff; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #10b981; }

        .music-control-btn { position: fixed; bottom: 20px; right: 20px; width: 50px; height: 50px; background: linear-gradient(135deg, #10b981, #059669); color: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 20px; cursor: pointer; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4); z-index: 9999; transition: all 0.3s ease; border: 2px solid rgba(255, 255, 255, 0.5); }
        .music-control-btn:hover { transform: scale(1.1); }
        .music-control-btn.playing { animation: pulseMusic 1.5s infinite ease-in-out; background: linear-gradient(135deg, #ef4444, #b91c1c); box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4); }
        @keyframes pulseMusic { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.08); } }

        #preloader { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #f8fafc; z-index: 1000000; display: flex; flex-direction: column; justify-content: center; align-items: center; transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.6s; }
        .loader-spinner { width: 55px; height: 55px; border: 3px solid rgba(16, 185, 129, 0.1); border-radius: 50%; border-top-color: #059669; animation: spin 0.7s cubic-bezier(0.42, 0, 0.58, 1) infinite; margin-bottom: 20px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loader-text { color: #059669; font-size: 13px; letter-spacing: 2px; font-weight: 600; text-transform: uppercase; animation: pulseText 1.5s infinite ease-in-out; }
        @keyframes pulseText { 0%, 100% { opacity: 0.6; } 50% { opacity: 1; color: #10b981; } }

        .reveal { opacity: 0; transform: translateY(35px) scale(0.98); transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
        .reveal.active { opacity: 1; transform: translateY(0) scale(1); }

        .welcome-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at center, rgba(248, 250, 252, 0.92), rgba(255, 255, 255, 0.99)); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); z-index: 99999; display: none; justify-content: center; align-items: center; padding: 20px; transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
        .welcome-container { max-width: 480px; width: 100%; max-height: 92vh; overflow-y: auto; padding: 10px 5px; text-align: center; }
        .welcome-container h1 { font-size: 32px; color: #1e293b; margin-bottom: 8px; font-weight: 800; letter-spacing: -0.5px; }
        .welcome-container .subtitle { font-size: 14px; color: #64748b; margin-bottom: 25px; line-height: 1.5; }
        .feature-table-box { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(20px); border-radius: 24px; padding: 22px; margin-bottom: 18px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); border: 1px solid rgba(0, 0, 0, 0.08); text-align: left; opacity: 0; }
        .table-one { animation: tableEntrance 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.2s forwards; }
        .table-two { animation: tableEntrance 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.4s forwards; }
        @keyframes tableEntrance { 0% { transform: translateY(40px) scale(0.96); opacity: 0; } 100% { transform: translateY(0) scale(1); opacity: 1; } }
        .table-header { font-size: 13px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; border-bottom: 1px solid rgba(0, 0, 0, 0.05); padding-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .table-row-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .table-cell { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); padding: 16px; border-radius: 16px; display: flex; flex-direction: column; gap: 6px; opacity: 0; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .table-cell:hover { border-color: #059669; background: #f8fafc; transform: translateY(-4px); box-shadow: 0 10px 20px rgba(16, 185, 129, 0.1); }
        .table-one .cell-1 { animation: cellEntrance 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.5s forwards; }
        .table-one .cell-2 { animation: cellEntrance 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.6s forwards; }
        .table-two .cell-1 { animation: cellEntrance 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.7s forwards; }
        .table-two .cell-2 { animation: cellEntrance 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.8s forwards; }
        @keyframes cellEntrance { 0% { transform: translateY(15px); opacity: 0; } 100% { transform: translateY(0); opacity: 1; } }
        .table-cell i { font-size: 22px; margin-bottom: 2px; }
        .table-cell span { font-size: 14px; font-weight: 600; color: #1e293b; }
        .table-cell a { font-size: 12px; color: #0284c7; text-decoration: none; font-weight: 600; cursor: pointer; margin-top: 2px; }
        .table-cell a:hover { text-decoration: underline; color: #0369a1; }
        .welcome-btn { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 16px 30px; font-size: 15px; font-weight: 700; border-radius: 50px; cursor: pointer; box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.3); width: 100%; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 10px; opacity: 0; animation: cellEntrance 0.6s cubic-bezier(0.16, 1, 0.3, 1) 1.0s forwards; transition: all 0.3s ease; }
        .welcome-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 30px -5px rgba(16, 185, 129, 0.5); }
        .welcome-overlay.hide-welcome { opacity: 0 !important; visibility: hidden !important; pointer-events: none !important; }
        .welcome-overlay.hide-welcome .welcome-container { transform: scale(0.92) translateY(-20px); opacity: 0; transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
        /* Explore is the public landing page; do not block visitors with the old welcome overlay. */
        #welcomeOverlay { display: none !important; }

        .app-page { display: none; opacity: 0; transform: scale(0.98) translateY(15px); transition: opacity 0.45s cubic-bezier(0.16, 1, 0.3, 1), transform 0.45s cubic-bezier(0.16, 1, 0.3, 1); }
        .app-page.page-active { display: block; opacity: 1; transform: scale(1) translateY(0); }

        nav { position: fixed; top: 0; width: 100%; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border-bottom: 1px solid rgba(0, 0, 0, 0.05); padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; z-index: 1000; height: 60px; }
        .nav-left { display: flex; align-items: center; gap: 15px; color: #1e293b; }
        .menu-toggle-btn { background: #10b981; color: white; width: 40px; height: 40px; border-radius: 10px; display: flex; justify-content: center; align-items: center; font-size: 20px; cursor: pointer; border: 1px solid #fff; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2); transition: 0.3s; }
        .menu-toggle-btn:hover { background: #059669; transform: scale(1.05); }
        .logo { font-size: 20px; font-weight: bold; color: #1e293b; white-space: nowrap; margin-left: 5px;}
        .top-auth { display: flex; align-items: center; gap: 10px; }
        .top-auth-btn { text-decoration: none; padding: 9px 16px; border-radius: 999px; font-size: 13px; font-weight: 700; transition: all 0.25s ease; border: 1px solid #cbd5e1; }
        .top-auth-btn.login { background: #ffffff; color: #1e293b; }
        .top-auth-btn.signup { background: #059669; border-color: #059669; color: #ffffff; box-shadow: 0 5px 14px rgba(5,150,105,0.18); }
        .top-auth-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(15,23,42,0.08); }
        .top-auth-user { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; color:#1e293b; }
        .top-auth-avatar { width:34px; height:34px; border-radius:50%; overflow:hidden; border:2px solid rgba(5,150,105,0.2); display:flex; align-items:center; justify-content:center; background:#10b981; color:#fff; font-weight:800; }
        .top-auth-avatar img { width:100%; height:100%; object-fit:cover; }
        section { padding: 50px 5%; max-width: 1200px; margin: 0 auto; }
        .title { text-align: center; font-size: 30px; margin-bottom: 35px; color: #1e293b; font-weight: 800; }

        .about-intro { text-align: center; max-width: 800px; margin: 0 auto 40px auto; color: #475569; font-size: 16px; line-height: 1.8; padding: 25px; background: rgba(16, 185, 129, 0.05); border-radius: 16px; border: 1px dashed rgba(16, 185, 129, 0.3); box-shadow: 0 10px 20px rgba(0,0,0,0.02); }
        .about-intro strong { color: #059669; font-size: 18px; }
        .section-subtitle { text-align: center; font-size: 22px; color: #1e293b; margin-bottom: 25px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .feature-card { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); padding: 35px 25px; border-radius: 20px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: all 0.3s ease; display: flex; flex-direction: column; align-items: center; }
        .feature-card:hover { transform: translateY(-5px); border-color: #10b981; box-shadow: 0 15px 35px rgba(16, 185, 129, 0.1); }
        .feature-card i { font-size: 30px; color: #059669; margin-bottom: 20px; background: rgba(16, 185, 129, 0.1); width: 75px; height: 75px; display: flex; align-items: center; justify-content: center; border-radius: 50%; border: 1px solid rgba(16, 185, 129, 0.2); }
        .feature-card h4 { color: #1e293b; font-size: 18px; margin-bottom: 12px; font-weight: 800; }
        .feature-card p { color: #64748b; font-size: 14px; line-height: 1.6; }

        .hero { height: calc(100vh - 60px); position: relative; overflow: hidden; display: flex; justify-content: center; align-items: flex-end; text-align: center; }
        .hero-slides { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; }
        .slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center; background-repeat: no-repeat; opacity: 0; transition: opacity 1.2s cubic-bezier(0.4, 0, 0.2, 1), transform 7s ease; transform: scale(1.06); }
        .slide.active { opacity: 1; transform: scale(1); }
        .hero::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to bottom, transparent 30%, #ffffff 100%); z-index: 1; }
        .hero-content { position: relative; color: #1e293b; z-index: 2; width: 100%; padding: 0 20px; margin-bottom: 40px; }
        .carousel-dots { display: flex; justify-content: center; gap: 10px; }
        .dot { width: 9px; height: 9px; background-color: rgba(0, 0, 0, 0.2); border-radius: 50%; cursor: pointer; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .dot.active { background-color: #059669; transform: scale(1.2); width: 26px; border-radius: 6px; }

        /* --- 7. FOOD MANAGEMENT PACK (GRID 4 ITEMS ON MOBILE) --- */
        .food-nav-menu { display: flex; justify-content: center; flex-wrap: wrap; gap: 10px; margin-bottom: 30px; position: sticky; top: 60px; background: rgba(255,255,255,0.95); padding: 15px 0; z-index: 100; backdrop-filter: blur(10px); }
        .food-nav-menu a { text-decoration: none; padding: 10px 20px; border-radius: 50px; font-weight: bold; font-size: 14px; color: #475569; background: #f1f5f9; border: 1px solid #cbd5e1; transition: 0.3s; }
        .food-nav-menu a:hover, .food-nav-menu a.active-nav { background: #059669; color: white; border-color: #059669; }

        .food-grid-container { max-width: 1200px; margin: 0 auto; scroll-margin-top: 150px; }
        .food-category-title { font-size: 20px; color: #1e293b; border-left: 5px solid #059669; padding-left: 12px; margin: 40px 0 20px 0; font-weight: 700; letter-spacing: 0.5px; }
        
        /* GRID SETUP: Min 4 columns for desktop */
        .food-item-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        
        .food-card-with-img { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .food-card-with-img:hover { transform: translateY(-5px); border-color: rgba(16, 185, 129, 0.4); box-shadow: 0 15px 30px rgba(16, 185, 129, 0.1); }
        .food-card-with-img img { width: 100%; height: 180px; object-fit: cover; transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1); cursor: zoom-in; }
        .food-card-with-img:hover img { transform: scale(1.05); }
        
        .food-card-body { padding: 15px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .food-card-body h3 { font-size: 15px; color: #1e293b; margin-bottom: 5px; font-weight: 600; line-height: 1.2; }
        .food-card-body p.desc { font-size: 12px; color: #64748b; margin-bottom: 10px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .food-card-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(0, 0, 0, 0.05); padding-top: 10px; }
        .food-price { font-size: 16px; font-weight: 700; color: #059669; }
        .food-status { font-size: 10px; background: rgba(16, 185, 129, 0.1); color: #059669; padding: 4px 8px; border-radius: 50px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .drinks-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .drink-item { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); padding: 15px; border-radius: 14px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s ease; }
        .drink-item:hover { border-color: #059669; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.08); }
        .drink-name { font-size: 14px; font-weight: 600; color: #1e293b; }

        .management { display: flex; gap: 20px; flex-wrap: wrap; }
        .management .card { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); padding: 25px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); flex: 1; min-width: 240px; }
        .management .card h3 { color: #059669; margin-bottom: 10px; font-weight: 700; font-size: 18px; }
        .management .card p { color: #475569; font-size: 15px; line-height: 1.5; }
        .cottage-section { display: flex; flex-direction: column; gap: 25px; }
        .cottage { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); border-radius: 20px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.03); }
        .cottage img { width: 100%; height: 320px; object-fit: cover; }
        .cottage-content { padding: 22px; color: #475569; }
        .cottage-content h3 { color: #059669; }
        .entrance-card { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); padding: 22px; border-radius: 20px; display: flex; align-items: center; gap: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.03); }
        .entrance-icon { font-size: 32px; color: #059669; background: rgba(16, 185, 129, 0.1); padding: 18px; border-radius: 16px; }
        .entrance-details h3 { font-size: 18px; color: #1e293b; margin-bottom: 4px; }
        .entrance-details p { color: #475569; }
        .facilities { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 20px; }
        .facility { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); border-radius: 20px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.03); }
        .facility img { width: 100%; height: 210px; object-fit: cover; }
        .facility-content { padding: 16px; text-align: center; font-weight: 600; font-size: 15px; color: #1e293b; }
        .video-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .video-card { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); padding: 12px; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.03); }
        .video-card video { width: 100%; height: 460px; object-fit: cover; border-radius: 14px; background: #000; display: block; }
        .video-card h3 { font-size: 15px; color: #1e293b; margin-top: 12px; font-weight: 600; padding-left: 4px; text-align: left; }
        .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; }
        .gallery img { width: 100%; height: 150px; object-fit: cover; border-radius: 16px; cursor: zoom-in; transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.4s, box-shadow 0.4s; border: 1px solid rgba(0, 0, 0, 0.08); }
        .gallery img:hover { transform: scale(1.05) translateY(-3px); box-shadow: 0 12px 24px rgba(0,0,0,0.15); border-color: #059669; }

        .side-menu-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 100001; opacity: 0; visibility: hidden; transition: 0.3s ease; }
        .side-menu-overlay.active { opacity: 1; visibility: visible; }
        
        .side-menu { position: fixed; top: 15px; left: -320px; width: 280px; height: calc(100vh - 30px); background: #ffffff; border-radius: 20px; z-index: 100002; display: flex; flex-direction: column; padding: 25px; transition: left 0.4s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 10px 0 30px rgba(0,0,0,0.15); overflow-y: auto; }
        .side-menu.active { left: 15px; }
        
        .close-menu-btn { position: absolute; top: -10px; right: -10px; background: #10b981; color: white; width: 40px; height: 40px; border-radius: 12px; display: flex; justify-content: center; align-items: center; font-size: 20px; cursor: pointer; border: 2px solid #ffffff; transition: 0.3s; z-index: 10; box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3); }
        .close-menu-btn:hover { background: #059669; transform: scale(1.1); }

        .side-profile { text-align: left; margin-bottom: 30px; border-bottom: 1px solid rgba(0,0,0,0.08); padding-bottom: 20px; }
        .profile-pic-container { position: relative; width: 70px; height: 70px; margin-bottom: 15px; cursor: pointer; border-radius: 18px; overflow: hidden; border: 2px solid rgba(16,185,129,0.3); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.2); }
        .profile-img-preview { width: 100%; height: 100%; object-fit: cover; }
        .upload-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.3s; color: white; font-size: 20px; }
        .profile-pic-container:hover .upload-overlay { opacity: 1; }
        .profile-initials { background: #10b981; color: #ffffff; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 800; }
        .profile-name { color: #1e293b; font-size: 22px; font-weight: 800; line-height: 1.2; margin-bottom: 5px; }
        .profile-role { color: #64748b; font-size: 14px; }

        .side-nav-links { display: flex; flex-direction: column; gap: 8px; }
        .side-link { display: flex; align-items: center; gap: 15px; padding: 14px 16px; color: #475569; text-decoration: none; font-size: 16px; font-weight: 600; border-radius: 12px; cursor: pointer; transition: 0.3s ease; }
        .side-link i { font-size: 20px; width: 25px; text-align: center; }
        .side-link:hover, .side-link.active { background: rgba(16, 185, 129, 0.08); color: #059669; padding-left: 22px; }

        .contact { background: #f8fafc; border-top: 1px solid rgba(0, 0, 0, 0.05); text-align: center; }
        .contact p { margin: 12px 0; color: #475569; font-size: 15px; }
        .contact a { color: #059669; text-decoration: none; font-weight: 600; }
        footer { background: #f8fafc; color: #64748b; text-align: center; padding: 25px; font-size: 13px; border-top: 1px solid rgba(0, 0, 0, 0.05); }
        
        /* FOOD LIGHTBOX */
        .lightbox { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); display: none; flex-direction: column; gap: 20px; justify-content: center; align-items: center; z-index: 99999; opacity: 0; transition: opacity 0.3s ease; cursor: zoom-out; }
        .lightbox img { max-width: 95%; max-height: 85%; border-radius: 16px; box-shadow: 0 30px 60px rgba(0,0,0,0.3); transform: scale(0.8); transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); border: 2px solid #ffffff; }
        .lightbox.show { opacity: 1; }
        .lightbox.show img { transform: scale(1); }
        
        .download-btn { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 12px 30px; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 15px; display: flex; align-items: center; gap: 10px; transform: translateY(20px); opacity: 0; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3); border: 2px solid rgba(255, 255, 255, 0.5); cursor: pointer; }
        .download-btn:hover { background: linear-gradient(135deg, #059669, #047857); transform: scale(1.05) translateY(18px) !important; box-shadow: 0 15px 35px rgba(16, 185, 129, 0.4); }
        .lightbox.show .download-btn { opacity: 1; transform: translateY(0); }
        
        .feature-popup-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(248, 250, 252, 0.82); backdrop-filter: blur(8px); z-index: 100000; display: none; justify-content: center; align-items: center; padding: 20px; }
        .feature-popup-content { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.08); padding: 30px; border-radius: 24px; max-width: 390px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15); text-align: center; animation: modalZoomIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes modalZoomIn { from { transform: scale(0.92) translateY(12px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }
        .feature-popup-content i { font-size: 42px; color: #059669; margin-bottom: 16px; }
        .feature-popup-content h3 { font-size: 20px; color: #1e293b; margin-bottom: 12px; font-weight: 700; }
        .feature-popup-content p { font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 24px; }
        .close-popup-btn { background: rgba(16, 185, 129, 0.1); color: #059669; border: none; padding: 12px 28px; font-size: 14px; font-weight: 600; border-radius: 50px; cursor: pointer; transition: 0.2s; }
        .close-popup-btn:hover { background: rgba(16, 185, 129, 0.2); }

        .booking-card { background: #fff; padding: 20px; border-radius: 16px; border: 1px solid #cbd5e1; box-shadow: 0 5px 15px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between; }
        .booking-status { display: inline-block; padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-Pending { background: #fef3c7; color: #d97706; }
        .status-Confirmed { background: #d1fae5; color: #059669; }
        .status-Cancelled { background: #fee2e2; color: #ef4444; }
        .status-Verified { background: #e0e7ff; color: #2563eb; }

        .qr-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 999999; display: none; justify-content: center; align-items: center; padding: 20px; }
        .qr-modal-content { background: #ffffff; padding: 30px; border-radius: 20px; text-align: center; max-width: 350px; width: 100%; }
        #qrcode-container img { margin: 0 auto; border: 10px solid #fff; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

        .games-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .game-card { background: #fff; border-radius: 20px; overflow: hidden; border: 1px solid #cbd5e1; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }

        @media (max-width: 600px) {
            section { padding: 40px 4%; }
            .title { font-size: 25px; margin-bottom: 25px; }
            .video-card video { height: 240px; border-radius: 12px; } 
            .gallery { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 8px; }
            .gallery img { height: 110px; border-radius: 12px; }
            .top-auth { gap: 6px; }
            .top-auth-btn { padding: 8px 11px; font-size: 12px; }
            .top-auth-user span { display:none; }
            .welcome-container h1 { font-size: 26px; }
            .feature-table-box { padding: 18px; border-radius: 20px; }
            .table-row-grid { gap: 10px; }
            .table-cell { padding: 14px; border-radius: 12px; }
            .side-menu { width: 260px; left: -280px; }
            .about-intro { padding: 15px; font-size: 14px; }
            
            .food-nav-menu a { padding: 8px 12px; font-size: 12px; }
            /* FORCE 4 COLUMNS ON MOBILE */
            .food-item-grid { grid-template-columns: repeat(4, 1fr) !important; gap: 8px !important; }
            .food-card-body { padding: 8px; }
            .food-card-body h3 { font-size: 11px; margin-bottom: 2px; }
            .food-card-body p.desc { font-size: 9px; margin-bottom: 5px; }
            .food-price { font-size: 11px; }
            .food-status { display: none; } /* Hide available badge to save space on mobile */
            .food-card-with-img img { height: 90px; }
        }
        .map-container { border-radius: 20px; overflow: hidden; border: 1px solid rgba(0, 0, 0, 0.05); box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-top: 20px; }
        .map-btn { display: block; width: 100%; text-align: center; background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 15px; border-radius: 50px; text-decoration: none; font-weight: 700; margin-top: 15px; transition: 0.3s; }
        .map-btn:hover { box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4); transform: translateY(-2px); }
    </style>
</head>
<body>

    <audio id="bgMusic" loop autoplay preload="auto">
        <source src="assetsmusiconetime.m3" type="audio/mpeg">
    </audio>

    <div class="music-control-btn" id="musicBtn" onclick="toggleMusic()">
        <i class="fas fa-play" id="musicIcon"></i>
    </div>

    <div id="preloader">
        <div class="loader-spinner"></div>
        <div class="loader-text">Loading Experience...</div>
    </div>

    <div class="side-menu-overlay" id="sideMenuOverlay" onclick="toggleSideMenu()"></div>
    <div class="side-menu" id="sideMenu">
        <div class="close-menu-btn" onclick="toggleSideMenu()">
            <i class="fas fa-times"></i>
        </div>
        
        <?php if ($isLoggedIn): ?>
        <div class="side-profile">
            <div class="profile-pic-container" onclick="document.getElementById('profilePicInput').click()" title="Click to change photo">
                <?php if ($profilePic): ?>
                    <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" class="profile-img-preview">
                <?php else: ?>
                    <div class="profile-initials"><?php echo $initials; ?></div>
                <?php endif; ?>
                <div class="upload-overlay"><i class="fas fa-camera"></i></div>
            </div>
            <form id="uploadForm" method="POST" enctype="multipart/form-data" style="display: none;">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" onchange="document.getElementById('uploadForm').submit()">
            </form>
            <div class="profile-name"><?php echo htmlspecialchars($userName); ?></div>
            <div class="profile-role"><?php echo $userRole; ?></div>
        </div>
        <?php else: ?>
        <div class="side-profile">
            <div class="profile-initials" style="width:70px;height:70px;border-radius:18px;margin-bottom:15px;"><i class="fas fa-user"></i></div>
            <div class="profile-name">Welcome, Guest</div>
            <div class="profile-role">Browse CherryJoe River Park without an account</div>
        </div>
        <?php endif; ?>

        <div class="side-nav-links">
            <a onclick="navigateMenu('home', 'slink-home')" class="side-link active" id="slink-home">
                <i class="fas fa-home"></i> Home
            </a>
            
            <a onclick="<?php echo ($isLoggedIn && !$isAdmin) ? "navigateMenu('booking', 'slink-booking')" : "requireLogin('booking')"; ?>" class="side-link" id="slink-booking">
                <i class="fas fa-calendar-alt"></i> Booking
            </a>

            <a onclick="navigateMenu('explore', 'slink-explore')" class="side-link" id="slink-explore">
                <i class="fas fa-compass"></i> Explore
            </a>
            <a onclick="navigateMenu('food', 'slink-food')" class="side-link" id="slink-food">
                <i class="fas fa-utensils"></i> Food Menu
            </a>
            
            <a onclick="navigateMenu('games', 'slink-games')" class="side-link" id="slink-games">
                <i class="fas fa-gamepad"></i> 3D Games
            </a>

            <a onclick="scrollToAbout()" class="side-link" id="slink-about">
                <i class="fas fa-info-circle"></i> About Us
            </a>
            
            <?php if($isAdmin): ?>
            <a href="admin_dashboard.php" class="side-link" style="margin-top: 10px; color: #d97706;">
                <i class="fas fa-cogs"></i> Admin Panel
            </a>
            <?php endif; ?>

            <?php if ($isLoggedIn): ?>
            <a href="logout.php" class="side-link" style="margin-top: auto; color: #dc2626;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
            <?php else: ?>
            <a href="login.php" class="side-link" style="margin-top: auto; color: #059669;">
                <i class="fas fa-sign-in-alt"></i> Log In
            </a>
            <a href="signup.php" class="side-link" style="color:#059669;">
                <i class="fas fa-user-plus"></i> Sign Up
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- QR CODE MODAL -->
    <div class="qr-modal" id="qrModal" onclick="closeQRModal()">
        <div class="qr-modal-content" onclick="event.stopPropagation()">
            <h3 style="color:#059669; margin-bottom:10px;"><i class="fas fa-qrcode"></i> Your Booking QR</h3>
            <p id="qr-cottage-name" style="margin-bottom:20px; font-weight:bold; color:#1e293b;"></p>
            <div id="qrcode-container" style="display:flex; justify-content:center; margin-bottom:20px;"></div>
            <p style="font-size:13px; color:#64748b; margin-bottom:20px;">Present this QR code to the admin upon arrival to confirm your reservation instantly.</p>
            <button onclick="closeQRModal()" style="background:#ef4444; color:white; border:none; padding:10px 20px; border-radius:50px; font-weight:bold; cursor:pointer; width:100%;">Close Panel</button>
        </div>
    </div>

    <!-- WELCOME OVERLAY & POPUP -->
    <div class="feature-popup-modal" id="featurePopup">
        <div class="feature-popup-content">
            <i id="popupIcon" class="fas fa-tree"></i>
            <h3 id="popupTitle">Feature Title</h3>
            <p id="popupDesc">Descriptions...</p>
            <button class="close-popup-btn" onclick="closeFeaturePopup()">Close Details</button>
        </div>
    </div>

    <div class="welcome-overlay" id="welcomeOverlay">
        <div class="welcome-container">
            <h1>Hello, Welcome!</h1>
            <p class="subtitle">Quickly review CherryJoe River Park highlights inside our structured tables before moving forward.</p>
            
            <div class="feature-table-box table-one">
                <div class="table-header"><i class="fas fa-swimming-pool"></i> Table 1: Amenities & Rates</div>
                <div class="table-row-grid">
                    <div class="table-cell cell-1">
                        <i class="fas fa-home" style="color: #f97316;"></i>
                        <span>Open Cottage</span>
                        <a onclick="openFeaturePopup('cottage')">View Rate</a>
                    </div>
                    <div class="table-cell cell-2">
                        <i class="fas fa-water-ladder" style="color: #38bdf8;"></i>
                        <span>Resort Pool</span>
                        <a onclick="openFeaturePopup('pool')">View Details</a>
                    </div>
                </div>
            </div>

            <div class="feature-table-box table-two">
                <div class="table-header"><i class="fas fa-leaf"></i> Table 2: Park Experience</div>
                <div class="table-row-grid">
                    <div class="table-cell cell-1">
                        <i class="fas fa-water" style="color: #22d3ee;"></i>
                        <span>River View</span>
                        <a onclick="openFeaturePopup('river')">View Setup</a>
                    </div>
                    <div class="table-cell cell-2">
                        <i class="fas fa-utensils" style="color: #f43f5e;"></i>
                        <span>Local Food</span>
                        <a onclick="openFeaturePopup('food')">View Menu</a>
                    </div>
                </div>
            </div>

            <button class="welcome-btn" onclick="closeWelcomeScreen()">Enter Full Website</button>
        </div>
    </div>

    <nav>
        <div class="nav-left">
            <div class="menu-toggle-btn" onclick="toggleSideMenu()">
                <i class="fas fa-bars"></i>
            </div>
            <div class="logo">CherryJoe River Park</div>
        </div>

        <div class="top-auth">
            <?php if ($isLoggedIn): ?>
                <div class="top-auth-user">
                    <div class="top-auth-avatar">
                        <?php if ($profilePic): ?>
                            <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile">
                        <?php else: ?>
                            <?php echo htmlspecialchars($initials); ?>
                        <?php endif; ?>
                    </div>
                    <span><?php echo htmlspecialchars($userName); ?></span>
                </div>
            <?php else: ?>
                <a class="top-auth-btn login" href="login.php">Log In</a>
                <a class="top-auth-btn signup" href="signup.php">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- MAIN HOME PAGE -->
    <div id="page-home" class="app-page <?php echo $isLoggedIn ? 'page-active' : ''; ?>">
        <section class="hero" id="home" style="padding:0; max-width:100%;">
            <div class="hero-slides">
                <div class="slide active" style="background-image: url('imagesgallery7.jpg');"></div>
                <div class="slide" style="background-image: url('imagescherryjoe-hero.jpg');"></div>
                <div class="slide" style="background-image: url('imagesgallery2.jpg');"></div>
                <div class="slide" style="background-image: url('imagesgallery3.jpg');"></div>
                <div class="slide" style="background-image: url('imagesgallery4.jpg');"></div>
                <div class="slide" style="background-image: url('imagesgallery1.jpg');"></div>
            </div>
            <div class="hero-content">
                <div class="carousel-dots">
                    <div class="dot active" onclick="changeSlide(0)"></div>
                    <div class="dot" onclick="changeSlide(1)"></div>
                    <div class="dot" onclick="changeSlide(2)"></div>
                    <div class="dot" onclick="changeSlide(3)"></div>
                    <div class="dot" onclick="changeSlide(4)"></div>
                    <div class="dot" onclick="changeSlide(5)"></div>
                </div>
            </div>
        </section>

        <!-- ABOUT US SECTION -->
        <section id="about" class="reveal">
            <h2 class="title">About Us</h2>
            <div class="about-intro">
                <p><strong>CherryJoe River Park</strong> is a popular nature trip and eco-tourism destination located in Purok Magong-ong, Barangay San Rafael, Cateel, Davao Oriental, Philippines. Known for its peaceful, uncommercialized atmosphere, it highlights the natural beauty of the Cateel River area.</p>
            </div>
            <h3 class="section-subtitle">Key Features & Amenities</h3>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-bridge-water"></i>
                    <h4>Scenic Hanging Bridge</h4>
                    <p>Offers sweeping views of the surrounding river and lush greenery.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-swimmer"></i>
                    <h4>Natural Swimming</h4>
                    <p>Features fresh, cool river water and scenic rock formations perfect for swimming and relaxing.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-users"></i>
                    <h4>Inclusivity & Accessibility</h4>
                    <p>The park welcomes families and is noted as being good for kids and LGBTQ+ friendly.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-leaf"></i>
                    <h4>Nature Trips</h4>
                    <p>Highly recommended for visitors looking to escape busy city life, take photos, and bond in a natural environment.</p>
                </div>
            </div>
        </section>

        <section id="location" class="reveal">
            <h2 class="title">Visit Us</h2>
            <div class="map-container">
                <iframe 
                    src="https://maps.google.com/maps?q=CherryJoe%20River%20Park,%20Cateel,%20Davao%20Oriental&t=k&z=18&ie=UTF8&iwloc=&output=embed" 
                    width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            <a href="https://share.google/7u3FzgC9UR5maQkR4" target="_blank" class="map-btn">
                <i class="fas fa-location-arrow"></i> Get Directions
            </a>
        </section>

        <section class="contact reveal" id="contact" style="max-width:100%;">
            <h2 class="title" style="color:#1e293b;">Contact Details</h2>
            <p><i class="fas fa-phone"></i> +63 920 408 7956</p>
            <p><i class="fas fa-envelope"></i> cherryday103080@gmail.com</p>
            <p><i class="fas fa-map-marker-alt"></i> Purok Magong-ong Brgy. San Rafael Cateel Davao Oriental</p>
            <p><i class="fab fa-facebook"></i> Facebook: <a href="https://www.facebook.com/search/top/?q=CherryJoe%20River%20Park" target="_blank">CherryJoe River Park Website</a></p>
        </section>
    </div>

    <!-- EXPLORE PAGE -->
    <div id="page-explore" class="app-page <?php echo !$isLoggedIn ? 'page-active' : ''; ?>">
        <section id="management" class="reveal">
            <h2 class="title">Management</h2>
            <div class="management">
                <div class="card"><h3>Owner</h3><p>CherryJoe River Park Owner</p></div>
                <div class="card"><h3>Manager</h3><p>Santi Day</p></div>
            </div>
        </section>
        
        <section id="rates-and-cottage" class="reveal">
            <h2 class="title">Rates & Cottages</h2>
            <div class="cottage-section">
                <div class="entrance-card"><div class="entrance-icon"><i class="fas fa-ticket-alt"></i></div><div class="entrance-details"><h3>Entrance Fee</h3><p><strong>Price:</strong> ₱20 per person</p></div></div>
                <div class="cottage"><img src="imagescottage.jpg" alt="Open Cottage" onclick="showImage(this.src)"><div class="cottage-content"><h3>Open Cottage</h3><p><strong>Price:</strong> ₱100</p><p><strong>Capacity:</strong> 8 Persons</p></div></div>
            </div>
        </section>
        
        <section id="facilities" class="reveal">
            <h2 class="title">Facilities</h2>
            <div class="facilities">
                <div class="facility"><img src="imagespool.jpg" alt="Pool" onclick="showImage(this.src)"><div class="facility-content"><h3>Pool</h3></div></div>
                <div class="facility"><img src="imagesriver.jpg" alt="River" onclick="showImage(this.src)"><div class="facility-content"><h3>River View</h3></div></div>
                <div class="facility"><img src="imagesrestaurant.jpg" alt="Restaurant" onclick="showImage(this.src)"><div class="facility-content"><h3>Restaurant</h3></div></div>
                <div class="facility"><img src="imagesfunctionhall.jpg" alt="Function Hall" onclick="showImage(this.src)"><div class="facility-content"><h3>Function Hall</h3></div></div>
            </div>
        </section>
        
        <section id="video" style="text-align:center;" class="reveal">
            <h2 class="title">Resort Video Tour</h2>
            <div class="video-grid">
                <div class="video-card"><video controls playsinline preload="metadata" onplay="handleVideoPlay(this)"><source src="videosresort-tour.mp4" type="video/mp4"></video><h3>Overview Tour</h3></div>
                <div class="video-card"><video controls playsinline preload="metadata" onplay="handleVideoPlay(this)"><source src="videosresort-tour2.mp4" type="video/mp4"></video><h3>River Side View</h3></div>
                <div class="video-card"><video controls playsinline preload="metadata" onplay="handleVideoPlay(this)"><source src="videosresort-tour3.mp4" type="video/mp4"></video><h3>Pool Amenities</h3></div>
                <div class="video-card"><video controls playsinline preload="metadata" onplay="handleVideoPlay(this)"><source src="videosresort-tour4.mp4" type="video/mp4"></video><h3>Night Ambient</h3></div>
                <div class="video-card"><video controls playsinline preload="metadata" onplay="handleVideoPlay(this)"><source src="videosresort-tour5.mp4" type="video/mp4"></video><h3>Cottage Walkthrough</h3></div>
                <div class="video-card"><video controls playsinline preload="metadata" onplay="handleVideoPlay(this)"><source src="videosresort-tour6.mp4" type="video/mp4"></video><h3>Event Function Hall</h3></div>
            </div>
        </section>
        
        <section id="gallery" class="reveal">
            <h2 class="title">Gallery</h2>
            <div class="gallery">
                <img src="imagesgallery1.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery2.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery3.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery4.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery5.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery6.jpg" alt="Gallery" onclick="showImage(this.src)">
                <img src="imagesgallery7.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery8.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery9.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery10.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery11.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery12.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery13.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery14.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery15.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery16.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery17.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery18.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery19.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery20.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery21.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery22.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery23.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery24.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery25.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery26.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery27.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery28.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery29.jpg" alt="Gallery" onclick="showImage(this.src)"><img src="imagesgallery30.jpg" alt="Gallery" onclick="showImage(this.src)">
            </div>
        </section>
    </div>

    <!-- FOOD PAGE (DYNAMIC PDO) -->
    <div id="page-food" class="app-page">
        <div class="food-nav-menu">
            <a href="#cat-Specialties" onclick="setActiveNav(this)">Specialties</a>
            <a href="#cat-Combo Meals" onclick="setActiveNav(this)">Combo Meals</a>
            <a href="#cat-Finger Foods" onclick="setActiveNav(this)">Finger Foods</a>
            <a href="#cat-Drinks" onclick="setActiveNav(this)">Drinks</a>
        </div>

        <section id="food-section" style="padding-top: 20px;">
            <div class="food-grid-container">
                
                <?php
                $categories = ['Specialties', 'Combo Meals', 'Finger Foods', 'Drinks'];
                foreach ($categories as $cat) {
                    try {
                        $stmt = $conn->prepare("SELECT * FROM menu_items WHERE category = :cat ORDER BY id DESC");
                        $stmt->execute(['cat' => $cat]);
                        $items = $stmt->fetchAll();
                        
                        if (count($items) > 0) {
                            $icon = 'fas fa-star';
                            if ($cat == 'Combo Meals') $icon = 'fas fa-concierge-bell';
                            if ($cat == 'Finger Foods') $icon = 'fas fa-hamburger';
                            if ($cat == 'Drinks') $icon = 'fas fa-glass-cheers';

                            echo "<div class='food-category-title' id='cat-".htmlspecialchars($cat)."'><i class='$icon'></i> ".htmlspecialchars($cat)."</div>";
                            
                            if ($cat == 'Drinks') {
                                echo "<div class='drinks-grid'>";
                                foreach ($items as $item) {
                                    echo "<div class='drink-item'>
                                            <span class='drink-name'>".htmlspecialchars($item['name'])."</span> 
                                            <span class='food-price'>".htmlspecialchars($item['price'])."</span>
                                          </div>";
                                }
                                echo "</div>";
                            } else {
                                echo "<div class='food-item-grid'>";
                                foreach ($items as $item) {
                                    $img = !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'https://placehold.co/400x250?text=No+Image';
                                    echo "<div class='food-card-with-img'>";
                                    echo "<img src='$img' alt='".htmlspecialchars($item['name'])."' onclick=\"showImage(this.src)\" onerror=\"this.src='https://placehold.co/400x250?text=No+Image'\">";
                                    echo "<div class='food-card-body'>";
                                    echo "<div><h3>".htmlspecialchars($item['name'])."</h3>";
                                    if (!empty($item['description'])) {
                                        echo "<p class='desc'>".nl2br(htmlspecialchars($item['description']))."</p>";
                                    }
                                    echo "</div>";
                                    echo "<div class='food-card-footer'><span class='food-price'>".htmlspecialchars($item['price'])."</span><span class='food-status'>Available</span></div>";
                                    echo "</div></div>";
                                }
                                echo "</div>";
                            }
                        }
                    } catch(PDOException $e) { }
                }
                ?>
            </div>
        </section>
    </div>

    <!-- CUSTOM 3D GAMES PAGE -->
    <div id="page-games" class="app-page">
        <section class="reveal">
            <h2 class="title">CherryJoe Exclusives</h2>
            <div class="about-intro">
                <p>Play our very own custom 3D game built exclusively for CherryJoe River Park guests! Dodge the river rocks to get the highest score.</p>
            </div>
            
            <div class="games-grid" style="display: block;">
                <div class="game-card" style="max-width: 800px; margin: 0 auto; background: #f8fafc; border: 2px solid #10b981; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                    <iframe src="3dgame.html?name=<?php echo urlencode($userName); ?>&email=<?php echo urlencode($userEmail); ?>&slots=<?php echo $current_game_slots; ?>&attempts=<?php echo $u_attempts; ?>&goal=<?php echo $u_goal; ?>&highscore=<?php echo $u_highscore; ?>" width="100%" height="500" frameborder="0" style="border: none; display: block;"></iframe>
                    <div style="background: #ffffff; border-top: 1px solid #cbd5e1; padding: 15px; text-align: center;">
                        <h3 style="color: #059669; margin-bottom: 5px; font-size: 18px;"><i class="fas fa-ship"></i> CherryJoe River Dodge</h3>
                        <p style="font-size: 13px; color: #64748b;">Controls: Use Left & Right arrows (or tap the left/right sides of your screen) to dodge the rocks!</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- BOOKING & RESERVATION -->
    <?php if($isLoggedIn && !$isAdmin): ?>
    <div id="page-booking" class="app-page">
        <section class="reveal">
            <h2 class="title">Reservation & Booking</h2>
            
            <!-- BOOKING FORM WITH GCASH -->
            <div style="background: #fff; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto 40px auto; border: 1px solid #cbd5e1;">
                <h3 style="color: #059669; margin-bottom: 20px; text-align: center;"><i class="fas fa-calendar-plus"></i> Book a Facility</h3>
                <form id="bookingForm" onsubmit="submitBooking(event)">
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom: 5px; font-weight: bold; color: #1e293b; font-size: 14px;">Select Cottage/Facility</label>
                        <select id="cottage_type" required style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; outline: none;">
                            <option value="">-- Choose Here --</option>
                            <option value="Open Cottage - ₱100 (6 persons)">Open Cottage - ₱100 (Good for 6 persons)</option>
                            <option value="Open Cottage - ₱150 (8 persons)">Open Cottage - ₱150 (Good for 8 persons)</option>
                            <option value="Open Cottage - ₱200 (10 persons)">Open Cottage - ₱200 (Good for 10 persons)</option>
                            <option value="Open Cottage - ₱300 (12 persons)">Open Cottage - ₱300 (Good for 12 persons)</option>
                            <option value="Floating Cottage - ₱1500 (Family)">Floating Cottage - ₱1500 (For Family)</option>
                            <option value="Function Hall">Function Hall</option>
                        </select>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="display:block; margin-bottom: 5px; font-weight: bold; color: #1e293b; font-size: 14px;">Check-In Date</label>
                            <input type="date" id="check_in" min="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; outline: none;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom: 5px; font-weight: bold; color: #1e293b; font-size: 14px;">Check-Out Date</label>
                            <input type="date" id="check_out" min="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; outline: none;">
                        </div>
                    </div>

                    <!-- GCASH PAYMENT DETAILS BOX -->
                    <div style="background: #f0fdf4; border: 1px dashed #10b981; padding: 20px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
                        <p style="color: #059669; font-weight: bold; font-size: 15px; margin-bottom: 5px;">
                            <i class="fas fa-mobile-alt"></i> GCash Payment Info
                        </p>
                        <p style="color: #334155; font-size: 14px;"><b>Number:</b> 0920 408 7956</p>
                        <p style="color: #334155; font-size: 14px; margin-bottom: 15px;"><b>Account Name:</b> CherryJoe River Park</p>
                        
                        <label style="display:block; margin-bottom: 5px; font-weight: bold; color: #1e293b; font-size: 13px; text-align: left;">GCash Reference Number (Ref No.)</label>
                        <input type="text" id="gcash_ref" placeholder="e.g. 1002 345 6789" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none;">
                    </div>

                    <button type="submit" id="btn-submit-booking" style="width: 100%; padding: 15px; background: #10b981; color: white; border: none; border-radius: 50px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 16px;">
                        Confirm Reservation
                    </button>
                    <div id="booking-msg" style="margin-top: 15px; text-align: center; font-weight: bold; font-size: 14px;"></div>
                </form>
            </div>

            <!-- MY BOOKINGS LIST -->
            <h3 class="section-subtitle" style="margin-top: 50px;">My Bookings</h3>
            <div class="features-grid">
                <?php if (count($userBookings) > 0): ?>
                    <?php foreach ($userBookings as $b): ?>
                        <?php 
                            $today = date('Y-m-d');
                            $is_expired = (strtotime($b['check_out']) < strtotime($today));
                        ?>
                        <div class="booking-card">
                            <div>
                                <h4 style="color: #1e293b; margin-bottom: 8px; font-size: 16px; font-weight: bold;">
                                    <?php echo htmlspecialchars($b['cottage_type']); ?>
                                </h4>
                                <p style="color: #64748b; font-size: 13px; margin-bottom: 5px;"><i class="fas fa-calendar-check"></i> <b>In:</b> <?php echo htmlspecialchars($b['check_in']); ?></p>
                                <p style="color: #64748b; font-size: 13px; margin-bottom: 15px;"><i class="fas fa-calendar-times"></i> <b>Out:</b> <?php echo htmlspecialchars($b['check_out']); ?></p>
                                <span class="booking-status status-<?php echo htmlspecialchars($b['status']); ?>">
                                    <?php echo htmlspecialchars($b['status']); ?>
                                </span>
                                
                                <?php if($is_expired && $b['status'] !== 'Cancelled'): ?>
                                    <span style="font-size: 11px; color: #ef4444; font-weight: bold; margin-left: 5px;">(Expired)</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if($b['status'] === 'Confirmed'): ?>
                                <?php if(!$is_expired): ?>
                                    <button onclick="showQRCode('CJRP-<?php echo $b['id']; ?>', '<?php echo addslashes($b['cottage_type']); ?>')" style="margin-top: 15px; background: #1e293b; color: white; border: none; padding: 10px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; transition: 0.2s;">
                                        <i class="fas fa-qrcode"></i> Show QR Code
                                    </button>
                                <?php else: ?>
                                    <button disabled style="margin-top: 15px; background: #94a3b8; color: white; border: none; padding: 10px; border-radius: 8px; cursor: not-allowed; font-weight: bold; width: 100%;">
                                        <i class="fas fa-qrcode"></i> QR Code Expired
                                    </button>
                                <?php endif; ?>
                            <?php elseif($b['status'] === 'Verified'): ?>
                                <button disabled style="margin-top: 15px; background: #dbeafe; color: #2563eb; border: 1px solid #bfdbfe; padding: 10px; border-radius: 8px; font-weight: bold; width: 100%;">
                                    <i class="fas fa-check-double"></i> Checked-In at Resort
                                </button>
                            <?php endif; ?>
                            
                            <?php if($b['status'] === 'Pending' && !$is_expired): ?>
                                <button onclick="cancelBooking(<?php echo $b['id']; ?>)" style="margin-top: 10px; background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; padding: 10px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; transition: 0.2s;">
                                    <i class="fas fa-times-circle"></i> Cancel Reservation
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #64748b; width: 100%; font-size: 15px;">You have no reservations yet.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- RESORT POLICIES & FAQS -->
        <section class="reveal" style="margin-top: 40px;">
            <h2 class="title">Resort Policies & FAQs</h2>
            <div style="max-width: 800px; margin: 0 auto; background: #f8fafc; padding: 30px; border-radius: 20px; border: 1px solid #cbd5e1;">
                <h3 style="color: #059669; margin-bottom: 10px; font-size: 18px;"><i class="fas fa-file-alt"></i> Cancellation Policy</h3>
                <p style="color: #475569; margin-bottom: 25px; font-size: 15px; line-height: 1.6;">You can modify or cancel your booking for free up to 24 hours before your check-in date. Cancellations made on the day of the reservation are non-refundable.</p>
                
                <h3 style="color: #059669; margin-bottom: 10px; font-size: 18px;"><i class="fas fa-question-circle"></i> Frequently Asked Questions</h3>
                <ul style="color: #475569; font-size: 15px; padding-left: 20px; line-height: 1.8;">
                    <li><strong>Are pets allowed?</strong> Yes, we are a pet-friendly resort! Just ensure they are on a leash.</li>
                    <li><strong>Do you allow outside food?</strong> Yes, there is no corkage fee for outside food, except for alcoholic beverages.</li>
                    <li><strong>What time is check-in?</strong> Standard check-in is at 11:00 AM.</li>
                </ul>
            </div>
        </section>
    </div>
    <?php endif; ?>

    <!-- FOOTER -->
    <footer>© 2026 CherryJoe River Park</footer>

    <!-- LIGHTBOX PARA SA FOOD UG GALLERY -->
    <div class="lightbox" id="lightbox" onclick="hideImage()">
        <img id="lightbox-img" alt="Enlarged Preview">
        <a id="lightbox-download" href="#" download="CherryJoe_Photo.jpg" class="download-btn" onclick="event.stopPropagation()">
            <i class="fas fa-download"></i> Download Photo
        </a>
    </div>

    <script>
        const audio = document.getElementById('bgMusic');
        const musicBtn = document.getElementById('musicBtn');
        const musicIcon = document.getElementById('musicIcon');

        function toggleMusic() {
            if (audio.paused) {
                audio.play().then(() => {
                    musicIcon.className = "fas fa-pause";
                    musicBtn.classList.add('playing');
                }).catch(err => console.log("Audio play blocked."));
            } else {
                audio.pause();
                musicIcon.className = "fas fa-play";
                musicBtn.classList.remove('playing');
            }
        }

        function handleVideoPlay(playingVideo) {
            if (!audio.paused) {
                audio.pause();
                musicIcon.className = "fas fa-play";
                musicBtn.classList.remove('playing');
            }
            const allVideos = document.querySelectorAll('video');
            allVideos.forEach(v => {
                if (v !== playingVideo) v.pause();
            });
        }

        function forceAutoplayOnInteraction() {
            if (audio.paused) {
                audio.play().then(() => {
                    musicIcon.className = "fas fa-pause";
                    musicBtn.classList.add('playing');
                    document.removeEventListener('click', forceAutoplayOnInteraction);
                    document.removeEventListener('touchstart', forceAutoplayOnInteraction);
                    document.removeEventListener('scroll', forceAutoplayOnInteraction);
                }).catch(e => console.log("Browser block active."));
            }
        }

        function toggleSideMenu() {
            const menu = document.getElementById('sideMenu');
            const overlay = document.getElementById('sideMenuOverlay');
            menu.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function navigateMenu(pageId, linkId) {
            document.querySelectorAll('.side-link').forEach(link => link.classList.remove('active'));
            if(linkId) {
                document.getElementById(linkId).classList.add('active');
            }
            navigateTo(pageId);
            toggleSideMenu();
        }

        function scrollToAbout() {
            navigateMenu('home', 'slink-about');
            setTimeout(() => {
                const aboutSection = document.getElementById('about');
                if (aboutSection) {
                    aboutSection.scrollIntoView({ behavior: 'smooth' });
                }
            }, 300);
        }

        window.addEventListener('load', () => {
            const preloader = document.getElementById('preloader');
            const welcomeOverlay = document.getElementById('welcomeOverlay');
            const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

            const playPromise = audio.play();
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    musicIcon.className = "fas fa-pause";
                    musicBtn.classList.add('playing');
                }).catch(error => {
                    console.log("Autoplay blocked.");
                });
            }

            preloader.style.opacity = '0';
            setTimeout(() => {
                preloader.style.visibility = 'hidden';
                initScrollRevealEngine();

                // Always start public visitors on Explore.
                if (!isLoggedIn) {
                    localStorage.setItem('currentPage', 'explore');
                    localStorage.setItem('currentSideLink', 'slink-explore');
                    activateSideLink('slink-explore');
                    navigateTo('explore', false);
                } else {
                    // Logged-in users start at Home, unless they are already browsing a saved page.
                    const savedPage = localStorage.getItem('currentPage');
                    const savedSideLink = localStorage.getItem('currentSideLink');
                    const allowedPages = ['home', 'explore', 'food', 'games', 'booking'];
                    const pageToOpen = allowedPages.includes(savedPage) ? savedPage : 'home';
                    navigateTo(pageToOpen, true);
                    if (savedSideLink) activateSideLink(savedSideLink);
                }

                document.addEventListener('click', forceAutoplayOnInteraction);
                document.addEventListener('touchstart', forceAutoplayOnInteraction);
                document.addEventListener('scroll', forceAutoplayOnInteraction);
            }, 600);
        });

        function initScrollRevealEngine() {
            const targets = document.querySelectorAll('.reveal');
            const visualObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                    }
                });
            }, { threshold: 0.08, rootMargin: "0px 0px -40px 0px" });
            targets.forEach(element => visualObserver.observe(element));
        }

        const featureData = {
            cottage: { title: "Open Cottages Setup", icon: "fas fa-home", desc: "Relax in our comfortable Open Cottages built perfect for families and barkadas. Current rental rate is fixed at only ₱100 with an 8-person maximum capacity limit." },
            pool: { title: "Resort Swimming Pool", icon: "fas fa-swimming-pool", desc: "Enjoy a pristine, treated, and completely cold swim in our integrated pool structure. Highly dynamic safety setup designed for children and adults." },
            river: { title: "Scenic River View", icon: "fas fa-water", desc: "Nature at its best! CherryJoe is directly parallel to the clean refreshing river flow of Cateel, Davao Oriental, providing natural therapeutic acoustics." },
            food: { title: "Authentic Food Menu", icon: "fas fa-utensils", desc: "Savor premium local delicacies cooked fresh: Grilled Tuna Belly, Sinuwag Pork Belly, Tuna Panga, Freshwater Eel, and strong hot Native Coffee." }
        };

        function openFeaturePopup(key) {
            const context = featureData[key];
            if (context) {
                document.getElementById('popupIcon').className = context.icon;
                document.getElementById('popupTitle').innerText = context.title;
                document.getElementById('popupDesc').innerText = context.desc;
                document.getElementById('featurePopup').style.display = 'flex';
            }
        }

        function closeFeaturePopup() { document.getElementById('featurePopup').style.display = 'none'; }

        document.querySelectorAll('video').forEach(v => {
            v.addEventListener('play', function() { handleVideoPlay(this); });
        });

        function closeWelcomeScreen() {
            const overlay = document.getElementById('welcomeOverlay');
            if (overlay) {
                overlay.classList.add('hide-welcome');
                setTimeout(() => { overlay.style.display = 'none'; }, 500);
            }
        }

        function activateSideLink(linkId) {
            document.querySelectorAll('.side-link').forEach(link => link.classList.remove('active'));
            const link = document.getElementById(linkId);
            if (link) link.classList.add('active');
        }

        function requireLogin(feature) {
            if (feature === 'booking') {
                const proceed = confirm('Please log in first to access Booking.\n\nWould you like to log in now?');
                if (proceed) window.location.href = 'login.php';
                return;
            }
            window.location.href = 'login.php';
        }

        function navigateTo(pageId, saveState = true) {
            const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

            if (pageId === 'booking' && !isLoggedIn) {
                requireLogin('booking');
                return;
            }

            if (saveState) {
                localStorage.setItem('currentPage', pageId);
                const activeSideLink = document.querySelector('.side-link.active');
                if (activeSideLink) {
                    localStorage.setItem('currentSideLink', activeSideLink.id);
                }
            }

            const activePage = document.querySelector('.app-page.page-active');
            const targetPage = document.getElementById('page-' + pageId);

            if (activePage && activePage !== targetPage && targetPage) {
                activePage.style.opacity = '0';
                activePage.style.transform = 'scale(0.98) translateY(12px)';

                setTimeout(() => {
                    activePage.classList.remove('page-active');
                    targetPage.classList.add('page-active');

                    requestAnimationFrame(() => {
                        targetPage.style.opacity = '1';
                        targetPage.style.transform = 'scale(1) translateY(0)';
                    });
                }, 250);
            } else if (!activePage && targetPage) {
                targetPage.classList.add('page-active');
                targetPage.style.opacity = '1';
                targetPage.style.transform = 'scale(1) translateY(0)';
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function navigateMenu(pageId, linkId) {
            if (pageId === 'booking' && !<?php echo $isLoggedIn ? 'true' : 'false'; ?>) {
                requireLogin('booking');
                return;
            }
            activateSideLink(linkId);
            navigateTo(pageId, true);
            toggleSideMenu();
        }

        function scrollToAbout() {
            navigateMenu('home', 'slink-about');
            setTimeout(() => {
                const aboutSection = document.getElementById('about');
                if (aboutSection) {
                    aboutSection.scrollIntoView({ behavior: 'smooth' });
                }
            }, 300);
        }

        let currentSlideIndex = 0;
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        let slideInterval;

        function showSlide(index) {
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            if (slides[index]) slides[index].classList.add('active');
            if (dots[index]) dots[index].classList.add('active');
            currentSlideIndex = index;
        }

        function nextSlide() { 
            let targetIndex = (currentSlideIndex + 1) % slides.length; 
            showSlide(targetIndex); 
        }
        function changeSlide(index) { showSlide(index); resetSlideTimer(); }
        function startSlideTimer() { slideInterval = setInterval(nextSlide, 4500); }
        function resetSlideTimer() { clearInterval(slideInterval); startSlideTimer(); }
        startSlideTimer();

        // ANIMATED LIGHTBOX PARA SA IMAGES
        function showImage(src) { 
            const box = document.getElementById('lightbox');
            document.getElementById('lightbox-img').src = src;
            
            const downloadBtn = document.getElementById('lightbox-download');
            // GIPAPAWAS NAKO ANG DOWNLOAD BUTTON SA TANAN BISAN ASA PA GIKAN
            downloadBtn.style.display = 'none';

            box.style.display = 'flex'; 
            setTimeout(() => box.classList.add('show'), 15);
        }
        
        function hideImage() { 
            const box = document.getElementById('lightbox');
            box.classList.remove('show');
            setTimeout(() => box.style.display = 'none', 300);
        }

        // FOOD MENU NAVIGATION (TABS HIGHLIGHT)
        function setActiveNav(element) {
            const links = document.querySelectorAll('.food-nav-menu a');
            links.forEach(link => link.classList.remove('active-nav'));
            element.classList.add('active-nav');
        }

        // ===============================================
        // PREVENT DATES EARLIER THAN CHECK-IN
        // ===============================================
        const checkInInput = document.getElementById('check_in');
        const checkOutInput = document.getElementById('check_out');
        if (checkInInput && checkOutInput) {
            checkInInput.addEventListener('change', function() {
                checkOutInput.min = this.value;
                if(checkOutInput.value < this.value) {
                    checkOutInput.value = this.value;
                }
            });
        }

        // ===============================================
        // QR CODE LOGIC
        // ===============================================
        function showQRCode(codeText, cottageName) {
            document.getElementById('qr-cottage-name').innerText = cottageName;
            document.getElementById('qrcode-container').innerHTML = ""; 
            
            new QRCode(document.getElementById("qrcode-container"), {
                text: codeText,
                width: 200,
                height: 200,
                colorDark : "#059669",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
            
            document.getElementById('qrModal').style.display = 'flex';
        }

        function closeQRModal() {
            document.getElementById('qrModal').style.display = 'none';
        }

        // ===============================================
        // MGA FUNCTION PARA SA BOOKING SYSTEM (WITH GCASH)
        // ===============================================

        async function submitBooking(e) {
            e.preventDefault();
            if (!<?php echo $isLoggedIn ? 'true' : 'false'; ?>) {
                requireLogin('booking');
                return;
            }
            const btn = document.getElementById('btn-submit-booking');
            const msg = document.getElementById('booking-msg');
            
            const cottage = document.getElementById('cottage_type').value;
            const checkIn = document.getElementById('check_in').value;
            const checkOut = document.getElementById('check_out').value;
            const gcashRef = document.getElementById('gcash_ref').value;

            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            btn.style.pointerEvents = 'none';

            try {
                const response = await fetch('process_booking.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `cottage=${encodeURIComponent(cottage)}&check_in=${checkIn}&check_out=${checkOut}&gcash_ref=${encodeURIComponent(gcashRef)}`
                });
                
                const result = await response.text();
                
                if(result.trim() === 'SUCCESS') {
                    msg.style.color = '#059669';
                    msg.innerHTML = '✅ Booking submitted! GCash payment recorded. Please check your email.';
                    document.getElementById('bookingForm').reset();
                    setTimeout(() => location.reload(), 2500);
                } else {
                    msg.style.color = '#ef4444';
                    msg.innerHTML = '❌ Error: ' + result;
                }
            } catch (error) {
                msg.style.color = '#ef4444';
                msg.innerHTML = '❌ System Error. Please try again.';
            }
            
            btn.innerHTML = 'Confirm Reservation';
            btn.style.pointerEvents = 'auto';
        }

        async function cancelBooking(bookingId) {
            if(!confirm("Are you sure you want to cancel this reservation?")) return;

            try {
                const formData = new FormData();
                formData.append('action', 'cancel');
                formData.append('booking_id', bookingId);

                const response = await fetch('process_booking.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.text();
                
                if(result.trim() === 'SUCCESS_CANCEL') {
                    alert('Reservation cancelled successfully.');
                    location.reload();
                } else {
                    alert('Failed to cancel. ' + result);
                }
            } catch(e) {
                alert('System Error.');
            }
        }

    </script>
</body>
</html>
