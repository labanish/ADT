<div id="wrapperd">			
	<div id="patient_enrolled_content" class="full-content">
		<?php $this->load->view("reports/reports_top_menus_v") ?>
		<h4 style="text-align: center" id='report_title'>Patient Status &amp; Disclosure Between <span  id="start_date"><?php echo $from;?></span> and <span  id="start_date"><?php echo $to;?></span></h4>
		<hr size="1" style="width:80%">
         <div id='disclosure_chart'></div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function(){	
		$("#disclosure_chart").load("<?php echo base_url().'report_management/disclosure_chart/'.$from.'/'.$to ?>")
	
	});
</script>

	