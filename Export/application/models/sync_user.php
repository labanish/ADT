<?php
class Sync_User extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('username', 'varchar', 11);
		$this -> hasColumn('password', 'char', 60);
		$this -> hasColumn('email', 'varchar', 128);
		$this -> hasColumn('name', 'varchar', 255);
		$this -> hasColumn('role', 'varchar', 30);
		$this -> hasColumn('status', 'char', 1);
		$this -> hasColumn('profile_id', 'int', 11);
	}

	public function setUp() {
		$this -> setTableName('sync_user');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("sync_user");
		$sync_user = $query -> execute();
		return $sync_user;
	}

}
?>