<div class="row-fluid">
  <div class="span12">
    <div class="table-responsive">
		<table class="table table-bordered table-hover table-condensed" id="merge_listing" >
			<thead>
				<tr>
					<th>CCC No</th>
					<th>Patient Name</th>
					<th>Options</th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
    </div>
  </div>
</div>

<script type="text/javascript">	
	$(document).ready(function(){
		var base_url='<?php echo base_url();?>';
		var oTable =$('#merge_listing').dataTable({
											"bJQueryUI" : true,
											"sPaginationType" : "full_numbers",
											"bStateSave" : true,
											"sDom" : '<"H"T<"clear">lfr>t<"F"ip>',
											"bProcessing" : true,
											"bServerSide" : true,
											"bAutoWidth" : false,
											"bDeferRender" : true,
											"bInfo" : true,
											"sAjaxSource": base_url+"patient_management/getPatientMergeList",
            });
        //merging patient
		$(".merge_patient").live('click',function(){
			var target_patient_id = $(this).attr("id");
            bootbox.confirm("<h4>Save</h4>\n\<hr/><center>Are you sure?</center>",
                function(res){
                    if(res===true){
                    var counter=0;
					var patients=new Array();
					
					$("input:checkbox[name='patients']:checked").each(function(){
					    patients.push($(this).val());
					    counter++;
		            });
		            if(counter>0){
                        //ajax call to patient merge function
                        $.ajax({
			                url: base_url+'patient_management/merge',
			                type: 'POST', 
			                data: { 
			                	'patients': patients ,
			                	'target_ccc':target_patient_id
			                },      
			                success: function(data) {
			                     //Refresh Page
			                     location.reload(); 
			                },
			                error: function(){
			                	bootbox.alert('<h4>Merge Error!</h4>\n\<hr/><center>failed merged!</center>');
			                }
			           });
		            }else{
		               bootbox.alert('<h4>Selection Alert</h4>\n\<hr/><center>no patient selected!</center>');
		            }
                    return true;
                    }else{
                     	return false;
                    }
                });
		});
		//unmerging patient
		$(".unmerge_patient").live('click',function(){
			var target_patient_id = $(this).attr("id");
			bootbox.confirm("<h4>Save</h4>\n\<hr/><center>Are you sure?</center>",
                function(res){
                    if(res===true){
                        //ajax call to patient unmerge function
                        $.ajax({
			                url: base_url+'patient_management/unmerge',
			                type: 'POST', 
			                data: { 
			                	'target_ccc':target_patient_id
			                },      
			                success: function(data) {
			                     //Refresh Page
			                     location.reload(); 
			                },
			                error: function(){
			                	bootbox.alert('<h4>Merge Error!</h4>\n\<hr/><center>failed unmerged!</center>');
			                }
			            });
                    	return true;
                    }else{
                     	return false;
                    }
                });
		});
	});

</script>
