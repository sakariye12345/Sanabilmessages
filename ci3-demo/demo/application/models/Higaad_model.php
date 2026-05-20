<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Higaad_model extends CI_Model
{
    public function get_active_academic_year()
    {
        return $this->db->where('is_active', 1)
                        ->order_by('id', 'DESC')
                        ->get('academic_years')
                        ->row();
    }

    public function get_open_exam_window($academic_year_id)
    {
        return $this->db->where('academic_year_id', $academic_year_id)
                        ->where('status', 'open')
                        ->order_by('exam_year', 'DESC')
                        ->order_by('exam_month', 'DESC')
                        ->get('higaad_exam_windows')
                        ->row();
    }

    public function get_teacher_classes($teacher_id = null)
{
    // Waqtigan ha fiirin macalin gaar ah, soo qaad dhamaan fasallada
    return $this->db->order_by('name', 'ASC')
                    ->get('classes')
                    ->result();
}


    public function get_students_by_class($class_id)
    {
        return $this->db->where('class_id', $class_id)
                        ->order_by('customer_name', 'ASC')
                        ->get('tbl_customer')
                        ->result();
    }


    public function get_all_academic_years()
    {
        // haddii aadan haysan start_date, hoos ku beddel id
        return $this->db->order_by('start_date', 'DESC')
                        ->get('academic_years')
                        ->result();
    }

    public function get_current_chapter_for_student($student_id)
    {
        $row = $this->db->select('current_chapter_id')
                        ->where('id', $student_id)
                        ->get('tbl_customer')
                        ->row();
        return $row ? $row->current_chapter_id : null;
    }

    public function get_available_chapters($student_id, $academic_year_id)
    {
        // chapters all 1..8
        $all = $this->db->order_by('chapter_number', 'ASC')
                        ->get('higaad_chapters')
                        ->result();

        // chapters already done
        $done = $this->db->select('chapter_id')
                         ->where('student_id', $student_id)
                         ->where('academic_year_id', $academic_year_id)
                         ->get('higaad_results')
                         ->result();

        $done_ids = array_map(function($r){ return $r->chapter_id; }, $done);
        $available = [];
        foreach ($all as $chapter) {
            if (!in_array($chapter->id, $done_ids)) {
                $available[] = $chapter;
            }
        }
        return $available;
    }

    public function is_class_submitted($class_id, $academic_year_id, $month, $year)
    {
        return $this->db->where([
                            'class_id' => $class_id,
                            'academic_year_id' => $academic_year_id,
                            'exam_month' => $month,
                            'exam_year' => $year
                        ])
                        ->count_all_results('higaad_class_submissions') > 0;
    }

public function save_result($data)
{
    // 1) iska hubi in uusan chapter-kan hore uga jirin ardaygan sanadkan
    $exists = $this->db->where([
                        'student_id'       => $data['student_id'],
                        'academic_year_id' => $data['academic_year_id'],
                        'chapter_id'       => $data['chapter_id']
                    ])
                    ->get('higaad_results')
                    ->row();

    if ($exists) {
        return ['success' => false, 'message' => 'Student already has result for this chapter.'];
    }

    // 2) INSERT – dam db_debug si aanu 500 u noqon
    $old_db_debug           = $this->db->db_debug;
    $this->db->db_debug     = FALSE;

    $this->db->insert('higaad_results', $data);
    $db_error = $this->db->error();

    // restore setting
    $this->db->db_debug = $old_db_debug;

    if (!empty($db_error['code'])) {
        log_message('error', 'Higaad save_result DB error: ' . print_r($db_error, true));

        return [
            'success' => false,
            'message' => 'Database error: ' . $db_error['message']
        ];
    }

    // 3) update current_chapter_id ee tbl_customer (haddii column-kan jirto)
    $old_db_debug       = $this->db->db_debug;
    $this->db->db_debug = FALSE;

    $this->db->where('customer_id', $data['student_id'])
             ->update('tbl_customer', ['current_chapter_id' => $data['chapter_id']]);

    $db_error_update = $this->db->error();
    $this->db->db_debug = $old_db_debug;

    if (!empty($db_error_update['code'])) {
        log_message('error', 'Higaad update current_chapter_id DB error: ' . print_r($db_error_update, true));
        // laakiin natiijada wuu kaydsan yahay, sidaa darteed ma joojinayno user-ka
    }

    return ['success' => true];
}


 public function submit_class($class_id, $academic_year_id, $month, $year, $teacher_id)
{
    // 1) Haddii hore loo submit gareeyay, jooji
    if ($this->is_class_submitted($class_id, $academic_year_id, $month, $year)) {
        return ['success' => false, 'message' => 'Class already submitted.'];
    }

    // hubi in uu yahay integer, NULL ha ahayn
    $teacher_id = (int) $teacher_id;

    $data = [
        'class_id'         => $class_id,
        'academic_year_id' => $academic_year_id,
        'exam_month'       => $month,
        'exam_year'        => $year,
        'teacher_id'       => $teacher_id,     // users.id ayaad halkan ku kaydinaysaa
        // 'status' iyo 'submitted_at' DB ayaa default uga dhigaya, sida table-kaaga
    ];

    // 2) damo db_debug inta aan insert-gareyneyno, si aanu 500 page u dhisin
    $old_debug           = $this->db->db_debug;
    $this->db->db_debug  = FALSE;

    $this->db->insert('higaad_class_submissions', $data);
    $db_error = $this->db->error();

    // soo celi setting-gii hore
    $this->db->db_debug = $old_debug;

    if (!empty($db_error['code'])) {
        // log ku qor si aad log-ka uga arki karto faahfaahinta
        log_message('error', 'Higaad submit_class DB error: ' . print_r($db_error, true));

        return [
            'success' => false,
            'message' => 'Database error: ' . $db_error['message']
        ];
    }

    return ['success' => true];
}




public function get_submissions($academic_year_id = null, $month = null, $year = null)
{
    $this->db->select('
        s.*,
        c.name AS class_name,
        u.username AS submitted_username,
        CONCAT(u.first_name, " ", u.last_name) AS submitted_fullname
    ');
    $this->db->from('higaad_class_submissions AS s');
    $this->db->join('classes AS c', 'c.id = s.class_id');
    $this->db->join('users   AS u', 'u.id = s.teacher_id', 'left');

    if (!empty($academic_year_id)) {
        $this->db->where('s.academic_year_id', $academic_year_id);
    }
    if (!empty($month)) {
        $this->db->where('s.exam_month', $month);
    }
    if (!empty($year)) {
        $this->db->where('s.exam_year', $year);
    }

    $this->db->order_by('s.submitted_at', 'DESC');

    return $this->db->get()->result();
}



    public function get_results_for_class_window($class_id, $academic_year_id, $month, $year)
{
    $rows = $this->db->where('class_id', $class_id)
                     ->where('academic_year_id', $academic_year_id)
                     ->where('exam_month', $month)
                     ->where('exam_year', $year)
                     ->get('higaad_results')
                     ->result();

    // index by student_id → hal row arday kasta
    $by_student = [];
    foreach ($rows as $r) {
        $by_student[$r->student_id] = $r;
    }

    return $by_student;
}
  //soo saar natiijooyinka fasalka + exam-ka
public function get_results_map_for_class($class_id, $academic_year_id, $month, $year)
{
    $rows = $this->db->where('class_id', $class_id)
                     ->where('academic_year_id', $academic_year_id)
                     ->where('exam_month', $month)
                     ->where('exam_year', $year)
                     ->get('higaad_results')
                     ->result();

    // index by student_id si fudud loogu helo view-ga
    $map = [];
    foreach ($rows as $r) {
        $map[$r->student_id] = $r;   // student_id = tbl_customer.customer_id
    }

    return $map;
}

//Submitted Models

// Get list of submitted Higaad classes for a given year + month + year
public function get_higaad_submissions($academic_year_id, $month, $year)
{
    return $this->db
        ->select('
            s.*,
            c.name AS class_name,
            CONCAT(u.first_name, " ", u.last_name) AS submitted_by
        ')
        ->from('higaad_class_submissions AS s')
        ->join('classes AS c', 'c.id = s.class_id')
        ->join('users AS u', 'u.id = s.teacher_id', 'left')   // ← teachers → users
        ->where('s.academic_year_id', (int)$academic_year_id)
        ->where('s.exam_month', (int)$month)
        ->where('s.exam_year', (int)$year)
        ->order_by('c.name', 'ASC')
        ->get()
        ->result();
}

// Get all Higaad results (xifdi, imlaa, khad) for one submitted class
public function get_class_results_with_students($class_id, $academic_year_id, $month, $year)
{
    return $this->db->select('r.*, s.student_code, s.customer_name, s.current_chapter_id')
                    ->from('higaad_results AS r')
                    ->join('tbl_customer AS s', 's.customer_id = r.student_id')
                    ->where('r.class_id', (int)$class_id)
                    ->where('r.academic_year_id', (int)$academic_year_id)
                    ->where('r.exam_month', (int)$month)
                    ->where('r.exam_year', (int)$year)
                    ->order_by('s.customer_name', 'ASC')
                    ->get()
                    ->result();
}

// Get single submission row (with class & teacher names)
public function get_submission_by_id($id)
{
    return $this->db
        ->select('
            s.*,
            c.name AS class_name,
            CONCAT(u.first_name, " ", u.last_name) AS submitted_by,
            ay.name AS academic_year_name
        ')
        ->from('higaad_class_submissions AS s')
        ->join('classes AS c', 'c.id = s.class_id')
        ->join('users AS u', 'u.id = s.teacher_id', 'left')
        ->join('academic_years AS ay', 'ay.id = s.academic_year_id')
        ->where('s.id', (int) $id)
        ->get()
        ->row();
}



}
