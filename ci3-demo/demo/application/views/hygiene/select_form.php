<?php $this->load->view('/inc/hygiene_header.php'); ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Hygiene Attendance — Select</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css'); ?>">
</head>
<body class="p-4">
  <h3>Prepare Hygiene Attendance</h3>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error'); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?= $this->session->flashdata('success'); ?></div>
  <?php endif; ?>

  <?= form_open('hygiene/prepare'); ?>
    <div class="mb-3">
      <label class="form-label">Class</label>
      <select name="class_id" class="form-select" required>
        <option value="">-- Select class --</option>
        <?php foreach ($classes as $c): ?>
          <option value="<?= (int)$c['id']; ?>"><?= html_escape($c['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Date</label>
      <input type="date" name="attn_date" value="<?= html_escape($today); ?>" class="form-control" required>
    </div>
    <?= csrf_field(); ?>
    <button type="submit" class="btn btn-primary">Continue</button>
  <?= form_close(); ?>
</body>
</html>
