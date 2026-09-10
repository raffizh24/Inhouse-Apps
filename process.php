<?php
require 'vendor/autoload.php';
require 'config.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// Pastikan koneksi database tersedia ($conn atau $pdo)
if (!isset($conn) && isset($pdo)) {
    $conn = $pdo;
} elseif (!isset($conn) && !isset($pdo)) {
    die("Koneksi database gagal dimuat.");
}

if (isset($_POST['upload'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    if (!empty($file)) {
        try {
            $type = IOFactory::identify($file);
            $reader = IOFactory::createReader($type);
            $reader->setReadDataOnly(false);

            $spreadsheet = $reader->load($file);
            $sheet = $spreadsheet->getActiveSheet();

            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            // =========================================================================
            // STEP 1: Deteksi Bulan & Tahun Pertama dari Excel untuk Clean-up Data Lama
            // =========================================================================
            $detectedMonth = null;
            $detectedYear = null;

            for ($colCheck = 1; $colCheck <= $highestColumnIndex; $colCheck++) {
                $cellVal = $sheet->getCell([$colCheck, 8])->getCalculatedValue();
                if (!empty($cellVal) && $cellVal !== '-') {
                    $dt = null;
                    if (is_numeric($cellVal) && $cellVal > 40000) {
                        $dt = Date::excelToDateTimeObject($cellVal);
                    } else {
                        $ts = strtotime(trim((string)$cellVal));
                        if ($ts !== false && date('Y', $ts) > 1970) {
                            $dt = new DateTime(date('Y-m-d', $ts));
                        }
                    }

                    if ($dt) {
                        $detectedMonth = $dt->format('m');
                        $detectedYear = $dt->format('Y');
                        break;
                    }
                }
            }

            // Jika tanggal bulan terdeteksi, hapus data lama di bulan & tahun tersebut (Gaya PDO)
            if ($detectedMonth && $detectedYear) {
                $stmtDelete = $conn->prepare("DELETE FROM planning WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
                $stmtDelete->execute([$detectedMonth, $detectedYear]);
            }

            // =========================================================================
            // STEP 2: Proses Insert Data Baru
            // =========================================================================
            $insertedCount = 0;

            $stmt = $conn->prepare("INSERT INTO planning (model, tanggal, shift, seq, qty_plan) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("SQL Prepare Error");
            }

            $currentDateFormatted = null;
            $currentShiftNum = 0;

            for ($col = 1; $col <= $highestColumnIndex; $col++) {

                // 1. Cek Header Tanggal di Baris ke-8
                $cellTanggal = $sheet->getCell([$col, 8])->getCalculatedValue();

                if (!empty($cellTanggal) && $cellTanggal !== '-') {
                    if (is_numeric($cellTanggal) && $cellTanggal > 40000) {
                        $currentDateFormatted = Date::excelToDateTimeObject($cellTanggal)->format('Y-m-d');
                    } else {
                        $timestamp = strtotime(trim((string)$cellTanggal));
                        if ($timestamp !== false && date('Y', $timestamp) > 1970) {
                            $currentDateFormatted = date('Y-m-d', $timestamp);
                        }
                    }
                }

                // 2. Cek Header Shift di Baris ke-10
                $shiftLabel = strtoupper(trim((string)$sheet->getCell([$col, 10])->getCalculatedValue()));

                if ($shiftLabel === 'I' || $shiftLabel === '1')   $currentShiftNum = 1;
                if ($shiftLabel === 'II' || $shiftLabel === '2')  $currentShiftNum = 2;
                if ($shiftLabel === 'III' || $shiftLabel === '3') $currentShiftNum = 3;

                // 3. Cek SubHeader di Baris ke-11
                $subHeaderLabel = strtolower(trim((string)$sheet->getCell([$col, 11])->getCalculatedValue()));

                if ($currentDateFormatted && $currentShiftNum > 0 && str_contains($subHeaderLabel, 'qty')) {

                    $colSeq = $col - 1;

                    for ($row = 12; $row <= 100; $row++) {
                        $model = trim((string)$sheet->getCell([3, $row])->getCalculatedValue());

                        if (empty($model)) {
                            continue;
                        }

                        $qty = $sheet->getCell([$col, $row])->getCalculatedValue();
                        $qtyClean = str_replace(['.', ',', ' '], '', $qty);

                        $seqVal = $sheet->getCell([$colSeq, $row])->getCalculatedValue();
                        $seqClean = is_numeric($seqVal) ? (int)$seqVal : null;

                        if (is_numeric($qtyClean) && (int)$qtyClean > 0) {
                            $qtyInt = (int)$qtyClean;

                            // Eksekusi insert menggunakan PDO (masukkan parameter ke dalam array execute)
                            $stmt->execute([$model, $currentDateFormatted, $currentShiftNum, $seqClean, $qtyInt]);
                            $insertedCount++;
                        }
                    }
                }
            }

            header("Location: index.php?page=assy&status=success&count=" . $insertedCount);
            exit();
        } catch (Exception $e) {
            header("Location: index.php?page=assy&status=error&msg=" . urlencode($e->getMessage()));
            exit();
        }
    } else {
        header("Location: index.php?page=assy&status=error&msg=" . urlencode("File tidak ditemukan."));
        exit();
    }
} else {
    header("Location: index.php?page=assy");
    exit();
}
