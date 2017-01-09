<?php
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
			var month=parseInt("<?php echo date('m', strtotime(date('Y-m-d') . "-1 month")); ?>");
			var year=parseInt("<?php echo date('Y', strtotime(date('Y-m-d') . "-1 month")); ?>");
	        var last_day_month=LastDayOfMonth(year,month);
	        $("#period_start").val("01");
	        $("#period_end").val(last_day_month);
	        var reporting_period = $("#reporting_period").attr("value");
			reporting_period = convertDate(reporting_period);
			var start_date = reporting_period + "-" + $("#period_start_date").attr("value");
			var end_date = reporting_period + "-" + $("#period_end_date").attr("value");
			<?php }
		else{?>
			var report_period="<?php echo date('F-Y', strtotime($fmaps_array[0]['period_begin'])); ?>";
			$("#reporting_period").val(report_period);	
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
			echo '
				<div class="alert alert-info">
				  <button type="button" class="close" data-dismiss="alert">&times;</button>
				  '.$this->session->flashdata('order_message').'
				</div>';
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
		if($options=='view'){//IF viewing a map
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
		else if($options=='update'){//If updating a map
			echo "<h4>".ucfirst($options).' '.@$maps_id.' '.@ucfirst($status)."</h4>";	
			?>
			<input type="hidden"  id="status" name="status" value="<?php echo $status;?>"/>
			<input type="hidden"  id="created" name="created" value="<?php echo $created;?>"/>
			<?php 
		}?>
		</div>
		<!-- Facility Information -->
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
						<th>Period of Reporting: </th><td>
						<input class="_green" name="reporting_period" id="reporting_period" type="text" placeholder="Click here to select period" readonly="readonly">
						</td>
						<input name="start_date" id="period_start" type="hidden">
						<input name="end_date" id="period_end" type="hidden">
						</td> 
						<td colspan="2"></td>
					</tr>
					
				</tbody>
			</table>
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
		<!-- Facility information ends -->
		<!--List of Regimens Starts -->
		<div class="facility_info_bottom" style="width:100%;">
			<table class=" table table-bordered regimen-table big-table research" id="tbl_patients_regimen">
				<thead style="font-size:0.8em;">
					<tr>
						<th width="15%" class="col_drug">Regimen Code</th>
						<th width="65%">ARV or OI Treatment Regimen</th>
						<th width="20%">
						<input type="button" id="accordion_collapse" value="+"/><br>
						</span>Number of Current Active Patients/Clients on this regimen at the end of this Reporting period<span></th>
					</tr>
				</thead>
				
					<?php foreach ($regimen_categories as $key => $value) {//Start looping through the regimen categories
						?>
						<tbody>
						<?php
						$category_id = $value->id; 
						$category_name = $value->Name;
						$regimens = $value->Regimens;
						echo "<tr class='accordion'><th colspan='3' id='$category_id'  >$category_name</th></tr>";
						
						//---------------- Load regimens section starts
						if($options=='view'){//If viewing MAPS
							$regimen_list=array_filter($regimen_array,function($item) use ($category){
								return $item['name']==$category;
							});
							foreach($regimen_list as $regimen){
						   	?>
								<tr>
									<td style="border-right:2px solid #DDD;"><?php echo $regimen['code'];?></td>
									<td regimen_id="<?php echo $regimen['reg_id'];?>" class="regimen_desc col_drug"><?php echo $regimen['description'];?></td>
									<td regimen_id="<?php echo $regimen['reg_id'];?>" class="regimen_numbers">
									<input type="text" class="f_right patient_number" name="patient_numbers[]" id="patient_numbers_<?php echo $regimen['reg_id'];?>" value="<?php echo $regimen['total'];?>" >
									<input name="patient_regimens[]"class="regimen_list" value="<?php echo $regimen['reg_id'];?>" type="hidden">
									<input type="hidden" name="item_id[]" class="item_id"/>
									</td>
								</tr>
							<?php	
							}
						}
						else{//If creating of updating MAPS
							
							
							foreach ($regimens as $regimen) {
								?>
								<tr>
									<td style="border-right:2px solid #DDD;"><?php echo $regimen -> code;?>
										<!--<input type="hidden" name="item_id[]" class="item_id" id="item_id_<?php echo $regimen -> id;?>" value=""/>-->
									</td>
									<td regimen_id="<?php echo $regimen -> id;?>" class="regimen_desc col_drug"><?php echo $regimen -> name;?></td>
									<td regimen_id="<?php echo $regimen -> id;?>" class="regimen_numbers">
									<input type="text" class="f_right patient_number" data-cat="<?php echo $category_id; ?>" name="patient_numbers[]" id="patient_numbers_<?php echo $regimen -> id;?>" >
									<input name="patient_regimens[]"class="regimen_list" value="<?php echo $regimen -> id;?>" type="hidden">
									<input type="hidden" name="item_id[]" class="item_id"/>
									</td>
								</tr>
								<?php
							}
						}
						
						//---------------- Load regimens section ends
						?>
						</tbody>
						<?php
					}?>
				</tbody>
			</table>
		</div>
		<!--List of regimens Ends -->
		
		<?php
		if(isset($hide_generate) && $hide_generate==2){//Display this only when generating a D-MAPS
		?>
			<table class=" table table-bordered ">
				<tr>
					<th colspan="4" style="text-align: center">Central site Reporting rate</th>
				</tr>
			
				<tr>
					<th colspan="2">Total No. of Facility Reports Expected <input type="text"  class="validate[requied] tbl_header_input f_right" name="reports_expected" id="reports_expected" /></th>
					<th colspan="2">Actual No. of Facility reports Received <input type="text"  class="validate[requied] tbl_header_input f_right" name="reports_actual" id="reports_actual" /></th>
				</tr>
			</table>
		<?php
		}
		?>
		
		<?php //When viewing or updating, display these details
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
	 </form>
</div>