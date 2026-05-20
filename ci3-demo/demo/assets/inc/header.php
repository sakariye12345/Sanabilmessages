<html>
<head>

   <title>Inventory Management</title>


<meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Inventory Management</title>
        <!-- Tell the browser to be responsive to screen width -->
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

   
   <!--script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script--> 
    <script src="<?php  echo base_url('assets\plugins\jquery\jquery.min.js');?>"></script>

   <!--link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"-->

<link rel="stylesheet" href="<?php  echo base_url('assets\plugins\bootstrap-4.3.1-dist\css\bootstrap.min.css');?>">

<!--link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css"-->



<link rel="stylesheet" type="text/css" href="<?php  echo base_url('assets\css\jquery.dataTables.min.css');?>">






<!--Start-->


      


<!--link rel="stylesheet" type="text/css" href="<?php  echo base_url('assets\css\jquery.dataTables.min.css');?>">


    <link rel="stylesheet" type="text/css" href="<?php  echo base_url('assets\css\bootstrap.min.css');?>">

    <link rel="stylesheet" type="text/css" href="<?php  echo base_url('assets\css\dataTables.bootstrap.min.css');?>"-->








    <link rel="stylesheet" type="text/css" href="<?php  echo base_url('assets\css\buttons.dataTables.min.css');?>">
    <link rel="stylesheet" type="text/css" href="<?php  echo base_url('assets\css\style.css');?>">
    <script type="text/javascript" src="<?php  echo base_url('assets\js\jquery-2.2.4.min.js');?>"></script>
<script type="text/javascript" src="<?php  echo base_url('assets\js\jquery.dataTables.min.js');?>"></script>
<script type="text/javascript" src="<?php  echo base_url('assets\js\dataTables.buttons.min.js');?>"></script>
<script type="text/javascript" src="<?php  echo base_url('assets\js\jszip.min.js');?>"></script>
<script type="text/javascript" src="<?php  echo base_url('assets\js\pdfmake.min.js');?>"></script>
<script type="text/javascript" src="<?php  echo base_url('assets\js\vfs_fonts.js');?>"></script>
<script type="text/javascript" src="<?php  echo base_url('assets\js\buttons.html5.min.js');?>"></script>
<script type="text/javascript" src="<?php  echo base_url('assets\js\buttons.print.min.js');?>"></script>


<script type="text/javascript" src="<?php  echo base_url('assets\js\jquery.mark.min.js');?>"></script>
<script type="text/javascript" src="<?php  echo base_url('assets\js\datatables.mark.js');?>"></script>
<script type="text/javascript" src="<?php  echo base_url('assets\js\buttons.colVis.min.js');?>"></script>

<!--End-->




<link rel="stylesheet" type="text/css" href="<?php  echo base_url('\assets\plugins\fontawesome-free-5.14.0-web\css\all.min.css');?>">


<link rel="stylesheet" type="text/css" href="<?php  echo base_url('\assets\plugins\fontawesome-free-5.14.0-web\css\fontawesome.min.css');?>">


   

<script src="<?php  echo base_url('\assets\plugins\bootstrap-4.3.1-dist\js\bootstrap.min.js');?>">
   
</script>


<style type="text/css">
   @page {
    size: auto;
}
</style>






