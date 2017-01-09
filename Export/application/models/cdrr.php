<?php
class Cdrr extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('status', 'varchar', 20);
		$this -> hasColumn('created', 'datetime');
		$this -> hasColumn('updated', 'datetime');
		$this -> hasColumn('code', 'varchar', 15);
		$this -> hasColumn('period_begin', 'date');
		$this -> hasColumn('period_end', 'date');
		$this -> hasColumn('comments', 'text');
		$this -> hasColumn('reports_expected', 'int', 11);
		$this -> hasColumn('reports_actual', 'int', 11);
		$this -> hasColumn('services', 'varchar', 255);
		$this -> hasColumn('sponsors', 'varchar', 255);
		$this -> hasColumn('non_arv', 'int', 11);
		$this -> hasColumn('delivery_note', 'varchar', 255);
		$this -> hasColumn('order_id', 'int', 11);
		$this -> hasColumn('facility_id', 'int', 11);
	}

	public function setUp() {
		$this -> setTableName('cdrr');
		$this -> hasOne('Sync_Facility as S_Facility', array('local' => 'facility_id', 'foreign' => 'id'));
		$this -> hasOne('Facilities as Facility', array('local' => 'facility_id', 'foreign' => 'id'));
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("cdrr");
		$cdrrs = $query -> execute();
		return $cdrrs;
	}

	public function getCdrr($id){
		$query = Doctrine_Query::create() -> select("*") -> from("cdrr")->where("id =? ",array($id));
		$cdrrs = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $cdrrs;
	}

	public function getPeriods() {
		$query = Doctrine_Query::create() -> select("Distinct(period_begin) as periods") -> from("cdrr")->orderBy("period_begin desc");
		$cdrrs = $query -> execute();
		return $cdrrs;
	}


}
?>