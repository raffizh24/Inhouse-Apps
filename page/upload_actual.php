<?php
// Query Ambil Data Log Transaksi Terbaru (Limit 15 data terakhir)
$query_tr = mysqli_query($conn, "
    SELECT id, part_code, date_tr, shift, qty, status 
    FROM `transaction` 
    ORDER BY id DESC 
    LIMIT 15
");

// Daftar 4 Part Utama
$parts_list = [
    ['code' => 'CCHS-B829JBTA', 'name' => 'Base Pan'],
    ['code' => 'GCAB-A646JBTA', 'name' => 'Top Table'],
    ['code' => 'GCAB-A767JBTA', 'name' => 'Front Panel'],
    ['code' => 'PPLT-B282JBTA', 'name' => 'Side Cover']
];

if (isset($_POST['btn_submit_batch'])) {
    $parts = $_POST['parts'] ?? [];

    mysqli_begin_transaction($conn);
    try {
        $count = 0;
        foreach ($parts as $p) {
            $partCode = $p['part_code'] ?? '';
            $qty      = (int)($p['qty'] ?? 0);
            $status   = $p['status'] ?? 'ASSY';

            // Hanya proses part yang diisi Qty-nya (> 0)
            if ($partCode !== '' && $qty > 0) {
                // Insert ke tabel transaction
                mysqli_query($conn, "
                    INSERT INTO `transaction` (part_code, date_tr, shift, qty, status)
                    VALUES ('$partCode', '$currentDateTr', '$currentShiftTr', '$qty', '$status')
                ");

                // Update persediaan jika status ASSY (mengurangi qty_paint)
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
        echo "<script>alert('Berhasil menyimpan $count transaksi actual!');location.href='index.php?page=upload_actual';</script>";
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('ERROR: {$e->getMessage()}');history.back();</script>";
        exit;
    }
}
?>

<div class="row g-4 mb-4">
    <!-- 1. CARD FORM INPUT PER PART CODE -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-success text-white fw-bold py-3 d-flex justify-content-between align-items-center">
                <span>Input Transaksi Actual Part</span>
                <span class="badge bg-light text-success">Quick Input</span>
            </div>
            <div class="card-body">
                <form action="" method="POST">

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center mb-3" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-start">Part Code / Nama Part</th>
                                    <th style="width: 110px;">Qty</th>
                                    <th style="width: 120px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($parts_list as $index => $part): ?>
                                    <tr>
                                        <td class="text-start">
                                            <div class="fw-bold text-dark"><?= $part['code'] ?></div>
                                            <small class="text-muted"><?= $part['name'] ?></small>
                                            <input type="hidden" name="parts[<?= $index ?>][part_code]" value="<?= $part['code'] ?>">
                                        </td>
                                        <td>
                                            <input type="number" name="parts[<?= $index ?>][qty]" class="form-control form-control-sm text-center" min="0" placeholder="0">
                                        </td>
                                        <td>
                                            <select name="parts[<?= $index ?>][status]" class="form-select form-select-sm">
                                                <option value="ASSY" selected>ASSY</option>
                                                <option value="PAINT">PAINT</option>
                                                <option value="PRESS">PRESS</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="btn_submit_batch" class="btn btn-success fw-bold py-2">
                            Simpan Transaksi Actual
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- 2. CARD TABEL LOG TRANSAKSI HISTORY -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-dark text-white fw-bold py-3 d-flex justify-content-between align-items-center">
                <span>History Transaksi Terbaru</span>
                <span class="badge bg-secondary">Tabel Transaction</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-hover table-striped mb-0 align-middle text-center" style="font-size: 0.85rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 50px;">id</th>
                                <th>part_code</th>
                                <th>date_tr</th>
                                <th>shift</th>
                                <th>qty</th>
                                <th>status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query_tr && mysqli_num_rows($query_tr) > 0): ?>
                                <?php while ($tr = mysqli_fetch_assoc($query_tr)): ?>
                                    <tr>
                                        <td class="text-muted"><?= $tr['id'] ?></td>
                                        <td class="fw-bold text-start"><?= htmlspecialchars($tr['part_code']) ?></td>
                                        <td><?= $tr['date_tr'] ?></td>
                                        <td><span class="badge bg-secondary"><?= $tr['shift'] ?></span></td>
                                        <td class="fw-semibold text-primary"><?= number_format($tr['qty'], 0, ',', '.') ?></td>
                                        <td>
                                            <?php
                                            $badge_status = 'bg-secondary';
                                            if ($tr['status'] == 'PRESS') $badge_status = 'bg-warning text-dark';
                                            if ($tr['status'] == 'PAINT') $badge_status = 'bg-info text-dark';
                                            if ($tr['status'] == 'ASSY') $badge_status = 'bg-success';
                                            ?>
                                            <span class="badge <?= $badge_status ?>"><?= $tr['status'] ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-muted py-4">Belum ada transaksi recorded.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>