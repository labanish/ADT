<script>
	$(document).ready(function() {
		//load pediatric drugs and dose when the add button is clicked 
	$(document).ready(function() {
		$('#new_client').on('click',function(e){
			//GET DRUGS
			var request=$.ajax({
			url: "dossing_chart/get_drugs",
			type: 'POST',
			    dataType: "json",
			    success: function(datas) {
					for(var i=0;i<datas.length; i++){
						var ids=datas[i]['id'];
						var drugs= datas[i]['drug'];
						$('#drug').append($('<option>', {
							value: ids,
							text: drugs
							}));
					}	
			    }
			});
			//GET DOSE
			var request=$.ajax({
			url: "dossing_chart/get_dose",
			type: 'POST',
			    dataType: "json",
			    success: function(datas) {
					for(var i=0;i<datas.length; i++){
						var ids=datas[i]['id'];
						var doses= datas[i]['dose'];
						$('#dose').append($('<option>', {
							value: ids,
							text: doses
							}));
					}	
			    }
			});
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
				<a href="#client_form" role="button" id="new_client" class="btn" data-toggle="modal"><i class="icon-plus icon-black"></i>New Peadiatric Dose</a>
			<?php
				echo @$classifications;
	        ?>
	        
	      </div>

	      
	    </div><!--/span-->
	  </div><!--/row-->
	</div><!--/.fluid-container-->
	
		
	<div id="client_form" title="New Drug Classification" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
		<?php
			$attributes = array('class' => 'input_form');
			echo form_open('drugcode_classification/save', $attributes);
		?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="NewDrug">Peadiatric Dossing Chart details</h3>
		</div>
		<div class="modal-body">
			<label>
			<strong class="label">Maximum Weight</strong>
			<input type="text" name="max_weight" id="max_weight" class="input-xlarge" required="required">
			</label>
			<label>
			<strong class="label">Minimum Weight</strong>
			<input type="text" name="min_weight" id="min_weight" class="input-xlarge" required="required">
			</label>
			<label>
			<strong class="label">Drug</strong>
			<select multiple id="drug">
			</select>
			</label>
			<label>
			<strong class="label">Dose</strong>
			<select>
			</select>
			</label>
		</div>
		<div class="modal-footer">
		   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		   <input type="submit" value="Save" class="btn btn-primary " />
		</div>
		<?php echo form_close();?>
	</div>
	
	<div id="edit_form" title="Edit Drug Classification" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
		<?php
			$attributes = array('class' => 'input_form');
			echo form_open('drugcode_classification/update', $attributes);
		?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="NewDrug">Drug Classification details</h3>
		</div>
		<div class="modal-body">
			<label>
			<strong class="label">Drug Classification</strong>
			<input type="hidden" name="classification_id" id="classification_id" class="input">
			<input type="text" name="edit_classification_name" id="edit_classification_name" class="input-xlarge" required="required">
			</label>
		</div>
		<div class="modal-footer">
		   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		   <input type="submit" value="Save" class="btn btn-primary " />
		</div>
		<?php echo form_close();?>
	</div>
</div>
