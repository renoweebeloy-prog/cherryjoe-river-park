<?php
session_start();
require 'db_connect.php';

// I-check kung naka-login ba gyud ang user
if (!isset($_SESSION['user_id'])) {
    die("Error: Not logged in");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['name'];
    $user_email = $_SESSION['email'];
    
    // Kuhaon ang data gikan sa JavaScript Fetch
    $cottage = $_POST['cottage'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];

    try {
        // 1. I-SAVE SA SUPABASE DATABASE
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, user_name, user_email, cottage_type, check_in, check_out, status) VALUES (:uid, :uname, :email, :cottage, :cin, :cout, 'Pending')");
        
        $stmt->execute([
            'uid' => $user_id,
            'uname' => $user_name,
            'email' => $user_email,
            'cottage' => $cottage,
            'cin' => $check_in,
            'cout' => $check_out
        ]);

        // ==========================================
        // 2. I-SEND ANG EMAIL GAMIT ANG GOOGLE APPS SCRIPT
        // ==========================================
        $google_app_script_url = 'IBUTANG_DIRI_ANG_WEB_APP_URL_NIMO'; 

        // Nagbutang ta og action=booking aron mailhan sa Google nga booking ni, dili OTP
        $url = $google_app_script_url . "?action=booking&email=" . urlencode($user_email) . "&name=" . urlencode($user_name) . "&cottage=" . urlencode($cottage) . "&check_in=" . urlencode($check_in) . "&check_out=" . urlencode($check_out);

        // Simple GET Request gamit ang cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        // Kung walay error, i-send ang SUCCESS message pabalik sa website
        echo "SUCCESS";

    } catch(PDOException $e) {
        echo "Database Error: " . $e->getMessage();
    }
} else {
    echo "Invalid Request";
}
?>
