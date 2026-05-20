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
 
// fetch classes using datatables



 var advanced_search = $('#advanced_search').val();
 if (advanced_search == "advanced_search") {
    search = "";
 }else{
    search = "search";
 }

     //*********** Data tables for all classes
   $(document).ready(function(){

      $('#classes_table thead  '+search+' th').each( function () {
        var title = $('#classes_table thead th').eq( $(this).index() ).text();
        $(this).html( '<input type="text" placeholder="Search '+title+'" />' );
    } );
   
     
   var table = $('#classes_table').DataTable({
      "ajax": base_url + "settings/fetch_classes_all",
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
   table.columns().every( function () {
        var that = this;
 
        $( 'input', this.header() ).on( 'keyup change', function () {
            that
                .search( this.value )
                .draw();
        } );
    } );
   
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