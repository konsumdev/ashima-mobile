<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Approve shift model (todo)
 *
 * @category Model
 * @version 1.0
 * @author John fritz marquez <fritzified.gamer@gmail.com>
 */
	class Todo_shifts_model extends CI_Model {
		/**
		 * CHECKS APPLICATION LEAVE FOR EVERY COMPANY
		 * @param int $company_id
		 * @return object
		 */
		public function shifts_list($company_id, $emp_id){
			if(is_numeric($company_id)){				
				$where = array(
						'ewsa.company_id' => $company_id,
						"ag.emp_id" => $emp_id,
						"ewsa.employee_work_schedule_status" => "pending"
				);
				$where2 = array(
						"aws.level !=" => ""
				);
				$select = array(
					'aws.employee_work_schedule_application_id',
				    'ewsa.date_from',
				    'ewsa.start_time',
				    'ewsa.end_time',
				    'ewsa.date_filed',
				    'ewsa.reason',
				    'e.emp_id',
				    'e.account_id',
				    'ewsa.date_to',
				    'ewsa.employee_work_schedule_status',
                    'a.profile_image',
                    'ws.name'
				);
				$edb_sel = array(
				    'e.last_name',
				    'e.first_name',
				    'a.payroll_cloud_id'
				);
				
					
				$this->db->select($select);
				$this->edb->select($edb_sel);	
				$this->edb->where($where);
				$this->db->where($where2);
		
				$this->edb->join('employee AS e','e.emp_id = ewsa.emp_id','left');
				$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
				$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
				$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
				$this->edb->join("approval_groups_via_groups AS agg","epi.shedule_request_approval_grp = agg.approval_groups_via_groups_id","LEFT");
				$this->edb->join("work_schedule AS ws","ws.work_schedule_id = ewsa.work_schedule_id","LEFT");
				$this->edb->join("approval_work_schedule AS aws","aws.employee_work_schedule_application_id = ewsa.employee_work_schedule_application_id","LEFT");
				$this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND aws.level = ag.level","LEFT");
				$this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");

				$this->db->group_by("ewsa.employee_work_schedule_application_id");
                $this->db->order_by("ewsa.date_filed","DESC");
				#$this->db->order_by("ewsa.employee_work_schedule_application_id","DESC");

				$query = $this->edb->get('employee_work_schedule_application AS ewsa');
				$result = $query->result();
				
				$query->free_result();
				return $result;
			}else{
				return false;
			}
				
		}
		
		public function not_in_search_shifts($company_id, $emp_id, $search = ""){

			if(is_numeric($company_id)){
				$konsum_key = konsum_key();
				$where = array(
						'ewsa.company_id' => $company_id,
						//'o.deleted'	=> '0',
						"ag.emp_id" => $emp_id,
						"ewsa.employee_work_schedule_status" => "pending"
				);
				$where2 = array(
						"aws.level !=" => ""
				);
				if($search != "" && $search != "all"){
					$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
				}
				$sel = array(
				    'epi.shedule_request_approval_grp',
				    'aws.level',
				    'aws.employee_work_schedule_application_id'
				);
				$this->edb->select($sel);
				$this->edb->where($where);
				$this->db->where($where2);
				$this->edb->join('employee AS e','e.emp_id = ewsa.emp_id','left');
				$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
				$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
				
				$this->edb->join("approval_groups_via_groups AS agg","epi.shedule_request_approval_grp = agg.approval_groups_via_groups_id","LEFT");
				$this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
				
				$this->edb->join("approval_work_schedule AS aws","aws.employee_work_schedule_application_id = ewsa.employee_work_schedule_application_id","LEFT");
				$this->db->group_by("ewsa.employee_work_schedule_application_id");
				$query = $this->edb->get('employee_work_schedule_application AS ewsa');
				$result = $query->result();
				
				$arrs = array();
				if($result){
					$is_assigned = true;
					$workforce_notification = get_workforce_notification_settings($company_id);
					foreach($result as $key => $approvers){
						$shift_approval_grp = $approvers->shedule_request_approval_grp;
						$level = $approvers->level;
						$check = $this->check_assigned_shifts($shift_approval_grp, $level);
						if($workforce_notification->option == "choose level notification"){
							$is_assigned = check_if_is_level($level, $workforce_notification->workforce_alerts_notification_id);
						}
						//$is_done = $this->is_done($overtime_approval_grp, $level);
						if($is_assigned && $check){
							//if(!$is_done){
			
						}
						else{
							array_push($arrs, $approvers->employee_work_schedule_application_id);
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
		 * check if the employees time in adjustment is under the approvers approval group
		 * @param unknown $hours_appr_grp
		 * @param unknown $level
		 * @return boolean
		 */
		public function check_assigned_shifts($hours_appr_grp, $level){
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