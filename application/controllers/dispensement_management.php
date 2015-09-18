<?php
class Dispensement_Management extends MY_Controller {
	function __construct() {
		parent::__construct();

		$this -> load -> database();
	//$this->output->enable_profiler(TRUE);
	}

	public function index() {
		//$this -> listing();
	}
	public function trialdispense($patientID)
	{
		$this->load->model('patientmodel');
		print_r($this->patientmodel->get_patient_details($patientID));
	}
	public function dispense($record_no) {
		
		//$this->db->save_queries = FALSE;
		$facility_code = $this -> session -> userdata('facility');
                
		$dispensing_date = "";
		$data['last_regimens'] = "";
		$data['visits'] = "";
		$data['appointments'] = "";
		$dispensing_date = date('Y-m-d');

		$sql = "select ps.name as patient_source,p.patient_number_ccc,FLOOR(DATEDIFF(CURDATE(),p.dob)/365) as age from patient p 
				LEFT JOIN patient_source ps ON ps.id = p.source
				where p.id='$record_no' and facility_code='$facility_code'
				";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		       
		if ($results) {
			$patient_no = $results[0]['patient_number_ccc'];
			$age=@$results[0]['age'];
			$data['results'] = $results;
	
		}

		/***********/
		/*$sql = "SELECT r.id,
		               r.regimen_desc,
                       r.regimen_code,
                       pv.dispensing_date,
                       pv.current_weight,
                       pv.current_height,
                       v.name as visit_purpose_name
                FROM patient_visit pv
                LEFT JOIN regimen r ON r.id = pv.regimen
                LEFT JOIN visit_purpose v ON v.id = pv.visit_purpose
                WHERE pv.patient_id =  '$patient_no'
                ORDER BY pv.dispensing_date DESC";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		if ($results) {
			$data['last_regimens'] = $results[0];
			$dispensing_date = $results[0]['dispensing_date'];

			//Check if patient had startART or enrollment purpose in previous visits
			$enrollment_check = 0;
			$start_art_check = 0;
			foreach($results as $result)
			{ 
				$visit_purpose = strtolower($result['visit_purpose_name']);
				
				if (strpos($visit_purpose,'startart') !== false)
				{
                   $start_art_check = 1;
				} 
				if(strpos($visit_purpose,'enrollment') !== false) {
				    $enrollment_check = 1;
				}
			}

			$data['purposes'] = Visit_Purpose::getFiltered($enrollment_check,$start_art_check);
		}else{
			$data['purposes'] = Visit_Purpose::getAll();
		}

		$sql = "SELECT DISTINCT(d.drug),
		               pv.quantity,
		               pv.pill_count,
		               pv.months_of_stock as mos,
		               pv.drug_id,
		               pv.dispensing_date,
		               ds.value,
		               ds.frequency,
		               pv.dose,
		               pv.duration
		        FROM v_patient_visits pv
		        LEFT JOIN drugcode d 
		                    ON d.id=pv.drug_id
		        LEFT JOIN dose ds 
		                    ON ds.Name=d.dose 
		        WHERE pv.patient_id = '$patient_no' 
		        AND pv.dispensing_date = '$dispensing_date' 
		        ORDER BY pv.id DESC";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		$data['prev_visit'] = "";
		if ($results) {
			$data['visits'] = $results;//Get latest dispensed drug;
			$data['prev_visit'] = json_encode($results);
		}
        $sql="UPDATE drugcode SET quantity='' WHERE quantity=0";
        $this->db->query($sql);
		$sql = "SELECT appointment "
                        . "FROM patient_appointment pa "
                        . "WHERE pa.patient = '$patient_no' "
                        . "AND pa.facility =  '$facility_code' "
                        . "ORDER BY appointment DESC LIMIT 1";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		if ($results) {
			$data['appointments'] = $results[0];
		}

		$data['facility'] = $facility_code;
		$data['user'] = $this -> session -> userdata('full_name');

		if($age==''){
		   $data['regimens'] = Regimen::getRegimens();
		}else{
			if($age>=15){
				//adult regimens
				$data['regimens']=Regimen::getAdultRegimens();
			}else if($age<15){
				//paediatric regimens
				$data['regimens']=Regimen::getChildRegimens();
			}
		}
*/
		/*************/
		$sql1="SELECT dispensing_date FROM patient_visit pv WHERE pv.patient_id =  '$patient_no' AND pv.active=1 ORDER BY dispensing_date DESC LIMIT 1";
		$query = $this -> db -> query($sql1);
		$results1 = $query -> row_array();
		
		
		$dated=$results1['dispensing_date'];
		
		//die();

		$sql = "SELECT d.id as drug_id,d.drug,d.dose,d.duration, pv.quantity,pv.dispensing_date,pv.pill_count,r.id as regimen_id,r.regimen_desc,r.regimen_code,pv.months_of_stock as mos,ds.value,ds.frequency
					FROM patient_visit pv
					LEFT JOIN drugcode d ON d.id = pv.drug_id
					LEFT JOIN dose ds ON ds.Name=d.dose
					LEFT JOIN regimen r ON r.id = pv.regimen
					WHERE pv.patient_id =  '$patient_no'
					AND pv.active=1
					AND pv.dispensing_date = '$dated'
				ORDER BY dispensing_date DESC";	
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		//getPreviouslyDispensedDrugs();
		$data = array();
		$data['non_adherence_reasons'] = Non_Adherence_Reasons::getAllHydrated();
		$data['regimen_changes'] = Regimen_Change_Purpose::getAllHydrated();
		$data['purposes'] = Visit_Purpose::getAll();
		$data['dated']=$dated;
		
		$data['patient_id'] = $record_no; 
		$data['purposes'] = Visit_Purpose::getAll();
		$data['patient_appointment']=$results;
		
		$data['hide_side_menu'] = 1;
		$data['content_view'] = "patients/dispense_v";
		$this -> base_params($data);
                
        
	}
	
