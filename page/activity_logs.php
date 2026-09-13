<?php
// activity_logs.php
// Activity Log - UPDATE & DELETE

require_once __DIR__ . '/../config.php';

if (function_exists('check_login')) {
    check_login();
}

date_default_timezone_set('Asia/Jakarta');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Koneksi database gagal dimuat. Periksa kembali file config.php Anda.");
}

/* ==========================================================
   FILTER
   ========================================================== */
$action = $_GET['action'] ?? 'ALL';

$allowedActions = ['ALL', 'UPDATE', 'DELETE'];

if (!in_array($action, $allowedActions, true)) {
    $action = 'ALL';
}

/* ==========================================================
   AMBIL DATA LOG
   ========================================================== */
try {
    $query = "
        SELECT
            id,
            user_id,
            action,
            description,
            target_table,
            part_code,
            old_data,
            new_data,
            created_at
        FROM activity_logs
    ";

    $params = [];

    if ($action !== 'ALL') {
        $query .= " WHERE action = :action ";
        $params[':action'] = $action;
    }

    $query .= " ORDER BY created_at DESC, id DESC ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    $logs = [];
}

/* ==========================================================
   FORMAT JSON
   ========================================================== */
function formatLogData($data)
{
    if (empty($data)) {
        return '-';
    }

    $decoded = json_decode($data, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return json_encode(
            $decoded,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
    }

    return $data;
}

/* ==========================================================
   ACTION BADGE
   ========================================================== */
function actionBadge($action)
{
    switch ($action) {
        case 'DELETE':
            return '<span class="badge bg-danger">DELETE</span>';

        case 'UPDATE':
            return '<span class="badge bg-warning text-dark">UPDATE</span>';

        case 'INSERT':
            return '<span class="badge bg-success">INSERT</span>';

        default:
            return '<span class="badge bg-secondary">' . htmlspecialchars($action) . '</span>';
    }
}
?>

<div class="container-fluid py-3 px-3 text-dark" style="font-size:0.85rem;">

    <!-- =====================================================
         HEADER
         ===================================================== -->
    <div class="log-header mb-3">

        <div>
            <h5 class="fw-bold text-secondary mb-0">
                <i class="bi bi-clock-history me-1"></i>
                Activity Log
            </h5>

            <small class="text-muted">
                Riwayat perubahan data UPDATE dan DELETE
            </small>
        </div>

        <div class="log-actions">

            <a href="index.php?page=cmc" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>
                Kembali ke Dashboard CMC
            </a>

        </div>

    </div>

    <!-- =====================================================
         ERROR
         ===================================================== -->
    <?php if (isset($errorMsg)): ?>

        <div class="alert alert-danger py-2">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <?= htmlspecialchars($errorMsg) ?>
        </div>

    <?php endif; ?>

    <!-- =====================================================
         FILTER
         ===================================================== -->
    <div class="card shadow-sm border-0 mb-3">

        <div class="card-body py-2">

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">

                <div class="d-flex align-items-center gap-2">

                    <span class="fw-semibold text-muted">
                        Filter:
                    </span>

                    <a href="?page=activity_logs&action=ALL"
                        class="btn btn-sm <?= $action === 'ALL' ? 'btn-dark' : 'btn-outline-dark' ?>">
                        Semua
                    </a>

                    <a href="?page=activity_logs&action=UPDATE"
                        class="btn btn-sm <?= $action === 'UPDATE' ? 'btn-warning' : 'btn-outline-warning' ?>">
                        <i class="bi bi-pencil-square me-1"></i>
                        UPDATE
                    </a>

                    <a href="?page=activity_logs&action=DELETE"
                        class="btn btn-sm <?= $action === 'DELETE' ? 'btn-danger' : 'btn-outline-danger' ?>">
                        <i class="bi bi-trash me-1"></i>
                        DELETE
                    </a>

                </div>

                <div class="text-muted">
                    Total:
                    <strong><?= count($logs) ?></strong>
                    log
                </div>

            </div>

        </div>

    </div>

    <!-- =====================================================
         TABLE LOG
         ===================================================== -->
    <div class="card shadow-sm border-0">

        <div class="card-body p-0">

            <div class="table-responsive log-table-wrapper">

                <table class="table table-hover table-striped table-bordered table-sm align-middle mb-0">

                    <thead class="table-dark sticky-top">
                        <tr>
                            <th style="width:55px;" class="text-center">#</th>
                            <th style="width:160px;">Tanggal</th>
                            <th style="width:90px;" class="text-center">Action</th>
                            <th style="width:90px;">User ID</th>
                            <th style="width:150px;">Part Code</th>
                            <th style="width:180px;">Target Table</th>
                            <th>Description</th>
                            <th style="width:280px;">Old Data</th>
                            <th style="width:280px;">New Data</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($logs)): ?>

                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Tidak ada activity log.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php $no = 1; ?>

                            <?php foreach ($logs as $log): ?>

                                <tr>

                                    <td class="text-center">
                                        <?= $no++ ?>
                                    </td>

                                    <td>
                                        <small class="fw-semibold">
                                            <?= htmlspecialchars($log['created_at']) ?>
                                        </small>
                                    </td>

                                    <td class="text-center">
                                        <?= actionBadge($log['action']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($log['user_id']) ?>
                                    </td>

                                    <td>
                                        <span class="fw-bold text-primary">
                                            <?= htmlspecialchars($log['part_code'] ?? '-') ?>
                                        </span>
                                    </td>

                                    <td>
                                        <small>
                                            <?= htmlspecialchars($log['target_table'] ?? '-') ?>
                                        </small>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($log['description'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($log['old_data'])): ?>
                                            <pre class="log-json"><?= htmlspecialchars(formatLogData($log['old_data'])) ?></pre>
                                        <?php else: ?>
                                            <span class="text-muted">NULL</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($log['new_data'])): ?>
                                            <pre class="log-json"><?= htmlspecialchars(formatLogData($log['new_data'])) ?></pre>
                                        <?php else: ?>
                                            <span class="text-muted">NULL</span>
                                        <?php endif; ?>
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

<style>
    .log-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }

    .log-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .log-table-wrapper {
        max-height: calc(100vh - 230px);
        overflow: auto;
    }

    .log-table-wrapper thead {
        position: sticky;
        top: 0;
        z-index: 5;
    }

    .log-table-wrapper table {
        font-size: .82rem;
    }

    .log-table-wrapper th {
        white-space: nowrap;
        vertical-align: middle;
    }

    .log-table-wrapper td {
        vertical-align: middle;
    }

    .log-json {
        margin: 0;
        padding: 8px;
        min-width: 250px;
        max-width: 350px;
        max-height: 150px;
        overflow: auto;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        font-size: .75rem;
        white-space: pre-wrap;
        word-break: break-word;
    }

    @media (max-width:768px) {
        .log-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .log-table-wrapper {
            max-height: calc(100vh - 300px);
        }
    }
</style>