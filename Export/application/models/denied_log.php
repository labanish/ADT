<?php
class Denied_Log extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('ip_address', 'text');
		$this -> hasColumn('location','varchar',150);
		$this -> hasColumn('user_id','varchar',150);
		$this -> hasColumn('timestamp','varchar',150);
	}

	public function setUp() {
		$this -> setTableName('denied_log');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("denied_log");
		$sync_log = $query -> execute();
		return $sync_log;
	}
	


}
