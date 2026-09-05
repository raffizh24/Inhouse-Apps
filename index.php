<?php
require 'config.php';
check_login();

// 1. HELPER FUNCTION (DATE & SHIFT)
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

// 2. SINGLE SOURCE OF TRUTH FOR TIME
$now            = date('Y-m-d H:i:s');
$currentDate    = getProductionDateOnly($now);
$currentShift   = getShift(date('H:i', strtotime($now)));

$currentDateTr  = $currentDate;
$currentShiftTr = $currentShift;

$productionDateDisplay  = date('d/m/Y', strtotime($currentDate));
$productionShiftDisplay = $currentShift;

// 3. HANDLER PROSES INPUT TRANSAKSI ACTUAL (BATCH)
if (isset($_POST['btn_submit_batch'])) {
    $parts = $_POST['parts'] ?? [];

    mysqli_begin_transaction($conn);
    try {
        $count = 0;
        foreach ($parts as $p) {
            $partCode = $conn->real_escape_string($p['part_code'] ?? '');
            $qty      = (int)($p['qty'] ?? 0);
            $status   = $conn->real_escape_string($p['status'] ?? 'ASSY');

            if ($partCode !== '' && $qty > 0) {
                mysqli_query($conn, "
                    INSERT INTO `transaction` (part_code, date_tr, shift, qty, status)
                    VALUES ('$partCode', '$currentDateTr', '$currentShiftTr', '$qty', '$status')
                ");

                if ($status === 'ASSY') {
                    mysqli_query($conn, "
                        UPDATE part 
                        SET qty_paint = qty_paint - $qty 
                        WHERE part_code = '$partCode'
                    ");
                }
                $count++;
            }
        }

        mysqli_commit($conn);
        echo "<script>alert('Berhasil menyimpan $count transaksi actual!'); location.href='index.php?page=upload_actual';</script>";
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Gagal menyimpan transaksi: " . addslashes($e->getMessage()) . "'); history.back();</script>";
        exit;
    }
}

// 4. ROUTING HALAMAN
$page = $_GET['page'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Inhouse Apps</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>

<body class="bg-light">

    <!-- NAVBAR DENGAN NAVIGASI 3 PAGE -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php?page=dashboard">Inhouse Apps</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $page == 'dashboard' ? 'active fw-bold' : '' ?>" href="index.php?page=dashboard">
                            Dashboard
                        </a>
                    </li>
                    <?php if ($_SESSION['role'] === 'production'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'upload_plan' ? 'active fw-bold' : '' ?>" href="index.php?page=upload_plan">
                                Upload Plan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'upload_actual' ? 'active fw-bold' : '' ?>" href="index.php?page=upload_actual">
                                Upload Actual
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <div class="d-flex align-items-center text-white">
                    <span class="me-3">User: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> (<span class="badge bg-warning text-dark"><?= strtoupper($_SESSION['role']) ?></span>)</span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- MEMANGGIL FILE PAGE BERDASARKAN ROUTING -->
    <div class="container-fluid px-4">
        <?php
        switch ($page) {
            case 'upload_plan':
                if ($_SESSION['role'] === 'production') {
                    include 'page/upload_plan.php';
                } else {
                    echo "<div class='alert alert-danger'>Akses ditolak. Halaman khusus Production.</div>";
                }
                break;

            case 'upload_actual':
                if ($_SESSION['role'] === 'production') {
                    include 'page/upload_actual.php';
                } else {
                    echo "<div class='alert alert-danger'>Akses ditolak. Halaman khusus Production.</div>";
                }
                break;

            case 'dashboard':
            default:
                include 'page/dashboard.php';
                break;
        }
        ?>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>