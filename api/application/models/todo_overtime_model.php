<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Approve LEave model for approving overtime , leaves , loans
 *
 * @category Model
 * @version 1.0
 * @author Filadelfo Jr. Sandalo <filsandalojr@gmail.com>
 */
	class Todo_overtime_model extends CI_Model {
		
		/**
		 * CHECKS APPLICATION LEAVE FOR EVERY COMPANY
		 * @param int $company_id
		 * @return object
		 */
		public function overtime_list($company_id, $emp_id, $search = '', $flag_payroll_correction='')
		{
			if ( ! is_numeric($company_id)) {
				return false;
			}
			
			$filter = $this->not_in_search_overtime($company_id, $emp_id, $search, $flag_payroll_correction);
			//$level = $this->overtime_approval_level($this->session->userdata('emp_id'));

			if($flag_payroll_correction == "current") {
			    $payroll_correction = "no";
			} elseif ($flag_payroll_correction == "late") {
			    $payroll_correction = "yes";
			} else {
			    $payroll_correction = "no";
			}

			$where = array(
				'o.company_id' 		=> $company_id,
				'o.deleted'			=> '0',
				'ag.emp_id' 		=> $emp_id,
				'o.overtime_status' => 'pending',
				'epi.employee_status' => 'Active',
				// 'o.flag_payroll_correction' => $payroll_correction,
				'ao.level !=' 		=> ''
			);

			$select = array(
			    "epi.overtime_approval_grp",
			    "ao.level",
			    "o.overtime_id",
			    "o.overtime_status",
			    "o.overtime_date_applied",
			    "o.overtime_from",
			    "o.overtime_to",
			    "o.start_time",
			    "o.end_time",
			    "o.no_of_hours",
			    "o.reason",
			    "o.notes",
			    "o.emp_id",
			    "o.company_id",
			    "pg.name AS pg_name",
			    "a.profile_image",
			    "o.for_resend_auto_rejected_id"
			);
			
			$edb_select = array(
			    'e.first_name',
			    'e.last_name',
			    'a.payroll_cloud_id'
			);
						
			$this->db->select($select);
			$this->edb->select($edb_select);
			$this->db->where($where);
			if($filter != FALSE){
				$this->db->where("o.overtime_id NOT IN ({$filter})");
			}
				
			$this->edb->join('employee AS e','e.emp_id = o.emp_id','left');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
    		$this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
    		$this->edb->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
			$this->db->group_by("o.overtime_id");

			$query = $this->edb->get('employee_overtime_application AS o');
			$result = $query->result();
			
			$query->free_result();
			return $result;
		
		}
		public function not_in_search_overtime($company_id, $emp_id, $search = "", $flag_payroll_correction=''){
			if(!is_numeric($company_id)){
				return false;
			}
				
			if($flag_payroll_correction == "current") {
		        $payroll_correction = "no";
		    } elseif ($flag_payroll_correction == "late") {
		        $payroll_correction = "yes";
		    } else {
		        $payroll_correction = "no";
		    }

			$where = array(
				'o.company_id' 			=> $company_id,
				'o.deleted'				=> '0',
				'ag.emp_id'				=> $emp_id,
				'o.overtime_status' 	=> 'pending',
				'epi.employee_status' 	=> 'Active',
				'ao.level !='			=> '',
				'o.flag_payroll_correction' => $payroll_correction
			);

			$select = array(
			    "epi.overtime_approval_grp",
			    "o.overtime_id",
			    "ao.level",
			);
			
			$this->db->select($select);
			$this->db->where($where);
			$this->db->join("employee_payroll_information AS epi","epi.emp_id = o.emp_id","LEFT");
			$this->db->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$this->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
			$this->db->join("approval_overtime AS ao","ao.overtime_id = o.overtime_id","LEFT");
			$this->db->group_by("o.overtime_id");
			$query = $this->db->get('employee_overtime_application AS o');
			$result = $query->result();
				
			$arrs = array();
			if($result){
				$is_assigned = true;
				
				foreach($result as $key => $approvers){
					$overtime_approval_grp = $approvers->overtime_approval_grp;
					$level = $approvers->level;
					$check = $this->check_assigned_overtime($overtime_approval_grp, $level);
	
					if($check){
	
					} else {
						array_push($arrs, $approvers->overtime_id);
					}
				}
			}
			
			$string = implode(",", $arrs);
			
			return $string;
			
		}
		public function overtime_list_count($company_id, $emp_id, $limit = 10, $start = 0, $search =""){
		if(is_numeric($company_id)){
			$konsum_key = konsum_key();
			$filter = $this->not_in_search_overtime($company_id, $emp_id, $search);
			$level = $this->overtime_approval_level($this->session->userdata('emp_id'));
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
			$this->db->select('count(*) as val');
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
			$query = $this->edb->get('employee_overtime_application AS o',$limit,$start);	
			$row = $query->row();

			return ($row) ? $query->num_rows : 0;
		}else{
			return false;
		}
	}
		public function get_overtime_last_level($emp_id){
				
			$where = array(
					'epi.emp_id' => $emp_id
			);
			$this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("approval_groups AS ag","agg.approval_process_id = ag.approval_process_id","LEFT");
			$this->db->order_by("ag.approval_group_id","desc");
			$q = $this->edb->get("employee_payroll_information AS epi",1);
			$r = $q->row();
			return ($r)? $r->level:FALSE;
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
		public function get_last_level_checked_payroll($company_id, $workforce_id){
			$where = array(
					"workforce_alerts_notification_id" => $workforce_id,
					"company_id" => $company_id
			);
			$this->db->where($where);
			$this->db->order_by("level","DESC");
			$query = $this->db->get("workforce_notification_leveling");
			$row = $query->row();
		
			return ($row) ? $row->level : false;
		}
	public function generate_leave_overtime_token($new_level, $ot_id){
		$str2 = 'ABCDEFG1234567890';
		$shuffled2 = str_shuffle($str2);
		
		$update = array(
			"level" => $new_level,
			"token_level" => $shuffled2
		);
		$where = array(
			"overtime_id" => $ot_id
		);
		
		$this->db->where($where);
		$update_approval_ot_token = $this->db->update("approval_overtime",$update);
		
		return ($update_approval_ot_token) ? true : false;
	}
	
	/**
	 * check if the employees time in adjustment is under the approvers approval group
	 * @param unknown $hours_appr_grp
	 * @param unknown $level
	 * @return boolean
	 */
	public function check_assigned_overtime($hours_appr_grp, $level){
		$where = array(
				"emp_id" => $this->session->userdata("emp_id"),
				"level" => $level,
				"approval_groups_via_groups_id" => $hours_appr_grp
		);
		$this->db->where($where);
		$query = $this->db->get("approval_groups");
		$row = $query->row();
	
		return ($row) ? true : false;
	}
	}
	
	
	