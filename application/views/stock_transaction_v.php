<style>
	#inner_wrapper{
		top:120px;
	}
	.main-content{
		margin:0 auto;	
	}
	#sql{
		display:none;
	}
	.full-content{
		display:table;
	}
	table th{
		text-align:left;
	}
	#drugs_table {
		background: #FFF;
		font-size: 12px;
	}
	#drugs_table input, #drugs_table select{
		font-size:12px;
		height:2.5em;
		width: 4.2em;
	}
	#transaction_type_details{
		padding:1%;
		display:table-cell;
		width:15%;
	}
	#transaction_type_details th{
		text-align:left;
	}
	#drug_details{
		padding:1%;
		width:82%;
		display:table-cell;
	}
	#drugs_table{
		width: 100%;
		
	}
	#drugs_table th{
		
		text-align:center;
	}
	#sub_title{
		margin-bottom:15px;
		font-size:16px;
	}
	#submit_section{
		text-align:right;
		padding-right:10px;	
	}
	.icondose {
        background: #FFFFFF url(../../assets/images/dropdown.png) no-repeat 95% 4px;		
    }
</style>

<script type="text/javascript">
	$(document).ready(function(){
	var background_type="<?php echo $stock_type;?>";
	optgroup = 'Others';
	if(background_type=="1"){
		$("#stock_div").css("background","#ff6666");
	}else{
		$("#stock_div").css("background","#9CF");
	}
		
		
		$("#btn_submit").attr("disabled","disabled");
		$("#btn_print").attr("disabled","disabled");
		var today = new Date();
		 today_date = ("0" + today.getDate()).slice(-2)
                 today_year = today.getFullYear();
		 today_month = today.getMonth();
		
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
		var today_full_date =today_date+ "-"+month[today_month] + "-" + today_year ;
		$("#transaction_date").attr("value", today_full_date);
		
		$(".t_source").css("display","none");
		$(".t_destination").css("display","none");
	    $(".t_picking_list").css("display","none");
	    $(".send_email").css("display","none");
		$("#drug_details").css("pointer-events","none");
		
		//Transaction type change
		$("#select_transtype").change(function(){
			$("#drugs_table tr:gt(1)").remove();			
			reset_table_rows();			
			 $(".send_email").css("display","none");
			 $(".batchselect").find("option:gt(0)").remove();
			 $(".batchselect").val("");
			 $("#reference_number").val("");
			 $("#select_destination").val("");
			 $("#select_source").val("");
			 
			 $("#btn_print").css("display","none");
			batch_type = 0;
			is_batch_load = false;
			//If transaction type not selected
			if($("#select_transtype").attr("value")==0){
				$("#drug_details").css("pointer-events","none");
				$(".t_source").css("display","none");
				$(".t_destination").css("display","none");
				$(".send_email").css("display","none");
				$("#btn_submit").attr("disabled","disabled");
				$("#btn_print").attr("disabled","disabled");
			}else{
				//if transaction type is selected	
				$("#btn_submit").removeAttr('disabled');
				$("#btn_print").removeAttr('disabled');
				$("#drug_details").css("pointer-events","auto");
				
				//Coming in
				trans_type=$("#select_transtype option:selected").text().toLowerCase().replace(/ /g,'');
				trans_effect=$("#select_transtype option:selected").attr('label');
				//Check which type of transaction is selected
				stock_type=<?php echo  $stock_type ?>;

				
				//Stock coming in
				if(trans_type.indexOf('received') != -1 || trans_type.indexOf('balanceforward')!= -1 || (trans_type.indexOf('returns')!= -1 && trans_effect==0) || (trans_type.indexOf('adjustment')!= -1 && trans_effect==1) || trans_type.indexOf('startingstock')!= -1 || trans_type.indexOf('physicalcount')!= -1 ){
					$("#select_drug ").html("<option value='0'>Loading drugs ...</option> ");

					
					//Whether to show source or not
					if(trans_type.indexOf('receivedfrom')!= -1 || (trans_type.indexOf('returns')!= -1 && trans_effect==0)){
						$(".t_destination").css("display","none");
						$(".t_source").css("display","block");
					}
					else{
						$(".t_destination").css("display","none");
						$(".t_source").css("display","none");
					}
				
					
					//Renitialize drugs table 
					reinitializeDrugs(stock_type,trans_type);
					
					//If transaction is returns to, get only drugs that are in stock		
					if(trans_type.indexOf('returns')!= -1 && trans_effect==0){
						//Get drugs that have a balance
						loadDrugs(stock_type);
						is_batch_load = stock_type;
					}
					else{	
						is_batch_load=false;
						loadAlldrugs();
					}
					
					
				}
				
				//Going out(quantity_out)
				else {
					$("#select_drug ").html("<option value='0'>Loading drugs ...</option> ");
					
					//If transaction is issued, display print transaction button
					if(trans_type.indexOf('issue')!= -1){
						$("#btn_print").css("display","inline");
					}
					
					//In case of dispensed to patients,adjustments,returns,losses,expiries, hide destination
					if(trans_type.indexOf('dispensed')!= -1 || trans_type.indexOf('adjustment')!= -1 ||  trans_type.indexOf('loss')!= -1 || trans_type.indexOf('expir') != -1){
						$(".t_destination").css("display","none");
						$(".t_source").css("display","none");
					}
					else{
						$(".t_destination").css("display","block");
						$(".t_source").css("display","none");
					}
					
					//Renitialize drugs table 
					reinitializeDrugs(stock_type,trans_type);
					
					if(trans_type.indexOf('returns')!= -1 && trans_effect==1){//If transaction is returns from, get all drugs
						is_batch_load =false;
						loadAlldrugs();
					}
					else{
						//Get drugs that have a balance
						loadDrugs(stock_type);
						is_batch_load = stock_type;
					}
					 
				}
			}
		})
		
		
		//Source change
		$("#select_source").change(function(){
			$(".send_email").css("display","none");	
			stock_type=<?php echo  $stock_type ?>;
			selected_source=$("#select_source option:selected").text().toLowerCase().replace(/ /g,'');
			var supplier_name='<?php echo $supplier_name ?>';
			pipeline_name=supplier_name.toLowerCase().replace(/ /g,'');
			//show email
			optgrp=$("#select_source :selected").parent().attr('label');
			
			var request =$.ajax({
		        type:"post",
		        url:"<?php echo base_url().'system_management/checkConnection'; ?>",
		        dataType: "json",
		         
		    });
		    request.done(function(msg){
		    	if(msg=="1"){
		    		if(trans_type.indexOf('receivedfrom')!= -1 && optgrp.indexOf('Central Site')!= -1){
						$(".send_email").css("display","block");	
					} 
		    	}
		    });
		    request.fail(function(jqXHR, textStatus) {
                bootbox.alert("<h4>InternetConnection Problem</h4>\n\<hr/>\n\<center>Could not check internet connection : </center>" + jqXHR.responseText);
            });
		   
			//Get type of optgroup selected
			optgroup =$('#select_source :selected').parent().attr('label');
			
			if(optgroup=='Stores'){//If selected option is a store (Pharmacy or Main Store)
				
				if(trans_effect==1){//Transaction is coming in
					//If transaction if receive from Main Store to Pharmacy, get available drugs in the Main Store
					selected_source_id=$("#select_source option:selected").val();
					loadDrugs(selected_source_id);
					is_batch_load = selected_source_id;
				}
				else if(trans_effect==0){
					//If transaction if receive from Main Store to Pharmacy, get available drugs in the Main Store
					loadDrugs(stock_type);
					is_batch_load = stock_type;
				}
				
			}
			else{//If others, load all
				//If transaction is returns to(-) with negative effect, get balance from actual store
				if(trans_type.indexOf('returns')!= -1 && trans_effect==0){
					//If transaction if receive from Main Store to Pharmacy, get available drugs in the Main Store
					loadDrugs(stock_type);
					is_batch_load = stock_type;
				}else{
					is_batch_load =false;
					loadAlldrugs();
				}
				
				
			}
			
		})
		
		$("#select_destination").change(function(){
			
			stock_type=<?php echo  $stock_type ?>;
			selected_destination=$("#select_destination option:selected").text().toLowerCase().replace(/ /g,'');
			var supplier_name='<?php echo $supplier_name ?>';
			pipeline_name=supplier_name.toLowerCase().replace(/ /g,'');
			
			//Get type of optgroup selected
			var optgroup =$('#select_destination :selected').parent().attr('label');
			
			if(optgroup=='Stores'){//If selected option is a store (Pharmacy or Main Store)
				
				if(trans_effect==1){//Transaction is coming in
					selected_destination_id=$("#select_destination option:selected").val();
					loadDrugs(selected_destination_id);
					is_batch_load = selected_destination_id;
				}
				else if(trans_effect==0){
					//If transaction if receive from Main Store to Pharmacy, get available drugs in the Main Store
					loadDrugs(stock_type);
					is_batch_load = stock_type;
				}
				
				
			}
			else {//If others, load all
				 if(trans_effect==0){
				 	loadDrugs(stock_type);
					is_batch_load = stock_type;
				 }
				 else if(trans_effect==1){
				 	is_batch_load =false;
				 	loadAlldrugs();
				 }
				
			}
		});
		
		//Picking list changed
		$("#picking_list_name").change(function(){
			var rowCount = $('#drugs_table tr').length;
			//Check if details were entered before submiting
			if(rowCount==2){
			
			}
			var link="<?php echo base_url().'inventory_management/getOrderDetails' ?>";
			//Get list of orders
			var order_id=$("#picking_list_name").val();
			$.ajax({
				url : link,
				type : 'POST',
				dataType : 'json',
				data: {"order_id":order_id},
				success : function(data) {
					var data_count=data.length;
					$("#count_dispatched_drugs").val(data_count);
					var x=1;
					var last_row=$('#drugs_table tr:last');
					$.each(data, function(i, jsondata) {
						var drug_id=data[i]['id'];
						var cdrr_id=data[i]['cdrr_id'];
						var resupply=data[i]['resupply'];
						var drug_unit=data[i]['unit'];
						var pack_size=data[i]['pack_size'];
						var drug_selected=last_row.find(".drug").val();
						var cloned_object = $('#drugs_table tr:last').clone(true);
						var drug_row = cloned_object.attr("drug_row");
						var next_drug_row = parseInt(drug_row) + 1;
						cloned_object.attr("drug_row", next_drug_row);
						cloned_object.find(".remove").show();
						var packs = cloned_object.find(".pack");
						var expiry_id = "expiry_date_" + next_drug_row;
						cloned_object.find(".cdrr_id").attr('value',cdrr_id);
						cloned_object.find(".drug").attr('value',drug_id);
						cloned_object.find(".unit").attr('value',drug_unit);
						cloned_object.find(".pack").attr('value',resupply);
						cloned_object.find(".pack_size").attr('value',pack_size);
						var expiry_selector = "#" + expiry_id;
						$(expiry_selector).datepicker({
							defaultDate : new Date(),
							dateFormat : $.datepicker.ATOM,
							minDate : 0,
							changeYear : true,
							changeMonth : true,
				            onSelect : function(){
		                        var date= new Date($(this).datepicker('getDate')); 
		                        var c_date=new Date(today_year,today_month,today_date);
		                        var diff = date.getTime() - c_date.getTime();
		                        var months = Math.ceil(diff/(1000 * 60 * 60 * 24*30));
		                        if(date<c_date){
		                            bootbox.alert("<h4>Expiry Notice</h4>\n\<hr/><center>An expired date being updated! </center>" );
		                            $("#btn_submit").attr("disabled","disabled");
		                        }else if(months<=6){
		                           bootbox.alert("<h4>Expiry Notice</h4>\n\<hr/><center>The expiry date updated is within 6 months! </center>" );
		                            $("#btn_submit").removeAttr('disabled');
		                        }else{
		                           $("#btn_submit").removeAttr('disabled');
		                        }
				            }
						});
						
						//Validity check
						if(!isNaN(pack_size) && pack_size.length > 0 && !isNaN(resupply) && resupply.length > 0) {
							var qty=resupply * pack_size;
							cloned_object.find(".quantity ").attr('value',qty);
						}
						cloned_object.insertAfter('#drugs_table tr:last');
						refreshDatePickers();
						if(x==data_count){
							$('#drugs_table tbody tr:first').remove();
						}
						x++;
						
					});
	
				}
			});
			
			
		});
		
		//Drug change
		$("#select_drug").change(function(){
			var trans_type=$("#select_transtype option:selected").text().toLowerCase().replace(/ /g,'');
			var trans_effect=$("#select_transtype option:selected").attr('label');
			var stock_type=<?php echo  $stock_type ?>;
			//Get source selected
			var selected_source=$("#select_source option:selected").text().toLowerCase().replace(/ /g,'');
			resetFields($(this));
			var row=$(this);
			var selected_drug=$(this).val();
			//Check if transaction type needs batch load
			if(is_batch_load!=false){
				$(this).closest("tr").find("#batch_1").css("display","none");
				row.closest("tr").find(".b_list").css("display","none");
				$(this).closest("tr").find("#batchselect_1").css("display","block");
				$(this).closest("tr").find("#batchselect_1 ").html("<option value='0'>Loading batches ...</option> ");
				batch_type = 1;
				if(trans_type.indexOf('received') != -1){
					var selected_source=$("#select_source option:selected").text().toLowerCase().replace(/ /g,'');
					if(optgroup.indexOf('Stores')!= -1){
						var stock_type=$("#select_source option:selected").val();
					}
				}
				loadBatches(selected_drug,stock_type,row);
			}
			else if(is_batch_load==false){//No need to load batches
				batch_type = 0;
				row.closest("tr").find("#batchselect_1").css("display","none");
				//If transaction is adjustment (+), load batches for that drug
				if(trans_type.indexOf('adjustment')!= -1 && trans_effect==1){
					row.closest("tr").find(".b_list").css("display","block");
					row.closest("tr").find("#batch_1").css("display","none");
					row.closest("tr").find("#batch_2").css("display","none"); //Fix for expiry date showing in batch list
					getBatchList(selected_drug,stock_type,row);
				}else{
					row.closest("tr").find("#batch_1").css("display","block");
					row.closest("tr").find(".b_list").css("display","none");
					var selected_drug=$(this).val();
					loadDrugDetails(selected_drug,row);
				}
				
				
				
				
			}
			
			
		});
		
		
		//Batch change
		$(".batchselect").change(function(){
			var trans_type=$("#select_transtype option:selected").text().toLowerCase().replace(/ /g,'');
			var trans_effect=$("#select_transtype option:selected").attr('label');
			var stock_type=<?php echo  $stock_type ?>;
			
			//If transaction type if received from
			if(trans_type.indexOf('received') != -1){
				var selected_source=$("#select_source option:selected").text().toLowerCase().replace(/ /g,'');
				if(optgroup.indexOf('Stores')!= -1){
					stock_type=$("#select_source option:selected").val();
				}
			}
			
			resetFields($(this));
			var row=$(this);
			
			//Get batch details(balance,expiry date)
			if($(this).val()!=0){
				var batch_selected=$(this).val();
				var selected_drug=row.closest("tr").find("#select_drug").val();
				
				loadBatchDetails(selected_drug,is_batch_load,batch_selected,row);
			}else{
				resetFields($(this));
			}
		});
               //reset fields
		$("#reset").click(function (e){
                 e.preventDefault(); 
                 bootbox.confirm("<h4>Reset?</h4>\n\<hr/><center>Are you sure?</center>", function(res){
                 if(res){ 
                     clearForm("#stock_form");
                     reset_table_rows();
                 }else{
                     
                 }
                 });
              });
              
		//Add datepicker for the transaction date
		$("#transaction_date").datepicker({
			defaultDate : new Date(),
			dateFormat : 'dd-M-yy',
			changeYear : true,
			changeMonth : true
		});
		//Add datepicker for the expiry date
		$("#expiry_date").datepicker({
			defaultDate : new Date(),
			dateFormat : $.datepicker.ATOM,
			minDate : 0,
			changeYear : true,
			changeMonth : true,
                        
                        onSelect : function(){
                            var date= new Date($(this).datepicker('getDate')); 
                            var c_date=new Date(today_year,today_month,today_date);
                            var diff = date.getTime() - c_date.getTime();
                            var months = Math.ceil(diff/(1000 * 60 * 60 * 24*30));
                            if(date<c_date){
                                bootbox.alert("<h4>Expiry Notice</h4>\n\<hr/><center>An expired date being updated! </center>" );
                                $("#btn_submit").attr("disabled","disabled");
                            }else if(months<=6){
                               bootbox.alert("<h4>Expiry Notice</h4>\n\<hr/><center>The expiry date updated is within 6 months! </center>" );
                                $("#btn_submit").removeAttr('disabled');
                            }else{
                               $("#btn_submit").removeAttr('disabled');
                            }
                           }
		});
		//Check if number of packs has changed and automatically calculate the total
		$(".pack").keyup(function() {
			//updateCommodityQuantity($(this));
			
		});
		
		//Calculate the total cost automatically
		$("#unit_cost").keyup(function() {
			updateTotalCost($(this));
		});
		
		$(".pack").change(function() {
			updateCommodityQuantity($(this));
		});
		
		$(".quantity").change(function() {
			updateCommodityQuantityUnit($(this));
		});
		
		$("#unit_cost").change(function() {
			updateTotalCost($(this));
		});
		$(".remove").click(function() {
                    var rem_row=this;
                    bootbox.confirm("<h4>Remove?</h4>\n\<hr/><center>Are you sure?</center>", function(res){
                                  if(res)
                                     $(rem_row).closest('tr').remove();  
                                  
                              });
		});
		
		$(".add").click(function() {
			var last_row=$('#drugs_table tbody tr:last');
			var drug_selected=last_row.find(".drug").val();
			var quantity_entered=last_row.find(".quantity").val();
			if(last_row.find(".quantity").hasClass("stock_add_form_input_error")){
                            //alert(quantity_entered)
				bootbox.alert("<h4>Excess Quantity</h4>\n\<hr/><center>Error !Quantity issued is greater than qty available!</center>");
			}
			
			else if(drug_selected==0 ){
				bootbox.alert("<h4>Drug Alert</h4>\n\<hr/><center>You have not selected a drug!</center>");
			}
			else if(quantity_entered=="" || quantity_entered==0){
				bootbox.alert("<h4>Quantity Alert</h4>\n\<hr/><center>Please Specify the Quantity of the Drug</center>");
			}
			else{
				var cloned_object = $('#drugs_table tr:last').clone(true);
				var drug_row = cloned_object.attr("drug_row");
				var next_drug_row = parseInt(drug_row) + 1;
				cloned_object.attr("drug_row", next_drug_row);
				var batch_id = "batch_" + next_drug_row;
				var batchselect_id = "batchselect_" + next_drug_row;
				var quantity_id = "quantity_" + next_drug_row;
				var expiry_id = "expiry_date_" + next_drug_row;
				var batch = cloned_object.find(".batch");
				var batchselect = cloned_object.find(".batchselect");
				batchselect.empty();
				var packs = cloned_object.find(".pack");
				var unit = cloned_object.find(".unit");
				var pack_size = cloned_object.find(".pack_size");
				var quantity = cloned_object.find(".quantity");
				var quantity_available = cloned_object.find(".quantity_available");
				var expiry_date = cloned_object.find(".expiry");
				var unit_cost = cloned_object.find(".unit_cost");
				var total_amount = cloned_object.find(".amount");
				var comment = cloned_object.find(".comment");
				cloned_object.find(".remove").show();
				batch.attr("id", batch_id);
				batchselect.attr("id", batchselect_id);
				quantity.attr("id", quantity_id);
				expiry_date.attr("id", expiry_id);
				batch.attr("value", "");
				batchselect.attr("value", "");
				quantity.attr("value", "");
				expiry_date.attr("value", "");
				packs.attr("value", "");
				pack_size.attr("value", "");
				unit.attr("value", "");
				quantity_available.attr("value", "");
				unit_cost.attr("value","");
				total_amount.attr("value","");
				comment.attr("value","");
				var expiry_selector = "#" + expiry_id;
		
				$(expiry_selector).datepicker({
					defaultDate : new Date(),
					dateFormat : $.datepicker.ATOM,
					minDate : 0,
					changeYear : true,
					changeMonth : true,
		            onSelect : function(){
                        var date= new Date($(this).datepicker('getDate')); 
                        var c_date=new Date(today_year,today_month,today_date);
                        var diff = date.getTime() - c_date.getTime();
                        var months = Math.ceil(diff/(1000 * 60 * 60 * 24*30));
                        if(date<c_date){
                            bootbox.alert("<h4>Expiry Notice</h4>\n\<hr/><center>An expired date being updated! </center>" );
                            $("#btn_submit").attr("disabled","disabled");
                        }else if(months<=6){
                           bootbox.alert("<h4>Expiry Notice</h4>\n\<hr/><center>The expiry date updated is within 6 months! </center>" );
                            $("#btn_submit").removeAttr('disabled');
                        }else{
                           $("#btn_submit").removeAttr('disabled');
                        }
		            }
				});
				cloned_object.insertAfter('#drugs_table tr:last');
				refreshDatePickers();
			}
			
	
			return false;
		});
		
		//Button Print Issued transaction
		$("#btn_print").click(function(){
			var total_rows = $("#drugs_table >tbody tr").length;
			var counter = 0;
			var data = $("#drugs_table >tbody tr");
			print_transactions(counter,total_rows,data);
		});
		
		
		function print_transactions(counter,total,data){
			
			if(counter<(total)){
				var _url="<?php echo base_url().'inventory_management/print_issues'; ?>";
				var request=$.ajax({
					url		: _url,
					type	: 'post',
					data	: {"source"		:'<?php echo $store ?>',
							   "destination":$("#select_destination :selected").text(),
							   "drug"		:$(data[counter]).closest('tr').find(".drug :selected").text(),
							   "unit"		:$(data[counter]).closest('tr').find(".unit").attr("value"),
							   "batch"		:$(data[counter]).closest('tr').find(".batchselect").attr("value"),
							   "pack_size"	:$(data[counter]).closest('tr').find(".pack_size").attr("value"),
							   "expiry"		:$(data[counter]).closest('tr').find(".expiry").attr("value"),
							   "pack"		:$(data[counter]).closest('tr').find(".pack").attr("value"),
							   "quantity"	:$(data[counter]).closest('tr').find(".quantity").attr("value"),
							   "counter"	:counter,
							   "total"		:total},
					dataType: "html"
				});
				request.done(function(msg){
					
					counter++;
					if(counter==(total)){//If process done,open file
						window.open(msg);
					}else{
						print_transactions(counter,total,data);
					}
					
				});
				request.fail(function(jqXHR, textStatus) {
                    bootbox.alert("<h4>Drug Details Alert</h4>\n\<hr/>\n\<center>Could not print drug transactions : </center>" + jqXHR.responseText);
                });
				
			}
			
		}
		
		//Save transaction details
		$("#btn_submit").click(function(){
			var submit_btn = $(this)
			//Disable button
			submit_btn.attr("disabled","disabled");
			//Timestamp
			var time_stamp="<?php echo date('U');?>";
			var all_drugs_supplied=1;
			source_destination = '';
			var stock_transaction = '<?php echo $store; ?>';
			//Check drugs are from picking list
			if($("#picking_list_name").is(":visible")){
				//Check if all commoditities where supplied
				var count_dispatched_drugs=$("#count_dispatched_drugs").val();
				//Number drugs in drugs table
				var drugs_count=$("#drugs_table > tbody > tr").length;
				if(count_dispatched_drugs!=drugs_count){
					all_drugs_supplied=0;
				}
				
			}
			var emailaddress='';
			// Check if email address field is visible
			if($("#send_email").is(":visible")){
				//email=$("#send_email").text();
				var emailaddress=$("#send_email").val();
				//alert(emailaddress);
				if(emailaddress==""){
					bootbox.alert("<h4>Email</h4>\n\<hr/><center>Please enter an email address</center>");
					//Enable button
					submit_btn.removeAttr("disabled")
					return;
				}
			}
			//Check if select source is visible
			if($("#select_source").is(":visible")){
				optgroup =$('#select_source :selected').parent().attr('label');
				source_destination = $("#select_source").val();
				if($("#select_source").val()==0){
					bootbox.alert("<h4>Source</h4>\n\<hr/><center>Please select a source!</center>");
					//Enable button
					submit_btn.removeAttr("disabled")
					return;
				}
			}
			else if($("#select_destination").is(":visible")){
				optgroup =$('#select_destination :selected').parent().attr('label');
				source_destination = $("#select_destination").val();
				if($("#select_destination").val()==0){
					bootbox.alert("<h4>Destination</h4>\n\<hr/><center>Please select a destination !</center>");
					//Enable button
					submit_btn.removeAttr("disabled")
					return;
				}
			}
			else{
				optgroup = 'Others';
			}
			
			var trans_type=$("#select_transtype option:selected").text().toLowerCase().replace(/ /g,'');
			var trans_effect=$("#select_transtype option:selected").attr('label');
			var selected_source=$("#select_source option:selected").text();
			var selected_destination=$("#select_destination option:selected").text();
			var stock_type=<?php echo  $stock_type ?>;
			var last_row=$('#drugs_table tr');
			if(last_row.find(".quantity").hasClass("stock_add_form_input_error")){//VAlidation
				bootbox.alert("<h4>Quantity Alert</h4>\n\<hr/><center>There is a commodity that has a quantity greater than the quantity available!</center>");
				//Enable button
				submit_btn.removeAttr("disabled")
				return;
			}
			
			var rowCount = $('#drugs_table tr').length;
			//Check if details were entered before submiting
			if(rowCount==2){
				var drug_selected=last_row.find(".drug").val();
				var quantity_entered=last_row.find(".quantity").val();
				if(drug_selected==0 ){
					bootbox.alert("<h4>Drug Alert</h4>\n\<hr/><center>You have not selected a drug!</center>");
					//Enable button
					submit_btn.removeAttr("disabled")
					return;
				}
				else if(quantity_entered=="" || quantity_entered==0){
					bootbox.alert("<h4>Quantity Alert</h4>\n\<hr/><center>You have not entered any quantity!</center>");
					//Enable button
					submit_btn.removeAttr("disabled")
					return;
				}
				
			}
			
			
			
			var facility=<?php echo $facility ?>;
			var user=<?php echo $user_id ?>;
			
			//Before going any further, first calculate the number of drugs being recorded
			var drugs_count = 0;
			var c=0;
			<?php
			//Set a variable to store drugs are being added for filtering after saving
			$drug_names_transacted="";
			?>
			
			$.each($(".drug"), function(i, v) {
				//Check if batch number was entered
				if(batch_type==0){
					if($(this).closest("tr").find(".batch").attr("value")=="" || $(this).closest("tr").find(".expiry ").attr("value")==""){
						c=1;
						bootbox.alert("<h4>Batch and Expiry Date Alert</h4>\n\<hr/><left>Please make sure you have entered a batch number and selected an expiry date for all drugs!</left>");
						//Enable button
						submit_btn.removeAttr("disabled")
						return false;
					}
				}
				//Check if batch number was selected
				else if(batch_type==1){
					if($(this).closest("tr").find(".batch").is(":visible") && $(this).closest("tr").find(".batch").attr("value")==0){
						c=1;
						bootbox.alert("<h4>Batch and Expiry Date Alert</h4>\n\<hr/><left>Please make sure you have entered a batch number and selected an expiry date for all drugs!</left>");
						//Enable button
						submit_btn.removeAttr("disabled")
						return false;
					}
					else if($(this).closest("tr").find(".batch_select").is(":visible") && $(this).closest("tr").find(".batch_select").attr("value")==0){
						c=1;
						
						bootbox.alert("<h4>Batch and Expiry Date Alert</h4>\n\<hr/><left>Please make sure you have entered a batch number and selected an expiry date for all drugs!</left>");
						//Enable button
						submit_btn.removeAttr("disabled")
						return false;
					}
				}
				
				if($(this).attr("value")) {
					drugs_count++;
				}
			});
			//If no drugs were selected, exit
			if(drugs_count == 0) {
				//Enable button
				submit_btn.removeAttr("disabled")
				return;
			}
			//Retrieve all form input elements and their values
			var dump = retrieveFormValues();
			//Call this function to do a special retrieve function for elements with several values
			var drugs = retrieveFormValues_Array('drug');
			
			
			if(is_batch_load==false){//Select batches not visible
				var batches = retrieveFormValues_Array('batch');
			}
			else{
				var batches = retrieveFormValues_Array('batchselect');
			}
			var transaction_type=dump["transaction_type"];
			var cdrr_id=retrieveFormValues_Array('cdrr_id');
			var expiries = retrieveFormValues_Array('expiry');
			var quantities = retrieveFormValues_Array('quantity');
			var packs = retrieveFormValues_Array('pack');
			var unit_costs = retrieveFormValues_Array('unit_cost');
			var comments = retrieveFormValues_Array('comment');
			var amounts = retrieveFormValues_Array('amount');
			var available_quantity=retrieveFormValues_Array('available_quantity');
			var balance=0;
			
			//If transaction is from store
			var stock_type=<?php echo $stock_type; ?>;
			//Stockin coming in
			if(trans_type.indexOf('received') != -1 || trans_type.indexOf('balanceforward')!= -1 || (trans_type.indexOf('returns')!= -1 && trans_effect==1) || (trans_type.indexOf('adjustment')!= -1 && trans_effect==1) || trans_type.indexOf('startingstock')!= -1 || trans_type.indexOf('physicalcount')!= -1) {
				var quantity_choice = "quantity";
				var quantity_out_choice = "quantity_out";
			} else {
				var quantity_choice = "quantity_out";
				var quantity_out_choice = "quantity";
			}
			
			
			//After getting the number of drugs being recorded, create a unique entry (sql statement) for each in the database in this loop
			var sql_queries = "";
			var source="";
			var destination="";
			var remaining_drugs=$('#drugs_table>tbody tr').length;
			for(var i = 0; i < drugs_count; i++) {
				
				//Check if batch number was entered or selected for all drugs
				if(c==1){
					//Enable button
					submit_btn.removeAttr("disabled")
					return false;
				}
				var _url="<?php echo base_url().'inventory_management/save'; ?>";
				var get_qty_choice=quantity_choice;
				var get_qty_out_choice=quantity_out_choice;
				var get_source=dump["source"];
				var get_destination=dump["destination"];
				var get_transaction_date=dump["transaction_date"];
				var ref_number=dump["reference_number"];
				var get_transaction_type=dump["transaction_type"];
				var get_cdrr_id=cdrr_id[i];
				var get_drug_id=drugs[i];
				var get_batch=batches[i];
				var get_expiry=expiries[i];
				var get_packs=packs[i];
				var get_qty=quantities[i];
				var get_available_qty=available_quantity[i];
				var get_unit_cost=unit_costs[i];
				var get_amount=amounts[i];
				var get_comment=comments[i];
				var get_stock_type=stock_type;
				var get_user=user;
			
				if(reference_number.value == ''){
					    bootbox.alert("<h4>Ref. / Order Number Required</h4>\n\<hr/><center>Please enter the reference_number! </center>" );
					    //$("#btn_submit").prop("disabled",false);
					    //Enable button
						submit_btn.removeAttr("disabled")
				}else{
					var request=$.ajax({
				     url: _url,
				     type: 'post',
				     data: {"emailaddress":emailaddress,"time_stamp":time_stamp,"cdrr_id":get_cdrr_id,"all_drug_supplied":all_drugs_supplied,"remaining_drugs":i,"quantity_choice":get_qty_choice,"quantity_out_choice":get_qty_out_choice,"source_name":selected_source,"destination_name":selected_destination,"source":get_source,"destination":get_destination,"source_destination":source_destination,"transaction_date":get_transaction_date,"reference_number":ref_number,"trans_type":trans_type,"trans_effect":trans_effect,"transaction_type":get_transaction_type,"drug_id":get_drug_id,"batch":get_batch,"expiry":get_expiry,"packs":get_packs,"quantity":get_qty,"available_qty":get_available_qty,"unit_cost":get_unit_cost,"amount":get_amount,"comment":get_comment,"optgroup":optgroup,"stock_type":get_stock_type,"stock_transaction":stock_transaction},
				     dataType: "json",
				     async: false,
				    });
				   
			    	request.always(function(data){
			    	//console.log(data);
			    	//return;
			    	//$("#list_drugs_transacted").append(data);
			    	remaining_drugs-=1;
			    	//console.log(data)
			    	if (data instanceof Array) {//If data was not inserted
						//alert(data[0]);
					}
					
					else if(remaining_drugs==0){
						//Get drugs transacted for filtering
						var drugs_transacted=$("#list_drugs_transacted").val();
						//If all commodities from picking list were supplied,Update status for order, from dispatched to delivered
						if(all_drugs_supplied==0){
							
							//Set session after completing transactions
							var _url="<?php echo base_url().'inventory_management/set_transaction_session'; ?>";
							var request=$.ajax({
								url: _url,
								type: 'post',
								data: {"remaining_drugs":remaining_drugs,"list_drugs_transacted":drugs_transacted},
								dataType: "json"
							});
							request.always(function(data){
								window.location.replace('<?php echo base_url().'inventory_management'?>');
							});
						}
						else{//Change picking list status
							var order_id=$("#picking_list_name").val();
							var _url="<?php echo base_url().'inventory_management/set_order_status'; ?>";
							var request=$.ajax({
								url: _url,
							    type: 'post',
							    data: {"order_id":order_id,"status":"4"},
							    dataType: "json"
							});
							request.always(function(data){
								//Set session after completing transactions
								var _url="<?php echo base_url().'inventory_management/set_transaction_session'; ?>";
								var request=$.ajax({
									url: _url,
									type: 'post',
									data: {"remaining_drugs":remaining_drugs,"list_drugs_transacted":drugs_transacted},
									dataType: "json"
								});
								request.always(function(data){
									window.location.replace('<?php echo base_url().'inventory_management'?>');
								});
							});
						}
						
						
					}
			    });


				}

				
			  
			};
			
			
		})
		
		//Batch select change
		$("#batchlist").bind('input', function () {
			var val = $('#batchlist').val();
			var expiry_date = $('#batch_2 option').filter(function() {
							        return this.value ==val;
							    }).text();
		    $(this).closest("tr").find(".batch").val(val);
			$(this).closest("tr").find(".expiry").val(expiry_date);
		});
		
	});
	
	//Reinitialize drugs table
	function  reinitializeDrugs(stock_type,trans_type){
		//------------Whether show select order from picking list or not
		//if(stock_type==1 && trans_type.indexOf('received') != -1 &&  selected_source.indexOf(pipeline_name) != -1){
			//$(".t_picking_list").css("display","block");
		//}
		//else{
			//Before reinitialize table, check if picking list combo box is visible
			if($(".t_picking_list").is(":visible") && $("#picking_list_name").val()!=0){
				//Clone drug table row
				var cloned_object = $('#drugs_table tr:last').clone(true);
				$('#drugs_table tbody tr').remove();
				$('#drugs_table tbody ').append(cloned_object);
				//Reset the list of drugs
				
				//Reset all the fields
				var row=$('#drugs_table tbody tr:first');
				resetFields(row);
			}
			if($(".remove").is(":visible")){
				//row.closest("tr").find(".remove").remove();
			}
			
			$(".t_picking_list").css("display","none");
			//$("#select_source").val("0");
			$("#picking_list_name").val("0");
			
		//}
		//------------Whether show select order from picking list or not end
	}
	
	function loadDrugs(stock_type){
		$("#select_drug ").html("<option value='0'>Loading drugs ...</option> ");
		var _url="<?php echo base_url().'inventory_management/getStockDrugs'; ?>";
		//Get drugs that have a balance
		var request=$.ajax({
	     url: _url,
	     type: 'post',
	     data: {"stock_type":stock_type},
	     dataType: "json"
	    });
	    request.done(function(data){
	    	$("#select_drug option").remove();
	    	$("#select_drug ").append("<option value='0'>Select commodity </option> ");
	    	$.each(data,function(key,value){
	    		//alert(value.drug);
	    		$("#select_drug ").append("<option value='"+value.id+"'>"+value.drug+"</option> ");
	    	});
	    })
	    
	    request.fail(function(jqXHR, textStatus) {
		  bootbox.alert("<h4>Drug Retrieval Alert</h4>\n\<hr/><center>Could not retrieve the list of drugs : </center>" + textStatus );
		});
	}
	
	function loadAlldrugs(){
		var selected_source=$("#select_source option:selected").text().toLowerCase().replace(/ /g,'');
		//Renitialize drugs table 
		reinitializeDrugs(stock_type,trans_type);
		
		$("#select_drug ").html("<option value='0'>Loading drugs ...</option> ");
		
		var _url="<?php echo base_url().'inventory_management/getAllDrugs'; ?>";
		//Get drugs that have a balance
		var request=$.ajax({
	     url: _url,
	     type: 'post',
	     dataType: "json"
	    });
	    request.done(function(data){
	    	$("#select_drug option").remove();
	    	$("#select_drug ").append("<option value='0'>Select commodity </option> ");
	    	$.each(data,function(key,value){
	    		//alert(value.drug);
	    		$("#select_drug ").append("<option value='"+value.id+"'>"+value.drug+"</option> ");
	    		
	    	});
	    })
	    
	    request.fail(function(jqXHR, textStatus) {
		  bootbox.alert("<h4>Drug List Alert</h4>\n\<hr/><center>Could not retrieve the list of drugs : </center>" + textStatus );
		});
		
		
	}
	
	function loadBatches(selected_drug,stock_type_selected,row){
		var _url="<?php echo base_url().'inventory_management/getBacthes'; ?>";
				
		var request=$.ajax({
	     url: _url,
	     type: 'post',
	     data: {"selected_drug":selected_drug,"stock_type":stock_type_selected},
	     dataType: "json"
	    });
	    request.done(function(data){
	    	row.closest("tr").find(".batchselect option").remove();
	    	row.closest("tr").find(".batchselect ").append("<option value='0'>Select batch </option> ");
	    	$.each(data,function(key,value){
	    		row.closest("tr").find("#unit").val(value.Name);
	    		row.closest("tr").find("#pack_size").val(value.pack_size);
	    		row.closest("tr").find(".batchselect").append("<option value='"+value.batch_number+"'>"+value.batch_number+"</option> ");
	    		
	    	});
	    });
	    request.fail(function(jqXHR, textStatus) {
		  bootbox.alert("<h4>Batch List Alert</h4>\n\<hr/><center>Could not retrieve the list of batches :</center> " + textStatus );
		});
	}
	
	function getBatchList(selected_drug,stock_type_selected,row){//Get list of batches for adjustment (+)
		var url_dose = "<?php echo base_url() . 'inventory_management/getBacthes'; ?>";
		//Get doses
        var request_dose = $.ajax({
            url: url_dose,
            type: 'post',
            data: {"selected_drug":selected_drug,"stock_type":stock_type_selected},
            dataType: "json"
        });
        request_dose.done(function(data) {
        	row.closest("tr").find(".dose option").remove();
            $.each(data, function(key, value) {
            	row.closest("tr").find("#unit").val(value.Name);
	    		row.closest("tr").find("#pack_size").val(value.pack_size);
            	row.closest("tr").find(".dose").append("<option value='" + value.batch_number + "' >" + value.expiry_date + "</option> ");
            });
        });
	}
	
	function loadDrugDetails(selected_drug,row){
		
		var _url="<?php echo base_url().'inventory_management/getDrugDetails'; ?>";
		var request=$.ajax({
	     url: _url,
	     type: 'post',
	     data: {"selected_drug":selected_drug},
	     dataType: "json"
	    });
	    request.done(function(data){
	    	$.each(data,function(key,value){
	    		row.closest("tr").find("#unit").val(value.Name);
	    		row.closest("tr").find("#pack_size").val(value.pack_size);
	    		
	    	});
	    });
	    request.fail(function(jqXHR, textStatus) {
		  bootbox.alert("<h4>Drug Details Alert</h4>\n\<hr/><center>Could not retrieve drug details :</center> " + textStatus );
		});
	}
	
	function loadBatchDetails(selected_drug,stock_type,batch_selected,row){
		var _url="<?php echo base_url().'inventory_management/getBacthDetails'; ?>";
		var request=$.ajax({
	     url: _url,
	     type: 'post',
	     data: {"selected_drug":selected_drug,"stock_type":stock_type,"batch_selected":batch_selected},
	     dataType: "json"
	    });
	    
	    request.done(function(data){
	    	$.each(data,function(key,value){
	    		row.closest("tr").find(".expiry").val(value.expiry_date);
	    		row.closest("tr").find(".quantity_available ").val(value.balance);
                        var month=today_month;
                        var t_date=new Date(today_year,month,today_date);
                        var e_date=new Date(value.expiry_date);
                        var diff = e_date.getTime() - t_date.getTime();
                        var months = Math.floor(diff/(1000 * 60 * 60 * 24*30));

                        if(e_date<t_date){
                           bootbox.alert("<h4>Expiry Notice</h4>\n\<hr/><center>The drug being transacted has expired! </center>" );
                           $("#btn_submit").attr("disabled","disabled");
                        }else if(months<=6){
                           bootbox.alert("<h4>Expiry Notice</h4>\n\<hr/><center>The drug being transacted expires within 6 months! </center>" );
                           $("#btn_submit").removeAttr('disabled');
                        }else{
                            $("#btn_submit").removeAttr('disabled');
                        }
	    	});
	    });
	    request.fail(function(jqXHR, textStatus) {
		 bootbox.alert("<h4>Batch Details Alert</h4>\n\<hr/><center>Could not retrieve batch details : </center>" + textStatus );
		});
	}
	//reset table rows
        function reset_table_rows(){
	  //remove all table tr's except first one
	  $("#drugs_table tbody").find('tr').slice(1).remove();
	  var row = $('#drugs_table tr:last');
	  //default options
	  row.find(".unit").val("");
      row.find(".pack_size").val("");
	  row.find(".batch").val("");
	  row.find(".batch option").remove();
      row.find("#date_0").val("");
	  row.find("#packs_1").val("");
	  row.find("#quantity_1").val("");
	  row.find("#available_quantity").val("");
	  row.find("#unit_cost").val("");
	  row.find("#total_amount").val("");
	  row.find(".comment").val("");
          $(".t_source").hide();
          $(".t_destination").hide();
	
	}
	function resetFields(row){
		//row.closest("tr").find(".pack_size").val("");
		row.closest("tr").find(".pack").val("");
		row.closest("tr").find(".icondose").val("");
		row.closest("tr").find(".quantity").val("");
		row.closest("tr").find(".expiry").val("");
		row.closest("tr").find(".quantity_available").val("");
		row.closest("tr").find(".unit_cost").val("");
		row.closest("tr").find("#total_amount").val("");
	}
        /*Beginning of clearForm function*/
      function clearForm(form) {
      // iterate over all of the inputs for the form
      // element that was passed in
      $(':input', form).each(function() {
        var type = this.type;
        var tag = this.tagName.toLowerCase(); // normalize case
        // it's ok to reset the value attr of text inputs,
        // password inputs, and textareas
        if (type == 'text' || type == 'password' || tag == 'textarea')
        if ( $(':input').is('[readonly]') ) { 
        
        }else{
            this.value = "";
        }
        // checkboxes and radios need to have their checked state cleared
        // but should *not* have their 'value' changed
        else if (type == 'checkbox' || type == 'radio')
          this.checked = false;
        // select elements need to have their 'selectedIndex' property set to -1
        // (this works for both single and multiple select elements)
        else if (tag == 'select')
            if($(this).attr('id')!='ccc_store_id'){
                this.selectedIndex = -1;
            } 
      });
    };
    /*End of clearForm Function*/
	function updateCommodityQuantity(pack_object) {
		var trans_type=$("#select_transtype option:selected").text().toLowerCase().replace(/ /g,'');
		var selected_source=$("#select_source option:selected").text().toLowerCase().replace(/ /g,'');
		var trans_effect=$("#select_transtype option:selected").attr('label');
		var packs = pack_object.attr("value");
		var pack_size = pack_object.closest("tr").find(".pack_size").attr("value");
		var quantity_holder = pack_object.closest("tr").find(".quantity");
		var available_quantity=pack_object.closest("tr").find(".quantity_available").val();
		var stock_type=<?php echo  $stock_type ?>;
		available_quantity=parseInt(available_quantity);
		
		if(!isNaN(pack_size) && pack_size.length > 0 && !isNaN(packs) && packs.length > 0) {
			var qty=packs * pack_size;
			//If stock is going out, check that qty issued to be <= to qty available
			
			//Transaction coming in
			if(trans_type.indexOf('received') != -1  || trans_type.indexOf('balanceforward')!= -1 || (trans_type.indexOf('returns')!= -1 && trans_effect==1) || (trans_type.indexOf('adjustment')!= -1 && trans_effect==1) || trans_type.indexOf('startingstock')!= -1 || trans_type.indexOf('physicalcount')!= -1 || $("#select_transtype").attr("value") == 0) {
				quantity_holder.css("background-color","#FFF");
				quantity_holder.attr("value",qty );
				//If transaction is received from a store, check if quantity exists in other store
				if(trans_type.indexOf('received') !=-1 && optgroup=="Stores"){

					if(available_quantity>=qty){
						quantity_holder.css("background-color","#FFF");
						quantity_holder.attr("value",qty );
						quantity_holder.removeClass("stock_add_form_input_error");
					}
					else{
						quantity_holder.attr("value",qty );
						quantity_holder.addClass("stock_add_form_input_error");
						quantity_holder.css("background-color","rgb(255, 92, 52)");
					}
				}
				
				
				
			} 
			//Transaction going out
			else {
				if(available_quantity>=qty){
					quantity_holder.css("background-color","#FFF");
					quantity_holder.attr("value",qty );
					quantity_holder.removeClass("stock_add_form_input_error");
				}
				else{
					quantity_holder.attr("value",qty );
					bootbox.alert("<h4>Excess Quantity</h4>\n\<hr/><center>Error !Quantity issued is greater than qty available!</center>");
					quantity_holder.addClass("stock_add_form_input_error");
					quantity_holder.css("background-color","rgb(255, 92, 52)");
				}
			}
			
			
			
		}
	}

	function updateCommodityQuantityUnit(quantity_object) {
		var trans_type=$("#select_transtype option:selected").text().toLowerCase().replace(/ /g,'');
		var selected_source=$("#select_source option:selected").text().toLowerCase().replace(/ /g,'');
		var trans_effect=$("#select_transtype option:selected").attr('label');
		var quantity=quantity_object.attr("value");
		var pack_size = quantity_object.closest("tr").find(".pack_size").attr("value");
		var pack_holder=quantity_object.closest("tr").find(".pack");
		var available_quantity=quantity_object.closest("tr").find(".quantity_available").val();
		var stock_type=<?php echo  $stock_type ?>;
		available_quantity=parseInt(available_quantity);	
		
		if(!isNaN(quantity) && quantity.length>0 && !isNaN(pack_size) && pack_size.length > 0) {
			var pck= quantity/pack_size;
			//If stock is going out, check that qty issued to be <= to qty available
			//Transaction coming in
			if(trans_type.indexOf('received') != -1  || trans_type.indexOf('balanceforward')!= -1 || (trans_type.indexOf('returns')!= -1 && trans_effect==1) || (trans_type.indexOf('adjustment')!= -1 && trans_effect==1) || trans_type.indexOf('startingstock')!= -1 || trans_type.indexOf('physicalcount')!= -1 || $("#select_transtype").attr("value") == 0) {
				pack_holder.css("background-color","#FFF");
				pack_holder.attr("value",pck );
			} 
			//Transaction going out
			else {
				//quantity is less than what is in stock
				if(parseInt(available_quantity)>=parseInt(quantity)){
					pack_holder.attr("value",pck);
					quantity_holder.css("background-color","#FFF");
					quantity_holder.attr("value",quantity);
					quantity_holder.removeClass("stock_add_form_input_error");

				}
				else{
					//quantity is greater than what is in stock
					pack_holder.attr("value",pck);
					quantity_holder.attr("value",quantity);
					bootbox.alert("<h4>Excess Quantity</h4>\n\<hr/><center>Error !Quantity issued is greater than qty available!</center>");
					quantity_holder.addClass("stock_add_form_input_error");
					quantity_holder.css("background-color","rgb(255, 92, 52)");
				}
			}
		}
	}
	
	function updateTotalCost(unit_cost_object) {
		var unit_cost = unit_cost_object.attr("value");
		var quantity_holder = unit_cost_object.closest("tr").find(".pack").attr("value");
		var total_cost=unit_cost_object.closest("tr").find(".amount");
		if(!isNaN(unit_cost) && unit_cost.length > 0 && !isNaN(quantity_holder) && quantity_holder.length > 0) {
			total_cost.attr("value", unit_cost * quantity_holder);
		}
		else{
			total_cost.attr("value",0);
		}
	}
	
	function refreshDatePickers() {
		var counter = 0;
		$('.expiry').each(function() {
			var new_id = "date_" + counter;
			$(this).attr("id", new_id);
			$(this).datepicker("destroy");
			$(this).not('.hasDatePicker').datepicker({
				defaultDate : new Date(),
				dateFormat : $.datepicker.ATOM,
				minDate : 0,
				changeYear : true,
				changeMonth : true,
	            onSelect : function(){
                    var date= new Date($(this).datepicker('getDate')); 
                    var c_date=new Date(today_year,today_month,today_date);
                    var diff = date.getTime() - c_date.getTime();
                    var months = Math.ceil(diff/(1000 * 60 * 60 * 24*30));
                    if(date<c_date){
                        bootbox.alert("<h4>Expiry Notice</h4>\n\<hr/><center>An expired date being updated! </center>" );
                        $("#btn_submit").attr("disabled","disabled");
                    }else if(months<=6){
                       bootbox.alert("<h4>Expiry Notice</h4>\n\<hr/><center>The expiry date updated is within 6 months! </center>" );
                        $("#btn_submit").removeAttr('disabled');
                    }else{
                       $("#btn_submit").removeAttr('disabled');
                    }
	            }
			});
			counter++;

		});
	}
	
	function retrieveFormValues() {
		//This function loops the whole form and saves all the input, select, e.t.c. elements with their corresponding values in a javascript array for processing
		var dump = Array;
		$.each($("input, select, textarea"), function(i, v) {
			var theTag = v.tagName;
			var theElement = $(v);
			var theValue = theElement.val();
			if(theElement.attr('type') == "radio") {
				var text = 'input:radio[name=' + theElement.attr('name') + ']:checked';
				dump[theElement.attr("name")] = $(text).attr("value");
			} else {
				dump[theElement.attr("name")] = theElement.attr("value");
			}
		});
		return dump;
	}
	
	function retrieveFormValues_Array(name) {
		var dump = new Array();
		var counter = 0;
		$.each($("input[name=" + name + "], select[name=" + name + "], select[name=" + name + "]"), function(i, v) {
			var theTag = v.tagName;
			var theElement = $(v);
			var theValue = theElement.val();
			dump[counter] = theElement.attr("value");
			counter++;
		});
		return dump;
	}
	
	    
	
