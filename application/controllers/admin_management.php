<?php
class admin_management extends MY_Controller {
	function __construct() {
		parent::__construct();
		$this -> load -> library('encrypt');
		date_default_timezone_set('Africa/Nairobi');
	}

	public function addCounty() {
		$results = Counties::getAll();
		$dyn_table = "<table border='1' id='patient_listing'  cellpadding='5' class='dataTables'>";
		$dyn_table .= "<thead><tr><th>County Name</th><th> Options</th></tr></thead><tbody>";
		if ($results) {
			foreach ($results as $result) {
				if ($result['active'] == '1') {
					$option = "<a href='#edit_counties' data-toggle='modal' role='button' class='edit' table='counties' county='" . $result['county'] . "' county_id='" . $result['id'] . "'>Edit</a> | <a href='" . base_url() . "admin_management/disable/counties/" . $result['id'] . "' class='red'>Disable</a>";
				} else {
					$option = "<a href='#edit_counties' data-toggle='modal' role='button' class='edit' table='counties' county='" . $result['county'] . "' county_id='" . $result['id'] . "'>Edit</a> | <a href='" . base_url() . "admin_management/enable/counties/" . $result['id'] . "' class='green'>Enable</a>";
				}
				$dyn_table .= "<tr><td>" . $result['county'] . "</td><td>" . $option . "</td></tr>";
			}
		}
		$dyn_table .= "</tbody></table>";
		$data['label'] = 'County';
		$data['table'] = 'counties';
		$data['actual_page'] = 'View Counties';
		$data['dyn_table'] = $dyn_table;
		$this -> base_params($data);
	}

	public function addSatellite() {
		$results = Facilities::getSatellites($this -> session -> userdata("facility"));
		$dyn_table = "<table border='1' id='patient_listing'  cellpadding='5' class='dataTables'>";
		$dyn_table .= "<thead><tr><th>Facility Code</th><th>Facility Name</th><th>Options</th></tr></thead><tbody>";
		if ($results) {
			foreach ($results as $result) {
				$option = "<a href='" . base_url() . "admin_management/remove/" . $result['facilitycode'] . "'' class='red'>Remove</a>";
				$dyn_table .= "<tr><td>" . $result['facilitycode'] . "</td><td>" . $result['name'] . "</td><td>" . $option . "</td></tr>";
			}
		}
		$dyn_table .= "</tbody></table>";
		$data['label'] = 'Satellite';
		$data['table'] = 'facilities';
		$data['actual_page'] = 'View Satellites';
		$data['dyn_table'] = $dyn_table;
		$this -> base_params($data);
	}

	public function addDistrict() {
		$results = District::getAll();
		$dyn_table = "<table border='1' id='patient_listing'  cellpadding='5' class='dataTables'>";
		$dyn_table .= "<thead><tr><th>District Name</th><th> Options</th></tr></thead><tbody>";
		if ($results) {
			foreach ($results as $result) {
				if ($result['active'] == "1") {
					$option = "<a href='#edit_district' data-toggle='modal' role='button' class='edit' table='district' district='" . $result['Name'] . "' district_id='" . $result['id'] . "'>Edit</a> | <a href='" . base_url() . "admin_management/disable/district/" . $result['id'] . "' class='red'>Disable</a>";
				} else {
					$option = "<a href='#edit_district' data-toggle='modal' role='button' class='edit' table='district' district='" . $result['Name'] . "' district_id='" . $result['id'] . "'>Edit</a> | <a href='" . base_url() . "admin_management/enable/district/" . $result['id'] . "' class='green'>Enable</a>";
				}
				$dyn_table .= "<tr><td>" . $result['Name'] . "</td><td>" . $option . "</td></tr>";
			}
		}
		$dyn_table .= "</tbody></table>";
		$data['label'] = 'District';
		$data['table'] = 'district';
		$data['actual_page'] = 'View Districts';
		$data['dyn_table'] = $dyn_table;
		$this -> base_params($data);
	}

