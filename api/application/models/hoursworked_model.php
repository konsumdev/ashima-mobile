<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Hoursworked model
 *
 * @category Model
 * @version 1.0
 * @author Christopher Cuizon <christophercuizons@gmail.com>
 * 
 * Revised by Kris Edward Galanida
 */
class Hoursworked_model extends CI_Model {

	/**
	 * Get payroll period
	 * @param int $company_id
	 */
	public function get_payroll_period($company_id)
	{
		$where = array(
			//'payroll_group.company_id' => $company_id,
			//'payroll_group.status'	   => 'Active'
			'payroll_period.company_id' => $company_id,
			'payroll_period.status'	 => 'Active'
		);
		$this->db->where($where);
		$this->db->join('payroll_group','payroll_group.payroll_group_id = payroll_period.payroll_group_id','left');
		$q = $this->db->get('payroll_period');
		$result = $q->row();
		
		return ($result) ? $result : false;
	}
	
	/**
	 * get all employees on the selected company
	 * @param int $company_id
	 */
	public function get_employees($company_id,$payroll_group_id,$per_page=NULL, $page=NULL)
	{
		$where = array(
			'epi.company_id' 	   => $company_id,
			'epi.payroll_group_id' => $payroll_group_id,
			'epi.status'	 	   => 'Active'
		);
		$this->edb->where($where);
		$this->edb->join('employee','employee.emp_id = epi.emp_id','inner');
		$this->edb->join('accounts','accounts.account_id = employee.account_id','inner');
		$this->edb->order_by('employee.last_name','asc');
		$q = $this->edb->get('employee_payroll_information AS epi',$per_page, $page);
		$result = $q->result();
		
		return ($result) ? $result : false;
	}
	
	/**
	 * Get Employees Counter
	 * @param unknown_type $company_id
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $per_page
	 * @param unknown_type $page
	 */
	public function get_employees_counter($company_id,$payroll_group_id,$per_page=NULL, $page=NULL)
	{
		$s = array(
			"COUNT(epi.emp_id) AS total"
		);
		$where = array(
			'epi.company_id' 	   => $company_id,
			'epi.payroll_group_id' => $payroll_group_id,
			'epi.status'	 	   => 'Active'
		);
		$this->edb->where($where);
		$this->db->select($s);
		$this->edb->join('employee','employee.emp_id = epi.emp_id','inner');
		$this->edb->join('accounts','accounts.account_id = employee.account_id','inner');
		$this->edb->order_by('employee.last_name','asc');
		$q = $this->edb->get('employee_payroll_information AS epi',$per_page, $page);
		$result = $q->row();
		
		return ($result) ? $result->total : false;
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

		return ($result) ? $result : false;
	}
	
	/**
	 * Get holiday
	 * @param int $company_id
	 * @param int $hour_type_id
	 * @param date $date
	 */
	public function get_holiday($company_id,$hour_type_id=NULL,$date)
	{
		if ($hour_type_id) {
			$this->db->where('holiday.hour_type_id',$hour_type_id);
		}
		
		$where = array(
			'holiday.company_id' => $company_id,
			'holiday.date'		 => $date,
			'holiday.status'	 => 'Active'
		);
		$this->db->where($where);
		$this->db->join('hours_type','hours_type.hour_type_id = holiday.hour_type_id','inner');
		$q = $this->db->get('holiday');
		$result = $q->row();
		
		return ($result) ? $result : false;
	}
	
