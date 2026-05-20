<?php $this->load->view('/inc/invoice_header'); ?>



<div class="container" style="width:100%">
 <div class="collapse" id="collapse_2">
  <div class="card card-body" style="width: 100%">
   <div class="form-row">
    <div class="col">
      <a class="btn btn-default btn-sm text-dark rounded-pill" href="/dashboard"> <strong><i class="fa-solid fa-home"></i> </strong></a> 

      <span><strong>/</strong></span>
      <a class="btn btn-default btn-sm text-dark rounded-pill" href="/invoicing"> <strong>Invoicing</strong></a> 
      <span><strong>/</strong></span>
      <a onclick="location.reload();" class="btn btn-default btn-sm text-dark rounded-pill"> <strong>Customer Balances</strong></a> 
    </div>



  </div>
</div>
</div>
<br>



<div class="row">
  <div class="col">
   <h3> Customer Balances</h3>


 </div>
 <div class="col">

  <button type="button" class="btn btn-default text-dark float-right ml-2 rounded-pill" data-toggle="collapse" data-target="#collapse_2" aria-expanded="false" aria-controls="collapseExample"> <i class="fa-solid fa-bars"></i></button>
  <!--a style="width: 100px;" id="table_button" onclick="selectLocation();" class="btn btn-sm btn-secondary  rounded-pill float-right ml-2"  href="#">  <small class="fa fa-filter  "></small>&nbsp;&nbsp;  Filter &nbsp;&nbsp;    </a--> 


   
       <a id="table_button" class="btn btn-sm btn-secondary  rounded-pill float-right ml-2" href="/invoicing/customer_balances/advanced_search">  <small class="fa fa-search  "></small>&nbsp;&nbsp;  Advanced Search &nbsp;&nbsp;    </a> 
      <input type="hidden" name="advanced_search" id="advanced_search" value="">





 </div>
</div>




<br>



<div class="table-resposive">

    <table class="table table-striped table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>#</th>
                <th>Customer Number</th>
                <th>Name</th>
                <th>Type</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Address</th>
                <th>City</th>
                <th>Region/State</th>
                <th>Country</th>
                <th>Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($customers)): ?>
                <?php foreach ($customers as $i => $c): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= $c['customer_number'] ?></td>
                        <td><?= $c['customer_name'] ?></td>
                        <td><?= $c['type'] ?></td>
                        <td><?= $c['phone'] ?></td>
                        <td><?= $c['email'] ?></td>
                        <td><?= $c['address'] ?></td>
                        <td><?= $c['city'] ?></td>
                        <td><?= $c['region_state'] ?></td>
                        <td><?= $c['country'] ?></td>
                        <td><?= number_format($c['balance'], 2) ?></td>
                        <td><?= $c['status'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="12" class="text-center">No customers found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>




<script type="text/javascript">
$(document).ready(function() {
  // Initialize DataTable
  var table = $('#customers_balances_table').DataTable({
    "ajax": base_url + "invoicing/fetch_customer_balances",  // Your AJAX source
    "order": [],
    "scrollX": true,
    order: [0, 'desc'],
    mark: true,
    dom: 'Bfrtip',
    lengthMenu: [
      [10, 25, 50, 100, -1],
      ['10 rows', '25 rows', '50 rows', '100 rows', 'Show All']
    ],
    buttons: [
      'pageLength',
      { extend: 'copyHtml5', exportOptions: { columns: ':visible' } },
      { extend: 'excelHtml5', exportOptions: { columns: ':visible' } },
      { extend: 'csvHtml5', exportOptions: { columns: ':visible' } },
      { extend: 'pdfHtml5', exportOptions: { columns: ':visible' } },
      { extend: 'print', exportOptions: { columns: ':visible' } },
      'colvis'
    ],
    columnDefs: [{
      targets: -1,
      visible: false
    }],
    "footerCallback": function (row, data, start, end, display) {
      var api = this.api();
      // Function to remove formatting and convert to integer.
      var intVal = function(i) {
        return typeof i === 'string' ? i.replace(/[\$,]/g, '')*1 : typeof i === 'number' ? i : 0;
      };

      // Total over all pages
      var total = api.column(7).data().reduce(function(a, b) {
        return intVal(a) + intVal(b);
      }, 0);

      // Total over this page
      var pageTotal = api.column(7, { page: 'current' }).data().reduce(function(a, b) {
        return intVal(a) + intVal(b);
      }, 0);

      // Update footer (adjust as needed)
      $(api.column(1).footer()).html('Current Page Total: ' + pageTotal);
      $(api.column(2).footer()).html('Overall Total: ' + total);
    }
  });

  // Populate the Focal Point and Status dropdowns once the table has been initialized.
  table.one('init.dt', function() {
    // Populate Focal Point filter (column index 4)
    table.column(4).data().unique().sort().each(function(d) {
      if (d) {
        $('#focal_point_filter').append('<option value="' + d + '">' + d + '</option>');
      }
    });
    // Populate Status filter (column index 8)
    table.column(8).data().unique().sort().each(function(d) {
      if (d) {
        $('#status_filter').append('<option value="' + d + '">' + d + '</option>');
      }
    });
  });

  // Filter by Focal Point when dropdown value changes.
  $('#focal_point_filter').on('change', function() {
    var val = $.fn.dataTable.util.escapeRegex($(this).val());
    table.column(4).search(val ? '^' + val + '$' : '', true, false).draw();
  });

  // Filter by Status when dropdown value changes.
  $('#status_filter').on('change', function() {
    var val = $.fn.dataTable.util.escapeRegex($(this).val());
    table.column(8).search(val ? '^' + val + '$' : '', true, false).draw();
  });
});
</script>