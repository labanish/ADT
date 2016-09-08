<?php
class Password_Log extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('user_id','varchar',150);
		$this -> hasColumn('password','varchar',150);
		$this -> hasColumn('date_changed','varchar',150);
	}

	public function setUp() {
		$this -> setTableName('password_log');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("password_log");
		$password_log = $query -> execute();
		return $password_log;
	}


}
