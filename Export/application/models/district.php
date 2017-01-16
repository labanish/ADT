<?php
class District extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Name', 'varchar', 100);
		$this -> hasColumn('active', 'int', 5);
	}//end setTableDefinition

	public function setUp() {
		$this -> setTableName('district');
	}//end setUp

	public function getTotalNumber() {
		$query = Doctrine_Query::create() -> select("count(*) as Total_Districts") -> from("District");
		$total = $query -> execute();
		return $total[0]['Total_Districts'];
	}

	public function getPagedDistricts($offset, $items) {
		$query = Doctrine_Query::create() -> select("*") -> from("District") -> offset($offset) -> limit($items);
		$districts = $query -> execute();
		return $districts;
	}
	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("District");
		$districts = $query -> execute();
		return $districts;
	}
	
	public function getPOB(){
		$query = Doctrine_Query::create() -> select("*") -> from("District")->orderby("Name asc");
		$districts = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $districts;
		
	}
	public function getActive() {
		$query = Doctrine_Query::create() -> select("*") -> from("District")->where("active='1'");
		$districts = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $districts;
	}

	public function getItems() {
		$query = Doctrine_Query::create() -> select("id,Name") -> from("District")->where("active='1'")->orderby("Name asc");
		$districts = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $districts;
	}

	public function getID($name) {
		$query = Doctrine_Query::create() -> select("id") -> from("District")->where("Name LIKE '%$name%'");
		$districts = $query -> execute();
		$district_id=0;
		if($districts){
			$district_id=$districts[0]['id'];
		}
		return $district_id;
	}

}//end class
?>