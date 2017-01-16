<?php 
if($table){
?>
<a href="#dialog_<?php echo $table;?>" role="button" id="<?php echo $table;?>" class="btn add" data-toggle="modal"><i class="icon-plus icon-black"></i>New<?php echo "  " . $label;?></a>
<?php }echo $dyn_table;?>
<!--Dialog for Counties-->
<div id="dialog_counties" title="Add County" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="AddCounty" aria-hidden="true">
	   <?php
		$attributes = array('class' => 'input_form');
		echo form_open('admin_management/save/'.$table, $attributes);
		echo validation_errors('<p class="error">', '</p>');
		?>
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
			×
		</button>
		<h3 id="NewDrug">Add County</h3>
	</div>
	<div class="modal-body">
		<div class="max-row">
				<label>County Name</label>
				<input type="text" class="input-large" name="name" required="required"/>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">
			Cancel
		</button>
		<input type="submit" value="Save" class="btn btn-primary " />
	</div>
	<?php echo form_close(); ?>
</div>

<div id="edit_counties" title="Edit County" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="AddCounty" aria-hidden="true">
	   <?php
		$attributes = array('class' => 'input_form');
		echo form_open('admin_management/update/'.$table, $attributes);
		echo validation_errors('<p class="error">', '</p>');
		?>
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
			×
		</button>
		<h3 id="NewDrug">Edit County</h3>
	</div>
	<div class="modal-body">
		<div class="max-row">
				<label>County Name</label>
				<input type="hidden" class="input-large" name="county_id"  id="county_id" required="required"/>
				<input type="text" class="input-large" name="county_name" id="county_name" required="required"/>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">
			Cancel
		</button>
		<input type="submit" value="Save" class="btn btn-primary " />
	</div>
	<?php echo form_close(); ?>
</div>

<!--Dialog for Satellites-->
<div id="dialog_facilities" title="Add Satellite" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="AddCounty" aria-hidden="true">
    <?php
		$attributes = array('class' => 'input_form');
		echo form_open('admin_management/save/'.$table, $attributes);
		echo validation_errors('<p class="error">', '</p>');
	?>
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
			×
		</button>
		<h3 id="NewDrug">Add Satellite</h3>
	</div>
	<div class="modal-body">
		<div class="max-row">
				<label>Facility Name</label>
				<select name="facility" id="satellite" class="input-xlarge">
				</select>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">
			Cancel
		</button>
		<input type="submit" value="Save" class="btn btn-primary " />
	</div>
	<?php echo form_close(); ?>
</div>
<!--Dialog for Districts-->
<div id="dialog_district" title="Add District" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="AddDistrict" aria-hidden="true">
	<?php
		$attributes = array('class' => 'input_form');
		echo form_open('admin_management/save/'.$table, $attributes);
		echo validation_errors('<p class="error">', '</p>');
	?>
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
			×
		</button>
		<h3 id="NewDrug">Add District</h3>
	</div>
	<div class="modal-body">
		<div class="max-row">
				<label>District Name</label>
				<input type="text" class="input-large" name="name" required="required"/>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">
			Cancel
		</button>
		<input type="submit" value="Save" class="btn btn-primary " />
	</div>
	<?php echo form_close(); ?>
</div>

<div id="edit_district" title="Edit District" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="AddDistrict" aria-hidden="true">
	<?php
		$attributes = array('class' => 'input_form');
		echo form_open('admin_management/update/'.$table, $attributes);
		echo validation_errors('<p class="error">', '</p>');
	?>
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
			×
		</button>
		<h3 id="NewDrug">Edit District</h3>
	</div>
	<div class="modal-body">
		<div class="max-row">
				<label>District Name</label>
				<input type="hidden" class="input-large" name="district_id"  id="district_id" required="required"/>
				<input type="text" class="input-large" name="district_name" id="district_name" required="required"/>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">
			Cancel
		</button>
		<input type="submit" value="Save" class="btn btn-primary " />
	</div>
	<?php echo form_close(); ?>
</div>
<!--Dialog for Menus-->
<div id="dialog_menu" title="Add Menu" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="AddDistrict" aria-hidden="true">
	<?php
		$attributes = array('class' => 'input_form');
		echo form_open('admin_management/save/'.$table, $attributes);
		echo validation_errors('<p class="error">', '</p>');
	?>
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
			×
		</button>
		<h3 id="NewDrug">Add Menu</h3>
	</div>
	<div class="modal-body">
		<div class="max-row">
				<label>Menu Name</label>
				<input type="text" class="input-large" name="menu_name" required="required"/>
		</div>
		<div class="max-row">
				<label>Menu URL</label>
				<input type="text" class="input-large" name="menu_url" id="menu_url" required="required"/>
		</div>
		<div class="max-row">
				<label>Menu Description</label>
				<textarea cols="40" rows="5" name="menu_description" id="menu_description"></textarea>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">
			Cancel
		</button>
		<input type="submit" value="Save" class="btn btn-primary " />
	</div>
	<?php echo form_close(); ?>
