<style type="text/css">
	.ui-datepicker-calendar {
    	display: none;
    }
</style>
<script>
	$(document).ready(function() {
		
		$(".accordion").accordion();

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
			}else{
				var $research = $('.research');
				$research.find("tr").not('.accordion').hide();
				$research.find("tr").eq(0).show();
				$('#accordion_collapse').val("+");
			}
             
		});
		
		$("#reporting_period").datepicker({
			yearRange : "-120:+0",
			maxDate : "0D",
			changeMonth: true,
	        changeYear: true,
	        showButtonPanel: true,
	        dateFormat: 'MM-yy',
        	onClose: function(dateText, inst) { 
	            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
	            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
	            
	            month=parseInt(month);
	            var last_day_month=LastDayOfMonth(year,month+1);
	            
	            $("#period_start_date").val("01");
	            $("#period_end_date").val(last_day_month);
	            $(this).datepicker('setDate', new Date(year, month, 1));
	        }
		});
		function LastDayOfMonth(Year, Month){
		    return(new Date((new Date(Year, Month,1))-1)).getDate();
		}
		
		//Validate order before submitting
		$("#save_changes").click(function(){
			var oTable=$('#generate_order').dataTable({
				"sDom": "<'row'r>t<'row'<'span5'i><'span7'p>>",
				"iDisplayStart": 4000,
				"iDisplayLength": 4000,
				"sPaginationType": "bootstrap",
				"bSort": false,
				'bDestroy':true
			});
			if($(".label-warning").is(':visible')){
				bootbox.alert("<h4>Drugs Error</h4>\n\<hr/><center>Some drugs have a negative resupply quantity !</center>");
			}
			else{
				$("#fmEditOrder").submit();
			}
		});
		
		$(".pack_size").change(function() {
			calculateResupply($(this));
		});
		$(".opening_balance").change(function() {
			calculateResupply($(this));
		});
		$(".quantity_received").change(function() {
			calculateResupply($(this));
		});
		$(".quantity_dispensed").change(function() {
			calculateResupply($(this));
		});
		$(".losses").change(function() {
			calculateResupply($(this));
		});
		$(".adjustments").change(function() {
			calculateResupply($(this));
		});
		$(".physical_count").change(function() {
			calculateResupply($(this));
		});
	});
	function calculateResupply(element) { 
		var row_element = element.closest("tr");
		var opening_balance = parseInt(row_element.find(".opening_balance").attr("value"));
		var quantity_received = parseInt(row_element.find(".quantity_received").attr("value"));
		var quantity_dispensed = parseInt(row_element.find(".quantity_dispensed").attr("value")); 
		var losses = parseInt(row_element.find(".losses").attr("value"));
		var adjustments = parseInt(row_element.find(".adjustments").attr("value"));
		var physical_count = parseInt(row_element.find(".physical_count").attr("value"));
		var resupply = 0;
		if(!(opening_balance + 0)) {
			opening_balance = 0;
		}
		if(!(quantity_received + 0)) {
			quantity_received = 0;
		}
		if(!(quantity_dispensed + 0)) {
			quantity_dispensed = 0;
		} 

		if(!(adjustments + 0)) {
			adjustments = 0;
		}
		if(!(losses + 0)) {
			losses = 0;
		}
		if(!(physical_count + 0)) {
			physical_count = 0;
		} 
		calculated_physical = (opening_balance + quantity_received - quantity_dispensed - losses+ adjustments);
		//console.log(calculated_physical);
		if(element.attr("class") == "physical_count") {
		 resupply = 0 - physical_count;
		 } else {
		 resupply = 0 - calculated_physical;
		 physical_count = calculated_physical;
		 }
		resupply = (quantity_dispensed * 3) - physical_count;
		resupply=parseInt(resupply);
		row_element.find('.label-warning').remove();
		if(resupply<0){
			row_element.find('.col_drug').append("<span class='label label-warning' style='display:block'>Warning! Resupply qty cannot be negative</<span>");
			row_element.find(".resupply").css("background-color","#f89406");
		}
		else{
			row_element.find(".resupply").css("background-color","#fff");
		}
		row_element.find(".physical_count").attr("value", physical_count);
		row_element.find(".resupply").attr("value", resupply);
	}
</script>



<div class="full-content"  style="background:#9CF">
	<div >
		<ul class="breadcrumb">
			<li>
				<a href="<?php echo site_url().'order_management' ?>">Orders</a><span class="divider">/</span>
			</li>
			<li class="active" id="actual_page">
				Edit Details for Order No <?php echo $order_no;?>
			</li>
		</ul>
	</div>	
