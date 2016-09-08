<?php 
foreach($results as $result){
	
}
foreach ($expiries as $expiry) {
	
}
?>
<!DOCTYPE html>
<html lang="en" >
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<style type="text/css">
			.icondose {
				background: #FFFFFF url(../../assets/images/dropdown.png) no-repeat 95% 4px;		
			}
		</style>
		<script type="text/javascript">
			$(document).ready(function() {
				$("#patient").val("<?php echo $result['patient_id'];?>");
				var first_name="<?php echo strtoupper($result['first_name']); ?>";
				var other_name="<?php echo strtoupper($result['other_name']); ?>";
				var last_name="<?php echo strtoupper($result['last_name']); ?>";
				$("#ccc_id").val("<?php echo $ccc_id; ?>");
				$("#patient_details").val(first_name+" "+other_name+" "+last_name);
				 
				$("#dispensing_date").val("<?php echo @$result['dispensing_date'];?>"); 
				$("#original_dispensing_date").val("<?php echo @$result['dispensing_date'];?>"); 
				$("#original_drug").val("<?php echo @$result['drug_id']; ?>");
				$("#original_expiry_date").val("<?php echo @$expiry['expiry_date']; ?>");
				$("#dispensing_id").val("<?php echo @$record;?>"); 
				$("#batch_hidden").val("<?php echo @$result['batch_number']; ?>");
				$("#qty_hidden").val("<?php echo @$result['quantity']; ?>");
				$("#purpose").val("<?php echo @$result['visit_purpose'];?>"); 
				$("#weight").val("<?php echo @$result['current_weight'];?>"); 
				$("#height").val("<?php echo @$result['current_height'];?>"); 
				$("#last_regimen").val("<?php echo @$result['last_regimen'];?>"); 
				$("#current_regimen").val("<?php echo @$result['regimen'];?>"); 
				$("#adherence").val("<?php echo @$result['adherence'];?>"); 
				$("#non_adherence_reasons").val("<?php echo @$result['non_adherence_reason'];?>"); 
				$("#regimen_change_reason").val("<?php echo @$result['regimen_change_reason'];?>"); 
				$("#brand").val("<?php echo @$result['brand']; ?>");
				$("#indication").val("<?php echo @$result['indication']; ?>");
				$("#pill_count").val("<?php echo @$result['months_of_stock']; ?>");
				$("#missed_pills").val("<?php echo @$result['missed_pills']; ?>");
				$("#comment").val("<?php echo @$result['comment']; ?>");
				
				$("#original_drug").val("<?php echo $result['drug_id'];?>");
				
				if($("#last_regimen").val() !=""){
					if($("#last_regimen").val() !=$("#current_regimen").val()){
				      $("#regimen_change_reason_container").show();	
				    }
				}
				
				
				
				//Get Drugs in current_regimen
				getRegimenDrugs($("#current_regimen").val());
				
				
				
			//Dynamically change the list of drugs once a current regimen is selected
			$("#current_regimen").change(function() {
			   var regimen = $("#current_regimen option:selected").attr("value");
			   var last_regimen = $("#last_regimen").attr("value");
			   if(last_regimen != 0) {
						if(regimen != last_regimen) {
							$("#regimen_change_reason_container").show();
						} else {
							$("#regimen_change_reason_container").hide();
							$("#regimen_change_reason").val("");
						}
				}else{
					  $("#regimen_change_reason_container").hide();
					  $("#regimen_change_reason").val("");
				}	
			});
				
			//Attach date picker for date of dispensing
	        $("#dispensing_date").datepicker({
					yearRange : "-120:+0",
					maxDate : "0D",
					dateFormat : $.datepicker.ATOM,
					changeMonth : true,
					changeYear : true
			});
			
			//Add datepicker for the expiry date
			$("#expiry").datepicker({
					defaultDate : new Date(),
					dateFormat : $.datepicker.ATOM,
					changeMonth : true,
					changeYear : true

			});
			
			
			//Set Delete Trigger
			$("#delete_btn").click(function(){
				$("#delete_trigger").val("1");
				var message=confirm("Are You Sure You want to Delete?");
				if(message){
					return true;
				}else{
					return false;
				}
				
			});
			
	       //Function to display all Drugs in this regimen
		   $("#current_regimen").change(function() {
		   	  $("#drug option").remove();
		   	  $("#unit").val("");
		   	  var current_regimen = $(this).val();
                          getRegimenDrugs(current_regimen);
		   });
		   
		   function getRegimenDrugs(regimen){
		   	  var base_url="<?php echo base_url();?>";
		   	  var link=base_url+"regimen_management/getAllDrugs/"+regimen;
				$.ajax({
				    url: link,
				    type: 'POST',
				    dataType: "json",
				    success: function(data) {	
				    	$("#drug").append($("<option></option>").attr("value",'').text('--Select One--'));
				    	$.each(data, function(i, jsondata){
				    		$("#drug").append($("<option></option>").attr("value",jsondata.drug_id).text(jsondata.drug_name));
				    		if(jsondata.drug_id==$("#original_drug").val()){
				    			getDrugBatches(jsondata.drug_id);
				    		}
				    	});
				    	$("#drug").val($("#original_drug").val());
				    	$("#duration").val("");
                        $("#qty_disp").val("");
                        $("#expiry").val("");
                        $("#soh").val("");
                        $("#dose").val("");
				    }
				});
		   }
		   
		   //Event Checker for Drugs
		   $("#drug").change(function(){
		   	  $("#batch option").remove();
		   	  $("#unit").val("");
			  $("#dose").val("");
			  $("#duration").val("");
			  $("#qty_disp").val("");
		   	  $("#expiry").val("");
			  $("#soh").val("");
		   	  var drug = $(this).val();
		   	  
              getDrugBatches(drug);
              getBrands(drug);
		   });
		   
		   //Validate quantity dispensed
		   $(".qty_disp").blur(function() {
				checkQtyDispense();
			});
			
			
		   
		   function getDrugBatches(drug){
		   	  var base_url="<?php echo base_url();?>";
		   	  var link=base_url+"inventory_management/getAllDrugsBatches/"+drug;
				$.ajax({
				    url: link,
				    type: 'POST',
				    dataType: "json",
				    success: function(data) {	
				    	
				    	$("#batch").append($("<option></option>").attr("value",'').text('--Select One--'));
				    	$.each(data, function(i, jsondata){
				    		$("#batch").append($("<option></option>").attr("value",jsondata.batch_number).text(jsondata.batch_number));
				    	    $("#unit").val(jsondata.unit);
				    	    $("#dose").val(jsondata.dose);
				    	    $("#duration").val(jsondata.duration);
				    	    $("#qty_disp").val(jsondata.quantity); 
				    	    if($("#original_drug").val()==drug){
				    	    $("#batch").val($("#batch_hidden").val());   
				    	    $("#dose").val("<?php echo $result['dose']; ?>");
				    	    $("#duration").val("<?php echo $result['duration']; ?>");
				    	    $("#qty_disp").val("<?php echo $result['quantity']; ?>");
				    	    }
				    	});
                        getDoses();
				    	getBatchInfo();
				    }
				    
				});
		   }
		   
		   function getDoses(){
		   	var url_dose="<?php echo base_url().'dispensement_management/getDoses'; ?>";
					//Get doses
					var request_dose=$.ajax({
				     url: url_dose,
				     type: 'post',
				     dataType: "json"
				    });
				    request_dose.done(function(data){
				    	$(".dose option").remove();
				    	$.each(data,function(key,value){
				    		$(".dose").append("<option value='"+value.Name+"'  dose_val='"+value.value+"' dose_freq='"+value.frequency+"' >"+value.Name+"</option> ");
				   		});
				   		$(".dose").val("<?php echo @$result['dose']; ?>");			    	
				    });
		   }
		   
		   function getBrands(drug){
		   	 var base_url="<?php echo base_url();?>";
		   	  var link=base_url+"inventory_management/getDrugsBrands/"+drug;
				$.ajax({
				    url: link,
				    type: 'POST',
				    dataType: "json",
				    success: function(data) {	
				    	
				    	$("#brand").append($("<option></option>").attr("value",'').text('-Select One-'));
				    	$.each(data, function(i, jsondata){
				    		$("#brand").append($("<option></option>").attr("value",jsondata.id).text(jsondata.brand));			    		
				    	});
				    	
				    }
				});
		   }
		   
		   $("#batch").change(function(){
		   	if($(this).prop("selectedIndex")>1){
		   		bootbox.alert("<h4>Expired Batch</h4>\
                                                            <hr/>\n\
                                            <center>This is not the first expiring batch</center>");
		   	}
		   	   getBatchInfo();
		   });
		   
		   function getBatchInfo(){
		   	 var base_url="<?php echo base_url();?>";
			 var stock_type='<?php echo $ccc_id; ?>';
		   	 var drug=$("#drug").val();
		   	 var batch=$("#batch").val();
		   	 var link=base_url+"inventory_management/getAllBacthDetails";
		   	
		   	 $.ajax({
				    url: link,
				    type: 'POST',
					data: {"stock_type":stock_type,"selected_drug":drug,"batch_selected":batch},
				    dataType: "json",
				    success: function(data) {	
				    	console.log(stock_type+'');
				    	$("#expiry").val(data[0].expiry_date);
				    	if(data[0].balance<0){
				    		$("#soh").val(0);
				    	}else{
				    		$("#soh").val(data[0].balance);
				    	}
				    	
				    }
				});
		   }
		   
		   //check button wen submitted
		   		$(".my_btn").click(function(){
		     		var btn_id=$(this).attr("id");
		     		$("#btn_id").val(btn_id);
		     	});
		   
		   		  
		});
			function checkQtyDispense(){
				var selected_value = $("#qty_disp").attr("value");
				var stock_at_hand =  $("#soh").attr("value");
				var expiry_batch = Date.parse($("#expiry").attr("value"));
				var original_qty=$("#qty_hidden").val();
				var current_qty=$("#qty_disp").val();
				var difference = original_qty - current_qty;
				var stock_validity = parseInt(stock_at_hand) + parseInt(difference);
				
				var dtToday = Date.parse(new Date());
				$("#qty_disp").css("background-color","white");
				$("#qty_disp").removeClass("input_error");
				if(stock_validity < 0 && original_qty!=current_qty) {//If stock is less than value edited
					bootbox.alert("<h4>Quantity-Stock  Alert</h4>\n\<hr/><center>Quantity Cannot Be larger Than Stock at Hand</center>");
					$("#qty_disp").css("background-color","red");
					$("#qty_disp").addClass("input_error");
					return false;	
				}else if((dtToday>expiry_batch) && original_qty!=current_qty){//If value is being edited and batch has expired, should not allow editing
					bootbox.alert("<h4>Expiry Date  Alert</h4>\n\<hr/><center>The batch you are trying to edit has already expired</center>");
					return false;
				}
				else{
					$("#qty_disp").css("background-color","white");
					$("#qty_disp").removeClass("input_error");
					return true;
				}	
				
			}
			 //Function to validate required fields
		    function processData(form) {
		          var form_selector = "#" + form;
		          var validated = $(form_selector).validationEngine('validate');                 
		            if(!validated) {
		            	if($("#btn_id").val()=="delete_btn"){
		            	  return true;
		            	}else{
	                      return false;
	                    }
		            }else{
		            	//Validate quantity dispensed
		            	var check=checkQtyDispense();
		            	var last_row=$('#drugs_table tr:last');
                                
		            	if(check === false){
                            return false;
		            	}
                        else if(last_row.find(".qty_disp").hasClass("input_error")){ 
                           return false;
						}
						else{
                           return true;
						}
                                             
		            }
		     }
		</script>
		<style>
			.dataTables_scroll
			{
			    overflow:auto;
			}

			table#drugs_table input{
                height:30px;
            }
            table#drugs_table select{
                height:30px;
            }

            .mid-row select {
			    width: 100%;
			}

		</style>
	</head>
	<body>
