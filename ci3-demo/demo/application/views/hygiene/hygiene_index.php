<?php $this->load->view('/inc/hygiene_header.php'); ?>

 <?php include ("inc/select_form.php") ?>

 



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
          <h4 class="card-title mb-4 text-center text-md-left">Hygiene Attendance</h4>

         <!-- Prepare -->
          <a href="#"
             data-toggle="modal"
             data-target="#add_user_modal"
             class="btn btn-light text-dark btn-lg w-100 rounded-pill mb-3"
             style="background-color:#e2e6ea;">
            Prepare Hygiene Attendance
          </a>

          <!-- View hygiene attendance -->
          <a href="<?= site_url('hygiene/view_attendances'); ?>"
             class="btn btn-light text-dark btn-lg w-100 rounded-pill"
             style="background-color:#e2e6ea;">
            View Hygiene Attendance
          </a>

          <!-- Higaad result (teacher side) -->
          <a href="<?= site_url('teacher/higaad'); ?>"
             class="btn btn-light text-dark btn-lg w-100 rounded-pill mt-3"
             style="background-color:#e2e6ea;">
            Higaad Result
          </a>

          <!-- Higaad submitted classes (admin side) -->
          <a href="<?= site_url('admin/higaad/submitted'); ?>"
             class="btn btn-light text-dark btn-lg w-100 rounded-pill mt-3"
             style="background-color:#e2e6ea;">
            Higaad Submitted Classes
          </a>

        </div>
      </div>
    </div>
  </div>
</div>