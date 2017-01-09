<style type="text/css">
  .graph{
  	height:auto !important;
  }
</style>
<div class="full-content container">
	<div class="row-fluid">
		<?php $this->load->view("reports/reports_top_menus_v");?>
	</div>
	<div class="row-fluid">
		<div id="chartdiv" class="span12">
			<?php echo $graphs; ?>
		</div>
	</div>
</div>

