 <?php $this->load->view('/inc/header3'); ?>
 
<body style="background-image: url('<?php echo base_url('images/bglogin.png'); ?>'); background-size: cover; background-position: center; background-repeat: no-repeat;">

<div class="container" style="width: 100%;">
  <style>
    #card {
      width: 44% !important;
      /* match the login card’s height: */
      min-height: 360px;        /* ← adjust this value until it lines up */
      display: flex;
      align-items: center;      /* vertically center the form */
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
   <br> 
  </center>
  <br>

  <div id="card" class="card mx-auto">
    <div class="card-body" style="box-shadow:0 0 25px 0 lightgrey;">
      <div style="width:100%">
        <h3 class="mb-4">&nbsp;Two-Factor Verification</h3>

        <?php if ($this->session->flashdata('error')): ?>
          <div class="alert alert-danger">
            <?php echo $this->session->flashdata('error'); ?>
          </div>
        <?php endif; ?>

        <form method="post" action="<?php echo site_url('home/verify_2fa'); ?>" class="myForm">
          <div class="form-group">
            <label for="code">Enter 6-digit code</label>
            <input
            autocomplete="off"
              type="number" 
            
              class="form-control"
              name="code"
              id="code"
              maxlength="6"
              placeholder="Enter Code"
              required
              autofocus
            >
            <small class="text-danger">
              <?php echo form_error('code'); ?>
            </small>
          </div>

         <button style="width: 100px;" type="submit" class="btn btn-dark rounded-pill myButton">
  <i class="fa fa-lock"></i>&nbsp;Verify
</button>
        </form>
      </div>
    </div><!-- /.card-body -->
  </div><!-- /.card -->
</div><!-- /.container -->

<script>
  $(function(){
    $('.myForm').on('submit', function(){
      $('.myButton')
        .attr('disabled', true)
        .text('Please wait...')
        .css('width', '115px');
    });
  });
</script>

 

<?php $this->load->view('/inc/footer1'); ?>
