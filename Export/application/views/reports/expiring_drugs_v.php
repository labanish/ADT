
<div id="wrapperd">
	<div id="expiring_drugs" class="full-content">
		<?php $this->load->view("reports/reports_top_menus_v") ?>
		<h4 style="text-align: center" id='report_title'>List of Short-Dated Stocks as of <span class="_date"><?php echo date('d-M-Y') ?></span></h4>
		<hr size="1" style="width:80%">
		
		<table id="drug_table" class="dataTables" style="font-size:0.8em" border="1">
			<thead>
				<tr>
					<th style="min-width: 300px">Drug</th><th>Unit</th><th>Batch No</th><th>Expiry Date</th><th>SOH (Packs)</th><th>Days To Expiry</th>
				</tr>
			</thead>
			<tbody>
				<?php 
				foreach ($drug_details as $drug) {
					?>
					<tr><td><?php echo $drug['drug_name'] ?></td><td><?php echo $drug['drug_unit'] ?></td><td><?php echo $drug['batch'] ?></td><td><?php echo date('d-M-Y',strtotime($drug['expiry_date'])) ?></td><td><?php echo $drug['stocks_display'] ?></td><td><?php echo $drug['expired_days_display'] ?></td></tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</div>
</div>