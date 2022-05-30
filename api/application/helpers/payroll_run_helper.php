<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	
	/**
	 * Carry Over for fields
	 * @param unknown_type $emp_id
	 * @param unknown_type $field
	 */
	function carry_over_field($emp_id, $field){
		$CI =& get_instance();
		
		// get company id
		$w_comp = array(
			"emp_id"=>$emp_id
		);
		$CI->edb->where($w_comp);
		$q_comp = $CI->edb->get("employee");
		$r_comp = $q_comp->row();
		$comp_id = $r_comp->company_id;
		
		// get payroll period
		$w = array("company_id"=>$comp_id);
		$CI->db->where($w);
		$q = $CI->db->get("payroll_period");
		$r = $q->row();
		$period_from = $r->period_from;
		$period_to = $r->period_to;
		
		$value = 0;
		
		$sql = $CI->db->query("
			SELECT *
			FROM `payroll_carry_over`
			WHERE emp_id = '{$emp_id}'
			AND period_from = '{$period_from}'
			AND period_to = '{$period_to}'
		");
		
		$row = $sql->row();
		if($sql->num_rows() > 0){
			return $row->$field;
		}
		
		return $value;
	}
	
	/**
	 * Previos Payroll Period
	 * @param unknown_type $pfrom
	 * @param unknown_type $pto
	 * @param unknown_type $comp_id
	 * @param unknown_type $payroll_group_id
	 */
	function previos_pp($pfrom,$pto,$comp_id,$emp_id){
		$CI =& get_instance();
		
		$payroll_group_id = $CI->db->query("
			SELECT *FROM employee_payroll_information
			WHERE emp_id = '{$emp_id}'
		");
		
		$row_payroll_group_id = $payroll_group_id->row();
		if($payroll_group_id->num_rows() > 0){
			$sql = $CI->db->query("
				SELECT *
				FROM `payroll_calendar`
				WHERE cut_off_from = '{$pfrom}'
				AND cut_off_to = '{$pto}'
				AND company_id = '{$comp_id}'
				AND payroll_group_id = '{$row_payroll_group_id->payroll_group_id}'
			");
			
			$row = $sql->row();
			
			if($sql->num_rows() > 0){
				
				$sql_counter = $CI->db->query("
					SELECT COUNT(payroll_calendar_id) as count
					FROM `payroll_calendar`
					WHERE `payroll_calendar_id` <= '{$row->payroll_calendar_id}' 
				");
				
				$row_counter = $sql_counter->row();
				
				$row_counter = $row_counter->count - 1;
				
				$sql_sec_last_row = $CI->db->query("
					SELECT *
					FROM `payroll_calendar`
					WHERE company_id = '{$comp_id}'
					AND payroll_group_id = '{$row_payroll_group_id->payroll_group_id}'
					LIMIT 0,$row_counter
				");
				
				$row_sec_last_row = $sql_sec_last_row->row();
				if($sql_sec_last_row->num_rows() > 0){
					return $row_sec_last_row;
				}
				
			}else{
				return false;
			}
		}else{
			return false;
		}
		
	}
	
	/**
	 * Carry Over for Overtime 
	 * 
	 * @param unknown_type $from
	 * @param unknown_type $to
	 * @param unknown_type $emp_id
	 */
	function carry_over_overtime($from,$to,$emp_id){
		$CI =& get_instance();
		
		// PAYROLL INFORMATION
		$where = array(
			"emp_id" => $emp_id
		);
		$CI->db->where($where);
		$get_current_payroll_period = $CI->db->get("employee_payroll_information");
		$row_current_payroll_period = $get_current_payroll_period->row();
		
		// GET CURRENT PAYROLL PERIOD
		$where_current_pp = array(
			"payroll_group_id" => $row_current_payroll_period->payroll_group_id,
			"company_id" => $row_current_payroll_period->company_id,
			"status" => "Active"
		);
		$CI->db->where($where_current_pp);
		$cc_pp = $CI->db->get("payroll_period");
		$row_cc_pp = $cc_pp->row();
		
		$current_pp_from = $row_cc_pp->period_from;
		$current_pp_to = $row_cc_pp->period_to;
		
		/* $sql = $CI->db->query("
			SELECT *
			FROM `employee_overtime_application`
			WHERE emp_id = '{$emp_id}'
			AND overtime_status = 'approved'
		"); */
		
		$sql = $CI->db->query("
			SELECT *
			FROM employee_overtime_application
			WHERE overtime_date_applied >= '{$from}'
			AND overtime_date_applied <= '{$to}'
			AND approval_date >= '{$current_pp_from}'
			AND approval_date <= '{$current_pp_to}'
			AND emp_id = '{$emp_id}'
			AND overtime_status = 'approved'
		");
		
		$result = $sql->result();
		if($sql->num_rows() > 0){
			$total = 0;
			foreach($result as $row){
				
				$approval_date = $row->approval_date;
				$no_of_hours = $row->no_of_hours;
				
				/* if($approval_date != NULL || $approval_date != ""){

					if (($from <= $approval_date && $to >= $approval_date)) {
						$total = $total + $no_of_hours;
					} else {
						$total = 0;
					}
				} */
				$total = $total + $no_of_hours;
			}
		}else{
			$total = 0;
		}
		
		return $total;
		
	}
	
	/**
	 * Carry Over for Leave Type 
	 * 
	 * @param unknown_type $from
	 * @param unknown_type $to
	 * @param unknown_type $emp_id
	 */
	function carry_over_leave_type($from,$to,$emp_id,$comp_id){
		$CI =& get_instance();
		
		$total_leave_hours = 0;
		
		// GET CURRENT PAYROLL PERIOD
		$payroll_group = $CI->db->query("
			SELECT *
			FROM `employee_payroll_information` epi
			LEFT JOIN `payroll_group` pg ON epi.payroll_group_id = pg.payroll_group_id
			LEFT JOIN `payroll_period` pp ON pg.payroll_group_id = pp.payroll_group_id
			WHERE epi.emp_id = '{$emp_id}'
			AND pp.company_id = '{$comp_id}'
		");
		
		$row_pgroup = $payroll_group->row();
		$pp_from = $row_pgroup->period_from;
		$pp_to = $row_pgroup->period_to;
		
		// GET PREVIOUS PAYROLL PERIOD
		$previous_payroll_period = previos_pp($pp_from, $pp_to, $comp_id, $emp_id);
		$ppp_from = $previous_payroll_period->cut_off_from;
		$ppp_to = $previous_payroll_period->cut_off_to;
		
		// GET REMAINING LEAVE CREDITS
		$remaining_leave_credits = $CI->db->query("
			SELECT *
			FROM `employee_leaves`
			WHERE emp_id = '{$emp_id}'
		");
		$row_remaining_leave_credits = $remaining_leave_credits->result();
		$cnt_lc = 0;

		if($remaining_leave_credits->num_rows() > 0){
			foreach($row_remaining_leave_credits as $row_remaining_lc){
				$remaining_leave_credits = $row_remaining_lc->remaining_leave_credits;
				$previous_leave_credits = $row_remaining_lc->previous_leave_credits;
				$leave_type_id = $row_remaining_lc->leave_type_id; 
				
				// GET LAEVE APPLICATION DATE FILED BETWEEN PREVIOUS PAYROLL PERIOD
				$leave_application = $CI->db->query("
					SELECT *
					FROM `employee_leaves_application`
					WHERE emp_id = '{$emp_id}'
					AND leave_application_status = 'approve'
					AND leave_type_id = '{$leave_type_id}'
				");
				$results = $leave_application->result();
				
				// CHECK IF REMAINING BALANCE IS NOT NEGATIVE VALUE
				$cnt = 0;
				if($remaining_leave_credits > 0){
					if($leave_application->num_rows() > 0){
						foreach($results as $row_l_a){
							
							// DECLARE VARIABLES
							$date_filed = $row_l_a->date_filed;
							$approval_date = $row_l_a->approval_date;
							$total_leave_requested = $row_l_a->total_leave_requested; 	 
							
							// GET DATE FILED BETWEEN CURRENT PAYROLL PERIOD
							if(strtotime($ppp_from) <= strtotime($date_filed) && strtotime($date_filed) <= strtotime($ppp_to)){
								
								// CHECK APPROVAL DATE, IF APPROVAL DATE IS BETWEEN CURRENT PAYROLL PERIOD
								if(strtotime($pp_from) <= strtotime($approval_date) && strtotime($approval_date) <= strtotime($pp_to)){
									$cnt += $total_leave_requested;
								}
							}
						}
					}
				}else{
					// IF TOTAL REMAINING LEAVE BALANCE IS LESS THAN 0 THEN GET THE TOTAL PREVIOUS LEAVE CREDITS
					$cnt += $previous_leave_credits;
				}
				
				$cnt_lc += $cnt;
			}
		}else{
			$remaining_leave_credits = 0;
			$previous_leave_credits = 0;
		}
		
		return $cnt_lc * 8;
		
	}
	
	/**
	 * Convert Amount
	 * @param unknown $amount
	 */
	function convert_amount($amount){
		
		/* $var = '122.34343The';
		$float_value_of_var = floatval($var);
		echo $float_value_of_var; // 122.34343 */
		
		$amount = floatval($amount);
		$new_amount = number_format(0,2);
		if($amount >= 0){
			$new_amount = number_format($amount,2);
		}else{
			$new_amount = abs($amount);
			$new_amount = number_format($new_amount,2);
			$new_amount = "({$new_amount})";
		}
		return $new_amount;
	}
	
	/**
	 * Payroll Token Level
	 * @param unknown $lvl
	 */
	function token_level($lvl){
		$approver_emp_id = substr($lvl, 0, -1);
		$approver_emp_id = substr($approver_emp_id, 1);
		return $approver_emp_id;
	}
	
	/**
	 * Calculate Holiday Rest Day
	 * @param unknown $hour
	 * @param unknown $emp_hourly_rate
	 * @param unknown $rest_day_rate
	 */
	function calculate_holiday_rest_day($hour,$emp_hourly_rate,$rest_day_rate,$flag_rate = NULL){
		// $holiday = $hour * $emp_hourly_rate * ($rest_day_rate - 1);
		if($flag_rate > 0){
			// $holiday = $emp_hourly_rate * ($rest_day_rate - 1);
			$holiday = $emp_hourly_rate * ($rest_day_rate);
		}else{
			// $holiday = $emp_hourly_rate * ($rest_day_rate - 1);
			$holiday = $emp_hourly_rate * ($rest_day_rate);
			$holiday = round($holiday,2) * $hour;
		}
		return $holiday;
	}
	
	/**
	 * Calculate Holiday Regular
	 * @param unknown $hour
	 * @param unknown $emp_hourly_rate
	 * @param unknown $regular_rate
	 */
	function calculate_holiday($hour,$emp_hourly_rate,$regular_rate,$flag_rate = NULL){
		// $holiday = $hour * $emp_hourly_rate * ($regular_rate - 1);
		if($flag_rate > 0){
			$holiday = $emp_hourly_rate * ($regular_rate - 1);
		}else{
			$holiday = $emp_hourly_rate * ($regular_rate - 1);
			$holiday = round($holiday,2) * $hour;
		}
		return $holiday;
	}
	
	/**
	 * Calculate Overtime Holiday
	 * @param unknown $hours
	 * @param unknown $emp_hourly_rate
	 * @param unknown $holiday_pay_rate
	 * @param unknown $overtime_pay_rate
	 */
	function calculate_overtime_holiday($hours,$emp_hourly_rate,$holiday_pay_rate,$overtime_pay_rate,$flag_rate = NULL){
		// $overtime_rate += $overtime_dd->no_of_hours * ( $hourly_rate1 + ( $hourly_rate1 * ($holiday->ot_rate - 1)));
		/* $hourly_rate1 = $emp_hourly_rate * $holiday_pay_rate;
		if($flag_rate > 0){
			$overtime = ( $hourly_rate1 + ( $hourly_rate1 * ($overtime_pay_rate - 1)));
		}else{
			$overtime = ( $hourly_rate1 + ( $hourly_rate1 * ($overtime_pay_rate - 1)));
			$overtime = round($overtime,2) * $hours;
		} */
		
		// BAG.O
		if($flag_rate > 0){
			$overtime = $emp_hourly_rate * $holiday_pay_rate * $overtime_pay_rate;
		}else{
			$overtime = $emp_hourly_rate * $holiday_pay_rate * $overtime_pay_rate;
			$overtime = round($overtime,2) * $hours;
		}
		
		return $overtime;
	}
	
	/**
	 * Calculate Overtime for Regular Day or Rest Day
	 * @param unknown $hours
	 * @param unknown $emp_hourly_rate
	 * @param unknown $regular_pay_rate
	 * @param unknown $overtime_pay_rate
	 */
	function calculate_overtime_regular_or_rest_day($hours,$emp_hourly_rate,$regular_pay_rate,$overtime_pay_rate,$flag_rate = NULL){
		/* $regular_pay_rate = $regular_pay_rate * 100;
		if($flag_rate > 0){
			$overtime = $emp_hourly_rate * (($overtime_pay_rate - 1) + ($regular_pay_rate/100));
		}else{
			$overtime = $emp_hourly_rate * (($overtime_pay_rate - 1) + ($regular_pay_rate/100));
			$overtime = round($overtime,2) * $hours;
		}
		return $overtime; */

		if($flag_rate > 0){
			$overtime = $emp_hourly_rate * $regular_pay_rate * $overtime_pay_rate;
		}else{
			$overtime = $emp_hourly_rate * $regular_pay_rate * $overtime_pay_rate;
			$overtime = round($overtime,2) * $hours;
		}
		return $overtime;
		
	}
	
	/**
	 * Calculate Rest Day
	 * @param unknown $hours
	 * @param unknown $emp_hourly_rate
	 * @param unknown $rest_day_holiday_percentage
	 */
	function calculate_rest_day($hours,$emp_hourly_rate,$rest_day_holiday_percentage,$flag_rate = NULL){
		// $rest_day_holiday_amount = $rest_day_holiday_hours * ($emp_hourly_rate / 100) * $rest_day_holiday_percentage;
		// $rest_day = $hours * $emp_hourly_rate * ($rest_day_holiday_percentage - 1);
		if($flag_rate > 0){
			$rest_day = $emp_hourly_rate * $rest_day_holiday_percentage;
		}else{
			$rest_day = $emp_hourly_rate * $rest_day_holiday_percentage;
			$rest_day = round($rest_day,2) * $hours;
		}
		return $rest_day;
	}
	
	/**
	 * Calculate Night Differential Holiday
	 * @param unknown $total_hours
	 * @param unknown $holiday_pay_rate
	 * @param unknown $emp_hourly_rate
	 */
	function calculate_night_differential_holiday($total_hours,$nd_rate,$emp_hourly_rate,$holiday_pay_rate,$flag_rate=NULL){
		/* $holiday_hourly_rate = $emp_hourly_rate * $holiday_pay_rate;
		$nd_amount = ($holiday_pay_rate-1) * $holiday_hourly_rate * $total_hours;
		return $nd_amount; */
		
		if($flag_rate > 0){
			$holiday_hourly_rate = $emp_hourly_rate * $holiday_pay_rate;
			$nd_amount = (($nd_rate-1) * $holiday_hourly_rate);
		}else{
			$holiday_hourly_rate = $emp_hourly_rate * $holiday_pay_rate;
			$nd_amount = (($nd_rate-1) * $holiday_hourly_rate);
			$nd_amount = round($nd_amount,2) * $total_hours;
		}
		return $nd_amount;
	}
	
	/**
	 * Calculate Night Differential
	 * @param unknown $total_hours
	 * @param unknown $emp_hourly_rate
	 * @param unknown $nd_pay_rate
	 */
	function calculate_night_differential($total_hours,$emp_hourly_rate, $nd_pay_rate){
		// nd = total_hours x (emp_hourly_rate * (nsd_rate -1));
		$nd_amount = $total_hours * $emp_hourly_rate * ($nd_pay_rate -1);
		return $nd_amount;
	}
	
	/**
	 * Calculate Night Differential Rest Day
	 * @param unknown $total_hours
	 * @param unknown $emp_hourly_rate
	 * @param unknown $nd_pay_rate
	 * @param unknown $rest_day_pay_rate
	 */
	function calculate_night_differential_rest_day($total_hours,$emp_hourly_rate, $nd_pay_rate,$rest_day_pay_rate,$flag_rate = NULL){
		// rd_hourly_rate = $emp_hourly_rate x $rest_pay_rate;
		// nd = total_hours x (rd_hourly_rate * (nsd_rate -1));
		$rd_hourly_rate = $emp_hourly_rate * $rest_day_pay_rate;
		// $rd_hourly_rate = $rd_hourly_rate + ($rd_hourly_rate * ($nd_pay_rate -1));
		$rd_hourly_rate = ($rd_hourly_rate * ($nd_pay_rate -1));
		if($flag_rate > 0){
			$nd_amount = $rd_hourly_rate;
		}else{
			$nd_amount = round($rd_hourly_rate,2) * $total_hours;
		}
		return $nd_amount;
	}
	
	/**
	 * Calculate Overtime on Night Differential
	 * @param unknown $total_hours
	 * @param unknown $emp_hourly_rate
	 * @param unknown $overtime_rate
	 */
	function calculate_night_differential_overtime($total_hours,$emp_hourly_rate,$overtime_rate,$nd_reg_rd_rate,$flag_rate = NULL){
		// overtime rate = hourly rate +(hourly rate * OT rate)
		// = 78.25 +(78.25* 1.25-1)
		// = 97.81/hour
		// NSD on Overtime = NSD Hourly Rate * total nd hours during OT
		$houry_rate = $emp_hourly_rate +($emp_hourly_rate * ($overtime_rate - 1));
		// $houry_rate2 = $houry_rate + ($houry_rate * ($nd_reg_rd_rate - 1));
		$houry_rate2 = ($houry_rate * ($nd_reg_rd_rate - 1));
		if($flag_rate > 0){
			$night_diff_overtime = $houry_rate2;
		}else{
			$night_diff_overtime = round($houry_rate2,2) * $total_hours;
		}
		return $night_diff_overtime;
	}
	
	/**
	 * Calculate Overtime on Night Differential
	 * @param unknown $total_hours
	 * @param unknown $emp_hourly_rate
	 * @param unknown $overtime_rate
	 */
	function calculate_night_differential_overtime_rest_day($total_hours,$emp_hourly_rate,$overtime_rate,$nd_rest_day_rate,$rest_day_working_pay_rate,$flag_rate = NULL){
		if($flag_rate > 0){
			$night_diff_overtime = $emp_hourly_rate * $rest_day_working_pay_rate * $overtime_rate * ($nd_rest_day_rate -1);
		}else{
			$night_diff_overtime = $emp_hourly_rate * $rest_day_working_pay_rate * $overtime_rate * ($nd_rest_day_rate -1);
			$night_diff_overtime = round($night_diff_overtime,2) * $total_hours;
		}
		return $night_diff_overtime;
	}
	
	/**
	 * Calculate Overtime on Night Differential Holiday
	 * @param unknown $total_hours
	 * @param unknown $emp_hourly_rate
	 * @param unknown $holiday_pay_rate
	 * @param unknown $ot_rate
	 */
	function calculate_night_differential_overtime_holiday($total_hours,$emp_hourly_rate,$holiday_nd_pay_rate,$overtime_rate,$holiday_pay_rate,$flag_rate = NULL){
		// vertime on NSD = $emp_hourly_rate x $holiday_pay_rate;
		// Overtime on NSD += nd hours x ( $holiday_rate + ( $holiday_rate x ( ($holiday->ot_rate-1)));
		$new_houry_rate = $emp_hourly_rate * ($holiday_pay_rate);
		$new_houry_rate2 = ($new_houry_rate * ($overtime_rate));
		if($flag_rate > 0){
			// $night_differential = ( $new_houry_rate2 + ($new_houry_rate2 * ($holiday_nd_pay_rate-1)) );
			$night_differential = ( ($new_houry_rate2 * ($holiday_nd_pay_rate-1)) );
		}else{
			// $night_differential = ( $new_houry_rate2 + ($new_houry_rate2 * ($holiday_nd_pay_rate-1)) );
			$night_differential = ( ($new_houry_rate2 * ($holiday_nd_pay_rate-1)) );
			$night_differential = round($night_differential,2);
			$night_differential = $total_hours * $night_differential;
		}
		return $night_differential;
	}
	
	/**
	 * System Payroll Approval
	 * @param unknown $company_id
	 */
	function system_payroll_approval($company_id,$status="Closed"){
		
		$CI =& get_instance();
		
		// LOAD DRAFT PAY RUN LISTING
		$w = array(
			"company_id"=>$company_id,
			"status"=>"Active",
			"view_status"=>"Waiting for approval"
		);
		$CI->db->where($w);
		$q = $CI->db->get("draft_pay_runs");
		$r = $q->result();
		if($r){
			
			$flag_error = FALSE;
			
			// UPDATE PAYROLL APPROVAL LINK
			foreach($r as $row){
				
				$token = $row->token;
				$value = array(
					"approve_by_head"=>"Yes",
					"payroll_status"=>"approved",
					"notes"=>"approve via system workflow" // bag.o ni once i.disable ang workflow settings sa hr or admin automatic approved tanan payroll
				);
				$where = array(
					"comp_id"=>$company_id,
					"token"=>$token,
					"status"=>"Active",
				);
				$CI->db->where($where);
				$q = $CI->db->update("approval_payroll",$value);
				
				if($q){
					// do nothing
				}else{
					$flag_error = TRUE;
				}
				
			}
			
			// UPDATE DRAFT PAY RUN
			$draft_val = array(
				// "view_status"=>"Closed"
				"view_status"=>$status
			);
				
			$draft_where = array(
				"status"=>"Active",
				"view_status"=>"Waiting for approval",
				"token"=>$token
			);
				
			$CI->db->where($draft_where);
			$update_draft = $CI->db->update("draft_pay_runs",$draft_val);
			
			if($update_draft){
				// do nothing
			}else{
				$flag_error = TRUE;
			}
			
			return (!$flag_error) ? TRUE : FALSE ;
			
		}else{
			return TRUE;
		}
		
		
	}
	
	function time_overlap($start_time, $end_time, $times){
		/* $ustart = strtotime($start_time);
		$uend   = strtotime($end_time);
		 
		$start = strtotime($times["start"]);
		$end   = strtotime($times["end"]);
		if($ustart <= $end && $uend >= $start){
			return true;
		}
		 
		return false; */
		
		if(strtotime($times["start"]) < strtotime('-1 year')){
			// IF NAAY YEAR FORMAT
			$ustart = strtotime($start_time);
			$uend   = strtotime($end_time);
				
			$start = strtotime($times["start"]);
			$end   = strtotime($times["end"]);
			if($ustart <= $end && $uend >= $start){
				return true;
			}
		}else{
			// IF WAY YEAR FORMAT
			$date_custom_val = "2016-12-25";
			$start_time = date("{$date_custom_val} {$start_time}");
			$end_time = date("{$date_custom_val} {$end_time}");
			if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM"){
				$end_time = date("Y-m-d H:i:s",strtotime($end_time."+1 day"));
			}
			$ustart = strtotime($start_time);
			$uend   = strtotime($end_time);
			
			// TIME START AND END ARRAY
			$start_array = date("{$date_custom_val} {$times["start"]}");
			$end_array = date("{$date_custom_val} {$times["end"]}");
			if(date("A",strtotime($start_array)) == "PM" && date("A",strtotime($end_array)) == "AM"){
				$end_array = date("Y-m-d H:i:s",strtotime($end_array."+1 day"));
			}
			$start = strtotime($start_array);
			$end   = strtotime($end_array);
			
			// print "{$start_time} <br> {$end_time}<br><br>";
			// print "{$start_array} <br> {$end_array}";
			
			if($ustart <= $end && $uend >= $start){
				return true;
			}
		}
			
		return false;
		
	}
	
	/**
	 * Check Leave Applications
	 * @param unknown_type $emp_id
	 * @param unknown_type $company_id
	 */
	function check_leave_appliction($date,$emp_id,$company_id){ // MU GIKAN NI KANG ALDRIN i check if ang employee already on leave then mag flag sa employee time in to flag_on_leave = "yes"
		$CI =& get_instance();
		$w = array(
			"ela.status"=>"Active",
			"ela.emp_id"=>$emp_id,
			"ela.company_id"=>$company_id,
			"ela.leave_application_status"=>"approve",
			"DATE(ela.date_start)" => date("Y-m-d",strtotime($date))
		);
		$CI->db->where($w);
		$CI->db->where("(ela.flag_parent != 'yes' OR ela.flag_parent = 'no' OR ela.flag_parent IS NULL)");
		$CI->db->join("leave_type AS lt","lt.leave_type_id = ela.leave_type_id","LEFT");
		$q = $CI->db->get("employee_leaves_application AS ela");
		$r = $q->row();
		// return ($r) ? TRUE : FALSE ; // IF TRUE ANG RETURN flag_on_leave SA EMPLOYEE TIME IN = "yes"
		
		if($r){
			
			$credits = $r->credited * 8;
			$work_schedule_id = prm_work_schedule_id($company_id,$emp_id,$date);
			$workday = prm_get_workday($company_id,$work_schedule_id->work_schedule_id);
			if($workday != FALSE){
				
				$weekday = date('l',strtotime($date));
				switch ($workday->workday_type) {
				
					case 'Uniform Working Days':
				
						$w = prm_get_uniform_working_day($company_id,$work_schedule_id->work_schedule_id,$weekday);
						$hp_total_hours = ($w) ? $w->total_work_hours : 0 ;
						return ($credits >= $hp_total_hours) ? TRUE : FALSE ; // CREDITS >= WORKING_HOURS; 8 >= 8 = TRUE | 7 >= 8 = FALSE
						
						break;
				
					case 'Flexible Hours':
				
						$w = prm_get_flexible_hour($company_id,$work_schedule_id->work_schedule_id);
						$hp_total_hours = ($w) ? $w->total_hours_for_the_day : 0 ;
						return ($credits >= $hp_total_hours) ? TRUE : FALSE ; // CREDITS >= WORKING_HOURS; 8 >= 8 = TRUE | 7 >= 8 = FALSE
						
						break;
				
					case 'Workshift':
				
						$w = prm_get_workshift($company_id,$work_schedule_id->work_schedule_id);
						$hp_total_hours = ($w) ? $w->total_hours_work_per_block : 0 ;
						return ($credits >= $hp_total_hours) ? TRUE : FALSE ; // CREDITS >= WORKING_HOURS; 8 >= 8 = TRUE | 7 >= 8 = FALSE
						
						break;
				}
			}
			
		}else{
			return FALSE;
		}
		
	}
	
	/**
	 * Update Time In On Leave
	 * @param unknown $date
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	function update_time_in_on_leave($start,$end,$emp_id,$company_id){ // MU GIKAN NI KANG FRITZ PARA CANCEL SA LEAVE APPLICATION
		$CI =& get_instance();
		$current = $start;
		while ($current <= $end) {
			
			// CHECK IF EMPLOYEE IS ON LEAVE: flag_on_leave = yes
			$w = array(
				"emp_id"=>$emp_id,
				"comp_id"=>$company_id,
				"date"=>date("Y-m-d",strtotime($current)),
				"status"=>"Active",
				"flag_on_leave"=>"yes"
			);
			$CI->db->where($w);
			$q = $CI->db->get("employee_time_in");
			$r = $q->row();
			if($r){
				// UPDATE TIME IN ON LEAVE: flag_on_leave = no
				$time_in_id = $r->employee_time_in_id;
				$w = array(
					"emp_id"=>$emp_id,
					"comp_id"=>$company_id,
					"employee_time_in_id"=>$time_in_id
				);
				$val = array("flag_on_leave"=>"no");
				$CI->db->where($w);
				$CI->db->update("employee_time_in",$val);
			}
			
			$current = date('Y-m-d',strtotime($current.' +1 day'));
			
		}
		
		return TRUE;
	}
	
	/**
	 * Work Schedule ID
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $date
	 */
	function prm_work_schedule_id($company_id,$emp_id,$date=NULL){
	
		$CI =& get_instance();
		
		$s = array(
				"work_schedule_id"
		);
		$CI->db->select($s);
	
		$w_date = array(
				"valid_from <="		=>	$date,
				"until >="			=>	$date
		);
		if($date != NULL) $CI->db->where($w_date);
	
		$w = array(
				"emp_id"=>$emp_id,
				"company_id"=>$company_id,
				"payroll_group_id"=>0, // add
				"status"=>"Active"
		);
		$CI->db->where($w);
		$q = $CI->db->get("employee_shifts_schedule");
		$r = $q->row();
	
		if($r){
			// split scheduling
			return $r;
		}else{
				
			$s = array(
					"work_schedule_id"
			);
			$CI->db->select($s);
				
			// default work scheduling
			$w = array(
					"epi.emp_id"=>$emp_id,
					"epi.company_id"=>$company_id,
					"epi.status"=>"Active"
			);
			$CI->db->where($w);
			$CI->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$q = $CI->db->get("employee_payroll_information AS epi");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
	}
	
	/**
	 * Get workday
	 * @param int $company_id
	 * @param int $work_schedule_id
	 */
	function prm_get_workday($company_id,$work_schedule_id){
		$CI =& get_instance();
		$s = array("*","work_type_name AS workday_type");
		$CI->db->select($s);
		$where = array(
				'comp_id' 	   => $company_id,
				"work_schedule_id"=>$work_schedule_id
		);
		$CI->db->where($where);
		$q = $CI->db->get('work_schedule');
		$result = $q->row();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get uniform working day
	 * @param int $company_id
	 * @param int $work_schedule_id
	 * @param string $weekday
	 */
	function prm_get_uniform_working_day($company_id,$work_schedule_id,$weekday){
		$CI =& get_instance();
		// PARA MA CONNECT S WORK SCHEDULE TABLE
		$where = array(
				'rs.company_id' 	   => $company_id,
				"rs.work_schedule_id"=>$work_schedule_id,
				'rs.days_of_work'	   => $weekday
		);
		$CI->db->where($where);
		$CI->db->join("work_schedule AS ws","ws.work_schedule_id = rs.work_schedule_id","LEFT");
		$q = $CI->db->get('regular_schedule AS rs');
		$result = $q->row();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get flexible hour
	 * @param int $company_id
	 * @param int $work_schedule_id
	 */
	function prm_get_flexible_hour($company_id,$work_schedule_id){
		$CI =& get_instance();
		// PARA CONNECT WORK SCHEDULE TABLE
		$where = array(
				'fh.company_id' 	   => $company_id,
				"fh.work_schedule_id"=>$work_schedule_id
		);
		$CI->db->where($where);
		$CI->db->join("work_schedule AS ws","ws.work_schedule_id = fh.work_schedule_id","LEFT");
		$q = $CI->db->get('flexible_hours AS fh');
		$result = $q->row();
		return ($result) ? $result : false;
	}
	
	/**
	 * Get workshift
	 * @param int $company_id
	 * @param int $work_schedule_id
	 */
	function prm_get_workshift($company_id,$work_schedule_id){
		$CI =& get_instance();
		// PARA CONNECT WORK SCHEDULE TABLE
		$s = array("*","total_hours_work_per_block AS total_work_hours");
		$CI->db->select($s);
		$where = array(
			'ss.company_id' 	   => $company_id,
			"ss.work_schedule_id"=>$work_schedule_id
		);
		$CI->db->where($where);
		$CI->db->join("work_schedule AS ws","ws.work_schedule_id = ss.work_schedule_id","LEFT");
		$q = $CI->db->get('schedule_blocks AS ss');
		$result = $q->row();
		return ($result) ? $result : false;
	}
	
	/**
	 * Payroll Draft Pay Run Closed
	 * @param unknown $company_id
	 */
	function payroll_draft_pay_run_closed($company_id){
		$CI =& get_instance();
		
		$token_key = konsum_key();
		$token_key = "20{$token_key}16";
		$val = array(
			"token"=>$token_key
		);
		$where = array(
			'company_id' => $company_id
		);
		$CI->db->where($where);
		$CI->db->where("(token IS NULL)");
		$q = $CI->db->update('draft_pay_runs',$val);
		return  TRUE;
	}
	
	/**
	 * 
	 * @param unknown $needle
	 * @param unknown $haystack
	 * @param string $strict
	 */
	function in_array_custom($needle, $haystack, $strict = false) {
		
		/* if($haystack != FALSE && $haystack != NULL && $haystack != ""){
			foreach ($haystack as $key => $item) {
				if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_custom($needle, $item, $strict))) {
					return (object) $haystack[$key];
				}
			}
		}
		return false; */
		
		if($haystack != FALSE && $haystack != NULL && $haystack != ""){
			foreach ($haystack as $key => $item) {
				if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_custom($needle, $item, $strict))) {
					return (object) $haystack[$key];
					break;
				}
			}
		}
		return false;
		
		/* if($haystack != FALSE && $haystack != NULL && $haystack != ""){
			
			$show_per_page = 150;
			$counter_new = count($haystack) / $show_per_page;
			$counter_new = ($counter_new < 1) ? 1 : $counter_new ;
			$counter_new = floor($counter_new);
			$flag_stop_loop = TRUE;
			for($c = 0; $c <= $counter_new; $c++){
				$start = $c * ($show_per_page);
				$offset = $show_per_page;
				// $end_of_year_details_view_month_array_x = array_slice($haystack, $start, $offset,TRUE);
					
				#p($end_of_year_details_view_month_array_x);
				#print "<br>";
					
				if($flag_stop_loop){
					$end_of_year_details_view_month_array_x = array_slice($haystack, $start, $offset,TRUE);
					if($end_of_year_details_view_month_array_x != NULL){
						foreach ($end_of_year_details_view_month_array_x as $key => $item) {
							if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_custom($needle, $item, $strict))) {
								$flag_stop_loop = FALSE;
								return (object) $haystack[$key];
								break;
							}
						}
					}	
				}
					
			}
			
		}
		return false; */
		
	}
	
	function in_array_custom2($index, $search, $haystack = array(), $strict = false) {
		$new_arr = false;
		if($haystack != false && $haystack != null && count($haystack) > 0){
			$arr = array_filter($haystack, function($ar) use ($search, $index){
				return ($ar["{$index}"] == $search);
			});
			if($arr != null){
				$new_arr = array_values($arr);
				$new_arr = (object) $new_arr[0];
			}
		}
	
		return $new_arr;
	}
	
	function flag_recal(){
		return TRUE;
	}
	
	/**
	 * Check Payroll Group Payslip
	 * @param unknown $payroll_group_id
	 */
	function check_payroll_group_payslip($emp_id,$payroll_group_id,$company_id){
		$CI =& get_instance();
		
		$counter = 0;
		
		// BY PAYROLL GROUP
		$where = array(
			"company_id" => $company_id,
			"payroll_group_id" => $payroll_group_id,
			"status" => "Active"
		);
		$CI->db->where($where);
		$CI->db->where("(view_status = 'Open' OR view_status = 'Waiting for approval' OR view_status = 'Rejected')");
		$q = $CI->db->get("draft_pay_runs");
		$result = $q->result();
		if($result != FALSE){
			foreach($result as $row){
				
				// CHECK EMPLOYEE BY PAYROLL PERIOD
				$sort_param = array(
					"payroll_period"=>$row->pay_period,
					"period_from"=>$row->period_from,
					"period_to"=>$row->period_to
				);
				$get_exclude_list = $CI->prm->get_exclude_list($company_id,$emp_id,$sort_param);
				if(!$get_exclude_list) $counter++;
				
				// BREAKDOWN DRAFT PAY RUNS
				if($CI->input->get("breakdown") > 0){
					print "====================== {$get_exclude_list} : {$row->view_status} : {$row->pay_period} : Run by Payroll Group<br>";
				}
				
			}
		}
	
		// CUSTOM EMPLOYEE
		$where = array(
			"dpr.company_id" => $company_id,
			// "epi.payroll_group_id" => $payroll_group_id,
			// "pp.payroll_group_id" => $payroll_group_id,
			"prc.emp_id" => $emp_id,
			"dpr.status" => "Active"
		);
		$CI->db->where($where);
		$CI->db->where("(dpr.view_status = 'Open' OR dpr.view_status = 'Waiting for approval' OR dpr.view_status = 'Rejected')");
		$CI->db->join("payroll_run_custom AS prc","prc.draft_pay_run_id = dpr.draft_pay_run_id","LEFT");
		
		// COMMENT OUT OLD CODE
		// $CI->db->join("employee_payroll_information AS epi","epi.emp_id = prc.emp_id","LEFT");
		
		// MAG AGAD NANI SIYA SA PAYSLIP NGA PAYROLL GROUP ID DILI SA EMPLOYEE PAYROLL GROUP ID
		// pp.payroll_date = prc.payroll_period && pp.emp_id = prc.emp_id
		// $CI->db->join("payroll_payslip AS pp","pp.emp_id = prc.emp_id && pp.payroll_date = prc.payroll_period","LEFT");
		
		$q = $CI->db->get("draft_pay_runs AS dpr");
		$result = $q->result();
			
		if($result != FALSE){
			
			foreach($result as $row){
		
				// CHECK EMPLOYEE BY PAYROLL PERIOD
				$sort_param = array(
					"payroll_period"=>$row->pay_period,
					"period_from"=>$row->period_from,
					"period_to"=>$row->period_to
				);
				$get_exclude_list = $CI->prm->get_exclude_list($company_id,$emp_id,$sort_param);
				if(!$get_exclude_list) $counter++;
		
				// BREAKDOWN DRAFT PAY RUNS
				if($CI->input->get("breakdown") > 0){
					print "====================== {$get_exclude_list} : {$row->view_status} : {$row->pay_period} : Run by Custom Employee <br>";
				}
				
			}
		
		}
		
		return ($counter > 0) ? TRUE : FALSE ;
		
	}
	
	/**
	 * Employee Payroll Period Closed
	 * @param unknown $emp_id
	 * @param unknown $payroll_group_id
	 * @param unknown $company_id
	 */
	function employee_payroll_period_closed($emp_id,$payroll_group_id,$company_id){
		$CI =& get_instance();
		$payroll_period_array = array();
		$where = array(
			"company_id" => $company_id,
			"payroll_group_id" => $payroll_group_id,
			"status" => "Active",
			"view_status" => "Closed",
		);
		$CI->db->where($where);
		$q = $CI->db->get("draft_pay_runs");
		$result = $q->result();
		if($result != FALSE){
			foreach($result as $row){
				array_push($payroll_period_array, $row->pay_period);
			}
		}else{
			$where = array(
				"dpr.company_id" => $company_id,
				"prc.emp_id" => $emp_id,
				"dpr.status" => "Active",
				"dpr.view_status" => "Closed",
			);
			$CI->db->where($where);
			$CI->db->join("payroll_run_custom AS prc","prc.draft_pay_run_id = dpr.draft_pay_run_id","LEFT");
			$q = $CI->db->get("draft_pay_runs AS dpr");
			$result = $q->result();
			if($result != FALSE){
				foreach($result as $row){
					array_push($payroll_period_array, $row->pay_period);
				}
			}
		}
		return $payroll_period_array;
	}
	
	/**
	 * Get Url Link
	 */
	function get_base_url_link(){
		
		/* // URL LINKS
		if(base_url() == "http://payrollv4.konsum.local/" || base_url() == "http://payrollv5.konsum.local/" || base_url() == "http://payrollv3.konsum.local/"){
			$url_link = "http://payrollv3.konsum.local/"; // local
		}else if(base_url() == "https://sv01.ashima.ph/" || base_url() == "https://sv02.ashima.ph/" || base_url() == "https://ashima.ph/"
			|| base_url() == "https://sv03.ashima.ph/" || base_url() == "https://sv04.ashima.ph/" || base_url() == "https://sv05.ashima.ph/"
		){
			$url_link = "https://ashima.ph/"; // live
		}else if(base_url() == "http://ashimav1.konsum.ph/" || base_url() == "http://ashimav2.konsum.ph/" || base_url() == "http://ashimav3.konsum.ph/" || base_url() == "http://ashima.konsum.ph/"){
			$url_link = "http://ashimav1.konsum.ph/"; // staging
		}else{
			$url_link = "/"; // local
		} */
		
		// AWS BASE URL LINK
		// URL LINKS
		if(base_url() == "http://payrollv4.konsum.local/" || base_url() == "http://payrollv5.konsum.local/" || base_url() == "http://payrollv3.konsum.local/"){
			$url_link = "http://payrollv3.konsum.local/"; // local
		}else if(base_url() == "http://ashimav1.konsum.ph/" || base_url() == "http://ashimav2.konsum.ph/" || base_url() == "http://ashimav3.konsum.ph/" || base_url() == "http://ashima.konsum.ph/"){
			$url_link = "http://ashimav1.konsum.ph/"; // staging
		}else{
			$url_link = "/";
		}
		
		return $url_link;
	}
	
	/**
	 * To Do End of Year Run
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	function to_do_end_of_year_run($emp_id,$company_id){
		$CI =& get_instance();
		$w = array(
			"aeyr.company_id"=>$company_id,
			"eyr.view_status"=>"Waiting for approval",
			"eyr.status"=>"Active"
		);
		$CI->db->where($w);
		// aeyr.end_of_year_run_id = eyr.end_of_year_run_id AND
		$CI->edb->join("approval_end_of_year_run AS aeyr","aeyr.token = eyr.token","INNER");
		$q = $CI->edb->get("end_of_year_run AS eyr");
		$r = $q->result();
		if($r){
			$val_array = array();
			foreach($r as $row){
				
				// CHECK EMP ID IF OWNER
				/* $check_emp_id = ($emp_id != "" && $emp_id > 0) ? $emp_id : "-99{$company_id}" ; */
				
				// APPROVERS EMP ID
				$approvers_emp_id = $row->approvers_emp_id;
				$approvers_emp_id = explode(",", $approvers_emp_id);
				
				if(in_array($emp_id, $approvers_emp_id)){
					// CHECK EMP ID IF OWNER, PANG URL RANI NGA TRAPPING
					$url_emp_id = ($emp_id != "" && $emp_id > 0) ? $emp_id : "-{$company_id}" ;
					$val = array(
						"url"=>"/approval/end_of_year_run/index/{$row->token}/1{$url_emp_id}0_{$row->token_level}",
						"year_run"=>$row->year_run,
						"pay_schedule"=>$row->pay_schedule,
						"total_thirteenth_month"=>$row->total_thirteenth_month,
						"total_gross_compensation_income"=>$row->total_gross_compensation_income,
						"total_contributions"=>$row->total_contributions,
						"total_non_taxable"=>$row->total_non_taxable,
						"total_taxable"=>$row->total_taxable,
						"end_of_year_run_id"=>$row->end_of_year_run_id
					);
					array_push($val_array, $val);
				}
			}
			if($val_array != NULL){
				return (object) $val_array;
			}else{
				return FALSE;
			}
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Encrypt Value
	 * @param unknown $var
	 */
	function encrypt_val($var){
		$CI =& get_instance();
		// $secret_key = $CI->config->item('secret_key');
		$secret_key = konsum_key();
		// return 'AES_ENCRYPT("'.$var.'","'.$this->_config['secret_key'].'")';
		$val = 'AES_ENCRYPT("'.$var.'","'.$secret_key.'")';
		$val = str_replace( "'", '', $val );
		return trim($val);
	}
	
	/**
	 * Convert object to array
	 * @param unknown $if_time_in_regular_days_custom_array
	 */
	function convert_object_to_array_time_in($if_time_in_regular_days_custom_array){
		$in = array();
		foreach($if_time_in_regular_days_custom_array as $row){
			$emp_in = array(
				'date'	  => $row->date,
				"time_in" => $row->time_in,
				"time_out" => $row->time_out,
				"work_schedule_id" => $row->work_schedule_id,
				"total_hours" => $row->total_hours,
				"total_hours_required" => $row->total_hours_required,
				"emp_id" => $row->emp_id, // new array
				"tardiness_min" => $row->tardiness_min, // new array
				"undertime_min" => $row->undertime_min, // new array
				"absent_min" => $row->absent_min // new array
			);
		
			array_push($in,$emp_in);
		}
		return $in;
	}
	
	/**
	 * Update Payroll Cronjob Table
	 * @param string $type
	 * @param unknown $datetime
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	function payroll_cronjob_helper($type='timesheet',$datetime,$emp_id,$company_id){
		if($type != ''){
			$CI =& get_instance();
			// $CI->load->model('paycheck/payroll_run_model','prm');
			// $employee_cronjob_list_array = $CI->prm->employee_cronjob_list($company_id);
			$employee_cronjob_list_array = employee_cronjob_list($company_id);
			if($employee_cronjob_list_array != NULL){
				// UPDATE PAYROLL CRONJOB
				$check_emp_details = in_array_foreach_custom($emp_id.$company_id,$employee_cronjob_list_array);
				if($check_emp_details != FALSE){
					// EMPLOYEE ALLOWANCES CUSTOM
					$employee_allowances_array_custom = employee_allowances_array_custom($company_id);
					$datetime = date('Y-m-d',strtotime($datetime));
					$datetimestamp = date('Y-m-d H:i:s');
					foreach($check_emp_details as $check_emp_details_row){
						if(strtotime($check_emp_details_row->period_from) <= strtotime($datetime) && strtotime($datetime) <= strtotime($check_emp_details_row->period_to)){
							$w = array(
									'emp_id'=>$emp_id,
									'company_id'=>$company_id,
									'payroll_period'=>$check_emp_details_row->payroll_period,
									'period_from'=>$check_emp_details_row->period_from,
									'period_to'=>$check_emp_details_row->period_to
							);
							$CI->db->where($w);
				
							// VAL ARRAY
							if($type == 'timesheet'){ // TIMESHEET
								
								$val = array(
										'flag_run_parent'=>'0',
										'flag_night_differential'=>'0',
										'flag_rest_day'=>'0',
										'flag_holiday_premium'=>'0',
										'flag_paid_leave'=>'0',
										'flag_absences'=>'0',
										'flag_tardiness'=>'0',
										'flag_undertime'=>'0',
										'flag_hoursworked'=>'0',
										'datetimestamp'=>$datetimestamp,
								);
								
								// UPDATE ALLOWANCES FLAG ALSO
								$employee_allowances_array_custom_search = in_array_custom($emp_id."custom_search", $employee_allowances_array_custom);
								if($employee_allowances_array_custom_search){
									$val = array(
										'flag_run_parent'=>'0',
										'flag_night_differential'=>'0',
										'flag_rest_day'=>'0',
										'flag_holiday_premium'=>'0',
										'flag_paid_leave'=>'0',
										'flag_absences'=>'0',
										'flag_tardiness'=>'0',
										'flag_undertime'=>'0',
										'flag_hoursworked'=>'0',
										'datetimestamp'=>$datetimestamp,
										'flag_allowances'=>'0',
									);
								}
								
							}else if($type == 'overtime'){ // OVERTIME
								$val = array(
										'flag_run_parent'=>'0',
										'flag_overtime'=>'0',
										'flag_night_differential'=>'0',
										'datetimestamp'=>$datetimestamp,
								);
							}else if($type == 'leave_application'){ // LEAVE APPLICATION
								
								$val = array(
										'flag_run_parent'=>'0',
										'flag_paid_leave'=>'0',
										'flag_absences'=>'0',
										'flag_tardiness'=>'0',
										'flag_undertime'=>'0',
										'flag_hoursworked'=>'0',
										'datetimestamp'=>$datetimestamp,
								);
								
								// UPDATE ALLOWANCES FLAG ALSO
								$employee_allowances_array_custom_search = in_array_custom($emp_id."custom_search", $employee_allowances_array_custom);
								if($employee_allowances_array_custom_search){
									$val = array(
										'flag_run_parent'=>'0',
										'flag_paid_leave'=>'0',
										'flag_absences'=>'0',
										'flag_tardiness'=>'0',
										'flag_undertime'=>'0',
										'flag_hoursworked'=>'0',
										'datetimestamp'=>$datetimestamp,
										'flag_allowances'=>'0',
									);
								}
								
							}else if($type == 'all'){ // LEAVE APPLICATION
								$val = array(
										'flag_run_parent'=>'0',
										'flag_night_differential'=>'0',
										'flag_rest_day'=>'0',
										'flag_holiday_premium'=>'0',
										'flag_overtime'=>'0',
										'flag_paid_leave'=>'0',
										'flag_absences'=>'0',
										'flag_tardiness'=>'0',
										'flag_undertime'=>'0',
										'flag_hoursworked'=>'0',
											
										'flag_allowances'=>'0',
										'flag_commissions'=>'0',
										'flag_de_minimis'=>'0',
										'flag_other_earnings'=>'0',
											
										'flag_third_party_loans'=>'0',
										'flag_government_loans'=>'0',
										'flag_other_deductions'=>'0',
											
										'datetimestamp'=>$datetimestamp,
								);
							}else if($type == 'paycheck_adjustment'){
								$val = array(
										'flag_run_parent'=>'0',
										'datetimestamp'=>$datetimestamp,
								);
							}
							
							// UPDATE CRONJOB TABLE
							$update_val = $CI->db->update('payroll_cronjob',$val);
							
							// UPDATE PAYROLL HOURSWORKED TABLE
							$val_array = array(
								"flag_save"=>"0",
								"flag_cronjob_save"=>"0",
							);
							$CI->db->where($w);
							$CI->db->update("payroll_employee_hours",$val_array);
							
							return ($update_val) ? TRUE : FALSE ;
							break;
						}
					}
				}	
			}
		}
		return FALSE;
	}
	
	/**
	 * Employee Allowances ARray Custom
	 * @param unknown $company_id
	 */
	function employee_allowances_array_custom($company_id){
		$CI =& get_instance();
		$info = array ();
		$where = array (
			'company_id' => $company_id,
			'status' => 'Active',
			'employee_entitled_to_allowance_for_absent' => 'no',
		);
		$CI->db->where ( $where );
		$q = $CI->db->get ( 'employee_allowances' );
		$r = $q->result ();
		if ($r != FALSE) {
			foreach ( $r as $row ) {
				$val = array(
					"custom_search"=>$row->emp_id."custom_search",
					"emp_id"=>$row->emp_id,
				);
				array_push($info, $val);
			}
		}
		return $info;
	}
	
	/**
	 * Employee Cronjob List
	 *
	 * @param unknown $company_id
	 */
	function employee_cronjob_list($company_id) {
		$CI =& get_instance();
		$info = array ();
		$where = array (
				'company_id' => $company_id,
				'status' => 'Active'
		)
		// 'flag_run_parent' => 0
		;
		$CI->db->where ( $where );
		$q = $CI->db->get ( 'payroll_cronjob' );
		$r = $q->result ();
		if ($r != FALSE) {
			foreach ( $r as $row ) {
				$val = array (
						'custom_search' => $row->emp_id . $row->payroll_period . $row->period_from . $row->period_to,
						'custom_search2' => $row->emp_id . $row->company_id,
						'emp_id' => $row->emp_id,
						'payroll_period' => $row->payroll_period,
						'period_from' => $row->period_from,
						'period_to' => $row->period_to,
	
						'flag_run_parent' => $row->flag_run_parent,
						'flag_night_differential' => $row->flag_night_differential,
						'flag_rest_day' => $row->flag_rest_day,
						'flag_holiday_premium' => $row->flag_holiday_premium,
						'flag_overtime' => $row->flag_overtime,
						'flag_paid_leave' => $row->flag_paid_leave,
						'flag_absences' => $row->flag_absences,
						'flag_tardiness' => $row->flag_tardiness,
						'flag_undertime' => $row->flag_undertime,
						'flag_hoursworked' => $row->flag_hoursworked,
	
						'flag_allowances' => $row->flag_allowances,
						'flag_commissions' => $row->flag_commissions,
						'flag_de_minimis' => $row->flag_de_minimis,
						'flag_other_earnings' => $row->flag_other_earnings,
	
						'flag_third_party_loans' => $row->flag_third_party_loans,
						'flag_government_loans' => $row->flag_government_loans,
						'flag_other_deductions' => $row->flag_other_deductions,
	
						'night_differential_details' => $row->night_differential_details,
						'rest_day_details' => $row->rest_day_details,
						'holiday_premium_details' => $row->holiday_premium_details,
						'overtime_details' => $row->overtime_details,
						'paid_leave_details' => $row->paid_leave_details,
						'absences_details' => $row->absences_details,
						'tardiness_details' => $row->tardiness_details,
						'undertime_details' => $row->undertime_details,
						'hoursworked_details' => $row->hoursworked_details,
	
						'allowances_details' => $row->allowances_details,
						'commissions_details' => $row->commissions_details,
						'de_minimis_details' => $row->de_minimis_details,
						'other_earnings_details' => $row->other_earnings_details,
	
						'third_party_loan_details' => $row->third_party_loan_details,
						'government_loan_details' => $row->government_loan_details,
						'other_deduction_details' => $row->other_deduction_details,
				);
				array_push ( $info, $val );
			}
		}
		return $info;
	}
	
	function payroll_cronjob_helper_global($type,$emp_id="",$company_id,$params=NULL){
		$datetimestamp = date('Y-m-d H:i:s');
		
		// UPDATE TIMESHEETS, EARNINGS AND DEDUCTIONS
		if($type == 'basic_pay'){
			$CI =& get_instance();
			$w = array(
				'emp_id'=>$emp_id,
				'company_id'=>$company_id
			);
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				'flag_night_differential'=>'0',
				'flag_rest_day'=>'0',
				'flag_holiday_premium'=>'0',
				'flag_overtime'=>'0',
				'flag_paid_leave'=>'0',
				'flag_absences'=>'0',
				'flag_tardiness'=>'0',
				'flag_undertime'=>'0',
				'flag_hoursworked'=>'0',
					
				'flag_allowances'=>'0',
				'flag_commissions'=>'0',
				'flag_de_minimis'=>'0',
				'flag_other_earnings'=>'0',
					
				'flag_third_party_loans'=>'0',
				'flag_government_loans'=>'0',
				'flag_other_deductions'=>'0',
					
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE PAYROLL HOURSWORKED TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where($w);
			$CI->db->update("payroll_employee_hours",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPDATE COMMISSIONS
		if($type == 'commissions'){
			$CI =& get_instance();
			$w = array(
				'emp_id'=>$emp_id,
				'company_id'=>$company_id
			);
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				'flag_commissions'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_commission TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where($w);
			$CI->db->update("payroll_commission",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPDATE DE MINIMIS
		if($type == 'deminimis'){
			$CI =& get_instance();
			$w = array(
				'emp_id'=>$emp_id,
				'company_id'=>$company_id
			);
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				'flag_de_minimis'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_de_minimis TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where($w);
			$CI->db->update("payroll_de_minimis",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPDATE INSURANCE
		if($type == 'insurance'){
			$CI =& get_instance();
			$w = array(
				'emp_id'=>$emp_id,
				'company_id'=>$company_id
			);
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				// 'flag_de_minimis'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_insurance TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where($w);
			$CI->db->update("payroll_insurance",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPDATE ALLOWANCES
		if($type == 'allowances'){
			$CI =& get_instance();
			$w = array(
				'emp_id'=>$emp_id,
				'company_id'=>$company_id
			);
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				'flag_allowances'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_allowances TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where($w);
			$CI->db->update("payroll_allowances",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPDATE OTHER EARNINGS
		if($type == 'other_earnings'){
			$CI =& get_instance();
			$w = array(
				'emp_id'=>$emp_id,
				'company_id'=>$company_id
			);
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				'flag_other_earnings'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_other_earnings_lite TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where($w);
			$CI->db->update("payroll_other_earnings_lite",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPDATE OTHER DEDUCTIONS
		if($type == 'other_deductions'){
			$CI =& get_instance();
			$w = array(
				'emp_id'=>$emp_id,
				'company_id'=>$company_id
			);
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				'flag_other_deductions'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_for_other_deductions TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where($w);
			$CI->db->update("payroll_for_other_deductions",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPDATE GOVERNMENT LOANS
		if($type == 'government_loans'){
			$CI =& get_instance();
			$w = array(
				'emp_id'=>$emp_id,
				'company_id'=>$company_id
			);
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				'flag_government_loans'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_run_government_loans TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where($w);
			$CI->db->update("payroll_run_government_loans",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPDATE THIRD PARTY LOANS
		if($type == 'third_party_loan'){
			$CI =& get_instance();
			$w = array(
				'emp_id'=>$emp_id,
				'company_id'=>$company_id
			);
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				'flag_third_party_loans'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_run_loans TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where($w);
			$CI->db->update("payroll_run_loans",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// GLOBAL WHERE ARRAY
		$CI =& get_instance();
		$w = array(
			'emp_id'=>$emp_id,
			'company_id'=>$company_id
		);
		
		// UPATE TIMEESHEET REQUIRED
		if($type == 'timesheet_required'){
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				'flag_absences'=>'0',
				'flag_tardiness'=>'0',
				'flag_undertime'=>'0',
				'flag_hoursworked'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_employee_hours TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where($w);
			$CI->db->update("payroll_employee_hours",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPATE DE MINIMIS
		if($type == 'entitled_to_de_minimis'){
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				'flag_de_minimis'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_de_minimis TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where($w);
			$CI->db->update("payroll_de_minimis",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPATE OVERTIME PAY
		if($type == 'entitled_to_overtime_pay'){
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				'flag_overtime'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_employee_hours TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where($w);
			$CI->db->update("payroll_employee_hours",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPATE NIGHT DIFFERENTIAL
		if($type == 'entitled_to_night_differential'){
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				'flag_night_differential'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_employee_hours TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where($w);
			$CI->db->update("payroll_employee_hours",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPATE HOLIDAY PAY
		if($type == 'entitled_to_holiday_pay'){
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				'flag_holiday_premium'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_employee_hours TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where($w);
			$CI->db->update("payroll_employee_hours",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPATE REST DAY PAY
		if($type == 'entitled_to_rest_day'){
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				'flag_rest_day'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
				
			// UPDATE payroll_employee_hours TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where($w);
			$CI->db->update("payroll_employee_hours",$val_array);
				
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPATE LEAVES
		if($type == 'entitled_to_leaves'){
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				'flag_paid_leave'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_employee_hours TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where($w);
			$CI->db->update("payroll_employee_hours",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPDATE CRONJOB TABLE: HOLIDAY SETTINGS > GLOBAL UPDATE
		if($type == 'holiday_settings' || $type == 'night_differential_settings' || $type == 'overtime_settings'){
			$CI->db->where(array('company_id'=>$company_id));
			$val = array(
				'flag_run_parent'=>'0',
				'flag_hoursworked'=>'0',
				'flag_absences'=>'0',
				'flag_paid_leave'=>'0',
				'flag_night_differential'=>'0',
				'flag_overtime'=>'0',
				'flag_holiday_premium'=>'0',
				'flag_rest_day'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_employee_hours TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where(array('company_id'=>$company_id));
			$CI->db->update("payroll_employee_hours",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPDATE CONTRIBUTIONS
		if($type == 'sss' || $type == 'philhealth' || $type == 'pagibig' || $type == 'tax'){
			$CI->db->where($w);
			$val = array(
				'flag_run_parent'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPDATE CONTRIBUTIONS SETTINGS > GLOBAL UPDATE
		$contribution_array = array(
			"sss_settings",
			"philhealth_settings",
			"hdmf_settings",
			"contribution_schedule",
			"contribution_rules_earnings",
			"tax_deduction_schedule",
			"tax_computation_rules",
		);
		if(in_array($type, $contribution_array)){
			$CI->db->where(array('company_id'=>$company_id));
			$val = array(
				'flag_run_parent'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPDATE PAY RATE TABLE > GLOBAL SETTINGS
		if($type == 'pay_rate'){
			$CI->db->where(array('company_id'=>$company_id));
			$val = array(
				'flag_run_parent'=>'0',
				'flag_hoursworked'=>'0',
				'flag_holiday_premium'=>'0',
				'flag_rest_day'=>'0',
				'flag_overtime'=>'0',
				'flag_night_differential'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_employee_hours TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where(array('company_id'=>$company_id));
			$CI->db->update("payroll_employee_hours",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPDATE LEAVE SETTINGS > GLOBAL SETTINGS
		if($type == 'leave_settings'){
			$CI->db->where(array('company_id'=>$company_id));
			$val = array(
				'flag_run_parent'=>'0',
				'flag_hoursworked'=>'0',
				'flag_paid_leave'=>'0',
				'flag_absences'=>'0',
				'flag_tardiness'=>'0',
				'flag_undertime'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_employee_hours TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where(array('company_id'=>$company_id));
			$CI->db->update("payroll_employee_hours",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		// UPDATE TARDINESS SETTINGS > GLOBAL SETTINGS
		if($type == 'tardiness_settings'){
			$CI->db->where(array('company_id'=>$company_id));
			$val = array(
				'flag_run_parent'=>'0',
				'flag_hoursworked'=>'0',
				'flag_paid_leave'=>'0',
				'flag_absences'=>'0',
				'flag_tardiness'=>'0',
				'datetimestamp'=>$datetimestamp,
			);
			$update_val = $CI->db->update('payroll_cronjob',$val);
			
			// UPDATE payroll_employee_hours TABLE
			$val_array = array(
				"flag_save"=>"0",
				"flag_cronjob_save"=>"0",
			);
			$CI->db->where(array('company_id'=>$company_id));
			$CI->db->update("payroll_employee_hours",$val_array);
			
			return ($update_val) ? TRUE : FALSE ;
		}
		
		return FALSE;
	}
	
	/**
	 * Check Employee Paycheck Record
	 * @param unknown $emp_id
	 * @param unknown $company_id
	 */
	function check_employee_paycheck_record($emp_id,$company_id){
		$CI =& get_instance();
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active",
		);
		$CI->db->where($w);
		$q = $CI->db->get("payroll_payslip");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Paydraft
	 * @param unknown $emp_id
	 * @param unknown $payroll_group_id
	 * @param unknown $company_id
	 */
	function check_employee_paydraft($emp_id,$payroll_group_id,$company_id){
		$CI =& get_instance();
		// BY PAYROLL GROUP
		$s = array(
			"dpr.pay_period AS payroll_period",
			"dpr.period_from AS period_from",
			"dpr.period_to AS period_to",
			"pp.payroll_payslip_id AS payroll_payslip_id",
			//"pp.net_amount AS net_amount",
		);
		$CI->db->select($s);
		$w = array(
			"pprh.company_id"=>$company_id,
			"pprh.status"=>"Active",
			"dpr.payroll_group_id"=>$payroll_group_id,
			"pp.emp_id"=>$emp_id,
		);
		$CI->db->where($w);
		// $CI->db->where("(dpr.view_status = 'Open' OR dpr.view_status = 'Rejected')");
		$CI->db->where("(dpr.view_status = 'Open')");
		$CI->db->where('(pprh.payroll_period = (SELECT payroll_period FROM payroll_pre_run_history WHERE company_id = "'.$company_id.'" ORDER BY payroll_pre_run_history_id DESC LIMIT 1))',FALSE,FALSE);
		$CI->db->join("draft_pay_runs AS dpr","pprh.draft_pay_run_id = dpr.draft_pay_run_id","LEFT");
		$CI->db->join("payroll_payslip AS pp","pp.payroll_group_id = dpr.payroll_group_id AND pp.payroll_date = dpr.pay_period","LEFT");
		$CI->db->group_by("pprh.draft_pay_run_id");
		$q = $CI->db->get("payroll_pre_run_history AS pprh");
		$r = $q->result();
		if($r != FALSE){
			return $r;
		}else{
			// CUSTOM EMPLOYEE
			$s = array(
				"dpr.pay_period AS payroll_period",
				"dpr.period_from AS period_from",
				"dpr.period_to AS period_to",
				"pp.payroll_payslip_id AS payroll_payslip_id",
				//"pp.net_amount AS net_amount",
			);
			$CI->db->select($s);
			$w = array(
				"prc.company_id"=>$company_id,
				"prc.status"=>"Active",
				"prc.emp_id"=>$emp_id,
			);
			$CI->db->where($w);
			// $CI->db->where("(dpr.view_status = 'Open' OR dpr.view_status = 'Rejected')");
			$CI->db->where("(dpr.view_status = 'Open')");
			$CI->db->where('(prc.payroll_period = (SELECT payroll_period FROM payroll_pre_run_history WHERE company_id = "'.$company_id.'" ORDER BY payroll_pre_run_history_id DESC LIMIT 1))',FALSE,FALSE);
			$CI->db->join("draft_pay_runs AS dpr","prc.draft_pay_run_id = dpr.draft_pay_run_id","LEFT");
			$CI->db->join("payroll_payslip AS pp","pp.emp_id = prc.emp_id AND pp.payroll_date = prc.payroll_period","LEFT");
			$q = $CI->db->get("payroll_run_custom AS prc");
			$r = $q->result();
			if($r != FALSE){
				return $r;
			}
		}
		
		return FALSE;
	}
	
	
/* End of file payroll_run_helper.php */
/* Location: ./application/helpers/payroll_run_helper.php */