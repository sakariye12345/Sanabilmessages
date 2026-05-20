<?php $this->load->view('/inc/requests_header.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Proforma Invoice</title>
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
    form{
      margin-bottom: 20px;
      height: 720px;
    }
    .btn{
      background-color: #002060;
      color: #fff;
    }
    <style>
@media print {
  #customer_name, #customer_addr1, #customer_addr2 { display:none !important; }
  #client_block { margin-top: 0; }
}
</style>

  </style>
</head>
<body>

  <!-- Header -->
  <div class="container">

<form method="post" action="<?= site_url('requests/store'); ?>">


    <div class="actions">
      <button style="width:120px; background-color:  #002060; margin-bottom: 10px;" type="submit" class="btn btn-dark  rounded-pill">Save</button>
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
    <span id="ce_client_name" contenteditable="true" spellcheck="false">UCIG DJIBOUTI SA</span><br>
    <span id="ce_addr1" contenteditable="true" spellcheck="false">1er étage, Batiment SABA</span><br>
    <span id="ce_addr2" contenteditable="true" spellcheck="false">B.P.2541</span>
  </p>

  <!-- Hidden inputs to submit with the form -->
<input type="hidden" name="customer_name"  id="customer_name">
<input type="hidden" name="customer_addr1" id="customer_addr1">
<input type="hidden" name="customer_addr2" id="customer_addr2">

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
<th>Action</th>
    </tr>
  </thead>
 <tbody id="invoice-items">
  <tr>
    <td>1</td>
    <td>
      <select class="form-control product-select" name="product_id[]">
  <option value="">— Select —</option>
  <?php if (!empty($products)): foreach ($products as $p): ?>
    <option value="<?= (int)$p['id']; ?>"
            data-price="<?= (float)$p['price']; ?>">
      <?= html_escape($p['name']); ?>
    </option>
  <?php endforeach; endif; ?>
</select>


    </td>
    <td style="width: 120px"><input type="number" class="form-control qty" name="qty[]" value="1"></td>
    <td style="width: 120px"><input type="number" class="form-control price" name="price[]" value="0"></td>
    <td class="total-ht text-right">0</td>
    <td><button class="btn btn-danger btn-sm" onclick="deleteRow(this)">✖</button></td>
  </tr>
</tbody>

</table>

<button type="button" class="btn btn-sm" onclick="addInvoiceRow()">+ Add Line</button>




  


<!-- Totals Box -->
<div class="totals-box">
  <table border="1">
    <tr>
      <th>TOTAL HT</th>
      <td><span id="total_ht">0</span> FDj</td>
    </tr>
    <tr>
      <th>REMISE</th>
      <td><input type="number" id="remise" name="remise" value="0" style="width:100%; text-align:right; border:none; outline:none;"></td>
    </tr>
    <tr>
      <th>NET HT</th>
      <td><span id="net_ht">0</span> FDj</td>
    </tr>
    <tr>
      <th>TVA 10%</th>
      <td><span id="tva">0</span> FDj</td>
    </tr>
    <tr>
      <th>TIMBRE</th>
      <td><input type="number" id="timbre" value="0" name="timbre" style="width:100%; text-align:right; border:none; outline:none;"></td>
    </tr>
    <tr>
      <th>TOTAL TTC</th>
      <td><span id="total_ttc">0</span> FDj</td>
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

</form>

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
      const res = await fetch('<?= site_url('requests/price/'); ?>' + id)
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

<script>
$(function(){
  // optional enhancement
  $('.customer-select').select2({ placeholder: 'Select a customer', width: '150px' });

  function renderCustomer(c) {
    // Compose address lines like your example
    const name  = c?.name  || '—';
    const addr1 = c?.addr1 || '';         // e.g., "1er étage, Batiment SABA"
    const addr2 = c?.po_box ? 'B.P.' + c.po_box : (c?.addr2 || '');

    $('#client_name').text('CLIENT: ' + name);
    $('#client_addr1').text(addr1);
    $('#client_addr2').text(addr2);

    // push to hidden inputs for POST
    $('#customer_name').val(name);
    $('#customer_address').val([addr1, addr2].filter(Boolean).join(' | '));
    $('#customer_pobox').val(c?.po_box || '');
  }

  // When user changes customer
  $(document).on('change', '.customer-select', async function(){
    const opt = this.options[this.selectedIndex];
    if (!opt || !this.value) {
      renderCustomer(null);
      return;
    }

    // Quick fill from data-* for instant UI
    const quick = {
      id: this.value,
      name:  opt.getAttribute('data-name'),
      addr1: opt.getAttribute('data-addr1'),
      addr2: opt.getAttribute('data-addr2'),
      city:  opt.getAttribute('data-city'),
      po_box:opt.getAttribute('data-pobox')
    };
    renderCustomer(quick);

    // Authoritative fill from backend (optional)
    try{
      const res = await fetch('<?= site_url('requests/customer/'); ?>' + this.value);
      if (res.ok) {
        const js = await res.json();
        if (js && (js.name || js.addr1 || js.po_box)) renderCustomer(js);
      }
    }catch(e){ console.warn('customer ajax failed', e); }
  });

  // If edit page with a pre-selected customer, trigger once:
  const $sel = $('.customer-select');
  if ($sel.val()) $sel.trigger('change');
});
</script>

</body>
</html>
