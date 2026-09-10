<?php
// Pastikan koneksi PDO dan config ter-load dengan benar
require_once __DIR__ . '/../config.php';
global $pdo;

date_default_timezone_set('Asia/Jakarta');

if (!isset($pdo) || $pdo === null) {
    die("Koneksi database gagal dimuat. Periksa file config.php Anda.");
}

// Validasi Session / Role Access
$allowed_roles = ['PRESS', 'PAINTING', 'HEPI', 'INJECTION'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    // header("Location: login.php");
    // exit();
}

$current_role  = $_SESSION['role'] ?? 'PRESS';

// List Part Preset
$default_parts = [
    ['code' => 'GCAB-A646JBPZ', 'name' => 'Top Table'],
    ['code' => 'GCAB-A767JBPZ', 'name' => 'Front Panel'],
    ['code' => 'LCHS-A800JBPZ', 'name' => 'Base Pan'],
    ['code' => 'PPLT-B282JBPZ', 'name' => 'Side Cover R']
];

// =========================================================================
// 1. HANDLE ACTION BATCH SAVE, EDIT, & DELETE (PRESS)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- A. BATCH INPUT PRESS ---
    if ($_POST['action'] === 'save_batch_press') {
        $parts          = $_POST['parts'] ?? [];
        $selectedDate   = $_POST['production_date'] ?? date('Y-m-d');
        $selectedShift  = (int)($_POST['shift'] ?? 1);
        $now            = date('Y-m-d H:i:s');
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

            $sqlActivity = "INSERT INTO activity_logs (user_id, action, description, target_table, part_code, old_data, new_data, created_at) 
                            VALUES (:user_id, :action, :description, :target_table, :part_code, :old_data, :new_data, :created_at)";
            $stmtActivity = $pdo->prepare($sqlActivity);

            foreach ($parts as $item) {
                $part_code = trim($item['part_code'] ?? '');
                $part_name = trim($item['part_name'] ?? '');
                $qty       = (int)($item['qty'] ?? 0);

                if ($qty <= 0 || empty($part_code)) {
                    continue;
                }

                $stmtStok->execute([
                    ':part_code' => $part_code,
                    ':part_name' => $part_name,
                    ':qty'       => $qty
                ]);

                $stmtLog->execute([
                    ':user_id'    => $_SESSION['user_id'] ?? 1,
                    ':role'       => $current_role,
                    ':part_code'  => $part_code,
                    ':qty'        => $qty,
                    ':shift'      => $selectedShift,
                    ':prod_date'  => $selectedDate,
                    ':created_at' => $now
                ]);

                $stmtActivity->execute([
                    ':user_id'      => $_SESSION['user_id'] ?? 1,
                    ':action'       => 'INSERT',
                    ':description'  => "Input FG Press [Shift {$selectedShift} | Tgl: {$selectedDate}]: {$part_code} ({$part_name}) Qty: {$qty}",
                    ':target_table' => 'stock_transactions',
                    ':part_code'    => $part_code,
                    ':old_data'     => null,
                    ':new_data'     => json_encode(['qty' => $qty, 'shift' => $selectedShift, 'production_date' => $selectedDate]),
                    ':created_at'   => $now
                ]);

                $inserted_count++;
            }

            $pdo->commit();

            if ($inserted_count > 0) {
                $_SESSION['success'] = "Berhasil menyimpan $inserted_count item Part Finish Good (Shift $selectedShift - Tgl $selectedDate)!";
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

    // --- B. EDIT TRANSAKSI PRESS ---
    if ($_POST['action'] === 'edit_transaction') {
        $id_trx    = (int)$_POST['id_transaction'];
        $new_qty   = (int)$_POST['new_qty'];
        $new_date  = $_POST['production_date'] ?? date('Y-m-d');
        $new_shift = (int)($_POST['shift'] ?? 1);
        $now       = date('Y-m-d H:i:s');

        try {
            $pdo->beginTransaction();

            $stmtOld = $pdo->prepare("SELECT part_code, qty, shift, production_date FROM stock_transactions WHERE id = :id");
            $stmtOld->execute([':id' => $id_trx]);
            $oldTrx = $stmtOld->fetch();

            if ($oldTrx) {
                $selisih = $new_qty - $oldTrx['qty'];

                $stmtUpdateStok = $pdo->prepare("UPDATE stok_pp SET qty_press = qty_press + :selisih WHERE part_code = :part_code");
                $stmtUpdateStok->execute([':selisih' => $selisih, ':part_code' => $oldTrx['part_code']]);

                $stmtUpdateLog = $pdo->prepare("UPDATE stock_transactions SET qty = :qty, shift = :shift, production_date = :prod_date WHERE id = :id");
                $stmtUpdateLog->execute([
                    ':qty'       => $new_qty,
                    ':shift'     => $new_shift,
                    ':prod_date' => $new_date,
                    ':id'        => $id_trx
                ]);

                $stmtActivity = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, target_table, part_code, old_data, new_data, created_at) VALUES (:user_id, :action, :description, :target_table, :part_code, :old_data, :new_data, :created_at)");
                $stmtActivity->execute([
                    ':user_id'      => $_SESSION['user_id'] ?? 1,
                    ':action'       => 'UPDATE',
                    ':description'  => "Edit Trx #{$id_trx} ({$oldTrx['part_code']}) -> Qty: {$new_qty}, Shift: {$new_shift}, Tgl: {$new_date}",
                    ':target_table' => 'stock_transactions',
                    ':part_code'    => $oldTrx['part_code'],
                    ':old_data'     => json_encode(['qty' => $oldTrx['qty'], 'shift' => $oldTrx['shift'], 'production_date' => $oldTrx['production_date']]),
                    ':new_data'     => json_encode(['qty' => $new_qty, 'shift' => $new_shift, 'production_date' => $new_date]),
                    ':created_at'   => $now
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

    // --- C. DELETE TRANSAKSI PRESS ---
    if ($_POST['action'] === 'delete_transaction') {
        $id_trx = (int)$_POST['id_transaction'];
        $now    = date('Y-m-d H:i:s');

        try {
            $pdo->beginTransaction();

            $stmtOld = $pdo->prepare("SELECT part_code, qty, shift, production_date FROM stock_transactions WHERE id = :id");
            $stmtOld->execute([':id' => $id_trx]);
            $oldTrx = $stmtOld->fetch();

            if ($oldTrx) {
                $stmtSubStok = $pdo->prepare("UPDATE stok_pp SET qty_press = qty_press - :qty WHERE part_code = :part_code");
                $stmtSubStok->execute([':qty' => $oldTrx['qty'], ':part_code' => $oldTrx['part_code']]);

                $stmtDel = $pdo->prepare("DELETE FROM stock_transactions WHERE id = :id");
                $stmtDel->execute([':id' => $id_trx]);

                $stmtActivity = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, target_table, part_code, old_data, new_data, created_at) VALUES (:user_id, :action, :description, :target_table, :part_code, :old_data, :new_data, :created_at)");
                $stmtActivity->execute([
                    ':user_id'      => $_SESSION['user_id'] ?? 1,
                    ':action'       => 'DELETE',
                    ':description'  => "Hapus Trx #{$id_trx} ({$oldTrx['part_code']}) Qty: {$oldTrx['qty']}",
                    ':target_table' => 'stock_transactions',
                    ':part_code'    => $oldTrx['part_code'],
                    ':old_data'     => json_encode(['qty' => $oldTrx['qty'], 'shift' => $oldTrx['shift'], 'production_date' => $oldTrx['production_date']]),
                    ':new_data'     => null,
                    ':created_at'   => $now
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
// 2. QUERY DATA STOK & TRANSAKSI PER PART CODE
// =========================================================================

// A. Query Live Stok Per Part Code
$partStockMap = [];
$stmtPartStok = $pdo->query("SELECT part_code, qty_press, qty_paint FROM stok_pp");
while ($row = $stmtPartStok->fetch(PDO::FETCH_ASSOC)) {
    $partStockMap[$row['part_code']] = [
        'press' => (int)($row['qty_press'] ?? 0),
        'paint' => (int)($row['qty_paint'] ?? 0)
    ];
}

// B. Query Summary Transaksi Per Part Code
$todayFilter = date('Y-m-d');
$partTrxMap = [];
$sqlSummaryPerPart = "SELECT 
    part_code,
    SUM(CASE WHEN MONTH(production_date) = MONTH(?) AND YEAR(production_date) = YEAR(?) THEN qty ELSE 0 END) AS monthly,
    SUM(CASE WHEN YEARWEEK(production_date, 1) = YEARWEEK(?, 1) THEN qty ELSE 0 END) AS weekly,
    SUM(CASE WHEN production_date = ? THEN qty ELSE 0 END) AS daily,
    SUM(CASE WHEN production_date = ? AND shift = 1 THEN qty ELSE 0 END) AS shift1,
    SUM(CASE WHEN production_date = ? AND shift = 2 THEN qty ELSE 0 END) AS shift2,
    SUM(CASE WHEN production_date = ? AND shift = 3 THEN qty ELSE 0 END) AS shift3
FROM stock_transactions 
WHERE role = ?
GROUP BY part_code";

$stmtTrxPart = $pdo->prepare($sqlSummaryPerPart);
$stmtTrxPart->execute([
    $todayFilter,
    $todayFilter,
    $todayFilter,
    $todayFilter,
    $todayFilter,
    $todayFilter,
    $todayFilter,
    $current_role
]);

while ($row = $stmtTrxPart->fetch(PDO::FETCH_ASSOC)) {
    $partTrxMap[$row['part_code']] = $row;
}

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
    <!-- Laporan Foto Leader -->
    <div class="d-flex justify-content-end align-items-center mb-3 ms-auto">
        <button type="button" class="btn btn-sm btn-success fw-bold px-2 py-1" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#reportPhotoModal">
            Laporan Output Produksi
        </button>
    </div>

    <!-- NOTIFIKASI -->
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

    <!-- MAIN SECTION: FORM BATCH INPUT & TABLE HISTORY -->
    <div class="row g-3 mb-3">
        <!-- FORM BATCH INPUT STANDAR -->
        <div class="col-lg-5 col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                    <span class="fw-bold" style="font-size: 0.9rem;">Output Produksi Press</span>
                    <button type="button" class="btn btn-sm btn-warning px-2 m-0 fw-bold" style="font-size: 0.5rem;" data-bs-toggle="modal" data-bs-target="#backdateModal">
                        Input Susulan
                    </button>
                </div>
                <div class="card-body p-3">
                    <form method="POST">
                        <input type="hidden" name="action" value="save_batch_press">
                        <input type="hidden" name="production_date" value="<?= getProductionDateOnly($now) ?>">
                        <input type="hidden" name="shift" value="<?= $currentShift ?>">

                        <!-- Container Scrollable -->
                        <div class="pe-1" style="max-height: 253px; overflow-y: auto;">
                            <?php foreach ($default_parts as $index => $part):
                                $code = $part['code'];
                            ?>
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
                        </div>

                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-primary btn-sm fw-bold py-2">
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- TABEL HISTORY TRANSAKSI -->
        <div class="col-lg-7 col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white fw-bold py-2 d-flex justify-content-between align-items-center" style="font-size: 0.9rem;">
                    <span>Riwayat Transaksi Press</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 340px; overflow-y: auto;">
                        <table class="table table-hover align-middle text-center mb-0" style="font-size: 0.8rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-start" style="width: 120px;">Waktu Input</th>
                                    <th style="width: 120px;">Tgl Prod</th>
                                    <th>Shift</th>
                                    <th class="text-start">Part Code</th>
                                    <th>Qty</th>
                                    <th>Operator</th>
                                    <th style="width: 130px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($histories) > 0): ?>
                                    <?php foreach ($histories as $tr): ?>
                                        <tr>
                                            <td class="text-start">
                                                <?= date('d/m/Y H:i', strtotime($tr['created_at'])) ?>
                                            </td>
                                            <td class=" text-muted">
                                                <?= date('d/m/Y', strtotime($tr['production_date'])) ?>
                                                <small class="d-block text-black-50" style="font-size:0.65rem;"><?= date('H:i', strtotime($tr['production_date'])) ?></small>
                                            </td>
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

                                        <!-- MODAL EDIT -->
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
                                                                <label class="form-label small fw-bold">Tgl Produksi</label>
                                                                <input type="date" name="production_date" class="form-control form-control-sm" value="<?= $tr['production_date'] ?>" required>
                                                            </div>
                                                            <div class="mb-2">
                                                                <label class="form-label small fw-bold">Shift</label>
                                                                <select name="shift" class="form-select form-select-sm">
                                                                    <option value="1" <?= $tr['shift'] == 1 ? 'selected' : '' ?>>Shift 1</option>
                                                                    <option value="2" <?= $tr['shift'] == 2 ? 'selected' : '' ?>>Shift 2</option>
                                                                    <option value="3" <?= $tr['shift'] == 3 ? 'selected' : '' ?>>Shift 3</option>
                                                                </select>
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

                                        <!-- MODAL DELETE -->
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

    <!-- LIVE STOK & TOTAL TRANSAKSI PER PART CODE -->
    <div class="row g-3 mb-3">
        <!-- CARD 1: LIVE STOK SEMUA PART CODE -->
        <div class="col-md-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white py-2 border-bottom-0 d-flex justify-content-between align-items-center">
                    <span class="fw-bold" style="font-size: 0.9rem;">
                        Live Stok Press
                    </span>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-hover align-middle text-center mb-0" style="font-size: 0.78rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-start">Part Code</th>
                                    <th>Stok Press</th>
                                    <th>Stok Paint</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($default_parts as $p):
                                    $c = $p['code'];
                                    $stok_press = $partStockMap[$c]['press'] ?? 0;
                                    $stok_paint = $partStockMap[$c]['paint'] ?? 0;
                                ?>
                                    <tr>
                                        <td class="text-start fw-bold">
                                            <?= $c ?>
                                            <small class="d-block text-muted fw-normal" style="font-size:0.7rem;"><?= $p['name'] ?></small>
                                        </td>
                                        <td class="fw-bold text-primary"><?= number_format($stok_press) ?></td>
                                        <td class="fw-bold text-info"><?= number_format($stok_paint) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 2: TOTAL TRANSAKSI PER PART CODE -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0 border-start h-100">
                <div class="card-header bg-dark text-white py-2 border-bottom-0 d-flex justify-content-between align-items-center">
                    <span class="fw-bold" style="font-size: 0.9rem;">
                        Total Transaksi Press
                    </span>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-hover align-middle text-center mb-0" style="font-size: 0.78rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-start">Part Code</th>
                                    <th>Shift 1</th>
                                    <th>Shift 2</th>
                                    <th>Shift 3</th>
                                    <th>Daily</th>
                                    <th>Weekly</th>
                                    <th>Monthly</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($default_parts as $p):
                                    $c = $p['code'];
                                    $trx = $partTrxMap[$c] ?? [];
                                ?>
                                    <tr>
                                        <td class="text-start fw-bold">
                                            <?= $c ?>
                                            <small class="d-block text-muted fw-normal" style="font-size:0.7rem;"><?= $p['name'] ?></small>
                                        </td>
                                        <td><?= number_format((int)($trx['shift1'] ?? 0)) ?></td>
                                        <td><?= number_format((int)($trx['shift2'] ?? 0)) ?></td>
                                        <td><?= number_format((int)($trx['shift3'] ?? 0)) ?></td>
                                        <td class="fw-bold text-success"><?= number_format((int)($trx['daily'] ?? 0)) ?></td>
                                        <td class="fw-bold text-warning"><?= number_format((int)($trx['weekly'] ?? 0)) ?></td>
                                        <td class="fw-bold text-primary"><?= number_format((int)($trx['monthly'] ?? 0)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL INPUT SUSULAN (BACKDATE) -->
<div class="modal fade" id="backdateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-warning text-white py-2">
                    <h6 class="modal-title fw-bold">Input Susulan</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_batch_press">

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Tanggal Produksi</label>
                            <input type="date" name="production_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Shift</label>
                            <select name="shift" class="form-select form-select-sm" required>
                                <option value="1">Shift 1</option>
                                <option value="2">Shift 2</option>
                                <option value="3">Shift 3</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-2">

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
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL LAPORAN FOTO LEADER (UKURAN BESAR / MODAL-XL) -->
<div class="modal fade" id="reportPhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- HEADER MODAL -->
            <div class="modal-header bg-primary text-white py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0">
                        LAPORAN OUTPUT PRODUKSI - <?= strtoupper($current_role) ?>
                    </h5>
                    <small class="text-white-50">Tanggal Produksi: <b><?= date('d/m/Y', strtotime($todayFilter)) ?></b> | Update: <b><?= date('H:i') ?> WIB</b></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <!-- BODY LAPORAN (AREA YANG BISA DIFOTO) -->
            <div class="modal-body p-4 bg-white" id="printableReportArea">
                <!-- HEADER INFORMASI PABRIK -->
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <div>
                        <h4 class="fw-bold text-dark mb-0">LAPORAN OUTPUT PRESS</h4>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-secondary">TANGGAL: <?= date('d/m/Y', strtotime($todayFilter)) ?></div>
                        <small class="text-muted d-block">Shift Aktif: <b>Shift <?= $currentShift ?></b></small>
                    </div>
                </div>
                <!-- TABEL UTAMA FOTO LEADER -->
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center mb-0" style="font-size: 1.05rem;">
                        <thead class="table-dark text-uppercase fs-6">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th class="text-start" style="width: 200px;">Part Code</th>
                                <th class="text-start">Part Name</th>
                                <th style="width: 120px;" class="bg-primary text-white">Shift 1</th>
                                <th style="width: 120px;" class="bg-primary text-white">Shift 2</th>
                                <th style="width: 120px;" class="bg-primary text-white">Shift 3</th>
                                <th style="width: 150px;" class="bg-success text-white">Total Daily</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $grand_s1 = 0;
                            $grand_s2 = 0;
                            $grand_s3 = 0;
                            $grand_daily = 0;
                            $hasData = false;

                            foreach ($default_parts as $p):
                                $c     = $p['code'];
                                $trx   = $partTrxMap[$c] ?? [];
                                $s1    = (int)($trx['shift1'] ?? 0);
                                $s2    = (int)($trx['shift2'] ?? 0);
                                $s3    = (int)($trx['shift3'] ?? 0);
                                $daily = (int)($trx['daily'] ?? 0);

                                // FILTER: Skip part jika tidak ada transaksi (Total Daily = 0)
                                if ($daily === 0) {
                                    continue;
                                }

                                $hasData      = true;
                                $grand_s1    += $s1;
                                $grand_s2    += $s2;
                                $grand_s3    += $s3;
                                $grand_daily += $daily;
                            ?>
                                <tr>
                                    <td class="fw-bold bg-light"><?= $no++ ?></td>
                                    <td class="text-start fw-bold text-dark"><?= $c ?></td>
                                    <td class="text-start"><?= $p['name'] ?></td>
                                    <td class="fw-bold fs-5 text-dark"><?= number_format($s1) ?></td>
                                    <td class="fw-bold fs-5 text-dark"><?= number_format($s2) ?></td>
                                    <td class="fw-bold fs-5 text-dark"><?= number_format($s3) ?></td>
                                    <td class="fw-bold fs-4 text-success bg-light"><?= number_format($daily) ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$hasData): ?>
                                <tr>
                                    <td colspan="7" class="text-muted py-4">Belum ada transaksi produksi Press untuk hari ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <!-- FOOTER TOTAL SELURUH PART -->
                        <?php if ($hasData): ?>
                            <tfoot class="table-secondary fw-bold fs-5">
                                <tr>
                                    <td colspan="3" class="text-end py-2">TOTAL OUTPUT:</td>
                                    <td class="text-primary"><?= number_format($grand_s1) ?></td>
                                    <td class="text-primary"><?= number_format($grand_s2) ?></td>
                                    <td class="text-primary"><?= number_format($grand_s3) ?></td>
                                    <td class="text-success fs-4 bg-warning bg-opacity-25"><?= number_format($grand_daily) ?></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            <!-- FOOTER MODAL (AKSI) -->
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>

        </div>
    </div>
</div>