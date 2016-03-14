<?php

ob_start();

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
		// success Messages and Information
		$succuss = "Updates finished successfully.  ";
		$error = "Updates already exists in the Database";
		$error2 = "No changes were made";
		$counter = 0;
		$counter_1 = 0;
		$counter_2 = 0;
		$message = " Table(s) updated.";
		// arrays for adding new changes ito the sync_regimen Category
		$names = array('Adult Third Line','Paediatric Third Line','OIs Medicines [1. Universal Prophylaxis]','OIs Medicines [2. IPT]','OIs Medicines {CM} and {OC} For Diflucan Donation Program ONLY');
		$ids = array(17,18,19,20,21);
		// check the data if available
		$sql = "SELECT * FROM `testadt`.`sync_regimen_category` WHERE `sync_regimen_category`.`name` = 'Other Adult ART'";
		$result = $this->db->query($sql)->result_array();


		// check the data if available
		$sql_check = "SELECT * FROM `testadt`.`sync_regimen_category` WHERE id in (17,18,19,20,21)";
		$result_check = $this->db->query($sql_check)->result_array();

		//check if data is available

		$sql_check_1 = "SELECT * FROM `testadt`.`cdrr_item` WHERE `cdrr_item`.`Name` IN ('adjustments_neg')";
		$result_check_1 = array($this->db->query($sql_check_1));

		// echo "<pre>"; print_r($result_check_1); die('Done'); // stuck here


			if (count($result)>0) {

				$updatequery = "DELETE FROM `testadt`.`sync_regimen_category` WHERE `sync_regimen_category`.`name` = 'Other Adult ART'";
				$newresult = $this->db->query($updatequery);

				++$counter;
			}
			else {

				$counter;
			}

			if(count($result_check)<=0){
				$this->db->query("ALTER TABLE `sync_regimen_category` CHANGE `Name` `Name` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL");

				for ($i=0; $i < count($ids); $i++) { 
					$id = $ids[$i];
					$name = $names[$i];
					$sql_insert = "INSERT INTO `testadt`.`sync_regimen_category` VALUES ('$id','$name','1','2')";
					$this->db->query($sql_insert);
				}
				++$counter_1 ;
			}

			// Altering the cdrr_Item table (adding one column adjustments_neg)

			if($result_check_1){
				$this->db->query("ALTER TABLE `cdrr_item` ADD `adjustments_neg` INT(11) NULL DEFAULT NULL AFTER `adjustments`");

				++$counter_2;
			}

			if(($counter && $counter_1 && $counter_2)==0){

				echo $error;

			}
			elseif ($counter ==1 && $$counter1 ==1 && $counter2){

				echo $succuss;
				
			}
			else{

			}



	}

}
ob_get_clean();
?>