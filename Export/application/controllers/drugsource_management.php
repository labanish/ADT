<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Drugsource_Management extends MY_Controller {
	function __construct() {
		parent::__construct();
		$this->session->set_userdata("link_id","index");
		$this->session->set_userdata("linkSub","drugsource_management");
		$this->session->set_userdata("linkTitle","Drug Source Management");
	}

	public function index() {
		$this -> listing();
	}

	public function listing() {
		$access_level = $this -> session -> userdata('user_indicator');
		$sources = Drug_Source::getThemAll($access_level);
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
				//$links = anchor('drugsource_management/edit/' .$source->id, 'Edit',array('class' => 'edit_user','id'=>$source->id,'name'=>$source->Name));
				$links .= anchor('#edit_form', 'Edit', $array_param);
				
			}
			if($access_level=="facility_administrator"){
				
				if($source->Active==1){
				$links.=" | ";
				$links .= anchor('drugsource_management/disable/' .$source->id, 'Disable',array('class' => 'disable_user'));	
				}else{
				$links .= anchor('drugsource_management/enable/' .$source->id, 'Enable',array('class' => 'enable_user'));	
				}
			}
			$this -> table -> add_row($source->id, $source->Name,$links);
		}

		$data['sources'] = $this -> table -> generate();
		$data['title'] = "Drug Sources";
		$data['banner_text'] = "Drug Sources";
		$data['link'] = "drugsources";
		$actions = array(0 => array('Edit', 'edit'), 1 => array('Disable', 'disable'));
		$data['actions'] = $actions;
		$this -> base_params($data);
	}

	public function save() {
		$creator_id = $this -> session -> userdata('user_id');
		$source = $this -> session -> userdata('facility');

		$source = new Drug_Source();
		$source -> Name = $this -> input -> post('source_name');
		$source -> Active = "1";
		$source -> save();
		
		$this -> session -> set_userdata('message_counter','1');
		$this -> session -> set_userdata('msg_success',$this -> input -> post('source_name').' was successfully Added!');
		$this -> session -> set_flashdata('filter_datatable',$this -> input -> post('source_name'));//Filter datatable
		redirect('settings_management');
	}

	public function edit($source_id) {
		$data['title'] = "Edit Drug Sources";
		$data['settings_view'] = "editdrugsources_v";
		$data['banner_text'] = "Edit Drug Sources";
		$data['link'] = "drugsources";
		$data['sources'] = Drug_Source::getSource($source_id);
		$this -> base_params($data);
	}

	public function update() {
		$source_id = $this -> input -> post('source_id');
		$source_name = $this -> input -> post('source_name');
		

		$this -> load -> database();
		$query = $this -> db -> query("UPDATE drug_source SET Name='$source_name' WHERE id='$source_id'");
		//$this -> session -> set_userdata('message_counter','1');
		$this -> session -> set_userdata('msg_success',$this -> input -> post('source_name').' was Updated!');
		$this -> session -> set_flashdata('filter_datatable',$this -> input -> post('source_name'));//Filter datatable
		redirect('settings_management');
	}

	public function enable($source_id) {
		$this -> load -> database();
		$query = $this -> db -> query("UPDATE drug_source SET Active='1'WHERE id='$source_id'");
		$results=Drug_Source::getSource($source_id);
		//$this -> session -> set_userdata('message_counter','1');
		$this -> session -> set_userdata('msg_success',$results->Name.' was enabled!');
		$this -> session -> set_flashdata('filter_datatable',$results->Name);//Filter datatable
		redirect('settings_management');
	}

	public function disable($source_id) {
		$this -> load -> database();
		$query = $this -> db -> query("UPDATE drug_source SET Active='0'WHERE id='$source_id'");
		$results=Drug_Source::getSource($source_id);
		//$this -> session -> set_userdata('message_counter','2');
		$this -> session -> set_userdata('msg_error',$results->Name.' was disabled!');
		$this -> session -> set_flashdata('filter_datatable',$results->Name);//Filter datatable
		redirect('settings_management');
	}

	public function base_params($data) {
		$data['quick_link'] = "drug_sources";
		$this -> load -> view("drugsources_v", $data);
	}

	

}
