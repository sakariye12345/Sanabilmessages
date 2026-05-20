<?php 

class Model_settings extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

 

public function get_active_users(){

  $quary = $this->db->query("SELECT u.id, concat(u.first_name, ' ', u.last_name, ' ','-',' ', u.email, ' ','-',' ', u.phone_number ) as user_info from users u where u.status = 1");
  return $quary->result();
}

public function get_active_users_level_users(){

  $quary = $this->db->query("SELECT u.id, concat(u.first_name, ' ', u.last_name, ' ','-',' ', u.email, ' ','-',' ', u.phone_number ) as user_info from users u where u.status = 1 and user_level = 1");
  return $quary->result();
}

public function get_users_all()
{

  $sql = "  SELECT u.id, ROW_NUMBER() OVER ( ORDER BY u.id ) as sno, u.username, u.user_level, u.phone_number, u.email,concat( u.first_name, ' ', u.last_name) as full_name, u.location_id, l.description, u.status, u.added_date, u.recorded_by FROM users u inner join tbl_location l on u.location_id = l.id ";
  $query = $this->db->query($sql);
  return $query->result_array();
}


public function get_users_all_level_users()
{

  $sql = "  SELECT u.id, ROW_NUMBER() OVER ( ORDER BY u.id ) as sno, u.username, u.user_level, u.phone_number, u.email,concat( u.first_name, ' ', u.last_name) as full_name, u.location_id, l.description, u.status, u.added_date, u.recorded_by FROM users u inner join tbl_location l on u.location_id = l.id where user_level = 1 ";
  $query = $this->db->query($sql);
  return $query->result_array();
}
//Get all Admin
public function get_system_users(){

  $quary = $this->db->query("SELECT id , concat(first_name, ' ', last_name) as user_info, email FROM users where id > 0");
  return $quary->result();
}

//Get all Admin
public function get_admin(){

  $quary = $this->db->query("SELECT id , concat(first_name, ' ', last_name) as user_info, email FROM users where user_level = 2 ");
  return $quary->result();
}

public function get_admin_filtered($user_id){

  $quary = $this->db->query("SELECT id , concat(first_name, ' ', last_name) as user_info FROM users where user_level = 2 and id <> $user_id ");
  return $quary->result();
}


public function check_user_id($id)
{
  $sql = "SELECT COALESCE(SUM(id),0) as  id FROM users WHERE id ='$id' ";
  $query = $this->db->query($sql);

  $result = $query->row();
  return $result->id;  
}






public function check_user_id_level_user($id)
{
  $sql = "SELECT COALESCE(SUM(id),0) as  id FROM users WHERE id ='$id' and user_level='1' ";
  $query = $this->db->query($sql);

  $result = $query->row();
  return $result->id;  
}

public function get_single_user_data($id = null)
{
  if($id) {
   $sql = "SELECT u.id, u.mobile, ROW_NUMBER() OVER ( ORDER BY u.id ) as sno, u.username, u.user_level, u.phone_number, u.email, u.first_name, u.last_name, concat( u.first_name, ' ', u.last_name) as full_name, u.location_id, l.description, u.status, u.added_date, u.recorded_by , u.created_at, u.updated_at, token FROM users u inner join tbl_location l on u.location_id = l.id

   left join api_tokens at on u.id = at.user_id
   WHERE   u.id = ?   ";
   $query = $this->db->query($sql, array($id));
   return $query->row_array();
 }
}


public function check_user_location($id)
{
  $sql = "SELECT   location_id from users   
  where id = '$id' ";
  $query = $this->db->query($sql);

  $result = $query->row();
  return $result->location_id;  
}

public function insert_user($data)
{
  if($data) {
   $insert = $this->db->insert('users', $data);
   $user_id = $this->db->insert_id();
   return ($user_id) ? $user_id : false;
 }
}

