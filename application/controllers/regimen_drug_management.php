<?php
ob_start();

class Regimen_Drug_Management extends MY_Controller {
	function __construct() {
		parent::__construct();
		$this -> session -> set_userdata("link_id", "index");
		$this -> session -> set_userdata("linkSub", "regimen_drug_management");
		$this -> session -> set_userdata("linkTitle", "Regimen Drug Management");
	}

	public function index() {
		$this -> listing();
	}

	public function listing() {
		$access_level = $this -> session -> userdata('user_indicator');
		$source = 0;
		if ($access_level == "pharmacist") {
			$source = $this -> session -> userdata('facility');
		}
		$data = array();
		$data['styles'] = array("jquery-ui.css");
		$data['scripts'] = array("jquery-ui.js");
		$data['regimens'] = Regimen::getAll($source);
		$data['regimens_enabled'] = Regimen::getAllEnabled($source);
		$data['regimen_categories'] = Regimen_Category::getAll();
		$data['regimen_service_types'] = Regimen_Service_Type::getAll();
		$data['drug_codes'] = Drugcode::getAll($source);
		$data['drug_codes_enabled'] = Drugcode::getAllEnabled($source);
		$this -> base_params($data);
	}

	public function save() {
		if ($this -> input -> post()) {
			$access_level = $this -> session -> userdata('user_indicator');
			$source = 0;
			$drug_message = array();

			if ($access_level == "pharmacist") {
				$source = $this -> session -> userdata('facility');
			}
			//get drugs selected
			$drugs = $this -> input -> post('drugs_holder',TRUE);
			if ($drugs != null) {
				$drugs=explode(",", $drugs);
				foreach ($drugs as $drug) {
					//get drug name
					$results = Drugcode::getDrugCode($drug);
					//check if drug and regimen composite key is duplicate
					$duplicate = $this -> check_duplicate($this -> input -> post('regimen'), $drug);
					if ($duplicate == false) {
						$regimen_drug = new Regimen_Drug();
						$regimen_drug -> Regimen = $this -> input -> post('regimen');
						$regimen_drug -> Drugcode = $drug;
						$regimen_drug -> Source = $source;
						$regimen_drug -> save();
						$message = " was successfully Added!";
					} else {
						$message = " exists could not be added!";
					}
					$drug_message[] = $results -> Drug . $message;
				}
				$drug_message = implode(",", $drug_message);
				$this -> session -> set_userdata('msg_success', $drug_message);
			}else{
				$drug_message="Failed!No drugs were be selected.";
				$this -> session -> set_userdata('msg_success', $drug_message);
			}
		}
		redirect('settings_management');
	}

	public function enable($regimen_drug_id) {
		$this -> load -> database();
		$query = $this -> db -> query("UPDATE regimen_drug SET active='1'WHERE drugcode='$regimen_drug_id'");
		$results = Drugcode::getDrugCode($regimen_drug_id);
		//$this -> session -> set_userdata('message_counter', '1');
		$this -> session -> set_userdata('msg_success', $results -> Drug . ' was enabled!');
		redirect('settings_management');
	}

	public function disable($regimen_drug_id) {
		$this -> load -> database();
		$query = $this -> db -> query("UPDATE regimen_drug SET active='0'WHERE drugcode='$regimen_drug_id'");
		$results = Drugcode::getDrugCode($regimen_drug_id);
		//$this -> session -> set_userdata('message_counter', '2');
		$this -> session -> set_userdata('msg_error', $results -> Drug . ' was disabled!');
		redirect('settings_management');
	}

	public function check_duplicate($regimen_id, $drug_id) {
		$sql = "SELECT * FROM regimen_drug WHERE regimen='$regimen_id' AND drugcode='$drug_id' AND active='1'";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		$duplicate = true;
		if (!$results) {
			$duplicate = false;
		}
		return $duplicate;
	}

	public function base_params($data) {
		$data['quick_link'] = "regimen_drug";
		$data['title'] = "Regimen_Drug Management";
		$data['banner_text'] = "Regimen Drug Management";
		$data['link'] = "settings_management";
		$this -> load -> view('regimen_drug_listing_v', $data);
	}

}
ob_get_clean();
?>