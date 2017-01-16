<style type="text/css">
	
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
	

	#regimenchange_form{
		background-color:#CCFFFF;
	}
	.dataTables_length{
		width:50%;
	}
	.dataTables_info{
		width:36%;
	}

</style>
<script type="text/javascript">
	$(document).ready(function() {
		$(".setting_table").find("tr :first").css("min-width","250px");
		
		$('.edit_user').live('click',function(event){
			event.preventDefault();
			$("#edit_regimenchange_id").val(this.id);
			$("#edit_regimenchange_name").val(this.name);
			$("#edit_form").dialog("open");
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
		
		
		//count to check which message to display
        var count='<?php echo @$this -> session -> userdata['message_counter']?>';
        var message='<?php echo @$this -> session -> userdata['message']?>';	
	
	if(count == 1) {
	$(".passmessage").slideDown('slow', function() {

	});
	$(".passmessage").append(message);

	var fade_out = function() {
	$(".passmessage").fadeOut().empty();
	}
	setTimeout(fade_out, 5000);
     <?php 
     $this -> session -> set_userdata('message_counter', "0");
     $this -> session -> set_userdata('message', " ");
     ?>

	}
	if(count == 2) {
	$(".errormessage").slideDown('slow', function() {

	});
	$(".errormessage").append(message);

	var fade_out = function() {
	$(".errormessage").fadeOut().empty();
	}
	setTimeout(fade_out, 5000);
     <?php 
     $this -> session -> set_userdata('message_counter', "0");
     $this -> session -> set_userdata('message', " ");
     ?>

	}
		
	});

</script>
<div id="action_panel_parent" style="display:none">
	<div class="actions_panel" style="visibility:hidden" >

		<?php
//Loop through all the actions passed on to this file
foreach($actions as $action){
		?>
		<a class="link" link="<?php echo $this->router->class."/".$action[1]."/"?>"><?php echo $action[0]
		?></a>
		<?php }?>
	</div>
</div>

<div id="view_content">
	<div class="container-fluid">
	  <div class="row-fluid row">
		 <!-- Side bar menus -->
	    <?php echo $this->load->view('settings_side_bar_menus_v.php'); ?>
	    <!-- SIde bar menus end -->
		<div class="span12 span-fixed-sidebar">
	      	<div class="hero-unit">
				<?php echo validation_errors('<p class="error">', '</p>');?>
				<a href="#regimenchange_form" role="button" id="new_regimenchange" class="btn" data-toggle="modal"><i class="icon-plus icon-black"></i>New Regimen Change Reason</a>
				<?php echo $sources;?>
				
			</div>
	    </div><!--/span-->
	  </div><!--/row-->
	</div><!--/.fluid-container-->
	<div id="regimenchange_form" title="New regimen change reason" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="NewRegimen" aria-hidden="true">
		<?php
		$attributes = array('class' => 'input_form','id'=>'regimenchange_management_frm');
		echo form_open('regimenchange_management/save', $attributes);
		echo validation_errors('<p class="error">', '</p>');
		?>
		<div class="modal-header">
		    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		    <h3 id="NewRegimen">Regimen Change Reason details</h3>
		</div>	
		<div class="modal-body">
			<label>
				<strong class="label">Regimen change reason</strong>
				<input type="hidden" name="regimenchange_id" id="regimenchange_id" class="input" >
				<input type="text" name="regimenchange_name" id="regimenchange_name" class="input-xlarge span12" required="required">
			</label>
		</div>
		<div class="modal-footer">
		   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		   <input type="submit" value="Save" class="btn btn-primary " />
		</div>
		</form>
	</div>
	
	<!--Edit regimen change reason -->
	<div id="edit_form" title="Edit regimen change reason" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="NewRegimen" aria-hidden="true">
		<?php
		$attributes = array('class' => 'input_form','id'=>'regimenupdate_management_frm');
		echo form_open('regimenchange_management/update', $attributes);
		echo validation_errors('<p class="error">', '</p>');
		?>
		<div class="modal-header">
		    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		    <h3 id="NewRegimen">Regimen Change Reason details</h3>
		</div>
		<div class="modal-body">
			<label>
				<strong class="label">Regimen change reason</strong>
				<input type="hidden" name="regimenchange_id" id="edit_regimenchange_id" class="input" >
				<input type="text" name="regimenchange_name" id="edit_regimenchange_name" class="input-xlarge span12" required="required">
			</label>
		</div>	
		<div class="modal-footer">
		   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		   <input type="submit" value="Save" class="btn btn-primary " />
		</div>
		</form>
	</div>

</div>

<script type="text/javascript">
	$(document).ready(function(){
		/*Prevent Double Click*/
		$('form').submit(function(){
		  	$(this).find(':submit').attr('disabled','disabled');
		});
	});
</script>



