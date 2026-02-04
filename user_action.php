<?php
session_start();
include "../db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

/* EKLE */
if (isset($_POST['add'])) {
    $fullname = $_POST['fullname'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = $_POST['role'];

    mysqli_query($conn,
        "INSERT INTO users (fullname,email,password,role)
         VALUES ('$fullname','$email','$password','$role')"
    );

    $tab = $role === 'seller' ? 'sellers' : 'customers';
}

/* SİL */
if (isset($_GET['delete'])) {
    $id  = (int) $_GET['delete'];
    $tab = $_GET['tab'] ?? 'customers';

    mysqli_query($conn,
        "DELETE FROM users WHERE id=$id AND role!='admin'"
    );
}

header("Location: dashboard.php?tab=" . $tab);
exit;
