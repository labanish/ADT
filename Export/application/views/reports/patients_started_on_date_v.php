<style>
	
	#patient_listing td th {
		padding: 10px;
	}
	#patient_listing td {
		padding: 0.25em;
		text-align: left;
	}
	select.flt {
		font-size: 14px;
	}
	h5 {
		margin: 10px;
	}
	.report_title {
		color: rgb(34, 86, 253);
		letter-spacing: 1px;
	}
	h2 {
		margin: 0.5em;
	}
	h4 {
		font-size: 18px;
	}
	.odd {
		background-color: rgb(226, 232, 255);
	}
	
	#date_of_starting {
		color: rgb(45, 173, 13);
		font-size: 14px;
		border: none;
		font-weight: 800;
		width: 110px;
		padding-bottom: 0px;
		height: 25px;
	}
	#date_of_starting:hover {
		cursor: pointer;
	}
</style>

<div id="wrapperd">
			
	<div id="patient_enrolled_content" class="full-content">
		<?php $this->load->view("reports/reports_top_menus_v") ?>
		<h4 style="text-align: center" id='report_title'>Listing of Patients Who Started Between <span class="_date" id="start_date"><?php echo $from;?></span> And <span class="_date" id="end_date"><?php echo $to;?></span></h4>
		<hr size="1" style="width:80%">
		<table align='center'  width='20%' style="font-size:16px; margin-bottom: 20px">
			<tr>
				<td colspan="2"><h5 class="report_title" style="text-align:center;font-size:14px;">Number of patients: <span id="total_count"><?php echo $all_count; ?></span></h5></td>
			</tr>
		</table>
		<div id="appointment_list">
			<?php echo $dyn_table;?>
		</div>
	</div>
</div>
