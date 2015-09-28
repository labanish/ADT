<div class="center-content">
	<?php
if($login_type==1){
	?>
	<div class="row-fluid">
		<div class="span6">
			<?php echo $this -> session -> flashdata('login_message');?>
			<?php echo form_open('order/authenticate_user/1');?>
			<?php echo form_fieldset('', array('id' => 'login_legend'));?>
			<legend id="login_legend">
				<i class="fa fa-info-circle" style="padding-right:5px"></i>eSCM Log In
			</legend>
			
			<?php echo $this -> session -> flashdata('error_message');?>
			<div class="item">
				<?php echo form_error('username', '<div class="error_message">', '</div>');?>
				<?php echo form_label('Username:', 'username');?>
				<div class="input-group">
					<span class="input-group-addon"><i class="fa fa-user"></i></span>
					<?php echo form_input(array('name' => 'username', 'required' => 'required', 'id' => 'username', 'size' => '24', 'class' => 'textfield form-control', 'placeholder' => 'username'));?>
				</div>
			</div>
			<div class="item">
				<?php echo form_error('password', '<div class="error_message">', '</div>');?>
				<?php echo form_label('Password:', 'password');?>
				<div class="input-group">
					<span class="input-group-addon"><i class="fa fa-key"></i></span>
					<?php echo form_password(array('name' => 'password', 'required' => 'required', 'id' => 'password', 'size' => '24', 'class' => 'textfield form-control', 'placeholder' => '********'));?>
				</div>
			</div>
			<div style="margin-top:1em;">
				<?php echo form_fieldset_close();?>
				<?php echo form_fieldset('', array('class' => 'tblFooters'));?>
				<?php echo form_submit('input_go', 'Go');?> <?php echo form_fieldset_close();?>
				<?php echo form_close();?>
			</div>
			<div class="span4" style="margin-top:1em;">
				<h3>CDRR Templates <i><img class="img-rounded" style="height:30px;" src="<?php echo base_url() . 'assets/images/excel.gif';?>"/> </i></h3>
				<div class="accordion-inner">
					<a href="<?php echo base_url() . 'downloads/F-CDRR for Satellite Sites.xls';?>"> <i class="icon-download-alt"></i> F-CDRR for Satellite Sites.xls</a>
				</div>
				<h3>MAPS Templates <i><img class="img-rounded" style="height:30px;" src="<?php echo base_url() . 'assets/images/excel.gif';?>"/> </i></h3>
				<div class="accordion-inner">
					<a href="<?php echo base_url() . 'downloads/F-MAPS for Satellite Sites.xls';?>"><i class="icon-download-alt"></i> F-MAPS for Satellite Sites.xls</a>
				</div>
			</div>
		</div>
	</div>
	<?php
	}else{
	?>
	<div class="row-fluid">
		<div class="span6">
			<?php echo $this -> session -> flashdata('login_message');?>
			<?php echo form_open('order/authenticate_user');?>
			<?php echo form_fieldset('', array('id' => 'login_legend'));?>
			<legend id="login_legend">
				<i class="fa fa-info-circle" style="padding-right:5px"></i>NASCOP Log In
			</legend>
			<?php echo $this -> session -> flashdata('error_message');?>
			<div class="item">
				<?php echo form_error('email', '<div class="error_message">', '</div>');?>
				<?php echo form_label('Email Address:', 'username');?>
				<div class="input-group">
					<span class="input-group-addon"><i class="fa fa-user"></i></span>
					<?php echo form_input(array('type' => 'email', 'name' => 'email', 'required' => 'required', 'id' => 'email', 'size' => '24', 'class' => 'textfield form-control', 'placeholder' => 'mail@yourmail.com', 'value' => $this -> session -> userdata("Email_Address")));?>
				</div>
			</div>
			<div class="item">
				<?php echo form_error('password', '<div class="error_message">', '</div>');?>
				<?php echo form_label('Password:', 'password');?>
				<div class="input-group">
					<span class="input-group-addon"><i class="fa fa-key"></i></span>
					<?php echo form_password(array('name' => 'password', 'required' => 'required', 'id' => 'password', 'size' => '24', 'class' => 'textfield form-control', 'placeholder' => '********'));?>
				</div>
			</div>
			<div style="margin-top:1em;">
				<?php echo form_fieldset_close();?>
				<?php echo form_fieldset('', array('class' => 'tblFooters'));?>
				<?php echo form_submit('input_go', 'Go');?> <?php echo form_fieldset_close();?>
				<?php echo form_close();?>
			</div>
			<div class="span4" style="margin-top:1em;">
				<h3>CDRR Templates <i><img class="img-rounded" style="height:30px;" src="<?php echo base_url() . 'assets/images/excel.gif';?>"/> </i></h3>
				<div class="accordion-inner">
				<!-- addition of the most recent downloads from MOH (templates) -->
					<a href="<?php echo base_url().'downloads/modern-templates/F-CDRR for Satellite Sites.xls';?>"> <i class="icon-download-alt"></i> F-CDRR for Satellite Sites.xls</a>
				</div>
				<h3>MAPS Templates <i><img class="img-rounded" style="height:30px;" src="<?php echo base_url() . 'assets/images/excel.gif';?>"/> </i></h3>
				<div class="accordion-inner">
					<a href="<?php echo base_url().'downloads/F-MAPS for Satellite Sites.xls';?>"><i class="icon-download-alt"></i> F-MAPS for Satellite Sites.xls</a>
				</div>
			</div>
		</div>
	</div>
	<?php
	}
	?>
</div>