<?php
session_start();

require_once 'config.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {

    if (isset($_FILES['excel_file']['tmp_name']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $filePath = $_FILES['excel_file']['tmp_name'];

        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($filePath);
            $sheet       = $spreadsheet->getActiveSheet();
            $highestRow  = $sheet->getHighestRow();

            // UBAH POSISI KOLOM SESUAI FILE EXCEL KAMU
            // Contoh di bawah: A=Model, B=Tanggal, C=Shift, D=Qty
            for ($row = 2; $row <= $highestRow; $row++) {

                $modelRaw = $sheet->getCell("A{$row}")->getFormattedValue();
                $model    = mysqli_real_escape_string($conn, trim($modelRaw));

                // Lewati baris jika ini header "No", "Model", atau baris kosong
                if (empty($model) || strtolower($model) == 'no' || strtolower($model) == 'model') {
                    continue;
                }

                // Reading Tanggal (Kolom B)
                $cellTanggal = $sheet->getCell("B{$row}");
                $valTanggal  = $cellTanggal->getValue();

                $tanggalFormatted = null;
                if (!empty($valTanggal)) {
                    if (Date::isDateTime($cellTanggal)) {
                        $tanggalFormatted = Date::excelToDateTimeObject($valTanggal)->format('Y-m-d');
                    } else {
                        $tanggalFormatted = date('Y-m-d', strtotime($valTanggal));
                    }
                }

                // Reading Shift (Kolom C) & Qty (Kolom D)
                $shift    = mysqli_real_escape_string($conn, trim($sheet->getCell("C{$row}")->getFormattedValue()));
                $qty_plan = (int) $sheet->getCell("D{$row}")->getValue();

                // Simpan ke database
                if (!empty($model) && !empty($tanggalFormatted)) {
                    $query = "INSERT INTO planning (model, tanggal, shift, qty_plan) 
                              VALUES ('$model', '$tanggalFormatted', '$shift', '$qty_plan')";
                    mysqli_query($conn, $query);
                }
            }

            header("Location: page/upload_form.php?status=success");
            exit();
        } catch (Exception $e) {
            $msg = urlencode("Gagal membaca file: " . $e->getMessage());
            header("Location: page/upload_form.php?status=error&msg={$msg}");
            exit();
        }
    } else {
        $msg = urlencode("File tidak ditemukan atau eror saat unggah.");
        header("Location: page/upload_form.php?status=error&msg={$msg}");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
