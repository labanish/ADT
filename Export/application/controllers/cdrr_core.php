<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cdrr_Core extends MY_Controller {

	function __construct() 
	{
		parent::__construct();
		//Load Api
		$this->load->library('../controllers/cdrr_api');
	}

	public function login() 
	{
        //Add Facility Code to POST
        $_POST['facility_code'] = $this->session->userdata("facility_code");

        //Check Connection
        $test_url = "http://www.google.com";
        $online = $this->_is_online($test_url);

        if($online)
        {
        	//Use API to Authenticate User
	        $api = new Cdrr_Api;
	        $response = json_decode($api -> auth_user(),TRUE);
        }
        else
        {
        	//Use Database to Autheticate User
        }

        //Process User
        if(!$response['error'])
        {      
            //Localize User Settings
            $settings = $this->localize($response['content']);
            //Go to Listing 
            redirect("cdrr/listing");
        }
        else{
        	//Go to Login Interface
        	redirect("cdrr/login");
        }
	}

	public function listing()
	{
		$cdrrs = array();
		//Get Facility Id's from the environment sessions.
        $facilities = json_decode($this->session->userdata("api_facility"),TRUE);
		//Use API to get cdrrs
		$api = new Cdrr_Api;
        $response = $api -> get_cdrr($facilities);

        foreach($response as $counter => $cdrr)
        {  
        	foreach ($cdrr as $key => $value) {
                $cdrrs[$counter][] = $value;
            }
        }
        $data['aaData'] = $cdrrs;
        echo json_encode($data);
	}

	public function localize($data)
	{
		$settings = array();
		$need_to_save = array('id','name','ownUser_facility');
		foreach ($data as $index => $values) {
			foreach ($values as $key => $value) {
				if(in_array($key, $need_to_save))
				{   
					//Format User Facilities 
					if($key == "ownUser_facility")
					{
	                    $key = "facility";
	                    $facilities = $value;
	                    foreach ($facilities as $facility) {
	                    	$temp[]=$facility['facility_id'];
	                    }
	                    $value = json_encode($temp);
					}
	                $settings['api_'.$key] = $value;
	                //Save on Session
	                $this->session->set_userdata('api_'.$key,$value);
				}
			}
		}
		return $settings;
	}

	private function _is_online($url = '')
	{
	    $connection = get_headers($url, 1);
	    $status = array();
        preg_match('/HTTP\/.* ([0-9]+) .*/', $connection[0] , $status);
        $is_online = TRUE;

	    if ($status[1] == '404')
	    {
	        $is_online = FALSE;
	    }
	    return $is_online;
	}

}

/* End of file cdrr_core.php */
/* Location: ./application/controllers/cdrr_core.php */
