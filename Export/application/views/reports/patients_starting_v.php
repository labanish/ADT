
<div id="wrapperd">
			
	<div id="patient_enrolled_content" class="full-content">
		<?php $this->load->view("reports/reports_top_menus_v") ?>
		<h4 style="text-align: center" id='report_title'>Listing of Patients Starting Regimen in the Period Between <span class="green"><?php echo $from; ?></span> And <span class="green"><?php echo $to; ?></span></h4>
		<hr size="1" style="width:80%">
		<table align='center'  width='20%' style="font-size:16px; margin-bottom: 20px">
			<tr>
				<td colspan="2"><h5 class="report_title" style="text-align:center;font-size:14px;">Number of patients: <span id="whole_total"><?php echo $total; ?></span></h5></td>
			</tr>
		</table>
		<table  id="patient_listing" border='1' class="dataTables">
			<thead >
				<tr>
					<th> Patient No </th>
					<th> Patient Name </th>
					<th> Regimen </th>
				</tr>
			</thead>
			<tbody>
				<?php 
				foreach ($patients as $patient) {
				?>
				<tr><td><?php echo $patient['Patient_Id']?></td><td><?php echo $patient['First'].' '.$patient['Last'] ?></td><td><?php echo $patient['Regimen']?></td></tr>
				<?php	
				}
				?>
			</tbody>
		</table>
		
	</div>
</div>
