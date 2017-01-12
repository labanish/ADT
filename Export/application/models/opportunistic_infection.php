<?php
class Opportunistic_Infection extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Name', 'varchar', 100);
		$this -> hasColumn('Indication', 'varchar',50);
		$this -> hasColumn('Active', 'varchar', 2);
	}

	public function setUp() {
		$this -> setTableName('opportunistic_infection');
	}
    
	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("Opportunistic_Infection") -> where("Active", "1");
		$infections = $query -> execute();
		return $infections;
	}
	
	public function getAllHydrated() {
		$query = Doctrine_Query::create() -> select("*") -> from("Opportunistic_Infection") -> where("Active", "1");
		$infections = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $infections;
	}

	public function getTotalNumber() {
		$query = Doctrine_Query::create() -> select("count(*) as Total_OIs") -> from("Opportunistic_Infection");
		$total = $query -> execute();
		return $total[0]['Total_OIs'];
	}

	public function getPagedOIs($offset, $items) {
		$query = Doctrine_Query::create() -> select("Name") -> from("Opportunistic_Infection") -> offset($offset) -> limit($items);
		$ois = $query -> execute();
		return $ois;
	}
	public static function getIndication($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("opportunistic_infection") -> where("id = '$id'");
		$ois = $query -> execute();
		return $ois[0];
	}
	public function getThemAll($access_level="") {
		if($access_level="" || $access_level=="facility_administrator"){
			$query = Doctrine_Query::create() -> select("*") -> from("Opportunistic_Infection");
		}
		else{
			$query = Doctrine_Query::create() -> select("*") -> from("Opportunistic_Infection") -> where("Active='1'");
		}
		
		$infections = $query -> execute();
		return $infections;
	}

}
?>