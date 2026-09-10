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

// Tanggal Live Summary (Mengambil dari parameter GET live_date, jika kosong default ke hari ini)
$liveDate = $_GET['live_date'] ?? date('Y-m-d');

// Area & Jenis Transaksi Aktif untuk Detail History
$activeArea = $_GET['area'] ?? 'INJECTION';
$historyType = $_GET['h_type'] ?? 'IN'; // IN = Output Produksi, OUT = Pengambilan Part
$historyDateFilter = $_GET['history_date'] ?? '';

// Daftar area lengkap untuk Output Produksi (Termasuk Press)
$areasListIn = [
    'PRESS'     => ['title' => 'Press', 'table' => 'stok_pp'],
    'PAINTING'  => ['title' => 'Painting', 'table' => 'stok_pp'],
    'INJECTION' => ['title' => 'Injection', 'table' => 'stok_injection'],
    'HE'        => ['title' => 'HE', 'table' => 'stok_he'],
    'PIPING'    => ['title' => 'Piping', 'table' => 'stok_piping'],
];

// Daftar area untuk Pengambilan Part & Tab Bawah (Tanpa Press)
$areasListOut = [
    'PAINTING'  => ['title' => 'Painting', 'table' => 'stok_pp'],
    'INJECTION' => ['title' => 'Injection', 'table' => 'stok_injection'],
    'HE'        => ['title' => 'HE', 'table' => 'stok_he'],
    'PIPING'    => ['title' => 'Piping', 'table' => 'stok_piping'],
];

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
    $areaCondition = "(UPPER(t.role) LIKE '%HE%' OR UPPER(t.role) LIKE '%HEAT%' OR t.source_table = 'stok_he')";
} elseif ($activeArea === 'PIPING') {
    $masterTable = 'stok_piping';
    $areaCondition = "(UPPER(t.role) LIKE '%PIPE%' OR UPPER(t.role) LIKE '%PIPING%' OR t.source_table = 'stok_piping' OR UPPER(t.part_code) LIKE 'CPIP%')";
}

