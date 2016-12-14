<script>
//load pediatric drugs and dose when the add button is clicked 
	$(document).ready(function() {
		//GET DRUGS
		var request=$.ajax({
		url: "dossing_chart/get_drugs",
		type: 'POST',
		    dataType: "json",
		    success: function(datas) {
		    	$('#drug').empty();
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
		    	$('#dose').empty();
				for(var i=0;i<datas.length; i++){
					var ids=datas[i]['id'];
					var doses= datas[i]['Name'];
					$('#dose').append($('<option>', {
						value: ids,
						text: doses
						}));
				}	
		    }
		});

		//edit dose info
		$('.edit_user').on('click',function(e){
			e.preventDefault();
			var id=this.id;

			var request=$.ajax({
			    url: "dossing_chart/edit",
			    type: 'POST',
			    data: {"id":id},
			    dataType: "json",
			    success: function(data) {
			    	var id = data[0]['id'];	
			    	//console.log(id);	    	
					var max_weight = data[0]['max_weight'];
					var min_weight = data[0]['min_weight'];
					var dose = data[0]['Name'];
					var drug = data[0]['drug'];
					$('#idno').val(id);
					$('#max_weight').val(max_weight);
					$('#min_weights').val(min_weight);
					//DRUGS
					$('#drugs')	.find('option')
   									.remove()
   									.end()
   									.append($('<option>', {
    									value: id,
   				    					text: drug
									}));
					 //GET OTHER DRUGS
					var request=$.ajax({
			        url: "dossing_chart/get_drugs",
			        type: 'POST',
			        dataType: "json",
			        success: function(datas) {
						for(var i=0;i<datas.length; i++){
								var ids=datas[i]['id'];
								var drugs= datas[i]['drug'];
								$('#drugs').append($('<option>', {
									value: ids,
									 text: drugs
							}));
						}	
			                }
			            	});
					//DOSE
					$('#doses').find('option')
   									.remove()
   									.end()
					 				.append($('<option>', {

    									value: id,
   				    					text: dose
									}));
					 //GET OTHER DOSES
					var request1=$.ajax({
			        url: "dossing_chart/get_dose",
			        type: 'POST',
			        dataType: "json",
			        success: function(datas) {

						for(var i=0;i<datas.length; i++){
							//console.log(datas);
								var ids=datas[i]['id'];
								var doses= datas[i]['Name'];
								$('#doses').append($('<option>', {
									value: ids,
									 text: doses
							}));
						}	
			                }
			            	});


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
				<a href="#client_form1" role="button" id="new_client" class="btn" data-toggle="modal"><i class="icon-plus icon-black"></i>New Pediatric Drug Dose</a>
			<?php
				echo @$classifications;
	        ?>
	        
	      </div>

	      
	    </div><!--/span-->
	  </div><!--/row-->
	</div><!--/.fluid-container-->
	
		
	<div id="client_form1" title="New Dossing Chart" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
		<?php
			$attributes = array('class' => 'input_form');
			echo form_open('dossing_chart/save', $attributes);
		?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="NewDrug">Dossing Chart details</h3>
		</div>
		<div class="modal-body">
			<label>
			<p class="label">Minimum Weight</p>
			<input type="text" name="min_weight" id="min_weight" class="input-xlarge" required="required">
			</label>
			<label>
			<strong class="label">Maximum Weight</strong>
			<input type="text" name="max_weight" id="classification_name" class="input-xlarge" required="required">
			</label>
			<label>
			<strong class="label">Drug</strong>
			<select name="drug[]" multiple id="drug" style="width:100%;">
			</select>
			<label>
			<strong class="label">Dose</strong>
			<select name="dose" id="dose">
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
			echo form_open('dossing_chart/update', $attributes);
		?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="NewDrug">Dosing Chart Details</h3>
		</div>
		<div class="modal-body">
			<label>
			<input type="hidden" name="idno" id="idno" class="input">
			<strong class="label">Minimum Weight</strong>
			<input type="hidden" name="id" id="ids" class="input">
			<input type="text" name="min_weights" id="min_weights" class="input-xlarge" required="required">
			</label>
			<label>
			<strong class="label">Maximum Weight</strong>
			<input type="text" name="max_weights" id="max_weight" class="input-xlarge" required="required">
			</label>
			<label>
			<strong class="label">Drug</strong>
			<select name="drugs" id="drugs" style="width:100%;">
			</select>
			<label>
			<strong class="label">Dose</strong>
			<select name="doses" id="doses">
			</select>
			</label>
		</div>
		<div class="modal-footer">
		   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		   <input type="submit" value="Save" class="btn btn-primary " />
		</div>
		<?php echo form_close();?>
	</div>
</div>
