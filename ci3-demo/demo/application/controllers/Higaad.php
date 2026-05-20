<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Higaad extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Higaad_model');
        $this->load->database();
        // TODO: ku dar auth check (admin/teacher) halka methods ku habboon
    }

    /* =========================================================
     *  HELPER – Teacher auth
     * ========================================================= */
private function get_logged_in_teacher_id()
{
    // isku day dhowr key oo caadi ah oo system-yada badankoodu isticmaalaan
    $id = (int) $this->session->userdata('user_id');

    if (!$id) {
        $id = (int) $this->session->userdata('id');
    }

    if (!$id) {
        $id = (int) $this->session->userdata('teacher_id');
    }

    // ugu yaraan 0 ha noqdo – ma noqonaayo NULL
    return $id;
}

    /* =========================================================
     *  TEACHER PART – Higaad Results
     *  URL: teacher/higaad, teacher/higaad/prepare, iwm.
     * ========================================================= */

    // Main teacher page (hore: HigaadResults::index)
    public function index()
    {
        $teacher_id = $this->get_logged_in_teacher_id();

        $active_year = $this->Higaad_model->get_active_academic_year();
        $open_window = $active_year ? $this->Higaad_model->get_open_exam_window($active_year->id) : null;

        $data['active_year']      = $active_year;
        $data['open_window']      = $open_window;
        $data['teacher_classes']  = $this->Higaad_model->get_teacher_classes($teacher_id);

        $this->load->view('teacher/higaad_results', $data);
    }

    // AJAX – prepare table for selected class (hore: HigaadResults::prepare)
public function prepare()
{
    $teacher_id = $this->get_logged_in_teacher_id();
    $class_id   = (int) $this->input->post('class_id');

    $active_year = $this->Higaad_model->get_active_academic_year();
    $open_window = $active_year ? $this->Higaad_model->get_open_exam_window($active_year->id) : null;

    if (!$active_year || !$open_window) {
        echo json_encode(['success' => false, 'message' => 'No open exam window.']);
        return;
    }

    if ($this->Higaad_model->is_class_submitted(
        $class_id,
        $active_year->id,
        $open_window->exam_month,
        $open_window->exam_year
    )) {
        echo json_encode(['success' => false, 'message' => 'This class is already submitted.']);
        return;
    }

    // Ardayda fasalka
    $students = $this->Higaad_model->get_students_by_class($class_id);

    // Natiijooyinkii hore ee fasalkaas + examkan
    $results_map = $this->Higaad_model->get_results_map_for_class(
        $class_id,
        $active_year->id,
        $open_window->exam_month,
        $open_window->exam_year
    );

    // Return table HTML as partial
    $html = $this->load->view('teacher/partials/higaad_results_table', [
        'students'     => $students,
        'class_id'     => $class_id,
        'active_year'  => $active_year,
        'open_window'  => $open_window,
        'results_map'  => $results_map, // ← cusub
    ], true);

    echo json_encode(['success' => true, 'html' => $html]);
}


    // AJAX – get available chapters for a student (hore: HigaadResults::available_chapters)
    public function available_chapters()
    {
        $student_id = (int) $this->input->get('student_id');

        $active_year = $this->Higaad_model->get_active_academic_year();
        if (!$active_year) {
            echo json_encode([]);
            return;
        }

        $chapters = $this->Higaad_model->get_available_chapters($student_id, $active_year->id);
        echo json_encode($chapters);
    }

    // AJAX – save one student row (hore: HigaadResults::store_row)
 // application/controllers/teacher/Higaad.php
