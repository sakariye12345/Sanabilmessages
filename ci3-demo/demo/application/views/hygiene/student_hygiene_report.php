<?php $this->load->view('/inc/hygiene_header.php'); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Student Hygiene Report</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
  .card input[readonly], .card .form-control[readonly]{ background:#f6f7f9 }
  .stat-label{ color:#6c757d; }
</style>
</head>
<body class="bg-light">

<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h3 class="mb-0">Student Hygiene Report</h3>
      <div class="text-muted">From: <?= html_escape($from_date) ?> &nbsp; To: <?= html_escape($to_date) ?></div>
    </div>
   <form class="d-flex gap-2" method="get"
      action="<?= site_url('hygiene/student/'.$student['id']) ?>">
  <input type="date" name="from" class="form-control"
         value="<?= html_escape($from_date) ?>" required>
  <input type="date" name="to" class="form-control"
         value="<?= html_escape($to_date) ?>" required>
  <button class="btn btn-dark">Filter</button>
</form>
  </div>

  <!-- top cards -->
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card p-3 h-100">
        <div class="mb-2 fw-semibold">Student</div>
        <div class="row g-3">
          <div class="col-4"><div class="stat-label">ID</div>
            <input class="form-control" value="<?= (int)$student['id'] ?>" readonly></div>
          <div class="col-8"><div class="stat-label">Name</div>
            <input class="form-control" value="<?= html_escape($student['full_name']) ?>" readonly></div>
          <div class="col-12"><div class="stat-label">School</div>
            <input class="form-control" value="Schools Demo" readonly></div>
          <div class="col-6"><div class="stat-label">Class</div>
            <input class="form-control" value="<?= html_escape($student['class_name']) ?>" readonly></div>
          <div class="col-6"><div class="stat-label">Phone</div>
            <input class="form-control" value="<?= html_escape($student['phone'] ?? '') ?>" readonly></div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card p-3 h-100">
        <div class="mb-2 fw-semibold">Totals (Selected Period)</div>
        <div class="row g-3">
          <div class="col-6"><div class="stat-label">Normal (NR)</div>
            <input class="form-control" value="<?= (int)($summary['total_nr'] ?? 0) ?>" readonly></div>
          <div class="col-6"><div class="stat-label">Nails (NL)</div>
            <input class="form-control" value="<?= (int)($summary['total_nl'] ?? 0) ?>" readonly></div>
          <div class="col-6"><div class="stat-label">Hair (HR)</div>
            <input class="form-control" value="<?= (int)($summary['total_hr'] ?? 0) ?>" readonly></div>
          <div class="col-6"><div class="stat-label">Both (BH)</div>
            <input class="form-control" value="<?= (int)($summary['total_bh'] ?? 0) ?>" readonly></div>
          <div class="col-12"><div class="stat-label">Marked Days</div>
            <input class="form-control" value="<?= (int)($summary['total_marked'] ?? 0) ?>" readonly></div>
        </div>
      </div>
    </div>
  </div>

  <!-- detail table -->
  <div class="card p-3 mt-4">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <h5 class="mb-0">Daily Hygiene</h5>
    </div>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:140px">Date</th>
            
            <th style="width:120px">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($rows)): foreach ($rows as $r): ?>
            <tr>
              <td><?= html_escape($r['attn_date']) ?></td>
              
              <td>
                <?php
                  $map = ['NR'=>'Normal','NL'=>'Nails','HR'=>'Hair','BH'=>'Both'];
                  echo html_escape($map[$r['status']] ?? $r['status']);
                ?>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="3" class="text-muted">No hygiene entries in this period.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
