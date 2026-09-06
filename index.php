<?php
require 'config.php';
check_login();

// Routing halaman
$page = htmlspecialchars($_GET['page'] ?? 'dashboard', ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inhouse Apps</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>

<body class="bg-light">
    <!-- NAVBAR NAVIGASI -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 sticky-top">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $page === 'dashboard' ? 'active fw-bold' : '' ?>" href="index.php?page=dashboard">
                            Dashboard & Plan
                        </a>
                    </li>

                    <!-- MENU KHUSUS PRODUCTION -->
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'production'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $page === 'upload_actual' ? 'active fw-bold' : '' ?>" href="index.php?page=upload_actual">
                                Pengambilan Part
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- MENU KHUSUS WAREHOUSE (ATAU PRODUCTION BISA LIHAT JUGA) -->
                    <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['warehouse', 'production'])): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $page === 'warehouse_view' ? 'active fw-bold' : '' ?>" href="index.php?page=warehouse_view">
                                Monitoring Warehouse
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <div class="d-flex align-items-center text-white">
                    <span class="me-3">
                        User: <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Guest') ?></strong>
                        (<span class="badge bg-warning text-dark"><?= strtoupper(htmlspecialchars($_SESSION['role'] ?? '-')) ?></span>)
                    </span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- KONTEN UTAMA -->
    <div class="container-fluid px-4">
        <?php
        switch ($page) {
            case 'upload_actual':
                if (isset($_SESSION['role']) && $_SESSION['role'] === 'production') {
                    include 'page/upload_actual.php';
                } else {
                    echo "<div class='alert alert-danger'>Akses ditolak. Halaman khusus Production.</div>";
                }
                break;

            case 'warehouse_view':
                if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['warehouse', 'production'])) {
                    include 'page/warehouse_view.php';
                } else {
                    echo "<div class='alert alert-danger'>Akses ditolak. Halaman khusus Warehouse.</div>";
                }
                break;

            case 'dashboard':
            default:
                // 1. TAMPILKAN UPLOAD PLAN (HANYA UNTUK ROLE PRODUCTION)
                if (isset($_SESSION['role']) && $_SESSION['role'] === 'production') {
                    echo "<div id='upload-plan-section' class='mb-4'>";
                    include 'page/upload_plan.php';
                    echo "</div>";
                    echo "<hr class='my-4'>";
                }

                // 2. TAMPILKAN DASHBOARD DI BAGIAN BAWAH
                echo "<div id='dashboard-section'>";
                include 'page/dashboard.php';
                echo "</div>";
                break;
        }
        ?>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>