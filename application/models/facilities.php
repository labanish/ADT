<?php
class Facilities extends Doctrine_Record {
	public function setTableDefinition() {
		$this -> hasColumn('facilitycode', 'int', 32);
		$this -> hasColumn('name', 'varchar', 100);
		$this -> hasColumn('facilitytype', 'varchar', 5);
		$this -> hasColumn('parent', 'varchar', 10);
		$this -> hasColumn('district', 'varchar', 5);
		$this -> hasColumn('county', 'int', 11);
		$this -> hasColumn('flag', 'varchar', 2);
		$this -> hasColumn('email', 'varchar', 50);
		$this -> hasColumn('phone', 'varchar', 50);
		$this -> hasColumn('adult_age', 'varchar', 10);
		$this -> hasColumn('weekday_max', 'varchar', 20);
		$this -> hasColumn('weekend_max', 'varchar', 20);
		$this -> hasColumn('supported_by', 'int', 5);
		$this -> hasColumn('service_art', 'int', 2);
		$this -> hasColumn('service_pmtct', 'int', 2);
		$this -> hasColumn('service_pep', 'int', 2);
		$this -> hasColumn('supplied_by', 'int', 2);
		$this -> hasColumn('map', 'int', 11);
	}

	public function setUp() {
		$this -> setTableName('facilities');
		$this -> hasOne('District as Parent_District', array('local' => 'district', 'foreign' => 'id'));
		$this -> hasOne('Counties as County', array('local' => 'county', 'foreign' => 'id'));
		$this -> hasOne('Facility_Types as Type', array('local' => 'facilitytype', 'foreign' => 'id'));
		$this -> hasOne('Suppliers as supplier', array('local' => 'supplied_by', 'foreign' => 'id'));
		$this -> hasOne('Supporter as support', array('local' => 'supported_by', 'foreign' => 'id'));
		$this -> hasOne('Sync_Facility as S_Facility', array('local' => 'map', 'foreign' => 'id'));
	}

	public function getDistrictFacilities($district) {
		$query = Doctrine_Query::create() -> select("facilitycode,name") -> from("Facilities") -> where("District = '" . $district . "'");
		$facilities = $query -> execute();
		return $facilities;
	}

	public static function search($search) {
		$query = Doctrine_Query::create() -> select("facilitycode,name") -> from("Facilities") -> where("name like '%" . $search . "%'");
		$facilities = $query -> execute();
		return $facilities;
	}

	public static function getFacilityName($facility_code) {
		$query = Doctrine_Query::create() -> select("name") -> from("Facilities") -> where("facilitycode = '$facility_code'");
		$facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $facility[0]['name'];
	}

	public static function getTotalNumber($district = 0) {
		if ($district == 0) {
			$query = Doctrine_Query::create() -> select("COUNT(*) as Total_Facilities") -> from("Facilities");
		} else if ($district > 0) {
			$query = Doctrine_Query::create() -> select("COUNT(*) as Total_Facilities") -> from("Facilities") -> where("district = '$district'");
		}
		$count = $query -> execute();
		return $count[0] -> Total_Facilities;
	}

	public static function getTotalNumberInfo($facility_code) {
		$query = Doctrine_Query::create() -> select("COUNT(*) as Total_Facilities") -> from("Facilities") -> where("facilitycode = '$facility_code'");
		$count = $query -> execute();
		return $count[0] -> Total_Facilities;
	}

	public function getPagedFacilities($offset, $items, $district = 0) {
		if ($district == 0) {
			$query = Doctrine_Query::create() -> select("*") -> from("Facilities") -> orderBy("name") -> offset($offset) -> limit($items);
		} else if ($district > 0) {
			$query = Doctrine_Query::create() -> select("*") -> from("Facilities") -> where("district = '$district'") -> orderBy("name") -> offset($offset) -> limit($items);
		}

		$facilities = $query -> execute();
		return $facilities;
	}

	public static function getFacility($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("Facilities") -> where("id = '$id'");
		$facility = $query -> execute();
		return $facility[0];
	}

	public function getCurrentFacility($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("Facilities") -> where("facilitycode = '$id'");
		$facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $facility;
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("Facilities") -> orderBy("name");
		$facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $facility;
	}

	public function getFacilities() {
		$query = Doctrine_Query::create() -> select("facilitycode,name") -> from("Facilities") -> orderBy("name");
		$facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $facility;
	}

	public static function getSatellites($parent) {
		$query = Doctrine_Query::create() -> select("id,facilitycode,name") -> from("Facilities") -> where("parent = '$parent' AND facilitycode !='$parent' ") -> orderBy("name asc");
		$facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $facility;
	}

	public static function getCodeFacility($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("Facilities") -> where("facilitycode = '$id'");
		$facility = $query -> execute();
		return $facility[0];
	}

	public static function getMapFacility($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("Facilities") -> where("map = '$id'");
		$facility = $query -> execute();
		return $facility[0];
	}

	public static function getSupplier($id) {
		$query = Doctrine_Query::create() -> select("supplied_by") -> from("Facilities") -> where("facilitycode = '$id'");
		$facility = $query -> execute();
		return $facility[0];
	}

	public static function getParent($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("Facilities") -> where("facilitycode = '$id'");
		$facility = $query -> execute();
		return $facility[0];
	}

	public function getMainSupplier($facility_code) {
		$query = Doctrine_Query::create() -> select("*") -> from("Facilities f") -> leftJoin('f.supplier s') -> where("facilitycode = '$facility_code'");
		$facility = $query -> execute();
		return $facility[0];
	}

	public function getType($facility_code) {
		$query = Doctrine_Query::create() -> select("count(*) as total") -> from("Facilities") -> where("parent = '$facility_code'");
		$facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $facility[0]['total'];
	}

	public function getId($facility_code) {
		$query = Doctrine_Query::create() -> select("id") -> from("Facilities") -> where("facilitycode='$facility_code'");
		$facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $facility[0];
	}

	public function getSatellite($parent) {
		$query = Doctrine_Query::create() -> select("*") -> from("Facilities") -> where("facilitycode!='$parent' and parent = '$parent'") -> orderBy("name asc");
		$facility = $query -> execute();
		return $facility;
	}
	
	public static function getCentralCode($id) {
		$query = Doctrine_Query::create() -> select("facilitycode,parent") -> from("Facilities") -> where("facilitycode = '$id'");
		$facility = $query -> execute();
		return $facility[0]['parent'];
	}
	public static function getCentralName($id) {
		$query = Doctrine_Query::create() -> select("id,facilitycode,name") -> from("Facilities") -> where("facilitycode = '$id'");
		$facility = $query -> execute();
		return $facility;
	}
	public static function getParentandSatellites($parent) {
		$query = Doctrine_Query::create() -> select("DISTINCT(facilitycode) as code") -> from("Facilities") -> where("parent = '$parent' OR facilitycode ='$parent' ") -> orderBy("name asc");
		$facilities = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
     	$lists=array();
		if($facilities){
			foreach($facilities as $facility){
                $lists[]=$facility['code'];
			}
		}
		return $lists;
	}
	public function getItems() {
		$query = Doctrine_Query::create() -> select("facilitycode AS id,name AS Name") -> from("Facilities")->orderBy("name asc");
		$facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $facility;
	}

	public static function get_user_facilities_id($parent_code) {
		$query = Doctrine_Query::create() -> select("id") -> from("Facilities") -> where("parent = '$parent_code'");
		$facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $facility;
	}

}
