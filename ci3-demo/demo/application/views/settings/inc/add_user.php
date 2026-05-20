<!-- Stat Model --> 
<div class="modal fade  " id="add_user_modal"  role="dialog">
   <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
      <div class="modal-content">
         <div class="modal-header">
            <h4 class="modal-title" id="exampleModalLabel">  Add New User  </h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"  >
            <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body">
           

              <form  method="post" action="<?php  echo base_url()?>settings/add_user" name="add_user_form" id="add_user_form" onsubmit="return validateUserForm();"   >  
               <!--raw-->
               <div class="form-row">
                        <div class="col">
                           <div class="form-group ">
                              <label>First name</label>
                            <input type="text" class="form-control" name="first_name" id="first_name"   required autocomplete="off" placeholder="Enter First name" >
                           </div>
                        </div>
   <div class="col">
                           <div class="form-group ">
                              <label>Last name </label>
                              <input type="text" class="form-control" name="last_name" id="last_name"   required autocomplete="off" placeholder="Enter Last name" >
                           </div>
                        </div>
                        
                     </div>
              <div class="form-row">
                        <div class="col">
                           <div class="form-group ">
                              <label>Username</label>
                            <input type="text" class="form-control" name="username" id="username"   required autocomplete="off" placeholder="Enter Username" >
                           </div>
                        </div>
   <div class="col">
                           <div class="form-group ">
                              <label>Password </label>
                              <input type="Password" class="form-control" name="user_password" id="user_password"   required autocomplete="off" placeholder="Enter Password" >
                              <small id="password_error" class="form-text text-muted"></small>
                           </div>
                        </div>
                        
                     </div>

                     <div class="form-row">
                      <div class="col">
                           <div class="form-group ">
                              <label>Confirm Password </label>
                              <input type="Password" class="form-control" name="confirm_password" id="confirm_password"   required autocomplete="off" placeholder="Confirm Password" >
                              <small id="confirm_error" class="form-text text-muted"></small>
                               <small id="password_error" class="form-text text-muted"></small>
                           </div>
                        </div>
                         <div class="col">
                           <div class="form-group ">
                              <label>Email</label>
                              <input type="email" name="user_email" id="user_email" class="form-control"       class="form-control"  required autocomplete="off" placeholder="Enter Email"  >
                           </div>
                        </div>
                     
                     
                        
                     </div>

                      <div class="form-row">
                        <div class="col">
                           <div class="form-group ">
                              <label>Phone</label>
                              <input type="text" name="user_phone" id="user_phone" class="form-control"       class="form-control"  required autocomplete="off" placeholder="Enter Phone"  >
                           </div>
                        </div>
                       <div class="col">
                           <div class="form-group ">
                              <label>User Level</label>
                             <select id="user_level" name="user_level" class="form-control" required >
                              <option value="1">User</option>
                              <option value="2">Admin</option>
                           
                            </select>
                           </div>
                        </div>
   
                        
                     </div>

                      <div class="form-row">
                        
                    
 <div class="col">
                           <div class="form-group ">
                              <label>Location</label>
                             <select id="user_location" name="user_location"  class="form-control" required style="width: 100%;" > 
                               <option value="2">Demo</option>
                                  
                             </select>
                           </div>
                        </div>
                       
                         
                     </div>
                      
                
              
         </div>
         <div class="modal-footer">
            <button   style="width: 100px; border: 1px solid black; " type="button" class="btn btn-default text-dark btn-sm rounded-pill " data-dismiss="modal">&nbsp;<strong>Close</strong>&nbsp;</button>
             <button style="width: 100px; " type="submit" value="Add" name="action" class="btn btn-dark  btn-sm  rounded-pill " btn-sm   >
               <small class="fa fa-savse"></small>&nbsp;Add&nbsp; 
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