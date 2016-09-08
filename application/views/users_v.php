<style type="text/css">
	table {
	 table-layout:auto; 
	}

	.ui-multiselect-menu {
	    display: none;
	    margin-left: 15px;
	    position: static; 
	    text-align: left;
	   	zoom: 0.8;
	}

    .ui-multiselect-header{
		zoom:0.9;
	}

</style>
<script type="text/javascript">

	function triggetmultiselect(){
	    $('.sync_facility').multiselect().multiselectfilter();
	}

	$(document).ready(function() {
		$('#user_form').on('show', function(){
        	triggetmultiselect();
   		 });
		
		$("#btn_save_user").live('click',function(event){
			event.preventDefault();
			var fullname=$("#fullname").attr("value");
			var username=$("#username").attr("value");
			var phone=$("#phone").attr("value");
			var email=$("#email").attr("value");
			var filter = /^[0-9-+]+$/;
			
			var atpos=email.indexOf("@");
			var dotpos=email.lastIndexOf(".");

			//Order sites
			var user_facilities = $("select#user_facilities").multiselect("getChecked").map(function() {
					return this.value;
			}).get();
			$("#user_facilities_holder").val(user_facilities);
			
			
			if($.trim(fullname)=="" || $.trim(username)==""){
				$("#msg_error").text("Some fields are missing !");
			}
			else if ($.trim(username)!="" && $.trim(username).length<2 ){
				$("#msg_error").text("The Username field must be at least 2 characters in length!");
			}
			else if ($.trim(user_facilities) ==""){
				$("#msg_error").text("Select Order Sites for user");
			}
			else{
				$("#msg_error").text("");
				
				if($.trim(phone)=="" && $.trim(email)==""){
					$("#msg_error").text("Please enter phone number and/or email address!");
				}
				//Check phone number
				else if($.trim(phone)!="" && (!filter.test(phone) || phone.length<6 )){
					$("#msg_error").text("Invalid phone number !");
				}
				//Check email
				else if($.trim(email)!="" ){
					$("#msg_error").text("");
					if (atpos<1 || dotpos<atpos+2 || dotpos+2>=email.length){
					  $("#msg_error").text("Invalid email address !");
					  
					}
					else{
						$("#fm_user").submit();
					}
				}
				
				else{
					$("#fm_user").submit();
				}
			}
			
			
		});
		
		$("#btn_save_edit_user").live('click',function(event){
			event.preventDefault();
			var fullname=$("#e_fullname").attr("value");
			var username=$("#e_username").attr("value");
			var phone=$("#e_phone").attr("value");
			var email=$("#e_email").attr("value");
			var filter = /^[0-9-+]+$/;
			
			var atpos=email.indexOf("@");
			var dotpos=email.lastIndexOf(".");
			
			if($.trim(fullname)=="" || $.trim(username)==""){
				$("#e_msg_error").text("Some fields are missing !");
			}
			else if ($.trim(username)!="" && $.trim(username).length<4 ){
				$("#e_msg_error").text("Username is supposed to be more than 4 characters");
			}
			else{
				$("#e_msg_error").text("");
				
				if($.trim(phone)=="" && $.trim(email)==""){
					$("#e_msg_error").text("Please enter phone number and/or email address!");
				}
				//Check phone number
				else if($.trim(phone)!="" && (!filter.test(phone) || phone.length<10 )){
					$("#e_msg_error").text("Invalid phone number !");
				}
				//Check email
				else if($.trim(email)!="" ){
					$("#e_msg_error").text("");
					if (atpos<1 || dotpos<atpos+2 || dotpos+2>=email.length){
					  $("#e_msg_error").text("Invalid email address !");
					  
					}
					else{
						$("#fm_edit_user").submit();
					}
				}
				
				else{
					$("#fm_user").submit();
				}
			}
			
			
		});
		
		
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
			/*
			//Append the contents of the 'action_panel_parent' to this first td element
			$($("#action_panel_parent").html()).appendTo(first_td);
			//Loop through all the links included in the action panel for this td and append the row_id to the end of it
			$.each($(this).find(".link"), function(i,v){
				var current_link = $(this).attr("link");
				var new_link = $(this).attr("link")+row_id; 
				$(this).attr("href",new_link);
			});
			*/
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
	

	//Ajax to edit a user
	$(".edit_user").live('click',function(event){
		event.preventDefault();
		var user_id=this.id;
		var request=$.ajax({
	     url: "user_management/edit",
	     type: 'GET',
	     data: {"u_id":user_id},
	     dataType: "json",
	    });
	    request.done(function(msg) {
            var access_level="";
	    	for (var key in msg){
	    		
      			if (msg.hasOwnProperty(key)){
      				if(key=="users"){
      					for(var y in msg[key]) {
	      					if (msg[key].hasOwnProperty(y)) {
	      						access_level=msg[key][y].Access_Level;
	      						$("#e_facility").attr("value",msg[key][y].Facility_Code);
	      						$("#e_username").val(msg[key][y].Username);
								$("#e_fullname").val(msg[key][y].Name);
								$("#e_user_id").val(msg[key][y].id);
								$("#e_phone").val(msg[key][y].Phone_Number);
								$("#e_email").val(msg[key][y].Email_Address);
	      					}
	      				}
	      			}
	      			
	      			if(key=="user_type"){
	      				$("#e_access_level option").remove();
	     				for(var y in msg[key]) {
	     					if (msg[key].hasOwnProperty(y)) {
	     						//if(msg[key][y].Id==access_level){
	     							$("#e_access_level").append("<option value='"+msg[key][y].Id+"'>"+msg[key][y].Access+"</option>")
	      						//}
	     						
	     					}
	     				}
	     			}
      			}
      		}
      		//$("#edit_user").dialog("open");
	    });
	    request.fail(function(jqXHR, textStatus) {
		  bootbox.alert("<h4>Retrieval Error</h4>\n\<hr/><center>Could not retrieve user details: </center>" + textStatus );
		});

	});
		
	});

