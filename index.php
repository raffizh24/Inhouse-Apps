<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// --- LOGIKA TANGGAL PRODUKSI & SHIFT ---
function getProductionDateOnly($datetime)
{
    $time = date('H:i', strtotime($datetime));
    $date = date('Y-m-d', strtotime($datetime));

    if ($time < '09:00') {
        return date('Y-m-d', strtotime($date . ' -1 day'));
    }
    return $date;
}

function getShift($time)
{
    if ($time >= '09:00' && $time < '18:00') return 1;
    if ($time >= '18:00' || $time < '01:30') return 2;
    return 3;
}

$now                    = date('Y-m-d H:i:s');
$currentDate            = getProductionDateOnly($now);
$currentShift           = getShift(date('H:i', strtotime($now)));
$productionDateDisplay  = date('d/m/Y', strtotime($currentDate));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Production</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">INHOUSE APPS</a>

            <div class="ms-auto d-flex align-items-center gap-3">
                <!-- DISPLAY SHIFT & TANGGAL PRODUKSI -->
                <div class="d-flex align-items-center gap-1">
                    <span class="badge bg-primary fs-7 py-2 px-2">Tanggal: <?= $productionDateDisplay ?></span>
                    <span class="badge bg-warning text-dark fs-7 py-2 px-2=">Shift <?= $currentShift ?></span>
                </div>

                <!-- USER & LOGOUT -->
                <div class="d-flex align-items-center gap-2 text-white border-start ps-3">
                    <div class="lh-1 text-end">
                        <span class="d-block text-white-50" style="font-size: 0.7rem;">User logged in:</span>
                        <span class="fw-bold" style="font-size: 0.85rem;"><?= $_SESSION['username'] ?? 'Operator' ?></span>
                    </div>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm py-2 px-2 lh-1 d-flex align-items-center">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- LOAD HALAMAN PAGE (MISAL PRESS.PHP) -->
    <?php
    $page = $_GET['page'] ?? 'press';
    include "page/" . strtolower($page) . ".php";
    ?>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>