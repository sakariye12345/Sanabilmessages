<?php defined('BASEPATH') OR exit('No direct script access allowed');

class RequestsCreate extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
          $this->load->model('Requests_model', 'requests'); // <-- alias 'requests'
        $this->load->model('Customers_model', 'customers'); // <-- load it here
        $this->load->model('Products_model',  'products'); // <-- hal mar ku load garee

    }

    public function index()
    {

        $this->load->model('Products_model', 'products');
    $data['products']  = $this->products->get_all_min();
        //$data['customers'] = $this->customers->get_all_customers(); // use the alias
    //    $data['today']     = date('Y-m-d');
        $this->load->view('requests/form', $data);
    }



public function store()
{
    // ---- 1) Read ONLY what the form shows (totals + line items) ----
    $head = [
        'total_ht'   => (float)$this->input->post('total_ht')   ?: 0,
        'remise'     => (float)$this->input->post('remise')     ?: 0,
        'net_ht'     => (float)$this->input->post('net_ht')     ?: 0,
        'tva_amount' => (float)$this->input->post('tva_amount') ?: 0,
        'timbre'     => (float)$this->input->post('timbre')     ?: 0,
        'total_ttc'  => (float)$this->input->post('total_ttc')  ?: 0,
        'created_at' => date('Y-m-d H:i:s'),
    ];

    $this->db->trans_start();

    // A) Insert header into invoice_requests
    $this->db->insert('invoice_requests', $head);
    $request_id = $this->db->insert_id();

    // B) Insert items into invoice_request_items
    // NOTE: Make sure your form uses these names:
    // name="product_id[]", name="qty[]", name="price[]"
    $product_ids = (array)$this->input->post('product_id');
    $qtys        = (array)$this->input->post('qty');
    $prices      = (array)$this->input->post('price');

    foreach ($product_ids as $i => $pid) {
        $pid    = (int)$pid;
        $qty    = isset($qtys[$i])   ? (float)$qtys[$i]   : 0;
        $price  = isset($prices[$i]) ? (float)$prices[$i] : 0;
        $amount = $qty * $price; // always compute server-side

        if ($pid <= 0 || $qty <= 0) continue; // skip empty rows

        $item = [
            'request_id' => $request_id,
            'line_no'    => $i + 1,
            'product_id' => $pid,
            'qty'        => $qty,
            'unit_price' => $price,
            'amount'     => $amount,
        ];
        $this->db->insert('invoice_request_items', $item);
    }

    $this->db->trans_complete();

    if (!$this->db->trans_status()) {
        $err = $this->db->error();
        show_error('DB error: '.$err['message'], 500);
        return;
    }

    // Go to a page of your choice (change as needed)
    redirect('requests/view/'.$request_id);
}


public function price($id)
{
    $this->load->model('Products_model','products');
    $row   = $this->products->get_price((int)$id);   // soo qaado qiimaha ID-ga
    $price = isset($row['price']) ? (float)$row['price'] : 0;
    $this->output->set_content_type('application/json')
                 ->set_output(json_encode(['price' => $price]));
}



}
