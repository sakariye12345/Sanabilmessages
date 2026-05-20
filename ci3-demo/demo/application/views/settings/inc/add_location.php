<!-- Stat Model --> 
<div class="modal fade  " id="add_location_modal"  role="dialog">
   <div class="modal-dialog" role="document">
      <div class="modal-content">
         <div class="modal-header">
            <h4 class="modal-title" id="exampleModalLabel">  Add New School  </h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"  >
            <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body">
           

              <form  method="post" action="<?php  echo base_url()?>settings/add_location" name="add_location_form" id="add_location_form"  class="needs-validation" novalidate >  
               <!--raw-->
              <div class="form-row">
                        <div class="col">
                           <div class="form-group ">
                              <label> Type</label>
                            <select id="location_type" name="location_type" class="form-control" required >
                              <option value="">Select</option>
                                 <?php if(count($location_type)):  ?>
                           <?php   foreach($location_type as $row   ): ?>
                           <option value= <?php echo $row ->id ;?>><?php echo $row ->type ;?> </option>
                           <?php endforeach; ?>
                           <?php endif; ?>
                               
                            </select>
                           </div>
                        </div>
   <div class="col">
                           <div class="form-group ">
                              <label>Description </label>
                              <input type="text" class="form-control" name="location_description" id="location_description"   required autocomplete="off" placeholder="Enter Description" >
                           </div>
                        </div>
                        
                     </div>
                     <div class="form-row">
                     
                         <div class="col">
                           <div class="form-group ">
                              <label>Code </label>
                              <input type="text" class="form-control" name="location_number" id="location_number"   required  autocomplete="off"  >
                           </div>
                        </div>
                     
                     
                        <div class="col">
                           <div class="form-group ">
                              <label>Address</label>
                              <input type="text" name="location_address" id="location_address" class="form-control"       class="form-control"  required autocomplete="off"   >
                           </div>
                        </div>
                     </div>
                      <div class="form-row">
                        <div class="col">
                           <div class="form-group ">
                              <label>City/Town</label>
                             <select id="location_city" name="location_city" class="form-control" required >
                              <option value="">Select</option>
                               <?php if(count($city)):  ?>
                           <?php   foreach($city as $row   ): ?>
                           <option value= <?php echo $row ->city_id ;?>><?php echo $row ->city_name ;?> </option>
                           <?php endforeach; ?>
                           <?php endif; ?>
                            </select>
                           </div>
                        </div>
                    

                      <div class="col">
                           <div class="form-group ">
                              <label>Status</label>
                             <select id="location_status" name="location_status" class="form-control" required >
                              <option value="1">Active</option>
                              <option value="0">Inactive</option>
                           
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

