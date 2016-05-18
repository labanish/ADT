<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class new_patients extends MY_Controller {
	public function __construct() {
		parent::__construct();
	}
	public function index()
	{
		$this->display_new_patients();
	}
	
	public function display_new_patients()
	{
		$this->data['title'] = "New Patients";
		$this->data['banner_text'] = "New Patients From IQCare";
		$this->data['content_view'] = 'new_patient_sync';
		$this->data['new_patients']=$this->get_new_patients();
		$this->load->view('template',$this->data);
	}
	
	public function get_new_patients()
	{
		$get="select * from mirth_sync where inserted=0";
	$user_get=$this->db->query($get);
	return $user_get->result_array();
   // echo json_encode($user_get->result_array(),JSON_PRETTY_PRINT);
	}
	//inserts checked values form new_patients_sync into db
	//http://roywebdesign.net/insert-multiple-rows-with-codeigniter/
	public function insert_into_db()
	{
		if($_POST)
		{	
			$approve=$this->input->post('select');
			$patient_number_ccc=$this->input->post('patient_number_ccc');
			$first_name=$this->input->post('first_name');
			$last_name=$this->input->post('last_name');
			$other_name=$this->input->post('other_name');
			$gender=$this->input->post('gender');
			$phone=$this->input->post('phone');
			$date_enrolled=$this->input->post('date_enrolled');
			

			$new_patient = array();
			foreach($approve as $key=>$value){
				$new_patient[$key]['patient_number_ccc'] = $patient_number_ccc[$key];
		}
		$success=TRUE;
			
			if($success==TRUE){
				foreach($patient_number_ccc as $key=>$value){
				$update=$new_patient[$key]['patient_number_ccc'];
                
			$sql="select * from mirth_sync where patient_number_ccc='$update'";
	$query=$this->db->query($sql);



	if ($query->num_rows() > 0)
{
   $row = $query->row(); 

	$id= $row->id;
$patient_number_ccc=$row->patient_number_ccc;
  $fname=$row->first_name;
  $lame= $row->last_name;
  $oname= $row->other_name;
  $gender=$row->gender;
  $dob=$row->dob;
  $pob=$row->pob;
  $phone= $row->phone;
  $date_enrolled= $row->date_enrolled;
  $patient_source_id= $row->patient_source_id;
  $start_regimen_date= $row->start_regimen_date;
  $supporting_organization_id= $row->supporting_organization_id;
  $start_regimen_id= $row->start_regimen_id;
  $drug_allergies= $row->drug_allergies;
  $from_facility_id= $row->from_facility_id;
  $patient_status_id= $row->patient_status_id;
  $physical_address= $row->physical_address;
  $weight= $row->weight;
  $height= $row->height;
  $pregnant= $row->pregnant;
  $who_stage= $row->who_stage;
  $other_drugs= $row->other_drugs;
  $facility_code= $row->facility_code;
  $service_type= $row->service_type;
  $timestamp= $row->timestamp;


$sql1 = "insert INTO 
patients_approved(patient_number_ccc,
first_name,
last_name,
other_name,
gender,
dob,
pob,
phone,
date_enrolled,
start_regimen_date,
supporting_organization_id,
start_regimen_id,
drug_allergies,
from_facility_id,
patient_status_id,
physical_address,
weight,
height,
pregnant,
who_stage,
other_drugs,
facility_code,
service_type,
timestamp)
VALUES(
'$patient_number_ccc',
'$fname',
'$lame',
'$oname',
'$gender',
'$dob',
'$pob',
'$phone',
'$date_enrolled',
'$start_regimen_date',
'$supporting_organization_id',
'$start_regimen_id',
'$drug_allergies',
'$from_facility_id',
'$patient_status_id',
'$physical_address',
'$weight',
'$height',
'$pregnant',
'$who_stage',
'$other_drugs',
'$facility_code',
'$service_type',
'$timestamp')";
            
           if($this -> db -> query($sql1)){
           
$sql="UPDATE mirth_sync set inserted=1 where patient_number_ccc=$patient_number_ccc";
			$query=mysql_query($sql);	
           }
           else{
           	

           }
           

}
}

				}
				redirect('new_patients');
			}

		}



}




	
