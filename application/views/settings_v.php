<style>
    .setting_table{
    	font-size:12px;
    	letter-spacing:0px;
    }
	.dataTables_length{
		width:50%;
	}
	.dataTables_info{
		width:44%;
	}
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
	.edit_user{
		color:blue;
		font-weight:bold;
	}
	#edit_form, #client_form,#entry_form{
		background-color:#CCFFFF;
	}
	.hero-unit{
		margin-bottom:0px;
	}
	.dataTable {
		letter-spacing:0px;
	}
	table.dataTable{
	    zoom:0.9;	
	}
	.table-bordered input {
		width:4em;
	}
	table {
	  table-layout: fixed;
	}
	td {
	  white-space: nowrap;
	  overflow: hidden;         /* <- this does seem to be required */
	  text-overflow: ellipsis;
	}
	.settings_title {
		width:auto;
	}
	.well{
		background-color:#FFF;
		border:0px;
		height:auto;
	}
	.label{
	  background-color:transparent;
	}
</style>
<script>
	
	$(document).ready(function() {	
		setTimeout(function(){
			$(".message").fadeOut("2000");
		},6000);
		//What happens when editing/updating/disabling/enabling
		<?php
		//Check if the session for a page load already exists
		if($this->session->userdata('link_id') and $this->session->userdata('linkSub')){
		?>
		$(".settings_title").fadeIn("1000");
		$(".settings").css("display","none");
		$("#loadingDiv").css("display","block");
		link_id='#'+'<?php echo $this->session->userdata('link_id')?>';
		linkSub='<?php echo $this->session->userdata('linkSub')?>';
		linkIdUrl=link_id.substr(link_id.indexOf('#')+1,(link_id.indexOf('_li')-1));
		linkTitle='<?php echo @$this->session->userdata('linkTitle')?>';
		//Change the page title value
		$("#actual_page").html(linkTitle);
		$(".settings").load('<?php echo base_url();?>'+linkSub+'/'+linkIdUrl,function(){
			//$("input[type='text']").attr("required","required");
			$("#loadingDiv").css("display","none");
			$(".settings").css("display","block");
				if( linkSub=="brandname_management"){
					$('#brand_name_table').dataTable({
		    			"bLengthChange": false,
		                "bPaginate": false,
		                "bJQueryUI": true,
		            	"bDestroy":true,
		            	"bInfo" : true,
						"sScrollX" : "100%",
						"bScrollCollapse" : true,
						"sScrollY" : "200px",
						"bStateSave" : true,
		            	})
		    	        .rowGrouping({
		                    bExpandableGrouping: true,
		                    bExpandSingleGroup: false,
		                    iExpandGroupOffset: -1,
		                    asExpandedGroups: [""],
		                    
		                });
			        GridRowCount();
					function GridRowCount() {
				        $('span.rowCount-grid').remove();
				        $('input.expandedOrCollapsedGroup').remove();
				
				        $('.dataTables_wrapper').find('[id|=group-id]').each(function () {
				            var rowCount = $(this).nextUntil('[id|=group-id]').length;
				            $(this).find('td').append($('<span />', { 'class': 'rowCount-grid' }).append($('<b />', { 'text': '('+rowCount+')' })));
				        });
				
				        $('.dataTables_wrapper').find('.dataTables_filter').append($('<input />', { 'type': 'button', 'class': 'expandedOrCollapsedGroup collapsed', 'value': 'Expanded All Group' }));
				
				        $('.expandedOrCollapsedGroup').live('click', function () {
				            if ($(this).hasClass('collapsed')) {
				                $(this).addClass('expanded').removeClass('collapsed').val('Collapse All Group').parents('.dataTables_wrapper').find('.collapsed-group').trigger('click');
				            }
				            else {
				                $(this).addClass('collapsed').removeClass('expanded').val('Expanded All Group').parents('.dataTables_wrapper').find('.expanded-group').trigger('click');
				            }
			        	});
			        };
				}
				else{
					oTable = $('.setting_table').dataTable({
						"bJQueryUI" : true,
						"sPaginationType" : "full_numbers",
						"bDestroy":true,
						"bStateSave" : true,
					});
				}
			 //append actual classs for confirm dialog box to all enable and disable user
		     $(".disable_user").addClass("actual");	
		     $(".enable_user").addClass("actual");	
		     $(".unmerge_drug").addClass("actual");		  
		});
		<?php
		$this->session->unset_userdata('link_id');
		$this->session->unset_userdata('linkSub');
		}
		?>
		//What happens when editing/updating/disabling/enabling -- end
		
		$('.dropdown-toggle').click(function() {
			$('.setting_menus').dropdown();
		});
		
		//so which link was clicked?
			  $('.setting_menus li').on('click',function(){
			  	//Synchronizes stock balance
			  	if($(this).find('a').attr('class')=="stock_balance_synch"){
			  		//Starts synchronization
			  		$('#drug_stock_balance_synch').modal('show');
			  		drug_cons_synch();
			  		
			  	}
			  	//Synchronizes drug stock movment remaining balance
			  	else if($(this).find('a').attr('class')=="stock_movement_balance_synch"){
					$('#confirmbox').modal('show');
						//No clicked
						$('#confirmFalse').click(function(){
							
						});
						//Yes Clicked
						$('#confirmTrue').click(function(){
							synch_drug_movement_balance("1");
							$("#drug_stock__movement_balance_synch").modal("show");
							
						});
			  		
			  	}
			  	else{
			  		$(".settings_title").fadeIn("1000");
				  	$(".settings").css("display","none");
				  	$("#loadingDiv").css("display","block");
				  	var linkDomain=" ";
					link_id='#'+$(this).find('a').attr('id');
					linkSub=$(this).find('a').attr('class');
					linkIdUrl=link_id.substr(link_id.indexOf('#')+1,(link_id.indexOf('_li')-1));
					//Get actual page title
					linkTitle=$(this).find('a').attr('title');
					//Change the page title value
					$("#actual_page").html(linkTitle);
					//console.log(linkSub);
					$(".settings").load('<?php echo base_url();?>'+linkSub+'/'+linkIdUrl,function(){
						//$("input[type='text']").attr("required","required");
						$("#loadingDiv").css("display","none");
						$(".settings").css("display","block");
						//for brand management
				if( linkSub=="brandname_management"){
					$('#brand_name_table').dataTable({
		    			"bLengthChange": false,
		                "bPaginate": false,
		                "bJQueryUI": true,
		            	"bDestroy":true,
		            	"bInfo" : true,
						"sScrollX" : "100%",
						"bScrollCollapse" : true,
						"sScrollY" : "200px",
						"bStateSave" : true,
		            	})
		    	        .rowGrouping({
		                    bExpandableGrouping: true,
		                    bExpandSingleGroup: false,
		                    iExpandGroupOffset: -1,
		                    asExpandedGroups: [""],
		                    
		                });
			        GridRowCount();
					function GridRowCount() {
				        $('span.rowCount-grid').remove();
				        $('input.expandedOrCollapsedGroup').remove();
				
				        $('.dataTables_wrapper').find('[id|=group-id]').each(function () {
				            var rowCount = $(this).nextUntil('[id|=group-id]').length;
				            $(this).find('td').append($('<span />', { 'class': 'rowCount-grid' }).append($('<b />', { 'text': '('+rowCount+')' })));
				        });
				
				        $('.dataTables_wrapper').find('.dataTables_filter').append($('<input />', { 'type': 'button', 'class': 'expandedOrCollapsedGroup collapsed', 'value': 'Expanded All Group' }));
				
				        $('.expandedOrCollapsedGroup').live('click', function () {
				            if ($(this).hasClass('collapsed')) {
				                $(this).addClass('expanded').removeClass('collapsed').val('Collapse All Group').parents('.dataTables_wrapper').find('.collapsed-group').trigger('click');
				            }
				            else {
				                $(this).addClass('collapsed').removeClass('expanded').val('Expanded All Group').parents('.dataTables_wrapper').find('.expanded-group').trigger('click');
				            }
			        	});
			        };
				}
				else{
								oTable = $('.setting_table').dataTable({
									"bJQueryUI" : true,
									"sPaginationType" : "full_numbers",
									"bDestroy":true,
									"bStateSave" : true,
								});
							}
							//append actual classs for confirm dialog box to all enable and disable user
						    $(".disable_user").addClass("actual");	
						    $(".enable_user").addClass("actual");
						    $(".unmerge_drug").addClass("actual");		  		  		  	   		
					});
			  	}

				
				})/*end of which link was clicked*/
	
	}); 

