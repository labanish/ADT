<?php
class genericname_management extends MY_Controller {

	//required
	function __construct() {
		parent::__construct();
		$this->session->set_userdata("link_id","index");
		$this->session->set_userdata("linkSub","genericname_management");
		$this->session->set_userdata("linkTitle","Generic Name Management");
	}

	public function index() {
		$this -> listing();
	}

	public function listing() {
		$access_level = $this -> session -> userdata('user_indicator');
		$data = array();
		$generics = Generic_Name::getAllHydrated($access_level);
		$tmpl = array ( 'table_open'  => '<table class="setting_table table table-bordered table-striped">'  );
		$this -> table ->set_template($tmpl);
		$this -> table -> set_heading('Id', 'Name', 'Options');
		foreach ($generics as $generic) {
			$links="";
			$array_param=array(
				'id'=>$generic['id'],
				'role'=>'button',
				'class'=>'edit_user',
				'data-toggle'=>'modal',
				'name'=>$generic['Name']
			);
			if ($generic['Active'] == 1) {
				//$links = anchor('genericname_management/edit/' . $generic['id'], 'Edit', array('class' => 'edit_user','id'=>$generic['id'],'name'=>$generic['Name']));
				$links .= anchor('#edit_form', 'Edit', $array_param);
			}
			//Check if user is an admin
			if($access_level=="facility_administrator"){
				
				if ($generic['Active'] == 1) {
					$links .= " | ";
					$links .= anchor('genericname_management/disable/' . $generic['id'], 'Disable', array('class' => 'disable_user'));
				} else {
					$links .= anchor('genericname_management/enable/' . $generic['id'], 'Enable', array('class' => 'enable_user'));
				}
			}
			
			$this -> table -> add_row($generic['id'], $generic['Name'], $links);
		}
		$data['generic_names'] = $this -> table -> generate();
		$this -> base_params($data);
	}

	public function save() {

		//call validation function
		$valid = $this -> _submit_validate();
		if ($valid == false) {
			$data['settings_view'] = "generic_listing_v";
			$this -> base_params($data);
		} else {
			$drugname = $this -> input -> post("generic_name");
			$generic_name = new Generic_Name();
			$generic_name -> Name = $drugname;
			$generic_name -> Active = "1";
			$generic_name -> save();
			$this -> session -> set_userdata('msg_success', $this -> input -> post('generic_name') . ' was Added');
			$this -> session -> set_flashdata('filter_datatable',$this -> input -> post('generic_name'));//Filter datatable
			redirect("settings_management");
		}

	}

	public function edit($generic_id) {
		$data['title'] = "Edit Generic Name";
		$data['settings_view'] = "editgeneric_v";
		$data['banner_text'] = "Edit Generic Name";
		$data['link'] = "generic";
		$data['generics'] = Generic_Name::getGeneric($generic_id);
		$this -> base_params($data);
	}

	public function update() {
		$generic_id = $this -> input -> post('generic_id');
		$generic_name = $this -> input -> post('edit_generic_name');

		$this -> load -> database();
		$query = $this -> db -> query("UPDATE generic_name SET Name='$generic_name' WHERE id='$generic_id'");
		$this -> session -> set_userdata('msg_success', $this -> input -> post('edit_generic_name') . ' was Updated');
		$this -> session -> set_flashdata('filter_datatable',$this -> input -> post('edit_generic_name'));//Filter datatable
		redirect("settings_management");
	}

	public function enable($generic_id) {
		$query = $this -> db -> query("UPDATE generic_name SET Active='1'WHERE id='$generic_id'");
		$results = Generic_Name::getGeneric($generic_id);
		$this -> session -> set_userdata('msg_success', $results -> Name . ' was enabled');
		$this -> session -> set_flashdata('filter_datatable',$results -> Name );//Filter datatable
		redirect("settings_management");
	}

	public function disable($generic_id) {
		$this -> load -> database();
		$query = $this -> db -> query("UPDATE generic_name SET Active='0'WHERE id='$generic_id'");
		$results = Generic_Name::getGeneric($generic_id);
		$this -> session -> set_userdata('msg_error', $results -> Name . ' was disabled');
		$this -> session -> set_flashdata('filter_datatable',$results -> Name );//Filter datatable
		redirect("settings_management");
	}

	private function _submit_validate() {
		// validation rules
		$this -> form_validation -> set_rules('generic_name', 'Generic Name', 'trim|required|min_length[2]|max_length[100]');

		return $this -> form_validation -> run();
	}

	public function base_params($data) {
		$data['styles'] = array("jquery-ui.css");
		$data['scripts'] = array("jquery-ui.js");
		$data['quick_link'] = "generic";
		$data['title'] = "Generic Names";
		$data['banner_text'] = "Generic Management";
		$data['link'] = "settings_management";

		$this -> load -> view('generic_listing_v', $data);
	}

}
