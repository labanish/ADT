<?php

// buffer created

ob_start();

if ($facility_object -> supported_by == "1") {
	$supporter = "GOK";
}
if ($facility_object -> supported_by == "2") {
	$supporter = "PEPFAR";
}
if ($facility_object -> supported_by == "3") {
	$supporter = "MSF";
}
$p = 0;
if ($facility_object -> service_art == "1") {
	$p = 1;
	$type_of_service = "ART";
}
if ($facility_object -> service_pmtct == "1") {
	if ($p == 1) {$type_of_service .= ",PMTCT";
	} else {$type_of_service .= "PMTCT";
		$p = 1;
	}

}
if ($facility_object -> service_pep == "1") {
	if ($p == 1) {
		$type_of_service .= ",PEP";
	} else {$type_of_service .= "PEP";
	}

}
 // flush Buffer

ob_get_clean();
?>
<script type="text/javascript">
	$(document).ready(function(){
		var $research = $('.research');
		$research.find("tr").not('.accordion').hide();
		$research.find("tr").eq(0).show();

		$research.find(".accordion").click(function() {
			$research.find('.accordion').not(this).siblings().fadeOut(500);
			$(this).siblings().fadeToggle(500);
		}).eq(0).trigger('click');

		$('#accordion_collapse').click(function() {
			if($(this).val() == "+") {
				var $research = $('.research');
				$research.find("tr").show();
				$('#accordion_collapse').val("-");
			} else {
				var $research = $('.research');
				$research.find("tr").not('.accordion').hide();
				$research.find("tr").eq(0).show();
				$('#accordion_collapse').val("+");
			}

		});
		<?php if (empty($fmaps_array)) {?>
		var report_period="<?php echo date('F-Y', strtotime(date('Y-m-d') . "-1 month")); ?>";
		$("#reporting_period").val(report_period);
		$("#reporting_period_end").val(report_period);
		var month=parseInt("<?php echo date('m', strtotime(date('Y-m-d') . "-1 month")); ?>");
		var year=parseInt("<?php echo date('Y', strtotime(date('Y-m-d') . "-1 month")); ?>");
        var last_day_month=LastDayOfMonth(year,month);
        $("#period_start").val("01");
        $("#period_end").val(last_day_month);
        var reporting_period = $("#reporting_period").attr("value");
		reporting_period = convertDate(reporting_period);
		var start_date = reporting_period + "-" + $("#period_start_date").attr("value");
		var end_date = reporting_period + "-" + $("#period_end_date").attr("value");
		<?php }else{?>
		var report_period="<?php echo date('F-Y', strtotime($fmaps_array[0]['period_begin'])); ?>";
		$("#reporting_period").val(report_period);	
		$("#reporting_period_end").val(report_period);	
		var month=parseInt("<?php echo date('m', strtotime($fmaps_array[0]['period_begin'])); ?>");
		var year=parseInt("<?php echo date('Y', strtotime($fmaps_array[0]['period_begin'])); ?>");
        var last_day_month=LastDayOfMonth(year,month);
        $("#period_start").val("01");
        $("#period_end").val(last_day_month);
        var reporting_period = $("#reporting_period").attr("value");
		reporting_period = convertDate(reporting_period);
		var start_date = reporting_period + "-" + $("#period_start_date").attr("value");
		var end_date = reporting_period + "-" + $("#period_end_date").attr("value");			
		<?php }?>
        // getPeriodRegimenPatients(start_date, end_date);
	});
	function LastDayOfMonth(Year, Month) {
		return (new Date((new Date(Year, Month, 1)) - 1)).getDate();
	}
	//Function to validate required fields
    function processData(form) {
      var form_selector = "#" + form;
      var validated = $(form_selector).validationEngine('validate');
        
        if(!validated) {
           return false;
        }else{
        	$(".btn").attr("disabled","disabled");
        	return true;
        }
    }
</script>
<style>
	.ui-datepicker-calendar {
		display: none;
	}
	.tbl_header_input{
		width:32%;
	}
	.table th, .table td{
		padding:3px;
	}
