function getPageData(url){
	//Get JSON data for patient details page
	return  $.getJSON( url ,function( resp ) {
			    $.each( resp, function( column , elements ) {
		    	var text = "<option value=''>--Select--</option>";
		    	$.each( elements, function( key , value ) {
		    		text += "<option value='" + value.id +"'>" + value.Name + "</option>";    	
		        });
		        //Append html elements to DOM
		        $( "#"+column ).html( text );
		    });
		});
}

function createTable(div,url,sortIndex,sortOrder){
	var oTable =$(div).dataTable({
					"bProcessing": true,
			        "sAjaxSource": url,
			        "bJQueryUI" : true,
					"sPaginationType" : "full_numbers",
					"bStateSave" : true,
					"sDom" : '<"H"T<"clear">lfr>t<"F"ip>',
					"bAutoWidth" : false,
					"bDeferRender" : true,
					"bInfo" : true,
					"aLengthMenu":[10,25,50,100]
					});

	//Sort Table
	sortTable(oTable,sortIndex,sortOrder);
}

function sortTable(table,column,order){
	table.fnSort([[column,order]]);
}
