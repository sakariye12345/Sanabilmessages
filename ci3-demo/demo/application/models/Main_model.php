<?php
class Main_model extends CI_Model {




// App Access

public function get_access_level($app_id)
    {
        $user_id = $this->session->userdata('id');
        $sql = "SELECT COALESCE(SUM(access_level),0) as access_level FROM tbl_app_accessibility WHERE user_id ='$user_id' and app_id = '$app_id' ";
        $query = $this->db->query($sql);
        $result = $query->row();
        return $result->access_level;  
    }


 


public function get_tenant_id()
    {
        
        $sql = "SELECT tenant_id FROM company_info WHERE id = 1;";
        $query = $this->db->query($sql);
        $result = $query->row();
        return $result->tenant_id;  
    }


 

  



public function check_old_password($old_password) 
{
     $id = $this->session->userdata('id');
    if($old_password) {
        $sql = "SELECT id FROM users WHERE id =  ? and password = ? ";
        $query = $this->db->query($sql, array($id, $old_password));
        $result = $query->num_rows();
        return ($result == 1) ? true : false;
    }

    return false;
}


public function update_pws($data)
{
    if($data) {
        $id = $this->session->userdata('id');
        $this->db->where('id', $id);
        $update = $this->db->update('users', $data);
        return ($update == true) ? true : false;
    }
}

public function get_all_active_users(){
 
    $quary = $this->db->query("SELECT id , concat(first_name, ' ', last_name , ' - ', 
      email, ' - ', phone_number) as user_info FROM users where status = 1");
        return $quary->result();
}

public function get_all_active_departments(){
 
    $quary = $this->db->query("SELECT *  FROM tbl_departments where status = 1");
        return $quary->result();
}



public function get_all_active_departments_filtered($id){
 
    $quary = $this->db->query("SELECT *  FROM tbl_departments where status = 1 and id <> '$id'");
        return $quary->result();
}



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
//************************** End Delete Data





 
function adminExist($username,$password){

	$checkAdmin = $this->db->where(['username'=>$username, 'password'=>$password])
							->get('users');
							if ($checkAdmin->num_rows() > 0 ) {
							return $checkAdmin->row();
							}

}

function validate($email,$password,$status){
    $this->db->where('username',$email);
    $this->db->where('password',$password);
    $this->db->where('status',$status);
    $result = $this->db->get('users',1);
    return $result;
  }




  public function get_user($id)
    {
        $this->db->where('username', $id);
        $query = $this->db->get('users');
        return $query->row();
    }

    public function update_user($id, $userdata)
    {
        $this->db->where('username', $id);
        $this->db->update('users', $userdata);
    }




 
 

public function get_company_name()
    { 
        $sql = " SELECT company_name FROM `company` WHERE id=1 ";
        $query = $this->db->query($sql);
         $result = $query->row();
         return $result->company_name;  
    }

 


// ------------------- 2fa -----------------
public function set_2fa_secret($user_id, $secret)
{
    return $this->db
                ->where('id', $user_id)
                ->update('users', ['google_2fa_secret' => $secret]);
}
  public function reset_two_factor_secret($user_id)
    {
        $this->db->where('id', $user_id);
        // to set it to NULL:
        return $this->db
                    ->set('google_2fa_secret', null)
                    ->update('users');
        // OR if you prefer empty string:
        // ->set('google_2fa_secret', '')
    }

 public function get_user_2fa($id)
{
    $query = $this->db
        ->select('google_2fa_secret')
        ->from('users')
        ->where('id', $id)
        ->get();

    if ($query->num_rows() === 1) {
        return $query->row()->google_2fa_secret;
    }

    return null;
}



public function get_single_user_data($id = null)
{
  if($id) {
   $sql = "SELECT u.id,  u.*, ROW_NUMBER() OVER ( ORDER BY u.id ) as sno, u.username, u.user_level, u.phone_number, u.email, u.first_name, u.last_name, concat( u.first_name, ' ', u.last_name) as full_name, u.location_id, l.description, u.status, u.added_date, u.recorded_by , u.created_at, u.updated_at  FROM users u inner join tbl_location l on u.location_id = l.id

    WHERE   u.id = ?   ";
   $query = $this->db->query($sql, array($id));
   return $query->row_array();
 }
}


public function get_user_by_id($id)
{
  return $this->db
              ->where('id',$id)
              ->get('users')
              ->row_array();
}


public function log_action($user_id, $action) {
        $data = array(
            'user_id' => $user_id,
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s')
        );

        // Insert the log into the database
      //  $this->db->insert('login_log', $data);
    }


// ------------------- End 2fa -----------------


//end 
}
?>