<?php 

class Model_auth extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/* 
		This function checks if the email exists in the database
	*/
	

	public function check_user($user) 
	{
		if($user) {
			$sql = 'SELECT * FROM users WHERE username = ?';
			$query = $this->db->query($sql, array($user));
			$result = $query->num_rows();
			return ($result == 1) ? true : false;
		}

		return false;
	}

public function check_user_for_update($user, $id) 
	{
		if($user) {
			$sql = 'SELECT * FROM users WHERE  id <> "$id"   and username = "$user"';
			$query = $this->db->query($sql, array($user));
			$result = $query->num_rows();
			return ($result == 1) ? true : false;
		}

		return false;
	}



	public function check_email($email) 
	{
		if($email) {
			$sql = 'SELECT * FROM users WHERE email = ?';
			$query = $this->db->query($sql, array($email));
			$result = $query->num_rows();
			return ($result == 1) ? true : false;
		}

		return false;
	}

	public function check_email_for_update($email, $id) 
	{
		if($email) {
			$sql = 'SELECT * FROM users WHERE  id <> "$id" and email = ?';
			$query = $this->db->query($sql, array($email));
			$result = $query->num_rows();
			return ($result == 1) ? true : false;
		}

		return false;
	}


	public function check_phone($phone) 
	{
		if($phone) {
			$sql = 'SELECT * FROM users WHERE phone_number = ?';
			$query = $this->db->query($sql, array($phone));
			$result = $query->num_rows();
			return ($result == 1) ? true : false;
		}

		return false;
	}

	public function check_phone_for_update($phone, $id) 
	{
		if($phone) {
			$sql = 'SELECT * FROM users WHERE  id <> "$id" and phone_number = ?';
			$query = $this->db->query($sql, array($phone));
			$result = $query->num_rows();
			return ($result == 1) ? true : false;
		}

		return false;
	}

	/* 
		This function checks if the email and password matches with the database
	*/
	public function login($email, $password) {
		if($email && $password) {
			$sql = "SELECT * FROM users WHERE email = ?";
			$query = $this->db->query($sql, array($email));

			if($query->num_rows() == 1) {
				$result = $query->row_array();

				$hash_password = password_verify($password, $result['password']);
				if($hash_password === true) {
					return $result;	
				}
				else {
					return false;
				}

				
			}
			else {
				return false;
			}
		}
	}
}