<?php
class Access_Level extends Doctrine_Record {
	public function setTableDefinition() {
		$this -> hasColumn('Level_Name', 'varchar', 50);
		$this -> hasColumn('Description', 'text');
		$this -> hasColumn('Indicator', 'varchar', 100);
	}

	public function setUp() {
		$this -> setTableName('access_level');
		$this -> hasMany('Users as Users', array('local' => 'id', 'foreign' => 'Access_Level'));
	}

	public function getAll($user_type="1") {
		$query = Doctrine_Query::create() -> select("al.Id as Id,al.Level_Name as Access") -> from("access_level al") ->where($user_type);
		$users = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $users;
	}
	
	public function getAllHydrated() {
		$query = Doctrine_Query::create() -> select("al.Id as Id,al.Level_Name as Access") -> from("access_level al");
		$users = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $users;
	}

}
