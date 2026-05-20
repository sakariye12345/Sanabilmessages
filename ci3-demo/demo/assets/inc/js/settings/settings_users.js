 

 

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


