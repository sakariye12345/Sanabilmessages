<!-- Stat Model --> 
<div class="modal fade  " id="edit_user_modal"  role="dialog">
   <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
      <div class="modal-content">
         <div class="modal-header">
            <h4 class="modal-title" id="exampleModalLabel">  Edit User  </h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"  >
            <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body">
           

              <form  method="post" action="<?php  echo base_url()?>settings/update_user" name="update_user_form" id="update_user_form"   >   <input type="hidden" class="form-control" name="user_id" id="user_id"   required autocomplete="off" value="<?php echo $user_data['user']['id'] ?>" >
               <!--raw-->
               <div class="form-row">
                        <div class="col">
                           <div class="form-group ">
                              <label>First name</label>
                            <input type="text" class="form-control" name="first_name" id="first_name"   required autocomplete="off"  value="<?php echo $user_data['user']['first_name'] ?>" >
                           </div>
                        </div>
   <div class="col">
                           <div class="form-group ">
                              <label>Last name </label>
                              <input type="text" class="form-control" name="last_name" id="last_name"   required autocomplete="off" value="<?php echo $user_data['user']['last_name'] ?>">
                           </div>
                        </div>
                        
                     </div>
              <div class="form-row">
                        <div class="col">
                           <div class="form-group ">
                              <label>Username</label>
                            <input type="text" class="form-control" name="username" id="username"   required autocomplete="off" value="<?php echo $user_data['user']['username'] ?>" >
                           </div>
                        </div>
    
                          <div class="col">
                           <div class="form-group ">
                              <label>Email</label>
                              <input type="email" name="user_email" id="user_email" class="form-control"       class="form-control"  required autocomplete="off" value="<?php echo $user_data['user']['email'] ?>"  >
                           </div>
                        </div>
                     
                     </div>
 
                     

                      <div class="form-row">
                        <div class="col">
                           <div class="form-group ">
                              <label>Phone</label>
                              <input type="text" name="user_phone" id="user_phone" class="form-control"       class="form-control"  required autocomplete="off" value="<?php echo $user_data['user']['phone_number'] ?>" >
                           </div>
                        </div> 
                       
    <div class="col">
                           <div class="form-group ">
                              <label>User Level  </label>
              <select id="user_level" name="user_level" class="form-control" required >
 <option value="<?php echo $user_data['user']['user_level'] ?>"><?php echo $level; ?></option>
<?php if ($user_data['user']['user_level']==1): ?>
  <option value="2">Admin</option>
<?php endif ?>
<?php if ($user_data['user']['user_level']==2): ?>
  <option value="1">User</option>
<?php endif ?>
                             
                              
                           
                            </select>
                           </div>
                        </div>
                        



<?php   
if ($user_data['user']['mobile']  == 1 ) {
  $mobile = 'Yes';
}
else{
   $mobile = 'No';
}

 ?>

 <div class="col">
                           <div class="form-group ">
                              <label>Mobile </label>
              <select id="mobile" name="mobile" class="form-control" required >
 <option value="<?php echo $user_data['user']['mobile'] ?>"><?php echo $mobile; ?></option>

 <option value="0">No</option>
           
       <option value="1">Yes</option>                        
                           
                            </select>
                           </div>
                        </div>








                     </div>
 
                      <div class="form-row">
                        
                   
 
                        <div class="col">
                           <div class="form-group ">
                              <label>Location</label>
                             <select id="user_location" name="user_location"  class="form-control" required style="width: 100%;" > 
                               <option value="<?php echo $user_data['user']['location_id'] ?>"><?php echo $user_data['user']['description'] ?></option>
                                   
                             </select>
                           </div>
                        </div>  
                     </div>
                      
                
              
         </div>
         <div class="modal-footer">
            <button   style="width: 100px; border: 1px solid black; " type="button" class="btn btn-default text-dark btn-sm rounded-pill " data-dismiss="modal">&nbsp;<strong>Close</strong>&nbsp;</button>
             <button style="width: 100px; " type="submit" value="Add" name="action" class="btn btn-dark  btn-sm  rounded-pill " > &nbsp;Update&nbsp; 
               </button>
            </form>
         </div>
      </div>
   </div>
</div>
<!-- End Model -->
<script type="text/javascript">
  $(document).ready(function() {
  $("input#username").on({
  keydown: function(e) {
    if (e.which === 32)
      return false;
  },
  change: function() {
    this.value = this.value.replace(/\s/g, "");
  }
});
  });
</script>

