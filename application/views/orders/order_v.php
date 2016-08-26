<style>
	.tab-content{
		overflow:hidden !important;
		padding-bottom:10% !important; 
	}
	.dataTables_length{
		width:50% !important;
	}
</style>
<div class="container-fluid">	
	<!--row for notification and welcome message-->
	<div class="row-fluid">
		  <div class="span6">
			<?php 
			if($this->session->flashdata('order_delete')){
			?>
			<div class="alert alert-error">	
			 <button type='button' class='close' data-dismiss='alert'>&times;</button>
			 <?php		
				echo $this->session->flashdata('order_delete');
			?>
			</div>
			<?php
			 }else if ($this->session->flashdata('order_message')){
			 ?>
			 <div class="alert alert-success">	
			 <button type='button' class='close' data-dismiss='alert'>&times;</button>	
				<?php
				echo $this->session->flashdata('order_message');
				?>
			 </div>
			<?php
			}	
			?>
		   
	  	</div>
	  	<!-- This section is nolonger required. -->
					<!-- <div class="span5">
						<label style="float:right;"><i class="icon-user icon-black"></i> <b><?php echo $this->session->userdata("api_user");?> |</b> 
					  		<div class="dropdown" style="display:inline-block;">
								<a class="dropdown-toggle" data-toggle="dropdown" href="#"><i class="icon-wrench"></i> Updates</a> <strong> |</strong>
								<ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
									<li><a id="updates" href="#" class="updater"><i class="icon-refresh"></i> Update Records</a>
					                <li><a  href="#" class="updater"><i class="icon-download"></i> Update Settings</a>
								</ul>
							</div>
						<a href='<?php echo site_url("order/logout"); ?>'><i class="icon-off"></i> Logout</a></label>
					</div> -->
		<!-- End of non-required section required. -->
	</div>
	<!--row for tabs-->
	<div class="row-fluid">
		<div class="span12">
		  <ul class="nav nav-tabs">
			<li id="cdrr_btn" class="active">
				<a  href="#cdrrs">my CDRRs</a>
			</li>
			<li id="maps_btn">
				<a  href="#maps">my MAPs</a>
			</li>
			<!-- new section added for the offline modules -->
			<li id="templates_btn">
				<a  href="#templates">CDRRs and MAPs TEMPLATES</a>
			</li>
			<!-- End of the new section -->
            <!--ensure it shows when its a central site-->
            <?php 
               $facility_type = Facilities::getType($this -> session -> userdata("facility"));
                if($facility_type > 1 && $supplier_name ==="kemsa"){
            ?>		
			<li id="aggregate_btn">
				<a  href="#aggregate">my Aggregates</a>
			</li>
			<?php
                }
			?>
		  </ul>
		</div>
	</div>

	<!--row for table and buttons-->
    <div class="row-fluid">
	<div class="span12">
	<div class="tab-content">
		<div id="cdrrs" class="tab-pane active">
			<div class="menu_container">
				<?php
				echo $cdrr_buttons;
				echo $cdrr_filter;
				?>
			</div>
			<div class="cdrr_table table-responsive">
			<?php echo $cdrr_table;?>
			</div>
		</div>
		<div id="maps" class="tab-pane">
			<div class="menu_container">
				<?php
				echo $fmap_buttons;
				echo $maps_filter;
				?>
			</div>
			<div class="maps_table table-responsive">
			<?php echo $map_table; ?>
			</div>
		</div>
		<div id="aggregate" class="tab-pane">
			<div class="menu_container">
			</div>
			<div class="aggregate_table table-responsive">
			  <?php echo $aggregate_table;?>
			</div>
		</div>
		<!--New section  -->
		<div id="templates" class="tab-pane">
			<div class="menu_container">
			</div>
			<div class="table-responsive">
				<span id="test">
				<div class="span12" style="margin-top:1em;">
				<h4>CDRR Templates  <i><img class="img-rounded" style="height:30px;" src="<?php echo base_url().'assets/images/excel.jpg';?>"/> </i></h4>
				<div class="accordion-inner">
					<a href="<?php echo base_url().'downloads/modern-templates/F-CDRR for Satellite Sites.xlsx';?>"> <i class="icon-download-alt"></i> F-CDRR for Satellite Sites.xlsx</a>
				<div>
				</div class="accordion-inner">	
					<a href="<?php echo base_url().'downloads/modern-templates/D-CDRR for Central Sites.xlsx';?>"> <i class="icon-download-alt"></i> D-CDRR for Central Sites.xlsx</a>
				</div>
				<h4>MAPS Templates <i><img class="img-rounded" style="height:30px;" src="<?php echo base_url() . 'assets/images/excel.jpg';?>"/> </i></h4>
				<div class="accordion-inner">
					<a href="<?php echo base_url().'downloads/modern-templates/F-MAPS for Satellite Sites.xlsx';?>"><i class="icon-download-alt"></i> F-MAPS for Satellite Sites.xlsx</a>
				<div>
				</div class="accordion-inner">	
					<a href="<?php echo base_url().'downloads/modern-templates/D-MAPS for Central Sites.xlsx';?>"><i class="icon-download-alt"></i> D-MAPS for Central Sites.xlsx</a>
				</div>
				</div>
				</span>	

			</div>
		</div>
		<!-- End of New Section -->


	</div>
	</div>
	</div>
