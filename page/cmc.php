<?php
// cmc.php - Central Monitoring Control Dashboard (Light Theme)

require_once __DIR__ . '/../config.php';

if (function_exists('check_login')) {
    check_login();
}

date_default_timezone_set('Asia/Jakarta');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Koneksi database gagal dimuat. Periksa kembali file config.php Anda.");
}

// Area & Filter Tanggal Aktif
$activeArea = $_GET['area'] ?? 'INJECTION';
$historyDateFilter = $_GET['history_date'] ?? '';

// Tentukan tabel master dan filter kondisi area yang lebih presisi
$masterTable = 'stok_injection';
$areaCondition = "UPPER(t.role) = 'INJECTION'";

if ($activeArea === 'PRESS') {
    $masterTable = 'stok_pp';
    $areaCondition = "(UPPER(t.role) LIKE '%PRESS%' OR UPPER(t.part_code) LIKE '%PRESS%')";
} elseif ($activeArea === 'PAINTING') {
    $masterTable = 'stok_pp';
    $areaCondition = "(UPPER(t.role) LIKE '%PAINT%' OR t.source_table = 'stok_pp')";
} elseif ($activeArea === 'INJECTION') {
    $masterTable = 'stok_injection';
    $areaCondition = "(UPPER(t.role) LIKE '%INJ%' OR t.source_table = 'stok_injection' OR UPPER(t.part_code) LIKE 'INJ%')";
} elseif ($activeArea === 'HE') {
    $masterTable = 'stok_he';
    $areaCondition = "((UPPER(t.part_code) LIKE 'DCON%' OR UPPER(t.part_code) LIKE 'PEVA%') AND UPPER(t.part_code) != 'PEVA-A055VDKZ')";
} elseif ($activeArea === 'PIPING') {
    $masterTable = 'stok_piping';
    $areaCondition = "(UPPER(t.role) LIKE '%PIPE%' OR UPPER(t.role) LIKE '%PIPING%' OR t.source_table = 'stok_piping' OR UPPER(t.part_code) LIKE 'CPIP%')";
}

// Daftar tab area lengkap
$allTabs = [
    'PRESS'     => ['title' => 'Press', 'table' => 'stok_pp'],
    'PAINTING'  => ['title' => 'Painting', 'table' => 'stok_pp'],
    'INJECTION' => ['title' => 'Injection', 'table' => 'stok_injection'],
    'HE'        => ['title' => 'HE', 'table' => 'stok_he'],
    'PIPING'    => ['title' => 'Piping', 'table' => 'stok_piping'],
];

try {
    // Fungsi helper untuk mengambil data history berdasarkan tipe (IN / OUT)
    $fetchHistoryData = function ($type) use ($pdo, $masterTable, $areaCondition, $historyDateFilter) {
        $typeCondition = ($type === 'OUT') ? "(t.transaction_type = 'OUT' OR t.qty < 0)" : "(t.transaction_type = 'IN' OR t.qty >= 0)";

        $query = "
            SELECT t.production_date, t.part_code, m.part_name,
                   SUM(CASE WHEN t.shift = 1 THEN ABS(t.qty) ELSE 0 END) as s1,
                   SUM(CASE WHEN t.shift = 2 THEN ABS(t.qty) ELSE 0 END) as s2,
                   SUM(CASE WHEN t.shift = 3 THEN ABS(t.qty) ELSE 0 END) as s3,
                   SUM(ABS(t.qty)) as total
            FROM stock_transactions t
            LEFT JOIN {$masterTable} m ON t.part_code = m.part_code
            WHERE ({$areaCondition}) AND {$typeCondition}
        ";

        $params = [];
        if (!empty($historyDateFilter)) {
            $query .= " AND t.production_date = ?";
            $params[] = $historyDateFilter;
        }

        $query .= " GROUP BY t.production_date, t.part_code, m.part_name ORDER BY t.production_date DESC, t.part_code ASC LIMIT 100";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    };

    // Ambil data untuk Kiri (IN) dan Kanan (OUT) secara paralel
    $rowsIn = $fetchHistoryData('IN');
    $rowsOut = $fetchHistoryData('OUT');
} catch (Exception $e) {
    $rowsIn = [];
    $rowsOut = [];
    $errorMsg = $e->getMessage();
}
?>

