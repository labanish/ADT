<script>
	$(document).ready(function() {
		/*Prevent Double Click*/
		$('input_form').submit(function(){
		  	$(this).find(':submit').attr('disabled','disabled');
		});
		$('.edit_user').live('click',function(event){
			event.preventDefault();
			$("#generic_id").val(this.id);
			$("#edit_generic_name").val(this.name);
			//$("#edit_form").dialog("open");
		});
	
	});

</script>
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
	.dataTables_length{
		width:50%;
	}
	.dataTables_info{
		width:36%;
	}

</style>
<div id="view_content">

	<div class="container-fluid">
	  <div class="row-fluid row">
	    <!-- Side bar menus -->
	    <?php echo $this->load->view('settings_side_bar_menus_v.php'); ?>
	    <!-- SIde bar menus end -->
		<div class="span12 span-fixed-sidebar">
	      <div class="hero-unit">
	      	
	      	<?php 
	      		echo validation_errors('<p class="error">', '</p>');
				?>
			    <a href="#client_form" role="button" id="new_client" class="btn" data-toggle="modal"><i class="icon-plus icon-black"></i>New Generic Name</a>
			<?php
				echo @$generic_names;
	        ?>
	        
	      </div>

	      
	    </div><!--/span-->
	  </div><!--/row-->
	</div><!--/.fluid-container-->
	
		
	<div id="client_form" title="New Generic Name" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
		<?php
			$attributes = array('class' => 'input_form');
			echo form_open('genericname_management/save', $attributes);
		?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="NewDrug">Generic Name details</h3>
		</div>
		<div class="modal-body">
			<label>
			<strong class="label">Generic Name</strong>
			<input type="text" name="generic_name" id="generic_name" class="input-xlarge" required="">
			</label>
		</div>
		<div class="modal-footer">
		   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		   <input type="submit" value="Save" class="btn btn-primary " />
		</div>
		<?php echo form_close();?>
	</div>
	
	<div id="edit_form" title="Edit Generic Name" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
		<?php
			$attributes = array('class' => 'input_form');
			echo form_open('genericname_management/update', $attributes);
		?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="NewDrug">Generic Name details</h3>
		</div>
		<div class="modal-body">
			<label>
			<strong class="label">Generic Name</strong>
			<input type="hidden" name="generic_id" id="generic_id" class="input">
			<input type="text" name="edit_generic_name" id="edit_generic_name" class="input-xlarge" required="">
			</label>
		</div>
		<div class="modal-footer">
		   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		   <input type="submit" value="Save" class="btn btn-primary " />
		</div>
		<?php echo form_close();?>
	</div>
</div>
