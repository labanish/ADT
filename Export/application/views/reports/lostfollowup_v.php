<style type="text/css">
table {
  table-layout: fixed;
}

td+td {
  white-space: nowrap;
  overflow: hidden;         /* <- this does seem to be required */
  text-overflow: ellipsis;
}
</style>

<div id="wrapperd">
			
	<div id="patient_enrolled_content" class="full-content">
		<?php $this->load->view("reports/reports_top_menus_v") ?>
		<h4 style="text-align: center" id='report_title'>Patient Lost to Followup Between <span  id="start_date"><?php echo $from;?></span> and <span id="end_date"><?php echo $to;?></span> </h4>
		<hr size="1" style="width:80%">
		<div class="table-responsive">
         <?php echo $dyn_table;?>
        </div>
	</div>
</div>
	