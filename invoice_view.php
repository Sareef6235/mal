<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = getPDO();
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $postOrderId = (int)($_POST['order_id'] ?? 0);

    if ($postOrderId > 0 && $action === 'toggle_lock') {
        $stmt = $pdo->prepare('UPDATE orders_main SET status = IF(status = "editable", "locked", "editable") WHERE id = ?');
        $stmt->execute([$postOrderId]);
        header('Location: invoice_view.php?id=' . $postOrderId . '&updated=1');
        exit;
    }

    if ($postOrderId > 0 && $action === 'update_invoice') {
        $statusStmt = $pdo->prepare('SELECT status FROM orders_main WHERE id = ?');
        $statusStmt->execute([$postOrderId]);
        $status = $statusStmt->fetchColumn();

        if ($status === 'locked') {
            $message = 'This invoice is locked and cannot be edited.';
        } else {
            $bookNames = $_POST['book_name'] ?? [];
            $mrps = $_POST['mrp'] ?? [];
            $qtys = $_POST['qty'] ?? [];
            $rates = $_POST['rate'] ?? [];
            $discountPercents = $_POST['discount_percent'] ?? [];
            $cgstRates = $_POST['cgst_rate'] ?? [];
            $sgstRates = $_POST['sgst_rate'] ?? [];
            $itemIds = $_POST['item_id'] ?? [];

            $items = [];
            $totalDiscount = 0.0;
            $grandTotal = 0.0;

            foreach ($bookNames as $i => $nameRaw) {
                $bookName = trim((string)$nameRaw);
                if ($bookName === '') {
                    continue;
                }

                $qty = max(1, (int)($qtys[$i] ?? 1));
                $rate = max(0, (float)($rates[$i] ?? 0));
                $discountPercent = min(100, max(0, (float)($discountPercents[$i] ?? 0)));
                $cgstRate = min(100, max(0, (float)($cgstRates[$i] ?? 0)));
                $sgstRate = min(100, max(0, (float)($sgstRates[$i] ?? 0)));
                $mrp = max(0, (float)($mrps[$i] ?? 0));
                $itemId = isset($itemIds[$i]) ? (int)$itemIds[$i] : 0;

                $baseTotal = $rate * $qty;
                $discountPerItem = $rate * ($discountPercent / 100);
                $discountAmount = $discountPerItem * $qty;
                $taxableValue = $baseTotal - $discountAmount;
                $cgstAmount = $taxableValue * ($cgstRate / 100);
                $sgstAmount = $taxableValue * ($sgstRate / 100);
                $lineTotal = $taxableValue + $cgstAmount + $sgstAmount;

                $items[] = [
                    'item_id' => $itemId,
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

            if ($items) {
                $commission = $grandTotal * 0.1;

                try {
                    $pdo->beginTransaction();
                    $pdo->prepare('DELETE FROM orders_items WHERE order_id = ?')->execute([$postOrderId]);

                    $insertItem = $pdo->prepare(
                        'INSERT INTO orders_items (order_id, book_name, mrp, rate, qty, discount_percent, discount_amount, taxable_value, cgst_rate, cgst_amount, sgst_rate, sgst_amount, total)
                         VALUES (:order_id, :book_name, :mrp, :rate, :qty, :discount_percent, :discount_amount, :taxable_value, :cgst_rate, :cgst_amount, :sgst_rate, :sgst_amount, :total)'
                    );

                    foreach ($items as $item) {
                        $insertItem->execute([
                            ':order_id' => $postOrderId,
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

                    $updateMain = $pdo->prepare('UPDATE orders_main SET total_amount=?, total_discount=?, commission=? WHERE id=?');
                    $updateMain->execute([round($grandTotal,2), round($totalDiscount,2), round($commission,2), $postOrderId]);

                    $pdo->commit();
                    header('Location: invoice_view.php?id=' . $postOrderId . '&updated=1');
                    exit;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $message = 'Update failed: ' . $e->getMessage();
                }
            }
        }
    }
}

if ($orderId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM orders_main WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) {
        http_response_code(404);
        exit('Invoice not found.');
    }

    $itemStmt = $pdo->prepare('SELECT * FROM orders_items WHERE order_id = ? ORDER BY id');
    $itemStmt->execute([$orderId]);
    $items = $itemStmt->fetchAll();
} else {
    $orders = $pdo->query('SELECT * FROM orders_main ORDER BY id DESC')->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7fb; }
        .card { border: none; border-radius: 14px; box-shadow: 0 10px 26px rgba(0,0,0,0.08); }
        .summary-pill { border-radius: 10px; padding: 10px 14px; font-weight: 600; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Invoices</h2>
        <a href="invoice_create.php" class="btn btn-primary">+ New Invoice</a>
    </div>

    <?php if ($orderId === 0): ?>
        <div class="card p-3">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>ID</th><th>Student</th><th>Total</th><th>Discount</th><th>Commission</th><th>Status</th><th>Created</th><th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $row): ?>
                        <tr>
                            <td><?= (int)$row['id'] ?></td>
                            <td><?= e($row['student_name']) ?></td>
                            <td><?= number_format((float)$row['total_amount'], 2) ?></td>
                            <td><?= number_format((float)$row['total_discount'], 2) ?></td>
                            <td><?= number_format((float)$row['commission'], 2) ?></td>
                            <td><span class="badge <?= $row['status'] === 'locked' ? 'bg-danger' : 'bg-success' ?>"><?= e($row['status']) ?></span></td>
                            <td><?= e($row['created_at']) ?></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="invoice_view.php?id=<?= (int)$row['id'] ?>">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <?php $locked = $order['status'] === 'locked'; ?>
        <div class="card p-3">
            <div class="d-flex justify-content-between flex-wrap gap-2 align-items-center mb-2">
                <div>
                    <h4 class="mb-1">Invoice #<?= (int)$order['id'] ?></h4>
                    <div><strong>Student:</strong> <?= e($order['student_name']) ?></div>
                </div>
                <div class="d-flex gap-2">
                    <a href="print_invoice.php?id=<?= (int)$order['id'] ?>" class="btn btn-outline-secondary">Print</a>
                    <a href="pdf_invoice.php?id=<?= (int)$order['id'] ?>" class="btn btn-outline-dark">Download PDF</a>
                    <a href="invoice_export_csv.php?id=<?= (int)$order['id'] ?>" class="btn btn-outline-success">Export CSV</a>
                    <form method="post">
                        <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                        <input type="hidden" name="action" value="toggle_lock">
                        <button class="btn <?= $locked ? 'btn-success' : 'btn-danger' ?>" type="submit">
                            <?= $locked ? 'Unlock' : 'Lock' ?> Invoice
                        </button>
                    </form>
                </div>
            </div>

            <?php if (!empty($_GET['updated'])): ?>
                <div class="alert alert-success">Invoice updated successfully.</div>
            <?php endif; ?>
            <?php if (!empty($message)): ?>
                <div class="alert alert-danger"><?= e($message) ?></div>
            <?php endif; ?>

            <form method="post" id="editForm">
                <input type="hidden" name="action" value="update_invoice">
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <div class="table-responsive">
                    <table class="table table-bordered" id="editTable">
                        <thead>
                        <tr>
                            <th>Book Name</th><th>MRP</th><th>Qty</th><th>Rate</th><th>Discount %</th><th>Discount Amount</th><th>Taxable Value</th><th>CGST %</th><th>CGST Amt</th><th>SGST %</th><th>SGST Amt</th><th>Total</th><th>Action</th>
                        </tr>
                        </thead>
                        <tbody id="editItemsBody">
                        <?php foreach ($items as $idx => $item): ?>
                            <tr>
                                <td><input <?= $locked ? 'disabled' : '' ?> class="form-control form-control-sm" name="book_name[]" value="<?= e($item['book_name']) ?>"></td>
                                <td><input <?= $locked ? 'disabled' : '' ?> type="number" class="form-control form-control-sm mrp" name="mrp[]" value="<?= e((string)$item['mrp']) ?>"></td>
                                <td><input <?= $locked ? 'disabled' : '' ?> type="number" class="form-control form-control-sm qty" name="qty[]" value="<?= e((string)$item['qty']) ?>"></td>
                                <td><input <?= $locked ? 'disabled' : '' ?> type="number" class="form-control form-control-sm rate" name="rate[]" value="<?= e((string)$item['rate']) ?>"></td>
                                <td><input <?= $locked ? 'disabled' : '' ?> type="number" class="form-control form-control-sm discount-percent" name="discount_percent[]" value="<?= e((string)$item['discount_percent']) ?>"></td>
                                <td><input readonly type="number" class="form-control form-control-sm discount-amount" name="discount_amount[]" value="<?= e((string)$item['discount_amount']) ?>"></td>
                                <td><input readonly type="number" class="form-control form-control-sm taxable-value" name="taxable_value[]" value="<?= e((string)$item['taxable_value']) ?>"></td>
                                <td><input <?= $locked ? 'disabled' : '' ?> type="number" class="form-control form-control-sm cgst-rate" name="cgst_rate[]" value="<?= e((string)$item['cgst_rate']) ?>"></td>
                                <td><input readonly type="number" class="form-control form-control-sm cgst-amount" name="cgst_amount[]" value="<?= e((string)$item['cgst_amount']) ?>"></td>
                                <td><input <?= $locked ? 'disabled' : '' ?> type="number" class="form-control form-control-sm sgst-rate" name="sgst_rate[]" value="<?= e((string)$item['sgst_rate']) ?>"></td>
                                <td><input readonly type="number" class="form-control form-control-sm sgst-amount" name="sgst_amount[]" value="<?= e((string)$item['sgst_amount']) ?>"></td>
                                <td><input readonly type="number" class="form-control form-control-sm row-total" name="line_total[]" value="<?= e((string)$item['total']) ?>"></td>
                                <td>
                                    <input type="hidden" name="item_id[]" value="<?= (int)$item['id'] ?>">
                                    <button <?= $locked ? 'disabled' : '' ?> type="button" class="btn btn-sm btn-danger delete-row">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!$locked): ?>
                    <button type="button" class="btn btn-warning" id="addRowBtn">+ Add Row</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                <?php endif; ?>
            </form>

            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="summary-pill bg-light">Total Discount: <span id="totalDiscount"><?= number_format((float)$order['total_discount'], 2) ?></span></div>
                </div>
                <div class="col-md-4">
                    <div class="summary-pill bg-warning">Grand Total: <span id="grandTotal"><?= number_format((float)$order['total_amount'], 2) ?></span></div>
                </div>
                <div class="col-md-4">
                    <div class="summary-pill bg-info">Commission: <span id="commission"><?= number_format((float)$order['commission'], 2) ?></span></div>
                </div>
            </div>
        </div>

        <script>
            const tableBody = document.getElementById('editItemsBody');
            const addRowBtn = document.getElementById('addRowBtn');
            const isLocked = <?= $locked ? 'true' : 'false' ?>;

            const toNum = (v) => Number.isFinite(parseFloat(v)) ? parseFloat(v) : 0;

            function recalcRow(row) {
                const qty = Math.max(1, toNum(row.querySelector('.qty')?.value));
                const rate = Math.max(0, toNum(row.querySelector('.rate')?.value));
                const discountPercent = Math.max(0, toNum(row.querySelector('.discount-percent')?.value));
                const cgstRate = Math.max(0, toNum(row.querySelector('.cgst-rate')?.value));
                const sgstRate = Math.max(0, toNum(row.querySelector('.sgst-rate')?.value));

                const baseTotal = rate * qty;
                const discountPerItem = rate * (discountPercent / 100);
                const totalDiscount = discountPerItem * qty;
                const taxableValue = baseTotal - totalDiscount;
                const cgstAmount = taxableValue * (cgstRate / 100);
                const sgstAmount = taxableValue * (sgstRate / 100);
                const finalTotal = taxableValue + cgstAmount + sgstAmount;

                row.querySelector('.discount-amount').value = totalDiscount.toFixed(2);
                row.querySelector('.taxable-value').value = taxableValue.toFixed(2);
                row.querySelector('.cgst-amount').value = cgstAmount.toFixed(2);
                row.querySelector('.sgst-amount').value = sgstAmount.toFixed(2);
                row.querySelector('.row-total').value = finalTotal.toFixed(2);
            }

            function recalcAll() {
                let totalDiscount = 0;
                let grand = 0;
                tableBody.querySelectorAll('tr').forEach((row) => {
                    recalcRow(row);
                    totalDiscount += toNum(row.querySelector('.discount-amount').value);
                    grand += toNum(row.querySelector('.row-total').value);
                });
                const commission = grand * 0.10;
                document.getElementById('totalDiscount').textContent = totalDiscount.toFixed(2);
                document.getElementById('grandTotal').textContent = grand.toFixed(2);
                document.getElementById('commission').textContent = commission.toFixed(2);
            }

            function bindRow(row) {
                row.addEventListener('input', recalcAll);
                const del = row.querySelector('.delete-row');
                if (del) {
                    del.addEventListener('click', () => {
                        row.remove();
                        if (!tableBody.querySelector('tr')) {
                            addRow();
                        }
                        recalcAll();
                    });
                }
            }

            function rowTemplate() {
                return `<tr>
                    <td><input class="form-control form-control-sm" name="book_name[]"></td>
                    <td><input type="number" class="form-control form-control-sm mrp" name="mrp[]" value="0"></td>
                    <td><input type="number" class="form-control form-control-sm qty" name="qty[]" value="1"></td>
                    <td><input type="number" class="form-control form-control-sm rate" name="rate[]" value="0"></td>
                    <td><input type="number" class="form-control form-control-sm discount-percent" name="discount_percent[]" value="0"></td>
                    <td><input readonly type="number" class="form-control form-control-sm discount-amount" name="discount_amount[]"></td>
                    <td><input readonly type="number" class="form-control form-control-sm taxable-value" name="taxable_value[]"></td>
                    <td><input type="number" class="form-control form-control-sm cgst-rate" name="cgst_rate[]" value="9"></td>
                    <td><input readonly type="number" class="form-control form-control-sm cgst-amount" name="cgst_amount[]"></td>
                    <td><input type="number" class="form-control form-control-sm sgst-rate" name="sgst_rate[]" value="9"></td>
                    <td><input readonly type="number" class="form-control form-control-sm sgst-amount" name="sgst_amount[]"></td>
                    <td><input readonly type="number" class="form-control form-control-sm row-total" name="line_total[]"></td>
                    <td><input type="hidden" name="item_id[]" value="0"><button type="button" class="btn btn-sm btn-danger delete-row">Delete</button></td>
                </tr>`;
            }

            function addRow() {
                tableBody.insertAdjacentHTML('beforeend', rowTemplate());
                bindRow(tableBody.lastElementChild);
            }

            if (!isLocked) {
                tableBody.querySelectorAll('tr').forEach(bindRow);
                if (addRowBtn) {
                    addRowBtn.addEventListener('click', () => {
                        addRow();
                        recalcAll();
                    });
                }
                recalcAll();
            }
        </script>
    <?php endif; ?>
</div>
</body>
</html>
