<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Drug_stock_balance_sync extends MY_Controller {
	function __construct() {
		parent::__construct();
		$this->load->database();
		ini_set("max_execution_time", "1000000");
	}
	public function index() {
		//$this->synch_balance();
		$data['banner_text']="Syncronization";
		$data['content_view'] = "sync_drug_balance_v";	
		$data['title'] = "Web ADT";	
		$this->load->view("template",$data);
	}
	
	public function getDrugs(){
		if($this->input->post("check_if_malicious_posted")){
			$getTot_Drugs=$this->db->query("select d.id,drug from drugcode d where d.Enabled=1");
			$drugs=$getTot_Drugs->result_array();
			$data['drugs']=$drugs;
			$data['count']=count($drugs);
			echo json_encode($data);
		}
		
	}
	
	public function synch_balance($stock_type="2"){
		$stock_type=$this->input->post("stock_type");
		//$drug_id=$this->input->post("drug_id");
		$drug_id=102;
		$not_saved=0;
		$facility_code = $this -> session -> userdata('facility');
		$stock_param="";
		
		//CCC Store Name
		$ccc = CCC_store_service_point::getCCC($stock_type);
		$ccc_name = $ccc['Name'];
		
		//Store
		if(stripos($ccc_name,'store')){
			$stock_param=" AND (source='".$facility_code."' OR destination='".$facility_code."') AND source!=destination ";
		}
		//Pharmacy
		else if(stripos($ccc_name,'pharmacy')){
			$stock_param=" AND (source=destination) AND(source='".$facility_code."') ";
		}
		
			$count_it=0;
			$stock_status=0;
			//Get all the batches
			$get_batches_sql="SELECT d.batch_number AS batch,expiry_date FROM drug_stock_movement d WHERE d.drug =  '" .$drug_id. "' AND facility='" .$facility_code. "' ".$stock_param." AND batch_number!='' GROUP BY d.batch_number";
			
			
			$bacthes=$this -> db -> query($get_batches_sql);
			$batch_results=$bacthes -> result_array();
			foreach ($batch_results as $key => $batch_row) {
				
				//echo $count_it."<br>";
				//Query to check if batch has had a physical count
				$batch_no = $batch_row['batch'];
				$expiry_date=$batch_row['expiry_date'];
				if(trim($batch_no)==''){
					continue;
				}

				//Get the latest physical count
				$initial_stock_sql = "SELECT d.quantity AS Initial_stock, d.transaction_date AS transaction_date, '" .$batch_no. "' AS batch,t.name as trans_name 
									FROM drug_stock_movement d LEFT JOIN transaction_type t ON t.id=d.transaction_type 
									WHERE d.drug =  '" .$drug_id. "' AND (t.name LIKE '%physical%' OR t.name LIKE '%stock count%') 
									AND facility='" .$facility_code. "' ".$stock_param." AND d.batch_number =  '" .$batch_no. "' ORDER BY d.id DESC LIMIT 1";
				
			
				
				
				//$initial_stock_sql = "SELECT SUM( d.quantity ) AS Initial_stock, d.transaction_date AS transaction_date, '" .$batch_no. "' AS batch,t.name as trans_name FROM drug_stock_movement d LEFT JOIN transaction_type t ON t.id=d.transaction_type WHERE d.drug =  '" .$drug_id. "' AND (t.name LIKE '%physical count%' OR t.name LIKE '%stock count%') AND facility='" .$facility_code. "' ".$stock_param." AND d.batch_number =  '" .$batch_no. "'";
				$bacthes_initial_stock=$this -> db -> query($initial_stock_sql);
				$batch_initial_stock=$bacthes_initial_stock -> result_array();
				$x=count($batch_initial_stock);
				//echo $x.'<br>';
				foreach ($batch_initial_stock as $key => $value2) {
					//If initial stock is not null
					if($value2['Initial_stock']!=null){
						
						//Get the balance for that batch
						//$batch_stock_balance_sql = "SELECT (SUM( ds.quantity ) - SUM( ds.quantity_out )) AS stock_levels, ds.batch_number FROM drug_stock_movement ds WHERE ds.transaction_date BETWEEN  '" .$value2['transaction_date']. "' AND curdate() AND facility='" .$facility_code. "' ".$stock_param." AND ds.drug ='" .$drug_id. "'  AND ds.batch_number ='" .$value2['batch']. "'";
						$batch_stock_balance_sql = "SELECT ds.quantity AS stock_levels, ds.batch_number FROM drug_stock_movement ds WHERE ds.transaction_date BETWEEN  '" .$value2['transaction_date']. "' AND curdate() AND facility='" .$facility_code. "' ".$stock_param." AND ds.drug ='" .$drug_id. "'  AND ds.batch_number ='" .$value2['batch']. "' ORDER BY ds.id DESC LIMIT 1";
						
						$bacthes_balance=$this -> db -> query($batch_stock_balance_sql);
						$batch_balance_array=$bacthes_balance -> result_array();
						foreach ($batch_balance_array as $key => $value3) {
							//Save balance in drug_stock_balance table
							if($value3['stock_levels']>0){
								$batch_balance_save=$value3['stock_levels'];
							}
							else{
								$batch_balance_save=0;
							}
							$batch_number_save=$batch_no;
							$drug_id_save=$drug_id;
							$expiry_date_save=$expiry_date;
							$insert_balance_sql="INSERT INTO drug_stock_balance(drug_id,batch_number,stock_type,expiry_date,facility_code,balance) VALUES('".$drug_id_save."','".$batch_number_save."','".$stock_type."','".$expiry_date_save."','".$facility_code."','".$batch_balance_save."') ON DUPLICATE KEY UPDATE balance='".$batch_balance_save."'";
							$q=$this -> db -> query($insert_balance_sql);
							if(!$q){
								$not_saved++;
							}
						}
					}
					else{
						//Get the balance for that batch
						$batch_stock_balance_sql = "SELECT (SUM( ds.quantity ) - SUM( ds.quantity_out ) ) AS stock_levels, ds.batch_number FROM drug_stock_movement ds WHERE ds.drug =  '" .$drug_id. "' AND ds.expiry_date > curdate() AND facility='" .$facility_code. "' ".$stock_param." AND ds.batch_number='" .$value2['batch']. "'";
						
						//echo $batch_stock_balance_sql;die();
						$bacthes_balance=$this -> db -> query($batch_stock_balance_sql);
						$batch_balance_array=$bacthes_balance -> result_array();
						foreach ($batch_balance_array as $key => $value3) {
							//Store balance in drug_stock_balance table
							$batch_balance_save=$value3['stock_levels'];
							
							
							if($value3['stock_levels']>0){
								$batch_balance_save=$value3['stock_levels'];
							}
							else{
								$batch_balance_save=0;
							}
							$batch_number_save=$batch_no;
							$drug_id_save=$drug_id;
							$expiry_date_save=$expiry_date;
							$insert_balance_sql="INSERT INTO drug_stock_balance(drug_id,batch_number,stock_type,expiry_date,facility_code,balance) VALUES('".$drug_id_save."','".$batch_number_save."','".$stock_type."','".$expiry_date_save."','".$facility_code."','".$batch_balance_save."') ON DUPLICATE KEY UPDATE balance='".$batch_balance_save."'";
							$q=$this -> db -> query($insert_balance_sql);
							if(!$q){
								$not_saved++;
							}
						}
					}
				}
			}

	}	

	//Synchronizes drug stock moment balance
	public function drug_stock_movement_balance(){
		$stock_type=$this->input->post("stock_type");
		$drug_id=$this->input->post("drug_id");
		$facility_code = $this -> session -> userdata('facility');
		$stock_param="";
		//Store
		if($stock_type=='1'){
			$stock_param=" AND (source='".$facility_code."' OR destination='".$facility_code."') AND source!=destination ";
		}
		//Pharmacy
		else if($stock_type=='2'){
			$stock_param=" AND (source=destination) AND(source='".$facility_code."') ";
		}
		
		$stock_status=0;
		//Get all the batches
		$get_batches_sql="SELECT DISTINCT d.batch_number AS batch,expiry_date FROM drug_stock_movement d WHERE d.drug =  '" .$drug_id. "' AND facility='" .$facility_code. "' ".$stock_param." GROUP BY d.batch_number";
		$batches=$this -> db -> query($get_batches_sql);
		$batch_results=$batches -> result_array();
		foreach ($batch_results as $key => $batch_row) {
			$batch_number=$batch_row['batch'];
			//get drug balances
			$get_balances_sql="SELECT dsm.ID,dsm.TRANSACTION_DATE,dsm.TRANSACTION_TYPE,dsm.QUANTITY,dsm.QUANTITY_OUT,t.name as transaction_name,IF((t.name LIKE '%physical count%' OR t.name LIKE '%starting stock%') ,@BALANCE:=@BALANCE-@BALANCE+QUANTITY,@BALANCE:=@BALANCE+QUANTITY-QUANTITY_OUT) as balance FROM drug_stock_movement dsm LEFT JOIN transaction_type t ON t.id=dsm.TRANSACTION_TYPE ,(SELECT @BALANCE:=0) as DUMMY WHERE drug='" .$drug_id. "' AND batch_number='".$batch_number."' AND facility='" .$facility_code. "' ".$stock_param." ORDER BY ID ASC";
			$balances=$this -> db -> query($get_balances_sql);
			$balance_results=$balances -> result_array();
			//Loop through the array to get the actual values
			foreach ($balance_results as $key => $balance) {
				$bal=$balance['balance'];
				if($bal<0){
					$bal=0;
				}
				//Update the balance column 
				$update_balance_sql="UPDATE drug_stock_movement SET balance='".$bal."' WHERE id=".$balance['ID'];
				$balances=$this -> db -> query($update_balance_sql);
				//$balance_results=$balances -> result_array();
				
			}
		}
		
	}

	public function setConsumption(){
		$facility_code=$this->session->userdata("facility");
		//truncate drug_consumption_balance
		$sql="TRUNCATE drug_cons_balance";
		$this->db->query($sql);
		//old using patient visit
		//$sql="INSERT INTO drug_cons_balance(drug_id,stock_type,period,facility,amount)SELECT drug_id,ccc_store_sp,DATE_FORMAT(dispensing_date,'%Y-%m-01') as period,'$facility_code', SUM( quantity ) AS total FROM  patient_visit WHERE drug_id > 0 GROUP BY drug_id,period ORDER BY  patient_visit.drug_id";
	    //new using drug_stock_movement
	    $sql="INSERT INTO drug_cons_balance(drug_id,stock_type,period,facility,amount)SELECT dsm.drug,dsm.ccc_store_sp,DATE_FORMAT(dsm.transaction_date,'%Y-%m-01') as period,'$facility_code',SUM(dsm.quantity_out) AS total FROM  drug_stock_movement dsm LEFT JOIN transaction_type t ON t.id=dsm.transaction_type WHERE dsm.drug > 0 AND t.name LIKE '%dispense%' GROUP BY dsm.drug,dsm.ccc_store_sp,period ORDER BY  dsm.drug";
	    $this->db->query($sql);
	    echo "<div class='alert alert-info'><button type='button' class='close' data-dismiss='alert'>&times;</button><strong>(".$this->db->affected_rows().")</strong> rows updated for drug consumption</div>";
	}

	public function view_balance(){
        $data['drugs']=Drugcode::getEnabledDrugs();
    	$data['quick_link'] = "balance";
		$data['title'] = "webADT | Running Balance";
		$data['banner_text'] = "Running Balance Management";
		$data['link'] = "auto_management";
		$this -> load -> view('running_balance_v', $data);
    }

    public function getRunningBalance()
    {
      	ini_set("max_execution_time", "100000");
		ini_set("memory_limit", '2048M');

	    $drug_id=$this->input->post("drug_id");
		$stores=CCC_store_service_point::getActive();
		$facility_code=$this->session->userdata("facility");

        foreach($stores as $store){
            $store_id=$store['id'];
	        $sql="SELECT d.id AS trans_id, 
                        c.name AS trans_store, 
                        t.name AS trans_type, 
                        d.quantity AS trans_qty, 
                        d.quantity_out AS trans_qty_out,
                        IF(t.name LIKE '%physical%','1','0')as trans_desc
					FROM drug_stock_movement d
					LEFT JOIN ccc_store_service_point c ON d.ccc_store_sp = c.id
					LEFT JOIN transaction_type t ON d.transaction_type = t.id
					WHERE d.drug ='$drug_id'
					AND d.ccc_store_sp ='$store_id'
					ORDER BY d.id ASC";
			$query=$this->db->query($sql);
			$transactions=$query->result_array();
			$balance_before=0;
			$balance_after=0;
			$total=0;
			$prev_count=0;
			$balance=array();
            
            /*
		    $table="<table border='1'>";
            $table.="<thead><tr><th>Beginning Balance</th><th>Type of Transaction</th><th>QTY Transacted</th><th>End Balance</th><th>Cumulative Balance</th></tr></thead><tbody>";
  	        $table.="<tr><td>".$balance_before."</td><td>". $transaction['trans_type']."</td><td>".$total."</td><td>".$balance[$count]."</td><td>".$balance_after."</td></tr>";
            $table.="</tbody></table>";
            echo "<br/>Drug Balance:".$drug_balance."<br/>Difference:".$difference;
            */


            foreach($transactions as $count=>$transaction){
            	/*
            	1.Beginning Balance
            	2.Type of Transaction 
            	3.Quantity Transacted (quantity-quantity_out)
            	4.End Balance (Beginning_Balance+Quantity transacted)
            	5.Cumulative Balance (+=End Balance)
            	*/
            
          	 	$prev_count=$count-1;
                $total=$transaction['trans_qty']-$transaction['trans_qty_out'];
                if($prev_count>=0){
                   $balance_before=$balance[$prev_count];
                }
              	$balance[$count]=$balance_before+$total;

              	$balance_after=$balance[$count];
              	if($balance_after<0){
                   $balance[$count]=0;
                   $balance_after=0;
              	}

              	if($prev_count>=0){
              		$balance_after=$balance[$prev_count]+$total;
	              	if($balance_after<0){
	                   $balance[$count]=0;
	                   $balance_after=0;
	              	}
              	}
                //transaction is a starting stock
              	if($transaction['trans_desc']==1){
                   $balance[$count]=$total;
	               $balance_after=$total;
              	}

              	//update running_balance in machine code column
              	$record_id=$transaction['trans_id'];
              	$sql="UPDATE drug_stock_movement SET machine_code='$balance_after' WHERE id='$record_id'";
              	$this->db->query($sql);
            } 
            
               
            //get drug balance in drug stock balance
            $sql="SELECT SUM(dsb.balance) as total,dsb.batch_number as batch_no,dsb.expiry_date
				  FROM drug_stock_balance dsb
				  WHERE dsb.drug_id ='$drug_id'
				  AND dsb.stock_type ='$store_id'
				  AND facility_code='$facility_code'
				  AND dsb.expiry_date > CURDATE()
				  AND dsb.balance >0";
			$query=$this->db->query($sql);
			$balances=$query->result_array();
			$drug_balance=0;
			if($balances){
               $drug_balance=$balances[0]['total'];
               $batch_no=$balances[0]['batch_no'];
               $expiry_date=$balances[0]['expiry_date'];
			}

			//compare last balance after with balance in drug stock balance
		    $difference=$drug_balance-$balance_after;

			if($difference !=0){
				if($difference>0){
	               //positive adjustment
	               $column = "quantity";
	               $sql="SELECT id FROM transaction_type WHERE name LIKE '%adjustment (+)%'";
				}else{
				   //negative adjustment
				   $column = "quantity_out";
				   $sql="SELECT id FROM transaction_type WHERE name LIKE '%adjustment (-)%'";
				}

				//get transaction_type_id
				$query=$this->db->query($sql);
				$results=$query->result_array();
				$transaction_type_id=$results[0]['id'];

				//make difference absolute(positive)
				$difference = abs($difference);

                
                //source/destination (pharmacy)
                $source=$facility_code;
                $destination=$facility_code;
                
                //if store_id is 1 then source is (store)
                if($store_id==1){
                   $source=$store_id;
                }

				//insert record into drug_stock_movement
			    $insert_data = array(
			    	            'drug'=> $drug_id,
			    	            'transaction_date'=> date("Y-m-d"),
			    	            'batch_number' => $batch_no,
			    	            'expiry_date' => $expiry_date,
			    	            'transaction_type' =>$transaction_type_id,
			    	            'source' =>$source,
			    	            'destination' =>$destination,
			    	             $column => $difference,
			    	            'facility' => $facility_code,
			    	            'ccc_store_sp' => $store_id
			    	            );

			    $this->db->insert('drug_stock_movement', $insert_data); 
			    $last_insert_id=$this -> db -> insert_id();

			    //update running_balance in machine code column
              	$sql="UPDATE drug_stock_movement SET machine_code='$drug_balance' WHERE id='$last_insert_id'";
              	$this->db->query($sql);
		    }
        }

        return true;
    }
    
}
?>