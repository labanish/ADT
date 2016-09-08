<!--Sync Drug Form-->
<div id="sync_drug_form" class="modal hide fade dialog_form" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<form class="form-horizontal sync_drug_form" action="<?php echo base_url().'order_settings/save/sync_drug';?>" method="post">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="myModalLabel">Add Sync Drug</h3>
		</div>
		<div class="modal-body">
			<div class="control-group">
				<label class="control-label" for="sync_drug_name">Name</label>
				<div class="controls">
				  	<input type="text" id="sync_drug_name" name="name" placeholder="name" required="">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_drug_abbreviation">Abbrevation</label>
				<div class="controls">
				  	<input type="text" id="sync_drug_abbreviation" name="abbreviation" placeholder="abbrevation" >
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_drug_strength">Strength</label>
				<div class="controls">
				  	<input type="text" id="sync_drug_strength" name="strength" placeholder="strength" required="">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_drug_packsize">Packsize</label>
				<div class="controls">
				  	<input type="text" id="sync_drug_packsize" name="packsize" placeholder="packsize" required="">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_drug_formulation">Formulation</label>
				<div class="controls">
				  	<input type="text" id="sync_drug_formulation" name="formulation" placeholder="formulation" required="">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_drug_unit">Unit</label>
				<div class="controls">
					<input type="text" id="sync_drug_unit" name="unit" placeholder="unit">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_drug_note">Note</label>
				<div class="controls">
					<input type="text" id="sync_drug_note" name="note" placeholder="note">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_drug_weight">Weight</label>
				<div class="controls">
					<input type="text" id="sync_drug_weight" name="weight" placeholder="note">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_drug_category_id">Category</label>
				<div class="controls">
					<select id="sync_drug_category_id" name="category_id" class="category_id"></select>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_drug_regimen_id">Regimen</label>
				<div class="controls">
					<select id="sync_drug_regimen_id" name="regimen_id" class="regimen_id" ></select>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
			<button class="btn btn-primary">Save</button>
		</div>
	</form>
</div>

<!--Sync Regimen Form-->
<div id="sync_regimen_form" class="modal hide fade dialog_form" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<form class="form-horizontal sync_regimen_form" action="<?php echo base_url().'order_settings/save/sync_regimen';?>" method="post">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="myModalLabel">Add Sync Regimen</h3>
		</div>
		<div class="modal-body">
			<div class="control-group">
				<label class="control-label" for="sync_regimen_name">Name</label>
				<div class="controls">
				  	<input type="text" id="sync_regimen_name" name="name" placeholder="name" required="">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_regimen_code">Code</label>
				<div class="controls">
				  	<input type="text" id="sync_regimen_code" name="code" placeholder="code" required="">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_regimen_old_code">Old Code</label>
				<div class="controls">
				  	<input type="text" id="sync_regimen_old_code" name="old_code" placeholder="old code">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_regimen_description">Description</label>
				<div class="controls">
					<textarea id="sync_regimen_description" name="description"></textarea>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_regimen_category_id">Category</label>
				<div class="controls">
					<select id="sync_regimen_category_id" name="category_id" class="category_id" required=""></select>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
			<button class="btn btn-primary">Save</button>
		</div>
	</form>
</div>

<!--Sync Regimen Category Form-->
<div id="sync_regimen_category_form" class="modal hide fade dialog_form" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<form class="form-horizontal sync_regimen_category_form" action="<?php echo base_url().'order_settings/save/sync_regimen_category';?>" method="post">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="myModalLabel">Add Sync Regimen Category</h3>
		</div>
		<div class="modal-body">
			<div class="control-group">
				<label class="control-label" for="sync_regimen_category_Name">Name</label>
				<div class="controls">
				  	<input type="text" id="sync_regimen_category_Name" name="name" placeholder="name" required="">
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
			<button class="btn btn-primary">Save</button>
		</div>
	</form>
</div>