public function store_row()
{
    $teacher_id = $this->get_logged_in_teacher_id();   // users.id, waa ok haddii uu 0 ama null noqdo

    $student_id = (int) $this->input->post('student_id');   // tbl_customer.customer_id
    $class_id   = (int) $this->input->post('class_id');
    $chapter_id = (int) $this->input->post('chapter_id');
    $xifdi      = (int) $this->input->post('xifdi');
    $imlaa      = (int) $this->input->post('imlaa');
    $khad       = (int) $this->input->post('khad');

    $active_year = $this->Higaad_model->get_active_academic_year();
    $open_window = $active_year ? $this->Higaad_model->get_open_exam_window($active_year->id) : null;

    if (!$active_year || !$open_window) {
        echo json_encode(['success' => false, 'message' => 'No open exam window.']);
        return;
    }

    if ($this->Higaad_model->is_class_submitted(
        $class_id,
        $active_year->id,
        $open_window->exam_month,
        $open_window->exam_year
    )) {
        echo json_encode(['success' => false, 'message' => 'Class already submitted.']);
        return;
    }

    // small validation
    if ($xifdi < 0 || $imlaa < 0 || $khad < 0) {
        echo json_encode(['success' => false, 'message' => 'Marks must be positive.']);
        return;
    }

    if (!$chapter_id) {
        echo json_encode(['success' => false, 'message' => 'Please select chapter.']);
        return;
    }

    // Halkan waxaan ku xisaabineynaa TOTAL si uu ula jaan qaado column-ka DB
    $total = $xifdi + $imlaa + $khad;

    $data = [
        'student_id'       => $student_id,
        'class_id'         => $class_id,
        'academic_year_id' => $active_year->id,
        'exam_month'       => $open_window->exam_month,
        'exam_year'        => $open_window->exam_year,
        'chapter_id'       => $chapter_id,
        'xifdi'            => $xifdi,
        'imlaa'            => $imlaa,
        'khad'             => $khad,
        'total'            => $total,
        'teacher_id'       => $teacher_id,
    ];

    $result = $this->Higaad_model->save_result($data);
    echo json_encode($result);
}


    // AJAX – final submit for a class (hore: HigaadResults::submit_class)
 public function submit_class()
{
    $teacher_id = $this->get_logged_in_teacher_id();   // = users.id
    $class_id   = (int) $this->input->post('class_id');

    $active_year = $this->Higaad_model->get_active_academic_year();
    $open_window = $active_year ? $this->Higaad_model->get_open_exam_window($active_year->id) : null;

    if (!$active_year || !$open_window) {
        echo json_encode(['success' => false, 'message' => 'No open exam window.']);
        return;
    }

    $has_rows = $this->db->where('class_id', $class_id)
                         ->where('academic_year_id', $active_year->id)
                         ->where('exam_month', $open_window->exam_month)
                         ->where('exam_year',  $open_window->exam_year)
                         ->count_all_results('higaad_results') > 0;

    if (!$has_rows) {
        echo json_encode(['success' => false, 'message' => 'No results to submit for this class.']);
        return;
    }

    $res = $this->Higaad_model->submit_class(
        $class_id,
        $active_year->id,
        $open_window->exam_month,
        $open_window->exam_year,
        $teacher_id     // Halkan waxaan u gudbinay users.id
    );

    echo json_encode($res);
}

    /* =========================================================
     *  ADMIN PART – Higaad Exam Windows & Submitted Subjects
     *  URL: admin/higaad/windows, admin/higaad/submitted, ...
     * ========================================================= */

    // List/create exam windows (hore: HigaadExamWindows::index)
    public function windows()
    {
        // TODO: admin auth check

        $data['academic_years'] = $this->db->get('academic_years')->result();
        $data['windows'] = $this->db->order_by('exam_year', 'DESC')
                                    ->order_by('exam_month', 'DESC')
                                    ->get('higaad_exam_windows')->result();
        $this->load->view('admin/higaad_windows_index', $data);
    }

    // Store new exam window (hore: HigaadExamWindows::store)
  public function store_window()
{
    // TODO: admin auth check

    $academic_year_id = (int) $this->input->post('academic_year_id');
    $exam_month       = (int) $this->input->post('month');   // ka imanaya <select name="month">
    $status           = $this->input->post('status');        // 'open' ama 'closed'

    // Ka hel academic_years row-ga
    $year = $this->db->where('id', $academic_year_id)
                     ->get('academic_years')
                     ->row();

    if (!$year) {
        $this->session->set_flashdata('danger', 'Academic year invalid ah ayaad dooratay.');
        redirect('settings'); // dib ugu noqo settings
    }

    // Haddii aad leedahay start_date:
    // $exam_year = (int) date('Y', strtotime($year->start_date));
    // Haddii aadan haysan start_date oo magaca yahay "2024/2025", sanadka hore ka jar:
    $exam_year = (int) substr($year->name, 0, 4);

    $this->db->trans_start();

    if ($status === 'open') {
        // 1) windows oo dhan xiro
        $this->db->set('status', 'closed')->update('higaad_exam_windows');

        // 2) academic_years oo dhan ka dhig inactive
        $this->db->set('is_active', 0)->update('academic_years');

        // 3) academic year-kan ka dhig ACTIVE
        $this->db->where('id', $academic_year_id)
                 ->update('academic_years', ['is_active' => 1]);
    }

    // 4) samee / kaydi window-ka cusub
    $data = [
        'academic_year_id' => $academic_year_id,
        'exam_month'       => $exam_month,
        'exam_year'        => $exam_year,
        'status'           => $status,
        'created_at'       => date('Y-m-d H:i:s'),
    ];

    $this->db->insert('higaad_exam_windows', $data);

    $this->db->trans_complete();

    if ($this->db->trans_status() === FALSE) {
        $this->session->set_flashdata('danger', 'Waxaa dhacay qalad kaydinta window-ka.');
    } else {
        $this->session->set_flashdata('success', 'Academic year & exam window waa la furay.');
    }

    redirect('settings');   // ama 'admin/higaad/windows' haddii aad rabto
}



