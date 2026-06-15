<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance - CherryJoe River Park</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        /* --- PREMIUM UI CONFIG & RESET --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: #0f172a; /* Ultra-modern slate dark theme */
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
            padding: 20px;
            position: relative;
        }

        /* Ambient Background Glows */
        body::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(0, 208, 132, 0.15);
            border-radius: 50%;
            top: -50px;
            left: -50px;
            filter: blur(80px);
            z-index: 0;
        }

        body::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(249, 115, 22, 0.1);
            border-radius: 50%;
            bottom: -50px;
            right: -50px;
            filter: blur(80px);
            z-index: 0;
        }

        /* --- MAINTENANCE CARD --- */
        .maintenance-container {
            max-width: 550px;
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 32px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4), inset 0 1px 1px rgba(255,255,255,0.1);
            z-index: 10;
            animation: cardEntrance 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes cardEntrance {
            0% { transform: translateY(30px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        /* Animated Icon Stack */
        .icon-stack {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 25px auto;
        }

        .main-icon {
            font-size: 60px;
            color: #00d084;
            animation: pulseIcon 2s infinite ease-in-out;
        }

        .gear-icon {
            position: absolute;
            font-size: 26px;
            color: #f97316;
            bottom: 15px;
            right: 10px;
            animation: spinGear 3s linear infinite;
        }

        @keyframes pulseIcon {
            0%, 100% { transform: scale(1); opacity: 0.9; }
            50% { transform: scale(1.05); color: #059669; opacity: 1; }
        }

        @keyframes spinGear {
            to { transform: rotate(360deg); }
        }

        /* Typography */
        h1 {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
            background: linear-gradient(135deg, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .back-later-text {
            font-size: 18px;
            font-weight: 600;
            color: #f97316; /* Eye-catching orange color */
            margin-bottom: 20px;
            display: block;
        }

        .brand-tag {
            font-size: 13px;
            font-weight: 700;
            color: #00d084;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 20px;
            display: inline-block;
            background: rgba(0, 208, 132, 0.08);
            padding: 6px 16px;
            border-radius: 50px;
            border: 1px solid rgba(0, 208, 132, 0.15);
        }

        p.notice-text {
            color: #94a3b8;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        /* --- CONTACT & QUICK INFO GRID --- */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
            margin-bottom: 30px;
            text-align: left;
        }

        .info-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.04);
            padding: 16px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            border-color: rgba(0, 208, 132, 0.2);
            background: rgba(0, 208, 132, 0.03);
        }

        .info-card i {
            font-size: 20px;
            color: #f97316;
            background: rgba(249, 115, 22, 0.08);
            padding: 12px;
            border-radius: 12px;
            width: 44px;
            text-align: center;
        }

        .info-card i.fa-phone {
            color: #00d084;
            background: rgba(0, 208, 132, 0.08);
        }

        .info-details h3 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 2px;
        }

        .info-details p, .info-details a {
            font-size: 14px;
            color: #e2e8f0;
            font-weight: 600;
            text-decoration: none;
        }

        .info-details a:hover {
            color: #00d084;
            text-decoration: underline;
        }

        /* --- SOCIAL MEDIA BUTTONS --- */
        .social-container {
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding-top: 25px;
        }

        .social-title {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .fb-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, #1877f2, #166fe5);
            color: white;
            text-decoration: none;
            padding: 14px 24px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 50px;
            box-shadow: 0 10px 20px -5px rgba(24, 119, 242, 0.4);
            transition: all 0.3s ease;
            width: 100%;
        }

        .fb-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px -5px rgba(24, 119, 242, 0.6);
        }

        footer {
            margin-top: 35px;
            color: #475569;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        /* Responsive configuration */
        @media (max-width: 500px) {
            .maintenance-container {
                padding: 30px 20px;
                border-radius: 24px;
            }
            h1 { font-size: 26px; }
            .back-later-text { font-size: 16px; }
            p.notice-text { font-size: 14px; }
        }
    </style>
</head>
<body>

    <div class="maintenance-container">
        <div class="icon-stack">
            <i class="fas fa-compass main-icon"></i>
            <i class="fas fa-cog gear-icon"></i>
        </div>

        <span class="brand-tag">CherryJoe River Park</span>
        <h1>Under Maintenance</h1>
        <span class="back-later-text">Please come back later</span>
        
        <p class="notice-text">We are currently updating and improving our official website to provide you with a much better experience. We will be back online shortly!</p>

        <div class="info-grid">
            <div class="info-card">
                <i class="fas fa-phone"></i>
                <div class="info-details">
                    <h3>Contact Number</h3>
                    <p>0920 408 7956</p>
                </div>
            </div>

            <div class="info-card">
                <i class="fas fa-envelope"></i>
                <div class="info-details">
                    <h3>Email Address</h3>
                    <a href="mailto:cherryday103080@gmail.com">cherryday103080@gmail.com</a>
                </div>
            </div>

            <div class="info-card">
                <i class="fas fa-map-marker-alt"></i>
                <div class="info-details">
                    <h3>Resort Location</h3>
                    <p>Purok Magong-ong Brgy. San Rafael Cateel Davao Oriental</p>
                </div>
            </div>
        </div>

        <div class="social-container">
            <div class="social-title">For Inquiries and Bookings</div>
            <a href="https://www.facebook.com/share/1DkMYrXWiZ/" target="_blank" class="fb-btn">
                <i class="fab fa-facebook-f"></i> Visit Us on Facebook
            </a>
        </div>

        <footer>© 2026 CherryJoe River Park | Developed by Renowee Beloy</footer>
    </div>

</body>
</html>