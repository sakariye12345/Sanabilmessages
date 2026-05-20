<?php $this->load->view('/inc/hygiene_header.php'); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Higaad – Class Results</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet"
          href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">

<?php
$month_name = date('F', mktime(0, 0, 0, (int)$submission->exam_month, 10));
?>

<div class="container-fluid py-3">

    <h4 class="mb-3">
        Higaad Class Results – <?= html_escape($submission->class_name); ?>
        <small class="text-muted">
            (<?= $month_name . ' ' . (int)$submission->exam_year; ?>)
        </small>
    </h4>

    <div class="card mb-3">
        <div class="card-body">
            <p class="mb-1">
                <strong>Academic Year:</strong>
                <?= html_escape($submission->academic_year_name); ?>
            </p>
            <p class="mb-1">
                <strong>Class:</strong>
                <?= html_escape($submission->class_name); ?>
            </p>
            <p class="mb-1">
                <strong>Submitted by:</strong>
                <?= html_escape($submission->teacher_name ?: 'System'); ?>
            </p>
            <p class="mb-0">
                <strong>Submitted at:</strong>
                <?= html_escape($submission->submitted_at); ?>
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Students &amp; Marks</span>
            <a href="<?= site_url('admin/higaad/submitted'); ?>" class="btn btn-secondary btn-sm">
                &larr; Back to submitted list
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive mb-0">
                <table class="table table-sm table-striped mb-0">
                    <thead class="thead-light">
                    <tr>
                        <th style="width:60px;">#</th>
                        <th style="width:120px;">Student ID</th>
                        <th>Name</th>
                        <th style="width:120px;">Chapter</th>
                        <th style="width:80px;">Xifdi</th>
                        <th style="width:80px;">Imlaa</th>
                        <th style="width:80px;">Khad</th>
                        <th style="width:80px;">Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($results)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                No results recorded for this class.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $i = 1; ?>
                        <?php foreach ($results as $row): ?>
                            <?php
                            // if your higaad_results table has "total" generated column, we can use it
                            $total = isset($row->total)
                                ? (int)$row->total
                                : ((int)$row->xifdi + (int)$row->imlaa + (int)$row->khad);
                            ?>
                            <tr>
                                <td><?= $i++; ?></td>
                                <td><?= html_escape($row->student_code); ?></td>
                                <td><?= html_escape($row->customer_name); ?></td>
                                <td><?= 'Chapter ' . (int)$row->chapter_id; ?></td>
                                <td><?= (int)$row->xifdi; ?></td>
                                <td><?= (int)$row->imlaa; ?></td>
                                <td><?= (int)$row->khad; ?></td>
                                <td><?= $total; ?></td>
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
