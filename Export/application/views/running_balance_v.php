<style type="text/css">
	.progress{
		margin-top:0;
	}
	.settings{
		overflow:visible;
	}
	
</style>

<div class="container-fluid">
    <div class="row-fluid">
	   <div class="span10">
	   	<h3>Running Balance Settings</h3>
	   </div>
    </div>
    <div class="row-fluid">
	   <div class="span10">
	   		<div class="row-fluid">
	   			<div class="span4"><label>Select Drugs</label></div>
	   			<div class="span6"><select class="input-xxlarge multiselect validate" id="list_drugs" name="list_drugs" multiple="multiple"></select></div>
	   		</div>
	   		<div class="row-fluid">
	   		      <div class="span10"><button type="button" id="balance_btn" class="btn btn-primary">Fix Running Balances</button></div>
	   		</div>
	   </div>
    </div>
    <p></p>
    <div class="row-fluid">
        <div class="span10">
            <label><p class="text-warning">Overall Progress</p></label>
			<div id="overall_progress_bar" class="progress active">
				<div id="balance_overall_progress" class="bar" style="width:0%;"></div>
			</div>
        </div>
    </div>
</div>

<script src="<?php echo base_url();?>assets/scripts/bootstrap/bootstrap-multiselect.js"></script>
<script src="<?php echo base_url();?>assets/scripts/plugin/jquery-validate/jquery.validate.min.js"></script>
<script src="<?php echo base_url();?>assets/scripts/select2-3.4.8/select2.js"></script>
<script type="text/javascript">
  $(document).ready(function(){
	$(".loadingDiv").css("display","none");
	//Load drugs
	loadListDrugs();
	
	
  	$("#balance_btn").on('click',function(){
        $(this).attr("disabled",true);
	    var drugs = checkDrugsSelected();
	    if(drugs==0){//No drugs selected
	    	bootbox.alert("<h4>Drugs Selection</h4>\n\<hr/><center>Please select at least one drug </center>");
	    	$("#balance_btn").removeAttr("disabled"); 
	    	return;
	    }
	    var counter = 0;
	    var allSelected = $("#list_drugs option:not(:selected)").length == 0;
	    if(allSelected){//Is Select all was clicked, counter start at one
	    	counter = 1;	
	    }
	    total=drugs.length;
	    $("#balance_overall_progress").css("width","0");
    	getRunningBalance(drugs,total,counter);
    });

  });

  function getRunningBalance(drugs,total,counter){
  	var drug = drugs[counter];
  	var count = counter+1;
  	link = '<?php echo base_url(); ?>drug_stock_balance_sync/getRunningBalance';
  	$.ajax({
	    url: link,
	    type: 'post',
	    data: {
	    	"drug_id": drug
	       },
	    success: function(data){
	    	//Overall percentage
			overall_progress = (count/total) *100; 
			overall_progress = Math.round(overall_progress); 
			width_overall = overall_progress+"%";
			//Update overall progress bar
			$("#balance_overall_progress").text(width_overall);
			$("#balance_overall_progress").attr("aria-valuenow",overall_progress);
			$("#balance_overall_progress").css("width",width_overall);
			if(count!=total){
				getRunningBalance(drugs,total,count);
			}else{
				$("#balance_btn").removeAttr("disabled"); 
			}
			
	   },
	   error:function(jqXHR, textStatus) {
		  bootbox.alert("<h4>Balance Fix Error</h4>\n\<hr/><center>"+jqXHR.responseText+"</center>");
		  $("#balance_btn").removeAttr("disabled"); 
	   }
    });
  }
  
  
  function loadListDrugs(){
  	var _url = '<?php echo base_url(); ?>inventory_management/getAllDrugs';
  	$.ajax({
	    url: _url,
	    type: 'post',
	    dataType: "json",
	    success: function(data){
	    	$("#list_drugs option").remove();
	    	var count = data.length;
	    	count =count-1;
	    	$.each(data,function(i,v){
	    		$("#list_drugs").append("<option value='"+v.id+"'>"+v.drug+"</option>"); 
	    		if(count==i){
	    			$('.multiselect').multiselect({
					    	includeSelectAllOption : true,
							maxHeight : 300,
							enableFiltering : true,
							filterBehavior : 'both',
							enableCaseInsensitiveFiltering : true,
							filterPlaceholder : 'Search'
					});
	    		}
	    	})
	    	
	    }
    });
  }
  
  function checkDrugsSelected(){//Function to check if database tables to be migrated were selected
		//Variable to check if all database tables are selected, true if all are selected, false if not
		  var allSelected = $("#list_drugs option:not(:selected)").length == 0;
		  var check = 0;
		  var selectedTables = $('#list_drugs').val();
		  if(allSelected){//If all drugs are selected
		  	check = selectedTables;
		  }else{
		  	 
		  	if(selectedTables==null){//If no drug was selected
		  		check = 0;
		  	}else{//Is some drugs were selected
		  		check = selectedTables;
		  	}
		  }
		  
		  return check;
	}
</script>







