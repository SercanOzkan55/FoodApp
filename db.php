<?php
$conn = mysqli_connect("localhost", "root", "", "foodapp");

if (!$conn) {
    die("Bağlantı hatası: " . mysqli_connect_error());
}
?>
