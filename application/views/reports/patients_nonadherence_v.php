
<div  class="full-content">
	<?php $this->load->view("reports/reports_top_menus_v")
	?>
	<h4 style="text-align: center"><?php echo $report_title;?>
	<br>
	<br>
	Between
	<input type="text" id="start_date" value="<?php echo $from; ?>">
	and
	<input type="text" id="end_date" value="<?php echo $to; ?>">
	</h4>
	<hr size="1" style="width:80%">
	<?php echo $dyn_table;?>
</div>