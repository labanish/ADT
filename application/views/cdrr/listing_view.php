<!--custom css-->
<link href="../assets/modules/cdrr/listing.css" type="text/css" rel="stylesheet"/>

<!--Listing Container-->
<div class="container-fluid">
    <!--message row-->
    <div class="row-fluid">
        <div class="span11">
            <div class="alert alert-block alert-success">
			    <button type="button" class="close" data-dismiss="alert">&times;</button>
			    <strong>Welcome! </strong><?=$this->session->userdata("api_name");?> to the cdrr module.
			</div>
        </div>
    </div>
    <!--buttons row-->
    <div class="row-fluid">
        <div class="span11">
            <div class="btn-group">
                <input type="hidden" data-baseurl="<?=base_url();?>" id="sources">
			    <a class="btn btn-default" href="../cdrr/facility">
                    <i class="icon-plus"></i>
                    new cdrr
                </a>
			</div>
        </div>
    </div>
    <!--table row-->
    <div class="row-fluid">
        <div class="span11">
        	<div class="table-responsive">
        	    <table class="table table-bordered table-condensed table-hover" id="cdrr_listing">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Period Beginning</th>
                            <th>Status</th>
                            <th>Facility Name</th>
                            <!--<th>Action</th>-->
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>#ID</th>
                            <th>Period Beginning</th>
                            <th>Status</th>
                            <th>Facility Name</th>
                            <!--<th>Action</th>-->
                        </tr>
                    </tfoot>
        	    </table>
        	</div>
        </div>
    </div>
</div>

<!--form js-->
<script src="../assets/modules/forms/forms.js"></script>
<!--custom js-->
<script src="../assets/modules/cdrr/listing.js"></script>