<form method="post" id="fmEditOrder" action="<?php echo site_url('order_management/save')?>">
	<input type="hidden" name="order_number" value="<?php echo $order_details->id;?>" />
	<input type="hidden" name="facility_id" value="<?php echo $order_details->Facility_Object->facilitycode;?>" />
	<input type="hidden" name="central_facility" value="<?php echo $order_details->Facility_Object->parent;?>" />
	<input type="hidden" name="order_type" value="<?php echo $order_type ?>"/>
	<div class="facility_info">
		<table class="table dataTable" >
			<tbody>
				<tr>
					<th>Order No</th>
					<td><span class="_green">
					<?php 
					$order_types = array(0=>"Central Order",1=>"Aggregated Order",2=>"Satellite Order"); 
					echo $order_no."(".@$order_types[$order_details->Code].")";?></span></td>
					<th width="160px">Facility code:</th>
					<td><span class="_green"><?php echo $order_details -> Facility_Object -> facilitycode;?></span></td>
					</tr>
				<tr>
					<th width="140px">Facility Name:</th>
					<td><span class="_green"><?php echo $order_details -> Facility_Object -> name;?></span></td>
					
				
					<th>Facility Type:</th>
					<td><span class="_green"><?php echo $order_details -> Facility_Object -> Type -> Name;?></span></td>
					</tr>
				<tr>
					<th>District / County:</th>
					<td><span class="_green"><?php echo $order_details -> Facility_Object -> Parent_District -> Name;?> / <?php echo $order_details -> Facility_Object -> County -> county;?></span></td>
					<th>Reporting Period : </th>
					<td colspan="3"><input name="reporting_period" id="reporting_period" type="text" placeholder="Click here to select period" value="<?php echo date('F-Y',strtotime($order_details->Period_Begin)); ?>" disabled="disabled"/></td>
					<input name="start_date" id="period_start_date" type="hidden" value="<?php echo $order_details->Period_Begin;?>">
					<input name="end_date" id="period_end_date" type="hidden" value="<?php echo $order_details->Period_End;?>"></td>
				</tr>
				
			</tbody>
		</table>
	</div>
	<?php
	//check whether this is a satellite or a central order and display the relevant units
	$type = $order_details->Code;
	$unit = "In Units";
	if($type == "1"){
		$unit = "In Packs";
	}
	$header_text = '<thead>
<tr>
<!-- label row -->
<th class="col_drug" rowspan="3">Drug Name</th>
<th class="number" rowspan="3">Pack Size</th> <!-- pack size -->

<th class="number">Beginning Balance</th>
<th class="number">Qty Received</th>

<!-- dispensed_units -->
<th class="col_dispensed_units">Total Qty Dispensed</th>
<!-- dispensed_packs -->
<th class="col_losses_units">Losses (Damages, Expiries, Missing)</th>
<th class="col_adjustments">Adjustments (Borrowed from or Issued out to Other Facilities)</th>
<th class="number">End of Month Physical Count</th>
<th class="number" colspan="2">Quantity to Expire in less than 6 months</th>

<!-- aggr_consumed/on_hand -->
<th class="number">Qty required for Resupply</th>
</tr>
<tr>
<!-- unit row -->
<th>'.$unit.'</th> <!-- balance -->
<th>'.$unit.'</th> <!-- received -->
<th class="col_dispensed_units">'.$unit.'</th>
<!-- dispensed_units -->
<th class="col_dispensed_units">'.$unit.'</th>
<!-- dispensed_packs -->

<th>'.$unit.'</th> <!-- adjustments -->
<th>'.$unit.'</th> <!-- count -->
<th>'.$unit.'</th> <!-- expire -->
<th>mm-yy</th> <!-- expire -->

<!-- aggr_consumed/on_hand -->

<th>'.$unit.'</th> <!-- resupply -->
</tr>
<tr>
<!-- letter row -->
<th>A</th> <!-- balance -->
<th>B</th> <!-- received -->
<th>C</th> <!-- dispensed_units/packs -->
<th>D</th> <!-- losses -->
<th>E</th> <!-- adjustments -->
<th>F</th> <!-- count -->
<th>G</th> <!-- expire -->
<th>H</th> <!-- expire -->
<th>I</th> <!-- count -->

<!-- aggr_consumed/on_hand -->

</tr>
</thead>';
	?>
