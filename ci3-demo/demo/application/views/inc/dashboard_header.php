<html>







<head>
<title>Sanabil - Dashboard</title>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <!-- jQuery from CDN
 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
 
     -->
   
      
<script src="<?php echo base_url('assets\plugins\jquery\jquery.min.js'); ?>"></script>
    <!-- Bootstrap CSS from CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">

    <!-- DataTables CSS from CDN -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">

    <!-- DataTables Buttons CSS from CDN -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.4/css/buttons.dataTables.min.css">

    <!-- Custom Style -->
    <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/style.css'); ?>">

    <!-- DataTables JavaScript from CDN -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mark.js/8.11.1/jquery.mark.min.js"></script>

    <!-- Font Awesome CSS from CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <!-- Bootstrap JS from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

    <!-- Select2 CSS from CDN -->
    <link rel="stylesheet" href="<?php  echo base_url('assets\css\select2.min.css');?>">
      <script src="<?php  echo base_url('assets\js\select2.min.js');?>"></script>


    <!-- Custom Page Style -->
    <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/inc/css/style.css'); ?>">
</head>





 
<style type="text/css">
      @page {
         size: auto;
      }
   </style>

<body>
  <!-- <style>
      .dropdown:hover>.dropdown-menu {
         display: block;
      }
   </style>-->
   <style type="text/css">  
    
 
.dropdown .dropdown-menu a:focus {background: #ff8a46;
  color: white;}
 
 
 
</style>
 <style>


  [aria-expanded="false"] > .expanded_header,
[aria-expanded="true"] > .collapsed_header {
  display: none;
}
</style>
 
   <!-- NAV-->
   <!--nav class="navbar navbar-expand-lg navbar navbar-dark bg-dark  ;">
      < a class="navbar-brand" href="<?php echo base_url('dashboard'); ?>"><i class="fa-solid fa-bars"> &nbsp; </i>    </a >

<button style="color: white;" class="navbar navbar-expand-lg navbar navbar-dark bg-dark" type="button" data-toggle="collapse" data-target="#collapse_2" aria-expanded="false" aria-controls="collapseExample">  
                          <span class="collapsed_header"> <strong> <i class="fa-solid fa-bars"></i> </strong></span>
                        <span class="expanded_header"><strong> <i class="fa fa-times"></i> </strong> </span>
                      </button>



      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
         <span class="navbar-toggler-icon"></span>
      </button>





      <div class="collapse navbar-collapse" id="navbarNav">
          <img     style="width: 150px;" src="<?php echo base_url('images\Shihaab-Logo.png'); ?>" class="card-img-top  mx-auto "   alt="...">
            
      </div>
   </nav-->
   <!--end of nav-->
   <!--  <div class="overlay"><div class="loader"></div></div> -->
  
   <nav class="navbar navbar-expand-md navbar-dark bg-dark">
    <div class="navbar-collapse collapse w-100 order-1 order-md-0 dual-collapse2">
        <ul class="navbar-nav mr-auto">
          
            <li class="nav-item active">
     <button style="color: white;" class="navbar navbar-expand-lg navbar navbar-dark bg-dark" type="button" data-toggle="collapse" data-target="#collapse_2" aria-expanded="false" aria-controls="collapseExample">  
                          <span class="collapsed_header"> <strong> <i class="fa-solid fa-bars"></i> </strong></span>
                        <span class="expanded_header"><strong> <i class="fa fa-times"></i> </strong> </span>
                      </button>
            </li>

             
        </ul>
    </div>
    <div class="collapse navbar-collapse" id="navbarNav">
          <img     style="width: 150px;" src="<?php echo base_url('assets/images/header_logo.png'); ?>" class="card-img-top  mx-auto "   alt="...">
            
      </div>
    <div class="mx-auto order-0">
    
            


        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target=".dual-collapse2">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
   
<?php include('menu.php') ?>

</nav>