<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Products_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // used by the form to render the dropdown
    public function get_all_min()
    {
        // return id, name, price
        return $this->db->select('id, name, price')
                        ->from('products')            // <-- adjust table name if different
                        ->order_by('name','ASC')
                        ->get()->result_array();
    }

    // used by AJAX /requests/price/{id}
    public function get_price($id)
    {
        return $this->db->select('price')
                        ->from('products')            // <-- adjust table name if different
                        ->where('id', (int)$id)
                        ->get()->row_array();
    }
}
