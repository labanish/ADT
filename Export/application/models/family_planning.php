<?php
class Family_Planning extends Doctrine_Record {
	public function setTableDefinition() {
		$this -> hasColumn('name', 'varchar',150);
		$this -> hasColumn('indicator', 'varchar',20);
		$this -> hasColumn('active', 'int','5');
	}

	public function setUp() {
		$this -> setTableName('family_planning');
	}

	public static function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("family_planning")->where("active=1")->orderBy("name asc");
		$families= $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $families;
	}
	public function getItems() {
		$query = Doctrine_Query::create() -> select("indicator AS id,name AS Name") -> from("family_planning")-> where("active", "1")->orderBy("name asc");
		$families = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $families;
	}

}