	/**
	 * Check if non regular day
	 * @param int $company_id
	 * @param date $date
	 */
	public function check_non_regular_day($company_id,$date)
	{
		$where = array(
			'holiday.company_id' => $company_id,
			'holiday.date'		 => $date,
			'holiday.status'	 => 'Active'
		);
		$this->db->where($where);
		$this->db->join('hours_type','hours_type.hour_type_id = holiday.hour_type_id','left');
		$q = $this->db->get('holiday');
		$result = $q->row();
		
		if ($result) {
			if ($result->hour_type_name != 'Regular Day') {
				return $result;
			} else {
				return false;
			}
		} else {
			return false;
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
		return ($result) ? $result : false;
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
		
		return ($result) ? $result : false;
	}
	
	/**
	 * Get workday
	 * @param int $company_id
	 * @param int $payroll_group_id
	 */
	public function get_workday($company_id,$payroll_group_id)
	{
		$where = array(
			'company_id' 	   => $company_id,
			'payroll_group_id' => $payroll_group_id
		);
		$this->db->where($where);
		$q = $this->db->get('workday');
		$result = $q->row();
		
		return ($result) ? $result : false;
	}
	
	/**
	 * Get uniform working day
	 * @param int $company_id
	 * @param int $payroll_group_id
	 * @param string $weekday
	 */
	public function get_uniform_working_day($company_id,$payroll_group_id,$weekday)
	{
		$where = array(
			'company_id' 	   => $company_id,
			'payroll_group_id' => $payroll_group_id,
			'working_day'	   => $weekday
		);
		$this->db->where($where);
		$q = $this->db->get('uniform_working_day');
		$result = $q->row();

		return ($result) ? $result : false;
	}
	
	/**
	 * Get rest day
	 * @param int $company_id
	 * @param int $payroll_group_id
	 * @param string $weekday
	 */
	public function get_rest_day($company_id,$payroll_group_id,$weekday)
	{
		$where = array(
			'company_id' 	   => $company_id,
			'payroll_group_id' => $payroll_group_id,
			'rest_day' 		   => $weekday,
			'status'		   => 'Active'
		);
		$this->db->where($where);
		$q = $this->db->get('rest_day');
		$result = $q->row();
		
		return ($result) ? $result : false;
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
		
		return ($result) ? $result : false;
	}
	
	/**
	 * No need to encrypt
	 * @param int $company_id
	 * @param int $employee_id
	 * @param date $date
	 */
	public function get_leave_application($company_id,$employee_id,$date)
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
		
		return ($result) ?  $result:  false;
	}
	
	/**
	 * Get flexible hour
	 * @param int $company_id
	 * @param int $payroll_group_id
	 */
	public function get_flexible_hour($company_id,$payroll_group_id)
	{
		$where = array(
			'company_id' 	   => $company_id,
			'payroll_group_id' => $payroll_group_id
		);
		$this->db->where($where);
		$q = $this->db->get('flexible_hours');
		$result = $q->row();
		
		return ($result) ? $result : false;
	}
	
	/**
	 * Get workshift
	 * @param int $company_id
	 * @param int $payroll_group_id
	 */
	public function get_workshift($company_id,$payroll_group_id)
	{
		$where = array(
			'company_id' 	   => $company_id,
			'payroll_group_id' => $payroll_group_id
		);
		$this->db->where($where);
		$q = $this->db->get('workshift');
		$result = $q->row();
		
		return ($result) ? $result : false;
	}

	/**
	 * Get employee time keeping
	 * @param unknown_type $where
	 */
	public function get_employee_time_keeping($where)
	{
		$this->db->where($where);
		$q = $this->db->get('employee_time_keeping');
		$result = $q->row();
		
		return ($result) ? $result : false;
	}
	
	/**
	 * Add employee time keeping
	 * @param array $val
	 */
	public function add_employee_time_keeping($val)
	{
		$this->db->insert('employee_time_keeping',$val);
	}
	
	/**
	 * Get exclude employees
	 * @param int $company_id
	 * @param int $employee_id
	 */
	public function get_exclude_list($company_id,$employee_id)
	{
		$where = array(
			'company_id' => $company_id,
			'emp_id'	 => $employee_id
		);
		$this->db->where($where);
		$q = $this->db->get('exclude_list');
		$result = $q->row();
		
		return ($result) ? $result->exclude : false;
	}
	
	/**
	 * Get absences
	 * @param int $company_id
	 * @param int $employee_id
	 * @param date $date
	 */
	public function get_absences($company_id,$employee_id,$date)
	{
		$where = array(
			'company_id' => $company_id,
			'emp_id' 	 => $employee_id,	
			'date'		 => $date
		);
		$this->db->where($where);
		$q = $this->db->get('absences');
		$result = $q->row();
		
		return ($result) ? $result : false;
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
		
		return ($result) ? $result : false;
	}
	
	// add athan
	
