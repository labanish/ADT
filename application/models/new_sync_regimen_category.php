<?php
class New_Sync_Regimen_Category extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Name', 'varchar', 300);
		$this -> hasColumn('Service', 'varchar', 100);
		$this -> hasColumn('Active', 'varchar', 2);
	}

	public function setUp() {
		$this -> setTableName('new_sync_regimen_category');
		$this -> hasMany('new_sync_regimen as Regimens', array('local' => 'id', 'foreign' => 'category_id'));
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("new_sync_regimen_category") -> where("Active", "1");
		$regimens = $query -> execute();
		return $regimens;
	}

	public function getAllHydrate() {
		$query = Doctrine_Query::create() -> select("*") -> from("new_sync_regimen_category") -> where("Active", "1");
		$regimens = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $regimens;
	}

}
?>