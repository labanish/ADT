<script type="text/javascript">
	$(document).ready(function(){
		synch_drug_balance("1");
	});
	
	function synch_drug_balance(stock_type){
		//Get number total number of drugs
		var _url="<?php echo base_url().'drug_stock_balance_sync/getTotalDrugs'; ?>";
		var stock_type=stock_type;
		$.ajax({
			url : _url,
			type : 'POST',
			data : {
				"check_if_malicious_posted" : "1"
			},
			success : function(data) {
				data = $.parseJSON(data);
				//Count number of drugs
				var count_drugs=data.count;
				
				$("#div_tot_drugs").css("display","block");
				$("#tot_drugs").html(count_drugs);
				
				var remaining_drugs=1;
				$.each(data.drugs,function(key,value){
					
					//Start synch
					var drug_id=value.id;
					var link="<?php echo base_url().'drug_stock_balance_sync/synch_balance/'; ?>";
					var div_width=(remaining_drugs/count_drugs)*100;
					$.ajax({
						url : link,
						type : 'POST',
						data : {
							"check_if_malicious_posted" : "1","drug_id":drug_id,"stock_type":stock_type
						},
						success : function(data1) {
							remaining_drugs+=1;
							div_width1=(remaining_drugs/count_drugs)*100;
							div_width=div_width1+"%";
							if(stock_type==1){
								$(".bar_store").css("width",div_width);
							}
							else if(stock_type==2){
								$(".bar_pharmacy").css("width",div_width);
							}
							
							//div_percentage=div_width1.toFixed(0);
							//$(".bar").html(div_percentage);
							if(remaining_drugs==count_drugs){
								//$(".icon_drug_balance").css("display","block");
								//$(".progress").removeClass("active");
								//Start sync for pharmacy
								if(stock_type==1){
									synch_drug_balance(2);
									
								}
								
							}
						}
					});
				
				});
			}
		});
		}
</script>
<div class="center-content">
<div id="drug_stock_balance_synch">  
    <div class="row">  
	    <div class="span6"> 
	    	<div id="div_tot_drugs" style="display: none">
	    		Total number of drugs :<strong><span id="tot_drugs">e</span></strong>
	    	</div> 
		    <h4>Main Store Transactions</h4>  
		    <div class="progress progress_store progress-striped active">  
			  <div class="bar bar_store" style="width: 0%;"></div> 
			</div>  
			<h4>Pharmacy Transactions</h4>  
		    <div class="progress progress_pharmacy progress-striped active">  
			  <div class="bar bar_pharmacy" style="width: 0%;"></div> 
			</div>
	    </div>  
    </div>  
</div> <!-- /container -->  
</div>