<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Approve LEave model for approving overtime , leaves , loans
 *
 * @category Model
 * @version 1.0
 * @author Filadelfo Jr. Sandalo <filsandalojr@gmail.com>
 */
	class Todo_timein_model extends CI_Model {
		
		/**
		 * CHECKS APPLICATION LEAVE FOR EVERY COMPANY
		 * @param int $company_id
		 * @return object
		 */

		public function timein_list_new($emp_id, $company_id, $search = "", $rest_day_r_a = "no", $holiday_approve = "no")
		{
			if(!is_numeric($company_id)){
				return false;
			}

			$where = array(
				'ee.comp_id'   => $company_id,
				'ee.status'   => 'Active',
				'ee.corrected' => 'Yes',
				"ag.emp_id" => $emp_id,
				'ee.time_in_status'=> 'pending',
				//'ee.source !=' => 'mobile',
			    'ee.flag_payroll_correction' => 'no',
			    "at.level !=" => "",
			    "ee.rest_day_r_a" => $rest_day_r_a,
			    "ee.holiday_approve" => $holiday_approve

			);
		
			
			#$where2 = array(
			#	"at.level !=" => "",
			#	'ee.source !=' => 'mobile'
			#);

			if($search != "" && $search != "all"){
				$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
			}

			$select = array(
			    "ee.work_schedule_id",
			    "ee.comp_id",
			    "at.flag_add_logs",
			    "epi.attendance_adjustment_approval_grp",
			    "epi.add_logs_approval_grp",
			    "epi.location_base_login_approval_grp",
			    "ee.employee_time_in_id",
			    "at.level",
			    "ee.time_in_status",
			    "at.approve_by_hr",
			    "ee.emp_id",
			    "ee.date",
			    "ee.source",
			    "ee.time_in",
			    "ee.lunch_out",
			    "ee.lunch_in",
			    "ee.break1_out",
			    "ee.break1_in",
			    "ee.break2_out",
			    "ee.break2_in",
			    "ee.time_out",
			    "ee.total_hours",
			    "ee.total_hours_required",
			    "ee.tardiness_min",
			    "ee.undertime_min",
			    "ee.change_log_time_in",
			    "ee.change_log_lunch_out",
			    "ee.change_log_lunch_in",
			    "ee.change_log_time_out",
			    "ee.change_log_break1_out",
			    "ee.change_log_break1_in",
			    "ee.change_log_break2_out",
			    "ee.change_log_break2_in",
			    "ee.change_log_total_hours",
			    "ee.change_log_total_hours_required",
			    "ee.change_log_tardiness_min",
			    "ee.change_log_undertime_min",
			    "ee.location_1",
			    "ee.location_2",
			    "ee.location_3",
				"ee.location_4",
				"ee.location_5",
			    "ee.location_6",
			    "ee.location_7",
			    "ee.location_8",
			    "ee.reason",
			    "ee.change_log_date_filed",
			    "ee.last_source",
			    "e.company_id",
			    "ee.notes",
			    "pg.name AS pg_name",
			    "ee.rest_day_r_a",
			    "ee.holiday_approve",
			    #"ee.rest_day_premium",
			    #"ee.holiday_premium",
			    #"ee.convert_to_cto",
			    #"ee.convert_to_cto_holiday",
			    "ee.flag_rd_include",
			    "ee.flag_holiday_include",
				"ee.for_resend_auto_rejected_id",
				"at.approval_time_in_id"
			);
			
			$select1 = array(
			    "a.payroll_cloud_id",
			    "e.first_name",
				"e.last_name",
				"a.profile_image"
			);

			$this->db->select($select);
			$this->edb->select($select1);
			
			$this->db->where_in("app.name", array("Add Timesheet", "Timesheet Adjustment"));
			$this->db->where($where);
			$this->db->where(" (ee.source ='EP' OR ee.last_source='Adjusted') ",NULL,FALSE);			
			$this->edb->join('employee AS e','e.emp_id = ee.emp_id','LEFT');
			$this->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','LEFT');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id OR epi.add_logs_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			
			$this->edb->join("approval_time_in AS at","at.approval_time_in_id= ee.approval_time_in_id","LEFT");
			$this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND at.level = ag.level","LEFT");
			$this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
			$this->edb->join("work_schedule AS ws","ee.work_schedule_id = ws.work_schedule_id","LEFT");
			$this->db->group_by("ee.employee_time_in_id");
			$this->db->order_by("ee.date","DESC");

			$query = $this->edb->get('employee_time_in AS ee');
			$result = $query->result();
			$query->free_result();
			
			return ($result) ? $result : false;
			
		}
		
		/**
		 * get the lists of time in that is waiting for approval (employee approver side)
		 * @param int $emp_id
		 * @param int $company_id
		 * @param number $limit
		 * @param number $start
		 * @param string $search
		 * @param string $sort_by
		 * @param string $sort
		 * @return object|boolean
		 */
		public function timein_list($emp_id, $company_id, $search = ""){
			if(is_numeric($company_id)){
				$konsum_key = konsum_key();
				$filter = $this->not_in_search_timein($emp_id, $company_id, $search);
// 				$start = intval($start);
// 				$limit = intval($limit);
// 				$sort_array = array(
// 						"last_name",
// 						"pg_name",
// 						"date",
// 						"change_log_total_hours",
// 						"time_in_status"
// 				);
				//$level = $this->timein_approval_level($this->session->userdata('emp_id'));
				$where = array(
                    'ee.comp_id'   => $company_id,
                    'ee.status'   => 'Active',
                    'ee.corrected' => 'Yes',
                    'ee.time_in_status'=> 'pending',
                    'ag.emp_id' => $emp_id,
                    'ee.time_in_status'=> 'pending',
                    'ee.source !=' => 'mobile'
				);
				$where2 = array(
					"at.level !=" => "",
                    'ee.source !=' => 'mobile'
				);
				
				if($search != "" && $search != "all"){
 					$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
 				}
				
 				$select = array(
 				    'ee.change_log_date_filed',
 				    'ee.time_in_status',
 				    'ee.date',
 				    'ee.time_in',
 				    'ee.location_1',
 				    'ee.lunch_out',
 				    'ee.location_2',
 				    'ee.lunch_in',
 				    'ee.location_3',
 				    'ee.time_out',
 				    'ee.location_4',
 				    'at.flag_add_logs',
 				    'ee.change_log_time_in',
 				    'ee.change_log_lunch_out',
 				    'ee.change_log_lunch_in',
 				    'ee.change_log_time_out',
 				    'ee.reason',
 				    'ee.source',
 				    'ee.employee_time_in_id'
 				);
 				
 				$edb_select = array(
 				    'e.first_name',
 				    'e.last_name',
 				    'a.payroll_cloud_id',
 				    'a.profile_image'
 				);
				
 				$this->db->select($select);
 				$this->edb->select($edb_select);
				$this->edb->where($where);
				$this->db->where($where2);
				if($filter != FALSE){
					$this->db->where("ee.employee_time_in_id NOT IN ({$filter})");
				}
				$this->db->select($select);
				//$this->db->select('CONCAT('.$this->edb->decrypt('e.first_name').'," ",'.
				//$this->edb->decrypt('e.last_name').') as full_name',FALSE);
				$this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
				$this->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
				$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
				$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
				$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
				$this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
				$this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
				$this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
				$this->edb->join("approval_time_in AS at","at.approval_time_in_id= ee.approval_time_in_id","LEFT");
				$this->edb->join("work_schedule AS ws","ee.work_schedule_id = ws.work_schedule_id","LEFT");
				$this->db->group_by("employee_time_in_id");
// 				if($sort_by != ""){
// 					if(in_array($sort_by, $sort_array)){
// 						$this->db->order_by($sort_by,$sort);
// 					}
// 				}
// 				else{
// 					$this->db->order_by("date","DESC");
// 				}
					
				$query = $this->edb->get('employee_time_in AS ee');
				//$query = $this->edb->get('employee_time_in AS ee');
				$result = $query->result();
				$query->free_result();
				
				return $result;
			}else{
				return false;
			}
		}
		/**
		 * Gets the count of the time in lists that is waiting for approval for the pagination (right now, this is no use because we have made a custom counter)
		 * @param int $emp_id
		 * @param int $company_id
		 * @param string $search
		 * @return number|boolean
		 */
		public function timein_list_count($emp_id, $company_id,$search=""){
			if(is_numeric($company_id)){
				$konsum_key = konsum_key();
				$filter = $this->not_in_search_timein($emp_id, $company_id, $search);
				$level = $this->timein_approval_level($this->session->userdata('emp_id'));
				$where = array(
						'ee.comp_id'   => $company_id,
						'ee.status'   => 'Active',
						'ee.corrected' => 'Yes',
						'ee.time_in_status' => 'pending'
						
				
				);
				$where2 = array(
						"at.level !=" => ""
				);
				
				$this->db->select('count(*) as val');
		
				if($search != "" && $search != "all"){
 					$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
 				}
				
				$this->edb->where($where);
				$this->db->where($where2);
				if($filter != FALSE){
					$this->db->where("ee.employee_time_in_id NOT IN ({$filter})");
				}
			
				$this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
				$this->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
				$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
				$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
				$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
				$this->edb->join("approval_time_in AS at","at.approval_time_in_id= ee.approval_time_in_id","LEFT");
				$this->edb->join("work_schedule AS ws","ee.work_schedule_id = ws.work_schedule_id","LEFT");
				$this->db->group_by("employee_time_in_id");
				$query = $this->edb->get('employee_time_in AS ee');
		
				$row = $query->row();
	
				return ($row) ? $query->num_rows : 0;
			}else{
				return false;
			}
		}
		/**
		 * Gets the count of the time in lists that is waiting for approval for the pagination (right now, this is no use because we have made a custom counter)
		 * @param int $emp_id
		 * @param int $company_id
		 * @param string $search
		 * @return number|boolean
		 */
		public function not_in_search_timein($emp_id, $company_id,$search=""){
			if(is_numeric($company_id)){
				$konsum_key = konsum_key();
				$level = $this->timein_approval_level($this->session->userdata('emp_id'));
			
				$where = array(
						'ee.comp_id'   => $company_id,
						'ee.status'   => 'Active',
						'ee.corrected' => 'Yes',
						'ee.time_in_status' => 'pending'
		
				);
				$where2 = array(
						"at.level !=" => ""
				);
				if($search != "" && $search != "all"){
					$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
				}
				
				$select = array(
				    'at.flag_add_logs',
				    'epi.attendance_adjustment_approval_grp',
				    'at.level',
				    'ee.employee_time_in_id',
				    'epi.location_base_login_approval_grp',
				    'epi.attendance_adjustment_approval_grp',
				    'epi.add_logs_approval_grp'
				);
				
				$this->edb->select($select);
		
				$this->edb->where($where);
				$this->db->where($where2);
				$this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
				$this->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
				$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
				$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
				$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
				$this->edb->join("approval_time_in AS at","at.approval_time_in_id= ee.approval_time_in_id","LEFT");
				$this->db->group_by("employee_time_in_id");
				$query = $this->edb->get('employee_time_in AS ee');
				$result = $query->result();
				
				$arrs = array();
				if($result){
					$is_assigned = TRUE;
					$hours_notification = get_hours_notification_settings($this->company_id);
					foreach($result as $key => $approvers){
						if($approvers->flag_add_logs == 0){
							$appr_grp = $approvers->attendance_adjustment_approval_grp;
						}elseif($approvers->flag_add_logs == 1){
							$appr_grp = $approvers->add_logs_approval_grp;
						}elseif($approvers->flag_add_logs == 2){
							$appr_grp = $approvers->location_base_login_approval_grp;
						}
							
						$level = $approvers->level;
							
						$check = $this->check_assigned_hours($appr_grp, $level);
						//	echo $emp->employee_time_in_id.' - '. $emp->approval_time_in_id.' - '.$check.'</br>';
						/*if($hours_notification->option == "choose level notification"){
							$is_assigned = check_if_is_level_hours($level, $hours_notification->hours_alerts_notification_id);
						}*/
						if($check && $is_assigned){
						
						}else{
							array_push($arrs, $approvers->employee_time_in_id);
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
		 * @param int $overtime_appr_grp
		 * @param int $level
		 * @return boolean
		 */
		public function timein_approval_level($emp_id){
			if(is_numeric($emp_id)){
				$this->db->where('ap.name','Time In Approval Group');
				$this->db->or_where('ap.name','Mobile Clock-in');
				$this->db->or_where('ap.name','Add Timesheet');
				$this->db->where('ag.emp_id',$emp_id);
				$this->db->select('ag.level AS level');
				$this->db->join('approval_process AS ap','ap.approval_process_id = ag.approval_process_id','LEFT');
				$q = $this->db->get('approval_groups AS ag');
				$r = $q->row();
		
				return ($r) ? $r : FALSE;
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
		public function check_assigned_hours($hours_appr_grp, $level){
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
		/**
		 * gets the token of the time in adjustment
		 * @param unknown $leave_ids
		 * @param unknown $comp_id
		 * @param unknown $emp_id
		 * @param unknown $appr_timein_id
		 * @return string
		 */
		public function get_token($leave_ids,$comp_id,$emp_id, $appr_timein_id){
			$w = array(
					"time_in_id"=>$leave_ids,
					"comp_id"=>$comp_id,
					"emp_id"=>$emp_id,
					"status"=>"Active",
					"approval_time_in_id"=>$appr_timein_id
			);
			$this->db->where($w);
			$q = $this->db->get("approval_time_in");
			$row = $q->row();
			return ($q->num_rows() > 0) ? $row->token : "" ;
		}
		/**
		 * gets the time in adjustment data
		 * @param unknown $val
		 * @param unknown $appr_timein_id
		 * @return unknown|boolean
		 */
		public function get_employee_time_in($val){
		    if(is_numeric($val)){
		        
		        $where = array(
		            'ee.employee_time_in_id'   => $val
		        );
		        
		        $this->db->where($where);
		        $this->edb->join('company AS c','c.company_id = ee.comp_id','left');
		        $this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
		        $this->edb->join("approval_time_in AS ti","ti.approval_time_in_id = ee.approval_time_in_id","LEFT");
		        $this->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
		        $this->edb->join('accounts AS a','a.account_id = e.account_id','left');
		        $query = $this->edb->get('employee_time_in AS ee');
		        //$query = $this->edb->get('employee_time_in AS ee');
		        $result = $query->row();
		        $query->free_result();
		        return $result;
		    }else{
		        return false;
		    }
		}
		
		public function not_in_search_split_timein($emp_id, $company_id,$search="")
		{
		    if (is_numeric($company_id)) {
				$level = $this->timein_approval_level($this->session->userdata('emp_id'));

				$select = array(
				    "at.flag_add_logs",
				    "epi.attendance_adjustment_approval_grp",
				    "epi.add_logs_approval_grp",
				    "epi.location_base_login_approval_grp",
				    "sbti.schedule_blocks_time_in_id",
				    "at.level"
				);
				
				$this->db->select($select);
				
				$where = array(
					'sbti.comp_id' => $company_id,
					'sbti.status' => 'Active',
					'sbti.corrected' => 'Yes',
				    'sbti.time_in_status' => 'pending',
				    'sbti.flag_payroll_correction' => 'no',
				    "at.level !=" => ""
				);

				$this->db->where($where);
				$this->db->join('employee_payroll_information AS epi','epi.emp_id = sbti.emp_id','left');
				$this->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
				$this->db->join("approval_time_in AS at","at.approval_time_in_id= sbti.approval_time_in_id","LEFT");
				$this->db->group_by("sbti.schedule_blocks_time_in_id");
				$query = $this->db->get('schedule_blocks_time_in AS sbti');
				$result = $query->result();

				$arrs = array();
				if($result){
					$is_assigned = TRUE;
					$hours_notification = get_hours_notification_settings($this->company_id);
					foreach($result as $key => $approvers){
						if($approvers->flag_add_logs == 0){
							$appr_grp = $approvers->attendance_adjustment_approval_grp;
						}elseif($approvers->flag_add_logs == 1){
							$appr_grp = $approvers->add_logs_approval_grp;
						}elseif($approvers->flag_add_logs == 2){
							$appr_grp = $approvers->location_base_login_approval_grp;
						}

						$level = $approvers->level;

						$check = $this->check_assigned_hours($appr_grp, $level);
						
						if($check && $is_assigned){

						}else{
							array_push($arrs, $approvers->schedule_blocks_time_in_id);
						}
					}

				}
				$string = implode(",", $arrs);
				return $string;
			}else{
				return false;
			}
		}
		
		public function split_timein_list1($emp_id, $company_id, $search = "", $sort_by = "", $sort = "ASC"){
			if(is_numeric($company_id)){
				$konsum_key = konsum_key();
				$filter = $this->not_in_search_split_timein($emp_id, $company_id, $search);

				$sort_array = array(
					"last_name",
					"pg_name",
					"date",
					"change_log_total_hours",
					"time_in_status"
				);

				$where = array(
					'sbti.comp_id'   		=> $company_id,
					'sbti.status'   		=> 'Active',
					'sbti.corrected' 		=> 'Yes',
					"ag.emp_id" 			=> $emp_id,
				    'sbti.time_in_status'	=> 'pending',
				    'sbti.source !=' => 'mobile',
				    'sbti.flag_payroll_correction' => 'no'

				);
				$where2 = array(
				    "at.level !=" => "",
				    'sbti.source !=' => 'mobile'
				);

				if($search != "" && $search != "all"){
					$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
				}

				$select = array(
				    #"*",
				    "sbti.work_schedule_id",
				    "sbti.comp_id",
				    "at.flag_add_logs",
				    "epi.attendance_adjustment_approval_grp",
				    "epi.add_logs_approval_grp",
				    "epi.location_base_login_approval_grp",
				    "sbti.employee_time_in_id",
				    "at.level",
				    "sbti.time_in_status",
				    "at.approve_by_hr",
				    "sbti.emp_id",
				    "sbti.date",
				    "sbti.source",
				    "sbti.time_in",
				    "sbti.lunch_out",
				    "sbti.lunch_in",
				    "sbti.time_out",
				    "sbti.total_hours",
				    "sbti.total_hours_required",
				    "sbti.tardiness_min",
				    "sbti.undertime_min",
				    "sbti.change_log_time_in",
				    "sbti.change_log_lunch_out",
				    "sbti.change_log_lunch_in",
				    "sbti.change_log_time_out",
				    "sbti.change_log_total_hours",
				    "sbti.change_log_total_hours_required",
				    "sbti.change_log_tardiness_min",
				    "sbti.change_log_undertime_min",
				    "sbti.location_1",
				    "sbti.location_2",
				    "sbti.location_3",
				    "sbti.location_4",
				    "sbti.reason",
				    "sbti.change_log_date_filed",
				    "sbti.last_source",
				    "e.company_id",
				    "sbti.notes",
				    "pg.name AS pg_name",
				    "sbti.schedule_blocks_time_in_id"
				);
				
				$select1 = array(
				    "a.payroll_cloud_id",
				    "e.first_name",
				    "e.last_name",
				);
				
				$this->db->select($select);
				$this->edb->select($select1);

				$this->db->where($where);
				$this->db->where($where2);
				if($filter != FALSE){
					$this->db->where("sbti.schedule_blocks_time_in_id NOT IN ({$filter})");
				}
				
				$this->edb->join('employee AS e','e.emp_id = sbti.emp_id','left');
				$this->edb->join('employee_payroll_information AS epi','epi.emp_id = sbti.emp_id','left');
				$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
				#$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
				$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
				$this->edb->join("approval_time_in AS at","at.approval_time_in_id= sbti.approval_time_in_id","LEFT");
				$this->edb->join("approval_groups_via_groups AS agg","epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id OR epi.add_logs_approval_grp = agg.approval_groups_via_groups_id","LEFT");
				$this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
				$this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
				$this->db->join("employee_sched_block AS esb","sbti.schedule_blocks_id = esb.schedule_blocks_id","LEFT");
				$this->db->join("schedule_blocks AS sb","sb.work_schedule_id = esb.work_schedule_id","LEFT");
				$this->edb->join("work_schedule AS ws","sbti.work_schedule_id = ws.work_schedule_id","LEFT");
				$this->db->group_by("schedule_blocks_time_in_id");
				if($sort_by != ""){
					if(in_array($sort_by, $sort_array)){
						$this->db->order_by($sort_by,$sort);
					}
				}
				else{
					$this->db->order_by("date","DESC");
				}

				$query = $this->edb->get('schedule_blocks_time_in AS sbti');
				$result = $query->result();
				$query->free_result();

				return $result;
			}else{
				return false;
			}
		}
		
		public function get_company_subdomain()
		{
		    $where = array(
		        'company_id' => $this->company_id
		    );
		    
		    $this->db->select('sub_domain');
		    $this->db->where($where);
		    $q = $this->db->get('company');
		    $r = $q->row();
		    
		    return ($r) ? $r->sub_domain : false;
		}
		
		public function get_employee_split_time_in($val){
		    if(is_numeric($val)){
		        
		        $where = array(
		            'sbti.schedule_blocks_time_in_id' => $val
		        );
		        
		        $this->db->where($where);
		        $this->edb->join('company AS c','c.company_id = sbti.comp_id','left');
		        $this->edb->join('employee AS e','e.emp_id = sbti.emp_id','left');
		        $this->edb->join("approval_time_in AS ti","ti.approval_time_in_id = sbti.approval_time_in_id","LEFT");
		        $this->edb->join('employee_payroll_information AS epi','epi.emp_id = sbti.emp_id','left');
		        $this->edb->join('accounts AS a','a.account_id = e.account_id','left');
		        $query = $this->edb->get('schedule_blocks_time_in AS sbti');
		        //$query = $this->edb->get('employee_time_in AS ee');
		        $result = $query->row();
		        $query->free_result();
		        return $result;
		    }else{
		        return false;
		    }
		}

		public function timein_late($emp_id, $company_id, $search = "")
		{
			if (!is_numeric($company_id)) {
				return false;
			}

			$konsum_key = konsum_key();
			$filter = $this->not_in_search_timein_late($emp_id, $company_id, $search);

			$where = array(
	            'ee.comp_id'   		=> $company_id,
	            'ee.status'   		=> 'Active',
	            'ee.corrected' 		=> 'Yes',
	            'ag.emp_id' 		=> $emp_id,
	            'ee.time_in_status'	=> 'pending',
	            'ee.source !=' 		=> 'mobile',
	            'ee.flag_payroll_correction' => 'yes',
	            'at.level !=' 		=> ''
	        );

	        $select = array(
			    'ee.change_log_date_filed',
			    'ee.time_in_status',
			    'ee.date',
			    'ee.time_in',
			    'ee.location_1',
			    'ee.lunch_out',
			    'ee.location_2',
			    'ee.lunch_in',
			    'ee.location_3',
			    'ee.time_out',
			    'ee.location_4',
			    'at.flag_add_logs',
			    'ee.change_log_time_in',
			    'ee.change_log_lunch_out',
			    'ee.change_log_lunch_in',
			    'ee.change_log_time_out',
			    'ee.reason',
			    'ee.source',
			    'ee.employee_time_in_id'
			);
			
			$edb_select = array(
			    'e.first_name',
			    'e.last_name',
			    'a.payroll_cloud_id',
			    'a.profile_image'
			);

	        if($search != "" && $search != "all"){
	            $this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
	        }

	        $this->db->select($select);
	        $this->edb->select($edb_select);
	        $this->db->where($where);

	        if($filter != FALSE){
	            $this->db->where("ee.employee_time_in_id NOT IN ({$filter})");
	        }

	        $this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
	        $this->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
	        $this->edb->join('accounts AS a','a.account_id = e.account_id','left');
	        
	        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
	        $this->edb->join("approval_groups_via_groups AS agg","epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id OR epi.add_logs_approval_grp = agg.approval_groups_via_groups_id","LEFT");
	        $this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
	        $this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
	        $this->edb->join("approval_time_in AS at","at.approval_time_in_id= ee.approval_time_in_id","LEFT");
	        $this->edb->join("work_schedule AS ws","ee.work_schedule_id = ws.work_schedule_id","LEFT");
	        $this->db->group_by("ee.employee_time_in_id");

	        $query = $this->edb->get('employee_time_in AS ee');
	        $result = $query->result();
	        $query->free_result();
	        
	        return ($result) ? $result : false;
		}

		public function not_in_search_timein_late($emp_id, $company_id,$search="")
		{
			if (!is_numeric($company_id)) {
				return false;
			}

			$level = $this->timein_approval_level($emp_id);

			$select = array(
	            "ee.employee_time_in_id",
	            "at.level",
	            "at.flag_add_logs",
	            "epi.attendance_adjustment_approval_grp",
	            "epi.add_logs_approval_grp",
	            "epi.location_base_login_approval_grp"
	        );
	        
	        $this->db->select($select);
	        
	        $where = array(
	            'ee.comp_id'   => $company_id,
	            'ee.status'   => 'Active',
	            'ee.corrected' => 'Yes',
	            "ag.emp_id" => $emp_id,
	            'ee.time_in_status' => 'pending',
	            'ee.source !=' => 'mobile',
	            'ee.flag_payroll_correction' => 'yes',
	            "at.level !=" => "",
	            
	        );
	        
	        $this->db->where($where);
	        
	        $this->db->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
	        $this->db->join("approval_groups_via_groups AS agg","epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id","LEFT");
	        $this->db->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
	        $this->db->join("approval_time_in AS at","at.approval_time_in_id= ee.approval_time_in_id","LEFT");
	        $this->db->group_by("employee_time_in_id");
	        $query = $this->db->get('employee_time_in AS ee');
	        $result = $query->result();
	        
	        $arrs = array();
	        if($result){
	            $is_assigned = TRUE;
	            $hours_notification = get_hours_notification_settings($this->company_id);
	            foreach($result as $key => $approvers){
	                if($approvers->flag_add_logs == 0){
	                    $appr_grp = $approvers->attendance_adjustment_approval_grp;
	                }elseif($approvers->flag_add_logs == 1){
	                    $appr_grp = $approvers->add_logs_approval_grp;
	                }elseif($approvers->flag_add_logs == 2){
	                    $appr_grp = $approvers->location_base_login_approval_grp;
	                }
	                
	                $level = $approvers->level;
	                
	                $check = $this->check_assigned_hours($appr_grp, $level);
	                
	                if($check && $is_assigned){
	                    
	                }else{
	                    array_push($arrs, $approvers->employee_time_in_id);
	                }
	            }
	        }
	        $string = implode(",", $arrs);
	        return $string;
		}

		public function timein_approvpal_level($emp_id)
		{
			if (!is_numeric($emp_id)) {
				return false;
			}

			$this->db->or_where('ap.name','Timesheet Adjustment');
			$this->db->or_where('ap.name','Mobile Clock-in');
			$this->db->or_where('ap.name','Add Timesheet');
			$this->db->where('ag.emp_id',$emp_id);
			$this->db->select('ag.level AS level');
			$this->db->join('approval_process AS ap','ap.approval_process_id = ag.approval_process_id','LEFT');
			$q = $this->db->get('approval_groups AS ag');
			$r = $q->row_array();

			return ($r) ? $r : FALSE;
		}

		public function split_timein_late($emp_id, $company_id, $search = "")
		{
			if (!is_numeric($emp_id)) {
				return false;
			}

			$konsum_key = konsum_key();
			$filter = $this->not_in_search_split_timein_late($emp_id, $company_id, $search);

			$select = array(
	            'sbti.schedule_blocks_time_in_id',
	            'sbti.employee_time_in_id',
	            'sbti.work_schedule_id',
	            'sbti.split_schedule_id',
	            'sbti.date',
	            'sbti.time_in',
	            'sbti.lunch_out',
	            'sbti.lunch_in',
	            'sbti.time_out',
	            'sbti.reason',
	            'sbti.time_in_status',
	            'sbti.source',
	            'sbti.change_log_date_filed',
	            'sbti.change_log_time_in',
	            'sbti.change_log_lunch_out',
	            'sbti.change_log_lunch_in',
	            'sbti.change_log_time_out',
	            'sbti.location_1',
	            'sbti.location_2',
	            'sbti.location_3',
	            'sbti.location_4',
	            'at.flag_add_logs'
	        );
	        
	        $edb_select = array(
	            'a.payroll_cloud_id',
	            'a.profile_image',
	            'e.last_name',
	            'e.first_name',
	            'e.middle_name'
	        );

	        $where = array(
	            'sbti.comp_id'   		=> $company_id,
	            'sbti.status'   		=> 'Active',
	            'sbti.corrected' 		=> 'Yes',
	            'ag.emp_id' 			=> $emp_id,
	            'sbti.time_in_status'	=> 'pending',
	            'sbti.source !=' 		=> 'mobile',
	            'sbti.flag_payroll_correction' => 'yes',
	            'at.level !='			=> ''
	        );

	        if($search != "" && $search != "all"){
	            $this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
	        }

	        $this->db->select($select);
	        $this->edb->select($edb_select);
	        $this->db->where($where);
	        if($filter != FALSE){
	            $this->db->where("sbti.schedule_blocks_time_in_id NOT IN ({$filter})");
	        }
	        $this->edb->join('employee AS e','e.emp_id = sbti.emp_id','left');
	        $this->edb->join('employee_payroll_information AS epi','epi.emp_id = sbti.emp_id','left');
	        $this->edb->join('accounts AS a','a.account_id = e.account_id','left');
	        
	        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
	        $this->edb->join("approval_time_in AS at","at.approval_time_in_id= sbti.approval_time_in_id","LEFT");
	        $this->edb->join("approval_groups_via_groups AS agg","epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id OR epi.add_logs_approval_grp = agg.approval_groups_via_groups_id","LEFT");
	        $this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
	        $this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
	        $this->db->join("employee_sched_block AS esb","sbti.schedule_blocks_id = esb.schedule_blocks_id","LEFT");
	        $this->db->join("schedule_blocks AS sb","sb.work_schedule_id = esb.work_schedule_id","LEFT");
	        $this->edb->join("work_schedule AS ws","sbti.work_schedule_id = ws.work_schedule_id","LEFT");
	        $this->db->group_by("schedule_blocks_time_in_id");
	        $this->db->order_by("date","DESC");

	        $query = $this->edb->get('schedule_blocks_time_in AS sbti');
	        $result = $query->result();
	        $query->free_result();
	        
	        return $result;
		}

		public function not_in_search_split_timein_late($emp_id, $company_id, $search="")
		{
			if (!is_numeric($company_id)) {
				return false;
			}

			$level = $this->timein_approval_level($emp_id);
			$select = array(
	            "at.flag_add_logs",
	            "epi.attendance_adjustment_approval_grp",
	            "epi.add_logs_approval_grp",
	            "epi.location_base_login_approval_grp",
	            "sbti.schedule_blocks_time_in_id",
	            "at.level"
	        );
	        
	        $this->db->select($select);
	        
	        $where = array(
	            'sbti.comp_id'   => $company_id,
	            'sbti.status'   => 'Active',
	            'sbti.corrected' => 'Yes',
	            'sbti.time_in_status' => 'pending',
	            'sbti.source !=' => 'mobile',
	            'sbti.flag_payroll_correction' => 'yes'
	            
	        );
	        
	        $this->db->where($where);
	        $this->db->join('employee_payroll_information AS epi','epi.emp_id = sbti.emp_id','left');
	        $this->db->join("approval_time_in AS at","at.approval_time_in_id= sbti.approval_time_in_id","LEFT");
	        $this->db->group_by("sbti.schedule_blocks_time_in_id");
	        $query = $this->db->get('schedule_blocks_time_in AS sbti');
	        $result = $query->result();
	        
	        $arrs = array();
	        if($result){
	            $is_assigned = TRUE;
	            $hours_notification = get_hours_notification_settings($this->company_id);
	            foreach($result as $key => $approvers){
	                if($approvers->flag_add_logs == 0){
	                    $appr_grp = $approvers->attendance_adjustment_approval_grp;
	                }elseif($approvers->flag_add_logs == 1){
	                    $appr_grp = $approvers->add_logs_approval_grp;
	                }elseif($approvers->flag_add_logs == 2){
	                    $appr_grp = $approvers->location_base_login_approval_grp;
	                }
	                
	                $level = $approvers->level;
	                
	                $check = $this->check_assigned_hours($appr_grp, $level);
	                
	                if($check && $is_assigned){
	                    
	                }else{
	                    array_push($arrs, $approvers->schedule_blocks_time_in_id);
	                }
	            }
	            
	        }
	        $string = implode(",", $arrs);
	        return $string;
        }
        
        public function timeins_info($timein_id,$comp_id){
            $s = array(
                'employee_time_in_id',
                'change_log_date_filed',
                'change_log_time_in',
                'change_log_lunch_out',
                'change_log_lunch_in',
                'change_log_time_out',
                'emp_id',
                'comp_id',
                'date',
                'time_in',
                'lunch_out',
                'lunch_in',
                'time_out',
                
                "break1_out",
                "break1_in",
                "break2_out",
                "break2_in",
                
                'total_hours',
                'total_hours_required',
                'change_log_total_hours',
                'change_log_total_hours_required',
                'change_log_tardiness_min',
                'change_log_undertime_min',
                'change_log_absent_min',
                'change_log_overbreak_min',
                'change_log_late_min',
                
                "change_log_break1_out",
                "change_log_break1_in",
                "change_log_break2_out",
                "change_log_break2_in",
                
                'change_log_date',
                'change_log_work_schedule_id',
                
                'late_min',
                'overbreak_min',
                'tardiness_min',
                'undertime_min',
                'absent_min',
                'source',
                'work_schedule_id',
                
                "reason",
                "approval_time_in_id",
                "flag_regular_or_excess",
                "rest_day_r_a",
                "flag_rd_include",
                //"holiday_premium",
                "flag_holiday_include",
                "timesheet_not_req_flag",
                "partial_log_ded_break",
                "flag_open_shift",
                "os_approval_time_in_id",
                "approval_date"
            );
            
            $w = array(
                'employee_time_in_id' => $timein_id,
                'comp_id' => $comp_id
            );
            
            $this->db->select($s);
            $this->db->where($w);
            $q = $this->db->get("employee_time_in");
            $result = $q->row();
            
            return ($result) ? $result : FALSE ;
        }

        public function timein_list1($emp_id, $company_id, $search = "", $sort_by = "", $sort = "ASC", $rest_day_r_a = "no", $holiday_approve = "no"){
			if(is_numeric($company_id)){
				$konsum_key = konsum_key();
				$filter = false; //$this->not_in_search_timein($emp_id, $company_id, $search);
				
				$sort_array = array(
					"last_name",
					"pg_name",
					"date",
					"change_log_total_hours",
					"time_in_status"
				);
				
				$where = array(
					'ee.comp_id'   => $company_id,
					'ee.status'   => 'Active',
					'ee.corrected' => 'Yes',
					"ag.emp_id" => $emp_id,
					'ee.time_in_status'=> 'pending',
					'ee.source !=' => 'mobile',
				    'ee.flag_payroll_correction' => 'no',
				    "at.level !=" => "",
				    "ee.rest_day_r_a" => $rest_day_r_a,
				    "ee.holiday_approve" => $holiday_approve

				);
				#$where2 = array(
				#	"at.level !=" => "",
				#	'ee.source !=' => 'mobile'
				#);

				if($search != "" && $search != "all"){
					$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
				}

				$select = array(
				    "ee.work_schedule_id",
				    "ee.comp_id",
				    "at.flag_add_logs",
				    "epi.attendance_adjustment_approval_grp",
				    "epi.add_logs_approval_grp",
				    "epi.location_base_login_approval_grp",
				    "ee.employee_time_in_id",
				    "at.level",
				    "ee.time_in_status",
				    "at.approve_by_hr",
				    "ee.emp_id",
				    "ee.date",
				    "ee.source",
				    "ee.time_in",
				    "ee.lunch_out",
				    "ee.lunch_in",
				    "ee.break1_out",
				    "ee.break1_in",
				    "ee.break2_out",
				    "ee.break2_in",
				    "ee.time_out",
				    "ee.total_hours",
				    "ee.total_hours_required",
				    "ee.tardiness_min",
				    "ee.undertime_min",
				    "ee.change_log_time_in",
				    "ee.change_log_lunch_out",
				    "ee.change_log_lunch_in",
				    "ee.change_log_time_out",
				    "ee.change_log_break1_out",
				    "ee.change_log_break1_in",
				    "ee.change_log_break2_out",
				    "ee.change_log_break2_in",
				    "ee.change_log_total_hours",
				    "ee.change_log_total_hours_required",
				    "ee.change_log_tardiness_min",
				    "ee.change_log_undertime_min",
				    "ee.location_1",
				    "ee.location_2",
				    "ee.location_3",
				    "ee.location_4",
				    "ee.reason",
				    "ee.change_log_date_filed",
				    "ee.last_source",
				    "e.company_id",
				    "ee.notes",
				    "pg.name AS pg_name",
				    "ee.rest_day_r_a",
				    "ee.holiday_approve",
				    #"ee.rest_day_premium",
				    #"ee.holiday_premium",
				    #"ee.convert_to_cto",
				    #"ee.convert_to_cto_holiday",
				    "ee.flag_rd_include",
				    "ee.flag_holiday_include",
				    "ee.for_resend_auto_rejected_id"
				);
				
				$select1 = array(
				    "a.payroll_cloud_id",
				    "e.first_name",
				    "e.last_name",
				);

				$this->db->select($select);
				$this->edb->select($select1);
				
				$this->db->where_in("app.name", array("Add Timesheet", "Timesheet Adjustment"));
				$this->db->where($where);
				#$this->db->where($where2); 
				if($filter != FALSE){
					$this->db->where("ee.employee_time_in_id NOT IN ({$filter})");
				}
				
				$this->edb->join('employee AS e','e.emp_id = ee.emp_id','LEFT');
				$this->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','LEFT');
				$this->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
				$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
				$this->edb->join("approval_groups_via_groups AS agg","epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id OR epi.add_logs_approval_grp = agg.approval_groups_via_groups_id","LEFT");
				
				$this->edb->join("approval_time_in AS at","at.approval_time_in_id= ee.approval_time_in_id","LEFT");
				$this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id AND at.level = ag.level","LEFT");
				$this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
				$this->edb->join("work_schedule AS ws","ee.work_schedule_id = ws.work_schedule_id","LEFT");
				$this->db->group_by("ee.employee_time_in_id");
				if($sort_by != ""){
					if(in_array($sort_by, $sort_array)){
						$this->db->order_by($sort_by,$sort);
					}
				} else {
					$this->db->order_by("ee.date","DESC");
				}

				$query = $this->edb->get('employee_time_in AS ee');
				$result = $query->result();
				$query->free_result();

				return $result;
			}else{
				return false;
			}
		}
	}
	
		