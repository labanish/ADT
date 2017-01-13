<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class User_Management extends MY_Controller {
	function __construct() {
		parent::__construct();
                
		$this -> session -> set_userdata("link_id", "index");
		$this -> session -> set_userdata("linkSub", "user_management");
		$this -> session -> set_userdata("linkTitle", "Users Management");
		$this -> load -> library('encrypt');
		//$this -> load -> helper('geoiploc');
		ini_set("SMTP", 'ssl://smtp.googlemail.com');
		ini_set("smtp_port", '465');
		ini_set("sendmail_from", 'webadt.chai@gmail.com');
		date_default_timezone_set('Africa/Nairobi');
	}

	public function index() {
		$this -> listing();

	}

	public function login() {
		//if seesion variable user_id is not present
		if (!$this -> session -> userdata("user_id")) {
			$this -> session -> set_flashdata('message', 0);
			$data = array();
			$data['title'] = "webADT | System Login";
			$this->load->view("login_v", $data);
		} else {
			/*
			 * if user_id is present
			 * check actual page cookie
			 * redirect to actual page which is the last page accessed
			 */
		    $actual_page = $this -> input -> cookie("actual_page");
			redirect($actual_page);
		}

	}

	public function listing() {
		$access_level = $this -> session -> userdata('user_indicator');
		$user_type = "1";
		$facilities = "";
		//If user is a super admin, allow him to add only facilty admin and nascop pharmacist
		if ($access_level == "system_administrator") {
			$user_type = "indicator='nascop_pharmacist' or indicator='facility_administrator'";
			$facilities = Facilities::getAll();
			$users = Users::getAll();

		}
		//If user is a facility admin, allow him to add only facilty users
		else if ($access_level == "facility_administrator") {
			$facility_code = $this -> session -> userdata('facility');
			$user_type = "indicator='pharmacist'";
			$facilities = Facilities::getCurrentFacility($facility_code);
			$q = "u.Facility_Code='" . $facility_code . "' and Access_Level !='1' and Access_Level !='4'";
			$users = Users::getUsersFacility($q);

		}
		$user_types = Access_Level::getAll($user_type);

		$tmpl = array('table_open' => '<table class=" table table-bordered table-striped setting_table ">');
		$this -> table -> set_template($tmpl);
		$this -> table -> set_heading('id', 'Name', 'Email Address', 'Phone Number', 'Access Level', 'Registered By', 'Options');

		foreach ($users as $user) {
			$links = "";
			$array_param = array('id' => $user['id'], 'role' => 'button', 'class' => 'edit_user', 'data-toggle' => 'modal');
			//Is user is a system admin, allow him to edit only system  admin and nascop users
			if ($access_level == "system_administrator") {
				if ($user['Access'] == "System Administrator" or $user['Access'] == "NASCOP Pharmacist" or $user['Access'] == "Facility Administrator") {
					//$links = anchor('user_management/edit/' . $user['id'], 'Edit', array('class' => 'edit_user', 'id' => $user['id']));
					//$links = anchor('#edit_user', 'Edit', $array_param);
					//$links .= " | ";
				} else {
					$links = "";
				}
			} else {
				//$links = anchor('user_management/edit/' . $user['id'], 'Edit', array('class' => 'edit_user', 'id' => $user['id']));
				//Only show edit link for pharmacists
				if ($user['Access'] == "Pharmacist" || $user['Access'] == "User") {
					//$links = anchor('#edit_user', 'Edit', $array_param);
				}

			}

			if ($user['Active'] == 1) {
				if ($access_level == "system_administrator") {
					//$links .= " | ";
					$links .= anchor('user_management/disable/' . $user['id'], 'Disable', array('class' => 'disable_user'));
				} else if ($access_level == "facility_administrator" and $user['Access'] == "Pharmacist") {
					//$links .= " | ";
					$links .= anchor('user_management/disable/' . $user['id'], 'Disable', array('class' => 'disable_user'));
				}

			} else {
				//$links .= " | ";
				$links .= anchor('user_management/enable/' . $user['id'], 'Enable', array('class' => 'enable_user'));
			}
			if ($user['Access'] == "Pharmacist") {
				$level_access = "User";
			} else {
				$level_access = $user['Access'];
			}
			$this -> table -> add_row($user['id'], $user['Name'], $user['Email_Address'], $user['Phone_Number'], $level_access, $user['Creator'], $links);
		}

		$data['users'] = $this -> table -> generate();
		;
		$data['user_types'] = $user_types;
		$data['facilities'] = $facilities;
		$data['order_sites'] = Sync_Facility::get_active();
		$data['title'] = "System Users";
		//$data['content_view'] = "users_v";
		$data['banner_text'] = "System Users";
		$data['link'] = "users";
		$actions = array(0 => array('Edit', 'edit'), 1 => array('Disable', 'disable'));
		$data['actions'] = $actions;
		$this -> load -> view("users_v", $data);
	}

	public function change_password() {
		$data = array();
		$data['title'] = "Change User Password";
		$data['content_view'] = "change_password_v";
		$data['link'] = "settings_management";
		$data['banner_text'] = "Change Password";
		$data['hide_side_menu'] = 1;
		$this -> load -> view('template', $data);
	}

	public function activation() {
            $activation_code = $_POST['activation_code'];
		$user_id = $this -> session -> userdata('user_id');
		$this -> load -> database();
		$query = $this -> db -> query("select * from users where Signature='$activation_code' and Active='1'");
		$results = $query -> result_array();
		$data['title'] = "Account Activation";
		if ($results) {
			$query = $this -> db -> query("update users set Signature='1' where Signature='$activation_code' and Active='1'");
			$this -> session -> set_userdata("changed_password", "Your Account Has Been Activated");
			$this -> load -> view("login_v",$data);
			//redirect("user_management/login");
		} else {
			$this -> session -> set_userdata("changed_password", "Your Actvation code was incorrect");
			$this -> load -> view("login_v",$data);
			//redirect("user_management/login");
		}
        
        }

	public function save_new_password($type=2) {
		$old_password = $this -> input -> post("old_password");
		$new_password = $this -> input -> post("new_password");
		$valid_old_password = $this -> correct_current_password($old_password);

		$key = $this -> encrypt -> get_key();
		$encrypted_password = md5($key . $new_password);
		$user_id = $this -> session -> userdata('user_id');
		$timestamp = date("Y-m-d");

		//check if password matches last three passwords for this user
		$sql="SELECT * 
			  FROM (SELECT password 
				    FROM password_log 
				    WHERE user_id='$user_id' 
				    ORDER BY id DESC 
				    LIMIT 3) as pl 
			  WHERE pl.password='$encrypted_password'";
		$checkpassword_query = $this -> db -> query($sql);
		$check_results = $checkpassword_query -> result_array();

		//Check if old password is correct
		if ($valid_old_password == FALSE) {
			if ($type == 2) {
				$response = array('msg_password_change' => 'password_no_exist');
			} else {
				$this -> session -> set_userdata("matching_password", "This is not your current password");
			}
		} else if ($check_results) {
			if ($type == 2) {
				$response = array('msg_password_change' => 'password_exist');
			} else {
				$this -> session -> set_userdata("matching_password", "The current password Matches a Previous Password");
			}
		} else {	
			//update new password
			$sql="UPDATE users 
			      SET Password='$encrypted_password',Time_Created='$timestamp' 
			      WHERE id='$user_id'";
			$query = $this -> db -> query($sql);

			//add new password in log
			$new_password_log = new Password_Log();
			$new_password_log -> user_id = $user_id;
			$new_password_log -> password = $encrypted_password;
			$new_password_log -> save();
	
			if ($type == 2) {
				$response = array('msg_password_change' => 'password_changed');
			} else {
				$this -> session -> set_userdata("changed_password", "Your Password Has Been Changed");
			}
		}

        delete_cookie("actual_page");
		if ($type == 2) {
			echo json_encode($response);
		}else{
			$this->session->unset_userdata("user_id");
			redirect("user_management/login");
		}

	}

	private function _submit_validate_password() {
		// validation rules
		$this -> form_validation -> set_rules('old_password', 'Current Password', 'trim|required|min_length[2]|max_length[30]');
		$this -> form_validation -> set_rules('new_password', 'New Password', 'trim|required|min_length[2]|max_length[30]|matches[new_password_confirm]');
		$this -> form_validation -> set_rules('new_password_confirm', 'New Password Confirmation', 'trim|required|min_length[2]|max_length[30]');
		$temp_validation = $this -> form_validation -> run();
		if ($temp_validation) {
			$this -> form_validation -> set_rules('old_password', 'Current Password', 'trim|required|callback_correct_current_password');
			return $this -> form_validation -> run();
		} else {
			return $temp_validation;
		}

	}

	public function correct_current_password($pass) {
		$key = $this -> encrypt -> get_key();
		$pass = $key . $pass;
		$user = Users::getUserDetail($this -> session -> userdata('user_id'));
		$current_password=md5($pass);

		if ($user[0]['Password'] != $current_password) {
			$this -> form_validation -> set_message('correct_current_password', 'The current password you provided is not correct.');
			return FALSE;
		} else {
			return TRUE;
		}

	}

	public function authenticate() {
		$data = array();
		$validated = $this -> _submit_validate();
		if ($validated) {
			$username = $this -> input -> post("username");
			$password = $this -> input -> post("password");
			$remember = $this -> input -> post("remember");
			$key = $this -> encrypt -> get_key();
			$encrypted_password = $key . $password;
			$logged_in = Users::login($username, $encrypted_password);

			//This code checks if the credentials are valid
			if ($logged_in == false) {
				$data['invalid'] = true;
				$data['title'] = "System Login";
				$this -> load -> view("login_v", $data);
			}

			//Check if credentials are valid for username not password
			else if (isset($logged_in["attempt"]) && $logged_in["attempt"] == "attempt" && $logged_in["user"] -> Access -> Indicator != "system_administrator") {

				//check to see whether the user is active
				if ($logged_in["user"] -> Active == 0) {
					$data['inactive'] = true;
					$data['title'] = "System Login";
					$data['login_attempt'] = "<p class='error'>The Account has been deactivated. Seek help from the Facility Administrator</p>";
					$this -> load -> view("login_v", $data);
				} else {
					$data['invalid'] = false;
					$data['title'] = "System Login";
					$data['login_attempt'] = "enter the correct password!</p>";
					$this -> load -> view("login_v", $data);
					/*
					 *
					//Check if there is a login attempt
					if (!$this -> session -> userdata($username . '_login_attempt')) {

						$login_attempt = 1;
						$this -> session -> set_userdata($username . '_login_attempt', $login_attempt);
						$fail = $this -> session -> userdata($username . '_login_attempt');
						$data['login_attempt'] = "(Attempt: " . $fail . " )";
					} else {

						//Check if login Attempt is below 4
						if ($this -> session -> userdata($username . '_login_attempt') && $this -> session -> userdata($username . '_login_attempt') <= 4) {
							$login_attempt = $this -> session -> userdata($username . '_login_attempt');
							$login_attempt++;
							$this -> session -> set_userdata($username . '_login_attempt', $login_attempt);
							$fail = $this -> session -> userdata($username . '_login_attempt');
							$data['login_attempt'] = "(Attempt: " . $fail . " )";
						}

						if ($this -> session -> userdata($username . '_login_attempt') > 4) {
							$fail = $this -> session -> userdata($username . '_login_attempt');
							$data['login_attempt'] = "<p class='error'>The Account has been deactivated. Seek help from the Facility Administrator</p>";
							$this -> session -> set_userdata($username . '_login_attempt', 0);
							$this -> load -> database();
							$query = $this -> db -> query("UPDATE users SET Active='0' WHERE(username='$username' or email_address='$username' or phone_number='$username')");
							//Log Denied User in denied_log
							$new_denied_log = new Denied_Log();
							$new_denied_log -> ip_address = $_SERVER['REMOTE_ADDR'];
							$new_denied_log -> location = $this -> getIPLocation();
							$new_denied_log -> user_id = Users::getUserID($username);
							$new_denied_log -> save();

						}
					}
					*
					*/
					
				}
			} else if (isset($logged_in["attempt"]) && $logged_in["attempt"] == "attempt" && $logged_in["user"] -> Access -> Indicator == "system_administrator") {
				$data['title'] = "System Login";
				$data['invalid'] = true;
				$this -> load -> view("login_v", $data);
			} else {
				//If the credentials are valid, continue
				$today_time = strtotime(date("Y-m-d"));
				$create_time = strtotime($logged_in -> Time_Created);
				//check to see whether the user is active
				if ($logged_in -> Active == "0" && $logged_in -> Access -> Indicator != "system_administrator") {
					$data['inactive'] = true;
					$data['title'] = "System Login";
					$this -> load -> view("login_v", $data);

				} 
				/*
				else if (($today_time - $create_time) > (90 * 24 * 3600) && $logged_in -> Access -> Indicator != "system_administrator") {
					$user_id = Users::getUserID($username);
					$this -> session -> set_userdata('user_id', $user_id);
					$data['title'] = "System Login";
					$data['expired'] = true;
					$data['login_attempt'] = "Your Password Has Expired.<br/>Please Click <a href='change_password'>Here</a> to Change your Current Password";
					$this -> load -> view("login_v", $data); 
				}
				*/ 
				else if ($logged_in -> Active == "1" && $logged_in -> Signature != 1 && $logged_in -> Access -> Indicator != "system_administrator") {

					$user_id = Users::getUserID($username);
					$this -> session -> set_userdata('user_id', $user_id);
					$facility_details = Facilities::getCurrentFacility($logged_in -> Facility_Code);
					$data['unactivated'] = true;
					$data['title'] = "System Login";
					$this -> load -> view("login_v", $data);
				}
				//looks good. Continue!
				else {
					$facility_details = Facilities::getCurrentFacility($logged_in -> Facility_Code);
					$phone = $logged_in -> Phone_Number;
					$check = substr($phone, 0);
					$phone = str_replace('+254', '', $phone);

					$session_data = array(
						             'user_id' => $logged_in -> id, 
						             'user_indicator' => $logged_in -> Access -> Indicator, 
						             'facility_name' => $logged_in -> Facility -> name, 
						             'adult_age' => $logged_in -> Facility -> adult_age, 
						             'access_level' => $logged_in -> Access_Level, 
						             'username' => $logged_in -> Username, 
						             'full_name' => $logged_in -> Name, 
						             'Email_Address' => $logged_in -> Email_Address, 
						             'Phone_Number' => $phone, 
						             'facility' => $logged_in -> Facility_Code, 
						             'facility_id' => $facility_details[0]['id'],
						             'county' => $facility_details[0]['county'],
						             'facility_phone' => $facility_details[0]['phone'],
						             'facility_sms_consent'=>$facility_details[0]['map'],
						             //'lost_to_follow_up' => $facility_details[0]['lost_to_follow_up'], 
						             );
					$this -> session -> set_userdata($session_data);
					$user = $this -> session -> userdata('user_id');
					$sql = "update access_log set access_type='Logout' where user_id='$user'";
					$this -> db -> query($sql);
					$new_access_log = new Access_Log();
					$new_access_log -> machine_code = implode(",", $session_data);
					$new_access_log -> user_id = $this -> session -> userdata('user_id');
					$new_access_log -> access_level = $this -> session -> userdata('access_level');
					$new_access_log -> start_time = date("Y-m-d H:i:s");
					$new_access_log -> facility_code = $this -> session -> userdata('facility');
					$new_access_log -> access_type = "Login";
					$new_access_log -> save();
					//Set session to redirect the page to the previous page before logged out
					$this -> session -> set_userdata("prev_page", "1");
					redirect("home_controller/home");
				}

			}

		} else {//Not validated
			$data = array();
			$data['title'] = "System Login";
			$this -> load -> view("login_v", $data);
		}
	}

	private function _submit_validate() {
		// validation rules
		$this -> form_validation -> set_rules('username', 'Username', 'trim|required|min_length[2]|max_length[30]');

		$this -> form_validation -> set_rules('password', 'Password', 'trim|required|min_length[2]|max_length[30]');

		return $this -> form_validation -> run();
	}

	public function go_home($data) {
		$data['title'] = "System Home";
		$data['content_view'] = "home_v";
		$data['banner_text'] = "Dashboards";
		$data['link'] = "home";
		$this -> load -> view("template", $data);
	}

	public function save() {
		//default password
		$default_password='123456';

		$user_data=array(
					'Name' => $this -> input -> post('fullname',TRUE),
					'Username' => $this -> input -> post('username',TRUE),
					'Password' => md5($this -> encrypt -> get_key(). $default_password),
					'Access_Level' => $this -> input -> post('access_level',TRUE),
					'Facility_Code' => $this -> input -> post('facility',TRUE),
					'Created_By' => $this -> session -> userdata('user_id'),
					'Time_Created' => date('Y-m-d,h:i:s A'),
					'Phone_Number' => $this -> input -> post('phone',TRUE),
					'Email_Address' => $this -> input -> post('email',TRUE),
					'Active' => 1,
					'Signature' => 1
					);

		$this->db->insert("users",$user_data);

		//Save user facilities
		$this->save_user_facilities($this->db->insert_id(), $this -> input -> post('user_facilities_holder',TRUE));

		$this -> session -> set_userdata('msg_success', $this -> input -> post('fullname') . ' \' s details were successfully saved! The default password is <strong>'.$default_password.'</strong>');
		redirect('settings_management');
	}

	public function edit() {
		$access_level = $this -> session -> userdata('user_indicator');
		$user_type = "1";
		$facilities = "";
		//If user is a super admin, allow him to add only facilty admin and nascop pharmacist
		if ($access_level == "system_administrator") {
			$user_type = "indicator='nascop_pharmacist' or indicator='facility_administrator'";
			$facilities = Facilities::getAll();
		}
		//If user is a facility admin, allow him to add only facilty users
		else if ($access_level == "facility_administrator") {
			$facility_code = $this -> session -> userdata('facility');
			$user_type = "indicator='pharmacist'";
			$facilities = Facilities::getCurrentFacility($facility_code);
		}

		$user_id = $this -> input -> get('u_id');
		$data['users'] = Users::getUserAdmin($user_id);
		$data['user_type'] = Access_Level::getAll($user_type);
		echo json_encode($data);
	}

	public function update() {
		$user_id = $this -> input -> post('user_id');
		$name = $this -> input -> post('fullname');
		$username = $this -> input -> post('username');
		$access_Level = $this -> input -> post('access_level');
		$phone_number = $this -> input -> post('phone');
		$email_address = $this -> input -> post('email');
		$facility = $this -> input -> post('facility');

		$query = $this -> db -> query("UPDATE users SET Name='$name',Username='$username',Access_Level='$access_Level',Phone_Number='$phone_number',Email_Address='$email_address',Facility_Code='$facility' WHERE id='$user_id'");
		//$this -> session -> set_userdata('message_counter', '1');
		$this -> session -> set_userdata('msg_success', $this -> input -> post('username') . ' \' s details were successfully Updated!');
		$this -> session -> set_flashdata('filter_datatable', $this -> input -> post('username'));
		//Filter datatable
		redirect('settings_management');
	}

	public function enable($user_id) {
		$results = Users::getUser($user_id);
		$name = $results['Name'];
		$query = $this -> db -> query("UPDATE users SET Active='1'WHERE id='$user_id'");
		//$this -> session -> set_userdata('message_counter', '1');
		$this -> session -> set_userdata('msg_success', $name . ' was enabled!');
		$this -> session -> set_flashdata('filter_datatable', $name);
		//Filter datatable
		redirect('settings_management');
	}

	public function disable($user_id) {
		$results = Users::getUser($user_id);
		$name = $results['Name'];
		$query = $this -> db -> query("UPDATE users SET Active='0'WHERE id='$user_id'");
		//$this -> session -> set_userdata('message_counter', '2');
		$this -> session -> set_userdata('msg_error', $name . ' was disabled!');
		$this -> session -> set_flashdata('filter_datatable', $name);
		//Filter datatable
		redirect('settings_management');
	}

	public function logout($param = "1") {
                $machine_code = $this -> session -> userdata("machine_code_id");
		$last_id = Access_Log::getLastUser($this -> session -> userdata('user_id'));
		$this -> db -> where('id', $last_id);
		$this -> db -> update("access_log", array('access_type' => "Logout", 'end_time' => date("Y-m-d H:i:s")));
		$this -> session -> sess_destroy();
                
		if ($param == "2") {
			delete_cookie("actual_page");
		}
		redirect("system_management");
	}

	public function getIPLocation() {
		$ip = $_SERVER['REMOTE_ADDR'];
		return getCountryFromIP($ip, " NamE ");
	}

	public function update_machinecode($machine_code) {
		$machine_code = trim($machine_code);
		$this -> session -> set_userdata("machine_code_id", $machine_code);
		$user_id = $this -> session -> userdata("user_id");
		$this -> load -> database();
		$this -> db -> query("UPDATE access_log al,(SELECT MAX( id ) AS id FROM  `access_log` WHERE user_id = '$user_id' AND access_type =  'Login') as temp_log SET al.machine_code='$machine_code' WHERE al.id=temp_log.id");
	}

	public function sendActivationCode($username, $contact, $password, $code = "", $type = "phone") {

		//If activation code is to be sent through email
		if ($type == "email") {
			$email = $contact;
			//setting the connection variables
			$config['mailtype'] = "html";
			$config['protocol'] = 'smtp';
			$config['smtp_host'] = 'ssl://smtp.googlemail.com';
			$config['smtp_port'] = 465;
			$config['smtp_user'] = stripslashes('webadt.chai@gmail.com');
			$config['smtp_pass'] = stripslashes('WebAdt_052013');
			ini_set("SMTP", "ssl://smtp.gmail.com");
			ini_set("smtp_port", "465");
			$this -> load -> library('email', $config);
			$this -> email -> set_newline("\r\n");
			$this -> email -> from('webadt.chai@gmail.com', "WEB_ADT CHAI");
			$this -> email -> to("$email");
			$this -> email -> subject("Account Activation");
			$this -> email -> message("Dear $username,<p> You account has been created and your password is <b>$password</b></p>Please click the following link to activate your account.
			<form action='" . base_url() . "user_management/activation' method='post'>
			<input type='submit' value='Activate account' id='btn_activate_account'>
			<input type='hidden' name='activation_code' id='activation_code' value='" . $code . "'>
			</form>
			<br>
			Regards, <br>
			Web ADT Team.
			");

			//success message else show the error
			if ($this -> email -> send()) {
				echo 'Your email was successfully sent to ' . $email . '<br/>';
				//unlink($file);
				$this -> email -> clear(TRUE);

			} else {
				//show_error($this -> email -> print_debugger());
			}
			//ob_end_flush();

		}

		//If activatio code is to be sent via sms
		else if ($type == 'phone') {
			$phone = $contact;
			$message = "Your Web adt verification code is : " . $code;
			//$x= file_get_contents("http://41.57.109.238:13000cgi-bin/sendsms?username=clinton&password=ch41sms&to=$phone&text=$message");
			//ob_flush();

		}

	}

	public function resetPassword() {
		$data['title'] = "Reset Password";
		$this -> load -> view('resend_password_v', $data);
	}

	public function resendPassword() {

		$type = $this -> input -> post("type");
		$characters = strtoupper("abcdefghijklmnopqrstuvwxyz");
		$characters = $characters . 'abcdefghijklmnopqrstuvwxyz0123456789';
		$random_string_length = 8;
		$string = '';
		for ($i = 0; $i < $random_string_length; $i++) {
			$string .= $characters[rand(0, strlen($characters) - 1)];
		}
		$password = $string;
		$key = $this -> encrypt -> get_key();
		$encrypted_password = md5($key . $password);
		$timestamp = date("Y-m-d");

		//Change the password
		if ($type == 'email') {
			$email = $this -> input -> post("contact_email");
			$user_id_sql = $this -> db -> query("SELECT id FROM users WHERE Email_Address='$email' LIMIT 1");
			$arr = $user_id_sql -> result_array();
			$count = count($arr);
			$user_id = "";
			if ($count == 0) {
				$message = '<p class="message error">The email you entered was not found ! </p>';
				$this -> resetPassword($message);
			} else {
				foreach ($arr as $us_id) {
					$user_id = $us_id['id'];
				}
				$query = $this -> db -> query("update users set Password='$encrypted_password',Time_Created='$timestamp' where Email_Address='$email'");
				$new_password_log = new Password_Log();
				$new_password_log -> user_id = $user_id;
				$new_password_log -> password = $encrypted_password;
				$new_password_log -> save();
				$this -> sendPassword($email, $password, 'email');
			}

		} else if ($type == 'phone') {
			$phone = $this -> input -> post("contact_phone");
			$user_id_sql = $this -> db -> query("SELECT id FROM users WHERE Phone_Number='$phone' LIMIT 1");
			$arr = $user_id_sql -> result_array();
			$count = count($arr);
			$user_id = "";
			if ($count == 0) {
				$data['error'] = '<p class="alert-error">The phone number your entered was not found ! </p>';
				$this -> resetPassword($data);
			} else {
				foreach ($arr as $us_id) {
					$user_id = $us_id['id'];
				}
				$query = $this -> db -> query("update users set Password='$encrypted_password',Time_Created='$timestamp' where Phone_Number='$phone'");
				$new_password_log = new Password_Log();
				$new_password_log -> user_id = $user_id;
				$new_password_log -> password = $encrypted_password;
				$new_password_log -> save();
				$this -> sendPassword($phone, $password, "phone");
			}

		}

	}

	public function sendPassword($contact, $code = "", $type = "phone") {

		//If activation code is to be sent through email
		if ($type == "email") {

			$email = trim($contact);
			//setting the connection variables
			$config['mailtype'] = "html";
			$config['protocol'] = 'smtp';
			$config['smtp_host'] = 'ssl://smtp.googlemail.com';
			$config['smtp_port'] = 465;
			$config['smtp_user'] = stripslashes('webadt.chai@gmail.com');
			$config['smtp_pass'] = stripslashes('WebAdt_052013');
			ini_set("SMTP", "ssl://smtp.gmail.com");
			ini_set("smtp_port", "465");
			$this -> load -> library('email', $config);
			$this -> email -> set_newline("\r\n");
			$this -> email -> from('webadt.chai@gmail.com', "WEB_ADT CHAI");
			$this -> email -> to("$email");
			$this -> email -> subject("Account Activation");
			$this -> email -> message("Dear $contact, This is your new password:<b> $code </b><br>
										<br>
										Regards,<br>
										Web ADT Team
										");

			//success message else show the error
			if ($this -> email -> send()) {
				$data['message'] = 'Email address was sent to <b>' . $email . '</b> <br/>Your Password was Reset';
				//unlink($file);
				$this -> email -> clear(TRUE);

			} else {
				//$data['error'] = $this -> email -> print_debugger();
				//show_error($this -> email -> print_debugger());
			}
			//ob_end_flush();
			$data['reset'] = true;
			delete_cookie("actual_page");
			$data['title'] = "webADT | System Login";
			$this -> load -> view("login_v", $data);

		}
	}

	public function profile($data = "") {
		$data['title'] = 'webADT | User Profile';
		$data['banner_text'] = 'My Profile';
		$data['content_view'] = 'user_profile_v';
		$this -> base_params($data);
	}

	public function profile_update() {
		$data['title'] = 'webADT | User Profile';
		$data['banner_text'] = 'My Profile';
		$user_id = $this -> session -> userdata('user_id');
		$full_name = $this -> input -> post('u_fullname');
		$user_name = $this -> input -> post('u_username');
		$email = $this -> input -> post('u_email');
		$phone = $this -> input -> post('u_phone');
		$c_user = 0;
		$e_user = 0;

		//Check if username does not already exist
		//If username was changed by the user, check if it exists in the db
		if ($this -> session -> userdata('username') != $user_name) {
			$username_exist_sql = $this -> db -> query("SELECT * FROM users WHERE username='$user_name'");
			$c_user = count($username_exist_sql -> result_array());
		}
		//If email was changed by the user, check if it exists in the db
		if ($this -> session -> userdata('Email_Address') != $email) {
			$email_exist_sql = $this -> db -> query("SELECT * FROM users WHERE Email_Address='$email'");
			$e_user = count($email_exist_sql -> result_array());
		}

		if ($c_user > 0 and $e_user > 0) {
			$data['error'] = "<span class='message error'>The username and email entered are already in use!</span>";

		} else if ($c_user > 0) {
			$data['error'] = "<span class='message error'>The username entered is already in use !</span>";
		} else if ($e_user > 0) {
			$data['error'] = "<span class='message error'>The email entered is already in use !</span>";
		}

		//Neither email nor username is in use
		else if ($e_user == 0 and $c_user == 0) {
			//Update user details
			$update_user_sql = $this -> db -> query("UPDATE users SET Name='$full_name',username='$user_name',Email_Address='$email',Phone_Number='$phone' WHERE id='$user_id'");
			if ($update_user_sql == 1) {
				$message_success = "<span class='message info'>Your details were successfully updated!<span>";
			}
			//Update session details!
			$session_data = array('username' => $user_name, 'full_name' => $full_name, 'Email_Address' => $email, 'Phone_Number' => $phone);
			$this -> session -> set_userdata($session_data);
			$this -> session -> set_userdata("message_user_update_success", $message_success);

		}

		//Add/update user ordering sites
		$this->save_user_facilities($this->session->userdata('user_id'), $this -> input -> post('profile_user_facilities_holder',TRUE));


		$previous_url = $this -> input -> cookie('actual_page', true);
		redirect($previous_url);

	}

	public function base_params($data) {
		$this -> load -> view("template", $data);
	}

	public function resend_password()
	{
	    $email_address = $this->input->post("email_address",TRUE);
	    $default_password='123456';
	    $user=Users::get_email_account($email_address);
	    if($user){
            $this->db->where('id', $user[0]['id']);
            $user[0]['Password']=md5($this -> encrypt -> get_key(). $default_password);
		    $this->db->update('users', $user[0]); 
		    $notification='<div class="alert alert-block alert-success">
							  <button type="button" class="close" data-dismiss="alert">&times;</button>
							  <h4>RESET!</h4>
							  Account password was reset to the default password '.$default_password.'
							</div>';
	    }
	    else{
            $notification='<div class="alert alert-block alert-danger">
							  <button type="button" class="close" data-dismiss="alert">&times;</button>
							  <h4>FAILED!</h4>
							  Account does not exist
							</div>';
	    }
	    $this->session->set_flashdata("notification",$notification);
	    redirect("user_management/resetPassword");
	}

	public function save_user_facilities($user_id = '', $user_facilites = ''){
		$save_data = array('user_id' => $user_id, 'facility' => json_encode(explode(',', $user_facilites)));
		$table = 'user_facilities';
		if($user_facilites){
			$user = $this->db->get_where($table, array('user_id' => $user_id))->row_array();
			if($user){
				$this -> db -> where('id', $user['id']);
				$this -> db -> update($table, $save_data);
			}else{
				$this->db->insert($table, $save_data);
			}
		}
		return $save_data;
	}

	public function get_sites($user_id = ''){
		$data = json_encode(array());
		$this->db->select('facility');
		$row = $this->db->get_where('user_facilities', array('user_id' => $user_id))->row_array();
		if($row){
			$data = $row['facility'];
		}
		echo $data;
	}

}
