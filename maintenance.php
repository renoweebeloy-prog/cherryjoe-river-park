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
            background: linear-gradient(135deg, #d1fae5 0%, #34d399 100%); 
            font-family: "Segoe UI", Tahoma, sans-serif; 
            text-align: center; 
            color: #1e293b; 
        }
        
        .maintenance-box { 
            background: #ffffff; 
            padding: 45px 35px; 
            border-radius: 24px; 
            box-shadow: 0 20px 40px rgba(5, 150, 105, 0.2); 
            max-width: 500px; 
            border: 2px dashed #10b981; 
            margin: 20px; 
            position: relative; 
        }
        
        /* SEKRETO NGA BYPASS NGA LINK */
        .hidden-bypass {
            text-decoration: none;
            display: inline-block;
            cursor: default; /* Gibuhat natong default cursor para walay hand icon, mas tago! */
        }

        .icon { 
            font-size: 65px; 
            color: #059669; 
            margin-bottom: 20px; 
            animation: bounce 2s infinite ease-in-out; 
            text-shadow: 0 10px 15px rgba(5, 150, 105, 0.2);
            transition: 0.3s;
        }
        
        /* Kung i-hover sa Admin ang tools, mo-dako gamay as sign nga ma-click siya */
        .hidden-bypass:hover .icon {
            transform: scale(1.1);
            color: #047857;
        }

        @keyframes bounce { 
            0%, 100% { transform: translateY(0); } 
            50% { transform: translateY(-12px); } 
        }
        
        h1 { 
            font-size: 26px; 
            margin-bottom: 15px; 
            font-weight: 900; 
            color: #047857;
        }
        
        p { 
            color: #475569; 
            font-size: 16px; 
            line-height: 1.7; 
            margin-bottom: 15px; 
            padding: 0 10px;
        }
    </style>
</head>
<body>
    <div class="maintenance-box">
        
        <!-- ANG TOOLS ICON MAO NAY IMONG E-CLICK PARA MAKA-BYPASS 👇 -->
        <a href="login.php?admin=true" class="hidden-bypass">
            <div class="icon"><i class="fas fa-tools"></i></div>
        </a>
        
        <h1>⚠️ System Under Maintenance ⚠️</h1>
        <p>The system is currently undergoing maintenance to serve you better. Please come back later.</p>
        <p style="font-weight: bold; color: #059669;">We appreciate your patience and understanding.</p>
        
    </div>
</body>
</html>
