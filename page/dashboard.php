<?php
// Ambil bulan dari parameter URL (default bulan berjalan)
$selected_month = $_GET['month'] ?? date('Y-m');
$selected_month_clean = $conn->real_escape_string($selected_month);

// 1. Helper Function untuk Format Nama Model Display (A3AHA5BEY2 -> AH-A5BEY2)
function format_model_display($model_raw)
{
    // Menghilangkan 'A3' di depan jika ada, lalu mengganti AHA -> AH- dan AUA -> AU-
    $cleaned = preg_replace('/^A3/', '', $model_raw);
    $cleaned = str_replace('AHA', 'AH-', $cleaned);
    $cleaned = str_replace('AUA', 'AU-', $cleaned);
    return $cleaned;
}

// 2. Ambil semua tanggal unik di bulan terpilih
$dates_query = $conn->query("SELECT DISTINCT tanggal FROM planning WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$selected_month_clean' ORDER BY tanggal ASC");
$dates = [];
while ($row = $dates_query->fetch_assoc()) {
    $dates[] = $row['tanggal'];
}

// 3. Ambil data planning sekaligus (1 Query)
$planning_data = [];
$models_indoor = [];  // Untuk Indoor (AH-)
$models_outdoor = []; // Untuk Outdoor (AU-)

$data_query = $conn->query("SELECT model, tanggal, shift, seq, qty_plan FROM planning WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$selected_month_clean' ORDER BY model ASC, shift ASC");

while ($row = $data_query->fetch_assoc()) {
    $m = $row['model'];
    $t = $row['tanggal'];
    $s = $row['shift'];

    // Kelompokkan model ke Indoor (AH-) atau Outdoor (AU-)
    if (str_contains($m, 'AH')) {
        if (!in_array($m, $models_indoor)) $models_indoor[] = $m;
    } elseif (str_contains($m, 'AU')) {
        if (!in_array($m, $models_outdoor)) $models_outdoor[] = $m;
    } else {
        if (!in_array($m, $models_indoor)) $models_indoor[] = $m;
    }

    // Simpan ke array multidimensi
    $planning_data[$m][$t][$s] = [
        'qty' => $row['qty_plan'],
        'seq' => $row['seq']
    ];
}

// Helper Function untuk Render Tabel Matrix
function render_planning_table($title, $badge_color, $models, $dates, $planning_data, $selected_month)
{
?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">
                <span class="badge bg-<?= $badge_color ?> me-2"><?= $title ?></span>
                Planning Monthly Detail
            </h6>
            <span class="text-muted small">Total Model: <strong><?= count($models) ?></strong></span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover mb-0 align-middle text-center" style="font-size: 0.8rem;">
                    <thead class="table-dark text-nowrap">
                        <tr>
                            <th class="text-start align-middle" rowspan="2" style="min-width: 150px;">Model</th>
                            <?php foreach ($dates as $date): ?>
                                <th colspan="3" class="border-bottom-0"><?= date('d/m', strtotime($date)) ?></th>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <?php foreach ($dates as $date): ?>
                                <th style="width: 45px; background-color: #343a40;">S1</th>
                                <th style="width: 45px; background-color: #343a40;">S2</th>
                                <th style="width: 45px; background-color: #343a40;">S3</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($models)): ?>
                            <?php foreach ($models as $m): ?>
                                <tr>
                                    <!-- Menampilkan Nama Model Terformat -->
                                    <td class="text-start fw-bold text-nowrap">
                                        <?= htmlspecialchars(format_model_display($m)) ?>
                                    </td>

                                    <?php foreach ($dates as $date): ?>
                                        <?php for ($shift = 1; $shift <= 3; $shift++): ?>
                                            <?php
                                            $item = $planning_data[$m][$date][$shift] ?? null;
                                            $qty  = $item['qty'] ?? 0;
                                            $seq  = $item['seq'] ?? null;
                                            ?>
                                            <td class="<?= $qty > 0 ? 'bg-light fw-semibold text-primary' : 'text-muted' ?>">
                                                <?php if ($qty > 0): ?>
                                                    <?= number_format($qty, 0, ',', '.') ?>
                                                    <?php if ($seq): ?>
                                                        <div style="font-size: 0.65rem;" class="text-secondary">#<?= $seq ?></div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        <?php endfor; ?>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= (count($dates) * 3) + 1 ?>" class="text-muted py-4">
                                    Tidak ada data model <strong><?= $title ?></strong> untuk bulan <?= date('F Y', strtotime($selected_month . '-01')) ?>.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php
}
?>

<!-- Filter Header Bulan -->
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-secondary">Dashboard Planning Production</h5>

        <form method="GET" action="index.php" class="d-flex align-items-center">
            <input type="hidden" name="page" value="dashboard">
            <label class="me-2 fw-bold small text-muted">Pilih Bulan:</label>
            <input type="month" name="month" value="<?= htmlspecialchars($selected_month) ?>" class="form-control form-control-sm" onchange="this.form.submit()">
        </form>
    </div>
</div>

<?php
// 1. Render Tabel Indoor (AH-)
render_planning_table("INDOOR", "success", $models_indoor, $dates, $planning_data, $selected_month);

// 2. Render Tabel Outdoor (AU-)
render_planning_table("OUTDOOR", "warning", $models_outdoor, $dates, $planning_data, $selected_month);
?>