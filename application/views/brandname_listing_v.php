<script type="text/javascript" src="<?php echo base_url().'assets/Scripts/datatable/jquery.dataTables.rowGrouping.js'?>"></script>


<script>
	$(document).ready(function() {
		/*Prevent Double Click*/
		$('input_form').submit(function(){
		  	$(this).find(':submit').attr('disabled','disabled');
		});
		$("#entry_form").dialog({
			height : 200,
			width : 500,
			modal : true,
			autoOpen : false
		});
		$("#new_brandname").click(function() {
			$("#entry_form").dialog("open");
		});
		$("#drug_listing").accordion({
			autoHeight : false,
			navigation : true
		});

	});

	

</script>
<style>
	#regimen_drug_listing {
		width: 90%;
		margin: 10px auto;
	}
	a {
		text-decoration: none;
	}
	.enable_user {
		color: green;
		font-weight: bold;
	}
	.disable_user {
		color: red;
		font-weight: bold;
	}
	.edit_user {
		color: blue;
		font-weight: bold;
	}
	
	#drug_listing{
		width:90%;
		margin:10px auto;
	}	
	#entry_form{
		background-color:#CCFFFF;
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
    		<?php echo validation_errors('<p class="error">', '</p>');?>
    	    <a href="#client_form" role="button" id="new_client" class="btn" data-toggle="modal"><i class="icon-plus icon-black"></i>New Brand Name</a>		
	      	<table class="setting_table table table-bordered table-striped" id="brand_name_table">
	        	<thead>
	        		<tr>
	        			<th>Drug Codes</th>
	        			<th>Drug Codes - Brand Names</th>
	        			<th>Options</th>
	        		</tr>
	        	</thead>
	        	<tbody>
	        		<?php
	        		foreach($drug_codes as $drug_code){
	        			foreach($drug_code->Brands as $brand){
	        		?>
	        		<tr><td><?php echo $drug_code->Drug;?></td><td><?php echo $brand->Brand; ?></td>
	        			<td><?php echo anchor('brandname_management/delete/'.$brand->id,'Delete') ; ?></td></tr>
	        		<?php 
	        			}
	        		} ?>
	        	</tbody>
	        </table>
	        
	      </div>
	    </div><!--/span-->
	  </div><!--/row-->
	</div><!--/.fluid-container-->

	<div id="client_form" title="New Brandname" class="modal hide fade cyan" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
		<?php
		$attributes = array('class' => 'input_form');
		echo form_open('brandname_management/save', $attributes);
		?>
		
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="NewDrug">Brand details</h3>
		</div>
		<div class="modal-body">
			<table>
				<tr><td><strong class="label">Select Drug</strong></td>
					<td>
						<select class="input-xlarge" id="drugid" name="drugid">
							<?php
							foreach($drug_codes as $drug_code){
							?>
							<option value="<?php echo $drug_code -> id;?>"><?php echo $drug_code -> Drug;?></option>
							<?php }?>
						</select>
					</td>
				</tr>
				<tr><td><strong class="label">Brand Name</strong></td>
					<td>
						<input type="text" name="brandname" id="brandname" class="input-xlarge" required="">
					</td>
				</tr>
			</table>
			</div>
			<div class="modal-footer">
			   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
			   <input type="submit" value="Save" class="btn btn-primary " />
			</div>
		<?php echo form_close() ; ?>
	</div>
</div>