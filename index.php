<?php 
session_start();
require 'db_connect.php';

// KUNG WALA NAKA LOG-IN, E-KICK OUT PAINGON SA LOGIN PAGE
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// HANDLE PROFILE PICTURE UPLOAD
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_pic'])) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
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

// KUHAON ANG LATEST DATA SA USER
$stmt = $conn->prepare("SELECT full_name, email, profile_pic FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$currentUser = $stmt->fetch();

$userName = $currentUser['full_name'] ?? 'Guest';
$userEmail = $currentUser['email'] ?? '';
$profilePic = $currentUser['profile_pic'] ?? null;

$isAdmin = ($userEmail === 'admin@cherryjoe.com');
$userRole = $isAdmin ? 'Admin' : 'Visitor';

// Himoan og Initials
$nameParts = explode(' ', trim($userName));
$initials = strtoupper(substr($nameParts[0], 0, 1));
if (isset($nameParts[1])) {
    $initials .= strtoupper(substr($nameParts[1], 0, 1));
}

// KUHAON ANG MGA BOOKINGS SA USER (Para sa "My Bookings" list)
$userBookings = [];
try {
    $bookingStmt = $conn->prepare("SELECT * FROM bookings WHERE user_id = :uid ORDER BY created_at DESC");
    $bookingStmt->execute(['uid' => $_SESSION['user_id']]);
    $userBookings = $bookingStmt->fetchAll();
} catch(PDOException $e) {
    // Safely ignore kung wala pa na-create ang bookings table sa Supabase
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CherryJoe River Park</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- QR CODE GENERATOR SCRIPT -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <style>
        /* --- PREMIUM UI CONFIG & RESET --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif; -webkit-tap-highlight-color: transparent; }
        html { scroll-behavior: smooth; }
        body { background: #ffffff; color: #1e293b; padding-top: 60px; overflow-x: hidden; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #ffffff; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #10b981; }

        /* --- BACKGROUND MUSIC FLOATING BUTTON --- */
        .music-control-btn { position: fixed; bottom: 20px; right: 20px; width: 50px; height: 50px; background: linear-gradient(135deg, #10b981, #059669); color: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 20px; cursor: pointer; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4); z-index: 9999; transition: all 0.3s ease; border: 2px solid rgba(255, 255, 255, 0.5); }
        .music-control-btn:hover { transform: scale(1.1); }
        .music-control-btn.playing { animation: pulseMusic 1.5s infinite ease-in-out; background: linear-gradient(135deg, #ef4444, #b91c1c); box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4); }
        @keyframes pulseMusic { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.08); } }

        /* --- 1. PREMIUM LOADING SCREEN --- */
        #preloader { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #f8fafc; z-index: 1000000; display: flex; flex-direction: column; justify-content: center; align-items: center; transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.6s; }
        .loader-spinner { width: 55px; height: 55px; border: 3px solid rgba(16, 185, 129, 0.1); border-radius: 50%; border-top-color: #059669; animation: spin 0.7s cubic-bezier(0.42, 0, 0.58, 1) infinite; margin-bottom: 20px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loader-text { color: #059669; font-size: 13px; letter-spacing: 2px; font-weight: 600; text-transform: uppercase; animation: pulseText 1.5s infinite ease-in-out; }
        @keyframes pulseText { 0%, 100% { opacity: 0.6; } 50% { opacity: 1; color: #10b981; } }

        /* --- 2. SCROLL REVEAL TIMING PACK --- */
        .reveal { opacity: 0; transform: translateY(35px) scale(0.98); transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
        .reveal.active { opacity: 1; transform: translateY(0) scale(1); }

        /* --- 3. GLASSMORPHISM WELCOME OVERLAY --- */
        .welcome-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at center, rgba(248, 250, 252, 0.92), rgba(255, 255, 255, 0.99)); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); z-index: 99999; display: none; justify-content: center; align-items: center; padding: 20px; transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
        .welcome-container { max-width: 480px; width: 100%; max-height: 92vh; overflow-y: auto; padding: 10px 5px; text-align: center; }
        .welcome-container h1 { font-size: 32px; color: #1e293b; margin-bottom: 8px; font-weight: 800; letter-spacing: -0.5px; }
        .welcome-container .subtitle { font-size: 14px; color: #64748b; margin-bottom: 25px; line-height: 1.5; }
        .feature-table-box { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(20px); border-radius: 24px; padding: 22px; margin-bottom: 18px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); border: 1px solid rgba(0, 0, 0, 0.08); text-align: left; opacity: 0; }
        .table-header { font-size: 13px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; border-bottom: 1px solid rgba(0, 0, 0, 0.05); padding-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .table-row-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .table-cell { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); padding: 16px; border-radius: 16px; display: flex; flex-direction: column; gap: 6px; opacity: 1; transition: all 0.3s; }
        .table-cell i { font-size: 22px; margin-bottom: 2px; }
        .table-cell span { font-size: 14px; font-weight: 600; color: #1e293b; }
        .welcome-btn { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 16px 30px; font-size: 15px; font-weight: 700; border-radius: 50px; cursor: pointer; width: 100%; margin-top: 10px; transition: all 0.3s ease; }
        .welcome-overlay.hide-welcome { opacity: 0 !important; visibility: hidden !important; pointer-events: none !important; }

        /* --- 4. SMOOTH MOBILE PAGE APP TRANSITIONS --- */
        .app-page { display: none; opacity: 0; transform: scale(0.98) translateY(15px); transition: opacity 0.45s, transform 0.45s; }
        .app-page.page-active { display: block; opacity: 1; transform: scale(1) translateY(0); }

        /* --- 5. MODERN GLOBAL APP UI PARTS --- */
        nav { position: fixed; top: 0; width: 100%; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(14px); border-bottom: 1px solid rgba(0, 0, 0, 0.05); padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; z-index: 1000; height: 60px; }
        .nav-left { display: flex; align-items: center; gap: 15px; color: #1e293b; }
        .menu-toggle-btn { background: #10b981; color: white; width: 40px; height: 40px; border-radius: 10px; display: flex; justify-content: center; align-items: center; font-size: 20px; cursor: pointer; border: 1px solid #fff; }
        .logo { font-size: 20px; font-weight: bold; color: #1e293b; white-space: nowrap; margin-left: 5px;}
        section { padding: 50px 5%; max-width: 1200px; margin: 0 auto; }
        .title { text-align: center; font-size: 30px; margin-bottom: 35px; color: #1e293b; font-weight: 800; }

        /* --- ABOUT US DESIGN STYLES --- */
        .about-intro { text-align: center; max-width: 800px; margin: 0 auto 40px auto; color: #475569; font-size: 16px; line-height: 1.8; padding: 25px; background: rgba(16, 185, 129, 0.05); border-radius: 16px; border: 1px dashed rgba(16, 185, 129, 0.3); }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .feature-card { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); padding: 35px 25px; border-radius: 20px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .feature-card i { font-size: 30px; color: #059669; margin-bottom: 20px; background: rgba(16, 185, 129, 0.1); width: 75px; height: 75px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
        
        /* --- 6. PREMIUM HERO SLIDESHOW --- */
        .hero { height: calc(100vh - 60px); position: relative; overflow: hidden; display: flex; justify-content: center; align-items: flex-end; text-align: center; }
        .hero-slides { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; }
        .slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center; opacity: 0; transition: opacity 1.2s; }
        .slide.active { opacity: 1; }
        .hero-content { position: relative; z-index: 2; width: 100%; padding: 0 20px; margin-bottom: 40px; }
        .carousel-dots { display: flex; justify-content: center; gap: 10px; }
        .dot { width: 9px; height: 9px; background-color: rgba(0, 0, 0, 0.2); border-radius: 50%; cursor: pointer; }
        .dot.active { background-color: #059669; transform: scale(1.2); width: 26px; border-radius: 6px; }

        /* --- 7. FOOD MANAGEMENT PACK --- */
        .food-grid-container { max-width: 1000px; margin: 0 auto; }
        .food-item-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; }
        .food-card-with-img { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); border-radius: 20px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .food-card-with-img img { width: 100%; height: 200px; object-fit: cover; }
        .food-card-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        
        /* --- 8. EXPLORE ARCHITECTURE --- */
        .management { display: flex; gap: 20px; flex-wrap: wrap; }
        .management .card { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); padding: 25px; border-radius: 20px; flex: 1; min-width: 240px; }
        .cottage-section { display: flex; flex-direction: column; gap: 25px; }
        .cottage { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); border-radius: 20px; overflow: hidden; }
        .cottage img { width: 100%; height: 320px; object-fit: cover; }
        .entrance-card { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); padding: 22px; border-radius: 20px; display: flex; align-items: center; gap: 20px; }
        .facilities { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 20px; }
        .facility { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); border-radius: 20px; overflow: hidden; }
        .facility img { width: 100%; height: 210px; object-fit: cover; }
        .video-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .video-card { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); padding: 12px; border-radius: 20px; }
        .video-card video { width: 100%; height: 460px; object-fit: cover; border-radius: 14px; background: #000; display: block; }
        .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; }
        .gallery img { width: 100%; height: 150px; object-fit: cover; border-radius: 16px; cursor: pointer; }

        /* --- 9. WHITE SIDE MENU (DRAWER) SETTINGS --- */
        .side-menu-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 100001; opacity: 0; visibility: hidden; transition: 0.3s ease; }
        .side-menu-overlay.active { opacity: 1; visibility: visible; }
        .side-menu { position: fixed; top: 15px; left: -320px; width: 280px; height: calc(100vh - 30px); background: #ffffff; border-radius: 20px; z-index: 100002; display: flex; flex-direction: column; padding: 25px; transition: left 0.4s; overflow-y: auto; }
        .side-menu.active { left: 15px; }
        .close-menu-btn { position: absolute; top: -10px; right: -10px; background: #10b981; color: white; width: 40px; height: 40px; border-radius: 12px; display: flex; justify-content: center; align-items: center; cursor: pointer; }
        .profile-pic-container { position: relative; width: 70px; height: 70px; margin-bottom: 15px; cursor: pointer; border-radius: 18px; overflow: hidden; border: 2px solid rgba(16,185,129,0.3); }
        .profile-img-preview { width: 100%; height: 100%; object-fit: cover; }
        .profile-initials { background: #10b981; color: #ffffff; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 800; }
        
        .side-link { display: flex; align-items: center; gap: 15px; padding: 14px 16px; color: #475569; text-decoration: none; font-size: 16px; font-weight: 600; border-radius: 12px; cursor: pointer; }
        .side-link i { font-size: 20px; width: 25px; text-align: center; }
        .side-link:hover, .side-link.active { background: rgba(16, 185, 129, 0.08); color: #059669; }

        footer { background: #f8fafc; color: #64748b; text-align: center; padding: 25px; font-size: 13px; border-top: 1px solid rgba(0, 0, 0, 0.05); }

        /* BOOKING SPECIFIC STYLES */
        .booking-card { background: #fff; padding: 20px; border-radius: 16px; border: 1px solid #cbd5e1; box-shadow: 0 5px 15px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between; }
        .booking-status { display: inline-block; padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-Pending { background: #fef3c7; color: #d97706; }
        .status-Confirmed { background: #d1fae5; color: #059669; }
        .status-Cancelled { background: #fee2e2; color: #ef4444; }

        /* QR MODAL */
        .qr-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 999999; display: none; justify-content: center; align-items: center; padding: 20px; }
        .qr-modal-content { background: #ffffff; padding: 30px; border-radius: 20px; text-align: center; max-width: 350px; width: 100%; }
        #qrcode-container img { margin: 0 auto; border: 10px solid #fff; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

        @media (max-width: 600px) {
            section { padding: 40px 4%; }
            .title { font-size: 25px; margin-bottom: 25px; }
            .video-card video { height: 240px; border-radius: 12px; } 
            .gallery { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 8px; }
            .gallery img { height: 110px; border-radius: 12px; }
            .side-menu { width: 260px; left: -280px; }
        }
        .map-container { border-radius: 20px; overflow: hidden; border: 1px solid rgba(0, 0, 0, 0.05); }
        .map-btn { display: block; width: 100%; text-align: center; background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 15px; border-radius: 50px; text-decoration: none; font-weight: 700; margin-top: 15px; }
    </style>
</head>
<body>

    <!-- AUDIO TAG -->
    <audio id="bgMusic" loop autoplay preload="auto">
        <source src="assetsmusiconetime.mp3" type="audio/mpeg">
    </audio>

    <div class="music-control-btn" id="musicBtn" onclick="toggleMusic()">
        <i class="fas fa-play" id="musicIcon"></i>
    </div>

    <div id="preloader">
        <div class="loader-spinner"></div>
        <div class="loader-text">Loading Experience...</div>
    </div>

    <!-- SIDE MENU DRAWER -->
    <div class="side-menu-overlay" id="sideMenuOverlay" onclick="toggleSideMenu()"></div>
    <div class="side-menu" id="sideMenu">
        <div class="close-menu-btn" onclick="toggleSideMenu()">
            <i class="fas fa-times"></i>
        </div>
        
        <div class="side-profile">
            <div class="profile-pic-container" onclick="document.getElementById('profilePicInput').click()">
                <?php if ($profilePic): ?>
                    <img src="<?php echo htmlspecialchars($profilePic); ?>" class="profile-img-preview">
                <?php else: ?>
                    <div class="profile-initials"><?php echo $initials; ?></div>
                <?php endif; ?>
            </div>

            <form id="uploadForm" method="POST" enctype="multipart/form-data" style="display: none;">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" onchange="document.getElementById('uploadForm').submit()">
            </form>

            <div style="color: #1e293b; font-size: 22px; font-weight: 800;"><?php echo htmlspecialchars($userName); ?></div>
            <div style="color: #64748b; font-size: 14px; margin-bottom: 20px;"><?php echo $userRole; ?></div>
        </div>

        <div class="side-nav-links">
            <a onclick="navigateMenu('home', 'slink-home')" class="side-link active" id="slink-home"><i class="fas fa-home"></i> Home</a>
            
            <?php if(!$isAdmin): ?>
            <a onclick="navigateMenu('booking', 'slink-booking')" class="side-link" id="slink-booking"><i class="fas fa-calendar-alt"></i> Booking</a>
            <?php endif; ?>

            <a onclick="navigateMenu('explore', 'slink-explore')" class="side-link" id="slink-explore"><i class="fas fa-compass"></i> Explore</a>
            <a onclick="navigateMenu('food', 'slink-food')" class="side-link" id="slink-food"><i class="fas fa-utensils"></i> Food Menu</a>
            
            <!-- 3D GAME MENU LINK -->
            <a onclick="navigateMenu('games', 'slink-games')" class="side-link" id="slink-games"><i class="fas fa-gamepad"></i> 3D Game</a>

            <a onclick="scrollToAbout()" class="side-link" id="slink-about"><i class="fas fa-info-circle"></i> About Us</a>
            
            <?php if($isAdmin): ?>
            <a href="admin_dashboard.php" class="side-link" style="margin-top: 10px; color: #d97706;"><i class="fas fa-cogs"></i> Admin Panel</a>
            <?php endif; ?>

            <a href="logout.php" class="side-link" style="margin-top: auto; color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Logout</a>
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

    <!-- WELCOME OVERLAY -->
    <div class="welcome-overlay" id="welcomeOverlay">
        <div class="welcome-container">
            <h1>Hello, Welcome!</h1>
            <p class="subtitle">Quickly review CherryJoe River Park highlights inside our structured tables before moving forward.</p>
            <div class="feature-table-box table-one">
                <div class="table-header"><i class="fas fa-swimming-pool"></i> Table 1: Amenities</div>
                <div class="table-row-grid">
                    <div class="table-cell cell-1"><i class="fas fa-home" style="color: #f97316;"></i><span>Open Cottage</span></div>
                    <div class="table-cell cell-2"><i class="fas fa-water-ladder" style="color: #38bdf8;"></i><span>Resort Pool</span></div>
                </div>
            </div>
            <button class="welcome-btn" onclick="closeWelcomeScreen()">Enter Full Website</button>
        </div>
    </div>

    <nav>
        <div class="nav-left">
            <div class="menu-toggle-btn" onclick="toggleSideMenu()"><i class="fas fa-bars"></i></div>
            <div class="logo">CherryJoe River Park</div>
        </div>
    </nav>

    <!-- MAIN HOME PAGE -->
    <div id="page-home" class="app-page page-active">
        <section class="hero" id="home" style="padding:0; max-width:100%;">
            <div class="hero-slides">
                <div class="slide active" style="background-image: url('imagesgallery7.jpg');"></div>
                <div class="slide" style="background-image: url('imagescherryjoe-hero.jpg');"></div>
                <div class="slide" style="background-image: url('imagesgallery2.jpg');"></div>
            </div>
            <div class="hero-content">
                <div class="carousel-dots">
                    <div class="dot active" onclick="changeSlide(0)"></div>
                    <div class="dot" onclick="changeSlide(1)"></div>
                    <div class="dot" onclick="changeSlide(2)"></div>
                </div>
            </div>
        </section>

        <!-- ABOUT US SECTION -->
        <section id="about" class="reveal">
            <h2 class="title">About Us</h2>
            <div class="about-intro">
                <p><strong>CherryJoe River Park</strong> is a popular nature trip and eco-tourism destination located in Purok Magong-ong, Barangay San Rafael, Cateel, Davao Oriental.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card"><i class="fas fa-bridge-water"></i><h4>Scenic Hanging Bridge</h4><p>Sweeping views of the surrounding river.</p></div>
                <div class="feature-card"><i class="fas fa-swimmer"></i><h4>Natural Swimming</h4><p>Fresh, cool river water and scenic rocks.</p></div>
                <div class="feature-card"><i class="fas fa-users"></i><h4>Inclusivity</h4><p>Good for kids and LGBTQ+ friendly.</p></div>
            </div>
        </section>
        
        <section id="location" class="reveal">
            <h2 class="title">Visit Us</h2>
            <div class="map-container">
                <iframe src="https://maps.google.com/maps?q=CherryJoe%20River%20Park,%20Cateel,%20Davao%20Oriental&t=k&z=18&ie=UTF8&iwloc=&output=embed" width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </section>
    </div>

    <!-- EXPLORE PAGE -->
    <div id="page-explore" class="app-page">
        <section id="rates-and-cottage" class="reveal">
            <h2 class="title">Rates & Cottages</h2>
            <div class="cottage-section">
                <div class="entrance-card"><div class="entrance-icon"><i class="fas fa-ticket-alt"></i></div><div class="entrance-details"><h3>Entrance Fee</h3><p>₱20 per person</p></div></div>
                <div class="cottage"><img src="imagescottage.jpg"><div class="cottage-content"><h3>Open Cottage</h3><p>Starting at ₱100</p></div></div>
            </div>
        </section>
        <section id="video" style="text-align:center;" class="reveal">
            <h2 class="title">Resort Video Tour</h2>
            <div class="video-grid">
                <div class="video-card"><video controls playsinline preload="metadata" onplay="handleVideoPlay(this)"><source src="videosresort-tour.mp4" type="video/mp4"></video></div>
            </div>
        </section>
        <section id="gallery" class="reveal">
            <h2 class="title">Gallery</h2>
            <div class="gallery">
                <img src="imagesgallery1.jpg"><img src="imagesgallery2.jpg"><img src="imagesgallery3.jpg">
            </div>
        </section>
    </div>

    <!-- FOOD PAGE -->
    <div id="page-food" class="app-page">
        <section id="food-section">
            <h2 class="title">Delicious Menu</h2>
            <div class="food-grid-container">
                <?php
                $categories = ['Specialties', 'Combo Meal', 'Finger Foods', 'Drinks'];
                foreach ($categories as $cat) {
                    try {
                        $stmt = $conn->prepare("SELECT * FROM menu_items WHERE category = :cat ORDER BY id DESC");
                        $stmt->execute(['cat' => $cat]);
                        $items = $stmt->fetchAll();
                        if (count($items) > 0) {
                            echo "<div style='font-size:20px; font-weight:bold; color:#059669; margin:30px 0 10px 0;'>$cat</div>";
                            echo "<div class='food-item-grid'>";
                            foreach ($items as $item) {
                                $img = !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'https://placehold.co/400x250?text=No+Image';
                                echo "<div class='food-card-with-img'><img src='$img'><div class='food-card-body'><h3>".htmlspecialchars($item['name'])."</h3><div style='color:#059669; font-weight:bold;'>".htmlspecialchars($item['price'])."</div></div></div>";
                            }
                            echo "</div>";
                        }
                    } catch(PDOException $e) { }
                }
                ?>
            </div>
        </section>
    </div>

    <!-- USA KA 3D GAME PAGE (NAG CALL SA 3DGAME.HTML) -->
    <div id="page-games" class="app-page">
        <section class="reveal">
            <h2 class="title">CherryJoe 3D Game</h2>
            <div class="about-intro">
                <p>Play our custom 3D game built exclusively for CherryJoe River Park guests! Dodge the rocks to get a high score.</p>
            </div>
            
            <div class="games-grid" style="display: block;">
                <div style="max-width: 800px; margin: 0 auto; background: #f8fafc; border: 2px solid #10b981; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                    
                    <!-- E-CALL SI 3DGAME.HTML DIRI -->
                    <iframe src="3dgame.html" width="100%" height="500" frameborder="0" style="border: none; display: block;"></iframe>
                    
                    <div style="background: #ffffff; border-top: 1px solid #cbd5e1; padding: 15px; text-align: center;">
                        <h3 style="color: #059669; margin-bottom: 5px; font-size: 18px;"><i class="fas fa-ship"></i> CherryJoe River Dodge</h3>
                        <p style="font-size: 13px; color: #64748b;">Controls: Use Left & Right arrows (or tap the left/right sides of your screen) to dodge the rocks!</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- BOOKING & RESERVATION -->
    <?php if(!$isAdmin): ?>
    <div id="page-booking" class="app-page">
        <section class="reveal">
            <h2 class="title">Reservation & Booking</h2>
            
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

                    <div style="background: #f0fdf4; border: 1px dashed #10b981; padding: 20px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
                        <p style="color: #059669; font-weight: bold; font-size: 15px; margin-bottom: 5px;"><i class="fas fa-mobile-alt"></i> GCash Payment Info</p>
                        <p style="color: #334155; font-size: 14px;"><b>Number:</b> 0920 408 7956</p>
                        <p style="color: #334155; font-size: 14px; margin-bottom: 15px;"><b>Account Name:</b> CherryJoe River Park</p>
                        <input type="text" id="gcash_ref" placeholder="GCash Reference No." required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none;">
                    </div>

                    <button type="submit" id="btn-submit-booking" style="width: 100%; padding: 15px; background: #10b981; color: white; border: none; border-radius: 50px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 16px;">Confirm Reservation</button>
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
                                <h4 style="color: #1e293b; margin-bottom: 8px; font-size: 16px; font-weight: bold;"><?php echo htmlspecialchars($b['cottage_type']); ?></h4>
                                <p style="color: #64748b; font-size: 13px; margin-bottom: 5px;"><i class="fas fa-calendar-check"></i> <b>In:</b> <?php echo htmlspecialchars($b['check_in']); ?></p>
                                <p style="color: #64748b; font-size: 13px; margin-bottom: 15px;"><i class="fas fa-calendar-times"></i> <b>Out:</b> <?php echo htmlspecialchars($b['check_out']); ?></p>
                                <span class="booking-status status-<?php echo htmlspecialchars($b['status']); ?>"><?php echo htmlspecialchars($b['status']); ?></span>
                                
                                <?php if($is_expired && $b['status'] !== 'Cancelled'): ?>
                                    <span style="font-size: 11px; color: #ef4444; font-weight: bold; margin-left: 5px;">(Expired)</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if($b['status'] === 'Confirmed'): ?>
                                <?php if(!$is_expired): ?>
                                    <button onclick="showQRCode('CJRP-<?php echo $b['id']; ?>', '<?php echo addslashes($b['cottage_type']); ?>')" style="margin-top: 15px; background: #1e293b; color: white; border: none; padding: 10px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; transition: 0.2s;"><i class="fas fa-qrcode"></i> Show QR Code</button>
                                <?php else: ?>
                                    <button disabled style="margin-top: 15px; background: #94a3b8; color: white; border: none; padding: 10px; border-radius: 8px; cursor: not-allowed; font-weight: bold; width: 100%;"><i class="fas fa-qrcode"></i> QR Code Expired</button>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if($b['status'] === 'Pending' && !$is_expired): ?>
                                <button onclick="cancelBooking(<?php echo $b['id']; ?>)" style="margin-top: 10px; background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; padding: 10px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; transition: 0.2s;"><i class="fas fa-times-circle"></i> Cancel Reservation</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #64748b; width: 100%; font-size: 15px;">You have no reservations yet.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <?php endif; ?>

    <footer>© 2026 CherryJoe River Park</footer>

    <script>
        const audio = document.getElementById('bgMusic');
        const musicBtn = document.getElementById('musicBtn');
        const musicIcon = document.getElementById('musicIcon');

        function toggleMusic() {
            if (audio.paused) {
                audio.play().then(() => { musicIcon.className = "fas fa-pause"; musicBtn.classList.add('playing'); }).catch(e => console.log("Blocked"));
            } else {
                audio.pause(); musicIcon.className = "fas fa-play"; musicBtn.classList.remove('playing');
            }
        }

        function handleVideoPlay(playingVideo) {
            if (!audio.paused) { audio.pause(); musicIcon.className = "fas fa-play"; musicBtn.classList.remove('playing'); }
            document.querySelectorAll('video').forEach(v => { if (v !== playingVideo) v.pause(); });
        }

        function toggleSideMenu() {
            document.getElementById('sideMenu').classList.toggle('active');
            document.getElementById('sideMenuOverlay').classList.toggle('active');
        }

        function navigateMenu(pageId, linkId) {
            document.querySelectorAll('.side-link').forEach(link => link.classList.remove('active'));
            if(linkId) document.getElementById(linkId).classList.add('active');
            navigateTo(pageId);
            toggleSideMenu();
        }

        function scrollToAbout() {
            navigateMenu('home', 'slink-about');
            setTimeout(() => { document.getElementById('about').scrollIntoView({ behavior: 'smooth' }); }, 300);
        }

        window.addEventListener('load', () => {
            document.getElementById('preloader').style.opacity = '0';
            setTimeout(() => { 
                document.getElementById('preloader').style.visibility = 'hidden'; 
                initScrollRevealEngine();
                const welcomeOverlay = document.getElementById('welcomeOverlay');
                if (localStorage.getItem('welcomeScreenDismissed') === 'true') {
                    welcomeOverlay.classList.add('hide-welcome');
                    welcomeOverlay.style.display = 'none';
                    if (localStorage.getItem('currentPage')) navigateTo(localStorage.getItem('currentPage'));
                } else {
                    welcomeOverlay.style.display = 'flex';
                }
            }, 600);
            audio.play().then(() => { musicIcon.className = "fas fa-pause"; musicBtn.classList.add('playing'); }).catch(e=>console.log("Blocked"));
        });

        function initScrollRevealEngine() {
            const observer = new IntersectionObserver((entries) => { entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('active'); }); }, { threshold: 0.08 });
            document.querySelectorAll('.reveal').forEach(el => observer.observe(element));
        }

        function closeWelcomeScreen() {
            localStorage.setItem('welcomeScreenDismissed', 'true');
            localStorage.setItem('currentPage', 'home');
            document.getElementById('welcomeOverlay').style.display = 'none';
            toggleMusic();
        }

        function navigateTo(pageId) {
            if (localStorage.getItem('welcomeScreenDismissed') === 'true') localStorage.setItem('currentPage', pageId);
            const activePage = document.querySelector('.app-page.page-active');
            const targetPage = document.getElementById('page-' + pageId);
            if (activePage && activePage !== targetPage && targetPage) {
                activePage.style.opacity = '0';
                setTimeout(() => {
                    activePage.classList.remove('page-active');
                    targetPage.classList.add('page-active');
                    requestAnimationFrame(() => targetPage.style.opacity = '1');
                }, 250);
            } else if (!activePage && targetPage) {
                targetPage.classList.add('page-active');
                targetPage.style.opacity = '1';
            }
            window.scrollTo({ top: 0 });
        }

        document.getElementById('check_in').addEventListener('change', function() {
            document.getElementById('check_out').min = this.value;
            if(document.getElementById('check_out').value < this.value) {
                document.getElementById('check_out').value = this.value;
            }
        });

        function showQRCode(codeText, cottageName) {
            document.getElementById('qr-cottage-name').innerText = cottageName;
            document.getElementById('qrcode-container').innerHTML = ""; 
            new QRCode(document.getElementById("qrcode-container"), { text: codeText, width: 200, height: 200, colorDark : "#059669", colorLight : "#ffffff" });
            document.getElementById('qrModal').style.display = 'flex';
        }

        function closeQRModal() { document.getElementById('qrModal').style.display = 'none'; }

        async function submitBooking(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-booking');
            const msg = document.getElementById('booking-msg');
            
            btn.innerHTML = 'Processing...'; btn.style.pointerEvents = 'none';

            try {
                const response = await fetch('process_booking.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `cottage=${encodeURIComponent(document.getElementById('cottage_type').value)}&check_in=${document.getElementById('check_in').value}&check_out=${document.getElementById('check_out').value}&gcash_ref=${encodeURIComponent(document.getElementById('gcash_ref').value)}`
                });
                const result = await response.text();
                if(result.trim() === 'SUCCESS') { msg.style.color = '#059669'; msg.innerHTML = '✅ Booking submitted!'; setTimeout(() => location.reload(), 2500); }
                else { msg.style.color = '#ef4444'; msg.innerHTML = '❌ Error: ' + result; }
            } catch (error) { msg.style.color = '#ef4444'; msg.innerHTML = '❌ System Error.'; }
            btn.innerHTML = 'Confirm Reservation'; btn.style.pointerEvents = 'auto';
        }

        async function cancelBooking(bookingId) {
            if(!confirm("Cancel this reservation?")) return;
            const formData = new FormData(); formData.append('action', 'cancel'); formData.append('booking_id', bookingId);
            if((await fetch('process_booking.php', { method: 'POST', body: formData }).then(r=>r.text())).trim() === 'SUCCESS_CANCEL') location.reload();
        }
    </script>
</body>
</html>
