<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
class Settings extends CI_Controller {

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
  $this->load->model('Higaad_model');   // ADD THIS
 

}



public function index()
{
  if ( !$this->session->userdata('username'))
   redirect(base_url()  . "home/login");

  $data["city"] = 0;
  $data["location_type"] = 0;
  $data["location"] = 0;
  $data["users"] = $this->Model_settings->get_active_users();
  $data['ac_years'] = 0;
  $data['terms'] = 0;

  // NEW: academic years for modal
  $data['academic_years'] = $this->Higaad_model->get_all_academic_years();

  $data["users_level_users"] = $this->Model_settings->get_active_users_level_users();

  $this->load->view("settings/settings_index" ,$data );
}

function page_not_found(){
  $this->load->view("page_not_found");
}


  // API endpoint to fetch company name
    public function get_company_name() {
        // Check for the authorization token
        $token = $this->input->get_request_header('Authorization', TRUE);

        if ($token !== '123abc') {
            // If the token doesn't match, respond with a 401 Unauthorized
            $response = array('status' => 'error', 'message' => 'Unauthorized');
            echo json_encode($response);
            return;
        }

        // Fetch the company info from the model
        $company_info = $this->Model_settings->get_company_info();

        if ($company_info) {
            // If company info exists, return the company name as JSON
            $response = array('status' => 'success', 'company_name' => $company_info['company_name']);
        } else {
            // If no company info is found, respond with an error
            $response = array('status' => 'error', 'message' => 'Company info not found');
        }

        echo json_encode($response);
    }









  

//*********************** 

//****************** User accessibility 
public function accessibility_applications(){
  $data['app_data'] =  $resultList =  $this->Model_settings->get_applications();
  $this->load->view("settings/accessibility_applications" , $data);

}


public function accessibility_users(){
  $data['app_data'] =  $resultList =  $this->Model_settings->get_applications();
  $this->load->view("settings/accessibility_users" , $data);

}


public function fetch_accessibility_users()
{
  $this->load->model("main_model");
   // $resultList = $this->main_model->fetch_all_tables('*','tbl_location',array());
  $resultList =  $this->Model_settings->get_users_all_level_users();
  $result = array();
  $view = '';

  $i = 1;
  foreach ($resultList as $key => $value) {

    $view = '<a  href="'.base_url('settings/view_accessibility_user/'.$value['id']).'"id="table_button"   class="btn btn-sm btn-secondary  rounded-pill"  href="#"> &nbsp;&nbsp;  View &nbsp;&nbsp;</a> ';



    $result['data'][] = array(
      $value['sno'],
      $value['id'],
      $value['full_name'],
      $value['email'],


      $view,
    );
  }
  echo json_encode($result);
}




public function view_accessibility_application(){
 $id    =  $this->uri->segment(3);

 if ($id) {
  $check_app_id = $this->Model_settings->check_app_id($id);
  if ($check_app_id != 0) {
   $data['app_name'] =  $this->Model_settings->get_app_name($id);
   $data['app_data'] = $this->Model_settings->get_accessibility_application($id);
   $this->load->view("settings/view_accessibility_application" , $data);

 } else{ $this->page_not_found();}
} else{ $this->page_not_found();}

}     


//User accecability   

public function view_accessibility_user(){
 $id    =  $this->uri->segment(3);

 if ($id) {
  $check_user_id = $this->Model_settings->check_user_id($id);
  if ($check_user_id != 0) {
      // $data['app_name'] =  $this->Model_settings->get_app_name($id);
    $data['full_name'] =  $this->Model_settings->get_user_full_name($id);
    $data['user_data'] = $this->Model_settings->get_accessibility_user($id);
    $this->load->view("settings/view_accessibility_user" , $data);

  } else{ $this->page_not_found();}
} else{ $this->page_not_found();}

} 

