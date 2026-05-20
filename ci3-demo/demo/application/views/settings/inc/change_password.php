<!-- Stat Model --> 
<div class="modal fade  " id="change_password_modal"  role="dialog">
   <div class="modal-dialog" role="document">
      <div class="modal-content">
         <div class="modal-header">
            <h4 class="modal-title" id="exampleModalLabel1">  Change User Password </h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"  >
            <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body">
           

              <form  method="post" name="change_password_form" id="change_password_form"   >   
                <!--raw-->
              <div class="form-row">
                        <div class="col">
                           <div class="form-group ">
                              <label>User</label>
                            <select id="user_id_4" name="user_id_4" class="form-control" required style="width: 100%" >
                              <option value="">Select</option>
                                 <?php if(count($users)):  ?>
                           <?php   foreach($users as $row   ): ?>
                           <option value= <?php echo $row ->id ;?>><?php echo $row ->user_info ;?> </option>
                           <?php endforeach; ?>
                           <?php endif; ?>
                               
                            </select>
                           </div>
                        </div>
  
                        
                     </div>
                     <div class="form-row">
                      <div class="col">
                           <div class="form-group ">
                              <label> Password </label>
                              <input type="Password" class="form-control" name="change_password" id="change_password"   required autocomplete="off" placeholder="Enter new password" >
                              <small id="confirm_error" class="form-text text-muted"></small>
                               <small id="change_password_error" class="form-text text-muted"></small>
                           </div>
                        </div>
                      </div>
                     <div class="form-row">
                      <div class="col">
                           <div class="form-group ">
                              <label>Confirm Password </label>
                              <input type="Password" class="form-control" name="confirm_change_password" id="confirm_change_password"   required autocomplete="off" placeholder="Confirm password" >
                              <small id="confirm_password_error" class="form-text text-muted"></small>
                             
                           </div>
                        </div>
                      </div>
              
              
         </div>
         <div class="modal-footer">
            <button   style="width: 100px; border: 1px solid black; " type="button" class="btn btn-default text-dark btn-sm rounded-pill " data-dismiss="modal">&nbsp;<strong>Close</strong>&nbsp;</button>
             <button style="width: 100px; " type="submit" value="Add" name="action" class="btn btn-dark  btn-sm  rounded-pill " > &nbsp;Change&nbsp; 
               </button>
            </form>
         </div>
      </div>
   </div>
</div>
<!-- End Model -->
 
