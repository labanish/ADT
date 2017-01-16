<div class="full-content container">
    <div class="row-fluid">
		<?php $this->load->view("reports/reports_top_menus_v");?>
	</div>
   <div class="row-fluid">
   	<div class="span6">
   		<h3>Overview</h3>
   		<div id="overview"></div>
   	</div>
   	<div class="span6">
   		<h3>ART vs Non-ART</h3>
   		<div id="service"></div>
   	</div>
   </div>
   <div class="row-fluid">
   	<div class="span6">
   		<h3>Male vs Female</h3>
   		<div id="gender"></div>
   	</div>
   	<div class="span6">
   		<h3>Age</h3>
   		<div id="age"></div>
   	</div>
   </div>
</div>


<!--custom script-->
<script type='text/javascript'>
    $(function(){
    	var charts = ["overview","service","gender","age"];
    	//Loop through Charts
    	$.each(charts,function(i,v){
    		var url = "<?php echo base_url().'report_management/getAdherence/'.$type.'/'.$start_date.'/'.$end_date.'/'; ?>"+v;
    		//Load charts
   	        load_charts(v,url);
    	});
    });


   function load_charts(div,url){
   	 //Load onto div
   	 $("#"+div).load(url);
   }
</script>