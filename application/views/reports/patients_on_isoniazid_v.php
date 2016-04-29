<div id="wrapperd">
			
	<div id="patient_enrolled_content" class="full-content">
		<?php $this->load->view("reports/reports_top_menus_v") ?>
		<h4 style="text-align: center" id='report_title'>Listing of Patients on Drug Prophylaxis Between <span id="start_date"><?php echo $from; ?></span> And <span id="end_date"><?php echo $to; ?></span></h4>
		<hr size="1" style="width:80%">
         <table align='center'  width='50%' style="margin-bottom: 10px">
			<tr>
				<td colspan="3"><h5 class="report_title" style="text-align:center;font-size:14px;">Number of patients: 
				<span id="total_count"><?php echo $all_count;?></span></h5></td>
			</tr>
			<!--<tr style="text-align: center">
				<td width='10%'><h5 class="report_title" style="text-align: center; display:inline;color:green;">Visited: <span id="total_visited_count"><?php echo $visited;?></span></h5></td><td width='10%'><h5 class="report_title" style="text-align: center;display:inline; color:red;">Not Visited: <span id="total_not_visited_count"><?php echo $not_visited;?></span></h5></td><td width='10%'><h5 class="report_title" style="text-align: center;display:inline; color:blue;">Visited Later: <span id="total_visted_later_count"><?php echo $visited_later;?></span></h5></td>
			</tr>-->
		</table>
         <?php echo $dyn_table;?>
	</div>
</div>
