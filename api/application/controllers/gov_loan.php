<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Gov_loan extends CI_Controller{
	var $verify;
		
	public function __construct(){
	   parent::__construct();
	   $this->load->model('employee_model','employee');
	  
	   $this->emp_id = $this->session->userdata('emp_id');
	   $this->company_id =$this->employee->check_company_id($this->emp_id);  
	   
	}	
	public function index(){		
		#$loan = $this->employee->check_employee_government_loans($this->emp_id,$this->company_id, "", "");
	    $result = $this->employee->employee_gov_loans($this->company_id, $this->emp_id);

	    if($result) {
	        echo json_encode(array("result" => "1", "gov_loan" => $result));
	        return false;
	    } else {
	        echo json_encode(array("result" => "0"));
	        return false;
	    }
	}

}