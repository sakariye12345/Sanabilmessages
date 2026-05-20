<?php
// $header: invoice_requests row
// $items:  invoice_request_items rows
//a $products: array of ['id','name','price'] to map names
$pmap = [];
if (!empty($products)) {
  foreach ($products as $p) { $pmap[(int)$p['id']] = $p['name']; }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Proforma Invoice #<?= (int)$header['id']; ?></title>
  <style>
    /* A4 page */
    @page { size: A4; margin: 14mm; }

    body { font-family: Arial, sans-serif; color: #000; margin: 0; }
    .page { width: 190mm; margin: 0 auto; }
    .row { display: flex; justify-content: space-between; align-items: flex-start; }
    .logo { max-height: 60px; }
    h1 { font-size: 18px; margin: 12px 0 6px; }
    .muted { font-size: 12px; color: #444; }

    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #000; padding: 6px 8px; font-size: 12px; }
    th { background: #e9eef7; text-align: left; }

    .totals { margin-top: 16px; width: 280px; margin-left: auto; border: 1px solid #000; }
    .totals th { background: #002060; color: #000; font-weight:600 }
    .totals td { text-align: right; }

    .header-line { margin: 10px 0 6px; }
    .right { text-align: right; }
    .mt-8 { margin-top: 8px; }
    .mb-12 { margin-bottom: 12px; }
    .right { text-align: right; }
.right .mt-8 { margin-top: 8px; }

 .mt-8{
  font-size: 16px;
 }

    /* hide UI buttons when printing */
    .no-print { margin: 10px 0; }
    @media print { .no-print { display: none !important; } }
  </style>
</head>
<body>
  <div class="no-print page" style="">
    <button onclick="history.back()">Back</button>
    <button onclick="window.print()">Print</button>
  </div>

  <div class="page">
    <!-- Header -->
   <!-- Header -->
<div class="row">
  <div>
    <!-- use your logo path if needed -->
    <img src="<?= base_url('assets/images/ccl.png'); ?>" class="logo" alt="Logo">
    <div class="muted mt-8">
      BALBALA, DORALEH B.P1794<br>
      TEL: +253 77 88 68 20, FAX: +253 21 36 04 30
    </div>
  </div>

  <div class="right">
    <div class="muted"><?= date('d/m/Y', strtotime($header['created_at'] ?? date('Y-m-d'))); ?></div>

    <!-- 👇 Client block (printed) -->
    <div class="mt-8">
      <strong>CLIENT:</strong><br>
      <?= htmlspecialchars($header['customer_name']  ?? '', ENT_QUOTES, 'UTF-8'); ?><br>
      <?= htmlspecialchars($header['customer_addr1'] ?? '', ENT_QUOTES, 'UTF-8'); ?><br>
      <?= htmlspecialchars($header['customer_addr2'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
    </div>
  </div>
</div>


    <h1>FACTURE PROFORMA N°<?= str_pad((string)$header['id'], 6, '0', STR_PAD_LEFT); ?></h1>

    <!-- Items -->
    <table class="mb-12">
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>Product</th>
          <th style="width:90px;">Qty</th>
          <th style="width:100px;">Price</th>
          <th style="width:110px;">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i => $it): ?>
          <tr>
            <td><?= $i+1; ?></td>
            <td>
              <?php
                $pid = (int)$it['product_id'];
                echo isset($pmap[$pid]) ? htmlspecialchars($pmap[$pid]) : ('#'.$pid);
              ?>
            </td>
            <td><?= number_format((float)$it['qty'], 3, '.', ''); ?></td>
            <td><?= number_format((float)$it['unit_price'], 2, '.', ''); ?></td>
            <td class="right"><?= number_format((float)$it['amount'], 2, '.', ''); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Totals -->
    <table class="totals">
      <tr>
        <th>TOTAL HT</th>
        <td><?= number_format((float)$header['total_ht'], 2, '.', ''); ?> FDj</td>
      </tr>
      <tr>
        <th>REMISE</th>
        <td><?= number_format((float)$header['remise'], 2, '.', ''); ?></td>
      </tr>
      <tr>
        <th>NET HT</th>
        <td><?= number_format((float)$header['net_ht'], 2, '.', ''); ?> FDj</td>
      </tr>
      <tr>
        <th>TVA 10%</th>
        <td><?= number_format((float)$header['tva_amount'], 2, '.', ''); ?> FDj</td>
      </tr>
      <tr>
        <th>TIMBRE</th>
        <td><?= number_format((float)$header['timbre'], 2, '.', ''); ?></td>
      </tr>
      <tr>
        <th>TOTAL TTC</th>
        <td><?= number_format((float)$header['total_ttc'], 2, '.', ''); ?> FDj</td>
      </tr>
    </table>

    <div class="muted header-line">La validité de la proforma est de 20 jours</div>
  </div>

  <script>window.addEventListener('load', function(){ window.print(); });</script>
</body>
</html>
