<?php
class CCC_store_service_point extends Doctrine_Record {
	public function setTableDefinition() {
		$this -> hasColumn('Name', 'varchar', 100);
		$this -> hasColumn('Active', 'varchar', 2);
	}
	
	public function setUp() {
		$this -> setTableName('ccc_store_service_point');
	}
	
	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("ccc_store_service_point");
		$stores = $query -> execute();
		return $stores;
	}
	
	public function getAllActive() {
		$query = Doctrine_Query::create() -> select("*") -> from("ccc_store_service_point") -> where("Active", "1");
		$stores = $query -> execute();
		return $stores;
	}

	public function getActive() {
		$query = Doctrine_Query::create() -> select("*") -> from("ccc_store_service_point") -> where("Active", "1");
		$stores = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $stores;
	}
	public function getAllBut($ccc_id) {
		$query = Doctrine_Query::create() -> select("*") -> from("ccc_store_service_point") -> where("Active = 1 AND id!=$ccc_id") ->orderBy("id ASC");
		$stores = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $stores;
	}
	
	public static function getCCC($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("ccc_store_service_point") -> where("id = '$id' and Active='1' ");
		$ois = $query -> execute();
		return $ois[0];
	}

	public function getStoreGroups() {
		$query = Doctrine_Query::create() -> select("id,Name as name") -> from("ccc_store_service_point") -> where("Name LIKE '%store%' and Active='1'");
		$category['Store']=$query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		$query = Doctrine_Query::create() -> select("id,Name as name") -> from("ccc_store_service_point") -> where("Name LIKE '%pharm%' and Active='1'");
        $category['Pharmacy']=$query -> execute(array(), Doctrine::HYDRATE_ARRAY);
        return $category;
	}
	
}

?>
	