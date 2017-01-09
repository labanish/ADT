<?php
class Spouses extends Doctrine_Record {

	public function setTableDefinition() {
		//Patient
		$this -> hasColumn('primary_spouse','varchar',30);
		//Spouse
		$this -> hasColumn('secondary_spouse','varchar',30);
	}

	public function setUp() {
		$this -> setTableName('spouses');
		$this -> hasMany('Patient as Primary',array('local' =>'primary_spouse', 'foreign' => 'Patient_Number_CCC'));
		$this -> hasMany('Patient as Secondary',array('local' =>'secondary_spouse', 'foreign' =>'Patient_Number_CCC'));
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("spouses");
		$spouses = $query -> execute();
		return $spouses;
	}
	
	public function getPatient($id = NULL){
		$query = Doctrine_Query::create() -> select("*") -> from("spouses")->where("secondary_spouse = ? ",array($id));
		$spouses = $query -> execute();
		return $spouses;
	}

	public function getSpouse($id = NULL){
		$query = Doctrine_Query::create() -> select("*") -> from("spouses")->where("primary_spouse = ? ",array($id));
		$spouses = $query -> execute();
		return $spouses;
	}
}