	/**
	 * Check Leave Applications
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	public function check_leave_appliction($emp_id,$company_id){
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"leave_application_status"=>"approve"
		);
		$this->db->where($w);
		$q = $this->db->get("employee_leaves_application");
		return ($q->num_rows() > 0) ? $q->result() : FALSE ;
	}
	
	/**
	 * Check Paid Leave Records
	 * @param unknown_type $emp_id
	 * @param unknown_type $payroll_period
	 */
	public function check_leave_records($emp_id,$payroll_period){
		$w = array(
			"emp_id"=>$emp_id,
			"payroll_period"=>$payroll_period
		);
		$this->db->where($w);
		$q = $this->db->get("employee_paid_leave_records");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Halfday
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $current
	 */
	public function check_halfday($company_id,$emp_id,$date){
		$w = array(
			"emp_id"=>$emp_id,
			"comp_id"=>$company_id,
			"date"=>date("Y-m-d",strtotime($date)),
			"time_in_status"=>"approved",
			"date_halfday"=>NULL
		);
		$this->db->where($w);
		$q = $this->db->get("employee_time_in");
		$r = $q->result();
		#if($q->num_rows() > 1){
		if($r){
			$flag = 0;
			foreach($r as $row){
				if($row->flag_halfday == 1) $flag = 1;
				return ($flag == 1) ? $row : FALSE ;
			}
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check Cut off date
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $date
	 */
	public function check_cut_off_date($company_id,$emp_id,$date){
		$w = array(
			"emp_id"=>$emp_id,
			"comp_id"=>$company_id,
			"time_in_status"=>"approved",
			"flag_halfday"=>1,
			"date_halfday"=>$date
		);
		$this->db->where($w);
		$q = $this->db->get("employee_time_in");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
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
		if($q->num_rows() > 0){
			$payroll_group_id = $r->payroll_group_id;
			$comp_id = $r->company_id;
			
			// get hours worked
			$w2 = array(
				"payroll_group_id"=>$payroll_group_id,
				"working_day"=>$workday_val,
				"company_id"=>$comp_id,
				"status"=>"Active"
			);
			$this->db->where($w2);
			$q2 = $this->db->get("uniform_working_day");
			$r2 = $q2->row();
			if($q->num_rows() > 0){
				// for uniform working days table
				return $r2->working_hours;
			}else{
				$wf = array(
					"payroll_group_id"=>$payroll_group_id,
					"company_id"=>$comp_id
				);
				$this->db->where($wf);
				$qf = $this->db->get("flexible_hours");
				$rf = $qf->row();
				if($qf->num_rows() > 0){
					// for flexible hours table
					return $rf->total_hours_for_the_day;
				}else{
					$ww = array(
						"payroll_group_id"=>$payroll_group_id,
						"company_id"=>$comp_id
					);
					$this->db->where($ww);
					$qw = $this->db->get("workshift");
					$rq = $qw->row();
					if($qw->num_rows() > 0){
						// for workshift table
						return $rq->working_hours;
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
	 * Check Number Rows Timein
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $date
	 */
	public function check_num_rows_timein($company_id,$emp_id,$date){
		$w = array(
			"emp_id"=>$emp_id,
			"comp_id"=>$company_id,
			"date"=>date("Y-m-d",strtotime($date))
		);
		$this->db->where($w);
		$this->db->order_by("employee_time_in_id","DESC");
		$q = $this->db->get("employee_time_in");
		$r = $q->row();
		return ($q->num_rows == 1) ? $r : FALSE ;
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
		return ($result) ? $result : false;
	}
	

	/**
	 * GET FLEXIBLE HOLIDAY PREMIUM 
	 * tigkuha sa holiday premium na gi manual sa mga flexible na employee oh yeah
	 * @param int $company_id
	 * @param int $employee_id
	 * @param int $payroll_group_id
	 * @param dates $holiday_date
	 * @return object
	 */
	public function get_flexible_holiday_premium($company_id,$employee_id,$payroll_group_id,$holiday_date){
		if($company_id && is_numeric($employee_id) && is_numeric($payroll_group_id) && $holiday_date){
			$where = array(
				'company_id'		=> $company_id,
				'emp_id' 			=> $employee_id,
				'payroll_group_id' 	=> $payroll_group_id,
				'holiday_date' 		=> date("Y-m-d",strtotime($holiday_date)),
				'status'			=> 'Active'
			);
			$q = $this->db->get('flexible_holiday_premium');
			$res = $q->row();
			return  $res;
		}else{
			return false;
		}
	}
	

	/**
	 * Check Number of Breaks
	 * @param unknown_type $payroll_group
	 * @param unknown_type $comp_id
	 */
	public function num_of_breaks($payroll_group,$comp_id){
		// check number of breaks
		$number_of_breaks_per_day = 0;
		
		# UNIFORM WORKING DAYS
		$w_uwd = array(
			"payroll_group_id"=>$payroll_group,
			"company_id"=>$comp_id
		);
		$this->db->where($w_uwd);
		$q_uwd = $this->db->get("uniform_working_day_settings");
		$r_uwd = $q_uwd->row();
		if($q_uwd->num_rows() > 0){
			$number_of_breaks_per_day = $r_uwd->number_of_breaks_per_day;
		}else{
			# WORKSHIFT SETTINGS
			$w_ws = array(
				"payroll_group_id"=>$payroll_group,
				"company_id"=>$comp_id
			);
			$this->db->where($w_ws);
			$q_ws = $this->db->get("workshift_settings");
			$r_ws = $q_ws->row();
			if($q_ws->num_rows() > 0){
				$number_of_breaks_per_day = $r_ws->number_of_breaks_per_shift;
			}else{
				# FLEXIBLE HOURS
				$w_fh = array(
					"payroll_group_id"=>$payroll_group,
					"company_id"=>$comp_id
				);
				$this->db->where($w_fh);
				$q_fh = $this->db->get("flexible_hours");
				$r_fh = $q_fh->row();
				if($q_fh->num_rows() > 0){
					$number_of_breaks_per_day = $r_fh->duration_of_lunch_break_per_day;
				}
				
			}
		}
		return $number_of_breaks_per_day;
	}
	
	/**
	 * Check Number of Breaks
	 * @param unknown_type $work_schedule_id
	 * @param unknown_type $comp_id
	 */
	public function num_of_breaks_ws($work_schedule_id,$comp_id){
		// check number of breaks
		$number_of_breaks_per_day = 0;
		
		# UNIFORM WORKING DAYS
		$w_uwd = array(
			"work_schedule_id"=>$work_schedule_id,
			"company_id"=>$comp_id
		);
		$this->db->where($w_uwd);
		$q_uwd = $this->db->get("uniform_working_day_settings");
		$r_uwd = $q_uwd->row();
		if($q_uwd->num_rows() > 0){
			$number_of_breaks_per_day = $r_uwd->number_of_breaks_per_day;
		}else{
			# WORKSHIFT SETTINGS
			$w_ws = array(
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id
			);
			$this->db->where($w_ws);
			$q_ws = $this->db->get("workshift_settings");
			$r_ws = $q_ws->row();
			if($q_ws->num_rows() > 0){
				$number_of_breaks_per_day = $r_ws->number_of_breaks_per_shift;
			}else{
				# FLEXIBLE HOURS
				$w_fh = array(
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$comp_id
				);
				$this->db->where($w_fh);
				$q_fh = $this->db->get("flexible_hours");
				$r_fh = $q_fh->row();
				if($q_fh->num_rows() > 0){
					$number_of_breaks_per_day = $r_fh->duration_of_lunch_break_per_day;
				}
				
			}
		}
		return $number_of_breaks_per_day;
	}
	
	/**
	 * Check Work Schedule
	 * @param unknown_type $emp_id
	 * @param unknown_type $date
	 * @param unknown_type $company_id
	 */
	public function check_work_schedule($emp_id,$date,$company_id){
		// employee group id
		$s = array(
			"ess.work_schedule_id"
		);
		$w_date = array(
			"ess.valid_from <="		=>	$date,
			"ess.until >="			=>	$date
		);
		$this->db->where($w_date);
		
		$w_emp = array(
			"ess.emp_id"=>$emp_id,
			"e.status"=>"Active",
			"ess.status"=>"Active"
		);
		$this->edb->select($s);
		$this->edb->where($w_emp);
		$this->edb->join("employee AS e","e.emp_id = ess.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$q_emp = $this->edb->get("employee_shifts_schedule AS ess");
		$r_emp = $q_emp->row();
		
		$work_schedule_id = "";
		$payroll_group_id = "";
		
		if($r_emp){
			/* WORK SCHEDULE */
			$work_schedule_id = $r_emp->work_schedule_id;
			$payroll_group_id = "";
		}else{
			/* PAYROLL GROUP ID */
			$w = array(
				"emp_id"=>$emp_id,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("employee_payroll_information");
			$r = $q->row();
			if($r){
				$work_schedule_id = "";
				$payroll_group_id = $r->payroll_group_id;
			}
		}
		
		if($work_schedule_id != "" || $payroll_group_id != ""){
			if($work_schedule_id != ""){
				$my_where = array(
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$company_id,
				);
			}else{
				$my_where = array(
					"payroll_group_id"=>$payroll_group_id,
					"company_id"=>$company_id,
				);
			}
			
			// uniform
			$this->db->where($my_where);
			$this->db->where("working_day",date("l",strtotime($date)));
			$this->db->where("status","Active");
			$u_q = $this->db->get("uniform_working_day");
			$u_r = $u_q->row();
			if($u_r){
				$start_time = $u_r->work_start_time;
				$end_time = $u_r->work_end_time;
			}else{
				// workshift
				$this->db->where($my_where);
				$w_q = $this->db->get("workshift");
				$w_r = $w_q->row();
				if($w_r){
					$start_time = $w_r->start_time;
					$end_time = $w_r->end_time;
				}else{
					// flexible hour
					$this->db->where($my_where);
					$f_q = $this->db->get("flexible_hours");
					$f_r = $w_q->row();
					if($f_r){
						if($f_r->not_required_login == NULL || $f_r->not_required_login == 0 || $f_r->latest_time_in_allowed == NULL 
							&& $f_r->latest_time_in_allowed == 0
						){
							return FALSE;
						}else{
							$start_time = $f_r->latest_time_in_allowed;
							$end_time = "";	
						}
					}else{
						return FALSE;
					}
				}
			}
			
			$data["info"] = array(
				"start_time"=>$start_time,
				"end_time"=>$end_time
			);
			
			return $data;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Check Employee Time In
	 * @param unknown_type $emp_id
	 * @param unknown_type $new_end_time
	 * @param unknown_type $new_start_time
	 */
	public function check_emp_time_in($emp_id,$new_end_time,$new_start_time){
		$w = array(
			"emp_id"=>$emp_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$this->db->where("time_in BETWEEN '{$new_end_time}' AND '$new_start_time'");
		$q = $this->db->get("employee_time_in");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	# BOGART FLEX CODE HERE
	
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
				'h.hour_type_id'=>$hour_type_id,
				'h.status'=>'Active',
				'h.deleted'=>'0',
				'fhp.status'=>'Active'
			);
			$this->db->where($where_fhp);
			$this->db->join('holiday AS h','h.holiday_id=fhp.holiday_id','INNER');
			$q_flex_holidaypremium = $this->db->get('flexible_holiday_premium AS fhp');
			$row_fhp = $q_flex_holidaypremium->row();
			return $row_fhp;
		}else{
			return false;
		}
	}
	# END BOGART FLEX CODE

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
		return ($r) ? TRUE : FALSE ;
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
		if($r){
			return ($r->latest_time_in_allowed == NULL || $r->latest_time_in_allowed == "") ? TRUE : FALSE ;
		}else{
			return FALSE;
		}
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
			"epi.emp_id"=>$emp_id
		);
		$this->db->where($w);
		$this->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$q = $this->db->get("employee_payroll_information AS epi");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
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
		$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
		$q = $this->db->get("employee_time_in");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
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
		
		return ($result) ? $result : false;
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
				"work_day"=>$weekday
			);
		}
		$this->db->where($w);
		$q = $this->db->get("break_time");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
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
			'date'	  => $date
		);
		$this->db->where($where);
		$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
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
						$date = date("Y-m-d",strtotime($date." +1 day"));
						$ns_where = array(
							"emp_id"=>$emp_id,
							"DATE(date_start)" => $date,
							"DATE(date_end)" => $date,
							"status" => "Active",
							"leave_application_status"=>"approve"
						);
						$this->db->where($ns_where);
						$ns_q = $this->db->get("employee_leaves_application");
						$ns_r = $ns_q->row();
						if($ns_r){
							if(date("A",strtotime($r->time_in)) == "PM" && date("A",strtotime($ns_r->date_end)) == "AM"){
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
							}else{
								return FALSE;
							}
						}else{
							// NIGHT SHIFT - HALFDAY AFTER LUNCH IN
							$date = date("Y-m-d",strtotime($date." -1 day"));
							$ns_where = array(
								"emp_id"=>$emp_id,
								"DATE(date_start)" => $date,
								"DATE(date_end)" => $date,
								"status" => "Active",
								"leave_application_status"=>"approve"
							);
							$this->db->where($ns_where);
							$ns_q = $this->db->get("employee_leaves_application");
							$ns_r = $ns_q->row();
							if($ns_r){
								if(date("A",strtotime($ns_r->date_start)) == "PM" && date("A",strtotime($r->time_out)) == "AM"){
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
								}else{
									return FALSE;
								}
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
	 * Search Employee Name
	 * @param unknown_type $emp_name
	 * @param unknown_type $company_id
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $per_page
	 */
	public function search_employee_name($emp_name,$company_id,$payroll_group_id,$per_page=NULL)
	{
		$konsum_key = konsum_key();
		$where = array(
			'epi.company_id' 	   => $company_id,
			'epi.payroll_group_id' => $payroll_group_id,
			'epi.status'	 	   => 'Active'
		);
		$this->edb->where($where);
		#$this->db->where("CONCAT(e.first_name,' ',e.last_name) LIKE '%".$emp_name."%'", NULL, FALSE);
		$this->db->where("CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$emp_name."%'", NULL, FALSE); // encrypt
		$this->edb->join('employee AS e','e.emp_id = epi.emp_id','inner');
		$this->edb->join('accounts','accounts.account_id = e.account_id','inner');
		$this->edb->order_by('e.last_name','asc');
		$q = $this->edb->get('employee_payroll_information AS epi',$per_page);
		$result = $q->result();
		
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
		return ($r) ? $r : FALSE ;
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
		$q = $this->db->get('holiday');
		$result = $q->row();
		
		return ($result) ? $result : false;
	}
	
	/**
	 * Check Rest Day For Night Differential Premium
	 * @param unknown_type $company_id
	 */
	public function check_rest_day_for_nd_premium($company_id){
		$s = array(
			"*","ndp.rate AS nd_rate"
		);
		$w = array(
			"ndp.company_id"=>$company_id,
			"ht.default"=>"2"
		);	
		$this->db->where($w);
		$this->db->select($s);
		$this->db->join("hours_type AS ht","ht.hour_type_id = ndp.hours_type_id","LEFT");
		$q = $this->db->get("nightshift_differential_for_premium AS ndp");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Holiday Rate for Night Differential
	 * @param unknown_type $company_id
	 * @param unknown_type $hour_type_id
	 */
	public function get_holiday_rate_for_night_diff_settings($company_id,$hour_type_id){
		$w = array(
			"company_id"=>$company_id,
			"hours_type_id"=>$hour_type_id
		);
		$this->db->where($w);
		$q = $this->db->get("nightshift_differential_for_premium");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Rest Day For Night Differential Premium
	 * @param unknown_type $company_id
	 */
	public function check_if_restday($hour_type_id,$company_id){
		$s = array(
			"*","ndp.pay_rate AS nd_rate"
		);
		$w = array(
			"ht.hour_type_id"=>$hour_type_id,
			"ndp.company_id"=>$company_id,
			"ht.default"=>"2"
		);	
		$this->db->where($w);
		$this->db->select($s);
		$this->db->join("hours_type AS ht","ht.hour_type_id = ndp.hours_type_id","LEFT");
		$q = $this->db->get("nightshift_differential_for_premium AS ndp");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Working Schedule For Flexible
	 * @param unknown_type $id
	 * @param unknown_type $company_id
	 */
	public function check_working_schedule_flex($id,$company_id,$str){
		if($str == "work_schedule_id"){
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
		if($r){
			return ($r->not_required_login == 1) ? $r : FALSE ;
		}else{
			return FALSE;
		}
	}

}
/* End of file hourswoked_model */
/* Location: ./application/models/payroll_run/hoursworked_model.php */