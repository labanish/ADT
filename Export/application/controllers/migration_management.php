<?php
if (!defined('BASEPATH'))
exit('No direct script access allowed');

class Migration_Management extends MY_Controller {

	function __construct() {
		parent::__construct();
		$this -> load -> library('encrypt');
	}

	public function index() {
        //get cc_store_pharmacy
        $sql = "SELECT id as ccc_id,name as ccc_name 
                FROM ccc_store_service_point 
                WHERE active='1'";
		$query = $this -> db -> query($sql);
		$data['stores'] = $query -> result_array();
		//get databases in server
		$sql = "SHOW DATABASES";
		$query = $this -> db -> query($sql);
		$data['databases'] = $query -> result_array();
		//get tables
		$data['tables']=$this->mapping();
		//migration view
		$data['content_view'] = "migration_v";
		$data['banner_text'] = "Data Migration";
		$this->base_params($data);
	}
/**
 * [getFacilities description]
 * @return [type]
 */
	public function getFacilities(){
		$q=$_GET['q'];
		//get all facilities
        $sql = "SELECT facilitycode as facility_code,name as facility_name 
                FROM facilities
                WHERE name IS NOT NULL 
                AND name !=''
                AND name LIKE '%$q%'
                ORDER BY name ASC";
		$query = $this -> db -> query($sql);
		$results=$query -> result_array();

		if($results){
           foreach($results as $result){
           	 $answer[] = array("id"=>$result['facility_code'],"text"=>$result['facility_name']);
           }
		}else{
             $answer[] = array("id"=>"0","text"=>"No Results Found..");
		}
        echo json_encode($answer); 
	}

