 
 $(document).ready(function() {
    
    $("#user_location").select2({
      dropdownParent: $("#add_user_modal")
     });
    $("#user_id_4").select2({
      dropdownParent: $("#change_password_modal")
     });
    
    $("#user_accessibility").select2({
      dropdownParent: $("#accessibility_modal")
     });
});


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
 




 //Users

function  validateUserForm(){


  var pass = $("#user_password").val(); 
  var confirm = $("#confirm_password").val(); 
  if (pass==confirm) {
    return true;
  }else{
     
    $("#user_password").addClass("border-danger");
    $("#confirm_password").addClass("border-danger");
     $("#password_error").html("<span class='text-danger'>Passwords do not match. </span>");



    return false;
  }
}
 
//Change Password
$("#change_password_form").submit(function(event) {
  var pass = $("#change_password").val(); 
  var confirm = $("#confirm_change_password").val();
  if (pass==confirm) {
    event.preventDefault();
        $.ajax({
            url: base_url + 'settings/change_pass',
            data: $("#change_password_form").serialize(),
            type: "post",
            async: false,
            dataType: 'json',
            success: function(response) {
                $('#change_password_modal').modal('hide');
                $('#change_password_form')[0].reset();
                alert('Password changed successfully ');
             window.location = base_url + 'settings';
            },
            error: function() {
                alert("error");
            }
        });
}else{
     
    $("#change_password").addClass("border-danger");
    $("#confirm_change_password").addClass("border-danger");
     $("#change_password_error").html("<span class='text-danger'>Passwords do not match. </span>");
  $("#confirm_password_error").html("<span class='text-danger'>Passwords do not match. </span>");
    return false;
  }

});





 function selectUserAccessibility(){
  var user = document.getElementById("user_accessibility").value;
  if (user=="" ) {
   $("#user_accessibility").addClass("border-danger");
   alert("Please select user");
   return false;
}

window.location = base_url + 'settings/user_accessibility/'  + user  ;
 

 }

//**************