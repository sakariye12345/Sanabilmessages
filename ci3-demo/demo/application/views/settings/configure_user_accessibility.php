<?php $this->load->view('/inc/settings_header'); ?>
<?php include ("inc/style.php") ?>

<?php include ("inc/add_user_access.php") ?>
<?php include ("inc/edit_user_access.php") ?>
<?php include ("inc/remove_user_access.php") ?>
<?php include ("inc/app_access_level_info.php") ?>

<?php 
if ($user_access_level==0) {
  $show_user_access_level = "No Access";
  }
   else{
    $show_user_access_level = "Level ".$user_access_level;
   
}
 ?>
 


<div class = "container" style="width:100%" >

   <div class="collapse" id="collapse_2">
      <div class="card card-body" style="width: 100%">
         <div class="form-row">
            <div class="col">
              <a  class="btn btn-default btn-sm text-dark rounded-pill" href="<?php echo base_url('dashboard'); ?>"> <strong><i class="fa-solid fa-bars"></i> </strong></a> 

               <span><strong>/</strong></span>
               <a  class="btn btn-default btn-sm text-dark rounded-pill" href="<?php echo base_url('settings'); ?>"> <strong>Settings</strong></a> 
               <span><strong>/</strong></span>
                
               <a  class="btn btn-default btn-sm text-dark rounded-pill" href="<?php echo base_url() . 'settings/user_accessibility/'.$user_data['user']['id'] ?>"> <strong>User Accessibility</strong></a> 


               <span><strong>/</strong></span>

                  <a onclick="location.reload();" class="btn btn-default btn-sm text-dark rounded-pill"  > <strong>configure User Accessibility</strong></a> 
            </div>
         
            
            
         </div>
      </div>
   </div>
 <br>
  
 <title> - User Accessibility </title>
 

<div class = "row   " >
 
                  <div class = "col"  >

                    <h3> <?php echo $app_name; ?>: user accessibility  <small>(<?php echo $user_data['user']['full_name']; ?>)</small>   </h3>
                  
                 
                      
                     
                  </div>
                  <div class = "col"  >
                   
  <button type="button" class="btn btn-default text-dark float-right ml-2 rounded-pill " type="button" data-toggle="collapse" data-target="#collapse_2" aria-expanded="false" aria-controls="collapseExample"> <i class="fa-solid fa-bars"></i></button>

 
<?php if ($app_name=="Inventory"): ?>
  <a type="button" class="btn btn-default text-dark float-right ml-2 rounded-pill " data-toggle="modal" data-target="#inventory_info_modal"  > <i class="fa fa-info-circle"></i> </a>
<?php endif ?>
  
   
 <?php if ($access_exists): ?>
   <a  style=" background-color: #f2f2f2; color: #543e31; font-weight: bold;" href="#" data-toggle="modal" data-target="#remove_user_access_modal"   class="action_button btn btn-sm btn-secondary  rounded-pill float-right ml-2"  href="#">   &nbsp;&nbsp;  Remove Access &nbsp;&nbsp;    </a> 
                      <a  style="width: 100px;  background-color: #f2f2f2; color: #543e31; font-weight: bold;" href="#" data-toggle="modal" data-target="#edit_user_access_modal"   class="action_button btn btn-sm btn-secondary  rounded-pill float-right ml-2"  href="#">    &nbsp;&nbsp;  Edit &nbsp;&nbsp;    </a> 
 <?php endif ?>
  
<?php if (!$access_exists): ?>
       <a  style="width: 100px;  background-color: #f2f2f2; color: #543e31; font-weight: bold;" href="#" data-toggle="modal" data-target="#add_user_access_modal"   class="action_button btn btn-sm btn-secondary  rounded-pill float-right ml-2"  href="#">   &nbsp;&nbsp;  Add &nbsp;&nbsp;    </a> 
  
<?php endif ?>
     

                        
                      
                  </div>
               </div>



 
 <br>
                
   <!--Company Name  -->
                  <div class="card " style="box-shadow:0 0 5px 0 lightgrey;"  >
                     <div class="card-body"  >
                      <br>
                        

             
<div class="form-row">
               

                   <div class="col col-6">

                     <div class="form-group ">
                        <label for="label">Name</label>
                          <input   type="text" class="form-control  "  id="full_name_1" name="full_name_1"   value="<?php echo $user_data['user']['full_name'] ?>" >

                         
                     </div>
                  </div>
 
                  
                                
               </div>

               <div class="form-row">
                <div class="col col-6">
                     <div class="form-group ">
                        <label for="label" >Email</label>
                       <input  type="text" class="form-control  " id="email_1" name="email_1"   value=" <?php echo $user_data['user']['email'] ?>" >
                        

                     </div>
                  </div> 
                </div>
                <div class="form-row">

                 <div class="col col-6">
                     <div class="form-group ">
                        <label for="label" >Access Level</label>
                       <input  type="text" class="form-control  " id="phone_number_1" name="phone_number_1"   value="<?php echo $show_user_access_level;  ?>" >
                        

                     </div>
                  </div>

                   
                                
               </div>
  
                    
     
                                         
                     </div>
                  </div>
                  <!--End -->
  
 


   </div> 

      
    
 
  <script src="<?php echo base_url('assets\inc\js\settings\settings_accessibility.js'); ?>"></script> 
<?php $this->load->view('/inc/footer'); ?>