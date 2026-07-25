<?php
session_start();
require 'db_connect.php';

$error = '';
$success = '';

// KINI NGA PART KAY I-UPDATE NATO SUNOD KUNG NAA NAY 'APP PASSWORD'
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    // Diri ibutang ang code para mo-send og OTP sa email...
    // Sa pagkakaron, magpakita lang usa tag message:
    $error = "System needs Gmail App Password to send the OTP. Please set it up first!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CherryJoe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, sans-serif; }
        
        body { 
            background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(5,150,105,0.2)), url('imagesgallery7.jpg') center/cover fixed; 
            display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px;
        }

        .auth-card { 
            background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
            padding: 40px; border-radius: 24px; box-shadow: 0 25px 50px rgba(0,0,0,0.1); 
            border: 1px solid rgba(255, 255, 255, 0.5); width: 100%; max-width: 420px; text-align: center; 
            animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .logo-icon { font-size: 45px; color: #059669; margin-bottom: 10px; }
        h2 { color: #1e293b; font-size: 26px; font-weight: 800; margin-bottom: 5px; }
        p.subtitle { color: #64748b; font-size: 14px; margin-bottom: 25px; line-height: 1.5; }

        .input-group { position: relative; margin-bottom: 18px; text-align: left; }
        .input-group i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #10b981; font-size: 18px; }
        .input-group input { 
            width: 100%; padding: 15px 15px 15px 45px; border: 2px solid transparent; 
            background: rgba(255, 255, 255, 0.9); border-radius: 14px; font-size: 15px; color: #1e293b;
            transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }
        .input-group input:focus { border-color: #10b981; background: #ffffff; outline: none; box-shadow: 0 0 0 4px rgba(16,185,129,0.15); }

        .submit-btn { 
            background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 16px; 
            width: 100%; border-radius: 50px; font-weight: 700; font-size: 16px; cursor: pointer; 
            transition: 0.3s ease; margin-top: 5px; box-shadow: 0 10px 20px rgba(16, 185, 129, 0.25);
        }
        .submit-btn:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(16, 185, 129, 0.4); }

        .bottom-link { display: block; margin-top: 25px; color: #475569; font-size: 14px; text-decoration: none; transition: 0.3s; }
        .bottom-link span { color: #059669; font-weight: 700; }
        .bottom-link:hover span { text-decoration: underline; }

        .error-msg { background: #fee2e2; color: #ef4444; padding: 12px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; border: 1px solid #fca5a5; display: flex; align-items: center; gap: 8px; justify-content: center; font-weight: 600;}
    </style>
</head>
<body>
    <div class="auth-card">
        <i class="fas fa-unlock-alt logo-icon"></i>
        <h2>Forgot Password?</h2>
        <p class="subtitle">Enter your registered email address and we'll send you an OTP to reset your password.</p>
        
        <?php if($error): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" required placeholder="Enter your email">
            </div>

            <button type="submit" class="submit-btn">Send OTP Code</button>
        </form>
        
        <a href="login.php" class="bottom-link"><i class="fas fa-arrow-left"></i> Back to <span>Log in</span></a>
    </div>
</body>
</html>
