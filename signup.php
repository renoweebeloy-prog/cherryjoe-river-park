<?php
session_start();
require 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // I-check kung naa nay naggamit sa email
        $check = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $check->execute(['email' => $email]);
        
        if ($check->rowCount() > 0) {
            $error = "Email is already registered!";
        } else {
            // Insert sa database
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (:name, :email, :password)");
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => $hashed_password
            ]);
            $success = "Registration successful! You can now log in.";
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
    <title>Sign Up - CherryJoe River Park</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, sans-serif; }
        
        body { 
            background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(5,150,105,0.2)), url('imagesgallery7.jpg') center/cover fixed; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            padding: 20px;
        }

        .auth-card { 
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(16px); 
            -webkit-backdrop-filter: blur(16px);
            padding: 40px; 
            border-radius: 24px; 
            box-shadow: 0 25px 50px rgba(0,0,0,0.1); 
            border: 1px solid rgba(255, 255, 255, 0.5);
            width: 100%; 
            max-width: 420px; 
            text-align: center; 
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
        .input-group i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #10b981; font-size: 18px; }
        .input-group input { 
            width: 100%; 
            padding: 15px 15px 15px 45px; 
            border: 2px solid transparent; 
            background: rgba(255, 255, 255, 0.9); 
            border-radius: 14px; 
            font-size: 15px; 
            color: #1e293b;
            transition: all 0.3s ease; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }
        .input-group input:focus { 
            border-color: #10b981; 
            background: #ffffff; 
            outline: none; 
            box-shadow: 0 0 0 4px rgba(16,185,129,0.15); 
        }

        .submit-btn { 
            background: linear-gradient(135deg, #10b981, #059669); 
            color: white; 
            border: none; 
            padding: 16px; 
            width: 100%; 
            border-radius: 50px; 
            font-weight: 700; 
            font-size: 16px; 
            cursor: pointer; 
            transition: 0.3s ease; 
            margin-top: 10px; 
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.25);
        }
        .submit-btn:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 15px 25px rgba(16, 185, 129, 0.4); 
        }

        .bottom-link { display: block; margin-top: 25px; color: #475569; font-size: 14px; text-decoration: none; transition: 0.3s; }
        .bottom-link span { color: #059669; font-weight: 700; }
        .bottom-link:hover span { text-decoration: underline; }

        .error-msg { background: #fee2e2; color: #ef4444; padding: 12px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; border: 1px solid #fca5a5; display: flex; align-items: center; gap: 8px; justify-content: center; font-weight: 600;}
        .success-msg { background: #d1fae5; color: #059669; padding: 12px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; border: 1px solid #a7f3d0; display: flex; align-items: center; gap: 8px; justify-content: center; font-weight: 600;}

        /* --- GOOGLE/GMAIL BUTTON STYLES --- */
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 25px 0 20px 0;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 600;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #cbd5e1;
        }
        .divider::before { margin-right: 15px; }
        .divider::after { margin-left: 15px; }

        .google-btn {
            background: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 15px;
            width: 100%;
            border-radius: 50px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }
        .google-btn:hover {
            background: #f8fafc;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.05);
            border-color: #94a3b8;
        }
        .google-btn i {
            color: #ea4335; /* Google Red */
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <i class="fas fa-user-plus logo-icon"></i>
        <h2>Create Account</h2>
        <p class="subtitle">Join CherryJoe River Park today</p>
        
        <?php if($error): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="full_name" required placeholder="Full Name">
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" required placeholder="Email Address">
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" required placeholder="Password">
            </div>
            <button type="submit" class="submit-btn">Sign Up</button>
        </form>

        <!-- CONTINUE WITH GMAIL SECTION -->
        <div class="divider">OR</div>
        <a href="#" class="google-btn" onclick="alert('Google API integration required to activate this feature.');">
            <i class="fab fa-google"></i> Continue with Gmail
        </a>
        
        <a href="login.php" class="bottom-link">Already have an account? <span>Log in</span></a>
    </div>
</body>
</html>
