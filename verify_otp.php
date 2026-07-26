<?php
session_start();

if (!isset($_SESSION['reset_email'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$email = $_SESSION['reset_email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_otp = trim($_POST['otp_code']);
    
    // I-compare ang gi-type sa user sa OTP nga atong gi-save
    if ($user_otp == $_SESSION['reset_otp']) {
        // Kung sakto, lahos na sa new password page!
        header("Location: create_new_password.php");
        exit();
    } else {
        $error = "Invalid OTP code! Please check your email and try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - CherryJoe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, sans-serif; }
        body { background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(5,150,105,0.2)), url('imagesgallery7.jpg') center/cover fixed; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .auth-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(16px); padding: 40px; border-radius: 24px; box-shadow: 0 25px 50px rgba(0,0,0,0.1); width: 100%; max-width: 420px; text-align: center; }
        .logo-icon { font-size: 45px; color: #10b981; margin-bottom: 10px; }
        h2 { color: #1e293b; font-size: 26px; font-weight: 800; margin-bottom: 5px; }
        p.subtitle { color: #64748b; font-size: 14px; margin-bottom: 25px; line-height: 1.5; }
        .input-group { position: relative; margin-bottom: 18px; text-align: left; }
        .input-group i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #10b981; font-size: 18px; }
        .input-group input { width: 100%; padding: 15px 15px 15px 45px; background: #f8fafc; border-radius: 14px; font-size: 18px; letter-spacing: 5px; font-weight: bold; color: #1e293b; transition: all 0.3s ease; border: 2px solid #cbd5e1; text-align: center; }
        .input-group input:focus { border-color: #10b981; background: #ffffff; outline: none; box-shadow: 0 0 0 4px rgba(16,185,129,0.15); }
        .submit-btn { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 16px; width: 100%; border-radius: 50px; font-weight: 700; font-size: 16px; cursor: pointer; transition: 0.3s ease; margin-top: 5px; box-shadow: 0 10px 20px rgba(16, 185, 129, 0.25); }
        .submit-btn:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(16, 185, 129, 0.4); }
        .error-msg { background: #fee2e2; color: #ef4444; padding: 12px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; border: 1px solid #fca5a5; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 600; }
        .email-badge { background: #e2e8f0; padding: 5px 10px; border-radius: 5px; font-weight: bold; color: #334155; }
    </style>
</head>
<body>
    <div class="auth-card">
        <i class="fas fa-shield-alt logo-icon"></i>
        <h2>Verify OTP</h2>
        <p class="subtitle">We've sent a 6-digit code to <br> <span class="email-badge"><?php echo htmlspecialchars($email); ?></span></p>
        
        <?php if($error): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="text" name="otp_code" required placeholder="000000" maxlength="6">
            </div>
            <button type="submit" class="submit-btn" id="btn-verify">Verify Code</button>
        </form>
    </div>

    <script>
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function() {
                var btn = document.getElementById('btn-verify');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
                btn.style.pointerEvents = 'none';
            });
        }
    </script>
</body>
</html>
