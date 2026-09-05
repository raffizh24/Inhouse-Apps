<?php
require 'config.php';
check_login();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Planning System</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Planning System</a>
            <div class="d-flex align-items-center text-white">
                <span class="me-3">User: <strong><?= $_SESSION['username'] ?></strong> (<span class="badge bg-warning text-dark"><?= strtoupper($_SESSION['role']) ?></span>)</span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <?php include 'page/upload_form.php'; ?>
        <?php include 'page/dashboard.php'; ?>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>