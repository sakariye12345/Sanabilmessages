<?php $this->load->view('/inc/hygiene_header.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hygiene Report</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet"
        href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet"
        href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
  <style>
    body{ font-family: 'Segoe UI', sans-serif; }
    .filters{ display:flex; flex-wrap:wrap; gap:.75rem; align-items:flex-end; margin-bottom:12px; }
    .filters .form-group{ display:flex; flex-direction:column; }
    table.dataTable thead th{ background:#fff; color:#000; }
    .btn-group-sm .btn{ padding:.25rem .5rem; }
  </style>
</head>
<body>
<div class="container" style="width:100%">
  <h3>Hygiene Report</h3>

  <?php foreach (['success','danger','error','warning','info'] as $k):
        if ($msg = $this->session->tempdata($k)): ?>
    <div class="alert alert-<?= ($k==='danger'||$k==='error')?'danger':$k; ?>">
      <?= $msg; ?>
    </div>
  <?php $this->session->unset_tempdata($k); endif; endforeach; ?>

  <table id="hygieneReport" class="display nowrap table table-striped table-bordered" style="width:100%;">
    <thead>
      <tr>
        <th>ID</th><th>Name</th><th>Class</th>
        <!-- <th>Normal</th> -->
        <th>Confirm</th>
        <th>Nails</th><th>Hair</th><th>Both</th>
        <th>Marked days</th>
      </tr>
    </thead>
   <tbody>
<?php foreach (($rows ?? []) as $r):

  // IDs & names (fallbacks kept safe)
  $sid        = isset($r['student_id']) ? (int)$r['student_id'] : (int)($r['id'] ?? 0);
  $full_name  = isset($r['full_name'])  ? $r['full_name']  : ($r['student_name'] ?? '');
  $class_name = isset($r['class_name']) ? $r['class_name'] : ($r['class'] ?? '');

  // Support BOTH models:
  // attendance totals (old): total_nr, total_nl, total_hr, total_bh
  // compliance totals (new): conf_hr, conf_nl, conf_both, total_confirmed
  $nl = isset($r['total_nl'])   ? (int)$r['total_nl']   : (int)($r['conf_nl']    ?? 0);
  $hr = isset($r['total_hr'])   ? (int)$r['total_hr']   : (int)($r['conf_hr']    ?? 0);
  $bh = isset($r['total_bh'])   ? (int)$r['total_bh']   : (int)($r['conf_both']  ?? 0);

  // Marked days:
  // - compliance: distinct days => total_confirmed
  // - attendance: sum of daily marks (fallback to sum)
  if (array_key_exists('total_confirmed', $r)) {
      $marked = (int)$r['total_confirmed'];
  } else {
      $marked = (int)($r['total_nr'] ?? 0) + (int)($r['total_nl'] ?? 0)
              + (int)($r['total_hr'] ?? 0) + (int)($r['total_bh'] ?? 0);
  }
?>
  <tr data-student="<?= $sid; ?>">
    <td><?= $sid; ?></td>
    <td><?= html_escape($full_name); ?></td>
    <td><?= html_escape($class_name); ?></td>

    <!-- Confirm cell (AJAX) -->
    <td>
      <div class="btn-group btn-group-sm" role="group" aria-label="Confirm">
        <button class="btn btn-success js-confirm" data-id="<?= $sid; ?>" data-type="HR">Hair ✓</button>
        <button class="btn btn-success js-confirm" data-id="<?= $sid; ?>" data-type="NL">Nails ✓</button>
        <button class="btn btn-secondary js-confirm-both" data-id="<?= $sid; ?>">Both ✓</button>
      </div>
    </td>

    <!-- Totals -->
    <td><?= $nl; ?></td>
    <td><?= $hr; ?></td>
    <td><?= $bh; ?></td>
    <td><?= $marked; ?></td>
  </tr>
<?php endforeach; ?>
</tbody>
  </table>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
$(function(){
  $('#hygieneReport').DataTable({
    scrollX: true,
    pageLength: 100,
    lengthChange: false,
    dom: 'Bfrtip',
    buttons: ['pageLength','copy','excel','csv','pdf','print','colvis'],
    order: [[2,'asc'],[0,'asc']]
  });

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
      if(res && res.ok){
        $btn.prop('disabled', true)
           .removeClass('btn-success').addClass('btn-secondary')
           .text('Done');
      } else {
        alert(res && res.msg ? res.msg : 'Nothing to confirm or already resolved.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Network/response error. Open DevTools → Network → this request to see why.');
    });
  }

  $(document).on('click', '.js-confirm', function(e){
    e.preventDefault();
    const $b = $(this);
    if ($b.prop('disabled')) return;
    confirmOne($b.data('id'), $b.data('type'), $b);
  });

  $(document).on('click', '.js-confirm-both', function(e){
    e.preventDefault();
    const $b  = $(this);
    const sid = $b.data('id');
    confirmOne(sid, 'HR', $b);
    confirmOne(sid, 'NL', $b);
  });
</script>
</body>
</html>
