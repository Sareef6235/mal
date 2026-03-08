<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_auth('admin');
require_once __DIR__ . '/../config/db.php';

$data = $pdo->query('SELECT students.name, attendance.date, attendance.time, attendance.method FROM attendance JOIN students ON students.id = attendance.student_id ORDER BY attendance.date DESC, attendance.time DESC')->fetchAll();

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', 'Student');
    $sheet->setCellValue('B1', 'Date');
    $sheet->setCellValue('C1', 'Time');
    $sheet->setCellValue('D1', 'Method');

    $row = 2;
    foreach ($data as $d) {
        $sheet->setCellValue('A' . $row, $d['name']);
        $sheet->setCellValue('B' . $row, $d['date']);
        $sheet->setCellValue('C' . $row, $d['time']);
        $sheet->setCellValue('D' . $row, $d['method']);
        $row++;
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="attendance.xlsx"');
    $writer->save('php://output');
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="attendance.csv"');
$out = fopen('php://output', 'wb');
fputcsv($out, ['Student', 'Date', 'Time', 'Method']);
foreach ($data as $row) {
    fputcsv($out, [$row['name'], $row['date'], $row['time'], $row['method']]);
}
fclose($out);
exit;
