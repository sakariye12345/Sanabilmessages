<html>
   <head>
      <title>Sanabil | Allowed Parents Data</title>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <!-- Tell the browser to be responsive to screen width -->
      <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
      <script src="<?php echo base_url('assets\plugins\jquery\jquery.min.js'); ?>"></script>
      <link rel="stylesheet" href="<?php echo base_url('assets\plugins\bootstrap-4.3.1-dist\css\bootstrap.min.css'); ?>">
      <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets\css\jquery.dataTables.min.css'); ?>">
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
      <link rel="stylesheet" type="text/css" href="<?php echo base_url('\assets\plugins\fontawesome-free-6.1.1-web\css\all.min.css'); ?>">
      <link rel="stylesheet" type="text/css" href="<?php echo base_url('\assets\plugins\fontawesome-free-6.1.1-web\css\fontawesome.min.css'); ?>">
      <script src="<?php echo base_url('\assets\plugins\bootstrap-4.3.1-dist\js\bootstrap.min.js'); ?>"></script>
      <style type="text/css">
         @page {
         size: auto;
         }
      </style>
      <link rel="stylesheet" href="<?php echo base_url('assets\css\select2.min.css');?>">
      <script src="<?php echo base_url('assets\js\select2.min.js');?>"></script>
   </head>
   <body>
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
      <nav class="navbar navbar-expand-md navbar-dark bg-dark">
         <div class="navbar-collapse collapse w-100 order-1 order-md-0 dual-collapse2">
            <ul class="navbar-nav mr-auto">
                  <li class="nav-item">
                  <a href="<?php echo base_url('dashboard'); ?>" class="nav-link active  notification">
                     <span><i class="fas fa-home"></i></span>
                   </a>
               </li>

               <li class="nav-item active">
                  <a href="<?php echo base_url('parents'); ?>" style="color: white;" class="btn btn-dark" type="submit" >  
               <span class="collapsed_header"> Allowed Parents Data</span>
               </a>
               </li>
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
