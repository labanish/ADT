<style type="text/css">
	.progress{
		margin-top:0;
		width:100%;
	}
	.label{
		font-size: 1em !important;
	}
	#table_progress{
		height:10em;
		overflow-y:scroll;
		padding:0.4em;
	}
	.select2-drop-active{
		zoom:0.8;
	}
</style>

<div class="full-content">
   <div class="row-fluid">
       <div class="span8 offset2" id="migration_complete_msg"></div>
   </div>
   <form id="fmMigration" action="migration_management/migrate" method="post">
   <div class="row-fluid">
       <div class="span8 offset2">
       	<h3>Migration Settings</h3>
       </div>
   </div>
   <div class="row-fluid">
       <div class="span8 offset2">
       	 <div class="span5">
					<div class="form-group">
				      <label for="facility_code">Facility</label>
				      <input type="text" id="facility_code" name="facility_code" class="form-control span6 validate" style="width:90%" />
				    </div>
				    <div class="form-group" id="fg_ccc_pharmacy">
				      <label for="ccc_pharmacy">Pharmacy</label>
					      <select id="ccc_pharmacy" name="ccc_pharmacy" class="form-control span10 validate">
					        <option value=""> Select Pharmacy </option>
					        <?php 
					          foreach($stores as $store){
					        ?>
	                           <option value="<?php echo $store['ccc_id'];?>"><?php echo $store['ccc_name']; ?></option>
	                        <?php
					          }
					        ?>
					      </select>
				    </div>
		  </div>
		  <div class="span5">
					<div class="form-group">
				      <label for="source_database">Database</label>
				      <select id="source_database" name="source_database" class="form-control validate" style="width:90%">
				        <option value=""> Select Database </option>
				        <?php 
				          foreach($databases as $database){
				        ?>
                           <option value="<?php echo $database['Database'];?>"><?php echo $database['Database']; ?></option>
                        <?php
				          }
				        ?>
				      </select>
				    </div>
				    <div class="form-group">
	                    <label for="table" id="lbltable" name="lbltable">Tables</label> 
						<select class=" form-control multiselect span10 validate" id="table" name="table" multiple="multiple" required="required">
				        <?php 
				          foreach($tables as $table=>$table_config){
				        ?>
                           <option value="<?php echo $table;?>"><?php echo $table; ?></option>
                        <?php
				          }
				        ?>
						</select>
	                </div>
			</div>
       </div>
   </div>
   <!--start button-->
   <div class="row-fluid">
     <div class="span8 offset2">
		<div class="form-group">
			<button type="submit" id="migrate_btn" class="btn btn-primary">Start Migration</button>
		</div>
	 </div>	
   </div>
   <!--bottom-->
   <div class="row-fluid">
	   <div class="span8 offset2">
	   	<h3>Migration Progress</h3>
	   </div>
   </div>
   <!--overall progress-->
   <div class="row-fluid">
        <div class="span8 offset2">
			<label>Overall Progress</label>
			<div id="overall_progress_bar" class="progress active">
				<div id="migration_overall_progress" class="bar" style="width:0%;"></div>
			</div>
		</div>
	</div>
	<!--line separator-->
	<div class="row-fluid">
	  <div class="span8 offset2">
	    <p>
	     <hr size="2">
	     </p>
	  </div>
	</div>
	<!--table progress-->
	<div class="row-fluid">
		<div class="span8 offset2" id="table_progress">
		 <div id="migrate_table_result_holder">
		 </div>
		</div>
    </div>
    </form>
</div> <!--end full-content-->


<!--scripts-->
<script src="<?php echo base_url();?>assets/scripts/bootstrap/bootstrap-multiselect.js"></script>
<script src="<?php echo base_url();?>assets/scripts/plugin/jquery-validate/jquery.validate.min.js"></script>
<script src="<?php echo base_url();?>assets/scripts/select2-3.4.8/select2.js"></script>
<script src="<?php echo base_url();?>assets/scripts/migration.js"></script>