try {
    // 1. Ambil data mentah Live Summary berdasarkan $liveDate
    $stmtLive = $pdo->prepare("
        SELECT role, part_code, source_table, shift, transaction_type, SUM(qty) as total_qty
        FROM stock_transactions 
        WHERE production_date = ?
        GROUP BY role, part_code, source_table, shift, transaction_type
    ");
    $stmtLive->execute([$liveDate]);
    $rawLive = $stmtLive->fetchAll(PDO::FETCH_ASSOC);

    // Ambil daftar master part name dari seluruh tabel referensi untuk mapping di PHP
    $masterParts = [];
    $allMasters = ['stok_injection', 'stok_pp', 'stok_he', 'stok_piping'];
    foreach ($allMasters as $tbl) {
        $qMaster = $pdo->query("SELECT part_code, part_name FROM {$tbl}");
        while ($mRow = $qMaster->fetch(PDO::FETCH_ASSOC)) {
            $pCodeKey = strtoupper(trim($mRow['part_code']));
            if (!empty($mRow['part_name'])) {
                $masterParts[$pCodeKey] = $mRow['part_name'];
            }
        }
    }

    $liveProduction = []; // IN
    $livePengambilan = []; // OUT

    foreach ($rawLive as $row) {
        $r = strtoupper(trim($row['role'] ?? ''));
        $pCode = strtoupper(trim($row['part_code'] ?? ''));
        $pName = $masterParts[$pCode] ?? '-';
        $srcTable = strtolower(trim($row['source_table'] ?? ''));
        $shift = $row['shift'] ?? '-';
        $tType = strtoupper(trim($row['transaction_type'] ?? 'IN'));
        $qty = (int)$row['total_qty'];

        // Penentuan area Live Summary (Piping, Press, HE, Painting, Injection)
        $key = 'INJECTION';
        if (strpos($r, 'PIPE') !== false || strpos($r, 'PIPING') !== false || $srcTable === 'stok_piping' || strpos($pCode, 'CPIP') === 0 || ($r === 'HEPI' && strpos($pCode, 'CPIP') === 0)) {
            $key = 'PIPING';
        } elseif (strpos($r, 'PRESS') !== false || strpos($pCode, 'PRESS') !== false) {
            $key = 'PRESS';
        } elseif (strpos($r, 'HE') !== false || strpos($r, 'HEAT') !== false || $srcTable === 'stok_he' || $r === 'HEPI') {
            $key = 'HE';
        } elseif (strpos($r, 'PAINT') !== false || $srcTable === 'stok_pp') {
            $key = 'PAINTING';
        } elseif (strpos($r, 'INJ') !== false || $srcTable === 'stok_injection' || strpos($pCode, 'INJ') === 0) {
            $key = 'INJECTION';
        }

        if ($tType === 'OUT' || $qty < 0) {
            $livePengambilan[$key][$pCode]['name'] = $pName;
            $livePengambilan[$key][$pCode]['shifts'][$shift] = ($livePengambilan[$key][$pCode]['shifts'][$shift] ?? 0) + abs($qty);
        } else {
            $liveProduction[$key][$pCode]['name'] = $pName;
            $liveProduction[$key][$pCode]['shifts'][$shift] = ($liveProduction[$key][$pCode]['shifts'][$shift] ?? 0) + abs($qty);
        }
    }

    // 2. Ambil data Detail Rekapitulasi History berdasarkan Area & Jenis Transaksi (IN / OUT)
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
    $liveProduction = [];
    $livePengambilan = [];
    $historyRows = [];
    $errorMsg = $e->getMessage();
}
?>

<div class="container-fluid py-3 px-3 bg-light text-dark min-vh-100" style="font-size: 0.85rem;">

    <!-- Top Header: Live Summary Parts Today -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-secondary mb-0">Laporan Harian Produksi & Pengambilan</h5>
        <form method="GET" action="index.php" class="d-flex align-items-center gap-2">
            <input type="hidden" name="page" value="cmc">
            <input type="hidden" name="area" value="<?= htmlspecialchars($activeArea) ?>">
            <input type="hidden" name="h_type" value="<?= htmlspecialchars($historyType) ?>">
            <span class="text-muted small fw-semibold">Tanggal Produksi:</span>
            <input type="date" name="live_date" class="form-control form-control-sm bg-white border" value="<?= $liveDate ?>" onchange="this.form.submit()">
        </form>
    </div>

    <?php if (isset($errorMsg)): ?>
        <div class="alert alert-danger py-1"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <!-- BAGIAN 1: OUTPUT PRODUKSI (IN) - 5 Area (Termasuk Press) -->
    <div class="mb-3">
        <h6 class="fw-bold text-success mb-2">Output Produksi Inhouse</h6>
        <div class="row g-2">
            <?php foreach ($areasListIn as $keyArea => $info): ?>
                <div class="col">
                    <div class="card bg-white border shadow-sm h-100">
                        <div class="card-header bg-success bg-opacity-10 border-bottom py-1 px-2 fw-bold text-uppercase text-success text-truncate" style="font-size: 0.75rem;">
                            <?= $info['title'] ?>
                        </div>
                        <div class="card-body p-1">
                            <div class="table-responsive" style="max-height: 140px; overflow-y: auto;">
                                <table class="table table-light table-bordered table-sm text-center mb-0" style="font-size: 0.7rem;">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th class="text-start ps-1">Part Code / Name</th>
                                            <th style="width: 50px;">S1</th>
                                            <th style="width: 50px;">S2</th>
                                            <th style="width: 50px;">S3</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $partsInArea = $liveProduction[$keyArea] ?? [];
                                        if (empty($partsInArea)):
                                        ?>
                                            <tr>
                                                <td colspan="4" class="text-muted text-center py-1">Tidak ada data</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($partsInArea as $pCode => $data): ?>
                                                <tr>
                                                    <td class="text-start ps-1 text-truncate" style="max-width: 100px;" title="<?= htmlspecialchars($pCode . ' - ' . $data['name']) ?>">
                                                        <div class="fw-bold text-primary"><?= htmlspecialchars($pCode) ?></div>
                                                        <div class="text-muted text-truncate" style="font-size: 0.65rem;"><?= htmlspecialchars($data['name']) ?></div>
                                                    </td>
                                                    <td><?= $data['shifts'][1] ?? 0 ?></td>
                                                    <td><?= $data['shifts'][2] ?? 0 ?></td>
                                                    <td><?= $data['shifts'][3] ?? 0 ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- BAGIAN 2: PENGAMBILAN PART (OUT) - 4 Area (Tanpa Press) -->
    <div class="mb-4">
        <h6 class="fw-bold text-danger mb-2">Pengambilan Part Main Assy</h6>
        <div class="row g-2">
            <?php foreach ($areasListOut as $keyArea => $info): ?>
                <div class="col">
                    <div class="card bg-white border shadow-sm h-100">
                        <div class="card-header bg-danger bg-opacity-10 border-bottom py-1 px-2 fw-bold text-uppercase text-danger text-truncate" style="font-size: 0.75rem;">
                            <?= $info['title'] ?>
                        </div>
                        <div class="card-body p-1">
                            <div class="table-responsive" style="max-height: 140px; overflow-y: auto;">
                                <table class="table table-light table-bordered table-sm text-center mb-0" style="font-size: 0.7rem;">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th class="text-start ps-1">Part Code / Name</th>
                                            <th style="width: 50px;">S1</th>
                                            <th style="width: 50px;">S2</th>
                                            <th style="width: 50px;">S3</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $partsOutArea = $livePengambilan[$keyArea] ?? [];
                                        if (empty($partsOutArea)):
                                        ?>
                                            <tr>
                                                <td colspan="4" class="text-muted text-center py-1">Tidak ada data</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($partsOutArea as $pCode => $data): ?>
                                                <tr>
                                                    <td class="text-start ps-1 text-truncate" style="max-width: 100px;" title="<?= htmlspecialchars($pCode . ' - ' . $data['name']) ?>">
                                                        <div class="fw-bold text-primary"><?= htmlspecialchars($pCode) ?></div>
                                                        <div class="text-muted text-truncate" style="font-size: 0.65rem;"><?= htmlspecialchars($data['name']) ?></div>
                                                    </td>
                                                    <td><?= $data['shifts'][1] ?? 0 ?></td>
                                                    <td><?= $data['shifts'][2] ?? 0 ?></td>
                                                    <td><?= $data['shifts'][3] ?? 0 ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Navigasi Tab Area Bawah (Termasuk Press jika ingin diakses history-nya) -->
    <div class="d-flex flex-wrap gap-2 mb-3 border-bottom pb-2 align-items-center justify-content-between">
        <ul class="nav nav-pills gap-1 mb-0">
            <?php
            $allTabs = array_merge(['PRESS' => ['title' => 'Press', 'table' => 'stok_pp']], $areasListOut);
            foreach ($allTabs as $keyArea => $info):
            ?>
                <li class="nav-item">
                    <a style="width: 100px;" class="nav-link btn btn-sm <?= $activeArea === $keyArea ? 'active bg-primary text-white shadow-sm' : 'bg-white text-dark border' ?>"
                        href="index.php?page=cmc&area=<?= $keyArea ?>&h_type=<?= $historyType ?>&live_date=<?= $liveDate ?>">
                        <?= $info['title'] ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Toggle Jenis History (Output Produksi vs Pengambilan Part) -->
        <div class="btn-group btn-group-sm">
            <a href="index.php?page=cmc&area=<?= $activeArea ?>&h_type=IN&live_date=<?= $liveDate ?>" class="btn <?= $historyType === 'IN' ? 'btn-success' : 'btn-outline-success' ?>">
                Rekap Output Produksi
            </a>
            <a href="index.php?page=cmc&area=<?= $activeArea ?>&h_type=OUT&live_date=<?= $liveDate ?>" class="btn <?= $historyType === 'OUT' ? 'btn-danger' : 'btn-outline-danger' ?>">
                Rekap Pengambilan Part
            </a>
        </div>
    </div>

    <!-- Detail Rekapitulasi History Section -->
    <div class="card bg-white border shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 border-bottom">
            <span class="fw-bold text-secondary">
                Detail Rekapitulasi History (<span class="<?= $historyType === 'IN' ? 'text-success' : 'text-danger' ?>"><?= $historyType === 'IN' ? 'Output Produksi' : 'Pengambilan Part' ?></span>) - Area: <span class="text-primary"><?= htmlspecialchars($activeArea) ?></span>
            </span>

            <!-- Filter Tanggal History -->
            <form method="GET" action="index.php" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="page" value="cmc">
                <input type="hidden" name="area" value="<?= htmlspecialchars($activeArea) ?>">
                <input type="hidden" name="h_type" value="<?= htmlspecialchars($historyType) ?>">
                <input type="hidden" name="live_date" value="<?= $liveDate ?>">
                <input type="date" name="history_date" class="form-control form-control-sm bg-white border" value="<?= $historyDateFilter ?>">
                <button type="submit" class="btn btn-sm btn-primary px-3 py-1 shadow-sm">Filter</button>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
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