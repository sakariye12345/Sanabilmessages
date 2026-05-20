<?php include ("inc/dashboard_2_header.php") ?>
<?php  $first_name  = $this->session->userdata('first_name') ; ?>
<?php  $last_name  = $this->session->userdata('last_name') ; ?>
 
 
<div class = "container" style="width:100%" >
     <div class="collapse" id="collapse_2">
      <div class="card card-body" style="width: 100%">
         <div class="form-row">
            <div class="col">
               <a   href="<?php echo base_url('contacts'); ?>"class="btn btn-default btn-sm text-dark" > <strong>Contacts</strong></a>  
            </div>
            <div class="col">
               <a href="<?php echo base_url('inventory'); ?>" class="btn btn-default btn-sm text-dark"  > <strong>Inventory</strong></a>
            </div>
            <div class="col">
               <a  href="<?php echo base_url('pos'); ?>"  class="btn btn-default btn-sm text-dark"   > <strong>Point of Sale</strong></a>
            </div>
            <div class="col">
               <a  href="<?php echo base_url('invoicing'); ?>"    class="btn btn-default btn-sm text-dark"  > <strong>Invoicing</strong></a>
            </div>
            <div class="col">
               <a href="<?php echo base_url('sales'); ?>"  class="btn btn-default btn-sm text-dark"   > <strong>Sales</strong></a>
            </div>
            <div class="col">
               <a  href="<?php echo base_url('services'); ?>"  class="btn btn-default btn-sm text-dark"   > <strong>Services </strong></a>
            </div>
            <div class="col">
               <a  href="<?php echo base_url('purchase_orders'); ?>" class="btn btn-default btn-sm text-dark"   > <strong>Purchase Orders</strong></a>
            </div>
            <div class="col">
               <a href="<?php echo base_url('payments'); ?>" class="btn btn-default btn-sm text-dark"  href="#"> <strong>Payments</strong></a>
            </div>
            <div class="col">
               <a href="<?php echo base_url('reports'); ?>"  class="btn btn-default btn-sm text-dark "  href="#"> <strong>Reports</strong></a>
            </div>
            <div class="col">
               <a href="<?php echo base_url('banking'); ?>" class="btn btn-default btn-sm text-dark"  href="#"> <strong>Banking</strong></a>
            </div>
            <div class="col">
               <a href="<?php echo base_url('emr'); ?>"  class="btn btn-default btn-sm text-dark"  href="#"> <strong>EMR</strong></a>
            </div>
            
         </div>
      </div>
   </div>
 
 <br>
 
 
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
        
         
     
       


<div id="card" class="card mx-auto "   >
  <div class="card-body" style="box-shadow:0 0 25px 0 lightgrey;">
          <br/>



 <label > <h3 align="left" > &nbsp; Change Password</h3>  </label>
<br/>

    <?php if($this->session->flashdata('success')): ?>
               <div class="alert alert-success alert-dismissible" role="alert">
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                  <?php echo $this->session->flashdata('success'); ?>
               </div>
               <?php elseif($this->session->flashdata('error')): ?>
               <div class="alert alert-error alert-dismissible" role="alert">
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                  <?php echo $this->session->flashdata('error'); ?>
               </div>
               <?php endif; ?>

                <?php if($this->session->flashdata('info')): ?>
               <div style="background-color: #d4edda; color:#294b31" class="alert alert-info alert-dismissible" role="alert">
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                  <?php echo $this->session->flashdata('info'); ?>
               </div>
               <?php elseif($this->session->flashdata('error')): ?>
               <div class="alert alert-error alert-dismissible" role="alert">
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                  <?php echo $this->session->flashdata('error'); ?>
               </div>
               <?php endif; ?>
                <?php if($this->session->flashdata('danger')): ?>
               <div class="alert alert-danger alert-dismissible" role="alert">
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                  <?php echo $this->session->flashdata('danger'); ?>
               </div>
               <?php elseif($this->session->flashdata('error')): ?>
               <div class="alert alert-error alert-dismissible" role="alert">
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                  <?php echo $this->session->flashdata('error'); ?>
               </div>
               <?php endif; ?>
<form method="post" action="<?php  echo base_url()?>dashboard/update_pws" 
     onsubmit="return validate()" >


  <div class="form-group">
    <label>Old Password</label>
    <input required  type="password" class="form-control" name="old_password"  id="old_password"    required    >
    
     
  </div>

  <div class="form-group">
    <label>New Password</label>
    <input required type="password" class="form-control" name="new_password" id="new_password" required    >
      
  </div>
  
<div class="form-group">
    <label>Confirm</label>
    <input required type="password" class="form-control" name="confirm" id="confirm"   required >
      
  </div>
  

  <button style="width: 100px;" type="submit" name="change" class="btn btn-dark rounded-pill myButton"> &nbsp;</i>Change</button>
          <small></small> <br/>
 
  <?php echo '<label class="text-danger"> '.$this->session->flashdata("error"). '</label> ';
  

  ?>
<script>
$(document).ready(function() {
  $(".myForm").submit(function() {
    $(".myButton").attr("disabled", true);
    $(".myButton").val("Please wait...");
  });
});
</script>



</form>


       <div class = " card-footer"  > <a href="#">   </a> </div>
      </div><!--  end of card body-->

  </div><!--  end of card-->

    </div> <!--  end container-->
      <script type="text/javascript">
  if ( window.history.replaceState ) {
  window.history.replaceState( null, null, window.location.href );
}
  
</script>    
<script>
  
   function validate(){

    var new_pass = $('#new_password').val();
    var confirm_pass = $('#confirm').val();

    // create a regex variable to test the password criteria
   // var regex = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[0-9a-zA-Z]{8,}$/;
     var regex = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[0-9a-zA-Z@#$?%^&]{8,}$/;

    // check if the passwords match and meet the criteria
    if (regex.test(new_pass) && regex.test(confirm_pass) && new_pass == confirm_pass) {

      // password is valid and matches
      // ask for confirmation before changing the password
      if (confirm('Are you sure you want to change your password?')==true) {
        // change the password and display a success message
        console.log("Password changed successfully");
        return true;
      }else{
        // cancel the password change and return false
        return false;
      }
      
    } else {

      // password is invalid or does not match
      // display an alert message and clear the input fields
      alert('Error: Password did not match or meet the criteria!\n The Password must contain at least 8 characters, one digit, one lowercase letter, one uppercase letter, and any of the symbols @#$%^& ');
      $('#confirm').val("");
      $('#new_password').val("");

      // add a red border to the input fields
      $("#confirm").addClass("border-danger");
      $("#new_password").addClass("border-danger");
      return false;
    }

 }

</script>

<script>
  (function () {
  'use strict'

  // Fetch all the forms we want to apply custom Bootstrap validation styles to
  var forms = document.querySelectorAll('.needs-validation')

  // Loop over them and prevent submission
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {

        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }

        form.classList.add('was-validated')
      }, false)
    })
})()
 

</script>

 </div>
 
 <?php $this->load->view('/inc/footer'); ?>