public function user_accessibility(){
  $id    =  $this->uri->segment(3);

  if ($id) {
    $check_user_id_level_user = $this->Model_settings->check_user_id_level_user($id);

    if ($check_user_id_level_user != 0) {
      $data['app_data'] =  $resultList =  $this->Model_settings->get_applications();
      $data['user_id'] =  $id;

      $data['full_name'] =  $this->Model_settings->get_user_full_name($id);
      $data["users_level_users"] = $this->Model_settings->get_active_users_level_users();

      $this->load->view("settings/view_user_accessibility" , $data);



    } else{
      $this->page_not_found();
    }
    

  } else{
    $this->page_not_found();
  }

}

public function configure_user_accessibility()
{  

  $id    =  $this->uri->segment(3);
  $app_id = $this->uri->segment(4);

  if ($id ) {
    if ($app_id) {

      $check_user_id_level_user = $this->Model_settings->check_user_id_level_user($id);
      $check_app_id = $this->Model_settings->check_app_id($app_id);
      if ($check_user_id_level_user != 0) {

        if ($check_app_id != 0) {
         // Get user data 
          $result = array();
          $user_data = $this->Model_settings->get_single_user_data($id);
          $result['user'] = $user_data;
          $data['user_data'] = $result;
 // Get App name
          $data['app_name'] =  $this->Model_settings->get_app_name($app_id);
          $data['app_access_levels'] =  $this->Model_settings->get_app_access_levels($app_id);

          $data['access_exists'] =  $this->Model_settings->check_access_exists($id,$app_id);

          $data['user_access_level'] =  $this->Model_settings->get_user_access_level($id,$app_id);
          $data['access_id'] =  $this->Model_settings->get_user_access_id($id,$app_id);

          $data['application_id'] = $app_id;

          $this->load->view("settings/configure_user_accessibility" , $data);



         //chek app id
        } else{

          $this->page_not_found();
        }

        //chek user id
      } else{

        $this->page_not_found();
      }



//app id 
    }else{
      $this->page_not_found();
    }


    

  //user id
  }else{
    $this->page_not_found();
  }


}

//
public function add_user_access()
{

  $data = array(

    'user_id' => $this->input->post('user_id'),
    'app_id' => $this->input->post('app_id'),
    'access_level' => $this->input->post('access_level'),
    'created_at'  => date('d-M-Y H:i:s'),
    'updated_at'  => date('d-M-Y H:i:s'),


    'granted_by' => $this->session->userdata('id'),
  );
  $insert = $this->Model_settings->insert_user_access($data);
  echo json_encode($insert);
}

public function update_user_access()
{
  $access_id = $this->input->post('access_id');
  $data = array(
    'access_level' => $this->input->post('access_level_1'),
    'updated_at'  => date('d-M-Y H:i:s'),
    'granted_by' => $this->session->userdata('id'),
  );
  $update = $this->main_model->update_all_tables('tbl_app_accessibility',$data,array('id'=>$access_id));
  if($update==true)
  {
    echo 1;
  }
  else{
    echo 2;

  }
}


public function remove_user_access()
{
  $access_id = $this->input->post('access_id_1');
  $deleteData = $this->main_model->delete_data('tbl_app_accessibility',array('id'=>$access_id));
  if($deleteData==true)
  {
    echo 1;
  }
  else
  {
    echo 2;
  }
}



//*********************************** 
//========================= Fetch All
public function fetch_all_tables($data,$tablename,$where)
    {
        $query = $this->db->select($data)
                        ->from($tablename)
                        ->where($where)
                        ->get();
        return $query->result_array();
    }
//************************** End Fetch All

//=================== Fetch All ID
public function fetch_all_id($data,$tablename,$where)
    {
        $query = $this->db->select($data)
                        ->from($tablename)
                        ->where($where)
                        ->get();
        return $query->row_array();
    }
//******************* End Fetch All ID
//===================== Update All
public function update_all_tables($tablename, $data, $where)
    {
        $query = $this->db->update($tablename,$data,$where);
        return $query;
    }
//*********************** End Update All
//======================== Delete Date
public function delete_data($tablename,$where)
    {
        $query = $this->db->delete($tablename,$where);
        return $query;
    }
//************************** End Delete Data
// *************** Users

