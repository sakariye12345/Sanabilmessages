<?php defined('BASEPATH') OR exit('No direct script access allowed');

class RequestsView extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // auth/permissions checks here...
        $this->load->model('Requests_model', 'requests');
        $this->load->helper(['url']);
        date_default_timezone_set('Africa/Mogadishu');
    }

    public function index($id)
    {
        $doc = $this->requests->get($id);
        if (!$doc) show_404();
        $this->load->view('requests/view', $doc); // view w/ print button
    }

    public function print_doc($id)
    {
        $doc = $this->requests->get($id);
        if (!$doc) show_404();
        // same template, print-optimized
        $this->load->view('requests/view', $doc);
    }

 public function cancel($id)
{
    $id = (int)$id;
    if ($id <= 0) show_404();

    // Load model
    $this->load->model('Requests_model', 'requests');

    // Update status → Cancelled
    $ok = $this->requests->set_status($id, 'Cancelled');

    if ($ok) {
        $this->session->set_flashdata('success', 'Request cancelled successfully.');
    } else {
        $this->session->set_flashdata('error', 'Failed to cancel request.');
    }

    redirect('requests/view/'.$id);
}


}