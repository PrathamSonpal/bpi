<?php
// generate_invoice.php
session_start();
if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin') die("Access Denied");

// Get Data
$party = $_POST['party_name'] ?? 'Cash Customer';
$date  = date('d/m/Y');
$items = json_decode($_POST['cart_data'], true);
$invNo = "INV-" . strtoupper(substr(uniqid(), -5));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Invoice <?= $invNo ?></title>
<style>
    body { font-family: 'Helvetica', sans-serif; padding: 40px; color: #333; }
    .invoice-box { max-width: 800px; margin: auto; border: 1px solid #eee; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.15); }
    .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
    .company-details h1 { margin: 0; color: #003366; font-size: 24px; }
    .invoice-details { text-align: right; }
    
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th { background: #f8f9fa; text-align: left; padding: 12px; border-bottom: 2px solid #ddd; }
    td { padding: 12px; border-bottom: 1px solid #eee; }
    .total-row td { border-top: 2px solid #333; font-weight: bold; font-size: 18px; }
    
    .btn-print { 
        display: block; margin: 20px auto; padding: 10px 20px; 
        background: #007bff; color: white; text-decoration: none; 
        border-radius: 5px; text-align: center; width: 200px;
    }
    @media print { .btn-print { display: none; } .invoice-box { border: none; box-shadow: none; } }
</style>
</head>
<body>
    <a href="#" onclick="window.print()" class="btn-print">🖨️ Print Invoice</a>
    
    <div class="invoice-box">
        <div class="header">
            <div class="company-details">
                <h1>Bhavesh Plastic Industries</h1>
                <p>Rajkot, Gujarat</p>
            </div>
            <div class="invoice-details">
                <h2>INVOICE</h2>
                <p><strong>#:</strong> <?= $invNo ?><br><strong>Date:</strong> <?= $date ?></p>
            </div>
        </div>

        <p><strong>Bill To:</strong> <?= htmlspecialchars($party) ?></p>

        <table>
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th style="text-align:right;">Rate</th>
                    <th style="text-align:center;">Qty</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $grandTotal = 0;
                if($items): foreach($items as $item): 
                    $lineTotal = $item['qty'] * $item['rate'];
                    $grandTotal += $lineTotal;
                ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td style="text-align:right;"><?= number_format($item['rate'], 2) ?></td>
                    <td style="text-align:center;"><?= $item['qty'] ?></td>
                    <td style="text-align:right;"><?= number_format($lineTotal, 2) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                
                <tr class="total-row">
                    <td colspan="3" style="text-align:right;">Total Amount:</td>
                    <td style="text-align:right;">₹<?= number_format($grandTotal, 2) ?></td>
                </tr>
            </tbody>
        </table>
        
        <div style="margin-top: 40px; font-size: 12px; text-align: center; color: #777;">
            This is a computer generated invoice.
        </div>
    </div>
</body>
</html>