<?php
declare(strict_types=1);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

$https =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();
require_once __DIR__ . '/../security/csrf.php';


// Redirect if user not logged in
if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['id'];

// Fetch user data and validate session token
$get_user_data = mysqli_query($conn, "SELECT * FROM `users` WHERE `user_id`='$user_id' LIMIT 1");

if (mysqli_num_rows($get_user_data) !== 1) {
    // User not found → logout
    session_destroy();
    header("Location: login.php");
    exit;
}

$row = mysqli_fetch_assoc($get_user_data);

// Check if session is valid (single-device login)
if ($row['is_session'] !== 'true' || !isset($_SESSION['token']) || $_SESSION['token'] !== $row['session_token']) {
    // Invalid session → force logout
    session_destroy();
    header("Location: login.php?msg=Session expired");
    exit;
}

// ===================== User Data =====================
$count_profile_details = 0;

$user_id = $row['user_id'];
$username = $row['username'];
$first_name = $row['first_name'];
$middle_name = $row['middle_name'];
$last_name = $row['last_name'];
$email = $row['email'];
$phone = $row['phone'];
$pin = $row['pin'];
$password = $row['password'];
$dob = $row['dob'];
$gender = $row['gender'];
if ($gender === NULL) $count_profile_details++;

$country = $row['country'];
if ($country === NULL) $count_profile_details++;

$city = $row['city'];
if ($city === NULL) $count_profile_details++;

$postal_code = $row['postal_code'];
if ($postal_code === NULL) $count_profile_details++;

$type = $row['type'];
$status = $row['status'];
$last_login_on = $row['last_login_on'];
$last_login_at = $row['last_login_at'];

$referral_code = $row['referral_code'];

// Remaining uncompleted profile fields (optional)
$uncompleted_details = 12 - $count_profile_details;
?>
