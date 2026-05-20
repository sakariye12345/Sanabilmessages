<style>
    /* Mobile-only: si Name u helo ballac fiican */
    @media (max-width: 576px) {
        .student-name-col {
            white-space: nowrap;   /* ha kala jabin magaca Asma | Maxamed | Cali */
            min-width: 180px;      /* kor u qaad haddii aad rabto 200, 220, iwm */
        }
        .btn.btn-success.btn-sm {
            width: 150px;
        }
    }
</style>

<?php
// ---------------- Normalize input ----------------
$students = isset($students) && is_array($students) ? $students : [];

$results_by_student = isset($results_by_student) && is_array($results_by_student)
    ? $results_by_student
    : [];

$is_submitted = isset($is_submitted) ? (bool) $is_submitted : false;

// Month name
$month_name = date('F', mktime(0, 0, 0, (int) $open_window->exam_month, 10));

/*
 |------------------------------------------------------------------
 | Class label: isticmaal magaca fasalka (classes.name) haddii la haysto
 | Haddii controller uu soo diro $class_name → isticmaal
 | Haddii kale ka akhri DB-ga ama isticmaal class_id
 |------------------------------------------------------------------
*/
$class_label = '';

// 1) haddii controller uu soo diray magaca fasalka
if (isset($class_name) && $class_name !== '') {
    $class_label = $class_name;
}

// 2) haddii class_name aan la hayn, isku day in aad kasoo akhrido classes
if ($class_label === '' && !empty($class_id)) {
    $class_row = $this->db->select('name')
                          ->where('id', (int) $class_id)
                          ->get('classes')
                          ->row();
    if ($class_row) {
        $class_label = $class_row->name;        // tusaale: "Grade 2B"
    }
}

// 3) fallback ugu dambeeya
if ($class_label === '') {
    if (!empty($students) && isset($students[0]->class_id)) {
        $class_label = (int) $students[0]->class_id;
    } else {
        $class_label = (int) $class_id;
    }
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            Class:
            <strong><?= html_escape($class_label); ?></strong>
            &nbsp; | &nbsp;
            Month:
            <strong><?= html_escape($month_name . ' ' . (int) $open_window->exam_year); ?></strong>
        </span>

        <?php if (!empty($students)): ?>
            <button
                id="btn-submit-class"
                type="button"
                class="btn btn-success btn-sm"
                data-class-id="<?= (int) $class_id; ?>"
                <?= $is_submitted ? 'disabled aria-disabled="true"' : ''; ?>
            >
                <?= $is_submitted ? 'Already submitted' : 'Gudbi'; ?>
            </button>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:40px;">#</th>
                    <th style="width:120px;">ID</th>
                    <th>Name</th>
                    <th style="width:140px;">Mowduuc</th>
                    <th style="width:80px;">Xifdi</th>
                    <th style="width:80px;">Imlaa</th>
                    <th style="width:80px;">Khad</th>
                    <th style="width:120px;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($students)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted">
                        No students in this class.
                    </td>
                </tr>
            <?php else: ?>
                <?php $i = 1; ?>
                <?php foreach ($students as $st): ?>
                    <?php
                        $current_chapter_id = $st->current_chapter_id ?? null;
                        $chapter_label = $current_chapter_id
                            ? 'Chapter ' . (int) $current_chapter_id
                            : '-';

                        $res = !empty($results_by_student[$st->customer_id])
                            ? $results_by_student[$st->customer_id]
                            : null;
                    ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><?= html_escape($st->student_code); ?></td>
                        <td class="student-name-col"><?= html_escape($st->customer_name); ?></td>
                        <td><?= html_escape($chapter_label); ?></td>
                        <td><?= $res ? (int) $res->xifdi : ''; ?></td>
                        <td><?= $res ? (int) $res->imlaa : ''; ?></td>
                        <td><?= $res ? (int) $res->khad : ''; ?></td>
                        <td>
                            <button
                                type="button"
                                class="btn btn-outline-primary btn-sm btn-fill"
                                data-student-id="<?= (int) $st->customer_id; ?>"
                                data-student-name="<?= html_escape($st->customer_name); ?>"
                                data-class-id="<?= (int) $class_id; ?>"
                                <?= $is_submitted ? 'disabled aria-disabled="true"' : ''; ?>
                            >
                                Buuxi
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
