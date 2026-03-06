<?php
declare(strict_types=1);

date_default_timezone_set("Asia/Kathmandu");


$config = require __DIR__ . '/../../secure/db.php';

$conn = mysqli_connect(
    $config['host'],  
    $config['user'],  
    $config['pass'],  
    $config['name']   
);

if (!$conn) {
    http_response_code(500);
    exit('Database connection failed');
}