</script>

<div id="view_content">
	<div class="container-fluid">
		<div class="row-fluid row">
		 <!-- Side bar menus -->
	    <?php echo $this->load->view('settings_side_bar_menus_v.php'); ?>
	    <!-- SIde bar menus end -->
		<div class="span12 span-fixed-sidebar">
	      	<div class="hero-unit">
	     		 <a href="#user_form" role="button" id="new_client" class="btn" data-toggle="modal"><i class="icon-plus icon-black"></i>New User</a> 		
	      		<?php
				echo $users;
				?>
	      	</div>
	      	
	    </div>
	  </div>
	</div>

	<div id="user_form" title="New User" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
			<?php
			$attributes = array('class' => 'input_form','id'=>'fm_user');
			echo form_open('user_management/save', $attributes);
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
							<?php
							foreach($user_types as $user_type){ 
							if($user_type['Access']=="Pharmacist"){
								//$level_access="User";
								$level_access=$user_type['Access'];
							}else{
								$level_access=$user_type['Access'];
							}					
							?>
								<option value="<?php echo $user_type['Id']; ?>"><?php echo $level_access ?></option>
							<?php }
							?>
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
				<tr>
					<td>
						<strong class="label">Facility</strong>
					</td>
					<td>
						<span class="add-on"><i class=" icon-chevron-down icon-black"></i></span>
						<select name="facility" id="facility" class="input-xlarge">
							<?php 
							foreach($facilities as $facility){
							?>]
							<option value="<?php echo $facility['facilitycode'];?>"><?php echo $facility['name'];?></option>
							<?php }?>
						</select>
					</td>
				</tr>
				<!--Order sites-->
				<tr>
					<td>
						<strong class="label">Order Sites</strong>
					</td>
					<td>
						<span class="add-on"><i class=" icon-chevron-down icon-black"></i></span>
						<input type="hidden" id="user_facilities_holder" name="user_facilities_holder" />
						<select name="user_facilities" id="user_facilities" class="input-xlarge sync_facility" multiple="multiple" required="">
							<?php 
							foreach($order_sites as $site){
							?>]
							<option value="<?php echo $site['id'];?>"><?php echo $site['name'];?></option>
							<?php }?>
						</select>
					</td>
				</tr>
			</table>
			</div>
			<div class="modal-footer">
			   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
			   <input type="submit" id="btn_save_user" value="Save" class="btn btn-primary " />
			</div>
			</form>
			
		</div>

		<div id="edit_user" title="Edit User" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
			<?php
			$attributes = array('class' => 'input_form','id'=>'fm_edit_user');
			echo form_open('user_management/update', $attributes);
			echo validation_errors('<p class="error">', '</p>');
			?>
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<h3 id="NewDrug">User details</h3>
			</div>
			<div class="modal-body">
				<div class="msg error" id="e_msg_error">Fields with <i class="icon-star icon-black"></i> are compulsory</div>
				<table style="margin-top:10px" class="table-striped" width="100%">
					<tr><td><strong class="label">Usertype</strong> </td>
						<td>
							<span class="add-on"><i class=" icon-chevron-down icon-black"></i></span>
							<select class="input-xlarge" id="e_access_level" name="access_level">
								
							</select>
						</td>
					</tr>
					<tr><td><strong class="label">Full Name</strong></td>
						<td>
							<input type="hidden" name="user_id" id="e_user_id" class="input" >
							<div>
								<span class="add-on"><i class="icon-user icon-black"></i></span>
								<input type="text" name="fullname" id="e_fullname" class="input-xlarge">
								<span class="add-on"><i class="icon-star icon-black"></i></span>
							</div>
							
						</td>
					</tr>
					<tr><td><strong class="label">Username</strong></td>
						<td>
							<div >
								<span class="add-on"><i class="icon-user icon-black"></i></span>
								<input type="text" name="username" id="e_username" class="input-xlarge">
								<span class="add-on"><i class="icon-star icon-black"></i></span>
							</div>
						</td></tr>
					<tr><td><strong class="label">Phone number</strong></td>
						<td>
							<div >
								<span class="add-on"><i class="icon-calendar icon-black"></i></span>
								<input type="text" name="phone" id="e_phone" class="input-xlarge">
								<span class="add-on"><i class="icon-star icon-black"></i></span>
							</div>
						</td></tr>
					<tr><td><strong class="label">Email address</strong></td>
						<td><div >
							<span class="add-on"><i class="icon-envelope icon-black"></i></span>
							<input type="text" name="email" id="e_email" class="input-xlarge">
						</td></tr>
					<tr><td><strong class="label">Facility</strong></td>
						
						<td>
							<span class="add-on"><i class=" icon-chevron-down icon-black"></i></span>
							<select name="facility" id="e_facility" class="input-xlarge">
								<?php 
								foreach($facilities as $facility){
								?>]
								<option value="<?php echo $facility['facilitycode'];?>"><?php echo $facility['name'];?></option>
								<?php }?>
							</select>
						</td>
					</tr>
				</table>
				<div class="modal-footer">
				   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
				   <input type="submit" value="Save" class="btn btn-primary " />
				</div>
			</div>
			
			</form>
		</div>

</div>