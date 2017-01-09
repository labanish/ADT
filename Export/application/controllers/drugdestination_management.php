<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Drugdestination_Management extends MY_Controller {
	function __construct() {
		parent::__construct();
		$this->session->set_userdata("link_id","index");
		$this->session->set_userdata("linkSub","drugdestination_management");
		$this->session->set_userdata("linkTitle","Drug Destination Management");
	}

	public function index() {
		$this -> listing();
	}

	public function listing() {
		$access_level = $this -> session -> userdata('user_indicator');
		$sources = Drug_Destination::getThemAll($access_level);
		$tmpl = array ( 'table_open'  => '<table class="setting_table table table-bordered table-striped">'  );
		$this -> table ->set_template($tmpl);
		$this -> table -> set_heading('Id', 'Name','Options');

		foreach ($sources as $source) {
			$array_param=array(
				'id'=>$source->id,
				'role'=>'button',
				'class'=>'edit_user',
				'data-toggle'=>'modal',
				'name'=>$source->Name
			);
			$links="";
			if($source->Active==1){
				//$links = anchor('drugdestination_management/edit/' .$source->id, 'Edit',array('class' => 'edit_user','id'=>$source->id,'name'=>$source->Name));
				$links .= anchor('#edit_form', 'Edit', $array_param);
			}
			if($access_level=="facility_administrator"){
				
				if($source->Active==1){
				$links.=" | ";
				$links .= anchor('drugdestination_management/disable/' .$source->id, 'Disable',array('class' => 'disable_user'));	
				}else{
				$links .= anchor('drugdestination_management/enable/' .$source->id, 'Enable',array('class' => 'enable_user'));	
				}
			}
			$this -> table -> add_row($source->id, $source->Name,$links);
		}

		$data['sources'] = $this -> table -> generate();
		$data['title'] = "Drug Destinations";
		$data['banner_text'] = "Drug Destinations";
		$data['link'] = "drugdestinations";
		$actions = array(0 => array('Edit', 'edit'), 1 => array('Disable', 'disable'));
		$data['actions'] = $actions;
		$this -> base_params($data);
	}

	public function save() {
		$creator_id = $this -> session -> userdata('user_id');
		$source = $this -> session -> userdata('facility');
		
		$is_mainpharmacy = $this -> input -> post('pharmacy_check');
		$destination = $this -> input -> post('source_name');
		if($is_mainpharmacy==1){
			$destination.=' (Main Pharmacy)';
		}

		$source = new Drug_Destination();
		$source -> Name = $destination;
		$source -> Active = "1";
		$source -> save();
		
		//$this -> session -> set_userdata('message_counter','1');
		$this -> session -> set_userdata('msg_success',$this -> input -> post('source_name').' was successfully Added!');
		$this -> session -> set_flashdata('filter_datatable',$this -> input -> post('source_name'));//Filter datatable
		redirect('settings_management');
	}

	public function edit($source_id) {
		$data['title'] = "Edit Drug Destinations";
		$data['settings_view'] = "editdrugdestinations_v";
		$data['banner_text'] = "Edit Drug Destinations";
		$data['link'] = "drugdestination";
		$data['sources'] = Drug_Destination::getSource($source_id);
		$this -> base_params($data);
	}

	public function update() {
		$source_id = $this -> input -> post('source_id');
		
		$is_mainpharmacy = $this -> input -> post('pharmacy_check');
		$destination = $this -> input -> post('source_name');
		$pos = stripos($destination, 'main pharmacy');
		
		
		if($is_mainpharmacy==1){
			if($pos===10){//If name already has main pharmacy in it, don't append
				
			}else{
				$destination.=' (Main Pharmacy)';
			}
			
		}

		$this -> load -> database();
		$query = $this -> db -> query("UPDATE drug_destination SET Name='$destination' WHERE id='$source_id'");
		//$this -> session -> set_userdata('message_counter','1');
		$this -> session -> set_userdata('msg_success',$this -> input -> post('source_name').' was Updated!');
		$this -> session -> set_flashdata('filter_datatable',$this -> input -> post('source_name'));//Filter datatable
		redirect('settings_management');
	}

	public function enable($source_id) {
		$this -> load -> database();
		$query = $this -> db -> query("UPDATE drug_destination SET Active='1'WHERE id='$source_id'");
		$results=Drug_Destination::getSource($source_id);
		//$this -> session -> set_userdata('message_counter','1');
		$this -> session -> set_userdata('msg_success',$results->Name.' was enabled');
		$this -> session -> set_flashdata('filter_datatable',$results->Name);//Filter datatable
		redirect('settings_management');
	}

	public function disable($source_id) {
		$this -> load -> database();
		$query = $this -> db -> query("UPDATE drug_destination SET Active='0'WHERE id='$source_id'");
		$results=Drug_Destination::getSource($source_id);
		//$this -> session -> set_userdata('message_counter','2');
		$this -> session -> set_userdata('msg_error',$results->Name.' was disabled!');
		$this -> session -> set_flashdata('filter_datatable',$results->Name);//Filter datatable
		redirect('settings_management');
	}

	public function base_params($data) {
		$data['quick_link'] = "drug_destination";
		$this -> load -> view("drugdestination_v", $data);
	}

	

}
