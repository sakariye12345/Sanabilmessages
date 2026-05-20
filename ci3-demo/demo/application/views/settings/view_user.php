<?php $this->load->view('/inc/settings_header'); ?>
<?php include ("inc/style.php") ?>
 <?php 
  if ($user_data['user']['status']==1) {
   $show_status = "Active";
  }else{
    $show_status = "Inactive";
  }
  if ($user_data['user']['user_level']==1) {
   $level = "User";
  }else{
    $level = "Admin";
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
               <a  class="btn btn-default btn-sm text-dark rounded-pill" href="<?php echo base_url('settings/users'); ?>"> <strong>Users</strong></a> 
               <span><strong>/</strong></span>

                  <a onclick="location.reload();" class="btn btn-default btn-sm text-dark rounded-pill"  > <strong>View User</strong></a> 


                  <?php    
 $twofa = $this->main_model->get_user_2fa($user_data['user']['id']);
 ?>


 <?php if (empty($twofa)): ?>
  <a href="<?= base_url('twofactorauth/enable_2fa/'.$user_data['user']['id']) ?>"
     class="btn btn-sm btn-primary float-right ml-2">
    Enable 2FA
  </a>
<?php else: ?>
   
  <a href="<?= base_url('twofactorauth/reset_2fa/'.$user_data['user']['id']) ?>"
     class="btn btn-sm btn-warning float-right ml-2"
     onclick="return confirm('Are you sure you want to reset 2FA for this user?');">
    Reset 2FA
  </a>
<?php endif; ?>





            </div>
         
            
            
         </div>
      </div>
   </div>
 <br>
  <div id="messages"></div>
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
 <title> - View User</title>



 

<div class = "row   " >
                  <div class = "col"  >
                     <h3> View User </h3>
                      
                     
                  </div>
                  <div class = "col col-8"  >
                   
  <button type="button" class="btn btn-default text-dark float-right ml-2 rounded-pill " type="button" data-toggle="collapse" data-target="#collapse_2" aria-expanded="false" aria-controls="collapseExample"> <i class="fa-solid fa-bars"></i></button>




 


 <?php if (empty($twofa)): ?>
  <a href="<?= base_url('twofactorauth/enable_2fa/'.$user_data['user']['id']) ?>"
     class="btn btn-sm btn-primary float-right ml-2">
    Enable 2FA
  </a>
<?php else: ?>
  <span class="badge badge-success float-right ml-2">
    2FA Enabled
  </span>
 
<?php endif; ?>









  <?php if ($this->session->userdata('id') != $user_data['user']['id']  ): ?>
    <?php if ($user_data['user']['status']==1): ?>
      <a href="#" data-toggle="modal" data-target="#deactivate_user_modal"   id="table_button"   class="btn btn-sm btn-secondary  rounded-pill float-right ml-2"  href="#">  <small class="fa fa-edist  "></small>&nbsp;&nbsp;  Deactivate  &nbsp;&nbsp;    </a> 
    <?php endif ?>
     
     <?php endif ?>

      <?php if ($this->session->userdata('id') != $user_data['user']['id']  ): ?>
    <?php if ($user_data['user']['status']==0): ?>
      <a href="#" data-toggle="modal" data-target="#activate_user_modal"   id="table_button"   class="btn btn-sm btn-secondary  rounded-pill float-right ml-2"  href="#">  <small class="fa fa-edist  "></small>&nbsp;&nbsp;  Activate  &nbsp;&nbsp;    </a> 
    <?php endif ?>
     
     <?php endif ?>



 <a href="#" data-toggle="modal" data-target="#tokenModal"   id="table_button"   class="btn btn-sm btn-secondary  rounded-pill float-right ml-2"  href="#">  <small class="fa fa-edist  "></small>&nbsp;&nbsp;  Token Details  &nbsp;&nbsp;    </a> 


    
 

 <a  style="  background-color: #f2f2f2; color: #543e31; font-weight: bold;" href="#" id="generate_token"    class="action_button btn btn-sm btn-secondary  rounded-pill float-right ml-2"  href="#">  <small class="fa fa-edits  "></small>&nbsp;&nbsp;  Generate Token &nbsp;&nbsp;    </a> 

 
 
                      <a  style="width: 100px;  background-color: #f2f2f2; color: #543e31; font-weight: bold;" href="#" data-toggle="modal" data-target="#edit_user_modal"   class="action_button btn btn-sm btn-secondary  rounded-pill float-right ml-2"  href="#">  <small class="fa fa-edits  "></small>&nbsp;&nbsp;  Edit &nbsp;&nbsp;    </a> 

  <?php if($this->session->flashdata('success')): ?>
                
             <a onclick="history.back()" style="width: 100px;" href="#"    id="table_button"   class="btn btn-sm btn-secondary  rounded-pill float-right ml-2"  href="#">  <small class="fa fa-edits  "></small>&nbsp;&nbsp;  Done &nbsp;&nbsp;    </a> 
               <?php endif; ?>

                        
                      
                  </div>
               </div>



 
 <br>
                
   <!--Company Name  -->
                  <div class="card " style="box-shadow:0 0 5px 0 lightgrey;"  >
                     <div class="card-body"  >
                      <br>
                        <div class="form-row">
                           <div class="col">

                     <div class="form-group ">
                        <label for="label">User ID</label>
                          <input   type="text" class="form-control  "  id="user_id_1" name="user_id_1"   value="<?php echo $user_data['user']['id'] ?>" >

                         
                     </div>
                  </div> 
                  <div class="col">
                     <div class="form-group ">
                        <label for="label" >Username</label>
                       <input  type="text" class="form-control  " id="username_1" name="username_1"   value="<?php echo $user_data['user']['username'] ?>">
                        

                     </div>
                  </div>

                  
                                
               </div>

               <!--input   type="text" class="form-control  "  id="description_1" name="description_1"   value="<?php echo $user_data['user']['description'] ?>" -->
<div class="form-row">
                  

                   <div class="col">

                     <div class="form-group ">
                        <label for="label">Full name</label>
                          <input   type="text" class="form-control  "  id="full_name_1" name="full_name_1"   value="<?php echo $user_data['user']['full_name'] ?>" >

                         
                     </div>
                  </div>
 <div class="col">
                     <div class="form-group ">
                        <label for="label" >Email</label>
                       <input  type="text" class="form-control  " id="email_1" name="email_1"   value=" <?php echo $user_data['user']['email'] ?>" >
                        

                     </div>
                  </div> 
                  
                                
               </div>

               <div class="form-row">
                 <div class="col">
                     <div class="form-group ">
                        <label for="label" >Phone</label>
                       <input  type="text" class="form-control  " id="phone_number_1" name="phone_number_1"   value="<?php echo $user_data['user']['phone_number'] ?>" >
                        

                     </div>
                  </div>

                  <div class="col">

                     <div class="form-group ">
                        <label for="label">Location</label>
                          <input  type="text" class="form-control  "  id="location_1" name="location_1" value="<?php echo $user_data['user']['description'] ?>" >

                         
                     </div>
                  </div>  
                                
               </div>
  <div class="form-row">
                 <div class="col">
                     <div class="form-group ">
                        <label for="label" >User Level</label>
                       <input  type="text" class="form-control  " id="user_level_1" name="user_level_1"   value="<?php echo $level; ?>" >
                        

                     </div>
                  </div>

                  <div class="col">

                     <div class="form-group ">
                        <label for="label">Status</label>
                          <input  type="text" class="form-control  "  id="status_1" name="status_1"   value=" <?php echo $show_status; ?>" >

                         
                     </div>
                  </div>  
                                
               </div>
                    
     <div class="form-row">
                 <div class="col">
                     <div class="form-group ">
                        <label for="label" >Created at</label>
                       <input  type="text" class="form-control  " id="created_at" name="created_at"  value="<?php echo $user_data['user']['created_at'] ?>" >
                        

                     </div>
                  </div>

                  <div class="col">

                     <div class="form-group ">
                        <label for="label">Updated at</label>
                          <input  type="text" class="form-control  "  id="status_1" name="status_1" value="<?php echo $user_data['user']['updated_at'] ?>" >

                         
                     </div>
                  </div>  
                                
               </div>
 

                     </div>
                  </div>
                  <!--End -->
  
 


   </div> 

     <?php include ("inc/edit_user.php") ?>
     <?php if ($this->session->userdata('id') != $user_data['user']['id']  ): ?>
      <?php include ("inc/deactivate_user.php") ?>
       <?php include ("inc/activate_user.php") ?>
     <?php endif ?>










<!-- Button to trigger the modal -->


<!-- Modal -->
<div class="modal fade" id="tokenModal" tabindex="-1" role="dialog" aria-labelledby="tokenModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="tokenModalLabel">Token Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="modal-body">
                <div class="form-row">
                    <div class="col">
                        <div class="form-group">
                            <label for="token">Token</label>
                            <input type="text" class="form-control" id="created_at" name="created_at" value="<?php echo $user_data['user']['token']; ?>" readonly>
                        </div>
                    </div>
                  </div>
                   <div class="form-row">
                    <div class="col">
                        <div class="form-group">
                            <label for="endpoint">End Point 1</label>
                            <input readonly type="text" class="form-control" id="status_1" name="status_1" value="<?php echo base_url('messages/contacts'); ?>">
                        </div>
                    </div>
                </div>



<div class="form-row">
                    <div class="col">
                        <div class="form-group">
                            <label for="endpoint">End Point2</label>
                            <input readonly type="text" class="form-control" id="status_1" name="status_1" value="<?php echo base_url('messages/update_status'); ?>">
                        </div>
                    </div>
                </div>

            </div>
            
            <!-- Modal Footer -->
            <div class="modal-footer">
  <button   style="width: 100px; border: 1px solid black; " type="button" class="btn btn-default text-dark btn-sm rounded-pill " data-dismiss="modal">&nbsp;<strong>Close</strong>&nbsp;</button>                
            </div>
        </div>
    </div>
</div>



<script>
    $(document).ready(function () {
        $('#generate_token').click(function () {
            // Make sure the user ID is dynamically retrieved
            const userId = "<?php echo $this->uri->segment(3); ?>";

            if (!userId) {
                alert('User ID is missing!');
                return;
            }

            $.ajax({
                url: "<?php echo base_url('settings/generate_token'); ?>",
                method: "POST",
                data: { user_id: userId },
                dataType: "json",
                success: function (response) {
                    if (response.status === 'success') {
                        $('#api_token').val(response.token);
                        alert('Token generated successfully!');
                        location.reload();
                    } else {
                        alert(response.message || 'Failed to generate token.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    alert('Error: Token already generated!.');
                }
            });
        });
    });
</script>


 
  <script src="<?php echo base_url('assets\inc\js\settings\settings_users.js'); ?>"></script> 
<?php $this->load->view('/inc/footer'); ?>