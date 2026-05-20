<?php include ("inc/dashboard_header.php") ?>


<body style="background-image: url('<?php echo base_url('images/bg.png'); ?>');">

 
<?php  $first_name  = $this->session->userdata('first_name') ; ?>
<?php  $last_name  = $this->session->userdata('last_name') ; ?>
<div class = "container" style="width:100%" >

   <div class="collapse" id="collapse_2">
      <div class="card card-body" style="width: 100%">
         <div class="form-row">
            <div class="col">
               <a   href="<?php echo base_url('contacts'); ?>"class="btn btn-default btn-sm text-dark" > <strong>Registration</strong></a>  
            </div>
            
            <div class="col">
               <a  href="<?php echo base_url('invoicing'); ?>"    class="btn btn-default btn-sm text-dark"  > <strong>Invoicing</strong></a>
            </div>
            
            <div class="col">
               <a  href="<?php echo base_url('services'); ?>"  class="btn btn-default btn-sm text-dark"   > <strong>Services </strong></a>
            </div>
            <div class="col">
               <a  href="<?php echo base_url('purchase_orders'); ?>" class="btn btn-default btn-sm text-dark"   > <strong>Purchase </strong></a>
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
             
            
         </div>
      </div>
   </div>
</div>

 

<br>
<div class = "container" style="width:90%"  >
 


   <div class="card" style="box-shadow:0 0 15px 0 lightgrey;">
      <div class="card-body">
         <div class="bs-example">
            <div>
               <div class = "row" >
                  <div class = "col col-10"  >
                     <h4>Welcome, <?php  echo $first_name.' '.$last_name ; ?></h4>


                  </div>
                  <div class = "col"  >
                     <button type="button" class="btn btn-default text-dark float-right ml-2"> <i class="fa fa-gear"></i></button>

                  </div>
               </div>

            </div>
         </div>
      </div>
   </div>
   <br> 

   <div class="card" style="box-shadow: 0 0 15px 0 lightgrey;">
    <div class="card-body">
        <h4 class="card-title">Applications</h4>
        <br>
        <div class="row">
        
   <!--   <?php
// (optional) permission gating
$CI =& get_instance();
$canRequests = true; // e.g. ($CI->session->userdata('requests_access') == 1);
?>

<?php if ($canRequests): ?>
  <div class="col-md-3">
    <center>
      <a href="<?= site_url('requests/create'); ?>"
         style="background-color:#258d90; border-radius:15px; display:inline-block; position:relative;"
         type="button" class="btn btn-lg">
        <i class="fa fa-file-text-o fa-3x" style="color:#fff;"></i>
      </a>
    </center>
    <p class="card-text" style="text-align:center;"><strong>Requests</strong></p><br>
  </div>
<?php endif; ?>  -->





          

        <div class="col-md-3">
            <center>
                <a href="<?= base_url('hygiene/'); ?>" style="background-color: #9b4f4f; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa fa-box-open fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Hygiene Attendance</strong></p> <br>
        </div>

        <div class="col-md-3">
            <center>
                <a href="<?= site_url('teacher/higaad'); ?>" style="background-color: #9b4f4f; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa fa-box-open fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Higaad Result</strong></p> <br>
        </div>

        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('parents/') ?>" style="background-color: #2b5797; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa-solid fa-id-badge fa-3x"></i></a>

            </center>
            <p style="text-align: center;" class="card-text"><strong>Allowed Parents</strong></p> <br>
        </div>

           <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('contacts/') ?>" style="background-color: #258d90; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa fa-address-book fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Contacts</strong></p> <br>
        </div>

        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('inventory/') ?>" style="background-color: #9b4f4f; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa fa-box-open fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Inventory</strong></p> <br>
        </div>

         <!-- Hygiene Attendance tile -->



        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('pos/') ?>" style="background-color: #000; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fas fa-store-alt fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Point of Sale</strong></p> <br>
        </div>


        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('invoicing/') ?>" style="background-color: #4c7a64; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa fa-file-invoice-dollar fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Invoicing</strong></p> <br>
        </div>


        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('sales/') ?>" style="background-color: #73799b; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fas fa-chart-line fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Sales</strong></p> <br>
        </div>


         <div class="col-md-3">
            <center>
                <a href="<?= site_url('requests/'); ?>" style="background-color: #258d90; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa fa-file-alt fa-3x"></i>
</a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Requests</strong></p> <br>
        </div>
       







        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('messages/') ?>" style="background-color: #626462; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa-solid fa-message fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Messages</strong></p> <br>
        </div>

        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('projects/') ?>" style="background-color: #9b4f4f; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa-solid fa-feather fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Projects</strong></p> <br>
        </div>



        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('purchase_orders/') ?>" style="background-color: #258d90; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa-solid fa-check-to-slot fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Purchase</strong></p> <br>
        </div>
        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('payments/') ?>" style="background-color: #725368; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa-solid fa-credit-card fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Payments</strong></p> <br>
        </div>

        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('hr/') ?>" style="background-color: #007074; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa-solid fa-users fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Human Resources</strong></p> <br>
        </div>

        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('banking/') ?>" style="background-color: #000; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa-solid fa-building-columns fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>banking</strong></p> <br>
        </div>


        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('reports/') ?>" style="background-color: #98543c; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa-solid fa-file fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Reports</strong></p> <br>
        </div>


        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('services/') ?>" style="background-color: #9c8a92; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa-solid fa-puzzle-piece fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Services</strong></p> <br>
        </div>


        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('asset_management/') ?>" style="background-color: #9b4f4f; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa-solid fa-rectangle-list fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Asset Management</strong></p> <br>
        </div>


        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('accounting/') ?>" style="background-color: #4c7a64; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa-solid fa-calculator fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Accounting</strong></p> <br>
        </div>




        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('subscriptions') ?>" style="background-color: #725368; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa-solid fa-leaf fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Subscriptions</strong></p> <br>





        </div>



        <div class="col-md-3">
            <center>
                <a href="<?php echo base_url('dashboard/scan') ?>" style="background-color: #626462; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa-solid fa-barcode fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Scan</strong></p> <br>





<?php if ((int) $this->session->userdata('user_level') !== 1) { ?>

        </div><div class="col-md-3">
            <center>
                <a href="<?php echo base_url('settings/') ?>" style="background-color: #73799b; border-radius: 15px; display: inline-block; position: relative;" type="button" class="btn btn-lg">
                    <i style="color: white;" class="fa-solid fa-gear fa-3x"></i></a>
            </center>
            <p style="text-align: center;" class="card-text"><strong>Settings</strong></p> <br>
        </div>


<?php } ?>



        </div>
        <br>
    </div>
         </div>
      </div>

         </div></div></div></body>
<!-- End Model -->
               <?php include ("inc/footer.php") ?> 
