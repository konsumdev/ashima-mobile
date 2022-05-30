<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Approval Group Model
 *
 * @category Model
 * @version 1.0
 * @author reyneill
 *
 */
class Approval_group_model extends CI_Model {
	
	
	public function get_approver_name_shifts($emp_id,$company_id){
		$this->db->where("emp_id",$emp_id);
		$sql = $this->db->get("employee_payroll_information");
		$row = $sql->row();
		if($row){
			$shifts_approver_id = $row->shedule_request_approval_grp;
			$w = array(
					"ag.company_id"=>$company_id,
					"ag.approval_groups_via_groups_id"=>$shifts_approver_id,
					"ap.name" => "Shifts"
			);
			$this->db->where($w);
			$this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->edb->join("approval_process AS ap","ag.approval_process_id = ap.approval_process_id","LEFT");
			$this->db->order_by("ag.level","ASC");
			$q = $this->edb->get("approval_groups AS ag");
			$r = $q->result();
				
			return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	public function get_approver_name_shifts_desc($emp_id,$company_id){
		$this->db->where("emp_id",$emp_id);
		$sql = $this->db->get("employee_payroll_information");
		$row = $sql->row();
		if($row){
			$shifts_approver_id = $row->shedule_request_approval_grp;
			$w = array(
					"ag.company_id"=>$company_id,
					"ag.approval_groups_via_groups_id"=>$shifts_approver_id,
					"ap.name" => "Shifts"
			);
			$this->db->where($w);
			$this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->edb->join("approval_process AS ap","ag.approval_process_id = ap.approval_process_id","LEFT");
			$this->db->order_by("ag.level","DESC");
			$q = $this->edb->get("approval_groups AS ag");
			$r = $q->result();
				
			return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Get Approver Name Overtime
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function get_approver_name_overtime($emp_id,$company_id){
		$this->db->where("emp_id",$emp_id);
		$sql = $this->db->get("employee_payroll_information");
		$row = $sql->row();
		if($row){
			$overtime_approver_id = $row->overtime_approval_grp;
			$w = array(
				"ag.company_id"=>$company_id,
				"ag.approval_groups_via_groups_id"=>$overtime_approver_id,
				"ap.name" => "Overtime"
			);
			$this->db->where($w);
			$this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->edb->join("approval_process AS ap","ag.approval_process_id = ap.approval_process_id","LEFT");
			$this->db->order_by("ag.level","ASC");
			$q = $this->edb->get("approval_groups AS ag");
			$r = $q->result();
			
			return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Get Approver Name Overtime
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function get_approver_name_timein($emp_id,$company_id,$workflow_type){
		$this->db->where("emp_id",$emp_id);
		$sql = $this->db->get("employee_payroll_information");
		$row = $sql->row();
		if($row){
			$overtime_approver_id = $row->overtime_approval_grp;
			$w = array(
					"ag.company_id"=>$company_id,
					"ag.approval_groups_via_groups_id"=>$overtime_approver_id,
					"ap.name" => $workflow_type
			);
			$this->db->where($w);
			$this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->edb->join("approval_process AS ap","ag.approval_process_id = ap.approval_process_id","LEFT");
			$this->db->order_by("ag.level","ASC");
			$q = $this->edb->get("approval_groups AS ag");
			$r = $q->result();
				
			return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	
	public function get_approver_name_overtime_desc($emp_id,$company_id){
		$this->db->where("emp_id",$emp_id);
		$sql = $this->db->get("employee_payroll_information");
		$row = $sql->row();
		if($row){
			$overtime_approver_id = $row->overtime_approval_grp;
			$w = array(
				"ag.company_id"=>$company_id,
				"ag.approval_groups_via_groups_id"=>$overtime_approver_id,
				"ap.name" => "Overtime"
			);
			$this->db->where($w);
			$this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->edb->join("approval_process AS ap","ag.approval_process_id = ap.approval_process_id","LEFT");
			$this->db->order_by("ag.level","DESC");
			$q = $this->edb->get("approval_groups AS ag");
			$r = $q->result();
			
			return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check Total Level for Overtime
	 * @param unknown_type $token_level
	 * @param unknown_type $overtime_id
	 */
	public function check_token_level_for_overtime($token_level,$overtime_id){
		$w = array(
			"token_level"=>$token_level,
			"overtime_id"=>$overtime_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_overtime");
		$r = $q->row();
		return ($r) ? TRUE : FALSE ;
	}
	/**
	 * Check Total Level for request change work schedule
	 * @param unknown_type $token_level
	 * @param unknown_type $overtime_id
	 */
	public function check_token_level_for_work_schedule($token_level,$work_schedule_id){
		$w = array(
				"token_level"=>$token_level,
				"employee_work_schedule_application_id"=>$work_schedule_id,
				"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_work_schedule");
		$r = $q->row();
		return ($r) ? TRUE : FALSE ;
	}
	
	/**
	 * Check Overtime Level Status
	 * @param unknown_type $val
	 */
	public function check_overtime_lvl_stats($val){
		$w = array(
			"overtime_id"=>$val
		);
		$this->db->where($w);
		$q = $this->db->get("approval_overtime");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Employee Fullname
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 */
	public function get_employee_fullname($emp_id,$comp_id){
		$w = array(
			"e.emp_id"=>$emp_id,
			"e.company_id"=>$comp_id
		);
		$this->db->where($w);
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$q = $this->edb->get("employee AS e");
		$row = $q->row();
		return ($q->num_rows() > 0) ? ucwords($row->first_name)." ".ucwords($row->last_name) : FALSE ;
	}
	
	/**
	 * Get Leave Approver Information
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function get_approver_name_leave($emp_id,$company_id){
		$this->db->where("emp_id",$emp_id);
		$sql = $this->db->get("employee_payroll_information");
		$row = $sql->row();
		if($row){
			$leave_approval_grp = $row->leave_approval_grp;
			$w = array(
				"ag.company_id"=>$company_id,
				"ag.approval_groups_via_groups_id"=>$leave_approval_grp,
				"ap.name"=> "Leave"
					
					
			);
			$this->db->where($w);
			$this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->edb->join("approval_process AS ap","ag.approval_process_id = ap.approval_process_id","LEFT");
			$this->db->order_by("ag.level","ASC");
			$q = $this->edb->get("approval_groups AS ag");
			$r = $q->result();
			return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	/**
	 * Get Leave Approver Information
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function get_approver_name_leave_desc($emp_id,$company_id){
		$this->db->where("emp_id",$emp_id);
		$sql = $this->db->get("employee_payroll_information");
		$row = $sql->row();
		if($row){
			$leave_approval_grp = $row->leave_approval_grp;
			$w = array(
					"ag.company_id"=>$company_id,
					"ag.approval_groups_via_groups_id"=>$leave_approval_grp,
					"ap.name"=> "Leave"
			);
			$this->db->where($w);
			$this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->edb->join("approval_process AS ap","ag.approval_process_id = ap.approval_process_id","LEFT");
			$this->db->order_by("ag.level","DESC");
			$q = $this->edb->get("approval_groups AS ag");
			$r = $q->result();
			return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check Leave Level Status
	 * @param unknown_type $val
	 */
	public function check_leave_lvl_stats($val){
		$w = array(
			"leave_id"=>$val
		);
		$this->db->where($w);
		$q = $this->db->get("approval_leave");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	/**
	 * Timein Information
	 * @param unknown_type $val
	 */
	public function timein_information($val){
		$select = array(
				"*",
				"et.emp_id AS emp_id",
				"et.comp_id AS company_id"
		);
		$w = array(
				"et.employee_time_in_id" => $val,
				"et.status" => "Active"
		);
		$this->edb->select($select);
		$this->edb->where($w);
		$this->edb->join("approval_time_in AS at","et.approval_time_in_id = at.approval_time_in_id","LEFT");
		$this->edb->join("employee AS e","e.emp_id = et.emp_id","LEFT");
		$q = $this->edb->get("employee_time_in AS et");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
		
	}
	/**
	 * Leave Information
	 * @param unknown_type $val
	 */
	public function leave_information($val){
		$select = array(
			"*",
			"lt.leave_type AS leave_type",
			"el.emp_id AS emp_id",
			"el.company_id AS company_id"
		);
		$w = array(
			"el.employee_leaves_application_id" => $val,
			"el.status" => "Active"
		);
		$this->edb->select($select);
		$this->edb->where($w);
		$this->edb->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
		$this->edb->join("approval_leave AS al","el.employee_leaves_application_id = al.leave_id","LEFT");
		$this->edb->join("employee AS e","e.emp_id = el.emp_id","LEFT");
		$q = $this->edb->get("employee_leaves_application AS el");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	/**
	 * Overtime Information
	 * @param unknown_type $val
	 */
	public function overtime_information($val){
		$w = array(
				"ot.overtime_id"=>$val,
				"ot.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("approval_overtime AS ao","ot.overtime_id = ao.overtime_id","LEFT");
		$q = $this->db->get("employee_overtime_application AS ot");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Total Level for Leave
	 * @param unknown_type $token_level
	 * @param unknown_type $leave_id
	 */
	public function check_token_level_for_leave($token_level,$leave_id){
		$w = array(
			"token_level"=>$token_level,
			"leave_id"=>$leave_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_leave");
		$r = $q->row();
		return ($r) ? TRUE : FALSE ;
	}
	
// 	/**
// 	 * Get Time In Approver Information
// 	 * @param unknown_type $emp_id
// 	 * @param unknown_type $company_id
// 	 */
// 	public function get_approver_name_timein($emp_id,$company_id){
// 		$this->db->where("emp_id",$emp_id);
// 		$sql = $this->db->get("employee_payroll_information");
// 		$row = $sql->row();
// 		if($row){
// 			$eBundy_approval_grp = $row->eBundy_approval_grp;
// 			$w = array(
// 				"ag.company_id"=>$company_id,
// 				"ag.approval_groups_via_groups_id"=>$eBundy_approval_grp
// 			);
// 			$this->db->where($w);
// 			$this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
// 			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
// 			$this->db->order_by("ag.level","ASC");
// 			$q = $this->edb->get("approval_groups AS ag");
// 			$r = $q->result();
// 			return ($r) ? $r : FALSE ;
// 		}else{
// 			return FALSE;
// 		}
// 	}
	/**
	 * Get Time In CHANGE_LOGS Approver Information
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function get_approver_name_timein_change_logs($emp_id,$company_id){
		$this->db->where("emp_id",$emp_id);
		$sql = $this->db->get("employee_payroll_information");
		$row = $sql->row();
		if($row){
			$eBundy_approval_grp = $row->attendance_adjustment_approval_grp;
			$w = array(
					"ag.company_id"=>$company_id,
					"ag.approval_groups_via_groups_id"=>$eBundy_approval_grp,
					"ap.name" => "Timesheet Adjustment"
			);
			$this->db->where($w);
			
			$this->edb->join("approval_groups_via_groups AS agg","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->edb->join("approval_process AS ap","ag.approval_process_id = ap.approval_process_id","LEFT");
			$this->db->order_by("ag.level","ASC");
			$q = $this->edb->get("approval_groups AS ag");
			$r = $q->result();
			return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	public function get_approver_name_timein_location($emp_id,$company_id){
		$this->db->where("emp_id",$emp_id);
		$sql = $this->db->get("employee_payroll_information");
		$row = $sql->row();
		if($row){
			$eBundy_approval_grp = $row->location_base_login_approval_grp;
			$w = array(
					"ag.company_id"=>$company_id,
					"ag.approval_groups_via_groups_id"=>$eBundy_approval_grp,
					"ap.name" => "Mobile Clock-in"
			);
			$this->db->where($w);
			$this->edb->join("approval_groups_via_groups AS agg","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->edb->join("approval_process AS ap","ag.approval_process_id = ap.approval_process_id","LEFT");
			$this->db->order_by("ag.level","ASC");
			$q = $this->edb->get("approval_groups AS ag");
			$r = $q->result();
			
			return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	public function get_approver_name_timein_add_logs($emp_id,$company_id){
		$this->db->where("emp_id",$emp_id);
		$sql = $this->db->get("employee_payroll_information");
		$row = $sql->row();
		if($row){
			$eBundy_approval_grp = $row->add_logs_approval_grp;
			$w = array(
					"ag.company_id"=>$company_id,
					"ag.approval_groups_via_groups_id"=>$eBundy_approval_grp,
					"ap.name" => "Add Timesheet"
			);
			$this->db->where($w);
			$this->edb->join("approval_groups_via_groups AS agg","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->edb->join("approval_process AS ap","ag.approval_process_id = ap.approval_process_id","LEFT");
			$this->db->order_by("ag.level","ASC");
			$q = $this->edb->get("approval_groups AS ag");
			$r = $q->result();
			return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check Time In Level Status
	 * @param unknown_type $val
	 */
	public function check_timein_lvl_stats($val){
		$w = array(
			"time_in_id"=>$val
		);
		$this->db->where($w);
		$this->db->order_by("approval_time_in_id", "DESC");
		$q = $this->db->get("approval_time_in");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Total Level for Time In
	 * @param unknown_type $token_level
	 * @param unknown_type $time_in
	 */
	public function check_token_level_for_timein($token_level,$time_in){
		$w = array(
			"token_level"=>$token_level,
			"time_in_id"=>$time_in,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_time_in");
		$r = $q->row();
		return ($r) ? TRUE : FALSE ;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $timein_id
	 */
	public function check_timein_info($timein_id){
		$w = array(
			"employee_time_in_id"=>$timein_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("employee_time_in");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Approval Groups
	 * @param unknown_type $time_in_id
	 */
	public function check_approver_group($time_in_id){
		$w = array(
			"eti.employee_time_in_id"=>$time_in_id
		);
		$this->db->where($w);
		#$this->db->join("approval_process AS ap","ep.eBundy_approval_grp = ap.approval_process_id","LEFT");
		$this->db->join("approval_groups_via_groups AS ap","ep.eBundy_approval_grp = ap.approval_groups_via_groups_id","LEFT");
		$this->db->join("approval_groups AS ag","ap.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
		$this->db->join("employee_time_in AS eti","eti.emp_id = ep.emp_id","LEFT");
		$this->db->order_by("ag.level","DESC");
		$q = $this->db->get("employee_payroll_information AS ep","1");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check HR Approver
	 * @param unknown_type $time_in_id
	 */
	public function check_hr_approver($time_in_id){
		$w = array(
			"eti.employee_time_in_id"=>$time_in_id,
			"ag.include_hr_confirmation"=>1
		);
		$this->db->where($w);
		#$this->db->join("approval_process AS ap","ep.eBundy_approval_grp = ap.approval_process_id","LEFT");
		$this->db->join("approval_groups_via_groups AS ap","ep.eBundy_approval_grp = ap.approval_groups_via_groups_id","LEFT");
		$this->db->join("approval_groups AS ag","ap.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
		$this->db->join("employee_time_in AS eti","eti.emp_id = ep.emp_id","LEFT");
		$q = $this->db->get("employee_payroll_information AS ep");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Employee Information
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 */
	public function get_employee_information($emp_id,$comp_id){
		$w = array(
			"e.emp_id"=>$emp_id,
			"e.company_id"=>$comp_id
		);
		$this->db->where($w);
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$q = $this->edb->get("employee AS e");
		$row = $q->row();
		return ($row) ? $row : FALSE ;
	}
	
	/**
	 * Check Employee Twitter Account
	 * @param unknown_type $account_id
	 */
	public function check_twitter_acount($account_id){
		$w = array(
			"account_id"=>$account_id
		);
		$this->db->where($w);
		$q = $this->db->get("social_media_accounts");
		$r = $q->row();
		if($r){
			return ($r->twitter != "") ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check HR Twitter Account
	 * @param unknown_type $payroll_system_account_id
	 */
	public function check_hr_twitter_acount($payroll_system_account_id){
		$w = array(
			"psa_id"=>$payroll_system_account_id
		);
		$this->db->where($w);
		$q = $this->db->get("social_media_accounts");
		$r = $q->row();
		if($r){
			$a = $r->twitter_url;
			if($a){
				$x = explode("/", $a);
				return $x[1];
			}else{
				return FALSE;
			}
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check if employee is an approver of a specific approval group
	 * @param unknown $process
	 * @param unknown $emp_id
	 * @return boolean
	 */
	public function check_if_employee_is_approver($process, $emp_id){
		switch($process){
			case 'timein':
				$process_id = 5;
				break;
			case 'leave':
				$process_id = 3;
				break;
			case 'overtime':
				$process_id = 2;
				break;
			case 'expense':
				$process_id = 4;
				break;
		}
		
		$where = array(
				'approval_process_id' =>	$process_id,
				'emp_id'			  =>	$emp_id
		);
		$this->db->where($where);
		$q = $this->db->get('approval_groups');
		$r = $q->row();
		return ($r)? TRUE: FALSE;
	}
	
	/**
	 * schedule Information
	 * @param unknown_type $val
	 */
	public function schedule_information($val){
	
		$w = array(
				"ews.employee_work_schedule_application_id" => $val,
				"ews.status" => "Active"
		);
	
		$this->edb->where($w);
		$this->edb->join("approval_work_schedule AS at","ews.employee_work_schedule_application_id = at.employee_work_schedule_application_id","LEFT");
		$this->edb->join("employee AS e","e.emp_id = ews.emp_id","LEFT");
		$q = $this->edb->get("employee_work_schedule_application AS ews");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	
	}
	

	/**
	 * check if employee leave application is pending
	 * @param unknown $emp_leave_id
	 * @param unknown $company_id
	 */
	public function is_emp_shift_pending($emp_shift_id,$company_id){
		$where = array(
				"employee_work_schedule_application_id" => $emp_shift_id,
				"company_id" => $company_id,
				"employee_work_schedule_status" => "pending"
		);
		$this->db->where($where);
		$query = $this->db->get("employee_work_schedule_application");
		$row = $query->row();
			
		return ($row) ? true : false;
	}
	
	/**
	 * Check Total Level for Split Time In
	 * @param unknown_type $token_level
	 * @param unknown_type $time_in
	 */
	public function check_token_level_for_split_timein($token_level,$time_in){
		$w = array(
				"token_level"=>$token_level,
				"split_time_in_id"=>$time_in,
				"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_time_in");
		$r = $q->row();
		return ($r) ? TRUE : FALSE ;
	}
	
	
	/**
	 * Split Timein Information
	 * @param unknown_type $val
	 */
	public function split_timein_information($val){
		$select = array(
				"*",
				"sbti.emp_id AS emp_id",
				"sbti.comp_id AS company_id"
		);
		$w = array(
				"sbti.schedule_blocks_time_in_id" => $val,
				"sbti.status" => "Active"
		);
		$this->edb->select($select);
		$this->edb->where($w);
		$this->edb->join("approval_time_in AS at","sbti.approval_time_in_id = at.approval_time_in_id","LEFT");
		$this->edb->join("employee AS e","e.emp_id = sbti.emp_id","LEFT");
		$q = $this->edb->get("schedule_blocks_time_in AS sbti");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	
	}
	
	public function get_approval_group_via_groups_owner($approval_process_id, $company_id, $approval_groups_via_groups_id,$emp_id) {
		$where = array(
				'approval_process_id' => $approval_process_id,
				'company_id' => $company_id,
				'approval_groups_via_groups_id' => $approval_groups_via_groups_id,
				'emp_id' => $emp_id
		);
	
		$this->db->where($where);
		$q = $this->db->get('approval_groups');
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
}