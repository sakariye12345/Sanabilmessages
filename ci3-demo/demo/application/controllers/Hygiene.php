<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Hygiene extends CI_Controller
{
   public function __construct()
{
    parent::__construct();
    $this->load->model('Hygiene_model', 'hm');
    $this->load->helper(['url','form']);
    $this->load->library(['session','form_validation']);

    // Block cache
    $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    $this->output->set_header('Cache-Control: post-check=0, pre-check=0', false);
    $this->output->set_header('Pragma: no-cache');

    // 🔐 Auth gate with AJAX-friendly response
    if (!$this->session->userdata('username')) {
        if ($this->input->is_ajax_request()) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['ok'=>false,'msg'=>'Login required']));
            exit;
        }
        redirect(base_url('home/login'));
    }
}

    // Step 1: pick class + date
    public function index()
    {
        $data['classes'] = $this->hm->get_classes();
        $data['today'] = date('Y-m-d');
        $this->load->view('hygiene/hygiene_index.php', $data);
    }
 //Select form method
public function prepare()
{
    $this->form_validation->set_rules('class_id', 'Class', 'required|integer');
    $this->form_validation->set_rules(
        'attn_date', 'Date',
        'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]'
    );

    if (!$this->form_validation->run()) {
        $this->session->set_tempdata('danger', validation_errors(), 3);
        return redirect('hygiene/select_form');
    }

    $class_id  = (int)$this->input->post('class_id', true);
    $date      = $this->input->post('attn_date', true);
    $user_id   = (int)$this->session->userdata('user_id');

    // If already SUBMITTED (attendance rows exist), block re-prepare
    if ($this->hm->has_class_date($class_id, $date)) {
        $this->session->set_tempdata('danger',
            'Attendance for this class and date has already been submitted.', 3);
        return redirect('hygiene/view_attendances');
    }

    // Ensure a PENDING batch exists (create if missing)
    $this->hm->ensure_pending_batch($class_id, $date, $user_id);

    $this->session->set_tempdata('success', 'Batch created as Pending.', 3);
    return redirect('hygiene/view_attendances');   // 👈 show it in the table
}


// Marka view_attendaces view buttonka ku jira lagu dhufto waxa la wacayaa controller kan.
public function prepare_direct($class_id, $attn_date)
{
    $class_id  = (int)$class_id;
    $date      = urldecode($attn_date);

    $students  = $this->hm->get_students_by_class($class_id);
    $existing  = $this->hm->get_attendance_map($class_id, $date);

    $class_row  = $this->db->select('name')->from('classes')->where('id', $class_id)->get()->row_array();
    $class_name = $class_row ? $class_row['name'] : ('Class '.$class_id);

    $data = [
        'class_id'   => $class_id,
        'class_name' => $class_name,
        'attn_date'  => $date,
        'students'   => $students,
        'existing'   => $existing
    ];

    // show your original prepare.php
    $this->load->view('hygiene/prepare', $data);
}



    // Step 3: save submission
public function store()
{
    $this->form_validation->set_rules('class_id','Class','required|integer');
    $this->form_validation->set_rules('attn_date','Date','required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');

    if (!$this->form_validation->run()) {
        $this->session->set_flashdata('danger', validation_errors());
        return redirect('hygiene');
    }

    $class_id  = (int)$this->input->post('class_id', true);
    $date      = $this->input->post('attn_date', true);
    $status    = (array)$this->input->post('status', true);
    $is_edit   = (bool)$this->input->post('edit_mode'); // 👈

    // Prevent double submit ONLY for create (not for edit)
    if (!$is_edit && $this->hm->has_class_date($class_id, $date)) {
        $this->session->set_tempdata('danger','This class has already been submitted for that date.',3);
        return redirect('hygiene');
    }

      $rows = [];
    foreach ($status as $sid => $val) {
        $val = strtoupper(trim((string)$val));
        if (in_array($val, ['NR','NL','HR','BH'], true)) {
            $rows[] = ['student_id' => (int)$sid, 'status' => $val];
        }
    }

    $created_by = (int)$this->session->userdata('user_id');
    $ok = $this->hm->upsert_attendance($class_id, $date, $rows, $created_by);

    $this->session->set_tempdata($ok ? 'success' : 'danger',
        $ok ? 'Hygiene attendance saved successfully.'
            : 'Failed to save attendance. Please try again.', 3);
    if ($ok) {
    // U badal Status ka submitted 
    $this->db->where(['class_id'=>$class_id,'attn_date'=>$date])
             ->update('hygiene_batches', [
                'status'     => 'SUBMITTED',
                'updated_at' => date('Y-m-d H:i:s')
             ]);
}

if ($ok) {
    // mark batch as SUBMITTED
    $this->hm->mark_batch_submitted($class_id, $date, $created_by);
}


// Haddii kaydintu OK tahay, hubi status-yada oo samee/bilow follow-up
if ($ok) {
    $user_id = (int)$this->session->userdata('user_id');
    foreach ($rows as $r) {
        $sid = (int)$r['student_id'];
        $st  = strtoupper($r['status']);
        if ($st === 'HR' || $st === 'NL') {
            $this->hm->bump_followup($sid, $class_id, $st, $date, $user_id);
        } elseif ($st === 'BH') {
            // kala jabin BH
            $this->hm->bump_followup($sid, $class_id, 'HR', $date, $user_id);
            $this->hm->bump_followup($sid, $class_id, 'NL', $date, $user_id);
        }
        // NR -> wax follow-up ah ha sameynin
    }
}

    return redirect('hygiene/view_attendances');
}

