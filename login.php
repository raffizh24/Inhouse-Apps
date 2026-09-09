<?php
session_start();

// Panggil file konfigurasi
require_once 'config.php';
global $pdo; // Menggunakan PDO dari config.php

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        // Query menggunakan PDO
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Cek password (plain text)
            if ($password === $user['password']) {

                // Simpan data session
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role']; // PRESS, PAINTING, HEPI, INJECTION

                $fg_roles = ['PRESS', 'PAINTING', 'HEPI', 'INJECTION'];

                if (in_array($_SESSION['role'], $fg_roles)) {
                    // Routing otomatis: PAINTING diarahkan ke page/paint.php
                    $page = strtolower($_SESSION['role']);
                    if ($page === 'painting') {
                        $page = 'paint';
                    }

                    header("Location: index.php?page=" . $page);
                    exit();
                } else {
                    header("Location: index.php?page=dashboard");
                    exit();
                }
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username tidak ditemukan!";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - System Planning</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>

<body class="bg-light d-flex align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h5 class="mb-0 fw-bold">System Planning Login</h5>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2" style="font-size: 0.85rem;"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold" style="font-size: 0.85rem;">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold" style="font-size: 0.85rem;">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100 fw-bold py-2 mt-2">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>