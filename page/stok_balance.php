<?php
// stok_balance.php
// Master Live Balance Stok - All Areas

require_once __DIR__ . '/../config.php';

if (function_exists('check_login')) {
    check_login();
}

date_default_timezone_set('Asia/Jakarta');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Koneksi database gagal dimuat. Periksa kembali file config.php Anda.");
}

/* ==========================================================
   KONFIGURASI AREA
   ========================================================== */
$areaConfigs = [
    'PRESS' => [
        'title' => 'Press',
        'table' => 'stok_pp',
        'col'   => 'qty_press',
        'color' => 'primary',
        'icon'  => 'bi-gear-wide-connected'
    ],
    'PAINTING' => [
        'title' => 'Painting',
        'table' => 'stok_pp',
        'col'   => 'qty_paint',
        'color' => 'info',
        'icon'  => 'bi-paint-bucket'
    ],
    'INJECTION_AC' => [
        'title'       => 'Injection AC',
        'table'       => 'stok_injection',
        'col'         => 'qty_inj',
        'color'       => 'success',
        'icon'        => 'bi-box-seam',
        'filter_area' => 'AC'
    ],
    'INJECTION_WM' => [
        'title'       => 'Injection WM',
        'table'       => 'stok_injection',
        'col'         => 'qty_inj',
        'color'       => 'success',
        'icon'        => 'bi-box-seam',
        'filter_area' => 'WM'
    ],
    'HE' => [
        'title' => 'HE',
        'table' => 'stok_he',
        'col'   => 'qty_he',
        'color' => 'warning',
        'icon'  => 'bi-cpu'
    ],
    'PIPING' => [
        'title' => 'Piping',
        'table' => 'stok_piping',
        'col'   => 'qty_piping',
        'color' => 'danger',
        'icon'  => 'bi-diagram-3'
    ],
];

$allAreaData = [];

/* ==========================================================
   AMBIL DATA STOK
   ========================================================== */
try {
    foreach ($areaConfigs as $areaKey => $cfg) {
        $query = "
            SELECT
                part_code,
                part_name,
                {$cfg['col']} AS current_qty,
                updated_at
            FROM {$cfg['table']}
        ";

        $params = [];

        if (isset($cfg['filter_area'])) {
            $query .= " WHERE area = :area ";
            $params[':area'] = $cfg['filter_area'];
        }

        $query .= " ORDER BY part_name ASC ";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalQty = 0;

        foreach ($rows as $row) {
            $totalQty += (float)($row['current_qty'] ?? 0);
        }

        $lastUpdate = null;

        if (!empty($rows)) {
            $updateTimes = array_filter(array_column($rows, 'updated_at'));

            if (!empty($updateTimes)) {
                $lastUpdate = max($updateTimes);
            }
        }

        $allAreaData[$areaKey] = [
            'config'      => $cfg,
            'rows'        => $rows,
            'total_qty'   => $totalQty,
            'last_update' => $lastUpdate
        ];
    }
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
}

/* ==========================================================
   FORMAT LAST UPDATE
   ========================================================== */
function formatLastUpdate($datetime)
{
    if (empty($datetime)) {
        return '-';
    }

    $timestamp = strtotime($datetime);

    if ($timestamp === false) {
        return $datetime;
    }

    return date('d M Y H:i:s', $timestamp);
}

/* ==========================================================
   RENDER STOCK CARD
   ========================================================== */
