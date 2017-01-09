<?php
class Dependants extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('parent','varchar',30);
		$this -> hasColumn('child','varchar',30);
	}

	public function setUp() {
		$this -> setTableName('dependants');
		$this -> hasMany('Patient as pParent', array('local' => 'parent', 'foreign' => 'Patient_Number_CCC'));
		$this -> hasMany('Patient as pChild', array('local' => 'child', 'foreign' => 'Patient_Number_CCC'));
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("dependants");
		$dependants = $query -> execute();
		return $dependants;
	}
	
	public function getParent($id = NULL){
		$query = Doctrine_Query::create() -> select("*") -> from("dependants")->where("child = ? ",array($id));
		$dependants = $query -> execute();
		return $dependants;
	}

	public function getChild($id = NULL){
		$query = Doctrine_Query::create() -> select("*") -> from("dependants")->where("parent = ? ",array($id));
		$dependants = $query -> execute();
		return $dependants;
	}
}
