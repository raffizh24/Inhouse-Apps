<?php
// Set timezone ke WIB agar sesuai dengan jam lokal server/pabrik
date_default_timezone_set('Asia/Jakarta');
function getProductionDateOnly($datetime)
{
    $time = date('H:i', strtotime($datetime));
    $date = date('Y-m-d', strtotime($datetime));

    if ($time < '09:00') {
        return date('Y-m-d', strtotime($date . ' -1 day'));
    }
    return $date;
}

function getShift($time)
{
    if ($time >= '09:00' && $time < '18:00') return 1;
    if ($time >= '18:00' || $time < '01:30') return 2;
    return 3;
}

$now                    = date('Y-m-d H:i:s');
$currentDate            = getProductionDateOnly($now);
$currentShift           = getShift(date('H:i', strtotime($now)));

$currentDateTr          = $currentDate;
$currentShiftTr         = $currentShift;

$productionDateDisplay  = date('d/m/Y', strtotime($currentDate));
$productionShiftDisplay = $currentShift;

// =========================================================================
// 1. QUERY AGREGASI TOTAL QTY PER PART & SHIFT (STATUS ASSY / OUT)
// =========================================================================
$shift_summary = [];

// -------------------------------------------------------------------------
// A. Query Summary Painting (seid_ac_pp)
// -------------------------------------------------------------------------
$query_summary_paint = mysqli_query($conn, "
    SELECT 
        TRIM(part_code) AS part_code, 
        shift, 
        SUM(qty) AS total_qty 
    FROM `seid_ac_pp`.`transaction` 
    WHERE DATE(date_tr) = '$currentDate' AND status = 'ASSY'
    GROUP BY TRIM(part_code), shift
");

if ($query_summary_paint) {
    while ($row = mysqli_fetch_assoc($query_summary_paint)) {
        $code = $row['part_code'];
        $shift = (int)$row['shift'];
        $shift_summary[$code][$shift] = (int)$row['total_qty'];
    }
}

// -------------------------------------------------------------------------
// B. Query Summary Injection (seid_ac_inj)
// -------------------------------------------------------------------------
$query_summary_inj = mysqli_query($conn, "
    SELECT 
        TRIM(part_code) AS part_code, 
        shift, 
        SUM(qty) AS total_qty 
    FROM `seid_ac_inj`.`transaction` 
    WHERE DATE(date_tr) = '$currentDate' AND status = 'ASSY'
    GROUP BY TRIM(part_code), shift
");

if ($query_summary_inj) {
    while ($row = mysqli_fetch_assoc($query_summary_inj)) {
        $code = $row['part_code'];
        $shift = (int)$row['shift'];
        $shift_summary[$code][$shift] = (int)$row['total_qty'];
    }
}

// -------------------------------------------------------------------------
// C. Query Summary HE (seid_ac_hepi)
// -------------------------------------------------------------------------
$query_summary_he = mysqli_query($conn, " 
    SELECT 
        CASE 
            WHEN coupon LIKE 'CSR%' THEN 'DCON-B070JBEZ'
            WHEN coupon LIKE 'CDR%' THEN 'DCON-B105JBEZ'
            WHEN coupon LIKE 'SRI%' THEN 'DCON-B074JBEZ'
            WHEN coupon LIKE 'DRI%' THEN 'DCON-B075JBEZ'
            WHEN coupon LIKE 'EVA%' THEN 'PEVA-B243JBEZ'
            ELSE TRIM(coupon)
        END AS part_code, 
        shift, 
        SUM(qty) AS total_qty 
    FROM `seid_ac_hepi`.`history_lot` 
    WHERE DATE(tgl_lot) = '$currentDate' AND status = 'OUT'
    GROUP BY part_code, shift
");

if ($query_summary_he) {
    while ($row = mysqli_fetch_assoc($query_summary_he)) {
        $code = $row['part_code'];
        $shift = (int)$row['shift'];
        $shift_summary[$code][$shift] = (int)$row['total_qty'];
    }
}

// -------------------------------------------------------------------------
// D. Query Summary Piping (seid_ac_hepi)
// -------------------------------------------------------------------------
$query_summary_piping = mysqli_query($conn, "
    SELECT 
        CASE 
            WHEN coupon LIKE 'ALL-IDU%' THEN 'ALL-IDU'
            WHEN coupon LIKE 'SUC-IVT%' THEN 'SUC-IVT'
            -- Memotong nomor urut lot (contoh: 'DIS-5K2-24' menjadi 'DIS-5K2')
            ELSE CONCAT(
                SUBSTRING_INDEX(coupon, '-', 1), 
                '-', 
                SUBSTRING_INDEX(SUBSTRING_INDEX(coupon, '-', 2), '-', -1)
            )
        END AS part_code, 
        shift, 
        SUM(qty) AS total_qty 
    FROM `seid_ac_hepi`.`piping_history_lot` 
    WHERE DATE(tgl_lot) = '$currentDate' 
      AND status = 'OUT'
    GROUP BY part_code, shift
");

if ($query_summary_piping) {
    while ($row = mysqli_fetch_assoc($query_summary_piping)) {
        $code = trim($row['part_code']);
        $shift = (int)$row['shift'];
        $shift_summary[$code][$shift] = (int)$row['total_qty'];
    }
}

// =========================================================================
// 2. QUERY HISTORY (STATUS ASSY, TANGGAL & NAMA PART)
// =========================================================================

// Painting (seid_ac_pp)
$query_tr_paint = mysqli_query($conn, "
    SELECT t.id, t.part_code, p.part_name, t.date_tr, t.shift, t.qty
    FROM `seid_ac_pp`.`transaction` t
    LEFT JOIN `seid_ac_pp`.`part` p ON t.part_code = p.part_code
    WHERE t.part_code IN ('CCHS-B829JBTA', 'GCAB-A646JBTA', 'GCAB-A767JBTA', 'PPLT-B282JBTA') 
      AND t.status = 'ASSY'
    ORDER BY t.id DESC LIMIT 20
");

// Injection (seid_ac_inj)
$query_tr_inj = mysqli_query($conn, "
    SELECT t.id, t.part_code, p.part_name, t.date_tr, t.shift, t.qty
    FROM `seid_ac_inj`.`transaction` t
    LEFT JOIN `seid_ac_inj`.`part` p ON t.part_code = p.part_code
    WHERE t.part_code IN ('GGADPA056JBFA', 'GWAK-A517JBFA', 'GWAK-A517JBFB', 'GWAK-A520JBFA', 'GWAK-A544JBFC', 'LCHS-A801JBFA', 'LCHS-A801JBFC') 
      AND t.status = 'ASSY'
    ORDER BY t.id DESC LIMIT 20
");

// HISTORY HE (database: seid_ac_hepi, tabel: history_lot)
$query_tr_he = mysqli_query($conn, "
    SELECT 
        t.id_history_evap AS id,
        t.coupon AS part_code, -- Coupon dipanggil utuh tanpa diubah
        CASE 
            WHEN t.coupon LIKE 'CSR%' THEN 'Condenser SR Normal'
            WHEN t.coupon LIKE 'CDR%' THEN 'Condenser DR Normal'
            WHEN t.coupon LIKE 'SRI%' THEN 'Condenser SR Inverter'
            WHEN t.coupon LIKE 'DRI%' THEN 'Condenser DR Inverter'
            WHEN t.coupon LIKE 'EVA%' THEN 'Evaporator'
            ELSE '-'
        END AS part_name,
        t.tgl_lot AS date_tr, -- Mengambil timestamp utuh
        t.shift,
        t.qty,
        t.status
    FROM `seid_ac_hepi`.`history_lot` t
    WHERE t.status = 'OUT'
    ORDER BY t.id_history_evap DESC 
    LIMIT 20
");

// HISTORY PIPING (database: seid_ac_hepi, tabel: piping_history_lot)
$query_tr_piping = mysqli_query($conn, "
    SELECT 
        t.id_history AS id,
        t.coupon AS part_code, -- Coupon dipanggil utuh tanpa diubah
        CASE 
            -- CAPILLARY
            WHEN t.coupon LIKE 'CAP-5K2%' THEN 'Capillary 5K2'
            WHEN t.coupon LIKE 'CAP-7K1%' THEN 'Capillary 7K'
            WHEN t.coupon LIKE 'CAP-9K2%' THEN 'Capillary 9K2'
            WHEN t.coupon LIKE 'CAP-9CY%' THEN 'Capillary 9CAY'
            WHEN t.coupon LIKE 'CAP-68K%' THEN 'Capillary 6K & 8K'
            WHEN t.coupon LIKE 'CAP-10K%' THEN 'Capillary 10K'
            WHEN t.coupon LIKE 'CAP-13K%' THEN 'Capillary 13K'
            
            -- DISCHARGE
            WHEN t.coupon LIKE 'DIS-5K2%' THEN 'Discharge 5K2'
            WHEN t.coupon LIKE 'DIS-7K1%' THEN 'Discharge 7K'
            WHEN t.coupon LIKE 'DIS-9K2%' THEN 'Discharge 9K2'
            WHEN t.coupon LIKE 'DIS-9CY%' THEN 'Discharge 9CAY'
            WHEN t.coupon LIKE 'DIS-68K%' THEN 'Discharge 6K & 8K'
            WHEN t.coupon LIKE 'DIS-10K%' THEN 'Discharge 10K'
            WHEN t.coupon LIKE 'DIS-13K%' THEN 'Discharge 13K'
            
            -- SUCTION
            WHEN t.coupon LIKE 'SUC-5K2%' THEN 'Suction 5K2 & 7K'
            WHEN t.coupon LIKE 'SUC-9K2%' THEN 'Suction 9K2'
            WHEN t.coupon LIKE 'SUC-9CY%' THEN 'Suction 9CAY'
            WHEN t.coupon LIKE 'SUC-IVT%' THEN 'Suction 6K, 8K & 10K'
            WHEN t.coupon LIKE 'SUC-13K%' THEN 'Suction 13K'
            
            -- INDOOR
            WHEN t.coupon LIKE 'ALL-IDU%' THEN 'Tube Assy - Indoor'
            
            ELSE '-'
        END AS part_name,
        t.tgl_lot AS date_tr, -- Mengambil timestamp utuh
        t.shift,
        t.qty,
        t.status
    FROM `seid_ac_hepi`.`piping_history_lot` t
    WHERE t.status = 'OUT'
    ORDER BY t.id_history DESC 
    LIMIT 20
");

// 3. DAFTAR PART PER KATEGORI (TETAP SAMA)
$parts_painting = [
    ['code' => 'CCHS-B829JBTA', 'name' => 'Base Pan'],
    ['code' => 'GCAB-A646JBTA', 'name' => 'Top Table'],
    ['code' => 'GCAB-A767JBTA', 'name' => 'Front Panel'],
    ['code' => 'PPLT-B282JBTA', 'name' => 'Side Cover']
];

$parts_injection = [
    ['code' => 'GGADPA056JBFA', 'name' => 'Fan Guard'],
    ['code' => 'GWAK-A517JBFA', 'name' => 'Front Panel'],
    ['code' => 'GWAK-A517JBFB', 'name' => 'Front Panel Black'],
    ['code' => 'GWAK-A520JBFA', 'name' => 'Front Panel PCI'],
    ['code' => 'GWAK-A544JBFC', 'name' => 'Front Panel DEY'],
    ['code' => 'LCHS-A801JBFA', 'name' => 'Cabinet'],
    ['code' => 'LCHS-A801JBFC', 'name' => 'Cabinet DEY']
];

$parts_he = [
    ['code' => 'PEVA-B243JBEZ', 'name' => 'Evaporator'],
    ['code' => 'DCON-B070JBEZ', 'name' => 'Condensor SR Normal'],
    ['code' => 'DCON-B105JBEZ', 'name' => 'Condensor DR Normal'],
    ['code' => 'DCON-B074JBEZ', 'name' => 'Condensor SR Inverter'],
    ['code' => 'DCON-B075JBEZ', 'name' => 'Condensor DR Inverter']
];

$parts_piping = [
    ['code' => 'CAP-5K2', 'name' => 'Capillary 5K2'],
    ['code' => 'CAP-7K1', 'name' => 'Capillary 7K'],
    ['code' => 'CAP-9K2', 'name' => 'Capillary 9K2'],
    ['code' => 'CAP-9CY', 'name' => 'Capillary 9CAY'],
    ['code' => 'CAP-68K', 'name' => 'Capillary 6K & 8K'],
    ['code' => 'CAP-10K', 'name' => 'Capillary 10K'],
    ['code' => 'CAP-13K', 'name' => 'Capillary 13K'],

    ['code' => 'DIS-5K2', 'name' => 'Discharge 5K2'],
    ['code' => 'DIS-7K1', 'name' => 'Discharge 7K'],
    ['code' => 'DIS-9K2', 'name' => 'Discharge 9K2'],
    ['code' => 'DIS-9CY', 'name' => 'Discharge 9CAY'],
    ['code' => 'DIS-68K', 'name' => 'Discharge 6K & 8K'],
    ['code' => 'DIS-10K', 'name' => 'Discharge 10K'],
    ['code' => 'DIS-13K', 'name' => 'Discharge 13K'],

    ['code' => 'SUC-5K2', 'name' => 'Suction 5K2 & 7K'],
    ['code' => 'SUC-9K2', 'name' => 'Suction 9K2'],
    ['code' => 'SUC-9CY', 'name' => 'Suction 9CAY'],
    ['code' => 'SUC-IVT', 'name' => 'Suction 6K, 8K & 10K'],
    ['code' => 'SUC-13K', 'name' => 'Suction 13K'],

    ['code' => 'ALL-IDU', 'name' => 'Tube Assy - Indoor'],
];

// HANDLE FINISH PRODUCTION BATCH
if (isset($_POST['btn_submit_batch'])) {
    $category = $_POST['category'] ?? 'injection';
    $parts    = $_POST['parts'] ?? [];

    // Filter part yang qty-nya diisi > 0
    $itemsToProcess = [];
    foreach ($parts as $item) {
        $partCode = trim($item['part_code'] ?? '');
        $qty      = (int)($item['qty'] ?? 0);

        if ($partCode !== '' && $qty > 0) {
            $itemsToProcess[] = [
                'part_code' => $partCode,
                'qty'       => $qty
            ];
        }
    }

    // VALIDASI INPUT
    if (empty($itemsToProcess)) {
        echo "<script>
            alert('Part code atau qty tidak valid');
            history.back();
        </script>";
        exit;
    }

    // Pemisahan Database & Kolom Stok
    if ($category === 'painting') {
        $dbTarget     = 'seid_ac_pp';
        $targetColumn = 'qty_paint';
    } else {
        $dbTarget     = 'seid_ac_inj';
        $targetColumn = 'qty_injection';
    }

    // START TRANSACTION
    mysqli_begin_transaction($conn);

    try {
        foreach ($itemsToProcess as $item) {
            $partCode = $item['part_code'];
            $qty      = $item['qty'];

            // INSERT TRANSACTION (Gunakan $now agar jam H:i:s ikut tersimpan)
            $queryInsert = mysqli_query($conn, "
                INSERT INTO `$dbTarget`.`transaction`
                (part_code, date_tr, shift, qty, status)
                VALUES
                ('$partCode', '$currentDateTr', '$currentShiftTr', '$qty', 'ASSY')
            ");

            if (!$queryInsert) {
                throw new Exception("Gagal insert transaction ASSY ke $dbTarget: " . mysqli_error($conn));
            }

            // UPDATE STOCK ke DB Target
            $queryUpdate = mysqli_query($conn, "
                UPDATE `$dbTarget`.`part` 
                SET $targetColumn = $targetColumn - $qty 
                WHERE part_code = '$partCode'
            ");

            if (!$queryUpdate) {
                throw new Exception("Gagal update stok ke $dbTarget: " . mysqli_error($conn));
            }
        }

        // COMMIT
        mysqli_commit($conn);

        echo "<script>
            alert('Finish production recorded successfully');
            location.href='index.php?page=upload_actual';
        </script>";
        exit;
    } catch (Exception $e) {
        // ROLLBACK
        mysqli_rollback($conn);

        $errorMessage = addslashes($e->getMessage());
        echo "<script>
            alert('ERROR: {$errorMessage}');
            history.back();
        </script>";
        exit;
    }
}
?>

<!-- ================= INFORMASI TANGGAL & SHIFT SAAT INI ================= -->
<div class="alert alert-info shadow-sm d-flex justify-content-between align-items-center py-2 px-3 mb-3">
    <div>
        <i class="bi bi-calendar-event me-2"></i><strong>Tanggal Produksi:</strong>
        <span class="badge bg-primary fs-6 ms-1"><?= $productionDateDisplay ?></span>
    </div>
    <div>
        <i class="bi bi-clock-history me-2"></i><strong>Shift Aktif:</strong>
        <span class="badge bg-warning text-dark fs-6 ms-1">Shift <?= $productionShiftDisplay ?></span>
    </div>
</div>

<!-- ================= BAGIAN 1: FORM INPUT ACTUAL (4 CARDS - COL-LG-3) ================= -->
<div class="row g-3 mb-4">
    <!-- ================= CARD 1: PAINTING PARTS ================= -->
    <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-primary text-white fw-bold py-2 px-3 d-flex justify-content-between align-items-center">
                <small><i class="bi bi-paint-bucket me-1"></i>Painting Parts</small>
            </div>
            <div class="card-body p-2 d-flex flex-column justify-content-between">
                <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                    <table class="table table-bordered align-middle text-center mb-2" style="font-size: 0.75rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-start ps-2">Part</th>
                                <th style="width: 45px;">S1</th>
                                <th style="width: 45px;">S2</th>
                                <th style="width: 45px;">S3</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parts_painting as $part):
                                $code = $part['code'];
                                $s1 = $shift_summary[$code][1] ?? 0;
                                $s2 = $shift_summary[$code][2] ?? 0;
                                $s3 = $shift_summary[$code][3] ?? 0;
                            ?>
                                <tr>
                                    <td class="text-start ps-2 py-1">
                                        <div class="fw-bold text-dark"><?= $code ?></div>
                                        <small class="text-muted d-block text-truncate" style="max-width: 120px;"><?= $part['name'] ?></small>
                                    </td>
                                    <td class="py-1 text-muted fw-bold"><?= $s1 ?></td>
                                    <td class="py-1 text-muted fw-bold"><?= $s2 ?></td>
                                    <td class="py-1 text-muted fw-bold"><?= $s3 ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Tombol Pemicu Modal -->
                <div class="d-grid mt-2">
                    <button type="button" class="btn btn-primary btn-sm fw-bold py-1" data-bs-toggle="modal" data-bs-target="#modalPainting">
                        <i class="bi bi-box-seam me-1"></i>Ambil Part
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= CARD 2: INJECTION PARTS ================= -->
    <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-info text-dark fw-bold py-2 px-3 d-flex justify-content-between align-items-center">
                <small><i class="bi bi-cpu me-1"></i>Injection Parts</small>
            </div>
            <div class="card-body p-2 d-flex flex-column justify-content-between">
                <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                    <table class="table table-bordered align-middle text-center mb-2" style="font-size: 0.75rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-start ps-2">Part</th>
                                <th style="width: 45px;">S1</th>
                                <th style="width: 45px;">S2</th>
                                <th style="width: 45px;">S3</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parts_injection as $part):
                                $code = $part['code'];
                                $s1 = $shift_summary[$code][1] ?? 0;
                                $s2 = $shift_summary[$code][2] ?? 0;
                                $s3 = $shift_summary[$code][3] ?? 0;
                            ?>
                                <tr>
                                    <td class="text-start ps-2 py-1">
                                        <div class="fw-bold text-dark"><?= $code ?></div>
                                        <small class="text-muted d-block text-truncate" style="max-width: 120px;"><?= $part['name'] ?></small>
                                    </td>
                                    <td class="py-1 text-muted fw-bold"><?= $s1 ?></td>
                                    <td class="py-1 text-muted fw-bold"><?= $s2 ?></td>
                                    <td class="py-1 text-muted fw-bold"><?= $s3 ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Tombol Pemicu Modal -->
                <div class="d-grid mt-2">
                    <button type="button" class="btn btn-info btn-sm fw-bold py-1 text-dark" data-bs-toggle="modal" data-bs-target="#modalInjection">
                        <i class="bi bi-box-seam me-1"></i>Ambil Part
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= MODAL AMBIL PART - PAINTING ================= -->
    <div class="modal fade" id="modalPainting" tabindex="-1" aria-labelledby="modalPaintingLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST">
                    <input type="hidden" name="category" value="painting">
                    <div class="modal-header bg-primary text-white py-2">
                        <h6 class="modal-title id=" modalPaintingLabel"><i class="bi bi-paint-bucket me-2"></i>Input Ambil Part Painting</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Kode & Nama Part</th>
                                        <th style="width: 100px;">Qty Ambil</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($parts_painting as $index => $part): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark"><?= $part['code'] ?></div>
                                                <small class="text-muted"><?= $part['name'] ?></small>
                                                <input type="hidden" name="parts[<?= $index ?>][part_code]" value="<?= $part['code'] ?>">
                                            </td>
                                            <td>
                                                <input type="number" name="parts[<?= $index ?>][qty]" class="form-control form-control-sm text-center" min="0" placeholder="0">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="btn_submit_batch" class="btn btn-primary btn-sm fw-bold">Submit Transaksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= MODAL AMBIL PART - INJECTION ================= -->
    <div class="modal fade" id="modalInjection" tabindex="-1" aria-labelledby="modalInjectionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST">
                    <input type="hidden" name="category" value="injection">
                    <div class="modal-header bg-info text-dark py-2">
                        <h6 class="modal-title" id="modalInjectionLabel"><i class="bi bi-cpu me-2"></i>Input Ambil Part Injection</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Kode & Nama Part</th>
                                        <th style="width: 100px;">Qty Ambil</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($parts_injection as $index => $part): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark"><?= $part['code'] ?></div>
                                                <small class="text-muted"><?= $part['name'] ?></small>
                                                <input type="hidden" name="parts[<?= $index ?>][part_code]" value="<?= $part['code'] ?>">
                                            </td>
                                            <td>
                                                <input type="number" name="parts[<?= $index ?>][qty]" class="form-control form-control-sm text-center" min="0" placeholder="0">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="btn_submit_batch" class="btn btn-info btn-sm fw-bold text-dark">Submit Transaksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- CARD 3: HE PARTS -->
    <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-warning text-dark fw-bold py-2 px-3 d-flex justify-content-between align-items-center">
                <small><i class="bi bi-fire me-1"></i>HE Parts</small>
            </div>
            <div class="card-body p-2 d-flex flex-column justify-content-between">
                <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                    <table class="table table-bordered align-middle text-center mb-0" style="font-size: 0.75rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-start ps-2">Part</th>
                                <th style="width: 45px;">S1</th>
                                <th style="width: 45px;">S2</th>
                                <th style="width: 45px;">S3</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($parts_he)): ?>
                                <?php foreach ($parts_he as $part):
                                    $code = $part['code'];
                                    $s1 = $shift_summary[$code][1] ?? 0;
                                    $s2 = $shift_summary[$code][2] ?? 0;
                                    $s3 = $shift_summary[$code][3] ?? 0;
                                ?>
                                    <tr>
                                        <td class="text-start ps-2 py-1">
                                            <div class="fw-bold text-dark text-truncate" style="max-width: 200px;"><?= $code ?></div>
                                            <div class="text-muted small text-truncate" style="max-width: 200px; font-size: 0.68rem;"><?= $part['name'] ?></div>
                                        </td>
                                        <td class="py-1 fw-bold text-muted"><?= $s1 ?></td>
                                        <td class="py-1 fw-bold text-muted"><?= $s2 ?></td>
                                        <td class="py-1 fw-bold text-muted"><?= $s3 ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-muted py-3">Belum ada data transaksi HE hari ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD 4: PIPING PARTS -->
    <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-secondary text-white fw-bold py-2 px-3 d-flex justify-content-between align-items-center">
                <small><i class="bi bi-diagram-3 me-1"></i>Piping Parts</small>
            </div>
            <div class="card-body p-2 d-flex flex-column justify-content-between">
                <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                    <table class="table table-bordered align-middle text-center mb-0" style="font-size: 0.75rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-start ps-2">Part</th>
                                <th style="width: 45px;">S1</th>
                                <th style="width: 45px;">S2</th>
                                <th style="width: 45px;">S3</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($parts_piping)): ?>
                                <?php foreach ($parts_piping as $part):
                                    $code = $part['code'];
                                    $s1 = $shift_summary[$code][1] ?? 0;
                                    $s2 = $shift_summary[$code][2] ?? 0;
                                    $s3 = $shift_summary[$code][3] ?? 0;
                                ?>
                                    <tr>
                                        <td class="text-start ps-2 py-1">
                                            <div class="fw-bold text-dark text-truncate" style="max-width: 200px;"><?= $code ?></div>
                                            <div class="text-muted small text-truncate" style="max-width: 200px; font-size: 0.68rem;"><?= $part['name'] ?></div>
                                        </td>
                                        <td class="py-1 fw-bold text-muted"><?= $s1 ?></td>
                                        <td class="py-1 fw-bold text-muted"><?= $s2 ?></td>
                                        <td class="py-1 fw-bold text-muted"><?= $s3 ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-muted py-3">Belum ada data transaksi Piping hari ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= BAGIAN 2: HISTORY TRANSAKSI (4 CARDS - COL-LG-3) ================= -->
<div class="row g-3 mb-4">

    <!-- HISTORY 1: PAINTING -->
    <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-dark text-white text-center fw-bold py-2 px-3 d-flex justify-content-between align-items-center">
                <small>History Pengambilan Painting (20 Terakhir)</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                    <table class="table table-hover table-striped mb-0 align-middle text-center" style="font-size: 0.75rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-start ps-2">Waktu</th>
                                <th class="text-start">Part</th>
                                <th>Shift</th>
                                <th class="pe-2">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query_tr_paint && mysqli_num_rows($query_tr_paint) > 0): ?>
                                <?php while ($tr = mysqli_fetch_assoc($query_tr_paint)): ?>
                                    <tr>
                                        <td class="text-start ps-2 py-1 text-muted" style="font-size: 0.68rem;"><?= !empty($tr['date_tr']) ? date('d/m H:i', strtotime($tr['date_tr'])) : '-' ?></td>
                                        <td class="fw-bold text-start py-1">
                                            <?= htmlspecialchars($tr['part_code']) ?>
                                            <?php if (!empty($tr['part_name'])): ?>
                                                <br><small class="text-muted fw-normal" style="font-size: 0.65rem;"><?= htmlspecialchars($tr['part_name']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-1"><span class="badge bg-secondary" style="font-size: 0.65rem;"><?= $tr['shift'] ?></span></td>
                                        <td class="fw-semibold text-primary pe-2 py-1"><?= number_format($tr['qty'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-muted py-3">Belum ada log.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- HISTORY 2: INJECTION -->
    <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-dark text-white text-center fw-bold py-2 px-3 d-flex justify-content-between align-items-center">
                <small>History Pengambilan Injection (20 Terakhir)</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                    <table class="table table-hover table-striped mb-0 align-middle text-center" style="font-size: 0.75rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-start ps-2">Waktu</th>
                                <th class="text-start">Part</th>
                                <th>Shift</th>
                                <th class="pe-2">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query_tr_inj && mysqli_num_rows($query_tr_inj) > 0): ?>
                                <?php while ($tr = mysqli_fetch_assoc($query_tr_inj)): ?>
                                    <tr>
                                        <td class="text-start ps-2 py-1 text-muted" style="font-size: 0.68rem;"><?= !empty($tr['date_tr']) ? date('d/m H:i', strtotime($tr['date_tr'])) : '-' ?></td>
                                        <td class="fw-bold text-start py-1">
                                            <?= htmlspecialchars($tr['part_code']) ?>
                                            <?php if (!empty($tr['part_name'])): ?>
                                                <br><small class="text-muted fw-normal" style="font-size: 0.65rem;"><?= htmlspecialchars($tr['part_name']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-1"><span class="badge bg-secondary" style="font-size: 0.65rem;"><?= $tr['shift'] ?></span></td>
                                        <td class="fw-semibold text-primary pe-2 py-1"><?= number_format($tr['qty'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-muted py-3">Belum ada log.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- HISTORY 3: HE -->
    <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-dark text-white text-center fw-bold py-2 px-3 d-flex justify-content-between align-items-center">
                <small>History Pengambilan HE (20 Terakhir)</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                    <table class="table table-hover table-striped mb-0 align-middle text-center" style="font-size: 0.75rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-start ps-2">Waktu</th>
                                <th class="text-start">Part</th>
                                <th>Shift</th>
                                <th class="pe-2">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query_tr_he && mysqli_num_rows($query_tr_he) > 0): ?>
                                <?php while ($tr = mysqli_fetch_assoc($query_tr_he)): ?>
                                    <tr>
                                        <td class="text-start ps-2 py-1 text-muted" style="font-size: 0.68rem;">
                                            <?= !empty($tr['date_tr']) ? date('d/m H:i', strtotime($tr['date_tr'])) : '-' ?>
                                        </td>
                                        <td class="fw-bold text-start py-1">
                                            <?= htmlspecialchars($tr['part_code']) ?>
                                            <?php if (!empty($tr['part_name'])): ?>
                                                <br><small class="text-muted fw-normal" style="font-size: 0.65rem;"><?= htmlspecialchars($tr['part_name']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-1"><span class="badge bg-secondary" style="font-size: 0.65rem;"><?= $tr['shift'] ?></span></td>
                                        <td class="fw-semibold text-primary pe-2 py-1"><?= number_format($tr['qty'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-muted py-3">Belum ada log.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- HISTORY 4: PIPING -->
    <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-dark text-white text-center fw-bold py-2 px-3 d-flex justify-content-between align-items-center">
                <small>History Pengambilan Piping (20 Terakhir)</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                    <table class="table table-hover table-striped mb-0 align-middle text-center" style="font-size: 0.75rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-start ps-2">Waktu</th>
                                <th class="text-start">Part</th>
                                <th>Shift</th>
                                <th class="pe-2">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query_tr_piping && mysqli_num_rows($query_tr_piping) > 0): ?>
                                <?php while ($tr = mysqli_fetch_assoc($query_tr_piping)): ?>
                                    <tr>
                                        <td class="text-start ps-2 py-1 text-muted" style="font-size: 0.68rem;">
                                            <?= !empty($tr['date_tr']) ? date('d/m H:i', strtotime($tr['date_tr'])) : '-' ?>
                                        </td>
                                        <td class="fw-bold text-start py-1">
                                            <?= htmlspecialchars($tr['part_code']) ?>
                                            <?php if (!empty($tr['part_name'])): ?>
                                                <br><small class="text-muted fw-normal" style="font-size: 0.65rem;"><?= htmlspecialchars($tr['part_name']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-1"><span class="badge bg-secondary" style="font-size: 0.65rem;"><?= $tr['shift'] ?></span></td>
                                        <td class="fw-semibold text-primary pe-2 py-1"><?= number_format($tr['qty'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-muted py-3">Belum ada log.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>