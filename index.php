<?php 
session_start();
require 'db_connect.php';

// KUNG WALA NAKA LOG-IN, E-KICK OUT PAINGON SA LOGIN PAGE
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// I-check kung Admin ba base sa email kay gikuha na nato ang role column sa database
$isAdmin = ($_SESSION['email'] === 'admin@cherryjoe.com');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CherryJoe River Park</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>

    <style>
        /* --- PREMIUM UI CONFIG & RESET --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif; -webkit-tap-highlight-color: transparent; }
        html { scroll-behavior: smooth; }
        body { background: #ffffff; color: #1e293b; padding-top: 60px; padding-bottom: 90px; overflow-x: hidden; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #ffffff; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #10b981; }

        /* --- BACKGROUND MUSIC FLOATING BUTTON --- */
        .music-control-btn { position: fixed; bottom: 85px; right: 20px; width: 50px; height: 50px; background: linear-gradient(135deg, #10b981, #059669); color: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 20px; cursor: pointer; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4); z-index: 9999; transition: all 0.3s ease; border: 2px solid rgba(255, 255, 255, 0.5); }
        .music-control-btn:hover { transform: scale(1.1); }
        .music-control-btn.playing { animation: pulseMusic 1.5s infinite ease-in-out; background: linear-gradient(135deg, #ef4444, #b91c1c); box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4); }
        @keyframes pulseMusic { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.08); } }

        /* --- LOADING SCREEN --- */
        #preloader { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #f8fafc; z-index: 1000000; display: flex; flex-direction: column; justify-content: center; align-items: center; transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.6s; }
        .loader-spinner { width: 55px; height: 55px; border: 3px solid rgba(16, 185, 129, 0.1); border-radius: 50%; border-top-color: #059669; animation: spin 0.7s cubic-bezier(0.42, 0, 0.58, 1) infinite; margin-bottom: 20px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loader-text { color: #059669; font-size: 13px; letter-spacing: 2px; font-weight: 600; text-transform: uppercase; animation: pulseText 1.5s infinite ease-in-out; }
        @keyframes pulseText { 0%, 100% { opacity: 0.6; } 50% { opacity: 1; color: #10b981; } }

        /* --- APP PAGES & NAVIGATION --- */
        .reveal { opacity: 0; transform: translateY(35px) scale(0.98); transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
        .reveal.active { opacity: 1; transform: translateY(0) scale(1); }
        .app-page { display: none; opacity: 0; transform: scale(0.98) translateY(15px); transition: opacity 0.45s cubic-bezier(0.16, 1, 0.3, 1), transform 0.45s cubic-bezier(0.16, 1, 0.3, 1); }
        .app-page.page-active { display: block; opacity: 1; transform: scale(1) translateY(0); }

        nav { position: fixed; top: 0; width: 100%; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border-bottom: 1px solid rgba(0, 0, 0, 0.05); padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; z-index: 1000; height: 60px; }
        .nav-left { display: flex; align-items: center; gap: 15px; color: #1e293b; }
        .back-btn { font-size: 20px; cursor: pointer; transition: 0.3s; color: #1e293b; }
        .logo { font-size: 20px; font-weight: bold; }
        section { padding: 50px 5%; max-width: 1200px; margin: 0 auto; }
        .title { text-align: center; font-size: 30px; margin-bottom: 35px; color: #1e293b; font-weight: 800; }

        /* --- PROFILE SYSTEM & EXTRA CSS --- */
        .profile-container { max-width: 500px; margin: 0 auto; background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); border-radius: 24px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.05); }
        .profile-banner { background: linear-gradient(135deg, #10b981, #059669); height: 120px; position: relative; }
        .profile-avatar-wrapper { width: 105px; height: 105px; border-radius: 50%; background: #ffffff; padding: 5px; position: absolute; bottom: -50px; left: 50%; transform: translateX(-50%); }
        .profile-avatar { width: 100%; height: 100%; border-radius: 50%; background: #ffffff; display: flex; justify-content: center; align-items: center; overflow: hidden; border: 1px solid rgba(0, 0, 0, 0.08); }
        .profile-avatar i { font-size: 40px; color: #059669; }
        .profile-details { padding: 65px 22px 25px 22px; text-align: center; }
        .welcome-visitor { font-size: 12px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px; display: block; }
        .membership-tag { display: inline-block; background: rgba(16, 185, 129, 0.1); color: #059669; font-weight: 700; font-size: 11px; padding: 5px 14px; border-radius: 50px; margin-top: 14px; border: 1px solid rgba(16, 185, 129, 0.2); }
        .profile-menu-list { text-align: left; margin-top: 25px; border-top: 1px solid rgba(0, 0, 0, 0.05); padding-top: 15px; }
        .profile-menu-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 8px; color: #475569; text-decoration: none; font-size: 15px; border-bottom: 1px solid rgba(0, 0, 0, 0.03); cursor: pointer; transition: 0.2s ease; border-radius: 10px; }
        .profile-menu-item:hover { color: #059669; padding-left: 14px; background: rgba(16, 185, 129, 0.05); }
        .profile-menu-item div i { margin-right: 12px; width: 20px; color: #10b981; }

        .logout-btn { color: #ef4444 !important; }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.05) !important; padding-left: 14px; }
        .logout-btn div i { color: #ef4444 !important; }
        .admin-badge { background: rgba(245, 158, 11, 0.1) !important; color: #d97706 !important; border-color: rgba(245, 158, 11, 0.2) !important; }
        .admin-avatar { color: #d97706 !important; }

        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(16px); display: flex; justify-content: space-around; padding: 14px 0; border-top: 1px solid rgba(0,0,0,0.05); z-index: 1000; }
        .nav-item { text-decoration: none; color: #94a3b8; display: flex; flex-direction: column; align-items: center; font-size: 11px; cursor: pointer; }
        .nav-item.active { color: #059669; font-weight: 700; }
        .nav-item i { font-size: 19px; margin-bottom: 4px; }
    </style>
</head>
<body>

    <div id="preloader">
        <div class="loader-spinner"></div>
        <div class="loader-text">Loading Experience...</div>
    </div>

    <nav>
        <div class="nav-left">
            <i class="fas fa-chevron-left back-btn"></i>
            <div class="logo">CherryJoe River Park</div>
        </div>
    </nav>

    <!-- MAIN HOME PAGE -->
    <div id="page-home" class="app-page page-active">
        <section class="reveal">
            <h2 class="title">Welcome to CherryJoe</h2>
            <p style="text-align:center;">Enjoy the beauty of Cateel, Davao Oriental.</p>
        </section>
    </div>

    <!-- FOOD MENU PAGE -->
    <div id="page-food" class="app-page">
        <section id="food-section">
            <h2 class="title">Delicious Menu</h2>
            <div>
                <?php
                // Kuhaon nato sa Supabase database gamit ang PDO
                $categories = ['Specialties', 'Combo Meal', 'Finger Foods', 'Drinks'];
                foreach ($categories as $cat) {
                    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE category = :cat");
                    $stmt->execute(['cat' => $cat]);
                    $items = $stmt->fetchAll();
                    
                    if (count($items) > 0) {
                        echo "<h3 style='margin: 20px 0 10px; color:#059669;'><i class='fas fa-utensils'></i> $cat</h3>";
                        echo "<div style='display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;'>";
                        
                        foreach($items as $item) {
                            echo "<div style='border:1px solid #ddd; padding:15px; border-radius:10px;'>
                                    <h4>".htmlspecialchars($item['name'])."</h4>
                                    <p style='color:#666;'>".htmlspecialchars($item['description'])."</p>
                                    <h4 style='color:#059669;'>".htmlspecialchars($item['price'])."</h4>
                                  </div>";
                        }
                        echo "</div>";
                    }
                }
                ?>
            </div>
        </section>
    </div>

    <!-- PROFILE PAGE -->
    <div id="page-profile" class="app-page">
        <section id="profile-section">
            <h2 class="title">User Profile</h2>
            <div class="profile-container">
                <div class="profile-banner">
                    <div class="profile-avatar-wrapper">
                        <div class="profile-avatar">
                            <?php if($isAdmin): ?>
                                <i class="fas fa-user-shield admin-avatar"></i>
                            <?php else: ?>
                                <i class="fas fa-user-astronaut"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="profile-details">
                    <span class="welcome-visitor">Welcome!</span>
                    <h2><?php echo htmlspecialchars($_SESSION['name']); ?></h2>
                    <p class="email"><?php echo htmlspecialchars($_SESSION['email']); ?></p>
                    
                    <?php if($isAdmin): ?>
                        <span class="membership-tag admin-badge"><i class="fas fa-crown"></i> Park Administrator</span>
                    <?php else: ?>
                        <span class="membership-tag"><i class="fas fa-medal"></i> Resort Visitor</span>
                    <?php endif; ?>

                    <div class="profile-menu-list">
                        
                        <!-- GIPAKITA LANG NI NGA BUTTON KUNG ADMIN ANG NAG LOGIN -->
                        <?php if($isAdmin): ?>
                            <a href="admin_dashboard.php" class="profile-menu-item">
                                <div><i class="fas fa-cogs" style="color: #d97706;"></i> Admin Dashboard</div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>

                        <a onclick="navigateTo('food', 'nav-food')" class="profile-menu-item">
                            <div><i class="fas fa-utensils"></i> View Food Menu</div>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        
                        <!-- LOGOUT BUTTON -->
                        <a href="logout.php" class="profile-menu-item logout-btn">
                            <div><i class="fas fa-sign-out-alt"></i> Logout</div>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- BOTTOM NAVIGATION -->
    <div class="bottom-nav">
        <div class="nav-item active" id="nav-home" onclick="navigateTo('home', 'nav-home')"><i class="fas fa-home"></i><span>Home</span></div>
        <div class="nav-item" id="nav-food" onclick="navigateTo('food', 'nav-food')"><i class="fas fa-utensils"></i><span>Food</span></div>
        <div class="nav-item" id="nav-profile" onclick="navigateTo('profile', 'nav-profile')"><i class="fas fa-user"></i><span>Profile</span></div>
    </div>

    <script>
        window.addEventListener('load', () => {
            const preloader = document.getElementById('preloader');
            preloader.style.opacity = '0';
            setTimeout(() => { preloader.style.visibility = 'hidden'; }, 600);
        });

        function navigateTo(pageId, navItemId) {
            document.querySelectorAll('.app-page').forEach(page => page.classList.remove('page-active'));
            document.getElementById('page-' + pageId).classList.add('page-active');
            
            document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
            document.getElementById(navItemId).classList.add('active');
            window.scrollTo({ top: 0 });
        }
    </script>
</body>
</html>