////////////////////////SUMITTED METHODS////////////////////////////////////////




    // View submitted subjects (hore: HigaadExamWindows::submitted_subjects)
public function submitted_subjects()
{
    // → GET ka ka akhri
    $academic_year_id = $this->input->get('academic_year_id');
    $exam_month       = $this->input->get('exam_month');
    $exam_year        = $this->input->get('exam_year');

    // dhammaan academic years si loo buuxiyo filter-ka
    $data['academic_years'] = $this->Higaad_model->get_all_academic_years();

    // xasuuso waxa la doortay si select-yada loo “selected” yeelo
    $data['selected_year_id']   = $academic_year_id;
    $data['selected_month']     = $exam_month;
    $data['selected_exam_year'] = $exam_year;

    // haddii aysan jirin wax filter ah → isticmaal ACTIVE year + open window
    if (empty($academic_year_id) || empty($exam_month) || empty($exam_year)) {
        $active_year = $this->Higaad_model->get_active_academic_year();
        $open_window = $active_year
            ? $this->Higaad_model->get_open_exam_window($active_year->id)
            : null;

        if ($active_year && $open_window) {
            $academic_year_id = $active_year->id;
            $exam_month       = $open_window->exam_month;
            $exam_year        = $open_window->exam_year;

            // sidoo kale ku cusboonaysii "selected_*" si ay u muuqdaan
            $data['selected_year_id']   = $academic_year_id;
            $data['selected_month']     = $exam_month;
            $data['selected_exam_year'] = $exam_year;
        }
    }

    // had iyo jeer u dir model-ka (filters optional)
    $data['submissions'] = $this->Higaad_model
        ->get_submissions($academic_year_id, $exam_month, $exam_year);

    $this->load->view('admin/higaad_submitted_subjects', $data);
}



public function view_submitted_class($submission_id)
{
    // TODO: admin auth check

    $submission = $this->Higaad_model->get_submission_by_id($submission_id);
    if (!$submission) {
        show_404();
        return;
    }

    $results = $this->Higaad_model->get_class_results_with_students(
        $submission->class_id,
        $submission->academic_year_id,
        $submission->exam_month,
        $submission->exam_year
    );

    $data = [
        'submission' => $submission,
        'results'    => $results,
    ];

    $this->load->view('admin/higaad_submitted_class_view', $data);
}


public function setting()
{
    // TODO: halkan ku dar admin auth check haddii loo baahdo

    // isticmaal model-ka halkii aad direct uga wici lahayd $this->db
    $data['academic_years'] = $this->Higaad_model->get_all_academic_years();

    // Load view-ga settings (kaas aad snippet-kiisa soo dirtay)
    $this->load->view('settings/index', $data);
}


public function fill_class()
{
    $teacher_id = $this->get_logged_in_teacher_id();

    $class_id = (int) $this->input->post('class_id');
    if (!$class_id) {
        redirect('teacher/higaad');
        return;
    }

    $active_year = $this->Higaad_model->get_active_academic_year();
    $open_window = $active_year
        ? $this->Higaad_model->get_open_exam_window($active_year->id)
        : null;

    if (!$active_year || !$open_window) {
        $this->session->set_flashdata('danger', 'No open exam window.');
        redirect('teacher/higaad');
        return;
    }

    // ✅ Just check, do NOT redirect – we want read-only mode if true
    $is_submitted = $this->Higaad_model->is_class_submitted(
        $class_id,
        $active_year->id,
        $open_window->exam_month,
        $open_window->exam_year
    );

    // students of the class
    $students = $this->Higaad_model->get_students_by_class($class_id);

    // all results for this class + window
    $results_by_student = $this->Higaad_model
        ->get_results_for_class_window(
            $class_id,
            $active_year->id,
            $open_window->exam_month,
            $open_window->exam_year
        );

    $data = [
        'class_id'           => $class_id,
        'students'           => $students,
        'active_year'        => $active_year,
        'open_window'        => $open_window,
        'results_by_student' => $results_by_student,
        'is_submitted'       => $is_submitted,  // 🔴 important flag
    ];

    $this->load->view('teacher/higaad_fill_class', $data);
}


}
