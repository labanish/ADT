<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Backup_Management extends MY_Controller {
	function __construct() {
		parent::__construct();
		date_default_timezone_set('Africa/Nairobi');
	}

	public function index() {
		$data['content_view'] = 'backup_v';
		$data['banner_text'] = "Database Backup";
		$data['hide_side_menu'] = 1;
		$data['actual_page'] = 'Database Backup';
		$this -> base_params($data);
	}

	public function backup_db() {
		$file_path = $this -> input -> post('location', TRUE);
		$file_path = addslashes($file_path);
		$CI = &get_instance();
		$CI -> load -> database();
		$hostname_port = $CI -> db -> hostname;
		$username = $CI -> db -> username;
		$password = $CI -> db -> password;
		$current_db = $CI -> db -> database;
		
		//Fix for including port(it was combining both hostname and port)
		$hostname_port_tmp = explode(':', $hostname_port);
		$hostname = $hostname_port_tmp[0];
		$port = $hostname_port_tmp[1];

		$this -> load -> dbutil();
		if ($this -> dbutil -> database_exists($current_db)) {
			$mysql_home = realpath($_SERVER['MYSQL_HOME']) . "\mysqldump";
			$outer_file = "webadt_" . date('d-M-Y h-i-sa') . ".sql";
			$file_path = "\"" . $file_path . "\\" . $outer_file . "\"";
			$mysql_bin = str_replace("\\", "\\\\", $mysql_home);
			$mysql_con = $mysql_bin . ' -u ' . $username . ' -p' . $password . ' -h ' . $hostname . ' -P '.$port.' '. $current_db . ' > ' . $file_path;
			exec($mysql_con);
			$error_message = "<div class='alert-success'><button type='button' class='close' data-dismiss='alert'>&times;</button><strong>Backup!</strong> Database Backup Successful</div>";
			$this -> session -> set_flashdata('error_message', $error_message);
			redirect("backup_management");
		}
	}

	public function base_params($data) {
		$data['title'] = "webADT | Data Backup";
		$data['link'] = "backup_management";
		$this -> load -> view('template', $data);
	}

}
