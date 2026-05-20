<?php $this->load->view('/inc/hygiene_header.php'); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fill Higaad Results</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet"
          href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container py-3">
<h4 class="mb-3">
    Higaad Exam Results – Class <?= (int) $class_id; ?>
</h4>

<?php
$month_name = date('F', mktime(0, 0, 0, $open_window->exam_month, 10));
?>

<?php if (!empty($is_submitted)): ?>
    <div class="alert alert-danger">
        Class has already been submitted for
        <strong><?= $month_name . ' ' . (int)$open_window->exam_year; ?></strong>.
        You can only <strong>view</strong> the results; editing and submitting again
        are disabled.
    </div>
<?php endif; ?>
    <!-- Academic year + month -->
    <div class="card mb-3">
        <div class="card-body">
            <p class="mb-1">
                <strong>Academic Year:</strong>
                <?= html_escape($active_year->name); ?>
            </p>
            <p class="mb-0">
                <strong>Exam Month:</strong>
                <?= date('F', mktime(0, 0, 0, $open_window->exam_month, 10)); ?>
                <?= (int) $open_window->exam_year; ?>
            </p>
        </div>
    </div>

    <!-- Jadwalka ardayda -->
   <?php
    $this->load->view(
        'teacher/partials/higaad_results_table',
        [
            'students'           => $students,
            'class_id'           => $class_id,
            'active_year'        => $active_year,
            'open_window'        => $open_window,
            'results_by_student' => $results_by_student,
            'is_submitted'       => !empty($is_submitted),
        ]
    );
?>

    <a href="<?= site_url('teacher/higaad'); ?>"
       class="btn btn-secondary mt-3">
        &larr; Back to Higaad main
    </a>

</div>

<!-- Fill Result Modal -->
<div class="modal fade" id="fillModal" tabindex="-1" role="dialog"
     aria-labelledby="fillModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="fillForm" class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="fillModalLabel">Fill Result</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <input type="hidden" name="student_id" id="fill_student_id">
                <input type="hidden" name="class_id"   id="fill_class_id">

                <div class="form-group">
                    <label>Student</label>
                    <input type="text" id="fill_student_name"
                           class="form-control w-100" readonly>
                </div>

                <div class="form-group">
                    <label>Chapter</label>
                    <select name="chapter_id" id="fill_chapter_id"
                            class="form-control" required>
                        <!-- options AJAX ayaa soo buuxin doona -->
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group col-4">
                        <label>Xifdi</label>
                        <input type="number" name="xifdi" id="xifdi"
                               class="form-control" min="0" required>
                    </div>
                    <div class="form-group col-4">
                        <label>Imlaa</label>
                        <input type="number" name="imlaa" id="imlaa"
                               class="form-control" min="0" required>
                    </div>
                    <div class="form-group col-4">
                        <label>Khad</label>
                        <input type="number" name="khad" id="khad"
                               class="form-control" min="0" required>
                    </div>
                </div>

                <div id="fill_error"
                     class="alert alert-danger d-none"></div>

            </div>

            <div class="modal-footer">
                <button type="button" id="btn-save-result"
                        class="btn btn-primary">
                    Save result
                </button>
            </div>

        </form>
    </div>
</div>

<!-- JS (jQuery + Bootstrap) -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<script>
$(function () {

    // -------- Fill Modal --------
    $(document).on('click', '.btn-fill', function () {
        var studentId   = $(this).data('student-id');
        var studentName = $(this).data('student-name');
        var classId     = $(this).data('class-id');

        $('#fillForm')[0].reset();
        $('#fill_error').addClass('d-none').text('');

        $('#fill_student_id').val(studentId);
        $('#fill_student_name').val(studentName);
        $('#fill_class_id').val(classId);

        $('#fill_chapter_id').html('<option value="">Loading...</option>');

        // >>> URL-KAN AYAAN SAXNAY: wuxuu la mid yahay route-kaaga 'teacher/higaad/available'
        $.getJSON('<?= site_url("teacher/higaad/available"); ?>',   // maps to available_chapters
            { student_id: studentId }
        ).done(function (list) {
            var options = '';

            if (!list || !list.length) {
                options = '<option value="">No chapters available</option>';
            } else {
                options = '<option value="">-- Select Chapter --</option>';
                list.forEach(function (ch) {
                    var label = ch.name || ch.title || ch.chapter_name ||
                                ('Chapter ' + ch.chapter_number);
                    options += '<option value="' + ch.id + '">' + label + '</option>';
                });
            }

            $('#fill_chapter_id').html(options);
        }).fail(function (xhr) {
            console.log('available error', xhr.status, xhr.responseText);
            $('#fill_chapter_id').html('<option value="">Error loading</option>');
            alert('Error loading chapters: ' + xhr.status + ' ' + xhr.statusText);
        });

        $('#fillModal').modal('show');
    });

    // -------- Save single result --------
    $('#btn-save-result').on('click', function (e) {
        e.preventDefault();

        var data = $('#fillForm').serialize();

        $.ajax({
            url: '<?= site_url("teacher/higaad/store_row"); ?>',
            method: 'POST',
            data: data,
            success: function (res) {
                try { res = JSON.parse(res); } catch (e) {}

                if (res && res.success) {
                    $('#fillModal').modal('hide');
                    window.location.reload(); // soo cusboonaysii jadwalka
                } else {
                    $('#fill_error')
                        .removeClass('d-none')
                        .text((res && res.message) || 'Error saving result');
                }
            },
            error: function (xhr) {
                alert('Error (save_row): ' + xhr.status + ' ' + xhr.statusText);
            }
        });
    });

    // -------- Submit whole class --------
    $(document).on('click', '#btn-submit-class', function () {
        var classId = $(this).data('class-id');

        if (!classId) {
            alert('Class id missing on button');
            return;
        }

        if (!confirm('Are you sure you want to submit this class? You cannot edit later.')) {
            return;
        }

        $.ajax({
            url: '<?= site_url("teacher/higaad/submit_class"); ?>',
            method: 'POST',
            data: { class_id: classId },
            success: function (res) {
                try { res = JSON.parse(res); } catch (e) {}

                if (res && res.success) {
                    alert('Submitted successfully.');
                    window.location.href = '<?= site_url("teacher/higaad"); ?>';
                } else {
                    alert((res && res.message) || 'Error submitting class.');
                }
            },
            error: function (xhr) {
                alert('Error (submit_class): ' + xhr.status + ' ' + xhr.statusText);
            }
        });
    });

});
</script>

</body>
</html>
