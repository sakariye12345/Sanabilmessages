<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {
 function __construct(){
    parent::__construct();




    
    $this->load->model('main_model');

 }











	
	public function index()
	{
		
$this->load->model("main_model");
if ( $this->session->userdata('username')){
    redirect(base_url()  . "dashboard");
}else{
$this->load->view("login");
}
}


function login(){
	$this->load->model("main_model");
if ( $this->session->userdata('username')){
    redirect(base_url()  . "dashboard");
}else{
$this->load->view("login");
}
}



function singin(){

	//echo "Welcome";
$this->load->library('form_validation');
$this->form_validation->set_rules('username', 'Username', 'required');
$this->form_validation->set_rules('password', 'Pasword', 'required');
if (  $this->form_validation->run()){

	//echo "Welcome";
	$username = $this->input->post('username');
		$password =  $this->input->post('password');
        $disabled =  0;
		$this->load->model('main_model');
		$UserExist = $this->main_model->adminExist($username,$password ,$disabled);
		//echo '<pre>';
		//print_r($UserExist);
		//echo '</pre>';
if ($UserExist) {
	$sessionData = [
			'user_id' => $UserExist->user_id,
			'username' => $UserExist->username,
			'fullname' => $UserExist->fullname,
			'email' => $UserExist->email,
			'role_id' => $UserExist->role_id,

	];

	$this->session->set_userdata($sessionData);
	
	return redirect("home");
}else{
	$this->session->set_flashdata('error', 'Invalid Username and password');
				redirect(base_url()  . "home/login");

}

}

else{
	$this->login();
}



}



 

 





 public function auth()
  {
    $this->load->library('form_validation');
    $this->form_validation->set_rules('username','Username','required');
    $this->form_validation->set_rules('password','Password','required');

    if (!$this->form_validation->run()) {
      return $this->login();
    }

    $u = $this->input->post('username', TRUE);
    $p = md5($this->input->post('password', TRUE));
    $status = 1;

    // <- your existing validate call (make sure it SELECTs google_2fa_secret too)
    $validate = $this->main_model->validate($u, $p, $status);

    if ($validate->num_rows() === 0) {
      $this->session->set_flashdata('error','Invalid username and password');
      return redirect('home/login');
    }

    $data = $validate->row_array();
    $id   = $data['id'];

    // ————————————————
    // 2FA check (Gracefully handle missing column)
    // ————————————————
    if ( isset($data['google_2fa_secret']) && ! empty($data['google_2fa_secret']) ) {
      // stash just their ID
      $this->session->set_userdata('2fa_user_id', $id);
      return redirect('home/verify_2fa');
    }

    // ————————————————
    // no 2FA: finish login exactly as before
    // ————————————————
    $sesdata = [
      'id'         => $id,
      'username'   => $data['username'],
      'email'      => (isset($data['email']) && !empty($data['email'])) ? $data['email'] : $data['password'], 
      'user_level' => $data['user_level'],
      'first_name' => $data['first_name'],
      'last_name'  => $data['last_name'],
      'location_id'=> $data['location_id'],
      'tenant_id'  => (isset($data['tenant_id']) && !empty($data['tenant_id'])) ? $data['tenant_id'] : $this->main_model->get_tenant_id(),
      'mobile'     => $data['mobile'] ?? 0,
      'logged_in'  => TRUE,
    ];
    $this->session->set_userdata($sesdata);
   // $this->main_model->log_action($id,'login');
    redirect('dashboard');
  }





 /**
   * GET → show the 2FA form.
   * POST → verify the code, then complete the login.
   */
  public function verify_2fa()
  {
    // must have come from auth()
    $user_id = $this->session->userdata('2fa_user_id');
    if (! $user_id) {
      return redirect('home/login');
    }

    // on POST: verify
    if ($this->input->server('REQUEST_METHOD') === 'POST') {
      $code = $this->input->post('code', TRUE);

      // load lib and fetch the user’s secret
      require_once APPPATH.'third_party/PHPGangsta/GoogleAuthenticator.php';
      $ga = new PHPGangsta_GoogleAuthenticator();

      // fetch full user row (including google_2fa_secret)
      $user = $this->main_model->get_user_by_id($user_id);

      if ($ga->verifyCode($user['google_2fa_secret'], $code, 2)) {
        // success!
        $this->session->unset_userdata('2fa_user_id');

        // now set the real login session data
        $sesdata = [
          'id'         => $user['id'],
          'username'   => $user['username'],
          'email'      => $user['password'],  
          'user_level' => $user['user_level'],
          'first_name' => $user['first_name'],
          'last_name'  => $user['last_name'],
          'location_id'=> $user['location_id'],
          'tenant_id'  => $user['tenant_id'],
          'logged_in'  => TRUE,
        ];
        $this->session->set_userdata($sesdata);
        $this->main_model->log_action($user_id,'login');

        return redirect('dashboard');
      }

      // bad code
      $this->session->set_flashdata('error','Invalid authentication code.');
      return redirect('home/verify_2fa');
    }

    // on GET: show the entry form
    $this->load->view('auth/verify_2fa');
  }








function logout(){
$this->session->unset_userdata('id');
redirect(base_url()  . "home");

}
 

public function singout(){
$this->session->unset_userdata('username');
redirect(base_url()  . "home/login");


}



public function page_not_found(){
//$this->session->unset_userdata('id');
$this->load->view("page_not_found");

}

  
public function access_required(){
//$this->session->unset_userdata('id');
$this->load->view("access_required");

}
//End of Home
}