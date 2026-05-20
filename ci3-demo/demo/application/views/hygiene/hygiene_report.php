<?php $this->load->view('/inc/hygiene_header.php'); ?>

 <?php include ("inc/select_report.php") ?>
 <?php include ("inc/select_student.php") ?>
 



<div class="container py-3">
  <!-- Flash messages -->
  <?php
    $map = ['success'=>'success','danger'=>'danger','error'=>'danger','warning'=>'warning','info'=>'info'];
    foreach ($map as $key=>$bs):
      $msg = $this->session->tempdata($key);
      if ($msg):
  ?>
    <div class="row justify-content-center">
      <div class="col-12 col-sm-11 col-md-8 col-lg-6">
        <div class="alert alert-<?= $bs; ?> alert-dismissible fade show mb-3" role="alert">
          <?= $msg; ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </div>
    </div>
  <?php
        $this->session->unset_tempdata($key);
      endif;
    endforeach;
  ?>

  <div class="row justify-content-center">
    <div class="col-12 col-sm-11 col-md-8 col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h4 class="card-title mb-4  text-md-left">Hygiene Attendance</h4>

          <!-- Prepare -->
     <button type="button"
        class="btn btn-light text-dark btn-lg w-100 rounded-pill mb-3"
        style="background:#e2e6ea"
        data-toggle="modal"
        data-target="#filter_modal">
  Class Summary Report
</button>


          <!-- View -->
           <button type="button"
        class="btn btn-light text-dark btn-lg w-100 rounded-pill mb-3"
        style="background:#e2e6ea"
        data-toggle="modal"
        data-target="#Student_modal">
  Student Hygiene Report
</button>
<a style="background:#e2e6ea"
 class="btn btn-light text-dark btn-lg w-100 rounded-pill mb-3"
  href="<?= site_url('hygiene/followups'); ?>">
  Follow-ups
</a>
        </div>
      </div>
    </div>
  </div>
</div>