<!DOCTYPE html >
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" manifest="/ADT/offline.appcache">
<head>
	<?php 
        $this -> load -> view('sections/head');
	?>
</head>
<body>
    <div class="container-fluid">
        <div class="row-fluid">
            <div class="span8 offset2">
                <div class="row-fluid">
                	<div class="span12">
	                	<ul class="breadcrumb">
						  <li><a href="<?php echo base_url().'user_management/login'; ?>">Login</a> <span class="divider">/</span></li>
						  <li class="active">Forgot Password</li>
						</ul>
				    </div>
                </div>
	            <!--Forgot Password Form-->
                <form class="form" action="<?php echo base_url().'user_management/resend_password'?>" method="POST">
				    <fieldset>
					    <legend>
						    <?php
	                           echo $this->session->flashdata("notification");
						    ?>
					        Find your ADT account Enter your email.
                        </legend>
					    <div class="control-group">
						    <div class="controls input-prepend">
							  <span class="add-on">@</span>
							  <input class="span12" id="prependedInput" type="email" name="email_address" placeholder="username@email.com" required>
							</div>
	                    </div>
						<div class="control-group">
							 <div class="controls">
							    <button type="submit" class="btn"><i class="icon-ok-circle"></i> Send</button>
							</div>
						</div>
					</fieldset>
				</form>
            </div>
        </div>
        <div class="row-fluid">
			<footer>
				<div class="span12" style="text-align:center">
					<hr size="2" />
					Government of Kenya &copy; <?php echo date('Y');?>. All Rights Reserved
				</div>
			</footer>
		</div>
    </div>
</body>
</html>
