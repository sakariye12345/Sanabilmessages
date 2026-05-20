<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Request_items_model extends CI_Model
{
    public function by_request($request_id)
    {
        return $this->db->get_where('request_items', ['request_id' => $request_id])->result_array();
    }
}
