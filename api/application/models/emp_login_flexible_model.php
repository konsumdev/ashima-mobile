<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	/**
	 * Employee Login For Flexible Hours Model
	 *
	 * @category Model
	 * @version 1.1
	 * @author Jonathan Bangga <jonathanbangga@gmail.com>
	 *
	 */
	class Emp_login_flexible_model extends CI_Model {
		
		/**
		 * Check Flexible Required Login
		 * @param unknown_type $work_schedule_id
		 * @param unknown_type $check_company_id
		 */
		public function check_required_login($work_schedule_id,$check_company_id){
			$w = array(
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$check_company_id
			);
			$this->db->where($w);
			$q = $this->db->get("flexible_hours");
			$r = $q->row();
			if($r){
				return ($r->not_required_login != NULL || $r->not_required_login != 0) ? TRUE : FALSE ;
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Check Lastest TimeIn Allowed 
		 * @param unknown_type $work_schedule_id
		 * @param unknown_type $check_company_id
		 */
		public function check_lastest_timein_allowed($work_schedule_id,$check_company_id){
			$w = array(
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$check_company_id
			);	
			$this->db->where($w);
			$this->db->where("latest_time_in_allowed IS NOT NULL");
			$q = $this->db->get("flexible_hours");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
		
		/**
		 * no latest time in allowed
		 * @param unknown_type $date
		 * @param unknown_type $emp_no
		 * @param unknown_type $min_log
		 * @param unknown_type $work_schedule_id
		 */
		public function insert_time_in_for_lastest_timein_not_allowed($date,$emp_no,$min_log,$work_schedule_id,$check_type="",$comp_id = 0,$source = "",$currentdate="", $location=""){
			// added barak 
			$locloc = $location;
			// get employee information
			$w_emp 							= array(
											"a.payroll_cloud_id"	=> $emp_no,
											"a.user_type_id"		=> "5",
											"e.company_id" 			=> $comp_id
											);

			$s_emp 							= array(
											"emp_id",
											"company_id"
											);

			$this->edb->select($s_emp);
			$this->edb->where($w_emp);
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$q_emp 							= $this->edb->get("employee AS e");
			$r_emp 							= $q_emp->row();
			$emp_id 						= $r_emp->emp_id;
			
			// check payroll group
			$w_payroll_group 				= array(
											"emp_id"				=> $emp_id
											);

			$s_payroll_group 				= array(
											"payroll_group_id"
											);

			$this->db->select($s_payroll_group);
			$this->db->where($w_payroll_group);

			$q_payroll_group 				= $this->db->get("employee_payroll_information");
			$r_payroll_group 				= $q_payroll_group->row();
			
			if($q_payroll_group->num_rows() > 0){

				$payroll_group 				= $r_payroll_group->payroll_group_id;
			}
			
			$payroll_group 					= 0;
			$shift_name 					= "";
			
			// check number of breaks
			$number_of_breaks_per_day 		= 0;
			$comp_add 						= "";//$this->get_company_address($comp_id);

			$w_uwd 							= array(
											"work_schedule_id"		=> $work_schedule_id,
											"company_id"			=> $comp_id
											);
			$this->db->where($w_uwd);
			$q_uwd 							= $this->db->get("regular_schedule");
			$r_uwd 							= $q_uwd->row();
			
			if($q_uwd->num_rows() > 0){
				$number_of_breaks_per_day 	= $r_uwd->break_in_min;
				$shift_name 				= "regular schedule";
			}else{

				$w_ws 						= array(
											"work_schedule_id"		=> $work_schedule_id,
											"company_id"			=> $comp_id
											);
				$this->db->where($w_ws);

				$q_ws 						= $this->db->get("split_schedule");
				$r_ws 						= $q_ws->row();

				if($q_ws->num_rows() > 0){
					$number_of_breaks_per_day 	= $r_ws->number_of_breaks_per_shift;
					$shift_name 				= "split schedule";
				}
				else{

					$w_fh 					= array(
											"work_schedule_id"		=> $work_schedule_id,
											"company_id"			=> $comp_id
											);

					$this->db->where($w_fh);
					$q_fh 					= $this->db->get("flexible_hours");
					$r_fh 					= $q_fh->row();

					if($q_fh->num_rows() > 0){
						$number_of_breaks_per_day 	= $r_fh->duration_of_lunch_break_per_day;
						$shift_name 				= "flexible hours";
					}					
				}
			}
			
			$late_min = 0;
            // remove computation for new cron job
			/*if($check_type == "time in"){
				$late_min = $this->elm->late_min($comp_id, $date, $emp_id, $work_schedule_id);
			}*/

			#check employee on leave
			$onleave = check_leave_appliction($date,$emp_id,$comp_id);
			$ileave = 'no';
			if($onleave)
				$ileave = 'yes';
			
			// check if breaktime is 0
			if($number_of_breaks_per_day == 0){
				
				// check employee time in
				if($currentdate){
					$current_date = $currentdate;
				}else{
					$current_date = date("Y-m-d H:i:00");
				}

				$w = array(
					"a.payroll_cloud_id"=> $emp_no,
					"a.user_type_id"	=> "5",
					"eti.comp_id" 		=> $comp_id, 
					"eti.status"		=> "Active"
				);
				$this->edb->where($w);
				$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
				$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
				$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
				$this->db->order_by("eti.time_in","DESC");
				$q = $this->edb->get("employee_time_in AS eti",1,0);
				$r = $q->row();

				// check rest day
				$workday 			= date("l",strtotime($date));
				$check_rest_day		= $this->check_rest_day($workday,$work_schedule_id,$comp_id);
				if($check_rest_day){
					return $this->rest_day_flex($comp_id,$work_schedule_id,$current_date,$r,$emp_id,$check_type,$date,$source);
				}

				// added location barak
				$locloc = ($r) ? $r->location . " | " . $location : $location;

				if($q->num_rows() == 0){	
					/* CHECK TIME IN START */
					$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);
					if($wst != FALSE){
						$nwst = date("Y-m-d {$wst}");
						$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
					}
					
					// insert time in log
					$val = array(
						"emp_id"			=> $emp_id,
						"comp_id"			=> $comp_id,
						"date"				=> $date,
						"time_in"			=> $current_date,
						// "source" 			=> $source."-time in",
						"source"			=> 'mobile',
						"location" 			=> $location,
						"late_min" 			=> $late_min,
						"work_schedule_id" 	=> $work_schedule_id,
						"flag_on_leave" 	=> $ileave,
						"time_in_status"	=> "pending",
						"corrected"			=> "Yes",
						"mobile_clockin_status" => "pending",
                        "location_1"		=> $location,
                        "flag_new_time_keeping" => "1" // flag new cronjob
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
					//"source"  				=> $last_timein_source.",".$source."-".$check_type,
					$last_timein_source 	= $r->source;

					// get date time in to date time out
					$workday = date("l",strtotime($r->time_in));
					$payroll_group_id = $r->payroll_group_id;
		
					// check rest day
					$check_rest_day = $this->check_rest_day($workday,$work_schedule_id,$comp_id); //c2
					if($check_rest_day){
		
						// global where update data
						$where_update = array(
							"eti.emp_id"=>$emp_id,
							"eti.comp_id"=>$comp_id,
							"eti.employee_time_in_id"=>$r->employee_time_in_id
						);
						
						if($check_type=="time out"){
							
							// update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE FOR REST DAY
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							if($min_log < $get_diff){
								$update_val = array(
									"time_out"				=> $current_date,
									"source"				=> 'mobile',
									"time_in_status"		=> "pending",
									"corrected"				=> "Yes",
									"mobile_clockout_status" => "pending",
									"location_4"			=> $location,
									"location" 				=> $locloc,
                                    "flag_new_time_keeping" => "1" // flag new cronjob
								);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);


								// athan helper
								if($date){
									// payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
								}
							}
							else{
								return FALSE;
							}

							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							// update total hours and total hours required rest day xxxx
							$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
							if($get_total_hours < 0) $get_total_hours = 0;
							
							$update_timein_logs = array(
								"tardiness_min"			=> 0,
								"undertime_min"			=> 0,
								"total_hours"			=> $get_total_hours,
								"total_hours_required"	=> $get_total_hours,
								// "source"  				=> $last_timein_source.",".$source."-".$check_type,
                                "source"				=> 'mobile',
                                "flag_new_time_keeping" => "1" // flag new cronjob
							);
							$this->db->where($where_update);
							$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
							
							return ($q2) ? $q2->row() : FALSE ;
							exit;
							
						}else if($check_type=="time in"){
							
							/* CHECK TIME IN START */
							$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);
							if($wst != FALSE){
								// new start time
								$nwst 					= date("Y-m-d {$wst}");
								$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
								// if($check_diff_total_hours <= 1 && $check_diff_total_hours >= 0) $current_date = $nwst;
							}
							
							// insert time in value for rest day ============================================== >>> INSERT TIME IN VALUE FOR REST DAY
							$insert 	= FALSE;
							$get_diff 	= (strtotime($current_date) - strtotime($r->time_out)) / 60;
							
							if($min_log < $get_diff){
								$val = array(
									"emp_id"			=> $emp_id,
									"comp_id"			=> $comp_id,
									"work_schedule_id"	=> $work_schedule_id,
									"date"				=> $date,
									"time_in"			=> $current_date,
									"late_min" 			=> $late_min,
									// "source" 			=> $source,
									"source"			=> 'mobile',
									"flag_on_leave" 	=> $ileave,
									"time_in_status"	=> "pending",
									"corrected"			=> "Yes",
									"mobile_clockin_status" => "pending",
									"location_1"		=> $location,
									"location" 				=> $locloc,
                                    "flag_new_time_keeping" => "1" // flag new cronjob
								);
								$insert = $this->db->insert("employee_time_in",$val);	
							}
							else{
								return FALSE;
							}

							if($insert){
								$w2 = array(
									"a.payroll_cloud_id"=> $emp_no,
									"eti.date"			=> $date
									);
								$this->edb->where($w2);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$this->db->order_by("eti.time_in","DESC");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
				
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}
							else{
								$this->edb->where($where_update);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
				
								return ($q2) ? $q2->row() : FALSE ;
								exit;
							}
						}
					}
					
					#get hours worked from settings
					$get_hoursworked = $this->get_hoursworked($work_schedule_id,$comp_id)->total_hours_for_the_day; //x1
					
					// check workday settings
					$workday_settings_start_time 	= date("Y-m-d H:i:s",strtotime($r->time_in));
					$workday_settings_end_time 		= date("Y-m-d H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));

					if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
						// for night shift time in and time out value for working day
						$check_bet_timein 	= date("Y-m-d")." ".$workday_settings_start_time;
						$check_bet_timeout 	= date("Y-m-d",strtotime("+1 day"))." ".$workday_settings_end_time;
					}else{
						// for day shift time in and time out value for working day
						$check_bet_timein 	= date("Y-m-d")." ".$workday_settings_start_time;
						$check_bet_timeout 	= date("Y-m-d")." ".$workday_settings_end_time;
					}
					
					// check between date time in to date time out
					$add_oneday_timein = date("Y-m-d",strtotime("+1 day"))." ".$workday_settings_start_time;
		
					if(strtotime($check_bet_timein) <= strtotime($r->time_in) && strtotime($r->time_in) <= strtotime($add_oneday_timein)){
						
						// global where update data
						$where_update 	= array(
										"eti.emp_id"				=> $emp_id,
										"eti.comp_id"				=> $comp_id,
										"eti.employee_time_in_id"	=> $r->employee_time_in_id
										);
						
						if($check_type == "time out"){
							// update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE
							$update 			= FALSE;
							$get_diff 			= (strtotime($current_date) - strtotime($r->time_in)) / 60;

							if($min_log < $get_diff){
								$update_val 	= array(
												"time_out" 			=> $current_date,
												// "source"  			=> $last_timein_source.",".$source."-".$check_type,
												"source"			=> 'mobile',
												"time_in_status"	=> "pending",
												"corrected"			=> "Yes",
												"mobile_clockin_status" => "pending",
												"location_4"		=> $location,
												"location" 				=> $locloc,
                                                "flag_new_time_keeping" => "1" // flag new cronjob
												);
								$this->db->where($where_update);
								$update 		= $this->db->update("employee_time_in AS eti",$update_val);



								// athan helper
								if($date){
									// payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
								}
							}
							else{
								return FALSE;
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
								
								$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id, $work_schedule_id);
								$hsx = $this->convert_to_min($hours_worked);
								$workday_settings_end_time = date("Y-m-d H:i:s",strtotime($r2->time_in." +{$hsx} minutes"));
								
								//** if timeout before the init lunchin this means he/she is doing halfday as we discus 2/17/17 5:00 PM **//
								//if($new_time_out_cur_orig_str < $lunch_in_new_str){
								//	$new_break			= total_min_between($lunch_in_time_punch, $lunch_out_time_punch);
								//}
								
								if(strtotime($r2->time_out) < strtotime($workday_settings_end_time)){
									$t 					= date('Y-m-d',strtotime($r2->time_out));
									$hr 				= date('H:i:s',strtotime($workday_settings_end_time));
									$new_end_time 		= date("Y-m-d H:i:s",strtotime($t." ".$hr));
									$h 					= $this->total_hours_worked($new_end_time, $r2->time_out);
									$update_undertime 	= $h;
								}
								
								// check tardiness value
								$flag_tu = 0;
							
								// required hours worked only
								$new_total_hours = $this->get_tot_hours($emp_id,$comp_id,$r->time_in,$current_date,$hours_worked,$work_schedule_id); 
								
								// overwrite total hours
								$new_total_hours 	= ($get_total_hours >= $hours_worked) ? $hours_worked : $get_total_hours ;
								$total1 			= $this->total_hours_worked($r2->time_out, $r2->time_in);
								$render_hours  		= $this->convert_to_hours($total1);
								
								// if value is less then 0 then set value to 0
								if($update_tardiness < 0) 	$update_tardiness 	= 0;
								if($update_undertime < 0) 	$update_undertime 	= 0;
								if($new_total_hours < 0) 	$new_total_hours 	= 0;
								if($get_total_hours < 0) 	$get_total_hours 	= 0;
								
								$update_timein_logs = array(
														"tardiness_min"				=> $update_tardiness,
														"undertime_min"				=> $update_undertime,
														"total_hours" 				=> $hours_worked,
														"total_hours_required"		=> $render_hours,
														"flag_tardiness_undertime"	=> $flag_tu,
                                                        "work_schedule_id" 			=> $work_schedule_id,
                                                        "flag_new_time_keeping"     => "1" // flag new cronjob
													);
								
								$att = $this->elm->calculate_attendance($comp_id,$r->time_in,$current_date);
								
								if($att){
									$total_hours_worked 						= $this->elm->total_hours_worked($current_date, $r->time_in);
									$total_hours_worked 						= $this->elm->convert_to_hours($total_hours_worked);
									$update_timein_logs['lunch_in'] 			= null;
									$update_timein_logs['lunch_out'] 			= null;
									$update_timein_logs['total_hours_required'] = $total_hours_worked;
									$update_timein_logs['absent_min'] 			= ($hours_worked - $total_hours_worked) * 60;
									$update_timein_logs['late_min'] 			= 0;
									$update_timein_logs['tardiness_min'] 		= 0;
									$update_timein_logs['undertime_min'] 		= 0;
								}
								
								$this->db->where($where_update);
								$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
							}
							return ($q2) ? $q2->row() : FALSE ;
						}else{
							/* CHECK TIME IN START */
							$wst 	= $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);//x5
							if($wst != FALSE){
								// new start time
								$nwst 					= date("Y-m-d {$wst}");
								$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
							}
							
							// insert time in log ================================================================ >>>> INSERT NEW TIME IN LOG SAME DATE
							$insert 	= FALSE;
							$get_diff 	= (strtotime($current_date) - strtotime($r->time_out)) / 60;
							if($min_log < $get_diff){
								$val 	= array(
										"emp_id"			=> $emp_id,
										"comp_id"			=> $comp_id,
										"date"				=> $date,
										"time_in"			=> $current_date,
										// "source" 			=> $source,
										"source"			=> 'mobile',
										"location" 			=> $locloc,
										"late_min" 			=> $late_min,
										"work_schedule_id" 	=> $work_schedule_id,
										"flag_on_leave" 	=> $ileave,
										"time_in_status"	=> "pending",
										"corrected"			=> "Yes",
										"mobile_clockin_status" => "pending",
                                        "location_1"		=> $location,
                                        "flag_new_time_keeping" => "1" // flag new cronjob
										);
								$insert = $this->db->insert("employee_time_in",$val);	
							}
							else{
								return FALSE;
							}

							if($insert){
								$w2 = array(
									"a.payroll_cloud_id"=> $emp_no,
									"eti.date"			=> $date
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
						
						if(strtotime($check_bet_timein) <= strtotime($current_date) && strtotime($current_date) <= strtotime($add_oneday_timein) && $r->time_in != "" && $r->time_out != ""){
							
							/* CHECK TIME IN START */
							$wst 	= $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);
							if($wst != FALSE){
								// new start time
								$nwst 					= date("Y-m-d {$wst}");
								$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
							}
							
							// insert time in log
							$val = array(
								"emp_id"			=> $r_emp->emp_id,
								"comp_id"			=> $r_emp->company_id,
								"date"				=> $date,
								"time_in"			=> $current_date,
								// "source" 			=> $source,
								"location" 			=> $locloc,
								"late_min" 			=> $late_min,
								"work_schedule_id" 	=> $work_schedule_id,
								"flag_on_leave" 	=> $ileave,
								"source"			=> 'mobile',
								"time_in_status"	=> "pending",
								"corrected"			=> "Yes",
								"mobile_clockin_status" => "pending",
                                "location_1"		=> $location,
                                "flag_new_time_keeping" => "1" // flag new cronjob
							);
							
							$insert = $this->db->insert("employee_time_in",$val);
							
							if($insert){
								$w2 = array(
									"a.payroll_cloud_id"=> $emp_no,
									"eti.date"			=> $date
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
							$where_update 	= array(
											"eti.emp_id"				=> $emp_id,
											"eti.comp_id"				=> $comp_id,
											"eti.employee_time_in_id"	=> $r->employee_time_in_id
											);
							if($check_type == "time out"){
								// update time out value ============================================== >>> UPDATE TIME OUT VALUE
								$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
								
								if($min_log < $get_diff){
									$update_val 	= array(
													"time_out"			=> $current_date,
													// "source"  			=> $last_timein_source.",".$source."-".$check_type,
													"source"			=> 'mobile',
													"time_in_status"	=> "pending",
													"corrected"			=> "Yes",
													"mobile_clockout_status" => "pending",
													"location_4"		=> $location,
													"location" 			=> $locloc,
                                                    "flag_new_time_keeping" => "1" // flag new cronjob
													);
									$this->db->where($where_update);
									$update 	= $this->db->update("employee_time_in AS eti",$update_val);


									// athan helper
									if($date){
										// payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
									}
								}
								else{
									return FALSE;
								}

								$this->edb->where($where_update);
								$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
								$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);								
								$r2 = $q2->row();
								
								// update total hours and total hours required rest day
								$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
								
								// tardiness and undertime value
								$update_tardiness = 0;
								$update_undertime = 0;
								// I ADD NEW CODE HERE
								
								$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id, $work_schedule_id);
								$hsx = $this->convert_to_min($hours_worked);
								$workday_settings_end_time = date("Y-m-d H:i:s",strtotime($r2->time_in." +{$hsx} minutes"));
								
								// remove calculation for new cron job
								/*if(strtotime($r2->time_out) < strtotime($workday_settings_end_time)){
									$t = date('Y-m-d',strtotime($r2->time_out));
									$hr = date('H:i:s',strtotime($workday_settings_end_time));
									$new_end_time = date("Y-m-d H:i:s",strtotime($t." ".$hr));
									$h = $this->total_hours_worked($new_end_time, $r2->time_out);		
									
									$update_undertime = $h;
								}*/
								//END MY CODE HERE
								
								// check tardiness value
								$flag_tu = 0;
								
								$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r->time_in)), $emp_id, $work_schedule_id);
							
								
								// required hours worked only
								$new_total_hours = $this->get_tot_hours($emp_id,$comp_id,$r->time_in,$current_date,$hours_worked,$work_schedule_id); //endx
								$total1 = $this->total_hours_worked($r2->time_out, $r2->time_in);
								$render_hours  = $this->convert_to_hours($total1);
								// overwrite total hours
								$new_total_hours = ($get_total_hours >= $hours_worked) ? $hours_worked : $get_total_hours ;
								
								// if value is less then 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($new_total_hours < 0)  $new_total_hours = 0;
								if($get_total_hours < 0)  $get_total_hours = 0;
								
								$update_timein_logs = array(
													"tardiness_min"				=> $update_tardiness,
													"undertime_min" 			=> $update_undertime,
													"total_hours" 				=> $hours_worked,
													"total_hours_required" 		=> $render_hours,
													"flag_tardiness_undertime"	=> $flag_tu,
													// "source"  					=> $last_timein_source.",".$source."-".$check_type,
                                                    "source"  					=> 'mobile',
                                                    "flag_new_time_keeping" => "1" // flag new cronjob
													);
								
								#attendance settings
								$att = $this->elm->calculate_attendance($comp_id,$r->time_in,$current_date);
								
								if($att){
									
									$total_hours_worked = $this->elm->total_hours_worked($current_date, $r2->time_in);
									$total_hours_worked = $this->elm->convert_to_hours($total_hours_worked);
									$update_timein_logs['lunch_in'] = null;
									$update_timein_logs['lunch_out'] = null;
									$update_timein_logs['total_hours_required'] = $total_hours_worked;
									$update_timein_logs['absent_min'] = ($hours_worked - $total_hours_worked) * 60;
									$update_timein_logs['late_min'] = 0;
									$update_timein_logs['tardiness_min'] = 0;
									$update_timein_logs['undertime_min'] = 0;
								}
								
								if($r->flag_regular_or_excess == "excess"){
									$update_timein_logs['total_hours_required'] = $total_hours_worked;
									$update_timein_logs['total_hours']			= $total_hours_worked;
									$update_timein_logs['lunch_in'] 			= null;
									$update_timein_logs['lunch_out'] 			= null;
									$update_timein_logs['absent_min'] 			= 0;
									$update_timein_logs['undertime_min'] 		= 0;
									$update_timein_logs['tardiness_min'] 		= 0;
									$update_timein_logs['overbreak_min'] 		= 0;
									$update_timein_logs['late_min'] 			= 0;
								}
								
								$this->db->where($where_update);
								$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
								
								return ($q2) ? $q2->row() : FALSE ;
								
							}else{
								/* CHECK TIME IN START */
								$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);
								if($wst != FALSE){
									// new start time
									$nwst = date("Y-m-d H:i:00", strtotime($date." ".$wst));
									$check_diff_total_hours = (strtotime($nwst) - strtotime($current_date)) / 3600;
								}
								
								// insert time in log ================================================================ >>>> INSERT NEW TIME IN LOG SAME DATE
								$insert = FALSE;
								$get_diff = (strtotime($current_date) - strtotime($r->time_out)) / 60;//x8
								if($min_log < $get_diff){
									$val = array(
										"emp_id"			=> $emp_id,
										"comp_id"			=> $comp_id,
										"date"				=> $date,
										"time_in"			=> $current_date,
										// "source" 			=> $source,
										"late_min" 			=> $late_min,
										"location" 			=> $locloc,
										"work_schedule_id" 	=> $work_schedule_id,
										"flag_on_leave" 	=> $ileave,
										"source"			=> 'mobile',
										"time_in_status"	=> "pending",
										"corrected"			=> "Yes",
										"mobile_clockin_status" => "pending",
                                        "location_1"		=> $location,
                                        "flag_new_time_keeping" => "1" // flag new cronjob
									);
									
									if($r->date == $date){
										$val["flag_regular_or_excess"] 	= "excess";
										$val["work_schedule_id"] 		= "-2";
										$val["flag_on_leave"] 			= "no";
									}
									
									$insert = $this->db->insert("employee_time_in",$val);	
								}
								else{
									return FALSE;
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
			else{
				// check employee time in
				if($currentdate){
					$current_date = $currentdate;
				}
				else{
					$current_date = date("Y-m-d H:i:00");
				}

				$s = array(
					"eti.employee_time_in_id",
					"eti.time_in",
					"eti.time_out",
					"eti.lunch_in",
					"eti.lunch_out",
					);
				$w = array(
					"a.user_type_id"	=> "5",
					"eti.comp_id"		=> $comp_id,
					"eti.status"		=> "Active"
					);
				
				$w1 = array(
					"a.payroll_cloud_id"=> $emp_no
					);
				
				$this->db->select($s);
				$this->db->where($w);
				$this->edb->where($w1);
				$this->db->join("employee AS e","eti.emp_id = e.emp_id","INNER");
				$this->db->join("accounts AS a","a.account_id = e.account_id","INNER");
				$this->db->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
				$this->db->order_by("eti.time_in","DESC");
				$q = $this->edb->get("employee_time_in AS eti",1,0);
				$r = $q->row();
				
				// check rest day
				$workday 			= date("l",strtotime($date));
				$check_rest_day		= $this->check_rest_day($workday,$work_schedule_id,$comp_id);
				
				if($check_rest_day){
					return $this->rest_day_flex($comp_id,$work_schedule_id,$current_date,$r,$emp_id,$check_type,$date,$source);
				}
				
				$overbreak_min = 0;
                // remove computation for new cron job
                /*if($check_type=="lunch in"){
					$overbreak_min = $this->elm->overbreak_min($comp_id,$date,$emp_id,$work_schedule_id,$r->lunch_out);
				}*/

				$locloc = ($r) ? $r->location . " | " . $location : $location;
				
				if($q->num_rows() == 0){
					
					/* CHECK TIME IN START */
					$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);

					if($wst != FALSE){
						// new start time
						$nwst = date("Y-m-d H:i:00",strtotime($current_date." ".$wst));
						$check_diff_total_hours = (strtotime($nwst) - strtotime($current_date)) / 3600;
					}

					$val = array(
						"emp_id"=>$emp_id,
						"comp_id"=>$comp_id,
						"date"=>$date,
						"time_in"=>$current_date,						
						// "source" => $source."-time in",
						"location" => $locloc,
						"late_min" => $late_min,
						"work_schedule_id" => $work_schedule_id,
						"flag_on_leave" => $ileave,
						"source"			=> 'mobile',
						"time_in_status"	=> "pending",
						"corrected"			=> "Yes",
						"mobile_clockin_status" => "pending",
                        "location_1"		=> $location,
                        "flag_new_time_keeping" => "1" // flag new cronjob
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
				else{

					$last_timein_source 	= $r->source;
					// get date time in to date time out
					$workday = date("l",strtotime($r->time_in));
					
					// $payroll_group_id = $r->payroll_group_id;
					$payroll_group_id = 0;
					
					$get_hoursworked = $this->get_hoursworked($work_schedule_id,$comp_id)->total_hours_for_the_day; //x10
					
					// check workday settings
					$workday_settings_start_time 	= date("H:i:s",strtotime($r->time_in));
					$workday_settings_end_time 		= date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
		
					if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
						
						// for night shift time in and time out value for working day
						$check_bet_timein 	= $date." ".$workday_settings_start_time;
						$check_bet_timeout 	= date("Y-m-d",strtotime($date. "+1 day"));
						$check_bet_timeout 	= $check_bet_timeout." ".$workday_settings_end_time;
					}
					else{
						// for day shift time in and time out value for working day
						$check_bet_timein 	= $date." ".$workday_settings_start_time;
						$check_bet_timeout 	= $date." ".$workday_settings_end_time;
					}
					
					// check between date time in to date time out
					$add_oneday_timein 		= date("Y-m-d",strtotime($date." +1 day"));
					$add_oneday_timein 		= $add_oneday_timein." ".$workday_settings_start_time;

					
					// IF WHOLEDAY
					if(strtotime($check_bet_timein) <= strtotime($r->time_in) && strtotime($r->time_in) <= strtotime($add_oneday_timein)){
						
						if($r->flag_regular_or_excess == "excess"){
							$check_type = "time out";
						}
						// global where update data
						$where_update = array(
							"eti.emp_id"				=> $emp_id,
							"eti.comp_id"				=> $comp_id,
							"eti.employee_time_in_id"	=> $r->employee_time_in_id

						);
				
						if($check_type == "lunch out"){
							// update lunch out value ================================================================ >>>> UPDATE LUNCH OUT VALUE
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							
							if($min_log < $get_diff){
								$update_val = array(
											"lunch_out" => $current_date,
											// "source"  	=> $last_timein_source.",".$source."-".$check_type,
											"source"			=> 'mobile',
											"time_in_status"	=> "pending",
											"corrected"			=> "Yes",
											"mobile_lunchout_status" => "pending",
											"location_2"		=> $location,
											"location" 			=> $locloc,
                                            "flag_new_time_keeping" => "1" // flag new cronjob
											);
								$this->db->where($where_update);
								$update 	= $this->db->update("employee_time_in AS eti",$update_val);
							}
							else{
								return FALSE;
							}

							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
							
						}
						else if($check_type == "lunch in"){
							
							// update lunch in value ================================================================ >>>> UPDATE LUNCH IN VALUE
							$get_diff = (strtotime($current_date) - strtotime($r->lunch_out)) / 60;
							if($min_log < $get_diff){
								$update_val = array(
											"lunch_in" 		=> $current_date,
											"overbreak_min" => $overbreak_min,
											"tardiness_min" => $overbreak_min,
											// "source"  		=> $last_timein_source.",".$source."-".$check_type,
											"source"			=> 'mobile',
											"time_in_status"	=> "pending",
											"corrected"			=> "Yes",
											"mobile_lunchin_status" => "pending",
											"location_3"		=> $location,
											"location" 			=> $locloc,
                                            "flag_new_time_keeping" => "1" // flag new cronjob
											);
								$this->db->where($where_update);
								$update 	= $this->db->update("employee_time_in AS eti",$update_val);
							}
							else{
								return FALSE;
							}

							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE ;
							
						}
						else if($check_type == "time out"){
							// update time out value ================================================================ >>>> UPDATE TIME OUT VALUE
							$update = FALSE;
							
							if($r->lunch_in){
								$get_diff = (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
							}else{
								$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							}
							
							if($min_log < $get_diff){
								$update_val = array(
											"time_out" 	=> $current_date,
											// "source"  	=> $last_timein_source.",".$source."-".$check_type,
											"source"			=> 'mobile',
											"time_in_status"	=> "pending",
											"corrected"			=> "Yes",
											"mobile_clockout_status" => "pending",
											"location_4"		=> $location,
											"location" 			=> $locloc,
                                            "flag_new_time_keeping" => "1" // flag new cronjob
											);
								$this->db->where($where_update);
								$update 	= $this->db->update("employee_time_in AS eti",$update_val);

								// athan helper
								if($date){
									// payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
								}
							}
							else{
								return FALSE;
							}

							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							$r2 = $q2->row();
							
							if($update){
								// update flag tardiness and undertime
								$flag_tu = 0;
								
								// update tardiness for break time
								$update_tardiness_break_time = 0;
								$duration_of_lunch_break_per_day = $this->duration_of_lunch_break_per_day($emp_id, $comp_id, $work_schedule_id);
								
								// check no. of timein row
								$check_timein_row = $this->check_timein_row($emp_id, $comp_id, $r2->time_in);
								if($check_timein_row){
									// update tardiness
									$update_tardiness 	= 0;
									
									// update undertime
									$update_undertime 	= 0;
									
									// update total hours
									$update_total_hours = 0;
									
									$hours_worked 		= $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id, $work_schedule_id);
								}else{
									// update tardiness for timein
									$tardiness_timein = 0;
																		
									$tardiness_a = (strtotime($r2->lunch_in) - strtotime($r2->lunch_out)) / 60;
									if($duration_of_lunch_break_per_day < $tardiness_a){
										$update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
									}
								
									if($r2->lunch_in == "" || $r2->lunch_out ==""){
										$update_tardiness_break_time = 0;
									}
									
									// update total tardiness
									$update_tardiness = $tardiness_timein + $update_tardiness_break_time;
									
									// update undertime
									$update_undertime = 0;
									if(strtotime(date("H:i:s",strtotime($r2->time_in))) < strtotime($workday_settings_start_time)){
										$hours_worked 				= $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id, $work_schedule_id);
										$workday_settings_end_time 	= date("H:i:s",strtotime($r2->time_in." +{$hours_worked} hour"));
									}
									if(strtotime($r2->time_out) < strtotime($workday_settings_end_time)){
										$new_end_time 		= date("Y-m-d",strtotime($r2->time_out))." ".$workday_settings_end_time;
										$update_undertime 	= (strtotime($new_end_time) - strtotime($r2->time_out)) / 60;
									}
									// update total hours
									$hours_worked 		= $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id, $work_schedule_id);
									$hours_worked		= $hours_worked - ($duration_of_lunch_break_per_day / 60);
									$update_total_hours = $hours_worked - ($update_tardiness / 60) - ($update_undertime / 60) - ($duration_of_lunch_break_per_day / 60);
									
									// check tardiness value
									$get_total_hours_worked = ($hours_worked / 2) + .5;
									if($update_total_hours <= $get_total_hours_worked && $update_total_hours != 0){
										$update_tardiness 	= 0;
										$update_undertime 	= 0;
										$flag_tu 			= 1;
									}
								}
								
								// update total hours required
								$update_total_hours_required = ((strtotime($r2->time_out) - strtotime($r2->time_in)) / 3600) - ($duration_of_lunch_break_per_day / 60);
								
								$total1 				= $this->total_hours_worked($r2->time_out, $r2->time_in) - round($update_tardiness_break_time) - $number_of_breaks_per_day;
								$render_hours  			= $this->convert_to_hours($total1);
								$sub_total_hours_tardy 	= $render_hours + ($update_tardiness_break_time / 60);
								
								if($hours_worked > $sub_total_hours_tardy){
									$update_undertime	= ($hours_worked * 60) - ($sub_total_hours_tardy * 60);
								}
								// if value is less then 0 then set value to 0
								if($update_tardiness < 0) 				$update_tardiness 				= 0;
								if($update_undertime < 0) 				$update_undertime 				= 0;
								if($update_total_hours < 0) 			$update_total_hours 			= 0;
								if($update_total_hours_required < 0) 	$update_total_hours_required 	= 0;
								
								// update employee time in logs
								$update_timein_logs = array(
													"tardiness_min"				=> $update_tardiness,
													"overbreak_min" 			=> $update_tardiness_break_time,
													"undertime_min"				=> $update_undertime,
													"total_hours"				=> $hours_worked,
													"total_hours_required"		=> $render_hours,
                                                    "flag_tardiness_undertime"	=> $flag_tu,
                                                    "flag_new_time_keeping"     => "1" // flag new cronjob
													);
								
								$flex_rules = $this->get_flex_rules($comp_id,$work_schedule_id);
								if($flex_rules){
									$break_rules 	= $flex_rules->break_rules;
									$assumed_breaks = $flex_rules->assumed_breaks;
									$assumed_breaks = $assumed_breaks * 60;
									
									if($break_rules == "assumed"){
										$number_of_breaks_b 				= $this->elmf->check_break_time_flex($work_schedule_id,$comp_id,false);
										$new_lunch_out 						= strtotime($r2->time_in."+ ".$assumed_breaks." minutes");
										$new_lunch_out 						= date("Y-m-d H:i:s",$new_lunch_out);
										$update_timein_logs['lunch_out'] 	= $new_lunch_out;
										$new_lunch_in						= strtotime($new_lunch_out."+ ".$number_of_breaks_b." minutes");
										$new_lunch_in 						= date("Y-m-d H:i:s",$new_lunch_in);
										$update_timein_logs['lunch_in'] 	= $new_lunch_in;
										
										$current_date_str					= strtotime($current_date);
										$new_lunch_out_str					= strtotime($new_lunch_out);
										$new_lunch_in_str					= strtotime($new_lunch_in);
										
										// if timeout before the assumed break 	--> lunchout
										if($current_date_str < $new_lunch_out_str){
											$render_hours = (total_min_between($current_date,$r2->time_in))/60;
											// corrected here so no overbreak if assumed
											//$sub_total_hours_tardy 	= $render_hours + ($update_tardiness_break_time / 60);
											$sub_total_hours_tardy 	= $render_hours;
											
											if($hours_worked > $sub_total_hours_tardy){
												$update_undertime	= ($hours_worked * 60) - ($sub_total_hours_tardy * 60);
											}
											
											
											$update_timein_logs['lunch_out']			= null;
											$update_timein_logs['lunch_in']				= null;
											$update_timein_logs['overbreak_min']		= 0;
											$update_timein_logs['tardiness_min']		= 0;
											$update_timein_logs['undertime_min']		= $update_undertime;
											$update_timein_logs['total_hours_required']	= $render_hours;
										}
										
										// if timeout between the assumed break --> lunchout and lunchin
										if($current_date_str >= $new_lunch_out_str && $current_date_str <= $new_lunch_in_str){
											$render_hours = (total_min_between($new_lunch_out,$r2->time_in))/60;
											$sub_total_hours_tardy 	= $render_hours + ($update_tardiness_break_time / 60);
												
											if($hours_worked > $sub_total_hours_tardy){
												$update_undertime	= ($hours_worked * 60) - ($sub_total_hours_tardy * 60);
											}
											$update_timein_logs['lunch_out']			= null;
											$update_timein_logs['lunch_in']				= null;
											$update_timein_logs['overbreak_min']		= 0;
											$update_timein_logs['tardiness_min']		= 0;
											$update_timein_logs['undertime_min']		= $update_undertime;
											$update_timein_logs['total_hours_required']	= $render_hours;
										}
										// if timeout after the assumed break 	--> lunchout
										if($current_date_str > $new_lunch_in_str){
											$render_hours = (total_min_between($current_date,$r2->time_in) - $duration_of_lunch_break_per_day)/60;
											$sub_total_hours_tardy 	= $render_hours + ($update_tardiness_break_time / 60);
											
											if($hours_worked > $sub_total_hours_tardy){
												$update_undertime	= ($hours_worked * 60) - ($sub_total_hours_tardy * 60);
											}
											$update_timein_logs['overbreak_min']		= 0;
											$update_timein_logs['tardiness_min']		= 0;
											$update_timein_logs['lunch_out'] 			= $new_lunch_out;
											$update_timein_logs['lunch_in'] 			= $new_lunch_in;
											$update_timein_logs['undertime_min']		= $update_undertime;
											$update_timein_logs['total_hours_required']	= $render_hours;
										}
									}
								}
								
								//*attendance settings here ====>>>
								
								$att = $this->elm->calculate_attendance($comp_id,$r2->time_in,$r2->time_out);
								if($att){
									$total_hours_worked 						= $this->elm->total_hours_worked($r2->time_out, $r2->time_in);
									$total_hours_worked 						= $this->elm->convert_to_hours($total_hours_worked);
									$update_timein_logs['lunch_in'] 			= null;
									$update_timein_logs['lunch_out'] 			= null;
									$update_timein_logs['total_hours_required'] = $total_hours_worked;
									$update_timein_logs['absent_min'] 			= ($hours_worked - $total_hours_worked) * 60;
									$update_timein_logs['late_min'] 			= 0;
									$update_timein_logs['tardiness_min'] 		= 0;
									$update_timein_logs['undertime_min'] 		= 0;
								}
								
								if($r->flag_regular_or_excess == "excess"){
									$update_timein_logs['total_hours_required'] = $render_hours;
									$update_timein_logs['total_hours']			= $render_hours;
									$update_timein_logs['lunch_in'] 			= null;
									$update_timein_logs['lunch_out'] 			= null;
									$update_timein_logs['absent_min'] 			= 0;
									$update_timein_logs['undertime_min'] 		= 0;
									$update_timein_logs['tardiness_min'] 		= 0;
									$update_timein_logs['overbreak_min'] 		= 0;
                                    $update_timein_logs['late_min'] 			= 0;                                    
								}
								
								$this->db->where($where_update);
								$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
							}
							return ($q2) ? $q2->row() : FALSE;
							
						}else{
							
							/* CHECK TIME IN START */
							$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);
							if($wst != FALSE){
								// new start time
								$nwst = $date." ".$wst;
								$check_diff_total_hours = (strtotime($nwst) - strtotime($current_date)) / 3600;
							}
							
							// insert time in log ================================================================ >>>> INSERT NEW TIME IN LOG SAME DATE
							$insert = FALSE;
							$get_diff = (strtotime($current_date) - strtotime($r->time_out)) / 60;
							if($min_log < $get_diff){
								$val = array(
									"emp_id"			=> $emp_id,
									"comp_id"			=> $comp_id,
									"date"				=> $date,
									"time_in"			=> $current_date,
									// "source" 			=> $source."-time in",
									"late_min" 			=> $late_min,
									"location" 			=> $locloc,
									"work_schedule_id" 	=> $work_schedule_id,
									"flag_on_leave" 	=> $ileave,
									"source"			=> 'mobile',
									"time_in_status"	=> "pending",
									"corrected"			=> "Yes",
									"mobile_clockin_status" => "pending",
                                    "location_1"		=> $location,
                                    "flag_new_time_keeping"     => "1" // flag new cronjob
								);
								if($r->date == $date){
									$val["flag_regular_or_excess"] 	= "excess";
									$val["work_schedule_id"] 		= "-2";
									$val["tardiness_min"] 			= 0;
									$val["late_min"] 				= 0;
								}
								$insert = $this->db->insert("employee_time_in",$val);
							}
							else{
								return FALSE;
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
						
						if(strtotime($check_bet_timein) <= strtotime($current_date) && strtotime($current_date) <= strtotime($add_oneday_timein) && $r->time_in != "" && $r->lunch_out != "" && $r->lunch_in != "" && $r->time_out != ""){

							/* CHECK TIME IN START */
							$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);
							if($wst != FALSE){
								// new start time
								$nwst 					= $date." ".$wst;
								$check_diff_total_hours = (strtotime($nwst) - strtotime($current_date)) / 3600;
							}
							
							// insert time in log
							$val = array(
								"emp_id"			=> $r_emp->emp_id,
								"comp_id"			=> $r_emp->company_id,
								"date"				=> $date,
								"time_in"			=> $current_date,
								// "source" 			=> $source."-time in",
								"location" 			=> $locloc,
								"late_min" 			=> $late_min,
								"work_schedule_id" 	=> $work_schedule_id,
								"flag_on_leave" 	=> $ileave,
								"source"			=> 'mobile',
								"time_in_status"	=> "pending",
								"corrected"			=> "Yes",
								"mobile_clockin_status" => "pending",
                                "location_1"		=> $location,
                                "flag_new_time_keeping"     => "1" // flag new cronjob
							);
							$insert = $this->db->insert("employee_time_in",$val);
							
							if($insert){
								$w2 = array(
									"a.payroll_cloud_id"	=> $emp_no,
									"eti.date"				=> $date
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
								"eti.emp_id"				=> $emp_id,
								"eti.comp_id"				=> $comp_id,
								"eti.employee_time_in_id"	=> $r->employee_time_in_id
							);
							
							if($check_type == "lunch out"){
								
								// update lunch out value ================================================================ >>>> UPDATE LUNCH OUT VALUE
								$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
								if($min_log < $get_diff){
									$update_val = array(
												"lunch_out" => $current_date,
												// "source"  	=> $last_timein_source.",".$source."-".$check_type,
												"source"			=> 'mobile',
												"time_in_status"	=> "pending",
												"corrected"			=> "Yes",
												"mobile_lunchout_status" => "pending",
												"location_2"		=> $location,
												"location" 			=> $locloc,
                                                "flag_new_time_keeping"     => "1" // flag new cronjob
												);
									$this->db->where($where_update);
									$update = $this->db->update("employee_time_in AS eti",$update_val);
								}
								else{
									return FALSE;
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
									$update_val = array(
												"lunch_in" 		=> $current_date,
												"overbreak_min" => $overbreak_min,
												// "source"  		=> $last_timein_source.",".$source."-".$check_type,
												"source"			=> 'mobile',
												"time_in_status"	=> "pending",
												"corrected"			=> "Yes",
												"mobile_lunchin_status" => "pending",
												"location_3"		=> $location,
												"location" 			=> $locloc,
                                                "flag_new_time_keeping"     => "1" // flag new cronjob
												);
									$this->db->where($where_update);
									$update = $this->db->update("employee_time_in AS eti",$update_val);
								}
								else{
									return FALSE;
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
									$update_val = array(
												"time_out" 	=> $current_date,
												// "source"  	=> $last_timein_source.",".$source."-".$check_type,
												"source"			=> 'mobile',
												"time_in_status"	=> "pending",
												"corrected"			=> "Yes",
												"mobile_clockout_status" => "pending",
												"location_4"		=> $location,
												"location" 			=> $locloc,
                                                "flag_new_time_keeping"     => "1" // flag new cronjob
												);
									$this->db->where($where_update);
									$update = $this->db->update("employee_time_in AS eti",$update_val);

									// athan helper
									if($r){
										$date = $r->date;
										// payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
									}
								}
								else{
									return FALSE;
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
										// remove computation fro new cronjob
										// update tardiness for timein
										$tardiness_timein = 0;
										
										// update tardiness for break time
										$update_tardiness_break_time = 0;
										$duration_of_lunch_break_per_day = $this->duration_of_lunch_break_per_day($emp_id, $comp_id, $work_schedule_id);
										$tardiness_a = 0; // (strtotime($r2->lunch_in) - strtotime($r2->lunch_out)) / 60;
										
										/*
										if($duration_of_lunch_break_per_day < $tardiness_a){
											$update_tardiness_break_time = $tardiness_a - $duration_of_lunch_break_per_day;
										}
										
									
										if($r2->lunch_in == "" || $r2->lunch_out ==""){
											$update_tardiness_break_time = $duration_of_lunch_break_per_day;
											
										}*/
										
										// update total tardiness
										$update_tardiness = $tardiness_timein + $update_tardiness_break_time;
										
										// update undertime
										$update_undertime = 0;
										if(strtotime(date("H:i:s",strtotime($r2->time_in))) < strtotime($workday_settings_start_time)){
											$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id, $work_schedule_id);
											$workday_settings_end_time = date("H:i:s",strtotime($r2->time_in." +{$hours_worked} hour"));
										}
										if(strtotime($r2->time_out) < strtotime($workday_settings_end_time)){
                                            $new_end_time = date("Y-m-d",strtotime($r2->time_out))." ".$workday_settings_end_time;
                                            
                                            // remove computation fro new cronjob
											// $update_undertime = (strtotime($new_end_time) - strtotime($r2->time_out)) / 60;
										}
										
										// update total hours
										$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r2->time_in)), $emp_id, $work_schedule_id);
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
									$update_total_hours_required = ((strtotime($r2->time_out) - strtotime($r2->time_in)) / 3600) - ($duration_of_lunch_break_per_day / 60);
									
									
									// if value is less then 0 then set value to 0
									if($update_tardiness < 0) $update_tardiness = 0;
									if($update_undertime < 0) $update_undertime = 0;
									if($update_total_hours < 0) $update_total_hours = 0;
									if($update_total_hours_required < 0) $update_total_hours_required = 0;
									
									
									// update employee time in logs
									$update_timein_logs = array(
														"tardiness_min"				=> $update_tardiness,
														"undertime_min"				=> $update_undertime,
														"total_hours"				=> $hours_worked,
														"total_hours_required"		=> $update_total_hours_required,
                                                        "flag_tardiness_undertime"	=> $flag_tu,
                                                        "flag_new_time_keeping"     => "1" // flag new cronjob
														);
									
									#attendance settings
									$att = $this->elm->calculate_attendance($comp_id,$r2->time_in,$r2->time_out);								
									if($att){
										$total_hours_worked 						= $this->elm->total_hours_worked($r2->time_out, $r2->time_in);
										$total_hours_worked 						= $this->elm->convert_to_hours($total_hours_worked);
										$update_timein_logs['lunch_in'] 			= null;
										$update_timein_logs['lunch_out'] 			= null;
										$update_timein_logs['total_hours_required'] = $total_hours_worked;
										$update_timein_logs['absent_min'] 			= ($hours_worked - $total_hours_worked) * 60;
										$update_timein_logs['late_min'] 			= 0;
										$update_timein_logs['tardiness_min'] 		= 0;
										$update_timein_logs['undertime_min'] 		= 0;
									}
									$this->db->where($where_update);
									$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
								}
								return ($q2) ? $q2->row() : FALSE;
							}else{
								
								$insert = FALSE;
								$get_diff = (strtotime($current_date) - strtotime($r->time_out)) / 60;
								if($min_log < $get_diff){
									$val = array(
										"emp_id"			=> $emp_id,
										"comp_id"			=> $comp_id,
										"date"				=> $date,
										"time_in"			=> $current_date,
										// "source" 			=> $source."-time in",
										"location" 			=> $locloc,
										"late_min" 			=> $late_min,
										"work_schedule_id" 	=> $work_schedule_id,
										"flag_on_leave" 	=> $ileave,
										"source"			=> 'mobile',
										"time_in_status"	=> "pending",
										"corrected"			=> "Yes",
										"mobile_clockin_status" => "pending",
                                        "location_1"		=> $location,
                                        "flag_new_time_keeping"     => "1" // flag new cronjob
									);

									$insert = $this->db->insert("employee_time_in",$val);	
								}
								else{
									return FALSE;
								}

								if($insert){
									$w2 = array(
										"a.payroll_cloud_id"	=> $emp_no,
										"eti.date" 				=> $date
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
		 * Insert Time In Log
		 * @param unknown_type $date
		 * @param unknown_type $emp_no
		 * @param unknown_type $min_log
		 * @param unknown_type $work_schedule_id
		 */
		public function insert_time_in($date,$emp_no,$min_log,$work_schedule_id,$check_type,$comp_id = 0,$source="",$currentdate="", $location=""){
			
			$locloc = $location;

			$shift_name = "";

			$w_emp 	= array(
					"a.payroll_cloud_id"	=> $emp_no,
					"a.user_type_id"		=> "5",
					"e.company_id" 			=> $comp_id
					);
			$s_emp 	= array(
					"emp_id",
					"company_id"
					);
			$this->edb->select($s_emp);
			$this->edb->where($w_emp);
			$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
			$q_emp 	= $this->edb->get("employee AS e");
			$r_emp 	= $q_emp->row();
			$emp_id = $r_emp->emp_id;
			
			$w_payroll_group 	= array(
								"emp_id"	=> $emp_id
								);
			$s_payroll_group 	= array(
								"payroll_group_id"
								);
			$this->db->select($s_payroll_group);
			$this->db->where($w_payroll_group);
			$q_payroll_group = $this->db->get("employee_payroll_information");
			$r_payroll_group = $q_payroll_group->row();
			
			if($q_payroll_group->num_rows() > 0){
				$payroll_group = $r_payroll_group->payroll_group_id;
			}else{
				// return FALSE;
			}
			$payroll_group = 0;
			
			// check number of breaks
			$number_of_breaks_per_day 	= 0;
			$comp_add 					= $this->get_company_address($comp_id);
			
			# UNIFORM WORKING DAYS
			$w_uwd = array(
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id
			);
			$this->db->where($w_uwd);
			$q_uwd = $this->db->get("regular_schedule");
			$r_uwd = $q_uwd->row();
			if($q_uwd->num_rows() > 0){
				$number_of_breaks_per_day 	= $r_uwd->break_in_min;
				$shift_name					= "regular schedule";
			}
			else{
				# WORKSHIFT SETTINGS
				$w_ws = array(
					"work_schedule_id"	=> $work_schedule_id,
					"company_id"		=> $comp_id
				);
				$this->db->where($w_ws);
				$q_ws = $this->db->get("split_schedule");
				$r_ws = $q_ws->row();
				if($q_ws->num_rows() > 0){
					$number_of_breaks_per_day 	= $r_ws->number_of_breaks_per_shift;
					$shift_name 				= "split_schedule";
				}
				else{
					# FLEXIBLE HOURS
					$w_fh = array(
						"work_schedule_id"	=> $work_schedule_id,
						"company_id"		=> $comp_id
					);
					$this->db->where($w_fh);
					$q_fh = $this->db->get("flexible_hours");
					$r_fh = $q_fh->row();
					if($q_fh->num_rows() > 0){
						$number_of_breaks_per_day = $r_fh->duration_of_lunch_break_per_day;
						$shift_name = "flexible hours";
					}
				}
			}
			
			$late_min = 0;
			if($check_type=="time in"){
				$late_min = $this->elm->late_min($comp_id, $date, $emp_id, $work_schedule_id);
			}

			#check employee on leave
			$onleave = check_leave_appliction($date,$emp_id,$comp_id);
			$ileave = 'no';
			if($onleave){
				$ileave = 'yes';
			}
			
			// check if breaktime is 0
			if($number_of_breaks_per_day == 0){
				// check employee time in

			
				if($currentdate){
					$current_date = $currentdate;
				}
				else{
					$current_date = date("Y-m-d H:i:00");
				}

				$w 				= array(
								"a.payroll_cloud_id"=>$emp_no,
								"a.user_type_id"=>"5",
								"eti.comp_id" => $comp_id,
								"eti.status" => "Active",
								);
				
				$this->edb->where($w);
				$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
				$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
				$this->edb->join("employee_payroll_information AS epi","e.emp_id = epi.emp_id","INNER");
				$this->db->order_by("eti.time_in","DESC");
				$q = $this->edb->get("employee_time_in AS eti",1,0);
				$r = $q->row();
				
				// check rest day
				$workday 			= date("l",strtotime($date));
				$check_rest_day		= $this->check_rest_day($workday,$work_schedule_id,$comp_id);
				if($check_rest_day){
					return $this->rest_day_flex($comp_id,$work_schedule_id,$current_date,$r,$emp_id,$check_type,$date,$source, $location);
				}

				$locloc = ($r) ? $r->location . " | " . $location : $location;

				if($q->num_rows() == 0){

					/* CHECK TIME IN START */
					$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);
					if($wst != FALSE){
						// new start time
						$nwst 					= $date." ".$wst;
						$check_diff_total_hours = (strtotime($nwst) - strtotime($current_date)) / 3600;
					}
					
					// insert time in log
					$val = array(
						"emp_id"			=> $emp_id,
						"comp_id"			=> $comp_id,
						"date"				=> $date,
						"time_in"			=> $current_date,
						// "source" 			=> $source,
						"late_min" 			=> $late_min,
						"location" 			=> $locloc,
						"work_schedule_id" 	=> $work_schedule_id,
						"flag_on_leave" 	=> $ileave,
						"source"			=> 'mobile',
						"time_in_status"	=> "pending",
						"corrected"			=> "Yes",
						"mobile_clockin_status" => "pending",
						"location_1"		=> $location
					);
					
					$insert = $this->db->insert("employee_time_in",$val);
					
					if($insert){
						$w2 = array(
							"a.payroll_cloud_id"=> $emp_no,
							"eti.date"			=> $date
						);
						
						$this->edb->where($w2);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
						
						return ($q2) ? $q2->row() : FALSE ;
					}
				}else{

					//"source"  	=> $last_timein_source.",".$source."-".$check_type,
					$last_timein_source = $r->source;
					// get date time in to date time out
					$workday 			= date("l",strtotime($r->time_in));
					$payroll_group_id 	= $r->payroll_group_id;
					// check rest day
					$check_rest_day 	= $this->check_rest_day($workday,$work_schedule_id,$comp_id);
					
					if($check_rest_day){
						// global where update data
						$where_update = array(
							"eti.emp_id"				=> $emp_id,
							"eti.comp_id"				=> $comp_id,
							"eti.employee_time_in_id"	=> $r->employee_time_in_id
						);
						
						if($check_type == "time out"){
							// update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE FOR REST DAY
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							if($min_log < $get_diff){
								$update_val = array(
											"time_out" 	=> $current_date,
											// "source"  	=> $last_timein_source.",".$source."-".$check_type,
											"source"			=> 'mobile',
											"time_in_status"	=> "pending",
											"corrected"			=> "Yes",
											"mobile_clockout_status" => "pending",
											"location_4"		=> $location,
											"location" 			=> $locloc,
											);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);

								// athan helper
								if($r){
									$date = $r->date;
									// payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
								}
							}
							else{
								return FALSE;
							}
							
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							// update total hours and total hours required rest day
							$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
							if($get_total_hours < 0) $get_total_hours = 0;
							
							$update_timein_logs = array(
												"tardiness_min"			=> 0,
												"undertime_min"			=> 0,
												"total_hours"			=> $get_total_hours,
												"total_hours_required"	=> $get_total_hours
												);
							$this->db->where($where_update);
							$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
							
							return ($q2) ? $q2->row() : FALSE ;
							exit;
							
						}
						else if($check_type == "time in"){
							
							/* CHECK TIME IN START */
							$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);
							if($wst != FALSE){
								// new start time
								$nwst = $date." ".$wst;
								$check_diff_total_hours = (strtotime($nwst) - strtotime($current_date)) / 3600;
							}
							// insert time in value for rest day ============================================== >>> INSERT TIME IN VALUE FOR REST DAY
							$insert 	= FALSE;
							$get_diff 	= (strtotime($current_date) - strtotime($r->time_out)) / 60;
							if($min_log < $get_diff){
								$val = array(
									"emp_id"			=> $emp_id,
									"comp_id"			=> $comp_id,
									"date"				=> $date,
									"time_in"			=> $current_date,	
									// "source" 			=> $source."-time in",
									"location" 			=> $locloc,
									"work_schedule_id" 	=> $work_schedule_id,
									"flag_on_leave" 	=> $ileave,
									"source"			=> 'mobile',
									"time_in_status"	=> "pending",
									"corrected"			=> "Yes",
									"mobile_clockin_status" => "pending",
									"location_1"		=> $location
								);
								$insert = $this->db->insert("employee_time_in",$val);	
							}
							else{
								return FALSE;
							}
							
							if($insert){
								$w2 = array(
									"a.payroll_cloud_id"	=> $emp_no,
									"eti.date"				=> $date
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
					
					$get_hoursworked 				= $this->get_hoursworked($work_schedule_id,$comp_id)->total_hours_for_the_day;
					// check workday settings
					$workday_settings_start_time 	= $this->check_workday_settings_start_time($workday,$work_schedule_id,$comp_id);
					if($workday_settings_start_time != FALSE){
						if(strtotime($r->time_in) < strtotime($workday_settings_start_time)){
							$workday_settings_start_time = date("H:i:s",strtotime($r->time_in));
						}
					}
					else{
						$workday_settings_start_time = date("H:i:s",strtotime($r->time_in)); 
					}
					$workday_settings_end_time = date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
					
					if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
						
						// for night shift time in and time out value for working day
						$check_bet_timein = $date." ".$workday_settings_start_time;
						$check_bet_timeout = date("Y-m-d",strtotime($date. "+1 day"))." ".$workday_settings_end_time;
					}else{
						
						// for day shift time in and time out value for working day
						$check_bet_timein = $date." ".$workday_settings_start_time;
						$check_bet_timeout = $date." ".$workday_settings_end_time;
					}
					
					// check between date time in to date time out
					$add_oneday_timein = date("Y-m-d",strtotime($date." +1 day"))." ".$workday_settings_start_time;
		
					if(strtotime($check_bet_timein) <= strtotime($r->time_in) && strtotime($r->time_in) <= strtotime($add_oneday_timein)){
						
						// global where update data
						$where_update = array(
							"eti.emp_id"=>$emp_id,
							"eti.comp_id"=>$comp_id,
							"eti.employee_time_in_id"=>$r->employee_time_in_id
						);
						
						if($check_type == "time out"){
							// update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE
							$update = FALSE;
							
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							
							if($min_log < $get_diff){
								$update_val = array(
											"time_out" => $current_date,
											// "source"  	=> $last_timein_source.",".$source."-".$check_type,
											"source"			=> 'mobile',
											"time_in_status"	=> "pending",
											"corrected"			=> "Yes",
											"mobile_clockout_status" => "pending",
											"location_4"		=> $location,
											"location" 			=> $locloc,
											);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);

								// athan helper
								if($r){
									$date = $r->date;
									// payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
								}
							}
							else{
								return FALSE;
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
								$update_undertime 	= 0;
								$hours_worked 		= $this->get_hours_worked($date, $emp_id, $work_schedule_id);
								$startime 			= date('Y-m-d H:i:s',strtotime($date." ".$workday_settings_start_time));
								$hours 				= $hours_worked * 60;
								$endtime 			= date('Y-m-d H:i:s',strtotime($startime." +{$hours} minutes"));
								
								
								if($r2->time_out < $endtime){
									$update_undertime  = $this->total_hours_worked($endtime, $r2->time_out);									 
								}
								// check tardiness value
								$flag_tu 				= 0;
								$get_total_hours_worked = ($hours_worked / 2) + .5;
								
								// required hours worked only
								$new_total_hours = $this->get_tot_hours($emp_id,$comp_id,$r->time_in,$current_date,$hours_worked,$work_schedule_id); 
								
								$total1 = $this->total_hours_worked($r2->time_out, $r2->time_in);
								$render_hours  = $this->convert_to_hours($total1);
								// if value is less than 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($new_total_hours < 0) $new_total_hours = 0;
								if($get_total_hours < 0) $get_total_hours = 0;
								
								$update_timein_logs = array(
									"tardiness_min"				=> $update_tardiness,
									"undertime_min"				=> $update_undertime,
									"total_hours"				=> $hours_worked,
									"total_hours_required"		=> $render_hours ,
									"flag_tardiness_undertime"	=> $flag_tu
								);
								
								$att = $this->elm->calculate_attendance($comp_id,$r2->time_in,$r2->time_out);								
								if($att){
									$total_hours_worked = $this->elm->total_hours_worked($r2->time_out, $r2->time_in);
									$total_hours_worked = $this->elm->convert_to_hours($total_hours_worked);
									$update_timein_logs['lunch_in'] = null;
									$update_timein_logs['lunch_out'] = null;
									$update_timein_logs['total_hours_required'] = $total_hours_worked;
									$update_timein_logs['absent_min'] = ($hours_worked - $total_hours_worked) * 60;
									$update_timein_logs['late_min'] = 0;
									$update_timein_logs['tardiness_min'] = 0;
									$update_timein_logs['undertime_min'] = 0;
								}
								
								$flex_rules = $this->get_flex_rules($comp_id,$work_schedule_id);
								if($flex_rules){
									$break_rules 	= $flex_rules->break_rules;
									$assumed_breaks = $flex_rules->assumed_breaks;
									$assumed_breaks = $assumed_breaks * 60;
									
									if($break_rules == "assumed"){
										$number_of_breaks_b = $this->elmf->check_break_time_flex($work_schedule_id,$comp_id,false);
										$new_lunch_out = strtotime($r2->time_in."+ ".$assumed_breaks." minutes");
										$new_lunch_out = date("Y-m-d H:i:s",$new_lunch_out);
										$update_timein_logs['lunch_out'] = $new_lunch_out;
											
										$new_lunch_in	= strtotime($new_lunch_out."+ ".$number_of_breaks_b." minutes");
										$new_lunch_in 	= date("Y-m-d H:i:s",$new_lunch_in);
										$update_timein_logs['lunch_in'] = $new_lunch_in;
									}
								}
								
								if($r->flag_regular_or_excess == "excess"){
									$update_timein_logs["tardiness_min"]	= 0;
									$update_timein_logs["undertime_min"]	= 0;
									$update_timein_logs["lunch_out"]		= Null;
									$update_timein_logs["lunch_in"]			= Null;
									$update_timein_logs["absent_min"]		= 0;
									$update_timein_logs["late_min"]			= 0;
									$update_timein_logs["total_hours"]		= $render_hours;
								}
								
								$this->db->where($where_update);
								$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
							}
							
							return ($q2) ? $q2->row() : FALSE ;
						}else{
							/* qCHECK TIME IN START */
							$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);
							if($wst != FALSE){
								// new start time
								$nwst = $date." ".$wst;
								$check_diff_total_hours = (strtotime($nwst) - strtotime($current_date)) / 3600;
							}
							
							// insert time in log ================================================================ >>>> INSERT NEW TIME IN LOG SAME DATE
							$insert = FALSE;
							$get_diff = (strtotime($current_date) - strtotime($r->time_out)) / 60;
							if($min_log < $get_diff){
								$val = array(
									"emp_id"			=> $emp_id,
									"comp_id"			=> $comp_id,
									"date"				=> $date,
									"time_in"			=> $current_date,
									// "source" 			=> $source."-time in",
									"late_min" 			=> $late_min,
									"location" 			=> $locloc,
									"work_schedule_id" 	=> $work_schedule_id,
									"flag_on_leave" 	=> $ileave,
									"source"			=> 'mobile',
									"time_in_status"	=> "pending",
									"corrected"			=> "Yes",
									"mobile_clockin_status" => "pending",
									"location_1"		=> $location
								);
								if($r->date == $date){
									$val["flag_regular_or_excess"] 	= "excess";
									$val["work_schedule_id"] 		= "-2";
								}
								$insert = $this->db->insert("employee_time_in",$val);	
							}
							else{
								return FALSE;
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
					else{
						
						if(strtotime($check_bet_timein) <= strtotime($current_date) && strtotime($current_date) <= strtotime($add_oneday_timein) && $r->time_in != "" && $r->time_out != ""){
							
							/* CHECK TIME IN START */
							$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);
							if($wst != FALSE){
								// new start time
								$nwst = $date." ".$wst;
								$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
							}
							
							// insert time in log
							$val = array(
								"emp_id"=>$r_emp->emp_id,
								"comp_id"=>$r_emp->company_id,
								"date"=>$date,
								"time_in"=>$current_date,
								// "source" => $source."-time in",
								"late_min" => $late_min,
								"work_schedule_id" => $work_schedule_id,
								"source"			=> 'mobile',
								"time_in_status"	=> "pending",
								"corrected"			=> "Yes",
								"mobile_clockin_status" => "pending",
								"location_1"		=> $location,
								"location" 			=> $locloc,
							);
							$insert = $this->db->insert("employee_time_in",$val);
							
							if($insert){
								$w2 = array(
									"eti.emp_id"=>$r_emp->emp_id,
									"eti.date"=>$date
								);
								$this->db->where($w2);
								$this->db->order_by("eti.time_in","DESC");
								$q2 = $this->db->get("employee_time_in AS eti",1,0);
				
								return ($q2) ? $q2->row() : FALSE ;
							}	
						}else{
							
							// global where update data
							$where_update = array(
								"eti.emp_id"				=> $emp_id,
								"eti.comp_id"				=> $comp_id,
								"eti.employee_time_in_id"	=> $r->employee_time_in_id
							);
							
							if($check_type == "time out"){
								
								// update time out value ============================================== >>> UPDATE TIME OUT VALUE
								
								$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
								if($min_log < $get_diff){
									#$update_val = array();
								    $update_val = array(
								    			"time_out" 	=> $current_date,
								    			// "source"  	=> $last_timein_source.",".$source."-".$check_type,
								    			"source"			=> 'mobile',
												"time_in_status"	=> "pending",
												"corrected"			=> "Yes",
												"mobile_clockout_status" => "pending",
												"location_4"		=> $location,
												"location" 			=> $locloc,
								    			);
									$this->db->where($where_update);
									$update = $this->db->update("employee_time_in AS eti",$update_val);

									// athan helper
									if($r){
										$date = $r->date;
										// payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
									}
								}
								else{
									return FALSE;
								}
								
								$this->edb->where($where_update);
								$q2 = $this->edb->get("employee_time_in AS eti",1,0);
				
								// update total hours and total hours required rest day
								$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
								
								// tardiness and undertime value
								$update_tardiness = 0;
								$update_undertime = 0;
								
								// check tardiness value
								$flag_tu = 0;
								
								$hours_worked = $this->get_hours_worked(date("Y-m-d",strtotime($r->time_in)), $emp_id, $work_schedule_id);
								$get_total_hours_worked = ($hours_worked / 2) + .5;
								if($get_total_hours <= $get_total_hours_worked && $get_total_hours != 0){
									$update_tardiness = 0;
									$update_undertime = 0;
									$flag_tu = 1;
								}
								
								// required hours worked only
								$new_total_hours 	= $this->get_tot_hours($emp_id,$comp_id,$r->time_in,$current_date,$hours_worked,$work_schedule_id);
								$total1 			= $this->total_hours_worked($r->time_out, $r->time_in);
								$render_hours  		= $this->convert_to_hours($total1);
								
								// if value is less than 0 then set value to 0
								if($update_tardiness < 0) $update_tardiness = 0;
								if($update_undertime < 0) $update_undertime = 0;
								if($new_total_hours < 0) $new_total_hours = 0;
								if($get_total_hours < 0) $get_total_hours = 0;

								if($update){
									$update_timein_logs = array(
											"tardiness_min"				=> $update_tardiness,
											"undertime_min"				=> $update_undertime,
											"total_hours" 				=> $hours_worked,
											"total_hours_required"		=> $render_hours,
											"flag_tardiness_undertime"	=> $flag_tu,
											"time_out"					=> $current_date
									);
										
									$att = $this->elm->calculate_attendance($comp_id,$r->time_in,$current_date);
									if($att){
										$total_hours_worked = $this->elm->total_hours_worked($current_date, $r->time_in);
										$total_hours_worked = $this->elm->convert_to_hours($total_hours_worked);
										$update_timein_logs['lunch_in'] = null;
										$update_timein_logs['lunch_out'] = null;
										$update_timein_logs['total_hours_required'] = $total_hours_worked;
										$update_timein_logs['absent_min'] = ($hours_worked - $total_hours_worked) * 60;
										$update_timein_logs['late_min'] = 0;
										$update_timein_logs['tardiness_min'] = 0;
										$update_timein_logs['undertime_min'] = 0;
									}
										
									$this->db->where($where_update);
									$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
								}
								return ($q2) ? $q2->row() : FALSE ;
								
							}else{
								
								/* CHECK TIME IN START */
								$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);
								if($wst != FALSE){
									// new start time
									$nwst 					= $date." ".$wst;
									$check_diff_total_hours = (strtotime($nwst) - strtotime($current_date)) / 3600;
								}
								
								// insert time in log ================================================================ >>>> INSERT NEW TIME IN LOG SAME DATE
								$insert = FALSE;
								$get_diff = (strtotime($current_date) - strtotime($r->time_out)) / 60;
								if($min_log < $get_diff){
									$val = array(
										"emp_id"=>$emp_id,
										"comp_id"=>$comp_id,
										"date"=>$date,
										"time_in"=>$current_date,
										// "source" => $source."-time in",
										"work_schedule_id" => $work_schedule_id,
										"flag_on_leave" => $ileave,
										"source"			=> 'mobile',
										"time_in_status"	=> "pending",
										"corrected"			=> "Yes",
										"mobile_clockin_status" => "pending",
										"location_1"		=> $location,
										"location" 			=> $locloc,
									);
									$insert = $this->db->insert("employee_time_in",$val);	
								}
								else{
									return FALSE;
								}
								
								if($insert){
									$w2 = array(
										"eti.emp_id"=>$emp_id,
										"eti.date"=>$date
									);
									$this->db->where($w2);
									$this->db->order_by("eti.time_in","DESC");
									$q2 = $this->db->get("employee_time_in AS eti",1,0);
					
									return ($q2) ? $q2->row() : FALSE ;
								}else{
									$this->db->where($where_update);
									$q2 = $this->db->get("employee_time_in AS eti",1,0);
					
									return ($q2) ? $q2->row() : FALSE ;
								}
								
							}
						}
					}
				}
			}
			else{

				// check employee time in
				if($currentdate){
					$current_date = $currentdate;
				}
				else{
					$current_date = date("Y-m-d H:i:00");
				}
				$s 		= array(
						"eti.lunch_out",
						"eti.time_in",
						"eti.time_out",
						"eti.lunch_in",
						"eti.employee_time_in_id",
						"eti.overbreak_min",
						"eti.late_min",
						"eti.tardiness_min",
						"eti.undertime_min",
						"eti.absent_min"
						);

				$w 		= array(
						"a.payroll_cloud_id"=> $emp_no,
						"a.user_type_id"	=> "5",
						"eti.comp_id"			=> $comp_id,
						"eti.status"		=> "Active"
						);

				$this->edb->where($w);
				$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
				$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
				$this->db->order_by("eti.time_in","DESC");
				$q = $this->edb->get("employee_time_in AS eti",1,0);
				$r = $q->row();
				
				// check rest day
				$workday 			= date("l",strtotime($date));
				$check_rest_day		= $this->check_rest_day($workday,$work_schedule_id,$comp_id);
				if($check_rest_day){
					return $this->rest_day_flex($comp_id,$work_schedule_id,$current_date,$r,$emp_id,$check_type,$date,$source, $location);
				}

				$overbreak_min = 0;

				if($check_type == "lunch in"){
					$overbreak_min = $this->elm->overbreak_min($comp_id,$date,$emp_id,$work_schedule_id,$r->lunch_out);
				}

				$locloc = ($r) ? $r->location . " | " . $location : $location;

				if($q->num_rows() == 0){

					/* CHECK TIME IN START */
					$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);
					
					if($wst != FALSE){
						// new start time
						$nwst = $date." ".$wst;
						$check_diff_total_hours = (strtotime($nwst) - strtotime($current_date)) / 3600;
					}
					
					// insert time in log
					$val 	= array(
							"emp_id"			=> $emp_id,
							"comp_id"			=> $comp_id,
							"date"				=> $date,
							"time_in"			=> $current_date,
							// "source" 			=> $source."-time in",
							"late_min" 			=> $late_min,
							"location" 			=> $locloc,
							"work_schedule_id" 	=> $work_schedule_id,
							"flag_on_leave" 	=> $ileave,
							"source"			=> 'mobile',
							"time_in_status"	=> "pending",
							"corrected"			=> "Yes",
							"mobile_clockin_status" => "pending",
							"location_1"		=> $location
							);
					$insert = $this->db->insert("employee_time_in",$val);
					
					if($insert){
						$w2 = array(
							"a.payroll_cloud_id"=> $emp_no,
							"eti.date"			=> $date
							);
						$this->edb->where($w2);
						$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
						$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
						$q2 = $this->edb->get("employee_time_in AS eti",1,0);
						return ($q2) ? $q2->row() : FALSE ;
					}

				}
				else{
					//"source"  	=> $last_timein_source.",".$source."-".$check_type,
					$last_timein_source = $r->source;
					// get date time in to date time out
					$workday = date("l",strtotime($r->time_in));
					$payroll_group_id = 0;
					
					// check rest day
					$check_rest_day = $this->check_rest_day($workday,$work_schedule_id,$comp_id);
					if($check_rest_day){
						// global where update data
						$where_update 	= array(
										"eti.emp_id"				=> $emp_id,
										"eti.comp_id"				=> $comp_id,
										"eti.employee_time_in_id"	=> $r->employee_time_in_id
										);
						if($check_type == "time out"){
							
							// update time out value for rest day ============================================== >>> UPDATE TIME OUT VALUE FOR REST DAY
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							if($min_log < $get_diff){
								$update_val = array(
											"time_out"  => $current_date,
											// "source"  	=> $last_timein_source.",".$source."-".$check_type,
											"source"			=> 'mobile',
											"time_in_status"	=> "pending",
											"corrected"			=> "Yes",
											"mobile_clockout_status" => "pending",
											"location_4"		=> $location,
											"location"			=> $locloc
											);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);

								// athan helper
								if($r){
									$date = $r->date;
									// payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
								}
							}
							else{
								return FALSE;
							}
							
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							// update total hours and total hours required rest day
							$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
							if($get_total_hours < 0){
								$get_total_hours = 0;
							}
							
							$update_timein_logs = array(
												"tardiness_min"			=> 0,
												"undertime_min"			=> 0,
												"total_hours"			=> $get_total_hours,
												"total_hours_required"	=> $get_total_hours,
												// "source"  				=> $last_timein_source.",".$source."-".$check_type,
												"source"				=> 'mobile'
												);
							$this->db->where($where_update);
							$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
							
							return ($q2) ? $q2->row() : FALSE ;
							exit;
						}
						else if($check_type == "time in"){
							
							/* CHECK TIME IN START */
							$wst = $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);
							if($wst != FALSE){
								// new start time
								$nwst = date("Y-m-d {$wst}");
								$check_diff_total_hours = (strtotime($nwst) - strtotime($current_date)) / 3600;
								// if($check_diff_total_hours <= 1 && $check_diff_total_hours >= 0) $current_date = $nwst;
							}
							
							// insert time in value for rest day ============================================== >>> INSERT TIME IN VALUE FOR REST DAY
							$insert = FALSE;
							$get_diff = (strtotime($current_date) - strtotime($r->time_out)) / 60;
							if($min_log < $get_diff){
								$val = array(
									"emp_id"	=> $emp_id,
									"comp_id"	=> $comp_id,
									"date"		=> $date,
									"time_in"	=> $current_date,
									// "source" 	=> $source."-time in",
									// "location" 	=> $comp_add
									"source"			=> 'mobile',
									"time_in_status"	=> "pending",
									"corrected"			=> "Yes",
									"mobile_clockin_status" => "pending",
									"location_1"		=> $location,
									"location"			=> $locloc
								);
								$insert = $this->db->insert("employee_time_in",$val);	
							}
							else{
								return FALSE;
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
					
					$get_hoursworked 				= $this->get_hoursworked($work_schedule_id,$comp_id)->total_hours_for_the_day; 
					
					// check workday settings
					$workday_settings_start_time 	= $this->check_workday_settings_start_time($workday,$work_schedule_id,$comp_id);
					
					if($workday_settings_start_time != FALSE){
						
						$workday_settings_start_time 	= $workday_settings_start_time;
						$workday_settings_end_time 		= date("H:i:s",strtotime($workday_settings_start_time." +{$get_hoursworked} Hour"));
						
						if(date("A",strtotime($workday_settings_start_time)) == "PM" && date("A",strtotime($workday_settings_end_time)) == "AM"){
							// for night shift time in and time out value for working day
							$check_bet_timein 	= date("Y-m-d")." ".$workday_settings_start_time;
							$check_bet_timeout 	= date("Y-m-d",strtotime("+1 day"))." ".$workday_settings_end_time;
						}
						else{
							// for day shift time in and time out value for working day
							$check_bet_timein 	= date("Y-m-d")." ".$workday_settings_start_time;
							$check_bet_timeout 	= date("Y-m-d")." ".$workday_settings_end_time;
						}
						
						// check between date time in to date time out
						$add_oneday_timein = date("Y-m-d",strtotime("+1 day"))." ".$workday_settings_start_time;
					}

					//*** TIME IN IS HERE SAMOKA TAASAS CODE ===> if molapas ang iyang timein sa previous niya na timeIn + 1day
					if(strtotime($check_bet_timein) <= strtotime($current_date) && strtotime($current_date) <= strtotime($add_oneday_timein) && $r->time_in != "" && $r->lunch_out != "" && $r->lunch_in != "" && $r->time_out != ""){
						
						$excess = false;

						if($date == $r->date){
							$excess = true;
						}
						
						$time_in = $this->latest_time_in_flex_time_in($work_schedule_id,$comp_id,$emp_id,$date,$current_date,$source,$comp_add,$ileave,$emp_no,$excess);
						return ($time_in) ? $time_in : false;
					}
					//*** TIME IN IS HERE SAMOKA TAASAS CODE ===> if less than ang iyang timein sa previous niya na timeIn + 1day
					else{
						
						if($r->flag_regular_or_excess == "excess"){
							if($date == $r->date){
								$check_type = "time out";
							}
						}
						// global where update data
						$where_update 	= array(
										"eti.emp_id"				=> $emp_id,
										"eti.comp_id"				=> $comp_id,
										"eti.employee_time_in_id"	=> $r->employee_time_in_id,
										"eti.status"				=> "Active",
										);
						if($check_type == "lunch out"){
							// update lunch out value ================================================================ >>>> UPDATE LUNCH OUT VALUE
							$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
							if($min_log < $get_diff){
								$update_val = array(
											"lunch_out" 			=> $current_date,
											// "source"  				=> $last_timein_source.",".$source."-".$check_type,
											"source"			=> 'mobile',
											"time_in_status"	=> "pending",
											"corrected"			=> "Yes",
											"mobile_lunchout_status" => "pending",
											"location_2"		=> $location,
											"location"			=> $locloc
											);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);
							}
							else{
								return FALSE;
							}
							
							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
							
							return ($q2) ? $q2->row() : FALSE;
						}
						else if($check_type == "lunch in"){
							
							// update lunch in value ================================================================ >>>> UPDATE LUNCH IN VALUE
							$get_diff = (strtotime($current_date) - strtotime($r->lunch_out)) / 60;
							if($min_log < $get_diff){
								// GET TOTAL BREAKS
								$number_of_breaks_b = $this->elmf->check_break_time_flex($work_schedule_id,$comp_id,false);
								$overbreak_min		= 0;
								if($get_diff > $number_of_breaks_b){
									$overbreak_min 	= $get_diff - $number_of_breaks_b;
									$tardiness_min  = $r->late_min + $overbreak_min;
								}
								$update_val 		= array(
													"lunch_in" 		=> $current_date,
													"overbreak_min" => $overbreak_min,
													"tardiness_min" => $tardiness_min,
													// "source"  		=> $last_timein_source.",".$source."-".$check_type,
													"source"			=> 'mobile',
													"time_in_status"	=> "pending",
													"corrected"			=> "Yes",
													"mobile_lunchin_status" => "pending",
													"location_3"		=> $location,
													"location"			=> $locloc
													);
								$this->db->where($where_update);
								$update = $this->db->update("employee_time_in AS eti",$update_val);
							}
							else{
								return FALSE;
							}

							$this->edb->where($where_update);
							$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
							$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
							$q2 = $this->edb->get("employee_time_in AS eti",1,0);
			
							return ($q2) ? $q2->row() : FALSE;
						}
						else if($check_type == "time out"){
							// update time out value ================================================================ >>>> UPDATE TIME OUT VALUE
							
							$update 			= FALSE;
							$current_date_out 	= $current_date;
							if($r->flag_regular_or_excess == "excess"){
								$render_hours = ((strtotime($current_date) - strtotime($r->time_in)) / 60) / 60;
								$hours_worked  = $render_hours;
							}
							$get_diff 			= (strtotime($current_date) - strtotime($r->lunch_in)) / 60;
							if($min_log < $get_diff){
								$update_val = array(
											"time_out" 				=> $current_date,
											// "source"  				=> $last_timein_source.",".$source."-".$check_type,
											"source"			=> 'mobile',
											"time_in_status"	=> "pending",
											"corrected"			=> "Yes",
											"mobile_clockout_status" => "pending",
											"location_4"		=> $location,
											"location"			=> $locloc
											);
								$this->db->where($where_update);

								$update = $this->db->update("employee_time_in AS eti",$update_val);

								// athan helper
								if($r){
									$date = $r->date;
									// payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
								}
							}
							else{
								return FALSE;
							}
							
							$s 	= array(
									"eti.lunch_out",
									"eti.time_in",
									"eti.time_out",
									"eti.lunch_in",
									"eti.employee_time_in_id",
									"eti.overbreak_min",
									"eti.late_min",
									"eti.tardiness_min",
									"eti.undertime_min",
									"eti.absent_min"
							);
							$this->db->select($s);
							$this->db->where($where_update);
							$q2 = $this->db->get("employee_time_in AS eti",1,0);
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
								}
								else{
									// ** UPDATE UNDER TIME AND TOTAL HOURS WORKED
									$update_timein_logs 		= array();
									$current_date_timeout		= $current_date;
									$current_date_timeout_str	= strtotime($current_date);
									$current_date				= $r->time_in;
									$lunch_out_time_punch		= $r->lunch_out;
									$lunch_in_time_punch		= $r->lunch_in;
									$current_date_str			= strtotime($r->time_in);
									$current_date_out_str		= strtotime($current_date_out);
									// GET LATEST TIMEIN
									$wst 	= $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);
									$nwst	= "";
									if($wst != FALSE){
										// new start time
										$nwst 					= date("Y-m-d {$wst}");
										$check_diff_total_hours = (strtotime($nwst) - strtotime($current_date)) / 3600;
									}
									
									// GET TOTAL BREAKS
									$number_of_breaks_b 		= $this->elmf->check_break_time_flex($work_schedule_id,$comp_id,false);
									
									// GET TOTAL HOURS WORKED
									$hours_worked 			= $this->get_hours_worked(date("Y-m-d",strtotime($current_date)), $emp_id, $work_schedule_id);
									$hours_worked_wtb_min	= $hours_worked * 60;
									$hours_worked 			= $hours_worked - ($number_of_breaks_b/60);
									
									// ASSUMED HALFDAY HOUR
									$half_day			= $hours_worked/2;
									$half_day_min		= $half_day * 60;
									
									// INIT ASSUMED LUNCHOUT AND LUNCHIN ==> this is the basis if we exclude the break in computing the late
									$nwst_str 	= strtotime($nwst);
									$nwst_b 	= $current_date;
									
									if($current_date_str > $nwst_str){
										$nwst_b = $nwst;
									}
									
									$new_lunch_out 		= strtotime($nwst_b."+ ".$half_day_min." minutes");
									$new_lunch_out 		= date("Y-m-d H:i:s",$new_lunch_out);
									$new_lunch_in		= strtotime($new_lunch_out."+ ".$number_of_breaks_b." minutes");
									$new_lunch_in 		= date("Y-m-d H:i:s",$new_lunch_in);
									$new_time_out		= strtotime($nwst_b."+ ".$hours_worked_wtb_min." minutes");
									$new_time_out 		= date("Y-m-d H:i:s",$new_time_out);
									
									$new_lunch_out_str	= strtotime($new_lunch_out);
									$new_lunch_in_str	= strtotime($new_lunch_in);
									$break_b			= $number_of_breaks_b + $r->overbreak_min;
									
									// SET THE LATE / TARDINESS HERE
									$tardiness_set 	= $this->elm->tardiness_settings($emp_id, $comp_id);
									
									// adjust for tardiness settings grace period
									$grace_period	= 0;
									if($tardiness_set){
										$grace_period = $tardiness_set;
									}
									
									$nwst_str 			= strtotime($nwst);
									$late_min			= 0;
									if($nwst_str < $current_date_str){
										$late_min = total_min_between($current_date,$nwst);
										$late_min = $late_min - $grace_period;
										$late_min = ($late_min > 0) ? $late_min : 0;
									}
									
									// COMPUTE FOR TOTAL HOURS WORKED
									$render_hours = total_min_between($current_date_timeout,$current_date);
									$render_hours = ($render_hours - $break_b)/60;
									
									$flex_rules = $this->get_flex_rules($comp_id,$work_schedule_id);
									if($flex_rules){
										$break_rules 	= $flex_rules->break_rules;
										$assumed_breaks = $flex_rules->assumed_breaks;
										$assumed_breaks = $assumed_breaks * 60;
										if($break_rules == "assumed"){
											$new_lunch_out 			= strtotime($nwst_b."+ ".$assumed_breaks." minutes");
											$new_lunch_out 			= date("Y-m-d H:i:s",$new_lunch_out);
											$new_lunch_in			= strtotime($new_lunch_out."+ ".$number_of_breaks_b." minutes");
											$new_lunch_in 			= date("Y-m-d H:i:s",$new_lunch_in);
											
											$new_lunch_out_str		= strtotime($new_lunch_out);
											$new_lunch_in_str		= strtotime($new_lunch_in);
											
											//*** UPDATE TARDINESS -> affect new rule dont add break **//
											// if timein between the assumed lunchOut and lunchIn
											if($new_lunch_out_str <= $current_date_str && $new_lunch_in_str >= $current_date_str){
												$late_min = total_min_between($new_lunch_out,$nwst);
												$late_min = $late_min - $grace_period;
												$late_min = ($late_min > 0) ? $late_min : 0;
												
												$update_timein_logs['lunch_out'] 	= null;
												$update_timein_logs['lunch_in'] 	= null;
												$break_b 							= total_min_between($new_lunch_in,$r->time_in);
											}
											// if time in after assumed lunchIn
											else if($new_lunch_in_str <= $current_date_str){
												$late_min = total_min_between($current_date,$nwst);
												$late_min = ($late_min - $number_of_breaks_b) - $grace_period;
												$late_min = ($late_min > 0) ? $late_min : 0;
												
												$update_timein_logs['lunch_out'] 	= null;
												$update_timein_logs['lunch_in'] 	= null;
												$break_b							= 0;
											}
											else{
												// if timeout between the assumed lunchOut and lunchIn
												if($new_lunch_out_str <= $current_date_out_str && $new_lunch_in_str >= $current_date_out_str){
													$update_timein_logs['lunch_out'] 	= null;
													$update_timein_logs['lunch_in'] 	= null;
													$break_b 							= total_min_between($current_date_out,$new_lunch_out);
												}
												else{
													$update_timein_logs['lunch_out'] 	= $new_lunch_out;
													$update_timein_logs['lunch_in'] 	= $new_lunch_in;
												}
											}
											// COMPUTE FOR TOTAL HOURS WORKED
											$render_hours = total_min_between($current_date_timeout,$current_date);
											$render_hours = ($render_hours - $break_b)/60;
										}
										else if($break_rules == "capture"){
											//*** UPDATE TARDINESS -> affect new rule dont add break **//
											// if timein between the assumed lunchOut and lunchIn
											if($new_lunch_out_str <= $current_date_str && $new_lunch_in_str >= $current_date_str){
												$late_min = total_min_between($new_lunch_out,$nwst);
												$late_min = $late_min - $grace_period;
												$late_min = ($late_min > 0) ? $late_min : 0;
											}
											// if time in after assumed lunchIn
											else if($new_lunch_in_str <= $current_date_str){
												$late_min = total_min_between($current_date,$nwst);
												$late_min = ($late_min - $number_of_breaks_b) - $grace_period;
												$late_min = ($late_min > 0) ? $late_min : 0;
											}
											
											//** if timeout before the init lunchin this means he/she is doing halfday as we discus 2/17/17 5:00 PM **//
											if($new_lunch_in_str > $current_date_str){
												$new_break_b	= total_min_between($lunch_in_time_punch, $lunch_out_time_punch);
											}
											
											// COMPUTE FOR TOTAL HOURS WORKED
											$render_hours = total_min_between($current_date_timeout,$current_date);
											$render_hours = ($render_hours - $break_b)/60;
										}else if($break_rules == "disable"){
											// COMPUTE FOR TOTAL HOURS WORKED
											$render_hours = total_min_between($current_date_timeout,$current_date);
											$render_hours = $render_hours/60;
										}
									}
									
									$update_undertime	  = 0;
									// if timeout after assumed break
									if($current_date_timeout_str < $new_lunch_out_str){
										$update_undertime = total_min_between($new_time_out,$current_date_timeout);
										$update_undertime = $update_undertime - $number_of_breaks_b;
									}
									// if timeout between the assumed break
									else if($current_date_timeout_str >= $new_lunch_out_str && $current_date_timeout_str <= $new_lunch_in_str){
										$update_undertime = total_min_between($new_time_out,$new_lunch_out);
										$update_undertime = $update_undertime - $number_of_breaks_b;
									}
									// if timeout after the assumed break
									else if($current_date_timeout_str > $new_lunch_in_str){
										$update_undertime = total_min_between($new_time_out,$current_date_timeout);
									}
								}
								
								// if value is less then 0 then set value to 0
								if($update_undertime < 0) 	$update_undertime 	= 0;
								if($render_hours < 0) 		$render_hours		= 0; 
								if($hours_worked < 0) 		$hours_worked		= 0; 
								
								// update employee time in logs
								$update_timein_logs['undertime_min']			= $update_undertime;
								$update_timein_logs['total_hours']				= $hours_worked;
								$update_timein_logs['total_hours_required']		= $render_hours;
								$update_timein_logs['flag_tardiness_undertime']	= $flag_tu;

								#attendance settings
								$att = $this->elm->calculate_attendance($comp_id,$r2->time_in,$r2->time_out);								
								if($att){
									$total_hours_worked 						= $this->elm->total_hours_worked($r2->time_out, $r2->time_in);
									$total_hours_worked 						= $this->elm->convert_to_hours($total_hours_worked);
									$update_timein_logs['lunch_in'] 			= null;
									$update_timein_logs['lunch_out'] 			= null;
									$update_timein_logs['total_hours_required'] = $total_hours_worked;
									$update_timein_logs['absent_min'] 			= ($hours_worked - $total_hours_worked) * 60;
									$update_timein_logs['late_min'] 			= 0;
									$update_timein_logs['tardiness_min'] 		= 0;
									$update_timein_logs['undertime_min'] 		= 0;
								}
								if($r->flag_regular_or_excess == "excess"){
									$update_timein_logs['total_hours_required'] = $total_hours_worked;
									$update_timein_logs['total_hours']			= $total_hours_worked;
									$update_timein_logs['lunch_in'] 			= null;
									$update_timein_logs['lunch_out'] 			= null;
									$update_timein_logs['absent_min'] 			= 0;
									$update_timein_logs['undertime_min'] 		= 0;
									$update_timein_logs['tardiness_min'] 		= 0;
									$update_timein_logs['overbreak_min'] 		= 0;
									$update_timein_logs['late_min'] 			= 0;
								}
								$this->db->where($where_update);
								$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
							}
							return ($r2) ? $r2 : FALSE;
						}else{
							$excess = false;
							if($date == $r->date){
								$excess = true;
							}
							
							$time_in = $this->latest_time_in_flex_time_in($work_schedule_id,$comp_id,$emp_id,$date,$current_date,$source,$comp_add,$ileave,$emp_no,$excess,$location);
							return ($time_in) ? $time_in : false;
						}
					}
				}
			}
		}
		
		public function latest_time_in_flex_time_in($work_schedule_id,$comp_id,$emp_id,$date,$current_date,$source,$comp_add,$ileave,$emp_no,$excess = false, $location=""){
			/* CHECK TIME IN START */
			$current_date_str	= strtotime($current_date);
			// GET LATEST TIMEIN
			$wst 	= $this->check_workday_settings_start_time(date("l"),$work_schedule_id,$comp_id);
			$nwst	= "";
			if($wst != FALSE){
				// new start time
				$nwst 					= date("Y-m-d {$wst}");
				$check_diff_total_hours = (strtotime($nwst) - strtotime(date("Y-m-d H:i:s"))) / 3600;
			}
			
			// GET TOTAL BREAKS
			$number_of_breaks_b = $this->elmf->check_break_time_flex($work_schedule_id,$comp_id,false);
			
			// GET TOTAL HOURS WORKED
			$hours_worked 		= $this->get_hours_worked(date("Y-m-d",strtotime($current_date)), $emp_id, $work_schedule_id);
			$hours_worked 		= $hours_worked - ($number_of_breaks_b/60);
			
			// ASSUMED HALFDAY HOUR
			$half_day			= $hours_worked/2;
			$half_day_min		= $half_day * 60;
			
			// INIT ASSUMED LUNCHOUT AND LUNCHIN ==> this is the basis if we exclude the break in computing the late
			$nwst_str 	= strtotime($nwst);
			$nwst_b 	= $current_date;
			if($current_date_str > $nwst_str){
				$nwst_b = $nwst;
			}
			$new_lunch_out 		= strtotime($nwst_b."+ ".$half_day_min." minutes");
			$new_lunch_out 		= date("Y-m-d H:i:s",$new_lunch_out);
			$new_lunch_in		= strtotime($new_lunch_out."+ ".$number_of_breaks_b." minutes");
			$new_lunch_in 		= date("Y-m-d H:i:s",$new_lunch_in);
			
			$new_lunch_out_str	= strtotime($new_lunch_out);
			$new_lunch_in_str	= strtotime($new_lunch_in);
			
			// SET THE LATE / TARDINESS HERE
			$tardiness_set 		= $this->elm->tardiness_settings($emp_id, $comp_id);
			
			// adjust for tardiness settings grace period
			$grace_period		= 0;
			if($tardiness_set){
				$grace_period 	= $tardiness_set;
			}
			
			$nwst_str 			= strtotime($nwst);
			$late_min			= 0;
			if($nwst_str < $current_date_str){
				$late_min = total_min_between($current_date,$nwst);
				$late_min = $late_min - $grace_period;
				$late_min = ($late_min > 0) ? $late_min : 0;
			}
			
			$flex_rules = $this->get_flex_rules($comp_id,$work_schedule_id);
			if($flex_rules){
				$break_rules 	= $flex_rules->break_rules;
				$assumed_breaks = $flex_rules->assumed_breaks;
				$assumed_breaks = $assumed_breaks * 60;
				if($break_rules == "assumed"){
					$new_lunch_out 			= strtotime($nwst_b."+ ".$assumed_breaks." minutes");
					$new_lunch_out 			= date("Y-m-d H:i:s",$new_lunch_out);
					$new_lunch_in			= strtotime($new_lunch_out."+ ".$number_of_breaks_b." minutes");
					$new_lunch_in 			= date("Y-m-d H:i:s",$new_lunch_in);
					
					$new_lunch_out_str		= strtotime($new_lunch_out);
					$new_lunch_in_str		= strtotime($new_lunch_in);
					
					//*** UPDATE TARDINESS -> affect new rule dont add break **//
					// if timein between the assumed lunchOut and lunchIn
					if($new_lunch_out_str <= $current_date_str && $new_lunch_in_str >= $current_date_str){
						$late_min = total_min_between($new_lunch_out,$nwst);
						$late_min = $late_min;
						$late_min = ($late_min > 0) ? $late_min : 0;
					}
					// if time in after assumed lunchIn
					else if($new_lunch_in_str <= $current_date_str){
						$late_min = total_min_between($current_date,$nwst);
						$late_min = ($late_min - $number_of_breaks_b) - $grace_period;
						$late_min = ($late_min > 0) ? $late_min : 0;
					}
				}
				else if($break_rules == "capture"){
					//*** UPDATE TARDINESS -> affect new rule dont add break **//
					// if timein between the assumed lunchOut and lunchIn
					if($new_lunch_out_str <= $current_date_str && $new_lunch_in_str >= $current_date_str){
						$late_min = total_min_between($new_lunch_out,$nwst);
						$late_min = $late_min - $grace_period;
						$late_min = ($late_min > 0) ? $late_min : 0;
					}
					// if time in after assumed lunchIn
					else if($new_lunch_in_str <= $current_date_str){
						$late_min = total_min_between($current_date,$nwst);
						$late_min = ($late_min - $number_of_breaks_b) - $grace_period;
						$late_min = ($late_min > 0) ? $late_min : 0;
					}
				}
			}
			// insert time in log
			$val 	= array(
					"emp_id"			=> $emp_id,
					"comp_id"			=> $comp_id,
					"date"				=> $date,
					"time_in"			=> $current_date,
					"work_schedule_id" 	=> $work_schedule_id,
					// "source" 			=> $source."-time in",
					"total_hours" 		=> $hours_worked,
					"late_min" 			=> $late_min,
					"tardiness_min"		=> $late_min,
					"location" 			=> $comp_add,	
					"flag_on_leave" 	=> $ileave,
					"source"			=> 'mobile',
					"time_in_status"	=> "pending",
					"corrected"			=> "Yes",
					"mobile_clockin_status" => "pending",
					"location_1"		=> $location
			);
			if($excess){
				
				$val["flag_regular_or_excess"] 	= "excess"; 
				$val["work_schedule_id"] 		= "-2"; 
				$val["tardiness_min"] 			= 0; 
				$val["late_min"] 				= 0;
			}
			$insert = $this->db->insert("employee_time_in",$val);
			
			if($insert){
				$w2 = array(
						"a.payroll_cloud_id"=> $emp_no,
						"eti.date"			=> $date
				);
				$this->edb->where($w2);
				$this->edb->join("employee AS e","eti.emp_id = e.emp_id","INNER");
				$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
				$this->db->order_by("eti.time_in","DESC");
				$q2 = $this->edb->get("employee_time_in AS eti",1,0);
					 
				return ($q2) ? $q2->row() : FALSE ;
			}
		}


		
		public function rest_day_flex($comp_id,$work_schedule_id,$current_date,$r,$emp_id,$check_type,$date,$source, $location=''){

			$min_log 			= 5;
			$l_source 			= "";

			$where_update   	= array(
								"eti.emp_id"				=> $emp_id,
								"eti.comp_id"				=> $comp_id
								);

			if($r){
				$where_update 	= array(
								"eti.emp_id"				=> $emp_id,
								"eti.comp_id"				=> $comp_id,
								"eti.employee_time_in_id"	=> $r->employee_time_in_id
								);
			}

			if($check_type != "time in"){
				

				$check_type == "time out";

				if($r){

					$l_source 	= $r->source;
					$get_diff = (strtotime($current_date) - strtotime($r->time_in)) / 60;
					$locloc = ($r) ? $r->location . " | " . $location : $location;
				}
				$get_total_hours = 0;
				if($r){
					$get_total_hours = (strtotime($current_date) - strtotime($r->time_in)) / 3600;
				}
				if($get_total_hours < 0){
					 $get_total_hours 	= 0;
				}

				$update_timein_logs 	= array(
										"time_out" 				=> $current_date,
										"tardiness_min"			=> 0,
										"undertime_min"			=> 0,
										"absent_min"			=> 0,
										"total_hours"			=> $get_total_hours,
										"total_hours_required"	=> $get_total_hours,
										// "source"  				=> $l_source.",".$source."-".$check_type,
										"source"				=> 'mobile',
										"time_in_status"		=> 'pending',
										"mobile_clockout_status"=> 'pending',
										"corrected"				=> 'Yes',
										"location_4"			=> $location,
										"location"				=> $locloc,
                                        "flag_new_time_keeping" => "1" // flag new cronjob
										);

				if($min_log < $get_diff){

					$this->db->where($where_update);
					$sql_update_timein_logs = $this->db->update("employee_time_in AS eti",$update_timein_logs);
				}
				$this->db->where($where_update);
				$q2 					= $this->db->get("employee_time_in AS eti",1,0);

				// athan helper
				if($date){
					// payroll_cronjob_helper($type='timesheet',$date,$emp_id,$comp_id);
				}

				return ($q2) ? $q2->row() : FALSE ;
			}
			else{

				$check_type == "time in";

				if($r){
					$get_diff 		= (strtotime($current_date) - strtotime($r->time_out)) / 60;
				}
				else{
					$get_diff 		= 10;
				}
				
				if($min_log < $get_diff){
					$val 			= array(
									"emp_id"		=> $emp_id,
									"comp_id"			=> $comp_id,
									"work_schedule_id"	=> "-1",
									"date"				=> $date,
									"time_in"			=> $current_date,
									// "source" 			=> $source."-".$check_type,
									"late_min" 			=> 0,
									"source"				=> 'mobile',
									"time_in_status"		=> 'pending',
									"mobile_clockin_status" => 'pending',
									"corrected"				=> 'Yes',
									"location_1"			=> $location,
									"location"				=> $location,
                                    "flag_new_time_keeping" => "1" // flag new cronjob
									);
					$insert 		= $this->db->insert("employee_time_in",$val);	
				}
				else{
					return FALSE;
				}

				if($insert){

					$this->db->where($where_update);
					$this->db->order_by("eti.time_in","DESC");
					$q2 		= $this->db->get("employee_time_in AS eti",1,0);
	
					return ($q2) ? $q2->row() : FALSE ;
				}
				else{

					$this->db->where($where_update);
					$q2 		= $this->db->get("employee_time_in AS eti",1,0);
	
					return ($q2) ? $q2->row() : FALSE ;
				}
			}
		}

		public function get_break_flex($comp_id,$work_schedule_id){
			$where_break_flex = array(
					"company_id" => $comp_id,
					"work_schedule_id" => $work_schedule_id,
			);
		
			$this->db->where($where_break_flex);
			$sql_break_flex = $this->db->get("flexible_hours");
			$row_break_flex = $sql_break_flex->row();
		
			if($sql_break_flex->num_rows() > 0){
				$breaktime_settings = $row_break_flex->duration_of_lunch_break_per_day; // convert to seconds
			} else{
				$breaktime_settings = 0;
			}
		
			return $breaktime_settings;
		}
		
		/**
		 * Check Rest Day
		 * @param unknown_type $workday
		 * @param unknown_type $work_schedule_id
		 * @param unknown_type $comp_id
		 */
		public function check_rest_day($workday,$work_schedule_id,$comp_id){
			$w = array(
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id,
				"rest_day"=>$workday
			);
			$this->db->where($w);
			$q = $this->db->get("rest_day");
			return ($q->num_rows() > 0) ? TRUE : FALSE ;
		}
		
		/**
		 * Check Workday Settings for start time
		 * @param unknown_type $workday
		 * @param unknown_type $work_schedule_id
		 * @param unknown_type $company_id
		 */
		public function check_workday_settings_start_time($workday,$work_schedule_id,$company_id){
			// check uniform working days
			$w = array(
				"work_schedule_id"	=> $work_schedule_id,
				"company_id"		=> $company_id,
				"days_of_work"		=> $workday,
				"status"			=> "Active"
			);
			$this->db->where($w);
			$q 		= $this->db->get("regular_schedule");
			$row 	= $q->row();
			
			if($row){
				return $row->work_start_time;
			}else{
				// workshift
				$w2 = array(
					"work_schedule_id"	=> $work_schedule_id,
					"company_id"		=> $company_id
				);
				
				$this->db->where($w2);
				$q2 	= $this->db->get("split_schedule");
				$row2 	= $q2->row();
				if($row2){
					$time_in = date('H:i:s');
					return $this->get_starttime($row2->split_schedule_id, $time_in);
				}
				else{
					$wf = array(
						"work_schedule_id"	=> $work_schedule_id,
						"company_id"		=> $company_id
					);
					$this->db->where($wf);
					$qf = $this->db->get("flexible_hours");
					$rf = $qf->row();
					if($rf){
						if($rf->latest_time_in_allowed != NULL || $rf->latest_time_in_allowed != ""){
							return ($rf->not_required_login != NULL || $rf->not_required_login != 0) ? $rf->latest_time_in_allowed : FALSE ;
						}else{
							return FALSE;
						}
					}else{
						return FALSE;	
					}
				}
			}
		}
		
		public function get_starttime($split_schedule_id,$time_in){
		
			$this->db->where('split_schedule_id',$split_schedule_id);
			$q2 = $this->db->get("schedule_blocks");
			$result = $q2->result();
			$time_in = date('H:i:s',strtotime($time_in));
			$arr = array();
			foreach($result as $row):
			$start_time = date('H:i:s',strtotime($row->start_time));
			$end_time = date('H:i:s', strtotime($row->end_time));
			if($time_in >= $start_time && $time_in <= $end_time):
			return $row->start_time;
			else:
			$arr[] = $start_time;
			endif;
			endforeach;
		
			foreach($arr as $key => $row2):
			if($time_in <= $row2){
				return $row2;
			}
			endforeach;
		
			return false;
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
		 * Check Workday Settings for end time
		 * @param unknown_type $workday
		 * @param unknown_type $work_schedule_id
		 * @param unknown_type $company_id
		 */
		public function check_workday_settings_end_time($workday,$work_schedule_id,$company_id){
			// check uniform working days
			$w = array(
				"work_schedule_id"=>$work_schedule_id,
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
					"work_schedule_id"=>$work_schedule_id,
					"company_id"=>$company_id
				);
				$this->db->where($w2);
				$q2 = $this->db->get("split_schedule");
				$row2 = $q2->row();
				if($row2){
					return $row2->end_time;
				}else{
					return false;
				}
			}
		}
		
		/**
		 * Get Tardiness
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $date
		 * @param unknown_type $time_in
		 */
		public function get_tardiness_val($emp_id,$comp_id,$time_in_import,$work_schedule_id){
			$day = date("l",strtotime($time_in_import));
			
			$where = array(
				"emp_id" => $emp_id
			);
			$this->db->where($where);
			$sql_payroll_info = $this->db->get("employee_payroll_information");
			$row_payroll_info = $sql_payroll_info->row();
			//$payroll_group_id = $row_payroll_info->payroll_group_id;
			$payroll_group_id = 0;
			
			// check if rest day
			$rest_day = $this->check_holiday_val($time_in_import,$emp_id,$comp_id,$work_schedule_id);
			if($rest_day){
				$min_late = 0;
				$min_late_breaktime = 0;
			}else{
				// rest day
				$rd_where = array(
					"company_id"=>$comp_id,
					"rest_day"=>$day,
					"work_schedule_id"=>$work_schedule_id
				);
				$this->db->where($rd_where);
				$rest_day = $this->db->get("rest_day");
				
				if($rest_day->num_rows() == 0){
					// uniform working days settings
					$uni_where = array(
						"work_schedule_id"=>$work_schedule_id,
						"company_id"=>$comp_id
					);
					$this->db->where($uni_where);
					$sql = $this->db->get("regular_schedule");
					$row = $sql->row();
					
					if($row && $row->allow_flexible_workhours != 0){
						$payroll_sched_timein = $row->latest_time_in_allowed;
					}else{
						// uniform working days
						$uw_where = array(
							"work_schedule_id"=>$work_schedule_id,
							"company_id"=>$comp_id,
							"days_of_work"=>$day
						);
						$this->db->where($uw_where);
						$sql_uniform_working_days = $this->db->get("regular_schedule");
						$row_uniform_working_days = $sql_uniform_working_days->row();
						
						if($row_uniform_working_days){
							$payroll_sched_timein = $row_uniform_working_days->work_start_time;
						}else{
							// flexible working days
							$fl_where = array(
								"work_schedule_id"=>$work_schedule_id,
								"company_id"=>$comp_id
							);
							$this->db->where($fl_where);
							$sql_flexible_days = $this->db->get("flexible_hours");
							$row_flexible_days = $sql_flexible_days->row();
							
							if($row_flexible_days){
								$payroll_sched_timein = $row_flexible_days->latest_time_in_allowed;
							}else{
								// workshift working days
								$ws_where = array(
									"work_schedule_id"=>$work_schedule_id,
									"company_id"=>$comp_id
								);
								$this->db->where($ws_where);
								$sql_workshift = $this->db->get("workshift");
								$row_workshift = $sql_workshift->row();
								
								if($row_workshift){
									$payroll_sched_timein = $row_workshift->start_time;
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
		
		/**
		 * Get Undertime for import
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $date
		 * @param unknown_type $time_in
		 * @param unknown_type $work_schedule_id
		 */
		public function get_undertime_val($emp_id,$comp_id,$date_timein,$date_timeout,$work_schedule_id){
			
			
			$day = date("l",strtotime($date_timein));
			$start_time = "";
			
			$where = array(
				"emp_id" => $emp_id
			);
			$this->db->where($where);
			$sql_payroll_info = $this->db->get("employee_payroll_information");
			$row_payroll_info = $sql_payroll_info->row();
			// $payroll_group_id = $row_payroll_info->payroll_group_id;
			$payroll_group_id = 0;
	
			// check rest day
			$rest_day = $this->check_holiday_val($date_timein,$emp_id,$comp_id,$work_schedule_id);
			if($rest_day){
				$under_min_val = 0;
			}else{
				// rest day
				$rd_where = array(
					"company_id"=>$comp_id,
					"rest_day"=>$day,
					"work_schedule_id"=>$work_schedule_id
				);
				$this->db->where($rd_where);
				$rest_day = $this->db->get("rest_day");
				
				if($rest_day->num_rows() == 0){
					// uniform working days
					$uw_where = array(
						"work_schedule_id"=>$work_schedule_id,
						"company_id"=>$comp_id,
						"days_of_work"=>$day
					);
					$this->db->where($uw_where);
					$sql_uniform_working_days = $this->db->get("regular_schedule");
					$row_uniform_working_days = $sql_uniform_working_days->row();
					
					if($row_uniform_working_days){
						$start_time = $row_uniform_working_days->work_start_time;
						$undertime_min = $row_uniform_working_days->work_end_time;
						$working_hours = $row_uniform_working_days->total_work_hours;
					}else{
						// flexible working days
						$fl_where = array(
							"work_schedule_id"=>$work_schedule_id,
							"company_id"=>$comp_id
						);
						$this->db->where($fl_where);
						$sql_flexible_days = $this->db->get("flexible_hours");
						$row_flexible_days = $sql_flexible_days->row();
						
						if($row_flexible_days){
							$this->db->where("emp_id",$emp_id);
							$this->db->order_by("date", "DESC");
							$this->db->limit(1);
							$flexible_compute_time = $this->db->get("employee_time_in");
							$row_flexible_compute_time = $flexible_compute_time->row();
							
							/* $flexible_compute_time = $this->db->query("
								SELECT * FROM `employee_time_in` WHERE emp_id = '{$emp_id}'
								ORDER BY date DESC
								LIMIT 1
							"); */
							
							if($row_flexible_compute_time){
								$time_in = explode(" ", $row_flexible_compute_time->time_in);;
								$flexible_work_end = $time_in[1];
								
								// flexible total hours per day
								$flx_where = array(
									"work_schedule_id"=>$work_schedule_id,
									"company_id"=>$comp_id
								);
								$this->db->where($flx_where);
								$sql_flexible_working_days = $this->db->get("flexible_hours");
								$row_flexible_working_days = $sql_flexible_working_days->row();
								
								if($row_flexible_working_days){
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
							$ws_where = array(
								"work_schedule_id"=>$work_schedule_id,
								"company_id"=>$comp_id
							);
							$this->db->where($ws_where);
							$this->db->join('schedule_block AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
							$sql_workshift = $this->db->get("split_schedule");
							$row_workshift = $sql_workshift->row();
							
							if($row_workshift){
								$undertime_min =  $row_workshift->end_time;
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
						$check_tardiness_import = $this->check_tardiness_import($emp_id,$comp_id,$date_timein,$work_schedule_id);
						if($check_tardiness_import == 0){
							if(strtotime($undertime_min) <= strtotime($date_timeout_sec)){							
								$under_min_val = (strtotime($undertime_min) - strtotime($date_timeout_sec)) / 60;
							}else{
								$breaktime_hours = $this->add_breaktime($comp_id,$work_schedule_id,$date_timein);
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
			$get_hours_worked_to_mins = $this->get_hours_worked($day, $emp_id,$work_schedule_id) * 60;
			
			if($get_hours_worked_to_mins < $under_min_val) return 0;
			
			return ($under_min_val < 0) ? 0 : $under_min_val ;	
		}
		
		/**
		 * Check Holiday Value
		 * @param unknown_type $day
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $work_schedule_id
		 */
		public function check_holiday_val($day,$emp_id,$comp_id,$work_schedule_id){
			$w = array(
				"rest_day"=>date("l",strtotime($day)),
				"company_id"=>$comp_id,
				"work_schedule_id"=>$work_schedule_id,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get('rest_day');
			return ($q->num_rows() > 0) ? TRUE : FALSE ;
		}
		
		/**
		 * Check Tardiness for undertime only
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $date
		 * @param unknown_type $time_in
		 * @param unknown_type $work_schedule_id
		 */
		public function check_tardiness_import($emp_id,$comp_id,$time_in_import,$work_schedule_id){
			$day = date("l",strtotime($time_in_import));
			
			$where = array(
				"emp_id" => $emp_id
			);
			$this->db->where($where);
			$sql_payroll_info = $this->db->get("employee_payroll_information");
			$row_payroll_info = $sql_payroll_info->row();
			// $payroll_group_id = $row_payroll_info->payroll_group_id;
			$payroll_group_id = 0;
			
			// check rest day
			$rest_day = $this->check_holiday_val($time_in_import,$emp_id,$comp_id,$work_schedule_id);
			if($rest_day){
				$min_late = 0;
			}else{
				// rest day
				$rd_where = array(
					"company_id"=>$comp_id,
					"rest_day"=>$day,
					"work_schedule_id"=>$work_schedule_id
				);
				$this->db->where($rd_where);
				$rest_day = $this->db->get("rest_day");
				
				if($rest_day->num_rows() == 0){
					// uniform working days settings
					$uni_where = array(
						"work_schedule_id"=>$work_schedule_id,
						"company_id"=>$comp_id
					);
					$this->db->where($uni_where);
					$sql = $this->db->get("regular_schedule");
					$row = $sql->row();
					
					if($row && $row->allow_flexible_workhours != 0){
						$payroll_sched_timein = $row->latest_time_in_allowed;
					}else{				
						// uniform working days
						$uw_where = array(
							"work_schedule_id"=>$work_schedule_id,
							"company_id"=>$comp_id,
							"days_of_work"=>$day
						);
						$this->db->where($uw_where);
						$sql_uniform_working_days = $this->db->get("regular_schedule");
						$row_uniform_working_days = $sql_uniform_working_days->row();
						
						if($row_uniform_working_days){
							$payroll_sched_timein = $row_uniform_working_days->work_start_time;
						}else{
							// flexible working days
							$fl_where = array(
								"work_schedule_id"=>$work_schedule_id,
								"company_id"=>$comp_id
							);
							$this->db->where($fl_where);
							$sql_flexible_days = $this->db->get("flexible_hours");
							$row_flexible_days = $sql_flexible_days->row();
							
							if($row_flexible_days){
								$payroll_sched_timein = $row_flexible_days->latest_time_in_allowed;
							}else{
								// workshift working days
								$ws_where = array(
									"work_schedule_id"=>$work_schedule_id,
									"company_id"=>$comp_id
								);
								$this->db->where($ws_where);
								$sql_workshift = $this->db->get("split_schedule");
								$row_workshift = $sql_workshift->row();
								
								if($row_workshift){
									$row_workshift = $row_workshift->start_time;
								}else{
									$payroll_sched_timein = "00:00:00";
								}
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
			}
			
			return ($min_late < 0) ? 0 : $min_late;
		}
		
		/**
		 * Add Breaktime for undertime
		 * @param unknown_type $comp
		 * @param unknown_type $work_schedule_id
		 * @param unknown_type $workday
		 */
		public function add_breaktime($comp_id,$work_schedule_id,$workday){
			$flag_workshift = 0;
		
			// workshift working days
			$workshift_where = array(
				"work_schedule_id" => $work_schedule_id,
				"company_id"=>$comp_id
			);
			$this->db->where($workshift_where);
			$workshift_query = $this->db->get("split_schedule");
			$workshift_row = $workshift_query->row();
			if($workshift_row) $flag_workshift = 1;
			
			$day = date("l",strtotime($workday));
			$where = array(
				"company_id" => $comp_id,
				"work_schedule_id" => $work_schedule_id
			);
			$this->db->where($where);
			if($flag_workshift == 0) $this->db->where("days_of_work",$day);
			$sql = $this->db->get("regular_schedule");
			
			// FOR UNIFORM WORKING DAYS
			if($sql->num_rows() > 0){
				$row_uniform = $sql->row();
				$breaktime = (strtotime($row_uniform->work_end_time) - strtotime($row_uniform->work_start_time)) / 3600;
			}else{
				$breaktime = 0;
			}
				
			return ($breaktime < 0) ? 0 : $breaktime ;
		}
		
		/**
		 * Get Hours Worked for workday
		 * @param unknown_type $workday
		 * @param unknown_type $emp_id
		 * @param unknown_type $work_schedule_id
		 */
		public function get_hours_worked($workday, $emp_id,$work_schedule_id){
			$workday_val = date("l",strtotime($workday));
			// get employee payroll information
			$w	= array(
				"emp_id"=>$emp_id,
				"status"=>"Active"
				);
			$s 	= array("company_id");
			$this->db->select($s);
			$this->db->where($w);
			$q = $this->db->get("employee_payroll_information");
			$r = $q->row();
			if($q->num_rows() > 0){
				$payroll_group_id = 0;
				$comp_id = $r->company_id;
				// get hours worked
				$s1 = array("total_work_hours");
				$w2 = array(
					"work_schedule_id"	=> $work_schedule_id,
					"days_of_work"		=> $workday_val,
					"company_id"		=> $comp_id,
					"status"			=> "Active"
					);
				$this->db->select($s1);
				$this->db->where($w2);
				$q2 = $this->db->get("regular_schedule");
				$r2 = $q2->row();
				if($r2){
					// for uniform working days table
					return $r2->total_work_hours;
				}else{
					$sf = array("total_hours_for_the_day");
					$wf = array(
						"work_schedule_id"	=> $work_schedule_id,
						"company_id"		=> $comp_id
					);
					$this->db->select($sf);
					$this->db->where($wf);
					$qf = $this->db->get("flexible_hours");
					$rf = $qf->row();
 					if($rf){
 						// for flexible hours table
 						return $rf->total_hours_for_the_day;
 					}else{
 						return 0;
 					}
				}
			}else{
				return 0;
			}
		}
	
		/**
		 * Get Total Hours Worked
		 * @param unknown_type $time_in
		 * @param unknown_type $time_out
		 * @param unknown_type $hours_worked
		 * @param unknown_type $work_schedule_id
		 */
		public function get_tot_hours($emp_id,$comp_id,$time_in,$time_out,$hours_worked,$work_schedule_id,$new_employee_timein_date=null){
			$where = array(
				"emp_id" => $emp_id
			);
			$this->db->where($where);
			$sql_payroll_info = $this->db->get("employee_payroll_information");
			$row_payroll_info = $sql_payroll_info->row();
			// $payroll_group_id = $row_payroll_info->payroll_group_id;
			$payroll_group_id = 0;
			
			// check if rest day
			$rest_day = $this->check_holiday_val($time_in,$emp_id,$comp_id,$work_schedule_id);
			if($new_employee_timein_date != null) {
				$rest_day = $this->check_holiday_val($new_employee_timein_date,$emp_id,$comp_id,$work_schedule_id);
			}
			if($rest_day){
				$total = (strtotime($time_out) - strtotime($time_in)) / 3600;
			}else{
				if($new_employee_timein_date == null) {
					$days_of_work = $time_in;
				} else {
					$days_of_work = $new_employee_timein_date;
				}
				
				// check time out for uniform working days
				$where_uw = array(
					"company_id"=>$comp_id,
					"work_schedule_id"=>$work_schedule_id,
					"days_of_work"=>date("l",strtotime($days_of_work))	
				);
				$this->db->where($where_uw);
				$sql_uw = $this->db->get("regular_schedule");
				
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
					// check time out for flexible hours
					
					$flex_rules = $this->get_flex_rules($comp_id,$work_schedule_id);
					if($flex_rules){
						
					}
					
					$where_f = array(
						"company_id"=>$comp_id,
						"work_schedule_id"=>$work_schedule_id,
					);
					$this->db->where($where_f);
					$sql_f = $this->db->get("flexible_hours");
					$row_f = $sql_f->row();
					if($sql_f->num_rows() > 0){
						$new_time_out = date('Y-m-d H:i:s', strtotime($time_out));
						$total_hours_worked = (strtotime($time_out) - strtotime($time_in)) / 3600;
					}else{
						$total_hours_worked = 0;
					}
				}
				
				$total = $total_hours_worked;
				// remove temporarily
				/*if($total > $hours_worked){
					$total = $hours_worked;
				}**/
			}
			
			return ($total < 0) ? round(0,2) : round($total,2) ;
		}
		
		function get_flex_rules($company_id,$work_schedule_id){
			$w = array(
					'work_schedule_id' 	=> $work_schedule_id,
					'comp_id'			=> $company_id
			);
			$this->db->where($w);
			$q = $this->db->get('work_schedule');
			$row = $q->row();
			return ($row) ? $row : false;
		}
		
		/**
		 * Check Breaktime
		 * @param unknown_type $comp_id
		 * @param unknown_type $work_schedule_id
		 */
		public function check_breaktime($comp_id,$work_schedule_id){
			$flag_workshift = 0;
			// workshift working days
			$workshift_where = array(
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id
			);
			$this->db->where($workshift_where);
			$workshift_query = $this->db->get("split_schedule");
			$workshift_row = $workshift_query->row();
			if($workshift_row) $flag_workshift = 1;
			
			$w = array(
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id
			);
			$this->db->where($w);
			if($flag_workshift == 0) $this->db->where("days_of_work",date("l"));
			$q = $this->db->get("regular_schedule");
			return ($q->num_rows() > 0) ? $q->row() : FALSE ;
		}
		
		/**
		 * Check Hours Flex
		 * @param unknown_type $comp_id
		 * @param unknown_type $work_schedule_id
		 */
		public function check_hours_flex($comp_id,$work_schedule_id){
			$w = array(
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id
			);		
			$this->db->where($w);
			$q = $this->db->get("regular_schedule");
			$r = $q->row();
			if($q->num_rows() > 0){
				return ($r->allow_flexible_workhours == 1) ? $r->latest_time_in_allowed : FALSE ;
			}else{
				return FALSE;
			}
		}
		
		/**
		 * Get Workday Sched Start Time
		 * @param unknown_type $comp_id
		 * @param unknown_type $work_schedule_id
		 */
		public function get_workday_sched_start($comp_id,$work_schedule_id){
			$w = array(
				"work_schedule_id"=>$work_schedule_id,
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
					"work_schedule_id"=>$work_schedule_id,
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
		 * @param unknown_type $work_schedule_id
		 */
		public function get_end_time($comp_id,$work_schedule_id){
			$w = array(
				"work_schedule_id"=>$work_schedule_id,
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
					"work_schedule_id"=>$work_schedule_id,
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
		 * Check TimeIn Row
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $time_in
		 */
		public function check_timein_row($emp_id, $comp_id, $time_in){
			$w = array(
				"date"		=> date("Y-m-d",strtotime($time_in)),
				"emp_id"	=> $emp_id,
				"comp_id"	=> $comp_id,
				"status"	=> "Active",
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
		 * @param unknown_type $work_schedule_id 
		 */
		public function get_tardiness_import($emp_id,$comp_id,$time_in_import,$lunch_out,$lunch_in,$work_schedule_id){
			$day = date("l",strtotime($time_in_import));
			
			$where = array(
				"emp_id" => $emp_id
			);
			$this->db->where($where);
			$sql_payroll_info = $this->db->get("employee_payroll_information");
			$row_payroll_info = $sql_payroll_info->row();
			// $payroll_group_id = $row_payroll_info->payroll_group_id;
			$payroll_group_id = 0;
			$flag_workshift = 0;
			
			// check if holiday
			$check_holiday = $this->company_holiday($time_in_import,$comp_id);
			if($check_holiday) return 0;
			
			// check if rest day
			$rest_day = $this->check_holiday_val($time_in_import,$emp_id,$comp_id,$work_schedule_id);
			if($rest_day){
				$min_late = 0;
				$min_late_breaktime = 0;
			}else{
				// rest day
				$rd_where = array(
					"company_id"=>$comp_id,
					"rest_day"=>$day,
					"work_schedule_id"=>$work_schedule_id
				);
				$this->db->where($rd_where);
				$rest_day = $this->db->get("rest_day");
				
				if($rest_day->num_rows() == 0){
					// uniform working days settings
					$uni_where = array(
						"work_schedule_id"=>$work_schedule_id,
						"company_id"=>$comp_id
					);
					$this->db->where($uni_where);
					$sql = $this->db->get("regular_schedule");
					$row = $sql->row();
					
					if($row && $row->allow_flexible_workhours != 0){
						$payroll_sched_timein = $row->latest_time_in_allowed;
					}else{				
						// uniform working days
						$uw_where = array(
							"work_schedule_id"=>$work_schedule_id,
							"company_id"=>$comp_id,
							"days_of_work"=>$day
						);
						$this->db->where($uw_where);
						$sql_uniform_working_days = $this->db->get("regular_schedule");
						$row_uniform_working_days = $sql_uniform_working_days->row();
						
						if($row_uniform_working_days){
							$payroll_sched_timein = $row_uniform_working_days->work_start_time;
						}else{
							// flexible working days
							$fl_where = array(
								"work_schedule_id"=>$work_schedule_id,
								"company_id"=>$comp_id
							);
							$this->db->where($fl_where);
							$sql_flexible_days = $this->db->get("flexible_hours");
							$row_flexible_days = $sql_flexible_days->row();
							
							if($row_flexible_days){
								$payroll_sched_timein = $row_flexible_days->latest_time_in_allowed;
							}else{
								// workshift working days
								$ws_where = array(
									"work_schedule_id"=>$work_schedule_id,
									"company_id"=>$comp_id
								);
								$this->db->where($ws_where);
								$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
								$sql_workshift = $this->db->get("split_schedule");
								$row_workshift = $sql_workshift->row();
								
								if($row_workshift){
									$payroll_sched_timein = $row_workshift->start_time;
									$flag_workshift = 1;
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
				
				// for tardiness break
				
				// get uniform working days and workshift settings for break time
				$where_break = array(
					"company_id" => $comp_id,
					"work_schedule_id" => $work_schedule_id
				);
				$this->db->where($where_break);
				if($flag_workshift == 0) $this->db->where("days_of_work",$day);
				$sql_break = $this->db->get("regular_schedule");
				$row_break = $sql_break->row();
				
				if($sql_break->num_rows() > 0){
					$breaktime_settings = strtotime($row_break->work_end_time) - strtotime($row_break->work_start_time);
					#return $row_break->end_time." ".$row_break->start_time;
				}else{
					// get flexible hours for break time
					$where_break_flex = array(
						"company_id" => $comp_id,
						"work_schedule_id" => $work_schedule_id,
					);
					$this->db->where($where_break_flex);
					$sql_break_flex = $this->db->get("flexible_hours");
					$row_break_flex = $sql_break_flex->row();
					
					if($sql_break_flex->num_rows() > 0){
						$breaktime_settings = $row_break_flex->duration_of_lunch_break_per_day * 60; // convert to seconds
					} else{
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
			
			$min_late = ($min_late < 0) ? 0 : $min_late ;
			$min_late_breaktime = ($min_late_breaktime < 0) ? 0 : $min_late_breaktime;
			
			return $min_late + $min_late_breaktime;
		}
		
		/**
		 * Company Holiday
		 * @param unknown_type $time_in_import
		 * @param unknown_type $comp_id
		 */
		public function company_holiday($time_in_import,$comp_id){
			$date = date("Y-m-d",strtotime($time_in_import));
			$w = array(
				"date"=>$date,
				"company_id"=>$comp_id,
				"status"=>"Active"
			);
			$this->db->where($w);
			$q = $this->db->get("holiday");
			$r = $q->row();
			return ($r) ? TRUE : FALSE ;
		}
		
		/**
		 * Get Undertime for import
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $date
		 * @param unknown_type $time_in
		 * @param unknown_type $work_schedule_id
		 */
		public function get_undertime_import($emp_id,$comp_id,$date_timein,$date_timeout,$lunch_out,$lunch_in,$work_schedule_id){
			$day = date("l",strtotime($date_timein));
			$start_time = "";
			
			$where = array(
				"emp_id" => $emp_id
			);
			$this->db->where($where);
			$sql_payroll_info = $this->db->get("employee_payroll_information");
			$row_payroll_info = $sql_payroll_info->row();
			// $payroll_group_id = $row_payroll_info->payroll_group_id;
			$payroll_group_id = 0;
	
			// check if holiday
			$check_holiday = $this->company_holiday($date_timein,$comp_id);
			if($check_holiday) return 0;
			
			// check rest day
			$rest_day = $this->check_holiday_val($date_timein,$emp_id,$comp_id,$work_schedule_id);
			if($rest_day){
				$under_min_val = 0;
			}else{
				// rest day
				$rd_where = array(
					"company_id"=>$comp_id,
					"rest_day"=>$day,
					"work_schedule_id"=>$work_schedule_id
				);
				$this->db->where($rd_where);
				$rest_day = $this->db->get("rest_day");
				
				if($rest_day->num_rows() == 0){
					// uniform working days
					$uw_where = array(
						"work_schedule_id"=>$work_schedule_id,
						"company_id"=>$comp_id,
						"days_of_work"=>$day
					);
					$this->db->where($uw_where);
					$sql_uniform_working_days = $this->db->get("regular_schedule");
					$row_uniform_working_days = $sql_uniform_working_days->row();
					
					if($row_uniform_working_days){
						$start_time = $row_uniform_working_days->work_start_time;
						$undertime_min = $row_uniform_working_days->work_end_time;
						$working_hours = $row_uniform_working_days->total_work_hours;
					}else{
						// flexible working days
						$fl_where = array(
							"work_schedule_id"=>$work_schedule_id,
							"company_id"=>$comp_id
						);
						$this->db->where($fl_where);
						$sql_flexible_days = $this->db->get("flexible_hours");
						$row_flexible_days = $sql_flexible_days->row();
						
						if($row_flexible_days){
							$this->db->where("emp_id",$emp_id);
							$this->db->order_by("date", "DESC");
							$this->db->limit(1);
							$flexible_compute_time = $this->db->get("employee_time_in");
							$row_flexible_compute_time = $flexible_compute_time->row();
							
							/* $flexible_compute_time = $this->db->query("
								SELECT * FROM `employee_time_in` WHERE emp_id = '{$emp_id}'
								ORDER BY date DESC
								LIMIT 1
							"); */
							
							if($row_flexible_compute_time){
								$time_in = explode(" ", $row_flexible_compute_time->time_in);;
								$flexible_work_end = $time_in[1];
								
								// flexible total hours per day
								$flx_where = array(
									"work_schedule_id"=>$work_schedule_id,
									"company_id"=>$comp_id
								);
								$this->db->where($flx_where);
								$sql_flexible_working_days = $this->db->get("flexible_hours");
								$row_flexible_working_days = $sql_flexible_working_days->row();
								
								if($row_flexible_working_days){
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
							$ws_where = array(
								"work_schedule_id"=>$work_schedule_id,
								"company_id"=>$comp_id
							);
							$this->db->where($ws_where);
							$this->db->join("schedule_blocks AS sb","sb.split_schedule_id = split_schedule.split_schedule_id","LEFT");
							$sql_workshift = $this->db->get("split_schedule");
							$row_workshift = $sql_workshift->row();
							
							if($row_workshift){
								$undertime_min =  $row_workshift->end_time;
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
				
				if($date_timein == "" && $date_timeout == "" && $lunch_out == "" && $lunch_in == ""){
					return 0;
				}
				
				if($start_time == ""){
					return 0;
				}
				
				// check PM and AM
				$check_endtime = date("A",strtotime($undertime_min));
				$check_timout = date("A",strtotime($date_timeout_sec));
				
				// callcenter trapping
				if($check_endtime == "AM" && $check_timout == "PM" && $check_timein == "PM"){
					$time_out_date = date("Y-m-d",strtotime($date_timeout_sec."+1 day"));
					$new_undertime_min = $time_out_date." ".$undertime_min;
					$under_min_val = (strtotime($new_undertime_min) - strtotime($date_timeout)) / 60;
				}else{
					if(strtotime($date_timeout_sec) <= strtotime($undertime_min)){
						$check_tardiness_import = $this->check_tardiness_import($emp_id,$comp_id,$date_timein,$work_schedule_id);
						if($check_tardiness_import == 0){
							if(strtotime($undertime_min) <= strtotime($date_timeout_sec)){							
								$under_min_val = (strtotime($undertime_min) - strtotime($date_timeout_sec)) / 60;
							}else{
								$breaktime_hours = $this->add_breaktime($comp_id,$work_schedule_id,$date_timein);
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
			$get_hours_worked_to_mins = $this->get_hours_worked($day, $emp_id, $work_schedule_id) * 60;
			
			if($get_hours_worked_to_mins < $under_min_val) return 0;
			
			return ($under_min_val < 0) ? 0 : $under_min_val ;	
		}
		
		/**
		 * Get Total Hours Worked Complete Logs
		 * @param unknown_type $time_in
		 * @param unknown_type $lunch_in
		 * @param unknown_type $lunch_out
		 * @param unknown_type $time_out
		 * @param unknown_type $work_schedule_id
		 */
		public function get_tot_hours_complete_logs($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$time_out,$hours_worked,$work_schedule_id){
			$where = array(
				"emp_id" => $emp_id
			);
			$this->db->where($where);
			$sql_payroll_info = $this->db->get("employee_payroll_information");
			$row_payroll_info = $sql_payroll_info->row();
			// $payroll_group_id = $row_payroll_info->payroll_group_id;
			$payroll_group_id = 0;
			
			// check if rest day
			$rest_day = $this->check_holiday_val($time_in,$emp_id,$comp_id,$work_schedule_id);
			if($rest_day){
				$total = (strtotime($time_out) - strtotime($time_in)) / 3600;
			}else{
				
				// check time out for uniform working days
				$where_uw = array(
					"company_id"=>$comp_id,
					"work_schedule_id"=>$work_schedule_id,
					"days_of_work"=>date("l",strtotime($time_in))	
				);
				$this->db->where($where_uw);
				$sql_uw = $this->db->get("regular_schedule");
				
				$row_uw = $sql_uw->row();
				if($sql_uw->num_rows() > 0){
					
					$time_out_sec = date("H:i:s",strtotime($time_out));
					$time_out_date = date("Y-m-d",strtotime($time_out));
					$new_work_end_time = $time_out_date." ".$row_uw->work_end_time;
					if(strtotime($new_work_end_time) <= strtotime($time_out)){
						// FOR CALLCENTER
						$time_in_sec = date("H:i:s",strtotime($time_in));
						$total_hours_worked = (strtotime($new_work_end_time) - strtotime($time_in)) / 3600;
					}else{
						// FOR TGG ABOVE
						$total_hours_worked = (strtotime($time_out) - strtotime($time_in)) / 3600;
					}
					
				}else{
					// check time out for workshift
					$where_w = array(
						"company_id"=>$comp_id,
						"work_schedule_id"=>$work_schedule_id
					);
					$this->db->where($where_w);
					$sql_w = $this->db->get("split_schedule");
					$row_w = $sql_w->row();
					if($sql_w->num_rows() > 0){
						
						$time_out_sec = date("H:i:s",strtotime($time_out));
						$time_out_date = date("Y-m-d",strtotime($time_out));
						$new_work_end_time = $time_out_date." ".$row_uw->end_time; //zz
						
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
							"work_schedule_id"=>$work_schedule_id
						);
						$this->db->where($where_f);
						$sql_f = $this->db->get("flexible_hours");
						$row_f = $sql_f->row();
						if($sql_f->num_rows() > 0){
							$total_hours_worked = (strtotime($time_in . ' + '.$row_f->total_hours_for_the_day.' hour') - strtotime($time_in)) / 3600;
						}else{
							$total_hours_worked = 0;
						}
					}
				}
				
				$get_tardiness = ($this->get_tardiness_breaktime($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$work_schedule_id)) / 60;
				$breaktime_hours = $this->add_breaktime($comp_id,$work_schedule_id,$time_in);
				
				$total = $total_hours_worked - $get_tardiness - $breaktime_hours;
				if($total > $hours_worked){
					$total = $hours_worked;
				}
			}
			
			return ($total < 0) ? round(0,2) : round($total,2) ;
		}
		
		/**
		 * Get Tardiness for breaktime
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $date
		 * @param unknown_type $time_in
		 * @param unknown_type $work_schedule_id
		 */
		public function get_tardiness_breaktime($emp_id,$comp_id,$time_in_import,$lunch_out,$lunch_in,$work_schedule_id){
			$day = date("l",strtotime($time_in_import));
			
			$where = array(
				"emp_id" => $emp_id
			);
			$this->db->where($where);
			$sql_payroll_info = $this->db->get("employee_payroll_information");
			$row_payroll_info = $sql_payroll_info->row();
			// $payroll_group_id = $row_payroll_info->payroll_group_id;
			$payroll_group_id = 0;
			$flag_workshift = 0;
			
			// check rest day
			$rest_day = $this->check_holiday_val($time_in_import,$emp_id,$comp_id,$work_schedule_id);
			if($rest_day){
				$min_late_breaktime = 0;
			}else{
				// rest day
				$rd_where = array(
					"company_id"=>$comp_id,
					"rest_day"=>$day,
					"work_schedule_id"=>$work_schedule_id
				);
				$this->db->where($rd_where);
				$rest_day = $this->db->get("rest_day");
				
				if($rest_day->num_rows() == 0){
					// uniform working days settings
					$uni_where = array(
						"work_schedule_id"=>$work_schedule_id,
						"company_id"=>$comp_id
					);
					$this->db->where($uni_where);
					$sql = $this->db->get("regular_schedule");
					$row = $sql->row();
					
					if($row && $row->allow_flexible_workhours != 0){
						$payroll_sched_timein = $row->latest_time_in_allowed;
					}else{				
						// uniform working days
						$uw_where = array(
							"work_schedule_id"=>$work_schedule_id,
							"company_id"=>$comp_id,
							"days_of_work"=>$day
						);
						$this->db->where($uw_where);
						$sql_uniform_working_days = $this->db->get("regular_schedule");
						$row_uniform_working_days = $sql_uniform_working_days->row();
						
						if($row_uniform_working_days){
							$payroll_sched_timein = $row_uniform_working_days->work_start_time;
						}else{
							// flexible working days
							$fl_where = array(
								"work_schedule_id"=>$work_schedule_id,
								"company_id"=>$comp_id
							);
							$this->db->where($fl_where);
							$sql_flexible_days = $this->db->get("flexible_hours");
							$row_flexible_days = $sql_flexible_days->row();
							
							if($row_flexible_days){
								$payroll_sched_timein = $row_flexible_days->latest_time_in_allowed;
							}else{
								// workshift working days
								$ws_where = array(
									"work_schedule_id"=>$work_schedule_id,
									"company_id"=>$comp_id
								);
								$this->db->where($ws_where);
								$sql_workshift = $this->db->get("workshift");
								$row_workshift = $sql_workshift->row();
								
								if($row_workshift){
									$payroll_sched_timein = $row_workshift->start_time;
									$flag_workshift = 1;
								}else{
									$payroll_sched_timein = "00:00:00";
								}
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
				
				// get uniform working days and workshift settings for break time
				$where_break = array(
					"company_id" => $comp_id,
					"work_schedule_id"=>$work_schedule_id
				);
				$this->db->where($where_break);
				if($flag_workshift == 0) $this->db->where("workday",$day);
				$sql_break = $this->db->get("break_time");
				$row_break = $sql_break->row();
				
				if($sql_break->num_rows() > 0){
					 $breaktime_settings = strtotime($row_break->end_time) - strtotime($row_break->start_time);
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
		 * Get Total Hours Worked
		 * @param unknown_type $time_in
		 * @param unknown_type $lunch_in
		 * @param unknown_type $lunch_out
		 * @param unknown_type $time_out
		 * @param unknown_type $work_schedule_id
		 */
		public function get_tot_hours_limit($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$time_out,$work_schedule_id){
			$where = array(
				"emp_id" => $emp_id
			);
			$this->db->where($where);
			$sql_payroll_info = $this->db->get("employee_payroll_information");
			$row_payroll_info = $sql_payroll_info->row();
			//$payroll_group_id = $row_payroll_info->payroll_group_id;
			$payroll_group_id = 0;
			
			$total_hours_worked = (strtotime($time_out) - strtotime($time_in)) / 3600;
			
			$get_tardiness = ($this->get_tardiness_breaktime($emp_id,$comp_id,$time_in,$lunch_out,$lunch_in,$work_schedule_id)) / 60;
			$breaktime_hours = $this->add_breaktime($comp_id,$work_schedule_id,$time_in);
			
			$total = $total_hours_worked - $get_tardiness - $breaktime_hours;
			
			// check if rest day
			$rest_day = $this->check_holiday_val($time_in,$emp_id,$comp_id,$work_schedule_id);
			if($rest_day){
				$total = (strtotime($time_out) - strtotime($time_in)) / 3600;
			}
			
			return ($total < 0) ? round(0,2) : round($total,2);
		}
		
		/**
		 * Get Hoursworked For Flexible Hours
		 * @param unknown_type $work_schedule_id
		 * @param unknown_type $comp_id
		 */
		public function get_hoursworked($work_schedule_id,$comp_id){
			$w = array(
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id
			);
			$this->db->where($w);
			$q = $this->db->get("flexible_hours");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
		
		/**
		 * Duration of Lunch Break Per Day for Flexible Hours
		 * @param unknown_type $emp_id
		 * @param unknown_type $comp_id
		 * @param unknown_type $work_schedule_id
		 */
		public function duration_of_lunch_break_per_day($emp_id, $comp_id, $work_schedule_id){
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
					"work_schedule_id"=>$work_schedule_id,
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
		 * Check Break Time Work Schedule ID
		 * @param unknown_type $work_schedule_id
		 * @param unknown_type $comp_id
		 * @return number
		 */
		public function check_break_time_flex($work_schedule_id,$comp_id , $imba= true){
			// check number of breaks
			$number_of_breaks_per_day = 0;
			
			# UNIFORM WORKING DAYS
			$w_uwd = array(
				//"payroll_group_id"=>$payroll_group,
				"work_schedule_id"=>$work_schedule_id,
				"company_id"=>$comp_id
			);
			$this->db->where($w_uwd);
			$q_uwd = $this->db->get("regular_schedule");
			$r_uwd = $q_uwd->row();
			if($q_uwd->num_rows() > 0 && $imba){
				$number_of_breaks_per_day = $r_uwd->break_in_min;
			}else{
				
				# FLEXIBLE HOURS
				$w_fh = array(
					//"payroll_group_id"=>$payroll_group,
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
			return $number_of_breaks_per_day;
		}

		/**
		 * Check Break Time Payroll Group ID
		 * @param unknown_type $payroll_group_id
		 * @param unknown_type $comp_id
		 * @return number
		 */
		public function check_break_time_flex_payroll_group($payroll_group,$comp_id){
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
				/*$w_ws = array(
					"payroll_group_id"=>$payroll_group,
					"company_id"=>$comp_id
				);
				$this->db->where($w_ws);
				$this->db->join('schedule_blocks AS sb','sb.split_schedule_id = split_schedule.split_schedule_id','LEFT');
				$q_ws = $this->db->get("split_schedule");
				$r_ws = $q_ws->row();
				if($q_ws->num_rows() > 0){
					$number_of_breaks_per_day = $r_ws->break_in_min;
				}else{*/
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
				//}
			}
			return $number_of_breaks_per_day;
		}
		
		public function total_hours_worked($to,$from){
			$total      = strtotime($to) - strtotime($from);
			$hours      = floor($total / 60 / 60);
			$minutes    = round(($total - ($hours * 60 * 60)) / 60);
			return  ($hours * 60) + $minutes;
		}
		
		public function convert_to_hours($min){
			$h = date('H', mktime(0,$min));
			$m = date('i', mktime(0,$min));
			$m2 = ($m /60) * 100;
		
			$t = $h.".".round($m2, 0);
		
		
			return $t;
		}
		
		
		public function convert_to_min($min){
			$m = $min * 60;
			return $m;
		}
		
		public function get_company_address($comp_id){
		
		
			$ip = get_ip();
			$w_emp = array(
					'name' => 'lo.name'
			);
			
			
			$this->edb->select($w_emp);
			
			$this->edb->where('eid.company_id',$comp_id);
			$this->edb->where('ip_address',$ip);
			$this->edb->join("location_and_offices AS lo","lo.location_and_offices_id = eid.location_and_offices_id","LEFT");
			$q = $this->edb->get('employee_ip_address AS eid');
			$result = $q->row();
			
			if($result)
				return $result->name;
			else{
				
				$w_emp = array(
						'business_address'
				);
				
				
				$this->edb->select($w_emp);
				
				$this->edb->where('company_id',$comp_id);
				$q = $this->edb->get('company');
				$result = $q->row();
				
				return ($result) ? $result->business_address : "";
			}
			
		}
	}

/* End of file Emp_login_flexible_model.php 238*/
/* Location: ./application/models/employee_login_model/Emp_login_flexible_model.php */