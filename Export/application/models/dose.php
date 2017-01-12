<?php
class Dose extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Name', 'varchar', 25);
		$this -> hasColumn('Value', 'float');
		$this -> hasColumn('Frequency', 'varchar', 1);
		$this -> hasColumn('Active', 'varchar', 20);
	}

	public function setUp() {
		$this -> setTableName('dose');
	}

	public function getAll($access_level = "") {
		if ($access_level = "" || $access_level == "facility_administrator") {
			$query = Doctrine_Query::create() -> select("*") -> from("dose");
		} else {
			$query = Doctrine_Query::create() -> select("*") -> from("dose") -> where("Active='1'");
		}
		$doses = $query -> execute();
		return $doses;
	}

	public function getAllActive() {
		$query = Doctrine_Query::create() -> select("*") -> from("dose") -> where("Active='1'") -> orderBy('Name asc');
		$doses = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $doses;
	}

	public function getTotalNumber() {
		$query = Doctrine_Query::create() -> select("count(*) as Total_Doses") -> from("Dose");
		$total = $query -> execute();
		return $total[0]['Total_Doses'];
	}

	public function getPagedDoses($offset, $items) {
		$query = Doctrine_Query::create() -> select("*") -> from("Dose") -> offset($offset) -> limit($items);
		$doses = $query -> execute();
		return $doses;
	}

	public static function getDose($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("Dose") -> where("id = '$id'");
		$doses = $query -> execute();
		return $doses[0];
	}

	public static function getDoseHydrated($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("Dose") -> where("id = '$id'");
		$doses = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $doses;
	}

	public function getDoseLabel($name) {
		$query = Doctrine_Query::create() -> select("*") -> from("Dose") -> where("Name='$name'");
		$doses = $query -> execute();
		return $doses[0];
	}

}
