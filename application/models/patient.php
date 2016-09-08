<?php
class Patient extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Medical_Record_Number', 'varchar', 10);
		$this -> hasColumn('Patient_Number_CCC', 'varchar', 10);
		$this -> hasColumn('First_Name', 'varchar', 50);
		$this -> hasColumn('Last_Name', 'varchar', 50);
		$this -> hasColumn('Other_Name', 'varchar', 50);
		$this -> hasColumn('Dob', 'varchar', 32);
		$this -> hasColumn('Pob', 'varchar', 100);
		$this -> hasColumn('Gender', 'varchar', 2);
		$this -> hasColumn('Pregnant', 'varchar', 2);
		$this -> hasColumn('Weight', 'varchar', 5);
		$this -> hasColumn('Height', 'varchar', 5);
		$this -> hasColumn('Sa', 'varchar', 5);
		$this -> hasColumn('Phone', 'varchar', 30);
		$this -> hasColumn('Physical', 'varchar', 100);
		$this -> hasColumn('Alternate', 'varchar', 50);
		$this -> hasColumn('Other_Illnesses', 'text');
		$this -> hasColumn('Other_Drugs', 'text');
		$this -> hasColumn('Adr', 'text');
		$this -> hasColumn('Drug_Allergies', 'text');
		$this -> hasColumn('Tb', 'varchar', 2);
		$this -> hasColumn('Smoke', 'varchar', 2);
		$this -> hasColumn('Alcohol', 'varchar', 2);
		$this -> hasColumn('Date_Enrolled', 'varchar', 32);
		$this -> hasColumn('Source', 'varchar', 2);
		$this -> hasColumn('Supported_By', 'varchar', 2);
		$this -> hasColumn('Timestamp', 'varchar', 32);
		$this -> hasColumn('Facility_Code', 'varchar', 10);
		$this -> hasColumn('Service', 'varchar', 5);
		$this -> hasColumn('Start_Regimen', 'varchar', 5);
		$this -> hasColumn('Start_Regimen_Date', 'varchar', 20);
		$this -> hasColumn('Machine_Code', 'varchar', 5);
		$this -> hasColumn('Current_Status', 'varchar', 10);
		$this -> hasColumn('SMS_Consent', 'varchar', 2);
		$this -> hasColumn('Partner_Status', 'varchar', 2);
		$this -> hasColumn('Fplan', 'text');
		$this -> hasColumn('tb_category', 'varchar', 2);
		$this -> hasColumn('Tbphase', 'varchar', 2);
		$this -> hasColumn('Startphase', 'varchar', 15);
		$this -> hasColumn('Endphase', 'varchar', 15);
		$this -> hasColumn('Disclosure', 'varchar', 2);
		$this -> hasColumn('Status_Change_Date', 'varchar', 2);
		$this -> hasColumn('Support_Group', 'varchar', 255);
		$this -> hasColumn('Current_Regimen', 'varchar', 255);
		$this -> hasColumn('Start_Regimen_Merged_From', 'varchar', 20);
		$this -> hasColumn('Current_Regimen_Merged_From', 'varchar', 20);
		$this -> hasColumn('NextAppointment', 'varchar', 20);
		$this -> hasColumn('Start_Height', 'varchar', 20);
		$this -> hasColumn('Start_Weight', 'varchar', 20);
		$this -> hasColumn('Start_Bsa', 'varchar', 20);
		$this -> hasColumn('Transfer_From', 'varchar', 100);
		$this -> hasColumn('Active', 'int', 5);
		$this -> hasColumn('Drug_Allergies', 'text');
		$this -> hasColumn('Tb_Test', 'int', '11');
		$this -> hasColumn('Pep_Reason', 'int', 11);
		$this -> hasColumn('who_stage', 'int', 11);
		$this -> hasColumn('drug_prophylaxis', 'varchar', 20);
		$this -> hasColumn('isoniazid_start_date', 'varchar', 20);
		$this -> hasColumn('isoniazid_end_date', 'varchar', 20);
		$this -> hasColumn('tb_category', 'varchar', 2);
	}

	public function setUp() {
		$this -> setTableName('patient');
		$this -> hasOne('District as PDistrict', array('local' => 'Pob', 'foreign' => 'id'));
		$this -> hasOne('Gender as PGender', array('local' => 'Gender', 'foreign' => 'id'));
		$this -> hasOne('Patient_Source as PSource', array('local' => 'Source', 'foreign' => 'id'));
		$this -> hasOne('Supporter as PSupporter', array('local' => 'Supported_By', 'foreign' => 'id'));
		$this -> hasOne('Regimen_Service_Type as PService', array('local' => 'Service', 'foreign' => 'id'));
		$this -> hasOne('Regimen as SRegimen', array('local' => 'Start_Regimen', 'foreign' => 'id'));
		$this -> hasOne('Regimen as Parent_Regimen', array('local' => 'Current_Regimen', 'foreign' => 'id'));
		$this -> hasOne('Patient_Status as Parent_Status', array('local' => 'Current_Status', 'foreign' => 'id'));
		$this -> hasOne('Facilities as TFacility', array('local' => 'Transfer_From', 'foreign' => 'facilitycode'));
		$this -> hasOne('Pep_Reason as PReason', array('local' => 'Pep_Reason', 'foreign' => 'id'));
		$this -> hasOne('Who_Stage as PStage', array('local' => 'who_stage', 'foreign' => 'id'));
		$this -> hasOne('Dependants as dependant', array('local' => 'patient_number_ccc', 'foreign' => 'child'));
		$this -> hasOne('Spouses as Spouse', array('local' => 'patient_number_ccc', 'foreign' => 'primary_spouse'));
	}

	public function getPatientNumbers($facility) {
		$query = Doctrine_Query::create() -> select("count(*) as Total_Patients") -> from("Patient") -> where("Facility_Code = $facility");
		$total = $query -> execute();
		return $total[0]['Total_Patients'];
	}

	public function getPagedPatients($offset, $items, $machine_code, $patient_ccc, $facility) {
		$query = Doctrine_Query::create() -> select("p.*") -> from("Patient p") -> leftJoin("Patient p2") -> where("p2.Patient_Number_CCC = '$patient_ccc' and p2.Machine_Code = '$machine_code' and p2.Facility_Code=$facility and p.Facility_Code=$facility") -> offset($offset) -> limit($items);
		$patients = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $patients;
	}

	public function getAllPatients($facility) {
		$query = Doctrine_Query::create() -> select("*") -> from("patient") -> where("Facility_Code='$facility'");
		$patients = $query -> execute();
		return $patients;
	}

	public function getPagedFacilityPatients($offset, $items, $facility) {
		$query = Doctrine_Query::create() -> select("*") -> from("Patient") -> where("Facility_Code=$facility") -> offset($offset) -> limit($items);
		$patients = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $patients;
	}

	public function getPatientAllergies($patient_no) {
		$query = Doctrine_Query::create() -> select("Adr") -> from("Patient") -> where("Patient_Number_CCC='$patient_no'") -> limit(1);
		$patients = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $patients[0];
	}

	public function getEnrollment($period_start, $period_end, $indicator) {
		$adult_age = 15;
		if ($indicator == "adult_male") {
			$condition = "AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'male' AND round(datediff('$period_start',p.dob)/360)>=$adult_age and p.Active='1'";
		} else if ($indicator == "adult_female") {
			$condition = "AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'female' AND round(datediff('$period_start',p.dob)/360)>=$adult_age and p.Active='1'";
		} else if ($indicator == "child_male") {
			$condition = "AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'male' AND round(datediff('$period_start',p.dob)/360)<$adult_age and p.Active='1'";
		} else if ($indicator == "child_female") {
			$condition = "AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'female' AND round(datediff('$period_start',p.dob)/360)<$adult_age and p.Active='1'";
		}
		$query = Doctrine_Query::create() -> select("p.PSource.Name as source_name,COUNT(*) as total") -> from("Patient p") -> where("p.Date_Enrolled BETWEEN '$period_start' AND '$period_end' $condition") -> groupBy("p.Source");
		$patients = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $patients;
	}

	public function getStages($period_start, $period_end, $indicator) {
		$adult_age = 15;
		if ($indicator == "adult_male") {
			$condition = "AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'male' AND round(datediff('$period_start',p.dob)/360)>=$adult_age and p.Active='1'";
		} else if ($indicator == "adult_female") {
			$condition = "AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'female' AND round(datediff('$period_start',p.dob)/360)>=$adult_age and p.Active='1'";
		} else if ($indicator == "child_male") {
			$condition = "AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'male' AND round(datediff('$period_start',p.dob)/360)<$adult_age and p.Active='1'";
		} else if ($indicator == "child_female") {
			$condition = "AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'female' AND round(datediff('$period_start',p.dob)/360)<$adult_age and p.Active='1'";
		}
		$query = Doctrine_Query::create() -> select("p.PStage.name as stage_name,COUNT(*) as total") -> from("Patient p") -> where("p.Date_Enrolled BETWEEN '$period_start' AND '$period_end' $condition") -> groupBy("p.who_stage");
		$patients = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $patients;
	}

	public function getPregnant($period_end, $indicator) {
		$adult_age = 15;
		if ($indicator == "F163") {
			$condition = "AND p.Parent_Status.Name LIKE '%active%' AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'female' AND round(datediff('$period_end',p.dob)/360)>=$adult_age and p.Active='1'";
		} else if ($indicator == "D163") {
			$condition = "AND p.Parent_Status.Name LIKE '%active%' AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'female' AND round(datediff('$period_end',p.dob)/360)<$adult_age and p.Active='1'";
		}
		$query = Doctrine_Query::create() -> select("'$indicator' as status_name,COUNT(*) as total") -> from("Patient p") -> where("p.Date_Enrolled <='$period_end' AND p.Pregnant='1' $condition") -> groupBy("p.Pregnant");
		$patients = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $patients;
	}
	
	public function getAllArv($period_end, $indicator) {
		$adult_age = 15;
		if ($indicator == "G164") {
			$condition = "AND p.Parent_Status.Name LIKE '%active%' AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'male' AND round(datediff('$period_end',p.dob)/360)>=$adult_age and p.Active='1'";
		} else if ($indicator == "F164") {
			$condition = "AND p.Parent_Status.Name LIKE '%active%' AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'female' AND round(datediff('$period_end',p.dob)/360)>=$adult_age and p.Active='1'";
		} else if ($indicator == "E164") {
			$condition = "AND p.Parent_Status.Name LIKE '%active%' AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'male' AND round(datediff('$period_end',p.dob)/360)<$adult_age and p.Active='1'";
		} else if ($indicator == "D164") {
			$condition = "AND p.Parent_Status.Name LIKE '%active%' AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'female' AND round(datediff('$period_end',p.dob)/360)<$adult_age and p.Active='1'";
		}
		$query = Doctrine_Query::create() -> select("'$indicator' as status_name,COUNT(*) as total") -> from("Patient p") -> where("p.Date_Enrolled <='$period_end' AND p.Pregnant !='1' $condition");
		$patients = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $patients;
	}

	public function get_patient($id = NULL , $columns = NULL){
		$query = Doctrine_Query::create() -> select($columns) -> from("Patient p") -> where( "p.id = ?" , array($id) );
		$patients = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $patients[0];
	}
	
	public function get_patient_details($id){
		$query = Doctrine_Query::create() -> select("p.Patient_Number_CCC,CONCAT_WS(' ',first_name,last_name,other_name) as names,Height,Weight,FLOOR(DATEDIFF(CURDATE(),p.dob)/365) as Dob,Pregnant,Tb") -> from("Patient p") -> where( "p.id = $id");
		$patients = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $patients[0];
	}

