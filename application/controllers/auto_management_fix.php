<?php
ob_start();
//error_reporting(0);
class auto_management extends MY_Controller {
	var $nascop_url = "";
	var $viral_load_url="";
	function __construct() {
		parent::__construct();

		ini_set("max_execution_time", "100000");
		ini_set("memory_limit", '2048M');
		ini_set("allow_url_fopen", '1');

	    $dir = realpath($_SERVER['DOCUMENT_ROOT']);
	    $link = $dir . "\\ADT\\assets\\nascop.txt";
		$this -> nascop_url = trim(file_get_contents($link));
		$this -> eid_url="http://viralload.nascop.org/";
        $this->ftp_url='192.168.133.10';

        // off Campus access {should be active at facility level}
        // $this->ftp_url='41.89.6.210';
	}

	public function index($manual=FALSE){
		$message ="";
		$today = (int)date('Ymd');

		//get last update time of log file for auto_update
		$log=Migration_Log::getLog('auto_update');
		$last_update = (int)$log['last_index'];

		//if not updated today
		if ($today != $last_update || $manual==TRUE) {
			//Function to create stored procedures
			//$message .= $this->createStoredProcedures();
			//Function to add table indexes
			$message .= $this->addIndex();
			//function to update destination column to 1 in drug_stock_movement table for issued transactions that have name 'pharm'
			$message .= $this->updateIssuedTo();
			//function to update source_destination column in drug_stock_movement table where it is zero
			$message .= $this->updateSourceDestination();
			//function to update ccc_store_sp column in drug_stock_movement table for pharmacy transactions
			$message .= $this->updateCCC_Store();
			//function to update patients without current_regimen with last regimen dispensed
			$message .= $this->update_current_regimen(); 
			//function to send eid statistics to nascop dashboard
			$message .= $this->updateEid();
			//function to update patient data such as active to lost_to_follow_up	
			$message .= $this->updatePatientData();
			//function to update data bugs by applying query fixes
			$message .= $this->updateFixes();
			//function to get viral load data
			$message .= $this->updateViralLoad();
			//function to add new facilities list
			$message .= $this->updateFacilties();
			//function to create new tables into adt
			$message .= $this->update_database_tables();
			//function to create new columns into table
			$message .= $this->update_database_columns();
			//function to set negative batches to zero
			$message .= $this->setBatchBalance();
			//function to update hash value of system to nascop
			$message .= $this->update_system_version();
            //function to download guidelines from nascop
            $message .= $this->get_guidelines();
			//function to update facility admin that reporting deadline is close
			$message .= $this->update_reporting();

	        //finally update the log file for auto_update 
	        if ($this -> session -> userdata("curl_error") != 1) {
	        	$sql="UPDATE migration_log SET last_index='$today' WHERE source='auto_update'";
				$this -> db -> query($sql);
				$this -> session -> set_userdata("curl_error", "");
			} 
	    }

	    if($manual==TRUE){
          	$message="<div class='alert alert-info'><button type='button' class='close' data-dismiss='alert'>&times;</button>".$message."</div>";
	    }
	    echo $message;
	}

	public function updateDrugId() {
		//function to update drug_id column in drug_stock_movement table where drug_id column is zero
		//Get batches for drugs which are associateed with those drugs
		$sql = "SELECT batch_number
				FROM  `drug_stock_movement` 
				WHERE drug =0 AND batch_number!=''
				ORDER BY  `drug_stock_movement`.`drug` ";

		$query = $this -> db -> query($sql);
		$res = $query -> result_array();
		$counter = 0;
		if($res){
			foreach ($res as $value) {
				$batch_number = $value['batch_number'];
				//Get drug  id from drug_stock_balance
				$sql = "SELECT drug_id FROM drug_stock_balance WHERE batch_number = '$batch_number' LIMIT 1";
				$query = $this -> db -> query($sql);
				$res = $query -> result_array();
				if (count($res) > 0) {
					$drug_id = $res[0]['drug_id'];
					//Update drug id in drug stock movement
					$sql = "UPDATE drug_stock_movement SET drug = '$drug_id' WHERE batch_number = '$batch_number' AND drug = 0 ";
					$query = $this -> db -> query($sql);
					$counter++;
				}
			}
		}
		$message="";
		if($counter>0){
			$message=$counter . " records have been updated!<br/>";
		}
		return $message;
	}

	public function updateDrugPatientVisit() {
		//function to update drug column in patient_visit table where drug column is zero
		//Get batches for drugs which are associateed with those drugs
		$sql = "SELECT batch_number
				FROM  `patient_visit` 
				WHERE drug_id =0 AND batch_number!=''
				ORDER BY  `patient_visit`.`drug_id` ";

		$query = $this -> db -> query($sql);
		$res = $query -> result_array();
		$counter = 0;
		if($res){
			foreach ($res as $value) {
				$batch_number = $value['batch_number'];
				//Get drug  id from drug_stock_balance
				$sql = "SELECT drug_id FROM drug_stock_balance WHERE batch_number = '$batch_number' LIMIT 1";
				$query = $this -> db -> query($sql);
				$res = $query -> result_array();
				if (count($res) > 0) {
					$drug_id = $res[0]['drug_id'];
					//Update drug id in patient visit
					$sql = "UPDATE patient_visit SET drug_id = '$drug_id' WHERE batch_number = '$batch_number' AND drug_id = '0' ";
					//echo $sql;die();
					$query = $this -> db -> query($sql);
					$counter++;
				}
			}
		}
		$message="";
		if($counter>0){
			$message=$counter . " records have been updated!<br/>";
		}
		return $message;
	}

	public function updateIssuedTo(){
		$sql="UPDATE drug_stock_movement
		      SET destination='1'
		      WHERE destination LIKE '%pharm%'";
		$this->db->query($sql);
		$count=$this->db->affected_rows();
		$message="(".$count.") issued to transactions updated!<br/>";
		$message="";
		if($count>0){
			$message="(".$count.") issued to transactions updated!<br/>";
		}
		return $message;
	}

	public function updateSourceDestination(){
		$values=array(
			      'received from'=>'source',
			      'returns from'=>'destination',
			      'issued to'=>'destination',
			      'returns to'=>'source'
			      );
		$message="";
		foreach($values as $transaction=>$column){
				$sql="UPDATE drug_stock_movement dsm
					  LEFT JOIN transaction_type t ON t.id=dsm.transaction_type
					  SET dsm.source_destination=IF(dsm.$column=dsm.facility,'1',dsm.$column)
				      WHERE t.name LIKE '%$transaction%'
					  AND(dsm.source_destination IS NULL OR dsm.source_destination='' OR dsm.source_destination='0')";
                $this->db->query($sql);
                $count=$this->db->affected_rows();
                $message.=$count." ".$transaction." transactions missing source_destination(".$column.") have been updated!<br/>";
		}
		if($count<=0){
			$message="";
		}
		return $message;
	}

	public function updateCCC_Store(){
        $facility_code=$this->session->userdata("facility");
		$sql="UPDATE drug_stock_movement dsm
		      SET ccc_store_sp='1'
		      WHERE dsm.source !=dsm.destination
		      AND ccc_store_sp='2' 
		      AND (dsm.source='$facility_code' OR dsm.destination='$facility_code')";
        $this->db->query($sql);
        $count=$this->db->affected_rows();
        $message="(".$count.") transactions changed from main pharmacy to main store!<br/>";

        if($count<=0){
			$message="";
		}
		return $message;
	}
	
	public function setBatchBalance(){//Set batch balance to zero where balance is negative
		$facility_code=$this->session->userdata("facility");
		$sql="UPDATE drug_stock_balance dsb
		      SET dsb.balance=0
		      WHERE dsb.balance<0 
		      AND dsb.facility_code='$facility_code'";
        $this->db->query($sql);
        $count=$this->db->affected_rows();
        $message="(".$count.") batches with negative balance have been updated!<br/>";

        if($count<=0){
			$message="";
		}
		return $message;
	}

	public function update_current_regimen() {
		$count=1;
		//Get all patients without current regimen and who are not active
		$sql_get_current_regimen = "SELECT p.id,p.patient_number_ccc, p.current_regimen ,ps.name
									FROM patient p 
									INNER JOIN patient_status ps ON ps.id = p.current_status
									WHERE current_regimen = '' 
									AND ps.name != 'active'";
		$query = $this -> db -> query($sql_get_current_regimen);
		$result_array = $query -> result_array();
		if($result_array){
			foreach ($result_array as $value) {
				$patient_id = $value['id'];
				$patient_ccc = $value['patient_number_ccc'];
				//Get last regimen
				$sql_last_regimen = "SELECT pv.last_regimen FROM patient_visit pv WHERE pv.patient_id='" . $patient_ccc . "' ORDER BY id DESC LIMIT 1";
				$query = $this -> db -> query($sql_last_regimen);
				$res = $query -> result_array();
				if (count($res) > 0) {
					$last_regimen_id = $res[0]['last_regimen'];
					$sql = "UPDATE patient p SET p.current_regimen ='" . $last_regimen_id . "'  WHERE p.id = '" . $patient_id . "'";
					$query = $this -> db -> query($sql);
					$count++;
				}
			}   
		}     
        $message="(".$count.") patients without current_regimen have been updated with last dispensed regimen!<br/>";
        if($count<=0){
			$message="";
		}
		return $message;
	}

	public function updateEid() {
		$message="";
		$adult_age = 3;
		$facility_code = $this -> session -> userdata("facility");
		$url = trim($this -> nascop_url). "sync/eid/" . $facility_code;
		$sql = "SELECT patient_number_ccc as patient_no,
		               facility_code,
		               g.name as gender,
		               p.dob as birth_date,
		               rst.Name as service,
		               CONCAT_WS(' | ',r.regimen_code,r.regimen_desc) as regimen,
		               p.date_enrolled as enrollment_date,
		               ps.name as source,
		               s.name as status
				FROM patient p
				LEFT JOIN gender g ON g.id=p.gender
				LEFT JOIN regimen_service_type rst ON rst.id=p.service
				LEFT JOIN regimen r ON r.id=p.start_regimen
				LEFT JOIN patient_source ps ON ps.id=p.source
				LEFT JOIN patient_status s ON s.id=p.current_status
				WHERE p.active='1'
				AND round(datediff(p.date_enrolled,p.dob)/360)<$adult_age";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		if($results){
			$json_data = json_encode($results, JSON_PRETTY_PRINT);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, array('json_data' => $json_data));
			$json_data = curl_exec($ch);
			if (empty($json_data)) {
				$message = "cURL Error: " . curl_error($ch);
				$this -> session -> set_userdata("curl_error", 1);
			} else {
				$messages = json_decode($json_data, TRUE);
				$message = $messages[0];
			}
			curl_close($ch);
		}
		return $message."<br/>";
	}
    
    public function updateSms() {
    	$alert="";
		$facility_name=$this -> session -> userdata('facility_name');
		$facility_phone=$this->session->userdata("facility_phone");
		$facility_sms_consent=$this->session->userdata("facility_sms_consent");

		if($facility_sms_consent==TRUE){
			/* Find out if today is on a weekend */
			$weekDay = date('w');
			if ($weekDay == 6) {
				$tommorrow = date('Y-m-d', strtotime('+2 day'));
			} else {
				$tommorrow = date('Y-m-d', strtotime('+1 day'));
			}

			$nextweek=date('Y-m-d', strtotime('+1 week'));

			$phone_minlength = '8';
			$phone = "";
			$phone_list = "";
			$messages_list="";
			$first_part = "";
			$kenyacode = "254";
			$arrDelimiters = array("/", ",", "+");

			/*Get All Patient Who Consented Yes That have an appointment Tommorow */
			$sql = "SELECT p.phone,p.patient_number_ccc,p.nextappointment,temp.patient,temp.appointment,temp.machine_code as status,temp.id
						FROM patient p
						LEFT JOIN 
						(SELECT pa.id,pa.patient, pa.appointment, pa.machine_code
						FROM patient_appointment pa
						WHERE pa.appointment IN ('$tommorrow','$nextweek')
						GROUP BY pa.patient) as temp ON temp.patient=p.patient_number_ccc
						WHERE p.sms_consent =  '1'
						AND p.nextappointment =temp.appointment
						AND char_length(p.phone)>$phone_minlength
						AND temp.machine_code !='s'
						GROUP BY p.patient_number_ccc";

			$query = $this -> db -> query($sql);
			$results = $query -> result_array();
			$phone_data=array();

			if ($results) {
				foreach ($results as $result) {
					$phone = $result['phone'];
					$appointment = $result['appointment'];
					$newphone = substr($phone, -$phone_minlength);
					$first_part = str_replace($newphone, "", $phone);
					$message = "You have an Appointment on " . date('l dS-M-Y', strtotime($appointment)) . " at $facility_name Contact Phone: $facility_phone";

					if (strlen($first_part) < 7) {
						if ($first_part === '07') {
							$phone = "+" . $kenyacode . substr($phone, 1);
							$phone_list .= $phone;
							$messages_list .= "+" .$message;
						} else if ($first_part == '7') {
							$phone = "0" . $phone;
							$phone = "+" . $kenyacode . substr($phone, 1);
							$phone_list .= $phone;
							$messages_list .= "+" .$message;
						} else if ($first_part == '+' . $kenyacode . '07') {
							$phone = str_replace($kenyacode . '07', $kenyacode . '7', $phone);
							$phone_list .= $phone;
							$messages_list .= "+" .$message;
						}

					} else {
						/*If Phone Does not meet requirements*/
						$phone = str_replace($arrDelimiters, "-|-", $phone);
						$phones = explode("-|-", $phone);

						foreach ($phones as $phone) {
							$newphone = substr($phone, -$phone_minlength);
							$first_part = str_replace($newphone, "", $phone);
							if (strlen($first_part) < 7) {
								if ($first_part === '07') {
									$phone = "+" . $kenyacode . substr($phone, 1);
									$phone_list .= $phone;
									$messages_list .= "+" .$message;
									break;
								} else if ($first_part == '7') {
									$phone = "0" . $phone;
									$phone = "+" . $kenyacode . substr($phone, 1);
									$phone_list .= $phone;
									$messages_list .= "+" .$message;
									break;
								} else if ($first_part == '+' . $kenyacode . '07') {
									$phone = str_replace($kenyacode . '07', $kenyacode . '7', $phone);
									$phone_list .= $phone;
									$messages_list .= "+" .$message;
									break;
								}
							}
						}
					}
					$stmt = "update patient_appointment set machine_code='s' where id='" . $result['id'] . "'";
					$q = $this -> db -> query($stmt);
				}
				$phone_list = substr($phone_list, 1);
				$messages_list = substr($messages_list, 1);

				$phone_list = explode("+", $phone_list);
			    $messages_list = explode("+", $messages_list);
			
				foreach ($phone_list as $counter=>$contact) {
					$message = urlencode($messages_list[$counter]);
					file("http://41.57.109.242:13000/cgi-bin/sendsms?username=clinton&password=ch41sms&to=$contact&text=$message");
				}
				$alert = "Patients notified (<b>" . sizeof($phone_list) . "</b>)";
			}
		}
		return $alert;
	}

	public function updatePatientData() {
		$days_to_lost_followup = 90;
		$days_to_pep_end = 30;
		$days_in_year = date("z", mktime(0, 0, 0, 12, 31, date('Y'))) + 1;
		$adult_age = 12;
		$active = 'active';
		$lost = 'lost';
		$pep = 'pep';
		$pmtct = 'pmtct';
		$two_year_days = $days_in_year * 2;
		$adult_days = $days_in_year * $adult_age;
		$message = "";
		$state = array();

		//Get Patient Status id's
		$status_array = array($active, $lost, $pep, $pmtct);
		foreach ($status_array as $status) {
			$s = "SELECT id,name FROM patient_status ps WHERE ps.name LIKE '%$status%'";
			$q = $this -> db -> query($s);
			$rs = $q -> result_array();
			if($rs){
			    $state[$status] = $rs[0]['id'];
			}  else {
                            $state[$status]='NAN'; //If non existant
                        }	
		}

		if(!empty($state)){
			/*Change Last Appointment to Next Appointment*/
			$sql['Change Last Appointment to Next Appointment'] = "(SELECT patient_number_ccc,nextappointment,temp.appointment,temp.patient
						FROM patient p
						LEFT JOIN 
						(SELECT MAX(pa.appointment)as appointment,pa.patient
						FROM patient_appointment pa
						GROUP BY pa.patient) as temp ON p.patient_number_ccc =temp.patient
						WHERE p.nextappointment !=temp.patient
						AND DATEDIFF(temp.appointment,p.nextappointment)>0
						GROUP BY p.patient_number_ccc) as p1
						SET p.nextappointment=p1.appointment";

			/*Change Active to Lost_to_follow_up*/
			if(isset($state[$lost])){
				$sql['Change Active to Lost_to_follow_up'] = "(SELECT patient_number_ccc,nextappointment,DATEDIFF(CURDATE(),nextappointment) as days
					   FROM patient p
					   LEFT JOIN patient_status ps ON ps.id=p.current_status
					   WHERE ps.Name LIKE '%$active%'
					   AND (DATEDIFF(CURDATE(),nextappointment )) >=$days_to_lost_followup
					   AND p.status_change_date != CURDATE()) as p1
					   SET p.current_status = '$state[$lost]'";
			}
			
			/*Change Lost_to_follow_up to Active */
			if(isset($state[$active])){
				$sql['Change Lost_to_follow_up to Active'] = "(SELECT patient_number_ccc,nextappointment,DATEDIFF(CURDATE(),nextappointment) as days
					   FROM patient p
					   LEFT JOIN patient_status ps ON ps.id=p.current_status
					   WHERE ps.Name LIKE '%$lost%'
					   AND (DATEDIFF(CURDATE(),nextappointment )) <$days_to_lost_followup) as p1
					   SET p.current_status = '$state[$active]' ";
			}
			

			/*Change Active to PEP End*/
			if(isset($state[$pep])){
				$sql['Change Active to PEP End'] = "(SELECT patient_number_ccc,rst.name as Service,ps.Name as Status,DATEDIFF(CURDATE(),date_enrolled) as days_enrolled
					   FROM patient p
					   LEFT JOIN regimen_service_type rst ON rst.id=p.service
					   LEFT JOIN patient_status ps ON ps.id=p.current_status
					   WHERE (DATEDIFF(CURDATE(),date_enrolled))>=$days_to_pep_end 
					   AND rst.name LIKE '%$pep%' 
					   AND ps.Name NOT LIKE '%$pep%') as p1
					   SET p.current_status = '$state[$pep]' ";
			}
			

			/*Change PEP End to Active*/
			if(isset($state[$active])){
				$sql['Change PEP End to Active'] = "(SELECT patient_number_ccc,rst.name as Service,ps.Name as Status,DATEDIFF(CURDATE(),date_enrolled) as days_enrolled
					   FROM patient p
					   LEFT JOIN regimen_service_type rst ON rst.id=p.service
					   LEFT JOIN patient_status ps ON ps.id=p.current_status
					   WHERE (DATEDIFF(CURDATE(),date_enrolled))<$days_to_pep_end 
					   AND rst.name LIKE '%$pep%' 
					   AND ps.Name NOT LIKE '%$active%') as p1
					   SET p.current_status = '$state[$active]' ";
			}
			

			/*Change Active to PMTCT End(children)*/
			if(isset($state[$pmtct])){
				$sql['Change Active to PMTCT End(children)'] = "(SELECT patient_number_ccc,rst.name AS Service,ps.Name AS Status,DATEDIFF(CURDATE(),dob) AS days
					   FROM patient p
					   LEFT JOIN regimen_service_type rst ON rst.id = p.service
					   LEFT JOIN patient_status ps ON ps.id = p.current_status
					   WHERE (DATEDIFF(CURDATE(),dob )) >=$two_year_days
					   AND (DATEDIFF(CURDATE(),dob)) <$adult_days
					   AND rst.name LIKE  '%$pmtct%'
					   AND ps.Name NOT LIKE  '%$pmtct%') as p1
					   SET p.current_status = '$state[$pmtct]'";
			}
			

			/*Change PMTCT End to Active(Adults)*/
			if(isset($state[$active])){
				$sql['Change PMTCT End to Active(Adults)'] = "(SELECT patient_number_ccc,rst.name AS Service,ps.Name AS Status,DATEDIFF(CURDATE(),dob) AS days
					   FROM patient p
					   LEFT JOIN regimen_service_type rst ON rst.id = p.service
					   LEFT JOIN patient_status ps ON ps.id = p.current_status 
					   WHERE (DATEDIFF(CURDATE(),dob)) >=$two_year_days 
					   AND (DATEDIFF(CURDATE(),dob)) >=$adult_days 
					   AND rst.name LIKE '%$pmtct%'
					   AND ps.Name LIKE '%$pmtct%') as p1
					   SET p.current_status = '$state[$active]'";
			}
			
			foreach ($sql as $i => $q) {
				$stmt1 = "UPDATE patient p,";
				$stmt2 = " WHERE p.patient_number_ccc=p1.patient_number_ccc;";
				$stmt1 .= $q;
				$stmt1 .= $stmt2;
				$q = $this -> db -> query($stmt1);
				if ($this -> db -> affected_rows() > 0) {
					$message .= $i . "(<b>" . $this -> db -> affected_rows() . "</b>) rows affected<br/>";
				}
			}
		}
		return $message;
	}

	public function updateFixes(){
		//Rename the prophylaxis cotrimoxazole
        $fixes[]="UPDATE drug_prophylaxis
        	      SET name='cotrimoxazole'
        	      WHERE name='cotrimozazole'";
        //Remove start_regimen_date in OI only patients records
        $fixes[]="UPDATE patient p
                  LEFT JOIN regimen_service_type rst ON p.service=rst.id
                  SET p.start_regimen_date='' 
                  WHERE rst.name LIKE '%oi%'
                  AND p.start_regimen_date IS NOT NULL";
        //Update status_change_date for lost_to_follow_up patients
        $fixes[]="UPDATE patient p,
				 (SELECT p.id, INTERVAL 90 DAY + p.nextappointment AS choosen_date
				  FROM patient p
				  LEFT JOIN patient_status ps ON ps.id = p.current_status
				  WHERE ps.Name LIKE  '%lost%') as test 
				 SET p.status_change_date=test.choosen_date
				 WHERE p.id=test.id";
	    //Update patients without service lines ie Pep end status should have pep as a service line
        $fixes[]="UPDATE patient p
			 	  LEFT JOIN patient_status ps ON ps.id=p.current_status,
			 	  (SELECT id 
			 	   FROM regimen_service_type
			 	   WHERE name LIKE '%pep%') as rs
			 	  SET p.service=rs.id
			 	  WHERE ps.name LIKE '%pep end%'
			 	  AND p.service=''";
		//Updating patients without service lines ie PMTCT status should have PMTCT as a service line
        $fixes[]= "UPDATE patient p
				   LEFT JOIN patient_status ps ON ps.id=p.current_status,
				   (SELECT id 
				 	FROM regimen_service_type
				 	WHERE name LIKE '%pmtct%') as rs
				    SET p.service=rs.id
				    WHERE ps.name LIKE '%pmtct end%'
				 	AND p.service=''";
		//Remove ??? in drug instructions
		$fixes[]="UPDATE drug_instructions 
				  SET name=REPLACE(name, '?', '.')
				  WHERE name LIKE '%?%'";

		$facility_code=$this->session->userdata("facility");
		//Auto Update Supported and supplied columns for satellite facilities
		$fixes[] = "UPDATE facilities f, 
						(SELECT facilitycode,supported_by,supplied_by
					     FROM facilities 
					     WHERE facilitycode='$facility_code') as temp
	                SET f.supported_by=temp.supported_by,
	                f.supplied_by=temp.supplied_by
	                WHERE f.parent='$facility_code'
	                AND f.parent !=f.facilitycode";
	    //Auto Update to trim other_drugs,adr and other_illnesses
	    $fixes[]="UPDATE patient p
				  SET p.other_drugs = TRIM(Replace(Replace(Replace(p.other_drugs,'\t',''),'\n',''),'\r','')),
				  p.other_illnesses = TRIM(Replace(Replace(Replace(p.other_illnesses,'\t',''),'\n',''),'\r','')),
				  p.adr = TRIM(Replace(Replace(Replace(p.adr,'\t',''),'\n',''),'\r',''))";

		//Execute fixes
		$total=0;
		foreach ($fixes as $fix) {
			//will exempt all database errors
			$db_debug = $this->db->db_debug;
			$this->db->db_debug = false;
			$this -> db -> query($fix);
			$this->db->db_debug = $db_debug;
			//count rows affected by fixes
			if ($this -> db -> affected_rows() > 0) {
				$total += $this -> db -> affected_rows();
			}
	    }
        
        $message="(".$total.") rows affected by fixes applied!<br/>";
	    if($total>0){
			$message="";
		}
        return $message;
	}

	public function updateViralLoad(){
		$facility_code = $this -> session -> userdata("facility");
		$url = $this -> eid_url . "vlapi.php?mfl=" . $facility_code;
		$patient_tests=array();

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$json_data = curl_exec($ch); 
		if (empty($json_data)) {
			$message = "cURL Error: " . curl_error($ch)."<br/>";
			$this -> session -> set_userdata("curl_error", 1);
		} else {
			$data = json_decode($json_data, TRUE); 
			$lab_data=$data['posts'];
			foreach($lab_data as $lab){
				foreach($lab as $tests){
				   $ccc_no=trim($tests['Patient']);
				   $result=$tests['Result'];
				   $date_tested=$tests['DateTested'];
				   $patient_tests[$ccc_no][]=array('date_tested'=>$date_tested,'result'=>$result);
                }
			}
		    $message="Viral Load Download Success!<br/>";
		}
		curl_close($ch);
        //write to file
		$fp = fopen('assets/viral_load.json', 'w');
		fwrite($fp, json_encode($patient_tests,JSON_PRETTY_PRINT));
		fclose($fp);
		return $message;
	}

	public function updateFacilties(){
		$total=Facilities::getTotalNumber();
		$message="";
		if($total < 9800){
			$this -> load -> library('PHPExcel');
			$inputFileType = 'Excel5';
			$inputFileName = $_SERVER['DOCUMENT_ROOT'] . '/ADT/assets/facility_list.xls';
			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
			$objPHPExcel = $objReader -> load($inputFileName);
			$highestColumm = $objPHPExcel -> setActiveSheetIndex(0) -> getHighestColumn();
			$highestRow = $objPHPExcel -> setActiveSheetIndex(0) -> getHighestRow();
			$arr = $objPHPExcel -> getActiveSheet() -> toArray(null, true, true, true);
			$facilities=array();
			$facility_code=$this->session->userdata("facility");
			$lists=Facilities::getParentandSatellites($facility_code);

			for ($row = 2; $row < $highestRow; $row++) {
				$facility_id=$arr[$row]['A'];
				$facility_name=$arr[$row]['B'];
				$facility_type_name=str_replace(array("'"), "", $arr[$row]['G']);
				$facility_type_id=Facility_Types::getTypeID($facility_type_name);
				$district_name=str_replace(array("'"), "", $arr[$row]['E']);
				$district_id=District::getID($district_name);
				$county_name=str_replace(array("'"), "", $arr[$row]['D']);
				$county_id=Counties::getID($county_name);
				$email=$arr[$row]['T'];
				$phone=$arr[$row]['R'];
				$adult_age=15;
				$weekday_max='';
				$weekend_max='';
				$supported_by='';
				$service_art=0;
				if(strtolower($arr[$row]['AD'])=="y"){
					$service_art=1;
				}
				$service_pmtct=0;
				if(strtolower($arr[$row]['AR'])=="y"){
					$service_pmtct=1;
				}
				$service_pep=0;
				$supplied_by='';
				$parent='';
				$map=0;
		        //if is this facility or satellite of this facility
				if(in_array($facility_id,$lists)){
					$details=Facilities::getCurrentFacility($facility_id);
					if($details){
	                   	$parent=$details[0]['parent'];
						$supported_by=$details[0]['supported_by'];
						$supplied_by=$details[0]['supplied_by'];
						$service_pep=$details[0]['service_pep'];
						$weekday_max=$details[0]['weekday_max'];
					    $weekend_max=$details[0]['weekend_max'];
					    $map=$details[0]['map'];
					}
				}
				//append to facilities data array
				$facilities[$row]=array(
					                'facilitycode'=>$facility_id,
					                'name'=>$facility_name,
					                'facilitytype'=>$facility_type_id,
					                'district'=>$district_id,
					                'county'=>$county_id,
					                'email'=>$email,
					                'phone'=>$phone,
					                'adult_age'=>$adult_age,
					                'weekday_max'=>$weekday_max,
					                'weekend_max'=>$weekend_max,
					                'supported_by'=>$supported_by,
					                'service_art'=>$service_art,
					                'service_pmtct'=>$service_pmtct,
					                'service_pep'=>$service_pep,
					                'supplied_by'=>$supplied_by,
					                'parent'=>$parent,
					                'map'=>$map);
			}
			$sql="TRUNCATE facilities";
			$this->db->query($sql);
			$this->db->insert_batch('facilities',$facilities);
			$counter=count($facilities);
			$message=$counter . " facilities have been added!<br/>";
	    }
		return $message;
	}
	public function update_database_tables(){
		$count=0;
		$message="";
		$tables['dependants'] = "CREATE TABLE dependants(
									id int(11),
									parent varchar(30),
									child varchar(30),
									PRIMARY KEY (id)
									);";
        $tables['spouses']= "CREATE TABLE spouses(
								id int(11),
								primary_spouse varchar(30),
								secondary_spouse varchar(30),
								PRIMARY KEY (id)
								);";
        $tables['drug_instructions']="CREATE TABLE IF NOT EXISTS `drug_instructions` (
									  `id` int(11) NOT NULL AUTO_INCREMENT,
									  `name` varchar(255) NOT NULL,
									  `active` int(11) NOT NULL,
									  PRIMARY KEY (`id`)
									) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=35;
									INSERT INTO `drug_instructions` (`id`, `name`, `active`) VALUES
									(1, 'Warning. May cause drowsiness', 1),
									(2, 'Warning. May cause drowsiness. If affected to do not drive or operate machinery.Avoid alcoholic drink', 1),
									(3, 'Warning. May cause drowsiness. If affected to do not drive or operate machinery.', 1),
									(4, 'Warning. Avoid alcoholic drink', 1),
									(5, 'Do not take indigestion remedies at the same time of the day as this medicine', 1),
									(6, 'Do not take indigestion remedies or medicines containing Iron or Zinc at the same time of a day as this medicine', 1),
									(7, 'Do not take milk, indigestion remedies, or medicines containing Iron or Zinc at the same time of day as this medicine', 1),
									(8, 'Do not stop taking this medicine except on your doctor''s advice', 1),
									(9, 'Take at regular intervals. Complete the prescribed course unless otherwise directed', 1),
									(10, 'Warning. Follow the printed instruction you have been given with this medicine', 1),
									(11, 'Avoid exposure of skin to direct sunlight or sun lamps', 1),
									(12, 'Do not take anything containing aspirin while taking  this medicine', 1),
									(13, 'Dissolve or mix with water before taking', 1),
									(14, 'This medicine may colour the urine', 1),
									(15, 'Caution flammable: Keep away from fire or flames', 1),
									(16, 'Allow to dissolve under the tongue. Do not transfer from this container. Keep tightly closed. Discard 8 weeks after opening.', 1),
									(17, 'Do not take more than??.in 24 hours', 1),
									(18, 'Do not take more than ?..in 24 hours or?. In any one week', 1),
									(19, 'Warning. Causes drowsiness which may continue the next day. If affected do not drive or operate machinery. Avoid alcoholic drink', 1),
									(20, '??..with or after food', 1),
									(21, '???.half to one hour after food', 1),
									(22, '????..an hour before food or on an empty stomach', 1),
									(23, '???.an hour before food or on an empty stomach', 1),
									(24, '???. sucked or chewed', 1),
									(25, '??? swallowed whole, not chewed', 1),
									(26, '???dissolved under the tongue', 1),
									(27, '????with plenty of water', 1),
									(28, 'To be spread thinly?..', 1),
									(29, 'Do not take more than  2 at any one time. Do not take more than 8 in 24 hours', 1),
									(30, 'Do not take with any other paracetamol products.', 1),
									(31, 'Contains aspirin and paracetamol. Do not take with any other paracetamol products', 1),
									(32, 'Contains aspirin', 1),
									(33, 'contains an apirin-like medicine', 1),
									(34, 'Avoid a lot of fatty meals together with efavirenz', 1);";

		$tables['sync_regimen_category']="TRUNCATE TABLE `sync_regimen_category`;
											INSERT INTO `sync_regimen_category` (`id`, `Name`, `Active`, `ccc_store_sp`) VALUES
											(4, 'Adult First Line', '1', 2),
											(5, 'Adult Second Line', '1', 2),
											(7, 'Paediatric First Line', '1', 2),
											(8, 'Paediatric Second Line', '1', 2),
											(9, 'Other Pediatric Regimen', '1', 2),
											(10, 'PMTCT Mother', '1', 2),
											(11, 'PMTCT Child', '1', 2),
											(12, 'PEP Adult', '1', 2),
											(13, 'PEP Child', '', 2),
											(17, 'Adult Third Line', '1', 2),
											(18, 'Paediatric Third Line', '1', 2),
											(19, 'OIs Medicines [1. Universal Prophylaxis]', '1', 2),
											(20, 'OIs Medicines [2. IPT]', '1', 2),
											(21, 'OIs Medicines {CM} and {OC} For Diflucan Donation Program ONLY', '1', 2);";
                            $tables['faq'] = "CREATE TABLE IF NOT EXISTS `faq` (
                                                  `id` int(11) NOT NULL AUTO_INCREMENT,
                                                  `modules` varchar(100) NOT NULL,
                                                  `questions` varchar(255) NOT NULL,
                                                  `answers` varchar(255) NOT NULL,
                                                  `active` int(5) NOT NULL DEFAULT '1',
                                                  PRIMARY KEY (`id`)
                                                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";

                            $tables['regimen_category'] = "TRUNCATE TABLE `regimen_category`;
											INSERT INTO `regimen_category` (`id`, `Name`, `Active`, `ccc_store_sp`) VALUES
											(4, 'Adult First Line', '1', 2),
											(5, 'Adult Second Line', '1', 2),
											(7, 'Paediatric First Line', '1', 2),
											(8, 'Paediatric Second Line', '1', 2),
											(9, 'Other Pediatric Regimen', '1', 2),
											(10, 'PMTCT Mother', '1', 2),
											(11, 'PMTCT Child', '1', 2),
											(12, 'PEP Adult', '1', 2),
											(13, 'PEP Child', '', 2),
											(17, 'Adult Third Line', '1', 2),
											(18, 'Paediatric Third Line', '1', 2),
											(19, 'OIs Medicines [1. Universal Prophylaxis]', '1', 2),
											(20, 'OIs Medicines [2. IPT]', '1', 2),
											(21, 'OIs Medicines {CM} and {OC} For Diflucan Donation Program ONLY', '1', 2);";

                            $tables['sync_regimen'] = "TRUNCATE TABLE `sync_regimen`;
											INSERT INTO `sync_regimen` (`id`, `name`, `code`, `old_code`, `description`, `category_id`) VALUES
											(1, 'AZT + 3TC + NVP', 'AF1A', '', 'Zidovudine + Lamivudine + Nevirapine', 4),
											(2, 'AZT + 3TC + EFV', 'AF1B', '', 'Zidovudine + Lamivudine + Efavirenz', 4),
											(3, 'TDF + 3TC + NVP', 'AF2A', '', 'Tenofovir + Lamivudine + Nevirapine', 4),
											(4, 'TDF + 3TC + EFV', 'AF2B', '', 'Tenofovir + Lamivudine + Efavirenz', 4),
											(5, 'd4T + 3TC + NVP', 'AF3A', '', 'Stavudine + Lamivudine + Nevirapine', 4),
											(6, 'd4T + 3TC + EFV', 'AF3B', '', 'Stavudine + Lamivudine + Efavirenz', 4),
											(7, 'AZT + 3TC + LPV/r', 'AS1A', '', 'Zidovudine + Lamivudine + Lopinavir/Ritonavir', 5),
											(10, 'TDF + 3TC + LPV/r', 'AS2A', '', 'Tenofovir + Lamivudine + Lopinavir/Ritonavir', 5),
											(27, 'AZT 300mg BD (from week 14 to Delivery); then NVP 200mg stat + AZT 600mg stat (or 300mg BD) + 3TC 150mg BD during labour; then 1 tab of AZT/3TC 300mg/150mg BD for ONE week post-partum', 'PM1', '', 'PMTCT for the mother: Zidovudine 300mg BD (from week 14 to Delivery); then Nevirapine 200mg stat + Zidovudine 600mg stat (or 300mg BD) + Lamivudine 150mg BD during labour; then 1 tab of Zidovudine/Lamivudine 300mg/150mg BD for ONE week post-partum', 10),
											(29, 'PMTCT HAART: AZT + 3TC + NVP', 'PM3', '', 'PMTCT HAART for the Mother: Zidovudine + Lamivudine + Nevirapine', 10),
											(30, 'PMTCT HAART: AZT + 3TC + EFV', 'PM4', '', 'PMTCT HAART for the Mother: Zidovudine + Lamivudine + Efavirenz', 10),
											(31, 'PMTCT HAART: AZT + 3TC + LPV/r', 'PM5', '', 'PMTCT HAART for the Mother: Zidovudine + Lamivudine + Lopinavir/Ritonavir', 10),
											(35, 'NVP OD up to 6 weeks of age for: (i) Infants born of mothers on HAART (Breastfeeding or not); (ii) ALL Non-Breastfeeding infants born of mothers not on HAART', 'PC1', '', 'PMTCT for the Infant: Nevirapine syrup OD from Birth up to 6 weeks of age for: (i) Infants born of mothers on HAART (Breastfeeding or not); (ii) ALL Non-Breastfeeding infants born of mothers not on HAART', 11),
											(36, 'NVP OD for Breastfeeding Infants until 1 week after complete cessation of Breastfeeding ', 'PC2', '', 'PMTCT for the Infant: Nevirapine syrup OD from Birth up to until 1 week after complete cessation of Breastfeeding (for Breastfeeding Infants)', 11),
											(58, 'AZT + 3TC + NVP', 'CF1A', '', 'Zidovudine + Lamivudine + Nevirapine', 7),
											(61, 'ABC + 3TC + NVP', 'CF2A', '', 'Abacavir + Lamivudine + Nevirapine', 7),
											(62, 'ABC + 3TC + EFV', 'CF2B', '', 'Abacavir + Lamivudine + Efavirenz', 7),
											(65, 'AZT + 3TC + EFV', 'CF1B', '', 'Zidovudine + Lamivudine + Efavirenz', 7),
											(70, 'AZT + 3TC + LPV/r', 'CS1A', '', 'Zidovudine + Lamivudine + Lopinavir/Ritonavir ', 8),
											(71, 'ABC + 3TC + LPV/r', 'CS2A', '', 'Abacavir + Lamivudine + Lopinavir/Ritonavir', 8),
											(73, 'TDF + 3TC + LPV/r', 'CF4C', '', 'Tenofovir + Lamivudine + Lopinavir/Ritonavir', 9),
											(76, 'TDF + 3TC + NVP', 'CF4A', '', 'Tenofovir + Lamivudine + Nevirapine', 9),
											(119, 'AZT + 3TC + LPV/r', 'CF1C', '', 'Zidovudine + Lamivudine + Lopinavir/Ritonavir ', 7),
											(121, 'AZT + 3TC + ATV/r', 'AS1B', '', 'Zidovudine + Lamivudine + Atazanavir/Ritonavir', 5),
											(122, 'TDF + 3TC + ATV/r', 'AS2C', '', 'Tenofovir + Lamivudine + Atazanavir/Ritonavir', 5),
											(125, 'ABC + 3TC + LPV/r', 'CF2D', '', 'Abacavir + Lamivudine + Lopinavir/Ritonavir ', 7),
											(167, 'ABC + 3TC + NVP', 'AF4A', '', 'Abacavir + Lamivudine + Nevirapine', 4),
											(168, 'ABC + 3TC + EFV', 'AF4B', '', 'Abacavir + Lamivudine + Efavirenz', 4),
											(169, 'All other 1st line Adult regimens', 'AF5X', '', 'Total of ALL OTHER Adult patients on 1st line regimens not listed above (coded and uncoded)', 4),
											(174, 'ABC + 3TC + LPV/r', 'AS5A', '', 'Abacavir + Lamivudine + Lopinavir/Ritonavir', 5),
											(175, 'ABC + 3TC + ATV/r', 'AS5B', '', 'Abacavir + Lamivudine + Atazanavir/Ritonavir', 5),
											(176, 'All other 2nd line Adult regimens', 'AS6X', '', 'Total of ALL OTHER Adult patients on 2nd line regimens not listed above (coded and uncoded regimens)', 5),
											(177, 'RAL + 3TC + DRV + RTV', 'AT1A', '', 'Raltegravir + Lamivudine + Darunavir + Ritonavir', 17),
											(178, 'RAL + 3TC + DRV + RTV + AZT', 'AT1B', '', 'Raltegravir + Lamivudine + Darunavir + Ritonavir + Zidovudine', 17),
											(179, 'RAL + 3TC + DRV + RTV + TDF', 'AT1C', '', 'Raltegravir + Lamivudine + Darunavir + Ritonavir + Tenofovir', 17),
											(180, 'ETV + 3TC + DRV + RTV', 'AT2A', '', 'Etravirine + Lamivudine + Darunavir + Ritonavir', 17),
											(181, 'All other 3rd line Adult regimens', 'AT2X', '', 'Total of ALL OTHER Adult patients on 3rd line regimens not listed above (coded and uncoded regimens)', 17),
											(185, 'AZT + 3TC + ATV/r', 'CF1D', '', 'Zidovudine + Lamivudine + Atazanavir/Ritonavir ', 7),
											(189, 'ABC + 3TC + ATV/r', 'CF2E', '', 'Abacavir + Lamivudine + Atazanavir/Ritonavir', 7),
											(190, 'd4T + 3TC + NVP for children weighing >= 25kg', 'CF3A', '', 'Stavudine + Lamivudine + Nevirapine', 7),
											(191, 'd4T + 3TC + EFV for children weighing >= 25kg', 'CF3B', '', 'Stavudine + Lamivudine + Efavirenz', 7),
											(193, 'TDF + 3TC + EFV', 'CF4B', '', 'Tenofovir + Lamivudine + Efavirenz', 9),
											(195, 'TDF + 3TC + ATV/r', 'CF4D', '', 'Tenofovir + Lamivudine + Atazanavir/Ritonavir', 9),
											(196, 'All other 1st line Paediatric regimens', 'CF5X', '', 'Total of ALL OTHER Paediatric patients on 1st line regimens not listed above (coded and uncoded regimens)', 9),
											(198, 'AZT + 3TC + ATV/r', 'CS1B', '', 'Zidovudine + Lamivudine + Atazanavir/Ritonavir ', 8),
											(200, 'ABC + 3TC + ATV/r', 'CS2C', '', 'Abacavir + Lamivudine + Atazanavir/Ritonavir', 8),
											(201, 'All other 2nd line Paediatric regimens', 'CS4X', '', 'Total of ALL OTHER Paediatric patients on 2nd line regimens not listed above (coded and uncoded regimens)', 8),
											(202, 'RAL + 3TC + DRV + RTV', 'CT1A', '', 'Raltegravir + Lamivudine + Darunavir + Ritonavir', 18),
											(203, 'RAL + 3TC + DRV + RTV + AZT', 'CT1B', '', 'Raltegravir + Lamivudine + Darunavir + Ritonavir + Zidovudine', 18),
											(204, 'RAL + 3TC + DRV + RTV + ABC', 'CT1C', '', 'Raltegravir + Lamivudine + Darunavir + Ritonavir + Abacavir', 18),
											(205, 'ETV + 3TC + DRV + RTV', 'CT2A', '', 'Etravirine + Lamivudine + Darunavir + Ritonavir', 18),
											(206, 'All other 3rd line Paediatric regimens', 'CT3X', '', 'Total of ALL OTHER Paed patients on 3rd line regimens not listed above (coded and uncoded regimens)', 18),
											(208, 'NVP 200mg stat + AZT 600mg stat (or 300mg BD) + 3TC 150mg BD during labour; then 1 tab of AZT/3TC 300mg/150mg BD for one week post-partum', 'PM2', '', 'PMTCT for the mother: Nevirapine 200mg stat + Zidovudine 600mg stat (or 300mg BD) + Lamivudine 150mg BD during labour; then 1 tab of Zidovudine/Lamivudine 300mg/150mg BD for ONE week post-partum (for Women coming for first time when in Labour)', 10),
											(212, 'PMTCT HAART: TDF + 3TC + NVP', 'PM6', '', 'PMTCT HAART for the Mother: Tenofovir + Lamivudine + Nevirapine', 10),
											(213, 'PMTCT HAART: TDF + 3TC + LPV/r', 'PM7', '', 'PMTCT HAART for the Mother: Tenofovir + Lamivudine + Lopinavir/Ritonavir   [For use by Pregnant women with less than 2 years NVP exposure and who never received the 3TC tail]', 10),
											(214, 'PMTCT HAART: TDF + 3TC + EFV', 'PM9', '', 'PMTCT HAART for the Mother: Tenofovir + Lamivudine + Efavirenz', 10),
											(215, 'PMTCT HAART: AZT + 3TC + ATV/r', 'PM10', '', 'PMTCT HAART for the Mother: Zidovudine + Lamivudine + Atazanavir/Ritonavir', 10),
											(216, 'PMTCT HAART: TDF + 3TC + ATV/r', 'PM11', '', 'PMTCT HAART for the Mother: Tenofovir + Lamivudine + Atazanavir/Ritonavir', 10),
											(217, 'All other PMTCT regimens for Women', 'PM1X', '', 'Total of ALL other PMTCT regimens for Women not listed above (coded and uncoded regimens)', 10),
											(220, 'AZT Liquid BD for 6 weeks ', 'PC4', '', 'PMTCT for the Infant: Zidovudine syrup BD for 6 weeks (Alternative for infants on TB treatment or NVP toxicity)', 11),
											(221, '3TC Liquid BD', 'PC5', '', 'PMTCT for the Infant: Lamivudine syrup BD Infant Lamivudine prophylaxis for infants who cannot take NVP due to severe NVP toxicity (grade 3 or 4)/or if baby is on TB treatment with rifampicin containing regimen', 11),
											(222, 'NVP Liquid OD for 12 weeks ', 'PC6', '', 'PMTCT for the Infant: Nevirapine syrup OD for 12 weeks Nevirapine prophylaxis for infants whose mothers who start ART after 38 weeks gestation, delivery or immediate postpartum', 11),
											(223, 'All other PMTCT regimens for Infants', 'PC1X', '', 'Total of ALL other PMTCT regimens for Infants not listed above (coded and uncoded regimens)', 11),
											(224, 'AZT + 3TC + LPV/r (Adult PEP)', 'PA1B', '', 'Zidovudine + Lamivudine + Lopinavir/Ritonavir ', 12),
											(225, 'AZT + 3TC + ATV/r (Adult PEP)', 'PA1C', '', 'Zidovudine + Lamivudine + Atazanavir/Ritonavir', 12),
											(226, 'TDF + 3TC + LPV/r (Adult PEP)', 'PA3B', '', 'Tenofovir + Lamivudine + Lopinavir/Ritonavir', 12),
											(227, 'TDF + 3TC + ATV/r (Adult PEP)', 'PA3C', '', 'Tenofovir + Lamivudine + Atazanavir/Ritonavir', 12),
											(228, 'All other PEP regimens for Adults', 'PA4X', '', 'Total of ALL OTHER Adult PEP patients not listed above', 12),
											(229, 'AZT + 3TC + LPV/r (Paed PEP)', 'PC1A', '', 'Zidovudine + Lamivudine + Lopinavir/Ritonavir', 13),
											(230, 'ABC + 3TC + LPV/r (Paed PEP)', 'PC3A', '', 'Abacavir + Lamivudine + Lopinavir/Ritonavir', 13),
											(231, 'All other PEP regimens for Children', 'PC4X', '', 'Total of ALL OTHER Paed PEP patients not listed above', 13),
											(232, 'Adult patients (=>15 Yrs) on Cotrimoxazole prophylaxis ', 'OI1A', '', 'Total number of Adult Patients / Clients (ART plus Non-ART) on Cotrimoxazole prophylaxis', 19),
											(233, 'Paediatric patients (<15 Yrs) on Cotrimoxazole prophylaxis ', 'OI1C', '', 'Total number of Paed Patients / Clients (ART plus Non-ART) on Cotrimoxazole prophylaxis', 19),
											(234, 'Adult patients (=>15 Yrs) on Dapsone prophylaxis ', 'OI2A', '', 'Total number of Adult Patients / Clients (ART plus Non-ART) on Dapsone prophylaxis', 19),
											(235, 'Paediatric patients (<15 Yrs) on Dapsone prophylaxis ', 'OI2C', '', 'Total number of Paed Patients / Clients (ART plus Non-ART) on Dapsone prophylaxis', 19),
											(236, 'Adult patients (=>15 Yrs) on Isoniazid prophylaxis ', 'OI4A', '', 'Total number of Adult Patients / Clients (ART plus Non-ART) on Isoniazid prophylaxis', 20),
											(237, 'Paediatric patients (<15 Yrs) on Isoniazid prophylaxis ', 'OI4C', '', 'Total number of Paed Patients / Clients (ART plus Non-ART) on Isoniazid prophylaxis', 20),
											(238, 'Adult patients on Diflucan (For Diflucan Donation Program ONLY)', 'OI3A', '', 'Total number of Adult Patients / Clients on Diflucan (For Diflucan Donation Program ONLY)2', 21),
											(239, 'Paed patients on Diflucan (For Diflucan Donation Program ONLY)', 'OI3C', '', 'Total number of Paed Patients / Clients on Diflucan (For Diflucan Donation Program ONLY)3', 21),
											(240, 'New patients with CM on Diflucan (For Diflucan Donation Program ONLY)', 'CM3N', '', 'Total number of New Patients / Clients on Diflucan - disaggregated by Cryptococcal meningitis (CM)', 21),
											(241, 'Revisit patients with CM on Diflucan (For Diflucan Donation Program ONLY)', 'CM3R', '', 'Total number of Revisit Patients / Clients on Diflucan - disaggregated by Cryptococcal meningitis (CM)', 21),
											(242, 'New patients with OC on Diflucan (For Diflucan Donation Program ONLY)', 'OC3N', '', 'Total number of New Patients / Clients on Diflucan - disaggregated by Oesophageal candidiasis (OC)', 21),
											(243, 'Revisit patients with OC on Diflucan (For Diflucan Donation Program ONLY)', 'OC3R', '', 'Total number of Revisit Patients / Clients on Diflucan - disaggregated by Oesophageal candidiasis (OC)', 21),
											(244, 'AZT + 3TC + NVP', 'AF1A', '', 'AZT + 3TC + NVP\r\n', 4),
											(245, 'AZT + 3TC + EFV', 'AF1B', '', 'Zidovudine + Lamivudine + Efavirenz', 4),
											(246, 'TDF + 3TC + NVP', 'AF2A', '', 'Tenofovir + Lamivudine + Nevirapine', 4),
											(247, 'TDF + 3TC + EFV', 'AF2B', '', 'Tenofovir + Lamivudine + Efavirenz', 4),
											(248, 'd4T + 3TC + NVP', 'AF3A', '', 'Stavudine + Lamivudine + Nevirapine', 4),
											(249, 'd4T + 3TC + EFV', 'AF3B', '', 'Stavudine + Lamivudine + Efavirenz', 4),
											(250, 'NVP OD up to 6 weeks of age for: (i) Infants born of mothers on HAART (Breastfeeding or not); (ii) ALL Non-Breastfeeding infants born of mothers not on HAART', 'PC1', '', 'NVP OD up to 6 weeks of age for: (i) Infants born of mothers on HAART (Breastfeeding or not); (ii) ALL Non-Breastfeeding infants born of mothers not on HAART\r\n', 11),
											(251, 'NVP OD for Breastfeeding Infants until 1 week after complete cessation of Breastfeeding ', 'PC2', '', 'NVP OD for Breastfeeding Infants until 1 week after complete cessation of Breastfeeding \r\n', 11),
											(252, 'PMTCT HAART: AZT + 3TC + NVP', 'PM3', '', 'PMTCT HAART: AZT + 3TC + NVP\r\n', 10),
											(253, 'PMTCT HAART: AZT + 3TC + EFV', 'PM4', '', 'PMTCT HAART: AZT + 3TC + EFV\r\n', 10),
											(254, 'PMTCT HAART: AZT + 3TC + LPV/r', 'PM5', '', 'PMTCT HAART: AZT + 3TC + LPV/r\r\n', 10),
											(255, 'AZT + 3TC + NVP', 'CF1A', '', 'AZT + 3TC + NVP\r\n', 7),
											(256, 'AZT + 3TC + EFV', 'CF1B', '', 'AZT + 3TC + EFV\r\n', 7),
											(257, 'AZT + 3TC + LPV/r', 'CF1C', '', 'AZT + 3TC + LPV/r\r\n', 7),
											(258, 'ABC + 3TC + NVP', 'CF2A', '', 'ABC + 3TC + NVP\r\n', 7),
											(259, 'ABC + 3TC + EFV', 'CF2B', '', 'ABC + 3TC + EFV\r\n', 7),
											(260, 'ABC + 3TC + LPV/r', 'CF2D', '', 'ABC + 3TC + LPV/r\r\n', 7),
											(261, 'AZT + 3TC + LPV/r', 'CS1A', '', 'AZT + 3TC + LPV/r\r\n', 8),
											(262, 'ABC + 3TC + LPV/r', 'CS2A', '', 'ABC + 3TC + LPV/r\r\n', 8),
											(263, 'AZT + 3TC + ATV/r', 'AS1B', '', 'AZT + 3TC + ATV/r', 5),
											(264, 'TDF + 3TC + ATV/r', 'AS2C', '', 'TDF + 3TC + ATV/r', 5),
											(265, 'AZT 300mg BD (from week 14 to Delivery); then NVP 200mg stat + AZT 600mg stat (or 300mg BD) + 3TC 150mg BD during labour; then 1 tab of AZT/3TC 300mg/150mg BD for ONE week post-partum', 'PM1', '', 'PMTCT for the mother: Zidovudine 300mg BD (from week 14 to Delivery); then Nevirapine 200mg stat + Zidovudine 600mg stat (or 300mg BD) + Lamivudine 150mg BD during labour; then 1 tab of Zidovudine/Lamivudine 300mg/150mg BD for ONE week post-partum', 10),
											(266, 'AZT + 3TC + LPV/r ', 'AS1A', '', 'Zidovudine + Lamivudine + Lopinavir/Ritonavir\r\n', 5),
											(267, 'TDF + 3TC + LPV/r', 'AS2A', '', 'Tenofovir + Lamivudine + Lopinavir/Ritonavir\r\n', 5);
											";

							$tables['sync_drug'] = "TRUNCATE TABLE `sync_drug`;
											INSERT INTO `sync_drug` (`id`, `name`, `abbreviation`, `strength`, `packsize`, `formulation`, `unit`, `note`, `weight`, `category_id`, `regimen_id`) VALUES
											(1, 'Zidovudine/Lamivudine/Nevirapine', 'AZT/3TC/NVP', '300/150/200mg', 60, 'FDC Tabs', '', '', 0, 1, 0),
											(2, 'Zidovudine/Lamivudine', 'AZT/3TC', '300/150mg', 60, 'FDC Tabs', '', '', 0, 1, 0),
											(3, 'Tenofovir/Lamivudine/Efavirenz', 'TDF/3TC/EFV', '300/300/600mg', 30, 'FDC Tabs', '', '', 0, 1, 0),
											(4, 'Tenofovir/Lamivudine', 'TDF/3TC', '300/300mg', 30, 'FDC Tabs', '', '', 0, 1, 0),
											(5, 'Stavudine/Lamivudine/Nevirapine', 'd4T/3TC/NVP', '30/150/200mg', 60, 'FDC Tabs', '', '', 0, 1, 0),
											(6, 'Stavudine/Lamivudine', 'd4T/3TC', '30/150mg', 60, 'FDC Tabs', '', '', 0, 1, 0),
											(7, 'Efavirenz', 'EFV', '600mg', 30, 'Tabs', '', '', 0, 1, 0),
											(8, 'Lamivudine', '3TC', '150mg', 60, 'Tabs', '', '', 0, 1, 0),
											(9, 'Nevirapine', 'NVP', '200mg', 60, 'Tabs', '', '', 0, 1, 0),
											(10, 'Tenofovir', 'TDF', '300mg', 30, 'Tabs', '', '', 0, 1, 0),
											(11, 'Zidovudine', 'AZT', '300mg', 60, 'Tabs', '', '', 0, 1, 0),
											(12, 'Abacavir', 'ABC', '300mg', 60, 'Tabs', '', '', 0, 1, 0),
											(15, 'Lopinavir/ritonavir', 'LPV/r', '200/50mg', 120, 'Tabs', '', '', 0, 1, 0),
											(16, 'Zidovudine/Lamivudine/Nevirapine', 'AZT/3TC/NVP', '60/30/50mg', 60, 'FDC Tabs', '', '', 0, 2, 0),
											(17, 'Zidovudine/Lamivudine', 'AZT/3TC', '60/30mg', 60, 'Tabs', '', '', 0, 2, 0),
											(18, 'Abacavir/Lamivudine', 'ABC/3TC', '60/30mg', 60, 'FDC Tabs', '', '', 0, 2, 0),
											(25, 'Efavirenz', 'EFV', '200mg', 90, 'Tabs', '', '', 0, 2, 0),
											(26, 'Lamivudine', '3TC', '10mg/ml', 240, 'Liquid', '', '', 0, 2, 0),
											(28, 'Lopinavir/ritonavir', 'LPV/r', '80/20mg/ml', 60, 'Liquid', '', '', 0, 2, 0),
											(30, 'Nevirapine', 'NVP', '10mg/ml', 240, 'Suspension', '', '', 0, 2, 0),
											(35, 'Zidovudine', 'AZT', '10mg/ml', 240, 'Liquid', '', '', 0, 2, 0),
											(36, 'Co-trimoxazole', '', '480mg', 1000, 'Tabs', '', '', 0, 3, 0),
											(37, 'Co-trimoxazole (500s) blister pack Tabs', '', '960mg', 500, 'Tabs (for Pack of 500 tabs)', '', '', 0, 3, 0),
											(38, 'Co-trimoxazole', '', '240mg/5ml', 100, 'Suspension', '', '', 0, 3, 0),
											(39, 'Dapsone', '', '100mg', 1000, 'Tabs', '', '', 0, 3, 0),
											(40, 'Diflucan', '', '200mg', 28, 'Tabs', '', '', 0, 3, 0),
											(43, 'Fluconazole', '', '200mg', 100, 'Tabs', '', '', 0, 3, 0),
											(45, 'Amphotericin B ', '', '50mg', 1, 'Injection', '', '', 0, 3, 0),
											(47, 'Pyridoxine', '', '50mg', 100, 'Tabs', '', '', 0, 3, 0),
											(130, 'Isoniazid', '', '300mg', 100, 'Tabs', '', '', 0, 3, 0),
											(140, 'Co-trimoxazole (100s) blister pack Tabs', '', '960mg', 100, 'Tabs (for Pack of 100 tabs)', '', '', 0, 3, 0),
											(141, 'Nevirapine', 'NVP', '10mg/ml', 100, 'Suspension', '', '', 0, 2, 0),
											(147, 'Ritonavir', 'RTV', '80mg/ml', 90, 'Liquid', '', '', 0, 2, 0),
											(157, 'Isoniazid', '', '100mg', 100, 'Tabs', '', '', 0, 3, 0),
											(165, 'Acyclovir (30s)', '', '400mg', 30, 'Tabs', '', '', 0, 3, 0),
											(173, 'Atazanavir/Ritonavir', 'ATV/r', '300/100mg', 30, 'Tabs', '', '', 0, 1, 0),
											(195, 'Darunavir', 'DRV', '600mg', 60, 'Tabs', '', '', 0, 1, 0),
											(196, 'Darunavir', 'DRV', '300mg', 120, 'Tabs', '', '', 0, 1, 0),
											(197, 'Etravirine', 'ETV', '200mg', 60, 'Tabs', '', '', 0, 1, 0),
											(198, 'Raltegravir', 'RAL', '400mg', 60, 'Tabs', '', '', 0, 1, 0),
											(199, 'Ritonavir', 'RTV', '100mg', 84, 'Caps', '', '', 0, 1, 0),
											(200, 'Saquinavir', 'SQV', '200mg', 270, 'Tabs', '', '', 0, 1, 0),
											(210, 'Darunavir', 'DRV', '150mg', 60, 'Tabs', '', '', 0, 2, 0),
											(211, 'Darunavir', 'DRV', '75mg', 60, 'Tabs', '', '', 0, 2, 0),
											(212, 'Darunavir Susp', 'DRV', '100mg', 200, 'Suspension', '', '', 0, 2, 0),
											(213, 'Etravirine', 'ETV', '100mg', 120, 'Tabs', '', '', 0, 2, 0),
											(214, 'Etravirine', 'ETV', '25mg', 120, 'Tabs', '', '', 0, 2, 0),
											(215, 'Raltegravir', 'RAL', '100mg', 60, 'Tabs', '', '', 0, 2, 0),
											(216, 'Raltegravir', 'RAL', '25mg', 60, 'Tabs', '', '', 0, 2, 0),
											(217, 'Raltegravir Susp', 'RAL', '100mg/5ml', 60, 'Suspension', '', '', 0, 2, 0),
											(225, 'Diflucan', '', '2mg/ml', 100, 'Infusion', '', '', 0, 3, 0),
											(226, 'Diflucan', '', '50mg/5ml', 35, 'Suspension', '', '', 0, 3, 0),
											(227, 'Darunavir', 'DRV', '150mg', 240, 'Tabs', '1', '', 999, 1, 0),
											(228, 'Darunavir', 'DRV', '150mg', 60, 'Tabs', '', '', 999, 2, 0),
											(229, 'Darunavir', 'DRV', '300mg', 120, 'Tabs', '', '', 999, 1, 0),
											(230, 'Darunavir', 'DRV', '600mg', 60, 'Tabs', '', '', 999, 1, 0),
											(231, 'Darunavir', 'DRV', '75mg', 60, 'Tabs', '', '', 999, 2, 0),
											(232, 'Darunavir Susp', 'DRV', '100mg', 200, 'Suspension', '', '', 999, 2, 0),
											(233, 'Darunavir', 'DRV', '75mg', 480, 'Tabs', '', '', 999, 2, 0),
											(234, 'Dapsone', '', '100mg', 100, 'Tabs', '', '', 999, 3, 0),
											(235, 'Etravirine', 'ETV', '100mg', 120, 'Tabs', '', '', 999, 2, 0),
											(236, 'Etravirine', 'ETV', '200mg', 60, 'Tabs', '', '', 999, 1, 0),
											(237, 'Etravirine', 'ETV', '25mg', 120, 'Tabs', '', '', 999, 2, 0),
											(238, 'Raltegravir', 'RAL', '100mg', 60, 'Tabs', '', '', 999, 2, 0),
											(239, 'Raltegravir', 'RAL', '25mg', 60, 'Tabs', '', '', 999, 2, 0),
											(240, 'Raltegravir', 'RAL', '400mg', 60, 'Tabs', '', '', 999, 1, 0),
											(241, 'Raltegravir Susp', 'RAL', '100mg/5ml', 60, 'Suspension', '', '', 999, 2, 0),
											(242, 'Isoniazid (H)', '', '300mg', 672, 'Tabs', '', '', 999, 1, 0),
											(244, 'Ritonavir', '', '100mg', 60, '', '', '', 999, 1, 0);";

															$tables['sync_facility'] = "TRUNCATE TABLE `sync_facility`;INSERT INTO `sync_facility` (`id`, `name`, `code`, `category`, `sponsors`, `services`, `manager_id`, `district_id`, `address_id`, `parent_id`, `ordering`, `affiliation`, `service_point`, `county_id`, `hcsm_id`, `keph_level`, `location`, `affiliate_organization_id`) VALUES
								(2, 'Maseno Mission Hospital', '13781', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 29, 146, NULL, 1, 'mission', 0, 17, NULL, 'Level 1', 'North West Kisumu', 2),
								(3, 'Zombe (AIC) Dispensary', '12860', 'satellite', '', '', 828, 73, 579, 102, 0, 'mission', 0, 18, NULL, 'Not Classified', '', 0),
								(4, 'AIC  Githumu Hospital', '10267', 'satellite', '', '', 678, 58, 8, NULL, 0, 'mission', 0, 29, NULL, 'Not Classified', '', 0),
								(5, 'AIC Kapsowar Mission Hospital', '14767', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', 895, 4, 49, NULL, 0, 'mission', 0, 44, NULL, 'Not Classified', '', 0),
								(6, 'Kijabe (AIC) Hospital', '10602', 'central', 'AIDS Relief', 'ART,PMTCT,PEP,LAB,RTK', 998, 195, 99, NULL, 1, 'mission', 0, 13, 1257, 'Level 1', 'Kijabe', 2),
								(7, 'AIC Litein', '00001', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 174, 80, NULL, 0, 'mission', 0, 2, NULL, 'Not Classified', '', 0),
								(8, 'AIC Lokichogio Health Centre', '15059', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 162, 50, NULL, 1, 'mission', 1, 43, NULL, 'Level 1', 'Lokichogio', 1),
								(9, 'Aid Village Clinic LTD - Mbirikani Clinic', '14571', 'standalone', 'AID Village Clinics', 'ART,PMTCT,PEP,LAB,RTK', 998, 198, 83, NULL, 0, 'private', 0, 10, NULL, 'Level 1', 'Imbirikani', 25),
								(10, 'Akala Health Centre', '13471', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 9, 210, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'Siaya', 42),
								(11, 'Alupe Sub-District Hospital', '15795', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 134, 9, NULL, 0, 'public', 1, 4, NULL, 'Not Classified', 'Alupe', 24),
								(12, 'Ambira Sub-District Hospital', '13476', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 9, 212, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'Central Ugenya', 24),
								(13, 'AMREF Kibera Health Centre', '13028', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 167, 213, NULL, 1, 'ngo', 0, 30, NULL, 'Level 3', 'Laini Saba', 4),
								(14, 'Asumbi Mission Hospital', '13488', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 11, 199, NULL, 1, 'mission', 0, 8, NULL, 'Not Classified', 'Central Gem', 2),
								(15, 'Athi River Health Centre', '11936', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 12, 227, NULL, 0, 'public', 1, 22, NULL, 'Not Classified', 'Mavoko', 24),
								(16, 'Babadogo (EDARP)', '12875', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 166, 129, 42, 0, 'mission', 1, 30, NULL, 'Not Classified', 'Ruaraka', 14),
								(17, 'Bahati Health Center ', '12878', 'standalone', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', NULL, 165, 48, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'kamukunji', 24),
								(18, 'Baraka Dispensary (Nairobi)', '12881', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 166, 259, NULL, 1, 'private', 1, 30, NULL, 'Level 2', 'Ruaraka', 2),
								(19, 'Baraka Clinic', '12881', 'standalone', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', 998, 166, 255, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Kasarani', 24),
								(20, 'Beacon of Hope Clinic (Kajiado)', '16667', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 182, 51, NULL, 1, 'private', 1, 10, NULL, 'Level 3', 'Nkaimurunya', 27),
								(21, 'Bokole Dispensary', '11254', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 175, 271, 181, 0, 'public', 1, 28, NULL, 'Level 1', 'Airport', 24),
								(22, 'Bomu Medical Hospital (Changamwe)', '11258', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 175, 276, NULL, 1, 'private', 0, 28, NULL, 'Level 1', 'Changamwe', 25),
								(23, 'Bondo District Hospital', '13507', 'central', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', NULL, 17, 58, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'Bondo', 24),
								(24, 'Bukaya Health Centre', '15817', 'standalone', '', '', 874, 18, 281, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', '', 0),
								(25, 'Bungasi Health Center', '15827', 'standalone', 'KEMSA', 'ART,PMTCT,PEP', 874, 18, 64, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', 'Bungasi', 42),
								(26, 'Bungoma District Hospital', '15828', 'standalone', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', 874, 19, 289, NULL, 0, 'public', 0, 3, NULL, 'Level 5', 'Bungoma', 24),
								(27, 'Burnt Forest RHDC (Eldoret East)', '16347', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 103, 291, NULL, 1, 'public', 1, 44, NULL, 'Level 3', 'Olare', 3),
								(28, 'Busia District Hospital Central Site(Ampath clinic)', '15834', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 126, 293, NULL, 1, 'public', 0, 4, NULL, 'Level 4', 'Busia Township', 3),
								(29, 'Butere District Hospital', '15836', 'central', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', 874, 21, 296, NULL, 0, 'public', 0, 3, NULL, 'Level 5', 'Butere', 24),
								(30, 'Chemelil Sugar Community Health Center', '13522', 'standalone', '', 'ART,PMTCT,PEP', NULL, 22, 82, NULL, 0, 'private', 0, 17, NULL, 'Not Classified', 'Nyando', 27),
								(31, 'Children of God Relief Institute (Nyumbani)', '13131', 'standalone', '', 'ART,PEP', 998, 167, 52, NULL, 0, 'ngo', 1, 30, NULL, 'Level 1', 'Karen', 0),
								(32, 'Chuka District Hospital', '11973', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 23, 65, NULL, 0, 'public', 0, 41, NULL, 'Not Classified', 'Kiang''ondu', 42),
								(33, 'Chwele Health Centre', '15860', 'standalone', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', NULL, 24, 59, NULL, 0, 'public', 0, 3, NULL, 'Level 4', 'Chwele', 42),
								(34, 'Coast Provincial General Hospital', '11289', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 205, 332, NULL, 1, 'public', 0, 28, NULL, 'Level 5', 'Tononoka', 24),
								(35, 'Consolata Mission Hospital (Mathari)', '10100', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 42, 95, NULL, 1, 'mission', 1, 36, 1257, 'Level 1', 'Mukaro', 2),
								(36, 'Consolata Nkubu Mission Hospital', '11976', 'standalone', 'KEMSA', 'ART,PMTCT,PEP,LAB', 889, 70, 11, NULL, 0, 'mission', 0, 26, NULL, 'Not Classified', 'Nkubu', 48),
								(37, 'Coptic Hospital', '12905', 'standalone', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', NULL, 47, 340, NULL, 0, 'mission', 0, 30, NULL, 'Not Classified', 'Ngong Road', 48),
								(38, 'Cottolengo Children''s Centre', '12907', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 167, 341, NULL, 1, 'mission', 1, 30, NULL, 'Level 2', 'Karen', 48),
								(39, 'Dandora II  Health Centre', '12912', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 170, 346, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Dandora', 24),
								(40, 'Diani Dispensary', '11304', 'standalone', '', 'ART,PMTCT,PEP', 820, 135, 12, NULL, 0, 'public', 0, 14, NULL, 'Not Classified', 'Diani', 24),
								(41, 'Dream Center Dispensary', '12929', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 167, 76, NULL, 1, 'mission', 1, 30, NULL, 'Level 2', 'Lang''ata', 2),
								(42, 'Eastern Deanery Aids Relief Program', '13220', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 170, 672, NULL, 1, 'mission', 0, 30, NULL, 'Level 3', '', 48),
								(43, 'Embu Provincial General Hospital', '12004', 'central', '', '', 889, 26, 369, NULL, 0, 'public', 0, 6, NULL, 'Not Classified', '', 0),
								(44, 'Emuhaya District Hospital', '15876', 'standalone', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', 874, 27, 372, NULL, 0, 'public', 0, 45, NULL, 'Not Classified', 'Emuhaya', 24),
								(45, 'Engineer District Hospital', '10171', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 59, 375, NULL, 1, 'public', 0, 35, 1120, 'Level 4', 'Kitiri', 17),
								(46, 'Faces  Nyanza (Lumumba)', '13738', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 2, 145, NULL, 1, 'public', 0, 17, NULL, 'Level 1', 'Township', 20),
								(47, 'Family Health Options', '', 'standalone', '', 'ART,PMTCT', NULL, 167, 384, NULL, 0, 'ngo', 0, 30, NULL, 'Not Classified', '', 25),
								(48, 'Forces Memorial Hospital', '13087', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP', 998, 163, 53, NULL, 0, 'private', 0, 30, NULL, 'Not Classified', '', 24),
								(49, 'Friends Lugulu Mission Hospital', '15965', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 30, 398, NULL, 1, 'mission', 0, 3, NULL, 'Level 2', 'Misikhu', 2),
								(50, 'Ganjoni municipal clinic', '', 'satellite', '', '', 889, 63, 179, NULL, 0, '', 0, 28, NULL, 'Level 3', '', 24),
								(51, 'Gatundu District Hospital', '10233', 'central', '', '', NULL, 31, 411, NULL, 0, 'public', 0, NULL, NULL, 'Not Classified', NULL, NULL),
								(52, 'Gertrudes Hospital', '12950', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 47, 414, NULL, 1, 'private', 1, 30, NULL, 'Level 3', 'Muthaiga', 27),
								(53, 'Githongo District Hospital', '12041', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 68, 420, NULL, 0, 'public', 0, 26, NULL, 'Not Classified', 'Marathi', 42),
								(54, 'Got Agulu Sub-District Hospital', '13588', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 17, 60, NULL, 0, 'public', 0, 38, NULL, 'Level 4', '', 24),
								(55, 'GSU HQ Dispensary (Ruaraka)', '12963', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 166, 428, NULL, 1, 'public', 1, 30, NULL, 'Level 2', 'Roysambu', 42),
								(56, 'Gucha District Hospital', '13594', 'central', 'PEPFAR', '', NULL, 33, 429, NULL, 0, 'public', 0, 16, NULL, 'Not Classified', '', 0),
								(57, 'Hamisi District Hospital', '15894', 'standalone', '', 'ART,PMTCT,PEP', 874, 34, 66, NULL, 0, 'public', 1, 45, NULL, 'Not Classified', '', 24),
								(58, 'Holy Family Nangina Mission Hospital', '16073', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 133, 74, NULL, 1, 'mission', 0, 4, NULL, 'Level 4', 'Nangosia', 2),
								(59, 'Homa-Bay District Hospital', '13608', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 11, 449, NULL, 1, 'public', 0, 8, NULL, 'Level 5', 'Homa-Bay', 24),
								(60, 'Homa Hills Health Centre', '13606', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 212, 447, NULL, 1, 'ngo', 0, 8, NULL, 'Level 3', 'Kanam B', 2),
								(61, 'ICAP K. Imarisha Program', '', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', 998, 47, 41, NULL, 0, 'ngo', 0, 30, NULL, 'Not Classified', '', 25),
								(62, 'Iguhu District Hospital', '15899', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 129, 201, NULL, 0, 'public', 1, 11, NULL, 'Not Classified', 'Iguhu', 24),
								(63, 'Ipali Health Centre', '15904', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 27, 205, NULL, 0, 'public', 1, 45, NULL, 'Not Classified', '', 21),
								(64, 'Isiolo District Hospital', '12094', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 71, 206, NULL, 0, 'public', 0, 9, NULL, 'Not Classified', 'Central', 42),
								(65, 'Jamaa Hospital', '12984', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 168, 211, NULL, 1, 'mission', 1, 30, NULL, 'Level 4', 'Makadara', 33),
								(66, 'JKUAT Clinic', '10378', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', 998, 62, 219, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', '', 24),
								(67, 'Kabarnet District Hospital', '14607', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 171, 223, NULL, 1, 'public', 1, 1, NULL, 'Level 4', 'Kapropita', 3),
								(68, 'Kabondo Health Center (Othoro)', '13638', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 35, 13, NULL, 0, 'public', 1, 8, NULL, 'Not Classified', '', 0),
								(69, 'Kajiado DH', '14652', 'standalone', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', NULL, 106, 14, NULL, 0, 'public', 0, 10, NULL, 'Level 5', '', 24),
								(70, 'Kakamega Provincial General Hospital (PGH)', '15915', 'central', '', '', 874, 127, 238, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', '', 0),
								(71, 'IRC Kakuma Hospital', '14579', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 162, 15, NULL, 1, 'ngo', 1, 43, NULL, 'Level 4', 'Kakuma', 18),
								(72, 'Kalokol (AIC) Health Centre', '14663', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 38, 54, 563, 0, 'mission', 1, 43, NULL, 'Level 3', 'Kalokol', 1),
								(73, 'Kambiri Health Centre', '15916', 'standalone', '', '', 874, 128, 246, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', '', 0),
								(74, 'Kangemi Health Centre', '13001', 'central', 'KENYA PHARMA', 'ART,PMTCT,PEP,LAB,RTK', 998, 47, 107, NULL, 1, 'public', 0, 30, NULL, 'Level 1', 'Kangemi', 42),
								(75, 'Kangundo District Hospital', '12177', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 39, 167, NULL, 1, 'public', 0, 22, NULL, 'Level 4', 'Kangundo', 17),
								(76, 'Kapenguria District Hospital', '14701', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 40, 254, NULL, 1, 'public', 0, 47, NULL, 'Level 4', 'Kapenguria', 7),
								(77, 'Kaplong Mision Hospital', '14741', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 137, 16, NULL, 0, 'mission', 0, 2, NULL, 'Not Classified', '', 48),
								(78, 'Kapsabet District Hospital', '14749', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 41, 585, NULL, 1, 'public', 0, 32, NULL, 'Level 1', 'Kapsabet Township', 32),
								(79, 'Karatina District Hospital', '10485', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 61, 184, NULL, 1, 'public', 0, 36, 1257, 'Level 4', 'Konyu', 43),
								(80, 'Kariobangi Health Centre', '13006', 'standalone', '', 'ART,PEP', NULL, 166, 268, NULL, 0, 'public', 1, 30, NULL, 'Kariobangi', 'Kariobangi', 14),
								(81, 'Kasarani Health Center', '13010', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', 998, 166, 17, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Kasarani', 24),
								(82, 'Kathiani Sub-District Hospital', '12230', 'central', '', '', 828, 12, 277, NULL, 0, 'public', 0, 22, NULL, 'Not Classified', '', 0),
								(83, 'Kayole Hospital', '13014', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 173, 376, 0, 'private', 1, 30, NULL, 'Level 3', 'Kayole', 14),
								(84, 'Kaviani Health Centre', '12257', 'satellite', '', '', 828, 12, 284, 82, 0, 'public', 0, 22, NULL, 'Not Classified', '', 0),
								(85, 'Kayole Soweto PHC', '13017', 'standalone', 'PEPFAR', 'ART,PEP', 998, 170, 55, NULL, 1, 'public', 1, 30, NULL, 'Level 1', 'Kayole', 42),
								(86, 'KEMRI Clinic', '18301', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 2, 91, NULL, 1, 'public', 1, 17, NULL, 'Not Classified', 'Central Kisumu', 20),
								(87, 'KEMRI/CRDR FACES Program', '13019', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 163, 587, NULL, 1, 'public', 1, 30, NULL, 'Not Classified', 'Kenyatta', 20),
								(88, 'Kendu Adventist Hospital', '13667', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 212, 290, NULL, 1, 'mission', 0, 8, NULL, 'Level 1', 'North Karachuonyo', 2),
								(89, 'Kendu Bay Sub-District Hospital', '13668', 'standalone', '', '', 864, 35, 18, NULL, 0, 'public', 0, 17, NULL, 'Not Classified', '', 0),
								(90, 'Kenyatta National Hospital', '13023', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 163, 73, NULL, 1, 'public', 0, 30, NULL, 'Level 6', 'Golfcourse', 24),
								(91, 'Kerugoya District Hospital', '10520', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 56, 297, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Kerugoya', 42),
								(92, 'Kiambu District Hospital', '10539', 'central', '', '', NULL, 5, 303, NULL, 0, 'public', 0, NULL, NULL, 'Not Classified', NULL, NULL),
								(93, 'Kikoko Mission Hospital', '12306', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 77, 81, NULL, 1, 'mission', 1, 23, 370, 'Level 1', 'Kikoko', 24),
								(94, 'Kikuyu (PCEA) Hospital', '10603', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 181, 164, NULL, 1, 'mission', 1, 13, 1257, 'Level 1', 'Kikuyu', 2),
								(95, 'Kilifi District Hospital', '11474', 'central', '', '', NULL, 8, 316, NULL, 0, 'public', 0, 14, NULL, 'Not Classified', '', 0),
								(96, 'Kima Mission Hosp, Kisumu', '15946', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 27, 317, NULL, 1, 'mission', 1, 45, NULL, 'Level 4', 'Wekhomo', 0),
								(97, 'Kimbimbi Sub-District Hospital', '10609', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 56, 320, NULL, 1, 'public', 1, 15, 1257, 'Level 3', 'Nyangati', 43),
								(98, 'Kimilili District Hospital', '15950', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 138, 19, NULL, 1, 'public', 0, 3, NULL, 'Level 4', 'Kibingei', 8),
								(99, 'Kiria-ini Mission Hospital', '10627', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 201, 20, NULL, 1, 'mission', 1, 29, 1257, 'Level 1', 'Kiru', 48),
								(100, 'Kisii District Hospital', '13703', 'central', '', '', NULL, 46, 334, NULL, 0, 'public', 0, 16, NULL, 'Not Classified', '', 0),
								(101, 'Kisumu East District Hospital', '13704', 'standalone', '', '', 895, 2, 335, NULL, 0, 'public', 0, 17, NULL, 'Not Classified', '', 0),
								(102, 'Kitui District Hospital', '12366', 'central', '', '', 828, 73, 338, NULL, 0, 'public', 0, 18, NULL, 'Not Classified', '', 0),
								(103, 'Kuria District Hospital', '13726', 'standalone', '', '', 895, 139, 21, NULL, 0, 'public', 0, 27, NULL, 'Not Classified', '', 0),
								(104, 'Khwisero District Hospital', '15940', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 130, 165, NULL, 1, 'public', 0, 11, NULL, 'Level 3', 'Kisa East', 24),
								(105, 'Consolata Hospital Kyeni', '12413', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 177, 333, NULL, 1, 'mission', 1, 6, NULL, 'Level 4', 'Kyeni North', 2),
								(106, 'Langata Health Centre, Nairobi', '13041', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 167, 355, 13, 0, 'public', 1, 30, NULL, 'Level 3', 'Mugumoini', 4),
								(107, 'Likuyani Sub-District Hospital', '15961', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 131, 360, NULL, 1, 'public', 0, 11, NULL, 'Level 3', 'Likuyani', 24),
								(108, 'Liverpool VCT ', '13050', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 47, 363, NULL, 1, 'ngo', 1, 30, NULL, 'Level 2', 'Kilimani', 23),
								(109, 'Lugari District Hospital', '15969', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 45, 371, NULL, 1, 'public', 0, 11, NULL, 'Level 4', 'Marakusi', 42),
								(110, 'St Elizabeth Lwak Mission Hospital', '13739', 'standalone', 'PEPFAR', 'ART,PEP,LAB,RTK', 874, 98, 505, NULL, 1, 'mission', 0, 38, NULL, 'Level 3', 'West Asembo', 2),
								(111, 'Mabusi Health Centre', '15983', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 45, 376, NULL, 1, 'public', 1, 11, NULL, 'Level 2', 'Musemwa', 0),
								(112, 'Macalder District Hospital', '13745', 'central', 'Ministry of Health', 'ART,PMTCT,PEP,LAB,RTK', 895, 211, 22, NULL, 1, 'public', 0, 27, NULL, 'Level 4', 'South East Kadem', 40),
								(113, 'Machakos District Hosp', '12438', 'central', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', 828, 12, 378, NULL, 0, 'public', 0, 22, NULL, 'Level 5', '', 24),
								(114, 'Madiany District Hospital', '13747', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 98, 144, NULL, 1, 'public', 0, 38, NULL, 'Level 4', 'East Uyoma', 42),
								(115, 'Pumwani Majengo Dispensary (UNITID)', 'K470301', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 165, 96, NULL, 1, 'public', 1, 30, NULL, 'Not Classified', 'Pumwani Majengo', 21),
								(116, 'Makindu District Hospital', '12455', 'central', 'MOH,PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 77, 158, NULL, 1, 'public', 0, 23, NULL, 'Level 4', 'Makindu', 42),
								(117, 'Makueni District Hospital', '12457', 'central', '', '', 828, 77, 75, NULL, 0, 'public', 0, 23, NULL, 'Not Classified', '', 0),
								(118, 'Makunga Rural Health Demonstration Centre', '15991', 'standalone', '', '', 874, 18, 382, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', '', 0),
								(119, 'Malava Sub-District Hospital', '15996', 'central', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', 874, 140, 23, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', 'Mugai', 0),
								(120, 'Malindi District Hospital', '11555', 'central', '', '', NULL, 64, 385, NULL, 0, 'public', 0, 14, NULL, 'Not Classified', '', 0),
								(121, 'Manyala Sub-District Hospital', '15999', 'central', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', 874, 21, 24, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', 'Manyala', 24),
								(122, 'Manyuada Health Centre', '13770', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', 895, 29, 389, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', '', 0),
								(123, 'Refuge Point', 'K470910', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 47, 796, NULL, 1, '', 1, 30, NULL, 'Level 2', '', 0),
								(124, 'Maragua Hospital', '10686', 'central', '', '', NULL, 58, 393, NULL, 0, 'public', 0, 29, NULL, 'Not Classified', '', 0),
								(125, 'Mariakani District Hospital', '11566', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 50, 218, NULL, 1, 'public', 0, 14, NULL, 'Level 4', 'Mariakani', 24),
								(126, 'Mary Immaculate Mission Hospital', '10700', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 281, 225, 174, 0, 'mission', 1, 36, 1257, 'Level 3', 'Mweiga', 43),
								(127, 'Masaba District Hospital', '13678', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 141, 25, 423, 0, 'public', 1, 34, NULL, 'Not Classified', 'Keroka', 24),
								(128, 'Maseno University Clinic', '13782', 'satellite', 'PEPFAR', 'PMTCT', 864, 29, 26, 231, 0, 'private', 1, 17, NULL, 'Level 2', 'North West Kisumu', 26),
								(129, 'Mater Hospital', '13074', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 168, 233, NULL, 1, 'mission', 0, 30, NULL, 'Level 1', 'Mukuru Nyayo', 2),
								(130, 'Matete Health Centre', '16005', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 45, 61, NULL, 1, 'public', 0, 11, NULL, 'Level 2', 'Chebaywa', 24),
								(131, 'Mathare  Hospital', '13076', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP', 998, 165, 102, NULL, 1, 'public', 1, 30, NULL, 'Level 1', 'Mathare', 42),
								(132, 'Matoso Health Clinic (Lalmba)', '13793', 'standalone', 'PEPFAR', 'ART,PEP,LAB,RTK', 895, 211, 151, NULL, 1, 'ngo', 1, 27, NULL, 'Level 1', 'West Kadem', 22),
								(133, 'Matungulu Health Centre', '16439', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 39, 241, 75, 0, 'public', 1, 22, NULL, 'Level 3', 'Kingoti', 24),
								(134, 'Matuu District Hospital', '12488', 'central', '', '', 828, 89, 243, NULL, 0, 'public', 0, 22, NULL, 'Not Classified', '', 0),
								(135, 'Maua Methodist Hospital', '12492', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 180, 92, NULL, 1, 'mission', 1, 26, NULL, 'Level 4', 'Maua', 2),
								(136, 'Mbagathi District Hospital', '13080', 'standalone', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', NULL, 163, 263, NULL, 0, 'public', 0, 30, NULL, 'Level 5', 'Mbagathi', 42),
								(137, 'Mbeere District Hospital', '16467', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 142, 27, NULL, 1, 'public', 0, 6, NULL, 'Level 4', 'Nthawa', 42),
								(138, 'Merlin Kisii', '', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', 895, 46, 56, NULL, 0, '', 0, 16, NULL, 'Not Classified', '', 0),
								(139, 'Meru District Hospital', '12516', 'central', '', '', 889, 79, 285, NULL, 0, 'public', 0, 26, NULL, 'Not Classified', '', 0),
								(140, 'Mewa Hospital', '11600', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 820, 16, 302, NULL, 0, 'mission', 0, 28, NULL, 'Not Classified', 'Majengo', 48),
								(141, 'Migori District Hospital', '13805', 'central', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', NULL, 96, 308, NULL, 0, 'public', 0, 27, NULL, 'Not Classified', 'Central Suna', 24),
								(142, 'Mombasa CBHC', '11614', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 175, 124, NULL, 1, 'ngo', 0, 28, NULL, 'Level 2', 'Ganjoni', 2),
								(143, 'Mt. Kenya Sub-District Hospital', '10739', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 25, 47, 174, 0, 'public', 1, 36, 1257, 'Level 1', 'Mukaro', 43),
								(144, 'AMPATH (Moi Teaching Referral Hospital)', '15204', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 103, 43, NULL, 1, 'public', 1, 44, NULL, 'Level 6', 'Chepkoilel', 3),
								(145, 'Mtwapa Health Centre', '11672', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 8, 367, 95, 0, 'public', 0, 14, NULL, 'Not Classified', '', 0),
								(146, 'Muhoroni Sub-District Hospital', '13831', 'central', '', '', 895, 22, 368, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', '', 0),
								(147, 'Mukuru Kwa Reuben FBO Clinic', '13173', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 168, 390, NULL, 1, 'mission', 1, 30, NULL, 'Level 2', 'Mukuru', 0),
								(148, 'Mukurweini District Hospital', '10763', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 42, 381, NULL, 1, 'public', 0, 36, 1257, 'Level 1', 'Muhito', 43),
								(149, 'Muranga District Hospital', '10777', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 184, 391, NULL, 1, 'public', 0, 29, NULL, 'Level 4', 'Township', 42),
								(150, 'Muriranjas Sub-District Hospital', '10782', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 184, 100, NULL, 1, 'public', 0, 29, 1257, 'Level 1', 'Mugoiri', 43),
								(151, 'Muthale Mission Hospital', '12587', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 73, 399, NULL, 0, 'mission', 1, 18, NULL, 'Not Classified', 'Muthale', 48),
								(152, 'Mutomo Mission Hospital', '12604', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 82, 178, NULL, 1, 'mission', 1, 18, NULL, 'Level 4', 'Kibwea', 2),
								(153, 'Mwala District Hospital', '12618', 'central', 'Ministry of Health, PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 83, 166, NULL, 1, 'public', 0, 22, 370, 'Level 4', 'Mwala', 17),
								(154, 'Mwea sub D Hospital', '', 'standalone', '', 'ART,PMTCT,PEP', NULL, 56, 40, NULL, 0, '', 0, 15, NULL, 'Not Classified', '', 0),
								(155, 'Mwingi District Hospital', '12626', 'standalone', '', '', 828, 84, 406, NULL, 0, 'public', 0, 18, NULL, 'Not Classified', '', 0),
								(156, 'Nairagie Health Centre', '15277', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 813, 144, 44, NULL, 0, 'public', 0, 33, NULL, 'Not Classified', '', 0),
								(157, 'Nairobi Women''s Hospital', '13117', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 47, 89, NULL, 1, 'private', 0, 30, NULL, 'Level 4', 'Kilimani', 27),
								(158, 'Naivasha District Hospital', '15280', 'central', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', 1728, 51, 409, NULL, 0, 'public', 0, 31, NULL, 'Not Classified', '', 0),
								(159, 'Rift Valley Provincial General Hospital', '15288', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 20, 112, NULL, 1, 'public', 1, 31, NULL, 'Level 5', 'Nakuru Town', 24),
								(160, 'Namasoli Health Center', '16065', 'satellite', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', 874, 21, 28, 104, 0, 'mission', 1, 11, NULL, 'Not Classified', 'Namasoli', 19),
								(161, 'Nanyuki District Hospital', '15305', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 111, 29, NULL, 1, 'public', 0, 20, NULL, 'Level 4', 'Nanyuki', 7),
								(162, 'Navakholo District Hospital', '16078', 'standalone', '', '', 874, 127, 434, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', '', 0),
								(163, 'Nazareth Hospital', '10825', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 197, 198, NULL, 1, 'mission', 1, 13, 1257, 'Level 4', 'Karabaini', 2),
								(164, 'New Life Home Nairobi', '13120', 'central', '', '', NULL, 47, 450, NULL, 0, 'ngo', 0, 30, NULL, 'Not Classified', '', 0),
								(165, 'JOOTRH', '13939', 'central', '', '', NULL, 2, 486, NULL, 0, 'public', 0, 17, NULL, 'Not Classified', '', 0),
								(166, 'Ngaira Rhodes Dispensary', '13121', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 169, 455, NULL, 1, 'public', 1, 30, NULL, 'Level 2', 'Central Business District', 42),
								(167, 'North Kinangop Catholic Hospital', '10887', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 186, 469, NULL, 1, 'mission', 1, 35, 1257, 'Level 4', 'Gitiri', 2),
								(168, 'Nyahururu District Hospital', '10890', 'central', '', '', 678, 59, 476, NULL, 0, 'public', 0, 35, NULL, 'Not Classified', '', 0),
								(169, 'Nyakach District Hospital', '13921', 'standalone', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', 895, 2, 67, NULL, 0, '', 0, 17, NULL, 'Not Classified', '', 0),
								(170, 'Nyambene District Hospital', '12684', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 180, 480, NULL, 1, 'public', 0, 26, NULL, 'Level 4', 'Maua', 42),
								(171, 'Nyamira District Hospital', '13912', 'central', '', '', 895, 97, 481, NULL, 0, 'public', 0, 34, NULL, 'Not Classified', '', 0),
								(172, 'Nyando District Hospital', '13921', 'central', '', '', 895, 22, 483, NULL, 0, 'public', 0, 17, NULL, 'Not Classified', '', 0),
								(174, 'Nyeri Provincial General Hospital (PGH)', '10903', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 25, 195, NULL, 1, 'public', 0, 36, 1257, 'Level 5', 'Mukaro', 42),
								(175, 'Olkalau Sub District Hospital (Nyandarua)', '10916', 'standalone', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', NULL, 59, 31, NULL, 0, 'public', 0, 35, NULL, 'Not Classified', '', 0),
								(176, 'Othaya Sub-District. Hospital', '10922', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 42, 517, NULL, 1, 'public', 0, 36, 1257, 'Level 1', 'Iriaini', 43),
								(177, 'Our  Lady of Lourdes Mwea  Hospital', '10808', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 48, 196, NULL, 1, 'mission', 1, 15, 1257, 'Level 4', 'Tebere', 2),
								(178, 'Papkodero Health Centre', '14013', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 98, 520, 114, 0, 'public', 1, 38, NULL, 'Level 1', 'Central Uyoma', 24),
								(179, 'Chogoria (PCEA) Mission Hospital', '11970', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 76, 93, NULL, 1, 'mission', 1, 41, NULL, 'Level 1', 'Chogoria', 2),
								(181, 'Port Reitz Hospital - Kilindini District Hospital', '11740', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 175, 491, NULL, 1, 'public', 0, 28, NULL, 'Level 1', 'Portreitz', 24),
								(182, 'Pumwani Maternity Hospital', '13156', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 165, 33, NULL, 1, 'public', 1, 30, NULL, 'Level 5', 'Pumwani', 21),
								(183, 'Rachuonyo District Hospital', '14022', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 35, 180, NULL, 1, 'public', 0, 8, NULL, 'Level 1', 'Kowidi', 8),
								(185, 'Remand Dispensary', '13161', 'standalone', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', 998, 168, 34, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', '', 0),
								(186, 'Rera Dispensary', '14042', 'satellite', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', 820, 9, 529, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', '', 0),
								(187, 'Riruta Health Centre', '13165', 'standalone', '', '', 998, 163, 494, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', '', 0),
								(188, 'Rongai Health Centre', '15495', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 20, 35, NULL, 0, 'public', 1, 31, NULL, 'Not Classified', 'Rongai', 42),
								(189, 'Rongo District Hospital', '14058', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 65, 152, NULL, 1, 'public', 0, 27, NULL, 'Level 4', 'Central Kamagambo', 41),
								(190, 'Runyenjes District Hospital', '12719', 'central', '', '', 889, 86, 495, NULL, 0, 'public', 0, 6, NULL, 'Not Classified', '', 0),
								(191, 'Saradidi Health Centre', '14068', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 98, 538, NULL, 0, 'public', 0, 38, NULL, 'Level 2', 'Central Asembo', 24),
								(192, 'Shibwe Sub-District Hospital', '16107', 'standalone', '', '', 874, 129, 496, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', '', 0),
								(193, 'Siaya District Hospital', '14080', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 9, 497, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'Siaya', 42),
								(194, 'Sony Medical Centre', '14097', 'standalone', 'PEPFAR', 'ART,PEP,LAB,RTK', 895, 65, 498, NULL, 1, 'private', 1, 27, NULL, 'Level 3', 'Central Sakwa', 50),
								(195, 'SOS Medical Centre Buruburu', '', 'standalone', '', '', NULL, 168, 820, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(196, 'SOS Dispensary', '13189', 'standalone', '', '', NULL, 165, 36, NULL, 0, 'ngo', 0, 30, NULL, 'Not Classified', '', 0),
								(197, 'St Elizabeth Chiga Health Centre', '14106', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 2, 503, NULL, 1, 'mission', 0, 17, NULL, 'Level 3', 'East Kolwa', 2),
								(198, 'St. Camillus Mission Hospital (karungu)', '14103', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 211, 506, NULL, 1, 'mission', 0, 27, NULL, 'Not Classified', 'West Karungu', 2),
								(199, 'St Elizabeth Hospital, Mukumu', '16030', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 128, 504, NULL, 1, 'mission', 0, 11, NULL, 'Not Classified', 'Khayega', 2),
								(200, 'St. Francis Community Hospital', '13202', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 166, 77, NULL, 1, 'mission', 1, 30, NULL, 'Level 1', 'Githurai', 9),
								(201, 'St Joseph''s Shelter Of Hope', '11817', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 219, 507, NULL, 1, 'mission', 1, 39, NULL, 'Level 2', 'Ikanga', 2),
								(202, 'St. Joseph Mukasa Dispensary', '13208', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 166, 39, NULL, 1, 'mission', 1, 30, NULL, 'Level 2', 'KAHAWA', 9),
								(203, 'Nyabondo Mission Hospital', '13864', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 210, 148, NULL, 1, 'mission', 0, 17, NULL, 'Level 4', 'Oboch', 2),
								(204, 'St Joseph Mission Hospital Migori', '14110', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 96, 149, NULL, 1, 'mission', 0, 27, NULL, 'Level 1', 'Central Suna', 2),
								(205, 'St Luke''s Mission Hospital ACK (Kaloleni)', '11818', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 50, 163, NULL, 1, 'mission', 1, 14, NULL, 'Level 4', 'Kaloleni', 2),
								(206, 'St Mary''s Hospital (Naivasha)', '15654', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 51, 502, NULL, 1, 'mission', 1, 31, NULL, 'Level 4', 'Gilgil', 0),
								(207, 'St Mary''s Hospital (Mumias)', '16141', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 18, 501, NULL, 1, 'mission', 1, 11, NULL, 'Level 3', 'Nabongo', 2),
								(208, 'St. Mary''s Mission Hospital-Langata', '13218', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 167, 500, NULL, 0, 'mission', 0, 30, NULL, 'Not Classified', 'Kibera', 9),
								(209, 'St Monica''s  Mission Hospital, Kisumu', '14120', 'standalone', 'PEPFAR', 'ART,PEP,LAB,RTK', 895, 2, 94, NULL, 1, 'mission', 0, 17, NULL, 'Level 3', 'East  Kajulu', 2),
								(210, 'St. Orsola Mission Hospital (community of St. Egidio)', '12769', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 215, 105, NULL, 1, 'mission', 1, 41, NULL, 'Level 4', 'Chiakariga', 12),
								(211, 'STC Casino, Nairobi', '13193', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 169, 159, NULL, 1, 'public', 0, 30, NULL, 'Level 1', ' City Square', 42),
								(212, 'Suba District Hospital', '14130', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 99, 523, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', 'Kakisingri Central', 42),
								(213, 'Tabaka Mission Hospital', '14139', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 92, 544, NULL, 1, 'mission', 1, 16, NULL, 'Level 1', 'S. M. Chache', 2),
								(214, 'Tabitha Medical Centre', '13234', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 167, 581, NULL, 1, 'ngo', 1, 30, NULL, 'Level 2', 'Sarang''Ombe', 34),
								(215, 'Tenwek Mission Hospital', '15719', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 102, 88, NULL, 1, 'mission', 0, 2, NULL, 'Level 4', 'Township', 32),
								(216, 'Tharaka District Hospital', '12795', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 215, 38, NULL, 1, 'public', 0, 41, NULL, 'Level 3', 'Marimanti', 34),
								(217, 'Thika District Hospital', '11094', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 62, 548, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Central', 42),
								(218, 'Tigoni District Hospital', '11104', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 54, 549, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Limuru', 24),
								(219, 'Tumaini Childrens'' Home', '17385', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 167, 68, NULL, 0, 'private', 0, 30, NULL, 'Not Classified', 'Langata', 0),
								(220, 'Tumaini Childrens'' Home (Nanyuki)', 'K551', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 283, 821, NULL, 1, 'private', 1, 20, NULL, 'Not Classified', 'Nanyuki - Rural', 27),
								(221, 'Tumaini Medical Centre', '16204', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 148, 553, NULL, 1, 'private', 1, 25, NULL, 'Not Classified', 'Mountain', 24),
								(222, 'Tumutumu (PCEA) Hospital', '11124', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 202, 185, NULL, 1, 'mission', 1, 36, 1257, 'Level 1', 'Mbogoini', 2),
								(223, 'Ukwala Sub-District Hospital', '14156', 'standalone', 'PEPFAR', 'PMTCT', 820, 9, 57, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'Ukwala', 42),
								(224, 'University Health Services (UNITID)', '13242', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 47, 558, NULL, 1, 'private', 1, 30, NULL, 'Level 2', 'Kilimani', 42),
								(225, 'University of Manitoba Research Group', '', 'standalone', 'PEPFAR', 'ART,PMTCT', NULL, 47, 1418, NULL, 0, '', 1, 30, NULL, 'Not Classified', '', 0),
								(226, 'Usigu Health centre', '14164', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 17, 563, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'C Yimbo', 42),
								(227, 'Uyawi Dispensary', '14165', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 820, 17, 564, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'C Sakwa', 42),
								(228, 'Uzima Health Centre', '13246', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 166, 45, NULL, 1, 'private', 1, 30, NULL, 'Level 1', 'Kariobangi', 9),
								(229, 'Vihiga District Hosptial', '16157', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 43, 565, NULL, 0, 'public', 0, 45, NULL, 'Not Classified', 'Wamuluma', 42),
								(230, 'Waithaka Health Centre', '13249', 'standalone', '', '', NULL, 163, 69, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', '', 0),
								(231, 'KEMRI/Walter Reed Project Kericho', '1001175', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 108, 138, NULL, 1, 'ngo', 1, 12, NULL, 'Level 4', '', 32),
								(232, 'Westlands Health Centre', '13258', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 47, 573, 74, 0, 'public', 1, 30, NULL, 'Not Classified', 'Parklands', 0),
								(233, 'Wesu District Hospital', '11906', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 221, 574, NULL, 1, 'public', 0, 39, NULL, 'Level 4', 'Wundanyi', 24),
								(234, 'Yala Sub-District Hospital', '14175', 'central', '', '', NULL, 9, 576, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', '', 0),
								(235, 'Abidha Health Center', '13461', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB', 895, 98, 63, 450, 0, 'public', 1, 38, NULL, 'Level 3', 'East Asembo', 24),
								(236, 'Angurai Health Center', '15800', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 214, 10, 554, 0, 'public', 1, 4, NULL, 'Level 3', 'Angurai', 3),
								(239, 'Githunguri Health Centre', '10269', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB', NULL, 5, 421, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Githunguri', 42),
								(242, 'Difathas Health Centre', '10110', 'satellite', '', 'ART,PMTCT,PEP', 678, 56, 350, 91, 0, 'public', 1, 15, NULL, 'Not Classified', '', 0),
								(251, 'Kangema Sub District Hospital', '10470', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 184, 248, NULL, 1, 'public', 0, 29, NULL, 'Level 1', 'Muguru', 42),
								(252, 'Kirogo Health Centre', '10636', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 184, 324, 97, 0, 'public', 1, 29, NULL, 'Level 3', 'Kahuhia', 42),
								(253, 'Gaichanjiru Hospital', '10199', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 58, 400, 124, 0, 'mission', 0, 29, NULL, 'Not Classified', 'Gaichanjiru', 19),
								(261, 'Ngorika Dispensary', '10871', 'satellite', '', '', 998, 59, 464, 168, 0, 'public', 0, 35, NULL, 'Not Classified', '', 0),
								(262, 'Shamata Health Centre', '11004', 'satellite', '', '', 678, 59, 539, 168, 0, 'public', 0, 35, NULL, 'Not Classified', '', 0),
								(264, 'Geta Bush Dispensary', '10244', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 187, 415, 45, 0, 'public', 1, 35, 1120, 'Level 2', 'Geta', 33),
								(265, 'Geta Forest Dispensary', '10245', 'satellite', 'PEPFAR', 'PMTCT', 1728, 187, 416, 45, 0, 'public', 1, 35, NULL, 'Level 2', 'Geta', 33),
								(266, 'Heni Dispensary', '10312', 'satellite', 'PEPFAR', 'PMTCT', 1728, 20, 444, 45, 0, 'public', 1, 31, NULL, 'Level 2', 'Magumu', 33),
								(267, 'Karangatha Health Centre', '10481', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 20, 472, 45, 0, 'public', 1, 31, 1120, 'Level 3', 'Nyakio', 33),
								(268, 'Kenton Dispensary', '10513', 'satellite', 'PEPFAR', 'PMTCT', 1728, 186, 292, 45, 0, 'public', 1, 35, NULL, 'Level 2', 'Magumu', 33),
								(270, 'Murungaru Health Centre', '10786', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 20, 396, 45, 0, 'public', 1, 31, 1120, 'Level 2', 'Engineer', 33),
								(271, 'Nandarasi Dispensary', '10820', 'satellite', 'PEPFAR', 'PMTCT', 1728, 186, 430, 45, 0, 'public', 1, 35, NULL, 'Level 2', 'Ndunyunjeru', 33),
								(272, 'Ndemi Dispensary', '10832', 'satellite', 'PEPFAR', 'PMTCT', 1728, 20, 436, 45, 0, 'public', 1, 31, NULL, 'Level 2', 'Malewa', 33),
								(274, 'Old Mawingu Dispensary', '10912', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 20, 513, 45, 0, 'public', 1, 31, 1120, 'Level 2', 'Gitiri', 33),
								(275, 'Turasha Dispensary', '11126', 'satellite', 'PEPFAR', 'PMTCT', 1728, 187, 554, 45, 0, 'public', 1, 35, NULL, 'Level 2', 'Kiriko', 33),
								(276, 'Wanjohi Health Centre', '11173', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 111, 569, 45, 0, 'public', 1, 20, 1120, 'Level 3', 'Wanjohi', 33),
								(277, 'Weru Dispensary (Nyandarua South)', '11183', 'satellite', 'PEPFAR', 'PMTCT', 1728, 20, 572, 45, 0, 'public', 1, 31, NULL, 'Level 2', 'Engineer', 33),
								(278, 'Bellevue Health Centre', '10055', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 183, 264, 79, 0, 'public', 1, 36, 1257, 'Level 1', 'Kamariki', 43),
								(279, 'Endarasha Rural Health Centre', '10170', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 281, 373, 174, 0, 'public', 1, 36, 1257, 'Level 3', 'Endarasha', 43),
								(280, 'Jamii Hospital', '10368', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 202, 214, 174, 0, 'private', 1, 36, 1257, 'Level 4', 'Iriaini', 43),
								(282, 'Naromoru Health Centre', '10822', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 183, 432, 174, 0, 'public', 1, 36, 1257, 'Level 3', 'Naromoru', 43),
								(283, 'Narumoru Catholic Dispensary', '16816', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 183, 433, NULL, 0, 'mission', 0, 36, NULL, 'Level 3', 'Naromoru', 43),
								(284, 'Ngorano Health Centre', '10870', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 202, 463, 79, 0, 'public', 1, 36, 1257, 'Level 1', 'Ngorano', 43),
								(285, 'Warazo Rural Health Centre', '11176', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 183, 570, 79, 0, 'public', 1, 36, 1257, 'Level 3', 'Munyu', 42),
								(286, 'Gichiche Health Centre', '10249', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 42, 417, 176, 0, 'public', 1, 36, 1257, 'Level 1', 'Chinga', 43),
								(287, 'GK Prison Dispensary (Kingongo)', '10286', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 25, 422, 174, 0, 'private', 1, 36, 1257, 'Level 2', 'Mukaro', 2),
								(289, 'Kiganjo Health center', '10582', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 31, 314, 174, 0, 'public', 1, 13, NULL, 'Level 2', 'Kiganjo', 42),
								(291, 'Outspan Hospital (Nyeri)', '10924', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 25, 191, 174, 0, 'private', 1, 36, 1257, 'Level 4', 'Mukaro', 43),
								(292, 'Wamagana Health Centre', '11161', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 42, 568, 174, 0, 'public', 1, 36, 1257, 'Level 3', 'Karundu', 43),
								(293, 'Gathanji Health CentreC', '17058', 'satellite', '', '', NULL, 62, 407, 92, 0, 'public', 0, 13, NULL, 'Not Classified', '', 0),
								(294, 'Gatura Healh Centre', '10236', 'standalone', '', '', NULL, 62, 412, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', '', 0),
								(295, 'Kirwara S/DISTRICT', '10639', 'satellite', '', '', 678, 62, 326, 217, 0, 'public', 0, 13, NULL, 'Not Classified', '', 0),
								(296, 'Ngoliba Health Centre', '10869', 'satellite', '', '', NULL, 62, 462, 217, 0, 'public', 0, 13, NULL, 'Not Classified', '', 0),
								(297, 'Ruiru Sub-District Hospital', '10973', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 62, 534, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', '', 0),
								(298, 'Giriama Mission', '11392', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 50, 419, 125, 0, 'mission', 1, 14, 1258, 'Level 2', 'Kaloleni', 24),
								(299, 'Gotani Dispensary', '11404', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 50, 427, 125, 0, 'public', 1, 14, 1258, 'Level 2', 'Kaya Fungo', 24),
								(300, 'Kombeni Health Centre', '11498', 'satellite', 'PEPFAR', 'PMTCT', 889, 50, 342, NULL, 0, 'public', 1, 14, NULL, 'Level 2', 'Ruruma', 24),
								(301, 'Rabai Health Centre', '11748', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 50, 522, NULL, 1, 'public', 1, 14, NULL, 'Level 3', 'Rabai', 24),
								(302, 'Tsangatsini Dispensary', '11859', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 50, 552, 125, 0, 'public', 1, 14, NULL, 'Level 2', 'Tsangatsini', 24),
								(303, 'Bamba Health Centre', '11238', 'standalone', '', '', NULL, 8, 247, 95, 0, 'private', 0, 14, NULL, 'Not Classified', '', 0),
								(304, 'Chasimba Health Centre', '11282', 'standalone', '', '', NULL, 8, 300, 95, 0, 'public', 0, 14, NULL, 'Not Classified', '', 0),
								(305, 'Matsangoni Health Centre', '11580', 'standalone', '', '', NULL, 8, 239, 95, 0, 'public', 0, 14, NULL, 'Not Classified', '', 0),
								(306, 'Takaungu Dispensary', '11836', 'satellite', '', '', NULL, 8, 545, 95, 0, 'public', 0, 14, NULL, 'Not Classified', '', 0),
								(307, 'Vipingo Rural Health Demonstration Centre', '11881', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 8, 566, 95, 0, 'public', 1, 14, NULL, 'Not Classified', 'Junju', 42),
								(308, 'Vitengeni Health Centre', '11883', 'standalone', 'PEPFAR', '', NULL, 8, 567, 95, 0, 'public', 0, 14, NULL, 'Not Classified', '', 0),
								(309, 'Bomu Medical Centre (Likoni)', '11259', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 196, 278, 22, 0, 'private', 1, 28, NULL, 'Level 2', 'Likoni', 27),
								(310, 'Chaani (MCM) Dispensary', '11274', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 175, 299, 181, 0, 'public', 1, 28, NULL, 'Level 2', 'Chaani', 24),
								(311, 'Ganjoni Women''s Health Project', '11273', 'standalone', 'PEPFAR', 'ART,PEP', 889, 205, 84, NULL, 1, 'mission', 1, 28, NULL, 'Level 3', 'Ganjoni', 2),
								(312, 'Magongo Health Centre', '11538', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 63, 380, NULL, 0, 'public', 0, 28, NULL, 'Not Classified', 'Changamwe', 42),
								(313, 'Mtongwe Health Centre', '11669', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 196, 97, NULL, 1, 'public', 1, 28, NULL, 'Level 1', 'Mtongwe', 0),
								(314, 'Adu Dispensary', '11198', 'standalone', 'PEPFAR', '', NULL, 64, 202, 120, 0, 'public', 0, 14, NULL, 'Not Classified', '', 0),
								(315, 'Baricho Dispensary (Malindi)', '11248', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 820, 8, 261, 120, 0, 'public', 0, 14, NULL, 'Not Classified', 'Bungale', 24),
								(316, 'Gede Health Centre', '11387', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 64, 413, 120, 0, 'private', 1, 14, NULL, 'Not Classified', 'Gede', 27),
								(317, 'Gongoni Health Centre', '11401', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 8, 425, 120, 0, 'public', 1, 14, NULL, 'Not Classified', 'Gongoni', 24),
								(318, 'Malanga AIC Dispensary', '11553', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 64, 383, 120, 0, 'mission', 1, 14, NULL, 'Not Classified', 'Lango Baya', 12),
								(319, 'Marafa Health Centre (Magarini)', '11562', 'satellite', '', '', NULL, 64, 392, 120, 0, 'public', 0, 14, NULL, 'Not Classified', '', 0),
								(320, 'Marereni Dispensary', '11563', 'satellite', '', '', NULL, 64, 395, 120, 0, 'public', 0, 14, NULL, 'Not Classified', '', 0),
								(321, 'Municipal Health Centre', '11677', 'satellite', '', 'PEP', NULL, 64, 387, 120, 0, 'public', 1, 14, NULL, 'Level 1', 'Malindi', 24),
								(322, 'Royal Nursing Home', '14061', 'satellite', 'PEPFAR', 'PMTCT', 895, 65, 533, 189, 0, 'private', 1, 27, NULL, 'Level 3', 'Central Kamagambo', 41),
								(323, 'Mbale Health Centre', '11589', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 221, 258, 233, 0, 'public', 1, 39, NULL, 'Level 3', 'Mbale', 24),
								(324, 'Mgange Nyika Health Centre', '11603', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 221, 304, 233, 0, 'public', 1, 39, NULL, 'Level 3', 'Mgange', 24),
								(325, 'Nyache Health Centre', '11720', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 221, 474, 233, 0, 'public', 1, 39, NULL, 'Level 3', 'Wumingu', 24),
								(326, 'Wundanyi Sub-District Hospital', '11908', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 221, 575, 233, 0, 'public', 1, 39, NULL, 'Level 4', 'Wundanyi', 24),
								(327, 'Nkubu Health Centre', '12666', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', 889, 70, 116, NULL, 0, 'public', 0, 26, NULL, 'Not Classified', '', 24),
								(328, 'Nguluni Health Centre', '12657', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 39, 466, 75, 0, 'public', 1, 22, 370, 'Level 3', 'Nguluni', 24),
								(329, 'Plateau MH', '15464', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 103, 521, 27, 0, 'private', 1, 44, NULL, 'Level 3', 'Plateau', 3),
								(330, 'Kibwezi Sub District Hospital', '12291', 'standalone', 'MOH/PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 72, 157, NULL, 0, 'public', 0, 23, NULL, 'Level 4', 'Kikumbulyu', 17),
								(331, 'Mtito Andei Health Centre', '12547', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 72, 347, NULL, 0, 'public', 1, 23, NULL, 'Not Classified', 'Mtito Andei', 42),
								(332, 'Kanyangi Sub-District Hospital', '12184', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 89, 252, 102, 0, 'public', 1, 18, NULL, 'Not Classified', 'Kanyangi', 24),
								(333, 'Kauwi Sub-District Hospital', '12255', 'satellite', '', '', 828, 73, 283, 102, 0, 'public', 0, 18, NULL, 'Not Classified', '', 0),
								(334, 'Kisasi Health Centre', '12340', 'satellite', '', '', 828, 73, 330, 102, 0, 'public', 0, 18, NULL, 'Not Classified', '', 0),
								(335, 'Kwa vonza Health Centre', '12396', 'satellite', '', '', 828, 73, 351, 102, 0, 'public', 0, 18, NULL, 'Not Classified', '', 0),
								(336, 'Yatta Health Centre', '12853', 'satellite', '', '', 828, 73, 577, 102, 0, 'public', 0, 18, NULL, 'Not Classified', '', 0),
								(337, 'Zion Health Centre', '12859', 'satellite', '', '', NULL, 73, 578, 95, 0, 'private', 0, 18, NULL, 'Not Classified', '', 0),
								(338, 'Kyuso District Hospital', '12420', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 74, 122, NULL, 1, 'public', 0, 18, NULL, 'Level 4', 'Kyuso', 43),
								(339, 'Tseikuru Sub-District Hospital', '12805', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 74, 176, 338, 0, 'public', 1, 18, NULL, 'Level 4', 'Tseikuru', 17),
								(340, 'Laisamis Hospital', '16215', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 200, 349, 565, 0, 'mission', 1, 25, NULL, 'Level 3', 'Laisamis', 24),
								(341, 'Muthambi Health Centre', '12589', 'standalone', '', '', 889, 76, 588, NULL, 0, 'public', 0, 41, NULL, 'Not Classified', '', 0),
								(342, 'Mitaboni Health Centre', '12530', 'standalone', '', '', 828, 12, 590, NULL, 0, 'public', 0, 22, NULL, 'Not Classified', '', 0),
								(343, 'Kathonzweni Health Centre', '12236', 'satellite', '', '', 828, 77, 279, NULL, 0, 'public', 0, 23, NULL, 'Not Classified', '', 0),
								(344, 'Kitise Health Centre', '12365', 'satellite', '', '', 828, 77, 337, NULL, 0, 'public', 0, 23, NULL, 'Not Classified', '', 0),
								(345, 'Mavindini Health Centre.', '12493', 'satellite', '', '', 828, 77, 262, NULL, 0, 'public', 0, 23, NULL, 'Not Classified', '', 0),
								(346, 'Mukuyuni Health Centre.', '12565', 'standalone', '', '', 828, 77, 386, NULL, 0, 'public', 0, 23, NULL, 'Not Classified', '', 0);
								INSERT INTO `sync_facility` (`id`, `name`, `code`, `category`, `sponsors`, `services`, `manager_id`, `district_id`, `address_id`, `parent_id`, `ordering`, `affiliation`, `service_point`, `county_id`, `hcsm_id`, `keph_level`, `location`, `affiliate_organization_id`) VALUES
								(347, 'Nunguni Health Centre', '', 'satellite', '', '', 828, 77, 1417, NULL, 0, '', 0, 23, NULL, 'Not Classified', '', 0),
								(348, 'Kalawa Health Centre', '1247', 'satellite', '', '', 828, 78, 242, NULL, 0, 'public', 0, 23, NULL, 'Not Classified', '', 0),
								(349, 'Kisau Health Centre', '12341', 'standalone', '', '', 828, 78, 331, NULL, 0, 'public', 0, 18, NULL, 'Not Classified', '', 0),
								(350, 'Mbooni District Hospital.', '12508', 'standalone', '', '', 828, 78, 272, NULL, 0, 'public', 0, 18, NULL, 'Not Classified', '', 0),
								(351, 'Tawa Sub-Distrct Hospial', '12787', 'satellite', '', '', 828, 78, 546, NULL, 0, 'public', 0, 23, NULL, 'Not Classified', '', 0),
								(353, 'Sololo Mission Hospital', '12739', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 81, 186, 577, 0, 'mission', 1, 25, NULL, 'Level 4', 'Obbu', 24),
								(354, 'Ikanga Sub-District Hospital', '12077', 'satellite', '', '', 828, 82, 204, 102, 0, 'public', 0, 18, NULL, 'Not Classified', '', 0),
								(355, 'Ikutha Health Centre', '12080', 'satellite', '', '', 828, 82, 404, 102, 0, 'public', 0, 18, NULL, 'Not Classified', '', 0),
								(356, 'Mutumo District Hospital', '12603', 'satellite', '', '', 828, 82, 402, 102, 0, 'public', 0, 18, NULL, 'Not Classified', '', 0),
								(357, 'Katulani Health Centre', '12244', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 83, 282, 629, 0, 'public', 1, 22, NULL, 'Level 2', 'Katulani', 17),
								(358, 'Mbitini Catholic Dispensary', '12502', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 85, 154, 116, 0, 'mission', 1, 23, NULL, 'Not Classified', 'Mbitini', 24),
								(359, 'Mathuki Health Centre', '12483', 'satellite', '', '', 828, 84, 236, 155, 0, 'public', 0, 18, NULL, 'Not Classified', '', 0),
								(360, 'Migwani Sub-District Hospital', '12523', 'satellite', '', '', 828, 84, 310, 155, 0, 'public', 0, 18, NULL, 'Not Classified', '', 0),
								(361, 'Nuu Sub-District Hospital', '12681', 'satellite', '', '', 828, 84, 470, 155, 0, 'public', 0, 18, NULL, 'Not Classified', '', 0),
								(363, 'Matiliku District Hospital', '12485', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 85, 250, 116, 0, 'public', 1, 23, NULL, 'Level 4', 'Matiliku', 24),
								(364, 'Sultan Hamud Sub- District Hosp.', '12777', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 85, 365, NULL, 0, 'public', 0, 23, NULL, 'Level 4', 'Sultan Hamud', 24),
								(365, 'P.C.E.A Karungaru Dispensary', '17444', 'standalone', '', '', 889, 87, 583, NULL, 0, 'mission', 0, 26, NULL, 'Not Classified', '', 0),
								(366, 'Miathene District Hospital', '16234', 'satellite', '', '', 889, 88, 306, NULL, 0, 'public', 0, 26, NULL, 'Not Classified', '', 0),
								(367, 'Muthara Sub-District Hospital', '12591', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 216, 401, 170, 0, 'public', 1, 26, NULL, 'Level 3', 'Muthara', 42),
								(368, 'Ekalakala Health Centre', '11995', 'standalone', '', '', 828, 89, 361, NULL, 0, 'public', 0, 22, NULL, 'Not Classified', '', 0),
								(369, 'Katangi Health Centre', '12215', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 89, 275, NULL, 0, 'public', 0, 22, NULL, 'Not Classified', 'Kyua', 42),
								(370, 'Masinga Health Centre', '12476', 'standalone', '', '', 828, 89, 226, NULL, 0, 'public', 0, 18, NULL, 'Not Classified', '', 0),
								(371, 'Ndithini Dispensary', '12637', 'standalone', '', '', 828, 89, 441, NULL, 0, 'public', 0, 18, NULL, 'Not Classified', '', 0),
								(372, 'CRS, Makadara', '', 'central', '', '', NULL, 168, 933, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(374, 'Dandora (EDARP)', '12911', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 164, 136, 42, 0, 'mission', 1, 30, NULL, 'Level 2', 'Dandora', 14),
								(376, 'Kayole II Sub-District Hospital', '13016', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 168, NULL, 1, 'public', 0, 30, NULL, 'Level 4', 'Kayole', 42),
								(377, 'Lea Toto Program Kariobangi ', '13047', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 358, 396, 0, 'ngo', 1, 30, NULL, 'Level 1', 'Kariobangi South', 13),
								(378, 'Njiru (EDARP)', '17548', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 133, 42, 0, 'mission', 1, 30, NULL, 'Level 2', 'Njiru', 14),
								(379, 'Ruai (EDARP)', '13169', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 132, 42, 0, 'mission', 1, 30, NULL, 'Not Classified', 'Ruai', 14),
								(380, 'Soweto (EDARP)', '13191', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 137, 42, 0, 'mission', 1, 30, NULL, 'Level 1', 'Kayole', 14),
								(381, 'Huruma (EDARP)', '12973', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 165, 127, 42, 0, 'mission', 1, 30, NULL, 'Not Classified', 'Huruma', 14),
								(384, 'Shauri Moyo (EDARP) Clinic', '13184', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 165, 135, 42, 0, 'public', 1, 30, NULL, 'Not Classified', ' Kamukunji', 14),
								(385, 'St. Vincent Dispensary (EDARP)', '13230', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 169, 130, 42, 0, 'mission', 1, 30, NULL, 'Level 2', 'Eastleigh North', 14),
								(386, 'AMURT Health Centre', '12870', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 47, 216, NULL, 1, 'ngo', 1, 30, NULL, 'Level 2', 'Kangemi', 16),
								(387, 'Defence Forces Memorial Hospital, Nairobi', '13087', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 163, 221, NULL, 1, 'private', 0, 30, NULL, 'Level 4', 'Golfcourse', 42),
								(388, 'CRS, Nairobi', '', 'central', '', '', NULL, 47, 934, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(390, 'Nairobi West', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 47, 356, NULL, 0, 'ngo', 0, 30, NULL, 'Level 2', 'Kangemi', 13),
								(391, 'Lea Toto Program Dagoretti', '13046', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 163, 357, 396, 0, 'ngo', 1, 30, NULL, 'Level 2', 'Satelite', 13),
								(392, 'Lea Toto Program Kibera', '13048', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 167, 359, 396, 0, 'ngo', 1, 30, NULL, 'Level 2', 'Sarang''Ombe', 13),
								(393, 'Lea Toto Program Dandora', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 164, 794, 396, 0, 'ngo', 1, 30, NULL, 'Not Classified', 'Dandora', 13),
								(394, 'Lea Toto Program Kawangware', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 163, 677, 396, 0, 'ngo', 1, 30, NULL, 'Not Classified', 'Kawangware', 13),
								(395, 'Lea Toto Program Mukuru', '17720', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 795, 396, 0, '', 1, 30, NULL, 'Level 2', 'Mukuru', 13),
								(396, 'Nyumbani Children''s Home', '13131', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 167, 676, NULL, 1, 'ngo', 0, 30, NULL, 'Level 1', 'Karen', 13),
								(397, 'Ushirika Community Based Health Centre', '13245', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 167, 562, 13, 0, 'public', 1, 30, NULL, 'Level 1', 'Sarang''Ombe', 4),
								(398, 'Sotik Health Centre', '15619', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 137, 511, 487, 0, 'private', 1, 2, NULL, 'Level 2', 'Chemagel', 32),
								(399, 'Etago Sub-District Hospital', '13550', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 213, 379, NULL, 0, 'public', 0, 16, NULL, 'Level 1', 'Chitago', 42),
								(400, 'Nduru District Hospital', '13847', 'satellite', '', '', 895, 92, 446, 100, 0, 'public', 0, 16, NULL, 'Not Classified', '', 0),
								(401, 'Acorn Comm Hospital', '13670', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 208, 78, 405, 0, 'private', 1, 8, NULL, 'Level 1', 'West Kanyamwa', 27),
								(402, 'Gongo Dispensary', '13587', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 11, 424, 59, 0, 'public', 1, 8, NULL, 'Not Classified', 'Gongo', 42),
								(403, 'Got Kojowi Health Centre', '13589', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 208, 426, 405, 0, 'public', 1, 8, NULL, 'Level 1', 'West Kobwae', 24),
								(404, 'Marindi Health Centre', '13777', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 11, 220, 59, 0, 'public', 1, 8, NULL, 'Level 3', 'West-Kanyada', 24),
								(405, 'Ndhiwa District Hospital', '13841', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 208, 438, NULL, 1, 'public', 0, 8, NULL, 'Level 4', 'West Kanyamwa', 24),
								(406, 'Ndiru Health Centre', '13843', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 11, 440, 59, 0, 'public', 1, 8, NULL, 'Level 3', 'Kagan', 24),
								(407, 'Nyagoro Health Centre', '13875', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 11, 475, 59, 0, 'public', 1, 8, NULL, 'Level 3', 'East Kochia', 24),
								(408, 'Ober Dispensary', '13953', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 212, 489, NULL, 0, 'public', 1, 8, NULL, 'Not Classified', 'Kakelo', 24),
								(409, 'Rangwe Sub-District Hospital', '14036', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 11, 527, 59, 0, 'public', 1, 8, NULL, 'Level 4', 'West Gem', 24),
								(410, 'Christamarrianne Hospital', '13527', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 46, 325, 213, 0, 'mission', 1, 16, NULL, 'Level 1', 'Township', 2),
								(411, 'Ibeno Sub-District Hospital', '13612', 'standalone', '', '', 895, 46, 458, NULL, 0, 'public', 0, 16, NULL, 'Not Classified', '', 0),
								(412, 'Kiogoro Health Centre', '13696', 'standalone', '', '', 895, 46, 321, NULL, 0, 'public', 0, 16, NULL, 'Not Classified', '', 0),
								(413, 'Raganga Health Centre', '14025', 'standalone', '', '', 895, 46, 525, 100, 0, 'public', 0, 16, NULL, 'Not Classified', '', 0),
								(415, 'Riana Health Centre', '14045', 'satellite', '', '', NULL, 93, 530, 100, 0, 'public', 0, 16, NULL, 'Not Classified', '', 0),
								(416, 'Riotanchi Health Centre', '14054', 'satellite', '', '', 895, 93, 531, 100, 0, 'public', 0, 16, NULL, 'Not Classified', '', 0),
								(417, 'Hongo Ogosa Health Centre', '13609', 'satellite', 'PEPFAR', 'ART,PMTCT,LAB,RTK', 895, 2, 452, NULL, 0, 'public', 0, 17, NULL, 'Level 2', 'Katho', 20),
								(418, 'Nyang''ande Health Centre', '13923', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 2, 484, NULL, 0, 'public', 1, 17, NULL, 'Level 2', 'Kawino', 20),
								(419, 'Pandpieri Health Centre', '14012', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 2, 519, 46, 0, 'mission', 1, 17, NULL, 'Not Classified', 'West Kolwa', 20),
								(420, 'Rabuor Health Centre', '14020', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 2, 524, NULL, 0, 'public', 0, 17, NULL, 'Level 3', 'West Kochieng', 20),
								(421, 'Tuungane Youth Centre (Kisumu East)', '16663', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 189, 556, 46, 0, 'ngo', 1, 17, NULL, 'Not Classified', 'Township', 20),
								(422, 'Bodi Dispensary', '13503', 'satellite', 'PEPFAR', 'ART,PEP,LAB,RTK', 895, 29, 269, 424, 0, 'public', 1, 17, NULL, 'Level 3', 'South Central Seme', 32),
								(423, 'Chulaimbo Sub-District Hospital', '13528', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 29, 150, NULL, 1, 'public', 1, 17, NULL, 'Level 4', 'North West Kisumu', 3),
								(424, 'Kombewa District Hospital', '13714', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 29, 143, NULL, 1, 'public', 0, 17, NULL, 'Level 4', 'South Central Seme', 42),
								(425, 'Nduru Kadero Dispensary', '13848', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 29, 448, 424, 0, 'public', 1, 17, NULL, 'Level 1', 'North Central Seme', 24),
								(426, 'Ratta Health Centre', '14040', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 29, 528, 424, 0, 'public', 1, 17, NULL, 'Level 3', 'Otwenya', 32),
								(427, 'Rodi Dispensary   ', '14057', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 29, 532, 424, 0, 'public', 1, 17, NULL, 'Level 2', 'East Seme', 32),
								(428, 'Agenga Health Centre', '13467', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 211, 203, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'Central Kadem', 40),
								(429, 'Kadem TB health Centre', '13640', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 822, 211, 228, 198, 0, 'mission', 1, 27, NULL, 'Not Classified', 'East Kadem', 40),
								(430, 'Karungu Sub District Hospital', '13656', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 211, 270, 112, 0, 'public', 0, 27, NULL, 'Level 4', 'West Karungu', 40),
								(431, 'Muhuru Sub District Hospital', '13833', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 211, 370, 112, 0, 'public', 1, 27, NULL, 'Level 1', 'East Muhuru', 40),
								(432, 'Ndiwa Health Centre', '13844', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 211, 442, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'East Kadem', 40),
								(434, 'Ogwedhi Health centre', '13969', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', 895, 96, 490, NULL, 0, 'public', 0, 27, NULL, 'Not Classified', 'Upper Suna', 24),
								(435, 'Olasi Dispensary  ', '13975', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 211, 512, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'Kaler', 40),
								(437, 'Sori Lakeside Health Centre', '14098', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 211, 510, 112, 0, 'mission', 1, 27, NULL, 'Not Classified', 'West Karungu', 40),
								(438, 'Taara Dispensary', '', 'satellite', '', '', 895, 96, 1420, 141, 0, '', 0, 27, NULL, 'Not Classified', '', 0),
								(439, 'Waongel Health centre', '', 'satellite', '', '', 895, 96, 1421, 141, 0, '', 0, 27, NULL, 'Not Classified', '', 0),
								(440, 'Ekerenyo Sub-District Hospital', '13540', 'satellite', '', '', 895, 97, 364, 171, 0, 'public', 0, 34, NULL, 'Not Classified', '', 0),
								(441, 'Matongo Health Centre', '13791', 'standalone', '', '', 895, 97, 237, NULL, 0, 'mission', 0, 34, NULL, 'Not Classified', '', 0),
								(442, 'Nyamaiya Health Centre', '13894', 'satellite', '', '', 895, 97, 477, 171, 0, 'public', 0, 34, NULL, 'Not Classified', '', 0),
								(445, 'Tinga Health Centre', '14146', 'satellite', '', '', 895, 97, 551, 171, 0, 'public', 0, 34, NULL, 'Not Classified', '', 0),
								(446, 'Chuowe Dispensary', '13529', 'satellite', 'PEPFAR', 'PMTCT', 895, 35, 329, 183, 0, 'public', 1, 8, NULL, 'Not Classified', 'Wangchieng', 24),
								(447, 'Godber Dispensary', '13584', 'satellite', 'PEPFAR', 'PMTCT', 895, 212, 423, 183, 0, 'public', 1, 8, NULL, 'Not Classified', 'Kakello', 24),
								(448, 'Othoro Health Centre (Rachuonyo)', '14002', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 212, 493, 183, 0, 'public', 1, 8, NULL, 'Not Classified', 'Kawuor', 24),
								(450, 'Ong''ielo Health Centre', '13987', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 98, 514, NULL, 1, 'public', 0, 38, NULL, 'Level 3', 'East Asembo', 24),
								(451, 'Bware Dispensary', '13519', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 822, 53, 298, 189, 0, 'private', 1, 27, NULL, 'Level 2', 'South Kanyamkago', 41),
								(452, 'Lwala Community Dispensary', '13740', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 65, 374, 189, 0, 'public', 1, 27, NULL, 'Level 3', 'North Kamagambo', 41),
								(453, 'Minyenya Health Centre', '13809', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 65, 327, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'North Kamagambo', 41),
								(454, 'Ngodhe Dispensary', '13853', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 65, 465, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'East Kamagambo', 41),
								(455, 'Nyamasare Dispensary', '13900', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 53, 479, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'Kamgundho', 41),
								(456, 'Othoro Health Centre', '14003', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 822, 53, 582, 189, 0, 'public', 1, 27, NULL, 'Level 4', 'North Kanyamkago', 41),
								(457, 'Ongito Dispensary', '13988', 'satellite', 'PEPFAR', 'PMTCT', 822, 53, 515, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'North Kanyamkago', 41),
								(458, 'Osogo Dispensary', '13997 ', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 822, 53, 1032, 189, 0, 'public', 1, 27, NULL, 'Level 2', ' West Kanyamkago', 41),
								(459, 'Oyani Health Centre', '14009', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 822, 65, 518, NULL, 0, 'public', 0, 27, NULL, 'Level 3', 'Central  Alego', 41),
								(460, 'Sibuoche Dispensary', '14082', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 53, 540, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'West Kanyamkago', 41),
								(461, 'Bama Nursing Home', '13493', 'satellite', '', '', NULL, 9, 245, 193, 0, 'private', 0, 38, NULL, 'Not Classified', '', 0),
								(462, 'Bar Agulu Dispensary', '13496', 'satellite', '', '', NULL, 9, 249, 193, 0, 'public', 0, 38, NULL, 'Not Classified', '', 0),
								(463, 'Bar Olengo Dispensary', '13499', 'standalone', '', '', NULL, 9, 251, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', '', 0),
								(464, 'Bar Sauri Health Centre', '16785', 'satellite', '', '', NULL, 9, 253, 234, 0, 'public', 0, 38, NULL, 'Not Classified', '', 0),
								(467, 'Dolphil Nursing & Maternity Home', '13535', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 820, 9, 354, 234, 0, 'mission', 1, 38, NULL, 'Not Classified', 'East Gem', 12),
								(479, 'Rwambwa Health Centre', '14063', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 820, 9, 536, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'Usonga', 42),
								(482, 'Uriri Dispensary', '14160', 'standalone', 'PEPFAR', 'PMTCT', NULL, 9, 560, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'North East Gem', 42),
								(485, 'Marigat Sub District Hospital', '15138', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 171, 230, NULL, 1, 'public', 0, 1, NULL, 'Level 4', 'Marigat', 3),
								(486, 'Kapkoros Health Centre', '14728', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 102, 260, 487, 0, 'public', 1, 2, NULL, 'Level 1', 'Kapkoros', 32),
								(487, 'Longisa District Hospital', '15077', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 102, 139, NULL, 1, 'public', 0, 2, NULL, 'Level 4', 'Cheboin', 32),
								(489, 'Silibwet Dispensary (Bomet)', '15570', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 102, 543, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Silibwet', 32),
								(490, 'Siongiroi Health Centre', '15587', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 176, 508, 487, 0, 'public', 1, 2, NULL, 'Level 3', 'Siongiroi', 32),
								(491, 'Kapkatet District Hospital', '14706', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 174, 257, NULL, 1, 'public', 0, 12, NULL, 'Level 4', 'Kapkatet', 32),
								(492, 'Kipwastuiyo Health Centre', '14935', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 174, 323, 491, 0, 'public', 1, 2, NULL, 'Level 3', 'Techoget', 32),
								(493, 'Koiwo Health Centre', '14970', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 190, 471, 491, 0, 'public', 1, 2, NULL, 'Level 3', 'Koiwo', 32),
								(494, 'Mogogosiek Health Centre', '15195', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 190, 343, 487, 0, 'public', 1, 2, NULL, 'Level 3', 'Mogogosiek', 32),
								(495, 'Rorett Health Centre', '15498', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 174, 589, 491, 0, 'public', 1, 2, NULL, 'Level 4', 'Kisiara', 32),
								(497, 'Uasin Gishu District Hospital', '15758', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 103, 557, NULL, 1, 'public', 1, 44, NULL, 'Level 2', 'Chepkoilel', 3),
								(498, 'Turbo Health Centre', '15753', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 104, 555, NULL, 1, 'public', 1, 44, NULL, 'Level 2', 'Kaptebee', 3),
								(499, 'Iten District Hospital', '14586', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 107, 207, NULL, 1, 'public', 0, 5, NULL, 'Level 4', 'Irong', 3),
								(500, 'Ainamoi Sub District Hospital', '14192', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 813, 108, 209, 504, 0, 'public', 1, 12, NULL, 'Level 3', 'Ainamoi', 32),
								(501, 'James Finlay Kenya', '14497', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 172, 586, NULL, 0, 'private', 1, 12, NULL, 'Level 1', 'Chaik', 32),
								(502, 'Jamji dispensary', '14592', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 172, 217, NULL, 0, 'private', 1, 12, NULL, 'Level 1', 'Chaik', 32),
								(503, 'Kerenga Dispensary', '14830', 'satellite', 'PEPFAR', 'PMTCT', 952, 172, 295, 506, 0, 'private', 1, 12, NULL, 'Level 1', 'Chaik', 32),
								(504, 'Kericho District Hospital', '14831', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 108, 117, NULL, 1, 'public', 0, 12, NULL, 'Level 4', 'Township', 32),
								(505, 'Sosiot Health Centre', '15617', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 172, 499, 504, 0, 'public', 1, 12, NULL, 'Level 3', 'Waldai', 32),
								(506, 'Unilever Tea central Hospital.', '15761', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 108, 559, 231, 0, 'private', 1, 12, NULL, 'Level 2', 'Ainamoi', 32),
								(507, 'Sigowet Sub-District Hospital', '15568', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 172, 542, 504, 0, 'public', 1, 12, NULL, 'Not Classified', 'Kebeneti', 32),
								(508, 'Forttenan Sub District Hospital', '14501', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 110, 388, 510, 0, 'public', 1, 12, NULL, 'Level 1', 'Chilchila', 32),
								(509, 'Kipkelion Sub District Hospital', '14897', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 110, 322, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Kipchorian', 32),
								(510, 'Londiani District Hospital', '15074', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 110, 140, NULL, 1, 'public', 0, 12, NULL, 'Level 4', 'Londiani', 32),
								(511, 'Kalalu dispensary', '14659', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 111, 240, 161, 0, 'public', 1, 20, NULL, 'Level 2', 'Daiga', 7),
								(513, 'Ngobit Dispensary', '15349', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 111, 460, 161, 0, 'public', 1, 20, NULL, 'Level 2', 'Ngobit', 7),
								(514, 'Doldol Health Centre', '14404', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 112, 352, NULL, 1, 'public', 0, 20, NULL, 'Level 3', 'Kurikuri', 24),
								(515, 'Kimanjo Dispensary', '14869', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 112, 318, 514, 0, 'public', 1, 20, NULL, 'Level 2', 'Tura', 7),
								(516, 'Ndindika Health Centre', '15325', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 52, 439, NULL, 1, 'public', 0, 20, NULL, 'Level 2', 'Kinamba', 7),
								(517, 'Rumuruti District Hospital', '15502', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 52, 535, NULL, 1, 'public', 0, 20, NULL, 'Level 4', 'Rumuruti', 7),
								(518, 'Live with Hope', '17589', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 172, 362, 504, 0, 'mission', 1, 12, NULL, 'Level 2', 'Township', 32),
								(519, 'Mutarakwa Dispensary (Molo)', '15262', 'satellite', 'PEPFAR', 'ART,PEP', 1728, 114, 397, NULL, 0, 'public', 1, 31, NULL, 'Level 1', 'Kihingo', 3),
								(520, 'Gilgil Sub-District Hospital', '14510', 'central', '', '', 1728, 51, 418, NULL, 0, 'public', 0, 31, NULL, 'Not Classified', '', 0),
								(525, 'Kapkangani Health Centre', '14704', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 41, 256, 78, 0, 'public', 1, 32, NULL, 'Level 3', 'Kapkangani', 32),
								(526, 'Kilibwoni Dispensary', '14866', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 41, 315, 78, 0, 'public', 1, 32, NULL, 'Level 3', 'Kilibwoni', 32),
								(527, 'Nandi Hills District Hospital', '14179', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 115, 431, NULL, 1, 'public', 0, 32, NULL, 'Level 4', 'Kaplelmet', 32),
								(528, 'Chepterwai Sub-District Hospital', '14369', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 116, 319, 78, 0, 'public', 1, 32, NULL, 'Level 4', 'Chepterwai', 32),
								(529, 'Mosoriot RHDC', '15229', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 116, 344, NULL, 1, 'public', 0, 32, NULL, 'Level 3', 'Mutwot', 3),
								(530, 'Chemase Health Centre', '14315', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 117, 305, 527, 0, 'public', 1, 32, NULL, 'Level 3', 'Chemase', 32),
								(531, 'Kaptumo Sub-District Hospital', '14792', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 117, 265, 527, 0, 'public', 1, 32, NULL, 'Level 4', 'Kaptumo', 32),
								(532, 'Kemeloi Health Centre', '14825', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 117, 288, 527, 0, 'public', 1, 32, NULL, 'Level 3', 'Kemeloi', 32),
								(533, 'Kabichbich Health Centre', '14615', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 118, 224, 76, 0, 'public', 1, 47, NULL, 'Level 3', 'Lelan', 7),
								(534, 'Sigor Sub District Hospital,Bomet', '15565', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 102, 541, 487, 0, 'public', 1, 2, NULL, 'Level 3', 'Sigor', 24),
								(535, 'Meteitei Sub-District Hospital', '15181', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 119, 287, 527, 0, 'public', 1, 32, NULL, 'Level 3', 'Kabolebo', 32),
								(536, 'Enoosaen Health Centre', '14465', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 284, 377, 825, 0, 'public', 1, 33, NULL, 'Level 3', 'Enoosaen', 32),
								(538, 'Kurangurik Dispensary', '15002', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 217, 353, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Murgan', 24),
								(539, 'Lolgorian Sub District Hospital', '15068', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 217, 366, 825, 0, 'public', 1, 33, NULL, 'Level 4', 'Lolgorian', 32),
								(540, 'Kitale District Hospital', '14947', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 121, 336, NULL, 1, 'public', 0, 42, NULL, 'Level 4', 'Kibomet', 3),
								(541, 'Huruma Sub District Hospital, Eldoret', '14555', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 104, 456, 144, 0, 'public', 1, 44, NULL, 'Level 3', 'Kapyemit', 3),
								(542, 'Kesses Health Centre', '14841', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 123, 301, 27, 0, 'public', 1, 44, NULL, 'Level 2', 'Kesses', 3),
								(543, 'St Ladislaus Dispensary', '16345', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 123, 121, NULL, 1, 'mission', 1, 44, NULL, 'Level 2', 'Langas', 30),
								(544, 'Chepareria Sub-District Hospital', '14330', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 40, 312, 76, 0, 'public', 1, 47, NULL, 'Level 3', 'Cheparera', 24),
								(545, 'Khunyangu RHDC', '15939', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 143, 42, NULL, 1, 'public', 1, 4, NULL, 'Level 2', 'Marachi Central', 3),
								(546, 'Angata Health Centre', '14205', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 217, 30, 825, 0, 'public', 1, 33, NULL, 'Level 1', 'Angata', 32),
								(547, 'Webuye District Hospital', '16161', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 30, 571, NULL, 1, 'public', 0, 3, NULL, 'Level 2', 'Webuye', 3),
								(548, 'Naitiri Health Centre', '16061', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 138, 408, NULL, 1, 'public', 0, 3, NULL, 'Level 3', 'Mbakalo', 3),
								(549, 'Port Victoria Hospital', '16091', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 125, 492, NULL, 1, 'public', 0, 4, NULL, 'Level 3', 'Bunyala West', 3),
								(551, 'Bukura Health Centre', '15820', 'standalone', '', '', 874, 127, 286, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', '', 0),
								(552, 'Mt  Elgon District Hospital', '16025', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 132, 345, NULL, 1, 'public', 1, 3, NULL, 'Level 4', 'Kapsokwony', 3),
								(553, 'Amukura Health Centre', '15798', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 214, 215, NULL, 1, 'public', 0, 4, NULL, 'Level 3', 'Amukura', 3),
								(554, 'Teso District Hospital', '16150', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 214, 547, NULL, 1, 'public', 1, 4, NULL, 'Level 4', 'Kocholia', 3),
								(555, 'Ahero Sub-District Hospital', '13468', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 22, 70, NULL, 0, 'public', 0, 17, NULL, 'Not Classified', 'Ahero', 24),
								(556, 'AIDS Relief', '', 'central', '', '', 998, 47, 72, NULL, 0, 'mission', 0, 30, NULL, 'Not Classified', '', 0),
								(557, 'Garissa Provincial General Hospital', '13346', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 145, 85, NULL, 1, 'public', 0, 7, NULL, 'Level 5', 'Waberi', 42),
								(558, 'Wajir District Hospital', '13452', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 220, 86, NULL, 1, 'public', 1, 46, NULL, 'Level 4', 'Central', 24),
								(559, 'Kenya Medical Supplies Agency', '', 'central', '', '', NULL, 165, 87, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(560, 'AHF Mathare Clinic', '12885', 'standalone', 'AHF', 'ART,PMTCT,PEP,LAB,RTK', 998, 169, 90, NULL, 1, 'ngo', 1, 30, NULL, 'Not Classified', 'Mathare', 25),
								(561, 'Kakuma Mission Hospital', '14655', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 7, 98, NULL, 1, 'mission', 1, 43, NULL, 'Not Classified', 'Kakuma', 9),
								(562, 'Wamba Mission Hospital', '15769', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 147, 101, NULL, 1, 'mission', 0, 37, NULL, 'Level 4', 'Wamba', 19),
								(563, 'Lodwar District Hospital', '15049', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 38, 103, NULL, 1, 'public', 0, 43, NULL, 'Level 4', 'Lodwar Town', 42),
								(564, 'Gesusu Sub-district Hospital', '13564', 'central', '', '', NULL, 141, 104, NULL, 0, 'public', 0, 16, NULL, 'Not Classified', '', 0),
								(565, 'Marsabit District Hospital', '12472', 'central', 'GOK/NASCOP, PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 148, 106, NULL, 1, 'public', 0, 25, NULL, 'Level 4', 'Mountain', 42),
								(566, 'Kinango District Hospital', '11480', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 149, 108, NULL, 1, 'public', 0, 19, NULL, 'Level 4', 'Kinango', 42),
								(567, 'Sena Health Centre', '14075', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 99, 109, NULL, 1, 'public', 1, 8, NULL, 'Level 1', 'Mfangano East', 42),
								(568, 'Mandera District Hospital', '13402', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 150, 110, NULL, 1, 'public', 0, 24, NULL, 'Level 4', 'Bulla Jamhuri', 24),
								(569, 'Ijara District Hospital - Masalani', '13406', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 151, 111, NULL, 1, 'public', 0, 7, NULL, 'Level 1', 'Gumarey', 24),
								(570, 'QA Manager Chemonics', '', 'standalone', '', '', NULL, 47, 113, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(571, 'Uhuru Camp', '13239', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 167, 114, NULL, 0, 'private', 0, 30, NULL, 'Not Classified', 'Langata', 24),
								(572, 'Kodiaga Dispensary', '13709', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 94, 115, NULL, 0, 'public', 0, 17, NULL, 'Not Classified', '', 24),
								(573, 'Mpeketoni Sub-District Hospital', '11649', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 194, 118, NULL, 1, 'public', 0, 21, NULL, 'Level 1', 'Central', 24),
								(574, 'Lamu District Hospital', '11512', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 194, 119, NULL, 1, 'public', 0, 21, NULL, 'Level 4', 'Langoni', 42),
								(575, 'Hola District Hospital', '11411', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 178, 120, NULL, 1, 'public', 0, 40, NULL, 'Level 4', 'Zubaki', 24),
								(576, 'Maralal District Hospital', '15126', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 154, 123, NULL, 1, 'public', 0, 37, NULL, 'Level 1', 'Maralal', 42),
								(577, 'Moyale District Hospital', '12544', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 81, 125, NULL, 1, 'public', 0, 25, NULL, 'Level 4', 'Central', 42),
								(578, 'Komarock EDARP', '17719', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 126, 42, 0, 'mission', 1, 30, NULL, 'Level 2', 'Komarock', 14),
								(579, 'Kariobangi (EDARP)', '18743', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 166, 128, 42, 0, 'mission', 1, 30, NULL, 'Not Classified', 'Kariobangi', 14),
								(580, 'Mathare 3A (EDARP)', '13075', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 165, 131, 42, 0, 'mission', 1, 30, NULL, 'Not Classified', 'Mathare', 14),
								(581, 'Donholm (EDARP)', '13220', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 134, 42, 0, 'mission', 1, 30, NULL, 'Level 2', 'Savannah', 14),
								(582, 'Kibera DO Health Center', '13029', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', 998, 167, 141, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', '', 24),
								(583, 'MSF FRANCE REF:Homa Bay DH', '', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 47, 142, NULL, 0, 'ngo', 0, 30, NULL, 'Not Classified', '', 25),
								(584, 'UON/UOM Pumwani VCT Centre', '13157', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 165, 147, NULL, 1, 'public', 0, 30, NULL, 'Not Classified', 'Pumwani', 31),
								(585, 'Nyatoto Health Centre', '13946', 'standalone', 'Ministry of Health', 'ART', 895, 99, 153, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', '', 0),
								(586, 'Kithituni Health Care Clinic', '17549', 'central', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', 828, 85, 155, NULL, 0, 'mission', 0, 23, NULL, 'Not Classified', 'Kasikeu', 17),
								(587, 'Ngwata Health Centre', '12663', 'satellite', 'Ministry of Health, PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 72, 156, 330, 0, 'public', 1, 23, NULL, 'Not Classified', 'Ngwata', 42),
								(588, 'MSF Belgium', '', 'central', '', '', 998, 167, 160, NULL, 0, 'ngo', 0, 30, NULL, 'Not Classified', '', 47),
								(589, 'Simaho Maternity Home', '13442', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 145, 161, 557, 0, 'mission', 1, 7, NULL, 'Not Classified', 'Township', 25),
								(590, 'Police Line Dispensary (Garissa)', '13420', 'satellite', 'Ministry of Health, PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 145, 162, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Central', 24),
								(591, 'Embakasi Health Centre', '12935', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 169, 376, 0, 'public', 1, 30, NULL, 'Not Classified', 'Embakasi', 21),
								(592, 'Umoja Health Centre', '13240', 'satellite', 'PEPFAR', 'PMTCT', 998, 170, 170, 376, 0, 'public', 1, 30, NULL, 'Not Classified', 'Umoja', 21),
								(593, 'Mukuru MMM Clinic', '13101', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 171, 376, 0, 'mission', 1, 30, NULL, 'Not Classified', 'Mukuru', 19),
								(594, 'Coni Health Centre', '12958', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 172, 376, 0, 'public', 1, 30, NULL, 'Not Classified', 'kayole', 27),
								(595, 'Tei Wa Yesu Health Centre', '12789', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 74, 174, 338, 0, 'mission', 1, 18, NULL, 'Not Classified', 'Kyuso', 17),
								(596, 'Katse Health Centre', '12242', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 74, 175, 338, 0, 'public', 1, 18, NULL, 'Level 3', 'Katse', 17),
								(597, 'Ngomeni Health Centre', '12654', 'satellite', 'PEPFAR', 'ART,PEP', 828, 74, 177, 338, 0, 'public', 1, 18, 370, 'Ngomeni', 'Ngomeni', 17),
								(598, 'Kiritiri Health Centre', '11964', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 44, 181, NULL, 1, 'public', 0, 6, NULL, 'Level 2', 'Gachoka', 42),
								(599, 'Kiamuringa Dispensary', '12274', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 44, 182, 598, 0, 'public', 1, 6, NULL, 'Level 2', 'Mbeti South', 42),
								(600, 'Kiambere Dam Dispensary', '12271', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 44, 183, 137, 0, 'public', 1, 6, NULL, 'Level 2', 'Mutitu', 42),
								(601, 'Ifo Hospital ', '13368', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 289, 187, 557, 0, 'ngo', 1, 7, NULL, 'Level 4', 'Dadaab', 24),
								(602, 'Dadaab Sub-District Hospital', '13316', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 192, 188, 557, 0, 'public', 1, 7, NULL, 'Level 4', 'Dadaab', 24),
								(603, 'Hagadera Hospital', '13359', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 156, 189, 557, 0, 'ngo', 1, 7, NULL, 'Level 4', 'Jarajilla', 24),
								(604, 'Iftin Sub-District Hospital', '13369', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 145, 190, 557, 0, 'public', 1, 7, NULL, 'Level 4', 'Iftin', 24),
								(605, 'Kabartonjo District Hospital', '14609', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 101, 192, NULL, 1, 'public', 0, 1, NULL, 'Level 1', 'Kabartonjo', 24),
								(606, 'Habaswein District Hospital', '13357', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 279, 193, 558, 0, 'public', 1, 46, NULL, 'Not Classified', 'Habaswein', 24),
								(607, 'Modogashe District Hospital', '13411', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 192, 194, 557, 0, 'public', 1, 7, NULL, 'Level 4', 'Modogashe', 24),
								(608, 'Dagahaley Hospital', '13318', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 289, 197, 557, 0, 'ngo', 1, 7, NULL, 'Level 4', 'Dadaab', 24),
								(609, 'Rhamu Sub-District Hospital', '13423', 'satellite', 'Ministry of Health', 'ART,PMTCT,PEP,LAB,RTK', 828, 199, 591, 568, 0, 'public', 1, 24, NULL, 'Level 4', 'Rhamu Town', 42),
								(610, 'Kerio Dispensary', '14838', 'standalone', '', '', NULL, 38, 592, NULL, 0, 'public', 0, 44, NULL, 'Not Classified', '', 0),
								(611, 'St Patrick''s Kanamkemer Dispensary', '15662', 'satellite', 'PEPFAR', 'ART,PEP', 1701, 38, 593, 561, 0, 'mission', 1, 43, NULL, 'Level 2', 'Kanamkemer', 4),
								(612, 'Kainuk Dispensary', '14645', 'satellite', 'PEPFAR', 'PMTCT', 1701, 218, 594, 563, 0, 'public', 1, 43, NULL, 'Not Classified', 'Kainuk', 24),
								(613, 'Namoruputh (PAG) Dispensary', '15299', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 38, 595, NULL, 0, 'mission', 1, 43, NULL, 'Not Classified', 'Loima', 12),
								(614, 'Lokichar RCEA', '15057', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 218, 596, 563, 0, 'mission', 1, 43, NULL, 'Level 3', 'Lokichar', 28),
								(615, 'Nakwamoru', '15292', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 218, 597, 563, 0, 'mission', 1, 43, NULL, 'Not Classified', 'Kaputir', 19),
								(616, 'Lokitaung SDH ', '15062', 'satellite', 'PEPFAR', 'PMTCT,LAB', 952, 162, 598, 563, 0, 'public', 1, 43, NULL, 'Not Classified', 'Ngissiger', 24),
								(617, 'Lokori Primary Health Care Programme', '16324', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 218, 599, 563, 0, 'mission', 1, 43, NULL, 'Level 3', 'Lokori', 12),
								(618, 'Lowarengak', '15096', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 162, 600, 840, 0, 'mission', 1, 43, NULL, 'Not Classified', 'Ngissiger', 19),
								(619, 'Lafey Sub-District Hospital', '13392', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 150, 601, 568, 0, 'public', 1, 24, NULL, 'Level 4', 'Lafey', 24),
								(620, 'Sio Port District Hospital', '16128', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 133, 602, NULL, 1, 'public', 0, 4, NULL, 'Level 4', 'Nanguba', 3),
								(621, 'Meridian Equator Hospital', '13109', 'standalone', '', '', NULL, 167, 603, NULL, 0, 'private', 0, NULL, NULL, 'Not Classified', NULL, NULL),
								(622, 'Chekalini Dispensary', '15851', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 45, 604, 109, 0, 'public', 1, 11, NULL, 'Level 2', 'Chekalini', 24),
								(623, 'Lari Health Centre', '10655', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 195, 605, NULL, 0, 'public', 1, 13, 1257, 'Level 3', 'Kirenga', 2),
								(624, 'Bamboo Health Centre', '10513', 'satellite', 'PEPFAR', 'PMTCT', 1728, 20, 606, 45, 0, 'public', 1, 31, NULL, 'Level 2', 'Magumu', 33),
								(625, 'Manunga Dispensary', '10681', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 20, 607, 45, 0, 'public', 1, 31, 1120, 'Level 2', 'Miharati', 33),
								(626, 'Mautuma Sub District Hospital', '16010', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 45, 608, 109, 0, 'public', 1, 11, NULL, 'Level 4', 'Mautuma', 3),
								(627, 'Gichira Health Centre', '10251', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 42, 609, 174, 0, 'public', 1, 36, 1257, 'Level 1', 'Aguthi', 43),
								(628, 'Karaba Dispensary (Nyeri South)', '10476', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 42, 610, 148, 0, 'public', 1, 36, 1257, 'Level 1', 'Thanu', 43),
								(629, 'Masii Health Centre ', '12475', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 83, 611, NULL, 0, 'public', 0, 22, NULL, 'Level 3', 'Masii', 17),
								(630, 'Thangathi Dispensary', '11090', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 42, 612, 148, 0, 'public', 1, 36, 1257, 'Level 3', 'Githii', 43),
								(631, 'Mokowe Health Centre', '11642', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 194, 613, 574, 0, 'public', 1, 21, NULL, 'Level 3', 'Mokowe', 24),
								(632, 'Witu Health Centre', '11907', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 194, 614, 574, 0, 'public', 1, 21, NULL, 'Level 3', 'Witu', 24),
								(633, 'Hindi Magogoni Dispensary', '11409', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 194, 615, 574, 0, 'public', 1, 21, 1258, 'Level 2', 'Hindi', 24),
								(634, 'Kiunga Health Centre', '11492', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 193, 616, 574, 0, 'public', 1, 21, NULL, 'Level 3', 'Kiunga', 24),
								(635, 'Lutsangani Dispensary', '11527', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 149, 617, 566, 0, 'public', 1, 19, NULL, 'Level 2', 'Gandini', 24),
								(636, 'Samburu Health Centre ', '11768', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 149, 618, NULL, 1, 'public', 0, 19, NULL, 'Level 3', 'Samburu', 24),
								(637, 'Taru Dispensary', '11838', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 149, 619, 636, 0, 'public', 1, 19, NULL, 'Level 2', 'Taru', 24),
								(638, 'Donyo Sabuk Dispensary', '16432', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 39, 620, 75, 0, 'public', 1, 22, NULL, 'Level 2', 'Matungulu', 24),
								(639, 'Kayatta Dispensary', '12267', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 39, 621, NULL, 0, 'public', 0, 22, NULL, 'Level 2', 'Kyanzavi', 24),
								(640, 'Kamuwongo Dispensary', '12169', 'satellite', 'PEPFAR', 'ART,PEP', 828, 74, 622, 338, 0, 'public', 1, 18, NULL, 'Level 2', 'Kamuwongo', 17),
								(641, 'Wamunyu  Health Centre ', '12841', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 83, 623, 153, 0, 'public', 1, 22, NULL, 'Level 3', 'Wamunyu', 17),
								(642, 'Muthetheni Health Centre', '12593', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 83, 624, 629, 0, 'public', 1, 22, NULL, 'Level 3', 'Muthetheni', 17),
								(643, 'Loiyangalani Health Centre', '12433', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 200, 625, 565, 0, 'public', 1, 25, NULL, 'Not Classified', 'Loiyangalani', 24),
								(644, 'Maikona Dispensary', '12446', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 209, 626, 565, 0, 'mission', 1, 25, NULL, 'Level 2', 'Maikona', 24),
								(645, 'North Horr Health Centre', '12668', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 209, 627, 565, 0, 'mission', 1, 25, 1256, 'Level 3', 'North Horr', 24),
								(646, 'Gatab Health Centre', '12030', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 200, 628, 565, 0, 'mission', 1, 25, 1256, 'Level 3', 'Mt Kulal', 24),
								(647, 'Ruai Health Centre', '', 'satellite', 'PEPFAR', '', NULL, 170, 629, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Ruai', 0),
								(649, 'Ijara District Hospital - Masalani Dispensing Point', '13406', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 151, 631, 569, 0, 'public', 1, 7, NULL, 'Level 4', 'Gumarey', 24),
								(651, 'Kijabe (AIC) Hospital Dispensing Point', '10602', 'satellite', 'AIDSRELIEF', 'ART,PMTCT,PEP,LAB,RTK', 998, 195, 633, 6, 0, 'mission', 1, 13, NULL, 'Level 4', 'Kijabe', 2),
								(652, 'Hara Dispensary', '13361', 'satellite', 'PEPFAR', 'PMTCT', 828, 151, 634, 569, 0, 'public', 1, 7, NULL, 'Level 3', 'Hara', 24),
								(653, 'Bodhai Dispensary', '13308', 'satellite', 'PEPFAR', 'PMTCT', 828, 151, 635, 569, 0, 'public', 1, 7, NULL, 'Level 2', 'Bodhai', 24),
								(654, 'Sangole Dispensary', '13432', 'satellite', 'PEPFAR', 'PMTCT', 828, 151, 636, 569, 0, 'public', 1, 7, NULL, 'Level 2', 'Sangole', 24),
								(655, 'Handaro Dispensary', '13360', 'satellite', 'PEPFAR', 'PMTCT', 828, 151, 637, 569, 0, 'public', 1, 7, NULL, 'Level 2', 'Handaro', 24),
								(656, 'Korisa Dispensary', '13383', 'satellite', 'PEPFAR', 'PMTCT', 889, 151, 638, 569, 0, 'public', 1, 7, NULL, 'Level 2', 'Korisa', 24),
								(657, 'Sangailu Health Centre', '13431', 'satellite', 'PEPFAR', 'PMTCT', 889, 151, 639, 569, 0, 'public', 1, 7, NULL, 'Level 3', 'Sangailu', 24),
								(658, 'Engineer District Hospital Dispensing Point', '10171', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 60, 640, 45, 0, 'public', 1, 35, 1120, 'Level 4', 'Kitiri', 17),
								(659, 'Nyeri Provincial General Hospital (PGH) dispensing point', '10903', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 25, 641, 174, 0, 'public', 1, 36, 1257, 'Level 1', 'Mukaro', 42),
								(660, 'Karatina District Hospital Dispensing Point', '10485', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 202, 642, 79, 0, 'public', 1, 36, 1257, 'Level 1', 'Konyu', 43),
								(661, 'Muranga District Hospital Dispensing Point', '10777', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 184, 643, 149, 0, 'public', 1, 29, NULL, 'Level 1', 'Township', 42),
								(662, 'Nyakianga Health Center', '10893', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 201, 644, 251, 0, 'public', 1, 29, NULL, 'Level 3', 'Njumbi', 42),
								(663, 'Kiria Health Center', '10624', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 184, 645, 150, 0, 'public', 1, 29, NULL, 'Level 3', 'Mugoiri', 42),
								(664, 'Mugeka Health Center', '10744', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 184, 646, 149, 0, 'public', 1, 29, NULL, 'Level 2', 'Gaturi', 42),
								(665, 'Kihoya Dispensary', '10594', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 184, 647, 251, 0, 'public', 1, 29, NULL, 'Level 2', 'Rwathia', 42),
								(666, 'Mombasa CBHC Dispensing Point', '11614', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 175, 648, 142, 0, 'mission', 1, 28, NULL, 'Level 3', 'Ganjoni', 2),
								(667, 'Hulugho Sub-District Hospital', '13365', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 151, 649, 569, 0, 'public', 1, 7, NULL, 'Level 4', 'Hadi', 24),
								(668, 'Mbungoni Catholic', '18043', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 188, 650, 142, 0, 'mission', 1, 28, NULL, 'Not Classified', 'Bamburi', 2),
								(669, 'Kotile Health Centre', '13385', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 151, 651, 569, 0, 'public', 1, 7, NULL, 'Level 3', 'Kotile', 24),
								(670, 'Mandera District Hospital Dispensing Point', '13402', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 150, 652, 568, 0, 'public', 1, 24, NULL, 'Level 4', 'Bulla Jamhuri', 24),
								(672, 'Mariakani District Hospital Dispensing Point', '11566', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 50, 654, 125, 0, 'public', 1, 14, NULL, 'Level 1', 'Mariakani', 24),
								(673, 'Wesu District Hospital Dispensing Point', '11906', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 221, 655, 233, 0, 'public', 1, 39, NULL, 'Level 1', 'Wundanyi', 24),
								(675, 'Lamu District Hospital Dispensing Point', '11512', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 194, 657, 574, 0, 'public', 1, 21, NULL, 'Level 4', 'Langoni', 24),
								(676, 'Faza Health Centre', '11373', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 193, 658, 574, 0, 'public', 1, 21, 1258, 'Level 4', 'Faza', 24),
								(677, 'Kinango District Hospital Dispensing Point', '11480', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 149, 659, 566, 0, 'public', 1, 19, NULL, 'Level 4', 'Kinango', 24),
								(678, 'Makindu District Hospital Dispensing Point', '12455', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 77, 660, 116, 0, 'public', 1, 23, NULL, 'Level 4', 'Makindu', 24),
								(679, 'Kangundo District Hospital Dispensing Point', '12177', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 39, 661, 75, 0, 'public', 1, 22, NULL, 'Level 4', 'Kangundo', 17),
								(680, 'Kombewa District Hospital Dispensing Point', '13714', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 29, 662, 424, 0, 'public', 1, 17, NULL, 'Level 4', 'South Central Seme', 42),
								(681, 'Mbeere District Hospital Dispensing point', '16467', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 44, 663, 137, 0, 'public', 1, 6, NULL, 'Level 2', 'Nthawa', 24),
								(682, 'Kyuso District Hospital Dispensing Point', '12420', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 74, 664, 338, 0, 'public', 1, 18, NULL, 'Level 4', 'Kyuso', 17),
								(683, 'Tharaka Health Center', '12794', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB', 828, 74, 665, 338, 0, 'public', 1, 18, NULL, 'Level 3', 'Tharaka', 17),
								(684, 'Mwala District Hospital dispensing point', '12618', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 83, 666, 153, 0, 'public', 1, 22, 370, 'Level 4', 'Mwala', 17),
								(685, 'Moyale District Hospital Dispensing Point', '12544', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 81, 667, 577, 0, 'public', 1, 25, NULL, 'Level 1', 'Central', 24),
								(686, 'Rongo District Hospital Dispensing Point', '14058', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 65, 668, 189, 0, 'public', 1, 27, NULL, 'Level 4', 'Central Kamagambo', 41),
								(687, 'Marsabit District Hospital Dispensing Point', '12472', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 148, 669, 565, 0, 'public', 1, 25, NULL, 'Level 1', 'Mountain', 24),
								(688, ' AMREF Kibera Health Centre Dispensing Point', '13028', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 167, 670, 13, 0, 'ngo', 1, 30, NULL, 'Level 3', 'Laini Saba', 4),
								(689, 'Madiany District Hospital Dispensing Point', '13747', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 98, 671, 114, 0, 'public', 1, 38, NULL, 'Level 1', 'East Uyoma', 42),
								(690, 'Manyuanda Health Centre (Rarieda)', '13771', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 98, 673, 114, 0, 'public', 1, 38, NULL, 'Level 3', 'West Uyoma', 24),
								(691, 'Nyumbani Children''s Home dispensing point', '13131', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 167, 674, 396, 0, 'ngo', 1, 30, NULL, 'Level 2', 'Karen', 13),
								(694, 'Garissa Provincial General Hospital (PGH) Dispensing Point', '13346', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 145, 679, 557, 0, 'public', 1, 7, NULL, 'Level 5', 'Waberi', 42),
								(695, 'Defence Forces Memorial Hospital, Nairobi Dispensing Point', '13087', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 163, 680, 387, 0, 'private', 1, 30, NULL, 'Level 4', 'Golfcourse', 42),
								(697, 'Homa-Bay District Hospital Dispensing Point', '13608', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 11, 682, 59, 0, 'public', 1, 8, NULL, 'Level 1', 'Homa-Bay', 24),
								(698, 'Magina Health Centre', '13751', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 208, 683, 405, 0, 'public', 1, 8, NULL, 'Level 1', 'Central Kabuoch', 24),
								(699, 'Pala Health Centre', '14011', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 208, 684, 405, 0, 'public', 1, 8, NULL, 'Level 1', 'South Kabuoch', 24),
								(700, 'Lagos Road Dispensary (Staff Clinic)', '13039', 'satellite', 'PEPFAR', 'PMTCT', 998, 169, 685, 211, 0, 'public', 1, 30, NULL, 'Not Classified', 'Central Business District', 21),
								(701, 'St Paul''s Health Centre', '14124', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 11, 686, 14, 0, 'mission', 1, 8, NULL, 'Not Classified', 'Homa-Bay Town', 19),
								(702, 'Rachuonyo District Hospital dispensing point', '14022', 'satellite', '14022', 'ART,PMTCT,PEP,LAB,RTK', 895, 35, 687, 183, 0, 'public', 1, 8, NULL, 'Level 1', 'Kowidi', 8),
								(703, 'Matata Nursing Hospital', '13789', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 212, 688, 183, 0, 'mission', 1, 8, NULL, 'Not Classified', 'West Kamagak', 48),
								(704, 'Miriu Health Centre', '13812', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 35, 689, NULL, 0, 'public', 0, 8, NULL, 'Level 1', 'Wangchieng', 24),
								(705, 'Macalder Sub-District Hospital Dispensing Point', '13745', 'satellite', 'Ministry of Health, PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 211, 690, 112, 0, 'public', 1, 27, NULL, 'Level 1', 'South East Kadem', 40);
								INSERT INTO `sync_facility` (`id`, `name`, `code`, `category`, `sponsors`, `services`, `manager_id`, `district_id`, `address_id`, `parent_id`, `ordering`, `affiliation`, `service_point`, `county_id`, `hcsm_id`, `keph_level`, `location`, `affiliate_organization_id`) VALUES
								(707, 'Nyarongi Dispensary ', '13940', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 822, 96, 692, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'North Suna', 40),
								(708, 'Lwanda Dispensary  ', '13741', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 211, 693, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'N.E.Kadem', 40),
								(709, 'Otati Dispensary ', '13999', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 211, 694, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'Central Karungu', 40),
								(710, 'Wath Onger Dispensary ', '14170', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 211, 695, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'South Kadem', 40),
								(711, 'Othoch Rakuom Dispensary ', '14001', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 211, 696, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'West Kadem', 40),
								(712, 'Sena Health Centre Dispensing Point', '14075', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 99, 697, 567, 0, 'public', 1, 8, NULL, 'Level 1', 'Mfangano East', 42),
								(713, 'St Luke''s Health Centre (Mbita)', '14116', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 203, 698, 567, 0, 'public', 1, 8, NULL, 'Not Classified', 'Mfangano East', 24),
								(714, 'Yokia Dispensary ', '14176', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 203, 699, 567, 0, 'public', 1, 8, NULL, 'Level 1', 'Mfangano North', 24),
								(715, 'Sex Workers Operation Project (SWOP) ,Eastlands', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 700, 584, 0, 'public', 1, 30, NULL, 'Not Classified', '', 31),
								(716, 'Sex Workers Operation Project (SWOP), Kariobangi', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 166, 701, 584, 0, 'public', 1, 30, NULL, 'Level 2', '', 31),
								(717, 'Wakula Dispensary ', '14169', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 203, 702, 567, 0, 'public', 1, 8, NULL, 'Not Classified', 'Mfangano North', 24),
								(718, 'Sex Workers Operation Project (SWOP), Kawangware', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 163, 703, 584, 0, 'public', 1, 30, NULL, 'Level 4', ' Kawangware', 31),
								(719, 'Sex Workers Operation Project (SWOP), Langata', '18176', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 167, 704, 584, 0, 'public', 1, 30, NULL, 'Level 3', 'Mugumoini', 31),
								(720, 'Sex Workers Operation Project (SWOP), Town centre', '13180', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 165, 705, 584, 0, 'public', 1, 30, NULL, 'Level 2', 'Central Business District', 31),
								(721, 'Takawiri Dispensary ', '14140', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 203, 706, 567, 0, 'public', 1, 8, NULL, 'Not Classified', 'Mfangano East', 24),
								(722, 'Ringiti Health Centre', '17710', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 203, 707, 567, 0, 'public', 1, 8, NULL, 'Not Classified', 'Mfangano West', 24),
								(723, 'Remba Dispensary', '17593', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 203, 708, 567, 0, 'public', 1, 8, NULL, 'Level 2', 'Mfangano North', 24),
								(724, '15KR (Kenya Rifle)', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 20, 709, 387, 0, 'private', 1, 31, NULL, 'Not Classified', '', 42),
								(725, 'Ugina Health Centre', '14155', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 203, 710, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', 'Mfangano South', 24),
								(726, '3KR (Kenya Rifle)', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 20, 711, 387, 0, 'private', 1, 31, NULL, 'Not Classified', '', 42),
								(727, '4th Brigade', '', 'satellite', 'PEPFAR', 'ART,PEP,LAB,RTK', 1728, 111, 712, 387, 0, 'private', 1, 20, NULL, 'Not Classified', '', 42),
								(728, '9KR (Kenya Rifle)', '', 'satellite', 'PEPFAR', 'ART,PEP,LAB,RTK', 1363, 105, 713, 387, 0, 'private', 1, 44, NULL, 'Not Classified', '', 42),
								(729, 'Kenya Navy', '11459', 'satellite', 'PEPFAR', 'PMTCT', NULL, 196, 714, 387, 0, 'private', 1, 28, NULL, 'Not Classified', 'Mtongwe', 42),
								(730, 'Laikipia Air Base', '', 'satellite', 'PEPFAR', 'ART,PEP,LAB,RTK', 1728, 111, 715, 387, 0, 'private', 1, 20, NULL, 'Not Classified', 'Laikipia', 42),
								(731, 'Naivasha Medical Clinic', '15282', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 51, 716, 6, 0, 'mission', 1, 31, NULL, 'Level 3', 'Hellsgate', 2),
								(732, 'Moi Teaching Refferal Hospital Dispensing Point', '15204', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 103, 717, 144, 0, 'public', 1, 44, NULL, 'Level 6', 'Chepkoilel', 3),
								(733, 'Ziwa Sub-District Hospital', '15788', 'standalone', 'PEPFAR', 'ART', 1363, 104, 718, NULL, 1, 'public', 1, 44, NULL, 'Level 2', 'Sirikwa', 3),
								(734, 'Amase Dispensary', '15797', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 214, 719, 553, 0, 'public', 1, 4, NULL, 'Level 2', 'Asinge', 3),
								(735, 'Andersen Medical Centre', '14203', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 191, 720, 144, 0, 'private', 1, 42, NULL, 'Level 3', 'Chepchoina', 3),
								(736, 'Bokoli Hospital', '15808', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 30, 721, NULL, 0, 'public', 0, 3, NULL, 'Level 4', 'Bokoli', 3),
								(737, 'Bumala A Health Centre', '15823', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 143, 722, NULL, 1, 'public', 1, 4, NULL, 'Level 2', 'Bumala', 3),
								(738, 'Bumala B Health Centre', '15824', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 143, 723, NULL, 1, 'public', 1, 4, NULL, 'Level 3', 'Marachi East', 3),
								(739, 'Changara (GOK) Dispensary', '16421', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 214, 724, 554, 0, 'public', 1, 4, NULL, 'Level 2', 'Changara', 3),
								(740, 'Chepsaita Dispensary', '14358', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 104, 725, 498, 0, 'public', 1, 44, NULL, 'Level 2', 'Ngenyilel', 3),
								(741, 'Cheptais Sub District Hospital', '15855', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 132, 726, 98, 0, 'public', 1, 3, NULL, 'Level 4', 'Cheptais', 3),
								(742, 'Chesikaki  Dispensary', '15856', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 132, 727, 98, 0, 'public', 1, 3, NULL, 'Level 2', 'Chesikaki', 3),
								(743, 'GK Prisons Dispensary (Busia)', '15891', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 206, 728, 28, 0, 'public', 1, 4, NULL, 'Level 2', 'Bukhayo West', 8),
								(744, 'GK Prisons Dispensary (Ngeria)', '14524', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 123, 729, 144, 0, 'public', 1, 44, NULL, 'Level 1', 'Ngeria', 3),
								(745, 'Kaptama (Friends) Health Centre', '15925', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 132, 730, 552, 0, 'public', 1, 3, NULL, 'Level 3', 'Kaptama', 3),
								(746, 'Kibisi Dispensary', '15943', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 124, 731, 548, 0, 'public', 1, 3, NULL, 'Level 2', 'Kibisi', 3),
								(747, 'Kopsiro Health Centre', '15956', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 132, 732, NULL, 0, 'public', 0, 3, NULL, 'Level 3', 'Kopsiro', 3),
								(748, 'Lukolis Dispensary', '15968', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 214, 733, 554, 0, 'public', 1, 4, NULL, 'Level 2', 'Amukura', 3),
								(749, 'Lupida Health Centre', '15975', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 206, 734, 760, 0, 'public', 1, 4, NULL, 'Level 3', 'Bukhayo North', 3),
								(750, 'Makutano  Dispensary', '15992', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 124, 735, 548, 0, 'public', 1, 3, NULL, 'Level 3', 'Tongaren', 3),
								(751, 'Makutano (PCEA) Medical Clinic (Trans Nzoia East)', '15458', 'satellite', 'PEPFAR', 'ART,PEP', 1701, 159, 736, NULL, 0, 'public', 1, 42, NULL, 'Not Classified', 'Makutano', 3),
								(752, 'Malaba Dispensary', '15993', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 214, 737, 554, 0, 'public', 1, 4, NULL, 'Level 2', 'Akadetewai', 3),
								(753, 'Mihuu Dispensary', '16016', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 30, 738, NULL, 0, 'public', 0, 3, NULL, 'Level 4', 'Chetambe', 3),
								(754, 'Milo Health Centre', '16018', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 30, 739, NULL, 0, 'public', 0, 3, NULL, 'Level 2', 'Sitikho', 3),
								(755, 'Moi Baracks', '17485', 'satellite', 'PEPFAR', 'ART,PEP', 1728, 104, 740, 387, 0, 'public', 1, 44, NULL, 'Not Classified', 'kamagut', 3),
								(756, 'Moi University Health Centre', '15205', 'satellite', 'PEPFAR', 'ART,PEP', 895, 123, 741, 144, 0, 'ngo', 1, 44, NULL, 'Level 1', 'Kesses', 3),
								(757, 'Moiben Health Centre', '15206', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 103, 742, 144, 0, 'ngo', 1, 44, NULL, 'Level 1', 'Moiben', 3),
								(758, 'Moi''s Bridge Health Centre', '15209', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 104, 743, NULL, 1, 'public', 1, 44, NULL, 'Level 3', 'Moisbridge', 3),
								(759, 'Mukhobola Health Centre', '16029', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 125, 744, NULL, 1, 'public', 1, 4, NULL, 'Level 2', 'Bunyala Central', 3),
								(760, 'Nambale Health Centre', '16066', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 206, 745, NULL, 1, 'public', 0, 4, NULL, 'Level 3', 'Nambale', 3),
								(761, 'Obekai Dispensary', '16087', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 134, 746, 553, 0, 'public', 1, 4, NULL, 'Not Classified', 'Mukura', 3),
								(762, 'Osieko Dispensary', '17680', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 125, 747, 423, 0, 'public', 1, 4, NULL, 'Level 2', 'South Bunyala', 3),
								(763, 'Pioneer Health Centre', '15463', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 123, 748, 144, 0, 'public', 1, 44, NULL, 'Level 4', 'Pioneer', 3),
								(765, 'Saboti Sub-District Hospital', '15508', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 121, 750, 540, 0, 'public', 1, 42, NULL, 'Level 4', 'Saboti', 3),
								(766, 'Sango Dispensary', '16100', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 131, 751, 107, 0, 'public', 1, 11, NULL, 'Level 2', 'Kongoni', 3),
								(767, 'Sinoko Dispensary (Bungoma East)', '16126', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 30, 752, NULL, 0, 'public', 0, 3, NULL, 'Not Classified', 'Ndivisi', 3),
								(768, 'Sinoko Dispensary (Lugari)', '16127', 'satellite', 'PEPFAR', 'ART,PEP', 874, 45, 753, NULL, 0, 'public', 1, 11, NULL, 'Level 2', 'Namunyiri', 3),
								(769, 'Soy Health Centre', '15623', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 104, 754, NULL, 1, 'public', 1, 44, NULL, 'Level 3', 'Soy', 3),
								(770, 'Tambach Sub-District Hospital', '15703', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 107, 755, 499, 0, 'public', 1, 5, NULL, 'Level 4', 'Kiptuilong', 3),
								(771, 'Tenges  Health Centre', '15718', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 171, 756, 67, 0, 'public', 1, 1, NULL, 'Level 3', 'Tenges', 3),
								(772, 'Tulwet Dispensary(Buret)', '17403', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 174, 757, 540, 0, 'public', 1, 2, NULL, 'Level 2', 'Tulwet', 3),
								(774, 'Diguna Dispensary', '14403', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 119, 759, NULL, 0, 'mission', 1, 32, NULL, 'Level 2', 'Tinderet', 3),
								(775, 'GK Prisons Dispensary (Eldoret East)', '14519', 'satellite', 'PEPFAR', 'ART,PEP', 895, 103, 760, 144, 0, 'public', 1, 44, NULL, 'Level 1', 'Chepkoilel', 3),
								(776, 'Kapenguria District Hospital Dispensing Point', '14701', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 40, 761, 76, 0, 'public', 1, 47, NULL, 'Level 4', 'Kapenguria', 7),
								(777, 'Ortum Mission Hospital', '15446', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 118, 762, 76, 0, 'mission', 1, 47, NULL, 'Level 4', 'Batei', 9),
								(778, 'Tamkal Dispensary', '15704', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 118, 763, NULL, 0, 'public', 1, 47, NULL, 'Level 2', 'Muino', 7),
								(779, 'Nanyuki District Hospital dispensing point', '15305', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 111, 764, 161, 0, 'public', 1, 20, NULL, 'Level 3', 'Nanyuki', 42),
								(780, 'Nanyuki Cottage Hospital', '15304', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 111, 765, 161, 0, 'public', 1, 20, NULL, 'Level 4', 'Nanyuki', 7),
								(781, 'Lodwar District Hospital Dispensing Point', '15049', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 38, 766, 563, 0, 'public', 1, 43, NULL, 'Level 4', 'Lodwar Town', 42),
								(782, 'Maralal District Hospital Dispensing Point	', '15126', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 154, 767, 576, 0, 'public', 1, 37, NULL, 'Level 4', 'Maralal', 42),
								(783, 'Baragoi District Hospital', '14228', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 160, 768, 576, 0, 'public', 1, 37, NULL, 'Level 4', 'Baragoi', 42),
								(784, 'South Horr Health Centre', '15621', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 160, 769, 576, 0, 'public', 1, 37, NULL, 'Level 3', 'South Horr', 42),
								(786, 'Lugari District Hospital Dispensing Point', '15969', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 45, 771, 109, 0, 'public', 1, 11, NULL, 'Level 2', 'Marakusi', 42),
								(787, 'Bomu Medical Hospital (Changamwe) Dispensing Point', '11258', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 175, 772, 22, 0, 'private', 1, 28, NULL, 'Level 3', 'Changamwe', 25),
								(788, 'Ngilai Health Center', '14459', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 147, 773, 562, 0, 'ngo', 1, 37, NULL, 'Level 2', 'Ngilai', 42),
								(789, 'Archers Post Health Centre', '14212', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 147, 774, 562, 0, 'public', 1, 37, NULL, 'Level 3', 'Waso East', 42),
								(790, 'Suguta Marmar Health Centre', '15682', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 154, 775, 576, 0, 'public', 1, 37, NULL, 'Level 3', 'Suguta Marmar', 42),
								(791, 'AIC Litein Mission Hospital', '14178', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 174, 776, NULL, 1, 'mission', 0, 12, NULL, 'Level 1', 'Litein', 32),
								(793, 'Kapkatet District Hospital Dispensing Point', '14706', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 174, 778, 491, 0, 'public', 1, 12, NULL, 'Level 4', 'Kapkatet', 32),
								(795, 'Koiwa Health Centre', '14970', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 190, 780, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Koiwo', 32),
								(797, 'St. Alice (EDARP) Dandora', '18219', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 782, 42, 0, 'mission', 1, 30, NULL, 'Level 2', 'Dandora', 14),
								(798, 'Mbiuni Health Centre', '12503', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 83, 783, 153, 0, 'public', 1, 22, NULL, 'Not Classified', 'Mbiuni', 17),
								(799, 'Mugunda Catholic Dispensary', '10750', 'standalone', 'KEMSA', 'ART,PMTCT,PEP', 998, 61, 784, NULL, 0, 'mission', 0, 36, NULL, 'Not Classified', 'Mugunda', 9),
								(800, 'Kakuyuni Health Centre', '16433', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 39, 785, 75, 0, 'public', 1, 22, NULL, 'Not Classified', '', 0),
								(801, 'Takaba District Hospital', '13445', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 161, 786, 568, 0, 'public', 1, 24, NULL, 'Level 1', 'Takaba', 24),
								(802, 'Elwak Sub District Hospital', '13335', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 199, 787, 568, 0, 'public', 1, 24, NULL, 'Level 4', 'Elwak Town', 42),
								(803, 'Kibera South (MSF Belgium) Dispensary', '13030', 'standalone', 'MSF', 'ART,PMTCT,PEP,LAB,RTK', 998, 167, 788, NULL, 1, 'ngo', 1, 30, NULL, 'Level 1', 'Kibera', 47),
								(804, 'Silanga (MSF Belgium) Dispensary', '13186', 'standalone', 'MSF', 'ART,PMTCT,PEP,LAB,RTK', 998, 167, 789, NULL, 1, 'ngo', 1, 30, NULL, 'Not Classified', 'Laini Saba', 47),
								(805, 'Gatwikera (MSF Belgium) Medical Clinic', '12948', 'standalone', 'MSF', 'ART,PMTCT,PEP,LAB,RTK', 998, 167, 790, NULL, 0, 'ngo', 0, 30, NULL, 'Level 1', 'Sarang''ombe', 47),
								(806, 'Nyamaraga Health Center', '13897', 'standalone', '', 'ART,PMTCT,PEP', NULL, 96, 791, NULL, 0, 'public', 0, 27, NULL, 'Not Classified', '', 24),
								(807, 'St. Veronica (EDARP)', '18409', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 165, 792, 42, 0, 'mission', 1, 30, NULL, 'Level 2', 'Eastleigh South', 14),
								(808, 'Mukunike Health Center', '16435', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 39, 793, 75, 0, 'public', 1, 22, NULL, 'Not Classified', 'Kakuyuni', 0),
								(809, 'Kutus Dispensary', '10647', 'standalone', '', 'ART,PMTCT,PEP', NULL, 56, 797, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', '', 24),
								(810, 'Lea Toto Program Mwiki/Zimmerman', '', 'satellite', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 166, 798, 396, 0, 'ngo', 1, 30, NULL, 'Not Classified', '', 0),
								(811, 'Lea Toto Program Kangemi', '16800', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 47, 799, 396, 0, 'ngo', 1, 30, NULL, 'Not Classified', 'Kangemi', 13),
								(812, 'Kaboson Health Centre', '14628', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 176, 800, 215, 0, 'mission', 1, 2, NULL, 'Level 3', 'Kaboson', 32),
								(813, 'Naikara Dispensary', '15276', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 207, 801, 215, 0, 'mission', 1, 33, NULL, 'Level 2', 'Naikara', 32),
								(814, 'Ngito Dispensary', '15348', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 207, 802, 215, 0, 'mission', 1, 33, NULL, 'Level 2', 'Ngito', 32),
								(815, 'Tenwek Mission Hospital Dispensing Point', '15719', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 102, 803, 215, 0, 'mission', 1, 12, NULL, 'Not Classified', 'Township', 32),
								(816, 'Serem Health Centre ', '15545', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 34, 804, 527, 0, 'public', 1, 32, NULL, 'Not Classified', 'Mugen', 24),
								(817, 'Koilot Health Centre', '14965', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 115, 805, 527, 0, 'public', 1, 32, NULL, 'Level 3', 'Koilot', 32),
								(818, 'Kabunyeria Health Centre', '14632', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 119, 806, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Kabirer', 32),
								(819, 'Soba River Health Centre', '15601', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 119, 807, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Soba', 32),
								(820, 'Kabiyet Health Centre', '14623', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 116, 808, 78, 0, 'public', 1, 32, NULL, 'Level 3', 'Kabiyet', 32),
								(821, 'Kabiemit Dispensary', '14618', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 116, 809, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Kabiemit', 32),
								(822, 'Chepkemel Health Centre (Mosop)', '14339', 'satellite', 'PEPFAR', 'PMTCT', 895, 116, 810, 78, 0, 'public', 1, 32, NULL, 'Level 3', 'Kipkarren', 24),
								(823, 'Chepkumia Dispensary', '14348', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 41, 811, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Chepkumia', 32),
								(824, 'Bura District Hospital', '13339', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 156, 812, 557, 0, 'public', 1, 7, NULL, 'Level 4', 'Bura', 24),
								(825, 'Transmara District Hospital', '15739', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 217, 813, NULL, 1, 'public', 0, 33, NULL, 'Level 2', 'Ololchani', 32),
								(826, 'Ndanai Hospital', '15322', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 137, 814, 487, 0, 'public', 1, 2, NULL, 'Level 4', 'Ndanai', 32),
								(827, 'Chemosot Health Centre', '14323', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 174, 815, 491, 0, 'public', 1, 2, NULL, 'Level 3', 'Chemosot', 32),
								(828, 'Chebangang Health Centre', '14289', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 190, 816, 487, 0, 'public', 1, 2, NULL, 'Level 3', 'Chebangang', 32),
								(829, 'Cheptalal Sub-District Hospital', '14366', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 190, 817, 487, 0, 'public', 1, 2, NULL, 'Level 4', 'Cheptalal', 32),
								(830, 'Tarakwa Dispensary ', '15710', 'satellite', 'PEPFAR', 'PMTCT', 952, 137, 818, 487, 0, 'mission', 1, 2, NULL, 'Level 2', 'Tarakwa', 32),
								(831, 'Liverpool VCT Centre Kisumu', '16662', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 2, 819, NULL, 1, 'ngo', 1, 17, NULL, 'Level 2', 'Township', 23),
								(832, 'Nairobi Hospital', '13110', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 47, 822, NULL, 0, 'private', 0, 30, NULL, 'Not Classified', 'Upper hill', 27),
								(833, 'Metropolitan Hospital Nairobi', '13090', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 168, 823, NULL, 0, 'private', 0, 30, NULL, 'Not Classified', '', 27),
								(834, 'Cheborgei Health Centre ', '14300', 'satellite', 'Walter Reed', 'ART,PMTCT,PEP,LAB,RTK', 1363, 174, 824, 491, 0, 'public', 1, 2, NULL, 'Level 3', 'Cheborgei', 32),
								(835, 'Kipsonoi Health Centre', '14920', 'satellite', 'Walter Reed', 'ART,PMTCT,PEP,LAB,RTK', 952, 137, 825, 487, 0, 'public', 1, 2, NULL, 'Level 3', 'Kamungei', 32),
								(836, 'Pfizer Laboratories', '', 'standalone', '', '', NULL, 168, 826, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(837, 'Busia District Hospital Central Site dispensing point', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 126, 827, 28, 0, 'public', 1, 4, NULL, 'Level 4', 'Busia Township', 3),
								(838, 'Olderkesi Dispensary', '15392', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 207, 828, 215, 0, 'public', 1, 33, NULL, 'Level 2', 'Olderkesi', 32),
								(839, 'Transmara District Hospital Dispensing Point', '15739', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 217, 829, 825, 0, 'public', 1, 33, NULL, 'Level 4', 'Ololchani', 32),
								(840, 'Diocese of Lodwar HIV/AIDS Programme', 'K550', 'central', 'PEPFAR', '', NULL, 38, 830, NULL, 1, 'mission', 0, 43, NULL, 'Not Classified', '', 0),
								(842, 'Nariokotome Dispensary', '15310', 'satellite', 'PEPFAR', 'ART,PEP', 1701, 162, 832, 840, 0, 'mission', 1, 43, NULL, 'Level 2', 'Ngissiger', 0),
								(843, 'Lowarengak Dispensary', '15096', 'satellite', 'PEPFAR', 'ART,PEP', 1701, 162, 833, 561, 0, 'mission', 1, 43, NULL, 'Level 1', 'Ngissiger', 0),
								(844, 'Kataboi Dispensary', '14814', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 162, 834, 563, 0, 'mission', 1, 43, NULL, 'Not Classified', 'Kataboi', 19),
								(845, 'St Mary''s Kalokol Primary Health Care Programme', '15656', 'satellite', 'PEPFAR', 'ART,PMTCT', 1701, 38, 835, 840, 0, 'mission', 1, 43, NULL, 'Level 2', 'Kalokol', 4),
								(846, 'St Catherine''s Napetet Dispensary', '15634', 'satellite', 'PEPFAR', 'ART,PMTCT', 1701, 38, 836, 840, 0, 'mission', 1, 43, NULL, 'Level 2', 'Lodwar Town', 4),
								(847, 'St Monica''s Nakwamekwi Dispensary', '15661', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 38, 837, 840, 0, 'mission', 1, 43, NULL, 'Level 2', 'Lodwar Town', 4),
								(848, 'Marira  Clinic', '10693', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 195, 838, 6, 0, 'mission', 1, 13, NULL, 'Not Classified', 'Gitithia', 2),
								(849, 'Masongaleni Health Centre', '12477', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 72, 839, NULL, 0, 'public', 0, 23, NULL, 'Not Classified', 'Masongaleni', 24),
								(850, 'Gatithi Dispensary', '10221', 'standalone', '', '', NULL, 56, 840, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', '', 0),
								(851, 'Awendo Sub-District Hospital', '13492', 'standalone', '', '', NULL, 65, 841, NULL, 0, 'public', 0, NULL, NULL, 'Not Classified', NULL, NULL),
								(852, 'Makadara Mercy Sisters Dispensary', '13057', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 168, 842, 129, 0, 'mission', 1, 30, NULL, 'Level 2', 'Makadara', 2),
								(853, 'Mater Hospital Dispensing Point', '13074', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 168, 843, 129, 0, 'mission', 1, 30, NULL, 'Level 4', 'Mukuru Nyayo', 2),
								(854, 'Garbatulla District Hospital', '12029', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 179, 844, NULL, 1, 'public', 1, 9, NULL, 'Level 1', 'Garbatulla', 42),
								(855, 'Kangeta Dispensary', '12174', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 180, 845, 170, 0, 'public', 1, 26, NULL, 'Level 3', 'Kangeta', 42),
								(856, 'Kina Dispensary', '12319', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 180, 846, 170, 0, 'public', 1, 26, NULL, 'Level 2', 'Meru N Park', 42),
								(857, 'Mutuati Dispensary', '12605', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 180, 847, 170, 0, 'public', 1, 26, NULL, 'Level 4', 'Mutuati', 33),
								(858, 'Nyambene District Hospital Dispensing point', '12684', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 180, 848, 170, 0, 'public', 1, 26, NULL, 'Not Classified', 'Maua', 42),
								(859, 'Chepsir Dispensary', '14362', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 110, 849, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Kapseger', 32),
								(860, 'Kedowa Dispensary', '14824', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 110, 850, 510, 0, 'public', 0, 12, NULL, 'Level 2', 'Kedowa', 32),
								(861, 'Lemotit Dispensary', '15026', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 110, 851, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Lemotit', 32),
								(862, 'Londiani District Hospital Dispensing Point', '15074', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB', 895, 110, 852, 510, 0, 'public', 1, 12, NULL, 'Level 1', 'Londiani', 32),
								(863, 'Momoniat Dispensary', '15219', 'satellite', 'PEPFAR', 'PMTCT', 895, 110, 853, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Chepseon', 32),
								(864, 'Tea Research Foundation Dispensary', '16471', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 854, 510, 0, 'ngo', 1, 12, NULL, 'Level 2', 'Cheboswa', 32),
								(865, 'Siriba Dispensary ', '14094', 'satellite', 'PEPFAR', 'PMTCT', NULL, 29, 855, 423, 0, 'public', 1, 17, NULL, 'Level 2', 'North West Kisumu', 24),
								(866, 'Riat Dispensary', '14046', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 29, 856, 423, 0, 'public', 1, 17, NULL, 'Level 2', 'West Kisumu', 3),
								(867, 'Sunga Dispensary', '17175', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 29, 857, 423, 0, 'public', 1, 17, NULL, 'Level 2', 'North West Kisumu', 3),
								(868, 'Chulaimbo Sub-District Hospital Dispensing Point', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 29, 858, 423, 0, 'public', 1, 17, NULL, 'Level 4', 'North West Kisumu', 3),
								(869, 'Ndori Health Centre ', '13845', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 98, 859, 450, 0, 'public', 1, 38, NULL, 'Level 3', 'Central Asembo', 24),
								(870, 'Misori Dispensary ', '13815', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 98, 860, 114, 0, 'public', 0, 38, NULL, 'Not Classified', 'West Uyoma', 24),
								(871, 'Naya Health Centre', '13837', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 98, 861, 114, 0, 'public', 1, 38, NULL, 'Level 3', 'South Uyoma', 24),
								(872, 'Kunya Dispensary ', '13725', 'standalone', 'PEPFAR', 'PMTCT', 895, 98, 862, 114, 0, 'public', 0, 38, NULL, 'Level 2', 'East Uyoma', 24),
								(873, 'Nyagoko Dispensary ', '13874', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 98, 863, 450, 0, 'public', 0, 38, NULL, 'Level 2', 'South Asembo', 24),
								(874, 'Masala Dispensary ', '13780', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 98, 864, 114, 0, 'public', 1, 38, NULL, 'Level 2', 'Central Uyoma', 24),
								(875, 'Mahaya Health Centre (Rarieda)', '13757', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 98, 865, 450, 0, 'public', 0, 38, NULL, 'Level 3', 'West Asembo', 24),
								(876, 'St Joseph''s Obaga Dispensary ', '14111', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 98, 866, 450, 0, 'public', 1, 38, NULL, 'Level 2', 'South Asembo', 24),
								(877, 'Rageng''ni Dispensary ', '14026', 'satellite', 'PEPFAR', 'PMTCT', 864, 98, 867, 114, 0, 'public', 1, 38, NULL, 'Level 2', 'East Uyoma', 24),
								(878, 'Rambugu Dispensary (Rarieda)', '17437', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 98, 868, 450, 0, 'public', 1, 38, NULL, 'Level 2', 'West Asembo', 24),
								(879, 'Kiamabara Dispensary', '10530', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 202, 869, 79, 0, 'public', 1, 36, NULL, 'Level 2', 'Gachuku', 24),
								(880, 'Lieta Health Centre (Rarieda)', '17439', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 98, 870, 114, 0, 'public', 1, 38, NULL, 'Not Classified', 'South Uyoma', 24),
								(881, 'Wagoro Dispensary (Rarieda)', '17438', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 98, 871, 114, 0, 'public', 1, 38, NULL, 'Level 2', 'West Uyoma', 24),
								(883, 'Kagwa Health Centre', '13644', 'satellite', 'PEPFAR', 'PMTCT', 864, 98, 873, 114, 0, 'public', 1, 38, NULL, 'Level 3', 'West Uyoma', 24),
								(884, 'Arito Langi Dispensary', '13484', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 29, 874, 424, 0, 'public', 1, 17, NULL, 'Level 2', 'West Seme', 24),
								(885, 'Miranga Sub District Hospital', '13810', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 29, 875, 424, 0, 'public', 1, 17, NULL, 'Level 4', 'Otwenya', 32),
								(886, 'Manyuanda Health Centre', '13770', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 29, 876, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'South West Seme', 24),
								(887, 'Opapla Dispensary', '13990', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 29, 877, 424, 0, 'public', 1, 17, NULL, 'Level 2', 'West Seme', 24),
								(888, 'Lwala Kadawa Dispensary', '17174', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 29, 878, 424, 0, 'public', 1, 17, NULL, 'Level 3', 'West Kisumu', 36),
								(889, 'Nzeveni Dispensary', '12692', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 72, 879, 330, 0, 'public', 1, 23, NULL, 'Not Classified', 'Nzambani', 42),
								(890, 'Makere Dispensary', '11548', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 178, 880, 575, 0, 'public', 1, 40, NULL, 'Level 2', 'Chewani', 24),
								(891, 'Pumwani Dispensary', '11744', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 178, 881, 575, 0, 'public', 1, 40, NULL, 'Level 2', 'Ndura', 24),
								(892, 'St. Raphael Health Centre-Tana River', '11366', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 178, 882, 575, 0, 'mission', 1, 40, NULL, 'Level 3', 'Mikinduni', 48),
								(893, 'Wenje Dispensary', '11903', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 178, 883, 575, 0, 'public', 1, 40, NULL, 'Level 2', 'Wenje', 24),
								(894, 'Bura Health Centre', '11264', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 173, 884, 575, 0, 'public', 1, 40, NULL, 'Level 3', 'Bura', 24),
								(895, 'Madogo Health Centre', '11533', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 173, 885, 575, 0, 'public', 1, 40, NULL, 'Level 3', 'Madogo', 24),
								(896, 'Majengo Dispensary', '11542', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 178, 886, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Majengo', 24),
								(897, 'Ijara Health Centre', '13370', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 151, 887, 569, 0, 'public', 1, 7, NULL, 'Level 3', 'Ijara', 24),
								(898, 'National Quality Control Laboratory (NQCL)', '', 'standalone', '', '', NULL, 163, 888, NULL, 0, '', 0, NULL, NULL, 'Not Classified', NULL, NULL),
								(899, 'St. Vincent De Paul Mission Hospital', '14128', 'standalone', '', '', NULL, 22, 889, 423, 0, 'mission', 0, 17, NULL, 'Not Classified', '', 0),
								(900, 'Lorgum Mission Hospital', '15089', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 38, 890, 840, 0, 'mission', 1, 43, NULL, 'Not Classified', '', 9),
								(901, 'Liverpool VCT Dispensing Point', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 47, 891, NULL, 0, 'ngo', 0, 30, NULL, 'Level 2', 'Kilimani', 23),
								(902, 'Babadogo Health Centre', '12876', 'satellite', '', 'ART,PEP', 998, 166, 892, 584, 0, 'public', 1, 30, NULL, 'Level 1', 'Ruaraka', 31),
								(903, 'Nyamrisra health center', '13915', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', 822, 99, 893, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', 'Nyamrisra', 24),
								(904, 'Mikindani (MCM) Dispensary', '11613', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 175, 894, 181, 0, 'public', 1, 28, NULL, 'Level 2', 'Jomvu', 21),
								(905, 'Miritini CDF Dispensary', '11620', 'satellite', 'PEPFAR', 'PMTCT', 889, 175, 895, 181, 0, 'public', 1, 28, NULL, 'Level 2', 'Miritini', 24),
								(906, 'St Valeria Medical Clinic', '11828', 'satellite', 'PEPFAR', 'ART,PEP', 820, 175, 896, 181, 0, 'private', 1, 28, NULL, 'Changamwe', 'Changamwe', NULL),
								(907, 'Westlands Health Care Services', '11905', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 175, 897, 181, 0, 'private', 1, 28, NULL, 'Level 2', 'Mungusi', 27),
								(908, 'Miritini (MCM) Dispensary', '17822', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 175, 898, 181, 0, 'public', 1, 28, NULL, 'Level 2', 'Miritini', 22),
								(909, 'Port Reitz Hospital - Kilindini District Hospital Dispensing point', '11740', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 175, 899, 181, 0, 'public', 1, 28, NULL, 'Level 4', 'Portreitz', 24),
								(910, 'Shika Adabu (MCM) Dispensary', '11785', 'satellite', 'PEPFAR', 'ART,PEP', NULL, 196, 900, NULL, 0, 'public', 0, 28, NULL, 'Level 1', 'Likoni', 0),
								(911, 'Lumumba Health Centre', '13738', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 2, 901, NULL, 0, 'public', 0, 17, NULL, 'Level 1', 'Township', 20),
								(912, 'Stock Adjustment Account', '', 'standalone', '', '', NULL, 47, 902, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(913, 'Gobei Dispensary', '13581', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 17, 903, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', '', 0),
								(914, 'Saidia Health Center', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 154, 904, 576, 0, 'public', 1, 37, NULL, 'Not Classified', '', 42),
								(915, 'Sereolipi Health Center', '15547', 'satellite', 'PEPFAR', 'PMTCT', 1728, 154, 905, 562, 0, 'public', 1, 37, NULL, 'Not Classified', 'Sereolipi', 24),
								(916, 'Kisima Dispensary, Loroki', '14943', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 154, 906, 576, 0, 'public', 1, 37, NULL, 'Not Classified', ' Kisima', 42),
								(917, 'Wamba Health Center', '15768', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 147, 907, 562, 0, 'public', 1, 37, NULL, 'Level 3', 'Wamba', 42),
								(918, 'GK Prisons', '13578', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 11, 908, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', 'Homa-Bay', 24),
								(919, 'Ogande Dispensary', '13962', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 11, 909, 59, 0, 'public', 1, 8, NULL, 'Level 2', 'East Kanyada', 12),
								(920, 'Nyalkinyi Dispensary', '16986', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 11, 910, 59, 0, 'public', 1, 8, NULL, 'Level 2', 'East Kanyada', 24),
								(921, 'Vyulya Dispensary', '12837', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 83, 911, 629, 0, 'public', 1, 22, NULL, 'Not Classified', 'Masii', 17),
								(923, 'Brothers of St. Joseph HIV/AIDS Self Help Group', '17576', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 281, 913, 174, 0, 'mission', 1, 36, NULL, 'Not Classified', 'Mweiga', 43),
								(924, 'Mweiga Health Centre ', '10809', 'satellite', 'PEPFAR', 'PMTCT', 998, 183, 914, 79, 0, 'public', 1, 36, NULL, 'Not Classified', 'Mweiga', 36),
								(925, 'UON Nyeri Dice1 MARPs Project', '18518', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 25, 915, 174, 0, 'public', 1, 36, NULL, 'Not Classified', 'Mukaro', 43),
								(926, 'Clinton Health Access Initiative', '', 'standalone', '', '', NULL, 163, 916, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(927, 'Pablo Hortsman Health Centre', '11729', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 194, 917, 574, 0, 'ngo', 1, 21, NULL, 'Not Classified', 'Mkomani', 25),
								(928, 'Phillips Pharmaceutical Ltd.', '', 'standalone', '', '', NULL, 170, 918, NULL, 0, 'private', 0, NULL, NULL, 'Not Classified', NULL, NULL),
								(929, 'Bomu Medical Centre (Mariakani)', '18267', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 50, 919, 22, 0, 'ngo', 1, 14, NULL, 'Level 3', 'Mariakani', 25),
								(930, 'Mukurweini District Hospital Dispensing Point', '10763', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 42, 921, 148, 0, 'public', 1, 36, 1257, 'Level 1', 'Muhito', 43),
								(931, 'Ngarua Health Center', '15339', 'satellite', 'PEPFAR', 'PMTCT,PEP', 1728, 52, 922, 516, 0, 'public', 1, 20, NULL, 'Not Classified', 'Kinamba', 7),
								(932, 'Othaya Sub-District. Hospital Dispensing Point', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 42, 923, 176, 0, 'public', 1, 36, 1257, 'Level 1', 'Iriaini', 43),
								(933, 'Oljabet Health Centre', '15404', 'satellite', 'PEPFAR', 'PMTCT,PEP', 1728, 52, 924, 517, 0, 'public', 1, 20, NULL, 'Not Classified', 'Marmanet', 7),
								(934, 'Sipili Health Centre', '15589', 'satellite', 'PEPFAR', 'PMTCT', 1728, 52, 925, 516, 0, 'public', 1, 20, NULL, 'Not Classified', 'Sipili', 7),
								(935, 'Olmoran Health Centre', '15417', 'satellite', 'PEPFAR', 'PMTCT', 1728, 52, 926, 517, 0, 'public', 1, 20, NULL, 'Not Classified', 'Olmoran', 7),
								(936, 'St Bridgit Mother and Child', '13199', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 169, 927, 211, 0, 'mission', 1, 30, NULL, 'Not Classified', 'Kamkunji', 48),
								(937, 'Ngara Health Centre', '13122', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 169, 928, 211, 0, 'public', 1, 30, NULL, 'Not Classified', 'Ngara', 42),
								(938, 'Pangani Dispensary', '13138', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 165, 929, 211, 0, 'public', 1, 30, NULL, 'Not Classified', 'Kariokor', 24),
								(939, 'Huruma NCCK Dispensary', '12972', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 169, 930, 211, 0, 'mission', 1, 30, NULL, 'Level 2', 'Huruma', 48),
								(940, 'Huruma Lions Dispensary', '12974', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 169, 931, 211, 0, 'public', 1, 30, NULL, 'Level 2', 'Huruma', 42),
								(941, 'Rumuruti District Hospital Dispensing Point', '15502', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 52, 932, 517, 0, 'public', 1, 20, NULL, 'Not Classified', 'Rumuruti', 7),
								(942, 'Ngenda Health Centre', '10864', 'standalone', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', NULL, 31, 935, NULL, 0, '', 0, 13, NULL, 'Not Classified', '', 0),
								(943, 'Nyeri Town Health Centre', '10905', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 25, 936, 174, 0, 'public', 1, 36, NULL, 'Not Classified', 'Mukaro', 43),
								(944, 'Kinunga Health Centre', '10615', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 25, 937, 174, 0, 'public', 1, 36, NULL, 'Not Classified', 'Tetu', 43),
								(945, 'Sagam Community Hospital', '14064', 'satellite', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', NULL, 9, 938, NULL, 0, 'private', 0, 38, NULL, 'Not Classified', '', 0),
								(946, 'Hongwe Catholic Dispensary', '11412', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 194, 939, 573, 0, 'mission', 1, 21, NULL, 'Level 2', 'Hongwe', 19),
								(947, 'Mkunumbi Dispensary', '11631', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 194, 940, 573, 0, 'public', 1, 21, NULL, 'Level 2', 'Mkunumbi', 24),
								(948, 'Mpeketoni District Hospital Dispensing Point', '11649', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 152, 941, 573, 0, 'public', 1, 21, NULL, 'Level 1', 'Central', 24),
								(949, 'Kongowea Health Centre', '11499', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 188, 942, 34, 0, 'public', 1, 28, NULL, 'Level 2', 'Kongowea', 21),
								(950, 'Kisauni  Dispensary', '17911', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB', 889, 188, 943, 34, 0, 'public', 1, 28, NULL, 'Level 2', 'Kisauni', 21),
								(951, 'Mazeras Dispensary', '11585', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 149, 944, 566, 0, 'public', 1, 19, NULL, 'Level 2', 'Kasemeni', 24),
								(952, 'Ndavaya Dispensary', '11701', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 149, 945, 566, 0, 'public', 1, 19, NULL, 'Not Classified', 'Ndavaya', 24),
								(953, 'Mnyenzeni Dispensary', '11638', 'satellite', 'PEPFAR', 'PMTCT', 889, 149, 946, 566, 0, 'public', 1, 19, NULL, 'Level 2', 'Kasemeni', 24),
								(954, 'Jera Dispensary ', '13634', 'standalone', '', '', NULL, 9, 947, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', '', 0),
								(955, 'GSU Training School, Embakasi', '12962	', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 948, 376, 0, 'private', 1, 30, NULL, 'Not Classified', 'Embakasi', 42),
								(956, 'APTC Health centre', '12871', 'satellite', 'PEPFAR', 'PMTCT', 998, 170, 949, 376, 0, 'private', 1, 30, NULL, 'Not Classified', 'Embakasi', 42),
								(957, 'Kayole II District Hospital Dispensing Point', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 950, 376, 0, 'public', 1, 30, NULL, 'Level 3', 'Kayole', 21),
								(958, 'Matangwe Community Health Centre', '13787', 'standalone', '', '', NULL, 17, 951, NULL, 0, 'private', 0, 38, NULL, 'Not Classified', '', 0),
								(959, 'Hola District Hospital Dispensing Point', '11411', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 178, 952, 575, 0, 'public', 1, 40, NULL, 'Level 4', 'Zubaki', 24),
								(960, 'Timau SDH', '12802', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 79, 953, NULL, 0, 'public', 0, 26, NULL, 'Not Classified', 'Kirimara', 24),
								(961, 'Jomvu Model Health Centre ', '11436', 'satellite', 'PEPFAR', 'PMTCT', 889, 175, 954, 181, 0, 'private', 1, 28, NULL, 'Level 2', 'Kiritini', 27),
								(962, 'Vigurungani Dispensary', '11880', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 149, 955, 566, 0, 'public', 1, 19, NULL, 'Not Classified', 'Vigurungani', 24),
								(963, 'Taveta District Hospital', '11840', 'standalone', '', '', NULL, 66, 956, NULL, 0, 'public', 0, NULL, NULL, 'Not Classified', NULL, NULL),
								(964, 'Kizingitini Dispensary', '11496', 'satellite', 'PEPFAR', 'PMTCT', 889, 193, 957, 574, 0, 'public', 1, 21, NULL, 'Not Classified', 'Kizingitini', 24),
								(965, 'Shella Dispensary', '11784', 'satellite', 'PEPFAR', 'ART,PMTCT', 889, 194, 958, 574, 0, 'public', 1, 21, NULL, 'Not Classified', 'Shella', 24),
								(966, 'Rhodes Chest Clinic', '13163', 'standalone', '', '', NULL, 165, 959, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', NULL, NULL),
								(967, 'Sex Workers Operation Project (SWOP), Thika Road', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 166, 960, 584, 0, 'public', 1, 30, NULL, 'Not Classified', '', 31),
								(969, 'UON/UOM Pumwani  VCT Centre Dispensing Point', '13157', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 165, 962, 584, 0, 'public', 1, 30, NULL, 'Not Classified', 'Pumwani', 31),
								(970, 'Karuri Health Centre', '10507', 'standalone', '', '', NULL, 5, 963, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', NULL, NULL),
								(971, 'Aluor Mission Health Centre', '13473', 'standalone', '', '', NULL, 9, 964, NULL, 0, 'mission', 0, 38, NULL, 'Not Classified', NULL, NULL),
								(972, 'Ratuoro Health Centre', '13641', 'standalone', '', '', NULL, 9, 965, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', NULL, NULL),
								(973, 'Aga Khan Hospital (Kisumu)', '13465', 'standalone', '', '', NULL, 2, 966, NULL, 0, 'private', 0, 17, NULL, 'Not Classified', NULL, NULL),
								(974, 'Airport Dispensary (Kisumu)', '13469', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 2, 967, 423, 0, 'public', 0, 17, NULL, 'Not Classified', 'East Kisumu', 42),
								(975, 'Kowino Dispensary ', '13722', 'standalone', 'PEPFAR', 'PMTCT', NULL, 2, 968, NULL, 0, 'public', 0, 17, NULL, 'Not Classified', 'Kolwa West', 0),
								(976, 'Wajir District Hospital Dispensing Point', '13452', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 220, 969, 558, 0, 'public', 1, 46, NULL, 'Level 4', 'Central', 24),
								(977, 'Griftu District Hospital ', '13352', 'satellite', 'PEPFAR', 'PMTCT', 1728, 272, 970, 558, 0, 'public', 1, 46, NULL, 'Not Classified', 'Griftu', 24),
								(978, 'Bute District Hospital ', '13314', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 146, 971, 558, 0, 'public', 1, 46, NULL, 'Not Classified', 'Bute', 24),
								(979, 'Gilgil Military Regional Hospital', '14511', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 51, 972, NULL, 0, 'public', 0, 31, NULL, 'Not Classified', 'Gilgil', 42),
								(980, 'Mama Lucy Kibaki Hospital', '17411', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 973, 376, 0, 'private', 1, 30, NULL, 'Not Classified', 'Umoja', 5),
								(981, 'Diwopa ', '12917', 'satellite', 'PEPFAR', 'PMTCT', 998, 170, 974, 376, 0, 'private', 1, 30, NULL, 'Not Classified', 'Kayole', 33),
								(982, 'St Raphael Dispensary Mihang''o', '17683', 'satellite', 'PEPFAR', 'PMTCT', 998, 170, 975, 376, 0, 'mission', 1, 30, NULL, 'Not Classified', '', 27),
								(984, 'Ponge Health Centre', '14015', 'satellite', 'PEPFAR', 'PMTCT', 895, 208, 977, 405, 0, 'public', 1, 8, NULL, 'Level 2', 'South Kabuoch', 42),
								(985, 'Tenwek Community Mobile Clinic', '15720', 'satellite', 'PEPFAR', 'PMTCT', 1728, 278, 1016, 487, 0, 'public', 1, 31, NULL, 'Level 2', 'Keringet', 32),
								(986, 'Tendwet Dispensary', '17859', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 278, 1015, 501, 0, 'private', 1, 31, NULL, 'Level 2', 'Kapsimbeiywo', 32),
								(987, 'Silibwet Dispensary', '17860', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 278, 1014, 487, 0, 'public', 1, 31, NULL, 'Level 2', 'Silibwet', 32),
								(988, 'Miniambo Dispensary', '16766', 'satellite', '', 'PMTCT', 864, 11, 978, 59, 0, 'public', 1, 8, NULL, 'Level 2', 'East Kanyada', 24),
								(989, 'Asumbi Mission Hospital Dispensing Point', '13488', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 11, 979, 14, 0, 'mission', 1, 8, NULL, 'Not Classified', 'Central Gem', 2),
								(990, 'Katilu District Hospital', '14818', 'satellite', 'PEPFAR', 'PMTCT', 1701, 218, 980, 563, 0, 'public', 1, 43, NULL, 'Not Classified', 'Katilu', 24),
								(991, 'Turkwel Dispensary (Loima)', '15754', 'satellite', 'PEPFAR', 'PMTCT', 952, 38, 981, 563, 0, 'public', 1, 43, NULL, 'Not Classified', 'Lorugum', 24),
								(992, 'Makutano Dispensary (Turkana North)', '15117', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 7, 982, 563, 0, 'public', 1, 43, NULL, 'Not Classified', 'Nakalale', 24),
								(993, 'Lopiding Sub-District Hospital', '15081', 'satellite', 'PEPFAR', 'PMTCT', 1701, 7, 983, 563, 0, 'public', 1, 43, NULL, 'Not Classified', 'Mogila', 24),
								(994, 'Burnt Forest RHDC (Eldoret East) Dispensing Point', '16347', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 103, 984, 27, 0, 'public', 1, 44, NULL, 'Level 3', 'Olare', 3),
								(997, 'Kitale District Hospital Dispensing Point', '14947', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 121, 987, 540, 0, 'public', 1, 42, NULL, 'Level 4', 'Kibomet', 3),
								(998, 'GK Farm Dispensary (Trans Nzoia)', '14514', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 121, 988, NULL, 0, 'public', 0, 42, NULL, 'Not Classified', 'Tumaini', 42),
								(999, 'Chewani Dispensary', '11283', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 178, 989, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Chewani', 24),
								(1001, 'Lugari Forest Dispensary', '15964', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 45, 991, 109, 0, 'public', 0, 11, NULL, 'Level 2', 'Lugari', 42),
								(1002, 'Kongoni Health Centre', '15955', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 45, 992, 107, 0, 'public', 1, 11, NULL, 'Level 3', 'Kongoni', 42),
								(1003, 'Jibana Sub- District Hospital', '11432', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 50, 993, 125, 0, 'public', 1, 14, NULL, 'Not Classified', 'Jibana', 24),
								(1004, 'Makanzani Dispensary', '11547', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 50, 994, 301, 0, 'public', 1, 14, NULL, 'Not Classified', 'Ruruma', 24),
								(1005, 'Aneko Dispensary', '13478', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 211, 995, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'West Kadem', 42),
								(1006, 'Kituka Dispensary', '13706', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 822, 211, 996, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'Central Kadem', 42),
								(1007, 'Kombato Dispensary', '16270', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 211, 997, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'S.E Kadem', 24),
								(1008, 'Thim Lich Dispensary', '16278', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 822, 96, 998, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'N.E.Kadem', 42),
								(1009, 'Bande Dispensary', '13494', 'satellite', 'PEPFAR', 'PMTCT', 822, 211, 999, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'East Kadem', 42),
								(1010, 'Ngararia Dispensary', '10857', 'standalone', 'PEPFAR', 'PMTCT', 998, 57, 1000, NULL, 0, 'private', 0, 29, NULL, 'Not Classified', 'Muruka', 36),
								(1011, 'Yago Dispensary', '16279', 'satellite', 'PEPFAR', 'PMTCT', 895, 211, 1001, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'East Kadem', 42),
								(1012, 'Kabuto Dispensary', '13639', 'satellite', 'PEPFAR', 'PMTCT', 822, 211, 1002, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'Central Kadem', 42),
								(1013, 'Namba Kodero Dispensary', '16273', 'satellite', 'PEPFAR', 'PMTCT', 895, 211, 1003, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'East Kadem', 42),
								(1014, 'Kipingi Dispensary', '16269', 'satellite', 'PEPFAR', 'PMTCT', 895, 211, 1004, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'S.E Karungu', 42),
								(1015, 'Winjo Dispensary', '14173', 'satellite', 'PEPFAR', 'PMTCT', 864, 211, 1005, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'East Muhuru', 42),
								(1016, 'Ochuna Dispensary', '13959', 'satellite', 'PEPFAR', 'PMTCT', NULL, 211, 1006, 112, 0, 'public', 1, 27, NULL, 'Not Classified', 'Kaler', 15),
								(1017, 'Ruqa Dispensary', '13426', 'satellite', 'PEPFAR', 'PMTCT', 828, 151, 1007, 569, 0, 'public', 1, 7, NULL, 'Level 2', 'Ruqa', 24),
								(1018, 'Furqan Dispensary', '16659', 'satellite', 'PEPFAR', 'PMTCT', 828, 151, 1008, 569, 0, 'public', 1, 7, NULL, 'Level 2', 'Masalani', 24),
								(1019, 'Kabarnet District Hospital Dispensing Point', '14607', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 101, 1009, 67, 0, 'public', 1, 1, NULL, 'Level 4', 'Kapropita', 3),
								(1020, 'Mosoriot Rural Health Training Centre Dispensing Point', '15229', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 116, 1010, 529, 0, 'public', 1, 32, NULL, 'Level 3', 'Mutwot', 3),
								(1021, 'Kabartonjo District Hospital Dispensing Point', '14609', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 101, 1011, 605, 0, 'public', 1, 1, NULL, 'Level 1', 'Kabartonjo', 24),
								(1022, 'Nandi Hills District Hospital Dispensing Point', '14179', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 115, 1012, 527, 0, 'public', 1, 32, NULL, 'Level 4', 'Kaplelmet', 32),
								(1023, 'Kakuma Mission Hospital Dispensing Point', '14655', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 162, 1013, NULL, 0, 'mission', 0, 43, NULL, 'Not Classified', 'Kakuma', 9),
								(1024, 'Kericho District Hospital Dispensing Point', '14831', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 108, 1017, 504, 0, 'public', 1, 12, NULL, 'Level 1', 'Township', 32),
								(1025, 'GK Prisons Dispensary(Kericho)', '14521', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 108, 1018, 504, 0, 'public', 0, 12, NULL, 'Level 2', 'Township', 42),
								(1026, 'Adurkoit Dispensary', '14185', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 40, 1019, NULL, 0, 'public', 1, 47, NULL, 'Level 2', 'Kanyarkwat', 7),
								(1027, 'Mbaga Health Centre', '13797', 'standalone', '', 'ART,PMTCT,PEP', NULL, 9, 1020, NULL, 0, 'mission', 0, 38, NULL, 'Not Classified', '', 0);
								INSERT INTO `sync_facility` (`id`, `name`, `code`, `category`, `sponsors`, `services`, `manager_id`, `district_id`, `address_id`, `parent_id`, `ordering`, `affiliation`, `service_point`, `county_id`, `hcsm_id`, `keph_level`, `location`, `affiliate_organization_id`) VALUES
								(1028, 'Ndhiwa Sub-District Hospital Dispensing Point', '13841', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 208, 1021, 405, 0, 'public', 1, 8, NULL, 'Level 4', 'West Kanyamwa', 42),
								(1029, 'Kabirirsang Dispensary', '14630 ', 'satellite', 'PEPFAR', 'PMTCT', 895, 41, 1022, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Kabirirsang', 32),
								(1030, 'Kiasa Dispensary', '13686', 'satellite', 'PEPFAR', 'PMTCT', 895, 208, 1023, 405, 0, 'public', 1, 8, NULL, 'Level 2', 'East Kwabai', 42),
								(1031, 'Kapsabet District Hospital Dispensing Point', '14749', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 41, 1024, 78, 0, 'public', 1, 32, NULL, 'Level 4', 'Kapsabet Township', 32),
								(1032, 'Nguku Dispensary', '13855', 'satellite', 'PEPFAR', 'PMTCT', 895, 208, 1025, 405, 0, 'public', 1, 8, NULL, 'Level 2', 'West Kanyadoto', 42),
								(1033, 'Kapsisiywo Dispensary', '14761 ', 'satellite', 'PEPFAR', 'PMTCT', 895, 41, 1026, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Kapsisiywo', 32),
								(1034, 'Oridi Dispensary', '16767', 'satellite', 'PEPFAR', 'PMTCT', 895, 208, 1027, 405, 0, 'public', 1, 8, NULL, 'Not Classified', 'North Kanyikela', 42),
								(1035, 'Malela Dispensary', '13761', 'satellite', 'PEPFAR', 'PMTCT', 895, 208, 1028, 405, 0, 'public', 1, 8, NULL, 'Level 2', 'North Kanyamwa', 42),
								(1036, 'Ober Kabuoch Dispensary', '13952', 'satellite', 'PEPFAR', 'PMTCT', 895, 208, 1029, 405, 0, 'public', 1, 8, NULL, 'Level 2', 'Riana', 42),
								(1037, 'Ombo Kachieng'' Dispensary', '13979', 'satellite', 'PEPFAR', 'PMTCT', 895, 208, 1030, 405, 0, 'public', 1, 8, NULL, 'Level 2', 'West Kabuoch', 42),
								(1038, 'Okok Dispensary', '16259', 'satellite', 'PEPFAR', 'PMTCT', 895, 208, 1031, 405, 0, 'public', 1, 8, NULL, 'Level 2', 'S. Kanyamwa', 42),
								(1039, 'Ongo Health Centre', ' 13989 ', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 65, 1033, 189, 0, 'public', 1, 27, NULL, 'Level 3', 'South Kamagambo', 41),
								(1040, 'Tumaini drop in Centre (DICE)', '13552', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 2, 1034, 46, 0, 'ngo', 1, 17, NULL, 'Not Classified', 'West Kolwa', 20),
								(1041, 'Kitere Dispensary', '17341 ', 'satellite', 'PEPFAR', 'PMTCT', NULL, 65, 1035, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'South Kamagambo', 35),
								(1042, 'Koloo Dispensary', '17038', 'satellite', 'PEPFAR', 'PMTCT', 822, 53, 1036, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'North Kanyamkago', 35),
								(1043, ' Kolwal Dispensary', '17344 ', 'satellite', 'PEPFAR', 'PMTCT', 822, 53, 1037, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'South East Kanyamkago', 35),
								(1044, 'Kuja Dispensary', '13724 ', 'satellite', 'PEPFAR', 'PMTCT', 822, 65, 1038, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'North Sakwa', 24),
								(1045, ' Kwoyo Kodalo Dispensary', '13729 ', 'satellite', 'PEPFAR', 'PMTCT', 822, 65, 1039, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'North Sakwa', 24),
								(1046, 'Midida Dispensary', '17342 ', 'satellite', 'PEPFAR', 'PMTCT', 822, 53, 1040, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'West Kanyamkago', 35),
								(1047, ' Nyakuru Dispensary', '13885 ', 'satellite', 'PEPFAR', 'PMTCT', 822, 65, 1041, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'North Sakwa', 24),
								(1048, 'Oyani (SDA) Dispensary', ' 14008 ', 'satellite', 'PEPFAR', 'PMTCT', 895, 53, 1042, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'West Kanyamkago', 12),
								(1049, ' Piny Owacho Dispensary', '17343 ', 'satellite', 'PEPFAR', 'PMTCT', 822, 53, 1043, 189, 0, 'public', 1, 27, NULL, 'Level 2', '', 35),
								(1050, ' Dede Dispensary', '13532 ', 'satellite', 'PEPFAR', 'PMTCT', 822, 65, 1044, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'West Sakwa', 24),
								(1051, 'Jevros Clinic', '13635', 'satellite', 'PEPFAR', 'PMTCT', 822, 65, 1045, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'North Sakwa', 41),
								(1052, 'Mariwa Health Centre', '13778 ', 'satellite', 'PEPFAR', 'PMTCT', 822, 65, 1046, 510, 0, 'public', 1, 27, NULL, 'Level 3', 'South Sakwa', 24),
								(1053, 'Otacho Dispensary', ' 13998 ', 'satellite', 'PEPFAR', 'PMTCT', 822, 65, 1047, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'Central Sakwa', 24),
								(1054, 'Mikindani Catholic Dispensary', '11614', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 175, 1048, NULL, 0, 'mission', 0, 28, NULL, 'Level 2', 'Mikindani', 19),
								(1055, 'Rabondo Dispensary', '14019', 'satellite', 'PEPFAR', 'PMTCT', 822, 53, 1049, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'West Sakwa', 24),
								(1056, 'Ranen (SDA) Dispensary', '14032', 'satellite', 'PEPFAR', 'PMTCT', 822, 65, 1050, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'North Sakwa', 12),
								(1057, ' Rapcom Nursing and Maternity Home', '14037', 'satellite', 'PEPFAR', 'PMTCT', 822, 65, 1051, 189, 0, 'private', 1, 27, NULL, 'Level 3', 'Central Sakwa', 27),
								(1058, 'St Monica Rapogi Health Centre', '14121 ', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 53, 1052, NULL, 1, 'mission', 1, 27, NULL, 'Level 3', 'North Kanyamkago', 19),
								(1059, ' Ulanda Dispensary', '14157 ', 'satellite', 'PEPFAR', 'ART,PMTCT', NULL, 65, 1053, 1063, 0, 'mission', 1, 27, NULL, 'Level 2', 'South Sakwa', 19),
								(1060, 'Uriri Health Centre', '14161 ', 'standalone', 'PEPFAR', 'PMTCT', 822, 53, 1054, NULL, 0, 'public', 0, 27, NULL, 'Level 3', 'Central Kanyamkago', 24),
								(1061, 'Rakwaro/Verna Mission Hospital', '14166', 'satellite', 'PEPFAR', 'ART,PMTCT', 895, 65, 1055, NULL, 0, 'mission', 1, 27, NULL, 'Level 3', 'West Kanyamkago', 19),
								(1062, 'STC Casino, Nairobi Dispensing Point ', '13193', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 169, 1056, 211, 0, 'public', 1, 30, NULL, 'Not Classified', 'City Square', 42),
								(1063, 'St Joseph Migori Mission Hospital Dispensing Point', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 96, 1057, 204, 0, 'mission', 1, 27, NULL, 'Not Classified', 'Central Suna', 2),
								(1064, 'Tabaka Mission Hospital Dispensing Point', '14139', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 92, 1058, 213, 0, 'mission', 1, 16, NULL, 'Not Classified', 'S. M. Chache', 2),
								(1065, 'Randago Dispensary', '17521', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP', NULL, 9, 1059, NULL, 0, 'public', 0, 38, NULL, 'Level 2', 'Randago', 21),
								(1066, 'Turbo Health Centre Dispensing Point', '15753', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 104, 1060, 498, 0, 'public', 1, 44, NULL, 'Not Classified', 'Kaptebee', 3),
								(1067, 'St Elizabeth Lorugum Health Centre', '15089', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 38, 1061, 563, 0, 'mission', 1, 43, NULL, 'Level 3', 'Lorugum', 19),
								(1069, 'Bar Ndege Dispensary', '16784', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 9, 1063, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'North East Ugenya', 24),
								(1070, 'Expiry and Damage Account', '', 'standalone', '', '', NULL, 47, 1064, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(1071, 'Obunga Dispensary ', '13957', 'satellite', 'PEPHAR', 'PMTCT', 864, 11, 1065, 59, 0, 'public', 1, 8, NULL, 'Level 2', 'Kagan East', 24),
								(1072, 'Obwanda Dispensary ', '13958', 'satellite', 'PEPFAR', 'PMTCT', 864, 11, 1066, 59, 0, 'public', 1, 8, NULL, 'Level 2', 'Kagan East', 24),
								(1073, 'Randung'' Dispensary ', '14031', 'satellite', 'PEPFAR', 'PMTCT', 864, 11, 1067, 59, 0, 'public', 1, 8, NULL, 'Level 2', 'West Gem', 24),
								(1074, 'Cheptuiyet Dispensary', '14375', 'satellite', 'PEPFAR', 'PMTCT', 952, 172, 1068, 504, 0, 'mission', 1, 12, NULL, 'Level 2', 'Kebeneti', 32),
								(1075, 'Bangali Dispensary', '11242', 'satellite', 'PEPFAR', 'PMTCT', 889, 173, 1069, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Bangali', 42),
								(1076, 'Rariw Dispensary', '14038', 'satellite', 'PEPFAR', 'PMTCT', 864, 11, 1070, 59, 0, 'public', 1, 8, NULL, 'Level 2', 'Central Gem', 24),
								(1077, 'Nyamasi Dispensary ', '13902', 'satellite', 'PEPHAR', 'PMTCT', 864, 11, 1071, 59, 0, 'public', 1, 8, NULL, 'Level 2', 'West-Kanyada', 24),
								(1078, 'Kapkiam Dispensary', '14715', 'satellite', 'PEPFAR', 'PMTCT', 952, 101, 1072, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Kapsaos', 32),
								(1079, 'Buwa', '17081', 'satellite', 'PEPFAR', 'PMTCT', 889, 173, 1073, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Buwa', 24),
								(1081, 'Daba AIC Dispensary', '11296', 'satellite', 'PEPFAR', 'PMTCT', 889, 178, 1075, NULL, 0, 'mission', 1, 40, NULL, 'Level 2', 'Galole', 48),
								(1082, 'Kenegut Dispensary', '14826', 'satellite', 'PEPFAR', 'PMTCT', 952, 108, 1076, 504, 0, 'mission', 1, 12, NULL, 'Level 2', 'Kenegut', 32),
								(1083, 'Charidende Dispensary (CDF)', '11281', 'satellite', 'PEPFAR', 'PMTCT', 889, 173, 1077, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Charidende', 24),
								(1084, 'Kwamo Dispensary ', '13728', 'satellite', 'PEPFAR', 'PMTCT', 895, 11, 1078, 405, 0, 'private', 1, 8, NULL, 'Not Classified', 'Central Kanyamwa', 36),
								(1085, 'GK Prisons Dispensary (Tana River)', '11398', 'satellite', 'PEPFAR', 'PMTCT', 889, 178, 1079, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Zubaki', 42),
								(1086, 'Kericho Nursing Home', '14834', 'satellite', 'PEPFAR', 'PMTCT', 952, 108, 1080, 504, 0, 'public', 1, 12, NULL, 'Level 4', 'Township', 32),
								(1087, 'Haroresa', ' 17078', 'satellite', 'PEPFAR', 'PMTCT', 889, 178, 1081, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Haroresa', 24),
								(1088, 'Mbalambala Dispensary', '11588', 'satellite', 'PEPFAR', 'PMTCT', 889, 173, 1082, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Bangali', 42),
								(1089, 'Ketepa Dispensary', '14842', 'satellite', 'PEPFAR', 'PMTCT', 952, 108, 1083, 504, 0, 'private', 1, 12, NULL, 'Level 2', 'Kipkigwere', 32),
								(1090, 'Kag-Sombo Medical Clinic', '11449', 'satellite', 'PEPFAR', 'PMTCT', 889, 173, 1084, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Sombo', 24),
								(1091, 'Nanighi Dispensary (Tana River)', '11699', 'satellite', 'PEPFAR', 'PMTCT', 889, 173, 1085, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Nanighi', 42),
								(1092, 'Lambwe Forest Dispensary ', '13732', 'satellite', 'PEPFAR', 'PMTCT', 895, 11, 1086, 405, 0, 'public', 1, 8, NULL, 'Level 2', 'South Kanyamwa', 24),
								(1093, 'Kipchimchim Mission Hospital', '14890', 'satellite', 'PEPFAR', 'PMTCT', 952, 108, 1087, 504, 0, 'public', 1, 12, NULL, 'Level 4', 'Ainamoi', 32),
								(1094, 'Marynoll Dispensary', '11574', 'satellite', 'PEPFAR', 'PMTCT', 889, 173, 1088, NULL, 0, 'public', 1, 40, NULL, 'Not Classified', 'Bura', 24),
								(1095, 'Meti', '17079', 'satellite', 'PEPFAR', 'PMTCT', 889, 173, 1089, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Meti', 24),
								(1096, 'Sombo Dispensary', '11804', 'satellite', 'PEPFAR', 'PMTCT', 889, 173, 1090, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Sombo', 42),
								(1097, 'Kipsitet Dispensary', '14919', 'satellite', 'PEPFAR', 'PMTCT', 952, 108, 1091, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Soin', 32),
								(1098, 'Mlanjo Dispensary', '11635', 'satellite', 'PEPFAR', 'PMTCT', 889, 173, 1092, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Mlanjo', 24),
								(1099, 'Titila (AIC) Dispensary', '11852', 'satellite', 'PEPFAR', 'PMTCT', 889, 178, 1093, NULL, 0, 'mission', 1, 40, NULL, 'Not Classified', 'Titila', 12),
								(1100, 'Manyatta (SDA) Dispensary', '13769', 'satellite', '', 'PMTCT', 864, 11, 1094, 59, 0, 'mission', 1, 8, NULL, 'Level 2', 'East Kagan', 12),
								(1101, 'Roka', '17077', 'satellite', 'PEPFAR', 'PMTCT', 889, 173, 1095, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Roka', 24),
								(1102, 'Kabuti Matiret', '14633', 'satellite', 'PEPFAR', 'PMTCT', 952, 172, 1096, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Kaplelartet', 32),
								(1103, 'Wayu Boru', '17080', 'satellite', 'PEPFAR', 'PMTCT', 889, 178, 1097, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Wayu', 24),
								(1104, 'Kakiptui Dispensary', '14653', 'satellite', 'PEPFAR', 'PMTCT', 952, 172, 1098, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Kapsuser', 32),
								(1105, 'Bangali Nomadic', '17074', 'satellite', 'PEPFAR', 'PMTCT', 889, 173, 1099, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Bangali', 24),
								(1106, 'Maram Dispensary', '16258', 'satellite', 'PEPFAR', 'PMTCT', 895, 11, 1100, 405, 0, 'public', 1, 8, NULL, 'Level 2', 'Central Kabuoch', 24),
								(1107, 'Waldena Dispensary', '11889', 'satellite', 'PEPFAR', 'PMTCT', 889, 178, 1101, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Waldena', 25),
								(1108, 'Chewele Dispensary', '11284', 'satellite', 'PEPFAR', 'PMTCT', 889, 173, 1102, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Chewele', 24),
								(1109, 'Kamawoi Dispensary', '14671', 'satellite', 'PEPFAR', 'PMTCT', 952, 172, 1103, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Chemamul', 32),
								(1110, 'Wayu Dispensary', '11900', 'satellite', 'PEPFAR', 'PMTCT', 889, 173, 1104, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Wayu', 24),
								(1111, 'Ngegu Dispensary ', '13849', 'satellite', 'PEPFAR', 'PMTCT', 864, 11, 1105, 59, 0, 'public', 1, 8, NULL, 'Level 2', 'West Kochia ', 24),
								(1112, 'Kaitui Dispensary', '14649', 'satellite', 'PEPFAR', 'PMTCT', 952, 108, 1106, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Kaitui', 32),
								(1113, 'Kapkoros Dispensary', '14727', 'satellite', 'PEPFAR', 'PMTCT', 952, 172, 1107, 504, 0, 'mission', 1, 12, NULL, 'Level 2', 'Chaik', 32),
								(1114, 'Al-Faruq Dispensary', '13275', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1108, 557, 0, 'mission', 1, 7, NULL, 'Level 2', 'Waberi', 51),
								(1115, 'Osano Nursing Home', '13995', 'satellite', 'PEPFAR', 'PMTCT', 895, 208, 1109, 405, 0, 'private', 1, 8, NULL, 'Not Classified', 'West Kanyamwa', 27),
								(1116, 'Balich Dispensary', '13299', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1110, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Balich', 24),
								(1117, 'Kaisugu Dispensary', '14647', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1111, 231, 0, 'private', 1, 12, NULL, 'Level 2', 'Cheboswa', 32),
								(1118, 'Bashal Islamic Community Health Initiative ', '17855', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1112, 557, 0, 'mission', 1, 7, NULL, 'Level 2', 'Waberi', 51),
								(1119, 'Bour-algy Dispensary', '13311', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1113, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Bour-algy', 24),
								(1120, 'Kapkurer Dispensary (Kericho)', '14730', 'satellite', 'PEPFAR', 'PMTCT', 952, 108, 1114, 231, 0, 'public', 1, 12, NULL, 'Level 2', 'Ainamoi', 24),
								(1121, 'Shimbrey Dispensary', '13443', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1115, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Shimbrey', 24),
								(1122, 'Daley Dispensary', '13319', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1116, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Daley', 24),
								(1123, 'Roadblock Clinic', '14056', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 208, 1117, 405, 0, 'private', 1, 8, NULL, 'Not Classified', 'West Kanyamwa', 27),
								(1124, 'Saka Health Centre', '13430', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1118, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Saka', 24),
								(1125, 'Kebeneti Dispensary', '14823', 'satellite', 'PEPFAR', 'PMTCT', 952, 172, 1119, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Kebeneti', 24),
								(1126, 'Danyere Health Centre', '13324', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1120, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Danyere', 24),
								(1127, 'Dujis Health Centre', '13331', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1121, 557, 0, 'private', 1, 7, NULL, 'Level 2', 'Dujis', 24),
								(1128, 'Raya Dispensary', '13422', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1122, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Raya\\', 12),
								(1129, 'Wiga Dispensary', '14172', 'satellite', 'PEPFAR', 'ART,PMTCT', 864, 11, 1123, 59, 0, 'public', 1, 8, NULL, 'Not Classified', 'Kanyada West', 24),
								(1130, 'Bora Bora Clinic', '13509', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 208, 1124, 405, 0, 'private', 1, 8, NULL, 'Not Classified', 'West Kanyamwa', 27),
								(1131, 'GK Prison Dispensary (Garissa)', '13350', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1125, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Central', 24),
								(1132, 'Korakora Health Centre', '13382', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1126, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Korakora', 24),
								(1133, 'Libahlow Nomadic Clinic', ' 16288', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1127, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Libahlow', 24),
								(1134, 'Medina Health Centre', '13408', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1128, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Galbet', 24),
								(1135, 'Manyoror Dispensary', '15121', 'satellite', 'PEPFAR', 'PMTCT', 952, 108, 1129, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Kapsaos', 32),
								(1136, 'Kericho Municipal Health Centre', '14833', 'satellite', 'PEPFAR', 'PMTCT', 952, 108, 1130, 504, 0, 'mission', 1, 12, NULL, 'Level 3', 'Township', 21),
								(1137, 'Chepkemel Health Centre (Kericho)', '14338', 'satellite', 'PEPFAR', 'PMTCT', 952, 172, 1131, 504, 0, 'public', 1, 12, NULL, 'Level 3', 'Kaplelartet', 24),
								(1138, 'Thessalia Health Centre', '15722', 'satellite', 'PEPFAR', 'PMTCT', 952, 108, 1132, 504, 0, 'mission', 1, 12, NULL, 'Level 3', 'Soin', 48),
								(1139, 'Iraa Dispensary', '14578', 'satellite', 'PEPFAR', 'PMTCT', 952, 172, 1133, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Kiptere', 24),
								(1140, 'Seretut Dispensary', '15550', 'satellite', 'PEPFAR', 'PMTCT', 952, 172, 1134, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Seretut', 24),
								(1141, 'St Leonard Hospital', '15649', 'satellite', 'PEPFAR', 'PMTCT', 952, 108, 1135, 504, 0, 'private', 1, 12, NULL, 'Not Classified', 'Township', 27),
								(1142, 'Siloam Hospital', '15571', 'satellite', 'PEPFAR', 'PMTCT', 952, 108, 1136, 504, 0, 'private', 1, 12, NULL, 'Level 4', 'Township', 24),
								(1143, 'Chagaik Dispensary', '16475', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1137, 231, 0, 'public', 1, 12, NULL, 'Level 2', 'Kaisugu', 38),
								(1144, 'Saramek Dispensary', '15531', 'satellite', 'PEPFAR', 'PMTCT', 952, 172, 1138, 231, 0, 'public', 1, 12, NULL, 'Not Classified', 'Chaik', 27),
								(1145, 'St Francis Tinga Health Centre(Kipkelion)', '15640', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1139, 510, 0, 'public', 1, 12, NULL, 'Not Classified', 'Kamasian', 19),
								(1146, 'Chepkunyuk Dispensary', '14351', 'satellite', 'PEPFAR', 'PMTCT', 1363, 115, 1140, 510, 0, 'public', 1, 32, NULL, 'Level 2', 'Chepkunyuk', 32),
								(1147, 'Jagoror Dispensary', '14588', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1141, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Kipsirichet', 32),
								(1148, 'Chepchabas Dispensary', '14331', 'satellite', 'PEPFAR', 'PMTCT', 952, 190, 1142, 487, 0, 'mission', 1, 2, NULL, 'Level 2', 'Saosa', 32),
								(1149, 'Kamwingi Dispensary', '14679', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1143, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Sorget', 32),
								(1150, 'Kapseger Dispensary', '14755', 'satellite', 'PEPFAR', 'PMTCT', 1701, 110, 1144, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Kapseger', 32),
								(1151, 'Chepwostuiyet Dispensary', '14376', 'satellite', 'PEPFAR', 'PMTCT', 952, 176, 1145, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Chepwostuiyet', 32),
								(1152, 'Chepseon Dispensary', '14359', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1146, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Chepseon', 32),
								(1153, 'Chebango Dispensary', '14290', 'satellite', 'PEPFAR', 'PMTCT', 952, 137, 1147, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Chebole', 32),
								(1154, 'Chepcholiet Dispensary', '14333', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1148, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Kapseger', 32),
								(1155, 'Barsiele Dispensary', '14238', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1149, 510, 0, 'public', 1, 12, NULL, 'Not Classified', 'Barsiele', 27),
								(1156, 'Bethel Faith Dispensary', '14249', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1150, 510, 0, 'private', 1, 12, NULL, 'Level 2', 'Kipteris', 24),
								(1157, 'Chebewor Dispensary', '14293', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1151, 510, 0, 'public', 1, 12, NULL, 'Not Classified', 'Kedowa', 24),
								(1158, 'Gelegele Dispensary', '14505', 'satellite', 'PEPFAR', 'PMTCT', 952, 137, 1152, 487, 0, 'private', 1, 2, NULL, 'Level 2', 'Gelegele', 32),
								(1159, 'Chepseon Health Care Clinic', '14360', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1153, 510, 0, 'public', 1, 12, NULL, 'Not Classified', 'Chepseon', 27),
								(1160, 'Chepseon Medical Clinic', '14361', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1154, 510, 0, 'private', 1, 12, NULL, 'Level 2', 'Chepseon', 27),
								(1161, 'Ashabito Health Centre', '13294', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1155, NULL, 0, 'public', 1, 24, NULL, 'Level 3', 'Ashabito', 24),
								(1162, 'Cherara Dispensary', '14381', 'satellite', 'PEPFAR', 'PMTCT', 895, 110, 1156, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Kokwet', 24),
								(1163, 'Buchenge Dispensary', '14271', 'satellite', 'PEPFAR', 'PMTCT,PEP,LAB,RTK', 1728, 110, 1157, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Ainamoi', 36),
								(1164, 'Gorgor Dispensary', '14531', 'satellite', 'PEPFAR', 'PMTCT', 952, 137, 1158, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Abosi', 32),
								(1165, 'Bore Hole 11 Health Centre', '13310', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1159, NULL, 0, 'mission', 1, 24, NULL, 'Level 3', 'Bore Hole 11', 48),
								(1166, 'Kalyet Clinic (Kipkelion)', '14665', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1160, 510, 0, 'private', 1, 12, NULL, 'Level 2', 'Lemotit', 32),
								(1167, 'Chesoen Dispensary', '14384', 'satellite', 'PEPFAR', 'PMTCT', 952, 102, 1161, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Chesoen', 32),
								(1168, 'Kapkwen Dispensary', '17756', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1162, 510, 0, 'private', 1, 12, NULL, 'Level 2', 'Machiesok', 24),
								(1169, 'Itembe Dispensary', '14585', 'satellite', 'PEPFAR', 'PMTCT', 952, 176, 1163, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Itembe', 32),
								(1170, 'Kericho Forest Dispensary', '14832', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1164, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Cheboswa', 24),
								(1171, 'Kapkimolwa Dispensary', '14717', 'satellite', 'PEPFAR', 'PMTCT', 952, 102, 1165, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Kapkimolwa', 32),
								(1172, 'Kimugu Dispensary', '18129', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 110, 1166, NULL, 0, 'mission', 1, 12, NULL, 'Level 2', 'Cheboswa', 38),
								(1173, 'Kapkesosio Dispensary', '14714', 'satellite', 'PEPFAR', 'PMTCT', 952, 176, 1167, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Kapkesosio ', 32),
								(1174, 'Elele Nomadic Clinic', '16443', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1168, NULL, 0, 'public', 1, 24, NULL, 'Level 2', 'Elele', 24),
								(1175, 'Kimugul Dispensary (Kipkelion)', '14882', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1169, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Kimugul', 24),
								(1176, 'Cheplanget Dispensary', '14353', 'satellite', 'PEPFAR', 'PMTCT', NULL, 174, 1170, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Cheplanget', 32),
								(1177, 'Kipkelion (CHFC) Dispensary', '14896', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1171, 510, 0, 'mission', 1, 12, NULL, 'Level 2', 'Kipchorian', 27),
								(1178, 'Kipsegi Dispensary', '14913', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1172, 510, 0, 'mission', 1, 12, NULL, 'Level 2', 'Kipsegi', 24),
								(1179, 'Kunyak Dispensary', '15001', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1173, 510, 0, 'mission', 1, 12, NULL, 'Level 2', 'Kapkoros', 24),
								(1180, 'Cheptabes Dispensary', '14365', 'satellite', 'PEPFAR', 'PMTCT', 952, 190, 1174, 501, 0, 'private', 1, 2, NULL, 'Level 2', 'Saosa', 32),
								(1181, 'Fincharo Dispensary', '13341', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1175, NULL, 0, 'public', 1, 24, NULL, 'Level 2', 'Fincharo', 24),
								(1182, 'Lelechwet Dispensary (Kipkelion)', '15020', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1176, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Kamasian', 24),
								(1183, 'Garsesala Dispensary', '16444', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1177, NULL, 0, 'public', 1, 24, NULL, 'Level 2', 'Garsesala', 24),
								(1184, 'Lelu Dispensary', '15023', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1178, 510, 0, 'ngo', 1, 12, NULL, 'Level 2', 'Borowet', 24),
								(1185, 'Girissa Dispensary', '13348', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1179, NULL, 0, 'public', 1, 24, NULL, 'Level 2', 'Girissa', 24),
								(1186, 'Kotulo Health Centre (Mandera Central)', '13388', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1180, NULL, 0, 'public', 1, 24, NULL, 'Level 3', 'Kotulo', 24),
								(1187, 'Shimbir Fatuma Health Centre', '13440', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1181, NULL, 0, 'public', 1, 24, NULL, 'Level 3', 'Shimbir', 24),
								(1188, 'Makyolok Dispensary', '15118', 'satellite', 'PEPFAR', 'PMTCT', 895, 110, 1182, 510, 0, 'mission', 1, 12, NULL, 'Level 2', 'Toroton', 24),
								(1189, 'Mariwa Dispesary (Kipkelion)', '15140', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1183, 510, 0, 'mission', 1, 12, NULL, 'Level 2', 'Kunyak', 24),
								(1190, 'Kaboeito Dispensary', '14625', 'satellite', 'PEPFAR', 'PMTCT', NULL, 174, 1184, 491, 0, 'mission', 1, 2, NULL, 'Level 2', 'Kisiara', 32),
								(1191, 'Mary Finch Dispensary', '15146', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1185, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Londiani', 48),
								(1192, 'Masaita/ Miti-Tatu Dispensary', '15149', 'satellite', 'PEPFAR', 'PMTCT', 1728, 110, 1186, 510, 0, 'ngo', 1, 12, NULL, 'Level 2', 'Masaita', 24),
								(1193, 'Mercy Mobile Clinic (Kipkelion)', '15175', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1187, 510, 0, 'private', 1, 12, NULL, 'Level 2', 'Kipchorian', 19),
								(1194, 'Mugumoini Dispensary (Kipkelion)', '15245', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1188, 510, 0, 'private', 1, 12, NULL, 'Level 2', 'Tendeno', 24),
								(1195, 'Ngendalel Dispensary', '15343', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1189, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Lesirwa', 24),
								(1196, 'Sereng Dispensary', '15546', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1190, 510, 0, 'mission', 1, 12, NULL, 'Level 2', 'Siwot', 24),
								(1197, 'Subukia Dispensary( Kipkelion )', '15677', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1191, 510, 0, 'mission', 1, 12, NULL, 'Level 2', 'Tendeno', 24),
								(1198, 'Wargadud Health Centre', '13455', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1192, NULL, 0, 'public', 1, 24, NULL, 'Level 3', 'Wargadud', 24),
								(1200, 'Bura Nomadic', '17075', 'satellite', 'PEPFAR', 'PMTCT', 889, 173, 1194, NULL, 0, 'public', 1, 40, NULL, 'Level 2', 'Bura Manyata', 42),
								(1201, 'Kapsinendet Dispensary', '14760', 'satellite', 'PEPFAR', 'PMTCT', 952, 190, 1195, 491, 0, 'mission', 1, 2, NULL, 'Level 2', 'Kimulot', 32),
								(1202, 'Luciel Dispensary ', '13736', 'satellite', 'PEPFAR', 'PMTCT', 895, 211, 1196, 112, 0, 'public', 1, 27, NULL, 'Not Classified', 'S.E.Karungu', 24),
								(1203, 'Yabicho Health Centre', '13456', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1197, NULL, 0, 'public', 1, 24, NULL, 'Not Classified', 'Yabicho', 24),
								(1204, 'AP Buru Buru Dispensary', '17649', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1198, NULL, 0, 'public', 1, 24, NULL, 'Level 2', 'Township', 24),
								(1205, 'Bar Aluru Dispensary (Rarieda)', '13497', 'satellite', 'PEPFAR', 'PMTCT', 864, 98, 1199, 450, 0, 'public', 1, 38, NULL, 'Level 2', 'West Asembo', 24),
								(1206, 'Kenene Dispensary', '14827', 'satellite', 'PEPFAR', 'PMTCT', 1363, 174, 1200, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Cheplanget', 32),
								(1207, 'Kaptebengwo Dispensary', '14777', 'satellite', 'PEPFAR', 'PMTCT', 1363, 137, 1201, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Rongena', 32),
								(1208, 'Arabia Health Center', '13290', 'satellite', 'PEPFAR', 'PMTCT', 828, 150, 1202, NULL, 0, 'mission', 1, 24, NULL, 'Level 3', 'Arabia', 48),
								(1209, 'Riat Dispensary  (Migori)', '14047', 'satellite', 'PEPFAR', 'PMTCT', 895, 211, 1203, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'Central Karungu', 40),
								(1210, 'Itare Dispensary', '14584', 'satellite', 'PEPFAR', 'PMTCT', 952, 190, 1204, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Kaptebengwet', 32),
								(1211, 'Nyamanga Dispensary', '13896', 'satellite', 'PEPFAR', 'PMTCT', 895, 211, 1205, 112, 0, 'public', 1, 27, NULL, 'Level 2', 'West Karungu', 40),
								(1212, 'Kipsingei Dispensary', '14918', 'satellite', 'PEPFAR', 'PMTCT', 1363, 137, 1206, 491, 0, 'mission', 1, 2, NULL, 'Level 2', 'Kipsingei', 32),
								(1213, 'Burabor Dispensary', '17870', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1207, NULL, 0, 'mission', 1, 24, NULL, 'Level 2', 'Burabor', 48),
								(1214, 'Buruburu Dispensary', '16313', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1208, NULL, 0, 'mission', 1, 24, NULL, 'Level 2', 'Township', 48),
								(1215, 'Port Victoria Hospital dispensing point', '16091', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 125, 1209, 549, 0, 'public', 1, 4, NULL, 'Level 3', 'Bunyala West', 3),
								(1216, 'Fino Health Center', '13340', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1210, NULL, 0, 'mission', 1, 24, NULL, 'Level 3', 'Fino', 48),
								(1217, 'Hareri Dispensary', ' 13362', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1211, NULL, 0, 'public', 1, 24, NULL, 'Level 2', 'Hareri', 24),
								(1218, 'Kadija Dispensary', '17648', 'satellite', 'PEPFAR', 'PMTCT', 828, 150, 1212, NULL, 0, 'private', 1, 24, NULL, 'Level 2', 'Bula mpya', 27),
								(1219, 'Macalder Mission Dispensary', '13744', 'satellite', 'PEPFAR', 'PMTCT,PEP,LAB,RTK', NULL, 96, 1213, 112, 0, 'mission', 1, 27, NULL, 'Not Classified', 'S.E. Kadem', 40),
								(1220, 'Khalalio Health Center', '13379', 'satellite', 'PEPFAR', 'PMTCT', 828, 150, 1214, NULL, 0, 'public', 1, 24, NULL, 'Level 3', 'Khalalio', 24),
								(1221, 'Shafshafey Health Center', '17647', 'satellite', 'PEPFAR', 'PMTCT', 828, 150, 1215, NULL, 0, 'public', 1, 24, NULL, 'Level 3', 'Shafshafey', 24),
								(1222, 'Monieri Medical Centre', '15220', 'satellite', 'PEPFAR', 'PMTCT', 952, 137, 1216, 491, 0, 'mission', 1, 2, NULL, 'Level 2', 'Manaret', 32),
								(1223, 'Rongena Dispensary', '15497', 'satellite', 'PEPFAR', 'PMTCT', 952, 137, 1217, 491, 0, 'mission', 1, 2, NULL, 'Level 2', 'Rongena', 32),
								(1224, 'Burgei Dispensary', '14273', 'satellite', 'PEPFAR', 'PMTCT', 952, 137, 1218, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Tembwo', 32),
								(1225, 'Libehiya Dispensary', '13396', 'satellite', 'PEPFAR', 'PMTCT', 828, 150, 1219, NULL, 0, 'public', 1, 24, NULL, 'Level 3', 'Libehiya ', 24),
								(1226, 'Chebitet Medical center', '14298', 'satellite', 'PEPFAR', 'PMTCT', 813, 190, 1220, 491, 0, 'private', 1, 2, NULL, 'Not Classified', 'Chebitet', 32),
								(1227, 'Saruchat Dispensary', '15532', 'satellite', 'PEPFAR', 'PMTCT', 1363, 137, 1221, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Tembwo', 0),
								(1228, 'Simbi Dispensary', '15575', 'satellite', 'PEPFAR', 'PMTCT', 1363, 137, 1222, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Manaret', 0),
								(1229, 'Soymet Dispensary', '15624', 'satellite', 'PEPFAR', 'PMTCT', 1363, 137, 1223, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Yaganek', 0),
								(1230, 'Alinjugur Health centre', '13282', 'satellite', 'PEPFAR', 'PMTCT', 828, 156, 1224, 557, 0, 'public', 1, 7, NULL, 'Level 3', 'Alinjugur', 42),
								(1231, 'Amuma Mobile Dispensary', '13289', 'satellite', 'PEPFAR', 'PMTCT', 828, 156, 1225, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Amuma', 24),
								(1232, 'Amuma Dispensary', '13288', 'satellite', 'PEPFAR', 'PMTCT', 828, 156, 1226, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Amuma', 24),
								(1233, 'Butiik Dispensary', '14276', 'satellite', 'PEPFAR', 'PMTCT', NULL, 174, 1227, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Cheplanget', 24),
								(1234, 'Borehole Five Dispensary', '17762', 'satellite', 'PEPFAR', 'PMTCT', 828, 156, 1228, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Alinjugur', 24),
								(1235, 'Chamalal Dispensary', '14285', 'satellite', 'PEPFAR', 'PMTCT', 952, 190, 1229, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Kimulot', 24),
								(1236, 'Fafi Dispensary', '13338', 'satellite', 'PEPFAR', 'PMTCT', 828, 156, 1230, 557, 0, 'mission', 1, 7, NULL, 'Level 2', 'Fafi', 48),
								(1237, 'Galmagalla Health Centre', ' 13342', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1231, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Galmagalla', 24),
								(1238, 'Kamongil Dispensary', '14676', 'satellite', 'PEPFAR', 'PMTCT', 952, 176, 1232, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Kapmongil', 32),
								(1239, 'Changoi Dispensary', '14287', 'satellite', 'PEPFAR', 'PMTCT', 952, 190, 1233, 491, 0, 'private', 1, 2, NULL, 'Level 3', 'Saosa', 38),
								(1240, 'Kataret Dispensary', '14815', 'satellite', 'PEPFAR', 'PMTCT', 952, 176, 1234, 487, 0, 'public', 1, 2, NULL, 'Not Classified', 'Kataret', 32),
								(1241, 'Chemoiben Dispensary', '14320', 'satellite', 'PEPFAR', 'PMTCT', NULL, 174, 1235, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Kapkatet', 24),
								(1242, 'Kiptulwa Dispensary', '14932', 'satellite', 'PEPFAR', 'PMTCT', 952, 137, 1236, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Kiptulwa', 32),
								(1243, 'Kamuthe Health Centre', '13378', 'satellite', 'PEPFAR', 'PMTCT', 828, 156, 1237, 557, 0, 'public', 1, 7, NULL, 'Level 3', 'Kamuthe', 24),
								(1244, 'Mansabubu Health Centre', '13405', 'satellite', 'PEPFAR', 'PMTCT', 828, 156, 1238, 557, 0, 'public', 1, 7, NULL, 'Level 3', 'Mansabubu', 24),
								(1245, 'Kapoleseroi Dispensary', '14747', 'satellite', 'PEPFAR', 'PMTCT', 952, 176, 1239, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Kapoleseroi', 32),
								(1246, 'Kabitungu Dispensary', '14622', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 174, 1240, 491, 0, 'public', 1, 12, NULL, 'Level 2', 'Chemoiywo', 24),
								(1247, 'Nanighi Health Centre', '13413', 'satellite', 'PEPFAR', 'PMTCT', 889, 156, 1241, NULL, 0, 'public', 1, 7, NULL, 'Level 3', 'Nanighi', 24),
								(1248, 'Kalaacha Dispensary', '14657', 'satellite', 'PEPFAR', 'PMTCT', NULL, 174, 1242, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Kapkatet', 24),
								(1249, 'Kapsimotwa Dispensary ', '14759', 'satellite', 'PEPFAR', 'PMTCT', 952, 101, 1243, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Kapsimotwa', 24),
								(1250, 'Yumbis Dispensary', '13459', 'satellite', 'PEPFAR', 'PMTCT', 828, 156, 1244, 557, 0, 'public', 1, 7, NULL, 'Level 3', 'Yumbis', 24),
								(1251, 'Kacheliba District Hospital', '14634', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 40, 1245, 76, 0, 'public', 1, 47, NULL, 'Level 3', 'Suam', 7),
								(1252, 'Kapkatet Hospital VCT', '17656', 'satellite', 'PEPFAR', 'PMTCT', 1363, 174, 1246, 491, 0, 'public', 1, 2, NULL, 'Not Classified', 'Kapkatet', 24),
								(1253, 'Kapkisiara Dispensary', '14718', 'satellite', 'PEPFAR', 'PMTCT', 1363, 174, 1247, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Kisiara', 24),
								(1254, 'Kembu Dispensary', '17083', 'satellite', 'PEPFAR', 'PMTCT', 952, 102, 1248, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Kembu', 24),
								(1255, 'Kiplelji Dispensary', '14903', 'satellite', 'PEPFAR', 'PMTCT', 952, 102, 1249, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Chesoen', 24),
								(1256, 'Kapset Dispensary', '14757', 'satellite', 'PEPFAR', 'PMTCT', 952, 190, 1250, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Kapset', 24),
								(1257, 'Kiromwok Dispensary', '14939', 'satellite', 'PEPFAR', 'PMTCT', 952, 102, 1251, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Kiramwok', 24),
								(1258, 'Lugumek Dispensary', '15100', 'satellite', 'PEPFAR', 'PMTCT', 952, 176, 1252, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Lugumek', 24),
								(1259, 'Mugango Dispensary', '15244', 'satellite', 'PEPFAR', 'PMTCT', 952, 102, 1253, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Mugango', 24),
								(1260, 'Ndamichonik Dispensary', '15321', 'satellite', 'PEPFAR', 'PMTCT', 952, 176, 1254, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Kanusin', 24),
								(1261, 'Kapsogut Dispensary', '14764', 'satellite', 'PEPFAR', 'PMTCT', 1363, 174, 1255, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Kapsogut', 24),
								(1262, 'Sachora Dispensary', '17092', 'satellite', 'PEPFAR', 'PMTCT', 952, 176, 1256, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Kyogong ', 24),
								(1263, 'Kaptembwo Dispensary', '14780', 'satellite', 'PEPFAR', 'PMTCT', 1363, 190, 1257, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Boito', 24),
								(1264, 'Singorwet Dispensary', '15583', 'satellite', 'PEPFAR', 'PMTCT', 952, 102, 1258, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Singorwet', 24),
								(1265, 'Alikune Dispensary', '17012', 'satellite', 'PEPFAR', 'PMTCT', 828, 289, 1259, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Dadaab`', 24),
								(1266, 'Chelelach Dispensary', '14308', 'satellite', 'PEPFAR', 'PMTCT', 952, 176, 1260, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Chelelach', 24),
								(1267, 'Baraki Dispensary', '16289', 'satellite', 'PEPFAR', 'PMTCT', 828, 192, 1261, NULL, 0, 'public', 1, 7, NULL, 'Level 2', 'Shantabaq', 24),
								(1268, 'Kapkures Dispensary (Sotik)', '16318', 'satellite', 'PEPFAR', 'PMTCT', 952, 137, 1262, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Kapkures', 24),
								(1269, 'Benane Health Centre', '13305', 'satellite', 'PEPFAR', 'PMTCT', NULL, 192, 1263, NULL, 0, 'public', 1, 7, NULL, 'Level 3', 'Benane', 24),
								(1270, 'Dadaab Clinic', '13315', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1264, 557, 0, 'public', 1, 7, NULL, 'Not Classified', 'Dadaab', 24),
								(1271, 'Kiptenden Dispensary (Buret)', '14927 ', 'satellite', 'PEPFAR', 'PMTCT', 1363, 190, 1265, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Kiptenden', 24),
								(1272, 'Kumahumato Dispensary', '16952', 'satellite', 'PEPFAR', 'PMTCT', 828, 192, 1266, 557, 0, 'ngo', 1, 7, NULL, 'Level 2', 'Kumahumato', 24),
								(1273, 'Dal-Lahelay Mobile Clinic', '16809', 'satellite', 'PEPFAR', 'PMTCT', 828, 192, 1267, NULL, 0, 'public', 1, 7, NULL, 'Level 2', 'Maalimin', 24),
								(1274, 'Liboi Clinic', '13397', 'satellite', 'PEPFAR', 'PMTCT', 828, 192, 1268, 607, 0, 'private', 1, 7, NULL, 'Not Classified', 'Liboi', 27),
								(1275, 'Kiptewit Dispensary', '14929', 'satellite', 'PEPFAR', 'PMTCT', 1363, 174, 1269, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Cheborgei', 24),
								(1276, 'Damajale Dispensary', '13320', 'satellite', 'PEPFAR', 'PMTCT', 828, 289, 1270, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Damajale', 24),
								(1277, 'Dertu Health Centre', '13327', 'satellite', 'PEPFAR', 'PMTCT', 828, 289, 1271, 557, 0, 'public', 1, 7, NULL, 'Level 3', 'Dertu', 24),
								(1278, 'Kiptome Dispensary', '14930', 'satellite', 'PEPFAR', 'PMTCT', 1363, 174, 1272, 491, 0, 'public', 1, 2, NULL, 'Not Classified', 'kapkisiara', 24),
								(1279, 'Eldere Dispensary', '16290', 'satellite', 'PEPFAR', 'PMTCT', NULL, 192, 1273, NULL, 0, 'public', 1, 7, NULL, 'Level 2', 'Benane', 24),
								(1280, 'Gurufa Dispensary', '13356', 'satellite', 'PEPFAR', 'PMTCT', 828, 192, 1274, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Gurufa', 24),
								(1281, 'Litein Dispensary', '15039', 'satellite', 'PEPFAR', 'PMTCT', 1363, 174, 1275, 491, 0, 'public', 1, 12, NULL, 'Level 2', 'Litein', 24),
								(1282, 'Mabasi Dispensary', '15105', 'satellite', 'PEPFAR', 'PMTCT', 1363, 174, 1276, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Kisiara', 24),
								(1283, 'Hagarbul Dispensary', '17336', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1277, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Dertu', 24),
								(1284, 'Liboi Health Centre', '13398', 'satellite', 'PEPFAR', 'PMTCT', 828, 289, 1278, NULL, 0, 'mission', 1, 7, NULL, 'Level 3', 'Liboi', 27),
								(1285, 'Maramara Dispensary', '15127', 'satellite', 'PEPFAR', 'PMTCT', 952, 190, 1279, 491, 0, 'private', 1, 2, NULL, 'Level 2', 'Maramara', 27),
								(1286, 'Maalimin Dispensary', '17339', 'satellite', 'PEPFAR', 'PMTCT', 813, 192, 1280, 607, 0, 'mission', 1, 7, NULL, 'Level 2', 'Maalimin', 24),
								(1287, 'Kulan Health Centre', '13386', 'satellite', 'PEPFAR', 'PMTCT', 828, 289, 1281, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Liboi', 24),
								(1288, 'Roret Medical Clinic', '15499', 'satellite', 'PEPFAR', 'PMTCT', NULL, 174, 1282, 491, 0, 'private', 1, 2, NULL, 'Not Classified', 'Kisiara', 27),
								(1289, 'Shantaabaq Health Centre (Lagdera)', '13438', 'satellite', 'PEPFAR', 'PMTCT', 828, 192, 1283, 557, 0, 'public', 1, 7, NULL, 'Level 3', 'Shantabaq', 24),
								(1290, 'Modogashe Clinic ', '13410', 'satellite', 'PEPFAR', 'PMTCT', 828, 192, 1284, 607, 0, 'public', 1, 7, NULL, 'Not Classified', 'Modogashe', 27),
								(1291, 'Saretho Health Centre', '13434', 'satellite', 'PEPFAR', 'PMTCT', 828, 289, 1285, 557, 0, 'public', 1, 7, NULL, 'Level 3', ' Saretho', 24),
								(1292, 'Kangemi Health Centre Dispensing Point', '13001', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 47, 1286, 74, 0, 'public', 1, 30, NULL, 'Not Classified', 'Kangemi', 42),
								(1293, 'Agawo Dispensary ', '13466', 'satellite', 'PEPFAR', 'PMTCT', 895, 35, 1287, 183, 0, 'public', 1, 8, NULL, 'Level 2', 'East Kamagak', 24),
								(1294, 'Atemo Health Centre', '13489', 'satellite', 'PEPFAR', 'PMTCT', 895, 212, 1288, 183, 0, 'public', 1, 8, NULL, 'Level 3', 'Kojwach', 24),
								(1295, 'Kimonge Dispensary ', '13693', 'satellite', 'PEPFAR', 'PMTCT', 895, 212, 1289, 183, 0, 'public', 1, 8, NULL, 'Level 2', 'Kojwach', 24),
								(1296, 'Kauma Dispensary (Rachuonyo)', '13658', 'satellite', 'PEPFAR', 'PMTCT', 895, 212, 1290, 183, 0, 'public', 1, 8, NULL, 'Level 2', 'Ramba', 24),
								(1297, 'Siomo Dispensary', '15585', 'satellite', 'PEPFAR', 'PMTCT', 1363, 190, 1291, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Embomos', 24),
								(1298, 'Mangima SDA Health Centre', '13768', 'satellite', 'PEPFAR', 'PMTCT', 895, 212, 1292, 183, 0, 'mission', 1, 8, NULL, 'Level 3', 'Konuonga', 12),
								(1299, 'Sosit Dispensary', '15618', 'satellite', 'PEPFAR', 'PMTCT', 1363, 174, 1293, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Kapkatet', 24),
								(1300, 'Masogo Dispensary', '13784', 'satellite', 'PEPFAR', 'PMTCT', 895, 212, 1294, 183, 0, 'public', 1, 8, NULL, 'Level 2', 'Ramba', 24),
								(1301, 'Sotit dispensary', '15620', 'satellite', 'PEPFAR', 'PMTCT', 1363, 190, 1295, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Sotit', 24),
								(1302, 'Nyalgosi Dispensary ', '13889', 'satellite', 'PEPFAR', 'PMTCT', 895, 212, 1296, 183, 0, 'public', 1, 8, NULL, 'Level 2', 'North Kachien', 24),
								(1303, 'Tebesonik Dispensary', '15713', 'satellite', 'PEPFAR', 'PMTCT', 1363, 174, 1297, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Tebesonik', 24),
								(1304, 'Tenduet Dispensary', '15717', 'satellite', 'PEPFAR', 'PMTCT', 1363, 101, 1298, 491, 0, 'private', 1, 2, NULL, 'Level 2', 'Konoin', 38),
								(1305, 'Wire Dispensary ', '14174', 'satellite', 'PEPFAR', 'PMTCT', 895, 212, 1299, 183, 0, 'mission', 1, 8, NULL, 'Level 2', 'West Kamagak', 12),
								(1306, 'Satiet Dispensary', '15533', 'satellite', 'PEPFAR', 'PMTCT', 1363, 190, 1300, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Satiet', 24),
								(1307, 'Yalla Dispensary', '17069', 'satellite', 'PEPFAR', 'PMTCT', 895, 212, 1301, 183, 0, 'public', 1, 8, NULL, 'Level 2', 'Kokech', 24),
								(1308, 'Ombek Dispensary ', '13978', 'satellite', 'PEPFAR', 'PMTCT', 895, 212, 1302, 183, 0, 'public', 1, 8, NULL, 'Level 2', 'Kodera ', 24),
								(1309, 'Cheboyo Dispensary', '14302', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 176, 1303, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Cheboyo', 32),
								(1310, 'Kimilili District Hospital Dispensing Point', '15950', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 124, 1304, 98, 0, 'public', 1, 3, NULL, 'Not Classified', 'Kibingei', 8),
								(1311, 'Chebunyo Dispensary', '14304', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 176, 1305, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Chebunyo', 32),
								(1312, 'Chemaner Dispensary (Bomet)', '14311', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 102, 1306, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Chemaner', 32),
								(1313, 'Irwaga Dispensary', '14580', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 102, 1307, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Irwaga', 32),
								(1314, 'Ndalu Health Centre', '16079', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 124, 1308, 548, 0, 'public', 1, 3, NULL, 'Level 3', 'Ndalu', 24),
								(1315, 'Kipsuter Dispensary', '14921', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 102, 1309, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Kipsuter', 32),
								(1316, 'Tongaren Health Centre', '16152', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 138, 1310, 548, 0, 'public', 1, 3, NULL, 'Level 3', 'Tongaren', 24),
								(1317, 'Makimeny Dispensary', '15116', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 176, 1311, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Makimeny', 32),
								(1318, 'Ndarawetta Dispensary.', '15323', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 102, 1312, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Ndarweta', 32),
								(1319, 'Olbutyo Health Centre', '15388', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 176, 1313, 487, 0, 'public', 1, 2, NULL, 'Level 3', 'Kongasis', 32),
								(1320, 'Olokyin Dispensary', '15421', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 102, 1314, 487, 0, 'public', 1, 2, NULL, 'Level 3', 'Kapkimolwo', 32),
								(1321, 'Tegat dispensary', '15714', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 176, 1315, 487, 0, 'public', 1, 2, NULL, 'Level 3', 'Tegat', 32),
								(1322, 'Merigi Dispensary', '15178', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 102, 1316, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Merigi', 32),
								(1323, 'Tumoi Dispensary', '15751', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 176, 1317, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Kapkesosio', 24),
								(1324, ' Nyabondo Mission Hospital Dispensing Point', '13864', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 210, 1318, 203, 0, 'mission', 1, 17, NULL, 'Level 4', 'Oboch', 2),
								(1325, 'Bomet Health Centre', '14261', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 102, 1319, 487, 0, 'public', 1, 2, NULL, 'Level 3', 'Township', 32),
								(1326, 'Holy Family Oriang Mission Dispensary ', '13604', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 212, 1320, NULL, 1, 'mission', 0, 8, NULL, 'Level 2', ' Kawuor', 48),
								(1327, 'Longisa District Hospital Dispensing Point', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 102, 1321, 487, 0, 'public', 1, 2, NULL, 'Level 4', 'Cheboin', 32),
								(1328, 'St Clare Bolo Health Centre', '14104', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 210, 1322, 203, 0, 'mission', 1, 17, NULL, 'Level 3', 'South West', 48),
								(1331, 'AIC Litein Mission Hospital Dispensing Point', '14178', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 174, 1325, 791, 0, 'mission', 1, 2, NULL, 'Not Classified', 'Litein', 32),
								(1332, 'Arroket Dispensary', '14215', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 137, 1326, 791, 0, 'ngo', 1, 2, NULL, 'Not Classified', 'Manaret', 32),
								(1337, 'Kolenyo Dispensary', '17172', 'satellite', 'PEPFAR', 'PMTCT', 895, 29, 1331, 231, 0, 'public', 1, 17, NULL, 'Level 2', 'South Central Seme', 24),
								(1338, 'Asat Beach Dispensary', '13487', 'satellite', 'PEPFAR', 'PMTCT', 895, 29, 1332, 231, 0, 'public', 1, 17, NULL, 'Level 2', 'South Central Seme', 24),
								(1339, 'Bar Korwa Dispensary', '13498', 'satellite', 'PEPFAR', 'PMTCT', 895, 29, 1333, 231, 0, 'mission', 1, 17, NULL, 'Level 2', 'North Central Seme', 19),
								(1340, 'Kuoyo Kaila Dispensary', '17171', 'satellite', 'PEPFAR', 'PMTCT', 813, 29, 1334, 231, 0, 'public', 1, 17, NULL, 'Level 2', 'East Seme', 24),
								(1341, 'Langi Kawino Dispensary', '18250', 'satellite', 'PEPFAR', 'PMTCT', 895, 29, 1335, 231, 0, 'public', 1, 17, NULL, 'Level 2', 'East Seme', 24),
								(1342, 'Onyinjo Dispensary', '17173', 'standalone', 'PEPFAR', 'PMTCT', 813, 29, 1336, NULL, 0, 'public', 0, 17, NULL, 'Level 2', 'Otwenya', 24),
								(1344, 'Oriang'' Alwala Dispensary', '18086', 'satellite', 'PEPFAR', 'PMTCT', 813, 29, 1338, 231, 0, 'public', 1, 17, NULL, 'Level 2', 'South West Seme', 24),
								(1345, 'Soklo Dispensary ', '14095', 'satellite', 'PEPFAR', 'ART,PMTCT', 895, 203, 1339, 567, 0, 'mission', 1, 8, NULL, 'Level 2', 'Mfangano South', 48),
								(1346, 'Finlay Flowers Dispensary', '14497', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 172, 1340, 231, 0, 'private', 1, 12, NULL, 'Level 2', 'Chaik', 32),
								(1347, 'Faces Nyanza (Lumumba) Dispensing Point', '13738', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 2, 1341, 46, 0, 'public', 1, 17, NULL, 'Not Classified', 'Township', 20),
								(1348, 'Kibos Sugar Research Dispensary ', '13689', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 2, 1342, 46, 0, 'public', 1, 17, NULL, 'Level 2', 'Miwani', 20),
								(1349, 'Chepgoiben Dispensary', '14334', 'satellite', 'PEPFAR', 'PMTCT', 952, 172, 1343, 501, 0, 'private', 1, 12, NULL, 'Level 2', 'Chaik', 32),
								(1350, 'Miwani Dispensary ', '13816', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 2, 1344, 46, 0, 'public', 1, 17, NULL, 'Level 2', 'Miwani', 15),
								(1351, 'Tuungane Youth Transition Centre', '17166', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 2, 1345, NULL, 0, 'public', 0, 17, NULL, 'Not Classified', 'Manyatta B', 20),
								(1352, 'Coast Provincial General Hospital  Dispensing Point', '11289', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 205, 1346, 34, 0, 'public', 1, 28, NULL, 'Level 5', 'Tononoka', 24),
								(1353, 'Chebitet Dispensary', '14298', 'satellite', 'PEPFAR', 'PMTCT', 952, 190, 1347, 491, 0, 'private', 1, 2, NULL, 'Level 2', 'Chebitet', 38),
								(1354, 'Chemamul Dispensary', '14310', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 190, 1348, 501, 0, 'private', 1, 2, NULL, 'Level 2', 'Saosa', 32),
								(1355, 'Cheptebes Dispensary', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 190, 1349, 501, 0, 'public', 1, 2, NULL, 'Level 2', '', 32),
								(1356, 'Ademasajida Dispensary', '13267', 'satellite', 'PEPFAR', 'PMTCT', 1728, 272, 1350, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Ademasajida', 24),
								(1357, 'Flower 1 Dispensary', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 190, 1351, 501, 0, 'private', 1, 2, NULL, 'Level 2', '', 32),
								(1358, 'Arbajahan Health Centre', '13291', 'satellite', 'PEPFAR', 'PMTCT', 1728, 272, 1352, 558, 0, 'public', 1, 46, NULL, 'Level 3', 'Arbajahan', 24),
								(1359, 'Abakore Health Centre', '13265', 'satellite', 'PEPFAR', 'PMTCT', 1728, 157, 1353, 558, 0, 'public', 1, 46, NULL, 'Level 3', 'Abakore', 24),
								(1360, 'Argane Dispensary', '13293', 'satellite', 'PEPFAR', 'PMTCT', 1728, 157, 1354, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Ibrahim Ure', 24),
								(1361, 'Biyamadhow Health Centre', '13306', 'satellite', 'PEPFAR', 'PMTCT', 1728, 157, 1355, 558, 0, 'public', 1, 46, NULL, 'Level 3', 'Biyamadhow', 24),
								(1362, 'Burder Dispensary', '13313', 'satellite', 'PEPFAR', 'PMTCT', 1728, 157, 1356, 558, 0, 'mission', 1, 46, NULL, 'Level 2', 'Burder', 24),
								(1363, 'Arbajahan Nomadic MC', '16286', 'satellite', 'PEPFAR', 'PMTCT', 1728, 146, 1357, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Arbajahan', 24),
								(1364, 'Flower 2 dispensary', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 190, 1358, 501, 0, 'private', 1, 2, NULL, 'Level 2', '', 32),
								(1365, 'Dadajabula Health Centre', '13317', 'satellite', 'PEPFAR', 'PMTCT', 1728, 157, 1359, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Dadajabula', 24),
								(1366, 'Dagahaley Dispensary', '17006', 'satellite', 'PEPFAR', 'PMTCT', NULL, 157, 1360, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Dagahley', 24),
								(1367, 'Diff Health Centre', '13328', 'satellite', 'PEPFAR', 'PMTCT', 1728, 157, 1361, 558, 0, 'public', 1, 46, NULL, 'Level 3', 'Diff', 24),
								(1368, 'Dilmanyaley Health Centre', '13329', 'satellite', 'PEPFAR', 'PMTCT', 1728, 157, 1362, 558, 0, 'public', 1, 46, NULL, 'Level 3', 'Dilmanyaley', 24),
								(1369, 'Kursin Dispensary', '13387', 'satellite', 'PEPFAR', 'PMTCT', 1728, 157, 1363, 558, 0, 'mission', 1, 46, NULL, 'Level 2', 'Kursin', 24);
								INSERT INTO `sync_facility` (`id`, `name`, `code`, `category`, `sponsors`, `services`, `manager_id`, `district_id`, `address_id`, `parent_id`, `ordering`, `affiliation`, `service_point`, `county_id`, `hcsm_id`, `keph_level`, `location`, `affiliate_organization_id`) VALUES
								(1370, 'Leheley Health Centre', '13394', 'satellite', 'PEPFAR', 'PMTCT', 1728, 157, 1364, 558, 0, 'public', 1, 46, NULL, 'Level 3', 'Leheley', 24),
								(1371, 'Meri Dispensary', '13409', 'satellite', 'PEPFAR', 'PMTCT', 889, 157, 1365, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Abakore', 24),
								(1372, 'Sabuli Health Centre ', '13428', 'satellite', 'PEPFAR', 'PMTCT', 1728, 157, 1366, 558, 0, 'public', 1, 46, NULL, 'Level 3', 'Sabuli', 24),
								(1373, 'Sabuli Nomadic Dispensary', '13429', 'satellite', 'PEPFAR', 'PMTCT', 1728, 157, 1367, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Sabuli', 24),
								(1374, 'Sarif Health Centre', '13435', 'satellite', 'PEPFAR', 'PMTCT', 1728, 157, 1368, 558, 0, 'public', 1, 46, NULL, 'Level 3', 'Sarif', 24),
								(1375, 'Burduras Health Centre', '17035', 'satellite', 'PEPFAR', 'PMTCT', 828, 161, 1369, NULL, 0, 'public', 1, 24, NULL, 'Level 3', 'Burduras East', 24),
								(1376, 'Dandu Health Centre', '13323', 'satellite', 'PEPFAR', 'PMTCT', 828, 161, 1370, 801, 0, 'public', 1, 24, NULL, 'Level 3', 'Dandu', 24),
								(1377, 'Guba Dispensary', '13353', 'satellite', 'PEPFAR', 'PMTCT', 828, 161, 1371, 801, 0, 'public', 1, 24, NULL, 'Level 2', 'Guba', 24),
								(1378, 'Eldas Health Centre', '13333', 'satellite', 'PEPFAR', 'PMTCT', 1728, 146, 1372, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Eldas', 24),
								(1379, 'Kiliweheri Health Centre', '13381', 'satellite', 'PEPFAR', 'PMTCT', 828, 161, 1373, 801, 0, 'public', 1, 24, NULL, 'Level 3', 'Kilewehiri', 24),
								(1380, 'Elnoor Dispensary', '13334', 'satellite', 'PEPFAR', 'PMTCT', 1728, 146, 1374, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Elnoor', 24),
								(1381, 'Malkamari Dispensary', '13401', 'satellite', 'PEPFAR', 'PMTCT', 828, 161, 1375, 801, 0, 'public', 1, 24, NULL, 'Not Classified', 'Malkamari', 24),
								(1382, 'Ganyure Dispensary', '13343', 'satellite', 'PEPFAR', 'PMTCT', 1728, 272, 1376, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Ganyure', 24),
								(1383, 'Takaba Nomadic Mobile', '16314', 'satellite', 'PEPFAR', 'PMTCT', 828, 161, 1377, 801, 0, 'public', 1, 24, NULL, 'Level 2', 'Dandu', 24),
								(1384, 'Garseyqoftu Dispensary', '17289', 'satellite', 'PEPFAR', 'PMTCT', 1728, 272, 1378, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Arbajhan', 24),
								(1385, 'Hadado Health Centre', '13358', 'satellite', 'PEPFAR', 'PMTCT', 1728, 272, 1379, 558, 0, 'public', 1, 46, NULL, 'Level 3', 'Hadado', 24),
								(1386, 'Shanta Abaq Dispensary (Wajir West)', '13437', 'satellite', 'PEPFAR', 'PMTCT', 1728, 272, 1380, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Ganyure', 24),
								(1387, 'Tulatula Dispensary', ' 13447', 'satellite', 'PEPFAR', 'PMTCT', 1728, 272, 1381, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Tulatula', 24),
								(1388, 'Wagalla Dispensary', '13449', 'satellite', 'PEPFAR', 'PMTCT', 1728, 272, 1382, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Wagalla', 24),
								(1389, 'Wagalla Health Centre', '17820', 'satellite', 'PEPFAR', 'PMTCT', 1728, 272, 1383, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Wagalla', 24),
								(1390, 'Kaproret Dispensary', '19294', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 108, 1384, 501, 0, 'private', 1, 12, NULL, 'Level 2', 'Chaik', 32),
								(1391, 'Kapsongoi Dispensary', '19157', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 108, 1385, 501, 0, 'private', 1, 12, NULL, 'Level 2', 'Chaik', 32),
								(1392, 'Kipketer Dispensary', '14899', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 172, 1386, 501, 0, 'private', 1, 12, NULL, 'Level 2', 'Chaik', 32),
								(1393, 'Marinyin Dispensary', '15139', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 813, 172, 1387, 501, 0, 'private', 1, 12, NULL, 'Level 2', 'Chaik', 32),
								(1394, 'Simotwet Dispensary', '15576', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 190, 1388, 501, 0, 'private', 1, 2, NULL, 'Level 2', 'Saosa', 32),
								(1395, 'Ajawa Health Centre', '13272', 'satellite', 'PEPFAR', 'PMTCT', 1728, 273, 1389, 558, 0, 'public', 1, 46, NULL, 'Level 3', 'Ajawa', 24),
								(1396, 'Batalu Dispensary', '13304', 'satellite', 'PEPFAR', 'PMTCT', 1728, 146, 1390, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Batalu', 24),
								(1397, 'Buna Sub-District Hospital ', '13312', 'satellite', 'PEPFAR', 'PMTCT', 1728, 146, 1391, 558, 0, 'public', 1, 46, NULL, 'Level 4', 'Buna', 24),
								(1398, 'Danaba Health Centre', '13322', 'satellite', 'PEPFAR', 'PMTCT', 1728, 273, 1392, 558, 0, 'public', 1, 46, NULL, 'Level 3', 'Danaba', 24),
								(1399, 'Dugo Health Centre', '13330', 'satellite', 'PEPFAR', 'PMTCT', 1728, 273, 1393, 558, 0, 'public', 1, 46, NULL, 'Level 3', 'Dugo', 24),
								(1400, 'Godoma Health Centre (NEP)', '13351', 'satellite', 'PEPFAR', 'PMTCT', 1728, 273, 1394, 558, 0, 'public', 1, 46, NULL, 'Level 3', 'Godoma', 24),
								(1401, 'Gurar Health Centre', '13355', 'satellite', 'PEPFAR', 'PMTCT', 1728, 273, 1395, 558, 0, 'public', 1, 46, NULL, 'Level 3', 'Gurar', 24),
								(1402, 'Korondille Health Centre', '13384', 'satellite', 'PEPFAR', 'PMTCT', 1728, 273, 1396, 558, 0, 'public', 1, 46, NULL, 'Level 3', 'Korondille', 24),
								(1403, 'Malkagufu Dispensary', '13400', 'satellite', 'PEPFAR', 'PMTCT', 1728, 146, 1397, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Malkagufu', 24),
								(1404, 'Qudama Dispensary', ' 13421', 'satellite', 'PEPFAR', 'PMTCT', 1728, 146, 1398, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Qudama', 24),
								(1405, 'Teso District Hospital Dispensing Point', '16150', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 214, 1399, 554, 0, 'public', 1, 4, NULL, 'Level 4', 'Kocholia', 3),
								(1406, 'Kamolo Dispensary', '17242', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 214, 1400, 554, 0, 'public', 1, 4, NULL, 'Level 2', 'Kamolo', 3),
								(1407, 'Mt  Elgon District Hospital Dispensing Point', '16025', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 132, 1401, 552, 0, 'public', 1, 3, NULL, 'Level 4', 'Kapsokwony', 3),
								(1408, 'Madende Dispensary', '15985', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 206, 1402, 760, 0, 'public', 1, 4, NULL, 'Level 2', 'Bukhayo East', 3),
								(1409, 'Nambale Health Centre Dispensing Point', '16066', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 206, 1403, 760, 0, 'public', 1, 4, NULL, 'Level 3', 'Nambale', 3),
								(1410, 'Amukura Health Centre Dispensing Point', '15798', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 214, 1404, 553, 0, 'public', 1, 4, NULL, 'Level 3', 'Amukura', 3),
								(1414, 'Iten District Hospital Dispensing Point', '14586', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 107, 1408, 499, 0, 'public', 1, 5, NULL, 'Level 4', 'Irong', 3),
								(1415, 'Rai Ply Dispensary', '', 'satellite', 'PEPFAR', 'PMTCT', 895, 122, 1409, 144, 0, 'public', 1, 44, NULL, 'Not Classified', '', 3),
								(1416, 'Kapteren Dispensary', '14781', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 107, 1410, 499, 0, 'public', 1, 5, NULL, 'Level 3', 'Mutei', 3),
								(1417, 'Khunyangu Sub District Hospital Dispensing Point', '15939', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 143, 1411, 545, 0, 'public', 1, 4, NULL, 'Level 3', 'Marachi Central', 3),
								(1418, 'Naitiri Health Centre Dispensing Point', '16061', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 138, 1412, 548, 0, 'public', 1, 3, NULL, 'Level 3', 'Mbakalo', 3),
								(1419, 'Chemogondany Hospital', '14319', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 172, 1413, NULL, 0, 'private', 1, 12, NULL, 'Level 4', 'Chaik', 27),
								(1420, 'Mabroukie -Limuru', '10667', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 813, 197, 1414, 506, 0, 'private', 1, 13, NULL, 'Not Classified', 'Karabaine', 38),
								(1421, 'James Finlay Kenya Dispensing Point', '14497', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 172, 1415, 501, 0, 'private', 1, 12, NULL, 'Level 1', 'Chaik', 32),
								(1422, 'Unilever Tea (Brooke Bond Tea) Kenya Dispensing Point', '15761', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 108, 1416, NULL, 0, 'private', 1, 12, NULL, 'Level 2', 'Ainamoi', 32),
								(1423, 'Kabianga Health Centre', '14613', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 172, 1419, 504, 0, 'public', 1, 12, NULL, 'Not Classified', 'Kabianga', 24),
								(1424, 'Itundu Dispensary', '10361', 'satellite', 'PEPFAR', 'PMTCT', 998, 202, 1422, 174, 0, 'public', 1, 36, NULL, 'Level 2', 'Iriaini', 24),
								(1425, 'Jamii Medical Clinic (Kilindini)', '11428', 'satellite', 'PEPFAR', 'PMTCT', 889, 175, 1423, NULL, 0, 'private', 0, 28, NULL, 'Level 2', 'Miritini', 27),
								(1426, 'Jarajara Dispensary', '13376', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1424, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Jarajara', 24),
								(1427, 'Al-Siha Nursing Home', '13287', 'satellite', 'PEPFAR', 'PMTCT', 828, 150, 1425, NULL, 0, 'private', 1, 24, NULL, 'Not Classified', 'Bulla Mpya', 27),
								(1429, 'Judy Medical Clinic', '11442', 'satellite', 'PEPFAR', 'PMTCT', 889, 175, 1427, NULL, 0, 'mission', 0, 28, NULL, 'Level 2', 'Miritini', 27),
								(1430, 'Kabianga Tea Research', '14614', 'satellite', 'PEPFAR', 'PMTCT', 952, 172, 1428, 506, 0, 'public', 1, 12, NULL, 'Not Classified', 'Kabianga', 27),
								(1431, ' Athibohol Dispensary', '13295 ', 'satellite', 'PEPFAR', 'PMTCT', 1728, 272, 1429, 558, 0, 'public', 1, 46, NULL, 'Level 2', 'Athibohol', 24),
								(1434, 'Kabisaga Dispensary', '14621', 'satellite', 'PEPFAR', 'PMTCT', 1363, 116, 1432, 78, 0, 'public', 1, 32, NULL, 'Not Classified', 'Kabisaga', 24),
								(1435, 'Kaboi Dispensary', '17023', 'satellite', 'PEPFAR', 'PMTCT', 1363, 117, 1433, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Kapsaos', 32),
								(1436, ' Bofu Dispensary', ' 11253 ', 'satellite', 'PEPFAR', 'PMTCT', 889, 149, 1434, NULL, 0, 'mission', 1, 19, NULL, 'Level 2', 'Mtaa', 19),
								(1437, 'Kabolecho Dispensary', '14626', 'satellite', 'PEPFAR', 'PMTCT', 1728, 217, 1435, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Kapsasian', 32),
								(1438, 'Amani Medical Clinic (Murungaru)', '17561', 'satellite', 'PEPFAR', 'PMTCT', 1728, 60, 1436, 45, 0, 'private', 1, 35, NULL, 'Level 2', 'Murungaru', 27),
								(1439, 'Kaboswa Tea Dispensary', '14629', 'satellite', 'PEPFAR', 'PMTCT', 1363, 115, 1437, 527, 0, 'private', 1, 32, NULL, 'Level 2', 'Tartar', 32),
								(1441, 'Kabwareng Dispensary', '17626', 'satellite', 'PEPFAR', 'PMTCT', 952, 41, 1439, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Chepkumia', 48),
								(1442, 'Kafuduni Dispensary', '11448', 'satellite', 'PEPFAR', 'PMTCT', 889, 149, 1440, 566, 0, 'public', 1, 19, NULL, 'Level 2', 'Mwatate', 24),
								(1443, 'Kagumoini Dispensary (Muranga North)', '10405', 'satellite', 'PEPFAR', 'PMTCT', 998, 201, 1441, 149, 0, 'public', 1, 29, NULL, 'Not Classified', 'Kiru', 24),
								(1444, 'Kagumoini Dispensary (Muranga South)', '10406', 'satellite', 'PEPFAR', 'PMTCT', 998, 58, 1442, 149, 0, 'public', 1, 29, NULL, 'Not Classified', 'Gaichanjiru', 24),
								(1445, 'Kahada Medical Clinic', '11450', 'satellite', 'PEPFAR', 'PMTCT', 889, 175, 1443, NULL, 0, 'private', 0, 28, NULL, 'Level 2', 'Miritini', 27),
								(1446, 'Burguret Dispensary', '10076 ', 'satellite', 'PEPFAR', 'PMTCT', 998, 183, 1444, NULL, 0, 'public', 1, 36, NULL, 'Level 2', 'Githima', 24),
								(1447, 'Kahuru Dispensary (Nyeri North)', '10427', 'satellite', 'PEPFAR', 'PMTCT', 998, 202, 1445, 79, 0, 'public', 1, 36, NULL, 'Not Classified', 'Engineer', 49),
								(1449, 'Kaiboi Mission Health Centre', '14640', 'satellite', 'PEPFAR', 'PMTCT', 1701, 116, 1447, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Lolkeringet', 32),
								(1450, 'Bwagamoyo Dispensary', '11266', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB', 889, 8, 1448, 301, 0, 'public', 1, 14, NULL, 'Level 2', 'Mwawesa', 24),
								(1451, 'Kaigat (SDA) Health Centre', '14642', 'satellite', 'PEPFAR', 'PMTCT', 1701, 116, 1449, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Kaigat', 12),
								(1452, ' Care Medical Clinic', '16176', 'satellite', 'PEPFAR', 'PMTCT', 1728, 59, 1450, 45, 0, 'public', 1, 35, NULL, 'Not Classified', 'Nyahururu', 24),
								(1453, 'Kaimosi tea dispensary', '14644', 'satellite', 'PEPFAR', 'PMTCT', 1363, 41, 1451, 527, 0, 'mission', 1, 32, NULL, 'Level 2', 'Kaimosi', 32),
								(1454, 'Kairo Dispensary', '10432', 'satellite', 'PEPFAR', 'PMTCT', 998, 201, 1452, 149, 0, 'public', 1, 29, NULL, 'Level 2', 'Kiru', 24),
								(1455, 'Kaiyaba Dispensary', '10435', 'satellite', 'PEPFAR', 'PMTCT', 998, 202, 1453, 79, 0, 'public', 1, 36, NULL, 'Level 2', 'Kirimukuyu', 24),
								(1456, 'Kamaget Dispensary', '14666', 'satellite', 'PEPFAR', 'PMTCT', 952, 172, 1454, 506, 0, 'public', 1, 12, NULL, 'Level 2', 'Kebeneti', 32),
								(1457, 'Kamaget Dispensary (Trans Mara)', '14667', 'satellite', 'PEPFAR', 'PMTCT', 1728, 217, 1455, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Murgan', 38),
								(1458, 'Kamangunet VCT', '16331', 'satellite', 'PEPFAR', 'PMTCT', 1363, 117, 1456, 527, 0, 'private', 1, 32, NULL, 'Level 2', 'Chebilat', 21),
								(1459, 'Kambe Dispensary', '16192', 'satellite', 'PEPFAR', 'PMTCT', 889, 50, 1457, NULL, 0, 'public', 1, 14, NULL, 'Level 2', 'Kambe', 24),
								(1460, 'Barotion (AIC) Dispensary', '14234 ', 'satellite', 'PEPFAR', 'PMTCT', 952, 102, 1458, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Kipsirichet', 24),
								(1461, 'Young Muslim Dispensary', '13458', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1459, 557, 0, 'mission', 1, 7, NULL, 'Level 2', 'Iftin', 51),
								(1462, 'Women Care Clinic', '16658', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1460, 557, 0, 'mission', 1, 7, NULL, 'Level 2', 'Central', 19),
								(1463, 'Wendiga Dispensary', '11182', 'satellite', 'PEPFAR', 'PMTCT', 998, 281, 1461, 79, 0, 'public', 1, 36, NULL, 'Level 2', 'Labura', 42),
								(1464, 'Webuye District Hospital Dispensing Point', '16161', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 30, 1462, 547, 0, 'public', 1, 3, NULL, 'Level 2', 'Webuye', 3),
								(1465, 'Kambirwa Health Centre', '10443', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1463, 149, 0, 'public', 1, 29, NULL, 'Level 3', 'Gikindu', 24),
								(1466, 'Watuka Dispensary', '11178', 'satellite', 'PEPFAR', 'PMTCT', 998, 281, 1464, 79, 0, 'public', 1, 36, NULL, 'Level 2', 'Watuka', 42),
								(1467, 'Wanjerere Dispensary', '11172', 'satellite', 'PEPFAR', 'PMTCT', 998, 282, 1465, 251, 0, 'public', 1, 29, NULL, 'Level 2', 'Rwathia', 24),
								(1468, 'Kamburaini Dispensary', '10447', 'satellite', 'PEPFAR', 'PMTCT', 998, 183, 1466, NULL, 0, 'public', 1, 36, NULL, 'Level 2', 'Kamburaini', 24),
								(1469, 'Kamelil Dispensary', '14672', 'satellite', 'PEPFAR', 'PMTCT', 952, 119, 1467, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Kamelil', 32),
								(1470, 'Wanjengi Dispensary', '11171', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1468, 150, 0, 'public', 1, 29, NULL, 'Level 2', 'Weithaga', 24),
								(1471, 'Chalani Dispensary', '16191 ', 'satellite', 'PEPFAR', 'PMTCT', 889, 50, 1469, NULL, 0, 'public', 1, 14, NULL, 'Level 2', 'Chanagande', 24),
								(1472, 'Kanjama Dispensary', '10472', 'satellite', 'PEPFAR', 'PMTCT', NULL, 201, 1470, NULL, 0, 'public', 0, 29, NULL, 'Level 2', 'Kiru', 24),
								(1473, 'Kanusin Dispensary', '14688', 'satellite', 'PEPFAR', 'PMTCT', 952, 137, 1471, 231, 0, 'public', 1, 2, NULL, 'Level 2', 'Kanusin', 32),
								(1474, 'Chebirbelek Dispensary', '14297', 'satellite', 'PEPFAR', 'PMTCT', 952, 172, 1472, 491, 0, 'public', 1, 12, NULL, 'Level 2', 'Chebirrirbei', 32),
								(1475, 'Kanyenyaini Health Centre', '10474', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1473, 251, 0, 'public', 1, 29, NULL, 'Level 3', 'Kanyenyaini', 24),
								(1476, 'Wahundura Dispensary', '11152', 'satellite', 'PEPFAR', 'PMTCT', 998, 201, 1474, 149, 0, 'public', 1, 29, NULL, 'Level 2', 'Kamacharia', 24),
								(1477, 'Kapchorwa Dispensary', '14696', 'satellite', 'PEPFAR', 'PMTCT', 1363, 115, 1475, 527, 0, 'mission', 1, 32, NULL, 'Level 2', 'Kapchorua', 32),
								(1478, 'Vishakani Dispensary', '16190', 'satellite', 'PEPFAR', 'PMTCT', 889, 50, 1476, NULL, 0, 'public', 1, 14, NULL, 'Level 2', 'Kaloleni', 24),
								(1479, 'Viragoni Dispensary', '17689', 'satellite', 'PEPFAR', 'PMTCT', 889, 50, 1477, NULL, 0, 'public', 1, 14, NULL, 'Level 2', 'Mwanamwinga', 24),
								(1480, 'Cheindoi Dispensary', '14307 ', 'satellite', 'PEPFAR', 'PMTCT', 952, 41, 1478, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Kapsabet', 32),
								(1481, 'Kapchumba Dispensary', '14697', 'satellite', 'PEPFAR', 'PMTCT', 1701, 41, 1479, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Tulon', 32),
								(1482, 'Kapkeben Dispensary', '14707', 'satellite', 'PEPFAR', 'PMTCT', 1701, 207, 1480, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Kapkeben', 32),
								(1483, 'Kapkenyeloi  Dispensary', '14711', 'satellite', 'PEPFAR', 'PMTCT', 1701, 116, 1481, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Kapkoimur', 32),
								(1484, 'Kapkeringoon Dispensary', '14713', 'satellite', 'PEPFAR', 'PMTCT', 1701, 116, 1482, 78, 0, 'ngo', 1, 32, NULL, 'Level 2', 'Kabisaga', 36),
								(1485, 'Kapkolei Dispensary', '14724', 'satellite', 'PEPFAR', 'PMTCT', 952, 117, 1483, NULL, 0, 'public', 1, 32, NULL, 'Level 2', 'Kapkolei', 32),
								(1486, 'Union Medical Dispensary', '16181', 'satellite', 'PEPFAR', 'PMTCT', 889, 8, 1484, NULL, 0, 'mission', 1, 14, NULL, 'Level 2', 'Ruruma', 27),
								(1487, 'Chemase Dispensary', '14314 ', 'satellite', 'PEPFAR', 'PMTCT,LAB,RTK', 1363, 172, 1485, 504, 0, 'private', 1, 12, NULL, 'Level 2', 'Chaik', 24),
								(1488, 'Tuthu Dispensary', '11129', 'satellite', 'PEPFAR', 'PMTCT', 998, 282, 1486, 251, 0, 'public', 1, 29, NULL, 'Level 2', 'Kiruri', 24),
								(1489, 'Kapletundo Dispensary', '16317', 'satellite', 'PEPFAR', 'PMTCT', 952, 137, 1487, 231, 0, 'public', 1, 2, NULL, 'Level 2', 'Kapletundo', 32),
								(1490, 'Kapng''ombe Dispensary', '14745', 'satellite', 'PEPFAR', 'PMTCT', 1701, 116, 1488, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Ndalat', 36),
								(1491, 'Kaprochoge', '', 'satellite', 'PEPFAR', 'PMTCT', 952, 117, 1489, 527, 0, 'public', 1, 32, NULL, 'Not Classified', 'Kaptumo', 24),
								(1492, 'Kapsamoch Diapensary', '14751', 'satellite', 'PEPFAR', 'PMTCT', 1701, 117, 1490, 527, 0, 'mission', 1, 32, NULL, 'Level 2', 'Kapkures', 32),
								(1493, 'Chemartin Tea Dispensary', '14313 ', 'satellite', 'PEPFAR', 'PMTCT', 1363, 115, 1491, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Siret', 24),
								(1494, 'Kapsaos Dispensary', '14752', 'satellite', 'PEPFAR', 'PMTCT', 1701, 117, 1492, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Kapsaos', 32),
								(1495, 'Kapsasian Dispensary', '14754', 'satellite', 'PEPFAR', 'PMTCT', 1728, 217, 1493, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Kapsasian', 32),
								(1496, 'Chebilat Dispensary', '14295 ', 'satellite', 'PEPFAR', 'PMTCT', 952, 137, 1494, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Manaret', 24),
								(1497, 'Kapsengere Dispensary', '14756', 'satellite', 'PEPFAR', 'PMTCT', 1363, 117, 1495, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Terik', 32),
								(1498, 'Chebilat Dispensary (Nandi South)', '17131 ', 'satellite', 'PEPFAR', 'PMTCT', 952, 117, 1496, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Chebilat', 24),
								(1499, 'Kaptel Dispensary', '14778', 'satellite', 'PEPFAR', 'PMTCT', 1701, 41, 1497, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Kaptel', 24),
								(1500, 'Kaptich Dispensary', '14782', 'satellite', 'PEPFAR', 'PMTCT', 1701, 116, 1498, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Kaptich', 32),
								(1501, 'Chebirbei Dispensary', '14296 ', 'satellite', 'PEPFAR', 'PMTCT', 952, 172, 1499, 506, 0, 'public', 1, 12, NULL, 'Not Classified', 'Chebirrirbei', 21),
								(1502, 'Kaptumek Dispensary', '14789', 'satellite', 'PEPFAR', 'PMTCT', 952, 117, 1500, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Maraba', 32),
								(1503, 'Chemomi Tea Dispensary', ' 14322', 'satellite', 'PEPFAR', 'PMTCT', 1363, 115, 1501, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Chemomi', 32),
								(1504, 'Kaptumo Health Centre', '14791', 'satellite', 'PEPFAR', 'PMTCT', 895, 123, 1502, 527, 0, 'public', 1, 44, NULL, 'Level 2', 'Kapkoi', 32),
								(1505, 'Chemundu Dispensary', '14324 ', 'satellite', 'PEPFAR', 'PMTCT', 1363, 41, 1503, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Chemundu', 32),
								(1506, 'Kapweria Dispensary', '14797', 'satellite', 'PEPFAR', 'PMTCT', 1701, 120, 1504, NULL, 0, 'public', 1, 33, NULL, 'Level 2', 'Ololmasani', 48),
								(1507, 'Chemursoi Dispensary', '14325 ', 'satellite', 'PEPFAR', 'PMTCT', 1363, 117, 1505, 527, 0, 'public', 1, 32, NULL, 'Level 2', ' Chemase', 24),
								(1508, 'Chemuswo Dispensary', '14326', 'satellite', 'PEPFAR', 'PMTCT', 952, 41, 1506, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Kamoiywo', 24),
								(1509, 'Keben Dispensary', '14821', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1507, 527, 0, 'ngo', 1, 32, NULL, 'Level 2', 'Koilot', 32),
								(1510, 'Chepkongony Dispensary', '14345 ', 'satellite', 'PEPFAR', 'PMTCT', 1363, 117, 1508, 527, 0, 'public', 1, 32, NULL, 'Level 2', ' Kaptumo', 32),
								(1511, 'Kemelil Dispensary', '', 'satellite', '', '', 952, 117, 1509, 527, 0, 'public', 1, 32, NULL, 'Not Classified', '', 32),
								(1512, 'Cheplengu Dispensary', ' 17629', 'satellite', 'PEPFAR', 'PMTCT', 952, 41, 1510, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Kapsabet', 24),
								(1513, 'Kenyagoro Dispensary', '14828', 'satellite', 'PEPFAR', 'PMTCT', 1363, 190, 1511, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Boito', 32),
								(1514, 'Cheptabach Dispensary', '14364 ', 'satellite', 'PEPFAR', 'PMTCT', 1363, 115, 1512, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Siret', 32),
								(1515, 'Transmara Medicare', '15740', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1513, 825, 0, 'private', 1, 33, NULL, 'Level 4', 'Olomismis', 32),
								(1516, 'Chepterit Mission Health Centre', '14368 ', 'satellite', 'PEPFAR', 'PMTCT', 952, 41, 1514, 78, 0, 'public', 1, 32, NULL, 'Level 3', 'Chepterit', 32),
								(1517, 'Kepchomo Tea Dispensary', '14829', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1515, 527, 0, 'private', 1, 32, NULL, 'Level 2', 'Nandi Hills', 32),
								(1518, 'Toretmoi Dispensary ', '17022', 'satellite', 'PEPFAR', 'PMTCT', 1701, 117, 1516, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Kapsaos', 24),
								(1519, ' Chepterwo Dispensary', '14370', 'satellite', 'PEPFAR', 'PMTCT', 952, 102, 1517, 491, 0, 'mission', 1, 2, NULL, 'Level 2', ' Ngesumin', 32),
								(1520, 'Kerinkan', '14835', 'satellite', 'PEPFAR', 'PMTCT', 1728, 217, 1518, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Isokon', 32),
								(1521, 'Cheptingwich Dispensary', '17024', 'satellite', 'PEPFAR', 'PMTCT', 1363, 117, 1519, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Chebara', 24),
								(1522, 'Khartoum Dispensary', '14844', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1520, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Tartar', 32),
								(1523, 'Tolilet dispensary', '16315', 'satellite', 'PEPFAR', 'PMTCT', 1701, 116, 1521, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Soymining', 24),
								(1524, 'Chesinende (ELCK) Dispensary', '14382', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1522, 510, 0, 'mission', 1, 12, NULL, 'Level 2', 'Chepseon', 48),
								(1525, 'Chuthber Dispensary', '13530 ', 'satellite', 'PEPFAR', 'PMTCT', 864, 35, 1523, 89, 0, 'public', 1, 8, NULL, 'Level 2', 'Rambira', 24),
								(1526, 'Tiryo Dispensary', '17176', 'satellite', 'PEPFAR', 'PMTCT', 1701, 41, 1524, 527, 0, 'public', 1, 32, NULL, 'Not Classified', 'Arwos', 24),
								(1527, 'Kiairathe Dispensary', '10528', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1525, 251, 0, 'public', 1, 29, NULL, 'Level 2', 'Muguru', 24),
								(1528, 'Kiamara Dispensary', '10533', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1526, 149, 0, 'public', 1, 29, NULL, 'Level 3', 'Iyego', 24),
								(1529, 'Kiamathaga Dispensary', '10536', 'satellite', 'PEPFAR', 'PMTCT', 998, 183, 1527, NULL, 0, 'public', 1, 36, NULL, 'Level 2', 'Mwichuiri', 24),
								(1530, 'Kiambogo Medical Clinic', '10538', 'satellite', 'PEPFAR', 'PMTCT', 998, 202, 1528, 45, 0, 'private', 1, 36, NULL, 'Not Classified', 'Kabaru', 27),
								(1531, 'Kiambogo Dispensary (Nyandarua South)', '10537', 'satellite', 'PEPFAR', 'PMTCT', 1728, 187, 1529, 45, 0, 'public', 1, 35, NULL, 'Not Classified', 'Kiambogo', 24),
								(1532, 'Kiangochi Dispensary', '10558', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1530, 149, 0, 'public', 1, 29, NULL, 'Level 2', 'Mbiri', 24),
								(1533, 'Tinderet Tea Dispensary', '15726', 'satellite', 'PEPFAR', 'PMTCT', 1701, 119, 1531, 527, 0, 'private', 1, 32, NULL, 'Level 2', 'Tinderet', 38),
								(1534, 'Thika road health services ltd (Kasarani)', '17950', 'satellite', 'PEPFAR', 'PMTCT', NULL, 166, 1532, NULL, 0, 'private', 1, 30, NULL, 'Level 2', 'Kasarani', 27),
								(1535, 'Kibabet Dispensary', '14847', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1533, 231, 0, 'public', 1, 32, NULL, 'Level 2', 'Mogobich', 32),
								(1536, 'Kobujoi Forest Dispensary', '17125', 'satellite', 'PEPFAR', 'PMTCT', 1701, 117, 1534, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Chebilat', 24),
								(1537, 'Tchundwa Dispensary', '11846', 'satellite', 'PEPFAR', 'PMTCT', NULL, 193, 1535, NULL, 0, 'public', 0, 21, NULL, 'Level 2', 'Tchundwa', 24),
								(1538, 'Taunet Dispensary', '17801', 'satellite', 'PEPFAR', 'PMTCT', 1701, 119, 1536, 527, 0, 'mission', 1, 32, NULL, 'Level 2', 'Songhor', 48),
								(1539, 'Takitech Dispensary', '15700', 'satellite', 'PEPFAR', 'PMTCT', 1728, 217, 1537, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Olomasani', 24),
								(1540, 'Emarti Health Centre', '14442', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1538, 825, 0, 'public', 1, 33, NULL, 'Level 3', 'Emarti', 32),
								(1541, 'Kokotoni dispensary', '17017', 'satellite', 'PEPFAR', 'PMTCT', 889, 50, 1539, NULL, 0, 'public', 1, 14, NULL, 'Level 2', 'Rabai', 24),
								(1542, 'Kibandaongo Dispensary', '11464', 'satellite', 'PEPFAR', 'PMTCT', 889, 149, 1540, NULL, 0, 'ngo', 1, 19, NULL, 'Level 2', 'Kinango', 24),
								(1543, 'Kokwet Dispensary				', '14977', 'satellite', 'PEPFAR', 'PMTCT', 1701, 116, 1541, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Kokwet', 38),
								(1544, 'Embaringo Dispensary', '10166', 'satellite', 'PEPFAR', 'PMTCT', 998, 281, 1542, NULL, 0, 'public', 1, 36, NULL, 'Level 2', 'Embaringo', 24),
								(1545, 'Taito Dispensary', '17020', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1543, 527, 0, 'mission', 1, 32, NULL, 'Level 2', 'Taito', 32),
								(1546, 'Kibugat Dispensary', '14856', 'satellite', 'PEPFAR', 'PMTCT', 1363, 174, 1544, 491, 0, 'ngo', 1, 2, NULL, 'Level 2', 'Tebesonik', 32),
								(1547, 'Koloch Dispensary', '14980', 'satellite', 'PEPFAR', 'PMTCT', 1701, 117, 1545, 527, 0, 'ngo', 1, 32, NULL, 'Level 2', 'kemeloi', 48),
								(1548, 'Kibutha Dispensary', '10577', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1546, 251, 0, 'public', 1, 29, NULL, 'Level 2', 'Kanyenyaini', 24),
								(1549, 'Tachasis Dispensary', '15698', 'satellite', 'PEPFAR', 'PMTCT', 1701, 119, 1547, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Meteitei', 32),
								(1550, 'Kibwareng Dispensary', '14857', 'satellite', 'PEPFAR', 'PMTCT', 952, 117, 1548, 527, 0, 'public', 1, 32, NULL, 'Level 3', 'Kibwareng', 32),
								(1551, 'Kibwari Dispensary', '14858', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1549, 231, 0, 'public', 1, 32, NULL, 'Level 2', 'Kapsimotwo', 32),
								(1552, 'Tabolwa Dispensary', '15696', 'satellite', 'PEPFAR', 'PMTCT', 1701, 116, 1550, 527, 0, 'mission', 1, 32, NULL, 'Level 2', 'Sangalo', 24),
								(1554, 'Emurua Dikirr Dispensary', ' 14451 ', 'satellite', 'PEPFAR', 'PMTCT', 1728, 106, 1552, 825, 0, 'public', 1, 10, NULL, 'Level 2', 'Bisil', 32),
								(1555, 'Kondamet Dispensary', '14985', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1553, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Oldonyoro', 48),
								(1556, 'Kongoro Dispensary', '14987', 'satellite', 'PEPFAR', 'PMTCT', 1701, 117, 1554, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Kongoro', 36),
								(1557, 'Stella Maris Medical Clinic', '11832', 'satellite', 'PEPFAR', 'PMTCT', NULL, 196, 1555, NULL, 0, 'private', 0, 28, NULL, 'Level 2', 'Likoni', 19),
								(1558, 'St. Boniface Dispensary, Tindinyo', '15630', 'satellite', 'PEPFAR', 'PMTCT', 1701, 41, 1556, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Tindinyo', 24),
								(1559, 'Kigetuini Dispensary', '10584', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1557, 149, 0, 'public', 1, 29, NULL, 'Not Classified', 'Gaturi', 24),
								(1560, 'St. Antony''s Abossi Health Centre', '15627', 'satellite', 'PEPFAR', 'PMTCT', 1728, 217, 1558, 825, 0, 'public', 1, 33, NULL, 'Level 3', 'Ololmasani', 19),
								(1561, 'Koyo Health centre', '14997', 'satellite', 'PEPFAR', 'PMTCT', 1701, 117, 1559, 527, 0, 'public', 1, 32, NULL, 'Level 3', 'Koyo', 24),
								(1562, 'Kilgoris (COG) Dispensary', '14864', 'satellite', 'PEPFAR', 'PMTCT', 1728, 217, 1560, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Ololchani', 12),
								(1563, 'Engineer Medical Clinic', '17557', 'satellite', 'PEPFAR', 'PMTCT', 1728, 60, 1561, 45, 0, 'private', 1, 35, NULL, 'Level 2', 'Engineer', 27),
								(1564, 'Entargeti Dispensary', '17320 ', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1562, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Olalui', 24),
								(1565, 'Kuresiet Dispensary', '16326', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1563, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Njipship', 48),
								(1566, 'Kilgoris Medical Centre', '14865', 'satellite', 'PEPFAR', 'PMTCT', 1728, 217, 1564, 825, 0, 'private', 1, 33, NULL, 'Level 2', 'Shartuka', 32),
								(1567, 'St Theresia Of Jesus', '15668', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1565, 825, 0, 'mission', 1, 33, NULL, 'Level 2', 'Esoit', 27),
								(1568, 'Eymole Health Centre', '18458', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1566, 801, 0, 'public', 1, 24, NULL, 'Level 3', 'Eymole', 24),
								(1569, 'Lafey Nomadic Dispensary', '16293', 'satellite', 'PEPFAR', 'PMTCT', 828, 150, 1567, NULL, 0, 'public', 1, 24, NULL, 'Level 2', 'Lafey', 27),
								(1570, 'Langoni Nursing Home', '11515', 'satellite', 'PEPFAR', 'PMTCT', 889, 194, 1568, NULL, 0, 'public', 1, 21, NULL, 'Not Classified', 'langoni', 27),
								(1571, 'Kilibasi Dispensary', '11473', 'satellite', 'PEPFAR', 'PMTCT', 889, 149, 1569, 566, 0, 'public', 1, 19, NULL, 'Level 2', 'Macknnon Road', 24),
								(1572, 'Lelmokwo Health Centre', '15022', 'satellite', 'PEPFAR', 'PMTCT', 1701, 41, 1570, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'lelmokwo', 32),
								(1573, 'Lenga Dispensary', '11517', 'satellite', 'PEPFAR', 'PMTCT', 889, 50, 1571, NULL, 0, 'public', 1, 14, NULL, 'Level 2', 'kambe', 24),
								(1574, 'Kimintet Dispensary', '14873', 'satellite', 'PEPFAR', 'PMTCT', 1728, 217, 1572, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Kimintet', 24),
								(1575, 'Farmers Choice Clinic', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 166, 1573, NULL, 0, '', 1, 30, NULL, 'Not Classified', '', 0),
								(1576, 'Lolkeringet Dispensary', '15069', 'satellite', 'PEPFAR', 'PMTCT', 1701, 116, 1574, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Lolkeringet', 36),
								(1577, 'St. Lawrence Dispensary', '17517', 'satellite', 'PEPFAR', 'PMTCT', 1728, 60, 1575, 45, 0, 'mission', 1, 35, NULL, 'Level 2', 'Nyandarua', 19),
								(1578, 'Kimng''oror (ACK) Health Centre', '16720', 'satellite', 'PEPFAR', 'PMTCT', 1701, 144, 1576, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Surungai', 12),
								(1579, 'Lolminingai Dispensary', '15070', 'satellite', 'PEPFAR', 'PMTCT', 952, 41, 1577, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Lolminingai', 32),
								(1580, 'Kimondi Forest Dispensary ', '14876', 'satellite', 'PEPFAR', 'PMTCT', 1701, 41, 1578, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Kapsabet', 32),
								(1581, 'St Joseph Hospital', '15647', 'satellite', 'PEPFAR', 'PMTCT', 1728, 217, 1579, 825, 0, 'mission', 1, 33, NULL, 'Level 4', 'Ololchani', 19),
								(1582, 'Londiani Nursing Home', '', 'satellite', 'PEPFAR', 'PMTCT', 952, 102, 1580, 510, 0, 'public', 1, 2, NULL, 'Not Classified', 'Londiani', 21),
								(1583, 'Machinery Medical Clinic', '17498', 'satellite', 'PEPFAR', 'PMTCT', 1728, 187, 1581, 45, 0, 'public', 1, 35, NULL, 'Not Classified', '', 42),
								(1584, 'St Joseph (EDARP) Clinic', '13207', 'satellite', 'PEPFAR', 'PMTCT', 998, 165, 1582, NULL, 0, 'private', 1, 30, NULL, 'Level 2', 'Shauri Moyo', 19),
								(1585, 'Mackinon Road Dispensary', '11531', 'satellite', 'PEPFAR', 'PMTCT', 889, 149, 1583, 636, 0, 'public', 1, 19, NULL, 'Level 2', 'Mackinon', 24),
								(1586, 'Kimong Dispensary', '14878', 'satellite', 'PEPFAR', 'PMTCT', 1701, 116, 1584, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Kurgung', 32),
								(1587, 'Makamini Dispensary', '11545', 'satellite', 'PEPFAR', 'PMTCT', 889, 149, 1585, NULL, 0, 'public', 1, 19, NULL, 'Level 2', 'Makamini ', 24),
								(1588, 'Kimout Dispensary', '14870', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1586, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Kisirichet', 32),
								(1589, 'St Francis Health Centre (Nairobi North)', '13203', 'satellite', 'PEPFAR', 'PMTCT', 998, 166, 1587, NULL, 0, 'mission', 1, 30, NULL, 'Level 2', 'Githurai', 48),
								(1590, 'St. David Clinic', '11031', 'satellite', 'PEPFAR', 'PMTCT', 1728, 187, 1588, 45, 0, 'mission', 1, 35, NULL, 'Not Classified', 'Wanjohi', 27),
								(1591, 'Kinagoni Dispensary', '11479', 'satellite', 'PEPFAR', 'PMTCT', 889, 149, 1589, 636, 0, 'public', 1, 19, NULL, 'Level 2', 'Samburu', 19),
								(1592, 'Fremo Medical Centre', '18612 ', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 163, 1590, NULL, 0, 'private', 1, 30, NULL, 'Not Classified', '', 27),
								(1593, 'Gacharageini Dispensary', '10191', 'satellite', 'PEPFAR', 'PMTCT', 998, 201, 1591, 149, 0, 'public', 1, 29, NULL, 'Level 2', 'Njumbi', 24),
								(1594, 'Kinarani Dispensary', '11481', 'satellite', 'PEPFAR', 'PMTCT', 889, 50, 1592, NULL, 0, 'public', 1, 14, NULL, 'Level 2', 'Mwanamwinga', 24),
								(1596, 'Sosiana Dispensary', '15615', 'satellite', 'PEPFAR', 'PMTCT', 1728, 217, 1594, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Mogor', 24),
								(1597, 'Mama Maria Clinic', '13765', 'satellite', 'PEPFAR', 'PMTCT', NULL, 211, 1595, 112, 0, 'mission', 1, 27, NULL, 'Not Classified', 'East Muhuru', 48),
								(1598, 'Gakawa Dispensary', '10200', 'satellite', 'PEPFAR', 'PMTCT', 998, 183, 1596, NULL, 0, 'public', 1, 36, NULL, 'Level 2', 'Kahurura', 24),
								(1599, 'Song''Onyet Dispensary', '15611', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1597, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Kipteris', 32),
								(1600, 'King''Wal Dispensary', '14887', 'satellite', 'PEPFAR', 'PMTCT', 1701, 144, 1598, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Chepterit', 32),
								(1601, 'Marie Stopes Nursing Home (Muranga)', '10690', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1599, 149, 0, 'ngo', 1, 29, NULL, 'Not Classified', 'Kiharu', 25),
								(1602, 'Sochoi Dispensary', '15603', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1600, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Sochoi', 24),
								(1603, 'Siwo Dispensary', '15598', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1601, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Siwo', 24),
								(1604, 'Mashangwa Dispensary', '16325', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1602, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Mashangwa', 24),
								(1605, 'Masururia Dispensary', '15151', 'satellite', 'PEPFAR', 'PMTCT', 952, 120, 1603, 141, 0, 'public', 1, 33, NULL, 'Level 2', 'Masururia', 24),
								(1606, 'Kipkeibon Dispensary', '14894', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1604, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Tartar', 32),
								(1607, 'Gakurwe Dispensary', '10203', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1605, 149, 0, 'public', 1, 29, NULL, 'Level 2', 'Gaturi', 24),
								(1608, 'Siu Dispensary', '11799', 'satellite', 'PEPFAR', 'PMTCT', NULL, 193, 1606, NULL, 0, 'public', 0, 21, NULL, 'Level 2', 'Siu', 24),
								(1609, 'Matharite Dispensary', '10706', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1607, 150, 0, 'public', 1, 29, NULL, 'Level 2', 'Kiharu', 24),
								(1610, 'Kipkoigen Dispensary', '14900', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1608, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Kibabet', 32),
								(1611, 'Matondoni Dispensary', '11579', 'satellite', 'PEPFAR', 'PMTCT', NULL, 194, 1609, NULL, 0, 'public', 0, 21, NULL, 'Level 2', 'Matondoni', 24),
								(1612, 'Kipkoimet Dispensary', '14901', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1610, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Mogobich', 32),
								(1613, ' Gatangara Dispensary', '10208', 'satellite', 'PEPFAR', 'PMTCT', 998, 282, 1611, 251, 0, 'public', 1, 29, NULL, 'Level 2', 'Kanyenyaini', 24),
								(1614, 'Kipkoror Comm. Disp', '17014', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1612, NULL, 0, 'mission', 1, 32, NULL, 'Level 3', 'Chepkunyuk', 32),
								(1615, 'Sitoi Dispensary', '15597', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1613, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Chemomi', 24),
								(1616, 'Mau Tea Dispensary					', '15158	', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1614, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Cheboswa', 38),
								(1618, 'Gatara Health Centre', '10209 ', 'satellite', 'PEPFAR', 'PMTCT', 998, 57, 1616, 150, 0, 'public', 1, 29, NULL, 'Level 3', 'Murarandia', 24),
								(1619, 'Mbogo Valley', '15164', 'satellite', 'PEPFAR', 'PMTCT', 1701, 119, 1617, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Tinderet', 32),
								(1620, 'Sirwa Dispensary', '15594', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1618, 527, 0, 'ngo', 1, 32, NULL, 'Level 2', 'Kapchorwa', 32),
								(1621, 'Gatei Dispensary', '10210 ', 'satellite', 'PEPFAR', 'PMTCT', 998, 202, 1619, 79, 0, 'public', 1, 36, NULL, 'Level 3', 'Gatei', 24),
								(1622, 'Mbuta Model Health Centre		', '11592', 'satellite', 'PEPFAR', 'PMTCT', 889, 196, 1620, NULL, 0, 'public', 0, 28, NULL, 'Level 2', 'Mtongwe', 24),
								(1623, 'Menet Dispensary										', '15171	', 'satellite', 'PEPFAR', 'PMTCT', 952, 102, 1621, 487, 0, 'public', 1, 2, NULL, 'Level 2', 'Menet', 24),
								(1624, 'Sironoi SDA Dispensary', '17247', 'satellite', 'PEPFAR', 'PMTCT', 1701, 41, 1622, 527, 0, 'private', 1, 32, NULL, 'Level 2', 'Kamoiywo', 48),
								(1625, 'Mentera Dispensary								', '15172', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1623, 510, 0, 'public', 1, 2, NULL, 'Level 2', 'Chilchila', 24),
								(1626, 'Gathaithi Dispensary', '10212 ', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1624, 150, 0, 'public', 1, 29, NULL, 'Level 2', 'Murarandia', 24),
								(1627, 'Gatheru Dispensary', '10216', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1625, 150, 0, 'public', 1, 29, NULL, 'Level 2', 'Kahuhia', 24),
								(1628, 'Gatina Dispensary', '10220', 'satellite', 'PEPFAR', 'PMTCT', 998, 202, 1626, 174, 0, 'ngo', 1, 36, NULL, 'Level 2', 'Gakuyu', 27),
								(1629, 'Nkararo Health Centre', '15362', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1627, 825, 0, 'mission', 1, 33, NULL, 'Level 3', 'Nkararo', 24),
								(1630, 'Kipsamoite Dispensary', '14910', 'satellite', 'PEPFAR', 'PMTCT', 1701, 116, 1628, 78, 0, 'mission', 1, 32, NULL, 'Level 2', 'Sangalo', 24),
								(1631, 'Siret Dispensary ', '15591', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1629, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Siret', 32),
								(1632, 'Gatondo Dispensary (Nyeri North)', '10224 ', 'satellite', 'PEPFAR', 'PMTCT', 998, 202, 1630, 79, 0, 'public', 1, 36, NULL, 'Level 2', 'Iriaini', 24),
								(1633, 'Siongi Dispensary', '15586', 'satellite', 'PEPFAR', 'PMTCT', 1363, 174, 1631, 491, 0, 'public', 1, 12, NULL, 'Level 2', 'Tebesonik', 24),
								(1634, 'Sio Port District Hospital Dispensing Point', '16128', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 133, 1632, 620, 0, 'public', 1, 4, NULL, 'Level 4', 'Nanguba', 3),
								(1635, 'Gikui Health Centre', ' 10256', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1633, 251, 0, 'public', 1, 29, NULL, 'Level 2', 'Iyego', 24),
								(1636, ' Giovanna Dispensary', '12955', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 166, 1634, NULL, 0, 'private', 0, 30, NULL, 'Not Classified', 'Roysambu', 27),
								(1637, ' Gitaro Dispensary', '10258', 'satellite', 'PEPFAR', 'PMTCT', 998, 57, 1635, 150, 0, 'public', 1, 29, NULL, 'Level 2', 'Mugoiri', 24),
								(1638, 'Githagara Health Centre', '10261 ', 'satellite', 'PEPFAR', 'PMTCT', 998, 57, 1636, 150, 0, 'public', 1, 29, NULL, 'Level 3', 'Mugoiri', 24),
								(1639, 'Singawa Medical Centre', '17765', 'satellite', 'PEPFAR', 'PMTCT', 889, 196, 1637, NULL, 0, 'public', 0, 28, NULL, 'Level 2', 'Likoni', 24),
								(1640, 'Silaloni (ilaloni) Dispensary', '11794', 'satellite', 'PEPFAR', 'PMTCT', 889, 149, 1638, 636, 0, 'mission', 1, 19, NULL, 'Level 2', 'Chengoni', 12),
								(1641, 'Gitugi Dispensary (Muranga North)', '10279', 'satellite', 'PEPFAR', 'PMTCT', 998, 201, 1639, 150, 0, 'public', 1, 29, NULL, 'Level 2', 'Kiruri', 24),
								(1642, 'Sigot Dispensary', '15567', 'satellite', 'PEPFAR', 'PMTCT', 1701, 116, 1640, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Lelmokwo', 24),
								(1643, 'GK Prison Dispensary (Murang''a)', '10287', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1641, 149, 0, 'public', 1, 29, NULL, 'Level 2', 'Township', 24),
								(1644, 'Shankoe Dispensary', '15558', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1642, 825, 0, 'private', 1, 33, NULL, 'Level 2', 'Shankoe', 32),
								(1645, 'Shangia Dispensary', '16189', 'satellite', 'PEPFAR', 'PMTCT', 889, 50, 1643, 125, 0, 'public', 1, 14, NULL, 'Level 2', 'Mariakani', 24),
								(1646, 'Setek Dispensary ', '15556', 'satellite', 'PEPFAR', 'PMTCT', 1701, 119, 1644, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Meteitei', 32),
								(1647, 'Segutiet Dispensary', '15540', 'satellite', 'PEPFAR', 'PMTCT', 952, 102, 1645, 487, 0, 'mission', 1, 2, NULL, 'Level 2', 'Chesoen', 12),
								(1648, 'Savimbi Medical Clinic', '16691', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1646, 825, 0, 'private', 1, 33, NULL, 'Level 2', 'Murgan', 12),
								(1649, 'Savani Dispensary ', '15534', 'satellite', 'PEPFAR', 'PMTCT', 952, 115, 1647, 527, 0, 'private', 1, 32, NULL, 'Level 2', 'Chepsire', 32),
								(1650, 'Savani Medical Centre', '11773', 'satellite', 'PEPFAR', 'PMTCT', NULL, 196, 1648, NULL, 0, 'private', 0, 28, NULL, 'Level 2', 'Likoni', 24),
								(1651, 'Santa Maria Medical Clinic', '11770', 'satellite', 'PEPFAR', 'PMTCT', 889, 196, 1649, NULL, 0, 'private', 0, 28, NULL, 'Level 2', 'Likoni', 24),
								(1652, 'Sang''alo Dispensary', '15529', 'satellite', 'PEPFAR', 'PMTCT', 1701, 116, 1650, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Sangalo', 24),
								(1653, 'Rwathia Dispensary', '10986', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1651, 251, 0, 'public', 1, 29, NULL, 'Level 2', 'Rwathia', 24),
								(1654, 'Rwanyambo Dispensary', '10984', 'satellite', 'PEPFAR', 'PMTCT', 1728, 186, 1652, 45, 0, 'private', 1, 35, NULL, 'Not Classified', 'Nyakio', 49),
								(1655, 'Royal Medical Centre', '10965', 'satellite', 'PEPFAR', 'PMTCT', 1728, 186, 1653, 45, 0, 'private', 1, 35, NULL, 'Not Classified', 'Gitiri', 27),
								(1656, 'Rotary Doctors General Outreach', '15500', 'satellite', 'PEPFAR', 'PMTCT', 952, 41, 1654, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Kapsabet', 24),
								(1657, 'Romosha Dispensary', '15491', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1655, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Meguara', 24),
								(1658, 'Rhamudimtu Health Centre', '13424', 'satellite', 'PEPFAR', 'PMTCT', 828, 199, 1656, NULL, 0, 'public', 1, 24, NULL, 'Level 3', 'Rhamudimtu', 24),
								(1659, 'Mombasa CBHC-Chaani', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 63, 1657, 142, 0, 'mission', 1, 28, NULL, 'Not Classified', 'Changamwe', 2),
								(1660, 'Patte Dispensary', '11735', 'satellite', 'PEPFAR', 'ART,PMTCT', NULL, 193, 1658, NULL, 0, 'public', 0, 21, NULL, 'Level 2', 'Patte', 24),
								(1661, 'Mgamboni Dispensary', '11601', 'satellite', 'PEPFAR', 'PMTCT', 889, 50, 1659, NULL, 0, 'private', 1, 14, NULL, 'Level 2', 'Jibana', 27),
								(1662, 'Mikaro Dispensary', '10731', 'satellite', 'PEPFAR', 'PMTCT', 1728, 186, 1660, 45, 0, 'public', 1, 35, NULL, 'Level 2', 'Mikaro', 24),
								(1663, 'Mirogi Health Centre									', '13813', 'satellite', 'PEPFAR', 'PMTCT', 895, 11, 1661, 405, 0, 'mission', 1, 8, NULL, 'Not Classified', 'Mirogi	', 19),
								(1664, 'Mkang''ombe Community Dispensary', '11627', 'satellite', 'PEPFAR', 'PMTCT', 889, 149, 1662, NULL, 0, 'mission', 1, 19, NULL, 'Level 2', 'Ndavaya', 36),
								(1665, 'Mogoiywet Dispensary								', '16327', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1663, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Ololmasani', 24),
								(1666, 'Mokong Tea Dispensary', '15211', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1664, 231, 0, 'public', 1, 32, NULL, 'Level 2', 'Cheptililik', 24),
								(1667, 'Mombwo Dispensary', '15218', 'satellite', 'PEPFAR', 'PMTCT', 1701, 119, 1665, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Kabirer', 25),
								(1668, 'Mosore Dispensary								', '15228', 'satellite', 'PEPFAR', 'PMTCT', 952, 102, 1666, 491, 0, 'public', 1, 2, NULL, 'Level 2', 'Tulwet', 32),
								(1669, 'Mother Amadeas							', '11645', 'satellite', 'PEPFAR', 'PMTCT', 889, 175, 1667, NULL, 0, 'mission', 0, 28, NULL, 'Level 3', 'Mikindani', 19),
								(1670, 'Mtaa Dispensary						', '11662', 'satellite', 'PEPFAR', 'PMTCT', 889, 149, 1668, NULL, 0, 'public', 1, 19, NULL, 'Level 2', 'Mtaa', 19),
								(1671, 'Mtaragon Dispensary					', '15242', 'satellite', 'PEPFAR', 'PMTCT', 895, 110, 1669, 510, 0, 'public', 1, 12, NULL, 'Level 2', 'Kimasian', 32),
								(1672, 'Muhuru Health Centre				', '13833', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 96, 1670, 112, 0, 'public', 1, 27, NULL, 'Level 4', 'East Muhuru', 40),
								(1673, 'Mukeu (AIC) Dispensary				', '10755', 'satellite', 'PEPFAR', 'PMTCT', 1728, 20, 1671, 45, 0, 'mission', 1, 31, NULL, 'Not Classified', 'Karangatha', 48),
								(1674, 'Oyuma Dispensary (Rachuonyo)', '17852', 'satellite', 'PEPFAR', 'PMTCT', 895, 35, 1672, 183, 0, 'public', 1, 8, NULL, 'Level 2', 'Central Karachuonyo', 24),
								(1675, 'Our Lady of Mercy Dispensary', '17497', 'satellite', 'PEPFAR', 'PMTCT', 1728, 186, 1673, 45, 0, 'mission', 1, 35, NULL, 'Level 2', '', 9),
								(1676, 'Ndau Dispensary									', '11700', 'satellite', 'PEPFAR', 'PMTCT', NULL, 193, 1674, NULL, 0, 'public', 0, 21, NULL, 'Level 2', 'Ndau', 24),
								(1677, 'Ndimaini Dispensary			', '10833', 'satellite', 'PEPFAR', 'PMTCT', 998, 202, 1675, 79, 0, 'public', 1, 36, NULL, 'Not Classified', 'Gakuyu', 24),
								(1678, 'Ndubusat/ Bethel Dispensary										', '16669', 'satellite', 'PEPFAR', 'PMTCT', 952, 110, 1676, 510, 0, 'ngo', 1, 12, NULL, 'Level 2', 'Kipteris', 27),
								(1679, 'Ndunyu Njeru Dispensary					', '10840', 'satellite', 'PEPFAR', 'PMTCT', 1728, 186, 1677, 45, 0, 'public', 1, 35, NULL, 'Not Classified', 'Mikaru', 24),
								(1680, 'Nganayio Dispensary								', '15337', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1678, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Olomismis', 48),
								(1681, 'Ngechek Dispensary						', '15342', 'satellite', 'PEPFAR', 'PMTCT', 952, 116, 1679, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Ngechek', 12),
								(1682, 'Ngothi MC					', '17558', 'satellite', 'PEPFAR', 'PMTCT', 1728, 186, 1680, 45, 0, 'public', 1, 35, NULL, 'Level 2', '', 24),
								(1683, 'Njabini Health Centre										', '10878', 'satellite', 'PEPFAR', 'PMTCT', 1728, 20, 1681, 45, 0, 'public', 1, 31, NULL, 'Level 3', 'Njabini', 24),
								(1684, 'Njabini Maternity and Nursing Home																										', '10877', 'satellite', 'PEPFAR', 'PMTCT', 1728, 186, 1682, 45, 0, 'private', 1, 35, NULL, 'Not Classified', 'Njabini ', 0),
								(1685, 'Njipiship Dispensary											', '15356', 'satellite', 'PEPFAR', 'PMTCT', 1728, 217, 1683, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Njipiship', 48),
								(1686, 'Nyangande Dispensary 	', '13923', 'standalone', 'PEPFAR', 'PMTCT', NULL, 2, 1684, NULL, 0, 'public', 0, 17, NULL, 'Level 3', 'Nyangande', 15),
								(1687, 'Nyango Medical Clinic			', '17646', 'satellite', 'PEPFAR', 'PMTCT', 889, 149, 1685, 566, 0, 'ngo', 1, 19, NULL, 'Level 2', 'Vigurungani', 19),
								(1688, 'NYS Dispensary (Kilindini)	', '11723', 'satellite', 'PEPFAR', 'PMTCT', NULL, 196, 1686, NULL, 0, 'public', 0, 28, NULL, 'Level 2', 'Mtongwe', 24),
								(1689, 'Olchobosei Clinic					', '16328', 'satellite', 'PEPFAR', 'PMTCT', 1728, 217, 1687, 825, 0, 'private', 1, 33, NULL, 'Level 2', 'Njipship', 19),
								(1690, 'Oldanyati Health Centre			', '15390', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1688, 825, 0, 'public', 1, 33, NULL, 'Level 3', 'Sikawa', 24),
								(1691, 'Oldebesi Dispensary										', '15391', 'satellite', 'PEPFAR', 'PMTCT', 952, 137, 1689, 215, 0, 'public', 1, 2, NULL, 'Level 2', 'Ndanai	', 32),
								(1692, 'Osupuko Dispensary', '15451', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1690, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Osupuko', 24),
								(1693, 'Osinoni Dispensary', '15448', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1691, 825, 0, 'private', 1, 33, NULL, 'Level 2', 'Osinoni', 24),
								(1694, 'Ollessos Community Dispensary', '15030', 'satellite', 'PEPFAR', 'PMTCT', 1701, 115, 1692, 527, 0, 'public', 1, 32, NULL, 'Level 2', 'Ollessos', 24),
								(1695, 'Oldonyorok (COG) Dispensary', '15394', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1693, 825, 0, 'mission', 1, 33, NULL, 'Level 2', 'Oldonyorok', 32),
								(1696, 'Olereko Dispensary				', '15400', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1694, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Shankoe', 24),
								(1697, 'Mukungi Dispensary											', '10758', 'satellite', 'PEPFAR', 'PMTCT', 1728, 186, 1695, 45, 0, 'public', 1, 35, NULL, 'Not Classified', 'Mukungi', 24),
								(1698, 'Kayole I Health Centre', '13015', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 170, 1696, 376, 0, 'public', 1, 30, NULL, 'Not Classified', 'Kayole', 24),
								(1699, 'Munyaka Dispensary	', '16807', 'satellite', 'PEPFAR', 'PMTCT', 1728, 186, 1697, 45, 0, 'public', 1, 35, NULL, 'Not Classified', 'Munyaka', 24),
								(1700, 'Murarandia Dispensary															', '10778', 'satellite', 'PEPFAR', 'PMTCT', 998, 184, 1698, 150, 0, 'public', 1, 29, NULL, 'Level 2', 'Kahuro	', 24),
								(1701, 'Itigo Dispensary', '14587', 'satellite', 'PEPFAR', 'PMTCT', 1363, 116, 1699, 78, 0, 'public', 1, 32, NULL, 'Level 2', 'Itigo', 24),
								(1702, 'Itiati Dispensary', '10360', 'satellite', 'PEPFAR', 'PMTCT', 998, 202, 1700, 79, 0, 'public', 1, 36, NULL, 'Level 2', 'Konyu', 24),
								(1703, 'Korwenje Dispensary	', '13720', 'satellite', 'PEPFAR', 'PMTCT', 864, 29, 1701, 424, 0, 'public', 1, 17, NULL, 'Level 2', 'North Central Seme', 24),
								(1704, 'Mere Dispensary			', '10722', 'satellite', 'PEPFAR', 'PMTCT', 998, 183, 1702, NULL, 0, 'public', 1, 36, NULL, 'Level 2', 'Mere', 24),
								(1705, 'Mugunda Dispensary			', '10749', 'satellite', 'PEPFAR', 'PMTCT', 998, 183, 1703, NULL, 0, 'public', 1, 36, NULL, 'Level 2', 'Mugunda', 24),
								(1706, 'Mwabila Dispensary				', '11681', 'satellite', 'PEPFAR', 'PMTCT', 889, 149, 1704, 636, 0, 'public', 1, 19, NULL, 'Level 2', 'Mavumbo', 24),
								(1707, 'Mwachinga Medical Clinic				', '11682', 'satellite', 'PEPFAR', 'PMTCT', NULL, 149, 1705, NULL, 0, 'public', 1, 19, NULL, 'Not Classified', 'Kinango', 24),
								(1708, 'Mwanda Dispensary 				', '11687', 'satellite', 'PEPFAR', 'PMTCT', 889, 149, 1706, 566, 0, 'public', 1, 19, NULL, 'Level 2', 'Mwavumbo', 24),
								(1709, 'Mureru Dispensary			', '10780', 'satellite', 'PEPFAR', 'PMTCT', 998, 183, 1707, NULL, 0, 'public', 1, 36, NULL, 'Level 2', 'Gakawa', 24),
								(1710, 'Island Farms Dispensary', '10355', 'satellite', 'PEPFAR', 'PMTCT', 998, 183, 1708, 79, 0, 'public', 1, 36, NULL, 'Level 2', 'Kimahuri', 24),
								(1711, 'Mutarakwa Dispensary (Nyandarua South)				', '10788', 'satellite', 'PEPFAR', 'PMTCT', 1728, 186, 1709, 45, 0, 'public', 1, 35, NULL, 'Level 2', 'Mutarakwa', 24),
								(1712, 'Ndathi Dispensary				', '10830', 'satellite', 'PEPFAR', 'PMTCT', 998, 183, 1710, NULL, 0, 'public', 1, 36, NULL, 'Level 2', 'Ndathi', 24),
								(1713, 'Islamic Relief Agency', '13373', 'satellite', 'PEPFAR', 'PMTCT', 828, 145, 1711, 557, 0, 'public', 1, 7, NULL, 'Level 2', 'Central', 51);
								INSERT INTO `sync_facility` (`id`, `name`, `code`, `category`, `sponsors`, `services`, `manager_id`, `district_id`, `address_id`, `parent_id`, `ordering`, `affiliation`, `service_point`, `county_id`, `hcsm_id`, `keph_level`, `location`, `affiliate_organization_id`) VALUES
								(1714, 'Iruri Dispensary', '10354', 'satellite', 'PEPFAR ', 'PMTCT', 998, 201, 1712, 149, 0, 'public', 1, 29, NULL, 'Level 2', 'Kamacharia', 24),
								(1715, 'IPCC Kaimosi dispensary', '14577', 'satellite', 'PEPFAR', 'PMTCT', 1363, 41, 1713, 78, 0, 'mission', 1, 32, NULL, 'Level 2', 'Kaimosi', 19),
								(1716, 'Ilkerin Dispensary', '14563', 'satellite', 'PEPFAR', 'PMTCT', 1728, 207, 1714, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Ilkerin', 24),
								(1717, 'Ilkerin Dispensary (Trans Mara)', '14564', 'satellite', 'PEPFAR', 'PMTCT', 1728, 120, 1715, 825, 0, 'public', 1, 33, NULL, 'Level 2', 'Murgan', 24),
								(1718, 'Ichichi Dispensary', '10335', 'satellite', 'PEPFAR', 'PMTCT', 998, 282, 1716, 251, 0, 'public', 1, 29, NULL, 'Level 2', 'Kiruri', 24),
								(1719, 'Ibnusina Clinic', '11417', 'satellite', 'PEPFAR', 'PMTCT', 889, 194, 1717, NULL, 0, 'private', 0, 21, NULL, 'Not Classified', 'Langoni', 27),
								(1720, 'Homa Lime Health Centre', '13607', 'satellite', 'PEPFAR', 'PMTCT', 895, 212, 1718, 183, 0, 'public', 1, 8, NULL, 'Level 3', 'West Kadhimu', 24),
								(1721, 'Holy Ghost Dispensary', '18305', 'satellite', 'PEPFAR', 'PMTCT', NULL, 175, 1719, NULL, 0, 'mission', 0, 28, NULL, 'Level 2', 'Changamwe', 19),
								(1722, 'Holy Family Dispensary', '10320', 'satellite', 'PEPFAR', 'PMTCT', 1728, 59, 1720, 45, 0, 'public', 1, 35, NULL, 'Level 2', 'Ndaragwa', 24),
								(1723, 'Greenview Hospital', '14538', 'satellite', 'PEPFAR', 'PMTCT', 952, 108, 1721, 501, 0, 'public', 1, 12, NULL, 'Level 4', 'Township', 24),
								(1724, 'Gither Dispensary', '13349', 'satellite', 'PEPFAR', 'PMTCT', 828, 150, 1722, NULL, 0, 'public', 1, 24, NULL, 'Level 2', 'Gither', 24),
								(1725, 'Kaimosi Mission Hospital', '15913', 'standalone', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', NULL, 34, 1723, NULL, 0, 'mission', 0, 11, NULL, 'Not Classified', '', 48),
								(1726, 'Suna Rabuor Dispensary ', '14135', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 96, 1724, NULL, 0, 'public', 0, 27, NULL, 'Not Classified', 'Suna Rabuor', 24),
								(1727, 'Marura Dispensary', '15145', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 111, 1725, 161, 0, 'public', 1, 20, NULL, 'Level 2', 'Marura', 24),
								(1728, 'St Joseph Catholic Dispensary Sirima (Laikipia East)', '15646', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 283, 1726, 161, 0, 'mission', 1, 20, NULL, 'Level 2', 'Sirima', 9),
								(1729, 'Matanya Dispensary', '15152', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 283, 1727, 161, 0, 'public', 1, 20, NULL, 'Level 2', 'Tigithi', 24),
								(1730, 'Lamuria Dispensary', '15007 ', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 283, 1728, 161, 0, 'public', 1, 20, NULL, 'Level 2', 'Lamuria', 24),
								(1731, 'Muramati Dispensary', '15253 ', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 111, 1729, 161, 0, 'public', 1, 20, NULL, 'Level 2', 'Daiga', 24),
								(1732, 'Ong''ielo Health Centre Dispensing Point', '13987', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 98, 1730, 450, 0, 'public', 1, 38, NULL, 'Level 3', 'East Asembo', 24),
								(1733, 'Asembo Bay Health Clinic (Rarieda)', '18039', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 98, 1731, 450, 0, 'private', 1, 38, NULL, 'Level 2', 'East Asembo', 27),
								(1734, 'Elgeyo Boader Dispensary', '14437', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 103, 1732, 144, 0, 'public', 1, 44, NULL, 'Level 2', 'Tembelio', 24),
								(1735, 'Segera Mission Dispensary', '17029', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 111, 1733, 161, 0, 'mission', 1, 20, NULL, 'Level 2', 'Segera', 12),
								(1736, 'Namboboto Mission Dispensary', '', 'satellite', '', 'ART', NULL, 133, 1734, 620, 0, 'mission', 0, 4, NULL, 'Not Classified', '', 0),
								(1737, 'Ndere Health Centre', '', 'standalone', '', '', NULL, 17, 1735, NULL, 0, '', 0, 38, NULL, 'Level 3', '', 0),
								(1738, 'Kivaani Health Centre', '12376', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 39, 1736, 75, 0, 'public', 1, 22, NULL, 'Level 2', 'Kivaani ', 42),
								(1739, 'Kiptere Dispensary', '14928', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 952, 172, 1737, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Kiptere', 24),
								(1740, 'Nyahera Sub District Hospital', '13880', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 2, 1738, 423, 0, 'public', 0, 17, NULL, 'Level 2', 'North Kisumu', 24),
								(1741, 'Shirikisho Dispensary', '14078', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 212, 1739, 14, 0, 'mission', 1, 8, NULL, 'Not Classified', 'Kowidi', 48),
								(1742, 'Mitubiri Dispensary', '10733', 'standalone', '', 'ART,PMTCT', NULL, 62, 1740, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Mitubiri', 21),
								(1743, 'Kwale DH', '', 'standalone', '', '', NULL, 285, 1741, NULL, 0, '', 0, 19, NULL, 'Not Classified', '', 0),
								(1744, 'APHIA PLUS WESTERN', '', 'standalone', '', '', NULL, 127, 1742, NULL, 0, 'ngo', 0, 11, NULL, 'Not Classified', '', 33),
								(1745, 'APHIA PLUS NAIROBI AND COAST', '', 'standalone', '', '', NULL, 169, 1743, NULL, 0, 'ngo', 0, 30, NULL, 'Not Classified', '', 5),
								(1746, 'IMARISHA ', '', 'standalone', '', '', NULL, 47, 1744, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(1747, 'Kijawa Dispensary', '16765', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 11, 1745, 59, 0, 'public', 1, 8, NULL, 'Level 2', 'Asego', 24),
								(1748, 'Oneno Dispensary', '16985', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 11, 1746, 59, 0, 'public', 1, 8, NULL, 'Level 2', 'Rangwe', 24),
								(1749, 'Kager Dispensary', '13643', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 11, 1747, 59, 0, 'ngo', 1, 8, NULL, 'Not Classified', 'East Kochia', 27),
								(1750, 'Mutithi Health Centre', '10798', 'standalone', '', 'ART', NULL, 56, 1748, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Mwea', 24),
								(1751, 'Chepkoton Dispensary ', '14347', 'satellite', 'PEPFAR', 'PMTCT,PEP,LAB,RTK', NULL, 172, 1749, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Kapsuser', 24),
								(1752, 'Cheronget ', '17144', 'satellite', 'PEPFAR', 'PMTCT,PEP,LAB,RTK', NULL, 172, 1750, 504, 0, 'public', 1, 12, NULL, 'Level 2', '', 24),
								(1753, 'Kalyongwet', '17308', 'satellite', 'PEPFAR', 'PMTCT,PEP,LAB,RTK', NULL, 172, 1751, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Kalyongwet', 24),
								(1754, 'Kamasega Dispensary ', '17209 ', 'satellite', 'PEPFAR', 'PMTCT,PEP,LAB,RTK', NULL, 172, 1752, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Soin', 24),
								(1755, ' Kapchebwai Dispensary', '18064', 'satellite', 'PEPFAR', 'PMTCT,PEP,LAB,RTK', NULL, 108, 1753, 504, 0, 'public', 1, 12, NULL, 'Not Classified', 'Ainamoi', 24),
								(1756, 'Kapkormom Dispensary', '14726', 'satellite', 'PEPFAR', 'PMTCT,PEP,LAB,RTK', 813, 108, 1754, 504, 0, 'private', 1, 12, NULL, 'Level 2', 'Koin', 36),
								(1757, ' Kaplelartet Dispensary', '14738', 'satellite', 'PEPFAR', 'PMTCT,PEP,LAB,RTK', 813, 172, 1755, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Sigowet', 24),
								(1758, 'Kapsiya', '17313', 'satellite', 'PEPFAR', 'PMTCT,PEP,LAB,RTK', NULL, 172, 1756, 504, 0, 'public', 1, 12, NULL, 'Level 2', 'Mobego', 24),
								(1759, 'Kagumo Dispensary', '10402', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 56, 1757, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Mutira', 24),
								(1760, ' St Pius Musoli Health Centre', '16145', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 140, 1758, NULL, 0, 'mission', 0, 11, NULL, 'Not Classified', 'Ikolomani North', 9),
								(1761, 'St Patrick Health Care Centre-Embakasi', '13222', 'satellite', 'Private', 'PMTCT', 998, 170, 1759, 376, 0, 'private', 1, 30, NULL, 'Not Classified', 'Kayole', 27),
								(1762, 'Alice Nursing Home', '12869', 'satellite', 'PEPFAR', 'PMTCT', 998, 170, 1760, 376, 0, 'private', 1, 30, NULL, 'Not Classified', 'Mukuru', 27),
								(1763, 'St Bakhita Dispensary', '', 'satellite', '', 'PMTCT', 998, 170, 1761, 376, 0, 'mission', 1, 30, NULL, 'Not Classified', '', 9),
								(1764, 'St Begson Hospital', '', 'satellite', '', 'PMTCT', 998, 170, 1762, 376, 0, 'mission', 1, 30, NULL, 'Not Classified', '', 0),
								(1765, 'Victory Hospital', '13247', 'satellite', '', 'PMTCT', 998, 170, 1763, NULL, 0, 'private', 1, 30, NULL, 'Not Classified', 'Umoja', 0),
								(1766, 'Mukuru Health Centre', '18463', 'satellite', '', 'PMTCT', 998, 170, 1764, 376, 0, 'public', 1, 30, NULL, 'Not Classified', 'Mukuru', 21),
								(1767, 'Imara health Centre', '17685', 'satellite', 'PEPFAR', 'PMTCT', 998, 170, 1765, 376, 0, 'mission', 1, 30, NULL, 'Not Classified', 'Kwa Njenga', 12),
								(1768, 'APHIA PLUS RIFTVALLEY VMMC ', '', 'standalone', '', '', NULL, 20, 1766, NULL, 0, 'ngo', 0, 31, NULL, 'Not Classified', '', 33),
								(1769, 'Kisegi Sub-District Hospital', '13701', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 99, 1767, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', 'Gwassi North', 24),
								(1770, 'Ndhuru Dispensary', '13842', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 11, 1768, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', 'Lambwe East', 24),
								(1771, 'Magunga Health Centre', '13753', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 99, 1769, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', 'Gwassi Central', 24),
								(1772, 'Nyandiwa Dispensary', '13920', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 99, 1770, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', 'Gwassi West', 24),
								(1773, 'Mbita District Hospital', '13798', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 203, 1771, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', 'Gembe West', 24),
								(1774, 'Ogongo Sub-District Hospital', '13967', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 203, 1772, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', 'Lambwe East', 24),
								(1775, 'Usao Health Centre ', '14162', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 203, 1773, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', 'Gembe East', 24),
								(1776, 'Kitare Health Centre', '13705', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 99, 1774, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', 'Gembe East', 24),
								(1777, 'Sondu Health Centre', '14096', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 101, 1775, NULL, 0, 'public', 0, 17, NULL, 'Not Classified', 'Nyakach', 24),
								(1778, 'Katito Health Centre', '13657', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 210, 1776, NULL, 0, 'public', 0, 17, NULL, 'Not Classified', 'North East Nyakach', 24),
								(1779, 'Couple Counselling Centre-Kenyatta', '18794', 'satellite', '', 'ART,PMTCT,PEP,LAB,RTK', 998, 163, 1777, 90, 0, 'public', 1, 30, NULL, 'Not Classified', 'Kenyatta', 26),
								(1780, 'Kenyatta National Hospital Dispensing Point', '13023', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 163, 1778, 90, 0, 'public', 1, 30, NULL, 'Not Classified', 'Golf Course', 24),
								(1781, 'Kandara Health Centre', '10459', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 57, 1779, NULL, 0, 'public', 0, 29, NULL, 'Not Classified', 'Ithiru', 24),
								(1782, 'Got Mater', '', 'standalone', '', '', NULL, 94, 1780, NULL, 0, '', 0, 17, NULL, 'Not Classified', '', 0),
								(1783, 'Kambajo Dispensary', '', 'standalone', '', '', NULL, 94, 1781, NULL, 0, '', 0, 17, NULL, 'Not Classified', '', 0),
								(1784, 'Kapiyo health Centre', '', 'standalone', '', '', NULL, 94, 1782, NULL, 0, '', 0, 17, NULL, 'Not Classified', '', 0),
								(1785, 'Mageta Island', '', 'standalone', '', '', NULL, 94, 1783, NULL, 0, '', 0, 17, NULL, 'Not Classified', '', 0),
								(1786, 'Nyaguda Dispensary', '', 'standalone', '', '', NULL, 94, 1784, NULL, 0, '', 0, 17, NULL, 'Not Classified', '', 0),
								(1787, 'Nyangoma Dispensary', '', 'standalone', '', '', NULL, 94, 1785, NULL, 0, '', 0, 17, NULL, 'Not Classified', '', 0),
								(1788, 'Iranda Health Centre', '13620', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 46, 1786, NULL, 0, 'public', 0, 16, NULL, 'Not Classified', 'Nyakoe', 24),
								(1789, 'Keumbu S D Hospital', '13680', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 46, 1787, NULL, 0, 'public', 0, 16, NULL, 'Not Classified', 'Keumbu', 24),
								(1790, 'Marani District Hospital', '13772', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 46, 1788, NULL, 0, 'public', 0, 16, NULL, 'Not Classified', 'Mwagichana', 24),
								(1791, 'Oresi Health Centre', '13991', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 46, 1789, NULL, 0, 'public', 0, 16, NULL, 'Not Classified', 'Township', 24),
								(1792, 'Rangala Health Centre', '14033', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 9, 1790, NULL, 0, 'mission', 0, 38, NULL, 'Not Classified', 'South Ugenya', 19),
								(1793, 'Sigomere Health Centre', '14085', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 9, 1791, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'East Uholo', 24),
								(1794, 'Bar Achuth Dispensary', '13495', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 9, 1792, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'North Ugenya', 24),
								(1795, 'Quadalupe Sisters Roret', '15475', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 108, 1793, 791, 0, 'mission', 1, 12, NULL, 'Not Classified', 'Kisiara', 9),
								(1796, 'Tom Mboya Health Centre', ' 14150', 'standalone', '', '', NULL, 203, 1794, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', '', 0),
								(1797, 'Kenyenya SDH', '', 'standalone', '', '', NULL, 46, 1795, NULL, 0, '', 0, 16, NULL, 'Not Classified', '', 0),
								(1798, 'St. Joseph Ombo FBO', '', 'standalone', '', '', NULL, 96, 1796, NULL, 0, '', 0, 27, NULL, 'Not Classified', '', 0),
								(1799, 'Saro Disp.', '', 'standalone', '', '', NULL, 96, 1797, NULL, 0, '', 0, 27, NULL, 'Not Classified', '', 0),
								(1800, 'Arombe Dispensary', '13486', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 822, 96, 1798, 1063, 0, 'public', 1, 27, NULL, 'Not Classified', 'Suna Lower', 24),
								(1801, 'God Jope HC', '', 'standalone', '', '', NULL, 96, 1799, NULL, 0, '', 0, 27, NULL, 'Not Classified', '', 0),
								(1802, 'Isibania Sub-District Hospital', '13625', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 275, 1800, NULL, 0, 'public', 0, 27, NULL, 'Not Classified', 'Bukira West', 24),
								(1803, 'Bugumbe Health Centre ', '13517', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 139, 1801, NULL, 0, 'public', 0, 27, NULL, 'Not Classified', 'Tagare', 24),
								(1804, 'Masaba Health Centre', '13779', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 275, 1802, NULL, 0, 'public', 0, 27, NULL, 'Not Classified', 'Bugumbe North', 24),
								(1805, 'Migosi Health Centre', '13807', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 2, 1803, NULL, 0, 'public', 0, 17, NULL, 'Not Classified', 'Kondele', 21),
								(1806, 'Likoni Catholic Dispensary', '11520', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 196, 1804, NULL, 0, 'mission', 0, 28, NULL, 'Not Classified', 'Mwenza', 19),
								(1807, 'Likoni District Hospital', '11522', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 196, 1805, NULL, 0, 'public', 0, 28, NULL, 'Not Classified', 'Likoni', 24),
								(1808, 'Kagio Catholic Dispensary (Mary Immucate Catholic Dispensary)', '10398', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 56, 1806, NULL, 0, 'mission', 0, 15, NULL, 'Not Classified', 'Mwirua', 19),
								(1809, 'Moi District Hospital Voi', '11641', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 219, 1807, NULL, 0, 'public', 0, 39, NULL, 'Not Classified', 'Voi', 24),
								(1810, 'Mwatate Sub-District Hospital', '11695', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 66, 1808, NULL, 0, 'public', 0, 39, NULL, 'Not Classified', 'Mwatate', 24),
								(1811, 'Ndovu Health Centre', '11705', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 221, 1809, NULL, 0, 'public', 0, 39, NULL, 'Not Classified', 'Voi', 24),
								(1812, 'Sagala Health Centre', '11764', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 66, 1810, NULL, 0, 'public', 0, 39, NULL, 'Not Classified', 'Voi', 24),
								(1813, 'Garashi Dispensary', '11384', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 8, 1811, NULL, 0, 'public', 0, 14, NULL, 'Not Classified', 'Garashi', 24),
								(1814, 'Msambweni District Hospital', '11655', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 135, 1812, NULL, 0, 'public', 0, 19, NULL, 'Not Classified', 'Vingujini', 24),
								(1815, 'Lungalunga Dispensary', '11526', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 135, 1813, NULL, 0, 'public', 0, 19, NULL, 'Not Classified', 'Lunga Lunga', 24),
								(1816, 'Mpukoni Health Centre', '12546', 'standalone', '', '', NULL, 80, 1814, NULL, 0, '', 0, 41, NULL, 'Not Classified', '', 0),
								(1817, 'Kianjuki Health centre', '', 'standalone', '', '', NULL, 80, 1815, NULL, 0, '', 0, 26, NULL, 'Not Classified', '', 0),
								(1818, 'Kibung''a health centre', '12289', 'satellite', '', 'ART,PMTCT,PEP', 889, 80, 1816, 216, 0, 'public', 1, 41, NULL, 'Not Classified', 'Turima', 24),
								(1819, 'Magutuni District Hospital', '12445', 'standalone', '', '', NULL, 80, 1817, NULL, 0, '', 0, 41, NULL, 'Not Classified', '', 0),
								(1820, 'Karurumo RHTC', '12203', 'standalone', '', '', NULL, 177, 1818, NULL, 0, '', 0, 6, NULL, 'Not Classified', '', 0),
								(1821, 'Kianjokoma Sub-District', '12279', 'standalone', '', '', NULL, 177, 1819, NULL, 0, '', 0, 6, NULL, 'Not Classified', '', 0),
								(1822, 'Kikoneni Health Centre', '11472', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 135, 1820, NULL, 0, 'public', 0, 19, NULL, 'Not Classified', 'Kikoneni', 24),
								(1823, 'Kambiti Dispensary', '10445', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 58, 1821, NULL, 0, 'public', 0, 29, NULL, 'Not Classified', 'Kambiti', 24),
								(1824, 'Vanga Health Centre', '11879', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 135, 1822, NULL, 0, 'public', 0, 19, NULL, 'Not Classified', 'Lunga Lunga', 24),
								(1825, 'Bunde health Centre', '', 'standalone', '', '', NULL, 22, 1823, NULL, 0, '', 0, 17, NULL, 'Not Classified', '', 0),
								(1826, 'St. Elizabeth Awasi HC', '', 'standalone', '', '', NULL, 22, 1824, NULL, 0, '', 0, 17, NULL, 'Not Classified', '', 0),
								(1827, 'GK Prisons Kisii', '', 'standalone', '', '', NULL, 46, 1825, NULL, 0, '', 0, 16, NULL, 'Not Classified', '', 0),
								(1828, 'Kanja Health Centre', '12179', 'standalone', '', '', NULL, 177, 1826, NULL, 0, '', 0, 6, NULL, 'Not Classified', '', 0),
								(1829, 'Nembure Health Centre', '12642', 'standalone', '', '', NULL, 26, 1827, NULL, 0, '', 0, 6, NULL, 'Not Classified', '', 0),
								(1830, 'Kikumini Dispensary', '12307', 'standalone', '', '', NULL, 12, 1828, NULL, 0, '', 0, 22, NULL, 'Not Classified', '', 0),
								(1831, 'Kasikeu Dispensary', '12208', 'standalone', 'PEPFAR', 'ART,PMTCT', 828, 77, 1829, NULL, 0, 'public', 0, 23, NULL, 'Not Classified', 'Kasikeu', 24),
								(1832, 'Mbenuu H. Centre', '12499', 'standalone', '', '', NULL, 85, 1830, NULL, 0, '', 0, 23, NULL, 'Not Classified', '', 0),
								(1833, 'Mt. Zion Community Health Clinic', '17269', 'standalone', '', '', NULL, 85, 1831, NULL, 0, '', 0, 23, NULL, 'Not Classified', '', 0),
								(1834, 'Kavuthu H/Centre', '12263', 'standalone', '', '', NULL, 85, 1832, NULL, 0, '', 0, 23, NULL, 'Not Classified', '', 0),
								(1835, 'Kadenge HC', '', 'standalone', '', '', NULL, 9, 1833, NULL, 0, '', 0, 38, NULL, 'Not Classified', '', 0),
								(1836, 'Kaluo HC', '', 'standalone', '', '', NULL, 9, 1834, NULL, 0, '', 0, 38, NULL, 'Not Classified', '', 0),
								(1837, 'Kogelo dispensary', '', 'standalone', '', '', NULL, 9, 1835, NULL, 0, '', 0, 38, NULL, 'Not Classified', '', 0),
								(1838, 'Ligega HC', '', 'standalone', '', '', NULL, 9, 1836, NULL, 0, '', 0, 38, NULL, 'Not Classified', '', 0),
								(1839, 'Rabar Dispensary', '', 'standalone', '', '', NULL, 9, 1837, NULL, 0, '', 0, 38, NULL, 'Not Classified', '', 0),
								(1840, 'Ngiya mission', '', 'standalone', '', '', NULL, 9, 1838, NULL, 0, '', 0, 38, NULL, 'Not Classified', '', 0),
								(1841, 'Urenga Disp.', '', 'standalone', '', '', NULL, 9, 1839, NULL, 0, '', 0, 38, NULL, 'Not Classified', '', 0),
								(1842, 'Tingwangi HC', '', 'standalone', '', '', NULL, 9, 1840, NULL, 0, '', 0, 38, NULL, 'Not Classified', '', 0),
								(1843, 'Sikalame Disp.', '', 'standalone', '', '', NULL, 9, 1841, NULL, 0, '', 0, 38, NULL, 'Not Classified', '', 0),
								(1844, 'Rwamba HC', '', 'standalone', '', '', NULL, 9, 1842, NULL, 0, '', 0, 38, NULL, 'Not Classified', '', 0),
								(1845, 'Kalala Dispensary', '12143', 'standalone', '', '', NULL, 83, 1843, NULL, 0, '', 0, 22, NULL, 'Not Classified', '', 0),
								(1846, 'Anginya Mission Hospital', '', 'standalone', '', '', NULL, 208, 1844, NULL, 0, '', 0, 8, NULL, 'Not Classified', '', 0),
								(1847, 'Emmanuel Community Health Clinic', '17574', 'standalone', 'PEPFAR', '', NULL, 183, 1845, NULL, 0, 'private', 0, 36, NULL, 'Not Classified', 'Ruguru', 0),
								(1848, 'Njukini Health Centre', '11718', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 66, 1846, NULL, 0, 'public', 0, 39, NULL, 'Not Classified', 'Njukini', 24),
								(1849, 'Challa Dispensary', '11278', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 66, 1847, NULL, 0, 'public', 0, 39, NULL, 'Not Classified', 'Challa', 24),
								(1850, 'Kitobo Dispensary (Taveta)', '11491', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 66, 1848, NULL, 0, 'public', 0, 39, NULL, 'Not Classified', 'Kimorigho', 24),
								(1851, 'Mata Dispensary (Taveta)', '11577', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 66, 1849, NULL, 0, 'public', 0, 39, NULL, 'Not Classified', 'Mata', 24),
								(1852, 'Tiwi RHTC', '11853', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 285, 1850, NULL, 0, 'public', 0, 19, NULL, 'Not Classified', 'Tiwi', 24),
								(1853, 'Tudor District Hospital (Mombasa)', ' 11861', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 205, 1851, NULL, 0, 'public', 0, 28, NULL, 'Not Classified', 'Tudor', 24),
								(1854, 'Shimo-La Tewa Health Centre (GK Prison)', '11395', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 188, 1852, NULL, 0, 'public', 0, 28, NULL, 'Not Classified', 'Bamburi', 24),
								(1855, 'Jocham Hospital', '11434', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 188, 1853, NULL, 0, 'private', 0, 28, NULL, 'Not Classified', 'Kisauni', 27),
								(1856, 'Mvita Dispensary', '11679', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 205, 1854, NULL, 0, 'public', 0, 28, NULL, 'Not Classified', 'Majengo', 24),
								(1857, 'Mwembe Tayari Staff Clinic', '11697', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 205, 1855, NULL, 0, 'public', 0, 28, NULL, 'Not Classified', 'Mwembe Tayari', 24),
								(1858, 'Bamburi Dispensary', '11239', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 188, 1856, NULL, 0, 'public', 0, 28, NULL, 'Not Classified', 'Bamburi', 24),
								(1859, 'State House Dispensary (Mombasa)', '11831', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 205, 1857, NULL, 0, 'public', 0, 28, NULL, 'Not Classified', 'Ganjoni', 24),
								(1860, 'Ganze Health Centre', '11383', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 8, 1858, NULL, 0, 'public', 0, 14, NULL, 'Not Classified', 'Ganze', 24),
								(1861, 'Mpeketoni Health Sevices(Witu)', ' 17694', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 152, 1859, NULL, 0, 'private', 0, 21, NULL, 'Not Classified', 'Witu', 27),
								(1862, 'Maria Teressa Nuzzo Health Centre', '11565', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 152, 1860, NULL, 0, 'mission', 0, 21, NULL, 'Not Classified', 'Baharini', 19),
								(1863, 'Garsen Health Centre', '11385', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 153, 1861, NULL, 0, 'public', 0, 40, NULL, 'Not Classified', 'Bilisa', 24),
								(1864, 'Ngao District Hospital', '11711', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 153, 1862, NULL, 0, 'public', 0, 40, NULL, 'Not Classified', 'Ngao', 24),
								(1865, 'Mnazini Dispensary', '11637', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 153, 1863, NULL, 0, 'public', 0, 40, NULL, 'Not Classified', 'Ndera', 24),
								(1866, 'Oda Dispensary', '11725', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 153, 1864, NULL, 0, 'public', 0, 40, NULL, 'Not Classified', 'Wachu Oda', 24),
								(1867, 'Semikaro Dispensary', '11778', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 153, 1865, NULL, 0, 'public', 0, 40, NULL, 'Not Classified', 'Chara', 24),
								(1868, 'Wema Catholic Dispensary', '11901', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 153, 1866, NULL, 0, 'mission', 0, 40, NULL, 'Not Classified', 'Salama', 48),
								(1869, 'Kipini Dispensary', '', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 153, 1867, NULL, 0, 'public', 0, 40, NULL, 'Not Classified', '', 24),
								(1870, 'Shimba Hills Health Centre', '11787', 'standalone', '', 'ART', NULL, 285, 1868, NULL, 0, 'public', 0, 19, NULL, 'Not Classified', '', 24),
								(1871, 'Kizibe Dispensary', '11495', 'standalone', '', 'ART', NULL, 285, 1869, NULL, 0, 'public', 0, 19, NULL, 'Not Classified', '', 24),
								(1872, 'Magodzoni Dispensary', '11537', 'standalone', '', 'ART', NULL, 285, 1870, NULL, 0, 'public', 0, 19, NULL, 'Not Classified', '', 24),
								(1873, 'Buguta health Centre', '', 'standalone', '', '', NULL, 66, 1871, NULL, 0, '', 0, 39, NULL, 'Not Classified', '', 0),
								(1874, 'Kipini Health Centre', '11484', 'standalone', '', 'ART', NULL, 153, 1872, NULL, 0, 'public', 0, 39, NULL, 'Not Classified', '', 0),
								(1875, 'Maisha House VCT(NOSET)', ' 19308', 'satellite', 'PEPFAR', 'ART', 998, 163, 1873, 584, 0, 'ngo', 1, 30, NULL, 'Not Classified', 'Ngara', 25),
								(1876, 'Gatimbi Health Center', '12031', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 101, 1874, NULL, 0, 'public', 0, 26, NULL, 'Not Classified', 'Kirigara', 24),
								(1877, 'Kibirichia Health Center', '', 'standalone', '', '', NULL, 32, 1875, NULL, 0, '', 0, 26, NULL, 'Not Classified', '', 0),
								(1878, 'Cottolengo Mission Hospital', '', 'standalone', '', '', NULL, 32, 1876, NULL, 0, '', 0, 26, NULL, 'Not Classified', '', 0),
								(1879, 'Kavutuu Health Center', '', 'standalone', '', '', NULL, 101, 1877, NULL, 0, '', 0, 23, NULL, 'Not Classified', '', 0),
								(1880, 'Kilala Health Center', '', 'standalone', '', '', NULL, 77, 1878, NULL, 0, '', 0, 23, NULL, 'Not Classified', '', 0),
								(1881, 'Kyuasini Dispensary', '', 'standalone', '', '', NULL, 101, 1879, NULL, 0, '', 0, 23, NULL, 'Not Classified', '', 0),
								(1882, 'Mutituni Health Center', '', 'standalone', '', '', NULL, 101, 1880, NULL, 0, '', 0, 23, NULL, 'Not Classified', '', 0),
								(1883, 'Muumandu  Health Center', '', 'standalone', '', '', NULL, 101, 1881, NULL, 0, '', 0, 22, NULL, 'Not Classified', '', 0),
								(1884, 'Kola Health Center', '', 'standalone', '', '', NULL, 101, 1882, NULL, 0, '', 0, 22, NULL, 'Not Classified', '', 0),
								(1885, 'Kalama Health Center', '', 'standalone', '', '', NULL, 101, 1883, NULL, 0, '', 0, 22, NULL, 'Not Classified', '', 0),
								(1886, 'Mua Dispensary', '', 'standalone', '', '', NULL, 101, 1884, NULL, 0, '', 0, 23, NULL, 'Not Classified', '', 0),
								(1887, 'Kakuyuni Health Center', '', 'satellite', '', '', NULL, 101, 1885, NULL, 0, '', 0, 22, NULL, 'Not Classified', '', 0),
								(1888, 'Bissel Health Centre', '', 'standalone', '', '', NULL, 106, 1886, NULL, 0, '', 0, 10, NULL, 'Not Classified', '', 0),
								(1889, 'Ongata Rongai Health Centre', '', 'standalone', '', '', NULL, 106, 1887, NULL, 0, '', 0, 10, NULL, 'Not Classified', '', 0),
								(1890, 'Isinya Health Centre', '', 'standalone', '', '', NULL, 276, 1888, NULL, 0, '', 0, 10, NULL, 'Not Classified', '', 0),
								(1891, 'Masimba Health Centre', '', 'standalone', '', '', NULL, 276, 1889, NULL, 0, '', 0, 10, NULL, 'Not Classified', '', 0),
								(1892, 'Namanga Health centre', '', 'standalone', '', '', NULL, 106, 1890, NULL, 0, '', 0, 10, NULL, 'Not Classified', '', 0),
								(1893, 'Magadi Hospital', '', 'standalone', '', '', NULL, 106, 1891, NULL, 0, '', 0, 10, NULL, 'Not Classified', '', 0),
								(1894, 'Majimbo Health Centre', '', 'standalone', '', '', NULL, 106, 1892, NULL, 0, '', 0, 9, NULL, 'Not Classified', '', 0),
								(1895, 'Kitengela medical Services', '', 'standalone', '', '', NULL, 276, 1893, NULL, 0, '', 0, 10, NULL, 'Not Classified', '', 0),
								(1896, 'Kitengela Health Centre', '', 'standalone', '', '', NULL, 106, 1894, NULL, 0, '', 0, 10, NULL, 'Not Classified', '', 0),
								(1897, 'Ray Drop in Centre', '', 'standalone', '', '', NULL, 276, 1895, NULL, 0, '', 0, 10, NULL, 'Not Classified', '', 0),
								(1898, 'AIC Dispensary-Kajiado', '', 'standalone', '', '', NULL, 106, 1896, NULL, 0, '', 0, 10, NULL, 'Not Classified', '', 0),
								(1899, 'Ngong SDH', '', 'standalone', '', '', NULL, 106, 1897, NULL, 0, '', 0, 10, NULL, 'Not Classified', '', 0),
								(1900, 'Molo District  Hospital', '', 'standalone', '', '', NULL, 114, 1898, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1901, 'Elburgon DH', '', 'standalone', '', '', NULL, 114, 1899, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1902, 'St Joseph Nursing Home-Molo', '', 'standalone', '', '', NULL, 114, 1900, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1903, 'Mau Narok Health Centre', '', 'standalone', '', '', NULL, 144, 1901, NULL, 0, '', 0, 33, NULL, 'Not Classified', '', 0),
								(1904, 'Lare Health Centre', '', 'standalone', '', '', NULL, 114, 1902, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1905, 'Egerton University Hospital', '', 'standalone', '', '', NULL, 114, 1903, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1906, 'Njoro Health Centre', '', 'standalone', '', '', NULL, 114, 1904, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1907, 'Olenguruone SDH', '', 'standalone', '', '', NULL, 278, 1905, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1908, 'Keringet HC', '', 'standalone', '', '', NULL, 278, 1906, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1909, 'St Elizabeth HC', '', 'standalone', '', '', NULL, 144, 1907, NULL, 0, '', 0, 33, NULL, 'Not Classified', '', 0),
								(1910, 'Ewaso Dispensary', '14483', 'satellite', '', 'ART,PMTCT', 1728, 112, 1908, 514, 0, 'public', 1, 20, NULL, 'Not Classified', 'Loiborsoit', 24),
								(1911, 'Aitong HC', '', 'standalone', '', '', NULL, 144, 1909, NULL, 0, '', 0, 33, NULL, 'Not Classified', '', 0),
								(1912, 'Narok District Hospital', '15311', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 144, 1910, NULL, 0, 'public', 0, 33, NULL, 'Not Classified', '', 0),
								(1913, 'Talek HC', '', 'standalone', '', '', NULL, 207, 1911, NULL, 0, '', 0, 33, NULL, 'Not Classified', '', 0),
								(1914, 'Sogoo HC', '', 'standalone', '', '', NULL, 207, 1912, NULL, 0, '', 0, 32, NULL, 'Not Classified', '', 0),
								(1915, 'Nasosura health Centre', '', 'standalone', '', '', NULL, 207, 1913, NULL, 0, '', 0, 33, NULL, 'Not Classified', '', 0),
								(1917, 'Entasekaa Health Centre', '', 'standalone', '', '', NULL, 207, 1915, NULL, 0, '', 0, 33, NULL, 'Not Classified', '', 0),
								(1918, 'Ololunga District hospital', '', 'standalone', '', '', NULL, 207, 1916, NULL, 0, '', 0, 33, NULL, 'Not Classified', '', 0),
								(1919, 'Enabelbel Health Centre', '14453', 'standalone', '', '', NULL, 144, 1917, NULL, 0, '', 0, 33, NULL, 'Not Classified', '', 0),
								(1920, 'Ntulele Dispensary', '15367', 'standalone', '', '', NULL, 144, 1918, NULL, 0, '', 0, 33, NULL, 'Not Classified', '', 0),
								(1921, 'Oljorre Dispensary', ' 17786', 'standalone', '', '', NULL, 144, 1919, NULL, 0, '', 0, 33, NULL, 'Not Classified', '', 0),
								(1922, 'Olokurto Health Centre', '15420', 'standalone', '', '', NULL, 144, 1920, NULL, 0, '', 0, 33, NULL, 'Not Classified', '', 0),
								(1923, 'Oserian Health Centre', ' 15447', 'standalone', '', '', NULL, 51, 1921, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1924, 'Rhein Valley Hospital', '15483', 'standalone', '', '', NULL, 144, 1922, NULL, 0, '', 0, 33, NULL, 'Not Classified', '', 0),
								(1925, 'Rocco Dispensary', '15489', 'standalone', '', '', NULL, 51, 1923, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1926, 'Maiela Health Centre', '15106', 'standalone', '', '', NULL, 51, 1924, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1927, 'Kabarak Health Centre', '14606', 'standalone', '', '', NULL, 20, 1925, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1928, 'Piave Dispensary', '15462', 'standalone', '', '', NULL, 20, 1926, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1929, 'Langa Langa Health Centre', '15009', 'standalone', '', '', NULL, 20, 1927, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1930, 'Tulwet HC', '15747', 'satellite', 'PEPFAR', 'PMTCT,PEP,LAB,RTK', NULL, 121, 1928, 540, 0, 'public', 1, 42, NULL, 'Level 2', 'Waitaluk', 3),
								(1931, 'Weonia Dispensary', '', 'satellite', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 159, 1929, 540, 0, 'public', 1, 42, NULL, 'Not Classified', '', 0),
								(1932, 'Tot SDH', '', 'standalone', '', '', NULL, 4, 1930, NULL, 0, '', 0, 5, NULL, 'Not Classified', '', 0),
								(1933, 'Chebiemit DH', '', 'standalone', '', '', NULL, 4, 1931, NULL, 0, '', 0, 5, NULL, 'Not Classified', '', 0),
								(1934, 'Kapsowar Mission Hospital', '', 'standalone', '', '', NULL, 4, 1932, NULL, 0, '', 0, 5, NULL, 'Not Classified', '', 0),
								(1935, 'Kapcherop Health Centre', '', 'standalone', '', '', NULL, 4, 1933, NULL, 0, '', 0, 5, NULL, 'Not Classified', '', 0),
								(1936, 'Arror Health Centre', '', 'standalone', '', '', NULL, 4, 1934, NULL, 0, '', 0, 5, NULL, 'Not Classified', '', 0),
								(1937, 'Barwessa Health Centre', '14243', 'satellite', 'PEPFAR', 'ART,PMTCT', 1728, 280, 1935, 605, 0, 'public', 1, 1, NULL, 'Not Classified', 'Lawan', 42),
								(1938, 'Kipsaraman Dispensary', '14912', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 280, 1936, 605, 0, 'mission', 1, 1, NULL, 'Not Classified', 'Kipsaraman', 19),
								(1939, 'Kapsara District Hospital', ' 14753', 'standalone', '', '', NULL, 159, 1937, NULL, 0, '', 0, 42, NULL, 'Not Classified', '', 0),
								(1940, 'Endebess Sub-District Hospital', '14455', 'standalone', '', '', NULL, 191, 1938, NULL, 0, '', 0, 42, NULL, 'Not Classified', '', 0),
								(1941, 'Upper Solai Health Centre', '15763', 'standalone', '', '', NULL, 20, 1939, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1942, 'Mogotio RHDC', '15200', 'standalone', '', '', NULL, 20, 1940, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1943, 'Family Health options Kenya (Nakuru)', '14177', 'standalone', '', '', NULL, 20, 1941, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1944, 'Kapkures Dispensary (Nakuru Central)', '14733', 'standalone', '', '', NULL, 20, 1942, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1945, 'Nakuru West (PCEA) Health Centre', '15290', 'standalone', '', '', NULL, 20, 1943, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1946, 'Prison Dispensary Nakuru', '15470', 'standalone', '', '', NULL, 20, 1944, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1947, 'FITC Dispensary', ' 14498', 'standalone', '', '', NULL, 20, 1945, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1948, 'Naivasha Max Prison Health Centre', '15281', 'standalone', '', '', NULL, 51, 1946, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1949, 'Naroosura Health Centre', '15312', 'standalone', '', '', NULL, 207, 1947, NULL, 0, '', 0, 33, NULL, 'Not Classified', '', 0),
								(1950, 'Sher Hospital', '', 'standalone', '', '', NULL, 51, 1948, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1951, 'Nachohag Health Center', '', 'standalone', '', '', NULL, 51, 1949, NULL, 0, '', 0, 31, NULL, 'Not Classified', '', 0),
								(1952, 'Sengani Dispensary', '16440', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 12, 1950, 75, 0, 'public', 1, 22, NULL, 'Not Classified', 'Tala', 24),
								(1953, 'Mkongani Dispensary', '11629', 'standalone', '', '', NULL, 285, 1951, NULL, 0, 'public', 0, 19, NULL, 'Not Classified', 'Mkongani', 24),
								(1954, 'Rangwe (SDA) Dispensary', '14035', 'satellite', 'PEPFAR', 'ART,PMTCT', 864, 11, 1952, 59, 0, 'mission', 1, 8, NULL, 'Level 2', 'West Gem', 12),
								(1955, 'Budonga Dispensary', ' 15812', 'standalone', '', '', NULL, 36, 1953, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', '', 24),
								(1956, 'Bushiri Health Centre', '15833', 'standalone', '', '', NULL, 36, 1954, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', '', 24),
								(1957, 'Shikusa Health Centre', '16112', 'standalone', '', '', NULL, 36, 1955, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', '', 24),
								(1958, 'Shamakhubu Health Centre', '16104', 'standalone', '', '', NULL, 36, 1956, NULL, 0, '', 0, 11, NULL, 'Not Classified', '', 0),
								(1959, 'Chombeli Health Centre', '15859', 'standalone', '', '', NULL, 140, 1957, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', '', 24),
								(1960, 'Kilingili Health Centre', '15945', 'standalone', '', '', NULL, 129, 1958, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', '', 24),
								(1961, 'Mechimeru Dispensary', '16014', 'standalone', '', '', NULL, 19, 1959, NULL, 0, 'public', 0, 3, NULL, 'Not Classified', '', 24),
								(1962, 'Bukembe Dispensary', '15819', 'standalone', '', '', NULL, 19, 1960, NULL, 0, '', 0, 3, NULL, 'Not Classified', '', 0),
								(1963, 'Bumula Health Centre', '15825', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 19, 1961, NULL, 0, 'public', 0, 3, NULL, 'Not Classified', '', 24),
								(1964, 'Kimaeti Dispensary', '15947', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 30, 1962, NULL, 0, 'public', 0, 3, NULL, 'Not Classified', 'Kimaeti', 24),
								(1965, 'Nasusi Dispensary', '16076', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 138, 1963, NULL, 0, 'public', 0, 3, NULL, 'Not Classified', 'Maeni', 24),
								(1966, 'Makhonge Health Centre', '15990', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 138, 1964, 98, 0, 'public', 1, 3, NULL, 'Not Classified', 'Kamukuywa', 24),
								(1967, 'Karima Dispensary', '15927', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 124, 1965, NULL, 0, 'public', 0, 3, NULL, 'Not Classified', '', 24),
								(1968, 'Sabatia Health Centre', '', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 43, 1966, NULL, 0, '', 0, 45, NULL, 'Not Classified', '', 0),
								(1969, 'Vihiga Health Centre', '16158', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 43, 1967, NULL, 0, 'public', 0, 45, NULL, 'Not Classified', 'C Maragoli', 24),
								(1970, 'Lyanaginga Health Centre', '15982', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 43, 1968, NULL, 0, 'public', 0, 45, NULL, 'Not Classified', 'Mungoma', 24),
								(1971, 'Tigoi Health Centre', '16151', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 34, 1969, NULL, 0, '', 0, 45, NULL, 'Not Classified', '', 0),
								(1972, 'Kuvasali Health Center', '15959', 'standalone', '', '', NULL, 140, 1970, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', 'Chemuche', 24),
								(1973, 'Banja Health Centre', '15805', 'standalone', '', '', NULL, 43, 1971, NULL, 0, 'public', 1, 45, NULL, 'Not Classified', '', 0),
								(1974, 'Malakisi Health Centre', '15994', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 286, 1972, NULL, 0, '', 0, 3, NULL, 'Not Classified', 'Malakisi', 0),
								(1975, 'Shiraha Health Centre', '16116', 'standalone', '', '', NULL, 21, 1973, NULL, 0, '', 1, 11, NULL, 'Not Classified', '', 0),
								(1976, 'Shikunga Health Centre', '16111', 'standalone', '', '', NULL, 21, 1974, NULL, 0, '', 1, 11, NULL, 'Not Classified', '', 0),
								(1977, 'Sirisia Hospital', '16130', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 286, 1975, NULL, 0, 'public', 0, 3, NULL, 'Not Classified', '', 24),
								(1978, 'Lukoye Health Centre', '17298', 'standalone', '', '', NULL, 21, 1976, NULL, 0, '', 1, 11, NULL, 'Not Classified', '', 0),
								(1979, 'Matayos Health Centre', '16004', 'satellite', '', '', NULL, 21, 1977, 28, 0, '', 1, 4, NULL, 'Not Classified', '', 0),
								(1980, 'Kabuchai Health Centre', '15911', 'standalone', '', '', NULL, 24, 1978, NULL, 0, 'public', 0, 3, NULL, 'Not Classified', '', 24),
								(1981, 'Lung''anyiro Dispensary', '15972', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 36, 1979, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', 'Matungu', 24),
								(1982, 'Khalaba Health Centre', '15931', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 36, 1980, NULL, 0, 'public', 0, 11, NULL, 'Not Classified', 'Matungu', 24),
								(1983, 'Mumias Dispensary', '16035', 'central', 'PEPFAR', 'ART,PMTCT,LAB', NULL, 18, 1981, NULL, 1, 'public', 0, 11, NULL, 'Not Classified', 'Lureko', 24),
								(1984, 'Mukuyu Dispensary', ' 16031', 'satellite', 'PEPFAR', 'ART,PMTCT,LAB', 874, 45, 1982, 109, 0, 'public', 1, 11, NULL, 'Not Classified', 'Mautuma', 24),
								(1985, 'St Charles Lwanga Health Centre', '15957', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 45, 1983, 109, 0, 'mission', 1, 11, NULL, 'Not Classified', 'Lugari', 19),
								(1986, 'Matunda Sub-District Hospital', '16008', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 45, 1984, 107, 0, 'public', 1, 11, NULL, 'Not Classified', ' Nzoia', 24),
								(1987, 'Chwele Friends Dispensary', '15861', 'standalone', '', '', NULL, 286, 1985, NULL, 0, '', 1, 3, NULL, 'Not Classified', '', 0),
								(1988, 'Coptic Nursing Home', '15862', 'standalone', '', '', NULL, 27, 1986, NULL, 0, '', 1, 45, NULL, 'Not Classified', '', 0),
								(1989, 'Matungu Sub-District Hospital', '16037', 'standalone', '', '', NULL, 18, 1987, NULL, 0, '', 1, 11, NULL, 'Not Classified', '', 0),
								(1990, 'Jaffrey Medical Centre', '', 'standalone', '', '', NULL, 16, 1988, NULL, 0, 'private', 0, 28, NULL, 'Not Classified', 'Ganjoni', 27),
								(1991, 'Nyakweri Dispensary', '18420', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 203, 1989, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', '', 24),
								(1992, 'Gakoe Health Centre', '10202', 'standalone', '', '', NULL, 31, 1990, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Mangu', 24),
								(1993, 'Katakani Dispensary', '12213', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 73, 1991, 338, 0, 'mission', 1, 18, NULL, 'Not Classified', 'Kyuso', 48),
								(1994, 'Nyalunya Dispensary ', '13890', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 2, 1992, NULL, 0, 'public', 0, 17, NULL, 'Not Classified', 'Kolwa Central', 24),
								(1995, 'AHF Soko Clinic', '18804', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 188, 1993, 34, 0, 'ngo', 1, 28, NULL, 'Not Classified', 'Kongowea', 25),
								(1996, 'Field Marsham Flouspar Medical Centre', '', 'satellite', '', 'ART,PMTCT,PEP,LAB', 1363, 107, 1994, 2051, 0, 'private', 1, 5, NULL, 'Not Classified', 'Chemoibon', 3),
								(1997, 'Mombasa Hospital', '11643', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 16, 1995, NULL, 0, 'private', 0, 28, NULL, 'Not Classified', '', 27),
								(1998, 'Osingo Dispensary', '13996', 'standalone', '', 'ART,PMTCT,PEP', NULL, 99, 1996, NULL, 0, 'public', 0, 27, NULL, 'Not Classified', '', 24),
								(1999, 'Sweet Waters Dispensary', '15694', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 283, 1997, 161, 0, 'public', 1, 20, NULL, 'Level 2', 'Marura', 24),
								(2000, 'St Hillarias Medical Clinic', '11816', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 175, 1998, 181, 0, 'private', 1, 28, NULL, 'Not Classified', 'Chaani', 27),
								(2001, 'Eldama Ravine District Hospital', '14432', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 37, 1999, NULL, 0, 'public', 0, 1, NULL, 'Not Classified', 'Eldama Ravine', 24),
								(2002, 'GK Farm Prisons Dispensary (Trans Nzoia)', '14514', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 121, 2000, 540, 0, 'public', 1, 42, NULL, 'Not Classified', 'Tunaini', 24),
								(2003, 'Nairobi Women''s Hospital Dispensing Point', '13117', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 163, 2001, 157, 0, 'private', 1, 30, NULL, 'Level 2', 'Kilimani', 27),
								(2004, 'Nairobi Women''s Hospital Adams', '16795', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 998, 163, 2002, 157, 0, 'private', 1, 30, NULL, 'Level 2', 'Adams Arcade', 27),
								(2005, 'Nairobi Women''s Hospital Eastleigh', '', 'satellite', 'PEPFAR', 'ART,PEP', 998, 165, 2003, 157, 0, 'private', 1, 30, NULL, 'Level 2', 'Eastleigh', 27),
								(2006, 'Nairobi Women''s Hospital Rongai ', '18195', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 998, 167, 2004, 157, 0, 'private', 1, 30, NULL, 'Level 2', 'Rongai', 27),
								(2007, 'Nairobi Women''s Hospital Kitengela', '', 'satellite', 'PEPFAR', 'PEP', 998, 170, 2005, 157, 0, 'private', 1, 30, NULL, 'Level 2', 'Kitengela', 27),
								(2008, 'Nairobi Women''s Hospital Nakuru', '', 'satellite', 'PEPFAR', 'ART,PEP', 998, 20, 2006, 157, 0, 'private', 1, 31, NULL, 'Level 2', 'Nakuru', 27),
								(2009, 'Kianyaga Sub-District Hospital', '10565', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 56, 2007, NULL, 0, 'public', 0, 15, NULL, 'Level 4', 'Baragwi', 24),
								(2010, 'Kisiiki Dispensary', '12347', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 89, 2008, NULL, 0, 'public', 0, 22, NULL, 'Not Classified', 'Mavoloni', 24),
								(2011, 'Huruma Health Centre (Laikipia East)', '14553', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 42, 2009, NULL, 0, 'mission', 1, 36, NULL, 'Level 2', 'Nanyuki', 19),
								(2012, 'Kamumu Dispensary', '12164', 'satellite', 'PEPFAR', 'PMTCT,PEP', NULL, 142, 2010, 137, 0, 'public', 1, 6, NULL, 'Level 2', 'Kiang''ombe', 24),
								(2013, 'Kokwanyo Dispensary ', '13712', 'satellite', 'PEPFAR', '', 895, 35, 2011, 183, 0, 'public', 1, 8, NULL, 'Level 2', 'Kokwanyo', 24),
								(2014, 'Chemolingot District Hospital', '14321', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 101, 2012, NULL, 1, 'public', 0, 1, NULL, 'Not Classified', 'Kositei', 24),
								(2015, 'Nginyang Health Centre', '15347', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 101, 2013, 2014, 0, 'public', 1, 1, NULL, 'Not Classified', 'Loiyamorok', 24),
								(2016, 'Marigat Sub District Hospital Dispensing Point', '15138', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 171, 2014, 485, 0, 'public', 1, 1, NULL, 'Level 4', 'Marigat', 24),
								(2018, 'Lwanda Awiti Dispensary', '16770', 'satellite', 'PEPFAR', '', 895, 208, 2016, 405, 0, 'public', 1, 8, NULL, 'Not Classified', 'Ndhiwa', 24),
								(2019, 'Stakeholder/Partner (s) Meeting Coast', '001', 'standalone', 'PEPFAR', '', 889, 16, 2017, NULL, 0, 'ngo', 0, 28, NULL, 'Not Classified', 'Mombasa', 25),
								(2020, 'Sigor Sub-District Hospital-West Pokot', '15564', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 1363, 118, 2018, 76, 0, 'public', 1, 47, NULL, 'Not Classified', 'Weiwei', 24),
								(2021, 'Emali AHF Health Clinic', '17445', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 85, 2019, NULL, 1, 'mission', 1, 23, NULL, 'Not Classified', 'Mbitini', 48),
								(2022, 'Amoya Dispensary', '16768', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 208, 2020, 405, 0, 'public', 1, 8, NULL, 'Not Classified', 'Central Kanyadoto', 24),
								(2023, 'Stakeholder/Partner (s) Meeting Eastern', '001', 'standalone', '', '', 889, 26, 2021, NULL, 0, 'ngo', 0, 6, NULL, 'Not Classified', 'embu', 25),
								(2024, 'Stakeholder/Partner (s) Meeting Western', '003', 'standalone', 'PEPFAR', '', 874, 24, 2022, NULL, 0, 'private', 0, 3, NULL, 'Not Classified', '', 27),
								(2025, 'Stakeholder/Partner (s) Meeting Rift Valley', '004', 'standalone', 'PEPFAR', '', 1728, 20, 2023, NULL, 0, 'ngo', 0, 31, NULL, 'Not Classified', '', 25),
								(2026, 'Stakeholder/Partner (s) Meeting ', '005', 'standalone', 'PEPFAR', '', 828, 77, 2024, NULL, 0, 'ngo', 0, 23, NULL, 'Not Classified', '', 25),
								(2027, 'Stakeholder/Partner (s) Meeting Rift_Valley', '006', 'standalone', 'PEPFAR', '', 1363, 117, 2025, NULL, 0, 'ngo', 0, 32, NULL, 'Not Classified', '', 25),
								(2028, 'Stakeholder/Partner (s) Meeting Rift-Valley', '009', 'standalone', 'PEPFAR', '', 1363, 38, 2026, NULL, 0, 'ngo', 0, 43, NULL, 'Not Classified', '', 25),
								(2029, 'Stakeholder/Partner (s) Meeting Central', '010', 'standalone', 'PEPFAR', '', 998, 25, 2027, NULL, 0, 'ngo', 0, 36, NULL, 'Not Classified', '', 25),
								(2030, 'Stakeholder/Partner (s) Meeting Nyanza/Western', '011', 'standalone', '', '', 895, 46, 2028, NULL, 0, 'ngo', 0, 16, NULL, 'Not Classified', '', 25),
								(2031, 'Stakeholder/Partner (s) Meeting: Nyanza', '012', 'standalone', 'PEPFAR', '', 895, 94, 2029, NULL, 0, 'ngo', 0, 17, NULL, 'Not Classified', '', 25),
								(2032, 'Stakeholder/Partner (s) Meeting Nairobi', '013', 'standalone', 'PEPFAR', '', 998, 169, 2030, NULL, 0, 'ngo', 0, 30, NULL, 'Not Classified', '', 25),
								(2033, 'Stakeholder/Partner (s) Meeting: Nairobi Region', '014', 'standalone', '', '', 998, 169, 2031, NULL, 0, 'ngo', 0, 30, NULL, 'Not Classified', '', 25),
								(2034, 'Stakeholder/Partner (s) Meeting - Nyanza', '015', 'standalone', '', '', 895, 94, 2032, NULL, 0, 'ngo', 0, 17, NULL, 'Not Classified', 'Kisumu', 25),
								(2035, 'IMc Tekeleza DiCE Clinic Muhuru', '19930', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 822, 96, 2033, 112, 0, 'ngo', 1, 27, NULL, 'Not Classified', 'Muhuru Central', 25),
								(2036, 'Tangulbei Health Centre', '15707', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 895, 101, 2034, 2014, 0, 'public', 1, 1, NULL, 'Not Classified', 'Korossi', 42),
								(2037, 'Kimalel Health Centre', '14867', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB', 1728, 101, 2035, 485, 0, 'public', 1, 1, NULL, 'Not Classified', 'Kimalel', 42),
								(2038, 'Loboi Dispensary', '15042', 'satellite', 'PEPFAR', 'ART,PMTCT', 895, 101, 2036, 485, 0, 'public', 1, 1, NULL, 'Not Classified', 'Loboi', 42),
								(2039, 'Stakeholder/Partner (s) Meeting RiftValley', '', 'standalone', '', '', 895, 122, 2037, NULL, 0, '', 0, 44, NULL, 'Not Classified', '', 0),
								(2040, 'Kapkateny Dispensary', '15922', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 138, 2038, 98, 0, 'public', 1, 3, NULL, 'Not Classified', 'Cheptais, Bungoma', 24),
								(2041, 'Marakusi Dispensary', '16001', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 45, 2039, 109, 0, 'public', 1, 11, NULL, 'Not Classified', 'Lugari', 24),
								(2042, 'Muhaka Dispensary', '16027', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 130, 2040, 104, 0, 'public', 1, 11, NULL, 'Level 2', 'Eshirombe', 24),
								(2043, 'Mulwanda Dispensary', '16033', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 130, 2041, 104, 0, 'mission', 1, 11, NULL, 'Not Classified', 'Mulwanda', 12),
								(2044, 'Mundoli Health Centre', '16040', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 130, 2042, 104, 0, 'mission', 1, 11, NULL, 'Not Classified', 'Mulwanda', 48),
								(2045, 'Shinutsa Dispensary', '16115', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 130, 2043, 104, 0, 'public', 1, 11, NULL, 'Not Classified', 'Kisa Esat', 24),
								(2046, 'Elwangale Health Centre', '16714', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 130, 2044, 104, 0, 'public', 1, 11, NULL, 'Not Classified', 'Kisa East', 24),
								(2047, 'Tala Dispensary', ' 17170', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 212, 2045, NULL, 0, 'public', 1, 8, NULL, 'Not Classified', 'Kokwanyo', 24),
								(2048, 'Bonde Dispensary ', '13506', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 210, 2046, NULL, 0, 'public', 0, 17, NULL, 'Not Classified', ' Nyalunya', 24),
								(2049, 'Tunyai Dispensary', ' 12813', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 889, 23, 2047, NULL, 0, 'public', 1, 41, NULL, 'Not Classified', 'Tunyai', 24),
								(2050, 'Kocholwo Sub-District Hospital', '14961', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 107, 2048, 2051, 0, 'public', 1, 5, NULL, 'Not Classified', 'Kocholwo', 3);
								INSERT INTO `sync_facility` (`id`, `name`, `code`, `category`, `sponsors`, `services`, `manager_id`, `district_id`, `address_id`, `parent_id`, `ordering`, `affiliation`, `service_point`, `county_id`, `hcsm_id`, `keph_level`, `location`, `affiliate_organization_id`) VALUES
								(2051, 'Chepkorio Health Centre', '14346', 'central', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 107, 2049, NULL, 1, 'public', 0, 5, NULL, 'Not Classified', 'Marichor', 24),
								(2052, 'Kamwosor Health Centre', '14680', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 107, 2050, 2051, 0, 'public', 1, 5, NULL, 'Not Classified', 'Kamwosor', 24),
								(2053, 'KIPE', '', 'satellite', 'PEPFAR', 'ART,PEP', 864, 2, 2051, 46, 0, 'private', 1, 17, NULL, 'Not Classified', 'Kisumu Central', 27),
								(2054, 'Koduogo Dispensary', '19861', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 11, 2052, 59, 0, 'public', 1, 8, NULL, 'Not Classified', 'West Kanyada', 24),
								(2055, 'Nyabola Dispensary', '13863', 'satellite', 'PEPFAR', 'PMTCT,RTK', 895, 212, 2053, 183, 0, 'public', 1, 8, NULL, 'Not Classified', 'Konuonga', 24),
								(2056, 'Wagai Dispensary', '16792', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 9, 2054, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'North E Gem', 24),
								(2058, 'Joy Medical Clinic (Changamwe)', '11440', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 175, 2056, 181, 0, 'private', 1, 28, NULL, 'Not Classified', 'Mikindani', 27),
								(2059, 'Ngere Dispensary', '13850', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 65, 2057, 189, 0, 'public', 1, 27, NULL, 'Level 2', 'West Kamagambo', 41),
								(2060, 'Ng''odhe Dispensary (Main Land)', '18076', 'satellite', 'PEPHAR', 'ART,PMTCT,PEP,LAB,RTK', 822, 203, 2058, 567, 0, 'public', 1, 8, NULL, 'Level 2', 'Gembe West', 24),
								(2061, 'Tabaka Town Clinic', '', 'satellite', 'PEPFAR', 'PMTCT,PEP,LAB,RTK', 895, 46, 2059, 213, 0, 'mission', 1, 16, NULL, 'Not Classified', 'Kisii', 19),
								(2062, 'Osani Dispensary', '17726', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 208, 2060, NULL, 0, 'private', 1, 8, NULL, 'Not Classified', '', 27),
								(2063, 'Lwanda Gwassi Dispensary', '13742', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 99, 2061, NULL, 0, 'public', 1, 8, NULL, 'Not Classified', 'Gwassi', 24),
								(2064, 'St. Camillus Mission Hospital (karungu) Dispensing Point', '14103', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 96, 2062, 198, 0, 'mission', 1, 27, NULL, 'Not Classified', 'West Karungu', 2),
								(2065, 'Imc Tekeleza Dice Clinic Karungu (Sori)', '19929', 'satellite', '', 'ART', 822, 96, 2063, 112, 0, 'ngo', 1, 27, NULL, 'Not Classified', '', 25),
								(2066, 'Obware Dispensary', '', 'satellite', 'PEPFAR', 'PMTCT,PEP,LAB,RTK', 895, 11, 2064, 112, 0, '', 1, 8, NULL, 'Not Classified', '', 0),
								(2067, 'Kibunga Sub-District Hospital', '12289', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 828, 23, 2065, NULL, 0, 'public', 1, 41, NULL, 'Not Classified', 'Turima', 24),
								(2068, 'Nyangiela Dispensary', '13926', 'satellite', 'PEPFAR', 'PMTCT,PEP', 895, 212, 2066, 183, 0, 'public', 1, 8, NULL, 'Not Classified', 'Konuonga', 24),
								(2069, 'Ithanga Health Centre', '16747', 'standalone', '', '', NULL, 25, 2067, NULL, 0, '', 0, 36, NULL, 'Not Classified', '', 0),
								(2071, 'Lea Toto Program, Nyumbani Village, Kitui', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 73, 2069, 396, 0, 'ngo', 1, 18, NULL, 'Not Classified', 'Kitui', 13),
								(2072, 'Likuyani Sub-District Hospital Dispensing Point', '15961', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 45, 2070, 107, 0, 'public', 1, 11, NULL, 'Level 2', 'Likuyani', 24),
								(2073, 'Alale Health Centre', '16367', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 277, 2071, 76, 0, 'public', 1, 47, NULL, 'Not Classified', 'Alale', 24),
								(2074, 'Lokori (AIC) Health Centre', '15064', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 38, 2072, 563, 0, 'mission', 1, 43, NULL, 'Not Classified', 'Lokori', 12),
								(2075, 'Amakuriat Dispensary', '14198', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1363, 40, 2073, 76, 0, 'mission', 1, 47, NULL, 'Not Classified', 'Alale', 48),
								(2076, 'Oropoi Dispensary', '15445', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1701, 7, 2074, 561, 0, 'mission', 1, 43, NULL, 'Not Classified', 'Kalobeyei', 48),
								(2077, 'Matunda Dispensary', '16364', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 874, 121, 2075, 540, 0, 'public', 1, 42, NULL, 'Level 2', 'Matunda', 24),
								(2078, 'Lunyito Dispensary', '15974', 'satellite', 'PEPFAR', 'PMTCT', 874, 45, 2076, 109, 0, 'public', 1, 11, NULL, 'Not Classified', 'Lugari', 24),
								(2079, 'Mahanga Dispensary', '15987', 'satellite', 'PEPFAR', 'PMTCT', 874, 36, 2077, 130, 0, 'public', 1, 11, NULL, 'Level 2', 'Lwandeti', 42),
								(2080, 'Majengo Dispensary (Lugari)', '15988', 'satellite', 'PEPFAR', 'PMTCT', 874, 45, 2078, 109, 0, 'public', 1, 11, NULL, 'Level 2', 'Lumakanda', 42),
								(2081, 'Matunda Nursing Home', '16007', 'satellite', 'PEPFAR', 'PMTCT', 874, 45, 2079, 109, 0, 'private', 1, 11, NULL, 'Level 3', 'Matunda', 0),
								(2082, 'Maturu Dispensary', '16009', 'satellite', 'PEPFAR', 'PMTCT', 874, 45, 2080, 130, 0, 'public', 1, 11, NULL, 'Level 2', 'Lwandeti', 42),
								(2083, 'Mbagara Dispensary', '16011', 'satellite', 'PEPFAR', 'PMTCT', 874, 45, 2081, 109, 0, 'public', 1, 11, NULL, 'Level 2', 'Mautuma', 42),
								(2084, 'Moi''s Bridge Nursing Home', '16022', 'satellite', 'PEPFAR', 'PMTCT', 874, 45, 2082, 109, 0, 'private', 1, 11, NULL, 'Level 2', 'Sinoko', 0),
								(2085, 'Ivona Clinic', '15905', 'satellite', 'PEPFAR', 'PMTCT', 874, 45, 2083, 109, 0, 'private', 1, 11, NULL, 'Level 3', 'Mautuma', 27),
								(2086, 'St Michael Runyenjes', '', 'standalone', '', '', NULL, 26, 2084, NULL, 0, '', 0, 6, NULL, 'Not Classified', '', 0),
								(2087, 'Ogielo Health Centre', '', 'standalone', '', '', NULL, 94, 2085, NULL, 0, '', 0, 17, NULL, 'Not Classified', '', 0),
								(2088, 'Mutsetsa Dispensary', '16055', 'satellite', 'PEPFAR', 'PMTCT', 874, 130, 2086, 104, 0, 'public', 1, 11, NULL, 'Level 2', 'Mulwanda', 42),
								(2089, 'Mwihila Mission Hospital', '16058', 'satellite', 'PEPFAR', 'PMTCT', 874, 130, 2087, 104, 0, 'public', 1, 11, NULL, 'Level 3', 'Kisa East', 42),
								(2090, 'NYS Dispensary (Turbo)', '16077', 'satellite', 'PEPFAR', 'PMTCT', 874, 45, 2088, 109, 0, 'public', 1, 11, NULL, 'Level 2', 'Mwamba', 24),
								(2091, 'Nzoia (ACK) Dispensary', '16084', 'satellite', 'PEPFAR', 'PMTCT', 874, 131, 2089, 109, 0, 'mission', 1, 11, NULL, 'Level 2', 'Sinoko', 12),
								(2092, 'Nzoia Matete Dispensary', '16086', 'satellite', 'PEPFAR', 'PMTCT', 874, 45, 2090, 130, 0, 'public', 1, 11, NULL, 'Level 2', ' Lwandeti', 42),
								(2093, 'Rophy Clinic', '16093', 'satellite', 'PEPFAR', 'PMTCT', 874, 45, 2091, 109, 0, 'private', 1, 11, NULL, 'Level 2', 'Lumakanda', 27),
								(2094, 'Seregeya Dispensary', '16102', 'satellite', 'PEPFAR', 'PMTCT', 874, 131, 2092, 109, 0, 'public', 1, 11, NULL, 'Level 2', 'Likuyani', 24),
								(2095, 'Sonak Clinic', '16132', 'satellite', 'PEPFAR', 'PMTCT', 874, 130, 2093, 104, 0, 'public', 1, 11, NULL, 'Level 2', ' Kisa East', 42),
								(2096, 'Soy Sambu Dispensary', '16134', 'satellite', 'PEPFAR', 'PMTCT', 874, 131, 2094, 109, 0, 'public', 1, 11, NULL, 'Level 2', 'Sango', 42),
								(2097, 'Lumani Dispensary', '15970', 'satellite', 'PEPFAR', 'PMTCT', 874, 36, 2095, 130, 0, 'public', 1, 11, NULL, 'Level 3', 'Chebaywa', 42),
								(2098, 'Turbo Forest Dispensary', '16154', 'satellite', 'PEPFAR', 'PMTCT', 874, 131, 2096, 109, 0, 'public', 0, 11, NULL, 'Level 2', 'Likuyani', 42),
								(2099, 'Beberion Clinic', '15807', 'satellite', 'PEPFAR', 'PMTCT', 874, 131, 2097, NULL, 0, 'private', 0, 11, NULL, 'Level 2', 'Sinoko', 27),
								(2100, 'Makongeni Health Centre', '19858', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 11, 2098, 59, 0, 'public', 1, 8, NULL, 'Not Classified', 'Township', 24),
								(2101, 'Nyawawa Dispensary', '19863', 'satellite', 'PEPFAR', 'ART,PMTCT', 864, 11, 2099, 59, 0, 'public', 1, 8, NULL, 'Not Classified', 'Gem West', 24),
								(2102, 'Pala Masogo Health Centre', '19859', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 11, 2100, 59, 0, 'public', 1, 8, NULL, 'Not Classified', 'East Kanyada', 24),
								(2103, 'Hope Compassionate (ACK) Dispensary', '16983', 'satellite', 'PEPFAR', 'ART,PMTCT', 864, 11, 2101, 59, 0, 'mission', 1, 8, NULL, 'Not Classified', 'Homa Bay Town', 12),
								(2104, 'Nyarut Health Centre', '19860', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 864, 11, 2102, 59, 0, 'public', 1, 8, NULL, 'Not Classified', 'Kochia Central', 24),
								(2105, 'Wikoteng'' Dispensary', '19865', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 864, 11, 2103, 59, 0, 'public', 1, 8, NULL, 'Not Classified', 'Homa Bay ', 24),
								(2106, 'Gachuriri Dispensary', '12023', 'standalone', 'PEPFAR', '', NULL, 44, 2104, NULL, 0, 'public', 0, 6, NULL, 'Not Classified', 'Mbeti', 24),
								(2107, 'Igegania Sub-District Hospital', '10338', 'standalone', 'PEPFAR', 'ART,PMTCT', NULL, 31, 2105, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Mangu', 24),
								(2108, 'Kaptarakwa Sub-District Hospital', '14776', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 107, 2106, 2051, 0, 'public', 1, 5, NULL, 'Not Classified', 'Kaptarakwa', 24),
								(2109, 'Nyamonye Mission Dispensary', '13914', 'standalone', 'PEPFAR', 'ART,PMTCT', NULL, 17, 2107, NULL, 0, 'ngo', 0, 38, NULL, 'Not Classified', 'N Yimbo', 19),
								(2110, 'AIC Kalamba Dispensary', '17431', 'standalone', 'PEPFAR', 'ART,PMTCT', 828, 85, 2108, NULL, 0, 'mission', 0, 23, NULL, 'Not Classified', 'Kalamba', 48),
								(2111, 'St. Monica Kayole', '', 'standalone', '', '', NULL, 164, 2109, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(2112, 'Khwisero District Hospital Dispensing Point', '', 'satellite', '', 'ART,PMTCT,PEP,LAB,RTK', 874, 130, 2110, 104, 0, 'public', 1, 11, NULL, 'Level 3', 'Kisa East', 0),
								(2113, 'Langata Hospital', '13042', 'standalone', 'PEPFAR', '', NULL, 167, 2111, NULL, 0, 'private', 0, 30, NULL, 'Not Classified', 'Mugomoini', 27),
								(2114, 'Mariakani Cottage Hospital Ltd', '13064', 'standalone', 'PEPFAR', 'ART,PMTCT', NULL, 168, 2112, NULL, 0, 'private', 0, 30, NULL, 'Not Classified', 'Nairobi South', 27),
								(2115, 'Jericho Health Centre', '12988', 'standalone', 'PEPFAR', 'ART,PMTCT', NULL, 168, 2113, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Jerisho - Lumumba', 21),
								(2116, 'Lunga Lunga Health Centre', '13053', 'standalone', 'PEPFAR', '', NULL, 168, 2114, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Viwandani', 21),
								(2117, 'Makadara Health Care', '13056', 'standalone', 'PEPFAR', '', NULL, 168, 2115, NULL, 0, 'private', 0, 30, NULL, 'Not Classified', 'Makadara', 27),
								(2118, 'Guru Nanak Hospital', '12965', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP', NULL, 169, 2116, NULL, 0, 'private', 0, 30, NULL, 'Not Classified', 'Ngara', 27),
								(2120, 'Njiru Dispensary', ' 13126', 'standalone', '', 'ART,PMTCT,PEP', NULL, 164, 2118, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Njiru', 21),
								(2121, 'Dandora I Health Centre', '12913', 'standalone', '', 'ART,PMTCT', NULL, 164, 2119, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Dandora', 21),
								(2122, 'Langata Women Prison Dispensary', '13044', 'standalone', '', 'ART,PMTCT,PEP', NULL, 167, 2120, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Mugumoini', 24),
								(2123, 'Nairobi West Men''s Prison Dispensary', '13116', 'standalone', '', 'ART,PMTCT,PEP', NULL, 167, 2121, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Mugumoini', 24),
								(2124, 'Pstc Health Centre', '13153', 'standalone', '', 'ART,PMTCT,PEP', NULL, 166, 2122, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Ruaraka', 24),
								(2125, 'Kamiti Prison Hospital', '13000', 'standalone', '', 'ART,PMTCT,PEP', NULL, 166, 2123, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Ruaraka', 24),
								(2126, 'Coptic Medical Clinic', '12904', 'standalone', '', '', NULL, 168, 2124, NULL, 0, 'mission', 0, 30, NULL, 'Not Classified', 'Viwandani', 12),
								(2127, 'Mutuini Sub-District Hospital', '13105', 'standalone', '', '', NULL, 163, 2125, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Mutuini', 24),
								(2128, 'Karen Health Centre', '13003', 'standalone', '', '', NULL, 167, 2126, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Karen', 21),
								(2129, 'Loco Dispensary', '13051', 'satellite', '', 'ART,PMTCT,PEP', NULL, 169, 2127, 211, 0, 'public', 1, 30, NULL, 'Not Classified', 'Makongeni', 26),
								(2130, 'Eastleigh Health Centre', '12930', 'standalone', '', '', NULL, 165, 2128, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Eastleigh', 21),
								(2131, 'Kemri Mimosa', '18505', 'standalone', '', 'ART,PMTCT,PEP', NULL, 163, 2129, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Woodley', 24),
								(2132, 'Senye Medical Clinic', '13179', 'standalone', '', 'ART,PMTCT,PEP', NULL, 167, 2130, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Sarang''ombe', 24),
								(2133, 'Johanna Justin-Jinich Community Clinic (Kibera)', '17650', 'standalone', '', 'ART,PMTCT,PEP', NULL, 167, 2131, NULL, 0, 'ngo', 0, 30, NULL, 'Not Classified', 'Sarang''ombe', 25),
								(2134, 'Kivuli Dispensary', '13036', 'standalone', '', '', NULL, 163, 2132, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(2135, 'Ngong Road Health Centre', '13123', 'standalone', '', '', NULL, 163, 2133, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(2136, 'Kahawa West Health Centre', '12997', 'standalone', '', '', NULL, 166, 2134, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(2137, 'Kenyatta University Dispensary', '13024', 'standalone', '', '', NULL, 166, 2135, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(2138, 'Mathare North Health Centre', '13077', 'standalone', '', '', NULL, 166, 2136, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(2139, 'Melchizedek Hospital', '', 'standalone', '', '', NULL, 163, 2137, NULL, 0, '', 0, 30, NULL, 'Not Classified', '', 0),
								(2140, 'Msf- Green House Clinic', '20049', 'standalone', '', '', NULL, 165, 2138, NULL, 0, 'ngo', 0, 30, NULL, 'Not Classified', 'Eastleigh', 25),
								(2141, 'Emali Model Health Centre', '18260', 'satellite', 'PEPFAR', 'ART', 828, 85, 2139, 116, 0, 'public', 1, 23, NULL, 'Not Classified', ' Mbitini', 24),
								(2142, 'Kasongo Dispensary', '16282', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 94, 2140, NULL, 0, 'public', 0, 17, NULL, 'Not Classified', 'Ombeyi', 24),
								(2143, 'Obuya Dispensary', '19866', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 11, 2141, 59, 0, 'public', 1, 8, NULL, 'Not Classified', 'West Kagan', 24),
								(2144, 'Nyawita Dispensary', '19864', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 895, 11, 2142, NULL, 0, 'public', 1, 8, NULL, 'Not Classified', 'Gongo', 24),
								(2145, 'Karumandi Dispensary', '10504', 'standalone', '', '', NULL, 56, 2143, NULL, 0, '', 0, 15, NULL, 'Not Classified', 'Karumaindi', 0),
								(2146, 'Kamiti Maximum Clinic', '18942', 'standalone', '', '', NULL, 166, 2144, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Kahawa', 24),
								(2147, 'Karemeno Dispensary', '10490', 'satellite', '', 'ART', 998, 281, 2145, 79, 0, 'public', 1, 36, NULL, 'Not Classified', 'Mugunda', 24),
								(2148, 'Ombo Kowiti Dispensary', '17039', 'satellite', '', 'ART,PMTCT,PEP', 895, 53, 2146, 189, 0, 'public', 1, 27, NULL, 'Not Classified', 'Central Kanyankago', 0),
								(2149, 'Kimana Health Centre', '', 'standalone', '', '', NULL, 198, 2147, NULL, 0, '', 0, 10, NULL, 'Not Classified', '', 0),
								(2150, 'Wundanyi Prison Dispensary', '19735', 'satellite', 'PEPFAR', 'ART,PMTCT', 828, 66, 2148, NULL, 0, 'public', 1, 39, NULL, 'Not Classified', '', 42),
								(2151, 'Leshau Pondo Health Centre', '10657', 'standalone', '', 'ART', NULL, 59, 2149, NULL, 0, '', 0, 35, NULL, 'Not Classified', '', 0),
								(2152, 'New Tumaini Dispensary', '10852', 'standalone', '', '', NULL, 59, 2150, NULL, 0, '', 0, 35, NULL, 'Not Classified', '', 0),
								(2153, 'Nangina Dispensary', '', 'satellite', '', 'ART', 874, 126, 2151, 620, 0, 'mission', 1, 4, NULL, 'Not Classified', 'Samia', 0),
								(2154, 'Namuduru Dispensary', '', 'satellite', '', 'ART', 874, 126, 2152, 620, 0, 'public', 1, 4, NULL, 'Not Classified', '', 0),
								(2155, 'Rumbiye Health Centre', '', 'satellite', '', 'ART', 874, 126, 2153, 620, 0, 'public', 1, 4, NULL, 'Not Classified', '', 0),
								(2156, 'Ageng''a Health Centre', '', 'satellite', '', '', 874, 126, 2154, 620, 0, 'public', 1, 4, NULL, 'Not Classified', '', 0),
								(2157, 'Nambuku Dispensary', '', 'satellite', '', 'ART', 874, 126, 2155, 620, 0, 'public', 1, 4, NULL, 'Not Classified', '', 0),
								(2158, 'Buduta Dispensary', '', 'satellite', '', '', 874, 126, 2156, 620, 0, '', 1, 4, NULL, 'Not Classified', '', 0),
								(2159, 'Island farm Dispensary', '10355', 'standalone', '', '', NULL, 183, 2157, NULL, 0, 'public', 0, 36, NULL, 'Not Classified', 'Kimahuri', 24),
								(2160, 'Njoki Dispensary', '10884', 'standalone', '', 'ART', NULL, 42, 2158, NULL, 0, 'public', 0, 36, NULL, 'Not Classified', 'Gakindu', 24),
								(2161, 'Gategi Health Centre', '16463', 'satellite', 'PEPFAR', 'ART,PMTCT', NULL, 44, 2159, 598, 0, 'public', 1, 6, NULL, 'Not Classified', 'Riakanau', 24),
								(2162, 'Anmer Dispensary', '10029', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 54, 2160, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Tinganga', 24),
								(2163, 'Cianda Dispensary', '10097', 'standalone', '', '', NULL, 5, 2161, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Cianda', 24),
								(2164, 'Gathanga Dispensary', '10214', 'standalone', '', '', NULL, 5, 2162, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Gathanga', 24),
								(2165, 'Gichuru Dispensary', '10252', 'standalone', '', '', NULL, 55, 2163, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Kikuyu', 24),
								(2166, 'Kagwe Dispensary', '10413', 'standalone', '', '', NULL, 195, 2164, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Lari', 24),
								(2167, 'Kigumo Health Centre (Kiambu East)', '10587', 'standalone', '', '', NULL, 33, 2165, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Kimothai', 24),
								(2168, 'Limuru Health Centre', '10661', 'standalone', '', '', NULL, 197, 2166, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Limuru', 24),
								(2169, 'Lussigetti Health Centre', '10666', 'standalone', '', '', NULL, 55, 2167, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Karai', 24),
								(2170, 'Miguta Dispensary', '10726', 'standalone', '', '', NULL, 5, 2168, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Ngewa', 24),
								(2171, 'Ngewa Health Centre', '10865', 'standalone', '', '', NULL, 5, 2169, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Ngewa', 24),
								(2172, 'Kangaru Dispensary (Kirinyaga)', '10468', 'standalone', '', '', NULL, 56, 2170, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Mwirua', 0),
								(2173, 'Nyathuna Sub District Hospital', '10895', 'standalone', '', '', NULL, 55, 2171, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Nyathuna', 24),
								(2174, 'Kiangai Dispensary', '10556', 'standalone', '', '', NULL, 56, 2172, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Kiini', 0),
								(2175, 'Kiang''ombe Dispensary', '10555', 'standalone', '', 'ART', NULL, 56, 2173, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Gachigi', 0),
								(2176, 'Kianjege Dispensary', '10562', 'standalone', '', '', NULL, 56, 2174, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Kiini North', 0),
								(2177, 'Wangige Health Centre', '11170', 'standalone', '', '', NULL, 101, 2175, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Kabete', 24),
								(2178, 'Hamundia Health Centre', '16753', 'standalone', '', '', NULL, 5, 2176, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Ruiru', 24),
								(2179, 'Ngorongo Health Centre', '10872', 'standalone', '', '', NULL, 31, 2177, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Chania', 24),
								(2180, 'Karatu Health Centre', '10489', 'standalone', '', '', NULL, 31, 2178, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Ndarugu', 24),
								(2181, 'Gitare Health Centre (Gatundu)', '10257', 'standalone', '', '', NULL, 31, 2179, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Kiamwangi', 24),
								(2182, 'Gachege Dispensary', '10194', 'standalone', '', '', NULL, 31, 2180, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Mangu', 24),
								(2183, 'Kiandutu Health Centre', '16814', 'standalone', '', '', NULL, 62, 2181, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Kopmo', 24),
								(2184, 'Juja Farm Health Centre', '10386', 'standalone', '', '', NULL, 62, 2182, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Juja', 24),
								(2185, 'Njoki-ini Dispensary', ' 10884', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 998, 61, 2183, 148, 0, 'public', 1, 36, NULL, 'Not Classified', 'Gakindu', 24),
								(2186, 'Ciagini Dispensary', '10096', 'standalone', '', '', NULL, 56, 2184, 97, 0, 'public', 0, 15, NULL, 'Not Classified', 'Thiba', 24),
								(2187, 'Gathigiriri Dispensary', '10217', 'standalone', 'PEPFAR', '', NULL, 56, 2185, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Tebere', 24),
								(2188, 'Gatugura Dispensary', '10229', 'standalone', '', 'ART,PMTCT', NULL, 56, 2186, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Kirima', 24),
								(2189, 'Gatwe Dispensary', '10239', 'standalone', '', '', NULL, 56, 2187, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Mutira', 24),
								(2190, 'Kandong''u Dispensary', '10461', 'standalone', '', '', NULL, 56, 2188, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Mutithi', 24),
								(2191, 'Kangaita Health Centre', '10462', 'standalone', '', '', NULL, 56, 2189, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Inoi', 24),
								(2192, 'Kalandini Health Centre', '12146', 'satellite', '', 'PMTCT', 828, 12, 2190, NULL, 0, 'public', 0, 22, NULL, 'Not Classified', 'Kalandini', 24),
								(2193, 'Chepkorio Health Centre Dispensing Point', '', 'satellite', '', 'ART,PMTCT,PEP', 1363, 107, 2191, 2051, 0, 'public', 1, 5, NULL, 'Not Classified', 'Marichor', 24),
								(2194, 'Shujaa Satellite Clinic,West Pokot', '20052', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 1363, 40, 2192, 76, 0, 'ngo', 1, 47, NULL, 'Not Classified', 'Chemochoi', 25),
								(2195, 'Muhamarani Dispensary', '18788', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 889, 194, 2193, 573, 0, 'public', 1, 21, NULL, 'Not Classified', 'Mkunumbi', 24),
								(2196, 'Lokusero Dispensary', ' 15065', 'satellite', '', 'ART', 1728, 112, 2194, 514, 0, 'public', 1, 20, NULL, 'Not Classified', 'Ilngwesi', 24),
								(2197, 'Tea Research Dispensary', '', 'satellite', '', 'ART,PMTCT,PEP,LAB,RTK', 895, 108, 2195, 510, 0, 'public', 1, 12, NULL, 'Not Classified', '', 0),
								(2198, 'Kiaragana Dispensary', '10566', 'standalone', '', 'ART', NULL, 56, 2196, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Mukure', 24),
								(2199, 'Kibirigwi Health Centre', '10571', 'standalone', '', 'ART', NULL, 56, 2197, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Kiini North', 24),
								(2200, 'Kiumbu Health Centre', '10641', 'standalone', '', 'ART', NULL, 48, 2198, 97, 0, 'public', 0, 15, NULL, 'Not Classified', 'Tebere', 24),
								(2201, 'Mumbuini Dispensary', '10766', 'standalone', '', 'ART', NULL, 48, 2199, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Murinduko', 24),
								(2202, 'Murinduko Health Centre', '10781', 'standalone', '', 'ART', NULL, 48, 2200, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Murinduko', 24),
								(2203, 'Nguka Dispensary', ' 10873', 'standalone', '', 'ART', NULL, 56, 2201, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Thiba', 24),
								(2204, 'Njegas Dispensary', '10880', 'standalone', '', 'ART', NULL, 56, 2202, 97, 0, 'public', 0, 15, NULL, 'Not Classified', 'Kangai', 24),
								(2205, 'Sagana Rural Health Demonstration Centre', '10994', 'standalone', '', 'ART', NULL, 56, 2203, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Kariti', 24),
								(2206, 'Thiba Health Centre', '11092', 'standalone', '', 'ART', NULL, 56, 2204, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Thiba', 24),
								(2207, 'Ucheru Community Health Centre', '11130', 'standalone', '', 'ART', NULL, 56, 2205, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Kanyekiini', 24),
								(2208, 'Wamumu Dispensary', '11164', 'standalone', '', 'ART', NULL, 56, 2206, 97, 0, 'public', 0, 15, NULL, 'Not Classified', 'Mutithi', 24),
								(2209, ' Gatunyu Dispensary', '10234', 'standalone', '', 'ART', NULL, 288, 2207, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', 'Mugumoini', 24),
								(2210, 'Maragua Ridge Health Centre', '10687', 'standalone', '', 'ART', NULL, 58, 2208, NULL, 0, 'public', 0, 29, NULL, 'Not Classified', 'Maragua Ridge', 24),
								(2211, 'Kangari Health Centre', '10465', 'standalone', '', 'ART', NULL, 287, 2209, NULL, 0, 'public', 0, 29, NULL, 'Not Classified', 'Kangari', 24),
								(2212, 'Mt Kenya (ACK) Hospital', '10738', 'standalone', '', 'ART,PMTCT,PEP', NULL, 56, 2210, NULL, 0, 'mission', 0, 15, NULL, 'Not Classified', 'Kerugoya', 48),
								(2213, 'Jeffrey Medical & Diagnostic Centre', '20064', 'standalone', '', 'ART', NULL, 163, 2211, NULL, 0, 'private', 0, 30, NULL, 'Not Classified', 'kawangware', 27),
								(2214, 'Makuyu Health Centre', '10674', 'standalone', '', 'ART,PMTCT,PEP', NULL, 57, 2212, NULL, 0, 'public', 0, 29, NULL, 'Not Classified', 'makuyu', 24),
								(2215, 'Kithimani Dispensary', '12357', 'standalone', '', 'ART,PMTCT', NULL, 89, 2213, NULL, 0, 'public', 0, 22, NULL, 'Not Classified', 'Kithimani', 24),
								(2216, 'Diani Health Centre', '11304', 'standalone', '', 'ART,PMTCT,PEP,LAB', NULL, 135, 2214, NULL, 0, 'public', 0, 18, NULL, 'Not Classified', 'Diani', 24),
								(2217, 'Marti Dispensary', '15144', 'satellite', 'PEPFAR', 'ART', 1728, 160, 2215, 576, 0, 'public', 1, 37, NULL, 'Not Classified', 'Marti', 24),
								(2218, 'Lesirkan Health Centre', '15029', 'satellite', 'PEPFAR', 'ART', 1728, 160, 2216, 576, 0, 'public', 1, 37, NULL, 'Not Classified', 'Ndoto', 24),
								(2219, 'Thika Barracks', '', 'satellite', '', 'ART', NULL, 62, 2217, 387, 0, '', 0, 13, NULL, 'Not Classified', '', 0),
								(2220, 'Hope World Wide-Uasin Gishu', '19114', 'satellite', '', 'ART', 895, 122, 2218, NULL, 0, 'private', 1, 44, NULL, 'Not Classified', 'Kapyemit', 27),
								(2221, 'Gatuto Dispensary', '16386', 'standalone', '', 'ART', NULL, 56, 2219, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Koroma', 24),
								(2222, 'Gathigiriri Health Centre', '10217', 'standalone', '', 'ART', NULL, 48, 2220, NULL, 0, 'public', 0, 15, NULL, 'Not Classified', 'Mwea', 24),
								(2223, 'Waware Dispensary', '14171', 'standalone', '', 'ART', NULL, 203, 2221, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', 'Rusinga East', 24),
								(2224, 'Wamba Mission Hospital Dispensing Point', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB', 1728, 147, 2222, 562, 0, 'mission', 1, 37, NULL, 'Level 4', 'Wwmba', 19),
								(2225, 'Akachiu Health Centre', '11923', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB', NULL, 180, 2223, 170, 0, 'public', 1, 26, NULL, 'Not Classified', 'Kanuni', 24),
								(2226, 'Laare Health Centre', '12422', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB', NULL, 69, 2224, 170, 0, 'public', 1, 26, NULL, 'Not Classified', 'Ntunene', 24),
								(2227, 'Kangema Sub District Hospital Dispensing Point', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 184, 2225, 251, 0, 'public', 1, 29, NULL, 'Not Classified', 'Muguru', 24),
								(2228, 'Muriranjas Sub-District Hospital Dispensing Point', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 998, 184, 2226, 150, 0, 'public', 1, 29, NULL, 'Not Classified', 'Mugoiri', 43),
								(2229, 'West Gate Dispensary', '15780', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB', 1728, 147, 2227, 562, 0, 'public', 1, 37, NULL, 'Not Classified', 'Waso West', 24),
								(2230, 'Swari Model Health Centre', '15693', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB', 1728, 147, 2228, 562, 0, 'public', 1, 37, NULL, 'Not Classified', 'Nairimirimo', 24),
								(2231, 'Nkutuk Elmuget Dispensary', '18030', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 1728, 147, 2229, 562, 0, 'public', 1, 37, NULL, 'Not Classified', 'Lodungokwe', 24),
								(2232, 'Lodungokwe Health Centre.', '15048', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB', 1728, 147, 2230, 562, 0, '', 1, 37, NULL, 'Not Classified', 'Lodungokwe', 24),
								(2233, 'St Raphael Dispensary-Trans Nzoia', '18515', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB', NULL, 284, 2231, 540, 0, 'mission', 1, 42, NULL, 'Not Classified', 'Matisi', 48),
								(2234, 'Kiminini Cottage Hospital', '14872', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB', 1701, 121, 2232, 540, 0, 'mission', 1, 42, NULL, 'Not Classified', 'Kiminini', 48),
								(2235, 'St John-Tigania Hospital', '12799', 'standalone', '', 'ART,PMTCT,PEP,LAB', NULL, 216, 2233, NULL, 0, '', 0, 26, NULL, 'Not Classified', 'Muthara', 0),
								(2236, 'Mission for Essential Drugs & Supplies (MEDS)', '', 'central', '', '', NULL, 168, 2234, NULL, 0, '', 0, 30, NULL, 'Not Classified', 'Mombasa Road', 0),
								(2237, 'Ogero Dispensary', '13966', 'standalone', '', 'ART', NULL, 9, 2235, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'South Gem', 24),
								(2238, 'Nyangiti Health Centre', '17832', 'satellite', 'PEPFAR', 'ART,PMTCT', 998, 201, 2236, 150, 0, 'public', 1, 29, NULL, 'Not Classified', 'Gakoe', 24),
								(2239, 'Barding Dispensary(Asburn Uhuru)', '18396', 'standalone', '', 'ART,PMTCT', NULL, 9, 2237, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'South Alego', 24),
								(2240, 'Kiruri Dispensary (Muranga North)', '10637', 'satellite', 'PEPFAR', 'PMTCT,PEP', 998, 57, 2238, 251, 0, '', 1, 29, NULL, 'Not Classified', 'Muguru', 0),
								(2241, 'Masyungwa Health Centre', '12479', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 828, 73, 2239, 338, 0, 'public', 1, 18, NULL, 'Not Classified', 'Masyungwa', 24),
								(2242, 'Nyathengo Dispensary', '13944', 'standalone', '', 'ART,PMTCT', NULL, 9, 2240, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'East Alego', 24),
								(2243, 'Nasewa Health Centre', '16074', 'satellite', '', 'ART,PMTCT', 874, 126, 2241, 28, 0, 'public', 1, 4, NULL, 'Not Classified', 'Nasewa', 24),
								(2244, 'Munongo Dispensary', '16043', 'satellite', '', 'ART,PMTCT,PEP', 874, 126, 2242, 28, 0, 'public', 1, 4, NULL, 'Not Classified', 'Bukhayo West', 24),
								(2245, 'Tanaka Nursing Home', '16149', 'satellite', '', 'ART,PMTCT', 874, 126, 2243, 28, 0, 'private', 1, 4, NULL, 'Not Classified', 'Busia Township', 27),
								(2246, 'Kithituni Health Care Clinic Dispensing Point', ' 17445', 'satellite', '', 'ART,PMTCT,PEP', 828, 85, 2244, 586, 0, 'public', 1, 23, NULL, 'Not Classified', 'Kasikeu', 24),
								(2247, 'Gatanga Dispensary', '10207', 'standalone', 'KEMSA', 'ART,PMTCT,PEP', NULL, 288, 2245, NULL, 0, 'public', 0, 29, NULL, 'Not Classified', 'Gatanga', 24),
								(2248, 'Kigumo Sub District Hospital (Kigumo)', '10588', 'standalone', 'KEMSA', 'ART,PMTCT,PEP,LAB', NULL, 287, 2246, NULL, 0, 'public', 0, 29, NULL, 'Not Classified', ' Iriguini', 24),
								(2249, 'Patanisho Maternity and Nursing Home', '12977', 'satellite', '', 'ART,PMTCT,PEP', NULL, 170, 2247, 376, 0, 'private', 1, 30, NULL, 'Not Classified', 'Kayole', 27),
								(2250, 'Kenyatta Barracks GRH', '', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 20, 2248, 387, 0, 'private', 1, 31, NULL, 'Level 1', '', 0),
								(2251, 'St.John of God Tigania', '', 'standalone', '', 'ART,PMTCT,PEP', NULL, 32, 2249, NULL, 0, 'mission', 0, 26, NULL, 'Not Classified', '', 0),
								(2252, 'Okiki Amayo Health Centre', '13972', 'standalone', '', 'PMTCT', NULL, 35, 2250, NULL, 0, 'public', 0, 8, NULL, 'Not Classified', 'Kakdhimu West', 24),
								(2253, 'Kaboywo Dispensary', '15909', 'satellite', '', 'ART,PMTCT,PEP', NULL, 132, 2251, 552, 0, 'public', 1, 3, NULL, 'Not Classified', 'Kaboywo', 24),
								(2254, 'Kaborom Dispensary', '15910', 'satellite', '', 'ART,PMTCT,PEP', NULL, 132, 2252, 552, 0, 'public', 1, 3, NULL, 'Not Classified', 'Kaptama', 24),
								(2255, 'Kamenjo Dispensary', ' 15917', 'satellite', '', 'ART,PMTCT', NULL, 132, 2253, 552, 0, '', 1, 3, NULL, 'Not Classified', '', 0),
								(2256, 'Matinyani Dispensary', '12486', 'standalone', '', 'ART,PMTCT', NULL, 73, 2254, NULL, 0, 'public', 0, 18, NULL, 'Not Classified', 'Kyondoni', 24),
								(2257, 'Ol-Arabel Dispensary', '15386', 'satellite', '', 'ART,PMTCT', 1728, 101, 2255, 485, 0, 'public', 1, 1, NULL, 'Not Classified', 'Kimoriot', 24),
								(2258, 'Suna Ragana Dispensary', '14136', 'standalone', '', 'ART,PMTCT', NULL, 99, 2256, NULL, 0, 'public', 0, 27, NULL, 'Not Classified', 'Suna Ragana', 24),
								(2259, 'Nguni health Centre', '12658', 'standalone', '', 'ART,PMTCT', NULL, 84, 2257, NULL, 0, 'public', 0, 18, NULL, 'Not Classified', 'Nguni', 24),
								(2260, 'Entanda Dispensary', '13545', 'standalone', '', '', NULL, 46, 2258, NULL, 0, 'public', 0, 16, NULL, 'Not Classified', 'Ikuruma', 24),
								(2261, 'Stakeholder / Partner (s) Meeting RiftValley', '', 'standalone', '', '', 998, 106, 2259, NULL, 0, '', 0, 10, NULL, 'Not Classified', '', 0),
								(2262, 'St Damiano Nursing Home', '16138', 'standalone', '', 'ART', NULL, 19, 2260, NULL, 0, 'mission', 0, 3, NULL, 'Not Classified', 'Township', 48),
								(2263, 'Loitokitok District Hospital', '15051', 'standalone', '', 'ART', NULL, 198, 2261, NULL, 0, 'public', 0, 10, NULL, 'Not Classified', 'Ololopon', 24),
								(2264, 'Ndeda Dispensary', '13839', 'standalone', '', 'ART,PMTCT,PEP', NULL, 17, 2262, NULL, 0, 'public', 0, 38, NULL, 'Not Classified', 'C Sakwa', 24),
								(2265, 'Kaptalelio Dispensary', '15924', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 874, 132, 2263, 552, 0, 'public', 1, 3, NULL, 'Not Classified', 'Kaptalelio', 24),
								(2266, 'Muskut Health Centre', '15260', 'satellite', '', 'ART,PMTCT', 1363, 107, 2264, 2051, 0, 'public', 1, 5, NULL, 'Not Classified', 'Muskut ', 3),
								(2267, 'Simotwo Dispensary (Keiyo)', '15578', 'satellite', '', 'ART,PMTCT', 1363, 107, 2265, 2051, 0, 'public', 1, 5, NULL, 'Not Classified', 'Maoi', 3),
								(2268, 'Bahati District Hospital', '14224', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 20, 2266, NULL, 0, 'public', 0, 31, NULL, 'Not Classified', 'Bahati', 24),
								(2269, 'Bartabwa Dispensary', '14241', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 1728, 280, 2267, 605, 0, 'public', 1, 1, NULL, 'Not Classified', 'Kinyach', 24),
								(2270, 'St Marys Narok', '', 'standalone', '', '', NULL, 144, 2268, NULL, 0, 'mission', 0, 33, NULL, 'Not Classified', '', 48),
								(2271, 'Konoin Health Centre', '', 'standalone', '', 'ART,PMTCT,PEP', NULL, 108, 2269, NULL, 0, 'public', 0, 12, NULL, 'Not Classified', 'Konoin', 24),
								(2272, 'Awasi Mission Hospital', '', 'standalone', '', 'ART,PMTCT,PEP', NULL, 210, 2270, NULL, 0, 'mission', 0, 17, NULL, 'Not Classified', '', 48),
								(2273, 'St. Paul Health Centre -Siaya', '', 'standalone', '', 'ART,PMTCT,PEP', NULL, 9, 2271, NULL, 0, 'mission', 0, 38, NULL, 'Not Classified', '', 48),
								(2274, 'KARI Health Clinic', '13005', 'satellite', '', 'ART,PMTCT,PEP', 998, 47, 2272, 74, 0, 'public', 1, 30, NULL, 'Not Classified', 'Kitisiru', 24),
								(2275, 'IOM International Organization for migration(gigiri)', '20158', 'satellite', '', 'ART,PMTCT,PEP', 998, 47, 2273, 74, 0, 'ngo', 1, 30, NULL, 'Not Classified', 'Kitisiru', 25),
								(2276, 'Karura Health Centre (Kiambu Rd)', '13009', 'satellite', '', 'ART,PMTCT', NULL, 47, 2274, 74, 0, 'public', 1, 30, NULL, 'Not Classified', 'Muthaiga', 21),
								(2277, 'Maria Immaculate Health Centre', '13062', 'satellite', '', 'ART,PMTCT', 998, 47, 2275, 74, 0, 'mission', 1, 30, NULL, 'Not Classified', ' Lavington', 19),
								(2278, 'AAR George Williamsons Clinic', '16796', 'satellite', '', 'ART', NULL, 47, 2276, 74, 0, 'private', 1, 30, NULL, 'Not Classified', 'Parklands', 26),
								(2279, 'National Spinal Injury Hospital', '13194', 'satellite', '', 'ART', 998, 47, 2277, 74, 0, 'public', 1, 30, NULL, 'Not Classified', 'KIlimani', 24),
								(2280, 'Consolata Shrine Dispensary (Deep Sea Nairobi)', '18888', 'satellite', '', 'ART', 998, 47, 2278, 74, 0, 'mission', 1, 30, NULL, 'Not Classified', 'Highridge', 9),
								(2281, 'St Angela Merici Health Centre (Kingeero)', '17876', 'satellite', '', 'ART', 998, 47, 2279, 74, 0, 'mission', 1, 30, NULL, 'Not Classified', 'Kingeero', 19),
								(2282, 'Salawa Health Centre', '15522', 'satellite', 'PEPFAR', 'ART,PMTCT', NULL, 101, 2280, 67, 0, 'public', 1, 1, NULL, 'Not Classified', 'Salawa', 24),
								(2283, 'Mogorwa Health Centre', '15197', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', NULL, 101, 2281, 67, 0, 'public', 1, 1, NULL, 'Not Classified', ' Emmom', 24),
								(2284, 'Kituro Health Centre', '14953', 'satellite', 'PEPFAR', 'ART,PMTCT', NULL, 101, 2282, 67, 0, 'public', 1, 1, NULL, 'Not Classified', 'Kituro', 24),
								(2285, 'Seretunin Health Centre', '15549', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', NULL, 101, 2283, 67, 0, 'public', 1, 1, NULL, 'Not Classified', 'Ewalel', 24),
								(2286, 'Kampi Samaki Health Centre', '14677', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', NULL, 101, 2284, 485, 0, 'public', 1, 1, NULL, 'Not Classified', 'Salabani', 24),
								(2287, 'Illinga''rua Dispensary', '14568', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', NULL, 101, 2285, 485, 0, 'public', 1, 1, NULL, 'Not Classified', ' Eldume', 24),
								(2288, 'GK Prison Dispensary (Homa Bay)', '13578', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', NULL, 11, 2286, 59, 0, 'public', 1, 8, NULL, 'Not Classified', 'Homabay', 24),
								(2289, 'Nduga Dispensary', '19868', 'satellite', 'PEPFAR', 'ART,PMTCT', NULL, 11, 2287, 59, 0, 'public', 1, 8, NULL, 'Not Classified', 'Gem West', 24),
								(2290, 'Butula Mission Health Centre', '15838', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', NULL, 143, 2288, 545, 0, 'mission', 1, 4, NULL, 'Not Classified', 'Elukhari', 48),
								(2291, 'Ikonzo Dispensary', '17165', 'satellite', 'PEPFAR', 'ART,PMTCT', NULL, 101, 2289, 545, 0, 'public', 1, 4, NULL, 'Not Classified', 'Bujumba', 24),
								(2292, 'Bumutiru Dispensary', '15826', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', NULL, 143, 2290, 545, 0, 'public', 1, 4, NULL, 'Not Classified', 'Marachi Central', 24),
								(2293, 'Bwaliro Dispensary', '15840', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', NULL, 101, 2291, 545, 0, 'public', 1, 4, NULL, 'Not Classified', 'Lugulu', 24),
								(2294, 'Kinna Health Centre', '12323', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 889, 179, 2292, 854, 0, 'public', 1, 9, NULL, 'Not Classified', 'Kinna', 24),
								(2295, 'Luoniek Dispensary', '15102', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', NULL, 52, 2293, 517, 0, 'public', 1, 20, NULL, 'Not Classified', 'Olmoran', 24),
								(2296, 'Melwa Health Centre', '15170', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', NULL, 52, 2294, 517, 0, 'public', 1, 20, NULL, 'Not Classified', 'Marmanet', 24),
								(2297, 'Bikeke Helth Centre', '16361', 'satellite', 'PEPFAR', 'ART,PMTCT', NULL, 121, 2295, 540, 0, 'public', 1, 42, NULL, 'Not Classified', 'Milimani', 24),
								(2298, 'Likii Dispensary', '15035', 'satellite', 'PEPFAR', 'ART,PMTCT', 1728, 111, 2296, 161, 0, 'public', 1, 20, NULL, 'Not Classified', 'Nturukuma', 24),
								(2299, 'Chegilet Dispensary', '14306', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 1363, 107, 2297, 499, 0, 'public', 1, 5, NULL, 'Not Classified', 'Keu', 24),
								(2300, 'Msekekwa Health Centre', '15238', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 1363, 107, 2298, 499, 0, 'public', 1, 5, NULL, 'Not Classified', 'Kapchemutwa', 21),
								(2301, 'Riley Mother and Baby-MTRH', '', 'satellite', 'PEPFAR', 'PMTCT', 1363, 103, 2299, 144, 0, 'public', 1, 44, NULL, 'Not Classified', 'Chepkoilel', 3),
								(2302, 'Langas RCEA', '15011', 'satellite', '', 'ART,PMTCT,PEP', 1363, 103, 2300, 144, 0, 'public', 1, 44, NULL, 'Not Classified', 'Langas', 3),
								(2303, 'Kilungu Sub-District Hospital', '12314', 'satellite', 'KEMSA', 'ART,PMTCT,PEP,LAB,RTK', 828, 77, 2301, 117, 0, 'public', 0, 23, NULL, 'Not Classified', 'Kithembe', 24),
								(2304, 'Kolowa Health Centre', '14979', 'satellite', '', 'ART,PMTCT,PEP', 1728, 101, 2302, 2014, 0, 'public', 1, 1, NULL, 'Not Classified', 'Kolowa', 24),
								(2305, 'Kirie Dispensary', '12333', 'satellite', '', 'ART,PMTCT,PEP', 889, 142, 2303, 137, 0, 'public', 1, 6, NULL, 'Not Classified', 'Mutitu', 24),
								(2306, 'Koru Dispensary', '17110', 'standalone', '', 'ART,PMTCT,PEP', NULL, 101, 2304, NULL, 0, 'public', 0, 17, NULL, 'Not Classified', ' Koru', 24),
								(2307, 'Busibwabo Dispensary', '15835', 'satellite', '', 'ART,PMTCT', 874, 126, 2305, 28, 0, 'public', 1, 4, NULL, 'Not Classified', 'Busibwabo', 24),
								(2308, 'Kanyarkwat Dispensary', '14689', 'satellite', 'AMPATH', 'ART,PMTCT', 1363, 40, 2306, 76, 0, 'public', 1, 47, NULL, 'Not Classified', 'Kanyarkwat', 24),
								(2309, 'Keringet Health Centre-West Pokot', '14837', 'satellite', 'AMPATH', 'ART,PMTCT,PEP', 1363, 40, 2307, 76, 0, 'public', 1, 47, NULL, 'Not Classified', 'Keringet', 24),
								(2310, 'Port Florence Hospital', '18774', 'standalone', '', 'ART,PMTCT,PEP', NULL, 9, 2308, NULL, 0, 'private', 0, 38, NULL, 'Not Classified', 'Township', 27),
								(2311, 'Sayyida Fatimah Hospital', '11774', 'standalone', '', 'ART,PMTCT,PEP', NULL, 188, 2309, NULL, 0, 'mission', 0, 28, NULL, 'Not Classified', 'Kisauni', 48),
								(2312, 'Nyamogonchoro Dispensary', '13983', 'standalone', '', 'ART,PMTCT,PEP', NULL, 92, 2310, NULL, 0, 'public', 0, 16, NULL, 'Not Classified', 'S.M. Borabu', 24),
								(2313, 'Al Farooq Hospital', '11208', 'standalone', '', 'ART,PMTCT', NULL, 16, 2311, NULL, 0, '', 0, 28, NULL, 'Not Classified', 'Majengo', 0),
								(2314, 'Mrughua Dispensary', '11652', 'satellite', '', 'ART,PMTCT,PEP', 828, 219, 2312, NULL, 0, 'public', 1, 39, NULL, 'Not Classified', 'Bura', 24),
								(2315, 'Mwanda Health Centre (Taita Taveta)', '11688', 'satellite', '', 'ART,PMTCT,PEP', 828, 221, 2313, NULL, 0, 'public', 1, 39, NULL, 'Not Classified', 'Kishamba', 24),
								(2316, 'Mbulia Dispensary', '11591', 'satellite', '', 'ART,PMTCT,PEP', 828, 219, 2314, NULL, 0, 'public', 1, 39, NULL, 'Not Classified', ' Ngolia', 24),
								(2317, ' Ghazi Dispensary', '11390', 'satellite', '', 'ART,PMTCT,PEP', 828, 219, 2315, 1809, 0, 'public', 1, 39, NULL, 'Not Classified', 'Ghazi', 24),
								(2318, 'Ndome Dispensary (Taita)', '11704', 'satellite', '', 'ART,PMTCT,PEP', 828, 219, 2316, 1809, 0, 'public', 1, 39, NULL, 'Not Classified', 'Ndome', 24),
								(2319, ' Tausa Health Centre', '11839', 'satellite', '', 'ART,PMTCT,PEP', 828, 219, 2317, 1809, 0, 'public', 1, 39, NULL, 'Not Classified', 'Mbololo', 24),
								(2320, 'Sagaighu Dispensary', '11763', 'satellite', '', 'ART,PMTCT,PEP', 828, 66, 2318, NULL, 0, 'public', 1, 39, NULL, 'Not Classified', 'Bura', 24),
								(2321, 'Werugha Health Centre', '11904', 'satellite', '', 'ART,PMTCT,PEP', 828, 221, 2319, NULL, 0, 'public', 1, 39, NULL, 'Not Classified', 'Mlondo', 24),
								(2322, 'Mikindani Medical Clinic', '11615', 'satellite', '', 'ART,PMTCT,PEP', 889, 175, 2320, 181, 0, 'private', 1, 28, NULL, 'Not Classified', 'Kwa Shee', 0),
								(2323, 'Miu Sub-Health Centre', '12537', 'satellite', '', 'ART,PMTCT', 828, 83, 2321, 629, 0, 'public', 1, 22, NULL, 'Not Classified', 'Miu', 24),
								(2324, 'Mawego Health Centre', '13795', 'satellite', '', 'ART,PMTCT,PEP', 895, 35, 2322, 1326, 0, 'mission', 1, 8, NULL, 'Not Classified', 'Kobuya', 2),
								(2326, 'Inuka Hospital & Maternity Home', '13618', 'standalone', '', 'ART,PMTCT,PEP', NULL, 9, 2324, NULL, 0, 'private', 0, 38, NULL, 'Not Classified', 'Ndere', 27),
								(2327, 'Thanantu Faith Clinic Dispensary', '12793', 'satellite', '', 'ART,PMTCT,PEP', 889, 87, 2325, 216, 0, 'private', 1, 41, NULL, 'Level 2', 'Gikingo', 27),
								(2328, 'Chiakariga Health Centre', '11969', 'satellite', '', 'ART,PMTCT,PEP', 889, 23, 2326, 216, 0, 'public', 1, 41, NULL, 'Not Classified', 'Chiakariga', 24),
								(2329, 'Tharaka District Hospital Dispensing Point', '', 'satellite', '', 'ART,PMTCT,PEP', 889, 215, 2327, 216, 0, 'public', 1, 41, NULL, 'Not Classified', 'Marimanti', 34),
								(2330, 'GSN Kisauni', '', 'satellite', 'GSN', 'ART,PMTCT,PEP', 889, 16, 2328, 34, 0, 'ngo', 1, 28, NULL, 'Not Classified', 'Kisauni', 16),
								(2331, 'GSN Island', '', 'satellite', 'GSN', 'ART,PMTCT,PEP', 889, 16, 2329, 34, 0, 'ngo', 1, 28, NULL, 'Not Classified', 'Mombasa', 16),
								(2332, 'Komarock Medical Clinic', '13038', 'satellite', '', 'ART,PMTCT', 998, 170, 2330, 376, 0, 'private', 1, 30, NULL, 'Not Classified', 'Kayole', 16),
								(2333, 'Mivukoni Health Centre', '12539', 'satellite', '', 'ART,PMTCT,PEP', 828, 84, 2331, 338, 0, 'public', 1, 18, NULL, 'Not Classified', 'Katuka', 24),
								(2336, 'Mundika Maternity & Nursing Home', '20062', 'satellite', '', 'ART,PMTCT,PEP', NULL, 169, 2334, 211, 0, 'private', 1, 30, NULL, 'Not Classified', 'Ngei', 27),
								(2337, 'Kipkabus Health Centre', '14893', 'satellite', '', 'ART,PMTCT,PEP', 1363, 103, 2335, 144, 0, 'public', 1, 44, NULL, 'Not Classified', 'Kipkabus', 24),
								(2338, 'Chepkanga Health Centre', '14335', 'satellite', '', 'ART,PMTCT,PEP', 1363, 103, 2336, 144, 0, 'public', 1, 44, NULL, 'Not Classified', 'Sergoit', 24),
								(2339, 'St Marys Ringa', '20364', 'standalone', '', '', NULL, 212, 2337, NULL, 0, 'mission', 0, 8, NULL, 'Not Classified', 'Kojwach', 2),
								(2340, 'Nyangena Sub District Hospital', '13924', 'standalone', '', 'ART,PMTCT', NULL, 97, 2338, NULL, 0, '', 0, 34, NULL, 'Not Classified', ' Kemera', 0),
								(2341, 'Masii Health Centre Dispensing Point', '12475', 'satellite', '', 'ART,PMTCT,PEP,LAB', NULL, 83, 2339, 629, 0, 'public', 1, 22, NULL, 'Not Classified', 'Masii', 0),
								(2342, 'Pedo Dispensary', '18725', 'standalone', '', 'ART,PMTCT,PEP', NULL, 210, 2340, NULL, 0, 'public', 0, 17, NULL, 'Not Classified', 'West Kandaria', 24),
								(2343, 'Isamwera Dispensary', '16425', 'standalone', '', 'ART,PMTCT', NULL, 93, 2341, NULL, 0, 'public', 0, 16, NULL, 'Not Classified', 'Boroko', 24),
								(2344, 'AIC Nyakach Mission Hospital', '', 'standalone', '', 'ART,PMTCT', NULL, 210, 2342, NULL, 0, 'mission', 0, 17, NULL, 'Not Classified', '', 1),
								(2345, 'Bukalama Dispensary', '17156', 'satellite', '', 'ART,PMTCT,PEP', 874, 126, 2343, 28, 0, 'public', 1, 4, NULL, 'Not Classified', 'Bugengi', 24),
								(2346, 'Esikulu Dispensary', '20171', 'satellite', '', 'ART,PMTCT,PEP', 874, 126, 2344, 28, 0, 'public', 1, 4, NULL, 'Not Classified', 'Esikulu', 24),
								(2347, 'Nasira Dispensary', '19887', 'satellite', '', 'ART,PMTCT,PEP', 874, 126, 2345, 28, 0, 'public', 1, 4, NULL, 'Not Classified', ' Nasira', 24),
								(2348, 'New Busia Maternity & Nursing Home', '16080', 'satellite', '', 'ART,PMTCT,PEP', 874, 126, 2346, 28, 0, 'private', 1, 4, NULL, 'Not Classified', 'Township', 27),
								(2349, 'Sericho Health Centre', '12729', 'satellite', '', 'ART,PMTCT,PEP,LAB,RTK', 889, 179, 2347, 854, 0, '', 1, 9, NULL, 'Not Classified', '', 0),
								(2350, 'Gafarsa Health Centre', '12025', 'satellite', '', 'ART,PMTCT,PEP,LAB,RTK', 889, 179, 2348, 854, 0, '', 1, 9, NULL, 'Not Classified', '', 0),
								(2351, 'Ndaragwa Health Centre', '10829', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 59, 2349, NULL, 0, '', 0, 35, NULL, 'Not Classified', '', 0),
								(2352, 'Kahembe Health Centre', '10419', 'standalone', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 59, 2350, NULL, 0, '', 0, 35, NULL, 'Not Classified', '', 0),
								(2353, 'Sina Dispensary', '15580', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 1363, 40, 2351, 76, 0, 'public', 1, 47, NULL, 'Not Classified', 'Sina', 3),
								(2354, 'Konyao Dispensary', '14988', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP', 1363, 40, 2352, 76, 0, 'public', 1, 47, NULL, 'Not Classified', 'Kapchok', 3),
								(2355, 'Garbatulla District Hospital Dispensing Point', '12029', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 179, 2353, 854, 0, 'public', 1, 9, NULL, 'Level 1', '', 42),
								(2356, 'Olpajeta Dispensary', '15430', 'satellite', '', 'ART,PMTCT,PEP', 1728, 283, 2354, 161, 0, 'public', 1, 20, NULL, 'Not Classified', 'Lamuria', 24),
								(2357, 'Lwandeti Dispensary', '15982', 'satellite', '', 'ART,PMTCT,PEP', 874, 36, 2355, 130, 0, 'public', 1, 11, NULL, 'Not Classified', 'Lwandeti', 24),
								(2358, 'Matete Health Centre Dispensing Point', '', 'satellite', '', 'ART,PMTCT,PEP,LAB', 874, 101, 2356, 130, 0, 'public', 1, 11, NULL, 'Not Classified', 'chebaywa', 24),
								(2359, 'Kahawa Garrison Health Centre', '12996', 'standalone', '', 'ART,PMTCT,PEP', NULL, 166, 2357, NULL, 0, 'public', 0, 30, NULL, 'Not Classified', 'Kasarani', 24),
								(2360, 'Samburu Health Centre Dispensing Point', '11768', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 889, 149, 2358, 636, 0, 'public', 1, 19, NULL, 'Level 3', '', 24),
								(2361, 'Doldol Health Centre Dispensing Point', '14404', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', 1728, 112, 2359, 161, 0, 'public', 1, 20, NULL, 'Level 4', 'kurikuri', 24),
								(2362, 'Ndindika Health Centre Dispensing Point', '', 'satellite', '', 'ART,PMTCT,PEP,LAB', 1728, 52, 2360, 516, 0, 'public', 1, 20, NULL, 'Not Classified', 'Kinamba', 24),
								(2363, 'Thigio Dispensary (Laikipia West)', '15723', 'satellite', '', 'ART,PMTCT,PEP', 1728, 52, 2361, 516, 0, 'public', 1, 20, NULL, 'Not Classified', 'Gituamba', 24),
								(2364, 'Mwenje Dispensary', '15266', 'satellite', '', 'ART,PMTCT,PEP', 1728, 112, 2362, 516, 0, 'public', 1, 20, NULL, 'Not Classified', 'Mwenje', 24),
								(2365, 'Ilpolei Dispensary', '14561', 'satellite', '', 'ART,PMTCT,PEP', 1728, 112, 2363, 514, 0, 'public', 1, 20, NULL, 'Not Classified', 'Ilpolei', 24),
								(2366, 'Oljogi Dispensary', '15405', 'satellite', '', 'ART,PMTCT,PEP', 1728, 112, 2364, 514, 0, 'private', 1, 20, NULL, 'Not Classified', 'Ilpolei', 27),
								(2367, 'Kanyuambora Dispensary', '12185', 'satellite', '', 'ART,PMTCT,PEP,LAB', 889, 26, 2365, 137, 0, 'public', 1, 6, NULL, 'Not Classified', 'Kanyuambora', 24),
								(2368, 'Kiritiri Health Centre Dispensing Point', '', 'satellite', '', 'ART,PMTCT,PEP,LAB', 889, 44, 2366, 598, 0, 'public', 1, 6, NULL, 'Not Classified', 'Gachoka', 24),
								(2369, 'Ndeiya Health Centre', '10831', 'standalone', '', 'ART,PMTCT,PEP', NULL, 54, 2367, NULL, 0, 'public', 0, 13, NULL, 'Not Classified', ' Ndreu', 24),
								(2370, 'Family Health Options Kenya (Eldoret)', '16348', 'satellite', '', 'ART,PMTCT,PEP', 1363, 103, 2368, 144, 0, 'private', 1, 44, NULL, 'Not Classified', 'Chepkoilel', 27),
								(2371, 'St Lukes Orthopaedic and Trauma Hospital', '18776', 'satellite', '', 'ART,PMTCT,PEP,LAB', 1363, 103, 2369, 144, 0, 'private', 1, 44, NULL, 'Not Classified', 'kapsoya', 27),
								(2372, 'Cedar Associate Clinic', '14280', 'satellite', '', 'ART,PMTCT,PEP', 1363, 103, 2370, 144, 0, 'private', 1, 44, NULL, 'Not Classified', 'kapsoya', 27),
								(2373, 'Reale Medical Clinic', '18983', 'satellite', '', 'ART,PMTCT,PEP,LAB', 1363, 103, 2371, 144, 0, 'private', 1, 44, NULL, 'Not Classified', 'Kibulgeny', 27),
								(2374, 'Chemolingot District Hospital Dispensing Point', '', 'satellite', '', 'ART,PMTCT,PEP,LAB', 1728, 101, 2372, 2014, 0, 'public', 1, 1, NULL, 'Not Classified', 'Kositei', 24),
								(2375, 'Churo GOK Dispensary', '20047', 'satellite', '', 'ART,PMTCT,PEP', 1728, 101, 2373, 2014, 0, 'public', 1, 1, NULL, 'Not Classified', 'Churo', 24),
								(2376, 'Ramula Health Centre ', '14030', 'standalone', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 17, 2374, NULL, 0, 'public', 0, 38, NULL, 'Level 3', '', 0),
								(2377, 'Kitalale Dispensary', '14946', 'satellite', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 121, 2375, 540, 0, 'public', 1, 42, NULL, 'Not Classified', '', 0),
								(2378, 'St Ursula Dispensary', '15669', 'satellite', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 121, 2376, 540, 0, 'mission', 1, 42, NULL, 'Not Classified', '', 0),
								(2379, 'Tom Mboya Dispensary', '15732', 'satellite', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 121, 2377, 540, 0, 'public', 1, 42, NULL, 'Not Classified', '', 0);
								INSERT INTO `sync_facility` (`id`, `name`, `code`, `category`, `sponsors`, `services`, `manager_id`, `district_id`, `address_id`, `parent_id`, `ordering`, `affiliation`, `service_point`, `county_id`, `hcsm_id`, `keph_level`, `location`, `affiliate_organization_id`) VALUES
								(2380, 'Ojola Dispensary', '13971', 'satellite', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 29, 2378, 423, 0, 'public', 1, 17, NULL, 'Not Classified', '', 0),
								(2381, 'Rota Dispensary', '14060', 'satellite', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 29, 2379, 423, 0, 'public', 1, 17, NULL, 'Not Classified', '', 0),
								(2382, 'Usoma Dispensary', '16664', 'satellite', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 29, 2380, 423, 0, 'public', 1, 17, NULL, 'Not Classified', '', 0),
								(2383, 'Ober Kamoth HC', '13954', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 29, 2381, 423, 0, 'public', 1, 17, NULL, 'Not Classified', '', 0),
								(2385, 'St Elizabeth Lwak Mission Hospital Dispensing Point', '13739', 'satellite', 'PEPFAR', 'ART,PEP,LAB,RTK', NULL, 98, 2383, 110, 0, 'mission', 1, 38, NULL, 'Level 3', 'West Asembo', 2),
								(2386, 'St Elizabeth Chiga Health Centre service point', '14106', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 2, 2384, NULL, 0, 'mission', 1, 17, NULL, 'Level 3', 'East Kolwa', 2),
								(2387, 'St Mark''s Lela Dispensary', '14118', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 29, 2385, 423, 0, 'public', 1, 17, NULL, 'Not Classified', '', 0),
								(2388, 'St Monica''s Mission Hospital Service Point, Kisumu', '14120', 'satellite', 'PEPFAR', 'ART,PEP,RTK', NULL, 2, 2386, 209, 0, 'mission', 1, 17, NULL, 'Level 3', 'East Kajulu', 2),
								(2389, 'St Monica Rapogi Health Centre Dispensing Point', '14121 ', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 53, 2387, 1058, 0, 'mission', 1, 27, NULL, 'Level 3', 'North Kanyamkago', 2),
								(2390, 'Holy Family Oriang Mission Dispensary Dispensing Point', '13604', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 212, 2388, 1326, 0, 'mission', 1, 8, NULL, 'Level 2', ' Kawuor', 2),
								(2391, 'Kendu Adventist Hospital Dispensing Point', '13667', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 212, 2389, 88, 0, 'mission', 1, 8, NULL, 'Level 1', 'North Karachuonyo', 2),
								(2392, 'Friends Lugulu Mission Hospital Dispensing Point', '15965', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB', NULL, 30, 2390, 49, 0, 'mission', 1, 3, NULL, 'Level 2', 'Misikhu', 2),
								(2393, 'Maseno Mission Hospital Dispensing Point', '13781', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 29, 2391, 2, 0, 'mission', 1, 17, NULL, 'Level 1', 'North West Kisumu', 2),
								(2394, 'Homa Hills Health Centre Dispensing Point', '13606', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 212, 2392, NULL, 0, 'mission', 1, 8, NULL, 'Level 3', '', 2),
								(2395, 'Holy Family Nangina Mission Hospital Dispensing Point', '16073', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 133, 2393, 58, 0, 'mission', 1, 4, NULL, 'Level 4', 'Nangosia', 2),
								(2396, 'St Elizabeth Hospital, Mukumu Dispensing Point', '16030', 'satellite', 'PEPFAR', 'ART,PMTCT,PEP,LAB,RTK', NULL, 128, 2394, 199, 0, 'mission', 1, 11, NULL, 'Not Classified', 'Khayenga', 2),
								(2397, 'Mumias Dispensary Dispensing Point', '16035', 'satellite', 'PEPFAR', 'ART,PMTCT,LAB', NULL, 18, 2395, 1983, 0, 'public', 1, 11, NULL, 'Not Classified', 'lurekp', 2),
								(2398, 'Gatunga Health Centre', '12034', 'satellite', '', 'ART,PMTCT,PEP', NULL, 23, 2396, 216, 0, 'mission', 1, 41, NULL, 'Level 3', '', 9),
								(2399, 'Mariakani Community Health Care Services', '16188', 'satellite', '', 'ART,PMTCT,PEP', NULL, 50, 2397, NULL, 0, '', 1, 14, NULL, 'Level 1', '', 0),
								(2400, 'Rabai Health Centre Dispensing point', '11748', 'satellite', '', 'ART,PMTCT', NULL, 50, 2398, 301, 0, '', 1, 14, NULL, 'Not Classified', '', 0),
								(2401, 'Kimbimbi Sub-District Hospital Dispensing point', '10609', 'satellite', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 48, 2399, 97, 0, 'public', 1, 15, NULL, 'Level 4', '', 0),
								(2402, 'St Joseph''s Dispensary (Dagoretti)', '13210', 'satellite', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 163, 2400, NULL, 0, '', 1, 30, NULL, 'Level 2', '', 0),
								(2403, 'Hoymas VCT (Nairobi)', '20063', 'satellite', '', 'ART,PMTCT,PEP,LAB,RTK', NULL, 165, 2401, 584, 0, '', 1, 30, NULL, 'Level 2', '', 0),
								(2404, 'Mlaleo Health Centre', '18210', 'satellite', '', 'ART', NULL, 16, 2402, NULL, 1, 'public', 1, 28, NULL, 'Level 1', '', 1),
								(2405, 'Mbaka Oromo', '20199', 'satellite', 'PEPFAR', 'ART', NULL, 94, 2403, 423, 0, '', 1, 17, NULL, 'Not Classified', '', 43),
								(2406, 'Mainga Dispensary', '21208', 'satellite', 'PEPFAR', 'ART', NULL, 94, 2404, 423, 1, '', 1, 17, NULL, 'Not Classified', '', 0),
								(2407, 'SOS childrens Home (Kisumu)', '20836', 'standalone', '', 'ART', NULL, 94, 2405, 423, 1, 'public', 1, 17, NULL, 'Not Classified', '', 1);
								";
                            

                            $tables['vw_patient_list']="CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_patient_list` AS select `p`.`patient_number_ccc` AS `ccc_number`,`p`.`first_name` AS `first_name`,`p`.`other_name` AS `other_name`,`p`.`last_name` AS `last_name`,`p`.`dob` AS `date_of_birth`,round(((to_days(curdate()) - to_days(`p`.`dob`)) / 360),0) AS `age`,if((round(((to_days(curdate()) - to_days(`p`.`dob`)) / 360),0) >= 14),'Adult','Paediatric') AS `maturity`,`p`.`pob` AS `pob`,if((`p`.`gender` = 1),'MALE','FEMALE') AS `gender`,if((`p`.`pregnant` = 1),'YES','NO') AS `pregnant`,`p`.`weight` AS `current_weight`,`p`.`height` AS `current_height`,`p`.`sa` AS `current_bsa`,`p`.`phone` AS `phone_number`,`p`.`physical` AS `physical_address`,`p`.`alternate` AS `alternate_address`,`p`.`other_illnesses` AS `other_illnesses`,`p`.`other_drugs` AS `other_drugs`,`p`.`adr` AS `drug_allergies`,if((`p`.`tb` = 1),'YES','NO') AS `tb`,if((`p`.`smoke` = 1),'YES','NO') AS `smoke`,if((`p`.`alcohol` = 1),'YES','NO') AS `alcohol`,`p`.`date_enrolled` AS `date_enrolled`,`ps`.`name` AS `patient_source`,`s`.`Name` AS `supported_by`,`rst`.`name` AS `service`,`r1`.`regimen_desc` AS `start_regimen`,`p`.`start_regimen_date` AS `start_regimen_date`,`pst`.`Name` AS `current_status`,if((`p`.`sms_consent` = 1),'YES','NO') AS `sms_consent`,`p`.`fplan` AS `family_planning`,`p`.`tbphase` AS `tbphase`,`p`.`startphase` AS `startphase`,`p`.`endphase` AS `endphase`,if((`p`.`partner_status` = 1),'Concordant',if((`p`.`partner_status` = 2),'Discordant','')) AS `partner_status`,`p`.`status_change_date` AS `status_change_date`,if((`p`.`partner_type` = 1),'YES','NO') AS `disclosure`,`p`.`support_group` AS `support_group`,`r`.`regimen_desc` AS `current_regimen`,`p`.`nextappointment` AS `nextappointment`,(to_days(`p`.`nextappointment`) - to_days(curdate())) AS `days_to_nextappointment`,`p`.`start_height` AS `start_height`,`p`.`start_weight` AS `start_weight`,`p`.`start_bsa` AS `start_bsa`,if((`p`.`transfer_from` <> ''),`f`.`name`,'N/A') AS `transfer_from`,`dp`.`name` AS `prophylaxis` from ((((((((`patient` `p` left join `regimen` `r` on((`r`.`id` = `p`.`current_regimen`))) left join `regimen` `r1` on((`r1`.`id` = `p`.`start_regimen`))) left join `patient_source` `ps` on((`ps`.`id` = `p`.`source`))) left join `supporter` `s` on((`s`.`id` = `p`.`supported_by`))) left join `regimen_service_type` `rst` on((`rst`.`id` = `p`.`service`))) left join `patient_status` `pst` on((`pst`.`id` = `p`.`current_status`))) left join `facilities` `f` on((`f`.`facilitycode` = `p`.`transfer_from`))) left join `drug_prophylaxis` `dp` on((`dp`.`id` = `p`.`drug_prophylaxis`))) where (`p`.`active` = '1');";

                $tables['vw_routine_refill_visit'] = "CREATE OR REPLACE VIEW vw_routine_refill_visit AS
							    SELECT 
							    	p.patient_number_ccc AS patient_number,
							    	rst.name AS type_of_service,
							    	s.Name AS client_support,
							    	CONCAT_WS(' ', p.first_name, p.other_name, p.last_name) AS patient_name,
							    	FLOOR(DATEDIFF(CURDATE(),p.dob)/365) as current_age,
							    	g.name AS sex,
							    	CONCAT_WS(' | ', r.regimen_code, r.regimen_desc) AS regimen,
							    	pv.dispensing_date AS visit_date,
							    	pv.current_weight AS current_weight,
							        CASE 
							        WHEN pv.pill_count > 0 AND (pv.missed_pills - pv.pill_count) >= pv.pill_count THEN 0
							        WHEN pv.pill_count > 0 AND (pv.missed_pills - pv.pill_count) > 0 THEN ROUND(((pv.missed_pills - pv.pill_count)/pv.pill_count)*100,2)
							        WHEN pv.missed_pills NOT REGEXP '[0-9]+' OR pv.missed_pills IS NULL THEN '-'
							        ELSE 100 END AS missed_pill_adherence,
							        CASE 
							        WHEN pv.pill_count > 0 AND (pv.months_of_stock - pv.pill_count) >= pv.pill_count THEN 0
							        WHEN pv.pill_count > 0 AND (pv.months_of_stock - pv.pill_count) > 0 THEN ROUND(((pv.months_of_stock - pv.pill_count)/pv.pill_count)*100,2)
							        WHEN pv.months_of_stock NOT REGEXP '[0-9]+' OR pv.months_of_stock IS NULL THEN '-'
							        ELSE 100 END AS pill_count_adherence,
							        CASE 
							        WHEN REPLACE(pv.adherence, '%', '') > 100 THEN 100
							        WHEN REPLACE(pv.adherence, '%', '') < 0 THEN 0
							        WHEN REPLACE(pv.adherence, '%', '') = 'Infinity' THEN ''
							        ELSE REPLACE(pv.adherence, '%', '')
							        END AS appointment_adherence,
							    	ps.name AS source
							    FROM patient_visit pv
							    LEFT JOIN patient p ON p.patient_number_ccc = pv.patient_id
							    LEFT JOIN regimen_service_type rst ON rst.id = p.service
							    LEFT JOIN supporter s ON s.id = p.supported_by
							    LEFT JOIN gender g ON g.id = p.gender
							    LEFT JOIN regimen r ON r.id = pv.regimen
							    LEFT JOIN patient_source ps on ps.id = p.source
							    LEFT JOIN visit_purpose v ON v.id = pv.visit_purpose
							    WHERE pv.active = 1
							    AND v.name LIKE '%routine%';";

            foreach($tables as $table=>$statements){
            if (!$this->db->table_exists($table)){
            	$statements=explode(";",$statements);
            	foreach($statements as $statement){
            		$this->db->query($statement);
            	}
		        $count++;
			}
        }

        if($count>0){
 			$message="(".$count.") tables created!<br/>";
        }
        return $message;
	}

	public function update_database_columns(){
		$message='';
		$statements['isoniazid_start_date']='ALTER TABLE patient ADD isoniazid_start_date varchar(20)';
		$statements['isoniazid_end_date']='ALTER TABLE patient ADD isoniazid_end_date varchar(20)';
		$statements['tb_category']='ALTER TABLE patient ADD tb_category varchar(2)';
		$statements['spouses']='ALTER TABLE `spouses` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT';
		$statements['dependants']='ALTER TABLE `dependants` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT';
		$statements['source_destination'] = "ALTER TABLE  `drug_stock_movement` CHANGE  `Source_Destination`  `Source_Destination` VARCHAR( 50 )";
		if ($statements) {
			foreach ($statements as $column => $statement) {
				if ($statement != null) {
				    $db_debug = $this->db->db_debug;
					$this->db->db_debug = false;
					$this -> db -> query($statement);
					$this->db->db_debug = $db_debug;
				}
			}
		}
		return $message;
	}
   
        //function to download guidelines from the nascop 
        public function get_guidelines(){
         $this->load->library('ftp');

        $config['hostname'] = $this->ftp_url;
        $config['username'] = 'demo';
        $config['password'] = 'demo';
        $config['port']     = 21;
        $config['passive']  = TRUE;
        $config['debug']    = TRUE;

        $this->ftp->connect($config);
        $server_file="/";
        $dir = realpath($_SERVER['DOCUMENT_ROOT']);
       
        
        $files = $this->ftp->list_files($server_file);
	        if(!empty($files))
	        {
		        foreach($files as $file){
		             $local_file = $dir . "/ADT/assets/guidelines". $file;
		             $downloadfile= $this->ftp->download($file,$local_file , 'ascii');
		        }
	        }
        }
   
        public function update_system_version(){
		$url = $this -> nascop_url . "sync/gitlog";
		$facility_code = $this -> session -> userdata("facility");
		$hash=Git_Log::getLatestHash();
		$results = array("facility_code" => $facility_code, "hash_value" => $hash);
		$json_data = json_encode($results, JSON_PRETTY_PRINT);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('json_data' => $json_data));
		$json_data = curl_exec($ch);
		if (empty($json_data)) {
			$message = "cURL Error: " . curl_error($ch)."<br/>";
		} else {
			$messages = json_decode($json_data, TRUE);
			$message = $messages[0]."<br/>";
		}
		curl_close($ch);
		return $message;
	}

	public function update_reporting() {
		$deadline = date('Y-m-10');
		$today = date('Y-m-d');
		$notification_days = 10;
		$notification = "";
		$message = "";
		$notification_link = site_url('order');
		if ($deadline > $today) {
			$diff = abs(strtotime($deadline) - strtotime($today));
			$years = floor($diff / (365 * 60 * 60 * 24));
			$months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
			$period = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));
			if ($notification_days >= $period) {
				$notification = "Dear webADT User,<br/>";
				$notification .= "The order reporting deadline is in " . $period . " days.<br/>";
				$notification .= "The Satellites List is below: <br/>";
			}
			//get reporting satellites
			$start_date = date('Y-m-01', strtotime("-1 month"));
			$facility_code = $this -> session -> userdata("facility");
			$central_site = Sync_Facility::getId($facility_code, 0);
			$central_site = $central_site['id'];

			$sql = "SELECT sf.name as facility_name,sf.code as facility_code,IF(c.id,'reported','not reported') as status
			        FROM sync_facility sf
			        LEFT JOIN cdrr c ON c.facility_id=sf.id AND c.period_begin='$start_date' 
			        WHERE sf.parent_id='$central_site'
			        AND sf.category LIKE '%satellite%'
			        AND sf.name NOT LIKE '%dispensing%'
			        GROUP BY sf.id";
			$query = $this -> db -> query($sql);
			$satellites = $query -> result_array();

			$notification .= "<table border='1'>";
			$notification .= "<thead><tr><th>Name</th><th>Code</th><th>Status</th></tr></thead><tbody>";
			if ($satellites) {
				foreach ($satellites as $satellite) {
					$notification .= "<tr><td>" . $satellite['facility_name'] . "</td><td>" . $satellite['facility_code'] . "</td><td>" . $satellite['status'] . "</td></tr>";
				}
			}
			$notification .= "</tbody></table>";

			//send notification via email 
			ini_set("SMTP", "ssl://smtp.gmail.com");
			ini_set("smtp_port", "465");

			$sql = "SELECT DISTINCT(Email_Address) as email 
			        FROM users u
			        LEFT JOIN access_level al ON al.id=u.Access_Level
			        WHERE al.Level_Name LIKE '%facility%' 
                    AND u.Facility_Code = '$facility_code'
			        AND Email_Address !=''
			        AND Email_Address !='kevomarete@gmail.com'";
			$query = $this -> db -> query($sql);
			$emails = $query -> result_array();
			if ($emails) {
				foreach($emails as $email)
				{
					$mail_list[] = $email['email'];
				}
			}
			if(!empty($mail_list))
			{
				$mail_list = implode(",", $mail_list);

				$config['mailtype'] = "html";
				$config['protocol'] = 'smtp';
				$config['smtp_host'] = 'ssl://smtp.googlemail.com';
				$config['smtp_port'] = 465;
				$config['smtp_user'] = stripslashes('webadt.chai@gmail.com');
				$config['smtp_pass'] = stripslashes('WebAdt_052013');

				$this -> load -> library('email', $config);

				$this -> email -> set_newline("\r\n");
				$this -> email -> from('webadt.chai@gmail.com', "WEB_ADT CHAI");
				$this -> email -> to("$mail_list");
				$this -> email -> subject("ORDER REPORTING NOTIFICATION");
				$this -> email -> message("$notification");

				if ($this -> email -> send()) {
					$message = 'Reporting Notification was sent!<br/>';
					$this -> email -> clear(TRUE);
				} else {
					$message = 'Reporting Notification Failed!<br/>';
				}
			}
		}
		return $message;
	}
	
	function createStoredProcedures(){
		$data =array();
		
		$data["MAPS: Patient Revisit OC CM Stored Procedure"] ="
			DROP procedure IF EXISTS `sp_GetRevisitCMOC`;
			
			DELIMITER $$
			CREATE PROCEDURE `sp_GetRevisitCMOC` (IN start_date DATE, IN end_date DATE)
			BEGIN
				SELECT IF(temp2.other_illnesses LIKE '%cryptococcal%','revisit_cm','revisit_oc') as OI,COUNT(temp2.ccc_number) as total
				FROM (SELECT DISTINCT(pv.patient_id) as ccc_number,oi.name as opportunistic_infection FROM patient_visit pv
								INNER JOIN  opportunistic_infection oi ON oi.indication = pv.indication
							) as temp1
				INNER JOIN (
						SELECT DISTINCT(p.patient_number_ccc) as ccc_number,other_illnesses FROM patient p
						INNER JOIN patient_status ps ON ps.id = p.current_status
						WHERE p.date_enrolled < start_date
						AND ps.name LIKE '%active%'
				) as temp2 ON temp2.ccc_number = temp1.ccc_number
				WHERE temp2.other_illnesses LIKE '%cryptococcal%' OR temp1.opportunistic_infection LIKE '%oesophageal%';
			END$$
			
			DELIMITER ;";
		
		$data["MAPS: Revisit Patient By Gender Stored Procedure"] ="
			DROP procedure IF EXISTS `sp_GetRevisitPatient`;
			
			DELIMITER $$
			CREATE  PROCEDURE `sp_GetRevisitPatient`(IN start_date DATE, IN end_date DATE)
			BEGIN
			        SELECT COUNT(DISTINCT(p.id)) as total,IF(p.gender=1,'new_male','new_female') as  gender 
							FROM patient p
							LEFT JOIN patient_visit pv ON pv.patient_id = p.patient_number_ccc
							INNER JOIN patient_status ps ON ps.id=p.current_status
							WHERE p.date_enrolled < start_date 
							AND ( pv.dispensing_date BETWEEN start_date AND end_date)
							AND ps.name LIKE '%active%'
							GROUP BY p.gender;
			END$$
			
			DELIMITER ;";
			$message = "";	
			foreach ($data as $key => $value) {
				echo $value;$this ->db ->query($value);
				if($this->db->affected_rows() >0){
					$message.=$key. " successfully created ! <br>";
				}else{
					$message.=$key. " could not be created ! ".$this->db->_error_message()." <br>";
				}
			}
		return $message;
	}
	
	public function addIndex(){//Create indexes on columns in table;
		$columns = array(
						array(
							"table"=>"patient_visit",
							"column"=>"dispensing_date",
							"message"=>"Dispensing date index (Patient Visit) "
								),
						array(
							"table"=>"patient",
							"column"=>"date_enrolled",
							"message"=>"Date Enrolled index (Patient)"
								),
						array(
							"table"=>"drug_stock_movement",
							"column"=>"Source_Destination",
							"message"=>"Transaction Date index (Drug Stock Movement)"
								)
								);
		$message = "";
		foreach ($columns as $value) {
			$sql ="SHOW INDEX FROM ".$value['table']." WHERE KEY_NAME =  '".$value['column']."'";
			$res = $this ->db ->query($sql);
			if($result = $res->result_array()){
				$index_to_drop = $result[0]['Key_name'];
				$this ->db ->query("ALTER TABLE  ".$value['table']." DROP INDEX `$index_to_drop`");
			}
			$sql = "ALTER TABLE ".$value['table']." ADD INDEX (`".$value['column']."`)";
				
			if($this ->db ->query($sql)){
				$message.=$value['message']. " successfully created ! <br>";
			}else{
				$message.=$value['message']. " could not be created ! ".$this->db->_error_message()." <br>";
			}
			
		}
		
		return $message;
	}
}

ob_get_clean();
?>