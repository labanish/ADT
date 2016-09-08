<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Dose_Management extends MY_Controller {
	function __construct() {
		parent::__construct();
		$this->session->set_userdata("link_id","index");
		$this->session->set_userdata("linkSub","dose_management");
		$this->session->set_userdata("linkTitle","Drug Dose Management");
		
	}

	public function index() {
		$this -> listing();
	}

	public function listing() {
		$access_level = $this -> session -> userdata('user_indicator');
		$doses = Dose::getAll($access_level);
		$tmpl = array ( 'table_open'  => '<table class="setting_table table table-bordered table-striped">'  );
		$this -> table ->set_template($tmpl);
		$this -> table -> set_heading('id', 'Name','Value','Frequency','Options');

		foreach ($doses as $dose) {
			$links="";	
			if($dose->Active==1){
				$array_param=array(
					'id'=>$dose['id'],
					'role'=>'button',
					'class'=>'edit_user',
					'data-toggle'=>'modal'
				);
				//$links = anchor('','Edit',array('class' => 'edit_user','id'=>$dose->id,'name'=>$dose->Name));
				$links .= anchor('#edit_dose', 'Edit', $array_param);
			}
			if($access_level=="facility_administrator"){
				
				if($dose->Active==1){
					$links.=" | ";
					$links .= anchor('dose_management/disable/' .$dose->id, 'Disable',array('class' => 'disable_user'));	
				}else{
					$links .= anchor('dose_management/enable/' .$dose->id, 'Enable',array('class' => 'enable_user'));	
				}
			}
			
			
			$this -> table -> add_row($dose->id,$dose->Name,$dose->Value,$dose->Frequency,$links);
		}

		$data['doses'] = $this -> table -> generate();
		$data['title'] = "Drug Doses";
		$data['banner_text'] = "Drug Doses";
		$data['link'] = "dose";
		$actions = array(0 => array('Edit', 'edit'), 1 => array('Disable', 'disable'));
		$data['actions'] = $actions;
		$this -> base_params($data);
	}

	public function save() {
		$dose = new Dose();
		$dose -> Name = $this -> input -> post('dose_name');
		$dose -> Value = $this -> input -> post('dose_value');
		$dose -> Frequency = $this -> input -> post('dose_frequency');
		$dose -> Active = "1";
		$dose -> save();
		
		$this -> session -> set_userdata('message_counter','1');
		$this -> session -> set_userdata('msg_success',$this -> input -> post('dose_name').' was succesfully Added!');
		$this -> session -> set_flashdata('filter_datatable',$this -> input -> post('dose_name'));//Filter after saving
		redirect('settings_management');
	}

	public function edit() {
		$dose_id=$this->input->post("id");
		$data['doses'] = Dose::getDoseHydrated($dose_id);
		echo json_encode($data);
	}

	public function update() {
		$dose_id = $this -> input -> post('dose_id');
		$dose_name = $this -> input -> post('dose_name');
		$dose_value = $this -> input -> post('dose_value');
		$dose_frequency = $this -> input -> post('dose_frequency');

		$this -> load -> database();
		$query = $this -> db -> query("UPDATE dose SET Name='$dose_name',value='$dose_value',frequency='$dose_frequency' WHERE id='$dose_id'");
		$this -> session -> set_userdata('msg_success',$this -> input -> post('dose_name').' was Updated!');
		$this -> session -> set_flashdata('filter_datatable',$this -> input -> post('dose_name'));//Filter after saving
		redirect('settings_management');
	}

	public function enable($dose_id) {
		$this -> load -> database();
		$query = $this -> db -> query("UPDATE dose SET Active='1' WHERE id='$dose_id'");
		$results=Dose::getDose($dose_id);
		//$this -> session -> set_userdata('message_counter','1');
		$this -> session -> set_userdata('msg_success',$results->Name.' was enabled!');
		$this -> session -> set_flashdata('filter_datatable',$results->Name);//Filter
		redirect('settings_management');
	}

	public function disable($dose_id) {
		$this -> load -> database();
		$query = $this -> db -> query("UPDATE dose SET Active='0' WHERE id='$dose_id'");
		$results=Dose::getDose($dose_id);
		//$this -> session -> set_userdata('message_counter','2');
		$this -> session -> set_userdata('msg_error',$results->Name.' was disabled!');
		$this -> session -> set_flashdata('filter_datatable',$results->Name);//Filter
		redirect('settings_management');
	}

	public function base_params($data) {
		$data['quick_link'] = "dose";
		$this -> load -> view("dose_v", $data);
	}

	

}
