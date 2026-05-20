<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require 'vendor/autoload.php';
//require 'assets2/autoload.php';
//require 'images';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
class Dashboard extends CI_Controller {

public function __construct()
	{
		parent::__construct();
if ( !$this->session->userdata('username') ){
            
           redirect(base_url()  . "home/login");

     

		$this->load->model('main_model');
	    $this->load->library('csvimport');
	  }
      $this->load->model('main_model');
	  if ($this->session->userdata('tenant_id')!=$this->main_model->get_tenant_id()) {
$this->session->unset_userdata('username');
$this->session->unset_userdata('id');

redirect(base_url()  . "home");
}

// Global variable:  Access and user level
$this->data = array(
  'system_title' => 'demo',
  'language' => 0,
  'currency' => 0,
  'user_level' => $this->session->userdata('user_level')
);




	$this->load->model('main_model');   
 
  }

  

public function index()
	{
		

if ( !$this->session->userdata('username'))
            
           redirect(base_url()  . "home/login");
           $data = $this->data;

if ($this->session->userdata('mobile') == 1) {
   redirect(base_url()  . "atdapp");
}else{ 


if ($data['language']==1) {
 $this->load->view("dashboard_arabic");
}else{
			$this->load->view("dashboard");
  }
}

}

 


function page_not_found(){
  $this->load->view("page_not_found");
}
 
 

//------------------------- Change password --------------------------------

public function change_password()
  {
  $this->load->view("change_password");  
  }



      public function update_pws()
      {

        $this->form_validation->set_rules('old_password', 'Old Password', 'trim|required');
        $id = $this->session->userdata('id');
        $old_password = $this->main_model->check_old_password(md5($this->input->post('old_password')),$id);

        if ($this->form_validation->run() == TRUE) {            
          if ($old_password) {

           $data = array(
            'password'  => md5($this->input->post('new_password')),
          );

           $update = $this->main_model->update_pws($data);

           if($update == true) {
            $this->session->set_flashdata('info', 'Password changed Successfully');
            redirect('dashboard/change_password/', 'refresh');

          }


          else {
            $this->session->set_flashdata('errors', 'Error occurred!!');
            redirect('dashboard/change_password/', 'refresh');
          }

        }
        else{
          $this->session->set_flashdata('danger', 'Error: Incorrect old password');
          
          redirect('dashboard/change_password/', 'refresh');
        }


//form run
      }
      else{
        $this->load->view("inventory");


      }

    }


 

//End of admin
}