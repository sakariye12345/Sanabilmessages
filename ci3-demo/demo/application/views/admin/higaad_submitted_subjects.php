<?php $this->load->view('/inc/hygiene_header.php'); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Higaad – Submitted Classes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet"
          href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container-fluid py-3">
    <h4 class="mb-3">
        Higaad – Submitted Classes
        <?php if (!empty($selected_year_id) && !empty($selected_month) && !empty($selected_exam_year)): ?>
            <?php
            $month_name = date('F', mktime(0, 0, 0, (int)$selected_month, 10));
            ?>
            <small class="text-muted">
                (<?= $month_name . ' ' . (int)$selected_exam_year; ?>)
            </small>
        <?php endif; ?>
    </h4>

    <!-- Filter form (optional) -->
    <form method="get" class="form-inline mb-3">
        <label class="mr-2">Academic year</label>
        <select name="academic_year_id" class="form-control mr-3">
            <option value="">-- All / active --</option>
            <?php foreach ($academic_years as $ay): ?>
                <option value="<?= (int)$ay->id; ?>"
                    <?= (!empty($selected_year_id) && $selected_year_id == $ay->id) ? 'selected' : ''; ?>>
                    <?= html_escape($ay->name); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="mr-2">Month</label>
        <select name="exam_month" class="form-control mr-3">
            <option value="">--</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <?php $label = date('F', mktime(0, 0, 0, $m, 10)); ?>
                <option value="<?= $m; ?>" <?= (!empty($selected_month) && $selected_month == $m) ? 'selected' : ''; ?>>
                    <?= $label; ?>
                </option>
            <?php endfor; ?>
        </select>

        <label class="mr-2">Year</label>
        <input type="number"
               name="exam_year"
               class="form-control mr-3"
               value="<?= !empty($selected_exam_year) ? (int)$selected_exam_year : ''; ?>"
               style="max-width: 120px;">

        <button type="submit" class="btn btn-primary">
            Filter
        </button>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive mb-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>Class</th>
                        <th>Academic Year</th>
                        <th>Month</th>
                        <th>Submitted by</th>
                        <th>Submitted at</th>
                        <th style="width:120px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($submissions)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                No Higaad classes submitted for the selected window.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $i = 1; ?>
                        <?php foreach ($submissions as $row): ?>
                            <?php
                            $month_name = date('F', mktime(0, 0, 0, (int)$row->exam_month, 10));
                            ?>
                            <tr>
                                <td><?= $i++; ?></td>
                                <td><?= html_escape($row->class_name); ?></td>
                                <td><?= (int)$row->academic_year_id; /* or show ay name if you join it */ ?></td>
                                <td><?= $month_name . ' ' . (int)$row->exam_year; ?></td>
                                                                <?php
// dooro magaca la soo bandhigayo
$submitted_name = 'System';

if (!empty($row->submitted_fullname)) {
    $submitted_name = $row->submitted_fullname;        // first + last name
} elseif (!empty($row->submitted_username)) {
    $submitted_name = $row->submitted_username;        // fallback: username
}
?>
<td><?= html_escape($submitted_name); ?></td>
                                
                                <td><?= html_escape($row->submitted_at); ?></td>
                                <td>
                                    <a href="<?= site_url('admin/higaad/submitted/view/' . (int)$row->id); ?>"
                                       class="btn btn-outline-primary btn-sm">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

</body>
</html>
