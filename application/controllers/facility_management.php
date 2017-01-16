<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Facility_Management extends MY_Controller {
	function __construct() {
		parent::__construct();
		$this -> session -> set_userdata("link_id", "index");
		$this -> session -> set_userdata("linkSub", "facility_management");
		$this -> session -> set_userdata("linkTitle", "Facility Details Management");
	}

	public function index() {
		$this -> listing();
	}

	public function listing() {
		$access_level = $this -> session -> userdata('user_indicator');
		$data['access_level'] = $access_level;
		$data['sites'] = Facilities::getFacilities();
		$data['supporter'] = Supporter::getAll();
		//get satellites
		//$data['satellites'] = Facilities::getSatellites($this -> session -> userdata("facility"));
		$district_query = $this -> db -> query("select * from district");
		$data['districts'] = $district_query -> result_array();
		$county_query = $this -> db -> query("select * from counties");
		$data['counties'] = $county_query -> result_array();
		$facility_type_query = $this -> db -> query("select * from facility_types");
		$data['facility_types'] = $facility_type_query -> result_array();
		$data['title'] = "Facility Information";
		$data['banner_text'] = "Facility Information";
		$data['link'] = "facility";
		$actions = array(0 => array('Edit', 'edit'), 1 => array('Disable', 'disable'));
		$data['actions'] = $actions;
		$this -> base_params($data);
	}

	public function view() {
		$access_level = $this -> session -> userdata('user_indicator');
		$source = $this -> input -> post('id');
		$data['facilities'] = Facilities::getCurrentFacility($source);
		echo json_encode($data);
	}

	public function getFacilityList() {
		$facilities = Facilities::getAll();
		echo json_encode($facilities);
	}

	public function getCurrent() {
		$source = $this -> session -> userdata('facility');
		$facilities = Facilities::getCurrentFacility($source);
		echo json_encode($facilities);
	}

	public function update() {
		$art_service = 0;
		$pmtct_service = 0;
		$pep_service = 0;

		if ($this -> input -> post('art_service') == "on") {
			$art_service = 1;
		}
		if ($this -> input -> post('pmtct_service') == "on") {
			$pmtct_service = 1;
		}
		if ($this -> input -> post('pep_service') == "on") {
			$pep_service = 1;
		}

		$facility_id = $this -> input -> post('facility_id');
		if ($facility_id) {
			$data = array(
				      'facilitycode' => $this -> input -> post('facility_cod'),
				      'name' => $this -> input -> post('facility_name'), 
				      'adult_age' => $this -> input -> post('adult_age'), 
				      'facilitytype' => $this -> input -> post('facility_type'), 
				      'district' => $this -> input -> post('district'), 
				      'county' => $this -> input -> post('county'), 
				      'weekday_max' => $this -> input -> post('weekday_max'), 
				      'weekend_max' => $this -> input -> post('weekend_max'),
				      'supported_by' => $this -> input -> post('supported_by'),
				      'phone' => $this -> input -> post('phone_number'), 
				      'service_art' => $art_service, 
				      'service_pmtct' => $pmtct_service, 
				      'service_pep' => $pep_service, 
				      'supplied_by' => $this -> input -> post('supplied_by'), 
				      'parent' => $this -> input -> post('central_site'),
				      'map'=>$this->input->post("sms_map", TRUE),
				      'lost_to_follow_up' => $this -> input -> post('lost_to_follow_up'), 
				    );
			$this -> db -> where('id', $facility_id);
			$this -> db -> update('facilities', $data);
			$this->session->set_userdata("facility_sms_consent",$this->input->post("sms_map", TRUE));
			$this -> session -> set_userdata('msg_success', $this -> input -> post('facility_name') . ' \'s details were successfully Updated!');
		} else {
			$this -> session -> set_userdata('msg_error', 'Facility details could not be updated!');
		}
		redirect('settings_management');
	}

	public function base_params($data) {
		$source = $this -> session -> userdata('facility');
		$access_level = $this -> session -> userdata('user_indicator');
		$data['quick_link'] = "facility";
		if ($access_level == "system_administrator") {
			$data['facilities_list'] = Facilities::getAll($source);
			$this -> load -> view("facility_v", $data);		} 
		else {
			$sql="SELECT * FROM Facilities where facilitycode='$source'";
   			$query = $this -> db -> query($sql);

			$facilities = $query -> result_array();
			$data['facilities'] = Facilities::getCurrentFacility($source);
			$this -> load -> view("facility_user_v", $data);

		}
	}

}