public function mark_batch_submitted(int $class_id, string $date, ?int $user_id = null): void
{
    $this->db->where(['class_id' => $class_id, 'attn_date' => $date])
             ->update('hygiene_batches', [
                 'status'       => 'SUBMITTED',
                 'updated_at'   => date('Y-m-d H:i:s'),
                 'submitted_by' => $user_id
             ]);
}

//start_batch – waxaa lagu wacaa marka user-ku “Yes” dhaho confirm-ka ka hor prepare
public function start_batch()
{
    $this->form_validation->set_rules('class_id', 'Class', 'required|integer');
    $this->form_validation->set_rules('attn_date', 'Date', 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');
    if (!$this->form_validation->run()) {
        $this->session->set_tempdata('danger', validation_errors(), 3);
        return redirect('hygiene/select_form');
    }

    $class_id  = (int)$this->input->post('class_id', true);
    $attn_date = $this->input->post('attn_date', true);
    $user_id   = (int)$this->session->userdata('user_id');

    // create-or-ignore pending batch
    $exists = $this->db->get_where('hygiene_batches', ['class_id'=>$class_id,'attn_date'=>$attn_date])->row_array();
    if (!$exists) {
        $this->db->insert('hygiene_batches', [
            'class_id'  => $class_id,
            'attn_date' => $attn_date,
            'status'    => 'PENDING',
            'created_by'=> $user_id
        ]);
    }

    $this->session->set_tempdata('success','Batch created (Pending).',3);
    return redirect('hygiene/view_attendances');
}


public function view_attendances()
{
    // School name (you can change or fetch dynamically)
    $data['school_name'] = 'Schools Demo';

    // Fetch real records grouped by class/date
    $data['attendances'] = $this->hm->get_attendance_batches();

    // Load your HTML + backend view
    $this->load->view('hygiene/view_attendances', $data);
}

public function view($class_id, $attn_date)
{
    $class_id  = (int)$class_id;
    $attn_date = urldecode($attn_date);

    $class = $this->db->get_where('classes', ['id'=>$class_id])->row_array();
    if (!$class) {
        $this->session->set_tempdata('danger', 'Class not found.', 3);
        return redirect('hygiene/view_attendances');
    }

    // SAME data sida prepare/view_batch
    $students = $this->hm->get_students_by_class($class_id);
    $existing = $this->hm->get_attendance_map($class_id, $attn_date);

    $data = [
        'class_id'   => $class_id,                       // ← **MUHIIM**
        'class_name' => $class['name'] ?? ('Class '.$class_id),
        'attn_date'  => $attn_date,
        'students'   => $students,
        'existing'   => $existing,
        'mode'       => 'edit',
    ];

    // Isticmaal view-ga la midka ah prepare-ka (edit detail)
    $this->load->view('hygiene/view_attendance_detail', $data);
}





// View Deital Attendance(Waa function ka soo saaraya attendance hore loo diyaariyay si edit loogu samayn karo)
public function view_batch($id)
{
    $id = (int)$id;

    // 1️⃣ Ka soo hel class_id iyo date record-kii id-gaas
    $one = $this->db->select('class_id, attn_date')
                    ->from('hygiene_attendance')
                    ->where('id', $id)
                    ->get()
                    ->row_array();

    if (!$one) {
        $this->session->set_tempdata('danger', 'Attendance record not found.', 3);
        return redirect('hygiene/view_attendances');
    }

    $class_id = (int)$one['class_id'];
    $date     = $one['attn_date'];

    // 2️⃣ Soo saar ardayda & statuses hore
    $students = $this->hm->get_students_by_class($class_id);
    $existing = $this->hm->get_attendance_map($class_id, $date);

    // 3️⃣ Soo hel magaca fasalka
    $class_row  = $this->db->select('name')
                           ->from('classes')
                           ->where('id', $class_id)
                           ->get()
                           ->row_array();
    $class_name = $class_row ? $class_row['name'] : ('Class '.$class_id);

    // 4️⃣ Gudbi xogta view-ga
    $data = [
        'class_id'   => $class_id,
        'class_name' => $class_name,
        'attn_date'  => $date,
        'students'   => $students,
        'existing'   => $existing,
        'mode'       => 'edit'
    ];

    $this->load->view('hygiene/view_attendance_detail', $data);
}




// 2A) Edit by attendance row id (preferred link from the table)
public function edit_by_id($id)
{
    $id = (int)$id;
    $row = $this->db->select('class_id, attn_date')
                    ->from('hygiene_attendance')
                    ->where('id', $id)
                    ->get()->row_array();

    if (!$row) {
        $this->session->set_tempdata('danger', 'Attendance record not found.', 3);
        return redirect('hygiene/view_attendances');
    }

    return $this->_load_edit_view((int)$row['class_id'], $row['attn_date']);
}

// 2B) Edit by class + date (fallback route)
public function edit_class_attendance($class_id, $attn_date)
{
    $class_id  = (int)$class_id;
    $attn_date = urldecode($attn_date);
    return $this->_load_edit_view($class_id, $attn_date);
}

// 2C) Private helper that actually prepares the data and loads the view
private function _load_edit_view($class_id, $date)
{
    // students + existing marks
    $students  = $this->hm->get_students_by_class($class_id);
    $existing  = $this->hm->get_attendance_map($class_id, $date);

    // class name
    $c = $this->db->select('name')->from('classes')->where('id', $class_id)->get()->row_array();
    $class_name = $c ? $c['name'] : ('Class '.$class_id);

    $data = [
        'class_id'   => $class_id,
        'class_name' => $class_name,
        'attn_date'  => $date,
        'students'   => $students,
        'existing'   => $existing
    ];

    // 👉 This loads YOUR file:
    // application/views/hygiene/edit_class_attendance.php
    $this->load->view('hygiene/edit_class_attendance', $data);
}
    // Report: filter form
    public function report()
    {
        $data['classes']  = $this->hm->get_classes();
    $data['students'] = $this->hm->get_students_dropdown();   // ← add
    $data['from_date']= date('Y-m-01');
    $data['to_date']  = date('Y-m-d');
    $data['rows']     = [];
    $this->load->view('hygiene/hygiene_report', $data);
    }

    // Report results (POST)
    public function report_results()
    {
        $this->form_validation->set_rules('class_id', 'Class', 'required|integer');
        $this->form_validation->set_rules('from_date', 'From Date', 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');
        $this->form_validation->set_rules('to_date', 'To Date', 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');

        if (!$this->form_validation->run()) {
            $this->session->set_flashdata('error', validation_errors());
            return redirect('hygiene/report_hygiene');
        }

        $class_id  = (int)$this->input->post('class_id', true);
        $from_date = $this->input->post('from_date', true);
        $to_date   = $this->input->post('to_date', true);
        $export    = $this->input->post('export', true);

        $rows = $this->hm->report_totals($class_id, $from_date, $to_date);

        if ($export === 'csv') {
            $this->_export_csv($rows, "hygiene_report_{$class_id}_{$from_date}_{$to_date}.csv");
            return; // exits after download
        }

        $data = [
            'classes'   => $this->hm->get_classes(),
            'from_date' => $from_date,
            'to_date'   => $to_date,
            'rows'      => $rows,
            'class_id'  => $class_id
        ];
        $this->load->view('hygiene/report_hygiene', $data);
    }

    private function _export_csv(array $rows, $filename)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename='.$filename);
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Attendance #', 'Student', 'Excellent (EH)', 'Acceptable (AC)', 'Unacceptable (UN)', 'Total Marked Days']);
        foreach ($rows as $r) {
            $total = (int)$r['total_eh'] + (int)$r['total_ac'] + (int)$r['total_un'];
            fputcsv($out, [
                $r['attendance_no'],
                $r['full_name'],
                $r['total_eh'],
                $r['total_ac'],
                $r['total_un'],
                $total
            ]);
        }
        fclose($out);
        exit;
    }
public function report_page()
{
    $data['classes']  = $this->hm->get_classes();
    $data['students'] = $this->hm->get_students_dropdown();   // ← add
    $data['today']    = date('Y-m-d');
    $this->load->view('hygiene/hygiene_report', $data);
}

// GET /hygiene/report-hygiene
public function report_hygiene()
{
    $data['classes']   = $this->hm->get_classes(); // not used in the single-student modal, but ok
    $data['students']  = $this->hm->get_students_dropdown();   // 👈 IMPORTANT
    $data['today']     = date('Y-m-d');
    $data['class_id']  = 0;
    $data['from_date'] = date('Y-m-d');
    $data['to_date']   = date('Y-m-d');
    $data['rows']      = [];
    $this->load->view('hygiene/hygiene_report', $data);
}

public function student_go()
{
    $this->form_validation->set_rules('student_id','Student','required|integer');
    $this->form_validation->set_rules('from_date','From','required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');
    $this->form_validation->set_rules('to_date','To','required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');

    if (!$this->form_validation->run()) {
        $this->session->set_tempdata('danger', validation_errors(), 3);
        return redirect('hygiene/report-hygiene');
    }

    $id   = (int)$this->input->post('student_id', true);
    $from = $this->input->post('from_date', true);
    $to   = $this->input->post('to_date', true);

    // → waxay keeni doontaa isla view-ga oo xogta diyaarinaya
    redirect("hygiene/student/{$id}?from={$from}&to={$to}");
}



public function report_hygiene_results()
{
    if ($this->input->method(TRUE) !== 'POST') {
        return redirect('hygiene/report-hygiene');
    }

    $this->form_validation->set_rules('class_id',  'Class', 'required|integer');
    $this->form_validation->set_rules('from_date', 'From',  'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');
    $this->form_validation->set_rules('to_date',   'To',    'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');

    if (!$this->form_validation->run()) {
        $this->session->set_tempdata('danger', validation_errors(), 3);
        return redirect('hygiene/report-hygiene');
    }

    $class_id  = (int)$this->input->post('class_id', true);
    $from_date = $this->input->post('from_date', true);
    $to_date   = $this->input->post('to_date', true);

    // ⬇️ Pull totals from hygiene_compliance instead of attendance
    $rows = $this->hm->compliance_report_totals($class_id, $from_date, $to_date);

    $data = [
        'classes'   => $this->hm->get_classes(),
        'students'  => $this->hm->get_students_dropdown(),
        'class_id'  => $class_id,
        'from_date' => $from_date,
        'to_date'   => $to_date,
        'rows'      => $rows,
        'today'     => date('Y-m-d'),
    ];
    $this->load->view('hygiene/report_hygiene', $data);
}


//  Per Student Report 
// Show empty report page (defaults to last 30 days)
// GET /hygiene/student/{id}?from=YYYY-MM-DD&to=YYYY-MM-DD
public function student_report($student_id)
{
    $student = $this->hm->get_student_basic((int)$student_id);
    if (!$student) {
        $this->session->set_tempdata('danger','Student not found.',3);
        return redirect('hygiene/report-hygiene');
    }

    $from = $this->input->get('from', true) ?: date('Y-m-d', strtotime('-30 days'));
    $to   = $this->input->get('to', true)   ?: date('Y-m-d');

    // Hadduu jiro range, soo saar natiijooyinka
    $summary = $this->hm->hygiene_student_summary($student_id, $from, $to);
    $rows    = $this->hm->hygiene_student_rows($student_id, $from, $to);

    $data = [
        'student'   => $student,
        'from_date' => $from,
        'to_date'   => $to,
        'summary'   => $summary ?: ['total_nr'=>0,'total_nl'=>0,'total_hr'=>0,'total_bh'=>0,'total_marked'=>0],
        'rows'      => $rows
    ];
    $this->load->view('hygiene/student_hygiene_report', $data);
}



// Handle filter submit
public function student_report_results($student_id)
{
    $this->form_validation->set_rules('from_date','From','required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');
    $this->form_validation->set_rules('to_date','To','required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');
    if (!$this->form_validation->run()) {
        $this->session->set_tempdata('danger', validation_errors(), 3);
        return redirect('hygiene/student/'.$student_id);
    }

    $student   = $this->hm->get_student_basic((int)$student_id);
    if (!$student) {
        $this->session->set_tempdata('danger','Student not found.',3);
        return redirect('hygiene');
    }

    $from_date = $this->input->post('from_date', true);
    $to_date   = $this->input->post('to_date', true);

    $summary = $this->hm->hygiene_student_summary($student_id, $from_date, $to_date);
    $rows    = $this->hm->hygiene_student_rows($student_id, $from_date, $to_date);

    $data = [
        'student'   => $student,
        'from_date' => $from_date,
        'to_date'   => $to_date,
        'summary'   => $summary ?: ['total_nr'=>0,'total_nl'=>0,'total_hr'=>0,'total_bh'=>0,'total_marked'=>0],
        'rows'      => $rows
    ];
    $this->load->view('hygiene/student_hygiene_report', $data);
}

// DELETE all attendance for a class on a date
public function delete_batch()
{
    if ($this->input->method(TRUE) !== 'POST') show_error('Invalid method', 405);

    $class_id  = (int)$this->input->post('class_id', true);
    $attn_date = $this->input->post('attn_date', true);

    if (!$class_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $attn_date)) {
        $this->session->set_tempdata('danger','Invalid payload.',3);
        return redirect('hygiene/view_attendances');
    }

    $ok = $this->db->where(['class_id'=>$class_id, 'attn_date'=>$attn_date])
                   ->delete('hygiene_attendance');

    // also clean marker if you like (optional)
    $this->db->where(['class_id'=>$class_id, 'attn_date'=>$attn_date])
             ->delete('hygiene_batches');

    $this->session->set_tempdata($ok ? 'success' : 'danger',
        $ok ? 'Deleted attendance for that day.' : 'Delete failed.', 3);

    return redirect('hygiene/view_attendances');
}

// DELETE one student’s row for a given class+date
public function delete_row_by_keys()
{
    if ($this->input->method(TRUE) !== 'POST') show_error('Invalid method', 405);

    $class_id   = (int)$this->input->post('class_id', true);
    $student_id = (int)$this->input->post('student_id', true);
    $attn_date  = $this->input->post('attn_date', true);
    $return_to  = $this->input->post('return_to', true) ?: 'hygiene/view_attendances';

    if (!$class_id || !$student_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $attn_date)) {
        $this->session->set_tempdata('danger','Invalid payload.',3);
        return redirect($return_to);
    }

    $ok = $this->db->where([
                'class_id'   => $class_id,
                'student_id' => $student_id,
                'attn_date'  => $attn_date
          ])->delete('hygiene_attendance');

    $this->session->set_tempdata($ok ? 'success' : 'danger',
        $ok ? 'Deleted this entry.' : 'Delete failed.', 3);

    return redirect($return_to);
}

