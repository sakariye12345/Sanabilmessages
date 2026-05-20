<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Customers_model extends CI_Model
{
    public function get_all_min()
    {
        return $this->db->select('customer_id ,customer_name,address,  city')
                        ->from('tbl_customers')
                        ->order_by('customer_name', 'ASC')
                        ->get()->result_array();
    }

    public function get_min($id)
    {
        return $this->db->select('customer_id ,customer_name, address,  city')
                        ->from('tbl_customers')
                        ->where('id', (int)$id)
                        ->get()->row_array();
    }
}
