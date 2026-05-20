<?php $this->load->view('/inc/requests_header.php'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<div class="container" style="max-width:1100px; margin:20px auto;">
  <div style="display:flex; gap:10px; align-items:center; margin-bottom:15px;">
    <h3 style="margin:0; color:#101C89;">Requests</h3>
    <a class="btn rounded-pill" href="<?= site_url('requests/create'); ?>"
       style="margin-left:auto; background:#101C89; color:#fff; width:130px;">New Request</a>
  </div>

  <div class="table-responsive">
    <table id="applications_table" class="table table-striped table-bordered" style="width:100%">
      <thead style="font-size: 12px;">
        <tr style="background:#101C89; color:#fff;">
          <th>#</th>
          <th>Date</th>
          <th>Client</th>
          <th class="text-right">Total HT</th>
          <th class="text-right">Remise</th>
          <th class="text-right">Net HT</th>
          <th class="text-right">VAT 10%</th>
          <th class="text-right">Timbre</th>
          <th class="text-right">Total TTC</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!empty($rows)): foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id']; ?></td>
          <td><?= date('Y-m-d', strtotime($r['created_at'] ?? 'now')); ?></td>
          <td>
            <strong><?= html_escape($r['customer_name']); ?></strong><br>
            <small class="text-muted">
              <?= html_escape(trim($r['customer_addr1'].' '.($r['customer_addr2'] ?? ''))); ?>
            </small>
          </td>
          <td class="text-right"><?= number_format((float)$r['total_ht'],   2, '.', ''); ?></td>
          <td class="text-right"><?= number_format((float)$r['remise'],     2, '.', ''); ?></td>
          <td class="text-right"><?= number_format((float)$r['net_ht'],     2, '.', ''); ?></td>
          <td class="text-right"><?= number_format((float)$r['tva_amount'], 2, '.', ''); ?></td>
          <td class="text-right"><?= number_format((float)$r['timbre'],     2, '.', ''); ?></td>
          <td class="text-right font-weight-bold"><?= number_format((float)$r['total_ttc'], 2, '.', ''); ?></td>
          <td style="color:<?= ($r['status']??'')==='Cancelled'?'red':(($r['status']??'')==='Paid'?'green':'black'); ?>">
            <?= html_escape($r['status'] ?? 'Draft'); ?>
          </td>
          <td>
            <a class="btn btn-sm rounded-pill" style="background:#101C89;color:#fff;width:80px;"
               href="<?= site_url('requests/view/'.$r['id']); ?>">View</a>
           

            
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="11" class="text-center">No requests found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
    //*********** Data tables 
   $(document).ready(function(){
   var table = $('#applications_table').DataTable({
  
      "order": [],
        "scrollX": true,
       order: [0, 'asc'],
   
   
   mark: true,
         dom: 'Bfrtip',
        lengthMenu: [
             [ 10, 25, 50, 100, -1],
             ['10 rows', '25 rows', '50 rows', '100 rows', 'Show All']
         ],
   
   
   
   
   
         buttons: [
             'pageLength',
             {
                 extend: 'copyHtml5',
                 exportOptions: {
                     columns: ':visible'
                 }
             },
             {
                 extend: 'excelHtml5',
                 exportOptions: {
                     columns: ':visible'
                 }
             },
             {
                 extend: 'csvHtml5',
                 exportOptions: {
                     columns: ':visible'
                 }
             },
             
             {
                 extend: 'pdfHtml5',
                // download: 'open',
                 exportOptions: {
                     columns: ':visible'
                 }
   
             },
             {
                 extend: 'print',
                 exportOptions: {
                     columns: ':visible'
                 }
             },
             'colvis'
         ],
         columDefs: [{
             targets: -1,
             visible: false
         }]
   
   
   }); //End DT
   
   });  //end of document ready 
 </script>
   