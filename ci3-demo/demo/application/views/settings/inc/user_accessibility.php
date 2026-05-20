<!-- Stat Model --> 
<div class="modal fade  " id="accessibility_modal"  role="dialog">
   <div class="modal-dialog" role="document">
      <div class="modal-content">
         <div class="modal-header">
            <h4 class="modal-title" >User Accessibility </h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"  >
            <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body"> 
           

              <form  method="post"    >   
                <!--raw-->
              <div class="form-row">
                        <div class="col">
                           <div class="form-group ">
                              <label>User</label>
                            <select id="user_accessibility" name="user_accessibility" class="form-control" required style="width: 100%" >
                              <option value="">Select</option>
                                 <?php if(count($users_level_users)):  ?>
                           <?php   foreach($users_level_users as $row   ): ?>
                           <option value= <?php echo $row ->id ;?>><?php echo $row ->user_info ;?> </option>
                           <?php endforeach; ?>
                           <?php endif; ?>
                               
                            </select>
                           </div>
                        </div>
  
                        
                     </div>
                      
                   
              
              
         </div>
         <div class="modal-footer">
            <button   style="width: 100px; border: 1px solid black; " type="button" class="btn btn-default text-dark btn-sm rounded-pill " data-dismiss="modal">&nbsp;<strong>Close</strong>&nbsp;</button>
            <a style="width: 100px; " onclick="selectUserAccessibility();" class="btn btn-dark btn-sm  rounded-pill "  href="#"> &nbsp;Select&nbsp; </a>
            </form>
         </div>
      </div>
   </div>
</div>
<!-- End Model -->
 