	public function get_other_dispensing_details(){
		$data  = array();
		$patient_ccc = $this ->input ->post("patient_ccc");
		$data['non_adherence_reasons'] = Non_Adherence_Reasons::getAllHydrated();
		$data['regimen_changes'] = Regimen_Change_Purpose::getAllHydrated();
		$data['patient_appointment']=Patient_appointment::getAppointmentDate($patient_ccc);
		
		echo json_encode($data);
	}

// 	public function getPreviouslyDispensedDrugs(){
//       $patient_ccc = $this ->input ->post("patient_ccc");
// 		//$patient_ccc=0887679;
// 		$sql1="SELECT dispensing_date FROM patient_visit WHERE patient_id =  '$patient_ccc' AND active=1 ORDER BY dispensing_date DESC LIMIT 1";
// 		$query = $this -> db -> query($sql1);
// 		$results1 = $query -> result_array();
		
		
// $dated=$results1[];
// 		echo $dated;
// 	die();

// 		$sql = "SELECT d.id as drug_id,d.drug,d.dose,d.duration, pv.quantity,pv.dispensing_date,pv.pill_count,r.id as regimen_id,r.regimen_desc,r.regimen_code,pv.months_of_stock as mos,ds.value,ds.frequency
// 					FROM patient_visit pv
// 					LEFT JOIN drugcode d ON d.id = pv.drug_id
// 					LEFT JOIN dose ds ON ds.Name=d.dose
// 					LEFT JOIN regimen r ON r.id = pv.regimen
// 					WHERE pv.patient_id =  '$patient_ccc'
// 					AND pv.active=1
// 					AND pv.dispensing_date = '$dated'
// 				ORDER BY dispensing_date DESC";	

// 		$query = $this -> db -> query($sql);
// 		$results = $query -> result_array();
// 		echo json_encode($results);
// 		die();
// 	} 

	public function getPreviouslyDispensedDrugs(){
		$patient_ccc = $this ->input ->post("patient_ccc");
		//$patient_ccc=1088816;
		$sql = "SELECT d.id as drug_id,d.drug,d.dose,d.duration, pv.quantity,pv.dispensing_date,pv.pill_count,r.id as regimen_id,r.regimen_desc,r.regimen_code,pv.months_of_stock as mos,ds.value,ds.frequency
					FROM patient_visit pv
					LEFT JOIN drugcode d ON d.id = pv.drug_id
					LEFT JOIN dose ds ON ds.Name=d.dose
					LEFT JOIN regimen r ON r.id = pv.regimen
					WHERE pv.patient_id =  '$patient_ccc'
					AND pv.active=1
					AND pv.dispensing_date = (SELECT dispensing_date FROM patient_visit pv WHERE pv.patient_id =  '$patient_ccc' AND pv.active=1 ORDER BY dispensing_date DESC LIMIT 1)
				ORDER BY dispensing_date DESC";	
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		echo json_encode($results);
	} 
	//Get list of drugs for a specific regimen
	public function getDrugsRegimens() {
		 $regimen_id = $this -> input -> post('selected_regimen');
		$and_stocktype = "";
		if ($this -> input -> post('stock_type')) {
			$stock_type = $this -> input -> post('stock_type');
			$and_stocktype = "AND dsb.stock_type = '$stock_type' ";
		}  
		$sql = "SELECT DISTINCT(d.id),UPPER(d.drug) as drug
		        FROM regimen_drug rd
		        LEFT JOIN regimen r ON r.id = rd.regimen 
		        LEFT JOIN drugcode d ON d.id=rd.drugcode 
				WHERE d.enabled='1'
				AND (rd.regimen='" . $regimen_id . "' OR r.regimen_code LIKE '%oi%')  
				ORDER BY d.drug asc";
		$get_drugs_sql = $this -> db -> query($sql);
		$get_drugs_array = $get_drugs_sql -> result_array();
		echo json_encode($get_drugs_array);

//die();
	}

