<?php
class Inventory_Management extends MY_Controller {
	function __construct() {
		parent::__construct();
	}

	public function index() {
		$this -> listing();
	}

	public function listing($stock_type = 1) {
		$data['active'] = "";
		//Make pharmacy inventory active
		if ($stock_type == 2) {
			$data['active'] = 'pharmacy_btn';
		}
		//Make store inventory active
		else {
			$data['active'] = 'store_btn';
		}
		$data['content_view'] = "inventory_listing_v";
		$this -> base_params($data);
	}
	

	public function stock_listing($stock_type = 1) {
		$facility_code = $this -> session -> userdata('facility');
		$data = array();
		/* Array of database columns which should be read and sent back to DataTables. Use a space where
		 * you want to insert a non-database field (for example a counter or static image)
		 */
		$aColumns = array('drug', 'generic_name', 'stock_level', 'drug_unit', 'pack_size', 'supported_by', 'dose');
		$iDisplayStart = $this -> input -> get_post('iDisplayStart', true);
		$iDisplayLength = $this -> input -> get_post('iDisplayLength', true);
		$iSortCol_0 = $this -> input -> get_post('iSortCol_0', true);
		$iSortingCols = $this -> input -> get_post('iSortingCols', true);
		$sSearch = $this -> input -> get_post('sSearch', true);
		$sEcho = $this -> input -> get_post('sEcho', true);
		/*
		 * Paging
		 * */
		$sLimit = "";
		if (isset($iDisplayStart) && $iDisplayLength != '-1') {
			$sLimit = "LIMIT " . intval($iDisplayStart) . ", " . intval($iDisplayLength);
		}
		/*
		 * Ordering
		 */
		$sOrder = "";
		if (isset($_GET['iSortCol_0'])) {
			$sOrder = "ORDER BY  ";
			for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
				if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == "true") {
					$sOrder .= "`" . $aColumns[intval($_GET['iSortCol_' . $i])] . "` " . ($_GET['sSortDir_' . $i] === 'asc' ? 'asc' : 'desc') . ", ";
				}
			}