	public function addMenu() {
		$results = Menu::getAll();
		$dyn_table = "<table border='1' id='patient_listing'  cellpadding='5' class='dataTables'>";
		$dyn_table .= "<thead><tr><th>Menu Name</th><th>Menu URL</th><th>Menu Description</th><th> Options</th></tr></thead><tbody>";
		if ($results) {
			foreach ($results as $result) {
				if ($result['active'] == "1") {
					$option = "<a href='#edit_menu' data-toggle='modal' role='button' class='edit' table='menu' menu_name='" . $result['Menu_Text'] . "' menu_url='" . $result['Menu_Url'] . "' menu_desc='" . $result['Description'] . "' menu_id='" . $result['id'] . "'>Edit</a> | <a href='" . base_url() . "admin_management/disable/menu/" . $result['id'] . "' class='red'>Disable</a>";
				} else {
					$option = "<a href='#edit_menu' data-toggle='modal' role='button' class='edit' table='menu' menu_name='" . $result['Menu_Text'] . "' menu_url='" . $result['Menu_Url'] . "' menu_desc='" . $result['Description'] . "' menu_id='" . $result['id'] . "'>Edit</a> | <a href='" . base_url() . "admin_management/enable/menu/" . $result['id'] . "' class='green'>Enable</a>";
				}
				$dyn_table .= "<tr><td>" . $result['Menu_Text'] . "</td><td>" . $result['Menu_Url'] . "</td><td>" . $result['Description'] . "</td><td>" . $option . "</td></tr>";
			}
		}
		$dyn_table .= "</tbody></table>";
		$data['label'] = 'Menu';
		$data['table'] = 'menu';
		$data['column'] = 'active';
		$data['actual_page'] = 'View Menus';
		$data['dyn_table'] = $dyn_table;
		$this -> base_params($data);
	}
        public function addFAQ() {
                $results = Faq::getAll();
		$dyn_table = "<table border='1' id='patient_listing'  cellpadding='5' class='dataTables'>";
		$dyn_table .= "<thead><tr><th>Module</th><th>Question</th><th>Answer</th><th>Options</th></tr></thead><tbody>";
		$option = "";
		if ($results) {
			foreach ($results as $result) {
				if ($result['active'] == "1") {
					$option = "<a href='#edit_faq' data-toggle='modal' role='button' class='edit' table='faq' faq_module='".$result['modules']."' faq-question='" . $result['questions'] . "' faq_answer='" . $result['answers'] . "' faq_id='" . $result['id'] . "'>Edit</a> | <a href='" . base_url() . "admin_management/disable/faq/" . $result['id'] . "' class='red'>Disable</a>";
				} else {
					$option = "<a href='#edit_faq' data-toggle='modal' role='button' class='edit' table='faq' faq_module='".$result['modules']."' faq-question='" . $result['questions'] . "' faq_answer='" . $result['answers'] . "' faq_id='" . $result['id'] . "'>Edit</a> | <a href='" . base_url() . "admin_management/enable/faq/" . $result['id'] . "' class='green'>Enable</a>";
				}
				$dyn_table .= "<tr><td>".$result['modules']."</td><td>" . $result['questions'] . "</td><td>" . $result['answers'] . "</td><td>" . $option . "</td></tr>";
			}
		}
		$dyn_table .= "</tbody></table>";
		$data['label'] = 'FAQ';
		$data['table'] = 'faq';
		$data['actual_page'] = 'View FAQ';
		$data['dyn_table'] = $dyn_table;
		$this -> base_params($data); 
        }

