<br/>
<nav class="navbar  navbar-expand-lg navbar navbar-dark bg-dark  ;">
    <a class="navbar-brand" href="#"> </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div>

    <p class="text-light   "  ><i class="fa fa-copyright" aria-hidden="true">    </i> <?php echo date("Y"); ?> &nbsp; Developed by Saafi Systems <!-----> &nbsp; <!--i class="fa fa-globe" aria-hidden="true"> &nbsp; </i> <a class="text-light"  href="https://www.shihaab.com" target="_blank">Shihaab.com</a-->  &nbsp; <!--i class="fa fa-envelope" aria-hidden="true"> &nbsp; </i>info@shihaab.com &nbsp; <i class="fa fa-phone" aria-hidden="true"> &nbsp; </i> +252 63 4458114 </p-->

</div>

</body>

<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/'); ?>plugins/datetimepicker/jquery.datetimepicker.css"/ >


<script src="<?php echo base_url('assets/'); ?>plugins/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>






<script type="text/javascript">
    jQuery('#datetimepicker').datetimepicker();




$('#datetimepicker1').datetimepicker({
    datepicker:false,
    format:'H:i',
    step:1
});

$('#datetimepicker2').datetimepicker({
    datepicker:false,
    format:'H:i',
    step:1
});

$('#updatetimepicker1').datetimepicker({
    datepicker:false,
    format:'H:i',
    step:1
});

$('#updatetimepicker2').datetimepicker({
    datepicker:false,
    format:'H:i',
    step:1
});


$('#added_date').datetimepicker({
   // yearOffset:222,
   // lang:'ch',
    timepicker:false,
    format:'Y-m-d',
    formatDate:'Y-m-d',
    //minDate:'-1970/01/02', // yesterday is minimum date
   // maxDate:'+1970/01/02' // and tommorow is maximum date calendar
});
$('#expire_date').datetimepicker({
   // yearOffset:222,
   // lang:'ch',
    timepicker:false,
    format:'Y-m-d',
    formatDate:'Y-m-d',
    //minDate:'-1970/01/02', // yesterday is minimum date
   // maxDate:'+1970/01/02' // and tommorow is maximum date calendar
});




$('#startingtime').datetimepicker({
    datepicker:false,
    format:'H:i',
    step:1
});


</script>











</html>