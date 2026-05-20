<!-- Stat Model --> 
<div class="modal fade  " id="activate_user_modal"  role="dialog">
   <div class="modal-dialog" role="document">
      <div class="modal-content">
         <div class="modal-header">
            <h4 class="modal-title" id="exampleModalLabel1">  Activate User  </h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"  >
            <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body">
           

              <form  method="post" name="activate_user_form" id="activate_user_form"   >   
              <input type="hidden" readonly name="user_id_3" id="user_id_3"   required autocomplete="off" value="<?php echo $user_data['user']['id'] ?>" >
               <h6>Are you sure you want to activate this user?</h6>
                
              
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
 