	public function addUsers() {
		$results = Users::getThem();
		$dyn_table = "<table border='1' id='patient_listing'  cellpadding='5' class='dataTables'>";
		$dyn_table .= "<thead><tr><th>Full Name</th><th>UserName</th><th>Access Level</th><th>Email Address</th><th>Phone Number</th><th>Account Creator</th><th> Options</th></tr></thead><tbody>";
		$option = "";
		if ($results) {
			foreach ($results as $result) {
				if ($result['id'] != $this -> session -> userdata("user_id")) {
					if ($result['Active'] == "1") {
						$option = "<a href='" . base_url() . "admin_management/disable/users/" . $result['id'] . "' class='red'>Disable</a>";
					} else {
						$option = "<a href='" . base_url() . "admin_management/enable/users/" . $result['id'] . "' class='green'>Enable</a>";
					}
				}
				$dyn_table .= "<tr><td>" . $result['Name'] . "</td><td>" . $result['Username'] . "</td><td>" . $result['Access'] . "</td><td>" . $result['Email_Address'] . "</td><td>" . $result['Phone_Number'] . "</td><td>" . $result['Creator'] . "</td><td>" . $option . "</td></tr>";
			}
		}
		$dyn_table .= "</tbody></table>";
		$data['label'] = 'Users';
		$data['table'] = 'users';
		$data['column'] = 'active';
		$data['actual_page'] = 'View Users';
		$data['dyn_table'] = $dyn_table;
		$this -> base_params($data);
	}

	public function inactive() {
		$facility_code = $this -> session -> userdata("facility");
		$results = Users::getInactive($facility_code);
		$dyn_table = "<table border='1' id='patient_listing'  cellpadding='5' class='dataTables'>";
		$dyn_table .= "<thead><tr><th>Full Name</th><th>UserName</th><th>Access Level</th><th>Email Address</th><th>Phone Number</th><th>Account Creator</th><th> Options</th></tr></thead><tbody>";
		$option = "";
		if ($results) {
			foreach ($results as $result) {
				if ($result['id'] != $this -> session -> userdata("user_id")) {
					if ($result['Active'] == "1") {
						$option = "<a href='" . base_url() . "admin_management/disable/users/" . $result['id'] . "' class='red'>Disable</a>";
					} else {
						$option = "<a href='" . base_url() . "admin_management/enable/users/" . $result['id'] . "' class='green'>Enable</a>";
					}
				}
				$dyn_table .= "<tr><td>" . $result['Name'] . "</td><td>" . $result['Username'] . "</td><td>" . $result['Access'] . "</td><td>" . $result['Email_Address'] . "</td><td>" . $result['Phone_Number'] . "</td><td>" . $result['Creator'] . "</td><td>" . $option . "</td></tr>";
			}
		}
		$dyn_table .= "</tbody></table>";
		$data['label'] = 'Users';
		$data['table'] = '';
		$data['actual_page'] = 'Deactivated Users';
		$data['dyn_table'] = $dyn_table;
		$this -> base_params($data);
	}

	public function online() {
		$today = date('Y-m-d');
		$sql = "SELECT DISTINCT(user_id) as user_id,start_time as time_log FROM access_log  WHERE access_type = 'Login' AND start_time LIKE '%$today%'";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		if ($results) {
			foreach ($results as $result) {
				$user_id = $result['user_id'];
				$activity=$this->dateDiff($result['time_log'],date('Y-m-d H:i:s'));
				$results = Users::getSpecific($user_id);
				$dyn_table = "<table border='1' id='patient_listing'  cellpadding='5' class='dataTables'>";
				$dyn_table .= "<thead><tr><th>Full Name</th><th>UserName</th><th>Access Level</th><th>Email Address</th><th>Activity Duration</th></tr></thead><tbody>";
				$option = "";
				if ($results) {
					foreach ($results as $result) {
						$dyn_table .= "<tr><td>" . $result['Name'] . "</td><td>" . $result['Username'] . "</td><td>" . $result['Access'] . "</td><td>" . $result['Email_Address'] . "</td><td>" . $activity . "</td></tr>";
					}
				}
			}
		} else {
			$dyn_table = "<table border='1' id='patient_listing'  cellpadding='5' class='dataTables'>";
			$dyn_table .= "<thead><tr><th>Full Name</th><th>UserName</th><th>Access Level</th><th>Email Address</th><th>Activity Duration</th></tr></thead><tbody>";
		}
		$dyn_table .= "</tbody></table>";
		$data['label'] = 'Users';
		$data['column'] = 'active';
		$data['table'] = '';
		$data['actual_page'] = 'Online Users';
		$data['dyn_table'] = $dyn_table;
		$this -> base_params($data);
	}

