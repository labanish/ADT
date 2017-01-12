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
					$links .= anchor('dossing_chart/disable/' . $classification['id'], 'Disable', array('class' => 'disable_user'));
				} else {
					$links .= anchor('dossing_chart/enable/' . $classification['id'], 'Enable', array('class' => 'enable_user'));
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
			  inner join regimen r on r.id = rd.regimen
			  WHERE (r.regimen_code LIKE '%CF%'
			  OR r.regimen_code LIKE '%PC%'
			  OR r.regimen_code LIKE '%CS%'
			  OR r.regimen_code LIKE '%CT%'
			  OR r.regimen_code LIKE '%OC%')
			  AND rd.active = '1'
			  GROUP BY d.id";
		$query = $this -> db -> query($sql);
		$data = $query -> result_array();
		echo json_encode($data);

	}
	//function to get doses from dose table 
	public function get_dose(){
		$sql="select id, Name
			  from dose
			  where Active = '1'";
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
	//function to get data for edit view
	public function edit(){
		$id = $this -> input -> post('id');
		$sql="select d.id,min_weight,max_weight,do.Name,dc.drug from dossing_chart d
			  inner join drugcode dc on dc.id=d.drug_id
			  inner join dose do on do.id=d.dose_id
			  where d.id='$id'";
		$query = $this -> db -> query($sql);
		$data = $query -> result_array();
		echo json_encode($data);
	}
	//update records
	public function update() {
		$id = $this -> input -> post('idno');
		$min_weight = $this -> input -> post('min_weights');
		$max_weight = $this -> input -> post('max_weights');
		$drug_id = $this -> input -> post('drugs');
		$dose_id = $this -> input -> post('doses');
		$query = $this -> db -> query("UPDATE dossing_chart SET 
									    min_weight='$min_weight',
									    max_weight='$max_weight',
									    drug_id='$drug_id',
									    dose_id='$dose_id'
									    WHERE id='$id'");
	
		$this -> session -> set_userdata('msg_success','Update Was Successfull');
		//Filter datatable
		redirect("settings_management");
	}
	

	public function enable($classification_id) {
		$query = $this -> db -> query("UPDATE dossing_chart SET is_active='1' WHERE id='$classification_id'");
		$this -> session -> set_userdata('msg_success','Item was enabled');
		//Filter datatable
		redirect("settings_management");
	}

	public function disable($classification_id) {
		$query = $this -> db -> query("UPDATE dossing_chart SET is_active='0' WHERE id='$classification_id'");
		$this -> session -> set_userdata('msg_error','Item was disabled');
		//Filter datatable
		redirect("settings_management");
	}
	private function _submit_validate() {
		$this->form_validation->set_rules('min_weight', 'Mimimum Weight', 'required');
		$this->form_validation->set_rules('max_weight', 'Maximium Weight', 'required');
		$this->form_validation->set_rules('dose', 'Dosage', 'required');
		$this->form_validation->set_rules('drug', 'Drug', 'required');
		return $this -> form_validation -> run();
	}

	public function base_params($data) {
		$this -> load -> view('dossing_chart_v', $data);
	}
}
?>