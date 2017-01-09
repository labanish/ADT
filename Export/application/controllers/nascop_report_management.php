<?php
class nascop_report_management extends MY_Controller{
	var $nascop_url = "";
	function __construct() {
		parent::__construct();
		$this -> load -> database();
        parent::__construct();

        ini_set("max_execution_time", "100000");
        ini_set("memory_limit", '2048M');
        ini_set("allow_url_fopen", '1');

        $dir = realpath($_SERVER['DOCUMENT_ROOT']);
        $link = $dir . "\\ADT\\assets\\nascop.txt";
        $this -> nascop_url = file_get_contents($link);
        
	}

	public function index(){}

  public function adherence(){
    $data=array();
    $data['adhere']=Patient::adherence_reports();
 
    echo '<pre>';
    echo json_encode($data,JSON_PRETTY_PRINT);
   echo '</pre>';
    

  }
  
public function nascop_reports()
{
    $temp = array();
    //Early Warning Indicators Report
    $percentage_on_firstline=0;
    
    $current_total = Patient::start_on_ART();
    $current_firstline = Patient::start_on_firstline();
    $patients_still_in_firstline=Patient::still_in_firstline();
    $lost_to_followup=Patient::get_lost_to_followup();
    
    
    foreach ($current_total as $period => $values) 
    { 
      foreach($values as $value)
      {
          $value_total = $value['art_patients'];
          $value_firstline = $current_firstline[$period][0]['firstline_patients'];
          $value_percent_firstline=doubleval(number_format(($value_firstline/$value_total)*100,2));
          $lost_to_follow=$lost_to_followup[$period][0]['lost_to_followup'];
          $Started_on_ART=$current_total[$period][0]['art_patients'];
          $percent_lost_to_follow=doubleval(number_format(($lost_to_follow/$Started_on_ART)*100,2));
          $percent_still_in_firstline=doubleval(number_format(($value_firstline/$value_total)*100,2));
          
          $temp[$period]['current_total'] = $value_total;
          $temp[$period]['current_firstline'] = $value_firstline;
          $temp[$period]['percentage_on_firstline'] = $value_percent_firstline;
          $temp[$period]['percentage_on_other_regimens']=100-$value_percent_firstline;
          $temp[$period]['patients_still_in_firstline']=$patients_still_in_firstline[$period][0]['Still_in_Firstline'];
          $temp[$period]['patients_starting_in_12months']=$value_total;
          $temp[$period]['percentage_still_in_firstline']=$percent_still_in_firstline;
          $temp[$period]['patients_lost_followup']=$lost_to_follow;
          $temp[$period]['patients_started_on_ART']=$Started_on_ART;
          $temp[$period]['percentage_lost_to_followup']=$percent_lost_to_follow;
      }     
    }

    echo '<pre>';
    echo json_encode($temp,JSON_PRETTY_PRINT);
    echo '</pre>';
    

   // $data['facility_code']=$facility_code;
   // $json_data = json_encode($data,JSON_PRETTY_PRINT);
    

}
public function send_nascop_reports(){
  $data=array();
  $data['early_warning_reports']=$this->nascop_reports();
  $data['adherence_reports']=$this->adhere();

  $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('Nascop_reports' => $data));
        $json_data = curl_exec($ch);
        if (empty($json_data)) {
            $message = "cURL Error: " . curl_error($ch);
        }else{
           $messages = json_decode($json_data, TRUE);
            $message = $messages[0]; 
        }
         curl_close($ch);
        return $message; 
}

public function test(){

  $temper=array();
  $current_total = Patient::start_on_ART();
  foreach ($current_total as $key => $value) {
    foreach ($value as $keys => $values) {
      foreach ($values as $keyes => $valuees) {
        # code...
        print_r($keyes);
      }
      
    }
    
  }



 /* $products = array('paper'=>array('cre'=>"cre_paper",'eng'=>"English_paper"),'book'=>array('cpa'=>"cpa_book",'program'=>"programming"));
  
foreach ($products as $key => $value) {
  foreach ($value as $keys=> $values) {
    # code...
   print_r($products['paper'][0]);
    //print_r($keys);
  }
}*/
  die();
}
}