<style type="text/css">
	
	.dataTables_length{
		width:50%;
	}
	.dataTables_info{
		width:36%;
	}
	#edit_form, #client_form{
		background-color:#CCFFFF;
	}

</style>
<script type="text/javascript">
	$(document).ready(function() {
		/*Prevent Double Click*/
		$('input_form').submit(function(){
		  	$(this).find(':submit').attr('disabled','disabled');
		});
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
		
		//When clicked dialog form for new indication pops up
		/*
		$("#new_client").click(function(){ 
			$("#client_form").dialog("open");
		});
		*/
		$(".edit_user").live('click',function(event){
			event.preventDefault();
			$("#edit_source_id").val(this.id);
			$("#edit_source_name").val(this.name);
			//$("#edit_form").dialog("open");
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
			    <a href="#client_form" role="button" id="new_client" class="btn" data-toggle="modal"><i class="icon-plus icon-black"></i>New Drug Source</a>
		
				<?php echo $sources;?>
				
			</div>
	    </div><!--/span-->
	  </div><!--/row-->
	</div><!--/.fluid-container-->
	<div id="client_form" title="New Drug Source" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="NewDrug" aria-hidden="true">
		<?php
		$attributes = array('class' => 'input_form');
		echo form_open('drugsource_management/save', $attributes);
		echo validation_errors('<p class="error">', '</p>');
		?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="NewDrug">Drug source details</h3>
		</div>
		<div class="modal-body">	
			<label>
			<strong class="label">Drug Source Name</strong>
			<input type="text" name="source_name" id="source_name" class="input-xlarge" size="30" required="">
			</label>
		</div>
		<div class="modal-footer">
		   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		   <input type="submit" value="Save" class="btn btn-primary " />
		</div>
		</form>
	</div>
	
	<div id="edit_form" title="Edit Drug Source" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="NewDrug" aria-hidden="true">
		<?php
		$attributes = array('class' => 'input_form');
		echo form_open('drugsource_management/update', $attributes);
		echo validation_errors('<p class="error">', '</p>');
		?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="NewDrug">Drug source details</h3>
		</div>
		<div class="modal-body">	
			<label>
			<strong class="label">Drug Source Name</strong>
			<input type="hidden" name="source_id" id="edit_source_id" class="input" size="30">
			<input type="text" name="source_name" id="edit_source_name" class="input-xlarge" required="">
			</label>
		</div>
		<div class="modal-footer">
		   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		   <input type="submit" value="Save" class="btn btn-primary " />
		</div>
		</form>
	</div>

</div>
