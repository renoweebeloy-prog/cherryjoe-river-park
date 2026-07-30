<?php
session_start();
require 'db_connect.php';

// I-check kung naka-login ba gyud ang user
if (!isset($_SESSION['user_id'])) {
    die("Error: Not logged in");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ==========================================
    // 1. KUNG "CANCEL RESERVATION" ANG GI-REQUEST
    // ==========================================
    if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
        $booking_id = $_POST['booking_id'];
        
        try {
            $updateStmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = :id AND user_id = :uid");
            if ($updateStmt->execute(['id' => $booking_id, 'uid' => $_SESSION['user_id']])) {
                echo "SUCCESS_CANCEL";
            } else {
                echo "FAILED";
            }
        } catch (PDOException $e) {
            echo "Database Error: " . $e->getMessage();
        }
        
        // IMPORTANTE: I-exit aron dili na siya mo-padayon sa ubos nga code
        exit(); 
    }

    // ==========================================
    // 2. KUNG BAG-ONG BOOKING FORM ANG GI-SUBMIT
    // ==========================================
    
    // Safety check aron dili mo-error kung walay na-submit nga cottage
    if (!isset($_POST['cottage'])) {
        die("Invalid Request Data");
    }

    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['name'];
    $user_email = $_SESSION['email'];
    
    $cottage = $_POST['cottage'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $gcash_ref = $_POST['gcash_ref'] ?? 'N/A';

    try {
        // I-save ang booking sa database
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

        // ==========================================
        // ⚠️ ILISI KINI SA IMONG GOOGLE APPS SCRIPT URL! ⚠️
        // ==========================================
        $google_app_script_url = 'https://script.google.com/macros/s/AKfycbyIWjfaWligW25F2MKfRuJHusoy43bcCIoYLhnjyAxr48vCGfze-bBBNJh-8HnaPpiTYA/exec'; 

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
}
?>
