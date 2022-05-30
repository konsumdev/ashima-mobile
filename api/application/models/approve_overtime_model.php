<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Approve LEave model for approving overtime , leaves , loans
 *
 * @category Model
 * @version 1.0
 * @author Christopher Cuizon <christophercuizons@gmail.com>
 * @revised by Reyneil Lato <reyneil_27@yahoo.com>
 */
class Approve_overtime_model extends CI_Model {
	/**
	 * 
	 * Listing of overtime applications with search function
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $limit
	 * @param unknown_type $start
	 * @param unknown_type $search
	 * @param unknown_type $sort_by
	 * @param unknown_type $sort
	 */
	public function search_overtime($company_id, $emp_id ,$limit=10, $start=0, $search = "", $sort_by = "", $sort = "ASC"){
		if(is_numeric($company_id)){
			$konsum_key = konsum_key();
			$filter = $this->not_in_search_overtime($company_id, $emp_id, $search);

			$sort_array = array(
				"first_name",
				"pg_name",
				"overtime_date_applied",
				"overtime_from",
				"no_of_hours",
				"with_nsd_hours",
				"overtime_status",
				"approval_date"
			);
			$where = array(
				'o.company_id' => $company_id,
				'o.deleted'	=> '0',
				"ag.emp_id" => $emp_id,
				"o.overtime_status" => "pending" 
			);
			$where2 = array(
				"ao.level !=" => ""
			);
			$select = array(
				"*",
				"pg.name AS pg_name"
			);
			if($search != "" && $search != "all"){
				$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
			}
			$this->db->select($select);
			$this->edb->where($where);
			$this->db->where($where2);
			
			if($filter != FALSE){
				$this->db->where("o.overtime_id NOT IN ({$filter})");
			}
			
			$this->edb->join('employee AS e','e.emp_id = o.emp_id','left');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
			$this->edb->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
			$this->db->group_by("o.overtime_id");
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			}
			else{
				$this->db->order_by("o.overtime_date_applied","DESC");
				$this->db->order_by("o.overtime_id","DESC");
			}
			$query = $this->edb->get('employee_overtime_application AS o',$limit,$start);	
			$result = $query->result();
			
			return $result;
		}else{
			return false;
		}
	}
	
	/**
	 * 
	 * Filter overtime ids for exclusion
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $search
	 */
	public function not_in_search_overtime($company_id, $emp_id, $search = ""){
		if(is_numeric($company_id)){
			$konsum_key = konsum_key();
			$where = array(
				'o.company_id' => $company_id,
				'o.deleted'	=> '0',
				"ag.emp_id" => $emp_id,
				"o.overtime_status" => "pending" 
			);
			$where2 = array(
				"ao.level !=" => ""
			);
			if($search != "" && $search != "all"){
				$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
			}
			$this->edb->where($where);
			$this->db->where($where2);
			$this->edb->join('employee AS e','e.emp_id = o.emp_id','left');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
			$this->edb->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
			$this->db->group_by("o.overtime_id");
			$query = $this->edb->get('employee_overtime_application AS o');	
			$result = $query->result();
			
			$arrs = array();
			if($result){
				$is_assigned = true;
				$workforce_notification = get_workforce_notification_settings($company_id);
				foreach($result as $key => $approvers){
					$overtime_approval_grp = $approvers->overtime_approval_grp;
					$level = $approvers->level;
					$check = $this->check_assigned_overtime($overtime_approval_grp, $level);
					if($workforce_notification->option == "choose level notification"){ 
						$is_assigned = check_if_is_level($level, $workforce_notification->workforce_alerts_notification_id);
					}
					$is_done = $this->is_done($overtime_approval_grp, $level);
					//if(!$is_done && $is_assigned && $check){
					if(!$is_done){
						
					}
					else{
						array_push($arrs, $approvers->overtime_id);
					}
				}
			}
			$string = implode(",", $arrs);
			return $string;
		}else{
			return false;
		}
	}
	
	/**
	 * 
	 * Overtime list count
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $search
	 */
	public function search_overtime_count($company_id, $emp_id, $search = ""){
		if(is_numeric($company_id)){
			$filter = $this->not_in_search_overtime($company_id, $emp_id, $search);
			$konsum_key = konsum_key();
			$where = array(
				'o.company_id'	=> $company_id,
				'o.deleted'	=> '0',
				"ag.emp_id" => $emp_id,
				"o.overtime_status" => "pending" 
			);
			$where2 = array(
				"ao.level !=" => ""
			);
			if($search != "" && $search != "all"){
				$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
			}
			$this->edb->where($where);
			$this->db->where($where2);
			if($filter != FALSE){
				$this->db->where("o.overtime_id NOT IN ({$filter})");
			}
			$this->edb->join('employee AS e','e.emp_id = o.emp_id','left');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
			$this->edb->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
			$this->db->group_by("o.overtime_id");
			$this->db->order_by("o.overtime_id","DESC");
			$query = $this->edb->get('employee_overtime_application AS o');	
			$row = $query->result();

			return ($row) ? $query->num_rows() : 0;
		}else{
			return false;
		}
	}
	
	/**
	 * 
	 * Check if assigned overtime
	 * @param unknown_type $overtime_appr_grp
	 * @param unknown_type $level
	 */
	public function check_assigned_overtime($overtime_appr_grp, $level){
		$where = array(
			"emp_id" => $this->session->userdata("emp_id"),
			"level <=" => $level,
			"approval_groups_via_groups_id" => $overtime_appr_grp
		);
		$this->db->where($where);
		$query = $this->db->get("approval_groups");
		$row = $query->row();
		
		return ($row) ? true : false;
	}
	
	/**
	 * 
	 * Show who's assigned approver on overtime list
	 * @param unknown_type $overtime_appr_grp
	 * @param unknown_type $level
	 */
	public function track_next_approver($overtime_appr_grp, $level){
		$where = array(
			"ag.level" => $level,
			"ag.approval_groups_via_groups_id" => $overtime_appr_grp
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
	
	/**
	 * 
	 * Check if approver is done approving a certain overtime application
	 * @param unknown_type $overtime_appr_grp
	 * @param unknown_type $level
	 */
	public function is_done($overtime_appr_grp, $level){
		$where = array(
			"emp_id" => $this->session->userdata("emp_id"),
			"level <" => $level,
			"approval_groups_via_groups_id" => $overtime_appr_grp
		);
		$this->db->where($where);
		$query = $this->db->get("approval_groups");
		$row = $query->row();
		
		return ($row) ? true : false;
	}
	
	/**
	 * 
	 * Get overtime application details
	 * @param unknown_type $overtime_id
	 * @param unknown_type $company_id
	 */
	public function overtime_details($overtime_id = "",$company_id){
		if(is_numeric($company_id)){
			$konsum_key = konsum_key();
			$where = array(
				'o.company_id'	=> $company_id,
				'o.deleted'	=> '0',
				'o.overtime_id' => $overtime_id
			);

			$this->db->where($where);
			$this->edb->join('employee AS e','e.emp_id = o.emp_id','LEFT');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("position AS pos","pos.position_id = epi.position","LEFT");
			$query = $this->edb->get('employee_overtime_application AS o');	
			$row = $query->row();
			
			return ($row) ? $row : false;
		}else{
			return false;
		}
	}
	
	/**
	 * 
	 * Get specific employee's overtime application details
	 * @param unknown_type $company_id
	 * @param unknown_type $limit
	 * @param unknown_type $start
	 * @param unknown_type $emp_id
	 * @param unknown_type $appr_emp_id
	 * @param unknown_type $sort_by
	 * @param unknown_type $sort
	 */
	public function specific_employee_overtime($company_id, $limit = 10, $start = 0, $emp_id, $appr_emp_id ,$sort_by = "", $sort = ""){
		if(is_numeric($company_id)){
			$filter = $this->not_in_specific_employee_overtime($company_id, $emp_id,$appr_emp_id);
			$start = intval($start);
			$limit = intval($limit);
			$sort_array = array(
				"overtime_date_applied",
				"overtime_from",
				"no_of_hours",
				"with_nsd_hours",
				"overtime_status",
				"approval_date"
			);
			$where = array(
				"o.company_id"	=> $company_id,
				"e.emp_id" => $emp_id,
				"o.deleted"	=> "0",
				"o.overtime_status" => "pending" ,
				"ag.emp_id" => $appr_emp_id
			);
			$where2 = array(
				"ao.level !=" => ""
			);
			$this->edb->where($where);
			$this->db->where($where2);
			if($filter != FALSE){
				$this->db->where("o.overtime_id NOT IN ({$filter})");
			}
			$this->edb->join('employee AS e','e.emp_id = o.emp_id','LEFT');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
			$this->edb->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
			$this->edb->join("position AS pos","pos.position_id = epi.position","LEFT");
			$this->db->group_by("o.overtime_id");
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			}
			else{
				$this->db->order_by("o.overtime_date_applied","DESC");
				$this->db->order_by("o.overtime_id","DESC");
			}
			$query = $this->edb->get('employee_overtime_application AS o',$limit,$start);	
			$result = $query->result();
			
			return $result;
		}else{
			return false;
		}
	}
	
	/**
	 * 
	 * Filter overtime ids for exclusion on a specific employee
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $appr_emp_id
	 */
	public function not_in_specific_employee_overtime($company_id,$emp_id,$appr_emp_id){
		if(is_numeric($company_id)){
			$where = array(
				"o.company_id"	=> $company_id,
				"e.emp_id" => $emp_id,
				"o.deleted"	=> "0",
				"o.overtime_status" => "pending",
				"ag.emp_id" => $appr_emp_id
			);
			$where2 = array(
				"ao.level !=" => ""
			);
			$this->edb->where($where);
			$this->db->where($where2);
			$this->edb->join('employee AS e','e.emp_id = o.emp_id','LEFT');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
			$this->edb->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
			$this->edb->join("position AS pos","pos.position_id = epi.position","LEFT");
			$this->db->group_by("o.overtime_id");
			$this->db->order_by("overtime_date_applied","DESC");
			$query = $this->edb->get('employee_overtime_application AS o');
			$result = $query->result();
			
			$arrs = array();
			if($result){
				$is_assigned = true;
				$workforce_notification = get_workforce_notification_settings($company_id);
				foreach($result as $key => $approvers){
					$overtime_approval_grp = $approvers->overtime_approval_grp;
					$level = $approvers->level;
					$check = $this->check_assigned_overtime($overtime_approval_grp, $level);
					if($workforce_notification->option == "choose level notification"){ 
						$is_assigned = check_if_is_level($level, $workforce_notification->workforce_alerts_notification_id);
					}
					$is_done = $this->is_done($overtime_approval_grp, $level);
					//if(!$is_done && $is_assigned && $check){
					if(!$is_done){
					}
					else{
						array_push($arrs, $approvers->overtime_id);
					}
				}
			}
			$string = implode(",", $arrs);
			return $string;
		}else{
			return false;
		}
	}
	
	/**
	 * 
	 * Count overtime application list on a specific employee
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $appr_emp_id
	 */
	public function specific_employee_overtime_count($company_id, $emp_id, $appr_emp_id){
		if(is_numeric($company_id)){
			$filter = $this->not_in_specific_employee_overtime($company_id, $emp_id,$appr_emp_id);
			$where = array(
				"o.company_id"	=> $company_id,
				"e.emp_id" => $emp_id,
				"o.deleted"	=> "0",
				"o.overtime_status" => "pending" ,
				"ag.emp_id" => $appr_emp_id
			);
			$where2 = array(
				"ao.level !=" => ""
			);
			$this->edb->where($where);
			$this->db->where($where2);
			if($filter != FALSE){
				$this->db->where("o.overtime_id NOT IN ({$filter})");
			}
			$this->edb->join('employee AS e','e.emp_id = o.emp_id','LEFT');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
			$this->edb->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
			$this->edb->join("position AS pos","pos.position_id = epi.position","LEFT");
			$this->db->order_by("overtime_date_applied","DESC");
			$this->db->group_by("o.overtime_id");
			$query = $this->edb->get('employee_overtime_application AS o');	
			$result = $query->result();
			
			return ($result) ? $query->num_rows() : 0;
		}else{
			return false;
		}
	}
	
	/**
	 * 
	 * Display all overtime application list for owner to approve with search
	 * no leveling is applied
	 * @param unknown_type $company_id
	 * @param unknown_type $limit
	 * @param unknown_type $start
	 * @param unknown_type $search
	 * @param unknown_type $sort_by
	 * @param unknown_type $sort
	 */
	public function search_overtime_owner($company_id ,$limit=10, $start=0, $search = "", $sort_by = "", $sort = "ASC"){
		if(is_numeric($company_id)){
			$konsum_key = konsum_key();

			$sort_array = array(
				"first_name",
				"pg_name",
				"overtime_date_applied",
				"overtime_from",
				"no_of_hours",
				"with_nsd_hours",
				"overtime_status",
				"approval_date"
			);
			$where = array(
				'o.company_id' => $company_id,
				'o.deleted'	=> '0',
				"o.overtime_status" => "pending" 
			);
			$select = array(
				"*",
				"pg.name AS pg_name"
			);
			if($search != "" && $search != "all"){
				$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
			}
			$this->db->select($select);
			$this->edb->where($where);

			$this->edb->join('employee AS e','e.emp_id = o.emp_id','left');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
			$this->edb->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
			$this->db->group_by("o.overtime_id");
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			}
			else{
				$this->db->order_by("o.overtime_date_applied","DESC");
				$this->db->order_by("o.overtime_id","DESC");
			}
			$query = $this->edb->get('employee_overtime_application AS o',$limit,$start);
			$result = $query->result();
			
			return ($result) ? $result : false;
		}else{
			return false;
		}
	}
	
	/**
	 * 
	 * Get overtime application list count for owner to approve with search
	 * @param unknown_type $company_id
	 * @param unknown_type $search
	 */
	public function search_overtime_owner_count($company_id ,$search = ""){
		if(is_numeric($company_id)){
			$konsum_key = konsum_key();

			$sort_array = array(
				"first_name",
				"pg_name",
				"overtime_date_applied",
				"overtime_from",
				"no_of_hours",
				"with_nsd_hours",
				"overtime_status",
				"approval_date"
			);
			$where = array(
				'o.company_id' => $company_id,
				'o.deleted'	=> '0',
				"o.overtime_status" => "pending" 
			);
			$select = array(
				"*",
				"pg.name AS pg_name"
			);
			if($search != "" && $search != "all"){
				$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
			}
			$this->db->select($select);
			$this->edb->where($where);

			$this->edb->join('employee AS e','e.emp_id = o.emp_id','left');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
			$this->edb->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
			$this->db->order_by("o.overtime_date_applied","DESC");
			$this->db->order_by("o.overtime_id","DESC");
			$this->db->group_by("o.overtime_id");

			$query = $this->edb->get('employee_overtime_application AS o');
			$result = $query->result();
			
			return ($result) ? $query->num_rows() : 0;
		}else{
			return false;
		}
	}
	
	/**
	 * 
	 * Get overtime applications on specific employee for owner to approve
	 * @param unknown_type $company_id
	 * @param unknown_type $limit
	 * @param unknown_type $start
	 * @param unknown_type $emp_id
	 * @param unknown_type $sort_by
	 * @param unknown_type $sort
	 */
	public function owner_specific_employee_overtime($company_id, $limit = 10, $start = 0, $emp_id,$sort_by = "", $sort = ""){
		if(is_numeric($company_id)){
			$start = intval($start);
			$limit = intval($limit);
			$sort_array = array(
				"overtime_date_applied",
				"overtime_from",
				"no_of_hours",
				"with_nsd_hours",
				"overtime_status",
				"approval_date"
			);
			$where = array(
				"o.company_id"	=> $company_id,
				"e.emp_id" => $emp_id,
				"o.deleted"	=> "0",
				"o.overtime_status" => "pending"
			);
			$this->edb->where($where);
			$this->edb->join('employee AS e','e.emp_id = o.emp_id','LEFT');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
			$this->edb->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
			$this->edb->join("position AS pos","pos.position_id = epi.position","LEFT");
			$this->db->group_by("o.overtime_id");
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			}
			else{
				$this->db->order_by("o.overtime_date_applied","DESC");
				$this->db->order_by("o.overtime_id","DESC");
			}
			$query = $this->edb->get('employee_overtime_application AS o',$limit,$start);	
			$result = $query->result();
			
			return ($result) ? $result : false;
		}else{
			return false;
		}
	}
	
	/**
	 * 
	 * Get overtime applications count on specific employee for owner to approve
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @return number|boolean
	 */
	public function owner_specific_employee_overtime_count($company_id, $emp_id){
		if(is_numeric($company_id)){
			$where = array(
				"o.company_id"	=> $company_id,
				"e.emp_id" => $emp_id,
				"o.deleted"	=> "0",
				"o.overtime_status" => "pending"
			);
			$this->edb->where($where);
			$this->edb->join('employee AS e','e.emp_id = o.emp_id','LEFT');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
			$this->edb->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
			$this->edb->join("position AS pos","pos.position_id = epi.position","LEFT");
			$this->db->group_by("o.overtime_id");
			$query = $this->edb->get('employee_overtime_application AS o');	
			$result = $query->result();
			
			return ($result) ? $query->num_rows() : 0;
		}else{
			return false;
		}
	}
	
	public function overtime_approval_level($emp_id){
		if(is_numeric($emp_id)){
			$this->db->where('ap.name','Overtime Approval Group');
			$this->db->where('ag.emp_id',$emp_id);
			$this->db->join('approval_process AS ap','ap.approval_process_id = ag.approval_process_id','LEFT');
			$q = $this->db->get('approval_groups AS ag');
			$r = $q->row();
			
			return ($r) ? $r->level : FALSE;
		}else{
			return false;
		}
	}
	
	public function overtime_information($company_id,$val){
		$w = array(
			"ot.overtime_id" => $val,
			"ot.status" => "Active",
			"ot.company_id" => $company_id
		);
		$this->db->where($w);
		$this->edb->join("approval_overtime AS ao","ot.overtime_id = ao.overtime_id","LEFT");
		$this->edb->join("employee AS e","e.emp_id = ot.emp_id","LEFT");
		$q = $this->edb->get("employee_overtime_application AS ot");
		$r = $q->row();
		
		return ($r) ? $r : FALSE ;
	}
	
	public function generate_overtime_level_token($new_level, $overtime_id){
		$str2 = 'ABCDEFG1234567890';
		$shuffled2 = str_shuffle($str2);
		
		$update = array(
			"level" => $new_level,
			"token_level" => $shuffled2
		);
		$where = array(
			"overtime_id" => $overtime_id
		);
		
		$this->db->where($where);
		$update_approval_overtime_token = $this->db->update("approval_overtime",$update);
		
		return ($update_approval_overtime_token) ? $shuffled2 : false;
	}
	
	public function get_overtime_last_level($emp_id, $company_id){
		$this->db->where("emp_id",$emp_id);
		$sql = $this->db->get("employee_payroll_information");
		$row = $sql->row();
		if($row){
			$overtime_approver_id = $row->overtime_approval_grp;
			$w = array(
				"ag.company_id"=>$company_id,
				"ag.approval_groups_via_groups_id"=>$overtime_approver_id
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
	
	/**
	 * Get Token from Approval Leave
	 * @param unknown_type $leave_ids
	 * @param unknown_type $comp_id
	 * @param unknown_type $emp_id
	 */
	public function get_token($leave_ids,$comp_id,$emp_id){
		$w = array(
			"overtime_id"=>$leave_ids,
			"comp_id"=>$comp_id,
			"emp_id"=>$emp_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_overtime");
		$row = $q->row();
		return ($q->num_rows() > 0) ? $row->token : "" ;
	}
	
	public function get_subscription(){
		$where = array(
			"payroll_system_account_id" => $this->session->userdata("psa_id"),
			"status" => "Active"
		);
		$this->db->where($where);
		$query = $this->db->get("payroll_system_account");
		$row = $query->row();
		
		return ($row) ? $row->choose_plans_id : 0;
	}
	/* -------------------------------------------------------- */
	
	
	/**
	 * CHECKS APPLICATION LEAVE FOR EVERY COMPANY
	 * @param int $company_id
	 * @return object
	 */
	public function overtime_list($company_id,$limit=10,$start=0){
		if(is_numeric($company_id)){
			$where = array(
				'o.company_id' => $company_id,
				'o.deleted'	   => '0'
			);
			$this->db->select('CONCAT('.$this->edb->decrypt('e.first_name').'," ",'.
				$this->edb->decrypt('e.last_name').') as full_name',FALSE);
			$this->edb->where($where);
			$this->edb->join('employee AS e','e.emp_id = o.emp_id','left');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$query = $this->edb->get('employee_overtime_application AS o',$limit,$start);
			
			$result = $query->result();
			$query->free_result();
			return $result;
		}else{
			return false;
		}
	}
	
	/**
	 * OVERTIME LIST VIA DDATE
	 * @param int $company_id
	 * @param int $limit
	 * @param int $start
	 * @param dates $date_from
	 * @param dates $date_to
	 * @return object
	 */
	public function overtime_list_by_date($company_id,$limit=10,$start=0,$date_from,$date_to){
		if(is_numeric($company_id)){
			$start = intval($start);
			$limit = intval($limit);
			
			$where = array(
				'o.company_id' 		 => $company_id,
				'o.deleted'	   		 => '0',
				'o.overtime_from <=' => $date_to,
				'o.overtime_to >='	 => $date_from
			);
			$this->db->select('CONCAT('.$this->edb->decrypt('e.first_name').'," ",'.
				$this->edb->decrypt('e.last_name').') as full_name',FALSE);
			$this->db->where($where);
			$this->edb->join('employee AS e','e.emp_id = o.emp_id','left');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$query = $this->edb->get('employee_overtime_application AS o',$limit,$start);
			
			$result = $query->result();
			$query->free_result();
			return $result;
		}else{
			return false;
		}
	}
	
	
	
	/**
	 * OVERTIME LIST VIA NAME
	 * @param int $company_id
	 * @param int $limit
	 * @param int $start
	 * @param string $employee_name
	 * @return object
	 */
	public function overtime_list_by_name($company_id,$limit=10,$start=0,$employee_name){
		if(is_numeric($company_id)){
			$start = intval($start);
			$limit = intval($limit);
			$employee_name = $this->db->escape_like_str($employee_name);
			
			$where = array(
				'o.company_id' => $company_id,
				'o.deleted'	   => '0'
			);
			$this->db->select('CONCAT('.$this->edb->decrypt('e.first_name').'," ",'.
				$this->edb->decrypt('e.last_name').') as full_name');
			$this->edb->where($where);
			$this->edb->like_concat('e.first_name','e.last_name');
			$this->edb->join('employee AS e','e.emp_id = o.emp_id','left');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$query = $this->edb->get('employee_overtime_application AS o',$limit,$start);
			
			$result = $query->result();
			$query->free_result();
			return $result;
		}else{
			return false;
		}
	}
	
	/**
	 * OVERTIME APPLICATION ACCOUNT COUNT DATES
	 * @param int $company_id
	 * @param dates $date_from
	 * @param dates $date_to
	 * @return integer
	 */
	public function overtime_application_count_date($company_id,$date_from,$date_to){
		if(is_numeric($company_id)){
			$date_from = $this->db->escape($date_from);
			$date_to = $this->db->escape($date_to);
			$query = $this->db->query("SELECT count(*) as val FROM employee_overtime_application WHERE company_id = '{$this->db->escape_str($company_id)}' AND deleted='0' 
					AND overtime_from >= {$date_from}	AND overtime_to <={$date_to}
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
	 * DISPLAY COUNT OVERTIME APPLICATION VIA EMPLOYE NAME
	 * @param int $company_id
	 * @param string $employee_name
	 * @return integer
	 */
	public function overtime_application_count_name($company_id,$employee_name){
		if(is_numeric($company_id)){
			$employee_name = $this->db->escape_like_str($employee_name);
			$query = $this->db->query("SELECT count(*) as val FROM employee_overtime_application o 
					LEFT JOIN employee e on e.emp_id = o.emp_id 
					WHERE o.company_id = '{$this->db->escape_str($company_id)}' AND o.deleted='0' 
					AND concat(e.first_name,' ',e.last_name) like '%{$employee_name}%' 
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
	 * Count Leaves application for pagination purposes only
	 * @param int $company_id
	 * @return integer
	 */
	public function overtime_application_count($company_id){
		if(is_numeric($company_id)){
			$query = $this->db->query("SELECT count(*) as val FROM employee_overtime_application WHERE  company_id = '{$this->db->escape_str($company_id)}' AND deleted='0'
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
	 * Update field
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
	
	/**
	 * REJECTS OVERTIME LOGS 
	 * rejects overtime logs
	 * @param int $overtime_id
	 * @param int $company_id
	 */
	public function ajax_overtime_logs_reject($overtime_id,$company_id) {
		$log_user = imodule_account();
		if($this->session->userdata('user_type_id') == 2) { // IF OWNER 	
			if(is_numeric($overtime_id)){
				# CHECK THE LEAVE APPliCATION ID ONCE IT IS VAlID THEN WE TAKE OUT EMP_ID WHO FILED THIS 
				$leave_id = check_overtime_application($overtime_id); 
				# NOW Shreds the DATA and distributes its emp_id to the profile_getAccount
				# NOW Shreds the DATA and distributes its emp_id to the profile_getAccount
				$check_employee = imodule_employee($leave_id->emp_id,$company_id);
				$employee_data = $check_employee ? $check_employee->last_name.",".$check_employee->first_name : "Unknown emp_id:".$leave_id->emp_id;
				$date_requested = date("m/d/Y",strtotime($leave_id->overtime_from));
				$check_users = "";
				if($log_user){
					if($log_user->last_name ==""){
						$check_users = "Owner";
					}else{
						$check_users = $log_user->last_name." , ".$log_user->first_name;
					}	
				} 
				$mesage = sprintf(lang("reject_overtime_leave"),$check_users ,$employee_data,$date_requested);
				add_activity($mesage,$company_id);				
			}
		} else if($this->session->userdata('user_type_id') == 3){
			if(is_numeric($overtime_id)){
				# CHECK THE LEAVE APPliCATION ID ONCE IT IS VAlID THEN WE TAKE OUT EMP_ID WHO FILED THIS 
				$leave_id = check_overtime_application($overtime_id); 
				# NOW Shreds the DATA and distributes its emp_id to the profile_getAccount
				$check_employee = imodule_employee($leave_id->emp_id,$company_id);
				$employee_data = $check_employee ? $check_employee->last_name.",".$check_employee->first_name : "Unknown emp_id:".$leave_id->emp_id;
				$date_requested = date("m/d/Y",strtotime($leave_id->overtime_from));
				$check_users = "";
				if($log_user){
					if($log_user->last_name ==""){
						$check_users = "Owner";
					}else{
						$check_users = $log_user->last_name." , ".$log_user->first_name;
					}	
				} 
				$mesage = sprintf(lang("reject_overtime_leave"),$check_users,$employee_data,$date_requested);
				add_activity($mesage,$company_id);	
			}
		}
	}
	
	/**
	 * APPROVES OVERTIME LOGS
	 * THIS WILL APPROVE OVERTIME IN EVERY TRANSACTIONS MADE
	 * @param int $overtime_id
	 * @param int $company_id
	 */
	public function ajax_overtime_logs_approve($overtime_id,$company_id) {
		$log_user = imodule_account();
		if($this->session->userdata('user_type_id') == 2) { // IF OWNER 	
			$company_owner = $this->profile->get_account($this->session->userdata('account_id'),"company_owner");
			$fullname = $company_owner->first_name." ".$company_owner->last_name;
			if(is_numeric($overtime_id)){
				# CHECK THE LEAVE APPliCATION ID ONCE IT IS VAlID THEN WE TAKE OUT EMP_ID WHO FILED THIS 
				$leave_id = check_overtime_application($overtime_id); 
				# NOW Shreds the DATA and distributes its emp_id to the profile_getAccount
				$check_employee = imodule_employee($leave_id->emp_id,$company_id);
				
				$employee_data = $check_employee ? $check_employee->last_name.",".$check_employee->first_name : "Unknown emp_id:".$leave_id->emp_id;
				$date_requested = date("m/d/Y",strtotime($leave_id->overtime_from));
				$check_users = "";
				if($log_user){
					if($log_user->last_name ==""){
						$check_users = "Owner";
					}else{
						$check_users = $log_user->last_name." , ".$log_user->first_name;
					}	
				} 
				
				$mesage = sprintf(lang("approve_overtime_leave"),$check_users,$employee_data,$date_requested);
				add_activity($mesage,$company_id);		
			}
		} else if($this->session->userdata('user_type_id') == 3){
			if(is_numeric($overtime_id)){
				# CHECK THE LEAVE APPliCATION ID ONCE IT IS VAlID THEN WE TAKE OUT EMP_ID WHO FILED THIS 
				$leave_id = check_overtime_application($overtime_id); 
				# NOW Shreds the DATA and distributes its emp_id to the profile_getAccount
				$check_employee = imodule_employee($leave_id->emp_id,$company_id);
				
				$employee_data = $check_employee ? $check_employee->last_name.",".$check_employee->first_name : "Unknown emp_id:".$leave_id->emp_id;
				$date_requested = date("m/d/Y",strtotime($leave_id->overtime_from));
				$check_users = "";
				if($log_user){
					if($log_user->last_name ==""){
						$check_users = "Owner";
					}else{
						$check_users = $log_user->last_name." , ".$log_user->first_name;
					}	
				} 
				$mesage = sprintf(lang("approve_overtime_leave"),$check_users,$employee_data,$date_requested);
				add_activity($mesage,$company_id);
				
			}
		}
	}
	
	/**
	* GET overtime get data
	*	@param int $company_id
	*	@param int $overtime_id
	*	@return object
	*/
	public function overtime_get_data($company_id,$overtime_id) {
		if(is_numeric($company_id) && is_numeric($overtime_id)) {
			$field = array(
						"company_id" => $company_id,
						"overtime_id" => $overtime_id
			);
			$query = $this->db->get_where("employee_overtime_application",$field);
			$row 	= $query->row();
			$query->free_result();
			return $row;
		} else {
			return false;
		}
	}
	
	/**
	 * OVERTIME LIST VIA DDATE
	 * @param int $company_id
	 * @param int $limit
	 * @param int $start
	 * @param dates $date_from
	 * @param dates $date_to
	 * @return object
	 */
	public function advance_search($company_id,$limit=10,$start=0,$date_from = "",$date_to="",$employee_name=""){
		if(is_numeric($company_id)){
			$start = intval($start);
			$limit = intval($limit);
			$where = array(
				'o.company_id' 		 => $company_id,
				'o.deleted'	   		 => '0'
			);
		
			if($employee_name !="" && $employee_name !="all"){
				$this->edb->like_concat('e.last_name','e.first_name',$employee_name);
			}
			if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
				$this->db->where(array("o.overtime_from >="=>"{$date_from}","o.overtime_to <="=>"{$date_to}"));
			}	
			
			$this->db->select('CONCAT('.$this->edb->decrypt('e.first_name').'," ",'.$this->edb->decrypt('e.last_name').') as full_name',FALSE);
			$this->db->where($where);
			$this->edb->join('employee AS e','e.emp_id = o.emp_id','left');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$query = $this->edb->get('employee_overtime_application AS o',$limit,$start);	
			$result = $query->result();
			$query->free_result();
			return $result;
		}else{
			return false;
		}
	}
	
	/**
	 * OVERTIME LIST VIA DDATE
	 * @param int $company_id
	 * @param int $limit
	 * @param int $start
	 * @param dates $date_from
	 * @param dates $date_to
	 * @return object
	 */
	public function count_advance_search($company_id,$date_from = "",$date_to="",$employee_name=""){
		if(is_numeric($company_id)){
			$where = array(
				'o.company_id' 		 => $company_id,
				'o.deleted'	   		 => '0'
			);
		
			if($employee_name !="" && $employee_name !="all"){
				$this->edb->like_concat('e.last_name','e.first_name',$employee_name);
			}
			if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
				$this->db->where(array("o.overtime_from >="=>"{$date_from}","o.overtime_to <="=>"{$date_to}"));
			}	
			
			$this->db->select('count(*) as val');
			$this->db->where($where);
			$this->edb->join('employee AS e','e.emp_id = o.emp_id','left');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$query = $this->edb->get('employee_overtime_application AS o');	
			$row = $query->row();
			$num_row = $query->num_rows();
			$query->free_result();
			return $num_row ? $row->val : 0;ult();
			
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
	*	Employee time in agains 
	*	@param int $company_id
	*	@param int $emp_id
	*	@param date $date
	*	@return object
	*/
	public function employee_time_in_against($company_id,$emp_id,$date) {
		if(is_numeric($company_id) && is_numeric($emp_id) && $date !="") {
			$field = array(
				"comp_id" => $this->db->escape_str($company_id),
				"emp_id"	=> $this->db->escape_str($emp_id),
				"date"			=>  $this->db->escape_str($date)
			);
			$query = $this->db->get_where("employee_time_in",$field);
			$row = $query->row();
			$query->free_result();
			return $row;	
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
					}else{
						return false;
					}
				}
			}
		}
	}
	
	public function is_emp_overtime_pending($ot_id,$company_id){
		$where = array(
				"overtime_id" => $ot_id,
				"company_id" => $company_id,
				"overtime_status" => "pending"
		);
		$this->db->where($where);
		$query = $this->db->get("employee_overtime_application");
		$row = $query->row();
			
		return ($row) ? true : false;
	}
}
	
/* End of file Approve_leave_model */
/* Location: ./application/models/hr/Approve_leave_model.php */;
	