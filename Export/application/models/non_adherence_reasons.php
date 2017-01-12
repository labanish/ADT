<?php
class Non_Adherence_Reasons extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Name', 'varchar', 150);
		$this -> hasColumn('Active', 'varchar', 10);
	}

	public function setUp() {
		$this -> setTableName('non_adherence_reasons');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("Non_Adherence_Reasons") -> where("Active", "1");
		$purposes = $query -> execute();
		return $purposes;
	}
	
	public function getAllHydrated() {
		$query = Doctrine_Query::create() -> select("*") -> from("Non_Adherence_Reasons") -> where("Active", "1");
		$purposes = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $purposes;
	}
	
	public function getThemAll($access_level="") {
		if($access_level="" || $access_level=="facility_administrator"){
			$query = Doctrine_Query::create() -> select("*") -> from("Non_Adherence_Reasons");
		}
		else{
			$query = Doctrine_Query::create() -> select("*") -> from("Non_Adherence_Reasons") -> where("Active", "1");
		}
		$purposes = $query -> execute();
		return $purposes;
	}
	
	public static function getSource($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("Non_Adherence_Reasons") -> where("id = '$id'");
		$ois = $query -> execute();
		return $ois[0];
	}

	public function getTotalNumber() {
		$query = Doctrine_Query::create() -> select("count(*) as Total_Purposes") -> from("Non_Adherence_Reasons");
		$total = $query -> execute();
		return $total[0]['Total_Purposes'];
	}

	public function getPagedPurposes($offset, $items) {
		$query = Doctrine_Query::create() -> select("Name") -> from("Non_Adherence_Reasons") -> offset($offset) -> limit($items);
		$purpose = $query -> execute();
		return $purpose;
	}

}
?>