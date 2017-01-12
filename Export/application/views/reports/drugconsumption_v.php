<script type="text/javascript">
	
	$(document).ready( function () {
		
		var stock_type=<?php echo $stock_type; ?>;//Year
		var base_url='<?php echo $base_url ?>';
		var _url=<?php echo "'".$base_url."report_management/drug_consumption/".$stock_type."/".$pack_unit."'"; ?>;
		var report_title=$("#report_title").text();
		var facility=$("#facility_name").text();
		$('#drug_table').dataTable({
			"oTableTools" : {
			"sSwfPath" : "<?php echo base_url() ?>assets/scripts/datatable/copy_csv_xls_pdf.swf",
			"aButtons" : ["copy", 
			{
				"sExtends" : "xls",
				"sTitle" : report_title+" ("+facility+")",
			},  
			{
				"sExtends" : "pdf",
				"sPdfOrientation" : "landscape",
				"sPdfSize" : "A3",
				"sTitle" : report_title+" ("+facility+")",
			}]
		},
			"sDom" : '<"H"<"clear">lfrT>t<"F"ip>',
			"bProcessing": true,
			"bServerSide": true,
			"sAjaxSource": _url,
	        "bJQueryUI": true,
	        "sScrollX" : "100%",
			"bScrollCollapse" : true,
			"bDestroy" : true,
			"iDisplayLength": 10,
    		"aLengthMenu": [[10, 25, 50,100, -1], [10, 25, 50, 100, "All"]],
	        "aoColumnDefs": [
          	{'bSortable': false, 'aTargets': [ 1,2,3,4,5,6,7,8,9,10,11,12,13] }
    		],
	        "sPaginationType": "full_numbers"
		});
		
	});

</script>
<div id="wrapperd">
	<div id="drug_consumption" class="full-content">
		<?php $this->load->view("reports/reports_top_menus_v") ?>
		<h4 style="text-align: center;" id="report_title">Listing of Drug Consumption Report for <span class="_date" id="_year"><?php echo @$year . '  (' .strtoupper($pack_unit).')' ?></span> </h4>
		<hr size="1" style="width:80%">
		
		<table id="drug_table" class="table table-bordered table-striped dataTables " style="font-size:0.8em">
			<thead>
				<tr>
					<th style="width:30% !important">Drug</th><th>Unit</th><th>Jan</th><th>Feb</th><th>Mar</th><th>Apr</th><th>May</th><th>Jun</th><th>Jul</th><th>Aug</th><th>Sep</th><th>Oct</th><th>Nov</th><th>Dec</th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
</div>	