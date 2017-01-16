<?php
class Generic_Name extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Name', 'varchar', 25);
		$this -> hasColumn('Active', 'varchar', 2);
	}

	public function setUp() {
		$this -> setTableName('generic_name');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("Name") -> from("generic_name");
		$drugcodes = $query -> execute();
		return $drugcodes;
	}

	public function getAllHydrated($access_level="") {
		if($access_level="" || $access_level=="facility_administrator"){
			$query = Doctrine_Query::create() -> select("Name,Active") -> from("generic_name");	
		}
		else{
			$query = Doctrine_Query::create() -> select("Name,Active") -> from("generic_name") -> where("Active='1'");
		}
		$drugcodes = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drugcodes;
	}

	public static function getGeneric($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("generic_name") -> where("id = '$id'");
		$drugcodes = $query -> execute();
		return $drugcodes[0];
	}

	public function getAllActive() {
		$query = Doctrine_Query::create() -> select("Id,Name") -> from("generic_name")->where("Active=1");
		$drugcodes = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drugcodes;
	}

}
