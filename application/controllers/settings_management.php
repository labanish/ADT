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
		// check the data if available table name = "sync_regimen_category"
		$sql = "SELECT * FROM `sync_regimen_category` WHERE `sync_regimen_category`.`name` = 'Other Adult ART'";
		$result = $this->db->query($sql)->result_array();


		// check the data if available table name = "sync_regimen_category"
		$sql_check = "SELECT * FROM `sync_regimen_category` WHERE id in (17,18,19,20,21)";
		$result_check = $this->db->query($sql_check)->result_array();

		//check if data is available table name = "cdrr_Item"
		$sql_check_1 = "SELECT * FROM `cdrr_item` WHERE `cdrr_item`.`Name` IN ('adjustments_neg')";
		$result_check_1 = array($this->db->query($sql_check_1));

		//check data is available. Table name = regimen_category
		$sql_check_2 = "SELECT * FROM `regimen_category` WHERE id > 11";
		$result_check_2 = array($this->db->query($sql_check_2)->result());


			if (count($result)>0) {

				$updatequery = "DELETE FROM `sync_regimen_category` WHERE `sync_regimen_category`.`name` = 'Other Adult ART'";
				$newresult = $this->db->query($updatequery);

				++$counter;
			}
			else {

				$counter;
			}

			if(count($result_check[0])<=0){
				$this->db->query("ALTER TABLE `sync_regimen_category` CHANGE `Name` `Name` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL");
				// updatating the table {sync_regimen_category}
				for ($i=0; $i < count($ids); $i++) { 
					$id = $ids[$i];
					$name = $names[$i];
					$sql_insert = "INSERT INTO `sync_regimen_category` VALUES ('$id','$name','1','2')";
					$this->db->query($sql_insert);
				}
				++$counter_1 ;
			}

			// Inheriting insert statement for reuse in table {regimen_category}
			if (count($result_check_2[0])<=0) {

				// echo "<pre>";print_r($names);die;
				for ($i=0; $i < count($names) ; $i++) { 
				$name = $names[$i];
				$sql_insert = "INSERT INTO `regimen_category` VALUES (NULL,'$name','1','2')"; //LOL!! Dont Jugde!
				// echo "$sql_insert<br/>";
				$this->db->query($sql_insert);
				}

			}
		
			// Altering the cdrr_Item table (adding one column adjustments_neg)

			if($result_check_1){
				$this->db->query("ALTER TABLE `cdrr_item` ADD `adjustments_neg` INT(11) NULL DEFAULT NULL AFTER `adjustments`");

				$sql = "create table`mirth_sync` (
	`id` int (10)  primary key,
	`patient_number_ccc` varchar (300) not null,
	`first_name` varchar (150) not null,
	`last_name` varchar (150) not null,
	`other_name` varchar (150) not null,
	`dob` varchar (96) not null,
	`pob` varchar (300) not null,
	`gender` varchar (6) not null,
	`weight` varchar (60) not null,
	`height` varchar (60) not null,
	`sa` varchar (60) not null,
	`physical` text not null,
	`other_drugs` text not null,
	`date_enrolled` varchar (96) not null,
	`source` varchar (150) not null,
	`supported_by` varchar (30) not null,
	`timestamp` varchar (96) not null ,
	`facility_code` varchar (30) not null,
	`service` varchar (150) not null,
	`start_regimen` varchar (150) not null,
	`start_regimen_date` varchar (45) not null,
	`current_regimen` varchar (765) not null,
	`start_height` varchar (60) not null,
	`start_weight` varchar (60) not null,
	`start_bsa` varchar (60) not null,
	`active` int (11) not null DEFAULT 1,
	`who_stage` int (11) not null,
	`inserted` int (11) not null DEFAULT 0
)
"; //LOL!! Dont Jugde!
				// echo "$sql_insert<br/>";
				$this->db->query($sql);
				++$counter_2;
			}

			if($counter ==0 && $counter_1==0 && $counter_2 ==0){

				echo $error;

			}
			elseif ($counter ==1 || $counter_1 ==1 || $counter_2 ==1){

				echo $succuss;
				
			}
			else{
				echo $error2;
			}

	}

}
ob_get_clean();
?>