<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Nonadherence_Management extends MY_Controller {
	function __construct() {
		parent::__construct();
		$this->session->set_userdata("link_id","index");
		$this->session->set_userdata("linkSub","nonadherence_management");
		$this->session->set_userdata("linkTitle","Non Adherence Reason Management");
	}

	public function index() {
		$this -> listing();
	}

	public function listing() {
		$access_level = $this -> session -> userdata('user_indicator');
		$sources = Non_Adherence_Reasons::getThemAll($access_level);
		$tmpl = array ( 'table_open'  => '<table class="setting_table table table-bordered table-striped">'  );
		$this -> table ->set_template($tmpl);
		$this -> table -> set_heading('Id', 'Name','Options');

		foreach ($sources as $source) {
			$links="";
			$array_param=array(
				'id'=>$source->id,
				'role'=>'button',
				'class'=>'edit_user',
				'data-toggle'=>'modal',
				'name'=>$source->Name
			);
			if($source->Active==1){
				//$links = anchor('Nonadherence_Management/edit/' .$source->id, 'Edit',array('class' => 'edit_user','class' => 'edit_user','id'=> $source->id,'name'=>$source->Name));
				$links .= anchor('#edit_form', 'Edit', $array_param);
			}
			
			if($access_level=="facility_administrator" ){
				
				if($source->Active==1){
					$links.=" | ";
					$links .= anchor('Nonadherence_Management/disable/' .$source->id, 'Disable',array('class' => 'disable_user'));	
				}
				else{
					$links .= anchor('Nonadherence_Management/enable/' .$source->id, 'Enable',array('class' => 'enable_user'));	
				}
			}
			$this -> table -> add_row($source->id, $source->Name,$links);
		}

		$data['sources'] = $this -> table -> generate();;
		$data['title'] = "Non adherence change Reasons";
		$data['banner_text'] = "Non adherence change Reasons";
		$data['link'] = "Non_Adherence_Reasons";
		$actions = array(0 => array('Edit', 'edit'), 1 => array('Disable', 'disable'));
		$data['actions'] = $actions;
		$this -> base_params($data);
	}

	public function save() {
		$creator_id = $this -> session -> userdata('user_id');
		$source = $this -> session -> userdata('facility');

		$source = new Non_Adherence_Reasons();
		$source -> Name = $this -> input -> post('nonadherence_name');
		$source -> Active = "1";
		$source -> save();
		
		//$this -> session -> set_userdata('message_counter','1');
		$this -> session -> set_userdata('msg_success',$this -> input -> post('nonadherence_name').' was successfully Added!');
		$this -> session -> set_flashdata('filter_datatable',$this -> input -> post('nonadherence_name') );//Filter datatable
		redirect('settings_management');
	}

	public function edit($source_id) {
		$data['title'] = "Edit non adherence reasons";
		$data['settings_view'] = "editclient_v";
		$data['banner_text'] = "Edit non adherence reasons";
		$data['link'] = "nonadherence_reasons";
		$data['sources'] = Non_Adherence_Reasons::getSource($source_id);
		$this -> base_params($data);
	}

	public function update() {
		$nonadherence_id = $this -> input -> post('nonadherence_id');
		$nonadherence_name = $this -> input -> post('nonadherence_name');
		

		$this -> load -> database();
		$query = $this -> db -> query("UPDATE Non_Adherence_Reasons SET Name='$nonadherence_name' WHERE id='$nonadherence_id'");
		//$this -> session -> set_userdata('message_counter','1');
		$this -> session -> set_userdata('msg_success',$this -> input -> post('nonadherence_name').' was Updated!');
		$this -> session -> set_flashdata('filter_datatable',$this -> input -> post('nonadherence_name') );//Filter datatable
		redirect('settings_management');
	}

	public function enable($nonadherence_id) {
		$this -> load -> database();
		$query = $this -> db -> query("UPDATE Non_Adherence_Reasons SET Active='1'WHERE id='$nonadherence_id'");
		$results=Non_Adherence_Reasons::getSource($nonadherence_id);
		//$this -> session -> set_userdata('message_counter','1');
		$this -> session -> set_userdata('msg_success',$results->Name.' was enabled!');
		$this -> session -> set_flashdata('filter_datatable',$results->Name);//Filter datatable
		redirect('settings_management');
	}

	public function disable($nonadherence_id) {
		$this -> load -> database();
		$query = $this -> db -> query("UPDATE Non_Adherence_Reasons SET Active='0'WHERE id='$nonadherence_id'");
		$results=Non_Adherence_Reasons::getSource($nonadherence_id);
		//$this -> session -> set_userdata('message_counter','2');
		$this -> session -> set_userdata('msg_error',$results->Name.' was disabled!');
		$this -> session -> set_flashdata('filter_datatable',$results->Name);//Filter datatable
		redirect('settings_management');
	}

	public function base_params($data) {
		$data['quick_link'] = "non_adherence_reason";
		$this -> load -> view("nonadherence_listing_v", $data);
	}

	

}
