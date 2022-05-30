<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Third_party_loan extends CI_Controller{
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
	    $result = $this->employee->employee_thrdpt_loans($this->company_id, $this->emp_id);
	    
	    if($result) {
	        echo json_encode(array("result" => "1", "third_pt_loans" => $result));
	        return false;
	    } else {
	        echo json_encode(array("result" => "0"));
	        return false;
	    }
	    
	    
	}
	
	public function amortization_schedule(){
		
		@$deductionId = $this->input->post('deductionId');
		@$principalAmt = $this->input->post('principalAmt');
		if(!$principalAmt){$principalAmt = 0;}
		$where = array(
				'deduction_id' => $deductionId
		);
		$select = array(
				'*'
		);
		$this->db->select($select);
		$this->db->where($where);
		$q1 = $this->db->get('amortization_schedule');
		$r1 = $q1->result();
		
		foreach($r1 as $r2):
			$amortId= $r2->amortization_schedule_id;
			$installment = $r2->principal + $r2->interest;
			$principal = $r2->principal;
			$interest = $r2->interest;
			$payroll_date = $r2->payroll_date;
			$principalAmt = $principalAmt - $principal;
			$indv[] = array ('installment' => $installment, 'principal' => $principal,'interest'=> $interest, 'payrollDate' => $payroll_date, 'loanBal' => $principalAmt);
			
		endforeach;
		
		echo json_encode($indv);
	}
}