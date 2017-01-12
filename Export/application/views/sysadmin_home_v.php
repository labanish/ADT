<style type="text/css">
  #ui-datepicker-div{
	zoom:1;
  }
</style>
<div class="center-content">
	<div>
    <div>
		<ul class="breadcrumb">
		  <li><a href="<?php echo site_url().'home_controller/home' ?>"  id='goHome'><i class="icon-home"></i><strong>Home</strong></a> 
		  	<span class="divider">/</span></li>
		  <li class="active" id="actual_page"></li>
		</ul>
	</div>
   <div>
	<?php
  	if($this->session->userdata("msg_success")){
  		?>
  		<div class="alert alert-block alert-success">
		    <button type="button" class="close" data-dismiss="alert">&times;</button>
		    <h4>Saved!</h4>
		    <?php echo $this->session->userdata("msg_success"); ?>
		</div>
  	<?php
  	$this->session->unset_userdata("msg_success");
	}
  		
  	else if($this->session->userdata("msg_error")){
  		?>
  		<div class="alert alert-block alert-danger">
		    <button type="button" class="close" data-dismiss="alert">&times;</button>
		    <h4>Error!</h4>
		    <?php echo $this->session->userdata("msg_error"); ?>
		</div>
  	<?php
  	$this->session->unset_userdata("msg_error");
  	}
	?>
	</div>
	</div>
    <div id="display_content">
	<div class="tile" id="drugs-chart">
			<h3>System Usage Summary <br/>For the last
				<select style="width:auto" class="period" id="usage_period">
					<option value="7">7 Days</option>
					<option value="14">14 Days</option>
				   <option value="30" selected=selected>1 Month</option>
				   <option value="90">3 Months</option>
				   <option value="180">6 Months</option>
			</select>
			<button class="generate btn btn-warning" id="usage_btn">Go</button>
			<button class="btn btn-success more" id="drugs-more">Larger</button>
			<button class="btn btn-danger less" id="drugs-less">Smaller</button>
			</h3>
			
			<div id="chart_area77">
				<div class="loadingDiv" style="margin:20% 0 20% 0;" ><img style="width: 30px;margin-left:50%" src="<?php echo asset_url().'images//loading_spin.gif' ?>"></div>
			</div>
			
   </div>
   <div class="tile" id="enrollment-chart">
			<h3>Weekly Access Log Summary <br/>From
				<input type="text" placeholder="Start" class="span3" id="enrollment_start"/> To
				<input type="text" placeholder="End" class="span3" id="enrollment_end" readonly="readonly"/>
				<button class="btn btn-warning generate" id="access_btn">Go</button>
				<button class="btn btn-success more" id="enrollment-more">Larger</button>
			<button class="btn btn-danger less" id="enrollment-less">Smaller</button>
				 </h3>
			<div id="chart_area78">
				<div class="loadingDiv" style="margin:20% 0 20% 0;"  ><img style="width: 30px;margin-left:50%" src="<?php echo asset_url().'images/loading_spin.gif' ?>"></div>
			</div>
  </div>
  </div>		
</div>
<script type="text/javascript">
	$(document).ready(function(){
	  var base_url="<?php echo base_url();?>";
	  var default_link="<?php echo $this -> session ->userdata('default_link'); ?>";		
		
	  //Get Today's Date and Upto Saturday
      var someDate = new Date();
      var dd = ("0" + someDate.getDate()).slice(-2);
      var mm = ("0" + (someDate.getMonth() + 1)).slice(-2);
      var y = someDate.getFullYear();

      var fromDate="<?php echo $monday = date('Y-m-d',strtotime('monday this week'));?>";     
      var numberOfDaysToAdd = 5;
      var to_date=new Date(someDate.setDate(someDate.getDate() + numberOfDaysToAdd)); 
      var dd = ("0" + to_date.getDate()).slice(-2);
      var mm = ("0" + (to_date.getMonth() + 1)).slice(-2);
      var y = to_date.getFullYear();
      var endDate =y+'-'+mm+'-'+dd;
		//Load Charts	
	    var period=30;	
		var chart1_link="<?php echo base_url().'admin_management/getSystemUsage/';?>"+period
		var chart2_link="<?php echo base_url().'admin_management/getWeeklySumary/';?>"+fromDate+'/'+endDate;
        $('#chart_area77').load(chart1_link);
        $('#chart_area78').load(chart2_link);
		
		
		if(default_link){
			default_link = base_url + "admin_management/" + default_link;	
			$("#display_content").load(default_link,function(){
				$('.dataTables').dataTable({
					"bJQueryUI" : true,
		            "sPaginationType" : "full_numbers",
		            "bStateSave" : true,
		            "sDom" : '<"H"<"clear">lfrT>t<"F"ip>',
					"bProcessing" : true,
					"bServerSide" : false,
					"bAutoWidth" : true,
					"bDeferRender" : true,
					"bInfo" : true,
					"bScrollCollapse" : true,
					"bDestroy" : true,
					"fnInitComplete": function() {
				        this.css("visibility", "visible");
				    }
	            });
	       });			
		}
		
		$("#goHome").click(function(){
			<?php 
			$this -> session ->set_userdata('default_link',""); 
			?>
		});
		
		$(".admin_link").click(function(){
			$("#display_content").empty();
			var link = $(this).attr("id");		
			var link = base_url + "admin_management/" + link;			
			$("#display_content").load(link,function(){
				$('.dataTables').dataTable({
					"bJQueryUI" : true,
		            "sPaginationType" : "full_numbers",
		            "bStateSave" : true,
		            "sDom" : '<"H"<"clear">lfrT>t<"F"ip>',
					"bProcessing" : true,
					"bServerSide" : false,
					"bAutoWidth" : true,
					"bDeferRender" : true,
					"bInfo" : true,
					"bScrollCollapse" : true,
					"bDestroy" : true,
					"fnInitComplete": function() {
				        this.css("visibility", "visible");
				    }
	            });
	       });			
		});
		setTimeout(function(){
			$(".message").fadeOut("2000");
		},6000);
		

	});
</script>
