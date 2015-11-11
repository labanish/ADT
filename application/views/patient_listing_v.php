<style type="text/css">
	.dataTable {
		letter-spacing:0px;
	}
	table.dataTable{
	    zoom:0.85;	
	}
	.table-bordered input {
		width:8em;
	}
	td {
	  white-space: nowrap;
	  overflow: hidden;         /* <- this does seem to be required */
	  text-overflow: ellipsis;
	}
	
</style>
<script type="text/javascript">
	
	$(document).ready(function(){
		var oTable =$('.listing_table').dataTable({
				"bJQueryUI" : true,
				"sPaginationType" : "full_numbers",
				"bStateSave" : true,
				"sDom" : '<"H"T<"clear">lfr>t<"F"ip>',
				"bProcessing" : true,
				"bServerSide" : true,
				"bAutoWidth" : false,
				"bDeferRender" : true,
				"bInfo" : true,
				"sAjaxSource": "patient_management/listing",
				"aoColumnDefs": [ { "bSearchable": true, "aTargets": [0,1,3,4] }, { "bSearchable": false, "aTargets": [ "_all" ] } ]
            });
		 setTimeout(function(){
			$(".message").fadeOut("2000");
		 },6000);
         oTable.fnSort([[2,'desc']]);
         oTable.columnFilter({ 
         /*	sPlaceHolder: "head:after",*/
         	aoColumns: [{ type: "text"},{ type: "text" },null,{ type: "text" },{ type: "text" },null]}
         );
         
         $(".listing_table").wrap('<div class="dataTables_scroll" />');
	});
  function notActive()
	  	{
       		bootbox.alert("<h4>Status Not Active</h4>\n\<hr/><center>Cannot Dispense to Patient</center>");
		}
</script>
<?php
$access_level = $this -> session -> userdata('user_indicator');
$user_is_administrator = false;
$user_is_nascop = false;
$user_is_pharmacist = false;
$user_is_facilityadmin = false;

if ($access_level == "system_administrator") {
	$user_is_administrator = true;
}
if ($access_level == "pharmacist") {
	$user_is_pharmacist = true;

}
if ($access_level == "nascop_staff") {
	$user_is_nascop = true;
}
if ($access_level == "facility_administrator") {
	$user_is_facilityadmin = true;
}
?>



<?php 
//COunt number of patients
?>
<div class="main-content">
	<div class="center-content">
		<div>
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
		<div class="table-responsive">
		<table class="listing_table table table-bordered table-hover table-condensed" id="patient_listing1" >
			<thead>
				<tr>
					<th >CCC No</th>
					<th style="width:19%">Patient Name</th>
					<th>Next Appointment</th>
					<th>Contact</th>
					<th style="width:20%">Current Regimen</th>
					<th>Status</th>
					<th style="width:18%">Action</th>
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
