<?php
class Regimen_Drug extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Regimen', 'varchar', 5);
		$this -> hasColumn('Drugcode', 'varchar', 5);
		$this -> hasColumn('Source', 'varchar', 10);
		$this -> hasColumn('Active', 'varchar', 10);
		$this -> hasColumn('Merged_From', 'varchar', 50);
		$this -> hasColumn('Regimen_Merged_From', 'varchar', 20);
	}

	public function setUp() {
		$this -> setTableName('regimen_drug');
		$this -> hasOne('Drugcode as Drug', array('local' => 'Drugcode', 'foreign' => 'id'));
	}

	public function getAll($source = 0, $access_level = "") {
		if ($access_level = "" || $access_level == "system_administrator") {
			$displayed_enabled = "";
		} else {
			$displayed_enabled = "AND Enabled='1'";
		}

		$query = Doctrine_Query::create() -> select("*") -> from("Regimen_Drug") -> where('Source = "' . $source . '" or Source ="0"' . $displayed_enabled);
		$regimen_drugs = $query -> execute();
		return $regimen_drugs;
	}

	public function getTotalNumber($source = 0) {
		$query = Doctrine_Query::create() -> select("count(*) as Total_Regimen_Drugs") -> from("Regimen_Drug") -> where('Source = "' . $source . '" or Source ="0"');
		$total = $query -> execute();
		return $total[0]['Total_Regimen_Drugs'];
	}

	public function getPagedRegimenDrugs($offset, $items, $source = 0) {
		$query = Doctrine_Query::create() -> select("Regimen,Drugcode,Active") -> from("Regimen_Drug") -> where('Source = "' . $source . '" or Source ="0"') -> offset($offset) -> limit($items);
		$regimen_drugs = $query -> execute();
		return $regimen_drugs;
	}

}
?>