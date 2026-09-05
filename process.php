<?php
require 'vendor/autoload.php';
require 'config.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

if (isset($_POST['upload'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    if (!empty($file)) {
        try {
            $type = IOFactory::identify($file);
            $reader = IOFactory::createReader($type);
            $reader->setReadDataOnly(false); // Baca hasil kalkulasi formula

            $spreadsheet = $reader->load($file);
            $sheet = $spreadsheet->getActiveSheet();

            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            $insertedCount = 0;

            // Prepared Statement ke MySQL
            $stmt = $conn->prepare("INSERT INTO planning (model, tanggal, shift, seq, qty_plan) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("SQL Prepare Error: " . $conn->error);
            }

            // Variable penampung Tanggal & Shift aktif (karena merged cell / offset kolom)
            $currentDateFormatted = null;
            $currentShiftNum = 0;

            // Loop Kolom dari A (Index 1) sampai kolom paling kanan
            for ($col = 1; $col <= $highestColumnIndex; $col++) {

                // 1. Cek Header Tanggal di Baris ke-8
                $cellTanggal = $sheet->getCell([$col, 8])->getCalculatedValue();

                if (!empty($cellTanggal) && $cellTanggal !== '-') {
                    if (is_numeric($cellTanggal) && $cellTanggal > 40000) {
                        // Konversi Serial Number Excel (misal: 46266 -> 2026-09-01)
                        $currentDateFormatted = Date::excelToDateTimeObject($cellTanggal)->format('Y-m-d');
                    } else {
                        $timestamp = strtotime(trim((string)$cellTanggal));
                        if ($timestamp !== false && date('Y', $timestamp) > 1970) {
                            $currentDateFormatted = date('Y-m-d', $timestamp);
                        }
                    }
                }

                // 2. Cek Header Shift di Baris ke-10 (I, II, III)
                $shiftLabel = strtoupper(trim((string)$sheet->getCell([$col, 10])->getCalculatedValue()));

                if ($shiftLabel === 'I' || $shiftLabel === '1')   $currentShiftNum = 1;
                if ($shiftLabel === 'II' || $shiftLabel === '2')  $currentShiftNum = 2;
                if ($shiftLabel === 'III' || $shiftLabel === '3') $currentShiftNum = 3;

                // 3. Cek SubHeader di Baris ke-11 (Mencari kolom Qty)
                $subHeaderLabel = strtolower(trim((string)$sheet->getCell([$col, 11])->getCalculatedValue()));

                // Eksekusi jika Tanggal aktif tersimpan, Shift terdeteksi, dan kolom berupa 'Qty'
                if ($currentDateFormatted && $currentShiftNum > 0 && str_contains($subHeaderLabel, 'qty')) {

                    // Kolom 'Seq.' berada tepat 1 kolom di sebelah kiri 'Qty'
                    $colSeq = $col - 1;

                    // Loop baris MODEL (Baris 12 sampai 100)
                    for ($row = 12; $row <= 100; $row++) {
                        // Ambil Model dari Kolom C (Index 3)
                        $model = trim((string)$sheet->getCell([3, $row])->getCalculatedValue());

                        if (empty($model)) {
                            continue;
                        }

                        // Ambil nilai Qty
                        $qty = $sheet->getCell([$col, $row])->getCalculatedValue();
                        $qtyClean = str_replace(['.', ',', ' '], '', $qty);

                        // Ambil nilai Seq (Urutan)
                        $seqVal = $sheet->getCell([$colSeq, $row])->getCalculatedValue();
                        $seqClean = is_numeric($seqVal) ? (int)$seqVal : null;

                        // Insert ke DB jika Qty berupa angka dan > 0
                        if (is_numeric($qtyClean) && (int)$qtyClean > 0) {
                            $qtyInt = (int)$qtyClean;
                            $stmt->bind_param("ssiii", $model, $currentDateFormatted, $currentShiftNum, $seqClean, $qtyInt);
                            $stmt->execute();
                            $insertedCount++;
                        }
                    }
                }
            }

            $stmt->close();

            header("Location: index.php?page=upload_plan&status=success&count=" . $insertedCount);
            exit();
        } catch (Exception $e) {
            header("Location: index.php?page=upload_plan&status=error&msg=" . urlencode($e->getMessage()));
            exit();
        }
    } else {
        header("Location: index.php?page=upload_plan&status=error&msg=" . urlencode("File tidak ditemukan."));
        exit();
    }
} else {
    header("Location: index.php?page=upload_plan");
    exit();
}
