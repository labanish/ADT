<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class dossing_chart extends MY_Controller {
	function __construct() {
		parent::__construct();
		$this -> session -> set_userdata("link_id", "index");
		$this -> session -> set_userdata("linkSub", "dossing_chart");
		$this -> session -> set_userdata("linkTitle", "Pediatrics Dossing Chart");
	}

	public function index() {
		$this -> listing();
	}

	public function listing() {
		$access_level = $this -> session -> userdata('user_indicator');
		$data = array();
		//get dosssing information from the dossing_chart table
		$sql="select ds.id,min_weight,max_weight,d.drug,do.Name as dose,ds.is_active as is_active
			  from dossing_chart ds 
			  inner join drugcode d on d.id=ds.drug_id
			  inner join dose do on do.id=ds.dose_id
				 ";
		$query = $this -> db -> query($sql);
		$classifications = $query -> result_array();
		$tmpl = array('table_open' => '<table class="setting_table table table-bordered table-striped">');
		$this -> table -> set_template($tmpl);
		$this -> table -> set_heading('','Minimum Weight' ,'Maximum Weight', 'Drug','Dose','Options');
		foreach ($classifications as $classification) {
			$links = "";
			$array_param = array('id' => $classification['id'], 'role' => 'button', 'class' => 'edit_user', 'data-toggle' => 'modal', 'name' => $classification['dose']);
			//$array_param = array('id' => $classification['id'], 'role' => 'button', 'class' => 'edit_user', 'data-toggle' => 'modal');
			if ($classification['is_active'] == 1) {
				$links .= anchor('#edit_form', 'Edit', $array_param);
			}
			//Check if user is an admin
			if ($access_level == "facility_administrator") {

				if ($classification['is_active'] == 1) {
					$links .= " | ";
					$links .= anchor('drugcode_classification/disable/' . $classification['id'], 'Disable', array('class' => 'disable_user'));
				} else {
					$links .= anchor('drugcode_classification/enable/' . $classification['id'], 'Enable', array('class' => 'enable_user'));
				}
			}

			$this -> table -> add_row(
				$classification['min_weight'], 
				$classification['min_weight'], 
				$classification['max_weight'], 
				$classification['drug'], 
				$classification['dose'], $links);
		}
		$data['classifications'] = $this -> table -> generate();
		$this -> base_params($data);
	}
	//function to select all pediatric drugs 
	public function get_drugs(){
		$sql="select d.id,d.drug
			  from drugcode d 
			  inner join regimen_drug rd on d.id=rd.drugcode
			  WHERE rd.regimen=5 or rd.regimen=6 or rd.regimen=7 or rd.regimen=9 or rd.regimen=10 
			  or rd.regimen=13 or rd.regimen=14";
		$query = $this -> db -> query($sql);
		$data = $query -> result_array();
		echo json_encode($data);

	}
	//function to get doses from dose table 
	public function get_dose(){
		$sql="select Name as dose
			  from dose";
		$query = $this -> db -> query($sql);
		$data = $query -> result_array();
		echo json_encode($data);

	}
	//save dossing infotmation to dossing chart database 
	public function save() {
		//call validation function
		$valid = $this -> _submit_validate();
		if ($valid == false) {
			$data['settings_view'] = "dossing_chart_v";
			$this -> base_params($data);
		} 
		else {
			$drugs=$this -> input -> post("drug");
			foreach ($drugs as $drug) {
			  $data = array(
				'min_weight' => $this -> input -> post("min_weight"),
				'max_weight' => $this -> input -> post("max_weight"),
				'drug_id'	 => $drug,
				'dose_id' 	 => $this -> input -> post("dose")
			  );
			 $result=$this->db->insert('dossing_chart',$data);
	
			}
			if($result){
				$this -> session -> set_userdata('msg_success', ' Item was Added');
			}
			else
			{
				$this -> session -> set_userdata('msg_success', ' Item was not Added');
			}
			
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
		//validation rules
		$this->form_validation->set_rules('min_weight', 'Mimimum Weight', 'required');
		$this->form_validation->set_rules('max_weight', 'Maximium Weight', 'required');
		$this->form_validation->set_rules('dose', 'Dosage', 'required');
		$this->form_validation->set_rules('drug', 'Drug', 'required');
		return $this -> form_validation -> run();
	}

	public function base_params($data) {
		//$data['quick_link'] = "indications";
		$this -> load -> view('dossing_chart_v', $data);
	}
}
?>