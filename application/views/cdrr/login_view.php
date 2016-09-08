<!--Page Container-->
<div class="container-fluid center-content">
    <div class="row-fluid">
        <!--Login Section-->
        <div class="span6">
            <!--Login Header-->
	        <div class="page-header">
			    <h3>CDRR <small>Login</small></h3>
			</div>
			<!--Login form-->
            <form class="form-horizontal" action="../cdrr_core/login" method="post">
				<div class="control-group">
				    <label class="control-label" for="inputEmail">Email/Username</label>
				    <div class="controls">
				        <input type="text" id="inputEmail" name="username"  required>
				    </div>
				</div>
			    <div class="control-group">
				    <label class="control-label" for="inputPassword">Password</label>
				    <div class="controls">
				        <input type="password" id="inputPassword" name="password" required>
				    </div>
			    </div>
			    <div class="control-group">
				    <div class="controls">
				        <button type="submit" class="btn btn-inverse">Sign in</button>
				    </div>
			    </div>
			</form>
        </div>
        <!--Template Section-->
        <div class="span5 offset-1">
            <!--Template Header-->
	        <div class="page-header">
			    <h3>CDRR <small>Templates</small></h3>
			</div>
			<div class="row-fluid">
			    <div class="span12">
					<!--Template Link-->
					<a href="../downloads/new_templates/F-CDRR for Satellite Sites.xls"> 
					    <i class="icon-download-alt"></i> F-CDRR for Satellite Sites.xls
					</a>
				</div>
			</div>
        </div>
    </div>
</div>