        public function getBrands() {
		$drug_id = $this -> input -> post("selected_drug");
		$get_drugs_sql = $this -> db -> query("SELECT DISTINCT id,brand FROM brand WHERE drug_id='" . $drug_id . "' AND brand!=''");
		$get_drugs_array = $get_drugs_sql -> result_array();
		echo json_encode($get_drugs_array);
	}

	public function getDoses() {
		$get_doses_sql = $this -> db -> query("SELECT id,Name,value,frequency FROM dose");
		$get_doses_array = $get_doses_sql -> result_array();
		echo json_encode($get_doses_array);
	}

//function to return drugs on the sync_drugs
	public function getMappedDrugCode(){
		$drug_id = $this -> input -> post("selected_drug");
		$get_drugcode_sql = $this -> db ->query("SELECT map FROM drugcode WHERE id='".$drug_id."' ");
		$get_drugcode_array = $get_drugcode_sql -> result_array();
		echo json_encode($get_drugcode_array);
	}



	public function getIndications() {
		$drug_id = $this -> input -> post("drug_id");
		$get_indication_array=array();
		$sql="SELECT * 
		      FROM regimen_drug rd
		      LEFT JOIN regimen r ON r.id=rd.regimen
		      WHERE rd.drugcode='$drug_id'
		      AND r.regimen_code LIKE '%oi%'";
        $query=$this->db->query($sql);
        $results=$query->result_array();
        //if drug is an OI show indications
		if($results){
			$get_indication_sql = $this -> db -> query("SELECT id,Name,Indication FROM opportunistic_infection where active='1'");
			$get_indication_array = $get_indication_sql -> result_array();
	    }
		echo json_encode($get_indication_array);
	}

	public function edit($record_no) {
		$facility_code = $this -> session -> userdata('facility');
		$ccc_id ='2';
		 $sql = "select pv.*,p.first_name,p.other_name,p.last_name,p.id as p_id "
                        . "from patient_visit pv,"
                        . "patient p "
                        . "where pv.id='$record_no' "
                        . "and pv.patient_id=p.patient_number_ccc "
                        . "and facility='$facility_code'";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
                //print_r($results);
		if ($results) {
			$data['results'] = $results;
			//Get expriry date the batch
			foreach ($results as $value) {
				$batch_number = $value['batch_number'];
				$drug_ig = $value['drug_id'];
				$ccc_id = $value['ccc_store_sp'];
				$sql = "select expiry_date FROM drug_stock_balance WHERE batch_number='$batch_number' AND drug_id='$drug_ig' AND stock_type='$ccc_id' AND facility_code='$facility_code' LIMIT 1"; 
				$expiry_sql = $this -> db -> query($sql);

				$expiry_array = $expiry_sql -> result_array();
				$expiry_date = "";
				$data['expiries'] = $expiry_array;
				foreach ($expiry_array as $row) {
					$expiry_date = $row['expiry_date'];
                                        //print_r($expiry_date);
					$data['original_expiry_date'] = $expiry_date;
				}
			}

		} 
		else {
			$data['results'] = "";
		}
		$data['purposes'] = Visit_Purpose::getAll();
		$data['record'] = $record_no;
		$data['ccc_id'] = $ccc_id;
		$data['regimens'] = Regimen::getRegimens();
		$data['non_adherence_reasons'] = Non_Adherence_Reasons::getAllHydrated();
		$data['regimen_changes'] = Regimen_Change_Purpose::getAllHydrated();
		$data['doses'] = Dose::getAllActive();
		$data['indications'] = Opportunistic_Infection::getAllHydrated();
		$data['content_view'] = 'edit_dispensing_v';
		$data['hide_side_menu'] = 1;
		$this -> base_params($data);
	}

