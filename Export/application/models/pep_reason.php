<?php
class Pep_Reason extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('name', 'varchar', 150);
		$this -> hasColumn('active', 'int', 11);
	}

	public function setUp() {
		$this -> setTableName('pep_reason');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("pep_reason");
		$reasons = $query -> execute();
		return $reasons;
	}

	public function getActive() {
		$query = Doctrine_Query::create() -> select("*") -> from("pep_reason") -> where("active", "1");
		$reasons = $query -> execute();
		return $reasons;
	}

	public function getItems() {
		$query = Doctrine_Query::create() -> select("id,name as Name") -> from("pep_reason")->where("active='1'")->orderby("name asc");
		$reasons = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $reasons;
	}

}
?>