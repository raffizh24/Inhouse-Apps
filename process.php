<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {

    if (isset($_FILES['excel_file']['tmp_name']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $filePath = $_FILES['excel_file']['tmp_name'];

        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($filePath);

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $sheetName = $sheet->getTitle();

                // Skip sheet pendukung
                if (in_array(strtolower($sheetName), ['cover', 'summary', 'master', 'template'])) {
                    continue;
                }

                $highestRow = $sheet->getHighestRow();

                // 1. Dapatkan daftar Tanggal dan Kolom Qty-nya dari Baris 8
                $dateBlocks = [];
                for ($colNum = 14; $colNum <= 200; $colNum += 7) {
                    // Konversi angka kolom ke huruf (misal: 16 -> P)
                    $colLetter = Coordinate::stringFromColumnIndex($colNum + 2);
                    $cellTanggal = $sheet->getCell("{$colLetter}8");
                    $valTanggal  = $cellTanggal->getValue();

                    if (!empty($valTanggal)) {
                        $tanggalFormatted = null;
                        if (Date::isDateTime($cellTanggal) || is_numeric($valTanggal)) {
                            $tanggalFormatted = Date::excelToDateTimeObject($valTanggal)->format('Y-m-d');
                        } else {
                            $parsedTime = strtotime($valTanggal);
                            if ($parsedTime !== false) {
                                $tanggalFormatted = date('Y-m-d', $parsedTime);
                            }
                        }

                        if ($tanggalFormatted) {
                            $dateBlocks[] = [
                                'tanggal'    => $tanggalFormatted,
                                'col_shift1' => Coordinate::stringFromColumnIndex($colNum + 2), // Qty Shift I (P)
                                'col_shift2' => Coordinate::stringFromColumnIndex($colNum + 4), // Qty Shift II (R)
                                'col_shift3' => Coordinate::stringFromColumnIndex($colNum + 6)  // Qty Shift III (T)
                            ];
                        }
                    }
                }

                // 2. Loop Baris Data Model (Mulai baris 12)
                for ($row = 12; $row <= $highestRow; $row++) {

                    // Ambil Model dari Kolom B
                    $modelRaw = $sheet->getCell("B{$row}")->getFormattedValue();
                    $model    = mysqli_real_escape_string($conn, trim($modelRaw));

                    // Filter: Hanya proses jika Model berawalan AH- atau AU-
                    $prefix = strtoupper(substr($model, 0, 3));
                    if ($prefix !== 'AH-' && $prefix !== 'AU-') {
                        continue;
                    }

                    // 3. Loop tiap blok Tanggal yang ditemukan
                    foreach ($dateBlocks as $block) {
                        $tgl = $block['tanggal'];

                        // Shift 1
                        $qty1 = (int) $sheet->getCell("{$block['col_shift1']}{$row}")->getValue();
                        if ($qty1 > 0) {
                            $query = "INSERT INTO planning (model, tanggal, shift, qty_plan) VALUES ('$model', '$tgl', '1', '$qty1')";
                            mysqli_query($conn, $query);
                        }

                        // Shift 2
                        $qty2 = (int) $sheet->getCell("{$block['col_shift2']}{$row}")->getValue();
                        if ($qty2 > 0) {
                            $query = "INSERT INTO planning (model, tanggal, shift, qty_plan) VALUES ('$model', '$tgl', '2', '$qty2')";
                            mysqli_query($conn, $query);
                        }

                        // Shift 3
                        $qty3 = (int) $sheet->getCell("{$block['col_shift3']}{$row}")->getValue();
                        if ($qty3 > 0) {
                            $query = "INSERT INTO planning (model, tanggal, shift, qty_plan) VALUES ('$model', '$tgl', '3', '$qty3')";
                            mysqli_query($conn, $query);
                        }
                    }
                }
            }

            header("Location: index.php?status=success");
            exit();
        } catch (Exception $e) {
            $msg = urlencode("Gagal membaca file: " . $e->getMessage());
            header("Location: index.php?status=error&msg={$msg}");
            exit();
        }
    } else {
        $msg = urlencode("File tidak ditemukan.");
        header("Location: index.php?status=error&msg={$msg}");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
