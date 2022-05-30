<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Deminimis extends CI_Controller{
	var $verify;
		
	public function __construct(){
	   parent::__construct();	
	   $this->load->model('konsumglobal_jmodel','jmodel');
	   $this->load->model('employee_model','employee');
	   $this->load->model('approval_model','approval');
	   $this->load->model('approval_group_model','agm');
	   
	  // $this->company_info = whose_company();
	  
	   $this->emp_id = $this->session->userdata('emp_id');
	   $this->company_id =$this->employee->check_company_id($this->emp_id);  
	   
	}	
	public function index(){
	    $result = $this->employee->de_minimis_settings($this->emp_id,$this->company_id, "", "");
	    
	    if($result) {
	        echo json_encode(array("result" => "1", "de_minimis" => $result));
	        return false;
	    } else {
	        echo json_encode(array("result" => "0"));
	        return false;
	    }
	    
	    
	}
}