</head>
<body>
   <style >
         .dropdown:hover>.dropdown-menu{
         display: block;
         }
      </style>

      <!-- NAV-->
     <nav class="navbar navbar-expand-lg navbar navbar-dark bg-dark  ;">
         <a class="navbar-brand" href="<?php echo base_url('admin/dashboard'); ?>"> <b>Inventory Management  </b> </a>
         <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
         <span class="navbar-toggler-icon"></span>
         </button>
         <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
               <li class="nav-item active">
                  <a class="nav-link" href="<?php echo base_url('admin/dashboard'); ?>"> <i class="fa fa-home"> &nbsp; </i> Home <span class="sr-only">(current)</span></a>
               </li>
               <?php
                  if ( $this->session->userdata('user_levelerror') ==1 )  {
                   ?>
               <?php
                  }
                  ?>
               <?php
                  if ( !$this->session->userdata('username'))  {
                   ?>
               <li class="nav-item active">
                  <a class="nav-link" href="<?php echo base_url('home/login'); ?>"> <i class="fa fa-user"> &nbsp; </i> Login <span class="sr-only">(current)</span></a>
               </li>
               <?php
                  }
                  ?>
               <?php
                  if ( $this->session->userdata('username'))  {
                   ?>
             
   <li class="nav-item active dropdown    ">
                  <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                  Inventory 
                  </a>
                  <div class=" dropdown-menu" aria-labelledby="navbarDropdown">
                     <a class="dropdown-item" href="<?php echo base_url('admin/stock'); ?>" > Stock</a>
                     <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('admin/products'); ?>">Products </a>
                     <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('admin/categories'); ?>">Categories</a>
                     <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('admin/social_science'); ?>">Brands</a>
                 
                      <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('stock/transfer'); ?>">Stock Transfer</a>

                      
                    
                  </div>
               </li>

 <li class="nav-item active">
                  <a class="nav-link" href="<?php echo base_url('orders/create'); ?>"> <i>  </i> Point of Sale <span class="sr-only">(current)</span></a>
               </li>

             <li class="nav-item active">
                  <a class="nav-link" href="<?php echo base_url('expenses/view_expenses'); ?>"> <i>  </i> Expenses <span class="sr-only">(current)</span></a>
               </li>





            

  <li class="nav-item active dropdown    ">
                  <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                  Reports
                  </a>
                  <div class=" dropdown-menu" aria-labelledby="navbarDropdown">
                     <a class="dropdown-item" href="<?php echo base_url('orders/transactions'); ?>" >Transections</a>
                     <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('reports/revenues'); ?>">Revenues  </a>
                     <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('reports/expenses'); ?>">Expenses</a>
                     <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('reports/profit_loss'); ?>">Profit/Loss</a>
                     <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('reports/accounts_payable'); ?>">Accounts Payable</a> <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('reports/accounts_receivable'); ?>">Accounts Receivable </a>
                  </div>
               </li>



<li class="nav-item active dropdown    ">
                  <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                  Company Information
                  </a>
                  <div class=" dropdown-menu" aria-labelledby="navbarDropdown">
                     



<a class="dropdown-item" href="<?php echo base_url('admin/users'); ?>" >Users</a>
                     <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('admin/faculty'); ?>">Branches </a>
                     <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('admin/department'); ?>">Customers </a>
                     <div class="dropdown-divider"></div>
                      <a class="dropdown-item" href="<?php echo base_url('admin/class_page'); ?>">Suppliers </a>
                     <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('admin/courses'); ?>">Staff </a>
                     <div class="dropdown-divider"></div>
                       <a class="dropdown-item" href="<?php echo base_url('admin/lecturer'); ?>">Lecturers  </a>
                     <div class="dropdown-divider"></div>
                       <a class="dropdown-item" href="<?php echo base_url('admin/staff'); ?>">Staffs </a>
                     <div class="dropdown-divider"></div>
                       <a class="dropdown-item" href="<?php echo base_url('admin/staff_department'); ?>">Staff Departments </a>
                     <div class="dropdown-divider"></div>

                       <a class="dropdown-item" href="<?php echo base_url('admin/sub_department'); ?>">Sub Departments </a>

                  
                  </div>
               </li>

<li class="nav-item active dropdown    ">
                  <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                  My Account
                  </a>
                  <div class=" dropdown-menu" aria-labelledby="navbarDropdown">
                     <a class="dropdown-item" href="<?php echo base_url('admin/changePassword'); ?>" >Change Password</a>
                    
                    
                     
                  </div>
               </li>








               <li class="nav-item active">
                  <a class="nav-link" href="<?php echo base_url('admin/singout'); ?>"> <i class="fa fa-user"> &nbsp; </i> Logout <span class="sr-only">(current)</span></a>
               </li>
               <?php
                  }
                  ?>
            </ul>
         </div>
      </nav>
      <!--end of nav-->
      <!--  <div class="overlay"><div class="loader"></div></div> -->