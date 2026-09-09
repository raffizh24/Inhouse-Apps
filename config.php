<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tambahkan fungsi check_login() di sini:
function check_login()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

$host    = 'localhost';
$db_name = 'seid_ac_inhouseapps'; // Ganti dengan nama database MySQL Anda
$user    = 'root';
$pass    = '';                   // Password XAMPP (default biasanya kosong)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Variabel ini HARUS bernama $pdo dan berada di scope global
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

// Opsional: Buat fungsi helper untuk memastikan koneksi selalu aman
function getDbConnection()
{
    global $pdo;
    return $pdo;
}