// Started on ART
public function start_on_ART(){
		$sql=("SELECT DATE_FORMAT(p.start_regimen_date,'%M-%Y') as period ,
		                 COUNT( p.patient_number_ccc) AS totalart 
                         FROM patient p 
                         LEFT JOIN regimen_service_type rst ON rst.id=p.service 
                         LEFT JOIN regimen r ON r.id=p.start_regimen 
                         LEFT JOIN patient_source ps ON ps.id = p.source
                         WHERE rst.name LIKE '%art%' 
                        AND ps.name NOT LIKE '%transfer%'
                         AND p.start_regimen !=''
                         AND p.start_regimen_date >= '2011-01-01'
                        GROUP BY YEAR(p.start_regimen_date),MONTH(p.start_regimen_date)
                        ORDER BY p.start_regimen_date DESC
                        
                       ");
		 $query = $this -> db -> query($sql);
		$patients = $query -> result_array();
         foreach($patients as $patient)
		{
			$data[$patient['period']][]=array('art_patients'=>(int)$patient['totalart']);

		}

		return $data;
	}
// Started on firstline regimen
public function start_on_firstline(){
		$sql=("SELECT DATE_FORMAT(p.start_regimen_date,'%M-%Y') as period ,
			         COUNT( p.patient_number_ccc) AS First 
                     FROM patient p 
                     LEFT JOIN regimen_service_type rst ON rst.id=p.service 
                     LEFT JOIN regimen r ON r.id = p.start_regimen 
                     LEFT JOIN patient_source ps ON ps.id = p.source 
                     WHERE r.line=1 
                     AND rst.name LIKE '%art%' 
                     AND ps.name NOT LIKE '%transfer%'
                     AND p.start_regimen !=''
                     AND p.start_regimen_date >= '2011-01-01'
                     GROUP BY YEAR(p.start_regimen_date),MONTH(p.start_regimen_date)
                     ORDER BY p.start_regimen_date DESC");
		
		$query = $this -> db -> query($sql);
		$patients = $query -> result_array();


		 foreach($patients as $patient)
		{
			$data[$patient['period']][]=array('firstline_patients'=>(int)$patient['First']);

		}

		return $data;

	}

	//Still in Firstline
	public function still_in_firstline(){

	$sql=("SELECT DATE_FORMAT(p.start_regimen_date,'%M-%Y') as period ,COUNT( * ) AS patients_still_firstline
                        FROM patient p
                        LEFT JOIN regimen_service_type rst ON rst.id=p.service
                        LEFT JOIN regimen r ON r.id=p.start_regimen
                        LEFT JOIN regimen r1 ON r1.id = p.current_regimen
                        LEFT JOIN patient_source ps ON ps.id = p.source
                        LEFT JOIN patient_status pt ON pt.id = p.current_status
                        WHERE rst.name LIKE '%art%'
                        AND ps.name NOT LIKE '%transfer%'
                        AND r.line=1
                        AND r1.line ='1'
                        AND p.start_regimen_date !=''
                        AND pt.Name LIKE '%active%'
                        GROUP BY YEAR(p.start_regimen_date),MONTH(p.start_regimen_date)
                        ORDER BY p.start_regimen_date DESC");	
         
         
         $query = $this -> db -> query($sql);
		 
		 $patients = $query -> result_array();

		foreach($patients as $patient)
		{
			$data[$patient['period']][]=array('Still_in_Firstline'=>(int)$patient['patients_still_firstline']);

		}

		return $data;
	}

	// Started ART 12 months ago
	public function started_art_12months(){
		$to_date = date('Y-m-d', strtotime($start_date. " -1 year"));
		$future_date = date('Y-m-d', strtotime($end_date . " -1 year"));
                
                $sql = "SELECT COUNT( * ) AS Total_Patients "
                        . " FROM patient p "
                        . " LEFT JOIN regimen_service_type rst ON rst.id=p.service "
                        . " LEFT JOIN regimen r ON r.id=p.start_regimen "
                        . " LEFT JOIN patient_source ps ON ps.id = p.source"
                        . " WHERE p.start_regimen_date"
                        . " BETWEEN '" . $to_date . "'"
                        . " AND '" . $future_date . "'"
                        . " AND rst.name LIKE  '%art%' "
                        . " AND ps.name NOT LIKE '%transfer%'"
                        . " AND p.start_regimen !=''";
		$patient_from_period_sql = $this -> db -> query($sql);
		$total_from_period_array = $patient_from_period_sql -> result_array();
		$total_from_period = 0;
		foreach ($total_from_period_array as $value) {
			$total_from_period = $value['Total_Patients'];
		}

	}

	public function get_lost_to_followup(){
		//Get total number of patients lost to follow up 
		$sql=("SELECT COUNT( p.patient_number_ccc ) AS total_patients_lost_to_follow, rst.name as service_type,
		 DATE_FORMAT(p.status_change_date,'%M-%Y') as period 
                        FROM patient p 
                        LEFT JOIN regimen_service_type rst ON rst.id=p.service 
                        LEFT JOIN regimen r ON r.id=p.start_regimen 
                        LEFT JOIN patient_source ps ON ps.id = p.source 
                        LEFT JOIN patient_status pt ON pt.id = p.current_status
                        WHERE rst.name LIKE '%art%' 
                        AND ps.name NOT LIKE '%transfer%' 
                        AND pt.Name LIKE '%lost%'
                        AND p.status_change_date >= '2011-01-01'
                        AND p.status_change_date!=''
                        GROUP BY YEAR(p.status_change_date),MONTH(p.status_change_date)
                        ORDER BY p.status_change_date DESC");
		$query = $this -> db -> query($sql);
		$patients = $query -> result_array();

		foreach($patients as $patient)
		{
			$data[$patient['period']][]=array('lost_to_followup'=>(int)$patient['total_patients_lost_to_follow']);

		}

		return $data;
		

	}

	public function adherence_reports(){

		$ontime=0;
		$missed=0;
		$defaulter = 0;
		$lost_to_followup = 0;
		$overview_total = 0;

		$adherence=array(
							'ontime'=> 0,
							'missed'=>0,
							'defaulter'=>0,
							'lost_to_followup'=>0);
		$sql=("SELECT 
                    pa.appointment as appointment,
                    pa.patient,
                    IF(UPPER(rst.Name) ='ART','art','non_art') as service,
        		    IF(UPPER(g.name) ='MALE','male','female') as gender,
        		    IF(FLOOR(DATEDIFF(CURDATE(),p.dob)/365)<15,'<15', IF(FLOOR(DATEDIFF(CURDATE(),p.dob)/365) >= 15 AND FLOOR(DATEDIFF(CURDATE(),p.dob)/365) <= 24,'15_24','>24')) as age
                FROM patient_appointment pa
                LEFT JOIN patient p ON p.patient_number_ccc = pa.patient
                LEFT JOIN regimen_service_type rst ON rst.id = p.service
                LEFT JOIN gender g ON g.id = p.gender 
                WHERE pa.appointment >'2011-01-01'
                GROUP BY pa.patient,pa.appointment
                ORDER BY pa.appointment");
		$query = $this -> db -> query($sql);
		$patients = $query -> result_array();
		
		#return $patients;

		if ($patients) {
			foreach ($patients as $patient) {
				$appointment=$patient['appointment'];
				$patient=$patient['patient'];
				$sql=("SELECT 
        		            DATEDIFF('$appointment',pv.dispensing_date) as no_of_days
	                    FROM v_patient_visits pv
	                    WHERE pv.patient_id='$patient'
	                    AND pv.dispensing_date >= '$appointment'
	                    GROUP BY pv.patient_id,pv.dispensing_date
	                    ORDER BY pv.dispensing_date ASC
	                    LIMIT 1");	
	                    $query=$this-> db ->query($sql);
	                    $results=$query -> result_array();	
	            	
	                                        }						
	       					            
		                } return $appointment; 

	                 						}

	

}
