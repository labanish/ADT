<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Indication_Management extends MY_Controller {
	function __construct() {
		parent::__construct();
		$this->session->set_userdata("link_id","index");
		$this->session->set_userdata("linkSub","indication_management");
		$this->session->set_userdata("linkTitle","Drug Indication Management");
	}

	public function index() {
		$this -> listing();
	}

	public function listing() {
		$access_level = $this -> session -> userdata('user_indicator');
		$infections = Opportunistic_Infection::getThemAll($access_level);
		$tmpl = array ( 'table_open'  => '<table class="setting_table table table-bordered table-striped">'  );
		$this -> table ->set_template($tmpl);
		$this -> table -> set_heading('Id', 'Name','Options');

		foreach ($infections as $infection) {
			$links="";
			
			if($infection->Active==1){
				$array_param=array(
					'id'=>$infection->id,
					'role'=>'button',
					'class'=>'edit_user',
					'data-toggle'=>'modal',
					'name'=>$infection->Name,
					'title'=>$infection->Indication
				);
				//$links = anchor('indication_management/edit/' .$infection->id, 'Edit',array('class' => 'edit_user','id'=>$infection->id,'name'=>$infection->Name));
				$links .= anchor('#edit_form', 'Edit', $array_param);
			}
			if($access_level=="facility_administrator"){
				
				if($infection->Active==1){
				$links.=" | ";
				$links .= anchor('indication_management/disable/' .$infection->id, 'Disable',array('class' => 'disable_user'));	
				}else{
				$links .= anchor('indication_management/enable/' .$infection->id, 'Enable',array('class' => 'enable_user'));	
				}
			}
			$infection_temp="";
			if($infection->Name){
				$infection_temp=" | ".$infection->Name;
			}
			$this -> table -> add_row($infection->id,$infection->Indication.$infection_temp,$links);
		}

		$data['indications'] = $this -> table -> generate();
		$data['title'] = "Drug Indications";
		$data['banner_text'] = "Drug Indications";
		$data['link'] = "indications";
		$actions = array(0 => array('Edit', 'edit'), 1 => array('Disable', 'disable'));
		$data['actions'] = $actions;
		$this -> base_params($data);
	}

	public function save() {
		$creator_id = $this -> session -> userdata('user_id');
		$source = $this -> session -> userdata('facility');

		$indication = new Opportunistic_Infection();
		$indication -> Name = $this -> input -> post('indication_name');
		$indication -> Indication = $this -> input -> post('indication_code');
		$indication -> Active = "1";
		$indication -> save();
		
		//$this -> session -> set_userdata('message_counter','1');
		$this -> session -> set_userdata('msg_success',$this -> input -> post('indication_code').' was Added');
		$this -> session -> set_flashdata('filter_datatable',$this -> input -> post('indication_code'));//Filter datatable
		redirect('settings_management');
	}

	public function edit($indication_id) {
		$data['title'] = "Edit Drug Indications";
		$data['settings_view'] = "editindications_v";
		$data['banner_text'] = "Edit Drug Indications";
		$data['link'] = "indications";
		$data['indications'] = Opportunistic_Infection::getIndication($indication_id);
		$this -> base_params($data);
	}

	public function update() {
		$indication_id = $this -> input -> post('indication_id');
		$indication_name = $this -> input -> post('indication_name');
		$indication_code = $this -> input -> post('indication_code');
		

		$this -> load -> database();
		$query = $this -> db -> query("UPDATE opportunistic_infection SET Name='$indication_name',Indication='$indication_code' WHERE id='$indication_id'");
		//$this -> session -> set_userdata('message_counter','1');
		$this -> session -> set_userdata('msg_success',$this -> input -> post('indication_code').' was Updated');
		$this -> session -> set_flashdata('filter_datatable',$this -> input -> post('indication_code'));//Filter datatable
		redirect('settings_management');
	}

	public function enable($indication_id) {
		$this -> load -> database();
		$query = $this -> db -> query("UPDATE opportunistic_infection SET Active='1'WHERE id='$indication_id'");
		$results=Opportunistic_Infection::getIndication($indication_id);
		//$this -> session -> set_userdata('message_counter','1');
		$this -> session -> set_userdata('msg_success',$results->Indication.' was enabled');
		$this -> session -> set_flashdata('filter_datatable',$results->Indication);//Filter datatable
		redirect('settings_management');
	}

	public function disable($indication_id) {
		$this -> load -> database();
		$query = $this -> db -> query("UPDATE opportunistic_infection SET Active='0'WHERE id='$indication_id'");
		$results=Opportunistic_Infection::getIndication($indication_id);
		//$this -> session -> set_userdata('message_counter','2');
		$this -> session -> set_userdata('msg_error',$results->Indication.' was disabled');
		$this -> session -> set_flashdata('filter_datatable',$results->Indication);//Filter datatable
		redirect('settings_management');
	}

	public function base_params($data) {
		$data['quick_link'] = "indications";
		$this -> load -> view('indications_v', $data);
	}

	

}
