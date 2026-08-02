?>
<?php
session_start();
require 'db_connect.php';

// IMONG GOOGLE API KEYS
$clientID = '967241447623-9c1mdcfm42j06etfrv3f9t1jo1dj9dqg.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'https://cherryjoe-river-park.onrender.com/google_login.php';

// KUNG NIBALIK NA SI GOOGLE UG NAAY GIDALA NGA 'code'
if (isset($_GET['code'])) {
    
    // 1. I-exchange ang code para makuha ang Access Token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $data = [
        'code' => $_GET['code'],
        'client_id' => $clientID,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $tokenData = json_decode($response, true);
    
    if (isset($tokenData['access_token'])) {
        
        // 2. Kuhaon ang pangalan ug email sa user gamit ang token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/oauth2/v2/userinfo");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $tokenData['access_token']));
        $userInfoResponse = curl_exec($ch);
        curl_close($ch);
        
        $userInfo = json_decode($userInfoResponse, true);
        
        $email = $userInfo['email'];
        $name = $userInfo['name'];
        
        try {
            // 3. I-check sa database kung naka-register na ba ni nga email
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // KUNG NAA NA: I-Login diretso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
            } else {
                // KUNG WALA PA: I-Register automatically sa database
                // Maghimo tag random password kay require man sa imong database ang password column
                $randomPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                
                $insert = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (:name, :email, :pass)");
                $insert->execute(['name' => $name, 'email' => $email, 'pass' => $randomPassword]);
                
                $_SESSION['user_id'] = $conn->lastInsertId();
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
            }
            
            // 4. E-Redirect kung Admin ba o User
            if ($_SESSION['email'] === 'admin@cherryjoe.com') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
            
        } catch(PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    }
} else {
    // KUNG WALAY 'code', I-REDIRECT ANG USER PAINGON SA GOOGLE LOGIN PAGE
    $authUrl = "https://accounts.google.com/o/oauth2/auth?response_type=code&client_id=" . $clientID . "&redirect_uri=" . urlencode($redirectUri) . "&scope=email%20profile";
    header("Location: " . $authUrl);
    exit();
}
?>
