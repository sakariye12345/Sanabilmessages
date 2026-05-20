<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CI3 API Model
 * Kani waa Koodhka xogta Nadiifinaya ee aad siinayso Developer-ka CI3.
 */
class Api_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        // Hubi in Database-ka lala xidhiidhay
        $this->load->database();
    }

    public function get_allowed_parents()
    {
        // ----------------------------------------------------
        // Kani waa SQL-ka rasmiga ah (Query Builder):
        // ----------------------------------------------------
        
        $this->db->select('id, parent_name, phone, status'); 
        $this->db->from('tbl_allowed_parents'); 
        $query = $this->db->get();
        // Haddii uu jiro Error xagga db ah, ku celi Array maran intii uu Crash yeelan lahaa
        if(!$query) return []; 
        
        $records = $query->result_array();
        $cleaned_data = [];
        
        foreach ($records as $row) {
            // Nadiifinta (Sanitization): Qadaadka waxkasta oo aan lambar ahayn MASAX. 
            // (+252-63-444 -> 25263444)
            $clean_phone = preg_replace('/[^0-9]/', '', $row['phone']);
            
            // Xaqiiji in lambarka keliya ee gudba uu yahay mid 9 nambar ka badan (Valid Mobile)
            if (strlen($clean_phone) > 8) {
                $cleaned_data[] = [
                    'school_id' => defined('SANABIL_SCHOOL_ID') ? SANABIL_SCHOOL_ID : 1, // Kani waa ID-ga iskuulka.
                    'parent_id' => $row['id'],
                    'parent_name' => $row['parent_name'],
                    'phone_number' => $clean_phone,
                    // Haddii waalidku inaktib noqdo False ka dhig, si Supabase App-ka uga saarto
                    'is_active' => ($row['status'] == 'active') ? true : false 
                ];
            }
        }
        
        return $cleaned_data;
    }

    public function update_parent($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('tbl_allowed_parents', $data);
    }

    public function insert_parent($data)
    {
        return $this->db->insert('tbl_allowed_parents', $data);
    }
}
