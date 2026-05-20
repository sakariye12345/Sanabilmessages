<?php $this->load->view('/inc/invoice_header'); ?>


<div class="container" style="width:100%">


 <br>

 
 
 <div class="row">

  <div class="col">
    <div class="card" style="box-shadow:0 0 5px 0 lightgrey;">
      <div class="card-body">

        <h4 class="card-title">Actions</h4>

                  <a href="/invoicing/new_invoice" style="background-color: #e2e6ea" type="button" class="btn btn-light text-dark btn-lg btn-block rounded-pill">New Invoice</a>
        
                  <a href="/invoicing/receive_payment_list" style="background-color: #e2e6ea" class="btn btn-light text-dark btn-lg btn-block rounded-pill">Receive Payment</a>
        
                  <a href="/invoicing/receive_payment_list_employees" style="background-color: #e2e6ea" class="btn btn-light text-dark btn-lg btn-block rounded-pill">Receive Employee Advance Payment</a>
        
                  <a href="/invoicing/receive_payment_list_customer_advance" style="background-color: #e2e6ea" class="btn btn-light text-dark btn-lg btn-block rounded-pill">Receive Customer Advance Payment</a>
        
      </div>
    </div>
  </div>

  <div class="col">
    <div class="card" style="box-shadow:0 0 5px 0 lightgrey;">
      <div class="card-body">

        <h4 class="card-title">View</h4>

                  <a href="/invoicing/invoices" style="background-color: #e2e6ea" class="btn btn-light text-dark btn-lg btn-block rounded-pill">Invoices</a>
        
                  <a href="/invoicing/outstanding_invoices" style="background-color: #e2e6ea" class="btn btn-light text-dark btn-lg btn-block rounded-pill">Outstanding Invoices</a>
        
                  <a href="#" data-toggle="modal" data-target="#select_customer_invoices_modal" style="background-color: #e2e6ea" class="btn btn-light text-dark btn-lg btn-block rounded-pill">Outstanding Customer Invoices</a>
        
                  <a href="/invoicing/received_payments" style="background-color: #e2e6ea" class="btn btn-light text-dark btn-lg btn-block rounded-pill">Received Payments</a>
        
                  <a href="<?php echo base_url('invoicing/customer_balances');?>" style="background-color: #e2e6ea" class="btn btn-light text-dark btn-lg btn-block rounded-pill">Customer Balances</a>
        
                  <a href="<?php echo base_url('invoicing/open_customer_balances');?>" style="background-color: #e2e6ea" class="btn btn-light text-dark btn-lg btn-block rounded-pill">Open Customer Balances</a>
        
                  <a href="/invoicing/employee_balances" style="background-color: #e2e6ea" class="btn btn-light text-dark btn-lg btn-block rounded-pill">Employee Balances</a>
        
                  <a href="/invoicing/open_employee_balances" style="background-color: #e2e6ea" class="btn btn-light text-dark btn-lg btn-block rounded-pill">Open Employee Balances</a>
        
      </div>
    </div>
  </div>

</div>




</div>


 





 <script src="<?php echo base_url('assets\inc\js\settings\settings_main.js'); ?>"></script> 
  <script src="<?php echo base_url('assets\inc\js\settings\settings_location.js'); ?>"></script> 