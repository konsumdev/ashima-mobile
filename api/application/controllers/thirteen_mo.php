<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Thirteen_mo extends CI_Controller{
	var $verify;
		
	public function __construct(){
	   parent::__construct();
	   $this->load->model('employee_model','employee');
	  
	   $this->emp_id = $this->session->userdata('emp_id');
	   $this->company_id =$this->employee->check_company_id($this->emp_id);  
	   
	}	
	public function index(){
		
	    $result = $this->employee->get_employee_thirteenth_month($this->emp_id,$this->company_id);
	    
	    if($result) {
	        echo json_encode(array("result" => "1", "thirteenth_month" => $result));
	        return false;
	    } else {
	        echo json_encode(array("result" => "0"));
	        return false;
	    }
		
	}
}