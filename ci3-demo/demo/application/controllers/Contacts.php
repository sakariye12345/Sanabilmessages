 <?php
defined('BASEPATH') OR exit('No direct script access allowed');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
class Contacts extends CI_Controller {
 var $data;
 public function __construct()
 {
  parent::__construct();
// Auth session
  if ( !$this->session->userdata('username'))
    redirect(base_url()  . "home/login");



 $this->load->model('main_model');
    if ($this->session->userdata('tenant_id')!=$this->main_model->get_tenant_id()) {
$this->session->unset_userdata('username');
$this->session->unset_userdata('id');

redirect(base_url()  . "home");
}




//App Access
  $this->load->model('main_model');  
  $level =  $this->session->userdata('user_level');
$app_id = 1 ; //App = Contacts
$app_access_level = $this->main_model->get_access_level($app_id);
if($level != 2 && $app_access_level == 0   ){
 redirect(base_url()  . "home/access_required");
}

// Global variable:  Access and user level
$this->data = array(
  'system_title' => 'demo',
  'access' => $this->main_model->get_access_level($app_id),
  'user_level' => $this->session->userdata('user_level')
);


 // Load Models
$this->load->model('Model_settings');
 



}

 /*
if (($data['access']>1 && $data['user_level'] != 2) || // for user 
  $data['user_level']==2) // for Admin 
  {
    action

  } // End access access  
 */

  public function index()
  {
   $data = $this->data;
    
   $this->load->view("contacts/contacts_index" ,$data );  
 }
  
 function page_not_found(){
  $this->load->view("page_not_found");
}

 


//End controller
      }