<?php
class Drug_Cons_Balance extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('drug_id', 'int', 11);
		$this -> hasColumn('stock_type', 'int', 11);
		$this -> hasColumn('period', 'varchar', 15);
		$this -> hasColumn('amount', 'int', 11);
		$this -> hasColumn('facility', 'varchar', 30);
		$this -> hasColumn('last_update', 'timestamp');
	}

	public function setUp() {
		$this -> setTableName('drug_cons_balance');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("drug_cons_balance");
		$balance = $query -> execute();
		return $balance;
	}

}
?>