// followups Controller 
public function followups()
{
    $data['open'] = $this->hm->get_open_followups();
    $this->load->view('hygiene/followups', $data);
}

public function resolve_followup()
{
    if ($this->input->method(TRUE) !== 'POST') {
        return redirect('hygiene/followups');
    }
    $this->load->library('form_validation');
    $this->form_validation->set_rules('id','Issue','required|integer');
    $this->form_validation->set_rules('resolved_at','Date','required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');
    if (!$this->form_validation->run()) {
        $this->session->set_tempdata('danger', validation_errors(), 3);
        return redirect('hygiene/followups');
    }

    $id   = (int)$this->input->post('id', true);
    $date = $this->input->post('resolved_at', true);
    $note = $this->input->post('note', true);
    $uid  = (int)$this->session->userdata('user_id');

    $ok = $this->hm->resolve_followup($id, $date, $note, $uid);
    $this->session->set_tempdata($ok ? 'success' : 'danger',
        $ok ? 'Compliance confirmed.' : 'Unable to confirm. Try again.', 3);
    return redirect('hygiene/followups');
}

public function followups_resolve()
{
    if ($this->input->method(TRUE) !== 'POST') {
        return redirect('hygiene/followups');
    }

    $id          = (int)$this->input->post('id', true);
    $resolved_at = $this->input->post('resolved_at', true) ?: date('Y-m-d');
    $user_id     = (int)$this->session->userdata('user_id');

    if ($id <= 0) {
        if ($this->input->is_ajax_request()) {
            return $this->output->set_content_type('application/json')
                                ->set_output(json_encode(['ok' => false, 'msg' => 'Invalid id']));
        }
        $this->session->set_tempdata('warning', 'Invalid item.', 3);
        return redirect('hygiene/followups');
    }

    // Get details so we know who/what to log in compliance
    $f = $this->hm->get_followup_by_id($id);
    if (!$f) {
        $msg = 'Follow-up not found.';
        if ($this->input->is_ajax_request()) {
            return $this->output->set_content_type('application/json')
                                ->set_output(json_encode(['ok' => false, 'msg' => $msg]));
        }
        $this->session->set_tempdata('warning', $msg, 3);
        return redirect('hygiene/followups');
    }

    // Map issue_type → one or two compliance rows
    $types = [];
    $t = strtoupper(trim((string)$f['issue_type']));
    if ($t === 'BH') {         // Both → log HR & NL
        $types = ['HR', 'NL'];
    } elseif (in_array($t, ['HR','NL'], true)) {
        $types = [$t];
    }

    // Do both operations atomically
    $this->db->trans_start();
    foreach ($types as $one) {
        $this->hm->insert_compliance((int)$f['student_id'], (int)$f['class_id'], $one, $resolved_at, $user_id);
    }
    $this->hm->followup_mark_resolved($id, $resolved_at, $user_id);
    $this->db->trans_complete();

    $ok = $this->db->trans_status();

    if ($this->input->is_ajax_request()) {
        return $this->output->set_content_type('application/json')
                            ->set_output(json_encode(['ok' => (bool)$ok]));
    }

    $this->session->set_tempdata($ok ? 'success' : 'danger',
        $ok ? 'Confirmed.' : 'Failed to confirm.', 3);
    return redirect('hygiene/followups');
}


