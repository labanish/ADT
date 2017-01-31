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
	.merge_drug{
	    color:green;
		font-weight:bold;	
	}
	.unmerge_drug{
	    color:red;
		font-weight:bold;	
	}

	.passmessage {

		display: none;
		background: #00CC33;
		color: black;
		text-align: center;
		height: 20px;
		padding:5px;
		font: bold 1px;
		border-radius: 8px;
		width: 30%;
		margin-left: 30%;
		margin-right: 10%;
		font-size: 16px;
		font-weight: bold;
	}
	.errormessage {
		display: none;
		background: #FF0000;
		color: black;
		text-align: center;
		height: 20px;
		padding:5px;
		font: bold 1px;
		border-radius: 8px;
		width: 30%;
		margin-left: 30%;
		margin-right: 10%;
		font-size: 16px;
		font-weight: bold;
	}

	.color_red{
		color:red;
	}
	.color_blue{
		color:#0072D3;
	}
	#new_drugcode,#edit_drugcode{
		background-color:#CCFFFF;
	}
	.ui-multiselect-menu{
		zoom:1;
		display:none; 
		padding:3px; 
		position:fixed; 
		z-index:10000; 
		text-align:left;
	}
	.ui-multiselect {
		font-size:0.9em;
	}
</style>
<script type="text/javascript">
	$(document).ready(function() {
		$('entry_form').submit(function(){
		  	$(this).find(':submit').attr('disabled','disabled');
		});
		$(".setting_table").find("tr :first").css("width","380px");
		//This loop goes through each table row in the page and applies the necessary modifications
		$.each($(".table_row"), function(i, v) {
			//First get the row id which will be used later
			var row_id = $(this).attr("row_id");
			//This gets the first td element of that row which will be used to add the action links
			var first_td = $(this).find("td:first");
			//Get the width of this td element in integer form (i.e. remove the .px part)
			var width = first_td.css("width").replace("px", "");
			//If the width is less than 200px, extend it to 200px so as to have a more uniform look
			if(width < 200) {
				first_td.css("width", "200px");
			} 
		});
		//Add a hover listener to all rows
		$(".table_row").hover(function() {
			//When hovered on, make the background color of the row darker and show the action links
			$(this).addClass("hovered");
			$(this).find(".actions_panel").css("visibility", "visible");
		}, function() {
			//When hovered off, reset the background color and hide the action links
			$(this).removeClass("hovered");
			$(this).find(".actions_panel").css("visibility", "hidden");
		});

		//When clicked dialog form for new indication pops up
		$("#btn_new_drugcode").click(function(event){ 
            $("#add_drug_mapping").val("0");
			event.preventDefault();
			var request=$.ajax({
		     url: "drugcode_management/add",
		     type: 'POST',
		     dataType: "json"
		    });

		     request.done(function(msg) {
		     	for (var key in msg){
		     		if (msg.hasOwnProperty(key)){
		     			if(key=="drug_units"){
		     				$("#add_drugunit option").remove();
		     				$("#add_drugunit").append("<option value='0'>--Select One--</option>");
		     				for(var y in msg[key]) {
		     					if (msg[key].hasOwnProperty(y)) {
		     						$("#add_drugunit").append("<option value="+msg[key][y].id+">"+msg[key][y].Name+"</option>");
		     					}
		     				}
		     			}
		     			if(key=="generic_names"){
		     				$("#add_genericname option").remove();
		     				$("#add_genericname").append("<option value='0'>--Select One--</option>");
		     				for(var y in msg[key]) {
		     					if (msg[key].hasOwnProperty(y)) {
		     						$("#add_genericname").append("<option value="+msg[key][y].id+">"+msg[key][y].Name+"</option>");
		     					}
		     				}
		     			}

		     			if(key=="doses"){
		     				$("#add_dose_frequency option").remove();
		     				for(var y in msg[key]) {
		     					if (msg[key].hasOwnProperty(y)) {
		     						$("#add_dose_frequency").append("<option value=\""+msg[key][y].Name+"\">"+msg[key][y].Name+"</option>");
		     					}
		     				}
		     			}
		     		}
		     	}
		     	//$("#new_drugcode").dialog("open");
		     });
			request.fail(function(jqXHR, textStatus) {
			  bootbox.alert( "<h4>Faulty Form</h4>\n\<hr/><center>Could not open the form to add new drug code: </center>" + textStatus );
			});
		});

		//Edit user
		$(".edit_user").live('click',function(event){
			event.preventDefault();
			var drugcode_id=this.id;

			var request=$.ajax({
		     url: "drugcode_management/edit",
		     type: 'POST',
		     data: {"drugcode_id":drugcode_id},
		     dataType: "json",

		    });

		    request.done(function(msg) {

		    	for (var key in msg){
		     		if (msg.hasOwnProperty(key)){
		     			if(key=="drug_units"){
		     					$("#drugunit").append("<option value='0'>--Select One--</option>");
		     				for(var y in msg[key]) {
		     					if (msg[key].hasOwnProperty(y)) {

		     						$("#drugunit").append("<option value="+msg[key][y].id+">"+msg[key][y].Name+"</option>");
		     					}
		     				}
		     			}
		     			if(key=="generic_names"){
		     				$("#genericname").append("<option value='0'>--Select One--</option>");
		     				for(var y in msg[key]) {
		     					if (msg[key].hasOwnProperty(y)) {
		     						$("#genericname").append("<option value="+msg[key][y].id+">"+msg[key][y].Name+"</option>");
		     					}
		     				}
		     			}

		     			if(key=="doses"){
		     				for(var y in msg[key]) {
		     					if (msg[key].hasOwnProperty(y)) {
		     						$("#dose_frequency").append("<option value=\""+msg[key][y].Name+"\">"+msg[key][y].Name+"</option>");
		     					}
		     				}
		     			}
		     			var drugname,drugunit,packsize,safety_quantity,genericname,supported_by,none_arv,tb_drug,drug_in_use,comments,dose_frequency,duration,quantity,dose_strength="";

		     			if(key=="drugcodes"){

		     				for(var y in msg[key]) {
		     					if (msg[key].hasOwnProperty(y)) {
		     					 $("#drugcode_id").val(msg[key][y].id);
		     					 $("#drugname").val(msg[key][y].Drug);
							     $("#drugunit").attr("value",msg[key][y].Unit);
							     $("#packsize").attr("value",msg[key][y].Pack_Size);
							     $("#safety_quantity").attr("value",msg[key][y].Safety_Quantity);
							     $("#genericname").attr("value",msg[key][y].Generic_Name);
							     $("#supplied_by").attr("value",msg[key][y].Supported_By);
							     $("#classification").attr("value",msg[key][y].classification);
							     if(msg[key][y].none_arv=="1"){
							     	$("#none_arv").attr("checked",true);
							     }
							     else{
							     	$("#none_arv").attr("checked",false);
							     }
							     if(msg[key][y].Tb_Drug=="1"){
							     	$("#tb_drug").attr("checked",true);
							     }
							     else{
							     	$("#tb_drug").attr("checked",false);
							     }
							     if(msg[key][y].Drug_In_Use=="1"){
							     	$("#drug_in_use").attr("checked",true);
							     }
							     else{
							     	$("#drug_in_use").attr("checked",false);
							     }


							    //Select Family Planning Methods Selected
								var instructions=msg[key][y].instructions;
								if(instructions != null || instructions != " ") {
									var instruction = instructions.split(',');
									for(var i = 0; i < instruction.length; i++) {
										$("select#editinstructions").multiselect("widget").find(":checkbox[value='" + instruction[i] + "']").each(function() {
					                       $(this).click();
					                    });
									}
								}

							     $("#comments").attr("value",msg[key][y].Comment);
							     $("#dose_frequency").attr("value",msg[key][y].Dose);
							     $("#duration").attr("value",msg[key][y].Duration);
							     $("#quantity").attr("value",msg[key][y].Quantity);
							     $("#dose_strength").attr("value",msg[key][y].Strength);
							     $("#edit_drug_mapping").attr("value",msg[key][y].map);
							     if(msg[key][y].map==0){
							     	$(".all_mappings").show();
							     	$(".semi_mappings").hide();
							     	$(".semi_mappings").attr("name","");
							     	$(".all_mappings").attr("name","drug_mapping");
							     	$(".all_mappings").attr("value",msg[key][y].map);
							     }else{
							     	$(".all_mappings").hide();
							     	$(".semi_mappings").show();
							     	$(".all_mappings").attr("name","");
							     	$(".semi_mappings").attr("name","drug_mapping");
							     	$(".semi_mappings").attr("value",msg[key][y].map);
							     }
		     					}


		     				}
		     			}
		     		}
		     	}


		     	$("#edit_drugcode").dialog("open");

		    });

		    request.fail(function(jqXHR, textStatus) {
			  bootbox.alert( "<h4>Retrieval Error</h4>\n\<hr/><center>Could not retrieve facility information:</center> " + textStatus );
			});
		});
		var opts = {
			"closeButton" : true,
			"positionClass" : "toast-bottom-right",
		};
		// select instructions
        $('.multiselect').multiselect({
        	minWidth:500
        }).multiselectfilter();
        
        //function to check drugs for merging
        var arr = [];
	    $('body').on('click', 'table tr .drugcodes',function(){
	        var value = $(this).val(),
	            isChecked = $(this).is(':checked'),
	            index = arr.indexOf(value);

	        if (isChecked && index === -1) {
	            arr.push(value);
	        } else if (!isChecked && index !== -1){
	            arr.splice(index, 1);
	        }
	    });

		//Check the drugcodes selected when merge is clicked
		$(".merge_drug").live('click',function(){
                    var _this=this;
			 bootbox.confirm("<h4>Save</h4>\n\<hr/><center>Are you sure?</center>",
                                function(res){
                                if(res===true){
			        var counter=0;
					var primary_drug_merge_id = $(_this).attr("id");
					var base_url='<?php echo base_url();?>';
					counter=arr.length;
		            if(counter>0){
						$.ajax({
			                url: base_url+'drugcode_management/merge/'+primary_drug_merge_id,
			                type: 'POST', 
			                data: { 'drug_codes': arr },      
			                success: function(data) {
			                     //Refresh Page
			                     location.reload(); 
			                },
			                error: function(){
			                	toastr.error('failed merged!', 'Merging', opts);
			                }
			           });
		           }else{
		           	  toastr.error('no drug selected!', 'Merging', opts);
		           }
		           return true;
				} else {
					return false;
				}
		});
		});
		//Disable multiple drugs
		$("#disable_mutliple_drugs").live("click",function(event){
			event.preventDefault();
			var count_checked = $("input:checkbox[name='drugcodes']:checked").size();
			var drug_selected = $("input:checkbox[name='drugcodes']:checked")
			if(count_checked>0){
				var test = confirm("Are You Sure?");
				if(test) {//User clicks yes
					var drug_codes=new Array();
					var base_url='<?php echo base_url();?>';
					$("input:checkbox[name='drugcodes']:checked").each(function(){
					    drug_codes.push($(this).val());
		            });
		            $.ajax({
			                url: base_url+'drugcode_management/disable',
			                type: 'POST', 
			                data: { 'drug_codes': drug_codes,'multiple':'1' },      
			                success: function(data) {
			                	//Refresh Page
			                    location.reload(); 
			                },
			                error: function(){
			                	toastr.error('Failed to disable!', 'Disabling', opts);
			                }
			           });
		            
		            
				}else {
					return false;
				}
			}else{
				bootbox.alert("<h4>Selection Error</h4>\n\<hr/><center>You have not selected any drugs to disable</center>");
			}
			
			
		});
		
		//Enable multiple drugs
		$("#enable_mutliple_drugs").live("click",function(event){
			event.preventDefault();
			var count_checked = $("input:checkbox[name='drugcodes']:checked").size();
			var drug_selected = $("input:checkbox[name='drugcodes']:checked")
			if(count_checked>0){
				var test = confirm("Are You Sure?");
				if(test) {//User clicks yes
					var drug_codes=new Array();
					var base_url='<?php echo base_url();?>';
					$("input:checkbox[name='drugcodes']:checked").each(function(){
					    drug_codes.push($(this).val());
		            });
		            $.ajax({
			                url: base_url+'drugcode_management/enable',
			                type: 'POST', 
			                data: { 'drug_codes': drug_codes,'multiple':'1' },      
			                success: function(data) {
			                	//Refresh Page
			                    location.reload(); 
			                },
			                error: function(){
			                	toastr.error('failed to enable!', 'Enabling', opts);
			                }
			           });
				}else {
					return false;
				}
			}else{
				bootbox.alert("<h4>Selection Error</h4>\n\<hr/><center>You have not selected any drugs to enable</center>");
			}
			
			
		});

		//count to check which message to display
        var count='<?php echo @$this -> session -> userdata['message_counter'];?>';
        var message='<?php echo @$this -> session -> userdata['message'];?>';	
	});

	//process new instructions
	function processNewInstructions(){  
	    var instructions = $("select#newinstructions").multiselect("getChecked").map(function() {
			return this.value;
		}).get();
		$("#new_instructions_holder").val(instructions);
	}
    
    //process edit instructions
    function processEditInstructions(){  
	    var instructions = $("select#editinstructions").multiselect("getChecked").map(function() {
			return this.value;
		}).get();
		$("#edit_instructions_holder").val(instructions);
	}

