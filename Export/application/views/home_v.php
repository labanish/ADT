<?php
$access_level = $this -> session -> userdata('user_indicator');
$user_is_administrator = false;
$user_is_nascop = false;
$user_is_pharmacist = false;
$user_is_facilityadmin = false;

if ($access_level == "system_administrator") {
	$user_is_administrator = true;
}
if ($access_level == "pharmacist") {
	$user_is_pharmacist = true;

}
if ($access_level == "nascop_staff") {
	$user_is_nascop = true;
}
if ($access_level == "facility_administrator") {
	$user_is_facilityadmin = true;
}



if($this->session->userdata("changed_password")){
	$message=$this->session->userdata("changed_password");
	echo "<p class='error'>".$message."</p>";
	$this->session->set_userdata("changed_password","");
}
?>

<script type="text/javascript">
	//Retrieve the Facility Code
	var facility_code = "<?php echo $this -> session -> userdata('facility');?>";
	var facility_name = "<?php echo $this -> session -> userdata('facility_name');?>";   
</script>
		<style type="text/css">
		  .full-content{padding:0;}
		  #ui-datepicker-div{
				zoom:1;
		  }
		  .tile h3{
		  	zoom:0.8;
		  }
		  #ui-datepicker-div{
					zoom:0.8;
			  }
		</style>


<script type="text/javascript">
$(document).ready(function() {
      var period=30;
      var location=2;
      $('h3 .btn-danger').hide();
    
      //Get Today's Date and Upto Saturday
      var someDate = new Date();
      var dd = ("0" + someDate.getDate()).slice(-2);
      var m = ("0" + (someDate.getMonth() + 1)).slice(-2);
      var y = someDate.getFullYear();
      var fromDate="<?php echo $monday = date('d-M-Y',strtotime('monday this week'));?>";  
      var endDate="<?php echo $saturday = date('d-M-Y',strtotime('saturday this week')); ?>";
      
      $("#enrollment_start").val(fromDate);
      $("#enrollment_end").val(endDate);
      
      $("#visit_start").val(fromDate);
      $("#visit_end").val(endDate);
    
	   $(".loadingDiv").show();
	   var expiry_link="<?php echo base_url().'facilitydashboard_management/getExpiringDrugs/';?>"+period+'/'+location;
	   var enrollment_link="<?php echo base_url().'facilitydashboard_management/getPatientEnrolled/';?>"+fromDate+'/'+endDate;
	   var visits_link="<?php echo base_url().'facilitydashboard_management/getExpectedPatients/';?>"+fromDate+'/'+endDate;
       $('#chart_area').load(expiry_link);
       $('#chart_area2').load(enrollment_link);
       $('#chart_area3').load(visits_link);
       $('#table1').load('<?php echo base_url().'facilitydashboard_management/getStockSafetyQty'?>',function(){
				$('#stock_level').dataTable({
					"bJQueryUI": true,
	        		"sPaginationType": "full_numbers",	
	        		"bFilter": false,
	        		"bLengthChange": false
	            });
	   });
    //Toggle
	var chartID;
	var graphID;
	var chartLink;

	$('.more').click(function(){
		$('h3 .btn-success').hide();
		$('h3 .btn-danger').show();
		var myID = $(this).attr('id');
		switch(myID){
		case'drugs-more':
		$('.tile').hide();
		$('#drugs-chart').show();
		chartID='#drugs-chart';
		graphID="#chart_area";
		chartLink="<?php echo base_url().'facilitydashboard_management/getExpiringDrugs/';?>"+period+'/'+location;
	
		break;
		case'enrollment-more':
		$('.tile').hide();
		$('#enrollment-chart').show();
		chartID='#enrollment-chart';
		graphID="#chart_area2";
		chartLink="<?php echo base_url().'facilitydashboard_management/getPatientEnrolled/';?>"+fromDate+'/'+endDate;
	  
		break;
		case'appointment-more':
		$('.tile').hide();
		$('#appointments-chart').show();
		chartID='#appointments-chart';
		graphID="#chart_area3";
		chartLink="<?php echo base_url().'facilitydashboard_management/getExpectedPatients/';?>"+fromDate+'/'+endDate;

		break;
		case'stock-more':
		$('.tile').hide();
		$('#stocks-chart').show();
		chartID='#stocks-chart';
		graphID="#table1";
		break;
		}
		
		  $(chartID).animate({height:'80%',width:'100%'}, 500);
		  $(graphID).load(chartLink);

	});
	
	$('.less').click(function(){
		$('h3 .btn-success').show();
		$('h3 .btn-danger').hide();
		var myID = $(this).attr('id');
		
		switch(myID){
		case'drugs-less':
		$('.tile').show();
		 $(graphID).load(chartLink);
		break;
		case'enrollment-less':
		$('.tile').show();
		 $(graphID).load(chartLink);
		break;
		case'appointment-less':
		$('.tile').show();
		 $(graphID).load(chartLink);
		break;
		case'stock-less':
		$('.tile').show();
		break;
		
		}
 $(chartID).animate({height:'45%',width:'49%'}, 500);

	});
	
    
		    $('.generate').click(function(){
                 var button_id=$(this).attr("id");
                 if(button_id=="expiry_btn"){
                 	 period = $('.period').val();
		    	     location = $('.location').val();
		    	     var expiry_link="<?php echo base_url().'facilitydashboard_management/getExpiringDrugs/';?>"+period+'/'+location;
		    	 	 $('#chart_area').load(expiry_link);           	
                 }else if(button_id=="enrollment_btn"){
                 	 var from_date=$("#enrollment_start").val();
                 	 var to_date=$("#enrollment_end").val();
                 	 var enrollment_link="<?php echo base_url().'facilitydashboard_management/getPatientEnrolled/';?>"+from_date+'/'+to_date;         	 
                     $('#chart_area2').load(enrollment_link);
                 }else if(button_id=="appointment_btn"){
                 	 var from_date=$("#visit_start").val();
                 	 var to_date=$("#visit_end").val();
                 	 var visits_link="<?php echo base_url().'facilitydashboard_management/getExpectedPatients/';?>"+from_date+'/'+to_date;
                     $('#chart_area3').load(visits_link);
                 }else if(button_id=="stockout_btn"){
                 	 period=$("#store_location").val();
                 	 $('#table1').load('<?php echo base_url().'facilitydashboard_management/getStockSafetyQty/'?>'+period,function(){

	                 });
                 } else if(button_id=="usage_btn"){                	
                 	 period=$("#usage_period").val();
                 	 $('#chart_area77').load('<?php echo base_url().'admin_management/getSystemUsage/'?>'+period);
                 } else if(button_id=="access_btn"){                	
                 	 var from_date=$("#enrollment_start").val();
                 	 var to_date=$("#enrollment_end").val();
                 	 $('#chart_area78').load('<?php echo base_url().'admin_management/getWeeklySumary/'?>'+from_date+'/'+to_date);	
                 }
            });
		});
    </script>

