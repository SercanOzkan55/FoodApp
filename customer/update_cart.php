<?php
session_start();
include "../db.php";

$user=(int)($_SESSION["user_id"] ?? 0);
$menu=(int)($_POST["menu_id"] ?? 0);
$diff=(int)($_POST["diff"] ?? 0);

if(!$user||!$menu||!$diff) exit;

$q=mysqli_query($conn,"
SELECT quantity FROM cart WHERE user_id=$user AND menu_id=$menu
");
if(!mysqli_num_rows($q)) exit;

$row=mysqli_fetch_assoc($q);
$new=$row["quantity"]+$diff;

if($new<=0){
mysqli_query($conn,"
DELETE FROM cart WHERE user_id=$user AND menu_id=$menu
");
}else{
mysqli_query($conn,"
UPDATE cart SET quantity=$new WHERE user_id=$user AND menu_id=$menu
");
}