	public function mapping($facility_code=null,$ccc_pharmacy=null,$source_database=null,$table=null){
		$key = $this -> encrypt -> get_key();
		$timestamp=date('Y-m-d H:i:s');
		//migration table mapping
		$tables = array(
			 'Drug Source'=>array(
 	            'source'=>'tblarvstocktransourceordestination',
 	            'source_columns'=>array(
 	            	'sourceordestination',
 	            	'1',
 	            	$ccc_pharmacy),
 	            'destination'=>'drug_source',
 	            'destination_columns'=>array(
 	            	'name',
 	            	'active',
 	            	'ccc_store_sp'),
 	             'conditions'=>'',
 	             'before'=>array(),
 	             'update'=>array()
 	            ),
			 'Drug Destination'=>array(
			 	'source'=>'tbldestination',
			 	'source_columns'=>array(
			 		'destination',
			 		'1',
 	            	$ccc_pharmacy),
			 	'destination'=>'drug_destination',
			 	'destination_columns'=>array(
			 		'name',
			 		'active',
 	            	'ccc_store_sp'),
			 	'conditions'=>'WHERE destination IS NOT NULL',
			 	'before'=>array(),
 	            'update'=>array()
			 	),
			 'Drug Unit'=>array(
			 	'source'=>'tblunit',
			 	'source_columns'=>array(
			 		'unit',
 	            	$ccc_pharmacy),
			 	'destination'=>'drug_unit',
			 	'destination_columns'=>array(
			 		'Name',
 	            	'ccc_store_sp'),
			 	 'conditions'=>'',
			 	 'before'=>array(),
 	             'update'=>array()
			 	),
			 'Drug Generic Name' =>array(
			 	'source'=>'tblGenericName',
			 	'source_columns'=>array(
			 		'genericname',
			 		'1',
 	            	$ccc_pharmacy),
			 	'destination'=>'generic_name',
			 	'destination_columns'=>array(
			 		'name',
			 		'active',
 	            	'ccc_store_sp'),
			 	'conditions'=>'WHERE genericname is not null',
			 	'before'=>array(),
 	            'update'=>array()
			 	), 
			 'Drug' => array(
			 	'source'=>'tblARVDrugStockMain',
			 	'source_columns'=>array(
			 		'arvdrugsid',
			 		'packsizes',
			 		'unit',
			 		'genericname',
			 		'saftystock',
			 		'comment',
			 		'supportedby',
			 		'stddose',
			 		'stdduration',
			 		'stdqty',
			 		'IF(tbdrug=0,"F","T")as tbdrug',
			 		'IF(inuse=0,"F","T") as inuse',
			 		'1',
 	            	$ccc_pharmacy),
			 	'destination'=>'drugcode',
			 	'destination_columns'=>array(
			 		'drug',
			 		'pack_size',
			 		'unit',
			 		'generic_name',
			 		'safety_quantity',
			 		'comment',
			 		'supported_by',
			 		'dose',
			 		'duration',
			 		'quantity',
			 		'tb_drug',
			 		'drug_in_use',
			 		'supplied',
 	            	'ccc_store_sp'),
			 	 'conditions'=>'',
			 	 'before'=>array(
			 	 	'0'=>'UPDATE '.$source_database.'.tblarvdrugstockmain d,'.$source_database.'.tblgenericname g
			 	 	      SET d.genericname=g.genericname
			 	 	      WHERE d.genericname=g.genid
			 	 	      AND d.genericname IS NOT NULL'),
 	             'update'=>array(
 	             	'0'=>'UPDATE drugcode dc,drug_unit du 
 	             	      SET dc.unit=du.id 
 	             	      WHERE dc.unit=du.Name
 	             	      AND du.ccc_store_sp='.$ccc_pharmacy.'
 	             	      AND dc.ccc_store_sp='.$ccc_pharmacy,
 	             	'1'=>'UPDATE drugcode dc,generic_name g 
 	             	      SET dc.generic_name=g.id 
 	             	      WHERE dc.generic_name=g.name
 	             	      AND g.ccc_store_sp='.$ccc_pharmacy.'
 	             	      AND dc.ccc_store_sp='.$ccc_pharmacy)
			 	),
			 'Drug Brand'=>array(
			 	'source'=>'tbldrugbrandname',
			 	'source_columns'=>array(
			 		'arvdrugsid',
			 		'brandname',
			 		$ccc_pharmacy
 	            	),
			 	'destination'=>'brand',
			 	'destination_columns'=>array( 		
			 		'drug_id',
			 		'brand',
			 		'ccc_store_sp'),
			 	'conditions'=>'WHERE brandname IS NOT NULL',
			 	'before'=>array(),
 	            'update'=>array(
 	            	'0'=>'UPDATE brand b,drugcode dc 
 	            	      SET b.drug_id=dc.id 
 	            	      WHERE b.drug_id=dc.drug
 	            	      AND b.ccc_store_sp='.$ccc_pharmacy.'
 	             	      AND dc.ccc_store_sp='.$ccc_pharmacy)
			 	),
			 'Drug Stock Balance'=>array(
			 	'source'=>'tbldrugstockbatch',
			 	'source_columns'=>array(
			 		'arvdrugsid',
			 		'batchno',
			 		'expirydate',
			 		$ccc_pharmacy,
			 		$facility_code,
			 		'quantity',
			 		'trandate',
 	            	$ccc_pharmacy),
			 	'destination'=>'drug_stock_balance',
			 	'destination_columns'=>array(
			 		'drug_id',
			 		'batch_number',
			 		'expiry_date',
			 		'stock_type',
			 		'facility_code',
			 		'balance',
			 		'last_update',
 	            	'ccc_store_sp'),
			 	'conditions'=>'',
			 	'before'=>array(),
 	            'update'=>array(
 	             	'0'=>'UPDATE drug_stock_balance dsb,drugcode dc 
 	             	      SET dsb.drug_id=dc.id 
 	             	      WHERE dsb.drug_id=dc.drug
 	             	      AND dsb.ccc_store_sp='.$ccc_pharmacy.'
 	             	      AND dc.ccc_store_sp='.$ccc_pharmacy)
			 	),
			 'Dose' =>array(
			 	'source'=>'tblDose',
			 	'source_columns'=>array(
			 		'dose',
			 		'value',
			 		'frequency',
			 		'1',
 	            	$ccc_pharmacy),
			 	'destination'=>'dose',
			 	'destination_columns'=>array(
			 	    'Name',
			 		'value',
			 		'frequency',
			 		'Active',
 	            	'ccc_store_sp'),
			 	'conditions'=>'',
			 	'before'=>array(),
 	            'update'=>array()
			 	),
			 'Indication' =>array(
			 	'source'=>'tblIndication',
			 	'source_columns'=>array(
			 		'indicationname',
			 		'indicationcode',
			 		'1',
 	            	$ccc_pharmacy),
			 	'destination'=>'opportunistic_infection',
			 	'destination_columns'=>array(
			 		'name',
			 		'indication',
			 		'active',
 	            	'ccc_store_sp'),
			 	'conditions'=>'WHERE indicationname IS NOT NULL',
			 	'before'=>array(),
 	            'update'=>array()
			 	),
			 'Regimen Change Reason' => array(
			 	'source'=>'tblReasonforChange',
			 	'source_columns'=>array(
			 		'reasonforchange',
			 		'1',
 	            	$ccc_pharmacy),
			 	'destination'=>'regimen_change_purpose',
			 	'destination_columns'=>array(
			 		'name',
			 		'active',
			 		'ccc_store_sp'),
			 	'conditions'=>'WHERE reasonforchange IS NOT NULL',
			 	'before'=>array(),
 	            'update'=>array()
			 	), 
			 'Regimen Category' => array(
			 	'source'=>'tblRegimenCategory',
			 	'source_columns'=>array(
			 		'categoryname',
			 		'1',
 	            	$ccc_pharmacy),
			 	'destination'=>'regimen_category',
			 	'destination_columns'=>array(
			 		'Name',
			 		'Active',
 	            	'ccc_store_sp'),
			 	'conditions'=>'',
			 	'before'=>array(),
 	            'update'=>array()
			 	), 
			 'Regimen Service Type' => array(
			 	'source'=>'tblTypeOfService',
			 	'source_columns'=>array(
			 		'typeofservice',
			 		'1',
 	            	$ccc_pharmacy),
			 	'destination'=>'regimen_service_type',
			    'destination_columns'=>array(
			 		'name',
			 		'active',
 	            	'ccc_store_sp'),
			    'conditions'=>'',
			 	'before'=>array(),
 	            'update'=>array()
			    ), 
			 'Regimen' => array(
			 	'source'=>'tblRegimen',
			 	'source_columns'=>array(
			 		'r.regimencode',
			 		'r.regimen',
			 		'r.line',
			 		'r.remarks',
			 		'rc.categoryname',
			 		'rs.typeofservice',
			 		'IF(`show`=0,"0","1")',
 	            	$ccc_pharmacy),
			 	'destination'=>'regimen',
			 	'destination_columns'=>array(
			 		'regimen_code',
			 		'regimen_desc',
			 		'line',
			 		'remarks',
			 		'category',
			 		'type_of_service',
			 		'enabled',
 	            	'ccc_store_sp'),
			 	'conditions'=>'r LEFT JOIN '.$source_database.'.tblregimencategory rc ON rc.categoryid=r.category LEFT JOIN '.$source_database.'.tbltypeofservice rs ON rs.typeofserviceid=r.typeoservice',
			 	'before'=>array(),
 	            'update'=>array(
 	             	'0'=>'UPDATE regimen r,regimen_category rc
 	             	      SET r.category=rc.id 
 	             	      WHERE r.category=rc.Name
 	             	      AND rc.ccc_store_sp='.$ccc_pharmacy.'
 	             	      AND r.ccc_store_sp='.$ccc_pharmacy,
	         	    '1'=>'UPDATE regimen r,regimen_service_type rst
	         	          SET r.type_of_service=rst.id 
	         	          WHERE r.type_of_service=rst.name
	         	          AND rst.ccc_store_sp='.$ccc_pharmacy.'
	         	          AND r.ccc_store_sp='.$ccc_pharmacy)
			 	), 
			 'Regimen Drugs' => array(
			 	'source'=>'tblDrugsInRegimen',
			 	'source_columns'=>array(
			 		'regimencode',
			 		'combinations',
			 		'1',
			 		$ccc_pharmacy),
			 	'destination'=>'regimen_drug',
			 	'destination_columns'=>array(
			 		'regimen',
			 		'drugcode',
			 		'active',
			 		'ccc_store_sp'),
			 	'conditions'=>'WHERE regimencode IS NOT NULL AND combinations IS NOT NULL',
			 	'before'=>array(),
 	            'update'=>array(
 	            	'0'=>'UPDATE regimen_drug rd,regimen r
 	            	      SET rd.regimen=r.id
 	            	      WHERE rd.regimen=r.regimen_code
 	            	      AND rd.ccc_store_sp='.$ccc_pharmacy.'
	         	          AND r.ccc_store_sp='.$ccc_pharmacy,
 	            	'1'=>'UPDATE regimen_drug rd,drugcode dc
 	            	      SET rd.drugcode=dc.id
 	            	      WHERE rd.drugcode=dc.drug
 	            	      AND rd.ccc_store_sp='.$ccc_pharmacy.'
	         	          AND dc.ccc_store_sp='.$ccc_pharmacy)
			 	), 
			 'Patient Status' => array(
			 	'source'=>'tblCurrentStatus',
			 	'source_columns'=>array(
			 		'currentstatus',
			 		'1',
 	            	$ccc_pharmacy),
			 	'destination'=>'patient_status',
			 	'destination_columns'=>array(
			 		'Name',
			 		'Active',
 	            	'ccc_store_sp'),
			 	'conditions'=>'WHERE currentstatus IS NOT NULL',
			 	'before'=>array(),
 	            'update'=>array()
			 	), 
			 'Patient Source' => array(
			 	'source'=>'tblSourceOfClient',
			 	'source_columns'=>array(
			 		'sourceofclient',
			 		'1',
 	            	$ccc_pharmacy),
			 	'destination'=>'patient_source',
			 	'destination_columns'=>array(
			 		'name',
			 		'active',
 	            	'ccc_store_sp'),
			 	'conditions'=>'WHERE sourceofclient IS NOT NULL',
			 	'before'=>array(),
 	            'update'=>array()
			 	), 
			 'Patient' => array(
			 	'source'=>'tblARTPatientMasterInformation',
			 	'source_columns'=>array(
			 		'artid',
			 		'firstname',
			 		'surname',
			 		'IF(UCASE(sex)="MALE","1","2")',
			 		'IF(pregnant=0,"0","1")',
			 		'STR_TO_DATE(datetherapystarted, "%Y-%m-%d")',
			 		'weightonstart',
			 		'clientsupportedby',
			 		'otherdeaseconditions',
			 		'adrorsideeffects',
			 		'otherdrugs',
			 		'rst.id',
			 		'STR_TO_DATE(dateofnextappointment, "%Y-%m-%d")',
			 		'cs.currentstatus',
			 		'currentregimen',
			 		'regimenstarted',
			 		'address',
			 		'currentweight', 
			 		'startbsa',
			 		'currentbsa',
			 		'startheight',
			 		'currentheight',
			 		's.sourceofclient',
			 		'IF(tb=0,"0","1")',
			 		'STR_TO_DATE(datestartedonart, "%Y-%m-%d")',
			 		'STR_TO_DATE(datechangedstatus, "%Y-%m-%d")',
			 		'lastname',
			 		'IF( dateofbirth IS NULL, IF( age IS NULL , DATE_SUB( datetherapystarted, INTERVAL ncurrentage YEAR ) , DATE_SUB( datetherapystarted, INTERVAL age YEAR ) ) ,STR_TO_DATE( dateofbirth, "%Y-%m-%d"))',
			 		'placeofbirth', 
			 		'patientcellphone',
			 		'alternatecontact',
			 		'IF(patientsmoke=0,"0","1")',
			 		'IF(patientdrinkalcohol=0,"0","1")',
			 		'transferfrom',
			 		$facility_code,
			 		'1',
 	            	$ccc_pharmacy),
			 	'destination'=>'patient',
			 	'destination_columns'=>array(
			 		'patient_number_ccc',
			 		'first_name',
			 		'last_name',
			 		'gender',
			 		'pregnant',
			 		'date_enrolled',
			 		'start_weight',
			 		'supported_by',
			 		'other_illnesses',
			 		'adr',
			 		'other_drugs',
			 		'service',
			 		'nextappointment',
			 		'current_status',
			 		'current_regimen',
			 		'start_regimen',
			 		'physical',
			 		'weight',
			 		'start_bsa',
			 		'sa',
			 		'start_height',
			 		'height',
			 		'source',
			 		'tb',
			 		'start_regimen_date',
			 		'status_change_date',
			 		'other_name',
			 		'dob',
			 		'pob',
			 		'phone',
			 		'alternate',
			 		'smoke',
			 		'alcohol',
			 		'transfer_from',
			 		'facility_code',
			 		'drug_prophylaxis',
 	            	'ccc_store_sp'),
			 	'conditions'=>'p 
			 	LEFT JOIN '.$source_database.'.tbltypeofservice ps ON ps.typeofserviceid=p.typeofservice 
			 	LEFT JOIN  regimen_service_type rst ON ps.typeofservice=rst.name AND rst.ccc_store_sp='.$ccc_pharmacy.'
			 	LEFT JOIN '.$source_database.'.tblcurrentstatus cs ON cs.currentstatusid=p.currentstatus 
			 	LEFT JOIN '.$source_database.'.tblsourceofclient s ON s.sourceid=p.sourceofclient',
			 	'before'=>array(),
 	            'update'=>array(
 	             	'0'=>'UPDATE patient 
 	             	      SET start_regimen_date=date_enrolled 
 	             	      WHERE start_regimen_date=""',
 	             	'1'=>'UPDATE patient 
 	             	      SET status_change_date=start_regimen_date 
 	             	      WHERE status_change_date=""',
 	             	'2'=>'UPDATE patient p,regimen r 
 	             	      SET p.current_regimen=r.id 
 	             	      WHERE p.current_regimen=r.regimen_code
 	             	      AND p.ccc_store_sp='.$ccc_pharmacy.'
	         	          AND r.ccc_store_sp='.$ccc_pharmacy,
 	             	'3'=>'UPDATE patient p,regimen r 
 	             	      SET p.start_regimen=r.id 
 	             	      WHERE p.start_regimen=r.regimen_code
 	             	      AND p.ccc_store_sp='.$ccc_pharmacy.'
	         	          AND r.ccc_store_sp='.$ccc_pharmacy,
 	             	'4'=>'UPDATE patient p,patient_status ps
 	             	      SET p.current_status=ps.id
 	             	      WHERE p.current_status=ps.Name
 	             	      AND p.ccc_store_sp='.$ccc_pharmacy.'
	         	          AND ps.ccc_store_sp='.$ccc_pharmacy,
 	             	'5'=>'UPDATE patient p,patient_source s
 	             	      SET p.source=s.id
 	             	      WHERE p.source=s.name
 	             	      AND p.ccc_store_sp='.$ccc_pharmacy.'
	         	          AND s.ccc_store_sp='.$ccc_pharmacy)
			 	), 
			 'Patient Appointment' => array(
			 	'source'=>'tblARTPatientMasterInformation',
			 	'source_columns'=>array(
			 		'artid',
			 		'STR_TO_DATE(dateofnextappointment,"%Y-%m-%d")',
			 		$facility_code),
			 	'destination'=>'patient_appointment',
			 	'destination_columns'=>array(
			 		'patient',
			 		'appointment',
			 		'facility'),
			 	'conditions'=>'',
			 	'before'=>array(),
 	            'update'=>array()
			 	), 
			 'Transaction Type' => array(
			 	'source'=>'tblStockTransactionType',
			 	'source_columns'=>array(
			 		'transactiondescription',
			 		'reporttitle',
			 		'1',
 	            	$ccc_pharmacy),
			 	'destination'=>'transaction_type',
			 	'destination_columns'=>array(
			 		'name',
			 		'`desc`',
			 		'active',
 	            	'ccc_store_sp'),
			 	'conditions'=>'',
			 	'before'=>array(),
 	            'update'=>array(
 	             	'0'=>'UPDATE transaction_type 
 	             	      SET effect="1" 
 	             	      WHERE name LIKE "%Starting%" 
 	             	      OR name LIKE "%+%" 
 	             	      OR name LIKE "%Forward%" 
 	             	      OR name LIKE "%Received%"
	         	          AND ccc_store_sp='.$ccc_pharmacy)
			 	), 
			 'Visit Purpose' => array(
			 	'source'=>'tblVisitTransaction',
			 	'source_columns'=>array(
			 		'visittranname',
			 		'1',
 	            	$ccc_pharmacy),
			 	'destination'=>'visit_purpose',
			 	'destination_columns'=>array(
			 		'name',
			 		'active',
 	            	'ccc_store_sp'),
			 	'conditions'=>'WHERE visittranname IS NOT NULL',
			 	'before'=>array(),
 	            'update'=>array()
			 	), 
			 'Users' => array(
			 	'source'=>'tblSecurity',
			 	'source_columns'=>array(
			 		'name',
			 		'userid',
			 		'md5(concat("'.$key.'",password))',
			 		'IF(UCASE(authoritylevel)="USER","2","3")',
			 		$facility_code,
			 		'1',
			 		'1',
			 		'"'.$timestamp.'"',
 	            	$ccc_pharmacy,
 	            	'1'),
			 	'destination'=>'users',
			 	'destination_columns'=>array(
			 		'Name',
			 		'Username',
			 		'Password',
			 		'Access_Level',
			 		'Facility_Code',
			 		'Active',
			 		'Created_By',
			 		'Time_Created',
 	            	'ccc_store_sp',
					'Signature'),
			 	'conditions'=>'',
			 	'before'=>array(),
 	            'update'=>array(
 	            	'0'=>'UPDATE users
 	            	      SET Facility_Code='.$facility_code.',
 	            	      ccc_store_sp='.$ccc_pharmacy.'
 	            	      WHERE id IN(1,2)')
			 	), 
			 'Drug Transactions' => array(
			 	'source'=>'tblARVDrugStockTransactions',
			 	'source_columns'=>array(
			 		'dc.id',
			 		'STR_TO_DATE(trandate, "%Y-%m-%d")',
			 		'batchno',
			 		't.id',
			 		$facility_code,
			 		$facility_code,
			 		'IF( ds.sourceordestination IS NOT NULL , ds.sourceordestination, dsm.sourceordestination )',
			 		'STR_TO_DATE(expirydate, "%Y-%m-%d")',
			 		'npacks',
			 		'IF(t.effect="1",qty,"0")',
			 		'IF(t.effect="0",qty,"0")',
			 		'runningstock',
			 		'unitcost',
			 		'amount',
			 		'remarks',
			 		'operator',
			 		'reforderno',
			 		$facility_code,
			 		$ccc_pharmacy),
			 	'destination'=>'drug_stock_movement',
			 	'destination_columns'=>array(
			 		'drug',
			 		'transaction_date',
			 		'batch_number',
			 		'transaction_type',
			 		'source',
			 		'destination',
			 		'Source_Destination',
			 		'expiry_date',
			 		'packs',
			 		'quantity',
			 		'quantity_out',
			 		'balance',
			 		'unit_cost',
			 		'amount',
			 		'remarks',
			 		'operator',
			 		'order_number',	
			 		'facility', 
			 		'ccc_store_sp'),
			 	'conditions'=>'dsm 
			 	LEFT JOIN drugcode dc ON dc.drug=dsm.arvdrugsid AND dc.ccc_store_sp='.$ccc_pharmacy.' 
			 	LEFT JOIN '.$source_database.'.tblstocktransactiontype b ON b.transactiontype = dsm.transactiontype 
			 	LEFT JOIN transaction_type t ON t.name = b.transactiondescription AND t.ccc_store_sp='.$ccc_pharmacy.'
			 	LEFT JOIN '.$source_database.'.tblarvstocktransourceordestination ds ON ds.sdno = dsm.sourceordestination',
			 	'before'=>array(),
 	            'update'=>array()
			 	),
			 'Patient Transactions' => array(
			 	'source'=>'tblARTPatientTransactions',
			 	'source_columns'=>array(
			 		'artid',
			 		'vt.visittranname',
			 		'weight',
			 		'regimen',
			 		'reasonsforchange',
			 		'drugname',
			 		'batchno',
			 		'brandname',
			 		'indication',
			 		'pillcount',
			 		'pv.comment',
			 		'operator',
			 		$facility_code,
			 		'pv.dose',
			 		'STR_TO_DATE(dateofvisit, "%Y-%m-%d")',
			 		'arvqty',
			 		'lastregimen',			 		
			 		'pv.duration',
			 		'pillcount',
			 		'adherence',	
			 		'1',
			 		$ccc_pharmacy 		
			 		),
			 	'destination'=>'patient_visit',
			 	'destination_columns'=>array(
			 		'patient_id',
			 		'visit_purpose',
			 		'current_weight',
			 		'regimen',
			 		'regimen_change_reason',
			 		'drug_id',
			 		'batch_number',
			 		'brand',
			 		'indication',
			 		'pill_count',
			 		'comment',
			 		'user',
			 		'facility',
			 		'dose',
			 		'dispensing_date',	
			 		'quantity',
			 		'last_regimen',			 		
			 		'duration',
			 		'months_of_stock',
			 		'adherence',
			 		'active',
			 		'ccc_store_sp'
			 		),
			 	'conditions'=>'pv 
			 	LEFT JOIN '.$source_database.'.tblvisittransaction vt ON vt.transactioncode=pv.transactioncode',
			 	'before'=>array(),
 	            'update'=>array()
			 	)
			 );
            //if table is not null get value of array in position of the table
			if($table!=null){
			     $tables=$tables[$table];
			}
			return $tables;
	}

	public function migrate(){
		//get posted data
		$facility_code=$this->input->post('facility_code',TRUE);
		$ccc_pharmacy=$this->input->post('ccc_pharmacy',TRUE);
		$source_database=$this->input->post('source_database',TRUE);
		$table=$this->input->post('table',TRUE);

		//retrieve table data from tables array
		$config=$this->mapping($facility_code,$ccc_pharmacy,$source_database,$table);
		$source_table=$config['source'];
		$source_columns=implode(",", $config['source_columns']);
		$destination_table=$config['destination'];
		$destination_columns=implode(",", $config['destination_columns']);
		$conditions=$config['conditions'];
		$befores=$config['before'];
		$updates=$config['update'];

		//check migration log for last value migrated
		$sql = "SELECT id,last_index,count 
		        FROM migration_log 
		        WHERE source='$destination_table' 
		        AND ccc_store_sp='$ccc_pharmacy'";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		$offset=0;
		$last_index=0;
		$count=0;
		$migration_id=null;
		if($results){
		  $last_index = $results[0]['last_index'];
		  $offset=$last_index;
		  $count = $results[0]['count'];
		  $migration_id=$results[0]['id'];
	    }

	    //run before statements that affect source table
		if(!empty($befores)){
			foreach($befores as $before){
	             $this->db->query($before);
			}
	    }

         //set limits  
	    $limit=" LIMIT ".$offset.",18446744073709551615";
	    if($destination_table=="patient_visit" || $destination_table=="drug_stock_movement"){
	    	//set limit to 10000 for patient_visit and drug_stock_movement tables
	    	$offset=$count;
	    	$limit=" LIMIT 20000 OFFSET ".$offset;
	    }

		//generate sql and execute
		$sql="INSERT IGNORE INTO ".$destination_table."(".$destination_columns.")";
		$sql.="SELECT ".$source_columns; 
		$sql.=" FROM ".$source_database.".".$source_table;
		$sql.=" ".$conditions.$limit;
        $this->db->query($sql);

        //unset conditions if patient_visit and drug_stock_movement
        if($destination_table=="patient_visit" || $destination_table=="drug_stock_movement"){
            $conditions="";
        }
        
    	//count records in source table
		$sql = "SELECT COUNT(*) as total FROM $source_database.$source_table $conditions";
	    $query = $this -> db -> query($sql);
	    $results = $query -> result_array();
	    if($results){
	    	if($destination_table=="patient_visit" || $destination_table=="drug_stock_movement"){
	    		//get total for large tables
                $total =$results[0]['total'];
                //count rows for large tables
	            $sql="SELECT COUNT(*) as total
	                  FROM $destination_table";

                $query = $this -> db -> query($sql);
			    $results = $query -> result_array();
			    $count=0;
			    $last_index=0;
			    if($results){
			       $count = $results[0]['total'];
			       $last_index = $count;
			    }
			    //compare count and total
			    if($count==$total){
			        //update statement for patient_visit
			        if($destination_table=="patient_visit"){
			        	$sql="UPDATE patient_visit pv
						              LEFT JOIN visit_purpose v ON pv.visit_purpose=v.name AND v.ccc_store_sp=$ccc_pharmacy
						              LEFT JOIN regimen cr ON pv.regimen=cr.regimen_code AND cr.ccc_store_sp=$ccc_pharmacy
						              LEFT JOIN regimen_change_purpose rcp ON pv.regimen_change_reason=rcp.name AND rcp.ccc_store_sp=$ccc_pharmacy
						              LEFT JOIN drugcode dc ON pv.drug_id=dc.drug AND dc.ccc_store_sp=$ccc_pharmacy
						              LEFT JOIN brand b ON pv.brand=b.brand AND b.ccc_store_sp=$ccc_pharmacy
						              LEFT JOIN regimen lr ON pv.last_regimen=lr.regimen_code AND lr.ccc_store_sp=$ccc_pharmacy
						                          SET pv.visit_purpose=v.id,
						                              pv.regimen=cr.id,
						                              pv.regimen_change_reason=rcp.id,
						                              pv.drug_id=dc.id,
						                              pv.brand=b.id,
						                              pv.last_regimen=lr.id";
                        $this->db->query($sql);
			        }
                   $response="Migration[".$table."] Success:Data migrated from source(".$source_table.") to destination(".$destination_table.")!";
			    }else{
			       $response="Migration[".$table."] In progress:Available data is currently being migrated!";
			    }
	    	}else{
		      $total =$results[0]['total'];
		      $last_index =$results[0]['total'];
		      if($last_index==$count){
	              $response="Migration[".$table."] Failed:All data is already migrated!";
		      }else{
		      	  $response="Migration[".$table."] Success:Data migrated from source(".$source_table.") to destination(".$destination_table.")!";
		      }
		      $count =$results[0]['total'];
	        }
	    }else{
	    	//response if failed not data in source table
          $response="Migration[".$table."] Failed:No data is present at source table!";
	    }

		//update migration log
		if($migration_id !=null){
            $migration_log=array(
							'last_index'=>$last_index,
							'count'=>$count);
			$this -> db -> where('id', $migration_id);
		    $this -> db -> update('migration_log', $migration_log);
		}else{
			$migration_log=array(
							'source'=>$destination_table,
							'last_index'=>$last_index,
							'count'=>$count,
							'ccc_store_sp'=>$ccc_pharmacy);
			$this->db->insert('migration_log',$migration_log);
		}

		//run update statements that affect destination table
		if(!empty($updates)){
			foreach($updates as $update){
	             $this->db->query($update);
			}
	    }

		//response
		$response_data['limit']=$limit;
		$response_data['count']=$count;
		$response_data['total']=$total;
		$response_data['message']=$response;
		$response_data['source_table']=$table;
		$response_data['current_table']=$source_table;
		echo json_encode($response_data,JSON_PRETTY_PRINT);
	}
	
	public function checkDB($dbname) {//Check if database selected can be migrated
		$sql = "show tables from $dbname like '%tblarvdrugstockmain%';";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		if ($results) {//If database can be migrated
			$temp = 1;
		} else {
			$temp = 0;
		}
		echo $temp;
	}

	public function base_params($data){
	    $data['hide_side_menu'] = 1;
		$data['title'] = 'webADT | Migration';
		$this -> load -> view('template',$data);
	}

}