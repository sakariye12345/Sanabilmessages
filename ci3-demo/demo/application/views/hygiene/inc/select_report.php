<style>
  @media (max-width: 520px){
    .modal { margin-top: -30px; }
  }
</style>

<!-- Report Filter Modal -->
<div class="modal fade" id="filter_modal" tabindex="-1" role="dialog" aria-hidden="true">
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
          $__classes = isset($classes) && is_array($classes) ? $classes : [];
        ?>

        <?= form_open(site_url('hygiene/report-hygiene/results'), ['id' => 'reportForm', 'method' => 'post']); ?>

          <div class="form-row">
            <div class="col">
              <div class="form-group">
                <label>Class</label>
                <select class="form-control" name="class_id" required>
                  <option value="">Select Class</option>
                  <?php foreach ($__classes as $c): ?>
                    <option value="<?= (int)$c['id']; ?>">
                      <?= html_escape($c['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <div class="form-row">
            <div class="col">
              <div class="form-group">
                <label>From</label>
                <input type="date" name="from_date" value="<?= html_escape($__today); ?>" class="form-control" required>
              </div>
            </div>
            <div class="col">
              <div class="form-group">
                <label>To</label>
                <input type="date" name="to_date" value="<?= html_escape($__today); ?>" class="form-control" required>
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
