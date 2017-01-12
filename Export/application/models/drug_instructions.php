<?php
class Drug_instructions extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('name', 'varchar', 255);
		$this -> hasColumn('active', 'int', 11);
		
	}

	public function setUp() {
		$this -> setTableName('drug_instructions');
        }

	public function getAllInstructions() {
		$query = Doctrine_Query::create() -> select("id,name") -> from("drug_instructions") -> where("active='1'");
		$druginstructions = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $druginstructions;
	}

}
?>
