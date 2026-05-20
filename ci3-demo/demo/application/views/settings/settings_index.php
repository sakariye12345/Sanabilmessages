<?php $this->load->view('/inc/settings_header'); ?>
<!--
<?php //include ("inc/add_location.php") ?>
-->
<?php include ("inc/user_accessibility.php") ?>
<?php include ("inc/change_password.php") ?>
<?php include ("inc/add_user.php") ?>

<!-- NEW: Academic year modal include -->
<?php include("inc/academic_year_modal.php"); ?>

<div class="container" style="width:100%">
    <br>
    <?php if($this->session->flashdata('danger')): ?>
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?php echo $this->session->flashdata('danger'); ?>
        </div>
    <?php elseif($this->session->flashdata('error')): ?>
        <div class="alert alert-error alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?php echo $this->session->flashdata('error'); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col">
            <div class="card" style="box-shadow:0 0 5px 0 lightgrey;">
                <div class="card-body">
                    <h4 class="card-title">Actions</h4>

                    <a style="background-color:#e2e6ea"
                       href="#"
                       data-toggle="modal"
                       data-target="#add_user_modal"
                       class="btn btn-light text-dark btn-lg btn-block rounded-pill">
                        Add New User
                    </a>

                    <a href="#"
                       data-toggle="modal"
                       data-target="#change_password_modal"
                       style="background-color:#e2e6ea"
                       type="button"
                       class="btn btn-light text-dark btn-lg btn-block rounded-pill">
                        Change User Password
                    </a>

                    <a href="#"
                       data-toggle="modal"
                       data-target="#accessibility_modal"
                       style="background-color:#e2e6ea"
                       type="button"
                       class="btn btn-light text-dark btn-lg btn-block rounded-pill">
                        Configure Accessibility
                    </a>

                    <!-- NEW BUTTON: Academic Year Modal -->
                    <a href="#"
                       data-toggle="modal"
                       data-target="#academic_year_modal"
                       style="background-color:#e2e6ea"
                       type="button"
                       class="btn btn-light text-dark btn-lg btn-block rounded-pill">
                        Configure Academic Year
                    </a>

                </div>
            </div>
        </div>

        <div class="col">
            <div class="card" style="box-shadow:0 0 5px 0 lightgrey;">
                <div class="card-body">
                    <h4 class="card-title">View</h4>

                    <a href="<?php echo base_url('settings/users'); ?>"
                       style="background-color:#e2e6ea"
                       type="button"
                       class="btn btn-light text-dark btn-lg btn-block rounded-pill">
                        Users
                    </a>

                    <a href="<?php echo base_url('settings/accessibility_users'); ?>"
                       style="background-color:#e2e6ea"
                       type="button"
                       class="btn btn-light text-dark btn-lg btn-block rounded-pill">
                        Accessibility
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url('assets/inc/js/settings/settings_main.js'); ?>"></script>
<script src="<?php echo base_url('assets/inc/js/settings/settings_location.js'); ?>"></script>

<?php $this->load->view('/inc/footer'); ?>
