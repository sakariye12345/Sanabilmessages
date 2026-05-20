
<!-- Stat Model --> 
<div class="modal fade  " id="add_user_modal"  role="dialog">
   <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
      <div class="modal-content" style=" width: 60%; margin-top: 60px; margin: auto;">
         <div class="modal-header">
            <h4 class="modal-title" id="exampleModalLabel"> Prepare Hygiene Attendance  </h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"  >
            <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body" >
           

           <?= form_open('hygiene/prepare', ['id' => 'prepareForm']); ?>

<div class="form-row">
  <div class="col">
    <div class="form-group">
      <label>Class</label>
      <!-- ADD name="class_id" and required -->
      <select class="form-control" name="class_id" required>
        <option value="">Select Class</option>
        <?php foreach ($classes as $c): ?>
          <option value="<?= (int)$c['id']; ?>"><?= html_escape($c['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<div class="form-row">
  <div class="col">
    <div class="form-group">
      <label>Date</label>
      <input type="date" name="attn_date" value="<?= html_escape($today); ?>" class="form-control" required>
    </div>
  </div>
</div>

<?= csrf_field(); ?>

<div class="modal-footer">
  <button type="button" class="btn btn-default text-dark btn-sm rounded-pill" data-dismiss="modal" style="width:100px; border:1px solid #000;"><strong>Close</strong></button>
  <button type="submit" id="prepareBtn" class="btn btn-dark btn-sm rounded-pill" style="width:100px;">
  Prepare
</button>
</div>

<?= form_close(); ?>
         </div>
      </div>
   </div>
</div>
<!-- End Model -->
<script type="text/javascript">
  $(document).ready(function() {
  $("input#username").on({
  keydown: function(e) {
    if (e.which === 32)
      return false;
  },
  change: function() {
    this.value = this.value.replace(/\s/g, "");
  }
});
  });
</script>
<script>
  (function () {
    var form = document.getElementById('prepareForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      // stop the default submit first
      e.preventDefault();

      // one confirmation only: SweetAlert2 if present, else window.confirm
      function reallySubmit() {
        // now submit for real -> Hygiene::prepare (which should redirect to view_attendances)
        form.submit();
      }

      if (window.Swal && Swal.fire) {
        Swal.fire({
          icon: 'question',
          title: 'Are you sure you want to prepare class attendance?',
          showCancelButton: true,
          confirmButtonText: 'Yes',
          cancelButtonText: 'No'
        }).then(function (res) {
          if (res.isConfirmed) reallySubmit();
          // else do nothing: modal stays open
        });
      } else {
        if (window.confirm('Are you sure you want to prepare class attendance?')) {
          reallySubmit();
        }
        // else do nothing: modal stays open
      }
    });
  })();
</script>

<script src="<?= base_url('assets/js/jquery.min.js'); ?>"></script>
<script src="<?= base_url('assets/js/bootstrap.bundle.min.js'); ?>"></script>