</div>

<div id="edit_menu" title="Edit Menu" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="AddDistrict" aria-hidden="true">
	<?php
		$attributes = array('class' => 'input_form');
		echo form_open('admin_management/update/'.$table, $attributes);
		echo validation_errors('<p class="error">', '</p>');
	?>
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
			×
		</button>
		<h3 id="NewDrug">Edit Menu</h3>
	</div>
	<div class="modal-body">
		<div class="max-row">
				<label>Menu Name</label>
				<input type="hidden" class="input-large" name="menu_id"  id="edit_menu_id" required="required"/>
				<input type="text" class="input-large" name="menu_name" id="edit_menu_name" required="required"/>
		</div>
		<div class="max-row">
				<label>Menu URL</label>
				<input type="text" class="input-large" name="menu_url" id="edit_menu_url" required="required"/>
		</div>
		<div class="max-row">
				<label>Menu Description</label>
				<textarea cols="40" rows="5" name="menu_description" id="edit_menu_description"></textarea>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">
			Cancel
		</button>
		<input type="submit" value="Save" class="btn btn-primary " />
	</div>
	<?php echo form_close(); ?>
</div>

<!--Dialog for Users-->

<div id="dialog_users" title="New User" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
		
			<?php
			$attributes = array('class' => 'input_form','id'=>'fm_user');
			echo form_open('admin_management/save/'.$table, $attributes);
			echo validation_errors('<p class="error">', '</p>');
			?>
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<h3 id="NewDrug">User details</h3>
			</div>
			<div class="modal-body">
			<div class="msg error" id="msg_error">Fields with <i class="icon-star icon-black"></i> are compulsory</div>
			<br>
			<table style="margin:0 auto" class="table-striped" width="100%">
				<tr><td><strong class="label">Usertype</strong> </td>
					<td>
						<span class="add-on"><i class=" icon-chevron-down icon-black"></i></span>
						<select class="input-xlarge" id="access_level" name="access_level">
							<option selected="selected" value="3">Facility Administrator</option>
						</select>
					</td>
					<td></td>
				</tr>
				
				<tr><td><strong class="label">Full Name</strong></td>
					<td>
						<div >
							<span class="add-on"><i class="icon-user icon-black"></i></span>
							<input type="text" class="input-xlarge" id="fullname" name="fullname" required="" >
							<span class="add-on"><i class="icon-star icon-black"></i></span>
						</div>
					</td><td class="_red"></td></tr>
				<tr><td><strong class="label">Username</strong></td>
					<td><div>
							<span class="add-on"><i class="icon-user icon-black"></i></span>
							<input type="text" name="username" id="username" class="input-xlarge" required=""> 
							<span class="add-on"><i class="icon-star icon-black"></i></span>
						</div>
					</td><td class="_red"></td></tr>
				<tr ><td><strong class="label">Phone number</strong></td>
					<td>
						<div >
							<span class="add-on"><i class="icon-calendar icon-black"></i> </span>
							<input type="text" name="phone" id="phone" class="input-xlarge" placeholder="e.g. +254721111111">
							<span class="add-on"><i class="icon-star icon-black"></i></span>
						</div>
					</td><td></td></tr>
				<tr><td><strong class="label">Email address</strong></td>
					<td>
						<div >
							<span class="add-on"><i class=" icon-envelope icon-black"></i></span>
							<input type="email" name="email" id="email" class="input-xlarge" placeholder="e.g. youremail@example.com">
						</div></td><td class="_red" id="invalid_email">
					</td></tr>
				<tr><td><strong class="label">Facility</strong></td>
					<td>
						<span class="add-on"><i class=" icon-chevron-down icon-black"></i></span>
						<select name="facility" id="facility" class="input-xlarge">
							
						</select>
					</td>
					<td></td>
				</tr>
			</table>
			</div>
			<div class="modal-footer">
			   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
			   <input type="submit" value="Save" class="btn btn-primary " />
			</div>
			</form>
			<?php echo form_close(); ?>
		</div>
