<?php $this->load->view('/inc/hygiene_header.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <!-- ✅ mobile scaling -->
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Recorded Classes</title>

  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

  <style>
    body { font-family: 'Segoe UI', sans-serif; font-size: 14px;  color: #333; }
    .header-path { font-size: 18px; margin-bottom: 15px; }
    .header-path b { font-weight: 700; font-size: 22px; }
    .back-container { position: absolute; top: 30px; right: 40px; }
    .back-container button { background: white; border: 1px solid #ccc; border-radius: 20px; padding: 5px 15px; font-size: 14px; cursor: pointer; }
    .back-container button:hover { background-color: #f0f0f0; }
    #classesTable_wrapper .dt-buttons { margin-bottom: 10px; }
    table.dataTable thead th { background-color: #f9f9f9; font-weight: 700; border-bottom: 1px solid #ddd; font-size: 16px; }
    table.dataTable tbody td { vertical-align: middle; font-size: 16px; border: 1px solid #ccc; }
    .status-submitted { color: green; font-weight: bold; }
    .status-pending { color: red; font-weight: bold; }
    .view-btn { padding: 5px 15px; border-radius: 20px; border: 1px solid #ccc; background-color: white; cursor: pointer; font-size: 14px; }
    .view-btn:hover { background-color: #f2f2f2; }
    div.dataTables_wrapper div.dataTables_scrollBody { border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; }
    ::-webkit-scrollbar { height: 10px; }
    ::-webkit-scrollbar-track { background: #ddd; }
    ::-webkit-scrollbar-thumb { background: #666; border-radius: 10px; }
    .dataTables_paginate .paginate_button { border-radius: 5px !important; padding: 5px 10px !important; }
    .actions{ display:flex; align-items:center;  justify-content:flex-end; }
     .dt-buttons{

     }
    /* ===== Mobile responsiveness ===== */
    @media (max-width: 992px){
      body{ margin: 24px; }
      table.dataTable thead th,
      table.dataTable tbody td { font-size: 15px; }
    }

    @media (max-width: 768px){
      body{ margin: 16px; }
      .container{ width: 100% !important; }
      .actions{
        flex-wrap: wrap;
        gap: .5rem;
        justify-content: flex-start;
      }
      .actions h2{
        font-size: 18px;
        line-height: 1.2;
        flex: 0 0 100%;          /* row 1 */
        margin: 0;
      }
      .actions button{
        width: 160px;            /* row 2, compact button */
      }
      table.dataTable thead th,
      table.dataTable tbody td { font-size: 14px; }
    }

    @media (max-width: 480px){
      .actions h2{
        font-size: 16px;
      }
      .actions span{ display: inline-block; }
      .actions button{
        width: 100%;             /* full width button on very small screens */
        border-radius: 24px;
      }
      /* make table always scrollable horizontally */
      .table-wrap{
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }
      /* shrink DataTables buttons row spacing a bit */
      #classesTable_wrapper .dt-buttons { gap: 4px; }
    }
    <style>
  /* Top bar layout (buttons + search) */
  .dt-topbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:.5rem;
    margin-bottom:.5rem;
  }
  /* Keep it one row on phones; allow sideways scroll if crowded */
  @media (max-width: 576px){
    .dt-topbar{
      flex-wrap:nowrap;
      overflow-x:auto;
      -webkit-overflow-scrolling:touch;
      padding:.25rem 0;
    }
    /* smaller buttons on phones */
    .dt-buttons .dt-button{
      font-size:.8rem !important;
      padding:.25rem .5rem !important;
      line-height:1.2 !important;
      border-radius:14px !important;
      white-space:nowrap;
    }
    /* compact search box */
    div.dataTables_filter label{
      margin:0;
      white-space:nowrap;
    }
    div.dataTables_filter input{
      height:30px;
      padding:.25rem .5rem;
      font-size:.85rem;
      width:140px;           /* tweak if you want narrower/wider */
    }
  }
</style>

  <style>
  .dt-topbar{display:flex; align-items:center; justify-content:flex-start; gap:.5rem; flex-wrap:wrap;}
  @media (max-width:576px){ .dt-topbar{flex-wrap:nowrap; overflow-x:auto; -webkit-overflow-scrolling:touch;} }
</style>

</head>
<body>
  <div class="container" style=" width: 90%; margin: auto;">
    <div class="actions">
      <h2 style="margin-right: auto;">
        Recorded Classes
        <span style="font-weight: 400"> \ <?= html_escape($school_name); ?> \ All Classes \ Date: <?= date('Y-m-d'); ?></span>
      </h2>
      <button style="width: 120px; border-radius: 25px; font-size: 16px; background-color: black; color: white; padding: 5px; margin-top: 4px" onclick="history.back();">&larr; Back</button>
    </div>
   

   <div class="dt-topbar" style="margin:.25rem 0 8px; gap:.5rem; align-items:center;">
  <div>
    <label for="dateFilter" style="margin-right:.35rem;">Date:</label>
    <input type="date" id="dateFilter" value="<?= date('Y-m-d'); ?>">
    <button id="clearDate" class="view-btn" style="padding:4px 10px; margin-left:.35rem;">All dates</button>
  </div>
</div>
 
 <!-- ✅ wrap table for safe horizontal scroll on phones -->
    <div class="table-wrap">
      <table  id="classesTable" class="display nowrap" style="width:100%">
        <thead  >
          <tr >
            <th style="color: black;" >#</th>
            <th style="color: black;" >Date</th>
            <th style="color: black;">Class</th>
            <th style="color: black;">Updated by</th>
            <th style="color: black;">Status</th>
            <th style="color: black;">View</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($attendances)): $i = 1; foreach ($attendances as $a): ?>
            <tr>
              <td><?= $i++; ?></td>
              <td><?= html_escape($a['date']); ?></td>
              <td><?= html_escape($a['class_name']); ?></td>
              <td><?= html_escape($a['submitted_by']); ?></td>
              <td>
                <?php if ($a['status'] === 'Submitted'): ?>
                  <span class="status-submitted">Submitted</span>
                <?php else: ?>
                  <span class="status-pending">Pending</span>
                <?php endif; ?>
              </td>
          <td>
  <?php
    $isPending = strcasecmp($a['status'], 'Pending') === 0;

    // PENDING → prepare.php (GET)
    // SUBMITTED → read-only detail
    $targetUrl = $isPending
      ? site_url('hygiene/prepare_direct/' . (int)$a['class_id'] . '/' . rawurlencode($a['date']))
      : site_url('hygiene/view/'           . (int)$a['class_id'] . '/' . rawurlencode($a['date']));
  ?>
  <a href="<?= $targetUrl ?>" class="view-btn" style="width:100px; text-decoration:none;">View</a>
</td>

            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-center text-muted">No attendance records found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- JS assets -->
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
  $(document).ready(function(){
    var table = $('#classesTable').DataTable({
      order: [],
      scrollX: true,
      order: [0, 'asc'],
      mark: true,
      dom: 'Bfrtip',
      lengthMenu: [
        [10, 25, 50, 100, -1],
        ['10 rows', '25 rows', '50 rows', '100 rows', 'Show All']
      ],
      buttons: [
        'pageLength',
        { extend: 'copyHtml5',  exportOptions: { columns: ':visible:not(:last-child)' } },
        { extend: 'excelHtml5', exportOptions: { columns: ':visible:not(:last-child)' } },
        { extend: 'csvHtml5',   exportOptions: { columns: ':visible:not(:last-child)' } },
        { extend: 'pdfHtml5',   exportOptions: { columns: ':visible:not(:last-child)' } },
        { extend: 'print',      exportOptions: { columns: ':visible:not(:last-child)' } },
        'colvis'
      ],
      // 👉 ha qarin; kaliya ka dhig aan la order-gareyn & aan la raadin
      columnDefs: [
        { targets: -1, orderable: false, searchable: false }
      ]
    });

    // ===== Date filter (sidaad hore u haysay) =====
    const dateColIndex = 1;
    function applyDateFilter(val){
      if (val) table.column(dateColIndex).search('^'+val+'$', true, false).draw();
      else     table.column(dateColIndex).search('').draw();
    }
    applyDateFilter($('#dateFilter').val());
    $('#dateFilter').on('change', function(){ applyDateFilter(this.value); });
    $('#clearDate').on('click', function(e){ e.preventDefault(); $('#dateFilter').val(''); applyDateFilter(''); });
  });
</script>


</body>
</html>
