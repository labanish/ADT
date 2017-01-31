<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class viral_load_manual extends MY_Controller {
	function __construct() {
		parent::__construct();
		$this -> session -> set_userdata("link_id", "index");
		$this -> session -> set_userdata("linkSub", "viral_load_manual");
		$this -> session -> set_userdata("linkTitle", "Viral Load Results");
	}

	public function index() {
		$this -> listing();
	}

	public function listing()
	{
		$access_level = $this -> session -> userdata('user_indicator');
		$data = array();
		//get viral load from the database
		$sql="select * from patient_viral_load";
        $query = $this -> db -> query($sql);
        $viral_results = $query -> result_array();
		$tmpl = array('table_open' => '<table class="setting_table table table-bordered table-striped">');
		$this -> table -> set_template($tmpl);
		$this -> table -> set_heading('id','Patient CCC Number', 'Test Date', 'Result','Justification','Options');
		foreach ($viral_results as $viral_result) {
			$links = "";
			$array_param = array(
				'id' => $viral_result['id'], 
				'role' => 'button', 
				'class' => 'edit_user', 
				'data-toggle' => 'modal', 
				'name' => $viral_result['patient_ccc_number']
			);
			$links .= anchor('#edit_form', 'Edit', $array_param);
			$this -> table -> add_row(
				$viral_result['id'],$viral_result['patient_ccc_number'],$viral_result['test_date'],$viral_result['result'],$viral_result['justification'], $links);
		}
		$data['viral_result'] = $this -> table -> generate();
		$this -> base_params($data);
	}
	public function get_patient_ccc_number()
	{
		$sql="select patient_number_ccc as patient_ccc_number from patient";
        $query = $this -> db -> query($sql);
        $ccc_result = $query -> result_array();
        echo json_encode($ccc_result);

	}

	public function update() {
		$id = $this -> input -> post('id');
		$patient_ccc_number = $this -> input -> post('patient_ccc_number');
		$query = $this -> db -> query("UPDATE patient_viral_load SET patient_ccc_number='$patient_ccc_number' WHERE id='$id'");
		$this -> session -> set_userdata('msg_success', $this -> input -> post('patient_ccc_number') . ' was Updated');
		$this -> session -> set_flashdata('filter_datatable', $this -> input -> post('patient_ccc_number'));
		//Filter datatable
		redirect("settings_management");
	}

	private function _submit_validate() {
		// validation rules
		$this -> form_validation -> set_rules('patient_ccc_number', 'Patient CCC Number', 'trim|required|min_length[2]|max_length[100]');

		return $this -> form_validation -> run();
	}

	public function base_params($data) {
		$data['quick_link'] = "indications";
		$this -> load -> view('viral_load_manual_v', $data);
	}

}
?>