<div class="main-content">
	<?php
	if(!$user_is_administrator){
	?>
	<div class="center-content">
		<div id="expDiv>"></div>
		<div class="tile" id="drugs-chart">
			<h3>Summary of Drugs Expiring <br/>in 
				<select style="width:auto" class="period">
					<option value="7">7 Days</option>
					<option value="14">14 Days</option>
				   <option value="30" selected=selected>1 Month</option>
				   <option value="90">3 Months</option>
				   <option value="180">6 Months</option>
			</select> at
			<select style="width:auto" class="location">
				   <option value="1">Main Store</option>
				   <option  selected=selected value="2">Pharmacy</option>
			</select> 
			<button class="generate btn btn-warning" style="color:black" id="expiry_btn">Get</button>
			<button class="btn btn-success more" id="drugs-more">Large</button>
			<button class="btn btn-danger less" id="drugs-less">Small</button>
			</h3>
			
			<div id="chart_area">
				<div class="loadingDiv" style="margin:20% 0 20% 0;" ><img style="width: 30px;margin-left:50%" src="<?php echo asset_url().'images/loading_spin.gif' ?>"></div>
			</div>
			
		</div>

		<div class="tile" id="enrollment-chart">
			<h3>Weekly Summary of Patient Enrollment <br/>From
				<input type="text" placeholder="Start" class="span3" id="enrollment_start"/> To
				<input type="text" placeholder="End" class="span3" id="enrollment_end" readonly="readonly"/>
				<button class="generate btn btn-warning" style="color:black" id="enrollment_btn">Get</button>
				<button class="btn btn-success more" id="enrollment-more">Large</button>
			<button class="btn btn-danger less" id="enrollment-less">Small</button>
				 </h3>
			<div id="chart_area2">
				<div class="loadingDiv" style="margin:20% 0 20% 0;" ><img style="width: 30px;margin-left:50%" src="<?php echo asset_url().'images/loading_spin.gif' ?>"></div>
			</div>
		</div>
		<div class="tile" id="appointments-chart">
			<h3>Weekly Summary of Patient Appointments
				<br/>From
				<input type="text" placeholder="Start" class="span3" id="visit_start"/> To
				<input type="text" placeholder="End" class="span3" id="visit_end" readonly="readonly" />
				<button class="generate btn btn-warning" style="color:black" id="appointment_btn">Get</button>
				<button class="btn btn-success more" id="appointment-more">Large</button>
			<button class="btn btn-danger less" id="appointment-less">Small</button>
				</h3>
			<div id="chart_area3">
						<div class="loadingDiv" style="margin:20% 0 20% 0;"><img style="width: 30px;margin-left:50%" src="<?php echo asset_url().'images/loading_spin.gif' ?>"></div>		
			</div>
		</div>
		<div class="tile" id="stocks-chart">
			<h3>Stocks About to Run Out at
			<br/>
			<select style="width:auto" class="location" id="store_location"> 
				   <option value="1">Main Store</option>
				   <option  selected=selected value="2">Pharmacy</option>
			</select> 	
			<button class="generate btn btn-warning" style="color:black" id="stockout_btn">Get</button>
			<button class="btn btn-success more" id="stock-more">Large</button>
			<button class="btn btn-danger less" id="stock-less">Small</button>
			</h3>		
			<div id="table1" style="overflow:scroll;">
			 	<div class="loadingDiv" style="margin:20% 0 20% 0;" ><img style="width: 30px;margin-left:50%" src="<?php echo asset_url().'images/loading_spin.gif' ?>"></div>
			</div>
		</div>
</div>
	<?php }if($user_is_administrator){ $this->load->view("sysadmin_home_v");}?>