</script>

<div class="center-content container-fluid">
	<?php
  	if($this->session->userdata("msg_success")){
  	?>
  	<div class="alert alert-success">
	  <button type="button" class="close" data-dismiss="alert">&times;</button>
	 <?php echo $this->session->userdata("msg_success");  ?>
	</div> 	
  	<?php
  	$this->session->unset_userdata("msg_success");
	}else if($this->session->userdata("msg_error")){
  	?>
	 <div class="alert alert-error">
	  <button type="button" class="close" data-dismiss="alert">&times;</button>
	 <?php echo $this->session->userdata("msg_error");  ?>
	</div> 		
  	<?php
  	$this->session->unset_userdata("msg_error");
  	}
  	?>
  	<div class="row-fluid">
  		<div class="span7">
			<div class="navbar" style="width:auto !important;">
				<div class="navbar-inner">
					<ul class="nav">
						<li class="dropdown">
							<a class="dropdown-toggle" role="button" data-toggle="dropdown" id="dLabel" href="#">Regimens<b class="caret"></b></a>
							<ul class="dropdown-menu setting_menus" role="menu" aria-labelledby="dLabel">
								<li>
									<a href="#" class="regimen_management" title="Regimen Management" id="index">Regimens</a>
								</li>
								<li>
									<a href="#" class="settings/listing/regimen_category" title="Regimen Category Management" id="index">Regimen Category</a>
								</li>
								<li>
									<a href="#" class="regimen_drug_management" title="Regimen Drug Management" id="index">Regimen Drugs</a>
								</li>
								<li>
									<a href="#" class="regimenchange_management" title="Regimen Change Reason Management" id="index">Regimen change reasons</a>
								</li>
								<li>
									<a href="#" class="settings/listing/regimen_service_type" title="Service Management" id="index">Regimen Service Type</a>
								</li>
								<li>
									<a href="#" class="order_settings/listing/sync_regimen" title="Order Regimen Management">Regimens Order</a>
								</li>
								<li>
									<a href="#" class="order_settings/listing/sync_regimen_category" title="Order Regimen Category Management">Regimen Category Order</a>
								</li>
							</ul>
						</li>
						<li class="divider-vertical"></li>
						<li class="dropdown">
							<a class="dropdown-toggle" role="button" data-toggle="dropdown" id="dLabel" href="#">Drugs<b class="caret"></b></a>
							<ul class="dropdown-menu setting_menus" role="menu" aria-labelledby="dLabel">
								<li>
									<a href="#" class="drugcode_classification" title="Drug Classification">Drug Classification</a>
								</li>
								<li>
									<a href="#" class="drugcode_management" title="Drug Code Management">Drug Codes</a>
								</li>
								<li>
									<a href="#" class="settings/listing/drug_unit" title="Drug Units Management">Drug Units</a>
								</li>
								<li>
									<a href="#" class="drug_stock_balance_sync/setConsumption" title="Drug Consumption">Drug Consumption</a>
								</li>
								<li>
									<a href="#" class="dose_management" title="Drug Dose Management">Drug Doses</a>
								</li>
								<li>
									<a href="#" class="settings/listing/drug_instructions" title="Drug Instructions Management">Drug Instructions</a>
								</li>
								<li>
									<a href="#" class="indication_management" title="Drug Indication Management">Drug Indications</a>
								</li>
								<li>
									<a href="#" class="drugsource_management" title="Drug Source Management">Drug Sources</a>
								</li>
								<li>
									<a href="#" class="drugdestination_management" title="Drug Destination Management">Drug Destinations</a>
								</li>
								<li>
									<a href="#" class="genericname_management" title="Generic Name Management" >Generic Names</a>
								</li>
								<li>
									<a href="#" class="brandname_management" title="Brand Name Management"> Brand Names</a>
								</li>
								<li>
									<a href="#" class="drug_stock_balance_sync/view_balance" title="Drug Balances Management">Drug Running Balance</a>
								</li>
								<li>
									<a href="#" class="dossing_chart" title="Drug Balances Management">Pediatric Dossing Chart</a>
								</li>
								<li>
									<a href="#" class="order_settings/listing/sync_drug" title="Order Drug Management">Drugs Order</a>
								</li>
							</ul>
						</li>
						<li class="divider-vertical"></li>
						<li class="dropdown">
							<a class="dropdown-toggle" role="button" data-toggle="dropdown" id="dLabel" href="#">Facility<b class="caret"></b></a>
							<ul class="dropdown-menu setting_menus" role="menu" aria-labelledby="dLabel">
								<li>
									<a href="#" class="facility_management" title="Facility Details Management">Facility Details</a>
								</li>
								<li>
									<a href="#" class="user_management" title="Users Management">Facility Users</a>
								</li>
								<li>
									<a href="#" class="client_management" title="Patient Source Management">Facility Patient Sources</a>
								</li>
								<li>
									<a href="#" class="order_settings/listing/sync_facility" title="Order Facility Management">Facility Order</a>
								</li>
								<!--<li>
									<a href="#" class="client_support" title="Facility Supporters Management">Facility Supporters</a>
								</li>-->
							</ul>
						</li>
						<li class="divider-vertical"></li>
						<li class="dropdown">
							<a class="dropdown-toggle" role="button" data-toggle="dropdown" id="dLabel" href="#">Others<b class="caret"></b></a>
							<ul class="dropdown-menu setting_menus" role="menu" aria-labelledby="dLabel">
								<li>
									<a href="#" class="settings/listing/ccc_store_service_point" title="CCC Store Service Point Management" id="index">CCC Store/Pharmacy</a>
								</li>
								<li>
									<a href="#" class="auto_management/index/true" title="Manual/Auto Scripts">Manual/Auto Scripts</a>
								</li>
								<li>
									<a href="#" class="nonadherence_management" title="Non Adherence Reason Management">Non Adherence reasons</a>
								</li>
								<li>
									<a href="#" class="patient_management/merge_list" title="Patient Merging" id="index">Patient Merging</a>
								</li>
								<li>
									<a href="#" class="settings/listing/pep_reason" title="PEP Management" id="index">PEP Reasons</a>
								</li>
								<li>
									<a href="#" class="settings/listing/transaction_type" title="Transaction Type Management" id="index">Transaction Types</a>
								</li>
								<li>
									<a href="#" class="visit_management" title="Visit Purpose Management">Visit Purpose</a>
								</li>
								<li>
									<a href="#" class="viral_load_manual" title="Patient Viral Load Results">Viral Load Results</a>
								</li>
								<li>
									<a href="#" class="settings/listing/patient_status" title="Patient Status Management">Patient Status</a>
								</li>
							</ul>
						</li>
						<!--<li class="divider-vertical"></li>
						<li class="dropdown">
							<a class="dropdown-toggle" role="button" data-toggle="dropdown" id="dLabel" href="#">Order<b class="caret"></b></a>
							<ul class="dropdown-menu setting_menus" role="menu" aria-labelledby="dLabel">
								
								
								
							</ul>
						</li>-->
					</ul>
				</div>
			</div>
		</div>
		<div class="span5">
			<div class="settings_title" style="display:none">
				<ul class="breadcrumb">
				  <li><a href="<?php echo site_url().'settings_management' ?>">Settings</a> <span class="divider">/</span></li>
				  <li class="active" id="actual_page"></li>
				</ul>
			</div>
		</div>
	</div>
	  	<div class="row-fluid">
  		<div class="span12">
  			<div class="settings well"></div>
  				<div id="loadingDiv" style="display:none;margin:10% 40% 20% 40%;" >
	             <img src="assets/images/loading_spin.gif">	
	            </div>
  		</div>
  		</div>
</div>