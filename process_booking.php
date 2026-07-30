<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Error: Not logged in");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['name'];
    $user_email = $_SESSION['email'];
    
    $cottage = $_POST['cottage'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $gcash_ref = $_POST['gcash_ref'] ?? 'N/A'; // Kuhaon ang GCash Ref Number

    try {
        // 1. I-SAVE SA SUPABASE DATABASE
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, user_name, user_email, cottage_type, check_in, check_out, gcash_ref, status) VALUES (:uid, :uname, :email, :cottage, :cin, :cout, :gref, 'Pending')");
        
        $stmt->execute([
            'uid' => $user_id,
            'uname' => $user_name,
            'email' => $user_email,
            'cottage' => $cottage,
            'cin' => $check_in,
            'cout' => $check_out,
            'gref' => $gcash_ref
        ]);

        // 2. I-SEND ANG EMAIL GAMIT ANG GOOGLE APPS SCRIPT
        $google_app_script_url = 'IBUTANG_DIRI_ANG_WEB_APP_URL_NIMO'; 

        $url = $google_app_script_url . "?action=booking&email=" . urlencode($user_email) . "&name=" . urlencode($user_name) . "&cottage=" . urlencode($cottage) . "&check_in=" . urlencode($check_in) . "&check_out=" . urlencode($check_out) . "&gcash_ref=" . urlencode($gcash_ref);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        echo "SUCCESS";

    } catch(PDOException $e) {
        echo "Database Error: " . $e->getMessage();
    }
} else {
    echo "Invalid Request";
}
?>