<!--Dialog for FAQs-->
<div id="dialog_faq" title="Add FAQ" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="AddFAQ" aria-hidden="true">
	   <?php
		$attributes = array('class' => 'input_form');
		echo form_open('admin_management/save/'.$table, $attributes);
		echo validation_errors('<p class="error">', '</p>');
		?>
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
			×
		</button>
		<h3 id="NewDrug">Add Frequently Asked Questions</h3>
	</div>
	<div class="modal-body">
            <div class="max-row">
				<label>Module</label>
				<!--<input type="text" class="input-large" name="faq_module" required="required"/>-->
				<select class="input-large" name="faq_module">
					<option value="Patients">Patients</option>
					<option value="Inventory">Inventory</option>
					<option value="Orders">Orders</option>
					<option value="Reports">Reports</option>
					<option value="Settings">Settings</option>
				</select>
		</div>
		<div class="max-row">
				<label>Question</label>
				<input type="text" class="input-large" name="faq_question" required="required"/>
		</div>
                <div class="max-row">
				<label>Answer</label>
				<textarea cols="40" rows="6" name="faq_answer" id="faq_answers"></textarea>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">
			Cancel
		</button>
		<input type="submit" value="Save" class="btn btn-primary " />
	</div>
	<?php echo form_close(); ?>
</div>

<div id="edit_faq" title="Edit FAQ" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="AddFAQ" aria-hidden="true">
	   <?php
		$attributes = array('class' => 'input_form');
		echo form_open('admin_management/update/'.$table, $attributes);
		echo validation_errors('<p class="error">', '</p>');
		?>
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
			×
		</button>
		<h3 id="NewDrug">Edit Frequently Asked Questions</h3>
	</div>
	<div class="modal-body">
            <div class="max-row">
				<label>Module</label>
                                <input type="hidden" class="input-large" name="faq_id"  id="faq_id" required="required"/>
				<input type="text" class="input-large" name="faq_module" id="edit_faq_module" required="required"/>
		</div>
		<div class="max-row">
				<label>Question</label>
				<input type="text" class="input-large" name="faq_question" id="edit_faq_question" required="required"/>
		</div>
              <div class="max-row">
				<label>Answer</label>
				<textarea cols="40" rows="6" name="faq_answer" id="edit_faq_answer"></textarea>
		</div>          
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">
			Cancel
		</button>
		<input type="submit" value="Save" class="btn btn-primary " />
	</div>
	<?php echo form_close(); ?>
</div>


<!--Dialog For User Rights-->
<div id="dialog_user_right" title="Add User Right" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="AddCounty" aria-hidden="true">
	<?php
		$attributes = array('class' => 'input_form','id'=>'fm_user');
		echo form_open('admin_management/save/'.$table, $attributes);
		echo validation_errors('<p class="error">', '</p>');
	 ?>
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
			×
		</button>
		<h3 id="NewDrug">Add User Right</h3>
	</div>
	<div class="modal-body">
		<div class="max-row">
				<label>Access Level</label>
				<select class="input-large" name="access_level" id="access_levels">

				</select>
		</div>
		<div class="max-row">
				<label>Menu List</label>
				<select class="input-large" name="menus" id="menus">

				</select>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">
			Cancel
		</button>
		<input type="submit" value="Save" class="btn btn-primary " />
	</div>
	<?php echo form_close(); ?>
</div>

<div id="edit_user_right" title="Edit User Right" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="AddCounty" aria-hidden="true">
	<?php
		$attributes = array('class' => 'input_form','id'=>'fm_user');
		echo form_open('admin_management/update/'.$table, $attributes);
		echo validation_errors('<p class="error">', '</p>');
	 ?>
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
			×
		</button>
		<h3 id="NewDrug">Edit User Right</h3>
	</div>
	<div class="modal-body">
		<div class="max-row">
				<label>Access Level</label>
				<input type="hidden" class="input-large" name="right_id"  id="edit_right_id" required="required"/>
				<select class="input-large" name="access_level" id="edit_access_levels">

				</select>
		</div>
		<div class="max-row">
				<label>Menu List</label>
				<select class="input-large" name="menus" id="edit_menus">

				</select>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">
			Cancel
		</button>
		<input type="submit" value="Save" class="btn btn-primary " />
	</div>
	<?php echo form_close(); ?>
</div>

<div id="edit_nascop" title="Edit Nascop" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="AddCounty" aria-hidden="true">
	   <?php
		$attributes = array('class' => 'input_form');
		echo form_open('admin_management/update/nascop', $attributes);
		echo validation_errors('<p class="error">', '</p>');
		?>
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
			×
		</button>
		<h3 id="NewDrug">Edit NASCOP</h3>
	</div>
	<div class="modal-body">
		<div class="max-row">
				<label>NASCOP URL</label>
				<input type="text" class="input-xlarge" name="nascop_url" id="nascop_url" required="required"/>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">
			Cancel
		</button>
		<input type="submit" value="Save" class="btn btn-primary " />
	</div>
	<?php echo form_close(); ?>
</div>

