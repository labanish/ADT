<?php
class Drug_Stock_Balance extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('drug_id', 'int', 11);
		$this -> hasColumn('batch_number', 'varchar', 50);
		$this -> hasColumn('expiry_date', 'varchar', 100);
		$this -> hasColumn('stock_type', 'int', 4);
		$this -> hasColumn('facility_code', 'varchar', 50);
		$this -> hasColumn('balance', 'int', 11);
		$this -> hasColumn('last_update', 'timestamp');
	}

	public function setUp() {
		$this -> setTableName('drug_stock_balance');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("drug_stock_balance");
		$balance = $query -> execute();
		return $balance;
	}

}
?>