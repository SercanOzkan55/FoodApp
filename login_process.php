<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

$email    = mysqli_real_escape_string($conn, $_POST["email"]);
$password = $_POST["password"];

$q = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

if (mysqli_num_rows($q) === 0) {
    header("Location: login.php?error=user");
    exit;
}

$user = mysqli_fetch_assoc($q);

if (!password_verify($password, $user["password"])) {
    header("Location: login.php?error=pass");
    exit;
}

$_SESSION["user_id"]  = $user["id"];
$_SESSION["fullname"] = $user["fullname"];
$_SESSION["role"]     = strtolower($user["role"]);

switch ($_SESSION["role"]) {
    case "admin":
        header("Location: admin/dashboard.php"); break;
    case "seller":
        header("Location: seller/dashboard.php"); break;
    case "customer":
        header("Location: customer/dashboard.php"); break;
    default:
        header("Location: login.php?error=role");
}
exit;
