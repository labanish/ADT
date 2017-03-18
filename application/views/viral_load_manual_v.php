<script>
	$(document).ready(function() {

		$('.edit_user').live('click',function(event){
			event.preventDefault();
			$("#id1").val(this.id);
			$("#patient_ccc_number1").val(this.name);
			//$("#edit_form").dialog("open");
		});
		/*Prevent Double Click*/
		$('input_form').submit(function(){
		  	$(this).find(':submit').attr('disabled','disabled');
		});
		 /* -------------------------- test date, date picker settings and checks -------------------------*/
        //Attach viral load dispensing date picker for date of dispensing
        $("#test_date").datepicker({
            yearRange: "-120:+0",
            maxDate: "0D",
            dateFormat: $.datepicker.ATOM,
            changeMonth: true,
            changeYear: true
        });
        $("#test_date").datepicker();
        $("#test_date").datepicker("setDate", new Date());
	
		//populate patient_ccc_number
        var base_url="<?php echo base_url();?>";
        var link = base_url + "viral_load_manual/get_patient_ccc_number/" ;
        var request_viral_load=$.ajax({
                url: link,
                type: 'POST',
                dataType: "json",
                success: function(data) {
                	$.each(data, function(i, data) {
       					 $('#patient_ccc_number').append("<option value='" + data.patient_ccc_number + "'>" + data.patient_ccc_number + "</option>");
    				});
                }
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
				<!--<a href="#file_form" role="button" id="new_client" class="btn" data-toggle="modal"><i class="icon-plus icon-black"></i>Upload CSV</a>-->
			<?php
				echo @$viral_result;
	        ?>
	        
	      </div>

	      
	    </div><!--/span-->
	  </div><!--/row-->
	</div><!--/.fluid-container-->
	
	<div id="edit_form" title="Edit Drug Classification" class="modal hide fade cyan " tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
		<?php
			$attributes = array('class' => 'input_form');
			echo form_open('viral_load_manual/update', $attributes);
		?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="NewDrug">Update Patient Viral Load Information</h3>
		</div>
		<div class="modal-body">
			<label>
			<strong class="label">Patient CCC Number</strong>
			<input type="hidden" name="id" id="id1" class="input">
			<input type="text" name="patient_ccc_number" id="patient_ccc_number1" class="input-xlarge" required="required">
			</label>
		</div>
		<div class="modal-footer">
		   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		   <input type="submit" value="Save" class="btn btn-primary " />
		</div>
		<?php echo form_close();?>
	</div>
	<!--file upload-->
		<div id="file_form" title="Edit Drug Classification" class="modal hide fade cyan " tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
		<?php
			$attributes = array('class' => 'input_form','enctype' =>'multipart/form-data');
			echo form_open('viral_load_manual/file_upload', $attributes);
		?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="NewDrug">Upload Patient Viral Load Information</h3>
		</div>
		<div class="modal-body">
			<label>
			<input type="file" name="file" value="" class="input-xlarge" required="required">
			</label>
		</div>
		<div class="modal-footer">
		   <input type="submit" value="Upload" class="btn btn-primary " />
		   <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
		
		</div>
		<?php echo form_close();?>
	</div>
</div>
