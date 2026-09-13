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

// Area & Jenis Transaksi Aktif untuk Detail History
$activeArea = $_GET['area'] ?? 'INJECTION';
$historyType = $_GET['h_type'] ?? 'IN'; // IN = Output Produksi, OUT = Pengambilan Part
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
    // Ambil data Detail Rekapitulasi History berdasarkan Area & Jenis Transaksi (IN / OUT)
    $typeCondition = ($historyType === 'OUT') ? "(t.transaction_type = 'OUT' OR t.qty < 0)" : "(t.transaction_type = 'IN' OR t.qty >= 0)";

    $historyQuery = "
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
        $historyQuery .= " AND t.production_date = ?";
        $params[] = $historyDateFilter;
    }

    $historyQuery .= " GROUP BY t.production_date, t.part_code, m.part_name ORDER BY t.production_date DESC, t.part_code ASC LIMIT 100";

    $stmtHistory = $pdo->prepare($historyQuery);
    $stmtHistory->execute($params);
    $historyRows = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $historyRows = [];
    $errorMsg = $e->getMessage();
}
?>

<div class="container-fluid py-3 px-3 text-dark" style="font-size: 0.85rem;">

    <!-- Top Header Title -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-secondary mb-0">Detail Rekapitulasi History Produksi & Pengambilan</h5>
    </div>

    <?php if (isset($errorMsg)): ?>
        <div class="alert alert-danger py-1"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <!-- Navigasi Tab Area & Toggle Jenis History (IN / OUT) -->
    <div class="d-flex flex-wrap gap-2 mb-3 border-bottom pb-2 align-items-center justify-content-between">
        <ul class="nav nav-pills gap-1 mb-0">
            <?php foreach ($allTabs as $keyArea => $info): ?>
                <li class="nav-item">
                    <a style="width: 100px;" class="nav-link btn btn-sm <?= $activeArea === $keyArea ? 'active bg-primary text-white shadow-sm' : 'bg-white text-dark border' ?>"
                        href="index.php?page=cmc&area=<?= $keyArea ?>&h_type=<?= $historyType ?>">
                        <?= $info['title'] ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Toggle Jenis History (Output Produksi vs Pengambilan Part) Modern Style -->
        <div class="btn-group btn-group-sm shadow-sm rounded-pill p-1 bg-white border" role="group">
            <a href="index.php?page=cmc&area=<?= $activeArea ?>&h_type=IN"
                class="btn btn-sm rounded-pill px-3 me-1 fw-semibold transition-all <?= $historyType === 'IN' ? 'btn-success text-white shadow-sm' : 'btn-light text-muted border-0' ?>">
                Output Produksi
            </a>
            <a href="index.php?page=cmc&area=<?= $activeArea ?>&h_type=OUT"
                class="btn btn-sm rounded-pill px-3 ms-1 fw-semibold transition-all <?= $historyType === 'OUT' ? 'btn-danger text-white shadow-sm' : 'btn-light text-muted border-0' ?>">
                Pengambilan Part
            </a>
        </div>
    </div>

    <!-- Detail Rekapitulasi History Section -->
    <div class="card bg-white border shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 border-bottom">
            <span class="fw-bold text-secondary">
                Riwayat Data (<span class="<?= $historyType === 'IN' ? 'text-success' : 'text-danger' ?>"><?= $historyType === 'IN' ? 'Output Produksi' : 'Pengambilan Part' ?></span>) - Area: <span class="text-primary"><?= htmlspecialchars($activeArea) ?></span>
            </span>

            <!-- Filter Tanggal History -->
            <form method="GET" action="index.php" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="page" value="cmc">
                <input type="hidden" name="area" value="<?= htmlspecialchars($activeArea) ?>">
                <input type="hidden" name="h_type" value="<?= htmlspecialchars($historyType) ?>">
                <input type="date" name="history_date" class="form-control form-control-sm bg-white border" value="<?= htmlspecialchars($historyDateFilter) ?>">
                <button type="submit" class="btn btn-sm btn-primary px-3 py-1 shadow-sm">Filter</button>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 650px; overflow-y: auto;">
                <table class="table table-light table-striped table-bordered table-sm align-middle mb-0 text-center" style="font-size: 0.78rem;">
                    <thead class="table-secondary text-dark sticky-top">
                        <tr>
                            <th style="width: 110px;">Tanggal</th>
                            <th style="width: 150px;">Part Code</th>
                            <th class="text-start ps-2">Part Name / Keterangan</th>
                            <th style="width: 70px;">S1</th>
                            <th style="width: 70px;">S2</th>
                            <th style="width: 70px;">S3</th>
                            <th style="width: 90px;" class="<?= $historyType === 'IN' ? 'bg-success' : 'bg-danger' ?> text-white">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historyRows)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada riwayat transaksi untuk kategori ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $dateCounts = [];
                            foreach ($historyRows as $row) {
                                $dateCounts[$row['production_date']] = ($dateCounts[$row['production_date']] ?? 0) + 1;
                            }

                            $renderedDates = [];
                            foreach ($historyRows as $row):
                                $date = $row['production_date'];
                                $isFirst = !isset($renderedDates[$date]);
                                if ($isFirst) {
                                    $renderedDates[$date] = true;
                                }
                            ?>
                                <tr>
                                    <?php if ($isFirst): ?>
                                        <td class="fw-semibold align-middle bg-white" rowspan="<?= $dateCounts[$date] ?>">
                                            <?= htmlspecialchars($date) ?>
                                        </td>
                                    <?php endif; ?>

                                    <td class="text-primary fw-bold"><?= htmlspecialchars($row['part_code']) ?></td>
                                    <td class="text-start ps-2"><?= htmlspecialchars($row['part_name'] ?? '-') ?></td>
                                    <td><?= number_format($row['s1']) ?></td>
                                    <td><?= number_format($row['s2']) ?></td>
                                    <td><?= number_format($row['s3']) ?></td>
                                    <td class="fw-bold <?= $historyType === 'IN' ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' ?>">
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