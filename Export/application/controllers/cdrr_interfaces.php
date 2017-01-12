<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cdrr_Interfaces extends MY_Controller {

	function __construct() 
	{
		parent::__construct();
	}

	public function login() 
	{
        $data['content_view'] = 'cdrr/login_view';
       	$data['title'] = 'Cdrr | Login';
       	$data['banner_text'] = 'Cdrr Login';
		$this -> load -> view('template', $data);
	}
	public function listing() 
	{
        $data['content_view'] = 'cdrr/listing_view';
       	$data['title'] = 'Cdrr | Listing';
       	$data['banner_text'] = 'Cdrr Listing';
		$this -> load -> view('template', $data);
	}

}

/* End of file cdrr_interfaces.php */
/* Location: ./application/controllers/cdrr_interfaces.php */
