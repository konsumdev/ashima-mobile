<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Documents extends CI_Controller{
    
	public function __construct(){
	   parent::__construct();
	   $this->load->model('employee_model','employee');
	  
	   $this->emp_id = $this->session->userdata('emp_id');
	   $this->company_id =$this->employee->check_company_id($this->emp_id);
	}	
	
	public function index() {
	    $page = $this->input->post('page');
	    $limit = $this->input->post('limit');
	    
	    $this->per_page = 10;
	    
	    $get_employee_documents_details_list = $this->employee->get_employee_documents_details_list($this->emp_id,$this->company_id,false,(($page-1) * $this->per_page),$limit);
	    $total = ceil($this->employee->get_employee_documents_details_list($this->emp_id,$this->company_id,true) / 10);
	    
	    if($get_employee_documents_details_list) {
	        echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "total" => $total,"list" => $get_employee_documents_details_list));
	        return false;
	    } else {
	        echo json_encode(array("result" => "0"));
	        return false;
	    }
	}
}