function renderStockCard($areaKey, $allAreaData)
{
    if (!isset($allAreaData[$areaKey])) {
        return;
    }

    $data       = $allAreaData[$areaKey];
    $cfg        = $data['config'];
    $rows       = $data['rows'];
    $totalQty   = $data['total_qty'];
    $lastUpdate = $data['last_update'];
?>
    <div class="card stock-card bg-white border border-<?= htmlspecialchars($cfg['color']) ?> shadow-sm h-100">
        <div class="card-header bg-<?= htmlspecialchars($cfg['color']) ?> bg-opacity-10 border-bottom border-<?= htmlspecialchars($cfg['color']) ?> py-2 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-<?= htmlspecialchars($cfg['color']) ?>">
                <i class="bi <?= htmlspecialchars($cfg['icon']) ?> me-1"></i>
                Area <?= htmlspecialchars($cfg['title']) ?>
            </span>
            <span class="badge bg-<?= htmlspecialchars($cfg['color']) ?>">
                <?= count($rows) ?> Part
            </span>
        </div>

        <div class="stock-summary px-3 py-2 bg-light border-bottom">
            <div class="row align-items-center">
                <div class="col-6">
                    <small class="text-muted fw-semibold d-block">TOTAL STOK</small>
                    <span class="fs-5 fw-bold text-<?= htmlspecialchars($cfg['color']) ?>">
                        <?= number_format($totalQty) ?>
                        <small class="text-muted fs-6">pcs</small>
                    </span>
                </div>

                <div class="col-6 text-end">
                    <small class="text-muted fw-semibold d-block">
                        <i class="bi bi-clock-history me-1"></i> LAST UPDATE
                    </small>
                    <small class="fw-semibold text-dark">
                        <?= htmlspecialchars(formatLastUpdate($lastUpdate)) ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive stock-table-wrapper">
                <table class="table table-hover table-striped table-bordered table-sm align-middle mb-0 text-center">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th style="width:45px;">#</th>
                            <th style="width:130px;">Part Code</th>
                            <th class="text-start">Part Name</th>
                            <th style="width:80px;">Qty</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox me-1"></i>
                                    Tidak ada data.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; ?>

                            <?php foreach ($rows as $stk): ?>
                                <tr>
                                    <td><?= $no++ ?></td>

                                    <td class="fw-bold text-primary">
                                        <?= htmlspecialchars($stk['part_code'] ?? '-') ?>
                                    </td>

                                    <td class="text-start">
                                        <?= htmlspecialchars($stk['part_name'] ?? '-') ?>
                                    </td>

                                    <td class="fw-bold <?= ((float)($stk['current_qty'] ?? 0) < 0) ? 'text-danger' : 'text-dark' ?>">
                                        <?= number_format((float)($stk['current_qty'] ?? 0)) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php
}
?>

<!-- =========================================================
     MAIN CONTENT
     ========================================================= -->
<div class="container-fluid py-3 px-3 text-dark" style="font-size:0.85rem;">

    <!-- TOP HEADER -->
    <div class="stock-header mb-3">

        <!-- JUDUL -->
        <div class="stock-title">
            <h5 class="fw-bold text-secondary mb-0">Master Balance Stok Live</h5>
            <small class="text-muted">Overview Live Stok Fisik Seluruh Area Produksi</small>
        </div>

        <!-- NAVIGASI TENGAH -->
        <div class="stock-nav">
            <button type="button" data-bs-target="#stockCarousel" data-bs-slide-to="0" class="btn btn-sm stock-nav-btn active" aria-current="true">
                <i class="bi bi-grid me-1"></i> Press & Painting
            </button>

            <button type="button" data-bs-target="#stockCarousel" data-bs-slide-to="1" class="btn btn-sm stock-nav-btn">
                <i class="bi bi-box-seam me-1"></i> Injection
            </button>

            <button type="button" data-bs-target="#stockCarousel" data-bs-slide-to="2" class="btn btn-sm stock-nav-btn">
                <i class="bi bi-diagram-3 me-1"></i> HE & Piping
            </button>
        </div>

        <!-- TOMBOL KEMBALI -->
        <div class="stock-back">
            <a href="index.php?page=cmc" class="btn btn-sm btn-outline-secondary px-3 py-1 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i>
                Kembali ke Dashboard CMC
            </a>
        </div>

    </div>

    <!-- ERROR -->
    <?php if (isset($errorMsg)): ?>
        <div class="alert alert-danger py-2 mb-3">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- CAROUSEL -->
    <div id="stockCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000" data-bs-touch="true">

        <div class="carousel-inner">

            <!-- SLIDE 1 : PRESS + PAINTING -->
            <div class="carousel-item active">
                <div class="row justify-content-center g-3">
                    <div class="col-12 col-xl-6">
                        <?php renderStockCard('PRESS', $allAreaData); ?>
                    </div>

                    <div class="col-12 col-xl-6">
                        <?php renderStockCard('PAINTING', $allAreaData); ?>
                    </div>
                </div>
            </div>

            <!-- SLIDE 2 : INJECTION AC + WM -->
            <div class="carousel-item">
                <div class="row justify-content-center g-3">
                    <div class="col-12 col-xl-6">
                        <?php renderStockCard('INJECTION_AC', $allAreaData); ?>
                    </div>

                    <div class="col-12 col-xl-6">
                        <?php renderStockCard('INJECTION_WM', $allAreaData); ?>
                    </div>
                </div>
            </div>

            <!-- SLIDE 3 : HE + PIPING -->
            <div class="carousel-item">
                <div class="row justify-content-center g-3">
                    <div class="col-12 col-xl-6">
                        <?php renderStockCard('HE', $allAreaData); ?>
                    </div>

                    <div class="col-12 col-xl-6">
                        <?php renderStockCard('PIPING', $allAreaData); ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- =========================================================
     CUSTOM CSS
     ========================================================= -->
