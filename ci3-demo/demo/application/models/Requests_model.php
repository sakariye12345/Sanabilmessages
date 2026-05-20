<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Requests_model extends CI_Model
{
    private $table_header = 'requests';
    private $table_items  = 'request_items';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        date_default_timezone_set('Africa/Mogadishu');
    }


    public function create(array $header, array $items)
    {
        $this->db->trans_start();

        // Generate invoice_no if missing
        if (empty($header['invoice_no'])) {
            $header['invoice_no'] = $this->generate_invoice_no();
        }

        $header['created_at'] = date('Y-m-d H:i:s');
        $header['updated_at'] = date('Y-m-d H:i:s');

        // compute totals from items and map to your columns
        $totals = $this->compute_totals($items);
        $header['subtotal'] = $totals['subtotal'];  // <-- your DB column
        $header['total']    = $totals['total'];     // <-- your DB column

        $this->db->insert($this->table_header, $header);
        $id = $this->db->insert_id();

        foreach ($items as $i) {
            $row = [
                'request_id' => $id,
                'code'       => isset($i['code']) ? $i['code'] : null,
                'description'=> isset($i['description']) ? $i['description'] : '',
                'qty'        => (float)($i['qty'] ?? 0),
                'uom'        => isset($i['uom']) ? $i['uom'] : 'unit',
                'unit_price' => (float)($i['unit_price'] ?? 0),
                'amount'     => round((float)($i['qty'] ?? 0) * (float)($i['unit_price'] ?? 0), 2),
            ];
            $this->db->insert($this->table_items, $row);
        }

        $this->db->trans_complete();
        return $this->db->trans_status() ? $id : false;
    }

    public function update_request($id, array $header, array $items)
    {
        $this->db->trans_start();

        $header['updated_at'] = date('Y-m-d H:i:s');

        $totals = $this->compute_totals($items);
        $header['subtotal'] = $totals['subtotal'];
        $header['total']    = $totals['total'];

        $this->db->where('id', $id)->update($this->table_header, $header);

        $this->db->where('request_id', $id)->delete($this->table_items);
        foreach ($items as $i) {
            $row = [
                'request_id' => $id,
                'code'       => isset($i['code']) ? $i['code'] : null,
                'description'=> isset($i['description']) ? $i['description'] : '',
                'qty'        => (float)($i['qty'] ?? 0),
                'uom'        => isset($i['uom']) ? $i['uom'] : 'unit',
                'unit_price' => (float)($i['unit_price'] ?? 0),
                'amount'     => round((float)($i['qty'] ?? 0) * (float)($i['unit_price'] ?? 0), 2),
            ];
            $this->db->insert($this->table_items, $row);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function get($id)
    {
        $header = $this->db->get_where($this->table_header, ['id' => $id])->row_array();
        if (!$header) return null;
        $items = $this->db->order_by('id')->get_where($this->table_items, ['request_id' => $id])->result_array();
        return ['header' => $header, 'items' => $items];
    }

    private function generate_invoice_no()
    {
        $year = date('Y');
        $this->db->select('COUNT(*) AS c');
        $this->db->like('created_at', $year, 'after');
        $count = (int)$this->db->get($this->table_header)->row()->c;
        $seq = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        return 'INV/'.$year.'/'.$seq;
    }

    private function compute_totals(array $items)
    {
        $sum = 0.0;
        foreach ($items as $i) {
            $qty  = (float)($i['qty'] ?? 0);
            $unit = (float)($i['unit_price'] ?? 0);
            $sum += $qty * $unit;
        }
        $sum = round($sum, 2);
        return [
            'subtotal' => $sum,
            'total'    => $sum, // no taxes/discounts in your design
        ];
    }

   public function set_status($id, $status)
{
    $this->db->where('id', (int)$id);
    return $this->db->update('requests', [
        'status'     => $status,
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
}

public function list($limit = 50, $offset = 0, $search = null)
{
    if ($search) {
        $this->db->group_start()
                 ->like('invoice_no', $search)
                 ->or_like('customer_name', $search)
                 ->group_end();
    }
    $this->db->order_by('id', 'DESC');
    return $this->db->get('requests', $limit, $offset)->result_array();
}

public function count_all($search = null)
{
    if ($search) {
        $this->db->group_start()
                 ->like('invoice_no', $search)
                 ->or_like('customer_name', $search)
                 ->group_end();
    }
    return (int)$this->db->count_all_results('requests');
}
public function allocate_invoice_no_tx(): string
{
    $year = (int) date('Y');

    $this->db->trans_start();

    $row = $this->db->query(
        "SELECT `last` FROM `invoice_counters` WHERE `year` = ? FOR UPDATE",
        [$year]
    )->row();

    if (!$row) {
        $this->db->insert('invoice_counters', ['year' => $year, 'last' => 0]);
        $last = 0;
    } else {
        $last = (int) $row->last;
    }

    $next = $last + 1;
    $this->db->where('year', $year)->update('invoice_counters', ['last' => $next]);

    $this->db->trans_complete();
    if (!$this->db->trans_status()) {
        throw new Exception('Failed to allocate invoice number.');
    }

    return sprintf('INV/%d/%03d', $year, $next);
}

public function get_request_header($id)
{
    return $this->db->from('invoice_requests')
                    ->where('id', (int)$id)
                    ->get()
                    ->row_array();
}

public function list_invoice_requests()
{
    return $this->db->select('id, created_at, customer_name, customer_addr1, customer_addr2,
                              total_ht, remise, net_ht, tva_amount, timbre, total_ttc, status')
                    ->from('invoice_requests')
                    ->order_by('id','DESC')
                    ->get()->result_array();
}


}

