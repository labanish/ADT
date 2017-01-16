<?php
class Drugcode extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Drug', 'varchar', 100);
		$this -> hasColumn('Unit', 'varchar', 30);
		$this -> hasColumn('Pack_Size', 'varchar', 100);
		$this -> hasColumn('Safety_Quantity', 'varchar', 4);
		$this -> hasColumn('Generic_Name', 'varchar', 100);
		$this -> hasColumn('Supported_By', 'varchar', 30);
		$this -> hasColumn('classification', 'varchar', 50);
		$this -> hasColumn('none_arv', 'varchar', 1);
		$this -> hasColumn('Tb_Drug', 'varchar', 1);
		$this -> hasColumn('Drug_In_Use', 'varchar', 1);
		$this -> hasColumn('Comment', 'varchar', 50);
		$this -> hasColumn('Dose', 'varchar', 20);
		$this -> hasColumn('Duration', 'varchar', 4);
		$this -> hasColumn('Quantity', 'varchar', 4);
		$this -> hasColumn('Source', 'varchar', 10);
		$this -> hasColumn('Type', 'varchar', 1);
		$this -> hasColumn('Supplied', 'varchar', 1);
		$this -> hasColumn('Enabled', 'varchar', 1);
		$this -> hasColumn('Strength', 'varchar', 20);
		$this -> hasColumn('Merged_To', 'varchar', 50);
		$this -> hasColumn('map', 'int', 11);
		$this -> hasColumn('instructions', 'varchar',255);
	}

	public function setUp() {
		$this -> setTableName('drugcode');
		$this -> hasOne('Generic_Name as Generic', array('local' => 'Generic_Name', 'foreign' => 'id'));
		$this -> hasOne('Drug_Unit as Drug_Unit', array('local' => 'Unit', 'foreign' => 'id'));
		$this -> hasOne('Supporter as Supporter', array('local' => 'Supported_By', 'foreign' => 'id'));
		$this -> hasOne('suppliers as Suppliers', array('local' => 'Supported_By', 'foreign' => 'id'));
		$this -> hasMany('Brand as Brands', array('local' => 'id', 'foreign' => 'Drug_Id'));
		$this -> hasOne('Dose as Drug_Dose', array('local' => 'Dose', 'foreign' => 'id'));
		$this -> hasOne('Sync_Drug as S_Drug', array('local' => 'map', 'foreign' => 'id'));

	}

	public function getAll($source = 0, $access_level = "") {
		if ($access_level == "" || $access_level == "facility_administrator") {
			$displayed_enabled = "Source='0' or Source !='0'";
		} else {
			$displayed_enabled = "(Source='$source' or Source='0') AND Enabled='1'";
		}

		$query = Doctrine_Query::create() -> select("d.id,d.Drug,du.Name as drug_unit,d.Pack_Size,d.Dose,s.Name as supplier,d.Safety_Quantity,d.Quantity,d.Duration,d.Enabled,d.Merged_To,d.map") -> from("Drugcode d") -> leftJoin('d.Drug_Unit du, d.Suppliers s') -> where($displayed_enabled) -> orderBy("id asc");
		$drugsandcodes = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drugsandcodes;
	}

	public function getAllEnabled($source = 0, $access_level = "") {
		$query = Doctrine_Query::create() -> select("id,Drug,Pack_Size,Safety_Quantity,Quantity,Duration,Enabled,Merged_To") -> from("Drugcode") -> where('enabled="1"') -> orderBy("Drug asc");
		$drugsandcodes = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drugsandcodes;
	}

	public function getARVs() {
		$query = Doctrine_Query::create() -> select("Drug,Pack_Size,Safety_Quantity,Quantity,Duration") -> from("Drugcode") -> where("None_Arv != '1'") -> orderBy("id asc");
		$drugsandcodes = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drugsandcodes;
	}

	public function getAllObjects($source = 0) {
		$query = Doctrine_Query::create() -> select("UPPER(d.Drug) As Drug,d.Pack_Size,d.Safety_Quantity,d.Quantity,d.Duration") -> from("Drugcode d") -> where("d.Supported_By='$source' and Enabled='1'") -> orderBy("id asc");
		$drugsandcodes = $query -> execute(array());
		return $drugsandcodes;
	}

	public function getBrands() {
		$query = Doctrine_Query::create() -> select("id,Drug") -> from("Drugcode") -> where("enabled='1'");
		$drugsandcodes = $query -> execute();
		return $drugsandcodes;
	}

	public function getEnabledDrugs() {
		$query = Doctrine_Query::create() -> select("id,Drug") -> from("Drugcode") -> where("Enabled='1'");
		$drugsandcodes = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drugsandcodes;
	}
	
	public function getNonMappedDrugs() {
		$query = Doctrine_Query::create() -> select("d.*,du.Name as drug_unit") -> from("drugcode d") -> leftJoin('d.Drug_Unit du') -> where("Enabled = '1' AND (map='' OR map='0')") -> orderBy("drug asc");
		$drugcodes = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drugcodes;
	}
	public function getTotalNumber($source = 0) {
		$query = Doctrine_Query::create() -> select("count(*) as Total_Drugs") -> from("Drugcode") -> where('Source = "' . $source . '" or Source ="0"');
		$total = $query -> execute();
		return $total[0]['Total_Drugs'];
	}

	public function getPagedDrugs($offset, $items, $source = 0) {
		$query = Doctrine_Query::create() -> select("Drug,Unit,Pack_Size,Safety_Quantity,Generic_Name,Supported_By,Dose,Duration,Quantity,Source,Enabled,Supplied") -> from("Drugcode") -> where('Source = "' . $source . '" or Source ="0"') -> offset($offset) -> limit($items);
		$drugs = $query -> execute();
		return $drugs;
	}

	public static function getDrugCode($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("Drugcode") -> where("id = '$id'");
		$drugs = $query -> execute();
		return $drugs[0];
	}
	
	public function getDrug($drug_id,$ccc_id){
       $sql="SELECT dc.*,du.Name as drugunit,dc.map
             FROM drugcode dc
             LEFT JOIN drug_unit du ON du.id=dc.unit
             WHERE dc.id='$drug_id'";
       $query=$this->db->query($sql);
       $drugs=$query->result_array();
	   if($drugs){
	   	return $drugs[0];
	   }
       
	}
	
	public function getDrugBatches($drug_id,$ccc_id,$facility_code,$today){
        $sql = "SELECT d.id,d.drug as drugname,du.Name AS unit,d.pack_size,dsb.batch_number,dsb.expiry_date,dsb.stock_type,dsb.balance 
				FROM drug_stock_balance dsb 
				LEFT JOIN drugcode d ON d.id=dsb.drug_id 
				LEFT JOIN drug_unit du ON du.id = d.unit 
				WHERE dsb.drug_id='$drug_id'  
				AND dsb.expiry_date > CURDATE() 
				AND dsb.balance > 0   
				AND dsb.facility_code='$facility_code' 
				AND dsb.stock_type='$ccc_id' 
				ORDER BY dsb.expiry_date asc";
       $query=$this->db->query($sql);
       $batches=$query->result_array();
       return $batches;
	}

	public static function getDrugCodeHydrated($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("Drugcode") -> where("id = '$id'");
		$drugs = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drugs;
	}

	public function deleteBrand($id) {
		$query = Doctrine_Query::create() -> delete('brand b') -> where("b.id ='$id'");
		$rows = $query -> execute();
		return $rows;
	}

	public function getDrugID($drugname) {
		$query = Doctrine_Query::create() -> select("id") -> from("Drugcode") -> where("Drug like '%$drugname%'");
		$drugs = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drugs[0]['id'];
	}

	public function getItems() {
		$query = Doctrine_Query::create() -> select("id,Drug AS Name") -> from("Drugcode")->where("Enabled='1'")->orderby("Drug asc");
		$drugs = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drugs;
	}

}
?>
