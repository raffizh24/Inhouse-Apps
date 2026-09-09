<?php
// Pastikan koneksi PDO dan config ter-load dengan benar[cite: 1]
require_once __DIR__ . '/../config.php';
global $pdo;

date_default_timezone_set('Asia/Jakarta');

// Pastikan koneksi aman[cite: 1]
if (!isset($pdo) || $pdo === null) {
    die("Koneksi database gagal dimuat. Periksa file config.php Anda.");
}

// Validasi Session / Role Access[cite: 1]
$allowed_roles = ['PRESS', 'PAINTING', 'HEPI', 'INJECTION'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    // header("Location: login.php");
    // exit();
}

// =========================================================================
// FUNGSIONALITAS TANGGAL PRODUKSI & SHIFT[cite: 1]
// =========================================================================
if (!function_exists('getProductionDateOnly')) {
    function getProductionDateOnly($datetime)
    {
        $time = date('H:i', strtotime($datetime));
        $date = date('Y-m-d', strtotime($datetime));

        if ($time < '09:00') {
            return date('Y-m-d', strtotime($date . ' -1 day'));
        }
        return $date;
    }
}

if (!function_exists('getShift')) {
    function getShift($time)
    {
        if ($time >= '09:00' && $time < '18:00') return 1;
        if ($time >= '18:00' || $time < '01:30') return 2;
        return 3;
    }
}

$current_role  = $_SESSION['role'] ?? 'PRESS';
$now           = date('Y-m-d H:i:s');
$currentDate   = getProductionDateOnly($now);
$currentShift  = getShift(date('H:i', strtotime($now)));

// List Part Preset[cite: 1]
$default_parts = [
    ['code' => 'GCAB-A646JBPZ', 'name' => 'Top Table'],
    ['code' => 'GCAB-A767JBPZ', 'name' => 'Front Panel'],
    ['code' => 'LCHS-A800JBPZ', 'name' => 'Base Pan'],
    ['code' => 'PPLT-B282JBPZ', 'name' => 'Side Cover R']
];

