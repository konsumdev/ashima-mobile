<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Payroll Deductions Model
 *
 * @category Model
 * @version 1.1
 * @author Jonathan Bangga <jonathanbangga@gmail.com>
 */

class Payroll_deductions_model extends CI_Model {

	/**
	 * Employee Contributions
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function employee_contributions($emp_id,$company_id){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("contributions");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * get the payroll period
	 * @param int $company_id
	 * @param int $payroll_group_id
	 */
	public function get_payroll_calendar($company_id,$payroll_date,$payroll_group_id)
	{
		$where = array(
			'pc.company_id' 	   	 => $company_id,
			'pc.first_payroll_date' => $payroll_date,
			'pg.payroll_group_id'	 => $payroll_group_id,
			'pc.status'	 	   	 => 'Active'
		);
		$this->db->where($where);
		$this->db->join("payroll_group AS pg","pg.period_type = pc.pay_schedule","LEFT");
		$q = $this->db->get('payroll_calendar AS pc');
		$result = $q->row();
		$q->free_result();
		if ($result) {
			$day = date('j',strtotime($result->first_payroll_date));
			
			if ($result->second_monthly == -1) {	// End of month
				$start = $result->first_semi_monthly;
				$end   = date('t',strtotime($day));
			} else {
				$start = $result->first_semi_monthly;
				$end   = $result->second_monthly;
			}
			
			if ($start < $day && $day <= $end) {
				$val['period'] = 2;
			} else {
				$val['period'] = 1;
			}
			
			return $val;
		}
	}
	
	/**
	 * Get other deductions
	 * @param int $company_id
	 */
	public function get_other_deductions($company_id)
	{
		$where = array(
			'comp_id' => $company_id,
			'view'	  => 'YES',
			'status'  => 'Active'
		);
		$this->edb->where($where);
		$q = $this->edb->get('deductions_other_deductions');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get employee other deduction
	 * @param array $where
	 */
	public function get_employee_other_deduction($where)
	{
		$this->edb->where($where);
		$q = $this->edb->get('employee_other_deductions');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get Other Deductions for employee single row 2.0
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function other_dd_emp_single($emp_id,$company_id,$deduction_id){
		$s = array(
			"*","ed.amount AS amount","ed.recurring AS recurring"
		);
		$this->db->select($s);
		$w = array(
			"ed.emp_id"=>$emp_id,
			"ed.company_id"=>$company_id,
			"ed.deduction_type_id"=>$deduction_id,
			"ed.status"=>"Active",
			"dd.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("deductions_other_deductions AS dd","dd.deductions_other_deductions_id = ed.deduction_type_id","left");
		$q = $this->db->get("employee_deductions AS ed");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Loans
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $per_page
	 * @param unknown_type $page
	 * @param unknown_type $sort_by
	 * @param unknown_type $q
	 */
	public function employee_loans($payroll_group_id,$company_id,$per_page, $page,$sort_by, $q){
		$konsum_key = konsum_key();
		
		$w = array(
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		
		if($q !==""){
			$this->db->where("
				(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$q."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
		}
		
		$this->edb->join("employee AS e","e.emp_id = ld.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("loan_type AS lt","lt.loan_type_id = ld.loan_type_id","LEFT");
		
		if($sort_by != ""){
			if($sort_by == "e.first_name"){
				$this->edb->order_by($sort_by,"ASC");
			}else{
				$this->db->order_by($sort_by,"ASC");
			}
		}else{
			$this->edb->order_by("e.first_name","ASC");
		}
		
		$q = $this->edb->get("loans_deductions AS ld",$per_page, $page);
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Loans Counter
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $sort_by
	 * @param unknown_type $q
	 */
	public function employee_loans_counter($payroll_group_id,$company_id,$sort_by, $q){
		$konsum_key = konsum_key();
		$s = array(
			"COUNT(ld.emp_id) AS total"
		);
		$this->db->select($s);
		
		$w = array(
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		
		if($q !==""){
			$this->db->where("
				(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$q."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
		}
		
		$this->edb->join("employee AS e","e.emp_id = ld.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		
		if($sort_by != ""){
			if($sort_by == "e.first_name"){
				$this->edb->order_by($sort_by,"ASC");
			}else{
				$this->db->order_by($sort_by,"ASC");
			}
		}else{
			$this->edb->order_by("e.first_name","ASC");
		}
		
		$q = $this->edb->get("loans_deductions AS ld");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * get deduction payroll group
	 * @param int $company_id
	 * @param int $payroll_group_id
	 */
	public function get_deduction_payroll_group($company_id,$payroll_group_id)
	{
		$where = array(
			'company_id' => $company_id,
			'status'	 => 'Active'
		);
		$this->db->where($where);
		$q = $this->db->get('priority_of_deductions');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get employee earnings
	 * @param int $company_id
	 * @param int $employee_id
	 */
	public function get_employee_earnings($company_id,$employee_id)
	{
		$where = array(
			'company_id' => $company_id,
			'emp_id'	 => $employee_id
		);
		$this->db->where($where);
		$q = $this->db->get('employee_earnings');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get withholding tax method
	 * @param int $company_id
	 */
	public function get_withholding_tax_method($company_id)
	{
		$this->db->where('company_id',$company_id);
		$q = $this->db->get('withholding_tax_method');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * get the settings of the tax
	 * @param int $company_id
	 */
	public function get_tax_settings($company_id)
	{
		$this->edb->where('company_id',$company_id);
		$q = $this->edb->get('withholding_tax_settings');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result->compensation_type : false;
	}
	
	/**
	 * get payroll info
	 * @param int $company_id
	 * @param int $employee_id
	 */
	public function get_payroll_info($company_id,$employee_id)
	{
		$where = array(
			'company_id'  => $company_id,
			'emp_id' 	  => $employee_id,
			'employee_status'   => 'Active'
		);
		$this->edb->where($where);
		$q = $this->edb->get('employee_payroll_information');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
}

/* End of file Payroll_deductions_model.php */
/* Location: ./application/models/paycheck/Payroll_deductions_model.php */