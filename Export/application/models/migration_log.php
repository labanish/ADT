<?php
class Migration_Log extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('source', 'varchar', 150);
		$this -> hasColumn('last_index', 'int', 100);
		$this -> hasColumn('count', 'int', 50);
	}

	public function setUp() {
		$this -> setTableName('migration_log');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("migration_log");
		$migration = $query -> execute();
		return $migration;
	}

	public function getTargets() {
		$query = Doctrine_Query::create() -> select("*") -> from("migration_log") -> where("source !='auto_update' and source !='patient_appointment'");
		$migration = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $migration;
	}

	public function getLog($source) {
		$query = Doctrine_Query::create() -> select("*") -> from("migration_log") -> where("source='$source'");
		$migration = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return @$migration[0];
	}

}
?>