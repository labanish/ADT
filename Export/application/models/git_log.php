<?php
class Git_Log extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('hash_value', 'varchar', 255);
		$this -> hasColumn('update_time', 'timestamp');
	}

	public function setUp() {
		$this -> setTableName('git_log');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("git_log");
		$git_log = $query -> execute();
		return $git_log;
	}

	public function getLatestHash(){
		$query = Doctrine_Query::create() -> select("*") -> from("git_log")->where("hash_value !=''")->orderBy("id desc")->limit(1);
		$git_log = $query -> execute();
		return @$git_log[0]->hash_value;
	}

}
?>