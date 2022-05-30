<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Approve LEave model for approving overtime , leaves , loans
 *
 * @category Model
 * @version 1.0
 * @author John Fritz Marquez <fritzified.gamer@gmail.com>
 */

	class Approve_shift_model extends CI_Model {
		
		/**
		 *
		 * Check if assigned overtime
		 * @param unknown_type $overtime_appr_grp
		 * @param unknown_type $level
		 */
		public function check_assigned_shifts($shifts_appr_grp, $level, $emp_id = NULL){
			$where = array(
					"emp_id" => ($emp_id != NULL) ? $emp_id : $this->session->userdata("emp_id"),
					"level" => $level,
					"approval_groups_via_groups_id" => $shifts_appr_grp
			);
			$this->db->where($where);
			$query = $this->db->get("approval_groups");
			$row = $query->row();
		
			return ($row) ? true : false;
		}
		
		/**
		 * Show who's assigned approver on overtime list
		 * @param unknown_type $overtime_appr_grp
		 * @param unknown_type $level
		 */
		public function track_next_approver($shift_appr_grp, $level, $current_approver_emp_id, $appr_process_id){
			$next_approver = array();
			$where = array(
					"level" => $level,
					"approval_groups_via_groups_id" => $shift_appr_grp,
					"emp_id !=" => $current_approver_emp_id,
					"approval_process_id" => $appr_process_id
			);
			$this->db->where($where);
			$query = $this->db->get("approval_groups");
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
							array_push($next_approver,ucwords($owner_row->first_name." ".$owner_row->last_name));
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
							array_push($next_approver,ucwords($emp_row->first_name." ".$emp_row->last_name));
						}
					}
				}
			}
			return $next_approver;
		}
		
		public function is_emp_shifts_pending($shifts_id,$company_id){
			$where = array(
					"employee_work_schedule_application_id" => $shifts_id,
					"company_id" => $company_id,
					"employee_work_schedule_status" => "pending"
			);
			$this->db->where($where);
			$query = $this->db->get("employee_work_schedule_application");
			$row = $query->row();
				
			return ($row) ? true : false;
		}
		
		public function shifts_information($company_id,$val){
			$w = array(
					"ewsa.employee_work_schedule_application_id" => $val,
					"ewsa.status" => "Active",
					"ewsa.company_id" => $company_id
			);
			$this->db->where($w);
			$this->edb->join("approval_work_schedule AS aws","ewsa.employee_work_schedule_application_id = aws.employee_work_schedule_application_id","LEFT");
			$this->edb->join("employee AS e","e.emp_id = ewsa.emp_id","LEFT");
			$q = $this->edb->get("employee_work_schedule_application AS ewsa");
			$r = $q->row();
		
			return ($r) ? $r : FALSE ;
		}
		
		/**
		 * Get Token from Approval Shift
		 * @param unknown_type $leave_ids
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function get_token($shifts_ids,$comp_id,$emp_id){
			$w = array(
					"employee_work_schedule_application_id"=>$shifts_ids,
					"company_id"=>$comp_id,
					"emp_id"=>$emp_id,
					"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("approval_work_schedule");
			$row = $q->row();
			return ($q->num_rows() > 0) ? $row->token : "" ;
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
		 * GET overtime get data
		 *	@param int $company_id
		 *	@param int $overtime_id
		 *	@return object
		 */
		public function shifts_get_data($company_id,$shift_id) {
			if(is_numeric($company_id) && is_numeric($shift_id)) {
				$field = array(
						"company_id" => $company_id,
						"employee_work_schedule_application_id" => $shift_id
				);
				$query = $this->db->get_where("employee_work_schedule_application",$field);
				$row 	= $query->row();
				$query->free_result();
				return $row;
			} else {
				return false;
			}
		}
		
		public function generate_shifts_level_token($new_level, $employee_work_schedule_application_id){
			$str2 = 'ABCDEFG1234567890';
			$shuffled2 = str_shuffle($str2);
		
			$update = array(
					"level" => $new_level,
					"token_level" => $shuffled2
			);
			$where = array(
					"employee_work_schedule_application_id" => $employee_work_schedule_application_id
			);
		
			$this->db->where($where);
			$update_approval_shifts_token = $this->db->update("approval_work_schedule",$update);
		
			return ($update_approval_shifts_token) ? $shuffled2 : false;
		}
		
		public function get_shifts_last_level($emp_id, $company_id){
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
		
		//get employee work sched app id
		public function get_emp_work_sched_app($employee_work_sched_app_id){
			$where = array(
					'employee_work_schedule_application_id' => $employee_work_sched_app_id
			);
			
			$this->db->where($where);
			$query = $this->db->get('employee_work_schedule_application');
			$res = $query->row();
			
			return ($res) ? $res : FALSE;
		}
		
	}