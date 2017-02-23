<?php
class Sync_Drug extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('name', 'varchar', 255);
		$this -> hasColumn('abbreviation', 'varchar', 255);
		$this -> hasColumn('strength', 'varchar', 255);
		$this -> hasColumn('packsize', 'int', 7);
		$this -> hasColumn('formulation', 'varchar', 255);
		$this -> hasColumn('unit', 'varchar', 255);
		$this -> hasColumn('note', 'varchar', 255);
		$this -> hasColumn('weight', 'int', 4);
		$this -> hasColumn('category_id', 'int', 11);
		$this -> hasColumn('regimen_id', 'int', 11);
	}

	public function setUp() {
		$this -> setTableName('sync_drug');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("sync_drug");
		$sync_drug = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_drug;
	}

	public function getActive() {
		$drug_name = "CONCAT_WS('] ',CONCAT_WS(' [',name,abbreviation),CONCAT_WS(' ',strength,formulation)) as name";
		$query = Doctrine_Query::create() -> select("id,packsize,$drug_name") -> from("sync_drug") -> where("Active = '1' and (category_id='1' or category_id='2' or category_id='3' or category_id='4')") -> orderBy("name asc");
		$sync_drug = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_drug;
	}
	public function getMapActive() {		
		$drug_name = "CONCAT_WS('] ',CONCAT_WS(' [',name,abbreviation),CONCAT_WS(' ',strength,formulation)) as name";
		$query = Doctrine_Query::create() -> select("id,packsize,$drug_name") -> from("sync_drug") -> where("Active = '1' and (category_id='1' or category_id='2' or category_id='3' or category_id='4')") -> orderBy("name asc");
		$sync_drug = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_drug;
	}

	public function getActiveList() {
		$drug_name = "CONCAT_WS('] ',CONCAT_WS(' [',name,abbreviation),CONCAT_WS(' ',strength,formulation)) as Drug,unit as Unit_Name,packsize as Pack_Size,category_id as Category";
		$query = Doctrine_Query::create() -> select("id,$drug_name") -> from("sync_drug") -> where("Active = '1' and (category_id='1' or category_id='2' or category_id='3' or category_id='4')") -> orderBy("name asc");
		$sync_drug = $query -> execute();
		return $sync_drug;
	}

	public function getPackSize($id) {
		$query = Doctrine_Query::create() -> select("packsize") -> from("sync_drug") -> where("id='$id'");
		$sync_drug = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_drug[0];
	}

}
?>