<?php
echo password_hash("7878161614", PASSWORD_DEFAULT);
?>
 <?php
$plain = "7878161614"; // password you want to test
$hash = '$2y$10$EDfLgICDH5IRZDm4f5jjyOho9N0ycWVeZzN3h8oEeZwLV3f8YWnJi'; // copy-paste the hash from DB

if (password_verify($plain, $hash)) {
    echo "✅ Match!";
} else {
    echo "❌ No match!";
}
?>