</script>

<div id="view_content">
	<div class="passmessage"></div>
    <div class="errormessage"></div>
	
	<div class="container-fluid">
	  <div class="row-fluid">

	    <!-- Side bar menus -->
	    <?php echo $this->load->view('settings_side_bar_menus_v.php'); ?>
	    <!-- SIde bar menus end -->
	    <div class="span12 span-fixed-sidebar">
	      <div class="hero-unit">
			<a href="#new_drugcode" role="button" id="btn_new_drugcode" class="btn" data-toggle="modal"><i class="icon-plus icon-black"></i>New Drug Code</a>      	
	      	<button id="disable_mutliple_drugs" class="btn btn-danger">Disable selected drugs</button>
	      	<button id="enable_mutliple_drugs" class="btn btn-info">Enable selected drugs</button>
	      	<a href="#md_bulk_mapping" role="button" class="btn btn-success" id="btn_bulk_mapping_new"  data-toggle="modal">Bulk mapping</a>
	      	<?php echo $drugcodes;?>
	      	
	      </div>

	      
	    </div><!--/span-->
	  </div><!--/row-->


	</div><!--/.fluid-container-->
	<!-- Add new drug -->
	<div style="width:70%;margin-left:-35%;" id="new_drugcode" title="Add New Drug" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="NewDrug" aria-hidden="true">
		<?php
			$attributes = array(
				            'id' => 'entry_form',
				            'onsubmit'=>'return processNewInstructions()');
			echo form_open('drugcode_management/save', $attributes);

		?>
		<div class="modal-header">
		    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		    <h3 id="NewDrug">Drug details</h3>
		</div>
		<div class="modal-body">
		<div class="span5">
			
			<table style="margin-top:54px">
				<tr><td>
					<strong class="label">Drug ID</strong></td><td><input type="text" class="input-xlarge" style="width:320px;font-size:13px" id="add_drugname" name="drugname" required=""/></td></tr>
				<tr><td><strong class="label">Unit</strong></td>
					<td>
						<select id="add_drugunit" class="input-small" name="drugunit" required="">
							
						</select>		
					</td>
				</tr>
				<tr><td><strong class="label">Packsize</strong></td><td><input type="text" class="input-small" id="add_packsize" name="packsize" required=""/></td></tr>
				<!--<tr><td><strong class="label">Safety Quantity</strong></td><td><input type="text" class="input-small" id="add_safety_quantity" name="safety_quantity" /></td></tr>-->
				<input type="hidden" class="input-small" id="add_safety_quantity" name="safety_quantity" />
				<tr><td><strong class="label">Generic Name</strong></td>
					<td>
						<select class="input-xlarge" id="add_genericname" name="genericname">
							
						</select>
					</td>
				</tr>
				<tr><td><strong class="label">Supplied By</strong></td>
					<td>
						<select class="input-large" id="add_supplied_by" name="supplied_by">
							<option value='0'>-Select One--</option>
							<?php
							  foreach($suppliers as $supplier){
							  	echo "<option value='".$supplier['id']."'>".$supplier['Name']."</option>";
							  }
							?>
						</select>
					</td>
				</tr><td><strong class="label">Classification</strong></td>
				<td>
						<select class="input-xlarge" id="add_classification" name="classification">
							<?php
							foreach ($classifications as $classification) {
								echo "<option value='".$classification['id']."'>".$classification['Name']."</option>";
							}
							?>
						</select>
					</td>
				<tr>
					<td colspan="2"><hr size="1"></td>
				</tr>
				
				<tr>
					<td colspan="2">
						<label class="checkbox"><input type="checkbox" id="add_none_arv" name="none_arv" />Non ARV Drug</label> 
						<label class="checkbox" ><input type="checkbox" id="add_tb_drug" name="tb_drug" /><span class="color_red"> TB Drug</span></label> 
						<!--<label class="checkbox"><input type="checkbox" id="add_drug_in_use" name="drug_in_use"/> Drug In Use?</label>-->
						<input type="hidden" id="add_drug_in_use" name="drug_in_use"/>
					</td>
					<td></td></tr>
			</table>
		</div>		
		<div class="span4">
			
				<legend class="color_blue">Standard Dispensing Information</legend>
				<table class="tbl_new_drug">
					<tr><td><strong class="label">Dose Strength</strong></td>
						<td>
							<select class="input-small" name="dose_strength" id="add_dose_strength">
								<option value="1">mg</option>
								<option value="2">g</option>
								<option value="3">ml</option>
								<option value="4">l</option>
							</select>
						</td>
					</tr>
					<tr><td><strong class="label">Dose</strong></td>
						<td>
							<select class="input" id="add_dose_frequency" name="dose_frequency">
								
							</select>
						</td>
					</tr>
					<tr>
						<td><strong class="label">Duration</strong></td><td><input type="text" class="input-small" id="add_duration" name="duration"/></td>
					</tr>
					<tr>
						<td><strong class="label">Quantity</strong></td><td><input type="text" class="input-small" name="quantity" id="add_quantity" /></td>
					</tr>
                    <tr>
                       <td><strong class="label">Comments</strong></td>
					   <td><textarea id="comments" name="comments" rows="2"></textarea></td>
					</tr>
					<tr>
						<td><strong class="label">Mappings</strong></td>
					<td>
							<select class="input input-xlarge" id="add_drug_mapping" name="drug_mapping">
								<option value='0'>-Select One--</option>
								<?php
							      foreach ($edit_mappings as $map) {
								     echo "<option value='".$map['id']."'>".$map['name']."<b>(".$map['packsize'].")</b>"."</option>";
							      }
							     ?>
							</select>
							<span class="small_text">e.g. Name[abbreviation]strength|formulation(packsize)</span>
						</td>
					</tr>
					<tr>
					   <td>
					    <strong class="label">Instructions</strong>
					   </td>
                       <td>
                        <input type="hidden" id="new_instructions_holder" name="instructions_holder" />
                        <select class="multiselect" multiple="multiple" id="newinstructions"  name="instructions">
								<?php
                                   foreach ($instructions as $instruction ){
                                       echo "<option value='".$instruction['id']."'>"." ".$instruction['name']."</option>";
                                   }
                                ?>
							</select>
						</td>
					</tr>
				</table>
			
			</div>
		</div>
		<div class="modal-footer">
		   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		   <input type="submit" value="Save" class="btn btn-primary " />
		</div>
		
		<?php echo form_close() ?>
	</div>
	
	<!-- Edit drugcode -->
	<div style="width:70%;margin-left:-35%;" id="edit_drugcode" title="Edit Drug" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="NewDrug" aria-hidden="true">
		<?php
			$attributes = array(
				            'id' => 'entry_form',
				            'onsubmit'=>'return processEditInstructions()');
			echo form_open('drugcode_management/update', $attributes);

		?>
		<div class="modal-header">
		    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		    <h3 id="NewDrug">Drug details</h3>
		</div>
		<div class="modal-body">
			<div class="span5">
			
			<table style="margin-top:54px">
				<tr><td>
					<input type="hidden" name="drugcode_id" id="drugcode_id" class="input">
					<strong class="label">Drug ID</strong></td>
					<td><input type="text" class="input-xlarge" style="width:320px;font-size:13px" id="drugname" name="drugname" required=""/></td></tr>
				<tr><td><strong class="label">Unit</strong></td>
					<td>
						<select id="drugunit" class="input-small" name="drugunit" required="">
							
						</select>		
					</td>
				</tr>
				<tr><td><strong class="label">Packsize</strong></td><td><input type="text" class="input-small" id="packsize" name="packsize" required=""/><input type="hidden" class="input-small" id="safety_quantity" name="safety_quantity" /></td></tr>
				<!--<tr><td><strong class="label">Safety Quantity</strong></td><td><input type="text" class="input-small" id="safety_quantity" name="safety_quantity" /></td></tr>-->
				<tr><td><strong class="label">Generic Name</strong></td>
					<td>
						<select class="input-xlarge" id="genericname" name="genericname">
				
						</select>
					</td>
				</tr>
				<tr><td><strong class="label">Supplied By</strong></td>
					<td>
						<select class="input-large" id="supplied_by" name="supplied_by">
							<option value='0'>-Select One--</option>
							<?php
							  foreach($suppliers as $supplier){
							  	echo "<option value='".$supplier['id']."'>".$supplier['Name']."</option>";
							  }
							?>
						</select>
					</td>
				</tr>
				<tr><td><strong class="label">Classification</strong></td>
					<td>
						<select class="input-xlarge" id="classification" name="classification">
							<?php
							foreach ($classifications as $classification) {
								echo "<option value='".$classification['id']."'>".$classification['Name']."</option>";
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2"><hr size="1"></td>
				</tr>
				<tr>
					<td colspan="2">
						<label class="checkbox"><input type="checkbox" id="none_arv" name="none_arv" />Non ARV Drug</label> 
						<label class="checkbox" ><input type="checkbox" id="tb_drug" name="tb_drug" /><span class="color_red"> TB Drug</span></label> 
						<!--<label class="checkbox"><input type="checkbox" id="drug_in_use" name="drug_in_use"/> Drug In Use?</label>-->
						<input type="hidden" id="drug_in_use" name="drug_in_use"/>
					</td>
					<td></td></tr>
			</table>
		</div>		
		<div class="span4">
			
				<legend class="color_blue">Standard Dispensing Information</legend>
				<table class="tbl_new_drug">
					<tr><td><strong class="label">Dose Strength</strong></td>
						<td>
							<select class="input-small" name="dose_strength" id="dose_strength">
								<option value="1">mg</option>
								<option value="2">g</option>
								<option value="3">ml</option>
								<option value="4">l</option>
							</select>
						</td>
					</tr>
					<tr><td><strong class="label">Dose</strong></td>
						<td>
							<select class="input" id="dose_frequency" name="dose_frequency">
								
							</select>
						</td>
					</tr>
					<tr>
						<td><strong class="label">Duration</strong></td><td><input type="text" class="input-small" id="duration" name="duration"/></td>
					</tr>
					<tr>
						<td><strong class="label">Quantity</strong></td><td><input type="text" class="input-small" name="quantity" id="quantity" /></td>
					</tr>
					<tr><td><strong class="label">Comments</strong></td>
						<td><textarea id="comments" name="comments" rows="2"></textarea></td>
					</tr>
					<tr>
						<td><strong class="label">Mappings</strong></td>
						<td>
							<select class="input all_mappings" id="edit_drug_mapping" name="drug_mapping">
								<option value='0'>-Select One--</option>
								<?php
								  foreach ($edit_mappings as $map) {
								     echo "<option value='".$map['id']."'>".$map['name']."<b>(".$map['packsize'].")</b>"."</option>";
							      }
							      ?>
							</select>
							<select class="input semi_mappings" id="edit_drug_mapping" name="drug_mapping">
								<option value='0'>-Select One--</option>
								<?php
								  foreach ($mappings as $map) {
								     echo "<option value='".$map['id']."'>".$map['name']."<b>(".$map['packsize'].")</b>"."</option>";
							      }
							      ?>
							</select>
							<span class="small_text">e.g. Name[abbreviation]strength|formulation(packsize)</span>
						</td>
					</tr>
					<tr>
					   <td>
					    <strong class="label">Instructions</strong>
					   </td>
                       <td>
                        <input type="hidden" id="edit_instructions_holder" name="instructions_holder" />
                        <select class="multiselect input-xlarge"  multiple="multiple" id="editinstructions" name="instructions">
								<?php
                                   foreach ($instructions as $instruction ){
                                       echo "<option value='".$instruction['id']."'>"." ".$instruction['name']."</option>";
                                   }
                                ?>
							</select>
						</td>
					</tr>
				</table>
			
			</div>
		</div>
		<div class="modal-footer">
		   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		   <input type="submit" value="Save" class="btn btn-primary " />
		</div>
		<?php echo form_close() ?>
	</div>
	
	<!-- Modal for bulk drugs mapping -->
	<form id="fmBulkMapping" action="" style="width:100% !important">
		<div id="md_bulk_mapping" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="BulkMapping" aria-hidden="true" style="width: 100% !important">
			<div class="modal-header">
			    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			    <h3 id="BulkMapping">Bulk Drugs  Mapping [ Drugs details ]</h3>
			</div>
			<div class="modal-body">
				<div id="loadingD" style="display: none; width: 60%; position:fixed; margin-bottom: 15px; text-align: center;"><img style="width: 30px;" src="<?php echo base_url();?>/assets/images/loading_spin.gif"></div>
				<table class="table table-bordered table-striped" id="tbl_bulk_mapping"> 
					<thead>
						<tr><th style="width: 6%">#</th><th style="width:60%">Drugs</th><th>Sync Drugs</th></tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
			
			<div class="modal-footer">
			   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
			   <input type="button" value="Map drugs" id="btn_submit_bulk" class="btn btn-primary " />
			</div>
		</div>
	</form>
	<!-- Modal for bulk drugs mapping  End-->
</div>

<script type="text/javascript">
	$(document).ready(function(){
		/*Prevent Double Submission*/
		jQuery('form').on('submit',function(){
			$(this).find(':submit').prop('disabled', true);
		});
		ap = 0;
		//Bulk Mapping Modal
		$("#btn_bulk_mapping_new").on("click",function(){
			$("#md_bulk_mapping").css("width","60%");
			$("#md_bulk_mapping").css("margin-left","-30%");
			$("#tbl_bulk_mapping tbody > tr ").remove();
			
			//get Non mapped regimens
			$.ajax({
	                url: base_url+'drugcode_management/getNonMappedDrugs',
	                type: 'GET', 
	                dataType:'json',
	                data: { 'param': '0' },      
	                success: function(data) {	
	                	var counter = 0; 
	                	var total_drugs = data.non_mapped_drugs.length;
	                	var total_sync = data.sync_drugs.length;
	                	if(ap==0){
	                		$("#tbl_bulk_mapping thead").append("<tr id='0' map_id=''><td>0</td><td class='truncate' style='font-style:italic'> Name [Unit] - strength ( packsize ) </td><td style='font-style:italic'>Name[abbreviation]strength|formulation(packsize)</td></tr>");
	                		ap=1;
	                	}
	                	for (var i = 0; i <total_drugs ; i++) {
	                		var c = i+1;
	                		var name = data.non_mapped_drugs[i]['Drug'];
							var unit = data.non_mapped_drugs[i]['drug_unit'];
							var dose = data.non_mapped_drugs[i]['Dose'];
							var strength = data.non_mapped_drugs[i]['Strength'];
							var packsize = data.non_mapped_drugs[i]['Pack_Size'];
							var id = data.non_mapped_drugs[i]['id'];
							$("#tbl_bulk_mapping tbody").append("<tr id='"+id+"' map_id=''><td>"+c+"</td><td class='truncate'>"+name+"[" +unit+ "] - "+strength+" ( "+packsize+" ) </td><td></td></tr>");
	                	}
	                	$("#edit_drug_mapping").clone ().appendTo ("#tbl_bulk_mapping tbody > tr >td:last-child").attr ("class", "sel_bulk_map");
	                	// appendRows(counter,total_drugs,data.non_mapped_drugs);
	                   
	                },
	                error: function(){
	                	toastr.error('failed loading Drugs!', 'Mapping');
	                }
	           });
			
		});
		
		//Select bulk map change
		$(".sel_bulk_map").live("change",function(){
			var map_id = $(this).attr("value");
			$(this).closest('tr').attr("map_id",map_id);
		});
		
		
		//Submit bulk mapped regimens
		$("#btn_submit_bulk").live("click",function(){
			$("#loadingD").css("display","block")
			var data = $("#tbl_bulk_mapping tbody>tr");
			var counter = 0;
			var total = data.length;
			mappDrugs(counter,total,data);
			
		});
	})
	
	function appendRows(counter,total,data){
		var name = data[counter]['Drug'];
		var unit = data[counter]['drug_unit'];
		var dose = data[counter]['Dose'];
		var strength = data[counter]['Strength'];
		var packsize = data[counter]['Pack_Size'];
		var id = data[counter]['id'];
		if(counter<(total-1)){
			var c = counter+1;
			$("#tbl_bulk_mapping tbody").append("<tr id='"+id+"' map_id=''><td>"+c+"</td><td class='truncate'>"+name+"[" +unit+ "] - "+strength+" ( "+packsize+" ) </td><td></td></tr>");
	    	appendRows(c,total,data);
		}else{
			$("#edit_drug_mapping")
			  .clone ()
			  .appendTo ("#tbl_bulk_mapping tbody > tr >td:last-child")
			  .attr ("class", "sel_bulk_map");
		}
		
	}
	
	function mappDrugs(counter,total,data){
		var map_id = $(data[counter]).attr("map_id");
		var id = $(data[counter]).attr('id');
		if(counter<(total-1)){
			var c = counter+1;
			if(map_id!=""){//if regimen mapped, update regimens details
				$.ajax({
	                url: base_url+'drugcode_management/updateBulkMapping',
	                type: 'POST', 
	                dataType:'html',
	                data: { 'drug_id': id, "map_id":map_id },      
	                success: function(msg) {	
	                	mappDrugs(c,total,data);
	                },
	                error: function(){
	                	toastr.error('failed loading Drugs!', 'Mapping');
	                }
	           });
				
			}else{
				mappDrugs(c,total,data);
			}
			
		}else{
			$("#loadingD").html("<span class='alert alert-info'> All drugs have been successfully mapped !</span>");
			setTimeout(function(){
				//Refresh Page
	            location.reload(); }, 3000);
		}
	}
</script>