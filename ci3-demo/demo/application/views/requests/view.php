<?php $this->load->view('/inc/requests_header.php'); ?>
<?php /* @var $header array @var $items array @var $products array */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
<title>Proforma Invoice #<?= (int)$header['id']; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 4 -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

  <!-- jQuery (MUST come before Select2 and your script) -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

  <!-- Select2 -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <style>
    body {
      font-family: 'Arial', sans-serif;
    
    }
    .logo {
      max-height: 80px;
    }
    .invoice-title {
      font-weight: bold;
      font-size: 20px;
      margin-top: 20px;
    }
    .table th {
      background-color: #f2f2f2;
      font-weight: bold;
      text-align: center;
      color: #000;
    }
    .table-bordered td,
    .table-bordered th {
      border: 1px solid black !important;
    }
    .totals-box {
      width: 300px;
      float: right;
      margin-top: 20px;
      border: 1px solid black;
    }
    .totals-box table {
      width: 100%;
    }
    .totals-box th {
      background: #002060;
      color: white;
      padding: 8px;
      text-align: left;
    }
    .totals-box td {
      padding: 8px;
      text-align: right;
    }
    .footer-note {
      margin-top: 80px;
      font-weight: bold;
    }
    .form-control{
      outline: none;
      border: none;
    }
    .form-control:focus{
      outline: none;
      border: none;
    }
    .actions{
      text-align: right;
      margin-bottom: 10px;
      
     
    }
  </style>
</head>
<body>

  <!-- Header -->
  <div class="container">

  
  <div class="actions  ">
  <!-- PRINT opens printable page -->
  <a href="<?= site_url('requests/print/'.$header['id']); ?>"
     class="btn rounded-pill"
     style="width:120px; background-color:#002060; color:#fff; margin-bottom:10px;">
     Print
  </a>

  <!-- EDIT goes to editable form -->
  <a href="<?= site_url('requests/edit/'.$header['id']); ?>"
     class="btn rounded-pill"
     style="width:120px; background-color:#002060; color:#fff; margin-bottom:10px;">
     Edit
  </a>

  <!-- CANCEL: POST with confirm -->
  <form method="post"
        action="<?= site_url('requests/cancel/'.$header['id']); ?>"
        onsubmit="return confirm('Are you sure you want to cancel this request? This will delete it.');"
        style="display:inline-block; margin:0;">
    <button type="submit"
            class="btn rounded-pill"
            style="width:120px; background-color:#a00; color:#fff; margin-bottom:10px;">
      Cancel
    </button>
  </form>
</div>

  <div class="row" style="">
    <div class="col-md-6">
      <img src="<?= base_url('assets/images/ccl.png'); ?>" alt="Company Logo" class="logo"><br>
      <p class="mt-2">
        BALBALA, DORALEH B.P1794<br>
        TEL: +253 77 88 68 20, FAX: +253 21 36 04 30
      </p>
    </div>
<div class="col-md-6 text-right">
  <p class="mt-2" id="client_block" style="min-height:72px;">
    <strong>CLIENT:</strong>
    <span id="ce_client_name" contenteditable="true" spellcheck="false">
      <?= isset($header['customer_name']) ? html_escape($header['customer_name']) : '' ?>
    </span><br>
    <span id="ce_addr1" contenteditable="true" spellcheck="false">
      <?= isset($header['customer_addr1']) ? html_escape($header['customer_addr1']) : '' ?>
    </span><br>
    <span id="ce_addr2" contenteditable="true" spellcheck="false">
      <?= isset($header['customer_addr2']) ? html_escape($header['customer_addr2']) : '' ?>
    </span>
  </p>

  <!-- Hidden inputs to submit with the form -->
  <input type="hidden" name="customer_name"  id="customer_name"
         value="<?= isset($header['customer_name']) ? html_escape($header['customer_name']) : '' ?>">
  <input type="hidden" name="customer_addr1" id="customer_addr1"
         value="<?= isset($header['customer_addr1']) ? html_escape($header['customer_addr1']) : '' ?>">
  <input type="hidden" name="customer_addr2" id="customer_addr2"
         value="<?= isset($header['customer_addr2']) ? html_escape($header['customer_addr2']) : '' ?>">
