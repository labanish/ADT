<style>
	#fmChangePassword .short {
		color: #FF0000;
	}

	#fmChangePassword .weak {
		color: #E66C2C;
	}

	#fmChangePassword .good {
		color: #2D98F3;
	}
 
	legend {
		font-size: 22px;
	}
	table tr {
		line-height: 40px;
	}
	label {
		margin-right: 20px;
	}

	#main_wrapper {
		height: auto;
	}
</style>

<div class="full-content">
	<form id="fmChangePassword" action="<?php echo base_url().'user_management/save_new_password/1'?>" method="post" class="well">
	<legend>Change Password</legend>
	<span class="message error" id="m_error_msg_change_pass"></span>
	<div id="loadingDiv" style="display: none"><img style="width: 30px" src="<?php echo asset_url().'images/loading_spin.gif' ?>"></div>
	<br>
	<br>
	<table cellpadding="5">
	<tr>
	<td><label >Old Password</label></td><td><input type="password" name="old_password" id="m_old_password" required=""></td>
	</tr>
	<tr>
	<td><label >New Password</label></td><td><input type="password" name="new_password" id="m_new_password" required=""><span id="m_result"></span></td>
	</tr>
	<tr>
	<td><label >Confirm New Password</label></td><td>
	<input type="password" name="new_password_confirm" id="m_new_password_confirm" required="">
	<span id="m_result_confirm"></span></td>
	</tr>
	<tr>
		<td colspan="2">
		<input type="submit" class="btn btn_submit_pass" name="register" id="m_btn_submit_change_pass" value=" Submit ">
		</td>
	</tr>
	</table>

	</form>

</div>