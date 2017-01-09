<?php
class Access_Log extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('machine_code','varchar',150);
		$this -> hasColumn('user_id','varchar',150);
		$this -> hasColumn('access_level','int',5);
		$this -> hasColumn('start_time','varchar',50);
		$this -> hasColumn('end_time','varchar',50);
		$this -> hasColumn('facility_code','varchar',150);
		$this -> hasColumn('access_type','varchar',150);
	}

	public function setUp() {
		$this -> setTableName('access_log');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("access_log");
		$sync_log = $query -> execute();
		return $sync_log;
	}
	
	public function getLastUser($user_id){
		$query = Doctrine_Query::create() -> select("id") -> from("access_log")->where("user_id='$user_id'")->orderby("id desc")->limit("1");
		$sync_log = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_log[0]['id'];
	}
	


}
