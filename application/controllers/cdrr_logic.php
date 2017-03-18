<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cdrr_Logic extends MY_Controller {
    
    var $nascop_url = "http://192.168.133.10/NASCOP/";
    var $escm_url = "http://portal.kemsa.co.ke/escm-api/";
    var $facility_code = "";
    var $default_url = "";

	function __construct() 
	{
		parent::__construct();
		//Load Logic Controller
		$this->load->library('../controllers/cdrr_dal');
        //Load CURL & Session
        $this->load->library('Curl');
        //Load Default Url
        $this->default_url = $this->get_url();
	}

	public function process_user($username,$password,$facility_code) 
	{   
        $curl = new Curl();
        $response = array();
        //Get Supplier
        $supplier = $this->get_supplier($facility_code);
        if($supplier == "kemsa")
        {
            //Use nascop url
            $url = $this -> default_url;
            $post = array("email" =>  $username ,"password" => $password);                                                                                                 
            $url = $this -> default_url . 'sync/user';
            $curl -> post($url,$post);
        }
        else
        {
            //Use escm url
            $curl -> setBasicAuthentication($username, $password);
            $curl -> setOpt(CURLOPT_RETURNTRANSFER, TRUE);
            $url = $this -> default_url . 'user/' . $username;
            $curl -> get($url);
        }
        //Handle Response
        if ($curl -> error) 
        {
            $response['error'] = TRUE;
            $response['content'] = array($curl -> error_code);
        }
        else
        {
            $response['error'] = FALSE;
            $response['content'] = json_decode($curl -> response,TRUE);
        }

        return json_encode($response);
	}

	public function get_cdrr($facilities) 
	{   
        $dal = new Cdrr_Dal;
        return $dal->get_cdrr($facilities);
	}

    public function get_supplier($facility_code = NULL)
    {
        //Get Supplier Based on Facility
        $facility = Facilities::getSupplier($facility_code);
        return strtolower($facility -> supplier -> name);
    }

    public function get_url()
    {
        //Get Supplier
        $supplier = $this->get_supplier();
        if($supplier == "kemsa")
        {
            $url = $this -> nascop_url;
        }
        else
        {
            $url = $this -> escm_url;
        }
        return $url;
    }
}

/* End of file cdrr_logic.php */
/* Location: ./application/controllers/cdrr_logic.php */
