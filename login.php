<?php
session_start();
require 'db_connect.php';

// Redirect to index if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            // Password is correct, start session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['name'] = $row['full_name'];
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $row['role']; // 'admin' or 'user'
            
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CherryJoe River Park</title>
    <style>
        /* Exact same styles as signup.php to maintain UI consistency */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .auth-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); width: 100%; max-width: 400px; text-align: center; }
        .auth-card h2 { color: #059669; margin-bottom: 20px; }
        .input-group { margin-bottom: 15px; text-align: left; }
        .input-group label { display: block; font-size: 14px; color: #475569; margin-bottom: 5px; }
        .input-group input { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 15px; outline: none; transition: 0.3s; box-sizing: border-box; }
        .input-group input:focus { border-color: #059669; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }
        .btn { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 15px; width: 100%; border-radius: 50px; font-weight: bold; font-size: 16px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(16,185,129,0.3); }
        .link { color: #059669; text-decoration: none; font-size: 14px; display: inline-block; margin-top: 20px; }
        .message { padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 14px; }
        .error { background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h2>Welcome Back</h2>
        <?php if($error) echo "<div class='message error'>$error</div>"; ?>
        
        <form method="POST" action="">
            <div class="input-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="juan@example.com">
            </div>
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn">Log In</button>
        </form>
        <a href="signup.php" class="link">Don't have an account? Sign up</a>
    </div>
</body>
</html>
