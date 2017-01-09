$(function() {
	//Initialize the multi select plugin:
	$('.multiselect').multiselect({
	    	includeSelectAllOption : true,
			maxHeight : 300,
			enableFiltering : true,
			filterBehavior : 'both',
			enableCaseInsensitiveFiltering : true,
			filterPlaceholder : 'Search'
	});
	
	// Validate the migration form
	$("#fmMigration1").validate({
		// Rules for form validation
		rules : {
			facility_code : {
				required : true
			},
			source_database : {
				required : true
			},
			ccc_pharmacy : {
				required : true
			}
		},

		// Messages for form validation
		messages : {
			facility_code : {
				required : 'Please select a facility'
			},
			source_database : {
				required : 'Please select a database'
			},
			ccc_pharmacy : {
				required : 'You have not selected any Store'
			}
		},

		// Do not change code below
		errorPlacement : function(error, element) {
			error.insertAfter(element.parent());
		}
	});
	
	//Facility selection filtering
	$("#facility_code").select2({
        minimumInputLength: 2,
	    ajax: {
	      url: "migration_management/getFacilities",
	      dataType: 'json',
	      data: function (term, page) {
	        return {
	          q: term
	        };
	      },
	      results: function (data, page) {
	        return { results: data };
	      }
    	}
	});
	
	$("#ccc_pharmacy").select2();
	$("#source_database").select2();
	$("#migrate_btn").attr('disabled', 'disabled');
	
	//function when database is selected,gets the target tables if match is correct
	$("#source_database").on('change', function() {
		//Remove incorrect database message from dom
		$("#incorrect_database").remove();
		
		//$("#migrate_btn").attr('disabled', 'disabled');
		var database_name = $(this).val();
		var link = 'migration_management/checkDB/' + database_name;
		$.ajax({
			url : link,
			type : 'POST',
			dataType : "json",
			success : function(data) {
				
				//append target tables
				$("#target_tables").multiselect('dataprovider', data);
				if(data != 0) {
					//$("#migrate_btn:disabled").removeAttr('disabled');
					//toastr.success('Database successfully selected!', 'Migration', opts);
				} else {
					console.log(data);
					//display error wrong database chosen
					$("<em class='invalid' id='incorrect_database'>Database selected cannot be migrated !</em>").insertAfter("#source_database");
				}
			}
		});
	});
	
	
	//Select table dropdown change
	$("#table").on('change', function() {
		$( ".no_table_selected" ).remove();//Remobe error message for table not selected from dom
		var check = checkTableSelected();
		if(check == 0){
			//$("#migrate_btn").attr('disabled', 'disabled');
			$("<em class='invalid' id='no_table_selected'>Please select at least one table to migrate !</em>").insertAfter(".btn-group");
		}
		else{
			//$("#migrate_btn:disabled").removeAttr('disabled');
		}
	});

	//validate check
	$(".validate").on('change', function() {
          var facility=$("#facility_code").val();
          var pharmacy=$("#ccc_pharmacy").val();
          var database=$("#source_database").val();
          var table=$("#table").val();
          
          if(facility !='' && pharmacy !='' && database !='' && table !=null ){
               $("#migrate_btn:disabled").removeAttr('disabled');
          }
	});
	
	
	//When Start Migration button clicked
	$( "#fmMigration" ).submit(function( event ) {

    $("#overall_progress_bar").addClass("progress-striped");
    $("#migrate_table_result_holder").empty();//Clear list of tables
    //$("#migrate_btn").attr('disabled', 'disabled'); //Disable button
		
	//Reset progress bar
	$("#migration_overall_progress").text('0%');
	$("#migration_overall_progress").attr("aria-valuenow",'0');
	$("#migration_overall_progress").css("width",'0%');		

		
	 $( "#no_table_selected" ).remove();//Remobe error message for table not selected from dom
	 $( "#no_ccc_pharmacy" ).remove();//Remobe error message for table not selected from dom
	  event.preventDefault();
	  var selectedTables = checkTableSelected();
	  
	  if(selectedTables!=0){//If table validation works, meaning at least one tabe, submit data
	  	var btn = $("#migration_btn");
	    btn.button('Migrating ...');
	    
	    var selectedPharmacy = $.trim($('#ccc_pharmacy').val());//Check if ccc pharmacy was entered or selected
	    
	    var facility_code = $("#facility_code").val();
		var selected_ccc_pharmacy = selectedPharmacy;
		var database_name = $("#source_database").val();
		$("#migrate_btn").attr('disabled', 'disabled'); //Disable button
		//If everything is ok, start migration
		startMigration(facility_code,selected_ccc_pharmacy,database_name,selectedTables);
	    
		
	  }
	  else{
	  	$("<em class='invalid' id='no_table_selected'>Please select at least one table to migrate !</em>").insertAfter(".btn-group");
	  }
	  
	});
	
});

function checkTableSelected(){//Function to check if database tables to be migrated were selected
	//Variable to check if all database tables are selected, true if all are selected, false if not
	  var allSelected = $("#fmMigration #table option:not(:selected)").length == 0;
	  var check = 0;
	  var selectedTables = $('#table').val();
	  if(allSelected){//If all database tables are selected
	  	check = selectedTables;
	  }else{
	  	 
	  	if(selectedTables==null){//If no table was selected
	  		check = 0;
	  	}else{//Is some tables were selected
	  		check = selectedTables;
	  	}
	  }
	  
	  return check;
}