	public function assignRights() {
		$sql = "select ur.id,al.level_name,m.menu_text,ur.active,ur.access_level as access_id,ur.menu as menu_id from user_right ur,menu m, access_level al where m.id=ur.menu and al.id=ur.access_level";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		$dyn_table = "<table border='1' id='patient_listing'  cellpadding='5' class='dataTables'>";
		$dyn_table .= "<thead><tr><th>Access Level</th><th>Menu</th><th> Options</th></tr></thead><tbody>";
		if ($results) {
			foreach ($results as $result) {
				if ($result['active'] == "1") {
					$option = "<a href='#edit_user_right' data-toggle='modal' role='button' class='edit' table='user_right' access_id='" . $result['access_id'] . "' edit_menu_id='" . $result['menu_id'] . "' right_id='" . $result['id'] . "'>Edit</a> | <a href='" . base_url() . "admin_management/disable/user_right/" . $result['id'] . "' class='red'>Disable</a>";
				} else {
					$option = "<a href='#edit_user_right' data-toggle='modal' role='button' class='edit' table='user_right' access_id='" . $result['access_id'] . "' edit_menu_id='" . $result['menu_id'] . "' right_id='" . $result['id'] . "'>Edit</a> | <a href='" . base_url() . "admin_management/enable/user_right/" . $result['id'] . "' class='green'>Enable</a>";
				}
				$dyn_table .= "<tr><td>" . $result['level_name'] . "</td><td>" . $result['menu_text'] . "</td><td>" . $option . "</td></tr>";
			}
		}
		$dyn_table .= "</tbody></table>";
		$data['label'] = 'User Rights';
		$data['table'] = 'user_right';
		$data['column'] = 'active';
		$data['actual_page'] = 'User Rights';
		$data['dyn_table'] = $dyn_table;
		$this -> base_params($data);
	}

	public function nascopSettings() {
		$results = file_get_contents(base_url() . 'assets/nascop.txt');
		$dyn_table = "<table border='1' id='patient_listing'  cellpadding='5' class='dataTables'>";
		$dyn_table .= "<thead><tr><th>Link</th><th>Options</th></tr></thead><tbody>";
		if ($results) {
			$dyn_table .= "<tr><td>" . $results . "</td><td><a href='#edit_nascop'data-toggle='modal' role='button' class='edit green' table='nascop' nascop_url='" . $results . "' >Update</a></td></tr>";
		}
		$dyn_table .= "</tbody></table>";
		$data['label'] = 'Nascop Settings';
		$data['column'] = 'active';
		$data['table'] = '';
		$data['actual_page'] = 'NASCOP Settings';
		$data['dyn_table'] = $dyn_table;
		$this -> base_params($data);
	}

	public function getAccessLogs() {
		$sql = "select * from access_log al left join users u on u.id=al.user_id";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		$dyn_table = "<table border='1' id='patient_listing'  cellpadding='5' class='dataTables'>";
		$dyn_table .= "<thead><tr><th>User</th><th>Start Time</th><th>End Time</th><th>Session Duration</th><th>Status</th></tr></thead><tbody>";
		if ($results) {
			foreach ($results as $result) {
				$time_log = date('Y-m-d H:i:s', strtotime($result['start_time']));
				if ($result['end_time']) {
					$now = date('Y-m-d H:i:s', strtotime($result['end_time']));
					$next_date=date('d-M-Y h:i:s a',strtotime($result['end_time']));
				} else {
					$now = date('Y-m-d H:i:s');
					$next_date="-";
				}
				$dd = date_diff(new DateTime($time_log), new DateTime($now));

				if ($dd -> h > 0) {
					$activity = $dd -> h . " Hour(s)" . $dd -> i . " Minutes and " . $dd -> s . " Seconds";
				} else {
					$activity = $dd -> i . " Minutes and " . $dd -> s . " Seconds";
				}
				$dyn_table .= "<tr><td>" . $result['Name'] . "</td><td>" . date('d-M-Y h:i:s a',strtotime($result['start_time'])) . "</td><td>" .$next_date. "</td><td>" . $activity . "</td><td>" . $result['access_type'] . "</td></tr>";
			}
		}
		$dyn_table .= "</tbody></table>";
		$data['label'] = 'Access Logs';
		$data['column'] = 'active';
		$data['table'] = '';
		$data['actual_page'] = 'Access Logs';
		$data['dyn_table'] = $dyn_table;
		$this -> base_params($data);
	}

