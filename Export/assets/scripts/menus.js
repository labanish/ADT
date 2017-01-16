//-------- Date picker -------------------------

$(document).ready(function() {
	var href = window.location.href;
	var _href=href.substr(href.lastIndexOf('/') + 1);
	var href_final=_href.split('.');
	//Hide current page from menus
	var _id="#"+href_final[0];
	$(".select_types").css("display","none");

	//Add datepicker
	$("#date_range_from").datepicker({
		changeMonth : true,
		changeYear : true,
		dateFormat : 'dd-M-yy',
		onSelect: function(selected) {
          $("#date_range_to").datepicker("option","minDate", selected)
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
		onSelect: function(selected) {
           $("#date_range_from").datepicker("option","maxDate", selected)
        }
	});

	$("#donor_date_range_from").datepicker({
		changeMonth : true,
		changeYear : true,
		dateFormat : 'dd-M-yy',
		onSelect: function(selected) {
          $("#donor_date_range_to").datepicker("option","minDate", selected)
        }
	});
	$("#donor_date_range_to").datepicker({
		changeMonth : true,
		changeYear : true,
		dateFormat : 'dd-M-yy',
		onSelect: function(selected) {
           $("#donor_date_range_from").datepicker("option","maxDate", selected)
        }
	});
	$( "#single_year_filter" ).datepicker({
        changeMonth: false,
        changeYear: true,
        dateFormat: 'yy',
        showButtonPanel: true,
        onClose: function(dateText, inst) { 
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            $(this).datepicker('setDate', new Date(year, 1));
        }
	    
    });
    $("#single_year_filter").focus(function () {
        $(".ui-datepicker-month").hide();
         $(".ui-datepicker-calendar").hide();
    });
    
    

	$(".reports_types").css("display","none");
	$("#standard_report_row").css("display","block");
	
	//Reports types
	$(".reports_tabs").click(function(){
		$(".select_types").css("display","none");
		//Reset all texts and selects
		$("select").val("0");
		$("input:text").val("");
		
		//Standard report selected
		if($(this).attr("id")=='standard_report'){
			
			$(".active").removeClass();
			$(this).addClass("active");
			$(".reports_types").css("display","none");
			$("#standard_report_row").css("display","block");
		}
		//Visiting report tab selected
		else if($(this).attr("id")=='visiting_patient'){
			$(".active").removeClass();
			$(this).addClass("active");
			$(".reports_types").css("display","none");
			$("#visiting_patient_report_row").css("display","block");
		}
		else if($(this).attr("id")=='early_warning_indicators'){
			$(".active").removeClass();
			$(this).addClass("active");
			$(".reports_types").css("display","none");
			$("#early_warning_report_row").css("display","block");
		}
		else if($(this).attr("id")=='drug_inventory'){
			$(".active").removeClass();
			$(this).addClass("active");
			$(".reports_types").css("display","none");
			$("#drug_inventory_report_row").css("display","block");
		}
	})
	
	//Features to select
	$(".select_report").change(function(){
		
		var get_type=$("option:selected", this).attr("class");
		var get_id=$("option:selected", this).attr("id");
		
		if(get_type=="none"){
			$(".select_types").css("display","none");
			return;
		}
		if(get_type=="donor_date_range_report"){
			$(".select_types").css("display","none");
			$("#donor_date_range_report").css("display","block");
		}
		else if(get_type=="annual_report"){
			$(".select_types").css("display","none");
			$("#year").css("display","block");
		}
		else if(get_type=="single_date_report"){
			$(".select_types").css("display","none");
			$("#single_date").css("display","block");
		}
		else if(get_type=="date_range_report"){
			$(".select_types").css("display","none");
			$("#date_range_report").css("display","block");
			//If report is drug_consumption report, display select report type
			if(get_id=='drug_stock_on_hand' || get_id=='expiring_drugs' || get_id=='expired_drugs' || get_id=='getDrugsIssued' || get_id=='getDrugsReceived' ){
				$(".show_report_type").show();
			}
			else{
				$(".show_report_type").hide();
			}
		}
		else if(get_type=="month_range_report"){
			$(".select_types").css("display","none");
			$("#month_range_report").css("display","block");
			//If report is drug_consumption report, display select report type
			if(get_id=='drug_stock_on_hand' || get_id=='expiring_drugs' || get_id=='expired_drugs' || get_id=='getDrugsIssued' || get_id=='getDrugsReceived' || get_id=='commodity_summary'){
				$(".show_report_type").show();
			}
			else{
				$(".show_report_type").hide();
			}
		}
		else if(get_type=="no_filter"){
			$(".select_types").css("display","none");
			$("#no_filter").css("display","block");
			$("#selected_report").attr("value", $(this).attr("id"));
			//If report is drug_consumption report, display select report type
			if(get_id=='drug_stock_on_hand' || get_id=='expiring_drugs' || get_id=='expired_drugs'){
				$(".show_report_type").show();
			}
			else{
				$(".show_report_type").hide();
			}
		}
	})
	
});

