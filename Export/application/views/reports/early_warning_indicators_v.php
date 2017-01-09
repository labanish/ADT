<div id="wrapperd">
			
	<div id="patient_enrolled_content" class="full-content">
		<?php $this->load->view("reports/reports_top_menus_v") ?>
		<h4 style="text-align: center">Listing of HIV Drugs Resistance Early Warning Indicators Between <span class="green"><?php echo $from; ?></span> And <span class="green"><?php echo $to; ?></span></h4>
		<hr size="1" style="width:80%">
		<div class="patient_percentage">
			<h3 style="text-align: center;margin:0 auto;">Percentage of Patients Started on First Line.Suggested Target 100%</h3>
			<table class="listing_table" id="percentage_patients" cellspacing="5" border='1'>
				<tr>
					<th> Patients Initiated on First Line </th>
					<th> Total Patients Starting ART </th>
					<th> Percentage Started on First Line(%)</th>
					<th> Percentage Started on other Regimens(%)</th>
				</tr>
				<tr><td align="center"><?php echo $first_line ?></td><td align="center"><?php echo $tot_patients ?></td><td align="center"><?php echo number_format($percentage_firstline,1); ?></td><td align="center"><?php echo number_format($percentage_onotherline,1); ?></td></tr>
			</table>
		</div>
		<div class="retention_percentage">
			<h3 style="text-align: center;margin:0 auto;">Patients Retention on First Line ART.Suggested Target >70%</h3>
			<table class="listing_table" id="percentage_retention" cellpadding="5" border="1">
				<tr>
					<th> Patients Still in First Line </th>
					<th> Total Patients Starting 12 months from selected period </th>
					<th> Percentage(%) Patients Retained in First Line</th>
				</tr>
				<tr><td align="center"><?php echo $stil_in_first_line; ?></td><td align="center"><?php echo $total_from_period;?></td><td align="center"><?php echo $percentage_stillfirstline ?></td></tr>
			</table>
		</div>
		<div class="lost_to_follow_up_percentage" cellpadding="5">
			<h3 style="text-align: center;margin:0 auto;">Cohort of Patients Lost to follow up(Same Period Last Year).Suggested Target < 20%</h3>
			<table class="listing_table" id="percentage_lost_to_follow_up" border="1">
				<tr>
					<th> No. of Patients Lost to Follow Up </th>
					<th> Total Patients Started on ART in the selected period </th>
					<th> Percentage(%) of Patients Lost to follow Up</th>
				</tr>
				<tr><td align="center"><?php echo $lost_to_follow ?></td><td align="center"><?php echo $total_before_period ?></td><td align="center"><?php echo $percentage_lost_to_follow ?></td></tr>
			</table>
		</div>
		
	</div>
</div>
