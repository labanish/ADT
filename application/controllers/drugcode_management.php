<?php
class Drugcode_management extends MY_Controller {
	function __construct() {
		parent::__construct();
		$this -> session -> set_userdata("link_id", "index");
		$this -> session -> set_userdata("linkSub", "drugcode_management");
		$this -> session -> set_userdata("linkTitle", "DrugCode Management");
		ini_set("max_execution_time", "100000");
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
		$drugcodes = Drugcode::getAll($source, $access_level);
		$tmpl = array('table_open' => '<table id="drugcode_setting" class="setting_table table table-bordered table-striped">');
		$this -> table -> set_template($tmpl);
		$this -> table -> set_heading('id', 'Drug', 'Unit', 'Dose', 'Supplier', 'Options');

		foreach ($drugcodes as $drugcode) {
			$array_param = array('id' => $drugcode['id'], 'role' => 'button', 'class' => 'edit_user', 'data-toggle' => 'modal');

			$links = "";
			if ($drugcode['Enabled'] == 1) {
				$links .= anchor('#edit_drugcode', 'Edit', $array_param);
			}

			$drug = $drugcode['id'];
			if ($drugcode['Enabled'] == 1 && $access_level == "facility_administrator") {
				$links .= " | ";
				$links .= anchor('drugcode_management/disable/' . $drugcode['id'], 'Disable', array('class' => 'disable_user'));
				$links .= " | ";
				$links .= "<a href='#' class='merge_drug' id='$drug'>Merge</a>";
			} elseif ($access_level == "facility_administrator") {
				$links .= anchor('drugcode_management/enable/' . $drugcode['id'], 'Enable', array('class' => 'enable_user'));

			}
			if ($drugcode['Merged_To'] != '') {
				if ($access_level == "facility_administrator") {
					$links .= " | ";
					$links .= anchor('drugcode_management/unmerge/' . $drugcode['id'], 'Unmerge', array('class' => 'unmerge_drug'));
				}
				$checkbox = "<input type='checkbox' name='drugcodes' id='drugcodes' class='drugcodes' value='$drug' disabled/>";
			} else {
				$checkbox = "<input type='checkbox' name='drugcodes' id='drugcodes' class='drugcodes' value='$drug'/>";
			}
			$mapped = "";
			if ($drugcode['map'] != 0) {
				$mapped = "<b>(mapped)</b>";
			}

			$this -> table -> add_row($drugcode['id'], $checkbox . "&nbsp;" . strtoupper($drugcode['Drug']) . " " . $mapped, "<b>" . $drugcode['drug_unit'] . "</b>", "<b>" . $drugcode['Dose'] . "</b>", "<b>" . $drugcode['supplier'] . "</b>", $links);
		}

		$data['drugcodes'] = $this -> table -> generate();
		$data['suppliers'] = Drug_Source::getAllHydrated();
		$data['classifications'] = Drug_Classification::getAllHydrated($access_level, "0");
		$query = $this -> db -> query("SELECT s.id,CONCAT_WS('] ',CONCAT_WS(' [',s.name,s.abbreviation),CONCAT_WS(' | ',s.strength,s.formulation)) as name,s.packsize
                                       FROM sync_drug s 
                                       WHERE s.id NOT IN(SELECT dc.map
                                                         FROM drugcode dc
                                                         WHERE dc.map !='0')
                                       AND (s.category_id='1' or s.category_id='2' or s.category_id='3' or s.category_id='4')
                                       AND s.active='1'
                                       ORDER BY name asc");

		$data['edit_mappings'] = $query -> result_array();
		$data['mappings'] = Sync_Drug::getOrderedActive();
        $data['instructions']=  Drug_instructions::getAllInstructions();
		$this -> base_params($data);
	}

	public function add() {
		$data = array();
		$data['drug_units'] = Drug_Unit::getThemAll();
		$data['generic_names'] = Generic_Name::getAllActive();
		$data['supporters'] = Supporter::getAllActive();
		$data['doses'] = Dose::getAllActive();
		echo json_encode($data);
	}

	public function save() {

		$valid = $this -> _submit_validate();
		$access_level = $this -> session -> userdata('user_indicator');
		$source = 0;
		if ($access_level == "pharmacist") {
			$source = $this -> session -> userdata('facility');
		}
		$non_arv = 0;
		$tb_drug = 0;
		$drug_in_use = 0;
		$supplied = 0;
		if ($this -> input -> post('none_arv') == "on") {
			$non_arv = 1;
		}
		if ($this -> input -> post('tb_drug') == "on") {
			$tb_drug = 1;
		}
		if ($this -> input -> post('drug_in_use') == "on") {
			$drug_in_use = 1;
		}

		//get drug instructions
		$instructions = $this -> input -> post('instructions_holder', TRUE);
		if ($instructions == null) {
			$instructions = "";
		}

		$drugcode = new Drugcode();
		$drugcode -> Drug = $this -> input -> post('drugname');
		$drugcode -> Unit = $this -> input -> post('drugunit');
		$drugcode -> Pack_Size = $this -> input -> post('packsize');
		$drugcode -> Safety_Quantity = $this -> input -> post('safety_quantity');
		$drugcode -> Generic_Name = $this -> input -> post('genericname');
		$drugcode -> Supported_By = $this -> input -> post('supplied_by');
		$drugcode -> classification = $this -> input -> post('classification');
		$drugcode -> none_arv = $non_arv;
		$drugcode -> Tb_Drug = $tb_drug;
		$drugcode -> Drug_In_Use = $drug_in_use;
		$drugcode -> Comment = $this -> input -> post('comments');
		$drugcode -> Dose = $this -> input -> post('dose_frequency');
		$drugcode -> Duration = $this -> input -> post('duration');
		$drugcode -> Quantity = $this -> input -> post('quantity');
		$drugcode -> Strength = $this -> input -> post('dose_strength');
		$drugcode -> map = $this -> input -> post('drug_mapping');
		$drugcode -> Source = $source;
		$drugcode -> instructions = $instructions;

		$drugcode -> save();
		//$this -> session -> set_userdata('message_counter', '1');
		$this -> session -> set_userdata('msg_success', $this -> input -> post('drugname') . ' was successfully Added!');
		$this -> session -> set_flashdata('filter_datatable', $this -> input -> post('drugname'));
		//Filter after saving
		redirect('settings_management');
	}

	//}

	public function edit() {
		$drugcode_id = $this -> input -> post('drugcode_id');
		$data['generic_names'] = Generic_Name::getAllActive();
		$data['drug_units'] = Drug_Unit::getThemAll();
		$data['doses'] = Dose::getAllActive();
		$data['supporters'] = Supporter::getAllActive();
		$data['doses'] = Dose::getAllActive();
		$data['drugcodes'] = Drugcode::getDrugCodeHydrated($drugcode_id);
		echo json_encode($data);
	}

	public function update() {
		$non_arv = "0";
		$tb_drug = "0";
		$drug_in_use = "0";
		$supplied = 0;
		if ($this -> input -> post('none_arv') == "on") {
			$non_arv = "1";
		}
		if ($this -> input -> post('tb_drug') == "on") {

			$tb_drug = "1";
		}
		if ($this -> input -> post('drug_in_use') == "on") {
			$drug_in_use = "1";
		}

		$source_id = $this -> input -> post('drugcode_id');
		//get drug instructions
		$instructions = $this -> input -> post('instructions_holder', TRUE);
		if ($instructions == null) {
			$instructions = "";
		}

		$data = array('Drug' => $this -> input -> post('drugname'), 'Unit' => $this -> input -> post('drugunit'), 'Pack_Size' => $this -> input -> post('packsize'), 'Safety_Quantity' => $this -> input -> post('safety_quantity'), 'Generic_Name' => $this -> input -> post('genericname'), 'Supported_By' => $this -> input -> post('supplied_by'), 'classification' => $this -> input -> post('classification'), 'none_arv' => $non_arv, 'tb_drug' => $tb_drug, 'Drug_In_Use' => $drug_in_use, 'Comment' => $this -> input -> post('comments'), 'Dose' => $this -> input -> post('dose_frequency'), 'Duration' => $this -> input -> post('duration'), 'Quantity' => $this -> input -> post('quantity'), 'Strength' => $this -> input -> post('dose_strength'), 'map' => $this -> input -> post('drug_mapping'),'instructions' => $instructions);

		$this -> db -> where('id', $source_id);
		$this -> db -> update('drugcode', $data);
		//$this -> session -> set_userdata('message_counter', '1');
		$this -> session -> set_userdata('msg_success', $this -> input -> post('drugname') . ' was Updated');
		$this -> session -> set_flashdata('filter_datatable', $this -> input -> post('drugname'));
		//Filter after saving
		redirect('settings_management');
	}

	public function enable($drugcode_id) {
		
		if($this ->input ->post('multiple')){
			//Handle the array with all drugcodes that are to be merged
			$drugcodes = $this -> input -> post('drug_codes');
			$drugcodes_to_disable = implode(",", $drugcodes);
			$the_query = "UPDATE drugcode SET enabled='1' WHERE id IN($drugcodes_to_disable);";
			if($this -> db -> query($the_query)){
				$this -> session -> set_userdata('msg_success','The selected drugs were successfully enabled!');
			}else{
				$this -> session -> set_userdata('msg_error', 'One or more of the selected drugs were not enabled!');
			}
		}else{
			$query = $this -> db -> query("UPDATE drugcode SET Enabled='1'WHERE id='$drugcode_id'");
			$results = Drugcode::getDrugCode($drugcode_id);
			//$this -> session -> set_userdata('message_counter', '1');
			$this -> session -> set_userdata('msg_success', $results['Drug'] . ' was enabled!');
			$this -> session -> set_flashdata('filter_datatable', $results['Drug']);
			//Filter
			redirect('settings_management');
		}
	}

	public function disable($drugcode_id) {
		if($this ->input ->post('multiple')){
			//Handle the array with all drugcodes that are to be merged
			$drugcodes = $this -> input -> post('drug_codes');
			$drugcodes_to_disable = implode(",", $drugcodes);
			$the_query = "UPDATE drugcode SET enabled='0' WHERE id IN($drugcodes_to_disable);";
			if($this -> db -> query($the_query)){
				$this -> session -> set_userdata('msg_success','The selected drugs were successfully disabled!');
			}else{
				$this -> session -> set_userdata('msg_error', 'One or more of the selected drugs were not disabled!');
			}
		}else{
			$query = $this -> db -> query("UPDATE drugcode SET Enabled='0'WHERE id='$drugcode_id'");
			$results = Drugcode::getDrugCode($drugcode_id);
			$this -> session -> set_userdata('message_counter', '2');
			$this -> session -> set_userdata('msg_success', $results['Drug'] . ' was disabled!');
			$this -> session -> set_flashdata('filter_datatable', $results['Drug']);
			//Filter
			redirect('settings_management');
		}
		
		
	}

	public function merge($primary_drugcode_id) {
		//Handle the array with all drugcodes that are to be merged
		$drugcodes = $this -> input -> post('drug_codes');
		$drugcodes = array_diff($drugcodes, array($primary_drugcode_id));
		$drugcodes_to_remove = implode(",", $drugcodes);

		//First Query that disables the drug_codes that are to be merged
		$the_query = "UPDATE drugcode SET enabled='0',merged_to='$primary_drugcode_id' WHERE id IN($drugcodes_to_remove);";
		$this -> db -> query($the_query);
		//Second Query that updates drug_stock_movement table to merge all drug id's in transactions that have the drugcodes that are to be merged with the primary_drugcode_id
		$the_query = "UPDATE drug_stock_movement SET merged_from=drug,drug='$primary_drugcode_id' WHERE drug IN($drugcodes_to_remove);";
		$this -> db -> query($the_query);
		//Third Query that updates patient_visit table for all transactions involving the drugcode to be merged with the primary_drugcode_id
		$the_query = "UPDATE patient_visit SET merged_from=drug_id,drug_id='$primary_drugcode_id' WHERE drug_id IN($drugcodes_to_remove);";
		$this -> db -> query($the_query);
		//Final Query that updates regimen_drug table for all regimens involving the drugcode to be merged with the primary_drugcode_id
		$the_query = "UPDATE regimen_drug SET merged_from=drugcode,drugcode='$primary_drugcode_id' WHERE drugcode IN($drugcodes_to_remove);";
		$this -> db -> query($the_query);
		$results = Drugcode::getDrugCode($primary_drugcode_id);
		$this -> session -> set_userdata('message_counter', '1');
		$this -> session -> set_userdata('msg_success', $results -> Drug . ' was Merged!');
	}

	public function unmerge($drugcode) {
		$this -> load -> database();
		//First Query that umerges the drug_code
		$the_query = "UPDATE drugcode SET merged_to='' WHERE id='$drugcode';";
		$this -> db -> query($the_query);
		//Second Query that updates drug_stock_movement table to unmerge all drug id's that match the merged_from column
		$the_query = "UPDATE drug_stock_movement SET drug='$drugcode',merged_from='' WHERE merged_from='$drugcode';";
		$this -> db -> query($the_query);
		//Third Query that updates patient_visit table to unmerge all drug id's that match the merged_from column
		$the_query = "UPDATE patient_visit SET drug_id='$drugcode',merged_from='' WHERE merged_from='$drugcode';";
		$this -> db -> query($the_query);
		//Final Query that updates regimen_drug table to unmerge all drug id's that match the merged_from column
		$the_query = "UPDATE regimen_drug SET drugcode='$drugcode',merged_from='' WHERE merged_from='$drugcode';";
		$this -> db -> query($the_query);

		$results = Drugcode::getDrugCode($drugcode);
		$this -> session -> set_userdata('message_counter', '1');
		$this -> session -> set_userdata('msg_error', $results -> Drug . ' was unmerged!');
		redirect('settings_management');

	}

	private function _submit_validate() {
		// validation rules
		$this -> form_validation -> set_rules('drugname', 'Drug Name', 'trim|required|min_length[2]|max_length[100]');
		$this -> form_validation -> set_rules('packsize', 'Pack Size', 'trim|required|min_length[2]|max_length[10]');

		return $this -> form_validation -> run();
	}
	
	public function getNonMappedDrugs($param='0'){
		$data = array();
		$query = $this -> db -> query("SELECT s.id,CONCAT_WS('] ',CONCAT_WS(' [',s.name,s.abbreviation),CONCAT_WS(' | ',s.strength,s.formulation)) as name,s.packsize
                                       FROM sync_drug s 
                                       WHERE s.id NOT IN(SELECT dc.map
                                                         FROM drugcode dc
                                                         WHERE dc.map !='0')
                                       AND (s.category_id='1' or s.category_id='2' or s.category_id='3' or s.category_id='4')
                                       AND s.active = '1'
                                       ORDER BY name asc");
		$data['sync_drugs'] = $query -> result_array();
		if($param==1){
			echo json_encode($data['sync_drugs']);
			die();
		}
		
		$data['non_mapped_drugs'] = Drugcode::getNonMappedDrugs();//Not mapped regimens
		echo json_encode($data);
	}

	public function updateBulkMapping(){
		$drug_id = $this ->input ->post("drug_id");
		$map_id = $this ->input ->post("map_id");
		
		$query = $this ->db ->query("UPDATE drugcode SET map = '$map_id' WHERE id = '$drug_id'");
		$aff = $this->db->affected_rows();
		echo $aff;
		
	}

	public function base_params($data) {
		$data['styles'] = array("jquery-ui.css");
		$data['scripts'] = array("jquery-ui.js");
		$data['quick_link'] = "drugcode";
		$data['title'] = "Drug Code";
		$data['banner_text'] = "Drug Code Management";
		$data['link'] = "settings_management";
		$this -> load -> view('drugcode_listing_v', $data);
	}

}
?>