$(function(){
	//Patient Listing DataTables
	var oTable = $('#patient_listing').dataTable({
			        "bProcessing": true,
			         "bDestroy": true,
			        "sAjaxSource": 'patient_management/get_patients',
			        "bJQueryUI" : true,
					"sPaginationType" : "full_numbers",
					"bStateSave" : true,
					"sDom" : '<"H"T<"clear">lfr>t<"F"ip>',
					"bAutoWidth" : false,
					"bDeferRender" : true,
					"bInfo" : true,
					"aoColumnDefs": [{ "bSearchable": true, "aTargets": [0,1,3,4] }, { "bSearchable": false, "aTargets": [ "_all" ] }]
			    });

    //Filter Table
    oTable.columnFilter({ 
        aoColumns: [{ type: "text"},{ type: "text" },null,{ type: "text" },{ type: "text" },null]}
    );

    //Fade Out Message
    setTimeout(function(){
		$(".message").fadeOut("2000");
    },6000);
});

function filter(status){
	var oTable = $('#patient_listing').dataTable({
			        "bProcessing": true,
			        "bDestroy": true,
			        "sAjaxSource": status,
			        "bJQueryUI" : true,
					"sPaginationType" : "full_numbers",
					"bStateSave" : true,
					"sDom" : '<"H"T<"clear">lfr>t<"F"ip>',
					"bAutoWidth" : false,
					"bDeferRender" : true,
					"bInfo" : true,
					"aoColumnDefs": [{ "bSearchable": true, "aTargets": [0,1,3,4] }, { "bSearchable": false, "aTargets": [ "_all" ] }]
			    });

    //Filter Table
    oTable.columnFilter({ 
        aoColumns: [{ type: "text"},{ type: "text" },null,{ type: "text" },{ type: "text" },null]}
    );

    //Fade Out Message
    setTimeout(function(){
		$(".message").fadeOut("2000");
    },6000);
}

$(document).ready(function(){
		$('#btn_filter').click(function(){
			var choice = $('#filter').val();
			var url = "<?php echo base_url(); ?>";
			if(choice ==0){
				var new_url = 'patient_management/get_patients/';				
			}else if(choice==1){
				var new_url = 'patient_management/get_patients/inactive';	
			}else if(choice==2){
				var new_url = 'patient_management/get_patients/all';	
			}
			filter(new_url);
		});

	});