</div>

<!-- Modal to select a satellite facility -->
<div id="select_satellite" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<form id="fmFillOrderForm" action="<?php echo base_url().'order/create_order/cdrr/2'?>" method="post" style="margin:0 auto;">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
				×
			</button>
			<h3 id="myModalLabel">Satellite Facilities</h3>
		</div>
		<div class="modal-body">
			<table  cellpadding="5">
				<tr>
					<td><span id="msg_fill_order" class='message error'></span></td>
				</tr>
				<tr>
					<td colspan='2'>
					<select id="satellite_facility" name="satellite_facility" style="width:250px;height:35px;">
						<option value="0">--Select Facility--</option>
						<?php
						$options = array();
                         foreach($facilities as $facility){
                        // 	$options[$facility['facilitycode']]=$facility['name'];
						?>
						<option value="<?php echo $facility['facilitycode'];?>"><?php echo 'MFL CODE:'.$facility['facilitycode'].' '.$facility['name'];?></option>
						<?php 
						 }?>
					</select></td>
				</tr>
			</table>
		</div>
		<div class="modal-footer">
			<button class="btn" data-dismiss="modal" aria-hidden="true">
				Cancel
			</button>
			<input type="button" class="btn btn-primary" name="proceed" id="proceed" value="Go to Form">
			<a href="#">
			<input type="button" class="btn btn-primary" id="upload_excel_btn" value="Upload Excel">
			</a>
		</div>
	</form>
	<div id="excel_upload" style="text-align:center;display: none">
		<form id='fmImportData' name="frm" method="post" enctype="multipart/form-data" id="frm" action="<?php echo base_url()."order/import_order/cdrr"?>">
			<p>
				<input type="file"  name="file[]" size="30" multiple="multiple"  required="required" accept="application/vnd.ms-excel"/>
				<input name="btn_save" class="btn" type="submit"  value="Save"  style="width:80px; height:30px;"/>
			</p>
		</form>
	</div>
	<script type="text/javascript">
		$(document).ready(function() {
			$(".cdrr_filter").change(function(){
				var type=$(this).attr("id");
				var period_begin=$(this).attr("value");
				var base_url="<?php echo base_url();?>";
				var url=base_url+'order/get_orders/cdrr/'+period_begin;
				
				$.ajax({
				    url: url,
				    type: 'POST',
				    success: function(data) {
                        $('.cdrr_table').empty();
                        $(".cdrr_table").append(data);
                        
                        var oTable1 = $('#order_listing_cdrr').dataTable({
											"bJQueryUI" : true,
											"sPaginationType" : "full_numbers",
											"bStateSave" : true,
											"sDom" : '<"H"T<"clear">lfr>t<"F"ip>',
											"bProcessing" : true,
											"bServerSide" : false,
											"bAutoWidth" : false,
											"bDeferRender" : true,
											"bInfo" : true,
										});
										
						oTable1.fnSort([[1, 'desc']]);
				    }
				});	
			});
			
			
			$(".maps_filter").change(function(){
				var type=$(this).attr("id");
				var period_begin=$(this).attr("value");
				var base_url="<?php echo base_url();?>";
				var url=base_url+'order/get_orders/maps/'+period_begin;
				
				$.ajax({
				    url: url,
				    type: 'POST',
				    success: function(data) {
                        $('.maps_table').empty();
                        $(".maps_table").append(data);
                        
                        var oTable2= $('#order_listing_maps').dataTable({
											"bJQueryUI" : true,
											"sPaginationType" : "full_numbers",
											"bStateSave" : true,
											"sDom" : '<"H"T<"clear">lfr>t<"F"ip>',
											"bProcessing" : true,
											"bServerSide" : false,
											"bAutoWidth" : false,
											"bDeferRender" : true,
											"bInfo" : true,
										});
										
						oTable2.fnSort([[1, 'desc']]);
				    }
				});	
			});
				
			$("#msg_fill_order").css("display", "none");
			$("#upload_excel_btn").click(function() {
				$("#excel_upload").toggle();
			});
			//Validate before submitting
			$("#proceed").click(function() {
				if($("#satellite_facility").val() == 0) {
					$("#msg_fill_order").fadeIn("slow");
					$("#msg_fill_order").html("Please select a facility !");
				}
				//If everything is ok,submit the form
				else {
					$("#msg_fill_order").fadeOut("slow");
					$("#msg_fill_order").html("");
					$("#fmFillOrderForm").submit();
				}
			});
			var 
			
			//cdrr table
			oTable1 = $('#order_listing_cdrr').dataTable({
				"bJQueryUI" : true,
				"sPaginationType" : "full_numbers",
				"sDom" : '<"H"T<"clear">lfr>t<"F"ip>',
				"bProcessing" : true,
				"bServerSide" : false,
				"bAutoWidth" : false,
				"bDeferRender" : true,
				"bInfo" : true,
				"bStateSave" : true,

			});
			oTable1.fnSort([[1, 'desc']]);
			//maps table
			var oTable2 = $('#order_listing_maps').dataTable({
				"bJQueryUI" : true,
				"sPaginationType" : "full_numbers",
				"sDom" : '<"H"T<"clear">lfr>t<"F"ip>',
				"bProcessing" : true,
				"bServerSide" : false,
				"bAutoWidth" : false,
				"bDeferRender" : true,
				"bInfo" : true,
				"bStateSave" : true,
			});
			oTable2.fnSort([[1, 'desc']]);
			
          //aggregate table
          	var oTable3 = $('#order_listing_aggregate').dataTable({
				"bJQueryUI" : true,
				"sPaginationType" : "full_numbers",
				"sDom" : '<"H"T<"clear">lfr>t<"F"ip>',
				"bProcessing" : true,
				"bServerSide" : false,
				"bAutoWidth" : false,
				"bDeferRender" : true,
				"bInfo" : true,
				"bStateSave" : true,
			});
			oTable3.fnSort([[1, 'desc']]);
		});

	</script>
