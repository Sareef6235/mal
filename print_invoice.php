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
?>
<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Print Invoice #<?= (int)$order['id'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: #222; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #444; padding: 6px; font-size: 12px; text-align: right; }
        th:first-child, td:first-child { text-align: left; }
        .meta { margin-bottom: 14px; }
        .totals { margin-top: 10px; width: 320px; margin-left: auto; }
        .totals td { border: none; padding: 3px 0; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<button class="no-print" onclick="window.print()">Print</button>
<div class="meta">
    <h2>Book Invoice #<?= (int)$order['id'] ?></h2>
    <div>Student: <?= e($order['student_name']) ?></div>
    <div>Date: <?= e($order['created_at']) ?></div>
</div>
<table>
    <thead>
    <tr>
        <th>Book Name</th><th>MRP</th><th>Qty</th><th>Rate</th><th>Disc %</th><th>Discount</th><th>Taxable</th><th>CGST</th><th>SGST</th><th>Total</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item): ?>
        <tr>
            <td><?= e($item['book_name']) ?></td>
            <td><?= number_format((float)$item['mrp'],2) ?></td>
            <td><?= (int)$item['qty'] ?></td>
            <td><?= number_format((float)$item['rate'],2) ?></td>
            <td><?= number_format((float)$item['discount_percent'],2) ?></td>
            <td><?= number_format((float)$item['discount_amount'],2) ?></td>
            <td><?= number_format((float)$item['taxable_value'],2) ?></td>
            <td><?= number_format((float)$item['cgst_amount'],2) ?> (<?= number_format((float)$item['cgst_rate'],2) ?>%)</td>
            <td><?= number_format((float)$item['sgst_amount'],2) ?> (<?= number_format((float)$item['sgst_rate'],2) ?>%)</td>
            <td><?= number_format((float)$item['total'],2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<table class="totals">
    <tr><td>Total Discount:</td><td><?= number_format((float)$order['total_discount'],2) ?></td></tr>
    <tr><td>Grand Total:</td><td><?= number_format((float)$order['total_amount'],2) ?></td></tr>
    <tr><td>Commission (10%):</td><td><?= number_format((float)$order['commission'],2) ?></td></tr>
</table>

<script>window.print();</script>
</body>
</html>
