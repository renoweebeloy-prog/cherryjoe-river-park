<?php
// Supabase Database Connection Details (Gikan sa imong screenshot)
$host = 'aws-0-ap-southeast-1.pooler.supabase.com'; // I-paste ang tibuok host kung lahi sa screenshot
$port = '6543';
$db_name = 'postgres';
$db_user = 'postgres.gitciqkpxlokouileogg';
$db_pass = '2RxcT1CMPohd56cn'; // Ilisi ni sa imong tinuod nga Supabase password

try {
    // Data Source Name (DSN) para sa PostgreSQL
    // Importante ang 'sslmode=require' kay required ni sa Supabase para sa external/Render connections
    $dsn = "pgsql:host=$host;port=$port;dbname=$db_name;sslmode=require";
    
    // Pagbuhat og PDO instance
    $conn = new PDO($dsn, $db_user, $db_pass);
    
    // I-set ang PDO error mode to exception para sayon pangitaon ang error kung naa man
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // I-set ang default fetch mode para associative array ang i-return permi
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Kung mapalpak ang connection, i-stop ang page ug i-print ang error
    die("Connection failed: " . $e->getMessage());
}
?>
