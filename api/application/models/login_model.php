<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	/**
	 * Employee Login Model
	 *
	 * @category Model
	 * @version 1.0
	 * @author Jonathan Bangga <jonathanbangga@gmail.com>
	 */
	class Login_model extends CI_Model {
	
		public function payroll_group_id($emp_no,$check_company_id){
			// employee group id
			$s = array(
				"epi.payroll_group_id"
			);
			$w_emp = array(
				"a.payroll_cloud_id"=>$emp_no,
				"epi.company_id"=>$check_company_id,
				"e.status"=>"Active",
				"epi.status"=>"Active"
			);
			$this->edb->select($s);
			$this->edb->where($w_emp);
			$this->edb->join("employee AS e","e.emp_id = epi.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$q_emp = $this->edb->get("employee_payroll_information AS epi");
			$r_emp = $q_emp->row();			
			return ($r_emp) ? $r_emp->payroll_group_id : FALSE ;
		}
		
		public function payroll_group_info($emp_no,$check_company_id){
			// employee group id
		
			$w_emp = array(
					"a.payroll_cloud_id"=>$emp_no,
					"epi.company_id"=>$check_company_id,
					"e.status"=>"Active",
					"epi.status"=>"Active"
			);
		
			$this->edb->where($w_emp);
			$this->edb->join("employee AS e","e.emp_id = epi.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$q_emp = $this->edb->get("employee_payroll_information AS epi");
			$r_emp = $q_emp->row();
			return ($r_emp) ? $r_emp : FALSE ;
		}
		
		/**
		 *
		 * Use to sync the data.
		 * @param unknown $emp_no
		 * @param unknown $check_company_id
		 * @return Ambigous <boolean, unknown>
		 */
		public function payroll_group_info_by_compid($check_company_id){
			// employee group id
		
			$w_emp = array(
					"epi.company_id"=>$check_company_id,
					"epi.status"=>"Active"
			);
		
			$this->edb->where($w_emp);
			$this->edb->join("employee AS e","e.emp_id = epi.emp_id","LEFT");
			$this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
			$this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
			$q_emp = $this->edb->get("employee_payroll_information AS epi");
			$r_emp = $q_emp->result();
			return ($r_emp) ? $r_emp : FALSE ;
		}
		
		/**
		 * Check Workday 
		 * @param unknown_type $emp_no
		 * @param unknown_type $check_company_id
		 */
		public function check_workday($payroll_group_id,$check_company_id){
			$w = array(
				"payroll_group_id"=>$payroll_group_id,
				"company_id"=>$check_company_id
			);	
			$this->db->where($w);
			$q = $this->db->get("workday");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
		
		/**
		 * Check Flexible Required Login
		 * @param unknown_type $payroll_group_id
		 * @param unknown_type $check_company_id
		 */
		public function check_required_login($payroll_group_id,$check_company_id){
			$w = array(
				"payroll_group_id"=>$payroll_group_id,
				"company_id"=>$check_company_id
			);
			$this->db->where($w);
			$q = $this->db->get("flexible_hours");
			$r = $q->row();
			if($r){
				return ($r->not_required_login == NULL) ? TRUE : FALSE ;
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Insert Time In Log
		 * @param unknown_type $date
		 * @param unknown_type $emp_no
		 */
		public function insert_time_in($date,$emp_no,$min_log,$check_type=""){
			
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
						
						if($r->time_in != "" && $r->time_out ==""){
							
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
							if($get_total_hours < 0) $get_total_hours = 0;
							
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
							
						}else if($r->time_in != "" && $r->time_out !=""){
							
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
					
					$get_hoursworked = $this->get_hoursworked($payroll_group_id,$comp_id)->total_hours_for_the_day;
					
					// check workday settings
					$workday_settings_start_time = $this->check_workday_settings_start_time($workday,$payroll_group_id,$comp_id);
					if(strtotime($r->time_in) < strtotime($workday_settings_start_time->latest_time_in_allowed)){
						$workday_settings_start_time = date("H:i:s",strtotime($r->time_in));
					}else{
						$workday_settings_start_time = $workday_settings_start_time->latest_time_in_allowed;
					}
						
					$workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
					
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
		
					if(strtotime($check_bet_timein) <= strtotime($r->time_in) && strtotime($r->time_in) <= strtotime($add_oneday_timein)){					
						
						// global where update data
						$where_update = array(
							"eti.emp_id"=>$emp_id,
							"eti.comp_id"=>$comp_id,
							"eti.employee_time_in_id"=>$r->employee_time_in_id
						);
						
						if($r->time_in != "" && $r->time_out == ""){
							
							// update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE
							$update = FALSE;
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
							$r2 = $q2->row();
			
							if($update){
								// update total hours and total hours required rest day
								$get_total_hours = (strtotime($current_date) - strtotime($r2->time_in)) / 3600;
								
								// update tardiness for timein
								$tardiness_timein = 0;
								if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
									if(date("A",strtotime($r2->time_in)) == "AM"){
										// add one day for time in log
										$new_start_timein = date("Y-m-d",strtotime($r2->time_in." -1 day"));
										$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
										
										if(strtotime($new_start_timein) < strtotime($r2->time_in)){
											$tardiness_timein = (strtotime($r2->time_in) - strtotime($new_start_timein)) / 60;			
										}
									}
								}else{
									$new_start_timein = date("Y-m-d",strtotime($r2->time_in));
									$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
									
									if(strtotime($new_start_timein) < strtotime($r2->time_in)){
										$tardiness_timein = (strtotime($r2->time_in) - strtotime($new_start_timein)) / 60;			
									}
								}
	
								// update total tardiness
								$update_tardiness = $tardiness_timein;
								
								// update undertime
								$update_undertime = 0;
								if(strtotime(date("H:i:s",strtotime($r2->time_in))) < strtotime($workday_settings_start_time)){
									$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id);
									$workday_settings_end_time = date("H:i:s",strtotime($r2->time_in." +{$hours_worked} hour"));
								}
								if(strtotime($r2->time_out) < strtotime($workday_settings_end_time)){
									$new_end_time = date("Y-m-d",strtotime($r2->time_out))." ".$workday_settings_end_time;
									$update_undertime = (strtotime($new_end_time) - strtotime($r2->time_out)) / 60;
								}
								
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
								
								// if value is less than 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($new_total_hours < 0) $new_total_hours = 0;
								if($get_total_hours < 0) $get_total_hours = 0;
								
								$update_timein_logs = array(
									"tardiness_min"=>$update_tardiness,
									"undertime_min"=>$update_undertime,
									"total_hours"=>$new_total_hours,
									"total_hours_required"=>$get_total_hours,
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
						if(strtotime($check_bet_timein) <= strtotime($current_date) && strtotime($current_date) <= strtotime($add_oneday_timein)
							&& $r->time_in != "" && $r->time_out != ""
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
							
							if($r->time_in != "" && $r->time_out == ""){
								
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
								$update_tardiness = 0;#$this->get_tardiness_val($emp_id,$comp_id,$r->time_in);
								$update_undertime = 0;#$this->get_undertime_val($emp_id,$comp_id,$r->time_in,$current_date);
								
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
								
								// if value is less than 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($new_total_hours < 0) $new_total_hours = 0;
								if($get_total_hours < 0) $get_total_hours = 0;
								
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
						
						if($r->time_in != "" && $r->time_out ==""){
							
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
							if($get_total_hours < 0) $get_total_hours = 0;
							
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
							
						}else if($r->time_in != "" && $r->time_out !=""){
							
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
					
					$get_hoursworked = $this->get_hoursworked($payroll_group_id,$comp_id)->total_hours_for_the_day;
					
					// check workday settings
					$workday_settings_start_time = $this->check_workday_settings_start_time($workday,$payroll_group_id,$comp_id);
					if(strtotime($r->time_in) < strtotime($workday_settings_start_time->latest_time_in_allowed)){
						$workday_settings_start_time = date("H:i:s",strtotime($r->time_in));
					}else{
						$workday_settings_start_time = $workday_settings_start_time->latest_time_in_allowed;
					}
					$workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
					
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
					
					// IF WHOLEDAY
					if(strtotime($check_bet_timein) <= strtotime($r->time_in) && strtotime($r->time_in) <= strtotime($add_oneday_timein)){

						// global where update data
						$where_update = array(
							"eti.emp_id"=>$emp_id,
							"eti.comp_id"=>$comp_id,
							"eti.employee_time_in_id"=>$r->employee_time_in_id
						);
						
						if($r->time_in != "" && $r->lunch_out == "" && $r->lunch_in == "" && $r->time_out == ""){
							
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
							
						}else if($r->time_in != "" && $r->lunch_out != "" && $r->lunch_in == "" && $r->time_out == ""){
							
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
							
						}else if($r->time_in != "" && $r->lunch_out != "" && $r->lunch_in != "" && $r->time_out == ""){
							
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
									
									// update tardiness for timein
									$tardiness_timein = 0;
									if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
										if(date("A",strtotime($r2->time_in)) == "AM"){
											// add one day for time in log
											$new_start_timein = date("Y-m-d",strtotime($r2->time_in." -1 day"));
											$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
											
											if(strtotime($new_start_timein) < strtotime($r2->time_in)){
												$tardiness_timein = (strtotime($r2->time_in) - strtotime($new_start_timein)) / 60;			
											}
										}
									}else{
										$new_start_timein = date("Y-m-d",strtotime($r2->time_in));
										$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
										
										if(strtotime($new_start_timein) < strtotime($r2->time_in)){
											$tardiness_timein = (strtotime($r2->time_in) - strtotime($new_start_timein)) / 60;			
										}
									}
									
									// update tardiness for break time
									$update_tardiness_break_time = 0;
									$duration_of_lunch_break_per_day = $this->duration_of_lunch_break_per_day($emp_id, $comp_id);
									$tardiness_a = (strtotime($r2->lunch_in) - strtotime($r2->lunch_out)) / 60;
									if($duration_of_lunch_break_per_day < $tardiness_a){
										$update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
									}

									// update total tardiness
									$update_tardiness = $tardiness_timein + $update_tardiness_break_time;
									
									// update undertime
									$update_undertime = 0;
									if(strtotime(date("H:i:s",strtotime($r2->time_in))) < strtotime($workday_settings_start_time)){
										$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id);
										$workday_settings_end_time = date("H:i:s",strtotime($r2->time_in." +{$hours_worked} hour"));
									}
									if(strtotime($r2->time_out) < strtotime($workday_settings_end_time)){
										$new_end_time = date("Y-m-d",strtotime($r2->time_out))." ".$workday_settings_end_time;
										$update_undertime = (strtotime($new_end_time) - strtotime($r2->time_out)) / 60;
									}
									
									// update total hours
									$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id);
									$update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - ($duration_of_lunch_break_per_day / 60);
									
									// check tardiness value
									$get_total_hours_worked = ($hours_worked / 2) + .5;
									if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0){
										$update_tardiness = 0;
										$update_undertime = 0;
										$flag_tu = 1;
									}
								}
								
								// update total hours required
								$update_total_hours_required = ((strtotime($r2->time_out) - strtotime($r2->time_in)) / 3600) - ($update_tardiness / 60) - ($duration_of_lunch_break_per_day / 60);
								
								// if value is less than 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($update_total_hours < 0) $update_total_hours = 0;
								if($update_total_hours_required < 0) $update_total_hours_required = 0;
								
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
						if(strtotime($check_bet_timein) <= strtotime($current_date) && strtotime($current_date) <= strtotime($add_oneday_timein)
							&& $r->time_in != "" && $r->lunch_out != "" && $r->lunch_in != "" && $r->time_out != ""
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
							
							if($r->time_in != "" && $r->lunch_out == "" && $r->lunch_in == "" && $r->time_out == ""){
								
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
								
							}else if($r->time_in != "" && $r->lunch_out != "" && $r->lunch_in == "" && $r->time_out == ""){
								
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
								
							}else if($r->time_in != "" && $r->lunch_out != "" && $r->lunch_in != "" && $r->time_out == ""){
								
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
									
										// update tardiness for timein
										$tardiness_timein = 0;
										if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
											if(date("A",strtotime($r2->time_in)) == "AM"){
												// add one day for time in log
												$new_start_timein = date("Y-m-d",strtotime($r2->time_in." -1 day"));
												$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
												
												if(strtotime($new_start_timein) < strtotime($r2->time_in)){
													$tardiness_timein = (strtotime($r2->time_in) - strtotime($new_start_timein)) / 60;			
												}
											}
										}else{
											$new_start_timein = date("Y-m-d",strtotime($r2->time_in));
											$new_start_timein = $new_start_timein." ".$workday_settings_start_time;
											
											if(strtotime($new_start_timein) < strtotime($r2->time_in)){
												$tardiness_timein = (strtotime($r2->time_in) - strtotime($new_start_timein)) / 60;			
											}
										}
										
										// update tardiness for break time
										$update_tardiness_break_time = 0;
										$duration_of_lunch_break_per_day = $this->duration_of_lunch_break_per_day($emp_id, $comp_id);
										$tardiness_a = (strtotime($r2->lunch_in) - strtotime($r2->lunch_out)) / 60;
										if($duration_of_lunch_break_per_day < $tardiness_a){
											$update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
										}
	
										// update total tardiness
										$update_tardiness = $tardiness_timein + $update_tardiness_break_time;
										
										// update undertime
										$update_undertime = 0;
										if(strtotime(date("H:i:s",strtotime($r2->time_in))) < strtotime($workday_settings_start_time)){
											$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id);
											$workday_settings_end_time = date("H:i:s",strtotime($r2->time_in." +{$hours_worked} hour"));
										}
										if(strtotime($r2->time_out) < strtotime($workday_settings_end_time)){
											$new_end_time = date("Y-m-d",strtotime($r2->time_out))." ".$workday_settings_end_time;
											$update_undertime = (strtotime($new_end_time) - strtotime($r2->time_out)) / 60;
										}
										
										// update total hours
										$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id);
										$update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - ($duration_of_lunch_break_per_day / 60);
										
										// check tardiness value
										$get_total_hours_worked = ($hours_worked / 2) + .5;
										if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0){
											$update_tardiness = 0;
											$update_undertime = 0;
											$flag_tu = 1;
										}
									}
									
									// update total hours required
									$update_total_hours_required = ((strtotime($r2->time_out) - strtotime($r2->time_in)) / 3600) - ($update_tardiness / 60) - ($duration_of_lunch_break_per_day / 60);
									
									// if value is less then 0 then set value to 0
									if($update_tardiness < 0) $update_tardiness = 0;
									if($update_undertime < 0) $update_undertime = 0;
									if($update_total_hours < 0) $update_total_hours = 0;
									if($update_total_hours_required < 0) $update_total_hours_required = 0; 
									
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
		 * Insert Time In For Latest Timein Not Allowed
		 * @param unknown_type $date
		 * @param unknown_type $emp_no
		 */
		public function insert_time_in_for_lastest_timein_not_allowed($date,$emp_no,$min_log,$check_type=""){
			
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
						
						if($r->time_in != "" && $r->time_out ==""){
							
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
							if($get_total_hours < 0) $get_total_hours = 0;
							
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
							
						}else if($r->time_in != "" && $r->time_out !=""){
							
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
					
					$get_hoursworked = $this->get_hoursworked($payroll_group_id,$comp_id)->total_hours_for_the_day;
					
					// check workday settings
					$workday_settings_start_time = date("H:i:s",strtotime($r->time_in));
					$workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));

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
		
					if(strtotime($check_bet_timein) <= strtotime($r->time_in) && strtotime($r->time_in) <= strtotime($add_oneday_timein)){
						
						// global where update data
						$where_update = array(
							"eti.emp_id"=>$emp_id,
							"eti.comp_id"=>$comp_id,
							"eti.employee_time_in_id"=>$r->employee_time_in_id
						);
						
						if($r->time_in != "" && $r->time_out == ""){
							
							// update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE
							$update = FALSE;
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
							$r2 = $q2->row();
							
							if($update){
								// update total hours and total hours required rest day
								$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
	
								// update total tardiness
								$update_tardiness = 0;
								
								// update undertime
								$update_undertime = 0;
								if(strtotime(date("H:i:s",strtotime($r2->time_in))) < strtotime($workday_settings_start_time)){
									$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id);
									$workday_settings_end_time = date("H:i:s",strtotime($r2->time_in." +{$hours_worked} hour"));
								}
								if(strtotime($r2->time_out) < strtotime($workday_settings_end_time)){
									$new_end_time = date("Y-m-d",strtotime($r2->time_out))." ".$workday_settings_end_time;
									$update_undertime = (strtotime($new_end_time) - strtotime($r2->time_out)) / 60;
								}
								
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
								
								// if value is less then 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($new_total_hours < 0) $new_total_hours = 0;
								if($get_total_hours < 0) $get_total_hours = 0;
								
								$update_timein_logs = array(
									"tardiness_min"=>$update_tardiness,
									"undertime_min"=>$update_undertime,
									"total_hours"=>$new_total_hours,
									"total_hours_required"=>$get_total_hours,
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
						if(strtotime($check_bet_timein) <= strtotime($current_date) && strtotime($current_date) <= strtotime($add_oneday_timein)
							&& $r->time_in != "" && $r->time_out != ""
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
							
							if($r->time_in != "" && $r->time_out == ""){
								
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
								$update_tardiness = 0;#$this->get_tardiness_val($emp_id,$comp_id,$r->time_in);
								$update_undertime = 0;#$this->get_undertime_val($emp_id,$comp_id,$r->time_in,$current_date);
								
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
								
								// if value is less then 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($new_total_hours < 0) $new_total_hours = 0;
								if($get_total_hours < 0) $get_total_hours = 0;
								
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
						
						if($r->time_in != "" && $r->time_out ==""){
							
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
							if($get_total_hours < 0) $get_total_hours = 0;
							
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
							
						}else if($r->time_in != "" && $r->time_out !=""){
							
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
					
					$get_hoursworked = $this->get_hoursworked($payroll_group_id,$comp_id)->total_hours_for_the_day;
					
					// check workday settings
					$workday_settings_start_time = date("H:i:s",strtotime($r->time_in));
					$workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
		
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
					
					// IF WHOLEDAY
					if(strtotime($check_bet_timein) <= strtotime($r->time_in) && strtotime($r->time_in) <= strtotime($add_oneday_timein)){
						
						// global where update data
						$where_update = array(
							"eti.emp_id"=>$emp_id,
							"eti.comp_id"=>$comp_id,
							"eti.employee_time_in_id"=>$r->employee_time_in_id
						);
						
						if($r->time_in != "" && $r->lunch_out == "" && $r->lunch_in == "" && $r->time_out == ""){
							
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
							
						}else if($r->time_in != "" && $r->lunch_out != "" && $r->lunch_in == "" && $r->time_out == ""){
							
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
							
						}else if($r->time_in != "" && $r->lunch_out != "" && $r->lunch_in != "" && $r->time_out == ""){
							
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
									
									// update tardiness for timein
									$tardiness_timein = 0;
									
									// update tardiness for break time
									$update_tardiness_break_time = 0;
									$duration_of_lunch_break_per_day = $this->duration_of_lunch_break_per_day($emp_id, $comp_id);
									$tardiness_a = (strtotime($r2->lunch_in) - strtotime($r2->lunch_out)) / 60;
									if($duration_of_lunch_break_per_day < $tardiness_a){
										$update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
									}

									// update total tardiness
									$update_tardiness = $tardiness_timein + $update_tardiness_break_time;
									
									// update undertime
									$update_undertime = 0;
									if(strtotime(date("H:i:s",strtotime($r2->time_in))) < strtotime($workday_settings_start_time)){
										$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id);
										$workday_settings_end_time = date("H:i:s",strtotime($r2->time_in." +{$hours_worked} hour"));
									}
									if(strtotime($r2->time_out) < strtotime($workday_settings_end_time)){
										$new_end_time = date("Y-m-d",strtotime($r2->time_out))." ".$workday_settings_end_time;
										$update_undertime = (strtotime($new_end_time) - strtotime($r2->time_out)) / 60;
									}
									
									// update total hours
									$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id);
									$update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - ($duration_of_lunch_break_per_day / 60);
									
									// check tardiness value
									$get_total_hours_worked = ($hours_worked / 2) + .5;
									if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0){
										$update_tardiness = 0;
										$update_undertime = 0;
										$flag_tu = 1;
									}
								}
								
								// update total hours required
								$update_total_hours_required = ((strtotime($r2->time_out) - strtotime($r2->time_in)) / 3600) - ($update_tardiness / 60) - ($duration_of_lunch_break_per_day / 60);
								
								// if value is less then 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($update_total_hours < 0) $update_total_hours = 0;
								if($update_total_hours_required < 0) $update_total_hours_required = 0;
								
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
						if(strtotime($check_bet_timein) <= strtotime($current_date) && strtotime($current_date) <= strtotime($add_oneday_timein)
							&& $r->time_in != "" && $r->lunch_out != "" && $r->lunch_in != "" && $r->time_out != ""
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
							
							if($r->time_in != "" && $r->lunch_out == "" && $r->lunch_in == "" && $r->time_out == ""){
								
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
								
							}else if($r->time_in != "" && $r->lunch_out != "" && $r->lunch_in == "" && $r->time_out == ""){
								
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
								
							}else if($r->time_in != "" && $r->lunch_out != "" && $r->lunch_in != "" && $r->time_out == ""){
								
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
									
										// update tardiness for timein
										$tardiness_timein = 0;
										
										// update tardiness for break time
										$update_tardiness_break_time = 0;
										$duration_of_lunch_break_per_day = $this->duration_of_lunch_break_per_day($emp_id, $comp_id);
										$tardiness_a = (strtotime($r2->lunch_in) - strtotime($r2->lunch_out)) / 60;
										if($duration_of_lunch_break_per_day < $tardiness_a){
											$update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
										}
	
										// update total tardiness
										$update_tardiness = $tardiness_timein + $update_tardiness_break_time;
										
										// update undertime
										$update_undertime = 0;
										if(strtotime(date("H:i:s",strtotime($r2->time_in))) < strtotime($workday_settings_start_time)){
											$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id);
											$workday_settings_end_time = date("H:i:s",strtotime($r2->time_in." +{$hours_worked} hour"));
										}
										if(strtotime($r2->time_out) < strtotime($workday_settings_end_time)){
											$new_end_time = date("Y-m-d",strtotime($r2->time_out))." ".$workday_settings_end_time;
											$update_undertime = (strtotime($new_end_time) - strtotime($r2->time_out)) / 60;
										}
										
										// update total hours
										$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id);
										$update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - ($duration_of_lunch_break_per_day / 60);
										
										// check tardiness value
										$get_total_hours_worked = ($hours_worked / 2) + .5;
										if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0){
											$update_tardiness = 0;
											$update_undertime = 0;
											$flag_tu = 1;
										}
									}
									
									// update total hours required
									$update_total_hours_required = ((strtotime($r2->time_out) - strtotime($r2->time_in)) / 3600) - ($update_tardiness / 60) - ($duration_of_lunch_break_per_day / 60);
									
									// if value is less then 0 then set value to 0
									if($update_tardiness < 0) $update_tardiness = 0;
									if($update_undertime < 0) $update_undertime = 0;
									if($update_total_hours < 0) $update_total_hours = 0;
									if($update_total_hours_required < 0) $update_total_hours_required = 0;
									
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
		public function check_workday_settings_start_time($workday,$payroll_group_id,$company_id,$check_type=""){
			$w = array(
				"payroll_group_id"=>$payroll_group_id,
				"company_id"=>$company_id
			);
			$this->db->where($w);
			$q = $this->db->get("flexible_hours");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
		
		/**
		 * Get Hoursworked For Flexible Hours
		 * @param unknown_type $payroll_group_id
		 * @param unknown_type $comp_id
		 */
		public function get_hoursworked($payroll_group_id,$comp_id){
			$w = array(
				"payroll_group_id"=>$payroll_group_id,
				"company_id"=>$comp_id
			);
			$this->db->where($w);
			$q = $this->db->get("flexible_hours");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
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
					return 0;
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
						"working_day"=>date("l",strtotime($time_in))	
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
						$CI->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
						$sql_w = $CI->db->get("split_schedule");
						$row_w = $sql_w->row();
						if($sql_w->num_rows() > 0){
							
							$time_out_sec = date("H:i:s",strtotime($time_out));
							$time_out_date = date("Y-m-d",strtotime($time_out));
							$new_work_end_time = $time_out_date." ".$row_uw->end_time;
							
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
								$new_end_time = date("H:i:s",strtotime($time_in . ' + '.$row_f->total_hours_for_the_day.' hour'));
								
								$time_out_sec = date("H:i:s",strtotime($time_out));
								$time_out_date = date("Y-m-d",strtotime($time_out));
								$new_work_end_time = $time_out_date." ".$new_end_time;
								
								if(strtotime($new_work_end_time) <= strtotime($time_out)){
												
									$time_in_sec = date("H:i:s",strtotime($time_in));
									$total_hours_worked = (strtotime($new_work_end_time) - strtotime($time_in)) / 3600;
								}else{
									$total_hours_worked = (strtotime($time_out) - strtotime($time_in)) / 3600;
								}
								
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
		 * Duration of Lunch Break Per Day for Flexible Hours
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 */
		public function duration_of_lunch_break_per_day($emp_id, $comp_id){
			// check employee payroll group id
			$w = array(
				"emp_id"=>$emp_id,
				"company_id"=>$comp_id
			);
			$this->edb->where($w);
			$q = $this->edb->get("employee_payroll_information");
			$r = $q->row();
			if($r){
				$payroll_group_id = $r->payroll_group_id;
				// check duration of lunch break per day
				$where = array(
					"payroll_group_id"=>$payroll_group_id,
					"company_id"=>$comp_id
				);
				$this->db->where($where);
				$query = $this->db->get("flexible_hours");
				$row = $query->row();
				return ($row) ? $row->duration_of_lunch_break_per_day : FALSE ;
			}else{
				return FALSE;
			}
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
		 * 
		 * Enter description here ...
		 * @param unknown_type $payroll_group_id
		 * @param unknown_type $check_company_id
		 */
		public function check_lastest_timein_allowed($payroll_group_id,$check_company_id){
			$w = array(
				"payroll_group_id"=>$payroll_group_id,
				"company_id"=>$check_company_id
			);	
			$this->db->where($w);
			$this->db->where("latest_time_in_allowed IS NOT NULL");
			$q = $this->db->get("flexible_hours");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
		
	}