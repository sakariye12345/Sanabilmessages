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
     .btn{
      background-color: #002060;
      color: #fff;
    }
  </style>
</head>
<body>

  <!-- Header -->
  <div class="container">
     <div class="actions  ">

 

  <!-- EDIT goes to editable form -->
<button form="editForm" type="submit" class="btn rounded-pill" style="width:120px;  color:#fff; margin-bottom:10px;">Update</button>



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

  <form id="editForm" method="post" action="<?= site_url('requests/update/'.$header['id']); ?>">
 

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
    <span id="ce_client_name" contenteditable="true" spellcheck="false" style="outline: none;">
      <?= isset($header['customer_name']) ? html_escape($header['customer_name']) : '' ?>
    </span><br>
    <span id="ce_addr1" contenteditable="true" spellcheck="false" style="outline: none;">
      <?= isset($header['customer_addr1']) ? html_escape($header['customer_addr1']) : '' ?>
    </span><br>
    <span id="ce_addr2" contenteditable="true" spellcheck="false" style="outline: none;">
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
      <th>Product</th>
      <th>Qty</th>
      <th>Price</th>
      <th>Amount</th>
      <th>Action</th>
     
    </tr>
  </thead>
 <tbody id="invoice-items">
      <?php
        // Build options once
        $opts = '<option value="">— Select —</option>';
        if (!empty($products)) {
          foreach ($products as $p) {
            $opts .= '<option value="'.(int)$p['id'].'" data-price="'.(float)$p['price'].'">'.
                      htmlspecialchars($p['name']).'</option>';
          }
        }
      ?>
      <?php foreach ($items as $i => $it): ?>
        <tr>
          <td><?= $i+1; ?></td>
          <td>
            <select class="form-control product-select" name="product_id[]">
              <?= $opts ?>
            </select>
            <script>
              // preselect this row's product
              (function(){ 
                var rows = document.querySelectorAll('#invoice-items tr');
                var row  = rows[rows.length-1];
                var sel  = row ? row.querySelector('.product-select') : null;
                if (sel) sel.value = "<?= (int)$it['product_id']; ?>";
              })();
            </script>
          </td>
          <td><input type="number" step="0.001" class="form-control qty"   name="qty[]"   value="<?= number_format((float)$it['qty'],3,'.',''); ?>"></td>
          <td><input type="number" step="0.01"  class="form-control price" name="price[]" value="<?= number_format((float)$it['unit_price'],2,'.',''); ?>"></td>
          <td class="total-ht text-right"><?= number_format((float)$it['amount'],2,'.',''); ?></td>
          <td><button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">✖</button></td>
        </tr>
      <?php endforeach; ?>
    </tbody>

</table>


<button type="button" class="btn btn-sm" onclick="addInvoiceRow()">+ Add Line</button>



  


<!-- Totals Box (EDITABLE remise/timbre; others auto) -->
  <div class="totals-box">
    <table border="1" style="width:100%">
      <tr>
        <th>TOTAL HT</th>
        <td><span id="total_ht"><?= number_format((float)$header['total_ht'], 2, '.', ''); ?></span> FDj</td>
      </tr>
      <tr>
        <th>REMISE</th>
        <td>
          <input type="number" id="remise" name="remise"
                 value="<?= number_format((float)$header['remise'], 2, '.', ''); ?>"
                 style="width:100%; text-align:right; border:none; outline:none;">
        </td>
      </tr>
      <tr>
        <th>NET HT</th>
        <td><span id="net_ht"><?= number_format((float)$header['net_ht'], 2, '.', ''); ?></span> FDj</td>
      </tr>
      <tr>
        <th>TVA 10%</th>
        <td><span id="tva"><?= number_format((float)$header['tva_amount'], 2, '.', ''); ?></span> FDj</td>
      </tr>
      <tr>
        <th>TIMBRE</th>
        <td>
          <input type="number" id="timbre" name="timbre"
                 value="<?= number_format((float)$header['timbre'], 2, '.', ''); ?>"
                 style="width:100%; text-align:right; border:none; outline:none;">
        </td>
      </tr>
      <tr>
        <th>TOTAL TTC</th>
        <td><span id="total_ttc"><?= number_format((float)$header['total_ttc'], 2, '.', ''); ?></span> FDj</td>
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

</form>

