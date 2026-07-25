<?php
session_start();
require 'db_connect.php';

// Kung walay email sa session (wala ni-agi sa OTP), i-kick out pabalik sa login
if (!isset($_SESSION['reset_email'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // I-check kung nag-match ba ang gi-type nga password
    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match! Please try again.";
    } else {
        try {
            // I-hash (i-encrypt) ang bag-ong password para secure
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // I-update didto sa database base sa email sa user
            $stmt = $conn->prepare("UPDATE users SET password = :password WHERE email = :email");
            $stmt->execute([
                'password' => $hashed_password,
                'email' => $_SESSION['reset_email']
            ]);

            // Limpyohan ang session para dili na nila mabalikan kini nga page
            unset($_SESSION['reset_otp']);
            unset($_SESSION['reset_email']);

            $success = "Password successfully reset! You can now log in.";
        } catch(PDOException $e) {
            $error = "System Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Password - CherryJoe</title>
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
        .input-group input { width: 100%; padding: 15px 15px 15px 45px; border: 2px solid #cbd5e1; background: #f8fafc; border-radius: 14px; font-size: 15px; color: #1e293b; transition: all 0.3s ease; }
        .input-group input:focus { border-color: #10b981; background: #ffffff; outline: none; box-shadow: 0 0 0 4px rgba(16,185,129,0.15); }
        .submit-btn { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 16px; width: 100%; border-radius: 50px; font-weight: 700; font-size: 16px; cursor: pointer; transition: 0.3s ease; margin-top: 5px; }
        .submit-btn:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(16, 185, 129, 0.4); }
        .error-msg { background: #fee2e2; color: #ef4444; padding: 12px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; border: 1px solid #fca5a5; display: flex; align-items: center; gap: 8px; justify-content: center; font-weight: 600;}
        .success-msg { background: #d1fae5; color: #059669; padding: 12px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; border: 1px solid #a7f3d0; font-weight: 600;}
    </style>
</head>
<body>
    <div class="auth-card">
        <i class="fas fa-key logo-icon"></i>
        <h2>Create New Password</h2>
        <p class="subtitle">Please enter your new password below.</p>
        
        <?php if($error): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <!-- KUNG SUCCESS ANG PAG-ILIS, MAGPAKITA OG LOGIN BUTTON -->
            <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <a href="login.php" style="text-decoration:none;"><button class="submit-btn">Go to Login</button></a>
        <?php else: ?>
            <!-- KUNG WALA PA, IPA-TYPE ANG PASSWORD -->
            <form method="POST">
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="new_password" required placeholder="Enter New Password">
                </div>
                <div class="input-group">
                    <i class="fas fa-check-circle"></i>
                    <input type="password" name="confirm_password" required placeholder="Confirm New Password">
                </div>
                <button type="submit" class="submit-btn">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
