<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Approve LEave model for approving overtime , leaves , loans
 *
 * @category Model
 * @version 1.0
 * @author Christopher Cuizon <christophercuizons@gmail.com>
 */
	class Approve_timeins_model extends CI_Model {
		
		/**
		 * CHECKS APPLICATION LEAVE FOR EVERY COMPANY
		 * @param int $company_id
		 * @return object
		 */
		public function timeins_list($company_id,$limit,$start){
			if(is_numeric($company_id)){
				$start = intval($start);
				$limit = intval($limit);
				
				$where = array(
					'eti.comp_id'   => $company_id,
					'eti.status'   => 'Active',
					'eti.corrected' => 'Yes'
				);
				$this->edb->where($where);
				$this->db->select('CONCAT('.$this->edb->decrypt('e.first_name').'," ",'.
					$this->edb->decrypt('e.last_name').') as full_name',FALSE);
				$this->edb->join('employee AS e','e.emp_id = eti.emp_id');
				$this->edb->join('accounts AS a','a.account_id = e.account_id');
				$this->edb->order_by("e.last_name","ASC");
				
				$query = $this->edb->get('employee_time_in AS eti',$limit,$start);
								
				$result = $query->result();
				$query->free_result();
				return $result;
			}else{
				return false;
			}
	
		}
		
		
		public function get_company_info($company_id){
			$where = array(
					'company_id'   => $company_id
			);
			$this->edb->where($where);
			$query = $this->edb->get('company');
			$row = $query->row();
			$num_row = $query->num_rows();
			$query->free_result();
			return ($num_row > 0)? $row : false;
		}
		
		/**
		 * Update global fields
		 * use for all
		 * @param string $database
		 * @param array $fields
		 * @param array $where
		 */
		public function update_field($database,$fields,$where){
			$this->db->where($where);
			$this->db->update($database,$fields);
			return $this->db->affected_rows();
		}
		
		/**
		 * Expenses application counts dates
		 * check expenses application and count to date
		 * @param int $company_id
		 * @param int $date_from
		 * @param dates $date_to
		 * @return integer
		 */
		public function timeins_application_count_date($company_id,$date_from,$date_to){
			if(is_numeric($company_id)){
				$date_from = $this->db->escape($date_from);
				$date_to = $this->db->escape($date_to);
				$query = $this->db->query("SELECT count(*) as val FROM employee_time_in eti
						LEFT JOIN employee e on e.emp_id = eti.emp_id
						WHERE 
						eti.corrected = 'Yes' 
						AND eti.comp_id = '{$this->db->escape_str($company_id)}' AND eti.deleted='0' 
						AND eti.date >={$date_from} AND eti.date <={$date_to}
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
		 * TIMESINS list via dates
		 * expense list of expenses via dates
		 * @param int $company_id
		 * @param int $limit
		 * @param int $start
		 * @param dates $date_from
		 * @param dates $date_to
		 * @return object
		 */
		public function timeins_list_by_date($company_id,$limit,$start,$date_from,$date_to){
			if(is_numeric($company_id)){
				$start = intval($start);
				$limit = intval($limit);
				
				$where = array(
					'ee.comp_id'   => $company_id,
					'ee.status'   => 'Active',
					'ee.corrected' => 'Yes',
				);
				$this->db->where($where);
				$this->db->where('ee.date BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
				$this->db->select('CONCAT('.$this->edb->decrypt('e.first_name').'," ",'.
					$this->edb->decrypt('e.last_name').') as full_name',FALSE);
				$this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
				$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
				$query = $this->edb->get('employee_time_in AS ee',$limit,$start);
				
				$result = $query->result();
				$query->free_result();
				return $result;
			}else{
				return false;
			}
		}
		
		/**
		 * COUNT expenses application count names
		 * counts application snames
		 * @param int $company_id
		 * @param string $employee_name
		 * @return int
		 */
		public function timeins_application_count_name($company_id,$employee_name){
			if(is_numeric($company_id)){
				$employee_name = $this->db->escape_like_str($employee_name);
				$query = $this->db->query("SELECT count(*) as val FROM employee_time_in ex
						LEFT JOIN employee e on e.emp_id = ex.emp_id
						WHERE ex.comp_id = '{$this->db->escape_str($company_id)}' AND ex.deleted='0' 
						AND  ex.corrected = 'Yes' 
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
		 * Expense list by name
		 * Checks expenses application sort by name
		 * @param int $company_id
		 * @param int $limit
		 * @param int $start
		 * @param string $employee_name
		 * @return object
		 */
		public function timeins_list_by_name($company_id,$limit,$start,$employee_name){
			if(is_numeric($company_id)){
				$start = intval($start);
				$limit = intval($limit);
				$employee_name = $this->db->escape_like_str($employee_name);
				
				$where = array(
					'ee.comp_id'   => $company_id,
					'ee.status'   => 'Active',
					'ee.corrected' => 'Yes',
				);
				$this->db->where($where);
				$this->db->select('CONCAT('.$this->edb->decrypt('e.first_name').'," ",'.
					$this->edb->decrypt('e.last_name').') as full_name',FALSE);
				$this->edb->like_concat('e.first_name','e.last_name',$employee_name);
				$this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
				$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
				$query = $this->edb->get('employee_time_in AS ee',$limit,$start);
				
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
		public function timeins_application_count($company_id){
			if(is_numeric($company_id)){
				$query = $this->db->query("SELECT count(*) as val FROM employee_time_in WHERE  corrected = 'Yes'   
						AND comp_id = '{$this->db->escape_str($company_id)}' AND deleted='0'
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
		 * Get Time Ins Information
		 * @param unknown_type $timein_id
		 * @param unknown_type $comp_id
		 */
		public function timeins_info($timein_id,$comp_id){
			$s = array(
				'et.change_log_date_filed',
				'et.change_log_time_in',
				'et.change_log_lunch_out',
				'et.change_log_lunch_in',
				'et.change_log_break1_out',
				'et.change_log_break1_in',
				'et.change_log_break2_out',
				'et.change_log_break2_in',
				'et.change_log_time_out',
				'et.change_log_late_min',
				'et.change_log_overbreak_min',
				'et.change_log_absent_min',
				'et.change_log_tardiness_min',
				'a.email',
				'et.emp_id',
				'et.date',
			    'et.work_schedule_id',
			    'et.missing_lunch',
			);

			$w = array(
				'et.employee_time_in_id'=>$timein_id,
				'et.comp_id'=>$comp_id
			);
			$this->edb->where($w);
			$this->edb->join("employee AS e","e.emp_id = et.emp_id","left");
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$q = $this->edb->get("employee_time_in AS et");
			$result = $q->row();
			
			return ($q->num_rows() > 0) ? $result : FALSE ;
		}
		
		/**
		 * Get Time Ins Information
		 * @param unknown_type $timein_id
		 * @param unknown_type $comp_id
		 */
		public function timeins_info_split($timein_id,$comp_id){
			$s = array(
					'et.change_log_date_filed',
					'et.change_log_time_in',
					'et.change_log_lunch_out',
					'et.change_log_lunch_in',
					'et.change_log_time_out',
					'a.email'
			);
			$w = array(
					'et.schedule_blocks_time_in_id'=>$timein_id,
					'et.comp_id'=>$comp_id
			);
			$this->edb->where($w);
			$this->edb->join("employee AS e","e.emp_id = et.emp_id","left");
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$q = $this->edb->get("schedule_blocks_time_in AS et");
			$result = $q->row();
				
			return ($q->num_rows() > 0) ? $result : FALSE ;
		}
		
		/**
		* to get default leave workflow
		* @param unknown $company_id
		*/
		public function get_default_approval_group($company_id,$type){
			$where 	= array(
					"ag.emp_id" 		=> "-99{$company_id}",
					"ag.company_id" 	=> $company_id,
					"ap.name" 			=> $type
					);
				$this->db->where($where);
				$this->db->join("approval_groups_via_groups AS agvg", "agvg.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","INNER");
				$this->db->join("approval_process AS ap", "ap.approval_process_id = ag.approval_process_id","LEFT");
				$query = $this->db->get("approval_groups AS ag");
				$row = $query->row();

			return ($row) ? $query->row() : false;
		}

		/**
		 * TIMESINS list via dates
		 * expense list of expenses via dates
		 * @param int $company_id
		 * @param int $limit
		 * @param int $start
		 * @param dates $date_from
		 * @param dates $date_to
		 * @return object
		 */
		
		public function advance_search($company_id,$limit,$start,$date_from = "",$date_to="",$employee_name =  "",$emp_id = "",$count = false){
			if(is_numeric($company_id)){
				$search = "";
				$filter = "";//not_in_search_timein_gold($emp_id, $company_id, $search);
				$start 	= intval($start);
				$limit 	= intval($limit);
				$konsum_key = konsum_key();

				$select = array(
						'e.emp_id',
						'ee.comp_id',
						'ee.date',
						'ee.time_in_status',
						'ati.flag_add_logs',
						//'epi.add_logs_approval_grp',
						//'epi.location_base_login_approval_grp',
						//'epi.attendance_adjustment_approval_grp',
						'ati.level',
						'ee.employee_time_in_id',
						'ee.work_schedule_id',
						'a.account_id',
						'ee.time_in',
						'ee.lunch_out',
						'ee.lunch_in',

						'ee.break1_out',
						'ee.break1_in',
						'ee.break2_out',
						'ee.break2_in',

						'ee.time_out',
						'ee.total_hours',
						'ee.total_hours_required',
						'ee.tardiness_min',
						'ee.undertime_min',
						'ee.change_log_time_in',
						'ee.change_log_lunch_out',
						'ee.change_log_lunch_in',

						'ee.change_log_break1_out',
						'ee.change_log_break1_in',
						'ee.change_log_break2_out',
						'ee.change_log_break2_in',

						'ee.change_log_time_out',
						'ee.change_log_total_hours',
						'ee.change_log_total_hours_required',
						'ee.change_log_tardiness_min',
						'ee.change_log_undertime_min',
						'ee.notes',
						'ee.source',
						'ee.change_log_date_filed',
						'ee.reason',
						//'a.profile_image',
				);
				$select1 = array(
						'e.first_name',
						'e.last_name',
						'a.payroll_cloud_id',
				);
				$this->db->select($select);
				$this->edb->select($select1);
				
				$where = array(
					'ee.comp_id'   => $company_id,
					'ee.status'   => 'Active',
					'ati.status'   => 'Active',
					'ee.corrected' => 'Yes',
				);
				
				$where2 = array(
						"ati.level !=" => ""
				);
				
				if($employee_name !="" && $employee_name !="all"){
					$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$employee_name."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$employee_name."%')", NULL, FALSE);
				}
				if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
					$this->db->where('ee.date BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
				}
				$this->db->order_by('ee.date','DESC');
				
				
				$this->db->where($where);
				$this->db->where($where2);
				
				$this->db->where(" (ee.time_in_status ='pending' OR ee.split_status='pending') ",NULL,FALSE);
				if($filter != FALSE){
					$this->db->where("ee.employee_time_in_id NOT IN ({$filter})");
				}

				$this->edb->join("employee AS e","e.emp_id = ee.emp_id","INNER");
				$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
				//$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
				//$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
				//$this->edb->join("position AS p","p.dept_id = d.dept_id","LEFT");
				$this->edb->join("approval_time_in AS ati","ati.time_in_id = ee.employee_time_in_id","LEFT");
				$this->db->group_by("ee.employee_time_in_id");

				if(!$count){
					$query 	= $this->edb->get("employee_time_in AS ee",$limit,$start);
					$result = $query->result();
				}else{
					$query = $this->edb->get("employee_time_in AS ee");
					$result = $query->num_rows();
				}
				
				$query->free_result();	
				
				return $result;
			}else{
				return false;
			}
		
		}

		public function emp_payroll_info($company_id,$emp_ids=""){
		
			$row_array 	= array();

			if($emp_ids){
				$s 			= array(
							'epi.emp_id',
							'epi.position',
							'epi.department_id',
							'epi.add_logs_approval_grp',
							'epi.location_base_login_approval_grp',
							'epi.attendance_adjustment_approval_grp',
							);
				$this->db->select($s);
				
				$w 			= array(
							'epi.company_id'=> $company_id
							);
				$this->db->where($w);
				$this->db->where_in("epi.emp_id",$emp_ids);
				$q_pg = $this->db->get('employee_payroll_information AS epi');
				$r_pg = $q_pg->result();
				
				if($r_pg){
					foreach ($r_pg as $r1){
						$wd 	= array(
								"position"								=> $r1->position,
								"department_id"							=> $r1->department_id,
								"add_logs_approval_grp"					=> $r1->add_logs_approval_grp,
								"location_base_login_approval_grp"		=> $r1->location_base_login_approval_grp,
								"attendance_adjustment_approval_grp"	=> $r1->attendance_adjustment_approval_grp,
								"custom_search"							=> "emp_id-{$r1->emp_id}",
								);

						array_push($row_array,$wd);
					}
				}
			}
			return $row_array;
		}
		
		public function count_pending($company_id){
			if(is_numeric($company_id)){
		
				$where = array(
						'ee.comp_id'   => $company_id,
						'ee.status'   => 'Active',
						'ee.time_in_status' => 'pending',
						'ee.corrected' => 'Yes',
				);
	
			
				$this->db->where($where);
	
				$query = $this->edb->get('employee_time_in AS ee');
				$result = $query->num_rows();
				$query->free_result();
				return $result;
			}else{
				return false;
			}
		}
		
		function check_if_is_level($level, $alert_id){
			$CI =& get_instance();
			if(is_numeric($alert_id)){
				$where = array(
						'level'=>$level,
						'hours_alerts_notification_id'=>$alert_id
				);
				$CI->edb->where($where);
				$q = $CI->edb->get('hours_notification_leveling');
				$r = $q->row();
				return ($r) ? TRUE : FALSE;
					
			}							
		}
		
		/**
		 *
		 * Check if assigned timein
		 * @param unknown_type $overtime_appr_grp
		 * @param unknown_type $level
		 */
		public function check_assigned_timein($timein_appr_grp, $level,$comp_id = 0){
		
			$emp_id = ($this->session->userdata("emp_id")) ? $this->session->userdata("emp_id") : "-99".$comp_id;
						
			$where 	= array(
					"emp_id" => $emp_id,
					"level" => $level,
					"approval_groups_via_groups_id" => $timein_appr_grp
					);

			$this->db->where($where);
			$query 	= $this->db->get("approval_groups");
			$row 	= $query->row();

			return ($row) ? true : false;
		}
		
		
		/**
		 *
		 * Check if assigned timein
		 * @param unknown_type $overtime_appr_grp
		 * @param unknown_type $level
		 */
		public function check_assigned_timein_cronjob($timein_appr_grp, $level,$emp_id){
				
			$where = array(
					"emp_id" => $emp_id,
					"level" => $level,
					"approval_groups_via_groups_id" => $timein_appr_grp
			);
			$this->db->where($where);
			$query = $this->db->get("approval_groups");
			$row = $query->row();
			//	p($row);
			return ($row) ? true : false;
		}
		
		/**
		 *
		 * Show who's assigned approver on overtime list
		 * @param unknown_type $overtime_appr_grp
		 * @param unknown_type $level
		 */
		public function track_next_approver($timein_appr_grp, $level){
			
			$this->db->distinct();
			$this->db->group_by('ag.emp_id');
			$where = array(
					"ag.level" => $level,
					"ag.approval_groups_via_groups_id" => $timein_appr_grp
			);
			$this->db->where($where);
			$this->edb->join("employee AS e", "e.emp_id = ag.emp_id","LEFT");
			$query = $this->edb->get("approval_groups AS ag");
			$row = $query->result();
			$next_approver = "";
			//if($row){
			//	$next_approver = ucwords($row->first_name." ".$row->last_name);
			//}
		
			//return $next_approver;
			return ($row) ? $row : false;
		}
		

		/**
		 *
		 * Check if approver is done approving a certain timein application
		 * @param unknown_type $overtime_appr_grp
		 * @param unknown_type $level
		 */
		public function is_done($timein_appr_grp, $level){
			$where = array(
					"emp_id" => $this->session->userdata("emp_id"),
					"level <" => $level,
					"approval_groups_via_groups_id" => $timein_appr_grp
			);
			$this->db->where($where);
			$query = $this->db->get("approval_groups");
			$row = $query->row();
		
			return ($row) ? true : false;
		}
		
		/**
		 * Expenses application counts dates
		 * check expenses application and count to date
		 * @param int $company_id
		 * @param int $date_from
		 * @param dates $date_to
		 * @return integer
		 */
		public function count_advance_search($company_id,$date_from = "",$date_to="",$employee_name =  "",$emp_id = ""){
			if(is_numeric($company_id)){
				$konsum_key = konsum_key();
				$search = "";
				$filter = not_in_search_timein_gold($emp_id, $company_id, $search);

				$where = array(
					'ee.comp_id'   => $company_id,
					'ee.time_in_status ' => 'pending',
					'ee.status'   => 'Active',
					'ee.corrected' => 'Yes',
				);
				
				$where2 = array(
						"ati.level !=" => ""
				);
			if($employee_name !="" && $employee_name !="all"){
					$this->db->where("(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$employee_name."%')", NULL, FALSE);
				}
				if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
					$this->db->where('ee.date BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
				}		
				$this->db->where($where);
				$this->db->where($where2);
				if($filter != FALSE){
					$this->db->where("ee.employee_time_in_id NOT IN ({$filter})");
				}
				$this->edb->join("employee AS e","e.emp_id = ee.emp_id","LEFT");
				//$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
				//$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
				//$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
				//$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
				//$this->edb->join("approval_groups_via_groups AS agg","epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id","LEFT");
				//$this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
				//$this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
				$this->edb->join("approval_time_in AS ati","ati.time_in_id = ee.employee_time_in_id","LEFT");
				$this->db->group_by("ee.employee_time_in_id");
				$query = $this->edb->get('employee_time_in AS ee');
				
				$num_row = $query->num_rows();

				$row = $query->row();
				$query->free_result();				 
				return $num_row ? $num_row  : 0;
			}else{
				return false;
			}
		}
		
		/**
		 * Expenses application counts dates
		 * check expenses application and count to date
		 * @param int $company_id
		 * @param int $date_from
		 * @param dates $date_to
		 * @return integer
		 * ..aldrin cantero 
		 */
		public function count_advance_search2($company_id,$date_from = "",$date_to="",$employee_name =  ""){
			if(is_numeric($company_id)){
				
				$emp_id = $this->session->userdata("emp_id");
				$search = "";
				$filter = $this->todo_timein->not_in_search_timein($emp_id, $company_id, $search);
				
				$where = array(
					'ee.comp_id'   => $company_id,
					'ee.time_in_status ' => 'pending',
					'ee.status'   => 'Active',
					'ee.corrected' => 'Yes',
				);
				

				if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
					$this->db->where('ee.date BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
				}		

				$this->db->where($where);
				if($filter != FALSE){
					$this->db->where("ee.employee_time_in_id NOT IN ({$filter})");
				}
				$this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
				$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
				$query = $this->edb->get('employee_time_in AS ee');
				$result = $query->num_rows();
				$query->free_result();
				return $result;
			}else{
				return false;
			}
		}
		
		
		/**
		 * TIMESINS list via dates
		 * expense list of expenses via dates
		 * @param int $company_id
		 * @param int $limit
		 * @param int $start
		 * @param dates $date_from
		 * @param dates $date_to
		 * @return object
		 */
		public function sortby_employee($company_id,$limit,$start,$date_from = "",$date_to="",$type =  "",$orderby = ""){
			if(is_numeric($company_id)){
				$emp_id = $this->session->userdata("emp_id");
				$search = "";
				$filter = not_in_search_timein_gold($emp_id, $company_id, $search);
				
				$start = intval($start);
				$limit = intval($limit);
		
				$where = array(
						'ee.comp_id'   => $company_id,
						'ee.time_in_status ' => 'pending',
						'ee.status'   => 'Active',
						'ee.corrected' => 'Yes',
				);
				
				$where2 = array(
						"ati.level !=" => ""
				);
				if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
					$this->db->where('ee.date BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
				}
				
				
				if($type)
					$this->db->order_by($type,$orderby);
				
				$this->db->where($where);
				$this->db->where($where2);
				if($filter != FALSE){
					$this->db->where("ee.employee_time_in_id NOT IN ({$filter})");
				}
				$this->db->select('CONCAT('.$this->edb->decrypt('e.first_name').'," ",'.
						$this->edb->decrypt('e.last_name').') as full_name',FALSE);
				$this->edb->join("employee AS e","e.emp_id = ee.emp_id","LEFT");
				$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
				$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
				$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
				//$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
				//$this->edb->join("approval_groups_via_groups AS agg","epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id","LEFT");
				//$this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
				//$this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
				$this->edb->join("approval_time_in AS ati","ati.time_in_id = ee.employee_time_in_id","LEFT");
				$this->db->group_by("ee.employee_time_in_id");
				$query = $this->edb->get('employee_time_in AS ee',$limit,$start);
				
				//$query = $this->edb->get('employee_time_in AS ee');
				$result = $query->result();
				$query->free_result();
				return $result;
			}else{
				return false;
			}
		}
		
		public function  search_employee($company_id,$limit,$start,$date_from = "",$date_to="",$employee_name =  "",$emp_id = ""){

			
			if(is_numeric($company_id)){
				$konsum_key = konsum_key();
				
				$filter = not_in_search_timein_gold($emp_id, $company_id, $employee_name );
			
				$start = intval($start);
				$limit = intval($limit);
				$sort_array = array(
						"last_name",
						"pg_name",
						"date",
						"change_log_total_hours",
						"time_in_status"
				);
				//$level = $this->timein_approval_level($this->session->userdata('emp_id'));
				
				$where = array(
						'ee.comp_id'   => $company_id,
						'ee.status'   => 'Active',
						'ee.corrected' => 'Yes',
				);
				
				$where2 = array(
						"ati.level !=" => ""
				);
			
				if($employee_name !="" && $employee_name !="all"){
						$this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$employee_name."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$employee_name."%')", NULL, FALSE);
				}
				if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
					$this->db->where('ee.date BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
				}		
		
					$this->db->order_by('ee.date','DESC');
					$this->db->where($where);
					$this->db->where($where2);
					$this->db->where(" (ee.time_in_status ='pending' OR ee.split_status='pending') ",NULL,FALSE);
					if($filter != FALSE){
						$this->db->where("ee.employee_time_in_id NOT IN ({$filter})");
					}

					$this->edb->join("employee AS e","e.emp_id = ee.emp_id","LEFT");
					$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
					$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
					//$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
					$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
					//$this->edb->join("approval_groups_via_groups AS agg","epi.attendance_adjustment_approval_grp = agg.approval_groups_via_groups_id","LEFT");
					//$this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
					//$this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
					$this->edb->join("approval_time_in AS ati","ati.time_in_id = ee.employee_time_in_id","LEFT");
					$this->db->group_by("ee.employee_time_in_id");		
					$query = $this->edb->get('employee_time_in AS ee',$limit,$start);
					//$query = $this->edb->get('employee_time_in AS ee');
					$result = $query->result();
					$query->free_result();	
					return $result;
				
			}else{
				return false;
			}
		}
		
		
		public function query_split($employee_time_in_id){
			$w = array(
					"eti.employee_time_in_id"=>$employee_time_in_id,
					"eti.status" => "Active",
					'eti.time_in_status' => 'pending'
			);
			$this->edb->where($w);
			$this->db->distinct("eti.schedule_blocks_time_in");
			//$this->edb->join("employee AS e","e.emp_id = eti.emp_id","INNER");
			//$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			//$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","INNER");
			//$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("approval_time_in AS ati","ati.split_time_in_id= eti.schedule_blocks_time_in_id","inner");
			$this->db->order_by("eti.time_in","ASC");
			$split_q = $this->edb->get("schedule_blocks_time_in AS eti");
			
			$query_split = $split_q->result();
			
			return ($query_split) ? $query_split : false;
		}
		
		public function get_split($id){
			
			$w = array(
					"eti.schedule_blocks_time_in_id"=>$id,
					"eti.status" => "Active",
					'eti.time_in_status' => 'pending'
			);
			$this->edb->where($w);
			$split_q = $this->edb->get("schedule_blocks_time_in AS eti");
			$query_split = $split_q->row();
				
			return ($query_split) ? $query_split->employee_time_in_id : false;
		}
		
		public function check_split_list_to_approve($id){
			
			$w = array(
					"eti.employee_time_in_id"=>$id,
					"eti.status" => "Active",
					'eti.time_in_status' => 'pending'
			);
			$this->edb->where($w);
			$split_q = $this->edb->get("schedule_blocks_time_in AS eti");
			$query_split = $split_q->num_rows();
			
			return ($query_split > 0) ? $query_split : false;
		}
		
		/**
		 * 
		 * Search user employee and the ID
		 */
		public function search_employee2($company_id,$limit,$start,$date_from = "",$date_to="",$employee_name =  ""){
			if(is_numeric($company_id)){
					$emp_id = $this->session->userdata("emp_id");
					$search = "";
					$filter = $this->not_in_search_timein($emp_id, $company_id, $employee_name );
					
					$start = intval($start);
					$limit = intval($limit);
					$konsum_key = konsum_key();
						
					$where = array(
						'ee.comp_id'   => $company_id,
						'ee.time_in_status ' => 'pending',
						'ee.status'   => 'Active',
						'ee.corrected' => 'Yes',
					);
					//$this->db->or_where("AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')",$employee_name);
					if($employee_name !="" && $employee_name !="all"){
						//	$this->edb->like_concat('e.last_name','e.first_name',$employee_name);
						//	$this->db->or_like("AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')",$employee_name);
							$this->db->where("CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$employee_name."%' ", NULL, FALSE); // encrypt
							$this->db->or_where("AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')",$employee_name);
						//	$this->db->or_like('ee.time_in_status',$employee_name);
					}
					if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
						$this->db->where('ee.date BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
					}		
					$this->db->order_by('ee.date','DESC');
					$this->db->where($where);
					if($filter != FALSE){
						$this->db->where("ee.employee_time_in_id NOT IN ({$filter})");
					}
					$this->db->select('CONCAT('.$this->edb->decrypt('e.first_name').'," ",'.
					$this->edb->decrypt('e.last_name').') as full_name',FALSE);
					$this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
					$this->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
					$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
					$this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
					$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
					$this->edb->join("approval_time_in AS ati","ati.time_in_id = ee.employee_time_in_id","LEFT");
					$this->db->group_by('ee.time_in,ee.date');
					$query = $this->edb->get('employee_time_in AS ee',$limit,$start);
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
		public function not_in_search_timein($emp_id, $company_id,$search=""){
			if(is_numeric($company_id)){
				$konsum_key = konsum_key();
				$level = $this->todo_timein->timein_approval_level($this->session->userdata('emp_id'));
				$where = array(
						'ee.comp_id'   => $company_id,
						'ee.status'   => 'Active',
						'ee.corrected' => 'Yes',
						'ee.time_in_status' => 'pending'
		
				);
			
					if($search !="" && $search !="all"){
						//	$this->edb->like_concat('e.last_name','e.first_name',$employee_name);
						//	$this->db->or_like("AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')",$employee_name);
							$this->db->where("CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%' ", NULL, FALSE); // encrypt
							$this->db->or_where("AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')",$search);
						//	$this->db->or_like('ee.time_in_status',$employee_name);
					}
		
				$this->edb->where($where);
				
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
							
						$check = $this->todo_timein->check_assigned_hours($appr_grp, $level);
						//	echo $emp->employee_time_in_id.' - '. $emp->approval_time_in_id.' - '.$check.'</br>';
						if($hours_notification->option == "choose level notification"){
							$is_assigned = check_if_is_level_hours($level, $hours_notification->hours_alerts_notification_id);
						}
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
			return ($q->num_rows() > 0) ? ucwords($row->first_name): FALSE ;
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
		
		/** email sender to hr functionalities start here **/
		
		/**
		 * HR SETUP GET ALL LIST OF APPROVERS
		 * Enter description here ...
		 * @param int $company_id
		 * @param string $leave_type
		 */
		public function list_of_approvers($company_id,$leave_type='Time In Approval Group'){
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
		public function filder_approvers_all_email($company_id,$leave_type='Time In Approval Group'){
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
		
		public function check_valid_email($address){
			return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address)) ? FALSE : TRUE;
		}
		
		/** end email sender to hr functionalities end here **/
		
		/**
		 * 
		 * Get Department ID for getting the info of department
		 * @param int $id 
		 */
		public function get_department($id){
			$this->edb->where('dept_id',$id);
			$arrx = array(
					'department_name' 
			);
			$this->edb->select($arrx);
			$query = $this->edb->get('department');
			$row = $query->row();
								
			return $row;
		}	
		
		
		public function get_employee_time_in($val){
			if(is_numeric($val)){
			
				$where = array(
						'ee.employee_time_in_id'   => $val
				);
			
				$this->db->where($where);
				$this->edb->join('company AS c','c.company_id = ee.comp_id','left');
				$this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
				$this->edb->join("approval_time_in AS ti","ti.time_in_id = ee.employee_time_in_id","LEFT");
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
		
		
		public function get_employee_time_in_split($val){
			if(is_numeric($val)){
					
				$where = array(
						'ee.schedule_blocks_time_in_id'   => $val
				);
					
				$this->db->where($where);
				$this->edb->join('company AS c','c.company_id = ee.comp_id','left');
				$this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
				$this->edb->join("approval_time_in AS ti","ti.time_in_id = ee.employee_time_in_id","LEFT");
				$this->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
				$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
				$query = $this->edb->get('schedule_blocks_time_in AS ee');
				//$query = $this->edb->get('employee_time_in AS ee');
				$result = $query->row();
				$query->free_result();
				return $result;
			}else{
				return false;
			}
		}
		
		/**
		 * Get Leave Approver Information
		 * @param unknown_type $emp_id
		 * @param unknown_type $company_id
		 */
		public function get_approver_name_hours($emp_id,$company_id,$flag=""){
			$this->db->where("emp_id",$emp_id);
			$sql = $this->db->get("employee_payroll_information");
			$row = $sql->row();
			$leave_approval_grp ="";
			if($row){
				if($flag == 0)
					$leave_approval_grp = $row->attendance_adjustment_approval_grp;
				elseif($flag==1)
					$leave_approval_grp = $row->add_logs_approval_grp;
				elseif ($flag==2)
					$leave_approval_grp = $row->location_base_login_approval_grp;
				
				$this->db->distinct();
				$this->db->group_by('a.email');
				$w = array(
						"ag.company_id"=>$company_id,
						"ag.approval_groups_via_groups_id"=>$leave_approval_grp
				);
				$this->db->where($w);
				
				$this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
				$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
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
		public function get_approver_name_hours_desc($emp_id,$company_id,$flag=""){
			$this->db->where("emp_id",$emp_id);
			$sql = $this->db->get("employee_payroll_information");
			$row = $sql->row();
			if($row){
				if($flag == 0)
					$leave_approval_grp = $row->attendance_adjustment_approval_grp;
				elseif($flag==1)
				$leave_approval_grp = $row->add_logs_approval_grp;
				elseif ($flag==2)
				$leave_approval_grp = $row->location_base_login_approval_grp;
		
				$w = array(
						"ag.company_id"=>$company_id,
						"ag.approval_groups_via_groups_id"=>$leave_approval_grp
				);
				$this->db->where($w);
				$this->edb->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
				$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
				$this->db->order_by("ag.level","DESC");
				$q = $this->edb->get("approval_groups AS ag");
				$r = $q->result();
				return ($r) ? $r : FALSE ;
			}else{
				return FALSE;
			}
		}
		
		public function get_timein_last_hours($emp_id, $company_id,$flag = ""){
			$this->db->where("emp_id",$emp_id);
			$sql = $this->db->get("employee_payroll_information");
			$row = $sql->row();
			if($row){
				if($flag == 0)
					$leave_approval_grp = $row->attendance_adjustment_approval_grp;
				elseif($flag==1)
					$leave_approval_grp = $row->add_logs_approval_grp;
				elseif ($flag==2)
					$leave_approval_grp = $row->location_base_login_approval_grp;
				
				
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
		
		public function get_last_level_checked_hours($company_id, $hours_id){
			$where = array(
					"hours_alerts_notification_id" => $hours_id,
					"company_id" => $company_id
			);
			$this->db->where($where);
			$this->db->order_by("level","DESC");
			$query = $this->db->get("hours_notification_leveling");
			$row = $query->row();
		
			return ($row) ? $row->level : false;
		}
		
		public function generate_leave_level_token($new_level, $leave_id){
			$str2 = 'ABCDEFG1234567890';
			$shuffled2 = str_shuffle($str2);
		
			$update = array(
					"level" => $new_level,
					"token_level" => $shuffled2
			);
			$where = array(
					"time_in_id" => $leave_id
			);
		
			$this->db->where($where);
			$update_approval_leave_token = $this->db->update("approval_time_in",$update);
		
			return ($update_approval_leave_token) ? $shuffled2 : false;
		}
		
		/**
		 * Get Token from Approval Leave
		 * @param unknown_type $leave_ids
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function get_token($leave_ids,$comp_id,$emp_id){
			$w = array(
					"approval_time_in_id"=>$leave_ids,
					"comp_id"=>$comp_id,
					"emp_id"=>$emp_id,
					"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("approval_time_in");
			$row = $q->row();
			return ($q->num_rows() > 0) ? $row->token : "" ;
		}
		
		/**
		 * get the row of each work schedule
		 * @param unknown $work_schedule_id
		 */
		public function work_schedule_info($work_schedule_id){
			$w = array(
					"work_schedule_id" => $work_schedule_id,
					"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("work_schedule");
			$row = $q->row();
			return ($q->num_rows() > 0) ? $row : false ;
		}
		
		
		public function second_tod ($company_id,$limit,$start,$date_from = "",$date_to="",$employee_name =  ""){
			$emp_id = $this->session->userdata("emp_id");
			$search = "";
			//$filter = $this->todo_timein->not_in_search_timein($emp_id, $company_id, $search);
			$filter = $this->not_in_search_timein2($emp_id, $company_id, $search);
			$start = intval($start);
			$limit = intval($limit);
			
			
			$where = array(
					'ee.comp_id'   => $company_id,
					'ee.time_in_status ' => 'pending',
					'ee.status'   => 'Active',
					'ee.corrected' => 'Yes',
					//'ati.level' => 1
			);
			
			/*$where2 = array(
			 "ati.level !=" => ""
			);*/
			
			if($employee_name !="" && $employee_name !="all"){
				$this->edb->like_concat('e.last_name','e.first_name',$employee_name);
			}
			if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
				$this->db->where('ee.date BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
			}
			$this->db->order_by('ee.date','DESC');
			
			
			$this->db->where($where);
			//	$this->db->where($where2);
			if($filter != FALSE){
				$this->db->where("ee.employee_time_in_id NOT IN ({$filter})");
			}
			$this->db->select('CONCAT('.$this->edb->decrypt('e.first_name').'," ",'.
					$this->edb->decrypt('e.last_name').') as full_name',FALSE);
			$this->edb->join('company AS c','c.company_id = ee.comp_id','left');
			$this->edb->join('employee AS e','e.emp_id = ee.emp_id','left');
			$this->edb->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$this->edb->join("approval_groups_via_groups AS agg","epi.overtime_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("approval_groups AS ag","ag.approval_process_id = agg.approval_process_id","LEFT");
			$this->edb->join("approval_time_in AS ati","ati.time_in_id = ee.employee_time_in_id","LEFT");
			$this->db->group_by('ee.time_in,ee.date');
			$query = $this->edb->get('employee_time_in AS ee',$limit,$start);
			//$query = $this->edb->get('employee_time_in AS ee');
			
			$result = $query->result();
			$query->free_result();
			
			return $result;
		}
		
		/**
		 * Gets the count of the time in lists that is waiting for approval for the pagination (right now, this is no use because we have made a custom counter)
		 * @param int $emp_id
		 * @param int $company_id
		 * @param string $search
		 * @return number|boolean
		 */
		public function not_in_search_timein2($emp_id, $company_id,$search=""){
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
						if($hours_notification->option == "choose level notification"){
							$is_assigned = check_if_is_level_hours($level, $hours_notification->hours_alerts_notification_id);
						}
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
		 * This will use to search the list of todo to appove by this employee
		 * Return the time in id and employee information
		 */
		public function search_timein_list_to_approve($emp_id,$company_id){

				
			$search = "";
			$filter = not_in_search_timein_gold($emp_id, $company_id, $search,false,true);

			$where = array(
					'ee.comp_id'   => $company_id,
					'ee.time_in_status ' => 'pending',
					'ee.status'   => 'Active',
					'ee.corrected' => 'Yes',
					//'ati.level' => 1
			);
	
			$where2 = array(
					"ati.level !=" => ""
			);

			$this->db->order_by('ee.date','DESC');
	
			$this->db->where($where);
			$this->db->where($where2);
			if($filter != FALSE){
				$this->db->where("ee.employee_time_in_id NOT IN ({$filter})");
			}
			
			$this->edb->join("employee AS e","e.emp_id = ee.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			
			$this->edb->join("approval_time_in AS ati","ati.time_in_id = ee.employee_time_in_id","LEFT");
			$this->db->group_by("ee.employee_time_in_id");
			$query = $this->edb->get("employee_time_in AS ee");
	
			$result = $query->result();
			$query->free_result();
	
			return $result;
				
		}
		
		
		/**
		 *
		 * Check if assigned timein
		 * @param unknown_type $overtime_appr_grp
		 * @param unknown_type $level
		 */
		public function if_duedate_employee($timein_appr_grp, $level,$empid){
				
			$where = array(
					"ag.emp_id" => $empid,
					"ag.level" => $level,
					"ag.approval_groups_via_groups_id" => $timein_appr_grp
			);
			$this->db->where($where);
			$this->edb->join("approval_groups_via_groups AS  agvg","agvg.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
			$query = $this->edb->get("approval_groups AS ag");
			$row = $query->row();
			
			return ($row) ? $row : false;
		}
		
		public function get_numbers_of_level($timein_appr_grp, $level,$process_id){
		
			$where = array(
					"approval_process_id" => $process_id,
					"level" => $level,
					"approval_groups_via_groups_id" => $timein_appr_grp
			);
			$this->db->where($where);
			$query = $this->db->get("approval_groups");
			$row = $query->num_rows();
				
			return ($row) ? $row : 0;
		}
		
		public function list_of_employee_approve($timein_appr_grp){
			
			$where = array(
					"ag.approval_groups_via_groups_id" => $timein_appr_grp
			);
			$this->db->where($where);
			$this->edb->join("approval_groups_via_groups AS  agvg","agvg.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
			$query = $this->edb->get("approval_groups AS ag");
			$row = $query->result();
			
			return ($row) ? $row : false;
		}
		
		/**
		 * use for cronjob approval
		 * show the list of company and the psa
		 * @return Ambigous <boolean, unknown>
		 */
		public function company_and_owner(){
			
		
			$query = $this->db->get("assigned_company");
			$result = $query->result();
				
			return ($result) ? $result : false;
		}
		
		/**
		 * use for cronjob approval
		 * @param int $company_id
		 * @return Ambigous <boolean, unknown>
		 */
		public function show_list_approval_groups($company_id){
			
			
			$arrx = array(
					'emp_id',
					'employee_time_in_id',
					'comp_id',
					'change_log_date_filed'
			);
			$this->db->select($arrx);

			$where = array(
					"comp_id" => $company_id,
					"time_in_status" => "pending"
			);
		
			$this->db->where($where);
			$query = $this->db->get('employee_time_in');
			$result = $query->result();
				//p($result);
			return ($result) ? $result : false;
		}
		
		
		public function is_send($timein_id){
				
			
			$where = array(
					"timein_id" => $timein_id
			);
			$this->db->where($where);
			$query = $this->db->get('cronjob_log');
			$result = $query->row();
			
			return ($result) ? true :false;
			
		}
		
		public function is_approve($timein_id){
		
				
			$where = array(
					"timein_id" => $timein_id
			);
			$this->db->where($where);
			$query = $this->db->get('cronjob_log');
			$result = $query->row();
				
			return ($result) ? false : true;
				
		}
		
		public function change_date_cronjob($timein_id){
			
			$where = array(
					"timein_id" => $timein_id,
			);
			$this->db->where($where);
			$query = $this->db->get('cronjob_log');
			$result = $query->row();
			
			return ($result) ? $result : false;
		}
		
		public function add_cronjob_lod($timein_id,$datetime){
			
			$data = array(
					'timein_id' => $timein_id,
					'notify_datetime' => $datetime
			);
			
			$this->db->insert('cronjob_log', $data);
		}
		
		public function update_cronjob_lod($timein_id,$datetime){
				
			$data = array(
					'notify_datetime' => $datetime
			);
			
			$update = array(
					'timein_id' => $timein_id,
			);
			
			$this->db->where($update);
			$this->db->update('cronjob_log', $data);
		}
		
		public function add_cronjob_lod_approve($timein_id,$datetime,$level){
				
			$data = array(
					'timein_id' => $timein_id,
					'approve_datetime' => $datetime,
					'approve_level' => $level
			);
				
			$this->db->insert('cronjob_log', $data);
		}
		
		public function approve_cronjob_lod($timein_id,$due_date,$level){
				
			$data = array(
					'approve_level' => $level,
					'approve_datetime' => $due_date
			);
			
			$update = array(
					'timein_id' => $timein_id,
			);
			
			$this->db->where($update);
			$this->db->update('cronjob_log', $data);
		}
		
		public function get_via_groups($id){
				
			$where = array(
					"approval_groups_via_groups_id" => $id
			);
			$this->db->where($where);
			$query = $this->db->get('approval_groups_via_groups');
			$result = $query->row();
				
			return ($result) ? $result->approval_levels : false;
		}
		
		public function get_level_approve($timein_id){
			

			$where = array(
					"time_in_id" => $timein_id
			);
			$this->db->where($where);
			$this->db->order_by("time_in_id", "desc");
			$query = $this->db->get('approval_time_in',1);
			$result = $query->row();
			
			return ($result) ? $result : false;
		}
		
		// added : fritz 
		/**
		 * Get Time Ins Information
		 * @param unknown_type $timein_id
		 * @param unknown_type $comp_id
		 */
		public function split_timeins_info($timein_id,$comp_id){
			$s = array(
					'sbti.schedule_blocks_time_in_id',
					'sbti.employee_time_in_id',
					'sbti.schedule_blocks_id',
					'sbti.change_log_date_filed',
					'sbti.change_log_time_in',
					'sbti.change_log_lunch_out',
					'sbti.change_log_lunch_in',
					'sbti.change_log_time_out',
					'a.email'
			);
			$w = array(
					'sbti.schedule_blocks_time_in_id'=>$timein_id,
					'sbti.comp_id'=>$comp_id
			);
			$this->db->where($w);
			$this->edb->join("employee AS e","e.emp_id = sbti.emp_id","left");
			$this->edb->join('accounts AS a','a.account_id = e.account_id','left');
			$q = $this->edb->get("schedule_blocks_time_in AS sbti");
			$result = $q->row();
			
			return ($q->num_rows() > 0) ? $result : FALSE ;
		}
		
		public function generate_leave_level_token_split($new_level, $leave_id){
			$str2 = 'ABCDEFG1234567890';
			$shuffled2 = str_shuffle($str2);
		
			$update = array(
					"level" => $new_level,
					"token_level" => $shuffled2
			);
			$where = array(
					"split_time_in_id" => $leave_id
			);
		
			$this->db->where($where);
			$update_approval_leave_token = $this->db->update("approval_time_in",$update);
		
			return ($update_approval_leave_token) ? $shuffled2 : false;
			
		}
		
		public function get_employee_time_inv2($val){
			if(is_numeric($val)){
				$select = array(
						'ee.emp_id',
						'ee.comp_id',
						'ee.work_schedule_id',
						'ti.level',
						'ee.date',
						'ee.source',
						'ti.flag_add_logs',
						'ti.approval_time_in_id',
						'epi.attendance_adjustment_approval_grp',
						'epi.add_logs_approval_grp',
						'epi.location_base_login_approval_grp',
						'ee.time_in_status',
				);
				$where = array(
						'ee.employee_time_in_id'    => $val,
						'ee.time_in_status'   		=> "pending"
				);
					
				$this->db->select($select);
				$this->db->where($where);
				$this->db->join('company AS c','c.company_id = ee.comp_id','left');
				$this->db->join('employee AS e','e.emp_id = ee.emp_id','left');
				$this->db->join("approval_time_in AS ti","ti.time_in_id = ee.employee_time_in_id","LEFT");
				$this->db->join('employee_payroll_information AS epi','epi.emp_id = ee.emp_id','left');
				$this->db->join('accounts AS a','a.account_id = e.account_id','left');
				$query = $this->db->get('employee_time_in AS ee');
				$result = $query->row();
				$query->free_result();
				return $result;
			}else{
				return false;
			}
		}
		
		function get_employee_details_by_empid($emp_id){
			$name = "";
			$where = array(
					"e.emp_id" => $emp_id
			);
			$select = array(
					'e.first_name',
					'e.last_name',
					'a.email',
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
			$select1 = array(
					'a.account_id',
			);
			$this->edb->select($select);
			$this->db->select($select1);
			$this->db->where($where);
			$this->db->join('accounts AS a','e.account_id = a.account_id','LEFT');
			$this->db->join('employee_payroll_information AS epi','e.emp_id = epi.emp_id','LEFT');
			$query = $this->edb->get("employee AS e");
			$emp_row = $query->row();
			return ($emp_row) ? $emp_row : FALSE;
		
		}
		
		public function get_approver_name_hoursv2($emp_id,$company_id,$flag="",$leave_approval_grp){

			$this->db->distinct();
			$this->db->group_by('a.email');
			$w = array(
				"ag.company_id"=>$company_id,
				"ag.approval_groups_via_groups_id"=>$leave_approval_grp
				);
			$s = array(
				"e.first_name",
				"e.last_name",
				"a.email",
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
			$s1 = array(
				"a.account_id",
				"ag.level",
				"e.emp_id",
			);
			$this->db->select($s1);
			$this->edb->select($s);
			$this->db->where($w);
			$this->db->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
			$this->db->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->db->order_by("ag.level","ASC");
			$q = $this->edb->get("approval_groups AS ag");
			$r = $q->result();
			return ($r) ? $r : FALSE ;
		}
		public function get_timein_last_hoursv2($emp_id, $company_id,$flag = "",$leave_approval_grp){
			
				$w = array(
						"ag.company_id"						=> $company_id,
						"ag.approval_groups_via_groups_id"	=> $leave_approval_grp
				);
				$s1 = array(
					"ag.level",
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
				$this->db->select($s1);
				$this->db->where($w);
				$this->db->join("employee AS e","e.emp_id = ag.emp_id","LEFT");
				$this->db->join("accounts AS a","a.account_id = e.account_id","LEFT");
				$this->db->order_by("ag.level","DESC");
				$q = $this->edb->get("approval_groups AS ag",1);
				$r = $q->row();
				return ($r) ? $r->level : FALSE ;
		}
		public function timeins_infov2($timein_id,$comp_id){
			$s1 = array(
					'e.first_name',
					'e.last_name',
					'a.email'
			);
			$w = array(
					'et.employee_time_in_id'=>$timein_id,
					'et.comp_id'=>$comp_id
			);
			$this->edb->select($s1);
			$this->db->where($w);
			$this->db->join("employee AS e","e.emp_id = et.emp_id","left");
			$this->db->join('accounts AS a','a.account_id = e.account_id','left');
			$q = $this->edb->get("employee_time_in AS et");
			$result = $q->row();
				
			return ($q->num_rows() > 0) ? $result : FALSE ;
		}

		public function check_split_list_to_approvev2($id){
			
			$w = array(
					"eti.employee_time_in_id"	=> $id,
					"eti.status" 				=> "Active",
					'eti.time_in_status' 		=> 'pending'
			);
			$this->edb->where($w);
			$split_q = $this->edb->get("schedule_blocks_time_in AS eti");
			$query_split = $split_q->result();
			
			return ($query_split) ? $query_split : false;
		}

		public function get_all_approved_split_sched($id){
			$status = array(
					'pending',
					'reject'
				);
			$w = array(
					"eti.employee_time_in_id"	=> $id,
					"eti.status" 				=> "Active",
			);
			$this->db->where_not_in('eti.time_in_status', $status);
			$this->edb->where($w);
			$split_q = $this->edb->get("schedule_blocks_time_in AS eti");
			$query_split = $split_q->result();
			
			return ($query_split) ? $query_split : false;
		}
		
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

		public function check_assigned_timeinv2($comp_id = 0){
			
			$row_array 	= array();

			$emp_id 	= ($this->session->userdata("emp_id")) ? $this->session->userdata("emp_id") : "-99".$comp_id;
						
			$where 		= array(
						"emp_id" => $emp_id,
						"company_id" => $comp_id,
						//"level" => $level,
						//"approval_groups_via_groups_id" => $timein_appr_grp
						);
			$this->db->where($where);
			$query 		= $this->db->get("approval_groups");
			$r 			= $query->result();

			if($r){
				foreach ($r as $r1){
					$wd 	= array(
							"approval_groups_via_groups_id"		=> $r1->approval_groups_via_groups_id,
							"level"								=> $r1->level,
							"emp_id"							=> $r1->emp_id,
							"custom_search"						=> "ag_{$r1->level}_{$r1->approval_groups_via_groups_id}",
							"custom_searchv2"					=> "aglvl_{$r1->approval_groups_via_groups_id}",
							);
					array_push($row_array,$wd);
				}
			}

			return $row_array;
		}

		public function query_splitv2($employee_time_in_ids=array(),$comp_id){

			$row_array 	= array();

			$select 	= array(
						'eti.schedule_blocks_time_in_id',
						'eti.employee_time_in_id',
						'eti.work_schedule_id',
						'eti.split_schedule_id',
						'eti.schedule_blocks_id',
						'eti.emp_id',
						'eti.comp_id',
						'eti.date',
						'eti.time_in',
						'eti.lunch_out',
						'eti.lunch_in',
						'eti.time_out',
						'eti.total_hours',
						'eti.total_hours_required',
						'eti.time_in_status',
						'eti.overbreak_min',
						'eti.late_min',
						'eti.tardiness_min',
						'eti.undertime_min',
						'eti.reason',
						'eti.source',
						'eti.absent_min',
						'eti.change_log_date_filed',
						'eti.change_log_time_in',
						'eti.change_log_lunch_out',
						'eti.change_log_lunch_in',
						'eti.change_log_time_out',
						'eti.change_log_total_hours',
						'eti.change_log_total_hours_required',
						'eti.change_log_tardiness_min',
						'eti.change_log_undertime_min',
						'eti.change_log_late_min',
						'eti.change_log_absent_min',
						'eti.change_log_overbreak_min',
						'eti.flag_tardiness_undertime',
						'eti.flag_halfday',
						'eti.date_halfday',
						'eti.flag_tardiness_undertime',
						'eti.status',
						'ati.approval_time_in_id',
						'ati.time_in_id',
						'ati.split_time_in_id',
						'ati.comp_id',
						'ati.approver_id',
						'ati.token',
						'ati.approve_by_head',
						'ati.approve_by_hr',
						'ati.flag_add_logs',
						'ati.level',
						'ati.token_level',
						);
			$w = array(
					"eti.status" => "Active",
					"eti.comp_id" => $comp_id,
					'eti.time_in_status' => 'pending'
			);


			$this->db->select($select);

			if($employee_time_in_ids){
				$this->db->where_in("eti.employee_time_in_id",$employee_time_in_ids);
			}

			$this->db->where($w);
			$this->db->distinct("eti.schedule_blocks_time_in");
			$this->db->join("approval_time_in AS ati","ati.split_time_in_id= eti.schedule_blocks_time_in_id","inner");
			$this->db->order_by("eti.time_in","ASC");
			$split_q = $this->db->get("schedule_blocks_time_in AS eti");
			
			$query_split = $split_q->result();

			if($query_split){
				foreach ($query_split as $r1){
					$wd 	= array(
							"employee_time_in_id"		=> $r1->employee_time_in_id,
							'schedule_blocks_time_in_id'=> $r1->schedule_blocks_time_in_id,
							'work_schedule_id'			=> $r1->work_schedule_id,
							'split_schedule_id'			=> $r1->split_schedule_id,
							'schedule_blocks_id'		=> $r1->schedule_blocks_id,
							'emp_id'					=> $r1->emp_id,
							'comp_id'					=> $r1->comp_id,
							'date'						=> $r1->date,
							'time_in'					=> $r1->time_in,
							'lunch_out'					=> $r1->lunch_out,
							'lunch_in'					=> $r1->lunch_in,
							'time_out'					=> $r1->time_out,
							'total_hours'				=> $r1->total_hours,
							'total_hours_required'		=> $r1->total_hours_required,
							'time_in_status'			=> $r1->time_in_status,
							'overbreak_min'				=> $r1->overbreak_min,
							'late_min'					=> $r1->late_min,
							'tardiness_min'				=> $r1->tardiness_min,
							'undertime_min'				=> $r1->undertime_min,
							'reason'					=> $r1->reason,
							'source'					=> $r1->source,
							'absent_min'				=> $r1->absent_min,
							'change_log_date_filed'		=> $r1->change_log_date_filed,
							'change_log_time_in'		=> $r1->change_log_time_in,
							'change_log_lunch_out'		=> $r1->change_log_lunch_out,
							'change_log_lunch_in'		=> $r1->change_log_lunch_in,
							'change_log_time_out'		=> $r1->change_log_time_out,
							'change_log_total_hours'	=> $r1->change_log_total_hours,
							'change_log_total_hours_required'	=> $r1->change_log_total_hours_required,
							'change_log_tardiness_min'	=> $r1->change_log_tardiness_min,
							'change_log_undertime_min'	=> $r1->change_log_undertime_min,
							'change_log_late_min'		=> $r1->change_log_late_min,
							'change_log_absent_min'		=> $r1->change_log_absent_min,
							'change_log_overbreak_min'	=> $r1->change_log_overbreak_min,
							'flag_tardiness_undertime'	=> $r1->flag_tardiness_undertime,
							'flag_halfday'				=> $r1->flag_halfday,
							'date_halfday'				=> $r1->date_halfday,
							'flag_tardiness_undertime'	=> $r1->flag_tardiness_undertime,
							'approval_time_in_id'		=> $r1->approval_time_in_id,
							'time_in_id'				=> $r1->time_in_id,
							'split_time_in_id'			=> $r1->split_time_in_id,
							'approver_id'				=> $r1->approver_id,
							'token'						=> $r1->token,
							'approve_by_head'			=> $r1->approve_by_head,
							'approve_by_hr'				=> $r1->approve_by_hr,
							'flag_add_logs'				=> $r1->flag_add_logs,
							'level'						=> $r1->level,
							'token_level'				=> $r1->token_level,
							'status'					=> $r1->status,
							"custom_searchv2"			=> "eti_{$r1->employee_time_in_id}",
							);
					array_push($row_array,$wd);
				}
			}

			return $row_array;

			//return ($query_split) ? $query_split : false;
		}

		/**
		 * Get the workschedule info to display in timelogs
		 * @param unknown $emp_id
		 * @param unknown $check_company_id
		 */
		public function get_workschedule_info_for_no_workschedule($emp_id,$check_company_id,$date,$work_schedule_id = "",$activate = false){

			$day = date('l',strtotime($date));
			$w_uwd = array(
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$check_company_id,
					"days_of_work" => $day,
					"status" => 'Active'
			);
			$this->edb->where($w_uwd);
			$arr4 = array(
					'work_schedule_name' => 'work_schedule_name',
					'work_end_time' => 'work_end_time',
					'work_start_time' => 'work_start_time',
					'break_in_min' => 'break_in_min',
					'total_work_hours' => 'total_work_hours'
			);
			$this->edb->select($arr4);
			$q_uwd = $this->edb->get("regular_schedule");
			
			$r_uwd = $q_uwd->row();

		    if($q_uwd->num_rows() > 0){
				
				if($activate){
					$arr = array(
							'start_time' => $r_uwd->work_start_time,
							'end_time' => $r_uwd->work_end_time,
							'break' => $r_uwd->break_in_min,
							'total_hours' => $r_uwd->total_work_hours,
							'name' => $r_uwd->work_schedule_name,
							'type' => 1
					);
					return $arr;
				}else{
				
					$data = time12hrs($r_uwd->work_start_time)."-".time12hrs($r_uwd->work_end_time)."<br>";
					$data .= "break: ".$r_uwd->break_in_min." mins";
					$data .= "<br> Total Hours: ".$r_uwd->total_work_hours;
				}
			}else{
				// FLEXIBLE HOURS
				$fw = array(
					"f.company_id"=>$check_company_id,
					"f.work_schedule_id"=>$work_schedule_id,
				);

				$this->db->where($fw);
				$arr3 = array(
						'latest_time_in_allowed' 			=> 'f.latest_time_in_allowed',
						'name' 								=> 'ws.name',
						'duration_of_lunch_break_per_day' 	=> 'duration_of_lunch_break_per_day',
						'total_hours_for_the_day' 			=> 'total_hours_for_the_day'
				);

				$this->edb->join("work_schedule AS ws","ws.work_schedule_id = f.work_schedule_id","LEFT");
				$fq = $this->edb->get("flexible_hours AS f");
				$r_fh = $fq->row();
				
				if($fq->num_rows() > 0){
					$data = $r_fh;
					$total_h = $r_fh->total_hours_for_the_day - ($r_fh->duration_of_lunch_break_per_day / 60);
					$total_h = number_format($total_h,2);
					if($activate){
							$arr = array(
									'start_time' => $r_fh->latest_time_in_allowed,
									'end_time' => "",
									'break' => $r_fh->duration_of_lunch_break_per_day,
									'total_hours' => $r_fh->total_hours_for_the_day,
									'name' => '',
									'type' => 2
							);
							return $arr;
					}
					else{
						if($r_fh->latest_time_in_allowed != NULL || $r_fh->latest_time_in_allowed != ""){
							$data  = "Latest Timein: ".time12hrs($r_fh->latest_time_in_allowed) . " <br> ";
							$data .= "break: ".$r_fh->duration_of_lunch_break_per_day. " mins";
							$data .= "<br> Total hours: ". $total_h;
						}else{
							$data = "break: ".$r_fh->duration_of_lunch_break_per_day." mins";
							$data .= "<br> Total hours: ". $total_h;
						}
					}
				}
			}
			return $data;
		}

		public function get_to_do_hours($comp_id,$limit,$start,$count=false,$search=""){

			$w 	= array(
				'company_id' 	=> $comp_id,
				'module' 		=> 'hours',
				'status' 		=> 'Active',
				);

			if($search){
				$w['emp_id'] = $search;
			}

			$this->db->where($w);
			//$q = $this->db->get('to_do_list');

			if(!$count){
				$this->db->order_by('date', 'desc');
				$q = $this->db->get('to_do_list',$limit,$start);
				$r = $q->result();
			}
			else{
				$q = $this->db->get('to_do_list');
				$r = $q->num_rows();
			}
			
			return ($r) ? $r : false;
		}

		public function get_to_do_hours_rd_ra($comp_id,$limit,$start,$count=false,$search=""){

			$w 	= array(
				'company_id' 	=> $comp_id,
				'module' 		=> 'rd_ra',
				'status' 		=> 'Active',
				);

			if($search){
				$w['emp_id'] = $search;
			}

			$this->db->where($w);
			//$q = $this->db->get('to_do_list');

			if(!$count){
				$this->db->order_by('date', 'desc');
				$q = $this->db->get('to_do_list',$limit,$start);
				$r = $q->result();
			}
			else{
				$q = $this->db->get('to_do_list');
				$r = $q->num_rows();
			}
			
			return ($r) ? $r : false;
		}

		public function get_to_do_hours_holiday($comp_id,$limit,$start,$count=false,$search=""){

			$w 	= array(
				'company_id' 	=> $comp_id,
				'module' 		=> 'holiday',
				'status' 		=> 'Active',
				);

			if($search){
				$w['emp_id'] = $search;
			}

			$this->db->where($w);
			//$q = $this->db->get('to_do_list');

			if(!$count){
				$this->db->order_by('date', 'desc');
				$q = $this->db->get('to_do_list',$limit,$start);
				$r = $q->result();
			}
			else{
				$q = $this->db->get('to_do_list');
				$r = $q->num_rows();
			}
			
			return ($r) ? $r : false;
		}

		public function get_all_timein_approvals($comp_id, $ids=array()){

			$row_array = array();
			$s 	= array(
				'approval_time_in_id',
				'time_in_id',
				'split_time_in_id',
				'emp_id',
				'token',
				'flag_add_logs',
				'level',
				'token_level',
				'status',
				);

			$w 	= array(
				'comp_id' => $comp_id,
				'status' => 'Active',
				);


			$this->db->select($s);
			$this->db->where($w);

			if($ids){
				$this->db->where_in("approval_time_in_id",$ids);
			}

			$q = $this->db->get("approval_time_in");
			$r = $q->result();

			if($r){
				foreach ($r as $r1){
					$wd 	= array(
							"approval_time_in_id"		=> $r1->approval_time_in_id,
							"time_in_id"				=> $r1->time_in_id,
							"split_time_in_id"			=> $r1->split_time_in_id,
							"emp_id"					=> $r1->emp_id,
							"token"						=> $r1->token,
							"flag_add_logs"				=> $r1->flag_add_logs,
							"level"						=> $r1->level,
							"token_level"				=> $r1->token_level,
							"custom_search"				=> "appr_{$r1->approval_time_in_id}",
							"custom_searchv2"			=> "appr_all",
							);
					array_push($row_array,$wd);
				}
			}

			return $row_array;
		}

		public function all_work_schedule($company_id,$work_schedule_ids=array()){
			$row_array 	= array();
			$s 			= array(
					'name',
					'work_schedule_id',
					'work_type_name',
					'break_rules',
					'assumed_breaks',
					'category_id',
					'flag_migrate',
					'enable_grace_period',
					'tardiness_rule',
			);
			$this->db->select($s);
			
			$w 			= array(
						'comp_id'=> $company_id,
						);

			$this->db->where($w);

			if($work_schedule_ids){
				$this->db->where_in("work_schedule_id",$work_schedule_ids);
			}

			$q_pg = $this->db->get('work_schedule');
			$r_pg = $q_pg->result();
			
			if($r_pg){
				foreach ($r_pg as $r1){
					$wd 	= array(
							"name"					=> $r1->name,
							"work_schedule_id"		=> $r1->work_schedule_id,
							"work_type_name"		=> $r1->work_type_name,
							"break_rules"			=> $r1->break_rules,
							"assumed_breaks"		=> $r1->assumed_breaks,
							"category_id"			=> $r1->category_id,
							"flag_migrate"			=> $r1->flag_migrate,
							"enable_grace_period"	=> $r1->enable_grace_period,
							"tardiness_rule"		=> $r1->tardiness_rule,
							"custom_search"			=> "wsid-{$r1->work_schedule_id}",
					);
					array_push($row_array,$wd);
				}
			}
			return $row_array;
		}

		public function get_all_employee($comp_id,$emp_ids=array()){

			$row_array = array();
			$s = array(
				'e.first_name',
				'e.last_name',
				'e.emp_id',
				'e.company_id',
				'a.payroll_cloud_id',
                'a.account_id',
                'a.profile_image'
				);
			$w = array(
				'e.company_id' => $comp_id
				);

			$this->edb->select($s);
			$this->db->where('(e.company_id = '.$comp_id.' OR e.company_id = 0)');

			if($emp_ids){
				$this->db->where_in("e.emp_id",$emp_ids);
			}
			$q = $this->db->join('accounts AS a', 'a.account_id = e.account_id');
			$q = $this->edb->get('employee AS e');
			$r = $q->result();
			
			if($r){
				foreach ($r as $r1){
					$wd 	= array(
							"first_name"		=> $r1->first_name,
							"last_name"			=> $r1->last_name,
							"emp_id"			=> $r1->emp_id,
							"comp_id"			=> $r1->company_id,
							"payroll_cloud_id"	=> $r1->payroll_cloud_id,
                            "account_id"		=> $r1->account_id,
                            "profile_image"     => $r1->profile_image,
							"custom_search"		=> "emps-{$r1->emp_id}",
					);
					array_push($row_array,$wd);
				}
			}
			return $row_array;
		}

		public function get_all_employee_time_in($comp_id,$emp_timein_ids=array()){

			$row_array = array();
			$s = array(
				'employee_time_in_id',
				'work_schedule_id',
				'emp_id',
				'date',
				'time_in',
				'lunch_out',
				'lunch_in',
				'break1_out',
				'break1_in',
				'break2_out',
				'break2_in',
				'time_out',
				'total_hours',
				'total_hours_required',
				'reason',
				'time_in_status',
				'overbreak_min',
				'late_min',
				'tardiness_min',
				'undertime_min',
				'absent_min',
				'notes',
				'source',
				'last_source',
				'change_log_date_filed',
				'change_log_time_in',
				'change_log_lunch_out',
				'change_log_lunch_in',
				'change_log_time_out',
				'change_log_break1_out',
				'change_log_break1_in',
				'change_log_break2_out',
				'change_log_break2_in',
				'change_log_total_hours',
				'change_log_total_hours_required',
				'change_log_tardiness_min',
				'change_log_undertime_min',
				'change_log_late_min',
				'change_log_absent_min',
				'flag_tardiness_undertime',
				'approval_time_in_id',
				);
			$w = array(
				'comp_id' => $comp_id
				);

			$this->db->select($s);
			$this->db->where($w);

			if($emp_timein_ids){
				$this->db->where_in("employee_time_in_id",$emp_timein_ids);
			}
			$q = $this->db->get('employee_time_in');
			$r = $q->result();

			if($r){
				foreach ($r as $r1){
					$wd 	= array(
							"employee_time_in_id"				=> $r1->employee_time_in_id,
							"work_schedule_id"					=> $r1->work_schedule_id,
							"emp_id"							=> $r1->emp_id,
							"date"								=> $r1->date,
							"time_in"							=> $r1->time_in,
							"lunch_out"							=> $r1->lunch_out,
							"lunch_in"							=> $r1->lunch_in,
							"break1_out"						=> $r1->break1_out,
							"break1_in"							=> $r1->break1_in,
							"break2_out"						=> $r1->break2_out,
							'break2_in'							=> $r1->break2_in,
							'time_out'							=> $r1->time_out,
							'total_hours'						=> $r1->total_hours,
							'total_hours_required'				=> $r1->total_hours_required,
							'reason'							=> $r1->reason,
							'time_in_status'					=> $r1->time_in_status,
							'overbreak_min'						=> $r1->overbreak_min,
							'late_min'							=> $r1->late_min,
							'tardiness_min'						=> $r1->tardiness_min,
							'undertime_min'						=> $r1->undertime_min,
							'absent_min'						=> $r1->absent_min,
							'notes'								=> $r1->notes,
							'source'							=> $r1->source,
							'last_source'						=> $r1->last_source,
							'change_log_date_filed'				=> $r1->change_log_date_filed,
							'change_log_time_in'				=> $r1->change_log_time_in,
							'change_log_lunch_out'				=> $r1->change_log_lunch_out,
							'change_log_lunch_in'				=> $r1->change_log_lunch_in,
							'change_log_time_out'				=> $r1->change_log_time_out,
							'change_log_break1_out'				=> $r1->change_log_break1_out,
							'change_log_break1_in'				=> $r1->change_log_break1_in,
							'change_log_break2_out'				=> $r1->change_log_break2_out,
							'change_log_break2_in'				=> $r1->change_log_break2_in,
							'change_log_total_hours'			=> $r1->change_log_total_hours,
							'change_log_total_hours_required'	=> $r1->change_log_total_hours_required,
							'change_log_tardiness_min'			=> $r1->change_log_tardiness_min,
							'change_log_undertime_min'			=> $r1->change_log_undertime_min,
							'change_log_late_min'				=> $r1->change_log_late_min,
							'change_log_absent_min'				=> $r1->change_log_absent_min,
							'flag_tardiness_undertime'			=> $r1->flag_tardiness_undertime,
							'approval_time_in_id'				=> $r1->approval_time_in_id,
							"custom_search"						=> "eti-{$r1->employee_time_in_id}",
					);
					array_push($row_array,$wd);
				}
			}
			return $row_array;
		}

		public function query_splitv3($employee_time_in_ids=array(),$comp_id){

			$row_array 	= array();

			$select 	= array(
						'eti.schedule_blocks_time_in_id',
						'eti.employee_time_in_id',
						'eti.work_schedule_id',
						'eti.split_schedule_id',
						'eti.schedule_blocks_id',
						'eti.emp_id',
						'eti.comp_id',
						'eti.date',
						'eti.time_in',
						'eti.lunch_out',
						'eti.lunch_in',
						'eti.time_out',
						'eti.total_hours',
						'eti.total_hours_required',
						'eti.time_in_status',
						'eti.overbreak_min',
						'eti.late_min',
						'eti.tardiness_min',
						'eti.undertime_min',
						'eti.reason',
						'eti.source',
						'eti.absent_min',
						'eti.change_log_date_filed',
						'eti.change_log_time_in',
						'eti.change_log_lunch_out',
						'eti.change_log_lunch_in',
						'eti.change_log_time_out',
						'eti.change_log_total_hours',
						'eti.change_log_total_hours_required',
						'eti.change_log_tardiness_min',
						'eti.change_log_undertime_min',
						'eti.change_log_late_min',
						'eti.change_log_absent_min',
						'eti.change_log_overbreak_min',
						'eti.flag_tardiness_undertime',
						'eti.flag_halfday',
						'eti.date_halfday',
						'eti.flag_tardiness_undertime',
						'eti.status',
						);
			$w = array(
					"eti.status" => "Active",
					"eti.comp_id" => $comp_id,
					'eti.time_in_status' => 'pending'
			);


			$this->db->select($select);

			if($employee_time_in_ids){
				$this->db->where_in("eti.employee_time_in_id",$employee_time_in_ids);
			}

			$this->db->where($w);
			$this->db->order_by("eti.time_in","ASC");
			$split_q = $this->db->get("schedule_blocks_time_in AS eti");
			
			$query_split = $split_q->result();

			if($query_split){
				foreach ($query_split as $r1){
					$wd 	= array(
							"employee_time_in_id"		=> $r1->employee_time_in_id,
							'schedule_blocks_time_in_id'=> $r1->schedule_blocks_time_in_id,
							'work_schedule_id'			=> $r1->work_schedule_id,
							'split_schedule_id'			=> $r1->split_schedule_id,
							'schedule_blocks_id'		=> $r1->schedule_blocks_id,
							'emp_id'					=> $r1->emp_id,
							'comp_id'					=> $r1->comp_id,
							'date'						=> $r1->date,
							'time_in'					=> $r1->time_in,
							'lunch_out'					=> $r1->lunch_out,
							'lunch_in'					=> $r1->lunch_in,
							'time_out'					=> $r1->time_out,
							'total_hours'				=> $r1->total_hours,
							'total_hours_required'		=> $r1->total_hours_required,
							'time_in_status'			=> $r1->time_in_status,
							'overbreak_min'				=> $r1->overbreak_min,
							'late_min'					=> $r1->late_min,
							'tardiness_min'				=> $r1->tardiness_min,
							'undertime_min'				=> $r1->undertime_min,
							'reason'					=> $r1->reason,
							'source'					=> $r1->source,
							'absent_min'				=> $r1->absent_min,
							'change_log_date_filed'		=> $r1->change_log_date_filed,
							'change_log_time_in'		=> $r1->change_log_time_in,
							'change_log_lunch_out'		=> $r1->change_log_lunch_out,
							'change_log_lunch_in'		=> $r1->change_log_lunch_in,
							'change_log_time_out'		=> $r1->change_log_time_out,
							'change_log_total_hours'	=> $r1->change_log_total_hours,
							'change_log_total_hours_required'	=> $r1->change_log_total_hours_required,
							'change_log_tardiness_min'	=> $r1->change_log_tardiness_min,
							'change_log_undertime_min'	=> $r1->change_log_undertime_min,
							'change_log_late_min'		=> $r1->change_log_late_min,
							'change_log_absent_min'		=> $r1->change_log_absent_min,
							'change_log_overbreak_min'	=> $r1->change_log_overbreak_min,
							'flag_tardiness_undertime'	=> $r1->flag_tardiness_undertime,
							'flag_halfday'				=> $r1->flag_halfday,
							'date_halfday'				=> $r1->date_halfday,
							'flag_tardiness_undertime'	=> $r1->flag_tardiness_undertime,
							'status'					=> $r1->status,
							"custom_searchv2"			=> "eti_{$r1->employee_time_in_id}",
							);
					array_push($row_array,$wd);
				}
			}

			return $row_array;

			//return ($query_split) ? $query_split : false;
		}

		public function add_new_todo_hours($company_id,$shift_date,$emp_id,$approval_id,$level,$approvers_id,$work_schedule_id="",$module="hours"){

			if($approval_id){

				$insert = array(
						'company_id' 		=> $company_id,
						"date"				=> $shift_date,
						"emp_id" 			=> $emp_id,
						"approval_id" 		=> $approval_id,
						"level" 			=> $level,
						"approvers_id" 		=> $approvers_id,
						"work_schedule_id" 	=> $work_schedule_id,
						"module" 			=> $module,
						);

				$w 		= array(
						'approval_id' 		=> $approval_id,
						'company_id' 		=> $company_id,
						'status' 			=> "Active",
						);

				$this->db->where($w);
				$q = $this->db->get("to_do_list");
				$r = $q->row();
				if($r){

					// if exist update
					$wupdate 	= array(
								'to_do_list_id' => $r->to_do_list_id
								);	
					$this->db->where($wupdate);
					$this->db->update("to_do_list",$insert);
				}
				else{
					// insert
					$this->db->insert('to_do_list', $insert); 
				}
			}
		}

		public function inactive_new_todo_hours($company_id,$approval_id){

			if($approval_id){

				$insert = array(
						'status' 			=> "Inactive",
						);

				$w 		= array(
						'approval_id' 		=> $approval_id,
						'company_id' 		=> $company_id,
						'status' 			=> "Active",
						);

				$this->db->where($w);
				$this->db->where($w);
				$q = $this->db->get("to_do_list");
				$r = $q->row();
				if($r){

					// if exist update
					$wupdate 	= array(
								'to_do_list_id' => $r->to_do_list_id,
								);	
					$this->db->where($wupdate);
					$this->db->update("to_do_list",$insert);
				}
			}

		}
	}
	
	
/* End of file Approve_leave_model */
/* Location: ./application/models/hr/Approve_leave_model.php */;
	