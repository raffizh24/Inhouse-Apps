<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

echo "<h3>Debug Reader Excel</h3>";

// Pastikan kamu sudah upload file contoh atau ganti path ini ke file Excel kamu
$filePath = 'sample.xlsm'; // ATAU upload lewat form di bawah

if (isset($_FILES['excel_file'])) {
    $filePath = $_FILES['excel_file']['tmp_name'];
} else {
    echo '<form method="post" enctype="multipart/form-data">
            <input type="file" name="excel_file" required>
            <button type="submit">Cek Struktur File</button>
          </form>';
    exit();
}

try {
    $type = IOFactory::identify($filePath);
    $reader = IOFactory::createReader($type);
    $reader->setReadDataOnly(false);
    $spreadsheet = $reader->load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    echo "<b>Month/Year (C3):</b> " . var_export($sheet->getCell('C3')->getCalculatedValue(), true) . "<br><br>";

    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr style='background:#eee;'><th>Col Index</th><th>Col Letter</th><th>Baris 8 (Tanggal)</th><th>Baris 10 (Shift)</th><th>Baris 11 (SubHeader)</th><th>Baris 12 (Sample Qty)</th></tr>";

    $highestColumn = $sheet->getHighestColumn();
    $highestIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

    for ($col = 1; $col <= $highestIndex; $col++) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $tgl = $sheet->getCell([$col, 8])->getCalculatedValue();
        $shift = $sheet->getCell([$col, 10])->getCalculatedValue();
        $sub = $sheet->getCell([$col, 11])->getCalculatedValue();
        $qty = $sheet->getCell([$col, 12])->getCalculatedValue();

        // Tampilkan hanya kolom yang ada isinya agar tidak terlalu panjang
        if (!empty($tgl) || !empty($shift) || !empty($sub) || !empty($qty)) {
            echo "<tr>
                    <td>$col</td>
                    <td><b>$colLetter</b></td>
                    <td>" . var_export($tgl, true) . "</td>
                    <td>" . var_export($shift, true) . "</td>
                    <td>" . var_export($sub, true) . "</td>
                    <td>" . var_export($qty, true) . "</td>
                  </tr>";
        }
    }
    echo "</table>";

    echo "<br><b>Sample Model Baris 12 (C12):</b> " . var_export($sheet->getCell('C12')->getCalculatedValue(), true);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