	public function getDeniedLogs() {
		$sql = "select * from denied_log al left join users u on u.id=al.user_id";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		$dyn_table = "<table border='1' id='patient_listing'  cellpadding='5' class='dataTables'>";
		$dyn_table .= "<thead><tr><th>User</th><th>Timestamp</th></tr></thead><tbody>";
		if ($results) {
			foreach ($results as $result) {
				$dyn_table .= "<tr><td>" . $result['Name'] . "</td><td>" . date('d-M-Y h:i:s a',strtotime($result['timestamp'])). "</td></tr>";
			}
		}
		$dyn_table .= "</tbody></table>";
		$data['label'] = 'Denied Logs';
		$data['column'] = 'active';
		$data['table'] = '';
		$data['actual_page'] = 'Denied Logs';
		$data['dyn_table'] = $dyn_table;
		$this -> base_params($data);
	}

	public function save($table = "") {
		if ($table == "counties") {
			$county_name = $this -> input -> post("name");
			$new_county = new Counties();
			$new_county -> county = $county_name;
			$new_county -> save();
			$this -> session -> set_userdata('msg_success', 'County: ' . $county_name . ' was Added');
			$this -> session -> set_userdata('default_link', 'addCounty');
		} else if ($table == "facilities") {
			$satellite_code = $this -> input -> post("facility");
			if ($satellite_code) {
				$central_code = $this -> session -> userdata("facility");
				$sql = "update facilities set parent='$central_code' where facilitycode='$satellite_code'";
				$this -> db -> query($sql);
				$this -> session -> set_userdata('msg_success', 'Facility No: ' . $satellite_code . ' was Added as a Satellite');
			}
			$this -> session -> set_userdata('default_link', 'addSatellite');
		} else if ($table == "district") {
			$disrict_name = $this -> input -> post("name");
			$new_district = new District();
			$new_district -> Name = $disrict_name;
			$new_district -> save();
			$this -> session -> set_userdata('msg_success', 'District: ' . $disrict_name . ' was Added');
			$this -> session -> set_userdata('default_link', 'addDistrict');
		} else if ($table == "menu") {
			$menu_name = $this -> input -> post("menu_name");
			$menu_url = $this -> input -> post("menu_url");
			$menu_desc = $this -> input -> post("menu_description");
			$new_menu = new Menu();
			$new_menu -> Menu_Text = $menu_name;
			$new_menu -> Menu_Url = $menu_url;
			$new_menu -> Description = $menu_desc;
			$new_menu -> save();
			$this -> session -> set_userdata('msg_success', 'Menu: ' . $menu_name . ' was Added');
			$this -> session -> set_userdata('default_link', 'addMenu');
		}else if ($table == "faq") {
                        $faq_module=  $this-> input -> post("faq_module");
			$faq_question = $this -> input -> post("faq_question");
			$faq_answer = $this -> input -> post("faq_answer");
			$new_faq = new Faq();
                        $new_faq -> modules = $faq_module;
			$new_faq -> questions = $faq_question;
			$new_faq -> answers = $faq_answer;
			$new_faq -> save();
			$this -> session -> set_userdata('msg_success', 'FAQ was Added');
			$this -> session -> set_userdata('default_link', 'addFAQ');
		} else if ($table == "users") {
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

			$this -> session -> set_userdata('msg_success', 'User: ' . $this -> input -> post('fullname',TRUE) . ' was Added');
			$this -> session -> set_userdata('default_link', 'addUsers');
		} else if ($table == "user_right") {
			$access_level = $this -> input -> post("access_level");
			$menu = $this -> input -> post("menus");
			if ($menu) {
				$new_right = new User_Right();
				$new_right -> Access_Level = $access_level;
				$new_right -> Menu = $menu;
				$new_right -> Access_Type = "4";
				$new_right -> save();
				$this -> session -> set_userdata('msg_success', 'User Right was Added');
				$this -> session -> set_userdata('default_link', 'assignRights');
			}
		}
		redirect("home_controller/home");
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
	}

