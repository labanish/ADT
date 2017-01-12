<?php

class Facility_Types extends Doctrine_Record {
	public function setTableDefinition() {
		$this -> hasColumn('Name', 'varchar', 20);
	}

	public function setUp() {
		$this -> setTableName('facility_types'); 
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("id,Name") -> from("Facility_Types");
		$types = $query -> execute();
		return $types;
	}

	public function getTypeID($name) {
		$query = Doctrine_Query::create() -> select("id") -> from("Facility_Types")->where("Name LIKE '%$name%'");
		$types = $query -> execute();
		$type_id=0;
		if($types){
			$type_id=$types[0]['id'];
		}
		return $type_id;
	}

}
