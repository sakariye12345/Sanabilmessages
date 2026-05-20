<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fill Higaad Result</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet"
          href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container py-4">

    <h4 class="mb-3">Fill Higaad Result</h4>

    <?php if($this->session->flashdata('danger')): ?>
        <div class="alert alert-danger"><?= $this->session->flashdata('danger'); ?></div>
    <?php elseif($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?= $this->session->flashdata('success'); ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <p class="mb-1"><strong>Academic Year:</strong> <?= html_escape($active_year->name); ?></p>
            <p class="mb-1">
                <strong>Exam Month:</strong>
                <?= date('F', mktime(0,0,0,$open_window->exam_month,10,$open_window->exam_year)); ?>
                <?= (int)$open_window->exam_year; ?>
            </p>
            <p class="mb-0"><strong>Class ID:</strong> <?= (int)$class_id; ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Student: <strong><?= html_escape($student->customer_name); ?></strong>
        </div>
        <div class="card-body">

            <form method="post" action="<?= site_url('teacher/higaad/store_row_page'); ?>">

                <input type="hidden" name="student_id" value="<?= (int)$student->customer_id; ?>">
                <input type="hidden" name="class_id" value="<?= (int)$class_id; ?>">

                <div class="form-group">
                    <label>Student</label>
                    <input type="text" class="form-control"
                           value="<?= html_escape($student->customer_name); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Chapter</label>
                    <select name="chapter_id" class="form-control" required>
                        <option value="">-- Select Chapter --</option>
                        <?php foreach ($chapters as $ch): ?>
                            <?php
                                $label = $ch->name ?? $ch->title ?? $ch->chapter_name ?? ('Chapter '.$ch->chapter_number);
                            ?>
                            <option value="<?= (int)$ch->id; ?>">
                                <?= html_escape($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group col-4">
                        <label>Xifdi</label>
                        <input type="number" name="xifdi" class="form-control" min="0" required>
                    </div>
                    <div class="form-group col-4">
                        <label>Imlaa</label>
                        <input type="number" name="imlaa" class="form-control" min="0" required>
                    </div>
                    <div class="form-group col-4">
                        <label>Khad</label>
                        <input type="number" name="khad" class="form-control" min="0" required>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?= site_url('teacher/higaad'); ?>" class="btn btn-secondary">
                        ← Back to classes
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Save result
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>

</body>
</html>
