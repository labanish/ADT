<?php
class Visit_Purpose extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Name', 'varchar', 50);
		$this -> hasColumn('Active', 'varchar', 2);
	}

	public function setUp() {
		$this -> setTableName('visit_purpose');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("Visit_Purpose") -> where("Active", "1");
		$purposes = $query -> execute();
		return $purposes;
	}

	public function getFiltered($enrollment_check,$start_art_check) 
	{   
		$filter = "";
		if($enrollment_check == 1)
		{
           $filter .= " AND Name NOT LIKE '%enroll%'";
		}

		if($start_art_check == 1)
		{
           $filter .= " AND Name NOT LIKE '%startart%'";
		}
		$query = Doctrine_Query::create() -> select("*") -> from("Visit_Purpose") -> where("Active='1' $filter ");
		$purposes = $query -> execute();
		return $purposes;
	}
	
	public function getThemAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("Visit_Purpose")->orderby("Name asc");
		$purposes = $query -> execute();
		return $purposes;
	}

	public function getTotalNumber() {
		$query = Doctrine_Query::create() -> select("count(*) as Total_Purposes") -> from("Visit_Purpose");
		$total = $query -> execute();
		return $total[0]['Total_Purposes'];
	}

	public function getPagedPurposes($offset, $items) {
		$query = Doctrine_Query::create() -> select("Name") -> from("Visit_Purpose") -> offset($offset) -> limit($items);
		$purposes = $query -> execute();
		return $purposes;
	}
	public static function getSource($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("Visit_Purpose") -> where("id = '$id'");
		$ois = $query -> execute();
		return $ois[0];
	}

}
?>