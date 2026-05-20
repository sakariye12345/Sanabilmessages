<?php $this->load->view('/inc/settings_header'); ?>

<body style="background-image: url('<?php echo base_url('images/bglogin.png'); ?>'); background-size: cover; background-position: center; background-repeat: no-repeat;">

<div class="container" style="width: 100%;">
  <style>
    #card {
      width: 44% !important;
      min-height: 360px;       /* Match your login card height */
      display: flex;
      align-items: center;     /* Vertically center content */
    }
    #card .card-body {
      width: 100%;
    }
    @media (max-width: 800px) {
      #card {
        width: 100% !important;
        min-height: auto;
        display: block;
      }
    }
  </style>

  <center>
     <img style="width: 240px;" src="<?php echo base_url('/images/sanabil_main_logo.png'); ?>" class="card-img-top mx-auto" alt="..."><br/><small>Education</small><br>


    <small> </small><br>
  
  </center>
  <br>

  <div id="card" class="card mx-auto">
    <div class="card-body" style="box-shadow:0 0 25px 0 lightgrey;">
      <div style="width:100%">
        <h3 class="mb-4">&nbsp;Enable Two-Factor Authentication</h3>

        <p>Scan this with Google Authenticator:</p>
        <img src="<?php echo html_escape($qrCodeUrl); ?>" alt="QR Code"><br><br>

        <p>Or enter this code manually:</p>
        <pre><?php echo html_escape($secret); ?></pre>

        <p><a href="<?php echo site_url('settings/view_user/'.$user['id']); ?>">← Back to user</a></p>
      </div>
    </div><!-- /.card-body -->
  </div><!-- /.card -->
</div><!-- /.container -->

<?php $this->load->view('/inc/footer'); ?>