	public function save() {
		$period = date("M-Y");
		$ccc_id = $this -> input -> post("ccc_store_id");
        $this -> session -> set_userdata('ccc_store_id',$ccc_id);
        $record_no = $this -> session -> userdata('record_no');
		$patient_name= $this -> input -> post("patient_details");
		$next_appointment_date = $this -> input -> post("next_appointment_date");
		$last_appointment_date = $this -> input -> post("last_appointment_date");
		$last_appointment_date = date('Y-m-d', strtotime($last_appointment_date));
		$dispensing_date = $this -> input -> post("dispensing_date");
		$dispensing_date_timestamp = date('U', strtotime($dispensing_date));
		$facility = $this -> session -> userdata("facility");
		$patient = $this -> input -> post("patient");
		$height = $this -> input -> post("height");
		$current_regimen = $this -> input -> post("current_regimen");
		$drugs = $this -> input -> post("drug");
		$unit = $this -> input -> post("unit");
		$batch = $this -> input -> post("batch");
		$expiry = $this -> input -> post("expiry");
		$dose = $this -> input -> post("dose");
		$duration = $this -> input -> post("duration");
		$quantity = $this -> input -> post("qty_disp");
		$qty_available = $this -> input -> post("soh");
		$brand = $this -> input -> post("brand");
		$soh = $this -> input -> post("soh");
		$indication = $this -> input -> post("indication");
		$mos = $this -> input -> post("next_pill_count");
		//Actual Pill Count
		$pill_count = $this -> input -> post("pill_count");
		$comment = $this -> input -> post("comment");
		$missed_pill = $this -> input -> post("missed_pills");
		$purpose = $this -> input -> post("purpose");
		$purpose_refill_text = $this ->input ->post('purpose_refill_text');
		$weight = $this -> input -> post("weight");
		$last_regimen = $this -> input -> post("last_regimen");
		$regimen_change_reason = $this -> input -> post("regimen_change_reason");
		$non_adherence_reasons = $this -> input -> post("non_adherence_reasons");
		$patient_source = strtolower($this -> input -> post("patient_source"));
		$timestamp = date('U');
		$period = date("Y-m-01");
		$user = $this -> session -> userdata("username");
		$adherence = $this -> input -> post("adherence");
		
		$stock_type_text = $this -> input -> post("stock_type_text");
                
        //update service type
        $sql_get_service="SELECT type_of_service FROM regimen WHERE id='$current_regimen'";
        $results=  $this->db->query($sql_get_service);
        $res=$results->result_array();
        $service=$res[0]['type_of_service'];
        $sql_get_patient_service="SELECT service FROM patient WHERE patient_number_ccc='$patient'";
        $service_results=  $this->db->query($sql_get_patient_service);
        $service_res=$service_results->result_array();
        $patient_service=$service_res[0]['service'];
        
        if($patient_service!=$service){
            $sql="UPDATE patient SET service='$service' WHERE service='$patient_service' AND patient_number_ccc='$patient'";
            $this->db->query($sql);
        }
        //end update service type
		//echo var_dump($dose);die();
		
		//Get transaction type
		$transaction_type = transaction_type::getTransactionType("dispense", "0");
		$transaction_type = $transaction_type -> id;
		//Source destination
		$source='';
		$destination='';
		//Source and destination depending on the stock type
		if(stripos($stock_type_text,'store')){
			$source = $facility;
			$destination = '0';
		}elseif(stripos($stock_type_text,'pharmacy')){
			$source = $facility;
			$destination = $facility;
		}
		
		/*
		 * Update Appointment Info
		 */
		$sql="";
		$add_query = "";
		//If purpose of refill is start ART, update start regimen and start regimen date
		if($purpose_refill_text=="start art"){
			$add_query = " , start_regimen = '$current_regimen',start_regimen_date = '$dispensing_date' ";
		}
		
		$trans_id = '';
		$status_add = ' '; 
		if(stripos($patient_source,'transit')===0){//If patient is on transit, change his status
			$get_status = "SELECT id,name FROM patient_status WHERE name LIKE '%transit%' LIMIT 1";
			$q = $this ->db ->query($get_status);
			$result = $q ->result_array();
			$trans_id = $result[0]['id'];
			$add_query.= ", current_status = '$trans_id' ";
		}
		
		if ($last_appointment_date) {
			if ($last_appointment_date > $dispensing_date) {
				//come early for appointment
				$sql .= "update patient_appointment set appointment='$dispensing_date',machine_code='1' where patient='$patient' and appointment='$last_appointment_date';";
			}
		}
		$sql .= "insert into patient_appointment (patient,appointment,facility) values ('$patient','$next_appointment_date','$facility');";


		/*
		 * Update patient Info
		 */

		$sql .= "update patient SET weight='$weight',height='$height',current_regimen='$current_regimen',nextappointment='$next_appointment_date' $add_query where patient_number_ccc ='$patient' and facility_code='$facility';";

		/*
		 * Update Visit and Drug Info
		 */

		for ($i = 0; $i < sizeof($drugs); $i++) {
			//Get running balance in drug stock movement
			$sql_run_balance = $this -> db -> query("SELECT machine_code as balance FROM drug_stock_movement WHERE drug ='$drugs[$i]' AND ccc_store_sp ='$ccc_id' AND expiry_date >=CURDATE() ORDER BY id DESC  LIMIT 1");
			$run_balance_array = $sql_run_balance ->result_array();
			if (count($run_balance_array) > 0) {
				$prev_run_balance = $run_balance_array[0]["balance"];
			} else {
				//If drug does not exist, initialise the balance to zero
				$prev_run_balance = 0;
			}
			$act_run_balance = $prev_run_balance - $quantity[$i];
			//Get running balance in drug stock movement end ---------
			
			$remaining_balance = $soh[$i] - $quantity[$i];
			if ($pill_count[$i] == '') {
				$pill_count[$i] = $mos[$i];
			}
			/*if ($mos != "") {//If transaction has actual pill count, actual pill count will pill count + amount dispensed
			 $mos[$i] = $quantity[$i] + (int)$mos[$i];
			 }*/
			
			
			$sql .= "insert into patient_visit (patient_id, visit_purpose, current_height, current_weight, regimen, regimen_change_reason,last_regimen, drug_id, batch_number, brand, indication, pill_count, comment, `timestamp`, user, facility, dose, dispensing_date, dispensing_date_timestamp,quantity,duration,adherence,missed_pills,non_adherence_reason,months_of_stock,ccc_store_sp) VALUES ('$patient','$purpose', '$height', '$weight', '$current_regimen', '$regimen_change_reason',$last_regimen ,'$drugs[$i]', '$batch[$i]', '$brand[$i]', '$indication[$i]', '$pill_count[$i]','$comment[$i]', '$timestamp', '$user','$facility', '$dose[$i]','$dispensing_date', '$dispensing_date_timestamp','$quantity[$i]','$duration[$i]','$adherence','$missed_pill[$i]','$non_adherence_reasons','$mos[$i]','$ccc_id');";
			$sql .= "insert into drug_stock_movement (drug, transaction_date, batch_number, transaction_type,source,destination,expiry_date,quantity, quantity_out,balance, facility,`timestamp`,machine_code,ccc_store_sp) VALUES ('$drugs[$i]','$dispensing_date','$batch[$i]','$transaction_type','$source','$destination','$expiry[$i]',0,'$quantity[$i]',$remaining_balance,'$facility','$dispensing_date_timestamp','$act_run_balance','$ccc_id');";
			$sql .= "update drug_stock_balance SET balance=balance - '$quantity[$i]' WHERE drug_id='$drugs[$i]' AND batch_number='$batch[$i]' AND expiry_date='$expiry[$i]' AND stock_type='$ccc_id' AND facility_code='$facility';";
			$sql .= "INSERT INTO drug_cons_balance(drug_id,stock_type,period,facility,amount,ccc_store_sp) VALUES('$drugs[$i]','$ccc_id','$period','$facility','$quantity[$i]','$ccc_id') ON DUPLICATE KEY UPDATE amount=amount+'$quantity[$i]';";

		}
		$queries = explode(";", $sql);
		$count = count($queries);
		$c = 0;
		foreach ($queries as $query) {
			//$c++;
			//if (strlen($query) > 0) {
				$this -> db -> query($query);
			//}

		}

		$this -> session -> set_userdata('msg_save_transaction', 'success');
        $this -> session -> set_flashdata('dispense_updated', 'Dispensing to patient No. ' . $patient . ' successfully completed!');
		redirect("patient_management");
	}