</style>

	<div class="center-content" >
		<?php
		 	if ($this->session->flashdata('order_message')){
				echo '<p class="message info">'.$this->session->flashdata('order_message').'</p>';
			}	
		 ?>
		<form id="fmPostMaps" action="<?php echo base_url() . 'order/save/maps/prepared';?>" method="post" name="fmPostMaps" style="margin-bottom:8%;">
			<input type="hidden"  id="report_type" name="report_type" value="<?php echo $report_type;?>"/>
			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo site_url().'order' ?>">MAPS</a><span class="divider">/</span>
					</li>
					<li class="active" id="actual_page">
						<?php echo $page_title;?>
					</li>
				</ul>
			</div>
			
				<div>
					<?php
				if($options=='view'){
						echo "<h4>".@$maps_id.' '.@ucfirst($status)."</h4>";
						echo "<a href='".site_url("order/download_order/maps/".$map_id)."'>".$maps_id." ".$fmaps_array[0]['facility_name']." ".$fmaps_array[0]['period_begin']." to ".$fmaps_array[0]['period_end'].".xls</a><p>";
						$access_level = $this -> session -> userdata("user_indicator");
				      	if($access_level=="facility_administrator"){
					      	if($status=="prepared"){
							?> <input type="hidden" name="status_change" value="approved"/>
							   <input type="hidden" name="save_maps" value="Approve"/> 
					           <input type='submit' name='save_maps' class='btn btn-info state_change' value='Approve'/>
							<?php
							      } else if($status=="approved"){
							 ?>
							 		<input type="hidden" name="status_change" value="archived"/> 
							 		<input type="hidden" name="save_maps" value="Archive"/> 
			 		                <input type='submit' name='save_maps' class='btn btn-info state_change' value='Archive'/>
							 <?php
							      }
							  ?>
						<input type="hidden"  id="status" name="status" value="<?php echo $status;?>"/>
						<input type="hidden"  id="created" name="created" value="<?php echo $created;?>"/>
							 <?php
						}
					
				}
				else if($options=='update'){
					echo "<h4>".ucfirst($options).' '.@$maps_id.' '.@ucfirst($status)."</h4>";	
				?>
				<input type="hidden"  id="status" name="status" value="<?php echo $status;?>"/>
				<input type="hidden"  id="created" name="created" value="<?php echo $created;?>"/>
				<?php
		
				}
				?>
				</div>
				<div  class="facility_info" style="width:100%;">
				<table class="table"  border="1"  style="border:1px solid #DDD; font-size: 1em;">
					<tbody>
						<tr>
							<input type="hidden" name="facility_id" value="<?php echo @$facility_id;?>" />
							<input type="hidden" name="central_facility" value="<?php echo @$facility_object -> parent;?>" />
							<input type="hidden" name="order_type" value="0"/>
							<th width="180px">Facility code:</th>
							<td><span class="_green"><?php echo @$facility_object -> facilitycode;?></span></td>
							<th width="160px">Facility Name:</th>
							<td><span class="_green"><?php echo @$facility_object -> name;?></span></td>
						</tr>
						<tr>
							<th>County:</th>
							<td><span class="_green"><?php echo @$facility_object -> County -> county;?></span></td>
							<th>District:</th>
							<td><span class="_green"><?php echo @$facility_object -> Parent_District -> Name;?></span></td>
						</tr>
						<tr>
							<th>Programme Sponsor:</th>
							<td><span name="sponsors" id="fmap_sponsors" class="_green"><?php echo @$supporter;?></span>
								<input type="hidden" name="sponsor" value="<?php echo @$supporter;?>" />
							</td>
							<th>Service provided:</th>
							<td><span name="service" id="fmap_services" class="_green"><?php echo @$type_of_service;?></span>
								<input type="hidden" name="services" value="<?php echo @$type_of_service;?>" />
							</td>
						</tr>
						<tr>
							<th>Reporting Period : </th>
							<td colspan="1">
								<strong>Beginning:</strong> <input name="start_date" id="period_start" type="text" style="width:10%" readonly="readonly"> <input class="_green" name="reporting_period" id="reporting_period" type="text" placeholder="Click here to select period" style="width:35%" readonly="readonly">
							</td>
							<th colspan="2">Ending : <input name="end_date" id="period_end" type="text" readonly="readonly" style="width:10%" disabled="true"> <input class="_green" name="reporting_period_end" id="reporting_period_end" type="text" style="width:35%" readonly="readonly"></th>

						<!-- add start and end date -->
							<!-- <input name="start_date" id="period_start" type="text"> -->
							<!-- <input name="end_date" id="period_end" type="text"> -->
							</td> 
							<td colspan="2"></td>
						</tr>
								<!-- <tr> -->
									<!-- <th colspan="2">Total Number of Patients on ART ONLY:</th> -->
									<!-- <td><span>Adults (&gt;15yrs)</span><input type="text"  class="validate[requied] tbl_header_input f_right"  name="art_adult" id="art_adult" readonly="readonly" value="<?php echo @$fmaps_array[0]['art_adult'];?>"/></td> -->
									<!-- <td><span>Children (&lt;= 15yrs)</span><input type="text" class="validate[requied] tbl_header_input f_right" name="art_child" id="art_child" readonly="readonly" value="<?php echo @$fmaps_array[0]['art_child'];?>"/></td> -->
								<!-- </tr> -->
								<!-- <tr><th style="text-align: center" colspan="2">Males</th><th style="text-align: center" colspan="2">Females</th></tr> -->
								<!-- <tr> -->
									<!-- <th>New <input type="text"  class="validate[requied] tbl_header_input f_right" name="new_male" id="new_male" value="<?php echo @$fmaps_array[0]['new_male'];?>" /></th> -->
									<!-- <th>Revisit <input type="text"  class="validate[requied] tbl_header_input f_right" name="revisit_male" id="revisit_male" value="<?php echo @$fmaps_array[0]['new_female'];?>" /></th> -->
									<!-- <th>New <input type="text"  class="validate[requied] tbl_header_input f_right" name="new_female" id="new_female" value="<?php echo @$fmaps_array[0]['revisit_male'];?>" /></th> -->
									<!-- <th>Revisit <input type="text"  class="validate[requied] tbl_header_input f_right" name="revisit_female" id="revisit_female" value="<?php echo @$fmaps_array[0]['revisit_female'];?>"/></th>				 -->
								</tr>
					</tbody>
				</table>