<div id="commodity-table">
	<table class="table table-bordered table_order_details dataTables" id="generate_order">
		<?php echo $header_text;?>
		<tbody>
			<?php
			$counter = 0;
			foreach($commodities as $commodity){
			$counter++;
			if($counter == 10){
			//echo $header_text;
			$counter = 0;
			}
			?>
			<tr class="ordered_drugs" drug_id="<?php echo $commodity['did'];?>">
				<td class="col_drug"><?php echo $commodity['drug'];?></td>
				<td class="number">
				<input id="pack_size" type="text" value="<?php echo $commodity['pack_size']?>" class="pack_size" readonly="readonly"/>
				</td>
				<td class="number calc_count">
				<input name="opening_balance[]" id="opening_balance_<?php echo $commodity['did'];?>" type="text" class="opening_balance" value="<?php echo $commodity['balance'];?>">
				</td>
				<td class="number calc_count">
				<input name="quantity_received[]" id="received_in_period_<?php echo $commodity['did'];?>" type="text" class="quantity_received" value="<?php echo $commodity['received'];?>">
				</td>
				<!-- dispensed_units-->
				<td class="number col_dispensed_units calc_dispensed_packs  calc_resupply calc_count">
				<input name="quantity_dispensed[]" id="dispensed_in_period_<?php echo $commodity['did'];?>" type="text" class="quantity_dispensed" value="<?php echo $commodity['dispensed_units'];?>">
				</td>
				
				<td class="number col_dispensed_units calc_dispensed_packs  calc_resupply calc_count">
				<input name="losses[]" id="losses_in_period_<?php echo $commodity['did'];?>" type="text" class="losses" value="<?php echo $commodity['losses'];?>">
				</td>
				
				<td class="number calc_count">
				<input name="adjustments[]" id="CdrrItem_10_adjustments" type="text" class="adjustments" value="<?php echo $commodity['adjustments'];?>">
				</td>
				<td class="number calc_resupply col_count">
				<input tabindex="-1" name="physical_count[]" id="CdrrItem_10_count" type="text" class="physical_count" value="<?php echo $commodity['count'];?>">
				</td>
				<!--Expire-->
				<td class="number calc_expire_qty col_exqty">
					<input tabindex="-1" name="expire_qty[]" id="expire_qty_<?php echo$commodity['did'];?>" type="text" class="expire_qty" value="<?php echo $commodity['aggr_consumed'];?>">
				</td>
				<td class="number calc_expire_period col_experiod">
				    <input tabindex="-1" name="expire_period[]" id="expire_period_<?php echo $commodity['did'];?>" type="text" class="expire_period" value="<?php echo $commodity['aggr_on_hand'];?>">
				</td>
				<!-- aggregate -->
				<td class="number col_resupply">
				<input tabindex="-1" name="resupply[]" id="CdrrItem_10_resupply" type="text" class="resupply" value="<?php echo $commodity['resupply'];?>">
				</td>
				<input type="hidden" name="commodity[]" value="<?php echo $commodity['drug'];?>"/>
			</tr>
			<?php }?>
		</tbody>
	</table>
	<br />
	<hr size="1">
	<div class='comments'>
	<?php 
	$has_comment=0;
	foreach($comments as $comment){
		$has_comment=1;
		?>
	
		<span class="label" style="vertical-align: bottom">Comment </span>
		<textarea style="width:98%" rows="3" name="comments"><?php echo $comment->Comment ?></textarea>
		<table class="table table-bordered">
			<thead>
				<tr><th>Last Update</th><th>Made By</th><th>Access Level</th></tr>
			</thead>
			<tbody>
				<tr><td><span class="green"><?php echo date('l d-M-Y h:i:s a', $comment -> Timestamp);?></span></td><td><span class="green"><?php echo $comment -> User_Object -> Name;?></span></td><td><span class="green"><?php echo $comment -> User_Object -> Access -> Level_Name;?></span></td></tr>
			</tbody>
		</table>
		
	
	<?php } 
	if($has_comment==0){
	?>
	
		<span class="label" style="vertical-align: bottom"> Add Comment </span>
		<textarea style="width:98%" rows="3" name="comments"></textarea>
	<?php	
	}
	?>
	<input type="button" id="save_changes" class="btn" value="Save Order" name="save_changes"  />
	</div>
</div>

	<table class=" table table-bordered regimen-table big-table research">
		<thead>
			<tr>
				<th class="col_drug" colspan="2"> Regimen </th>
				<th><input type="button" id="accordion_collapse" value="+"/></span>Patients<span></th>
			</tr>
		</thead>
		<?php
		$counter = 1;
		foreach($regimen_categories as $category){
			?>
			<tbody>
				<?php
				$regimens = $category->Regimens;
				?><tr class="accordion"><th colspan="3"><?php echo $category->Name;?></th></tr><?php
				foreach($regimens as $regimen){

				?>
				<tr>
				<td style="border-right:2px solid #DDD;"><?php echo $regimen -> Regimen_Code;?></td>
				<td regimen_id="<?php echo $regimen -> id;?>" class="regimen_desc col_drug"><?php echo $regimen -> Regimen_Desc;?></td>
				<td regimen_id="<?php echo $regimen -> id;?>" class="regimen_numbers">
				<input name="patient_numbers[]" id="patient_numbers_<?php echo $regimen -> id;?>" type="text" value="<?php if(isset($regimen_totals[$regimen->id])){ echo $regimen_totals[$regimen->id-1]['total'];}?>">
				<input name="patient_regimens[]" value="<?php echo $regimen -> Regimen_Code." | ".$regimen -> Regimen_Desc;?>" type="hidden">
				</td>				 
			   </tr>
			<?php
			}
			?>
		</tbody>
		<?php
		}
		?>
	</table>
	
</form>
</div>