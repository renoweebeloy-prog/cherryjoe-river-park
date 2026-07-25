<?php
session_start();
require 'db_connect.php';

// WALA NAY PHPMAILER DIRE, LIMPYO UG SIMPLE NA!

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    try {
        // I-check kung naa ba sa database ang gi-type nga email
        $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Paghimo og 6-digit OTP code
            $otp = rand(100000, 999999);
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_email'] = $email;

            // NATIVE PHP MAIL FUNCTION (Wala nay require files)
            $to = $email;
            $subject = "Password Reset OTP - CherryJoe";
            $message = "Hello " . $user['full_name'] . ",\n\nYour One-Time Password (OTP) is: " . $otp . "\n\nPlease enter this code on the website to reset your password.";
            $headers = "From: CherryJoe System <no-reply@cherryjoe.com>\r\n";
            $headers .= "Reply-To: renowee.beloy@dorsu.edu.ph\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            // I-send ang email (Pahinumdom: Basin ma-block ni sa Render server)
            mail($to, $subject, $message, $headers);
            
            // Mo-lahos dayon sa verify_otp.php aron mangayo sa code
            header("Location: verify_otp.php");
            exit();
        } else {
            $error = "Sorry, we can't find that email in our system.";
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
        .auth-card { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); padding: 40px; border-radius: 24px; box-shadow: 0 25px 50px rgba(0,0,0,0.1); border: 1px solid rgba(255, 255, 255, 0.5); width: 100%; max-width: 420px; text-align: center; }
        .logo-icon { font-size: 45px; color: #059669; margin-bottom: 10px; }
        h2 { color: #1e293b; font-size: 26px; font-weight: 800; margin-bottom: 5px; }
        p.subtitle { color: #64748b; font-size: 14px; margin-bottom: 25px; line-height: 1.5; }
        .input-group { position: relative; margin-bottom: 18px; text-align: left; }
        .input-group i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #10b981; font-size: 18px; }
        .input-group input { width: 100%; padding: 15px 15px 15px 45px; border: 2px solid transparent; background: rgba(255, 255, 255, 0.9); border-radius: 14px; font-size: 15px; color: #1e293b; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .input-group input:focus { border-color: #10b981; background: #ffffff; outline: none; box-shadow: 0 0 0 4px rgba(16,185,129,0.15); }
        .submit-btn { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 16px; width: 100%; border-radius: 50px; font-weight: 700; font-size: 16px; cursor: pointer; transition: 0.3s ease; margin-top: 5px; box-shadow: 0 10px 20px rgba(16, 185, 129, 0.25); }
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
