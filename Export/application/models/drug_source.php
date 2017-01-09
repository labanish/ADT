<?php
class Drug_Source extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Name', 'varchar', 100);
		$this -> hasColumn('Active', 'varchar', 2);
	}

	public function setUp() {
		$this -> setTableName('drug_source');
	}
    
	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("Drug_Source") -> where("Active", "1");
		$infections = $query -> execute();
		return $infections;
	}
	public function getAllHydrated() {
		$query = Doctrine_Query::create() -> select("*") -> from("Drug_Source") -> where("Active=1") ->orderBy("id ASC");
		//return $query->getSqlQuery();
		$destinations = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $destinations;
	}

	public function getAllHydrate() {
		$query = Doctrine_Query::create() -> select("*") -> from("Drug_Source") -> where("Active=1") ->orderBy("id ASC");
		//return $query->getSqlQuery();
		$destinations = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $destinations;
	}

	public function getTotalNumber() {
		$query = Doctrine_Query::create() -> select("count(*) as Total_Sources") -> from("Drug_Source")->where("Active='1'") ;
		$total = $query -> execute();
		return $total[0]['Total_Sources'];
	}

	public function getPagedSources($offset, $items) {
		$query = Doctrine_Query::create() -> select("Name,Active") -> from("Drug_Source")->where("Active='1'") -> offset($offset) -> limit($items);
		$ois = $query -> execute();
		return $ois;
	}
	public static function getSource($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("Drug_Source") -> where("id = '$id'");
		$ois = $query -> execute();
		return $ois[0];
	}
	
	
	
	public function getThemAll($access_level="") {
		if($access_level="" || $access_level=="facility_administrator"){
			$query = Doctrine_Query::create() -> select("*") -> from("Drug_Source");
		}
		else{
			$query = Doctrine_Query::create() -> select("*") -> from("Drug_Source") -> where("Active='1'");
		}
		$infections = $query -> execute();
		return $infections;
	}

}
?>