<!-- End of the top section -->
				<?php
					if($hide_generate==2 && $hide_btn==0){
				?>
				<input type="button" style="width: auto" name="generate" id="generate" class="btn" value="Update Aggregated Data" >
				<?php		
					}
					else if($hide_generate==0 && $hide_btn==0){
				?>
				<input type="button" style="width: auto" name="generate_central" id="generate_central" class="btn" value="Generate Report" >
				<?php		
					}
				?>
								
				
			</div>
			<div class="facility_info_bottom" style="width:100%;">
				<table class=" table table-bordered regimen-table big-table research" id="tbl_patients_regimen">
					<thead style="font-size:0.8em;">
						<tr>
							<th width="15%" class="col_drug"> Regimen Code</th>
							<th width="65%">ARV Treatment Regimen</th>
							<th width="20%">
							<input type="button" id="accordion_collapse" value="+"/><br>
							</span>No of Cumulative Active Patients/Clients on this regimen at the End of the Reporting period<span></th>
						</tr>
					</thead>

			<!-- start to display the available Regimens from the system DB -->
					<?php
			$counter = 1;
			foreach($regimen_categories as $category){
					$cat = str_replace(' ', '_',$category);
						?>
					<tbody>
						<?php
						if($options=='view'){
							//Don't display OI regimens
							if(strtoupper($category) == 'OI REGIMEN'){
								continue;
							}
							echo '<tr class="accordion"><th colspan="3" id="'.$cat.'"  >'.$category.'</th></tr>';
							
							$regimen_list=array_filter($regimen_array,function($item) use ($category){
								return $item['name']==$category;
							});
							if($supplier=="KEMSA"){
							foreach($regimen_list as $regimen){
								?>
							<tr>
								<td style="border-right:2px solid #DDD;"><?php echo @$regimen['code'];?></td>
								<td regimen_id="<?php echo $regimen['reg_id'];?>" class="regimen_desc col_drug"><?php echo @$regimen ['description'];?></td>
								<td regimen_id="<?php echo $regimen['reg_id'];?>" class="regimen_numbers">
								<input type="text" class="f_right patient_number" data-cat="<?php echo $cat; ?>" name="patient_numbers[]" id="patient_numbers_<?php echo $regimen['reg_id'];?>" style="text-align: center;" value="<?php echo $regimen['total'];?>" >
								<input name="patient_regimens[]"class="regimen_list" style="text-align: center; color: blue; font-weight: bold;" value="<?php echo $regimen['reg_id'];?>" type="hidden">
								<input type="hidden" name="item_id[]" class="item_id"/>
								</td>
							</tr>
							<?php
							}
						   }else{
						   	foreach($regimen_list as $regimen){
						   	?>
								<tr>
								<td style="border-right:2px solid #DDD;"><?php echo $regimen['code'];?></td>
								<td regimen_id="<?php echo $regimen['reg_id'];?>" class="regimen_desc col_drug"><?php echo $regimen['description'];?></td>
								<td regimen_id="<?php echo $regimen['reg_id'];?>" class="regimen_numbers">
								<input type="text" class="f_right patient_number" name="patient_numbers[]" id="patient_numbers_<?php echo $regimen['reg_id'];?>" style="text-align: center; color: blue; font-weight: bold;" value="<?php echo $regimen['total'];?>" >
								<input name="patient_regimens[]"class="regimen_list" style="text-align: center; color: blue; font-weight: bold;" value="<?php echo $regimen['reg_id'];?>" type="hidden">
								<input type="hidden" name="item_id[]" class="item_id"/>
								</td>
							</tr>
							<?php	
						   }
						   }
						}
						else{
							//Don't display OI regimens
							if(strtoupper($category -> Name) == 'OI REGIMEN'){
							 continue;
							}
							$regimens = $category -> Regimens;
							$cat = str_replace(' ', '_',$category -> Name);
						?><tr class="accordion"><th colspan="3" class="reg_cat_name" id="<?php echo $cat; ?>" ><?php echo $category -> Name;?></th></tr><?php
						 if($supplier=="KEMSA"){
							foreach($regimens as $regimen){
								?>
							<tr>
								<td style="border-right:2px solid #DDD;"><?php echo $regimen -> code;?>
									<!--<input type="hidden" name="item_id[]" class="item_id" id="item_id_<?php echo $regimen -> id;?>" value=""/>-->
								</td>
								<td regimen_id="<?php echo $regimen -> id;?>" class="regimen_desc col_drug"><?php echo $regimen -> name;?></td>
								<td regimen_id="<?php echo $regimen -> id;?>" class="regimen_numbers">
								<input type="text" class="f_right patient_number" data-cat="<?php echo $cat; ?>" name="patient_numbers[]" id="patient_numbers_<?php echo $regimen -> id;?>" >
								<input name="patient_regimens[]"class="regimen_list" style="text-align: center; color: blue; font-weight: bold;" value="<?php echo $regimen -> id;?>" type="hidden">
								<input type="hidden" name="item_id[]" class="item_id"/>
								</td>
							</tr>
							<?php
							}
						}else{
							foreach($regimens as $regimen){
								?>
							<tr>
								<td style="border-right:2px solid #DDD;"><?php echo $regimen -> code;?>
									<!--<input type="hidden" name="item_id[]" class="item_id" id="item_id_<?php echo $regimen -> id;?>" value=""/>-->
								</td>
								<td regimen_id="<?php echo $regimen -> id;?>" class="regimen_desc col_drug"><?php echo $regimen -> name;?></td>
								<td regimen_id="<?php echo $regimen -> id;?>" class="regimen_numbers">
								<input type="text" style="text-align: center; color: blue; font-weight: bold;" class="f_right patient_number" data-cat="<?php echo $cat; ?>" name="patient_numbers[]" id="patient_numbers_<?php echo $regimen -> id;?>" value="0">
								<input name="patient_regimens[]" style="text-align: center; color: blue; font-weight: bold;" class="regimen_list" value="<?php echo $regimen -> id;?>" type="hidden">
								<input type="hidden" style="text-align: center; color: blue; font-weight: bold;" name="item_id[]" class="item_id"/>
								
								</td>
							</tr>
						<?php
						   }
					   }
						?>
					</tbody>
					<?php
					}}
					?>
				</table>
			</div>

