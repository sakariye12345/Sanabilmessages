<!-- Stat Model --> 
<div class="modal fade  " id="remove_user_access_modal"  role="dialog">
   <div class="modal-dialog" role="document">
      <div class="modal-content">
         <div class="modal-header">
            <h4 class="modal-title" >  Remove  User Access </h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"  >
            <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body">
           

              <form  method="post" name="remove_user_access_form" id="remove_user_access_form"   >   
              <input type="hidden" readonly name="access_id_1" id="access_id_1"   required autocomplete="off" value="<?php echo $access_id; ?>" >
               <h6>Are you sure you want to remove <?php echo $user_data['user']['full_name']; ?>’s access to <?php echo $app_name; ?> application?</h6>
                
              
         </div>
         <div class="modal-footer">
            <button   style="width: 100px; border: 1px solid black; " type="button" class="btn btn-default text-dark btn-sm rounded-pill " data-dismiss="modal">&nbsp;<strong>Cancel</strong>&nbsp;</button>
             <button style="width: 100px; " type="submit" value="Add" name="action" class="btn btn-dark  btn-sm  rounded-pill " > &nbsp;Yes&nbsp; 
               </button>
            </form>
         </div>
      </div>
   </div>
</div>
<!-- End Model -->
 