<style>
    .stock-header {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 20px;
    }

    .stock-title {
        justify-self: start;
    }

    .stock-nav {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        flex-wrap: wrap;
    }

    .stock-back {
        justify-self: end;
    }

    .stock-nav-btn {
        border: 1px solid #dee2e6;
        background: #f8f9fa;
        color: #6c757d;
        padding: 6px 14px;
        border-radius: 20px;
        white-space: nowrap;
        transition: background .2s ease, color .2s ease, border-color .2s ease, transform .2s ease;
    }

    .stock-nav-btn:hover {
        background: #e9ecef;
        color: #212529;
        border-color: #ced4da;
    }

    .stock-nav-btn.active {
        background: #212529;
        color: #fff;
        border-color: #212529;
    }

    .stock-nav-btn:active {
        transform: scale(.97);
    }

    .stock-card {
        border-radius: 10px;
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .stock-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .12) !important;
    }

    .stock-table-wrapper {
        max-height: 610px;
        overflow-y: auto;
    }

    .stock-table-wrapper table {
        font-size: .82rem;
    }

    .stock-table-wrapper th {
        white-space: nowrap;
    }

    .stock-table-wrapper td {
        vertical-align: middle;
    }

    .stock-table-wrapper thead {
        position: sticky;
        top: 0;
        z-index: 5;
    }

    #stockCarousel {
        padding: 0;
    }

    .carousel-item {
        min-height: 400px;
    }

    @media (max-width:991.98px) {
        .stock-header {
            grid-template-columns: 1fr;
            justify-items: center;
            text-align: center;
            gap: 10px;
        }

        .stock-title {
            justify-self: center;
        }

        .stock-nav {
            justify-self: center;
            order: 2;
        }

        .stock-back {
            justify-self: center;
            order: 3;
        }
    }

    @media (max-width:576px) {
        .stock-nav {
            width: 100%;
            gap: 5px;
        }

        .stock-nav-btn {
            font-size: .75rem;
            padding: 5px 9px;
        }

        .stock-nav-btn i {
            display: none;
        }

        .stock-summary {
            font-size: .8rem;
        }

        .stock-table-wrapper {
            max-height: 450px;
        }

        .stock-table-wrapper table {
            font-size: .75rem;
        }
    }
</style>

<!-- =========================================================
     CAROUSEL NAVIGATION SCRIPT
     ========================================================= -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const carousel = document.getElementById('stockCarousel');
        const navButtons = document.querySelectorAll('.stock-nav-btn');

        if (!carousel || !navButtons.length) {
            return;
        }

        carousel.addEventListener('slide.bs.carousel', function(event) {
            navButtons.forEach(function(button) {
                button.classList.remove('active');
                button.removeAttribute('aria-current');
            });

            const activeButton = document.querySelector(
                '.stock-nav-btn[data-bs-slide-to="' + event.to + '"]'
            );

            if (activeButton) {
                activeButton.classList.add('active');
                activeButton.setAttribute('aria-current', 'true');
            }
        });
    });
</script>