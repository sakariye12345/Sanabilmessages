<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
class Twofactorauth extends CI_Controller {

  public function __construct()
  {
    parent::__construct();
    if ( !$this->session->userdata('username'))

     redirect(base_url()  . "home/login");


  // $this->load->model('main_model');
  // if ($this->session->userdata('tenant_id')!=$this->main_model->get_tenant_id()) {
   // $this->session->unset_userdata('username');
   //$this->session->unset_userdata('id');

    //redirect(base_url()  . "home");
  //}

   $level =  $this->session->userdata('user_level');
   if($level != 2){
    redirect(base_url()  . "home/access_required");
  }
  $this->load->model('main_model');
  $this->load->model('Model_company');
  $this->load->model('Model_settings');
  $this->load->model('model_auth');
  $this->load->model('Model_exam');
   $this->load->model('Model_subscriptions');
  $this->load->model('Model_messages');


}


 

public function enable_2fa($id)
{
    // 1. load the library
    require_once APPPATH.'third_party/PHPGangsta/GoogleAuthenticator.php';
    $ga = new PHPGangsta_GoogleAuthenticator();

    // 2. fetch the user
    $user = $this->main_model->get_single_user_data($id);

    // 3. generate + persist a new secret if needed
    if (empty($user['google_2fa_secret'])) {
        $secret = $ga->createSecret();
        $this->main_model->set_2fa_secret($id, $secret);
    } else {
        $secret = $user['google_2fa_secret'];
    }

     $result = array();
  $company_data = $this->Model_company->getCompanyData(1);
  $result['company'] = $company_data;
  $data['company_data'] = $result;


  $company_name = $company_data['company_name'] ;

    // 4. build the QR URL
    $qrCodeUrl = $ga->getQRCodeGoogleUrl(
      $company_name . ':' . $user['email'],
 
        $secret
    );

    // 5. render a simple view
    $this->load->view('auth/enable_2fa', [
        'user'      => $user,
        'qrCodeUrl' => $qrCodeUrl,
        'secret'    => $secret,
    ]);
}



  public function reset_2fa($user_id)
    {
        // Optionally: check permissions here

        // Clear out the secret
        $updated = $this->main_model->reset_two_factor_secret($user_id);

        if ($updated) {
            $this->session->set_flashdata('success', '2FA has been reset for user.');
        } else {
            $this->session->set_flashdata('error', 'Could not reset 2FA.');
        }

        redirect($_SERVER['HTTP_REFERER'] ?? 'settings/index');
    }

//---------------------------- End Subjects  ------------------------------------


//End controller
}