<!--Start of Remove from the maps -->

			<!-- need to Remove the controller that generate the data -->

			<!-- <div class="facility_info_bottom" style="width:100%;">
				<table class=" table table-bordered ">
					<tr>
						<td colspan="3">
							<strong>List Any Other Regimens</strong><br>
							<textarea name="other_regimen" id="other_regimen" style="width:100%" value="<?php echo @$fmaps_array[0]['comments'];?>" ></textarea>
						</td>
					</tr>
					<tr>
						<th>Totals for PMTCT Clients (Pregnant Women ONLY):</th>
						<td><span>New Clients</span><input type="text"  class="validate[requied] tbl_header_input f_right" name="new_pmtct" id="new_pmtct" value="<?php echo @$fmaps_array[0]['new_pmtct'];?>"  /></td>
						<td><span>Revisit Clients</span><input type="text"  class="validate[requied] tbl_header_input f_right" name="revisit_pmtct" id="revisit_pmtct" value="<?php echo @$fmaps_array[0]['revisit_pmtct'];?>"  /></td>
					</tr>
					<tr>
						<th colspan="2">Total No. of Infants receiving ARV prophylaxis for PMTCT:</th>
						<td><input type="text"  class="validate[requied] tbl_header_input f_right" name="total_infant" id="total_infant" value="<?php echo @$fmaps_array[0]['total_infant'];?>" /></td>
					</tr>
					<tr>
						<th>Totals for PEP Clients ONLY:</th>
						<td><span>Adults (&gt;15yrs)</span><input type="text"  class="validate[requied] tbl_header_input f_right" name="pep_adult" id="pep_adult" value="<?php echo @$fmaps_array[0]['pep_adult'];?>" /></td>
						<td><span>Children (&lt;=15yrs)</span><input type="text"  class="validate[requied] tbl_header_input f_right" name="pep_child" id="pep_child" value="<?php echo @$fmaps_array[0]['pep_child'];?>" /></td>
					</tr>
					<tr>
						<th>Totals for Patients / Clients (ART plus Non-ART) on Cotrimoxazole/Dapsone prophylaxis:</th>
						<td><span>Adults (&gt;15yrs)</span><input type="text"  class="validate[requied] tbl_header_input f_right" name="tot_cotr_adult" id="total_adult" value="<?php echo @$fmaps_array[0]['total_adult'];?>" /></td>
						<td><span>Children (&lt;=15yrs)</span><input type="text"  class="validate[requied] tbl_header_input f_right" name="tot_cotr_child" id="total_child" value="<?php echo @$fmaps_array[0]['total_child'];?>" /></td>
					</tr>
					<tr>
						<th>Totals for Patients / Clients on Diflucan (For Diflucan Donation Program ONLY):</th>
						<td><span>Adults (&gt;15yrs)</span><input type="text"  class="validate[requied] tbl_header_input f_right" name="diflucan_adult" id="diflucan_adult" value="<?php echo @$fmaps_array[0]['diflucan_adult'];?>" /></td>
						<td><span>Children (&lt;=15yrs)</span><input type="text"  class="validate[requied] tbl_header_input f_right" name="diflucan_child" id="diflucan_child" value="<?php echo @$fmaps_array[0]['diflucan_child'];?>" /></td>
					</tr>
				</table> -->
	<!-- Second section of remove from MAPS -->
				<!-- <table class=" table table-bordered ">
					<tr>
						<th colspan="2" style="text-align: center">CM</th><th colspan="2" style="text-align: center">OC</th>
					</tr>
					<tr>
						<td>New <input type="text"  class="validate[requied] tbl_header_input f_right" name="new_cm" id="new_cm" value="<?php echo @$fmaps_array[0]['new_cm'];?>" /></td>
						<td>Revisit <input type="text"  class="validate[requied] tbl_header_input f_right" name="revisit_cm" id="revisit_cm" value="<?php echo @$fmaps_array[0]['revisit_cm'];?>" /></td>
						<td>New <input type="text"  class="validate[requied] tbl_header_input f_right" name="new_oc" id="new_oc" value="<?php echo @$fmaps_array[0]['new_oc'];?>" /></td>
						<td>Revisit <input type="text"  class="validate[requied] tbl_header_input f_right" name="revisit_oc" id="revisit_oc" value="<?php echo @$fmaps_array[0]['revisit_oc'];?>" /></td>
					</tr>
					<?php
					if(isset($hide_generate) && $hide_generate==2){
						?>
						<tr>
							<th colspan="4" style="text-align: center">Central site Reporting rate</th>
						</tr>
					
						<tr>
							<th colspan="2">Total No. of Facility Reports Expected <input type="text"  class="validate[requied] tbl_header_input f_right" name="reports_expected" id="reports_expected" /></th>
							<th colspan="2">Actual No. of Facility reports Received <input type="text"  class="validate[requied] tbl_header_input f_right" name="reports_actual" id="reports_actual" /></th>
						</tr>
						<?php
					}
					?>
				</table> -->
	<!-- End of second section -->

