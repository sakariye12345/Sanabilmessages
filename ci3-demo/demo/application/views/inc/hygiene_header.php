<html>
   <head>
      <title>Sanabil | Hygine Attendance</title>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <!-- Tell the browser to be responsive to screen width -->
      <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
      <!--script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script-->
      <script src="<?php echo base_url('assets\plugins\jquery\jquery.min.js'); ?>"></script>
      <!--link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"-->
      <link rel="stylesheet" href="<?php echo base_url('assets\plugins\bootstrap-4.3.1-dist\css\bootstrap.min.css'); ?>">
      <!--link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css"-->
      <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets\css\jquery.dataTables.min.css'); ?>">
      <!--Start-->
      <!--link rel="stylesheet" type="text/css" href="<?php echo base_url('assets\css\jquery.dataTables.min.css'); ?>">
         <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets\css\bootstrap.min.css'); ?>">
         
         <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets\css\dataTables.bootstrap.min.css'); ?>"-->
      <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets\css\buttons.dataTables.min.css'); ?>">
      <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets\css\style.css'); ?>">
      <script type="text/javascript" src="<?php echo base_url('assets\js\jquery-2.2.4.min.js'); ?>"></script>
      <script type="text/javascript" src="<?php echo base_url('assets\js\jquery.dataTables.min.js'); ?>"></script>
      <script type="text/javascript" src="<?php echo base_url('assets\js\dataTables.buttons.min.js'); ?>"></script>
      <script type="text/javascript" src="<?php echo base_url('assets\js\jszip.min.js'); ?>"></script>
      <script type="text/javascript" src="<?php echo base_url('assets\js\pdfmake.min.js'); ?>"></script>
      <script type="text/javascript" src="<?php echo base_url('assets\js\vfs_fonts.js'); ?>"></script>
      <script type="text/javascript" src="<?php echo base_url('assets\js\buttons.html5.min.js'); ?>"></script>
      <script type="text/javascript" src="<?php echo base_url('assets\js\buttons.print.min.js'); ?>"></script>
      <script type="text/javascript" src="<?php echo base_url('assets\js\jquery.mark.min.js'); ?>"></script>
      <script type="text/javascript" src="<?php echo base_url('assets\js\datatables.mark.js'); ?>"></script>
      <script type="text/javascript" src="<?php echo base_url('assets\js\buttons.colVis.min.js'); ?>"></script>
      <!--End-->
      <!--link rel="stylesheet" type="text/css" href="<?php echo base_url('\assets\plugins\fontawesome-free-5.14.0-web\css\all.min.css'); ?>"-->
      <!--link rel="stylesheet" type="text/css" href="<?php echo base_url('\assets\plugins\fontawesome-free-5.14.0-web\css\fontawesome.min.css'); ?>"-->
      <link rel="stylesheet" type="text/css" href="<?php echo base_url('\assets\plugins\fontawesome-free-6.1.1-web\css\all.min.css'); ?>">
      <link rel="stylesheet" type="text/css" href="<?php echo base_url('\assets\plugins\fontawesome-free-6.1.1-web\css\fontawesome.min.css'); ?>">
      <script src="<?php echo base_url('\assets\plugins\bootstrap-4.3.1-dist\js\bootstrap.min.js'); ?>"></script>
      <style type="text/css">
         @page {
         size: auto;
         }
      </style>
      <link rel="stylesheet" href="<?php  echo base_url('assets\css\select2.min.css');?>">
      <script src="<?php  echo base_url('assets\js\select2.min.js');?>"></script>

      <!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- FontAwesome (optional) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

   </head>
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
             <img     style="width: 150px;" src="<?php echo base_url('images\Shihaab-Logo.PNG'); ?>" class="card-img-top  mx-auto "   alt="...">
               
         </div>
         </nav-->
      <!--end of nav-->
      <!--  <div class="overlay"><div class="loader"></div></div> -->
      <nav class="navbar navbar-expand-md navbar-dark bg-dark">
         <div class="navbar-collapse collapse w-100 order-1 order-md-0 dual-collapse2">
            <ul class="navbar-nav mr-auto">
                  <li class="nav-item">
                  <a href="<?php echo base_url('dashboard'); ?>" class="nav-link active  notification">
                     <span><i class="fas fa-home"></i></span>
                   </a>
               </li>

               <li class="nav-item active">
                  <a   href="<?php echo base_url('hygiene'); ?>" style="color: white;" class="btn btn-dark" type="submit" >  
               <span class="collapsed_header">   Hygiene Attendance </span>
               </a>
               </li>
                 <li class="nav-item active">
                  <a   href="<?php echo base_url('hygiene/report'); ?>" style="color: white;" class="btn btn-dark" type="submit" >  
               <span class="collapsed_header">   Reports </span>
               </a>
               </li>
              




               <!--li class="nav-item active dropdown    ">
                  <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                  Users
                  </a>
                  <div class=" dropdown-menu" aria-labelledby="navbarDropdown">
                     <a class="dropdown-item" href="<?php echo base_url('admin/dashboard'); ?>">Add New User</a>
                     <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('admin/changePassword'); ?>">View Users</a>
                     <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url('admin/changePassword'); ?>">Change User Password</a>
                  </div>
               </li-->
                
            </ul>
         </div>
         <div class="mx-auto order-0">
         
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target=".dual-collapse2">
                <span class="navbar-toggler-icon"></span>
            </button>
            </div>
         <?php include('menu.php') ?>
      </nav>
      <script>
         var base_url = "<?php echo base_url();?>";
         
      </script>

     
