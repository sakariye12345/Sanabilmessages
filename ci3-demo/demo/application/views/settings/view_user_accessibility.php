<?php $this->load->view('/inc/settings_header'); ?>
<?php include ("inc/user_accessibility.php") ?>
<?php include ("inc/style.php") ?>
<div class = "container" style="width:100%" >
   <div class="collapse" id="collapse_2">
      <div class="card card-body" style="width: 100%">
         <div class="form-row">
            <div class="col">
              <a  class="btn btn-default btn-sm text-dark rounded-pill" href="<?php echo base_url('dashboard'); ?>"> <strong><i class="fa-solid fa-bars"></i> </strong></a> 

               <span><strong>/</strong></span>
               <a  class="btn btn-default btn-sm text-dark rounded-pill" href="<?php echo base_url('settings'); ?>"> <strong>Settings</strong></a> 
               <span><strong>/</strong></span>
                  <a onclick="location.reload();" class="btn btn-default btn-sm text-dark rounded-pill"  > <strong>User Accessibility</strong></a> 
            </div>
         
            
            
         </div>
      </div>
   </div>
 <br>
 
 <title> - User Accessibility</title>
<div class = "row" >
                  <div class = "col"  >
                     <h3> User Accessibility - <small><?php echo $full_name; ?></small></h3>
                      
                     
                  </div>
                  <div class = "col"  >
                   
  <button type="button" class="btn btn-default text-dark float-right ml-2 rounded-pill" type="button" data-toggle="collapse" data-target="#collapse_2" aria-expanded="false" aria-controls="collapseExample"> <i class="fa-solid fa-bars"></i></button>

      <a style=" background-color: #f2f2f2; color: #543e31;
     font-weight: bold;" href="#" data-toggle="modal" data-target="#accessibility_modal"   class="btn btn-sm btn-secondary  rounded-pill float-right ml-2"  href="#"> </small>&nbsp;&nbsp;  Change user &nbsp;&nbsp;    </a> 
                       
                      
                  </div>
               </div>



 
 <br>
                
  <div class="table-resposive">
      <table id="user_accessibility_table" class="table table-striped table-bordered  " style="width:100%">
         <thead>
            <tr id="table_row">
                <th style="width: 65px;"  >#</th>
                 <th>Application </th>
               
               <th style="width: 200px;">Select</th>
              
            </tr>
         </thead>
         <tbody>
  
 <tr >
   <?php if (count($app_data)): ?>
               <?php foreach($app_data as $row): ?>
           <td><?php echo $row->id ; ?></td>
           <td><?php echo $row->app_name ; ?></td>
           <td><a id="table_button"   class="btn btn-sm btn-secondary  rounded-pill"  href="<?php echo base_url() . 'settings/configure_user_accessibility/'.$user_id.'/'.$row->id ?>">&nbsp;&nbsp;  Select &nbsp;&nbsp;</a></td>
              
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
               <td colspan="8"> <center>No records found
</center>  </td>
            </tr>
            <?php endif; ?>

</tbody>
         <tfoot>
            <tr  class="bg-secondary text-light"   >
               <th  style="width: 20px;"></th>
               
            </tr>
         </tfoot>
      </table>
   </div>






   </div>

 
   
 
  <script src="<?php echo base_url('assets\inc\js\settings\settings_accessibility.js'); ?>"></script> 
<?php $this->load->view('/inc/footer'); ?>