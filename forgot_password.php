<?php
session_start();
require 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    try {
        $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $otp = rand(100000, 999999);
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_email'] = $email;

            // BREVO API KEY
            $api_key = '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'; 
            $api_url = 'https://api.brevo.com/v3/smtp/email';

            $data = [
                'sender' => [
                    'name' => 'CherryJoe River Park',
                    'email' => 'renowee.beloy@dorsu.edu.ph' // SIGUROHA NGA NA-VERIFY NI SA BREVO DASHBOARD NIMO
                ],
                'to' => [
                    [
                        'email' => $email,
                        'name' => $user['full_name']
                    ]
                ],
                'subject' => 'Password Reset OTP - CherryJoe',
                'htmlContent' => "<div style='font-family: Arial, sans-serif; padding: 20px; background: #f4f4f4; border-radius: 10px;'>
                                    <h2 style='color: #059669;'>Password Reset Request</h2>
                                    <p>Hello <b>{$user['full_name']}</b>,</p>
                                    <p>Your One-Time Password (OTP) is:</p>
                                    <h1 style='color: #ef4444; letter-spacing: 5px; text-align: center; background: #fff; padding: 15px; border-radius: 8px; border: 1px dashed #ef4444;'>{$otp}</h1>
                                    <p>Please enter this code on the website.</p>
                                  </div>"
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Gi-disable aron iwas server blocking
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'accept: application/json',
                'api-key: ' . $api_key,
                'content-type: application/json'
            ]);

            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_err = curl_error($ch);
            curl_close($ch);
            
            // ERROR TRACKER: Dinhi nato mahibal-an kung naay problema
            if ($curl_err) {
                $error = "System Error: Dili maka-connect sa API. " . $curl_err;
            } elseif ($httpcode == 201 || $httpcode == 200) {
                // KUNG SUCCESS, mo-adto dayon sa verify OTP
                header("Location: verify_otp.php");
                exit();
            } else {
                // KUNG BLOCKED SA BREVO, i-display ang error
                $error = "Wala na-send ang email. API Error: " . $response;
            }
            
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
        .error-msg { background: #fee2e2; color: #ef4444; padding: 12px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; border: 1px solid #fca5a5; display: flex; flex-direction: column; gap: 8px; font-weight: 600; text-align: left;}
        .bottom-link { display: block; margin-top: 25px; color: #475569; font-size: 14px; text-decoration: none; }
        .bottom-link span { color: #059669; font-weight: 700; }
    </style>
</head>
<body>
    <div class="auth-card">
        <i class="fas fa-unlock-alt logo-icon"></i>
        <h2>Forgot Password?</h2>
        <p class="subtitle">Enter your registered email address and we'll send you an OTP to reset your password.</p>
        
        <?php if($error): ?>
            <!-- Ang mo-gawas kung naay error sa API -->
            <div class="error-msg">
                <div><i class="fas fa-exclamation-circle"></i> Error!</div>
                <div style="font-size: 12px; font-weight: normal; word-break: break-all;"><?php echo $error; ?></div>
            </div>
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