<div class="full-content" style="background:#FFCC99">
	<div id="sub_title" >
		<a href="<?php  echo base_url().'patient_management ' ?>">Patient Listing </a> <i class=" icon-chevron-right"></i><a href="<?php  echo base_url().'patient_management/load_view/details/'.$result['p_id'] ?>"><?php echo strtoupper($result['first_name'].' '.$result['other_name'].' '.$result['last_name']) ?></a> <i class=" icon-chevron-right"></i><strong>Edit dispensing details</strong>
		<hr size="1">
	</div>
	<h3>Dispensing History Editing</h3>
	<form id="edit_dispense_form" name="edit_dispense_form" method="post"  action="<?php echo base_url().'dispensement_management/save_edit';?>"  >
		<input id="original_dispensing_date" name="original_dispensing_date" type="hidden"/>
		<input id="original_expiry_date" name="original_expiry_date" type="hidden"/>
		<input id="original_drug" name="original_drug" type="hidden"/>
		<input id="batch_hidden" name="batch_hidden" type="hidden"/>
		<input id="dispensing_id" name="dispensing_id" type="hidden"/>
		<input id="qty_hidden" name="qty_hidden" type="hidden"/>
		<input id="ccc_id" name="ccc_id" type="hidden"/>
		<input id="delete_trigger" name="delete_trigger" type="hidden" value="0"/>
		<div class="column-5">
			<fieldset>
				<legend>
					Dispensing Information
				</legend>
				<div class="max-row">
					<div class="mid-row">
						<label><span class='astericks'>*</span>Patient Number CCC</label>
						<input readonly="" id="patient" name="patient" class="validate[required]"/>
					</div>
					<div class="mid-row">
						<label><span class='astericks'>*</span>Patient Name</label>
						<input readonly="" id="patient_details" name="patient_details" class="validate[required]"/>
					</div>
				</div>
				<div class="max-row">
					<div class="mid-row">
						<label><span class='astericks'>*</span>Dispensing Date</label>
						<input type="text"name="dispensing_date" id="dispensing_date" class="validate[required]"/>
					</div>
					<div class="mid-row">
						<label><span class='astericks'>*</span>Purpose of Visit</label>
						<select type="text"name="purpose" id="purpose" class="validate[required]"/>
						<option value="">--Select One--</option>
									<?php 
									foreach($purposes as $purpose){
										echo "<option value='".$purpose['id']."'>".$purpose['Name']."</option>";
									}
									?>
						</select>
					</div>
				</div>
				<div class="max-row">
					<div class="mid-row">
						<label><span class='astericks'>*</span>Current Height(cm)</label>
						<input type="text"name="height" id="height" class="validate[required]"/>
					</div>
					<div class="mid-row">
						<label><span class='astericks'>*</span>Current Weight(kg)</label>
						<input type="text"name="weight" id="weight" class="validate[required]"/>
					</div>
					<div class="max-row">
						<div class="mid-row">
							<label id="scheduled_patients" class="message information close" style="display:none"></label>
							<label>Last Regimen Dispensed</label>
							<select name="last_regimen" id="last_regimen" />
							<option value="">-Select One--</option>
										<?php 
									       foreach($regimens as $regimen){
										     echo "<option value='".$regimen['id']."'>".$regimen['Regimen_Code']." | ".$regimen['Regimen_Desc']."</option>";
									       }
									     ?>
							</select>
						</div>
						<div class="mid-row">
							<label><span class='astericks'>*</span>Current Regimen</label>
							<select name="current_regimen" id="current_regimen"  class="validate[required]"/>
							<option value="">-Select One--</option>
										<?php 
									       foreach($regimens as $regimen){
										     echo "<option value='".$regimen['id']."'>".$regimen['Regimen_Code']." | ".$regimen['Regimen_Desc']."</option>";
									       }
									     ?>
							</select>
						</div>
					</div>
					<div class="max-row">
						<div style="display:none" id="regimen_change_reason_container">
							<label>Regimen Change Reason</label>
							<select type="text"name="regimen_change_reason" id="regimen_change_reason" style="width:50%;">
									<option value="">--Select One--</option>
										 <?php
										   foreach($regimen_changes as $changes){
										   	echo "<option value='".$changes['id']."'>".$changes['Name']."</option>";
										   }
										  ?>
							</select>
						</div>
					</div>
					<div class="max-row">
						<div class="mid-row">
							<label>Appointment Adherence (%)</label>
							<input type="text"name="adherence" id="adherence">
						</div>
						<div class="mid-row">
							<label> Poor/Fair Adherence Reasons </label>
							<select type="text"name="non_adherence_reasons" id="non_adherence_reasons">
								<option value="">-Select One--</option>
										<?php 
									       foreach($non_adherence_reasons as $reasons){
										     echo "<option value='".$reasons['id']."'>".$reasons['Name']."</option>";
									       }
									     ?>
							</select>
						</div>
					</div>
			</fieldset>
		</div>
		<div id="edit_drugs_section" style="margin: 0 auto;">
			<table border="1" class="table-bordered patient_table" id="drugs_table" style="width:100%; margin-top:10px">
				<thead>
					<th class="subsection-title" colspan="14">Select Drugs</th>
					<tr>
					<th>Drug</th>
					<th>Unit</th>
					<th>Batch No.</th>
					<th>Expiry Date</th>
					<th>Dose</th>
					<th>Duration</th>
					<th>Qty. disp</th>
					<th>Stock on Hand</th>
					<th>Brand Name</th>
					<th>Indication</th>
					<th>Pill Count</th>
					<th>Missed Pills</th>
					<th>Comment</th>
					</tr>
				</thead>
				<tbody>
					<tr drug_row="0">
					<td><select name="drug" class="drug input-xlarge" id="drug" ></select></td>
					<td>
					<input type="text" name="unit" id="unit" class="unit input-small validate[required]"  readonly="readonly"/>
					</td>
					<td><select id="batch" name="batch" class="batch input-medium"></select></td>
					<td>
					<input type="text" id="expiry" name="expiry" class="expiry input-xlarge validate[required]" style="width:100px;" />
					</td>
					<td>
					<input  name="dose" list="dose"  style="width:75%;" class="input-small next_pill dose icondose">
                            <datalist id="dose" class="dose">
                    <select name="dose"></select></datalist>
					</td>
					<td>
					<input type="text" id="duration" name="duration" class="duration input-small validate[required]" />
					</td>
					<td>
					<input type="text" id="qty_disp" name="qty_disp" class="qty_disp input-small validate[required]" />
					</td>
					<td>
					<input type="text" id="soh" name="soh" class="soh input-small" readonly="readonly"/>
					</td>
					<td><select name="brand" id="brand" class="brand input-small"></select></td>
					<td>
					<select name="indication" id="indication" class="indication input-small">
					<option value=" ">None</option>
					<?php 
					foreach($indications as $indication){
						echo "<option value='".$indication['Indication']."'>".$indication['Indication']." | ".$indication['Name']."</option>";
					}
					?>
					</select></td>
					<td>
					<input type="text" name="pill_count" id="pill_count" class="pill_count input-small" />
					</td>
					<td>
					 <input type="text" name="missed_pills" id="missed_pills" class="missed_pills input-small" />
					</td>
					<td>
					<input type="text" name="comment" id="comment" class="comment input-small" />
					</td>
					</tr>
				</tbody>
			
			</table>
		</div>
		<div id="submit_section">
			<div class="btn-group">
				<input type="hidden" id="btn_id" name="btn_id"/>
				<input  form="edit_dispense_form" class="btn actual my_btn"  value="Save & go Back" />
				<input type="submit" class="btn btn-danger my_btn" id="delete_btn" name="delete" value="Delete Record" onclick="checkBtn();"/>
			</div>
		</div>
	</form>
</div>
</body>
</html>