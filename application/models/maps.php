<?php
class Maps extends Doctrine_Record {
	public function setTableDefinition() {
		$this -> hasColumn('status', 'varchar', 10);
		$this -> hasColumn('created', 'datetime');
		$this -> hasColumn('updated', 'datetime');
		$this -> hasColumn('code', 'datetime');
		$this -> hasColumn('period_begin', 'date');
		$this -> hasColumn('period_end', 'date');
		$this -> hasColumn('reports_expected', 'int', 11);
		$this -> hasColumn('reports_actual', 'int', 11);
		$this -> hasColumn('services', 'varchar', 255);
		$this -> hasColumn('sponsors', 'varchar', 255);
		$this -> hasColumn('art_adult', 'int', 11);
		$this -> hasColumn('art_child', 'int', 11);
		$this -> hasColumn('new_male', 'int', 11);
		$this -> hasColumn('new_female', 'int', 11);
		$this -> hasColumn('revisit_male', 'int', 11);
		$this -> hasColumn('revisit_female', 'int', 11);
		$this -> hasColumn('new_pmtct', 'int', 11);
		$this -> hasColumn('revisit_pmtct', 'int', 11);
		$this -> hasColumn('total_infant', 'int', 11);
		$this -> hasColumn('pep_adult', 'int', 11);
		$this -> hasColumn('pep_child', 'int', 11);
		$this -> hasColumn('total_adult', 'int', 11);
		$this -> hasColumn('total_child', 'int', 11);
		$this -> hasColumn('diflucan_adult', 'int', 11);
		$this -> hasColumn('diflucan_child', 'int', 11);
		$this -> hasColumn('new_cm', 'int', 11);
		$this -> hasColumn('revisit_cm', 'int', 11);
		$this -> hasColumn('new_oc', 'int', 11);
		$this -> hasColumn('revisit_oc', 'int', 11);
		$this -> hasColumn('comments', 'text');
		$this -> hasColumn('report_id', 'int', 11);
		$this -> hasColumn('facility_id', 'int', 11);
	}

	public function setUp() {
		$this -> setTableName('maps');
		$this -> hasOne('Sync_Facility as S_Facility', array('local' => 'facility_id', 'foreign' => 'id'));
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("maps");
		$maps = $query -> execute();
		return $maps;
	}

	public function getMap($id){
		$query = Doctrine_Query::create() -> select("*") -> from("maps")->where("id =? ",array($id));
		$maps = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $maps;
	}

	public function getPeriods() {
		$query = Doctrine_Query::create() -> select("Distinct(period_begin) as periods") -> from("maps")->orderBy("period_begin desc");
		$maps = $query -> execute();
		return $maps;
	}

}
?>
