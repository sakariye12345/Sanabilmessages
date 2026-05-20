<?php $this->load->view('/inc/settings_header'); ?>
<?php include ("inc/add_location.php") ?>
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
                  <a onclick="location.reload();" class="btn btn-default btn-sm text-dark rounded-pill"  > <strong>Users</strong></a> 
            </div>
         
            
            
         </div>
      </div>
   </div>
 <br>
 
 <title> - Users</title>
<div class = "row" >
                  <div class = "col"  >
                     <h3> Users</h3>
                      
                     
                  </div>
                  <div class = "col"  >
                   
  <button type="button" class="btn btn-default text-dark float-right ml-2 rounded-pill" type="button" data-toggle="collapse" data-target="#collapse_2" aria-expanded="false" aria-controls="collapseExample"> <i class="fa-solid fa-bars"></i></button>

                      <a style="width: 100px;" id="table_button" onclick="selectLocation();" class="btn btn-sm btn-secondary  rounded-pill float-right ml-2"  href="#">  <small class="fa fa-filter  "></small>&nbsp;&nbsp;  Filter &nbsp;&nbsp;    </a> 
                       
                      
                  </div>
               </div>



 
 <br>
                
  <div class="table-resposive">
      <table id="users_table" class="table table-striped table-bordered  " style="width:100%">
         <thead>
            <tr id="table_row">
                <th>#</th>
                 <th>ID</th>
                <th> Username</th>
               <th>Full name </th>
               
                <th>Email</th>
                <th>Phone</th>
                <th>Location</th>
                <th>Role    </th>
  
               <th>Status</th>
               <th style="width: 65px;">View</th>
              
            </tr>
         </thead>
         <tfoot>
            <tr  class="bg-secondary text-light"   >
               <th  style="width: 20px;"></th>
               
            </tr>
         </tfoot>
      </table>
   </div>






   </div>
<script type="text/javascript">
   

 

     //*********** Data tables 
   $(document).ready(function(){
   var table = $('#users_table').DataTable({
      "ajax": base_url + "settings/fetch_users_all",
      "order": [],
        "scrollX": true,
       order: [0, 'desc'],
   
   
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

 

 //Duactuvate user
$("#deactivate_user_form").submit(function(event) {
    event.preventDefault();
        $.ajax({
            url: base_url + 'settings/deactivate_user',
            data: $("#deactivate_user_form").serialize(),
            type: "post",
            async: false,
            dataType: 'json',
            success: function(response) {
                $('#deactivate_user_modal').modal('hide');
                $('#deactivate_user_form')[0].reset();
                alert('User deactivated successfully ');
             window.location = base_url + 'settings/users';
            },
            error: function() {
                alert("error");
            }
        }); 
});


//Activate USer
$("#activate_user_form").submit(function(event) {
    event.preventDefault();
        $.ajax({
            url: base_url + 'settings/activate_user',
            
            data: $("#activate_user_form").serialize(),
            type: "post",
            async: false,
            dataType: 'json',
            success: function(response) {
                $('#activate_user_modal').modal('hide');
                $('#activate_user_form')[0].reset();
                alert('User activated successfully ');
             window.location = base_url + 'settings/users';
            },
            error: function() {
                alert("error");
            }
        });
     
});



</script>
 
   
 
 <?php $this->load->view('/inc/footer'); ?>