	public function inactive_users() {
		$facility_code = $this -> session -> userdata("facility");
		$sql = "select count(*) as total from users where Facility_Code='$facility_code' and Active='0' and access_level !='2'";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		$total = 0;
		$temp = "";
		if ($results) {
			foreach ($results as $result) {
				$total = $result['total'];
			}
		}
		$temp = $total;
		echo $temp;
	}

	public function online_users() {
		$facility_code = $this -> session -> userdata("facility");
		$today = date('Y-m-d');
		$sql = "update access_log set access_type ='Logout' WHERE datediff('$today',`start_time`)>0";
		$query = $this -> db -> query($sql);
		$sql = "SELECT COUNT(DISTINCT(user_id)) AS total FROM access_log WHERE access_type = 'Login' AND `start_time` LIKE '%$today%'";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		$total = 0;
		$temp = "";
		if ($results) {
			foreach ($results as $result) {
				$total = $result['total'];
			}
		}
		$temp = $total;
		echo $temp;
	}

	public function disable($table = "", $id = "") {
		if ($table == "users") {
			$sql = "update $table set Active='0' where id='$id'";
		} else {
			$sql = "update $table set active='0' where id='$id'";
		}
		$this -> db -> query($sql);
		$this -> session -> set_userdata('msg_error', $table . ' Record No:' . $id . ' was disabled');
		$this -> setDefaultLink($table);
		redirect("home_controller/home");
	}

	public function enable($table = "", $id = "") {
		if ($table == "users") {
			$sql = "update $table set Active='1' where id='$id'";
		} else {
			$sql = "update $table set active='1' where id='$id'";
		}
		$this -> db -> query($sql);
		$this -> session -> set_userdata('msg_success', $table . ' Record No:' . $id . ' was enabled');
		$this -> setDefaultLink($table);
		redirect("home_controller/home");
	}

	public function remove($facilitycode = "") {
		$sql = "update facilities set parent='' where facilitycode='$facilitycode'";
		$this -> db -> query($sql);
		$this -> session -> set_userdata('msg_error', ' Facility No:' . $facilitycode . ' was removed as a Satellite');
		$this -> session -> set_userdata('default_link', 'addSatellite');
		redirect("home_controller/home");
	}

