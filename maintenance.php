<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance - CherryJoe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            /* BAG-ONG LIGHT GREEN TO GREEN BACKGROUND */
            background: linear-gradient(135deg, #d1fae5 0%, #34d399 100%); 
            font-family: "Segoe UI", Tahoma, sans-serif; 
            text-align: center; 
            color: #1e293b; 
        }
        
        .maintenance-box { 
            background: #ffffff; 
            padding: 45px 40px; 
            border-radius: 24px; 
            box-shadow: 0 20px 40px rgba(5, 150, 105, 0.2); 
            max-width: 450px; 
            border: 2px dashed #10b981; 
            margin: 20px; 
            position: relative; 
        }
        
        .icon { 
            font-size: 65px; 
            color: #059669; 
            margin-bottom: 20px; 
            animation: bounce 2s infinite ease-in-out; 
            text-shadow: 0 10px 15px rgba(5, 150, 105, 0.2);
        }
        
        @keyframes bounce { 
            0%, 100% { transform: translateY(0); } 
            50% { transform: translateY(-12px); } 
        }
        
        h1 { 
            font-size: 30px; 
            margin-bottom: 12px; 
            font-weight: 900; 
            color: #047857;
            letter-spacing: -0.5px;
        }
        
        p { 
            color: #475569; 
            font-size: 16px; 
            line-height: 1.7; 
            margin-bottom: 15px; 
            padding: 0 10px;
        }
        
        /* SEKRETO NGA BUTTON PARA SA ADMIN */
        .secret-admin { 
            position: absolute; 
            bottom: 12px; 
            right: 18px; 
            color: #f1f5f9; /* Gitago gihapon ang color aron di halata */
            text-decoration: none; 
            font-size: 18px; 
            transition: 0.3s; 
            padding: 10px; 
        }
        .secret-admin:hover { 
            color: #059669; 
            transform: scale(1.2); 
        }
    </style>
</head>
<body>
    <div class="maintenance-box">
        <div class="icon"><i class="fas fa-tools"></i></div>
        <h1>Under Maintenance</h1>
        
        <p>We are currently upgrading the CherryJoe River Park website to serve you better. Please come back later.</p>
        
        <!-- I-CLICK LANG NING PADLOCK PARA MAKA-SULOD KA SA LOGIN.PHP -->
        <a href="login.php?admin=true" class="secret-admin" title="Admin Bypass"><i class="fas fa-lock"></i></a>
    </div>
</body>
</html>
