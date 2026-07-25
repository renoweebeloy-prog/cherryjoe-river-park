<?php
session_start();

// 1. I-delete ang tanang User Sessions
session_unset();
session_destroy();

// 2. I-delete ang "Keep me signed in" Cookie (I-set ang oras pabalik sa past para ma-expire)
if (isset($_COOKIE['cherryjoe_user'])) {
    setcookie('cherryjoe_user', '', time() - 3600, "/"); 
}

// 3. I-kick out pabalik sa Login page
header("Location: login.php");
exit();
?>
