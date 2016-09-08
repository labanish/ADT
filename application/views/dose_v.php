<style type="text/css">
	.actions_panel {
		width: 200px;
		margin-top: 5px;
	}
	#client_form, form{
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
		$(".setting_table").find("tr :first").css("min-width","300px");
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
		
		//When clicked dialog form for new dose pops up
		$("#new_client").click(function(){ 
			//$("#client_form").dialog("open");
		});
		
		$(".edit_user").live('click',function(event){ 
			event.preventDefault();
			var id=this.id;
			var request=$.ajax({
		     url: "dose_management/edit",
		     type: 'POST',
		     data: {"id":id},
		     dataType: "json"
		    });
		    
		    request.done(function(msg) {
		    	for (var key in msg){
		     		if (msg.hasOwnProperty(key)){
		     			if(key=="doses"){
			     			for(var y in msg[key]) {
		     					if (msg[key].hasOwnProperty(y)) {
		     						$('#edit_dose_id').val(msg[key][y].id);
									$('#edit_dose_name').val(msg[key][y].Name);
									$('#edit_dose_value').val(msg[key][y].Value);
									$('#edit_dose_frequency').val(msg[key][y].Frequency);
		     					}
			     			}
			     			//$("#edit_form").dialog("open");
			     		}
		     		}
		     	}
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
				<?php echo validation_errors('<p class="error">', '</p>');?>
			<a href="#client_form" role="button" id="new_client" class="btn" data-toggle="modal"><i class="icon-plus icon-black"></i>New Drug Dose</a>
				<?php echo $doses; ?>
				
			</div>
	    </div><!--/span-->
	  </div><!--/row-->
	</div><!--/.fluid-container-->
	<div id="client_form" title="New Drug Dose" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="NewDrug" aria-hidden="true">
		<?php
		$attributes = array('class' => 'input_form');
		echo form_open('dose_management/save', $attributes);
		echo validation_errors('<p class="error">', '</p>');
		?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="NewDrug">Dose details</h3>
		</div>
		<div class="modal-body">
			<table>
				<tr>
					<td>
						<strong class="label">Dose Name</strong>
					</td>
					<td>
						<input type="text" name="dose_name" id="dose_name" class="input-xlarge" size="30" required="">
					</td>
				</tr>
				<tr>
					<td>
						<strong class="label">Dose Value</strong>
					</td>
					<td>
						<input type="text" name="dose_value" id="dose_value" class="input-xlarge" size="30" required="">
					</td>
				</tr>
				<tr>
					<td>
						<strong class="label">Dose Frequency</strong>
					</td>
					<td>
						<input type="text" name="dose_frequency" id="dose_frequency" class="input-xlarge" size="30" required="">
					</td>
				</tr>
			</table>
		</div>
		<div class="modal-footer">
		   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		   <input type="submit" value="Save" class="btn btn-primary " />
		</div>
		
		<?php echo form_close(); ?>
	</div>
	
	<!-- Edit drug dose -->
	<div id="edit_dose" title="Edit Drug Dose" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="NewDrug" aria-hidden="true">
		<?php
		$attributes = array('class' => 'input_form');
		echo form_open('dose_management/update', $attributes);
		echo validation_errors('<p class="error">', '</p>');
		?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="NewDrug">Dose details</h3>
		</div>
		<table>
			<tr>
				<td>
					<strong class="label">Dose Name</strong>
				</td>
				<td>
					<input type="hidden" name="dose_id" id="edit_dose_id" class="input-xlarge" size="30">
					<input type="text" name="dose_name" id="edit_dose_name" class="input-xlarge" size="30" required="">
				</td>
			</tr>
			<tr>
				<td>
					<strong class="label">Dose Value</strong>
				</td>
				<td>
					<input type="text" name="dose_value" id="edit_dose_value" class="input-xlarge" size="30" required="">
				</td>
			</tr>
			<tr>
				<td>
					<strong class="label">Dose Frequency</strong>
				</td>
				<td>
					<input type="text" name="dose_frequency" id="edit_dose_frequency" class="input-xlarge" size="30" required="">
				</td>
			</tr>
		</table>
		<div class="modal-footer">
		   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		   <input type="submit" value="Save" class="btn btn-primary " />
		</div>
		<?php echo form_close(); ?>
	</div>

</div>