<script type="text/javascript">
	$(document).ready(function(){
		var base_url="<?php echo base_url(); ?>";
		$("#actual_page").text("<?php echo $actual_page; ?>");
		//Adding Facilities
		$("#facilities").live('click',function(){
		    var link=base_url+"facility_management/getFacilityList";
				$.ajax({
				    url: link,
				    type: 'POST',
				    dataType: "json",
				    async:false,
				    success: function(data) {	
				    	$("#satellite").empty();
				    	$("#satellite").append($("<option></option>").attr("value",'').text('--Select One--'));
				    	$.each(data, function(i, jsondata){
				    		//$("#satellite").append($("<option></option>").attr("value",jsondata.facilitycode).text(jsondata.facilitycode));
				    		$("#satellite").append($("<option></option>").attr("value",jsondata.facilitycode).text(jsondata.name));
				    	});
				    	
				    }
				});
		});
		
		//Adding Users
		$("#users").live('click',function(){
		    var link=base_url+"facility_management/getCurrent";
				$.ajax({
				    url: link,
				    type: 'POST',
				    dataType: "json",
				    success: function(data) {
				    	$("#facility").empty();	
				    	$.each(data, function(i, jsondata){
				    		$("#facility").append($("<option selected='selected'></option>").attr("value",jsondata.facilitycode).text(jsondata.name));
				    	});
				    }
				});
		});
		
		//Adding User Rights
	    $("#user_right").live('click',function(){
		    var link1=base_url+"settings_management/getAccessLevels";
				$.ajax({
				    url: link1,
				    type: 'POST',
				    dataType: "json",
				    success: function(data) {
				    	$("#access_levels").empty();	
				    	$.each(data, function(i, jsondata){
				    		$("#access_levels").append($("<option></option>").attr("value",jsondata.Id).text(jsondata.Access));
				    	});
				    }
				});
				
			 var link2=base_url+"settings_management/getMenus";
				$.ajax({
				    url: link2,
				    type: 'POST',
				    dataType: "json",
				    success: function(data) {
				    	$("#menus").empty();	
				    	$("#menus").append($("<option></option>").attr("value",'').text('--Select One--'));
				    	$.each(data, function(i, jsondata){
				    		$("#menus").append($("<option></option>").attr("value",jsondata.id).text(jsondata.Menu_Text));
				    	});
				    }
				});	
		});
		
		$(".edit").live('click',function(){
			var table=$(this).attr("table");
			if(table=='counties'){
				$("#county_id").val($(this).attr("county_id"));
				$("#county_name").val($(this).attr("county"));
			}else if(table=='district'){
				$("#district_id").val($(this).attr("district_id"));
				$("#district_name").val($(this).attr("district"));
			}else if(table=='menu'){
				$("#edit_menu_id").val($(this).attr("menu_id"));
				$("#edit_menu_name").val($(this).attr("menu_name"));
				$("#edit_menu_url").val($(this).attr("menu_url"));
				$("#edit_menu_description").val($(this).attr("menu_desc"));
			}else if(table=='faq'){
                                $("#faq_id").val($(this).attr("faq_id"));
                                 $("#edit_faq_module").val($(this).attr("faq_module"));
				$("#edit_faq_question").val($(this).attr("faq-question"));
				$("#edit_faq_answer").val($(this).attr("faq_answer"));
                        }else if(table=='user_right'){
				        $("#edit_right_id").val($(this).attr("right_id"))
						var access_id = $(this).attr("access_id");
						var menu_id = $(this).attr("edit_menu_id");
						var link1 = base_url + "settings_management/getAccessLevels";
						$.ajax({
							url : link1,
							type : 'POST',
							dataType : "json",
							success : function(data) {
								$("#edit_access_levels").empty();
								$.each(data, function(i, jsondata) {
									if(access_id == jsondata.Id) {
										$("#edit_access_levels").append($("<option selected='selected'></option>").attr("value", jsondata.Id).text(jsondata.Access));
									} else {
										$("#edit_access_levels").append($("<option></option>").attr("value", jsondata.Id).text(jsondata.Access));
									}
								});
							}
						});

						var link2 = base_url + "settings_management/getMenus";
						$.ajax({
							url : link2,
							type : 'POST',
							dataType : "json",
							success : function(data) {
								$("#edit_menus").empty();
								$("#edit_menus").append($("<option></option>").attr("value", '').text('--Select One--'));
								$.each(data, function(i, jsondata) {
									if(menu_id == jsondata.id) {
										$("#edit_menus").append($("<option selected='selected'></option>").attr("value", jsondata.id).text(jsondata.Menu_Text));
									} else {
										$("#edit_menus").append($("<option></option>").attr("value", jsondata.id).text(jsondata.Menu_Text));
									}
								});
							}
						});
					}else if(table=="nascop"){
						$("#nascop_url").val($(this).attr("nascop_url"));
					}
			   });
		  });
</script>

