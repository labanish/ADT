<div id="wrapperd">
			
	<div id="patient_enrolled_content" class="full-content">
		<?php $this->load->view("reports/reports_top_menus_v") ?>
		<h4 style="text-align: center" id='report_title'>Patient BMI Summary Upto <span  id="start_date"><?php echo $from;?></span></h4>
		<h5 class="report_title" style="text-align: center">Number Of Patients: <span id="total_count"><?php echo number_format($overall); ?></span></h5>
		<hr size="1" style="width:80%">
         <?php echo $dyn_table;?>
	</div>
</div>
	