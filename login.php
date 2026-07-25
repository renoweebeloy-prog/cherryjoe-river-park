<?php
session_start();
require 'db_connect.php';

// AUTO-LOGIN LOGIC
if (!isset($_SESSION['user_id']) && isset($_COOKIE['cherryjoe_user'])) {
    try {
        $stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE id = :id");
        $stmt->execute(['id' => $_COOKIE['cherryjoe_user']]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
        }
    } catch(PDOException $e) {
        // Ignore error
    }
}

// Redirect kung naka-login na
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['email'] === 'admin@cherryjoe.com') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    try {
        $stmt = $conn->prepare("SELECT id, full_name, password, email FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            
            if ($remember) {
                setcookie('cherryjoe_user', $user['id'], time() + (86400 * 30), "/"); 
            }
            
            if ($user['email'] === 'admin@cherryjoe.com') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Invalid email or password.";
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
    <title>Login - CherryJoe River Park</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, sans-serif; }
        
        body { 
            background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(5,150,105,0.2)), url('imagesgallery7.jpg') center/cover fixed; 
            display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px;
        }

        .auth-card { 
            background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px);
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
        p.subtitle { color: #64748b; font-size: 14px; margin-bottom: 25px; }

        .input-group { position: relative; margin-bottom: 18px; text-align: left; }
        .input-group > i.left-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #10b981; font-size: 18px; }
        
        /* STYLE PARA SA MATA / TOGGLE EYE */
        .input-group i.toggle-password { 
            position: absolute; right: 16px; top: 50%; transform: translateY(-50%); 
            color: #64748b; font-size: 18px; cursor: pointer; transition: 0.3s ease;
        }
        .input-group i.toggle-password:hover { color: #10b981; }

        .input-group input { 
            width: 100%; padding: 15px 45px; border: 2px solid transparent; /* padding left ug right para dili matabunan ang icons */
            background: rgba(255, 255, 255, 0.9); border-radius: 14px; font-size: 15px; color: #1e293b;
            transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }
        .input-group input:focus { border-color: #10b981; background: #ffffff; outline: none; box-shadow: 0 0 0 4px rgba(16,185,129,0.15); }

        .remember-flex { display: flex; align-items: center; justify-content: flex-start; gap: 8px; margin-bottom: 20px; }
        .remember-flex input[type="checkbox"] { width: 16px; height: 16px; accent-color: #059669; cursor: pointer; }
        .remember-flex label { font-size: 14px; color: #475569; font-weight: 600; cursor: pointer; user-select: none; }

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

        .divider { display: flex; align-items: center; text-align: center; margin: 25px 0 20px 0; color: #94a3b8; font-size: 13px; font-weight: 600; }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid #cbd5e1; }
        .divider::before { margin-right: 15px; } .divider::after { margin-left: 15px; }

        .google-btn { background: #ffffff; color: #475569; border: 1px solid #cbd5e1; padding: 15px; width: 100%; border-radius: 50px; font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 12px; text-decoration: none; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .google-btn:hover { background: #f8fafc; transform: translateY(-2px); box-shadow: 0 10px 15px rgba(0,0,0,0.05); border-color: #94a3b8; }
        .google-btn i { color: #ea4335; font-size: 18px; }
    </style>
</head>
<body>
    <div class="auth-card">
        <i class="fas fa-user logo-icon"></i>
        <h2>Welcome Back</h2>
        <p class="subtitle">Sign in to continue to CherryJoe</p>
        
        <?php if($error): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <i class="fas fa-envelope left-icon"></i>
                <input type="email" name="email" required placeholder="Email Address">
            </div>
            
            <div class="input-group">
                <i class="fas fa-lock left-icon"></i>
                <input type="password" name="password" id="login_pass" required placeholder="Password">
                <!-- ANG MATA ICON DINHI -->
                <i class="fas fa-eye toggle-password" onclick="togglePass('login_pass', this)"></i>
            </div>
            
            <div style="text-align: right; margin-top: -10px; margin-bottom: 15px;">
                <a href="forgot_password.php" style="color: #059669; font-size: 13px; font-weight: 700; text-decoration: none;">Forgot Password?</a>
            </div>
            
            <div class="remember-flex">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Keep me signed in</label>
            </div>

            <button type="submit" class="submit-btn">Log In</button>
        </form>

        <div class="divider">OR</div>
        <a href="google_login.php" class="google-btn">
            <i class="fab fa-google"></i> Continue with Gmail
        </a>
        
        <a href="signup.php" class="bottom-link">Don't have an account? <span>Sign up</span></a>
    </div>

    <!-- JAVASCRIPT PARA SA MATA -->
    <script>
        function togglePass(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>
