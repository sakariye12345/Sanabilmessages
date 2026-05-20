<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Requests extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url']);
          if (!$this->session->userdata('username')) {
            redirect(base_url('home/login'));
        }
        $this->load->model('Products_model', 'products'); // needs get_all_min() and get_price($id)
        date_default_timezone_set('Africa/Mogadishu');
          $this->load->model('Customers_model', 'customers');  // <-- MUHIIM
    }

    /** Optional landing: go straight to request index */
    public function index()
    {
        $this->load->view('requests/request_index');
    }

    public function list()
{
    // Haddii aadan rabin Model, waxaa ku filan tan:
    $rows = $this->db->select('id, created_at, customer_name, customer_addr1, customer_addr2,
                               total_ht, remise, net_ht, tva_amount, timbre, total_ttc, status')
                     ->from('invoice_requests')
                     ->order_by('id','DESC')
                     ->get()->result_array();

    $data['rows'] = $rows;
    $this->load->view('requests/index', $data);
}

    /** Show the proforma form (needs view: application/views/requests/form.php) */
    public function create()
    {
         $data['products']  = $this->products->get_all_min();
    $data['customers'] = $this->customers->get_all_min();   // <-- REQUIRED
    $this->load->view('requests/form', $data);
    }
   

   public function customer($id)
{
    $id  = (int)$id;
    $row = $this->customers->get_min($id); // implement to return: id,name,addr1,addr2,city,po_box...
    if (!$row) $row = [];

    $this->output->set_content_type('application/json')
                 ->set_output(json_encode($row));
}
    /** Save ONLY what’s in the form: totals + line items */
    public function store()
{
    // ---- Read editable client fields ----
    $customer_name  = trim((string)$this->input->post('customer_name',  true));
    $customer_addr1 = trim((string)$this->input->post('customer_addr1', true));
    $customer_addr2 = trim((string)$this->input->post('customer_addr2', true));

    // ---- Read totals (you already had these) ----
    $head = [
        'customer_name'  => $customer_name,
        'customer_addr1' => $customer_addr1,
        'customer_addr2' => $customer_addr2,

        'total_ht'   => (float)$this->input->post('total_ht')   ?: 0,
        'remise'     => (float)$this->input->post('remise')     ?: 0,
        'net_ht'     => (float)$this->input->post('net_ht')     ?: 0,
        'tva_amount' => (float)$this->input->post('tva_amount') ?: 0,
        'timbre'     => (float)$this->input->post('timbre')     ?: 0,
        'total_ttc'  => (float)$this->input->post('total_ttc')  ?: 0,
        'created_at' => date('Y-m-d H:i:s'),
    ];

    // ---- Start TX & insert header ----
    $this->db->trans_start();
    $this->db->insert('invoice_requests', $head);
    $request_id = $this->db->insert_id();

    // ---- Insert items (product_id[], qty[], price[]) ----
    $product_ids = (array)$this->input->post('product_id');
    $qtys        = (array)$this->input->post('qty');
    $prices      = (array)$this->input->post('price');

    foreach ($product_ids as $i => $pid) {
        $pid    = (int)$pid;
        $qty    = isset($qtys[$i])   ? (float)$qtys[$i]   : 0;
        $price  = isset($prices[$i]) ? (float)$prices[$i] : 0;
        if ($pid <= 0 || $qty <= 0) continue;

        $this->db->insert('invoice_request_items', [
            'request_id' => $request_id,
            'line_no'    => $i + 1,
            'product_id' => $pid,
            'qty'        => $qty,
            'unit_price' => $price,
            'amount'     => $qty * $price,
        ]);
    }

    $this->db->trans_complete();

    if (!$this->db->trans_status()) {
        $err = $this->db->error();
        show_error('DB error: '.$err['message'], 500);
        return;
    }

    redirect('requests/view/'.$request_id);
}

    /** Detail page (needs view: application/views/requests/view.php) */
    public function view($id)
{
    $id = (int)$id;

    // header
    $header = $this->db->get_where('invoice_requests', ['id' => $id])->row_array();
    if (!$header) { show_404(); return; }

    // items
    $items = $this->db->order_by('line_no','ASC')
                      ->get_where('invoice_request_items', ['request_id' => $id])
                      ->result_array();

    // products for <select> options (id,name,price)
    $products = $this->products->get_all_min();

    $data = compact('header','items','products');
    $this->load->view('requests/view', $data); // file-ka hoos ku qoran
}

public function print_doc($id)
{
    $id = (int)$id;

    $header = $this->db->get_where('invoice_requests', ['id'=>$id])->row_array();
    if (!$header) { show_404(); return; }

    $items  = $this->db->order_by('line_no','ASC')
                       ->get_where('invoice_request_items', ['request_id'=>$id])
                       ->result_array();

    // so we can print product names, not only IDs
    $products = $this->products->get_all_min(); // id, name, price

    $data = compact('header','items','products');
    $this->load->view('requests/print', $data);
}

public function edit($id)
{
     $id = (int)$id;

    $header = $this->db->get_where('invoice_requests', ['id'=>$id])->row_array();
    if (!$header) { show_404(); return; }

    $items  = $this->db->order_by('line_no','ASC')
                       ->get_where('invoice_request_items', ['request_id'=>$id])
                       ->result_array();

    $products = $this->products->get_all_min(); // id, name, price

    $data = compact('header','items','products');
    $this->load->view('requests/edit', $data);   // use the updated view below
}

// Save EDIT (replace header + items)
public function update($id)
{
    $id = (int)$id;

    $head = [
        'total_ht'   => (float)$this->input->post('total_ht')   ?: 0,
        'remise'     => (float)$this->input->post('remise')     ?: 0,
        'net_ht'     => (float)$this->input->post('net_ht')     ?: 0,
        'tva_amount' => (float)$this->input->post('tva_amount') ?: 0,
        'timbre'     => (float)$this->input->post('timbre')     ?: 0,
        'total_ttc'  => (float)$this->input->post('total_ttc')  ?: 0,
    ];

    $product_ids = (array)$this->input->post('product_id');
    $qtys        = (array)$this->input->post('qty');
    $prices      = (array)$this->input->post('price');

    $this->db->trans_start();

    // update header
    $this->db->where('id', $id)->update('invoice_requests', $head);

    // replace items (simple & safe)
    $this->db->delete('invoice_request_items', ['request_id'=>$id]);

    foreach ($product_ids as $i => $pid) {
        $pid   = (int)$pid;
        $qty   = isset($qtys[$i])   ? (float)$qtys[$i]   : 0;
        $price = isset($prices[$i]) ? (float)$prices[$i] : 0;
        if ($pid <= 0 || $qty <= 0) continue;

        $this->db->insert('invoice_request_items', [
            'request_id' => $id,
            'line_no'    => $i + 1,
            'product_id' => $pid,
            'qty'        => $qty,
            'unit_price' => $price,
            'amount'     => $qty * $price,
        ]);
    }

    $this->db->trans_complete();
    if (!$this->db->trans_status()) {
        $err = $this->db->error();
        show_error('DB error: '.$err['message'], 500);
        return;
    }

    redirect('requests/view/'.$id);
}


public function cancel($id)
{
    $id = (int)$id;
    if ($id <= 0) { show_404(); return; }

    // If you want soft-delete instead, comment next lines and set deleted_at instead.
    $this->db->trans_start();
    $this->db->delete('invoice_requests', ['id' => $id]); // ON DELETE CASCADE removes items
    $this->db->trans_complete();

    if (!$this->db->trans_status()) {
        $err = $this->db->error();
        show_error('DB error: '.$err['message'], 500);
        return;
    }

    // Go back to list or create
    redirect('requests');
}


    /** AJAX: return price for a product ID */
    public function price($id)
    {
        $row   = $this->products->get_price((int)$id);
        $price = isset($row['price']) ? (float)$row['price'] : 0;
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode(['price' => $price]));
    }


}