</div>



  </div>
  
   
  <!-- Title and Date -->
  <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
  <div class="invoice-title">FACTURE PROFORMA N°005211</div>
  <div><strong><?= date('d/m/Y'); ?></strong></div>
</div>

  <!-- Items Table -->
  <table class="table table-bordered">
  <thead>
    <tr>
     <th>#</th>
<th>Produit</th>
<th>Qté</th>
<th>Prix</th>
<th>Montant</th>

     
    </tr>
  </thead>
  <tbody id="invoice-items">
      <?php
      // si sahlan u samee map products[id] => ['name'=>..., 'price'=>...]
      $pmap = [];
      if (!empty($products)) {
        foreach ($products as $p) { $pmap[(int)$p['id']] = $p; }
      }
      ?>
      <?php foreach ($items as $i => $it): ?>
        <?php
          $pid   = (int)$it['product_id'];
          $qty   = (float)$it['qty'];
          $price = (float)$it['unit_price'];
          $amt   = (float)$it['amount']; // haddii aadan kaydin, isticmaal $qty*$price
        ?>
        <tr>
          <td><?= $i+1; ?></td>
          <td>
            <!-- SELECT (read-only); haddii aad rabto edit, ka saar disabled -->
            <select class="form-control product-select" name="product_id[]" disabled>
              <option value="">— Select —</option>
              <?php if (!empty($products)): foreach ($products as $p): ?>
                <option value="<?= (int)$p['id']; ?>"
                        data-price="<?= (float)$p['price']; ?>"
                        <?= ((int)$p['id'] === $pid ? 'selected' : ''); ?>>
                  <?= html_escape($p['name']); ?>
                </option>
              <?php endforeach; endif; ?>
            </select>
          </td>
          <td><input type="number" class="form-control qty"   name="qty[]"   value="<?= number_format($qty,3,'.',''); ?>" disabled></td>
          <td><input type="number" class="form-control price" name="price[]" value="<?= number_format($price,2,'.',''); ?>" disabled></td>
          <td class="text-right"><?= number_format($amt,2,'.',''); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>

</table>






  


 <!-- Totals Box from DB header -->
  <div class="totals-box">
    <table border="1" style="width:100%">
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
  </div>



  <!-- Footer Note -->
  <p class="footer-note">La validité de la proforma est de 20 jours</p>
</div>
<!-- Place inside your <form> -->
<input type="hidden" name="total_ht"   id="total_ht_input">
<input type="hidden" name="net_ht"     id="net_ht_input">
<input type="hidden" name="tva_amount" id="tva_input">
<input type="hidden" name="total_ttc"  id="total_ttc_input">
<!-- Remise & Timbre horey waa input, magaca sii hay: name="remise", name="timbre" -->


<script>
$(function () {
  function syncClient() {
    $('#customer_name').val( $('#ce_client_name').text().trim() );
    $('#customer_addr1').val( $('#ce_addr1').text().trim() );
    $('#customer_addr2').val( $('#ce_addr2').text().trim() );
  }

  $('#ce_client_name, #ce_addr1, #ce_addr2').on('input blur', syncClient);
  syncClient(); // initial
});
</script>



