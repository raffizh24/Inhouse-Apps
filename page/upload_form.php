<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'production'): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0">Upload File Planning Excel (.xlsm / .xlsx)</h6>
        </div>
        <div class="card-body">
            <!-- Notifikasi Pesan Alert -->
            <?php if (isset($_GET['status'])): ?>
                <?php if ($_GET['status'] == 'success'): ?>
                    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                        <strong>Berhasil!</strong> Data planning berhasil di-import.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($_GET['status'] == 'error'): ?>
                    <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                        <strong>Gagal!</strong> <?= htmlspecialchars($_GET['msg'] ?? 'Terjadi kesalahan.') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Form Upload -->
            <form action="process.php" method="POST" enctype="multipart/form-data" class="row g-3 align-items-center">
                <div class="col-auto">
                    <input class="form-control" type="file" name="excel_file" accept=".xlsx, .xlsm, .xls" required>
                </div>
                <div class="col-auto">
                    <button type="submit" name="upload" class="btn btn-success">Import Planning</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>