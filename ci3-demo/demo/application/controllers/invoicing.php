<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Invoicing extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect(base_url('home/login'));
        }
        // load models you actually use here
        // $this->load->model('Model_invoice');
        // $this->load->model('Model_settings');
    }

    public function index()
    {
        $data = [];
        // fill $data only with what this page needs
        $this->load->view('invoicing/invoicing_index', $data);
    }
    // customers
    public function customer_balances()
    {
        $this->load->model('Model_invoice');
        $data['customers'] = $this->Model_invoice->get_customer_balances();

        $this->load->view('invoicing/customer_balances', $data);
    }


}
