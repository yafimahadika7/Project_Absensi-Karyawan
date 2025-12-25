<?php
require_once '../config/auth.php';
require_once '../config/koneksi.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

auth_guard();
role_guard('hrd');

// ======================
// FILTER
// ======================
$dari = $_GET['dari'];
$sampai = $_GET['sampai'];
$departemen = $_GET['departemen'] ?? '';

// ======================
// DATA KARYAWAN
// ======================
$whereDept = "";
if ($departemen !== '') {
    $whereDept = "WHERE k.departemen_id = '$departemen'";
}

$qKaryawan = mysqli_query($koneksi, "
    SELECT
        u.id AS user_id,
        u.nama,
        d.nama_departemen
    FROM users u
    JOIN karyawan k ON k.user_id = u.id
    JOIN departemen d ON k.departemen_id = d.id
    $whereDept
    ORDER BY d.nama_departemen ASC, u.nama ASC
");

// ======================
// DATA ABSENSI RANGE
// ======================
$absensi = [];
$qAbs = mysqli_query($koneksi, "
    SELECT *
    FROM absensi
    WHERE tanggal BETWEEN '$dari' AND '$sampai'
");

while ($a = mysqli_fetch_assoc($qAbs)) {
    $absensi[$a['user_id']][$a['tanggal']] = $a;
}

// ======================
// RANGE TANGGAL
// ======================
$periode = new DatePeriod(
    new DateTime($dari),
    new DateInterval('P1D'),
    (new DateTime($sampai))->modify('+1 day')
);

// ======================
// CREATE EXCEL
// ======================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Laporan Absensi');

// Header
$headers = ['Tanggal', 'Nama', 'Departemen', 'Jam Masuk', 'Jam Pulang', 'Status'];
$col = 'A';
foreach ($headers as $h) {
    $sheet->setCellValue($col . '1', $h);
    $sheet->getStyle($col . '1')->getFont()->setBold(true);
    $sheet->getStyle($col . '1')->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $col++;
}

// ======================
// ISI DATA (SAMA DENGAN TAMPILAN ADMIN)
// ======================
$rowNum = 2;

foreach ($periode as $tgl) {

    $tanggal = $tgl->format('Y-m-d');
    mysqli_data_seek($qKaryawan, 0);

    while ($k = mysqli_fetch_assoc($qKaryawan)) {

        $data = $absensi[$k['user_id']][$tanggal] ?? null;
        $status = $data['status'] ?? 'belum absen';

        $sheet->setCellValue('A' . $rowNum, date('d-m-Y', strtotime($tanggal)));
        $sheet->setCellValue('B' . $rowNum, $k['nama']);
        $sheet->setCellValue('C' . $rowNum, strtoupper($k['nama_departemen']));
        $sheet->setCellValue(
            'D' . $rowNum,
            isset($data['jam_masuk']) ? date('H:i', strtotime($data['jam_masuk'])) : '-'
        );
        $sheet->setCellValue(
            'E' . $rowNum,
            isset($data['jam_pulang']) ? date('H:i', strtotime($data['jam_pulang'])) : '-'
        );
        $sheet->setCellValue('F' . $rowNum, ucfirst($status));

        $rowNum++;
    }
}

// ======================
// OUTPUT
// ======================
$filename = "laporan_absensi_{$dari}_{$sampai}.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;