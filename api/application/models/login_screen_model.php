<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Login Screen Model
 *
 * @category Model
 * @version 1.0
 * @author Jonathan Bangga <jonathanbangga@gmail.com>
 */
class Login_screen_model extends CI_Model {
	
	/**
	 * Check Employee Number
	 * @param unknown_type $emp_no
	 */
	public function check_emp_no($emp_no){
		$w = array(
			'payroll_cloud_id'=>$emp_no,
			'user_type_id'=>'5'
		);
		$this->edb->where($w);
		$q = $this->edb->get('accounts');
		return ($q->num_rows() > 0) ? $q->row() : FALSE ;
	}
	
	public function check_emp_info($emp_no){
		$w_emp = array(
			"a.payroll_cloud_id"=>$emp_no,
			"a.user_type_id"=>"5"
		);
		$this->edb->where($w_emp);
		$this->edb->join("employee_payroll_information AS pi","pi.emp_id = e.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$q_emp = $this->edb->get("employee AS e");
		$q = $q_emp->row();
		
		return ($q_emp->num_rows() > 0) ? $q_emp->row() : FALSE ;
	}
	
	
	public function new_check_emp_info($emp_no,$comp_id){
		$w_emp = array(
				"a.payroll_cloud_id"=>$emp_no,
				"a.user_type_id"=>"5",
				"e.company_id" => $comp_id,
		);
		$this->edb->where($w_emp);
		$this->edb->join("employee_payroll_information AS pi","pi.emp_id = e.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$q_emp = $this->edb->get("employee AS e");
		$q = $q_emp->row();
	
		return ($q_emp->num_rows() > 0) ? $q_emp->row() : FALSE ;
	}
	
	public function get_position($position_id){
		$w_emp = array(
				"position_id"=> $position_id,
		);
		$this->edb->where($w_emp);
		$q_emp = $this->edb->get("position");
		$q = $q_emp->row();
		
		return ($q_emp->num_rows() > 0) ? $q_emp->row() : FALSE ;
	}
	
	/**
	 * Insert Time In Log
	 * @param unknown_type $date
	 * @param unknown_type $emp_no
	 */
	public function insert_time_in($date,$emp_no,$min_log,$check_type =""){
		
		$shift_name = "";
		// get employee information
		$w_emp = array(
			"a.payroll_cloud_id"=>$emp_no,
			"a.user_type_id"=>"5"
		);
		$this->edb->where($w_emp);
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$q_emp = $this->edb->get("employee AS e");
		$r_emp = $q_emp->row();
		
		$emp_id = $r_emp->emp_id;
		$comp_id = $r_emp->company_id;
		
		// check payroll group
		$w_payroll_group = array(
			"emp_id"=>$emp_id
		);
		$this->db->where($w_payroll_group);
		$q_payroll_group = $this->db->get("employee_payroll_information");
		$r_payroll_group = $q_payroll_group->row();
		if($q_payroll_group->num_rows() > 0){
			$payroll_group = $r_payroll_group->payroll_group_id;
		}else{
			return FALSE;
		}
		
		// check number of breaks
		$number_of_breaks_per_day = 0;
		
		# UNIFORM WORKING DAYS
		$w_uwd = array(
			"payroll_group_id"=>$payroll_group,
			"company_id"=>$comp_id
		);
		$this->db->where($w_uwd);
		$q_uwd = $this->db->get("regular_schedule");
		$r_uwd = $q_uwd->row();
		if($q_uwd->num_rows() > 0){
			$number_of_breaks_per_day = $r_uwd->break_in_min;
			$shift_name = "regular schedule";
		}else{
			# WORKSHIFT SETTINGS
			$w_ws = array(
				"payroll_group_id"=>$payroll_group,
				"company_id"=>$comp_id
			);
			$this->db->where($w_ws);
			$q_ws = $this->db->get("split_schedule");
			$r_ws = $q_ws->row();
			if($q_ws->num_rows() > 0){
				$number_of_breaks_per_day = $r_ws->number_of_breaks_per_shift;
				$shift_name = "split schedule";
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
					$number_of_breaks_per_day = $r_fh->number_of_breaks_per_day;
					$shift_name = "flexible hours";
				}
				
			}
		}
		
		// check if breaktime is 0
		if($number_of_breaks_per_day == 0){
			// check employee time in
			$current_date = date("Y-m-d H:i:s");
			$w = array(
				"a.payroll_cloud_id"=>$emp_no,
				"a.user_type_id"=>"5"
			);
			$this->edb->where($w);
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
			$this->db->order_by("eti.time_in","DESC");
			$q = $this->edb->get("employee_time_in AS eti",1,0);
			$r = $q->row();
			if($q->num_rows() == 0){
				
				/* CHECK TIME IN START */
				$wst = $this->check_workday_settings_start_time(date("l"),$payroll_group,$comp_id,$r->time_in); 
				if($wst != FALSE){
					// new start time
					$nwst = date("Y-m-d {$wst}");
					$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
					// if($check_diff_total_hours <= 1 && $check_diff_total_hours >= 0) $current_date = $nwst;
				}
				
				// insert time in log
				$val = array(
					"emp_id"=>$emp_id,
					"comp_id"=>$comp_id,
					"date"=>$date,
					"time_in"=>$current_date
				);
				$insert = $this->db->insert("employee_time_in",$val);
				
				if($insert){
					$w2 = array(
						"a.payroll_cloud_id"=>$emp_no,
						"eti.date"=>$date
					);
					$this->edb->where($w2);
					$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
					$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
					$q2 = $this->edb->get("employee_time_in AS eti",1,0);
	
					return ($q2) ? $q2->row() : FALSE ;
				}
			}else{
				
				// get date time in to date time out
				$workday = date("l",strtotime($r->time_in));
				$payroll_group_id = $r->payroll_group_id;
	
				// check rest day
				$check_rest_day = $this->check_rest_day($workday,$payroll_group_id,$comp_id);
				if($check_rest_day){
	
					// global where update data
					$where_update = array(
						"eti.emp_id"=>$emp_id,
						"eti.comp_id"=>$comp_id,
						"eti.employee_time_in_id"=>$r->employee_time_in_id
					);
					
					if($check_type == "time out"){
						
						// update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE FOR REST DAY
						$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
						if($min_log < $get_diff){
							$update_val = array("time_out"=>$current_date);
							$this->db->where($where_update);
							$update = $this->db->update("employee_time_in AS eti",$update_val);
						}
						
						$this->edb->where($where_update);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
		
						// update total hours and total hours required rest day
						$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
						$update_timein_logs = array(
							"tardiness_min"=>0,
							"undertime_min"=>0,
							"total_hours"=>$get_total_hours,
							"total_hours_required"=>$get_total_hours
						);
						$this->db->where($where_update);
						$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
						
						return ($q2) ? $q2->row() : FALSE ;
						exit;
						
					}else if($check_type == "time in"){
						
						/* CHECK TIME IN START */
						$wst = $this->check_workday_settings_start_time(date("l"),$payroll_group,$comp_id);
						if($wst != FALSE){
							// new start time
							$nwst = date("Y-m-d {$wst}");
							$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
							// if($check_diff_total_hours <= 1 && $check_diff_total_hours >= 0) $current_date = $nwst;
						}
						
						// insert time in value for rest day ============================================== >>> INSERT TIME IN VALUE FOR REST DAY
						$insert = FALSE;
						$get_diff = (strtotime($current_date) - strtotime($r->time_out)) / 60;
						if($min_log < $get_diff){
							$val = array(
								"emp_id"=>$emp_id,
								"comp_id"=>$comp_id,
								"date"=>$date,
								"time_in"=>$current_date
							);
							$insert = $this->db->insert("employee_time_in",$val);	
						}
						
						if($insert){
							$w2 = array(
								"a.payroll_cloud_id"=>$emp_no,
								"eti.date"=>$date
							);
							$this->edb->where($w2);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$this->db->order_by("eti.time_in","DESC");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
							exit;
						}else{
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
							exit;
						}
					}
				}
				
				// check workday settings
				$workday_settings_start_time = $this->check_workday_settings_start_time($workday,$payroll_group_id,$comp_id); 
				$workday_settings_end_time = $this->check_workday_settings_end_time($workday,$payroll_group_id,$comp_id); 
	
				if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
					
					// for night shift time in and time out value for working day
					$check_bet_timein = date("Y-m-d")." ".$workday_settings_start_time;
					$check_bet_timeout = date("Y-m-d",strtotime("+1 day"))." ".$workday_settings_end_time;
				}else{
					
					// for day shift time in and time out value for working day
					$check_bet_timein = date("Y-m-d")." ".$workday_settings_start_time;
					$check_bet_timeout = date("Y-m-d")." ".$workday_settings_end_time;
				}
				
				// check between date time in to date time out
				$add_oneday_timein = date("Y-m-d",strtotime("+1 day"))." ".$workday_settings_start_time;
	
				if(strtotime($check_bet_timein) <= strtotime($r->time_in) && strtotime($r->time_in) <= strtotime($add_oneday_timein." -2 hours")){
					
					// global where update data
					$where_update = array(
						"eti.emp_id"=>$emp_id,
						"eti.comp_id"=>$comp_id,
						"eti.employee_time_in_id"=>$r->employee_time_in_id
					);
					
					if($check_type == "time out"){
						
						// update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE
						$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
						if($min_log < $get_diff){
							$update_val = array("time_out"=>$current_date);
							$this->db->where($where_update);
							$update = $this->db->update("employee_time_in AS eti",$update_val);
						}
						
						$this->edb->where($where_update);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
		
						// update total hours and total hours required rest day
						$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
						
						// tardiness and undertime value
						$update_tardiness = $this->get_tardiness_val($emp_id,$comp_id,$r->time_in);
						$update_undertime = $this->get_undertime_val($emp_id,$comp_id,$r->time_in,$current_date);
						
						// check tardiness value
						$flag_tu = 0;
						
						$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r->time_in)), $emp_id);
						$get_total_hours_worked = ($hours_worked / 2) + .5;
						if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
							$update_tardiness = 0;
							$update_undertime = 0;
							$flag_tu = 1;
						}
						
						// required hours worked only
						$new_total_hours = $this->get_tot_hours($emp_id,$comp_id,$r->time_in,$current_date,$hours_worked);
						
						$update_timein_logs = array(
							"tardiness_min"=>$update_tardiness,
							"undertime_min"=>$update_undertime,
							"total_hours"=>$new_total_hours,
							"total_hours_required"=>$get_total_hours,
							"flag_tardiness_undertime"=>$flag_tu
						);
						$this->db->where($where_update);
						$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
						
						return ($q2) ? $q2->row() : FALSE ;
					}else{
						
						/* CHECK TIME IN START */
						$wst = $this->check_workday_settings_start_time(date("l"),$payroll_group,$comp_id);
						if($wst != FALSE){
							// new start time
							$nwst = date("Y-m-d {$wst}");
							$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
							// if($check_diff_total_hours <= 1 && $check_diff_total_hours >= 0) $current_date = $nwst;
						}
						
						// insert time in log ================================================================ >>>> INSERT NEW TIME IN LOG SAME DATE
						$insert = FALSE;
						$get_diff = (strtotime($current_date) - strtotime($r->time_out)) / 60;
						if($min_log < $get_diff){
							$val = array(
								"emp_id"=>$emp_id,
								"comp_id"=>$comp_id,
								"date"=>$date,
								"time_in"=>$current_date
							);
							$insert = $this->db->insert("employee_time_in",$val);	
						}
						
						if($insert){
							$w2 = array(
								"a.payroll_cloud_id"=>$emp_no,
								"eti.date"=>$date
							);
							$this->edb->where($w2);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$this->db->order_by("eti.time_in","DESC");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
						}else{
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
						}
					}
				}else{
					#if(strtotime($check_bet_timein) <= strtotime($current_date) && strtotime($current_date) <= strtotime($add_oneday_timein)){
					#&& $r->time_in != "" && $r->time_out != ""
					if(strtotime($check_bet_timein) <= strtotime($current_date) && strtotime($current_date) <= strtotime($add_oneday_timein." -2 hours")
						&& $r->time_in != ""
					){
						
						/* CHECK TIME IN START */
						$wst = $this->check_workday_settings_start_time(date("l"),$payroll_group,$comp_id);
						if($wst != FALSE){
							// new start time
							$nwst = date("Y-m-d {$wst}");
							$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
							// if($check_diff_total_hours <= 1 && $check_diff_total_hours >= 0) $current_date = $nwst;
						}
						
						// insert time in log
						$val = array(
							"emp_id"=>$r_emp->emp_id,
							"comp_id"=>$r_emp->company_id,
							"date"=>$date,
							"time_in"=>$current_date
						);
						$insert = $this->db->insert("employee_time_in",$val);
						
						if($insert){
							$w2 = array(
								"a.payroll_cloud_id"=>$emp_no,
								"eti.date"=>$date
							);
							$this->edb->where($w2);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$this->db->order_by("eti.time_in","DESC");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
						}
					}else{
						// global where update data
						$where_update = array(
							"eti.emp_id"=>$emp_id,
							"eti.comp_id"=>$comp_id,
							"eti.employee_time_in_id"=>$r->employee_time_in_id
						);
						
						if($check_type == "time out"){
							
							// update time out value ============================================== >>> UPDATE TIME OUT VALUE
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							if($min_log < $get_diff){
								$update_val = array("time_out"=>$current_date);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);
							}
							
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							// update total hours and total hours required rest day
							$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
							
							// tardiness and undertime value
							$update_tardiness = $this->get_tardiness_val($emp_id,$comp_id,$r->time_in);
							$update_undertime = $this->get_undertime_val($emp_id,$comp_id,$r->time_in,$current_date);
							
							// check tardiness value
							$flag_tu = 0;
							
							$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r->time_in)), $emp_id);
							$get_total_hours_worked = ($hours_worked / 2) + .5;
							if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
								$update_tardiness = 0;
								$update_undertime = 0;
								$flag_tu = 1;
							}
							
							// required hours worked only
							$new_total_hours = $this->get_tot_hours($emp_id,$comp_id,$r->time_in,$current_date,$hours_worked);
							
							$update_timein_logs = array(
								"tardiness_min"=>$update_tardiness,
								"undertime_min"=>$update_undertime,
								"total_hours"=>$new_total_hours,
								"total_hours_required"=>$get_total_hours,
								"flag_tardiness_undertime"=>$flag_tu
							);
							$this->db->where($where_update);
							$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
							
							return ($q2) ? $q2->row() : FALSE ;
							
						}else{
							
							/* CHECK TIME IN START */
							$wst = $this->check_workday_settings_start_time(date("l"),$payroll_group,$comp_id);
							if($wst != FALSE){
								// new start time
								$nwst = date("Y-m-d {$wst}");
								$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
								// if($check_diff_total_hours <= 1 && $check_diff_total_hours >= 0) $current_date = $nwst;
							}
							
							// insert time in log ================================================================ >>>> INSERT NEW TIME IN LOG SAME DATE
							$insert = FALSE;
							$get_diff = (strtotime($current_date) - strtotime($r->time_out)) / 60;
							if($min_log < $get_diff){
								$val = array(
									"emp_id"=>$emp_id,
									"comp_id"=>$comp_id,
									"date"=>$date,
									"time_in"=>$current_date
								);
								$insert = $this->db->insert("employee_time_in",$val);	
							}
							
							if($insert){
								$w2 = array(
									"a.payroll_cloud_id"=>$emp_no,
									"eti.date"=>$date
								);
								$this->edb->where($w2);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$this->db->order_by("eti.time_in","DESC");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
				
								return ($q2) ? $q2->row() : FALSE ;
							}else{
								$this->edb->where($where_update);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
				
								return ($q2) ? $q2->row() : FALSE ;
							}
							
						}
					}
				}
			}
		}else{
			
			// check employee time in
			$current_date = date("Y-m-d H:i:s");
			$w = array(
				"a.payroll_cloud_id"=>$emp_no,
				"a.user_type_id"=>"5"
			);
			$this->edb->where($w);
			$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
			$this->db->order_by("eti.time_in","DESC");
			$q = $this->edb->get("employee_time_in AS eti",1,0);
			$r = $q->row();
		
			if($q->num_rows() == 0){
				
				/* CHECK TIME IN START */
				$wst = $this->check_workday_settings_start_time(date("l"),$payroll_group,$comp_id);
				if($wst != FALSE){
					// new start time
					$nwst = date("Y-m-d {$wst}");
					$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
					// if($check_diff_total_hours <= 1 && $check_diff_total_hours >= 0) $current_date = $nwst;
				}
				
				// insert time in log
				$val = array(
					"emp_id"=>$emp_id,
					"comp_id"=>$comp_id,
					"date"=>$date,
					"time_in"=>$current_date
				);
				$insert = $this->db->insert("employee_time_in",$val);
				
				if($insert){
					$w2 = array(
						"a.payroll_cloud_id"=>$emp_no,
						"eti.date"=>$date
					);
					$this->edb->where($w2);
					$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
					$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
					$q2 = $this->edb->get("employee_time_in AS eti",1,0);
	
					return ($q2) ? $q2->row() : FALSE ;
				}
			}else{

				// get date time in to date time out
				$workday = date("l",strtotime($r->time_in));
				$payroll_group_id = $r->payroll_group_id;
	
				// check rest day
				$check_rest_day = $this->check_rest_day($workday,$payroll_group_id,$comp_id);
				if($check_rest_day){
	
					// global where update data
					$where_update = array(
						"eti.emp_id"=>$emp_id,
						"eti.comp_id"=>$comp_id,
						"eti.employee_time_in_id"=>$r->employee_time_in_id
					);
					
					if($check_type == "time out"){
						
						// update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE FOR REST DAY
						$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
						if($min_log < $get_diff){
							$update_val = array("time_out"=>$current_date);
							$this->db->where($where_update);
							$update = $this->db->update("employee_time_in AS eti",$update_val);
						}
						
						$this->edb->where($where_update);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
		
						// update total hours and total hours required rest day
						$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
						$update_timein_logs = array(
							"tardiness_min"=>0,
							"undertime_min"=>0,
							"total_hours"=>$get_total_hours,
							"total_hours_required"=>$get_total_hours
						);
						$this->db->where($where_update);
						$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
						
						return ($q2) ? $q2->row() : FALSE ;
						exit;
						
					}else if($check_type == "time in"){
						
						/* CHECK TIME IN START */
						$wst = $this->check_workday_settings_start_time(date("l"),$payroll_group,$comp_id);
						if($wst != FALSE){
							// new start time
							$nwst = date("Y-m-d {$wst}");
							$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
							// if($check_diff_total_hours <= 1 && $check_diff_total_hours >= 0) $current_date = $nwst;
						}
						
						// insert time in value for rest day ============================================== >>> INSERT TIME IN VALUE FOR REST DAY
						$insert = FALSE;
						$get_diff = (strtotime($current_date) - strtotime($r->time_out)) / 60;
						if($min_log < $get_diff){
							$val = array(
								"emp_id"=>$emp_id,
								"comp_id"=>$comp_id,
								"date"=>$date,
								"time_in"=>$current_date
							);
							$insert = $this->db->insert("employee_time_in",$val);	
						}
						
						if($insert){
							$w2 = array(
								"a.payroll_cloud_id"=>$emp_no,
								"eti.date"=>$date
							);
							$this->edb->where($w2);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$this->db->order_by("eti.time_in","DESC");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
							exit;
						}else{
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
							exit;
						}
					}
				}
				
				// check workday settings
				$workday_settings_start_time = $this->check_workday_settings_start_time($workday,$payroll_group_id,$comp_id);
				$workday_settings_end_time = $this->check_workday_settings_end_time($workday,$payroll_group_id,$comp_id);
	
				if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
					
					// for night shift time in and time out value for working day
					$check_bet_timein = date("Y-m-d")." ".$workday_settings_start_time;
					$check_bet_timeout = date("Y-m-d",strtotime("+1 day"))." ".$workday_settings_end_time;
				}else{
					
					// for day shift time in and time out value for working day
					$check_bet_timein = date("Y-m-d")." ".$workday_settings_start_time;
					$check_bet_timeout = date("Y-m-d")." ".$workday_settings_end_time;
				}
				
				// check between date time in to date time out
				$add_oneday_timein = date("Y-m-d",strtotime("+1 day"))." ".$workday_settings_start_time;
	
				
				// CHECK HALFDAY 	>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
				$flag_halfday = 0;
				$check_breaktime = $this->check_breaktime($comp_id,$payroll_group);
				if($check_breaktime != FALSE){
					$b_st = $check_breaktime->start_time;
					$b_et = $check_breaktime->end_time;
					$now_date = date("Y-m-d H:i:s");
					$now_time = date("H:i:s");
					
					if(strtotime($check_bet_timein) <= strtotime($r->time_in) && strtotime($r->time_in) <= strtotime($add_oneday_timein)){
						//if(date("A",strtotime($b_et)) == date("A",strtotime($r->time_in)) || strtotime($b_et) <= strtotime(date("H:i:s",strtotime($r->time_in)))) $flag_halfday = 1;
						
						/* FOR DAY SHIFT */
						
						if(date("A",strtotime($workday_settings_start_time)) != "PM" && date("A",strtotime($workday_settings_end_time)) != "AM"){
							if(strtotime($b_et) <= strtotime(date("H:i:s",strtotime($r->time_in)))) $flag_halfday = 1;
						}
						
					}else{
						if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
							/*
							 	$new_date_timein = date("Y-m-d H:i:s",strtotime($check_bet_timein." -1 day"));
								$new_date_timeout = date("Y-m-d",strtotime($r->time_in))." ".date("H:i:s",strtotime($add_oneday_timein));
								if(strtotime($new_date_timein) <= strtotime($r->time_in) && strtotime($r->time_in) <= strtotime($new_date_timeout)) $flag_halfday = 1; // FOR NIGHT SHIFT
							*/
							
							/* FOR NIGHT SHIFT */
							
							if(date("A",strtotime($b_et)) == "AM"){
								$new_bt = date("Y-m-d",strtotime($r->time_in." +1 day"))." ".$b_et;
							}else{
								$new_bt = date("Y-m-d",strtotime($r->time_in))." ".$b_et;
							}
							
							if(strtotime($new_bt) <= strtotime($r->time_in)) $flag_halfday = 1;
						}
					}
				}else{
					//return FALSE;
				}
				// END HALFDAY 		>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
				
				// IF HALFDAY		>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
				if($flag_halfday == 1){
					
					$new_date_timein = date("Y-m-d H:i:s",strtotime($check_bet_timein." -1 day"));
					$new_date_timeout = date("Y-m-d",strtotime($r->time_in))." ".date("H:i:s",strtotime($add_oneday_timein));
					
					// global where update data
					$where_update = array(
						"eti.emp_id"=>$emp_id,
						"eti.comp_id"=>$comp_id,
						"eti.employee_time_in_id"=>$r->employee_time_in_id
					);
					
					// check last time in if previos date
					if(strtotime($check_bet_timein) <= strtotime($r->time_in) && strtotime($r->time_in) <= strtotime($add_oneday_timein." -2 hours") || strtotime($new_date_timein) <= strtotime($r->time_in) && strtotime($r->time_in) <= strtotime($new_date_timeout)){
						if($r->time_in != "" && $r->time_out == ""){
							// update time out value ================================================================ >>>> UPDATE TIME OUT VALUE
							$update = FALSE;
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							if($min_log < $get_diff){
								
								$tardiness = 0;
								$undertime = 0;
								$total_hours = 0;
								$total_hours_required = 0;
								
								// CHECK IF WORKING HOURS IF FLEXIBLE
								$check_hours_flexible = $this->check_hours_flex($comp_id,$payroll_group);
								
								// GET WORKDAY SCHEDULE
								$wd_start = $this->get_workday_sched_start($comp_id,$payroll_group);
								$wd_end = $this->get_end_time($comp_id,$payroll_group);
								
								if(date("A",strtotime($wd_start)) != "PM" && date("A",strtotime($wd_end)) != "AM"){ // DAY SHIFT TRAPPING 
									
									// FOR TARDINESS 										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
									if($check_hours_flexible != FALSE){
										$tardiness_a = (strtotime($b_st) - strtotime($check_hours_flexible)) / 60; // time start - breaktime end time (tardiness for start time)
									}else{
										$tardiness_a = (strtotime($b_st) - strtotime($workday_settings_start_time)) / 60; // time start - breaktime end time (tardiness for start time)
									}
									
									$tardiness_b = (strtotime($b_et) < strtotime(date("H:i:s",strtotime($r->time_in)))) ? (strtotime(date("H:i:s",strtotime($r->time_in))) - strtotime($b_et)) / 60 : 0 ; // tardiness for time in breaktime
									
									$tardiness = $tardiness_a + $tardiness_b;
									
									// GET END TIME FOR TIME OUT
									$get_end_time = $this->get_end_time($comp_id,$payroll_group);
									
								}else{ // NIGHT SHIFT TRAPPING
									$new_end_date = date("Y-m-d",strtotime(date("Y-m-d")." -1 day"))." ".$b_et;
									$now_date = date("Y-m-d H:i:s");
									
									$new_breaktime_start = date("Y-m-d")." ".$b_st;
									
									if($check_hours_flexible != FALSE){
										$tardiness_a = (strtotime($new_breaktime_start) - strtotime(date("Y-m-d",strtotime($new_date_timein))." ".$check_hours_flexible)) / 60; // time start - breaktime end time (tardiness for start time)
									}else{
										$tardiness_a = (strtotime($new_breaktime_start) - strtotime($new_date_timein)) / 60; // time start - breaktime end time (tardiness for start time)
									}
									
									$tardiness_b = (strtotime($b_et) < strtotime(date("H:i:s",strtotime($r->time_in)))) ? (strtotime(date("H:i:s",strtotime($r->time_in))) - strtotime($b_et)) / 60 : 0 ; // tardiness for time in breaktime
									
									$tardiness = $tardiness_a + $tardiness_b;
									
									// GET END TIME FOR TIME OUT
									$get_end_time = $this->get_end_time($comp_id,$payroll_group);
								}
								
								// FOR UNDERTIME 											>>>>>>>>>>>>>>>>>>>>>>>>>>>>
								if($get_end_time != FALSE){
									if(strtotime($now_time) < strtotime($get_end_time)) $undertime = (strtotime($get_end_time) - strtotime($now_time)) / 60;
								}else{
									return FALSE; // error found..
								}
								
								// FOR TOTAL HOURS										>>>>>>>>>>>>>>>>>>>>>>>>>>>>
								if($undertime == 0){
									$total_hours = 	(strtotime($get_end_time) - strtotime(date("H:i:s",strtotime($r->time_in)))) / 3600;
								}else{
									$total_hours = 	(strtotime($now_time) - strtotime(date("H:i:s",strtotime($r->time_in)))) / 3600;
								}
								
								// FOR TOTAL HOURS REQUIRED								>>>>>>>>>>>>>>>>>>>>>>>>>>>>
								$total_hours_required = (strtotime($now_time) - strtotime(date("H:i:s",strtotime($r->time_in)))) / 3600;
								
								$update_val = array(
									"time_out"=>$current_date,
									"tardiness_min"=>$tardiness,
									"undertime_min"=>$undertime,
									"total_hours"=>$total_hours,
									"total_hours_required"=>$total_hours_required,
								);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);
							}
							
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							$r2 = $q2->row();
							
							return ($q2) ? $q2->row() : FALSE ;
						}
					}else{
						
						/* CHECK TIME IN START */
						$wst = $this->check_workday_settings_start_time(date("l"),$payroll_group,$comp_id);
						if($wst != FALSE){
							// new start time
							$nwst = date("Y-m-d {$wst}");
							$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
							// if($check_diff_total_hours <= 1 && $check_diff_total_hours >= 0) $current_date = $nwst;
						}
						
						// insert time in log
						$val = array(
							"emp_id"=>$emp_id,
							"comp_id"=>$comp_id,
							"date"=>$date,
							"time_in"=>$current_date
						);
						$insert = $this->db->insert("employee_time_in",$val);
						
						if($insert){
							$w2 = array(
								"a.payroll_cloud_id"=>$emp_no,
								"eti.date"=>$date
							);
							$this->edb->where($w2);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
						}
					}
					
					$w2 = array(
						"a.payroll_cloud_id"=>$emp_no,
						"eti.date"=>$date
					);
					$this->edb->where($w2);
					$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
					$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
					$q2 = $this->edb->get("employee_time_in AS eti",1,0);
	
					return ($q2) ? $q2->row() : FALSE ;
					
					exit;
				}
				
				// IF WHOLEDAY
				if(strtotime($check_bet_timein) <= strtotime($r->time_in) && strtotime($r->time_in) <= strtotime($add_oneday_timein." -2 hours")){
					
					// global where update data
					$where_update = array(
						"eti.emp_id"=>$emp_id,
						"eti.comp_id"=>$comp_id,
						"eti.employee_time_in_id"=>$r->employee_time_in_id
					);
					
					if($check_type == "lunch out"){
						
						// update lunch out value ================================================================ >>>> UPDATE LUNCH OUT VALUE
						$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
						if($min_log < $get_diff){
							$update_val = array("lunch_out"=>$current_date);
							$this->db->where($where_update);
							$update = $this->db->update("employee_time_in AS eti",$update_val);
						}
						
						$this->edb->where($where_update);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
		
						return ($q2) ? $q2->row() : FALSE ;
						
					}else if($check_type == "lunch in"){
						
						// update lunch in value ================================================================ >>>> UPDATE LUNCH IN VALUE
						$get_diff = (strtotime($current_date) - strtotime($r->lunch_out)) / 60;
						if($min_log < $get_diff){
							$update_val = array("lunch_in"=>$current_date);
							$this->db->where($where_update);
							$update = $this->db->update("employee_time_in AS eti",$update_val);
						}
						
						$this->edb->where($where_update);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
		
						return ($q2) ? $q2->row() : FALSE ;
						
					}else if($check_type == "time out"){
						
						// update time out value ================================================================ >>>> UPDATE TIME OUT VALUE
						$update = FALSE;
						$get_diff = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
						if($min_log < $get_diff){
							$update_val = array("time_out"=>$current_date);
							$this->db->where($where_update);
							$update = $this->db->update("employee_time_in AS eti",$update_val);
						}
						
						$this->edb->where($where_update);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
						$r2 = $q2->row();
						
						if($update){
							
							// update flag tardiness and undertime
							$flag_tu = 0;
							
							// check no. of timein row
							$check_timein_row = $this->check_timein_row($emp_id, $comp_id, $r2->time_in);
							if($check_timein_row){
								// update tardiness
								$update_tardiness = 0;
								
								// update undertime
								$update_undertime = 0;
								
								// update total hours
								$update_total_hours = 0;
							}else{
								// update tardiness
								$update_tardiness = get_tardiness_import($emp_id, $comp_id, $r2->time_in, $r2->lunch_out, $r2->lunch_in);
		
								// update undertime
								$update_undertime = get_undertime_import($emp_id, $comp_id, $r2->time_in, $r2->time_out, $r2->lunch_out, $r2->lunch_in);
								
								// update total hours
								$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id);
								$update_total_hours = get_tot_hours($emp_id, $comp_id, $r2->time_in, $r2->lunch_out, $r2->lunch_in, $r2->time_out, $hours_worked);
								
								// check tardiness value
								$get_total_hours_worked = ($hours_worked / 2) + .5;
								if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0){
									$update_tardiness = 0;
									$update_undertime = 0;
									$flag_tu = 1;
								}
							}
							
							// update total hours required
							$update_total_hours_required = get_tot_hours_limit($emp_id, $comp_id, $r2->time_in, $r2->lunch_out, $r2->lunch_in, $r2->time_out);
							
							// update employee time in logs
							$update_timein_logs = array(
								"tardiness_min"=>$update_tardiness,
								"undertime_min"=>$update_undertime,
								"total_hours"=>$update_total_hours,
								"total_hours_required"=>$update_total_hours_required,
								"flag_tardiness_undertime"=>$flag_tu
							);
							$this->db->where($where_update);
							$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
						}
						
						return ($q2) ? $q2->row() : FALSE ;
						
					}else{
						
						/* CHECK TIME IN START */
						$wst = $this->check_workday_settings_start_time(date("l"),$payroll_group,$comp_id);
						if($wst != FALSE){
							// new start time
							$nwst = date("Y-m-d {$wst}");
							$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
							// if($check_diff_total_hours <= 1 && $check_diff_total_hours >= 0) $current_date = $nwst;
						}
						
						// insert time in log ================================================================ >>>> INSERT NEW TIME IN LOG SAME DATE
						$insert = FALSE;
						$get_diff = (strtotime($current_date) - strtotime($r->time_out)) / 60;
						if($min_log < $get_diff){
							$val = array(
								"emp_id"=>$emp_id,
								"comp_id"=>$comp_id,
								"date"=>$date,
								"time_in"=>$current_date
							);
							$insert = $this->db->insert("employee_time_in",$val);	
						}
						
						if($insert){
							$w2 = array(
								"a.payroll_cloud_id"=>$emp_no,
								"eti.date"=>$date
							);
							$this->edb->where($w2);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$this->db->order_by("eti.time_in","DESC");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
						}else{
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
						}
					}
				}else{
					#if(strtotime($check_bet_timein) <= strtotime($current_date) && strtotime($current_date) <= strtotime($add_oneday_timein)){
					#&& $r->time_in != "" && $r->lunch_out != "" && $r->lunch_in != "" && $r->time_out != ""
					if(strtotime($check_bet_timein) <= strtotime($current_date) && strtotime($current_date) <= strtotime($add_oneday_timein." -2 hours")
						&& $r->time_in != ""
					){
						
						/* CHECK TIME IN START */
						$wst = $this->check_workday_settings_start_time(date("l"),$payroll_group,$comp_id);
						if($wst != FALSE){
							// new start time
							$nwst = date("Y-m-d {$wst}");
							$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
							// if($check_diff_total_hours <= 1 && $check_diff_total_hours >= 0) $current_date = $nwst;
						}
						
						// insert time in log
						$val = array(
							"emp_id"=>$r_emp->emp_id,
							"comp_id"=>$r_emp->company_id,
							"date"=>$date,
							"time_in"=>$current_date
						);
						$insert = $this->db->insert("employee_time_in",$val);
						
						if($insert){
							$w2 = array(
								"a.payroll_cloud_id"=>$emp_no,
								"eti.date"=>$date
							);
							$this->edb->where($w2);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$this->db->order_by("eti.time_in","DESC");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
						}	
					}else{
						// global where update data
						$where_update = array(
							"eti.emp_id"=>$emp_id,
							"eti.comp_id"=>$comp_id,
							"eti.employee_time_in_id"=>$r->employee_time_in_id
						);
						
						if($check_type == "lunch out"){
							
							// update lunch out value ================================================================ >>>> UPDATE LUNCH OUT VALUE
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							if($min_log < $get_diff){
								$update_val = array("lunch_out"=>$current_date);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);
							}
							
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
							
						}else if($check_type == "lunch in"){
							
							// update lunch in value ================================================================ >>>> UPDATE LUNCH IN VALUE
							$get_diff = (strtotime($current_date) - strtotime($r->lunch_out)) / 60;
							if($min_log < $get_diff){
								$update_val = array("lunch_in"=>$current_date);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);
							}
							
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
							
						}else if($check_type == "time out"){
							
							// update time out value ================================================================ >>>> UPDATE TIME OUT VALUE
							$update = FALSE;
							$get_diff = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
							if($min_log < $get_diff){
								$update_val = array("time_out"=>$current_date);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);
							}
							
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							$r2 = $q2->row();
							
							if($update){
								
								// update flag tardiness and undertime
								$flag_tu = 0;
								
								// check no. of timein row
								$check_timein_row = $this->check_timein_row($emp_id, $comp_id, $r2->time_in);
								if($check_timein_row){
									// update tardiness
									$update_tardiness = 0;
									
									// update undertime
									$update_undertime = 0;
									
									// update total hours
									$update_total_hours = 0;
									
								}else{
									// update tardiness
									$update_tardiness = get_tardiness_import($emp_id, $comp_id, $r2->time_in, $r2->lunch_out, $r2->lunch_in);
			
									// update undertime
									$update_undertime = get_undertime_import($emp_id, $comp_id, $r2->time_in, $r2->time_out, $r2->lunch_out, $r2->lunch_in);
									
									// update total hours
									$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id);
									$update_total_hours = get_tot_hours($emp_id, $comp_id, $r2->time_in, $r2->lunch_out, $r2->lunch_in, $r2->time_out, $hours_worked);	
								
									// check tardiness value
									$get_total_hours_worked = ($hours_worked / 2) + .5;
									if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0){
										$update_tardiness = 0;
										$update_undertime = 0;
										$flag_tu = 1;
									}
								}
								
								// update total hours required
								$update_total_hours_required = get_tot_hours_limit($emp_id, $comp_id, $r2->time_in, $r2->lunch_out, $r2->lunch_in, $r2->time_out);
								
								// update employee time in logs
								$update_timein_logs = array(
									"tardiness_min"=>$update_tardiness,
									"undertime_min"=>$update_undertime,
									"total_hours"=>$update_total_hours,
									"total_hours_required"=>$update_total_hours_required,
									"flag_tardiness_undertime"=>$flag_tu
								);
								$this->db->where($where_update);
								$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
							}
							
							return ($q2) ? $q2->row() : FALSE ;
							
						}else{
							
							/* CHECK TIME IN START */
							$wst = $this->check_workday_settings_start_time(date("l"),$payroll_group,$comp_id);
							if($wst != FALSE){
								// new start time
								$nwst = date("Y-m-d {$wst}");
								$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
								// if($check_diff_total_hours <= 1 && $check_diff_total_hours >= 0) $current_date = $nwst;
							}
							
							// insert time in log ================================================================ >>>> INSERT NEW TIME IN LOG SAME DATE
							$insert = FALSE;
							$get_diff = (strtotime($current_date) - strtotime($r->time_out)) / 60;
							if($min_log < $get_diff){
								$val = array(
									"emp_id"=>$emp_id,
									"comp_id"=>$comp_id,
									"date"=>$date,
									"time_in"=>$current_date
								);
								$insert = $this->db->insert("employee_time_in",$val);	
							}
							
							if($insert){
								$w2 = array(
									"a.payroll_cloud_id"=>$emp_no,
									"eti.date"=>$date
								);
								$this->edb->where($w2);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$this->db->order_by("eti.time_in","DESC");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
				
								return ($q2) ? $q2->row() : FALSE ;
							}else{
								$this->edb->where($where_update);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
				
								return ($q2) ? $q2->row() : FALSE ;
							}
						}
					}
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
	public function check_workday_settings_start_time($workday,$payroll_group_id,$company_id,$time_in = ""){
		// check uniform working days
		$w = array(
			"payroll_group_id"=>$payroll_group_id,
			"company_id"=>$company_id,
			"days_of_work"=>$workday,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("regular_schedule");
		$row = $q->row();
		if($row){
			return $row->work_start_time;
		}else{
			// workshift
			$w2 = array(
				"payroll_group_id"=>$payroll_group_id,
				"company_id"=>$company_id
			);
			$this->db->where($w2);
			$q2 = $this->db->get("split_schedule");
			$row2 = $q2->row();
			if($row2){
				return $this->get_starttime($row2->split_schedule_id,$time_in);
			}else{
				return false;
			}
		}
	}
	
	/**
	 * Get the nearest time for split schedule_time_in
	 * @return unknown
	 */
	public function get_starttime($split_schedule_id,$time_in){
	
		$this->db->where('split_schedule_id',$split_schedule_id);
		$q2 = $this->db->get("schedule_blocks");
		$result = $q2->result();
		$time_in = date('H:i:s',strtotime($time_in));
		$arr = array();
		// p($result);
		foreach($result as $row):
		$start_time = date('H:i:s',strtotime($row->start_time));
		$end_time = date('H:i:s', strtotime($row->end_time));
		if($time_in >= $start_time && $time_in <= $end_time):
		return $row->start_time;
		else:
		$arr[] = $start_time;
		endif;
		endforeach;
		//p($arr);
		foreach($arr as $key => $row2):
		if($time_in <= $row2){
			return $row2;
		}
		endforeach;
	
		return false;
	}
	
	
	/**
	 * Check Workday Settings for end time
	 * @param unknown_type $workday
	 * @param unknown_type $payroll_group_id
	 * @param unknown_type $company_id
	 */
	public function check_workday_settings_end_time($workday,$payroll_group_id,$company_id,$time_in =""){
		// check uniform working days
		$w = array(
			"payroll_group_id"=>$payroll_group_id,
			"company_id"=>$company_id,
			"days_of_work"=>$workday,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("regular_schedule");
		$row = $q->row();
		if($row){
			return $row->work_end_time;
		}else{
			// workshift
			$w2 = array(
				"payroll_group_id"=>$payroll_group_id,
				"company_id"=>$company_id
			);
			$this->db->where($w2);
			$q2 = $this->db->get("split_schedule");
			$row2 = $q2->row();
			if($row2){
				return $this->get_endtime($row2->split_schedule_id, $time_in);
			}else{
				return false;
			}
		}
	}
	
	// adding end time - aldrin
	public function get_endtime($split_schedule_id,$time_in){
		$this->db->where('split_schedule_id',$split_schedule_id);
		$q2 = $this->db->get("schedule_blocks");
		$result = $q2->result();
		$time_in = date('H:i:s',strtotime($time_in));
		$arr = array();
		foreach($result as $row):
		$start_time = date('H:i:s',strtotime($row->start_time));
		$end_time = date('H:i:s', strtotime($row->end_time));
		if($time_in >= $start_time && $time_in <= $end_time):
		return $row->end_time;
		else:
		$arr[] = $end_time;
		endif;
		endforeach;
	
		foreach($arr as $key => $row2):
		if($time_in <= $row2){
			return $row2;
		}
		endforeach;
	
		return false;
	}
	/**
	 * Get Hours Worked for workday
	 * @param unknown_type $workday
	 * @param unknown_type $emp_id
	 */
	public function get_hours_worked($workday, $emp_id){
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
				"days_of_work"=>$workday_val,
				"company_id"=>$comp_id,
				"status"=>"Active"
			);
			$this->db->where($w2);
			$q2 = $this->db->get("regular_schedule");
			$r2 = $q2->row();
			if($q2->num_rows() > 0){
				// for uniform working days table
				return $r2->total_work_hours;
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
					$qw = $this->db->get("split_schedule");
					$rq = $qw->row();
					if($qw->num_rows() > 0){
						// for workshift table
						return $rq->total_work_hours;
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
	 * Check TimeIn Row
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $time_in
	 */
	public function check_timein_row($emp_id, $comp_id, $time_in){
		$w = array(
			"date"=>date("Y-m-d",strtotime($time_in)),
			"emp_id"=>$emp_id,
			"comp_id"=>$comp_id
		);
		$this->db->where($w);
		$q = $this->db->get("employee_time_in");
		return ($q->num_rows() >= 2) ? TRUE : FALSE ;
	}
	
	/**
	 * Get Tardiness
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $date
	 * @param unknown_type $time_in
	 */
	public function get_tardiness_val($emp_id,$comp_id,$time_in_import){
		
		$CI =& get_instance();
		
		$day = date("l",strtotime($time_in_import));
		
		$where = array(
			"emp_id" => $emp_id
		);
		$CI->db->where($where);
		$sql_payroll_info = $CI->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		$payroll_group_id = $row_payroll_info->payroll_group_id;
		
		if($payroll_group_id == "" || $payroll_group_id == NULL){
			return 0;
		}else{
			// check if rest day
			$rest_day = check_holiday_val($time_in_import,$emp_id,$comp_id,$payroll_group_id);
			if($rest_day){
				$min_late = 0;
				$min_late_breaktime = 0;
			}else{
				// rest day
				$rest_day = $CI->db->query("
					SELECT *FROM rest_day
					WHERE company_id = '{$comp_id}'
					AND rest_day = '{$day}'
					AND payroll_group_id = '{$payroll_group_id}'
				");
				
				if($rest_day->num_rows() == 0){
					// uniform working days settings
					$sql = $CI->db->query("
						SELECT *FROM regular_schedule
						WHERE payroll_group_id = '{$payroll_group_id}'
						AND company_id = '{$comp_id}'
					");
					$row = $sql->row();
					
					if($sql->num_rows() > 0 && $row->allow_flexible_workhours != 0){
						$payroll_sched_timein = $row->latest_time_in_allowed;
					}else{				
						// uniform working days
						$sql_uniform_working_days = $CI->db->query("
							SELECT *FROM regular_schedule
							WHERE payroll_group_id = '{$payroll_group_id}'
							AND company_id = '{$comp_id}'
							AND days_of_work = '{$day}'
						");
						
						if($sql_uniform_working_days->num_rows() > 0){
							$row_uniform_working_days = $sql_uniform_working_days->row();
							$sql_uniform_working_days->free_result();
							$payroll_sched_timein = $row_uniform_working_days->work_start_time;
						}else{
							// flexible working days
							$sql_flexible_days = $CI->db->query("
								SELECT *FROM flexible_hours
								WHERE payroll_group_id = '{$payroll_group_id}'
								AND company_id = '{$comp_id}'
							");
							
							if($sql_flexible_days->num_rows() > 0){
								$row_flexible_days = $sql_flexible_days->row();
								$sql_flexible_days->free_result();
								$payroll_sched_timein = $row_flexible_days->latest_time_in_allowed;
							}else{
								// workshift working days
								$sql_workshift = $CI->db->query("
									SELECT *FROM split_schedule
									WHERE payroll_group_id = '{$payroll_group_id}'
									AND company_id = '{$comp_id}'
								");
								
								if($sql_workshift->num_rows() > 0){
									$row_workshift = $sql_workshift->row();
									$sql_workshift->free_result();
									$payroll_sched_timein = $this->get_starttime($row_workshift->split_schedule_id, $time_in) ;
								}else{
									$payroll_sched_timein = "00:00:00";
								}
							}
						}
					}
				}else{
					$payroll_sched_timein = "00:00:00";
				}
				
				if($payroll_sched_timein != "00:00:00" || $payroll_sched_timein != "" || $payroll_sched_timein != NULL){
					
					$time_in_original = $time_in_import;
					$time_in_import = date("H:i:s",strtotime($time_in_import));
				
					// for tardiness time in
					$time_x=(strtotime($time_in_import) - strtotime($payroll_sched_timein)) / 3600;

					// check time in and allowed time in
					$ti_val = date("A",strtotime($time_in_import));
					$at_val = date("A",strtotime($payroll_sched_timein));
					
					if($ti_val == "PM" && $at_val == "AM"){
						// for tardiness time in
						$time_date = date("Y-m-d",strtotime($time_in_original));
						$add_oneday_timein = date("Y-m-d",strtotime($time_date."+ 1 day"));
						$new_allowed_time = $add_oneday_timein." ".$payroll_sched_timein;
						
						$time_x=(strtotime($time_in_original) - strtotime($new_allowed_time)) / 3600;
					}
					
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
			}
			
			$min_late = ($min_late < 0) ? 0 : $min_late ;
			
			return $min_late;	
		}
	}
	
	/**
	 * Get Undertime for import
	 * @param unknown_type $emp_id
	 * @param unknown_type $comp_id
	 * @param unknown_type $date
	 * @param unknown_type $time_in
	 */
	public function get_undertime_val($emp_id,$comp_id,$date_timein,$date_timeout){
		$CI =& get_instance();
		
		$day = date("l",strtotime($date_timein));
		$start_time = "";
		
		$where = array(
			"emp_id" => $emp_id
		);
		$CI->db->where($where);
		$sql_payroll_info = $CI->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		$payroll_group_id = $row_payroll_info->payroll_group_id;

		if($payroll_group_id == "" || $payroll_group_id == NULL){
			return 0;
		}else{
			// check rest day
			$rest_day = check_holiday_val($date_timein,$emp_id,$comp_id,$payroll_group_id);
			if($rest_day){
				$under_min_val = 0;
			}else{
				// rest day
				$rest_day = $CI->db->query("
					SELECT *FROM rest_day
					WHERE company_id = '{$comp_id}'
					AND rest_day = '{$day}'
					AND payroll_group_id = '{$payroll_group_id}'
				");
				
				if($rest_day->num_rows() == 0){
					// uniform working days
					$sql_uniform_working_days = $CI->db->query("
						SELECT *FROM regular_schedule
						WHERE payroll_group_id = '{$payroll_group_id}'
						AND company_id = '{$comp_id}'
						AND days_of_work = '{$day}'
					");
					
					if($sql_uniform_working_days->num_rows() > 0){
						$row_uniform_working_days = $sql_uniform_working_days->row();
						$start_time = $row_uniform_working_days->work_start_time;
						$undertime_min = $row_uniform_working_days->work_end_time;
						$working_hours = $row_uniform_working_days->total_work_hours;
					}else{
						// flexible working days
						$sql_flexible_days = $CI->db->query("
							SELECT *FROM flexible_hours
							WHERE payroll_group_id = '{$payroll_group_id}'
							AND company_id = '{$comp_id}'
						");
						
						if($sql_flexible_days->num_rows() > 0){
							
							$flexible_compute_time = $CI->db->query("
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
								$sql_flexible_working_days = $CI->db->query("
									SELECT *FROM flexible_hours
									WHERE payroll_group_id = '{$payroll_group_id}'
									AND company_id = '{$comp_id}'
								");
								
								if($sql_flexible_working_days->num_rows() > 0){
									$row_flexible_working_days = $sql_flexible_working_days->row();
									$sql_flexible_working_days->free_result();
									$total_hours_for_the_day = $row_flexible_working_days->total_hours_for_the_day;
									$end_time = date("H:i:s",strtotime($flexible_work_end) + 60 * 60 * $total_hours_for_the_day);
									
									$start_time = $row_flexible_working_days->latest_time_in_allowed;
									$undertime_min =  $end_time;
									$working_hours = $row_flexible_working_days->total_hours_for_the_day;
								}else{
									$undertime_min =  "00:00:00";
								}
							}else{
								$undertime_min =  "00:00:00";
							}
							
						}else{
							// workshift working days
							$sql_workshift = $CI->db->query("
								SELECT *FROM split_schedule
								WHERE payroll_group_id = '{$payroll_group_id}'
								AND company_id = '{$comp_id}'
							");
							
							if($sql_workshift->num_rows() > 0){
								$row_workshift = $sql_workshift->row();
								$sql_workshift->free_result();
								$undertime_min = $this->get_endtime($row_workshift->split_schedule_id, $time_in);
								$working_hours = $row_workshift->total_work_hours;
							}else{
								$undertime_min =  "00:00:00";
							}
						}
					}
				}else{
					$undertime_min = "00:00:00";
					$working_hours = 0;
				}
				
				$date_timeout_sec = date("H:i:s",strtotime($date_timeout));
				
				if($start_time == ""){
					return 0;
				}
				
				// check PM and AM
				$check_endtime = date("A",strtotime($undertime_min));
				$check_timein = date("A",strtotime($date_timein));
				$check_timout = date("A",strtotime($date_timeout_sec));
				
				// callcenter trapping
				if($check_endtime == "AM" && $check_timout == "PM" && $check_timein == "PM"){
					$time_out_date = date("Y-m-d",strtotime($date_timeout_sec."+1 day"));
					$new_undertime_min = $time_out_date." ".$undertime_min;
					$under_min_val = (strtotime($new_undertime_min) - strtotime($date_timeout)) / 60;
				}else{
					if(strtotime($date_timeout_sec) <= strtotime($undertime_min)){
						$check_tardiness_import = check_tardiness_import($emp_id,$comp_id,$date_timein);
						if($check_tardiness_import == 0){
							if(strtotime($undertime_min) <= strtotime($date_timeout_sec)){							
								$under_min_val = (strtotime($undertime_min) - strtotime($date_timeout_sec)) / 60;
							}else{
								$breaktime_hours = add_breaktime($comp_id,$payroll_group_id,$date_timein);
								$working_hours = $working_hours + $breaktime_hours;
								$date_timin_sec = date('H:i:s', strtotime($date_timein));
								
								$new_date_timein = (strtotime($start_time) <= strtotime($date_timin_sec)) ? $date_timein : $start_time ;
								$new_timeout_sec = date('H:i:s', strtotime($new_date_timein . ' + '.$working_hours.' hour'));
								$under_min_val = (strtotime($new_timeout_sec) - strtotime($date_timeout_sec)) / 60;
							}
						}else{
							$under_min_val = (strtotime($undertime_min) - strtotime($date_timeout_sec)) / 60;
						}
					}else{
						$under_min_val = 0;
					}
				}
			}
			
			// check total hours for workday
			$get_hours_worked_to_mins = $this->get_hours_worked($day, $emp_id) * 60;
			
			if($get_hours_worked_to_mins < $under_min_val) return 0;
			
			return ($under_min_val < 0) ? 0 : $under_min_val ;	
		}
	}
	
	/**
	 * Get Total Hours Worked
	 * @param unknown_type $time_in
	 * @param unknown_type $time_out
	 */
	public function get_tot_hours($emp_id,$comp_id,$time_in,$time_out,$hours_worked){
		$CI =& get_instance();
		
		$where = array(
			"emp_id" => $emp_id
		);
		$CI->db->where($where);
		$sql_payroll_info = $CI->db->get("employee_payroll_information");
		$row_payroll_info = $sql_payroll_info->row();
		$payroll_group_id = $row_payroll_info->payroll_group_id;
		
		if($payroll_group_id == "" || $payroll_group_id == NULL){
			return 0;
		}else{
			// check if rest day
			$rest_day = check_holiday_val($time_in,$emp_id,$comp_id,$payroll_group_id);
			if($rest_day){
				$total = (strtotime($time_out) - strtotime($time_in)) / 3600;
			}else{
				
				// check time out for uniform working days
				$where_uw = array(
					"company_id"=>$comp_id,
					"payroll_group_id"=>$payroll_group_id,
					"days_of_work"=>date("l",strtotime($time_in))	
				);
				$CI->db->where($where_uw);
				$sql_uw = $CI->db->get("regular_schedule");
				
				$row_uw = $sql_uw->row();
				if($sql_uw->num_rows() > 0){
					
					$time_out_sec = date("H:i:s",strtotime($time_out));
					$time_out_date = date("Y-m-d",strtotime($time_out));
					$new_work_end_time = $time_out_date." ".$row_uw->work_end_time;
					if(strtotime($new_work_end_time) <= strtotime($time_out)){
						
						$time_in_sec = date("H:i:s",strtotime($time_in));
						$total_hours_worked = (strtotime($new_work_end_time) - strtotime($time_in)) / 3600;
						
					}else{
						$total_hours_worked = (strtotime($time_out) - strtotime($time_in)) / 3600;
					}
					
				}else{
					// check time out for workshift
					$where_w = array(
						"company_id"=>$comp_id,
						"payroll_group_id"=>$payroll_group_id
					);
					$CI->db->where($where_w);
					$sql_w = $CI->db->get("split_schedule");
					$row_w = $sql_w->row();
					if($sql_w->num_rows() > 0){
						
						$time_out_sec = date("H:i:s",strtotime($time_out)); //end
						$time_out_date = date("Y-m-d",strtotime($time_out));
						$new_work_end_time = $time_out_date." ".$this->get_endtime($row_w->split_schedule_id, $time_in);
						
						if(strtotime($new_work_end_time) <= strtotime($time_out)){
										
							$time_in_sec = date("H:i:s",strtotime($time_in));
							$total_hours_worked = (strtotime($new_work_end_time) - strtotime($time_in)) / 3600;
						}else{
							$total_hours_worked = (strtotime($time_out) - strtotime($time_in)) / 3600;
						}
						
					}else{
						// check time out for flexible hours
						$where_f = array(
							"company_id"=>$comp_id,
							"payroll_group_id"=>$payroll_group_id
						);
						$CI->db->where($where_f);
						$sql_f = $CI->db->get("flexible_hours");
						$row_f = $sql_f->row();
						if($sql_f->num_rows() > 0){
							$total_hours_worked = (strtotime($time_in . ' + '.$row_f->total_hours_for_the_day.' hour') - strtotime($time_in)) / 3600;
						}else{
							$total_hours_worked = 0;
						}
					}
				}
				
				$total = $total_hours_worked;
				if($total > $hours_worked){
					$total = $hours_worked;
				}
			}
			
			return ($total < 0) ? round(0,2) : round($total,2) ;
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
			"company_id"=>$comp_id,
			"workday"=>date("l")
		);
		$this->db->where($w);
		$q = $this->db->get("break_time");
		return ($q->num_rows() > 0) ? $q->row() : FALSE ;
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
		$q = $this->db->get("uniform_working_day_settings");
		$r = $q->row();
		if($q->num_rows() > 0){
			return ($r->allow_flexible_workhours == 1) ? $r->latest_time_in_allowed : FALSE ;
		}else{
			return FALSE;
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
		$q = $this->db->get("uniform_working_day");
		$r = $q->row();
		if($q->num_rows() > 0){
			return $r->work_end_time;
		}else{
			$w2 = array(
				"payroll_group_id"=>$payroll_group,
				"company_id"=>$comp_id
			);
			$this->db->where($w2);
			$q2 = $this->db->get("workshift");
			$r2 = $q2->row();
			if($q2->num_rows() > 0){
				return $r2->end_time;
			}else{
				return FALSE;
			}
		}
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
			"working_day"=>date("l"),
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("uniform_working_day");
		$r = $q->row();
		if($q->num_rows() > 0){
			return $r->work_start_time;
		}else{
			$w2 = array(
				"payroll_group_id"=>$payroll_group,
				"company_id"=>$comp_id
			);
			$this->db->where($w2);
			$q2 = $this->db->get("workshift");
			$r2 = $q2->row();
			if($q2->num_rows() > 0){
				return $r2->start_time;
			}else{
				return FALSE;
			}
		}
	}
	
	/**
	 * Check Company ID
	 * @param unknown_type $emp_id
	 */
	public function check_company_id($emp_id){
		$w = array(
			"e.emp_id"=>$emp_id,
		);
		$this->db->where("e.status","Active");
		$this->edb->where($w);
		$this->edb->join("company_approvers AS ca","e.account_id = ca.account_id","INNER");
		$this->edb->join("user_roles AS ur","ca.users_roles_id = ur.users_roles_id","INNER");
		$this->edb->join("privilege AS p","ur.users_roles_id = p.users_roles_id","INNER");
		$q = $this->edb->get("employee AS e");
		
		$r = $q->row();
		return ($r) ? $r->company_id : FALSE ;
	}
	
	/**
	 * Check Employee Company ID
	 * @param unknown_type $emp_no
	 */
	public function check_emp_compid($emp_no){
		$w = array(
			"a.payroll_cloud_id"=>$emp_no,
		);
		$this->edb->where($w);
		$this->db->where("e.status","Active");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$q = $this->edb->get("employee AS e");
		$r = $q->row();
		return ($r) ? $r->company_id : FALSE ;
	}
	
	/**
	 * Company Logo
	 * @param unknown_type $id
	 */
	public function company_logo($id){
		
		$w = array(
			"accounts.account_id"=>$id
		);
		$this->edb->where($w);
		$this->edb->join("company_approvers","accounts.account_id = company_approvers.account_id","INNER");
		$this->edb->join("user_roles","company_approvers.users_roles_id = user_roles.users_roles_id","INNER");
		$this->edb->join("privilege","user_roles.users_roles_id = privilege.users_roles_id","INNER");
		$this->edb->join("company","company.company_id = privilege.company_id","INNER");
		$q = $this->edb->get("accounts");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
		
	}
	
	/**
	 * Check Employee ID
	 * @param unknown_type $emp_no
	 */
	public function check_employee_id($emp_no){
		$w = array(
			"a.payroll_cloud_id"=>$emp_no
		);
		$this->edb->where($w);
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$q = $this->edb->get("employee AS e");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	
	/**
	 * Check Employee ID
	 * @param unknown_type $emp_no
	 */
	public function new_check_employee_id($emp_no,$comp_id){
		$w = array(
				"a.payroll_cloud_id"=>$emp_no
		);
		$this->edb->where($w);
		$this->edb->where("e.company_id",$comp_id);
		$this->edb->join("employee AS e","a.account_id = e.account_id","LEFT");
		$q = $this->edb->get("accounts AS a");
		$r = $q->row();
	
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Time In Log
	 * @param unknown_type $emp_id
	 * @param unknown_type $date_capture
	 */
	public function check_timein_log($emp_id,$date_capture){
		$this->db->where("`time_in` = '{$date_capture}' OR `lunch_out` = '{$date_capture}' OR `lunch_in` = '{$date_capture}' OR `time_out` = '{$date_capture}'");
		$w = array("emp_id"=>$emp_id);
		$this->db->where($w);
		$q = $this->db->get("employee_time_in");
		$r = $q->row();
		return ($r) ? TRUE : FALSE ;
	}
	
	
	/**
	 * Check Clock Guard Settings
	 * @param unknown_type $company_id
	 */
	public function check_clock_guard_settings($company_id){
		$w = array(
			"company_id"=>$company_id
		);
		$this->db->where($w);
		$q = $this->db->get("clock_guard_settings");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Image Log
	 * @param unknown_type $emp_id
	 * @param unknown_type $time_in
	 */
	public function check_image_log($emp_id,$time_in){
		$w = array(
			"emp_id"=>$emp_id,
			"date_capture"=>$time_in
		);
		$this->db->where($w);
		$q = $this->db->get("employee_image_logs");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Check Employee Work Schedule ID
	 * @param unknown_type $emp_no
	 * @param unknown_type $check_company_id
	 * @param unknown_type $currentdate
	 */
	public function emp_work_schedule($emp_no,$check_company_id,$currentdate){
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
			"a.payroll_cloud_id"=>$emp_no,
			"ess.company_id"=>$check_company_id,
			"e.status"=>"Active",
			"ess.status"=>"Active"
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
					"a.payroll_cloud_id"=>$emp_no
			);
			$this->edb->where($w);
			$this->edb->join('payroll_group AS pg','pg.payroll_group_id = epi.payroll_group_id','INNER');
			$this->edb->join('employee AS e','epi.emp_id = e.emp_id','LEFT');
			$this->edb->join('accounts AS a','e.account_id = a.account_id','LEFT');
			$q_pg = $this->edb->get('employee_payroll_information AS epi');
			$r_pg = $q_pg->row();
			return ($r_pg) ? $r_pg->work_schedule_id : FALSE;
			
		}
		
	}
	
	/**
	 * Check Employee Work Schedule ID
	 * @param unknown_type $emp_no
	 * @param unknown_type $check_company_id
	 * @param unknown_type $currentdate
	 */
	public function emp_work_schedule2($emp_no,$check_company_id,$currentdate){
		// employee group id
		$s = array(
			"ess.work_schedule_id",

		);
		$w_date = array(
			"ess.valid_from <="		=>	$currentdate,
			"ess.until >="			=>	$currentdate
		);
		$this->db->where($w_date);
		
		$w_emp = array(
			"a.payroll_cloud_id"=>$emp_no,
			"ess.company_id"=>$check_company_id,
			"ess.payroll_group_id" => 0,
			"e.status"=>"Active",
			"ess.status"=>"Active"
		);
		$this->edb->select($s);
		$this->edb->where($w_emp);
		$this->edb->join("employee AS e","e.emp_id = ess.emp_id","LEFT");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
		$q_emp = $this->edb->get("employee_shifts_schedule AS ess");
		$r_emp = $q_emp->row();
		
		return ($r_emp) ? $r_emp->work_schedule_id : FALSE ;
	}
	
	
	/**
	 * Work Schedule ID
	 * @param unknown_type $company_id
	 * @param unknown_type $emp_id
	 * @param unknown_type $date
	 */
	public function work_schedule_id($company_id,$emp_id,$date=NULL){
		
		$w_date = array(
			"valid_from <="		=>	$date,
			"until >="			=>	$date
		);
		if($date != NULL) $this->db->where($w_date);
		
		$w = array(
			"emp_id"=>$emp_id,
			"company_id"=>$company_id,
			"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("employee_shifts_schedule");
		$r = $q->row();
		
		return ($r) ? $r : FALSE ;
	}

}