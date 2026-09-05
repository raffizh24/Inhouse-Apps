<?php
require 'config.php';
require 'library/SimpleXLSX.php'; // Path ke folder library kamu

use Shuchkin\SimpleXLSX;

check_login();

if ($_SESSION['role'] !== 'production') {
    header("Location: index.php?status=error&msg=" . urlencode("Akses Ditolak!"));
    exit;
}

if (isset($_POST['upload'])) {
    $fileTmpPath = $_FILES['excel_file']['tmp_name'];

    if ($xlsx = SimpleXLSX::parse($fileTmpPath)) {
        $rows = $xlsx->rows();
        $header = $rows[0]; // Baris 1: Header Tanggal

        // Bersihkan data bulan tersebut agar tidak menumpuk
        if (isset($header[1])) {
            $firstDate = date('Y-m-d', strtotime($header[1]));
            $month = date('m', strtotime($firstDate));
            $year  = date('Y', strtotime($firstDate));
            $conn->query("DELETE FROM planning WHERE MONTH(tanggal) = '$month' AND YEAR(tanggal) = '$year'");
        }

        $stmt = $conn->prepare("INSERT INTO planning (model, tanggal, qty_plan) VALUES (?, ?, ?)");

        // Loop Baris (Mulai baris ke-2)
        for ($i = 1; $i < count($rows); $i++) {
            $model = trim($rows[$i][0]);
            if (empty($model)) continue;

            // Loop Kolom Tanggal
            for ($col = 1; $col < count($header); $col++) {
                $tanggal = date('Y-m-d', strtotime($header[$col]));
                $qty     = (int)($rows[$i][$col] ?? 0);

                $stmt->bind_param("ssi", $model, $tanggal, $qty);
                $stmt->execute();
            }
        }

        header("Location: index.php?status=success");
        exit;
    } else {
        header("Location: index.php?status=error&msg=" . urlencode(SimpleXLSX::parseError()));
        exit;
    }
}
