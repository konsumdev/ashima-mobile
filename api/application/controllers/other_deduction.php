<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class other_deduction extends CI_Controller{
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
		
		$select = array(
				'dod.name',
				'ed.amount',
				'dod.recurring',
				'ed.type',
				'dod.payroll_schedule',
				'ed.start_date',
				'ed.end_date',
				'ed.no_of_occurence'
		);
		
		$where = array(
				'ed.emp_id' => $this->emp_id,
				'ed.company_id' => $this->company_id,
				'ed.status' => 'Active'
		);
		
		$this->db->select($select);
		$this->db->where($where);
		$this->db->join('deductions_other_deductions AS dod', 'dod.deductions_other_deductions_id = ed.deduction_type_id','LEFT');
		$q= $this->db->get('employee_deductions AS ed');
		$resultData = $q->result();
		$resultRows = $q->num_rows();
		$result = array($resultData,$resultRows);
		echo json_encode($result);
	}

}