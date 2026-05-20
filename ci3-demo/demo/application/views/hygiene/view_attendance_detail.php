<?php $this->load->view('/inc/hygiene_header.php'); ?>
<?php
  // CSRF for CI3
  $csrf_name = $this->security->get_csrf_token_name();
  $csrf_hash = $this->security->get_csrf_hash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Hygiene Attendance</title>
  
  <style>
    /*  👉 waa isla styling kii prepare.php */
    :root{
      --bg:#f8f9fa; --ink:#222; --muted:#6c757d;
      --card:#fff; --border:#dee2e6;
    }
    *{box-sizing:border-box}
    body{font-family:'Segoe UI',sans-serif;background:var(--bg);color:var(--ink)}
    .container{padding:1.25rem;max-width:900px;margin:0 auto}
    .actions{display:flex;gap:.75rem;align-items:center;margin:0 0 1rem}
    .actions h1{font-size:1.5rem;margin:0;margin-right:auto}
    
    .circle-btn{padding:.5rem .75rem;border-radius:10%;border:0;background:#eee}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem}
    .card{background:var(--card);border:1px solid var(--border);border-radius:0;padding:1rem}
    .card label{font-weight:700;margin-top:.5rem}
    .card input,.card select{width:100%;margin-top:.3rem;padding:.5rem;border:1px solid #ccc;border-radius:3px}
    .table-card{background:var(--card);border:1px solid var(--border);border-radius:0;padding:1rem}
    .students-header{display:grid;grid-template-columns:72px 1fr 220px;gap:.75rem;font-weight:700;margin-bottom:.5rem}
    .row-item{display:grid;grid-template-columns:72px 1fr 220px;gap:.75rem;align-items:center;padding:.4rem 0;border-bottom:1px solid #f1f1f1;}
    .row-item:last-child{border-bottom:0}
    .name-input{background:#edf0f2}
    .form-select{font-size:.95rem;padding:.5rem .75rem;border-radius:3px;border:1px solid #ccc}
  </style>

  <style>
/* ===== Students: responsive layout ===== */

/* Desktop / large */
.table-card .students-header{
  display:grid;
  grid-template-columns:72px 1fr 220px;
  gap:.75rem;
  font-weight:700;
  margin-bottom:.5rem;
}
.table-card .row-item{
  display:grid;
  grid-template-columns:72px 1fr 220px;
  gap:.75rem;
  align-items:center;
  padding:.5rem 0;
  border-bottom:1px solid #f1f1f1;
}
.table-card .row-item:last-child{ border-bottom:0; }

/* Inputs look nice everywhere */
.table-card .name-input{
  background:#edf0f2;
}

/* ===== Tablet (≤ 768px) ===== */
@media (max-width: 768px){
  .table-card .students-header{
    grid-template-columns:64px 1fr 180px;
    gap:.6rem;
  }
  .table-card .row-item{
    grid-template-columns:64px 1fr 180px;
    gap:.6rem;
  }
}

/* ===== Mobile (≤ 520px) — one row per student ===== */
@media (max-width: 520px){
  /* keep header slim (or set display:none to hide it) */
  .table-card .students-header{
    grid-template-columns:48px 1fr 130px;
    gap:.5rem;
    font-size:.95rem;
  }

  /* one-line compact row */
  .table-card .row-item{
    grid-template-columns:48px 1fr 130px;
    gap:.5rem;
    padding:.45rem 0;
  }

  /* tighten controls */
  .table-card .name-input{
    height:34px;
    padding:.3rem .5rem;
    font-size:.95rem;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }
  .table-card .status-select{
    height:34px;
    padding:.25rem .5rem;
    font-size:.95rem;
  }

  /* checkbox/number column */
  .table-card .row-item .col-roll input[type="checkbox"]{
    width:18px; height:18px; vertical-align:middle; margin-right:.25rem;
  }
  .table-card .row-item .col-roll strong{
    font-size:.95rem;
  }
  .width{ width:150px; margin-left:-25px;   }
  .width2{  width:100px }
}
</style>

</head>
<body>

<main class="container">
  <div class="actions">
    <h1>Edit Hygiene Attendance</h1>
    <?php
      $any_id  = isset($a['any_id']) ? (int)$a['any_id'] : 0;
      $classId = isset($a['class_id']) ? (int)$a['class_id'] : 0;
      $date    = isset($a['date']) ? $a['date'] : '';

      $editUrl = $any_id > 0
          ? site_url('hygiene/edit_class_attendance/'.$any_id)                               // by id
          : site_url('hygiene/edit_class_attendance/'.$classId.'/'.rawurlencode($date));     // fallback
    ?>
    <a href="<?= site_url('hygiene/edit_class_attendance/'.(int)$class_id.'/'.rawurlencode($attn_date)); ?>"
       class="btn btn-dark rounded-pill" style="width:100px;">
      Edit
    </a>

    <a style="width:100px" class="btn btn-dark rounded-pill" href="<?= site_url('hygiene/view_attendances'); ?>" class="circle-btn" title="Back">Back</a>

    <!-- Delete ALL for this class/date (CSRF included) -->
    <form action="<?= site_url('hygiene/attendance/delete-batch'); ?>"
          method="post"
          style="margin-left:.5rem"
          onsubmit="return confirm('Delete ALL attendance for class <?= (int)$class_id; ?> on <?= html_escape($attn_date); ?> ? This cannot be undone.');">
      <input type="hidden" name="class_id"  value="<?= (int)$class_id; ?>">
      <input type="hidden" name="attn_date" value="<?= html_escape($attn_date); ?>">
      <input type="hidden" name="<?= $csrf_name; ?>" value="<?= $csrf_hash; ?>">
      <button type="submit" class="btn btn-danger rounded-pill" style="width:120px;margin-top: 14px">Delete</button>
    </form>
  </div>

  <?= form_open('hygiene/store', ['id' => 'hygieneForm']); ?>

  <!-- Top bar -->
  <input type="hidden" name="class_id" value="<?= (int)$class_id; ?>">
  <?= csrf_field(); ?>

  <!-- Info -->
  <section class="form-row">
    <div class="card">
      <label>School</label>
      <input type="text" value="Schools Demo" disabled />
      <label>Class</label>
      <input type="text" value="<?= html_escape($class_name); ?>" disabled />
    </div>
    <div class="card">
      <label>Date</label>
      <input type="date" name="attn_date" value="<?= html_escape($attn_date); ?>" required />
    </div>
  </section>

  <!-- Students -->
  <section class="table-card">
    <div class="students-header">
      <div><input type="checkbox" id="checkAll" /> All</div>
      <div>Name</div>
      <div>Status</div>
    </div>

    <?php foreach ($students as $s):
      $sid  = (int)$s['id'];
      $roll = (int)$s['attendance_no'];
      $name = $s['full_name'];
      $prev = isset($existing[$sid]) ? $existing[$sid] : '';
    ?>
      <div class="row-item" data-row-id="<?= $sid; ?>">
        <div class="col-roll"><strong><?= $roll; ?></strong></div>
        <div class="col-name">
          <input  type="text" class="form-control name-input width" value="<?= html_escape($name); ?>" readonly />
        </div>
        <?php $readonly = true; ?>
        <div class="col-status">
          <select class="form-select status-select width2" name="status[<?= $sid; ?>]">
            <option value="NR" <?= $prev==='NR'?'selected':''; ?>>Normal</option>
            <option value="NL" <?= $prev==='NL'?'selected':''; ?>>Nails</option>
            <option value="HR" <?= $prev==='HR'?'selected':''; ?>>Hair</option>
            <option value="BH" <?= $prev==='BH'?'selected':''; ?>>Both</option>
          </select>
        </div>
      </div>
    <?php endforeach; ?>
  </section>

  <?= form_close(); ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const checkAll  = document.getElementById('checkAll');
  const submitBtn = document.getElementById('submitBtn');
  const $$ = sel => Array.from(document.querySelectorAll(sel));

  function setAcceptable(sel){
    for(let i=0;i<sel.options.length;i++){
      const o = sel.options[i];
      if((o.value||'').toUpperCase()==='AC'){ sel.selectedIndex=i; return; }
    }
  }
  function setAllStatusesAcceptable(){ $$('select[name^="status["]').forEach(setAcceptable); }

  if (checkAll){
    checkAll.addEventListener('change', function(){
      if(this.checked){ setAllStatusesAcceptable(); }
    });
  }

  if (submitBtn){
    submitBtn.addEventListener('click', function(){
      const form=document.getElementById('hygieneForm');
      if(form) form.submit();
    });
  }
});
</script>

</body>
</html>
