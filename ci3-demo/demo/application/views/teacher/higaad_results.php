<?php $this->load->view('/inc/hygiene_header.php'); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Higaad Exam Results</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS (v4) -->
    <link rel="stylesheet"
          href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container py-3" style="max-width: 700px;">
    <h4 class="mb-3">Higaad Exam Results</h4>

    <?php
    // hal mar ayaan akhrinaynaa fariinta higaad
    $higaad_danger = $this->session->flashdata('higaad_danger');
    ?>

    <?php if (!empty($higaad_danger)): ?>
        <div class="alert alert-danger">
            <?= $higaad_danger; ?>
        </div>
    <?php endif; ?>

    <?php if (!$active_year): ?>
        <div class="alert alert-warning">
            Academic year lama dejin.
        </div>

    <?php elseif (!$open_window): ?>
        <div class="alert alert-info">
            Ma jiro exam window Higaad oo hadda open ah.
        </div>

    <?php else: ?>
        <div class="card mb-3">
            <div class="card-body">
                <p class="mb-1">
                    <strong>Academic Year:</strong>
                    <?= html_escape($active_year->name); ?>
                </p>
                <p class="mb-0">
                    <strong>Exam Month:</strong>
                    <?= date('F', mktime(0, 0, 0, $open_window->exam_month, 10)); ?>
                    <?= (int)$open_window->exam_year; ?>
                </p>
            </div>
        </div>

        <!-- Button si loo furo modal-ka diyaarinta -->
        <button class="btn btn-primary btn-block mb-3"
                data-toggle="modal"
                data-target="#prepareModal">
            Prepare Higaad Results
        </button>

    <?php endif; ?>
</div>

<!-- Prepare Modal (doorashada class-ka kaliya) -->
<div class="modal fade" id="prepareModal" tabindex="-1" role="dialog"
     aria-labelledby="prepareModalLabel" aria-hidden="true" style="margin-top: -110px;">
    <div class="modal-dialog modal-dialog-centered" role="document">

        <!-- form-kan waxa uu toos ugu diraa fill_class -->
        <form class="modal-content"
              method="post"
              action="<?= site_url('teacher/higaad/fill_class'); ?>">

            <div class="modal-header">
                <h5 class="modal-title" id="prepareModalLabel">Prepare Higaad Results</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="form-group">
                    <label>Class</label>
                    <select name="class_id" class="form-control" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach ($teacher_classes as $c): ?>
                            <option value="<?= $c->id; ?>">
                                <?= html_escape($c->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Exam Month</label>
                    <input type="text"
                           class="form-control"
                           readonly
                           value="<?=
                               $open_window
                                   ? date('F', mktime(0, 0, 0, $open_window->exam_month, 10))
                                   : '';
                           ?>">
                </div>

            </div>

            <div class="modal-footer">
                <button style="width: 120px"
                        type="submit"
                        class="btn btn-primary rounded-pill">
                    Prepare
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JS (jQuery + Bootstrap) -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

</body>
</html>