	public function save_edit() {
		$timestamp = "";
		$patient = "";
		$facility = "";
		$user = "";
		$record_no = "";
		$soh = $this -> input -> post("soh");
		//Get transaction type
		$transaction_type = transaction_type::getTransactionType("dispense", "0");
		$transaction_type = $transaction_type -> id;
		$transaction_type1 = transaction_type::getTransactionType("returns", "1");
		$transaction_type1 = $transaction_type1 -> id;
		$original_qty = @$_POST["qty_hidden"];
		$facility = $this -> session -> userdata("facility");
		$user = $this -> session -> userdata("full_name");
		$timestamp = date('Y-m-d H:i:s');
		$patient = @$_POST['patient'];
		$expiry_date = @$_POST['expiry'];
		$ccc_id = @$_POST["ccc_id"];
		
		//Define source and destination
		$source = $facility;
		$destination = $facility;
		
		//Get ccc_store_name 
		$ccc_store = CCC_store_service_point::getCCC($ccc_id);
		$ccc_name = $ccc_store ->Name;
		
		if(stripos($ccc_name,'store')){
			$source = $facility;
			$destination = '';
		}
		
		//Get running balance in drug stock movement
		$sql_run_balance = $this -> db -> query("SELECT machine_code as balance FROM drug_stock_movement WHERE drug ='".@$_POST['original_drug']."' AND ccc_store_sp ='$ccc_id' AND expiry_date >=CURDATE() ORDER BY id DESC  LIMIT 1");
		$run_balance_array = $sql_run_balance ->result_array();
		if (count($run_balance_array) > 0) {
			$prev_run_balance = $run_balance_array[0]["balance"];
		} else {
			//If drug does not exist, initialise the balance to zero
			$prev_run_balance = 0;
		}
		
		//Get running balance in drug stock movement end ---------
		
		//If record is to be deleted
		if (@$_POST['delete_trigger'] == 1) {
			$sql = "update patient_visit set active='0' WHERE id='" . @$_POST["dispensing_id"] . "';";
			$this -> db -> query($sql);
			$bal = $soh + @$_POST["qty_disp"];
			
			$act_run_balance = $prev_run_balance + @$_POST["qty_disp"];//Actual running balance		
			
			//If deleting previous transaction, check if batch has not expired, if not, insert in drug stock balance table
			$today = strtotime(date("Y-m-d"));
			$original_expiry = strtotime(@$_POST["original_expiry_date"]);
			if($today<=$original_expiry){
				//If balance for this batch is greater than zero, update stock, otherwise, insert in drug stock balance
				$sql_batch_balance = "SELECT balance FROM drug_stock_balance WHERE drug_id='" . @$_POST["original_drug"] . "' AND batch_number='" . @$_POST["batch"] . "' AND expiry_date='" . @$_POST["original_expiry_date"] . "' AND stock_type='$ccc_id' AND facility_code='$facility'";
				$query = $this -> db -> query($sql_batch_balance);
				$res =$query->result_array();
				$prev_batch_balance = "";
				if($res){
					$prev_batch_balance = $res[0]['balance'];
				}
				if($prev_batch_balance>0){
					//Update drug_stock_balance
					$sql = "UPDATE drug_stock_balance SET balance=balance+" . @$_POST["qty_disp"] . " WHERE drug_id='" . @$_POST["original_drug"] . "' AND batch_number='" . @$_POST["batch"] . "' AND expiry_date='" . @$_POST["original_expiry_date"] . "' AND stock_type='$ccc_id' AND facility_code='$facility'";
					//echo $sql;die();
					$this -> db -> query($sql);
				}else{
					
					$sql = "INSERT INTO drug_stock_balance (balance,dug_id,batch_number,expiry_date,stock_type,facility_code) VALUES('" . @$_POST["qty_disp"] . "','" . @$_POST["original_drug"] . "','" . @$_POST["batch"] . "','" . @$_POST["original_expiry_date"] . "','$ccc_id','$facility')";
					//echo $sql;die();
					$this -> db -> query($sql);
				}
			}
			

			//Insert in drug stock movement
			//Get balance after update
			$sql = "SELECT balance FROM drug_stock_balance WHERE drug_id='" . @$_POST["original_drug"] . "' AND batch_number='" . @$_POST["batch"] . "' AND expiry_date='" . @$_POST["original_expiry_date"] . "' AND stock_type='$ccc_id' AND facility_code='$facility'";
			$query = $this -> db -> query($sql);
			$results = $query -> result_array();
			$actual_balance = $results[0]['balance'];
			$sql = "INSERT INTO drug_stock_movement (drug, transaction_date, batch_number, transaction_type,source,destination,source_destination,expiry_date, quantity, balance, facility, machine_code,timestamp,ccc_store_sp) SELECT '" . @$_POST["original_drug"] . "','" . @$_POST["original_dispensing_date"] . "', '" . @$_POST["batch"] . "','$transaction_type1','$source','$destination','Dispensed To Patients','$expiry_date','" . @$_POST["qty_disp"] . "','" . @$actual_balance . "','$facility','$act_run_balance','$timestamp','$ccc_id' from drug_stock_movement WHERE batch_number= '" . @$_POST["batch"] . "' AND drug='" . @$_POST["original_drug"] . "' LIMIT 1;";
			$this -> db -> query($sql);

			//Update drug consumption
			$period = date('Y-m-01');
			$sql = "UPDATE drug_cons_balance SET amount=amount-" . $original_qty . " WHERE drug_id='" . @$_POST["original_drug"] . "' AND stock_type='$ccc_id' AND period='$period' AND facility='$facility'";
			$this -> db -> query($sql);

			$this -> session -> set_userdata('dispense_deleted', 'success');
		} else {//If record is edited

			$period = date('Y-m-01');
			$sql = "UPDATE patient_visit SET dispensing_date = '" . @$_POST["dispensing_date"] . "', visit_purpose = '" . @$_POST["purpose"] . "', current_weight='" . @$_POST["weight"] . "', current_height='" . @$_POST["height"] . "', regimen='" . @$_POST["current_regimen"] . "', drug_id='" . @$_POST["drug"] . "', batch_number='" . @$_POST["batch"] . "', dose='" . @$_POST["dose"] . "', duration='" . @$_POST["duration"] . "', quantity='" . @$_POST["qty_disp"] . "', brand='" . @$_POST["brand"] . "', indication='" . @$_POST["indication"] . "', pill_count='" . @$_POST["pill_count"] . "', missed_pills='" . @$_POST["missed_pills"] . "', comment='" . @$_POST["comment"] . "',non_adherence_reason='" . @$_POST["non_adherence_reasons"] . "',adherence='" . @$_POST["adherence"] . "' WHERE id='" . @$_POST["dispensing_id"] . "';";
			$this -> db -> query($sql);
			if (@$_POST["batch"] != @$_POST["batch_hidden"] || @$_POST["qty_disp"] != @$_POST["qty_hidden"]) {
				//Update drug_stock_balance
				//Balance=balance+(previous_qty_disp-actual_qty_dispense)
				$bal = $soh;
				//New qty dispensed=old qty - actual qty dispensed
				$new_qty_dispensed = $_POST["qty_hidden"] - $_POST["qty_disp"];
				$act_run_balance = $prev_run_balance - $_POST["qty_disp"];
				//If new quantity dispensed is less than qty previously dispensed
				//echo $new_qty_dispensed;die();
				if ($new_qty_dispensed > 0) {
					$bal = $soh + $new_qty_dispensed;
					$sql = "UPDATE drug_stock_balance SET balance=balance+" . @$new_qty_dispensed . " WHERE drug_id='" . @$_POST["original_drug"] . "' AND batch_number='" . @$_POST["batch"] . "' AND expiry_date='" . @$_POST["original_expiry_date"] . "' AND stock_type='$ccc_id' AND facility_code='$facility'";
					$this -> db -> query($sql);

					//Update drug consumption
					$sql = "UPDATE drug_cons_balance SET amount=amount-" . $new_qty_dispensed . " WHERE drug_id='" . @$_POST["original_drug"] . "' AND stock_type='$ccc_id' AND period='$period' AND facility='$facility'";
					$this -> db -> query($sql);

				} else if ($new_qty_dispensed < 0) {
					$bal = $soh - $new_qty_dispensed;
					$new_qty_dispensed = abs($new_qty_dispensed);
					$sql = "UPDATE drug_stock_balance SET balance=balance-" . @$new_qty_dispensed . " WHERE drug_id='" . @$_POST["original_drug"] . "' AND batch_number='" . @$_POST["batch"] . "' AND expiry_date='" . @$_POST["original_expiry_date"] . "' AND stock_type='$ccc_id' AND facility_code='$facility'";
					$this -> db -> query($sql);

					//Update drug consumption
					$sql = "UPDATE drug_cons_balance SET amount=amount+" . $new_qty_dispensed . " WHERE drug_id='" . @$_POST["original_drug"] . "' AND stock_type='$ccc_id' AND period='$period' AND facility='$facility'";
					$this -> db -> query($sql);
				}
				//Balance after returns
				$bal1 = $soh + $original_qty;
				$act_run_balance1 = $prev_run_balance + $original_qty;//Actual running balance
				$act_run_balance = $act_run_balance + $original_qty;
				//Returns transaction
				$sql = "INSERT INTO drug_stock_movement (drug, transaction_date, batch_number, transaction_type,source,destination,source_destination,expiry_date, quantity,balance, facility, machine_code,timestamp,ccc_store_sp) SELECT '" . @$_POST["original_drug"] . "','" . @$_POST["original_dispensing_date"] . "', '" . @$_POST["batch_hidden"] . "','$transaction_type1','$source','$destination','Dispensed To Patients',expiry_date,'" . @$_POST["qty_hidden"] . "','$bal1','$facility','$act_run_balance1','$timestamp','$ccc_id' from drug_stock_movement WHERE batch_number= '" . @$_POST["batch_hidden"] . "' AND drug='" . @$_POST["original_drug"] . "' LIMIT 1;";
				$this -> db -> query($sql);
				//Dispense transaction
				$sql = "INSERT INTO drug_stock_movement (drug, transaction_date, batch_number, transaction_type,source,destination,expiry_date, quantity_out,balance, facility, machine_code,timestamp,ccc_store_sp) SELECT '" . @$_POST["drug"] . "','" . @$_POST["original_dispensing_date"] . "', '" . @$_POST["batch"] . "','$transaction_type','$source','$destination',expiry_date,'" . @$_POST["qty_disp"] . "','$bal','$facility','$act_run_balance','$timestamp','$ccc_id' from drug_stock_movement WHERE batch_number= '" . @$_POST["batch"] . "' AND drug='" . @$_POST["drug"] . "' LIMIT 1;";
				$this -> db -> query($sql);

			}
			$this -> session -> set_userdata('dispense_updated', 'success');
		}
		$sql = "select * from patient where patient_number_ccc='$patient' and facility_code='$facility'";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		$record_no = $results[0]['id'];
		$this -> session -> set_userdata('msg_save_transaction', 'success');
		redirect("patient_management/load_view/details/$record_no");
	}

