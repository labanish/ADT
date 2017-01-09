<?php
class Supporter extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Name', 'varchar', 25);
		$this -> hasColumn('Active', 'varchar', 2);
	}

	public function setUp() {
		$this -> setTableName('supporter');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("supporter");
		$supporters = $query -> execute();
		return $supporters;
	}

	public function getAllActive() {
		$query = Doctrine_Query::create() -> select("*") -> from("supporter") -> where("Active='1'");
		$supporters = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $supporters;
	}

	public function getThemAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("supporter");
		$supporters = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $supporters;
	}

	public function getTotalNumber() {
		$query = Doctrine_Query::create() -> select("count(*) as Total_Supporters") -> from("supporter") -> where("Active='1'");
		$total = $query -> execute();
		return $total[0]['Total_Supporters'];
	}

	public function getPagedSupporters($offset, $items) {
		$query = Doctrine_Query::create() -> select("Name,Active") -> from("supporter") -> where("Active='1'") -> offset($offset) -> limit($items);
		$supporters = $query -> execute();
		return $supporters;
	}

	public function getSource($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("supporter") -> where("id='$id'");
		$supporters = $query -> execute();
		return $supporters[0];
	}

}
