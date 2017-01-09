<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Drugcode_classification extends MY_Controller {
	function __construct() {
		parent::__construct();
		$this -> session -> set_userdata("link_id", "index");
		$this -> session -> set_userdata("linkSub", "drugcode_classification");
		$this -> session -> set_userdata("linkTitle", "Drug Code Classification");
	}

	public function index() {
		$this -> listing();
	}

	public function listing() {
		$access_level = $this -> session -> userdata('user_indicator');
		$data = array();
		$classifications = Drug_Classification::getAllHydrated($access_level);
		$tmpl = array('table_open' => '<table class="setting_table table table-bordered table-striped">');
		$this -> table -> set_template($tmpl);
		$this -> table -> set_heading('Id', 'Name', 'Options');
		foreach ($classifications as $classification) {
			$links = "";
			$array_param = array('id' => $classification['id'], 'role' => 'button', 'class' => 'edit_user', 'data-toggle' => 'modal', 'name' => $classification['Name']);
			if ($classification['Active'] == 1) {
				$links .= anchor('#edit_form', 'Edit', $array_param);
			}
			//Check if user is an admin
			if ($access_level == "facility_administrator") {

				if ($classification['Active'] == 1) {
					$links .= " | ";
					$links .= anchor('drugcode_classification/disable/' . $classification['id'], 'Disable', array('class' => 'disable_user'));
				} else {
					$links .= anchor('drugcode_classification/enable/' . $classification['id'], 'Enable', array('class' => 'enable_user'));
				}
			}

			$this -> table -> add_row($classification['id'], ucwords($classification['Name']), $links);
		}
		$data['classifications'] = $this -> table -> generate();
		$this -> base_params($data);
	}

	public function save() {

		//call validation function
		$valid = $this -> _submit_validate();
		if ($valid == false) {
			$data['settings_view'] = "classification_v";
			$this -> base_params($data);
		} else {
			$drugname = $this -> input -> post("classification_name");
			$generic_name = new Drug_classification();
			$generic_name -> Name = $drugname;
			$generic_name -> Active = "1";
			$generic_name -> save();
			$this -> session -> set_userdata('msg_success', $this -> input -> post('classification_name') . ' was Added');
			$this -> session -> set_flashdata('filter_datatable', $this -> input -> post('classification_name'));
			//Filter datatable
			redirect("settings_management");
		}

	}

	public function update() {
		$classification_id = $this -> input -> post('classification_id');
		$classification_name = $this -> input -> post("edit_classification_name");
		$query = $this -> db -> query("UPDATE drug_classification SET name='$classification_name' WHERE id='$classification_id'");
		$this -> session -> set_userdata('msg_success', $this -> input -> post('edit_classification_name') . ' was Updated');
		$this -> session -> set_flashdata('filter_datatable', $this -> input -> post('edit_classification_name'));
		//Filter datatable
		redirect("settings_management");
	}

	public function enable($classification_id) {
		$query = $this -> db -> query("UPDATE drug_classification SET Active='1'WHERE id='$classification_id'");
		$results = Drug_Classification::getClassification($classification_id);
		$this -> session -> set_userdata('msg_success', $results -> Name . ' was enabled');
		$this -> session -> set_flashdata('filter_datatable', $results -> Name);
		//Filter datatable
		redirect("settings_management");
	}

	public function disable($classification_id) {
		$query = $this -> db -> query("UPDATE drug_classification SET Active='0'WHERE id='$classification_id'");
		$results = Drug_Classification::getClassification($classification_id);
		$this -> session -> set_userdata('msg_error', $results -> Name . ' was disabled');
		$this -> session -> set_flashdata('filter_datatable', $results -> Name);
		//Filter datatable
		redirect("settings_management");
	}

	private function _submit_validate() {
		// validation rules
		$this -> form_validation -> set_rules('classification_name', 'Classification Name', 'trim|required|min_length[2]|max_length[100]');

		return $this -> form_validation -> run();
	}

	public function base_params($data) {
		$data['quick_link'] = "indications";
		$this -> load -> view('classification_v', $data);
	}

}
?>