</div>
<!-- Modal to select a satellite facility end -->
<script type="text/javascript">

	$(document).ready(function() {
	  var base_url="<?php echo base_url();?>";
	  var online = navigator.onLine;
	  	if(online==true){
	      $(".updater").show();
	      //run auto update for orders
	        //update_orders();	
	      	window.setInterval(function(){
		     update_orders();	
	        },900000);
		  
	    }else{
	       $(".updater").hide();
	    }
	    
	  //update function
	  
	  $(".updater").click(function(){
	  	$.blockUI({ message: '<h3><img width="30" height="30" src="<?php echo asset_url().'images/loading_spin.gif' ?>" /> synchronizing...</h3>' }); 
	  	var type=$(this).attr("id");
	  	if(type=="updates"){
	  	  var link=base_url+"order/get_updates";	
	  	}else{
	  	  var link=base_url+"order/api_sync";	
	  	}
			$.ajax({
				    url: link,
				    type: 'POST',
				    success: function(data) {
					   window.location = base_url + "order"; 
				    }
		    });
	  });	    
	    
	  $(".delete_order").live('click',function(event){
	  		event.preventDefault();
	  		var href=$(this).attr('href');
	  		$(this).attr("href","#");
	  		if(href !="#"){
				$("#confirmsubmission").modal("show");
				$(".conf_maps_body").html(
					'<p id="cMessage" class="alert alert-danger" >Are you sure you want to delete this report? This process is irreversible.</p><form id="fmDeleteOrder"></form>'
				)
				$("#fmDeleteOrder").attr("action",href);
			}
	  });
	  $(".order_btn").click(function(){
	  	if($(this).attr("id")=="cTrue"){
	  		$("#fmDeleteOrder").submit();
	  	}

	  });
	  /*
	   * Delete order report end
	   */

		$("#cdrr_btn").click(function() {
			$("#maps_btn").removeClass();
			$("#aggregate_btn").removeClass();
			$("#templates_btn").removeClass();
			$(this).addClass("active");
			$("#cdrrs").show();
			$("#maps").hide();
			$("#aggregate").hide();
			$("#templates").hide();
		});
		$("#maps_btn").click(function() {
			$("#cdrr_btn").removeClass();
			$("#aggregate_btn").removeClass();
			$("#templates_btn").removeClass();
			$(this).addClass("active");
			$("#maps").show();
			$("#cdrrs").hide();
			$("#aggregate").hide();
			$("#templates").hide();

		});
		$("#aggregate_btn").click(function() {
			$("#cdrr_btn").removeClass();
			$("#maps_btn").removeClass();
			$("#templates_btn").removeClass();
			$(this).addClass("active");
			$("#aggregate").show();
			$("#maps").hide();
			$("#cdrrs").hide();
			$("#templates").hide();
		});
		$("#templates_btn").click(function() {
			$("#cdrr_btn").removeClass();
			$("#maps_btn").removeClass();
			$("#aggregate_btn").removeClass();
			$(this).addClass("active");
			$("#templates").show();
			$("#maps").hide();
			$("#cdrrs").hide();
			$("#aggregate").hide();
		});
		
		$('.btn_satellite').click(function(){//check which button was clicked
			var btn_id=$(this).attr('id');
			if(btn_id=='btn_new_cdrr_satellite'){
				$('#fmFillOrderForm').attr('action','<?php echo base_url().'order/create_order/cdrr/2'?>');
				$('#fmImportData').attr('action','<?php echo base_url().'order/import_order/cdrr'?>');
			}
			else if(btn_id=='btn_new_maps_satellite'){
				$('#fmFillOrderForm').attr('action','<?php echo base_url().'order/create_order/maps/2'?>');
				$('#fmImportData').attr('action','<?php echo base_url().'order/import_order/maps'?>');
			}
		});
	});
	
	function update_orders(){
		 var base_url="<?php echo base_url();?>";
		 var link=base_url+"order/get_updates/1";
		    $.ajax({
				    url: link,
				    type: 'POST',
				    success: function(data) {
				       if(data !=2){
					   window.location = base_url + "order"; 
					   }
				    }
		    });
	}