public function users(){
  $data["city"] = 0;
  $data["location_type"] =0;
  $this->load->view("settings/users" , $data);

}
public function add_user()
{

  $this->form_validation->set_rules('username', 'Username', 'trim|required');
  
  $user_exists = $this->model_auth->check_user($this->input->post('username'));
  $email_exists = $this->model_auth->check_email($this->input->post('user_email'));
  $phone_exists = $this->model_auth->check_phone($this->input->post('user_phone'));



  if ($this->form_validation->run() == TRUE) {          


// check user
   if (!$user_exists) {

// check email
    if (!$email_exists) {
// phone email
      if (!$phone_exists) {

       $data = array(
        'username'  => $this->input->post('username'),

        'password' => md5($this->input->post("user_password")),
        'phone_number'  => $this->input->post('user_phone'),
        'email'  => $this->input->post('user_email'),
        'user_level'  => $this->input->post('user_level'),

        'first_name'  => $this->input->post('first_name'),
        'last_name'  => $this->input->post('last_name'),

        'location_id'  => $this->input->post('user_location'),

        'status'  => 1,  
        'tenant_id'  => $this->main_model->get_tenant_id(),

        'added_date'  => date('Y-m-d'),
        'created_at'  => date('d-M-Y H:i:s'),
        'updated_at'  => date('d-M-Y H:i:s'),
        'recorded_by'  => $this->session->userdata('id'),

      );

       $user_id = $this->Model_settings->insert_user($data);

       if($user_id == true) {
        $this->session->set_flashdata('success', 'Added Successfully');
        redirect('settings/view_user/'.$user_id, 'refresh');


      }

      else {
        $this->session->set_flashdata('errors', 'Error occurred!!');
        redirect('settings/index/');
      }




    }else{
      $this->session->set_flashdata('danger', 'Error: Phone Number already exists');
      redirect('settings');
    }

  }




  else{
    $this->session->set_flashdata('danger', 'Error: Email already exists');
    redirect('settings');

         }// end email check 1

// end email check 2

       }
       else{
        $this->session->set_flashdata('danger', 'Error: Username already exists');
        redirect('settings');
      }
    }


    else{
      $this->load->view("settings/settings_index");


    }

  }


  public function update_user()
  {
    $id    =  $this->input->post('user_id');

    $this->form_validation->set_rules('user_id', 'Username', 'trim|required');

    $user_exists = $this->model_auth->check_user_for_update($this->input->post('username'), $this->input->post('user_id'));
    $email_exists = $this->model_auth->check_email_for_update($this->input->post('user_email'), $id);
    $phone_exists = $this->model_auth->check_phone_for_update($this->input->post('user_phone'), $id);


    
    if ($this->form_validation->run() == TRUE) {          
     if (!$user_exists) {
       $data = array(
        'username'  => $this->input->post('username'),
        'phone_number'  => $this->input->post('user_phone'),
        'email'  => $this->input->post('user_email'),
        'user_level'  => $this->input->post('user_level'),

        'first_name'  => $this->input->post('first_name'),
        'last_name'  => $this->input->post('last_name'),
                'mobile'  => $this->input->post('mobile'),


        

        'location_id'  => $this->input->post('user_location'),
        'updated_at'  => date('d-M-Y H:i:s'),
        'recorded_by'  => $this->session->userdata('id'),

      );

       $update = $this->Model_settings->update_user($data, $id);

       if($update == true) {
        $this->session->set_flashdata('info', 'Updated Successfully');
        redirect('settings/view_user/'.$id, 'refresh');


      }

      else {
        $this->session->set_flashdata('errors', 'Error occurred!!');
        redirect('settings/view_user/'.$id, 'refresh');
      }




    }
    else{
      $this->session->set_flashdata('danger', 'Error: Username already exists');
      redirect('settings/view_user/'.$id, 'refresh');
    }
//***
  }
  

  else{
    $this->load->view("settings/settings_index");


  }

}



