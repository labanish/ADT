<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Settings extends MY_Controller {
	function __construct() {
		parent::__construct();
		$this -> session -> set_userdata("link_id", "listing/regimen_service_type");
		$this -> session -> set_userdata("linkSub", "settings/listing/regimen_service_type");
		$this -> session -> set_userdata("linkTitle", "Settings Management");
	}

	public function enable($table = "", $id) {
		//If table is CCC_Store, disable CCC Store in drug_source and drug_destination
		if($table=="ccc_store_service_point"){
			$this -> db -> where('id', $id);
			$this -> db -> update('ccc_store_service_point', array("active" => 1));
			$sql = "SELECT * FROM ccc_store_service_point WHERE id='$id' LIMIT 1";
			
			$ccc_stores = CCC_store_service_point::getAllActive();
			$this -> session -> set_userdata('ccc_store',$ccc_stores);
		}
		else{
			$this -> db -> where('id', $id);
			$this -> db -> update($table, array("active" => 1));
			$sql = "SELECT * FROM $table WHERE id='$id' LIMIT 1";
		}

		
		$query = $this -> db -> query($sql);
		$results = $query -> result();

		$this -> session -> set_userdata('msg_success', $results[0] -> name . ' was enabled!');
		$this -> session -> set_flashdata('filter_datatable', $results[0] -> name);
		$this -> session -> set_userdata("link_id", "listing/" . $table);
		$this -> session -> set_userdata("linkSub", "settings/listing/" . $table);
		//Filter datatable
		redirect('settings_management');
	}

	public function disable($table = "", $id) {
		
		//If table is CCC_Store, disable CCC Store in drug_source and drug_destination
		if($table=="ccc_store_service_point"){
			
			
			$this -> db -> where('id', $id);
			$this -> db -> update('ccc_store_service_point', array("active" => 0));
			
			$sql = "SELECT * FROM ccc_store_service_point WHERE id='$id' LIMIT 1";
			//Get CCC Stores if they exist
			$ccc_stores = CCC_store_service_point::getAllActive();
			$this -> session -> set_userdata('ccc_store',$ccc_stores);
		}
		else{
			$this -> db -> where('id', $id);
			$this -> db -> update($table, array("active" => 0));
			$sql = "SELECT * FROM $table WHERE id='$id' LIMIT 1";
		}
		
		
		$query = $this -> db -> query($sql);
		$results = $query -> result();

		$this -> session -> set_userdata('msg_error', $results[0] -> name . ' was disabled!');
		$this -> session -> set_flashdata('filter_datatable', $results[0] -> name);
		$this -> session -> set_userdata("link_id", "listing/" . $table);
		$this -> session -> set_userdata("linkSub", "settings/listing/" . $table);
		//Filter datatable
		redirect('settings_management');
	}

	public function listing($table = "") {
		$columns = array("#", "Name", "Options");
		if($table=="transaction_type"){
			$columns = array("#", "Name", "Description","Effect","Options");
		}else if($table=="patient"){
			$columns = array("#","CCC NO","Patient Name","Options");
		}
		$access_level = $this -> session -> userdata('user_indicator');
		$tmpl = array('table_open' => '<table class="setting_table table table-bordered table-striped">');
		$this -> table -> set_template($tmpl);
		$this -> table -> set_heading($columns);
		$sql = "SELECT * FROM $table";
		//If table is CCC_Store, get ccc store from either drug_source or drug_destination
		if($table=="ccc_store_service_point"){
			$sql = "SELECT * FROM ccc_store_service_point";
		}else if($table=="patient"){
			$sql = "SELECT * FROM patient";
		}
		$query = $this -> db -> query($sql);
		$sources = $query -> result();

		foreach ($sources as $source) {

			if($table=="ccc_store_service_point"){
				$name = $source -> name;
				$name = str_replace("ccc_store_", "", $name);
			}else if($table=="patient"){
				$name = $source -> first_name;
				$name .= ' '.$source -> other_name;
				$name .= ' '.$source -> last_name;
				//$name =str_replace(" ","",$name);
				$name =strtoupper($name);
			}else if($table=="patient_status"){
				$name = $source -> Name;
			}else{
				$name = $source -> name;
			}

			if($table=="transaction_type"){
			  $array_param = array('id' => $source -> id, 'role' => 'button', 'class' => 'edit_user', 'data-toggle' => 'modal', 'name' => $name,'desc'=>$source->desc,'effect'=>$source->effect);
			}else{
                          $array_param = array('id' => $source -> id, 'role' => 'button', 'class' => 'edit_user', 'data-toggle' => 'modal', 'name' => $name);
			}

			$links = "";
			if($table=="patient"){
			   $links = "<a href='#' class='btn btn-danger btn-mini unmerge_patient' id='".$source -> id."'>unmerge</a>";
			   $checkbox = "<input type='checkbox' name='patients' class='patients' value='".$source -> id."' disabled/>";
               if ($source -> active == 1) {
              	   $links = "<a href='#' class='btn btn-success btn-mini merge_patient' id='".$source -> id."'>Merge</a>";
              	   $checkbox = "<input type='checkbox' name='patients' class='patients' value='".$source -> id."'/>";
               }
               $this -> table -> add_row("",$checkbox . "&nbsp;" .$source -> patient_number_ccc, $name, $links); 
			}else{
				if($table=="patient_status"){
					$active = $source -> Active;
				}else{
					$active = $source -> active;
				}
				if ($active == 1) {
					$links .= anchor('#edit_form', 'Edit', $array_param);
				}
				if ($access_level == "facility_administrator") {
					
					if ($active == 1) {
						$links .= " | ";
						$links .= anchor('settings/disable/' . $table . '/' . $source -> id, 'Disable', array('class' => 'disable_user'));
					} else {
						$links .= anchor('settings/enable/' . $table . '/' . $source -> id, 'Enable', array('class' => 'enable_user'));
					}
				}
				if($table=="transaction_type"){
				  $this -> table -> add_row($source -> id, $source -> name,$source -> desc,$source -> effect,$links); 
				}else{
	              $this -> table -> add_row($source -> id, $name, $links); 
				}
			}
		}
		$this -> session -> set_userdata("link_id", "listing/" . $table);
		$this -> session -> set_userdata("linkSub", "settings/listing/" . $table);

		$data['sources'] = $this -> table -> generate();
		$data['title'] = strtoupper($table);
		$data['banner_text'] = strtoupper($table);
		$data['table'] = $table;
		$data['link'] = $table;
		$actions = array(0 => array('Edit', 'edit'), 1 => array('Disable', 'disable'));
		$data['actions'] = $actions;
		$this -> base_params($data);
	}

	public function save($table = "") {
		$name = $this -> input -> post("source_name");
		//If adding new ccc_store, add CCC Stores in both drug_source and destination, then add ccc_store prefix
		if($table=="transaction_type"){
		  $desc = $this -> input -> post("desc");
		  $effect = $this -> input -> post("effect");
          $data_array= array(
          	            "name" => $name,
          	            "effect"=>$effect,
          	            "`desc`"=>$desc
          	            );
          $this -> db -> insert($table,$data_array);
		}else{
			$this -> db -> insert($table, array("name" => $name, "active" => 1));
		}
		
		$ccc_stores = CCC_store_service_point::getAllActive();
		$this -> session -> set_userdata('ccc_store',$ccc_stores);
		
		$this -> session -> set_userdata('message_counter', '1');
		$this -> session -> set_userdata('msg_success', $this -> input -> post('source_name') . ' was successfully Added!');
		$this -> session -> set_flashdata('filter_datatable', $this -> input -> post('source_name'));
		$this -> session -> set_userdata("link_id", "listing/" . $table);
		$this -> session -> set_userdata("linkSub", "settings/listing/" . $table);
		//Filter datatable
		redirect('settings_management');
	}

	public function update($table = "") {
		$id = $this -> input -> post("source_id");
		$name = $this -> input -> post("source_name");
		if($table=="transaction_type"){
		  $desc = $this -> input -> post("desc");
		  $effect = $this -> input -> post("effect");
          $data_array= array(
          	            "name" => $name,
          	            "effect"=>$effect,
          	            "`desc`"=>$desc
          	            );
		}else{
		 $data_array= array("name" => $name);
	    }
		$this -> db -> where('id', $id);
		$this -> db -> update($table,$data_array);
		$ccc_stores = CCC_store_service_point::getAllActive();
		$this -> session -> set_userdata('ccc_store',$ccc_stores);
		
		$this -> session -> set_userdata('msg_success', $this -> input -> post('source_name') . ' was Updated!');
		$this -> session -> set_flashdata('filter_datatable', $this -> input -> post('source_name'));
		$this -> session -> set_userdata("link_id", "listing/" . $table);
		$this -> session -> set_userdata("linkSub", "settings/listing/" . $table);
		//Filter datatable
		redirect('settings_management');
	}

	public function base_params($data) {
		$data['quick_link'] = "settings";
		$this -> load -> view("mysetting_v", $data);
	}

}