//function to start migration
function startMigration(facility_code,ccc_pharmacy,database,selected_tables) {
	//get overall total table selected
	var overall_total = selected_tables.length;
	
	var table_counter = 0;
	var overall_percentage = 0;
	//check if multi-select lable is present and remove it from the count
	if($.inArray("multiselect-all", selected_tables) > -1) {
		overall_total = overall_total - 1;
	}
	//exclude select all
	getCurrentTable(table_counter,selected_tables,overall_total,facility_code,ccc_pharmacy,database,0);
}


//Function to check current table being migrated and its index
function getCurrentTable(table_counter,selected_tables,overall_total,facility_code,ccc_pharmacy,database,migrated_tables){
	var index = table_counter;
	var current_table = selected_tables[index];
	if(current_table == "multiselect-all") { //If select all option is selected, increment the index
		table_counter++;
		getCurrentTable(table_counter,selected_tables,overall_total,facility_code,ccc_pharmacy,database,0);
		return;
		
	}else{
		//Check Migration Log
		migrate(current_table, table_counter, overall_total,facility_code,ccc_pharmacy,database,selected_tables,migrated_tables);
		//return;
	}
	
}

//function to start migration
function migrate(source_table, table_counter, overall_total, facility_code,ccc_pharmacy,database,selected_tables,migrated_tables) {
	
	var link = 'migration_management/migrate';
	$.ajax({
		url : link,
		type : 'POST',
		dataType : "json",
		data : {
			"facility_code" : facility_code,
			"ccc_pharmacy" : ccc_pharmacy,
			"source_database" : database,
			"table" : source_table
		},
		success : function(data) {
			//alert(migrated_tables)
			var current_table = data.current_table;//Table that has just been migrated
			var source_table = data.source_table;
			var count = data.count; //Total number of migrated data
			var total = data.total; //Total data to be migrated
			var message = data.message; 
			
			
			//Calculate table migration progress
			var table_migration_progress = (count/total) *100;
			table_migration_progress = Math.round(table_migration_progress);
			var width_table_migration_progress = table_migration_progress+"%";
			console.log('table: '+current_table+', '+count+' - '+total+' --->'+data.limit);
			
			//If count is not equal to total( table has not finishhed migrating), run migration for same table
			if(parseInt(count)<parseInt(total)){
				//Display row when table starts migration
				if($("#"+current_table).length==0){
					$("#migrate_table_result_holder").append(
						'<div class="ticket migration_notification_holder" id="'+current_table+'"></div>'
					);
				}
				
				
				//Table progress bar
				$("#"+current_table+"").html(
					'<span title="" class="ticket-title">'+source_table+'</span>'+
					'<div class="progress  ticket-label" style="width: 30%">'+
					  '<div class="bar" style="width:'+width_table_migration_progress+'">'+
					    '<span >'+width_table_migration_progress+' Complete</span>'+
					  '</div>'+
					'</div>'
				)
				
					getCurrentTable(table_counter,selected_tables,overall_total,facility_code,ccc_pharmacy,database,migrated_tables);
			
				
			}
			else{//Check if current table has finished migration
				$("#"+current_table+"").remove();
				migrated_tables++;
				var overall_progress = (migrated_tables/overall_total) *100; //Overall percentage
				overall_progress = Math.round(overall_progress); 
				width_overall = overall_progress+"%";
				//Update overall progress bar
				$("#migration_overall_progress").text(width_overall);
				$("#migration_overall_progress").attr("aria-valuenow",overall_progress);
				$("#migration_overall_progress").css("width",width_overall);
				
				var msg_failed = 'No data is present at source table';
				var msg_success = 'Success:Data migrated';
				var msg_already_migrated = 'All data is already migrated';
				var msg_color ='';
				if(message.indexOf(msg_success) != -1){
				    msg_color='success';
				}else if(message.indexOf(msg_failed) != -1){
				    msg_color='error';
				}else if(message.indexOf(msg_already_migrated) != -1){
				    msg_color='warning';
				}
				
				
				$("#migrate_table_result_holder").append(
					'<div class="ticket migration_notification_holder" id="'+current_table+'">'+
						'<span title="" class="ticket-title">'+source_table+'</span>'+
						'<span class="label label-'+msg_color+' ticket-label">'+message+'</span>'+
					'</div>'
				)
				//Check if all the tables have been migrated, Migration finished
				if(migrated_tables==overall_total){
					$("#overall_progress_bar").removeClass("progress-striped");
					$("#migrate_btn").removeAttr('disabled');//Enable migration button
					$('#migration_complete_msg').html(
					'<div class="alert alert-info alert-dismissable">'+
					  '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'+
					  '<strong ><i class="fa fa-check"></i> Migration complete! '+
					'</div>');
					return;
				}
				else{//If all the tables have not yet been migrated, continue looping by incrementing the counter
					table_counter++;
					//migrated_table++;
					getCurrentTable(table_counter,selected_tables,overall_total,facility_code,ccc_pharmacy,database,migrated_tables);
				}
				
				
			}
			
		}
	});
}

