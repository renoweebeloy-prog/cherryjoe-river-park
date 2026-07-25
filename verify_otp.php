<?php
session_start();

if (!isset($_SESSION['reset_otp'])) {
    header("Location: forgot_password.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = trim($_POST['otp_code']);

    if ($entered_otp == $_SESSION['reset_otp']) {
        header("Location: reset_password.php");
        exit();
    } else {
        $error = "Invalid OTP code. Please try again.";
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
        .logo-icon { font-size: 45px; color: #059669; margin-bottom: 10px; }
        .input-group { margin-bottom: 18px; text-align: center; }
        .input-group input { width: 100%; padding: 15px; border: 2px solid #cbd5e1; background: #f8fafc; border-radius: 14px; font-size: 24px; font-weight: bold; text-align: center; letter-spacing: 5px;}
        .input-group input:focus { border-color: #10b981; outline: none; }
        .submit-btn { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 16px; width: 100%; border-radius: 50px; font-weight: 700; font-size: 16px; cursor: pointer; }
        .error-msg { background: #fee2e2; color: #ef4444; padding: 12px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; border: 1px solid #fca5a5;}
        .spam-warning { background: #fffbeb; color: #d97706; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: bold; margin-bottom: 20px; border: 1px dashed #fcd34d; }
    </style>
</head>
<body>
    <div class="auth-card">
        <i class="fas fa-shield-alt logo-icon"></i>
        <h2>Verify Your OTP</h2>
        <p style="color: #64748b; margin-bottom: 10px;">We've sent a 6-digit code to your email.</p>
        
        <!-- SPAM WARNING MESSAGE -->
        <div class="spam-warning">
            <i class="fas fa-exclamation-triangle"></i> Can't see it? Please check your  <b>Spam</b>  folder.
        </div>
        
        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <input type="text" name="otp_code" required maxlength="6" placeholder="------" autocomplete="off">
            </div>
            <button type="submit" class="submit-btn">Verify Code</button>
        </form>
    </div>
</body>
</html>