<div class="container-fluid py-3 px-3 text-dark" style="font-size: 0.85rem;">

    <!-- Top Header Title & Filter Tanggal Global -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h5 class="fw-bold text-secondary mb-0">Detail Rekapitulasi History Produksi & Pengambilan</h5>

        <!-- Filter Tanggal Global untuk Kedua Tabel -->
        <form method="GET" action="index.php" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="page" value="cmc">
            <input type="hidden" name="area" value="<?= htmlspecialchars($activeArea) ?>">
            <input type="date" name="history_date" class="form-control form-control-sm bg-white border" value="<?= htmlspecialchars($historyDateFilter) ?>">
            <button type="submit" class="btn btn-sm btn-primary px-3 py-1 shadow-sm">Filter</button>
            <?php if (!empty($historyDateFilter)): ?>
                <a href="index.php?page=cmc&area=<?= htmlspecialchars($activeArea) ?>" class="btn btn-sm btn-outline-secondary px-2 py-1">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (isset($errorMsg)): ?>
        <div class="alert alert-danger py-1"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <!-- Navigasi Tab Area -->
    <div class="d-flex flex-wrap gap-2 mb-3 border-bottom pb-2 align-items-center">
        <ul class="nav nav-pills gap-1 mb-0">
            <?php foreach ($allTabs as $keyArea => $info): ?>
                <li class="nav-item">
                    <a style="width: 100px;" class="nav-link btn btn-sm <?= $activeArea === $keyArea ? 'active bg-primary text-white shadow-sm' : 'bg-white text-dark border' ?>"
                        href="index.php?page=cmc&area=<?= $keyArea ?><?= !empty($historyDateFilter) ? '&history_date=' . $historyDateFilter : '' ?>">
                        <?= $info['title'] ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Layout 2 Kolom (Kiri: Output Produksi | Kanan: Pengambilan Part) -->
    <div class="row g-3">

        <!-- KOLOM KIRI: Output Produksi (IN) -->
        <div class="col-xl-6 col-lg-6 col-md-12">
            <div class="card bg-white border shadow-sm h-100">
                <div class="card-header bg-success bg-opacity-10 d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="fw-bold text-success">
                        <i class="bi bi-box-seam me-1"></i> Output Produksi - Area: <span class="text-dark"><?= htmlspecialchars($activeArea) ?></span>
                    </span>
                    <span class="badge bg-success">IN</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 660px; overflow-y: auto;">
                        <table class="table table-light table-striped table-bordered table-sm align-middle mb-0 text-center" style="font-size: 0.78rem;">
                            <thead class="table-secondary text-dark sticky-top">
                                <tr>
                                    <th style="width: 95px;">Tanggal</th>
                                    <th style="width: 120px;">Part Code</th>
                                    <th class="text-start ps-2">Part Name / Keterangan</th>
                                    <th style="width: 50px;">S1</th>
                                    <th style="width: 50px;">S2</th>
                                    <th style="width: 50px;">S3</th>
                                    <th style="width: 75px;" class="bg-success text-white">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rowsIn)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Belum ada data output produksi.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                    $dateCountsIn = [];
                                    foreach ($rowsIn as $row) {
                                        $dateCountsIn[$row['production_date']] = ($dateCountsIn[$row['production_date']] ?? 0) + 1;
                                    }
                                    $renderedDatesIn = [];
                                    foreach ($rowsIn as $row):
                                        $date = $row['production_date'];
                                        $isFirst = !isset($renderedDatesIn[$date]);
                                        if ($isFirst) $renderedDatesIn[$date] = true;
                                    ?>
                                        <tr>
                                            <?php if ($isFirst): ?>
                                                <td class="fw-semibold align-middle bg-white" rowspan="<?= $dateCountsIn[$date] ?>">
                                                    <?= htmlspecialchars($date) ?>
                                                </td>
                                            <?php endif; ?>
                                            <td class="text-primary fw-bold"><?= htmlspecialchars($row['part_code']) ?></td>
                                            <td class="text-start ps-2"><?= htmlspecialchars($row['part_name'] ?? '-') ?></td>
                                            <td><?= number_format($row['s1']) ?></td>
                                            <td><?= number_format($row['s2']) ?></td>
                                            <td><?= number_format($row['s3']) ?></td>
                                            <td class="fw-bold bg-success bg-opacity-10 text-success">
                                                <?= number_format($row['total']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN: Pengambilan Part (OUT) -->
        <div class="col-xl-6 col-lg-6 col-md-12">
            <div class="card bg-white border shadow-sm h-100">
                <div class="card-header bg-danger bg-opacity-10 d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="fw-bold text-danger">
                        <i class="bi bi-arrow-up-right-square me-1"></i> Pengambilan Part - Area: <span class="text-dark"><?= htmlspecialchars($activeArea) ?></span>
                    </span>
                    <span class="badge bg-danger">OUT</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 660px; overflow-y: auto;">
                        <table class="table table-light table-striped table-bordered table-sm align-middle mb-0 text-center" style="font-size: 0.78rem;">
                            <thead class="table-secondary text-dark sticky-top">
                                <tr>
                                    <th style="width: 95px;">Tanggal</th>
                                    <th style="width: 120px;">Part Code</th>
                                    <th class="text-start ps-2">Part Name / Keterangan</th>
                                    <th style="width: 50px;">S1</th>
                                    <th style="width: 50px;">S2</th>
                                    <th style="width: 50px;">S3</th>
                                    <th style="width: 75px;" class="bg-danger text-white">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rowsOut)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Belum ada data pengambilan part.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                    $dateCountsOut = [];
                                    foreach ($rowsOut as $row) {
                                        $dateCountsOut[$row['production_date']] = ($dateCountsOut[$row['production_date']] ?? 0) + 1;
                                    }
                                    $renderedDatesOut = [];
                                    foreach ($rowsOut as $row):
                                        $date = $row['production_date'];
                                        $isFirst = !isset($renderedDatesOut[$date]);
                                        if ($isFirst) $renderedDatesOut[$date] = true;
                                    ?>
                                        <tr>
                                            <?php if ($isFirst): ?>
                                                <td class="fw-semibold align-middle bg-white" rowspan="<?= $dateCountsOut[$date] ?>">
                                                    <?= htmlspecialchars($date) ?>
                                                </td>
                                            <?php endif; ?>
                                            <td class="text-primary fw-bold"><?= htmlspecialchars($row['part_code']) ?></td>
                                            <td class="text-start ps-2"><?= htmlspecialchars($row['part_name'] ?? '-') ?></td>
                                            <td><?= number_format($row['s1']) ?></td>
                                            <td><?= number_format($row['s2']) ?></td>
                                            <td><?= number_format($row['s3']) ?></td>
                                            <td class="fw-bold bg-danger bg-opacity-10 text-danger">
                                                <?= number_format($row['total']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>