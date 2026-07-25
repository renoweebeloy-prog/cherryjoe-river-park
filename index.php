<?php 
session_start();
require 'db_connect.php';

// KUNG WALA NAKA LOG-IN, E-KICK OUT PAINGON SA LOGIN PAGE
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// I-check kung Admin ba base sa email
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

        /* --- 4. SMOOTH MOBILE PAGE APP TRANSITIONS --- */
        .app-page { display: none; opacity: 0; transform: scale(0.98) translateY(15px); transition: opacity 0.45s cubic-bezier(0.16, 1, 0.3, 1), transform 0.45s cubic-bezier(0.16, 1, 0.3, 1); }
        .app-page.page-active { display: block; opacity: 1; transform: scale(1) translateY(0); }

        /* --- 5. MODERN GLOBAL APP UI PARTS --- */
        nav { position: fixed; top: 0; width: 100%; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border-bottom: 1px solid rgba(0, 0, 0, 0.05); padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; z-index: 1000; height: 60px; }
        .nav-left { display: flex; align-items: center; gap: 15px; color: #1e293b; }
        .back-btn { font-size: 20px; cursor: pointer; transition: 0.3s; color: #1e293b; }
        .back-btn:hover { color: #059669; }
        .logo { font-size: 20px; font-weight: bold; color: #1e293b; white-space: nowrap; }
        section { padding: 50px 5%; max-width: 1200px; margin: 0 auto; }
        .title { text-align: center; font-size: 30px; margin-bottom: 35px; color: #1e293b; font-weight: 800; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
        .card { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); padding: 25px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .card:hover { transform: translateY(-5px); border-color: rgba(16, 185, 129, 0.4); box-shadow: 0 15px 35px rgba(16, 185, 129, 0.08); }
        .card h3 { color: #059669; margin-bottom: 10px; font-weight: 700; font-size: 18px; }
        .card p { color: #475569; font-size: 15px; line-height: 1.5; }

        /* --- 6. PREMIUM HERO SLIDESHOW --- */
        .hero { height: calc(100vh - 60px); position: relative; overflow: hidden; display: flex; justify-content: center; align-items: flex-end; text-align: center; }
        .hero-slides { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; }
        .slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center; background-repeat: no-repeat; opacity: 0; transition: opacity 1.2s cubic-bezier(0.4, 0, 0.2, 1), transform 7s ease; transform: scale(1.06); }
        .slide.active { opacity: 1; transform: scale(1); }
        .hero::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to bottom, transparent 30%, #ffffff 100%); z-index: 1; }
        .hero-content { position: relative; color: #1e293b; z-index: 2; width: 100%; padding: 0 20px; margin-bottom: 40px; }
        .carousel-dots { display: flex; justify-content: center; gap: 10px; }
        .dot { width: 9px; height: 9px; background-color: rgba(0, 0, 0, 0.2); border-radius: 50%; cursor: pointer; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .dot.active { background-color: #059669; transform: scale(1.2); width: 26px; border-radius: 6px; }

        /* --- 7. FOOD MANAGEMENT PACK --- */
        .food-grid-container { max-width: 1000px; margin: 0 auto; }
        .food-category-title { font-size: 20px; color: #1e293b; border-left: 5px solid #059669; padding-left: 12px; margin: 40px 0 20px 0; font-weight: 700; letter-spacing: 0.5px; }
        .food-item-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; }
        .food-card-with-img { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); border-radius: 20px; overflow: hidden; display: flex; flex-direction: column; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .food-card-with-img:hover { transform: translateY(-5px); border-color: rgba(16, 185, 129, 0.4); box-shadow: 0 15px 30px rgba(16, 185, 129, 0.1); }
        .food-card-with-img img { width: 100%; height: 200px; object-fit: cover; transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
        .food-card-with-img:hover img { transform: scale(1.05); }
        .food-card-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .food-card-body h3 { font-size: 17px; color: #1e293b; margin-bottom: 8px; font-weight: 600; }
        .food-card-body p.desc { font-size: 13px; color: #64748b; margin-bottom: 12px; line-height: 1.4; }
        .food-card-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(0, 0, 0, 0.05); padding-top: 12px; }
        .food-price { font-size: 18px; font-weight: 700; color: #059669; }
        .food-status { font-size: 11px; background: rgba(16, 185, 129, 0.1); color: #059669; padding: 4px 12px; border-radius: 50px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .drinks-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
        .drink-item { background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); padding: 15px; border-radius: 14px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s ease; }
        .drink-item:hover { border-color: #059669; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.08); }
        .drink-name { font-size: 15px; font-weight: 600; color: #1e293b; }

        /* --- 8. PREMIUM PROFILE & SETTINGS SYSTEM --- */
        .profile-container { max-width: 500px; margin: 0 auto; background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); border-radius: 24px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.05); }
        .profile-banner { background: linear-gradient(135deg, #10b981, #059669); height: 120px; position: relative; }
        .profile-avatar-wrapper { width: 105px; height: 105px; border-radius: 50%; background: #ffffff; padding: 5px; position: absolute; bottom: -50px; left: 50%; transform: translateX(-50%); }
        .profile-avatar { width: 100%; height: 100%; border-radius: 50%; background: #ffffff; display: flex; justify-content: center; align-items: center; overflow: hidden; border: 1px solid rgba(0, 0, 0, 0.08); }
        .profile-avatar i { font-size: 40px; color: #059669; }
        .profile-details { padding: 65px 22px 25px 22px; text-align: center; }
        .welcome-visitor { font-size: 12px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px; display: block; }
        .profile-details h2 { font-size: 22px; color: #1e293b; font-weight: 700; }
        .profile-details .email { color: #64748b; font-size: 14px; margin-top: 4px; }
        .membership-tag { display: inline-block; background: rgba(16, 185, 129, 0.1); color: #059669; font-weight: 700; font-size: 11px; padding: 5px 14px; border-radius: 50px; margin-top: 14px; border: 1px solid rgba(16, 185, 129, 0.2); letter-spacing: 0.5px; }
        .profile-menu-list { text-align: left; margin-top: 25px; border-top: 1px solid rgba(0, 0, 0, 0.05); padding-top: 15px; }
        .profile-menu-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 8px; color: #475569; text-decoration: none; font-size: 15px; border-bottom: 1px solid rgba(0, 0, 0, 0.03); cursor: pointer; transition: 0.2s ease; border-radius: 10px; }
        .profile-menu-item:hover { color: #059669; padding-left: 14px; background: rgba(16, 185, 129, 0.05); }
        .profile-menu-item div i { margin-right: 12px; width: 20px; color: #10b981; }
        .logout-btn { color: #ef4444 !important; }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.05) !important; padding-left: 14px; }
        .logout-btn div i { color: #ef4444 !important; }
        .admin-badge { background: rgba(245, 158, 11, 0.1) !important; color: #d97706 !important; border-color: rgba(245, 158, 11, 0.2) !important; }
        .admin-avatar { color: #d97706 !important; }

        /* --- 9. EXPLORE ARCHITECTURE --- */
        .management { display: flex; gap: 20px; flex-wrap: wrap; }
        .management .card { flex: 1; min-width: 240px; }
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
        .gallery img { width: 100%; height: 150px; object-fit: cover; border-radius: 16px; cursor: pointer; transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.4s, box-shadow 0.4s; border: 1px solid rgba(0, 0, 0, 0.08); }
        .gallery img:hover { transform: scale(1.05) translateY(-3px); box-shadow: 0 12px 24px rgba(0,0,0,0.15); border-color: #059669; }

        /* --- OVERLAYS & COMPONENT ENGINE --- */
        .contact { background: #f8fafc; border-top: 1px solid rgba(0, 0, 0, 0.05); text-align: center; }
        .contact p { margin: 12px 0; color: #475569; font-size: 15px; }
        .contact a { color: #059669; text-decoration: none; font-weight: 600; }
        footer { background: #f8fafc; color: #64748b; text-align: center; padding: 25px; font-size: 13px; border-top: 1px solid rgba(0, 0, 0, 0.05); }
        .lightbox { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(248, 250, 252, 0.94); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); display: none; flex-direction: column; gap: 20px; justify-content: center; align-items: center; z-index: 99999; opacity: 0; transition: opacity 0.3s ease; }
        .lightbox img { max-width: 92%; max-height: 70%; border-radius: 16px; box-shadow: 0 30px 60px rgba(0,0,0,0.15); transform: scale(0.93); transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1); border: 2px solid #ffffff; }
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
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); display: flex; justify-content: space-around; align-items: center; padding: 14px 0; box-shadow: 0 -5px 20px rgba(0,0,0,0.05); border-top: 1px solid rgba(0, 0, 0, 0.05); z-index: 1000; }
        .nav-item { text-decoration: none; color: #94a3b8; display: flex; flex-direction: column; align-items: center; font-size: 11px; gap: 4px; transition: 0.25s cubic-bezier(0.16, 1, 0.3, 1); cursor: pointer; }
        .nav-item i { font-size: 19px; transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
        .nav-item:hover i { transform: translateY(-2px); color: #059669; }
        .nav-item.active { color: #059669; font-weight: 700; }
        .nav-item.active i { transform: scale(1.15) translateY(-1px); }

        @media (max-width: 600px) {
            section { padding: 40px 4%; }
            .title { font-size: 25px; margin-bottom: 25px; }
            .video-card video { height: 240px; border-radius: 12px; } 
            .gallery { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 8px; }
            .gallery img { height: 110px; border-radius: 12px; }
            .welcome-container h1 { font-size: 26px; }
            .feature-table-box { padding: 18px; border-radius: 20px; }
            .table-row-grid { gap: 10px; }
            .table-cell { padding: 14px; border-radius: 12px; }
        }
        .map-container { border-radius: 20px; overflow: hidden; border: 1px solid rgba(0, 0, 0, 0.05); box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-top: 20px; }
        .map-btn { display: block; width: 100%; text-align: center; background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 15px; border-radius: 50px; text-decoration: none; font-weight: 700; margin-top: 15px; transition: 0.3s; }
        .map-btn:hover { box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4); transform: translateY(-2px); }
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
            <i class="fas fa-chevron-left back-btn" onclick="goBack()"></i>
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

        <section id="about" class="reveal">
            <h2 class="title">About CherryJoe River Park</h2>
            <div class="grid">
                <div class="card"><h3>📍 Location</h3><p>Purok Magong-ong Brgy. San Rafael Cateel Davao Oriental</p></div>
                <div class="card"><h3>🕒 Opening Hours</h3><p>11:00 AM - 2:00 AM</p></div>
                <div class="card"><h3>📞 Contact</h3><p>0920 408 7956</p></div>
                <div class="card"><h3>📧 Email</h3><p>cherryday103080@gmail.com</p></div>
            </div>
        </section>

        <section id="location" class="reveal">
            <h2 class="title">Visit Us</h2>
            <div class="map-container">
                <iframe 
                    src="https://maps.google.com/maps?q=CherryJoe%20River%20Park,%20Cateel,%20Davao%20Oriental&t=k&z=18&ie=UTF8&iwloc=&output=embed" 
                    width="100%" 
                    height="350" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            <a href="https://share.google/7u3FzgC9UR5maQkR4" target="_blank" class="map-btn">
                <i class="fas fa-location-arrow"></i> Get Directions
            </a>
        </section>

        <section class="contact reveal" id="contact" style="max-width:100%;">
            <h2 class="title" style="color:#1e293b;">Contact Us</h2>
            <p><i class="fas fa-map-marker-alt"></i> Purok Magong-ong Brgy. San Rafael Cateel Davao Oriental</p>
            <p><i class="fas fa-phone"></i> 0920 408 7956</p>
            <p><i class="fas fa-envelope"></i> cherryday103080@gmail.com</p>
            <p><i class="fab fa-facebook"></i> Facebook: <a href="https://www.facebook.com/search/top/?q=CherryJoe%20River%20Park" target="_blank">CherryJoe River Park</a></p>
        </section>
    </div>

    <!-- EXPLORE PAGE -->
    <div id="page-explore" class="app-page">
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
                <div class="cottage"><img src="imagescottage.jpg" alt="Open Cottage"><div class="cottage-content"><h3>Open Cottage</h3><p><strong>Price:</strong> ₱100</p><p><strong>Capacity:</strong> 8 Persons</p></div></div>
            </div>
        </section>
        
        <section id="facilities" class="reveal">
            <h2 class="title">Facilities</h2>
            <div class="facilities">
                <div class="facility"><img src="imagespool.jpg" alt="Pool"><div class="facility-content"><h3>Pool</h3></div></div>
                <div class="facility"><img src="imagesriver.jpg" alt="River"><div class="facility-content"><h3>River View</h3></div></div>
                <div class="facility"><img src="imagesrestaurant.jpg" alt="Restaurant"><div class="facility-content"><h3>Restaurant</h3></div></div>
                <div class="facility"><img src="imagesfunctionhall.jpg" alt="Function Hall"><div class="facility-content"><h3>Function Hall</h3></div></div>
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
        <section id="food-section">
            <h2 class="title">Delicious Menu</h2>
            <div class="food-grid-container">
                
                <?php
                // Kuhaon nato sa Supabase database gamit ang PDO
                $categories = ['Specialties', 'Combo Meal', 'Finger Foods', 'Drinks'];
                
                foreach ($categories as $cat) {
                    try {
                        $stmt = $conn->prepare("SELECT * FROM menu_items WHERE category = :cat ORDER BY id DESC");
                        $stmt->execute(['cat' => $cat]);
                        $items = $stmt->fetchAll();
                        
                        if (count($items) > 0) {
                            
                            // Magbutang tag icon depende sa category
                            $icon = 'fas fa-star';
                            if ($cat == 'Combo Meal') $icon = 'fas fa-concierge-bell';
                            if ($cat == 'Finger Foods') $icon = 'fas fa-hamburger';
                            if ($cat == 'Drinks') $icon = 'fas fa-glass-cheers';

                            echo "<div class='food-category-title'><i class='$icon'></i> $cat</div>";
                            
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
                                    echo "<img src='$img' alt='".htmlspecialchars($item['name'])."' onerror=\"this.src='https://placehold.co/400x250?text=No+Image'\">";
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
                    } catch(PDOException $e) {
                        // Error handling silently if needed
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
                    <h2><?php echo htmlspecialchars($_SESSION['name'] ?? 'Guest'); ?></h2>
                    <p class="email"><?php echo htmlspecialchars($_SESSION['email'] ?? 'guest@cherryjoe.com'); ?></p>
                    
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
                        <a onclick="navigateTo('explore', 'nav-about')" class="profile-menu-item">
                            <div><i class="fas fa-compass"></i> Explore Facilities</div>
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

    <!-- FOOTER -->
    <footer>© 2026 CherryJoe River Park</footer>

    <!-- LIGHTBOX -->
    <div class="lightbox" id="lightbox" onclick="hideImage()">
        <img id="lightbox-img" alt="Lightbox Preview">
        <a id="lightbox-download" href="#" download="CherryJoe_Gallery.jpg" class="download-btn" onclick="event.stopPropagation()">
            <i class="fas fa-download"></i> Download Photo
        </a>
    </div>

    <!-- BOTTOM NAVIGATION -->
    <div class="bottom-nav">
        <div class="nav-item active" id="nav-home" onclick="navigateTo('home', 'nav-home')"><i class="fas fa-home"></i><span>Home</span></div>
        <div class="nav-item" id="nav-about" onclick="navigateTo('explore', 'nav-about')"><i class="fas fa-compass"></i><span>Explore</span></div>
        <div class="nav-item" id="nav-food" onclick="navigateTo('food', 'nav-food')"><i class="fas fa-utensils"></i><span>Food</span></div>
        <div class="nav-item" id="nav-profile" onclick="navigateTo('profile', 'nav-profile')"><i class="fas fa-user"></i><span>Profile</span></div>
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

        window.addEventListener('load', () => {
            const preloader = document.getElementById('preloader');
            const welcomeOverlay = document.getElementById('welcomeOverlay');
            
            const playPromise = audio.play();
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    musicIcon.className = "fas fa-pause";
                    musicBtn.classList.add('playing');
                }).catch(error => {
                    console.log("Autoplay blocked. Waiting for user interaction.");
                });
            }

            preloader.style.opacity = '0';
            setTimeout(() => {
                preloader.style.visibility = 'hidden';
                initScrollRevealEngine(); 
                
                const hasEnteredBefore = localStorage.getItem('welcomeScreenDismissed');
                const savedPage = localStorage.getItem('currentPage');
                const savedNav = localStorage.getItem('currentNav');
                
                if (hasEnteredBefore === 'true') {
                    welcomeOverlay.classList.add('hide-welcome');
                    welcomeOverlay.style.display = 'none';
                    
                    document.addEventListener('click', forceAutoplayOnInteraction);
                    document.addEventListener('touchstart', forceAutoplayOnInteraction);
                    document.addEventListener('scroll', forceAutoplayOnInteraction);

                    if (savedPage && savedNav) {
                        navigateTo(savedPage, savedNav);
                    }
                } else {
                    welcomeOverlay.style.display = 'flex';
                }
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
            localStorage.setItem('welcomeScreenDismissed', 'true');
            localStorage.setItem('currentPage', 'home');
            localStorage.setItem('currentNav', 'nav-home');
            
            document.getElementById('welcomeOverlay').classList.add('hide-welcome');
            setTimeout(() => { document.getElementById('welcomeOverlay').style.display = 'none'; }, 500);
            toggleMusic();
        }

        function navigateTo(pageId, navItemId) {
            if (localStorage.getItem('welcomeScreenDismissed') === 'true') {
                localStorage.setItem('currentPage', pageId);
                localStorage.setItem('currentNav', navItemId);
            }

            const activePage = document.querySelector('.app-page.page-active');
            const targetPage = document.getElementById('page-' + pageId);
            
            if (activePage && activePage !== targetPage) {
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
            } else if (!activePage) {
                targetPage.classList.add('page-active');
                targetPage.style.opacity = '1';
                targetPage.style.transform = 'scale(1) translateY(0)';
            }
            
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => item.classList.remove('active'));
            
            const targetedNav = document.getElementById(navItemId);
            if (targetedNav) targetedNav.classList.add('active');
            
            window.scrollTo({ top: 0 });
        }

        function goBack() { navigateTo('home', 'nav-home'); }

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

        function showImage(src) { 
            const box = document.getElementById('lightbox');
            document.getElementById('lightbox-img').src = src;
            const downloadBtn = document.getElementById('lightbox-download');
            downloadBtn.href = src;
            let filename = src.split('/').pop() || 'CherryJoe_Gallery.jpg';
            downloadBtn.download = filename;

            box.style.display = 'flex'; 
            setTimeout(() => box.classList.add('show'), 15);
        }
        
        function hideImage() { 
            const box = document.getElementById('lightbox');
            box.classList.remove('show');
            setTimeout(() => box.style.display = 'none', 300);
        }

        emailjs.init("xUnFGUm3ZIw6UfW_h");
    </script>
</body>
</html>
