<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invoice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1d2671 0%, #c33764 100%);
            color: #fff;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
        }
        .table input {
            min-width: 90px;
        }
        .table thead th {
            white-space: nowrap;
            font-size: 0.85rem;
        }
        .totals-box {
            background: rgba(255, 255, 255, 0.22);
            border-radius: 12px;
            padding: 12px;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="glass-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Book Order / Invoice System</h2>
            <a class="btn btn-light btn-sm" href="invoice_view.php">View Saved Invoices</a>
        </div>

        <form method="post" action="invoice_save.php" id="invoiceForm" novalidate>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Student Name</label>
                    <input type="text" name="student_name" class="form-control" required maxlength="150" placeholder="Enter student/customer name">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-dark align-middle" id="invoiceTable">
                    <thead>
                    <tr>
                        <th>Book Name</th>
                        <th>MRP</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Discount %</th>
                        <th>Discount Amount</th>
                        <th>Taxable Value</th>
                        <th>CGST %</th>
                        <th>CGST Amt</th>
                        <th>SGST %</th>
                        <th>SGST Amt</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody id="itemsBody"></tbody>
                </table>
            </div>

            <div class="d-flex gap-2 mb-3">
                <button type="button" class="btn btn-warning" id="addRowBtn">+ Add Row</button>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="totals-box">
                        <h6>Summary</h6>
                        <div class="d-flex justify-content-between"><span>Total Discount:</span><strong id="totalDiscountDisplay">0.00</strong></div>
                        <div class="d-flex justify-content-between"><span>Grand Total:</span><strong id="grandTotalDisplay">0.00</strong></div>
                        <div class="d-flex justify-content-between"><span>Commission (10%):</span><strong id="commissionDisplay">0.00</strong></div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="total_discount" id="totalDiscountInput" value="0">
            <input type="hidden" name="grand_total" id="grandTotalInput" value="0">
            <input type="hidden" name="commission" id="commissionInput" value="0">

            <div class="mt-4">
                <button type="submit" class="btn btn-success btn-lg">Save Invoice</button>
            </div>
        </form>
    </div>
</div>

<script>
    const itemsBody = document.getElementById('itemsBody');
    const addRowBtn = document.getElementById('addRowBtn');
    const totalDiscountDisplay = document.getElementById('totalDiscountDisplay');
    const grandTotalDisplay = document.getElementById('grandTotalDisplay');
    const commissionDisplay = document.getElementById('commissionDisplay');

    function toNum(value) {
        const parsed = parseFloat(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function rowTemplate() {
        return `<tr>
            <td><input type="text" class="form-control form-control-sm book-name" name="book_name[]" required></td>
            <td><input type="number" class="form-control form-control-sm mrp" name="mrp[]" min="0" step="0.01" value="0"></td>
            <td><input type="number" class="form-control form-control-sm qty" name="qty[]" min="1" step="1" value="1"></td>
            <td><input type="number" class="form-control form-control-sm rate" name="rate[]" min="0" step="0.01" value="0"></td>
            <td><input type="number" class="form-control form-control-sm discount-percent" name="discount_percent[]" min="0" max="100" step="0.01" value="0"></td>
            <td><input type="number" class="form-control form-control-sm discount-amount" name="discount_amount[]" readonly></td>
            <td><input type="number" class="form-control form-control-sm taxable-value" name="taxable_value[]" readonly></td>
            <td><input type="number" class="form-control form-control-sm cgst-rate" name="cgst_rate[]" min="0" max="100" step="0.01" value="9"></td>
            <td><input type="number" class="form-control form-control-sm cgst-amount" name="cgst_amount[]" readonly></td>
            <td><input type="number" class="form-control form-control-sm sgst-rate" name="sgst_rate[]" min="0" max="100" step="0.01" value="9"></td>
            <td><input type="number" class="form-control form-control-sm sgst-amount" name="sgst_amount[]" readonly></td>
            <td><input type="number" class="form-control form-control-sm row-total" name="line_total[]" readonly></td>
            <td><button type="button" class="btn btn-danger btn-sm delete-row">Delete</button></td>
        </tr>`;
    }

    function recalculateRow(row) {
        const qty = Math.max(1, toNum(row.querySelector('.qty').value));
        const rate = Math.max(0, toNum(row.querySelector('.rate').value));
        const discountPercent = Math.max(0, toNum(row.querySelector('.discount-percent').value));
        const cgstRate = Math.max(0, toNum(row.querySelector('.cgst-rate').value));
        const sgstRate = Math.max(0, toNum(row.querySelector('.sgst-rate').value));

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

    function recalculateAll() {
        let totalDiscount = 0;
        let grandTotal = 0;

        itemsBody.querySelectorAll('tr').forEach((row) => {
            recalculateRow(row);
            totalDiscount += toNum(row.querySelector('.discount-amount').value);
            grandTotal += toNum(row.querySelector('.row-total').value);
        });

        const commission = grandTotal * 0.10;
        totalDiscountDisplay.textContent = totalDiscount.toFixed(2);
        grandTotalDisplay.textContent = grandTotal.toFixed(2);
        commissionDisplay.textContent = commission.toFixed(2);

        document.getElementById('totalDiscountInput').value = totalDiscount.toFixed(2);
        document.getElementById('grandTotalInput').value = grandTotal.toFixed(2);
        document.getElementById('commissionInput').value = commission.toFixed(2);
    }

    function bindRowEvents(row) {
        row.addEventListener('input', (event) => {
            if (event.target.matches('input')) {
                recalculateAll();
            }
        });

        row.querySelector('.delete-row').addEventListener('click', () => {
            row.remove();
            if (!itemsBody.querySelector('tr')) {
                addRow();
            }
            recalculateAll();
        });
    }

    function addRow() {
        itemsBody.insertAdjacentHTML('beforeend', rowTemplate());
        const row = itemsBody.lastElementChild;
        bindRowEvents(row);
        recalculateAll();
    }

    addRowBtn.addEventListener('click', addRow);
    addRow();
</script>
</body>
</html>