<!-- End of the Remove section from MAPS -->
				<?php
				if($is_view==1 || $is_update==1){
				?>
			    <table style="width:100%;" class="table table-bordered">
			    	<?php 
			    	    error_reporting(0); 
			    	    foreach($logs as $log){?>
					<tr>
						<td><b>Report <?php echo $log->description;?> by:</b>
							<input type="hidden" name="log_id[]" id="log_id_<?php echo $log -> id;?>" value="<?php echo $log -> id;?>"/>
						</td>
						<td><?php echo $log->s_user->name; ?></td>
						<td><b>Designation:</b></td>
						<td><?php echo $log->s_user->role; ?></td>
					</tr>
					<tr>
						<td><b>Contact Telephone:</b></td>
						<td>N/A</td>
						<td><b>Date:</b></td>
						<td><?php echo $log->created; ?></td>
					</tr>
					<?php }?>
				</table>
				<?php if($is_update==1){?>
				    <input type="submit" id="save_changes" class="btn btn-info actual" value="Submit Report">
				    <input type="hidden" value="Submit Order" name="save_maps">
				<?php
				}}else{
				?>	
					<input type="submit" id="save_changes" class="btn btn-info actual" value="Submit Report">
					<input type="hidden" value="Submit Order" name="save_maps">
				<?php	
				}
				?>
			</div>
	</form>		
	</div>