// =========================================================================
// 1. HANDLE ACTION BATCH SAVE, EDIT, & DELETE[cite: 1]
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- A. BATCH INPUT (SAVE MULTIPLE PARTS) ---
    if ($_POST['action'] === 'save_batch_press') {
        $parts = $_POST['parts'] ?? [];
        $inserted_count = 0;

        try {
            $pdo->beginTransaction();

            $sqlStok = "INSERT INTO stok_pp (part_code, part_name, qty_press) 
                        VALUES (:part_code, :part_name, :qty)
                        ON DUPLICATE KEY UPDATE 
                            part_name = VALUES(part_name),
                            qty_press = qty_press + VALUES(qty_press)";
            $stmtStok = $pdo->prepare($sqlStok);

            $sqlLog = "INSERT INTO stock_transactions (user_id, role, part_code, source_table, transaction_type, qty, shift, production_date, created_at) 
                       VALUES (:user_id, :role, :part_code, 'stok_pp', 'IN', :qty, :shift, :prod_date, :created_at)";
            $stmtLog = $pdo->prepare($sqlLog);

            $sqlActivity = "INSERT INTO activity_logs (user_id, action, description, created_at) 
                            VALUES (:user_id, :action, :description, :created_at)";
            $stmtActivity = $pdo->prepare($sqlActivity);

            foreach ($parts as $item) {
                $part_code = trim($item['part_code'] ?? '');
                $part_name = trim($item['part_name'] ?? '');
                $qty       = (int)($item['qty'] ?? 0);

                if ($qty <= 0 || empty($part_code)) {
                    continue;
                }

                // 1. Update Live Stok
                $stmtStok->execute([
                    ':part_code' => $part_code,
                    ':part_name' => $part_name,
                    ':qty'       => $qty
                ]);

                // 2. Insert Log Transaksi
                $stmtLog->execute([
                    ':user_id'   => $_SESSION['user_id'] ?? 1,
                    ':role'      => $current_role,
                    ':part_code' => $part_code,
                    ':qty'       => $qty,
                    ':shift'     => $currentShift,
                    ':prod_date' => $currentDate,
                    ':created_at' => $now
                ]);

                // 3. Insert ke Activity Logs
                $stmtActivity->execute([
                    ':user_id'     => $_SESSION['user_id'] ?? 1,
                    ':action'      => 'INSERT_BATCH_PRESS',
                    ':description' => "Input FG Press [Shift {$currentShift} | Tgl: {$currentDate}]: {$part_code} ({$part_name}) Qty: {$qty}",
                    ':created_at'  => $now
                ]);

                $inserted_count++;
            }

            $pdo->commit();

            if ($inserted_count > 0) {
                $_SESSION['success'] = "Berhasil menyimpan $inserted_count item Part Finish Good (Shift $currentShift)!";
            } else {
                $_SESSION['error'] = "Tidak ada item yang disimpan. Masukkan Qty lebih dari 0.";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['error'] = "Gagal menyimpan batch data: " . $e->getMessage();
        }

        header("Location: index.php?page=" . strtolower($current_role));
        exit();
    }

    // --- B. EDIT TRANSAKSI ---
    if ($_POST['action'] === 'edit_transaction') {
        $id_trx  = (int)$_POST['id_transaction'];
        $new_qty = (int)$_POST['new_qty'];

        try {
            $pdo->beginTransaction();

            $stmtOld = $pdo->prepare("SELECT part_code, qty FROM stock_transactions WHERE id = :id");
            $stmtOld->execute([':id' => $id_trx]);
            $oldTrx = $stmtOld->fetch();

            if ($oldTrx) {
                $selisih = $new_qty - $oldTrx['qty'];

                // Update Stok
                $stmtUpdateStok = $pdo->prepare("UPDATE stok_pp SET qty_press = qty_press + :selisih WHERE part_code = :part_code");
                $stmtUpdateStok->execute([':selisih' => $selisih, ':part_code' => $oldTrx['part_code']]);

                // Update Log Transaksi
                $stmtUpdateLog = $pdo->prepare("UPDATE stock_transactions SET qty = :qty WHERE id = :id");
                $stmtUpdateLog->execute([':qty' => $new_qty, ':id' => $id_trx]);

                // Insert Activity Log
                $stmtActivity = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (:user_id, :action, :description, :created_at)");
                $stmtActivity->execute([
                    ':user_id'     => $_SESSION['user_id'] ?? 1,
                    ':action'      => 'EDIT_TRX_PRESS',
                    ':description' => "Edit Qty Trx #{$id_trx} ({$oldTrx['part_code']}) dari {$oldTrx['qty']} menjadi {$new_qty}",
                    ':created_at'  => $now
                ]);

                $pdo->commit();
                $_SESSION['success'] = "Transaksi berhasil diperbarui!";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['error'] = "Gagal memperbarui transaksi: " . $e->getMessage();
        }

        header("Location: index.php?page=" . strtolower($current_role));
        exit();
    }

    // --- C. DELETE TRANSAKSI ---
    if ($_POST['action'] === 'delete_transaction') {
        $id_trx = (int)$_POST['id_transaction'];

        try {
            $pdo->beginTransaction();

            $stmtOld = $pdo->prepare("SELECT part_code, qty FROM stock_transactions WHERE id = :id");
            $stmtOld->execute([':id' => $id_trx]);
            $oldTrx = $stmtOld->fetch();

            if ($oldTrx) {
                // Kurangi Stok Live
                $stmtSubStok = $pdo->prepare("UPDATE stok_pp SET qty_press = qty_press - :qty WHERE part_code = :part_code");
                $stmtSubStok->execute([':qty' => $oldTrx['qty'], ':part_code' => $oldTrx['part_code']]);

                // Hapus Log Transaksi
                $stmtDel = $pdo->prepare("DELETE FROM stock_transactions WHERE id = :id");
                $stmtDel->execute([':id' => $id_trx]);

                // Insert Activity Log
                $stmtActivity = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (:user_id, :action, :description, :created_at)");
                $stmtActivity->execute([
                    ':user_id'     => $_SESSION['user_id'] ?? 1,
                    ':action'      => 'DELETE_TRX_PRESS',
                    ':description' => "Hapus Trx #{$id_trx} ({$oldTrx['part_code']}) Qty: {$oldTrx['qty']}",
                    ':created_at'  => $now
                ]);

                $pdo->commit();
                $_SESSION['success'] = "Transaksi berhasil dihapus dan stok telah disesuaikan!";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['error'] = "Gagal menghapus transaksi: " . $e->getMessage();
        }

        header("Location: index.php?page=" . strtolower($current_role));
        exit();
    }
}

// =========================================================================
// 2. QUERY DATA STOK & SUMMARY TRANSAKSI
// =========================================================================

// A. Live Stok Terkini (Press & Paint)
$stmtTotalStok = $pdo->query("SELECT SUM(qty_press) AS total_press, SUM(qty_paint) AS total_painting FROM stok_pp");
$stokLive = $stmtTotalStok->fetch();
$totalStokPress    = $stokLive['total_press'] ?? 0;
$totalStokPainting = $stokLive['total_painting'] ?? 0;

// B. Rekap Total Transaksi PRESS, PAINT, ASSY (Bulanan, Mingguan, Harian, Shift 1-3)
$sqlSummary = "SELECT 
    -- PRESS
    SUM(CASE WHEN role = 'PRESS' AND MONTH(production_date) = MONTH(ref.d) AND YEAR(production_date) = YEAR(ref.d) THEN qty ELSE 0 END) AS press_monthly,
    SUM(CASE WHEN role = 'PRESS' AND YEARWEEK(production_date, 1) = YEARWEEK(ref.d, 1) THEN qty ELSE 0 END) AS press_weekly,
    SUM(CASE WHEN role = 'PRESS' AND production_date = ref.d THEN qty ELSE 0 END) AS press_daily,
    SUM(CASE WHEN role = 'PRESS' AND production_date = ref.d AND shift = 1 THEN qty ELSE 0 END) AS press_s1,
    SUM(CASE WHEN role = 'PRESS' AND production_date = ref.d AND shift = 2 THEN qty ELSE 0 END) AS press_s2,
    SUM(CASE WHEN role = 'PRESS' AND production_date = ref.d AND shift = 3 THEN qty ELSE 0 END) AS press_s3,

    -- PAINT
    SUM(CASE WHEN role IN ('PAINTING', 'PAINT') AND MONTH(production_date) = MONTH(ref.d) AND YEAR(production_date) = YEAR(ref.d) THEN qty ELSE 0 END) AS paint_monthly,
    SUM(CASE WHEN role IN ('PAINTING', 'PAINT') AND YEARWEEK(production_date, 1) = YEARWEEK(ref.d, 1) THEN qty ELSE 0 END) AS paint_weekly,
    SUM(CASE WHEN role IN ('PAINTING', 'PAINT') AND production_date = ref.d THEN qty ELSE 0 END) AS paint_daily,
    SUM(CASE WHEN role IN ('PAINTING', 'PAINT') AND production_date = ref.d AND shift = 1 THEN qty ELSE 0 END) AS paint_s1,
    SUM(CASE WHEN role IN ('PAINTING', 'PAINT') AND production_date = ref.d AND shift = 2 THEN qty ELSE 0 END) AS paint_s2,
    SUM(CASE WHEN role IN ('PAINTING', 'PAINT') AND production_date = ref.d AND shift = 3 THEN qty ELSE 0 END) AS paint_s3,

    -- ASSY
    SUM(CASE WHEN role = 'ASSY' AND MONTH(production_date) = MONTH(ref.d) AND YEAR(production_date) = YEAR(ref.d) THEN qty ELSE 0 END) AS assy_monthly,
    SUM(CASE WHEN role = 'ASSY' AND YEARWEEK(production_date, 1) = YEARWEEK(ref.d, 1) THEN qty ELSE 0 END) AS assy_weekly,
    SUM(CASE WHEN role = 'ASSY' AND production_date = ref.d THEN qty ELSE 0 END) AS assy_daily,
    SUM(CASE WHEN role = 'ASSY' AND production_date = ref.d AND shift = 1 THEN qty ELSE 0 END) AS assy_s1,
    SUM(CASE WHEN role = 'ASSY' AND production_date = ref.d AND shift = 2 THEN qty ELSE 0 END) AS assy_s2,
    SUM(CASE WHEN role = 'ASSY' AND production_date = ref.d AND shift = 3 THEN qty ELSE 0 END) AS assy_s3

FROM stock_transactions 
CROSS JOIN (SELECT :prod_date AS d) ref";

$stmtSummary = $pdo->prepare($sqlSummary);
$stmtSummary->execute([':prod_date' => $currentDate]);
$summary = $stmtSummary->fetch();

// C. History Transaksi
$queryHistory = "SELECT t.*, u.username 
                 FROM stock_transactions t
                 LEFT JOIN users u ON t.user_id = u.id
                 WHERE t.role = :role AND t.transaction_type = 'IN'
                 ORDER BY t.id DESC LIMIT 20";
$stmtHistory = $pdo->prepare($queryHistory);
$stmtHistory->execute([':role' => $current_role]);
$histories = $stmtHistory->fetchAll();
?>

<div class="container-fluid py-3 px-4">
    <!-- NOTIFIKASI[cite: 1] -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            [SUKSES] <?= $_SESSION['success'];
                        unset($_SESSION['success']); ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            [ERROR] <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- CARD RINGKASAN LIVE STOK & REKAP TRANSAKSI -->
    <div class="row g-3 mb-3">
        <!-- CARD LIVE STOK -->
        <div class="col-lg-3 col-md-12">
            <div class="card shadow-sm border-0 border-start border-primary border-4 h-100">
                <div class="card-body py-2 px-3 d-flex flex-column justify-content-center">
                    <small class="text-muted fw-bold d-block text-uppercase mb-2" style="font-size:0.75rem;">Live Stok Terkini</small>
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <span class="d-block text-muted" style="font-size:0.75rem;">Stok Press</span>
                            <span class="fs-4 fw-bold text-primary"><?= number_format($totalStokPress) ?></span>
                        </div>
                        <div class="col-6">
                            <span class="d-block text-muted" style="font-size:0.75rem;">Stok Paint</span>
                            <span class="fs-4 fw-bold text-info"><?= number_format($totalStokPainting) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL REKAP TRANSAKSI -->
        <div class="col-lg-9 col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center mb-0" style="font-size: 0.78rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Proses</th>
                                    <th>Bulanan</th>
                                    <th>Mingguan</th>
                                    <th>Harian</th>
                                    <th>Shift 1</th>
                                    <th>Shift 2</th>
                                    <th>Shift 3</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- PRESS -->
                                <tr>
                                    <td class="fw-bold text-primary text-start">PRESS</td>
                                    <td><?= number_format($summary['press_monthly'] ?? 0) ?></td>
                                    <td><?= number_format($summary['press_weekly'] ?? 0) ?></td>
                                    <td class="fw-bold"><?= number_format($summary['press_daily'] ?? 0) ?></td>
                                    <td><?= number_format($summary['press_s1'] ?? 0) ?></td>
                                    <td><?= number_format($summary['press_s2'] ?? 0) ?></td>
                                    <td><?= number_format($summary['press_s3'] ?? 0) ?></td>
                                </tr>
                                <!-- PAINT -->
                                <tr>
                                    <td class="fw-bold text-info text-start">PAINTING</td>
                                    <td><?= number_format($summary['paint_monthly'] ?? 0) ?></td>
                                    <td><?= number_format($summary['paint_weekly'] ?? 0) ?></td>
                                    <td class="fw-bold"><?= number_format($summary['paint_daily'] ?? 0) ?></td>
                                    <td><?= number_format($summary['paint_s1'] ?? 0) ?></td>
                                    <td><?= number_format($summary['paint_s2'] ?? 0) ?></td>
                                    <td><?= number_format($summary['paint_s3'] ?? 0) ?></td>
                                </tr>
                                <!-- ASSY -->
                                <tr class="table-success">
                                    <td class="fw-bold text-success text-start">ASSY</td>
                                    <td><?= number_format($summary['assy_monthly'] ?? 0) ?></td>
                                    <td><?= number_format($summary['assy_weekly'] ?? 0) ?></td>
                                    <td class="fw-bold"><?= number_format($summary['assy_daily'] ?? 0) ?></td>
                                    <td><?= number_format($summary['assy_s1'] ?? 0) ?></td>
                                    <td><?= number_format($summary['assy_s2'] ?? 0) ?></td>
                                    <td><?= number_format($summary['assy_s3'] ?? 0) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- FORM BATCH INPUT[cite: 1] -->
        <div class="col-lg-5 col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
                    <span class="fw-bold">Batch Input Finish Good</span>
                    <span class="badge bg-light text-dark fw-bold">Shift <?= $currentShift ?></span>
                </div>
                <div class="card-body p-3">
                    <form method="POST">
                        <input type="hidden" name="action" value="save_batch_press">

                        <?php foreach ($default_parts as $index => $part): ?>
                            <div class="border rounded p-2 mb-2 bg-light">
                                <div class="row g-2 align-items-center">
                                    <div class="col-7">
                                        <input type="hidden" name="parts[<?= $index ?>][part_code]" value="<?= $part['code'] ?>">
                                        <input type="hidden" name="parts[<?= $index ?>][part_name]" value="<?= $part['name'] ?>">
                                        <div class="fw-bold text-dark" style="font-size: 0.85rem;"><?= $part['code'] ?></div>
                                        <small class="text-muted d-block" style="font-size: 0.75rem;"><?= $part['name'] ?></small>
                                    </div>
                                    <div class="col-5">
                                        <input type="number" name="parts[<?= $index ?>][qty]" class="form-control form-control-sm text-center fw-bold" min="0" value="0" placeholder="Qty">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-primary btn-sm fw-bold py-2">
                                Submit Batch (Shift <?= $currentShift ?>)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- TABEL HISTORY TRANSAKSI[cite: 1] -->
        <div class="col-lg-7 col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white fw-bold py-2">
                    History Input & Aksi (<?= $current_role ?>)
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                        <table class="table table-hover align-middle text-center mb-0" style="font-size: 0.8rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Waktu</th>
                                    <th>Shift</th>
                                    <th class="text-start">Part Code</th>
                                    <th>Qty</th>
                                    <th>Operator</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($histories) > 0): ?>
                                    <?php foreach ($histories as $tr): ?>
                                        <tr>
                                            <td class="text-muted"><?= date('d/m H:i', strtotime($tr['created_at'])) ?></td>
                                            <td><span class="badge bg-secondary">S<?= $tr['shift'] ?? '-' ?></span></td>
                                            <td class="text-start fw-bold"><?= htmlspecialchars($tr['part_code']) ?></td>
                                            <td class="text-success fw-bold">+<?= number_format($tr['qty']) ?></td>
                                            <td><?= htmlspecialchars($tr['username'] ?? 'User') ?></td>
                                            <td>
                                                <button class="btn btn-warning btn-sm py-0 px-2 fw-bold" data-bs-toggle="modal" data-bs-target="#editModal<?= $tr['id'] ?>">
                                                    Edit
                                                </button>
                                                <button class="btn btn-danger btn-sm py-0 px-2 fw-bold" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $tr['id'] ?>">
                                                    Hapus
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- MODAL EDIT[cite: 1] -->
                                        <div class="modal fade" id="editModal<?= $tr['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered modal-sm">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <div class="modal-header py-2">
                                                            <h6 class="modal-title fw-bold">Edit Trx #<?= $tr['id'] ?></h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <input type="hidden" name="action" value="edit_transaction">
                                                            <input type="hidden" name="id_transaction" value="<?= $tr['id'] ?>">
                                                            <div class="mb-2">
                                                                <label class="form-label small fw-bold">Part Code</label>
                                                                <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($tr['part_code']) ?>" disabled>
                                                            </div>
                                                            <div class="mb-2">
                                                                <label class="form-label small fw-bold">Qty Baru</label>
                                                                <input type="number" name="new_qty" class="form-control form-control-sm" value="<?= $tr['qty'] ?>" min="1" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer py-1">
                                                            <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- MODAL DELETE[cite: 1] -->
                                        <div class="modal fade" id="deleteModal<?= $tr['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered modal-sm">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <div class="modal-header py-2">
                                                            <h6 class="modal-title fw-bold">Hapus Trx #<?= $tr['id'] ?></h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <input type="hidden" name="action" value="delete_transaction">
                                                            <input type="hidden" name="id_transaction" value="<?= $tr['id'] ?>">
                                                            Yakin ingin menghapus input <b><?= htmlspecialchars($tr['part_code']) ?></b> sejumlah <b><?= $tr['qty'] ?></b>?
                                                        </div>
                                                        <div class="modal-footer py-1">
                                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-muted py-4">Belum ada riwayat transaksi.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>