</div>

<script type="text/javascript">
$(document).ready(function(){
	var base_url="<?php echo base_url(); ?>"; 
	var month=new Array();
	month[0]="Jan";
	month[1]="Feb";
	month[2]="Mar";
	month[3]="Apr";
	month[4]="May";
	month[5]="Jun";
	month[6]="Jul";
	month[7]="Aug";
	month[8]="Sep";
	month[9]="Oct";
	month[10]="Nov";
	month[11]="Dec";   		      	   
    $("#enrollment_start").datepicker({
			yearRange : "-120:+0",
			maxDate : "0D",
			dateFormat :'dd-M-yy',
			changeMonth : true,
			changeYear : true,
			beforeShowDay: function(date){ 
	           var day = date.getDay(); 
	           return [day == 1];
	        }
	});			
			
			$("#visit_start").datepicker({
					yearRange : "-120:+0",
					dateFormat : 'dd-M-yy',
					changeMonth : true,
					changeYear : true,
					beforeShowDay: function(date){ 
                                   var day = date.getDay(); 
                                   return [day == 1];
                                   }
			});
						
			//Visit Onchange Events
			$("#visit_start").change(function(){
				var from_date=$(this).val();
				var someDate = new Date(from_date);
                var numberOfDaysToAdd = 5;
                var to_date=new Date(someDate.setDate(someDate.getDate() + numberOfDaysToAdd)); 
                var dd = ("0" + to_date.getDate()).slice(-2);
                var mm = to_date.getMonth();
                var y = to_date.getFullYear();
                var someFormattedDate =dd+'-'+month[mm]+'-'+y;
				$("#visit_end").val(someFormattedDate);
			});
			
			//Enrollments Onchange Events
			$("#enrollment_start").change(function(){
				var from_date=$(this).val();
				var someDate = new Date(from_date);
                var numberOfDaysToAdd = 5;
                var to_date=new Date(someDate.setDate(someDate.getDate() + numberOfDaysToAdd)); 
                var dd = ("0" + to_date.getDate()).slice(-2);
                var mm = to_date.getMonth();
                var y = to_date.getFullYear();
                var someFormattedDate =dd+'-'+month[mm]+'-'+y;
				$("#enrollment_end").val(someFormattedDate);
			});
			
		      });

</script>