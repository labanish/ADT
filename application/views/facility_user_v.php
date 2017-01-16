<?php

	foreach($facilities as $facility){
		
	}

?>
<style type="text/css">
	.actions_panel {
		width: 200px;
		margin-top: 5px;
	}
	.hovered td {
		background-color: #E5E5E5 !important;
	}
	a{
		text-decoration: none;
	}
	.enable_user{
		color:green;
		font-weight:bold;
	}
	.disable_user{
		color:red;
		font-weight:bold;
	}
	.edit_user{
		color:blue;
		font-weight:bold;
	}
	
	#facility_form(
		margin-top: 5px;
		border:1px solid #DDD;
		padding:20px;
		margin-left:500px;
		margin-right:200px;
	)
	.submit-button .Save{
		display:none;
	}
	.ui-multiselect-menu{
	 zoom:0.79999;
	}
	legend {
	 font-size: 12px;
	}	
</style>

<script type="text/javascript">
$(document).ready(function() {
$("#facility_type").attr("value","<?php echo @$facility['facilitytype'];?>");
$("#district").attr("value","<?php echo @$facility['district'];?>");
$("#central_site").attr("value","<?php echo @$facility['parent'];?>");
$("#county").attr("value","<?php echo @$facility['county'];?>");
$("#sms_map").val("<?php echo $facility['map'];?>");
});
</script>
	<div class="container-fluid">
	  <div class="row-fluid row">
		 <!-- Side bar menus -->
	    <?php echo $this->load->view('settings_side_bar_menus_v.php'); ?>
	    <!-- SIde bar menus end -->

	    <div class="span12 span-fixed-sidebar" >
	      	<div class="hero-unit" style="padding-bottom:20px;background: rgb(184, 255, 184);">
				<?php echo validation_errors('<p class="error">', '</p>');?>
				

				<?php
					$attributes = array('class' => 'input_form');
					echo form_open('facility_management/update', $attributes);
					echo validation_errors('<p class="error">', '</p>');
				?>
	    		<div id="facility_form" title="Facility Information" style="zoom:0.8">
	    			
		      		
						<fieldset>
	    					<h3>Facility Details</h3>
							<table class="facility_basic_info" style="width:70%;">
								<tr><td><label for="facility_code"><strong class="label">Organization Code/MFL No</strong></label></td>
									<td>
										<input type="hidden" name="facility_id" id="facility_id" class="input" value="<?php echo @$facility['id'];?>" >
										<input type="hidden" name="facility_cod" id="facility_cod" class="input" value="<?php echo @$facility['facilitycode'];?>" >
										<span name="facility_code" id="facility_code"  class="input-large uneditable-input" ><?php echo @$facility['facilitycode'];?></span>
										
									</td>
								</tr>
								<tr><td><strong class="label">Name of Organization / System</strong></td>
									<td><input type="text" name="facility_name" id="facility_name" class="input-xlarge span12" style="color:green" value="<?php echo @$facility['name'];?>" >
									</td>
								</tr>
								<tr><td><strong class="label">Adult Age</strong></td>
									<td><input type="text" name="adult_age" id="adult_age" class="input-small" value="<?php echo @$facility['adult_age'];?>">
									</td>
								</tr>
								<tr><td><strong class="label">Maximum Patients Per Weekday</strong></td>
									<td><input type="text" name="weekday_max" id="weekday_max" class="input-small" value="<?php echo @$facility['weekday_max'];?>"></td>
								</tr>
								<tr><td><strong class="label">Maximum Patients Per Weekend</strong></td>
									<td><input type="text" name="weekend_max" id="weekend_max" class="input-small" value="<?php echo @$facility['weekend_max'];?>"></td>
								</tr>
										<tr><td><strong class="label">Lost to follow_up days</strong></td>
								<td><input type="text" name="lost_to_follow_up" id="lost_to_follow_up" class="input-small" style="color:green" value="<?php echo @$facility['lost_to_follow_up'];?>" >
									</td>
								</tr>
								<tr><td><strong class="label">Facility Type</strong></td>
									<td><select class="input-xlarge" id="facility_type" name="facility_type">
											<?php foreach($facility_types as $facility_type){?>
											<option value="<?php echo $facility_type['id'];?>"><?php echo @$facility_type['Name'];?></option>
											<?php }?>
										</select>
									</td>
								</tr>
							
								<tr><td><strong class="label">District</strong></td>
									<td><select class="input-xlarge" id="district" name="district">
											<?php foreach($districts as $district){?>
											<option value="<?php echo $district['id'];?>"><?php echo $district['name'];?></option>
											<?php }?>
										</select>
									</td>
								</tr>

							   <tr><td><strong class="label">County</strong></td>
									<td><select class="input-xlarge" id="county" name="county">
											<?php foreach($counties as $county){?>
											<option value="<?php echo $county['id'];?>"><?php echo $county['county'];?></option>
											<?php }?>
										</select>
									</td>
								</tr>
								
								 <tr><td><strong class="label">Central Site</strong></td>
									<td><select class="input-xlarge" id="central_site" name="central_site">
											<?php foreach($sites as $site){
												if($site['name'] !=""){
													?>
											<option value="<?php echo $site['facilitycode'];?>"><?php echo $site['name'];?></option>
											<?php } }?>
										</select>
									</td>
								</tr>
								<tr><td><strong class="label">Facility Phone No</strong></td>
									<td><input type="text" name="phone_number" id="phone_number" class="input-xlarge" value="<?php echo @$facility['phone'];?>"></td>
								</tr>
								<tr>
								    <td><strong class="label">Does Facilty want to send sms to patients?</strong></td>
									<td>
									<select name="sms_map" id="sms_map">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
									</select>
									</td>
								</tr>
							<?php $supported_by=$facility['supported_by']; ?>

							</table>
							<p>&nbsp;</p>
							<hr size="2" style="border-top: 1px solid #000;">
							<!--<div class="span3">
								<fieldset>
									<legend style="color:red">Client Supported By</legend>
									
									  <?php foreach ($supporter as $support) {
									  	?>
									  	<label class="radio">
										  	<input type="radio" name="supported_by" value="<?php echo $support->id?>" id="<?php echo $support->id?>" <?php if($supported_by==$support->id){?> checked="checked"<?php } ?>>
										    <?php echo $support->Name ?> Sponsorship
										  </label> 
									  	<?php
									  }	
									  ?>

								</fieldset>
							</div>-->

							<div class="span4">
								<fieldset>
									<legend style="color:red">Services offered at the facility</legend>
									<label class="checkbox">
									  <input type="checkbox" id="art_service" name="art_service" <?php if(@$facility['service_art']==1){?> checked <?php } ?>>
									 ART
									</label>
									<label class="checkbox">
									  <input type="checkbox" id="pmtct_service" name="pmtct_service" <?php if(@$facility['service_pmtct']==1){?> checked <?php } ?>>
									 PMTCT
									</label>
									<label class="checkbox">
									  <input type="checkbox" id="pep_service" name="pep_service" <?php if(@$facility['service_pep']==1){?> checked <?php } ?>>
									 PEP
									</label>
								</fieldset>
							</div>
							<!--<div class="span2">
								<fieldset>
									<legend style="color:red">Client Supplied By</legend>
									<label class="radio">
									  	<input type="radio" name="supplied_by" value="1" id="supply_1" <?php if(@$facility['supplied_by']==1){?> checked="checked"<?php } ?>>
									     KEMSA
									</label>
									<label class="radio">
									  	<input type="radio" name="supplied_by" value="2" id="supply_2" <?php if(@$facility['supplied_by']==2){?> checked="checked"<?php } ?>>
									     Kenya Pharma
									</label> 
								</fieldset>
							</div>-->					
						</fieldset>
						<input type="submit" class="btn btn-warning" value="Save">
				</div>
	    		</form>
			</div>
			<div id="loading" style="text-align:center;display:none"><img width="120px" src="<?php echo base_url().'assets/images/loading.gif' ?>"></div> 
			    
	    </div><!--/span-->
	  </div><!--/row-->
	</div><!--/.fluid-container-->
	
</div>