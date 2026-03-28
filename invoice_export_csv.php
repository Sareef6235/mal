<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) {
    exit('Invalid invoice id.');
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM orders_main WHERE id = ?');
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order) {
    exit('Invoice not found.');
}

$itemStmt = $pdo->prepare('SELECT * FROM orders_items WHERE order_id = ? ORDER BY id');
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="invoice_' . $orderId . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Invoice ID', $order['id']]);
fputcsv($output, ['Student Name', $order['student_name']]);
fputcsv($output, ['Created At', $order['created_at']]);
fputcsv($output, []);
fputcsv($output, ['Book Name', 'MRP', 'Qty', 'Rate', 'Discount %', 'Discount Amount', 'Taxable Value', 'CGST %', 'CGST Amount', 'SGST %', 'SGST Amount', 'Total']);

foreach ($items as $item) {
    fputcsv($output, [
        $item['book_name'],
        $item['mrp'],
        $item['qty'],
        $item['rate'],
        $item['discount_percent'],
        $item['discount_amount'],
        $item['taxable_value'],
        $item['cgst_rate'],
        $item['cgst_amount'],
        $item['sgst_rate'],
        $item['sgst_amount'],
        $item['total'],
    ]);
}

fputcsv($output, []);
fputcsv($output, ['Total Discount', $order['total_discount']]);
fputcsv($output, ['Grand Total', $order['total_amount']]);
fputcsv($output, ['Commission', $order['commission']]);

fclose($output);
exit;
