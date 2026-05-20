<!-- Stat Model --> 
<div class="modal fade  " id="edit_user_access_modal"  role="dialog">
   <div class="modal-dialog modal-dialog-scrollable" role="document">
      <div class="modal-content">
         <div class="modal-header">
            <h4 class="modal-title" id="exampleModalLabel">  Edit User Access  </h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"  >
            <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body">
           

              <form  method="post"  id="edit_user_access_form" name="edit_user_access_form">  
               <!--raw-->

        
                      <div class="form-row">

 <input type="hidden" class="form-control"  name="access_id" id="access_id" value="<?php echo $access_id; ?>">

          <input type="hidden" class="form-control"  name="user_id_1" id="user_id_1" value="<?php echo $user_data['user']['id']; ?>">

          <input type="hidden" class="form-control" name="app_id_1" id="app_id_1" value="<?php echo $application_id; ?>">
          <input type="hidden" class="form-control" name="full_name_user_access_1" id="full_name_user_access_1"   required autocomplete="off"  value="<?php echo $user_data['user']['full_name'] ?>" >

          <input type="hidden" class="form-control" name="app_name_1" id="app_name_1"   required autocomplete="off"  value="<?php echo $app_name ?>" >

                       <div class="col">
                           <div class="form-group ">
                              <label>Access Level 
 
                          </label>
                             <select id="access_level_1" name="access_level_1" class="form-control" required >
                               
                              <option value="<?php echo $user_access_level; ?>"><?php echo "Level ".$user_access_level ?></option>

<?php if ($user_access_level==1): ?>
      <option value="2">Level 2</option>
      <?php if ($app_access_levels>2 ): ?>
      <option value="3">Level 3</option>
                  <?php endif ?>             
     <?php if ($app_access_levels>3 ): ?>
     <option value="4">Level 4</option>
      <?php endif ?>  
<?php endif ?>

<?php if ($user_access_level==2): ?>
      <option value="1">Level 1</option>
      <?php if ($app_access_levels>2 ): ?>
      <option value="3">Level 3</option>
                  <?php endif ?>             
     <?php if ($app_access_levels>3 ): ?>
     <option value="4">Level 4</option>
      <?php endif ?>  
<?php endif ?>


<?php if ($user_access_level==3): ?>
      <option value="1">Level 1</option>
      <option value="2">Level 2</option>            
     <?php if ($app_access_levels>3 ): ?>
     <option value="4">Level 4</option>
      <?php endif ?>  
<?php endif ?>

<?php if ($user_access_level==4): ?>
      <option value="1">Level 1</option>
      <option value="2">Level 2</option>            
     <option value="3">Level 3</option>
     
<?php endif ?>

                             
                            </select>
                           </div>
                        </div>
   
                        
                     </div>

                      
                
              
         </div>
         <div class="modal-footer">
            <button   style="width: 100px; border: 1px solid black; " type="button" class="btn btn-default text-dark btn-sm rounded-pill " data-dismiss="modal">&nbsp;<strong>Close</strong>&nbsp;</button>
             <button style="width: 100px; " type="submit" value="Add" name="action" class="btn btn-dark  btn-sm  rounded-pill " btn-sm   >
               <small class="fa fa-savse"></small>&nbsp;Update&nbsp; 
               </button>
            </form>
         </div>
      </div>
   </div>
</div>
 

