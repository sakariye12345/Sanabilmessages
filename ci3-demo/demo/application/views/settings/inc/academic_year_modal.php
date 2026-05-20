<div class="modal fade" id="academic_year_modal" tabindex="-1" role="dialog" aria-labelledby="academic_year_modal_label" aria-hidden="true" style="margin-top: -60px;">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form action="<?php echo base_url('admin/academic_year/save'); ?>" method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="academic_year_modal_label">Configure Academic Year</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span>&times;</span>
        </button>
      </div>

      <div class="modal-body" >

        <!-- Academic year from DB -->
       <div class="form-group">
    <label for="academic_year_id">Academic Year</label>
    <select name="academic_year_id" id="academic_year_id"
            class="form-control select2_ac_year" style="width: 100%;" required>
        <option value="">Select academic year</option>
        <?php if (!empty($academic_years)): ?>
            <?php foreach ($academic_years as $year): ?>
                <option value="<?php echo $year->id; ?>">
                    <?php echo html_escape($year->name); ?>
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
</div>

        <!-- 12 months (hard-coded loop) -->
        <div class="form-group">
            <label for="month">Month</label>
            <select class="form-control" name="month" id="month" required>
              <option value="">Select month</option>
              <?php
                $months = [
                    1  => 'January',
                    2  => 'February',
                    3  => 'March',
                    4  => 'April',
                    5  => 'May',
                    6  => 'June',
                    7  => 'July',
                    8  => 'August',
                    9  => 'September',
                    10 => 'October',
                    11 => 'November',
                    12 => 'December',
                ];
                foreach ($months as $num => $label): ?>
                    <option value="<?php echo $num; ?>"><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
        </div>

        <!-- Status (open/close) -->
        <div class="form-group">
            <label for="status">Status</label>
            <select class="form-control" name="status" id="status" required>
              <option value="">Select</option>
              <option value="open">Open</option>
              <option value="closed">Close</option>
            </select>
        </div>

      </div>

      <div class="modal-footer">
        <button style="width: 100px" type="submit" class="btn btn-primary rounded-pill">Save</button>
        <button style="width: 100px" type="button" class="btn btn-secondary rounded-pill" data-dismiss="modal">Close</button>
      </div>
    </form>
  </div>
</div>


<script>
$(document).ready(function () {

    // init Select2 ee academic year-ka
    $('.select2_ac_year').select2({
        dropdownParent: $('#academic_year_modal'), // muhiim marka modal la joogo
        placeholder: 'Select academic year',
        allowClear: true
    });

});
</script>