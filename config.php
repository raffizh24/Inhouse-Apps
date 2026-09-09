<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "seid_ac_InhouseApps"; // <-- Nama database kamu

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

function check_login()
{
    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
        exit;
    }
}
