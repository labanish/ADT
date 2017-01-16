<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cdrr_Api extends MY_Controller {

	function __construct() 
	{
		parent::__construct();
		//Load Logic Controller
		$this->load->library('../controllers/cdrr_logic');
	}

	public function auth_user() 
	{
        $username = $this->input->post('username',TRUE);
        $password = $this->input->post('password',TRUE);
        $facility_code = $this->input->post('facility_code',TRUE);

        //Process User
        $logic = new Cdrr_Logic;
        $user = $logic->process_user($username,$password,$facility_code);
        return $user;
	}

	public function get_cdrr($facilities) 
	{
        //Get Cdrrs
        $dal = new Cdrr_Dal;
        $cdrrs = $dal->get_cdrr($facilities);
        return $cdrrs;
	}

}

/* End of file cdrr_api.php */
/* Location: ./application/controllers/cdrr_api.php */
