<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Employee Model
 *
 * @category Model
 * @version 1.0
 * @author Jonathan Bangga <jonathanbangga@gmail.com>
 */
	class Employee_model extends CI_Model {
		
		/**
		 * Fetch Employee Account
		 * @param unknown_type $emp_id
		 */
		public function my_profile($emp_id){
			$where = array(
				'employee.emp_id' => $emp_id,
				'employee.status' => 'Active'				
			);
			$this->edb->where($where);
			$this->edb->join('accounts','accounts.account_id = employee.account_id','left');
			$sql = $this->edb->get('employee');
			
			if($sql->num_rows() > 0){
				$row = $sql->row();
				$sql->free_result();
				return $row;
			}else{
				return false;
			}
		}
		
		/**
		 * Employee Leave Application Counter
		 * Enter description here ...
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function leave_application_counter($comp_id,$emp_id,$correction = false){
			
			$where = array(
				"el.company_id" => $comp_id,
				"el.emp_id" => $emp_id,
				"el.status" => 'Active'
			);
			
			/*$select = array (
				"COUNT(el.employee_leaves_application_id) AS count"
			);*/
			
			#$this->db->select($select);
			$this->db->where('el.flag_parent IS NOT NULL');
			$this->db->where($where);
			$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
			
			if($correction) {
			    $query = $this->db->get("employee_leaves_application_correction AS el");
			} else {
			    $query = $this->db->get("employee_leaves_application AS el");
			}
			
			#$row = $query->row();
				
			return ($query->num_rows > 0) ? $query->num_rows : 0;			
		}
		
		/**
		 * Employee Leave Application
		 * Enter description here ...
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function leave_application($limit = 10, $start = 0, $comp_id, $emp_id, $sort_by = "", $sort = "ASC",$correction = false){
			$where = array(
				"el.company_id" => $comp_id,
				"el.emp_id" => $emp_id,
				"el.status" => "Active"
			);
			
			$sort_array = array(
				"leave_type",
				"date_filed",
				"leave_application_status"
			);
			$this->db->where('el.flag_parent IS NOT NULL');
			$this->db->where($where);
			$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)) {
					$this->db->order_by($sort_by, $sort);
				}
			} else {
				$this->db->order_by("employee_leaves_application_id", "desc");
			}
			
			if($correction) {
			    $query = $this->db->get("employee_leaves_application_correction AS el",$limit, $start);
			} else {
			    $query = $this->db->get("employee_leaves_application AS el",$limit, $start);
			}
			
			$result = $query->result();
			
			return ($result) ? $result :false;
			
			/*
			if($start==0){
				$sql = $this->db->query("
					SELECT *FROM `employee_leaves_application` el
					LEFT JOIN `leave_type` lt ON el.leave_type_id = lt.leave_type_id
					WHERE el.company_id = {$comp_id}
					AND el.emp_id = {$emp_id}
					AND el.status = 'Active'
					ORDER BY employee_leaves_application_id DESC
					LIMIT ".$limit."
				");
				
				if($sql->num_rows() > 0){
					$row = $sql->result();
					$sql->free_result();
					return $row;
				}else{
					return false;
				}
			}else{
				$sql = $this->db->query("
					SELECT *FROM `employee_leaves_application` el
					LEFT JOIN `leave_type` lt ON el.leave_type_id = lt.leave_type_id
					WHERE el.company_id = {$comp_id}
					AND el.emp_id = {$emp_id}
					AND el.status = 'Active'
					ORDER BY employee_leaves_application_id DESC
					LIMIT ".$start.",".$limit."
				");
				
				if($sql->num_rows() > 0){
					$row = $sql->result();
					$sql->free_result();
					return $row;
				}else{
					return false;
				}
			}
			*/
		}
		
		/**
		 * Employee Loan Payment History 
		 * Enter description here ...
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function loans($comp_id,$emp_id){
			$where = array(
				"el.company_id" => $comp_id,
				"el.emp_id" => $emp_id,
				"el.status" => 'Active'
			);
			
			$this->db->where($where);
			$this->db->join("loan_type AS lt","lt.loan_type_id = el.loan_type_id","LEFT");
			$query = $this->db->get("employee_loans AS el");
			
			$result = $query->result();
			
			return ($result) ? $result : false;
			
			/*$sql = $this->db->query("
				SELECT *FROM `employee_loans` el
				LEFT JOIN loan_type lt ON el.loan_type_id = lt.loan_type_id
				WHERE el.company_id = {$comp_id}
				AND el.emp_id = {$emp_id}
				AND el.status = 'Active'
			");
			
			if($sql->num_rows() > 0){
				$row = $sql->result();
				$sql->free_result();
				return $row;
			}else{
				return false;
			}*/
		}
		
		/**
		 * Filter Loan Type
		 * @param unknown_type $loan_type
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function filter_loan_type($loan_type,$comp_id,$emp_id){
			$where = array (
				"el.company" => $comp_id,
				"el.emp_id" => $emp_id,
				"el.loan_type_id" => $loan_type,
				"el.status" => 'Active'
			);
			
			$this->db->where($where);
			$this->db->join("loan_type AS lt", "lt.loan_type_id = el.loan_type_id", "LEFT");
			$query = $this->db->get("employee_loans AS el");
			
			$result = $query->result();
			
			return ($result) ? $result : false;
			
			/*$sql = $this->db->query("
				SELECT *FROM `employee_loans` el
				LEFT JOIN loan_type lt ON el.loan_type_id = lt.loan_type_id
				WHERE el.company_id = {$comp_id}
				AND el.emp_id = {$emp_id}
				AND el.loan_type_id = '{$loan_type}'
				AND el.status = 'Active'
			");
			
			if($sql->num_rows() > 0){
				$row = $sql->result();
				$sql->free_result();
				return $row;
			}else{
				return false;
			}*/
		}
		
		/**
		 * Employee Overtime
		 * Enter description here ...
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function overtime($comp_id,$emp_id){
			/*$sql2 = $this->db->query("
				SELECT *FROM `overtime` o
				LEFT JOIN overtime_type ot ON o.overtime_type_id = ot.overtime_type_id
				LEFT JOIN location l ON o.location_id = l.location_id
				LEFT JOIN project p ON l.project_id = p.project_id 
				WHERE o.company_id = {$comp_id}
				AND o.emp_id = {$emp_id}
			");*/
			
			$where = array (
					"o.company_id" => $comp_id,
					"o.emp_id" => $emp_id,
					"o.status" => 'Active'
			);
				
			$this->db->where($where);
			$this->db->join("overtime_type AS ot", "ot.overtime_type_id = o.overtime_type_id", "LEFT");
			$query = $this->db->get("overtime AS o");
				
			$result = $query->result();
				
			return ($result) ? $result : false;
			
			/*$sql = $this->db->query("
				SELECT *FROM `overtime` o
				LEFT JOIN overtime_type ot ON o.overtime_type_id = ot.overtime_type_id
				WHERE o.company_id = {$comp_id}
				AND o.emp_id = {$emp_id}
				AND o.status = 'Active'
			");
			
			if($sql->num_rows() > 0){
				$row = $sql->result();
				$sql->free_result();
				return $row;
			}else{
				return false;
			}*/
		}
		
		/**
		 * Leave Type Information
		 * @param unknown_type $comp_id
		 */
		public function leave_type($comp_id,$emp_id,$leave_type_id=FALSE){
			
			$date = date("Y-m-d");
			if($leave_type_id == FALSE){
				$w = array(
						"el.company_id"=>$comp_id,
						"el.emp_id"=>$emp_id,
						"el.as_of <= "=>$date,
						"el.status"=>"Active"
				);
			}
			else{
				$w = array(
						"el.company_id"=>$comp_id,
						"el.emp_id"=>$emp_id,
						"el.as_of <= "=>$date,
						"el.leave_type_id"=>$leave_type_id,
						"el.status"=>"Active"
				);
			}
			
			$this->db->where($w);
			$this->db->join("leave_type lt","lt.leave_type_id = el.leave_type_id","left");
			$q = $this->db->get("employee_leaves AS el");
			$result = $q->result();
			return ($q->num_rows() > 0) ? $result : FALSE ;
		}
		
		/**
		 * Employee Overtime Information Counter
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function overtime_application_counter($comp_id,$emp_id,$from="",$to="",$correction = false){
			$between="";
			
			if($from!="" && $from!="none" && $to!="" && $to!="none"){
				$this->db->where('overtime_from BETWEEN "'.$from.'" AND "'.$to.'"',NULL,FALSE);
			}
			
			$where = array(
				"company_id" => $comp_id,
				"emp_id" => $emp_id,
				"status" => 'Active'
			);
			
			$select = array (
				"COUNT(overtime_id) AS count"
			);
			
			$this->db->select($select);
			$this->db->where($where);
			if($correction) {
			    $query = $this->db->get("employee_overtime_application_correction");
			} else {
			    $query = $this->db->get("employee_overtime_application");
			}
			
			$row = $query->row();
				
			return ($row) ? $row->count : 0;

			/*
			$sql = $this->db->query("
				SELECT 
				COUNT(overtime_id) as total_row
				FROM employee_overtime_application
				WHERE company_id = '{$comp_id}'
				AND emp_id = '{$emp_id}'
				AND status = 'Active'
			");
			if($sql->num_rows() > 0){
				$row = $sql->row();
				$sql->free_result();
				return $row->total_row;
			}else{
				return false;
			}*/
		}
		
		/**
		 * Employee Overtime Information
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function overtime_application($limit, $start, $comp_id, $emp_id, $sort_by = "", $sort = "ASC",$date_from="",$date_to="",$correction=false){
			
			$where = array(
					"company_id" => $comp_id,
					"emp_id" => $emp_id,
					"status" => "Active"
			);
			
			$sort_array = array(
					
				"overtime_date_applied",
				"no_of_hours",
				"overtime_status"
			);
			
			if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
				$this->db->where('overtime_from BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
			}
			
			$this->db->where($where);
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)) {
					$this->db->order_by($sort_by, $sort);
				}
			} else {
				$this->db->order_by("overtime_id", "desc");
			}
			
			if($correction) {
			    $query = $this->db->get("employee_overtime_application_correction",$limit, $start);
			} else {
			    $query = $this->db->get("employee_overtime_application",$limit, $start);
			}
			
			$result = $query->result();
				
			return ($result) ? $result :false;
		}
		
		/**
		 * Loan Type Information
		 * @param unknown_type $comp_id
		 */
		public function loan_type($comp_id){
			
			$where = array(
				"company_id" => $comp_id,
				"status" => 'Active'
			);
			
			$this->db->where($where);
			$query = $this->db->get("loan_type");
			
			$result = $query->result();
			
			return ($result) ? $result : false;
			
			/*$sql = $this->db->query("
				SELECT *FROM loan_type
				WHERE company_id = '{$comp_id}'
				AND status = 'Active'
			");
			if($sql->num_rows() > 0){
				$result = $sql->result();
				$sql->free_result();
				return $result;
			}else{
				return false;
			}*/
		}
		
		/**
		 * Check Employee Amortization Schedule ID		 
		 * @param unknown_type $amor_sched_id
		 * @param unknown_type $comp_id
		 */
		public function check_amortization_sched_id($emp_loan_id,$comp_id){
			
			$where = array(
				"company_id" => $comp_id,
				"employee_loans_id" => $emp_loan_id,
				"status" => 'Active'
			);
			
			$this->db->where($where);
			$query = $this->db->get("employee_loans");
			
			$result = $query->result();
			
			return ($result) ? $result : false;
			
			/*$sql = $this->db->query("
				SELECT 
				*FROM employee_loans
				WHERE company_id = '{$comp_id}'
				AND employee_loans_id = '{$emp_loan_id}'
				AND status = 'Active'
			");
			$results = $sql->result();
			if($sql->num_rows() > 0){
				return true;
			}else{
				return false;
			}*/
		}
		
		/**
		 * Employee Payment History Information
		 * @param unknown_type $comp_id
		 */
		public function emp_payment_history($comp_id, $loan_id){
			$where = array(
				"comp_id" => $comp_id,
				"employee_loans_id" => $loan_id,
				"status" => 'Active'
			);
			
			$this->db->where($where);
			$query = $this->db->get("employee_payment_history");
			
			$result = $query->result();
			
			return ($result) ? $result : false;
			
			/*$sql = $this->db->query("
				SELECT 
				*FROM employee_payment_history
				WHERE comp_id = '{$comp_id}'
				AND employee_loans_id = '{$loan_id}'
				AND status = 'Active'
			");
			
			if($sql->num_rows() > 0){
				$results = $sql->result();
				$sql->free_result();
				return $results;
			}else{
				return FALSE;
			}*/
		}
		
		/**
		 * Total Principal Amount Amortization
		 * @param unknown_type $loan_id
		 * @param unknown_type $comp_id
		 */
		public function total_princiapl_amortization($loan_id,$comp_id){
			$where = array(
				"comp_id" => $comp_id,
				"emp_loan_id" => $loan_id,
				"status" => 'Active'
			);
			
			$this->db->where($where);
			$query = $this->db->get("employee_amortization_schedule");
			
			$result = $query->result();
			
			if ($result){
				$total_val = 0;
				foreach($result as $row){
					$total_val = $total_val + $row->principal;
				}
				return $total_val;
			} else {
				return false;
			}
			
			/*
			$sql = $this->db->query("
				SELECT 
				*FROM employee_amortization_schedule
				WHERE comp_id = '{$comp_id}'
				AND emp_loan_id = '{$loan_id}'
				AND status = 'Active'
			");
			$result = $sql->result();
			
			if($sql->num_rows() > 0){
				$total_val = 0;
				foreach($result as $row){
					$total_val = $total_val + $row->principal;
				}
				return $total_val;
			}else{
				return false;
			}*/
		}
		
		/**
		 * Employee Loan Amount
		 * @param unknown_type $loan_id
		 * @param unknown_type $comp_id
		 */
		public function loan_amount($loan_id,$comp_id){
			
			$where = array(
					"comp_id" => $comp_id,
					"employee_loans_id" => $loan_id,
					"status" => 'Active'
			);
				
			$this->db->where($where);
			$query = $this->db->get("employee_loans");
				
			$row = $query->row();
				
			return ($row) ? $row->principal : false;
		
			/*$sql = $this->db->query("
				SELECT 
				*FROM employee_loans
				WHERE company_id = '{$comp_id}'
				AND employee_loans_id = '{$loan_id}'
				AND status = 'Active'
			");
			$row = $sql->row();
			if($sql->num_rows() > 0){
				return $row->principal;
			}else{
				return false;
			}*/
		}
		
		/**
		 * Employee Loan No Information
		 * @param unknown_type $comp_id
		 */
		public function emp_loan_no_group($comp_id, $loan_id){
			$w = array(
				"el.company_id"=>$comp_id,
				"e.status"=>"Active",
				"el.employee_loans_id"=>$loan_id
			);
			$this->edb->where($w);
			$this->edb->join("employee AS e","el.emp_id = e.emp_id","left");
			$this->edb->join("accounts AS a","e.account_id = a.account_id","left");
			$this->edb->join("loan_type AS lt","el.loan_type_id = lt.loan_type_id","left");
			$this->edb->group_by("e.emp_id");
			$sql = $this->edb->get("employee_loans AS el");
			
			if($sql->num_rows() > 0){
				$row = $sql->row();
				return $row;
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Get Interest and Principal Value from Payment History
		 * Enter description here ...
		 * @param unknown_type $amotization_id
		 * @param unknown_type $comp_id
		 */
		public function kapila_ka_row_interest_principal($loan_id, $comp_id){
			$sql = $this->db->query("
				SELECT 
				*FROM employee_payment_history
				WHERE comp_id = '{$comp_id}'
				AND employee_loans_id = '{$loan_id}'
				AND status = 'Active'
			");
			$row = $sql->row();
			if($sql->num_rows() > 0){
				$kapila_ka_row_res = $sql->num_rows() + 1;
				return $kapila_ka_row_res;
			}else{
				$kapila_ka_row_res = 1;
				return $kapila_ka_row_res;
			}
		}
		
		/**
		 * Get Interest and Principal Value
		 * @param unknown_type $get_kapila_ka_row
		 * @param unknown_type $comp_id
		 */
		public function get_interest_principal($amotization_id, $get_kapila_ka_row,$comp_id){
			$sql = $this->db->query("
				SELECT 
				*FROM employee_amortization_schedule
				WHERE comp_id = '{$comp_id}'
				AND emp_loan_id = '{$amotization_id}'
				AND status = 'Active'
				LIMIT {$get_kapila_ka_row},1
			");
			$row = $sql->row();
			if($sql->num_rows() > 0){
				$sql->free_result();
				return $row;
			}else{
				return false;
			}
		}
		
		/**
		 * Payment Debit Amount / Remaining Cash Amount
		 * @param unknown_type $loan_id
		 * @param unknown_type $comp_id
		 */
		public function payment_debit_amount($loan_id, $comp_id){
			$sql = $this->db->query("
				SELECT *
				FROM `employee_payment_history`
				WHERE comp_id = '{$comp_id}'
				AND employee_loans_id = '{$loan_id}'
				AND status = 'Active'
				ORDER BY employee_payment_history_id DESC
				LIMIT 1
			");
			$row = $sql->row();
			if($sql->num_rows() > 0){
				$sql->free_result();
				return $row->remaining_cash_amount;
			}else{
				return 0;
			}
		}
		
		/**
		 * Total Loan Amount from Amortization Schedule
		 * @param unknown_type $comp_id
		 * @param unknown_type $loan_id
		 */
		public function total_loan_amount($comp_id, $loan_id){
			$sql = $this->db->query("
				SELECT
				*FROM employee_amortization_schedule
				WHERE comp_id = '{$comp_id}'
				AND emp_loan_id = '{$loan_id}'
				AND status = 'Active'
			");
			
			if($sql->num_rows() > 0){
				$results = $sql->result();
				$sql->free_result();
				return $results;
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Check Employee Time Out Value
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function check_time_out_first($comp_id, $emp_id){
			$start_time = "";
			$date_val = date("Y")."-".date("m")."-".date("d");
			$time_val = date("H:i:s");
			$sql = $this->db->query("
				SELECT *FROM employee_time_in
				WHERE comp_id = '{$comp_id}'
				AND emp_id = '{$emp_id}'
				ORDER BY date DESC
				LIMIT 1
			");
			// AND date = '{$date_val}'
			
			if($sql->num_rows() > 0){
				$row = $sql->row();
				$sql->free_result();
				
				// compute number of day
				
				#$startDate = strtotime("{$row->time_in}");
				#$endDate = strtotime("{$date_val} {$time_val}");
				
				$where = array(
					"emp_id"=>$emp_id
				);
				$this->db->where($where);
				$sql_payroll = $this->db->get("employee_payroll_information");
				$row_payroll = $sql_payroll->row();
				if($row_payroll){
					$payroll_group_id = $row_payroll->payroll_group_id;
					
					$this->db->where('payroll_group_id',$payroll_group_id);
					$sql2 = $this->db->get("payroll_group");
					$row2 = $sql2->row();
					$work_schedule_id = $row2->work_schedule_id;
					
					$where_st = array(
						"work_schedule_id"=>$work_schedule_id,
						"company_id"=>$comp_id
					);
					$this->db->where($where_st);
					$sql_st = $this->db->get("regular_schedule");
					$row_st = $sql_st->row();
					if($sql_st->num_rows() > 0){
						// for regular schedules
						$start_time = $row_st->work_start_time;
					}else{
						// for workshift
						$where_w = array(
							"eb.work_schedule_id"=>$work_schedule_id,
							"eb.emp_id"=>$emp_id
						);
						$this->db->where($where_w);
						//$this->db->join('schedule_blocks');
						$this->db->join('schedule_blocks AS sb','sb.schedule_blocks_id = eb.schedule_blocks_id','LEFT');
						$sql_w = $this->db->get("employee_sched_block AS eb");
						$sql_w = $this->db->get("schedule_blocks");
						$row_w = $sql_w->row();
						if($sql_w->num_rows() > 0){
							$start_time = $row_w->start_time;
						}else{
							// for flexible hours
							$where_f = array(
								"work_schedule_id"=>$work_schedule_id,
								"company_id"=>$comp_id
							);
							$this->db->where($where_f);
							$sql_f = $this->db->get("flexible_hours");
							$row_f = $sql_f->row();
							if($sql_f->num_rows() > 0){
								$start_time = $row_f->latest_time_in_allowed;
							}
						}
					}
					
					// check if workday is restday
					
					if($start_time == ""){
						return false;
					}else{
						$current_date = date("Y-m-d H:i:s");
						$ti_date = date("Y-m-d",strtotime($row->time_in));
						
						$startDate = strtotime("{$ti_date} {$start_time} -1 hour");
						$endDate = strtotime("{$current_date}");
						
						$interval = $endDate - $startDate;
						$days = floor($interval / (60 * 60 * 24));
						
						// if no. of day is greater than 0, add new row for employee time in
						if($days >= 1){
							return FALSE;
						}else{
							// get employee time in information
							return $row;
						}
					}
				}else{
					return FALSE;
				}
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Time In Table is empty
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $date_val
		 * @param unknown_type $time_in
		 */
		public function time_in_is_empty($comp_id, $emp_id){
			#$current_datetime = date("Y")."-".date("m")."-".date("d");
			$current_datetime = date("Y-m-d H:i:s");
			$time_val = date("H:i:s");
			$sql = $this->db->query("
				SELECT *FROM employee_time_in
				WHERE comp_id = '{$comp_id}'
				AND emp_id = '{$emp_id}'
				ORDER BY date DESC
				LIMIT 1
			");
			if($sql->num_rows() == 0){
				return TRUE;
			}else{
				$row = $sql->row();
				$sql->free_result();
				
				$where = array(
					"emp_id"=>$emp_id
				);
				$this->db->where($where);
				$sql_payroll = $this->db->get("employee_payroll_information");
				$row_payroll = $sql_payroll->row();
				$payroll_group_id = $row_payroll->payroll_group_id;
				
				$where_st = array(
					"payroll_group_id"=>$payroll_group_id,
					"company_id"=>$comp_id
				);
				$this->db->where($where_st);
				$sql_st = $this->db->get("regular_sechdule");
				$row_st = $sql_st->row();
				if($sql_st->num_rows() > 0){
					// for regular schedules
					$start_time = $row_st->work_start_time;
				}else{
					// for workshift
					$where_w = array(
						"payroll_group_id"=>$payroll_group_id,
						"company_id"=>$comp_id
					);
					$this->db->where($where_w);
					$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
					$sql_w = $this->db->get("split_schedule");
					$row_w = $sql_w->row();
					if($sql_w->num_rows() > 0){
						$start_time = $row_w->start_time;
					}else{
						// for flexible hours
						$where_f = array(
							"payroll_group_id"=>$payroll_group_id,
							"company_id"=>$comp_id
						);
						$this->db->where($where_f);
						$sql_f = $this->db->get("flexible_hours");
						$row_f = $sql_f->row();
						if($sql_f->num_rows() > 0){
							$start_time = $row_f->latest_time_in_allowed;
						}
					}
				}
				
				$current_date = date("Y-m-d H:i:s");
				$ti_date = date("Y-m-d",strtotime($row->time_in));
				
				$startDate = strtotime("{$ti_date} {$start_time} -1 hour");
				
				$endDate = strtotime("{$current_datetime}");
				$interval = $endDate - $startDate;
				$days = floor($interval / (60 * 60 * 24));
				
				// if no. of day is greater than 0, add new row for employee time in
				
				if($days >= 1){
					return TRUE;
				}else{
					return FALSE;
				}
			}
		}
		
		/**
		 * Employee Time In List Counter
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function time_in_list_counter($comp_id, $emp_id,$from="",$to=""){
			
			$sel = array(
					'COUNT(employee_time_in_id) AS total_row'
			);
			
			if($from!="" && $from!="none" && $to!="" && $to!="none"){
				$this->db->where('date BETWEEN "'.$from.'" AND "'.$to.'"',NULL,FALSE);
			}
			
			$where = array(
					'comp_id' 	=> $comp_id,
					'emp_id'	=> $emp_id
			);
			
			$this->db->select($sel);
			$this->db->where($where);
			$q = $this->db->get('employee_time_in');
			$r = $q->row();
			
			return ($r) ? $r->total_row : false;
			
			/*$sql = $this->db->query("
				SELECT 
				COUNT(employee_time_in_id) AS total_row
				FROM employee_time_in
				WHERE comp_id = '{$comp_id}'
				AND emp_id = '{$emp_id}'
				ORDER BY date DESC
			");
			if($sql->num_rows() > 0){
				$row = $sql->row();
				$sql->free_result();
				return $row->total_row;
			}else{
				return FALSE;
			}*/
		}
		
		/**
		 * Employee Time In List
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function time_in_list($limit, $start, $comp_id, $emp_id, $sort="DESC", $sort_by,$date_from="",$date_to=""){
			$sort_array = array(
					"date",
					"time_in_status"
			);
			if($start==0){
			
				$w = array(
					'et.emp_id'=>$emp_id,
					'et.comp_id'=> $comp_id,
					'et.status'=> 'Active',
				    'et.flag_open_shift' => '0'
				);
				if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
					$this->db->where('date BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
				}
				$this->db->where($w);
				if($sort_by != ""){
					if(in_array($sort_by, $sort_array)){
						$this->db->order_by($sort_by,$sort);
					}
				}else{
					$this->db->order_by('et.date','DESC');
				}
				
				$sql = $this->db->get('employee_time_in AS et',$limit);
				
				if($sql->num_rows() > 0){
					$results = $sql->result();
					$sql->free_result();
					return $results;
				}else{
					return FALSE;
				}
			}else{
				$w = array(
					'et.emp_id'=>$emp_id,
					'et.comp_id'=> $comp_id,
				    'et.status'=> 'Active',
				    'et.flag_open_shift' => '0'
				);
				if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
					$this->db->where('date BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
				}
				$this->db->where($w);
				if($sort_by != ""){
					if(in_array($sort_by, $sort_array)){
						$this->db->order_by($sort_by,$sort);
					}
				}else{
					$this->db->order_by('et.date','DESC');
				}
				//$this->db->join('approval_time_in AS at','et.employee_time_in_id = at.time_in_id',"LEFT");
				$sql = $this->db->get('employee_time_in AS et',$start,$limit);
				
				if($sql->num_rows() > 0){
					$results = $sql->result();
					$sql->free_result();
					return $results;
				}else{
					return FALSE;
				}
			}
		}
		/**
		 * Employee Time In List with sorting
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function time_in_list_sorted($comp_id, $emp_id, $type, $split = false){
		    
			$period = next_pay_period($emp_id, $comp_id);
			$w = array(
					'date >=' => date('Y-m-d', strtotime($period->cut_off_from)),
					'date <=' => date('Y-m-d', strtotime($period->first_payroll_date)),
					'emp_id'=>$emp_id,
					'comp_id'=> $comp_id,
					'status'=> 'Active'
			);
			$this->db->where($w);
			if($type == 'pending' || $type == 'reject'){
			    if($type == 'reject') {
			        #$this->db->where("time_in_status IS NOT NULL");
			        $this->db->where("((time_in_status IS NULL and last_source IS NULL and corrected = 'Yes') OR (time_in_status = 'reject'))");
			    } else {
			        $this->db->where('time_in_status',$type);
			    }
			}
			if($type == 'undertime'){
				$u_where = array('undertime_min >'=> 0);
				$this->db->where($u_where);
			}
			if($type == 'tardiness'){
				$t_where = array('tardiness_min >'=> 0);
				$this->db->where($t_where);
			}
			$this->db->order_by('date','DESC');
			$sql = $this->db->get('employee_time_in');
			#last_query();
			if($sql->num_rows() > 0){ 
				$results = $sql->result();
				$sql->free_result();
				return $results;
			}else{
				return FALSE;
			}
			
		}
		
		/**
		 * Get Information Current Time In
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function get_timein_today($comp_id, $emp_id){
			$sql = $this->db->query("
				SELECT *FROM employee_time_in
				WHERE comp_id = '{$comp_id}'
				AND emp_id = '{$emp_id}'
				ORDER BY date DESC
				LIMIT 1
			");
			
			if($sql->num_rows() > 0){
				$row = $sql->row();
				$sql->free_result();
				
				$date_val = date("Y")."-".date("m")."-".date("d");
				$time_val = date("H:i:s");
				
				$current_date = date("Y-m-d H:i:s");
				
				// compute number of day
				
				$where = array(
					"emp_id"=>$emp_id
				);
				$this->db->where($where);
				$sql_payroll = $this->db->get("employee_payroll_information");
				$row_payroll = $sql_payroll->row();
				$payroll_group_id = $row_payroll->payroll_group_id;
				
				$where_st = array(
					"payroll_group_id"=>$payroll_group_id,
					"company_id"=>$comp_id
				);
				$this->db->where($where_st);
				$sql_st = $this->db->get("regular_scehdule");
				$row_st = $sql_st->row();
				if($sql_st->num_rows() > 0){
					// for regular schedules
					$start_time = $row_st->work_start_time;
				}else{
					// for workshift
					$where_w = array(
						"payroll_group_id"=>$payroll_group_id,
						"company_id"=>$comp_id
					);
					$this->db->where($where_w);
					$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
					$sql_w = $this->db->get("split_schedule");
					$row_w = $sql_w->row();
					if($sql_w->num_rows() > 0){
						$start_time = $row_w->start_time;
					}else{
						// for flexible hours
						$where_f = array(
							"payroll_group_id"=>$payroll_group_id,
							"company_id"=>$comp_id
						);
						$this->db->where($where_f);
						$sql_f = $this->db->get("flexible_hours");
						$row_f = $sql_f->row();
						if($sql_f->num_rows() > 0){
							$start_time = $row_f->latest_time_in_allowed;
						}
					}
				}
				
				$current_date = date("Y-m-d H:i:s");
				$ti_date = date("Y-m-d",strtotime($row->time_in));
				
				$startDate = strtotime("{$ti_date} {$start_time} -1 hour");
				
				$endDate = strtotime($current_date);
				
				$interval = $endDate - $startDate;
				# $days = floor($interval / (60 * 60 * 24));
				$days = $interval / (60 * 60 * 24);
				
				#print $row->time_in." = ".$current_date;
				// if no. of day is greater than 0, add new row for employee time in
				
				#if($days > 0){
				
				if($days >= 1){
					return FALSE;
				}else{
					// get employee time in information
					return $row;
				}
			
				/*if(strtotime(time()) > strtotime(time($row->date))){
					return FALSE;
				}else{
					// get employee time in information
					return $row;
				}*/
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Update Employee Lunch Out value
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $lunch_out_val
		 */
		public function update_lunch_out($comp_id, $emp_id, $employee_time_in_id){
			$date_val = date("Y")."-".date("m")."-".date("d");
			$lunch_out_val = date('Y-m-d H:i:s');
			$sql = $this->db->query("
				UPDATE employee_time_in
				SET lunch_out = '{$lunch_out_val}'
				WHERE comp_id = '{$comp_id}'
				AND emp_id = '{$emp_id}'
				AND employee_time_in_id = '{$employee_time_in_id}'
			");
			
			if($sql){
				return TRUE;
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Update Employee Lunch In value
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $lunch_out_val
		 */
		public function update_lunch_in($comp_id, $emp_id, $employee_time_in_id){
			$date_val = date("Y")."-".date("m")."-".date("d");
			$current_time = date('Y-m-d H:i:s');
			$sql = $this->db->query("
				UPDATE employee_time_in
				SET lunch_in = '{$current_time}'
				WHERE comp_id = '{$comp_id}'
				AND emp_id = '{$emp_id}'
				AND employee_time_in_id = '{$employee_time_in_id}'
			");
			
			if($sql){
				return TRUE;
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Update Employee Time Out value
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $lunch_out_val
		 */
		public function update_time_out($comp_id, $emp_id, $employee_time_in_id, $under_min_val){
			$date_val = date("Y")."-".date("m")."-".date("d");
			$current_time = date('Y-m-d H:i:s');
			$sql = $this->db->query("
				UPDATE employee_time_in
				SET time_out = '{$current_time}', undertime_min = '{$under_min_val}'
				WHERE comp_id = '{$comp_id}'
				AND emp_id = '{$emp_id}'
				AND employee_time_in_id = '{$employee_time_in_id}'
			");
			
			if($sql){
				return TRUE;
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Check Current Time for lunch out and time out
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $min_log
		 */
		public function check_current_time_login($comp_id, $emp_id, $min_log){
			$time_val = date("H:i:s");
			$date_val = date("Y")."-".date("m")."-".date("d");
			$sql = $this->db->query("
				SELECT *FROM employee_time_in
				WHERE comp_id = '{$comp_id}'
				AND emp_id = '{$emp_id}'
				ORDER BY date DESC
				LIMIT 1
			");
			
			if($sql->num_rows() > 0){
				$row = $sql->row();
				$sql->free_result();
				$time_in = $row->time_in;
				$lunch_out = $row->lunch_out;
				$lunch_in = $row->lunch_in;
				$time_out = $row->time_out;
				#if($lunch_out=="0000-00-00 00:00:00"){
				if($lunch_out==NULL){
					
					// this is for lunch out
					
					// compute number of minutes
					$startTime = $time_in;
					$endTime = $time_val;
					$minute = floor((strtotime($endTime) - strtotime($startTime)) / 60);
					
					// if no. of minute is greater than $min_log, add new row for employee time in
					if($minute < $min_log){
						return TRUE;
					}else{
						return FALSE;
					}
				#}elseif($lunch_out!="0000-00-00 00:00:00" && $time_out=="0000-00-00 00:00:00"){
				}elseif($lunch_out!=NULL && $time_out==NULL){
					
					// this is for time out
					
					// compute number of minutes
					$startTime = $lunch_in;
					$endTime = $time_val;
					$minute = floor((strtotime($endTime) - strtotime($startTime)) / 60);
					
					// if no. of minute is greater than $min_log, add new row for employee time in
					if($minute < $min_log){
						return TRUE;
					}else{
						return FALSE;
					}
				}
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Check Current Time for time in and lunch in
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $min_log
		 */
		public function check_current_time_to_timein_lunchin($comp_id, $emp_id, $min_log){
			$time_val = date("H:i:s");
			$date_val = date("Y")."-".date("m")."-".date("d");
			$sql = $this->db->query("
				SELECT *FROM employee_time_in
				WHERE comp_id = '{$comp_id}'
				AND emp_id = '{$emp_id}'
				ORDER BY date DESC
				LIMIT 1
			");
			
			if($sql->num_rows() > 0){
				$row = $sql->row();
				$sql->free_result();
				$time_in = $row->time_in;
				$lunch_out = $row->lunch_out;
				$lunch_in = $row->lunch_in;
				$time_out = $row->time_out;
				#if($lunch_in=="0000-00-00 00:00:00"){
				if($lunch_in==NULL){
					// this is for lunch in
					// compute number of minutes
					$startTime = $lunch_out;
					$endTime = $time_val;
					$minute = floor((strtotime($endTime) - strtotime($startTime)) / 60);
					
					// if no. of minute is greater than $min_log, add new row for employee time in
					if($minute < $min_log){
						return TRUE;
					}else{
						return FALSE;
					}
				}
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Check if time in is not empty
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function check_time_in_is_empty($comp_id, $emp_id){
			$sql = $this->db->query("
				SELECT *FROM employee_time_in
				WHERE comp_id = '{$comp_id}'
				AND emp_id = '{$emp_id}'
				ORDER BY date DESC
				LIMIT 1
			");
			
			if($sql->num_rows() > 0){
				$row = $sql->row();
				$sql->free_result();
				$time_in = $row->time_in;
				$lunch_out = $row->lunch_out;
				$lunch_in = $row->lunch_in;
				$time_out = $row->time_out;
				
				if($time_in == "0000-00-00 00:00:00" && $lunch_out == "0000-00-00 00:00:00"){
					print $time_in;
					return TRUE;
				}elseif($lunch_out != "0000-00-00 00:00:00" && $lunch_in == "0000-00-00 00:00:00" && $lunch_in == "0000-00-00 00:00:00"){
					return TRUE;
				}else{
					return FALSE;
				}
			}else{
				return TRUE;
			}
		}
		
		/**
		 * Get Employee Time In Information
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $timein_id
		 */
		public function get_timein_info($comp_id, $emp_id, $timein_id){
			$sql = $this->db->query("
				SELECT *FROM employee_time_in
				WHERE comp_id = '{$comp_id}'
				AND emp_id = '{$emp_id}'
				AND employee_time_in_id = '{$timein_id}'
				AND status = 'Active'
			");
			if($sql->num_rows() > 0){
				$row = $sql->row();
				$sql->free_result();
				return $row;
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Update Employee Time In Log
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $employee_timein
		 * @param unknown_type $time_in
		 * @param unknown_type $lunch_out
		 * @param unknown_type $lunch_in
		 * @param unknown_type $time_out
		 * @param unknown_type $reason
		 * @param unknown_type $hours_worked
		 */
		public function update_employee_time_log(
			$comp_id, $emp_id, $employee_timein, $time_in, $lunch_out, $lunch_in, $time_out, $reason, $hours_worked
		){
			if($lunch_out != "0000-00-00 00:00:00" || $lunch_in != "0000-00-00 00:00:00" || $time_out != "0000-00-00 00:00:00"){
				$compute_timein_lunchout = (strtotime($lunch_out) - strtotime($time_in)) / 3600; 
				$compute_lunchin_timeout = (strtotime($time_out) - strtotime($lunch_in)) / 3600;
				$first_hours_worked = round($compute_timein_lunchout,2);
				$second_hours_worked = round($compute_lunchin_timeout,2);
				
				$total_hours_worked = $first_hours_worked + $second_hours_worked;
				if($total_hours_worked > $hours_worked){
					$total_hours_worked = $hours_worked;
				}
			}else{
				$total_hours_worked = "0.00";
			}
			$sql = $this->db->query("
				UPDATE employee_time_in
				SET time_in = '{$time_in}', lunch_out = '{$lunch_out}', lunch_in = '{$lunch_in}', time_out = '{$time_out}', 
				reason = '{$reason}', time_in_status = 'pending', corrected = 'Yes', total_hours = '{$total_hours_worked}'
				WHERE comp_id = '{$comp_id}'
				AND emp_id = '{$emp_id}'
				AND employee_time_in_id = '{$employee_timein}'
			");
			if($sql){
				return TRUE;
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Total Hours Worked
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $hours_worked
		 */
		public function total_hours($comp_id, $emp_id, $hours_worked){
			$sql = $this->db->query("
				SELECT *FROM employee_time_in
				WHERE comp_id = '{$comp_id}'
				AND emp_id = '{$emp_id}'
				AND status = 'Active'
				ORDER BY date DESC
				LIMIT 1
			");
			if($sql->num_rows() > 0){
				$row = $sql->row();
				$sql->free_result();
				
				$compute_timein_lunchout = (strtotime($row->lunch_out) - strtotime($row->time_in)) / 3600; 
				$compute_lunchin_timeout = (strtotime($row->time_out) - strtotime($row->lunch_in)) / 3600;
				$first_hours_worked = round($compute_timein_lunchout,2);
				$second_hours_worked = round($compute_lunchin_timeout,2);
				
				$total_hours_worked = $first_hours_worked + $second_hours_worked;
				$total_hours_worked_view = $first_hours_worked + $second_hours_worked;
				if($total_hours_worked > $hours_worked){
					$total_hours_worked = $hours_worked;
				}
				
				$sql_update = $this->db->query("
					UPDATE employee_time_in
					SET 
					total_hours = '{$total_hours_worked}',
					total_hours_required = '{$total_hours_worked_view}'
					WHERE comp_id = '{$comp_id}'
					AND emp_id = '{$emp_id}'
					AND employee_time_in_id = '{$row->employee_time_in_id}'
				");
				if($sql_update){
					return TRUE;
				}else{
					return FALSE;
				}
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Get Employee Week Day Value
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $weekDay_value
		 */
		public function weekDay_value($comp_id, $emp_id, $weekDay_value){
			// for rest day
			$sql = $this->db->query("
				SELECT *FROM employee_shifts_schedule ess
				LEFT JOIN rest_day rd ON ess.payroll_group_id = rd.payroll_group_id 
				WHERE ess.company_id = '{$comp_id}'
				AND ess.emp_id = '{$emp_id}'
				AND rd.rest_day = '{$weekDay_value}'
			");
			
			if($sql->num_rows() > 0){
				return TRUE;
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Get Date Week Day Value
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $weekDay_value
		 */
		public function date_weekDay_value($comp_id, $emp_id, $weekDay_value, $start_time){
			// PAYROLL INFORMATION
			$where = array(
				"emp_id" => $emp_id,
				"company_id" => $comp_id
			);
			
			$this->db->where($where);
			$sql_payroll_info = $this->db->get("employee_payroll_information");
			$row_payroll_info = $sql_payroll_info->row();
			$emp_payroll_group_id = $row_payroll_info->payroll_group_id;
			
			// REGULAR SCHEDULE
			$where_uniform_settings = array(
				"company_id" => $comp_id,
				"payroll_group_id" => $emp_payroll_group_id
			);
			$this->db->where($where_uniform_settings);
			$sql_regular_schedules_where = $this->db->get("regular_schedule");
			$row_regular_schedules_where = $sql_regular_schedules_where->row();
			
			// for rest day return value 0
			$sql = $this->db->query("
				SELECT *FROM employee_shifts_schedule ess
				LEFT JOIN rest_day rd ON ess.payroll_group_id = rd.payroll_group_id 
				WHERE ess.company_id = '{$comp_id}'
				AND ess.emp_id = '{$emp_id}'
				AND rd.rest_day = '{$weekDay_value}'
			");
			
			if($sql->num_rows() > 0){
				return "0";
			}else{

				$zero = "00:00:00";
				
				// for regular schedules
				$sql_uniform_wd = $this->db->query("
					SELECT *FROM employee_shifts_schedule ess
					LEFT JOIN regular_schedule rs ON ess.payroll_group_id = rs.payroll_group_id
					WHERE ess.company_id = '{$comp_id}'
					AND ess.emp_id = '{$emp_id}'
					AND rs.days_of_work = '{$weekDay_value}'
				");
				
				if($sql_uniform_wd->num_rows() > 0){

					$row = $sql_uniform_wd->row();
					$sql_uniform_wd->free_result();
					
					#$work_start_time = ($sql_uniform_working_days_where->num_rows() > 0) ? $row_uniform_working_days_where->latest_time_in_allowed: $row->work_start_time;
					$work_start_time = $row->work_start_time;
					$work_end_time = $row->work_end_time;

					$new_weekday_val = date("h:i:s A",strtotime($work_start_time))."-".date("h:i:s A",strtotime($work_end_time));
					return $new_weekday_val;
				}else{
					// workshift working days
					
					$where = array(
							'ess.company_id'=>$comp_id,
							'ess.emp_id'=>$emp_id
					);
					$this->db->where($where);
					$this->db->join('split_schedule AS sc','ess.payroll_group_id = sc.payroll_group_id','LEFT');
					$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = sc.split_schedule_id','LEFT');
					$sql_workshift = $this->db->get('employee_shifts_schedule AS ess');
// 					$sql_workshift = $this->db->query("
// 						SELECT *FROM employee_shifts_schedule ess
// 						LEFT JOIN split_schedule w ON ess.payroll_group_id = w.payroll_group_id
// 						WHERE ess.company_id = '{$comp_id}'
// 						AND ess.emp_id = '{$emp_id}'
// 					");
					
					if($sql_workshift->num_rows() > 0){
						$row_workshift = $sql_workshift->row();
						$sql_workshift->free_result();
						$work_start_time = $row_workshift->start_time;
						$work_end_time = $row_workshift->end_time;
						
						// check workshift grace period
						$where_grace_p = array(
							"payroll_group_id"=>$emp_payroll_group_id,
							"company_id"=>$comp_id
						);
						$this->db->where($where_grace_p);
						$sql_grace_p = $this->db->get("split_schedule");
						$row_grace_p = $sql_grace_p->row();
						
						if($sql_grace_p->num_rows() > 0){
							$grace_period_val = strtotime($work_start_time) + ($row_grace_p->grace_period_for_every_shift * 60);
						}else{
							$grace_period_val = strtotime($grace_period_val);
						}
						
						#$new_weekday_val = date("h:i:s A",$grace_period_val)."-".date("h:i:s A",strtotime($work_end_time));
						$new_weekday_val = date("h:i:s A",strtotime($work_start_time))."-".date("h:i:s A",strtotime($work_end_time));
						return $new_weekday_val;
					}else{
						// flexible working days
						$new_weekday_val = date("h:i:s A",strtotime($start_time))."-".date("H:i:s",strtotime($start_time) + 60 * 60 * 8); // 8 = labor code
						return $new_weekday_val;
					}
				}
			}
		}
		
		/**
		 * Get Employee Shift Schedule Information
		 * @param unknown_type $comp_id
		 * @param unknown_type $day
		 */
		public function get_shift_sched($emp_id,$day){
			// rest day
			$rest_day = $this->db->query("
				SELECT *FROM employee_shifts_schedule ess
				LEFT JOIN rest_day rd ON rd.payroll_group_id = ess.payroll_group_id
				WHERE ess.emp_id = '{$emp_id}'
				AND rd.rest_day = '{$day}'
			");
			
			if($rest_day->num_rows() == 0){
				// regular schedule
				$sql = $this->db->query("
					SELECT *FROM employee_shifts_schedule ess
					LEFT JOIN regular_schedule rs ON rs.payroll_group_id = ess.payroll_group_id
					WHERE ess.emp_id = '{$emp_id}'
				");
				if($sql->num_rows() > 0){
					$row = $sql->row();
					$sql->free_result();
					return $row->latest_time_in_allowed;
				}else{
					// regular schedules
					$sql_regular_schedules = $this->db->query("
						SELECT *FROM employee_shifts_schedule ess
						LEFT JOIN regular_schedule rs ON rs.payroll_group_id = ess.payroll_group_id
						WHERE ess.emp_id = '{$emp_id}'
					");
					
					if($sql_regular_schedules->num_rows() > 0){
						$row_regular_schedules = $sql_regular_schedules->row();
						$sql_regular_schedules->free_result();
						return $row_regular_schedules->work_start_time;
					}else{
						// flexible working days
						$sql_flexible_days = $this->db->query("
							SELECT *FROM employee_shifts_schedule ess
							LEFT JOIN flexible_hours fh ON fh.payroll_group_id = ess.payroll_group_id
							WHERE ess.emp_id = '{$emp_id}'
						");
						
						if($sql_flexible_days->num_rows() > 0){
							$row_flexible_days = $sql_flexible_days->row();
							$sql_flexible_days->free_result();
							return $row_flexible_days->latest_time_in_allowed;
						}else{
							// workshift working days
// 							$sql_workshift = $this->db->query("
// 								SELECT *FROM employee_shifts_schedule ess
// 								LEFT JOIN workshift w ON w.payroll_group_id = ess.payroll_group_id
// 								WHERE ess.emp_id = '{$emp_id}'
// 							");
							$where = array(
								'ess.emp_id'=>$emp_id	
									
							);
							$this->db->where($where);
							$this->db->join('split_schedule AS sc','ess.payroll_group_id = sc.payroll_group_id','LEFT');
							$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = sc.split_schedule_id','LEFT');
							$sql_workshift = $this->db->get('employee_shifts_schedule AS ess');
							
							if($sql_workshift->num_rows() > 0){
								$row_workshift = $sql_workshift->row();
								$sql_workshift->free_result();
								return $row_workshift->start_time;
							}else{
								return "00:00:00";
							}
						}
					}
				}
			}else{
				return "00:00:00";
			}
			
		}
		
		/**
		 * Get Employee Work End Time
		 * @param unknown_type $emp_id
		 * @param unknown_type $day
		 */
		public function undertime_min($emp_id,$day){
			// rest day
			$rest_day = $this->db->query("
				SELECT *FROM employee_shifts_schedule ess
				LEFT JOIN rest_day rd ON rd.payroll_group_id = ess.payroll_group_id
				WHERE ess.emp_id = '{$emp_id}'
				AND rd.rest_day = '{$day}'
			");
			
			if($rest_day->num_rows() == 0){
				// regular schedules
				$sql_regular_schedules = $this->db->query("
					SELECT *FROM employee_shifts_schedule ess
					LEFT JOIN regular_schedule rs ON rs.payroll_group_id = ess.payroll_group_id
					WHERE ess.emp_id = '{$emp_id}'
				");
				
				if($sql_regular_schedules->num_rows() > 0){
					$row_regular_schedules = $sql_regular_schedules->row();
					$sql_regular_schedules->free_result();
					return $row_regular_schedules->work_end_time;
				}else{
					// flexible working days
					$sql_flexible_days = $this->db->query("
						SELECT *FROM employee_shifts_schedule ess
						LEFT JOIN flexible_hours fh ON fh.payroll_group_id = ess.payroll_group_id
						WHERE ess.emp_id = '{$emp_id}'
					");
					
					if($sql_flexible_days->num_rows() > 0){
						
						$flexible_compute_time = $this->db->query("
							SELECT * FROM `employee_time_in` WHERE emp_id = '{$emp_id}'
							ORDER BY date DESC
							LIMIT 1
						");
						
						if($flexible_compute_time->num_rows() > 0){
							$row_flexible_compute_time = $flexible_compute_time->row();
							$flexible_compute_time->free_result();
							$time_in = explode(" ", $row_flexible_compute_time->time_in);;
							$flexible_work_end = $time_in[1];
							
							// flexible total hours per day
							$sql_flexible_days_of_works = $this->db->query("
								SELECT *FROM employee_shifts_schedule ess
								LEFT JOIN flexible_hours fh ON fh.payroll_group_id = ess.payroll_group_id
								WHERE ess.emp_id = '{$emp_id}'
							");
							
							if($sql_flexible_days_of_works->num_rows() > 0){
								$row_flexible_days_of_works = $sql_flexible_days_of_works->row();
								$sql_flexible_days_of_works->free_result();
								$row_flexible_days_of_works->total_hours_for_the_day;
							}
							
							//$end_time = date("H:i:s",strtotime($flexible_work_end) + 60 * 60 * 9); // 9 = company worked hours
							$end_time = date("H:i:s",strtotime($flexible_work_end) + 60 * 60 * $row_flexible_days_of_works);
							return $end_time;
						}else{
							return "00:00:00";
						}
						
					}else{
						// workshift working days
// 						$sql_workshift = $this->db->query("
// 							SELECT *FROM employee_shifts_schedule ess
// 							LEFT JOIN workshift w ON w.payroll_group_id = ess.payroll_group_id
// 							WHERE ess.emp_id = '{$emp_id}'
// 						");
						$where = array(
								'ess.emp_id'=>$emp_id
									
						);
						$this->db->where($where);
						$this->db->join('split_schedule AS sc','ess.payroll_group_id = sc.payroll_group_id','LEFT');
						$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = sc.split_schedule_id','LEFT');
						$sql_workshift = $this->db->get('employee_shifts_schedule AS ess');
						
						if($sql_workshift->num_rows() > 0){
							$row_workshift = $sql_workshift->row();
							$sql_workshift->free_result();
							return $row_workshift->end_time;
						}else{
							return "00:00:00";
						}
					}
				}
			}else{
				return "00:00:00";
			}
		}
		
		/**
		 * Get Total Hours Value
		 * @param unknown_type $emp_id
		 * @param unknown_type $week_day
		 */
		public function total_hours_value($emp_id,$week_day){
			
			$where = array(
				"emp_id" => $emp_id
			);
			$this->db->where($where);
			$sql_payroll_info = $this->db->get("employee_payroll_information");
			$row_payroll_info = $sql_payroll_info->row();

			$payroll_group_id = $row_payroll_info->payroll_group_id;
			$company_id = $row_payroll_info->company_id;
			
			// rest day
			$rest_day = $this->db->query("
				SELECT *FROM employee_shifts_schedule ess
				LEFT JOIN rest_day rd ON rd.payroll_group_id = ess.payroll_group_id
				WHERE ess.emp_id = '{$emp_id}'
				AND rd.rest_day = '{$week_day}'
				AND rd.payroll_group_id = '{$payroll_group_id}'
			");
			
			if($rest_day->num_rows() == 0){
				// regular schedules
				$sql_regular_schedules = $this->db->query("
					SELECT *FROM employee_shifts_schedule ess
					LEFT JOIN regular_schedule rs ON rs.payroll_group_id = ess.payroll_group_id
					WHERE ess.emp_id = '{$emp_id}'
					AND rs.days_of_work = '{$week_day}'
					AND rs.payroll_group_id = '{$payroll_group_id}'
					AND rs.company_id = '{$company_id}'
				");
				
				if($sql_regular_schedules->num_rows() > 0){
					$row_regular_schedules = $sql_regular_schedules->row();
					$sql_regular_schedules->free_result();
					return $row_regular_schedules->total_work_hours;
				}else{
					// flexible working days
					$sql_flexible_days_of_works = $this->db->query("
						SELECT *FROM employee_shifts_schedule ess
						LEFT JOIN flexible_hours fh ON fh.payroll_group_id = ess.payroll_group_id
						WHERE ess.emp_id = '{$emp_id}'
						AND fh.payroll_group_id = '{$payroll_group_id}'
						AND fh.company_id = '{$company_id}'
					");
					
					if($sql_flexible_days_of_works->num_rows() > 0){
						$row_flexible_days_of_works = $sql_flexible_days_of_works->row();
						$sql_flexible_days_of_works->free_result();
						return $row_flexible_days_of_works->total_hours_for_the_day;
					}else{
						// workshift working days
						$sql_workshift_days_of_works = $this->db->query("
							SELECT *FROM employee_shifts_schedule ess
							LEFT JOIN split_schedule w ON w.payroll_group_id = ess.payroll_group_id
							WHERE ess.emp_id = '{$emp_id}'
							AND w.payroll_group_id = '$pay'
							AND w.company_id = '{$company_id}'
						");
						
						if($sql_workshift_days_of_works->num_rows() > 0){
							$row_workshift_days_of_works = $sql_workshift_days_of_works->row();
							$sql_workshift_days_of_works->free_result();
							return $row_workshift_days_of_works->total_work_hours;
						}else{
							return 0;
						}
					}
				}
			}else{
				return 0;
			}
		}
		
		/**
		 * Get Holiday Date Information
		 * @param unknown_type $date_start
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 */
		public function get_holiday_date($date_start,$emp_id,$comp_id){
			$date_start = date("Y-m-d",strtotime($date_start));
		    $date_start_d = date("d",strtotime($date_start));
		    $date_start_m = date("m",strtotime($date_start));
			
			$w = array(
				'company_id'=> $comp_id,
			    'MONTH(date)'=> $date_start_m,
			    'DAY(date)'=> $date_start_d,
				'status' => 'Active'
			);
			$this->db->where($w);
			$q = $this->db->get('holiday');
			$r = $q ->row();
			#p($r);
			if($r) {
			    if($r->date_type == "fixed") {
			        #return TRUE;
			        $proceed = true;
			    } else {
			        if($date_start == $r->date) {
			            #return TRUE;
			            $proceed = true;
			        } else {
			            #return FALSE;
			            $proceed = false;
			        }
			    }
			    
			    // new : for local holidays
			    if($proceed) {
			        $s = array(
			            'location_and_offices_id'
			        );
			        
			        $w1 = array(
			            'company_id'=> $comp_id,
			            'emp_id'=> $emp_id,
			            'status' => 'Active'
			        );
			        
			        $this->db->select($s);
			        $this->db->where($w1);
			        $q1 = $this->db->get('employee_payroll_information');
			        $r1 = $q1->row();
			       
			        $proceed1 = false;
			        if($r1) {
			            if($r1->location_and_offices_id != 0 || $r1->location_and_offices_id != null) {
			                if($r->locations != "" || $r->locations != null) {
			                    $x = explode(",", $r->locations);
			                    foreach ($x as $loc) {
			                        if($loc == $r1->location_and_offices_id) {
			                            $proceed1 = true;
			                        }
			                    }
			                }
			            }
			        }
			        
			        if($r->scope == "local" && !$proceed1) {
			            return FALSE;
			        } else {
			            return TRUE;
			        }
			        
			    } else {
			        return FALSE;
			    }
			} else {
			    return FALSE;
			}
		}
		
		/**
		 * Get Return Date Value
		 * @param unknown_type $emp_id
		 * @param unknown_type $date
		 */
		public function get_return_date_val($comp_id,$emp_id,$date){
			// rest day
			$rest_day = $this->db->query("
				SELECT *FROM employee_shifts_schedule ess
				LEFT JOIN rest_day rd ON rd.payroll_group_id = ess.payroll_group_id
				WHERE ess.emp_id = '{$emp_id}'
				AND rd.rest_day = '".date('l',strtotime($date))."'
			");
			
			if($rest_day->num_rows() == 0){
				// regular schedules
				$sql_regular_schedules = $this->db->query("
					SELECT *FROM employee_shifts_schedule ess
					LEFT JOIN regular_schedule rs ON rs.payroll_group_id = ess.payroll_group_id
					WHERE ess.emp_id = '{$emp_id}'
					AND rs.days_of_work = '".date('l',strtotime($date))."'
				");
											
				if($sql_regular_schedules->num_rows() > 0){
					$row_regular_schedules = $sql_regular_schedules->row();
					$sql_regular_schedules->free_result();
					
					// PAYROLL INFORMATION
					$where = array(
						"emp_id" => $emp_id,
						"company_id" => $comp_id
					);
					
					$this->db->where($where);
					$sql_payroll_info = $this->db->get("employee_payroll_information");
					$row_payroll_info = $sql_payroll_info->row();
					$emp_payroll_group_id = $row_payroll_info->payroll_group_id;
					
					// REGULALR SCHEDULES
					$where_uniform_settings = array(
						"company_id" => $comp_id,
						"payroll_group_id" => $emp_payroll_group_id
					);
					$this->db->where($where_uniform_settings);
					$sql_regular_schedules_where = $this->db->get("regular_schedule");
					if($sql_regular_schedules_where->num_rows() > 0){
						$row_regular_schedules_where = $sql_regular_schedules_where->row();
						$latest_time_in_allowed = $row_regular_schedules_where->latest_time_in_allowed;
						#$uniform_return_date_val = (strtotime(date('H:i:s',strtotime($date))) - strtotime($latest_time_in_allowed)) / 3600;
						$uniform_return_date_val = (strtotime(date('H:i:s',strtotime($date))) - strtotime($row_regular_schedules->work_start_time)) / 3600;
					}else{
						$uniform_return_date_val = (strtotime(date('H:i:s',strtotime($date))) - strtotime($row_regular_schedules->work_start_time)) / 3600;
					}
					
					if($uniform_return_date_val<0){
						if(abs($uniform_return_date_val) >= 12){
							return (24-(abs($uniform_return_date_val))) / 8;
						}else{
							return 0;
						}
					}else{
						return $uniform_return_date_val / 8;
					}
				}else{
					// flexible working days
					$sql_flexible_days_of_works = $this->db->query("
						SELECT *FROM employee_shifts_schedule ess
						LEFT JOIN flexible_hours fh ON fh.payroll_group_id = ess.payroll_group_id
						WHERE ess.emp_id = '{$emp_id}'
					");
					
					if($sql_flexible_days_of_works->num_rows() > 0){
						$row_flexible_days_of_works = $sql_flexible_days_of_works->row();
						$sql_flexible_days_of_works->free_result();
						$flexible_return_date_val = (strtotime(date('H:i:s',strtotime($date))) - strtotime($row_flexible_days_of_works->latest_time_in_allowed)) / 3600;
						if($flexible_return_date_val<0){
							if(abs($flexible_return_date_val) >= 12){
								return (24-(abs($flexible_return_date_val))) / 8;
							}else{
								return 0;
							}
						}else{
							return $flexible_return_date_val / 8;
						}
					}else{
						// workshift working days
						$sql_workshift_days_of_works = $this->db->query("
							SELECT *FROM employee_shifts_schedule ess
							LEFT JOIN split_schedule w ON w.payroll_group_id = ess.payroll_group_id
							WHERE ess.emp_id = '{$emp_id}'
						");
						
						if($sql_workshift_days_of_works->num_rows() > 0){
							$row_workshift_days_of_works = $sql_workshift_days_of_works->row();
							$sql_workshift_days_of_works->free_result();
							
							// check workshift grace period
							$where_grace_p = array(
								"payroll_group_id"=>$emp_payroll_group_id,
								"company_id"=>$comp_id
							);
							$this->db->where($where_grace_p);
							$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
							$sql_grace_p = $this->db->get("split_schedule");
							$row_grace_p = $sql_grace_p->row();
							
							if($sql_grace_p->num_rows() > 0){
								$grace_period_val = strtotime($row_workshift_days_of_works->start_time) + ($row_grace_p->grace_period_for_every_shift * 60);
							}else{
								$grace_period_val = strtotime($row_workshift_days_of_works->start_time);
							}
							
							#$workshift_return_date_val = (strtotime(date('H:i:s',strtotime($date))) - $grace_period_val) / 3600;
							$workshift_return_date_val = (strtotime(date('H:i:s',strtotime($date))) - strtotime($row_workshift_days_of_works->start_time)) / 3600;
							if($workshift_return_date_val<0){
								if(abs($workshift_return_date_val) >= 12){
									return (24-(abs($workshift_return_date_val))) / 8;
								}else{
									return 0;
								}
							}else{
								return $workshift_return_date_val / 8;
							}
						}else{
							return 0;
						}
					}
				}
			}else{
				return 0;
			}
		}
		
		/**
		 * Get Hours Worked for workday
		 * @param unknown_type $workday
		 * @param unknown_type $emp_id
		 */
		public function get_hours_worked($workday, $emp_id){
			$CI =& get_instance();
			
			$mininum_wage_rate = "";
			$working_hours = "";
			$no_of_days = "";
			$regular_hourly_rate = "";
			
			// check if workday is workshit schedule
			$sql_workshift = $this->db->query("
				SELECT *
				FROM `employee_time_in` eti
				LEFT JOIN split_schedule ws ON eti.comp_id = ws.company_id
				WHERE eti.emp_id = '{$emp_id}'
				GROUP BY ws.total_working_days_per_year
				
			");
			
			$row_workshift = $sql_workshift->row();
			if($sql_workshift->num_rows() > 0){
				$sql_workshift->free_result();
				
				if($row_workshift->total_working_days_per_year != null || $row_workshift->total_work_hours != ""){
					// for workshift
					$no_of_days = $row_workshift->total_working_days_per_year / 12; // 12 = months
					$working_hours = $row_workshift->total_work_hours;
				}
			}
			
			// ==========================================
			
			// check if workday is flexible hours
			$sql_flexible_hours = $this->db->query("
				SELECT *
				FROM `employee_time_in` eti
				LEFT JOIN flexible_hours fh ON eti.comp_id = fh.company_id
				WHERE eti.emp_id = '{$emp_id}'
				GROUP BY fh.total_days_per_year
			");
			
			$row_flexible_hours = $sql_flexible_hours->row();
			if($sql_flexible_hours->num_rows() > 0){
				$sql_flexible_hours->free_result();
				
				if($row_flexible_hours->total_days_per_year != null){
					// for flexible hours
					$no_of_days = $row_flexible_hours->total_days_per_year / 12; // 12 = months
					$working_hours = $row_flexible_hours->total_hours_for_the_day;
				}
			}
			
			if($working_hours != "" && $no_of_days != ""){
				// get mininum wage rate for employee
				$w = array(
					"emp_id"=>$emp_id,
					"status"=>"Active"
				);
				$this->edb->where($w);
				$sql_minimum_wage_rate = $this->edb->get("basic_pay_adjustment");
				
				$row_minimum_wage_rate = $sql_minimum_wage_rate->row();
				if($sql_minimum_wage_rate->num_rows() > 0){
					$sql_minimum_wage_rate->free_result();
					$effective_date = strtotime(date("Y-m-d",strtotime($row_minimum_wage_rate->effective_date)));
					$current_date = strtotime(date("Y-m-d"));

					if($row_minimum_wage_rate->new_basic_pay == NULL || $row_minimum_wage_rate->effective_date == NULL || $row_minimum_wage_rate->adjustment_date == NULL){
						$current_basic_pay = $row_minimum_wage_rate->current_basic_pay;
					}else{
						if($effective_date > $current_date){
							$current_basic_pay = $row_minimum_wage_rate->current_basic_pay;
						}else{
							$current_basic_pay = $row_minimum_wage_rate->new_basic_pay;
						}
					}
					
					$mininum_wage_rate = $current_basic_pay / $no_of_days;
				}else{
					$current_basic_pay = 0;
					$mininum_wage_rate = 0;
				}
				
				// get regular hours type
			
				$regular_hourly_rate = number_format($mininum_wage_rate, 2) / number_format($working_hours);
			}
			
			$workday_val = date("l",strtotime($workday));
						
			// check if workday is regular schedules, get the total working days per year
// 			$sql_regular_schedules = $this->db->query("
// 				SELECT *
// 				FROM `employee_time_in` eti
// 				LEFT JOIN regular_schedule ON eti.comp_id = uwds.company_id
// 				LEFT JOIN regular_schedule uwd ON uwds.company_id = uwd.company_id
// 				WHERE eti.emp_id = '{$emp_id}'
// 				AND uwd.working_day = '{$workday_val}'
// 				GROUP BY uwd.working_day
// 			");
			
			$sql_regular_schedules = $this->db->query("
					SELECT *
					FROM `employee_time_in` eti
					LEFT JOIN regular_schedule ON eti.comp_id = rs.company_id
					WHERE eti.emp_id = '{$emp_id}'
					AND rs.days_of_work = '{$workday_val}'
					GROUP BY rs.days_of_work
			");
			
			// get number of days
			$row_regular_schedules = $sql_regular_schedules->row();
			if($sql_regular_schedules->num_rows() > 0){
				$sql_regular_schedules->free_result();
				
				if($row_regular_schedules->total_working_days_per_year != null || $row_regular_schedules->total_work_hours != ""){
					// for regular schedules
					$no_of_days = $row_regular_schedules->total_working_days_per_year / 12; // 12 = months
					$working_hours = $row_regular_schedules->total_work_hours;
				}
			}
			
			return $working_hours;
		}
		
		/**
		 * Get Workday Timeout Value
		 * @param unknown_type $hours_worked
		 * @param unknown_type $comp_id
		 */
		public function work_day_timeout($hours_worked,$comp_id){
			$sql = $this->db->query("
				SELECT *
				FROM `regular_schedule`
				WHERE days_of_work = '{$hours_worked}'
				AND company_id = '{$comp_id}'
			");
			$row = $sql->row();
			if($sql->num_rows() > 0){
				return $row->work_end_time;
			}else{
				return false;
			}
		}
		
		/**
		 * Payroll Run Details
		 * @param unknown_type $comp_id
		 */
		public function payroll_run_details($emp_id,$comp_id){
			$where = array(
				"emp_id"=>$emp_id,
				"company_id"=>$comp_id
			);
			$this->edb->where($where);
			$this->db->order_by('payroll_date','DESC');
			$sql = $this->edb->get("payroll_payslip");
			
			return ($sql->num_rows() > 0) ? $sql->result() : false;
		}
		 
		
		public function get_data_payslip($company_id,$emp_id,$limit = 10,$start = 0)
		{
			$where = array(
				"e.company_id" => $company_id,
				"e.status" => 'Active',
				"a.deleted" => '0',
				"pp.emp_id" => $emp_id,
			    "pp.flag_prev_emp_income" => '0',
			    "pp.status" => 'Active'
		
		
			);
			$query = $this->edb->where($where);
			$this->edb->join('employee AS e','e.account_id=a.account_id','inner');
			$this->edb->join('payroll_payslip AS pp','e.emp_id=pp.emp_id','inner');
			$this->db->order_by("payroll_date","desc");
			$query = $this->edb->get('accounts AS a',$limit,$start);
			$result = $query->result();
			return $result;
		
		}
		
		public function get_data_payslip_count($company_id,$emp_id)
		{
			$where = array(
				"e.company_id" => $company_id,
				"e.status" => 'Active',
				"a.deleted" => '0',
				"pp.emp_id" => $emp_id,
				"pp.flag_prev_emp_income" => '0',
			    "pp.status" => 'Active'
		
		
			);
			$query = $this->edb->where($where);
			$this->edb->join('employee AS e','e.account_id=a.account_id','inner');
			$this->edb->join('payroll_payslip AS pp','e.emp_id=pp.emp_id','inner');
			$this->db->order_by("payroll_date","desc");
			$query = $this->edb->get('accounts AS a');
			$result = $query->result();
			return $query->num_rows;
		
		}
		public function get_data_pay_open_bal($company_id,$emp_id){
			$where = array(
					"e.company_id" => $company_id,
					"e.status" => 'Active',
					"a.deleted" => '0',
					"pp.emp_id" => $emp_id,
					"pp.flag_prev_emp_income !=" => '0'
			);
			$query = $this->edb->where($where);
			$this->edb->join('employee AS e','e.account_id=a.account_id','inner');
			$this->edb->join('payroll_payslip AS pp','e.emp_id=pp.emp_id','inner');
			$this->db->order_by('pp.payroll_date','DESC');
			$query = $this->edb->get('accounts AS a');
			$result = $query->result();
			return $result;
		}
		
		/**
		 * Payroll Run Details Where
		 * @param unknown_type $comp_id
		 */
		public function payroll_run_details_where($emp_id,$comp_id,$id){
			$where = array(
				"payroll_payslip_id"=>$id,
				"emp_id"=>$emp_id,
				"company_id"=>$comp_id
			);
			$this->edb->where($where);
			$sql = $this->edb->get("payroll_payslip");
			
			return ($sql->num_rows() > 0) ? $sql->row() : false;
		}

		public function generate_printable_payslip($payroll_payslip_id,$payroll_date,$period_from,$period_to,$company_id){
			$w = array(
				"pp.payroll_payslip_id"=>$payroll_payslip_id,
				"e.company_id"=>$company_id,
				"e.status"=>"Active",
				"e.deleted"=>"0",
			);
			
			$sel = array(
				"lao.name AS lao_name"
			);
			$this->edb->select("*");
			$this->db->select($sel);

			$this->db->where($w);
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER");
			$this->edb->join("position AS pst","epi.position = pst.position_id","LEFT");
			$this->edb->join("department AS dpt","epi.department_id = dpt.dept_id","LEFT");
			$this->edb->join("location_and_offices AS lao","epi.location_and_offices_id = lao.location_and_offices_id","LEFT");
			$this->edb->join("payroll_group AS pg","epi.payroll_group_id = pg.payroll_group_id","INNER");
			$this->edb->join("employee AS e","epi.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("company AS cp","epi.company_id = cp.company_id","INNER");
			$q = $this->edb->get("payroll_payslip AS pp");
			$r = $q->result();
			return ($r) ? $r : FALSE ;
		}
		
		/**
		 * Get No of hours for breaktime
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $date
		 * @param unknown_type $workday
		 */
		public function get_breaktime($comp_id,$emp_id,$workday,$start_time_btime){
			$where_pgroup_id = array(
				"emp_id" => $emp_id
			);
			$sql_ppgroup = $this->db->get("employee_payroll_information",$where_pgroup_id);
			$row_ppgroup = $sql_ppgroup->row();
			if($sql_ppgroup->num_rows() > 0) $payroll_group_id = $row_ppgroup->payroll_group_id;
			
			// FOR regular schedules
			$where_break_time = array(
				"payroll_group_id"=>$payroll_group_id,
				"break_time_number"=>"1",
				"workday"=>$workday,
				"company_id"=>$comp_id
			);
			$sql_breaktime = $this->db->get("break_time",$where_break_time);
			$row_breaktime = $sql_breaktime->row();
			
			if($sql_breaktime->num_rows() > 0){
				$total_breaktime = 0;
				$b_start_time = $row_breaktime->start_time;
				$b_end_time = $row_breaktime->end_time;
				
				// CHECK IS START TIME IS LESS THAN START TIME FOR BREAK TIME
				if(strtotime($start_time_btime) < strtotime($b_end_time)){
					$total_breaktime = (strtotime($b_end_time) - strtotime($b_start_time)) / 3600;
				}
				
			}
			
			// FOR WORKSHIFT BREAK TIME
			$where_break_workshift = array(
				"payroll_group_id"=>$payroll_group_id,
				"company_id"=>$comp_id
			);
			$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
			$sql_break_workshift = $this->db->get("split_schedule",$where_break_workshift);
			$row_break_workshift = $sql_break_workshift->row();
			
			if($sql_break_workshift->num_rows() > 0){
				$total_breaktime = 0;
				$b_start_time = $row_break_workshift->start_time;
				$b_end_time = $row_break_workshift->end_time;
				
				// CHECK IS START TIME IS LESS THAN START TIME FOR BREAK TIME
				if(strtotime($start_time_btime) < strtotime($b_end_time)){
					$total_breaktime = (strtotime($b_end_time) - strtotime($b_start_time)) / 3600;
				}
			}
			
			return $total_breaktime;
		}
		
		/**
		 * Get End date for breaktime
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $workday
		 * @param unknown_type $start_time_btime
		 */
		public function get_end_date_breaktime($comp_id,$emp_id,$workday,$end_time_btime){
			$where_pgroup_id = array(
				"emp_id" => $emp_id
			);
			$sql_ppgroup = $this->db->get("employee_payroll_information",$where_pgroup_id);
			$row_ppgroup = $sql_ppgroup->row();
			if($sql_ppgroup->num_rows() > 0) $payroll_group_id = $row_ppgroup->payroll_group_id;
			
			$where_break_time = array(
				"payroll_group_id"=>$payroll_group_id,
				"break_time_number"=>"1",
				"workday"=>$workday,
				"company_id"=>$comp_id
			);
			$sql_breaktime = $this->db->get("break_time",$where_break_time);
			$row_breaktime = $sql_breaktime->row();
			
			$total_breaktime = 0;
			
			// FOR regular schedules 
			if($sql_breaktime->num_rows() > 0){
				$b_start_time = $row_breaktime->start_time;
				$b_end_time = $row_breaktime->end_time;
				
				// CHECK IF END TIME IS LESS THAN END TIME FOR BREAK TIME
				if(strtotime($end_time_btime) >= strtotime($b_end_time)){
					$total_breaktime = (strtotime($b_end_time) - strtotime($b_start_time)) / 3600;
				}
				
			}
			
			// FOR WORKSHIFT BREAK TIME
			$where_break_workshift = array(
				"payroll_group_id"=>$payroll_group_id,
				"company_id"=>$comp_id
			);
			$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
			$sql_break_workshift = $this->db->get("split_schedule",$where_break_workshift);
			$row_break_workshift = $sql_break_workshift->row();
			
			if($sql_break_workshift->num_rows() > 0){
				$total_breaktime = 0;
				$b_start_time = $row_break_workshift->start_time;
				$b_end_time = $row_break_workshift->end_time;
				
				// CHECK IF END TIME IS LESS THAN END TIME FOR BREAK TIME
				if(strtotime($end_time_btime) >= strtotime($b_end_time)){
					$total_breaktime = (strtotime($b_end_time) - strtotime($b_start_time)) / 3600;
				}
			}
			
			return $total_breaktime;
		}
		
		/**
		 * Get Other Deductions
		 * @param unknown_type $comp_id
		 */
		public function other_deductions($comp_id){
			$where = array(
				'comp_id' => $comp_id,
				'status' => 'Active'
			);
			
			$sql = $this->db->get_where("deductions_other_deductions",$where);
			return ($sql->num_rows() > 0) ? $sql->result() : false ;
		}
		
		/**
		 * Get Other Deduction Where Deduction Other deduction ID
		 * @param unknown_type $deduction_other_dd_id
		 */
		public function get_other_dd_where($deduction_other_dd_id){
			$payroll = $this->hwm->get_payroll_period($this->company_id);
			
			// get other deductions
		
			$where = array(
				'company_id'  	 => $this->company_id,
				'employee_id' 	 => $this->emp_id,
				'payroll_period' => $payroll->payroll_period,
				'deductions_other_deductions_id' => $deduction_other_dd_id
			);
			
			$cnt_other_deductions = 0;
			$get_employee_other_deduction_total = $this->payroll_mdl->get_employee_other_deduction_total($where);
			if($get_employee_other_deduction_total){
				foreach($get_employee_other_deduction_total as $row_get_employee_other_deduction_total){
					$q1_od = $this->ldm->get_payroll_calendar($this->company_id,$payroll->payroll_period);
					$q2_od = $this->payroll_mdl->get_other_deductions($this->company_id,$payroll->payroll_group_id,$row_get_employee_other_deduction_total->deductions_other_deductions_id);
					if ($q2_od) {
						if($q2_od->priority == 'every payroll'){
							$cnt_other_deductions = $row_get_employee_other_deduction_total->amount;
						}elseif ($q2_od->priority == 'first payroll of the month' && $q1_od['period'] == 1) {
							$cnt_other_deductions = $row_get_employee_other_deduction_total->amount; 
						} elseif (($q2_od->priority == 'last payroll of the month') &&
							$q1['period'] == 2) {
							$cnt_other_deductions = $row_get_employee_other_deduction_total->amount;
						}
					}									
				}
			}
			
			return $cnt_other_deductions;
			
			// end other deductions
		}
		
		/**
		 * Check Leave Prior
		 * @param unknown_type $comp_id
		 * @param unknown_type $leave_type_id
		 */
		public function check_leave_prior($comp_id,$leave_type_id){
			$where = array(
				"company_id" => $comp_id,
				"leave_type_id" => $leave_type_id
			);
			$this->db->where($where);
			$sql = $this->db->get("leave_type");
			return ($sql->num_rows() > 0) ? $sql->row() : false ;
		}
		
		/**
		 * Check Workday
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 */
		public function check_workday($emp_id,$comp_id){
			$where = array(
				"emp_id"=>$emp_id,
				"company_id"=>$comp_id
			);
			
			$this->db->where($where);
			$sql = $this->db->get("employee_payroll_information");
			$row_pi = $sql->row();
			$payroll_group_id = $row_pi->payroll_group_id;
			
			$where_fh = array(
				"payroll_group_id"=>$payroll_group_id,
				"company_id"=>$comp_id
			);
			$this->db->where($where_fh);
			$sql_fl = $this->db->get("flexible_hours");
			return ($sql_fl->num_rows() > 0) ? $sql_fl->row() : FALSE ;
		}
		
		/**
		 * Compute Total Hours
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $employee_time_in_id
		 */
		public function compute_total_hours($comp_id, $emp_id, $employee_time_in_id, $hours_worked){
			$where = array(
				"comp_id"=>$comp_id,
				"emp_id"=>$emp_id,
				"employee_time_in_id"=>$employee_time_in_id
			);
			$this->db->where($where);
			$sql = $this->db->get("employee_time_in");
			$row = $sql->row();
			$time_in = $row->time_in;
			$lunch_out = $row->lunch_out;
			$lunch_in = $row->lunch_in;
			$time_out = $row->time_out;
			
			if($time_in != NULL && $lunch_out != NULL && $lunch_in != NULL && $time_out != NULL){
				// tardiness
				$tardiness = get_tardiness_import($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in);
				
				// undertime
				$undertime = get_undertime_import($emp_id, $comp_id, $time_in, $time_out, $lunch_out, $lunch_in);
				
				// total hours worked
				$total_hours_worked = get_tot_hours_limit($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out);
				
				// total hours worked view
				$total_hours_worked_view = get_tot_hours($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out, $hours_worked);
				
				$where_tot = array(
					"comp_id"=>$comp_id,
					"emp_id"=>$emp_id,
					"employee_time_in_id"=>$employee_time_in_id
				);
				$this->db->where($where_tot);
	
				$data = array(
					"tardiness_min"=>$tardiness,
					"undertime_min"=>$undertime,
					"total_hours_required"=>$total_hours_worked,
					"total_hours"=>$total_hours_worked_view
				);
				$this->db->update('employee_time_in', $data);
			}
			
			return TRUE;
		}
		
		/**
		 * Check Payslip ID
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $id
		 */
		public function check_payslip_id($emp_id,$comp_id,$id){
			
			/* $w = array(
				"payroll_payslip_id"=>$id,
				"emp_id"=>$emp_id,
				"company_id"=>$comp_id
			);
			$this->edb->where($w);
			$sql = $this->edb->get("payroll_payslip");
			return ($sql->num_rows() == 0) ? TRUE : FALSE ; */
			
			$w = array(
				"payroll_payslip_id"=>$id,
				"emp_id"=>$emp_id,
				"company_id"=>$comp_id
			);
			$this->edb->where($w);
			$sql = $this->edb->get("payroll_payslip");
			$r = $sql->row();
			
			if($r){
				
				// custom
				$payroll_period = $r->payroll_date;
				$period_from = $r->period_from;
				$period_to = $r->period_to;
				
				$w = array(
					"dpr.company_id"=>$comp_id,
					"prc.emp_id"=>$emp_id,
					"dpr.pay_period"=>$payroll_period,
					"dpr.period_from"=>$period_from,
					"dpr.period_to"=>$period_to,
					"dpr.view_status"=>"Closed",
					"dpr.status"=>"Active"
				);
				$this->db->where($w);
				$this->db->join("payroll_run_custom AS prc","prc.draft_pay_run_id = dpr.draft_pay_run_id","LEFT");
				$q = $this->db->get("draft_pay_runs AS dpr");
				$r2 = $q->row();
				
				if($r2){
					// redirect("/{$this->uri->segment(1)}/employee/emp_payroll_history","refresh");
					return FALSE;
				}else{
					
					// by payroll group
					$w = array(
						"dpr.company_id"=>$comp_id,
						"dpr.payroll_group_id"=>$r->payroll_group_id,
						"dpr.pay_period"=>$payroll_period,
						"dpr.period_from"=>$period_from,
						"dpr.period_to"=>$period_to,
						"dpr.view_status"=>"Closed",
						"dpr.status"=>"Active"
					);
					$this->db->where($w);
					$q = $this->db->get("draft_pay_runs AS dpr");
					$r = $q->row();
					
					if($r){
						return FALSE;
					}else{
						redirect("/{$this->uri->segment(1)}/employee/emp_payroll_history","refresh");
					}
					
				}
				
			}else{
				return TRUE;
			}
			
			
		}
		
		/**
		 * Employee Information
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 */
		public function emp_info($emp_id,$comp_id){
			
			$select = array(
				'accounts.payroll_cloud_id',
				'employee.first_name',
				'employee.last_name'
			);
			$where = array(
				'employee.emp_id' => $emp_id,
				'employee.company_id' => $comp_id,
				'employee.status'	  => 'Active'
			);
			$this->edb->select($select);
			$this->edb->where($where);
			$this->edb->join('accounts','employee.account_id = accounts.account_id','left');
			$sql = $this->edb->get('employee');
			return ($sql->num_rows() > 0) ? $sql->row() : FALSE ;
		}
		
		/**
		 * Get Employee Fullname
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 */
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
		
		/**
		 * Leave Type Name
		 * @param unknown_type $leave_type
		 * @param unknown_type $comp_id
		 */
		public function leave_type_name($leave_type,$comp_id){
			$w = array(
				"leave_type_id"=>$leave_type,
				"company_id"=>$comp_id
			);
			$this->db->where($w);
			$q = $this->db->get("leave_type");
			$row = $q->row();
			return ($q->num_rows() > 0) ? ucwords($row->leave_type) : FALSE ;
		}
		
		/**
		 * Get Approver Name
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 */
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
		
		/**
		 * Update Employee Time In Logs
		 * @param unknown_type $emp_id
		 * @param unknown_type $employee_time_in_id
		 */
		public function update_change_logs($comp_id, $emp_id, $employee_time_in_id, $reason, $time_in,$lunch_out,$lunch_in,$time_out, $hours_worked){			
			
			if($time_in != NULL){
				// tardiness
				$tardiness = get_tardiness_import($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in);
				
				// undertime
				$undertime = get_undertime_import($emp_id, $comp_id, $time_in, $time_out, $lunch_out, $lunch_in);
				
				// total hours worked
				$total_hours_worked = get_tot_hours_limit($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out);
				
				// total hours worked view
				$total_hours_worked_view = get_tot_hours($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out, $hours_worked);
				
				$where_tot = array(
					"comp_id"=>$comp_id,
					"emp_id"=>$emp_id,
					"employee_time_in_id"=>$employee_time_in_id
				);
				$this->db->where($where_tot);
	
				$data = array(
					"time_in_status"=>'pending', 
					"corrected"=>'Yes',
					"reason"=>$reason,
					"change_log_date_filed"=>date("Y-m-d H:i:s"),
					"change_log_time_in"=>$time_in,
					"change_log_lunch_out"=>$lunch_out,
					"change_log_lunch_in"=>$lunch_in,
					"change_log_time_out"=>$time_out,
					"change_log_tardiness_min"=>$tardiness,
					"change_log_undertime_min"=>$undertime,
					"change_log_total_hours_required"=>$total_hours_worked,
					"change_log_total_hours"=>$total_hours_worked_view
				);
				$this->db->update('employee_time_in', $data);
			}
			
			return TRUE;
		}
		
		/**
		 * Add Employee Time In Logs
		 * @param unknown_type $emp_id
		 */
		public function add_logs($comp_id, $emp_id, $reason, $time_in,$lunch_out,$lunch_in,$time_out, $hours_worked){			
			
			if($time_in != NULL){
				// tardiness
				$tardiness = get_tardiness_import($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in);
				
				// undertime
				$undertime = get_undertime_import($emp_id, $comp_id, $time_in, $time_out, $lunch_out, $lunch_in);
				
				// total hours worked
				$total_hours_worked = get_tot_hours_limit($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out);
				
				// total hours worked view
				$total_hours_worked_view = get_tot_hours($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out, $hours_worked);
	
				$date_insert = array(
					"comp_id"=>$comp_id,
					"emp_id"=>$emp_id,
					"date"=>date("Y-m-d",strtotime($time_in)),
					"time_in_status"=>'pending',
					"corrected"=>'Yes',
					"reason"=>$reason,
					"time_in"=>$time_in,
					"lunch_out"=>$lunch_out,
					"lunch_in"=>$lunch_in,
					"time_out"=>$time_out,
					"tardiness_min"=>$tardiness,
					"undertime_min"=>$undertime,
					"total_hours"=>0,
					"total_hours_required"=>$total_hours_worked,
					"change_log_date_filed"=>date("Y-m-d H:i:s"),
					"change_log_time_in"=>$time_in,
					"change_log_lunch_out"=>$lunch_out,
					"change_log_lunch_in"=>$lunch_in,
					"change_log_time_out"=>$time_out,
					"change_log_tardiness_min"=>$tardiness,
					"change_log_undertime_min"=>$undertime,
					"change_log_total_hours_required"=>$total_hours_worked,
					"change_log_total_hours"=>$total_hours_worked_view
				
				);
				$this->db->insert('employee_time_in', $date_insert);
			}
			
			return TRUE;
		}
		
		/**
		 * Add Employee Time In Logs - IMPORT
		 * @param unknown_type $emp_id
		 */
		public function import_add_logs($comp_id, $emp_id, $reason, $time_in,$lunch_out,$lunch_in,$time_out, $hours_worked){			
			
			if($time_in != NULL){
				// tardiness
				$tardiness = get_tardiness_import($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in);
				
				// undertime
				$undertime = get_undertime_import($emp_id, $comp_id, $time_in, $time_out, $lunch_out, $lunch_in);
				
				// total hours worked
				$total_hours_worked = get_tot_hours_limit($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out);
				
				// total hours worked view
				$total_hours_worked_view = get_tot_hours($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out, $hours_worked);
				
				$date_insert = array(
					"comp_id"=>$comp_id,
					"emp_id"=>$emp_id,
					"date"=>date("Y-m-d",strtotime($time_in)),
					"source"=>"import",
					"time_in"=>$time_in,
					"lunch_out"=>$lunch_out,
					"lunch_in"=>$lunch_in,
					"time_out"=>$time_out,
					"tardiness_min"=>$tardiness,
					"undertime_min"=>$undertime,
					"total_hours"=>$total_hours_worked_view,
					"total_hours_required"=>$total_hours_worked
				);
				$this->db->insert('employee_time_in', $date_insert);
			}
			
			return TRUE;
		}
		
		/**
		 * Get Approver Name For Time In
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 */
		public function get_approver_name_timein($emp_id,$comp_id){
			$w = array(
				'epi.emp_id'=>$emp_id,
				'epi.company_id'=>$comp_id
			);
			$this->edb->where($w);
			$this->edb->join("employee AS e","epi.eBundy_approval_grp = e.emp_id","left");
			$this->edb->join("accounts AS a","e.account_id = a.account_id","left");
			$q = $this->edb->get("employee_payroll_information AS epi");
			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		
		/**
		 * Check Time In
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $employee_timein
		 */
		public function check_timein($emp_id,$comp_id,$employee_timein){
			$w = array(
				"emp_id"=>$emp_id,
				"comp_id"=>$comp_id,
				"employee_time_in_id"=>$employee_timein
			);
			$this->db->where($w);
			$q = $this->db->get("employee_time_in");
			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		
		/**
		 * Check Split Time In
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $employee_timein
		 */
		public function check_split_timein($emp_id,$comp_id,$employee_timein){
			$w = array(
					"emp_id"=>$emp_id,
					"comp_id"=>$comp_id,
					"schedule_blocks_time_in_id"=>$employee_timein
			);
			$this->db->where($w);
			$q = $this->db->get("schedule_blocks_time_in");
			
			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		
		/**
		 * Get Approver Name For Overtime
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 */
		public function get_approver_name_overtime($emp_id,$comp_id){
			$s = array(
				'e.first_name',
				'a.email'
			);
			$w = array(
				'epi.emp_id'=>$emp_id,
				'epi.company_id'=>$comp_id
			);
			$this->edb->select($s);
			$this->edb->where($w);
			$this->edb->join("employee AS e","epi.overtime_approval_grp = e.emp_id","left");
			$this->edb->join("accounts AS a","e.account_id = a.account_id","left");
			$q = $this->edb->get("employee_payroll_information AS epi");
			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		
		/**
		 * 
		 * Enter description here ...
		 * @param unknown_type $leave_type
		 */
		public function last_row_leave_app($emp_id,$comp_id,$leave_type){
			$w = array(
				"emp_id"=>$emp_id,
				"company_id"=>$comp_id,
				"leave_type_id"=>$leave_type
			);
			$this->db->where($w);
			$this->db->order_by('employee_leaves_application_id','desc');
			$q = $this->db->get("employee_leaves_application");
			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		
		/**
		 * Last Row Overtime Application
		 * @param unknown_type $leave_type
		 */
		public function last_row_overtime_application($emp_id,$comp_id){
			$w = array(
				"emp_id"=>$emp_id,
				"company_id"=>$comp_id
			);
			$this->db->where($w);
			$this->db->order_by('overtime_id','desc');
			$q = $this->db->get("employee_overtime_application");
			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		
		/**
		 * Leave Credits
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function leave_credits($comp_id,$emp_id){
			$date = date("Y-m-d");
			$select2 = array(
			    '*',
			    'lt.created_date AS lt_created_date',
				"el.leave_credits AS a_leave_credits",
				"lt.leave_units AS a_leave_units"			    
			);
			$w = array(
				"el.company_id"=>$comp_id,
				"el.emp_id"=>$emp_id,
				"el.as_of <= "=>$date,
				"el.status"=>"Active"
			);
			
			$this->db->select($select2);
            $this->db->where($w);
            $this->db->where("(lt.flag_is_ml > 1 OR lt.flag_is_ml IS NULL)");
			$this->db->join("leave_type lt","lt.leave_type_id = el.leave_type_id","left");
			$this->edb->join("employee_payroll_information AS ep","ep.emp_id = el.emp_id","INNER");
			$q = $this->edb->get("employee_leaves el");
			$row = $q->result();
			return ($q->num_rows() > 0) ? $row : FALSE ;
		}
		
		/**
		 * Pending Remaining Credits (Ashima 2015)
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function pending_remaining_credits($comp_id,$emp_id){
			$date = date("Y-m-d");
			$where = array(
					"el.company_id"=>$comp_id,
					"el.emp_id"=>$emp_id,
					"el.as_of <= "=>$date,
					"ea.leave_application_status" => "pending",
					"el.status"=>"Active"
			);
			$this->db->where($where);
			$this->db->join("employee_leaves_application ea","ea.leave_type_id = el.leave_type_id","left");
			$this->db->join("leave_type lt","lt.leave_type_id = el.leave_type_id","left");
			$this->db->like('date_filed', $date);
			$q = $this->db->get("employee_leaves el");
			$row = $q->result();
			return ($q->num_rows() > 0) ? $row : FALSE ;
		}
		
		/**
		 * Get Approver Name Overtime
		 * @param unknown_type $comp_id
		 */
		public function get_approver_overtime($emp_id,$comp_id){
			$s = array(
				'e.firstname',
				'a.email',
				'epi.overtime_approval_grp'
			);
			$w = array(
				'epi.emp_id'=>$emp_id,
				'epi.company_id'=>$comp_id
			);
			$this->edb->where($w);
			$this->edb->join("employee AS e","epi.overtime_approval_grp = e.emp_id","left");
			$this->edb->join("accounts AS a","e.account_id = a.account_id","left");
			$q = $this->edb->get("employee_payroll_information AS epi");
			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		
		/**
		 * Check Approval Date
		 * @param unknown_type $payroll_date
		 * @param unknown_type $period_from
		 * @param unknown_type $period_to
		 * @param unknown_type $company_id
		 */
		public function check_approval_date($payroll_date,$period_from,$period_to,$company_id,$emp_id,$payroll_group_id){
			$w = array(
				   "prc.payroll_period"=>$payroll_date,
				   "prc.period_from"=>$period_from,
				   "prc.period_to"=>$period_to,
				   "prc.company_id"=>$company_id,
				   "prc.emp_id"=>$emp_id,
				   "prc.status"=>"Active",
				   "dpr.view_status"=>"Closed",
				   "ap.generate_payslip"=>"Yes"
			);
			$this->db->where($w);
			$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
			$this->db->join("approval_payroll AS ap","ap.token = dpr.token","LEFT");
			$q = $this->db->get("payroll_run_custom AS prc");
			$r = $q->row();
			#last_query();
			if($r){
				return $r;
			}else{
				$w = array(
			    	"dpr.pay_period"=>$payroll_date,
			    	"dpr.period_from"=>$period_from,
				    "dpr.period_to"=>$period_to,
				    "dpr.company_id"=>$company_id,
				    "dpr.payroll_group_id"=>$payroll_group_id,
				    "dpr.status"=>"Active",
				    "dpr.view_status"=>"Closed",
				    "ap.generate_payslip"=>"Yes"
			   );
				
			   $this->db->where($w);
			   $this->db->join("approval_payroll AS ap","ap.token = dpr.token","LEFT");
			   $q = $this->db->get("draft_pay_runs AS dpr");
			   $r = $q->row();
			   return ($r) ? $r : FALSE ;
			}
			
			#return ($q->num_rows() > 0) ? TRUE : FALSE ;
		}
		
		/**
		 * Get New Other Deductions
		 * @param unknown_type $company_id
		 * @param unknown_type $emp_id
		 */
		public function new_other_deductions($company_id,$emp_id,$payroll_period,$period_from,$period_to){
			$w = array(
				"emp_id"=>$emp_id,
				"company_id"=>$company_id,
				"payroll_period"=>$payroll_period,
				"period_from"=>$period_from,
				"period_to"=>$period_to,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("payroll_for_other_deductions");
			return ($q->num_rows() > 0) ? $q->result() : FALSE ;
		}
		
		/**
		 * Check Employee Number
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 */
		public function check_emp_no($emp_id,$comp_id){
			$w = array(
				"e.emp_id"=>$emp_id,
				"e.company_id"=>$comp_id
			);
			$this->edb->where($w);
			$this->edb->join("accounts AS a","e.account_id = a.account_id","LEFT");
			$q = $this->edb->get("employee AS e");
			$r = $q->row();
			
			return ($q->num_rows() > 0) ? $r->payroll_cloud_id : FALSE ;
		}
		
		/**
		 * Check Time In Settings
		 * @param unknown_type $company_id
		 */
		public function check_timein_settings($company_id){
			$w = array(
				"comp_id"=>$company_id
			);
			$this->db->where($w);
			$q = $this->db->get("time_in_settings");
			$r = $q->row();
			if($q->num_rows() > 0){
				$show = $r->show;
				return ($show == "Yes") ? TRUE : FALSE ;
			}else{
				return false;
			}
		}
		
		public function get_payroll_group($emp_id){
			$w = array(
				"emp_id"=>$emp_id
			);
			$this->db->where("status","Active");
			$this->edb->where($w);
			$q = $this->edb->get("employee_payroll_information");
			$r = $q->row();
			return ($r) ? $r->payroll_group_id : FALSE ;
		}
		
		/**
		 * Get Workday Sched Start Time
		 * @param unknown_type $comp_id
		 * @param unknown_type $payroll_group
		 */
		public function get_workday_sched_start($comp_id,$payroll_group){
			$w = array(
				"payroll_group_id"=>$payroll_group,
				"company_id"=>$comp_id,
				"days_of_work"=>date("l"),
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("regular_schedule");
			$r = $q->row();
			if($q->num_rows() > 0){
				return $r->work_start_time;
			}else{
				$w2 = array(
					"payroll_group_id"=>$payroll_group,
					"company_id"=>$comp_id
				);
				$this->db->where($w2);
				$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
				$q2 = $this->db->get("split_schedule");
				$r2 = $q2->row();
				if($q2->num_rows() > 0){
					return $r2->start_time;
				}else{
					return FALSE;
				}
			}
		}
		
		/**
		 * Get End Time
		 * @param unknown_type $comp_id
		 * @param unknown_type $payroll_group
		 */
		public function get_end_time($comp_id,$payroll_group){
			$w = array(
				"payroll_group_id"=>$payroll_group,
				"company_id"=>$comp_id,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("regular_schedule");
			$r = $q->row();
			if($q->num_rows() > 0){
				return $r->work_end_time;
			}else{
				$w2 = array(
					"payroll_group_id"=>$payroll_group,
					"company_id"=>$comp_id
				);
				$this->db->where($w2);
				$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
				$q2 = $this->db->get("split_schedule");
				$r2 = $q2->row();
				if($q2->num_rows() > 0){
					return $r2->end_time;
				}else{
					return FALSE;
				}
			}
		}
		
		/**
		 * Check Hours Flex
		 * @param unknown_type $comp_id
		 * @param unknown_type $payroll_group
		 */
		public function check_hours_flex($comp_id,$payroll_group){
			$w = array(
				"payroll_group_id"=>$payroll_group,
				"company_id"=>$comp_id
			);		
			$this->db->where($w);
			$q = $this->db->get("regular_schedule");
			$r = $q->row();
			if($q->num_rows() > 0){
				return ($r->latest_time_in_allowed != NULL) ? $r->latest_time_in_allowed: FALSE ;
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Check Breaktime
		 * @param unknown_type $comp_id
		 * @param unknown_type $payroll_group
		 */
		public function check_breaktime($comp_id,$payroll_group){
			$w = array(
				"payroll_group_id"=>$payroll_group,
				"company_id"=>$comp_id
			);
			$this->db->where($w);
			$q = $this->db->get("regular_schedule");
			if($q->num_rows() > 0){
				
				return $q->row();
			}else{
				$array = array(
						'start_time AS work_start_time',
						'end_time AS work_end_time'
						
				);
				$this->db->where($w);
				$this->db->select($array);
				$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
				$qs = $this->db->get("split_schedule");
				if($qs->num_rows() > 0){
					return $qs->row();
					
				}else{
					$this->db->where($w);
					$qf = $this->db->get("flexible_hours");
					if($qf->num_rows() > 0){
					
						return $qf->row();
					}else{
						
						return FALSE;
					}
				}
			}
			
		}
		
		/**
		 * Check Workday Settings for start time
		 * @param unknown_type $workday
		 * @param unknown_type $payroll_group_id
		 * @param unknown_type $company_id
		 */
		public function check_workday_settings_start_time($workday,$payroll_group_id,$company_id){
			// check regular schedules
			$w = array(
				"payroll_group_id"=>$payroll_group_id,
				"company_id"=>$company_id,
				"days_of_work"=>$workday,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("regular_schedule");
			if($q->num_rows() > 0){
				$row = $q->row();
				return $row->work_start_time;
			}else{
				// workshift
				$w2 = array(
					"payroll_group_id"=>$payroll_group_id,
					"company_id"=>$company_id
				);
				$this->db->where($w2);
				//$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
				$q2 = $this->db->get("regular_schedule");
				if($q2){
					$row2 = $q2->row();
					return $row2->work_start_time;
				}else{
					return false;
				}
			}
		}
		
		/**
		 * Check Workday Settings for end time
		 * @param unknown_type $workday
		 * @param unknown_type $payroll_group_id
		 * @param unknown_type $company_id
		 */
		public function check_workday_settings_end_time($workday,$payroll_group_id,$company_id){
			// check regular schedules
			$w = array(
				"payroll_group_id"=>$payroll_group_id,
				"company_id"=>$company_id,
				"days_of_work"=>$workday,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("regular_schedule");
			if($q->num_rows() > 0){
				$row = $q->row();
				return $row->work_end_time;
			}else{
				// workshift
				$w2 = array(
					"payroll_group_id"=>$payroll_group_id,
					"company_id"=>$company_id
				);
				$this->db->where($w2);
				//$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
				$q2 = $this->db->get("regular_schedule");
				if($q2){
					$row2 = $q2->row();
					return $row2->work_end_time;
				}else{
					return false;
				}
			}
		}
		
		/**
		 * Employee Time In
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function employee_timein($comp_id, $emp_id){
			$w = array(
				"emp_id"=>$emp_id,
				"comp_id"=>$comp_id,
			     "source" => "EP"
			);
			$this->db->where($w);
			$this->db->order_by("employee_time_in_id","DESC");
			$q = $this->db->get("employee_time_in",1,0);
			$r = $q->row();
			return ($r) ? $r->employee_time_in_id : FALSE ;
		}
		
		/**
		 * Check Rest Day
		 * @param unknown_type $workday
		 * @param unknown_type $payroll_group_id
		 * @param unknown_type $comp_id
		 */
		public function check_rest_day($workday,$payroll_group_id,$comp_id){
			$w = array(
				"payroll_group_id"=>$payroll_group_id,
				"company_id"=>$comp_id,
				"rest_day"=>$workday
			);
			$this->db->where($w);
			$q = $this->db->get("rest_day");
			return ($q->num_rows() > 0) ? TRUE : FALSE ;
		}
		
		/**
		 * Halfday Check Workday
		 * @param unknown_type $payroll_group
		 * @param unknown_type $company_id
		 * @param unknown_type $new_date
		 */
		public function halfday_check_workday($payroll_group,$company_id,$new_date){
			// regular_schedule
			$w = array(
				"payroll_group_id"=>$payroll_group,
				"company_id"=>$company_id,
				"days_of_work"=>date("l",strtotime($new_date))
			);
			$this->db->where($w);
			$q = $this->db->get("regular_schedule");
			$r = $q->row();
			if($r){
				$ds = date("A",strtotime($r->work_start_time));
				$de = date("A",strtotime($r->work_end_time));
				return ($ds == "PM" && $de == "AM") ? TRUE : FALSE ;
			}else{
				$w1 = array(
					"payroll_group_id"=>$payroll_group,
					"company_id"=>$company_id
				);
				$this->db->where($w1);
				$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
				$q1 = $this->db->get("split_schedule");
				$r1 = $q1->row();
				
				if($r1){
					$ds = date("A",strtotime($r1->start_time));
					$de = date("A",strtotime($r1->end_time));
					return ($ds == "PM" && $de == "AM") ? TRUE : FALSE ;
				}else{
					return FALSE;
				}
			}
		}
		
		/**
		 * Get payroll period
		 * @param int $company_id
		 */
		public function get_payroll_period($company_id)
		{
			$where = array(
				'company_id' => $company_id,
				'status'	 => 'Active'
			);
			$this->db->where($where);
			$q = $this->db->get('payroll_period');
			$row = $q->row();
			
			return ($row) ? $row : false;
		}
		
		/**
		 * Get Hours Worked for workday
		 * @param unknown_type $workday
		 * @param unknown_type $emp_id
		 */
		public function new_hoursworked($workday, $emp_id){
			$workday_val = date("l",strtotime($workday));
			
			// get employee payroll information
			$w = array("emp_id"=>$emp_id);
			$this->edb->where($w);
			$this->db->where("status","Active");
			$q = $this->edb->get("employee_payroll_information");
			$r = $q->row();
			
			// get work schedule id
			$w_ws = array("emp_id"=>$emp_id);
			$this->db->where($w);
			$this->db->where("status","Active");
			$q_ws = $this->db->get("employee_shifts_schedule");
			$r_ws = $q_ws->row();
			$work_schedule_id = ($r_ws) ? $r_ws->work_schedule_id : "" ;
			if($work_schedule_id==""){
				$payroll_group_id = $r->payroll_group_id;
				$w_pg = array("payroll_group_id"=> $payroll_group_id);
				$this->db->where($w_pg);
				$this->db->where("status","Active");
				$q_pg =  $this->db->get("payroll_group");
				$r_pg = $q_pg->row();
				if($q_pg->num_rows() > 0){
					$work_schedule_id = $r_pg->work_schedule_id;
				}
			}
			if($work_schedule_id){
				//$payroll_group_id = $r->payroll_group_id;
				$comp_id = $r->company_id;
				
				// get hours worked
				$w2 = array(
					"days_of_work"=>$workday_val,
					"company_id"=>$comp_id,
					"status"=>"Active"
				);
				//if($work_schedule_id==""){
					//$this->db->where("payroll_group_id",$payroll_group_id);
				//}else{
					$this->db->where("work_schedule_id",$work_schedule_id);
				//}
				$this->db->where($w2);
				$q2 = $this->db->get("regular_schedule");
				$r2 = $q2->row();
				if($q2->num_rows() > 0){
					// for regular schedules table
					return $r2->total_work_hours;
				}else{
					$wf = array("company_id"=>$comp_id);
					//if($work_schedule_id==""){
						//$this->db->where("payroll_group_id",$payroll_group_id);
					//}else{
						$this->db->where("work_schedule_id",$work_schedule_id);
					//}
					$this->db->where($wf);
					$qf = $this->db->get("flexible_hours");
					$rf = $qf->row();
					if($qf->num_rows() > 0){
						// for flexible hours table
						return $rf->total_hours_for_the_day;
					}else{
						
// 						$ww = array("eb.emp_id"=>$emp_id);
// 						//if($work_schedule_id==""){
// 						//	$this->db->where("payroll_group_id",$payroll_group_id);
// 						//}else{
// 							$this->db->where("eb.work_schedule_id",$work_schedule_id);
// 						//}
// 						$this->db->where($ww);
// 						$this->db->join('schedule_blocks AS sb','sb.schedule_blocks_id = eb.schedule_blocks_id','LEFT');
// 						$qw = $this->db->get("employee_sched_block AS eb");
// 						$rq = $qw->result();
// 						if($qw->num_rows() > 0){
// 							$total_work_hours = 0;
// 							foreach($rq as $sb){
// 								$total_work_hours = $total_work_hours + $sb->total_work_hours;
// 							}
// 							// for workshift table
// 							return $total_work_hours;
// 						}else{
 							return 0;
// 						}
					}
				}
			}else{
				return 0;
			}
		}
		
		/**
		 * Check Company Break Time
		 * @param unknown_type $emp_work_schedule_id
		 * @param unknown_type $company_id
		 * @param unknown_type $type
		 */
		public function check_break_time($id,$company_id,$type, $day){
			
			if($type=="work_schedule_id"){
				$w = array(
					"company_id"=>$company_id,
					"work_schedule_id"=>$id,
					"days_of_work"=>date("l",strtotime($day)),
					"payroll_group_id"=> 0
				);
			}else{
				$w = array(
					"company_id"=>$company_id,
					"payroll_group_id"=>$id
				);
			}
			$this->db->where($w);
			$q = $this->db->get("regular_schedule");
			if($q->num_rows() > 0){
				$r = $q->row();
				if($r->break_in_min == 0){
					
					return FALSE;
				}else{
					return TRUE;
				}
			}else{
				
				if($type=="work_schedule_id"){
					$w = array(
							"company_id"=>$company_id,
							"work_schedule_id"=>$id
					);
				}else{
					$w = array(
							"company_id"=>$company_id,
							"payroll_group_id"=>$id
					);
				}
				$this->db->where($w);
				$qf = $this->db->get("flexible_hours");
				if($qf->num_rows() > 0){
				$rf = $qf->row();
					#if($r->duration_of_lunch_break_per_day == 0 || $r->duration_of_lunch_break_per_day == ""){
					if($rf->duration_of_lunch_break_per_day == 0 || $rf->duration_of_lunch_break_per_day == ""){
						return FALSE;
					}else{
						
						return TRUE;
					}
				}else{
					return FALSE;
				}
				
			}
		}
		
		/**
		 * Check Company Break Time
		 * @param unknown_type $emp_work_schedule_id
		 * @param unknown_type $company_id
		 * @param unknown_type $type
		 */
		public function check_break_time_for_assumed($id,$company_id,$type, $day){
				
			if($type=="work_schedule_id"){
				$w = array(
						"company_id"=>$company_id,
						"work_schedule_id"=>$id,
						"days_of_work"=>date("l",strtotime($day)),
						"payroll_group_id"=> 0
				);
			}else{
				$w = array(
						"company_id"=>$company_id,
						"payroll_group_id"=>$id
				);
			}
			$this->db->where($w);
			$q = $this->db->get("regular_schedule");
			if($q->num_rows() > 0){
				$r = $q->row();
				if($r->break_in_min == 0){
						
					return FALSE;
				}else{
					return $r;
				}
			}else{
		
				if($type=="work_schedule_id"){
					$w = array(
							"company_id"=>$company_id,
							"work_schedule_id"=>$id
					);
				}else{
					$w = array(
							"company_id"=>$company_id,
							"payroll_group_id"=>$id
					);
				}
				$this->db->where($w);
				$qf = $this->db->get("flexible_hours");
				
				if($qf->num_rows() > 0){
					$rf = $qf->row();
					#if($r->duration_of_lunch_break_per_day == 0 || $r->duration_of_lunch_break_per_day == ""){
					if($rf->duration_of_lunch_break_per_day == 0 || $rf->duration_of_lunch_break_per_day == ""){
						return FALSE;
					}else{
						return $rf;
					}
				}else{
					return FALSE;
				}
		
			}
		}
		
		/**
		 * Get latest time in allowed
		 * @param unknown_type $company_id
		 * @param unknown_type $id
		 * @param unknown_type $string
		 */
		public function get_latest_timein_allowed_val($company_id,$id,$string){
			$ww = array("company_id"=>$company_id);
			if($string=="payroll_group_id"){
				$this->db->where("payroll_group_id",$id);
			}else{
				$this->db->where("work_schedule_id",$id);
			}
			$this->db->where($ww);
			$q_w = $this->db->get("regular_schedule");
			$r_w = $q_w->row();
			if($r_w){
				return ( $r_w->latest_time_in_allowed != NULL) ? $r_w->latest_time_in_allowed :  FALSE ;
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Check Work Schedule
		 * @param unknown_type $id
		 * @param unknown_type $company_id
		 * @param unknown_type $type
		 */
		public function check_work_schedule_flex($id,$company_id,$type){
			if($type=="work_schedule_id"){
				$w = array(
					"work_schedule_id"=>$id,
					"company_id"=>$company_id
				);
			}else{
				$w = array(
					"payroll_group_id"=>$id,
					"company_id"=>$company_id
				);
			}
			$this->db->where($w);
			$q = $this->db->get("flexible_hours");
			$r = $q->row();
			return ($r) ? TRUE : FALSE ;
		}
		
		/**
		 * Work Schedule ID
		 * @param unknown_type $company_id
		 * @param unknown_type $emp_id
		 */
		public function work_schedule_id($company_id,$emp_id){
			
			$currentdate = date("Y-m-d");
			$w_currentdate = array(
				"valid_from <="		=>	$currentdate,
				"until >="			=>	$currentdate
			);
			$w = array(
				"emp_id"=>$emp_id,
				"company_id"=>$company_id,
				"status"=>"Active"
			);
			
			$this->db->where($w_currentdate);
			$this->db->where($w);
			$q = $this->db->get("employee_shifts_schedule");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
		
		/**
		 * Check Workday for Work Schedule
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $work_schedule
		 */
		public function check_workday_ws($emp_id,$comp_id,$work_schedule){
			$where_fh = array(
				"work_schedule_id"=>$work_schedule,
				"company_id"=>$comp_id
			);
			$this->db->where($where_fh);
			$sql_fl = $this->db->get("flexible_hours");
			return ($sql_fl->num_rows() > 0) ? $sql_fl->row() : FALSE ;
		}
		
		/**
		 * Check if Work Schedule is Regular Schedule
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $work_schedule
		 */
		public function check_regular_ws($emp_id,$comp_id,$work_schedule,$dow=FALSE){
			if($dow == FALSE){
				$where_fh = array(
						"work_schedule_id"=>$work_schedule,
						"company_id"=>$comp_id
				);
			}else {
				$where_fh = array(
						"days_of_work"=>$dow,
						"work_schedule_id"=>$work_schedule,
						"company_id"=>$comp_id
				);
			}
			$this->db->where($where_fh);
			$sql_fl = $this->db->get("regular_schedule");
			return ($sql_fl->num_rows() > 0) ? $sql_fl->result() : FALSE ;
		}
		
		/**
		 * Check if Work Schedule is Workshift Schedule
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $work_schedule
		 */
		public function check_workshift_ws($emp_id,$comp_id,$work_schedule){
			$where_fh = array(
					"work_schedule_id"=>$work_schedule,
					"company_id"=>$comp_id
			);
			$this->db->where($where_fh);
			$sql_fl = $this->db->get("schedule_blocks");
			return ($sql_fl->num_rows() > 0) ? $sql_fl->row() : FALSE ;
		}
		
		
		/**
		 * For Leave Hoursworked for Work Schedule
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $workday
		 * @param unknown_type $work_schedule
		 */
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
			
			// for workshift
			//CHANGED DUE TO SPLIT SCHEDULE IS CHANGED
// 			$where_w = array(
// 				"work_schedule_id"=>$work_schedule,
// 				"company_id"=>$comp_id
// 			);
			
// 			$this->db->where($where_w);
// 			$sql_w = $this->db->get("split_schedule");
// 			$row_w = $sql_w->row();
			
// 			if($sql_w->num_rows() > 0){
// 				return $row_w->total_work_hours;
// 			}
			
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
		
		/**
		 * For Leave Breaktime Start Time for Work Schedule
		 * @param unknown_type $emp
		 * @param unknown_type $comp_id
		 * @param unknown_type $workday
		 * @param unknown_type $work_schedule
		 */
		public function for_leave_breaktime_start_time_ws($emp_id,$comp_id,$workday,$work_schedule, $start_time){
			// for regular schedules and workshift
			$where_workday = array(
				"work_schedule_id"=>$work_schedule,
				"company_id"=>$comp_id,
				"days_of_work"=>$workday	
			);
			$this->db->where($where_workday);
			$sql_workday = $this->db->get("regular_schedule");
			$row_workday = $sql_workday->row();
			
			//if($sql_workday->num_rows() > 0){
			
			if($row_workday){
				if($row_workday->break_in_min != 0){
					$new_st = $row_workday->work_start_time;
					$total_work_hours = number_format($row_workday->total_work_hours,0);
					$new_latest_timein_allowed = date('H:i:s', strtotime($row_workday->work_start_time.' +'.$row_workday->latest_time_in_allowed.' minutes'));
						
					if($new_latest_timein_allowed){ // if latest time in is true
					    
					    if(strtotime($start_time) < strtotime($new_st)){
							$new_work_start_time = $new_st;
					    }elseif(strtotime($new_st) <= strtotime($start_time) && strtotime($start_time) <= strtotime($new_latest_timein_allowed)){
							$new_work_start_time = $start_time;
						}elseif(strtotime($start_time) > strtotime($new_latest_timein_allowed)){
							$new_work_start_time = $new_latest_timein_allowed;
						}
					}else{
						$new_work_start_time = $row_workday->work_start_time;
					}
					$end_time = date('H:i:s', strtotime($new_work_start_time.' +'.$total_work_hours.' hours'));
					$check_time = (abs(strtotime($end_time) - strtotime($new_work_start_time))) / 3600;
					$check2 = $check_time / 2;
					$check2 = round($check2, 0, PHP_ROUND_HALF_DOWN);
					
					
					
					//$hd_check = strtotime($wd_start) + $check_time;
					$hd_check = date('H:i:s', strtotime($new_work_start_time.' +'.$check2.' hours'));
					return $hd_check;
				}else{
					return '12:00:00';
				}
			}
		}
		
		/**
		 * For Leave Breaktime End Time for Work Schedule
		 * @param unknown_type $emp
		 * @param unknown_type $comp_id
		 * @param unknown_type $workday
		 * @param unknown_type $work_schedule
		 */
		public function for_leave_breaktime_end_time_ws($emp_id,$comp_id,$workday,$work_schedule,$start_time){
			// for regular schedules and workshift
			$where_workday = array(
				"work_schedule_id"=>$work_schedule,
				"company_id"=>$comp_id,
				"days_of_work"=>$workday
			);
			
			$this->db->where($where_workday);
			$sql_workday = $this->db->get("regular_schedule");
			$row_workday = $sql_workday->row();
			
			if($row_workday){
				if($row_workday->break_in_min != 0){
 					$new_st = $row_workday->work_start_time;
					$total_work_hours = number_format($row_workday->total_work_hours,0);
					$new_latest_timein_allowed = date('H:i:s', strtotime($row_workday->work_start_time.' +'.$row_workday->latest_time_in_allowed.' minutes'));
						
					if($new_latest_timein_allowed){ // if latest time in is true
					    if(strtotime($start_time) < strtotime($new_st)){
							$new_work_start_time = $new_st;
					    }elseif(strtotime($new_st) <= strtotime($start_time) && strtotime($start_time) <= strtotime($new_latest_timein_allowed)){
							$new_work_start_time = $start_time;
					    }elseif(strtotime($start_time) > strtotime($new_latest_timein_allowed)){
							$new_work_start_time = $new_latest_timein_allowed;
						}
					}else{
						$new_work_start_time = $row_workday->work_start_time;
					}
					
					$end_time = date('H:i:s', strtotime($new_work_start_time.' +'.$total_work_hours.' hours'));
					$check_time = (abs(strtotime($end_time) - strtotime($new_work_start_time)) ) / 3600;
					$check2 = $check_time / 2;
					$check2 = round($check2, 0, PHP_ROUND_HALF_DOWN);
					
					$hd_check = date('H:i:s', strtotime($new_work_start_time.' +'.$check2.' hours +'.$row_workday->break_in_min.' minutes'));
					
					return $hd_check;
				}else{
					return '12:00:00';
				}
			}
		}
		
		/**
		 * For Leave Hoursworked work start time
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $work_schedule 
		 */
		public function for_leave_hoursworked_work_start_time_ws($emp_id,$comp_id,$work_schedule){
			// for regular schedules and workshift
			$where_hw = array(
				"work_schedule_id"=>$work_schedule,
				"company_id"=>$comp_id,
			);
			
			// check regular schedules
			$this->db->where($where_hw);
			$uws_q = $this->db->get("regular_schedule");
			$uws_r = $uws_q->row();
			
			if($uws_r){
				if($uws_r->latest_time_in_allowed != "" || $uws_r->latest_time_in_allowed != NULL){
					
					return date('H:i:s', strtotime($uws_r->work_start_time.' + '.$uws_r->latest_time_in_allowed.' minutes'));
				}else{
					$this->db->where($where_hw);
					$sql_hw = $this->db->get("regular_schedule");
					$row_hw = $sql_hw->row();
					
					// for regular schedules and workshift
					if($sql_hw->num_rows() > 0){
						return $row_hw->work_start_time;
					}else{
						$this->db->where($where_hw);
						$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
						$sql_w = $this->db->get("split_schedule");
						$row_w = $sql_w->row();
						return ($row_w) ? $row_w->start_time : FALSE ;
					}
				}
			}else{
				$this->db->where($where_hw);
				$sql_hw = $this->db->get("regular_schedule");
				$row_hw = $sql_hw->row();
				
				// for regular schedules and workshift
				if($sql_hw->num_rows() > 0){
					return $row_hw->work_start_time;
				}else{
					$this->db->where($where_hw);
					$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
					$sql_w = $this->db->get("split_schedule");
					$row_w = $sql_w->row();
					return ($row_w) ? $row_w->start_time : FALSE ;
				}
			}
		}
		
		/**
		 * For Leave Hoursworked work end time
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $work_schedule
		 */
		public function for_leave_hoursworked_work_end_time_ws($emp_id,$comp_id,$work_schedule,$date = ""){
			
			// for regular schedules and workshift
			if($date != "") {
				$where_hw = array(
						"work_schedule_id"=>$work_schedule,
						"company_id"=>$comp_id,
						"days_of_work" => date("l", strtotime($date))
				);
			} else {
				$where_hw = array(
						"work_schedule_id"=>$work_schedule,
						"company_id"=>$comp_id,
				);
			}
			
			
			$this->db->where($where_hw);
			$sql_hw = $this->db->get("regular_schedule");
			$row_hw = $sql_hw->row();
			
			// for regular schedules and workshift
			if($sql_hw->num_rows() > 0){
				return $row_hw->work_end_time;
			}else{
				$this->db->where($where_hw);
				$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
				$sql_w = $this->db->get("split_schedule");
				$row_w = $sql_w->row();
				return ($row_w) ? $row_w->end_time : FALSE ;
			}
		}
		
		/**
		 * Get Total Hours Worked
		 * @param unknown_type $time_in
		 * @param unknown_type $lunch_in
		 * @param unknown_type $lunch_out
		 * @param unknown_type $time_out
		 */
		public function get_tot_hours_ws($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$time_out,$hours_worked,$work_schedule, $all_break_out = 0,$new_break_rules = false){
		    #echo $time_out
		    // check if rest day
		    
		    $rest_day = $this->check_holiday_val_ws($time_in,$emp_id,$comp_id,$work_schedule);
		    if($rest_day){
		        $total = (strtotime($time_out) - strtotime($time_in)) / 3600;
		    }else{
		        // check time out for regular schedules
		        $where_uw = array(
		            "company_id"=>$comp_id,
		            "work_schedule_id"=>$work_schedule,
		            "days_of_work"=>date("l",strtotime($time_in))
		        );
		        $this->db->where($where_uw);
		        $sql_uw = $this->db->get("regular_schedule");
		        
		        $row_uw = $sql_uw->row();
		        
		        if($sql_uw->num_rows() > 0){
		            // FOR CALLCENTER
		            
		            $time_out_sec = date("H:i:s",strtotime($time_out));
		            $time_out_date = date("Y-m-d",strtotime($time_out));
		            $time_out = date('Y-m-d H:i:s', strtotime($time_out));
		            $new_work_end_time = date('Y-m-d H:i:s', strtotime($time_out_date." ".$row_uw->work_end_time));
		            
		            $total_hours_worked = abs((strtotime($time_out) - strtotime($time_in)) / 3600);
		            #p($time_out.' '.$time_in);
		            #echo $time_out.' '.$time_in.'***';
		        }else{
		            // check time out for flexible hours
		            $where_f = array(
		                "company_id"=>$comp_id,
		                "work_schedule_id"=>$work_schedule,
		            );
		            $this->db->where($where_f);
		            $sql_f = $this->db->get("flexible_hours");
		            $row_f = $sql_f->row();
		            if($row_f){
		                $total_hours_worked = abs((strtotime($time_in . ' + '.$row_f->total_hours_for_the_day.' hour') - strtotime($time_in)) / 3600);
		            }else{
		                $total_hours_worked = 0;
		            }
		        }
		        
		        $get_tardiness = ($this->get_tardiness_breaktime_ws($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$work_schedule)) / 60;
		        
		        $breaktime_hours1 = $this->add_breaktime_ws_toper($comp_id,$work_schedule,$time_in);
		        $breaktime_hours = 0;
		        
		        if($breaktime_hours1) {
		            $my_date = date("Y-m-d", strtotime($time_in));
		            
		            if($breaktime_hours1->assumed_breaks != null || $breaktime_hours1->assumed_breaks != "" || $breaktime_hours1->assumed_breaks != 0) {
		                $assumed_breaks = $breaktime_hours1->assumed_breaks;
		            } else {
		                $assumed_breaks = $hours_worked / 2;
		            }
		            
		            $threshold = $breaktime_hours1->latest_time_in_allowed;
		            
		            $end_break_tot = $assumed_breaks + ($breaktime_hours1->break_in_min / 60);
		            
		            $start_break = date("Y-m-d H:i:s", strtotime($my_date." ".$breaktime_hours1->work_start_time." +".number_format($breaktime_hours1->assumed_breaks,0)." hours"));
		            $end_break1 = date("Y-m-d H:i:s", strtotime($my_date." ".$breaktime_hours1->work_start_time." +".$end_break_tot." hours"));
		            $end_break = date("Y-m-d H:i:s", strtotime($end_break1." ".$threshold." minutes"));
		            
		            if(strtotime($end_break) <= strtotime($time_in)) {
		                $breaktime_hours = 0;
		            } else {
		                if($new_break_rules) {
		                    $breaktime_hours = $all_break_out / 60;
		                } else {
		                    $breaktime_hours = $breaktime_hours1->break_in_min / 60;
		                }
		                
		            }
		        } else {
		            $breaktime_hours = $this->add_breaktime_ws($comp_id,$work_schedule,$time_in);
		        }
		        
		        $total = $total_hours_worked - $get_tardiness - $breaktime_hours;
		        
		        if($total > $hours_worked){
		            $total = $hours_worked;
		        }
		        
		    }
		    
		    return ($total < 0) ? round(0,2) : round($total,2) ;
		}
		
		/**
		 * Add Breaktime for undertime
		 * @param unknown_type $comp
		 * @param unknown_type $work_schedule
		 * @param unknown_type $workday
		 */
		public function add_breaktime_ws($comp_id,$work_schedule,$workday ){
			$CI =& get_instance();
			
			$day = date("l",strtotime($workday));
			
			$where = array(
				"days_of_work" => $day,
				"company_id" => $comp_id,
				"work_schedule_id"=>$work_schedule,
			);
			$this->db->where($where);
			$sql = $this->db->get("regular_schedule");
			$r = $sql->row();
			
			// FOR REGULAR SCHEDULES
			if($r){
				$breaktime = $r->break_in_min / 60;
			}else{
				$w_f = array(
						'company_id'=> $comp_id,
						'work_schedule_id'=> $work_schedule
				);
				$this->db->where($w_f);
				$q_f = $this->db->get("flexible_hours");
				$r_f = $q_f->row();
				
				if($r_f){
					$breaktime = $r_f->duration_of_lunch_break_per_day / 60;
				}else{
					$breaktime = 0;
				}
			}
				
			return ($breaktime < 0) ? 0 : $breaktime ;
		}
		
		public function add_breaktime_ws_toper($comp_id,$work_schedule,$workday ){
			$CI =& get_instance();
				
			$day = date("l",strtotime($workday));
				
			$where = array(
					"rs.days_of_work" => $day,
					"rs.company_id" => $comp_id,
					"rs.work_schedule_id"=>$work_schedule,
			);
			$this->db->where($where);
			$this->db->join("work_schedule AS ws", "ws.work_schedule_id = rs.work_schedule_id", "LEFT");
			$sql = $this->db->get("regular_schedule AS rs");
			$r = $sql->row();
			
			return ($r) ? $r : false;
		}
		
		/**
		 * Check Holiday for Work Schedule
		 * @param unknown_type $day
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $work_schedule
		 */
		public function check_holiday_val_ws($day,$emp_id,$comp_id,$work_schedule){
			$w = array(
				"rest_day"=>date("l",strtotime($day)),
				"company_id"=>$comp_id,
				"work_schedule_id"=>$work_schedule,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get('rest_day');
			return ($q->num_rows() > 0) ? TRUE : FALSE ;
		}
		
		/**
		 * Get Tardiness for breaktime
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $date
		 * @param unknown_type $time_in
		 */
		public function get_tardiness_breaktime_ws($emp_id,$comp_id,$time_in_import,$lunch_out,$lunch_in,$work_schedule){
			$day = date("l",strtotime($time_in_import));
			
			// check rest day
			$rest_day = $this->check_holiday_val_ws($time_in_import,$emp_id,$comp_id,$work_schedule);
			if($rest_day){
				$min_late_breaktime = 0;
			}else{
			// rest day
				$rest_day = $this->db->query("
					SELECT *FROM rest_day
					WHERE company_id = '{$comp_id}'
					AND rest_day = '{$day}'
					AND work_schedule_id = '{$work_schedule}'
				");
				
				if($rest_day->num_rows() == 0){
					
					// regular schedules
					$sql = $this->db->query("
						SELECT *FROM regular_schedule
						WHERE work_schedule_id = '{$work_schedule}'
						AND company_id = '{$comp_id}'
					");
					
					if($sql->num_rows() > 0){
						$row = $sql->row();
						$sql->free_result();
						$payroll_sched_timein = $row->latest_time_in_allowed;
						$payroll_group_id = $row->payroll_group_id;
						
					}else{				
						// regular schedule
						$sql_regular_schedules = $this->db->query("
							SELECT *FROM regular_schedule
							WHERE work_schedule_id = '{$work_schedule}'
							AND company_id = '{$comp_id}'
							AND days_of_work = '{$day}'
						");
						
						if($sql_regular_schedules->num_rows() > 0){
							$row_regular_schedules = $sql_regular_schedules->row();
							$sql_regular_schedules->free_result();
							$payroll_sched_timein = $row_regular_schedules->work_start_time;
							$payroll_group_id = $row->payroll_group_id;
						}else{
							// flexible working days
							$sql_flexible_days = $this->db->query("
								SELECT *FROM flexible_hours
								WHERE work_schedule_id = '{$work_schedule}'
								AND company_id = '{$comp_id}'
							");
							
							if($sql_flexible_days->num_rows() > 0){
								$row_flexible_days = $sql_flexible_days->row();
								$sql_flexible_days->free_result();
								$payroll_sched_timein = $row_flexible_days->latest_time_in_allowed;
								$payroll_group_id = $row->payroll_group_id;
							}else{
								// workshift working days
// 								$where = array(
// 										'work_schedule_id'=>$work_schedule,
// 										'company_id' => $comp_id
// 								);
// 								$this->db->where($where);
// 								$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
// 								$sql_workshift = $this->db->get('split_scheduke');
								
// // 								$sql_workshift = $this->db->query("
// // 									SELECT *FROM split_schedule
// // 									WHERE work_schedule_id = '{$work_schedule}'
// // 									AND company_id = '{$comp_id}'
// // 								");
								
// 								if($sql_workshift->num_rows() > 0){
// 									$row_workshift = $sql_workshift->row();
// 									$sql_workshift->free_result();
// 									$payroll_group_id = $row->payroll_group_id;
// 									return $row_workshift->start_time;
// 								}else{
									$payroll_sched_timein = "00:00:00";
								//}
							}
						}
					}
				}else{
					$payroll_sched_timein = "00:00:00";
				}
				
				$time_in_import = date("H:i:s",strtotime($time_in_import));
				
				// for tardiness time in
				$time_x=(strtotime($time_in_import) - strtotime($payroll_sched_timein)) / 3600;	
			
				if($payroll_sched_timein != "00:00:00" || $payroll_sched_timein != "" || $payroll_sched_timein != NULL){
					if($time_x<0){
						if(abs($time_x) >= 12){
							// print $time_z= (24-(abs($time_x))) * 60 . " late ";
							$min_late = round((24-(abs($time_x))) * 60, 2);
						}else{
							$min_late = 0;
						}
					}else{
						// print $time_x * 60 . " late ";
						$min_late = round($time_x * 60, 2);
					}
				}else{
					$min_late = 0;
				}
				
				// for tardiness break
				
				// get regular schedules and workshift settings for break time
				$where_break = array(
					"company_id" => $comp_id,
					"work_schedule_id"=>$work_schedule,
					"days_of_work" => $day
				);
				$this->db->where($where_break);
				$sql_break = $this->db->get("regular_schedule");
				$row_break = $sql_break->row();
				
				if($sql_break->num_rows() > 0){
					 $breaktime_settings = $row_break->break_in_min  * 60;
				}else{
					// get flexible hours for break time
					$where_break_flex = array(
						"company_id" => $comp_id,
						"payroll_group_id" => $payroll_group_id
					);
					$this->db->where($where_break_flex);
					$sql_break_flex = $this->db->get("flexible_hours");
					$row_break_flex = $sql_break_flex->row();
					
					if($sql_break_flex->num_rows() > 0){
						$breaktime_settings = $row_break_flex->duration_of_lunch_break_per_day * 60; // convert to seconds
					}else{
						$breaktime_settings = 0;
					}
				}
				
				$breaktime_timein = strtotime($lunch_in) - strtotime($lunch_out);
				
				if($breaktime_timein > $breaktime_settings){
					$min_late_breaktime = ($breaktime_timein - $breaktime_settings) / 60;
				}else{
					$min_late_breaktime = 0;
				}	
			}
			return ($min_late_breaktime < 0) ? 0 : $min_late_breaktime ;	
		}
		
		/**
		 * Check Employee Work Schedule ID
		 * @param unknown_type $emp_id
		 * @param unknown_type $check_company_id
		 * @param unknown_type $currentdate
		 */
		public function emp_work_schedule($emp_id,$check_company_id,$currentdate){
			// employee group id
			$s = array(
				"ess.work_schedule_id"
			);
			$w_date = array(
				"ess.valid_from <="		=>	$currentdate,
				"ess.until >="			=>	$currentdate
			);
			$this->db->where($w_date);
			
			$w_emp = array(
				"e.emp_id"=>$emp_id,
				"ess.company_id"=>$check_company_id,
				"e.status"=>"Active",
				"ess.status"=>"Active",
				"ess.payroll_group_id" => 0
			);
			$this->edb->select($s);
			$this->edb->where($w_emp);
			$this->edb->join("employee AS e","e.emp_id = ess.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$q_emp = $this->edb->get("employee_shifts_schedule AS ess");
			$r_emp = $q_emp->row();
			
			if($r_emp){
				return $r_emp->work_schedule_id;
			}else{
				$w = array(
						'epi.emp_id'=> $emp_id
				);
				$this->db->where($w);
				$this->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','LEFT');
				$q_pg = $this->edb->get('employee_payroll_information AS epi');
				$r_pg = $q_pg->row();
				
				return ($r_pg) ? $r_pg->work_schedule_id : FALSE;
				
			}
		}
		
		/**
		 * Check Company ID
		 * @param unknown_type $emp_id
		 */
		public function check_company_id($emp_id){
			$w = array(
				"emp_id"=>$emp_id,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("employee");
			$r = $q->row();
			return ($r) ? $r->company_id : FALSE ;
		}
		
		/**
		 * Check Employee for Approvers
		 * @param unknown_type $emp_id
		 * @param unknown_type $field
		 */
		public function check_emp_approvers($emp_id,$field){
			$w = array(
				"emp_id"=>$emp_id,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("employee_payroll_information");
			$r = $q->row();
			if($r){
				return ($r->$field == "") ? FALSE : TRUE ;
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Check New Latest Time In Allowed
		 * @param unknown_type $emp_id 
		 * @param unknown_type $comp_id
		 * @param unknown_type $schedule_id
		 * @param unknown_type $str
		 */
		public function new_latest_timein_allowed($emp_id,$comp_id,$schedule_id,$str){
			if($str == "work_schedule"){
				$where = array(
					"company_id"=>$comp_id,
					"work_schedule_id"=>$schedule_id
				);
			}else{
				$where2 = array(
					"emp_id"=>$emp_id,
					"company_id"=>$comp_id
				);
				$this->db->where($where2);
				$sql = $this->db->get("employee_payroll_information");
				$row_sql = $sql->row();
				
				if($row_sql){
					$payroll_group_id = $row_sql->payroll_group_id;
					
					$where = array(
						"company_id"=>$comp_id,
						"payroll_group_id"=>$payroll_group_id
					);
				}else{
					return FALSE;
				}
			}
			
			// check regular schedule settings
			$this->db->where($where);
			$uws_q = $this->db->get("regular_schedule");
			$uws_r = $uws_q->row();
			if($uws_r){
				return ($uws_r->latest_time_in_allowed != "" || $uws_r->latest_time_in_allowed != NULL) ? date('H:i:s', strtotime($uws_r->work_start_time.' +'.$uws_r->latest_time_in_allowed.' minutes')) : FALSE ;
			}else{
				// check flexible hours
				$this->db->where($where);
				$f_q = $this->db->get("flexible_hours");
				$f_r = $f_q->row();
				if($f_r){
					if($f_r->not_required_login == 0){
						return ($f_r->latest_time_in_allowed != "" || $f_r->latest_time_in_allowed != NULL) ? $f_r->latest_time_in_allowed : FALSE ;	
					}else{
						return FALSE;
					}
				}else{
					return FALSE;
				}
			}
		}
		
		/**
		 * Get Start Time Schedule
		 * @param unknown_type emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $schedule_id
		 * @param unknown_type $str
		 */
		public function get_start_time($emp_id,$comp_id,$schedule_id,$str){
			if($str == "work_schedule"){
				$where = array(
					"company_id"=>$comp_id,
					"work_schedule_id"=>$schedule_id
				);
			}else{
				$where2 = array(
					"emp_id"=>$emp_id,
					"company_id"=>$comp_id
				);
				$this->db->where($where2);
				$sql = $this->db->get("employee_payroll_information");
				$row_sql = $sql->row();
				
				if($row_sql){
					$payroll_group_id = $row_sql->payroll_group_id;
					
					$where = array(
						"company_id"=>$comp_id,
						"payroll_group_id"=>$payroll_group_id
					);
				}else{
					return FALSE;
				}
			}
			
			// check regular schedule
			$this->db->where($where);
			$uws_q = $this->db->get("regular_schedule");
			$uws_r = $uws_q->row();
			if($uws_r){
				return $uws_r->work_start_time;
			}else{
				// check flexible hours
				$this->db->where($where);
				$f_q = $this->db->get("flexible_hours");
				$f_r = $f_q->row();
				if($f_r){
					if($f_r->not_required_login == 0){
						return ($f_r->latest_time_in_allowed != "" || $f_r->latest_time_in_allowed != NULL) ? $f_r->latest_time_in_allowed : FALSE ;	
					}else{
						// check workshift schedule
						$this->db->where($where);
						$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
						$w_q = $this->db->get("split_schedule");
						$w_r = $w_q->row();
						return ($w_r) ? $w_r->start_time : FALSE ;
					}
				}else{
					return FALSE;
				}
			}
		}
		
		/**
		 * Update Employee Time In Logs	
		 * @param unknown_type $emp_id
		 * @param unknown_type $employee_time_in_id
		 */
		public function get_total_hours_logs($comp_id, $emp_id, $employee_time_in_id, $reason, $time_in,$lunch_out,$lunch_in,$time_out, $hours_worked){			
			
			if($time_in != NULL){
				// tardiness
				$tardiness = get_tardiness_import($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in);
				
				// undertime
				$undertime = get_undertime_import($emp_id, $comp_id, $time_in, $time_out, $lunch_out, $lunch_in);
				
				// total hours worked
				$total_hours_worked = get_tot_hours_limit($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out);
				
				// total hours worked view
				$total_hours_worked_view = get_tot_hours($emp_id, $comp_id, $time_in, $lunch_out, $lunch_in, $time_out, $hours_worked);
				
				return $total_hours_worked;
			}else{
				return FALSE;
			}
			
		}
	
		/**
		 * Get Approval Leave
		 * @param string $leave_id
		 * @return boolean or row
		 */
		public function get_approval_leave($leave_id = NULL){
			
			if($leave_id != NULL){
				$this->db->where('leave_id',$leave_id);
				$q = $this->db->get('approval_leave');
				$r = $q->row();
				return ($r) ? $r: FALSE ;
			}else{
				return false;
			}
		}
		/**
		 * Get Approval Overtime
		 * @param string $ot_id
		 * @return boolean or row
		 */
		public function get_approval_ot($ot_id = NULL){
				
			if($ot_id != NULL){
				$this->db->where('overtime_id',$ot_id);
				$q = $this->db->get('approval_overtime');
				$r = $q->row();
				return ($r) ? $r: FALSE ;
			}else{
				return false;
			}
		}
		/**
		 * Get global approver if level is null
		 * @param string $table = table name
		 * @param string $column = column name
		 * @param string $id
		 */
		public function get_approver($table, $column, $id){
			$this->db->where($column,$id);
			$this->edb->join('employee AS e','approver_id = e.emp_id','LEFT');
			$q = $this->edb->get($table);
			$r = $q->row();
			return ($r) ? $r->first_name: FALSE;
		}
		
		/**
		 * Get defendents info (ashima 2015)
		 * @param null :D
		 * @param null :D
		 * @param null :D
		 */
		
		public function get_dependents($emp_id){
			$res = array();
			
			$this->db->where('emp_id',$emp_id);
			$q = $this->db->get('employee_qualifid_dependents');
			$r = $q->result();
			
			if($r) {
				foreach ($r as $row) {
					$years_old = $this->getAge($row->dob); //strtotime(date('Y-m-d')) - $row->dob;
					$temp = array(
							"dependents_name" => $row->dependents_name,
							"company_id" => $row->company_id,
							"dob" => $years_old,
							"relation" => $row->relation
					);
			
					array_push($res, $temp);
				}
				
				return $res;
			} else {
				return false;
			}
		}
		
		/**
		 * Get Holiday Total over the year, awh ha? (ashima 2015)
		 * @param null
		 * @param null
		 * @param null
		 */
		
		public function get_holidays($comp_id){
			$where = array(
					'company_id' => $comp_id,
					'date >' => date('Y-m-d')
			);
			
			$this->db->where($where);
			$q = $this->db->get('holiday');
			$r = $q->result();
				
			return ($r) ? $r : FALSE;
		}
		public function total_holidays($comp_id){
			$where = array(
					'company_id' => $comp_id
			);
				
			$this->db->where($where);
			$query = $this->db->get('holiday');
			$num_row = $query->num_rows();
		
			return $num_row ? $num_row : 0;
			
		}
		
		/**
		 *
		 * Checks the profile information
		 * @param int $account_id
		 * @return object
		 */
		public function profile_info($account_id)
		{
			$where = array(
					//"status"  	 => "Active",
					"account_id" => $account_id
			);
			$this->edb->where($where);
				
			$query = $this->edb->get("accounts");
			$row = $query->row();
			$query->free_result();
			return $row;
		}
		
		/**
		 * Updates databases
		 * Enter description here ...
		 * @param unknown_type $database
		 * @param unknown_type $fields
		 * @param unknown_type $where
		 */
		public function update_fields($database,$fields,$where){
			$this->edb->where($where);
			$this->edb->update($database,$fields);
			return $this->db->affected_rows();
		}
		
		/**
		 * Update profile photos only
		 * @param int $emp_id
		 * @param string $photo
		 * @return boolean
		 */
		public function update_profile_photos($account_id,$photo){
			if(is_numeric($account_id) && $photo){
				$photo_where = array("account_id"=>$account_id);
				$photo_field = array("profile_image"=>$photo);
				return $this->update_fields("accounts",$photo_field,$photo_where);
			}else{
				return false;
			}
		}
		
		public function insert_to_table($table_name,$data){
			$insert = $this->edb->insert($table_name,$data);
			if($insert){
				return $this->db->insert_id();
			}else{
				return false;
			}
		}
		
		//ADDED NEW FUNCTIONALITIES
		
		public function check_if_have_sched($emp_id){
			
			$w = array("emp_id"=>$emp_id);
			$this->edb->where($w);
			$this->db->where("status","Active");
			$q = $this->edb->get("employee_payroll_information");
			$r = $q->row();
			$payroll_group_id = $r->payroll_group_id;
			// get work schedule id
			$w_ws = array("emp_id"=>$emp_id);
			$this->db->where($w);
			$this->db->where("status","Active");
			$q_ws = $this->db->get("employee_shifts_schedule");
			$r_ws = $q_ws->row();
			$work_schedule_id = ($r_ws) ? $r_ws->work_schedule_id : "" ;
			
			if($q_ws->num_rows() > 0){
				return TRUE;
				
			}else{
				$w_pg = array("payroll_group_id"=> $payroll_group_id);
				$this->db->where($w_pg);
				$this->db->where("status","Active");
				$q_pg =  $this->db->get("payroll_group");
				$r_pg = $q_pg->row();
				if($r_pg){
					if($r_pg->work_schedule_id != NULL || $r_pg->work_schedule_id != 0){
						return TRUE;
					}else{
						
					}
				}else{
					return FALSE;
				}
				
			}
				
		}
		/**
	`		 * Check Work Schedule
		 * @param unknown_type $id
		 * @param unknown_type $company_id
		 * @param unknown_type $type
		 */
		public function work_schedule_type($work_schedule_id, $comp_id){
			$w = array(
					"work_schedule_id" => $work_schedule_id,
					"comp_id"=> $comp_id
			);
			$this->db->where($w);
			$q = $this->db->get('work_schedule');
			$r = $q->row();
			
			return ($r) ? $r->work_type_name : FALSE;
		}
	
		/**
		 * Get Approver Name Overtime
		 * @param unknown_type $comp_id
		 */
		public function get_approver_shifts($emp_id,$comp_id){
			
			$w = array(
					'epi.emp_id'=>$emp_id,
					'epi.company_id'=>$comp_id
			);
			$this->edb->where($w);
			$this->edb->join("employee AS e","epi.emp_id = e.emp_id","left");
			$this->edb->join("accounts AS a","e.account_id = a.account_id","left");
			$q = $this->edb->get("employee_payroll_information AS epi");
			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		public function deminimis($comp_id){
			$w = array(
					"company_id"=>$comp_id,
					"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get('deminimis');
			$result = $q->result();
			
			return ($result) ? $result : false;
		}
		public function get_employee_deminimis($emp_id,$id,$period_from,$period_to,$payroll_period){
			$w = array(
					"emp_id"=>$emp_id,
					"de_minimis_id"=>$id,
					"period_from"=>$period_from,
					"period_to"=>$period_to,
					"payroll_period"=>$payroll_period
			);
			$this->db->where($w);
			$q = $this->db->get("payroll_de_minimis");
			$r = $q->result();
			return ($r) ? $r : FALSE ;
		}
		
		public function get_employee_thirteenth_month($emp_id, $comp_id){
			$w = array(
				"emp_id" => $emp_id,
				"company_id"=> $comp_id	
			);
			$this->db->where($w);
			$q = $this->edb->get("employee_thirteenth_month");
			$r = $q->result();
			return ($r) ? $r : FALSE ;
		}
		
		public function get_employee_service_charge($acc_id, $comp_id, $sort_by = "", $sort){
			$sort_array = array(
					"cut_off_from",
					"employee_hours_worked",
					"employees_share",
					"service_charge_allocation_amount"
			);
			
			$w = array(
					'escd.account_id' => $acc_id,
					'escd.company_id' => $comp_id
			);
			$this->db->where($w);
			$this->db->join("employee_service_charge AS esc","esc.employee_service_charge_id = escd.employee_service_charge_id","LEFT");
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			}
			$q = $this->db->get("employee_service_charge_detail AS escd");
			$r = $q->result();
			
			return ($r) ? $r : FALSE ;
		}
		/**
		 * Check Employee Government Loans
		 * @param unknown $emp_id
		 * @param unknown $company_id
		 */
		public function check_employee_government_loans($emp_id,$company_id, $sort_by="", $sort){
			$sort_array = array(
					"loan_type_name",
					"gld.principal_amount",
					"remittance_due",
					"gld.loan_term",
					"date_granted"
			);
			
			$s = array(
					"*",
					"gld.first_remittance_date AS first_remittance_date",
					"gld.remittance_due AS remittance_due",
					"gld.remittance_scheme AS remittance_scheme",
					"gld.payment_schedule AS payment_schedule",
					"gld.loan_term AS loan_term",
			);
			$this->db->select($s);
			$w = array(
					"gld.emp_id"=>$emp_id,
					"gld.company_id"=>$company_id,
					"gld.status"=>"Active"
			);
			$this->db->where($w);
			$this->db->join("government_loans AS gl","gl.loan_type_id = gld.loan_type_id","LEFT");
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			}
			$q = $this->db->get("gov_loans_deduction AS gld");
			$r = $q->result();
			return ($r) ? $r : FALSE ;
		}
		public function de_minimis_settings($emp_id, $company_id, $sort_by = "", $sort){
			$sort_array = array(
					"dm.deminimis_type",
					"dm.rate_type",
					"edm.custom_amount",
					"edm.frequency",
					"dm.excess",
					"dm.max_non_taxable_amount"
			);
				

			$w = array(

					"edm.emp_id"=>$emp_id,
					"edm.company_id"=>$company_id,
					"edm.status"=>"Active"
			);
			$this->db->where($w);

			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			}
			$this->edb->join("deminimis AS dm","edm.de_minimis_id = dm.deminimis_id","LEFT");
			$q = $this->edb->get("employee_de_minimis AS edm");
			$r = $q->result();
			return ($r) ? $r : FALSE ;
		}
		public function employee_allowances($emp_id, $comp_id, $sort_by = "", $sort){
			$sort_array = array(
					"name",
					"efa.applicable_daily_rates",
					"efa.hourly_rate",
					"efa.daily_rate",
					"efa.allowance_amount"
			);
			$s = array("*","efa.pay_out_schedule AS pay_out_schedule");
			$this->db->select($s);
			$w = array(
				"efa.emp_id"=>$emp_id,
				"efa.company_id"=>$comp_id,
				"efa.status"=>"Active",
				"als.status"=>"Active"
			);
			$this->db->where("(efa.date_filed IS NULL)");
			$this->db->where($w);
			$this->db->join("allowance_settings AS als","als.allowance_settings_id = efa.allowance_settings_id","LEFT");
			//$q = $this->db->get("employee_fixed_allowances AS efa");
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			}
			$q = $this->db->get("employee_allowances AS efa");
			$r = $q->result();
			return ($q->num_rows() > 0) ? $r : FALSE ;
		}
		
		public function employee_commissions($emp_id, $company_id, $sort_by, $sort){
			$sort_array = array(
					"cs.commission_plan",
					"c.commission_scheme",
					"c.percentage_rate",
					"c.schedule_date"
			);
			
			$s = array(
					"*","c.commission_scheme","c.amount","c.percentage","c.percentage_rate","c.commission_schedule","c.pay_schedule","c.schedule_date"
			);
			$this->db->select($s);
			$w = array(
					"c.emp_id"=>$emp_id,
					"c.company_id"=>$company_id,
					"c.status"=>"Active"
			);
			$this->db->where($w);
			$this->db->join("commission_settings AS cs","cs.commission_settings_id = c.commission_settings_id","LEFT");
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			}
			$q = $this->db->get("commissions AS c");
			$r = $q->result();
			return ($r) ? $r : FALSE ;
		}
		public function employee_third_party_loans($emp_id,$company_id, $sort_by, $sort){
			$sort_array = array(
					"lt.loan_type_name",
					"el.principal_amount",
					"el.date_granted"
			);
				
			$w = array(
					"el.emp_id"=>$emp_id,
					"el.company_id"=>$company_id,
					"el.status"=>"Active"
			);
			
			$s = array(
					"*","el.principal_amount AS principal","el.beginning_balance AS beginning","el.first_remittance_date AS first_remittance","el.date_granted AS granted_date"
			);
			
			$this->db->select($s);
			$this->db->where($w);
		//	$this->db->join('loans_deductions AS ld','el.loan_no = ld.loan_number','LEFT');
			$this->edb->join("loan_type AS lt","lt.loan_type_id = el.loan_type_id","LEFT");
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			}
			$q = $this->edb->get("loans_deductions AS el");
			$r = $q->result();
			return ($r) ? $r : FALSE ;
		}
		public function get_payslip_commission($emp_id, $company_id, $pay_period, $period_from, $period_to){
			$w = array(
					"emp_id"=>$emp_id,
					"company_id"=>$company_id,
					"payroll_period" => $pay_period,
					"period_from" => $period_from,
					"period_to" => $period_to,
					"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get('payroll_commission');
			$r = $q->row();
			
			return ($r) ? $r->amount : 0;
		}
		
		/**
		 * Get Number of days before which the leave application should be submitted
		 * Enter description here ...
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $leave_type_id
		 */
		public function get_employee_payslip_hours($emp_id, $comp_id, $pay_period, $period_from, $period_to){
			$w = array(
					"emp_id"=>$emp_id,
					"company_id"=>$comp_id,
					"payroll_period" => $pay_period,
					"period_from" => $period_from,
					"period_to" => $period_to,
					"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get('payroll_employee_hours');
			$r = $q->result();
			
			return ($r) ? $r : FALSE;
		}
		

		public function get_holiday_today($date_start,$emp_id,$comp_id){
		    
		    
		    $date_start = date("Y-m-d",strtotime($date_start));
		    $date_start_d = date("d",strtotime($date_start));
		    $date_start_m = date("m",strtotime($date_start));
		    
		    $w = array(
		        'company_id'=> $comp_id,
		        'MONTH(date)'=> $date_start_m,
		        'DAY(date)'=> $date_start_d,
		        'status' => 'Active'
		    );
		    $this->db->where($w);
		    $q = $this->db->get('holiday');
		    $r = $q ->row();
		    #p($r);
		    if($r) {
		        #if($r->repeat_type == "yes") {
		        if($r->date_type == "fixed") {
		            return ($r) ? $r: FALSE;
		        } else {
		            if($date_start == $r->date) {
		                return ($r) ? $r: FALSE;
		            } else {
		                return FALSE;
		            }
		        }
		    } else {
		        return FALSE;
		    }
		    
		    
			/*$date_start = date("Y-m-d",strtotime($date_start));
				
			$w = array(
					'company_id'=> $comp_id,
					'date'=> $date_start,
					'status' => 'Active'
			);
			$this->db->where($w);
			$q = $this->db->get('holiday');
			$r = $q ->row();
			return ($r) ? $r: FALSE;*/
		}
		
		/** Added - Fritz - Start  **/
		/**
		 * get birthdays for supervisor
		 * @param unknown $company_id
		 * @return Ambigous <multitype:boolean number , multitype:unknown NULL >|boolean
		 */
		public function get_birthdays_for_supervisor($company_id, $emp_id){
			$now = date("Y-m-d");
			$pc_where = array(
					"cut_off_from <=" => $now,
					"cut_off_to >=" => $now,
					"company_id" => $company_id
			);
			$this->db->where($pc_where);
			$pc_query = $this->db->get("payroll_calendar");
			$pc_row = $pc_query->row();
		
			if($pc_row){
				$where = array(
						"e.company_id" => $company_id,
						"epi.employee_status" => "Active",
						"edrt.parent_emp_id" => $emp_id
				);
				$this->db->where($where);
				$this->db->where("DATE_FORMAT(e.dob, '%c-%d') BETWEEN DATE_FORMAT('".date("Y-m-d",strtotime($pc_row->cut_off_from))."', '%c-%d') AND DATE_FORMAT('".date("Y-m-d",strtotime($pc_row->cut_off_to))."', '%c-%d')");
				$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
				$this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
				$this->edb->join("position AS p","p.position_id = epi.position","LEFT");
				$this->db->order_by("e.dob", "asc");
				$query = $this->edb->get("employee AS e");
				$result = $query->result();
		
				return ($result) ? array($result, $query->num_rows()) : array(false, 0);
			}
			else{
				return false;
			}
		}
		
		/**
		 * get anniversaries for supervisor
		 * @param unknown $company_id
		 * @return Ambigous <multitype:boolean number , multitype:unknown NULL >|boolean
		 */
		public function get_anniversaries_for_supervisor($company_id, $emp_id){
			$now = date("Y-m-d");
			$pc_where = array(
					"cut_off_from <=" => $now,
					"cut_off_to >=" => $now,
					"company_id" => $company_id
			);
			$this->db->where($pc_where);
			$pc_query = $this->db->get("payroll_calendar");
			$pc_row = $pc_query->row();
		
			if($pc_row){
				$where = array(
						"e.company_id" => $company_id,
						"epi.employee_status" => "Active",
						"edrt.parent_emp_id" => $emp_id
				);
				$this->db->where($where);
				$this->db->where("DATE_FORMAT(epi.date_hired, '%c-%d') BETWEEN DATE_FORMAT('".date("Y-m-d",strtotime($pc_row->cut_off_from))."', '%c-%d') AND DATE_FORMAT('".date("Y-m-d",strtotime($pc_row->cut_off_to))."', '%c-%d')");
				$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
				$this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
				$this->edb->join("position AS p","p.position_id = epi.position","LEFT");
				$query = $this->edb->get("employee AS e");
				$result = $query->result();
		
				return ($result) ? array($result, $query->num_rows()) : array(false, 0);
			}
			else{
				return false;
			}
		}
		
		/**
		 * Employee Time In List with sorting
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 */
		public function missing_timesheet_list($comp_id, $emp_id, $manager_id, $type){
			#$period = next_pay_period($emp_id, $comp_id);
			$w = array(
					'date' => date('Y-m-d', strtotime('-1 day')),
					'emp_id'=>$emp_id,
					'comp_id'=> $comp_id,
					'status'=> 'Active'
			);
			$this->db->where($w);
			if($type == 'pending' || $type == 'reject'){
				$this->db->where('time_in_status',$type);
			}
			if($type == 'undertime'){
				$u_where = array('undertime_min >'=> 0);
				$this->db->where($u_where);
			}
			if($type == 'tardiness'){
				$t_where = array('tardiness_min >'=> 0);
				$this->db->where($t_where);
			}
			#$this->db->order_by('date','DESC');
			$sql = $this->db->get('employee_time_in');
				//last_query();
			if($sql->num_rows() > 0){
				$results = $sql->result();
				$sql->free_result();
				return $results;
			}else{
				return FALSE;
			}
		}
		
		public function check_employee_work_schedule($flag_date,$emp_id,$company_id){
			$CI =& get_instance();
			$w = array(
					"emp_id"=>$emp_id,
					"company_id"=>$company_id,
					"valid_from <="=>$flag_date,
					"until >="=>$flag_date,
					"status"=>"Active",
					"payroll_group_id" => 0
			);
			$CI->db->where($w);
			$q = $CI->db->get("employee_shifts_schedule");
			$r = $q->row();
			if($r){
				return $r;
			}else{
				$w = array(
						'epi.emp_id'=> $emp_id
				);
				$CI->db->where($w);
				$CI->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','LEFT');
				$q_pg = $CI->edb->get('employee_payroll_information AS epi');
				$r_pg = $q_pg->row();
		
				return ($r_pg) ? $r_pg: FALSE;
			}
		}

		public function check_employee_leave_application_mod($emp_id, $min='', $max='') {
			$w = array(
				"emp_id"=>$emp_id,
				"status" => "Active"
			);
            $this->db->where($w);

            if($min != "" && $max != ""){
                $w1 = array(
                    "date >="		=> $min,
                    "date <="		=> $max,
                );
                $this->db->where($w1);
            } else {
            	$w1 = array(
                    "date"		=> $min,
                );
                $this->db->where($w1);
            }
            
			$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
			$this->db->where("(time_in IS NOT NULL and time_out IS NOT NULL)");
			$this->db->where("( tardiness_min > 0 or undertime_min > 0 or absent_min > 0 )");
			$q = $this->db->get("employee_time_in");
			$r = $q->result();
            // last_query();
			if ( ! $r) { return false; }

			// $leave_apps = $this->get_custom_leaves_applications($emp_id);
			// if ( ! $leave_apps) { false; }

			$holder = array();
			foreach ($r as $row) {
				$data = array();
				// $get_leave = in_array_custom($emp_id."_".$row->date."_".$row->date, $leave_apps);

				$w2 = array(
					"emp_id"=>$emp_id,
					"DATE(date_start)" => $row->date,
					"DATE(date_end)" => $row->date,
					"status" => "Active",
					"leave_application_status"=>"approve"
				);
				$this->db->where($w2);
				$q2 = $this->db->get("employee_leaves_application");
				$get_leave = $q2->row();
				$q2->free_result();

				if ($get_leave) {
					if($row->tardiness_min > $row->undertime_min){
						$data = array(
							"tardiness"=>"1",
							"undertime"=>"",
							"absent" => "1",
							"credited"=>$get_leave->credited,
						    "exclude_lunch_break" => $get_leave->exclude_lunch_break
						);
					} elseif ($row->undertime_min > $row->absent_min) {
						$data = array(
							"tardiness"=>"",
							"undertime"=>"1",
							"absent" => "",
						    "credited"=>$get_leave->credited,
						    "exclude_lunch_break" => $get_leave->exclude_lunch_break
						);
					} else {
						$data = array(
							"tardiness"=>"",
							"undertime"=>"",
							"absent" => "1",
						    "credited"=>$r2->credited,
						    "exclude_lunch_break" => $get_leave->exclude_lunch_break
						);
					}
				} else {
					$date_end = date("Y-m-d",strtotime($row->date." +1 day"));
					// $get_leave2 = in_array_custom($row->emp_id."_".$row->date."_".$date_end, $leave_apps);

					// $date_end = date("Y-m-d",strtotime($date." +1 day"));
					$ns_where = array(
						"emp_id"=>$emp_id,
						"DATE(date_start)" => $row->date,
						// "DATE(date_end)" => $date,
						"DATE(date_end)" => $date_end,
						"status" => "Active",
						"leave_application_status"=>"approve"
					);
					$this->db->where($ns_where);
					$ns_q = $this->db->get("employee_leaves_application");
					$get_leave2 = $ns_q->row();
					$ns_q->free_result();

					if ($get_leave2) {
						if($row->tardiness_min > $row->undertime_min){
							$data = array(
								"tardiness"=>"1",
								"undertime"=>"",
								"absent" => "1",
								#"credited"=>$r2->credited
							    "credited"=>$get_leave2->credited,
							    "exclude_lunch_break" => $get_leave2->exclude_lunch_break
							);
						} elseif ($row->undertime_min > $row->absent_min) {
							$data = array(
								"tardiness"=>"",
								"undertime"=>"1",
								"absent" => "",
								#"credited"=>$r2->credited
							    "credited"=>$get_leave2->credited,
							    "exclude_lunch_break" => $get_leave2->exclude_lunch_break
							);
						} else {
							$data = array(
								"tardiness"=>"",
								"undertime"=>"",
								"absent" => "1",
								#"credited"=>$r2->credited
							    "credited"=>$get_leave2->credited,
							    "exclude_lunch_break" => $get_leave2->exclude_lunch_break
							);
						}
					} else {
						$date_start = date("Y-m-d",strtotime($row->date." -1 day"));
						// $get_leave3 = in_array_custom($row->emp_id."_".$date_start."_".$row->date, $leave_apps);
						// $date_start = date("Y-m-d",strtotime($date." -1 day"));
						$ns_where = array(
							"emp_id"=>$emp_id,
							// "DATE(date_start)" => $date,
							"DATE(date_start)" => $date_start,
							"DATE(date_end)" => $row->date,
							"status" => "Active",
							"leave_application_status"=>"approve"
						);
						$this->db->where($ns_where);
						$ns_q = $this->db->get("employee_leaves_application");
						$get_leave3 = $ns_q->row();
						$ns_q->free_result();

						if ($get_leave3) {
							if($row->tardiness_min > $row->undertime_min){
								$data = array(
									"tardiness"=>"1",
									"undertime"=>"",
									"absent" => "1",
								    "credited"=>$get_leave3->credited,
								    "exclude_lunch_break" => $get_leave3->exclude_lunch_break
								);
							} elseif ($row->undertime_min > $row->absent_min) {
								$data = array(
									"tardiness"=>"",
									"undertime"=>"1",
									"absent" => "",
								    "credited"=>$get_leave3->credited,
								    "exclude_lunch_break" => $get_leave3->exclude_lunch_break
								);
							} else {
								$data = array(
									"tardiness"=>"",
									"undertime"=>"",
									"absent" => "1",
								    "credited"=>$get_leave3->credited,
								    "exclude_lunch_break" => $get_leave3->exclude_lunch_break
								);
							}
						}
					}
				}
				if ($data) {
					$data['q'] = $emp_id."_".$row->date;
					array_push($holder, $data);
				}
			}
			return $holder;
		}

		public function get_custom_leaves_applications($emp_id) {
			$s = array(
				'credited',
				'exclude_lunch_break',
				"DATE(date_start) AS date_start_year",
				"DATE(date_end) AS date_end_year"
			);
			$w2 = array(
				"emp_id"=>$emp_id,
				"status" => "Active",
				"leave_application_status"=>"approve"
			);
			$this->db->select($s);
			$this->db->where($w2);
			$q2 = $this->db->get("employee_leaves_application");
			$r2 = $q2->result();
			// p($r2); exit();
			if ($r2) {
				$holder = array();
				foreach ($r2 as $val) {
					$temp = (array)$val;
					// p($val);
					$temp['q'] = $emp_id."_".$val->date_start_year."_".$val->date_end_year;
					array_push($holder, $temp);
				}
				return $holder;
			}

			return false;
		}
		
		/**
		 * Check Employee Leave Application
		 * @param unknown_type $date
		 * @param unknown_type $emp_id
		 */
		public function check_employee_leave_application($emp_id,$date){
			$w = array(
				"emp_id"=>$emp_id,
				"date" => $date,
				"status" => "Active",
			);
			$this->db->where($w);
			$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
			$q = $this->db->get("employee_time_in");
			$r = $q->row();
			$q->free_result();
			
			if($r){
				if($r->time_in != NULL && $r->time_out != NULL){
					if($r->tardiness_min > 0 || $r->undertime_min > 0 || $r->absent_min > 0){
						// DAY SHIFT
						$w2 = array(
							"emp_id"=>$emp_id,
							"DATE(date_start)" => $date,
							"DATE(date_end)" => $date,
							"status" => "Active",
							"leave_application_status"=>"approve"
						);
						$this->db->where($w2);
						$q2 = $this->db->get("employee_leaves_application");
						$r2 = $q2->row();
						$q2->free_result();
						if($r2){
							if($r->tardiness_min > $r->undertime_min){
								$data["info"] = array(
									"tardiness"=>"1",
									"undertime"=>"",
									"absent" => "1",
									"credited"=>$r2->credited,
								    "exclude_lunch_break" => $r2->exclude_lunch_break
								);
							} elseif ($r->undertime_min > $r->absent_min) {
								$data["info"] = array(
									"tardiness"=>"",
									"undertime"=>"1",
									"absent" => "",
								    "credited"=>$r2->credited,
								    "exclude_lunch_break" => $r2->exclude_lunch_break
								);
							} else {
								$data["info"] = array(
									"tardiness"=>"",
									"undertime"=>"",
									"absent" => "1",
								    "credited"=>$r2->credited,
								    "exclude_lunch_break" => $r2->exclude_lunch_break
								);
							}
							return $data;
						}else{
							// NIGHT SHIFT - HALFDAY BEFORE LUNCH OUT
							// $date = date("Y-m-d",strtotime($date." +1 day"));
							$date_end = date("Y-m-d",strtotime($date." +1 day"));
							$ns_where = array(
								"emp_id"=>$emp_id,
								"DATE(date_start)" => $date,
								// "DATE(date_end)" => $date,
								"DATE(date_end)" => $date_end,
								"status" => "Active",
								"leave_application_status"=>"approve"
							);
							$this->db->where($ns_where);
							$ns_q = $this->db->get("employee_leaves_application");
							$ns_r = $ns_q->row();
							$ns_q->free_result();
							if($ns_r){
								#if(date("A",strtotime($r->time_in)) == "PM" && date("A",strtotime($ns_r->date_end)) == "AM"){
								if($r->tardiness_min > $r->undertime_min){
									$data["info"] = array(
										"tardiness"=>"1",
										"undertime"=>"",
										"absent" => "1",
										#"credited"=>$r2->credited
									    "credited"=>$ns_r->credited,
									    "exclude_lunch_break" => $ns_r->exclude_lunch_break
									);
								} elseif ($r->undertime_min > $r->absent_min) {
									$data["info"] = array(
										"tardiness"=>"",
										"undertime"=>"1",
										"absent" => "",
										#"credited"=>$r2->credited
									    "credited"=>$ns_r->credited,
									    "exclude_lunch_break" => $ns_r->exclude_lunch_break
									);
								} else {
									$data["info"] = array(
										"tardiness"=>"",
										"undertime"=>"",
										"absent" => "1",
										#"credited"=>$r2->credited
									    "credited"=>$ns_r->credited,
									    "exclude_lunch_break" => $ns_r->exclude_lunch_break
									);
								}
									return $data;
								#}else{
								#	return FALSE;
								#}
							}else{
								// NIGHT SHIFT - HALFDAY AFTER LUNCH IN
								// $date = date("Y-m-d",strtotime($date." -1 day"));
								$date_start = date("Y-m-d",strtotime($date." -1 day"));
								$ns_where = array(
									"emp_id"=>$emp_id,
									// "DATE(date_start)" => $date,
									"DATE(date_start)" => $date_start,
									"DATE(date_end)" => $date,
									"status" => "Active",
									"leave_application_status"=>"approve"
								);
								$this->db->where($ns_where);
								$ns_q = $this->db->get("employee_leaves_application");
								$ns_r = $ns_q->row();
								$ns_q->free_result();
								if($ns_r){
									#if(date("A",strtotime($ns_r->date_start)) == "PM" && date("A",strtotime($r->time_out)) == "AM"){
									if($r->tardiness_min > $r->undertime_min){
										$data["info"] = array(
											"tardiness"=>"1",
											"undertime"=>"",
											"absent" => "1",
										    "credited"=>$r2->credited,
										    "exclude_lunch_break" => $r2->exclude_lunch_break
										);
									} elseif ($r->undertime_min > $r->absent_min) {
										$data["info"] = array(
											"tardiness"=>"",
											"undertime"=>"1",
											"absent" => "",
										    "credited"=>$r2->credited,
										    "exclude_lunch_break" => $r2->exclude_lunch_break
										);
									} else {
										$data["info"] = array(
											"tardiness"=>"",
											"undertime"=>"",
											"absent" => "1",
										    "credited"=>$r2->credited,
										    "exclude_lunch_break" => $r2->exclude_lunch_break
										);
									}
										return $data;
									#}else{
									#	return FALSE;
									#}
								}else{
									return FALSE;
								}
							}
						}
					}else{
						return FALSE;
					}
				} else {
				    return FALSE;
				}
			}else{
				return FALSE;
			}
		}
		
		/** Added - Fritz - End  **/
// 		public function get_leave_type_id($comp_id,$emp_id,$leave_type_id){
// 			$date = date("Y-m-d");
// 			$w = array(
// 					"el.company_id"		=> $comp_id,
// 					"el.emp_id"			=> $emp_id,
// 					"lt.leave_type_id" 	=> $leave_type_id,
// 					"el.as_of <= "		=> $date,
// 					"el.status"			=> "Active"
// 			);
			
// 			$sel = array(
// 					"lt.num_days_before_leave_application",
// 					"lt.num_consecutive_days_allowed",
// 					"lt.exclude_weekends"
// 			);
			
// 			$this->db->where($w);
// 			$this->db->select($sel);
// 			$this->db->join("leave_type lt","lt.leave_type_id = el.leave_type_id","left");
// 			$q = $this->db->get("employee_leaves AS el");
// 			$result = $q->row();
			
// 			return ($result) ? $result : false;
// 			//return $result;
			
// 			//return ($q->num_rows() > 0) ? $result : FALSE ;
// 		}

		/**
		 * get out of office
		 * @param unknown $company_id
		 */
		public function out_of_office($company_id, $emp_id){
// 			$now = date("Y-m-d");
// 			$where = array(
// 					"e.company_id" => $company_id,
// 					"DATE(eti.date) !=" => $now,
// 					"edrt.parent_emp_id" => $emp_id,
// 					"epi.employee_status" => 'Active'
// 			);
// 			$this->db->where($where);
			
			
			
			
			$where = array(

					"ws.work_type_name"=>"Uniform Working Days",
					"e.company_id" => $company_id,
					"edrt.parent_emp_id" => $emp_id,
					"rs.work_start_time <= " => date("H:i:s"),
					"rs.work_end_time >= " => date("H:i:s"),
					"rs.days_of_work" => date("l"),
					"epi.employee_status" => "Active"

			);
			$this->db->where($where);
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
			$this->edb->join("regular_schedule AS rs","rs.work_schedule_id = ws.work_schedule_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->edb->join("position AS p","p.position_id = epi.position","LEFT");
			$this->db->group_by("e.emp_id");
			$query = $this->edb->get("employee AS e");
			
			
			
// 			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
// 			$this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
// 			$this->edb->join("employee_time_in AS eti","eti.emp_id = e.emp_id","LEFT");
// 			$this->db->group_by('e.emp_id');
// 			$query = $this->edb->get("employee AS e");
			$result = $query->result();
			//last_query();
			
			#$counter = 0;
			$new_array = array();
			if($result){
				foreach($result as $row){
					
					$custom_sched = check_employee_custom_schedule($row->emp_id);
						
					if($custom_sched){
						$work_sched_id = $custom_sched->work_schedule_id;
							
						$w = array(
								"work_schedule_id" => $work_sched_id,
								"work_start_time <= " => date("H:i:s"),
								"work_end_time >= " => date("H:i:s"),
								"days_of_work" => date("l")
						);
						$query = $this->db->get("regular_schedule");
						$r = $query->row();
						
						if($r){
							$emp_id = $row->emp_id;
							$w = array(
									"emp_id" => $emp_id,
									"date =" => date("Y-m-d"),
									"status" => "Active"
							);
							$this->db->where($w);
							$time_in = $this->db->get("employee_time_in");
							$time_in_row = $time_in->row();
							
							if(!$time_in_row){
								$start_time = $r->work_start_time;
								$end_time = $r->work_end_time;
								if($start_time != "" && $end_time != ""){
									if(strtotime($start_time) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
										if(!check_employee_on_leave($row->emp_id)){
											#$counter++;
											$res_array = array(
												'first_name' => $row->first_name,
												'last_name' => $row->last_name,
												'account_id' => $row->account_id,
												'position_name' => $row->position_name
											);
																						
											array_push($new_array, $res_array);
											
										}
									}
								}
							}
						}
					}
					else{
						$emp_id = $row->emp_id;
						$w = array(
								"emp_id"=>$emp_id,
								"date"=>date("Y-m-d"),
								"status"=>"Active"
						);
						$this->db->where($w);
						$time_in = $this->db->get("employee_time_in");
						$time_in_row = $time_in->row();
						
						if(!$time_in_row){
							$start_time = $row->work_start_time;
							$end_time = $row->work_end_time;
							if($start_time != "" && $end_time != ""){
								if(strtotime($start_time) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
									if(!check_employee_on_leave($row->emp_id)){
										
										$res_array = array(
												'first_name' => $row->first_name,
												'last_name' => $row->last_name,
												'account_id' => $row->account_id,
												'position_name' => $row->position_name
										);
										
										array_push($new_array, $res_array);
										#$counter++;
									}
								}
							}
						}
					}
				}
			}
			
			return $new_array;
			#return ($new_res) ? array($new_res, $query->num_rows()) : array(false, 0);
		}
		
		public function generate_list_of_blocks($employee_time_in){
			$w_uwd = array(
					"eti.employee_time_in_id"=>$employee_time_in,
					"eti.status" => 'Active'
			);
			$this->db->where($w_uwd);
		
			$arr = array(
					'schedule_blocks_time_in_id' => 'eti.schedule_blocks_time_in_id',
					'time_in' => 'eti.time_in',
					'lunch_out' => 'eti.lunch_out',
					'lunch_in' => 'eti.lunch_in',
					'time_out' => 'eti.time_out',
					'total_hours_required' => 'eti.total_hours_required',
					'total_hours' => 'eti.total_hours',
					'tardiness_min'  => 'eti.tardiness_min',
					'undertime_min' => 'eti.undertime_min',
					'absent_min' => 'eti.absent_min',
					'source' => 'eti.source',
					'date' => 'eti.date',
					'payroll_cloud_id' => 'a.payroll_cloud_id',
					'employee_time_in_id' => 'eti.employee_time_in_id',
					'comp_id' => 'eti.comp_id',
					'first_name' => 'e.first_name',
					'last_name' => 'e.last_name',
					'department_id' => 'epi.department_id',
					'schedule_blocks_id' => 'eti.schedule_blocks_id',
					'change_log_time_in' => 'eti.change_log_time_in',
					'change_log_lunch_out' => 'eti.change_log_lunch_out',
					'change_log_lunch_in' => 'eti.change_log_lunch_in',
					'change_log_time_out' => 'eti.change_log_time_out',
					'change_log_tardiness_min' => 'eti.change_log_tardiness_min',
					'change_log_undertime_min' => 'eti.change_log_undertime_min',
					'change_log_total_hours_required' => 'eti.change_log_total_hours_required',
					'time_in_status' => 'eti.time_in_status',
					'reason' => 'eti.reason',
					'notes' => 'eti.notes',
					'work_schedule_id' => 'eti.work_schedule_id',
					'change_log_total_hours' => 'eti.change_log_total_hours',
					'overbreak_min' => 'eti.overbreak_min',
					'late_min' => 'eti.late_min'
			);
			$this->edb->select($arr);
			$arr2 = array(
					'emp_id' => 'eti.emp_id',
					'account_id' => 'a.account_id'
			);
			$this->edb->select($arr2);
			/*$this->db->select('CONCAT('.$this->edb->decrypt('e.first_name').'," ",'.
			 $this->edb->decrypt('e.last_name').') as full_name',FALSE);*/
			$this->edb->join('employee AS e','e.emp_id = eti.emp_id',"INNER");
			$this->edb->join('accounts AS a','a.account_id = e.account_id',"INNER");
			$this->edb->join('employee_payroll_information AS epi','epi.emp_id = eti.emp_id','left');
			$this->edb->order_by("e.last_name","ASC");
			$q = $this->edb->get('schedule_blocks_time_in AS eti');
		
			$r= $q->result();
		
		
			return ($q->num_rows() > 0) ? $r : false;
		}
		
		/**
		 * Get Employee Time In Information
		 * @param unknown_type $comp_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $timein_id
		 */
		public function get_split_timein_info($comp_id, $emp_id, $timein_id){
			$where = array(
					'comp_id' => $comp_id,
					'emp_id' => $emp_id,
					'schedule_blocks_time_in_id' => $timein_id,
					'status' => 'Active'
			);
			
			$this->db->where($where);
			$q = $this->db->get('schedule_blocks_time_in');
			$r = $q->row();
			$q->free_result();
			
			return ($r) ? $r : FALSE;
		}
			
		public function list_of_blocks($currentdate,$emp_id,$work_schedule_id,$comp_id,$select = array()){
			$w_date = array(
					"em.valid_from <="		=>	$currentdate,
					"em.until >="			=>	$currentdate
			);
			
			$this->db->where($w_date);
			
			$w_ws = array(
					"em.work_schedule_id" => $work_schedule_id,
					"em.company_id"=>$comp_id,
					"em.emp_id" => $emp_id
			);
			$this->db->where($w_ws);
		
			if($select){
				$this->edb->select($select);
			}
		
			$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
			$this->edb->join("schedule_blocks AS sb","sb.schedule_blocks_id = em.schedule_blocks_id","LEFT");
			$q_ws = $this->edb->get("employee_sched_block AS em");
			$r_ws = $q_ws->result();
			
			if($r_ws)
				return $r_ws;
			else
				return false;
		}
		
		public function for_leave_hoursworked_break($emp_id,$comp_id,$workday,$work_schedule){
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
				return $row_hw->break_in_min;
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
				return $row_f->duration_of_lunch_break_per_day;
			}
		}
		
		public function check_break_by_sched_blocks($schedule_blocks_id){
			$where = array(
					"schedule_blocks_id" => $schedule_blocks_id
			);
			
			$this->db->where($where);
			$q = $this->db->get("schedule_blocks");
			$r = $q->row();
			$q->free_result();
				
			return ($r) ? $r : FALSE;
		}
		
		public function get_split_timein_logs($employee_timein_id, $emp_id, $comp_id, $date){
			$where = array(
					"employee_time_in_id" => $employee_timein_id,
					"emp_id" => $emp_id,
					"comp_id" => $comp_id,
					"date" => $date
			);
			
			$this->db->where($where);
			$q = $this->db->get("schedule_blocks_time_in");
			$r = $q->result();
			
			return ($r) ? $r : FALSE;
		}
		
		public function get_uniform_sched_time($work_schedule_id,$emp_id,$company_id,$valid_from) {
			$where = array(
					"work_schedule_id"	=> $work_schedule_id,
					"emp_id" 			=> $emp_id,
					"company_id" 		=> $company_id,
					"valid_from" 		=> $valid_from,
					"until" 			=> $valid_from
			);
				
			$this->db->where($where);
			$q = $this->db->get("employee_shifts_schedule");
			$r = $q->row();
			
			if($r) {
				$where = array(
						"company_id" 		=> $company_id,
						"work_schedule_id"	=> $work_schedule_id,
						"days_of_work"		=> date("l", strtotime($valid_from))
				);
				
				$this->db->where($where);
				$q3 = $this->db->get("regular_schedule");
				$r3 = $q3->row();
				
				return ($r3) ? $r3 : FALSE;
			} else {
				$where = array(
						"company_id" 		=> $company_id,
						"work_schedule_id"	=> $work_schedule_id,
						"days_of_work"		=> date("l", strtotime($valid_from))
				);
				
				$this->db->where($where);
				$q2 = $this->db->get("regular_schedule");
				$r2 = $q2->row();
				
				return ($r2) ? $r2 : FALSE;
			}
			
			
		}
		
		public function get_flex_sched_time($work_schedule_id,$emp_id,$company_id,$valid_from){
			$where = array(
					"work_schedule_id"	=> $work_schedule_id,
					"emp_id" 			=> $emp_id,
					"company_id" 		=> $company_id,
					"valid_from" 		=> $valid_from,
					"until" 			=> $valid_from
			);
			
			$this->db->where($where);
			$q = $this->db->get("employee_shifts_schedule");
			$r = $q->row();
			
			if($r) {
				$where = array(
						"company_id" 		=> $company_id,
						"work_schedule_id"	=> $work_schedule_id
				);
			
				$this->db->where($where);
				$q3 = $this->db->get("flexible_hours");
				$r3 = $q3->row();
			
				return ($r3) ? $r3 : FALSE;
			} else {
				$where = array(
						"company_id" 		=> $company_id,
						"work_schedule_id"	=> $work_schedule_id
				);
			
				$this->db->where($where);
				$q2 = $this->db->get("flexible_hours");
				$r2 = $q2->row();
			
				return ($r2) ? $r2 : FALSE;
			}
		}
		
		/**
		 * Get Current Shifts
		 * @param unknown_type $id
		 * @param unknown_type $company_id
		 * @param unknown_type $type
		 */
		public function get_current_shift($work_schedule_id, $comp_id, $currentdate = "", $emp_id = ""){
			$arrs = array();
			$w = array(
				"work_schedule_id" => $work_schedule_id,
				"comp_id"=> $comp_id
			);
			$this->db->where($w);
			$q = $this->db->get('work_schedule');
			$r = $q->row();
			
			if($r) {
				if($r->work_type_name == 'Uniform Working Days') {
					$w1 = array(
						"work_schedule_id" => $work_schedule_id,
						"company_id"=> $comp_id,
						"days_of_work" => date('l', strtotime($currentdate))
					);
					
					$this->db->where($w1);
					$q1 = $this->db->get('regular_schedule');
					$r1 = $q1->row();
					
					if($r1) {
						$arr = array(
							"work_type_name" => 'Uniform Working Days',
							"work_schedule_id" => $r1->work_schedule_id,
							"start" => $r1->work_start_time,
							"end" => $r1->work_end_time,
							"total_hours_for_the_day" => 0,
							"latest_time_in_allowed" => ""
						);
						array_push($arrs, (object) $arr);
					}
					
				} elseif ($r->work_type_name == 'Flexible Hours') {
					$w2 = array(
							"work_schedule_id" => $work_schedule_id,
							"company_id"=> $comp_id
					);
						
					$this->db->where($w2);
					$q2 = $this->db->get('flexible_hours');
					$r2 = $q2->row();
					
					if($r2) {
						if($r2->latest_time_in_allowed == null) {
							$latest_time_in_allowed = "FLEXI";
						} else {
							$latest_time_in_allowed = $r2->latest_time_in_allowed;
						}
						
						$arr = array(
								"work_type_name" => 'Flexible Hours',
								"work_schedule_id" => $r2->work_schedule_id,
								"start" => $latest_time_in_allowed,
								"end" => "",
								"total_hours_for_the_day" => $r2->total_hours_for_the_day,
								"latest_time_in_allowed" => $r2->latest_time_in_allowed,
								"not_required_login" => $r2->not_required_login
						);
						array_push($arrs, (object) $arr);
					}
					
				} elseif ($r->work_type_name == 'Workshift') {
					$w3 = array(
							"sb.work_schedule_id" => $work_schedule_id,
							"sb.company_id"=> $comp_id,
							"ess.valid_from" => date('Y-m-d', strtotime($currentdate)),
							"ess.emp_id" => $emp_id
					);
					
					$this->db->where($w3);
					#$this->db->order_by("start_time","ASC");
					$this->db->join("employee_sched_block AS esb","sb.schedule_blocks_id = esb.schedule_blocks_id","LEFT");
					$this->db->join("employee_shifts_schedule AS ess","ess.shifts_schedule_id = esb.shifts_schedule_id","LEFT");
					$q3 = $this->db->get('schedule_blocks AS sb');					
					$r3 = $q3->result();
					#last_query();
					if($r3) {
						$new_date = date("Y-m-d");
						$new_datetime = date("Y-m-d H:i:s");
						foreach ($r3 as $row) {
							if(date('A', strtotime($new_datetime)) == "PM" && date('A', strtotime($row->end_time)) == "AM") {
								$new_start_date = $new_date.' '.date('H:i:s', strtotime($row->start_time.' -120 minutes'));
								$new_end_date = date("Y-m-d", strtotime($new_date.' +1 day')).' '.date('H:i:s', strtotime($row->end_time));
								#echo $row->start_time.' '.$new_datetime;
								if(date('A', strtotime($row->start_time)) == "PM" && date('A', strtotime($row->end_time)) == "AM" && strtotime(date($new_datetime)) <= strtotime($new_start_date)) {
									$arr = array(
											"work_type_name" => 'Workshift',
											"work_schedule_id" => $row->work_schedule_id,
											"start" => $row->start_time,
											"end" => $row->end_time,
											"total_hours_for_the_day" => 0,
											"latest_time_in_allowed" => ""
									);
									array_push($arrs, (object) $arr);
								} elseif (date('A', strtotime($row->start_time)) == "PM" && date('A', strtotime($row->end_time)) == "AM") {
									$arr = array(
											"work_type_name" => 'Workshift',
											"work_schedule_id" => $row->work_schedule_id,
											"start" => $row->start_time,
											"end" => $row->end_time,
											"total_hours_for_the_day" => 0,
											"latest_time_in_allowed" => ""
									);
									array_push($arrs, (object) $arr);
								}
							}
							else {
								if(strtotime(date("Y-m-d H:i:s")) <= strtotime($new_date.' '.date('H:i:s', strtotime($row->end_time)))) {
									if(date('A', strtotime($row->start_time)) == "AM" && date('A', strtotime($row->end_time)) == "AM" || date('A', strtotime($row->start_time)) == "AM" && date('A', strtotime($row->end_time)) == "PM") {
										
										$arr = array(
												"work_type_name" => 'Workshift',
												"work_schedule_id" => $row->work_schedule_id,
												"start" => $row->start_time,
												"end" => $row->end_time,
												"total_hours_for_the_day" => 0,
												"latest_time_in_allowed" => ""
										);
										array_push($arrs, (object) $arr);
										
										
									} 
								} elseif (date('A', strtotime($new_datetime)) == "AM" && date('A', strtotime($row->start_time)) == "AM" && date('A', strtotime($row->end_time)) == "AM") {
									$arr = array(
											"work_type_name" => 'Workshift',
											"work_schedule_id" => $row->work_schedule_id,
											"start" => $row->start_time,
											"end" => $row->end_time,
											"total_hours_for_the_day" => 0,
											"latest_time_in_allowed" => ""
									);
									array_push($arrs, (object) $arr);
								}
							}
						}
					}
				}
			}
			return ($arrs) ? $arrs : FALSE;
		}
		
		
		/**
		 * Get Current Shifts
		 * @param unknown_type $id
		 * @param unknown_type $company_id
		 * @param unknown_type $type
		 */
		public function get_split_shift_info($work_schedule_id, $comp_id, $emp_id, $currentdate = ""){
			$w3 = array(
					"ess.work_schedule_id" => $work_schedule_id,
					"ess.company_id"=> $comp_id,
					"ess.emp_id" => $emp_id,
					"ess.payroll_group_id" => 0,
					"ess.valid_from" => $currentdate
			);
		
			$this->db->where($w3);
			$this->db->join("employee_sched_block AS esb","esb.shifts_schedule_id = ess.shifts_schedule_id","LEFT");
			$this->db->join("schedule_blocks AS sb","sb.schedule_blocks_id = esb.schedule_blocks_id","LEFT");
			$q3 = $this->db->get('employee_shifts_schedule AS ess');
			$r3 = $q3->result();
			
			return ($r3) ? $r3 : false;

		}
		
		/**
		 * Work Schedule ID
		 * @param unknown_type $company_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $date
		 */
		public function assigned_work_schedule($company_id,$emp_id,$date=NULL){
			
			$wd = date("l",strtotime($date));
			
			$sel = array(
				"ess.emp_id",
				"ess.shifts_schedule_id",
				"ess.work_schedule_id",
				"ws.bg_color",
				"ws.flag_custom",
				"ws.work_type_name",
				"ws.name",
				"ws.category_id"
					
			);
			$w_date = array(
				"ess.valid_from <="	=>	$date,
				"ess.until >="		=>	$date,
				"ess.payroll_group_id"	=>	"0"
			);
			if($date != NULL) 
			$this->db->where($w_date);
			
			$w = array(
				"ess.emp_id" => $emp_id,
				"ess.company_id" => $company_id,
				"ess.status" => "Active"
			);
			$this->db->select($sel);
			$this->db->where($w);
			$this->db->join("work_schedule AS ws","ws.work_schedule_id = ess.work_schedule_id","LEFT");
			$q = $this->db->get("employee_shifts_schedule AS ess");
			$r = $q->row();
			return ($r) ? $r : FALSE;
		}
		
		/**
		 * Work Schedule via Payroll Group
		 * @param unknown_type $company_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $date
		 */
		public function assigned_work_schedule_via_payroll_group($payroll_group_id, $company_id){
			$sel = array(
				"pg.work_schedule_id",
				"ws.category_id",
				"ws.name",
				"ws.work_type_name"
			);
			$w = array(
				"pg.payroll_group_id" => $payroll_group_id,
				"pg.company_id" => $company_id
			);
			$this->db->select($sel);
			$this->db->where($w);
			$this->db->join("work_schedule AS ws", "ws.work_schedule_id = pg.work_schedule_id", "INNER");
			$q = $this->db->get("payroll_group AS pg");
			$r = $q->row();
			return ($r) ? $r : FALSE;
			/* set false */
		}
		
		/**
		 * Work Schedule ID
		 * @param unknown_type $company_id
		 * @param unknown_type $emp_id
		 * @param unknown_type $date
		 */
		public function todo_work_schedule_id($company_id,$emp_id,$date=NULL){
		$w_date = array(
			"ess.valid_from <="		=>	$date,
			"ess.until >="			=>	$date
		);
		if($date != NULL) $this->db->where($w_date);
		
		$w = array(
			"ess.emp_id"=>$emp_id,
			"ess.company_id"=>$company_id,
			"ess.status"=>"Active",
			"payroll_group_id" => 0
		);
		$this->db->where($w);
		
		$arr2 = array(
				'valid_from' => 'ess.valid_from',
				'work_schedule_id' => 'ess.work_schedule_id'
		);
		$this->edb->select($arr2);
		$this->edb->join("work_schedule AS ws","ws.work_schedule_id = ess.work_schedule_id","LEFT"); // add new
		$q = $this->edb->get("employee_shifts_schedule AS ess");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Work Schedule Information
	 * @param unknown_type $company_id
	 * @param unknown_type $work_schedule_id
	 * @param unknown_type $weekday
	 */
	public function todo_work_schedule_info2($company_id,$work_schedule_id,$weekday,$currentdate = NULL,$emp_id =null,$employee_time_in_id = 0){
		$wd = date("l",strtotime($weekday));
		$break_time = 0;
	
		$uww = array(
				"uw.days_of_work"=>$wd,
				"uw.company_id"=>$company_id,
				"uw.work_schedule_id"=>$work_schedule_id,
				"uw.status"=>"Active"
		);

		$this->db->where($uww);
		$arr2 = array(
				'work_start_time' => 'uw.work_start_time',
				'work_end_time' => 'uw.work_end_time',
				'work_schedule_name' => 'uw.work_schedule_name',
				'total_work_hours' => 'uw.total_work_hours',
				'break_in_min' => 'uw.break_in_min'
		);
		$this->edb->select($arr2);
		$this->edb->join("work_schedule AS ws","ws.work_schedule_id = uw.work_schedule_id","LEFT");
		$uwq = $this->edb->get("regular_schedule AS uw");
		$uwr = $uwq->row();

		if($uwr){
			$start_time = $uwr->work_start_time;
			$end_time = $uwr->work_end_time;
			$shift_name = $uwr->work_schedule_name;
			$total_hours = $uwr->total_work_hours;
			$break_time = $uwr->break_in_min;
		}else{
			
			$w_date = array(
					"es.valid_from <="		=>	$currentdate,
					"es.until >="			=>	$currentdate
			);
			$this->db->where($w_date);
			
			
			$w_ws = array(
					//"payroll_group_id"=>$payroll_group,
					"em.work_schedule_id"=>$work_schedule_id,
					"em.company_id"=>$company_id,
					"em.emp_id" => $emp_id
			);
			$this->db->where($w_ws);
			$arrx = array(
					'schedule_blocks_id' => 'em.schedule_blocks_id'
			);
			$this->edb->select($arrx);
			$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
			$q_ws = $this->edb->get("employee_sched_block AS em");
			$r_ws = $q_ws->result();
			$data = array();
			if($q_ws->num_rows() > 0){
				
				$w = array(
						"employee_time_in_id"=>$employee_time_in_id,
						"eti.status" => "Active"
				);
				$this->edb->where($w);
				$this->db->order_by("eti.time_in","ASC");
				$split_q = $this->edb->get("schedule_blocks_time_in AS eti");
				$query_split = $split_q->result();
				$arr = array();	
			
			    foreach ($query_split as $rowx):
			    
					$split = $this->elm->get_splitschedule_info_new($rowx->time_in,$work_schedule_id,$emp_id,$company_id);
					
					if($split){				
						$start_time = date('H:i:s',strtotime($split['start_time']));
						$end_time = date('H:i:s',strtotime($split['end_time']));
						$shift_name = $split['block_name'];
					}
					
					$data[] = array(
							"start_time"=>$start_time,
							"end_time"=>$end_time,
							"shift_name"=>$shift_name,
							"break" => $split['break_in_min'],
							"total" => $split['total_hours_work_per_block']
					);
				endforeach;
	
				return $data;
			}else{
				$fw = array(
						"f.company_id"=>$company_id,
						"f.work_schedule_id"=>$work_schedule_id
				);
				$this->db->where($fw);
				$this->db->join("work_schedule AS ws","ws.work_schedule_id = f.work_schedule_id","LEFT");
				$fq = $this->db->get("flexible_hours AS f");
				$fr = $fq->row();
	
				if($fr){
					if($fr->latest_time_in_allowed != NULL || $fr->latest_time_in_allowed != ""){
						$start_time = $fr->latest_time_in_allowed;
						$end_time = "";
						$shift_name = $fr->name;						
					}else{
						$start_time = "";
						$end_time = "";
						$shift_name = $fr->name;
					
					}
				}else{
					$start_time = "";
					$end_time = "";
					$shift_name = "";
					$total_hours = "";
					$total_days_per_year = "";
				}
			}
		}
	
		$data["work_schedule"] = array(
				"start_time"=>$start_time,
				"end_time"=>$end_time,
				"shift_name"=>$shift_name,
				"break_time"=>$break_time
		);
		return $data;
	
	}
	
	public function get_employee_time_in($employee_time_in_id, $emp_id, $comp_id) {
		$where = array(
				"comp_id"				=> $comp_id,
				"emp_id"				=> $emp_id,
				"employee_time_in_id"	=> $employee_time_in_id,
				"status"				=> "Active"
		);
		$this->db->where($where);
		$q = $this->db->get('employee_time_in');
		$r = $q->row();
		
		return ($r) ? $r : FALSE;
	}
	
	public function get_schedule_blocks_time_in($schedule_blocks_time_in_id, $comp_id, $emp_id) {
		$where = array(
			"comp_id"					=> $comp_id,
			"emp_id"						=> $emp_id,
			"schedule_blocks_time_in_id"	=> $schedule_blocks_time_in_id,
			"status"						=> "Active"
		);
		
		$this->db->where($where);
		$q = $this->db->get('schedule_blocks_time_in');
		$r = $q->row();
		
		return ($r) ? $r : FALSE;
	}
	
	/**
	 * HISTORY CAN BE DELETED
	 * @param int $emp_id
	 * @param int $employee_leaves_application_id
	 */
	public function leaves_can_be_cancel($emp_id,$employee_leaves_application_id){
		if(is_numeric($this->company_id) && is_numeric($employee_leaves_application_id)) {
			$where = array(
				"company_id"						=> $this->company_id,
				"employee_leaves_application_id" 	=> $this->db->escape_str($employee_leaves_application_id),
				"status"							=> "Active",
				//"leave_application_status" 		=> "approve"
			);
			$this->db->where($where);
			$q = $this->edb->get('employee_leaves_application AS ela');
			$r = $q->row();
			$q->free_result();
			#echo last_query()." employee LEaves<Br /><br />";
			if($r) {
				$date_start = date("Y-m-d",strtotime($r->date_start));
				$date_end = date("Y-m-d",strtotime($r->date_end));
				
				/** SCENARIO 1 **/
				$dp_where = array(
					'dpr.view_status'	=>'Closed',
					'prc.emp_id'		=>$emp_id,
					'dpr.company_id'	=>$this->company_id,
					'dpr.period_from <='=>$date_start,
					'dpr.period_to >='	=>$date_end
				);
				$this->db->where($dp_where);
				$this->db->join('payroll_run_custom AS prc','prc.draft_pay_run_id = dpr.draft_pay_run_id','LEFT');
				$q_drft = $this->db->get('draft_pay_runs AS dpr');
				$scene1 = $q_drft->row();
				$q_drft->free_result();
				/** END SCENARIO 1 **/
				#echo last_query()." employee LEaves 1<Br /><br />";
				
				/** SCENARIO 2 **/
				$dbp_where2 = array(
					'dpr.view_status'	=>'Closed',
					'pp.emp_id'			=>$emp_id,
					'dpr.company_id'	=>$this->company_id,
					'dpr.period_from <='=>$date_start,
					'dpr.period_to >='	=>$date_end
				);
				$this->db->where($dbp_where2);
				$this->db->join('payroll_payslip AS pp','pp.payroll_group_id = dpr.payroll_group_id','LEFT');
				$q_drft2 = $this->db->get('draft_pay_runs AS dpr');
				$scene2 = $q_drft2->row();
				#echo last_query()." employee LEaves2<Br /><br />";
				/** END SCENARIO 2 **/
				
				if($scene1){ # CONDITION 1
					#echo '1';
					return 0;
				}else if($scene2){
					#echo '2';
					return 0;
				}else{
					return 1;
				}
			}else{
				return 0;
			}
		}else{
			return 0;
		}
	}
	
	public function get_employee_leave_application($employee_leaves_application_id, $emp_id, $company_id) {
		$where = array(
				"employee_leaves_application_id" => $employee_leaves_application_id,
				"emp_id" => $emp_id,
				"company_id" => $company_id
		);
		
		$this->db->where($where);
		$q = $this->db->get("employee_leaves_application");
		$r = $q->row();
		
		return ($r) ? $r : false;
	}
	
	public function get_credits_from_emp_leaves($leave_type_id, $emp_id, $company_id) {
		$where = array(
				"leave_type_id" => $leave_type_id,
				"emp_id" => $emp_id,
				"company_id" => $company_id
		);
		
		$this->db->where($where);
		$q = $this->db->get("employee_leaves");
		$r = $q->row();
		
		return ($r) ? $r : false;
	}
	
	/**
	 * Leave Credits
	 * @param unknown_type $comp_id
	 * @param unknown_type $emp_id
	 */
	public function leave_credits_display($comp_id,$emp_id,$leave_type_id){
		$date = date("Y-m-d");
		$w = array(
				"el.company_id"=>$comp_id,
				"el.emp_id"=>$emp_id,
				"el.as_of <= "=>$date,
				"el.status"=>"Active",
				"el.leave_type_id" => $leave_type_id
		);
		$this->db->where($w);
		$this->db->join("leave_type lt","lt.leave_type_id = el.leave_type_id","left");
		$q = $this->db->get("employee_leaves el");
		$row = $q->row();
		return ($row) ? $row : FALSE ;
	}
	
	public function check_existing_overtime_applied($emp_id, $company_id, $start_time, $end_time, $overtime_from, $overtime_to) {
	    #$new_start_time = date('H:i:01',strtotime($start_time." +1 secs"));
	    $new_start_time = date('H:i:01',strtotime($start_time));
	    $new_end_time = date('H:i:s',strtotime($end_time." -1 secs"));
	    
		$this->db->where("(overtime_from = '".$overtime_from."' AND overtime_to = '".$overtime_to."'
						AND start_time BETWEEN '".$new_start_time."' AND '".$new_end_time."' 
                        AND emp_id = '".$emp_id."' AND company_id = '".$company_id."'
                        AND status = 'Active'
                        OR (overtime_from = '".$overtime_from."' AND overtime_to = '".$overtime_to."'
						AND start_time < '".$new_start_time."' AND end_time >'".$new_end_time."'
                        AND emp_id = '".$emp_id."' AND company_id = '".$company_id."'
                        AND status = 'Active')
						AND (overtime_status = 'approved' OR overtime_status = 'pending'))
						OR (overtime_from = '".$overtime_from."' AND overtime_to = '".$overtime_to."'
						AND end_time BETWEEN '".$new_start_time."' AND '".$new_end_time."' AND emp_id = '".$emp_id."' AND company_id = '".$company_id."' 
                        AND status = 'Active'
						AND (overtime_status = 'approved' OR overtime_status = 'pending')
				)");
		
		$q = $this->db->get("employee_overtime_application");
		$res = $q->row();
		#last_query();exit();
		
		return ($res) ? $res : FALSE;
	}
	
	public function check_existing_leave_applied($emp_id, $company_id, $date_start, $date_end, $is_flexi = false) {
	    if ($is_flexi) {
	        $this->db->where("((cast(date_start AS date) BETWEEN '".date("Y-m-d", strtotime($date_start))."' AND '".date("Y-m-d", strtotime($date_end))."' 
                        OR cast(date_end AS date) BETWEEN '".date("Y-m-d", strtotime($date_start))."' AND '".date("Y-m-d", strtotime($date_end))."')
                        AND emp_id = '".$emp_id."' AND company_id = '".$company_id."'
						AND (leave_application_status = 'approve' OR leave_application_status = 'pending')
						AND (flag_parent = 'yes' OR flag_parent = 'no') AND status = 'Active')
						OR ((cast(date_end AS date) BETWEEN '".date("Y-m-d", strtotime($date_start))."' AND '".date("Y-m-d", strtotime($date_end))."' 
                        OR cast(date_end AS date) BETWEEN '".date("Y-m-d", strtotime($date_start))."' AND '".date("Y-m-d", strtotime($date_end))."') AND emp_id = '".$emp_id."' AND company_id = '".$company_id."'
						AND (leave_application_status = 'approve' OR leave_application_status = 'pending')
						AND (flag_parent = 'yes' OR flag_parent = 'no') AND status = 'Active'
						)");
	        $q = $this->db->get("employee_leaves_application");
	        $res = $q->row();
	    } else {
	        $this->db->where("(date_start BETWEEN '".$date_start."' AND '".$date_end."' AND emp_id = '".$emp_id."' AND company_id = '".$company_id."'
						AND (leave_application_status = 'approve' OR leave_application_status = 'pending')
						AND (flag_parent = 'yes' OR flag_parent = 'no') AND status = 'Active')
						OR (date_end BETWEEN '".$date_start."' AND '".$date_end."' AND emp_id = '".$emp_id."' AND company_id = '".$company_id."'
						AND (leave_application_status = 'approve' OR leave_application_status = 'pending')
						AND (flag_parent = 'yes' OR flag_parent = 'no') AND status = 'Active'
						)");
	        $q = $this->db->get("employee_leaves_application");
	        $res = $q->row();
	    }
	    
		return ($res) ? $res : FALSE;
	}
	
	public function get_emp_contributions($company_id, $emp_id, $type="", $sort_by="", $sort, $limit = 10, $start = 0)
	{
		$konsum_key = konsum_key();
		
		$sort_array = array(
				"pp.pay_date",
				"pp.pp_sss",
				"pp.pp_pagibig",
				"pp.pp_philhealth",
				"pp.pp_withholding_tax"
		);
		
		
		
		if($type == "sss") {
			$where1 = array(
					"e.company_id" 				=> $company_id,
					"e.status" 					=> 'Active',
					"a.deleted" 				=> '0',
					"pp.emp_id" 				=> $emp_id,
					"pp.flag_prev_emp_income"	=> '0',
					"pp.pp_sss !=" 				=> "0"
			);
			
			$this->edb->where($where1);
			
		} else if ($type == "phic") {
			$where1 = array(
					"e.company_id" 				=> $company_id,
					"e.status" 					=> 'Active',
					"a.deleted" 				=> '0',
					"pp.emp_id" 				=> $emp_id,
					"pp.flag_prev_emp_income"	=> '0',
					"pp.pp_philhealth !=" 		=> "0"
			);
			
			$this->edb->where($where1);
		}  else if($type == "hdmf") {
			$where1 = array(
					"e.company_id" 				=> $company_id,
					"e.status" 					=> 'Active',
					"a.deleted" 				=> '0',
					"pp.emp_id" 				=> $emp_id,
					"pp.flag_prev_emp_income"	=> '0',
					"pp.pp_pagibig !=" 			=> "0"
			);
			$this->edb->where($where1);
			
		} else if ($type == "tax") {
			$where = array(
					"e.company_id" 				=> $company_id,
					"e.status" 					=> 'Active',
					"a.deleted" 				=> '0',
					"pp.emp_id" 				=> $emp_id,
					"pp.flag_prev_emp_income"	=> '0',
					#"pp.pp_sss >" => "0"
			);
			$this->db->where($where);
		} else {
			$where = array(
					"e.company_id" 				=> $company_id,
					"e.status" 					=> 'Active',
					"a.deleted" 				=> '0',
					"pp.emp_id" 				=> $emp_id,
					"YEAR(pp.payroll_date)"		=> date('Y'),
					"pp.flag_prev_emp_income"	=> '0'
					#"pp.pp_sss >" => "0"
			);
			
			$this->edb->or_where("(AES_DECRYPT(pp.pp_sss,'{$konsum_key}') != 0 && AES_DECRYPT(pp.pp_philhealth,'{$konsum_key}') != 0 && AES_DECRYPT(pp.pp_pagibig,'{$konsum_key}') != 0)");
			$this->db->where($where);
		}
		
		$w = array(
		    "YEAR(pp.payroll_date)" => date('Y'),
		);
		
		$this->db->where($w);
		
		// ADDED BY ATHAN: 2019-06-20: last author fritz
		// if($this->input->get("debug") == 1){
		  $this->db->where(" (dpr.view_status = 'Closed') ",FALSE,FALSE);
		// }
		
		$this->edb->join('employee AS e','e.account_id=a.account_id','inner');
		$this->edb->join('payroll_payslip AS pp','e.emp_id=pp.emp_id','inner');
		
		// ADDED BY ATHAN: 2019-06-20: last author fritz
		// if($this->input->get("debug") == 1){
		  $this->edb->join('draft_pay_runs AS dpr','dpr.payroll_group_id = pp.payroll_group_id AND dpr.pay_period = pp.payroll_date','inner');
		// }
		
		if($sort_by != ""){
			if(in_array($sort_by, $sort_array)){
				$this->db->order_by($sort_by,$sort);
			}
		} else {
			$this->db->order_by("payroll_date","desc");
		}
		
		$query = $this->edb->get('accounts AS a',$limit,$start);
		$result = $query->result();
		#last_query();
		return $result;
	
	}
	
	public function get_emp_contributions_count($company_id,$emp_id,$type="")
	{
		$konsum_key = konsum_key();
		
		if($type == "sss") {
			$where = array(
					"e.company_id" 				=> $company_id,
					"e.status" 					=> 'Active',
					"a.deleted" 				=> '0',
					"pp.emp_id" 				=> $emp_id,
					"pp.flag_prev_emp_income"	=> '0',
					"pp.pp_sss !=" 				=> "0"
			);
		} else if ($type == "phic") {
			$where = array(
					"e.company_id" 				=> $company_id,
					"e.status" 					=> 'Active',
					"a.deleted" 				=> '0',
					"pp.emp_id" 				=> $emp_id,
					"pp.flag_prev_emp_income"	=> '0',
					"pp.pp_philhealth !=" 		=> "0"
			);
		}  else if ($type == "hdmf") {
			$where = array(
					"e.company_id" 				=> $company_id,
					"e.status" 					=> 'Active',
					"a.deleted" 				=> '0',
					"pp.emp_id" 				=> $emp_id,
					"pp.flag_prev_emp_income"	=> '0',
					"pp.pp_pagibig !=" 			=> "0"
			);
		} else if ($type == "tax") {
			$where = array(
					"e.company_id" 				=> $company_id,
					"e.status" 					=> 'Active',
					"a.deleted" 				=> '0',
					"pp.emp_id" 				=> $emp_id,
					"pp.flag_prev_emp_income"	=> '0',
					#"pp.pp_sss >" => "0"
			);
			$this->db->where($where);
		} else {
			$where = array(
					"e.company_id" 				=> $company_id,
					"e.status" 					=> 'Active',
					"a.deleted" 				=> '0',
					"pp.emp_id" 				=> $emp_id,
					"pp.flag_prev_emp_income"	=> '0',
					#"pp.pp_sss >" => "0"
			);
			
			$this->edb->or_where("(AES_DECRYPT(pp.pp_sss,'{$konsum_key}') != 0 && AES_DECRYPT(pp.pp_philhealth,'{$konsum_key}') != 0 && AES_DECRYPT(pp.pp_pagibig,'{$konsum_key}') != 0)");
		}
		
		$w = array(
				"YEAR(pp.payroll_date)" => date('Y'),
		);
		
		$this->db->where($w);
		$query = $this->edb->where($where);
		$this->edb->join('employee AS e','e.account_id=a.account_id','inner');
		$this->edb->join('payroll_payslip AS pp','e.emp_id=pp.emp_id','inner');
		$this->db->order_by("payroll_date","desc");
		$query = $this->edb->get('accounts AS a');
		$result = $query->result();
		return $query->num_rows;
	
	}
	
	public function wt_fixed($company_id, $emp_id, $payroll_period, $period_from, $period_to) {
		$sel = array(
				'amount'
		);
		
		$where = array(
				"company_id" 		=> $company_id,
				"emp_id" 			=> $emp_id,
				"status" 			=> 'Active',
				"payroll_period"	=> $payroll_period,
				"period_from" 		=> $period_from,
				"period_to" 		=> $period_to,
		
		
		);
		
		$this->db->where($where);
		$this->db->select($sel);
		$query = $this->db->get('payroll_wttax_fixed');
		$result = $query->row();
		
		return ($result) ? $result->amount : 0;
	}
	
	public function check_existing_timein($emp_id, $company_id, $date, $time_in, $time_out) {
		$new_date = date("Y-m-d", strtotime($date));
		$time_in = date('Y-m-d H:i:s', strtotime($time_in));
		$time_out =	date('Y-m-d H:i:s', strtotime($time_out));
		$where = array(
				"emp_id" 			=> $emp_id,
				"comp_id" 			=> $company_id,
				"date" 				=> $new_date,
				"status" 			=> "Active"
		);
		
		$this->db->where($where);
		// pasensya na sa manual query mao naabut sako utok ug sa deadline LoL
		$this->db->where("((time_in >= '{$time_in}' AND time_in <= '{$time_out}' AND emp_id = {$emp_id} AND status = 'Active') 
						OR (time_out >= '{$time_in}' AND time_out <= '{$time_out}' AND emp_id = {$emp_id} AND status = 'Active') 
						OR (time_in <= '{$time_in}' AND time_out >= '{$time_out}' AND emp_id = {$emp_id} AND status = 'Active'))");
		$this->db->where("(time_in_status != 'reject' OR time_in_status is null)");
		$query = $this->db->get("employee_time_in");
		$res = $query->row();
		
		// u can change the "true" result to array data as result
		return ($res) ? true : false;
	}
	
	public function check_overlapping_logs($emp_id, $company_id, $date, $time_in, $time_out) {
		$new_date = date("Y-m-d", strtotime($date));
		$time_in = date('Y-m-d H:i:s', strtotime($time_in));
		$time_out =	date('Y-m-d H:i:s', strtotime($time_out));
		$where = array(
				"emp_id" 			=> $emp_id,
				"comp_id" 			=> $company_id,
			"date" 				=> $new_date,
				"status" 			=> "Active"
		);
	
		$this->db->where($where);
		// pasensya na sa manual query mao naabut sako utok ug sa deadline LoL
		$this->db->where("((time_in >= '{$time_in}' AND time_in <= '{$time_out}' AND emp_id = {$emp_id} AND status = 'Active')
			OR (time_out >= '{$time_in}' AND time_out <= '{$time_out}' AND emp_id = {$emp_id} AND status = 'Active')
			OR (time_in <= '{$time_in}' AND time_out >= '{$time_out}' AND emp_id = {$emp_id} AND status = 'Active'))");
		$this->db->where("(time_in_status != 'reject' OR time_in_status is null)");
		$query = $this->db->get("employee_time_in");
		$res = $query->result();
		
		if($res) {
			if(count($res) > 1) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	public function check_if_excess_logs($emp_id, $company_id, $employee_time_in_id) {
		$where = array(
				"emp_id" 					=> $emp_id,
				"comp_id" 					=> $company_id,
				"employee_time_in_id"		=> $employee_time_in_id,
				"status" 					=> "Active",
				"flag_regular_or_excess"	=> "excess"
		);
		
		$this->db->where($where);
		$query = $this->db->get("employee_time_in");
		$res = $query->row();
		
		return ($res) ? true : false;
	}
	
	public function check_pending_timesheet_for_double_entry($emp_id, $company_id, $date) {
		$new_date = date("Y-m-d", strtotime($date));
		
		$where = array(
				"emp_id" 			=> $emp_id,
				"comp_id" 			=> $company_id,
				"date" 				=> $new_date,
				"status" 			=> "Active",
				"time_in_status"	=> "pending",
				"last_source"		=> "Adjusted"
		);
	
		$this->db->where($where);
		$query = $this->db->get("employee_time_in");
		$res = $query->result();
		
		return ($res) ? true : false;
	}
	
	public function check_if_timeout_is_null($emp_id, $company_id, $date) {
		$new_date = date("Y-m-d", strtotime($date));
	
		$where = array(
				"emp_id" 			=> $emp_id,
				"comp_id" 			=> $company_id,
				"date" 				=> $new_date,
				"status" 			=> "Active",
				"time_out"			=> null
		);
	
		$this->db->where($where);
		$query = $this->db->get("employee_time_in");
		$res = $query->result();
		
		return ($res) ? true : false;
	}
	
	public function check_existing_timein_date($emp_id, $company_id, $date) {
		$new_date = date("Y-m-d", strtotime($date));
		$where = array(
				"emp_id" 			=> $emp_id,
				"comp_id" 			=> $company_id,
				"date" 				=> $new_date,
				"status" 			=> "Active"
		);
	
		$this->db->where($where);
		$this->db->where("(time_in_status != 'reject' OR time_in_status is null)");
		$query = $this->db->get("employee_time_in");
		$res = $query->row();
		
		return ($res) ? true : false;
	}
	
	public function get_timein_now_for_btn($emp_id,$comp_id) {
		// CHECK WORK SCHEDULE
		$arrx2 = array(
				'work_scheduel_id'	=> 'eti.work_schedule_id',
				'time_in' 			=> 'eti.time_in',
				'time_out' 			=> 'eti.time_out',
				'date' 				=> 'eti.date'
		);
		
		$this->db->select($arrx2);
		
		$w = array(
				#"a.payroll_cloud_id"	=>$emp_no,
				#"a.user_type_id"		=>"5",
				"eti.comp_id" 			=> $comp_id,
		          "eti.emp_id" 			=> $emp_id,
				"eti.status"			=> "Active"
		);
		
		$this->db->where($w);
		#$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
		#$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		#$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
		$this->db->order_by("eti.time_in","DESC");
		$q = $this->db->get("employee_time_in AS eti",1,0);
		
		if($q->num_rows() < 0){
			$arrx2 = array(
					'work_scheduel_id'	=> 'sbti.work_schedule_id',
					'time_in' 			=> 'sbti.time_in',
					'time_out' 			=> 'sbti.time_out',
					'date' 				=> 'sbti.date'
			);
			
			$this->db->select($arrx2);
			
			$w = array(
					#"a.payroll_cloud_id"	=>$emp_no,
					#"a.user_type_id"		=>"5",
					"sbti.comp_id" 			=> $comp_id,
			         "sbti.emp_id" 			=> $emp_id,
					"sbti.status"			=> "Active"
			);
			
			$this->db->where($w);
			#$this->edb->join("employee AS e","sbti.emp_id = e.emp_id","INNER");
			#$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			#$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
			$this->db->order_by("sbti.time_in","DESC");
			$q = $this->db->get("schedule_blocks_time_in AS sbti",1,0);
			$r = $q->row();
			
			return  ($r) ? $r : false;
		} else {
			$r = $q->row();
			return  ($r) ? $r : false;
		}
		
		
	}
	
	public function get_time_ins($date,$emp_id) {
		$where = array(
				'date'=> $date,
				'emp_id' =>$emp_id
		);
		$this->db->where($where);
		$this->db->order_by("time_in","DESC");
		$q = $this->db->get('employee_time_in');
		$r = $q->row();
			
		return ($r) ? $r : FALSE;
	}
	
	public function get_payroll_deminimis_fritz($emp_id,$draft_pay_run_id,$payroll_date,$period_from,$period_to,$company_id,$de_minimis_id){
    	$w = array(
    		"emp_id"			=> $emp_id,
    		"period_from"		=> $period_from,
    		"period_to"			=> $period_to,
    		"payroll_period"	=> $payroll_date,
    		"company_id"		=> $company_id,
    		"de_minimis_id" 	=> $de_minimis_id,
    		#"status"=>"Active",
    	);
    	$this->db->where($w);
    	$q = $this->edb->get("payroll_de_minimis");
    	$r = $q->result();
    	return ($r) ? $r : FALSE ;
    }
    
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
    
    public function cancel_leave_by_admin($emp_id,$employee_leaves_application_id){
    	if(is_numeric($this->company_id) && is_numeric($employee_leaves_application_id)) {
    		$where = array(
    				"company_id"  						=> $this->company_id,
    				"employee_leaves_application_id" 	=> $this->db->escape_str($employee_leaves_application_id),
    				"status"   							=> "Active",
    				"leave_application_status" 			=> "approve"
    		);
    		$this->db->where($where);
    		$q = $this->edb->get('employee_leaves_application AS ela');
    		$r = $q->row();
    		$q->free_result();
    		
    		if($r) {
    			$date_start = date("Y-m-d",strtotime($r->date_start));
    			$date_end = date("Y-m-d",strtotime($r->date_end));
    			 
    			/* SCENARIO 1 */
    			$dp_where = array(
    					'dpr.view_status' 	=> 'Closed',
    					'prc.emp_id'  		=> $emp_id,
    					'dpr.company_id' 	=> $this->company_id
    			);
    			$this->db->select(array('dpr.view_status','dpr.period_from','dpr.period_to'));
    			$this->db->where($dp_where);
    			$this->db->join('payroll_run_custom AS prc','prc.draft_pay_run_id = dpr.draft_pay_run_id','LEFT');
    			 
    			$q_drft = $this->db->get('draft_pay_runs AS dpr');
    			$scene1 = $q_drft->result();
    			$q_drft->free_result();
    			 
    			$scene1_flag = false;
    			if($scene1){
    				foreach($scene1 as $k1){    
    					$flag_date_array = array();
    					$pfrom = $k1->period_from;
    					$pto = $k1->period_to;
    					 
    					while(strtotime($pfrom)<=strtotime($pto)){
    
    					$flag_date_array[date("Y-m-d",strtotime($pfrom))] = $pfrom;
    						 
    						if(isset($flag_date_array[$date_start])){
    						$scene1_flag = true;
    						break;
    					}
    					 
    					if(isset($flag_date_array[$date_end])){
    						$scene1_flag = true;
    						break;
    					}
    					$pfrom = date("Y-m-d",strtotime("+1 day",strtotime($pfrom)));
    					}
    					 
    				}
    			}
    			
    			/* END SCENARIO 1 */
    			#echo last_query()." employee LEaves 1<Br /><br />";
    			
    			/* SCENARIO 2 */
    			$dbp_where2 = array(
    					'dpr.view_status'	=> 'Closed',
    					'pp.emp_id'   		=> $emp_id,
    					'dpr.company_id' 	=> $this->company_id
    			);
    			
    			$this->db->where($dbp_where2);
      			$this->db->select(array('dpr.company_id','dpr.period_from','dpr.period_to'));
          		$this->db->join('payroll_payslip AS pp','pp.payroll_group_id = dpr.payroll_group_id AND pp.payroll_date = dpr.pay_period','LEFT');
     
          		$q_drft2 = $this->db->get('draft_pay_runs AS dpr');
      			$scene2 = $q_drft2->result();
			   	$q_drft2->free_result();
			   	
          		/* END SCENARIO 2 */
   
      			$scene2_flag = false;
	          	if($scene2){
		      		foreach($scene2 as $k2){
		          	 
		          		$flag_date_array2 = array();
			          	$pfrom2 = $k2->period_from;
			          	$pto2 = $k2->period_to;
			    
			          	while(strtotime($pfrom2)<=strtotime($pto2)){
			          		$flag_date_array2[date("Y-m-d",strtotime($pfrom2))] = $pfrom2;
				          	if(isset($flag_date_array2[$date_start])){
				          		$scene2_flag = true;
				          		break;
				         	}
		          			if(isset($flag_date_array2[$date_end])){
		          				$scene2_flag = true;
		          				break;
	          				}
		          			$pfrom2 = date("Y-m-d",strtotime("+1 day",strtotime($pfrom2)));
		          		}
		          	}
	          	}
          	 
	          	if($scene1_flag){ # CONDITION 1
		          	#echo '1';
		          	return 0;
	          	}else if($scene2_flag){
          			#echo '2';
          			return 0;
          		}else{
          			return 1;
          		}
    
          	}else{
          		return 0;
          	}
    	}else{
        	return 0;
       	}
	}
	
	public function get_emp_contributions_to_date($company_id, $emp_id, $pay_date)
	{
		$sel = array(
				"pp.fixed_sss",
				"pp.pp_sss",
				"pp.pagibig_contribution_type",
				"pp.pp_philhealth",
				"pp.pp_pagibig",
				"e.company_id",
				"pp.payroll_date"
		);
		
		$where = array(
				"e.company_id" 				=> $company_id,
				"e.status" 					=> 'Active',
				"a.deleted" 				=> '0',
				"pp.emp_id" 				=> $emp_id,
				"pp.flag_prev_emp_income"	=> '0',
				#"pp.payroll_date <=" 		=> $pay_date
				
		);
		
		$w = array(
				"pp.payroll_date >=" 		=> date('Y-01-01'),
				"pp.payroll_date <=" 		=> $pay_date
		);
		
		$this->db->where($w);
		
		$this->edb->select($sel);
		$this->db->where($where);
		$this->edb->join('employee AS e','e.account_id=a.account_id','LEFT');
		$this->edb->join('payroll_payslip AS pp','e.emp_id=pp.emp_id','LEFT');
		$this->db->order_by("pp.payroll_date","ASC");
		$query = $this->edb->get('accounts AS a');
		$result = $query->result();
		
		if($result) {

			$total_to_date_ss = 0;
			$total_to_date_phic = 0;
			$total_to_date_hdmf = 0;
			$test1 = 0;
			$pp_philhealth_employer_to_date = 0;
			$pp_pagibig_employer_to_date = 0;
			
			$get_basic_pay = $this->get_basic_pay($emp_id,$company_id);
			
			if($get_basic_pay->effective_date == ""){
				$bsc_pay = $get_basic_pay->current_basic_pay;
			}else{
				$bsc_pay = $get_basic_pay->new_basic_pay;
			}
			
			$test2 = 0;
			$hdmf_employer2 = 0;
			
			foreach ($result as $key=>$res) {
				$hdmf_employer1 = 0;
				
				if($res->pp_philhealth == '287.00') {
					$pp_philhealth = '287.00';
				} else {
					$pp_philhealth = $res->pp_philhealth;
				}
				
				if($res->fixed_sss == "yes") {
					$test2 = ($res->pp_sss > 0) ? $res->pp_sss : 0;
				} else {
					$test2 = $this->get_sss_table($res->pp_sss);
				}
				
				 #$this->get_flag_hdmf($company_id, $emp_id);
					
				$phic_employer = $this->get_phic_table($pp_philhealth);
				$hdmf_employer = $this->get_hdmf_table($res->company_id);
				
				if($res->pp_pagibig != 0) {
					if($hdmf_employer) {
						$percent = $hdmf_employer->hdmf_percent_base_pay_percent / 100;
						$get_flag_hdmf = $res->pagibig_contribution_type;
						if($get_flag_hdmf) {
							if($get_flag_hdmf == "fixed") {
								$hdmf_employer1 = 100;
							} elseif ($get_flag_hdmf == "basic_pay") {
								$hdmf_employer1 = $bsc_pay * $percent;
							} elseif ($get_flag_hdmf == "mixed_deduction") {
								$hdmf_employer1 = 100; //$bsc_pay * $percent + 100;
							} else {
								$hdmf_employer1 = 100;
							}
								
						} else {
							$hdmf_employer1 = 100;
						}
					} else {
						$hdmf_employer1 = 100;
					}
				}
				
				
				$total_to_date_ss				+= $res->pp_sss;
				$total_to_date_phic 			+= $res->pp_philhealth;
				$total_to_date_hdmf 			+= $res->pp_pagibig;
					
				$test1 							+= $test2;
				$pp_philhealth_employer_to_date	+= $phic_employer;
				$pp_pagibig_employer_to_date	+= $hdmf_employer1;
			}
			
			$arr = array(
					"pp_sss" 								=> $total_to_date_ss,
					"pp_sss_employer_to_date" 				=> $test1,
					"pp_sss_employer" 						=> $test2,
					"total_pp_sss_employer_to_date" 		=> $total_to_date_ss + $test1,
					"pp_philhealth" 						=> $total_to_date_phic,
					"pp_philhealth_employer_to_date" 		=> $pp_philhealth_employer_to_date,
					"pp_philhealth_employer" 				=> $phic_employer,
					"total_pp_philhealth_employer_to_date" 	=> $total_to_date_phic + $pp_philhealth_employer_to_date,
					"pp_pagibig" 							=> $total_to_date_hdmf,
					"pp_pagibig_employer_to_date" 			=> $pp_pagibig_employer_to_date,
					"pp_pagibig_employer" 					=> $hdmf_employer1,
					"total_pp_pagibig_employer_to_date" 	=> $total_to_date_hdmf + $pp_pagibig_employer_to_date
			);
			
			return $arr;
		} else {
			return 0;
		}
	}
	
	public function get_sss_table($amount) {
		
		$sel = array(
			"employer_monthly_contribution_ss"		
		);
		
		$where = array(
				"employee_ss" 	=> "{$amount}",
				"status" 		=> "Active"
		);
		
		$this->db->where($where);
		$q = $this->db->get("sss");
		$res = $q->row();
		
		return ($res) ? $res->employer_monthly_contribution_ss : 0;
	}
	
	public function get_phic_table($amount) {
		$sel = array(
				"employer_share"
		);
	
		$where = array(
				"employer_share" 	=> $amount,
				"status" 			=> "Active"
		);
	
		$this->db->where($where);
		$q = $this->db->get("phil_health");
		$r = $q->row();
	
		return ($r) ? $r->employer_share : 0;
	}
	
	public function get_hdmf_table($company_id) {
		$where = array(
				"company_id" => $company_id
		);
		
		$this->db->where($where);
		$q = $this->db->get("contribution_calculation_settings");
		$r = $q->row();
		
		return ($r) ? $r : false;
	}
	
	public function get_flag_hdmf($company_id, $emp_id) {
		$sel = array(
				"flag_hdmf"
		);
	
		$where = array(
				"company_id" => $company_id,
				"emp_id" => $emp_id
		);
	
		$this->db->select($sel);
		$this->db->where($where);
		$q = $this->db->get('employee_payroll_information');
		$r = $q->row();
		
		return ($r) ? $r->flag_hdmf : false;
	}
	
	public function contributions_box($company_id, $emp_id, $type="") {
		
		$sel = array(
				"pp.payroll_date",
				"pp.pp_sss",
				"pp.pp_philhealth",
				"pp.pp_pagibig",
				"pp.pagibig_contribution_type"
		);
		
		if($type == "sss") {
			$where = array(
					"e.company_id" 				=> $company_id,
					"e.status" 					=> 'Active',
					"a.deleted" 				=> '0',
					"pp.emp_id" 				=> $emp_id,
					"pp.flag_prev_emp_income" 	=> '0',
					"pp.pp_sss !=" 				=> "0"
			);
		} else if($type == "phic") {
			$where = array(
					"e.company_id" 				=> $company_id,
					"e.status" 					=> 'Active',
					"a.deleted" 				=> '0',
					"pp.emp_id" 				=> $emp_id,
					"pp.flag_prev_emp_income" 	=> '0',
					"pp.pp_philhealth !="		=> "0"
			);
		}  else if($type == "hdmf") {
			$where = array(
					"e.company_id" 				=> $company_id,
					"e.status" 					=> 'Active',
					"a.deleted" 				=> '0',
					"pp.emp_id" 				=> $emp_id,
					"pp.flag_prev_emp_income" 	=> '0',
					"pp.pp_pagibig !=" 			=> "0"
			);
		} else {
			$where = array(
					"e.company_id" 				=> $company_id,
					"e.status" 					=> 'Active',
					"a.deleted"					=> '0',
					"pp.emp_id" 				=> $emp_id,
					"pp.flag_prev_emp_income" 	=> '0',
					#"pp.pp_sss >" => "0"
			);
		}
		
		
		
		$this->edb->select($sel);
		$this->edb->where($where);
		$this->edb->join('employee AS e','e.account_id=a.account_id','inner');
		$this->edb->join('payroll_payslip AS pp','e.emp_id=pp.emp_id','inner');
		$this->db->order_by("pp.payroll_date", "DESC");
		$query = $this->edb->get('accounts AS a');
		$result = $query->row();
		
		if($result) {
			// static ang 287.00 ky na generate nmn sa payslip (saup man ang table b4) suppo
			if($result->pp_philhealth == '287.00') {
				$pp_philhealth = '287.00';
			} else {
				$pp_philhealth = $result->pp_philhealth;
			}
			
			$test = $this->get_sss_table($result->pp_sss);
			$phic_employer = $this->get_phic_table($pp_philhealth);
			$hdmf_employer = $this->get_hdmf_table($company_id);
			
			$get_flag_hdmf = $result->pagibig_contribution_type; #$this->get_flag_hdmf($company_id, $emp_id);
			
			$get_basic_pay = $this->get_basic_pay($emp_id,$company_id);
			
			if($get_basic_pay->effective_date == ""){
				$bsc_pay = $get_basic_pay->current_basic_pay;
			}else{
				$bsc_pay = $get_basic_pay->new_basic_pay;
			}
			
			if($hdmf_employer) {
				$percent = $hdmf_employer->hdmf_percent_base_pay_percent / 100;
				if($get_flag_hdmf) {
					if($get_flag_hdmf == "fixed") {
						$hdmf_employer1 = 100;
					} elseif ($get_flag_hdmf == "basic_pay") {
						$hdmf_employer1 = $bsc_pay * $percent;
						#echo $bsc_pay.' - '.$percent;
					} elseif ($get_flag_hdmf == "mixed_deduction") {
						$hdmf_employer1 = 100; //$bsc_pay * $percent + 100;
					} else {
						$hdmf_employer1 = 100;
					}
						
				} else {
					$hdmf_employer1 = 100;
				}
			} else {
				$hdmf_employer1 = 100;
			}
			
			$total_to_date_ss = $result->pp_sss;
			$total_to_date_phic = $result->pp_philhealth;
			$total_to_date_hdmf = $result->pp_pagibig;
			
			$arr = array(
					"my_SSS_contribution" 		=> $total_to_date_ss,
					"sss_date" 					=> $result->payroll_date,
					"pp_sss_employer"			=> $test,
					"my_PHIC_contribution" 		=> $total_to_date_phic,
					"philhealth_date" 			=> $result->payroll_date, // for formality nlng, LoL
					"pp_philhealth_employer" 	=> $phic_employer,
					"my_HDMF_contribution" 		=> $total_to_date_hdmf,
					"hdmf_date" 				=> $result->payroll_date, // for formality nlng, LoL
					"pp_hdmf_employer" 			=> $hdmf_employer1,
					
			);
			
			return $arr;
		} else {
			return 0;
		}
	}
	
	public function get_calendar_settings($comp_id)
	{
		$where = array(
				"company_id" => $comp_id
		);
		$this->db->where($where);
		$q = $this->db->get("payroll_calendar_working_days_settings");
		$r = $q->row();
		return ($r) ? $r : false;
	}
	
	public function get_working_days_settings($company_id){
		$where = array(
				"company_id" => $company_id
		);
		$this->db->where($where);
		$query = $this->db->get("payroll_calendar_working_days_settings");
		$row = $query->row();
	
		return ($row) ? $row : false;
	}
	
	public function get_basic_pay($emp_id,$comp_id){
		$where = array(
				"emp_id" => $emp_id,
				"comp_id" => $comp_id
		);
		$this->edb->where($where);
		$query = $this->edb->get("basic_pay_adjustment");
		$row = $query->row();
	
		return ($row) ? $row : false;
	}
	
	public function get_last_shift($emp_id, $company_id) {
		$where = array(
				"emp_id" => $emp_id,
				"company_id" => $company_id,
				"status" => "Active"
		);
		
		$this->db->where($where);
		$this->db->order_by("valid_from",'DESC');
		$q = $this->db->get("employee_shifts_schedule");
		
		$r = $q->row();
		
		return ($r) ? $r : false; 
	}

	public function get_all_current_time_in($comp_id, $emp_id, $min='', $max='') {
		$sel = array(
				"date",
				"time_in",
				"lunch_out",
				"lunch_in",
				"time_out"
		);
		
		$w = array(
				'et.emp_id' 	=> $emp_id,
				'et.comp_id'	=> $comp_id,
				'et.status'		=> 'Active'
        );
        
        if($min != "" && $max != ""){
            $w1 = array(
                "date >="		=> $min,
                "date <="		=> $max,
            );
            $this->db->where($w1);
        }

		$this->db->select($sel);
		$this->db->where($w);
		$sql = $this->db->get('employee_time_in AS et');
		$results = $sql->result();
		
		if ($results) {
			$holder = array();
			foreach ($results as $row) {
				$temp = (array)$row;
				$temp['q'] = $emp_id."_".$row->date;
				array_push($holder, $temp);
			}
			return $holder;
		}
		return false;
	}

	public function get_current_time_in($comp_id, $emp_id, $date){	
		$sel = array(
				"date",
				"time_in",
				"lunch_out",
				"lunch_in",
				"time_out"
		);
		
		$w = array(
				'et.emp_id' 	=> $emp_id,
				'et.comp_id'	=> $comp_id,
				'et.status'		=> 'Active',
				'et.date' 		=> $date
		);

		$this->db->select($sel);
		$this->db->where($w);
		$sql = $this->db->get('employee_time_in AS et');
		$results = $sql->row();
		
		return ($results) ? $results : false;

	}
	
	public function get_active_employees($employee_id_name="",$limit, $start, $comp_id,$emp_id,$all=false,$sort="",$order="asc")
	{
		$select = array(
			'department.department_name',
			'employee.emp_id',
			'employee.account_id',
			'employee.company_id',
			'employee.last_name',
			'employee.first_name',
			'employee.middle_name',
			'employee.dob',
			'employee.address',
			'accounts.payroll_cloud_id',
			'accounts.email',
			'accounts.login_mobile_number',
			'accounts.login_mobile_number_2',
			'accounts.telephone_number',
			'employee_payroll_information.employee_payroll_information_id',
			'employee_payroll_information.department_id',
			'employee_payroll_information.date_hired',
			'employee_payroll_information.employee_status',
			'employee.city',
			'employee.zipcode',
			'accounts.flag_primary',
			'position.position_name'
		);
		
		if($employee_id_name !==""){
			$where = array(
				'employee.company_id' => $comp_id,
				'employee.status'	  => 'Active',
				'accounts.user_type_id' => '5',
				'accounts.deleted'=>'0',
				'employee.status'=>'Active',
				'employee.deleted'=>'0',
				'edrt.parent_emp_id' => $emp_id
			);
		} else {
			$where = array(
				'employee.company_id' => $comp_id,
				'employee.status'	  => 'Active',
				'accounts.user_type_id' => '5',
				'accounts.deleted'=>'0',
				'employee.status'=>'Active',
				'employee.deleted'=>'0',
				'employee_payroll_information.employee_status' => 'Active',
				'edrt.parent_emp_id' => $emp_id
			);
		}
		
		$konsum_key = konsum_key();
		$this->edb->select($select);
		$this->db->where($where);
		if($employee_id_name !==""){
			$employee_id_name = $this->db->escape_like_str(stripslashes(clean_input($employee_id_name)));
			$where2  = array(
				'employee.company_id' => $comp_id,
				'employee.status'	  => 'Active',
				'accounts.user_type_id' => '5',
				'accounts.deleted'=>'0',
				'employee.status'=>'Active',
				'employee.deleted'=>'0',
				'edrt.parent_emp_id' => $emp_id
			);
			
			$where_str = "(convert(CONCAT(AES_DECRYPT(employee.first_name,'{$konsum_key}'),' ',AES_DECRYPT(employee.last_name,'{$konsum_key}')) using latin1) LIKE '%".$employee_id_name."%'
				OR (convert(AES_DECRYPT(accounts.payroll_cloud_id,'{$konsum_key}') using latin1)='{$employee_id_name}')
				OR (convert(AES_DECRYPT(accounts.email,'{$konsum_key}') using latin1) LIKE '%".$employee_id_name."%')
				)";
			
			$this->db->where($where2);
			$this->db->where($where_str, NULL, FALSE);
			#$this->db->where("convert(CONCAT(AES_DECRYPT(employee.first_name,'{$konsum_key}'),' ',AES_DECRYPT(employee.last_name,'{$konsum_key}')) using latin1) LIKE '%".$employee_id_name."%'", NULL, FALSE); // encrypt
			#$this->db->or_where("convert(AES_DECRYPT(accounts.payroll_cloud_id,'{$konsum_key}') using latin1)=",$employee_id_name);
			#$this->db->or_where("convert(AES_DECRYPT(accounts.email,'{$konsum_key}') using latin1) LIKE '%".$employee_id_name."%'"); // for email search
			#$this->db->where($where2);			
		}
		$this->edb->join('accounts','accounts.account_id = employee.account_id','INNER');
		$this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = employee.emp_id","LEFT");
		$this->edb->join('employee_payroll_information','employee_payroll_information.emp_id = employee.emp_id','left');
		$this->edb->join('department','department.dept_id = employee_payroll_information.department_id','left');
		$this->edb->join('position', 'position.position_id = employee_payroll_information.position', 'left');

		$this->db->order_by('CONVERT(UPPER(AES_DECRYPT(employee.last_name, "'.$konsum_key.'")) using latin1)', 'ASC', false);
		if($all == false){
			$q = $this->edb->get('employee',$limit,$start);
		}else{
			$q = $this->edb->get('employee');
		}
		
		$result = $q->result();
		return $result;
	}
	
	/**
	 * Check no of employees
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 */
	public function check_no_emp($emp_id,$comp_id=0){
		## OCTOBER 3 2016
		$where = array(
			'payroll_system_account_id'=>$this->session->userdata('psa_id')
		);
		$q = get_table_info('payroll_system_account',$where);
		return $q->no_of_employees;
		
		## end october 3 2016
		$where_id = array(
			'e.emp_id' => $emp_id
		);
		$this->edb->where($where_id);
		$check_employee = $this->edb->get("employee AS e");
		$e_row = $check_employee->row();
		$check_employee->free_result();

		if($e_row){
			$w = array(
				"e.emp_id"=>$e_row->emp_id
			);
			$this->edb->where($w);
			$this->edb->join("accounts AS a","psa.payroll_system_account_id = a.payroll_system_account_id","LEFT");
			$this->edb->join("employee AS e","a.account_id = e.account_id","LEFT");
			$q = $this->edb->get("payroll_system_account AS psa");
			$r = $q->row();
			return ($q->num_rows() > 0) ? $r->no_of_employees : FALSE ;
		}else{
			$w = array(
				"a.account_id"=>$this->session->userdata('account_id')
			);
			$this->edb->where($w);
			$this->edb->join("accounts AS a","psa.payroll_system_account_id = a.payroll_system_account_id","LEFT");
			$this->edb->join("company_owner AS co","co.account_id = a.account_id","LEFT");
			$q = $this->edb->get("payroll_system_account AS psa");
			$r = $q->row();
			return ($q->num_rows() > 0) ? $r->no_of_employees : FALSE ;
		}
	}
	
	/**
	 * No Need to Encrypt
	 * No of active users
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 */
	public function no_active_users($emp_id){
		## ADDED OCTOBER 3 2016
		$psa_id = $this->session->userdata('psa_id');
		$sql = $this->db->query("
				SELECT
				COUNT(e.emp_id) as emp_id
				FROM employee e
				INNER JOIN accounts a ON e.account_id = a.account_id
				LEFT JOIN employee_payroll_information AS epi ON epi.emp_id = e.emp_id
				WHERE
				a.payroll_system_account_id = {$psa_id}
		AND e.status = 'Active'
		AND a.user_type_id = '5'
		AND epi.employee_status = 'Active'
		");
		
		//last_query();
		$no_active_users = 0;
		if($sql->num_rows() > 0){
			$row = $sql->row();
			$sql->free_result();
			$no_active_users =  $row->emp_id;
		}
		return $no_active_users;
		## END added october 3 2016
		
		$w = array("e.emp_id"=>$emp_id);
		$this->db->where("e.status","Active");
		$w = $this->edb->where($w);
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$q = $this->edb->get("employee AS e");
		$r = $q->row();
		if($r){
			$payroll_system_account_id = $r->payroll_system_account_id;
			$sql = $this->db->query("
				SELECT 
				COUNT(e.emp_id) as emp_id
				FROM employee e
				LEFT JOIN accounts a ON e.account_id = a.account_id
				WHERE 
				a.payroll_system_account_id = {$payroll_system_account_id}
				AND e.status = 'Active'
				AND a.user_type_id = '5'
			");

			if($sql->num_rows() > 0){
				$row = $sql->row();
				$sql->free_result();
				return $row->emp_id;
			}else{
				return FALSE;
			}
		}else{

			$payroll_system_account_id = $this->session->userdata('psa_id');
			$sql = $this->db->query("
				SELECT 
				COUNT(e.emp_id) as emp_id
				FROM employee e
				LEFT JOIN accounts a ON e.account_id = a.account_id
				WHERE 
				a.payroll_system_account_id = {$payroll_system_account_id}
				AND e.status = 'Active'
				AND a.user_type_id = '5'
			");

			if($sql->num_rows() > 0){
				$row = $sql->row();
				$sql->free_result();
				return $row->emp_id;
			}else{
				return FALSE;
			}

		}
	}
	
	public function get_holiday_schedule($date_start,$emp_id,$comp_id){
		$date_start = date("Y-m-d",strtotime($date_start));
			
		$w = array(
				'hol.company_id'=> $comp_id,
				'hol.date'=> $date_start,
				'hol.status' => 'Active'
		);
		$this->db->where($w);
		$this->db->join("hours_type AS ht","ht.hour_type_id = hol.hour_type_id","LEFT");
		$q = $this->db->get('holiday AS hol');
		$r = $q->row();
		return ($r) ? $r: FALSE;
	}
	
	public function emp_time_in_information($time_in_id){
		
		$w = array(
				"eti.employee_time_in_id" => $time_in_id,
				"eti.status" => "Active"
		);
		
		$this->edb->where($w);
		$this->edb->join("approval_time_in AS ati","eti.employee_time_in_id = ati.time_in_id","LEFT");
		$this->edb->join("employee AS e","e.emp_id = eti.emp_id","LEFT");
		$q = $this->edb->get("employee_time_in AS eti");
		$r = $q->row();
		
		return ($r) ? $r : FALSE ;
	}
	
	public function leave_credits_dash($comp_id,$emp_id){
		$date = date("Y-m-d");
		$s = array(
				"el.effective_date",
				"lt.leave_type",
				"lt.leave_type_id",
				"el.leave_credits",
				"lt.leave_units",
				"el.remaining_leave_credits",	
		);
		
		$w = array(
				"el.company_id"=>$comp_id,
				"el.emp_id"=>$emp_id,
				"el.as_of <= "=>$date,
				"el.status"=>"Active"
		);
		
		$this->db->select($s);
		$this->db->where($w);
		$this->db->join("leave_type lt","lt.leave_type_id = el.leave_type_id","left");
		$q = $this->db->get("employee_leaves el");
		$row = $q->result();
		return ($q->num_rows() > 0) ? $row : FALSE ;
	}
	
	public function time_in_list_v2($limit, $start, $comp_id, $emp_id, $sort="DESC", $sort_by,$date_from="",$date_to=""){
		$sort_array = array(
				"date",
				"time_in_status"
		);
		
		$sel = array(
			"time_in_status",
			"source",
			"last_source",
			"time_in",
			"time_out",
			"lunch_out",
			"lunch_in",
    		    "break1_out",
    		    "break1_in",
    		    "break2_out",
    		    "break2_in",
			"change_log_date_filed",
			"change_log_time_in",
			"change_log_time_out",
			"change_log_lunch_out",
			"change_log_lunch_in",
    		    "change_log_break1_out",
    		    "change_log_break1_in",
    		    "change_log_break2_out",
    		    "change_log_break2_in",
			"emp_id",
			"date",
			"undertime_min",
			"tardiness_min",
			"work_schedule_id",
			"employee_time_in_id",
			"comp_id",
			"late_min",
			"overbreak_min",
			"change_log_tardiness_min",
			"change_log_undertime_min",
			"total_hours_required",
			"total_hours",
			"absent_min",
			"change_log_total_hours",
			"change_log_total_hours_required",
			"notes",
			"reason",
			"split_status",
		    "change_log_late_min",
		    "change_log_overbreak_min",
		    "change_log_absent_min"
		);
		
		$w = array(
				'et.emp_id'=>$emp_id,
				'et.comp_id'=> $comp_id,
				'et.status'=> 'Active'
		);
		
		$this->db->select($sel);
		$this->db->where($w);
		
		if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
			$this->db->where('date BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
		}
			
			
		if($sort_by != ""){
			if(in_array($sort_by, $sort_array)){
				$this->db->order_by($sort_by,$sort);
			}
		}else{
			$this->db->order_by('et.date','DESC');
		}
		
		if($start==0){	
			$sql = $this->db->get('employee_time_in AS et',$limit);
		}else{
			$sql = $this->db->get('employee_time_in AS et',$start,$limit);
		}
		
		if($sql->num_rows() > 0){
			$results = $sql->result();
			$sql->free_result();
			
			return $results;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check Work Schedule
	 * @param unknown_type $work_schedule_id
	 * @param unknown_type $check_company_id
	 */
	public function check_workday_opt_v2($check_company_id){
		$res = array();
		$w = array(
				"comp_id"=> $check_company_id
		);
		$this->edb->where($w);
	
		$arrx = array(
				'work_type_name',
				'work_schedule_id',
				'comp_id'
		);
		$this->edb->select($arrx);
		$q = $this->edb->get("work_schedule");
		$r = $q->result();
		
		if($r){
			foreach ($r as $row) {
				$temp = array(
						"work_schedule_id" => $row->work_schedule_id,
						"comp_id" => $row->comp_id,
						"work_type_name" => $row->work_type_name,
						"filter_query" => "work_schedule_id-{$row->work_schedule_id}"
				);
		
				array_push($res, $temp);
			}
			return $res;
		}
	}
	
	public function todo_work_schedule_id_v2($company_id,$emp_id){
		$res = array();
		
		$w = array(
				"ess.emp_id"=>$emp_id,
				"ess.company_id"=>$company_id,
				"ess.status"=>"Active",
				"payroll_group_id" => 0
		);
		$this->db->where($w);
	
		$arr2 = array(
				'valid_from' => 'ess.valid_from',
				'work_schedule_id' => 'ess.work_schedule_id',
				'company_id' => 'ess.company_id',
				'emp_id' => 'ess.emp_id',
		);
		$this->edb->select($arr2);
		$this->edb->join("work_schedule AS ws","ws.work_schedule_id = ess.work_schedule_id","LEFT"); // add new
		$q = $this->edb->get("employee_shifts_schedule AS ess");
		$r = $q->result();
		
		if($r){
			foreach ($r as $row) {
				$temp = array(
						"emp_id" => $row->emp_id,
						"company_id" => $row->company_id,
						"valid_from" => $row->valid_from,
						"work_schedule_id" => $row->work_schedule_id,
						"filter_query" => "date-{$row->valid_from}"
				);
		
				array_push($res, $temp);
			}
			return $res;
		}
		#return ($r) ? $r : FALSE ;
	}
	
	public function check_rest_day_v2($comp_id){
		$res = array();
		$w = array(
			#"work_schedule_id"=>$work_schedule_id,
			"company_id"=>$comp_id,
			#"rest_day"=>$workday
		);
		$this->db->where($w);
		$q = $this->db->get("rest_day");
		$r = $q->result();
		
		if($r){
			foreach ($r as $row) {
				$temp = array(
						"company_id" => $row->company_id,
						"rest_day" => $row->rest_day,
						"work_schedule_id" => $row->work_schedule_id,
						"filter_query" => "work_schedule_id-{$row->work_schedule_id}{$row->rest_day}"
				);
		
				array_push($res, $temp);
			}
			return $res;
		}
		#return ($q->num_rows() > 0) ? TRUE : FALSE ;
	}
	
	public function assigned_work_schedule_v2($company_id,$emp_id){
		$res = array();
			
		$sel = array(
				"ess.emp_id",
				"ess.shifts_schedule_id",
				"ess.work_schedule_id",
				"ws.bg_color",
				"ws.flag_custom",
				"ws.work_type_name",
				"ws.name",
				"ws.category_id",
				"ess.valid_from",
				"ess.company_id"
			
		);
		
		$w = array(
		    "ess.emp_id" => $emp_id,
		    "ess.company_id" => $company_id,
		    "ess.status" => "Active"
		);
		
		$this->db->select($sel);
		$this->db->where($w);
		$this->db->join("work_schedule AS ws","ws.work_schedule_id = ess.work_schedule_id","LEFT");
		$q = $this->db->get("employee_shifts_schedule AS ess");
		$r = $q->result();
		
		if($r){
			foreach ($r as $row) {
			    $temp = array(
			        "valid_from" => $row->valid_from,
			        "emp_id" => $row->emp_id,
			        "shifts_schedule_id" => $row->shifts_schedule_id,
			        "bg_color" => $row->bg_color,
			        "flag_custom" => $row->flag_custom,
			        "company_id" => $row->company_id,
			        "work_type_name" => $row->work_type_name,
			        "name" => $row->name,
			        "category_id" => $row->category_id,
			        "work_schedule_id" => $row->work_schedule_id,
			        "filter_query" => "date-{$row->valid_from}"
			    );
			    
				array_push($res, $temp);
			}
			return $res;
		}
		#return ($r) ? $r : FALSE;
	}
	
	public function get_holiday_schedule_v2($comp_id){
		$res = array();
		#$date_start = date("Y-m-d",strtotime($date_start));
		
		$sel = array(
				"hol.date",
				"hol.company_id",
				"hol.holiday_name",
				"ht.hour_type_name",
		          "hol.repeat_type"
		);
		
		$w = array(
				'hol.company_id'=> $comp_id,
				'hol.status' => 'Active'
		);
		
		$this->db->select($sel);
		$this->db->where($w);
		$this->db->join("hours_type AS ht","ht.hour_type_id = hol.hour_type_id","LEFT");
		$q = $this->db->get('holiday AS hol');
		$r = $q->result();
		
		if($r){
			foreach ($r as $row) {
				$temp = array(
						"date" => $row->date,
						"company_id" => $row->company_id,
						"holiday_name" => $row->holiday_name,
						"hour_type_name" => $row->hour_type_name,
				        "repeat_type" => $row->repeat_type,
						"filter_query" => "date-{$row->date}"
				);
		
				array_push($res, $temp);
			}
			return $res;
		}
		#return ($r) ? $r: FALSE;
	}
	
	public function generate_list_of_blocks_v2($emp_id){
		$res = array();
		
		$w_uwd = array(
				"eti.emp_id"=>$emp_id,
				"eti.status" => 'Active'
		);
		$this->db->where($w_uwd);
	
		$arr = array(
			'emp_id' => 'eti.emp_id',
			'schedule_blocks_time_in_id' => 'eti.schedule_blocks_time_in_id',
			'time_in' => 'eti.time_in',
			'lunch_out' => 'eti.lunch_out',
			'lunch_in' => 'eti.lunch_in',
			'time_out' => 'eti.time_out',
			'total_hours_required' => 'eti.total_hours_required',
			'total_hours' => 'eti.total_hours',
			'tardiness_min'  => 'eti.tardiness_min',
			'undertime_min' => 'eti.undertime_min',
			'absent_min' => 'eti.absent_min',
			'source' => 'eti.source',
			'date' => 'eti.date',
			'payroll_cloud_id' => 'a.payroll_cloud_id',
			'employee_time_in_id' => 'eti.employee_time_in_id',
			'comp_id' => 'eti.comp_id',
			'first_name' => 'e.first_name',
			'last_name' => 'e.last_name',
			'department_id' => 'epi.department_id',
			'schedule_blocks_id' => 'eti.schedule_blocks_id',
			'change_log_time_in' => 'eti.change_log_time_in',
			'change_log_lunch_out' => 'eti.change_log_lunch_out',
			'change_log_lunch_in' => 'eti.change_log_lunch_in',
			'change_log_time_out' => 'eti.change_log_time_out',
			'change_log_tardiness_min' => 'eti.change_log_tardiness_min',
			'change_log_undertime_min' => 'eti.change_log_undertime_min',
			'change_log_total_hours_required' => 'eti.change_log_total_hours_required',
			'time_in_status' => 'eti.time_in_status',
			'reason' => 'eti.reason',
			'notes' => 'eti.notes',
			'work_schedule_id' => 'eti.work_schedule_id',
			'change_log_total_hours' => 'eti.change_log_total_hours',
			'overbreak_min' => 'eti.overbreak_min',
			'late_min' => 'eti.late_min',
		    'change_log_overbreak_min' => 'eti.change_log_overbreak_min',
		    'change_log_late_min' => 'eti.change_log_late_min',
		    'change_log_absent_min' => 'eti.change_log_absent_min',
		    'last_source' => 'eti.last_source'
		);
		
		$this->edb->select($arr);

		$arr2 = array(
				'emp_id' => 'eti.emp_id',
				'account_id' => 'a.account_id'
		);
		
		$this->edb->select($arr2);
		
		$this->edb->join('employee AS e','e.emp_id = eti.emp_id',"INNER");
		$this->edb->join('accounts AS a','a.account_id = e.account_id',"INNER");
		$this->edb->join('employee_payroll_information AS epi','epi.emp_id = eti.emp_id','left');
		$this->edb->order_by("e.last_name","ASC");
		$q = $this->edb->get('schedule_blocks_time_in AS eti');
	
		$r= $q->result();
	
		if($r){
			foreach ($r as $row) {
				$temp = array(
					'late_min' 							=> $row->emp_id,
					'schedule_blocks_time_in_id' 		=> $row->schedule_blocks_time_in_id,
					'time_in' 							=> $row->time_in,
					'lunch_out' 						=> $row->lunch_out,
					'lunch_in' 							=> $row->lunch_in,
					'time_out' 							=> $row->time_out,
					'total_hours_required' 				=> $row->total_hours_required,
					'total_hours' 						=> $row->total_hours,
					'tardiness_min'  					=> $row->tardiness_min,
					'undertime_min' 					=> $row->undertime_min,
					'absent_min' 						=> $row->absent_min,
					'source' 							=> $row->source,
					'date' 								=> $row->date,
					'payroll_cloud_id' 					=> $row->payroll_cloud_id,
					'employee_time_in_id' 				=> $row->employee_time_in_id,
					'comp_id' 							=> $row->comp_id,
					'first_name' 						=> $row->first_name,
					'last_name' 						=> $row->last_name,
					'department_id' 					=> $row->department_id,
					'schedule_blocks_id' 				=> $row->schedule_blocks_id,
					'change_log_time_in' 				=> $row->change_log_time_in,
					'change_log_lunch_out' 				=> $row->change_log_lunch_out,
					'change_log_lunch_in' 				=> $row->change_log_lunch_in,
					'change_log_time_out' 				=> $row->change_log_time_out,
					'change_log_tardiness_min' 			=> $row->change_log_tardiness_min,
					'change_log_undertime_min' 			=> $row->change_log_undertime_min,
					'change_log_total_hours_required' 	=> $row->change_log_total_hours_required,
					'time_in_status' 					=> $row->time_in_status,
					'reason' 							=> $row->reason,
					'notes' 							=> $row->notes,
					'work_schedule_id' 					=> $row->work_schedule_id,
					'change_log_total_hours' 			=> $row->change_log_total_hours,
					'overbreak_min' 					=> $row->overbreak_min,
					'late_min' 							=> $row->late_min,
					'account_id'						=> $row->account_id,
				    'change_log_overbreak_min' 			=> $row->change_log_overbreak_min,
				    'change_log_late_min' 					=> $row->change_log_late_min,
				    'change_log_absent_min' 							=> $row->change_log_absent_min,
				    'last_source' => $row->last_source,
				    'account_id'						=> $row->account_id,
					"filter_query" 						=> "employee_time_in_id-{$row->employee_time_in_id}"
				);
		
				array_push($res, $temp);
			}
			return $res;
		}
	
		#return ($q->num_rows() > 0) ? $r : false;
	}
	
	public function assigned_work_schedule_via_payroll_group_v2($company_id){
		$res = array();
		
		$sel = array(
				"pg.work_schedule_id",
				"ws.category_id",
				"ws.name",
				"ws.work_type_name",
				"pg.payroll_group_id",
				"pg.company_id"
		);
		$w = array(
				"pg.company_id" => $company_id
		);
		$this->db->select($sel);
		$this->db->where($w);
		$this->db->join("work_schedule AS ws", "ws.work_schedule_id = pg.work_schedule_id", "INNER");
		$q = $this->db->get("payroll_group AS pg");
		$r = $q->result();
		
		if($r){
			foreach ($r as $row) {
				$temp = array(
						"work_schedule_id" => $row->work_schedule_id,
						"payroll_group_id" => $row->payroll_group_id,
						"company_id" => $row->company_id,
						"category_id" => $row->category_id,
						"name" => $row->name,
						"work_type_name" => $row->work_type_name,
						"filter_query" => "payroll_group_id-{$row->payroll_group_id}"
				);
		
				array_push($res, $temp);
			}
			return $res;
		}
		
		#return ($r) ? $r : FALSE;
		/* set false */
	}
	
	public function get_shift_via_payroll_grp($emp_id) {
		$s = array(
				"payroll_group_id"
		);
		
		$w = array(
				'emp_id'=> $emp_id
		);
		
		$this->db->select($s);
		$this->db->where($w);
		$q_pg = $this->edb->get('employee_payroll_information');
		$r_pg = $q_pg->row();
		 
		return ($r_pg) ? $r_pg : FALSE;
	}
	
	public function get_holiday_date_v2($emp_id,$comp_id){
		$res = array();
			
		$w = array(
				'company_id'=> $comp_id,
				'status' => 'Active'
		);
		
		$this->db->where($w);
		$q = $this->db->get('holiday');
		$r = $q->result();
		
		if($r){
			foreach ($r as $row) {
				$temp = array(
						"date" => $row->date,
						"company_id" => $row->company_id,
						"filter_query" => "date-{$row->date}"
				);
		
				array_push($res, $temp);
			}
			return $res;
		}
		
		return ($r) ? TRUE: FALSE;
	}
	
	public function get_rest_day_v2($company_id)
	{
		$res = array();
		
		$where = array(
				'company_id' 	   	=> $company_id,
				'status'		   	=> 'Active'
		);
		$this->db->where($where);
		$q = $this->db->get('rest_day');
		$result = $q->result();
		
		if($result){
			foreach ($result as $row) {
				$temp = array(
						"rest_day" => $row->rest_day,
						"company_id" => $row->company_id,
						"payroll_group_id" => $row->payroll_group_id,
						"work_schedule_id" => $row->work_schedule_id,
						"filter_query" => "date-{$row->rest_day}{$row->work_schedule_id}"
				);

				array_push($res, $temp);
			}
			return $res;
		}

		return ($result) ? $result : false;
	}
	
	public function check_date_and_time_in($date, $emp_id, $company_id){
		$CI =& get_instance();
	
		$where = array(
				'date'=> $date,
				'emp_id' =>$emp_id,
				'comp_id' => $company_id,
				'status' => 'Active'
		);
		$CI->db->where($where);
		$CI->db->where("(time_in_status != 'reject' OR time_in_status is null)");
		$q = $CI->db->get('employee_time_in');
		$r = $q->row();
	
		return ($r) ? $r : false;
	}
	
	public function get_company_overtime_settings($company_id) {
		$where = array(
				"company_id" => $company_id,
				"status" => "Active"
		);
		
		$this->db->where($where);
		$q = $this->db->get("company_overtime_settings");
		$r = $q->row();
		
		return ($r) ? $r : false;
	}
	
	public function timeins_application_count($company_id,$emp_id,$from="",$to=""){
		if(is_numeric($company_id) && is_numeric($emp_id)){
			$between="";
	
			$this->db->order_by('eti.time_in','DESC');
			$where = array(
					'eti.comp_id'   => $company_id,
					'eti.status'   => 'Active',
					'eti.emp_id' => $emp_id
			);
	
			if($from!="" && $from!="none" && $to!="" && $to!="none"){
				//$between = "AND date between ".$from." AND ".$to;
				$this->db->where('date BETWEEN "'.$from.'" AND "'.$to.'"',NULL,FALSE);
			}
				
			$this->edb->where($where);
			$this->db->where(" (eti.time_in_status ='approved' OR  eti.time_in_status IS NULL) ",NULL,FALSE);
	
			$this->db->select('CONCAT('.$this->edb->decrypt('e.first_name').'," ",'.
			$this->edb->decrypt('e.last_name').') as full_name',FALSE);
			$this->edb->join('employee AS e','e.emp_id = eti.emp_id',"INNER");
			$this->edb->join('accounts AS a','a.account_id = e.account_id',"INNER");
			$this->edb->join('employee_payroll_information AS epi','epi.emp_id = eti.emp_id','left');
			$this->edb->order_by("e.last_name","ASC");
			$query = $this->edb->get('employee_time_in AS eti');
			//$query = $this->db->query("SELECT count(*) as val FROM employee_time_in WHERE   comp_id = '{$this->db->escape_str($company_id)}' AND deleted='0' ".$between);
			$row = $query->row();
			$num_row = $query->num_rows();
			$query->free_result();
			return $num_row ? $num_row: 0;
		}else{
			return false;
		}
	}
	
	public function employee_master_list($payroll_period,$period_from,$period_to,$company_id,$emp_id,$per_page, $page,$sort_by, $q, $sort_param = NULL){
		
		$konsum_key = konsum_key();
	
		// GET EMPLOYEE EXCLUDED LISTS
		$employee_excluded_lists = $this->employee_excluded_lists($company_id,$emp_id, $sort_param);
		
		// GET EMPLOYEE NGA MANA PA RUN SA HR
		$flag_employee_open = TRUE;
		if($sort_param["flag_closed_or_waiting_for_approval"]){
			
			// do nothing, for payroll approval nani siya
			$check_employee_already_run = FALSE;
			
			// GET EMPLOYEE PAYROLL STATUS CLOSED OR PENDING
			$get_employee_payroll_status_closed_or_pending = $this->get_employee_payroll_status_closed_or_pending($sort_param);
			$flag_employee_open = FALSE;
				
		}
		else{
			$check_employee_already_run = $this->check_employee_already_run($sort_param);
			$flag_employee_open = TRUE;
		}
		
		$s = array(
				"*","e.emp_id AS emp_id",
				"pp.payroll_group_id AS payroll_group_id" // kinahanglan ang payroll_group na kuhaon kay ang naa sa payroll payslip payroll_group_id mismo
		);
		$this->edb->select($s);
		
		if($q != ""){
			$this->db->where("
					pp.payroll_date = '{$payroll_period}' AND pp.period_from = '{$period_from}' AND pp.period_to = '{$period_to}' AND e.company_id = {$company_id} AND
					(convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
					OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
					", NULL, FALSE); // encrypt
		}
		
		$w = array(
				"pp.payroll_date"		=> $payroll_period,
				"pp.period_from"		=> $period_from,
				"pp.period_to"			=> $period_to,
				"e.company_id"			=> $company_id,
				"e.status"				=> "Active",
				"epl.employee_status"	=> "Active",
				"e.emp_id" 				=> $emp_id
			);
		$this->db->where($w);
	
		// CHECK KUNG ANG EMPLOYEE NA EXCLUDE WITH IN SA PAYROLL PERIOD, BAG.O NI SYA
		if(isset($sort_param["exclude_view"]) == ""){
			// GET EMPLOYEE EXCLUDED LISTS
			if($employee_excluded_lists != FALSE){
			$this->db->where_not_in("e.emp_id", $employee_excluded_lists);
			}
		}
	
		// GET EMPLOYEE NGA MANA PA RUN SA HR
		if($check_employee_already_run != FALSE){
			$this->db->where_not_in("e.emp_id", $check_employee_already_run);
		}
	
		// PARA NI SA CLOSED OR PENDING STATUS SA PAYROLL
		if(!$flag_employee_open){
			$this->db->where_in("pp.emp_id", $get_employee_payroll_status_closed_or_pending);
		}
		$this->edb->join("employee AS e","e.emp_id = pp.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epl.payroll_group_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		
	
		$q = $this->edb->get("payroll_payslip AS pp");
		
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	public function timeins_list_v4($company_id,$emp_id,$limit,$start,$date_from,$date_to,$filter = array()){
		if(is_numeric($company_id)){
			$start = intval($start);
			$limit = intval($limit);
	
			$this->db->order_by('eti.time_in','DESC');
			$where = array(
					'eti.comp_id'   => $company_id,
					'eti.emp_id'	=>  $emp_id,
					'eti.status'   => 'Active'
			);
	
			if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
				$this->db->where('eti.date BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
			}
	
			$this->edb->where($where);
			//$wherex = " (eti.time_in_status = 'approved' OR eti.time_in_status IS NULL OR eti.comp_id = '{$company_id}') AND eti.comp_id = {$company_id} AND eti.status = 'Active' ";
			//$this->db->where($wherex,null,false);
			#$this->db->where(" (eti.time_in_status ='approved' OR  eti.time_in_status IS NULL) ",NULL,FALSE);
			
			$arr = array('time_in' => 'eti.time_in',
					'lunch_out' => 'eti.lunch_out',
					'lunch_in' => 'eti.lunch_in',
					'time_out' => 'eti.time_out',
					'total_hours_required' => 'eti.total_hours_required',
					'total_hours' => 'eti.total_hours',
					'late_min' => 'eti.late_min',
					'overbreak_min' => 'eti.overbreak_min',
					'tardiness_min'  => 'eti.tardiness_min',
					'undertime_min' => 'eti.undertime_min',
					'absent_min' => 'eti.absent_min',
					'source' => 'eti.source',
					'date' => 'eti.date',
					'payroll_cloud_id' => 'a.payroll_cloud_id',
					'work_schedule_id' => 'eti.work_schedule_id',
					'employee_time_in_id' => 'eti.employee_time_in_id',
					'comp_id' => 'eti.comp_id',
					'first_name' => 'e.first_name',
					'last_name' => 'e.last_name',
					'middle_name' => 'e.middle_name',
					'department_id' => 'epi.department_id',
					'emp_id' => 'eti.emp_id',
					'account_id' => 'a.account_id',
					'location' => 'eti.location',
					'time_in_status' => 'eti.time_in_status'
			);
			$this->edb->select($arr);
			
			if($filter){

				$this->db->where("eti.emp_id IN ({$filter})");
			}
			/*$this->db->select('CONCAT('.$this->edb->decrypt('e.first_name').'," ",'.
			 $this->edb->decrypt('e.last_name').') as full_name',FALSE);*/
			$this->edb->join('employee AS e','e.emp_id = eti.emp_id',"INNER");
			$this->edb->join('accounts AS a','a.account_id = e.account_id',"INNER");
			$this->edb->join('employee_payroll_information AS epi','epi.emp_id = eti.emp_id','left');
			$query = $this->edb->get('employee_time_in AS eti',$limit,$start);
	
			$result = $query->result();
			$query->free_result();
			return $result;
		}else{
			return false;
		}
	
	}
	
	public function employee_excluded_lists($company_id,$emp_id, $sort_param=NULL){
		$ignore = array();
		$w = array(
			"company_id"=>$company_id,
			"exclude"=> 1,
			// BAG.O NPUD NI ZZZZZZZ para dili na global ang pag exclude sa employee, by payroll period na ang pag exclude ani tungod sa kadaghan versions zzzzzzzzzzz
			"status"=> "Active",
			"payroll_period"=>$sort_param["payroll_period"],
			"period_from"=>$sort_param["period_from"],
			"period_to"=>$sort_param["period_to"],
			'emp_id' => $emp_id
		);
		$this->db->where($w);
		$q = $this->db->get("exclude_list");
		$r = $q->result();
		if($r){
			foreach($r as $row){
				array_push($ignore, $row->emp_id);
			}
			return $ignore;
		}else{
			return FALSE;
		}
	}
	
	public function get_employee_payroll_status_closed_or_pending($data_param){
		
		$arrs = array();
		
		// PAYROLL CUSTOM
		$w = array(
			"prc.company_id"=>$this->company_id,
			"prc.payroll_period"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
			"prc.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
			"prc.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
			"prc.status"=>"Active",
			"dpr.token"=>$data_param["token"],
			'prc.emp_id' => $this->emp_id
		);
		$this->db->where($w);
		$this->db->where("(dpr.view_status = 'Closed' OR dpr.view_status = 'Waiting for approval')");
		$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
		$q = $this->db->get("payroll_run_custom AS prc");
		$r = $q->result();
		
		if($r){
			foreach($r as $row){
				array_push($arrs, $row->emp_id);
			}
		}
		
		// ===
		
		// BY PAYROLL GROUP
		$w = array(
			"dpr.company_id"=>$this->company_id,
			"dpr.pay_period"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
			"dpr.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
			"dpr.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
			"pp.payroll_date"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
			"pp.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
			"pp.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
			"dpr.status"=>"Active",
			"dpr.token"=>$data_param["token"],
			"dpr.emp_id"=>$this->emp_id,
		);
		$this->db->where($w);
		$this->db->where("(dpr.view_status = 'Closed' OR dpr.view_status = 'Waiting for approval')");
		$this->db->join("draft_pay_runs AS dpr","pp.payroll_group_id = dpr.payroll_group_id && dpr.pay_period = pp.payroll_date","LEFT");
		$q = $this->db->get("payroll_payslip AS pp");
		$r = $q->result();
		
		if($r){
			foreach($r as $row){
				array_push($arrs, $row->emp_id);
			}
		}
		
		return $arrs;
	}
	
	public function check_employee_already_run($data_param){
		
		// IGNORE ARRAY
		$ignore = array();
		
		// check from payroll custom list
		$w = array(
			"prc.company_id"=>$data_param["company_id"],
			"prc.payroll_period"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
			"prc.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
			"prc.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
			"pp.payroll_date"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
			"pp.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
			"pp.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
			"prc.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->where("(dpr.view_status = 'Closed' OR dpr.view_status = 'Waiting for approval')");
		$this->db->join("payroll_payslip AS pp","pp.emp_id = prc.emp_id","LEFT");
		$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
		$q = $this->db->get("payroll_run_custom AS prc");
		$r = $q->result();
		if($r){
			foreach($r as $row){
				array_push($ignore, $row->emp_id);
			}
		}else{
			// by pay run payroll group employee
			$w = array(
				"dpr.company_id"=>$data_param["company_id"],
				"dpr.pay_period"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
				"dpr.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
				"dpr.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
				"pp.payroll_date"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
				"pp.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
				"pp.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
				"dpr.status"=>"Active"
			);
			$this->db->where($w);
			$this->db->where("(dpr.view_status = 'Closed' OR dpr.view_status = 'Waiting for approval')");
			$this->db->join("employee_payroll_information AS epi","pp.emp_id = epi.emp_id","LEFT");
			// $this->db->join("draft_pay_runs AS dpr","epi.payroll_group_id = dpr.payroll_group_id","LEFT");
			$this->db->join("draft_pay_runs AS dpr","pp.payroll_group_id = dpr.payroll_group_id && pp.payroll_date = dpr.pay_period","LEFT");
			$q = $this->db->get("payroll_payslip AS pp");
			$r = $q->result();
			if($r){
				foreach($r as $row){
					array_push($ignore, $row->emp_id);
				}
			}
		}
		
		return $ignore;
	}
	
	public function get_department($id){
		$this->edb->where('dept_id',$id);
		$query = $this->edb->get('department');
		$row = $query->row();
	
		return $row;
	}
	
	public function work_schedule_id_v4($company_id,$emp_id,$date=NULL){
		$w_date = array(
				"ess.valid_from <="		=>	$date,
				"ess.until >="			=>	$date
		);
		if($date != NULL) $this->db->where($w_date);
	
		$w = array(
				"ess.emp_id"=>$emp_id,
				"ess.company_id"=>$company_id,
				"ess.status"=>"Active",
				"ess.payroll_group_id" => "0"
		);
		$this->db->where($w);
	
		$arr2 = array(
				'valid_from' => 'ess.valid_from',
				'work_schedule_id' => 'ess.work_schedule_id'
		);
		$this->edb->select($arr2);
		$this->edb->join("work_schedule AS ws","ws.work_schedule_id = ess.work_schedule_id","LEFT"); // add new
		$q = $this->edb->get("employee_shifts_schedule AS ess");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	public function work_schedule_info_v4($company_id,$work_schedule_id,$weekday,$currentdate = NULL,$emp_id =null,$employee_time_in_id = 0){
		$wd = date("l",strtotime($weekday));
		$break_time = 0;
	
		$uww = array(
				"uw.days_of_work"=>$wd,
				"uw.company_id"=>$company_id,
				"uw.work_schedule_id"=>$work_schedule_id,
				"uw.status"=>"Active"
		);
	
		$this->db->where($uww);
		$arr2 = array(
				'work_start_time' => 'uw.work_start_time',
				'work_end_time' => 'uw.work_end_time',
				'work_schedule_name' => 'uw.work_schedule_name',
				'total_work_hours' => 'uw.total_work_hours'
		);
		$this->edb->select($arr2);
		$this->edb->join("work_schedule AS ws","ws.work_schedule_id = uw.work_schedule_id","LEFT");
		$uwq = $this->edb->get("regular_schedule AS uw");
		$uwr = $uwq->row();
	
		if($uwr){
			$start_time = $uwr->work_start_time;
			$end_time = $uwr->work_end_time;
			$shift_name = $uwr->work_schedule_name;
			$total_hours = $uwr->total_work_hours;
	
		}else{
				
			$w_date = array(
					"es.valid_from <="		=>	$currentdate,
					"es.until >="			=>	$currentdate
			);
			$this->db->where($w_date);
				
				
			$w_ws = array(
					//"payroll_group_id"=>$payroll_group,
					"em.work_schedule_id"=>$work_schedule_id,
					"em.company_id"=>$company_id,
					"em.emp_id" => $emp_id
			);
			$this->db->where($w_ws);
			$arrx = array(
					'schedule_blocks_id' => 'em.schedule_blocks_id'
			);
			$this->edb->select($arrx);
			$this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
			$q_ws = $this->edb->get("employee_sched_block AS em");
			$r_ws = $q_ws->result();
			$data = array();
			if($q_ws->num_rows() > 0){
	
				$w = array(
						"employee_time_in_id"=>$employee_time_in_id,
						"eti.status" => "Active"
				);
				$this->edb->where($w);
				$this->db->order_by("eti.time_in","ASC");
				$split_q = $this->edb->get("schedule_blocks_time_in AS eti");
				$query_split = $split_q->result();
				$arr = array();
					
				foreach ($query_split as $rowx):
				 
				$split = $this->elm->get_splitschedule_info_new($rowx->time_in,$work_schedule_id,$emp_id,$company_id);
					
				if($split){
					$start_time = date('H:i:s',strtotime($split['start_time']));
					$end_time = date('H:i:s',strtotime($split['end_time']));
					$shift_name = $split['block_name'];
				}
					
				$data[] = array(
						"start_time"=>$start_time,
						"end_time"=>$end_time,
						"shift_name"=>$shift_name,
						"break" => $split['break_in_min'],
						"total" => $split['total_hours_work_per_block']
				);
				endforeach;
	
				return $data;
			}else{
				$fw = array(
						"f.company_id"=>$company_id,
						"f.work_schedule_id"=>$work_schedule_id
				);
				$this->db->where($fw);
				$this->db->join("work_schedule AS ws","ws.work_schedule_id = f.work_schedule_id","LEFT");
				$fq = $this->db->get("flexible_hours AS f");
				$fr = $fq->row();
	
				if($fr){
					if($fr->latest_time_in_allowed != NULL || $fr->latest_time_in_allowed != ""){
						$start_time = $fr->latest_time_in_allowed;
						$end_time = "";
						$shift_name = $fr->name;
					}else{
						$start_time = "";
						$end_time = "";
						$shift_name = $fr->name;
							
					}
				}else{
					$start_time = "";
					$end_time = "";
					$shift_name = "";
					$total_hours = "";
					$total_days_per_year = "";
				}
			}
		}
	
		$data["work_schedule"] = array(
				"start_time"=>$start_time,
				"end_time"=>$end_time,
				"shift_name"=>$shift_name,
				"break_time"=>$break_time
		);
		return $data;
	
	}
	
	public function emp_work_schedule_v4($emp_id,$check_company_id,$currentdate){
		// employee group id
		$s = array(
				"ess.work_schedule_id"
		);
		$w_date = array(
				"ess.valid_from <="		=>	$currentdate,
				"ess.until >="			=>	$currentdate
		);
		$this->db->where($w_date);
			
		$w_emp = array(
				"ess.emp_id"=>$emp_id,
				"ess.company_id"=>$check_company_id,
				"ess.status"=>"Active",
				"ess.payroll_group_id" => 0
		);
		$this->edb->select($s);
		$this->edb->where($w_emp);
		$q_emp = $this->edb->get("employee_shifts_schedule AS ess");
		$r_emp = $q_emp->row();
		
		if ($r_emp) {
			return $r_emp->work_schedule_id;
		}else{
			$w = array(
			    'epi.emp_id'=> $emp_id,
			    "epi.company_id"=>$check_company_id
			);
			
			$this->db->where($w);
			$this->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','LEFT');
			$q_pg = $this->edb->get('employee_payroll_information AS epi');
			$r_pg = $q_pg->row();
			
			return ($r_pg) ? $r_pg->work_schedule_id : FALSE;
		}
	}
	
	public function get_workschedule_info_for_no_workschedule_v4($emp_id,$check_company_id,$date,$work_schedule_id = ""){
		
		
			$where = array(
					'e.company_id'=>$check_company_id,
					'e.emp_id' => $emp_id
			);
			$this->db->where($where);
			$arr2 = array(
					'payroll_cloud_id' => 'a.payroll_cloud_id'
			);
			$this->edb->select($arr2);
			$this->edb->join('accounts AS a','a.account_id=e.account_id','INNER');
			$q = $this->edb->get('employee AS e');
			$result = $q->row();
			$data = "";
			if($result){
				$emp_no = $result->payroll_cloud_id;
					
				//$payroll_group_id = $this->emp_login->payroll_group_id($emp_no,$check_company_id);
					
				$day = date('l',strtotime($date));
				$w_uwd = array(
						//"payroll_group_id"=>$payroll_group,
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
					$data = time12hrs($r_uwd->work_start_time)."-".time12hrs($r_uwd->work_end_time)."<br>";
					$data .= "break: ".$r_uwd->break_in_min." mins";
					$data .= "<br> Total Hours: ".$r_uwd->total_work_hours;
				}else{# FLEXIBLE HOURS
					$fw = array(
					"f.company_id"=>$check_company_id,
					"f.work_schedule_id"=>$work_schedule_id,
					//"f.status" => 'Active'
					);
					$this->db->where($fw);
					$arr3 = array(
							'latest_time_in_allowed' => 'f.latest_time_in_allowed',
							'name' => 'ws.name',
							'number_of_breaks_per_day' => 'number_of_breaks_per_day',
							'total_hours_for_the_day' => 'total_hours_for_the_day'
					);
					$this->edb->select($arr3);
					$this->edb->join("work_schedule AS ws","ws.work_schedule_id = f.work_schedule_id","LEFT");
					$fq = $this->edb->get("flexible_hours AS f");
					$r_fh = $fq->row();
					if($fq->num_rows() > 0){
						$data = $r_fh;
		
						if($r_fh->latest_time_in_allowed != NULL || $r_fh->latest_time_in_allowed != ""){
							$data = $r_fh->latest_time_in_allowed . " <br> ";
							$data .= "break: ".$r_fh->number_of_breaks_per_day. " mins";
							$data .= "<br> Total hours: ". $r_fh->total_hours_for_the_day;
						}else{
							//$data = $r_fh->name;
							$data = "break: ".$r_fh->number_of_breaks_per_day." mins";
							$data .= "<br> Total hours: ". $r_fh->total_hours_for_the_day;
						}
					}
				}
					
					
				return $data;
			}
		}
		
		
		public function employee_gov_loans($company_id, $emp_id, $limit = 10, $start = 0, $sort_by = "", $order_by = '',$count=false){
			$konsum_key = konsum_key();
			$start = intval($start);
			$limit = intval($limit);
			$sort_array = array(
					"loan_type_name",
					"ld.principal_amount",
					"ld.loan_term",
					"ld.first_remittance_date"
			);
			$where = array(
					"ld.company_id" => $company_id,
					#"a.account_id" => $account_id,
					#"a.account_id" => $account_id
					"ld.status" => "Active",
					"e.emp_id" => $emp_id
			
			);
			$select = array(
					"ld.principal_amount AS pr_amount",
					"ld.remittance_due AS rem_due",
					"ld.loan_term AS lo_ter",
					"ld.remittance_scheme AS rem_scheme",
					"ld.payment_schedule AS pay_sched",
					"ld.date_granted AS date_grant",
			    "ld.first_remittance_date AS f_rem_date",
			    "ld.loan_number AS ln"
			    
			);
			$this->edb->select($select);
			$this->db->where($where);
			$this->edb->join("employee AS e","e.emp_id = ld.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("government_loans AS gl","gl.loan_type_id = ld.loan_type_id","LEFT");
			
			if($sort_by != ""){
				if($order_by == ""){
					$order_by = "ASC";
				}else if($order_by == 'desc'){
					$order_by = "desc";
				}
					
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$order_by);
				}
				else{
					$this->db->order_by("e.last_name","ASC");
				}
					
				#if(in_array($sort_by, $sort_array)){
				#	$this->db->order_by($sort_by,$sort);
				#}
			}else{
					
			}
			
			if($count == false){
				$where1 = array(
					"ld.status" => 'Active'
				);
				
				$this->db->where($where1);
				$query = $this->edb->get("gov_loans_deduction AS ld",$limit,$start);
				$result = $query->result();
				#last_query();
				return ($result) ? $result : false;
			}else{
				$query = $this->edb->get("gov_loans_deduction AS ld");
				$num = $query->num_rows();
				#last_query();
				return $num;
			}
		}
		
		public function employee_thrdpt_loans($company_id, $emp_id, $limit = 10, $start = 0, $sort_by = "", $sort = "ASC",$count=false){
			$konsum_key = konsum_key();
			$start = intval($start);
			$limit = intval($limit);
			$sort_array = array(
					"loan_type_name",
					"ld.principal_amount",
					"ld.beginning_balance",
					"ld.loan_term",
					"ld.first_remittance_date"
			);
			$where = array(
					"ld.company_id" => $company_id,
					"ld.status" => "Active",
					"e.emp_id" => $emp_id
					#"a.account_id" => $account_id
			);
			$select = array(
					"ld.loan_number AS lo_num",
					"ld.principal_amount AS pr_amount",
					"ld.beginning_balance AS beg_bal",
					"ld.loan_term AS lo_ter",
					"ld.first_remittance_date AS f_rem_date"
			);
			$this->db->select($select);
			$this->db->where($where);
			$this->edb->join("employee AS e","e.emp_id = ld.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("loan_type AS lt","lt.loan_type_id = ld.loan_type_id","LEFT");
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			}
		
			if($count == false){
				$query = $this->edb->get("loans_deductions AS ld");
				$result = $query->result();
					
				return ($result) ? $result : false;
			}else{
				$query = $this->edb->get("loans_deductions AS ld");
				$result = $query->num_rows();
				return $result;
			}
		}
		
		public function check_double_timesheet_for_ot($emp_id, $start_date, $end_date){
			$s = array(
				'emp_id',
				'date',
				'time_in_status',
				'time_in',
				'time_out',
			    'rest_day_r_a',
			    'flag_rd_include',
			    'flag_holiday_include',
			    'holiday_approve'
			);
			
			$where = array(
				#'date ' 		=> date('Y-m-d', strtotime($start_date)),
				#'time_in <=' 	=> date('Y-m-d H:i:s', strtotime($start_date)),
				#'time_out >='	=> date('Y-m-d H:i:s', strtotime($end_date)),
				"DATE_FORMAT(time_in,'%Y-%m-%d %H:%i:00') <=" => date('Y-m-d H:i:s', strtotime($start_date)),
				"DATE_FORMAT(time_out,'%Y-%m-%d %H:%i:00') >=" => date('Y-m-d H:i:s', strtotime($end_date)),
				'emp_id' 		=> $emp_id,
				'status' 		=> 'Active'
			);
			
			$this->db->select($s);
			$this->db->where($where);
			$this->db->where("(time_in_status != 'reject' OR time_in_status is null)");
			$q = $this->db->get('employee_time_in');
			$r = $q->row();
			
			return ($r) ? $r : false;
			
		}
		
		public function check_double_timesheet_for_ot_split($emp_id, $start_date, $end_date){
		    $s = array(
		        'emp_id',
		        'date',
		        'time_in_status',
		        'time_in',
		        'time_out',
		        'schedule_blocks_id'
		    );
		    
		    $where = array(
		        "DATE_FORMAT(time_in,'%Y-%m-%d %H:%i:00') <=" => date('Y-m-d H:i:s', strtotime($start_date)),
		        "DATE_FORMAT(time_out,'%Y-%m-%d %H:%i:00') >=" => date('Y-m-d H:i:s', strtotime($end_date)),
		        'emp_id' => $emp_id,
		        'status' => 'Active'
		    );
		    
		    $this->db->select($s);
		    $this->db->where($where);
		    $this->db->where("(time_in_status != 'reject' OR time_in_status is null)");
		    $q = $this->db->get('schedule_blocks_time_in');
		    $r = $q->row();
		    
		    return ($r) ? $r : false;
		    
		}
		
		public function other_deduction_summary($emp_id, $company_id, $sort_by="", $sort="", $limit = 10000, $start = 0) {
			$res = array();
			$sort_array = array(
					"deduction_name",
					"amount",
					"payroll_period",
					"period_from",
					"period_to"
			);
			
			$where = array(
					"emp_id" => $emp_id,
					"company_id" => $company_id,
					"status" => "Active"
			);
			
			$this->db->where($where);
			
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			} else {
				$this->db->order_by("deduction_id","desc");
				$this->db->order_by("payroll_period","desc");
			}
			
			$q = $this->db->get('payroll_for_other_deductions',$limit,$start);
			$r = $q->result();
			
			if($r) {
				$flag = 0;
				$flag_id = "";
				$cont = '';
				foreach ($r as $key=>$row) {
				    
					$get_other_deduction_to_date = $this->get_other_deduction_to_date($row->emp_id,$row->company_id,$row->employee_deduction_id);
					$get_other_deduction_to_date = number_format($get_other_deduction_to_date,2,'.','');
					$amount = number_format($row->amount,2,'.','');
					$employee_deduction_id = $row->employee_deduction_id;
					
					if($cont == ''){
						$cont = $row->employee_deduction_id;
					}
						
					if($cont !=""){
						if($cont == $row->employee_deduction_id){
							$flag_content = 0;
						}else{
							$cont = $row->employee_deduction_id;
							$flag_content = 1;
						}
					} else {
					    $flag_content = 1;
					}
						
					if($flag_content == 1){
						$cont = "";
						$flag_id = "";
						$flag = 0;
					}
					
					$temp = array(
							"deduction_name" => $row->deduction_name,
							"amount" => $amount,
							"payroll_period" => $row->payroll_period,
							"period_from" => $row->period_from,
							"period_to" => $row->period_to,
							"deduction_id" => $row->deduction_id,
							"employee_deduction_id" => $row->employee_deduction_id,
							"emp_id" => $row->emp_id,
							"company_id" => $row->company_id,
							"total" => $get_other_deduction_to_date
					);
					
					if($flag_id == ''){
						$flag_id = $employee_deduction_id;
						$amount = 0;
					}
					
					if($flag == 0) {
					    $total = $get_other_deduction_to_date - $amount;
						$flag = $total;
					} else {
					    
						$total = $flag - $amount;
						$flag = $total;
					}
					
					$temp['amount_to_date'] = number_format($total,2,'.','');
					
					array_push($res, (object) $temp);
				}
				
				return $res;
			}
			
			#return ($r) ? $r : false;
			
		}
		
		public function get_other_deduction_to_date($emp_id, $company_id, $deduction_id) {
			$where = array(
					"emp_id" => $emp_id,
					"company_id" => $company_id,
					"status" => "Active",
					"employee_deduction_id" => $deduction_id,
			);
			
			$this->db->where($where);
			$this->db->order_by("payroll_period","DESC");
			$q = $this->db->get('payroll_for_other_deductions');
			$r = $q->result();
			
			if($r) {
				$amount = 0;
				foreach ($r as $key=>$res) {
					$amount += $res->amount;
				}
				
				return $amount;
			} else{
				return 0;
			}
			
			#return ($r) ? $r : false;
			
		}
		
		public function get_current_approver($val){
			if(is_numeric($val)){
					
				$s = array(
						'ti.level'
				);
				
				$where = array(
						'ee.employee_time_in_id'   => $val
				);
					
				$this->db->select($s);
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
		
		public function edit_delete_void($emp_id,$comp_id,$gDate){
		    return false; // aborted function hahah
		    
			/*$gDate 			= date("Y-m-d",strtotime($gDate));
			$return_void 	= false;
			$stat_v1 		= "";
			$w = array(
					'prc.emp_id' 			=> $emp_id,
					'prc.company_id' 		=> $comp_id,
					'prc.period_from <=' 	=> $gDate,
					'prc.period_to >='  	=> $gDate,
					'prc.status'			=> 'Active'
			);
			$s = array(
					'dpr.view_status'
			);
			$this->db->select($s);
			$this->db->where($w);
			$this->db->join("draft_pay_runs as dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id");
			$q = $this->db->get('payroll_run_custom as prc');
			$r = $q->result();
			if($r){
				foreach($r as $r1){
					$stat_v = $r1->view_status;
					if($stat_v == "Waiting for approval" || $stat_v == "Closed"){
						$return_void = true;
					}
				}
			}else{
				$w1 = array(
						'epi.emp_id' 			=> $emp_id,
						'dpr.company_id' 		=> $comp_id,
						'dpr.period_from <=' 	=> $gDate,
						'dpr.period_to >='  	=> $gDate,
						'dpr.status'			=> 'Active'
				);
				$s1 = array(
						'dpr.view_status'
				);
				$this->db->select($s1);
				$this->db->where($w1);
				$this->db->join("draft_pay_runs as dpr","dpr.payroll_group_id = epi.payroll_group_id");
				$q1 = $this->db->get('employee_payroll_information as epi');
				$r1 = $q1->result();
				if($r1){
					foreach($r1 as $r1x){
						$stat_v = $r1x->view_status;
						if($stat_v == "Waiting for approval" || $stat_v == "Closed"){
							$return_void = true;
						}
					}
				}
			}
		
			if($return_void){
				return $stat_v;
			}else{
				return false;
			}*/
		}
		
		public function get_payroll_group_ids($company_id,$emp_ids=""){
			$row_array	= array();
			$s 	= array(
					"epi.payroll_group_id",
					"epi.emp_id"
			);
			$w 	= array(
					'epi.company_id'=> $company_id
			);
			$this->db->select($s);
			$this->db->where($w);
			if($emp_ids){
				$this->db->where_in("emp_id",$emp_ids);
			}
			$q = $this->edb->get('employee_payroll_information AS epi');
			$r = $q->result();
			if($r){
				foreach ($r AS $r1){
					$wd = array(
							"payroll_group_id"	=> $r1->payroll_group_id,
							"emp_id"			=> $r1->emp_id,
							"custom_search"		=> "emp_id-{$r1->emp_id}",
					);
					array_push($row_array,$wd);
				}
			}
			return $row_array;
		}
		
		
		public function assigned_work_schedule_dl($company_id,$min="",$max="",$emp_ids=""){
			$row_array	= array();
			$sel 		= array(
					"ess.emp_id",
					"ess.shifts_schedule_id",
					"ess.work_schedule_id",
					"ws.bg_color",
					"ws.flag_custom",
					"ws.work_type_name",
					"ws.name",
					"ws.category_id",
					"ess.valid_from",
					"ess.until"
			);
			if($min != "" && $max != ""){
				$w1 = array(
						"ess.valid_from >="		=> $min,
						"ess.valid_from <="		=> $max,
				);
				$this->db->where($w1);
			}
			$w = array(
					"ess.company_id" 		=> $company_id,
					"ess.status" 			=> "Active",
					"ess.payroll_group_id"	=>	"0"
			);
			$this->db->select($sel);
			$this->db->where($w);
			if($emp_ids){
				$this->db->where_in("emp_id",$emp_ids);
			}
			$this->db->join("work_schedule AS ws","ws.work_schedule_id = ess.work_schedule_id","LEFT");
			$q = $this->db->get("employee_shifts_schedule AS ess");
			$r = $q->result();
			if($r){
				foreach ($r AS $r1){
					$wd = array(
							"emp_id"				=> $r1->emp_id,
							"shifts_schedule_id"	=> $r1->shifts_schedule_id,
							"work_schedule_id"		=> $r1->work_schedule_id,
							"bg_color"				=> $r1->bg_color,
							"flag_custom"			=> $r1->flag_custom,
							"work_type_name"		=> $r1->work_type_name,
							"name"					=> $r1->name,
							"category_id"			=> $r1->category_id,
							"valid_from"			=> $r1->valid_from,
							"until"					=> $r1->until,
							"custom_search"	=> "{$r1->emp_id}-{$r1->valid_from}",
					);
					array_push($row_array,$wd);
				}
			}
			return $row_array;
		}
		
		public function emp_work_schedule_dl($company_id,$min="",$max="",$emp_ids=""){
			// employee group id
			$row_array = array();
			$s = array(
					"ess.work_schedule_id",
					"ess.emp_id",
					"ess.valid_from",
			);
			$this->edb->select($s);
		
			if($min != "" && $max != ""){
				$w1 = array(
						"ess.valid_from >="	=> $min,
						"ess.valid_from <="	=> $max
				);
				$this->db->where($w1);
			}
		
			if($emp_ids){
				$this->db->where_in("ess.emp_id",$emp_ids);
			}
			$w_emp = array(
					"ess.company_id"=>$company_id,
					"ess.status"=>"Active",
					"ess.payroll_group_id" => 0
			);
		
			$this->edb->where($w_emp);
			$q_emp = $this->edb->get("employee_shifts_schedule AS ess");
			$r_emp = $q_emp->result();
		
			if($r_emp){
				foreach ($r_emp as $r1){
					$wd 	= array(
							"work_schedule_id"	=> $r1->work_schedule_id,
							"custom_search"		=> "emp_id-{$r1->emp_id}-{$r1->valid_from}",
					);
					array_push($row_array,$wd);
				}
			}
		
			return $row_array;
		}
		
		public function emp_payroll_info_wsid($company_id,$emp_ids=""){
			$row_array 	= array();
			$s 			= array(
					'epi.emp_id',
					'pg.work_schedule_id',
			);
			$this->db->select($s);
		
			$w 			= array(
					'epi.company_id'=> $company_id
			);
			$this->db->where($w);
		
			if($emp_ids){
				$this->db->where_in("epi.emp_id",$emp_ids);
			}
			$this->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','LEFT');
			$q_pg = $this->db->get('employee_payroll_information AS epi');
			$r_pg = $q_pg->result();
		
			if($r_pg){
				foreach ($r_pg as $r1){
					$wd 	= array(
							"work_schedule_id"	=> $r1->work_schedule_id,
							"custom_search"		=> "emp_id-{$r1->emp_id}",
					);
					array_push($row_array,$wd);
				}
			}
			return $row_array;
		}
		
		public function check_is_date_holidayv2($company_id,$min="",$max=""){
		
			$row_array 	= array();
			$year_min 	= date("Y",strtotime($min));
			$year_max 	= date("Y",strtotime($max));
			
			$y_gap		= ($year_max - $year_min) + 1;
			$s		= array(
					'h.repeat_type',
					'ht.hour_type_name',
					'h.holiday_name',
					'h.date',
					'h.date_type',
			);
			$this->db->select($s);
			$w		= array(
					"h.company_id" => $company_id
			);
			$this->db->where($w);
			//$this->db->where("(MONTH(h.date) = '{$month}' && DAY(h.date) = '{$day}')");
			$this->db->join("hours_type AS ht","ht.hour_type_id = h.hour_type_id");
			$q = $this->db->get("holiday AS h");
			$r = $q->result();
			
			if($r){
				foreach ($r as $r1){
					$date		= $r1->date;
					
					if($r1->repeat_type == "yes" && $r1->date_type != "movable"){
						
						for($x = 0;$x < $y_gap;$x++){
							
							$month 		= date("m",strtotime($date));
							$day 		= date("d",strtotime($date));
							$year 		= $year_min + $x;
							
							$hol_date 	= $year."-".$month."-".$day;
							
							$wd 	= array(
									"repeat_type"		=> $r1->repeat_type,
									"hour_type_name"	=> $r1->hour_type_name,
									"holiday_name"		=> $r1->holiday_name,
									"date"				=> $hol_date,
									"custom_search"		=> "date-{$hol_date}",
							);
							array_push($row_array,$wd);
						}
					}else{
						$wd 	= array(
								"repeat_type"		=> $r1->repeat_type,
								"hour_type_name"	=> $r1->hour_type_name,
								"holiday_name"		=> $r1->holiday_name,
								"date"				=> $r1->date,
								"custom_search"		=> "date-{$r1->date}",
						);
						array_push($row_array,$wd);
					}
				}
			}
			return $row_array;
		}
		
		public function check_workday_dl($company_id){
			$row_array 	= array();
			$w = array(
				"comp_id"=> $company_id
			);	
			$this->db->where($w);
			$arrx = array(
					'work_type_name',
					'work_schedule_id'
					);
			$this->db->select($arrx);
			$q = $this->db->get("work_schedule");
			$r = $q->result();
			if($r){
				foreach ($r as $r1){
					$wd 	= array(
							"work_type_name"		=> $r1->work_type_name,
							"work_schedule_id"		=> $r1->work_schedule_id,
							"custom_search"		=> "wsi-{$r1->work_schedule_id}",
					);
					array_push($row_array,$wd);
				}
			}
			return $row_array;
		}
		
		public function generate_list_of_blocksv2($comp_id,$min="",$max="",$emp_ids=""){
			$row_array 	= array();
			$arr		= array(
					'payroll_cloud_id',
					'first_name',
					'last_name',
			);
			$this->edb->select($arr);
			$arr2 = array(
					'emp_id' 				=> 'eti.emp_id',
					'account_id' 			=> 'a.account_id',
					'time_in' 				=> 'eti.time_in',
					'lunch_out' 			=> 'eti.lunch_out',
					'lunch_in' 				=> 'eti.lunch_in',
					'time_out' 				=> 'eti.time_out',
					'total_hours_required' 	=> 'eti.total_hours_required',
					'total_hours' 			=> 'eti.total_hours',
					'tardiness_min'  		=> 'eti.tardiness_min',
					'late_min' 				=> 'eti.late_min',
					'overbreak_min' 		=> 'eti.overbreak_min',
					'undertime_min' 		=> 'eti.undertime_min',
					'absent_min' 			=> 'eti.absent_min',
					'source' 				=> 'eti.source',
					'date' 					=> 'eti.date',
					'employee_time_in_id' 	=> 'eti.employee_time_in_id',
					'schedule_blocks_id' 	=> 'eti.schedule_blocks_id'
					);
			$this->db->select($arr2);
			
			if($min != "" && $max != ""){
				$w1 = array(
						"eti.date >="	=> $min,
						"eti.date <="	=> $max
				);
				$this->db->where($w1);
			}
			
			if($emp_ids){
				$this->db->where_in("eti.emp_id",$emp_ids);
			}
			
			$w_uwd = array(
					"eti.comp_id"	=> $comp_id,
					"eti.status" 	=> 'Active'
			);
			$this->db->where($w_uwd);
			$this->db->join('employee AS e','e.emp_id = eti.emp_id',"INNER");
			$this->db->join('accounts AS a','a.account_id = e.account_id',"INNER");
			$this->db->join('employee_payroll_information AS epi','epi.emp_id = eti.emp_id','left');
			$this->edb->order_by("e.last_name","ASC");
			$q = $this->edb->get('schedule_blocks_time_in AS eti');
			$r= $q->result();
			
			if($r){
				foreach ($r as $r1){
					$wd 	= array(
							"emp_id"				=> $r1->emp_id,
							"account_id"			=> $r1->account_id,
							"time_in"				=> $r1->time_in,
							"lunch_out"				=> $r1->lunch_out,
							"lunch_in"				=> $r1->lunch_in,
							"time_out"				=> $r1->time_out,
							"total_hours_required"	=> $r1->total_hours_required,
							"total_hours"			=> $r1->total_hours,
							"tardiness_min"			=> $r1->tardiness_min,
							"late_min"				=> $r1->late_min,
							"overbreak_min"			=> $r1->overbreak_min,
							"undertime_min"			=> $r1->undertime_min,
							"absent_min"			=> $r1->absent_min,
							"source"				=> $r1->source,
							"date"					=> $r1->date,
							"source"				=> $r1->source,
							"schedule_blocks_id"	=> $r1->schedule_blocks_id,
							"employee_time_in_id"	=> $r1->employee_time_in_id,
							"custom_search"			=> "eti-{$r1->employee_time_in_id}",
					);
					array_push($row_array,$wd);
				}
			}
			return $row_array;
		}
		
		public function all_sched_block_time_in($company_id,$min="",$max="",$emp_ids=""){
			$row_array	= array();
			$s 			= array(
						"sbti.employee_time_in_id",
						"sbti.emp_id",
						"sbti.date",
						"sb.start_time",
						"sb.end_time",
						"sb.block_name",
						"sb.break_in_min",
						"sb.schedule_blocks_id",
						"sb.total_hours_work_per_block",
						);
			$this->db->select($s);
			
			$w 	= array(
					"sbti.comp_id" => $company_id,
					"sbti.status" => "Active",
			);
			$this->db->where($w);
			
			if($min != "" && $max != ""){
				$w1 = array(
						"sbti.date >="		=> $min,
						"sbti.date <="		=> $max,
				);
				$this->db->where($w1);
			}
			
			if($emp_ids){
				$this->db->where_in("sbti.emp_id",$emp_ids);
			}
			$this->db->order_by("sbti.time_in","ASC");
			$split_q 		= $this->db->join("schedule_blocks AS sb","sb.schedule_blocks_id = sbti.schedule_blocks_id");
			$split_q 		= $this->db->get("schedule_blocks_time_in AS sbti");
			$query_split 	= $split_q->result();
			if($query_split){
				foreach ($query_split as $r1){
					$wd 	= array(
							"start_time"		=> $r1->start_time,
							"end_time"			=> $r1->end_time,
							"shift_name"		=> $r1->block_name,
							"break"				=> $r1->break_in_min,
							"total"				=> $r1->total_hours_work_per_block,
							"schedule_blocks"	=> $r1->schedule_blocks_id,
							"custom_search"		=> "etid-{$r1->employee_time_in_id}-{$r1->schedule_blocks_id}",
							);
					array_push($row_array,$wd);
				}
			}
			return $row_array;
		}
		
		public function all_sched_flex_in($company_id){
			$row_array	= array();
			$s 			= array(
						"f.work_schedule_id",
						"f.latest_time_in_allowed",
						"ws.name",
						"f.total_hours_for_the_day",
						"f.duration_of_lunch_break_per_day",
						);
			
			$this->db->select($s);
			$w = array(
					"f.company_id"		=> $company_id
			);
			$this->db->where($w);
			$this->db->join("work_schedule AS ws","ws.work_schedule_id = f.work_schedule_id","LEFT");
			$fq = $this->db->get("flexible_hours AS f");
			$fr = $fq->result();
			
			if($fr){
				foreach ($fr as $r1){
					if($r1->latest_time_in_allowed != NULL || $r1->latest_time_in_allowed != ""){
						$start_time = $r1->latest_time_in_allowed;
						$end_time 	= "";
						$shift_name = $r1->name;
					}else{
						$start_time = "";
						$end_time 	= "";
						$shift_name = $r1->name;
					}
					$wd = array(
							"start_time"				=> $start_time,
							"end_time"					=> $end_time,
							"total_hours"				=> $r1->total_hours_for_the_day,
							"latest_time_in_allowed"	=> $r1->latest_time_in_allowed,
							"shift_name"				=> $shift_name,
							"break"						=> $r1->duration_of_lunch_break_per_day,
							"custom_search"				=> "wsi-{$r1->work_schedule_id}",
					);
					array_push($row_array,$wd);
				}
			}
			
			return $row_array;
		}
		
		public function get_comp_reg_sched($company_id){
			$row_array 	= array();
			$w_uwd 		= array(
						"company_id"=> $company_id,
						"status" 	=> 'Active'
						);
			$this->db->where($w_uwd);
			$arr4 	= array(
					'work_schedule_name',
					'work_end_time',
					'work_start_time',
					'break_in_min',
					'total_work_hours',
					'days_of_work',
					'work_schedule_id',
					'break_in_min'
					);
			$this->db->select($arr4);
			$q_uwd = $this->db->get("regular_schedule");
			$r_uwd = $q_uwd->result();
			if($r_uwd){
				foreach ($r_uwd as $r1){
					$wd 	= array(
							"work_schedule_name"=> $r1->work_schedule_id,
							"work_end_time"		=> $r1->work_end_time,
							"work_start_time"	=> $r1->work_start_time,
							"break_in_min"		=> $r1->break_in_min,
							"total_work_hours"	=> $r1->total_work_hours,
							"days_of_work"		=> $r1->days_of_work,
							"break_in_min"		=> $r1->break_in_min,
							"custom_search"		=> "rwis-{$r1->work_schedule_id}-{$r1->days_of_work}",
					);
					array_push($row_array,$wd);
				}
			}
			return $row_array;
		}
		
		public function check_this_sched($company_id,$min="",$max="",$emp_ids=""){
			$row_array 	= array();
			$arrx 		= array(
						'em.work_schedule_id',
						'em.schedule_blocks_id',
						'em.emp_id',
						'es.valid_from',
						);
			$this->db->select($arrx);
			if($min != "" && $max != ""){
				$w1 = array(
						"es.valid_from >="		=> $min,
						"es.until <="			=> $max,
				);
				$this->db->where($w1);
			}
			if($emp_ids){
				$this->db->where_in("em.emp_id",$emp_ids);
			}
			
			$w_ws 	= array(
					"em.company_id"			=> $company_id
					);
			
			$this->db->where($w_ws);
			$this->db->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
			$q_ws = $this->db->get("employee_sched_block AS em");
			$r_ws = $q_ws->result();
			if($r_ws){
				foreach ($r_ws as $r1){
					$wd 	= array(
							"schedule_blocks_id"=> $r1->schedule_blocks_id,
							"custom_search"		=> "wsidate-{$r1->work_schedule_id}-{$r1->valid_from}",
							);
					array_push($row_array,$wd);
				}
			}
			return $row_array;
		}
		
		public function work_schedule_info2($company_id,$work_schedule_id,$weekday,$currentdate = NULL,$emp_id =null,$employee_time_in_id = 0,$check_sched_split_flex=array(),$all_sched_block_time_in=array(),$all_sched_flex=array()){
		
			$wd = date("l",strtotime($weekday));
			$break_time = 0;
			$start_time = "";
			$end_time 	= "";
			$shift_name = "";
			$uww = array(
					"uw.days_of_work"=>$wd,
					"uw.company_id"=>$company_id,
					"uw.work_schedule_id"=>$work_schedule_id,
					"uw.status"=>"Active"
			);
		
			$this->db->where($uww);
			$arr2 = array(
					'work_start_time' 		=> 'uw.work_start_time',
					'work_end_time' 		=> 'uw.work_end_time',
					'work_schedule_name'	=> 'uw.work_schedule_name',
					'total_work_hours' 		=> 'uw.total_work_hours',
					'break_in_min' 			=> 'uw.break_in_min'
			);
			$this->edb->select($arr2);
			$this->edb->join("work_schedule AS ws","ws.work_schedule_id = uw.work_schedule_id","LEFT");
			$uwq 	= $this->edb->get("regular_schedule AS uw");
			$uwr 	= $uwq->row();
			$data 	= array();
			if($uwr){
				$start_time 	= $uwr->work_start_time;
				$end_time 		= $uwr->work_end_time;
				$shift_name 	= $uwr->work_schedule_name;
				$total_hours 	= $uwr->total_work_hours;
				$break_time 	= $uwr->break_in_min;
			}else{
				$sched_split = in_array_foreach_custom("wsidate-{$work_schedule_id}-{$currentdate}",$check_sched_split_flex);
				if($sched_split){
					foreach ($sched_split as $split){
						$split_data = in_array_custom("etid-{$employee_time_in_id}-{$split->schedule_blocks_id}",$all_sched_block_time_in);
							
						if($split_data){
							$data[] = array(
									"start_time"	=> $split_data->start_time,
									"end_time"		=> $split_data->end_time,
									"shift_name"	=> $split_data->shift_name,
									"break" 		=> $split_data->break,
									"total" 		=> $split_data->total
							);
						}
					}
					return $data;
				}
				else{
					$flex_data 	= in_array_custom("wsi-".$work_schedule_id,$all_sched_flex);
					if($flex_data){
						$start_time = $flex_data->start_time;
						$end_time 	= $flex_data->end_time;
						$shift_name = $flex_data->shift_name;
					}
				}
			}
		
			$data["work_schedule"] = array(
					"start_time"	=>$start_time,
					"end_time"		=>$end_time,
					"shift_name"	=>$shift_name,
					"break_time"	=>$break_time
			);
			return $data;
		
		}
		
		public function get_workschedule_info_for_no_workschedule($company_id,$date,$work_schedule_id = "",$activate = false,$all_sched_flex=array(),$all_sched_reg=array()){

			$data = "";
			$day = date('l',strtotime($date));
			$reg_data 	= in_array_custom("rwis-{$work_schedule_id}-{$day}",$all_sched_reg);
			if($reg_data){
				if($activate){
					$arr = array(
							'start_time' 	=> $reg_data->work_start_time,
							'end_time' 		=> $reg_data->work_end_time,
							'break' 		=> $reg_data->break_in_min,
							'total_hours' 	=> $reg_data->total_work_hours,
							'name' 			=> $reg_data->work_schedule_name,
							'type' 			=> 1
					);
					return $arr;
				}
				else{
					$data  = time12hrs($reg_data->work_start_time)."-".time12hrs($reg_data->work_end_time)."<br>";
					$data .= "break: ".$reg_data->break_in_min." mins";
					$data .= "<br> Total Hours: ".$reg_data->total_work_hours;
				}
			}
			else{
				$flex_data 	= in_array_custom("wsi-".$work_schedule_id,$all_sched_flex);
				if($flex_data){
					$total_h = $flex_data->total_hours - ($flex_data->break / 60);
					$total_h = number_format($total_h,2);
					if($activate){
						$arr = array(
								'start_time' 	=> $flex_data->latest_time_in_allowed,
								'end_time' 		=> "",
								'break' 		=> $flex_data->break,
								'total_hours' 	=> $flex_data->total_hours,
								'name' 			=> '',
								'type' 			=> 2
						);
			
						return $arr;
					}else{
						if($flex_data->latest_time_in_allowed != NULL || $flex_data->latest_time_in_allowed != ""){
							$data  = "Latest Timein: ".time12hrs($flex_data->latest_time_in_allowed) . " <br> ";
							$data .= "break: ".$flex_data->break. " mins";
							$data .= "<br> Total hours: ". $total_h;
						}else{
							$data = "break: ".$flex_data->break." mins";
							$data .= "<br> Total hours: ". $total_h;
						}
					}
				}
			}
			return $data;
			
		}
		
		public function check_if_leave_for_ot($emp_id,$company_id,$date) {
			$w = array(
					"emp_id" => $emp_id,
					"company_id" => $company_id,
					"DATE_FORMAT(date_start,'%Y-%m-%d')" => date("Y-m-d", strtotime($date)),
					"leave_application_status" => "approve"
			);
			
			$this->db->where($w);
			$query = $this->db->get("employee_leaves_application");
				
			$row = $query->row();
			
			return ($row) ? $row : false;
		}
		
		public function get_split_timein_info_res($emp_id,$company_id,$employee_time_in_id) {
			$w = array(
					"emp_id" => $emp_id,
					"comp_id" => $company_id,
					"employee_time_in_id" => $employee_time_in_id,
					"status" => "Active"
			);			
			
			$this->db->where($w);
			$query = $this->db->get("schedule_blocks_time_in");
			
			$result = $query->result();
				
			return ($result) ? $result : false;
			
		}
		
		public function add_split_to_emp_timein_table () {
			
		}
		
		public function add_split_to_sched_blocks_timein_table($employee_time_in_id, $sched_blocks_id, $company_id, $emp_id, $work_schedule_id, $new_employee_timein_date, $reason,
				$new_time_in,$new_lunch_out = "", $new_lunch_in = "", $new_time_out, $tardiness_min = 0, $undertime_min = 0, $total_hours, $total_hours_required, $flag_tu = "",
		    $change_log_total_hours, $change_log_total_hours_required, $flag_halfday = "", $late_min = 0, $overbreak_min = 0,$flag_payroll_correction="no") {		
			
			$date_insert = array(
					"employee_time_in_id"				=> $employee_time_in_id,
					"schedule_blocks_id"				=> $sched_blocks_id,
					"comp_id"							=> $company_id,
					"emp_id"							=> $emp_id,
					"work_schedule_id"					=> $work_schedule_id,
					"date"								=> date("Y-m-d",strtotime($new_employee_timein_date)),
					"time_in_status"					=> 'pending',
					"corrected"							=> 'Yes',
					"reason"							=> $reason,
					"time_in"							=> $new_time_in,
					"lunch_out"							=> $new_lunch_out,
					"lunch_in"							=> $new_lunch_in,
					"time_out"							=> $new_time_out,
					"tardiness_min"						=> $tardiness_min,
					"undertime_min"						=> $undertime_min,
					"total_hours"						=> $total_hours,
			         "total_hours_required"				=> $change_log_total_hours_required,
					"flag_tardiness_undertime"			=> $flag_tu,
					"change_log_date_filed"				=> date("Y-m-d H:i:s"),
					"change_log_tardiness_min"			=> $tardiness_min,
					"change_log_undertime_min"			=> $undertime_min,
					"change_log_time_in"				=> $new_time_in,
					"change_log_lunch_out"				=> $new_lunch_out,
					"change_log_lunch_in"				=> $new_lunch_in,
					"change_log_time_out"				=> $new_time_out,
					"change_log_total_hours"			=> $change_log_total_hours,
					"change_log_total_hours_required"	=> $change_log_total_hours_required,
					"source"							=> "EP",
					"flag_halfday"						=> $flag_halfday,
					"late_min"							=> $late_min,
        			    "overbreak_min"						=> $overbreak_min,
        			    "change_log_late_min"				 => $late_min,
        			    "change_log_overbreak_min"			=> $overbreak_min,
			         "flag_payroll_correction"          => $flag_payroll_correction
			);
			
			$this->db->insert('schedule_blocks_time_in', $date_insert);
		}
		
		public function get_split_timein_approved($emp_id,$company_id,$employee_time_in_id,$for_changelog = false) {
			$w = array(
					"emp_id" 				=> $emp_id,
					"comp_id" 				=> $company_id,
					"employee_time_in_id"	=> $employee_time_in_id,
					"status" 				=> "Active"
			);			
			
			$this->db->where($w);
			if($for_changelog) {
				$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL OR time_in_status = 'reject')");
			} else {
				$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
			}
			
			$this->db->order_by("time_in", "asc");
			$query = $this->db->get("schedule_blocks_time_in");
			
			$result = $query->result();
				
			return ($result) ? $result : false;
		}
		
		public function get_split_timein_except($emp_id,$company_id,$employee_time_in_id,$schedule_blocks_time_in_id) {
			$w = array(
					"emp_id" 						=> $emp_id,
					"comp_id" 						=> $company_id,
					"employee_time_in_id"			=> $employee_time_in_id,
					"status" 						=> "Active",
					"schedule_blocks_time_in_id !="	=> $schedule_blocks_time_in_id
			);
				
			$this->db->where($w);
			$query = $this->db->get("schedule_blocks_time_in");
				
			$result = $query->result();
		
			return ($result) ? $result : false;
		}
		
		public function save_approved_split($concat_time_in,$concat_time_out,$approval_status,$change_log_total_hours_required,$change_log_tardiness_min,
				$change_log_undertime_min,$late_min,$overbreak_min,$employee_time_in_id,$comp_id) {
			
			$fields = array(
					"time_in" 							=> $concat_time_in,
					"time_out" 							=> $concat_time_out,
					"split_status" 						=> $approval_status,
					"time_in_status"					=> $approval_status,
					"total_hours_required"				=> $change_log_total_hours_required,
					"tardiness_min"						=> $change_log_tardiness_min,
					"undertime_min"						=> $change_log_undertime_min,
					"late_min"							=> $late_min,
					"overbreak_min"						=> $overbreak_min,
					"change_log_total_hours_required"	=> $change_log_total_hours_required,
					"change_log_tardiness_min"			=> $change_log_tardiness_min,
					"change_log_undertime_min"			=> $change_log_undertime_min,
			);
			
			$where = array(
					"employee_time_in_id"	=> $employee_time_in_id,
					"comp_id"				=> $comp_id
			);
			
			$this->db->where($where);
			$this->db->update('employee_time_in', $fields);
		}
		
		public function check_greater_than_2_leaves($emp_id, $company_id, $shift_date) {
			$where = array(
					"emp_id" 						=> $emp_id,
					"company_id" 					=> $company_id,
					"shift_date" 					=> $shift_date,
					"status" 						=> "Active",
					#"leave_application_status !=" 	=> 'approve'
			        # "leave_application_status"  	=> 'approve'
			);
		
			$this->db->where($where);
			$this->db->where("(flag_parent IS NOT NULL)");
			$this->db->where("(leave_application_status = 'approve' OR leave_application_status = 'pending')");
			#$this->db->where("(leave_application_status != 'reject' OR leave_application_status != 'cancelled')");
			$query = $this->db->get("employee_leaves_application");
			$res = $query->result();
			
			return ($query->num_rows() <= 0) ? 0 : $query->num_rows();
		}
		
		public function get_employee_documents_details_list($emp_id,$comp_id, $limit = "", $start = "", $search = "", $sort_by = "", $sort = "ASC"){
			$konsum_key = konsum_key();
			$start = intval($start);
			$limit = intval($limit);
			$sort_array = array(
					"e.first_name",
					"e.last_name",
					"ec.employment_certificate_settings_id",
					"ec.document_type",
					"ec.created_date"
			);
			$select = array(
					"*",
					"ec.created_date AS date_requested",
					"ec.employee_certificate_id AS employee_certificate_ids"
			);
			$where = array(
					"ec.company_id" => $comp_id,
					"e.emp_id" => $emp_id,
					"a.user_type_id" => "5",
					"ec.status" => "Active"
			);
			$this->db->select($select);
			$this->edb->where($where);
			if($search != "" && $search != "all"){
				$search = $this->db->escape_like_str(stripslashes(clean_input($search)));
				
				$this->db->where("CONVERT(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')USING latin1) LIKE '%".$search."%' OR CONVERT(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) USING latin1)  LIKE '%".$search."%'", NULL, FALSE);
			}
			
			$this->edb->join('employee AS e','e.emp_id = ec.emp_id','LEFT');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$this->edb->join("approval_groups_via_groups AS agg","epi.document_approval_grp = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
			$this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
			$this->edb->join("employment_certificate_settings AS ecs","ecs.employment_certificate_settings_id = ec.employment_certificate_settings_id","LEFT");
			$this->edb->join("approval_document AS ad","ad.employee_certificate_id = ec.employee_certificate_id","LEFT");
			$this->db->group_by("ec.employee_certificate_id");
			
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			}
			else{
				$this->db->order_by("ec.employee_certificate_id","DESC");
			}
			
			if($limit == "" && $start == "") {
			    $sql = $this->edb->get("employee_certificate AS ec");
			    return $sql->num_rows();
			} else {
			    $sql = $this->edb->get("employee_certificate AS ec",$limit,$start);
			    $result = $sql->result();
			    
			    return ($result) ? $result : false;
			}
			
		}
		
		public function get_document_settings($company_id){
			$w = array(
					"company_id"=>$company_id,
					"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("employment_certificate_settings");
			$r = $q->result();
			$q->free_result();
			return ($r) ? $r : FALSE ;
		}
		
		public function get_employee_qr_code($id,$comp_id,$emp_id){
			$where = array(
					"ec.company_id" => $comp_id,
					"ec.employee_certificate_id" => $id,
					"ec.status" => "Active",
					"ec.emp_id" => $emp_id
			);
			$select = array(
					"*",
					"ec.created_date AS date_requested"
			);
			$this->db->select($select);
			$this->edb->where($where);
			$this->edb->join('basic_pay_adjustment AS bpa','bpa.emp_id = ec.emp_id','LEFT');
			$this->edb->join('company AS c','c.company_id = ec.company_id','LEFT');
			$this->edb->join('employee AS e','e.emp_id = ec.emp_id','LEFT');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("employment_certificate_settings AS ecs","ecs.employment_certificate_settings_id = ec.employment_certificate_settings_id","LEFT");
			$sql = $this->edb->get("employee_certificate AS ec");
			$row= $sql->row();
		
			return ($row) ? $row: false;
		}
		
		public function get_document_approval_grp($emp_id,$company_id){
			$w = array(
					"emp_id" => $emp_id,
					"company_id"=>$company_id,
					"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("employee_payroll_information");
			$r = $q->row();
			$q->free_result();
			return ($r) ? $r : FALSE ;
		}
		
		public function get_employee_document_approver($emp_id, $company_id){
			$w = array(
					'epi.emp_id'=>$emp_id,
					'epi.company_id'=>$company_id
			);
			$this->edb->where($w);
			$this->edb->join("employee AS e","epi.document_approval_grp = e.emp_id","left");
			$this->edb->join("accounts AS a","e.account_id = a.account_id","left");
			$q = $this->edb->get("employee_payroll_information AS epi");
			return ($q) ? $q->row() : FALSE ;
		}
		
		public function track_next_approver($document_approval_grp, $level, $current_approver_emp_id, $appr_process_id){
			$next_approver = array();
			$where = array(
					"level" => $level,
					"approval_groups_via_groups_id" => $document_approval_grp,
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
		
		public function check_assigned_document($document_approval_grp, $level, $emp_id = NULL){
			$where = array(
					"emp_id" => ($emp_id != NULL) ? $emp_id : $this->session->userdata("emp_id"),
					"level" => $level,
					"approval_groups_via_groups_id" => $document_approval_grp
			);
			$this->db->where($where);
			$query = $this->db->get("approval_groups");
			$row = $query->row();
		
			return ($row) ? true : false;
		}
		
		public function get_employee_documents_details($id,$comp_id,$emp_id){
			$where = array(
					"ec.company_id" => $comp_id,
					"ec.employee_certificate_id" => $id,
					"ec.status" => "Active",
					"ec.emp_id" => $emp_id
			);
			$select = array(
					"*",
					"ec.created_date AS date_requested"
			);
			$this->db->select($select);
			$this->edb->where($where);
			$this->edb->join('employee AS e','e.emp_id = ec.emp_id','LEFT');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("employment_certificate_settings AS ecs","ecs.employment_certificate_settings_id = ec.employment_certificate_settings_id","LEFT");
			$sql = $this->edb->get("employee_certificate AS ec");
			$result = $sql->result();
		
			return ($result) ? $result : false;
		}
		
		public function document_information($val,$emp_id){
			$w = array(
					"ec.employee_certificate_id"=>$val,
					"ec.status"=>"Active",
					"ec.emp_id" => $emp_id
			);
			$this->db->where($w);
			$this->db->join("approval_document AS ao","ec.employee_certificate_id = ao.employee_certificate_id","LEFT");
			$q = $this->db->get("employee_certificate AS ec");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
		
		public function get_employee_documents_docs($id,$comp_id,$emp_id){
			$where = array(
					"ec.company_id" => $comp_id,
					"ec.employee_certificate_id" => $id,
					"ec.status" => "Active",
					"ec.emp_id" => $emp_id
			);
			$select = array(
					"*",
					"ec.created_date AS date_requested"
			);
			$this->db->select($select);
			$this->edb->where($where);
			$this->edb->join('employee AS e','e.emp_id = ec.emp_id','LEFT');
			$this->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
			$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
			$this->edb->join("employment_certificate_settings AS ecs","ecs.employment_certificate_settings_id = ec.employment_certificate_settings_id","LEFT");
			$sql = $this->edb->get("employee_certificate AS ec");
			$row= $sql->row();
		
			return ($row) ? $row: false;
		}
		
		public function get_contribution_history($comp_id,$emp_id,$type="",$sort_by="", $sort="", $limit = 10000, $start = 0)
		{
			$sort_array = array(
					"pay_date",
					"voluntary_contributions",
					"hdmf_modified",
					"period_from"
			);
			
			if($type == "voluntary_contributions") {
				$w = array(
						'voluntary_contributions >'	=> 0,
				);
			} elseif ($type == "hdmf_modified") {
				$w = array(
						'hdmf_modified >'	=> 0,
				);
			} else {
				$w = array(
						'voluntary_contributions >'	=> 0,
						'hdmf_modified >'			=> 0,
				);
			}
			
			$where = array(
					'company_id'			=> $comp_id,
					'emp_id'				=> $emp_id,
					"YEAR(payroll_date)" 	=> date('Y'),
			);
			
			$this->db->where($where);
			$this->edb->where($w);
			
			if($sort_by != ""){
				if(in_array($sort_by, $sort_array)){
					$this->db->order_by($sort_by,$sort);
				}
			} else {
				$this->db->order_by("pay_date","desc");
			}
			
			$q = $this->edb->get('payroll_payslip',$limit,$start);
		
			$result = $q->result();
			return ($result) ? $result : false;
		}
		
		public function get_contribution_to_date_voluntary($comp_id,$emp_id,$pay_date)
		{
			$w = array(
					'voluntary_contributions >'	=> 0,
					'hdmf_modified >'			=> 0,
			);
				
			$where = array(
					'company_id'			=> $comp_id,
					'emp_id'				=> $emp_id,
					"payroll_date >="		=> date('Y-01-01'),
					"payroll_date <="		=> $pay_date
			);
			
			$this->db->where($where);
			$this->edb->where($w);
			
			#$this->db->order_by("pay_date","asc");
				
			$q = $this->edb->get('payroll_payslip');
		
			$result = $q->result();
			
			$new_res = array();
			$voluntary_contributions = 0;
			$hdmf_modified = 0;
			if($result) {
				foreach ($result as $row) {
					$voluntary_contributions += $row->voluntary_contributions;
					$hdmf_modified += $row->hdmf_modified;
					$temp = array(
							"voluntary_contributions_to_date" => $voluntary_contributions,
							"hdmf_modified_to_date" => $hdmf_modified,
					);
					
					array_push($new_res,$temp);
				}
			}
			
			return $new_res;
		}
		
		public function get_contribution_history_count($comp_id,$emp_id,$type="")
		{
				
			$where = array(
					'company_id'			=> $comp_id,
					'emp_id'				=> $emp_id,
					"YEAR(payroll_date)" 	=> date('Y'),
			);
		
			if($type == "voluntary_contributions") {
				$w = array(
						'voluntary_contributions >'	=> 0,
				);
			} elseif ($type == "hdmf_modified") {
				$w = array(
						'hdmf_modified >'	=> 0,
				);
			} else {
				$w = array(
						'voluntary_contributions >'	=> 0,
						'hdmf_modified >'			=> 0,
				);
			}
				
			$this->db->where($where);
			$this->edb->where($w);
				
			$q = $this->edb->get('payroll_payslip');
		
			$result = $q->num_rows();
				
			return $result;
		}
		
		public function get_contribution_history_download($comp_id,$emp_id,$type="")
		{
			$where = array(
					'company_id'			=> $comp_id,
					'emp_id'				=> $emp_id,
					"YEAR(payroll_date)" 	=> date('Y'),
			);
		
			if($type == "voluntary_contributions") {
				$w = array(
						'voluntary_contributions >'	=> 0,
				);
			} elseif ($type == "hdmf_modified") {
				$w = array(
						'hdmf_modified >'	=> 0,
				);
			} else {
				$w = array(
						'voluntary_contributions >'	=> 0,
						'hdmf_modified >'			=> 0,
				);
			}
			
			$this->db->where($where);
			$this->edb->where($w);			
			$this->db->order_by("pay_date","desc");
			$q = $this->edb->get('payroll_payslip');
		
			$result = $q->result();
			$q->free_result();
			
			return ($result) ? $result : false;
		}
		
		public function document_info($id){
			$select = array(
					"ad.token",
					"ad.token_level",
					"ad.level",
					"ad.approve_by_head",
					"ad.approve_by_hr",
					"ec.created_date AS date_requested",
					"ecs.document_type",
					"ecs.body",
					"ecs.document_name",
					"ecs.company_logo",
					"ecs.qr_code_attach",
					"e.emp_id",
					"ecs.created_by_account_id",
					"ecs.company_id",
					"ec.qr_code_img_name"
			);
			$sel = array(
					"e.first_name",
					"e.last_name"
			);
			$where = array(
					"ec.employee_certificate_id" => $id
			);
			$this->db->select($select);
			$this->edb->select($sel);
			$this->db->where($where);
			$this->edb->join("employee AS e", "e.emp_id = ec.emp_id", "LEFT");
			$this->db->join("approval_document AS ad","ad.employee_certificate_id = ec.employee_certificate_id","LEFT");
			$this->db->join("employment_certificate_settings AS ecs", "ecs.employment_certificate_settings_id = ec.employment_certificate_settings_id", "LEFT");
			$query = $this->edb->get("employee_certificate AS ec");
			$row = $query->row();
		
			return ($row) ? $row : false;
		}
		
		public function time_in_correction_list_counter($comp_id, $emp_id,$from="",$to=""){
		    
		    $sel = array(
		        'COUNT(employee_time_in_correction_id) AS total_row'
		    );
		    
		    if($from!="" && $from!="none" && $to!="" && $to!="none"){
		        $this->db->where('date BETWEEN "'.$from.'" AND "'.$to.'"',NULL,FALSE);
		    }
		    
		    $where = array(
		        'company_id'  => $comp_id,
		        'emp_id'      => $emp_id
		    );
		    
		    $this->db->select($sel);
		    $this->db->where($where);
		    $q = $this->db->get('employee_time_in_correction');
		    $r = $q->row();
		    
		    return ($r) ? $r->total_row : false;
		}
		
		public function time_in_list_correction($limit, $start, $comp_id, $emp_id, $sort="DESC", $sort_by,$date_from="",$date_to=""){
		    $sort_array = array(
		        "date",
		        "date_approved"
		    );
		    
		    $sel = array(
		        "source",
		        "employee_time_in_id",
		        "time_in",
		        "time_out",
		        "lunch_out",
		        "lunch_in",
		        "emp_id",
		        "date",
		        "undertime_min",
		        "tardiness_min",
		        "workschedule_id",
		        "employee_time_in_correction_id",
		        "company_id",
		        "late_min",
		        "overbreak_min",
		        "total_hours_required",
		        "total_hours",
		        "absent_min",
		        "date_approved"
		    );
		    
		    $w = array(
		        'emp_id'      => $emp_id,
		        'company_id'  => $comp_id,
		        'status'      => 'Active'
		    );
		    
		    $this->db->select($sel);
		    $this->db->where($w);
		    
		    if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
		        $this->db->where('date BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
		    }
		    
		    if($sort_by != ""){
		        if(in_array($sort_by, $sort_array)){
		            $this->db->order_by($sort_by,$sort);
		        }
		    }else{
		        $this->db->order_by('date','DESC');
		    }
		    
		    if($start==0){
		        $sql = $this->db->get('employee_time_in_correction',$limit);
		    }else{
		        $sql = $this->db->get('employee_time_in_correction',$start,$limit);
		    }
		    
		    if($sql->num_rows() > 0){
		        $results = $sql->result();
		        $sql->free_result();
		        
		        return $results;
		    }else{
		        return FALSE;
		    }
		}
		
		public function get_app_id_in_employe_timein($emp_id, $company_id, $date, $time_in, $time_out) {		    
		    $w = array(
		        "emp_id" => $emp_id,
		        "comp_id" => $company_id,
		        "date" => $date,
		        "time_in" => $time_in,
		        "time_out" => $time_out
		    );
		    
		    $this->db->where($w);
		    $q = $this->db->get("employee_time_in");
		    $r = $q->row();
		    
		    return ($r) ? $r->employee_time_in_id : false;
		}
		
		public function check_document_manager_settings($company_id) {
		    $w = array(
		        "company_id" => $company_id,
		        "status" => "Active"
		    );
		    
		    $this->db->where($w);
		    $q = $this->db->get("employment_certificate_settings");
		    $r = $q->result();
		    
		    return ($r) ? true : false;
		}
		
		public function timeins_correction_application_count($company_id,$emp_id,$from="",$to=""){
		    if(is_numeric($company_id) && is_numeric($emp_id)){
		        $between="";
		        
		        $this->db->order_by('etic.time_in','DESC');
		        $where = array(
		            'etic.company_id'   => $company_id,
		            'etic.status'   => 'Active',
		            'etic.emp_id' => $emp_id
		        );
		        
		        if($from!="" && $from!="none" && $to!="" && $to!="none"){
		            $this->db->where('date BETWEEN "'.$from.'" AND "'.$to.'"',NULL,FALSE);
		        }
		        
		        $this->edb->where($where);
		        
		        $this->db->select('CONCAT('.$this->edb->decrypt('e.first_name').'," ",'.
		        $this->edb->decrypt('e.last_name').') as full_name',FALSE);
		        $this->edb->join('employee AS e','e.emp_id = etic.emp_id',"INNER");
		        $this->edb->join('accounts AS a','a.account_id = e.account_id',"INNER");
		        $this->edb->join('employee_payroll_information AS epi','epi.emp_id = etic.emp_id','left');
		        $this->edb->order_by("e.last_name","ASC");
		        $query = $this->edb->get('employee_time_in_correction AS etic');
		        $row = $query->row();
		        $num_row = $query->num_rows();
		        $query->free_result();
		        
		        return $num_row ? $num_row: 0;
		    }else{
		        return false;
		    }
		}
		
		public function timeins_correction_list($company_id,$emp_id,$limit,$start,$date_from,$date_to,$filter = array()){
		    if(is_numeric($company_id)){
		        $start = intval($start);
		        $limit = intval($limit);
		        
		        $this->db->order_by('etic.time_in','DESC');
		        $where = array(
		            'etic.company_id'   => $company_id,
		            'etic.emp_id'	=>  $emp_id,
		            'etic.status'   => 'Active'
		        );
		        
		        if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
		            $this->db->where('etic.date BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
		        }
		        
		        $this->edb->where($where);
		        
		        $arr = array('time_in' => 'etic.time_in',
		            'lunch_out' => 'etic.lunch_out',
		            'lunch_in' => 'etic.lunch_in',
		            'time_out' => 'etic.time_out',
		            'total_hours_required' => 'etic.total_hours_required',
		            'total_hours' => 'etic.total_hours',
		            'late_min' => 'etic.late_min',
		            'overbreak_min' => 'etic.overbreak_min',
		            'tardiness_min'  => 'etic.tardiness_min',
		            'undertime_min' => 'etic.undertime_min',
		            'absent_min' => 'etic.absent_min',
		            'source' => 'etic.source',
		            'date' => 'etic.date',
		            'payroll_cloud_id' => 'a.payroll_cloud_id',
		            'work_schedule_id' => 'etic.workschedule_id',
		            'employee_time_in_correction_id' => 'etic.employee_time_in_correction_id',
		            'company_id' => 'etic.company_id',
		            'first_name' => 'e.first_name',
		            'last_name' => 'e.last_name',
		            'middle_name' => 'e.middle_name',
		            'department_id' => 'epi.department_id',
		            'emp_id' => 'etic.emp_id',
		            'account_id' => 'a.account_id'
		        );
		        $this->edb->select($arr);
		        
		        if($filter){
		            
		            $this->db->where("etic.emp_id IN ({$filter})");
		        }
		        
		        $this->edb->join('employee AS e','e.emp_id = etic.emp_id',"INNER");
		        $this->edb->join('accounts AS a','a.account_id = e.account_id',"INNER");
		        $this->edb->join('employee_payroll_information AS epi','epi.emp_id = etic.emp_id','left');
		        $query = $this->edb->get('employee_time_in_correction AS etic',$limit,$start);
		        
		        $result = $query->result();
		        $query->free_result();
		        return $result;
		    }else{
		        return false;
		    }
		}
		
		public function assigned_work_schedule_v3($company_id,$emp_id){
		    $res = array();
		    
		    $sel = array(
		        "ess.emp_id",
		        "ess.shifts_schedule_id",
		        "ess.work_schedule_id",
		        "ws.bg_color",
		        "ws.flag_custom",
		        "ws.work_type_name",
		        "ws.name",
		        "ws.category_id",
		        "ess.valid_from",
		        "ess.company_id"
		    );
		    
		    $w = array(
		        "ess.emp_id" => $emp_id,
		        "ess.company_id" => $company_id,
		        "ess.status" => "Active"
		    );
		    
		    $this->db->select($sel);
		    $this->db->where($w);
		    $this->db->join("work_schedule AS ws","ws.work_schedule_id = ess.work_schedule_id","LEFT");
		    $q = $this->db->get("employee_shifts_schedule AS ess");
		    $r = $q->result();
		    
		    if($r){
		        foreach ($r as $row) {
		            $temp = array(
		                "valid_from" => $row->valid_from,
		                "emp_id" => $row->emp_id,
		                "shifts_schedule_id" => $row->shifts_schedule_id,
		                "bg_color" => $row->bg_color,
		                "flag_custom" => $row->flag_custom,
		                "company_id" => $row->company_id,
		                "work_type_name" => $row->work_type_name,
		                "name" => $row->name,
		                "category_id" => $row->category_id,
		                "work_schedule_id" => $row->work_schedule_id,
		                "filter_query" => "work_schedule_id-{$row->work_schedule_id}"
		            );
		            
		            array_push($res, $temp);
		        }
		        return $res;
		    }
		    #return ($r) ? $r : FALSE;
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
		
		public function get_approver_name_timesheet($emp_id,$comp_id,$grp){
		    $w = array(
		        'epi.emp_id'=>$emp_id,
		        'epi.company_id'=>$comp_id
		    );
		    $this->edb->where($w);
		    
		    if($grp == "Add Timesheet") {
		        $this->edb->join("employee AS e","epi.add_logs_approval_grp = e.emp_id","LEFT");
		    } elseif ($grp == "Timesheet Adjustment") {
		        $this->edb->join("employee AS e","epi.attendance_adjustment_approval_grp = e.emp_id","LEFT");
		    } else {
		        $this->edb->join("employee AS e","epi.eBundy_approval_grp = e.emp_id","LEFT");
		    }
		    
		    $this->edb->join("accounts AS a","e.account_id = a.account_id","LEFT");
		    $q = $this->edb->get("employee_payroll_information AS epi");
		    
		    return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		
		public function check_rest_day_sched($workday,$work_schedule_id,$comp_id){
		    $w = array(
		        "work_schedule_id"=>$work_schedule_id,
		        "company_id"=>$comp_id,
		        "rest_day"=>$workday,
		        'status'		   => 'Active'
		    );
		    $this->db->where($w);
		    $q = $this->db->get("rest_day");
		    return ($q->num_rows() > 0) ? TRUE : FALSE ;
		}
		
		public function get_split_time_by_schedule_blocks_id($schedule_blocks_id,$company_id) {
		    $w = array(
		        "schedule_blocks_id" => $schedule_blocks_id,
		        "company_id" => $company_id
		    );
		    
		    $this->db->where($w);
		    $q = $this->db->get("schedule_blocks");
		    $r = $q->row();
		    
		    return ($r) ? $r : false;
		}
		
		public function total_hours_for_all_split_blocks($currentdate,$emp_id,$work_schedule_id,$comp_id){
		    
		    $w_date = array(
		        "em.valid_from <="		=>	$currentdate,
		        "em.until >="			=>	$currentdate
		    );
		    $this->db->where($w_date);
		    
		    $w_ws = array(
		        "em.work_schedule_id"=>$work_schedule_id,
		        "em.company_id"=>$comp_id,
		        "em.emp_id" => $emp_id
		    );
		    $this->db->where($w_ws);
		    
		    $this->edb->join("employee_shifts_schedule AS es","es.shifts_schedule_id = em.shifts_schedule_id","LEFT");
		    $this->edb->join("schedule_blocks AS sb","sb.schedule_blocks_id = em.schedule_blocks_id","LEFT");
		    $q_ws = $this->edb->get("employee_sched_block AS em");
		    $r_ws = $q_ws->result();
		    
		    if($r_ws) {
		        $total_hrs = 0;
		        foreach ($r_ws as $row) {
		            $total_hrs += $row->total_hours_work_per_block;
		        }
		        
		        return $total_hrs;
		    } else {
		        return 0;
		    }
		}
		
		public function for_leave_hoursworked_work_start_time_ws_v2($emp_id,$comp_id,$work_schedule,$date,$leave_request_type=""){
		    // for regular schedules and workshift
		    $where_hw = array(
		        "work_schedule_id"=>$work_schedule,
		        "company_id"=>$comp_id,
		        "days_of_work"=>date('l', strtotime($date))
		    );
		    
		    // check regular schedules
		    $this->db->where($where_hw);
		    $uws_q = $this->db->get("regular_schedule");
		    $uws_r = $uws_q->row();
		    
		    if($uws_r){
		        if(($uws_r->latest_time_in_allowed != "" || $uws_r->latest_time_in_allowed != NULL) && $leave_request_type != ""){
		            $boundary = date('H:i:s', strtotime($uws_r->work_start_time.' + '.$uws_r->latest_time_in_allowed.' minutes'));
		            $input_time = date('H:i:s', strtotime($date));
		            
		            if(strtotime($input_time) > strtotime($boundary)) {
		                return date('H:i:s', strtotime($uws_r->work_start_time.' + '.$uws_r->latest_time_in_allowed.' minutes'));
		            } else {
		                if(strtotime($uws_r->work_start_time) > strtotime($input_time)){
		                    $start_new_diff = 0;
		                } else {
		                    $start_new_diff = (strtotime($input_time) - strtotime($uws_r->work_start_time)) / 60;
		                }
		                
		                return date('H:i:s', strtotime($uws_r->work_start_time.' + '.$start_new_diff.' minutes'));
		            }
		            
		        }else{
		            $this->db->where($where_hw);
		            $sql_hw = $this->db->get("regular_schedule");
		            $row_hw = $sql_hw->row();
		            
		            // for regular schedules and workshift
		            if($row_hw){
		                return $row_hw->work_start_time;
		            }else{
		                $this->db->where($where_hw);
		                $this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
		                $sql_w = $this->db->get("split_schedule");
		                $row_w = $sql_w->row();
		                return ($row_w) ? $row_w->start_time : FALSE ;
		            }
		        }
		    }else{
		        return FALSE;
		    }
		    
		}
		
		public function for_leave_hoursworked_work_end_time_ws_v2($emp_id,$comp_id,$work_schedule,$date,$leave_request_type=""){
		    // for regular schedules and workshift
		    $where_hw = array(
		        "work_schedule_id"=>$work_schedule,
		        "company_id"=>$comp_id,
		        "days_of_work" => date("l", strtotime($date))
		    );
		    
		    $this->db->where($where_hw);
		    $sql_hw = $this->db->get("regular_schedule");
		    $row_hw = $sql_hw->row();
		    
		    // for regular schedules and workshift
		    if($row_hw) {
		        if(($row_hw->latest_time_in_allowed != "" || $row_hw->latest_time_in_allowed != NULL) && $leave_request_type != ""){
		        #if($row_hw->latest_time_in_allowed != "" || $row_hw->latest_time_in_allowed != NULL){
		            return date('H:i:s', strtotime($row_hw->work_end_time.' + '.$row_hw->latest_time_in_allowed.' minutes'));
		            
		        }else{
		            $this->db->where($where_hw);
		            $sql_hw = $this->db->get("regular_schedule");
		            $row_hw1 = $sql_hw->row();
		            
		            // for regular schedules and workshift
		            if($row_hw1){
		                return $row_hw1->work_end_time;
		            }else{
		                $this->db->where($where_hw);
		                $this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
		                $sql_w = $this->db->get("split_schedule");
		                $row_w = $sql_w->row();
		                return ($row_w) ? $row_w->end_time : FALSE ;
		            }
		        }
		    } else {
		        return FALSE;
		    }
		}
		
		public function check_if_migrated_v3($company_id){
		    $where = array(
		        "company_id" => $company_id,
		        "status" => "Active",
		        "flag_migrate_v3" => "1"
		    );
		    $this->db->where($where);
		    $query = $this->db->get("leave_type");
		    $row = $query->row();
		    
		    return ($row) ? true : false;
		}
		
		public function get_all_employee_timein($company_id,$emp_ids="",$min_range="",$max_range=""){
		    $row_array	= array();
		    $arrx		= array(
		        'employee_time_in_id',
		        'work_schedule_id',
		        "emp_id",
		        "date",
		        "time_in",
		        "lunch_out",
		        "lunch_in",
		        "time_out",
		        "total_hours",
		        "total_hours_required",
		        "corrected",
		        "reason",
		        "time_in_status",
		        "overbreak_min",
		        "late_min",
		        "tardiness_min",
		        "undertime_min",
		        "absent_min",
		        "source",
		        "last_source",
		        "flag_time_in",
		        "flag_halfday",
		        "location",
		        "split_status",
		        "flag_on_leave",
		        "flag_delete_on_hours",
		        "flag_regular_or_excess"
		    );
		    
		    $this->db->select($arrx);
		    
		    $w 		= array(
		        "status" 			=> "Active",
		        "comp_id" 			=> $company_id
		    );
		    
		    if($emp_ids){
		        $this->db->where_in("emp_id",$emp_ids);
		    }
		    
		    if($min_range != "" && $max_range != ""){
		        $w1 = array(
		            "date >="	=> $min_range,
		            "date <="	=> $max_range,
		        );
		        $this->db->where($w1);
		    }
		    
		    //$this->db->where_in("time_in_status",$time_in_status);
		    #$this->db->where("(time_in_status = 'approved' OR time_in_status is NULL)",FALSE,FALSE);
		    $this->db->where($w);
		    $this->db->order_by("date","DESC");
		    $q 		= $this->db->get("employee_time_in AS eti");
		    $result = $q->result();
		    
		    if($result){
		        foreach ($result as $key =>$r1){
		            $wd 	= array(
		                "employee_time_in_id"		=> $r1->employee_time_in_id,
		                "work_schedule_id"			=> $r1->work_schedule_id,
		                "emp_id"					=> $r1->emp_id,
		                "date"						=> $r1->date,
		                "time_in"					=> $r1->time_in,
		                "lunch_out"					=> $r1->lunch_out,
		                "lunch_in"					=> $r1->lunch_in,
		                "time_out"					=> $r1->time_out,
		                "total_hours"				=> $r1->total_hours,
		                "total_hours_required"		=> $r1->total_hours_required,
		                "corrected"					=> $r1->corrected,
		                "reason"					=> $r1->reason,
		                "time_in_status"			=> $r1->time_in_status,
		                "overbreak_min"				=> $r1->overbreak_min,
		                "late_min"					=> $r1->late_min,
		                "tardiness_min"				=> $r1->tardiness_min,
		                "undertime_min"				=> $r1->undertime_min,
		                "absent_min"				=> $r1->absent_min,
		                "source"					=> $r1->source,
		                "last_source"				=> $r1->last_source,
		                "flag_time_in"				=> $r1->flag_time_in,
		                "flag_halfday"				=> $r1->flag_halfday,
		                "location"					=> $r1->location,
		                "split_status"				=> $r1->split_status,
		                "flag_on_leave"				=> $r1->flag_on_leave,
		                "flag_delete_on_hours"		=> $r1->flag_delete_on_hours,
		                "flag_regular_or_excess"	=> $r1->flag_regular_or_excess,
		                "custom_search"				=> "emp_id_timeins_{$r1->emp_id}",
		                "custom_searchv2"			=> "timeins_id_{$r1->employee_time_in_id}",
		                "custom_searchv3"			=> "emp_id_timeins_date_{$r1->emp_id}_{$r1->date}"
		            );
		            array_push($row_array, $wd);
		        }
		    }
		    
		    return $row_array;
		}
		
		public function check_emp_no_v2($emp_id,$comp_id){
		    $w = array(
		        "e.emp_id"=>$emp_id,
		        "e.company_id"=>$comp_id
		    );
		    
		    $s = array(
		        "a.payroll_cloud_id"
		    );
		    
		    $this->edb->select($s);
		    $this->db->where($w);
		    $this->db->join("accounts AS a","e.account_id = a.account_id","LEFT");
		    $q = $this->edb->get("employee AS e");
		    $r = $q->row();
		    
		    return ($r) ? $r->payroll_cloud_id : FALSE ;
		}
		
		public function check_if_all_child_pending($emp_id,$comp_id,$id) {
		    $w = array(
		        "emp_id" => $emp_id,
		        "comp_id" => $comp_id,
		        "schedule_blocks_time_in_id" => $id,
		        "status" => "Active",
		        "time_in_status" => "pending"
		    );
		    
		    $this->db->where($w);
		    $q = $this->db->get("schedule_blocks_time_in");
		    $r = $q->result();
		    
		    return ($r) ? TRUE : FALSE ;
		}
		
		public function get_all_child_block($emp_id,$comp_id,$id) {
		    $w = array(
		        "emp_id" => $emp_id,
		        "comp_id" => $comp_id,
		        "employee_time_in_id" => $id,
		        "status" => "Active",
		        "time_in_status !=" => "reject"
		    );
		    
		    $this->db->where($w);
		    $this->db->order_by("time_in","DESC");
		    $q = $this->db->get("schedule_blocks_time_in");
		    $r = $q->result();
		    
		    return ($r) ? $r : FALSE ;
		}
		
		public function get_valid_split_logs($emp_id,$comp_id,$id) {
		    $w = array(
		        "emp_id" => $emp_id,
		        "comp_id" => $comp_id,
		        "employee_time_in_id" => $id,
		        "status" => "Active",
		    );
		    
		    $this->db->where($w);
		    $this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
		    $this->db->order_by("time_in","DESC");
		    $q = $this->db->get("schedule_blocks_time_in");
		    $r = $q->result();
		    
		    return ($r) ? $r : FALSE ;
		}
		
		public function get_split_logs_already_exist($emp_id,$comp_id,$id,$date) {
		    $w = array(
		        "emp_id" => $emp_id,
		        "comp_id" => $comp_id,
		        "schedule_blocks_id" => $id,
		        "status" => "Active",
		        "date" => $date
		    );
		    
		    $this->db->where($w);
		    $this->db->where("(time_in_status != 'reject' OR time_in_status IS NULL)");
		    $q = $this->db->get("schedule_blocks_time_in");
		    $r = $q->row();
		    
		    return ($r) ? $r : FALSE ;
		}
		
		public function get_all_split_logs_already_exist($emp_id,$comp_id,$id) {
		    $w = array(
		        "emp_id" => $emp_id,
		        "comp_id" => $comp_id,
		        "employee_time_in_id" => $id,
		        "status" => "Active"
		    );
		    
		    $this->db->where($w);
		    $this->db->where("(time_in_status != 'reject' OR time_in_status IS NULL)");
		    $q = $this->db->get("schedule_blocks_time_in");
		    $r = $q->result();
		    
		    return ($r) ? $r : FALSE ;
		}
		
		public function emp_split_time_in_information($time_in_id){
		    
		    $w = array(
		        "eti.employee_time_in_id" => $time_in_id,
		        "eti.status" => "Active"
		    );
		    
		    $this->edb->where($w);
		    $this->edb->join("approval_time_in AS ati","eti.employee_time_in_id = ati.time_in_id","LEFT");
		    $this->edb->join("employee AS e","e.emp_id = eti.emp_id","LEFT");
		    $q = $this->edb->get("schedule_blocks_time_in AS eti");
		    $r = $q->row();
		    
		    return ($r) ? $r : FALSE ;
		}
		
		public function get_active_clockin($emp_id,$comp_id){
		    $w = array(
		        "emp_id"=>$emp_id,
		        "company_id"=>$comp_id,
		        "status"=>"Active"
		    );
		    
		    $this->db->where($w);
		    $this->db->order_by('cronjob_clockin_id','DESC');
		    $q = $this->db->get('cronjob_clockin');
		    $r = $q->row();
		    
		    return ($r) ? $r : false;
		}
		
		public function check_date_and_time_in_v2($date, $emp_id, $company_id){
		    $s = array(
		        "time_in",
		        "date",
		        "holiday_approve"
		    );
		    
		    $where = array(
		        'date'=> $date,
		        'emp_id' =>$emp_id,
		        'comp_id' => $company_id,
		        'status' => 'Active',
		    );
		    
		    $this->db->select($s);
		    $this->db->where($where);
		    $this->db->where("(time_in_status != 'reject' OR time_in_status is null)");
		    $q = $this->db->get('employee_time_in');
		    $r = $q->row();
		    
		    return ($r) ? $r : false;
		}
		
		public function get_uniform_sched_time_v2($work_schedule_id,$company_id,$valid_from) {
		    $where = array(
		        "company_id" 		=> $company_id,
		        "work_schedule_id"	=> $work_schedule_id,
		        "days_of_work"		=> date("l", strtotime($valid_from))
		    );
		    
		    $this->db->where($where);
		    $q3 = $this->db->get("regular_schedule");
		    $r3 = $q3->row();
		    
		    return ($r3) ? $r3 : FALSE;
		}
		
		public function overtime_information_v2($val){
		    $w = array(
		        "ot.overtime_id" => $val,
		        "ot.status" => "Active"
		    );
		    
		    $this->edb->where($w);
		    $this->edb->join("approval_overtime AS ao","ot.overtime_id = ao.overtime_id","LEFT");
		    $this->edb->join("employee AS e","e.emp_id = ot.emp_id","LEFT");
		    $q = $this->edb->get("employee_overtime_application AS ot");
		    $r = $q->row();
		    
		    return ($r) ? $r : FALSE ;
		}
		
		public function get_all_pending_leave($company_id, $emp_id,$leave_type_id) {
		    $where = array(
		        "emp_id" => $emp_id,
		        "company_id" => $company_id,
		        "leave_application_status" => "Pending",
		        "leave_type_id" => $leave_type_id,
		        "status" => "Active"
		    );
		    
		    $this->db->where($where);
		    $this->db->where('flag_parent IS NOT NULL');
		    $q = $this->db->get("employee_leaves_application");
		    $r = $q->result();
		    
		    return ($r) ? $r : false;
		}
		
		public function check_existing_overtime_applied_v2($emp_id, $company_id, $year) {
		    $where = array(
		        "emp_id" => $emp_id,
		        "company_id" => $company_id,
		        "status" => "Active",
		        "YEAR(overtime_from)"=> date('Y',strtotime($year)),
		        "overtime_status !=" => "reject"
		    );
		    
		    $this->db->where($where);
		    
		    $q = $this->db->get("employee_overtime_application");
		    $res = $q->result();
		    #last_query();exit();
		    
		    return ($res) ? $res : FALSE;
		}
		
		
		public function get_active_employees_v2_count($employee_id_name="",$limit, $start, $comp_id,$emp_id,$all=false,$sort="",$order="asc", $view_by, $first_level_emp_id,$all_emp_ids){
		    $select = array(
		        'department.department_name',
		        'employee.emp_id',
		        'employee.account_id',
		        'employee.company_id',
		        'employee.last_name',
		        'employee.first_name',
		        'employee.middle_name',
		        'employee.dob',
		        'employee.address',
		        'accounts.payroll_cloud_id',
		        'accounts.email',
		        'accounts.login_mobile_number',
		        'accounts.login_mobile_number_2',
		        'accounts.telephone_number',
		        'employee_payroll_information.employee_payroll_information_id',
		        'employee_payroll_information.department_id',
		        'employee_payroll_information.date_hired',
		        'employee_payroll_information.employee_status',
		        'employee.city',
		        'employee.zipcode',
		        'accounts.flag_primary',
		        'position.position_name'
		    );
		    
		    $where = array();
		    
		    if($employee_id_name !==""){
		        
		        $where['employee.company_id'] = $comp_id;
		        $where['employee.status'] = "Active";
		        $where['accounts.user_type_id'] = "5";
		        $where['accounts.deleted'] = "0";
		        $where['employee.deleted'] = "0";
		        
		        if($view_by == "" || $view_by == "my_team"){
		            $where['edrt.parent_emp_id'] = $emp_id;
		            
		        }else if($view_by == "first_level_dr"){
		            $where['edrt.parent_emp_id'] = $first_level_emp_id;
		            
		        }else if($view_by == "all"){
		            if(count($all_emp_ids) > 0){
		                $this->db->where_in("employee.emp_id",$all_emp_ids);
		            }else{
		                $where['edrt.parent_emp_id'] = $emp_id;
		            }
		        }
		        
		    }else{
		        $where['employee.company_id'] = $comp_id;
		        $where['employee.status'] = "Active";
		        $where['accounts.user_type_id'] = "5";
		        $where['accounts.deleted'] = "0";
		        $where['employee.deleted'] = "0";
		        $where['employee_payroll_information.employee_status'] = "Active";
		        
		        if($view_by == "" || $view_by == "my_team"){
		            $where['edrt.parent_emp_id'] = $emp_id;
		            
		        }else if($view_by == "first_level_dr"){
		            $where['edrt.parent_emp_id'] = $first_level_emp_id;
		            
		        }else if($view_by == "all"){
		            if(count($all_emp_ids) > 0){
		                $this->db->where_in("employee.emp_id",$all_emp_ids);
		            }else{
		                $where['edrt.parent_emp_id'] = $emp_id;
		            }
		        }
		    }
		    
		    $konsum_key = konsum_key();
		    $this->edb->select($select);
		    $this->db->where($where);
		    if($employee_id_name !==""){
		        $employee_id_name = $this->db->escape_like_str(stripslashes(clean_input($employee_id_name)));
		        $where2  = array(
		            'employee.company_id' => $comp_id,
		            'employee.status'    => 'Active',
		            'accounts.user_type_id' => '5',
		            'accounts.deleted'=>'0',
		            'employee.deleted'=>'0',
		            #'edrt.parent_emp_id' => $emp_id
		        );
		        
		        if($view_by == "" || $view_by == "my_team"){
		            $where2['edrt.parent_emp_id'] = $emp_id;
		            
		        }else if($view_by == "first_level_dr"){
		            $where2['edrt.parent_emp_id'] = $first_level_emp_id;
		            
		        }else if($view_by == "all"){
		            if(count($all_emp_ids) > 0){
		                $this->db->where_in("employee.emp_id",$all_emp_ids);
		            }else{
		                $where2['edrt.parent_emp_id'] = $emp_id;
		            }
		        }
		        
		        $where_str = "(convert(CONCAT(AES_DECRYPT(employee.first_name,'{$konsum_key}'),' ',AES_DECRYPT(employee.last_name,'{$konsum_key}')) using latin1) LIKE '%".$employee_id_name."%'
                 OR (convert(AES_DECRYPT(accounts.payroll_cloud_id,'{$konsum_key}') using latin1)='{$employee_id_name}')
                 OR (convert(AES_DECRYPT(accounts.email,'{$konsum_key}') using latin1) LIKE '%".$employee_id_name."%')
                 )";
		        
		        $this->db->where($where2);
		        $this->db->where($where_str, NULL, FALSE);
		        #$this->db->where("convert(CONCAT(AES_DECRYPT(employee.first_name,'{$konsum_key}'),' ',AES_DECRYPT(employee.last_name,'{$konsum_key}')) using latin1) LIKE '%".$employee_id_name."%'", NULL, FALSE); // encrypt
		        #$this->db->or_where("convert(AES_DECRYPT(accounts.payroll_cloud_id,'{$konsum_key}') using latin1)=",$employee_id_name);
		        #$this->db->or_where("convert(AES_DECRYPT(accounts.email,'{$konsum_key}') using latin1) LIKE '%".$employee_id_name."%'"); // for email search
		        #$this->db->where($where2);
		    }
		    $this->edb->join('accounts','accounts.account_id = employee.account_id','INNER');
		    $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = employee.emp_id","LEFT");
		    $this->edb->join('employee_payroll_information','employee_payroll_information.emp_id = employee.emp_id','left');
		    $this->edb->join('department','department.dept_id = employee_payroll_information.department_id','left');
		    $this->edb->join('position', 'position.position_id = employee_payroll_information.position', 'left');
		    
		    $this->db->order_by('CONVERT(UPPER(AES_DECRYPT(employee.last_name, "'.$konsum_key.'")) using latin1)', 'ASC', false);
		    if($all == false){
		        $q = $this->edb->get('employee',$limit,$start);
		    }else{
		        $q = $this->edb->get('employee');
		    }
		    
		    $result = $q->num_rows();
		    return $result;
		}
		
		public function get_active_employees_v2($employee_id_name="",$limit, $start, $comp_id,$emp_id,$all=false,$sort="",$order="asc", $view_by, $first_level_emp_id,$all_emp_ids){
		    $select = array(
		        'department.department_name',
		        'employee.emp_id',
		        'employee.account_id',
		        'employee.company_id',
		        'employee.last_name',
		        'employee.first_name',
		        'employee.middle_name',
		        'employee.dob',
		        'employee.address',
		        'accounts.payroll_cloud_id',
		        'accounts.email',
		        'accounts.login_mobile_number',
		        'accounts.login_mobile_number_2',
		        'accounts.telephone_number',
		        'employee_payroll_information.employee_payroll_information_id',
		        'employee_payroll_information.department_id',
		        'employee_payroll_information.date_hired',
		        'employee_payroll_information.employee_status',
		        'employee.city',
		        'employee.zipcode',
		        'accounts.flag_primary',
		        'position.position_name'
		    );
		    $where = array();
		    if($employee_id_name !==""){
		        $where['employee.company_id'] = $comp_id;
		        $where['employee.status'] = "Active";
		        $where['accounts.user_type_id'] = "5";
		        $where['accounts.deleted'] = "0";
		        $where['employee.deleted'] = "0";
		        if($view_by == "" || $view_by == "my_team"){
		            $where['edrt.parent_emp_id'] = $emp_id;
		        }else if($view_by == "first_level_dr"){
		            $where['edrt.parent_emp_id'] = $first_level_emp_id;
		        }else if($view_by == "all"){
		            if(count($all_emp_ids) > 0){
		                $this->db->where_in("employee.emp_id",$all_emp_ids);
		            }else{
		                $where['edrt.parent_emp_id'] = $emp_id;
		            }
		        }
		    }else{
		        $where['employee.company_id'] = $comp_id;
		        $where['employee.status'] = "Active";
		        $where['accounts.user_type_id'] = "5";
		        $where['accounts.deleted'] = "0";
		        $where['employee.deleted'] = "0";
		        $where['employee_payroll_information.employee_status'] = "Active";
		        if($view_by == "" || $view_by == "my_team"){
		            $where['edrt.parent_emp_id'] = $emp_id;
		        }else if($view_by == "first_level_dr"){
		            $where['edrt.parent_emp_id'] = $first_level_emp_id;
		        }else if($view_by == "all"){
		            if(count($all_emp_ids) > 0){
		                $this->db->where_in("employee.emp_id",$all_emp_ids);
		            }else{
		                $where['edrt.parent_emp_id'] = $emp_id;
		            }
		        }
		    }
		    $konsum_key = konsum_key();
		    $this->edb->select($select);
		    $this->db->where($where);
		    if($employee_id_name !==""){
		        $employee_id_name = $this->db->escape_like_str(stripslashes(clean_input($employee_id_name)));
		        $where2  = array(
		            'employee.company_id' => $comp_id,
		            'employee.status'    => 'Active',
		            'accounts.user_type_id' => '5',
		            'accounts.deleted'=>'0',
		            'employee.deleted'=>'0',
		            #'edrt.parent_emp_id' => $emp_id
		        );
		        if($view_by == "" || $view_by == "my_team"){
		            $where2['edrt.parent_emp_id'] = $emp_id;
		        }else if($view_by == "first_level_dr"){
		            $where2['edrt.parent_emp_id'] = $first_level_emp_id;
		        }else if($view_by == "all"){
		            if(count($all_emp_ids) > 0){
		                $this->db->where_in("employee.emp_id",$all_emp_ids);
		            }else{
		                $where2['edrt.parent_emp_id'] = $emp_id;
		            }
		        }
		        $where_str = "(convert(CONCAT(AES_DECRYPT(employee.first_name,'{$konsum_key}'),' ',AES_DECRYPT(employee.last_name,'{$konsum_key}')) using latin1) LIKE '%".$employee_id_name."%'
             OR (convert(AES_DECRYPT(accounts.payroll_cloud_id,'{$konsum_key}') using latin1)='{$employee_id_name}')
             OR (convert(AES_DECRYPT(accounts.email,'{$konsum_key}') using latin1) LIKE '%".$employee_id_name."%')
             )";
		        $this->db->where($where2);
		        $this->db->where($where_str, NULL, FALSE);
		        #$this->db->where("convert(CONCAT(AES_DECRYPT(employee.first_name,'{$konsum_key}'),' ',AES_DECRYPT(employee.last_name,'{$konsum_key}')) using latin1) LIKE '%".$employee_id_name."%'", NULL, FALSE); // encrypt
		        #$this->db->or_where("convert(AES_DECRYPT(accounts.payroll_cloud_id,'{$konsum_key}') using latin1)=",$employee_id_name);
		        #$this->db->or_where("convert(AES_DECRYPT(accounts.email,'{$konsum_key}') using latin1) LIKE '%".$employee_id_name."%'"); // for email search
		        #$this->db->where($where2);
		    }
		    $this->edb->join('accounts','accounts.account_id = employee.account_id','INNER');
		    $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = employee.emp_id","LEFT");
		    $this->edb->join('employee_payroll_information','employee_payroll_information.emp_id = employee.emp_id','left');
		    $this->edb->join('department','department.dept_id = employee_payroll_information.department_id','left');
		    $this->edb->join('position', 'position.position_id = employee_payroll_information.position', 'left');
		    $this->db->order_by('CONVERT(UPPER(AES_DECRYPT(employee.last_name, "'.$konsum_key.'")) using latin1)', 'ASC', false);
		    if($all == false){
		        $q = $this->edb->get('employee',$limit,$start);
		    }else{
		        $q = $this->edb->get('employee');
		    }
		    $result = $q->result();
		    return $result;
		}
		/** Added - Fritz - End  **/

		public function emp_work_schedule_v2($emp_id,$check_company_id,$currentdate)
		{
			$s = array(
				"ess.work_schedule_id",
				"ess.valid_from"
			);
			$w_date = array(
				"ess.valid_from <="		=>	$currentdate,
				"ess.valid_from >="		=>	$currentdate
			);
			$this->db->where($w_date);
			
			$w_emp = array(
				"e.emp_id"=>$emp_id,
				"ess.company_id"=>$check_company_id,
				"e.status"=>"Active",
				"ess.status"=>"Active",
				"ess.payroll_group_id" => 0
			);
			$this->edb->select($s);
			$this->edb->where($w_emp);
			$this->edb->join("employee AS e","e.emp_id = ess.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$q_emp = $this->edb->get("employee_shifts_schedule AS ess");
			$r_emp = $q_emp->result();

			if ($r_emp) {
				$ws_ids = array();
				foreach ($r_emp as $row) {
					$temp = array(
						'work_schedule_id' => $row->work_schedule_id,
						'date' => $row->valid_from
					);
					array_push($ws_ids, $temp);
				}

				return $ws_ids;
			}

			$w = array(
				'epi.emp_id'=> $emp_id
			);
			$this->db->where($w);
			$this->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','LEFT');
			$q_pg = $this->edb->get('employee_payroll_information AS epi');
			$r_pg = $q_pg->row();

			$ws = array(
				'work_schedule_id' => $r_pg->work_schedule_id,
				'date' => $currentdate
			);
				
			return ($r_pg) ? $ws : FALSE;
		}

		public function leave_credits_for_doughnut($comp_id,$emp_id, $leave_type){
			$date = date("Y-m-d");
			$w = array(
					"el.company_id"=>$comp_id,
					"el.emp_id"=>$emp_id,
					"el.leave_type_id" => $leave_type,
					"el.as_of <= "=>$date,
					"el.status"=>"Active"
			);
				
			$select = array(
					"*",
					"el.leave_credits AS a_leave_credits",
					"lt.leave_units AS a_leave_units"
			);
			$this->db->select($select);
			$this->db->where($w);
			$this->db->join("leave_type lt","lt.leave_type_id = el.leave_type_id","LEFT");
			$q = $this->db->get("employee_leaves el");
			$row = $q->row();
			
			return ($row) ? $row : FALSE ;
		}

		public function get_current_shift_v2($work_schedule_id, $comp_id, $currentdate)
		{
			$w = array(
				"work_schedule_id" => $work_schedule_id,
				"comp_id"=> $comp_id
			);
			$this->db->where($w);
			$q = $this->db->get('work_schedule');
			$r = $q->row();

			$shift_arr = array();
			if ($r) {
				// Regular
				if($r->work_type_name == 'Uniform Working Days') {
					$w1 = array(
						"work_schedule_id" => $work_schedule_id,
						"company_id"=> $comp_id,
						"days_of_work" => date('l', strtotime($currentdate))
					);
					
					$this->db->where($w1);
					$q1 = $this->db->get('regular_schedule');
					$r1 = $q1->row();

					if($r1) {
						$arr = array(
							"work_type_name" => 'Uniform Working Days',
							"work_schedule_id" => $r1->work_schedule_id,
							"start_thres" => date("H:i:s", strtotime($r1->work_start_time." +".$r1->latest_time_in_allowed." minutes")),
							"start" => $r1->work_start_time,
							"end" => $r1->work_end_time,
							"total_hours_for_the_day" => 0,
							"latest_time_in_allowed" => $r1->latest_time_in_allowed
						);
						array_push($shift_arr, (object) $arr);						
					}

					return $shift_arr;
				}

				// Split
				if ($r->work_type_name == 'Workshift') {

					$w3 = array(
							"work_schedule_id" => $work_schedule_id,
							"company_id"=> $comp_id,
							"start_time >" => date('H:i:s')
					);
					
					$this->db->where($w3);
					$q3 = $this->db->get('schedule_blocks');
					$r3 = $q3->row();
					
					if($r3) {
						$arr = array(
								"work_type_name" => 'Workshift',
								"work_schedule_id" => $r3->work_schedule_id,
								"start" => $r3->start_time,
								"end" => $r3->end_time,
								"total_hours_for_the_day" => 0,
								"latest_time_in_allowed" => ""
						);
						array_push($shift_arr, (object) $arr);
					}

					return $shift_arr;
				}

				// Flexi
				if ($r->work_type_name == 'Flexible Hours') {
					$w2 = array(
							"work_schedule_id" => $work_schedule_id,
							"company_id"=> $comp_id
					);
						
					$this->db->where($w2);
					$q2 = $this->db->get('flexible_hours');
					$r2 = $q2->row();
					
					if($r2) {
						if($r2->latest_time_in_allowed == null) {
							$latest_time_in_allowed = "FLEXI";
						} else {
							$latest_time_in_allowed = $r2->latest_time_in_allowed;
						}
						
						$arr = array(
								"work_type_name" => 'Flexible Hours',
								"work_schedule_id" => $r2->work_schedule_id,
								"start_thres" => $latest_time_in_allowed,
								"start" => $latest_time_in_allowed,
								"end" => "",
								"total_hours_for_the_day" => $r2->total_hours_for_the_day,
								"latest_time_in_allowed" => $r2->latest_time_in_allowed
						);
						array_push($shift_arr, (object) $arr);
					}

					return $shift_arr;
				}
			}

			return false;
		}
}
	
	
/* End of file employee_model.php */
/* Location: ./application/models/employee_model.php */