</script>

<div class="main-content">
	
		<div>
			<span id="msg_server"></span>
		</div>
		<div class="full-content" id="stock_div" style="background:#9CF">
                    <form id="stock_form" name="stock_form" method="post" action="<?php echo base_url().'inventory_management/save' ?>" >
			<textarea name="list_drugs_transacted" id="list_drugs_transacted" style="display: none"></textarea>
			<textarea name="sql" id="sql" style="display: none"></textarea>
			
			<div id="sub_title" >
				<a href="<?php  echo base_url().'inventory_management ' ?>">Inventory</a> <i class=" icon-chevron-right"></i>  <?php echo $store ?> 
				<hr size="1">
			</div>
			<div id="transaction_type_details">
				<h3>Transaction details</h3>
				<table>
					<tr><th>Transaction Date</th></tr>
					<tr><td><input type="text" name="transaction_date" id="transaction_date" class="input-large" /></td></tr>
					<tr><th>Transaction Type</th></tr>
					<tr><td><select name="transaction_type" id="select_transtype" class="input-large">
								<option label="" value="0" selected="">-- Select Type --</option>
								<?php
								foreach ($transaction_types as $transaction_type) {
								
									if(@$user_is_facility_administrator==0){//Display adjustments for only facility administrators
										$trans_name=str_replace(" ","",strtolower($transaction_type['Name']));
										$findme="ajustment";
										$pos = stripos($trans_name, $findme);
										//echo '<option label="" value="0" selected="">'.$user_is_facility_administrator.'</option>';
										if($pos===0){
											//continue;
										}	
									}
									//If transaction is a pharmacy transaction,
									if($stock_type==2){
										//Hide issued to when transaction is from pharmacy
										$trans_name=str_replace(" ","",strtolower($transaction_type['Name']));
										$findme="issued";
										$pos = strpos($trans_name, $findme);
										//Check if transaction type is not issued to
										//if($pos===false){
									?>
									<option label="<?php echo $transaction_type['Effect'] ?>"  value="<?php echo $transaction_type['id'] ?>"><?php echo $transaction_type['Name'] ?></option>
									<?php
										//}
									}
									
									
									else{
										////If transaction is a store transaction,hide dispensed to
										$trans_name=str_replace(" ","",strtolower($transaction_type['Name']));
										$findme="dispense";
										$pos = strpos($trans_name, $findme);
										if($pos===false){
										?>
										<option label="<?php echo $transaction_type['Effect'] ?>" value="<?php echo $transaction_type['id'] ?>"><?php echo $transaction_type['Name'] ?></option>
										<?php
										}
									}
								}
								?>
								
							</select>
						</td>
					</tr>
					<tr><th>Ref. /Order No</th></tr>
					<tr><td><input type="text" name="reference_number" id="reference_number" required="" class="input-large" /></td></tr>
					<tr class="t_source"><th>Source</th></tr>
					<tr class="t_source"><td>
						<select name="source" id="select_source" class="input-large">
							<option value="0">--Select Source --</option>
							<?php
							$ccc_stores = $this ->session ->userdata("ccc_store");
							$init = 0;
							if(count($ccc_stores)>0){
								foreach ($ccc_stores as $value) {
									if($value['id']==$stock_type){
										continue;
									}
									if($init==0){
										echo '<optgroup label="Stores" class="stores">';
										$init++;
									}
									echo '<option value='.$value['id'].'>'.$value['Name'].'</option>';	
								}
								echo '</optgroup>';
							}?>
								
							<?php
							//List satelittes or central site depending on facilty type
							$init = 0;
							if(count($get_list)>0){
								foreach ($get_list as $value) {
									if($init==0){
										echo '<optgroup label="'.$list_facility.'" class="'.$list_facility.'">';
										$init++;
									}
									echo '<option value='.$value['id'].'>'.$value['name'].'</option>';	
								}
								echo '</optgroup>';
							}?>
							
							
							<optgroup label="Others" class="others">
							<?php
							foreach ($drug_sources as $drug_source) {
								$source_name = $drug_source['Name'];
								$drug_s=trim(strtolower($source_name));
								$drug_s=str_replace("ccc_store_","",strtolower($drug_s));
								$findme1="store";
								$findme2="main";
								$pos1 = strpos($drug_s, $findme1);
								$pos2 = strpos($drug_s, $findme2);
								$pipeline_name=str_replace(" ","",strtolower($supplier_name));
								$pos3 = strpos($drug_s, $pipeline_name);
								//If stock type is main store, don't display main store as source
								if($stock_type==1 && ($pos1==true || $pos2==true)){
									continue;
								}
								//If transaction type is pharmacy, don't display pipeline
								else if ($stock_type==2 && ($pos3==true || $pos3===0)){
									continue;
								}
								?>
								<option value="<?php echo $drug_source['id'] ?>"><?php echo $drug_s ?></option>
								<?php
							}
							?>
						</select>
						</td>
					</tr>

					<tr class="send_email"><th>Email Address</th></tr>
					<tr class="send_email"><td>
						<input type="email" name="email_address" id="send_email" class="input-large"  value placeholder="youremail@example.com"/></td></tr>
				
					<tr class="t_destination"><th>Destination</th></tr>
					<tr class="t_destination"><td>
							<select name="destination" id="select_destination" class="input-large">
								<option value="0">--Select Destination --</option>
								
								<?php
								$ccc_stores = $this ->session ->userdata("ccc_store");
								$init = 0;
								if(count($ccc_stores)>0){
									foreach ($ccc_stores as $value) {
										if($value['id']==$stock_type){
											continue;
										}
										if($init==0){
											echo '<optgroup label="Stores" class="stores">';
											$init++;
										}
										echo '<option value='.$value['id'].'>'.$value['Name'].'</option>';	
									}
									echo '</optgroup>';
								}?>
								<?php
								//List satelittes or central site depending on facilty type
								$init = 0;
								if(count($get_list)>0){
									foreach ($get_list as $value) {
										if($init==0){
											echo '<optgroup label="'.$list_facility.'" class="'.$list_facility.'">';
											$init++;
										}
										echo '<option value='.$value['id'].'>'.$value['name'].'</option>';	
									}
									echo '</optgroup>';
								}?>
								<optgroup label="Others" class="others">
									
								<?php
								
								foreach ($drug_destinations as $drug_destination) {
									$drug_d=trim(strtolower($drug_destination['Name']));
									$drug_d=str_replace("ccc_store_","",strtolower($drug_d));
									$findme1="main pharmacy";
									$pos1 = stripos($drug_d, $findme1);
									//Not picking outpatient pharmacy if stock type is pharmacy
									if($stock_type==2 && $pos1===10){
										continue;
									}
									//Outpatient pharmacy
									else if($pos1===0){
									?>
									<option value="<?php echo $drug_destination['id'] ?>"><?php echo $drug_d ?></option>
									<?php
									}
									else{
									?>
									<option value="<?php echo $drug_destination['id'] ?>"><?php echo $drug_d ?></option>
									<?php	
									}
								?>
								
								<?php
								}
								?>
								</optgroup>
							</select>
						</td>
					</tr>
					
					<!-- Select from orders dispacthed -->
					<tr class="t_picking_list"><th>Select Order </th></tr>
					<input type="hidden" id="count_dispatched_drugs" />
					<tr class="t_picking_list">
						<td>
						<select id="picking_list_name" name="picking_list_name" class="input-large" >
							<option value="0">-- Select One --</option>
							<?php
							foreach($picking_lists as $picking_list){
							?>
							<option value="<?php echo $picking_list['id'] ?>" ><?php echo "Order no: ".$picking_list['id']."(".date('M-Y',strtotime($picking_list['Period_Begin'])).")"; ?></option>
							<?php
							}
							?>
						</select>
						</td>
					</tr>
					
				</table>
			</div>
			<div id="drug_details">
				<h3>Drug details</h3>
				<table border="0" class="table table-bordered" id="drugs_table">
					<thead>
						<tr>
							<th style="width:18%;">Drug</th>
							<th style="width:7%;">Unit</th>
							<th>Pack Size</th>
							<th style="width:18%;">Batch No.</th>
							<th style="width:16%;">Expiry&nbsp;Date</th>
							<th>Packs</th>
							<th style="width:12%;">Qty</th>
							<th style="width:12%;">Available Qty</th>
							<th>Pack Cost</th>
							<th>Total</th>
							<th>Comment</th>
							<th style="width:28%">Action</th>
						</tr>
					</thead>
					<tbody >
						<tr drug_row="1">
							<td>
								<input type="hidden" name ="cdrr_id" class="cdrr_id" value="">
							<select id="select_drug" name="drug" class="drug"  style="width:100%;">
								<option value="0"> -- Select Commodity --</option>
							</select></td>
							<td>
							<input type="text" id="unit" name="unit" class="unit small_text input-small" readonly="" style="width:100%;" />
							</td>
							<td>
							<input type="text"  id="pack_size" name="pack_size" class="pack_size small_text input-small" readonly="" />
							</td>
							<td><select name="batchselect" class="batchselect" id="batchselect_1" style="display:none;width:100%;"></select>
							<input type="text" name="batch" class="batch  validate[required] input-small"   id="batch_1" style="width:100%;"/>
							<input  name="dose[]" list="batch_2" id="batchlist"  style="display:none;width:100%;" class="input-small b_list icondose">
                               <datalist id="batch_2" class="dose b_list"></datalist>
							</td>
							<td>
							<input type="text" name="expiry" class="expiry medium_text" id="expiry_date" style="width:100%;" />
							</td>
							<td>
							<input type="text" name="pack" class="pack small_text validate[required] input-small" id="packs_1"  />
							</td>
							<td>
							<input type="text" name="quantity" id="quantity_1" class="quantity small_text input-small"  style="width:100%;" />
							</td>
							<td>
							<input type="text" id="available_quantity" name="available_quantity" class="quantity_available medium_text input-small" readonly="" style="width:100%;" />
							</td>
							<td>
							<input type="text" name="unit_cost" id="unit_cost" class="unit_cost small_text input-small" />
							</td>
							<td>
							<input type="text" name="amount" id="total_amount" class="amount input-small input-small" readonly="" />
							</td>
							<td >
							<input type="text" name="comment" class="comment " style="width:150px"/>
							</td>
							<td style="text-align: center;font-size: 10px" >
								<a href="#" class="add" >Add</a>
								<span class="remove"> | <a href="#" >Remove</a></span>
							</td>
							
						</tr>
					</tbody>
					
					
				</table>
			</div>
		
		
	
		<div id="submit_section">
			<input type="button" class="btn btn-size" id="btn_print" value="Generate PDF" />
			<input type="reset" class="btn btn-danger btn-size" id="reset" value="Reset Fields" />
			<input type="button" class="btn" id="btn_submit" value="Submit" />
		</div>
	</form>
	</div>
</div>