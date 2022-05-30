<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Employee Cash Advance Model
 *
 * @category Model
 * @version 1.0
 * @author reyneill
 *
 */
class Emp_cash_advance_model extends CI_Model {
	public function get_payment_terms($comp_id){
		$where = array(
			"cadpts.company_id" => $comp_id,
			"cadpts.status" => "Active"
		);
		$this->db->where($where);
		$this->db->join("cash_advance_payment_terms AS cadpt","cadpt.cash_advance_payment_terms_id = cadpts.cash_advance_payment_terms_id","LEFT");
		$query = $this->db->get("cash_advance_payment_terms_selected AS cadpts");
		$result = $query->result();
		
		return ($result) ? $result : false;
	}
	
	public function get_payment_schedule($comp_id){
		$where = array(
			"capss.company_id" => $comp_id,
			"capss.status" => "Active"
		);
		$this->db->where($where);
		$this->db->join("cash_advance_payment_schedule AS caps","caps.cash_advance_payment_schedule_id = capss.cash_advance_payment_schedule_id","LEFT");
		$query = $this->db->get("cash_advance_payment_schedule_selected AS capss");
		$result = $query->result();
		
		return ($result) ? $result : false;
	}
	
	public function amount_limit($comp_id){
		$select = array(
			"limit"
		);
		$where = array(
			"company_id" => $comp_id
		);
		$this->db->select($select);
		$this->db->where($where);
		$query = $this->db->get("cash_advance_settings");
		$row = $query->row();
		
		return ($row) ? $row->limit : false;
	}
	
	public function emp_loans_list($emp_id){
		$where = array(
			"emp_id" => $emp_id
		);
		$this->db->where($where);
		$this->db->order_by("application_date","DESC");
		$query = $this->db->get("cash_advance");
		$result = $query->result();
		
		return ($result) ? $result : false;
	}
	
	public function emp_payment_terms($id){
		$this->db->where("cash_advance_payment_terms_id",$id);
		$query = $this->db->get("cash_advance_payment_terms");
		$row = $query->row();
		
		return ($row) ? $row : false;
	}
	
	public function emp_payment_schedule($id){
		$this->db->where("cash_advance_payment_schedule_id",$id);
		$query = $this->db->get("cash_advance_payment_schedule");
		$row = $query->row();
		
		return ($row) ? $row : false;
	}
	
	public function get_employee_fullname($emp_id,$comp_id){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$comp_id
		);
		$this->edb->where($w);
		$q = $this->edb->get("employee");
		$row = $q->row();
		return ($q->num_rows() > 0) ? ucwords($row->first_name)." ".ucwords($row->last_name) : FALSE ;
	}
	
	public function get_approver_name($emp_id,$comp_id){
		$w = array(
			'epi.emp_id'=>$emp_id,
			'epi.company_id'=>$comp_id
		);
		$this->edb->where($w);
		$this->edb->join("employee AS e","epi.leave_approval_grp = e.emp_id","left");
		$this->edb->join("accounts AS a","e.account_id = a.account_id","left");
		$q = $this->edb->get("employee_payroll_information AS epi");
		return ($q) ? $q->row() : FALSE ;
	}
	
	public function check_cash_advance_setting($comp_id){
		$where = array(
			"company_id" => $comp_id,
			"enable_disable" => "Enable"
		);
		$this->db->where($where);
		$query = $this->db->get("cash_advance_settings");
		$row = $query->row();
		
		return ($row) ? $row : false;
	}
}