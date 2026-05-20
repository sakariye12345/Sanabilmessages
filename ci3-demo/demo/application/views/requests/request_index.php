<?php $this->load->view('/inc/requests_header.php'); ?>

<div class="container" style="width:100%">
  <br>
  <div class="row">
    <div class="col">
      <div class="card" style="box-shadow:0 0 5px 0 lightgrey;">
        <div class="card-body">
          <h4 class="card-title">Actions</h4>
          <!-- New Request -> form.php (RequestsCreate::index) -->
          <a href="<?= site_url('requests/create'); ?>"
             class="btn btn-light text-dark btn-lg btn-block rounded-pill"
             style="background-color:#e2e6ea">New Request</a>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card" style="box-shadow:0 0 5px 0 lightgrey;">
        <div class="card-body">
          <h4 class="card-title">View</h4>
          <!-- Requests list -> views/requests/index.php (Requests::list) -->
          <a href="<?= site_url('requests/list'); ?>"
             class="btn btn-light text-dark btn-lg btn-block rounded-pill"
             style="background-color:#e2e6ea">Requests</a>
        </div>
      </div>
    </div>
  </div>
</div>
