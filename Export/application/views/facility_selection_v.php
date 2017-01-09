<?php
echo validation_errors('
<p class="error">','</p>
'); 
?>
<script type="text/javascript">
	$(document).ready(function(){
		$(".data_import").dialog({
			width : 500,
			modal : true,
			height: 150,
			autoOpen : false
		 });
		$("#excel_upload").click(function(){
			 $(".data_import").dialog("open");
		});
	});	
</script>

<div class="full-content">
<form action="<?php echo base_url().'order_management/new_satellite_order'?>" method="post" style="margin:0 auto; width:300px;">
	<table width="100%"  cellpadding="5">
		<th>
		<label> <strong class="label" style="text-align:justify;">Select Satellite Facility</strong>	
		</th>
		<tr>
			<td colspan='2'>
					<select name="satellite_facility" style="width:250px;height:35px;">
			<option value="0">--Select Facility--</option>
			<?php 
				foreach($facilities as $facility){?>
					<option value="<?php echo $facility['facilitycode'];?>"><?php echo $facility['name'];?></option>
				<?php }
			?>
		</select> 
			</td>
			</tr>
			<tr>
			<td>
			<input type="submit" class="action_button" name="proceed" id="proceed" value="Fill Order Form" style="height:40px;margin-left:0px;"/>
			</td>
			<td>
         	<a class="action_button" href="#" id="excel_upload" style="height:25px;margin-right:0px;padding:5px;padding-top:8px;">Upload Excel</a>
			</td>	
			</tr>
</table>
</form>
</div>    
<div class="data_import" title="Excel Upload">
	<form name="frm" method="post" enctype="multipart/form-data" id="frm" action="<?php echo base_url()."fcdrr_management/data_upload"?>">
	<p>
				<input type="file"  name="file" size="30"  required="required" />
				<input name="btn_save" class="button" type="submit"  value="Save"  style="width:80px; height:30px;"/>
	</p>		
	</form>	
</div>	
