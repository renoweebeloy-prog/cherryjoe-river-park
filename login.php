<?php
session_start();
require 'db_connect.php';

// I-redirect diretso sa index.php kung naka-login na
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // Kuhaon ang user data gamit ang PDO
        $stmt = $conn->prepare("SELECT id, full_name, password, email FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // I-verify ang password
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            
            header("Location: index.php");
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
    <title>Login - CherryJoe</title>
    <style>
        body { font-family: sans-serif; background: #f8fafc; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .auth-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); width: 100%; max-width: 400px; text-align: center; }
        h2 { color: #059669; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #cbd5e1; border-radius: 10px; box-sizing: border-box;}
        button { background: #059669; color: white; border: none; padding: 15px; width: 100%; border-radius: 50px; font-weight: bold; cursor: pointer; margin-top: 10px;}
        .error { background: #fee2e2; color: #ef4444; padding: 10px; border-radius: 5px; margin-bottom: 10px;}
    </style>
</head>
<body>
    <div class="auth-card">
        <h2>Welcome Back</h2>
        <?php if($error) echo "<div class='error'>$error</div>"; ?>
        
        <form method="POST">
            <input type="email" name="email" required placeholder="Email Address">
            <input type="password" name="password" required placeholder="Password">
            <button type="submit">Log In</button>
        </form>
        <br><a href="signup.php" style="color:#059669; text-decoration:none;">Don't have an account? Sign up</a>
    </div>
</body>
</html>
