<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['warehouse', 'production'])) {
    echo "<div class='alert alert-danger'>Akses ditolak. Halaman khusus Warehouse & Production.</div>";
    exit;
}

date_default_timezone_set('Asia/Jakarta');

// Logika tanggal & shift produksi
if (!function_exists('getProductionDateOnly')) {
    function getProductionDateOnly($datetime)
    {
        $time = date('H:i', strtotime($datetime));
        $date = date('Y-m-d', strtotime($datetime));
        return ($time < '09:00') ? date('Y-m-d', strtotime($date . ' -1 day')) : $date;
    }
}

$now            = date('Y-m-d H:i:s');
$currentDate    = getProductionDateOnly($now);
$activeCategory = $_GET['category'] ?? 'injection';

// ==========================================
// 1. QUERY LIVE MONITORING PARTS PER SHIFT
// ==========================================

// --- A. PAINTING PARTS ---
$paintingParts = [];
$qPpLive = mysqli_query($conn, "
    SELECT 
        CASE 
            WHEN t.part_code LIKE 'CCHS-B829%' THEN 'CCHS-B829JBTA'
            WHEN t.part_code LIKE 'GCAB-A646%' THEN 'GCAB-A646JBTA'
            WHEN t.part_code LIKE 'GCAB-A767%' THEN 'GCAB-A767JBTA'
            WHEN t.part_code LIKE 'PPLT-B282%' THEN 'PPLT-B282JBTA'
            ELSE TRIM(t.part_code)
        END AS part_code_custom,
        CASE 
            WHEN t.part_code LIKE 'CCHS-B829%' THEN 'Base Pan'
            WHEN t.part_code LIKE 'GCAB-A646%' THEN 'Top Table'
            WHEN t.part_code LIKE 'GCAB-A767%' THEN 'Front Panel'
            WHEN t.part_code LIKE 'PPLT-B282%' THEN 'Side Cover R'
            ELSE COALESCE(p.part_name, '-')
        END AS part_name_custom,
        SUM(CASE WHEN t.shift = 1 THEN t.qty ELSE 0 END) AS s1,
        SUM(CASE WHEN t.shift = 2 THEN t.qty ELSE 0 END) AS s2,
        SUM(CASE WHEN t.shift = 3 THEN t.qty ELSE 0 END) AS s3
    FROM `seid_ac_pp`.`transaction` t
    LEFT JOIN `seid_ac_pp`.`part` p ON t.part_code = p.part_code
    WHERE (
        CASE 
            WHEN TIME(t.date_tr) < '09:00:00' THEN DATE(DATE_SUB(t.date_tr, INTERVAL 1 DAY))
            ELSE DATE(t.date_tr)
        END
    ) = '$currentDate' AND t.status = 'ASSY'
    GROUP BY part_code_custom, part_name_custom
    ORDER BY part_code_custom ASC
");
if ($qPpLive) {
    while ($row = mysqli_fetch_assoc($qPpLive)) {
        $paintingParts[] = [
            'part_code' => $row['part_code_custom'],
            'part_name' => $row['part_name_custom'],
            's1'        => $row['s1'],
            's2'        => $row['s2'],
            's3'        => $row['s3']
        ];
    }
}

// --- B. PRESS PARTS ---
$pressParts = [];
$qPressLive = mysqli_query($conn, "
    SELECT 
        CASE 
            WHEN t.part_code LIKE 'GCAB-A646%' THEN 'GCAB-A646JBPZ'
            WHEN t.part_code LIKE 'GCAB-A767%' THEN 'GCAB-A767JBPZ'
            WHEN t.part_code LIKE 'LCHS-A800%' OR t.part_code LIKE 'CCHS-B829%' THEN 'LCHS-A800JBPZ'
            WHEN t.part_code LIKE 'PPLT-B282%' THEN 'PPLT-B282JBPZ'
            ELSE TRIM(t.part_code)
        END AS part_code_custom,
        CASE 
            WHEN t.part_code LIKE 'GCAB-A646%' THEN 'Top Table'
            WHEN t.part_code LIKE 'GCAB-A767%' THEN 'Front Panel'
            WHEN t.part_code LIKE 'LCHS-A800%' OR t.part_code LIKE 'CCHS-B829%' THEN 'Base Pan'
            WHEN t.part_code LIKE 'PPLT-B282%' THEN 'Side Cover R'
            ELSE COALESCE(p.part_name, '-')
        END AS part_name_custom,
        SUM(CASE WHEN t.shift = 1 THEN t.qty ELSE 0 END) AS s1,
        SUM(CASE WHEN t.shift = 2 THEN t.qty ELSE 0 END) AS s2,
        SUM(CASE WHEN t.shift = 3 THEN t.qty ELSE 0 END) AS s3
    FROM `seid_ac_pp`.`transaction` t
    LEFT JOIN `seid_ac_pp`.`part` p ON t.part_code = p.part_code
    WHERE (
        CASE 
            WHEN TIME(t.date_tr) < '09:00:00' THEN DATE(DATE_SUB(t.date_tr, INTERVAL 1 DAY))
            ELSE DATE(t.date_tr)
        END
    ) = '$currentDate' AND t.status = 'PAINT'
    GROUP BY part_code_custom, part_name_custom
    ORDER BY part_code_custom ASC
");
if ($qPressLive) {
    while ($row = mysqli_fetch_assoc($qPressLive)) {
        $pressParts[] = [
            'part_code' => $row['part_code_custom'],
            'part_name' => $row['part_name_custom'],
            's1'        => $row['s1'],
            's2'        => $row['s2'],
            's3'        => $row['s3']
        ];
    }
}

// --- C. INJECTION PARTS ---
$injectionParts = [];
$qInjLive = mysqli_query($conn, "
    SELECT 
        t.part_code, 
        p.part_name,
        SUM(CASE WHEN t.shift = 1 THEN t.qty ELSE 0 END) AS s1,
        SUM(CASE WHEN t.shift = 2 THEN t.qty ELSE 0 END) AS s2,
        SUM(CASE WHEN t.shift = 3 THEN t.qty ELSE 0 END) AS s3
    FROM `seid_ac_inj`.`transaction` t
    LEFT JOIN `seid_ac_inj`.`part` p ON t.part_code = p.part_code
    WHERE (
        CASE 
            WHEN TIME(t.date_tr) < '09:00:00' THEN DATE(DATE_SUB(t.date_tr, INTERVAL 1 DAY))
            ELSE DATE(t.date_tr)
        END
    ) = '$currentDate' AND t.status = 'ASSY'
    GROUP BY t.part_code, p.part_name
    ORDER BY t.part_code ASC
");
if ($qInjLive) {
    while ($row = mysqli_fetch_assoc($qInjLive)) {
        $injectionParts[] = $row;
    }
}

// --- D. HE PARTS ---
$heParts = [];
$qHeLive = mysqli_query($conn, "
    SELECT 
        CASE 
            WHEN t.coupon LIKE 'CSR%' THEN 'DCON-B070JBEZ'
            WHEN t.coupon LIKE 'CDR%' THEN 'DCON-B105JBEZ'
            WHEN t.coupon LIKE 'SRI%' THEN 'DCON-B074JBEZ'
            WHEN t.coupon LIKE 'DRI%' THEN 'DCON-B075JBEZ'
            WHEN t.coupon LIKE 'EVA%' THEN 'PEVA-B161JBPZ'
            ELSE TRIM(t.coupon)
        END AS part_code,
        CASE 
            WHEN t.coupon LIKE 'CSR%' THEN 'Condenser SR Normal'
            WHEN t.coupon LIKE 'CDR%' THEN 'Condenser DR Normal'
            WHEN t.coupon LIKE 'SRI%' THEN 'Condenser SR Inverter'
            WHEN t.coupon LIKE 'DRI%' THEN 'Condenser DR Inverter'
            WHEN t.coupon LIKE 'EVA%' THEN 'Evaporator'
            ELSE '-'
        END AS part_name,
        SUM(CASE WHEN t.shift = 1 THEN t.qty ELSE 0 END) AS s1,
        SUM(CASE WHEN t.shift = 2 THEN t.qty ELSE 0 END) AS s2,
        SUM(CASE WHEN t.shift = 3 THEN t.qty ELSE 0 END) AS s3
    FROM `seid_ac_hepi`.`history_lot` t
    WHERE (
        CASE 
            WHEN TIME(t.tgl_lot) < '09:00:00' THEN DATE(DATE_SUB(t.tgl_lot, INTERVAL 1 DAY))
            ELSE DATE(t.tgl_lot)
        END
    ) = '$currentDate' AND t.status = 'OUT'
    GROUP BY part_code, part_name
    ORDER BY part_code ASC
");
if ($qHeLive) {
    while ($row = mysqli_fetch_assoc($qHeLive)) {
        $heParts[] = $row;
    }
}

// --- E. PIPING PARTS ---
$pipingParts = [];
$qPipLive = mysqli_query($conn, "
    SELECT 
        CASE 
            WHEN t.coupon LIKE 'ALL-IDU%' THEN 'CPIPCC533JBKZ'
            WHEN t.coupon LIKE 'SUC-5K2%' THEN 'CPIPCC616JBKZ'
            WHEN t.coupon LIKE 'SUC-9K2%' THEN 'CPIPCC625JBKZ'
            WHEN t.coupon LIKE 'SUC-IVT%' THEN 'CPIPCC571JBKZ'
            WHEN t.coupon LIKE 'SUC-13K%' THEN 'CPIPCC624JBKZ'
            WHEN t.coupon LIKE 'SUC-9CY%' THEN 'CPIPCK426JBKZ'
            WHEN t.coupon LIKE 'DIS-5K2%' THEN 'CCYC-F289JBKZ'
            WHEN t.coupon LIKE 'DIS-7K1%' THEN 'CCYC-F091JBKZ'
            WHEN t.coupon LIKE 'DIS-9K2%' THEN 'CCYC-F255JBKZ'
            WHEN t.coupon LIKE 'DIS-68K%' THEN 'CPIPCC572JBKZ'
            WHEN t.coupon LIKE 'DIS-10K%' THEN 'CPIPCC561JBKZ'
            WHEN t.coupon LIKE 'DIS-13K%' THEN 'CPIPCC623JBKZ'
            WHEN t.coupon LIKE 'DIS-9CY%' THEN 'CCYC-F311JBKZ'
            WHEN t.coupon LIKE 'CAP-5K2%' THEN 'CCPY-A455JBKZ'
            WHEN t.coupon LIKE 'CAP-7K1%' THEN 'CCPY-A456JBKZ'
            WHEN t.coupon LIKE 'CAP-9K2%' THEN 'CCPY-A457JBKZ'
            WHEN t.coupon LIKE 'CAP-68K%' THEN 'CCPY-A458JBKZ'
            WHEN t.coupon LIKE 'CAP-10K%' THEN 'CCPY-A459JBKZ'
            WHEN t.coupon LIKE 'CAP-13K%' THEN 'CCPY-A516JBKZ'
            WHEN t.coupon LIKE 'CAP-9CY%' THEN 'PCPY-C007JB1Z'
            ELSE TRIM(t.coupon)
        END AS part_code,
        CASE 
            WHEN t.coupon LIKE 'ALL-IDU%' THEN 'TUBE ASSY'
            WHEN t.coupon LIKE 'SUC-5K2%' THEN 'SUCTION 7K/ 5K-2'
            WHEN t.coupon LIKE 'SUC-9K2%' THEN 'SUCTION 9K2'
            WHEN t.coupon LIKE 'SUC-IVT%' THEN 'SUCTION 6K/8K/10K'
            WHEN t.coupon LIKE 'SUC-13K%' THEN 'SUCTION MUFFLER 13K TUBE 1'
            WHEN t.coupon LIKE 'SUC-9CY%' THEN 'SUCTION 9CAY'
            WHEN t.coupon LIKE 'DIS-5K2%' THEN 'DISCHARGE 5K2'
            WHEN t.coupon LIKE 'DIS-7K1%' THEN 'DISCHARGE 7K'
            WHEN t.coupon LIKE 'DIS-9K2%' THEN 'DISCHARGE 9K2'
            WHEN t.coupon LIKE 'DIS-68K%' THEN 'DISCHARGE 6K & 8K'
            WHEN t.coupon LIKE 'DIS-10K%' THEN 'DISCHARGE 10K'
            WHEN t.coupon LIKE 'DIS-13K%' THEN 'DISCHARGE 13K'
            WHEN t.coupon LIKE 'DIS-9CY%' THEN 'DISCHARGE 9CAY'
            WHEN t.coupon LIKE 'CAP-5K2%' THEN 'CAPILARY 5K'
            WHEN t.coupon LIKE 'CAP-7K1%' THEN 'CAPILARY 7K'
            WHEN t.coupon LIKE 'CAP-9K2%' THEN 'CAPILARY 9K'
            WHEN t.coupon LIKE 'CAP-68K%' THEN 'CAPILARY X6/X8'
            WHEN t.coupon LIKE 'CAP-10K%' THEN 'CAPILARY X10'
            WHEN t.coupon LIKE 'CAP-13K%' THEN 'CAPILARY X13'
            WHEN t.coupon LIKE 'CAP-9CY%' THEN 'CAPILARY 9CAY'
            ELSE '-'
        END AS part_name,
        SUM(CASE WHEN t.shift = 1 THEN t.qty ELSE 0 END) AS s1,
        SUM(CASE WHEN t.shift = 2 THEN t.qty ELSE 0 END) AS s2,
        SUM(CASE WHEN t.shift = 3 THEN t.qty ELSE 0 END) AS s3
    FROM `seid_ac_hepi`.`piping_history_lot` t
    WHERE (
        CASE 
            WHEN TIME(t.tgl_lot) < '09:00:00' THEN DATE(DATE_SUB(t.tgl_lot, INTERVAL 1 DAY))
            ELSE DATE(t.tgl_lot)
        END
    ) = '$currentDate' AND t.status = 'OUT'
    GROUP BY part_code, part_name
    ORDER BY part_code ASC
");
if ($qPipLive) {
    while ($row = mysqli_fetch_assoc($qPipLive)) {
        $pipingParts[] = $row;
    }
}

// ==========================================
// 2. QUERY DETAIL HISTORY TABEL BAWAH
// ==========================================
$filterDate = $_GET['filter_date'] ?? '';

switch ($activeCategory) {
    case 'painting':
        $where = "WHERE t.status = 'ASSY'";
        if (!empty($filterDate)) $where .= " AND DATE(t.date_tr) = '$filterDate'";
        $queryDetail = "
            SELECT DATE(t.date_tr) AS tgl,
                CASE 
                    WHEN t.part_code LIKE 'CCHS-B829%' THEN 'CCHS-B829JBTA'
                    WHEN t.part_code LIKE 'GCAB-A646%' THEN 'GCAB-A646JBTA'
                    WHEN t.part_code LIKE 'GCAB-A767%' THEN 'GCAB-A767JBTA'
                    WHEN t.part_code LIKE 'PPLT-B282%' THEN 'PPLT-B282JBTA'
                    ELSE TRIM(t.part_code)
                END AS part_code,
                CASE 
                    WHEN t.part_code LIKE 'CCHS-B829%' THEN 'Base Pan'
                    WHEN t.part_code LIKE 'GCAB-A646%' THEN 'Top Table'
                    WHEN t.part_code LIKE 'GCAB-A767%' THEN 'Front Panel'
                    WHEN t.part_code LIKE 'PPLT-B282%' THEN 'Side Cover R'
                    ELSE COALESCE(p.part_name, '-')
                END AS part_name,
                SUM(CASE WHEN t.shift = 1 THEN t.qty ELSE 0 END) AS s1,
                SUM(CASE WHEN t.shift = 2 THEN t.qty ELSE 0 END) AS s2,
                SUM(CASE WHEN t.shift = 3 THEN t.qty ELSE 0 END) AS s3,
                SUM(t.qty) AS total
            FROM `seid_ac_pp`.`transaction` t
            LEFT JOIN `seid_ac_pp`.`part` p ON t.part_code = p.part_code
            $where
            GROUP BY DATE(t.date_tr), part_code, part_name
            ORDER BY DATE(t.date_tr) DESC, part_code ASC";
        break;

    case 'press':
        $where = "WHERE t.status = 'PAINT'";
        if (!empty($filterDate)) $where .= " AND DATE(t.date_tr) = '$filterDate'";
        $queryDetail = "
            SELECT DATE(t.date_tr) AS tgl,
                CASE 
                    WHEN t.part_code LIKE 'GCAB-A646%' THEN 'GCAB-A646JBPZ'
                    WHEN t.part_code LIKE 'GCAB-A767%' THEN 'GCAB-A767JBPZ'
                    WHEN t.part_code LIKE 'LCHS-A800%' OR t.part_code LIKE 'CCHS-B829%' THEN 'LCHS-A800JBPZ'
                    WHEN t.part_code LIKE 'PPLT-B282%' THEN 'PPLT-B282JBPZ'
                    ELSE TRIM(t.part_code)
                END AS part_code,
                CASE 
                    WHEN t.part_code LIKE 'GCAB-A646%' THEN 'Top Table'
                    WHEN t.part_code LIKE 'GCAB-A767%' THEN 'Front Panel'
                    WHEN t.part_code LIKE 'LCHS-A800%' OR t.part_code LIKE 'CCHS-B829%' THEN 'Base Pan'
                    WHEN t.part_code LIKE 'PPLT-B282%' THEN 'Side Cover R'
                    ELSE COALESCE(p.part_name, '-')
                END AS part_name,
                SUM(CASE WHEN t.shift = 1 THEN t.qty ELSE 0 END) AS s1,
                SUM(CASE WHEN t.shift = 2 THEN t.qty ELSE 0 END) AS s2,
                SUM(CASE WHEN t.shift = 3 THEN t.qty ELSE 0 END) AS s3,
                SUM(t.qty) AS total
            FROM `seid_ac_pp`.`transaction` t
            LEFT JOIN `seid_ac_pp`.`part` p ON t.part_code = p.part_code
            $where
            GROUP BY DATE(t.date_tr), part_code, part_name
            ORDER BY DATE(t.date_tr) DESC, part_code ASC";
        break;

    case 'he':
        $where = "WHERE t.status = 'OUT'";
        if (!empty($filterDate)) $where .= " AND DATE(t.tgl_lot) = '$filterDate'";
        $queryDetail = "
            SELECT DATE(t.tgl_lot) AS tgl,
                CASE 
                    WHEN t.coupon LIKE 'CSR%' THEN 'DCON-B070JBEZ'
                    WHEN t.coupon LIKE 'CDR%' THEN 'DCON-B105JBEZ'
                    WHEN t.coupon LIKE 'SRI%' THEN 'DCON-B074JBEZ'
                    WHEN t.coupon LIKE 'DRI%' THEN 'DCON-B075JBEZ'
                    WHEN t.coupon LIKE 'EVA%' THEN 'PEVA-B161JBPZ'
                    ELSE TRIM(t.coupon)
                END AS part_code,
                CASE 
                    WHEN t.coupon LIKE 'CSR%' THEN 'Condenser SR Normal'
                    WHEN t.coupon LIKE 'CDR%' THEN 'Condenser DR Normal'
                    WHEN t.coupon LIKE 'SRI%' THEN 'Condenser SR Inverter'
                    WHEN t.coupon LIKE 'DRI%' THEN 'Condenser DR Inverter'
                    WHEN t.coupon LIKE 'EVA%' THEN 'Evaporator'
                    ELSE '-'
                END AS part_name,
                SUM(CASE WHEN t.shift = 1 THEN t.qty ELSE 0 END) AS s1,
                SUM(CASE WHEN t.shift = 2 THEN t.qty ELSE 0 END) AS s2,
                SUM(CASE WHEN t.shift = 3 THEN t.qty ELSE 0 END) AS s3,
                SUM(t.qty) AS total
            FROM `seid_ac_hepi`.`history_lot` t
            $where
            GROUP BY DATE(t.tgl_lot), part_code, part_name
            ORDER BY DATE(t.tgl_lot) DESC, part_code ASC";
        break;

    case 'piping':
        $where = "WHERE t.status = 'OUT'";
        if (!empty($filterDate)) $where .= " AND DATE(t.tgl_lot) = '$filterDate'";
        $queryDetail = "
            SELECT DATE(t.tgl_lot) AS tgl,
                CASE 
                    WHEN t.coupon LIKE 'ALL-IDU%' THEN 'CPIPCC533JBKZ'
                    WHEN t.coupon LIKE 'SUC-5K2%' THEN 'CPIPCC616JBKZ'
                    WHEN t.coupon LIKE 'SUC-9K2%' THEN 'CPIPCC625JBKZ'
                    WHEN t.coupon LIKE 'SUC-IVT%' THEN 'CPIPCC571JBKZ'
                    WHEN t.coupon LIKE 'SUC-13K%' THEN 'CPIPCC624JBKZ'
                    WHEN t.coupon LIKE 'SUC-9CY%' THEN 'CPIPCK426JBKZ'
                    WHEN t.coupon LIKE 'DIS-5K2%' THEN 'CCYC-F289JBKZ'
                    WHEN t.coupon LIKE 'DIS-7K1%' THEN 'CCYC-F091JBKZ'
                    WHEN t.coupon LIKE 'DIS-9K2%' THEN 'CCYC-F255JBKZ'
                    WHEN t.coupon LIKE 'DIS-68K%' THEN 'CPIPCC572JBKZ'
                    WHEN t.coupon LIKE 'DIS-10K%' THEN 'CPIPCC561JBKZ'
                    WHEN t.coupon LIKE 'DIS-13K%' THEN 'CPIPCC623JBKZ'
                    WHEN t.coupon LIKE 'DIS-9CY%' THEN 'CCYC-F311JBKZ'
                    WHEN t.coupon LIKE 'CAP-5K2%' THEN 'CCPY-A455JBKZ'
                    WHEN t.coupon LIKE 'CAP-7K1%' THEN 'CCPY-A456JBKZ'
                    WHEN t.coupon LIKE 'CAP-9K2%' THEN 'CCPY-A457JBKZ'
                    WHEN t.coupon LIKE 'CAP-68K%' THEN 'CCPY-A458JBKZ'
                    WHEN t.coupon LIKE 'CAP-10K%' THEN 'CCPY-A459JBKZ'
                    WHEN t.coupon LIKE 'CAP-13K%' THEN 'CCPY-A516JBKZ'
                    WHEN t.coupon LIKE 'CAP-9CY%' THEN 'PCPY-C007JB1Z'
                    ELSE TRIM(t.coupon)
                END AS part_code,
                CASE 
                    WHEN t.coupon LIKE 'ALL-IDU%' THEN 'TUBE ASSY'
                    WHEN t.coupon LIKE 'SUC-5K2%' THEN 'SUCTION 7K/ 5K-2'
                    WHEN t.coupon LIKE 'SUC-9K2%' THEN 'SUCTION 9K2'
                    WHEN t.coupon LIKE 'SUC-IVT%' THEN 'SUCTION 6K/8K/10K'
                    WHEN t.coupon LIKE 'SUC-13K%' THEN 'SUCTION MUFFLER 13K TUBE 1'
                    WHEN t.coupon LIKE 'SUC-9CY%' THEN 'SUCTION 9CAY'
                    WHEN t.coupon LIKE 'DIS-5K2%' THEN 'DISCHARGE 5K2'
                    WHEN t.coupon LIKE 'DIS-7K1%' THEN 'DISCHARGE 7K'
                    WHEN t.coupon LIKE 'DIS-9K2%' THEN 'DISCHARGE 9K2'
                    WHEN t.coupon LIKE 'DIS-68K%' THEN 'DISCHARGE 6K & 8K'
                    WHEN t.coupon LIKE 'DIS-10K%' THEN 'DISCHARGE 10K'
                    WHEN t.coupon LIKE 'DIS-13K%' THEN 'DISCHARGE 13K'
                    WHEN t.coupon LIKE 'DIS-9CY%' THEN 'DISCHARGE 9CAY'
                    WHEN t.coupon LIKE 'CAP-5K2%' THEN 'CAPILARY 5K'
                    WHEN t.coupon LIKE 'CAP-7K1%' THEN 'CAPILARY 7K'
                    WHEN t.coupon LIKE 'CAP-9K2%' THEN 'CAPILARY 9K'
                    WHEN t.coupon LIKE 'CAP-68K%' THEN 'CAPILARY X6/X8'
                    WHEN t.coupon LIKE 'CAP-10K%' THEN 'CAPILARY X10'
                    WHEN t.coupon LIKE 'CAP-13K%' THEN 'CAPILARY X13'
                    WHEN t.coupon LIKE 'CAP-9CY%' THEN 'CAPILARY 9CAY'
                    ELSE '-'
                END AS part_name,
                SUM(CASE WHEN t.shift = 1 THEN t.qty ELSE 0 END) AS s1,
                SUM(CASE WHEN t.shift = 2 THEN t.qty ELSE 0 END) AS s2,
                SUM(CASE WHEN t.shift = 3 THEN t.qty ELSE 0 END) AS s3,
                SUM(t.qty) AS total
            FROM `seid_ac_hepi`.`piping_history_lot` t
            $where
            GROUP BY DATE(t.tgl_lot), part_code, part_name
            ORDER BY DATE(t.tgl_lot) DESC, part_code ASC";
        break;

    case 'injection':
    default:
        $where = "WHERE t.status = 'ASSY'";
        if (!empty($filterDate)) $where .= " AND DATE(t.date_tr) = '$filterDate'";
        $queryDetail = "
            SELECT DATE(t.date_tr) AS tgl, t.part_code, p.part_name,
                   SUM(CASE WHEN t.shift = 1 THEN t.qty ELSE 0 END) AS s1,
                   SUM(CASE WHEN t.shift = 2 THEN t.qty ELSE 0 END) AS s2,
                   SUM(CASE WHEN t.shift = 3 THEN t.qty ELSE 0 END) AS s3,
                   SUM(t.qty) AS total
            FROM `seid_ac_inj`.`transaction` t
            LEFT JOIN `seid_ac_inj`.`part` p ON t.part_code = p.part_code
            $where
            GROUP BY DATE(t.date_tr), t.part_code, p.part_name
            ORDER BY DATE(t.date_tr) DESC, t.part_code ASC";
        break;
}

$resDetail = mysqli_query($conn, $queryDetail);

$groupedHistory = [];
if ($resDetail && mysqli_num_rows($resDetail) > 0) {
    while ($row = mysqli_fetch_assoc($resDetail)) {
        $groupedHistory[$row['tgl']][] = $row;
    }
}

$modulesNav = [
    'press'     => 'Press',
    'painting'  => 'Painting',
    'injection' => 'Injection',
    'he'        => 'HE',
    'piping'    => 'Piping'
];
?>

<style>
    .card-process {
        border: none;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
        overflow: hidden;
    }

    .card-header-custom {
        color: #ffffff;
        font-weight: bold;
        font-size: 14px;
        padding: 8px 12px;
    }

    .bg-painting {
        background-color: #0066ff;
    }

    .bg-press {
        background-color: #fd7e14;
    }

    .bg-injection {
        background-color: #00c8e6;
    }

    .bg-he {
        background-color: #ffc107;
        color: #000 !important;
    }

    .bg-piping {
        background-color: #6c757d;
    }

    .table-parts {
        margin-bottom: 0;
        font-size: 12px;
    }

    .table-parts th {
        background-color: #f8f9fa;
        text-align: center;
        border-bottom: 2px solid #dee2e6;
        padding: 4px 8px;
    }

    .table-parts td {
        vertical-align: middle;
        border-color: #edf2f7;
        padding: 4px 8px;
    }

    .part-code-text {
        font-weight: bold;
        color: #000;
        display: block;
    }

    .part-name-text {
        font-size: 10px;
        color: #6c757d;
        display: block;
    }

    .col-shift {
        width: 35px;
        text-align: center;
    }

    .scrollable-card-body {
        max-height: 380px;
        overflow-y: auto;
    }
</style>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0 fw-bold"><i class="bi bi-speedometer2"></i> Live Summary Parts Today</h5>
        <span class="badge bg-dark fs-6">Tanggal Produksi: <?= date('d M Y', strtotime($currentDate)) ?></span>
    </div>

    <!-- 1. LIVE SUMMARY HARI INI (5 CARD PARALLEL) -->
    <div class="row g-2 mb-4">

        <!-- Painting Parts Card -->
        <div class="col-12 col-md-2" style="flex: 0 0 auto; width: 20%;">
            <div class="card card-process h-100">
                <div class="card-header-custom bg-painting">Painting Parts</div>
                <div class="card-body p-0 scrollable-card-body">
                    <table class="table table-bordered table-hover table-parts">
                        <thead>
                            <tr>
                                <th>Part</th>
                                <th class="col-shift">S1</th>
                                <th class="col-shift">S2</th>
                                <th class="col-shift">S3</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($paintingParts)): ?>
                                <?php foreach ($paintingParts as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="part-code-text"><?= htmlspecialchars($item['part_code']) ?></span>
                                            <span class="part-name-text"><?= htmlspecialchars($item['part_name'] ?? '-') ?></span>
                                        </td>
                                        <td class="col-shift"><?= $item['s1'] ?></td>
                                        <td class="col-shift"><?= $item['s2'] ?></td>
                                        <td class="col-shift"><?= $item['s3'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Tidak ada data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Press Parts Card -->
        <div class="col-12 col-md-2" style="flex: 0 0 auto; width: 20%;">
            <div class="card card-process h-100">
                <div class="card-header-custom bg-press">Press Parts</div>
                <div class="card-body p-0 scrollable-card-body">
                    <table class="table table-bordered table-hover table-parts">
                        <thead>
                            <tr>
                                <th>Part</th>
                                <th class="col-shift">S1</th>
                                <th class="col-shift">S2</th>
                                <th class="col-shift">S3</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pressParts)): ?>
                                <?php foreach ($pressParts as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="part-code-text"><?= htmlspecialchars($item['part_code']) ?></span>
                                            <span class="part-name-text"><?= htmlspecialchars($item['part_name'] ?? '-') ?></span>
                                        </td>
                                        <td class="col-shift"><?= $item['s1'] ?></td>
                                        <td class="col-shift"><?= $item['s2'] ?></td>
                                        <td class="col-shift"><?= $item['s3'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Tidak ada data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Injection Parts Card -->
        <div class="col-12 col-md-2" style="flex: 0 0 auto; width: 20%;">
            <div class="card card-process h-100">
                <div class="card-header-custom bg-injection">Injection Parts</div>
                <div class="card-body p-0 scrollable-card-body">
                    <table class="table table-bordered table-hover table-parts">
                        <thead>
                            <tr>
                                <th>Part</th>
                                <th class="col-shift">S1</th>
                                <th class="col-shift">S2</th>
                                <th class="col-shift">S3</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($injectionParts)): ?>
                                <?php foreach ($injectionParts as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="part-code-text"><?= htmlspecialchars($item['part_code']) ?></span>
                                            <span class="part-name-text"><?= htmlspecialchars($item['part_name'] ?? '-') ?></span>
                                        </td>
                                        <td class="col-shift"><?= $item['s1'] ?></td>
                                        <td class="col-shift"><?= $item['s2'] ?></td>
                                        <td class="col-shift"><?= $item['s3'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Tidak ada data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- HE Parts Card -->
        <div class="col-12 col-md-2" style="flex: 0 0 auto; width: 20%;">
            <div class="card card-process h-100">
                <div class="card-header-custom bg-he">HE Parts</div>
                <div class="card-body p-0 scrollable-card-body">
                    <table class="table table-bordered table-hover table-parts">
                        <thead>
                            <tr>
                                <th>Part</th>
                                <th class="col-shift">S1</th>
                                <th class="col-shift">S2</th>
                                <th class="col-shift">S3</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($heParts)): ?>
                                <?php foreach ($heParts as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="part-code-text"><?= htmlspecialchars($item['part_code']) ?></span>
                                            <span class="part-name-text"><?= htmlspecialchars($item['part_name'] ?? '-') ?></span>
                                        </td>
                                        <td class="col-shift"><?= $item['s1'] ?></td>
                                        <td class="col-shift"><?= $item['s2'] ?></td>
                                        <td class="col-shift"><?= $item['s3'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Tidak ada data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Piping Parts Card -->
        <div class="col-12 col-md-2" style="flex: 0 0 auto; width: 20%;">
            <div class="card card-process h-100">
                <div class="card-header-custom bg-piping">Piping Parts</div>
                <div class="card-body p-0 scrollable-card-body">
                    <table class="table table-bordered table-hover table-parts">
                        <thead>
                            <tr>
                                <th>Part</th>
                                <th class="col-shift">S1</th>
                                <th class="col-shift">S2</th>
                                <th class="col-shift">S3</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pipingParts)): ?>
                                <?php foreach ($pipingParts as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="part-code-text"><?= htmlspecialchars($item['part_code']) ?></span>
                                            <span class="part-name-text"><?= htmlspecialchars($item['part_name'] ?? '-') ?></span>
                                        </td>
                                        <td class="col-shift"><?= $item['s1'] ?></td>
                                        <td class="col-shift"><?= $item['s2'] ?></td>
                                        <td class="col-shift"><?= $item['s3'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Tidak ada data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- TAB NAVIGASI MODUL -->
    <ul class="nav nav-tabs mb-3">
        <?php foreach ($modulesNav as $key => $title): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($activeCategory === $key) ? 'active fw-bold' : '' ?>"
                    href="index.php?page=warehouse_view&category=<?= $key ?>">
                    <?= $title ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- 2. REKAP DETAIL TABEL (ROWSPAN MERGE TANGGAL) -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-table"></i> Detail Rekapitulasi History: <strong><?= strtoupper($activeCategory) ?></strong>
            </h6>

            <form method="GET" class="d-flex align-items-center gap-2">
                <input type="hidden" name="page" value="warehouse_view">
                <input type="hidden" name="category" value="<?= htmlspecialchars($activeCategory) ?>">
                <input type="date" name="filter_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filterDate) ?>">
                <button type="submit" class="btn btn-sm btn-light">Filter</button>
                <?php if (!empty($filterDate)): ?>
                    <a href="index.php?page=warehouse_view&category=<?= $activeCategory ?>" class="btn btn-sm btn-outline-light">Reset</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th style="width: 140px;">Tanggal</th>
                            <th>Part Code</th>
                            <th>Part Name</th>
                            <th>S1</th>
                            <th>S2</th>
                            <th>S3</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($groupedHistory)): ?>
                            <?php foreach ($groupedHistory as $tgl => $items): ?>
                                <?php $rowCount = count($items); ?>
                                <?php foreach ($items as $index => $row): ?>
                                    <tr>
                                        <?php if ($index === 0): ?>
                                            <td rowspan="<?= $rowCount ?>" class="fw-bold align-middle bg-light">
                                                <?= date('d/m/Y', strtotime($tgl)) ?>
                                            </td>
                                        <?php endif; ?>

                                        <td class="fw-bold text-start"><?= htmlspecialchars($row['part_code']) ?></td>
                                        <td class="text-start"><?= htmlspecialchars($row['part_name'] ?? '-') ?></td>
                                        <td class="text-primary"><?= number_format($row['s1']) ?></td>
                                        <td class="text-warning text-dark"><?= number_format($row['s2']) ?></td>
                                        <td class="text-secondary"><?= number_format($row['s3']) ?></td>
                                        <td class="fw-bold table-success"><?= number_format($row['total']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-muted p-3">Tidak ada data transaksi.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>