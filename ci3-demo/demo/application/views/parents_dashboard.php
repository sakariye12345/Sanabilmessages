<?php $this->load->view('/inc/allowed_parents_header'); ?>
<div class="container" style="width:100%;">
   <div class="collapse" id="collapse_2">
      <div class="card card-body" style="width: 100%">
         <div class="form-row">
            <div class="col">
              <a class="btn btn-default btn-sm text-dark rounded-pill" href="<?php echo base_url('dashboard'); ?>"> <strong><i class="fa-solid fa-bars"></i> </strong></a> 
               <span><strong>/</strong></span>
               <a class="btn btn-default btn-sm text-dark rounded-pill" href="<?php echo base_url('parents'); ?>"> <strong>API</strong></a> 
               <span><strong>/</strong></span>
               <a onclick="location.reload();" class="btn btn-default btn-sm text-dark rounded-pill"> <strong>Allowed Parents Feed</strong></a> 
            </div>
         </div>
      </div>
   </div>
   <br>
   <title> - Allowed Parents</title>
   
   <div class="row">
      <div class="col">
         <h3>Allowed Parents Data</h3>
      </div>
      <div class="col">
         <button type="button" class="btn btn-default text-dark float-right ml-2 rounded-pill" data-toggle="collapse" data-target="#collapse_2" aria-expanded="false" aria-controls="collapseExample"> <i class="fa-solid fa-bars"></i></button>
         <button class="btn btn-sm btn-dark rounded-pill float-right ml-2" data-toggle="modal" data-target="#addModal"><i class="fa fa-plus"></i> Add Parent</button>
         <button class="btn btn-sm btn-info rounded-pill float-right ml-2" data-toggle="modal" data-target="#importModal"><i class="fa fa-upload"></i> Import CSV</button>
         <a href="<?= base_url('parents/download_template') ?>" class="btn btn-sm btn-outline-secondary rounded-pill float-right ml-2"><i class="fa fa-download"></i> Template</a>
         <a id="table_button" class="btn btn-sm btn-secondary rounded-pill float-right ml-2" target="_blank" href="<?= base_url('api/v1/parents/allowed') ?>"><small class="fa fa-code"></small>&nbsp;&nbsp; Test JSON API &nbsp;&nbsp;</a> 
      </div>
   </div>
   <br>
                
   <div class="table-responsive">
      <table id="parents_table" class="table table-striped table-bordered" style="width:100%">
         <thead>
            <tr id="table_row">
               <th style="width: 20px;">#</th>
               <th>ID</th>
               <th>Full Name</th>
               <th>Sanitized Phone</th>
               <th>Status</th>
               <th style="width: 65px;">Sync Ready</th>
               <th style="width: 80px;">Actions</th>
            </tr>
         </thead>
         <tbody>
            <?php if(!empty($parents_list)): $count = 1; ?>
               <?php foreach($parents_list as $row): ?>
                  <tr>
                     <td><?= $count++ ?></td>
                     <td><?= $row['parent_id'] ?></td>
                     <td><?= $row['parent_name'] ?></td>
                     <td><?= $row['phone_number'] ?></td>
                     <td>
                        <?php if($row['is_active']): ?>
                           <strong style="color: green;">Active</strong>
                        <?php else: ?>
                           <strong style="color: red;">Inactive</strong>
                        <?php endif; ?>
                     </td>
                     <td>
                        <?php if($row['is_active'] && strlen($row['phone_number']) >= 9): ?>
                           <a class="btn btn-success btn-sm rounded-pill text-white" disabled>Ready</a>
                        <?php else: ?>
                           <a class="btn btn-danger btn-sm rounded-pill text-white" disabled>Block</a>
                        <?php endif; ?>
                     </td>
                     <td>
                        <button class="btn btn-info btn-sm rounded-pill edit-btn" 
                                data-id="<?= $row['parent_id'] ?>" 
                                data-name="<?= $row['parent_name'] ?>" 
                                data-phone="<?= $row['phone_number'] ?>" 
                                data-status="<?= $row['is_active'] ? 'active' : 'inactive' ?>">
                           <i class="fa fa-edit"></i> Edit
                        </button>
                     </td>
                  </tr>
               <?php endforeach; ?>
            <?php endif; ?>
         </tbody>
         <tfoot>
            <tr class="bg-secondary text-light">
               <th></th>
               <th></th>
               <th></th>
               <th></th>
               <th></th>
               <th></th>
            </tr>
         </tfoot>
      </table>
   </div>

   <!-- Add Modal -->
   <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
         <div class="modal-content">
            <div class="modal-header bg-dark text-white">
               <h5 class="modal-title">Add New Parent</h5>
               <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
               </button>
            </div>
            <form id="addForm">
               <div class="modal-body">
                  <div class="form-group">
                     <label>Full Name</label>
                     <input type="text" class="form-control" name="parent_name" required>
                  </div>
                  <div class="form-group">
                     <label>Phone Number</label>
                     <input type="text" class="form-control" name="phone" required placeholder="e.g. 252634444444">
                  </div>
                  <div class="form-group">
                     <label>Status</label>
                     <select class="form-control" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                     </select>
                  </div>
               </div>
               <div class="modal-footer">
                  <button type="button" class="btn btn-secondary rounded-pill" data-dismiss="modal">Close</button>
                  <button type="submit" class="btn btn-primary rounded-pill">Add Parent</button>
               </div>
            </form>
         </div>
      </div>
   </div>

   <!-- Import Modal -->
   <div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
         <div class="modal-content">
            <div class="modal-header bg-info text-white">
               <h5 class="modal-title">Import Parents from Excel (.xlsx)</h5>
               <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
               </button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
               <div class="modal-body">
                  <p class="small text-muted">Hubi inuu file-kaagu u egyahay template-ka rasmiga ah (Excel/XLSX).</p>
                  <div class="form-group">
                     <label>Choose Excel File</label>
                     <input type="file" class="form-control" name="file" accept=".xlsx" required>
                  </div>
               </div>
               <div class="modal-footer">
                  <button type="button" class="btn btn-secondary rounded-pill" data-dismiss="modal">Close</button>
                  <button type="submit" class="btn btn-info rounded-pill">Start Import</button>
               </div>
            </form>
         </div>
      </div>
   </div>

   <!-- Edit Modal -->
   <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
         <div class="modal-content">
            <div class="modal-header bg-dark text-white">
               <h5 class="modal-title">Quick Edit Parent</h5>
               <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
               </button>
            </div>
            <form id="editForm">
               <div class="modal-body">
                  <input type="hidden" id="edit_id" name="id">
                  <div class="form-group">
                     <label>Full Name</label>
                     <input type="text" class="form-control" id="edit_name" name="parent_name" required>
                  </div>
                  <div class="form-group">
                     <label>Phone Number (e.g. 252634444444)</label>
                     <input type="text" class="form-control" id="edit_phone" name="phone" required>
                  </div>
                  <div class="form-group">
                     <label>Status</label>
                     <select class="form-control" id="edit_status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                     </select>
                  </div>
               </div>
               <div class="modal-footer">
                  <button type="button" class="btn btn-secondary rounded-pill" data-dismiss="modal">Close</button>
                  <button type="submit" class="btn btn-primary rounded-pill">Save Changes</button>
               </div>
            </form>
         </div>
      </div>
   </div>
