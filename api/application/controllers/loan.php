<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Loan extends CI_Controller{
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
		
		$this->db->where('emp_id', $this->emp_id);
		$this->db->join('loan_type AS lt', 'lt.loan_type_id = el.loan_type_id','LEFT');
		$q= $this->db->get('employee_loans as el');
		$result = $q->result();
		echo json_encode($result);
		
	}
}