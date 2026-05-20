<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CI3 API Controller Prototype
 * Kani waa Koodhka Rasmiga ah ee aad siinayso Backend Developer-ka CI3.
 */
class Api extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        // 1. Marka hore, API-gu waa inuu JSON u sheegaa browserka inuu soo celinayo
        header('Content-Type: application/json');
        
        // 2. Hubinta Badbaadada (Authentication Check)
        $this->verify_token();
        
        // 3. Load the Model (Xogta Database-ka kasoo saaraya)
        $this->load->model('Api_model');
    }

    /**
     * Nidaamka Xaqiijinta (Master Key)
     */
    private function verify_token()
    {
        // ⚠️ Beddel furahan, kana dhig midka dhabta ah ee Iskuulku isticmaalayo
        // (Sida ku cad sawirka: 3e8ea952f2a06672)
        $valid_token = 'YOUR_SCHOOL_API_TOKEN';
        
        // Soo dhufo Headers-ka uu Supabase soo diro
        $headers = $this->input->request_headers();
        $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        // Haddii uusan fure jirin, ama uusan ahayn kii rasmiga ahaa, Diid (401 Unauthorized)
        if (strpos($auth_header, $valid_token) === false) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Unauthorized Access. Invalid API Token.'
            ]);
            exit; // 🚫 Nidaamka halkan ku jooji, qofkana xogta ha siin!
        }
    }

    /**
     * Endpoint: GET /api/v1/parents/allowed
     * Shaqada: Wuxuu soo qaadaa dhammaan diiwaanka waalidiinta nadiifka ah.
     */
    public function allowed_parents()
    {
        // 1. Wac Modelka si uu xogta u soo habeeyo
        $parents = $this->Api_model->get_allowed_parents();
        
        // 2. Ku dar "HTTP 200 OK" Status
        http_response_code(200);

        // 3. Soo daabac Xogta iyadoo JSON ah (Qaabkii Supabase doonaysay)
        echo json_encode([
            'status' => 'success',
            'count' => count($parents),
            'data' => $parents
        ]);
    }
}