public function update_user($data, $id)
{
  if($data && $id) {
   $this->db->where('id', $id);
   $update = $this->db->update('users', $data);
   return ($update == true) ? true : false;
 }
}



public function insert_token($data)
{
    // Ensure the insert returns a valid result
    if ($this->db->insert('api_tokens', $data)) {
        return true;
    } else {
        log_message('error', 'Database Insert Error: ' . json_encode($this->db->error()));
        return false;
    }
}


public function insert_location_token($data)
{
    // Ensure the insert returns a valid result
    if ($this->db->insert('api_tokens_location', $data)) {
        return true;
    } else {
        log_message('error', 'Database Insert Error: ' . json_encode($this->db->error()));
        return false;
    }
}
//**************** End Users


//*************** User  accessibility
public function get_applications(){

  $quary = $this->db->query("SELECT * from tbl_applications");
  return $quary->result();

}


public function get_accessibility_application($id){

  $quary = $this->db->query("SELECT ac.id, ROW_NUMBER() OVER ( ORDER BY ac.id ) as sno,  ac.user_id, concat(u.first_name, ' ', u.last_name) as full_name, u.email, ac.app_id, a.app_name, ac.access_level, ac.access_level FROM tbl_app_accessibility ac inner join tbl_applications a on ac.app_id = a.id inner join users u on ac.user_id = u.id where ac.app_id = '$id'
    ");
  return $quary->result();

}


public function get_accessibility_user($id){

  $quary = $this->db->query("SELECT a.id, ROW_NUMBER() OVER ( ORDER BY a.id ) as sno, a.user_id, a.app_id, app.app_name, a.access_level FROM tbl_app_accessibility a inner join tbl_applications app on a.app_id = app.id

    where a.user_id = '$id'
    ");
  return $quary->result();

}


public function check_app_id($app_id)
{
  $sql = "SELECT COALESCE(SUM(id),0) as  id FROM tbl_applications WHERE id ='$app_id' ";
  $query = $this->db->query($sql);

  $result = $query->row();
  return $result->id;  
}

public function get_app_access_levels($app_id)
{
  $sql = "SELECT   access_levels FROM tbl_applications WHERE id ='$app_id' ";
  $query = $this->db->query($sql);

  $result = $query->row();
  return $result->access_levels;  
}

public function get_user_full_name($id)
{
  $sql = "SELECT concat(first_name, ' ', last_name) as full_name  FROM users WHERE id = '$id'  ";
  $query = $this->db->query($sql);

  $result = $query->row();
  return $result->full_name;  
}  
public function get_app_name($app_id)
{
  $sql = "SELECT app_name  FROM tbl_applications WHERE id = '$app_id'  ";
  $query = $this->db->query($sql);

  $result = $query->row();
  return $result->app_name;  
} 

public function check_access_exists($id,$app_id) 
{
  if($id) {
   $sql = 'SELECT * FROM tbl_app_accessibility WHERE app_id = ? and user_id = ?';
   $query = $this->db->query($sql, array($app_id,$id));
   $result = $query->num_rows();
   return ($result == 1) ? true : false;
 }

 return false;
}

public function get_user_access_level($id,$app_id)
{
  $sql = "SELECT COALESCE(SUM(access_level),0) as access_level  FROM tbl_app_accessibility WHERE user_id  = '$id' and app_id = '$app_id'   ";
  $query = $this->db->query($sql);

  $result = $query->row();
  return $result->access_level;  
}



public function get_user_access_id($id,$app_id)
{
  $sql = "SELECT COALESCE(SUM(id),0) as id  FROM tbl_app_accessibility WHERE user_id  = '$id' and app_id = '$app_id'   ";
  $query = $this->db->query($sql);

  $result = $query->row();
  return $result->id;  
}

public function insert_user_access($data)
{
  $query = $this->db->insert('tbl_app_accessibility',$data);
  return $query;
}


	// End User accessibility

  


//end temp



//End Modal
}