public function fetch_users_all()
{
  $this->load->model("main_model");
   // $resultList = $this->main_model->fetch_all_tables('*','tbl_location',array());
  $resultList =  $this->Model_settings->get_users_all();
  $result = array();
  $view = '';

  $i = 1;
  foreach ($resultList as $key => $value) {

    $view = '<a  href="'.base_url('settings/view_user/'.$value['id']).'"id="table_button"   class="btn btn-sm btn-secondary  rounded-pill"  href="#"> &nbsp;&nbsp;  View &nbsp;&nbsp;</a> ';




    if($value['user_level'] == 1) {
      $level = '<td>User</td>';  
    }


    else {
      $level = '<td>Admin </td>';  
    }  

    if($value['status'] == 0) {
      $show_status = '<a class="btn-sm btn-default text-danger"> <b>Inactive</b> </a>';  
    }

    else {
      $show_status = '<a class="btn-sm btn-default text-success"> <b>Active</b> </a>'; 
    }  



    $result['data'][] = array(
      $value['sno'],
      $value['id'],
      $value['username'],
      $value['full_name'],
      $value['email'],
      $value['phone_number'],
      $value['description'],
      $level,
      $show_status,  


      $view,
    );
  }
  echo json_encode($result);
}

public function view_user()
{  

  $id    =  $this->uri->segment(3);


  if ($id ) {
    $check_user_id = $this->Model_settings->check_user_id($id);
    if ($check_user_id != 0) {


         // Get location  data 
      $result = array();
      $user_data = $this->Model_settings->get_single_user_data($id);
      $result['user'] = $user_data;
      $data['user_data'] = $result;
//get location
      $user_location = $this->Model_settings->check_user_location($id);
      $data["location"] = 0;



      $this->load->view("settings/view_user" , $data);

    } else{

      $this->page_not_found();
    }
  }else{
    $this->page_not_found();
  }


}


public function deactivate_user()
{
  $id = $this->input->post('user_id_2');
  $data = array(

    'status' => 0,
  );
  $update = $this->main_model->update_all_tables('users',$data,array('id'=>$id));
  if($update==true)
  {
    echo 1;
  }
  else{
    echo 2;

  }
}
public function activate_user()
{
  $id = $this->input->post('user_id_3');
  $data = array(

    'status' => 1,
  );
  $update = $this->main_model->update_all_tables('users',$data,array('id'=>$id));
  if($update==true)
  {
    echo 1;
  }
  else{
    echo 2;

  }
}

public function change_pass()
{
  $id = $this->input->post('user_id_4');
  $data = array(

    'password' => md5($this->input->post('change_password')),
  );
  $update = $this->main_model->update_all_tables('users',$data,array('id'=>$id));
  if($update==true)
  {
    echo 1;
  }
  else{
    echo 2;

  }
}





 public function generate_token()
{
    $user_id = $this->input->post('user_id');

    // Validate the user ID
    if (empty($user_id)) {
        echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
        return;
    }

    $this->load->model('Model_settings');

    // Generate a random 16-digit token
    $token = bin2hex(random_bytes(8));

    // Prepare the data for insertion
    $data = [
        'user_id' => $user_id,
        'token' => $token,
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Insert the token
    $inserted = $this->Model_settings->insert_token($data);

    if ($inserted) {
        // Return success response
        echo json_encode(['status' => 'success', 'token' => $token]);
    } else {
        // Log the error and return failure response
        log_message('error', 'Failed to insert token: ' . $this->db->last_query());
        echo json_encode(['status' => 'error', 'message' => 'Failed to save token.']);
    }
}




 public function generate_location_token()
{
    $user_id = $this->input->post('user_id');

    // Validate the user ID
    if (empty($user_id)) {
        echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
        return;
    }

    $this->load->model('Model_settings');

    // Generate a random 16-digit token
    $token = bin2hex(random_bytes(32));

    // Prepare the data for insertion
    $data = [
        'location_id' => $user_id,
        'token' => $token,
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Insert the token
    $inserted = $this->Model_settings->insert_location_token($data);

    if ($inserted) {
        // Return success response
        echo json_encode(['status' => 'success', 'token' => $token]);
    } else {
        // Log the error and return failure response
        log_message('error', 'Failed to insert token: ' . $this->db->last_query());
        echo json_encode(['status' => 'error', 'message' => 'Failed to save token.']);
    }
}


//********* End Users






//---------------------------- Classes ------------------------------------
 

//End controller
}