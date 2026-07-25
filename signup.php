<?php
session_start();
require 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $error = "Email is already registered!";
    } else {
        // Insert new user (default role is 'user')
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        
        if ($stmt->execute()) {
            $success = "Registration successful! You can now log in.";
        } else {
            $error = "Error: Something went wrong.";
        }
        $stmt->close();
    }
    $check_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - CherryJoe River Park</title>
    <style>
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
        .success { background: #d1fae5; color: #059669; border: 1px solid #6ee7b7; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h2>Create Account</h2>
        <?php if($error) echo "<div class='message error'>$error</div>"; ?>
        <?php if($success) echo "<div class='message success'>$success</div>"; ?>
        
        <form method="POST" action="">
            <div class="input-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required placeholder="Juan Dela Cruz">
            </div>
            <div class="input-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="juan@example.com">
            </div>
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn">Sign Up</button>
        </form>
        <a href="login.php" class="link">Already have an account? Log in</a>
    </div>
</body>
</html>