<style>
[contenteditable="true"] { outline: none; }
[contenteditable="true"]:focus { outline-color: #bbb; }
@media print { [contenteditable="true"] { outline: none !important; } }
</style>


<script>
$(function () {
  // ✅ 1) Cache server-rendered options BEFORE we init Select2 or the user selects anything
  const PRODUCT_OPTIONS_HTML = document.querySelector('#invoice-items tr:first-child .product-select').innerHTML;

  function initSelect2(el) {
    $(el).select2({ placeholder: 'Search for a product', width: '100%', allowClear: true });
  }

  function recalcRow(tr) {
    const qty   = parseFloat($(tr).find('.qty').val())   || 0;
    const price = parseFloat($(tr).find('.price').val()) || 0;
    const amt   = qty * price;
    $(tr).find('.total-ht').text((isFinite(amt) ? amt : 0).toFixed(2));
  }

 function calculateAll() {
  let totalHT = 0;
  $('#invoice-items tr').each(function () {
    const qty   = parseFloat($(this).find('.qty').val())   || 0;
    const price = parseFloat($(this).find('.price').val()) || 0;
    const line  = qty * price;
    $(this).find('.total-ht').text(line.toFixed(2));
    totalHT += line;
  });

  $('#total_ht').text(totalHT.toFixed(2));

  const remise = parseFloat($('#remise').val()) || 0;
  const netHT  = totalHT - remise;   $('#net_ht').text(netHT.toFixed(2));

  const tva    = netHT * 0.10;       $('#tva').text(tva.toFixed(2));
  const timbre = parseFloat($('#timbre').val()) || 0;
  const ttc    = netHT + tva + timbre; $('#total_ttc').text(ttc.toFixed(2));

  // ✅ send totals with POST
  $('#total_ht_input').val(totalHT.toFixed(2));
  $('#net_ht_input').val(netHT.toFixed(2));
  $('#tva_input').val(tva.toFixed(2));
  $('#total_ttc_input').val(ttc.toFixed(2));
}

  function renumberRows() {
    $('#invoice-items tr').each(function (i) {
      $(this).find('td:first').text(i + 1);
    });
  }

  async function fillPriceFromAjax(selectEl) {
    const tr = selectEl.closest('tr');
    if (!tr) return;

    const id = selectEl.value;
    if (!/^\d+$/.test(id || '')) {
      const priceInput = tr.querySelector('.price');
      if (priceInput) priceInput.value = '0.00';
      recalcRow(tr); calculateAll();
      return;
    }

    try {
      const res = await fetch('<?= site_url('requestscreate/price/'); ?>' + id);
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const js = await res.json();

      const price = parseFloat(js.price) || 0;
      const priceInput = tr.querySelector('.price');
      if (priceInput) priceInput.value = price.toFixed(2);

      const evt = new Event('input', { bubbles: true });
      (tr.querySelector('.price') || selectEl).dispatchEvent(evt);
    } catch (err) {
      console.error('Price lookup failed:', err);
      const priceInput = tr.querySelector('.price');
      if (priceInput) priceInput.value = '0.00';
      recalcRow(tr); calculateAll();
    }
  }

  function handleProductChange(selectEl) {
    const tr = selectEl.closest('tr');
    if (!tr) return;

    // provisional: set from data-price if present
    const opt = selectEl.options[selectEl.selectedIndex];
    if (opt) {
      const dp = parseFloat(opt.getAttribute('data-price') || '0');
      const priceInput = tr.querySelector('.price');
      if (priceInput) priceInput.value = (isFinite(dp) ? dp : 0).toFixed(2);
      recalcRow(tr); calculateAll();
    }

    // authoritative: AJAX overwrite
    fillPriceFromAjax(selectEl);
  }

  // ✅ 2) Init existing row
  initSelect2('.product-select');
  calculateAll();

  // ✅ 3) Add row uses the cached PRODUCT_OPTIONS_HTML (guaranteed full product list)
  window.addInvoiceRow = function () {
    const rowCount = $('#invoice-items tr').length + 1;

    const $row = $(`
      <tr>
        <td>${rowCount}</td>
        <td>
          <select class="form-control product-select" name="product_id[]">
            ${PRODUCT_OPTIONS_HTML}
          </select>
        </td>
        <td style="width:120px"><input type="number" class="form-control qty" name="qty[]" value="1"></td>
        <td style="width:120px"><input type="number" class="form-control price" name="price[]" value="0"></td>
        <td class="total-ht text-right">0</td>
        <td><button class="btn btn-danger btn-sm" onclick="deleteRow(this)">✖</button></td>
      </tr>
    `);

    $('#invoice-items').append($row);

    const $sel = $row.find('.product-select');
    initSelect2($sel);
    $sel.val('').trigger('change'); // start empty

    renumberRows();
    calculateAll();
  };

  window.deleteRow = function (btn) {
    $(btn).closest('tr').remove();
    renumberRows(); calculateAll();
  };

  $(document).on('input change', '.qty, .price, #remise, #timbre', function () {
    const tr = $(this).closest('tr');
    if (tr.length) recalcRow(tr[0]);
    calculateAll();
  });

  // Native + Select2 events
  document.getElementById('invoice-items').addEventListener('change', function (e) {
    if (e.target.classList && e.target.classList.contains('product-select')) {
      handleProductChange(e.target);
    }
  });
  $(document).on('select2:select select2:clear', '.product-select', function () {
    handleProductChange(this);
  });
});
</script>



</body>
</html>
