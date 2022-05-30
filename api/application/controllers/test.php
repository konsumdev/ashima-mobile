<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Test extends CI_Controller{
	
	public function __construct(){
	   parent::__construct();
	   $this->load->library('session');
	   
	}

	public function index(){
        echo "API test page.";
    }

    public function server_time(){
        echo "Server time: ";
        p(date("Y-m-d H:i:s"));
        p(date("Y-m-d h:i A"));
    }
}