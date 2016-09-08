<?php
class Regimen_Category extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Name', 'varchar', 50);
		$this -> hasColumn('Active', 'varchar', 2);
	}

	public function setUp() {
		$this -> setTableName('regimen_category');
		$this -> hasMany('Regimen as Regimens', array('local' => 'id', 'foreign' => 'category_id'));
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("Regimen_Category") -> where("Active", "1") -> orderBy("Name asc");
		$regimens = $query -> execute();
		return $regimens;
	}

	public function getAllHydrate() {
		$query = Doctrine_Query::create() -> select("*") -> from("Regimen_Category") -> where("Active", "1");
		$regimens = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $regimens;
	}

}
?>