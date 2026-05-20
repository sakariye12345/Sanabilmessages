<style>
  @media (max-width: 520px){
    .modal { margin-top: -30px; }
  }
</style>

<!-- Report Filter Modal -->
<div class="modal fade" id="Student_modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content" style="width:60%; margin:auto; margin-top:60px;">
      <div class="modal-header">
        <h4 class="modal-title">Filter Hygiene Report</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <?php
          // Fallbacks if controller didn’t send them
          $__today   = isset($today)   && $today   ? $today   : date('Y-m-d');
          
        ?>

    <?= form_open(site_url('hygiene/student-go'), ['id'=>'reportForm','method'=>'post']); ?>

<div class="form-row">
  <div class="col">
    <div class="form-group">
      <label>Student</label>
      <select class="form-control" name="student_id" required>
        <option value="">Select Student</option>
        <?php foreach (($students ?? []) as $s): 
              $label = trim(($s['attendance_no'] ? $s['attendance_no'].' - ' : '')
                           . $s['full_name'] . ' (' . $s['class_name'] . ')'); ?>
          <option value="<?= (int)$s['id']; ?>"><?= html_escape($label); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<div class="form-row">
  <div class="col">
    <div class="form-group">
      <label>From</label>
      <input type="date" name="from_date" value="<?= html_escape($today ?? date('Y-m-d')); ?>" class="form-control" required>
    </div>
  </div>
  <div class="col">
    <div class="form-group">
      <label>To</label>
      <input type="date" name="to_date" value="<?= html_escape($today ?? date('Y-m-d')); ?>" class="form-control" required>
    </div>
  </div>
</div>

<?= csrf_field(); ?>
<div class="modal-footer">
            <button type="button" class="btn btn-default text-dark btn-sm rounded-pill" data-dismiss="modal" style="width:100px; border:1px solid #000;">
              <strong>Close</strong>
            </button>
            <button type="submit" id="runReportBtn" class="btn btn-dark btn-sm rounded-pill" style="width:100px;">
              Filter
            </button>
          </div>

<?= form_close(); ?>
      </div>
    </div>
  </div>
</div>


          
<!-- Keep only the libs, no submit interception -->
<script src="<?= base_url('assets/js/jquery.min.js'); ?>"></script>
<script src="<?= base_url('assets/js/bootstrap.bundle.min.js'); ?>"></script>
