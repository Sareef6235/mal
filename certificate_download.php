<?php
declare(strict_types=1);
require_once __DIR__ . '/system.php';
$fpdfFile = __DIR__ . '/vendor/fpdf/fpdf.php';

$id = (int)($_GET['student_id'] ?? 0);
$result = sys_fetch_student_result($id);
if (!$result) { http_response_code(404); exit('Student not found'); }

if (is_file($fpdfFile)) { require_once $fpdfFile; $pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Madrasa Result Certificate', 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, 'Student Name: ' . $result['student']['name'], 0, 1);
$pdf->Cell(0, 8, 'Register No: ' . $result['student']['register_no'], 0, 1);
$pdf->Cell(0, 8, 'Class: ' . $result['student']['class_name'], 0, 1);
$pdf->Ln(5);
$pdf->Cell(0, 8, 'Subject-wise Marks:', 0, 1);
foreach ($result['subjects'] as $subj) $pdf->Cell(0, 8, sprintf('%s: %s/%s', $subj['name'], $subj['mark'], $subj['max']), 0, 1);
$pdf->Ln(3);
$pdf->Cell(0, 8, 'Total: ' . $result['total'], 0, 1);
$pdf->Cell(0, 8, 'Percentage: ' . $result['percentage'] . '%', 0, 1);
$pdf->Cell(0, 8, 'Grade: ' . $result['grade'], 0, 1);
$pdf->Cell(0, 8, 'Promotion: ' . $result['promotion'], 0, 1);
$pdf->Ln(8);
$pdf->Cell(0, 8, 'Principal Signature: ' . sys_setting('principal_name', 'Principal'), 0, 1);
$pdf->Output('D', 'certificate-' . $result['student']['register_no'] . '.pdf');
    exit;
}
header('Content-Type: text/plain; charset=UTF-8');
header('Content-Disposition: attachment; filename="certificate-' . $result['student']['register_no'] . '.txt"');
echo "Madrasa Result Certificate\n";
echo 'Student Name: ' . $result['student']['name'] . "\n";
echo 'Register No: ' . $result['student']['register_no'] . "\n";
echo 'Class: ' . $result['student']['class_name'] . "\n";
foreach ($result['subjects'] as $subj) echo sprintf('%s: %s/%s', $subj['name'], $subj['mark'], $subj['max']) . "\n";
