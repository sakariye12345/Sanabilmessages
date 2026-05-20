 

 $(document).ready(function() {
    
    
    $("#user_accessibility").select2({
      dropdownParent: $("#accessibility_modal")
     });
});

 

     //*********** Data tables 
   $(document).ready(function(){
   var table = $('#user_accessibility_table').DataTable({
   //   "ajax": base_url + "settings/fetch_apps",
      "order": [],
        "scrollX": true,
       order: [0, 'asc'],
   
   
   mark: true,
         dom: 'Bfrtip',
        lengthMenu: [
             [ 30, 40, 50, 100, -1],
             ['30 rows', '40 rows', '50 rows', '100 rows', 'Show All']
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

   function selectUserAccessibility(){
  var user = document.getElementById("user_accessibility").value;
  if (user=="" ) {
   $("#user_accessibility").addClass("border-danger");
   alert("Please select user");
   return false;
}

window.location = base_url + 'settings/user_accessibility/'  + user  ;
 

 }


 //Add user access 
$("#add_user_access_form").submit(function(event) {
    event.preventDefault();
     
var name = document.getElementById("full_name_user_access").value;
var level = document.getElementById("access_level").value;
var app_name = document.getElementById("app_name").value;
if (confirm("Are you sure to grant "+ name +" Level "+level+" "+ app_name +" application access?") == true) {
        $.ajax({
            url: base_url + 'settings/add_user_access',
            data: $("#add_user_access_form").serialize(),
            type: "post",
            async: false,
            dataType: 'json',
            success: function(response) {
                $('#add_user_access_modal').modal('hide');
                $('#add_user_access_form')[0].reset();
                alert('Level '+level+' access successfully granted to '+ name);
             location.reload();
            },
            error: function() {
                alert("error");
            }
        }); 
        } else {
 return false;
}
});



 //Edit user access 
$("#edit_user_access_form").submit(function(event) {
    event.preventDefault();
     
var name = document.getElementById("full_name_user_access_1").value;
var level = document.getElementById("access_level_1").value;
var app_name = document.getElementById("app_name_1").value;
if (confirm("Are you sure update and grant "+ name +" Level "+level+" "+ app_name +" application access?") == true) {
        $.ajax({
            url: base_url + 'settings/update_user_access',
            data: $("#edit_user_access_form").serialize(),
            type: "post",
            async: false,
            dataType: 'json',
             success: function(response){
                 $('#edit_user_access_modal').modal('hide');
                  $('#edit_user_access_form')[0].reset();
                  if(response==1)
                  {
                    alert('Updated and level '+level+' access successfully granted to '+ name);
                    location.reload();

                  }
                  else{
                    alert('Failed !');
                     location.reload();
                  }
                
                },
            error: function() {
                alert("error");
            }
        }); 
        } else {
 return false;
}
});



//Remove user access
$("#remove_user_access_form").submit(function(event) {
    event.preventDefault();
        $.ajax({
            url: base_url + 'settings/remove_user_access',
            data: $("#remove_user_access_form").serialize(),
            type: "post",
            async: false,
            dataType: 'json',
            success: function(response) {
                $('#remove_user_access_modal').modal('hide');
                $('#remove_user_access_form')[0].reset();
                alert('Access removed successfully ');
              location.reload();
            },
            error: function() {
                alert("error");
            }
        }); 
});
