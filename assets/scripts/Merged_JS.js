$(document).ready(function() {
	setTimeout(function() {
		$(".message,.alert").fadeOut("20000");
	}, 60000);
	$(".error").css("display", "block");

	$(".actual").on('click',function(e) { 
            var parentForm = $(".actual").closest("form").attr("name");
            e.preventDefault(); 
            if(processData(parentForm)){
              bootbox.confirm("<h4>Save</h4>\n\<hr/><center>Are you sure?</center>",
                function(res){
                    if(res===true){
                      $("#"+parentForm).submit();
	                    if(parentForm == "fmPostCdrr")
	                    {
	                        $(".btn").attr("disabled","disabled");
	                    }
                    }
                    else{
                    	$(this).removeAttr("disabled");
                    }
                });
            }
	});
	/*Ensure Correct Phone format is used*/
	$(".phone").change(function() {
		var phone = $(this).val();
		var phone_length = phone.length;
		var number_length = 10;
		/*
		 * 1.Check Number Length
		 * 2.If yes,check if first characters are 07{
		 * 3.if matches 07 alert successful
		 * 4.if no match alert your phone number should start with 07}
		 * 5.if no,alert incorrect phone format used
		 */
		if(phone_length == number_length) {
			var first_char = phone.substr(0, 2);
			if(first_char != 07) {
				alert("your phone number should start with 07");
			}
		} else {
			alert("incorrect phone format used");
		}
	});
	//Progress Bar
	function progress(percent, $element) {
		var progressBarWidth = percent * $element.width() / 100;
		$element.find('.bar').animate({
			width : progressBarWidth
		}, 500).html(percent + "%&nbsp;");

	}

	var base_url = $("#base_url").val();
	var report_title = $("#report_title").text();
	var facility = $("#facility_name").text();
	var oTable = $('.dataTables').dataTable({
		"bJQueryUI" : true,
		"sPaginationType" : "full_numbers",
		"bStateSave" : true,
		"sDom" : '<"H"<"clear">lfrT>t<"F"ip>',
		"oTableTools" : {
			"sSwfPath" : base_url + "assets/scripts/datatable/copy_csv_xls_pdf.swf",
			"aButtons" : ["copy", {
				"sExtends" : "xls",
				"sTitle" : report_title + " (" + facility + ")",
			}, {
				"sExtends" : "pdf",
				"sPdfOrientation" : "landscape",
				"sPdfSize" : "A3",
				"sTitle" : report_title + " (" + facility + ")",
			}]
		},
		"bProcessing" : true,
		"bServerSide" : false,
		"bAutoWidth" : true,
		"bDeferRender" : true,
		"bInfo" : true,
		"iDisplayLength": 10,
    	"aLengthMenu": [[10, 25, 50,100, -1], [10, 25, 50, 100, "All"]],
		"bScrollCollapse" : true,
		"bDestroy" : true,
		"fnInitComplete": function() {
	        this.css("visibility", "visible");
	    }
	});
	$('.dataTables tbody td').live('mouseover', function() {//Show full text when one mouseovers
		var sTitle;
		sTitle = jQuery(this).text();
		this.setAttribute( 'title', sTitle );
	});
	$('.dataTable tbody td').live('mouseover', function() {//Show full text when one mouseovers
		var sTitle;
		sTitle = jQuery(this).text();
		this.setAttribute( 'title', sTitle );
	});
	
	$('.listing_table tbody td').live('mouseover', function() {//Show full text when one mouseovers
		var sTitle;
		sTitle = jQuery(this).text();
		this.setAttribute( 'title', sTitle );
	});
	
	$(".patient_table").wrap('<div class="dataTables_scroll" />');//Alignment
	
	var base_url = $("#base_url").val();
	$("#change_password_link").click(function() {
		$("#old_password").attr("value", "");
		$("#new_password").attr("value", "");
		$("#new_password_confirm").attr("value", "");
		$(".error").html("");
		$(".error").css("display", "none");
		$("#result").html("");
	});

	$(".error").css("display", "none");
	$('#new_password').keyup(function() {
		//$('#result').html(checkStrength($('#new_password').val()));
	});
	function checkStrength(password) {

		//initial strength
		var strength = 0;

		//if the password length is less than 6, return message.
		if(password.length < 6) {
			$('#result').removeClass();
			$('#result').addClass('short');
			return 'Too short';
		}

		//length is ok, lets continue.

		//if length is 8 characters or more, increase strength value
		if(password.length > 7)
			strength += 1;
		if(password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/))
			strength += 1;
		if(password.match(/([a-zA-Z])/) && password.match(/([0-9])/))
			strength += 1;
		if(password.match(/([!,%,&,@,#,$,^,*,?,_,~])/))
			strength += 1;
		if(password.match(/(.*[!,%,&,@,#,$,^,*,?,_,~].*[!,",%,&,@,#,$,^,*,?,_,~])/))
			strength += 1;
		if(strength < 2) {
			$('#result').removeClass();
			$('#result').addClass('weak');
			return 'Weak';
		} else if(strength == 2) {
			$('#result').removeClass();
			$('#result').addClass('good');
			return 'Good';
		} else {
			$('#result').removeClass();
			$('#result').addClass('strong');
			return 'Strong';
		}
	}

  
	$("#btn_submit_change_pass").click(function(event) {
		var base_url = $("#base_url").val();
		$(".error").css("display", "none");
		$('#result_confirm').html("");
		event.preventDefault();
		var old_password = $("#old_password").attr("value");
		var new_password = $("#new_password").attr("value");
		var new_password_confirm = $("#new_password_confirm").attr("value");

		if(new_password == "" || new_password_confirm == "" || old_password == "") {
			$(".error").css("display", "block");
			$("#error_msg_change_pass").html("All fields are required !");
		} else if($('#new_password').val().length < 3) {
			$(".error").css("display", "block");
			$("#error_msg_change_pass").html("Your password must have more than 2 characters!");
		} else if($("#result").attr("class") == "weak") {
			$(".error").css("display", "block");
			$("#error_msg_change_pass").html("Please enter a strong password!");
		} else if(new_password != new_password_confirm) {
			$(".error").css("display", "block");
			$('#result_confirm').removeClass();
			$('#result_confirm').addClass('short');
			$("#error_msg_change_pass").html("You passwords do not match !");
		} else {
			$(".error").css("display", "none");
			$("#m_loadingDiv").css("display", "block");
			//$("#fmChangePassword").submit();
			var _url = base_url + "user_management/save_new_password/2";
			var request = $.ajax({
				url : _url,
				type : 'post',
				data : {
					"old_password" : old_password,
					"new_password" : new_password
				},
				dataType : "json"
			});
			request.done(function(data) {
				$("#m_loadingDiv").css("display", "none");
				$.each(data, function(key, value) {
					if(value == "password_no_exist") {
						$("#error_msg_change_pass").css("display", "block");
						$("#error_msg_change_pass").html("You entered a wrong password!");
					} else if(value == "password_exist") {
						$("#error_msg_change_pass").css("display", "block");
						$("#error_msg_change_pass").html("Your new password matches one of your three pevious passwords!");
					} else if(value == "password_changed") {
						$("#error_msg_change_pass").css("display", "block");
						$("#error_msg_change_pass").removeClass("error");
						$("#error_msg_change_pass").addClass("success");
						$("#error_msg_change_pass").html("Your password was successfully updated!");
						window.setTimeout('location.reload()', 3000);
					} else {
						alert(value);
					}
				});
			});
			request.fail(function(jqXHR, textStatus) {
				alert("An error occured while updating your password : " + textStatus + ". Please try again or contact your system administrator!");
			});
		}
	});
	$.extend($.gritter.options, {
		position : 'bottom-right', // defaults to 'top-right' but can be 'bottom-left', 'bottom-right', 'top-left', 'top-right' (added in 1.7.1)
		fade_in_speed : 'medium', // how fast notifications fade in (string or int)
		fade_out_speed : 2000, // how fast the notices fade out
		time : 6000 // hang on the screen for...
	});

});
/**
 * End Change password validation
 */

/*
 * Auto logout
 */
var timer = 0;
function set_interval() {
	// the interval 'timer' is set as soon as the page loads
	//timer = setInterval("auto_logout()", 5100000);
	// the figure '180000' above indicates how many milliseconds the timer be set to.
	// Eg: to set it to 5 mins, calculate 3min = 3x60 = 180 sec = 180,000 millisec.
	// So set it to 180000
}

function reset_interval() {
	//resets the timer. The timer is reset on each of the below events:
	// 1. mousemove   2. mouseclick   3. key press 4. scroliing
	//first step: clear the existing timer

	if(timer != 0) {
		clearInterval(timer);
		timer = 0;
		// second step: implement the timer again
		timer = setInterval("auto_logout()", 3600000);
		// completed the reset of the timer
	}
}

function auto_logout() {
	var base_url = $("#base_url").val();
	// this function will redirect the user to the logout script
	window.location = base_url + "user_management/logout";
}

/*
* Auto logout end
*/

//Function to get data for ordering(Cdrr)
function getPeriodDrugBalance(count,start_date, facility_id, code,total,drugs,stores) {
	var base_url = getbaseurl();
	var drug = drugs[count];
	var link = base_url + 'order/getItems';
	
	$.ajax({
		url : link,
		type : 'POST',
		dataType : 'json',
		data:{
			"drug_id":drug,
			"period_begin":start_date,
			"facility_id":facility_id,
			"code":code,
			"stores":stores
		},
		success : function(data) {
			$("#opening_balance_" + drug).attr("value", data.beginning_balance);
			$("#received_in_period_" + drug).attr("value", data.received_from);
			$("#dispensed_in_period_" + drug).attr("value", data.dispensed_to_patients);
			$("#losses_in_period_" + drug).attr("value", data.losses);
			$("#positive_adjustment_" + drug).attr("value", data.positive_adjustment);
			$("#adjustments_in_period_" + drug).attr("value", data.adjustments);
			$("#physical_in_period_" + drug).attr("value", data.physical_stock);
			$("#expire_qty_" + drug).attr("value", data.expiry_qty);
			$("#expire_period_" + drug).attr("value", data.expiry_month);
			$("#out_of_stock_" + drug).attr("value", data.stock_out);
			$("#resupply_" + drug).attr("value", data.resupply);

			if(code == "F-CDRR_packs") {
				$("#dispensed_in_period_packs_" + drug).attr("value", data.dispensed_packs);
			}
			if(code == "D-CDRR") {
				$("#aggregated_qty_" + drug).attr("value", data.reported_consumed);
				$("#aggregated_physical_qty_" + drug).attr("value", data.reported_physical_stock);
			}

			//check count is equal to total(total-1)
	    	if(count==(total-1)){
	    		$.unblockUI({});
	    	}else{
	    		//increment drug counter
	    	    count++;
	    		//recursive function to continue
	    		getPeriodDrugBalance(count,start_date, facility_id, code,total,drugs,stores);
	    	}
		}
	});
}

function getExpectedActualReports(facility_code,period_start,type){
	var base_url = getbaseurl();
	var link = base_url + 'order/getExpectedActualReport';
	
	$.ajax({
		url : link,
		type : 'POST',
		dataType : 'json',
		data:{
			"period_begin":period_start,
			"facility_code":facility_code,
			"type":type,
		},
		success : function(data) {
			$("#central_rate").attr("value",data.expected);
			$("#actual_report").attr("value",data.actual);
		}
	});
}

function convertDate(stringdate) {
	// Internet Explorer does not like dashes in dates when converting,
	// so lets use a regular expression to get the year, month, and day
	var DateRegex = /([^-]*)-([^-]*)-([^-]*)/;
	var DateRegexResult = stringdate.match(DateRegex);
	var DateResult;
	var StringDateResult = "";

	// try creating a new date in a format that both Firefox and Internet Explorer understand
	try {
		DateResult = new Date(DateRegexResult[2] + "/" + DateRegexResult[3] + "/" + DateRegexResult[1]);
	}
	// if there is an error, catch it and try to set the date result using a simple conversion
	catch(err) {
		DateResult = new Date(stringdate);
	}

	var _month = DateResult.getMonth() + 1;
	if(parseInt(DateResult.getMonth() + 1) < 10) {
		_month = "0" + parseInt((DateResult.getMonth() + 1));
	}

	// format the date properly for viewing
	StringDateResult = (DateResult.getFullYear()) + "-" + _month;

	return StringDateResult;
}

//Function to get data for ordering(Maps)
function getPeriodRegimenPatients(start_date, end_date) {

	var base_url = getbaseurl();
	var link = base_url + 'order/getPeriodRegimenPatients/' + start_date + '/' + end_date;
	$.ajax({
		url : link,
		type : 'POST',
		dataType : 'json',
		success : function(data) {
			var total_patients = 0;
			var total_patients_div = "";
			$.each(data, function(i, jsondata) {
				total_patients = jsondata.patients;
				regimen_category = jsondata.regimen_category;
				regimen_category = regimen_category.toLowerCase();
				total_patients_div = "#patient_numbers_" + jsondata.regimen;
				$(total_patients_div).attr("value", total_patients);
				//Calculate total summary of ART patients
				
				if((regimen_category.indexOf('pep')>-1 || regimen_category.indexOf('pmtct')>-1)){
					
				}
				else if((regimen_category.indexOf('paed')>-1 || regimen_category.indexOf('ped')>-1 || regimen_category.indexOf('child')>-1)){//Check if regimen is adult or paed
					var old_val = $("#art_child").val();
					var new_val = parseInt(old_val)+(parseInt(total_patients));
					$("#art_child").val(new_val);
				}else if(regimen_category.indexOf('adult')>-1 || regimen_category.indexOf('mother')>-1){//Adult regimen
					var old_val = $("#art_adult").val();
					var new_val = parseInt(old_val)+(parseInt(total_patients));
					$("#art_adult").val(new_val);
				}
			});
		}
	});

}

//Function that gets aggregated data
function getAggregateFmaps(start_date, end_date) {
	var base_url = getbaseurl();
	var link = base_url + 'order/get_aggregated_fmaps/' + start_date + '/' + end_date;

	$.ajax({
		url : link,
		type : 'POST',
		dataType : 'json',
		success : function(data) {
			//Maps array
			var reports_expected = 0;
			var reports_actual = 0;
			var art_adult = 0;
			var art_child = 0;
			var new_male = 0;
			var revisit_male = 0;
			var new_female = 0;
			var revisit_female = 0;
			var new_pmtct = 0;
			var revisit_pmtct = 0;
			var total_infant = 0;
			var pep_adult = 0;
			var pep_child = 0;
			var total_adult = 0;
			var total_child = 0;
			var diflucan_adult = 0;
			var diflucan_child = 0;
			var new_cm = 0;
			var revisit_cm = 0;
			var new_oc = 0;
			var revisit_oc = 0;
			var comments = '';
			var services = '';
			var sponsors = '';
			$.each(data.maps_array, function(i, jsondata) {
				$('#reports_expected').val(data.maps_array.reports_expected);
				$('#reports_actual').val(data.maps_array.reports_actual);
				$('#art_adult').val(data.maps_array.art_adult);
				$('#art_child').val(data.maps_array.art_child);
				$('#new_male').val(data.maps_array.new_male);
				$('#revisit_male').val(data.maps_array.revisit_male);
				$('#new_female').val(data.maps_array.new_female);
				$('#revisit_female').val(data.maps_array.revisit_female);
				$('#new_pmtct').val(data.maps_array.new_pmtct);
				$('#revisit_pmtct').val(data.maps_array.revisit_pmtct);
				$('#total_infant').val(data.maps_array.total_infant);
				$('#pep_adult').val(data.maps_array.pep_adult);
				$('#pep_child').val(data.maps_array.pep_child);
				$('#total_adult').val(data.maps_array.total_adult);
				$('#total_child').val(data.maps_array.total_child);
				$('#diflucan_adult').val(data.maps_array.diflucan_adult);
				$('#diflucan_child').val(data.maps_array.diflucan_child);
				$('#new_cm').val(data.maps_array.new_cm);
				$('#revisit_cm').val(data.maps_array.revisit_cm);
				$('#new_oc').val(data.maps_array.new_oc);
				$('#revisit_oc').val(data.maps_array.revisit_oc);
				$('#other_regimen').val(data.maps_array.comments);
			});
			//Patients regimen
			$.each(data.maps_items_array, function(i, jsondata) {

				//Loop through each regimen
				var tot = jsondata.total;
				var regimen_id = jsondata.regimen_id;
				//console.log(regimen_id);
				$('#tbl_patients_regimen').find('tbody tr ').each(function() {
					var regimen_desc = $(this).find('.regimen_numbers').find('.regimen_list').val();
					//console.log(regimen_id+' - '+regimen_desc);

					if(regimen_id == regimen_desc) {
						//console.log(regimen_desc);
						$(this).find('.regimen_numbers').find('.patient_number').attr('value', tot);
					}
				});
			});
		}
	});

}

function getFacilityData(fmaps_id) {
	var base_url = getbaseurl();
	var link = base_url + 'order/get_fmaps_details/' + fmaps_id;

	$.ajax({
		url : link,
		type : 'POST',
		dataType : 'json',
		success : function(data) {
			//Maps array
			var reports_expected = 0;
			var reports_actual = 0;
			var art_adult = 0;
			var art_child = 0;
			var new_male = 0;
			var revisit_male = 0;
			var new_female = 0;
			var revisit_female = 0;
			var new_pmtct = 0;
			var revisit_pmtct = 0;
			var total_infant = 0;
			var pep_adult = 0;
			var pep_child = 0;
			var total_adult = 0;
			var total_child = 0;
			var diflucan_adult = 0;
			var diflucan_child = 0;
			var new_cm = 0;
			var revisit_cm = 0;
			var new_oc = 0;
			var revisit_oc = 0;
			var comments = '';
			var services = '';
			var sponsors = '';
			$.each(data.maps_array, function(i, jsondata) {
				$('#reports_expected').val(data.maps_array.reports_expected);
				$('#reports_actual').val(data.maps_array.reports_actual);
				$('#art_adult').val(data.maps_array.art_adult);
				$('#art_child').val(data.maps_array.art_child);
				$('#new_male').val(data.maps_array.new_male);
				$('#revisit_male').val(data.maps_array.revisit_male);
				$('#new_female').val(data.maps_array.new_female);
				$('#revisit_female').val(data.maps_array.revisit_female);
				$('#new_pmtct').val(data.maps_array.new_pmtct);
				$('#revisit_pmtct').val(data.maps_array.revisit_pmtct);
				$('#total_infant').val(data.maps_array.total_infant);
				$('#pep_adult').val(data.maps_array.pep_adult);
				$('#pep_child').val(data.maps_array.pep_child);
				$('#total_adult').val(data.maps_array.total_adult);
				$('#total_child').val(data.maps_array.total_child);
				$('#diflucan_adult').val(data.maps_array.diflucan_adult);
				$('#diflucan_child').val(data.maps_array.diflucan_child);
				$('#new_cm').val(data.maps_array.new_cm);
				$('#revisit_cm').val(data.maps_array.revisit_cm);
				$('#new_oc').val(data.maps_array.new_oc);
				$('#revisit_oc').val(data.maps_array.revisit_oc);
				$('#other_regimen').val(data.maps_array.comments);
			});
			//Patients regimen
			$.each(data.maps_items_array, function(i, jsondata) {

				//Loop through each regimen
				var tot = jsondata.total;
				var regimen_id = jsondata.regimen_id;
				var item_id = jsondata.item_id;
				//console.log(regimen_id);
				$('#tbl_patients_regimen').find('tbody tr ').each(function() {
					var regimen_desc = $(this).find('.regimen_numbers').find('.regimen_list').val();
					//console.log(regimen_id+' - '+regimen_desc);

					if(regimen_id == regimen_desc) {
						//console.log(regimen_desc);
						$(this).find('.regimen_numbers').find('.item_id').attr('value', item_id);
						$(this).find('.regimen_numbers').find('.patient_number').attr('value', tot);
					}
				});
			});
		}
	});

}

function getPercentage(count, total) {
	return (count / total) * 100;
}

function testAlert() {
	alert("OK");
}

/*
 * Sysnchronization of Orders
 */
function syncOrders() {
	var base_url = getbaseurl();
	var link = base_url + "synchronization_management/startSync";
	$.ajax({
		url : link,
		type : 'POST',
		success : function(data) {

			$.gritter.add({
				// (string | mandatory) the heading of the notification
				title : 'Synchronization.',
				// (string | mandatory) the text inside the notification
				text : data,
				// (string | optional) the image to display on the left
				// (bool | optional) if you want it to fade out on its own or just sit there
				sticky : false,
				// (int | optional) the time you want it to be alive for before fading out
				time : ''
			});

			//alert(data)
		}
	});

}

function autoUpdate() {
	var base_url = getbaseurl();
	var link = base_url + "auto_management";
	$.ajax({
		url : link,
		type : 'POST',
		success : function(data) {
			if(data != 0) {
				$.gritter.add({
					// (string | mandatory) the heading of the notification
					title : 'Auto Update.',
					// (string | mandatory) the text inside the notification
					text : data,
					// (string | optional) the image to display on the left
					// (bool | optional) if you want it to fade out on its own or just sit there
					sticky : false,
					// (int | optional) the time you want it to be alive for before fading out
					time : ''
				});
			}
			//alert(data)
		}
	});

}

function getbaseurl() {
	var href = window.location.href;
	var base_url = href.substr(href.lastIndexOf('http://'), href.lastIndexOf('/ADT'));
	var _href = href.substr(href.lastIndexOf('/') + 1);
	var base_url = base_url + "/ADT/";
	return base_url;
}

/*
 *Synchronizes drug stock balance table
 */
function synch_drug_balance(stock_type) {
	var base_url = $("#base_url").val();
	$(".bar_dsb").css("width", "0%");
	//Get number total number of drugs
	var _url = base_url + "drug_stock_balance_sync/getDrugs";
	var stock_type = stock_type;
	$.ajax({
		url : _url,
		type : 'POST',
		data : {
			"check_if_malicious_posted" : "1"
		},
		success : function(data) {
			data = $.parseJSON(data);
			//Count number of drugs
			var count_drugs = data.count;

			$("#div_tot_drugs").css("display", "block");
			$("#tot_drugs").html(count_drugs);

			var remaining_drugs = 1;
			$.each(data.drugs, function(key, value) {

				//Start synch
				var drug_id = value.id;
				var link = base_url + "drug_stock_balance_sync/synch_balance";
				var div_width = (remaining_drugs / count_drugs) * 100;
				$.ajax({
					url : link,
					type : 'POST',
					data : {
						"check_if_malicious_posted" : "1",
						"drug_id" : drug_id,
						"stock_type" : stock_type
					},
					success : function(data1) {
						remaining_drugs += 1;
						div_width1 = (remaining_drugs / count_drugs) * 100;
						div_width = div_width1 + "%";
						if(stock_type == 1) {
							$(".bar_store").css("width", div_width);
						} else if(stock_type == 2) {
							$(".bar_pharmacy").css("width", div_width);
						}

						//div_percentage=div_width1.toFixed(0);
						//$(".bar").html(div_percentage);
						if(remaining_drugs == count_drugs) {
							//$(".icon_drug_balance").css("display","block");
							//$(".progress").removeClass("active");
							//Start sync for pharmacy
							if(stock_type == 1) {
								synch_drug_balance(2);
							} else if(stock_type == 2) {
								//$(".sync_complete").html("Synchronization successfully completed !<i class='icon-ok'></i>");
								//$(".modal-footer").css("display","block");
								synch_drug_movement_balance("1");
							}

						}
					}
				});

			});
		}
	});
}

//Synchronizes drug stock  movement balance
function synch_drug_movement_balance(stock_type) {
	var base_url = $("#base_url").val();
	$(".bar_dsm").css("width", "0%");
	//$(".sync_complete").html("");
	$(".modal-footer").css("display", "none");
	//Get number total number of drugs
	var _url = base_url + "drug_stock_balance_sync/getDrugs";
	var stock_type = stock_type;
	$.ajax({
		url : _url,
		type : 'POST',
		data : {
			"check_if_malicious_posted" : "1"
		},
		success : function(data) {
			data = $.parseJSON(data);
			//Count number of drugs
			var count_drugs = data.count;

			//$("#div_tot_drugs").css("display","block");
			//$("#tot_drugs").html(count_drugs);

			var remaining_drugs = 1;
			$.each(data.drugs, function(key, value) {

				//Start synch
				var drug_id = value.id;
				var link = base_url + "drug_stock_balance_sync/drug_stock_movement_balance";
				var div_width = (remaining_drugs / count_drugs) * 100;
				$.ajax({
					url : link,
					type : 'POST',
					data : {
						"check_if_malicious_posted" : "1",
						"drug_id" : drug_id,
						"stock_type" : stock_type
					},
					success : function(data1) {
						remaining_drugs += 1;
						div_width1 = (remaining_drugs / count_drugs) * 100;
						div_width = div_width1 + "%";
						if(stock_type == 1) {
							$(".bar_store_dsm").css("width", div_width);
						} else if(stock_type == 2) {
							$(".bar_pharmacy_dsm").css("width", div_width);
						}

						//div_percentage=div_width1.toFixed(0);
						//$(".bar").html(div_percentage);
						if(remaining_drugs == count_drugs) {
							//$(".icon_drug_balance").css("display","block");
							//$(".progress").removeClass("active");
							//Start sync for pharmacy
							if(stock_type == 1) {
								synch_drug_movement_balance(2);
							} else if(stock_type == 2) {
								$(".sync_complete").html("Synchronization successfully completed !<i class='icon-ok'></i>");
								$(".modal-footer").css("display", "block");
								//drug_cons_synch();
							}

						}
					}
				});

			});
		}
	});
}

//Drug consumption balance
function drug_cons_synch() {
	var base_url = $("#base_url").val();
	$(".bar").css("width", "0%");
	$(".sync_complete").html("");
	$(".modal-footer").css("display", "none");
	$(".bar_dcb").css("width", "0%");
	$(".modal-footer").css("display", "none");
	//Get total number of drugs
	var _url = base_url + "drug_stock_balance_sync/get_drug_details_cons";
	var stock_type = 2;
	$.ajax({
		url : _url,
		type : 'POST',
		data : {
			"check_if_malicious_posted" : "1"
		},
		success : function(data) {
			console.log(data);
			data = $.parseJSON(data);
			//Count number of drugs
			var count_drugs = data.count;
			var remaining_drugs = 1;
			$.each(data.drugs, function(key, value) {

				//Start synch
				var drug_id = value.drug_id;
				var period = value.period;
				var total = value.total;
				var link = base_url + "drug_stock_balance_sync/drug_consumption";
				var div_width = (remaining_drugs / count_drugs) * 100;
				$.ajax({
					url : link,
					type : 'POST',
					data : {
						"check_if_malicious_posted" : "1",
						"drug_id" : drug_id,
						"stock_type" : stock_type,
						"period" : period,
						"total" : total
					},
					success : function(data1) {
						remaining_drugs += 1;
						div_width1 = (remaining_drugs / count_drugs) * 100;
						div_width = div_width1 + "%";
						$(".bar_dcb").css("width", div_width);
						//Done synchronizing
						if(remaining_drugs == count_drugs) {
							//$(".sync_complete").html("Synchronization successfully completed !<i class='icon-ok'></i>");
							//$(".modal-footer").css("display", "block");
							//synch_drug_balance(1);
						}

					}
				});

			});
		}
	});
}

/*
*Reports JS
*/
//-------- Date picker -------------------------

$(document).ready(function() {

	var href = window.location.href;
	var _href = href.substr(href.lastIndexOf('/') + 1);
	var href_final = _href.split('.');
	//Hide current page from menus
	var _id = "#" + href_final[0];
	$(".select_types").css("display", "none");

	/*
	 * Reports JS
	 */

	$(".generate_btn").live('click', function() {
		var base_url = $("#base_url").val();
		if($(".input-medium").is(":visible") || $(".month_period").is(":visible") || $(".report_type").is(":visible") || $(".report_type_1").is(":visible") || $(".input_year").is(":visible") || $(".input_dates").is(":visible") || $(".donor_input_dates_from").is(":visible") || $(".input_dates_from").is(":visible") || $(".donor_input_dates_to").is(":visible") || $(".input_dates_to").is(":visible")) {
			if($(".input_year").is(":visible") && $(".input_year").val() == "") {
				alert("Please enter the year");
				return;
			}
			//Dates not selected
			if($(".input_dates").is(":visible") && $(".input_dates").val() == "") {
				alert("Please select the date");
			}
			//Dates not selected
			else if($(".input_dates_from").is(":visible") && $(".input_dates_from").val() == "") {
				alert("Please select the starting date");
			}
			//Dates not selected
			else if($(".donor_input_dates_from").is(":visible") && $(".donor_input_dates_from").val() == "") {
				alert("Please select the starting date");
			}
			//Dates not selected
			else if($(".input_dates_to").is(":visible") && $(".input_dates_to").val() == "") {
				alert("Please select the end date");
			}
			//Dates not selected
			else if($(".donor_input_dates_to").is(":visible") && $(".donor_input_dates_to").val() == "") {
				alert("Please select the end date");
			}
			//Dropdown not chosen
			else if($("#commodity_summary_report_type").is(":visible") && $("#commodity_summary_report_type").val() == 0) {
				alert("Please select the report type");
			} else if($("#commodity_summary_report_type_1").is(":visible") && $("#commodity_summary_report_type_1").val() == 0) {
				alert("Please select the report type");
			}
			//If everything is ok,generate a report
			else {
				var id = $(this).attr("id");

				if(id == "generate_date_range_report") {
					var report = $(".select_report:visible").attr("value");
					var from = $("#date_range_from").attr("value");
					var to = $("#date_range_to").attr("value");
					//If adherence report, get adherence report type
					if(report=="graphical_adherence"){
						var adherence_type = $("#adherence_type_report").val();
						report = report + "/"+adherence_type;
					}
					
					if($(".report_type").is(":visible")) {
						report = report + "/" + $(".report_type:visible").attr("value");
					}
					
					var report_url = base_url + "report_management/" + report + "/" + from + "/" + to;
					window.location = report_url;
				} else if(id == "generate_month_range_report") {
					var report = $(".select_report:visible").attr("value");
					var from = $("#period_start_date").attr("value");
					var to = $("#period_end_date").attr("value");
					if($(".report_type").is(":visible")) {
						report = report + "/" + $(".report_type:visible").attr("value");
					}
					var report_url = base_url + "report_management/" + report + "/" + from + "/" + to;
					window.location = report_url;
				} else if(id == "generate_no_filter_report") {
					var report = $(".select_report:visible").attr("value");
					var report_type = $(".report_type:visible").attr("value");
					var report_url = base_url + "report_management/" + report + "/" + report_type;
					window.location = report_url;
				} else if(id == "generate_single_date_report") {
					var report = $(".select_report:visible").attr("value");
					var selected_date = $("#single_date_filter").attr("value");
					var report_url = base_url + "report_management/" + report + "/" + selected_date;
					window.location = report_url;
				} else if(id == "generate_single_year_report") {
					var report = $(".select_report:visible").attr("value");
					var selected_year = $("#single_year_filter").attr("value");
					//If drug consumption report, get types of reports selected(packs or units)
					var get_id = $(".select_report:visible option:selected").attr("id");
					if(get_id=='drug_consumption'){
						var pack_unit = $('#pack_unit').val();
						selected_year=selected_year+'/'+pack_unit;
					}
					var report_url = base_url + "report_management/" + report + "/" + selected_year;
					window.location = report_url;
				} else if(id == "generate_single_year_report") {
					var report = $(".select_report:visible").attr("value");
					var selected_year = $("#single_year_filter").attr("value");
					var report_url = base_url + "report_management/" + report + "/" + selected_year;
					window.location = report_url;
				} else if(id == "generate_month_period_report") {
					var report = $(".select_report:visible").attr("value");
					var from = $("#month_start_date").attr("value");
					var to = $("#month_end_date").attr("value");
					var report_url = base_url + "report_management/" + report + "/" + from + "/" + to;
					window.location = report_url;
				} else if(id == "donor_generate_date_range_report") {
					var report = $(".select_report:visible").attr("value");
					var from = $("#donor_date_range_from").attr("value");
					var to = $("#donor_date_range_to").attr("value");
					var donor = $("#donor").attr("value");
					var report_url = base_url + "report_management/" + report + "/" + from + "/" + to + "/" + donor;
					window.location = report_url;
				}
			}
		}

	});
	/*
	* Reports generation end
	*/

	//Add datepicker
	$("#date_range_from").datepicker({
		changeMonth : true,
		changeYear : true,
		dateFormat : 'dd-M-yy',
		onSelect : function(selected) {
			$("#date_range_to").datepicker("option", "minDate", selected);
		}
	});
	$("#single_date_filter").datepicker({
		changeMonth : true,
		changeYear : true,
		dateFormat : 'dd-M-yy'
	});
	$("#date_range_to").datepicker({
		changeMonth : true,
		changeYear : true,
		dateFormat : 'dd-M-yy',
		onSelect : function(selected) {
			$("#date_range_from").datepicker("option", "maxDate", selected);
		}
	});

	$("#donor_date_range_from").datepicker({
		changeMonth : true,
		changeYear : true,
		dateFormat : 'dd-M-yy',
		onSelect : function(selected) {
			$("#donor_date_range_to").datepicker("option", "minDate", selected);
		}
	});
	$("#donor_date_range_to").datepicker({
		changeMonth : true,
		changeYear : true,
		dateFormat : 'dd-M-yy',
		onSelect : function(selected) {
			$("#donor_date_range_from").datepicker("option", "maxDate", selected);
		}
	});
	$("#single_year_filter").datepicker({
		changeMonth : false,
		changeYear : true,
		dateFormat : 'yy',
		showButtonPanel : true,
		onClose : function(dateText, inst) {
			var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
			$(this).datepicker('setDate', new Date(year, 1));
		}
	});
	$("#single_year_filter").focus(function() {
		$(".ui-datepicker-month").hide();
		$(".ui-datepicker-calendar").hide();
	});

	$(".reports_types").css("display", "none");
	$("#standard_report_row").css("display", "block");

	//Reports types
	$(".reports_tabs").click(function() {
		$("#default").show();
		$(".select_types").css("display", "none");
		//Reset all texts and selects
		$("select").val("0");
		$("input:text").val("");

		//Standard report selected
		if($(this).attr("id") == 'standard_report') {

			$(".active").removeClass();
			$(this).addClass("active");
			$(".reports_types").css("display", "none");
			$("#standard_report_row").css("display", "block");
		}
		//Visiting report tab selected
		else if($(this).attr("id") == 'visiting_patient') {
			$(".active").removeClass();
			$(this).addClass("active");
			$(".reports_types").css("display", "none");
			$("#visiting_patient_report_row").css("display", "block");
		} else if($(this).attr("id") == 'early_warning_indicators') {
			$(".active").removeClass();
			$(this).addClass("active");
			$(".reports_types").css("display", "none");
			$("#early_warning_report_row").css("display", "block");
		} else if($(this).attr("id") == 'drug_inventory') {
			$(".active").removeClass();
			$(this).addClass("active");
			$(".reports_types").css("display", "none");
			$("#drug_inventory_report_row").css("display", "block");
		} else if($(this).attr("id") == 'moh_forms') {
			$(".active").removeClass();
			$(this).addClass("active");
			$("#default").hide();
			$(".reports_types").css("display", "none");
			$("#moh_forms_report_row").css("display", "block");
		}else if($(this).attr("id") == 'guidelines') {
			$(".active").removeClass();
			$(this).addClass("active");
			$(".reports_types").css("display", "none");
                        var report_url = base_url + "report_management/load_guidelines_view";
                        window.location = report_url;
		}
	});
	//Features to select
	$(".select_report").change(function() {
		var get_type = $("option:selected", this).attr("class");
		var get_id = $("option:selected", this).attr("id");
		
		if(get_type == "none") {
			$(".select_types").css("display", "none");
			return;
		}
		if(get_type == "donor_date_range_report") {
			$(".select_types").css("display", "none");
			$("#donor_date_range_report").css("display", "block");
		} else if(get_type == "annual_report") {
			
			$(".select_types").css("display", "none");
			$("#year").css("display", "block");
			//Check if it is drug_consumption to check if it is pack or unit
			if(get_id=="drug_consumption"){
				$("#pack_unit").css("display", "block");
			}else{
				$("#pack_unit").css("display", "none");
			}
		} else if(get_type == "single_date_report") {
			$(".select_types").css("display", "none");
			$("#single_date").css("display", "block");
		} else if(get_type == "date_range_report") {
			$(".select_types").css("display", "none");
			$("#date_range_report").css("display", "block");
			var selected = $("option:selected", this).val();
			if(selected == "graphical_adherence"){//If adherence report is selected, show  select adherence report type
				$(".adherence_report_type_title").css("display","block");
			}else{
				$(".adherence_report_type_title").css("display","none");
			}
			//If report is drug_consumption report, display select report type
			if(get_id == 'drug_stock_on_hand' || get_id == 'expiring_drugs' || get_id == 'expired_drugs' || get_id == 'getDrugsIssued' || get_id == 'getDrugsReceived' || get_id == 'commodity_summary') {
				$(".show_report_type").show();
			} else {
				$(".show_report_type").hide();
			}
		} else if(get_type == "month_range_report") {
			$(".select_types").css("display", "none");
			$("#month_range_report").css("display", "block");
			//If report is drug_consumption report, display select report type
			if(get_id == 'drug_stock_on_hand' || get_id == 'expiring_drugs' || get_id == 'expired_drugs' || get_id == 'getDrugsIssued' || get_id == 'getDrugsReceived' || get_id == 'commodity_summary') {
				$(".show_report_type").show();
			} else {
				$(".show_report_type").hide();
			}
		} else if(get_type == "month_period_report") {
			$(".select_types").css("display", "none");
			$("#month_period_report").css("display", "block");
		} else if(get_type == "no_filter") {
			$(".select_types").css("display", "none");
			$("#no_filter").css("display", "block");
			$("#selected_report").attr("value", $(this).attr("id"));
			//If report is drug_consumption report, display select report type
			if(get_id == 'drug_stock_on_hand' || get_id == 'expiring_drugs' || get_id == 'expired_drugs') {
				$(".show_report_type").show();
			} else {
				$(".show_report_type").hide();
			}
		}
	});
});
/*
 *Reports JS End
 */