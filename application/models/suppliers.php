<?php
class Suppliers extends Doctrine_Record {
	public function setTableDefinition() {
		$this -> hasColumn('name', 'varchar', 20);		
	}

	public function setUp() {
		$this -> setTableName('suppliers');
	}
	
	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("suppliers");
		$gender = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $gender;
	}

	
}