<!--Sync Facility Form-->
<div id="sync_facility_form" class="modal hide fade dialog_form" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<form class="form-horizontal sync_facility_form" action="<?php echo base_url().'order_settings/save/sync_facility';?>" method="post">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="myModalLabel">Add Sync Facility</h3>
		</div>
		<div class="modal-body">
			<div class="control-group">
				<label class="control-label" for="sync_facility_name">Name</label>
				<div class="controls">
				  	<input type="text" id="sync_facility_name" name="name" placeholder="name" required="">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_facility_code">Code</label>
				<div class="controls">
				  	<input type="text" id="sync_facility_code" name="code" placeholder="code" required="">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_facility_category">Category</label>
				<div class="controls">
				  	<input type="text" id="sync_facility_category" name="category" placeholder="category" required="">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_facility_sponsors">Sponsors</label>
				<div class="controls">
				  	<input type="text" id="sync_facility_sponsors" name="sponsors" placeholder="sponsors">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_facility_services">Services</label>
				<div class="controls">
				  	<input type="text" id="sync_facility_services" name="services" placeholder="services">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_facility_ordering">Is Ordering Site?</label>
				<div class="controls">
					<select id="sync_facility_ordering" name="ordering">
						<option value="0" selected="">No</option>
						<option value="1">Yes</option>
					</select>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_facility_service_point">Is Service Point?</label>
				<div class="controls">
					<select id="sync_facility_service_point" name="service_point">
						<option value="0" selected="">No</option>
						<option value="1">Yes</option>
					</select>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_facility_parent_id">Parent Facility</label>
				<div class="controls">
					<select id="sync_facility_parent_id" name="parent_id" class="parent_id"></select>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_facility_county_id">County</label>
				<div class="controls">
					<select id="sync_facility_county_id" name="county_id" class="county_id"></select>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_facility_district_id">Sub-County</label>
				<div class="controls">
					<select id="sync_facility_district_id" name="district_id" class="district_id"></select>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="sync_facility_keph_level">Keph Level</label>
				<div class="controls">
				  	<input type="text" id="sync_facility_keph_level" name="keph_level" placeholder="services">
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
			<button class="btn btn-primary">Save</button>
		</div>
	</form>
</div>

<script type="text/javascript">
	var categoryURL = 'order_settings/fetch/sync_regimen_category'
	var regimenURL = 'order_settings/fetch/sync_regimen'
	var parentURL = 'order_settings/fetch/sync_facility'
	var countyURL = 'order_settings/fetch/counties'
	var subcountyURL = 'order_settings/fetch/district'
	$(function() {
		//Load SelectBox Resources
		LoadResource('.category_id', categoryURL);
		LoadResource('.regimen_id', regimenURL);
		LoadResource('.parent_id', parentURL);
		LoadResource('.county_id', countyURL);
		LoadResource('.district_id', subcountyURL);

		//Edit event
		$(".edit_setting").on('click', function(){
			resource_id = $(this).attr('id')
			resource_table = $(this).attr('table')
			LoadDetails(resource_table, resource_id)
			ChangeUrl(resource_table, resource_id)
		});
	});

	function LoadResource(divClass, resourceURL){
	    //Fetch Resources
	    $.get(resourceURL, function(data) {
	        //Append Items to SelectBox
	        LoadSelectBox(divClass, data)
	    });
	}

	function LoadSelectBox(divClass, data){
		$(divClass).append($("<option></option>").attr("value", 0).text('-select one-'));
		//Parse json to array
		data = $.parseJSON(data);
		//Append results to selectbox
		$.each(data, function(i, item) {
		    $(divClass).append($("<option></option>").attr("value", item.id).text(item.name));
		});
	}

	function LoadDetails(table, id){
		var detailsURL = 'order_settings/get_details/'+table+'/'+id
		$.get(detailsURL, function(data) {
	        //Parse json to array
			data = $.parseJSON(data);
			//Append results to selectbox
			$.each(data, function(index, value) {
			    $("#"+table+"_"+index).attr("value", value);
			});
	    });
	}

	function ChangeUrl(resource_table, resource_id){
		var newURL = "<?php echo base_url();?>"+"order_settings/update/"+resource_table+"/"+resource_id
		$("."+resource_table+"_form").attr("action", newURL)
	}
</script>