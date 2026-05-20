<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Hygiene_model extends CI_Model
{
    /* CLASSES: weli ka keen table-ka classes */
    public function get_classes()
    {
        return $this->db->select('id, name')
            ->from('classes')
            ->where('is_active', 1)
            ->order_by('name', 'ASC')
            ->get()->result_array();
    }

    /* STUDENTS: ka keen tbl_customer halkii students */
    public function get_students_by_class($class_id)
    {
        $rows = $this->db->select('customer_id AS id,
                                   atd_number  AS attendance_no,
                                   customer_name AS full_name')
            ->from('tbl_customer')
            ->where(['type' => 1, 'class_id' => (int)$class_id]) // type=1 = arday
            ->order_by('atd_number', 'ASC')
            ->order_by('customer_id', 'ASC')
            ->get()->result_array();

        // Haddii atd_number = 0/madhan, si muuqaal ah ugu buuxi 1..N si liisku u hagaago
        $roll = 1;
        foreach ($rows as &$r) {
            if (empty($r['atd_number']) || (int)$r['atd_number'] === 0) {
                $r['atd_number'] = $roll;
            }
            $roll++;
        }
        unset($r);

        return $rows;
    }

    /* Attendance map ee class+date */
   public function get_attendance_map($class_id, $date)
{
    $rows = $this->db->select('student_id, status')
                     ->from('hygiene_attendance')
                     ->where(['class_id' => $class_id, 'attn_date' => $date])
                     ->get()
                     ->result_array();
    $map = [];
    foreach ($rows as $r) {
        $map[(int)$r['student_id']] = $r['status'];
    }
    return $map;
}


    /* Save/Upsert attendance (student_id = tbl_customer.customer_id) */
    public function upsert_attendance($class_id, $date, array $rows, $created_by = null)
{
    $this->db->trans_start();

    $sql = "INSERT INTO hygiene_attendance
               (class_id, student_id, attn_date, status, created_by)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
               status = VALUES(status),
               updated_at = CURRENT_TIMESTAMP";

    foreach ($rows as $r) {
        $this->db->query($sql, [
            (int)$class_id,
            (int)$r['student_id'],
            $date,
            $r['status'],
            $created_by
        ]);
    }

    $this->db->trans_complete();
    return $this->db->trans_status();
}


    /* Report: ku xir tbl_customer si aad u hesho totals per student */
    public function report_totals($class_id, $from_date, $to_date)
    {
        return $this->db->query("
            SELECT
                c.customer_id   AS student_id,
                c.atd_number    AS attendance_no,
                c.customer_name AS full_name,
                SUM(CASE WHEN ha.status='EH' THEN 1 ELSE 0 END) AS total_eh,
                SUM(CASE WHEN ha.status='AC' THEN 1 ELSE 0 END) AS total_ac,
                SUM(CASE WHEN ha.status='UN' THEN 1 ELSE 0 END) AS total_un
            FROM tbl_customer c
            LEFT JOIN hygiene_attendance ha
                   ON ha.student_id = c.customer_id
                  AND ha.attn_date BETWEEN ? AND ?
            WHERE c.type = 1
              AND c.class_id = ?
            GROUP BY c.customer_id, c.atd_number, c.customer_name
            ORDER BY c.atd_number ASC, c.customer_id ASC
        ", [$from_date, $to_date, (int)$class_id])->result_array();
    }
public function get_all_attendance()
{
    return $this->db->select('ha.*, c.name AS class_name, cu.customer_name AS student_name')
                    ->from('hygiene_attendance ha')
                    ->join('classes c', 'c.id = ha.class_id', 'left')
                    ->join('tbl_customer cu', 'cu.customer_id = ha.student_id', 'left')
                    ->order_by('ha.attn_date', 'DESC')
                    ->get()
                    ->result_array();
}
public function get_attendance_batches(): array
{
    // Submitted batches (group existing attendance rows)
    $submitted = $this->db->query("
        SELECT
            MIN(ha.id)  AS any_id,
            ha.class_id,
            ha.attn_date            AS date,
            c.name                  AS class_name,
            CONCAT('System at ', DATE_FORMAT(MIN(ha.created_at),'%d-%b-%Y %H:%i:%s')) AS submitted_by,
            'Submitted'             AS status
        FROM hygiene_attendance ha
        LEFT JOIN classes c ON c.id = ha.class_id
        GROUP BY ha.class_id, ha.attn_date, c.name
    ")->result_array();

    // Pending/Submitted markers from hygiene_batches
    // (if a class/date appears in both (submitted + batch), we’ll keep the Submitted one)
    $batches = $this->db->query("
        SELECT
            hb.id                   AS any_id,
            hb.class_id,
            hb.attn_date            AS date,
            c.name                  AS class_name,
            CONCAT('System at ', DATE_FORMAT(hb.created_at,'%d-%b-%Y %H:%i:%s')) AS submitted_by,
            CASE WHEN hb.status='SUBMITTED' THEN 'Submitted' ELSE 'Pending' END AS status
        FROM hygiene_batches hb
        LEFT JOIN classes c ON c.id = hb.class_id
    ")->result_array();

    // Merge: keep the Submitted record if duplicates exist for same class/date
    $key = fn($r) => $r['class_id'].'|'.$r['date'];
    $map = [];

    foreach ($batches as $r) {
        $map[$key($r)] = $r;          // seed with Pending/Submitted from batches
    }
    foreach ($submitted as $r) {
        $map[$key($r)] = $r;          // overwrite with real Submitted from attendance
    }

    $rows = array_values($map);

    // Sort: newest date first, then class name
    usort($rows, function($a,$b){
        if ($a['date'] === $b['date']) return strcasecmp($a['class_name'], $b['class_name']);
        return strcmp($b['date'], $a['date']); // DESC
    });

    return $rows;
}

public function mark_batch_submitted($class_id, $attn_date, $user_id = null)
{
    $class_id  = (int)$class_id;
    $attn_date = (string)$attn_date;

    // Does a batch row already exist?
    $row = $this->db->get_where('hygiene_batches', [
        'class_id'  => $class_id,
        'attn_date' => $attn_date
    ])->row_array();

    $now = date('Y-m-d H:i:s');

    if ($row) {
        // Update -> SUBMITTED
        $this->db->where('id', (int)$row['id'])->update('hygiene_batches', [
            'status'     => 'SUBMITTED',
            'updated_at' => $now,
            'updated_by' => $user_id,
        ]);
        return $this->db->affected_rows() >= 0;
    }

    // Create new row already SUBMITTED (in case it was never created as PENDING)
    return $this->db->insert('hygiene_batches', [
        'class_id'   => $class_id,
        'attn_date'  => $attn_date,
        'status'     => 'SUBMITTED',
        'created_at' => $now,
        'created_by' => $user_id,
    ]);
}



private function get_username_by_id($user_id)
{
    if (!$user_id) return 'System';
    $u = $this->db->select('first_name, last_name')
                  ->from('users')
                  ->where('id', (int)$user_id)
                  ->get()
                  ->row_array();
    return $u ? trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) : 'System';
}




public function get_attendance_detail($class_id, $date)
{
    return $this->db->select('
                    cu.customer_id AS student_id,
                    cu.customer_name   AS student_name,
                    cu.atd_number,
                    ha.status
                ')
                ->from('tbl_customer cu')
                ->join('hygiene_attendance ha',
                       'ha.student_id = cu.customer_id AND ha.class_id='.(int)$class_id.' AND ha.attn_date='.$this->db->escape($date),
                       'left')
                ->where('cu.class_id', (int)$class_id)
                ->order_by('cu.atd_number', 'ASC')
                ->get()->result_array();
}


// in Hygiene_model
public function ensure_pending_batch($class_id, $attn_date, $user_id = null)
{
    $row = $this->db->get_where('hygiene_batches', [
        'class_id'  => (int)$class_id,
        'attn_date' => $attn_date,
    ])->row_array();

    if ($row) {
        // If it exists but not submitted yet, keep it as PENDING (no-op)
        if ($row['status'] !== 'SUBMITTED') {
            $this->db->where('id', (int)$row['id'])
                     ->update('hygiene_batches', ['status' => 'PENDING']);
        }
        return true;
    }

    // Create new pending batch
    return $this->db->insert('hygiene_batches', [
        'class_id'   => (int)$class_id,
        'attn_date'  => $attn_date,
        'status'     => 'PENDING',
        'created_by' => $user_id,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}


 public function has_class_date($class_id, $date)
{
    return (bool) $this->db->select('1')
        ->from('hygiene_attendance')
        ->where('class_id', (int)$class_id)
        ->where('attn_date', $date)
        ->limit(1)
        ->get()->row();
}

public function hygiene_report_totals($class_id, $from_date, $to_date)
{
    $params = [$from_date, $to_date];
    $sql = "
        SELECT
            c.customer_id   AS student_id,
            c.atd_number    AS attendance_no,
            c.customer_name AS full_name,
            c.class_id,
            cl.name         AS class_name,
            SUM(CASE WHEN ha.status='NR' THEN 1 ELSE 0 END) AS total_nr,
            SUM(CASE WHEN ha.status='NL' THEN 1 ELSE 0 END) AS total_nl,
            SUM(CASE WHEN ha.status='HR' THEN 1 ELSE 0 END) AS total_hr,
            SUM(CASE WHEN ha.status='BH' THEN 1 ELSE 0 END) AS total_bh
        FROM tbl_customer c
        LEFT JOIN hygiene_attendance ha
               ON ha.student_id = c.customer_id
              AND ha.attn_date BETWEEN ? AND ?
        LEFT JOIN classes cl ON cl.id = c.class_id
        WHERE c.type = 1
    ";
    if ((int)$class_id > 0) {
        $sql .= " AND c.class_id = ? ";
        $params[] = (int)$class_id;
    }
    $sql .= "
        GROUP BY c.customer_id, c.atd_number, c.customer_name, c.class_id, cl.name
        ORDER BY cl.name ASC, c.atd_number ASC, c.customer_id ASC
    ";
    return $this->db->query($sql, $params)->result_array();
}


public function report_totals_with_class($class_id, $from_date, $to_date)
{
    $sql = "
      SELECT
        c.customer_id   AS student_id,
        c.atd_number    AS attendance_no,
        c.customer_name AS full_name,
        cl.name         AS class_name,
        SUM(CASE WHEN ha.status='NR' THEN 1 ELSE 0 END) AS total_nr,
        SUM(CASE WHEN ha.status='NL' THEN 1 ELSE 0 END) AS total_nl,
        SUM(CASE WHEN ha.status='HR' THEN 1 ELSE 0 END) AS total_hr,
        SUM(CASE WHEN ha.status='BH' THEN 1 ELSE 0 END) AS total_bh,
        COUNT(ha.id) AS total_marked
      FROM tbl_customer c
      JOIN classes cl ON cl.id = c.class_id
      LEFT JOIN hygiene_attendance ha
             ON ha.student_id = c.customer_id
            AND ha.class_id   = c.class_id
            AND ha.attn_date BETWEEN ? AND ?
      WHERE c.type = 1
        AND c.class_id = ?
      GROUP BY c.customer_id, c.atd_number, c.customer_name, cl.name
      ORDER BY c.atd_number ASC, c.customer_id ASC
    ";
    return $this->db->query($sql, [$from_date, $to_date, (int)$class_id])->result_array();
}


// Basic student + class info
public function get_student_basic(int $student_id): ?array
{
    $row = $this->db->select('
                c.customer_id     AS id,
                c.customer_name   AS full_name,
                c.atd_number      AS attendance_no,
                COALESCE(c.customer_phone, "") AS phone,
                c.class_id,
                cl.name           AS class_name
            ')
            ->from('tbl_customer c')
            ->join('classes cl','cl.id = c.class_id','left')
            ->where('c.type', 1) // 1 = student
            ->where('c.customer_id', (int)$student_id)
            ->get()
            ->row_array();

    return $row ?: null;
}


// Summary counts for one student in a date range
public function hygiene_student_summary($student_id, $from_date, $to_date)
{
    $sql = "SELECT
              SUM(CASE WHEN ha.status='NR' THEN 1 ELSE 0 END) AS total_nr,
              SUM(CASE WHEN ha.status='NL' THEN 1 ELSE 0 END) AS total_nl,
              SUM(CASE WHEN ha.status='HR' THEN 1 ELSE 0 END) AS total_hr,
              SUM(CASE WHEN ha.status='BH' THEN 1 ELSE 0 END) AS total_bh,
              COUNT(ha.id)                                     AS total_marked
            FROM hygiene_attendance ha
            WHERE ha.student_id = ?
              AND ha.attn_date BETWEEN ? AND ?";
    return $this->db->query($sql, [(int)$student_id, $from_date, $to_date])->row_array();
}

// Daily rows for the detail table
public function hygiene_student_rows($student_id, $from_date, $to_date)
{
    return $this->db->select('ha.attn_date, ha.status, ha.class_id, cl.name AS class_name')
                    ->from('hygiene_attendance ha')
                    ->join('classes cl','cl.id=ha.class_id','left')
                    ->where('ha.student_id',(int)$student_id)
                    ->where('ha.attn_date >=',$from_date)
                    ->where('ha.attn_date <=',$to_date)
                    ->order_by('ha.attn_date','ASC')
                    ->get()->result_array();
}

// All active students, with class name (used by the dropdown)
public function get_students_dropdown(): array
{
    return $this->db->select("
            c.customer_id   AS id,
            c.atd_number    AS attendance_no,
            c.customer_name AS full_name,
            cl.name         AS class_name
        ")
        ->from('tbl_customer c')
        ->join('classes cl', 'cl.id = c.class_id', 'left')
        ->where('c.type', 1)                 // 1 = student
        ->order_by('cl.name', 'ASC')
        ->order_by('c.atd_number', 'ASC')
        ->order_by('c.customer_id', 'ASC')
        ->get()->result_array();
}

// Delete every attendance row for one class & date + clean batch marker
public function delete_attendance_by_class_date($class_id, $attn_date)
{
    $this->db->where('class_id', (int)$class_id)
             ->where('attn_date', $attn_date)
             ->delete('hygiene_attendance');

    // Optional: also delete (or mark) the batch record
    $this->db->where('class_id', (int)$class_id)
             ->where('attn_date', $attn_date)
             ->delete('hygiene_batches');

    return ($this->db->affected_rows() >= 0);
}

// Delete a single row by numeric id (if you have it)
public function delete_attendance_row($row_id)
{
    return $this->db->where('id', (int)$row_id)->delete('hygiene_attendance');
}

// Delete one student's record for that class & date (keyed delete)
public function delete_attendance_by_keys($class_id, $student_id, $attn_date)
{
    return $this->db->where('class_id', (int)$class_id)
                    ->where('student_id', (int)$student_id)
                    ->where('attn_date', $attn_date)
                    ->delete('hygiene_attendance');
}

//Follow Up
// Create/update follow-up when student is flagged
public function bump_followup($student_id, $class_id, $issue_type, $flag_date, $user_id = null)
{
    $student_id = (int)$student_id;
    $class_id   = (int)$class_id;
    $issue_type = ($issue_type === 'NL') ? 'NL' : 'HR'; // BH waa la kala jabinayaa
    $flag_date  = (string)$flag_date;

    // Raadi issue furan oo isla nooca ah
    $open = $this->db->get_where('hygiene_followups', [
        'student_id' => $student_id,
        'class_id'   => $class_id,
        'issue_type' => $issue_type,
        'status'     => 'OPEN'
    ])->row_array();

    if ($open) {
        // cusboonaysii tirada iyo last_flagged_at
        $this->db->where('id', (int)$open['id'])
                 ->update('hygiene_followups', [
                     'last_flagged_at' => $flag_date,
                     'times_flagged'   => (int)$open['times_flagged'] + 1
                 ]);
        return;
    }

    // abuur rikoor cusub
    $this->db->insert('hygiene_followups', [
        'student_id'      => $student_id,
        'class_id'        => $class_id,
        'issue_type'      => $issue_type,
        'first_flagged_at'=> $flag_date,
        'last_flagged_at' => $flag_date,
        'times_flagged'   => 1,
        'status'          => 'OPEN',
    ]);
}

// Mark an issue as resolved (confirmed compliance)
public function resolve_followup($id, $resolve_date, $note = null, $user_id = null)
{
    return $this->db->where('id', (int)$id)
                    ->where('status', 'OPEN')           // ka hortag double resolve
                    ->update('hygiene_followups', [
                        'status'      => 'RESOLVED',
                        'resolved_at' => $resolve_date,
                        'resolved_by' => $user_id,
                        'note'        => $note,
                    ]);
}

// List open issues (for Follow-ups page)
public function get_open_followups(): array
{
    return $this->db->select("
                hf.id, hf.student_id, hf.class_id, hf.issue_type,
                hf.first_flagged_at, hf.last_flagged_at, hf.times_flagged,
                c.name AS class_name,
                s.customer_name AS student_name, s.atd_number
            ")
            ->from('hygiene_followups hf')
            ->join('classes c','c.id = hf.class_id','left')
            ->join('tbl_customer s','s.customer_id = hf.student_id','left')
            ->where('hf.status', 'OPEN')
            ->where('hf.resolved_at IS NULL', null, false)   // 👈 important
            ->order_by('hf.last_flagged_at','DESC')
            ->order_by('c.name','ASC')
            ->get()->result_array();
}

// OPTIONAL: tirakoob la soo celiyo report-ka ardayga
public function student_compliance_counts($student_id, $from_date = null, $to_date = null)
{
    $this->db->select("
            SUM(CASE WHEN issue_type='HR' AND status='RESOLVED' THEN 1 ELSE 0 END) AS complied_hr,
            SUM(CASE WHEN issue_type='NL' AND status='RESOLVED' THEN 1 ELSE 0 END) AS complied_nl
        ")
        ->from('hygiene_followups')
        ->where('student_id', (int)$student_id);

    if ($from_date) $this->db->where('resolved_at >=', $from_date);
    if ($to_date)   $this->db->where('resolved_at <=', $to_date);

    return $this->db->get()->row_array();
}
// Single resolve
// Single resolve
// Hygiene_model.php

/** Mark a single follow-up as resolved + log compliance */
public function followup_mark_resolved($id, $date, $user_id = null)
{
    $row = $this->db->get_where('hygiene_followups',
        ['id' => (int)$id, 'status' => 'OPEN'])->row_array();
    if (!$row) return false;

    $this->db->trans_start();

    // close the follow-up
    $this->db->where('id', (int)$id)->update('hygiene_followups', [
        'status'      => 'RESOLVED',
        'resolved_at' => $date,
        'resolved_by' => $user_id
    ]);

    // log the compliance
    $this->db->insert('hygiene_compliance', [
        'student_id'   => (int)$row['student_id'],
        'class_id'     => (int)$row['class_id'],
        'type'         => $row['issue_type'],   // 'HR' or 'NL'
        'confirmed_at' => $date,
        'confirmed_by' => $user_id
    ]);

    $this->db->trans_complete();
    return $this->db->trans_status();
}

/** Bulk resolve + bulk compliance log */
public function followups_bulk_mark_resolved(array $ids, $date, $user_id = null): int
{
    if (!$ids) return 0;
    $rows = $this->db->select('id, student_id, class_id, issue_type')
                     ->from('hygiene_followups')
                     ->where_in('id', $ids)
                     ->where('status', 'OPEN')
                     ->get()->result_array();
    if (!$rows) return 0;

    $this->db->trans_start();

    $this->db->where_in('id', array_column($rows,'id'))
             ->update('hygiene_followups', [
                 'status'      => 'RESOLVED',
                 'resolved_at' => $date,
                 'resolved_by' => $user_id
             ]);

    $payload = [];
    foreach ($rows as $r) {
        $payload[] = [
            'student_id'   => (int)$r['student_id'],
            'class_id'     => (int)$r['class_id'],
            'type'         => $r['issue_type'], // HR or NL
            'confirmed_at' => $date,
            'confirmed_by' => $user_id
        ];
    }
    if ($payload) $this->db->insert_batch('hygiene_compliance', $payload);

    $this->db->trans_complete();
    return $this->db->trans_status() ? count($rows) : 0;
}
// In Hygiene_model
public function compliance_report_totals($class_id, $from_date, $to_date): array
{
    $class_id = (int)$class_id;

    // Params for the subquery (date range). We’ll append class_id if provided.
    $params = [$from_date, $to_date];

    // Optional class filter (0 or null => all classes)
    $classFilterSql = '';
    if ($class_id > 0) {
        $classFilterSql = ' AND c.class_id = ? ';
        $params[] = $class_id;
    }

    $sql = "
        SELECT
            c.customer_id   AS student_id,
            c.atd_number    AS attendance_no,
            c.customer_name AS full_name,
            cl.name         AS class_name,

            COALESCE(a.conf_hr, 0)              AS conf_hr,     -- times Hair confirmed
            COALESCE(a.conf_nl, 0)              AS conf_nl,     -- times Nails confirmed
            COALESCE(a.conf_both, 0)            AS conf_both,   -- days with both HR & NL
            COALESCE(a.total_days_confirmed, 0) AS total_confirmed  -- distinct confirm days
        FROM tbl_customer c
        JOIN classes cl ON cl.id = c.class_id

        /* Aggregate compliance per student */
        LEFT JOIN (
            SELECT
                z.student_id,
                SUM(z.hr_cnt) AS conf_hr,
                SUM(z.nl_cnt) AS conf_nl,
                SUM(CASE WHEN z.hr_cnt > 0 AND z.nl_cnt > 0 THEN 1 ELSE 0 END) AS conf_both,
                COUNT(*) AS total_days_confirmed
            FROM (
                /* One row per student per day, with counts of HR & NL that day */
                SELECT
                    hc.student_id,
                    hc.confirmed_at,
                    SUM(CASE WHEN hc.type = 'HR' THEN 1 ELSE 0 END) AS hr_cnt,
                    SUM(CASE WHEN hc.type = 'NL' THEN 1 ELSE 0 END) AS nl_cnt
                FROM hygiene_compliance hc
                WHERE hc.confirmed_at BETWEEN ? AND ?
                GROUP BY hc.student_id, hc.confirmed_at
            ) z
            GROUP BY z.student_id
        ) a ON a.student_id = c.customer_id

        WHERE c.type = 1  /* 1 = student */
        $classFilterSql
        ORDER BY cl.name ASC, c.atd_number ASC, c.customer_id ASC
    ";

    return $this->db->query($sql, $params)->result_array();
}

/** Tirinta “compliance” ee report-ka (per student, date range, class optional) */
public function compliance_report_rows($class_id, $from_date, $to_date): array
{
    // counts by type
    $sql_counts = "
      SELECT hc.student_id,
             SUM(hc.type='HR') AS conf_hr,
             SUM(hc.type='NL') AS conf_nl
      FROM hygiene_compliance hc
      WHERE hc.confirmed_at BETWEEN ? AND ?
      GROUP BY hc.student_id
    ";

    // 'both' = maalmo uu HR *iyo* NL labadaba xaqiijiyay
    $sql_both = "
      SELECT student_id, SUM(both_flag) AS conf_both
      FROM (
         SELECT student_id, confirmed_at,
                MAX(type='HR') hr, MAX(type='NL') nl,
                CASE WHEN MAX(type='HR')=1 AND MAX(type='NL')=1 THEN 1 ELSE 0 END both_flag
         FROM hygiene_compliance
         WHERE confirmed_at BETWEEN ? AND ?
         GROUP BY student_id, confirmed_at
      ) x
      GROUP BY student_id
    ";

    $params = [$from_date, $to_date, $from_date, $to_date];

    $sql = "
      SELECT
        c.customer_id   AS student_id,
        c.customer_name AS full_name,
        cl.name         AS class_name,
        COALESCE(cnt.conf_hr, 0)  AS conf_hr,
        COALESCE(cnt.conf_nl, 0)  AS conf_nl,
        COALESCE(bothy.conf_both,0) AS conf_both
      FROM tbl_customer c
      JOIN classes cl ON cl.id = c.class_id
      LEFT JOIN ($sql_counts) cnt   ON cnt.student_id = c.customer_id
      LEFT JOIN ($sql_both)   bothy ON bothy.student_id = c.customer_id
      WHERE c.type = 1
    ";

    if ((int)$class_id > 0) {
        $sql .= " AND c.class_id = ? ";
        $params[] = (int)$class_id;
    }

    $sql .= " ORDER BY cl.name ASC, c.atd_number ASC, c.customer_id ASC ";

    return $this->db->query($sql, $params)->result_array();
}


public function resolve_student_type(int $student_id, string $issue_type, string $resolved_at, ?int $user_id = null): int
{
    $issue_type = strtoupper(trim($issue_type)); // HR or NL
    if (!in_array($issue_type, ['HR','NL'], true)) return 0;

    $this->db->where([
            'student_id' => $student_id,
            'issue_type' => $issue_type,
            'status'     => 'OPEN'
        ])
        ->update('hygiene_followups', [
            'status'      => 'RESOLVED',
            'resolved_at' => $resolved_at,
            'resolved_by' => $user_id,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    return $this->db->affected_rows();
}

// (Optional) For enabling/disabling buttons intelligently in the table:
public function get_open_types_map(array $student_ids): array
{
    if (empty($student_ids)) return [];
    $rows = $this->db->select('student_id, issue_type')
        ->from('hygiene_followups')
        ->where_in('student_id', $student_ids)
        ->where('status', 'OPEN')
        ->get()->result_array();

    $map = []; // student_id => ['HR'=>true, 'NL'=>true]
    foreach ($rows as $r) {
        $sid = (int)$r['student_id'];
        $typ = strtoupper($r['issue_type']);
        if (!isset($map[$sid])) $map[$sid] = [];
        $map[$sid][$typ] = true;
    }
    return $map;
}

public function followup_mark_resolved_by_student_type(int $student_id, string $type, string $resolved_at, ?int $user_id = null): int
{
    // Resolve the newest OPEN follow-up for this student & type.
    // If you prefer to resolve *all* OPEN of that type, drop the limit().
    $this->db->trans_start();

    // Get one latest OPEN follow-up
    $row = $this->db->select('id')
                    ->from('hygiene_followups')
                    ->where([
                        'student_id' => $student_id,
                        'issue_type' => $type,
                        'status'     => 'OPEN'
                    ])
                    ->order_by('last_flagged_at', 'DESC')
                    ->limit(1)
                    ->get()->row_array();

    if ($row) {
        $this->db->where('id', (int)$row['id'])
                 ->update('hygiene_followups', [
                     'status'      => 'RESOLVED',
                     'resolved_at' => $resolved_at,
                     'resolved_by' => $user_id
                 ]);
    }

    $this->db->trans_complete();
    return $row ? $this->db->affected_rows() : 0;
}

public function resolve_latest_open_by_student_type(int $student_id, string $type, string $resolved_at, ?int $user_id = null): bool
{
    $type = strtoupper(trim($type)); // HR or NL

    // pick the latest OPEN follow-up for that student & type
    $row = $this->db->select('id')
        ->from('hygiene_followups')
        ->where([
            'student_id' => $student_id,
            'issue_type' => $type,
            'status'     => 'OPEN'
        ])
        ->order_by('last_flagged_at','DESC')
        ->limit(1)
        ->get()->row_array();

    if (!$row) return false;

    $this->db->where('id', (int)$row['id'])->update('hygiene_followups', [
        'status'      => 'RESOLVED',
        'resolved_at' => $resolved_at,
        'resolved_by' => $user_id,
        'updated_at'  => date('Y-m-d H:i:s'),
    ]);

    return $this->db->affected_rows() > 0;
}

}
