<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Withholding_tax extends CI_Controller{
	var $verify;
		
	public function __construct(){
	   parent::__construct();	
	   $this->load->model('employee_model','employee');
	  
	   $this->emp_id = $this->session->userdata('emp_id');
	   $this->company_id =$this->employee->check_company_id($this->emp_id);  
	   
	}	
	
	public function index(){
	    $page = $this->input->post('page');
	    $limit = $this->input->post('limit');
	    
	    $this->per_page = 10;
		
	    $result = $this->employee->get_emp_contributions($this->company_id,$this->emp_id,false,"tax",(($page-1) * $this->per_page),$limit);
	    $total = ceil($this->employee->get_emp_contributions($this->company_id,$this->emp_id,true,"tax") / 10);
	    
	    if($result) {
	        echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "total" => $total,"list" => $result));
	        return false;
	    } else {
	        echo json_encode(array("result" => "0"));
	        return false;
	    }
		
	}
	
	public function withholding_tax_fixed(){
	    $page = $this->input->post('page');
	    $limit = $this->input->post('limit');
	    
	    $this->per_page = 10;
	    
	    $result = $this->employee->get_wt_fixed($this->company_id,$this->emp_id,false,(($page-1) * $this->per_page),$limit);
	    $total = ceil($this->employee->get_wt_fixed($this->company_id,$this->emp_id,true) / 10);
	    
	    if($result) {
	        echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "total" => $total,"list" => $result));
	        return false;
	    } else {
	        echo json_encode(array("result" => "0"));
	        return false;
	    }
	    
	}
}