<script>
$(function () {
  // -------- Config / helpers ----------
  const $itemsTbody = $('#invoice-items');

  // Cache the server-rendered <option>s (first row) so new rows get the full list
  const PRODUCT_OPTIONS_HTML =
    ($itemsTbody.find('tr:first .product-select').html() || '<option value="">— Select —</option>');

  function initSelect2(target) {
    $(target).select2({ placeholder: 'Search for a product', width: '100%', allowClear: true });
  }

  function recalcRow(tr) {
    const qty   = parseFloat($(tr).find('.qty').val())   || 0;
    const price = parseFloat($(tr).find('.price').val()) || 0;
    const amt   = qty * price;
    $(tr).find('.total-ht').text(amt.toFixed(2));
  }

  function renumberRows() {
    $itemsTbody.find('tr').each(function (i) {
      $(this).find('td:first').text(i + 1);
    });
  }

  function calculateAll() {
    let totalHT = 0;

    $itemsTbody.find('tr').each(function () {
      const qty   = parseFloat($(this).find('.qty').val())   || 0;
      const price = parseFloat($(this).find('.price').val()) || 0;
      const line  = qty * price;
      $(this).find('.total-ht').text(line.toFixed(2));
      totalHT += line;
    });

    $('#total_ht').text(totalHT.toFixed(2));

    const remise = parseFloat($('#remise').val()) || 0;
    const netHT  = totalHT - remise; $('#net_ht').text(netHT.toFixed(2));

    const tva    = netHT * 0.10;      $('#tva').text(tva.toFixed(2));
    const timbre = parseFloat($('#timbre').val()) || 0;
    const ttc    = netHT + tva + timbre; $('#total_ttc').text(ttc.toFixed(2));

    // push totals to hidden inputs so they post with the form
    $('#total_ht_input').val(totalHT.toFixed(2));
    $('#net_ht_input').val(netHT.toFixed(2));
    $('#tva_input').val(tva.toFixed(2));
    $('#total_ttc_input').val(ttc.toFixed(2));
  }

  async function fillPriceFromAjax(selectEl) {
    const tr = selectEl.closest('tr');
    if (!tr) return;

    const id = selectEl.value;
    if (!/^\d+$/.test(id)) {
      $(tr).find('.price').val('0.00');
      recalcRow(tr); calculateAll();
      return;
    }

    try {
      const res = await fetch('<?= site_url('requests/price/'); ?>' + id);
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const js = await res.json();
      const price = parseFloat(js?.price) || 0;
      $(tr).find('.price').val(price.toFixed(2));
      // trigger recalcs
      recalcRow(tr); calculateAll();
    } catch (e) {
      console.warn('Price lookup failed:', e);
      $(tr).find('.price').val('0.00');
      recalcRow(tr); calculateAll();
    }
  }

  function handleProductChange(selectEl) {
    const tr  = selectEl.closest('tr');
    if (!tr) return;

    // Provisional: set from data-price embedded in <option>
    const opt = selectEl.options[selectEl.selectedIndex];
    const dp  = parseFloat(opt?.getAttribute('data-price') || '0');
    $(tr).find('.price').val((isFinite(dp) ? dp : 0).toFixed(2));

    recalcRow(tr);
    calculateAll();

    // Authoritative: overwrite with AJAX price (if available)
    fillPriceFromAjax(selectEl);
  }

  // -------- Public actions ----------
  window.addInvoiceRow = function () {
    const idx = $itemsTbody.find('tr').length + 1;
    const rowHtml = `
      <tr>
        <td>${idx}</td>
        <td>
          <select class="form-control product-select" name="product_id[]">
            ${PRODUCT_OPTIONS_HTML}
          </select>
        </td>
        <td style="width:120px"><input type="number" step="0.001" class="form-control qty"   name="qty[]"   value="1"></td>
        <td style="width:120px"><input type="number" step="0.01"  class="form-control price" name="price[]" value="0"></td>
        <td class="total-ht text-right">0.00</td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">✖</button></td>
      </tr>`;
    $itemsTbody.append(rowHtml);

    const $newSelect = $itemsTbody.find('tr:last .product-select');
    initSelect2($newSelect);
    $newSelect.val('').trigger('change');

    calculateAll();
  };

  window.deleteRow = function (btn) {
    $(btn).closest('tr').remove();
    renumberRows();
    calculateAll();
  };

  // -------- Event wiring ----------
  // Init Select2 on existing selects
  initSelect2('.product-select');

  // Qty/price/remise/timbre changes recalc totals
  $(document).on('input change', '.qty, .price, #remise, #timbre', function () {
    const tr = $(this).closest('tr');
    if (tr.length) recalcRow(tr[0]);
    calculateAll();
  });

  // Native change
  $itemsTbody.on('change', '.product-select', function () {
    handleProductChange(this);
  });

  // Select2 events
  $(document).on('select2:select select2:clear', '.product-select', function () {
    handleProductChange(this);
  });

  // Initial totals
  calculateAll();
});
</script>




</body>
</html>
