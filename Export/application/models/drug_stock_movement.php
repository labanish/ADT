<?php
class Drug_Stock_Movement extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Machine_Code', 'varchar', 10);
		$this -> hasColumn('Drug', 'varchar', 10);
		$this -> hasColumn('Transaction_Date', 'varchar', 10);
		$this -> hasColumn('Batch_Number', 'varchar', 100);
		$this -> hasColumn('Transaction_Type', 'varchar', 10);
		$this -> hasColumn('Source', 'varchar', 10);
		$this -> hasColumn('Destination', 'varchar', 10);
		$this -> hasColumn('Source_Destination', 'int', 11);
		$this -> hasColumn('Expiry_date', 'varchar', 10);
		$this -> hasColumn('Packs', 'varchar', 10);
		$this -> hasColumn('Quantity', 'int', 15);
		$this -> hasColumn('Quantity_Out', 'int', 15);
		$this -> hasColumn('Balance', 'real', 15);
		$this -> hasColumn('Unit_Cost', 'varchar', 10);
		$this -> hasColumn('Amount', 'varchar', 10);
		$this -> hasColumn('Remarks', 'text');
		$this -> hasColumn('Operator', 'varchar', 10);
		$this -> hasColumn('Order_Number', 'varchar', 10);
		$this -> hasColumn('Facility', 'varchar', 10);
		$this -> hasColumn('Machine_Code', 'varchar', 10);
		$this -> hasColumn('Merged_From', 'varchar', 50);
		$this -> hasColumn('Timestamp', 'varchar', 50);
		$this -> hasColumn('ccc_store_sp', 'int', 11);
	}

	public function setUp() {
		$this -> setTableName('drug_stock_movement');
		$this -> hasOne('drugcode as Drug_Object', array('local' => 'Drug', 'foreign' => 'id'));
		$this -> hasOne('drug_destination as Destination_Object', array('local' => 'Destination', 'foreign' => 'id'));
		$this -> hasOne('drug_destination as Destination_Trans', array('local' => 'source_destination', 'foreign' => 'id'));
		$this -> hasOne('drug_source as Source_Object', array('local' => 'Source', 'foreign' => 'id'));
		$this -> hasOne('drug_source as Source_Trans', array('local' => 'source_destination', 'foreign' => 'id'));
		$this -> hasOne('facilities as Facility_Object', array('local' => 'Facility', 'foreign' => 'facilitycode'));
		$this -> hasOne('facilities as Facility_Sat', array('local' => 'destination', 'foreign' => 'facilitycode'));
		$this -> hasOne('transaction_type as Transaction_Object', array('local' => 'Transaction_Type', 'foreign' => 'id'));
		$this -> hasOne('CCC_store_service_point as store', array('local' => 'ccc_store_sp', 'foreign' => 'id'));
	}

	public function getTotalTransactions($facility) {
		$query = Doctrine_Query::create() -> select("count(*) as Total_Transactions") -> from("Drug_Stock_Movement") -> where("Facility= '$facility'");
		//echo $query->getSQL();
		$total = $query -> execute();
		return $total[0]['Total_Transactions'];
	}

	public function getPagedTransactions($offset, $items, $machine_code, $drug, $facility, $transaction_date, $timestamp) {
		$query = Doctrine_Query::create() -> select("dm2.*") -> from("Drug_Stock_Movement dm2") -> where("dm2.Machine_Code = '$machine_code' and dm2.Facility='$facility' and dm2.Timestamp>$timestamp");
		//echo $query->getSQL();
		$drug_transactions = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drug_transactions;
	}

	public function getPagedFacilityTransactions($offset, $items, $facility) {
		$query = Doctrine_Query::create() -> select("*") -> from("Drug_Stock_Movement") -> where("Facility='$facility'") -> offset($offset) -> limit($items);
		$drug_transactions = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drug_transactions;
	}

	public function getDrugTransactions($drug_id, $facility, $stock_type = 1) {
		$where = "";
		$today = date('Y-m-d');
		//Stock transaction
		if ($stock_type == 1) {
			$where = "and (ds.source='$facility'  or ds.destination='$facility') and ds.source!=ds.destination";
		}
		//Pharmacy transaction
		else if ($stock_type == 2) {
			$where = "and ds.source='$facility'  and ds.source=ds.destination";
		}

		$query = Doctrine_Query::create() -> select("*") -> from("Drug_Stock_Movement ds") -> where("ds.Facility='$facility' and ds.drug='$drug_id' $where") -> orderBy("ds.id desc");
		$drug_transactions = $query -> execute();
		return $drug_transactions;
	}

	public function getDrugMonthlyConsumption($drug_id, $facility, $stock_type = 1) {
		$where = "";
		$today = date('Y-m-d');
		//Store transaction
		if ($stock_type == 1) {
			$where = "and (dsm.source='$facility'  or dsm.destination='$facility') and dsm.source!=dsm.destination";
		}
		//Pharmacy transaction
		else if ($stock_type == 2) {
			$where = "and dsm.source='$facility'  and dsm.source=dsm.destination";
		}

		$query = Doctrine_Query::create() -> select("dsm.quantity_out as total_out") -> from("drug_stock_movement dsm") -> where("dsm.drug='$drug_id' AND facility ='$facility' AND DATEDIFF(CURDATE(),dsm.transaction_date) <= 90 $where");
		$drug_monthly_consumption = $query -> execute();
		return $drug_monthly_consumption;
	}
	public function getDrugConsumption($drug_id, $facility,$ccc_id = 1,$transaction_type='') {
		$query = Doctrine_Query::create() -> select("dsm.quantity_out as total_out") -> from("drug_stock_movement dsm") -> where("dsm.drug='$drug_id' AND facility ='$facility' AND DATEDIFF(CURDATE(),dsm.transaction_date) <= 90 and transaction_type ='$transaction_type' and ccc_store_sp='$ccc_id'");
		$drug_monthly_consumption = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drug_monthly_consumption;
	}

	public function getTransactionItems($stores,$period_begin,$period_end,$drug_id,$transaction){
        $query = Doctrine_Query::create() -> select("IF(dsm.Transaction_Object.Effect=1,SUM(dsm.quantity),SUM(dsm.quantity_out)) AS total") -> from("drug_stock_movement dsm") -> where("dsm.transaction_date BETWEEN '$period_begin' AND  '$period_end' AND dsm.Drug_Object.map='$drug_id' AND dsm.Transaction_Object.Name LIKE '%$transaction%' AND d.ccc_store_sp IN($stores)");
		$total = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return @$total[0]['total'];
	}

}
