<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Settings_Management extends MY_Controller {
	function __construct() {
		parent::__construct();
		if(!$this->session->userdata("link_id")){
			$this->session->set_userdata("link_id","index");
			$this->session->set_userdata("linkSub","regimen_management");
		}
		
	}

	public function index() {
		$access_level = $this -> session -> userdata('user_indicator');
		if($access_level=="system_administrator"){
			$data['settings_view']='settings_system_admin_v';
		}
		else{
			$data['content_view'] = "settings_v";
		}
		$this->base_params($data);

	}

	public function base_params($data) {
		$data['title'] = "System Settings";
		$data['banner_text'] = "System Settings";
		$data['link'] = "settings_management";
		$this -> load -> view("template", $data);
	}
	
	public function getMenus(){
		$menus=Menu::getAllActive();
		echo json_encode($menus);
	}
	
	public function getAccessLevels(){
		$access=Access_Level::getAllHydrated();
		echo json_encode($access);
	}

	// new update tables functions

	public function updateTables(){

		$succuss = "Updates finished successfully";
		$error = "Updates already exists in the Database";

		$sql = "SELECT * FROM `testadt.sync_regimen_category` WHERE `sync_regimen_category.name` = 'Other Adult ART'";
		$result = $this->db->query($sql);

			if ($result) {

				$updatequery = "DELETE FROM `testadt`.`sync_regimen_category` WHERE `sync_regimen_category.name` = 'Other Adult ART'";
				$this->db->query($updatequery);
					
			}
			else{

			}

	}

}
