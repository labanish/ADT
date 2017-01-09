<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Fcdrr_Management extends MY_Controller {
	function __construct() {
		parent::__construct();
		$data = array();
		$this -> load -> library('PHPExcel');
		ini_set("max_execution_time", "10000");
	}

	public function index() {
		$data['settings_view'] = "fcdrr_upload";
		$this -> base_params($data);
	}

	public function data_upload() {
		if ($_POST['btn_save']) {

			$objReader = new PHPExcel_Reader_Excel2007();

			if ($_FILES['file']['tmp_name']) {
				$objPHPExcel = $objReader -> load($_FILES['file']['tmp_name']);

			} else {
				$this -> session -> set_userdata('upload_counter', '1');
				redirect("fcdrr_management/index");

			}

			$arr = $objPHPExcel -> getActiveSheet() -> toArray(null, true, true, true);
			$highestColumm = $objPHPExcel -> setActiveSheetIndex(0) -> getHighestColumn();
			$highestRow = $objPHPExcel -> setActiveSheetIndex(0) -> getHighestRow();

			//Top Details

			$facility_name = $arr[5]['B'] . $arr[5]['C'] . $arr[5]['D'] . $arr[5]['E'];
			$province = $arr[6]['B'] . $arr[6]['C'] . $arr[6]['D'] . $arr[6]['E'];
			$facility_code = $arr[5]['R'] . $arr[5]['S'] . $arr[5]['T'];
			$district = $arr[6]['R'] . $arr[6]['S'] . $arr[6]['T'];

			$type_of_service_art = $arr[8]['C'];
			$type_of_service_pmtct = $arr[8]['E'];
			$type_of_service_pep = $arr[8]['H'];

			if ($type_of_service_art && $type_of_service_pmtct && $type_of_service_pep) {
				$services_offered = "ART,PMTCT,PEP";
			} else if ($type_of_service_pmtct && $type_of_service_art) {
				$services_offered = "ART,PMTCT";
			} else if ($type_of_service_pep && $type_of_service_art) {
				$services_offered = "ART,PEP";
			} else if ($type_of_service_pmtct && $type_of_service_pep) {
				$services_offered = "PMTCT,PEP";
			} else {
				if ($type_of_service_art) {
					$services_offered = "ART";
				}
				if ($type_of_service_pmtct) {
					$services_offered = "PMTCT";
				}
				if ($type_of_service_pep) {
					$services_offered = "PEP";
				}
			}
			@$services_offered;

			$programme_sponsor_gok = $arr[4]['D'];
			$programme_sponsor_pepfar = $arr[4]['G'];
			$programme_sponsor_msf = $arr[4]['L'];

			$programme_sponsor = "";

			if ($programme_sponsor_gok) {
				$programme_sponsor = "GOK";
			}
			if ($programme_sponsor_pepfar) {
				$programme_sponsor = "PEPFAR";
			}
			if ($programme_sponsor_msf) {
				$programme_sponsor = "MSF";
			}

			$updated_on = date("U");

			//Reporting Period

			@$beginning = trim($arr[10]['D'] . $arr[10]['E']);
			@$ending = $arr[10]['R'] . $arr[10]['S'] . $arr[10]['T'];
			
			$start=explode("-",$beginning);
			$day=$start[0];
			$month=$start[1];
			$year=$start[2];
			$beginning="20".$year."-".$month."-".$day;
			$beginning = date('Y-m-d',strtotime($beginning));

			$ending = str_replace('/', '-', $ending);
			$old_ending = strtotime($ending);
			$ending = date('Y-m-d', $old_ending);

			$central_facility = $this -> session -> userdata('facility');
			$parent = Facilities::getParent($central_facility);
			$central_site = $parent -> parent;

			//Comments

			for ($i = 105; $i <= 109; $i++) {
				for ($j = 1; $j <= $highestColumm; $j++) {
				}
				@$comments .= $arr[$i]['A'] . $arr[$i]['B'] . $arr[$i]['C'] . $arr[$i]['D'] . $arr[$i]['E'] . $arr[$i]['G'] . $arr[$i]['H'] . $arr[$i]['L'];

			}
			$unique_id = 0;
			$this -> load -> database();
			$facility_order_query = $this -> db -> query("SELECT MAX(id) AS id FROM facility_order");
			$facility_order_results = $facility_order_query -> result_array();
			$facility_id = $facility_order_results[0]['id'];
			$order_number = $facility_id + 1;
			$unique_id = md5($order_number . $facility_code);
			
			$query = $this -> db -> query("INSERT INTO facility_order (`id`, `status`, `created`, `updated`, `code`, `period_begin`, `period_end`, `comments`, `reports_expected`, `reports_actual`, `services`, `sponsors`, `delivery_note`, `order_id`, `facility_id`,`central_facility`,`unique_id`) VALUES ('$order_number', '0', CURDATE(), '$updated_on', '2', '$beginning', '$ending', '$comments', NULL, NULL, '$services_offered', '$programme_sponsor', NULL, NULL, '$facility_code','$central_site','$unique_id');");
			$facility_id = $unique_id;
			$user_id = $this -> session -> userdata('full_name');

			$query = $this -> db -> query("SELECT MAX(id) AS id FROM order_comment");
			$results = $query -> result_array();
			$last_id = $results[0]['id'];
			$last_id = $last_id + 1;
			$last_id = md5($last_id . $facility_code);

			//Adding comments
			$order_comment = new Order_Comment();
			$order_comment -> Order_Number = $facility_id;
			$order_comment -> Timestamp = date('U');
			$order_comment -> User = $user_id;
			$order_comment -> Comment = $comments;
			$order_comment -> Unique_Id = $last_id;
			$order_comment -> save();

			//Adult ARV Preparations
			for ($i = 18; $i <= 42; $i++) {
				for ($j = 1; $j <= $highestColumm; $j++) {
				}
				$quantity_required_for_supply = $arr[$i]['L'];
				$drug_name = $arr[$i]['A'];
				if ($quantity_required_for_supply != 0) {
                    $drug_id = $drug_name;
					$basic_unit = $arr[$i]['B'];
					$beginning_balance = $arr[$i]['C'];
					$quantity_received_in_period = $arr[$i]['D'];
					$quantity_dispensed_in_period = $arr[$i]['E'];
					$adjustments_to_other_facilities = $arr[$i]['G'];
					$end_of_month_physical_count = $arr[$i]['H'];
					$quantity_required_for_supply = $arr[$i]['L'];
					$query = $this -> db -> query("SELECT MAX(id) AS id FROM cdrr_item");
					$results = $query -> result_array();
					$last_id = $results[0]['id'];
					$last_id++;
					$last_id = md5($last_id . $facility_code);
					$cdrr_query = $this -> db -> query("INSERT INTO cdrr_item (`id`, `balance`, `received`, `dispensed_units`, `dispensed_packs`, `losses`, `adjustments`, `count`, `resupply`, `aggr_consumed`, `aggr_on_hand`, `publish`, `cdrr_id`, `drug_id`,`unique_id`) VALUES (NULL, '$beginning_balance', '$quantity_received_in_period', '$quantity_dispensed_in_period', NULL, NULL, '$adjustments_to_other_facilities', '$end_of_month_physical_count', '$quantity_required_for_supply', NULL, NULL, '0', '$facility_id', '$drug_id','$last_id');");
				}
			}

			//Paediatric Preparations

			for ($i = 44; $i <= 76; $i++) {
				for ($j = 1; $j <= $highestColumm; $j++) {
				}

				$quantity_required_for_supply = $arr[$i]['L'];
				$drug_name = $arr[$i]['A'];
				if ($quantity_required_for_supply != 0) {
					$drug_id = $drug_name;
					$basic_unit = $arr[$i]['B'];
					$beginning_balance = $arr[$i]['C'];
					$quantity_received_in_period = $arr[$i]['D'];
					$quantity_dispensed_in_period = $arr[$i]['E'];
					$adjustments_to_other_facilities = $arr[$i]['G'];
					$end_of_month_physical_count = $arr[$i]['H'];
					$quantity_required_for_supply = $arr[$i]['L'];
					$query = $this -> db -> query("SELECT MAX(id) AS id FROM cdrr_item");
					$results = $query -> result_array();
					$last_id = $results[0]['id'];
					$last_id++;
					$last_id = md5($last_id . $facility_code);
					$cdrr_query = $this -> db -> query("INSERT INTO cdrr_item (`id`, `balance`, `received`, `dispensed_units`, `dispensed_packs`, `losses`, `adjustments`, `count`, `resupply`, `aggr_consumed`, `aggr_on_hand`, `publish`, `cdrr_id`, `drug_id`,`unique_id`) VALUES (NULL, '$beginning_balance', '$quantity_received_in_period', '$quantity_dispensed_in_period', NULL, NULL, '$adjustments_to_other_facilities', '$end_of_month_physical_count', '$quantity_required_for_supply', NULL, NULL, '0', '$facility_id', '$drug_id','$last_id');");

				}

			}

			//Drugs for IOs

			for ($i = 78; $i <= 99; $i++) {
				for ($j = 1; $j <= $highestColumm; $j++) {
				}

				$quantity_required_for_supply = $arr[$i]['L'];
				$drug_name = $arr[$i]['A'];
				if ($quantity_required_for_supply != 0) {
					$drug_id = $drug_name;
					$basic_unit = $arr[$i]['B'];
					$beginning_balance = $arr[$i]['C'];
					$quantity_received_in_period = $arr[$i]['D'];
					$quantity_dispensed_in_period = $arr[$i]['E'];
					$adjustments_to_other_facilities = $arr[$i]['G'];
					$end_of_month_physical_count = $arr[$i]['H'];
					$quantity_required_for_supply = $arr[$i]['L'];
					$query = $this -> db -> query("SELECT MAX(id) AS id FROM cdrr_item");
					$results = $query -> result_array();
					$last_id = $results[0]['id'];
					$last_id++;
					$last_id = md5($last_id . $facility_code);
					$cdrr_query = $this -> db -> query("INSERT INTO cdrr_item (`id`, `balance`, `received`, `dispensed_units`, `dispensed_packs`, `losses`, `adjustments`, `count`, `resupply`, `aggr_consumed`, `aggr_on_hand`, `publish`, `cdrr_id`, `drug_id`,`unique_id`) VALUES (NULL, '$beginning_balance', '$quantity_received_in_period', '$quantity_dispensed_in_period', NULL, NULL, '$adjustments_to_other_facilities', '$end_of_month_physical_count', '$quantity_required_for_supply', NULL, NULL, '0', '$facility_id', '$drug_id','$last_id');");

				}
			}

			//PMTCT Regimen 1.Pregnant Women
			for ($i = 19; $i <= 21; $i++) {
				for ($j = 19; $j <= $highestColumm; $j++) {
				}

				$regimen_code = $arr[$i]['S'];
				$regimen_desc = $arr[$i]['T'];
				$no_of_clients_dispensed_in_period = $arr[$i]['V'] . $arr[$i]['W'];
				if ($no_of_clients_dispensed_in_period) {
					$regimen_id = $regimen_code." | ".$regimen_desc;
					$query = $this -> db -> query("SELECT MAX(id) AS id FROM maps_item");
					$results = $query -> result_array();
					$last_id = $results[0]['id'];
					$last_id++;
					$last_id = md5($last_id . $facility_code);
					$next_query = $this -> db -> query("INSERT INTO maps_item (`id`, `total`, `regimen_id`, `maps_id`,`unique_id`) VALUES (NULL, '$no_of_clients_dispensed_in_period', '$regimen_id', '$facility_id','$last_id');");
				}

			}

			//PMTCT Regimen 2.Infants

			for ($i = 23; $i <= 27; $i++) {
				for ($j = 19; $j <= $highestColumm; $j++) {
				}

				$regimen_code = $arr[$i]['S'];
				$regimen_desc = $arr[$i]['T'];
				$no_of_clients_dispensed_in_period = $arr[$i]['V'] . $arr[$i]['W'];
				if ($no_of_clients_dispensed_in_period) {
					$this -> load -> database();
					$regimen_id = $regimen_code." | ".$regimen_desc;
					$query = $this -> db -> query("SELECT MAX(id) AS id FROM maps_item");
					$results = $query -> result_array();
					$last_id = $results[0]['id'];
					$last_id++;
					$last_id = md5($last_id . $facility_code);
					$next_query = $this -> db -> query("INSERT INTO maps_item (`id`, `total`, `regimen_id`, `maps_id`,`unique_id`) VALUES (NULL, '$no_of_clients_dispensed_in_period', '$regimen_id', '$facility_id','$last_id');");
				}

			}

			//Adult ART First Line Regimens

			for ($i = 33; $i <= 43; $i++) {
				for ($j = 19; $j <= $highestColumm; $j++) {
				}

				$regimen_code = $arr[$i]['S'];
				$regimen_desc = $arr[$i]['T'];
				$no_of_clients_dispensed_in_period = $arr[$i]['V'] . $arr[$i]['W'];
				if ($no_of_clients_dispensed_in_period) {
					$regimen_id = $regimen_code." | ".$regimen_desc;
					$query = $this -> db -> query("SELECT MAX(id) AS id FROM maps_item");
					$results = $query -> result_array();
					$last_id = $results[0]['id'];
					$last_id++;
					$last_id = md5($last_id . $facility_code);
					$next_query = $this -> db -> query("INSERT INTO maps_item (`id`, `total`, `regimen_id`, `maps_id`,`unique_id`) VALUES (NULL, '$no_of_clients_dispensed_in_period', '$regimen_id', '$facility_id','$last_id');");
				}

			}

			//Adult ART Second Line Regimens

			for ($i = 45; $i <= 58; $i++) {
				for ($j = 19; $j <= $highestColumm; $j++) {
				}

				$regimen_code = $arr[$i]['S'];
				$regimen_desc = $arr[$i]['T'];
				$no_of_clients_dispensed_in_period = $arr[$i]['V'] . $arr[$i]['W'];
				if ($no_of_clients_dispensed_in_period) {
					$regimen_id = $regimen_code." | ".$regimen_desc;
					$query = $this -> db -> query("SELECT MAX(id) AS id FROM maps_item");
					$results = $query -> result_array();
					$last_id = $results[0]['id'];
					$last_id++;
					$last_id = md5($last_id . $facility_code);
					$next_query = $this -> db -> query("INSERT INTO maps_item (`id`, `total`, `regimen_id`, `maps_id`,`unique_id`) VALUES (NULL, '$no_of_clients_dispensed_in_period', '$regimen_id', '$facility_id','$last_id');");
				}

			}

			//Other Adult ART regimens

			for ($i = 60; $i <= 62; $i++) {
				for ($j = 19; $j <= $highestColumm; $j++) {
				}

				$regimen_code = $arr[$i]['S'];
				$regimen_desc = $arr[$i]['T'];
				$no_of_clients_dispensed_in_period = $arr[$i]['V'] . $arr[$i]['W'];
				if ($no_of_clients_dispensed_in_period) {
					$regimen_id = $regimen_code." | ".$regimen_desc;
					$query = $this -> db -> query("SELECT MAX(id) AS id FROM maps_item");
					$results = $query -> result_array();
					$last_id = $results[0]['id'];
					$last_id++;
					$last_id = md5($last_id . $facility_code);
					$next_query = $this -> db -> query("INSERT INTO maps_item (`id`, `total`, `regimen_id`, `maps_id`,`unique_id`) VALUES (NULL, '$no_of_clients_dispensed_in_period', '$regimen_id', '$facility_id','$last_id');");
				}

			}

			//Paediatric ART First Line Regimens

			for ($i = 64; $i <= 74; $i++) {
				for ($j = 19; $j <= $highestColumm; $j++) {
				}

				$regimen_code = $arr[$i]['S'];
				$regimen_desc = $arr[$i]['T'];
				$no_of_clients_dispensed_in_period = $arr[$i]['V'] . $arr[$i]['W'];
				if ($no_of_clients_dispensed_in_period) {
					$regimen_id = $regimen_code." | ".$regimen_desc;
					$query = $this -> db -> query("SELECT MAX(id) AS id FROM maps_item");
					$results = $query -> result_array();
					$last_id = $results[0]['id'];
					$last_id++;
					$last_id = md5($last_id . $facility_code);
					$next_query = $this -> db -> query("INSERT INTO maps_item (`id`, `total`, `regimen_id`, `maps_id`,`unique_id`) VALUES (NULL, '$no_of_clients_dispensed_in_period', '$regimen_id', '$facility_id','$last_id');");
				}

			}

			//Paediatric ART Second Line Regimens

			for ($i = 76; $i <= 84; $i++) {
				for ($j = 19; $j <= $highestColumm; $j++) {
				}

				$regimen_code = $arr[$i]['S'];
				$regimen_desc = $arr[$i]['T'];
				$no_of_clients_dispensed_in_period = $arr[$i]['V'] . $arr[$i]['W'];
				if ($no_of_clients_dispensed_in_period) {
					$regimen_id = $regimen_code." | ".$regimen_desc;
					$query = $this -> db -> query("SELECT MAX(id) AS id FROM maps_item");
					$results = $query -> result_array();
					$last_id = $results[0]['id'];
					$last_id++;
					$last_id = md5($last_id . $facility_code);
					$next_query = $this -> db -> query("INSERT INTO maps_item (`id`, `total`, `regimen_id`, `maps_id`,`unique_id`) VALUES (NULL, '$no_of_clients_dispensed_in_period', '$regimen_id', '$facility_id','$last_id');");
				}

			}

			//Other Paediatric ART regimens

			for ($i = 86; $i <= 87; $i++) {
				for ($j = 19; $j <= $highestColumm; $j++) {
				}

				$regimen_code = $arr[$i]['S'];
				$regimen_desc = $arr[$i]['T'];
				$no_of_clients_dispensed_in_period = $arr[$i]['V'] . $arr[$i]['W'];
				if ($no_of_clients_dispensed_in_period) {
					$regimen_id = $regimen_code." | ".$regimen_desc;
					$query = $this -> db -> query("SELECT MAX(id) AS id FROM maps_item");
					$results = $query -> result_array();
					$last_id = $results[0]['id'];
					$last_id++;
					$last_id = md5($last_id . $facility_code);
					$next_query = $this -> db -> query("INSERT INTO maps_item (`id`, `total`, `regimen_id`, `maps_id`,`unique_id`) VALUES (NULL, '$no_of_clients_dispensed_in_period', '$regimen_id', '$facility_id','$last_id');");
				}

			}

			//POST Exposure Prophylaxis(PEP)

			for ($i = 91; $i <= 99; $i++) {
				for ($j = 19; $j <= $highestColumm; $j++) {
				}

				$regimen_code = $arr[$i]['S'];
				$regimen_desc = $arr[$i]['T'];
				$no_of_clients_dispensed_in_period = $arr[$i]['V'] . $arr[$i]['W'];
				if ($no_of_clients_dispensed_in_period) {
					$regimen_id = $regimen_code." | ".$regimen_desc;
					$query = $this -> db -> query("SELECT MAX(id) AS id FROM maps_item");
					$results = $query -> result_array();
					$last_id = $results[0]['id'];
					$last_id++;
					$last_id = md5($last_id . $facility_code);
					$next_query = $this -> db -> query("INSERT INTO maps_item (`id`, `total`, `regimen_id`, `maps_id`,`unique_id`) VALUES (NULL, '$no_of_clients_dispensed_in_period', '$regimen_id', '$facility_id','$last_id');");
				}
			}

			//ARV Data collection and Reporting Tools

			//1.Name of Data-DAR

			//a.ARVS Collection Tool

			$fifty_arv_page_requested = $arr[116]['D'];
			$three_hundred_arv_page_requested = $arr[116]['E'];
			if ($fifty_arv_page_requested) {
				$dar_arv_quantity_requested = $fifty_arv_page_requested;
			}
			if ($three_hundred_arv_page_requested) {
				$dar_arv_quantity_requested = $three_hundred_arv_page_requested;
			}

			//a.OIs Collection Tool

			$fifty_oi_page_requested = $arr[116]['G'];
			$three_hundred_oi_page_requested = $arr[116]['H'];
			if ($fifty_oi_page_requested) {
				$dar_oi_quantity_requested = $fifty_oi_page_requested;
			}
			if ($three_hundred_oi_page_requested) {
				$dar_oi_quantity_requested = $three_hundred_oi_page_requested;
			}

			//2.Name of Data-FCDRR

			$fcdrr_quantity_requested = $arr[116]['L'];

			//Prepared By details

			$report_prepared_by = $arr[119]['B'] . $arr[119]['C'] . $arr[119]['D'];
			$prepared_by_contact_telephone = $arr[121]['B'] . $arr[121]['C'] . $arr[121]['D'];
			$signature_prepared_by = $arr[119]['G'] . $arr[119]['H'] . $arr[119]['L'];
			$date_prepared_by_signature = $arr[121]['G'] . $arr[121]['H'];

			//Approved By details
			$report_approved_by = $arr[123]['B'] . $arr[123]['C'] . $arr[123]['D'];
			$approved_by_contact_telephone = $arr[126]['B'] . $arr[126]['C'] . $arr[126]['D'];
			$signature_approved_by = $arr[123]['G'] . $arr[123]['H'] . $arr[123]['L'];
			$date_approved_by_signature = $arr[126]['G'] . $arr[126]['H'];

			//$this -> session -> set_userdata('upload_counter','2');
			redirect("order_management/edit_order/$order_number");

		}

	}

	public function base_params($data) {
		$data['title'] = "FCDRR Data";
		$data['banner_text'] = "(F-CDRR)DATA UPLOAD";
		$data['content_view'] = "fcdrr_upload";
		$data['quick_link'] = "fcdrr";
		$this -> load -> view('template', $data);
	}

}