			$sOrder = substr_replace($sOrder, "", -2);
			if ($sOrder == "ORDER BY") {
				$sOrder = "";
			}
		}

		/*
		 * Filtering
		 * NOTE this does not match the built-in DataTables filtering which does it
		 * word by word on any field. It's possible to do here, but concerned about efficiency
		 * on very large tables, and MySQL's regex functionality is very limited
		 */
		$sFilter = "";
		$c = 0;
		if (isset($sSearch) && !empty($sSearch)) {
			$sFilter = "AND ( ";
			for ($i = 0; $i < count($aColumns); $i++) {
				$bSearchable = $this -> input -> get_post('bSearchable_' . $i, true);

				// Individual column filtering
				if (isset($bSearchable) && $bSearchable == 'true') {
					if ($aColumns[$i] != 'drug_unit') {
						if ($c != 0) {
							$sFilter .= " OR ";
						}
						$c = 1;
						$sSearch = mysql_real_escape_string($sSearch);
						$sFilter .= "`" . $aColumns[$i] . "` LIKE '%" . $sSearch . "%'";
					}

				}
			}
			$sFilter .= " )";
			if ($sFilter == "AND ( )") {
				$sFilter = "";
			}
		}

		// Select Data
		$sql = "SELECT dc.id,UPPER( dc.drug ) AS drug, du.Name AS drug_unit,d.Name as dose, s.name AS supported_by, dc.pack_size, UPPER( g.Name ) AS generic_name, IF( SUM( balance ) >0, SUM( balance ) ,  '0' ) AS stock_level
				FROM drugcode dc
				LEFT OUTER JOIN generic_name g ON g.id = dc.generic_name
				LEFT OUTER JOIN suppliers s ON s.id = dc.supported_by
				LEFT OUTER JOIN dose d ON d.Name = dc.dose
				LEFT OUTER JOIN drug_unit du ON du.id = dc.unit
				LEFT OUTER JOIN (
				SELECT * 
				FROM drug_stock_balance
				WHERE facility_code =  '$facility_code'
				AND expiry_date > CURDATE()
				AND stock_type =  '$stock_type'
				) AS dsb ON dsb.drug_id = dc.id
				WHERE dc.enabled =  '1' " . $sFilter . "
				GROUP BY dc.id " . $sOrder . " " . $sLimit;
		$q = $this -> db -> query($sql);
		$rResult = $q;
		//echo $iDisplayLength;die();
		// Data set length after filtering
		$this -> db -> select('COUNT(id) AS found_rows from drugcode dc where dc.enabled=1 ' . $sFilter);
		$iFilteredTotal = $this -> db -> get() -> row() -> found_rows;

		//Total number of drugs that are displayed
		$this -> db -> select('COUNT(id) AS found_rows from drugcode dc where dc.enabled=1');
		$iTotal = $this -> db -> get() -> row() -> found_rows;
		//$iFilteredTotal = $iTotal;

		// Output
		$output = array('sEcho' => intval($sEcho), 'iTotalRecords' => $iTotal, 'iTotalDisplayRecords' => $iFilteredTotal, 'aaData' => array());

		foreach ($rResult->result_array() as $aRow) {
			$row = array();
			$x = 0;
			foreach ($aColumns as $col) {
				$x++;
				//Format soh
				if ($col == "stock_level") {
					$row[] = '<b style="color:green">' . number_format($aRow['stock_level']) . '</b>';
				} else {
					$row[] = $aRow[$col];
				}

			}
			$id = $aRow['id'];
			$row[] = "<a href='" . base_url() . "inventory_management/getDrugBinCard/" . $id . "/" . $stock_type . "'>View Bin Card</a>";

			$output['aaData'][] = $row;
		}

		echo json_encode($output);
	}
	
	public function getDrugBinCard($drug_id='',$ccc_id=''){
		
		//CCC Store Name
		$ccc = CCC_store_service_point::getCCC($ccc_id);
		$ccc_name = $ccc['Name'];

		$pack_size = 0;
		//get drug information
        $drug=Drugcode::getDrug($drug_id,$ccc_id);
		$data['commodity'] = '';
		$data['unit'] = '';
		$drug_map = '';
        if($drug){
        	$data['commodity']=$drug['drug'];
			$drug_map = $drug['map'];
        	$data['unit']=$drug['drugunit'];
			$pack_size = $drug['pack_size'];
        }
        $total_stock=0;
        //get batch information
        $drug_batches=array();
		$today = date('Y-m-d');
		$facility_code = $this -> session -> userdata('facility');
        $batches=Drugcode::getDrugBatches($drug_id,$ccc_id,$facility_code,$today);
        if($batches){//Check if batches exist
        	foreach($batches as $counter=>$batch){
                $drug_batches[$counter]['drug']=$batches[$counter]['drugname'];
                $drug_batches[$counter]['packsize']=$batches[$counter]['pack_size'];
                $drug_batches[$counter]['batchno']=$batches[$counter]['batch_number'];
                $drug_batches[$counter]['balance']=$batches[$counter]['balance'];
                $drug_batches[$counter]['expiry_date']=$batches[$counter]['expiry_date'];
                $total_stock=$total_stock+$batches[$counter]['balance'];
        	}
        }
		
		//Consumption
		$three_months_consumption = 0;
		$transaction_type='';
		if (stripos($ccc_name, "pharmacy")) {
			$transaction_type = Transaction_Type::getTransactionType('dispense',0);
			$transaction_type = $transaction_type['id'];
		} else if (stripos($ccc_name, "store")) {
			$transaction_type = Transaction_Type::getTransactionType('issue',0);
			$transaction_type = $transaction_type['id'];
		}

		$consumption = Drug_Stock_Movement::getDrugConsumption($drug_id, $facility_code,$ccc_id,$transaction_type);
		foreach ($consumption as $value) {
			$three_months_consumption += $value['total_out'];
		}

		// echo "<pre>";print_r($data);die;
		//3 Months consumption using facility orders
		$data['maximum_consumption'] = number_format($three_months_consumption);
		$data['avg_consumption'] = number_format(($three_months_consumption) / 3);
		$monthly_consumption = number_format(($three_months_consumption) / 3);
		$min_consumption = $three_months_consumption * (0.5);
		$data['minimum_consumption'] = number_format($min_consumption);
		$data['stock_val'] = $ccc_id;
		$data['hide_sidemenu']='';
        $data['total_stock']=$total_stock;
        $data['batches']=$drug_batches;
		$data['hide_side_menu'] = '1';
		$data['store'] = $ccc_name;
		$data['drug_id'] = $drug_id;
		$data['content_view']='bin_card_v';

		$this->base_params($data);
	}

	public function getDrugTransactions($drug_id='4',$ccc_id='2'){
		ini_set("memory_limit", -1);
		$iDisplayStart = $this -> input -> get_post('iDisplayStart', true);
		$iDisplayLength = $this -> input -> get_post('iDisplayLength', true);
		$iSortCol_0 = $this -> input -> get_post('iSortCol_0', false);
		$iSortingCols = $this -> input -> get_post('iSortingCols', true);
		$sSearch = $this -> input -> get_post('sSearch', true);
		$sEcho = $this -> input -> get_post('sEcho', true);
		$where="";

        //columns
        $aColumns = array('Order_Number', 
        	              'Transaction_Date', 
        	              't.name as Transaction_Type', 
        	              't.effect',
        	              'Batch_Number', 
        	              'd.name as destination_name', 
        	              's.name as source_name', 
        	              'source_destination',
        	              'Expiry_Date', 
        	              'Pack_Size', 
        	              'Packs', 
        	              'ds.Quantity', 
        	              'ds.Quantity_Out', 
        	              'Machine_Code', 
        	              'Unit_Cost', 
        	              'Amount');

        $count = 0;

		// Paging
		if (isset($iDisplayStart) && $iDisplayLength != '-1') {
			$this -> db -> limit($this -> db -> escape_str($iDisplayLength), $this -> db -> escape_str($iDisplayStart));
		}

		// Ordering
		if (isset($iSortCol_0)) {
			for ($i = 0; $i < intval($iSortingCols); $i++) {
				$iSortCol = $this -> input -> get_post('iSortCol_' . $i, true);
				$bSortable = $this -> input -> get_post('bSortable_' . intval($iSortCol), true);
				$sSortDir = $this -> input -> get_post('sSortDir_' . $i, true);

				if ($bSortable == 'true') {
					$this -> db -> order_by($aColumns[intval($this -> db -> escape_str($iSortCol))], $this -> db -> escape_str($sSortDir));
				}
			}
		}
		//Filtering
		if (isset($sSearch) && !empty($sSearch)) {
			$column_count=0;
			//new columns
	        $newColumns = array('Order_Number', 
				              'Transaction_Date', 
				              't.name', 
				              't.effect',
				              'Batch_Number', 
				              'd.name', 
				              's.name', 
				              'source_destination',
				              'Expiry_Date', 
				              'Pack_Size', 
				              'Packs', 
				              'ds.Quantity', 
				              'ds.Quantity_Out', 
				              'Machine_Code', 
				              'Unit_Cost', 
				              'Amount');
			for ($i = 0; $i < count($newColumns); $i++) {
				$bSearchable = $this -> input -> get_post('bSearchable_' . $i, true);

				// Individual column filtering
				if (isset($bSearchable) && $bSearchable == 'true') {
					if($column_count==0){
						$where.="(";                   
					}else{
                        $where.=" OR ";       
					}
					$where.=$newColumns[$i]." LIKE '%".$this -> db -> escape_like_str($sSearch)."%'";
					$column_count++;
				}
			}
		}

		//data
		$this -> db -> select('SQL_CALC_FOUND_ROWS ' . str_replace(' , ', ' ', implode(', ', $aColumns)), false);
	    $this -> db -> select('t.effect');
	    $this -> db -> from("drug_stock_movement ds");
		$this -> db -> join("drugcode dc", "dc.id=ds.drug", "left");
		$this -> db -> join("transaction_type t", "t.id=ds.transaction_type","left");
		$this -> db -> join("drug_source s", "s.id=ds.source_destination", "left");
		$this -> db -> join("drug_destination d", "d.id=ds.source_destination","left");
		$this -> db -> where("ds.drug", $drug_id);
		$this -> db -> where("ds.ccc_store_sp", $ccc_id);
		//search sql clause
		if($where !=""){
		    $where.=")";
			$this ->db -> where($where);
		}
		$this -> db -> order_by('ds.id', 'desc');
		$rResult = $this -> db -> get();

		// Data set length after filtering
		$this -> db -> select('FOUND_ROWS() AS found_rows');
		$iFilteredTotal = $this -> db -> get() -> row() -> found_rows;

		// Total data set length
		$this -> db -> select("ds.*");
	    $this -> db -> from("drug_stock_movement ds");
		$this -> db -> join("drugcode dc", "dc.id=ds.drug", "left");
		$this -> db -> join("transaction_type t", "t.id=ds.transaction_type","left");
		$this -> db -> join("drug_source s", "s.id=ds.source_destination", "left");
		$this -> db -> join("drug_destination d", "d.id=ds.source_destination","left");
		$this -> db -> where("ds.drug", $drug_id);
		$this -> db -> where("ds.ccc_store_sp", $ccc_id);
		$total = $this -> db -> get();
		$iTotal = count($total -> result_array());
		// Output
		$output = array('sEcho' => intval($sEcho), 
			            'iTotalRecords' => $iTotal, 
			            'iTotalDisplayRecords' => $iFilteredTotal, 
			            'aaData' => array());

		//loop through data to change transaction type
		foreach ($rResult->result() as $drug_transaction) {
			$row = array();
            if($drug_transaction->effect==1){
            	//quantity_out & source (means adds stock to system)
            	$transaction_type=$drug_transaction->Transaction_Type;
            	$qty=$drug_transaction->Quantity;
            	if($drug_transaction->source_name!="" || $drug_transaction->source_name !=0 ){
            		$transaction_type=$drug_transaction->Transaction_Type." (".$drug_transaction->source_name.")";
            	}else if(!is_numeric($drug_transaction->source_destination)){
            		$transaction_type=$drug_transaction->Transaction_Type." (".$drug_transaction->source_destination.")";
            	}
            }else{
            	//quantity & destination (means removes stock from system)
            	$transaction_type=$drug_transaction->Transaction_Type;
            	$qty=$drug_transaction->Quantity_Out;
            	if($drug_transaction->destination_name!="" || $drug_transaction->destination_name !=0){
            		$transaction_type=$drug_transaction->Transaction_Type." (".$drug_transaction->destination_name.")";
            	}else if(!is_numeric($drug_transaction->source_destination)){
            		$transaction_type=$drug_transaction->Transaction_Type." (".$drug_transaction->source_destination.")";
            	}
            }
      
			$row[] = $drug_transaction -> Order_Number;
			$row[] = date('d-M-Y', strtotime($drug_transaction -> Transaction_Date));
			$row[] = $transaction_type;
			$row[] = $drug_transaction -> Batch_Number;
			$row[] = date('d-M-Y', strtotime($drug_transaction -> Expiry_Date));
			$row[] = $drug_transaction -> Pack_Size;
			$row[] = $drug_transaction -> Packs;
			$row[] = $qty;
			if(!empty($drug_transaction -> Machine_Code)){
				$row[] = number_format($drug_transaction -> Machine_Code);
			}else{
				$row[] = "";
			}
			$row[] = $drug_transaction -> Unit_Cost;
			$row[] = $drug_transaction -> Amount;
			$output['aaData'][] = $row;
		}
		// echo "<pre>";print_r($output);die;
		echo json_encode($output,JSON_PRETTY_PRINT);
	}
	
	

	public function stock_transaction($stock_type = 1) {
		$data['hide_side_menu'] = 1;
		$facility_code = $this -> session -> userdata('facility');
		$user_id = $this -> session -> userdata('user_id');
		$access_level=$this -> session -> userdata('user_indicator');
		if($access_level=="facility_administrator"){
		  $transaction_type = Transaction_Type::getAll();
		}else{
		  $transaction_type = Transaction_Type::getAllNonAdjustments();
		}
		$drug_source = Drug_Source::getAll();
		$facility_detail = facilities::getSupplier($facility_code);
		$drug_destination = Drug_Destination::getAll();
		//Check facility type(satelitte, standalone or central)
		$facility_type = Facilities::getType($facility_code);
		$get_list = array();
		$data['list_facility'] = "";
		if ($facility_type == 0) {//Satellite
			$central_code = facilities::getCentralCode($facility_code);
			$get_list = facilities::getCentralName($central_code);
			$data['list_facility'] = "Central Site";
			
		} else if ($facility_type == 1) {//Standalone
			$get_list = array();
			$data['list_facility'] = "";
		} else if ($facility_type > 1) {//Central
			$get_list = facilities::getSatellites($facility_code);
			$data['list_facility'] = "Satelitte Sites";
		}
		
		$name = CCC_store_service_point::getCCC($stock_type);
		$name = $name['Name'];
		
		$data['supplier_name'] = $facility_detail -> supplier -> name;
		$data['picking_lists'] = "";
		$data['get_list'] = $get_list;
		$data['user_id'] = $user_id;
		$data['facility'] = $facility_code;
		$data['stock_type'] = $stock_type;
		$data['transaction_types'] = $transaction_type;
		$data['drug_sources'] = $drug_source;
		$data['drug_destinations'] = $drug_destination;
		$data['store'] = strtoupper($name);	
		$data['content_view'] = "stock_transaction_v";
		$this -> base_params($data);

	}
    public function sendemail($email){
    	
		$config['mailtype']="html";
		$config['protocol']="smtp";
		$config['smtp_host']="ssl://smtp.googlemail.com";
		$config['smtp_port']="465";
		$config['smtp_user']=stripslashes('webadt.chai@gmail.com');
		$config['smtp_pass']=stripslashes('WebAdt_052013');

		$this->load->library('email',$config);
		$this -> email -> set_newline("\r\n");
		$this->email->from('webadt.chai@gmail.com', "WEB_ADT CHAI");
		$this->email->to($email);
		$this->email->subject('Reciept of drugs');
		$this->email->message('Dear Sir/Madam we have recieved the drugs sent');
		
		if(@$this->email->send()){
			echo "The email was successfully sent";
		}
		else{
			//show_error($this->email->print_debugger());
			echo "The email was not sent";
		}
		
		
    }

	public function getStockDrugs() {
		$stock_type = $this -> input -> post("stock_type");
		$facility_code = $this -> session -> userdata('facility');

		$drugs_sql = $this -> db -> query("SELECT DISTINCT(d.id),d.drug FROM drugcode d LEFT JOIN drug_stock_balance dsb on dsb.drug_id=d.id WHERE dsb.facility_code='$facility_code' AND dsb.stock_type='$stock_type' AND dsb.balance>0 AND dsb.expiry_date>=CURDATE() AND d.enabled='1' ORDER BY d.drug asc");
		$drugs_array = $drugs_sql -> result_array();
		echo json_encode($drugs_array);

	}

	public function getAllDrugs() {
		$facility_code = $this -> session -> userdata('facility');
		$drugs_sql = $this -> db -> query("SELECT DISTINCT(d.id),d.drug FROM drugcode d  WHERE d.enabled='1' ORDER BY d.drug asc");
		$drugs_array = $drugs_sql -> result_array();
		echo json_encode($drugs_array);

	}

	public function getBacthes() {
		$facility_code = $this -> session -> userdata('facility');
		$stock_type = $this -> input -> post("stock_type");
		$selected_drug = $this -> input -> post("selected_drug");
		$sql = "SELECT  
		            DISTINCT d.pack_size,
		            d.comment,
		            d.duration,
		            d.quantity,
		            u.Name,
		            dsb.batch_number,
		            dsb.expiry_date,
		            d.dose as dose,
		            do.Name as dose_id 
				FROM drugcode d 
				LEFT JOIN drug_stock_balance dsb ON d.id=dsb.drug_id 
				LEFT JOIN drug_unit u ON u.id=d.unit 
				LEFT JOIN dose do ON d.dose=do.id  
				WHERE d.enabled=1 
				AND dsb.facility_code='$facility_code' 
				AND dsb.stock_type='$stock_type' 
				AND dsb.drug_id='$selected_drug' 
				AND dsb.balance > 0 
				AND dsb.expiry_date > CURDATE() 
				ORDER BY dsb.expiry_date ASC";
		$batch_sql = $this -> db -> query($sql);
		$batches_array = $batch_sql -> result_array();
		echo json_encode($batches_array);
	}

	public function getBacthDetails() {
		$facility_code = $this -> session -> userdata('facility');
		$stock_type = $this -> input -> post("stock_type");
		$selected_drug = $this -> input -> post("selected_drug");
		$batch_selected = $this -> input -> post("batch_selected");
		$sql = "SELECT 
		            dsb.balance, 
		            dsb.expiry_date 
		        FROM drug_stock_balance dsb  
		        WHERE dsb.facility_code = '$facility_code' 
		        AND dsb.stock_type = '$stock_type' 
		        AND dsb.drug_id = '$selected_drug' 
		        AND dsb.batch_number = '$batch_selected' 
		        AND dsb.balance > 0 
		        AND dsb.expiry_date > CURDATE() 
		        ORDER BY dsb.expiry_date ASC
		        LIMIT 1";
		$batch_sql = $this -> db -> query($sql);
		$batches_array = $batch_sql -> result_array();
		echo json_encode($batches_array);
	}

	public function getAllBacthDetails() {
		$facility_code = $this -> session -> userdata('facility');
		$stock_type = $this -> input -> post("stock_type");
		$selected_drug = $this -> input -> post("selected_drug");
		$batch_selected = $this -> input -> post("batch_selected");
		$sql = "SELECT 
		            dsb.balance, 
		            dsb.expiry_date 
		        FROM drug_stock_balance dsb  
		        WHERE dsb.facility_code='$facility_code' 
		        AND dsb.stock_type='$stock_type' 
		        AND dsb.drug_id='$selected_drug' 
		        AND dsb.batch_number='$batch_selected'  
		        ORDER BY last_update DESC,dsb.expiry_date ASC 
		        LIMIT 1";
		$batch_sql = $this -> db -> query($sql);
		$batches_array = $batch_sql -> result_array();
		echo json_encode($batches_array);
	}

	//Get balance details
	public function getBalanceDetails() {
		$facility_code = $this -> session -> userdata('facility');
		$stock_type = $this -> input -> post("stock_type");
		$selected_drug = $this -> input -> post("selected_drug");
		$batch_selected = $this -> input -> post("batch_selected");
		$expiry_date = $this -> input -> post("expiry_date");
		$sql = "SELECT 
		            dsb.balance, 
		            dsb.expiry_date 
		        FROM drug_stock_balance dsb  
		        WHERE dsb.facility_code = '$facility_code' 
		        AND dsb.stock_type = '$stock_type' 
		        AND dsb.drug_id = '$selected_drug' 
		        AND dsb.batch_number = '$batch_selected' 
		        AND dsb.balance > 0 
		        AND dsb.expiry_date > CURDATE() 
		        AND dsb.expiry_date='$expiry_date' 
		        ORDER BY last_update DESC,dsb.expiry_date ASC 
		        LIMIT 1";
		$batch_sql = $this -> db -> query($sql);
		$batches_array = $batch_sql -> result_array();
		echo json_encode($batches_array);
	}

	public function getDrugDetails() {
		$selected_drug = $this -> input -> post("selected_drug");
		$sql = "SELECT 
		            d.pack_size,
		            u.Name 
		        FROM drugcode d 
		        LEFT JOIN drug_unit u ON u.id=d.unit 
		        WHERE d.enabled=1 
		        AND d.id='$selected_drug'";
		$drug_details_sql = $this -> db -> query($sql);
		$drug_details_array = $drug_details_sql -> result_array();
		echo json_encode($drug_details_array);
	}

	public function save() {
		/*
		 * Get posted data from the client
		 */
		$balance = "";
		$facility = $this -> session -> userdata("facility");
		$facility_detail = facilities::getSupplier($facility);
		$supplier_name = $facility_detail -> supplier -> name;
		$get_user = $this -> session -> userdata("user_id");
		$cdrr_id = $this -> input -> post("cdrr_id");
		$get_qty_choice = $this -> input -> post("quantity_choice");
		$get_qty_out_choice = $this -> input -> post("quantity_out_choice");
		$get_source = $this -> input -> post("source");
		$get_source_name = $this -> input -> post("source_name");
		$get_destination_name = $this -> input -> post("destination_name");
		$get_destination = $this -> input -> post("destination");
		$get_transaction_date = date('Y-m-d', strtotime($this -> input -> post("transaction_date")));
		$get_ref_number = $this -> input -> post("reference_number");
		$get_transaction_type = $this -> input -> post("transaction_type");
		$transaction_type_name = $this -> input -> post("trans_type");
		$transaction_effect = $this -> input -> post("trans_effect");
		$get_drug_id = $this -> input -> post("drug_id");
		$get_batch = $this -> input -> post("batch");
		$get_expiry = $this -> input -> post("expiry");
		$get_packs = $this -> input -> post("packs");
		$get_qty = $this -> input -> post("quantity");
		$get_available_qty = $this -> input -> post("available_qty");
		$get_unit_cost = $this -> input -> post("unit_cost");
		$get_amount = $this -> input -> post("amount");
		$get_comment = $this -> input -> post("comment");
		$get_stock_type = $this -> input -> post("stock_type");
		$stock_type_name = $this -> input -> post("stock_transaction"); //Name of kind of transaction being carried
		$all_drugs_supplied = $this -> input -> post("all_drugs_supplied");
		$time_stamp = $this -> input -> post("time_stamp");
		$email=$this->input->post("emailaddress");
		$balance = 0;
		$pharma_balance = 0;
		$store_balance = 0;
		$sql_queries = "";
		$source_destination = $this -> input -> post("source_destination");
		$check_optgroup = $this -> input -> post("optgroup"); //Check if store selected as source or destination
		$source_dest_type = '';
		$running_balance = 0;
		$other_running_balance = 0; //For other store
		
		
		// If email is not empty
		if($email !=""){
			$this->sendemail($email);
		}
		
		
		// STEP 1, GET BALANCES FROM DRUG STOCK BALANCE TABLE
		//Get running balance in drug stock movement
		$sql_run_balance = $this -> db -> query("SELECT machine_code as balance FROM drug_stock_movement WHERE drug ='$get_drug_id' AND ccc_store_sp ='$get_stock_type' AND expiry_date >=CURDATE() ORDER BY id DESC  LIMIT 1");
		
		$run_balance_array = $sql_run_balance ->result_array();
		if (count($run_balance_array) > 0) {
			$run_balance = $run_balance_array[0]["balance"];
		} else {
			//If drug does not exist, initialise the balance to zero
			$run_balance = 0;
		}
		//If transaction has positive effect to current transaction type
		if (stripos($transaction_type_name, "received") === 0 || stripos($transaction_type_name, "balance") === 0 || (stripos($transaction_type_name, "returns") === 0 && $transaction_effect == 1) || (stripos($transaction_type_name, "adjustment") === 0 && $transaction_effect == 1) || stripos($transaction_type_name, "startingstock") === 0 || stripos($transaction_type_name, "physicalcount") === 0) {
			$source_dest_type = $get_source;
			//Get remaining balance for the drug
			$get_balance_sql = $this -> db -> query("SELECT dsb.balance FROM drug_stock_balance dsb  WHERE dsb.facility_code='$facility' AND dsb.stock_type='$get_stock_type' AND dsb.drug_id='$get_drug_id' AND dsb.batch_number='$get_batch' AND dsb.balance>0 AND dsb.expiry_date>=CURDATE() AND dsb.expiry_date='$get_expiry' LIMIT 1");
			$balance_array = $get_balance_sql -> result_array();
			//Check if drug exists in the drug_stock_balance table
			if (count($balance_array) > 0) {
				$bal = $balance_array[0]["balance"];
			} else {
				//If drug does not exist, initialise the balance to zero
				$bal = 0;
			}
			
			
			//If many transactions from the same drug, set balances to zero only once
			if(($this -> session -> userdata("updated_dsb")) && ($this -> session -> userdata("updated_dsb")==$get_drug_id)){
				
			}else{
				//If transaction is physical count, set actual quantity as physical count
				if (stripos($transaction_type_name, "startingstock") === 0 || stripos($transaction_type_name, "physicalcount") === 0) {
					$bal = 0;
					$run_balance = 0;
					//Set all balances fro each batch of the drug to be zero in drug_stock_balance for physical count transaction type
					$sql = "UPDATE drug_stock_balance SET balance =0 WHERE drug_id='$get_drug_id' AND stock_type='$get_stock_type' AND facility_code='$facility'";
					$set_bal_zero = $this -> db -> query($sql);
					$this -> session -> set_userdata("updated_dsb", $get_drug_id);
					
				}
			}
			
			//If stock coming in from another store, get current store 
			if($check_optgroup=='Stores'){
				$source_dest_type = $get_source;
				//If transaction type is returns from(+), 
				if(stripos($transaction_type_name, "returns") === 0 && $transaction_effect == 1){
					$source_dest_type = $get_destination;
				}
				
				//Get remaining balance for the drug
				$get_balance_sql = $this -> db -> query("SELECT dsb.balance FROM drug_stock_balance dsb  
				WHERE dsb.facility_code='$facility' AND dsb.stock_type='".$source_dest_type."' AND dsb.drug_id='$get_drug_id' AND dsb.batch_number='$get_batch' 
				AND dsb.balance>0 AND dsb.expiry_date>=CURDATE() AND dsb.expiry_date='$get_expiry' LIMIT 1");
				$balance_array = $get_balance_sql -> result_array();
				//Check if drug exists in the drug_stock_balance table
				if (count($balance_array) >0) {
					$bal_pharma = $balance_array[0]["balance"];
				} else {
					//If drug does not exist, initialise the balance to zero
					$bal_pharma = 0;
				}
				
				//Get running balance in drug stock movement
				$sql_run_balance = $this -> db -> query("SELECT machine_code as balance FROM drug_stock_movement WHERE drug ='$get_drug_id' AND ccc_store_sp ='$source_dest_type' AND expiry_date >=CURDATE() ORDER BY id DESC  LIMIT 1");
				$run_balance_array = $sql_run_balance ->result_array();
				if (count($run_balance_array) > 0) {
					$other_run_balance = $run_balance_array[0]["balance"];
				} else {
					//If drug does not exist, initialise the balance to zero
					$other_run_balance = 0;
				}
				
				$pharma_balance = $bal_pharma - $get_qty; //New balance
				$other_running_balance = $other_run_balance -$get_qty;
			}
			
			$balance = $get_qty + $bal;  //Current store balance
			$running_balance = $get_qty + $run_balance;

		} else {//If transaction has negative effect (Issuing, returns(-) ...)
			
			//If issuing to a store(Pharmacy or Main Store), get remaining balance in destination
			if($check_optgroup=='Stores'){
				$source_dest_type = $get_destination;
				//If transaction type is returns to(-), get use source instead of destination as where the transaction came from
				if(stripos($transaction_type_name, "returns") === 0 && $transaction_effect == 0){
					$source_dest_type = $get_source;
				}
				
				//Get remaining balance for the drug
				$get_balance_sql = $this -> db -> query("SELECT dsb.balance FROM drug_stock_balance dsb  
				WHERE dsb.facility_code='$facility' AND dsb.stock_type='".$source_dest_type."' AND dsb.drug_id='$get_drug_id' AND dsb.batch_number='$get_batch' 
				AND dsb.balance>0 AND dsb.expiry_date>=CURDATE() AND dsb.expiry_date='$get_expiry' LIMIT 1");
				$balance_array = $get_balance_sql -> result_array();
				//Check if drug exists in the drug_stock_balance table
				if (count($balance_array) >0) {
					$bal_pharma = $balance_array[0]["balance"];
				} else {
					//If drug does not exist, initialise the balance to zero
					$bal_pharma = 0;
				}
				
				//Get running balance in drug stock movement
				$sql_run_balance = $this -> db -> query("SELECT machine_code as balance FROM drug_stock_movement WHERE drug ='$get_drug_id' AND ccc_store_sp ='$source_dest_type' AND expiry_date >=CURDATE() ORDER BY id DESC  LIMIT 1");
				$run_balance_array = $sql_run_balance ->result_array();
				if (count($run_balance_array) > 0) {
					$other_run_balance = $run_balance_array[0]["balance"];
				} else {
					//If drug does not exist, initialise the balance to zero
					$other_run_balance = 0;
				}
				
				$pharma_balance = $bal_pharma + $get_qty; //New balance
				$other_running_balance = $other_run_balance + $get_qty;
			}

			//Substract balance from qty going out
			$balance = $get_available_qty - $get_qty;
			$running_balance = $run_balance - $get_qty;
		}

		/*
		 * Get transaction source and destination depending on type of transaction
		 */
		
		// STEP 2, SET SOURCE AND DESTINATION
		
		//Check if stock type is store or pharmacy
		$s_d = "";
		if($check_optgroup=='Stores'){
			$source_destination = $get_source_name;
			if(stripos($stock_type_name, "pharmacy")){//If pharmacy transaction, source and destinations is facility code
				$source = $facility;
				$destination = $facility;
				
				//Check if transaction is coming in or going out to find what to put in source and destination
				//If transaction is coming, destination is current store
				if($transaction_effect==1){
					$source_destination = $get_source_name;
					if(stripos($transaction_type_name, "returns") === 0 && $transaction_effect == 1){//If transaction is returns from(+), source is current store
						$source_destination = $get_destination_name;
					}
					
				}else if($transaction_effect==0){//If transaction is going out, current store is sources
					$source_destination = $get_destination_name;
					if(stripos($transaction_type_name, "returns") === 0 && $transaction_effect == 0){//If transaction is returns from(-), destination is current store
						$source_destination = $get_source_name;
					}
				}
				else{//Transaction does not have effect ( Error)
					$time = date("Y-m-d H:is:s");
					$error[] = 'An error occured while saving your data ! No transaction effect found! ('.$time.')';
				}
			}	
			elseif(stripos($stock_type_name, "store")){//If store transaction, source or destination is facility code
				//Check if transaction is coming in or going out to find what to put in source and destination
				//If transaction is coming, destination is current store
				if($transaction_effect==1){
					//If transaction is coming in, destination is current store
					$source = $get_source_name;
					$destination = $facility;
					$source_destination = $get_source_name;
					if(stripos($transaction_type_name, "returns") === 0 && $transaction_effect == 1){//If transaction is returns from(+), source is current store
						$source = $facility;
						$destination = $get_destination_name;
						$source_destination = $get_destination_name;
					}
					
				}else if($transaction_effect==0){//If transaction is going out, current store is sources
					$source = $facility;
					$destination = $get_destination_name;
					$source_destination = $get_destination_name;
					if(stripos($transaction_type_name, "returns") === 0 && $transaction_effect == 0){//If transaction is returns from(-), destination is current store
						$source = $get_source_name;
						$destination = $facility;
						$source_destination = $get_source_name;
					}
				}
				else{//Transaction does not have effect ( Error)
					$time = date("Y-m-d H:is:s");
					$error[] = 'An error occured while saving your data ! No transaction effect found! ('.$time.')';
				}
			}
				
		}
		else {
			if(stripos($stock_type_name, "pharmacy")){//If pharmacy transaction, source and destinations is facility code
				$source = $facility;
				$destination = $facility;
				if($transaction_effect==1){
					$source_destination = $get_source;
					$s_d = 's';
					if(stripos($transaction_type_name, "returns") === 0 && $transaction_effect == 1){//If transaction is returns from(+), source is current store
						$source_destination = $get_destination;
						$s_d = 'd';
					}
					
				}else if($transaction_effect==0){//If transaction is going out, current store is sources
					$source_destination = $get_destination;
						$s_d = 'd';
					if(stripos($transaction_type_name, "returns") === 0 && $transaction_effect == 0){//If transaction is returns from(-), destination is current store
						$source_destination = $get_source;
						$s_d = 's';
					}
				}else{//Transaction does not have effect ( Error)
					$time = date("Y-m-d H:is:s");
					$error[] = 'An error occured while saving your data ! No transaction effect found! ('.$time.')';
				}
			}
			elseif(stripos($stock_type_name, "store")){//If store transaction, source or destination is facility code
				if($transaction_effect==1){
					//If transaction is coming in, destination is current store
					$source = $get_source;
					$destination = $facility;
					$source_destination = $get_source;
					$s_d = 's';
					if(stripos($transaction_type_name, "returns") === 0 && $transaction_effect == 1){//If transaction is returns from(+), source is current store
						$source = $facility;
						$destination = $get_destination;
						$source_destination = $get_destination;
						$s_d = 'd';
					}
					
				}else if($transaction_effect==0){//If transaction is going out, current store is sources
					$source = $facility;
					$destination = $get_destination;
					$source_destination = $get_destination;
					$s_d = 'd';
					if(stripos($transaction_type_name, "returns") === 0 && $transaction_effect == 0){//If transaction is returns from(-), destination is current store
						$source = $get_source;
						$destination = $facility;
						$source_destination = $get_source;
						$s_d = 's';
					}
				}else{//Transaction does not have effect ( Error)
					$time = date("Y-m-d H:is:s");
					$error[] = 'An error occured while saving your data ! No transaction effect found! ('.$time.')';
				}
			}
			
		}
		
		//Sanitize by removing (store) or (pharmacy)
		$source_destination = str_ireplace('(store)', '', $source_destination);
		$source_destination = str_ireplace('(pharmacy)', '', $source_destination);
		
		//If source or destination is central site or satellite, insert exact name instead of IDs
		if($check_optgroup=='Central Site' || $check_optgroup=='Satelitte Sites'){
			if($s_d == 'd'){
				$source_destination = $get_destination_name;
			}elseif($s_d == 's'){
				$source_destination = $get_source_name;
			}
		}
		//echo json_encode($running_balance ." -- ".$other_running_balance);die();
		
		//echo json_encode($source_destination);die();
		// STEP 3, INSERT TRANSACTION IN DRUG STOCK MOVEMENT FOR CURRENT STORES
		$drug_stock_mvt_transact = array(
						'drug' =>$get_drug_id,
						'transaction_date'=>$get_transaction_date,
						'batch_number'=>$get_batch,
						'transaction_type'=>$get_transaction_type,
						'source'=>$source,
						'destination'=>$destination,
						'expiry_date'=>$get_expiry,
						'packs'=>$get_packs,
						$get_qty_choice=>$get_qty,
						$get_qty_out_choice=>'0',
						'balance'=>$balance,
						'unit_cost'=>$get_unit_cost,
						'amount'=>$get_amount,
						'remarks'=>$get_comment,
						'operator'=>$get_user,
						'order_number'=>$get_ref_number,
						'facility'=>$facility,
						'Source_Destination'=>$source_destination,
						'timestamp'=>$time_stamp,
						'machine_code' =>$running_balance,
						'ccc_store_sp'=>$get_stock_type
					);
		
					
		$this->db->insert('drug_stock_movement', $drug_stock_mvt_transact);
		
		//check if query inserted
		$inserted = $this->db->affected_rows(); 
		if($inserted<1){//If query did not insert
			$time = date("Y-m-d H:is:s");  
			$errNo   = $this->db->_error_number();
			$errMess = $this->db->_error_message();
			$remaining_drugs = $this -> input -> post("remaining_drugs");
			$error[] = 'An error occured while saving your data(Drug Transaction 1) ! Error  '.$errNo.' : '.$errMess .' ('.$time.')';
			echo json_encode($error);
			die();
		}
		
		//STEP 4, UPDATE DRUG STOCK BALANCE FOR CURRENT STORE
		
		if($transaction_effect==1){
			$balance_sql = "INSERT INTO drug_stock_balance(drug_id,batch_number,expiry_date,stock_type,facility_code,balance,ccc_store_sp) VALUES('" . $get_drug_id . "','" . $get_batch . "','" . $get_expiry . "','" . $get_stock_type . "','" . $facility . "','" . $get_qty . "','".$get_stock_type."') ON DUPLICATE KEY UPDATE balance=balance + " . $get_qty . ";";
			if (stripos($transaction_type_name, "physical")) {//Physical Count
				$balance_sql = "INSERT INTO drug_stock_balance(drug_id,batch_number,expiry_date,stock_type,facility_code,balance,ccc_store_sp) VALUES('" . $get_drug_id . "','" . $get_batch . "','" . $get_expiry . "','" . $get_stock_type . "','" . $facility . "','" . $get_qty . "','".$get_stock_type."') ON DUPLICATE KEY UPDATE balance=" . $get_qty . ";";
				
			}
		}
		else if($transaction_effect==0){
			$balance_sql = "UPDATE drug_stock_balance SET balance=balance - " . $get_qty . " WHERE drug_id='" . $get_drug_id . "' AND batch_number='" . $get_batch . "' AND expiry_date='" . $get_expiry . "' AND stock_type='".$get_stock_type."' AND facility_code='" . $facility . "';";
		}
		$sql_dsb_current_store = $this ->db ->query($balance_sql);
		
		$inserted = $this->db->affected_rows(); 
		if($inserted<1){//If query did not insert
			$time = date("Y-m-d H:is:s");  
			$errNo   = $this->db->_error_number();
			$errMess = $this->db->_error_message();
			$remaining_drugs = $this -> input -> post("remaining_drugs");
			$error[] = 'An error occured while saving your data (Drug Balance)! Error  '.$errNo.' : '.$errMess .' ('.$time.')';
			echo json_encode($error);
			die();
		}
		
		//STEP 5, IF STORE TRANSACTIONS, UPDATE OTHER STORE DETAILS
		
		if($check_optgroup=='Stores'){// If transaction if from one store to another, update drug stock balance in the other store
			
			//STEP 6, UPDATE DRUG STOCK MOVEMENT FOR THE OTHER STORE
			if(stripos($source_destination, "pharmacy")){//If pharmacy transaction, source and destinations is facility code
				$source = $facility;
				$destination = $facility;
			}
			
			$source_destination = $stock_type_name;
			//Get corresponding transaction types
			$sql = "";
			if (stripos($transaction_type_name, "receive") === 0){//If transaction is received, insert an issued to
				$sql = "SELECT id FROM transaction_type WHERE name LIKE '%issued%' LIMIT 1";
			}
			else if(stripos($transaction_type_name, "issued") === 0){//Issued, insert a received
				$sql = "SELECT id FROM transaction_type WHERE name LIKE '%received%' LIMIT 1";
			}
			else if(stripos($transaction_type_name, "returns") === 0 && $transaction_effect == 1){//Returns froms(+), insert an returns to (-)
				$sql= "SELECT id FROM transaction_type WHERE name LIKE '%returns%' AND effect='0' LIMIT 1";
			}
			else if(stripos($transaction_type_name, "returns") === 0 && $transaction_effect == 0){//Returns to(-), insert an returns from (+)
				$sql = "SELECT id FROM transaction_type WHERE name LIKE '%returns%' AND effect='1' LIMIT 1";
			}
			$get_trans_id = $this -> db -> query($sql);
			$get_trans_id = $get_trans_id -> result_array();
			$transaction_type = $get_trans_id[0]['id'];
			
			//Sanitize by removing (store) or (pharmacy)
			$source_destination = str_ireplace('(store)', '', $source_destination);
			$source_destination = str_ireplace('(pharmacy)', '', $source_destination);
			
			$drug_stock_mvt_other_trans = array(
						'drug' =>$get_drug_id,
						'transaction_date'=>$get_transaction_date,
						'batch_number'=>$get_batch,
						'transaction_type'=>$transaction_type,
						'source'=>$source,
						'destination'=>$destination,
						'expiry_date'=>$get_expiry,
						'packs'=>$get_packs,
						$get_qty_choice=>'0',
						$get_qty_out_choice=>$get_qty,
						'balance'=>$pharma_balance,
						'unit_cost'=>$get_unit_cost,
						'amount'=>$get_amount,
						'remarks'=>$get_comment,
						'operator'=>$get_user,
						'order_number'=>$get_ref_number,
						'facility'=>$facility,
						'Source_Destination'=>$source_destination,
						'timestamp'=>$time_stamp,
						'machine_code' =>$other_running_balance,
						'ccc_store_sp'=>$source_dest_type
					);
			
					
			$this->db->insert('drug_stock_movement', $drug_stock_mvt_other_trans);
			//echo json_encode($source_destination);die();
			//check if query inserted
			$inserted = $this->db->affected_rows(); 
			if($inserted<1){//If query did not insert
				$time = date("Y-m-d H:is:s");  
				$errNo   = $this->db->_error_number();
				$errMess = $this->db->_error_message();
				$remaining_drugs = $this -> input -> post("remaining_drugs");
				$error[] = 'An error occured while saving your data(Drug Transaction 2) ! Error  '.$errNo.' : '.$errMess .' ('.$time.')';
				echo json_encode($error);
				die();
			}
			
			//STEP 7, UPDATE DRUG STOCK BALANCE FOR THE OTHER STORE
			
			//If transaction has a positive effect on current store, it will have a negative effect on the other store
			if($transaction_effect==1){
				//If transaction has a positive effect, substract balance in the other store
				$balance_sql = "UPDATE drug_stock_balance SET balance=balance - " . $get_qty . " WHERE drug_id='" . $get_drug_id . "' AND batch_number='" . $get_batch . "' AND expiry_date='" . $get_expiry . "' AND stock_type='".$get_source."' AND facility_code='" . $facility . "';";
				if(stripos($transaction_type_name, "returns") === 0 && $transaction_effect == 1){//If returns from(+), substract from other store
					$balance_sql = "INSERT INTO drug_stock_balance(drug_id,batch_number,expiry_date,stock_type,facility_code,balance,ccc_store_sp) VALUES('" . $get_drug_id . "','" . $get_batch . "','" . $get_expiry . "','" . $get_destination . "','" . $facility . "','" . $get_qty . "','".$get_stock_type."') ON DUPLICATE KEY UPDATE balance=balance - " . $get_qty . ";";
				}
			}
			else if($transaction_effect==0){//If transaction has negative effect, add to balance in the other store
				$balance_sql = "INSERT INTO drug_stock_balance(drug_id,batch_number,expiry_date,stock_type,facility_code,balance,ccc_store_sp) VALUES('" . $get_drug_id . "','" . $get_batch . "','" . $get_expiry . "','" . $get_destination . "','" . $facility . "','" . $get_qty . "','".$get_stock_type."') ON DUPLICATE KEY UPDATE balance=balance + " . $get_qty . ";";
				if(stripos($transaction_type_name, "returns") === 0 && $transaction_effect == 0){//If returns to(-), add to drug stock balance in the other store
					$balance_sql = "UPDATE drug_stock_balance SET balance=balance + " . $get_qty . " WHERE drug_id='" . $get_drug_id . "' AND batch_number='" . $get_batch . "' AND expiry_date='" . $get_expiry . "' AND stock_type='".$get_source."' AND facility_code='" . $facility . "';";
				}
			}			
			$sql_dsb_store = $this ->db ->query($balance_sql);
			$inserted = $this->db->affected_rows(); 
			if($inserted<1){//If query did not insert
				$time = date("Y-m-d H:is:s");  
				$errNo   = $this->db->_error_number();
				$errMess = $this->db->_error_message();
				$remaining_drugs = $this -> input -> post("remaining_drugs");
				$error[] = 'An error occured while saving your data(Drug Balance 2) ! Error  '.$errNo.' : '.$errMess .' ('.$time.')';
				echo json_encode($error);
				die();
			}
			
			
		}
		
		
		//Check if transaction came from picking list and not all drugs where supplied
		iF ($all_drugs_supplied == 0) {
			//Update supplied drugs
			$sql = "UPDATE cdrr_item SET publish='1' WHERE id='$cdrr_id'";
			$this -> db -> query($sql);
		}

		//Get drug_name
		$drug_det = Drugcode::getDrugCodeHydrated($get_drug_id);
		$drug_name = $drug_det[0]['Drug'];
		echo json_encode($drug_name);die();
		
	}
	
	//Print Issue transactions
	public function print_issues(){
	   $source		= $this ->input ->post("source");
	   $destination	= $this ->input ->post("destination");
	   $drug 		= $this ->input ->post("drug");
	   $unit		= $this ->input ->post("unit");
	   $batch		= $this ->input ->post("batch");
	   $pack_size	= $this ->input ->post("pack_size");
	   $expiry		= date('Y-m-d',strtotime($this ->input ->post("expiry")));
	   $pack		= $this ->input ->post("pack");
	   $quantity	= $this ->input ->post("quantity");
	   $counter 	= $this ->input ->post("counter");	
	   $total		= $this ->input ->post("total");	
	   //Build table
	   
	   if($counter==0){
   		
	   	//$this -> mpdf -> addPage();
	   	$string = '<table border="1" align="center" width="100%" style="border-collapse:collapse">
	   				 <caption>Issues to '.strtoupper($destination).'</caption>
	   				<thead>
	   				<tr ><td colspan="7" style="text-align:center;">Drugs Issued from <strong>'.$source.'</strong> to <strong>'.strtoupper($destination).'</strong> on '.date('D j M Y').'</td></tr>
	   				<tr><th>Commodity</th><th>Unit</th><th>PackSize</th><th>BatchNo</th><th>ExpiryDate</th><th>Packs</th><th>Qty</th></tr></thead>
	   				<tbody>
	   				';	
		$this ->session ->set_userdata('string',$string);
				
	   }
	   if($counter==($total-1)){//If las row
   			
   			$string = $this ->session ->userdata('string').'<tr><td>'.$drug.'</td><td>'.$unit.'</td><td>'.$pack_size.'</td><td>'.$batch.'</td><td>'.$expiry.'</td><td>'.$pack.'</td><td>'.$quantity.'</td></tr>
   					</tbody>
   				</table>';
			//write to page
			//echo $string;die();
			$this -> load -> library('mpdf');
			$this -> mpdf = new mPDF('c','B4');
			$this -> mpdf ->ignore_invalid_utf8 = true;
			$this -> mpdf ->simpleTables = true;
		   	$this -> mpdf ->WriteHTML($string);
			$this->mpdf->SetFooter("{DATE D j M Y }|{PAGENO}/{nb}| Issues_".date('U')."  , source Web ADT");
		   	
			$file_name='Export/Issues_'.date('U').'.pdf';
			$this -> mpdf -> Output($file_name, 'F');
			echo (base_url().$file_name);
			die();
   		}else{
   			$string = $this ->session ->userdata('string').'<tr><td>'.$drug.'</td><td>'.$unit.'</td><td>'.$pack_size.'</td><td>'.$batch.'</td><td>'.$expiry.'</td><td>'.$pack.'</td><td>'.$quantity.'</td></tr>';
   			$this ->session ->set_userdata('string',$string);
		}
		echo json_encode($counter.'-'.$total);
		die();

	   
	}

	public function set_transaction_session() {
		$drugs_transacted = $this -> input -> post("list_drugs_transacted");
		$remaining_drugs = $this -> input -> post("remaining_drugs");
		//$this->session->set_userdata('filter_datatable',$drugs_transacted);
		if ($remaining_drugs == 0) {
			$this -> session -> set_userdata("msg_save_transaction", "success");
			$this -> session -> unset_userdata("updated_dsb");
		} else {
			$this -> session -> set_userdata("msg_save_transaction", "failure");
		}
	}

	public function save_edit() {
		$sql = $this -> input -> post("sql");
		$queries = explode(";", $sql);
		foreach ($queries as $query) {
			if (strlen($query) > 0) {
				$this -> db -> query($query);
			}

		}
	}

	public function getDrugsBatches($drug) {
		$today = date('Y-m-d');
		$sql = "select drug_stock_balance.batch_number,drug_unit.Name as unit,dose.Name as dose,drugcode.quantity,drugcode.duration from drug_stock_balance,drugcode,drug_unit,dose where drug_id='$drug' and drugcode.id=drug_stock_balance.drug_id  and drug_unit.id=drugcode.unit and dose.id= drugcode.dose and expiry_date>'$today' and balance>0 group by batch_number order by drug_stock_balance.expiry_date asc";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		if ($results) {
			echo json_encode($results);
		}
	}

	public function getAllDrugsBatches($drug) {
		$today = date('Y-m-d');
		$sql = "select drug_stock_balance.batch_number,drug_unit.Name as unit,dose.Name as dose,drugcode.quantity,drugcode.duration from drug_stock_balance,drugcode,drug_unit,dose where drug_id='$drug' and drugcode.id=drug_stock_balance.drug_id  and drug_unit.id=drugcode.unit and dose.id= drugcode.dose group by batch_number order by drug_stock_balance.expiry_date asc";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		if ($results) {
			echo json_encode($results);
		}
	}

	public function getBatchInfo($drug, $batch) {
		$sql = "select * from drug_stock_balance where drug_id='$drug' and batch_number='$batch'";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		if ($results) {
			echo json_encode($results);
		}
	}

	public function getDrugsBrands($drug) {
		$sql = "select * from brand where drug_id='$drug' group by brand";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		if ($results) {
			echo json_encode($results);
		}
	}

	//Get orders for a picking list
	public function getOrderDetails() {
		$order_id = $this -> input -> post("order_id");
		$sql = $this -> db -> query("SELECT ci.id as cdrr_id,dc.id,u.Name as unit,dc.pack_size,ci.drug_id,ci.newresupply,ci.resupply FROM cdrr_item ci LEFT JOIN drugcode dc ON dc.drug=ci.drug_id LEFT JOIN facility_order fo ON fo.unique_id=ci.cdrr_id LEFT JOIN drug_unit u ON dc.unit=u.id  WHERE fo.id='$order_id' AND ci.publish=0");
		$order_list = $sql -> result_array();
		echo json_encode($order_list);
	}

	//Set order status
	public function set_order_status() {
		$order_id = $this -> input -> post("order_id");
		$status = $this -> input -> post("status");
		$updated_on = date("U");
		$this -> db -> query("UPDATE facility_order SET status='$status',updated='$updated_on' WHERE id='$order_id'");

	}

	public function base_params($data) {
		$data['title'] = "webADT | Inventory";
		$data['banner_text'] = "Inventory Management";
		$data['link'] = "inventory";
		$this -> load -> view('template', $data);
	}

}
?>