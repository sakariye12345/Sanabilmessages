<?php $this->load->view('/inc/settings_header'); ?>
<?php $app_id    =  $this->uri->segment(3); ?>
<?php include ("inc/add_location.php") ?>
<?php include ("inc/style.php") ?>
<div class = "container" style="width:100%" >
   <div class="collapse" id="collapse_2">
      <div class="card card-body" style="width: 100%">
         <div class="form-row">
            <div class="col">
              <a  class="btn btn-default btn-sm text-dark rounded-pill" href="<?php echo base_url('dashboard'); ?>"> <strong><i class="fa-solid fa-home"></i> </strong></a> 

               <span><strong>/</strong></span>
               <a  class="btn btn-default btn-sm text-dark rounded-pill" href="<?php echo base_url('settings'); ?>"> <strong>Settings</strong></a> 
               <span><strong>/</strong></span>
                  <a onclick="location.reload();" class="btn btn-default btn-sm text-dark rounded-pill"  > <strong>Applications Accessibility</strong></a> 
            </div>
         
            
            
         </div>
      </div>
   </div>
 <br>
 
 <title> - <?php echo $app_name ?> Accessibility</title>
<div class = "row" >
                  <div class = "col col-8"  >
                     <h3> 

                      <a style="color: black;"  href="<?php echo base_url() . 'settings/accessibility_applications/'.$app_id ?>">Applications Accessibility  </a>  <small>/ <?php echo $app_name ?>  </small></h3>
                      
                     
                  </div>
                  <div class = "col"  >
                   
  <button type="button" class="btn btn-default text-dark float-right ml-2 rounded-pill" type="button" data-toggle="collapse" data-target="#collapse_2" aria-expanded="false" aria-controls="collapseExample"> <i class="fa-solid fa-bars"></i></button>

                     
                 
                      
                  </div>
               </div>



 
 <br>
                
  <div class="table-resposive">
      <table id="applications_table" class="table table-striped table-bordered  " style="width:100%">
         <thead>
            <tr id="table_row">
                 <th>#</th>
                <th> Full Name</th>
                <th> Email</th>
                <th> Access Level</th>
      
        
              
            </tr>
         </thead>
          <tbody>
  
 <tr >
   <?php if (count($app_data)): ?>
               <?php foreach($app_data as $row): ?>
           <td><?php echo $row->sno ; ?></td>
           <td><?php echo $row->full_name ; ?></td>
           <td><?php echo $row->email ; ?></td>
            <td><?php echo 'Level '.$row->access_level ; ?></td>
         
              
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
               <td colspan="4"> <center>No records found
</center>  </td>
            </tr>
            <?php endif; ?>

</tbody>
         <tfoot>
            <tr  class="bg-secondary text-light"   >
               <th colspan="4" style="width: 20px;"></th>
               
            </tr>
         </tfoot>
      </table>
   </div>






   </div>

 <script>
    //*********** Data tables 
   $(document).ready(function(){
   var table = $('#applications_table').DataTable({
  
      "order": [],
        "scrollX": true,
       order: [0, 'asc'],
   
   
   mark: true,
         dom: 'Bfrtip',
        lengthMenu: [
             [ 10, 25, 50, 100, -1],
             ['10 rows', '25 rows', '50 rows', '100 rows', 'Show All']
         ],
   
   
   
   
   
         buttons: [
             'pageLength',
             {
                 extend: 'copyHtml5',
                 exportOptions: {
                     columns: ':visible'
                 }
             },
             {
                 extend: 'excelHtml5',
                 exportOptions: {
                     columns: ':visible'
                 }
             },
             {
                 extend: 'csvHtml5',
                 exportOptions: {
                     columns: ':visible'
                 }
             },
             
             {
                 extend: 'pdfHtml5',
                // download: 'open',
                 exportOptions: {
                     columns: ':visible'
                 }
   
             },
             {
                 extend: 'print',
                 exportOptions: {
                     columns: ':visible'
                 }
             },
             'colvis'
         ],
         columDefs: [{
             targets: -1,
             visible: false
         }]
   
   
   }); //End DT
   
   });  //end of document ready 
 </script>
   
 
<?php $this->load->view('/inc/footer'); ?>