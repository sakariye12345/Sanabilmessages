<?php defined('BASEPATH') OR exit('No direct script access allowed');

class RequestsEdit extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // auth/permissions checks here...
        $this->load->model('Requests_model', 'requests');
        $this->load->helper(['url', 'form']);
        $this->load->library('form_validation');
        date_default_timezone_set('Africa/Mogadishu');
    }

    public function index($id)
    {
        $doc = $this->requests->get($id);
        if (!$doc) show_404();
        $customers = $this->db->select('customer_id, customer_name, address, customer_phone AS phone')
                      ->from('tbl_customers')
                      ->order_by('customer_name')
                      ->get()->result_array();
        $doc['customers'] = $customers;
        $this->load->view('requests/edit', $doc);
    }

    public function update($id)
{
    $action = $this->input->post('__action');

    // If Cancel was clicked → set status and bounce back to view
    if ($action === 'cancel') {
        $ok = $this->requests->set_status((int)$id, 'cancelled'); // or 'canceled'
        $this->session->set_flashdata($ok ? 'success' : 'error',
            $ok ? 'Request cancelled.' : 'Failed to cancel request.');
        return redirect('requests/view/'.$id);
    }

    // Otherwise it's UPDATE: validate + save edits
    $this->form_validation->set_rules('invoice_date', 'Invoice Date', 'required');
    $this->form_validation->set_rules('customer_name', 'Customer Name', 'required');
    if (!$this->form_validation->run()) return $this->index($id);

    $header = [
        'invoice_date'     => $this->input->post('invoice_date', true),
        'customer_id'      => $this->input->post('customer_id') ?: null,
        'customer_name'    => $this->input->post('customer_name', true),
        'customer_address' => $this->input->post('customer_address', true),
        'customer_phone'   => $this->input->post('customer_phone', true),
        'bank_account'     => $this->input->post('bank_account', true),
        'due_days'         => $this->input->post('due_days', true),
        'notes'            => $this->input->post('notes', true),
        'phase_text'       => $this->input->post('phase_text', true),
        'currency'         => 'USD',
        'status'           => 'draft', // keep current workflow
    ];

    // Items
    $codes = (array)$this->input->post('item_code');
    $descs = (array)$this->input->post('item_description');
    $qtys  = (array)$this->input->post('item_qty');
    $uoms  = (array)$this->input->post('item_uom');
    $prices= (array)$this->input->post('item_unit_price');

    $items = [];
    $n = max(count($codes), count($descs), count($qtys), count($uoms), count($prices));
    for ($i=0; $i<$n; $i++) {
        $desc = isset($descs[$i]) ? trim($descs[$i]) : '';
        if ($desc === '') continue;
        $items[] = [
            'code'       => $codes[$i]        ?? null,
            'description'=> $desc,
            'qty'        => (float)($qtys[$i] ?? 0),
            'uom'        => $uoms[$i]         ?? 'unit',
            'unit_price' => (float)($prices[$i] ?? 0),
        ];
    }

    if (empty($items)) {
        $this->session->set_flashdata('error', 'At least one item is required.');
        return redirect('requests/edit/'.$id);
    }

    $ok = $this->requests->update_request((int)$id, $header, $items); // recomputes subtotal/total
    $this->session->set_flashdata($ok ? 'success' : 'error',
        $ok ? 'Request updated.' : 'Failed to update request.');

    return redirect('requests/view/'.$id);
}
}