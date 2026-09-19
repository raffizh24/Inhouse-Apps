<?php
// cmc.php - Central Monitoring Control (Rekapitulasi Transaksi per Part & Area)

require_once __DIR__ . '/../config.php';

// Pastikan session aktif dan user sudah login (jika diperlukan)
if (function_exists('check_login')) {
    check_login();
}

date_default_timezone_set('Asia/Jakarta');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Koneksi database gagal dimuat. Periksa kembali file config.php Anda.");
}

// Filter Bulan & Tahun
$filterMonth = $_GET['month'] ?? date('m');
$filterYear = $_GET['year'] ?? date('Y');

try {
    // 1. Ambil daftar unique part_code dari tabel stock_transactions berdasarkan bulan/tahun
    $stmtParts = $pdo->prepare("
        SELECT DISTINCT part_code, source_table 
        FROM stock_transactions 
        WHERE MONTH(production_date) = ? AND YEAR(production_date) = ?
        ORDER BY part_code ASC
    ");
    $stmtParts->execute([$filterMonth, $filterYear]);
    $partsList = $stmtParts->fetchAll(PDO::FETCH_ASSOC);

    // 2. Ambil seluruh transaksi pada bulan tersebut untuk dipetakan ke dalam tabel kartu
    $stmtTx = $pdo->prepare("
        SELECT part_code, role, transaction_type, qty, DAY(production_date) as day_num, production_date
        FROM stock_transactions 
        WHERE MONTH(production_date) = ? AND YEAR(production_date) = ?
    ");
    $stmtTx->execute([$filterMonth, $filterYear]);
    $allTransactions = $stmtTx->fetchAll(PDO::FETCH_ASSOC);

    // Petakan data transaksi agar mudah diakses: [part_code][role][day] = total_qty
    $matrixData = [];
    $monthlySummary = [];

    foreach ($allTransactions as $tx) {
        $pCode = $tx['part_code'];
        $role = strtoupper($tx['role']);
        $day = (int)$tx['day_num'];
        $type = $tx['transaction_type'];
        $qty = (int)$tx['qty'];

        // Tentukan nilai multiplier (OUT = negatif, IN = positif)
        $val = ($type === 'OUT') ? - ($qty) : $qty;

        // Akumulasi Harian
        if (!isset($matrixData[$pCode][$role][$day])) {
            $matrixData[$pCode][$role][$day] = 0;
        }
        $matrixData[$pCode][$role][$day] += $val;

        // Akumulasi Bulanan
        if (!isset($monthlySummary[$pCode][$role])) {
            $monthlySummary[$pCode][$role] = 0;
        }
        $monthlySummary[$pCode][$role] += $val;
    }
} catch (Exception $e) {
    $partsList = [];
    $matrixData = [];
    $monthlySummary = [];
    $errorMsg = $e->getMessage();
}

$listRoles = ['PRESS', 'PAINTING', 'INJECTION', 'HEPI', 'ASSY'];
?>

<div class="container-fluid py-3 px-4 bg-dark text-white min-vh-100">
    <!-- Header & Filter Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-light">CMC (Central Monitoring Control)</h4>
            <small class="text-secondary">Monitoring Rekapitulasi Transaksi Tiap Area (Press, Paint, Injection, HEPI, Assy)</small>
        </div>

        <!-- Filter Bulan & Tahun -->
        <form method="GET" action="index.php" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="page" value="cmc">

            <select name="month" class="form-select form-select-sm bg-secondary text-white border-0" onchange="this.form.submit()">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= sprintf('%02d', $m) ?>" <?= $filterMonth == sprintf('%02d', $m) ? 'selected' : '' ?>>
                        <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                    </option>
                <?php endfor; ?>
            </select>

            <select name="year" class="form-select form-select-sm bg-secondary text-white border-0" onchange="this.form.submit()">
                <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= $filterYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>

    <?php if (isset($errorMsg)): ?>
        <div class="alert alert-danger py-2">Gagal memuat data: <?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <?php if (empty($partsList)): ?>
        <div class="card bg-secondary bg-opacity-10 border border-secondary text-center py-5">
            <h5 class="text-muted">Tidak ada data transaksi ditemukan pada bulan/tahun ini.</h5>
        </div>
    <?php else: ?>
        <!-- Grid Container Card per Part Code -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
            <?php foreach ($partsList as $part):
                $pCode = $part['part_code'];
                $sTable = $part['source_table'];
            ?>
                <div class="col">
                    <div class="card bg-secondary bg-opacity-10 border border-secondary shadow-sm">
                        <!-- Card Header: Part Code & Source Table -->
                        <div class="card-header bg-dark text-center py-2 border-bottom border-secondary">
                            <h6 class="fw-bold text-info mb-0"><?= htmlspecialchars($pCode) ?></h6>
                            <span class="badge bg-secondary text-light" style="font-size: 0.65rem;"><?= htmlspecialchars($sTable) ?></span>
                        </div>

                        <div class="card-body p-2">
                            <!-- SECTION: MONTHLY SUMMARY -->
                            <div class="badge bg-primary w-100 mb-1 py-1 fw-bold text-uppercase" style="font-size: 0.75rem;">Monthly Summary</div>
                            <div class="table-responsive mb-2">
                                <table class="table table-dark table-bordered table-sm text-center mb-0" style="font-size: 0.75rem;">
                                    <thead class="table-secondary text-dark">
                                        <tr>
                                            <th>Ket</th>
                                            <?php foreach ($listRoles as $r): ?>
                                                <th><?= ucfirst(strtolower($r)) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold text-start ps-2">Total Qty</td>
                                            <?php foreach ($listRoles as $r):
                                                $valMonth = $monthlySummary[$pCode][$r] ?? 0;
                                            ?>
                                                <td class="<?= $valMonth < 0 ? 'text-danger' : 'text-light' ?>">
                                                    <?= number_format($valMonth) ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- SECTION: DAILY BREAKDOWN (1 - 31) -->
                            <div class="badge bg-success w-100 mb-1 py-1 fw-bold text-uppercase" style="font-size: 0.75rem;">Daily Breakdown (Tanggal 1 - 31)</div>
                            <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                                <table class="table table-dark table-bordered table-sm text-center mb-0 align-middle" style="font-size: 0.75rem;">
                                    <thead class="table-secondary text-dark sticky-top">
                                        <tr>
                                            <th style="width: 40px;">Tgl</th>
                                            <?php foreach ($listRoles as $r): ?>
                                                <th><?= ucfirst(strtolower($r)) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$filterMonth, (int)$filterYear);
                                        for ($day = 1; $day <= $daysInMonth; $day++):
                                        ?>
                                            <tr>
                                                <td class="fw-bold bg-dark text-light"><?= $day ?></td>
                                                <?php foreach ($listRoles as $r):
                                                    $valDay = $matrixData[$pCode][$r][$day] ?? 0;
                                                ?>
                                                    <td class="<?= $valDay < 0 ? 'text-danger' : ($valDay > 0 ? 'text-light' : 'text-muted') ?>">
                                                        <?= $valDay !== 0 ? number_format($valDay) : '-' ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>