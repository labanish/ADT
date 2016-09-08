<?php
class Transaction_Type extends Doctrine_Record {
	
	public function setTableDefinition() {
		$this -> hasColumn('Name', 'varchar', 100);
		$this -> hasColumn('Desc', 'varchar', 200);
		$this -> hasColumn('Effect', 'varchar', 2);
		$this -> hasColumn('active', 'int', 5);
	}	
	public function setUp() {
		$this -> setTableName('transaction_type');
	}
	
	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("transaction_type")->where("active='1'");
		$transaction_types = $query -> execute();
		return $transaction_types;
	}
	public function getAllNonAdjustments() {
		$query = Doctrine_Query::create() -> select("*") -> from("transaction_type")->where("`Desc` NOT LIKE '%adjust%' and active='1'");
		$transaction_types = $query -> execute();
		return $transaction_types;
	}
	
	public function getTransactionType($filter,$effect){
		$query = Doctrine_Query::create() -> select("*") -> from("transaction_type")->where("Name LIKE '%$filter%' AND effect='$effect' ");
		$transaction_type = $query -> execute();
		return $transaction_type[0];
	}
	
	public function getAllTypes(){
		$query = Doctrine_Query::create() -> select("id,Name,Effect") -> from("transaction_type")->where("Name LIKE '%received%' OR Name LIKE '%adjustment%' OR Name LIKE '%return%' OR Name LIKE '%dispense%' OR Name LIKE '%issue%' OR Name LIKE '%loss%' OR Name LIKE '%ajustment%' OR Name LIKE '%physical%count%' OR Name LIKE '%starting%stock%'");
		$transaction_types = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $transaction_types;
	}
	public function getEffect($id){
		$query = Doctrine_Query::create() -> select("Effect") -> from("transaction_type")->where("id='$id'");
		$transaction_types = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $transaction_types[0];
	}
}
?>
	