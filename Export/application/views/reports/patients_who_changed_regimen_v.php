
<div id="wrapperd">
			
	<div id="patient_enrolled_content" class="full-content">
		<?php $this->load->view("reports/reports_top_menus_v") ?>
		<h4 style="text-align: center" id='report_title'>Listing of Active Patients Who Have Changed Regimens In The Period Between <span class="green"><?php echo $from; ?></span> And <span class="green"><?php echo $to; ?></span></h4>
		<hr size="1" style="width:80%">
		<table align='center'  width='20%' style="font-size:16px; margin-bottom: 20px">
			<tr>
				<td colspan="2"><h5 class="report_title" style="text-align:center;font-size:14px;">Number of patients: <span id="whole_total"><?php echo $total; ?></span></h5></td>
			</tr>
		</table>
		<table  id="patient_listing" border="1" class="dataTables">
			<thead >
				<tr>
					<th> From Regimen </th>
					<th> To Regimen </th>
					<th> ART No </th>
					<th> Name </th>
					<th> Service </th>
					<th> Date of Change </th>
					<th> Change Reaon</th>
				</tr>
			</thead>
			<tbody>
				<?php 
				foreach ($patients as $patient) {
				?>
				<tr><td><?php echo $patient['from_regimen']?></td><td><?php echo $patient['to_regimen']?></td><td><?php echo $patient['art_no']?></td><td><?php echo strtoupper($patient['full_name']);?></td><td><?php echo $patient['service_type']?></td><td><?php echo date('d-M-Y',strtotime($patient['dispensing_date']));?></td><td><?php echo $patient['regimen_change_reason']?></td></tr>
				<?php	
				}
				?>
			</tbody>
		</table>
		
	</div>
</div>
