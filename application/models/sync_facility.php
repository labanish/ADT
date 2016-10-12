<?php
class Sync_Facility extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('name', 'varchar', 255);
		$this -> hasColumn('code', 'varchar', 15);
		$this -> hasColumn('category', 'varchar', 15);
		$this -> hasColumn('sponsors', 'varchar', 255);
		$this -> hasColumn('services', 'varchar', 255);
		$this -> hasColumn('manager_id', 'int', 11);
		$this -> hasColumn('district_id', 'int', 11);
		$this -> hasColumn('address_id', 'int', 11);
		$this -> hasColumn('parent_id', 'int', 11);
		$this -> hasColumn('ordering', 'tinyint', 1);
		$this -> hasColumn('affiliation', 'varchar', 255);
		$this -> hasColumn('service_point', 'tinyint', 1);
		$this -> hasColumn('county_id', 'int', 11);
		$this -> hasColumn('hcsm_id', 'int', 11);
		$this -> hasColumn('keph_level', 'varchar', 25);
		$this -> hasColumn('location', 'varchar', 255);
		$this -> hasColumn('affiliate_organization_id', 'int', 11);

	}

	public function setUp() {
		$this -> setTableName('sync_facility');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("sync_facility");
		$sync_facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_facility;
	}

	public function getId($facility_code, $status_code = 0) {

		/*
		status_code >= 3 -> Central;ensure category is central;ordering = 1;service_point = 1
		status_code = 0 -> Satellite;ensure category is satellite;ordering = 0;service_point = 1 (also dispensing_point)
		status_code = 1 -> Standalone;ensure category is standalone;ordering = 1;service_point = 0
		*/
		if ($status_code == 0) {
			$conditions = "code='$facility_code' and ordering='1'";
		} else if ($status_code == 3) {
			$conditions = "code='$facility_code' and category like '%standalone%'";
		} else {
			$conditions = "code='$facility_code' and service_point='1'";
		}
		$query = Doctrine_Query::create() -> select("id") -> from("sync_facility") -> where("$conditions");
		$sync_facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return @$sync_facility[0];
	}

	public function getCode($facility_id, $status_code = 0) {
		if ($status_code == 0) {
			$conditions = "id='$facility_id' and ordering='1'";
		} else if ($status_code == 3) {
			$conditions = "id='$facility_id' and category like '%standalone%'";
		} else {
			$conditions = "id='$facility_id' and service_point='1'";
		}

		$query = Doctrine_Query::create() -> select("code") -> from("sync_facility") -> where("$conditions");
		$sync_facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return @$sync_facility[0];
	}

	public function getSatellites($central_site) {//Include CUrrent facility
		$query = Doctrine_Query::create() -> select("id") -> from("sync_facility") -> where("parent_id='$central_site'");
		$sync_facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_facility;
	}

	public function getOtherSatellites($central_site, $facility_code) { //Only get satellites
		$query = Doctrine_Query::create() -> select("id") -> from("sync_facility") -> where("parent_id='$central_site' and code !='$facility_code'");
		$sync_facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_facility;
	}

	public function getSatellitesDetails($central_site) {
		$query = Doctrine_Query::create() -> select("*") -> from("sync_facility") -> where("parent_id='$central_site'");
		$sync_facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_facility;
	}

	public function get_facility_category($code = NULL) 
	{
		$query = Doctrine_Query::create() -> select("category") -> from("sync_facility") -> where("code='$code'");
		$sync_facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_facility[0]['category'];
	}

	public function get_active() {
		$query = Doctrine_Query::create() -> select("*") -> from("sync_facility") -> where("Active='1'");
		$sync_facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_facility;
	}

}
?>

