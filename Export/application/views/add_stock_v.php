<!DOCTYPE html>
<html lang="en" >
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>New Stock</title>
		<link rel="SHORTCUT ICON" href="Images/favicon.ico">
		<link href="CSS/style.css" type="text/css" rel="stylesheet"/>
		<link href="CSS/offline_css.css" type="text/css" rel="stylesheet"/>
		<link href="CSS/jquery-ui.css" type="text/css" rel="stylesheet"/>
		<link href="CSS/validator.css" type="text/css" rel="stylesheet"/>
		<!-- Bootstrap -->
		<link href="Scripts/bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
		<link href="Scripts/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet" media="screen">
		
		<script type="text/javascript" src="Scripts/offlineData.js"></script>
		<script type="text/javascript" src="Scripts/jquery.js"></script>
		<script type="text/javascript" src="Scripts/jquery-ui.js"></script>
		<script type="text/javascript" src="Scripts/offline_database.js"></script>
		<script type="text/javascript" src="Scripts/validator.js"></script>
		<script type="text/javascript" src="Scripts/validationEngine-en.js"></script>
		<script type="text/javascript" src="Scripts/sorttable.js"></script>
		<script src="Scripts/bootstrap/js/bootstrap.min.js"></script>
		<script type="text/javascript">
			$(document).ready(function() {
				initDatabase();
				var d = new Date();
				var n = d.getFullYear();
				var satelite_sql = "";
				$("#year").text(n);
				//Get the environmental variables to display the hospital name
				selectEnvironmentVariables(function(transaction, results) {
					// Handle the results
					var row = results.rows.item(0);
					$("#facility_name").text(row['facility_name']);
					var facility = row['facility'];

					var today = new Date();
					var today_date = ("0" + today.getDate()).slice(-2)
					var today_year = today.getFullYear();
					var today_month = ("0" + (today.getMonth() + 1)).slice(-2)
					var today_full_date =today_date  + "-" + today_month + "-" + today_year;

					$("#transaction_date").attr("value", today_full_date);
					satelite_sql = "select * FROM facilities WHERE parent='" + facility + "' and facilitycode !='" + facility + "'";
					//Dynamically load the list of satelite sites
					SQLExecuteAbstraction(satelite_sql, function(transaction, results) {
						// Handle the results
						for(var i = 0; i < results.rows.length; i++) {
							var rower = results.rows.item(i);
							$("#destination").append($("<option></option>").attr("value", rower['facilitycode']).text(rower['name']));
						}
					});
					//Add datepicker for the transaction date
					$("#transaction_date").datepicker({
						defaultDate : new Date(),
						dateFormat : 'dd MM yy',
						changeYear : true,
						changeMonth : true
					});
					//Add datepicker for the expiry date
					$("#expiry_date").datepicker({
						defaultDate : new Date(),
						dateFormat : $.datepicker.ATOM,
						changeYear : true,
						changeMonth : true
					});
					//Check if number of packs has changed and automatically calculate the total
					$(".pack").keyup(function() {
						//updateCommodityQuantity($(this));
					});
					
					//Calculate the total cost automatically
					$("#unit_cost").keyup(function() {
						updateTotalCost($(this));
					});
					
					//Dynamically load the list of transaction types
					selectAll("transaction_type", function(transaction, results) {
						// Handle the results
						for(var i = 0; i < results.rows.length; i++) {
							var row = results.rows.item(i);
							//In case of store inventory, don't display dispense to patients
							if($("#add_stock_type").val()=='1'){
								if(row['id']!=5){
									$("#transaction_type").append($("<option></option>").attr("value", row['id']).attr("type", row['effect']).text(row['name']));
								}
							}
							
						}
					});
					
					//Dynamically load the list of commodity destinations
					selectAll("drug_destination", function(transaction, results) {
						// Handle the results
						for(var i = 0; i < results.rows.length; i++) {
							var row = results.rows.item(i);
							if(row['id'] == 1) {
								$("#destination").append($("<option></option>").attr("value",facility).text(row['name']));
							}else{
							$("#destination").append($("<option></option>").attr("value", row['id']).text(row['name']));
							}
						}
					});
					//Dynamically load the list of commodity sources
					selectAll("drug_source", function(transaction, results) {
						// Handle the results
						for(var i = 0; i < results.rows.length; i++) {
							var row = results.rows.item(i);
							$("#source").append($("<option></option>").attr("value", row['id']).text(row['name']));
							
						}
					});
					//Dynamically load the list of commodities
					selectAll("drugcode", function(transaction, results) {
						// Handle the results
						for(var i = 0; i < results.rows.length; i++) {
							var row = results.rows.item(i);
							$(".drug").append($("<option></option>").attr("value", row['id']).text(row['drug']));
						}
					});
					
					$(".destination").change(function(){
					   var selected=$(this).val();
					   if(selected==facility){
					   	$("#source").attr("value",facility);
					   }
					});

					$(".pack").change(function() {

						/*var selected_value = $(this).attr("value");
						 var pack_size = $(".pack_size").attr("value");
						 var stock_at_hand = $(".quantity").attr("value");
						 var stock_validity = stock_at_hand - (selected_value * pack_size);
						 if(stock_validity < 0 && stock_at_hand != "") {
						 alert("Quantity Cannot Be larger Than Stock at Hand");
						 $(this).attr("value", " ");

						 }
						 if(stock_validity > 0) {
						 updateCommodityQuantity($(this));
						 }*/
						updateCommodityQuantity($(this));

					});
					
					$("#unit_cost").change(function() {
						updateTotalCost($(this));
					});

					$(".remove").click(function() {
						$(this).closest('tr').remove();
					});
					//Add listener to the drug batch selecter to prepopulate the expiry date
					$(".batchselect").change(function() {
						var row_element = $(this).closest("tr");
						var expiry_date = row_element.find(".expiry");
						var drug = row_element.find(".drug").attr("value");
						//Get basic getails of the selected patient
						getBatchExpiry(drug, $(this).attr("value"), function(transaction, results) {
							// Handle the results
							if(results.rows.length > 0) {
								var row = results.rows.item(0);
								expiry_date.attr("value", row['expiry_date']);
								if(row['expiry_date'] != row['LEAST']) {
									bootbox.alert("<h4>Expired Batch</h4>\n\<hr/><center>This is not the first batch expiring</center>")
								}
							}
						});
						retrieveBatchesLevels(drug, $(this).val(), row_element);

					});
					function populateOnDrugRow(drug) {
						//First things first, retrieve the row where this drug exists
						var row_element = drug.closest("tr");

						var cloned_object = $('#drugs_table tr:last');
						var drug_row = cloned_object.attr("drug_row");
						var next_drug_row = parseInt(drug_row) + 1;
						cloned_object.attr("drug_row", next_drug_row);
						var batch_id = "batch_" + next_drug_row;
						var batchselect_id = "batchselect_" + next_drug_row;
						//Second thing, retrieve the respective containers in the row where the drug is
						var unit = row_element.find(".unit");
						var batch = row_element.find(".batchselect");
						var batchprev = row_element.find(".batch");
						var expiry = row_element.find(".expiry");
						var dose = row_element.find(".dose");
						var duration = row_element.find(".duration");
						var brand = row_element.find(".brand");
						var pack_size = row_element.find(".pack_size");
						var pack = row_element.find(".pack");
						var quantity = row_element.find(".quantity");
						var stock_on_hand = row_element.find(".quantity_available");
						//pack.attr("value", "");
						quantity.attr("value", "");

						if(drug.attr("value") > 0) {
							//Retrieve details about the selected drug from the database
							selectSingleFilteredQuery("drugcode", "id", drug.attr("value"), function(transaction, results) {

								// Handle the results
								var row = results.rows.item(0);
								getUnitName(row['unit'], function(transaction, res) {
									if(res.rows.length > 0) {
										var r = res.rows.item(0);
										//console.log(r);
										unit.attr("value", r['name']);
										pack_size.attr("value", row['pack_size'])
									}

								});
								unit.attr("value", row['unit']);
								dose.attr("value", row['dose']);
								duration.attr("value", row['duration']);
								batch.children('option').remove();
								expiry.attr("value", "");

								//Retrieve all the batch numbers for this drug
								selectEnvironmentVariables(function(transaction, results) {
									// Handle the results
									var row = results.rows.item(0);
									var facility = row['facility'];
									retrieveBatches(drug.attr("value"), batch, facility);
								});
							});
						}
					}

					function retrieveBatches(drug, batch, facility) {
						var stock_status = 0;
						var starting_stock_sql = "SELECT (SUM( d.quantity ) - SUM( d.quantity_out )) AS Initital_stock,d.batch_number AS batch,transaction_date FROM drug_stock_movement d WHERE d.drug ='" + drug + "' AND facility='" + facility + "' AND strftime('%Y%m%d',d.expiry_date)> strftime('%Y%m%d',date()) GROUP BY d.batch_number  having Initital_stock>0 order by d.expiry_date asc";
						//console.log(starting_stock_sql);
						batch.append($("<option></option>").attr("value", "").text("Select"));
						SQLExecuteAbstraction(starting_stock_sql, function(transaction, results) {
							for(var i = 0; i < results.rows.length; i++) {
								var first_row = results.rows.item(i);
								var batch_value = first_row["batch"];
								var initial_stock_sql = "SELECT SUM( d.quantity ) AS Initial_stock, d.transaction_date AS transaction_date, '" + batch_value + "' AS batch FROM drug_stock_movement d WHERE d.drug =  '" + drug + "' AND facility='" + facility + "' AND (source='"+facility+"' OR destination='"+facility+"') AND source!=destination AND transaction_type =  '11' AND d.batch_number =  '" + batch_value + "'";
								SQLExecuteAbstraction(initial_stock_sql, function(transaction, results) {
									for(var m = 0; m < results.rows.length; m++) {
										var physical_row = results.rows.item(m);
										initial_stock = physical_row['Initial_stock'];
										//Check if initial stock is present meaning physical count done
										if(initial_stock != null) {
											batch_stock_sql = "SELECT (SUM( ds.quantity ) - SUM( ds.quantity_out )) AS stock_levels, ds.batch_number FROM drug_stock_movement ds WHERE ds.transaction_date BETWEEN  '" + physical_row['transaction_date'] + "' AND date() AND facility='" + facility + "'  AND (ds.source='"+facility+"' OR ds.destination='"+facility+"') AND ds.source!=ds.destination AND ds.drug ='" + drug + "'  AND ds.batch_number ='" + physical_row['batch'] + "'";
											SQLExecuteAbstraction(batch_stock_sql, function(transaction, results) {
												for(var j = 0; j < results.rows.length; j++) {
													var second_row = results.rows.item(j);
													//console.log(second_row)
													if(second_row['stock_levels'] > 0) {
														var formatted_batch_balance = second_row['stock_levels'];
														var drug_id = drug;
														var batch_id = second_row['batch_number'];
														var row_element = $('#drugs_table tr:last');
														var batch = row_element.find(".batchselect");
														var batchprev = row_element.find(".batch");
														var batchselect_id = batch.attr("id");
														var batchshow_id = batchprev.attr("id");
														$('#' + batchselect_id).show();
														$('#' + batchshow_id).hide();
														batch.append($("<option></option>").attr("value", batch_id).text(batch_id));
													} else {
														populateDrugRow(drug);
													}
												}
											});
										} else {
											
											batch_stock_sql = "SELECT (SUM( ds.quantity ) - SUM( ds.quantity_out ) ) AS stock_levels, ds.batch_number FROM drug_stock_movement ds WHERE ds.drug =  '" + drug + "' AND facility='" + facility + "' AND (ds.source='"+facility+"' OR ds.destination='"+facility+"') AND ds.source!=ds.destination AND ds.expiry_date > date() AND ds.batch_number='" + physical_row['batch'] + "'";
											//console.log(batch_stock_sql)
											SQLExecuteAbstraction(batch_stock_sql, function(transaction, results) {
												for(var j = 0; j < results.rows.length; j++) {
													var second_row = results.rows.item(j);

													if(second_row['stock_levels'] > 0) {
														var formatted_batch_balance = second_row['stock_levels'];
														var drug_id = drug;
														var batch_id = second_row['batch_number'];
														var row_element = $('#drugs_table tr:last');
														var batch = row_element.find(".batchselect");
														var batchprev = row_element.find(".batch");
														var batchselect_id = batch.attr("id");
														var batchshow_id = batchprev.attr("id");
														$('#' + batchselect_id).show();
														$('#' + batchshow_id).hide();
														batch.append($("<option></option>").attr("value", batch_id).text(batch_id));
													} else {
														populateDrugRow(drug);
													}

												}
											});
										}

									}

								});
							}

						});
					}

					function retrieveBatchesLevels(drug, batch, row_element) {
						var stock_status = 0;
						//Query to check if batch has had a physical count
						selectEnvironmentVariables(function(transaction, results) {
							// Handle the results
							var row = results.rows.item(0);
							var facility = row['facility'];

							var initial_stock_sql = "SELECT SUM( d.quantity ) AS Initial_stock, d.transaction_date AS transaction_date, '" + batch + "' AS batch FROM drug_stock_movement d WHERE d.drug =  '" + drug + "' AND facility='" + facility + "' AND transaction_type =  '11' AND d.batch_number =  '" + batch + "'";

							SQLExecuteAbstraction(initial_stock_sql, function(transaction, results) {
								for(var m = 0; m < results.rows.length; m++) {
									var physical_row = results.rows.item(m);
									initial_stock = physical_row['Initial_stock'];
									//Check if initial stock is present meaning physical count done
									if(initial_stock != null) {
										batch_stock_sql = "SELECT (SUM( ds.quantity ) - SUM( ds.quantity_out )) AS stock_levels, ds.batch_number FROM drug_stock_movement ds WHERE ds.transaction_date BETWEEN  '" + physical_row['transaction_date'] + "' AND date() AND facility='" + facility + "' AND (source='"+facility+"' OR destination='"+facility+"') AND ds.source!=ds.destination AND ds.drug ='" + drug + "'  AND ds.batch_number ='" + physical_row['batch'] + "'";
										SQLExecuteAbstraction(batch_stock_sql, function(transaction, results) {
											for(var j = 0; j < results.rows.length; j++) {
												var second_row = results.rows.item(j);
												//console.log(second_row)
												if(second_row['stock_levels'] > 0) {
													batch_stock = second_row['stock_levels'];
													row_element.find(".quantity_available").attr("value", batch_stock);
												}
											}
										});
									} else {
										batch_stock_sql = "SELECT (SUM( ds.quantity ) - SUM( ds.quantity_out ) ) AS stock_levels, ds.batch_number FROM drug_stock_movement ds WHERE ds.drug =  '" + drug + "' AND facility='" + facility + "' AND (source='"+facility+"' OR destination='"+facility+"') AND ds.source!=ds.destination  AND ds.expiry_date > date() AND ds.batch_number='" + physical_row['batch'] + "'";
										//console.log(batch_stock_sql)
										SQLExecuteAbstraction(batch_stock_sql, function(transaction, results) {
											for(var j = 0; j < results.rows.length; j++) {
												var second_row = results.rows.item(j);

												if(second_row['stock_levels'] > 0) {
													batch_stock = second_row['stock_levels'];
													row_element.find(".quantity_available").attr("value", batch_stock);
												}

											}
										});
									}

								}

							});
						});
					}


					$(".add").click(function() {
						var cloned_object = $('#drugs_table tr:last').clone(true);
						var drug_row = cloned_object.attr("drug_row");
						var next_drug_row = parseInt(drug_row) + 1;
						cloned_object.attr("drug_row", next_drug_row);
						var batch_id = "batch_" + next_drug_row;
						var batchselect_id = "batchselect_" + next_drug_row;
						var quantity_id = "quantity_" + next_drug_row;
						var expiry_id = "expiry_date_" + next_drug_row;
						var batch = cloned_object.find(".batch");
						var batchselect = cloned_object.find(".batchselect");
						batchselect.empty();
						var packs = cloned_object.find(".pack");
						var unit = cloned_object.find(".unit");
						var pack_size = cloned_object.find(".pack_size");
						var quantity = cloned_object.find(".quantity");
						var quantity_available = cloned_object.find(".quantity_available");
						var expiry_date = cloned_object.find(".expiry");
						var unit_cost = cloned_object.find(".unit_cost");
						var total_amount = cloned_object.find(".amount");
						var comment = cloned_object.find(".comment");
						cloned_object.find(".remove").show();
						batch.attr("id", batch_id);
						batchselect.attr("id", batchselect_id);
						quantity.attr("id", quantity_id);
						expiry_date.attr("id", expiry_id);
						batch.attr("value", "");
						batchselect.attr("value", "");
						quantity.attr("value", "");
						expiry_date.attr("value", "");
						packs.attr("value", "");
						pack_size.attr("value", "");
						unit.attr("value", "");
						quantity_available.attr("value", "");
						unit_cost.attr("value","");
						total_amount.attr("value","");
						comment.attr("value","");
						var expiry_selector = "#" + expiry_id;

						$(expiry_selector).datepicker({
							defaultDate : new Date(),
							changeYear : true,
							changeMonth : true
						});
						cloned_object.insertAfter('#drugs_table tr:last');
						refreshDatePickers();

						return false;
					});
					//Fill some of the text boxes with details of the selected drug
					$(".drug").change(function() {
						$(".quantity_available").val("");
						if($("#transaction_type").attr("value") == 1 || $("#transaction_type").attr("value") == 4 || $("#transaction_type").attr("value") == 11 || $("#transaction_type").attr("value") == 0) {
							populateDrugRow($(this));
						} else {
							populateOnDrugRow($(this));
						}
					});
					$("#transaction_type").change(function() {
						var selected_value = $(this).val();
						$("#source").attr("value", "");
						$("#destination").attr("value", "");
						//If selected value is received from or returns to
						if(selected_value == 1 || selected_value == 8) {
							$("#destination_label").hide();
							$("#source_label").show();
						}
						
						//If selected value is returns from or issued to
						if(selected_value == 6 || selected_value == 3) {
							$("#source_label").hide();
							$("#destination_label").show();
						}
						if(selected_value == 2 || selected_value == 4 || selected_value == 5 || selected_value == 7 || selected_value == 9 || selected_value == 10 || selected_value == 11) {
							$("#source_label").hide();
							$("#destination_label").hide();
						}
						if(selected_value == 0) {
							$("#source_label").show();
							$("#destination_label").show();
						}
					});
				});
			});
			function updateCommodityQuantity(pack_object) {
				var packs = pack_object.attr("value");
				var pack_size = pack_object.closest("tr").find(".pack_size").attr("value");
				var quantity_holder = pack_object.closest("tr").find(".quantity");
				var available_quantity=pack_object.closest("tr").find(".quantity_available").val();
				available_quantity=parseInt(available_quantity);
				
				if(!isNaN(pack_size) && pack_size.length > 0 && !isNaN(packs) && packs.length > 0) {
					var qty=packs * pack_size;
					//If stock is going out, check that qty issued to be <= to qty available
					
					//Transaction coming in
					if($("#transaction_type").attr("value") == 1 || $("#transaction_type").attr("value") == 4 || $("#transaction_type").attr("value") == 11 || $("#transaction_type").attr("value") == 0) {
						quantity_holder.css("background-color","#FFF");
						quantity_holder.attr("value",qty );
					} 
					//Transaction going out
					else {
						if(available_quantity>=qty){
							quantity_holder.css("background-color","#FFF");
							quantity_holder.attr("value",qty );
							quantity_holder.removeClass("stock_add_form_input_error");
						}
						else{
							quantity_holder.attr("value",qty );
							bootbox.alert("<h4>Quantit Error</h4>\n\<hr/><center>Error !Quantity issued is greater than qty available!</center>");
							quantity_holder.addClass("stock_add_form_input_error");
							quantity_holder.css("background-color","rgb(255, 92, 52)");
						}
					}
					
					
					
				}
			}
			
			function updateTotalCost(unit_cost_object) {
				var unit_cost = unit_cost_object.attr("value");
				var quantity_holder = unit_cost_object.closest("tr").find(".quantity").attr("value");
				var total_cost=unit_cost_object.closest("tr").find(".amount");
				if(!isNaN(unit_cost) && unit_cost.length > 0 && !isNaN(quantity_holder) && quantity_holder.length > 0) {
					total_cost.attr("value", unit_cost * quantity_holder);
				}
				else{
					total_cost.attr("value",0);
				}
			}

			function populateDrugRow(drug) {

				//First things first, retrieve the row where this drug exists
				var row_element = drug.closest("tr");
				var row_id = row_element.attr("row_id");

				//Second thing, retrieve the respective containers in the row where the drug is
				var unit = row_element.find(".unit");
				var pack_size = row_element.find(".pack_size");
				var pack = row_element.find(".pack");
				var quantity = row_element.find(".quantity");
				pack.attr("value", "");
				quantity.attr("value", "");
				var row_element = $('#drugs_table tr:last');
				var batch = row_element.find(".batchselect");
				var batchprev = row_element.find(".batch");
				var batchselect_id = batch.attr("id");
				var batchshow_id = batchprev.attr("id");
				$('#' + batchselect_id).hide();
				$('#' + batchshow_id).show();
				$("#expiry_date").attr("value", "");

				//Retrieve details about the selected drug from the database
				getDrugsDetails(drug.attr("value"), function(transaction, results) {
					// Handle the results
					var row = results.rows.item(0);
					unit.attr("value", row['drug_unit']);
					pack_size.attr("value", row['pack_size']);
					//After all is done, recalculate the quantity
					updateCommodityQuantity(pack);
				});
			}

			function refreshDatePickers() {
				var counter = 0;
				$('.expiry').each(function() {
					var new_id = "date_" + counter;
					$(this).attr("id", new_id);
					$(this).datepicker("destroy");
					$(this).not('.hasDatePicker').datepicker({
						defaultDate : new Date(),
						dateFormat : $.datepicker.ATOM,
						changeYear : true,
						changeMonth : true
					});
					counter++;

				});
			}
		</script>
		<style type="text/css">
			.short_title {
				height: 35px;
				background: #036;
				color: #FFF;
				font-weight: bold;
				width: 100%;
			}
			.banner_text {
				color: #FFF;
				font-weight: bold;
				font-family: Book Antiqua;
			}
			#fmAddStock {
				background: #D1EAF0;
				min-height: 400px;
			}
			#drugs_section {
				clear: both;
				margin: 20px 40% 0 70px;
				padding: 20px;
				zoom: 110%;
				font-weight: bold;
				color: #000;
			}
			#submit_section {
				margin:  0px 0px 0px 0px;
			}
			.submit-button{
				margin:0;
				float:none;
				height:auto;
				font-weight:normal;
			}
			#drugs_table {
				background: #FFF;
			}
			#facility_name {
				color: green;
				margin-top: 5px;
				font-weight: bold;
			}
			.expiry medium_text {
				width: 500px;
			}
			.span10,.span2{
				padding:8px;
			}
			.span2{
				margin-left:0px;
				
			}
			.span10{
				margin-left:0px;
			}
			.label{
				color:black;
			}
			.btn-small{
				text-align:center;
				padding:0px 4px 2px 4px;
			}
			.table td {
				padding:3px;
			}
			.table input[type="text"],.table  select{
				margin:2px;
				font-size:13px;
				height:25px;
			}
			.btn-large {
				padding: 5px 10px;
			}
			legend {
				font-size: 20px;
			}
		</style>
	</head>
	<body>
		<div id="wrapper">
			<div id="top-panel" style="margin:0px;">
				<div class="logo"></div>
				<div class="network">
					Network Status: <span id="status" class="offline">Offline</span>
					<p>
						Out-of-Sync Records: <span id="local-count"></span>
					</p>
				</div>
				<div id="system_title">
					<span style="display: block; font-weight: bold; font-size: 14px; margin:2px;">Ministry of Health</span>
					<span style="display: block; font-size: 12px;">ARV Drugs Supply Chain Management Tool</span>
					<span style="display: block; font-size: 14px;" id="facility_name" ></span>
				</div>
				
			</div>
			<div id="inner_wrapper">
				<div id="main_wrapper">
					<form id="stock_form" method="post" >
						<input type="hidden" name="add_stock_type" id="add_stock_type" value="1">
					<div id="fmAddStock">
						<div class="short_title" >
							<h3 class="banner_text" style="width:auto;">Commodity Transaction Entry Form - <span style="color:rgb(20, 255, 0);">Main Store</span></h3>
						</div>
						<hr/>
						<div class="container-fluid">
  							<div class="row-fluid">
								<div class="span2">
									<label> <strong class="label">Transaction Date</strong><br>
										<input type="text"name="transaction_date" id="transaction_date" class=" input-large required=" required" style="color:green;">
									</label>
									<label> <strong class="label">Transaction Type</strong><br>
										<select type="text" name="transaction_type" id="transaction_type" class="input-large" >
											<option value="0">--Select One--</option>
										</select> </label>
									<label> <strong class="label">Ref./Order Number</strong><br>
										<input   type="text" name="reference_number" id="reference_number" class="input-large" required="required" >
									</label>
									<label id="source_label"> <strong class="label">Source</strong><br>
										<select type="text" name="source" id="source" class="input-large"  >
											<option></option>
										</select> </label>
									<label id="destination_label"> <strong class="label">Destination</strong><br>
										<select type="text" name="destination" id="destination" class="input-large destination" >
											<option></option>
										</select> </label>
								</div>
								<div class="span10">
									<legend>Drug details</legend>
									<table border="0" class="table table-bordered" id="drugs_table">
										<tr>
											<th>Drug</th>
											<th>Unit</th>
											<th>Pack Size</th>
											<th>Batch No.</th>
											<th>Expiry&nbsp;Date</th>
											<th>Packs</th>
											<th>Qty</th>
											<th>Available Qty</th>
											<th>Unit Cost</th>
											<th>Total</th>
											<th>Comment</th>
											<th>Action</th>
										</tr>
										<tr drug_row="1">
											<td>
											<select name="drug" class="drug"  style="max-width:250px;">
												<option>Select Commodity</option>
											</select></td>
											<td>
											<input type="text" name="unit" class="unit small_text" />
											</td>
											<td>
											<input type="text" name="pack_size" class="pack_size small_text" />
											</td>
											<td><select name="batchselect" class="batchselect" id="batchselect_1" style="display:none;width:120px;"></select>
											<input type="text" name="batch" class="batch  validate[required]"   id="batch_1" style="width:120px;"/>
											</td>
											<td>
											<input type="text" name="expiry" class="expiry medium_text" id="expiry_date"  size="15"/>
											</td>
											<td>
											<input type="text" name="pack" class="pack small_text validate[required]" id="packs_1"  />
											</td>
											<td>
											<input type="text" name="quantity" id="quantity_1" class="quantity small_text" readonly="" />
											</td>
											<td>
											<input type="text" name="available_quantity" class="quantity_available medium_text" readonly="" />
											</td>
											<td>
											<input type="text" name="unit_cost" id="unit_cost" class="unit_cost small_text " />
											</td>
											<td>
											<input type="text" name="amount" id="total_amount" class="amount input-small" readonly="" />
											</td>
											<td >
											<input type="text" name="comment" class="comment" style="width:150px"/>
											</td>
											<td style="text-align: center">
												<button class="btn btn-info btn-small add"><i class="icon-plus"></i></button>
												<button style="display:none;" class="btn btn-danger btn-small remove"><i class="icon-minus"></i></button>
											</td>
										</tr>
									</table>
									<div id="submit_section">
										<input type="reset" class="btn btn-warning btn-large" id="reset" value="Reset Fields" />
										<input form="stock_form" class=" submit-button btn btn-success btn-large" id="submit" value="Save Stock" />
									</div>
								
									</div>
								</div>
							</div>
							
					</div>
					</form>
				</div>
				<!--End Wrapper div-->
			</div>
			<div id="bottom_ribbon" style="top:20px; width:90%;">
				<div id="footer">
					<div id="footer_text">
						Government of Kenya &copy; <span id="year" ></span>. All Rights Reserved
					</div>
				</div>
			</div>
	</body>
</html>
