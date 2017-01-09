<?php
class New_Sync_Regimen extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('name', 'varchar', 255);
		$this -> hasColumn('code', 'varchar', 5);
		$this -> hasColumn('old_code', 'varchar', 45);
		$this -> hasColumn('description', 'text');
		$this -> hasColumn('category_id', 'int', 11);
	}

	public function setUp() {
		$this -> setTableName('new_sync_regimen');
		$this -> hasOne('New_Sync_Regimen_Category as Sync_Regimen_Category', array('local' => 'category_id', 'foreign' => 'id'));
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("new_sync_regimen");
		$sync_regimen = $query -> execute();
		return $sync_regimen;
	}

	public function getActive() {
		$query = Doctrine_Query::create() -> select("sr.id,sr.code,sr.name,sr.category_id, sr.Sync_Regimen_Category.Name as category_name") -> from("new_sync_regimen sr") -> orderBy("category_id, code asc");
		$sync_regimen = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_regimen;
	}

	public function getId($regimen_code) {
		$query = Doctrine_Query::create() -> select("id") -> from("new_sync_regimen") -> where("code like '%$regimen_code%'");
		$sync_regimen = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return @$sync_regimen[0]['id'];
	}

}
?>