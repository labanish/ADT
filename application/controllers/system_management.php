<?php

error_reporting(1);
class System_management extends MY_Controller {
	function __construct() {
		parent::__construct();
		$this -> load -> library('PHPExcel');
		$this -> load -> helper('url');
		$this -> load -> library('github_updater');
		$this -> load -> library('Unzip');
		$this -> load -> library('Curl');
		date_default_timezone_set('Africa/Nairobi');

	}

	public function index() {
		$this -> load_assets();
		redirect('user_management/login');
	}

	public function load_assets() {

		// Clear cache Details
		/*
		$files = glob('assets/cache/*'); // get all file names in Cache
		foreach($files as $file){ // iterate files
		  if(is_file($file))
		    unlink($file); // delete file
		}*/

		// Clear cache Details

		$this -> load -> library('carabiner');

		$date_past = date('o-m-d H:i:s', time() - 1 * 60);

		$jsArray = array( array('jquery-1.11.1.min.js'), array('jquery-1.7.2.min.js'), array('jquery-migrate-1.2.1-min.js'), array('jquery.form.js'), array('jquery.gritter.js'), array('jquery-ui.js'), array('sorttable.js'), array('datatable/jquery.dataTables.min.js'), array('datatable/columnFilter.js'), array('bootstrap/bootstrap.min.js'), array('bootstrap/paging.js'), array('jquery.multiselect.js'), array('jquery.multiselect.filter.js'), array('validator.js'), array('validationEngine-en.js'), array('menus.js'), array('jquery.blockUI.js'), array('amcharts/amcharts.js'), array('highcharts/highcharts.js'), array('highcharts/highcharts-more.js'), array('highcharts/modules/exporting.js'),array('toastr.js'),array('select2-3.4.8/select2.min.js'),array('bootbox.min.js'), array('Merged_JS.js'));
		$cssArray = array( array('amcharts/style.css'), array('bootstrap.css'), array('bootstrap.min.css'), array('bootstrap-responsive.min.css'), array('datatable/jquery.dataTables.css'), array('datatable/jquery.dataTables_themeroller.css'), array('datatable/demo_table.css'), array('jquery-ui.css'), array('style.css'), array('assets/jquery.multiselect.css'), array('assets/jquery.multiselect.filter.css'), array('assets/prettify.css'), array('style_report.css'), array('validator.css'), array('jquery.gritter.css'),array('toastr.min.css'),array('select2-3.4.8/select2.css'));

		$this -> carabiner -> css($cssArray);
		$this -> carabiner -> js($jsArray);

		$assets = $this -> carabiner -> display_string();
		$this -> load -> helper('file');
		if (!write_file('application/views/sections/link.php', $assets)) {
			echo 'Unable to write the file';
		} else {
			echo 'File written!';
		}
	}

	public function search_system($search_type,$stock_type = '2'){
		$search=$_GET['q'];
		$answer = array();
		//Patient Search
		if($search_type=='patient'){
			$sql = "SELECT p.id,p.patient_number_ccc,p.First_Name,p.other_name,p.Last_Name,p.phone 
				FROM patient p
				WHERE p.patient_number_ccc LIKE '%$search%' OR p.First_Name LIKE '%$search%' OR other_name LIKE '%$search%' OR Last_Name LIKE '%$search%' OR phone LIKE '%$search%' ORDER BY first_name ASC";
			$query = $this -> db -> query($sql);
			$results=$query -> result_array();
	
			if($results){
	           foreach($results as $result){
	           	 $p_ccc = $result['patient_number_ccc'];
				 $_fname = $result['First_Name'];
				 $p_mname = $result['middle_name'];
				 $p_lname = $result['Last_Name'];
				 $p_phone = $result['phone'];
				 $res = 'CCC No: '.$p_ccc.' | '.$_fname.' '.$p_mname.' '.$p_lname.' ('.$p_phone.')';
	           	 $answer[] = array("id"=>$result['id'],"text"=>$res);
	           }
			}else{
	             $answer[] = array("id"=>"0","text"=>"No Results Found..");
			}
		
		}else if($search_type=='drugcode'){
			$sql = "SELECT d.id,d.drug,du.Name as drug_unit, d.pack_size,g.name as generic_name
				FROM drugcode d
				LEFT JOIN drug_unit du ON du.id = d.unit
				LEFT JOIN generic_name g ON g.id = d.generic_name
				WHERE d.drug LIKE '%$search%' OR  du.Name LIKE '%$search%' OR g.name LIKE '%$search%' ";
			$query = $this -> db -> query($sql);
			$results=$query -> result_array();
	
			if($results){
	           foreach($results as $result){
	           	 
				 $res = $result['drug'].' ('.$result['generic_name'].')  - '.$result['drug_unit'];
	           	 $answer[] = array("id"=>$result['id'],"text"=>$res);
	           }
			}else{
	             $answer[] = array("id"=>"0","text"=>"No Results Found..");
			}
		}
		
		
        echo json_encode($answer); 
		
	}
	
	public function checkConnection(){//Check Internet Connection
		$curl = new Curl();
		$url ="http://google.com/";
		$curl -> get($url);
		if ($curl -> error) {
			echo json_encode('0');
		} else {
			echo json_encode('1');
		}
	}


}