</script>
<?php
	if($this->session->userdata("order_go_back")){
		
		if($this->session->userdata("order_go_back")=="cdrr"){
			?>
			<script type="text/javascript">
				$(document).ready(function(){
					$("#maps_btn").removeClass();
					$("#aggregate_btn").removeClass();
					$(this).addClass("active");
					$("#maps").hide();
					$("#maps_wrapper").hide();
					$("#aggregate").hide();
					$("#aggregate_wrapper").hide();
					$("#cdrrs").show();
					$("#cdrrs_wrapper").show();
					
				});
			</script>
			
			<?php
		}
		else if($this->session->userdata("order_go_back")=="fmaps"){
			?>
			<script type="text/javascript">
					$(document).ready(function(){
					$("#cdrr_btn").removeClass();
					$("#aggregate_btn").removeClass();
					$("#maps_btn").addClass("active");
					$("#cdrrs").hide();
					$("#cdrrs_wrapper").css("display","none");
					$("#aggregate").hide();
					$("#aggregate_wrapper").hide();
					$("#maps").show();
					$("#maps_wrapper").show();
					
				});
			</script>
			
			<?php
		}
		
	}
	else{
		?>
		<script type="text/javascript">
		$(document).ready( function () {
			$("#maps_btn").removeClass();
			$("#aggregate_btn").removeClass();
			$(this).addClass("active");
			$("#maps").hide();
			$("#maps_wrapper").hide();
			$("#aggregate").hide();
			$("#aggregate_wrapper").hide();
			$("#cdrrs").show();
			$("#cdrrs_wrapper").show();
		});
		</script>
		<?php	
	}
	?>
