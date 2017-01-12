<?php
class Drug_Prophylaxis extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('name', 'varchar', 150);
	}

	public function setUp() {
		$this -> setTableName('drug_prophylaxis');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("drug_prophylaxis");
		$drug_prophylaxis = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drug_prophylaxis;
	}
	public function getItems() {
		$query = Doctrine_Query::create() -> select("id, name AS Name") -> from("drug_prophylaxis");
		$drug_prophylaxis = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drug_prophylaxis;
	}

}