	public function drugAllergies() {
		$drug = $this -> input -> post("selected_drug");
		$patient_no = $this -> input -> post("patient_no");
		$allergies = Patient::getPatientAllergies($patient_no);
		@$drug_list = explode(",", @$allergies['Adr']);
		$is_allergic = 0;
		foreach ($drug_list as $value) {
			if($value !=''){
				$value=str_ireplace("-","", $value);
				if ($drug == $value) {
					$is_allergic = 1;
			 	}
		   }
		}
		echo $is_allergic;
	}

	public function print_test(){
		$check_if_print=@$this->input->post("print_check");
		$no_to_print=$this->input->post("print_count");
		$drug_name=$this->input->post("print_drug_name");
		$qty=$this->input->post("print_qty");
		$dose_value=$this->input->post("print_dose_value");
		$dose_frequency=$this->input->post("print_dose_frequency");
		$dose_hours=$this->input->post("print_dose_hours");
		$drug_instructions=$this->input->post("print_drug_info");
		$patient_name=$this->input->post("print_patient_name");
		$pharmacy_name=$this->input->post("print_pharmacy");
		$dispensing_date=$this->input->post("print_date");
		$facility_name=$this->input->post("print_facility_name");
		$facility_phone=$this->input->post("print_facility_phone");
		$str="";
		
		$this -> load -> library('mpdf');

		//MPDF Config
		$mode = 'utf-8';
		$format = array(80,90);
		$default_font_size = '11';
		$default_font = 'Helvetica';
		$margin_left = '5';
		$margin_right = '5';
		$margin_top = '4';
		$margin_bottom = '4';
		$margin_header = '';
		$margin_footer = '';
		$orientation = 'P';

		$this -> mpdf = new mPDF($mode,$format,$default_font_size,$default_font,$margin_left,$margin_right,$margin_top,$margin_bottom,$margin_header,$margin_footer,$orientation);

		if($check_if_print){
			//loop through checkboxes check if they are selected to print
			foreach($check_if_print as $counter=>$check_print){
				//selected to print
				if($check_print){
	               //count no. to print
	               $count=1;
				   
	               while($count<=$no_to_print[$counter]){
	               	     $this -> mpdf -> addPage();
	               	     $str='<table border="1"  style="border-collapse:collapse;font-size:11px;">';
						 $str.='<tr>';
						 $str.='<td colspan="2">Drugname: <b>'.strtoupper($drug_name[$counter]).'</b></td>';
						 $str.='<td>Qty: <b>'.$qty[$counter].'</b></td>';
						 $str.='</tr>';
						 $str.='<tr>';
						 $str.='<td colspan="3">Tablets/Capsules: ';
						 $str.='<b>'.$dose_value[$counter].'</b> to be taken <b>'.$dose_frequency[$counter].'</b> times a day after every <b>'.$dose_hours[$counter].'</b> hours</td>';
						 $str.='</tr>';
						 $str.='<tr>';
						 $str.='<td colspan="3">Before/After Meals: ';
						 $str.='<b>'.$drug_instructions[$counter].'</b></td>';
						 $str.='</tr>';
						 $str.='<tr>';
						 $str.='<td>Patient Name: <b>'.$patient_name.'</b> </td><td> Pharmacy :<b>'.$pharmacy_name[$counter].'</b> </td> <td>Date:<b>'.$dispensing_date.'</b></td>';
						 $str.='</tr>';
						 $str.='<tr>';
						 $str.='<td colspan="3" style="text-align:center;">Keep all medicines in a cold dry place out of reach of children.</td></tr>';
						 $str.='<tr><td colspan="2">Facility Name: <b>'.$this->session->userdata("facility_name").'</b></td><td> Facility Phone: <b>'.$this->session->userdata("facility_phone").'</b>';
						 $str.='</td>';
						 $str.='</tr>';
						 $str.='</table>';
						 //write to page
						 $this -> mpdf -> WriteHTML($str);
						 $count++;
					}
	            }
			}
			$file_name='Export/'.$patient_name.'(Labels).pdf';
			$this -> mpdf -> Output($file_name, 'F');
			echo base_url().$file_name;
	    }else{
	    	echo 0;
	    }
	}

	public function getInstructions($drug_id){
		$instructions="";
		$sql="SELECT instructions FROM drugcode WHERE id='$drug_id'";
		$query=$this->db->query($sql);
		$results=$query->result_array();
		if($results){
          //get values
          $values=$results[0]['instructions'] ;
          //get instruction names
          if($values !=""){
	          $values=explode(",",$values);
	          foreach($values as $value){
	     		$sql="SELECT name FROM drug_instructions WHERE id='$value'";
				$query=$this->db->query($sql);
				$results=$query->result_array();
				if($results){
				  foreach($results as $result){
                     $instructions.=$result['name']."\n";
				  }
				}
	          }
	      } 
		}
		echo ($instructions);
	}

	public function base_params($data) {
		$data['title'] = "webADT | Drug Dispensing";
		$data['banner_text'] = "Facility Dispensing";
		$data['link'] = "dispensements";
		$this -> load -> view('template', $data);
	}

	public function save_session(){
		$session_name = $this -> input -> post("session_name",TRUE);
		$session_value = $this -> input -> post("session_value",TRUE);
		$this -> session -> set_userdata($session_name,$session_value);
        
        echo $this -> session -> userdata($session_name);
	}



}
?>