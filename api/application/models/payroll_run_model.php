<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Payroll Run Model
 *
 * @category Model
 * @version 1.1
 * @author Jonathan Bangga <jonathanbangga@gmail.com>
 */

class Payroll_run_model extends CI_Model {
   
	/**
	 * Payroll Group
	 * @param unknown_type $company_id
	 */
	public function payroll_group($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_group");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Payroll Group ID
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 */
	public function check_payroll_group_id($payroll_group_id,$company_id){
		$w = array(
			"payroll_group_id"=>$payroll_group_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_group");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Payroll Calendar
	 * @param unknown_type $period_type
	 * @param unknown_type $company_id
	 */
	public function payroll_calendar($period_type,$company_id){
		$w = array(
			"pay_schedule"=>$period_type,
			"company_id"=>$company_id
		);
		$this->db->where($w);
		$this->db->order_by("first_payroll_date","ASC");
		$q = $this->db->get("payroll_calendar");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Payroll Period
	 * @param unknown_type $company_id
	 */
	public function payroll_period($company_id){
		$w = array(
			"pp.company_id"=>$company_id,
			"pp.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("payroll_group AS pg","pg.payroll_group_id = pp.payroll_group_id","LEFT");
		$q = $this->db->get("payroll_period AS pp");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Exclude
	 * @param unknown_type $company_id
	 */
	public function check_exclude($emp_id,$company_id){
		$w = array(
			"el.emp_id"=>$emp_id,
			"el.company_id"=>$company_id
		);
		$this->db->where($w);
		$this->edb->join("employee AS e","e.emp_id = el.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$q = $this->edb->get("exclude_list AS el");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee List
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $per_page
	 * @param unknown_type $page
	 * @param unknown_type $sort_by
	 * @param unknown_type $q = $search
	 */
	public function employee($payroll_group_id,$company_id,$per_page, $page, $sort_by, $q){
		$konsum_key = konsum_key();
		
		$w = array(
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		
		if($q !==""){
			$this->db->where("
				(convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%') 
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
		}
		
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		
		if($sort_by != ""){
			if($sort_by == "e.first_name"){
				$this->edb->order_by($sort_by,"ASC");
			}else{
				$this->db->order_by($sort_by,"ASC");
			}
		}else{
			$this->edb->order_by("e.first_name","ASC");
		}
		
		$q = $this->edb->get("employee AS e",$per_page, $page);
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Counter
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 */
	public function employee_counter($payroll_group_id,$company_id){
		$s = array(
			"COUNT(e.emp_id) AS total" 
		);
		$this->db->select($s);
		$w = array(
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->order_by("e.first_name","ASC");
		$q = $this->edb->get("employee AS e");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Get Count Leave Application
	 * @param unknown_type $company_id
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $date_start
	 * @param unknown_type $date_end
	 */
	public function get_count_leave_application($company_id,$payroll_group_id,$date_start,$date_end) {
		if(is_numeric($company_id)) { 
			$s = array(
				"COUNT(*) AS total"
			);
			$this->db->select($s);
			$where = array(
				'employee_leaves_application.status' 			=> 'Active', 
				'employee_payroll_information.payroll_group_id' => $payroll_group_id,
				'employee.status' 								=> 'Active',
				'employee_leaves_application.leave_application_status' => 'approve',
				'employee_leaves_application.company_id' 		=> $company_id 
			);
			$this->edb->where($where);
			$this->db->where('employee_leaves_application.date_start BETWEEN CAST("'.$date_start.'" as date) AND CAST("'.$date_end.'" as date)');
			$this->edb->join('employee','employee.emp_id = employee_leaves_application.emp_id','left');
			$this->edb->join('employee_payroll_information','employee.emp_id = employee_payroll_information.emp_id','left');
			$this->edb->join('department AS d','d.dept_id = employee_payroll_information.department_id','left');
			$this->edb->join('accounts','accounts.account_id = employee.account_id','left');
			$this->edb->join('leave_type','leave_type.leave_type_id = employee_leaves_application.leave_type_id','left');
			$this->edb->join('payroll_period','payroll_period.payroll_group_id = employee_payroll_information.payroll_group_id','left');
						
			$query = $this->edb->get('employee_leaves_application');
			$result = $query->row();
			$query->free_result();
			return ($result) ? $result->total : FALSE ;
		} else {
			return false;
		}
	}
	
	/**
	 * Employee Leave Application Information
	 * @param unknown_type $company_id
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $date_start
	 * @param unknown_type $date_end
	 * @param unknown_type $offset
	 * @param unknown_type $limit
	 */
	public function get_leave_application($company_id,$payroll_group_id,$date_start,$date_end,$offset=10,$limit=0,$sort_by,$q) {
		if(is_numeric($company_id)) { 
			
			$konsum_key = konsum_key();
			
			$where = array(
				'employee_leaves_application.status' 			=> 'Active', 
				'employee_payroll_information.payroll_group_id' => $payroll_group_id,
				'employee.status' 								=> 'Active',
				'employee_leaves_application.leave_application_status' => 'approve',
				'employee_leaves_application.company_id' 		=> $company_id 
			);
			$this->edb->where($where);
			$this->db->where('employee_leaves_application.date_start BETWEEN CAST("'.$date_start.'" as date) AND CAST("'.$date_end.'" as date)');
			
			if($q !==""){
				$this->db->where("
					((convert(CONCAT(AES_DECRYPT(employee.first_name,'{$konsum_key}'),' ',AES_DECRYPT(employee.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
					OR AES_DECRYPT(accounts.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%')
					", NULL, FALSE); // encrypt
			}
			
			$this->edb->join('employee','employee.emp_id = employee_leaves_application.emp_id','left');
			$this->edb->join('employee_payroll_information','employee.emp_id = employee_payroll_information.emp_id','left');
			$this->edb->join('department AS d','d.dept_id = employee_payroll_information.department_id','left');
			$this->edb->join('accounts','accounts.account_id = employee.account_id','left');
			$this->edb->join('leave_type','leave_type.leave_type_id = employee_leaves_application.leave_type_id','left');
			$this->edb->join('payroll_period','payroll_period.payroll_group_id = employee_payroll_information.payroll_group_id','left');
			
			if($sort_by != ""){
				if($sort_by == "employee.first_name"){
					$this->edb->order_by($sort_by,"ASC");
				}else{
					$this->db->order_by($sort_by,"ASC");
				}
			}else{
				$this->edb->order_by("employee.first_name","ASC");
			}
			
			$query = $this->edb->get('employee_leaves_application',$limit,$offset);
			$result = $query->result();
			$query->free_result();
			return ($result) ? $result : FALSE ;
		} else {
			return false;
		}
	}
	
	/**
	 * Work Schedule ID
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $date
	 */
	public function work_schedule_id($company_id,$emp_id,$date=NULL){
		
		$s = array(
			"work_schedule_id"
		);
		$this->db->select($s);
		
		$w_date = array(
			"valid_from <="		=>	$date,
			"until >="			=>	$date
		);
		if($date != NULL) $this->db->where($w_date);
		
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"payroll_group_id"=>0, // add
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("employee_shifts_schedule");
		$r = $q->row();
		$q->free_result();
		
		if($r){
			// split scheduling
			return $r;
		}else{
			
			$s = array(
				"work_schedule_id"
			);
			$this->db->select($s);
			
			// default work scheduling
			$w = array(
				"epi.emp_id"=>$emp_id,
				"epi.company_id"=>$company_id,
				"epi.status"=>"Active"
			);
			$this->db->where($w);
			$this->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$q = $this->db->get("employee_payroll_information AS epi");
			$r = $q->row();
			$q->free_result();
			return ($r) ? $r : FALSE ;
		}
	}
	
	/**
	 * Get rest day
	 * @param int $company_id
	 * @param int $work_schedule_id
	 * @param string $weekday
	 */
	public function get_rest_day($company_id,$work_schedule_id,$weekday)
	{
		$where = array(
			'company_id' 	   => $company_id,
			"work_schedule_id"=>$work_schedule_id,
			'rest_day' 		   => $weekday,
			'status'		   => 'Active'
		);
		$this->db->where($where);
		$q = $this->db->get('rest_day');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get rest day Default
	 * @param int $company_id
	 * @param int $payroll_group_id
	 * @param string $weekday
	 */
	public function get_rest_day_default($company_id,$payroll_group_id,$weekday)
	{
		$where = array(
			'pg.company_id' 	   => $company_id,
			'pg.payroll_group_id' => $payroll_group_id,
			'rd.rest_day' 		   => $weekday,
			'rd.status'		   => 'Active'
		);
		$this->db->where($where);
		$this->db->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
		$this->db->join("rest_day AS rd","rd.work_schedule_id = ws.work_schedule_id","LEFT");
		$q = $this->db->get('payroll_group AS pg');
		
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get workday
	 * @param int $company_id
	 * @param int $work_schedule_id
	 */
	public function get_workday($company_id,$work_schedule_id)
	{
		$s = array("*","work_type_name AS workday_type");
		$this->db->select($s);
		$where = array(
			'comp_id' 	   => $company_id,
			"work_schedule_id"=>$work_schedule_id
		);
		$this->db->where($where);
		$q = $this->db->get('work_schedule');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get workday Default
	 * @param int $company_id
	 * @param int $payroll_group_id
	 */
	public function get_workday_default($company_id,$payroll_group_id)
	{
		$s = array("*","ws.work_type_name AS workday_type");
		$this->db->select($s);
		$where = array(
			'ws.comp_id' 	   => $company_id,
			'pg.payroll_group_id' => $payroll_group_id
		);
		$this->db->where($where);
		$this->db->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
		$q = $this->db->get('payroll_group AS pg');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Check Working Schedule For Flexible
	 * @param unknown_type $id
	 * @param unknown_type $company_id
	 */
	public function check_working_schedule_flex($id,$company_id,$str){
		if($str == "work_schedule_id"){
			$w = array(
				"f.work_schedule_id"=>$id,
				"f.company_id"=>$company_id
			);
			$this->db->where($w);
			$q = $this->db->get("flexible_hours AS f");
			$r = $q->row();
			if($r){
				return ($r->not_required_login == 1) ? $r : FALSE ;
			}else{
				return FALSE;
			}
		}else{
			$w = array(
				"pg.payroll_group_id"=>$id,
				"f.company_id"=>$company_id
			);
			$this->db->where($w);
			$this->db->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
			$this->db->join("flexible_hours AS f","f.work_schedule_id = ws.work_schedule_id","LEFT");
			$q = $this->db->get('payroll_group AS pg');
			
			$r = $q->row();
			$q->free_result();
			if($r){
				return ($r->not_required_login == 1) ? $r : FALSE ;
			}else{
				return FALSE;
			}
		}
	}
	
	/**
	 * Get timein of each employee
	 * @param int $company_id
	 * @param int $employee_id
	 * @param date $date
	 */
	public function get_timein($company_id,$employee_id,$date)
	{
		$where = array(
			'emp_id'  => $employee_id,
			'comp_id' => $company_id,
			'date'	  => $date,
			'flag_halfday' => 0
		);
		$this->db->where($where);
		$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
		$this->db->order_by("time_in","DESC");
		$q = $this->db->get('employee_time_in');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get timein of each employee
	 * @param unknown_type $company_id
	 * @param unknown_type $employee_id
	 * @param unknown_type $start
	 * @param unknown_type $end
	 */
	public function new_get_timein($company_id,$employee_id,$start,$end)
	{
		$where = array(
			'emp_id'  => $employee_id,
			'comp_id' => $company_id
			// 'flag_halfday' => 0
		);
		$this->db->where($where);
		
		$this->db->where('date >=',$start);
		$this->db->where('date <=',$end);
		
		$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
		// $this->db->order_by("time_in","DESC");
		$this->db->order_by("time_in","ASC");
		$q = $this->db->get('employee_time_in');
		$result = $q->result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get flexible hour
	 * @param int $company_id
	 * @param int $work_schedule_id
	 */
	public function get_flexible_hour($company_id,$work_schedule_id)
	{
		/* $where = array(
			'company_id' 	   => $company_id,
			"work_schedule_id"=>$work_schedule_id
		);
		$this->db->where($where);
		$q = $this->db->get('flexible_hours');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false; */
		
		// PARA CONNECT WORK SCHEDULE TABLE
		$where = array(
			'fh.company_id' 	   => $company_id,
			"fh.work_schedule_id"=>$work_schedule_id
		);
		$this->db->where($where);
		$this->db->join("work_schedule AS ws","ws.work_schedule_id = fh.work_schedule_id","LEFT");
		$q = $this->db->get('flexible_hours AS fh');
		$result = $q->row();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get flexible hour Default
	 * @param int $company_id
	 * @param int $payroll_group_id
	 */
	public function get_flexible_hour_default($company_id,$payroll_group_id)
	{
		$where = array(
			'f.company_id' 	   => $company_id,
			'pg.payroll_group_id' => $payroll_group_id
		);
		$this->db->where($where);
		$this->db->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
		$this->db->join("flexible_hours AS f","f.work_schedule_id = ws.work_schedule_id","LEFT");
		$q = $this->db->get('payroll_group AS pg');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get uniform working day
	 * @param int $company_id
	 * @param int $work_schedule_id
	 * @param string $weekday
	 */
	public function get_uniform_working_day($company_id,$work_schedule_id,$weekday)
	{
		/* $where = array(
			'company_id' 	   => $company_id,
			"work_schedule_id"=>$work_schedule_id,
			#'working_day'	   => $weekday
			'days_of_work'	   => $weekday
		);
		$this->db->where($where);
		#$q = $this->db->get('uniform_working_day');
		$q = $this->db->get('regular_schedule');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false; */
		
		// PARA MA CONNECT S WORK SCHEDULE TABLE
		$where = array(
			'rs.company_id' 	   => $company_id,
			"rs.work_schedule_id"=>$work_schedule_id,
			'rs.days_of_work'	   => $weekday
		);
		$this->db->where($where);
		$this->db->join("work_schedule AS ws","ws.work_schedule_id = rs.work_schedule_id","LEFT");
		$q = $this->db->get('regular_schedule AS rs');
		$result = $q->row();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get uniform working day Default
	 * @param int $company_id
	 * @param int $payroll_group_id
	 * @param string $weekday
	 */
	public function get_uniform_working_day_default($company_id,$payroll_group_id,$weekday)
	{
		$where = array(
			'rs.company_id' 	   => $company_id,
			'rs.payroll_group_id' => $payroll_group_id,
			#'working_day'	   => $weekday
			'rs.days_of_work'	   => $weekday
		);
		$this->db->where($where);
		$this->db->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
		$this->db->join("regular_schedule AS rs","rs.work_schedule_id = ws.work_schedule_id","LEFT");
		$q = $this->db->get('payroll_group AS pg');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get workshift
	 * @param int $company_id
	 * @param int $work_schedule_id
	 */
	public function get_workshift($company_id,$work_schedule_id)
	{
		/* // $s = array("*","sb.total_hours_work_per_block AS total_work_hours");
		$s = array("*","total_hours_work_per_block AS total_work_hours");
		$this->db->select($s);
		$where = array(
			'ss.company_id' 	   => $company_id,
			"ss.work_schedule_id"=>$work_schedule_id
		);
		$this->db->where($where);
		#$this->db->join("schedule_blocks AS sb","sb.work_schedule_id = ss.work_schedule_id","LEFT");
		#$q = $this->db->get('split_schedule AS ss');
		$q = $this->db->get('schedule_blocks AS ss');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false; */
		
		// PARA CONNECT WORK SCHEDULE TABLE
		$s = array("*","total_hours_work_per_block AS total_work_hours");
		$this->db->select($s);
		$where = array(
			'ss.company_id' 	   => $company_id,
			"ss.work_schedule_id"=>$work_schedule_id
		);
		$this->db->where($where);
		$this->db->join("work_schedule AS ws","ws.work_schedule_id = ss.work_schedule_id","LEFT");
		$q = $this->db->get('schedule_blocks AS ss');
		$result = $q->row();
		return ($result) ? $result : false;
	}
	
	/**
	 * New Workshift (Schedule Blocks)
	 * @param int $company_id
	 * @param int $work_schedule_id
	 * @param int $location_shift_schedule_id
	 */
	#public function new_get_workshift($company_id,$work_schedule_id)
	public function new_get_workshift($company_id,$location_shift_schedule_id)
	{
		$s = array("*","total_hours_work_per_block AS total_work_hours");
		$this->db->select($s);
		$where = array(
			'sb.company_id' 	   => $company_id,
			"esb.shifts_schedule_id"=>$location_shift_schedule_id
		);
		$this->db->where($where);
		$this->db->join("schedule_blocks AS sb","sb.schedule_blocks_id = esb.schedule_blocks_id","LEFT");
		$q = $this->db->get('employee_sched_block AS esb');
		$result = $q->result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get workshift Default
	 * @param int $company_id
	 * @param int $payroll_group_id
	 */
	public function get_workshift_default($company_id,$payroll_group_id)
	{
		$where = array(
			'company_id' 	   => $company_id,
			'payroll_group_id' => $payroll_group_id
		);
		$this->db->where($where);
		$q = $this->db->get('split_schedule');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Check Hours Type Flag
	 * @param unknown_type $company_id
	 */
	public function check_hours_type_flag($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("nightshift_differential_for_premium");
		$r = $q->result();
		$q->free_result();
		if($r){
			$flag = 0;
			foreach($r as $row){
				if($flag == 0){
					if($row->flag_add_prem == 1 || $row->flag_add_prem != "0") $flag = 1;
				}
			}
			return ($flag) ? $r : FALSE ;
		}else{
			return FALSE;
		}
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get holiday
	 * @param int $company_id
	 * @param int $hour_type_id
	 * @param date $date
	 */
	public function get_holiday_for_night_diff_settings($company_id,$hour_type_id=NULL,$date)
	{
		$s = array(
			"*",
			"nightshift_differential_for_premium.pay_rate AS pay_rate", // night diff pay rate
			"hours_type.working AS holiday_working_pay_rate"
		);
		$this->db->select($s);
		if ($hour_type_id) {
			$this->db->where('holiday.hour_type_id',$hour_type_id);
		}
		
		$where = array(
			'holiday.company_id' => $company_id,
			'holiday.date'		 => $date,
			'holiday.status'	 => 'Active'
		);
		$this->db->where($where);
		$this->db->join('nightshift_differential_for_premium','nightshift_differential_for_premium.hours_type_id = holiday.hour_type_id','LEFT');
		$this->db->join('hours_type','hours_type.hour_type_id = nightshift_differential_for_premium.hours_type_id','LEFT'); // bag.o ni na join
		$this->db->join('overtime_type','overtime_type.hour_type_id = hours_type.hour_type_id','LEFT'); // bag.o ni na join
		$q = $this->db->get('holiday');
		$result = $q->row();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get Employee Overtime
	 * @param unknown_type $company_id
	 * @param unknown_type $employee_id
	 * @param unknown_type $current
	 */
	public function get_employee_overtime($company_id,$employee_id,$current)
	{
		// payroll period
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$pp_q = $this->db->get("payroll_period");
		$pp_r = $pp_q->row();
		$pp_q->free_result();
		if($pp_r){
			$period_from = $pp_r->period_from;
			$period_to = $pp_r->period_to;
		}else{
			return FALSE;
		}
		
		// date where
		$date_where = array(
			"overtime_from >= " => $period_from,
			"overtime_from <= " => $period_to,
		
			"overtime_to >= " => $period_from,
			"overtime_to <= " => $period_to
		);
		$this->db->where($date_where);
		
		$where = array(
			'company_id' 	  => $company_id,
			'emp_id'	 	  => $employee_id,
			'overtime_status' => 'approved',
			'status'		  => 'Active'
		);
		$this->edb->where($where);
		$this->db->where('CAST(approval_date AS DATE) = "'.$current.'"');
		$q = $this->edb->get('employee_overtime_application');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get Employee Overtime
	 * @param unknown_type $company_id
	 * @param unknown_type $employee_id
	 * @param unknown_type $start
	 * @param unknown_type $end
	 */
	public function new_get_employee_overtime($company_id,$employee_id,$start,$end)
	{
		// date where
		$date_where = array(
			#"overtime_from >= " => $start,
			#"overtime_from <= " => $end,
			"overtime_to >= " => $start,
			"overtime_to <= " => $end
		);
		$this->db->where($date_where);
		
		$where = array(
			'company_id' 	  => $company_id,
			'emp_id'	 	  => $employee_id,
			'overtime_status' => 'approved',
			'status'		  => 'Active'
		);
		$this->db->where($where);
		
		#$this->db->where('approval_date >=',$start);
		#$this->db->where('approval_date <=',$end);
		
		$q = $this->db->get('employee_overtime_application');
		$result = $q->result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get Holiday
	 * @param unknown_type $company_id
	 * @param unknown_type $hour_type_id
	 * @param unknown_type $date
	 */
	public function get_holiday($company_id,$hour_type_id=NULL,$date)
	{
		/* if ($hour_type_id) {
			$this->db->where('holiday.hour_type_id',$hour_type_id);
		}
		
		$where = array(
			'holiday.company_id' => $company_id,
			'holiday.date'		 => $date,
			'holiday.status'	 => 'Active'
		);
		$this->db->where($where);
		$this->db->join('hours_type','hours_type.hour_type_id = holiday.hour_type_id','left');
		$this->db->join('overtime_type','overtime_type.hour_type_id = holiday.hour_type_id','left');
		$q = $this->db->get('holiday');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false; */
		
		// VERSION 1.4.2 FIXES
		if ($hour_type_id) {
			$this->db->where('holiday.hour_type_id',$hour_type_id);
		}
		
		$where = array(
			'holiday.company_id' => $company_id,
			'MONTH(holiday.date)'	=> date("m",strtotime($date)),
			'DAY(holiday.date)'		=> date("d",strtotime($date)),
			'holiday.status'	 => 'Active'
		);
		$this->db->where($where);
		$this->db->join('hours_type','hours_type.hour_type_id = holiday.hour_type_id','left');
		$this->db->join('overtime_type','overtime_type.hour_type_id = holiday.hour_type_id','left');
		$q = $this->db->get('holiday');
		$r = $q->row();
		
		if($r){
			
			return ($r->date_type == "fixed" || ($r->date_type == "movable" && $r->date == $date)) ? $r : FALSE ;
			
		}else{
			return FALSE;
		}
		
		// =========
		
		/* if ($hour_type_id) {
			$this->db->where('holiday.hour_type_id',$hour_type_id);
		}
		
		$where = array(
			'holiday.company_id' => $company_id,
			'MONTH(holiday.date)'	=> date("m",strtotime($date)),
			'DAY(holiday.date)'		=> date("d",strtotime($date)),
			'holiday.status'	 => 'Active'
		);
		$this->db->where($where);
		$this->db->join('hours_type','hours_type.hour_type_id = holiday.hour_type_id','left');
		$this->db->join('overtime_type','overtime_type.hour_type_id = holiday.hour_type_id','left');
		$q = $this->db->get('holiday');
		$r = $q->result();
		
		#last_query();
		#print "<br><br>";
		
		if($r){
				
			$cnt = 0;
			foreach($r as $row){ $cnt++; }
			
			if($cnt >= 2){
				
				#print $cnt." {$date} tessssss <br>";
				
				// DOUBLE HOLIDAY
				$w = array(
					"company_id"=>$company_id,
					"hour_type_name"=>"Double Holiday",
					"status"=>"Active"
				);
				$this->db->where($w);
				$q = $this->db->get("hours_type");
				$r = $q->row();
				if($r){
					return $r;
				}else{
					return FALSE;	
				}
				
				#return ($r) ? $r : FALSE ;
				
			}else{
				
				// REGULAR OR SPECIAL HOLIDAY
				if ($hour_type_id) {
					$this->db->where('holiday.hour_type_id',$hour_type_id);
				}
				
				$where = array(
					'holiday.company_id' => $company_id,
					'MONTH(holiday.date)'	=> date("m",strtotime($date)),
					'DAY(holiday.date)'		=> date("d",strtotime($date)),
					'holiday.status'	 => 'Active'
				);
				$this->db->where($where);
				$this->db->join('hours_type','hours_type.hour_type_id = holiday.hour_type_id','left');
				$this->db->join('overtime_type','overtime_type.hour_type_id = holiday.hour_type_id','left');
				$q = $this->db->get('holiday');
				$r = $q->row();
				
				if($r){
						
					return ($r->date_type == "fixed" || ($r->date_type == "movable" && $r->date == $date)) ? $r : FALSE ;
						
				}else{
					return FALSE;
				}
				
			}
			
			// return ($r->date_type == "fixed" || ($r->date_type == "movable" && $r->date == $date)) ? $r : FALSE ;
				
		}else{
			return FALSE;
		} */
		
	}
	
	/**
	 * Get overtime rate
	 * @param int $company_id
	 * @param string $hour_type
	 */
	public function get_overtime_rate($company_id,$hour_type)
	{
		$where = array(
			'hours_type.company_id' => $company_id,
			'hours_type.hour_type_name' => $hour_type,
			'hours_type.status'	 => 'Active'
		);
		$this->db->where($where);
		$this->db->join('overtime_type','overtime_type.hour_type_id = hours_type.hour_type_id','left');
		$q = $this->db->get('hours_type');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get timein of each employee
	 * @param int $company_id
	 * @param int $employee_id
	 * @param date $date
	 */
	public function emp_absences_get_timein($company_id,$employee_id,$date)
	{
		$where = array(
			'emp_id'  => $employee_id,
			'comp_id' => $company_id,
			'date'	  => $date
		);
		$this->db->where($where);
		$q = $this->db->get('employee_time_in');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * No need to encrypt
	 * @param int $company_id
	 * @param int $employee_id
	 * @param date $date
	 */
	public function emp_absences_get_leave_application($company_id,$employee_id,$date)
	{
		$where = array(
			'company_id' 			     => $company_id,	
			'emp_id'	 			     => $employee_id,
			'CAST(approval_date AS DATE) =' => $date,
			'leave_application_status'	 => 'approve' 
		);
		$this->db->where($where);
		$q = $this->db->get('employee_leaves_application');
		$result = $q->row();
		$q->free_result();
		return ($result) ?  $result:  false;
	}
	
	/**
	 * Get absences
	 * @param int $company_id
	 * @param int $employee_id
	 * @param date $date
	 */
	public function emp_absences_get_absences($company_id,$employee_id,$date)
	{
		$where = array(
			'company_id' => $company_id,
			'emp_id' 	 => $employee_id,	
			'date'		 => $date
		);
		$this->db->where($where);
		$q = $this->db->get('absences');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	*	get all hours type for the company 
	*	@param int $company_id
	*/
	public function get_hourstypes($company_id) 
	{
		$where = array(
			'company_id' => $company_id,
			'status'	 => 'Active'
		);
		$this->db->where($where);
		$q = $this->db->get('hours_type');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get exclude employees
	 * @param int $company_id
	 * @param int $employee_id
	 * @param int $sort_param
	 */
	// public function get_exclude_list($company_id,$employee_id)
	public function get_exclude_list($company_id,$employee_id,$sort_param)
	{
		$where = array(
			'company_id' => $company_id,
			'emp_id'	 => $employee_id,
			// BAG.O NPUD NI ZZZZZZZ para dili na global ang pag exclude sa employee, by payroll period na ang pag exclude ani tungod sa kadaghan versions zzzzzzzzzzz
			"status"=> "Active",
			"payroll_period"=>$sort_param["payroll_period"],
			"period_from"=>$sort_param["period_from"],
			"period_to"=>$sort_param["period_to"]
		);
		$this->db->where($where);
		$q = $this->db->get('exclude_list');
		$result = $q->row();
		return ($result) ? $result->exclude : false;
	}
	
	/**
	 * Night Differential Settings
	 */
	public function get_night_shift_differential_settings($company_id){
		$where = array(
			'company_id' => $company_id
		);
		$this->db->where($where);
		$q = $this->db->get('nightshift_differential_settings');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Check Employee Work Schedule
	 * @param unknown_type $emp_id
	 * @param unknown_type $current
	 * @param unknown_type $work_schedule_id
	 */
	public function check_employee_work_schedule($emp_id,$current,$work_schedule_id){
		$w = array(
			"emp_id"=>$emp_id,
			"work_schedule_id"=>$work_schedule_id,
			"valid_from <= "=>$current,
			"until >= "=>$current
		);
		$this->db->where($w);
		$q = $this->db->get("employee_shifts_schedule");
		$r = $q->row();
		$q->free_result();
		return ($r) ? TRUE : FALSE ;
	}
	
	/**
	 * Get timein of each employee for Regular Days Only
	 * @param int $company_id
	 * @param int $employee_id
	 * @param date $date
	 */
	public function time_in_regular_days($company_id,$employee_id,$date)
	{
		$where = array(
			'emp_id'  => $employee_id,
			'comp_id' => $company_id,
			'date'	  => $date,
			'status'	  => "Active"				
		);
		$this->db->where($where);
		// $this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
		
		// $this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL OR time_in_status = 'pending')");
		// $this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL OR (time_in_status = 'pending' AND source != 'add log'))");
		$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL OR (time_in_status = 'pending' AND source != 'EP'))");
		$this->db->where("time_out IS NOT NULL"); // bag.o ni sya nga condition
		
		$this->db->where("(flag_on_leave IS NULL OR flag_on_leave = 'no')"); // bag.o ni sya nga condition 05-nov-2016
		
		$this->db->order_by("time_in","DESC");
		$q = $this->db->get('employee_time_in');
		$r = $q->row();
		if($r){
			return ($r->time_in != NULL && $r->time_out != NULL) ? $r : false;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Get timein of each employee for Regular Days Only
	 * @param unknown_type $company_id
	 * @param unknown_type $employee_id
	 * @param unknown_type $current
	 * @param unknown_type $end
	 */
	public function new_time_in_regular_days($company_id,$employee_id,$current,$end)
	{
		$where = array(
			'emp_id'  => $employee_id,
			'comp_id' => $company_id,
			'status'	  => "Active"
		);
		$this->db->where($where);
		
		$this->db->where('date >=',$current);
		$this->db->where('date <=',$end); 
		
		$this->db->where("time_in IS NOT NULL");
		$this->db->where("time_out IS NOT NULL");
		
		$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
		$this->db->order_by("time_in","ASC");
		$q = $this->db->get('employee_time_in');
		$r = $q->result();
		if($r){
			return ($r) ? $r : false;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check Break Time
	 * @param unknown_type $company_id
	 * @param unknown_type $schedule_id
	 * @param unknown_type $weekday
	 * @param unknown_type $str_flag
	 */
	public function check_break_time($company_id,$schedule_id,$weekday,$str_flag){
		if($str_flag == "work_schedule_id"){
			$w = array(
				"company_id"=>$company_id,
				"work_schedule_id"=>$schedule_id,
				"workday"=>$weekday
			);	
		}else{
			$w = array(
				"company_id"=>$company_id,
				"payroll_group_id"=>$schedule_id,
				"workday"=>$weekday
			);
		}
		$this->db->where($w);
		$q = $this->db->get("break_time");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get regular day
	 * @param int $company_id
	 * @param int $hour_type_id
	 * @param date $date
	 */
	public function get_regular_day($company_id)
	{
		$where = array(
			'company_id' 	 => $company_id,
			'hour_type_name' => 'Regular Day',
			'status'	 	 => 'Active'
		);
		$this->db->where($where);
		$q = $this->db->get('hours_type');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * MAO NI MOKUHA SA FLEXI DAY KATONG MGA AMPAW NGA WALAY LOGS
	 * Enter description here ...
	 * @param int $emp_id
	 * @param int $company_id
	 * @param date $current
	 * @param int $holiday_id
	 * @param int $hour_type_id
	 * @return object
	 */
	public function flexi_holiday($emp_id,$company_id,$current,$holiday_id,$hour_type_id){
		if($emp_id && is_numeric($company_id)){
			$where_fhp = array(
				'fhp.emp_id'=>$emp_id,
				'fhp.company_id' => $company_id,
				'fhp.holiday_id'=>$holiday_id,
				'fhp.holiday_date'=> date("Y-m-d",strtotime($current)),
				//'h.hour_type_id'=>$hour_type_id,
				'h.status'=>'Active',
				'h.deleted'=>'0',
				'fhp.status'=>'Active'
			);
			$this->db->where($where_fhp);
			$this->db->join('holiday AS h','h.holiday_id=fhp.holiday_id','INNER');
			$q_flex_holidaypremium = $this->db->get('flexible_holiday_premium AS fhp');
			$row_fhp = $q_flex_holidaypremium->row();
			$q_flex_holidaypremium->free_result();
			return $row_fhp;
		}else{
			return false;
		}
	}
	
	/**
	 * Get Hour Type For Rest Day
	 * @param int $company_id
	 * @param int $hour_type_id
	 * @param date $date
	 */
	public function hour_type_for_rest_day($company_id)
	{
		$where = array(
			'company_id' 	 => $company_id,
			'hour_type_name' => 'Rest Day',
			'status'	 	 => 'Active'
		);
		$this->db->where($where);
		$q = $this->db->get('hours_type');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get timein of each employee
	 * @param int $company_id
	 * @param int $employee_id
	 * @param date $date
	 */
	public function get_timein_halfday($company_id,$employee_id,$date)
	{
		$where = array(
			'emp_id'  => $employee_id,
			'comp_id' => $company_id,
			'date'	  => $date,
			'flag_halfday' => 1,
			'time_in_status' => 'Approved',
			'date_halfday'=>NULL
		);
		$this->db->where($where);
		$this->db->order_by("time_in","DESC");
		$q = $this->db->get('employee_time_in');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get timein of each employee
	 * @param int $company_id
	 * @param int $employee_id
	 * @param date $date
	 */
	public function new_get_timein_halfday($company_id,$employee_id,$start,$end)
	{
		$where = array(
			'emp_id'  => $employee_id,
			'comp_id' => $company_id,
			'flag_halfday' => 1,
			'time_in_status' => 'Approved',
			'date_halfday'=>NULL
		);
		$this->db->where($where);
		
		$this->db->where('date >=',$start);
		$this->db->where('date <=',$end);
		
		$this->db->order_by("time_in","DESC");
		$q = $this->db->get('employee_time_in');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get timein of each employee
	 * @param int $company_id
	 * @param int $employee_id
	 * @param date $date
	 */
	public function get_timein_halfday_cutoff($company_id,$employee_id,$date)
	{
		$where = array(
			'emp_id'  => $employee_id,
			'comp_id' => $company_id,
			'date_halfday'  => $date,
			'flag_halfday' => 1,
			'time_in_status' => 'Approved'
		);
		$this->db->where($where);
		$this->db->order_by("time_in","DESC");
		$q = $this->db->get('employee_time_in');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get timein of each employee
	 * @param int $company_id
	 * @param int $employee_id
	 * @param date $date
	 */
	public function new_get_timein_halfday_cutoff($company_id,$employee_id,$start,$end)
	{
		$where = array(
			'emp_id'  => $employee_id,
			'comp_id' => $company_id,
			'flag_halfday' => 1,
			'time_in_status' => 'Approved'
		);
		$this->db->where($where);
		
		$this->db->where('date_halfday >=',$start);
		$this->db->where('date_halfday <=',$end);
		
		$this->db->order_by("time_in","DESC");
		$q = $this->db->get('employee_time_in');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Check Night Differential Settings
	 * @param unknown_type $company_id
	 */
	public function check_night_differential_settings($company_id){
		$w = array(
			"company_id"=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("nightshift_differential_settings");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get night differential
	 * @param int $company_id
	 */
	public function get_night_differential($company_id)
	{
		$this->db->where('company_id',$company_id);
		$q = $this->db->get('nightshift_differential_settings');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Check Pay Rate Type
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $payroll_group_id
	 */
	public function check_pay_rate_type($company_id,$emp_id,$payroll_group_id){
		$w = array(
			"epi.payroll_group_id"=>$payroll_group_id,
			"epi.emp_id"=>$emp_id,
			'epi.employee_status' => 'Active'
		);
		$this->db->where($w);
		$this->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$q = $this->db->get("employee_payroll_information AS epi");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Leave Applications
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function check_leave_appliction($emp_id,$company_id){
		$w = array(
			"ela.emp_id"=>$emp_id,
			"ela.company_id"=>$company_id,
			"ela.leave_application_status"=>"approve"
			// "ela.flag_parent != " => "yes"
		);
		$this->db->where($w);
		$this->db->where("(ela.flag_parent != 'yes' OR ela.flag_parent = 'no' OR ela.flag_parent IS NULL)");		
		$this->db->join("leave_type AS lt","lt.leave_type_id = ela.leave_type_id","LEFT");
		$q = $this->db->get("employee_leaves_application AS ela");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Working Days
	 * @param unknown_type $comp_id
	 * @param unknown_type $id
	 * @param unknown_type $workday
	 * @param unknown_type $str
	 */
	public function check_working_hours($comp_id,$id,$workday,$str){
		// uniform working days
		$w = array(
			"rs.company_id"=>$comp_id,
			#"working_day"=>$workday,
			"rs.days_of_work"=>$workday,
			"rs.status"=>"Active"
		);
		$this->db->where($w);
		
		if($str == "payroll_group_id"){
			$this->db->where("pg.payroll_group_id",$id);
			$this->db->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
			$this->db->join("regular_schedule AS rs","rd.work_schedule_id = ws.work_schedule_id","LEFT");
			$q = $this->db->get('payroll_group AS pg');
		}else{
			$this->db->where("work_schedule_id",$id);
			$q = $this->db->get("regular_schedule AS rs");
		}
		
		$r = $q->row();
		$q->free_result();
		if($r){
			return $r->total_work_hours;
		}else{
			// workshift
			$ww = array(
				"company_id"=>$comp_id
			);
			$this->db->where($ww);
		
			if($str == "payroll_group_id"){
				$this->db->where("payroll_group_id",$id);
			}else{
				$this->db->where("work_schedule_id",$id);
			}
			
			$qw = $this->db->get("split_schedule");
			$rw = $qw->row();
			$qw->free_result();
			if($rw){
				return $rw->working_hours;
			}else{
				// flexible hours
				$wf = array(
					"company_id"=>$comp_id
				);
				$this->db->where($wf);
			
				if($str == "payroll_group_id"){
					$this->db->where("payroll_group_id",$id);
				}else{
					$this->db->where("work_schedule_id",$id);
				}
				
				$qf = $this->db->get("flexible_hours");
				$rf = $qf->row();
				$qf->free_result();
				if($rf){
					return $rf->total_hours_for_the_day;
				}else{
					return FALSE;
				}
			}
		}
	}
	
	/**
	 * Check Latest Time Allowed
	 * @param unknown_type $company_id
	 * @param unknown_type $id
	 * @param unknown_type $str
	 */
	public function check_latest_time_allowed($company_id,$id,$str){
		if($str == "work_schedule_id"){
			$w = array(
				"work_schedule_id"=>$id
			);
		}else{
			$w = array(
				"payroll_group_id"=>$id
			);
		}
		$this->db->where($w);
		$q = $this->db->get("flexible_hours");
		$r = $q->row();
		$q->free_result();
		if($r){
			return ($r->latest_time_in_allowed == NULL || $r->latest_time_in_allowed == "") ? TRUE : FALSE ;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check Employee Time In
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $current
	 */
	public function check_employee_time_in($company_id,$emp_id,$current){
		$w = array(
			"emp_id"=>$emp_id,
			"comp_id"=>$company_id,
			"date"=>$current,
			"status"=>"Active"
		);
		$this->db->where($w);
		$this->db->where("time_in IS NOT NULL AND time_out IS NOT NULL");
		$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
		$q = $this->db->get("employee_time_in");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Time In By Hourly
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $current
	 */
	public function check_employee_time_in_by_hourly($company_id,$emp_id,$current){
		$w = array(
			"emp_id"=>$emp_id,
			"comp_id"=>$company_id,
			"date"=>$current,
			"status"=>"Active"
		);
		$this->db->where($w);
		$this->db->where("time_in IS NOT NULL AND time_out IS NOT NULL");
		$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
		$q = $this->db->get("employee_time_in");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Time In
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $current
	 */
	public function new_check_employee_time_in($company_id,$emp_id,$start,$end){
		$w = array(
			"emp_id"=>$emp_id,
			"comp_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		
		$this->db->where('date >=',$start);
		$this->db->where('date <=',$end);
		
		// $this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL) AND location != 'over_xxx'");
		// $this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
		
		// $this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL OR time_in_status = 'pending')");
		// $this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL OR (time_in_status = 'pending' AND source != 'add log'))");
		$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL OR (time_in_status = 'pending' AND source != 'EP'))");
		
		$this->db->where("(location != 'over_xxx' OR location IS NULL)");
		$this->db->where("time_out IS NOT NULL");
		
		$this->db->where("(flag_on_leave IS NULL OR flag_on_leave = 'no')"); // bag.o ni sya nga condition 05-nov-2016
		
		$this->db->order_by("date","ASC");
		$q = $this->db->get("employee_time_in");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
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
			"status" => "Active"
		);
		$this->db->where($w);
		$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
		$q = $this->db->get("employee_time_in");
		$r = $q->row();
		$q->free_result();
		if($r){
			if($r->time_in != NULL && $r->time_out != NULL){
				if($r->tardiness_min > 0 || $r->undertime_min > 0){
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
								"credited"=>$r2->credited
							);
						}else{
							$data["info"] = array(
								"tardiness"=>"",
								"undertime"=>"1",
								"credited"=>$r2->credited
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
										"credited"=>$ns_r->credited
									);
								}else{
									$data["info"] = array(
										"tardiness"=>"",
										"undertime"=>"1",
										"credited"=>$ns_r->credited
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
											"credited"=>$ns_r->credited
										);
									}else{
										$data["info"] = array(
											"tardiness"=>"",
											"undertime"=>"1",
											"credited"=>$ns_r->credited
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
			}
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Payroll Group Info
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 */
	public function payroll_group_name($payroll_group_id,$company_id){
		$w = array(
			"payroll_group_id"=>$payroll_group_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_group");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * De Minimis Settings
	 * @param unknown_type $company_id
	 */
	public function dm_settings($company_id){
		$w = array(
			"company_id"=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("de_minimis_settings");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Non Taxable Income 
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 */
	public function non_taxable_income($emp_id,$comp_id){
		$w = array(
			"company_id"=>$comp_id
		);
		$this->db->where($w);
		$sql = $this->db->get("de_minimis");
		$r = $sql->result();
		$sql->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * De Minimis For Diff Employee
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function de_minimis_different_employee($company_id){
		$w = array(
			"dms.company_id"=>$company_id
		);
		$this->db->where($w);
		$q = $this->edb->get("de_minimis_for_diff_emp AS dms");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Allowances Settings
	 * @param unknown_type $company_id
	 */
	public function allowances_settings($company_id){
		$w = array(
			"company_id"=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("allowance_settings");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;	
	}
	
	/**
	 * Earnings Information
	 * @param unknown_type $company_id
	 */
	public function earnings_information($company_id){
		$w = array(
			"company_id"=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("earnings");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get other deductions
	 * @param int $company_id
	 */
	public function get_other_deductions($company_id)
	{
		$where = array(
			'comp_id' => $company_id,
			// 'view'	  => 'YES',
			'status'  => 'Active'
		);
		$this->edb->where($where);
		$q = $this->edb->get('deductions_other_deductions');
		$result = $q->result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Employee List
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $per_page
	 * @param unknown_type $page
	 * @param unknown_type $sort_by
	 * @param unknown_type $q = $search
	 */
	public function carry_over_employee($payroll_group_id,$company_id,$per_page, $page, $sort_by, $q){
		$konsum_key = konsum_key();
		$s = array(
			"*","e.emp_id AS emp_id"
		);
		$this->edb->select($s);
		
		$w = array(
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		
		if($q !==""){
			$this->db->where("
				(convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
		}
		
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		
		if($sort_by != ""){
			if($sort_by == "e.first_name"){
				$this->edb->order_by($sort_by,"ASC");
			}else{
				$this->db->order_by($sort_by,"ASC");
			}
		}else{
			$this->edb->order_by("e.first_name","ASC");
		}
		
		$q = $this->edb->get("employee AS e",$per_page, $page);
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Counter
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 */
	public function carry_over_employee_counter($payroll_group_id,$company_id){
		$s = array(
			"COUNT(e.emp_id) AS total" 
		);
		$this->db->select($s);
		$w = array(
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		$this->edb->order_by("e.first_name","ASC");
		$q = $this->edb->get("employee AS e");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Get De Minimis Amount from Carry Over
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $de_minimis_id
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $payroll_period
	 */
	public function get_deminimis_amount($emp_id,$company_id,$de_minimis_id,$period_from,$period_to,$payroll_period){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"de_minimis_id"=>$de_minimis_id,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"payroll_period"=>$payroll_period
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_de_minimis");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Allowances Amount from Carry Over
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $allowance_settings_id
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $payroll_period
	 */
	public function get_allowances_amount($emp_id,$company_id,$allowance_settings_id,$period_from,$period_to,$payroll_period){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"allowance_settings_id"=>$allowance_settings_id,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"payroll_period"=>$payroll_period
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_allowances");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Earnings Amount from Carry Over
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $allowance_settings_id
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $payroll_period
	 */
	public function get_earnings_amount($emp_id,$company_id,$earning_id,$period_from,$period_to,$payroll_period){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"earning_id"=>$earning_id,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"payroll_period"=>$payroll_period
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_earnings");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Other Deduction Amount from Carry Over
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $deductions_other_deductions_id
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $payroll_period
	 */
	public function get_other_deductions_amount($emp_id,$company_id,$deductions_other_deductions_id,$period_from,$period_to,$payroll_period){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"other_deduction_id"=>$deductions_other_deductions_id,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"payroll_period"=>$payroll_period
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_other_deductions");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Payroll Carry Over
	 * @param unknown_type $field
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function payroll_carry_over($field,$emp_id,$company_id){
		$w = array(
			"emp_id"=>$emp_id
		);
		$this->db->where($w);
		$q = $this->edb->get("payroll_carry_over");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->$field : 0 ;
	}
	
	/**
	 * Check Previous Overtime
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $previous_period_from
	 * @param unknown_type $previous_period_to
	 * @param unknown_type $emp_id
	 */
	public function check_previous_overtime($period_from,$period_to,$previous_period_from,$previous_period_to,$emp_id){
		$w = array(
			"overtime_from >= " => $previous_period_from,
			"overtime_from <= " => $previous_period_to,
		
			"overtime_to >= " => $previous_period_from,
			"overtime_to <= " => $previous_period_to,
		
			"DATE(approval_date) >= " => $period_from,
			"DATE(approval_date) <= " => $period_to,
		
			"emp_id"=>$emp_id,
			"overtime_status"=>"approved"
		);
		$this->db->where($w);
		$q = $this->db->get("employee_overtime_application");
		$r = $q->result();
		$q->free_result();
		if($r){
			return $r;
			
			/*$total = 0;
			foreach($r as $row){
				$total = $total + $row->no_of_hours;
			}
			return $total;*/
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Get payroll period
	 * @param int $company_id
	 */
	public function get_payroll_period($company_id)
	{
		$where = array( 
			'payroll_period.company_id' => $company_id,
			'payroll_period.status'	 => 'Active'
		);
		$this->db->where($where);
		$this->db->join('payroll_group','payroll_group.payroll_group_id = payroll_period.payroll_group_id','left');
		
		$q = $this->db->get('payroll_period');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * New Hourly Rate
	 * @param unknown_type $company_id
	 * @param unknown_type $employee_id
	 * @param unknown_type $payroll_group_id
	 */
	public function new_hourly_rate($company_id,$employee_id,$payroll_group_id,$period,$work_schedule_id=NULL){
		// check if employee salary is set
		$basic_pay = $this->get_basic_pay($company_id,$employee_id);
		
		$day_per_year = 0;
		$bp = 0;
		
		if (!$basic_pay) {
			return false;
		}
		
		// Get Average Working Hours Per Day
		$average_working_hours_per_day = $this->average_working_hours_per_day($company_id);
		
		// check new basic pay
		$edate = date('Y-m-d',strtotime($basic_pay->effective_date));
		if (is_object($period)) {
			$pfrom = date('Y-m-d',strtotime($period->period_from));
			$pto   = date('Y-m-d',strtotime($period->period_to));	
		} elseif (is_array($period)) {
			$pfrom = date('Y-m-d',strtotime($period['period_from']));
			$pto   = date('Y-m-d',strtotime($period['period_to']));
		} else {
			$pfrom ="";
			$pto="";
		}
		

		if ($basic_pay->effective_date) {
			if (($pfrom <= $edate && $pto >= $edate) || $edate <= $pfrom) {
				$bp = $basic_pay->new_basic_pay; 
			} else {
				$bp = $basic_pay->current_basic_pay;
			}
		} else {
			$bp = $basic_pay->current_basic_pay;
		}
		
		// get total days per year
		$day_per_year = $this->rank_total_working_days_in_a_year($employee_id);
		$day_per_year = ($day_per_year != FALSE) ? $day_per_year->total_working_days_in_a_year / 12 : 0 ;
		
		// check employee period type
		$check_employee_period_type = $this->check_employee_period_type($employee_id,$company_id);
		if($check_employee_period_type != FALSE){
			
			// CHECK MINIMUM WAGE EARNER
			$check_minimum_wage_earner = $this->check_minimum_wage_earner($company_id,$employee_id);
			if($check_minimum_wage_earner != FALSE){
				// if ($check_minimum_wage_earner->minimum_wage_earner == 'yes') {
				if ($check_minimum_wage_earner->minimum_wage_earner == 'yes' && FALSE) { // wla nani kay na trap nani ni keith for employee basic pay
						
					$daily_minimum_wage = 0;
			
					// CHECK MINIMUM WAGE EARNER SETTINGS
					$check_minimum_wage_earner_default_settings = $this->check_minimum_wage_earner_default_settings($company_id);
					if($check_minimum_wage_earner_default_settings != FALSE){
						// Default Daily Minimum Wage
						$daily_minimum_wage = $check_minimum_wage_earner_default_settings->amount;
					}else{
						// Use Current Regional Daily Minimum Wage Table
						$current_regional_daily_minimum_wage_table = $this->current_regional_daily_minimum_wage_table($company_id);
						if($current_regional_daily_minimum_wage_table != FALSE){
							// $daily_minimum_wage = $current_regional_daily_minimum_wage_table->non_agriculture + $current_regional_daily_minimum_wage_table->cola;
							
							// CHECK COMPANY INDUSTRY
							$check_company_industry = $this->prm->check_company_industry($company_id);
							if($check_company_industry != FALSE){
							
								if($check_company_industry->industry == "Non-Agriculture"){
									$company_industry = $current_regional_daily_minimum_wage_table->non_agriculture;
								}else if($check_company_industry->industry == "Agriculture (plantation and non-plantation)"){
									$company_industry = $current_regional_daily_minimum_wage_table->agriculture_plantation;
								}else if($check_company_industry->industry == "Private Hospitals (bed capacity of 100 or less)"){
									$company_industry = $current_regional_daily_minimum_wage_table->private_hospitals;
								}else if($check_company_industry->industry == "Retail/Service Establishment (15 workers or less)"){
									$company_industry = $current_regional_daily_minimum_wage_table->retail_and_service_establishments_with_morethan_ten_workers;
								}else if($check_company_industry->industry == "manufacturing employing less than 10 workers"){
									$company_industry = $current_regional_daily_minimum_wage_table->manufacturing_establishments;
								}else{
									$company_industry = 0;
								}
							
								$employee_cola = ($current_regional_daily_minimum_wage_table->cola > 0) ? $current_regional_daily_minimum_wage_table->cola : 0 ;
								// $daily_minimum_wage = $company_industry + $employee_cola;
								$daily_minimum_wage = $company_industry;
							}
							
						}
					}
			
					$days_for_daily_minimum_wage = 0;
			
					// CHECK EMPLOYEE TIMEIN
					$check_employee_timein = $this->check_employee_timein($company_id,$employee_id,$pfrom,$pto);

					if($check_employee_timein != FALSE){
						foreach($check_employee_timein as $row_employee_timein){
							$days_for_daily_minimum_wage++;
						}
					}
			
					// DEFAULT BASIC PAY
					// $bp = $daily_minimum_wage * $days_for_daily_minimum_wage;
					$bp = $daily_minimum_wage;
					
					// return ($day_per_year > 0) ? ($bp / 8) : 0 ;
					return ($day_per_year > 0) ? ($bp / $average_working_hours_per_day) : 0 ;
					
				} else {
					
					$employee_period_type = $check_employee_period_type->period_type;
					$employee_pay_rate_type = $check_employee_period_type->pay_rate_type;
						
					// get hourly rate
					if(
						($employee_period_type == "Semi Monthly" && $employee_pay_rate_type == "By Hour")
							||
						($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Hour")
						){ // hourly
						return ($day_per_year > 0) ? $bp : 0 ;
					}else if(
							($employee_period_type == "Semi Monthly" && $employee_pay_rate_type == "By Day")
								||
							($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Day")
							){ // daily
						// return ($day_per_year > 0) ? ($bp / 8) : 0 ;
						return ($day_per_year > 0) ? ($bp / $average_working_hours_per_day) : 0 ;
					}else if(
							($employee_period_type == "Semi Monthly" && $employee_pay_rate_type == "By Month")
								||
							($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Month")
							){ // month
						// return ($day_per_year > 0) ? ($bp / $day_per_year / 8) : 0 ;
						return ($day_per_year > 0) ? ($bp / $day_per_year / $average_working_hours_per_day) : 0 ;
					}else{
						return 0;
					}		
					
				}
			} else {
				return 0;
			}
			
		}else{
			return 0;
		}
	}
	
	/**
	 * Get Average Working Hours Per Day
	 * @param unknown $company_id
	 */
	public function average_working_hours_per_day($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
			#"average_working_hours_per_day != " => NULL
		);
		$this->db->where($w);
		$this->db->where("average_working_hours_per_day IS NOT NULL");
		$q = $this->db->get("payroll_calendar_working_days_settings");
		$r = $q->row();
		$average_working_hours_per_day = ($r) ? $r->average_working_hours_per_day : 8 ;
		return $average_working_hours_per_day;
	}
	
	/**
	 * get employee earnings
	 * @param int $company_id
	 * @param int $employee_id
	 */
	public function get_basic_pay($company_id,$employee_id)
	{
		$where = array(
			'comp_id' 	 => $company_id,
			'emp_id' 	 => $employee_id,
			'status'	 => 'Active'
		);
		$this->edb->where($where);
		$q = $this->edb->get('basic_pay_adjustment');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Employee Basic Pay
	 * @param unknown_type $comp_id
	 * @param unknown_type $emp_id
	 */
	public function emp_basic_pay($comp_id,$emp_id,$period){
		$bp = 0;
		// check if employee salary is set
		$basic_pay = $this->get_basic_pay($comp_id,$emp_id);
		
		if (!$basic_pay) {
			return false;
		}

		// check new basic pay
		$edate = date('Y-m-d',strtotime($basic_pay->effective_date));
		if (is_object($period)) {
			$pfrom = date('Y-m-d',strtotime($period->period_from));
			$pto   = date('Y-m-d',strtotime($period->period_to));	
		} elseif (is_array($period)) {
			$pfrom = date('Y-m-d',strtotime($period['period_from']));
			$pto   = date('Y-m-d',strtotime($period['period_to']));
		}
		
		if ($basic_pay->effective_date) {
			if (($pfrom <= $edate && $pto >= $edate) || $edate <= $pfrom) {
				$bp = $basic_pay->new_basic_pay; 
			} else {
				$bp = $basic_pay->current_basic_pay;
			}
		} else {
			$bp = $basic_pay->current_basic_pay;
		}

		// FOR FORTNIGHTLY BASIC PAY
		$check_employee_period_type = $this->check_employee_period_type($emp_id,$comp_id);
		
		if($check_employee_period_type != FALSE){
			
			// Get Average Working Hours Per Day
			$average_working_hours_per_day = $this->average_working_hours_per_day($comp_id);
			
			// get total days per year
			$day_per_year = $this->rank_total_working_days_in_a_year($emp_id);
			$day_per_year = ($day_per_year != FALSE) ? $day_per_year->total_working_days_in_a_year / 12 : 0 ;
			
			$employee_period_type = $check_employee_period_type->period_type;
			$employee_pay_rate_type = $check_employee_period_type->pay_rate_type;
			
			if($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Month"){ // hourly
				$bp = $bp / $day_per_year;
				$bp = ($bp > 0) ? $bp : 0 ;
			}
		}
		
		return $bp;
	}
	
	/**
	 * Check Previous Payroll Period
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $period_to
	 */
	public function check_previous_payroll_period($payroll_group_id,$company_id,$period_to){
		$w = array(
			"cut_off_to !=" => $period_to,
			"cut_off_to <=" => $period_to,
			"company_id" => $company_id,
			"payroll_group_id"=>$payroll_group_id
		);
		$this->db->where($w);
		$this->db->order_by("cut_off_to","DESC");
		$q = $this->db->get("payroll_calendar",1);
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Payroll Carry Over
	 * @param unknown_type $emp_id
	 */
	public function check_payroll_co($emp_id,$period_from,$period_to){
		$w = array(
			"emp_id"=>$emp_id,
			"period_from"=>$period_from,
			"period_to"=>$period_to
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over");
		return ($q->num_rows() == 0) ? TRUE : FALSE ;
	}
	
	/**
	 * Advance Payment
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $per_page
	 * @param unknown_type $page
	 * @param unknown_type $sort_by
	 * @param unknown_type $q
	 */
	public function advance_payment($start,$end,$payroll_group_id,$company_id,$per_page, $page, $sort_by, $q){
		$konsum_key = konsum_key();
		
		$s = array(
			"*","e.emp_id AS emp_id"
		);
		$this->edb->select($s);
		
		$w = array(
			"pc.period_from"=>$start,
			"pc.period_to"=>$end,
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		
		if($q !==""){
			$this->db->where("
				(convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
		}
		
		$this->edb->join("employee AS e","e.emp_id = pc.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		
		if($sort_by != ""){
			if($sort_by == "e.first_name"){
				$this->edb->order_by($sort_by,"ASC");
			}else{
				$this->db->order_by($sort_by,"ASC");
			}
		}else{
			$this->edb->order_by("e.first_name","ASC");
		}
		
		$q = $this->edb->get("payroll_carry_over_advance_payment AS pc",$per_page, $page);
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Advance Payment Counter
	 * @param unknown_type $start
	 * @param unknown_type $end
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $sort_by
	 * @param unknown_type $q
	 */
	public function advance_payment_counter($start,$end,$payroll_group_id,$company_id, $sort_by, $q){
		$konsum_key = konsum_key();
		
		$s = array(
			"COUNT(pc.emp_id) AS total"
		);
		$this->db->select($s);
		
		$w = array(
			"pc.period_from"=>$start,
			"pc.period_to"=>$end,
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		
		if($q !==""){
			$this->db->where("
				(convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
		}
		
		$this->edb->join("employee AS e","e.emp_id = pc.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		
		if($sort_by != ""){
			if($sort_by == "e.first_name"){
				$this->edb->order_by($sort_by,"ASC");
			}else{
				$this->db->order_by($sort_by,"ASC");
			}
		}else{
			$this->edb->order_by("e.first_name","ASC");
		}
		
		$q = $this->edb->get("payroll_carry_over_advance_payment AS pc");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Retroactive Gross Pay Employee
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $per_page
	 * @param unknown_type $page
	 * @param unknown_type $sort_by
	 * @param unknown_type $q
	 */
	public function retroactive_gross_pay_employee($payroll_group_id,$company_id,$per_page, $page, $sort_by, $q){
		$konsum_key = konsum_key();
		
		$s = array(
			"*","e.emp_id AS emp_id"
		);
		$this->edb->select($s);
		
		$w = array(
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->where('bpa.basic_pay_id IS NOT NULL');
		
		if($q !==""){
			$this->db->where("
				(convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
		}
		
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		
		if($sort_by != ""){
			if($sort_by == "e.first_name"){
				$this->edb->order_by($sort_by,"ASC");
			}else{
				$this->db->order_by($sort_by,"ASC");
			}
		}else{
			$this->edb->order_by("e.first_name","ASC");
		}
		
		$q = $this->edb->get("employee AS e",$per_page, $page);
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Retroactive Gross Pay Employee Counter
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $sort_by
	 * @param unknown_type $q
	 */
	public function retroactive_gross_pay_employee_counter($payroll_group_id,$company_id, $sort_by, $q){
		$konsum_key = konsum_key();
		$s = array(
			"COUNT(e.emp_id) AS total"
		);
		$this->db->select($s);
		
		$w = array(
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->where('bpa.basic_pay_id IS NOT NULL');
		
		if($q !==""){
			$this->db->where("
				(convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
		}
		
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		
		if($sort_by != ""){
			if($sort_by == "e.first_name"){
				$this->edb->order_by($sort_by,"ASC");
			}else{
				$this->db->order_by($sort_by,"ASC");
			}
		}else{
			$this->edb->order_by("e.first_name","ASC");
		}
		
		$q = $this->edb->get("employee AS e");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Employee Basic Pay Adjustment
	 * @param unknown_type $emp_id
	 */
	public function employee_basic_adjustment($emp_id)
	{
		$this->edb->where('bpa.emp_id',$emp_id);
		$this->edb->where('a.user_type_id','5');
		$this->edb->join('employee AS e','bpa.emp_id = e.emp_id','left');
		$this->edb->join('accounts AS a','e.account_id = a.account_id','left');
		$this->edb->join('employee_payroll_information AS epi','bpa.emp_id = epi.emp_id','left');
		$this->edb->join('payroll_group AS pg','epi.payroll_group_id = pg.payroll_group_id','left');
		$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		$this->edb->order_by("e.last_name","asc");
		$sql = $this->edb->get('basic_pay_adjustment AS bpa');
		$r = $sql->row();
		$sql->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Retro Active Settings
	 * @param unknown_type $comp_id
	 */
	public function retro_active_settings($comp_id)
	{
		$where = array(
			'comp_id' => $comp_id,
			'status'  => 'Active'
		);
		$this->db->where($where);
		$sql = $this->db->get('retroactive_settings');
		
		return $sql->row();
	}
	
	/**
	 * Previos Payroll Period
	 * @param unknown_type $pfrom
	 * @param unknown_type $pto
	 * @param unknown_type $comp_id
	 * @param unknown_type $payroll_group_id
	 */
	public function previos_pp($pfrom,$pto,$comp_id,$emp_id){
		
		$this->db->where("emp_id",$emp_id);
		$q1 = $this->db->get("employee_payroll_information");
		$r1 = $q1->row();
		$q1->free_result();
		if($r1){
			$payroll_group_id = $r1->payroll_group_id;
			$w2 = array(
				"cut_off_from"=>$pfrom,
				"cut_off_to"=>$pto,
				"company_id"=>$comp_id,
				"payroll_group_id"=>$payroll_group_id
			);
			$this->db->where($w2);
			$q2 = $this->db->get("payroll_calendar");
			$r2 = $q2->row();
			$q2->free_result();
			if($r2){
				$payroll_calendar_id = $r2->payroll_calendar_id;
				
				$this->db->select("COUNT(payroll_calendar_id) as count");
				$w3 = array(
					"payroll_calendar_id <= " => $payroll_calendar_id
				); 
				$this->db->where($w3);
				$q3 = $this->db->get("payroll_calendar");
				$r3 = $q3->row();
				$q3->free_result();
				$row_counter = ($r3) ? $row_counter->count - 1 : 0 ;
				
				$w4 = array(
					"company_id"=>$comp_id,
					"payroll_group_id"=>$payroll_group_id
				);
				$this->db->where($w4);
				$q4 = $this->db->get("payroll_calendar",0,$row_counter);
				$r4 = $q4->row();
				$q4->free_result();
				return ($r4) ? $r4 : FALSE ;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	/**
	 * Get Grace Period
 	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 */
	public function get_grace_period($emp_id,$comp_id){
		/* $w = array(
			"epi.company_id"=>$comp_id,
			"epi.status"=>"Active",
			"ts.comp_id"=>$comp_id,
			"ts.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("employee_payroll_information AS epi","epi.rank_id = ts.rank_id","LEFT");
		$sql = $this->db->get("tardiness_settings AS ts");
		$row = $sql->row();
		$sql->free_result();
		// return ($row) ? $row->tarmin : 30 ; // company wide 30 minutes default
		
		if($row){
			return $row->tarmin;
		}else{
			$w = array(
				"comp_id"=>$comp_id,
				"default"=>1,
				"status"=>"Active"
			);
			$this->db->where($w);
			$sql = $this->db->get("tardiness_settings");
			$row = $sql->row();
			return ($row) ? $row->tarmin : 0 ;
		} */
		
		// VERSION 1.6
		$w = array(
			"epi.emp_id"=>$emp_id,
			"epi.company_id"=>$comp_id,
			"epi.status"=>"Active"
		);
		$this->db->where($w);
		$sql = $this->db->get("employee_payroll_information AS epi");
		$row = $sql->row();
		if($row){
			
			// RANK ID OR PAYROLL GROUP ID
			$rank_id = ($row->rank_id > 0) ? $row->rank_id : 0 ;
			$payroll_group_id = ($row->payroll_group_id > 0) ? $row->payroll_group_id : 0 ;
			
			// CHECK TARDINESS SETTINGS
			$this->db->where("comp_id",$comp_id);
			$q = $this->db->get("tardiness_settings");
			$r = $q->result();
			if($r){
				$value = 0;
				$flag = TRUE;
				foreach($r as $row_tard_settings){
					if($flag && $row_tard_settings->grace_period_type == "accumulated" && $row_tard_settings->tarmin > 0){
						// CHECK FOR RANK IDS or PAYROLL GROUP IDS
						$rank_ids_array = explode(",", $row_tard_settings->rank_ids);
						$payroll_group_ids_array = explode(",", $row_tard_settings->payroll_group_ids);
						if(in_array($rank_id, $rank_ids_array) || in_array($payroll_group_id, $payroll_group_ids_array)){
							$value = $row_tard_settings->tarmin;
						}
					}
				}
				return $value;
			}else{
				return 0;
			}
			
		}else{
			return 0;
		}
		
	}
	
	/**
	 * No need to encrypt
	 * Get total timein
	 * @param int $company_id
	 * @param int $employee_id
	 * @param date $from_date
	 * @param date $to_date
	 */
	public function get_total_timein($company_id,$employee_id,$from_date,$to_date)
	{
		$select = array(
			'SUM(tardiness_min) AS tardiness',
			'SUM(undertime_min) AS undertime'
		);
		$where = array(
			'emp_id'  => $employee_id,
			'comp_id' => $company_id,
			'flag_halfday' => 0
		);
		$this->db->select($select);
		$this->db->where($where);
		$this->db->where('date BETWEEN "'.$from_date.'" AND "'.$to_date.'"',NULL,FALSE);
		$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
		$q = $this->db->get('employee_time_in');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * No need to encrypt
	 * @param int $company_id
	 * @param int $employee_id
	 * @param date $date
	 */
	public function retroactive_get_leave_application($company_id,$employee_id,$date)
	{
		$where = array(
			'company_id' 			     => $company_id,	
			'emp_id'	 			     => $employee_id,
			'CAST(approval_date AS DATE) =' => $date,
			'leave_application_status'	 => 'approve' 
		);
		$this->db->where($where);
		$q = $this->db->get('employee_leaves_application');
		$result = $q->result();
		$q->free_result();
		return ($result) ?  $result:  false;
	}
	
	/**
	 * New Hourly Rate
	 * @param unknown_type $company_id
	 * @param unknown_type $employee_id
	 * @param unknown_type $payroll_group_id
	 */
	public function retroactive_new_hourly_rate($company_id,$employee_id,$payroll_group_id,$period,$work_schedule_id=NULL){
		// check if employee salary is set
		$basic_pay = $this->get_basic_pay($company_id,$employee_id);
		
		$day_per_year = 0;
		$bp = 0;
		
		if (!$basic_pay) {
			return false;
		}
		
		// check new basic pay
		$edate = date('Y-m-d',strtotime($basic_pay->effective_date));
		if (is_object($period)) {
			$pfrom = date('Y-m-d',strtotime($period->period_from));
			$pto   = date('Y-m-d',strtotime($period->period_to));	
		} elseif (is_array($period)) {
			$pfrom = date('Y-m-d',strtotime($period['period_from']));
			$pto   = date('Y-m-d',strtotime($period['period_to']));
		}
		

		if ($basic_pay->effective_date) {
			if (($pfrom <= $edate && $pto >= $edate) || $edate <= $pfrom) {
				// $bp = $basic_pay->new_basic_pay; 
				$bp = $basic_pay->current_basic_pay; // for retroactive purpose
			} else {
				$bp = $basic_pay->current_basic_pay;
			}
		} else {
			$bp = $basic_pay->current_basic_pay;
		}
		
		// get workday
		if($work_schedule_id!=NULL){
			/* EMPLOYEE WORK SCHEDULE */
			$workday = $this->get_workday($company_id,$work_schedule_id);
		}else{
			/* EMPLOYEE PAYROLL INFORMATION */
			$workday = $this->get_workday_default($company_id,$payroll_group_id);
		}
		
		if(!$workday){
			return 0;
		}
		
		// get total days per year
		$day_per_year = $this->rank_total_working_days_in_a_year($employee_id);
		$day_per_year = ($day_per_year != FALSE) ? $day_per_year->total_working_days_in_a_year / 12 : 0 ;
		
		// get hourly rate
		return ($day_per_year > 0) ? ($bp / $day_per_year / 8) : 0 ;
	}
	
	/**
	 * Employee Basic Pay
	 * @param unknown_type $comp_id
	 * @param unknown_type $emp_id
	 */
	public function retroactive_emp_basic_pay($comp_id,$emp_id,$period){
		$bp = 0;
		// check if employee salary is set
		$basic_pay = $this->get_basic_pay($comp_id,$emp_id);
		
		if (!$basic_pay) {
			return false;
		}

		// check new basic pay
		$edate = date('Y-m-d',strtotime($basic_pay->effective_date));
		if (is_object($period)) {
			$pfrom = date('Y-m-d',strtotime($period->period_from));
			$pto   = date('Y-m-d',strtotime($period->period_to));	
		} elseif (is_array($period)) {
			$pfrom = date('Y-m-d',strtotime($period['period_from']));
			$pto   = date('Y-m-d',strtotime($period['period_to']));
		}
		
		if ($basic_pay->effective_date) {
			if (($pfrom <= $edate && $pto >= $edate) || $edate <= $pfrom) {
				// $bp = $basic_pay->new_basic_pay; 
				$bp = $basic_pay->current_basic_pay; // for retroactive purpose
			} else {
				$bp = $basic_pay->current_basic_pay;
			}
		} else {
			$bp = $basic_pay->current_basic_pay;
		}
		
		return $bp;
	}
	
	/**
	 * New Hourly Rate
	 * @param unknown_type $company_id
	 * @param unknown_type $employee_id
	 * @param unknown_type $payroll_group_id
	 */
	public function new_retroactive_hourly_rate($company_id,$employee_id,$payroll_group_id,$period,$work_schedule_id=NULL){
		// check if employee salary is set
		$basic_pay = $this->get_basic_pay($company_id,$employee_id);
		
		$day_per_year = 0;
		$bp = 0;
		
		if (!$basic_pay) {
			return false;
		}
		
		// check new basic pay
		$edate = date('Y-m-d',strtotime($basic_pay->effective_date));
		if (is_object($period)) {
			$pfrom = date('Y-m-d',strtotime($period->period_from));
			$pto   = date('Y-m-d',strtotime($period->period_to));	
		} elseif (is_array($period)) {
			$pfrom = date('Y-m-d',strtotime($period['period_from']));
			$pto   = date('Y-m-d',strtotime($period['period_to']));
		}
		

		if ($basic_pay->effective_date) {
			if (($pfrom <= $edate && $pto >= $edate) || $edate <= $pfrom) {
				// $bp = $basic_pay->new_basic_pay; 
				// $bp = $basic_pay->current_basic_pay; // for retroactive purpose
				$bp = $basic_pay->new_basic_pay; // for new retroactive purpose
			} else {
				// $bp = $basic_pay->current_basic_pay;
				$bp = $basic_pay->new_basic_pay; // for new retroactive purpose
			}
		} else {
			// $bp = $basic_pay->current_basic_pay;
			$bp = $basic_pay->new_basic_pay; // for new retroactive purpose
		}
		
		// get workday
		if($work_schedule_id!=NULL){
			/* EMPLOYEE WORK SCHEDULE */
			$workday = $this->get_workday($company_id,$work_schedule_id);
		}else{
			/* EMPLOYEE PAYROLL INFORMATION */
			$workday = $this->get_workday_default($company_id,$payroll_group_id);
		}
		
		if(!$workday){
			return 0;
		}
		
		// get total days per year
		$day_per_year = $this->rank_total_working_days_in_a_year($employee_id);
		$day_per_year = ($day_per_year != FALSE) ? $day_per_year->total_working_days_in_a_year / 12 : 0 ;
		
		// get hourly rate
		return ($day_per_year > 0) ? ($bp / $day_per_year / 8) : 0 ;
	}
	
	/**
	 * Employee Basic Pay
	 * @param unknown_type $comp_id
	 * @param unknown_type $emp_id
	 */
	public function new_retroactive_emp_basic_pay($comp_id,$emp_id,$period){
		$bp = 0;
		// check if employee salary is set
		$basic_pay = $this->get_basic_pay($comp_id,$emp_id);
		
		if (!$basic_pay) {
			return false;
		}

		// check new basic pay
		$edate = date('Y-m-d',strtotime($basic_pay->effective_date));
		if (is_object($period)) {
			$pfrom = date('Y-m-d',strtotime($period->period_from));
			$pto   = date('Y-m-d',strtotime($period->period_to));	
		} elseif (is_array($period)) {
			$pfrom = date('Y-m-d',strtotime($period['period_from']));
			$pto   = date('Y-m-d',strtotime($period['period_to']));
		}
		
		if ($basic_pay->effective_date) {
			if (($pfrom <= $edate && $pto >= $edate) || $edate <= $pfrom) {
				// $bp = $basic_pay->new_basic_pay; 
				// $bp = $basic_pay->current_basic_pay; // for retroactive purpose
				$bp = $basic_pay->new_basic_pay; // for new retroactive purpose
			} else {
				// $bp = $basic_pay->current_basic_pay;
				$bp = $basic_pay->new_basic_pay; // for new retroactive purpose
			}
		} else {
			// $bp = $basic_pay->current_basic_pay;
			$bp = $basic_pay->new_basic_pay; // for new retroactive purpose
		}
		
		return $bp;
	}
	
	/**
	 * Employee Overtime
	 * @param unknown_type $emp_id
	 * @param unknown_type $retro_datefrom
	 * @param unknown_type $retro_dateto
	 */
	public function get_overtime_emp_current_new_pay($emp_id,$retro_datefrom,$retro_dateto){
		$w = array(
			"overtime_date_applied >= " => $period_from,
			"overtime_date_applied <= " => $period_to,
			"approval_date >= " => $period_from,
			"approval_date <= " => $period_to,
			"emp_id" => $emp_id,
			"overtime_status" => "approved"  
		);
		$this->db->where($w);
		$q = $this->db->get("employee_overtime_application");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Period Type
	 * @param unknown_type $company_id
	 * @param unknown_type $payroll_group_id
	 */
	public function check_period_type($company_id,$payroll_group_id){
		$where = array(
			'company_id' => $company_id,
			'payroll_group_id' => $payroll_group_id,
			'status' => 'Active'
		);
		$this->db->where($where);
		$q = $this->db->get('payroll_group');
		$row = $q->row();
		$q->free_result();
		return ($row) ? $row : false;
	}
	
	/**
	 * Get working hours
	 * @param int $company_id
	 * @param int $work_schedule_id
	 * @param string $weekday
	 */
	public function get_working_hours($company_id,$work_schedule_id,$weekday)
	{
		// get workday settings
		$workday = $this->get_workday($company_id,$work_schedule_id);
		$hours = 0;
		if($workday != FALSE){
			switch ($workday->workday_type) {
				case 'Uniform Working Days':
					$w = $this->get_uniform_working_day($company_id,$work_schedule_id,$weekday);
					if ($w) $hours = $w->total_work_hours;
					break;
				case 'Flexible Hours':
					$w = $this->get_flexible_hour($company_id,$work_schedule_id);
					if ($w) {
						if ($w->not_required_login == 0) {
							$hours = $w->total_hours_for_the_day;
						}
					}
					break;
				case 'Workshift':
					$w = $this->get_workshift($company_id,$work_schedule_id);
					if ($w) $hours = $w->total_work_hours;
					break;
			}
		}
		
		return $hours;
	}
	
	/**
	 * Check Employee Time In
	 * @param unknown_type $company_id
	 * @param unknown_type $employee_id
	 * @param unknown_type $start
	 * @param unknown_type $end
	 */
	public function check_employee_timein($company_id,$employee_id,$start,$end)
	{
		$where = array(
			'emp_id'  => $employee_id,
			'comp_id' => $company_id,
		);
		$this->db->where($where);
		
		$this->db->where('date >=',$start);
		$this->db->where('date <=',$end);
		
		$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
		$this->db->order_by("time_in","DESC");
		$q = $this->db->get('employee_time_in');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * get payroll group
	 * @param int $company_id
	 * @param int $payroll_group_id
	 */
	public function get_payroll_group($company_id,$payroll_group_id)
	{
		$where = array(
			'payroll_group_id' => $payroll_group_id,
			'company_id'	   => $company_id,
			'status'		   => 'Active'
		);
		$this->edb->where($where);
		$q = $this->edb->get('payroll_group');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get night differential rate
	 * @param int $company_id
	 * @param date $current
	 * @param date $start
	 * @param date $end
	 * @param decimal $hour_rate
	 * @param decimal $payroll_group_id
	 * @param decimal $work_schedule_id
	 */
	public function get_night_differential_rate($company_id,$current,$start,$end,$hour_rate,$payroll_group_id,$work_schedule_id=NULL)
	{
		$flag = 0;
		$diff_rate = 0;
		$nd = $this->get_night_differential($company_id);
		if ($nd) {
			if ($start && $end) {
				
				if($nd->from_time != NULL && $nd->to_time != NULL){
					if(date("A",strtotime($nd->from_time)) == "PM" && date("A",strtotime($nd->to_time)) == "AM"){
						$nd_timein = date('Y-m-d H:i:s',strtotime($current.' '.$nd->from_time));
						$nd_timeout = date('Y-m-d H:i:s',strtotime($current.' '.$nd->to_time.' +1 day'));
					}else{
						$nd_timein = date('Y-m-d H:i:s',strtotime($current.' '.$nd->from_time));
						$nd_timeout = date('Y-m-d H:i:s',strtotime($current.' '.$nd->to_time));
					}
					
					// get weekday
					$weekday = date('l',strtotime($current));
					
					// get workday settings
					$workday = $this->get_workday($company_id,$work_schedule_id);
					
					if($workday != FALSE){
						switch ($workday->workday_type) {
							case 'Uniform Working Days':
								$w = $this->get_uniform_working_day($company_id,$payroll_group_id,$weekday);
								
								if ($w) {
									$work_start_time = date('Y-m-d H:i:s',strtotime($current.' '.$w->work_start_time));
									$work_end_time   = date('Y-m-d H:i:s',strtotime($current.' '.$w->work_end_time.' +1 day'));
									$flag = 1;
								}
								break;
							case 'Workshift':
								$w = $this->get_workshift($company_id,$work_schedule_id);
								
								if ($w) {
									$work_start_time = date('Y-m-d H:i:s',strtotime($current.' '.$w->start_time));
									$work_end_time   = date('Y-m-d H:i:s',strtotime($current.' '.$w->end_time.' +1 day'));
									$flag = 1;
								}
								break;
							default:
								$work_start_time = $nd_timein;
								$work_end_time   = $nd_timeout;
								$flag = 1;
								break;
						}
						
						if ($flag) {
							if ($work_start_time >= $nd_timein) $nd_timein = $work_start_time;
							if ($work_end_time <= $nd_timeout) $nd_timeout = $work_end_time;
						}
						
						if ($start <= $nd_timeout && $end >= $nd_timein) {	// night differential
							if ($start >= $nd_timein) {	// late login
								$time_in = strtotime($start);
							} else {
								$time_in = strtotime($nd_timein);
							}
							if ($end <= $nd_timeout) {	// late logout
								$time_out = strtotime($nd_timeout);
							} else {
								$time_out = strtotime($end);
							}
							$diff = ($time_out - $time_in) / 60 / 60;
						} else {
							$diff = 0;
						}
						
						if ($nd->rate_type == 'hourly rate') {
							$diff_rate = ($diff * $hour_rate) * ($nd->rate / 100);
						} else {
							$diff_rate = $diff * $nd->rate;
						}
					}
				}
				
				return $diff_rate;
			}
		} else {
			return 0;
		}
	}
	
	/**
	 * Check Flexible Work Schedule
	 * @param unknown_type $company_id
	 * @param unknown_type $id
	 */
	public function check_flexible_work_schedule($company_id,$id){
		$w = array("work_schedule_id"=>$id);
		$this->db->where($w);
		$q = $this->db->get("flexible_hours");
		$r = $q->row();
		$q->free_result();
		if($r){
			return ($r->not_required_login == 1) ? TRUE : FALSE ;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * No need to encrypt
	 * Get leave application
	 * @param int $company_id
	 * @param int $employee_id
	 * @param date $date
	 */
	public function check_employee_leave_application_for_income($company_id,$employee_id,$date)
	{
		$where = array(
			'company_id' 			     => $company_id,	
			'emp_id'	 			     => $employee_id,
			'CAST(approval_date AS DATE) =' => $date,
			'leave_application_status'	 => 'approve' 
		);
		$this->db->where($where);
		$q = $this->db->get('employee_leaves_application');
		$result = $q->result();
		$q->free_result();
		return ($result) ?  $result:  false;
	}
	
	/**
	 * Get tardiness settings
	 * @param int $company_id
	 */
	public function get_tardiness_settings($company_id)
	{
		$where = array(
			'comp_id' => $company_id,
			'status'  => 'Active'
		);
		$this->edb->where($where);
		$q = $this->edb->get('tardiness_settings');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Carry over adjustment		 
	 * @param int $comp_id
	 */
	public function carry_over_adjustment($comp_id,$emp_id){
		$w = array(
			"c.company_name"=>$comp_id,
			"epi.emp_id"=>$emp_id,
			"epi.employee_status"   => "Active"
		);
		$this->edb->where($w);
		$this->edb->join("employee AS e","epi.emp_id = e.emp_id","left");
		$this->edb->join("company AS c","e.company_id = c.company_id","left");
		$this->edb->join("accounts AS a","e.account_id = a.account_id","left");
		$this->edb->join("basic_pay_adjustment AS b","e.emp_id = b.emp_id","left");
		$q = $this->edb->get("employee_payroll_information AS epi");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get employee fixed allowance
	 * @param int $company_id
	 * @param int $employee_id
	 */
	public function get_employee_fixed_allowance($company_id,$employee_id)
	{
		$where = array(
			'efa.company_id' => $company_id,
			'efa.emp_id'	 => $employee_id
		);
		$this->db->where($where);
		$this->db->join('allowance_settings','allowance_settings.allowance_settings_id = efa.allowance_settings_id','left');
		$q = $this->db->get('employee_fixed_allowances AS efa');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Calculate income
	 * @param int $company_id
	 * @param int $payroll_group_id
	 * @param int $employee_id
	 * @param mixed $period
	 * @param $work_schedule_id
	 */
	public function calculate_income($company_id,$payroll_group_id,$employee_id,$period,$work_schedule_id=NULL)
	{
		// check if employee salary is set
		$basic_pay = $this->get_basic_pay($company_id,$employee_id);
		
		if (!$basic_pay) {
			return false;
		}
		
		$payroll = $period;
		
		$bp = $day_per_year = 0;
		
		// check new basic pay
		$edate = date('Y-m-d',strtotime($basic_pay->effective_date));
		if (is_object($period)) {
			$pfrom = date('Y-m-d',strtotime($period->period_from));
			$pto   = date('Y-m-d',strtotime($period->period_to));	
		} elseif (is_array($period)) {
			$pfrom = date('Y-m-d',strtotime($period['period_from']));
			$pto   = date('Y-m-d',strtotime($period['period_to']));
		}

		if ($basic_pay->effective_date) {
			if (($pfrom <= $edate && $pto >= $edate) || $edate <= $pfrom) {
				$bp = $basic_pay->new_basic_pay; 
			} else {
				$bp = $basic_pay->current_basic_pay;
			}
		} else {
			$bp = $basic_pay->current_basic_pay;
		}
		
		// add new check period type
		
		$check_period_type = $this->check_period_type($company_id,$payroll_group_id);
		if($check_period_type != FALSE){
			if($check_period_type->pay_rate_type == "By Hour"){
				$total_hours = 0;
				// total hours
				$check_employee_timein = $this->check_employee_timein($employee_id,$company_id,$pfrom,$pto);
					if($check_employee_timein != FALSE){
						foreach($check_employee_timein as $row_timein){
							$current = $row_timein->date;
							$check_rd = $this->get_rest_day($company_id,$work_schedule_id,date("l",strtotime($current)));
							
							if(!$check_rd){
								// check employee timein
								if($check_employee_timein){
									$get_working_hours = $this->get_working_hours($company_id,$work_schedule_id,date("l",strtotime($current)));
									if($get_working_hours != FALSE) $total_hours += $get_working_hours;
								}
							}		
						}
					}

				// new basic pay
				$bp = $bp * $total_hours;	
			}
		}
		
		// end check period type
		
		// check payroll group
		$payroll_group = $this->get_payroll_group($company_id, $payroll_group_id);
		if ($payroll_group->pay_rate_type == 'By Day') {
			$bp = $bp * 26;
		}
		
		// workday
		$workday = $this->get_workday($company_id,$work_schedule_id);
		
		if(!$workday){
			$income['net'] = array(
				'basic_pay' => 0,
				'extra_pay' => 0
			);
			$income['regular'] = array(
				'basic_pay' => 0,
				'extra_pay' => 0
				// 'extra_pay' => - $absent_rate - $tardiness_rate - $undertime_rate
			);
			$income['basic'] = array(
				'basic_pay' => 0,
				'extra_pay' => 0
			);
			
			return $income;
		}
		
		// get total days per year
		// $w->total_working_days_per_year
		$day_per_year = $this->rank_total_working_days_in_a_year($employee_id);
		$day_per_year = ($day_per_year != FALSE) ? $day_per_year->total_working_days_in_a_year / 12 : 0 ;
		
		// get hourly rate
		$hour_rate = ($bp > 0 && $day_per_year > 0) ? $bp / $day_per_year / 8 : 0 ;
		
		// EMPLOYEE HOURLY RATE
		$emp_hourly_rate = 0;
		$period_type = $payroll->period_type;
		$pay_rate_type = $payroll->pay_rate_type;
		if($period_type == "Semi Monthly" && $pay_rate_type == "By Month"){
			$emp_hourly_rate = $this->new_hourly_rate($company_id,$employee_id,$payroll_group_id,$payroll,$work_schedule_id);
		}else if($period_type == "Semi Monthly" && $pay_rate_type == "By Day"){
			$n_payroll_period = $payroll;
			$emp_hourly_rate = $this->emp_basic_pay($company_id,$employee_id,$payroll) / 8;
		}else if($period_type == "Semi Monthly" && $pay_rate_type == "By Hour"){
			$n_payroll_period = $payroll;
			$emp_hourly_rate = $this->emp_basic_pay($company_id,$employee_id,$payroll);
		}
		
		$emp_hourly_rate = round($emp_hourly_rate,2);
		
		// get tardiness and undertime
		$current = $pfrom;
		$overtime_rate = 0;
		$night_diff_rate = 0;
		$holiday_rate = 0;
		$tardiness = 0;
		$undertime = 0;
		$absent	   = 0;
		
		// GET OVERTIME
		$start = $pfrom;
		$end = $pto;
 	 	$total_amount_overtime = 0;
		$night_diff_rate = 0;
		$ot_no_hours = 0;
		$overtime_dd1 = $this->new_get_employee_overtime($this->company_id,$employee_id,$start,$end);
		if ($overtime_dd1) {
			foreach($overtime_dd1 as $overtime_dd){
				$current = $overtime_dd->overtime_from;
				
				/* CHECK WORK SCHEDULE */
				$work_schedule_id = $this->work_schedule_id($this->company_id,$employee_id,$current);
				$weekday = date('l',strtotime($overtime_dd->overtime_from));
				$rest_day = $this->get_rest_day($this->company_id,$work_schedule_id->work_schedule_id,$weekday);
				$holiday = $this->get_holiday($this->company_id,NULL,$overtime_dd->overtime_from);
				
				$no_of_hours = $overtime_dd->no_of_hours;
				
				if ($holiday) {
					
					// REGULAR HOLIDAY (SPECIAL OR LEGAL)
					if(!$rest_day){
						$check_holiday_hours_type = $this->check_overtime_hours_type("Regular Day",$holiday->holiday_type,$this->company_id);
						
						if($check_holiday_hours_type != FALSE){
							$ot_no_hours += $overtime_dd->no_of_hours;
							if($check_holiday_hours_type->ot_rate > 0){
								$hourly_rate1 = $emp_hourly_rate * ($check_holiday_hours_type->pay_rate/100); // add holiday rate
								$overtime_rate += $overtime_dd->no_of_hours * ( $hourly_rate1 + ( $hourly_rate1 * ( $check_holiday_hours_type->ot_rate/100)));	
								$total_amount_overtime += $overtime_rate;	
							}
						}	
					}
					
					// REST DAY HOLIDAY (SPECIAL OR LEGAL)
					if($rest_day){
						$check_holiday_hours_type = $this->check_overtime_hours_type("Rest Day",$holiday->holiday_type,$this->company_id);
						
						if($check_holiday_hours_type != FALSE){
							$ot_no_hours += $overtime_dd->no_of_hours;
							if($check_holiday_hours_type->ot_rate > 0){
								$hourly_rate1 = $emp_hourly_rate * ($check_holiday_hours_type->pay_rate/100); // add holiday rate
								$overtime_rate += $overtime_dd->no_of_hours * ( $hourly_rate1 + ( $hourly_rate1 * ( $check_holiday_hours_type->ot_rate/100)));	
								$total_amount_overtime += $overtime_rate;
							}
						}	
					}
					
				} else {
					// get regular overtime rate
					if ($rest_day != FALSE) {
						$regular = $this->check_overtime_hours_type("Rest Day","",$this->company_id);
						if($regular != FALSE){
							if($regular->ot_rate > 0){
								$ot_no_hours += $overtime_dd->no_of_hours;
								$overtime_rate += $overtime_dd->no_of_hours * ($emp_hourly_rate * (( $regular->ot_rate/100)));
								$a = $overtime_dd->no_of_hours * ($emp_hourly_rate * (( $regular->ot_rate/100)));
								
								$total_amount_overtime += $overtime_rate;	
							}
						}
						
					} else {	// ordinary day
						$ot_no_hours += $overtime_dd->no_of_hours;
						$regular = $this->check_overtime_hours_type("Regular Day","",$this->company_id);
						if($regular != FALSE){
							if($regular->ot_rate > 0){
								$overtime_rate += $overtime_dd->no_of_hours  * (($emp_hourly_rate) + ($regular->ot_rate/100));
								$total_amount_overtime += $overtime_rate;
							}
						}
					}
				}
				
				// NIGHT DIFFERENTIAL OVERTIME
				$night_diff_rate += $this->get_night_differential_rate($this->company_id,date('Y-m-d',strtotime($overtime_dd->overtime_from)),
				date('Y-m-d H:i:s',strtotime($overtime_dd->overtime_from.' '.$overtime_dd->start_time)),
				date('Y-m-d H:i:s',strtotime($overtime_dd->overtime_to.' '.$overtime_dd->end_time)),
				$emp_hourly_rate,$payroll->payroll_group_id,$work_schedule_id->work_schedule_id);
			}
		}
		
		// night differential and holiday rate
		$timein = $this->new_get_timein($this->company_id,$employee_id,$pfrom,$pto);
		if ($timein) {
			foreach($timein as $row_timein_new){
				// current date
				$current = $row_timein_new->date;
				
				// get weekday
				$weekday = date('l',strtotime($current));
				
				// check rest day
				$rest_day = $this->get_rest_day($company_id,$work_schedule_id,$weekday); 
				
				// check holiday
				$holiday = $this->get_holiday($company_id,NULL,$current);
				
				if (!$row_timein_new->time_in || !$row_timein_new->time_out) {
					// do nothing
				}else{
					if(!$rest_day){
						$tardiness += $row_timein_new->tardiness_min;
						$undertime += $row_timein_new->undertime_min;
						
						// check night differential
						$night_diff_rate += $this->get_night_differential_rate($company_id,$current,
								$row_timein_new->time_in,$row_timein_new->time_out,$hour_rate,$payroll_group_id,$work_schedule_id);
							
						if ($holiday) {
							$holiday_rate += ($timein->total_hours * $hour_rate) * (($holiday->pay_rate - 100) / 100);
						}
					}
				}
			}
		}
		
		// absent rate
		while ($current <= $pto) {
			
			/* CHECK FLEXIBLE SCHEDULE */
			$check_work_schedule = $this->check_flexible_work_schedule($company_id,$work_schedule_id);
			
			if($check_work_schedule){
				// check if filed as absent
				$abs = $this->emp_absences_get_absences($company_id,$employee_id,$current);
				$absent += ($abs) ? $abs->absent : 0 ;
			}else{
				// get weekday
				$weekday = date('l',strtotime($current));
				
				// check rest day
				$rest_day = $this->get_rest_day($company_id,$work_schedule_id,$weekday); 
				
				// check holiday
				$holiday = $this->get_holiday($company_id,NULL,$current);
				
				if (!$rest_day) {
					$timein = $this->get_timein($this->company_id,$employee_id,$current);
					if ($timein) {
						if (!$timein->time_in || !$timein->time_out) {
							// incomplete attendance; consider as absent
							$absent += $this->get_working_hours($company_id,$work_schedule_id,$weekday);
							// do nothing
						}
					} elseif (!$timein && !$holiday) {		// is absent
						// initial leave count
						$leave = 0;
						
						// check if the employee filed a leave
						$pl = $this->check_employee_leave_application_for_income($company_id,$employee_id,$current);
						if ($pl) {
							foreach ($pl as $pl1) {
								$leave += $pl1->credited * 8;
							}
							//$paid_leave += $leave;
						}
						
						if ($pl) {
							//$leave = $pl->credited * 8;
						}
						
						$hours = $this->get_working_hours($company_id,$work_schedule_id,$weekday);
						
						$absent += $hours - $leave;
					}
				}
			}
			
			$current = date('Y-m-d',strtotime($current.' +1 day'));
		}	// end of while
		
		$tardiness = $tardiness / 60;
		$undertime = $undertime / 60;

		// get tardiness settings
		$ts = $this->get_tardiness_settings($company_id);
		$grace = $ts->tarmin / 60;

		// calculate tardiness
		$tardiness -= $grace;
		$tardiness = ($tardiness <= 0) ? 0 : $tardiness;
		
		$absent_rate = $absent * $hour_rate;
		$tardiness_rate = $tardiness * $hour_rate;
		$undertime_rate = $undertime * $hour_rate;
		
		// adjustment 1
		$employe = $this->carry_over_adjustment($company_id,$employee_id);
		$night_diff_settings = $this->get_night_differential($company_id);
		
		$carry_over_absences = $this->payroll_carry_over("absences",$employee_id,$this->company_id);
        $carry_over_tardiness = $this->payroll_carry_over("tardiness",$employee_id,$this->company_id);
        $carry_over_undertime = $this->payroll_carry_over("undertime",$employee_id,$this->company_id);
        $carry_over_field = $this->payroll_carry_over("night_differential",$employee_id,$this->company_id);
        $check_nd_set = $night_diff_settings->rate_type;
        $rate = $night_diff_settings->rate;
        
        if($check_nd_set == "hourly rate") {
			$carry_over_night_differential = (($rate / 100) * $hour_rate) * $carry_over_field;
        }else{
        	$carry_over_night_differential = $rate * $carry_over_field;
        }
            				
        
        $carry_over_earnings = $this->payroll_carry_over("earnings",$employee_id,$this->company_id);
        $carry_over_commission = $this->payroll_carry_over("commission",$employee_id,$this->company_id);
        $carry_over_allowance = $this->payroll_carry_over("allowance",$employee_id,$this->company_id);
        $carry_over_expense = $this->payroll_carry_over("expense",$employee_id,$this->company_id);
        $carry_over_loans = $this->payroll_carry_over("loans",$employee_id,$this->company_id);
            				
		// CHECK PREVIOUS PAYROLL PERIOD
		$check_previous_payroll_period = $this->check_previous_payroll_period($payroll_group_id,$company_id,$pto);
		if(!$check_previous_payroll_period){
			$previous_period_from = $payroll->period_from;
			$previous_period_to = $payroll->period_to;
		}else{
			$previous_period_from = $check_previous_payroll_period->cut_off_from;
			$previous_period_to = $check_previous_payroll_period->cut_off_to;
		}

		// carry over overtime
		$carry_over_ot = 0;
		
		if($check_previous_payroll_period != FALSE){
			$c_off_from = $previous_period_from;
			$c_off_to = $previous_period_to;
			
			/* PREVIOUS PAYROLL PERIOD FOR OVERTIME */
			$ot_no_hours = 0;
			$check_previous_overtime = $this->check_previous_overtime($pfrom,$pto,$previous_period_from,$previous_period_to,$employee_id);
			if($check_previous_overtime != FALSE){
				// $payroll = $this->get_payroll_period($this->company_id);
				foreach($check_previous_overtime as $overtime_dd){
					/* CHECK WORK SCHEDULE */
					$work_schedule_id = $this->work_schedule_id($company_id,$employee_id,$overtime_dd->overtime_from);
					
					// EMPLOYEE HOURLY RATE
					$emp_hourly_rate = 0;
					$period_type = $payroll->period_type;
					$pay_rate_type = $payroll->pay_rate_type;
					if($period_type == "Semi Monthly" && $pay_rate_type == "By Month"){
						if($work_schedule_id != FALSE){
							/* EMPLOYEE WORK SCHEDULE */
							$emp_hourly_rate = $this->new_hourly_rate($company_id,$employee_id,$payroll_group_id,$payroll,$work_schedule_id);
						}else{
							/* EMPLOYEE PAYROLL INFORMATION */
							$emp_hourly_rate = $this->new_hourly_rate($company_id,$employee_id,$payroll_group_id,$payroll);
						}
					}else if($period_type == "Semi Monthly" && $pay_rate_type == "By Day"){
						$n_payroll_period = $payroll;
						$emp_hourly_rate = $this->emp_basic_pay($company_id,$employee_id,$payroll) / 8;
					}else if($period_type == "Semi Monthly" && $pay_rate_type == "By Hour"){
						$n_payroll_period = $payroll;
						$emp_hourly_rate = $this->emp_basic_pay($company_id,$employee_id,$payroll);
					}
					
					$emp_hourly_rate = round($emp_hourly_rate,2);
					
					$weekday = date('l',strtotime($overtime_dd->overtime_from));
					$rest_day = $this->get_rest_day($company_id,$work_schedule_id,$weekday);
					
					// check if holiday
					$holiday = $this->get_holiday($company_id,NULL,$overtime_dd->overtime_from);
					
					if ($holiday) {
						$ot_no_hours += $overtime_dd->no_of_hours;
						$hourly_rate1 = $emp_hourly_rate * ($holiday->pay_rate/100);
						$carry_over_ot += $overtime_dd->no_of_hours * ( $hourly_rate1 + ( $hourly_rate1 * ( $holiday->ot_rate/100)));
					} else {
						// get regular overtime rate
						if ($rest_day!=FALSE) {
							$regular = $this->get_overtime_rate($company_id,'Rest Day');
							$ot_no_hours += $overtime_dd->no_of_hours;
							$hourly_rate1 = $emp_hourly_rate * ($regular->pay_rate/100);
							$carry_over_ot += $overtime_dd->no_of_hours * ( $hourly_rate1 + ( $hourly_rate1 * ( $regular->ot_rate/100)));
							$a = $overtime_dd->no_of_hours * ( $hourly_rate1 + ( $hourly_rate1 * ( $regular->ot_rate/100)));
						} else {	// ordinary day
							$ot_no_hours += $overtime_dd->no_of_hours;
							$regular = $this->get_overtime_rate($company_id,'Regular Day');
							$carry_over_ot += $overtime_dd->no_of_hours  * (($emp_hourly_rate) + ($emp_hourly_rate) * ($regular->ot_rate/100));
						}
					}
				}
			}				
			
		}
		
		// carry over leave type
		$carry_over_leave_type = 0;
		
		// NEW PAID LEAVE FOR PREVIOUS PAYROLL PERIOD
		$pp_current = $previous_period_from;
		while ($pp_current <= $previous_period_to) {
			
			/* CHECK WORK SCHEDULE */
			$work_schedule_id = $this->work_schedule_id($company_id,$employee_id,$pp_current);
			
			$check_leave_application = $this->check_leave_appliction($employee_id,$company_id);
			
			if($check_leave_application != FALSE){
				$flag_between = 0;
				foreach($check_leave_application as $row_cla){
					$ds = date("Y-m-d",strtotime($row_cla->date_start));
					$de = date("Y-m-d",strtotime($row_cla->date_end));
					$approved_date = $row_cla->approval_date;
					$leave_application_status = $row_cla->leave_application_status;
					
					if(
						strtotime(date("Y-m-d",strtotime($period_from))) <= strtotime(date("Y-m-d",strtotime($approved_date)))
						&& strtotime(date("Y-m-d",strtotime($approved_date))) <= strtotime(date("Y-m-d",strtotime($period_to)))
					){
						$credited = $row_cla->credited;
						$non_credited = $row_cla->non_credited;
						$remaining_credits = $row_cla->remaining_credits;
						$employee_leaves_application_id = $row_cla->employee_leaves_application_id;
						
						$check_day = floor(((strtotime($row_cla->date_end) - strtotime($row_cla->date_start)) / 86400)) + 1;
						$total_days = floor(((strtotime($row_cla->date_end) - strtotime($row_cla->date_start)) / 86400)) + 1;
						
						if($check_day > 1){
							$c = 0;
							$cd = 0;
							$total_holidays = 0;
							
							for($check_day;$check_day > $cd;$cd++){
								$a = date("l",strtotime($ds." +".$cd." day"));
								$date_holiday = date("Y-m-d",strtotime($ds." +".$cd." day"));
								
								$rest_day = $this->get_rest_day($company_id,$work_schedule_id,$a);
								
								$holiday = $this->get_holiday($company_id,NULL,$date_holiday);
								
								if ($rest_day || $holiday) $total_holidays++;
							}
							
							$check_day = $check_day - $total_holidays;
							
							for($total_days; $total_days > $c;$c++){
								
								$a = date("l",strtotime($ds." +".$c." day"));
								$date_holiday = date("Y-m-d",strtotime($ds." +".$c." day"));
								
								$rest_day = $this->get_rest_day($company_id,$work_schedule_id,$a);
								
								$holiday = $this->get_holiday($company_id,NULL,$date_holiday);
								
								if (!$rest_day && !$holiday) {
									if($date_holiday == date("Y-m-d",strtotime($pp_current))){
										$carry_over_leave_type += ($credited / $check_day);
									}
								}
							}
						}elseif(date("Y-m-d",strtotime($pp_current)) == $ds){
							if($credited > 0 && $non_credited == 0){
								$carry_over_leave_type += $credited;
							}
						}	
					}
				}
			}
			
			$pp_current = date('Y-m-d',strtotime($pp_current.' +1 day'));
		}
		
        $carry_over_leave_type = ($carry_over_leave_type * 8) * $emp_hourly_rate;

		$adj_ded_1 = ($carry_over_absences + $carry_over_tardiness + $carry_over_undertime) * $hour_rate;
		$adj_ded_2 = ($carry_over_ot + $carry_over_leave_type + $carry_over_night_differential) * $hour_rate;
			              	
        $total_carry_over = $adj_ded_2 + $carry_over_loans + $carry_over_earnings + $carry_over_commission + $carry_over_allowance + $carry_over_expense - $adj_ded_1;
            			
        // end adjustment 1
        
        // check allowance if taxable
		$allowances = $this->get_employee_fixed_allowance($company_id,$employee_id);
		$allowance = 0;
		if ($allowances) {
			foreach ($allowances as $a) {
				if ($a->taxable) {
					$allowance += $a->amount;
				}
			}
		}
		
		$absent_rate = ($check_period_type->pay_rate_type == "By Hour") ? 0 : $absent_rate ;
		
		// PAYROLL CALENDAR
		$q1 = $this->get_payroll_calendar($company_id,$period->payroll_period,$payroll_group_id);
		
		// DE MINIMIS SETTINGS
		$de_minimis_less_to_taxable_income = 0; // Less to Taxable Income
		$de_minimis_remaining_non_taxable_income = 0; // Remaining Non Taxable Income
		$de_minimis_settings = $this->de_minimis_settings($company_id);
		if($de_minimis_settings != FALSE){
			foreach($de_minimis_settings as $row_dms){
				
				// default amount
				$nt_count = 0;
				
				// check employee id on de minimis table
				$check_employee_id_de_minimis = $this->check_employee_id_de_minimis($row_dms->deminimis_id,$employee_id,$company_id);
				if($check_employee_id_de_minimis != FALSE){
					
					// variable fields
					$dm_plan = $row_dms->plan;
					$dm_rules = $row_dms->rules;
					$dm_max_non_taxable_amount = $row_dms->max_non_taxable_amount;
					$dm_tax_rate = $row_dms->tax_rate;
					$dm_amount = $row_dms->amount;
					$dm_schedule_date = $row_dms->schedule_date;
					$dm_schedule = $row_dms->schedule;
					
					if($dm_plan == "Monthly"){
						// check de minimis on payroll payslip where payroll period is current
						$check_de_minimis_payslip = $this->check_de_minimis_payslip($employee_id,$company_id);
						if(!$check_de_minimis_payslip){
							if (($dm_schedule == 'first payroll' || $dm_schedule == 'per payroll') && 
								$q1['period'] == 1) {
								
								$nt_count = $dm_amount - $dm_max_non_taxable_amount;
								if($nt_count > 0){
									$de_minimis_with_tax_rate = ($nt_count * ($dm_tax_rate / 100));
									$nt_count = ($nt_count - $de_minimis_with_tax_rate) + $dm_max_non_taxable_amount;
								}else{
									$nt_count = 0;
								}
									
							} elseif (($dm_schedule == 'second payroll' || $dm_schedule == 'per payroll') && 
								$q1['period'] == 2) {
								
								$nt_count = $dm_amount - $dm_max_non_taxable_amount;
								if($nt_count > 0){
									$de_minimis_with_tax_rate = ($nt_count * ($dm_tax_rate / 100));
									$nt_count = ($nt_count - $de_minimis_with_tax_rate) + $dm_max_non_taxable_amount;
								}else{
									$nt_count = 0;
								}
									
							}
						}
					}elseif($dm_plan == "One-time"){
						if(strtotime($pfrom) <= strtotime($dm_schedule_date) && strtotime($dm_schedule_date) <= strtotime($pto)){
							$nt_count = $dm_amount - $dm_max_non_taxable_amount;
							if($nt_count > 0){
								$de_minimis_with_tax_rate = ($nt_count * ($dm_tax_rate / 100));
								$nt_count = ($nt_count - $de_minimis_with_tax_rate) + $dm_max_non_taxable_amount;
							}else{
								$nt_count = 0;
							}
						}
					}
					
					// check de minimis rules
					if($dm_rules == "Less to Taxable Income"){
						$de_minimis_less_to_taxable_income += $nt_count;
					} else {
						$remaining_non_taxable_income = $dm_amount - $dm_max_non_taxable_amount;
						$de_minimis_remaining_non_taxable_income += ($remaining_non_taxable_income > 0) ? $remaining_non_taxable_income : 0 ; 
					}
					
				}
			}
		}
		
		// FIXED ALLOWANCES
		$allowance_max_non_taxable_income = 0;
		$total_fixed_allowances = 0; // taxable
		$total_fixed_allowances_nt = 0; // non taxable
		$remaining_taxable_allowances = 0; // remaining taxable allowances
		$allowances_td = "";
		$allowances_settings = $this->allowances_settings($this->company_id);
		if($allowances_settings != FALSE){
			foreach($allowances_settings as $row_allowances_settings){
				$fix_allowance = 0;
				$get_fixed_allowances = $this->get_fixed_allowances($employee_id,$company_id,$row_allowances_settings->allowance_settings_id);
				if($get_fixed_allowances != FALSE){
					foreach($get_fixed_allowances as $row_fa){
						$fixed_allowance_amount = $row_fa->amount;
						$pay_out_schedule = $row_fa->pay_out_schedule;
						$check_taxable = $row_fa->taxable;
						$maximum_non_taxable_amount = $row_fa->maximum_non_taxable_amount;
						
						// taxable
						if($check_taxable == "Yes"){
							if (($pay_out_schedule == 'first payroll' || $pay_out_schedule == 'every payroll') && 
								$q1['period'] == 1) {
								$taxable_allowances = $fixed_allowance_amount - $maximum_non_taxable_amount;
								$remaining_taxable_allowances = ($taxable_allowances > 0) ? $taxable_allowances : 0 ;
							} elseif (($pay_out_schedule == 'second payroll' || $pay_out_schedule == 'every payroll') && 
								$q1['period'] == 2) {
								$taxable_allowances = $fixed_allowance_amount - $maximum_non_taxable_amount;
								$remaining_taxable_allowances = ($taxable_allowances > 0) ? $taxable_allowances : 0 ;
							}
						}
					}
				}
			}
		}					
		
		$extra_pay_net =  $overtime_rate + $night_diff_rate + $holiday_rate - $absent_rate - $tardiness_rate - $undertime_rate + $total_carry_over + $allowance - $de_minimis_less_to_taxable_income + $de_minimis_remaining_non_taxable_income + $remaining_taxable_allowances;
		$extra_pay_net = ($extra_pay_net < 0) ? 0 : $extra_pay_net ;
		
		$extra_pay_regular = $absent_rate - $tardiness_rate - $undertime_rate - $de_minimis_less_to_taxable_income + $de_minimis_remaining_non_taxable_income + $remaining_taxable_allowances;
		$extra_pay_regular = ($extra_pay_regular < 0) ? 0 : $extra_pay_regular ;
		
		$income['net'] = array(
			'basic_pay' => $bp,
			'extra_pay' => $extra_pay_net
		);
		$income['regular'] = array(
			'basic_pay' => $bp,
			'extra_pay' => $extra_pay_regular
			// 'extra_pay' => - $absent_rate - $tardiness_rate - $undertime_rate
		);
		$income['basic'] = array(
			'basic_pay' => $bp,
			'extra_pay' => 0
		);
		/* print "
			OT: {$overtime_rate} <br>
			ND: {$night_diff_rate} <br>
			Holiday Rate: {$holiday_rate} <br>
			Absent: {$absent_rate} <br>
			Tardi: {$tardiness_rate} <br>
			Undertime: {$undertime_rate} <br>
			CarryOver: {$total_carry_over} <br>
			Allowance: {$allowance} <br>
		"; */
		return $income;
	}
	
	/**
	 * get the payroll period
	 * @param int $company_id
	 * @param int $payroll_group_id
	 */
	public function get_payroll_calendar($company_id,$payroll_date,$payroll_group_id)
	{
		$where = array(
			'pc.company_id' 	   	 => $company_id,
			'pc.first_payroll_date' => $payroll_date,
			'pg.payroll_group_id'	 => $payroll_group_id,
			'pc.status'	 	   	 => 'Active'
		);
		$this->db->where($where);
		$this->db->join("payroll_group AS pg","pg.period_type = pc.pay_schedule","LEFT");
		$q = $this->db->get('payroll_calendar AS pc');
		$result = $q->row();
		if ($result) {
			
			$val['period'] = 0;
			
			// IF pay_schedule = Fortnightly
			if($result->pay_schedule == "Fortnightly"){
				$val['period'] = $result->period_count;
			}elseif($result->pay_schedule == "Semi Monthly"){
				$day = date('j',strtotime($result->first_payroll_date));
					
				if ($result->second_monthly == -1) {	// End of month
					$start = $result->first_semi_monthly;
					$end   = date('t',strtotime($day));
				} else {
					$start = $result->first_semi_monthly;
					$end   = $result->second_monthly;
				}
					
				if ($start < $day && $day <= $end) {
					$val['period'] = 2;
				} else {
					$val['period'] = 1;
				}	
			}
			
			return $val;
		}
	}
	
	/**
	 * Get deduction uniform schedule
	 * @param int $company_id
	 */
	public function get_deduction_uniform_schedule($company_id)
	{
		$this->edb->where('company_id',$company_id);
		$q = $this->edb->get('deduction_uniform_schedule');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get deduction different schedule
	 * @param array $where
	 */
	public function get_deduction_diff_schedule($where)
	{
		$this->edb->where($where);
		$q = $this->edb->get('deduction_diff_schedule');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Employee Advance Payment
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function employee_advance_payment($emp_id,$company_id){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$this->db->where("deducted_period IS NULL");
		$q = $this->db->get("payroll_carry_over_advance_payment");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Advance Payment
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $payroll_period
	 */
	public function employee_advance_payment_amount($emp_id,$company_id,$payroll_period){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active",
			"deducted_period"=>$payroll_period
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_advance_payment");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Calculate deduction for tax
	 * @param string $type
	 * @param int $status
	 * @param decimal $salary_range
	 */
	public function get_deduction_income_tax($type,$status,$salary_range)
	{
		if (!$type) {
			return false;
		}
		
		$valid_type = array('Daily','Semi Monthly','Monthly','Annual','Fortnightly');
		$tax_status = array(
			-1 => 'Z',
			0  => 'S',
			1  => 'S-1',
			2  => 'S-2',
			3  => 'S-3',
			4  => 'S-4'
		);
		
		if (!in_array($type, $valid_type)) {
			show_error('Invalid tax type');
		}
		
		if ($type == 'Annual') {
			$init_exemption = 50000;
			if ($status > 0 && $status < 5) {
				$add_exemption = $status * 25000;
			} else {
				$add_exemption = 0;
			}
			
			#print "salary rage {$salary_range} <br>";
			
			$salary_range = $salary_range - $init_exemption - $add_exemption;
			
			if ($salary_range > 0) { 
				$this->db->where('"'.$salary_range.'" BETWEEN range_of_tax_from AND range_of_tax_to',NULL,FALSE);
				$q = $this->db->get('withholding_tax_annual');
				$result = $q->row();
				$q->free_result();
				$cal1 = $salary_range - $result->range_of_tax_from;
				
				$cal2 = ($cal1 * ($result->additional_tax / 100)) + $result->exemption;
				
				#print "init_exemption = {$init_exemption} <br>";
				#print "add_exemption = {$add_exemption} <br>";
				#print "salary rage = {$salary_range} <br>";
				#print "range_of_tax_from = {$result->range_of_tax_from} <br>";
				#print "cal1 = {$cal1} <br>";
				#print "cal2 = {$cal2} <br>";
				
				/* print "Range: {$result->range_of_tax_from} <br>
					Call2: {$cal2} <br>
				"; */
				return $cal2;
			} else {
				return 0;
			}
		} else {
			
			#if(in_array($status, $tax_status)){
			
			// check tax status
			$ts = "";
			if(intval($status) == -1){
				$ts = 'Z';
			}else if(intval($status) == 0){
				$ts = 'S';
			}else if(intval($status) == 1){
				$ts = 'S-1';
			}else if(intval($status) == 2){
				$ts = 'S-2';
			}else if(intval($status) == 3){
				$ts = 'S-3';
			}else if(intval($status) == 4){
				$ts = 'S-4';
			}
			
			// status value
			if($ts != ""){
			
				$type = ($type == "Fortnightly") ? "Semi Monthly" : $type ;
				
				$where = array(
					// 'tax_name' => $tax_status[$status],
					'tax_name' => $ts,
					'tax_type' => $type
				);
				$this->db->where($where);
				$q = $this->db->get('withholding_tax_status');
				$result = $q->row();
				$q->free_result();
				
				if($result){
					// getting the tax range
					if ($result->amount_excess1 <= $salary_range && $result->amount_excess2 > $salary_range) {
						$range = $result->amount_excess1;
						$section = 'tax1';
					} elseif ($result->amount_excess2 <= $salary_range && $result->amount_excess3 > $salary_range) {
						$range = $result->amount_excess2;
						$section = 'tax2';
					} elseif ($result->amount_excess3 <= $salary_range && $result->amount_excess4 > $salary_range) {
						$range = $result->amount_excess3;
						$section = 'tax3';
					} elseif ($result->amount_excess4 <= $salary_range && $result->amount_excess5 > $salary_range) {
						$range = $result->amount_excess4;
						$section = 'tax4';
					} elseif ($result->amount_excess5 <= $salary_range && $result->amount_excess6 > $salary_range) {
						$range = $result->amount_excess5;
						$section = 'tax5';
					} elseif ($result->amount_excess6 <= $salary_range && $result->amount_excess7 > $salary_range) {
						$range = $result->amount_excess6;
						$section = 'tax6';
					} elseif ($result->amount_excess7 <= $salary_range && $result->amount_excess8 > $salary_range) {
						$range = $result->amount_excess7;
						$section = 'tax7';
					} elseif ($result->amount_excess8 <= $salary_range) {
						$range = $result->amount_excess8;
						$section = 'tax8';
					} else {
						$range = 1;
						$section = 'tax1';
					}
						
					// print "Range = {$range}";
					// exit;
						
					$where = array(
							'tax_name' => 'Exemption',
							'tax_type' => $type
					);
					$this->db->where($where);
					$q = $this->db->get('withholding_tax');
					$exemption = $q->row_array();
					$q->free_result();
					$where = array(
							'tax_name' => 'Status',
							'tax_type' => $type
					);
					$this->db->where($where);
					$q = $this->db->get('withholding_tax');
					$status = $q->row_array();
					$q->free_result();
					$cal1 = $salary_range - $range;
					$cal2 = ($cal1 * ($status[$section] / 100)) + $exemption[$section];
				}else{
					$cal2 = 0;
				}
				
				return $cal2;
			}else{
				return 0;
			}
			
		}
	}
	
	/**
	 * Calculate deduction for tax
	 * @param string $type
	 * @param int $status
	 * @param decimal $salary_range
	 */
	public function fortnightly_get_deduction_income_tax($type,$status,$salary_range,$tax_period)
	{
		if (!$type) {
			return false;
		}
	
		$valid_type = array('Daily','Semi Monthly','Monthly','Annual','Fortnightly');
		$tax_status = array(
				-1 => 'Z',
				0  => 'S',
				1  => 'S-1',
				2  => 'S-2',
				3  => 'S-3',
				4  => 'S-4'
		);
	
		if (!in_array($type, $valid_type)) {
			show_error('Invalid tax type');
		}
	
		if ($type == 'Annual') {
			$init_exemption = 50000;
			if ($status > 0 && $status < 5) {
				$add_exemption = $status * 25000;
			} else {
				$add_exemption = 0;
			}
				
			#print "salary rage {$salary_range} <br>";
				
			$salary_range = $salary_range - $init_exemption - $add_exemption;
				
			if ($salary_range > 0) {
				$this->db->where('"'.$salary_range.'" BETWEEN range_of_tax_from AND range_of_tax_to',NULL,FALSE);
				$q = $this->db->get('withholding_tax_annual');
				$result = $q->row();
				$q->free_result();
				$cal1 = $salary_range - $result->range_of_tax_from;
	
				$cal2 = ($cal1 * ($result->additional_tax / 100)) + $result->exemption;
	
				#print "init_exemption = {$init_exemption} <br>";
				#print "add_exemption = {$add_exemption} <br>";
				#print "salary rage = {$salary_range} <br>";
				#print "range_of_tax_from = {$result->range_of_tax_from} <br>";
				#print "cal1 = {$cal1} <br>";
				#print "cal2 = {$cal2} <br>";
	
				/* print "Range: {$result->range_of_tax_from} <br>
				 Call2: {$cal2} <br>
					"; */
				return $cal2;
			} else {
				return 0;
			}
		} else {
				
			#if(in_array($status, $tax_status)){
				
			// check tax status
			$ts = "";
			if(intval($status) == -1){
				$ts = 'Z';
			}else if(intval($status) == 0){
				$ts = 'S';
			}else if(intval($status) == 1){
				$ts = 'S-1';
			}else if(intval($status) == 2){
				$ts = 'S-2';
			}else if(intval($status) == 3){
				$ts = 'S-3';
			}else if(intval($status) == 4){
				$ts = 'S-4';
			}
				
			// status value
			if($ts != ""){
				
				$type = ($type == "Fortnightly" && ($tax_period == "First Payroll" || $tax_period == "Second Payroll")) ? "Monthly" : $type ;
				
				$where = array(
					// 'tax_name' => $tax_status[$status],
					'tax_name' => $ts,
					'tax_type' => $type
				);
				$this->db->where($where);
				$q = $this->db->get('withholding_tax_status');
				$result = $q->row();
				
				$q->free_result();
	
				if($result){
					// getting the tax range
					if ($result->amount_excess1 <= $salary_range && $result->amount_excess2 > $salary_range) {
						$range = $result->amount_excess1;
						$section = 'tax1';
					} elseif ($result->amount_excess2 <= $salary_range && $result->amount_excess3 > $salary_range) {
						$range = $result->amount_excess2;
						$section = 'tax2';
					} elseif ($result->amount_excess3 <= $salary_range && $result->amount_excess4 > $salary_range) {
						$range = $result->amount_excess3;
						$section = 'tax3';
					} elseif ($result->amount_excess4 <= $salary_range && $result->amount_excess5 > $salary_range) {
						$range = $result->amount_excess4;
						$section = 'tax4';
					} elseif ($result->amount_excess5 <= $salary_range && $result->amount_excess6 > $salary_range) {
						$range = $result->amount_excess5;
						$section = 'tax5';
					} elseif ($result->amount_excess6 <= $salary_range && $result->amount_excess7 > $salary_range) {
						$range = $result->amount_excess6;
						$section = 'tax6';
					} elseif ($result->amount_excess7 <= $salary_range && $result->amount_excess8 > $salary_range) {
						$range = $result->amount_excess7;
						$section = 'tax7';
					} elseif ($result->amount_excess8 <= $salary_range) {
						$range = $result->amount_excess8;
						$section = 'tax8';
					} else {
						$range = 1;
						$section = 'tax1';
					}
	
					// print "Range = {$range}";
					// exit;
	
					$where = array(
							'tax_name' => 'Exemption',
							'tax_type' => $type
					);
					$this->db->where($where);
					$q = $this->db->get('withholding_tax');
					$exemption = $q->row_array();
					$q->free_result();
					$where = array(
							'tax_name' => 'Status',
							'tax_type' => $type
					);
					$this->db->where($where);
					$q = $this->db->get('withholding_tax');
					$status = $q->row_array();
					$q->free_result();
					$cal1 = $salary_range - $range;
					$cal2 = ($cal1 * ($status[$section] / 100)) + $exemption[$section];
				}else{
					$cal2 = 0;
				}
	
				return $cal2;
			}else{
				return 0;
			}
				
		}
	}
	
	/**
	 * Employee Holiday Premium
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $per_page
	 * @param unknown_type $page
	 * @param unknown_type $sort_by
	 * @param unknown_type $q
	 */
	public function employee_holiday_premium($payroll_group_id,$company_id,$per_page, $page,$sort_by, $q){
		$konsum_key = konsum_key();
		
		$w = array(
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		
		if($q !==""){
			$this->db->where("
				(convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
		}
		
		$this->edb->join("employee AS e","e.emp_id = fhp.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("holiday AS h","h.holiday_id = fhp.holiday_id","LEFT");
		//$this->edb->join("hours_type AS ht","ht.hour_type_id = h.hour_type_id","LEFT");
		
		if($sort_by != ""){
			if($sort_by == "e.first_name"){
				$this->edb->order_by($sort_by,"ASC");
			}else{
				$this->db->order_by($sort_by,"ASC");
			}
		}else{
			$this->edb->order_by("e.first_name","ASC");
		}
		
		$q = $this->edb->get("flexible_holiday_premium AS fhp",$per_page, $page);
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Holiday Premium Counter
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $sort_by
	 * @param unknown_type $q
	 */
	public function employee_holiday_premium_counter($payroll_group_id,$company_id,$sort_by, $q){
		$konsum_key = konsum_key();
		
		$s = array(
			"COUNT(fhp.emp_id) AS total"
		);
		$this->db->select($s);
		
		$w = array(
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		
		if($q !==""){
			$this->db->where("
				(convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
		}
		
		$this->edb->join("employee AS e","e.emp_id = fhp.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		
		if($sort_by != ""){
			if($sort_by == "e.first_name"){
				$this->edb->order_by($sort_by,"ASC");
			}else{
				$this->db->order_by($sort_by,"ASC");
			}
		}else{
			$this->edb->order_by("e.first_name","ASC");
		}
		
		$q = $this->edb->get("flexible_holiday_premium AS fhp");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Check Flexible Holiday Premium
	 * Enter description here ...
	 * @param unknown_type $id
	 * @param unknown_type $company_id
	 */
	public function check_flexible_holiday_premium_id($id,$company_id){
		$w = array(
			"fhp.flexible_holiday_premium_id"=>$id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		$this->edb->join("employee AS e","e.emp_id = fhp.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("holiday AS h","h.holiday_id = fhp.holiday_id","LEFT");
		$this->edb->join("hours_type AS ht","ht.hour_type_id = h.hour_type_id","LEFT");
		$q = $this->edb->get("flexible_holiday_premium AS fhp");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Download Carry Over
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 */
	public function download_carry_over_employee($payroll_group_id,$company_id){
		$konsum_key = konsum_key();
		
		$w = array(
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		
		$q = $this->edb->get("employee AS e");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Employee List
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 */
	public function get_employee_flexible_schedule($payroll_group_id,$company_id){
		$w = array(
			"epi.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"a.user_type_id"=>"5",
			"ws.work_type_name"=>"Flexible Hours",
			"e.status"=>"Active"
		);
		$this->db->where($w);
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$this->edb->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
		$q = $this->edb->get("employee AS e");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	

	/**
	 * Employee List
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $per_page
	 * @param unknown_type $page
	 * @param unknown_type $sort_by
	 * @param unknown_type $q = $search
	 */
	public function payroll_run_employee($payroll_group_id,$company_id,$per_page, $page, $sort_by, $q, $sort_param=NULL){
		$konsum_key = konsum_key();
		
		// GET EMPLOYEE EXCLUDED LISTS
		// $employee_excluded_lists = $this->employee_excluded_lists($company_id);
		$employee_excluded_lists = $this->employee_excluded_lists($company_id, $sort_param);
		
		// GET EMPLOYEE NGA MANA PA RUN SA HR
		$check_employee_already_run = $this->check_employee_already_run($sort_param);
		
		/* $s = array(
			"*","e.emp_id AS emp_id"
		);
		$this->edb->select($s); */
		
		$s = array(
			"e.emp_id",
			"a.account_id",
			"epl.rank_id",
			"epl.payroll_group_id",
			"epl.employment_type",
			"epl.tax_status",
			"a.profile_image",
			"d.department_name",
			"epl.nightshift_differential_rule_name"
		);
		$this->db->select($s);
		
		// encrypted
		$s_en = array(
			"a.payroll_cloud_id",
			"e.first_name","e.last_name"
		);
		$this->edb->select($s_en);
		
		if($q !==""){
			$this->db->where("
				epl.payroll_group_id = {$payroll_group_id} 
				AND e.company_id = {$company_id} 
				AND e.status = 'Active' 
				AND epl.employee_status = 'Active' 
				AND (convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
			
			// AND epl.tax_status >= -1
		}
		
		$w = array(
			// "epl.tax_status >= " => "-1",
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active",
			"epl.employee_status"=>"Active"
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
		
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epl.payroll_group_id","LEFT");
		
		if($sort_by != "" && $sort_param != ""){
			if($sort_by == "e.last_name" || $sort_by == "a.payroll_cloud_id"){
				$this->edb->order_by($sort_by,$sort_param["sort"]);
			}else{
				$this->db->order_by($sort_by,$sort_param["sort"]);
			}
		}else if($sort_by != "" && $sort_param == ""){
			if($sort_by == "e.last_name" || $sort_by == "a.payroll_cloud_id"){
				$this->edb->order_by($sort_by,"ASC");
			}else{
				$this->db->order_by($sort_by,"ASC");
			}
		}else{
			$this->edb->order_by("e.last_name","ASC");
		}
		
		$q = $this->edb->get("employee AS e",$per_page, $page);
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Custom Employee List
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $per_page
	 * @param unknown_type $page
	 * @param unknown_type $sort_by
	 * @param unknown_type $q = $search
	 */
	public function custom_payroll_run_employee($draft_pay_run_id,$company_id,$per_page, $page, $sort_by, $q, $sort_param=NULL){
		
		$konsum_key = konsum_key();
	
		// GET EMPLOYEE EXCLUDED LISTS
		// $employee_excluded_lists = $this->employee_excluded_lists($company_id);
		$employee_excluded_lists = $this->employee_excluded_lists($company_id, $sort_param);
		
		// GET EMPLOYEE NGA MANA PA RUN SA HR
		$check_employee_already_run = $this->check_employee_already_run($sort_param);
		
		$s = array(
			"*",
			"e.emp_id AS emp_id",
			"epl.rank_id AS rank_id",
			"epl.employment_type AS employment_type",
			"epl.payroll_group_id AS payroll_group_id"
		);
		$this->edb->select($s);
	
		if($q !==""){
			$this->db->where("
					prc.draft_pay_run_id = {$draft_pay_run_id} 
					AND prc.company_id = {$company_id} 
					AND prc.status = 'Active' 
					AND epl.employee_status = 'Active'
					AND (convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
					OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
			
			// AND epl.tax_status >= -1
		}
	
		$w = array(
			// "epl.tax_status >= " => "-1",
			"prc.draft_pay_run_id"=>$draft_pay_run_id,
			"prc.company_id"=>$company_id,
			"prc.status"=>"Active",
			"e.status"=>"Active",
			"epl.employee_status"=>"Active"
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
		
		$this->edb->join("employee AS e","e.emp_id = prc.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epl.payroll_group_id","LEFT");
		
		if($sort_by != "" && $sort_param != ""){
			if($sort_by == "e.last_name" || $sort_by == "a.payroll_cloud_id"){
				$this->edb->order_by($sort_by,$sort_param["sort"]);
			}else{
				$this->db->order_by($sort_by,$sort_param["sort"]);
			}
		}else if($sort_by != "" && $sort_param == ""){
			if($sort_by == "e.last_name" || $sort_by == "a.payroll_cloud_id"){
				$this->edb->order_by($sort_by,"ASC");
			}else{
				$this->db->order_by($sort_by,"ASC");
			}
		}else{
			$this->edb->order_by("e.last_name","ASC");
		}
	
		$q = $this->edb->get("payroll_run_custom AS prc",$per_page, $page);
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Payroll Run Employee Counter
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $q
	 * @param unknown_type $sort_param // bag.o ni sya para trapping
	 */
	public function payroll_run_employee_counter($payroll_group_id,$company_id,$q,$sort_param=NULL){
		
		$konsum_key = konsum_key();
		
		// GET EMPLOYEE EXCLUDED LISTS
		// $employee_excluded_lists = $this->employee_excluded_lists($company_id);
		$employee_excluded_lists = $this->employee_excluded_lists($company_id, $sort_param);
		
		// GET EMPLOYEE NGA MANA PA RUN SA HR
		$check_employee_already_run = $this->check_employee_already_run($sort_param);
		
		$s = array(
			"COUNT(e.emp_id) AS total"
		);
		$this->db->select($s);
		
		if($q !==""){
			$this->db->where("
				epl.payroll_group_id = {$payroll_group_id} 
				AND e.company_id = {$company_id} 
				AND e.status = 'Active' 
				AND epl.employee_status = 'Active' 
				AND (convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
			
			// AND epl.tax_status >= -1
		}
		
		$w = array(
			// "epl.tax_status >= " => "-1",
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active",
			"epl.employee_status"=>"Active"
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
		
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		
		$q = $this->edb->get("employee AS e");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;
		
	}
	
	/**
	 * Payroll Run Employee Counter
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $q
	 * @param unknown_type $sort_param // bag.o rani sya para trapping
	 */
	public function custom_payroll_run_employee_counter($draft_pay_run_id,$company_id,$q,$sort_param=NULL){
		
		$konsum_key = konsum_key();
		
		// GET EMPLOYEE EXCLUDED LISTS
		// $employee_excluded_lists = $this->employee_excluded_lists($company_id);
		$employee_excluded_lists = $this->employee_excluded_lists($company_id, $sort_param);
		
		// GET EMPLOYEE NGA MANA PA RUN SA HR
		$check_employee_already_run = $this->check_employee_already_run($sort_param);
		
		$s = array(
				"COUNT(e.emp_id) AS total"
		);
		$this->db->select($s);
	
		if($q !==""){
			$this->db->where("
					prc.draft_pay_run_id = {$draft_pay_run_id} 
					AND e.company_id = {$company_id} 
					AND e.status = 'Active' 
					AND epl.employee_status = 'Active' 
					AND (convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
					OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
			
			// AND epl.tax_status >= -1
		}
	
		$w = array(
			// "epl.tax_status >= " => "-1",
			"prc.draft_pay_run_id"=>$draft_pay_run_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active",
			"epl.employee_status"=>"Active"
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
		
		$this->edb->join("employee AS e","e.emp_id = prc.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		
		$q = $this->edb->get("payroll_run_custom AS prc");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Employee Loans
	 * @param unknown_type $comp_id
	 */
	public function employee_loans($comp_id){
		$w = array(
			"company_id"=>$comp_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("loan_type");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**	
	 * Check Earnings Entitled
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $field
	 */
	public function check_earnings_entitled($emp_id,$company_id,$field){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			// $field=>"No",
			//$field=>"no",
			"status"=>"Active"
		);
		$this->db->where($w);
		$this->db->where("({$field} = 'no' OR {$field} IS NULL OR {$field} = '')");
		// $q = $this->db->get("employee_earnings");
		$q = $this->db->get("employee_payroll_information");
		$r = $q->row();
		$q->free_result();
		return ($r) ? TRUE : FALSE ;
	}
	
	/**
	 * Check Overtime Rate
	 * @param unknown_type $key
	 * @param unknown_type $company_id
	 */
	public function check_overtime_rate($key,$company_id){
		$w = array(
			"hour_type_id"=>$key,
			"company_id"=>$company_id,
			"default" => "0",
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("hours_type");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}	
	
	/**
	 * ND Settings
	 * @param unknown_type $company_id
	 */
	public function nd_settings($company_id){
		$w = array(
			"company_id"=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("nightshift_differential_settings");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Carry Over De Minimis
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $payroll_period
	 */
	public function carry_over_de_minimis($emp_id,$company_id,$period_from,$period_to,$payroll_period){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"payroll_period"=>$payroll_period,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_de_minimis");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Carry Over Allowances
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $payroll_period
	 */
	public function carry_over_allowances($emp_id,$company_id,$period_from,$period_to,$payroll_period){
		
		/* $w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"payroll_period"=>$payroll_period,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_allowances");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ; */
		
		$w = array(
			"pca.emp_id"=>$emp_id,
			"pca.company_id"=>$company_id,
			"pca.period_from"=>$period_from,
			"pca.period_to"=>$period_to,
			"pca.payroll_period"=>$payroll_period,
			"pca.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("allowance_settings AS ast","ast.allowance_settings_id = pca.allowance_settings_id","INNER");
		$q = $this->db->get("payroll_carry_over_allowances AS pca");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Carry Over Earnings
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $payroll_period
	 */
	public function carry_over_earnings($emp_id,$company_id,$period_from,$period_to,$payroll_period){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"payroll_period"=>$payroll_period,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_earnings");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Carry Over Other Deductions
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $payroll_period
	 */
	public function carry_over_other_deductions($emp_id,$company_id,$period_from,$period_to,$payroll_period){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"payroll_period"=>$payroll_period,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_other_deductions");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * De Minimis For Diff Employee
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $de_minimis_id
	 */
	public function check_employee_dm($emp_id,$company_id,$de_minimis_id){
		$w = array(
			"edm.emp_id"=>$emp_id,
			"dms.company_id"=>$company_id,
			"dms.de_minimis_id"=>$de_minimis_id,
			"edm.flag_custom_amount"=>1
		);
		$this->db->where($w);
		$this->edb->join("employee_de_minimis AS edm","edm.de_minimis_id = dms.de_minimis_id","LEFT");
		$q = $this->edb->get("de_minimis_for_diff_emp AS dms");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Fixed Allowances
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $comp_id $allowance_settings_id // add
	 */
	public function get_fixed_allowances($emp_id,$comp_id,$allowance_settings_id){
		$s = array(
			"*",
			"efa.pay_out_schedule AS pay_out_schedule",
			"efa.variability_type AS variability_type",
			"efa.frequency AS frequency",
			"efa.allowance_amount AS allowance_amount",
			"efa.employee_entitled_to_allowance_for_absent AS employee_entitled_to_allowance_for_absent",
			"efa.daily_rate AS daily_rate"
		);
		$this->db->select($s);
		$w = array(
			"efa.emp_id"=>$emp_id,
			"efa.company_id"=>$comp_id,
			"als.allowance_settings_id"=>$allowance_settings_id,
			"efa.status"=>"Active",
			"als.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("allowance_settings AS als","als.allowance_settings_id = efa.allowance_settings_id","LEFT");
		//$q = $this->db->get("employee_fixed_allowances AS efa");
		$q = $this->db->get("employee_allowances AS efa");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Earnings Amount
	 * @param unknown_type $earning_id
	 * @param unknown_type $emp_id
	 */
	public function employee_earnings_amount($earning_id,$emp_id){
		$w = array(
			"earning_id"=>$earning_id,
			"emp_id"=>$emp_id
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_other_earnings");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Employee Other Deductions
	 * @param unknown_type $emp_id
	 * @param unknown_type $deductions_other_deductions_id
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $payroll_period
	 */
	public function get_employee_deduction($emp_id,$deductions_other_deductions_id,$period_from,$period_to,$payroll_period){
		$w = array(
			"emp_id"=>$emp_id,
			"deduction_other_deduction_id"=>$deductions_other_deductions_id,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"payroll_date"=>$payroll_period
		);
		$this->db->where($w);
		$q = $this->edb->get("payroll_run_other_deductions");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Employee Government Loans
	 * @param unknown_type $emp_id
	 * @param unknown_type $deductions_other_deductions_id
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $payroll_period
	 */
	public function get_employee_government_loans($emp_id,$id,$period_from,$period_to,$payroll_period){
		$w = array(
			"prg.emp_id"=>$emp_id,
			//loan_deduction_idd"=>$id,
			"gld.loan_type_id"=>$id,
			"prg.period_from"=>$period_from,
			"prg.period_to"=>$period_to,
			"prg.payroll_period"=>$payroll_period,
			"prg.flag_opening_balance"=>0
		);
		$this->db->where($w);
		$this->db->join("gov_loans_deduction AS gld","gld.loan_deduction_id = prg.loan_deduction_id","LEFT"); // INNER TO LEFT JOIN
		$q = $this->db->get("payroll_run_government_loans AS prg");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Employee Government Loans
	 * @param unknown_type $emp_id
	 * @param unknown_type $deductions_other_deductions_id
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $payroll_period
	 */
	public function get_employee_government_loans2($emp_id,$id,$period_from,$period_to,$payroll_period){
		$w = array(
			"prg.emp_id"=>$emp_id,
			//loan_deduction_idd"=>$id,
			"gld.loan_type_id"=>$id,
			"prg.period_from"=>$period_from,
			"prg.period_to"=>$period_to,
			"prg.payroll_period"=>$payroll_period,
			"prg.flag_opening_balance"=>1
		);
		$this->db->where($w);
		$this->db->join("government_loans AS gld","gld.loan_type_id = prg.loan_deduction_id","INNER");
		$q = $this->db->get("payroll_run_government_loans AS prg");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Employee Loans
	 * @param unknown_type $emp_id
	 * @param unknown_type $deductions_other_deductions_id
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $payroll_period
	 */
	public function get_employee_loans($emp_id,$id,$period_from,$period_to,$payroll_period){
		$w = array(
			"emp_id"=>$emp_id,
			"loan_type_id"=>$id,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"payroll_date"=>$payroll_period
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_run_loans");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Employee Other Deductions
	 * @param unknown $emp_id
	 * @param unknown id
	 * @param unknown $period_from
	 * @param unknown $period_to
	 * @param unknown $payroll_period
	 */
	public function get_employee_other_deductions($emp_id,$id,$period_from,$period_to,$payroll_period){
		$w = array(
			"emp_id"=>$emp_id,
			"deduction_id"=>$id,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"payroll_period"=>$payroll_period
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_for_other_deductions");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Loan ID
	 * @param unknown_type $emp_id
	 * @param unknown_type $loan_type_id
	 * @param unknown_type $company_id
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $payroll_period
	 */
	public function check_employee_loan_id($emp_id,$loan_type_id,$company_id,$period_from,$period_to,$payroll_period){
		$s = array(
			"prl.installment",
			"prl.emp_id",
			"prl.company_id",
			"prl.period_from",
			"prl.period_to",
			"prl.payroll_date"
		);
		$this->edb->select($s);
		
		$s2 = array(
			"lt.loan_type_name","lt.loan_type_id AS loan_type_id"
		);
		$this->db->select($s2);
		
		$w = array(
			"prl.emp_id"=>$emp_id,
			"el.emp_id"=>$emp_id,
			"prl.loan_type_id"=>$loan_type_id,
			"prl.company_id"=>$company_id,
			"prl.period_from"=>$period_from,
			"prl.period_to"=>$period_to,
			"prl.payroll_date"=>$payroll_period
		);
		$this->db->where($w);
		$this->db->join("loan_type AS lt","lt.loan_type_id = prl.loan_type_id","LEFT");
		$this->db->join("employee_loans AS el","el.loan_type_id = lt.loan_type_id","LEFT");
		$this->db->group_by(array("prl.emp_id","prl.installment","prl.payroll_date"));
		$q = $this->edb->get("payroll_run_loans AS prl");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Accrual Information
	 * @param unknown_type $company_id
	 */
	public function accrual($company_id){
		$where = array(
			"comp_id" => $company_id
		);
		$this->db->where($where);
		$sql = $this->db->get("accrual_formula");
		$r = $sql->result();
		$sql->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Total Regular Days
	 * Enter description here ...
	 * @param unknown_type $payroll
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function total_regular_days($payroll,$emp_id,$company_id){
		$from = strtotime(date('Y-m-d', strtotime($payroll->period_from)));
		$to = strtotime(date('Y-m-d', strtotime($payroll->period_to)));
		
		$total_days = (($to - $from) / (3600 * 24)) + 1;
		
		$new_total_days = 0;
		$date_from3 = $payroll->period_from;
		
		for($i=0;$i<$total_days;$i++){
			$workday = date('l',strtotime($date_from3));
			$work_schedule_id = $this->work_schedule_id($company_id,$emp_id,date("Y-m-d",strtotime($date_from3)));
			$get_restday = $this->get_rest_day($company_id,$work_schedule_id->work_schedule_id,$workday);
			$new_total_days = ($get_restday != FALSE) ? $new_total_days + 1 : $new_total_days + 0 ;
			$date_from3 = date('m/d/Y',strtotime($date_from3." +1 day"));
		}
		
		$no_of_days_for_payroll_period = $total_days - $new_total_days;
		return $no_of_days_for_payroll_period;
	}
	
	/**
	 * Payroll Master List
	 * @param unknown_type $company_id
	 * @param unknown_type $per_page
	 * @param unknown_type $page
	 * @param unknown_type $sort_by
	 * @param unknown_type $q
	 */
	public function payroll_master_list($payroll,$company_id,$per_page, $page,$sort_by, $q){
		$konsum_key = konsum_key();
		
		$w2 = array(
			"pp.company_id"=>$company_id,
			"pp.payroll_date"=>$payroll->payroll_period,
			"pp.period_from"=>$payroll->period_from,
			"pp.period_to"=>$payroll->period_to,
			"pp.status"=>"Active"
		);
		$this->edb->where($w2);
		
		if($q !==""){
			$this->db->where("
				(convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
		}
		
		$this->edb->join("employee AS e","e.emp_id = pp.emp_id","left");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","left");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epl.payroll_group_id","LEFT");
		
		$this->edb->order_by("e.last_name","ASC");
		$q2 = $this->edb->get("payroll_payslip AS pp",$per_page, $page);
		$r = $q2->result();
		$q2->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Payroll Master List Counter
	 * @param unknown_type $payroll
	 * @param unknown_type $company_id
	 * @param unknown_type $q
	 */
	public function payroll_master_list_counter($payroll,$company_id,$q){
		$konsum_key = konsum_key();
		$s = array(
			"COUNT(pp.emp_id) AS total"
		);
		$this->edb->select($s);

		$w2 = array(
			"pp.company_id"=>$company_id,
			"pp.payroll_date"=>$payroll->payroll_period,
			"pp.period_from"=>$payroll->period_from,
			"pp.period_to"=>$payroll->period_to,
			"pp.status"=>"Active"
		);
		$this->edb->where($w2);
		
		if($q !==""){
			$this->db->where("
				(convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
		}
		
		$this->edb->join("employee AS e","e.emp_id = pp.emp_id","left");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","left");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epl.payroll_group_id","LEFT");
		
		$this->edb->order_by("e.last_name","ASC");
		$q2 = $this->edb->get("payroll_payslip AS pp");
		$r = $q2->row();
		$q2->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Check Company Payroll Period
	 * @param unknown_type $company_id
	 */
	public function check_company_payroll_period($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_period");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * De Minimis Settings
	 * @param unknown_type $company_id
	 */
	public function de_minimis_settings($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("deminimis");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check De Minimis Payslip
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function check_de_minimis_payslip($pay_period,$emp_id,$company_id){
		$month = date("m",strtotime($pay_period));
		$year = date("Y",strtotime($pay_period));
		
		/*$w = array(
			"YEAR(payroll_date)" => $year,
			"MONTH(payroll_date)" => $month,
			"emp_id" => $emp_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->edb->get("payroll_payslip");
		$r = $q->row();*/
		
		/* $w = array(
			"YEAR(dpr.pay_period)" => $year,
			"MONTH(dpr.pay_period)" => $month,
			"epi.payroll_group_id" => $emp_id,
			"dpr.company_id"=>$company_id,
			"dpr.view_status"=>"Closed",
			"dpr.status"=>"Active"
		);
		$this->db->where($w);
		$this->edb->join("employee_payroll_information AS epi","epi.payroll_group_id = dpr.payroll_group_id","INNER");
		$q = $this->edb->get("draft_pay_runs AS dpr");
		$r = $q->row();
		return ($r) ? $r : FALSE ; */
		
		$w = array(
			"YEAR(payroll_period)" => $year,
			"MONTH(payroll_period)" => $month,
			"emp_id" => $emp_id,
			"company_id"=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_de_minimis");
		$r = $q->row();
		$q->free_result();
		if($r){
			return ($r->amount > 0) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check Employee ID De Minimis
	 * @param unknown_type $de_minimis_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function check_employee_id_de_minimis($de_minimis_id,$emp_id,$company_id){
		$w = array(
			"de_minimis_id"=>$de_minimis_id,
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->edb->get("employee_de_minimis");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Rank Total Working Days in a year
	 * @param unknown_type $emp_id
	 */
	public function rank_total_working_days_in_a_year($emp_id){
		
		$w = array(
			"epi.emp_id"=>$emp_id,
			"epi.status"=>"Active"
		);
		$s = array(
			"*","pcs.working_days_in_a_year AS total_working_days_in_a_year"
		);
		$this->db->select($s);
		$this->db->where($w);
		$this->db->where("pcs.enable_rank","No");
		$this->db->join("payroll_calendar_working_days_settings AS pcs","pcs.company_id = epi.company_id","LEFT");
		$q = $this->db->get("employee_payroll_information AS epi");
		$r = $q->row();
		$q->free_result();
		if($r){
			// COMPANY WIDE (DEFUALT VALUE FOR ALL COMPANY)
			return $r;
		}else{
			// WORKING DAYS DEPENDING ON RANK
			$this->db->where($w);
			$this->db->join("rank_working_days AS rwd","rwd.rank_id = epi.rank_id","INNER");
			$q = $this->db->get("employee_payroll_information AS epi");
			$r = $q->row();
			$q->free_result();
			return ($r) ? $r : FALSE ;
		}
	}
	
	/**
	 * Check Hours Type
	 * @param unknown_type $key
	 * @param unknown_type $company_id
	 */
	public function check_hours_type($key,$company_id){
		$w = array(
			"hour_type_id"=>$key,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("hours_type");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Holiday Hours Type
	 * @param unknown_type $day_type
	 * @param unknown_type $holiday_type
	 * @param unknown_type $company_id
	 */
	public function check_holiday_hours_type($day_type,$holiday_type,$company_id){
		$w = array(
			"hour_type_name"=>"{$day_type};{$holiday_type};Regular",
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("hours_type");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Night Differential Rate
	 * @param unknown_type $day_type
	 * @param unknown_type $holiday_type
	 * @param unknown_type $company_id
	 */
	public function check_night_differential_rate_for_premium($day_type,$holiday_type,$company_id){
		$s = array(
			"*","ndfp.pay_rate AS pay_rate"
		);
		$this->db->select($s);
		$w = array(
			"ndfp.flag_add_prem"=>1,
			"ht.hour_type_name"=>"{$day_type};{$holiday_type};Regular",
			"ht.company_id"=>$company_id,
			"ht.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("nightshift_differential_for_premium AS ndfp","ndfp.hours_type_id = ht.hour_type_id","LEFT");
		$q = $this->db->get("hours_type AS ht");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Overtime Hours Type
	 * @param unknown_type $day_type
	 * @param unknown_type $holiday_type
	 * @param unknown_type $company_id
	 */
	public function check_overtime_hours_type($day_type,$holiday_type,$company_id){
		$w = array(
			"ht.hour_type_name"=>"{$day_type};{$holiday_type};Overtime",
			"ht.company_id"=>$company_id,
			"ot.company_id"=>$company_id,
			"ht.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("overtime_type AS ot","ot.hour_type_id = ht.hour_type_id","LEFT");
		$q = $this->db->get("hours_type AS ht");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Payroll Group Employee List
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 */
	public function get_payroll_group_employee_list($payroll_group_id,$company_id){
		
		// CHECK PAYROLL GROUP VALUE
		if($payroll_group_id == "" || $payroll_group_id < 0){
			return FALSE;
		}
		
		$s = array(
			"*","e.emp_id AS emp_id"
		);
		$this->edb->select($s);
		
		$w = array(
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active",
			"epl.employee_status"=>"Active"
		);
		$this->db->where($w);
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		#$this->edb->join("department AS d","d.dept_id = epl.department_id","INNER");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		// $this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","INNER");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epl.payroll_group_id","LEFT");
		$this->edb->join("cost_center AS cc","cc.cost_center_id = epl.cost_center","LEFT");
		$q = $this->edb->get("employee AS e");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Work Schedule Information
	 * @param unknown_type $work_schedule_id
	 * @param unknown_type $company_id
	 */
	public function work_schedule_name($work_schedule_id,$company_id){
		$s = array(
			"*","name AS work_schedule_name"
		);
		$this->db->select($s);
		$w = array(
			"work_schedule_id"=>$work_schedule_id,
			"comp_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("work_schedule");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Cost Center Information
	 * @param unknown_type $cost_center
	 * @param unknown_type $company_id
	 */
	public function cost_center($cost_center,$company_id){
		$w = array(
			"cost_center_id"=>$cost_center,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("cost_center");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Exclude Employee
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function check_exclude_employee($emp_id,$company_id){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("exclude_list");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ; 
	}
	
	/**
	 * Check Payroll Group Draft Pay Run
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $pay_period
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $company_id
	 */
	public function check_payroll_group_draft_pay_run($payroll_group_id,$pay_period,$period_from,$period_to,$company_id){
		$s = array(
			"*","dpr.pay_period AS payroll_period"
		);
		$this->db->select($s);
		$w = array(
			"dpr.payroll_group_id"=>$payroll_group_id,
			//"pay_period"=>$pay_period,
			//"period_from"=>$period_from,
			//"period_to"=>$period_to,
			"dpr.company_id"=>$company_id,
			"dpr.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("payroll_group AS pg","pg.payroll_group_id = dpr.payroll_group_id","LEFT");
		$q = $this->db->get("draft_pay_runs AS dpr");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Draft Pay Runs
	 * @param unknown_type $company_id
	 * @param unknown_type $per_page
	 * @param unknown_type $page
	 * @param unknown_type $sort_by
	 * @param unknown_type $q
	 */
	public function draft_pay_runs($company_id,$per_page, $page,$sort_by, $q, $draft_pay_runs_param = NULL){
		$w = array(
			"dpr.company_id"=>$company_id,
			"dpr.status"=>"Active"
			// "dpr.view_status"=>"Open"
		);
		$this->db->where($w);
		$this->db->where("(dpr.view_status = 'Open' OR dpr.view_status = 'Rejected')");
		
		// (pg.name LIKE '%".$q."%') OR (dpr.draft_pay_run_id LIKE '%".$q."%')
		// (dpr.draft_pay_run_id LIKE '%".$q."%')
		
		if($q != ""){
			$this->db->where("
				(pg.name LIKE '%".$q."%') OR (dpr.draft_pay_run_id LIKE '%".$q."%')
				AND
				(dpr.view_status = 'Open' OR dpr.view_status = 'Rejected')
			", NULL, FALSE);
		}
		
		if($sort_by != "" && $draft_pay_runs_param == ""){
			
			#$this->db->order_by($sort_by,"ASC");
			if($sort_by == "pg.name"){
				$this->db->order_by($sort_by,"ASC");
				$this->db->order_by("dpr.payroll_group_id","DESC");
			}else{
				$this->db->order_by($sort_by,"ASC");
			}
			
		}else if($sort_by != "" && $draft_pay_runs_param != ""){
			#$this->db->order_by($sort_by,$draft_pay_runs_param["sort"]);
			
			if($sort_by == "pg.name"){ // payroll group name
				if($draft_pay_runs_param["sort"] == "ASC"){
					$this->db->order_by("dpr.payroll_group_id","DESC");
					$this->db->order_by($sort_by,$draft_pay_runs_param["sort"]);
				}else{
					$this->db->order_by($sort_by,$draft_pay_runs_param["sort"]);
					$this->db->order_by("dpr.payroll_group_id","ASC");
				}
			}else if($sort_by == "lo.name"){ // location name
				if($draft_pay_runs_param["sort"] == "ASC"){
					$this->db->order_by("dpr.location_id","DESC");
					$this->db->order_by($sort_by,$draft_pay_runs_param["sort"]);
				}else{
					$this->db->order_by($sort_by,$draft_pay_runs_param["sort"]);
					$this->db->order_by("dpr.location_id","ASC");
				}
			}else{
				$this->db->order_by($sort_by,$draft_pay_runs_param["sort"]);
			}
			
		}else {
			$this->db->order_by("dpr.pay_period","DESC");
		}
		
		$this->db->join("payroll_group AS pg","pg.payroll_group_id = dpr.payroll_group_id","LEFT");
		$this->db->join("location_and_offices AS lo","lo.location_and_offices_id = dpr.location_id","LEFT"); // new
		
		// $this->db->order_by("draft_pay_run_id","DESC");
		// $this->db->order_by("dpr.pay_period","DESC");
		
		$q = $this->db->get("draft_pay_runs AS dpr",$per_page, $page);
		$r = $q->result();
		$q->free_result();
		#last_query();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Draft Pay Runs Counter
	 * @param unknown_type $company_id
	 * @param unknown_type $q
	 */
	public function draft_pay_runs_counter($company_id, $q){
		$s = array(
			"COUNT(draft_pay_run_id) AS total"
		);
		$this->db->select($s);
		$w = array(
			"dpr.company_id"=>$company_id,
			"dpr.status"=>"Active",
			#"dpr.view_status != "=>"Closed"
			#"dpr.view_status"=>"Open"
		);
		$this->db->where($w);
		$this->db->where("(dpr.view_status = 'Open' OR dpr.view_status = 'Rejected')");
		
		// (pg.name LIKE '%".$q."%') OR (dpr.draft_pay_run_id LIKE '%".$q."%')
		// (dpr.draft_pay_run_id LIKE '%".$q."%')
		
		if($q != ""){
			$this->db->where("
				(pg.name LIKE '%".$q."%') OR (dpr.draft_pay_run_id LIKE '%".$q."%')
				AND
				(dpr.view_status = 'Open' OR dpr.view_status = 'Rejected')
			", NULL, FALSE);
		}
		
		$this->db->join("payroll_group AS pg","pg.payroll_group_id = dpr.payroll_group_id","LEFT");
		$this->db->join("location_and_offices AS lo","lo.location_and_offices_id = dpr.location_id","LEFT"); // new
		
		$q = $this->db->get("draft_pay_runs AS dpr");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Draft Pay Runs
	 * @param unknown_type $company_id
	 * @param unknown_type $per_page
	 * @param unknown_type $page
	 * @param unknown_type $sort_by
	 * @param unknown_type $q
	 */
	public function pay_run_history($company_id,$per_page, $page,$sort_by, $q, $draft_pay_runs_param = NULL){
		$w = array(
			"dpr.company_id"=>$company_id,
			"dpr.status"=>"Active",
			// "dpr.view_status !=" => "Open"
		);
		$this->db->where($w);
		$this->db->where("(dpr.view_status = 'Closed' OR dpr.view_status = 'Waiting for approval')");
		
		// (pg.name LIKE '%".$q."%') OR (dpr.draft_pay_run_id LIKE '%".$q."%')
		// (dpr.draft_pay_run_id LIKE '%".$q."%')
		
		if($q != ""){
			$this->db->where("
				(pg.name LIKE '%".$q."%' OR dpr.draft_pay_run_id LIKE '%".$q."%')
				AND
				(dpr.view_status = 'Closed' OR dpr.view_status = 'Waiting for approval')
			", NULL, FALSE);
		}
		
		if($sort_by != "" && $draft_pay_runs_param == ""){
			$this->db->order_by($sort_by,"ASC");
		}else if($sort_by != "" && $draft_pay_runs_param != ""){
			#$this->db->order_by($sort_by,$draft_pay_runs_param["sort"]);
			
			if($sort_by == "pg.name"){ // payroll group name
				if($draft_pay_runs_param["sort"] == "ASC"){
					$this->db->order_by("dpr.payroll_group_id","DESC");
					$this->db->order_by($sort_by,$draft_pay_runs_param["sort"]);
				}else{
					$this->db->order_by($sort_by,$draft_pay_runs_param["sort"]);
					$this->db->order_by("dpr.payroll_group_id","ASC");
				}
			}else if($sort_by == "lo.name"){ // location name
				if($draft_pay_runs_param["sort"] == "ASC"){
					$this->db->order_by("dpr.location_id","DESC");
					$this->db->order_by($sort_by,$draft_pay_runs_param["sort"]);
				}else{
					$this->db->order_by($sort_by,$draft_pay_runs_param["sort"]);
					$this->db->order_by("dpr.location_id","ASC");
				}
			}else{
				$this->db->order_by($sort_by,$draft_pay_runs_param["sort"]);
			}
			
		}else{
			$this->db->order_by("dpr.pay_period","DESC");
		}
		
		$this->db->join("payroll_group AS pg","pg.payroll_group_id = dpr.payroll_group_id","LEFT");
		$this->db->join("location_and_offices AS lo","lo.location_and_offices_id = dpr.location_id","LEFT"); // new
		
		// $this->db->order_by("dpr.view_status","ASC");
		// $this->db->order_by("dpr.pay_period","DESC");
		
		$q = $this->db->get("draft_pay_runs AS dpr",$per_page, $page);
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Draft Pay Runs Counter
	 * @param unknown_type $company_id
	 * @param unknown_type $q
	 */
	public function pay_run_history_counter($company_id, $q){
		$s = array(
			"COUNT(draft_pay_run_id) AS total"
		);
		$this->db->select($s);
		$w = array(
			"dpr.company_id"=>$company_id,
			"dpr.status"=>"Active",
			// "dpr.view_status !=" => "Open"
		);
		$this->db->where($w);
		$this->db->where("(dpr.view_status = 'Closed' OR dpr.view_status = 'Waiting for approval')");
		
		// (pg.name LIKE '%".$q."%') OR (dpr.draft_pay_run_id LIKE '%".$q."%')
		// (dpr.draft_pay_run_id LIKE '%".$q."%')
		
		if($q != ""){
			$this->db->where("
				(pg.name LIKE '%".$q."%' OR dpr.draft_pay_run_id LIKE '%".$q."%')
				AND
				(dpr.view_status = 'Closed' OR dpr.view_status = 'Waiting for approval')
			", NULL, FALSE);
		}
		
		$this->db->join("payroll_group AS pg","pg.payroll_group_id = dpr.payroll_group_id","LEFT");
		$this->db->join("location_and_offices AS lo","lo.location_and_offices_id = dpr.location_id","LEFT"); // new
		$q = $this->db->get("draft_pay_runs AS dpr");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Employee Payroll Group Info
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 */
	public function employee_payroll_group_info($payroll_group_id,$company_id){
		$w = array(
			"epi.payroll_group_id"=>$payroll_group_id,
			"epi.company_id"=>$company_id,
			"epi.status"=>"Active"
		);
		$this->db->where($w);
		$this->edb->join("employee AS e","e.emp_id = epi.emp_id","LEFT");
		$q = $this->edb->get("employee_payroll_information AS epi");
		$r = $q->result();
		if($r){
			$data["data"] = array(
				"total_rows"=>$q->num_rows(),
				"result"=>$r
			);
			$q->free_result();
			return $data;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Employee Exclude
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 */
	public function employee_exclude($payroll_group_id,$company_id){
		$w = array(
			"el.exclude"=>1,
			"epi.payroll_group_id"=>$payroll_group_id,
			"epi.company_id"=>$company_id,
			"epi.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("employee_payroll_information AS epi","epi.emp_id = el.emp_id","LEFT");
		$q = $this->db->get("exclude_list AS el");
		$r = $q->result();
		$q->free_result();
		if($r){
			$data["data"] = array(
				"total_rows"=>$q->num_rows(),
				"result"=>$r
			);
			return $data;
		}else{
			return FALSE;
		} 
	}
	
	/**
	 * Total Net Pay for Current Period
	 * @param unknown_type $payroll_period
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $company_id
	 */
	public function total_net_pay_for_current_period($payroll_period,$period_from,$period_to,$company_id){
		
		$k = konsum_key();
		$this->db->select("SUM(AES_DECRYPT(net_amount,'{$k}')) AS total",FALSE,TRUE);
		$w = array(
			"payroll_date"=>$payroll_period,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->edb->get("payroll_payslip");
		$r = $q->row();
		return ($r) ? $r->total : 0 ;
		
		/* $r = $q->result();
		$q->free_result();
		if($r){
			$total = 0;
			foreach($r as $row){
				
				// check if excluded
				$exclude = $this->get_exclude_list($company_id,$row->emp_id);
				
				if(!$exclude){
					$total += $row->net_amount;
				}
				
			}
			return $total;
		}else{
			return 0;
		} */
	}
	
	/**
	 * Employee Master List
	 * @param unknown_type $payroll_period
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $company_id
	 * @param unknown_type $per_page
	 * @param unknown_type $page
	 * @param unknown_type $sort_by
	 * @param unknown_type $q
	 */
	public function employee_master_list($payroll_period,$period_from,$period_to,$company_id,$per_page, $page,$sort_by, $q, $sort_param = NULL){
		
		$konsum_key = konsum_key();
		
		// GET EMPLOYEE EXCLUDED LISTS
		// $employee_excluded_lists = $this->employee_excluded_lists($company_id);
		$employee_excluded_lists = $this->employee_excluded_lists($company_id, $sort_param);
		
		// GET EMPLOYEE NGA MANA PA RUN SA HR
		$flag_employee_open = TRUE;
		if(isset($sort_param["flag_closed_or_waiting_for_approval"])){
			
			// do nothing, for payroll approval nani siya
			$check_employee_already_run = FALSE;
			
			// GET EMPLOYEE PAYROLL STATUS CLOSED OR PENDING
			$get_employee_payroll_status_closed_or_pending = $this->get_employee_payroll_status_closed_or_pending($sort_param);
			$flag_employee_open = FALSE;
			
		}else{
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
			"pp.payroll_date"=>$payroll_period,
			"pp.period_from"=>$period_from,
			"pp.period_to"=>$period_to,
			"e.company_id"=>$company_id,
			"e.status"=>"Active",
			"epl.employee_status"=>"Active"
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
			#$this->db->where_not_in("e.emp_id", $check_employee_already_run);
		}
		
		// PARA NI SA CLOSED OR PENDING STATUS SA PAYROLL
		if(!$flag_employee_open){
			$this->db->where_in("pp.emp_id", $get_employee_payroll_status_closed_or_pending);
		}
		
		// INCLUDE EMPLOYEE LIST
		if(isset($sort_param["emp_include"])){
			if($sort_param["emp_include"] != NULL){
				$this->db->where_in("pp.emp_id", $sort_param["emp_include"]);
				
				/* $check_employee_already_run = implode(",", $check_employee_already_run);
				$check_employee_already_run = ($check_employee_already_run == "") ? "''" : $check_employee_already_run ;
				$emp_include = implode(",", $sort_param["emp_include"]);
				$this->db->where("
						(
							e.emp_id NOT IN ({$check_employee_already_run})
								OR
							pp.emp_id IN ({$emp_include})
						)
				",NULL, FALSE); */
				
			}
		}
		
		$this->edb->join("employee AS e","e.emp_id = pp.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epl.payroll_group_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		
		if($sort_by != "" && $sort_param != ""){
			if($sort_by == "e.last_name" || $sort_by == "a.payroll_cloud_id"){
				$this->edb->order_by($sort_by,$sort_param["sort"]);
			}else{
				$this->db->order_by($sort_by,$sort_param["sort"]);
			}
		}else if($sort_by != "" && $sort_param == ""){
			if($sort_by == "e.last_name" || $sort_by == "a.payroll_cloud_id"){
				$this->edb->order_by($sort_by,"ASC");
			}else{
				$this->db->order_by($sort_by,"ASC");
			}
		}else{
			$this->edb->order_by("e.last_name","ASC");
		}
		
		$q = $this->edb->get("payroll_payslip AS pp",$per_page, $page);
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}

	/**
	 * Employee Master List counter
	 * @param unknown_type $payroll_period
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $company_id
	 * @param unknown_type $q
	 */
	public function employee_master_list_counter($payroll_period,$period_from,$period_to,$company_id,$q, $sort_param = NULL){
		
		$konsum_key = konsum_key();
		
		// GET EMPLOYEE EXCLUDED LISTS
		// $employee_excluded_lists = $this->employee_excluded_lists($company_id);
		$employee_excluded_lists = $this->employee_excluded_lists($company_id, $sort_param);
		
		// GET EMPLOYEE NGA MANA PA RUN SA HR
		if(isset($sort_param["flag_closed_or_waiting_for_approval"])){
			
			// do nothing, for payroll approval nani siya
			$check_employee_already_run = FALSE;
			
			// GET EMPLOYEE PAYROLL STATUS CLOSED OR PENDING
			$get_employee_payroll_status_closed_or_pending = $this->get_employee_payroll_status_closed_or_pending($sort_param);
			$flag_employee_open = FALSE;
			
		}else{
			$check_employee_already_run = $this->check_employee_already_run($sort_param);
			$flag_employee_open = TRUE;
		}
		
		$s = array(
			"COUNT(DISTINCT pp.emp_id) AS total"
		);
		$this->db->select($s);
		
		$w = array(
			"pp.payroll_date"=>$payroll_period,
			"pp.period_from"=>$period_from,
			"pp.period_to"=>$period_to,
			"e.company_id"=>$company_id,
			"e.status"=>"Active",
			"epl.employee_status"=>"Active"
		);
		$this->db->where($w);
		
		if($q !==""){
			$this->db->where("
				(convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
		}
		
		// CHECK KUNG ANG EMPLOYEE NA EXCLUDE WITH IN SA PAYROLL PERIOD, BAG.O NI SYA
		if(isset($sort_param["exclude_view"]) == ""){
			// GET EMPLOYEE EXCLUDED LISTS
			if($employee_excluded_lists != FALSE){
				$this->db->where_not_in("e.emp_id", $employee_excluded_lists);
			}
		}
		
		// GET EMPLOYEE NGA MANA PA RUN SA HR
		if($check_employee_already_run != FALSE){
			#$this->db->where_not_in("e.emp_id", $check_employee_already_run);
		}
		
		// PARA NI SA CLOSED OR PENDING STATUS SA PAYROLL
		if(!$flag_employee_open){
			$this->db->where_in("pp.emp_id", $get_employee_payroll_status_closed_or_pending);
		}
		
		// INCLUDE EMPLOYEE LIST
		if(isset($sort_param["emp_include"])){
			if($sort_param["emp_include"] != NULL){
				$this->db->where_in("pp.emp_id", $sort_param["emp_include"]);
				
				/* $check_employee_already_run = implode(",", $check_employee_already_run);
				$check_employee_already_run = ($check_employee_already_run == "") ? "''" : $check_employee_already_run ;
				$emp_include = implode(",", $sort_param["emp_include"]);
				$this->db->where("
					(
						e.emp_id NOT IN ({$check_employee_already_run})
							OR
						pp.emp_id IN ({$emp_include})
					)
				",NULL, FALSE); */
			}
		}
		
		$this->edb->join("employee AS e","e.emp_id = pp.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epl.payroll_group_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		
		$q = $this->edb->get("payroll_payslip AS pp");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Employee De Minimis Non Taxable Income
	 * @param unknown_type $payroll_period
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function employee_dm_non_taxable_income($payroll_period,$period_from,$period_to,$emp_id,$company_id){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"payroll_period"=>$payroll_period,
			"period_from"=>$period_from,
			"period_to"=>$period_to
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_de_minimis");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Non Taxable Income for Allowances
	 * @param unknown_type $payroll_period
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function employee_allowances_non_taxable_income($payroll_period,$period_from,$period_to,$emp_id,$company_id){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"payroll_period"=>$payroll_period,
			"period_from"=>$period_from,
			"period_to"=>$period_to
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_allowances");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Payroll Commission
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $pay_period
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 */
	public function check_employee_payroll_commission($emp_id,$company_id,$pay_period,$period_from,$period_to){
		$month = date("m");
		$year = date("Y");
		$w = array(
			"YEAR(payroll_period)" => $year,
			"MONTH(payroll_period)" => $month,
			"emp_id" => $emp_id,
			"company_id"=>$company_id,
			"amount > "=>0,
			"payroll_period != "=>$pay_period,
			"period_from != "=>$period_from,
			"period_to != "=>$period_to,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->edb->get("payroll_commission");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Schedule Block Time In
	 * @param unknown_type $emp_id
	 * @param unknown_type $date
	 * @param unknown_type $company_id
	 */
	public function employee_schedule_block_time_in($emp_id,$date,$company_id){
		$s = array(
			"*","lo.name AS location_name"
		);
		$this->db->select($s);
		$w = array(
			"sbt.emp_id"=>$emp_id,
			"sbt.date"=>$date,
			"sbt.comp_id"=>$company_id,
			"sbt.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->where("sbt.time_in IS NOT NULL");
		$this->db->where("sbt.time_out IS NOT NULL");
		$this->db->where("(sbt.time_in_status = 'approved' OR sbt.time_in_status IS NULL)");
		
		$this->db->join("schedule_blocks AS sb","sbt.schedule_blocks_id = sb.schedule_blocks_id","LEFT");
		$this->db->join("location_and_offices AS lo","lo.location_and_offices_id = sb.location_and_offices_id","LEFT");
		$this->db->order_by("sbt.date","DESC");
		$q = $this->db->get("schedule_blocks_time_in AS sbt");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Schedule Block Information
	 * @param unknown_type $schedule_blocks_id
	 * @param unknown_type $company_id
	 */
	public function schedule_block_info($schedule_blocks_id,$company_id){
		$w = array(
			"schedule_blocks_id"=>$schedule_blocks_id,
			"company_id"=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("schedule_blocks");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Pay Run IDs
	 * @param unknown_type $draft_pay_run_token
	 * @param unknown_type $payroll_period
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $company_id
	 */
	public function pay_run_ids($draft_pay_run_token,$payroll_period,$period_from,$period_to,$company_id){
		$w = array(
			"token"=>$draft_pay_run_token,
			"pay_period"=>$payroll_period,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("draft_pay_runs");
		$r = $q->result();
		$q->free_result();
		if($r){
			$arrs = array();
			foreach($r AS $row){
				$payroll_group_id = $row->payroll_group_id;
				
				// check payroll period on payroll payslip
				$where = array(
					"epi.payroll_group_id"=>$payroll_group_id,
					"pp.payroll_date"=>$payroll_period,
					"pp.period_from"=>$period_from,
					"pp.period_to"=>$period_to,
					"pp.company_id"=>$company_id,
					"pp.status"=>"Active"
				);
				$this->db->where($where);
				$this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","LEFT");
				$query = $this->edb->get("payroll_payslip AS pp");
				$result = $query->result();
				$query->free_result();
				if($result != FALSE){
					// array_push($arrs, $row->draft_pay_run_id);	
				}
				
				array_push($arrs, $row->draft_pay_run_id);
			}
			return implode(",", $arrs);
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Payroll Group Draft Pay Run
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $pay_period
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $company_id
	 * @param unknown_type $draft_pay_run_id
	 */
	public function payroll_group_draft_pay_run($payroll_group_id,$pay_period,$period_from,$period_to,$company_id,$draft_pay_run_id=NULL){
		$s = array(
			"*","dpr.pay_period AS payroll_period"
		);
		$this->db->select($s);
		$w = array(
			"dpr.payroll_group_id"=>$payroll_group_id,
			"dpr.draft_pay_run_id"=>$draft_pay_run_id,
			"dpr.pay_period"=>$pay_period,
			"dpr.period_from"=>$period_from,
			"dpr.period_to"=>$period_to,
			"dpr.company_id"=>$company_id,
			"dpr.status"=>"Active"
		);
		$this->db->where($w);
		//$this->db->join("payroll_group AS pg","pg.payroll_group_id = dpr.payroll_group_id","LEFT");
		$q = $this->db->get("draft_pay_runs AS dpr");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Draft Pay Run Payroll Cost
	 * @param unknown_type $draft_pay_run_id
	 * @param unknown_type $company_id
	 */
	public function draft_payroll_cost($draft_pay_run_id,$company_id){
		
		// FLAG RECALCULATE PAYROLL DRAFT COST, FLAG CLOSED AND NO AMOUNT
		$recalculate = FALSE;
		$flag_closed_and_no_amount = FALSE;
		$token = "";
		
		// CHECK IF DRAFT PAY RUN ID HAS TOKEN
		$ww = array(
			"draft_pay_run_id"=>$draft_pay_run_id,
			"company_id"=>$company_id,
			"status"=>"Active",
			"view_status"=>"Closed"
		);
		$this->db->where($ww);
		$query = $this->db->get("draft_pay_runs");
		$row = $query->row();
		if($row){
			
			// CHECK PAYROLL DRAFT COST AMOUNT
			$check_ww = array(
				"draft_pay_run_id"=>$draft_pay_run_id,
				"token"=>$row->token,
				"company_id"=>$company_id,
				"status"=>"Active"
			);
			$this->db->where($check_ww);
			$this->db->order_by("payroll_run_cost_amount_id","DESC");
			$check_qq = $this->edb->get("payroll_run_cost_amount");
			$check_rr = $check_qq->row();
			if($check_rr != FALSE){
				return $check_rr->amount;
			}else{
				$recalculate = TRUE;
				$flag_closed_and_no_amount = TRUE;
				$token = $row->token;
			}
			
		}else{
			$recalculate = TRUE;
		}
		
		// FLAG RECALCULATE
		if($recalculate){
			
			// RECALCULATE
			$w = array(
				"draft_pay_run_id"=>$draft_pay_run_id,
				"company_id"=>$company_id,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("draft_pay_runs");
			$r = $q->row();
			if($q){
					
				// CUSTOM SELECT: additional ni sya para dili mag cge loop pag sum up sa tanan net amount, mas paspas ni sya kay ang sql na mag process..
				$konsum_key = konsum_key();
				$this->db->select("SUM(CAST(AES_DECRYPT(pp.net_amount,'{$konsum_key}') AS DECIMAL(30,2))) as net_amount_total",FALSE);
					
				// by payroll group
				if($r->payroll_group_id > 0){
					// $this->db->where("epi.payroll_group_id",$r->payroll_group_id);
					$this->db->where("pp.payroll_group_id",$r->payroll_group_id);
				}else{
					$this->db->where("prc.draft_pay_run_id",$draft_pay_run_id);
				}
					
				// check payroll period on payroll payslip
				$where = array(
					#"epi.payroll_group_id"=>$r->payroll_group_id,
					"pp.payroll_date"=>$r->pay_period,
					"pp.period_from"=>$r->period_from,
					"pp.period_to"=>$r->period_to,
					"pp.company_id"=>$company_id,
					"pp.status"=>"Active"
				);
				$this->db->where($where);
					
				// check if by payroll group or payroll custom run
				if($r->payroll_group_id > 0){
					$this->db->where(array("epi.employee_status"=>"Active","e.status"=>"Active"));
					$this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER"); // LEFT TO INNER
					$this->edb->join("employee AS e","e.emp_id = epi.emp_id","INNER"); // LEFT TO INNER
				}else{
					$this->db->where(array("epi.employee_status"=>"Active","e.status"=>"Active"));
					$this->edb->join("payroll_run_custom AS prc","prc.emp_id = pp.emp_id","INNER"); // LEFT TO INNER
					$this->edb->join("employee_payroll_information AS epi","epi.emp_id = prc.emp_id","INNER"); // LEFT TO INNER
					$this->edb->join("employee AS e","e.emp_id = epi.emp_id","INNER"); // LEFT TO INNER
				}
					
				$query = $this->edb->get("payroll_payslip AS pp");
				$row = $query->row();
				
				// FLAG IF ANG DRAFT PAY RUN ID WA AMOUNT PERO CLOSED NA SYA, I ADD TO PAYROLL RUN CUSTOM PARA MA REGISTER ANG TOTAL AMOUNT ANI NIYA..
				if($flag_closed_and_no_amount){
					// INSERT PAYROLL RUN COST AMOUNT
					$net_amount_total = ($row) ? $row->net_amount_total : 0 ;
					$insert_val = array(
						"company_id"=>$company_id,
						"amount"=>$net_amount_total,
						"draft_pay_run_id"=>$draft_pay_run_id,
						"token"=>$token
					);
					$this->edb->insert("payroll_run_cost_amount",$insert_val);
				}
				
				return ($row) ? $row->net_amount_total : 0 ;
					
				/* $result = $query->result();
				 if($result){
				 $total = 0;
				 foreach($result AS $row){
				 // $total += $row->net_amount;
				 $new_net_amount = round($row->net_amount,2);
				 $total += $new_net_amount;
				 }
				 return $total;
				 } */
					
			}else{
				return 0;
			}
			
		}
	}
	
	/**
	 * Total Last Payroll Month
	 * @param unknown_type $date_last_month
	 * @param unknown_type $year
	 * @param unknown_type $company_id
	 */
	public function total_last_payroll_month($date_last_month,$year,$company_id){
		
		$total = 0;
		
		/* // PAYROLL GROUP =======================================================================================================
		
		// SUM
		$konsum_key = konsum_key();
		// $this->db->select("SUM(AES_DECRYPT(pp.net_amount,'{$konsum_key}')) as net_amount_total",FALSE);
		$this->db->select("SUM(CAST(AES_DECRYPT(pp.net_amount,'{$konsum_key}') AS DECIMAL(30,2))) as net_amount_total",FALSE);
		
		// EMLPOYEE STATUS AND STATUS = ACTIVE
		$this->db->where(array("epi.employee_status"=>"Active","e.status"=>"Active"));
		
		$w = array(
			"MONTH(dpr.pay_period)"=>$date_last_month,
			"YEAR(dpr.pay_period)"=>$year,
			"MONTH(pp.payroll_date)"=>$date_last_month,
			"YEAR(pp.payroll_date)"=>$year,
			"pp.company_id"=>$company_id,
			"dpr.view_status"=>"Closed",
			"pp.status"=>"Active",
			"dpr.payroll_group_id != " => 0
		);
		$this->db->where($w);
		// $this->edb->join("payroll_payslip AS pp","pp.payroll_group_id = dpr.payroll_group_id","INNER");
		$this->edb->join("payroll_payslip AS pp","pp.payroll_group_id = dpr.payroll_group_id && pp.payroll_date = dpr.pay_period","INNER");
		
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER"); // bag.o ni
		$this->edb->join("employee AS e","e.emp_id = epi.emp_id","INNER"); // bag.o ni
		
		$q = $this->edb->get("draft_pay_runs AS dpr");
		$r = $q->row();
		if($r){
			$total += $r->net_amount_total;
		}
		
		// PAYROLL RUN CUSTOM =================================================================================================
		
		// SUM
		$konsum_key = konsum_key();
		// $this->db->select("SUM(AES_DECRYPT(pp.net_amount,'{$konsum_key}')) as net_amount_total",FALSE);
		$this->db->select("SUM(CAST(AES_DECRYPT(pp.net_amount,'{$konsum_key}') AS DECIMAL(30,2))) as net_amount_total",FALSE);
		
		// EMLPOYEE STATUS AND STATUS = ACTIVE
		$this->db->where(array("epi.employee_status"=>"Active","e.status"=>"Active"));
		
		$w = array(
			"MONTH(dpr.pay_period)"=>$date_last_month,
			"YEAR(dpr.pay_period)"=>$year,
			"MONTH(pp.payroll_date)"=>$date_last_month,
			"YEAR(pp.payroll_date)"=>$year,
			"pp.company_id"=>$company_id,
			"dpr.view_status"=>"Closed",
			"pp.status"=>"Active",
			"dpr.payroll_group_id" => 0
		);
		$this->db->where($w);
		$this->edb->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","INNER");
		$this->edb->join("payroll_payslip AS pp","pp.payroll_date = prc.payroll_period && pp.emp_id = prc.emp_id","INNER");
		
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER"); // bag.o ni
		$this->edb->join("employee AS e","e.emp_id = epi.emp_id","INNER"); // bag.o ni
		
		$q = $this->edb->get("payroll_run_custom AS prc");
		$r = $q->row();
		if($r){
			$total += $r->net_amount_total;
		}
		
		return $total; */
		
		// VERSION 2
		$w = array(
			"MONTH(pay_period)"=>$date_last_month,
			"YEAR(pay_period)"=>$year,
			"view_status"=>"Closed",
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q_main = $this->db->get("draft_pay_runs");
		$result = $q_main->result();
		if($result){
			foreach($result as $row_r){
				$draft_payroll_cost = $this->draft_payroll_cost($row_r->draft_pay_run_id,$company_id);
				$total += $draft_payroll_cost;
			}
		}
		
		return $total;
		
	}
	
	/**
	 * Total Payroll Year to Date
	 * @param unknown_type $company_id
	 */
	public function total_payroll_year_to_date($company_id){
		
		$total = 0;
		
		/* // VERSION 2
		// PAYROLL GROUP =======================================================================================================
		
		// SUM
		$konsum_key = konsum_key();
		// $this->db->select("SUM(AES_DECRYPT(pp.net_amount,'{$konsum_key}')) as net_amount_total",FALSE);
		$this->db->select("SUM(CAST(AES_DECRYPT(pp.net_amount,'{$konsum_key}') AS DECIMAL(30,2))) as net_amount_total",FALSE);
		
		// EMLPOYEE STATUS AND STATUS = ACTIVE
		$this->db->where(array("epi.employee_status"=>"Active","e.status"=>"Active"));
		
		$w = array(
			"pp.company_id"=>$company_id,
			"dpr.view_status"=>"Closed",
			"pp.status"=>"Active",
			"dpr.payroll_group_id != " => 0
		);
		$this->db->where($w);
		// $this->edb->join("payroll_payslip AS pp","pp.payroll_group_id = dpr.payroll_group_id","INNER");
		$this->edb->join("payroll_payslip AS pp","pp.payroll_group_id = dpr.payroll_group_id && pp.payroll_date = dpr.pay_period","INNER");
		
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER"); // bag.o ni
		$this->edb->join("employee AS e","e.emp_id = epi.emp_id","INNER"); // bag.o ni
		
		$q = $this->edb->get("draft_pay_runs AS dpr");
		$r = $q->row();
		if($r){
			$total += $r->net_amount_total;	
		}
		
		// PAYROLL RUN CUSTOM =================================================================================================
		
		// SUM
		$konsum_key = konsum_key();
		// $this->db->select("SUM(AES_DECRYPT(pp.net_amount,'{$konsum_key}')) as net_amount_total",FALSE);
		$this->db->select("SUM(CAST(AES_DECRYPT(pp.net_amount,'{$konsum_key}') AS DECIMAL(30,2))) as net_amount_total",FALSE);
		
		// EMLPOYEE STATUS AND STATUS = ACTIVE
		$this->db->where(array("epi.employee_status"=>"Active","e.status"=>"Active"));
		
		$w = array(
			"pp.company_id"=>$company_id,
			"dpr.view_status"=>"Closed",
			"pp.status"=>"Active",
			"dpr.payroll_group_id" => 0
		);
		$this->db->where($w);
		$this->edb->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","INNER");
		$this->edb->join("payroll_payslip AS pp","pp.payroll_date = prc.payroll_period && pp.emp_id = prc.emp_id","INNER");
		
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","INNER"); // bag.o ni
		$this->edb->join("employee AS e","e.emp_id = epi.emp_id","INNER"); // bag.o ni
		
		$q = $this->edb->get("payroll_run_custom AS prc");
		$r = $q->row();
		
		if($r){
			$total += $r->net_amount_total;
		}
		
		return $total; */
	
		/* // VERSION 2
		$w = array(			
			"view_status"=>"Closed",
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q_main = $this->db->get("draft_pay_runs");
		$result = $q_main->result();
		if($result){
			foreach($result as $row_r){
				$draft_payroll_cost = $this->draft_payroll_cost($row_r->draft_pay_run_id,$company_id);
				$total += $draft_payroll_cost;
			}
		} */
		
		// VERSION 3
		
		// NAA NA DAAN AMOUNT SA PAYROLL RUN COST AMOUNT
		$w = array(
			"dpr.view_status"=>"Closed",
			"dpr.company_id"=>$company_id,
			"dpr.status"=>"Active",
			"prca.status"=>"Active",
			"YEAR(dpr.pay_period)"=>date("Y")
		);
		$this->db->where($w);
		
		$konsum_key = konsum_key();
		$this->db->select("SUM(CAST(AES_DECRYPT(prca.amount,'{$konsum_key}') AS DECIMAL(30,2))) as total_amount",FALSE);
		$this->db->join("payroll_run_cost_amount AS prca","prca.draft_pay_run_id = dpr.draft_pay_run_id && prca.token = dpr.token","INNER");
		$q_main = $this->db->get("draft_pay_runs AS dpr");
		$result = $q_main->row();
		if($result){
			$total += $result->total_amount;
		}
		
		// WLA PA DAAN AMOUNT SA PAYROLL RUN COST AMOUNT
		$s = array(
			"*","dpr.draft_pay_run_id AS draft_pay_run_id"
		);
		$this->db->select($s);
		$w = array(
			"dpr.view_status"=>"Closed",
			"dpr.company_id"=>$company_id,
			"dpr.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->where("(prca.amount IS NULL)");
		$this->db->join("payroll_run_cost_amount AS prca","prca.draft_pay_run_id = dpr.draft_pay_run_id && prca.token = dpr.token","LEFT");
		$q_main = $this->db->get("draft_pay_runs AS dpr");
		$result = $q_main->result();
		if($result){
			foreach($result as $row_r){
				$draft_payroll_cost = $this->draft_payroll_cost($row_r->draft_pay_run_id,$company_id);
				$total += $draft_payroll_cost;
			}
		}
		
		return $total;
		
	}
	
	/**
	 * Next Pay Run Payment Date
	 * @param unknown_type $company_id
	 */
	public function next_pay_run_payment_date($company_id){
		$w = array(
			"company_id"=>$company_id,
			"view_status"=>"Closed",
			"status"=>"Active"
		);
		$this->db->where($w);
		$this->db->order_by("pay_period","DESC");
		$q = $this->db->get("draft_pay_runs",1);
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * New Pay Run Date (Payroll Calendar)
	 * @param unknown_type $current_payroll_period_on_master_list
	 * @param unknown_type $company_id
	 */
	public function npr_date_payroll_calendar($current_payroll_period_on_master_list,$company_id){
		if($current_payroll_period_on_master_list != ""){
			$w = array(
				"first_payroll_date > "=>$current_payroll_period_on_master_list,
				"company_id"=>$company_id,
				"status"=>"Active"
			);
		}else{
			$w = array(
				"company_id"=>$company_id,
				"status"=>"Active"
			);
		}
		$this->db->where($w);
		$this->db->order_by("first_payroll_date","ASC");
		$q = $this->db->get("payroll_calendar",1);
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->first_payroll_date : FALSE ;
	}
	
	/**
	 * Draft Pay Runs Sidebar
	 * @param unknown_type $company_id
	 * @param unknown_type $per_page
	 * @param unknown_type $page
	 * @param unknown_type $sort_by
	 * @param unknown_type $q
	 */
	public function draft_pay_runs_sidebar($company_id,$pay_period,$period_from,$period_to){
		$w = array(
			"dpr.company_id"=>$company_id,
			"dpr.status"=>"Active",
			"dpr.pay_period"=>$pay_period,
			"dpr.period_from"=>$period_from,
			"dpr.period_to"=>$period_to
			// "dpr.view_status != "=>"Closed"
		);
		$this->db->where($w);
		$this->db->where("(dpr.view_status = 'Open' OR dpr.view_status = 'Rejected')");
		$this->db->join("payroll_group AS pg","pg.payroll_group_id = dpr.payroll_group_id","LEFT");
		$q = $this->db->get("draft_pay_runs AS dpr");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Total Draft Employees
	 * @param unknown_type $draft_pay_run_id
	 * @param unknown_type $company_id
	 */
	public function total_draft_employees($draft_pay_run_id,$company_id){
		$w = array(
			"draft_pay_run_id"=>$draft_pay_run_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$this->db->where("(view_status = 'Open' OR view_status = 'Rejected')");		
		$q = $this->db->get("draft_pay_runs");
		$r = $q->row();
		$q->free_result();
		if($q){

			// by payroll group
			if($r->payroll_group_id > 0){
				$this->db->where("epi.payroll_group_id",$r->payroll_group_id);
			}else{
				$this->db->where("prc.draft_pay_run_id",$draft_pay_run_id);
			}
			
			// check payroll period on payroll payslip
			$where = array(
				//"epi.payroll_group_id"=>$r->payroll_group_id,
				"pp.payroll_date"=>$r->pay_period,
				"pp.period_from"=>$r->period_from,
				"pp.period_to"=>$r->period_to,
				"pp.company_id"=>$company_id,
				"pp.status"=>"Active"
			);
			$this->db->where($where);
			
			if($r->payroll_group_id > 0){
				$this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","LEFT");
			}else{
				$this->edb->join("payroll_run_custom AS prc","prc.emp_id = pp.emp_id","LEFT");
			}
			
			$query = $this->edb->get("payroll_payslip AS pp");
			$result = $query->result();
			$query->free_result();
			if($result){
				$total = 0;
				foreach($result AS $row){
					$total++;
				}
				return $total;
			}
		}else{
			return 0;
		}
		
		/* $w = array(
			"draft_pay_run_id"=>$draft_pay_run_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$this->db->where("(view_status = 'Open' OR view_status = 'Rejected')");
		$q = $this->db->get("draft_pay_runs");
		$r = $q->row();
		if($r){
			if($r->payroll_group_id > 0){
				$w = array(
					"dpr.company_id"=>$company_id,
					"dpr.pay_period"=>date("Y-m-d",strtotime($r->payroll_period)),
					"dpr.period_from"=>date("Y-m-d",strtotime($r->period_from)),
					"dpr.period_to"=>date("Y-m-d",strtotime($r->period_to)),
					"pp.payroll_date"=>date("Y-m-d",strtotime($r->payroll_period)),
					"pp.period_from"=>date("Y-m-d",strtotime($r->period_from)),
					"pp.period_to"=>date("Y-m-d",strtotime($r->period_to)),
					"dpr.status"=>"Active"
				);
				$this->db->where($w);
				$this->db->where("(dpr.view_status = 'Open' OR dpr.view_status = 'Rejected')");
				$this->db->join("employee_payroll_information AS epi","pp.emp_id = epi.emp_id","LEFT");
				$this->db->join("draft_pay_runs AS dpr","epi.payroll_group_id = dpr.payroll_group_id","LEFT");
				$q = $this->db->get("payroll_payslip AS pp");
				$r = $q->row();
				$q->free_result();
				return ($r) ? $r : FALSE ;
			}else{
				// check from payroll custom list
				$w = array(
					"prc.company_id"=>$company_id,
					"prc.payroll_period"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
					"prc.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
					"prc.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
					"pp.payroll_date"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
					"pp.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
					"pp.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
					"prc.status"=>"Active"
				);
				$this->db->where($w);
				$this->db->where("(dpr.view_status = 'Open' OR dpr.view_status = 'Rejected')");
				$this->db->join("payroll_payslip AS pp","pp.emp_id = prc.emp_id","LEFT");
				$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
				$q = $this->db->get("payroll_run_custom AS prc");
				$r = $q->row();
			}
		} */
		
	}
	
	/**
	 * Get Other Deductions for employee 2.0
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function other_dd_emp($emp_id,$company_id){
		$w = array(
			"ed.emp_id"=>$emp_id,
			"ed.company_id"=>$company_id,
			"ed.status"=>"Active",
			"dd.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("deductions_other_deductions AS dd","dd.deductions_other_deductions_id = ed.deduction_type_id","left");
		$q = $this->db->get("employee_deductions AS ed");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * View Employee Payroll Info
	 * @param unknown_type $comp_id
	 */
	public function view_employee_payroll_info($comp_id){
		$where = array(
			'epi.company_id' => $comp_id,
			'a.user_type_id' => '5',
			'el.exclude !=' => "1",
			'epi.employee_status'=>'Active'
		);
		$this->edb->where($where);
		$this->db->where('bpi.basic_pay_id IS NOT NULL');	// db non encrypt
		
		// where status = active
		$this->db->where("epi.status","Active");
		$this->db->where("bpi.status","Active");
		$this->db->where("e.status","Active");
		
		$this->edb->join('exclude_list AS el','epi.emp_id = el.emp_id','left');
		$this->edb->join('basic_pay_adjustment AS bpi','epi.emp_id = bpi.emp_id','left');
		$this->edb->join('employee AS e','epi.emp_id = e.emp_id','left');
		$this->edb->join('accounts AS a','e.account_id = a.account_id','left');
		$this->edb->order_by('e.last_name','asc');
		$sql = $this->edb->get('employee_payroll_information AS epi');
		$r = $sql->result();
		$sql->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * check Total working days in a year
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 */
	public function check_total_working_days_in_a_year($payroll_group_id,$company_id){
		
		// CHECK WORKING DAYS DEFAULT VALUE
		$w = array(
			"enable_rank"=>"No",
			"status"=>"Active",
			"company_id"=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_calendar_working_days_settings");
		$r = $q->row();
		$q->free_result();
		if($r){
			return TRUE;
		}else{
			$w = array(
				"epi.payroll_group_id"=>$payroll_group_id,
				"epi.company_id"=>$company_id,
				"epi.status"=>"Active"
			);
			$this->db->where($w);
			$this->db->join("rank_working_days AS rwd","rwd.rank_id = epi.rank_id","LEFT");
			$q = $this->db->get("employee_payroll_information AS epi");
			$r = $q->result();
			$q->free_result();
			if($r){
				$flag = FALSE;
				foreach($r as $row){
					if($row->total_working_days_in_a_year == ""){
						$flag = TRUE;
					}
				}
				return ($flag) ? FALSE : TRUE ;
			}else{
				return FALSE;
			}
		}
		
	}
	
	/**
	 * Draft Pay Run
	 * @param unknown_type $emp_id
	 * @param unknown_type $pay_period
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $company_id
	 */
	public function draft_pay_run($emp_id,$pay_period,$period_from,$period_to,$company_id){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("employee_payroll_information");
		$r = $q->row();
		$q->free_result();
		if($q){
			$this->db->select("*,pay_period AS payroll_period");
			$ww = array(
				#"dpr.payroll_group_id"=>$r->payroll_group_id,
				"dpr.pay_period"=>$pay_period,
				"dpr.period_from"=>$period_from,
				"dpr.period_to"=>$period_to,
				"dpr.company_id"=>$company_id,
				"dpr.status"=>"Active"
			);
			$this->db->where($ww);
			#$this->db->join("payroll_group AS pg","pg.payroll_group_id = dpr.payroll_group_id","LEFT");
			$qq = $this->db->get("draft_pay_runs AS dpr");
			$rr = $qq->row();
			$qq->free_result();
			return ($rr) ? $rr : FALSE ;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Employee List
	 * @param unknown_type $comp_id
	 */
	public function employee_pre_run($comp_id){
		$s = array(
			"*","e.emp_id AS emp_id"
		);
		$this->edb->select($s);
		$w1 = array(
			"c.company_id"=>$comp_id,
			"e.status"=>"Active",
			"epi.employee_status"=>"Active"
		);
		$this->db->where($w1);
		#$this->db->where("b.basic_pay_id IS NOT NULL");
		$this->edb->join("employee AS e","epi.emp_id = e.emp_id","left");
		$this->edb->join("company AS c","e.company_id = c.company_id","left");
		$this->edb->join("accounts AS a","e.account_id = a.account_id","left");
		$this->edb->join("basic_pay_adjustment AS b","e.emp_id = b.emp_id","left");
		$this->edb->join("position AS p","p.position_id = epi.position","left");
		$this->edb->order_by("e.last_name","ASC");
		$q1 = $this->edb->get("employee_payroll_information AS epi");
		$result = $q1->result();
		return ($result) ? $result : FALSE ;
	}
	
	/**
	 * Payroll Run Details Where
	 * @param unknown_type $comp_id
	 */
	public function payroll_run_details_where($emp_id,$comp_id,$payroll){
		$where = array(
			"emp_id"=>$emp_id,
			"company_id"=>$comp_id,
			"period_from"=>$payroll->period_from,
			"period_to"=>$payroll->period_to,
			"payroll_date"=>$payroll->payroll_period
		);
		$this->edb->where($where);
		$sql = $this->edb->get("payroll_payslip");
		$r = $sql->row();
		$sql->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Download Payroll Run Details Where
	 * @param unknown_type $comp_id
	 */
	public function download_payroll_details($emp_id,$comp_id,$payroll){
		$where = array(
			"emp_id"=>$emp_id,
			"company_id"=>$comp_id,
			"period_from"=>$payroll["period_from"],
			"period_to"=>$payroll["period_to"],
			"payroll_date"=>$payroll["payroll_period"]
		);
		$this->edb->where($where);
		$sql = $this->edb->get("payroll_payslip");
		$r = $sql->row();
		$sql->free_result();
		return ($r) ? $r : FALSE ;
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
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Information
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 */
	public function emp_info($emp_id,$comp_id){
		$where = array(
			'employee.emp_id' => $emp_id,
			'employee.company_id' => $comp_id,
			'employee.status'	  => 'Active'
		);
		$this->edb->where($where);
		$this->edb->join('accounts','employee.account_id = accounts.account_id','left');
		$sql = $this->edb->get('employee');
		$row = $sql->row();
		$sql->free_result();
		return ($row) ? $row : FALSE ;
	}
	
	/**
	 * Check Entitled to SSS
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 */
	public function entitled_gc($company_id,$emp_id,$field){
		
		$field = ($field == "entitled_to_pagibig") ? "entitled_to_hdmf" : $field ;
		
		$w = array(
			// $field=>"No",
			$field=>"no",
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		// $q = $this->db->get("employee_government_contributions");
		$q = $this->db->get("employee_payroll_information");
		$r = $q->row();
		$q->free_result();
		return ($r) ? TRUE : FALSE ;
	}
	
	/**
	 * get deductions settings
	 * @param int $company_id
	 */
	public function get_deduction_income($company_id)
	{
		$where = array(
			'comp_id' => $company_id,
			'status'  => 'Active'
		);
		$this->edb->where($where);
		$q = $this->edb->get('deductions_income');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * No need to encrypt
	 * get sss deduction
	 * @param decimal $salary_range
	 */
	public function sss_share($salary_range)
	{
		$this->db->where($salary_range.' BETWEEN range_compensation_from AND range_compensation_to',NULL,FALSE);
		$q = $this->db->get('sss');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result->employee_ss : false;
	}
	
	/**
	 * calculate deduction for sss
	 * @param int 	$company_id
	 * @param int 	$payroll_group_id
	 * @param int   $employee_id
	 * @param array $period
	 * @param $work_schedule_id
	 */
	public function get_deduction_income_sss($company_id,$payroll_group_id,$employee_id,$period,$work_schedule_id=NULL,$employee_basic_pay=NULL)
	{
		// check if employee salary is set
		$basic_pay = $this->get_basic_pay($company_id,$employee_id);
		
		if (!$basic_pay) {
			return false;	
		}
		
		// check new basic pay
		$edate = date('Y-m-d',strtotime($basic_pay->effective_date));
		// $pfrom = date('Y-m-d',strtotime($period->period_from));
		// $pto   = date('Y-m-d',strtotime($period->period_to));
		
		if (is_object($period)) {
			$pfrom = date('Y-m-d',strtotime($period->period_from));
			$pto   = date('Y-m-d',strtotime($period->period_to));
		} elseif (is_array($period)) {
			$pfrom = date('Y-m-d',strtotime($period['period_from']));
			$pto   = date('Y-m-d',strtotime($period['period_to']));
		}

		if ($basic_pay->effective_date) {
			if (($pfrom <= $edate && $pto >= $edate) || $edate <= $pfrom) {
				$bp = $basic_pay->new_basic_pay; 
			} else {
				$bp = $basic_pay->current_basic_pay;
			}
		} else {
			$bp = $basic_pay->current_basic_pay;
		}
		
		// add new check period type
		
		$check_period_type = $this->check_period_type($company_id,$payroll_group_id);
		if($check_period_type != FALSE){
			if($check_period_type->pay_rate_type == "By Hour"){
				$total_hours = 0;
				// get number of days
				$current = $pfrom;
				while($current <= $pto){
					$check_rd = $this->get_rest_day($company_id,$work_schedule_id,date("l",strtotime($current)));
					if(!$check_rd){
						$get_working_hours = $this->get_working_hours($company_id,$work_schedule_id,date("l",strtotime($current)));
						if($get_working_hours != FALSE) $total_hours += $get_working_hours;
					}
					$current = date("Y-m-d",strtotime($current." +1 day"));
				}
			
				// new basic pay
				$bp = $bp * $total_hours;
			}
		}
		
		// end check period type
		
		// check payroll group
		$payroll_group = $this->get_payroll_group($company_id, $payroll_group_id);
		if($payroll_group != FALSE){
			if ($payroll_group->pay_rate_type == 'By Day') {
				$bp = $bp * 26;
			}	
		}
		
		// EMPLOYEE BASIC PAY
		$bp = $employee_basic_pay;
		
		// get deduction income
		$result = $this->get_deduction_income($company_id);
		
		if($result != FALSE){
			$di_settings = array();
			$total = 0;
			
			// Getting all settings for philhealth
			foreach ($result as $di) {
				switch ($di->income) {
					case 'Basic Pay':
						$di_settings['basic'] = $di->basis_for_sss;
						break;
					case 'Overtime Pay':
						$di_settings['overtime'] = $di->basis_for_sss;
						break;
					case 'Holiday/Premium Pay':
						$di_settings['holiday'] = $di->basis_for_sss;
						break;
					case 'Night Shift Differential':
						$di_settings['night_shift'] = $di->basis_for_sss;
						break;
					case 'Taxable Other Earnings':
						$di_settings['tax'] = $di->basis_for_sss;
						break;
					case 'Non-Taxable Other Earnings':
						$di_settings['non_tax'] = $di->basis_for_sss;
						break;
				}
			}
			
			if ($di_settings['basic'] == 'Yes') {
				$total += $bp;
			}
			
			// Compare to sss table
			$sss = $this->sss_share($total);
			return $sss;
		}else{
			return FALSE;
		}
			
	}
	
	/**
	 * No need to encrypt
	 * get philheath deduction
	 * @param decimal $salary_range
	 */
	public function philheath_share($salary_range)
	{
		$this->db->where($salary_range.' BETWEEN salary_range_from AND salary_range_to',NULL,FALSE);
		$q = $this->db->get('phil_health');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result->employer_share : false;
	}
	
	/**
	 * calculate deduction for philhealth
	 * @param int 	$company_id
	 * @param int 	$payroll_group_id
	 * @param int   $employee_id
	 * @param array $period
	 * @param $work_schedule_id
	 */
	public function get_deduction_income_philhealth($company_id,$payroll_group_id,$employee_id,$period,$work_schedule_id=NULL,$employee_basic_pay=NULL)
	{
		// check if employee salary is set
		$basic_pay = $this->get_basic_pay($company_id,$employee_id);
		
		if (!$basic_pay) {
			return false;	
		}
		
		// check new basic pay
		$edate = date('Y-m-d',strtotime($basic_pay->effective_date));
		// $pfrom = date('Y-m-d',strtotime($period->period_from));
		// $pto   = date('Y-m-d',strtotime($period->period_to));
		
		if (is_object($period)) {
			$pfrom = date('Y-m-d',strtotime($period->period_from));
			$pto   = date('Y-m-d',strtotime($period->period_to));
		} elseif (is_array($period)) {
			$pfrom = date('Y-m-d',strtotime($period['period_from']));
			$pto   = date('Y-m-d',strtotime($period['period_to']));
		}

		if ($basic_pay->effective_date) {
			if (($pfrom <= $edate && $pto >= $edate) || $edate <= $pfrom) {
				$bp = $basic_pay->new_basic_pay; 
			} else {
				$bp = $basic_pay->current_basic_pay;
			}
		} else {
			$bp = $basic_pay->current_basic_pay;
		}
		
		// add new check period type
		
		$check_period_type = $this->check_period_type($company_id,$payroll_group_id);
		if($check_period_type != FALSE){
			if($check_period_type->pay_rate_type == "By Hour"){
				$total_hours = 0;
				// get number of days
				$current = $pfrom;
				while($current <= $pto){
					$check_rd = $this->get_rest_day($company_id,$work_schedule_id,date("l",strtotime($current)));
					if(!$check_rd){
						$get_working_hours = $this->get_working_hours($company_id,$work_schedule_id,date("l",strtotime($current)));
						if($get_working_hours != FALSE) $total_hours += $get_working_hours;
					}
					$current = date("Y-m-d",strtotime($current." +1 day"));
				}
			
				// new basic pay
				$bp = $bp * $total_hours;
			}
		}
		
		// end check period type
		
		// check payroll group
		$payroll_group = $this->get_payroll_group($company_id, $payroll_group_id);
		if($payroll_group != FALSE){
			if ($payroll_group->pay_rate_type == 'By Day') {
				$bp = $bp * 26;
			}	
		}
		
		// EMPLOYEE BASIC PAY
		$bp = $employee_basic_pay;
		
		// get deduction income
		$result = $this->get_deduction_income($company_id);
		
		if($result != FALSE){
			
			$di_settings = array();
			$total = 0;
			
			// Getting all settings for philhealth
			foreach ($result as $di) {
				switch ($di->income) {
					case 'Basic Pay':
						$di_settings['basic'] = $di->basis_for_sss;
						break;
					case 'Overtime Pay':
						$di_settings['overtime'] = $di->basis_for_sss;
						break;
					case 'Holiday/Premium Pay':
						$di_settings['holiday'] = $di->basis_for_sss;
						break;
					case 'Night Shift Differential':
						$di_settings['night_shift'] = $di->basis_for_sss;
						break;
					case 'Taxable Other Earnings':
						$di_settings['tax'] = $di->basis_for_sss;
						break;
					case 'Non-Taxable Other Earnings':
						$di_settings['non_tax'] = $di->basis_for_sss;
						break;
				}
			}
			
			if ($di_settings['basic'] == 'Yes') {
				$total += $bp;
			}
			
			// Compare to philheath table
			$philhealth = $this->philheath_share($total);
			return $philhealth;
				
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Employee Commissions
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function employee_commissions($emp_id,$company_id){
		$s = array(
			"*","c.commission_scheme","c.amount","c.percentage","c.percentage_rate","c.commission_schedule","c.pay_schedule","c.schedule_date"
		);
		$this->db->select($s);
		$w = array(
			"c.emp_id"=>$emp_id,
			"c.company_id"=>$company_id,
			"c.status"=>"Active",
			"cs.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("commission_settings AS cs","cs.commission_settings_id = c.commission_settings_id","LEFT");
		$q = $this->db->get("commissions AS c");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Leave
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $current
	 */
	public function employee_leave($company_id,$emp_id,$current){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active",
			"leave_application_status"=>"approve",
			"date_start <= " => $current,
			"date_end >= " => $current
		);
		$this->db->where($w);
		$q = $this->db->get("employee_leaves_application");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get withholding tax method
	 * @param int $company_id
	 */
	public function get_withholding_tax_method($company_id)
	{
		$this->db->where('company_id',$company_id);
		$q = $this->db->get('withholding_tax_method');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * get the settings of the tax
	 * @param int $company_id
	 */
	public function get_tax_settings($company_id)
	{
		$this->db->where('company_id',$company_id);
		$q = $this->db->get('withholding_tax_settings');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result->compensation_type : false;
	}
	
	/**
	 * get payroll info
	 * @param int $company_id
	 * @param int $employee_id
	 */
	public function get_payroll_info($company_id,$employee_id)
	{
		$where = array(
			'company_id'  => $company_id,
			'emp_id' 	  => $employee_id,
			'employee_status'   => 'Active'
		);
		$this->db->where($where);
		$q = $this->edb->get('employee_payroll_information');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Employee Calculate income
	 * @param int $earnings
	 * @param int $company_id
	 * @param int $payroll_group_id
	 * @param int $employee_id
	 * @param mixed $period
	 * @param $work_schedule_id
	 */
	public function employee_calculate_income($earnings,$company_id,$payroll_group_id,$employee_id,$period,$work_schedule_id=NULL)
	{
		// check if employee salary is set
		$basic_pay = $this->get_basic_pay($company_id,$employee_id);
		
		if (!$basic_pay) {
			return false;
		}
		
		$payroll = $period;
		
		$bp = $day_per_year = 0;
		
		// check new basic pay
		$edate = date('Y-m-d',strtotime($basic_pay->effective_date));
		if (is_object($period)) {
			$pfrom = date('Y-m-d',strtotime($period->period_from));
			$pto   = date('Y-m-d',strtotime($period->period_to));	
		} elseif (is_array($period)) {
			$pfrom = date('Y-m-d',strtotime($period['period_from']));
			$pto   = date('Y-m-d',strtotime($period['period_to']));
		}

		if ($basic_pay->effective_date) {
			if (($pfrom <= $edate && $pto >= $edate) || $edate <= $pfrom) {
				$bp = $basic_pay->new_basic_pay; 
			} else {
				$bp = $basic_pay->current_basic_pay;
			}
		} else {
			$bp = $basic_pay->current_basic_pay;
		}
		
		// add new check period type
		
		$check_period_type = $this->check_period_type($company_id,$payroll_group_id);
		if($check_period_type != FALSE){
			if($check_period_type->pay_rate_type == "By Hour"){
				$total_hours = 0;
				// total hours
				$check_employee_timein = $this->check_employee_timein($employee_id,$company_id,$pfrom,$pto);
					if($check_employee_timein != FALSE){
						foreach($check_employee_timein as $row_timein){
							$current = $row_timein->date;
							$check_rd = $this->get_rest_day($company_id,$work_schedule_id,date("l",strtotime($current)));
							
							if(!$check_rd){
								// check employee timein
								if($check_employee_timein){
									$get_working_hours = $this->get_working_hours($company_id,$work_schedule_id,date("l",strtotime($current)));
									if($get_working_hours != FALSE) $total_hours += $get_working_hours;
								}
							}		
						}
					}

				// new basic pay
				$bp = $bp * $total_hours;	
			}
		}
		
		// end check period type
		
		// check payroll group
		$payroll_group = $this->get_payroll_group($company_id, $payroll_group_id);
		if($payroll_group != FALSE){
			if ($payroll_group->pay_rate_type == 'By Day') {
				$bp = $bp * 26;
			}	
		}
		
		// workday
		$workday = $this->get_workday($company_id,$work_schedule_id);
		
		// check company plan
		$check_company_plan = $this->prm->check_company_plan($this->session->userdata('account_id'));
		
		if($check_company_plan != FALSE){
		
			// ASHIMA PLAN
			if($check_company_plan->choose_plans_id == $this->config->item("ashima_lite")){ // lite
				$flag_ashima_lite = 1;
			}else if($check_company_plan->choose_plans_id == $this->config->item("ashima_engaged")){ // engaged
				$flag_ashima_lite = 0;
			}
		
		}else{
			$flag_ashima_lite = 0;
		}
		
		if(!$workday && $flag_ashima_lite == 0){ // ashima engaged
			$income['net'] = array(
				'basic_pay' => 0,
				'extra_pay' => 0
			);
			$income['regular'] = array(
				'basic_pay' => 0,
				'extra_pay' => 0
				// 'extra_pay' => - $absent_rate - $tardiness_rate - $undertime_rate
			);
			$income['basic'] = array(
				'basic_pay' => 0,
				'extra_pay' => 0
			);
			
			return $income;
		}
		
		// CHECK MINIMUM WAGE SETTINGS
		if($earnings["minimum_wage_earner"] != ""){
			$bp = $earnings["minimum_wage_earner_default_basic_pay"];
		}
		
		// MINIMUM WAGE EARNER FOR COLA
		$minimum_wage_earner_cola = $earnings["minimum_wage_earner_cola"];
		
		// absent rate
		$absent_rate = $earnings["absent_rate"];
		
		// tardiness
		$tardiness_rate = $earnings["tardiness_rate"];
		
		// undertime
		$undertime_rate = $earnings["undertime_rate"];
		
		// overtime
		$overtime_rate = $earnings["overtime_rate"];
		
		// night differential
		$night_diff_rate = $earnings["night_diff_rate"];
		
		// holiday rate
		$holiday_rate = $earnings["holiday_rate"];
		
		// carry over
		$total_carry_over = $earnings["total_carry_over"];
		
		// EMPLOYEE COMMISSIONS
		$total_employee_commission = isset($earnings["total_employee_commission"]);
		
		// ABSENT RATE
		if($check_period_type != FALSE){
			$absent_rate = ($check_period_type->pay_rate_type == "By Hour") ? 0 : $absent_rate ;
		}else{
			$absent_rate = 0;
		}
		
		// PAYROLL CALENDAR
		$q1 = $this->get_payroll_calendar($company_id,$period->payroll_period,$payroll_group_id);
		
		// FIXED ALLOWANCES
		$remaining_taxable_allowances = $earnings["remaining_taxable_allowances"]; // remaining taxable allowances				
		
		// ADVANCE PAYMENT
		$advance_amount = $earnings["advance_amount"];
		
		// EXCESS FOR DE MINIMIS
		$de_minimis_excess = $earnings["de_minimis_excess"];
		
		// SERVICE CHARGE TAXABLE
		$service_charge_taxable = $earnings["service_charge_taxable"];
		
		// HAZARD PAY
		$hazard_pay_taxable = $earnings["hazard_pay_taxable"];
		
		// OTHER EARNINGS FOR ASHIMA LITE ONLY
		$employee_other_earnings_taxable = $earnings["employee_other_earnings_taxable"];
		
		// PAID LEAVE WITH TIME IN
		if(isset($earnings["total_paid_leave_with_time_in"]) != ""){
			$total_paid_leave_with_time_in = $earnings["total_paid_leave_with_time_in"];
		}else{
			$total_paid_leave_with_time_in = 0;
		}
		
		// $remaining_taxable_allowances + 
		// + $total_employee_commission
		$extra_pay_net =  $overtime_rate + $night_diff_rate + $holiday_rate - $absent_rate - $tardiness_rate - $undertime_rate + $total_carry_over + 
		$advance_amount + $minimum_wage_earner_cola + $de_minimis_excess + $service_charge_taxable + $hazard_pay_taxable + $employee_other_earnings_taxable + $total_paid_leave_with_time_in;
		
		// $remaining_taxable_allowances + 
		// + $total_employee_commission
		$extra_pay_regular = $absent_rate - $tardiness_rate - $undertime_rate + 
		$advance_amount + $minimum_wage_earner_cola + $de_minimis_excess + $service_charge_taxable + $hazard_pay_taxable + $employee_other_earnings_taxable;
		
		$income['net'] = array(
			'basic_pay' => $bp,
			'extra_pay' => $extra_pay_net
		);
		$income['regular'] = array(
			'basic_pay' => $bp,
			'extra_pay' => $extra_pay_regular
			// 'extra_pay' => - $absent_rate - $tardiness_rate - $undertime_rate
		);
		$income['basic'] = array(
			'basic_pay' => $bp,
			'extra_pay' => 0
		);
		
		if($this->input->get("breakdown") > 0){
			
			// Allowances: {$remaining_taxable_allowances} <br>
			// <br> remaining_taxable_allowances: $remaining_taxable_allowances +
			// <br> remaining_taxable_allowances: $remaining_taxable_allowances +
			// Commission: {$total_employee_commission} <br>
			//  + <br> total_employee_commission: $total_employee_commission
			
			print "Taxable<br>
					Ajustment: {$total_carry_over} <br>
					Service Charge: {$service_charge_taxable} <br>
					Hazard Pay: {$hazard_pay_taxable} <br>
					De Minimis Excess: {$de_minimis_excess} <br><br>
				";
			
			print "If Withholding Tax Computation Rules = (Net Taxable Compensation Income): <br>";
			print "Extra Pay Net: $extra_pay_net = overtime_rate: $overtime_rate + 
			<br> night_diff_rate: $night_diff_rate +
			<br> holiday_rate: $holiday_rate - 
			<br> absent_rate: $absent_rate - 
			<br> tardiness_rate: $tardiness_rate - 
			<br> undertime_rate: $undertime_rate + 
			<br> total_carry_over: $total_carry_over + 
			<br> advance_amount: $advance_amount + 
			<br> minimum_wage_earner_cola: $minimum_wage_earner_cola + 
			<br> de_minimis_excess: $de_minimis_excess + 
			<br> service_charge_taxable: $service_charge_taxable + 
			<br> hazard_pay_taxable: $hazard_pay_taxable + 
			<br> commission: {$total_employee_commission} +
			<br> employee_other_earnings_taxable: {$employee_other_earnings_taxable}
			<br><br>";
			
			print "If Withholding Tax Computation Rules = (Regular Taxable Compenstation Income): <br>";
			print "Extra Pay Regulay: $extra_pay_regular = 
			<br> absent_rate: $absent_rate - 
			<br> tardiness_rate: $tardiness_rate - 
			<br> undertime_rate: $undertime_rate + 
			<br> advance_amount: $advance_amount + 
			<br> minimum_wage_earner_cola: $minimum_wage_earner_cola + 
			<br> de_minimis_excess: $de_minimis_excess + 
			<br> service_charge_taxable: $service_charge_taxable + 
			<br> hazard_pay_taxable: $hazard_pay_taxable + 
			<br> employee_other_earnings_taxable: {$employee_other_earnings_taxable}
			<br><br>";
		}
		
		/* print "
			OT: {$overtime_rate} <br>
			ND: {$night_diff_rate} <br>
			Holiday Rate: {$holiday_rate} <br>
			Absent: {$absent_rate} <br>
			Tardi: {$tardiness_rate} <br>
			Undertime: {$undertime_rate} <br>
			CarryOver: {$total_carry_over} <br>
			Allowance: {$allowance} <br>
		"; */
		
		return $income;
	}
	
	/**
	 * Withholding Tax Schedule
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function withholding_tax_schedule($emp_id,$company_id){
		$w = array(
			"epi.emp_id"=>$emp_id,
			"epi.company_id"=>$company_id,
			"epi.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("employee_payroll_information AS epi","epi.rank_id = wtds.rank_id","INNER");
		$q = $this->db->get("withholding_tax_deduction_schedule AS wtds");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee First Extra Pay
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $pay_period
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $company_id
	 */
	public function employee_first_extra_pay($emp_id,$payroll_group_id,$pay_period,$company_id,$type){
		/* $w = array(
			"payroll_group_id"=>$payroll_group_id,
			"first_payroll_date < "=>$pay_period,
			#"cut_off_from"=>$period_from,
			#"cut_off_to"=>$period_to,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$this->db->order_by("first_payroll_date","DESC");
		$q = $this->db->get("payroll_calendar",1);
		$r = $q->row();
		if($r){
			$ww = array(
				"dpr.payroll_group_id"=>$payroll_group_id,
				"dpr.pay_period"=>$r->first_payroll_date,
				"dpr.period_from"=>$r->cut_off_from,
				"dpr.period_to"=>$r->cut_off_to,
				"dpr.company_id"=>$company_id,
				"dpr.view_status"=>"Closed",
				"dpr.status"=>"Active"
			);
			$this->db->where($ww);
			$this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","LEFT");
			$this->edb->join("draft_pay_runs AS dpr","dpr.payroll_group_id = epi.payroll_group_id","LEFT");
			$qq = $this->edb->get("payroll_payslip AS pp");
			$rr = $qq->result();
			if($rr){
				$total = 0;
				foreach($rr as $row){
					if($type == "net"){
						$total = $total + $row->overtime_pay + $row->night_diff + $row->sunday_holiday - $row->absences - 
						$row->tardiness_pay - $row->undertime_pay + $row->adjustment_1 + $row->nt_allowances + $row->advance_amount;
					}else if($type == "regular"){
						$total = $total + $row->absences - $row->tardiness_pay - $row->undertime_pay + $row->nt_allowances + $row->advance_amount;
					}
				}
				return ($total > $total) ? $total : 0 ;
			}else{
				return 0;
			}
		}else{
			return 0;
		} */
		
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"payroll_date < "=>$pay_period,
			"status"=>"Active"
		);
		$this->db->where($w);
		$this->db->order_by("payroll_date","DESC");
		$q = $this->edb->get("payroll_payslip",1);
		$row = $q->row();
		$q->free_result();
		if($row){
			$total = 0;
			if($type == "net"){
				$total = $total + $row->overtime_pay + $row->night_diff + $row->sunday_holiday - $row->absences 
				- $row->tardiness_pay - $row->undertime_pay + $row->adjustment_1 + $row->nt_allowances + $row->advance_amount 
				+ $row->service_charge_taxable + $row->hazard_pay_taxable;
			}else if($type == "regular"){
				$total = $total + $row->absences - $row->tardiness_pay - $row->undertime_pay + $row->nt_allowances + $row->advance_amount 
				+ $row->service_charge_taxable + $row->hazard_pay_taxable;
			}
			return $total;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Year To Date Payslip Information
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $year
	 * @param unknown_type $field
	 */
	public function year_to_date_payslip_information($emp_id,$company_id,$year,$field=NULL){
		
		$k = konsum_key();
		$this->db->select("SUM(AES_DECRYPT(pp.{$field},'{$k}')) AS total",FALSE,TRUE);
		$w = array(
			"pp.emp_id"=>$emp_id,
			"pp.company_id"=>$company_id,
			"YEAR(pp.payroll_date)"=>$year,
			"pp.status"=>"Active",
			"dpr.view_status"=>"Closed"
		);
		$this->db->where($w);
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","LEFT");
		$this->edb->join("draft_pay_runs AS dpr","dpr.payroll_group_id = epi.payroll_group_id && dpr.pay_period = pp.payroll_date","LEFT");
		$q = $this->edb->get("payroll_payslip AS pp");
		$r = $q->row();
		return ($r) ? $r->total : 0 ;
		
		/* $r = $q->result();
		$q->free_result();
		if($r){
			if($field != ""){
				$total = 0;
				foreach($r as $row){
					$total += $row->$field;
				}
				return $total;
			}else{
				return 0;
			}
		}else{
			return 0;
		} */
	}
	
	/**
	 * Check Payroll Period On Draft
	 * @param unknown_type $data_param
	 */
	public function old_check_payroll_period_on_draft($data_param){
		$w = array(
			// "payroll_group_id"=>$data_param["payroll_group_id"],
			"pay_period"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
			"period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
			"period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
			"company_id"=>$data_param["company_id"]
		);
		
		$this->db->where($w);
		$q = $this->db->get("draft_pay_runs");
		$r = $q->result();
		$q->free_result();
		$payroll_group_id = ($data_param["payroll_group_id"] == "") ? 0 : $data_param["payroll_group_id"] ;
		if($r){
			$flag = FALSE;
			foreach($r as $row){
				if($row->view_status == "Closed" || $row->view_status == "Waiting for approval"){
					if($payroll_group_id > 0 && $row->payroll_group_id != $payroll_group_id){
						$flag = FALSE;
					}else{
						$flag = TRUE;
					}
				}else if($row->payroll_group_id == $payroll_group_id && $row->view_status == "Open"){
					$flag = TRUE;
				}
				
				if($row->view_status == "Closed" || $row->view_status == "Waiting for approval"){
					if($payroll_group_id > 0 && $row->payroll_group_id != $payroll_group_id){
						// do nothing
					}else{
						// $flag = TRUE;
						return TRUE;
					}
				}
				
			}
			return $flag;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check Payroll Period On Draft
	 * @param unknown_type $data_param
	 */
	public function check_payroll_period_on_draft($data_param){
		// check if custom employee
		if($data_param["array_emp_id"] == "" && $data_param["payroll_group_id"] > 0){
			
			$w = array(
				"payroll_group_id"=>$data_param["payroll_group_id"],
				"pay_period"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
				"period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
				"period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
				"company_id"=>$data_param["company_id"]
			);
			
			$this->db->where($w);
			$this->db->where("(view_status = 'Closed' OR view_status = 'Waiting for approval')");
			$q = $this->db->get("draft_pay_runs");
			$r = $q->row();
			if($r){
				$res = array(
					"error"=>"Closed",
					"employee_list"=>NULL
				);
				return $res;
				exit;
			}
			
			$payroll_group_id = $data_param["payroll_group_id"];
			$company_id = $data_param["company_id"];
			$get_employee_by_payroll_group = $this->get_employee_by_payroll_group($payroll_group_id,$company_id);
			if($get_employee_by_payroll_group != FALSE){
				$employee_list = array();
				$flag = FALSE;
				foreach($get_employee_by_payroll_group as $row_new_res){
					// by payroll group
					$w = array(
						// "payroll_group_id"=>$data_param["payroll_group_id"],
						"payroll_group_id"=>$row_new_res->payroll_group_id,
						"pay_period"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
						"period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
						"period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
						"company_id"=>$data_param["company_id"]							
					);
						
					$this->db->where($w);
					$this->db->where("(view_status = 'Closed' OR view_status = 'Waiting for approval')");
					$q = $this->db->get("draft_pay_runs");
					$r = $q->result();
					$q->free_result();
					#return ($r) ? $r : FALSE ;
					
					if($r){
						// push employee info
						array_push($employee_list, $row_new_res->emp_id);
						$flag = TRUE;
					}else{
						// custom
						$w = array(
							"prc.emp_id"=>$row_new_res->emp_id,
							"prc.payroll_period"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
							"prc.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
							"prc.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
							"prc.company_id"=>$data_param["company_id"]
						);
						$this->db->where($w);
						$this->db->where("(dpr.view_status = 'Closed' OR dpr.view_status = 'Waiting for approval')");
						$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
						$q = $this->db->get("payroll_run_custom AS prc");
						$r = $q->row();
						if($r){
							// push employee info
							array_push($employee_list, $row_new_res->emp_id);
							$flag = TRUE;
						}
					}
				}
				
				if($flag){
					$res = array(
						"error"=>"Custom",
						"employee_list"=>$employee_list
					);
					return $res;
				}else{
					return FALSE;
				}
				
			}else{
				return FALSE;
			}
			
		}else{
			// custom list
			$str_error = "";
			$explode_emp_id = explode(",", $data_param["array_emp_id"]);
			for($x=0;$x<count($explode_emp_id);$x++){
				$emp_id = $explode_emp_id[$x];
				// check from payroll custom list
				$w = array(
					"pp.emp_id"=>$emp_id,
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
				$r = $q->row();
				$q->free_result();
				if($r){
					$str_error .= "Error {$emp_id} <br>";
				}else{
					$w = array(
						"pp.emp_id"=>$emp_id,
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
					$this->db->join("draft_pay_runs AS dpr","epi.payroll_group_id = dpr.payroll_group_id","LEFT");
					$q = $this->db->get("payroll_payslip AS pp");
					$r = $q->row();
					$q->free_result();
					$str_error .= ($r) ? "Error {$emp_id} <br>" : "" ;
				}
				
			}

			if($str_error != ""){
				$res = array(
					"error"=>"Custom",
					"employee_list"=>$str_error
				);
				return $res;
			}else{
				return FALSE;
			}
			
			// return ($str_error > "") ? $str_error : FALSE ;
		}
	}
	
	/**
	 * Get Employee By Payroll Group
	 * @param unknown $payroll_group_id
	 * @param unknown $company_id
	 */
	public function get_employee_by_payroll_group($payroll_group_id,$company_id){
		$w = array(
			"payroll_group_id"=>$payroll_group_id,
			"company_id"=>$company_id,
			"status"=>"Active",
		);
		$this->db->where($w);
		$q = $this->db->get("employee_payroll_information");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Payroll Run Status
	 * @param unknown_type $payroll_period
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $v
	 */
	public function check_payroll_run_status($draft_pay_run_id,$payroll_period,$period_from,$period_to,$company_id){
		$w = array(
			"draft_pay_run_id"=>$draft_pay_run_id,
			"pay_period"=>$payroll_period,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"company_id"=>$company_id,
			"status"=>"Active",
			"view_status"=>"Closed"	
			// "view_status !="=>"Open"
		);
		$this->db->where($w);
		$this->db->group_by("view_status");
		$q = $this->db->get("draft_pay_runs");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Admin Password / Company Owner Password
	 * @param unknown_type $mypass
	 * @param unknown_type $company_id
	 */
	public function check_admin_pass($mypass,$company_id){
		$s = array(
			"accounts.account_id",
			"company_owner.last_name",
			"company_owner.middle_name",
			"company_owner.first_name",
			"accounts.email",
			"payroll_system_account.sub_domain",
			"payroll_system_account.`name`",
			"assigned_company.assigned_company_id",
			"assigned_company.company_id",
			"assigned_company.payroll_system_account_id",
			"assigned_company.deleted",
			"company.company_name",
			"company.number_of_employees",
			"accounts.`password`"
		);
		
		$w = array(
			"accounts.user_type_id"=>"2",
			"assigned_company.company_id"=>$company_id
		);
		#$this->edb->select($s);
		$this->edb->where($w);
		$this->db->where("accounts.`password`",$mypass);
		$this->edb->join("company_owner","accounts.account_id = company_owner.account_id","INNER");
		$this->edb->join("payroll_system_account","accounts.account_id = payroll_system_account.account_id","INNER");
		$this->edb->join("assigned_company","payroll_system_account.payroll_system_account_id = assigned_company.payroll_system_account_id","INNER");
		$this->edb->join("company","assigned_company.company_id = company.company_id","INNER");
		$q = $this->edb->get("accounts");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Admin Password / Company Owner Password
	 * @param unknown_type $mypass
	 * @param unknown_type $company_id
	 */
	public function admin_information($company_id){
		$s = array(
			"accounts.account_id",
			"company_owner.last_name",
			"company_owner.middle_name",
			"company_owner.first_name",
			"accounts.email",
			"payroll_system_account.sub_domain",
			"payroll_system_account.`name`",
			"assigned_company.assigned_company_id",
			"assigned_company.company_id",
			"assigned_company.payroll_system_account_id",
			"assigned_company.deleted",
			"company.company_name",
			"company.number_of_employees",
			"accounts.`password`"
		);
		
		$w = array(
			"accounts.user_type_id"=>"2",
			"assigned_company.company_id"=>$company_id
		);
		#$this->edb->select($s);
		$this->edb->where($w);
		$this->edb->join("company_owner","accounts.account_id = company_owner.account_id","INNER");
		$this->edb->join("payroll_system_account","accounts.account_id = payroll_system_account.account_id","INNER");
		$this->edb->join("assigned_company","payroll_system_account.payroll_system_account_id = assigned_company.payroll_system_account_id","INNER");
		$this->edb->join("company","assigned_company.company_id = company.company_id","INNER");
		$q = $this->edb->get("accounts");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Generate Payslip
	 * @param unknown_type $company_id
	 */
	public function check_generate_payslip($company_id,$payroll_param){
		
		$w = array(
			"company_id"=>$company_id,
			"draft_pay_run_id"=>$payroll_param["draft_pay_run_id"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("draft_pay_runs");
		$r = $q->row();
		
		if($r){
			$w2 = array(
				"comp_id"=>$company_id,
				"payroll_period"=>$payroll_param["payroll_period"],
				"period_from"=>$payroll_param["period_from"],
				"period_to"=>$payroll_param["period_to"],
				"payroll_status"=>"approved",
				"generate_payslip"=>"No",
				"token"=>$r->token
			);
			$this->db->where($w2);
			$q2 = $this->db->get("approval_payroll");
			$r = $q2->row();
			$q2->free_result();
			return ($r) ? true : false ;
		}else{
			return FALSE;
		}
	}
	
	/**
     * Check Bank Settings
     * @param unknown_type $comp_id
     */
    public function check_bank_settings($comp_id){
    	$w = array(
    		"comp_id"=>$comp_id,
    		"status"=>"Active"
    	);
    	$this->db->where($w);
    	$q = $this->db->get("bank_settings");
    	$r = $q->row();
    	$q->free_result();
    	return ($r) ? $r : FALSE ;
    }
    
    /**
     * Check Bank Settings
     * @param unknown_type $comp_id
     */
    public function new_bank_settings($comp_id){
    	$w = array(
    			"comp_id"=>$comp_id,
    			"status"=>"Active"
    	);
    	$this->db->where($w);
    	$q = $this->db->get("bank_settings");
    	$r = $q->result();
    	return ($r) ? $r : FALSE ;
    }
    
    /**
     * Check Bank Settings
     * @param unknown_type $comp_id
     */
    public function new_check_bank_settings($comp_id,$bank_name){
    	$w = array(
    		"comp_id"=>$comp_id,
    		"status"=>"Active",
    		"bank_name"=>$bank_name    			
    	);
    	$this->db->where($w);
    	$q = $this->db->get("bank_settings");
    	$r = $q->row();
    	return ($r) ? $r : FALSE ;
    }
    
	/**
	 * Get Company Information
	 * @param unknown_type $company_id
	 */
	public function company_information($company_id){
		$s = array(
			"accounts.account_id",
			"company_owner.last_name",
			"company_owner.middle_name",
			"company_owner.first_name",
			"accounts.email",
			"payroll_system_account.sub_domain",
			"payroll_system_account.`name`",
			"assigned_company.assigned_company_id",
			"assigned_company.company_id",
			"assigned_company.payroll_system_account_id",
			"assigned_company.deleted",
			"company.company_name",
			"company.number_of_employees",
			"accounts.`password`"
		);
		
		$w = array(
			"accounts.user_type_id"=>"2",
			"assigned_company.company_id"=>$company_id
		);
		#$this->edb->select($s);
		$this->edb->where($w);
		$this->edb->join("company_owner","accounts.account_id = company_owner.account_id","INNER");
		$this->edb->join("payroll_system_account","accounts.account_id = payroll_system_account.account_id","INNER");
		$this->edb->join("assigned_company","payroll_system_account.payroll_system_account_id = assigned_company.payroll_system_account_id","INNER");
		$this->edb->join("company","assigned_company.company_id = company.company_id","INNER");
		$q = $this->edb->get("accounts");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Commission Settings
	 * @param unknown_type $company_id
	 */
	public function commission_settings($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("commission_settings");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Overtime Rate
	 * @param unknown_type $company_id
	 */
	public function overtime_rate($company_id)
	{
		$where = array(
			'hours_type.company_id' => $company_id,
			'overtime_type.company_id' => $company_id,
		);
		$this->db->where($where);
		$this->db->join('hours_type','hours_type.hour_type_id = overtime_type.hour_type_id','INNER');
		$q = $this->db->get('overtime_type');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Ger houRs type for earnings
	 * @param unknown_type $company_id
	 */
	public function get_hourstypes_for_earnings($company_id) 
	{
		$where = array(
			'company_id' => $company_id,
			'status'	 => 'Active',
			'default'	 => 0,
		);
		$this->db->where($where);
		$q = $this->db->get('hours_type');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Check ND Settings Premium
	 * @param unknown_type $company_id
	 */
	public function check_nd_settings_add_to_premium($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active",
			"flag_add_prem"=>1
		);
		$this->db->where($w);
		$this->db->group_by("flag_add_prem");
		$q = $this->db->get('nightshift_differential_for_premium');
		$row = $q->row();
		$q->free_result();
		return ($row) ? $row : false;
	}
	
	/**
	 * Hours Type ND Settings Earnings
	 * @param unknown_type $company_id
	 */
	public function hours_type_nd_settings_earnings($company_id){
		$s = array(
			"*","nd.pay_rate AS pay_rate"
		);
		$this->db->select($s);
		$w = array(
			"nd.company_id"=>$company_id,
			"nd.status"=>"Active",
			"nd.flag_add_prem"=>1
		);
		$this->db->where($w);
		$this->db->join("hours_type AS ht","ht.hour_type_id = nd.hours_type_id","INNER");
		$q = $this->db->get('nightshift_differential_for_premium AS nd');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Adjustment for workday
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $param_data
	 */
	public function adjustment_workday($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get('payroll_carry_over_workday');
		$result = $q->result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Adjustment for Overtime
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $param_data
	 */
	public function adjustment_overtime($emp_id,$company_id,$param_data){
		$w = array(
			"pcoo.company_id"=>$company_id,
			"pcoo.emp_id"=>$emp_id,
			"pcoo.payroll_period"=>$param_data["payroll_period"],
			"pcoo.period_from"=>$param_data["period_from"],
			"pcoo.period_to"=>$param_data["period_to"],
			"pcoo.status"=>"Active"
		);
		$this->db->where($w);
		// $this->db->join("hours_type AS ht","ht.hour_type_id = pcoo.hours_type_id","INNER");
		$this->db->order_by("pcoo.date","ASC");
		$q = $this->db->get('payroll_carry_over_adjustment_overtime AS pcoo');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Adjustment for Holiday
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $param_data
	 */
	public function adjustment_holiday($emp_id,$company_id,$param_data){
		$w = array(
			"pcoh.company_id"=>$company_id,
			"pcoh.emp_id"=>$emp_id,
			"pcoh.payroll_period"=>$param_data["payroll_period"],
			"pcoh.period_from"=>$param_data["period_from"],
			"pcoh.period_to"=>$param_data["period_to"],
			"pcoh.status"=>"Active"
		);
		$this->db->where($w);
		// $this->db->join("hours_type AS ht","ht.hour_type_id = pcoh.hours_type_id","INNER");
		$this->db->order_by("pcoh.date","ASC");
		$q = $this->db->get('payroll_carry_over_adjustment_holiday AS pcoh');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	
	/**
	 * Adjustment for Night Differential
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $param_data
	 */
	public function adjustment_night_differential($emp_id,$company_id,$param_data){
		$w = array(
			"pcond.company_id"=>$company_id,
			"pcond.emp_id"=>$emp_id,
			"pcond.payroll_period"=>$param_data["payroll_period"],
			"pcond.period_from"=>$param_data["period_from"],
			"pcond.period_to"=>$param_data["period_to"],
			"pcond.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("hours_type AS ht","ht.hour_type_id = pcond.hours_type_id","INNER");
		$q = $this->db->get('payroll_carry_over_adjustment_night_differential AS pcond');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Adjustment for Night Differential no Holiday Rate
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $param_data
	 */
	public function adjustment_night_differential_no_holiday_rate($emp_id,$company_id,$param_data){
		$w = array(
			"pcond.company_id"=>$company_id,
			"pcond.emp_id"=>$emp_id,
			"pcond.payroll_period"=>$param_data["payroll_period"],
			"pcond.period_from"=>$param_data["period_from"],
			"pcond.period_to"=>$param_data["period_to"],
			"pcond.status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get('payroll_carry_over_adjustment_night_differential AS pcond');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Adjustment for Night Differential
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $param_data
	 */
	public function adjustment_paid_leave($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get('payroll_carry_over_adjustment_paid_leave');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}

	/**
	 * Adjustment for Allowances
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $param_data
	 */
	public function adjustment_allowances($emp_id,$company_id,$param_data){
		$w = array(
			"pca.company_id"=>$company_id,
			"pca.emp_id"=>$emp_id,
			"pca.payroll_period"=>$param_data["payroll_period"],
			"pca.period_from"=>$param_data["period_from"],
			"pca.period_to"=>$param_data["period_to"],
			"pca.status"=>"Active"
		);
		$this->db->where($w);
		// $this->db->join("allowance_settings AS ast","ast.allowance_settings_id = pca.allowance_settings_id","LEFT");
		$this->db->join("allowance_settings AS ast","ast.allowance_settings_id = pca.allowance_settings_id","INNER");
		$q = $this->db->get('payroll_carry_over_allowances AS pca');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Adjustment for Commission
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $param_data
	 */
	public function adjustment_commission($emp_id,$company_id,$param_data){
		$w = array(
			"pce.company_id"=>$company_id,
			"pce.emp_id"=>$emp_id,
			"pce.payroll_period"=>$param_data["payroll_period"],
			"pce.period_from"=>$param_data["period_from"],
			"pce.period_to"=>$param_data["period_to"],
			"pce.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("commission_settings AS cs","cs.commission_settings_id = pce.earning_id","LEFT");
		$q = $this->db->get('payroll_carry_over_earnings AS pce');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Adjustment for De Minimis
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $param_data
	 */
	public function adjustment_deminimis($emp_id,$company_id,$param_data){
		$w = array(
			"pcdm.company_id"=>$company_id,
			"pcdm.emp_id"=>$emp_id,
			"pcdm.payroll_period"=>$param_data["payroll_period"],
			"pcdm.period_from"=>$param_data["period_from"],
			"pcdm.period_to"=>$param_data["period_to"],
			"pcdm.status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get('payroll_carry_over_de_minimis AS pcdm');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Adjustment Advance Payment
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function adjustment_advance_payment($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get('payroll_carry_over_advance_payment');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Check Employee Loans
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $pay_period
	 */
	public function check_employee_loans($emp_id,$company_id,$pay_period){
		$w = array(
			"ld.emp_id"=>$emp_id,
			"ld.company_id"=>$company_id,
			"ld.status"=>"Active",
			"ashed.payroll_date"=>date("m/d/Y",strtotime($pay_period)),
			"ld.status"=>"Active",
			"lt.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("loans_deductions AS ld","ld.loan_deduction_id = ashed.deduction_id","LEFT");
		$this->db->join("loan_type AS lt","lt.loan_type_id = ld.loan_type_id","LEFT");
		$q = $this->db->get("amortization_schedule AS ashed");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Minimum Wage Earner
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 */
	public function check_minimum_wage_earner($company_id,$emp_id){
		$s = array("minimum_wage_earner");
		$this->db->select($s);
		
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("employee_payroll_information");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Government Loans
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function check_employee_government_loans($emp_id,$company_id){
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
		$this->db->where("(gl.status = 'Active' OR gl.status = 'active')");
		$this->db->join("government_loans AS gl","gl.loan_type_id = gld.loan_type_id","LEFT");
		$q = $this->db->get("gov_loans_deduction AS gld");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * 
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $loan_deduction_id
	 * @param unknown $param_data
	 */
	public function check_government_amortization($emp_id,$company_id,$loan_deduction_id,$param_data){
		$s = array(
			"COUNT(payroll_run_government_loan_id) as total"
		);
		$this->db->select($s);
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"loan_deduction_id"=>$loan_deduction_id,
			"payroll_period !=" => $param_data["payroll_period"],
			"period_from !=" => $param_data["period_from"],
			"period_to !=" => $param_data["period_to"],
			"status"=>"Active",
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_run_government_loans");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : 0 ;
	}
	
	/**
	 * Check Flexible Overtime Date
	 * @param unknown $overtime_id
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $date
	 * @param unknown $pay_period
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function check_flexible_overtime_date($overtime_id,$emp_id,$company_id,$date,$pay_period,$period_from,$period_to){
		$w = array(
			"company_id"=>$company_id,
			"overtime_id"=>$overtime_id, // new field
			"emp_id"=>$emp_id,
			"date"=>date("Y-m-d",strtotime($date)),
			"payroll_period" => $pay_period,
			"period_from" => $period_from,
			"period_to" => $period_to,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("flexible_overtime");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Flexible Schedule
	 * @param unknown $company_id
	 * @param unknown $work_schedule_id
	 */
	public function check_flexible_schedule($company_id,$work_schedule_id){
		$w = array(
			"company_id"=>$company_id,
			"work_schedule_id"=>$work_schedule_id,
			"not_required_login"=>1
		);
		$this->db->where($w);
		$q = $this->db->get("flexible_hours");
		$r = $q->row();
		// return ($r) ? $r : FALSE ;
		$q->free_result();
		return TRUE; // temporary static value
	}
	
	/**
	 * Check Employee Period Type
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function check_employee_period_type($emp_id,$company_id){
		$s = array(
			"pg.period_type",
			"pg.pay_rate_type"
		);
		$this->db->select($s);
		
		$w = array(
			"epi.emp_id"=>$emp_id,
			"epi.company_id"=>$company_id,
			"epi.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$q = $this->db->get("employee_payroll_information AS epi");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Adjustment Absences
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function adjustment_absences($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get('payroll_carry_over_adjustment_absences');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Adjustment Tardiness
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function adjustment_tardiness($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get('payroll_carry_over_adjustment_tardiness');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Adjustment Undertime
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function adjustment_undertime($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get('payroll_carry_over_adjustment_undertime');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Adjustment Third Party Loans
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function adjustment_third_party_loans($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get('payroll_carry_over_adjustment_third_party_loan');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Employee Government Loans
	 * @param unknown $company_id
	 */
	public function employee_government_loans($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get('government_loans');
		$result = $q->result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Adjustment Third Party Loans
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function adjustment_government_loans($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get('payroll_carry_over_adjustment_government_loan');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Employee Other Deductions Adjustment
	 * @param unknown $company_id
	 */
	public function deductions_other_deductions($company_id){
		$w = array(
			"comp_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get('deductions_other_deductions');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Adjustment Third Party Loans
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function adjustment_other_deductions($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get('payroll_carry_over_adjustment_other_deduction');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Employee Information this is via ajax
	 * @param unknown $employee_name
	 * @param unknown $company_id
	 */
	public function get_employees($employee_name,$company_id,$pay_schedule_dp,$pay_calculation_dp){
		$konsum_key = konsum_key();
		
		$this->db->where("
				e.company_id = {$company_id} AND pg.period_type = '{$pay_schedule_dp}' AND pg.pay_rate_type = 'By {$pay_calculation_dp}' AND
				epi.employee_status = 'Active' AND
				(convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$employee_name."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$employee_name."%'
			", NULL, FALSE); // encrypt
		
		$w = array(
			"pg.period_type"=>$pay_schedule_dp,
			"pg.pay_rate_type"=>"By {$pay_calculation_dp}",
			"e.company_id"=>$company_id,
			"e.status"=>"Active",
			"epi.employee_status"=>"Active"
		);
		$this->db->where($w);
		
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
		
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$this->edb->join("cost_center AS cc","cc.cost_center_id = epi.cost_center","LEFT");
		
		$q = $this->edb->get("employee AS e",5);
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Payroll Information
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function check_employee_payroll_information($emp_id,$company_id){
		
		$s = array(
			"epi.tax_status",
			"epi.payroll_group_id",
			"epi.rank_id",
			"epi.employment_type",
			"pg.period_type",
			"pg.pay_rate_type",
			"epi.entitled_to_deminimis",
			"epi.entitled_to_sss",
			"epi.entitled_to_hdmf",
			"epi.entitled_to_philhealth",
			"epi.position",
			"epi.date_hired",
			"epi.nightshift_differential_rule_name"
		);
		
		$this->edb->select($s);
		
		$w = array(
			"epi.emp_id"=>$emp_id,
			"epi.company_id"=>$company_id,
			"epi.status"=>"Active"
			#"e.status"=>"Active",
			#"e.company_id"=>$company_id,
		);
		$this->db->where($w);
		//$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$q = $this->edb->get("employee_payroll_information AS epi");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Custom List Period Type
	 * @param unknown $draft_pay_run_id
	 * @param unknown $company_id
	 */
	public function check_custom_list_period_type($draft_pay_run_id,$company_id){
		$w = array(
			"dpr.draft_pay_run_id"=>$draft_pay_run_id,
			"dpr.company_id"=>$company_id,
			"dpr.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("payroll_run_custom AS prc","prc.draft_pay_run_id = dpr.draft_pay_run_id","INNER");
		$this->db->join("employee_payroll_information AS epi","epi.emp_id = prc.emp_id","INNER");
		$this->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","INNER");
		$this->db->group_by("pg.period_type");
		$q = $this->db->get("draft_pay_runs AS dpr");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;	
	}
	
	/**
	 * Draft Pay Run Information
	 * @param unknown $val
	 * @param unknown $company_id
	 */
	public function draft_pay_run_information($val,$company_id){
		$w = array(
			"draft_pay_run_id"=>$val,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("draft_pay_runs");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Payroll Payslip
	 * @param unknown $params
	 */
	public function check_payroll_payslip($params){
		
		// CHECK TOKEN
		$draft_pay_run_token = $this->check_draft_pay_run_token($params["draft_pay_run_id"],$params["company_id"]);
		
		$w = array(
			"pp.payroll_date"=>$params["payroll_period"],
			"pp.period_from"=>$params["period_from"],
			"pp.period_to"=>$params["period_to"],
			"pp.company_id"=>$params["company_id"],
			"pp.status"=>"Active"
		);
		$this->db->where($w);
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = pp.emp_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$q = $this->edb->get("payroll_payslip AS pp");
		$r = $q->result();
		if($r){
			$no_of_employees_paid = 0;
			$total_net_amount = 0;
			
			// PARAMS
			$exclude_params = array(
				"payroll_period"=>date("Y-m-d",strtotime($params["payroll_period"])),
				"period_from"=>date("Y-m-d",strtotime($params["period_from"])),
				"period_to"=>date("Y-m-d",strtotime($params["period_to"])),
				"company_id"=>$params["company_id"]
			);
			
			foreach($r as $row){
				
				// check if excluded
				// $exclude = $this->get_exclude_list($params["company_id"],$row->emp_id);
				$exclude = $this->get_exclude_list($params["company_id"],$row->emp_id, $exclude_params);
				 
				// check payroll period status if already open
				$check_payroll_period_params = array(
					"draft_pay_run_token"=>$draft_pay_run_token,
					"payroll_period"=>$params["payroll_period"],
					"period_from"=>$params["period_from"],
					"period_to"=>$params["period_to"],
					"emp_id"=>$row->emp_id,
					"payroll_group_id"=>$row->payroll_group_id,
					"company_id"=>$params["company_id"]
				);
				$check_payroll_period = $this->check_payroll_period_for_approval($check_payroll_period_params);
				
				// CHECK DRAFT ID
				$check_draft_token_info = $this->check_draft_token_info($check_payroll_period_params);
				if(!$check_draft_token_info){
					// CHECK EMPLOYEE IF ALREADY CLOSED/PROCESSED
					$check_employee_closed = $this->check_employee_closed($check_payroll_period_params);
				}else{
					$check_employee_closed = FALSE;
				}
				
				if(!$exclude && !$check_payroll_period && !$check_employee_closed){
				// if(!$exclude && !$check_payroll_period){
					$no_of_employees_paid++;
					$total_net_amount += $row->net_amount;
				}
				
			}
			
			$data_result = array(
				"no_of_employees_paid"=>$no_of_employees_paid,
				"total_net_amount"=>$total_net_amount
			);
			
			return $data_result;
		}else{
			return FALSE;
		}	
	}
	
	/**
	 * Pay Run Information
	 * @param unknown $params
	 */
	public function pay_run_info($params){
		$w = array(
			"payroll_period"=>$params["payroll_period"],
			"period_from"=>$params["period_from"],
			"period_to"=>$params["period_to"],
			"comp_id"=>$params["company_id"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$this->db->order_by("approval_payroll_id","DESC");
		$q = $this->db->get("approval_payroll");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Information
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function employee_information($emp_id,$company_id){
		$w = array(
			"emp_id"=>$emp_id,
			#"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->edb->get("employee");
		$r = $q->row();
		$q->free_result();
		if($r){
			if($r->company_id == 0){
				return $r;
			}else{
				$w = array(
					"emp_id"=>$emp_id,
					"company_id"=>$company_id,
					"status"=>"Active"
				);
				$this->db->where($w);
				$q = $this->edb->get("employee");
				$r = $q->row();
				return ($r) ? $r : FALSE ;
			}
		}else{
			return FALSE;
		}
		#return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Payroll Payslip
	 * @param unknown $params
	 */
	public function employee_payroll_payslip($params){
		$w = array(
			"pp.payroll_date"=>$params["payroll_period"],
			"pp.period_from"=>$params["period_from"],
			"pp.period_to"=>$params["period_to"],
			"pp.company_id"=>$params["company_id"],
			"pp.status"=>"Active"
		);
		$this->db->where($w);
		$this->edb->join("employee AS e","e.emp_id = pp.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$q = $this->edb->get("payroll_payslip AS pp");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * HR Email Address
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function hr_email_query($emp_id,$company_id){
		$w = array(
			"e.emp_id"=>$emp_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$q = $this->edb->get("employee AS e");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * HR Email Address By ID
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function hr_email_query_by_account($account_id){
		/* $w = array(
			"a.account_id"=>$account_id
		);
		$this->db->where($w);
		$this->edb->join("accounts AS a","a.account_id = co.account_id","LEFT");
		$q = $this->edb->get("company_owner AS co");
		$r = $q->row();
	
		return ($r) ? $r : FALSE ; */
		
		$w = array(
			"a.account_id"=>$account_id
		);
		$this->db->where($w);
		$q = $this->edb->get("accounts AS a");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
		
	}
	
	/**
	 * Check Draft Pay Run ID From Payroll Approval
	 * @param unknown $token
	 */
	public function check_draft_pay_run_id($token){
		$w = array(
			"token"=>$token,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_payroll");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Payroll Information
	 * @param unknown $payroll_group_id
	 * @param unknown $company_id
	 */
	public function employee_payroll_information($payroll_group_id,$company_id){
		$w = array(
			"pg.payroll_group_id"=>$payroll_group_id,
			"pg.company_id"=>$company_id,
			"pg.status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_group AS pg");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Minimum Wage Earner Default Settings
	 * @param unknown $company_id
	 */
	public function check_minimum_wage_earner_default_settings($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("minimum_wages_default_settings");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Current Regional Daily Minimum Wage Table 
	 * @param unknown $company_id
	 */
	public function current_regional_daily_minimum_wage_table($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("minimum_wages_settings");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Payroll Status
	 * @param unknown $draft_pay_run_id
	 * @param unknown $company_id
	 */
	public function check_payroll_status($draft_pay_run_id,$company_id){
		$w = array(
			"draft_pay_run_id"=>$draft_pay_run_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("draft_pay_runs");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Payroll File Batch Number
	 * @param unknown $payroll_period
	 * @param unknown $company_id
	 */
	public function check_batch_number($payroll_period,$company_id){
		/*$month = date("m",strtotime($payroll_period));
		$year = date("Y",strtotime($payroll_period));
		
		$s = array(
			"COUNT(approval_payroll_id) AS total"
		);
		
		$this->db->select($s);
		
		$w = array(
			"YEAR(payroll_period)" => $year,
			"MONTH(payroll_period)" => $month,
			"comp_id"=>$company_id
		);
		$this->db->where($w);
		$this->db->group_by("");
		$q = $this->db->get("approval_payroll");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;*/
		
		$date = date("Y-m-d");
		
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_file_batch_number");
		$r = $q->row();
		$q->free_result();
		if($r){
			$w2 = array(
				"date"=>$date,
				"company_id"=>$company_id,
				"status"=>"Active"
			);
			$this->db->where($w2);
			$q2 = $this->db->get("payroll_file_batch_number");
			$r2 = $q2->row();
			$q2->free_result();
			if($r2){
				// update counter
				$batch_number = $r2->counter + 1;
				
				$update_val = array(
					"counter"=>$batch_number
				);
				$this->db->where($w2);
				$this->db->update("payroll_file_batch_number",$update_val);
				return $batch_number;
			}else{
				// update date [reset counter]
				
				$batch_number = 1;
				$update_val = array(
					"date"=>$date,
					"counter"=>$batch_number
				);
				$w2 = array(
					"company_id"=>$company_id,
					"status"=>"Active"
				);
				$this->db->where($w2);
				$this->db->update("payroll_file_batch_number",$update_val);
				return $batch_number;
			}
			
		}else{
			// insert new
			$val = array(
				"date"=>$date,
				"company_id"=>$company_id,
				"counter"=>"1"
			);
			$this->db->insert("payroll_file_batch_number",$val);
			return FALSE;
		}
	}
	
	/**
	 * Check Payroll Period [closed or waiting for approval]
	 * @param unknown $check_payroll_period_params
	 */
	public function check_payroll_period($check_payroll_period_params){
		
		// check from drafts
		$w = array(
			"pay_period"=>$check_payroll_period_params["payroll_period"],
			"period_from"=>$check_payroll_period_params["period_from"],
			"period_to"=>$check_payroll_period_params["period_to"],
			"payroll_group_id"=>$check_payroll_period_params["payroll_group_id"],
			"company_id"=>$check_payroll_period_params["company_id"],
			"status"=>"Active"
		);
		$this->db->where($w);
		// $this->db->where("(view_status = 'Closed' OR view_status = 'Waiting for approval')");
		$q = $this->db->get("draft_pay_runs");
		$r = $q->row();
		if($r){
			
			#return ($r->view_status == "Closed" || $r->view_status == "Waiting for approval") ? TRUE : FALSE ;
			
			$w = array(
				"prc.payroll_period"=>$check_payroll_period_params["payroll_period"],
				"prc.period_from"=>$check_payroll_period_params["period_from"],
				"prc.period_to"=>$check_payroll_period_params["period_to"],
				"prc.emp_id"=>$check_payroll_period_params["emp_id"],
				"prc.company_id"=>$check_payroll_period_params["company_id"],
				"prc.status"=>"Active"
			);
			$this->db->where($w);
			$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
			$q = $this->db->get("payroll_run_custom AS prc");
			$r2 = $q->row();
			if($r2){
				return FALSE;
			}else{
				return ($r->view_status == "Closed" || $r->view_status == "Waiting for approval") ? TRUE : FALSE ;
			}
			
		}else{
			$w = array(
				"prc.payroll_period"=>$check_payroll_period_params["payroll_period"],
				"prc.period_from"=>$check_payroll_period_params["period_from"],
				"prc.period_to"=>$check_payroll_period_params["period_to"],
				"prc.emp_id"=>$check_payroll_period_params["emp_id"],
				"prc.company_id"=>$check_payroll_period_params["company_id"],
				"prc.status"=>"Active"
			);
			$this->db->where($w);
			$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
			$q = $this->db->get("payroll_run_custom AS prc");
			$r = $q->row();
			if($r){
				
				#return ($r->view_status == "Closed" || $r->view_status == "Waiting for approval") ? TRUE : FALSE ;
				
				// check from drafts
				$w = array(
					"pay_period"=>$check_payroll_period_params["payroll_period"],
					"period_from"=>$check_payroll_period_params["period_from"],
					"period_to"=>$check_payroll_period_params["period_to"],
					"payroll_group_id"=>$check_payroll_period_params["payroll_group_id"],
					"company_id"=>$check_payroll_period_params["company_id"],
					"status"=>"Active"
				);
				$this->db->where($w);
				$q = $this->db->get("draft_pay_runs");
				$r2 = $q->row();
				if($r2){
					return FALSE;
				}else{
					return ($r->view_status == "Closed" || $r->view_status == "Waiting for approval") ? TRUE : FALSE ;
				}
				
			}else{
				return FALSE;
			}
		}
		
	}
	
	/**
	 * Check Draft Pay Run Token
	 * @param unknown $draft_pay_run_id
	 * @param unknown $company_id
	 */
	public function check_draft_pay_run_token($draft_pay_run_id,$company_id){
		$w = array(
			"company_id"=>$company_id,
			"draft_pay_run_id"=>$draft_pay_run_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("draft_pay_runs");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->token : "" ;
	}
	
	/**
	 * Check Payroll Period for approval [closed or waiting for approval]
	 * @param unknown $check_payroll_period_params
	 */
	public function check_payroll_period_for_approval($check_payroll_period_params){
		
		$payroll_period = $check_payroll_period_params["payroll_period"];
		$period_from = $check_payroll_period_params["period_from"];
		$period_to = $check_payroll_period_params["period_to"];
		$company_id = $check_payroll_period_params["company_id"];
		$emp_id = $check_payroll_period_params["emp_id"];
		
		// check from drafts
		$w = array(
			"token"=>$check_payroll_period_params["draft_pay_run_token"],
			"pay_period"=>$payroll_period,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"payroll_group_id"=>$check_payroll_period_params["payroll_group_id"],
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		// $this->db->where("(view_status = 'Closed' OR view_status = 'Waiting for approval')");
		$q = $this->db->get("draft_pay_runs");
		$r = $q->row();
		if($r){
			return ($r->view_status == "Closed" || $r->view_status == "Waiting for approval") ? FALSE : TRUE ;
		}else{
			$w = array(
				"dpr.token"=>$check_payroll_period_params["draft_pay_run_token"],
				"prc.payroll_period"=>$payroll_period,
				"prc.period_from"=>$period_from,
				"prc.period_to"=>$period_to,
				"prc.emp_id"=>$emp_id,
				"prc.company_id"=>$company_id,
				"prc.status"=>"Active",
				"dpr.payroll_group_id"=> 0
			);
			$this->db->where($w);
			$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
			$q = $this->db->get("payroll_run_custom AS prc");
			$r = $q->row();
			if($r){
				return ($r->view_status == "Closed" || $r->view_status == "Waiting for approval") ? FALSE : TRUE ;
			}else{
				return TRUE;
			}
		}
	}
	
	/**
	 * Check Previous Gross Pay
	 * @param unknown $prev_gross_pay_params
	 */
	public function previous_gross_pay($prev_gross_pay_params){
		$w = array(
			"emp_id"=>$prev_gross_pay_params["emp_id"],
			"company_id"=>$prev_gross_pay_params["company_id"],
			"payroll_date < "=>$prev_gross_pay_params["payroll_period"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$this->db->order_by("payroll_date","DESC");
		$q = $this->edb->get("payroll_payslip",1);
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->gross_pay : 0 ;
	}
	
	/**
	 * Check Payroll Period for approval [closed or waiting for approval]
	 * @param unknown $check_payroll_period_params
	 */
	public function check_payroll_period_for_approval_for_email($check_payroll_period_params){
		// check from drafts
		$w = array(
			"token"=>$check_payroll_period_params["draft_pay_run_token"],
			"pay_period"=>$check_payroll_period_params["payroll_period"],
			"period_from"=>$check_payroll_period_params["period_from"],
			"period_to"=>$check_payroll_period_params["period_to"],
			"payroll_group_id"=>$check_payroll_period_params["payroll_group_id"],
			"company_id"=>$check_payroll_period_params["company_id"],
			"status"=>"Active"
			// "view_status"=>"Open"
		);
		$this->db->where($w);
		$q = $this->db->get("draft_pay_runs");
		$r = $q->row();
		$q->free_result();
		if($r){
			return ($r) ? FALSE : TRUE ;
		}else{
			$w = array(
				"dpr.token"=>$check_payroll_period_params["draft_pay_run_token"],
				"prc.payroll_period"=>$check_payroll_period_params["payroll_group_id"],
				"prc.period_from"=>$check_payroll_period_params["period_from"],
				"prc.period_to"=>$check_payroll_period_params["period_to"],
				"prc.emp_id"=>$check_payroll_period_params["emp_id"],
				"prc.company_id"=>$check_payroll_period_params["company_id"],
				"prc.status"=>"Active"
				// "dpr.view_status"=>"Open"
			);
			$this->db->where($w);
			$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
			$q = $this->db->get("payroll_run_custom AS prc");
			$r = $q->row();
			$q->free_result();
			if($r){
				return ($r) ? FALSE : TRUE ;
			}else{
				return TRUE;
			}
		}
	}
	
	/**
	 * Check Fixed Contributions
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $string
	 */
	public function check_fixed_contributions($emp_id,$company_id,$string){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("employee_payroll_information");
		$r = $q->row();
		$q->free_result();
		if($r){
			return ($r->$string > 0) ? $r->$string : 0 ;
		}else{
			return 0;
		}
	}
	
	/**
	 * Check Company Profile
	 * @param unknown $company_id
	 */
	public function check_company_industry($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("company");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee De Minimis
	 * @param unknown $employee_de_minimis_params
	 */
	public function employee_de_minimis_excess($employee_de_minimis_params){
		$w = array(
			"company_id"=>$employee_de_minimis_params["company_id"],
			"emp_id"=>$employee_de_minimis_params["emp_id"],
			"payroll_period"=>$employee_de_minimis_params["payroll_period"],
			"period_from"=>$employee_de_minimis_params["period_from"],
			"period_to"=>$employee_de_minimis_params["period_to"]				
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_de_minimis");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Excluded
	 * @param unknown $company_id
	 * @param unknown $sort_param
	 */
	public function check_employee_excluded($company_id, $sort_param=NULL){
		$w = array(
			"exclude"=>"1",
			"company_id"=>$company_id,
			// BAG.O NPUD NI ZZZZZZZ para dili na global ang pag exclude sa employee, by payroll period na ang pag exclude ani tungod sa kadaghan versions zzzzzzzzzzz
			"status"=> "Active",
			"payroll_period"=>$sort_param["payroll_period"],
			"period_from"=>$sort_param["period_from"],
			"period_to"=>$sort_param["period_to"]
		);
		$this->db->where($w);
		$q = $this->db->get("exclude_list");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * 
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function check_employee_information($emp_id,$company_id){
		$w = array(
			"e.emp_id"=>$emp_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
		$q = $this->edb->get("employee AS e");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Service Charge
	 * @param unknown $service_charge_params
	 */
	public function check_employee_service_charge($service_charge_params){
		
		// VARIABLES
		$payroll_period = $service_charge_params["payroll_period"];
		$period_from = $service_charge_params["period_from"];
		$period_to = $service_charge_params["period_to"];
		$account_id = $service_charge_params["account_id"];
		$company_id = $service_charge_params["company_id"];
		
		$w = array(
			"esc.first_payroll_date"=>$payroll_period,
			"esc.cut_off_from"=>$period_from,
			"esc.cut_off_to"=>$period_to,
			"escd.account_id"=>$account_id,
			"esc.company_id"=>$company_id,
			"esc.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("employee_service_charge AS esc","esc.employee_service_charge_id = escd.employee_service_charge_id","LEFT");
		$this->db->join("location_and_offices AS lao","lao.location_and_offices_id = esc.location_and_offices_id","LEFT");
		$q = $this->db->get("employee_service_charge_detail AS escd");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Service Charge Settings
	 * @param unknown $company_id
	 */
	public function check_service_charge_settings($company_id){
		$w = array(
			"enabled"=>"yes",
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("service_charges");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Hazard Pay Settings
	 * @param unknown $company_id
	 */
	public function check_hazard_pay_settings($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("hazard_pay");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Hazard Pay (Check Employee Leave Application) for entitled leave employee
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param unknown $current
	 */
	public function hp_check_employee_leave_application($company_id,$emp_id,$current){
		$w = array(
			"status"=>"Active",
			"company_id"=>$company_id,
			"date_start <= " => date("Y-m-d",strtotime($current)),
			"date_end >= " => date("Y-m-d",strtotime($current))
		);
		$this->db->where($w);
		$q = $this->db->get("employee_leaves_application");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Hazard Pay Settings Enabled
	 * @param unknown $company_id
	 */
	public function check_hazard_pay_settings_enabled($company_id){
		$w = array(
			"enabled"=>"yes",
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("hazard_pay_settings");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Night Differential Rate
	 * @param unknown $company_id
	 */
	public function check_night_differential_rate($company_id,$type){
		$s = array(
			"*","ndp.pay_rate AS pay_rate"
		);
		$this->db->select($s);
		$w = array(
			"ht.company_id"=>$company_id,
			"ht.hour_type_name"=>$type,
			"ht.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("hours_type AS ht","ht.hour_type_id = ndp.hours_type_id","LEFT");
		$q = $this->db->get("nightshift_differential_for_premium AS ndp");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Allowances
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param unknown $allowance_settings_id
	 * @param unknown $param_data
	 */
	public function check_employee_allowances($company_id,$emp_id,$allowance_settings_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"allowance_id"=>$allowance_settings_id,
			"payroll_period != "=>$param_data["payroll_period"],
			"period_from != "=>$param_data["period_from"],
			"period_to != "=>$param_data["period_to"]
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_allowances");
		$r = $q->result();
		$q->free_result();
		return ($r) ? 1 : 0 ;
	}
	
	/**
	 * Check Hours Type
	 * @param unknown $company_id
	 * @param unknown $hours_type
	 */
	public function check_daily_rate($company_id,$hours_type){
		$w = array(
			"company_id"=>$company_id,
			"hour_type_name"=>$hours_type
		);
		$this->db->where($w);
		$q = $this->db->get("hours_type");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Allowances Once a month
	 * @param unknown $emp_id
	 * @param unknown $payroll_period
	 * @param unknown $company_id
	 */
	public function check_employee_allowance_once_a_month($emp_id,$payroll_period,$company_id){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"MONTH(payroll_period) != "=>date("m",strtotime($payroll_period)),
			"YEAR(payroll_period) != "=>date("Y",strtotime($payroll_period))
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_allowances");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Time In Information
	 * @param unknown $emp_id
	 * @param unknown $current
	 * @param unknown $company_id
	 */
	public function check_employee_time_in_info($emp_id,$current,$company_id){
		$w = array(
			"comp_id"=>$company_id,
			"emp_id"=>$emp_id,
			"date"=>date("Y-m-d",strtotime($current)),
			"status"=>"Active"
		);
		$this->db->where($w);
		$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
		$q = $this->db->get("employee_time_in");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Payroll Approval Toke
	 * @param unknown $draft_pay_run_id
	 * @param unknown $company_id
	 */
	public function check_payroll_approval_token($draft_pay_run_id,$company_id){
		$w = array(
			"company_id"=>$company_id,
			"view_status"=>"Waiting for approval",
			"status"=>"Active",
			"draft_pay_run_id"=>$draft_pay_run_id
		);
		$this->db->where($w);
		$q = $this->db->get("draft_pay_runs");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
		
		/* if($r){
			$w = array(
				"company_id"=>$company_id,
				"payroll_period"=>$r->pay_period,
				"period_from"=>$r->period_from,
				"period_to"=>$r->period_from,
				"status"=>"Active",
			);
			$this->db->where($w);
			$q = $this->db->get("approval_payroll");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		} */
	}
	
	/**
	 * Employee Third Party Loans
	 * @param unknown $params
	 */
	public function employee_third_party_loans($params){
		$w = array(
			"emp_id"=>$params["emp_id"],
			"company_id"=>$params["company_id"],
			"payroll_date"=>$params["payroll_period"],
			"period_from"=>$params["period_from"],
			"period_to"=>$params["period_to"]
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_run_loans");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Government Loans
	 * @param unknown $params
	 */
	public function employee_government_loans_for_approval($params){
		$w = array(
			"emp_id"=>$params["emp_id"],
			"company_id"=>$params["company_id"],
			"payroll_period"=>$params["payroll_period"],
			"period_from"=>$params["period_from"],
			"period_to"=>$params["period_to"]
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_run_government_loans");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Government Loans
	 * @param unknown $params
	 */
	public function employee_other_deduction_query($params){
		$w = array(
			"emp_id"=>$params["emp_id"],
			"company_id"=>$params["company_id"],
			"payroll_period"=>$params["payroll_period"],
			"period_from"=>$params["period_from"],
			"period_to"=>$params["period_to"]
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_for_other_deductions");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Payroll Information
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 */
	public function emp_payroll_info($emp_id,$comp_id){
		$w = array(
			"epi.emp_id"=>$emp_id,
			"epi.company_id"=>$comp_id
		);
		$this->db->where($w);
		$this->db->join("position AS p","p.position_id = epi.position","left");
		$q = $this->db->get("employee_payroll_information AS epi");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Filter Draft Pay Run
	 * @param unknown $company_id
	 * @param unknown $payroll_period
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function filter_draft_pay_run($company_id,$payroll_period,$period_from,$period_to){
		$w = array(
			//"dpr.payroll_group_id"=>$payroll_group_id,
			"dpr.pay_period"=>$payroll_period,
			"dpr.period_from"=>$period_from,
			"dpr.period_to"=>$period_to,
			"dpr.company_id"=>$company_id,
			"dpr.status"=>"Active",
			"dpr.flag_save"=>"1"
		);
		$this->db->where($w);
		$this->db->where("(dpr.view_status = 'Open' OR dpr.view_status = 'Rejected')");
		$this->db->join("payroll_group AS pg","pg.payroll_group_id = dpr.payroll_group_id","LEFT");
		$q = $this->db->get("draft_pay_runs AS dpr");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Calculate Payroll Cost
	 * @param unknown $calculate_params
	 */
	public function calculate_payroll_cost($calculate_params){
		$k = konsum_key();
		
		$employee_exclude_info = $this->employee_exclude($calculate_params["payroll_group_id"],$calculate_params["company_id"]);
		
		if($employee_exclude_info != FALSE){
			$not_in = array();
			foreach($employee_exclude_info["data"]["result"] as $row_e){
				//$not_in[] = $row_e->emp_id;
				array_push($not_in, $row_e->emp_id);
			}
			$this->db->where_not_in("pp.emp_id",$not_in);
		}
		
		$w = array(
			"pp.payroll_date"=>$calculate_params["payroll_period"],
			"pp.period_from"=>$calculate_params["period_from"],
			"pp.period_to"=>$calculate_params["period_to"],
			"pp.company_id"=>$calculate_params["company_id"],
			"pp.payroll_group_id"=>$calculate_params["payroll_group_id"],
		);
		$this->db->where($w);
		
		$this->db->select("SUM(AES_DECRYPT(pp.net_amount,'{$k}')) AS total",FALSE,TRUE);
		
		$q = $this->db->get("payroll_payslip AS pp");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Employee De Minimis Excess for Total taxable pay
	 * @param unknown $params
	 */
	public function employee_de_minimis_excess_by_rules($employee_de_minimis_params){
		$w = array(
			"company_id"=>$employee_de_minimis_params["company_id"],
			"emp_id"=>$employee_de_minimis_params["emp_id"],
			"payroll_period"=>$employee_de_minimis_params["payroll_period"],
			"period_from"=>$employee_de_minimis_params["period_from"],
			"period_to"=>$employee_de_minimis_params["period_to"],
			"rules"=>"Add to Non Taxable Income"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_de_minimis");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee De Minimis Excess for Total taxable pay: bisan pag less to taxable income ang rules sa de minimis
	 * i add gihapon ni sya sa non taxable kay pang tax shield rani sya nga de minimis
	 * @param unknown $params
	 */
	public function employee_de_minimis_excess_by_rules_if_less_taxable_income($employee_de_minimis_params){
		$w = array(
			"company_id"=>$employee_de_minimis_params["company_id"],
			"emp_id"=>$employee_de_minimis_params["emp_id"],
			"payroll_period"=>$employee_de_minimis_params["payroll_period"],
			"period_from"=>$employee_de_minimis_params["period_from"],
			"period_to"=>$employee_de_minimis_params["period_to"],
			"rules"=>"Less to Taxable Income"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_de_minimis");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Payroll Group Information
	 * @param unknown $payroll_group_id
	 * @param unknown $company_id
	 */
	public function get_payroll_group_info($payroll_group_id,$company_id){
		$w = array(
			"payroll_group_id"=>$payroll_group_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_group");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Payroll Approval Employee Commissions
	 * @param unknown $params
	 */
	public function payroll_approval_employee_commissions($params){
		$w = array(
			"company_id"=>$params["company_id"],
			"emp_id"=>$params["emp_id"],
			"payroll_period"=>$params["payroll_period"],
			"period_from"=>$params["period_from"],
			"period_to"=>$params["period_to"]
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_commission");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Allowances Settings
	 * @param unknown $company_id
	 */
	public function employee_allowances_settings($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("allowance_settings");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Commission Settings
	 * @param unknown $company_id
	 */
	public function employee_commission_settings($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("commission_settings");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Excess of De Minimis
	 * @param unknown $company_id
	 */
	public function excess_deminimis($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("deminimis");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Allowances
	 * @param unknown $allowance_settings_id
	 * @param unknown $company_id
	 */
	public function employee_allowances($emp_id,$allowance_settings_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"allowance_id"=>$allowance_settings_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"]
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_allowances");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Employee Commissions
	 * @param unknown $emp_id
	 * @param unknown $commission_id
	 * @param unknown $company_id
	 */
	public function get_employee_commissions($emp_id,$commission_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"commission_settings_id"=>$commission_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"]
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_commission");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee De Minimis
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function check_employee_deminimis($emp_id,$id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"de_minimis_id"=>$id,
			// "rules"=>"Add to Non Taxable Income",
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"]
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_de_minimis");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Adjustment for Service Charge
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function adjustment_service_charge($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_adjustment_service_charge");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Draft Pay Run ID
	 * @param unknown $draft_pay_run_id
	 * @param unknown $company_id
	 */
	public function check_draft_pay_run_id_array($draft_pay_run_id,$company_id){
		$w = array(
			"draft_pay_run_id"=>$draft_pay_run_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("draft_pay_runs");
		$r = $q->row();
		$q->free_result();
		if($r){
			$w = array(
				"token"=>$r->token,
				"company_id"=>$company_id,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("draft_pay_runs");
			$row = $q->result();
			$q->free_result();
			if($row){
				$array = array();
				foreach($row as $r){
					array_push($array,$r->draft_pay_run_id);
				}
				return $array;
			}else{
				return FALSE;
			}
			
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Reject Draft Pay Run ID
	 * @param unknown $token
	 * @param unknown $company_id
	 */
	public function reject_draft_pay_run_id($token,$company_id){
		$w = array(
			"token"=>$token,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("draft_pay_runs");
		$row = $q->result();
		$q->free_result();
		if($row){
			$array = array();
			foreach($row as $r){
				array_push($array,$r->draft_pay_run_id);
			}
			return $array;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Company Location
	 * @param unknown $company_id
	 */
	public function company_location($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("location_and_offices");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Company Tax Exception for Service Charge
	 * @param unknown $company_id
	 */
	public function service_charge_tax_exception($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("service_charges");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Company Hazard Pay Settings
	 * @param unknown $company_id
	 */
	public function hazard_pay_settings($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("hazard_pay");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Company Tax Exception for Service Charge
	 * @param unknown $company_id
	 */
	public function hazard_pay_tax_exception($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("hazard_pay");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Ajustment Hazard Pay
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function adjustment_hazard_pay($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_adjustment_hazard_pay");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Total Amount for Other Deductions
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $deduction_id
	 * @param unknown $employee_deduction_id
	 * @param unknown $param_data
	 */
	public function check_total_amount_other_deductions($emp_id,$company_id,$deduction_id,$employee_deduction_id,$param_data){
		$s = array(
			"COUNT(emp_id) AS total"
		);
		$this->db->select($s);
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			// "deduction_id"=>$deduction_id, // for engaged trapping
			"employee_deduction_id"=>$employee_deduction_id,
			"payroll_period != "=>$param_data["payroll_period"],
			"period_from != "=>$param_data["period_from"],
			"period_to != "=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_for_other_deductions");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Check Employee Absences Adjustment
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function check_absences_adjustment($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_adjustment_absences");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Tardiness Adjustment
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function check_tardiness_adjustment($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_adjustment_tardiness");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Undertime Adjustment
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function check_undertime_adjustment($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_adjustment_undertime");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Other Deductions Adjustment
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function check_other_deductions_adjustment($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_adjustment_other_deduction");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Payroll Group Default for Ashima Lite
	 * @param unknown $company_id
	 */
	public function payroll_group_default($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_group");
		$r = $q->result();
		$q->free_result();
		
		// INSERT PAYROLL GROUP DEFAULT FOR ASHIMA LITE
		if(!$r){
			
			// hourly
			$val_hourly = array(
				"company_id"=>$company_id,
				"name"=>"Hourly",
				"period_type"=>"Semi Monthly",
				"pay_rate_type"=>"By Hour"
			);
			$this->db->insert("payroll_group",$val_hourly);
			
			// daily
			$val_daily = array(
				"company_id"=>$company_id,
				"name"=>"Daily",
				"period_type"=>"Semi Monthly",
				"pay_rate_type"=>"By Day"
			);
			$this->db->insert("payroll_group",$val_daily);
			
			// monthly
			$val_monthly = array(
				"company_id"=>$company_id,
				"name"=>"Monthly",
				"period_type"=>"Semi Monthly",
				"pay_rate_type"=>"By Month"
			);
			$this->db->insert("payroll_group",$val_monthly);
			
		}
		
		return TRUE;
	}
	
	/**
	 * Check Company Plan
	 * @param unknown $account_id
	 */
	public function check_company_plan_old($account_id){
		$w = array(
			"a.account_id"=>$account_id
		);
		$this->db->where($w);
		$this->db->join("payroll_system_account AS psa","psa.payroll_system_account_id = a.payroll_system_account_id","LEFT");
		$q = $this->db->get("accounts AS a");
		$r = $q->row(); 
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}

	/**
	 * Check Company Plan
	 * @param unknown $account_id
	 */
	public function check_company_plan($account_id){
		/* old
		 $w = array(
		 "a.account_id"=>$account_id
		 );
		 $this->db->where($w);
		 $this->db->join("payroll_system_account AS psa","psa.payroll_system_account_id = a.payroll_system_account_id","LEFT");
		 $q = $this->db->get("accounts AS a");
		 $r = $q->row();
		 $q->free_result();
		 return ($r) ? $r : FALSE ;
		 */
		$w = array(
				"a.account_id"=>$account_id
		);
		$this->db->where($w);
		$this->db->join("payroll_system_account AS psa","psa.payroll_system_account_id = a.payroll_system_account_id","LEFT");
		$q = $this->db->get("accounts AS a");
		$r = $q->row();
		$q->free_result();
	
		if($this->session->userdata('support') == 'yes') {
			$w = array(
					"payroll_system_account_id"=>$this->session->userdata("psa_id")
			);
			$this->db->where($w);
			$q = $this->db->get("payroll_system_account AS psa");
			$r = $q->row();
			$q->free_result();
		}
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Payroll Calendar for Ashima Lite
	 * @param unknown $company_id
	 */
	public function payroll_calendar_ashima_lite($company_id){
		$w = array(
			"company_id"=>$company_id
		);
		$this->db->where($w);
		$this->db->order_by("first_payroll_date","ASC");
		$q = $this->db->get("payroll_calendar");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Payroll Group Employee List Lite
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 */
	public function get_payroll_group_employee_list_lite($payroll_group_id,$company_id){
		$s = array(
			"*","e.emp_id AS emp_id"
		);
		$this->edb->select($s);
	
		// Payroll Group
		if($payroll_group_id > 0) $this->db->where("epl.payroll_group_id",$payroll_group_id);
	
		$w = array(
			// "epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active",
			"epl.employee_status"=>"Active"
		);
		$this->db->where($w);
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epl.payroll_group_id","INNER");
		$this->edb->join("cost_center AS cc","cc.cost_center_id = epl.cost_center","LEFT");
		$q = $this->edb->get("employee AS e");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get timein of each employee for Regular Days Only Lite
	 * @param unknown_type $company_id
	 * @param unknown_type $employee_id
	 * @param unknown_type $current
	 * @param unknown_type $end
	 */
	public function new_time_in_regular_days_lite($company_id,$employee_id,$current,$end)
	{
		$where = array(
			'eti.emp_id'  => $employee_id,
			'eti.comp_id' => $company_id,
			'wtl.type'=>"regular"
		);
		$this->db->where($where);
	
		$this->db->where('eti.date >=',$current);
		$this->db->where('eti.date <=',$end);
	
		$this->db->where("eti.time_in IS NOT NULL");
		$this->db->where("eti.time_out IS NOT NULL");
	
		$this->db->where("(eti.time_in_status = 'approved' OR eti.time_in_status IS NULL)");
		$this->db->order_by("eti.time_in","ASC");
		$this->db->join("working_type_lite AS wtl","wtl.working_type_lite_id = eti.working_type_lite_id","LEFT");
		$q = $this->db->get('employee_time_in AS eti');
		$r = $q->result();
		$q->free_result();
		if($r){
			return ($r) ? $r : false;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Get Employee Overtime Lite
	 * @param unknown_type $company_id
	 * @param unknown_type $employee_id
	 * @param unknown_type $start
	 * @param unknown_type $end
	 */
	public function new_get_employee_overtime_lite($company_id,$employee_id,$start,$end)
	{
		// date where
		$date_where = array(
			"eva.overtime_from >= " => $start,
			"eva.overtime_from <= " => $end,

			"eva.overtime_to >= " => $start,
			"eva.overtime_to <= " => $end,
				
			'wtl.type'=>"overtime"
		);
		$this->db->where($date_where);
	
		$where = array(
			'eva.company_id' 	  => $company_id,
			'eva.emp_id'	 	  => $employee_id,
			'eva.overtime_status' => 'approved',
			'eva.status'		  => 'Active'
		);
		$this->db->where($where);
	
		#$this->db->where('approval_date >=',$start);
		#$this->db->where('approval_date <=',$end);
	
		$this->db->join("working_type_lite AS wtl","wtl.working_type_lite_id = eva.working_type_lite_id","LEFT");
		$q = $this->db->get('employee_overtime_application AS eva');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Night Differential Ashima Lite
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param unknown $start
	 * @param unknown $end
	 */
	public function nd_ashima_lite($company_id,$emp_id,$start,$end){
		$w = array(
			"date >= " => $start,
			"date <= " => $end,
			"comp_id"=>$company_id,
			"emp_id"=>$emp_id
		);
		$this->db->where($w);
		$q = $this->db->get("night_differential_lite");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Other Deductions Ashima Lite
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function employee_other_deductions_lite($emp_id,$company_id){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("employee_deductions");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Total Amount for Other Deductions Ashima Lite
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $deduction_id
	 * @param unknown $param_data
	 */
	public function check_total_amount_other_deductions_lite($emp_id,$company_id,$deduction_id,$param_data){
		$s = array(
			"COUNT(emp_id) AS total"
		);
		$this->db->select($s);
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"employee_deduction_id"=>$deduction_id, // for lite trapping
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_for_other_deductions");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Employee Earnigs Lite
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 */
	public function employee_earnigs_lite($emp_id,$company_id){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("employee_earnings_lite");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Adjustment for Other Earnings Lite
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 * @param unknown_type $param_data
	 */
	public function adjustment_other_earnings($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get('payroll_carry_over_adjustment_other_earnings');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Employee Other Earnings Ashima Lite
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function employee_other_earnings_lite($emp_id,$company_id){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("employee_earnings_lite");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Total Amount for Other Earnings Ashima Lite
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $earnings_id
	 * @param unknown $param_data
	 */
	public function check_total_amount_other_earnings_lite($emp_id,$company_id,$earnings_id,$param_data){
		$s = array(
			"COUNT(emp_id) AS total"
		);
		$this->db->select($s);
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"employee_earning_id"=>$earnings_id,
			
			#"payroll_period"=>$param_data["payroll_period"],
			#"period_from"=>$param_data["period_from"],
			#"period_to"=>$param_data["period_to"],
				
			"payroll_period != "=>$param_data["payroll_period"],
			"period_from != "=>$param_data["period_from"],
			"period_to != "=>$param_data["period_to"],
				
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_other_earnings_lite");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Payroll Calendar Workding Days for Ashima Lite
	 * @param unknown $company_id
	 */
	public function payroll_calendar_working_days_ashima_lite($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_calendar_working_days_settings");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Custom Payroll Draft
	 * @param unknown $payroll_period
	 * @param unknown $period_from
	 * @param unknown $period_to
	 * @param unknown $company_id
	 */
	public function check_custom_payroll_run($payroll_period,$period_from,$period_to,$company_id){
		$w = array(
			"company_id"=>$company_id,
			"payroll_period"=>$payroll_period,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_run_custom");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Payroll Draft
	 * @param unknown $payroll_group_id
	 * @param unknown $company_id
	 */
	public function check_payroll_run_draft($company_id,$payroll_group_id){
		$w = array(
			"company_id"=>$company_id,
			"payroll_group_id"=>$payroll_group_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("employee_payroll_information");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get timein of each employee for Regular Days Only Lite
	 * @param unknown_type $company_id
	 * @param unknown_type $employee_id
	 * @param unknown_type $current
	 * @param unknown_type $end
	 */
	public function emp_total_days_time_in($company_id,$employee_id,$current,$end)
	{
		$s = array(
			"COUNT(eti.emp_id) AS total"
		);
		$this->db->select($s);
		
		$where = array(
			'eti.emp_id'  => $employee_id,
			'eti.comp_id' => $company_id#,
			#'wtl.type'=>"regular"
		);
		$this->db->where($where);
	
		$this->db->where('eti.date >=',$current);
		$this->db->where('eti.date <=',$end);
	
		$this->db->where("eti.time_in IS NOT NULL");
		$this->db->where("eti.time_out IS NOT NULL");
	
		$this->db->where("(eti.time_in_status = 'approved' OR eti.time_in_status IS NULL)");
		$this->db->order_by("eti.time_in","ASC");
		#$this->db->join("working_type_lite AS wtl","wtl.working_type_lite_id = eti.working_type_lite_id","LEFT");
		$q = $this->db->get('employee_time_in AS eti');
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : 0 ;
	}
	
	/**
	 * Check Annual Earnings for Lite
	 * @param unknown $company_id
	 * @param unknown $ear_year
	 */
	public function check_annual_earnings($company_id,$ear_year){
		$s = array(
			"payroll_period"
		);
		$this->db->select($s);
		
		$w = array(
			"company_id"=>$company_id,
			"YEAR(payroll_period)" => $ear_year,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_other_earnings_lite");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Monthly Earnings for Lite
	 * @param unknown $company_id
	 * @param unknown $ear_year
	 */
	public function check_monthly_earnings($company_id,$ear_year,$ear_month){
		$s = array(
			"payroll_period"
		);
		$this->db->select($s);
	
		$w = array(
			"company_id"=>$company_id,
			"YEAR(payroll_period)" => $ear_year,
			"MONTH(payroll_period)" => $ear_month,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_other_earnings_lite");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Other Earnings for Lite only
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function lite_other_earnings($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_other_earnings_lite");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Leave Application
	 * @param unknown $leave_date_start
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function check_leave_pay_period($leave_date_start,$emp_id,$company_id){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active",
			"date_start <= " => $leave_date_start,
			"date_end >= " => $leave_date_start
		);
		$this->db->where($w);
		$q = $this->db->get("employee_leaves_application");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Leave Application
	 * @param unknown $leave_date_start
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function new_employee_leave_application($leave_date_start,$emp_id,$company_id){
		$w = array(
			"ela.emp_id"=>$emp_id,
			"ela.company_id"=>$company_id,
			"ela.status"=>"Active",
			"DATE(ela.date_start) <= " => $leave_date_start,
			"DATE(ela.date_start) >= " => $leave_date_start,
			// "DATE(ela.date_end) >= " => $leave_date_start,
			"ela.leave_application_status" => "approve"
			//"ela.date_filed !=" => "0000-00-00"
		);
		$this->db->where($w);
		$this->db->where("(ela.flag_parent != 'yes' OR ela.flag_parent = 'no' OR ela.flag_parent IS NULL)");		
		$this->db->join("leave_type AS lt","lt.leave_type_id = ela.leave_type_id","LEFT");
		$q = $this->db->get("employee_leaves_application AS ela");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Other Deductions Adjustment
	 * @param unknown $company_id
	 */
	public function employee_deductions_lite($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		//$w2 = array(
				//"company_id"=> 'employee_deduction_id'
		//);
		//$this->db->select($w2);
		$q = $this->db->get('employee_deductions');
		$result = $q->result();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	public function get_employee_other_deductions_lite($emp_id,$id,$period_from,$period_to,$payroll_period){
		$w = array(
				"emp_id"=>$emp_id,
				"employee_deduction_id"=>$id,
				"period_from"=>$period_from,
				"period_to"=>$period_to,
				"payroll_period"=>$payroll_period
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_for_other_deductions");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}

	/**
	 * Get Holiday
	 * @param unknown_type $company_id
	 * @param unknown_type $hour_type_id
	 * @param unknown_type $date
	 */
	public function get_holiday_for_daily_rate($company_id,$date){
		$where = array(
			'holiday.company_id' => $company_id,
			'holiday.date'		 => $date,
			'holiday.status'	 => 'Active'
		);
		$this->db->where($where);
		$this->db->join('hours_type','hours_type.hour_type_id = holiday.hour_type_id','left');
		$q = $this->db->get('holiday');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	/**
	 * Check Draft Pay Run By Payroll Group
	 * @param unknown $draft_pay_run_fields
	 * @param unknown $company_id
	 */
	public function check_draft_pay_run_info($draft_pay_run_fields,$company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active",
			"payroll_group_id != " => 0
		);
		$this->db->where($w);
		$this->db->where_in("draft_pay_run_id",$draft_pay_run_fields);
		$q = $this->db->get("draft_pay_runs");
		$r = $q->result();
		if($r){
			$res = array();
			foreach($r as $row){
				array_push($res, $row->payroll_group_id);
			}
			return $res;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check Draft Pay Run By Payroll Custom Employee
	 * @param unknown $draft_pay_run_fields
	 * @param unknown $company_id
	 */
	public function check_draft_pay_run_info_custom($draft_pay_run_fields,$company_id){
		$w = array(
			"dpr.company_id"=>$company_id,
			"dpr.status"=>"Active",
			"dpr.payroll_group_id" => 0
		);
		$this->db->where($w);
		$this->db->where_in("dpr.draft_pay_run_id",$draft_pay_run_fields);
		$this->db->join("payroll_run_custom AS prc","prc.draft_pay_run_id = dpr.draft_pay_run_id","LEFT");
		$q = $this->db->get("draft_pay_runs AS dpr");
		$r = $q->result();
		if($r){
			$res = array();
			foreach($r as $row){
				array_push($res, $row->emp_id);
			}
			return $res;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Lite - Get Employee Other Deductions
	 * @param unknown $emp_id
	 * @param unknown $period_from
	 * @param unknown $period_to
	 * @param unknown $payroll_period
	 */
	public function lite_get_employee_other_deductions($emp_id,$period_from,$period_to,$payroll_period){
		$s = array(
			"SUM(amount) AS total"
		);
		$this->db->select($s);
		$w = array(
			"emp_id"=>$emp_id,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"payroll_period"=>$payroll_period
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_for_other_deductions");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Lite - Get Employee Other Earnings
	 * @param unknown $emp_id
	 * @param unknown $period_from
	 * @param unknown $period_to
	 * @param unknown $payroll_period
	 */
	public function lite_get_employee_other_earnings($emp_id,$period_from,$period_to,$payroll_period){
		$s = array(
			"SUM(amount) AS total"
		);
		$this->db->select($s);
		$w = array(
			"emp_id"=>$emp_id,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"payroll_period"=>$payroll_period
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_other_earnings_lite");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Payroll Custom Run - Total Employee Included
	 * @param unknown $draft_pay_run_id
	 * @param unknown $company_id
	 */
	public function custom_total_employee_included($draft_pay_run_id,$company_id){
		$s = array(
			"COUNT(dpr.draft_pay_run_id) AS total"
		);
		$this->db->select($s);
		$w = array(
			"dpr.draft_pay_run_id"=>$draft_pay_run_id,
			"dpr.company_id"=>$company_id,
			"dpr.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("payroll_run_custom AS prc","prc.draft_pay_run_id = dpr.draft_pay_run_id","LEFT");
		$q = $this->db->get("draft_pay_runs AS dpr");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Check Employee if Absent for Daily Rate Only
	 * @param unknown $current
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function check_employee_absent($current,$emp_id,$company_id){
		$w = array(
			"emp_id"=>$emp_id,
			"date"=>date("Y-m-d",strtotime($current)),
			"comp_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("employee_time_in");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Timesheet Required
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function check_timesheet_required($emp_id,$company_id){
		$s = array(
			"timesheet_required"
		);
		$this->db->select($s);
		$w = array(
			"timesheet_required"=>"yes",
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("employee_payroll_information");
		$r = $q->row();
		return ($r) ? TRUE : FALSE ;
	}
	
	/**
	 * Employee Card ID
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function employee_card_id($emp_id,$company_id){
		$w = array(
			"emp_id"=>$emp_id,
			"comp_id"=>$company_id,
			"status"=>"Active"			
		);
		$this->db->where($w);
		$q = $this->edb->get("employee_payroll_account_info");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Custom Payroll Run
	 * @param unknown $val_array
	 */
	public function check_employee_custom_payroll_run($data_param){
		
		return FALSE; // FORCE TO FALSE RETURN KAY WLA NANI GAMIT.. sa duplicate employee ni..
		
		// AND CODE SA UBOS KAY WLA NAY GAMIT, DISREGARD TANAN...
		
		$emp_id = $data_param["emp_id"];
		// check from payroll custom list
		$w = array(
			"pp.emp_id"=>$emp_id,
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
		$r = $q->row();
		$q->free_result();
		if($r){
			return $r;
		}else{
			$w = array(
				"pp.emp_id"=>$emp_id,
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
			$this->db->join("draft_pay_runs AS dpr","epi.payroll_group_id = dpr.payroll_group_id","LEFT");
			$q = $this->db->get("payroll_payslip AS pp");
			$r = $q->row();
			$q->free_result();
			return ($r) ? $r : FALSE ;
		}
	}
	
	/**
	 * check employee payroll run closed
	 * @param unknown $check_payroll_period_params
	 */
	public function check_employee_closed($data_param){
		$w = array(
			#"dpr.token"=>$data_param["draft_pay_run_token"],
			#"dpr.payroll_group_id" => 0,
			"prc.emp_id"=>$data_param["emp_id"],
			"prc.company_id"=>$data_param["company_id"],
			"prc.payroll_period"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
			"prc.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
			"prc.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
			"prc.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->where("(dpr.view_status = 'Closed' OR dpr.view_status = 'Waiting for approval')");
		$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
		$q = $this->db->get("payroll_run_custom AS prc");
		$r = $q->row();
		// return ($r) ? $r : FALSE ;
		return ($r) ? FALSE : $r ; // IF NAA SYAY DISPLAY CLOSED OR WAITING KAY IPA DISPLAY SA VIEWS, NABALE ANG CONDITION ANI
	}
	
	/**
	 * Check Draft Pay Run ID
	 * @param unknown $data_param
	 */
	public function check_draft_token_info($data_param){
		$w = array(
			"dpr.token"=>$data_param["draft_pay_run_token"],
			"dpr.payroll_group_id" => 0,
			"dpr.company_id"=>$data_param["company_id"],
			"dpr.pay_period"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
			"dpr.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
			"dpr.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
			"dpr.status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("draft_pay_runs AS dpr");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * New Previous Payroll Period
	 * @param unknown $employee_payroll_group_id
	 * @param unknown $company_id
	 * @param unknown $period_to
	 */
	public function previous_payroll_period($employee_payroll_group_id,$company_id,$period_to){
		// CHECK PERIOD TYPE
		$w = array(
			"payroll_group_id"=>$employee_payroll_group_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_group");
		$r = $q->row();
		if($r){
			$w = array(
				"cut_off_to !=" => $period_to,
				"cut_off_to <=" => $period_to,
				"company_id" => $company_id,
				"pay_schedule"=>$r->period_type,
				"status"=>"Active"
			);
			$this->db->where($w);
			$this->db->order_by("cut_off_to","DESC");
			$q = $this->db->get("payroll_calendar",1);
			$r = $q->row();
			$q->free_result();
			return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check Payroll Approval Group
	 * @param unknown $company_id
	 */
	public function check_payroll_approval_group($company_id){
		$w = array(
			"company_id"=>$company_id,
			"name"=>"Payroll"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_process");
		$r = $q->row();
		if($r){
			$approval_process_id = $r->approval_process_id;
			$w = array(
				"ag.approval_process_id"=>$approval_process_id,
				"ag.company_id"=>$company_id
			);
			$this->db->where($w);
			$this->edb->join("approval_groups_via_groups AS agv","agv.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
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
	 * Check Approver Employee Password
	 * @param unknown $approver_emp_id
	 * @param unknown $encrypt_mypass
	 * @param unknown $company_id
	 */
	public function check_approver_employee_password($approver_emp_id,$encrypt_mypass,$company_id){
		$w = array(
			"e.emp_id"=>$approver_emp_id,
			"a.password"=>$encrypt_mypass,
			#"e.company_id"=>$company_id,
			"e.status"=>"Active"
		);
		$this->db->where($w);
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$q = $this->edb->get("employee AS e");
		$r = $q->row();
		if($r){
			if($r->company_id == 0){
				return $r;
			}else{
				$w = array(
					"e.emp_id"=>$approver_emp_id,
					"a.password"=>$encrypt_mypass,
					"e.company_id"=>$company_id,
					"e.status"=>"Active"
				);
				$this->db->where($w);
				$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
				$q = $this->edb->get("employee AS e");
				$r = $q->row();
				return ($r) ? $r : FALSE ;
			}
		}else{
			return FALSE;
		}
		#return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Next Payroll Approver Level
	 * @param unknown $active_level
	 * @param unknown $company_id
	 */
	public function check_next_approver_level($active_level,$company_id){
		$w = array(
			"company_id"=>$company_id,
			"name"=>"Payroll"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_process");
		$r = $q->row();
		if($r){
			$approval_process_id = $r->approval_process_id;
			$w = array(
				"ag.approval_process_id"=>$approval_process_id,
				"ag.company_id"=>$company_id,
				"ag.level > "=>$active_level
			);
			$this->db->where($w);
			$this->edb->join("approval_groups_via_groups AS agv","agv.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
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
	 * Check Payroll System Account ID
	 * @param unknown $company_id
	 */
	public function check_payroll_system_account_id($company_id){
		$w = array(
			"company_id"=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("assigned_company");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Approval Via Groups
	 * @param unknown $company_id
	 */
	public function check_approval_via_groups($company_id){
		
		$w = array(
			"company_id"=>$company_id,
			"name"=>"Payroll"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_process");
		$r = $q->row();
		if($r){
			$approval_process_id = $r->approval_process_id;
			$w = array(
				"ag.approval_process_id"=>$approval_process_id,
				"ag.company_id"=>$company_id
			);
			$this->db->where($w);
			$this->db->join("approval_groups AS ag","agv.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
			$this->db->group_by("ag.approval_process_id");
			$q = $this->db->get("approval_groups_via_groups AS agv");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		}
		
	}
	
	/**
	 * Check Payroll Status
	 * @param unknown $approver_emp_id
	 * @param unknown $company_id
	 * @param unknown $token
	 */
	public function check_payroll_status_level($approver_emp_id,$company_id,$token){
		$w = array(
			"token"=>$token,
			"comp_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_payroll");
		$r = $q->row();
		if($r){
			$level = $r->level;
			$w = array(
				"company_id"=>$company_id,
				"name"=>"Payroll"
			);
			$this->db->where($w);
			$q = $this->db->get("approval_process");
			$r = $q->row();
			if($r){
				$approval_process_id = $r->approval_process_id;
				$w = array(
					"ag.approval_process_id"=>$approval_process_id,
					"ag.company_id"=>$company_id,
					"ag.emp_id"=>$approver_emp_id,
					"ag.level"=>$level
				);
				$this->db->where($w);
				$q = $this->db->get("approval_groups AS ag");
				$r = $q->row();
				return ($r) ? $r : FALSE ;
			}else{
				return FALSE;
			}
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check Payroll Token Level
	 * @param unknown $token
	 * @param unknown $company_id
	 */
	public function check_payroll_token_level($token,$company_id){
		$w = array(
			"token"=>$token,
			"comp_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_payroll");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Notify Same Level
	 * @param unknown $active_level
	 * @param unknown $company_id
	 */
	public function notify_same_level($active_level,$company_id){
		$w = array(
			"company_id"=>$company_id,
			"name"=>"Payroll"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_process");
		$r = $q->row();
		if($r){
			$approval_process_id = $r->approval_process_id;
			$w = array(
				"ag.approval_process_id"=>$approval_process_id,
				"ag.company_id"=>$company_id,
				"ag.level"=>$active_level
			);
			$this->db->where($w);
			$this->edb->join("approval_groups_via_groups AS agv","agv.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
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
	 * Get Leave Type
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 */
	public function get_leave_type($company_id,$emp_id){
		$w = array(
			"el.emp_id"=>$emp_id,
			"el.company_id"=>$company_id,
			"el.status"=>"Active",
			"lt.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
		$q = $this->db->get("employee_leaves AS el");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Leave History
	 * @param unknown $leave_type_id
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $date
	 */
	public function check_leave_history($leave_type_id,$emp_id,$company_id,$date){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active",
			"leave_type_id"=>$leave_type_id,
			"YEAR(date)"=>$date, // convert by year
			// "previous_period_leave_balance > "	=>	0 // add
			"previous_period_leave_balance >= "	=>	0 // add
		);
		$this->db->where($w);
		$this->db->order_by("date","DESC");
		$q = $this->db->get("employee_leave_history",1);
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Leave History Total Rows
	 * @param unknown $leave_type_id
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $date
	 */
	public function check_leave_conversion_for_total_rows($leave_type_id,$emp_id,$company_id,$date){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active",
			"leave_type_id"=>$leave_type_id,
			"YEAR(date)"=>$date // convert by year
		);
		$this->db->where($w);
		$q = $this->db->get("employee_leave_history");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Leave History Last Date
	 * @param unknown $leave_type_id
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function check_leave_conversion_last_date($leave_type_id,$emp_id,$company_id,$period_from,$period_to){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active",
			"leave_type_id"=>$leave_type_id,
			// "date"=>$date, // convert by year
			// "date <= "=>$period_from,
			// "date >= "=>$period_to,
			"previous_period_leave_balance > "	=>	0 // add
		);
		$this->db->where($w);
		$this->db->order_by("date","DESC");
		$q = $this->db->get("employee_leave_history",1);
		#$r = $q->result();
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Leave History Last Date by Row
	 * @param unknown $leave_type_id
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function check_leave_conversion_last_date_row($leave_type_id,$emp_id,$company_id,$period_from,$period_to){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active",
			"leave_type_id"=>$leave_type_id,
			// "date"=>$date, // convert by year
			// "date <= "=>$period_from,
			// "date >= "=>$period_to,
			"previous_period_leave_balance > "	=>	0 // add
		);
		$this->db->where($w);
		$this->db->order_by("date","DESC");
		$q = $this->db->get("employee_leave_history",1);
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Payroll Approval Group
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function check_payroll_approver_level($emp_id,$company_id,$per_page, $page, $sort_by, $search, $sort_param=NULL){
		$w = array(
			"company_id"=>$company_id,
			"name"=>"Payroll"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_process");
		$r = $q->row();
		if($r){
			
			$approval_process_id = $r->approval_process_id;
			$w = array(
				"ag.approval_process_id"=>$approval_process_id,
				"ag.emp_id"=>$emp_id, // 
				"ag.company_id"=>$company_id
			);
			$this->db->where($w);
			$this->db->join("approval_groups_via_groups AS agv","agv.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
			$q = $this->db->get("approval_groups AS ag");
			$r = $q->row();
			
			if($r){
				// payroll approval
				#$this->db->select("*");
				#$this->db->select("(SELECT draft_pay_run_id FROM draft_pay_runs WHERE token = approval_payroll.token) AS draft_pay_run_ids");
				
				if($search != ""){
					$datewhere = date("Y-m-d",strtotime($search));
					$this->db->where("payroll_period = '{$datewhere}'");
				}
				
				$w = array(
					"level <= " => $r->level, // 
					"level <= " => 2,
					"comp_id"=>$company_id,
					"payroll_status"=>"pending",
					"status"=>"Active"
				);
				$this->db->where($w);
				if($sort_by == NULL){
					$this->db->order_by("payroll_period","DESC");
				}else{
					$this->db->order_by("payroll_period",$sort_by);
				}
				
				$q = $this->db->get("approval_payroll",$per_page, $page);
				$r = $q->result();
				return ($r) ? $r : FALSE ;
			}else{
				return FALSE;
			}
			
			// return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check Payroll Approval Group Counter
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function check_payroll_approver_level_counter($emp_id,$company_id,$per_page, $page, $sort_by, $q, $sort_param=NULL){
		$w = array(
				"company_id"=>$company_id,
				"name"=>"Payroll"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_process");
		$r = $q->row();
		if($r){
				
			$approval_process_id = $r->approval_process_id;
			$w = array(
					"ag.approval_process_id"=>$approval_process_id,
					"ag.emp_id"=>$emp_id, //
					"ag.company_id"=>$company_id
			);
			$this->db->where($w);
			$this->db->join("approval_groups_via_groups AS agv","agv.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
			$q = $this->db->get("approval_groups AS ag");
			$r = $q->row();
				
			if($r){
				// payroll approval
				$this->db->select("COUNT(*) AS total");
				$w = array(
				"level <= " => $r->level, // 
				"level <= " => 2,
				"comp_id"=>$company_id,
				"payroll_status"=>"pending",
				"status"=>"Active"
						);
				$this->db->where($w);
				$q = $this->db->get("approval_payroll");
				$r = $q->row();
				return ($r) ? $r->total : FALSE ;
			}else{
				return FALSE;
			}
				
			// return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Get Password By Account ID
	 * @param unknown $account_id
	 */
	public function get_password_by_account_id($account_id){
		$w = array(
			"account_id"=>$account_id,
			"deleted"=>"0"
		);
		$this->db->where($w);
		$q = $this->db->get("accounts");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Payroll Approval Group
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function payroll_approval_my_level_id($emp_id,$company_id){
		$w = array(
			"company_id"=>$company_id,
			"name"=>"Payroll"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_process");
		$r = $q->row();
		if($r){
				
			$approval_process_id = $r->approval_process_id;
			$w = array(
				"ag.approval_process_id"=>$approval_process_id,
				"ag.emp_id"=>$emp_id,
				"ag.company_id"=>$company_id
			);
			$this->db->where($w);
			$this->db->join("approval_groups_via_groups AS agv","agv.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
			$q = $this->db->get("approval_groups AS ag");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check Token Result
	 * @param unknown $token
	 * @param unknown $company_id
	 */
	public function check_token_result($token,$company_id){
		$w = array(
			"token"=>$token,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("draft_pay_runs");
		$r = $q->result();
		if($r){
			$res = array();
			foreach($r AS $row){
				array_push($res, $row->draft_pay_run_id);
			}
			return $res;
		}else{
			return FALSE;	
		}
	}
	
	/**
	 * Hours Type Default
	 * @param unknown $company_id
	 */
	public function hours_type_update($company_id){
		$w = array(
			// "flag_update" => "1",
			"flag_update > " => 0,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("hours_type");
		$r = $q->result();
		return ($r) ? TRUE : FALSE ;
	}
	
	/**
	 * Check No. of Occurences Paid
	 * @param unknown $period_from
	 * @param unknown $period_to
	 * @param unknown $payroll_period
	 * @param unknown $earning_id
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 */
	public function check_no_of_occurences_paid($period_from,$period_to,$payroll_period,$earning_id,$company_id,$emp_id){
		$this->db->select("SUM(total_occurences_daily_and_weekly) AS sum_total");
		$w = array(
			'emp_id'=>$emp_id,
			'payroll_period != '=>$payroll_period,
			'period_from != '=>$period_from,
			'period_to != '=>$period_to,
			'company_id'=>$company_id,
			"employee_earning_id"=>$earning_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_other_earnings_lite");
		$r = $q->row();
		if($r){
			return ($r->sum_total == NULL || $r->sum_total == "") ? 0 : $r->sum_total ;
		}else{
			return 0;
		}
	}
	
	/**
	 * New Night Differential Settings Default
	 * @param unknown $company_id
	 * @return Ambigous <boolean, unknown>
	 */
	// public function new_night_differential_settings($company_id){
	public function check_nd_default($company_id){
		
		// DEFAULT SETTING ON NIGHT DIFFERENTIAL
		$w = array(
			'company_id' => $company_id,
			"enable" => "yes"
		);
		$this->db->where($w);
		$q = $this->db->get('nightshift_differential_settings_inclusion');
		$r = $q->row();
		if(!$r){
			$w = array(
				'company_id' => $company_id,
				"enable" => "yes"
			);
			$this->db->where($w);
			$q = $this->db->get('nightshift_differential_settings');
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * New Night Differential Settings
	 * @param unknown $emp_id
	 * @param unknown $v
	 * @param unknown $payroll_group_id
	 * @param unknown $employment_type
	 * @param unknown $company_id
	 */
	public function new_night_differential_settings($emp_id,$rank_id,$payroll_group_id,$employment_type,$company_id){
		
		/* SELECT FIND_IN_SET(9,company_id)
		 from
		 nightshift_differential_settings
		 ORDER BY FIND_IN_SET(9,company_id) DESC
		 LIMIT 1 */
		
		// print "tal {$rank_id}";exit;
		
		// CHECK NIGHT DIFFERENTIAL ON
		$w = array(
			'company_id' => $company_id,
			"enable" => "yes"
		);
		$this->db->where($w);
		$q = $this->db->get('nightshift_differential_settings');
		$flag_on = $q->row();
		
		// CUSTOM EMPLOYEE
		$w = array(
			"ndsc.emp_id"=>$emp_id,
			"ndsc.company_id"=>$company_id
		);
		$this->db->where($w);
		$this->db->join("nightshift_differential_settings_inclusion AS ndsi","ndsi.nightshift_differential_settings_id = ndsc.nightshift_differential_settings_id && ndsi.flag_fixed_sched = ndsc.flag_fixed_sched","LEFT");
		$q = $this->db->get("nightshift_differential_settings_custom_employee AS ndsc");
		$r = $q->row();
		if($r && $flag_on){
			return $r;
		}
		
		// CHECK BY PAYROLL GROUP ID
		$this->db->select("*");
		$this->db->select("FIND_IN_SET({$payroll_group_id},pgroup) AS flag_true",FALSE);
		$w = array(
			"enable"=>"yes",
			"company_id"=>$company_id
		);
		$this->db->where($w);
		$this->db->order_by("FIND_IN_SET({$payroll_group_id},pgroup)","DESC",FALSE);
		$q = $this->db->get('nightshift_differential_settings_inclusion',1);
		$r = $q->row();
		
		if($r && $flag_on){
			#return ($r->flag_true > 0) ? $r : FALSE ;
			#exit;
			if($r->flag_true > 0) return $r;
		}
		
		// CHECK BY RANK
		$this->db->select("*");
		$this->db->select("FIND_IN_SET({$rank_id},rank_id) AS flag_true",FALSE);
		$w = array(
			"enable"=>"yes",
			"company_id"=>$company_id
		);
		$this->db->where($w);
		$this->db->order_by("FIND_IN_SET({$rank_id},rank_id)","DESC",FALSE);
		$q = $this->db->get('nightshift_differential_settings_inclusion',1);
		$r = $q->row();
		
		if($r && $flag_on){
			#return ($r->flag_true > 0) ? $r : FALSE ;
			#exit;
			if($r->flag_true > 0) return $r;
		}
		
		// CHECK EMPLOYMENT TYPE
		$this->db->select("*");
		$this->db->select("FIND_IN_SET({$employment_type},employment_type) AS flag_true",FALSE);
		$w = array(
			"enable"=>"yes",
			"company_id"=>$company_id
		);
		$this->db->where($w);
		$this->db->order_by("FIND_IN_SET({$employment_type},employment_type)","DESC",FALSE);
		$q = $this->db->get('nightshift_differential_settings_inclusion',1);
		$r = $q->row();
		if($r && $flag_on){
			#return ($r->flag_true > 0) ? $r : FALSE ;
			#exit;
			if($r->flag_true > 0) return $r;
		}
		
		return FALSE;
	}
	
	/**
	 * Clear Leave Conversion Employee Custom
	 * @param unknown $token
	 * @param unknown $company_id
	 */
	public function clear_leave_conversion_employee_custom($token,$company_id){
		$s = array(
			"pp.leave_conversion_type_ids AS leave_conversion_type_ids",
			"pp.emp_id AS emp_id"
		);
		$this->db->select($s);
		$w = array(
			"pp.leave_conversion_type_ids != " => "0",
			"dpr.token"=>$token,
			"dpr.company_id"=>$company_id,
			"dpr.status"=>"Active",
			"dpr.view_status"=>"Closed"
		);
		$this->db->where($w);
		$this->db->join("payroll_run_custom AS prc","prc.draft_pay_run_id = dpr.draft_pay_run_id","LEFT");
		$this->db->join("payroll_payslip AS pp","pp.emp_id = prc.emp_id","LEFT");
		$q = $this->db->get("draft_pay_runs AS dpr");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Clear Leave Conversion Employee from Payroll Payslip
	 * @param unknown $token
	 * @param unknown $company_id
	 */
	public function clear_leave_conversion_from_payroll_payslip($token,$company_id){
		$s = array(
			"pp.leave_conversion_type_ids AS leave_conversion_type_ids",
			"pp.emp_id AS emp_id"
		);
		$this->db->select($s);
		$w = array(
			"pp.leave_conversion_type_ids != " => "0",
			"dpr.token"=>$token,
			"dpr.company_id"=>$company_id,
			"dpr.status"=>"Active",
			"dpr.view_status"=>"Closed"
		);
		$this->db->where($w);
		$this->db->join("payroll_payslip AS pp","pp.payroll_group_id = dpr.payroll_group_id && pp.payroll_date = dpr.pay_period","LEFT");
		$q = $this->db->get("draft_pay_runs AS dpr");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Company Location
	 * @param unknown $company_id
	 */
	public function get_company_location($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("location_and_offices");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Employee By Location list
	 * @param unknown_type $location_id
	 * @param unknown_type $company_id
	 */
	public function get_employee_by_location_list($location_id,$company_id){
	
		// CHECK LOCATION VALUE
		if($location_id == "" || $location_id < 0){
			return FALSE;
		}
	
		$s = array(
			"*","e.emp_id AS emp_id"
		);
		$this->edb->select($s);
	
		$w = array(
			"epl.location_and_offices_id"=>$location_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active",
			"epl.employee_status"=>"Active"
		);
		$this->db->where($w);
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		#$this->edb->join("department AS d","d.dept_id = epl.department_id","INNER");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","INNER");
		$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epl.payroll_group_id","LEFT");
		$this->edb->join("cost_center AS cc","cc.cost_center_id = epl.cost_center","LEFT");
		$q = $this->edb->get("employee AS e");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Draft Pay Run ID
	 * @param unknown $draft_pay_run_id
	 * @param unknown $company_id
	 */
	public function get_draft_pay_run_info($draft_pay_run_id,$company_id){
		$w = array(
			"company_id"=>$company_id,
			"draft_pay_run_id"=>$draft_pay_run_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("draft_pay_runs");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Location Name
	 * @param unknown $location_id
	 * @param unknown $company_id
	 */
	public function get_location_name($location_id,$company_id){
		$w = array(
			"company_id"=>$company_id,
			"location_and_offices_id"=>$location_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("location_and_offices");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}

	/**
	 * Number Of Days Overdue Before Escalation:
	 */
	public function escalate_payroll_approval(){
		$w = array(
			"status"=>"Active",
			"payroll_status"=>"pending"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_payroll");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Advance Settings for payroll
	 * @param unknown $esc_company_id
	 */
	public function check_advance_settings_for_payroll($esc_company_id){
		
		$w = array(
			"company_id"=>$esc_company_id,
			"name"=>"Payroll"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_process");
		$r = $q->row();
		if($r){
			$w = array(
				"approval_process_id"=>$r->approval_process_id
			);
			$this->db->where($w);
			$this->db->group_by("approval_process_id");
			$q = $this->db->get("approval_groups");
			$r = $q->row();
			if($r){
				$w = array(
					"company_id"=>$esc_company_id,
					"approval_groups_via_groups_id"=>$r->approval_groups_via_groups_id,
					"enable_advance_settings"=>"yes",
					"days_overdue_before_escalation > "=> 0
				);
				$this->db->where($w);
				$q = $this->db->get("approval_groups_via_groups");
				$r = $q->row();
				return ($r) ? $r : FALSE ;
			}else{
				return FALSE;
			}
		}else{
			return FALSE;
		}
		
	}
	
	/**
	 * Escalate Payroll Notification Current Level
	 * @param unknown $company_id
	 */
	public function escalte_payroll_notify_current_level($esc_level_id,$esc_company_id){
		$w = array(
			"company_id"=>$esc_company_id,
			"name"=>"Payroll"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_process");
		$r = $q->row();
		if($r){
			$approval_process_id = $r->approval_process_id;
			$w = array(
				"ag.approval_process_id"=>$approval_process_id,
				"ag.company_id"=>$esc_company_id,
				"ag.level"=>$esc_level_id
			);
			$this->db->where($w);
			$this->edb->join("approval_groups_via_groups AS agv","agv.approval_groups_via_groups_id = ag.approval_groups_via_groups_id","LEFT");
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
	 * Check Advance Settings for payroll
	 * @param unknown $esc_company_id
	 */
	public function check_advance_settings_for_payroll_due_date($esc_company_id){
		
		$w = array(
			"company_id"=>$esc_company_id,
			"name"=>"Payroll"
		);
		$this->db->where($w);
		$q = $this->db->get("approval_process");
		$r = $q->row();
		if($r){
			$w = array(
				"approval_process_id"=>$r->approval_process_id
			);
			$this->db->where($w);
			$this->db->group_by("approval_process_id");
			$q = $this->db->get("approval_groups");
			$r = $q->row();
			if($r){
				$w = array(
					"company_id"=>$esc_company_id,
					"approval_groups_via_groups_id"=>$r->approval_groups_via_groups_id,
					"enable_due_date"=>"yes"
				);
				$this->db->where($w);
				$q = $this->db->get("approval_groups_via_groups");
				$r = $q->row();
				return ($r) ? $r : FALSE ;
			}else{
				return FALSE;
			}
		}else{
			return FALSE;
		}
		
	}
	
	/**
	 * Check Days Hours Mins Workflow Payroll
	 * @param unknown $approval_groups_via_groups_id
	 * @param unknown $esc_level_id
	 * @param unknown $esc_company_id
	 */
	public function check_days_hours_mins_workflow_payroll($approval_groups_via_groups_id,$esc_level_id,$esc_company_id){
		$w = array(
			"company_id"=>$esc_company_id,
			"level"=>$esc_level_id,
			"approval_groups_via_groups_id"=>$approval_groups_via_groups_id
		);
		$this->db->where($w);
		$q = $this->db->get("approval_groups");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Time In Location
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function check_employee_time_in_location($emp_id,$company_id,$period_from,$period_to){
		/* SELECT
		#*
		lo.`name`
		FROM schedule_blocks_time_in AS sbt
		LEFT JOIN schedule_blocks AS sb ON sbt.schedule_blocks_id = sb.schedule_blocks_id
		LEFT JOIN location_and_offices AS lo ON lo.location_and_offices_id = sb.location_and_offices_id
		WHERE 
		sbt.comp_id = 2 AND sb.location_and_offices_id > 0
		AND sbt.date >= '2015-12-26' AND sbt.date <= '2016-01-10' # '2015-12-28' >= '2015-12-26' AND '2015-12-28' <= '2016-01-10'
		GROUP BY sb.location_and_offices_id */
		
		$s = array(
			"*","lo.name AS location_name"
		);
		$this->db->select($s);
		$w = array(
			"sbt.comp_id"=>$company_id,
			"sb.location_and_offices_id > "=>0,
			"sbt.date >="=>$period_from,
			"sbt.date <="=>$period_to,
			"sbt.emp_id"=>$emp_id
		);
		$this->db->where($w);
		$this->db->where("(sbt.time_in_status = 'approved' OR sbt.time_in_status IS NULL)"); // add ni
		// $this->db->join("schedule_blocks AS sb","sbt.schedule_blocks_id = sb.schedule_blocks_id","LEFT");
		$this->db->join("schedule_blocks AS sb","sbt.schedule_blocks_id = sb.schedule_blocks_id","INNER");
		// $this->db->join("location_and_offices AS lo","lo.location_and_offices_id = sb.location_and_offices_id","LEFT");
		$this->db->join("location_and_offices AS lo","lo.location_and_offices_id = sb.location_and_offices_id","INNER");
		$this->db->group_by("sb.location_and_offices_id");
		$q = $this->db->get("schedule_blocks_time_in AS sbt");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
		
	}
	
	/**
	 * Check Employee Time In
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $current
	 */
	public function location_new_check_employee_time_in($company_id,$emp_id,$start,$end,$location_id){
		$w = array(
			"emp_id"=>$emp_id,
			"comp_id"=>$company_id,
			"status"=>"Active",
			"sb.location_and_offices_id"=>$location_id,
		);
		$this->db->where($w);
	
		$this->db->where('date >=',$start);
		$this->db->where('date <=',$end);
	
		// $this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL) AND location != 'over_xxx'");
		$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
		//$this->db->where("(location != 'over_xxx' OR location IS NULL)");
		$this->db->where("time_out IS NOT NULL");
		$this->db->order_by("date","ASC");
		$this->db->join("schedule_blocks AS sb","sbt.schedule_blocks_id = sb.schedule_blocks_id","LEFT");
		$q = $this->db->get("schedule_blocks_time_in AS sbt");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Earnings Other Earnings
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param unknown $name
	 */
	public function get_earnings_other_earnings($company_id,$emp_id,$name){
		$w = array(
			"comp_id"=>$company_id,
			#"emp_id"=>$emp_id,
			"name"=>$name,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("earnings_other_earnings");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Earnings OTher Earnings List
	 * @param unknown $company_id
	 */
	public function earnings_other_earnings($company_id){
		$w = array(
			"comp_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("earnings_other_earnings");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Payroll Earnings Other Earnings Amount
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $earnings_other_earnings_id
	 * @param unknown $param_data
	 */
	public function check_employee_payroll_earnings_other_earnings_amount($emp_id,$company_id,$earnings_other_earnings_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"earnings_other_earnings_id"=>$earnings_other_earnings_id,
			"status"=>"Active",
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"]
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_other_earnings_lite");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get timein of each employee for Regular Days Only By Location
	 * @param int $company_id
	 * @param int $employee_id
	 * @param date $date
	 */
	public function location_time_in_regular_days($company_id,$emp_id,$date,$location_id){
		$w = array(
			"emp_id"=>$emp_id,
			"comp_id"=>$company_id,
			"status"=>"Active",
			"status"=>"Active",
			"sb.location_and_offices_id"=>$location_id,
			"sbt.date"	  => $date
		);
		$this->db->where($w);
		$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
		$this->db->where("time_out IS NOT NULL");
		$this->db->order_by("date","ASC");
		$this->db->join("schedule_blocks AS sb","sbt.schedule_blocks_id = sb.schedule_blocks_id","LEFT");
		$q = $this->db->get("schedule_blocks_time_in AS sbt");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * New Workshift (Schedule Blocks) By Location
	 * @param int $company_id
	 * @param int $work_schedule_id
	 * @param int $shifts_schedule_id
	 */
	#public function location_new_get_workshift($company_id,$work_schedule_id,$location_id)
	public function location_new_get_workshift($company_id,$emp_id,$date,$location_id){
		
		/* $s = array("*","total_hours_work_per_block AS total_work_hours");
		$this->db->select($s);
		$where = array(
			'ss.company_id' 	   => $company_id,
			#"ss.work_schedule_id"=>$work_schedule_id,
			"ss.schedule_blocks_id"=>$shifts_schedule_id,
			"ss.location_and_offices_id"=>$location_id
		);
		$this->db->where($where);
		$q = $this->db->get('schedule_blocks AS ss');
		$result = $q->result();
		return ($result) ? $result : false; */
		
		$s = array("*","sb.total_hours_work_per_block AS total_work_hours");
		$this->db->select($s);
		
		$w_date = array(
			"ess.valid_from <="		=>	$date,
			"ess.until >="			=>	$date
		);
		if($date != NULL) $this->db->where($w_date);
		
		$w = array(
			"ess.emp_id"=>$emp_id,
			"ess.company_id"=>$company_id,
			"ess.payroll_group_id"=>0, // add
			"ess.status"=>"Active",
			"sb.location_and_offices_id"=>$location_id
		);
		$this->db->where($w);
		$this->db->join("employee_sched_block AS esb","esb.shifts_schedule_id = ess.shifts_schedule_id","LEFT");
		$this->db->join("schedule_blocks AS sb","sb.schedule_blocks_id = esb.schedule_blocks_id","LEFT");
		$q = $this->db->get("employee_shifts_schedule AS ess");
		$r = $q->result();
		return ($r) ? $r : false;
		
	}
	
	/**
	 * Location Shift Schedule ID
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param unknown $date
	 */
	public function location_shift_schedule_id($company_id,$emp_id,$date){
		$w_date = array(
			"valid_from <="		=>	$date,
			"until >="			=>	$date
		);
		if($date != NULL) $this->db->where($w_date);
		
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"payroll_group_id"=>0, // add
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("employee_shifts_schedule");
		$r = $q->row();
		return ($r) ? $r : false;
	}
	
	/**
	 * Check Employee De Minimis
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param unknown $dm_id
	 * @param unknown $param_data
	 */
	public function check_employee_deminimis_active($company_id,$emp_id,$dm_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"de_minimis_id"=>$dm_id,
			"payroll_period != "=>$param_data["payroll_period"],
			"period_from != "=>$param_data["period_from"],
			"period_to != "=>$param_data["period_to"]
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_de_minimis");
		$r = $q->result();
		return ($r) ? 1 : 0 ;
	}
	
	/**
	 * Check Employee De Minimis
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param unknown $dm_id
	 * @param unknown $param_data
	 */
	public function check_employee_deminimis_once_a_month($company_id,$emp_id,$dm_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"de_minimis_id"=>$dm_id,
			#"payroll_period != "=>$param_data["payroll_period"],
			#"period_from != "=>$param_data["period_from"],
			#"period_to != "=>$param_data["period_to"]
			"MONTH(payroll_period) != "=>date("m",strtotime($param_data["payroll_period"])),
			"YEAR(payroll_period) != "=>date("Y",strtotime($param_data["payroll_period"]))
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_de_minimis");
		$r = $q->result();
		return ($r) ? 1 : 0 ;
	}

	/**
	 * Employee Is Fortnightly
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function is_fortnightly($emp_id,$company_id){
		$where = array(
			'e.status'=>'Active',
			'epi.status'=>'Active',
			'pg.status'=>'Active',
			'e.company_id'=>$company_id,
			'e.emp_id'=>$emp_id,
			'pg.period_type'=>'Fortnightly'
		);
		$select = array(
			'e.emp_id','pg.period_type'
		);
		
		$this->db->select($select);
		$this->db->where($where);
		$this->db->join('employee_payroll_information AS epi','epi.emp_id= e.emp_id','INNER');
		$this->db->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','INNER');
		$q = $this->db->get('employee AS e');
		$r = $q->row();
		return $r ? true :  false;
	}
	
	/**
	 * Check Employee De Minimis Annualy
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param unknown $dm_id
	 * @param unknown $param_data
	 */
	public function check_employee_deminimis_annualy($company_id,$emp_id,$dm_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"de_minimis_id"=>$dm_id,
			"YEAR(payroll_period) != "=>date("Y",strtotime($param_data["payroll_period"]))
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_de_minimis");
		$r = $q->result();
		return ($r) ? 1 : 0 ;
	}
	
	/**
	 * Absences For the Entire Year
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $year
	 */
	public function absences_for_the_entire_year($emp_id,$company_id,$year){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"YEAR(payroll_date)"=>$year,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->edb->get("payroll_payslip");
		$r = $q->row();
		return ($r) ? $r->days_absent * 24 : 0 ;
	}
	
	/**
	 * check Pay schedule
	 * @param unknown $company_id
	 */
	public function check_pay_schedule($company_id){
		$s = array(
			"pay_schedule"
		);
		#$this->db->select($s);
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$this->db->group_by("pay_schedule");
		$q = $this->db->get("payroll_calendar");
		$r = $q->result();
		return ($r) ? $r : FALSE ; 
	}
	
	/**
	 * Check Double Holiday
	 * @param unknown $company_id
	 * @param unknown $date
	 */
	public function check_double_holiday($company_id,$date){
		
		$where = array(
			'holiday.company_id' => $company_id,
			'MONTH(holiday.date)'	=> date("m",strtotime($date)),
			'DAY(holiday.date)'		=> date("d",strtotime($date)),
			'holiday.status'	 => 'Active'
		);
		$this->db->where($where);
		$this->db->join('hours_type','hours_type.hour_type_id = holiday.hour_type_id','left');
		$this->db->join('overtime_type','overtime_type.hour_type_id = holiday.hour_type_id','left');
		$q = $this->db->get('holiday');
		$r = $q->result();
		
		if($r){
		
			$cnt = 0;
			// foreach($r as $row){ $cnt++; }
			foreach($r as $row){
				if($row->date_type == "fixed" || ($row->date_type == "movable" && $row->date == $date)){
					$cnt++;
				}
			}
				
			if($cnt >= 2){
				
				// DOUBLE HOLIDAY
				$s = array(
					"*",
					"hour_type_name AS holiday_name"
				);
				$this->db->select($s);
				$w = array(
					"company_id"=>$company_id,
					"hour_type_name"=>"Double Holiday",
					"status"=>"Active"
				);
				$this->db->where($w);
				$q = $this->db->get("hours_type");
				$r = $q->row();
				return ($r) ? $r : FALSE ;
		
			}else{
				return FALSE;
			}
		
		}else{
			return FALSE;
		}
		
	}
	
	/**
	 * Check Fortnightly Last Payroll
	 * @param unknown $company_id
	 * @param unknown $pay_period
	 */
	public function check_fortnightly_last_payroll($company_id,$pay_period){
		
		/* SELECT
		* from payroll_calendar
		where 
		company_id = 36
		and pay_schedule = "Fortnightly"
		AND YEAR(first_payroll_date) = "2016"
		AND MONTH(first_payroll_date) = "05"
		ORDER BY period_count DESC */
		
		$w = array(
			"company_id"=>$company_id,
			"pay_schedule"=>"Fortnightly",
			"YEAR(first_payroll_date)"=>date("Y",strtotime($pay_period)),
			"MONTH(first_payroll_date)"=>date("m",strtotime($pay_period)),
			"status"=>"Active"
		);
		$this->db->where($w);
		$this->db->order_by("period_count","DESC");
		$q = $this->db->get("payroll_calendar");
		$r = $q->row();
		if($r){
			return ($r->first_payroll_date == date("Y-m-d",strtotime($pay_period))) ? $r : FALSE ;
		}else{
			return FALSE;
		}
		
		// return ($r) ? $r : FALSE ;
		
	}
	
	/**
	 * Get No of Payroll Per Month
	 * @param unknown $company_id
	 * @param unknown $pay_period
	 */
	public function get_no_of_payroll_per_month_fortnightly($company_id,$pay_period){
		$s = array(
			"COUNT(*) AS total"
		);
		$this->db->select($s);
		$w = array(
			"company_id"=>$company_id,
			"pay_schedule"=>"Fortnightly",
			"YEAR(first_payroll_date)"=>date("Y",strtotime($pay_period)),
			"MONTH(first_payroll_date)"=>date("m",strtotime($pay_period)),
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_calendar");
		$r = $q->row();
		return ($r) ? $r->total : 0 ;
	}
	
	/**
	 * Check Leave Application Per Payroll Period
	 * @param unknown $emp_id
	 * @param unknown $period_from
	 * @param unknown $period_to
	 * @param unknown $company_id
	 */
	public function check_leave_application_per_payroll_period($emp_id,$period_from,$period_to,$company_id){
		$w = array(
			"ela.emp_id"=>$emp_id,
			"ela.company_id"=>$company_id,
			"ela.status"=>"Active",
			"DATE(ela.date_start) >= " => $period_from,
			"DATE(ela.date_start) <= " => $period_to,
			"ela.leave_application_status" => "approve"
		);
		$this->db->where($w);
		$this->db->where("(ela.flag_parent != 'yes' OR ela.flag_parent = 'no' OR ela.flag_parent IS NULL)");
		$q = $this->db->get("employee_leaves_application AS ela");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Holiday Per Payroll Period
	 * @param unknown $emp_id
	 * @param unknown $period_from
	 * @param unknown $period_to
	 * @param unknown $company_id
	 */
	public function check_holiday_per_payroll_period($period_from,$period_to,$company_id){
		
		/*# DATE_FORMAT(date, '2014-%m-%d') BETWEEN DATE_FORMAT("2014-12-26", '2014-%m-%d') AND DATE_FORMAT("2015-01-10", '2015-%m-%d')
		#OR
		#DATE_FORMAT(date, '2015-%m-%d') BETWEEN DATE_FORMAT("2014-12-26", '2014-%m-%d') AND DATE_FORMAT("2015-01-10", '2015-%m-%d')
		
		#DATE_FORMAT(date, '2014-%m-%d') BETWEEN DATE_FORMAT("2015-01-01", '2014-%m-%d') AND DATE_FORMAT("2015-01-26", '2015-%m-%d')
		#OR
		#DATE_FORMAT(date, '%c-%d') BETWEEN DATE_FORMAT("2015-01-01", '%c-%d') AND DATE_FORMAT("2015-01-26", '%c-%d')
		
		AND
		(DATE_FORMAT("2015-01-01", '%m-%d') < '12-2' AND DATE_FORMAT(date, '%m-%d') >= DATE_FORMAT("2015-01-01", '%m-%d')
		AND DATE_FORMAT(date, '%m-%d') <= DATE_FORMAT("2015-02-10", '%m-%d'))
		
		OR (DATE_FORMAT("2015-01-01", '%m-%d') >= '12-2' AND (DATE_FORMAT(date, '%m-%d') >= DATE_FORMAT("2015-01-01", '%m-%d')
		OR DATE_FORMAT(date, '%m-%d') <= DATE_FORMAT("2015-02-10", '%m-%d'))) */
		
		// FROM
		$m_from = date("m",strtotime($period_from));
		$d_from = date("d",strtotime($period_from));
		
		// TO
		$m_to = date("m",strtotime($period_to));
		$d_to = date("d",strtotime($period_to));
		
		$this->db->where("(
				DATE_FORMAT('{$period_from}', '%m-%d') < '12-2'
				AND DATE_FORMAT(date, '%m-%d') >= DATE_FORMAT('{$period_from}', '%m-%d')
				AND DATE_FORMAT(date, '%m-%d') <= DATE_FORMAT('{$period_to}', '%m-%d')
			)
			OR (
				DATE_FORMAT('{$period_from}', '%m-%d') >= '12-2'
				AND (
					DATE_FORMAT(date, '%m-%d') >= DATE_FORMAT('{$period_from}', '%m-%d')
					OR DATE_FORMAT(date, '%m-%d') <= DATE_FORMAT('{$period_to}', '%m-%d')
				)
			)
		");
		
		$where = array(
			'holiday.company_id' => $company_id,
			'holiday.status'	 => 'Active'
		);
		$this->db->where($where);
		
		$q = $this->db->get('holiday');
		$r = $q->result();
		if($r){
			$flag = FALSE;
			foreach($r as $row){
				if($row->date_type == "fixed"){
					$flag = TRUE;
				}else if($row->date_type == "movable"){
					
					// SAME YEAR
					if(date("Y",strtotime($period_from)) == date("Y",strtotime($period_to))){
						$year_from = date("Y",strtotime($period_from));
						$new_date = date("{$year_from}-m-d",strtotime($row->date));
						if( strtotime($period_from) <= strtotime($new_date) && strtotime($new_date) <= strtotime($period_to) ){
							$flag = TRUE;
						}
					}else{
						
						// if(date("m",strtotime($row->date)) == "12"){
						if(date("m",strtotime($row->date)) == date("m",strtotime($period_from))){
							$year_from = date("Y",strtotime($period_from));
							$new_date = date("{$year_from}-m-d",strtotime($row->date));
							if( strtotime($period_from) <= strtotime($new_date) && strtotime($new_date) <= strtotime($period_to) ){
								$flag = TRUE;
							}
						}else{
							$year_from = date("Y",strtotime($$period_to));
							$new_date = date("{$year_from}-m-d",strtotime($row->date));
							if( strtotime($period_from) <= strtotime($new_date) && strtotime($new_date) <= strtotime($period_to) ){
								$flag = TRUE;
							}
						}
						
					}
					
				}
			}
			return $flag;
				
		}else{
			return FALSE;
		}
		
	}
	
	/**
	 * Get Employee Loans Payroll Payslip
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $payroll_period
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function get_employee_loans_payroll_payslip($emp_id,$company_id,$payroll_period,$period_from,$period_to){
		$this->db->select("SUM(installment) AS total");
		$w = array(
			'emp_id'=>$emp_id,
			'payroll_date'=>$payroll_period,
			'period_from'=>$period_from,
			'period_to'=>$period_to,
			'company_id'=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_run_loans");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Get Employee Loans Payroll Payslip
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $payroll_period
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function get_employee_not_adjustment_government_loans_payroll_payslip($emp_id,$company_id,$payroll_period,$period_from,$period_to){
		$this->db->select("SUM(amount) AS total");
		$w = array(
			'emp_id'=>$emp_id,
			'payroll_period'=>$payroll_period,
			'period_from'=>$period_from,
			'period_to'=>$period_to,
			'company_id'=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_run_government_loans");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Get Employee Other Deductions Payroll Payslip
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $payroll_period
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function get_employee_other_deductions_payroll_payslip($emp_id,$company_id,$payroll_period,$period_from,$period_to){
		$this->db->select("SUM(amount) AS total");
		$w = array(
			'emp_id'=>$emp_id,
			'payroll_period'=>$payroll_period,
			'period_from'=>$period_from,
			'period_to'=>$period_to,
			'company_id'=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_for_other_deductions");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Get Employee Adjustment Third Party Payroll Payslip
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $payroll_period
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function get_employee_third_party_payroll_payslip($emp_id,$company_id,$payroll_period,$period_from,$period_to){
		$this->db->select("SUM(amount) AS total");
		$w = array(
			'emp_id'=>$emp_id,
			'payroll_period'=>$payroll_period,
			'period_from'=>$period_from,
			'period_to'=>$period_to,
			'company_id'=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_adjustment_third_party_loan");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Get Employee Adjustment Government Loans Payroll Payslip
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $payroll_period
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function get_employee_government_loans_payroll_payslip($emp_id,$company_id,$payroll_period,$period_from,$period_to){
		$this->db->select("SUM(amount) AS total");
		$w = array(
			'emp_id'=>$emp_id,
			'payroll_period'=>$payroll_period,
			'period_from'=>$period_from,
			'period_to'=>$period_to,
			'company_id'=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_adjustment_government_loan");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Get Employee Adjustment Government Loans Payroll Payslip
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $payroll_period
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function get_employee_adjustment_other_deductions_payroll_payslip($emp_id,$company_id,$payroll_period,$period_from,$period_to){
		$this->db->select("SUM(amount) AS total");
		$w = array(
			'emp_id'=>$emp_id,
			'payroll_period'=>$payroll_period,
			'period_from'=>$period_from,
			'period_to'=>$period_to,
			'company_id'=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_adjustment_other_deduction");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Get Employee Adjustment Absences Payroll Payslip
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $payroll_period
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function get_employee_absences_payroll_payslip($emp_id,$company_id,$payroll_period,$period_from,$period_to){
		$this->db->select("SUM(amount) AS total");
		$w = array(
			'emp_id'=>$emp_id,
			'payroll_period'=>$payroll_period,
			'period_from'=>$period_from,
			'period_to'=>$period_to,
			'company_id'=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_adjustment_absences");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Get Employee Adjustment Tardiness Payroll Payslip
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $payroll_period
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function get_employee_tardiness_payroll_payslip($emp_id,$company_id,$payroll_period,$period_from,$period_to){
		$this->db->select("SUM(amount) AS total");
		$w = array(
			'emp_id'=>$emp_id,
			'payroll_period'=>$payroll_period,
			'period_from'=>$period_from,
			'period_to'=>$period_to,
			'company_id'=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_adjustment_tardiness");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Get Employee Adjustment Undertime Payroll Payslip
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $payroll_period
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function get_employee_undertime_payroll_payslip($emp_id,$company_id,$payroll_period,$period_from,$period_to){
		$this->db->select("SUM(amount) AS total");
		$w = array(
			'emp_id'=>$emp_id,
			'payroll_period'=>$payroll_period,
			'period_from'=>$period_from,
			'period_to'=>$period_to,
			'company_id'=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_adjustment_undertime");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Get Employee Fixed Allowances Payroll Payslip
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $payroll_period
	 * @param unknown $period_from
	 * @param unknown $period_to
	 */
	public function get_employee_fixed_allowances_payroll_payslip($emp_id,$company_id,$payroll_period,$period_from,$period_to){
		$this->db->select("SUM(amount) AS total");
		$w = array(
			'emp_id'=>$emp_id,
			'payroll_period'=>$payroll_period,
			'period_from'=>$period_from,
			'period_to'=>$period_to,
			'company_id'=>$company_id,
			'taxable'=>"Exempt"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_allowances");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * For Recalculation Purpose Payroll Run Employee Counter
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $q
	 * @param unknown_type $pay_period
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $sort_param
	 */
	public function recal_payroll_run_employee_counter($payroll_group_id,$company_id,$q,$pay_period,$period_from,$period_to,$sort_param){
		
		$konsum_key = konsum_key();
		
		// GET EMPLOYEE EXCLUDED LISTS
		// $employee_excluded_lists = $this->employee_excluded_lists($company_id);
		$employee_excluded_lists = $this->employee_excluded_lists($company_id, $sort_param);
		
		// GET EMPLOYEE NGA MANA PA RUN SA HR
		$check_employee_already_run = $this->check_employee_already_run($sort_param);
		
		$s = array(
			// "COUNT(e.emp_id) AS total"
			"COUNT(DISTINCT e.emp_id) AS total" // tagsa rani mahitabo nga mag doble ang employee sa usa ka pay run (for custom payroll run trapping ni)
		);
		$this->db->select($s);
	
		if($q !==""){
			$this->db->where("
					epl.payroll_group_id = {$payroll_group_id}
					AND e.company_id = {$company_id}
					AND e.status = 'Active'
					AND epl.employee_status = 'Active'
					AND (convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
					OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
				
			// AND epl.tax_status >= -1
		}
	
		$w = array(
			// "epl.tax_status >= " => "-1",
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active",
			"epl.employee_status"=>"Active",
			
			// NEW
			"pp.payroll_date"=>$pay_period,
			"pp.period_from"=>$period_from,
			"pp.period_to"=>$period_to
		);
		$this->db->where($w);
		
		// CHECK KUNG ANG EMPLOYEE NA EXCLUDE WITH IN SA PAYROLL PERIOD, BAG.O NI SYA
		if(isset($sort_param["exclude_view"]) == ""){
			// GET EMPLOYEE EXCLUDED LISTS
			if($employee_excluded_lists != FALSE){
				$this->db->where_not_in("e.emp_id", $employee_excluded_lists);
			}
		}
		
		if(isset($sort_param["pay_run_history_counter"]) == 1){
			// do nothing	
		}else{
			
			// GET EMPLOYEE NGA MANA PA RUN SA HR
			if($check_employee_already_run != FALSE){
				$this->db->where_not_in("e.emp_id", $check_employee_already_run);
			}
			
		}
		
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
	
		// BAG.O RANI G ADD FOR PAYROLL PAYSLIP PURPOSE
		$this->edb->join("payroll_payslip AS pp","pp.emp_id = e.emp_id","INNER");
	
		$q = $this->edb->get("employee AS e");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * For Recalculation Purpose Payroll Run Employee Counter
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $q
	 * @param unknown_type $pay_period
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 */
	public function recal_custom_payroll_run_employee_counter($draft_pay_run_id,$company_id,$q,$pay_period,$period_from,$period_to,$sort_param){
		
		$konsum_key = konsum_key();
		
		// GET EMPLOYEE EXCLUDED LISTS
		// $employee_excluded_lists = $this->employee_excluded_lists($company_id);
		$employee_excluded_lists = $this->employee_excluded_lists($company_id, $sort_param);
		
		// GET EMPLOYEE NGA MANA PA RUN SA HR
		$check_employee_already_run = $this->check_employee_already_run($sort_param);
		
		$s = array(
			// "COUNT(e.emp_id) AS total"
			"COUNT(DISTINCT e.emp_id) AS total" // tagsa rani mahitabo nga mag doble ang employee sa usa ka pay run (for custom payroll run trapping ni)
		);
		$this->db->select($s);
	
		if($q !==""){
			$this->db->where("
					prc.draft_pay_run_id = {$draft_pay_run_id}
					AND e.company_id = {$company_id}
					AND e.status = 'Active'
					AND epl.employee_status = 'Active'
					AND (convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
					OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
				
			// AND epl.tax_status >= -1
		}
	
		$w = array(
			// "epl.tax_status >= " => "-1",
			"prc.draft_pay_run_id"=>$draft_pay_run_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active",
			"epl.employee_status"=>"Active",
				
			// NEW
			"pp.payroll_date"=>$pay_period,
			"pp.period_from"=>$period_from,
			"pp.period_to"=>$period_to
		);
		$this->db->where($w);
		
		// CHECK KUNG ANG EMPLOYEE NA EXCLUDE WITH IN SA PAYROLL PERIOD, BAG.O NI SYA
		if(isset($sort_param["exclude_view"]) == ""){
			// GET EMPLOYEE EXCLUDED LISTS
			if($employee_excluded_lists != FALSE){
				$this->db->where_not_in("e.emp_id", $employee_excluded_lists);
			}
		}
		
		if(isset($sort_param["pay_run_history_counter"]) == 1){
			// do nothing
		}else{
			
			// GET EMPLOYEE NGA MANA PA RUN SA HR
			if($check_employee_already_run != FALSE){
				$this->db->where_not_in("e.emp_id", $check_employee_already_run);
			}
		}
		
		$this->edb->join("employee AS e","e.emp_id = prc.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
	
		// BAG.O RANI G ADD FOR PAYROLL PAYSLIP PURPOSE
		$this->edb->join("payroll_payslip AS pp","pp.emp_id = prc.emp_id","INNER");
	
		$q = $this->edb->get("payroll_run_custom AS prc");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Ge Payroll Payslip By Employee
	 * @param unknown $params
	 */
	public function get_payroll_payslip($params){
		$w = array(
			"payroll_date"=>$params["payroll_period"],
			"period_from"=>$params["period_from"],
			"period_to"=>$params["period_to"],
			"emp_id"=>$params["emp_id"],
			"company_id"=>$params["company_id"],
		);
		$this->db->where($w);
		$q = $this->edb->get("payroll_payslip");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Basic Pay for Fortnightly Only
	 * @param unknown_type $comp_id
	 * @param unknown_type $emp_id
	 */
	public function check_basic_pay_for_fortnightly($comp_id,$emp_id,$period){
		
		$bp = 0;
		
		// check if employee salary is set
		$basic_pay = $this->get_basic_pay($comp_id,$emp_id);
	
		if (!$basic_pay) {
			return false;
		}
	
		// check new basic pay
		$edate = date('Y-m-d',strtotime($basic_pay->effective_date));
		if (is_object($period)) {
			$pfrom = date('Y-m-d',strtotime($period->period_from));
			$pto   = date('Y-m-d',strtotime($period->period_to));
		} elseif (is_array($period)) {
			$pfrom = date('Y-m-d',strtotime($period['period_from']));
			$pto   = date('Y-m-d',strtotime($period['period_to']));
		}
	
		if ($basic_pay->effective_date) {
			if (($pfrom <= $edate && $pto >= $edate) || $edate <= $pfrom) {
				$bp = $basic_pay->new_basic_pay;
			} else {
				$bp = $basic_pay->current_basic_pay;
			}
		} else {
			$bp = $basic_pay->current_basic_pay;
		}
	
		return $bp;
	}
	
	/**
	 * Employee De Minimis Excess Taxable or Non Taxable
	 * @param unknown $params
	 */
	public function employee_de_minimis_excess_by_rules_taxable_or_non_taxable($employee_de_minimis_params){
		$w = array(
			"company_id"=>$employee_de_minimis_params["company_id"],
			"emp_id"=>$employee_de_minimis_params["emp_id"],
			"payroll_period"=>$employee_de_minimis_params["payroll_period"],
			"period_from"=>$employee_de_minimis_params["period_from"],
			"period_to"=>$employee_de_minimis_params["period_to"]
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_de_minimis");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Third Payty Loans Adjustment
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function check_third_party_loans_adjustment($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_adjustment_third_party_loan");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Government Loans Adjustment
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function check_governemnt_loans_adjustment($emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_carry_over_adjustment_government_loan");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Payroll Run Employee Counter
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $q
	 */
	public function excluded_payroll_run_employee_counter($payroll_group_id,$company_id,$q){
		$konsum_key = konsum_key();
		$s = array(
			"COUNT(e.emp_id) AS total"
		);
		$this->db->select($s);
	
		if($q !==""){
			
			// CHECK EXCLUDE EMPLOYEE BY PAYROLL PERIOD
			if(isset($q["payroll_period"]) == ""){
			
				$this->db->where("
						epl.payroll_group_id = {$payroll_group_id}
				AND e.company_id = {$company_id}
				AND e.status = 'Active'
				AND epl.employee_status = 'Active'
				AND (convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
				
				// AND epl.tax_status >= -1
				
			}
			
		}
	
		$w = array(
			// "epl.tax_status >= " => "-1",
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active",
			"epl.employee_status"=>"Active",
			"el.exclude > "=> 0
		);
		$this->db->where($w);
	
		// CHECK EXCLUDE EMPLOYEE BY PAYROLL PERIOD
		if(isset($q["payroll_period"]) != ""){
			$exclude_w = array(
				"el.payroll_period"=>$q["payroll_period"],
            	"el.period_from"=>$q["period_from"],
            	"el.period_to"=>$q["period_to"],
            	"el.company_id"=>$q["company_id"],
				"el.status"=>"Active"
			);
			$this->db->where($exclude_w);
		}
		
		$this->edb->join("exclude_list AS el","el.emp_id = e.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
	
		$q = $this->edb->get("employee AS e");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Payroll Run Employee Counter
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $q
	 */
	public function excluded_custom_payroll_run_employee_counter($draft_pay_run_id,$company_id,$q){
		$konsum_key = konsum_key();
		$s = array(
				"COUNT(e.emp_id) AS total"
		);
		$this->db->select($s);
	
		if($q !==""){
			
			// ADDITIONAL TRAPPING FOR EXCLUDE LIST PURPOSE
			if(isset($q["payroll_period"]) == ""){
			
				$this->db->where("
						prc.draft_pay_run_id = {$draft_pay_run_id}
				AND e.company_id = {$company_id}
				AND e.status = 'Active'
				AND epl.employee_status = 'Active'
				AND (convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$q."%')
				OR AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')  LIKE '%".$q."%'
				", NULL, FALSE); // encrypt
				
				// AND epl.tax_status >= -1
				
			}
		}
	
		$w = array(
			// "epl.tax_status >= " => "-1",
			"prc.draft_pay_run_id"=>$draft_pay_run_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active",
			"epl.employee_status"=>"Active",
			"el.exclude > "=> 0
		);
		$this->db->where($w);
		
		// CHECK EXCLUDE EMPLOYEE BY PAYROLL PERIOD
		if(isset($q["payroll_period"]) != ""){
			$exclude_w = array(
				"el.payroll_period"=>$q["payroll_period"],
				"el.period_from"=>$q["period_from"],
				"el.period_to"=>$q["period_to"],
				"el.company_id"=>$q["company_id"],
				"el.status"=>"Active"
			);
			$this->db->where($exclude_w);
		}
		
		$this->edb->join("exclude_list AS el","el.emp_id = prc.emp_id","INNER");
		$this->edb->join("employee AS e","e.emp_id = prc.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
	
		$q = $this->edb->get("payroll_run_custom AS prc");
		$r = $q->row();
		return ($r) ? $r->total : FALSE ;
	}
	
	/**
	 * Payroll Run Employee Counter
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 * @param unknown_type $q
	 */
	public function employee_already_run_exclude($payroll_group_id,$company_id,$q,$data_param){
		
		/* $emp_id = $data_param["emp_id"];
		// check from payroll custom list
		$w = array(
				"pp.emp_id"=>$emp_id,
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
		$r = $q->row();
		$q->free_result();
		if($r){
			return $r;
		}else{
			$w = array(
					"pp.emp_id"=>$emp_id,
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
			$this->db->join("draft_pay_runs AS dpr","epi.payroll_group_id = dpr.payroll_group_id","LEFT");
			$q = $this->db->get("payroll_payslip AS pp");
			$r = $q->row();
			$q->free_result();
			return ($r) ? $r : FALSE ;
		} */
		
		$total = 0;
		$konsum_key = konsum_key();
		
		// BY PAYROLL GROUP
		$s = array(
			"COUNT(e.emp_id) AS total"
		);
		$this->db->select($s);
	
		$w = array(
			// "epl.tax_status >= " => "-1",
			"epl.payroll_group_id"=>$payroll_group_id,
			"e.company_id"=>$company_id,
			"e.status"=>"Active",
			"epl.employee_status"=>"Active"
		);
		$this->db->where($w);
	
		$w2 = array(
			"dpr.company_id"=>$company_id,
			"dpr.pay_period"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
			"dpr.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
			"dpr.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
			"pp.payroll_date"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
			"pp.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
			"pp.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
			"dpr.status"=>"Active"
		);
		$this->db->where($w2);
		$this->db->where("(dpr.view_status = 'Closed' OR dpr.view_status = 'Waiting for approval')");
		
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
	
		$this->edb->join("payroll_payslip AS pp","pp.emp_id = e.emp_id","LEFT");
		$this->edb->join("draft_pay_runs AS dpr","epl.payroll_group_id = dpr.payroll_group_id","LEFT");
		
		$q = $this->edb->get("employee AS e");
		$r = $q->row();
		if($r){ $total += $r->total; }
		
		// =====================================
		
		// BY CUSTOM EMPLOYEE
		$s = array(
			"COUNT(e.emp_id) AS total"
		);
		$this->db->select($s);
		
		$w = array(
			"e.company_id"=>$company_id,
			"e.status"=>"Active",
			"epl.employee_status"=>"Active"
		);
		$this->db->where($w);
		
		$w2 = array(
			"dpr.company_id"=>$company_id,
			"dpr.pay_period"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
			"dpr.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
			"dpr.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
			"prc.payroll_period"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
			"prc.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
			"prc.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
			"dpr.status"=>"Active"
		);
		$this->db->where($w2);
		$this->db->where("(dpr.view_status = 'Closed' OR dpr.view_status = 'Waiting for approval')");
		
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epl","epl.emp_id = e.emp_id","LEFT");
		$this->edb->join("department AS d","d.dept_id = epl.department_id","LEFT");
		$this->edb->join("basic_pay_adjustment AS bpa","bpa.emp_id = e.emp_id","LEFT");
		
		$this->edb->join("payroll_run_custom AS prc","prc.emp_id = e.emp_id","LEFT");
		$this->edb->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
		
		$q = $this->edb->get("employee AS e");
		$r = $q->row();
		if($r){ $total += $r->total; }
		
		return $total;
	}
	
	/**
	 * Employee Excluded Lists
	 * @param unknown $company_id
	 * @param unknown $sort_param bag.o in
	 */
	public function employee_excluded_lists($company_id, $sort_param=NULL){
		$ignore = array();
		$w = array(
			"company_id"=>$company_id,
			"exclude"=> 1,
			// BAG.O NPUD NI ZZZZZZZ para dili na global ang pag exclude sa employee, by payroll period na ang pag exclude ani tungod sa kadaghan versions zzzzzzzzzzz
			"status"=> "Active",
			"payroll_period"=>$sort_param["payroll_period"],
			"period_from"=>$sort_param["period_from"],
			"period_to"=>$sort_param["period_to"]
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
	
	/**
	 * Check Employee Already Run
	 * @param unknown $data_param
	 */
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
	
	/**
	 * Check New Employee Exclude Function
	 * @param unknown $val_employee_custom_pr
	 */
	public function check_new_exclude_employee_function($params){
		$w = array(
			"emp_id"=>$params["emp_id"],
			"company_id"=>$params["company_id"],
			"payroll_period"=>$params["payroll_period"],
			"period_from"=>$params["period_from"],
			"period_to"=>$params["period_to"],
			"status"=>"Active",
		);
		$this->db->where($w);
		$q = $this->db->get("exclude_list");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Delete Payroll Payslip Data (Excluded Employee, For Update Employee Payroll Payslip)
	 * @param unknown $params
	 */
	public function delete_payroll_payslip_data($params){
	
		// VARIABLES
		$emp_id = $params["emp_id"];
		$payroll_period = $params["payroll_period"];
		$period_from = $params["period_from"];
		$period_to = $params["period_to"];
		// $payroll_group_id = $params["payroll_group_id"];
		$company_id = $params["company_id"];
	
		// DELETE PAYROLL PAYSLIP
		$del_array = array(
				'emp_id'=>$emp_id,
				'payroll_date'=>$payroll_period,
				'period_from'=>$period_from,
				'period_to'=>$period_to,
				// 'payroll_group_id'=>$payroll_group_id,
				'company_id'=>$company_id
		);
		$del_q = $this->db->delete("payroll_payslip",$del_array);
	
		// DELETE DE MINIMIS
		$delete_payroll_deminimis_where = array(
				"emp_id"=>$emp_id,
				"company_id"=>$company_id,
				"payroll_period"=>$payroll_period,
				"period_from"=>$period_from,
				"period_to"=>$period_to
		);
		$this->db->where($delete_payroll_deminimis_where);
		$this->db->delete("payroll_de_minimis");
	
		// DELETE ALLOWANCES
		$allowance_where = array(
				"emp_id"=>$emp_id,
				"company_id"=>$company_id,
				"payroll_period"=>$payroll_period,
				"period_from"=>$period_from,
				"period_to"=>$period_to
		);
		$this->db->where($allowance_where);
		$this->db->delete("payroll_allowances");
	
		// DELETE EMPLOYEE COMMISSIONS
		$where = array(
				"emp_id"=>$emp_id,
				"company_id"=>$company_id,
				"payroll_period"=>$payroll_period,
				"period_from"=>$period_from,
				"period_to"=>$period_to
		);
		$this->db->where($where);
		$this->db->delete("payroll_commission");
	
		// DELETE OTHER DEDUCTIONS
		$del_array2 = array(
				'emp_id'=>$emp_id,
				'payroll_period'=>$payroll_period,
				'period_from'=>$period_from,
				'period_to'=>$period_to,
				'company_id'=>$company_id
		);
		$this->db->where($del_array2);
		$del_q = $this->db->delete("payroll_for_other_deductions",$del_array2);
	
		// DELETE EMPLOYEE LOANS
		$del_array2 = array(
				'emp_id'=>$emp_id,
				'payroll_date'=>$payroll_period,
				'period_from'=>$period_from,
				'period_to'=>$period_to,
				'company_id'=>$company_id
		);
		$this->db->where($del_array2);
		$delete_employee_loans = $this->db->delete("payroll_run_loans");
	
		// DELETE GOVERNMENT LOANS
		$del_array2 = array(
				'emp_id'=>$emp_id,
				'payroll_period'=>$payroll_period,
				'period_from'=>$period_from,
				'period_to'=>$period_to,
				'company_id'=>$company_id
		);
		$this->db->where($del_array2);
		$delete_employee_loans = $this->db->delete("payroll_run_government_loans");
	
		// EMPLOYEE HOURS
		$this->db->where($del_array2);
		$this->db->delete("payroll_employee_hours");
	
		// EMPLOYEE OTHER EARNINGS FOR ASHIMA LITE ONLY
		if($this->flag_ashima_lite == 1 || $this->flag_ashima_lite == 0){ // ashima lite only, ashima also
			$this->db->where($del_array2);
			$this->db->delete("payroll_other_earnings_lite");
		}
	
		// EMPLOYEE LOCATION
		$del_array = array(
				'emp_id'=>$emp_id,
				'payroll_period'=>$payroll_period,
				'period_from'=>$period_from,
				'period_to'=>$period_to,
				'company_id'=>$company_id
		);
		$this->db->where($del_array);
		$this->db->delete("payroll_payslip_location");
	
		return TRUE;
	}
	
	/**
	 * Check Overtime Application for Night Differential
	 * @param unknown $company_id
	 * @param unknown $emp_id
	 * @param unknown $start
	 * @param unknown $end
	 */
	public function check_overtime_appplication_for_nd($company_id,$emp_id,$start,$end){
		$where = array(
			'emp_id'  => $emp_id,
			'company_id' => $company_id,
			'status' => "Active"
		);
		$this->db->where($where);
		// $this->db->where("( (overtime_from >= '{$start}' AND overtime_from <= '{$end}') OR (overtime_to >= '{$start}' AND overtime_to <= '{$end}') )");
		$this->db->where("(overtime_to >= '{$start}' AND overtime_to <= '{$end}')");
		#$this->db->where('date >=',$start);
		#$this->db->where('date <=',$end);
		$q = $this->db->get('employee_overtime_application');
		$result = $q->result();
		return ($result) ? $result : FALSE;
	}
	
	/**
	 * Check Night Differential and Pay Rate Flag
	 * @param unknown $company_id
	 */
	public function night_diff_payrate_flag($company_id){
		$w = array(
			"flag_updated > "=> 0,
			"company_id"=>$company_id
			// "status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("nightshift_differential_settings");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * New Night Differential Settings Version 1.6.1
	 * @param unknown $nightshift_differential_rule_name
	 * @param unknown $company_id
	 */
	public function new_night_differential_settings_v2($nightshift_differential_rule_name,$company_id){
		$w = array(
			"enable"=>"yes",
			"company_id"=>$company_id,
			"nightshift_differential_rule_name"=>$nightshift_differential_rule_name
		);
		$this->db->where($w);
		$q = $this->db->get('nightshift_differential_settings_inclusion');
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}

	/**
	 * Get NEw Night Differential Premium
	 * @param unknown $company_id
	 * @return Ambigous <boolean, unknown>
	 */
	public function get_new_night_differential_premium($company_id){
		$s = array(
			"ndfp.pay_rate AS nd_pay_rate"
		);
		$this->db->select($s);
		$where = array(
			'ht.hour_type_name' => "Regular",
			'ht.company_id' => $company_id,
			'ht.status'	 => 'Active'
		);
		$this->db->where($where);
		$this->db->join('hours_type AS ht','ht.hour_type_id = ndfp.hours_type_id','LEFT');
		$q = $this->db->get('nightshift_differential_for_premium AS ndfp');
		$row = $q->row();
		return ($row) ? $row->nd_pay_rate : FALSE ;
		
	}
	
	/**
	 * Get WTTax Fixed 
	 * @param unknown $pay_period
	 * @param unknown $period_from
	 * @param unknown $period_to
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	public function get_wttax_fixed($pay_period,$period_from,$period_to,$emp_id,$company_id){
		$w = array(
			"payroll_period"=>$pay_period,
			"period_from"=>$period_from,
			"period_to"=>$period_to,
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_wttax_fixed");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Employee Other Earnings for Lite only
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function new_other_earnings($emp_id,$company_id,$param_data){
		$s = array(
			"*",
			"poe.amount AS amount"				
		);
		$this->db->select($s);
		$w = array(
			"poe.company_id"=>$company_id,
			"poe.emp_id"=>$emp_id,
			"poe.payroll_period"=>$param_data["payroll_period"],
			"poe.period_from"=>$param_data["period_from"],
			"poe.period_to"=>$param_data["period_to"],
			"poe.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("earnings_other_earnings AS eoe","eoe.earnings_other_earnings_id = poe.earnings_other_earnings_id","LEFT");
		$q = $this->db->get("payroll_other_earnings_lite AS poe");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Workflow Notification Settings if OFF
	 * @param unknown $company_id
	 */
	public function check_workflow_notification_settings($company_id){
		$w = array(
			"status"=>"Inactive",
			"company_id"=>$company_id,
		);
		$this->db->where($w);
		$q = $this->db->get("approval_settings");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}

	/**
	 * Check Empoyee Overtime Application
	 * @param unknown $emp_id
	 * @param unknown $time_in
	 * @param unknown $company_id
	 * @return Ambiguous
	 */
	public function check_emp_overtime($emp_id,$time_in,$company_id){
		
		$date_where = array(
			"overtime_from <= " => $time_in,
			"overtime_to >= " => $time_in
		);
		$this->db->where($date_where);
		
		$where = array(
			'company_id' 	  => $company_id,
			'emp_id'	 	  => $emp_id,
			'overtime_status' => 'approved',
			'status'		  => 'Active'
		);
		$this->db->where($where);
		$q = $this->db->get('employee_overtime_application');
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Night Diff Double Holiday
	 * @param unknown $company_id
	 * @param unknown $date
	 */
	public function night_diff_double_holiday($company_id,$date){ // check_double_holiday($company_id,$date){
	
		$where = array(
			'holiday.company_id' => $company_id,
			'MONTH(holiday.date)'	=> date("m",strtotime($date)),
			'DAY(holiday.date)'		=> date("d",strtotime($date)),
			'holiday.status'	 => 'Active'
		);
		$this->db->where($where);
		$this->db->join('hours_type','hours_type.hour_type_id = holiday.hour_type_id','left');
		$this->db->join('overtime_type','overtime_type.hour_type_id = holiday.hour_type_id','left');
		$q = $this->db->get('holiday');
		$r = $q->result();
	
		if($r){
	
			$cnt = 0;
			// foreach($r as $row){ $cnt++; }
			foreach($r as $row){
				if($row->date_type == "fixed" || ($row->date_type == "movable" && $row->date == $date)){
					$cnt++;
				}
			}
	
			if($cnt >= 2){
	
				// DOUBLE HOLIDAY
				$s = array(
					"*",
					"ndfp.pay_rate AS pay_rate", // night diff pay rate
					"ht.working AS holiday_working_pay_rate"
				);
				$this->db->select($s);
	
				$where = array(
					"ht.hour_type_name"=>"Double Holiday",
					'ht.company_id' => $company_id,
					'ht.status'	 => 'Active'
				);
				$this->db->where($where);
				$this->db->join('holiday AS h','h.hour_type_id = ht.hour_type_id','LEFT');
				$this->db->join('nightshift_differential_for_premium AS ndfp','ndfp.hours_type_id = ht.hour_type_id','LEFT'); // bag.o ni na join
				$this->db->join('overtime_type AS ot','ot.hour_type_id = ht.hour_type_id','LEFT'); // bag.o ni na join
				$q = $this->db->get('hours_type AS ht');
				$result = $q->row();
				return ($result) ? $result : false;
	
			}else{
				return FALSE;
			}
	
		}else{
			return FALSE;
		}
	
	}
	
	/**
	 * Check Overtime Double Holiday
	 * @param unknown $company_id
	 * @param unknown $date
	 */
	public function ot_double_holiday($company_id,$date){ // check_double_holiday($company_id,$date){
	
		$where = array(
			'holiday.company_id' => $company_id,
			'MONTH(holiday.date)'	=> date("m",strtotime($date)),
			'DAY(holiday.date)'		=> date("d",strtotime($date)),
			'holiday.status'	 => 'Active'
		);
		$this->db->where($where);
		$this->db->join('hours_type','hours_type.hour_type_id = holiday.hour_type_id','left');
		$this->db->join('overtime_type','overtime_type.hour_type_id = holiday.hour_type_id','left');
		$q = $this->db->get('holiday');
		$r = $q->result();
	
		if($r){
	
			$cnt = 0;
			// foreach($r as $row){ $cnt++; }
			foreach($r as $row){
				if($row->date_type == "fixed" || ($row->date_type == "movable" && $row->date == $date)){
					$cnt++;
				}
			}
	
			if($cnt >= 2){
	
				// DOUBLE HOLIDAY
				$where = array(
					"ht.hour_type_name"=>"Double Holiday",
					'ht.company_id' => $company_id,
					'ht.status'	 => 'Active'
				);
				$this->db->where($where);
				$this->db->join('holiday AS h','h.hour_type_id = ht.hour_type_id','LEFT');
				$this->db->join('overtime_type AS ot','ot.hour_type_id = ht.hour_type_id','LEFT'); // bag.o ni na join
				$q = $this->db->get('hours_type AS ht');
				$result = $q->row();
				return ($result) ? $result : false;
	
			}else{
				return FALSE;
			}
	
		}else{
			return FALSE;
		}
	
	}
	
	/**
	 * Get Other Deductions for employee single row 2.0
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function other_dd_emp_single($emp_id,$company_id,$deduction_id){
		$s = array("*","ed.amount AS amount","ed.recurring AS recurring");
		$this->db->select($s);
		$this->db->where("(ed.deduction_status != 'done' OR ed.deduction_status IS NULL)");
		$w = array(
			"ed.emp_id"=>$emp_id,
			"ed.company_id"=>$company_id,
			"ed.deduction_type_id"=>$deduction_id,
			"ed.status"=>"Active",
			"dd.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("deductions_other_deductions AS dd","dd.deductions_other_deductions_id = ed.deduction_type_id","left");
		$q = $this->db->get("employee_deductions AS ed");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Contributions Settings: Verions 1.7.1
	 * @param unknown $company_id
	 */
	public function check_contribution_settings($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("contribution_calculation_settings");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Draft Pay Runs
	 * @param unknown $date_last_month
	 * @param unknown $year
	 * @param unknown $company_id
	 */
	public function get_draft_pay_runs($date_last_month,$year,$company_id){
		$where = array(
			"MONTH(pay_period)"=>$date_last_month,
			"YEAR(pay_period)"=>$year,
			"company_id"=>$company_id,
			"view_status"=>"Closed",
			"status"=>"Active"
		);
		$this->db->where($where);
		$q = $this->db->get("draft_pay_runs");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Employee Payroll Status Closed Pending
	 * @param unknown $params
	 */
	public function get_employee_payroll_status_closed_or_pending($data_param){
		
		$arrs = array();
		
		// PAYROLL CUSTOM
		$w = array(
			"prc.company_id"=>$this->company_id,
			"prc.payroll_period"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
			"prc.period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
			"prc.period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
			"prc.status"=>"Active",
			"dpr.token"=>$data_param["token"]
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
			"dpr.token"=>$data_param["token"]
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
	
	/**
	 * Get Payroll Draft Pay Run ID status = open
	 * @param unknown $sort_param
	 */
	public function get_draft_pay_run_id_open($data_param){
		$w = array(
			"pay_period"=>date("Y-m-d",strtotime($data_param["payroll_period"])),
			"period_from"=>date("Y-m-d",strtotime($data_param["period_from"])),
			"period_to"=>date("Y-m-d",strtotime($data_param["period_to"])),
			"status"=>"Active",
			"view_status"=>"Open",
			"flag_save"=>1
		);
		$this->db->where($w);
		$q = $this->db->get("draft_pay_runs");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Payroll Adjustment
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $param_data
	 */
	public function check_payroll_adjustment($table,$emp_id,$company_id,$param_data){
		$w = array(
			"company_id"=>$company_id,
			"emp_id"=>$emp_id,
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("{$table}");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Draft Pay Run Flag Save and Open viw status
	 * @param unknown $company_id
	 */
	public function get_draft_pay_run_save_open($param_data){
		$w = array(
			"company_id"=>$param_data["company_id"],
			"pay_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"],
			"status"=>"Active",
			"view_status"=>"Open",
			"flag_save"=>1
		);
		$this->db->where($w);
		$q = $this->db->get("draft_pay_runs");
		$r = $q->result();
		return ($r) ? $r : FALSE ;
	}
	
	public function get_payroll_pre_run_history($param_data,$row_dpr_array){
		$w = array(
			"company_id"=>$param_data["company_id"],
			"payroll_period"=>$param_data["payroll_period"],
			"period_from"=>$param_data["period_from"],
			"period_to"=>$param_data["period_to"]
		);
		$this->db->where($w);
		$this->db->where_in("draft_pay_run_id",$row_dpr_array);
		$this->db->order_by("date_time","DESC");
		$q = $this->db->get("payroll_pre_run_history");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Payroll File Batch Number for BPI
	 * @param unknown $payroll_period
	 * @param unknown $company_id
	 */
	public function check_batch_number_bpi($payroll_period,$company_id){
	
		$date = date("Y-m-d");
	
		$w = array(
				"company_id"=>$company_id,
				"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_file_batch_number_bpi");
		$r = $q->row();
		if($r){
			$w2 = array(
					"date"=>$date,
					"company_id"=>$company_id,
					"status"=>"Active"
			);
			$this->db->where($w2);
			$q2 = $this->db->get("payroll_file_batch_number_bpi");
			$r2 = $q2->row();
			if($r2){
				// update counter
				$batch_number = $r2->counter + 1;
	
				$update_val = array(
						"counter"=>$batch_number
				);
				$this->db->where($w2);
				$this->db->update("payroll_file_batch_number_bpi",$update_val);
				return $batch_number;
			}else{
				// update date [reset counter]
	
				$batch_number = 1;
				$update_val = array(
						"date"=>$date,
						"counter"=>$batch_number
				);
				$w2 = array(
						"company_id"=>$company_id,
						"status"=>"Active"
				);
				$this->db->where($w2);
				$this->db->update("payroll_file_batch_number_bpi",$update_val);
				return $batch_number;
			}
				
		}else{
			// insert new
			$val = array(
					"date"=>$date,
					"company_id"=>$company_id,
					"counter"=>"1"
			);
			$this->db->insert("payroll_file_batch_number_bpi",$val);
			return FALSE;
		}
	}
	
	/**
	 * New Holiday Pay Settings
	 * @param unknown $company_id
	 */
	public function holiday_pay_settings($company_id){
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("holiday_pay_settings");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}

	/**
	 * Check Leave Application for Successive Regular Holiday
	 * @param unknown $emp_id
	 * @param unknown $date
	 * @param unknown $company_id
	 */
	public function leave_application_for_successive_holiday($emp_id,$date,$company_id){
		$w = array(
			"ela.emp_id"=>$emp_id,
			"ela.company_id"=>$company_id,
			"ela.status"=>"Active",
			"ela.date_start" => $date,
			"ela.leave_application_status" => "approve"
		);
		$this->db->where($w);
		$this->db->where("(ela.flag_parent != 'yes' OR ela.flag_parent = 'no' OR ela.flag_parent IS NULL)");
		$q = $this->db->get("employee_leaves_application AS ela");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Leave History Last Pay
	 * @param unknown $leave_type_id
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 * @param unknown $date
	 */
	public function check_leave_history_last_pay($emp_id,$company_id){
		
		$w = array(
				"lt.paid_leave"=>"yes",
				"lt.what_happen_to_unused_leave_upon_termination"=>"convert to cash",
				"el.emp_id"=>$emp_id,
				"el.company_id"=>$company_id,
				"el.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
		$q = $this->db->get("employee_leaves AS el");
		$r = $q->result();
		if($r){
			$hours = 0;
			foreach($r as $row){
				$w = array(
						"elh.emp_id"=>$emp_id,
						"elh.company_id"=>$company_id,
						"elh.status"=>"Active",
						"elh.leave_type_id"=>$row->leave_type_id
				);
				$this->db->where($w);
				$this->db->order_by("date","DESC");
				$q2 = $this->db->get("employee_leave_history AS elh",1);
				$r2 = $q2->row();
				if($r2){
					$leave_accrued = $row->leave_credits_accrual;
					$previous_period_leave_balance = $r2->previous_period_leave_balance;
						
					if($previous_period_leave_balance > $leave_accrued){
						$previous_period_leave_balance = $leave_accrued;
						$hours += ($previous_period_leave_balance * 8);
					}else{
						$hours += ($previous_period_leave_balance * 8);
					}
				}
			}
			return $hours;
		}else{
			return 0;
		}
		
	}
	
	/**
	 * Check Leave Conversion
	 * @param unknown $company_id
	 * @param unknown $payroll_period
	 * @param unknown $emp_id
	 */
	public function check_leave_conversion($company_id,$payroll_period,$emp_id=NULL){
		$konsum_key = konsum_key();
		$w = array(
			"company_id"=>$company_id,
			"payroll_date"=>$payroll_period,
			"status"=>"Active"
		);
		$this->db->where($w);
		$this->db->where('AES_DECRYPT(leave_conversion_non_taxable,"'.$konsum_key.'") > 0');
		
		// CUSTOM EMPLOYEE
		if($emp_id != ""){ $this->db->where("emp_id",$emp_id); }
		$q = $this->edb->get("payroll_payslip");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	
}

/* End of file payroll_run_model.php */
/* Location: ./application/models/paycheck/payroll_run_model.php */