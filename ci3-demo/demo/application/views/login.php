<?php include ("inc/header3.php") ?>

<body style="background-image: url('<?php echo base_url('assets/images/bglogin.png'); ?>'); background-size: cover; background-position: center; background-repeat: no-repeat;">


<div class="container" style="width: 100%">
   <style>
#card {
        width: 44% !important;
      }
  @media (max-width: 800px) {
      #card {
        width: 100% !important;
      }
    }
</style>   
        
         
     
        <center>
          
       <img src="<?php echo base_url('assets/images/login_logo.png'); ?>" style="width: 240px;" class="card-img-top mx-auto" alt="..." >

    

        </center>
         
 <br>
        

 <center><h5>
   Demo app
 </h5> </center>
<style></style>

<div id="card" class="card mx-auto">
  <div class="card-body" style="box-shadow:0 0 25px 0 lightgrey;">
    <br/>

    <label><h3 align="left">&nbsp; Login</h3></label>
    <br/>

    <form method="post" action="<?php echo base_url() ?>home/auth" class="some thi myForm">
      <div class="form-group">
        <label>Username</label>
        <input type="text" class="form-control" name="username" id="username" placeholder="Username" value="<?= set_value('username') ?>">
        <small class="text-danger">
          <?php echo form_error("username"); ?>
        </small>
      </div>

      <div class="form-group">
        <label>Password</label>
        <div class="input-group">
          <input type="password" class="form-control" name="password" id="password" placeholder="Password" value="<?= set_value('password') ?>">
          <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="button" id="showPasswordToggle">
              <i class="fa fa-eye"></i>
            </button>
          </div>
        </div>
        <small class="text-danger">
          <?php echo form_error("password"); ?>
        </small>
      </div>

      
      <?= csrf_field(); ?>

      <button style="width: 100px;" type="submit" name="insert" class="btn btn-dark rounded-pill myButton"><i class="fa fa-lock">&nbsp;</i>Login</button>
      <small></small> <br />

      <?php echo '<label class="text-danger">' . $this->session->flashdata("error") . '</label>'; ?>

      <script>
        $(document).ready(function() {
          $(".myForm").submit(function() {
            $(".myButton").attr("disabled", true);
            $(".myButton").val("Please wait...");
          });

          $("#showPasswordToggle").click(function() {
            var passwordInput = $("#password");
            var passwordFieldType = passwordInput.attr("type");

            if (passwordFieldType === "password") {
              passwordInput.attr("type", "text");
            } else {
              passwordInput.attr("type", "password");
            }
          });
        });
      </script>
    </form>

    <div class="card-footer"><a href="#"></a></div>
  </div><!-- end of card body-->

</div><!-- end of card-->

</div><!-- end container-->
<script type="text/javascript">
  if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
  }
</script>

<?php include ("inc/footer1.php") ?>