	public function update($table = "") {
		if ($table == "counties") {
			$county_id = $this -> input -> post("county_id");
			$county_name = $this -> input -> post("county_name");
			$this -> db -> where('id', $county_id);
			$this -> db -> update($table, array('county' => $county_name));
			$this -> session -> set_userdata('msg_success', 'County: ' . $county_name . ' was Updated');
			$this -> session -> set_userdata('default_link', 'addCounty');
		} else if ($table == "district") {
			$district_id = $this -> input -> post("district_id");
			$district_name = $this -> input -> post("district_name");
			$this -> db -> where('id', $district_id);
			$this -> db -> update($table, array('name' => $district_name));
			$this -> session -> set_userdata('msg_success', 'District: ' . $district_name . ' was Updated');
			$this -> session -> set_userdata('default_link', 'addDistrict');
		} else if ($table == "menu") {
			$menu_id = $this -> input -> post("menu_id");
			$menu_name = $this -> input -> post("menu_name");
			$menu_url = $this -> input -> post("menu_url");
			$menu_description = $this -> input -> post("menu_description");
			$this -> db -> where('id', $menu_id);
			$this -> db -> update($table, array('menu_text' => $menu_name, 'menu_url' => $menu_url, 'description' => $menu_description));
			$this -> session -> set_userdata('msg_success', 'Menu: ' . $menu_name . ' was Updated');
			$this -> session -> set_userdata('default_link', 'addMenu');
		} elseif ($table=="faq") {
                        $faq_id = $this -> input -> post("faq_id");
                        $faq_module = $this -> input -> post("faq_module");
			$faq_question = $this -> input -> post("faq_question");
			$faq_answer = $this -> input -> post("faq_answer");
			$this -> db -> where('id', $faq_id);
			$this -> db -> update($table, array('modules' => $faq_module,'questions' => $faq_question, 'answers' => $faq_answer));
			$this -> session -> set_userdata('msg_success', 'FAQ was Updated');
			$this -> session -> set_userdata('default_link', 'addFAQ');
                
                }else if ($table == "user_right") {
			$right_id = $this -> input -> post("right_id");
			$access_id = $this -> input -> post("access_level");
			$menu_id = $this -> input -> post("menus");
			$this -> db -> where('id', $right_id);
			$this -> db -> update($table, array('access_level' => $access_id, 'menu' => $menu_id));
			$this -> session -> set_userdata('msg_success', 'User Right was Updated');
			$this -> session -> set_userdata('default_link', 'assignRights');
		} else if ($table = "nascop") {
			$myFile = '././assets/nascop.txt';
			$fh = fopen($myFile, 'w') or die("can't open file");
			$stringData = $this -> input -> post("nascop_url");
			fwrite($fh, $stringData);
			fclose($fh);
			$this -> session -> set_userdata('msg_success', 'NASCOP URL was Updated');
			$this -> session -> set_userdata('default_link', 'nascopSettings');
		}
		redirect("home_controller/home");
	}

	public function setDefaultLink($table = "") {
		if ($table == "counties") {
			$this -> session -> set_userdata('default_link', 'addCounty');
		} else if ($table == "users") {
			$this -> session -> set_userdata('default_link', 'addUsers');
		} else if ($table == "district") {
			$this -> session -> set_userdata('default_link', 'addDistrict');
		} else if ($table == "menu") {
			$this -> session -> set_userdata('default_link', 'addMenu');
		}else if ($table=="faq") {
                        $this -> session -> set_userdata('default_link', 'addFAQ');
                } else if ($table == "user_right") {
			$this -> session -> set_userdata('default_link', 'assignRights');
		} else if ($table = "nascop") {
			$this -> session -> set_userdata('default_link', 'nascopSettings');
		}
	}

	public function getSystemUsage($period = '') {
		$dataArray = array();
		$total_series = array();
		$sql = "select * from access_level order by id asc";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		$count = 1;
		foreach ($results as $result) {
			$access_level = $result['id'];
			$level = $result['level_name'];
			$sql = "SELECT acl.id AS access, acl.level_name, COUNT(*) AS total FROM access_log al,access_level acl  WHERE DATEDIFF(CURDATE(),al.start_time) <=  '$period' AND acl.id = al.access_level AND al.access_level='$access_level'";
			$query = $this -> db -> query($sql);
			$results = $query -> result_array();
			if ($results) {
				foreach ($results as $result) {
					$total = $result['total'];
					$dataArray[] = (int)$total;
				}
			}
			$series = array('name' => "Usage", 'data' => $dataArray);
		}
		$total_series[] = $series;

		$columns = array("System Admin", "Facility User", "Facility Admin");
		$resultArray = json_encode($total_series);
		$categories = json_encode($columns);
		$resultArraySize = 0;
		$data['resultArraySize'] = $resultArraySize;
		$data['container'] = 'chart_expiry';
		$data['chartType'] = 'bar';
		$data['title'] = 'Chart';
		$data['chartTitle'] = 'System Usage Summary';
		$data['categories'] = $categories;
		$data['yAxix'] = 'No. Of Times';
		$data['resultArray'] = $resultArray;
		$this -> load -> view('chart_v', $data);
	}

