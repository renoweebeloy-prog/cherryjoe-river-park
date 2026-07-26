<?php
session_start();
require 'db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    try {
        $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Magbuhat og 6-digit random code
            $otp = rand(100000, 999999);
            
            // I-save ang OTP sa session aron ma-check unya sa verify_otp.php
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_otp'] = $otp;

            // ==========================================
            // ILISI KINI SA IMONG BAG-ONG WEB APP URL
            // ==========================================
            $google_app_script_url = 'https://script.google.com/macros/s/AKfycbyPx7ThrruhQHnI26fLNZ6hsFeciOlVyU9Eu-eqo99_z4n1i-Sthft-EZGrS6JKFftTmQ/exec'; 

            // Ipasa ang data gamit ang GET URL
            $url = $google_app_script_url . "?email=" . urlencode($email) . "&otp=" . $otp . "&name=" . urlencode($user['full_name']);

            // Simple GET Request gamit ang cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);

            // Kung ni-SUCCESS ang Google Apps Script
            if (trim($response) == "SUCCESS") {
                header("Location: verify_otp.php");
                exit();
            } else {
                $error = "Google System Error: " . $response;
            }
        } else {
            $error = "Sorry, This Gmail is not registered in the system and cannot receive an OTP code. Please try again.";
        }
    } catch(PDOException $e) {
        $error = "System Error: " . $e->getMessage();
    }
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
        body { background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(5,150,105,0.2)), url('imagesgallery7.jpg') center/cover fixed; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .auth-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(16px); padding: 40px; border-radius: 24px; box-shadow: 0 25px 50px rgba(0,0,0,0.1); width: 100%; max-width: 420px; text-align: center; }
        .logo-icon { font-size: 45px; color: #059669; margin-bottom: 10px; }
        h2 { color: #1e293b; font-size: 26px; font-weight: 800; margin-bottom: 5px; }
        p.subtitle { color: #64748b; font-size: 14px; margin-bottom: 25px; line-height: 1.5; }
        .input-group { position: relative; margin-bottom: 18px; text-align: left; }
        .input-group i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #10b981; font-size: 18px; }
        .input-group input { width: 100%; padding: 15px 15px 15px 45px; background: #f8fafc; border-radius: 14px; font-size: 15px; color: #1e293b; transition: all 0.3s ease; border: 2px solid #cbd5e1; }
        .input-group input:focus { border-color: #10b981; background: #ffffff; outline: none; box-shadow: 0 0 0 4px rgba(16,185,129,0.15); }
        .submit-btn { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 16px; width: 100%; border-radius: 50px; font-weight: 700; font-size: 16px; cursor: pointer; transition: 0.3s ease; margin-top: 5px; box-shadow: 0 10px 20px rgba(16, 185, 129, 0.25); }
        .submit-btn:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(16, 185, 129, 0.4); }
        .error-msg { background: #fee2e2; color: #ef4444; padding: 12px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; border: 1px solid #fca5a5; display: flex; align-items: center; gap: 8px; font-weight: 600; text-align: left;}
        .bottom-link { display: block; margin-top: 25px; color: #475569; font-size: 14px; text-decoration: none; }
        .bottom-link span { color: #059669; font-weight: 700; }
    </style>
</head>
<body>
    <div class="auth-card">
        <i class="fas fa-unlock-alt logo-icon"></i>
        <h2>Forgot Password?</h2>
        <p class="subtitle">Enter your registered email address and we'll send you an OTP.</p>
        
        <?php if($error): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" required placeholder="Enter your email">
            </div>
            <button type="submit" class="submit-btn" id="btn-submit">Verify Account & Send OTP</button>
        </form>
        
        <a href="login.php" class="bottom-link"><i class="fas fa-arrow-left"></i> Back to <span>Log in</span></a>
    </div>

    <script>
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function() {
                var btn = document.getElementById('btn-submit');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending Code...';
                btn.style.pointerEvents = 'none';
            });
        }
    </script>
</body>
</html>
