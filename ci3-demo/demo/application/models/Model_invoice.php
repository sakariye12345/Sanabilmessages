
<?php
class Model_invoice extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // get customers data
    public function get_customer_balances()
    {
        $this->db->select('customer_number, customer_name, type, phone, email, address, city, region_state, country, balance, status');
        $this->db->from('customers');
        $this->db->order_by('customer_name', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }
}