	public function getWeeklySumary($startdate = '', $enddate = '') {
		$dataArray = array();
		$total_series = array();
		$timestamp = time();
		$edate = date('Y-m-d', $timestamp);
		$series = array();
		$dates = array();
		$columns = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
		$x = 6;
		$y = 0;
		if ($startdate == "" || $enddate == "") {
			for ($i = 0; $i < $x; $i++) {
				if (date("D", $timestamp) != "Sun") {
					$sdate = date('Y-m-d', $timestamp);
					//Store the days in an array
					$dates[$y] = $sdate;
					$y++;
				}
				//If sunday is included, add one more day
				else {$x = 8;
				}
				$timestamp += 24 * 3600;
			}
			$start_date = $sdate;
			$end_date = $edate;
		} else {
			$startdate = strtotime($startdate);
			for ($i = 0; $i < $x; $i++) {
				if (date("D", $startdate) != "Sun") {
					$sdate = date('Y-m-d', $startdate);
					//Store the days in an array

					$dates[$y] = $sdate;
					$y++;
				}
				//If sunday is included, add one more day
				else {$x = 8;
				}
				$startdate += 24 * 3600;
			}
			$start_date = $startdate;
			$end_date = $enddate;
		}
//print_r($dates);die();
		foreach ($dates as $date_period) {
			$sql = "SELECT count(*) as total FROM access_log WHERE DATE_FORMAT(start_time,'%Y-%m-%d')='$date_period' ORDER BY start_time ";
			$query = $this -> db -> query($sql);
			$results = $query -> result_array();
			foreach ($results as $value) {
				$total = $value['total'];
				$dataArray[] = (int)$total;
			}
			$series = array('name' => "Summary", 'data' => $dataArray);
		}
		$total_series[] = $series;

		$resultArray = json_encode($total_series);
		$categories = json_encode($columns);
		$resultArraySize = 0;
		$data['resultArraySize'] = $resultArraySize;
		$data['container'] = 'chart_enrollment';
		$data['chartType'] = 'bar';
		$data['title'] = 'Chart';
		$data['chartTitle'] = 'Weekly System Access Summary';
		$data['categories'] = $categories;
		$data['yAxix'] = 'Access Total';
		$data['resultArray'] = $resultArray;
		$this -> load -> view('chart_v', $data);

	}

	function dateDiff($time1, $time2, $precision = 6) {
		// If not numeric then convert texts to unix timestamps
		if (!is_int($time1)) {
			$time1 = strtotime($time1);
		}
		if (!is_int($time2)) {
			$time2 = strtotime($time2);
		}

		// If time1 is bigger than time2
		// Then swap time1 and time2
		if ($time1 > $time2) {
			$ttime = $time1;
			$time1 = $time2;
			$time2 = $ttime;
		}

		// Set up intervals and diffs arrays
		$intervals = array('year', 'month', 'day', 'hour', 'minute', 'second');
		$diffs = array();

		// Loop thru all intervals
		foreach ($intervals as $interval) {
			// Create temp time from time1 and interval
			$ttime = strtotime('+1 ' . $interval, $time1);
			// Set initial values
			$add = 1;
			$looped = 0;
			// Loop until temp time is smaller than time2
			while ($time2 >= $ttime) {
				// Create new temp time from time1 and interval
				$add++;
				$ttime = strtotime("+" . $add . " " . $interval, $time1);
				$looped++;
			}

			$time1 = strtotime("+" . $looped . " " . $interval, $time1);
			$diffs[$interval] = $looped;
		}

		$count = 0;
		$times = array();
		// Loop thru all diffs
		foreach ($diffs as $interval => $value) {
			// Break if we have needed precission
			if ($count >= $precision) {
				break;
			}
			// Add value and interval
			// if value is bigger than 0
			if ($value > 0) {
				// Add s if value is not 1
				if ($value != 1) {
					$interval .= "s";
				}
				// Add value and interval to times array
				$times[] = $value . " " . $interval;
				$count++;
			}
		}

		// Return string with times
		return implode(", ", $times);
	}

	public function base_params($data) {
		$data['content_view'] = "admin/add_param_a";
		$data['title'] = "webADT | System Admin";
		$data['banner_text'] = "System Admin";
		$this -> load -> view('admin/admin_template', $data);
	}

}
