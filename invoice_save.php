<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: invoice_create.php');
    exit;
}

$studentName = trim((string)($_POST['student_name'] ?? ''));
$bookNames = $_POST['book_name'] ?? [];
$mrps = $_POST['mrp'] ?? [];
$qtys = $_POST['qty'] ?? [];
$rates = $_POST['rate'] ?? [];
$discountPercents = $_POST['discount_percent'] ?? [];
$cgstRates = $_POST['cgst_rate'] ?? [];
$sgstRates = $_POST['sgst_rate'] ?? [];

if ($studentName === '' || !is_array($bookNames) || count($bookNames) === 0) {
    http_response_code(422);
    exit('Invalid input. Student name and at least one item are required.');
}

$pdo = getPDO();
$items = [];
$totalDiscount = 0.0;
$grandTotal = 0.0;

foreach ($bookNames as $i => $nameRaw) {
    $bookName = trim((string)$nameRaw);
    if ($bookName === '') {
        continue;
    }

    $mrp = max(0, (float)($mrps[$i] ?? 0));
    $qty = max(1, (int)($qtys[$i] ?? 1));
    $rate = max(0, (float)($rates[$i] ?? 0));
    $discountPercent = min(100, max(0, (float)($discountPercents[$i] ?? 0)));
    $cgstRate = min(100, max(0, (float)($cgstRates[$i] ?? 0)));
    $sgstRate = min(100, max(0, (float)($sgstRates[$i] ?? 0)));

    $baseTotal = $rate * $qty;
    $discountPerItem = $rate * ($discountPercent / 100);
    $discountAmount = $discountPerItem * $qty;
    $taxableValue = $baseTotal - $discountAmount;
    $cgstAmount = $taxableValue * ($cgstRate / 100);
    $sgstAmount = $taxableValue * ($sgstRate / 100);
    $lineTotal = $taxableValue + $cgstAmount + $sgstAmount;

    $items[] = [
        'book_name' => $bookName,
        'mrp' => round($mrp, 2),
        'rate' => round($rate, 2),
        'qty' => $qty,
        'discount_percent' => round($discountPercent, 2),
        'discount_amount' => round($discountAmount, 2),
        'taxable_value' => round($taxableValue, 2),
        'cgst_rate' => round($cgstRate, 2),
        'cgst_amount' => round($cgstAmount, 2),
        'sgst_rate' => round($sgstRate, 2),
        'sgst_amount' => round($sgstAmount, 2),
        'total' => round($lineTotal, 2),
    ];

    $totalDiscount += $discountAmount;
    $grandTotal += $lineTotal;
}

if (count($items) === 0) {
    http_response_code(422);
    exit('No valid rows found.');
}

$commission = $grandTotal * 0.10;

try {
    $pdo->beginTransaction();

    $stmtMain = $pdo->prepare(
        'INSERT INTO orders_main (student_name, total_amount, total_discount, commission, status) VALUES (?, ?, ?, ?, ?)' 
    );
    $stmtMain->execute([
        $studentName,
        round($grandTotal, 2),
        round($totalDiscount, 2),
        round($commission, 2),
        'editable',
    ]);

    $orderId = (int)$pdo->lastInsertId();

    $stmtItem = $pdo->prepare(
        'INSERT INTO orders_items (
            order_id, book_name, mrp, rate, qty, discount_percent, discount_amount,
            taxable_value, cgst_rate, cgst_amount, sgst_rate, sgst_amount, total
        ) VALUES (
            :order_id, :book_name, :mrp, :rate, :qty, :discount_percent, :discount_amount,
            :taxable_value, :cgst_rate, :cgst_amount, :sgst_rate, :sgst_amount, :total
        )'
    );

    foreach ($items as $item) {
        $stmtItem->execute([
            ':order_id' => $orderId,
            ':book_name' => $item['book_name'],
            ':mrp' => $item['mrp'],
            ':rate' => $item['rate'],
            ':qty' => $item['qty'],
            ':discount_percent' => $item['discount_percent'],
            ':discount_amount' => $item['discount_amount'],
            ':taxable_value' => $item['taxable_value'],
            ':cgst_rate' => $item['cgst_rate'],
            ':cgst_amount' => $item['cgst_amount'],
            ':sgst_rate' => $item['sgst_rate'],
            ':sgst_amount' => $item['sgst_amount'],
            ':total' => $item['total'],
        ]);
    }

    $pdo->commit();

    header('Location: invoice_view.php?id=' . $orderId . '&saved=1');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    exit('Unable to save invoice: ' . e($e->getMessage()));
}
