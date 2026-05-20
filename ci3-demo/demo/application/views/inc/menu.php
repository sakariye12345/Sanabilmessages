 <div class="navbar-collapse collapse w-100 order-3 dual-collapse2">
        <ul class="navbar-nav ml-auto">
            <!--li class="nav-item">
                <a class="nav-link active" href="#"><i class="fas fa-bell"></i>(0) </a>
            </li-->
            
<style type="text/css">
   .notification {
  background-color: #555;
  color: white;
  text-decoration: none;
  padding: 10px 15px;
  position: relative;
  display: inline-block;
  border-radius: 2px;
}

.notification:hover {
  background: #ff8a46;
}

.notification .badge {
  position: absolute;
  top: -3px;
  right: -3px;
  padding: 2px 5px;
  border-radius: 50%;
  background: red;
  color: white;
}


.notification .badge_lan {
  position: absolute;
  top: -3px;
  right: -3px;
  padding: 2px 5px;
  border-radius: 50%;
  background: red;
  color: white;
}

 .global_add {
  background-color: #555;
  color: white;
  text-decoration: none;
  padding: 10px 15px;
  position: relative;
  display: inline-block;
  border-radius: 2px;
}

.global_add:hover {
  background: #ff8a46;
}
.global_add:focus {
  background: #ff8a46;
}



</style>


 <style type="text/css">
   
   .notification .badge,
   .notification .badge_lan {
      position: absolute;
      top: -3px;
      right: -3px;
      padding: 2px 5px;
      border-radius: 50%;
      background: red;
      color: white;
      font-size: 12px; /* Adjust font size for consistency */
      line-height: 12px; /* Ensure the badges are aligned similarly */
      min-width: 20px; /* Ensure consistent width */
      text-align: center; /* Center text within the badge */
   }
</style>


 <li class="nav-item active dropdown    ">
                  <a class="nav-link global_add" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                      <span><i class="fas fa-plus"></i></span>
                  </a>
                  <div class=" dropdown-menu" aria-labelledby="navbarDropdown">
                     
                           
                     <a class="dropdown-item" href="<?php echo base_url('contacts'); ?>">Contacts</a>


                       <div class="dropdown-divider"></div>
                      

                     <a class="dropdown-item" href="<?php echo base_url('settings'); ?>"></a>
                     <div class="dropdown-divider"></div>
 
                  </div>
               </li>

 

<!--li class="nav-item active dropdown    ">
                  <a class="nav-link global_add" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                      <span><i class="fa-solid fa-rocket"></i>ss</span>
                  </a>
                  <div class=" dropdown-menu" aria-labelledby="navbarDropdown" style="width: 300px; column-count: 2;">
                     
                     <a class="dropdown-item" href="<?php echo base_url('contacts'); ?>">Contacts </a>
                      <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('inventory'); ?>">Inventory</a>

                      <div class="dropdown-divider"></div>
                      

                      <a class="dropdown-item" href="<?php echo base_url('pos'); ?>">Point of Sales</a>
                       <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('sales'); ?>">Sales</a>
                     <div class="dropdown-divider"></div>

                    
                    
                     
                     <a class="dropdown-item" href="<?php echo base_url('Invoicing'); ?>">Invoicing</a>
                     <div class="dropdown-divider"></div>

                     <a class="dropdown-item" href="<?php echo base_url('calendar'); ?>">Calendar</a>

                     <div class="dropdown-divider"></div>

 <a class="dropdown-item" href="<?php echo base_url('emr'); ?>">EMR</a>
                  <div class="dropdown-divider"></div>   

<a class="dropdown-item" href="<?php echo base_url('prescriptions'); ?>">E-Prescriptions</a>

 <div class="dropdown-divider"></div>   

<a class="dropdown-item" href="<?php echo base_url('banking'); ?>">Banking</a>

 <div class="dropdown-divider"></div>   

<a class="dropdown-item" href="<?php echo base_url('purchase_orders'); ?>">Purchase</a>


 <div class="dropdown-divider"></div>   

<a class="dropdown-item" href="<?php echo base_url('Reports'); ?>">Reports</a>

 <div class="dropdown-divider"></div>   

<a class="dropdown-item" href="<?php echo base_url('Services'); ?>">Services</a>


 </div>
               </li-->

               



               
             
 


<li class="nav-item active dropdown    ">
                  <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                     My Account
                  </a>
                  <div class=" dropdown-menu" aria-labelledby="navbarDropdown">
                     <a class="dropdown-item" href="#">Profile</a>
                     <!--div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="#">My Appointment</a-->
                     <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('dashboard/change_password'); ?>">Change Password</a>
                     <div class="dropdown-divider"></div>

                     <a class="dropdown-item" href="<?php echo base_url('home/singout'); ?>"> <i class="fas fa-sign-out-alt"> &nbsp; </i> Logout</a>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
                  </div>
               </li>
            <li class="nav-item">
                <a class="nav-link active Invisible" href="#">Link</a>
            </li>
        </ul>
    </div>
     
 