<style>
.dataTables td, .dataTables tr, .dataTables th {
	  white-space: nowrap;
	  overflow: hidden;        
	  text-overflow: ellipsis;
          width:auto;
	}


.dataTables_scroll
{
    overflow:auto;
}
</style>

<div id="wrapperd">
    <div class="full-content">
		<h4 style="text-align: center" id='report_title'>Patient Master List</h4>
		<div id="patient_list">
		<?php echo $dyn_table; ?>
		</div>
    </div>
</div>
<script type="text/javascript">
	$(document).ready(function(){
       
		$(".dataTables").wrap('<div class="dataTables_scroll" />');
                
                
	});
</script>