</div>

<script type="text/javascript">
   $(document).ready(function(){
      var table = $('#parents_table').DataTable({
         "order": [],
         "scrollX": true,
         order: [0, 'asc'],
         mark: true,
         dom: 'Bfrtip',
         lengthMenu: [
            [ 10, 25, 50, 100, -1],
            ['10 rows', '25 rows', '50 rows', '100 rows', 'Show All']
         ],
         buttons: [
            'pageLength',
            {
               extend: 'copyHtml5',
               exportOptions: { columns: ':visible' }
            },
            {
               extend: 'excelHtml5',
               exportOptions: { columns: ':visible' }
            },
            {
               extend: 'csvHtml5',
               exportOptions: { columns: ':visible' }
            },
            {
               extend: 'pdfHtml5',
               exportOptions: { columns: ':visible' }
            },
            {
               extend: 'print',
               exportOptions: { columns: ':visible' }
            },
            'colvis'
         ]
      });

      // Handle Edit Button Click
      $('.edit-btn').on('click', function(){
         $('#edit_id').val($(this).data('id'));
         $('#edit_name').val($(this).data('name'));
         $('#edit_phone').val($(this).data('phone'));
         $('#edit_status').val($(this).data('status'));
         $('#editModal').modal('show');
      });

      // Handle Add Form Submission
      $('#addForm').on('submit', function(e){
         e.preventDefault();
         $.ajax({
            url: "<?= base_url('parents/add') ?>",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
               var res = JSON.parse(response);
               if(res.status == 'success') {
                  alert('Parent added successfully!');
                  location.reload();
               } else {
                  alert('Error adding parent.');
               }
            }
         });
      });

      // Handle Import Form Submission
      $('#importForm').on('submit', function(e){
         e.preventDefault();
         var formData = new FormData(this);
         $.ajax({
            url: "<?= base_url('parents/import') ?>",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
               var res = JSON.parse(response);
               if(res.status == 'success') {
                  alert('Imported ' + res.count + ' records successfully!');
                  location.reload();
               } else {
                  alert('Error importing data: ' + (res.message || 'Unknown error'));
               }
            }
         });
      });

      // Handle Edit Form Submission
      $('#editForm').on('submit', function(e){
         e.preventDefault();
         $.ajax({
            url: "<?= base_url('parents/update') ?>",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
               var res = JSON.parse(response);
               if(res.status == 'success') {
                  alert('Updated successfully!');
                  location.reload();
               } else {
                  alert('Error updating data.');
               }
            }
         });
      });
   }); 
</script>
<?php $this->load->view('/inc/footer'); ?>
