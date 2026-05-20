  // .needs-validation forms
(function () {
  'use strict'

  // Fetch all the forms we want to apply custom Bootstrap validation styles to
  var forms = document.querySelectorAll('.needs-validation')

  // Loop over them and prevent submission
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }

        form.classList.add('was-validated')
      }, false)
    })
})()

 
 // Add New Location
$(document).ready(function() {
 
    $("#select_location").select2({
        dropdownParent: $("#select_location_modal")
    });
      
});
 
// fetch location using datatables

     //*********** Data tables 
   $(document).ready(function(){
   var table = $('#location_table').DataTable({
     // "ajax" : "<?php echo base_url('admin/fetch_categories'); ?>",
      "ajax": base_url + "settings/fetch_location_all",
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

 function selectLocation(){
  var location = document.getElementById("select_location").value;
  if (location=="" ) {
   $("#select_location").addClass("border-danger");
   alert("Please select location");
   return false;
}

window.location = base_url + 'settings/view_location/'  + location  ;
 

 }