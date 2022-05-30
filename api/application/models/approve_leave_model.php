<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Approve LEave model for approving overtime , leaves , loans
 *
 * @category Model
 * @version 1.0
 * @author Christopher Cuizon <christophercuizons@gmail.com>
 */
class Approve_leave_model extends CI_Model {

	public function search_leave($company_id, $emp_id, $limit = 10, $start = 0, $search = "", $sort_by = "", $sort = "ASC"){
		if(is_numeric($company_id)){
			$konsum_key = konsum_key();
			$filter = $this->not_in_search_leave($company_id, $emp_id, $search);
			
			$start = intval($start);
			$limit = intval($limit);
			$sort_array = array(
				"first_name",
				"pg_name",
				"date_filed",
				"leave_type",
				"date_start",
				"total_leave_requested",
				"approval_date"
			);
			$where = array(
				"el.company_id" =>$company_id,
				"el.deleted" => '0',
     			"ag.emp_id" => $emp_id,
				"el.leave_application_status" => "pending"
			);		
			$where2 = array(
				"al.level !=" => ""
			);
			if($search != "" && $search != "all"){
				$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
			}
			$select = array(
				"*",
				"pg.name AS pg_name",
				"empl.remaining_leave_credits AS remaining_c"
			);
			$this->db->select($select);
			$this->db->where($where2);
			$this->edb->where($where);
			if($filter != FALSE){
				$this->db->where("el.employee_leaves_application_id NOT IN ({$filter})");
			}
			$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    		$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
    		$this->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
    		$this->edb->join("employee_leaves AS empl","empl.leave_type_id = el.leave_type_id AND empl.emp_id = e.emp_id","LEFT");
    		$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
    		$this->db->group_by("el.employee_leaves_application_id");
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			}
			else{
				$this->db->order_by("date_filed","DESC");
				$this->db->order_by("employee_leaves_application_id","DESC");
			}
			$query = $this->edb->get("employee_leaves_application AS el",$limit,$start);
	
			$result = $query->result();
			$query->free_result();
			return $result;
		}else{
			return false;
		}
	}
	
	public function not_in_search_leave($company_id, $emp_id, $search = ""){
		if(is_numeric($company_id)){
			$konsum_key = konsum_key();
			$where = array(
				"el.company_id" =>$company_id,
				"el.deleted" => '0',
     			"ag.emp_id" => $emp_id,
				"el.leave_application_status" => "pending"
			);		
			$where2 = array(
				"al.level !=" => ""
			);
			if($search != "" && $search != "all"){
				$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
			}
			$this->db->where($where2);
			$this->edb->where($where);
			$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    		$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
    		$this->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
    		$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
    		$this->db->group_by("el.employee_leaves_application_id");
    		$this->db->order_by("date_filed","DESC");
			$query = $this->edb->get("employee_leaves_application AS el");
			$result = $query->result();
			
			$arrs = array();
			if($result){
				$is_assigned = true;
				$workforce_notification = get_workforce_notification_settings($company_id);
				foreach($result as $key => $approvers){
					$leave_approval_grp = $approvers->leave_approval_grp;
					$level = $approvers->level;
					$check = $this->check_assigned_leave($leave_approval_grp, $level);
					$is_done = $this->is_done($leave_approval_grp, $level);
					if($workforce_notification->option == "choose level notification"){ 
						$is_assigned = check_if_is_level($level, $workforce_notification->workforce_alerts_notification_id);
					}
					//if(!$is_done && $is_assigned && $check){
					if(!$is_done){
					}
					else{
						array_push($arrs, $approvers->employee_leaves_application_id);
					}
				}
			}
			$string = implode(",", $arrs);
			return $string;
		}else{
			return false;
		}
	}
	
	public function search_leave_count($company_id, $emp_id, $search = ""){
		if(is_numeric($company_id)){
			$konsum_key = konsum_key();
			$filter = $this->not_in_search_leave($company_id, $emp_id, $search);
			$where = array(
				"el.company_id" =>$company_id,
				"el.deleted" => '0',
     			"ag.emp_id" => $emp_id,
				"el.leave_application_status" => "pending"
			);		
			$where2 = array(
				"al.level !=" => ""
			);
			if($search != "" && $search != "all"){
				$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
			}	
			$this->db->select('count(*) as val');
			$this->db->where($where2);
			$this->edb->where($where);
			if($filter != FALSE){
				$this->db->where("el.employee_leaves_application_id NOT IN ({$filter})");
			}
			$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    		$this->edb->join("approval_groups AS ag","agg.approval_process_id = ag.approval_process_id","LEFT");
    		$this->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
    		$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
    		$this->db->group_by("el.employee_leaves_application_id");
			$query = $this->edb->get("employee_leaves_application AS el");
			$row = $query->row();

			return ($row) ? $query->num_rows : 0;
		}else{
			return false;
		}
	}
	
	/**
	 * 
	 * Get all pending leave application for owner
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $limit
	 * @param unknown_type $start
	 * @param unknown_type $search
	 * @param unknown_type $sort_by
	 * @param unknown_type $sort
	 */
	public function search_leave_owner($company_id, $limit = 10, $start = 0, $search = "", $sort_by = "", $sort = "ASC"){
		if(is_numeric($company_id)){
			$konsum_key = konsum_key();
			$start = intval($start);
			$limit = intval($limit);
			$sort_array = array(
				"first_name",
				"pg_name",
				"date_filed",
				"leave_type",
				"date_start",
				"total_leave_requested",
				"approval_date"
			);
			$where = array(
				"el.company_id" =>$company_id,
				"el.deleted" => '0',
				"el.leave_application_status" => "pending"
			);		

			if($search != "" && $search != "all"){
				$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
			}
			$select = array(
				"*",
				"pg.name AS pg_name",
				"empl.remaining_leave_credits AS remaining_c"
			);
			$this->db->select($select);
			$this->edb->where($where);
			$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    		$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
    		$this->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
    		$this->edb->join("employee_leaves AS empl","empl.leave_type_id = el.leave_type_id AND empl.emp_id = e.emp_id","LEFT");
    		$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
    		$this->db->group_by("el.employee_leaves_application_id");
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			}
			else{
				$this->db->order_by("date_filed","DESC");
				$this->db->order_by("employee_leaves_application_id","DESC");
			}
			$query = $this->edb->get("employee_leaves_application AS el",$limit,$start);
	
			$result = $query->result();
			$query->free_result();
			return $result;
		}else{
			return false;
		}
	}
	
	public function search_leave_owner_count($company_id, $search = ""){
		if(is_numeric($company_id)){
			$konsum_key = konsum_key();
			
			$sort_array = array(
				"first_name",
				"pg_name",
				"date_filed",
				"leave_type",
				"date_start",
				"total_leave_requested",
				"approval_date"
			);
			$where = array(
				"el.company_id" =>$company_id,
				"el.deleted" => '0',
				"el.leave_application_status" => "pending"
			);		
			if($search != "" && $search != "all"){
				$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
			}
			$select = array(
				"*",
				"pg.name AS pg_name"
			);
			$this->db->select($select);
			$this->edb->where($where);
			$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    		$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
    		$this->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
    		$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
    		$this->db->group_by("el.employee_leaves_application_id");
			$query = $this->edb->get("employee_leaves_application AS el");
			$result = $query->result();
			
			return ($result) ? $query->num_rows() : 0;
		}else{
			return false;
		}
	}
	
	public function check_assigned_leave($leave_appr_grp, $level){
		$where = array(
			"emp_id" => $this->session->userdata("emp_id"),
			"level" => $level,
			"approval_groups_via_groups_id" => $leave_appr_grp
		);
		$this->db->where($where);
		$query = $this->db->get("approval_groups");
		$row = $query->row();
		
		return ($row) ? true : false;
	}
	
	public function track_next_approver($leave_appr_grp, $level){
		$where = array(
			"ag.level" => $level,
			"ag.approval_groups_via_groups_id" => $leave_appr_grp
		);
		$this->db->where($where);
		$this->edb->join("employee AS e", "e.emp_id = ag.emp_id","LEFT");
		$query = $this->edb->get("approval_groups AS ag");
		$row = $query->row();
		$next_approver = "";
		if($row){
			$next_approver = ucwords($row->first_name." ".$row->last_name);
		}
		
		return $next_approver;
	}
	
	public function is_done($leave_appr_grp, $level){
		$where = array(
			"emp_id" => $this->session->userdata("emp_id"),
			"level <" => $level,
			"approval_groups_via_groups_id" => $leave_appr_grp
		);
		$this->db->where($where);
		$query = $this->db->get("approval_groups");
		$row = $query->row();
		
		return ($row) ? true : false;
	}
	
	public function leave_details($leave_id,$company_id){
		$where = array(
			"el.company_id" =>$company_id,
			"el.employee_leaves_application_id"	 => $leave_id,
			"el.deleted" => '0'
		);		
		$this->edb->where($where);
		$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$this->edb->join("position AS pos","pos.position_id = epi.position","LEFT");
		$query = $this->edb->get("employee_leaves_application AS el");
		
		$row = $query->row();
		
		return ($row) ? $row : false;
	}
	
	public function specific_employee_leave($company_id, $limit = 10, $start = 0, $emp_id, $appr_emp_id, $sort_by = "", $sort="ASC"){
		if(is_numeric($company_id)){
			$filter = $this->not_in_specific_employee_leave($company_id,$emp_id,$appr_emp_id);
			$konsum_key = konsum_key();
			$start = intval($start);
			$limit = intval($limit);
			$select = array(
				"*",
				"empl.remaining_leave_credits AS remaining_c"
			);
			$sort_array = array(
				"date_filed",
				"leave_type",
				"date_start",
				"total_leave_requested",
				"approval_date"
			);
			$where = array(
				"el.company_id" =>$company_id,
				"e.emp_id" => $emp_id,
				"el.deleted" => '0',
				"el.leave_application_status" => "pending"
			);		
			$where2 = array(
				"al.level !=" => ""
			);
			$this->edb->where($where);
			$this->db->where($where2);
			if($filter != FALSE){
				$this->db->where("el.employee_leaves_application_id NOT IN ({$filter})");
			}
			$this->db->select($select);
			$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    		$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
    		$this->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
    		$this->edb->join("employee_leaves AS empl","empl.leave_type_id = el.leave_type_id AND empl.emp_id = e.emp_id","LEFT");
    		$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			}
			else{
				$this->db->order_by("date_filed","DESC");
				$this->db->order_by("employee_leaves_application_id","DESC");
			}
    		$this->db->group_by("el.employee_leaves_application_id");
			$query = $this->edb->get("employee_leaves_application AS el",$limit,$start);
			$result = $query->result();
			
			return ($result) ? $result : false;
		}else{
			return false;
		}
	}
	
	public function not_in_specific_employee_leave($company_id, $emp_id, $appr_emp_id){
		if(is_numeric($company_id)){
			$where = array(
				"el.company_id" =>$company_id,
				"el.deleted" => '0',
     			"ag.emp_id" => $emp_id,
				"el.leave_application_status" => "pending",
				"ag.emp_id" => $appr_emp_id
			);		
			$where2 = array(
				"al.level !=" => ""
			);
			$this->db->where($where2);
			$this->edb->where($where);
			$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    		$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
    		$this->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
    		$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
    		$this->db->group_by("el.employee_leaves_application_id");
    		$this->db->order_by("date_filed","DESC");
			$query = $this->edb->get("employee_leaves_application AS el");
			$result = $query->result();
			
			$arrs = array();
			if($result){
				$is_assigned = true;
				$workforce_notification = get_workforce_notification_settings($company_id);
				foreach($result as $key => $approvers){
					$leave_approval_grp = $approvers->leave_approval_grp;
					$level = $approvers->level;
					$check = $this->check_assigned_leave($leave_approval_grp, $level);
					if($workforce_notification->option == "choose level notification"){ 
						$is_assigned = check_if_is_level($level, $workforce_notification->workforce_alerts_notification_id);
					}
					$is_done = $this->is_done($leave_approval_grp, $level);
					//if(!$is_done && $is_assigned && $check){
					if(!$is_done){
					}
					else{
						array_push($arrs, $approvers->employee_leaves_application_id);
					}
				}
			}
			$string = implode(",", $arrs);
			return $string;
		}else{
			return false;
		}
	}
	
	public function specific_employee_leave_count($company_id, $emp_id, $appr_emp_id){
		if(is_numeric($company_id)){
			$konsum_key = konsum_key();	
			$filter = $this->not_in_specific_employee_leave($company_id,$emp_id,$appr_emp_id);
			$where = array(
				"el.company_id" =>$company_id,
				"e.emp_id" => $emp_id,
				"el.deleted" => '0'
			);
			$where2 = array(
				"al.level !=" => "",
				"el.leave_application_status" => "pending"
			);
			$this->edb->where($where);
			$this->db->where($where2);
			if($filter != FALSE){
				$this->db->where("el.employee_leaves_application_id NOT IN ({$filter})");
			}
			$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    		$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
    		$this->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
    		$this->edb->group_by("el.employee_leaves_application_id");
			$query = $this->edb->get("employee_leaves_application AS el");
			$row = $query->row();

			return ($row) ? $query->num_rows() : 0;
		}else{
			return false;
		}
	}

	public function owner_specific_employee_leave($company_id, $limit = 10, $start = 0, $emp_id, $sort_by = "", $sort = "ASC"){
		if(is_numeric($company_id)){
			$start = intval($start);
			$limit = intval($limit);
			$select = array(
				"*",
				"empl.remaining_leave_credits AS remaining_c"
			);
			$sort_array = array(
				"date_filed",
				"leave_type",
				"date_start",
				"total_leave_requested",
				"approval_date"
			);
			$where = array(
				"el.company_id" =>$company_id,
				"e.emp_id" => $emp_id,
				"el.deleted" => '0',
				"el.leave_application_status" => "pending"
			);		
			$this->db->select($select);
			$this->edb->where($where);
			$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    		$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
    		$this->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
    		$this->edb->join("employee_leaves AS empl","empl.leave_type_id = el.leave_type_id AND empl.emp_id = e.emp_id","LEFT");
    		$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
    		$this->db->group_by("el.employee_leaves_application_id");
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			}
			else{
				$this->db->order_by("date_filed","DESC");
				$this->db->order_by("employee_leaves_application_id","DESC");
			}
			$query = $this->edb->get("employee_leaves_application AS el",$limit,$start);
			$result = $query->result();
			
			return ($result) ? $result : false;
		}else{
			return false;
		}
	}
	
	public function owner_specific_employee_leave_count($company_id, $emp_id, $sort_by = "", $sort = "ASC"){
		if(is_numeric($company_id)){
			$where = array(
				"el.company_id" =>$company_id,
				"e.emp_id" => $emp_id,
				"el.deleted" => '0',
				"el.leave_application_status" => "pending"
			);		
			$this->edb->where($where);
			$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
    		$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
    		$this->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
    		$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
    		$this->db->group_by("el.employee_leaves_application_id");
			$query = $this->edb->get("employee_leaves_application AS el");
			$result = $query->result();
			
			return ($result) ? $query->num_rows() : 0;
		}else{
			return false;
		}
	}
	
	public function leave_approval_level($emp_id){
		if(is_numeric($emp_id)){
			$this->db->where('ap.name','Leave Approval Group');
			$this->db->where('ag.emp_id',$emp_id);
			$this->db->join('approval_process AS ap','ap.approval_process_id = ag.approval_process_id','LEFT');
			$q = $this->db->get('approval_groups AS ag');
			$r = $q->row();
			
			return ($r) ? $r->level : FALSE;
		}else{
			return false;
		}
	}
	
	public function get_leave_last_level($emp_id, $company_id){
		$this->db->where("emp_id",$emp_id);
		$sql = $this->db->get("employee_payroll_information");
		$row = $sql->row();
		if($row){
			$leave_approval_grp = $row->leave_approval_grp;
			$w = array(
				"ag.company_id"=>$company_id,
				"ag.approval_groups_via_groups_id"=>$leave_approval_grp
			);
			$this->db->where($w);
			$this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->db->order_by("ag.level","DESC");
			$q = $this->edb->get("approval_groups AS ag",1);
			$r = $q->row();
			return ($r) ? $r->level : FALSE ;
		}else{
			return FALSE;
		}
	}

	public function generate_leave_level_token($new_level, $leave_id){
		$str2 = 'ABCDEFG1234567890';
		$shuffled2 = str_shuffle($str2);
		
		$update = array(
			"level" => $new_level,
			"token_level" => $shuffled2
		);
		$where = array(
			"leave_id" => $leave_id
		);
		
		$this->db->where($where);
		$update_approval_leave_token = $this->db->update("approval_leave",$update);
		
		return ($update_approval_leave_token) ? $shuffled2 : false;
	}
	
	public function get_last_level_checked_payroll($company_id, $workforce_id){
		$where = array(
			"workforce_alerts_notification_id" => $workforce_id,
			"company_id" => $company_id
		);
		$this->db->where($where);
		$this->db->order_by("level","DESC");
		$query = $this->db->get("workforce_notification_leveling",1);
		$row = $query->row();
		
		return ($row) ? $row->level : false;
	}
	
	public function get_last_level_notify_all($emp_id,$company_id){
		$this->db->where("emp_id",$emp_id);
		$sql = $this->db->get("employee_payroll_information");
		$row = $sql->row();
		if($row){
			$leave_approval_grp = $row->leave_approval_grp;
			$w = array(
				"ag.company_id"=>$company_id,
				"ag.approval_groups_via_groups_id"=>$leave_approval_grp
			);
			$this->db->where($w);
			$this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->db->order_by("ag.level","DESC");
			$q = $this->edb->get("approval_groups AS ag",1);
			$r = $q->row();
			return ($r) ? $r->level : FALSE ;
		}else{
			return FALSE;
		}
	}

	/**
	 * check if employee leave application is pending
	 * @param unknown $emp_leave_id
	 * @param unknown $company_id
	 */
	public function is_emp_leave_pending($emp_leave_id,$company_id){
		$where = array(
			"employee_leaves_application_id" => $emp_leave_id,
			"company_id" => $company_id,
			"leave_application_status" => "pending"
		);
		$this->db->where($where);
		$query = $this->db->get("employee_leaves_application");
		$row = $query->row();
			
		return ($row) ? true : false;
	}
	
	
	/**
	 * IMPORTANT : approve employee leave application with computations
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param unknown $leave_type_id
	 * @param unknown $total_leave_requested
	 * @param unknown $employee_leaves_application_id
	 */
	public function update_employee_leaves($company_id, $emp_id, $leave_type_id, $total_leave_requested, $employee_leaves_application_id, $flag_payroll_correction = "current") {
	    if(is_numeric($company_id) && is_numeric($emp_id)) {
	        # get employee leaves first
	        $query= $this->db->get_where("employee_leaves",array("company_id"=>$company_id,"leave_type_id"=>$leave_type_id,"emp_id"=>$emp_id,"status"=>"Active"));
	        $row = $query->row();
	        $query->free_result();
	        
	        $get_leave_restriction                 = $this->get_leave_restriction($leave_type_id,'requires_leave_credits');
	        $paid_leave                            = $this->get_leave_restriction($leave_type_id,'paid_leave');
	        $what_happen_to_unused_leave           = $this->get_leave_restriction($leave_type_id,'what_happen_to_unused_leave');
	        $leave_conversion_run_every            = $this->get_leave_restriction($leave_type_id,'leave_conversion_run_every');
	        $carry_over_schedule_specific_month    = $this->get_leave_restriction($leave_type_id,'carry_over_schedule_specific_month');
	        $carry_over_schedule_specific_day      = $this->get_leave_restriction($leave_type_id,'carry_over_schedule_specific_day');
	        $carry_forward_expiry                  = $this->get_leave_restriction($leave_type_id,'carry_forward_expiry');
	        $carry_forward_expiry_months           = $this->get_leave_restriction($leave_type_id,'carry_forward_expiry_months');
	        if($row) {
	            $leave_credits = $row->leave_credits; 				# LEAVE CREDITS
	            $previous_credits = $row->previous_leave_credits; 	# PREVIOUS CREDITS
	            $remaining_credits = $row->remaining_leave_credits;	# REMAINING CREDITS
	            
	            $leave_type_info = $this->get_leave_type_info($leave_type_id, $company_id);
	            
	            $check_remaining = $remaining_credits == NULL ?  $leave_type_info->leave_credits : $remaining_credits;
	            
	            if($get_leave_restriction == "no" && $paid_leave == "no") {
	                $remaining = $row->remaining_leave_credits;
	            } else {
	                $remaining = floatval($check_remaining) - floatval($total_leave_requested);
	            }
	            
	            $check_previous_credits = $previous_credits !="" ? $previous_credits : $leave_credits;
	            
	            $result_previous = floatval($check_previous_credits) - floatval($total_leave_requested);
	            if($result_previous < 0) { # IF LESS THAN SA PREVIOUS
	                $result_previous = floatval($check_previous_credits);
	            }
	            
	            $credited_value = 0;
	            $non_credited = 0;
	            
	            # get credited
	            if($check_remaining > 0) { #remaining > 3
	                #gikuha total leave kong 5 , employee - 3 remainig - 4
	                $credited_value_formula = floatval($check_remaining) - floatval($total_leave_requested);
	                if($credited_value_formula <= 0){
	                    $credited_value = floatval($check_remaining);
	                    $non_credited = abs($credited_value_formula);
	                }else{
	                    $credited_value = floatval($total_leave_requested);
	                    $non_credited = 0;
	                }
	            }else{
	                $non_credited = $total_leave_requested;
	            }
	            # end credited
	            
	            //calculate credits for remaining Carried Forward Leaves and Adjustment Leave
	            $remaining_carried_fc = $row->carried_forward_leaves;
	            $remaining_adjustment_l = $row->adjustment_leave;
	            
	            if($what_happen_to_unused_leave == "accrue to next period") {
	                $get_employee_details_by_empid = get_employee_details_by_empid($emp_id);
	                
	                $conversion_sched = "";
	                $this_year = date('Y');
	                $get_date_filed = $this->check_leave_application($employee_leaves_application_id,true);
	                if($leave_conversion_run_every == "annual") {
	                    $conversion_sched = $this_year.'-12-31';
	                    $conversion_sched = date('Y-m-d', strtotime($conversion_sched));
	                    
	                    $new_conversion_sched = date('Y-m-d', strtotime($conversion_sched));
	                } elseif ($leave_conversion_run_every == "anniversary") {
	                    $date_hired = $get_employee_details_by_empid->date_hired;
	                    $conversion_sched = $this_year.'-'.date('m-d', strtotime($date_hired));
	                    $conversion_sched = date('Y-m-d', strtotime($conversion_sched));
	                    
	                    $new_conversion_sched = date('Y-m-d', strtotime($conversion_sched));
	                } elseif ($leave_conversion_run_every == "specific date") {
	                    $conversion_sched = $this_year.'-'.$carry_over_schedule_specific_month.'-'.$carry_over_schedule_specific_day;
	                    $conversion_sched = date('Y-m-d', strtotime($conversion_sched));
	                    
	                    $new_conversion_sched = date('Y-m-d', strtotime($conversion_sched));
	                }
	                
	                if($carry_forward_expiry == "Expires") {
	                    if($carry_forward_expiry_months) {
	                        $expiration_date = date('Y-m-d', strtotime($new_conversion_sched.' +'.$carry_forward_expiry_months.' months'));
	                        
	                        if(strtotime($get_date_filed) < strtotime($expiration_date)) {
	                            if($total_leave_requested > $row->carried_forward_leaves) {
	                                $remaining_carried_fc = 0;
	                                $remaining_carried_temp = $total_leave_requested - $row->carried_forward_leaves;
	                                
	                                if($row->adjustment_leave > 0) {
	                                    if($row->adjustment_leave > $remaining_carried_temp) {
	                                        $remaining_adjustment_l = $row->adjustment_leave - $remaining_carried_temp;
	                                    } else {
	                                        $remaining_adjustment_l = 0;
	                                    }
	                                } else {
	                                    $remaining_adjustment_l = 0;
	                                }
	                            } else {
	                                $remaining_carried_fc = $row->carried_forward_leaves - $total_leave_requested;
	                                $remaining_adjustment_l = $row->adjustment_leave;
	                            }
	                            
	                        }
	                    }
	                } elseif($carry_forward_expiry == "No Expiry") {
	                    if($total_leave_requested > $row->carried_forward_leaves) {
	                        $remaining_carried_fc = 0;
	                        $remaining_carried_temp = $total_leave_requested - $row->carried_forward_leaves;
	                        
	                        if($row->adjustment_leave > 0) {
	                            if($row->adjustment_leave > $remaining_carried_temp) {
	                                $remaining_adjustment_l = $row->adjustment_leave - $remaining_carried_temp;
	                            } else {
	                                $remaining_adjustment_l = 0;
	                            }
	                        } else {
	                            $remaining_adjustment_l = 0;
	                        }
	                    } else {
	                        $remaining_carried_fc = $row->carried_forward_leaves - $total_leave_requested;
	                        $remaining_adjustment_l = $row->adjustment_leave;
	                    }
	                }
	            }
	            
	            $where = array(
	                "company_id"       => $company_id,
	                "emp_id"           => $emp_id,
	                "leave_type_id"    => $leave_type_id
	            );
	            
	            $field = array(
	                "remaining_leave_credits"  => $remaining,
	                "previous_leave_credits"	  => $result_previous,
	                "adjustment_leave"         => $remaining_adjustment_l,
	                "carried_forward_leaves"   => $remaining_carried_fc
	            );
	            $this->db->update("employee_leaves",$field,$where);
	            
	            // check payroll period >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	            $check_payroll_period = $this->check_payroll_period($company_id);
	            
	            $idates_now = idates_now();
	            if($check_payroll_period != FALSE){
	                $start_date = $this->check_leave_application($employee_leaves_application_id);
	                $payroll_period = $check_payroll_period->payroll_period;
	                $period_from = $check_payroll_period->period_from;
	                $period_to = $check_payroll_period->period_to;
	                $datenow = date("Y-m-d");
	                
	                if(strtotime($period_from) <= strtotime($start_date) && strtotime($start_date) <= strtotime($payroll_period)){
	                    if(strtotime($period_to) < strtotime($datenow) && strtotime($datenow) <= strtotime($payroll_period)) $idates_now = $period_to." ".date("H:i:s");
	                }
	            }
	            // end check payroll period >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	            
	            // check if workflow setting is disabled (auto reject or approve)
	            $get_approval_settings_disable_status = $this->get_approval_settings_disable_status($company_id);
	            
	            if($get_approval_settings_disable_status) {
	                if($get_approval_settings_disable_status->status == "Inactive") {
	                    $status = $get_approval_settings_disable_status->disabled_application_status;
	                } else {
	                    $status = "approve";
	                }
	            } else {
	                $status = "approve";
	            }
	            
	            // this is for to do if payroll is locked and closed
	            if($flag_payroll_correction == "late") {
	                $application_status = "Inactive";
	            } else {
	                $application_status = "Active";
	            }
	            
	            # UPDATES THE EMPLOYEE LEAVE APPLICATIONS
	            $fields_ela = array(
	                "leave_application_status" 	=> $status,
	                "approval_date"				=> $idates_now,
	                "credited"					=> $credited_value,
	                "non_credited"				=> $non_credited,
	                #"approver_account_id"		=> $this->session->userdata('account_id'), # BOGO NAG ADD NAKO DIRI PISTI
	                "status"                     => $application_status //if payroll is locked and closed (inactive)
	            );
	            $where_ela = array(
	                "employee_leaves_application_id"	=> $employee_leaves_application_id,
	                "company_id"						=> $company_id
	            );
	            $this->leave->update_field("employee_leaves_application",$fields_ela,$where_ela);
	            # END UPDATES THE EMPLOYEE LEAVE APPLICATIONS
	            
	            // --------------------- APPROVE CHILD START ---------------------
	            $where_child = array(
	                "company_id"   => $company_id,
	                "leaves_id"    => $employee_leaves_application_id
	            );
	            
	            $update_child = array(
	                "approval_date"				=> $idates_now,
	                "leave_application_status"     => $status,
	                "status"                       => $application_status //if payroll is locked and closed (inactive)
	            );
	            
	            $this->db->where($where_child);
	            $this->db->update("employee_leaves_application",$update_child);
	            // --------------------- APPROVE CHILD END ---------------------
	            
	            // --------------------- ADD APPROVED LEAVE HISTORY START ---------------------
	            
	            add_employee_history($company_id, $leave_type_id, $emp_id, $remaining, date("Y-m-d"));
	            
	            // --------------------- ADD APPROVED LEAVE HISTORY END ---------------------
	            
	            $we = number_format($check_remaining,3) .'-'. number_format($total_leave_requested,3);
	            return array("sumsa"=> $check_remaining."- ".$total_leave_requested."REMAINING".$remaining_credits."remaining val".$remaining."credited".$credited_value."calculation".$we);
	        } else {
	            return false;
	        }
	    } else {
	        return false;
	    }
	}
	
	/**
	 * Update fields
	 * @param string $database
	 * @param array $field
	 * @param array $where
	 * @return boolean
	 */
	public function update_field($database,$field,$where){
		$this->db->where($where);
		$this->db->update($database,$field);
		return $this->db->affected_rows();
	}
	
	/* ------------------------------------------------------------------------------------------------------- */
		
		
		
		
		
	/**
	 * CHECKS APPLICATION LEAVE FOR EVERY COMPANY
	 * @param int $company_id
	 * @return object
	 */
	public function leave_application_list($company_id,$limit=10,$start=0){
		if(is_numeric($company_id)){
			$start = intval($start);
			$limit = intval($limit);
			$query2 = $this->db->query(
					"	SELECT *,concat(e.first_name,' ',e.last_name) as full_name FROM employee_leaves_application el
						LEFT JOIN employee e on e.emp_id = el.emp_id 
						LEFT JOIN accounts a on a.account_id = e.account_id 
						WHERE el.company_id = '{$this->db->escape_str($company_id)}' AND el.deleted = '0' 
						ORDER BY el.date_filed desc,e.last_name ASC
						LIMIT {$start},{$limit} 
					"
			);
			$where = array(
				"el.company_id"=>$company_id,
				"el.deleted"=>"0",
			);
			$this->db->order_by("el.date_filed", "desc");
			$this->edb->order_by("e.last_name", "asc"); 
			$this->edb->where($where);
			$this->edb->join("employee AS e","e.emp_id = el.emp_id");
			$this->edb->join("accounts AS a","a.account_id = e.account_id");
			$query = $this->edb->get("employee_leaves_application AS el",$limit,$start);
			
			$result = $query->result();	
			$query->free_result();
			return $result;
		}else{
			return false;
		}
	}
	
	/**
	 * Leave date sorting 
	 * @param int $company_id
	 * @param int $limit
	 * @param int $start
	 */
	public function leave_application_date_sort($company_id,$limit=10,$start=0,$date_from,$date_to){
		if(is_numeric($company_id)){
			$date_from = $date_from;
			$date_to =	$date_to;
			$start = intval($start);
			$limit = intval($limit);
			$query2 = $this->db->query(
					"	SELECT *,concat(e.first_name,' ',e.last_name) as full_name FROM employee_leaves_application el
						LEFT JOIN employee e on e.emp_id = el.emp_id 
						LEFT JOIN accounts a on a.account_id = e.account_id 
						WHERE el.company_id = '{$this->db->escape_str($company_id)}' AND el.deleted = '0' 
						AND el.date_start >= {$date_from}	AND el.date_start <={$date_to} 	
						 LIMIT {$start},{$limit} 
					"
			);
			$where = array(
				"el.company_id" =>$company_id,
				"el.deleted" => '0',	
			);
			
			$this->edb->where($where);
			$this->db->where(array("el.date_start >="=>"{$date_from}","el.date_start <="=>"{$date_to}"));
		
			$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$query = $this->edb->get("employee_leaves_application AS el",$limit,$start);
	
			$result = $query->result();
			$query->free_result();
			return $result;
		}else{
			return false;
		}
	}
	
	/**
	 * CHECKS APPLICATION LEAVE FOR EVERY COMPANY by employe name
	 * @param int $company_id
	 * @return object
	 */
	public function leave_application_list_name($company_id,$limit=10,$start=0,$employee_name){
		if(is_numeric($company_id)){
			$start = intval($start);
			$limit = intval($limit);
			$employee_name =$this->db->escape_like_str($employee_name);
			$query2 = $this->db->query(
					"	SELECT *,concat(e.first_name,' ',e.last_name) as full_name FROM employee_leaves_application el
						LEFT JOIN employee e on e.emp_id = el.emp_id 
						LEFT JOIN accounts a on a.account_id = e.account_id 
						WHERE el.company_id = '{$this->db->escape_str($company_id)}' AND el.deleted = '0'
						AND concat(e.first_name,' ',e.last_name) like '%{$employee_name}%' 
						 LIMIT {$start},{$limit} 
					"
			);
			$this->db->like("concat(e.first_name,' ',e.last_name)",$employee_name);
			$where = array(
				"el.company_id" => $company_id,
				"el.deleted"=>"0"	
			);
			$this->db->where($where);
			$this->edb->join("employee AS e","e.emp_id = el.emp_id ","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id ","INNER");
			$query = $this->edb->get("employee_leaves_application AS el");
			$result = $query->result();
			$query->free_result();
			return $result;
		}else{
			return false;
		}
	}
	
	/**
	 * Count Leaves application for pagination purposes only
	 * @param int $company_id
	 * @return integer
	 */
	public function leave_application_count($company_id){
		if(is_numeric($company_id)){
			$query = $this->db->query("SELECT count(*) as val FROM employee_leaves_application WHERE  company_id = {$this->db->escape_str($company_id)} AND deleted='0'
			");
			$row = $query->row();
			$num_row = $query->num_rows();
			$query->free_result();
			return $num_row ? $row->val : 0;
		}else{
			return false;
		}
	}
	
	/**
	 * LEave application count date sort
	 * @param int $company_id
	 * @param date $date_from
	 * @param date $date_to
	 * @return integer
	 */
	public function leave_application_date_count($company_id,$date_from,$date_to){
		if(is_numeric($company_id)){
			$date_from = $this->db->escape($date_from);
			$date_to = $this->db->escape($date_to);
			$query = $this->db->query("SELECT count(*) as val FROM employee_leaves_application WHERE 
					 company_id = '{$this->db->escape_str($company_id)}' AND deleted='0'  AND date_start >= {$date_from}	AND date_start <={$date_to}");
					 
			$row = $query->row();
			$num_row = $query->num_rows();
			$query->free_result();
			return $num_row ? $row->val : 0;
		}else{
			return false;
		}
	}
	
	/**
	 * Leave application get count by name
	 * Enter description here ...
	 * @param unknown_type $company_id
	 */
	public function leave_application_count_name($company_id,$employee_name){
		if(is_numeric($company_id) && $employee_name !=""){
			$employee_name = $this->db->escape_like_str($employee_name);
			$query = $this->db->query(
				"SELECT count(*) as val FROM employee_leaves_application ela
				LEFT JOIN employee e on e.emp_id = ela.emp_id
				WHERE  ela.company_id = '{$this->db->escape_str($company_id)}' AND ela.deleted='0' 
				AND concat(e.first_name,' ',e.last_name) like '%{$employee_name}%'"
			);
			$row = $query->row();
			$num_row = $query->num_rows();
			$query->free_result();
			return $num_row ? $row->val : 0;
		}else{
			return false;
		}
	}

	/**
	 * ADD ACTIVITY LEAVES ON LEAVE APPlICATION
	 * once the leave application is done , records it on activity logs
	 * @param int $employee_leaves_application_id
	 * @param int $company_id
	 */
	public function ajax_leave_logs_approve($employee_leaves_application_id,$company_id){
		$log_user = imodule_account();
		if($this->session->userdata('user_type_id') == 2){ // IF OWNER 	
			#$company_owner = $this->profile->get_account($this->session->userdata('account_id'),"company_owner");
			#$fullname = $company_owner->first_name." ".$company_owner->last_name;
		
			
			if(is_numeric($employee_leaves_application_id)){
				# CHECK THE LEAVE APPliCATION ID ONCE IT IS VAlID THEN WE TAKE OUT EMP_ID WHO FILED THIS 
				$leave_id = check_leave_application($employee_leaves_application_id); 
				# NOW Shreds the DATA and distributes its emp_id to the profile_getAccount	
				#ADD ACTIVITY LOGS
				$check_employee = imodule_employee($leave_id->emp_id,$this->company_info->company_id);
				$employee_data = $check_employee ? $check_employee->last_name.",".$check_employee->first_name : "Unknown emp_id:".$leave_id->emp_id;
				$date_requested = date("m/d/Y",strtotime($leave_id->date_start));
				$check_users = "";
				if($log_user){
					if($log_user->last_name ==""){
						$check_users = "Owner";
					}else{
						$check_users = $log_user->last_name." , ".$log_user->first_name;
					}	
				} 				

				
				# ADD OUR NOTIFICATION
				inotify_logs($check_employee->emp_id,$company_id,'Your Leave application has been approve','system');
				# END ADD OUR REJECT NOTIFICATION
				
				$mesage = sprintf(lang("approve_leave"),$check_users,$employee_data,$date_requested);
				add_activity($mesage,$company_id);
				#END ACTIVITY LOGS
			}
		}else if($this->session->userdata('user_type_id') == 3){
			$company_owner = $this->profile->get_account($this->session->userdata('account_id'),"employee");
			$fullname = $company_owner->first_name." ".$company_owner->last_name;
			if(is_numeric($employee_leaves_application_id)){
				# CHECK THE LEAVE APPliCATION ID ONCE IT IS VAlID THEN WE TAKE OUT EMP_ID WHO FILED Tht
				$leave_id = check_leave_application($employee_leaves_application_id); 
				# NOW Shreds the DATA and distributes its emp_id to the profile_getAccount
				#ADD ACTIVITY LOGS
				$check_employee = imodule_employee($leave_id->emp_id,$company_id);
				$employee_data = $check_employee ? $check_employee->last_name.",".$check_employee->first_name : "Unknown emp_id:".$leave_id->emp_id;
				$date_requested = date("m/d/Y",strtotime($leave_id->date_start));
				$check_users = "";
				if($log_user){
					if($log_user->last_name ==""){
						$check_users = "Owner";
					}else{
						$check_users = $log_user->last_name." , ".$log_user->first_name;
					}	
				} 
				
											
				# ADD OUR NOTIFICATION
				inotify_logs($check_employee->emp_id,$company_id,'Your Leave application has been approve','system');
				# END ADD OUR REJECT NOTIFICATION
				
				$mesage = sprintf(lang("approve_leave"),$check_users,$employee_data,$date_requested);
				add_activity($mesage,$company_id);
				#ADD ACTIVITY LOGS
			}
		}
	}
	
	/**
	 * REJECTS APPLICATIONS 
	 * rejects application of leave
	 * @param int $employee_leaves_application_id
	 * @param int $company_id
	 */
	public function ajax_leave_logs_reject($employee_leaves_application_id,$company_id) {
		$log_user = imodule_account();
		if($this->session->userdata('user_type_id') == 2) { // IF OWNER 	
			if(is_numeric($employee_leaves_application_id)){
				# CHECK THE LEAVE APPliCATION ID ONCE IT IS VAlID THEN WE TAKE OUT EMP_ID WHO FILED THIS 
				$leave_id = check_leave_application($employee_leaves_application_id); 
				# NOW Shreds the DATA and distributes its emp_id to the profile_getAccount
				$check_employee = imodule_employee($leave_id->emp_id,$company_id);
				$employee_data = $check_employee ? $check_employee->last_name.",".$check_employee->first_name : "Unknown emp_id:".$leave_id->emp_id;
				$date_requested = date("m/d/Y",strtotime($leave_id->date_start));
				$check_users = "";
				if($log_user){
					if($log_user->last_name ==""){
						$check_users = "Owner";
					}else{
						$check_users = $log_user->last_name." , ".$log_user->first_name;
					}	
				} 	
				# ADD OUR NOTIFICATION
				inotify_logs($check_employee->emp_id,$company_id,'Your Leave application has been rejected','system');
				# END ADD OUR REJECT NOTIFICATION
				
				$mesage = sprintf(lang("reject_leave"),$check_users,$employee_data,$date_requested);
				add_activity($mesage,$company_id);
			}
		} else if($this->session->userdata('user_type_id') == 3){
			$company_owner = $this->profile->get_account($this->session->userdata('account_id'),"employee");
			$fullname = $company_owner->first_name." ".$company_owner->last_name;
			if(is_numeric($employee_leaves_application_id)){
				# CHECK THE LEAVE APPliCATION ID ONCE IT IS VAlID THEN WE TAKE OUT EMP_ID WHO FILED THIS 
				$leave_id = check_leave_application($employee_leaves_application_id); 
				# NOW Shreds the DATA and distributes its emp_id to the profile_getAccount
				$check_employee = imodule_employee($leave_id->emp_id,$company_id);
				$employee_data = $check_employee ? $check_employee->last_name.",".$check_employee->first_name : "Unknown emp_id:".$leave_id->emp_id;
				$date_requested = date("m/d/Y",strtotime($leave_id->date_start));
				
				$check_users = "";
				if($log_user){
					if($log_user->last_name ==""){
						$check_users = "Owner";
					}else{
						$check_users = $log_user->last_name." , ".$log_user->first_name;
					}	
				} 
				
				# ADD OUR NOTIFICATION
				inotify_logs($check_employee->emp_id,$company_id,'Your Leave application has been rejected','system');
				# END ADD OUR REJECT NOTIFICATION
				
				$mesage = sprintf(lang("reject_leave"),$check_users,$employee_data,$date_requested);
				add_activity($mesage,$company_id);
			}
		}
	}
	
	

	/**
	 * CHECK LEAVE TYPE 
	 * this will trigger the leave type details
	 * @param int $leave_type_id
	 * @param int $company_id
	 * @return object
	 */		
	public function check_leave_type($leave_type_id,$company_id){
		if(is_numeric($leave_type_id) && is_numeric($company_id)){
			$query = $this->db->query("SELECT * FROM leave_type WHERE leave_type_id = '{$this->db->escape_str($leave_type_id)}' AND company_id = '{$this->db->escape_str($company_id)}'");
			$row = $query->row();
			$query->free_result();
			return $row;
		}else{
			return false;
		}
	}
	
	public function employee_total_leave_approved($company_id,$emp_id,$leave_type_id) {
		if(is_numeric($company_id)) {
			$query = $this->db->query("SELECT sum(total_leave_requested) as total from `employee_leaves_application` where company_id = '{$company_id}' AND emp_id = '{$this->db->escape_str($emp_id)}' AND leave_application_status = 'approve' and status = 'Active' and leave_type_id = '{$this->db->escape_str($leave_type_id)}'");
			$row = $query->row();
			$query->free_result();
			return $row ? $row->total : 0;
		} else {
			return 0;
		}
	}
	
	public function employee_leave_credits($company_id,$emp_id,$leave_type_id) {
		if(is_numeric($company_id) && is_numeric($emp_id) && is_numeric($leave_type_id)) {
			$query = $this->db->query("SELECT sum(leave_credits) as total from `employee_leaves` where emp_id= '{$this->db->escape_str($emp_id)}' and company_id='{$company_id}' AND status = 'Active' and leave_type_id = '{$this->db->escape_str($leave_type_id)}'");
			$row = $query->row();
			$num_rows = $query->num_rows();
			$query->free_result();
			return $row->total !="" ?  $row->total :'0';
		} else {
			return '0';
		}
	}

	public function checkleave_employee_leaves_application($company_id,$employee_leaves_application_id) {
		if(is_numeric($company_id) && is_numeric($employee_leaves_application_id)) {
			$where = array(
						"company_id"	=> $company_id,
						"employee_leaves_application_id" => $this->db->escape_str($employee_leaves_application_id),
						"status"			=> "Active"
			);
			$this->db->select("employee_leaves_application_id,emp_id,leave_type_id,total_leave_requested,date_start,date_end");
			$query = $this->db->get_where("employee_leaves_application",$where);
			$row = $query->row();
			$query->free_result();
			return $row;
		} else {
			return false;
		}
	}
	
	public function get_leave_type_info($leave_type_id, $company_id){
		$where = array(
			"leave_type_id" => $leave_type_id,
			"company_id" => $company_id
		);
		$this->db->where($where);
		$query = $this->db->get("leave_type");
		$row = $query->row();
		
		return ($row) ? $row : false;
	}
	
	/**
	 * Check leave applications
	 * @param unknown_type $employee_leaves_application_id
	 */
	public function check_leave_application($employee_leaves_application_id,$date_applied=false){
	    $w = array("employee_leaves_application_id"=>$employee_leaves_application_id);
	    $this->db->where($w);
	    $q = $this->db->get("employee_leaves_application");
	    $r = $q->row();
	    if($date_applied == true) {
	        // note: nkigamit rako ug function mao ako ni g.add pra lng makuha nako sa date filed sa
	        // leave para gamit nako sa pgcalculate sa carried forward leave ug adjustment leave
	        return ($q->num_rows() > 0) ? $r->date_filed : FALSE ;
	    } else {
	        return ($q->num_rows() > 0) ? $r->date_start : FALSE ;
	    }
	    
	}
	
	/**
	 * Check Payroll Period
	 * @param unknown_type $val
	 */
	public function check_payroll_period($company_id){
		$ww = array("company_id"=>$company_id);
		$this->db->where($ww);
		$qq = $this->db->get("payroll_period");
		$rr = $qq->row();
		return ($qq->num_rows() > 0) ? $rr : FALSE ;
	}
	
	public function save_employee_leaves($company_id,$emp_id,$leave_type_id,$total_leave_requested) {
		if(is_numeric($company_id) && is_numeric($emp_id)) {
			$this->db->select("*");
			$this->db->from("employee_leaves e");
			$this->db->join("leave_type lt","lt.leave_type_id = e.leave_type_id","inner");
			$this->db->where(array("e.emp_id"=>90,"e.leave_type_id"=>21));
			$query = $this->db->get();
			$row = $query->row();
			$query->free_result();
			if($row) {
				
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	/**
	 * Leave Application
	 * @param unknown_type $leave_ids
	 * @param unknown_type $comp_id
	 */
	public function leave_information($leave_ids,$comp_id){
		$select = array(
			'employee.first_name',
			'employee.last_name',
			'employee_leaves_application.date_filed',
			'leave_type.leave_type',
			'employee_leaves_application.reasons',
			'employee_leaves_application.date_start',
			'employee_leaves_application.date_end',
			'employee_leaves_application.date_return',
			'employee_leaves_application.total_leave_requested',
			'employee_leaves_application.note',
			'accounts.email',
			'employee.emp_id'
		);
		$where = array(
			'employee_leaves_application.employee_leaves_application_id' => $leave_ids,
			'employee_leaves_application.company_id' => $comp_id
		);
		$this->edb->select($select);
		$this->edb->where($where);
		$this->edb->join('employee','employee_leaves_application.emp_id = employee.emp_id','left');
		$this->edb->join('accounts','accounts.account_id = employee.account_id','left');
		$this->edb->join('leave_type','employee_leaves_application.leave_type_id = leave_type.leave_type_id','left');
		
		$q = $this->edb->get('employee_leaves_application');
		$result = $q->row();
		
		return ($q->num_rows() > 0) ? $result : FALSE ;
	}
	
	/**
	 * Leave date sorting 
	 * @param int $company_id
	 * @param int $limit
	 * @param int $start
	 */
	public function advance_search($company_id,$limit=10,$start=0,$date_from = "",$date_to = "",$employee_name){
		if(is_numeric($company_id)){
			$date_from = $date_from;
			$date_to =	$date_to;
			$start = intval($start);
			$limit = intval($limit);
			$where = array(
				"el.company_id" =>$company_id,
				"el.deleted" => '0',	
			);		
			if($employee_name !="" && $employee_name !="all"){
					$this->edb->like_concat('e.last_name','e.first_name',$employee_name);
			}
			if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
				$this->db->where(array("el.date_start >="=>"{$date_from}","el.date_start <="=>"{$date_to}"));
			}	
			$this->edb->where($where);
			$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$query = $this->edb->get("employee_leaves_application AS el",$limit,$start);
	
			$result = $query->result();
			$query->free_result();
			return $result;
		}else{
			return false;
		}
	}
	
	/**
	 * Leave date sorting 
	 * @param int $company_id
	 * @param int $limit
	 * @param int $start
	 */
	public function count_advance_search($company_id,$date_from = "",$date_to = "",$employee_name){
		if(is_numeric($company_id)){
			$date_from = $date_from;
			$date_to =	$date_to;	
			$where = array(
				"el.company_id" =>$company_id,
				"el.deleted" => '0',	
			);		
			if($employee_name !="" && $employee_name !="all"){
					$this->edb->like_concat('e.last_name','e.first_name',$employee_name);
			}
			if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
				$this->db->where(array("el.date_start >="=>"{$date_from}","el.date_start <="=>"{$date_to}"));
			}	
			$this->db->select('count(*) as val');
			$this->edb->where($where);
			$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$query = $this->edb->get("employee_leaves_application AS el");
			$row = $query->row();
			$num_row = $query->num_rows();
			$query->free_result();
			return $num_row ? $row->val : 0;
		}else{
			return false;
		}
	}
	
	public function get_all_employees($company_id){
		if(is_numeric($company_id)) {
			$where = array(
				"e.company_id" =>$company_id,
				"e.status" => "Active",
				"a.user_type_id" => 5,
				"a.deleted" => "0"
			);
			$this->edb->where($where);
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$query = $this->edb->get('employee AS e');
			$result = $query->result();
			return $result;
		}else{
			return false;
		}
	}
	
	/**
	 * Get Approvers
	 */
	public function get_approver($emp_id){
		$w = array(
			'emp_id'=>$emp_id,
			'status'=>'Active'
		);
		$this->edb->where($w);
		$q = $this->edb->get("employee");
		$row = $q->row();
		return ($q->num_rows() > 0) ? ucwords($row->first_name)." ".ucwords($row->last_name): FALSE ;
	}
	
	/**
	 * HR SETUP GET ALL LIST OF APPROVERS
	 * Enter description here ...
	 * @param int $company_id
	 * @param string $leave_type
	 */
	public function list_of_approvers($company_id,$leave_type='Leave Approval Group'){
		if(is_numeric($company_id) && $leave_type !=""){ 
			$where = array(
				'ap.company_id' => $company_id,
				'e.deleted'=>'0',
				'a.deleted'=>'0',
				'e.status'=>'Active',
				'ap.name'=>$leave_type
			);
			$this->edb->where($where);
			$this->edb->join('approval_groups AS ag','ag.approval_process_id = ap.approval_process_id','INNER');
			$this->edb->join('employee AS e','e.emp_id = ag.emp_id','INNER');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','INNER');
			$query = $this->edb->get('approval_process AS ap');
			$result = $query->result();
			return $result;
		}else{
			return false;
		}
	}
	
	/**
	 * USE TO FILTER ALL APPROVERS EMAIL TO BE USED ON EMAIL CC
	 * Enter description here ...
	 * @param int $company_id
	 * @param string $leave_type
	 */
	public function filder_approvers_all_email($company_id,$leave_type='Leave Approval Group'){
		$approvers = $this->list_of_approvers($company_id,$leave_type);
		$email = array();
		if($approvers){
			foreach($approvers as $key=>$val){
				
				if($this->check_valid_email($val->email) == true) {
					$email[] = $val->email;	
				}
			
			}
		}
		return $email;
	}
	
	public function check_valid_email($address)
	{
		return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address)) ? FALSE : TRUE;
	}
	
		/**
	 * Get Token from Approval Leave
	 * @param unknown_type $leave_ids
	 * @param unknown_type $comp_id
	 * @param unknown_type $emp_id
	 */
	public function get_token($leave_ids,$comp_id,$emp_id){
		$w = array(
			"leave_id"=>$leave_ids,
			"comp_id"=>$comp_id,
			"emp_id"=>$emp_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_leave");
		$row = $q->row();
		return ($q->num_rows() > 0) ? $row->token : "" ;
	}
	
	/**
	 * MAO NI MO SEND OG SMS SA MGA APPROVERS PERO WALAY APIL ANG HR NA NI APPROVE OR NI REJECT GETS?
	 * Enter description here ...
	 * @param int $emp_id
	 * @param int $company_id
	 * @param int $message_receiver
	 * @param int $message_pms
	 */
	public function send_sms_message($company_id,$emp_id,$leave_type="",$approve_reject = ""){
		if($emp_id) {
			$check_noti_settings = get_notification_settings($company_id);
			if($check_noti_settings) {
				if($check_noti_settings->sms == 'Active') {
					$where_employee = array(
						"e.emp_id"	=> $emp_id,
						"e.status"	=> "Active",
						"a.deleted"	=> "0",
						"e.company_id"	=> $company_id
					);
					$this->edb->where($where_employee);
					$this->edb->select("a.account_id,e.first_name,e.last_name,a.email,e.emp_id,a.user_type_id");
					$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
					$query_employee = $this->edb->get("employee AS e");
					$profile_row = $query_employee->row();
					$query_employee->free_result();
					if($profile_row){
						$message_receiver = $profile_row->first_name." ".$profile_row->last_name."! Your ".$leave_type." has been ".$approve_reject." by HR";
						
						$ret = send_this_sms_global($company_id,$profile_row->account_id,$message_receiver);
							/*
							DISABLE NI NAKO KAY wala diay ni apil kong mo leave ang employee  nya gi approvahan
							ang employee ra e sendan og sms kaning sa ubos kay mura nig cc sa email kung 
							puhon gamiton uncomment lang
							$approvers = $this->list_of_approvers($company_id,'Leave Approval Group');
							if($approvers){
								foreach($approvers as $key=>$val){
									if($val->login_mobile_number !="") {
										if($val->emp_id != $this->session->userdata('emp_id')){
											 send_this_sms_global($company_id,$val->account_id,$message_pms);
										}
									}
								}
							}*/
						
					}else{
						return false;
					}
				}
			}
		}
	}
		
	public function get_leave_restriction($leave_type,$field="FALSE") {
		$select = array(
			'provide_half_day_option',
			'allow_to_apply_leaves_beyond_limit',
			'exclude_holidays',
			'exclude_weekends',
			'consecutive_days_after_weekend_holiday',
			'num_days_before_leave_application',
			'num_consecutive_days_allowed',
			'leave_units',
			'effective_start_date_by',
			'effective_start_date',
			'required_documents',
			'exclude_rest_days',
			'paid_leave',
			'requires_leave_credits',
			'what_happen_to_unused_leave',
			'leave_conversion_run_every',
			'carry_over_schedule_specific_month',
			'carry_over_schedule_specific_day',
			'carry_forward_expiry',
			'carry_forward_expiry_months',
		    'exclude_regular_holidays',
		    'exclude_special_holidays',
		    'partial_days_type',
		    'no_min_hours_allowed',
		    'no_duration_hours'
		);
		
		$this->db->where('leave_type_id',$leave_type);
		$query = $this->db->get('leave_type');
		$result = $query->result();
		if($field){
			if($result){
				foreach ($result as $r):
					$return = isset($r->$field) ? $r->$field : false;
				endforeach;
			}
		}
		return ($result) ? $return : false ;
	}
		
	public function get_leave_eff_date($leave_type,$comp_id,$emp_id,$field="FALSE"){
		$select = array(
				'effective_date'
		);
		$w = array(
				'leave_type_id' => $leave_type,
				'company_id' => $comp_id,
				'emp_id' => $emp_id
		);
			
		$this->db->where($w);
		$query = $this->db->get('employee_leaves');
		$result = $query->result();
		if($field){
			if($result){
				foreach ($result as $r):
				$return = $r->$field;
				endforeach;
			}
		}
		return ($result) ? $return : false ;
	}
	
/** added : fritz - start **/
	public function get_approval_settings_disable_status($comp_id) {
		$where = array(
				"company_id"	=> $comp_id,
				#"status"		=> "Active"
		);
		 
		$this->db->where($where);
		$q = $this->db->get("approval_settings");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
		 
	}

	public function new_update_employee_leaves($company_id, $emp_id, $leave_type_id, $total_leave_requested, $employee_leaves_application_id) {
	    if(is_numeric($company_id) && is_numeric($emp_id)) {
	        # get employee leaves first
	        
	        $query= $this->db->get_where("employee_leaves",array("company_id"=>$company_id,"leave_type_id"=>$leave_type_id,"emp_id"=>$emp_id,"status"=>"Active"));
	        $row = $query->row();
	        $query->free_result();
	        
	        $get_leave_restriction                 = $this->get_leave_restriction($leave_type_id,'requires_leave_credits');
	        $paid_leave                            = $this->get_leave_restriction($leave_type_id,'paid_leave');
	        $leave_conversion_run_every            = $this->get_leave_restriction($leave_type_id,'leave_conversion_run_every');
	        $carry_over_schedule_specific_month    = $this->get_leave_restriction($leave_type_id,'carry_over_schedule_specific_month');
	        $carry_over_schedule_specific_day      = $this->get_leave_restriction($leave_type_id,'carry_over_schedule_specific_day');
	        $carry_forward_expiry                  = $this->get_leave_restriction($leave_type_id,'carry_forward_expiry');
	        $carry_forward_expiry_months           = $this->get_leave_restriction($leave_type_id,'carry_forward_expiry_months');
	        $allow_negative_borrow_hours        = $this->get_leave_restriction($leave_type_id,'allow_negative_borrow_hours');
	        $allow_negative_borrow_unearned     = $this->get_leave_restriction($leave_type_id,'allow_negative_borrow_unearned');
	        $apply_limit_rest                   = $this->get_leave_restriction($leave_type_id,'allow_to_apply_leaves_beyond_limit');
	        $leave_units                        = $this->get_leave_restriction($leave_type_id,'leave_units');
	        
	        if($row) {
	            $leave_credits = $row->leave_credits; 				# LEAVE CREDITS
	            $previous_credits = $row->previous_leave_credits; 	# PREVIOUS CREDITS
	            $remaining_credits = $row->remaining_leave_credits;	# REMAINING CREDITS
	            
	          
	            $leave_type_info = $this->get_leave_type_info($leave_type_id, $company_id);
	            
	            $check_remaining = $remaining_credits == NULL ?  $leave_type_info->leave_credits : $remaining_credits;
	            
	            $this_guy_allow_nega = false;
	            if($apply_limit_rest == 'yes'){
	                if ($allow_negative_borrow_unearned == "yes") {
	                    #$check_remaining = $check_remaining;
	                    $this_guy_allow_nega = true;
	                }
	            }
	            
	            
	            
	            if($get_leave_restriction == "no" && $paid_leave == "no") {
	                $remaining = $row->remaining_leave_credits;
	            } else {
	                $remaining = floatval($check_remaining) - floatval($total_leave_requested);
	            }
	            
	            $check_previous_credits = $previous_credits !="" ? $previous_credits : $leave_credits;
	            
	            $result_previous = floatval($check_previous_credits) - floatval($total_leave_requested);
	            if($result_previous < 0) { # IF LESS THAN SA PREVIOUS
	                $result_previous = floatval($check_previous_credits);
	            }
	            
	            $credited_value = 0;
	            $non_credited = 0;
	            
	           /* p($remaining_credits);
	            echo "reamini " . $remaining  . " tl " . $total_leave_requested . " ck ".$check_remaining . " lc ". $leave_type_info->leave_credits;
	            echo "sdf"; 
	            exit();*/
	            
	            # get credited
	            if($this_guy_allow_nega) {
	                $borrowed_credits = $check_remaining + $allow_negative_borrow_hours;
	                if($borrowed_credits > 0) { #remaining > 3
	                    #gikuha total leave kong 5 , employee - 3 remainig - 4
	                    $credited_value_formula = floatval($borrowed_credits) - floatval($total_leave_requested);
	                    if($credited_value_formula <= 0){
	                        $credited_value = floatval($borrowed_credits);
	                        $non_credited = abs($credited_value_formula);
	                    }else{
	                        $credited_value = floatval($total_leave_requested);
	                        $non_credited = 0;
	                    }
	                }else{
	                    $non_credited = $total_leave_requested;
	                }
	            } else {
	                if($check_remaining > 0) { #remaining > 3
	                    #gikuha total leave kong 5 , employee - 3 remainig - 4
	                    $credited_value_formula = floatval($check_remaining) - floatval($total_leave_requested);
	                    if($credited_value_formula <= 0){
	                        $credited_value = floatval($check_remaining);
	                        $non_credited = abs($credited_value_formula);
	                    }else{
	                        $credited_value = floatval($total_leave_requested);
	                        $non_credited = 0;
	                    }
	                }else{
	                    $non_credited = $total_leave_requested;
	                }
	            }
	            # end credited
	            
	            $where = array(
	                "company_id"       => $company_id,
	                "emp_id"           => $emp_id,
	                "leave_type_id"    => $leave_type_id
	            );
	            
	            $field = array(
	                "remaining_leave_credits"  => $remaining,
	                "previous_leave_credits"	  => $result_previous,
	            );
	            $this->db->update("employee_leaves",$field,$where);
	            
	            // save to employee leave ledger
	            $eal_val = $this->leave_details($employee_leaves_application_id, $company_id);
	            $applied_json = array(
	                "date_filed"    => ($eal_val) ? date("Y-m-d", strtotime($eal_val->date_filed)) : "",
	                "shift_date"    => ($eal_val) ? date("Y-m-d", strtotime($eal_val->shift_date)) : "",
	                "start_date"    => ($eal_val) ? date("Y-m-d H:i:s", strtotime($eal_val->date_start)) : "",
	                "end_date"      => ($eal_val) ? date("Y-m-d H:i:s", strtotime($eal_val->date_end)) : "",
	                "credited"      => $credited_value,
	                "non_credited"  => $non_credited,
	                "total_leave_requested" => $total_leave_requested
	            );
	            
	            $applied_on = json_encode($applied_json);
	            
	            $approved_me = array(
	                'company_id'                => $company_id,
	                'leave_type_id'             => $leave_type_id,
	                'emp_id'                    => $emp_id,
	                'date'                      => date("Y-m-d H:i:s"),
	                'transaction_code'          => 'ealv', // earned leave
	                'application_date'          => $applied_on,
	                'description_fullname'      => 'employee approved leave balance',
	                'transaction'               => floatval($total_leave_requested),
	                'previous_balances'         => 0,
	                'balances'                  => $remaining,
	                'approved_leave'            => floatval($total_leave_requested),
	                'source'                    => 'employee apply',
	                'status'                    => 'Active',
	                'information'               => 'applied on employee ('.$employee_leaves_application_id.')'
	            );
	            
	            esave("employee_leave_ledger", $approved_me);
	            
	            // check payroll period >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	            $check_payroll_period = $this->check_payroll_period($company_id);
	            
	            $idates_now = idates_now();
	            if($check_payroll_period != FALSE){
	                $start_date = $this->check_leave_application($employee_leaves_application_id);
	                $payroll_period = $check_payroll_period->payroll_period;
	                $period_from = $check_payroll_period->period_from;
	                $period_to = $check_payroll_period->period_to;
	                $datenow = date("Y-m-d");
	                
	                if(strtotime($period_from) <= strtotime($start_date) && strtotime($start_date) <= strtotime($payroll_period)){
	                    if(strtotime($period_to) < strtotime($datenow) && strtotime($datenow) <= strtotime($payroll_period)) $idates_now = $period_to." ".date("H:i:s");
	                }
	            }
	            // end check payroll period >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	            
	            // check if workflow setting is disabled (auto reject or approve)
	            $get_approval_settings_disable_status = $this->get_approval_settings_disable_status($company_id);
	            
	            if($get_approval_settings_disable_status) {
	                if($get_approval_settings_disable_status->status == "Inactive") {
	                    $status = $get_approval_settings_disable_status->disabled_application_status;
	                } else {
	                    $status = "approve";
	                }
	            } else {
	                $status = "approve";
	            }
	            
	            # UPDATES THE EMPLOYEE LEAVE APPLICATIONS
	            $fields_ela = array(
	                "leave_application_status" 	=> $status,
	                "approval_date"				=> $idates_now,
	                "credited"					=> $credited_value,
	                "non_credited"				=> $non_credited,
	                "status"                     => "Active" //if payroll is locked and closed (inactive)
	            );
	            $where_ela = array(
	                "employee_leaves_application_id"	=> $employee_leaves_application_id,
	                "company_id"						=> $company_id
	            );
	            $this->leave->update_field("employee_leaves_application",$fields_ela,$where_ela);
	            # END UPDATES THE EMPLOYEE LEAVE APPLICATIONS
	            
	            // --------------------- APPROVE CHILD START ---------------------
	            $where_child = array(
	                "company_id"   => $company_id,
	                "leaves_id"    => $employee_leaves_application_id
	            );
	            
	            $get_the_child_leave = $this->get_the_child_leave($where_child);
	            $cont_tlr_hidden = $total_leave_requested;
	            
	            $used_credits_total = 0;
	            $new_credited_value = 0;
	            $new_non_credited_value = 0;
	            $days_cnt = 0;
	            $days_filed_cnt = count($get_the_child_leave);
	            
	            if($get_the_child_leave){
	                foreach ($get_the_child_leave as $child) {
	                    $check_for_working_hours  = $this->for_leave_hoursworked_ws($emp_id,$company_id,date("l",strtotime($child->shift_date)),$child->work_schedule_id);
	                    $per_day_credit           = $this->average_working_hours_per_day($company_id);
	                    $days_cnt++;
	                    
	                    
	                    
	                    
	                    
	                    
	                    
	                    if($days_filed_cnt == $days_cnt) { // for last loop
	                        if($check_remaining > 0) {
	                            $check_remaining = $check_remaining;
	                        } else {
	                            $used_credits = $cont_tlr_hidden - $used_credits_total;
	                            $check_remaining = 0;
	                        }
	                        
	                        if($used_credits > $check_remaining){
	                            $new_credited_value = $check_remaining;
	                            $new_non_credited_value = $used_credits - $check_remaining;
	                        }elseif($used_credits <= $check_remaining){
	                            $new_credited_value = $used_credits;
	                            $new_non_credited_value = 0;
	                        }
	                        
	                    } else {
	                        if($leave_units == "days"){
	                            $used_credits = $check_for_working_hours / $check_for_working_hours;
	                        }else{
	                            $used_credits = $check_for_working_hours / $per_day_credit;
	                        }
	                        
	                        if($used_credits > $check_remaining){
	                            $new_credited_value = $check_remaining;
	                            $new_non_credited_value = $used_credits - $check_remaining;
	                        }elseif($used_credits <= $check_remaining){
	                            $new_credited_value = $used_credits;
	                        }
	                        
	                        $check_remaining = $check_remaining - $new_credited_value;
	                        $used_credits_total = $used_credits_total + $used_credits;
	                    }
	                    
	                    if($new_credited_value < 0) {
	                        $new_credited_value = 0;
	                    }
	                    
	                    
	                    
	                    
	                    
	                    
	                    
	                    
	                    
	                    
	                    
	                    
	                    
	                    
	                   /* if($credited_value == 0) {
	                        $new_credited_value = 0;
	                    } else {
	                        if($child->non_credited == 0) {
	                            $new_credited_value = $child->credited;
	                        } else {
	                            $new_credited_value = 0;
	                        }
	                    }*/
	                    
	                    
	                    $where_child_up = array(
	                        "company_id" => $company_id,
	                        "leaves_id" => $employee_leaves_application_id,
	                        "employee_leaves_application_id"	=> $child->employee_leaves_application_id,
	                    );
	                    
	                    $update_child = array(
	                        "approval_date" => $idates_now,
	                        "leave_application_status" => $status,
	                        "credited" => $new_credited_value,
	                        "non_credited" => $new_non_credited_value,
	                        "status" => "Active" //if payroll is locked and closed (inactive)
	                    );
	                    
	                    $this->db->where($where_child_up);
	                    $this->db->update("employee_leaves_application",$update_child);
	                }
	            }
	            
	           /* $update_child = array(
	                "approval_date"				=> $idates_now,
	                "leave_application_status"     => $status,
	                "status"                       => "Active" //if payroll is locked and closed (inactive)
	            );
	            
	            $this->db->where($where_child);
	            $this->db->update("employee_leaves_application",$update_child);*/
	            // --------------------- APPROVE CHILD END ---------------------
	            
	            // --------------------- ADD APPROVED LEAVE HISTORY START ---------------------
	            
	            add_employee_history($company_id, $leave_type_id, $emp_id, $remaining, date("Y-m-d"));
	            
	            // --------------------- ADD APPROVED LEAVE HISTORY END ---------------------
	            
	            $we = number_format($check_remaining,3) .'-'. number_format($total_leave_requested,3);
	            return array("sumsa"=> $check_remaining."- ".$total_leave_requested."REMAINING".$remaining_credits."remaining val".$remaining."credited".$credited_value."calculation".$we);
	        } else {
	            return false;
	        }
	    } else {
	        return false;
	    }
	}
	
	public function get_the_child_leave($where_child) {
	    $this->db->where($where_child);
	    $this->db->order_by("employee_leaves_application_id","ASC");
	    $q = $this->db->get("employee_leaves_application");
        $r = $q->result();
        
        return ($r) ? $r : false;
	}
	
	public function for_leave_hoursworked_ws($emp_id,$comp_id,$workday,$work_schedule){
	    // for regular schedules and workshift
	    
	    $where_hw = array(
	        "work_schedule_id"=>$work_schedule,
	        "company_id"=>$comp_id,
	        "days_of_work"=>$workday
	    );
	    $this->db->where($where_hw);
	    $sql_hw = $this->db->get("regular_schedule");
	    $row_hw = $sql_hw->row();
	    
	    if($sql_hw->num_rows() > 0){
	        return $row_hw->total_work_hours;
	    }
	    
	    // for flexible
	    $where_f = array(
	        "work_schedule_id"=>$work_schedule,
	        "company_id"=>$comp_id
	    );
	    
	    $this->db->where($where_f);
	    $sql_f = $this->db->get("flexible_hours");
	    $row_f = $sql_f->row();
	    
	    if($sql_f->num_rows() > 0){
	        $duration_of_lunch_break_per_day = $row_f->duration_of_lunch_break_per_day / 60;
	        return $row_f->total_hours_for_the_day - $duration_of_lunch_break_per_day;
	    }
	}
	
	public function average_working_hours_per_day($company_id) {
	    $w = array (
	        "company_id" => $company_id,
	        "status" => "Active"
	    )
	    // "average_working_hours_per_day != " => NULL
	    ;
	    $this->db->where ( $w );
	    $this->db->where ( "average_working_hours_per_day IS NOT NULL" );
	    $q = $this->db->get ( "payroll_calendar_working_days_settings" );
	    $r = $q->row ();
	    $average_working_hours_per_day = ($r) ? $r->average_working_hours_per_day : 8;
	    return $average_working_hours_per_day;
	}
	
	/** fritz - end **/
	
	// ------------------------------------------------------------------------ //
	// ----------------------------- optimized ----------------------------- //
	// ------------------------------------------------------------------------ //
	
	/**
	 * todo leave count
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param string $search
	 * @param string $display
	 * @param string $from_to
	 * @param string $stats
	 * @param string $specific_emp_id
	 */
	public function get_todo_leave_count($company_id, $emp_id, $search = "", $display = "", $from_to = "", $stats = "", $specific_emp_id = ""){
		$konsum_key = konsum_key();
		
		$sort_array = array(
			"first_name",
			"pg_name",
			"date_filed",
			"leave_type",
			"date_start",
			"total_leave_requested",
			"approval_date"
		);
			
		$eselect = array(
			"a.account_id",
			"a.payroll_cloud_id",
			"e.first_name",
			"e.middle_name",
			"e.last_name"
		);
			
		$select = array(
			"el.employee_leaves_application_id",
			"el.leave_application_status",
			"el.leave_type_id",
			"el.company_id",
			"el.date_filed",
			"el.date_start",
			"el.date_end",
			"el.date_return",
			"el.required_file_documents",
			"el.total_leave_requested",
			"el.emp_id",
			"el.leave_application_status",
			"el.reasons",
			"el.note",
			"epi.leave_approval_grp",
			"d.department_name",
			"pg.name AS pg_name",
			"al.level AS leave_level",
			"empl.remaining_leave_credits AS remaining_c",
			"lt.leave_type",
			"el.work_schedule_id",
			"ws.work_type_name"
		);
			
		$ewhere = array(
			"el.deleted" => '0',
			"a.user_type_id" => "5",
			"e.status" => "Active",
			"al.status" => "Active"
		);
			
		$where = array(
			"el.company_id" =>$company_id,
			"el.leave_application_status" => "pending",
		    "lt.status" => "Active"
			//"al.approve_by_head" => "No",
			//"al.approve_by_hr" => "No"
		);
			
		if($stats != ""){
			//$filter = $this->filter_todo_leave($company_id, $stats);
			//$filter = ($filter) ? $filter : array("");
				
			//$this->db->where_in("el.employee_leaves_application_id", $filter);
			if($stats == "late"){
				$this->db->where(array("el.flag_payroll_correction" => "yes"));
			}
			elseif ($stats == "current"){
				$this->db->where(array("el.flag_payroll_correction" => "no"));
			}
		}
			
		$this->db->select($select);
		$this->edb->select($eselect);
			
		//$this->db->where($where);
		//$this->edb->where($ewhere);
			
		//if($specific_emp_id != ""){
		//	$this->db->where(array("el.emp_id" => $specific_emp_id));
		//}
			
		if($search != "" && $search != "all"){
			$this->db->where("(
					convert(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')using latin1) LIKE '%".$search."%' OR
					convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$search."%')", NULL, FALSE);
		}
		
		if($specific_emp_id != ""){
		    $this->db->where(array("el.emp_id" => $specific_emp_id));
		}
			
		if($display != "" && ($display == "week" || $display == "day")){
			if($display == "day" && strtotime($from_to)){
				$this->db->where(array("date_filed" => date("Y-m-d",strtotime($from_to))));
			}
	
			if($display == "week" && $from_to != ""){
				$d = explode("_", $from_to);
				if(strtotime($d[0]) && strtotime($d[1])){
					$this->db->where(array("date_filed <=" => date("Y-m-d",strtotime($d[1])), "date_filed >=" => date("Y-m-d",strtotime($d[0]))));
				}
			}
		}
			
		// IF NOT COMPANY OWNER
		if($emp_id != "-99{$company_id}"){
			$this->db->select(array(
				"ag.approval_group_id",
				"ag.emp_id"
			));
			$this->db->where(array(
				"ag.emp_id" => $emp_id,
				"ap.name" => "Leave"
			));
		}
		
		$this->db->where($where);
		$this->edb->where($ewhere);
		
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = el.emp_id","LEFT");
		$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$this->edb->join("employee_leaves AS empl","empl.leave_type_id = el.leave_type_id AND empl.emp_id = e.emp_id","LEFT");
		
		$this->db->join("work_schedule AS ws","ws.work_schedule_id = el.work_schedule_id","LEFT");
		$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
		$this->db->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
		$this->db->join("approval_groups_via_groups AS agg","agg.approval_groups_via_groups_id = epi.leave_approval_grp","LEFT");
	
		// IF NOT COMPANY OWNER
		if($emp_id != "-99{$company_id}"){
			$this->db->join("approval_groups AS ag", "ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND al.level <= ag.level", "LEFT");
			$this->db->join("approval_process AS ap", "ap.approval_process_id = ag.approval_process_id","LEFT");
		}
		
		$this->db->group_by("el.employee_leaves_application_id");
		$query = $this->edb->get("employee_leaves_application AS el");
		$result = $query->result();
	
		return ($result) ? $query->num_rows() : 0;
	}
	
	/**
	 * 
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param number $limit
	 * @param number $start
	 * @param string $search
	 * @param string $sort_by
	 * @param string $sort
	 * @param string $display
	 * @param string $from_to
	 * @param string $stats 
	 * @param string $specific_emp_id
	 */
	public function get_todo_leave($company_id, $emp_id, $limit = 10, $start = 0, $search = "", $sort_by = "", $sort = "ASC",$display = "", $from_to = "", $stats = "", $specific_emp_id = ""){
		$konsum_key = konsum_key();
		
		$start = intval($start);
		$limit = intval($limit);
		$sort_array = array(
			"first_name",
			"pg_name",
			"date_filed",
			"leave_type",
			"date_start",
			"total_leave_requested",
			"approval_date"
		);
		
		$eselect = array(
			"a.account_id",
			"a.payroll_cloud_id",
			"e.first_name",
			"e.middle_name",
			"e.last_name"
		);
		
		$select = array(
			"el.employee_leaves_application_id",
			"el.leave_application_status",
			"el.leave_type_id",
			"el.company_id",
			"el.date_filed",
			"el.date_start",
			"el.date_end",
			"el.date_return",
			"el.required_file_documents",
			"el.total_leave_requested",
		    "el.shift_date", // added 02272019
		    "el.for_resend_auto_rejected_id", // added 02272019
			"el.emp_id",
			"el.leave_application_status",
			"el.reasons",
			"el.note",
			"el.leave_cedits_for_untitled",
			"epi.leave_approval_grp",
			"d.department_name",
			"pg.name AS pg_name",
			"al.level AS leave_level",
			"empl.remaining_leave_credits AS remaining_c",
			"empl.leave_credits",
			"lt.leave_type",
			"el.work_schedule_id",
			"ws.work_type_name",
		    "el.cancellable"
		);
		
		$ewhere = array(
			"el.deleted" => '0',
 			"a.user_type_id" => "5",
			"e.status" => "Active",
			"al.status" => "Active"
		);	
		
		$where = array(
			"el.company_id" =>$company_id,
			"el.leave_application_status" => "pending",
		    "lt.status" => "Active"
			//"al.approve_by_head" => "No",
			//"al.approve_by_hr" => "No"
		);
		
		if($stats != ""){
			//$filter = $this->filter_todo_leave($company_id, $stats);
			//$filter = ($filter) ? $filter : array("");
				
			//$this->db->where_in("el.employee_leaves_application_id", $filter);
			if($stats == "late"){
				$this->db->where(array("el.flag_payroll_correction" => "yes"));
			}
			elseif ($stats == "current"){
				$this->db->where(array("el.flag_payroll_correction" => "no"));
			}
		}
		
		$this->db->select($select);
		$this->edb->select($eselect);
		
		//$this->db->where($where);
		//$this->edb->where($ewhere);
		
		//if($specific_emp_id != ""){
		//	$this->db->where(array("el.emp_id" => $specific_emp_id));
		//}
		
		if($search != "" && $search != "all"){
			$this->db->where("(
				convert(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')using latin1) LIKE '%".$search."%' OR
				convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$search."%')", NULL, FALSE);
		}
		
		if($specific_emp_id != ""){
		    $this->db->where(array("el.emp_id" => $specific_emp_id));
		}
		
		if($display != "" && ($display == "week" || $display == "day")){
			if($display == "day" && strtotime($from_to)){
				$this->db->where(array("date_filed" => date("Y-m-d",strtotime($from_to))));
			}
			
			if($display == "week" && $from_to != ""){
				$d = explode("_", $from_to);
				if(strtotime($d[0]) && strtotime($d[1])){
					$this->db->where(array("date_filed <=" => date("Y-m-d",strtotime($d[1])), "date_filed >=" => date("Y-m-d",strtotime($d[0]))));
				}
			}
		}
		
		// IF NOT COMPANY OWNER
		if($emp_id != "-99{$company_id}"){
			$this->db->select(array(
				"ag.approval_group_id",
				"ag.emp_id"
			));
			$this->db->where(array(
				"ag.emp_id" => $emp_id,
				"ap.name" => "Leave"
			));
		}
		
		$this->db->where($where);
		$this->edb->where($ewhere);
		
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = el.emp_id","LEFT");
		$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$this->edb->join("employee_leaves AS empl","empl.leave_type_id = el.leave_type_id AND empl.emp_id = e.emp_id","LEFT");
		
		$this->db->join("work_schedule AS ws","ws.work_schedule_id = el.work_schedule_id","LEFT");
		$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
		$this->db->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
		$this->db->join("approval_groups_via_groups AS agg","agg.approval_groups_via_groups_id = epi.leave_approval_grp","LEFT");
		
		// IF NOT COMPANY OWNER
		if($emp_id != "-99{$company_id}"){
			$this->db->join("approval_groups AS ag", "ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND al.level <= ag.level", "LEFT");
			$this->db->join("approval_process AS ap", "ap.approval_process_id = ag.approval_process_id","LEFT");
		}
		
		if($sort_by != ""){
			if(in_array($sort_by, $sort_array)){
				$this->db->order_by($sort_by,$sort);
			}
		}
		else{
			$this->db->order_by("date_filed","DESC");
			$this->db->order_by("employee_leaves_application_id","DESC");
		}
		
		$this->db->group_by("el.employee_leaves_application_id");
		$query = $this->edb->get("employee_leaves_application AS el", $limit, $start);
		$result = $query->result();

		return $result;
	}
	
	/**
	 */
	public function filter_todo_leave($company_id, $get_status = "current"){
		$konsum_key = konsum_key();
		$emp_leave_application_id_current = array();
		$emp_leave_application_id_late = array();
		
		$eselect = array(
			"a.account_id",
			"a.payroll_cloud_id",
			"e.first_name",
			"e.middle_name",
			"e.last_name"
		);
			
		$select = array(
			"el.employee_leaves_application_id",
			"el.leave_application_status",
			"el.leave_type_id",
			"el.company_id",
			"el.date_filed",
			"el.date_start",
			"el.date_end",
			"el.date_return",
			"el.required_file_documents",
			"el.total_leave_requested",
			"el.emp_id",
			"el.leave_application_status",
			"el.reasons",
			"el.note",
			"epi.leave_approval_grp",
			"d.department_name",
			"pg.name AS pg_name",
			"al.level AS leave_level"
		);
			
		$ewhere = array(
			"el.deleted" => '0',
			"a.user_type_id" => "5",
			"e.status" => "Active",
			"al.status" => "Active"
		);
			
		$where = array(
			"el.company_id" => $company_id,
			"el.leave_application_status" => "pending"
			//"al.approve_by_head" => "No",
			//"al.approve_by_hr" => "No"
		);
			
		$this->db->select($select);
		$this->edb->select($eselect);
			
		$this->db->where($where);
		$this->edb->where($ewhere);
		
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = el.emp_id","LEFT");
		$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$this->db->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
		$this->db->join("approval_groups_via_groups AS agg","agg.approval_groups_via_groups_id = epi.leave_approval_grp","LEFT");
			
		$this->db->group_by("el.employee_leaves_application_id");
		$query = $this->edb->get("employee_leaves_application AS el");
		$result = $query->result();
	
		if($result){
			foreach ($result as $row){
				$if_has_payrun = $this->check_status($row->emp_id, $company_id, $row->date_filed);
				if($if_has_payrun){
					array_push($emp_leave_application_id_late, $row->employee_leaves_application_id);
				}
				else{
					array_push($emp_leave_application_id_current, $row->employee_leaves_application_id);
				}
			}
		}
		
		return ($get_status == "current") ? $emp_leave_application_id_current : $emp_leave_application_id_late;
	}
	
	/**
	 * count todo leave
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param string $specific_emp_id
	 */
	public function count_todo_leave_old($company_id, $emp_id, $specific_emp_id = ""){
		$eselect = array(
			"a.account_id",
			"a.payroll_cloud_id",
			"e.first_name",
			"e.middle_name",
			"e.last_name"
		);
		
		$select = array(
			"el.employee_leaves_application_id",
			"el.leave_application_status",
			"el.leave_type_id",
			"el.company_id",
			"el.date_filed",
			"el.date_start",
			"el.date_end",
			"el.date_return",
			"el.required_file_documents",
			"el.total_leave_requested",
			"el.emp_id",
			"el.leave_application_status",
			"el.reasons",
			"el.note",
			"epi.leave_approval_grp",
			"d.department_name",
			"pg.name AS pg_name",
			"al.level AS leave_level"
		);
		
		$ewhere = array(
			"el.deleted" => '0',
			"a.user_type_id" => "5",
			"e.status" => "Active",
			"al.status" => "Active"
		);
		
		$where = array(
			"el.company_id" => $company_id,
			"el.leave_application_status" => "pending",
			"ag.emp_id" => $emp_id
			//"al.approve_by_head" => "No",
			//"al.approve_by_hr" => "No"
		);
		
		if($specific_emp_id != ""){
			$this->db->where(array("el.emp_id" => $specific_emp_id));
		}
		
		$this->db->select($select);
		$this->edb->select($eselect);
		
		$this->db->where($where);
		$this->edb->where($ewhere);
			
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = el.emp_id","LEFT");
		$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$this->db->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
		$this->db->join("approval_groups_via_groups AS agg","agg.approval_groups_via_groups_id = epi.leave_approval_grp","LEFT");
		$this->db->join("approval_groups AS ag", "ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND al.level = ag.level", "LEFT");
		$this->db->join("approval_process AS ap", "ap.approval_process_id = ag.approval_process_id","LEFT");
		$this->db->group_by("el.employee_leaves_application_id");
		$query = $this->edb->get("employee_leaves_application AS el");
		$result = $query->result();
		
		$late = 0;
		$current = 0;
		
		if($result){
			foreach ($result as $row){
				$if_has_payrun = $this->check_status($row->emp_id, $company_id, $row->date_filed);
				if($if_has_payrun){
					$late = $late + 1;
				}
				else{
					$current = $current + 1;
				}
			}
		}
			
		$ars = array(
			"current" => $current,
			"late" => $late
		);
		
		return $ars;
	}
	
	/**
	 * count todo leave
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param string $specific_emp_id
	 */
	public function count_todo_leave($company_id, $emp_id, $specific_emp_id = "", $stats = ""){
		$count = 0;
		$konsum_key = konsum_key();
			
		$eselect = array(
			"a.account_id",
			"a.payroll_cloud_id",
			"e.first_name",
			"e.middle_name",
			"e.last_name"
		);
			
		$select = array(
			"el.employee_leaves_application_id",
			"el.leave_application_status",
			"el.leave_type_id",
			"el.company_id",
			"el.date_filed",
			"el.date_start",
			"el.date_end",
			"el.date_return",
			"el.required_file_documents",
			"el.total_leave_requested",
			"el.emp_id",
			"el.leave_application_status",
			"el.reasons",
			"el.note",
			"epi.leave_approval_grp",
			"d.department_name",
			"pg.name AS pg_name",
			"al.level AS leave_level",
			"empl.remaining_leave_credits AS remaining_c",
			"lt.leave_type"
		);
			
		$ewhere = array(
			"el.deleted" => '0',
			"a.user_type_id" => "5",
			"e.status" => "Active",
			"al.status" => "Active"
		);
			
		$where = array(
			"el.company_id" =>$company_id,
			"el.leave_application_status" => "pending"
		);
			
		if($stats != ""){
			if($stats == "late"){
				$this->db->where(array("el.flag_payroll_correction" => "yes"));
			}
			elseif ($stats == "current"){
				$this->db->where(array("el.flag_payroll_correction" => "no"));
			}
		}
			
		$this->db->select($select);
		$this->edb->select($eselect);
			
		$this->db->where($where);
		$this->edb->where($ewhere);
			
		if($specific_emp_id != ""){
			$this->db->where(array("el.emp_id" => $specific_emp_id));
		}
			
		// IF NOT COMPANY OWNER
		if($emp_id != "-99{$company_id}"){
			$this->db->select(array(
				"ag.approval_group_id",
				"ag.emp_id"
			));
			$this->db->where(array(
				"ag.emp_id" => $emp_id,
				"ap.name" => "Leave"
			));
		}
			
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = el.emp_id","LEFT");
		$this->edb->join("employee AS e","e.emp_id = el.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$this->edb->join("employee_leaves AS empl","empl.leave_type_id = el.leave_type_id AND empl.emp_id = e.emp_id","LEFT");
			
		$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
		$this->db->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
		$this->db->join("approval_groups_via_groups AS agg","agg.approval_groups_via_groups_id = epi.leave_approval_grp","LEFT");
	
		// IF NOT COMPANY OWNER
		if($emp_id != "-99{$company_id}"){
			$this->db->join("approval_groups AS ag", "ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND al.level <= ag.level", "LEFT");
			$this->db->join("approval_process AS ap", "ap.approval_process_id = ag.approval_process_id","LEFT");
		}
		
		$this->db->group_by("el.employee_leaves_application_id");
		$query = $this->edb->get("employee_leaves_application AS el");
		$result = $query->result();
			
		if($result){
			foreach ($result as $approvers){
				$current_approver_id = $emp_id;
				$leave_approval_grp = intval($approvers->leave_approval_grp);
				$level = $approvers->leave_level;
				
				$his_turn = $this->his_turn($leave_approval_grp, $level, $current_approver_id, $company_id);
				if($his_turn){
					$count = $count + 1;
				}
			}
		}
		//$count = ($count > 15) ? "15+" : $count;
		
		return $count;
	}
	
	/**
	 * check date
	 * @param unknown $emp_id
	 * @param unknown $comp_id
	 * @param unknown $gDate
	 */
	public function check_status($emp_id, $comp_id, $gDate){
		$gDate = date("Y-m-d",strtotime($gDate));
		$return_void = false;
		$stat_v1 = "";
	
		$w = array(
			'prc.emp_id' => $emp_id,
			'prc.company_id' => $comp_id,
			'prc.period_from <=' => $gDate,
			'prc.period_to >=' => $gDate,
			'prc.status' => 'Active'
		);
		$s = array(
			'dpr.view_status'
		);
		$this->db->select($s);
		$this->db->where($w);
		$this->db->join("draft_pay_runs as dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id");
		$q = $this->db->get('payroll_run_custom as prc');
		$r = $q->row();
		if($r){
			$stat_v = $r->view_status;
			if($stat_v == "Waiting for approval" || $stat_v == "Closed"){
				$return_void = true;
			}
		}
		else{
			$w1 = array(
				#'epi.emp_id' => $emp_id,
				'pp.emp_id' => $emp_id,
				'dpr.company_id' => $comp_id,
				'dpr.period_from <=' => $gDate,
				'dpr.period_to >=' => $gDate,
				'dpr.status' => 'Active'
			);
			$s1 = array(
				'dpr.view_status'
			);
			$this->db->select($s1);
			$this->db->where($w1);
			#$this->db->join("draft_pay_runs as dpr","dpr.payroll_group_id = epi.payroll_group_id");
			#$q1 = $this->db->get('employee_payroll_information as epi');
				
			$this->db->join("payroll_payslip AS pp","dpr.payroll_group_id = pp.payroll_group_id AND dpr.pay_period = pp.pay_date","LEFT");
			$q1 = $this->db->get('draft_pay_runs as dpr');
			$r1 = $q1->row();
			if($r1){
				$stat_v = $r1->view_status;
				if($stat_v == "Waiting for approval" || $stat_v == "Closed"){
					$return_void = true;
				}
			}
		}	
		return $return_void;
	}
	
	/**
	 * check his turn
	 * @param unknown $agvg
	 * @param unknown $level
	 * @param unknown $emp_id
	 */
	public function his_turn($agvg, $level, $emp_id, $company_id = ""){
		$return = false;
		$agvg_exist = $this->check_workflow_exist($agvg, $company_id);
		
		if((!$agvg_exist && $emp_id == "-99{$company_id}") || ($agvg == "0" && $emp_id == "-99{$company_id}")){
			$return = true;
		}
		else{
			$where = array(
				"ag.emp_id" => $emp_id,
				"ag.level" => $level,
				"ag.approval_groups_via_groups_id" => $agvg,
				"ap.name" => "Leave"
			);
			$this->db->where($where);
			//$this->db->join("approval_groups_via_groups AS agvg", "agvg.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","INNER");
			$this->db->join("approval_process AS ap", "ap.approval_process_id = ag.approval_process_id","LEFT");
			$query = $this->db->get("approval_groups AS ag");
			$row = $query->row();
		
			$return =  ($row) ? true : false;
		}
		
		return $return;
	}
	
	
	/**
	 * to save correction if late
	 * @param unknown $company_id
	 * @param unknown $employee_leave_application_id
	 */
	public function save_correction($company_id, $employee_leave_application_id){
		// main
		$where = array(
			"employee_leaves_application_id" => $employee_leave_application_id,
			"company_id" => $company_id
		);
		$this->db->where($where);
		$query = $this->db->get("employee_leaves_application");
		$row = $query->row();
		
		if($row){
			$parent = array(
				"employee_leaves_application_id" => $employee_leave_application_id,
				"company_id" => $company_id,
				"emp_id" => $row->emp_id,
				"leave_type_id" => $row->leave_type_id,
				"reasons" => $row->reasons,
				"date_start" => $row->date_start,
				"date_end" => $row->date_end,
				"date_return" => $row->date_return,
				"date_filed" => $row->date_filed,
				"note" => $row->note,
				"approved_by_head" => $row->approved_by_head,
				"duration" => $row->duration,
				"total_leave_requested" => $row->total_leave_requested,
				"leave_cedits_for_untitled" => $row->leave_cedits_for_untitled,
				"leave_application_status" => $row->leave_application_status,
				"attachments" => $row->attachments,
				"approval_date" => $row->approval_date,
				"leaves_id" => $row->leaves_id,
				"flag_parent" => $row->flag_parent,
				"credited" => $row->credited,
				"non_credited" => $row->non_credited,
				"remaining_credits" => $row->remaining_credits,
				"timestamp_paid_leave" => $row->timestamp_paid_leave,
				"required_file_documents" => $row->required_file_documents,
				"cancel_reason" => $row->cancel_reason,
				"date_cancel" => $row->date_cancel,
				"status" => "Active",
				"deleted" => $row->deleted,
				#"approver_account_id" => $row->approver_account_id,
				"existing_leave_used_to_date" => $row->existing_leave_used_to_date,
			    "previous_credits" => $row->previous_credits,
			    "shift_date" => $row->shift_date,
			    "work_schedule_id" => $row->work_schedule_id
			);
			$parent_save = $this->db->insert("employee_leaves_application_correction", $parent);
		}
		
		// breakdown
		$where2 = array(
			"leaves_id" => $employee_leave_application_id,
			"company_id" => $company_id,
		);
		$this->db->where("flag_parent IS NULL");
		$this->db->where($where2);
		$query2 = $this->db->get("employee_leaves_application");
		$result = $query2->result();
		
		if($result){
			foreach ($result as $res){
				$breakown = array(
					"employee_leaves_application_id" => $res->employee_leaves_application_id,
					"company_id" => $res->company_id,
					"emp_id" => $res->emp_id,
					"leave_type_id" =>$res->leave_type_id,
					"reasons" =>$res->reasons,
					"date_start" =>$res->date_start,
					"date_end" =>$res->date_end,
					"date_return" =>$res->date_return,
					"date_filed" =>$res->date_filed,
					"note" =>$res->note,
					"approved_by_head" =>$res->approved_by_head,
					"duration" =>$res->duration,
					"total_leave_requested" =>$res->total_leave_requested,
					"leave_cedits_for_untitled" =>$res->leave_cedits_for_untitled,
					"leave_application_status" =>$res->leave_application_status,
					"attachments" =>$res->attachments,
					"approval_date" =>$res->approval_date,
					"leaves_id" =>$res->leaves_id,
					"flag_parent" =>$res->flag_parent,
					"credited" =>$res->credited,
					"non_credited" =>$res->non_credited,
					"remaining_credits" =>$res->remaining_credits,
					"timestamp_paid_leave" =>$res->timestamp_paid_leave,
					"required_file_documents" =>$res->required_file_documents,
					"cancel_reason" =>$res->cancel_reason,
					"date_cancel" =>$res->date_cancel,
					"status" => "Active",
					"deleted" =>$res->deleted,
					#"approver_account_id" =>$res->approver_account_id,
				    "existing_leave_used_to_date" => $res->existing_leave_used_to_date,
				    "previous_credits" => $res->previous_credits,
					"shift_date" =>$res->shift_date,
					"work_schedule_id" =>$res->work_schedule_id
				);
				$parent_save = $this->db->insert("employee_leaves_application_correction", $breakown);
			}
		}
	}
	
	/**
	 * for notify payroll admin
	 * @param unknown $company_id
	 */
	public function get_payroll_admin_hr($psa_id){
		$select = array(
			"a.account_id",
			"e.emp_id"
		);
		$eselect = array(
			"e.first_name",
			"e.last_name",
			"a.email"
		);
		$where = array(
			"a.payroll_system_account_id" => $psa_id,
			"a.enable_generic_privilege" => "Active",
			"a.user_type_id" => "3"
		);
		$this->db->select($select);
		$this->edb->select($eselect);
		$this->db->where($where);
		$this->edb->join("employee AS e","e.account_id = a.account_id","INNER");
		$query = $this->edb->get("accounts AS a");
		$result = $query->result();
		
		return ($result) ? $result : false;
	}
	
	/**
	 * check workflow exist
	 * @param unknown $agvg
	 * @param unknown $company_id
	 */
	public function check_workflow_exist($agvg, $company_id){
		$where1 = array(
			"approval_groups_via_groups_id" => $agvg, 
			"company_id" => $company_id
		);
		$this->db->where($where1);
		$query = $this->db->get("approval_groups_via_groups");
		$row = $query->row();
		
		return ($row) ? true : false;
	} 
	
	/**
	 * to get default leave workflow
	 * @param unknown $company_id
	 */
	public function get_default_approval_group($company_id){
		$where = array(
			"ag.emp_id" => "-99{$company_id}",
			"ag.company_id" => $company_id,
			"ap.name" => "Leave"
		);
		$this->db->where($where);
		$this->db->join("approval_groups_via_groups AS agvg", "agvg.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","INNER");
		$this->db->join("approval_process AS ap", "ap.approval_process_id = ag.approval_process_id","LEFT");
		$query = $this->db->get("approval_groups AS ag");
		$row = $query->row();
		
		return ($row) ? $query->row() : false;
	}
	
	/**
	 * Leave Application
	 * @param unknown_type $leave_ids
	 * @param unknown_type $comp_id
	 */
	public function get_leave_info($employee_leaves_application, $company_id){
		$select = array(
			'e.first_name',
			'e.last_name',
			'ela.date_filed',
			'lt.leave_type',
			'ela.reasons',
			'ela.date_start',
			'ela.date_end',
			'ela.date_return',
			'ela.total_leave_requested',
			'ela.note',
			'a.email',
			'a.account_id',
			'e.emp_id',
			'epi.leave_approval_grp',
			'ela.company_id',
			'al.level',
			'ela.leave_type_id',
		    "ela.shift_date", // added 02272019
		    "ela.for_resend_auto_rejected_id", // added 02272019
			"a.ns_add_logs_email_flag",
			"a.ns_timesheet_adj_email_flag",
			"a.ns_mobile_clockin_email_flag",
			"a.ns_change_shift_email_flag",
			"a.ns_leave_email_flag",
			"a.ns_overtime_email_flag",
			"a.ns_document_email_flag",
			"a.ns_termination_email_flag",
			"a.ns_end_of_year_email_flag",
			"a.ns_payroll_reminder_email_flag",
			"a.ns_birthday_email_flag",
			"a.ns_anniversary_email_flag",
			"a.ns_track_email_flag",
		);
		$where = array(
			'ela.employee_leaves_application_id' => $employee_leaves_application,
			'ela.company_id' => $company_id
		);
		$this->edb->select($select);
		$this->edb->where($where);
		$this->edb->join('employee AS e','ela.emp_id = e.emp_id','LEFT');
		$this->edb->join('employee_payroll_information AS epi','e.emp_id = epi.emp_id','LEFT');
		$this->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
		$this->edb->join('leave_type AS lt','ela.leave_type_id = lt.leave_type_id','left');
		$this->edb->join('approval_leave AS al','ela.employee_leaves_application_id = al.leave_id','left');
		$q = $this->edb->get('employee_leaves_application AS ela');
		$row = $q->row();
			
		return ($row) ? $row : false ;
	}
	
	/**
	 * check if for default approver
	 * @param unknown $agvg
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function is_default_approver($agvg, $emp_id, $company_id){
		$return = false;
		$agvg_exist = $this->check_workflow_exist($agvg, $company_id);
			
		if((!$agvg_exist && $emp_id == "-99{$company_id}") || ($agvg == "0" && $emp_id == "-99{$company_id}")){
			$return = true;
		}
		
		return $return;
	}
	
	// ------------------------------------------------------------------------ //
	// ----------------------------- optimized 2 ----------------------------- //
	// ------------------------------------------------------------------------ //
	
	/**
	 * track who's next approver employee or owner
	 * @param unknown $leave_appr_grp
	 * @param unknown $level
	 */
	public function track_next_approver_optimized($company_id = ""){
		$next_approver = array();
		$where = array(
			//"ag.level" => $level,
			//"ag.approval_groups_via_groups_id" => $leave_appr_grp,
			"ap.name" => "Leave",
			"agvg.company_id" => $company_id
		);
		$this->db->where($where);
		$this->db->join("approval_groups_via_groups AS agvg","agvg.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
		$this->db->join("approval_process AS ap", "ap.approval_process_id = ag.approval_process_id", "LEFT");
		$query = $this->db->get("approval_groups AS ag");
		$result = $query->result();
		if($result){
			foreach ($result as $res){
				if($res->emp_id == "-99{$res->company_id}"){
					$owner_where = array(
						"accounts.user_type_id" => "2",
						"assigned_company.company_id" => $res->company_id
					);
					$this->edb->where($owner_where);
					$this->edb->join("company_owner","company_owner.account_id = accounts.account_id","INNER");
					$this->edb->join("payroll_system_account","accounts.account_id = payroll_system_account.account_id","INNER");
					$this->edb->join("assigned_company","payroll_system_account.payroll_system_account_id = assigned_company.payroll_system_account_id","INNER");
					$this->edb->join("company","assigned_company.company_id = company.company_id","INNER");
					$owner_query = $this->edb->get("accounts");
					$owner_row = $owner_query->row();
					if($owner_row){
						array_push($next_approver,
							array(
								"custom_search" => "search_{$res->level}_agvg_{$res->approval_groups_via_groups_id}",
								"approver_name" => ucwords($owner_row->first_name." ".$owner_row->last_name)
							)		
						);
					}
				}
				else{
					$emp_where = array(
						"emp_id" => $res->emp_id
					);
					$this->edb->where($emp_where);
					$emp_query = $this->edb->get("employee");
					$emp_row = $emp_query->row();
					if($emp_row){
						//array_push($next_approver,ucwords($emp_row->first_name." ".$emp_row->last_name));
						array_push($next_approver,
							array(
								"custom_search" => "search_{$res->level}_agvg_{$res->approval_groups_via_groups_id}",
								"approver_name" => ucwords($emp_row->first_name." ".$emp_row->last_name)
							)
						);
					}
				}
			}
		}
		$next_approver = array_unique($next_approver, SORT_REGULAR);
	
		return $next_approver;
	}
}
	
/* End of file Approve_leave_model */
/* Location: ./application/models/hr/Approve_leave_model.php */;
	