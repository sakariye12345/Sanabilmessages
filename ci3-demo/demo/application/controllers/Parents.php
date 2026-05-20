<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

class Parents extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        // Xaqiiji inuu qofku guda jiraa (Sida dashboard-ka caadiga ah)
        // Haddii aan la tijaabineynin auth-ga waa laga jari karaa.
        $this->load->model('Api_model');
    }

    public function index()
    {
        // Xogta Waalidiinta (Lama Nadiifinin - Data dhab aahaan u eg)
        $data['system_title'] = 'Sanabil Parents Tool';
        $data['parents_list'] = $this->Api_model->get_allowed_parents();
        
        $this->load->view("parents_dashboard", $data);
    }

    public function update()
    {
        $id = $this->input->post('id');
        $data = [
            'parent_name' => $this->input->post('parent_name'),
            'phone' => $this->input->post('phone'),
            'status' => $this->input->post('status')
        ];

        if ($this->Api_model->update_parent($id, $data)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
    }

    public function add()
    {
        $data = [
            'parent_name' => $this->input->post('parent_name'),
            'phone' => $this->input->post('phone'),
            'status' => $this->input->post('status')
        ];

        if ($this->Api_model->insert_parent($data)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
    }

    public function download_template()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Full Name');
        $sheet->setCellValue('B1', 'Phone Number');
        $sheet->setCellValue('C1', 'Status (active/inactive)');
        
        $sheet->setCellValue('A2', 'Axmed Jaamac Cali');
        $sheet->setCellValue('B2', '252634444444');
        $sheet->setCellValue('C2', 'active');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="parents_template.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    public function import()
    {
        if (isset($_FILES['file']['name'])) {
            $path = $_FILES['file']['tmp_name'];
            $reader = new XlsxReader();
            $spreadsheet = $reader->load($path);
            $sheetData = $spreadsheet->getActiveSheet()->toArray();
            
            $count = 0;
            // Shaxda Excel: Row 0 waa Header, Row 1 wuxuu ka bilaabmaa xogta
            for($i=1; $i<count($sheetData); $i++) {
                $row = $sheetData[$i];
                if(!empty($row[0]) && !empty($row[1])) {
                    $insert_data = [
                        'parent_name' => $row[0],
                        'phone' => $row[1],
                        'status' => isset($row[2]) ? strtolower($row[2]) : 'active'
                    ];
                    $this->Api_model->insert_parent($insert_data);
                    $count++;
                }
            }
            echo json_encode(['status' => 'success', 'count' => $count]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
        }
    }
}
