<?php
class Drug_classification extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Name', 'varchar', 25);
		$this -> hasColumn('Active', 'varchar', 2);
	}

	public function setUp() {
		$this -> setTableName('drug_classification');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("Name") -> from("drug_classification");
		$drugcodes = $query -> execute();
		return $drugcodes;
	}

	public function getAllHydrated($access_level="",$get_active="1") {
		if(($access_level="" || $access_level=="facility_administrator") && $get_active=="1"){
			$query = Doctrine_Query::create() -> select("Name,Active") -> from("drug_classification");	
		}
		else{
			$query = Doctrine_Query::create() -> select("Name,Active") -> from("drug_classification") -> where("Active='1'");
		}
		$drugcodes = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drugcodes;
	}
	

	public static function getClassification($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("drug_classification") -> where("id = '$id'");
		$drugcodes = $query -> execute();
		return $drugcodes[0];
	}

	public function getAllActive() {
		$query = Doctrine_Query::create() -> select("Id,Name") -> from("drug_classification")->where("Active=1");
		$drugcodes = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drugcodes;
	}

}
