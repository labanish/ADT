<?php
class User_Facilities extends Doctrine_Record {
	public function setTableDefinition() {
		$this -> hasColumn('user_id', 'int', 11);
		$this -> hasColumn('facility', 'text');
	}

	public function setUp() {
		$this -> setTableName('user_facilities');
		$this -> hasOne('Sync_Facility as facility', array('local' => 'user_id', 'foreign' => 'id'));
	}

	public function getFacilityList($user_id) {
		$query = Doctrine_Query::create() -> select("*") -> from("user_facilities") -> where("user_id = '" . $user_id . "'");
		$rights = $query -> execute();
		return $rights[0];
	}

	public function getHydratedFacilityList($user_id) {
		$data = array();
		$query = Doctrine_Query::create() -> select("*") -> from("user_facilities") -> where("user_id = '" . $user_id . "'");
		$rights = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		if(!empty($rights)){
			$data = $rights[0];
		}
		return $data;
	}

}