<script type="text/javascript">
	$(document).ready(function(){

		//Check if report is a duplicate
		var duplicate = "<?=$duplicate?>";
		if(duplicate == true)
		{ 
		   bootbox.alert("<h4>Duplicate</h4>\n\<hr/><center>This Report already exists!</center>");
		}

		//function to disable button on click
		$("#fmPostMaps").on('submit',function(){
             $(".btn").attr("disabled","disabled");
             $(".state_change").attr("disabled","disabled");
		});
		//Check if data is being updated 
		var is_update="<?php echo @$is_update; ?>";
		var is_view="<?php echo @$is_view; ?>";
		var fmaps_id="<?php echo @$map_id; ?>";
		
		if(is_update==1){//If form is open for updating data
			getFacilityData(fmaps_id);
			$("#fmPostMaps").attr("action","<?php echo base_url() . 'order/save/maps/prepared/'.@$map_id;?>");//Change action to be posted to update function 
		}
		if(is_view==1){//When viewing maps details
			getFacilityData(fmaps_id);
			$("#fmPostMaps").attr("action","<?php echo base_url() . 'order/save/maps/prepared/'.@$map_id;?>");//Change action to be posted to update function 
			$(":input").attr('readonly',true);
			$(".state_change").attr("readonly",false);
			if($('#report_type').val()=='2'){//If reporting for satellite, enable art total
				$('#art_adult').removeAttr('readonly');
				$('#art_child').removeAttr('readonly');
			}
		}
		
		$("#generate").click(function() {//Get aggregated data
			$.blockUI({ message: '<h3><img width="30" height="30" src="<?php echo asset_url().'images/loading_spin.gif' ?>" /> Generating...</h3>' }); 
            var period_start = '<?php echo date('Y-m-01',strtotime(date('Y-m-d').'-1 month')) ?>';
            var period_end = '<?php echo date('Y-m-t',strtotime(date('Y-m-d').'-1 month')) ?>';
            
            getAggregateFmaps(period_start, period_end);
            setTimeout($.unblockUI, 10000);	
		});
		
		$("#generate_central").click(function() {//Generate data for central report
			$.blockUI({ message: '<h3><img width="30" height="30" src="<?php echo asset_url().'images/loading_spin.gif' ?>" /> Generating...</h3>' }); 
            var period_start = '<?php echo date('Y-m-01',strtotime(date('Y-m-d').'-1 month')) ?>';
            var period_end = '<?php echo date('Y-m-t',strtotime(date('Y-m-d').'-1 month')) ?>';
            var data_type = 'new_patient';
            $('#art_adult').val(0);
			$('#art_child').val(0);
			$('#new_male').val(0);
			$('#new_female').val(0);
			$('#revisit_male').val(0);
			$('#revisit_female').val(0);
		  	$('#revisit_pmtct').val(0);
		  	$('#new_pmtct').val(0);
		  	$('#total_infant').val(0);
		  	$('#pep_adult').val(0);
		  	$('#pep_child').val(0);
		  	$('#total_adult').val(0);
		  	$('#total_child').val(0);
		  	$('#diflucan_adult').val(0);
		  	$('#diflucan_child').val(0);
		  	$('#new_cm').val(0);
		  	$('#revisit_cm').val(0);
		  	$('#new_oc').val(0);
		  	$('#revisit_oc').val(0);
            getPeriodRegimenPatients(period_start, period_end);
            getNonMappedRegimen(period_start, period_end);
            getCentralData(period_start, period_end,data_type);
            
		});
		
		
		//When user changes patient number on a regimen, update total number on ART
		$(".patient_number").live("focus",function(){
			old_value = parseInt($(this).val());//Keep track of old values
			if(isNaN(old_value)) {
				old_value = 0;
			}
			//Get regimen category, adult or paed
			reg_category =$(this).data("cat");
			reg_category =reg_category.toLowerCase();
			
			
			
		});
		$(".patient_number").live("blur",function(){//On change, get old and new value before updating total ART
			new_value = $(this).val();
			if(isNaN(new_value)) {
				new_value = 0;
			}
			var change = new_value - old_value;
			if((reg_category.indexOf('pep')>-1 || reg_category.indexOf('pmtct')>-1)){
				
			}
			else if((reg_category.indexOf('paed')>-1 || reg_category.indexOf('ped')>-1 || reg_category.indexOf('child')>-1)){//Check if regimen is adult or paed
				var old_val = $("#art_child").val();
				if(old_val !=''){
					var new_val = parseInt(old_val)+(parseInt(change));	
				}else{
					var new_val = parseInt(change);
				}
				$("#art_child").val(new_val);
			}else if(reg_category.indexOf('adult')>-1 || reg_category.indexOf('mother')>-1){//Adult regimen
				var old_val = $("#art_adult").val();
				if(old_val !=''){
					var new_val = parseInt(old_val)+(parseInt(change));
				}else{
					var new_val = parseInt(change);
				}
				$("#art_adult").val(new_val);
			}
			old_value = new_value;
		});
		
	});
	
	function getNonMappedRegimen(start_date, end_date){//Get regimens that are not mapped(Not in the Escm or Nascop and list them as others)
		var base_url = getbaseurl();
		var link = base_url + 'order/getNotMappedRegimenPatients/' + start_date + '/' + end_date;
		$("#other_regimen").text("");
		$.ajax({
			url : link,
			type : 'POST',
			dataType : 'json',
			success : function(data) {
				var total_patients = 0;
				var total_patients_div = "";
				$.each(data, function(i, jsondata) {
					
					var total_patients = jsondata.patients;
					var regimen_desc = jsondata.regimen_desc;
					if(regimen_desc.toLowerCase().search("oi") == -1)
					{   
					    $("#other_regimen").append(""+regimen_desc+ " : "+total_patients);
					    $("#other_regimen").append(" ||  ");
					}
					
				});
			}
		});
		
	}
		//Get the values to Display

		 
	function getCentralData(period_start,period_end,data_type){
		
		var base_url = getbaseurl();
	  	var link = base_url + 'order/getCentralDataMaps/' + period_start + '/' + period_end + '/'+data_type;
	  	
	  	$.ajax({
				url : link,
				type : 'POST',
				dataType : 'json',
				success : function(data) {
					//console.log(data);
					var x=0;
					if('new_patient' in data){
						var l_new_patient=data.new_patient.length;
						if(l_new_patient==1){//Check if you only have males or female patients
							if(data.new_patient[0].gender=='new_male'){$('#new_male').val(data.new_patient[0].total);}
							else{$('#new_female').val(data.new_patient[0].total);}
						}
						else if(l_new_patient==2){
							if(data.new_patient[0].gender=='new_male'){
								$('#new_male').val(data.new_patient[0].total);
								$('#new_female').val(data.new_patient[1].total);
							}
							else if(data.new_patient[0].gender=='new_female'){
								$('#new_male').val(data.new_patient[1].total);
								$('#new_female').val(data.new_patient[0].total);
							}
							
						}
						
						getCentralData(period_start,period_end,'revisit_patient');//Recursive function for the next data to be appended
						
					}else if('revisit_patient' in data){
						var l_revisit_patient=data.revisit_patient.length;
						
						if(l_revisit_patient==1){
							if(data.revisit_patient[0].gender=='revisit_male'){$('#revisit_male').val(data.revisit_patient[0].total);}
							else{$('#revisit_female').val(data.revisit_patient[0].total);}
						}
						else if(l_revisit_patient==2){
							if(data.revisit_patient[0].gender=='revisit_male'){
								$('#revisit_male').val(data.revisit_patient[0].total);
								$('#revisit_female').val(data.revisit_patient[1].total);
							}
							else if(data.revisit_patient[0].gender=='revisit_female'){
								$('#revisit_male').val(data.revisit_patient[1].total);
								$('#revisit_female').val(data.revisit_patient[0].total);
							}
							
						}
						getCentralData(period_start,period_end,'revisit_pmtct');//Recursive function for the next data to be appended
						
					}else if('revisit_pmtct' in data){
						var l_revisit_pmtct=data.revisit_pmtct.length;
						$('#revisit_pmtct').val(data.revisit_pmtct[0].total);
						
						getCentralData(period_start,period_end,'new_pmtct');//Recursive function for the next data to be appended
						
					}else if('new_pmtct' in data){
						var l_new_pmtct=data.new_pmtct.length;
						$('#new_pmtct').val(data.new_pmtct[0].total);
						
						getCentralData(period_start,period_end,'prophylaxis');//Recursive function for the next data to be appended
						
					}else if('prophylaxis' in data){
						var l_prophylaxis=data.prophylaxis.length;
						$('#total_infant').val(data.prophylaxis[0].total);
						
						getCentralData(period_start,period_end,'pep');//Recursive function for the next data to be appended
						
					}else if('pep' in data){
						var l_pep=data.pep.length;
						if(l_pep==1){
							if(data.pep[0].age=='pep_adult'){$('#pep_adult').val(data.pep[0].total);}
							else{$('#pep_child').val(data.pep[0].total);}
						}
						else if(l_pep==2){
							if(data.pep[0].age=='pep_adult'){
								$('#pep_adult').val(data.pep[0].total);
								$('#pep_child').val(data.pep[1].total);
							}
							else if(data.pep[0].age=='pep_child'){
								$('#pep_adult').val(data.pep[1].total);
								$('#pep_child').val(data.pep[0].total);
							}
							
						}
						
						getCentralData(period_start,period_end,'cotrimo_dapsone');//Recursive function for the next data to be appended
						
					}else if('cotrimo_dapsone' in data){
						var l_cotrimo_dapsone=data.cotrimo_dapsone.length;
						if(l_cotrimo_dapsone==1){
							if(data.cotrimo_dapsone[0].age=='total_adult'){$('#total_adult').val(data.cotrimo_dapsone[0].total);}
							else{$('#total_child').val(data.cotrimo_dapsone[0].total);}
						}
						else if(l_cotrimo_dapsone==2){
							if(data.cotrimo_dapsone[0].age=='total_adult'){
								$('#total_adult').val(data.cotrimo_dapsone[0].total);
								$('#total_child').val(data.cotrimo_dapsone[1].total);
							}
							else if(data.cotrimo_dapsone[0].age=='total_child'){
								$('#total_adult').val(data.cotrimo_dapsone[1].total);
								$('#total_child').val(data.cotrimo_dapsone[0].total);
							}
							
						}
						
						getCentralData(period_start,period_end,'diflucan');//Recursive function for the next data to be appended
						
					}else if('diflucan' in data){
						var l_diflucan=data.diflucan.length;
						if(l_diflucan==1){
							if(data.diflucan[0].age=='diflucan_adult'){$('#diflucan_adult').val(data.diflucan[0].total);}
							else{$('#diflucan_child').val(data.diflucan[0].total);}
						}
						else if(l_diflucan==2){
							if(data.diflucan[0].age=='diflucan_adult'){
								$('#diflucan_adult').val(data.diflucan[0].total);
								$('#diflucan_child').val(data.diflucan[1].total);
							}
							else if(data.diflucan[0].age=='diflucan_child'){
								$('#diflucan_adult').val(data.diflucan[1].total);
								$('#diflucan_child').val(data.diflucan[0].total);
							}
							
						}
						getCentralData(period_start,period_end,'new_cm_oc');//Recursive function for the next data to be appended
						
					}else if('new_cm_oc' in data){
						var l_new_oc_cm=data.new_cm_oc.length;
						if(l_new_oc_cm==1){//CHeck if you only have males or female patients
							if(data.new_cm_oc[0].OI=='new_cm'){$('#new_cm').val(data.new_cm_oc[0].total);}
							else{$('#new_oc').val(data.new_cm_oc[0].total);}
						}
						else if(l_new_oc_cm==2){
							if(data.new_cm_oc[0].OI=='new_cm'){
								$('#new_cm').val(data.new_cm_oc[0].total);
								$('#new_oc').val(data.new_cm_oc[1].total);
							}
							else if(jsondata[0].OI=='new_oc'){
								$('#new_oc').val(data.new_cm_oc[1].total);
								$('#new_cm').val(data.new_cm_oc[0].total);
							}
							
						}
						getCentralData(period_start,period_end,'revisit_cm_oc');//Recursive function for the next data to be appended
						
					}else if('revisit_cm_oc' in data){
						var l_revisit_oc_cm=data.revisit_cm_oc.length;
						if(l_revisit_oc_cm==1){//CHeck if you only have males or female patients
							if(data.revisit_cm_oc[0].OI=='revisit_cm'){$('#revisit_cm').val(data.revisit_cm_oc[0].total);}
							else{$('#revisit_oc').val(data.revisit_cm_oc[0].total);}
						}
						else if(l_revisit_oc_cm==2){
							if(data.revisit_cm_oc[0].OI=='revisit_cm'){
								$('#revisit_cm').val(data.revisit_cm_oc[0].total);
								$('#revisit_oc').val(data.revisit_cm_oc[1].total);
							}
							else if(data.revisit_cm_oc[0].OI=='new_oc'){
								$('#revisit_oc').val(data.revisit_cm_oc[1].total);
								$('#revisit_cm').val(data.revisit_cm_oc[0].total);
							}
							
						}
						setTimeout($.unblockUI,1000);	
					}
							
				}
		});
	  	
	}
	
</script>


