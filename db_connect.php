<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'amore_academy');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die('<p style="font-family:sans-serif;color:red;padding:2rem;">Database connection failed: ' . mysqli_connect_error() . '</p>');
}

mysqli_set_charset($conn, 'utf8mb4');
?>