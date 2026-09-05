<?php
$selected_month = $_GET['month'] ?? date('Y-m');

$dates_query = $conn->query("SELECT DISTINCT tanggal FROM planning WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$selected_month' ORDER BY tanggal ASC");
$dates = [];
while ($row = $dates_query->fetch_assoc()) {
    $dates[] = $row['tanggal'];
}

$models_query = $conn->query("SELECT DISTINCT model FROM planning WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$selected_month' ORDER BY model ASC");
?>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Data Planning Bulanan</h5>
        <form method="GET" class="d-flex align-items-center">
            <label class="me-2 fw-bold">Pilih Bulan:</label>
            <input type="month" name="month" value="<?= $selected_month ?>" class="form-control form-control-sm me-2" onchange="this.form.submit()">
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover mb-0 align-middle text-center" style="font-size: 0.85rem;">
                <thead class="table-primary text-nowrap">
                    <tr>
                        <th class="text-start">Model</th>
                        <?php foreach ($dates as $date): ?>
                            <th><?= date('d/m', strtotime($date)) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($models_query->num_rows > 0): ?>
                        <?php while ($m = $models_query->fetch_assoc()): ?>
                            <tr>
                                <td class="text-start fw-bold"><?= htmlspecialchars($m['model']) ?></td>
                                <?php foreach ($dates as $date): ?>
                                    <?php
                                    $q = $conn->query("SELECT qty_plan FROM planning WHERE model = '{$m['model']}' AND tanggal = '$date'");
                                    $qty_data = $q->fetch_assoc();
                                    $qty = $qty_data['qty_plan'] ?? 0;
                                    ?>
                                    <td><?= $qty > 0 ? number_format($qty) : '-' ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= count($dates) + 1 ?>" class="text-muted py-3">Tidak ada data planning untuk bulan ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>