<?php $this->load->view('/inc/hygiene_header.php'); ?>
<style>
  /* highlight overdue rows */
  tr.dt-overdue { background:#ffeaea; }
  tr.dt-overdue td { border-top-color:#ffd1d1; }

  /* yarayn spacing buttonnada DT */
  #applications_table { white-space: nowrap; }
  .dt-buttons .btn { margin-right:.25rem; }
  .dt-buttons .btn { color:#fff !important; border-radius:0; }
</style>

<div class="container" style="max-width:1300px;margin-top:16px">
  <h3>Hygiene Follow-ups (Pending)</h3>

  <?php foreach (['success','danger','error','warning','info'] as $k):
        if ($msg = $this->session->tempdata($k)): ?>
    <div class="alert alert-<?= ($k==='danger'||$k==='error')?'danger':$k; ?>">
      <?= $msg; ?>
    </div>
  <?php $this->session->unset_tempdata($k); endif; endforeach; ?>

  <!-- Filters -->
  <div class="row g-2 align-items-end mb-2">
    <div class="col-sm-4 col-md-3">
      <label class="small text-muted mb-1">Class</label>
      <select id="filterClass" class="form-control form-control-sm">
        <option value="">All classes</option>
      </select>
    </div>

    <div class="col-sm-4 col-md-3">
      <label class="small text-muted mb-1">Status / Type</label>
      <select id="filterStatus" class="form-control form-control-sm">
        <option value="">All</option>
        <optgroup label="Type">
          <option value="__TYPE__:Hair">Hair</option>
          <option value="__TYPE__:Nails">Nails</option>
        </optgroup>
        <optgroup label="Follow-up state">
          <option value="__STATE__:Overdue">Overdue</option>
          <option value="__STATE__:Due today">Due today</option>
          <option value="__STATE__:Not due">Not due</option>
        </optgroup>
      </select>
    </div>

    <!-- Bulk bar -->
    <div class="col-sm-12 col-md-6 text-md-end mt-2 mt-md-0">
      <form id="bulkForm" method="post" action="<?= site_url('hygiene/followups/bulk-resolve'); ?>" class="d-inline-flex gap-2 align-items-center">
        <button style="width: 120px; padding: 7px;" id="bulkBtn" type="submit" class="btn btn-sm btn-success rounded-pill" disabled>Bulk Confirm</button>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table id="applications_table" class="table table-striped table-bordered table-sm" style="width:100%">
      <thead>
        <tr>
          <!-- NEW: checkbox header -->
          <th style="width:28px;"><input type="checkbox" id="checkAllRows"></th>
          <th>Student</th>
          <th>Class</th>
          <th>Type</th>
          <th>First flagged</th>
          <!-- <th>Last flagged</th> -->
          <th>Times flagged</th>
          <th>Confirm</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($open)): ?>
        <tr><td colspan="7" class="text-muted">No pending follow-ups. 🎉</td></tr>
      <?php else: foreach ($open as $r): ?>
        <tr>
          <!-- NEW: checkbox per row -->
          <td>
            <input type="checkbox" class="row-check" name="ids[]" form="bulkForm"
                   value="<?= (int)$r['id']; ?>">
          </td>

          <td><?= html_escape($r['student_name']); ?></td>
          <td><?= html_escape($r['class_name']); ?></td>
          <td><?= $r['issue_type']==='HR' ? 'Hair' : 'Nails'; ?></td>
          <td><?= html_escape($r['first_flagged_at']); ?></td>
          <!-- <td><?= html_escape($r['last_flagged_at']); ?></td> -->
          <td><?= (int)$r['times_flagged']; ?></td>
          <td>
            <form method="post" action="<?= site_url('hygiene/followups/resolve'); ?>" class="d-flex gap-1">
              <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
              <button style="width:120px; margin-top:4px; margin-bottom: -10px;" class="btn btn-sm btn-success rounded-pill"
                      onclick="return confirm('Confirm compliance for <?= html_escape($r['student_name']); ?>?');">
                Confirm
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>

<script>
  $(function () {
    // SLA helper (same behavior you had)
    function computeState(typeText, dateStr) {
      var days = (typeText.trim().toLowerCase() === 'hair') ? 3 : 2; // Hair=3, Nails=2
      var base = new Date(dateStr);
      if (isNaN(base.getTime())) return '';
      var due = new Date(base); due.setDate(due.getDate() + days);
      var today = new Date(); today.setHours(0,0,0,0); due.setHours(0,0,0,0);
      if (today.getTime() > due.getTime())  return 'Overdue';
      if (today.getTime() === due.getTime()) return 'Due today';
      return 'Not due';
    }

    var table = $('#applications_table').DataTable({
      scrollX: true,
      autoWidth: false,
      order: [],
      pageLength: 10,
      dom:
        "<'row'<'col-sm-6'B><'col-sm-6'f>>" +
        "<'row'<'col-sm-12'tr>>" +
        "<'row'<'col-sm-5'i><'col-sm-7'p>>",
      buttons: [
        'pageLength',
        { extend:'copy',  exportOptions:{ columns: ':visible' } },
        { extend:'excel', exportOptions:{ columns: ':visible' } },
        { extend:'csv',   exportOptions:{ columns: ':visible' } },
        { extend:'pdf',   exportOptions:{ columns: ':visible' } },
        { extend:'print', exportOptions:{ columns: ':visible' } },
        'colvis'
      ],
      columnDefs: [
        { targets: 0, orderable:false, searchable:false, className:'dt-body-center' }, // checkbox col
        { targets: -1, orderable:false, searchable:false } // Confirm col
      ],
      createdRow: function (row, data) {
        // Columns: 0=checkbox, 1=Student, 2=Class, 3=Type, 4=First flagged, 5=Times, 6=Confirm
        var typeText  = data[3];
        var flaggedOn = data[4];
        var state = computeState(typeText, flaggedOn);
        $(row).attr('data-state', state).attr('data-type', typeText);
        if (state === 'Overdue') $(row).addClass('dt-overdue');
      }
    });

    // === Populate CLASS filter from column 2 ===
    (function fillClassFilter(){
      var seen = {};
      table.column(2).data().each(function(v){
        v = (v || '').toString().trim();
        if (v && !seen[v]) seen[v] = true;
      });
      var classes = Object.keys(seen).sort(function(a,b){ return a.localeCompare(b); });
      var $sel = $('#filterClass');
      classes.forEach(function(c){ $sel.append($('<option/>',{ value:c, text:c })); });
    })();

    // === Custom filtering (class & status/type) ===
    $.fn.dataTable.ext.search.push(function(settings, rowData, dataIndex){
      if (settings.nTable !== document.getElementById('applications_table')) return true;

      var wantClass = $('#filterClass').val();
      var wantStat  = $('#filterStatus').val();

      var rowClass = rowData[2]; // Class
      var rowType  = rowData[3]; // Type
      var node     = table.row(dataIndex).node();
      var rowState = (node && node.getAttribute('data-state')) || '';

      if (wantClass && rowClass !== wantClass) return false;

      if (wantStat) {
        if (wantStat.indexOf('__TYPE__:') === 0) {
          var t = wantStat.split(':')[1];
          if (rowType !== t) return false;
        } else if (wantStat.indexOf('__STATE__:') === 0) {
          var s = wantStat.split(':')[1];
          if (rowState !== s) return false;
        }
      }
      return true;
    });

    $('#filterClass, #filterStatus').on('change', function(){
      table.draw();
      table.columns.adjust();
    });

    // ===== Bulk select & button enable =====
    var $bulkBtn   = $('#bulkBtn');
    var $checkAll  = $('#checkAllRows');

    function refreshBulkBtn(){
      var any = $('.row-check:checked').length > 0;
      $bulkBtn.prop('disabled', !any);
    }

    $checkAll.on('change', function(){
      var checked = this.checked;
      $('.row-check').prop('checked', checked);
      refreshBulkBtn();
    });

    $(document).on('change', '.row-check', function(){
      var total = $('.row-check').length;
      var sel   = $('.row-check:checked').length;
      $checkAll.prop('checked', sel === total);
      refreshBulkBtn();
    });

    table.on('draw', function(){
      refreshBulkBtn();
    });

    // ===== AJAX: Single Confirm =====
    $(document).on('submit', 'form[action$="hygiene/followups/resolve"]', function(e){
      e.preventDefault();
      var $form = $(this);
      var rowEl = $form.closest('tr');

      fetch($form.attr('action'), {
        method: 'POST',
        body: new FormData(this),
        headers: {'X-Requested-With':'XMLHttpRequest'}
      })
      .then(r => r.json())
      .then(res => {
        if (res && res.ok) {
          table.row(rowEl).remove().draw(false); // remove instantly
          refreshBulkBtn();
        } else {
          alert('Failed to confirm this row.');
        }
      })
      .catch(() => alert('Network error.'));
    });

    // ===== AJAX: Bulk Confirm =====
    $('#bulkForm').on('submit', function(e){
      e.preventDefault();

      var ids = $('.row-check:checked').map(function(){ return this.value; }).get();
      if (ids.length === 0) return;

      var fd = new FormData(this);
      ids.forEach(id => fd.append('ids[]', id)); // ensure ids[] exist

      fetch($(this).attr('action'), {
        method: 'POST',
        body: fd,
        headers: {'X-Requested-With':'XMLHttpRequest'}
      })
      .then(r => r.json())
      .then(res => {
        if (res && res.ok) {
          // remove the selected rows
          $('.row-check:checked').each(function(){
            table.row($(this).closest('tr')).remove();
          });
          table.draw(false);
          $('#checkAllRows').prop('checked', false);
          refreshBulkBtn();
        } else {
          alert('Bulk confirm failed.');
        }
      })
      .catch(() => alert('Network error.'));
    });

    table.columns.adjust();
  });
</script>

<!-- Requested helper (safe to include here; used in other pages too) -->
<script>
  function confirmOne(studentId, type, $btn){
    fetch('<?= site_url('hygiene/followups/resolve-by-student-type'); ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: new URLSearchParams({ student_id: studentId, type: type })
    })
    .then(async r => {
      const ct = r.headers.get('content-type') || '';
      if (!r.ok) throw new Error('HTTP '+r.status);
      if (!ct.includes('application/json')) {
        const text = await r.text();
        throw new Error('Non-JSON response: '+text.slice(0,200));
      }
      return r.json();
    })
    .then(res => {
      if (res && res.ok) {
        $btn.prop('disabled', true)
           .removeClass('btn-success').addClass('btn-secondary')
           .text('Done');
        // If you ever render type-specific quick buttons in this table:
        // $btn.closest('tr').fadeOut(150, function(){ $(this).remove(); });
      } else {
        alert(res && res.msg ? res.msg : 'Nothing to confirm or already resolved.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Request failed. Open DevTools → Network → the request to see the response body.');
    });
  }

  // Optional generic hook if you add buttons like:
  // <button class="btn btn-success js-confirm-type" data-id="123" data-type="HR">Hair ✓</button>
  $(document).on('click', '.js-confirm-type', function(e){
    e.preventDefault();
    var $b = $(this);
    if ($b.prop('disabled')) return;
    confirmOne($b.data('id'), $b.data('type'), $b);
  });
</script>
