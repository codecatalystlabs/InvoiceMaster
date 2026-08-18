<?php
/**
 * Code Catalyst Labs - Edit Invoice
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'Edit Invoice';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get invoice
$query = "SELECT * FROM invoices WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$invoice = mysqli_fetch_assoc($result);

if (!$invoice) {
    $_SESSION['error'] = 'Invoice not found.';
    header('Location: list.php');
    exit();
}

// Get items
$query = "SELECT * FROM invoice_items WHERE invoice_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);
$existing_items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $existing_items[] = $item;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = (int)$_POST['client_id'];
    $date = clean($_POST['date']);
    $due_date = clean($_POST['due_date']);
    $status = clean($_POST['status']);
    $tax_rate = (float)$_POST['tax_rate'];
    $discount = (float)$_POST['discount'];
    $notes = clean($_POST['notes']);
    
    // PHP 8 compatibility: Check if items exists and is an array
    $items = isset($_POST['items']) && is_array($_POST['items']) ? $_POST['items'] : [];
    
    // Calculate totals
    $subtotal = 0;
    foreach ($items as $item) {
        if (!empty($item['item_name']) && $item['qty'] > 0 && $item['unit_price'] > 0) {
            $subtotal += $item['qty'] * $item['unit_price'];
        }
    }
    
    $tax = ($subtotal * $tax_rate) / 100;
    $total = $subtotal + $tax - $discount;
    
    // Update invoice
    $query = "UPDATE invoices 
              SET client_id = ?, date = ?, due_date = ?, subtotal = ?, tax = ?, discount = ?, total = ?, status = ?, notes = ? 
              WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "issddddssi", $client_id, $date, $due_date, $subtotal, $tax, $discount, $total, $status, $notes, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Delete old items
        $delete_query = "DELETE FROM invoice_items WHERE invoice_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $id);
        mysqli_stmt_execute($delete_stmt);
        
        // Insert new items
        $item_query = "INSERT INTO invoice_items (invoice_id, item_name, qty, unit_price, total) VALUES (?, ?, ?, ?, ?)";
        $item_stmt = mysqli_prepare($conn, $item_query);
        
        foreach ($items as $item) {
            if (!empty($item['item_name']) && $item['qty'] > 0 && $item['unit_price'] > 0) {
                $item_total = $item['qty'] * $item['unit_price'];
                mysqli_stmt_bind_param($item_stmt, "isidd", $id, $item['item_name'], $item['qty'], $item['unit_price'], $item_total);
                mysqli_stmt_execute($item_stmt);
            }
        }
        
        mysqli_stmt_close($item_stmt);
        
        logAudit($conn, 'Update', 'Invoice', $id, "Invoice {$invoice['invoice_number']} updated");
        $_SESSION['success'] = 'Invoice updated successfully!';
        header('Location: view.php?id=' . $id);
        exit();
    } else {
        $_SESSION['error'] = 'Failed to update invoice.';
    }
    
    mysqli_stmt_close($stmt);
}

$tax_rate_percent = ($invoice['subtotal'] > 0) ? ($invoice['tax'] / $invoice['subtotal']) * 100 : DEFAULT_TAX_RATE;

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil"></i> Edit Invoice - <?php echo $invoice['invoice_number']; ?></h2>
        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to View
        </a>
    </div>
    
    <form method="POST" action="" id="invoiceForm">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Invoice Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="client_id" class="form-label">Client *</label>
                                <select class="form-select" id="client_id" name="client_id" required>
                                    <option value="">Select Client</option>
                                    <?php
                                    $clients = getClients($conn);
                                    while ($client = mysqli_fetch_assoc($clients)):
                                    ?>
                                    <option value="<?php echo $client['id']; ?>" 
                                            <?php echo $client['id'] == $invoice['client_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($client['name'] . ' - ' . $client['company']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="date" class="form-label">Invoice Date *</label>
                                <input type="date" class="form-control" id="date" name="date" 
                                       value="<?php echo $invoice['date']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="due_date" class="form-label">Due Date *</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                       value="<?php echo $invoice['due_date']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($invoice['notes']); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Items -->
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Items</h5>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                            <i class="bi bi-plus"></i> Add Item
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th width="40%">Item Name</th>
                                        <th width="15%">Quantity</th>
                                        <th width="20%">Unit Price</th>
                                        <th width="20%">Total</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <!-- Items will be added here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Unpaid" <?php echo $invoice['status'] === 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                <option value="Partially Paid" <?php echo $invoice['status'] === 'Partially Paid' ? 'selected' : ''; ?>>Partially Paid</option>
                                <option value="Paid" <?php echo $invoice['status'] === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="Overdue" <?php echo $invoice['status'] === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                                <option value="Cancelled" <?php echo $invoice['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <strong id="subtotalDisplay">0</strong>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <label for="tax_rate" class="form-label">Tax Rate (%):</label>
                            <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                                   value="<?php echo $tax_rate_percent; ?>" step="0.01" min="0" onchange="calculateTotal()">
                            <div class="d-flex justify-content-between mt-1">
                                <span>Tax:</span>
                                <strong id="taxDisplay">0</strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="discount" class="form-label">Discount (UGX):</label>
                            <input type="number" class="form-control" id="discount" name="discount" 
                                   value="<?php echo $invoice['discount']; ?>" step="0.01" min="0" onchange="calculateTotal()">
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <h5>Total:</h5>
                            <h5 class="text-primary" id="totalDisplay">0</h5>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Invoice
                            </button>
                            <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let itemIndex = 0;
const existingItems = <?php echo json_encode($existing_items); ?>;

function addItem(itemData = null) {
    const tbody = document.getElementById('itemsBody');
    const row = document.createElement('tr');
    row.id = 'item_' + itemIndex;
    
    const itemName = itemData ? itemData.item_name : '';
    const qty = itemData ? itemData.qty : 1;
    const unitPrice = itemData ? itemData.unit_price : 0;
    const total = qty * unitPrice;
    
    row.innerHTML = `
        <td>
            <input type="text" class="form-control" name="items[${itemIndex}][item_name]" 
                   value="${itemName}" required>
        </td>
        <td>
            <input type="number" class="form-control" name="items[${itemIndex}][qty]" 
                   value="${qty}" min="1" required onchange="calculateItemTotal(${itemIndex})">
        </td>
        <td>
            <input type="number" class="form-control" name="items[${itemIndex}][unit_price]" 
                   value="${unitPrice}" step="0.01" min="0" required onchange="calculateItemTotal(${itemIndex})">
        </td>
        <td>
            <input type="text" class="form-control" id="item_total_${itemIndex}" value="$${total.toFixed(2)}" readonly>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${itemIndex})">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);
    itemIndex++;
    calculateTotal();
}

function removeItem(index) {
    const row = document.getElementById('item_' + index);
    row.remove();
    calculateTotal();
}

function calculateItemTotal(index) {
    const row = document.getElementById('item_' + index);
    const qty = parseFloat(row.querySelector(`input[name="items[${index}][qty]"]`).value) || 0;
    const price = parseFloat(row.querySelector(`input[name="items[${index}][unit_price]"]`).value) || 0;
    const total = qty * price;
    
    row.querySelector(`#item_total_${index}`).value = '$' + total.toFixed(2);
    calculateTotal();
}

function calculateTotal() {
    let subtotal = 0;
    
    const rows = document.querySelectorAll('#itemsBody tr');
    rows.forEach(row => {
        const qtyInput = row.querySelector('input[name*="[qty]"]');
        const priceInput = row.querySelector('input[name*="[unit_price]"]');
        
        if (qtyInput && priceInput) {
            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            subtotal += qty * price;
        }
    });
    
    const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    
    const tax = (subtotal * taxRate) / 100;
    const total = subtotal + tax - discount;
    
    document.getElementById('subtotalDisplay').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('taxDisplay').textContent = '$' + tax.toFixed(2);
    document.getElementById('totalDisplay').textContent = '$' + total.toFixed(2);
}

// Load existing items on page load
document.addEventListener('DOMContentLoaded', function() {
    existingItems.forEach(item => {
        addItem(item);
    });
});
</script>

<?php include '../includes/footer.php'; ?>