public function followups_bulk_resolve()
{
    if ($this->input->method(TRUE) !== 'POST') {
        return redirect('hygiene/followups');
    }

    $ids_in     = (array)$this->input->post('ids');
    $ids        = array_values(array_filter(array_map('intval', $ids_in), fn($x) => $x > 0));
    $resolved_at= $this->input->post('resolved_at', true) ?: date('Y-m-d');
    $user_id    = (int)$this->session->userdata('user_id');

    if (empty($ids)) {
        $msg = 'No rows selected.';
        if ($this->input->is_ajax_request()) {
            return $this->output->set_content_type('application/json')
                                ->set_output(json_encode(['ok' => false, 'count' => 0, 'msg' => $msg]));
        }
        $this->session->set_tempdata('warning', $msg, 3);
        return redirect('hygiene/followups');
    }

    $rows = $this->hm->get_followups_by_ids($ids);   // expect list of follow-ups
    if (!$rows) $rows = [];

    $done_ids = [];
    $this->db->trans_start();

    foreach ($rows as $f) {
        $fid = (int)$f['id'];
        if ($fid <= 0) { continue; }

        $t = strtoupper(trim((string)$f['issue_type']));
        $types = ($t === 'BH') ? ['HR','NL'] : (in_array($t, ['HR','NL'], true) ? [$t] : []);

        foreach ($types as $one) {
            $this->hm->insert_compliance((int)$f['student_id'], (int)$f['class_id'], $one, $resolved_at, $user_id);
        }

        $this->hm->followup_mark_resolved($fid, $resolved_at, $user_id);
        $done_ids[] = $fid;
    }

    $this->db->trans_complete();
    $ok    = $this->db->trans_status();
    $count = $ok ? count($done_ids) : 0;

    if ($this->input->is_ajax_request()) {
        return $this->output->set_content_type('application/json')
                            ->set_output(json_encode(['ok' => (bool)$ok, 'count' => (int)$count]));
    }

    $this->session->set_tempdata($ok ? 'success' : 'warning',
        $ok ? "Bulk confirm done for {$count} item(s)." : 'Nothing updated.', 3);
    return redirect('hygiene/followups');
}



public function followups_resolve_by_student_type()
{
    if ($this->input->method(TRUE) !== 'POST') {
        return $this->output
            ->set_status_header(405)
            ->set_content_type('application/json')
            ->set_output(json_encode(['ok'=>false,'msg'=>'Method not allowed']));
    }

    $sid  = (int)$this->input->post('student_id', true);
    $type = strtoupper(trim((string)$this->input->post('type', true))); // HR / NL

    if ($sid <= 0 || !in_array($type, ['HR','NL'], true)) {
        return $this->output
            ->set_status_header(400)
            ->set_content_type('application/json')
            ->set_output(json_encode(['ok'=>false,'msg'=>'Bad params']));
    }

    $ok = $this->hm->resolve_latest_open_by_student_type(
        $sid, $type, date('Y-m-d'), (int)$this->session->userdata('user_id')
    );

    return $this->output
        ->set_status_header(200)
        ->set_content_type('application/json')
        ->set_output(json_encode(['ok'=>(bool)$ok]));
}




}
