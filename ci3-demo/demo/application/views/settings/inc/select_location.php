<!-- Stat Model --> 
<div class="modal fade  " id="select_location_modal"  role="dialog">
   <div class="modal-dialog" role="document">
      <div class="modal-content">
         <div class="modal-header">
            <h4 class="modal-title" id="exampleModalLabel">  Select Location  </h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"  >
            <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body">
            <form method="post" id="select_location_form"  >
               <!--raw-->
              <div class="form-row">
                        <div class="col">
                           <div class="form-group ">
                              <label>Location</label>
                            <select id="select_location" name="select_location" class="form-control" required style="width: 100%" >
                              <option value="">Select</option>
                                 <?php if(count($location)):  ?>
                           <?php   foreach($location as $row   ): ?>
                           <option value= <?php echo $row ->id ;?>><?php echo $row ->description ;?> </option>
                           <?php endforeach; ?>
                           <?php endif; ?>
                               
                            </select>
                           </div>
                        </div>
  
                        
                     </div>
                     
         </div>
         <div class="modal-footer">
          
             <button   style="width: 100px; border: 1px solid black; " type="button" class="btn btn-default text-dark btn-sm rounded-pill " data-dismiss="modal">&nbsp;<strong>Close</strong>&nbsp;</button>
             <a style="width: 100px; " onclick="selectLocation();" class="btn btn-dark btn-sm  rounded-pill "  href="#">  <small class="fa fa-checsk"></small>&nbsp;Select&nbsp; </a>  
               </form>   
         </div>
      </div>
   </div>
</div>
<!-- End Model -->
