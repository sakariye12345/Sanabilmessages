<?php $this->load->view('/inc/hygiene_header.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Hygiene Attendance</title>

  <!-- Dooro HAL bootstrap: CDN ama assets-kaaga. Halkan CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root{ --bg:#f8f9fa; --ink:#222; --muted:#6c757d; --card:#fff; --border:#dee2e6; }
    body{font-family:'Segoe UI',sans-serif;background:var(--bg);color:var(--ink)}
    .container{max-width:980px}
    .actions{display:flex;align-items:center;gap:.75rem;margin:1rem 0}
    .actions h1{font-size:1.8rem;margin:0;margin-right:auto;line-height:1.1}
    .card{background:var(--card);border:1px solid var(--border)}
    .row-item{display:grid;grid-template-columns:80px 1fr 240px;gap:.75rem;align-items:center;padding:.5rem 0;border-bottom:1px solid #f1f1f1}
    .row-item:last-child{border-bottom:0}
    @media (max-width: 576px){
      .row-item{grid-template-columns:60px 1fr 160px}
      .actions{flex-wrap:wrap}
      .actions h1{flex:0 0 100%;font-size:1.2rem;margin-bottom:.25rem}
      .width{ width:165px; margin-left: -45px}
      .width2{  width:110px }
      .width3{ margin-left:100px }
    }
  </style>
</head>
<body>
<main class="container py-3">

  <!-- Flash messages (ikhtiyaari) -->
  <?php foreach (['success'=>'success','danger'=>'danger','error'=>'danger','warning'=>'warning','info'=>'info'] as $k=>$bs): ?>
    <?php if ($msg = $this->session->tempdata($k)): ?>
      <div class="alert alert-<?= $bs; ?> alert-dismissible fade show" role="alert">
        <?= $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php $this->session->unset_tempdata($k); endif; ?>
  <?php endforeach; ?>

  <!-- FORM start -->
  <?= form_open('hygiene/store', ['id' => 'hygieneForm']); ?>
    <input type="hidden" name="class_id"  value="<?= (int)$class_id; ?>">
    <input type="hidden" name="edit_mode" value="1"> <!-- ← si store u ogyahay Tani waa EDIT -->
    <input type="hidden" name="attn_date" value="<?= html_escape($attn_date); ?>">
    <?= csrf_field(); ?>

    <!-- Top actions -->
    <div class="actions">
      <h1>Edit Hygiene Attendance</h1>

      <button type="submit" class="btn btn-dark rounded-pill px-4">Submit</button>
      <a href="<?= site_url('hygiene/view_attendances'); ?>" class="btn btn-outline-secondary rounded-pill px-4">Back</a>

      <!-- Taariikhda -->
      
    </div>

    <!-- Info cards -->
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <div class="card p-3">
          <label class="form-label fw-bold">School</label>
          <input type="text" class="form-control" value="Schools Demo" disabled>
          <label class="form-label fw-bold mt-2">Class</label>
          <input type="text" class="form-control" value="<?= html_escape($class_name); ?>" disabled>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card p-3">
          <label class="form-label fw-bold">Date</label>
          <input type="date" class="form-control" value="<?= html_escape($attn_date); ?>" disabled>
          <small class="text-muted mt-2"></small>
        </div>
      </div>
    </div>

    <!-- Students table -->
    <div class="card p-3">
      <div class="d-flex fw-bold mb-2" style="gap:.75rem;">
        <div class="" style="width:80px;">No.</div>
        <div class="flex-grow-1 ">Name</div>
        <div class="width3" style="width:240px;">Status</div>
      </div>

      <?php foreach ($students as $s):
        $sid  = (int)$s['id'];
        $roll = (int)$s['attendance_no'];
        $name = $s['full_name'];
        $prev = isset($existing[$sid]) ? $existing[$sid] : '';
      ?>
        <div class="row-item">
          <div><strong><?= $roll; ?></strong></div>
          <div>
            <input  type="text" class="form-control width" value="<?= html_escape($name); ?>" readonly>
          </div>
          <div>
            <select class="form-select status-select width2" name="status[<?= $sid; ?>]">
  <option value="NR" <?= $prev==='NR'?'selected':''; ?>>Normal</option>
  <option value="NL" <?= $prev==='NL'?'selected':''; ?>>Nails</option>
  <option value="HR" <?= $prev==='HR'?'selected':''; ?>>Hair</option>
  <option value="BH" <?= $prev==='BH'?'selected':''; ?>>Both</option>
</select>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  <?= form_close(); ?>
  <!-- FORM end -->

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
