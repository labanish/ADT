<!--Custom CSS files-->
<link href="<?php echo base_url().'assets/modules/patients/listing.css'; ?>" type="text/css" rel="stylesheet"/>

<!--container-->
<div class="container-fluid center-content">
    <!--message row-->
    <div class="row-fluid">
        <div class="span12">
            <?php 
			  if($this->session->userdata("msg_save_transaction")){
			  ?>
				<?php
				if($this->session->userdata("msg_save_transaction")=="success"){
					?>
				<div class="alert alert-success">
	              <button type="button" class="close" data-dismiss="alert">&times;</button>
				    <?php echo $this->session->userdata("user_enabled");  ?>
				    <?php echo $this->session->flashdata("dispense_updated");  ?>
				</div> 	
					<?php
				}
				else{
					?>
				  <div class="alert alert-success">
	               <button type="button" class="close" data-dismiss="alert">&times;</button>
				     Your data were not saved ! Try again or contact your system administrator.
				   </div> 	
				<?php
				}
				$this->session->unset_userdata('msg_save_transaction');
			  }
			?>
        </div>
    </div>

    <h3 style="margin-top: 0;">
    	<select id="filter">
    		<option value="0">Active</option>
    		<option value="1">Inactive</option>
    		<option value="2">All</option>
    	</select>

    	<button id="btn_filter">Filter</button>

    </h3>

    <!--table row-->
    <div class="row-fluid" style="margin-top: 0;">
        <div class="span12">
            <div class="table-responsive">
                <table class="table table-bordered table-condensed table-hover" id="patient_listing">
                    <thead>
                        <tr>
							<th>CCC No</th>
							<th>Patient Name</th>
							<th>Next Appointment</th>
							<th>Contact</th>
							<th>Current Regimen</th>
							<th>Status</th>
							<th>Action</th>
						</tr>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
	                    <tr>
							<th>CCC No</th>
							<th>Patient Name</th>
							<th>Next Appointment</th>
							<th>Contact</th>
							<th>Current Regimen</th>
							<th>Status</th>
							<th>Action</th>
						</tr>
                    </tfoot>    	
                </table>
            </div>
        </div>
    </div>
</div>

<!--custom js-->
<script src="./assets/modules/patients/listing.js"></script>

