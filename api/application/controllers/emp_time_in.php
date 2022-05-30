<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Employee Time In Controller
 *
 * @category Controller
 * @version 1.0
 * @author John fritz
 */
	class Emp_time_in extends CI_Controller {
		
		/**
		 * Constructor
		 */
		public function __construct() {
			parent::__construct();
			
			$this->authentication->check_if_logged_in();
			
			$this->load->model('employee_model','employee');
			$this->load->model('approval_group_model','agm');
			$this->load->model("approve_timeins_model","timeins");

			$this->load->model('emp_login_model','elm'); // REGULAR SCHEDULE & WORKSHIFT LOGIN MODEL
	  		$this->load->model('emp_login_flexible_model','elmf');
			
			$this->emp_id = $this->session->userdata('emp_id');
	 	 	$this->company_id =$this->employee->check_company_id($this->emp_id);
	 	 	
	 	 	$this->zero_time = NULL;
		}
		
		/**
		 * index page
		 */
		public function index() {
			
			
		}

		public function remove_break_of_uf() {

			$employee_timein_date = $this->input->post('employee_timein_date');
			$employee_timein_date1 = $this->input->post('employee_timein_date1');
			$new_employee_timein_time = $this->input->post('new_employee_timein_time');
			$flag = $this->input->post('flag');
			$flag_halfday = $this->input->post('flag_halfday');
			
			$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date('Y-m-d', strtotime($employee_timein_date)));
			$check_break_time = $this->employee->check_break_time($work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($employee_timein_date)));
			$check_break_time_for_assumed = $this->employee->check_break_time_for_assumed($work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($employee_timein_date)));
			
			$check_work_type = $this->employee->work_schedule_type($work_schedule_id, $this->company_id);
			
			$check_existing_timein = $this->employee->check_existing_timein_date($this->emp_id,$this->company_id,$employee_timein_date);
			$emp_no = $this->employee->check_emp_no($this->emp_id,$this->company_id);
			$split = $this->elm->new_get_splitinfo_fritz($emp_no, $this->company_id, $work_schedule_id,$this->emp_id,$employee_timein_date);
			$tardiness_rule_migrated_v3 = tardiness_rule_migrated_v3($this->company_id,$work_schedule_id);
			$get_schedule_settings = get_schedule_settings_by_workschedule_id($work_schedule_id,$this->company_id,date("l", strtotime($employee_timein_date)));
			
			if($tardiness_rule_migrated_v3) {
			    $check_break_time = false;
			    // || $get_schedule_settings->enable_additional_breaks == "yes"
			    
			    if($get_schedule_settings->enable_lunch_break == "yes" || $get_schedule_settings->enable_additional_breaks == "yes") {
			        if($get_schedule_settings->track_break_1 == "yes" || $get_schedule_settings->track_break_2 == "yes") {
			            
			            $break_in_min = $get_schedule_settings->break_in_min + $get_schedule_settings->break_1_in_min + $get_schedule_settings->break_2_in_min;
			            if($break_in_min > 0) {
			                
			                $check_break_time = true;
			            }
			        }
			    }
			}
			
			$check_holiday = $this->employee->get_holiday_date(date('Y-m-d', strtotime($employee_timein_date)),$this->emp_id,$this->company_id);
			if(check_if_enable_breaks_on_holiday($this->company_id,$work_schedule_id)) {
			    $check_holiday = false;
			}
			
			$Lunch_break = false;
			$break_1 = false;
			$break_2 = false;
			$lunch_break_hours_started = "";
			$lunch_break_hours_ended = "";
			$break_1_start = "";
			$break_1_end = "";
			$break_2_start = "";
			$break_2_end = "";
			
			$existing_log = false;
			if($flag != '1') {
				if($check_existing_timein) {
					$existing_log = true;
				}
			}
			
			if(!$check_break_time || $existing_log || $flag_halfday == 1 || $check_holiday){
				// if there is no break ("0")
				$number_of_breaks_per_day = 0;
			} else {
				// if there is a break ("1")
				$number_of_breaks_per_day = 1;
			}
			
			$this->form_validation->set_rules("employee_timein_date", 'Employee Time in Date', 'trim|required|xss_clean');
			
			if ($this->form_validation->run()==true){
				if($split['start_time'] == "") {
				    if($number_of_breaks_per_day == 0){
						echo json_encode(array(
								"error" => false,
								"break_in_min" => false,
    							    "lunch_break_hours_started" => ($lunch_break_hours_started != "") ? $lunch_break_hours_started : NULL,
    							    "lunch_break_hours_ended" => ($lunch_break_hours_ended != "") ? $lunch_break_hours_ended : NULL,
    							    "break_1" => $break_1,
    							    "break_1_hours_started" => ($break_1_start != "") ? $break_1_start : NULL,
    							    "break_1_hours_ended" => ($break_1_end != "") ? $break_1_end : NULL,
    							    "break_2" => $break_2,
    							    "break_2_hours_started" => ($break_2_start != "") ? $break_2_start : NULL,
    							    "break_2_hours_ended" => ($break_2_end != "") ? $break_2_end : NULL,
								"existing_log" => $existing_log,
						));
						return false;
					} else {
						$is_work = is_break_assumed($work_schedule_id);
					
						if($is_work) {
							if($check_break_time_for_assumed) {
								#$check_work_type_form = $this->employee->work_schedule_type($work_schedule_id, $this->company_id);
								$input_timein = date('Y-m-d', strtotime($employee_timein_date1)).' '.$new_employee_timein_time;
								$input_timein = date('Y-m-d H:i:s', strtotime($input_timein));
								if($check_work_type == "Uniform Working Days"){
									$grace = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
									$h = $is_work->assumed_breaks * 60;
									$start_wo_thres = date('Y-m-d', strtotime($employee_timein_date)).' '.$check_break_time_for_assumed->work_start_time;
									$start_w_thres = date('Y-m-d H:i:s', strtotime($start_wo_thres.' +'.$grace.' minutes'));
										
									if(strtotime($start_w_thres) >= strtotime($input_timein) && strtotime($start_wo_thres) <= strtotime($input_timein)) {
										$add_date = $input_timein;
									} elseif(strtotime($start_wo_thres) >= strtotime($input_timein)) {
										$add_date = $start_wo_thres;
									} else {
										$add_date = $start_w_thres;
									}
										
									$lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
									$lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_break_time_for_assumed->break_in_min} minutes"));
								}else if($check_work_type == "Flexible Hours"){
									//$grace = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
									if($check_break_time_for_assumed->latest_time_in_allowed == "" || $check_break_time_for_assumed->latest_time_in_allowed == null) {
										$add_date = $employee_timein_date.' '.$new_employee_timein_time;
									} else {
										$allowed_time_in = $employee_timein_date.' '.$check_break_time_for_assumed->latest_time_in_allowed;
										$latest_input = $employee_timein_date.' '.$new_employee_timein_time;
										if(strtotime($allowed_time_in) < strtotime($latest_input)) {
											$add_date = $allowed_time_in;
										} else {
											$add_date = $latest_input;
										}
									}
										
									$h = $is_work->assumed_breaks * 60;
									#$add_date = $employee_timein_date.' '.$new_employee_timein_time;
									$lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
									$lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_break_time_for_assumed->duration_of_lunch_break_per_day} minutes"));
								}
					
								echo json_encode(array(
								    "error" => true,
								    "break_in_min" => true,
								    "lunch_break_hours_started" => ($lunch_break_hours_started != "") ? $lunch_break_hours_started : NULL,
								    "lunch_break_hours_ended" => ($lunch_break_hours_ended != "") ? $lunch_break_hours_ended : NULL,
								    "break_1" => $break_1,
								    "break_1_hours_started" => ($break_1_start != "") ? $break_1_start : NULL,
								    "break_1_hours_ended" => ($break_1_end != "") ? $break_1_end : NULL,
								    "break_2" => $break_2,
								    "break_2_hours_started" => ($break_2_start != "") ? $break_2_start : NULL,
								    "break_2_hours_ended" => ($break_2_end != "") ? $break_2_end : NULL,
								    "assumed" => true,
								    "lunch_out_date" => date('m/d/Y', strtotime($lunch_out)),
								    "lunch_out_time" => date('h:i A', strtotime($lunch_out)),
										"lunch_in_date" => date('m/d/Y', strtotime($lunch_in)),
										"lunch_in_time" => date('h:i A', strtotime($lunch_in)),
										"lunch_out_hr" => date('h', strtotime($lunch_in)),
										"lunch_out_min" => date('i', strtotime($lunch_in)),
										"lunch_out_ampm" => date('A', strtotime($lunch_in)),
										"lunch_in_hr" => date('h', strtotime($lunch_in)),
										"lunch_in_min" => date('i', strtotime($lunch_in)),
										"lunch_in_ampm" => date('A', strtotime($lunch_in)),
										"existing_log" => $existing_log
					
								));
								return false;
							}
						} else {
						    if($tardiness_rule_migrated_v3) {
						        if($get_schedule_settings) {
						            if($get_schedule_settings->enable_lunch_break == "yes") {
						                if($get_schedule_settings->track_break_1 == "yes") {
						                    if($get_schedule_settings->break_schedule_1 == "fixed") {
						                        $h = $get_schedule_settings->break_started_after * 60;
						                        $lunch_break_datehours_started = date('Y-m-d', strtotime($employee_timein_date)).' '.$get_schedule_settings->work_start_time;
						                        $lunch_break_hours_started = date("Y-m-d H:i:s", strtotime($lunch_break_datehours_started." +".$h." minutes"));
						                        $lunch_break_hours_ended = date("Y-m-d H:i:s", strtotime($lunch_break_hours_started." +".$get_schedule_settings->break_in_min." minutes"));
						                    }
						                    
						                    $Lunch_break = true;
						                }
						            }
						            
						            if($get_schedule_settings->enable_additional_breaks == "yes") {
						                if($get_schedule_settings->track_break_2 == "yes") {
						                    if($get_schedule_settings->num_of_additional_breaks > 0) {
						                        if($get_schedule_settings->break_schedule_2 == "fixed") {
						                            if($get_schedule_settings->additional_break_started_after_1 != "") {
						                                $h = $get_schedule_settings->additional_break_started_after_1 * 60;
						                                $lunch_break_datehours_started = date('Y-m-d', strtotime($employee_timein_date)).' '.$get_schedule_settings->work_start_time;
						                                $break_1_start = date("Y-m-d H:i:s", strtotime($lunch_break_datehours_started." +".$h." minutes"));
						                                $break_1_end = date("Y-m-d H:i:s", strtotime($break_1_start." +".$get_schedule_settings->break_1_in_min." minutes"));
						                            }
						                            
						                            if($get_schedule_settings->additional_break_started_after_2 != "") {
						                                $h = $get_schedule_settings->additional_break_started_after_2 * 60;
						                                $lunch_break_datehours_started = date('Y-m-d', strtotime($employee_timein_date)).' '.$get_schedule_settings->work_start_time;
						                                $break_2_start = date("Y-m-d H:i:s", strtotime($lunch_break_datehours_started." +".$h." minutes"));
						                                $break_2_end = date("Y-m-d H:i:s", strtotime($break_2_start." +".$get_schedule_settings->break_2_in_min." minutes"));
						                            }
						                        }
						                        if($get_schedule_settings->num_of_additional_breaks == 2) {
						                            $break_1 = true;
						                            $break_2 = true;
						                        }
						                        
						                        if($get_schedule_settings->num_of_additional_breaks == 1) {
						                            $break_1 = true;
						                        }
					                            
						                        
						                    }
						                }
						            }
						        }
						        echo json_encode(array(
						            "error" => true,
						            "break_in_min" => $Lunch_break, // for lunch break 
						            "lunch_break_hours_started" => ($lunch_break_hours_started != "") ? $lunch_break_hours_started : NULL,
						            "lunch_break_hours_ended" => ($lunch_break_hours_ended != "") ? $lunch_break_hours_ended : NULL,
						            "break_1" => $break_1,
						            "break_1_hours_started" => ($break_1_start != "") ? $break_1_start : NULL,
						            "break_1_hours_ended" => ($break_1_end != "") ? $break_1_end : NULL,
						            "break_2" => $break_2,
						            "break_2_hours_started" => ($break_2_start != "") ? $break_2_start : NULL,
						            "break_2_hours_ended" => ($break_2_end != "") ? $break_2_end : NULL,
						            "existing_log" => $existing_log,
						        ));
						        return false;
						    } else {
						        echo json_encode(array(
						            "error" => true,
						            "break_in_min" => true,
						            "lunch_break_hours_started" => ($lunch_break_hours_started != "") ? $lunch_break_hours_started : NULL,
						            "lunch_break_hours_ended" => ($lunch_break_hours_ended != "") ? $lunch_break_hours_ended : NULL,
						            "break_1" => $break_1,
						            "break_1_hours_started" => ($break_1_start != "") ? $break_1_start : NULL,
						            "break_1_hours_ended" => ($break_1_end != "") ? $break_1_end : NULL,
						            "break_2" => $break_2,
						            "break_2_hours_started" => ($break_2_start != "") ? $break_2_start : NULL,
						            "break_2_hours_ended" => ($break_2_end != "") ? $break_2_end : NULL,
						            "existing_log" => $existing_log,
						        ));  
						        return false;
						    }
							
						}
					}
				} else {
					return false;
				}
			} else {
			    echo json_encode(array(
			        "error" => false,
			        "break_in_min" => false,
			        "lunch_break_hours_started" => ($lunch_break_hours_started != "") ? $lunch_break_hours_started : NULL,
			        "lunch_break_hours_ended" => ($lunch_break_hours_ended != "") ? $lunch_break_hours_ended : NULL,
			        "break_1" => $break_1,
			        "break_1_hours_started" => ($break_1_start != "") ? $break_1_start : NULL,
			        "break_1_hours_ended" => ($break_1_end != "") ? $break_1_end : NULL,
			        "break_2" => $break_2,
			        "break_2_hours_started" => ($break_2_start != "") ? $break_2_start : NULL,
			        "break_2_hours_ended" => ($break_2_end != "") ? $break_2_end : NULL,
			        "existing_log" => $existing_log,
			    ));
			    return false;
			}
			
		}
		
		// check break then remove lunch out / lunch in for uniform working and flexible
		public function remove_break_of_uf_OLD()  {
			$employee_timein_date = $this->input->post('employee_timein_date');
			$employee_timein_date1 = $this->input->post('employee_timein_date1');
			$new_employee_timein_time = $this->input->post('timeIn');
					
			$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date('Y-m-d', strtotime($employee_timein_date)));
			$check_break_time = $this->employee->check_break_time($work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($employee_timein_date)));
			$check_break_time_for_assumed = $this->employee->check_break_time_for_assumed($work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($employee_timein_date)));
			#$check_holiday = $this->employee->get_holiday_date(date('Y-m-d', strtotime($employee_timein_date)),$this->emp_id,$this->company_id);
			$check_work_type = $this->employee->work_schedule_type($work_schedule_id, $this->company_id);
			
			$check_existing_timein = $this->employee->check_existing_timein_date($this->emp_id,$this->company_id,date('Y-m-d', strtotime($employee_timein_date)));
			
			$existing_log = false;
			if($check_existing_timein) {
				$existing_log = true;
			}
		
			if(!$check_break_time || $existing_log){
				// if there is no break ("0")
				$number_of_breaks_per_day = 0;
			} else {
				// if there is a break ("1")
				$number_of_breaks_per_day = 1;
			}
			
			if ($employee_timein_date){
				if($number_of_breaks_per_day == 0){
					echo json_encode(array(
							"result" => 1,
							"break_in_min" => false,
							"existing_log" => $existing_log,
					));
					return false;
				} else {
					$is_work = is_break_assumed($work_schedule_id);
		
					if($is_work) {
						if($check_break_time_for_assumed) {
							#$check_work_type_form = $this->employee->work_schedule_type($work_schedule_id, $this->company_id);
							$input_timein = date('Y-m-d', strtotime($employee_timein_date1)).' '.$new_employee_timein_time;
							$input_timein = date('Y-m-d H:i:s', strtotime($input_timein));
							if($check_work_type == "Uniform Working Days"){
								$grace = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
								$h = $is_work->assumed_breaks * 60;
								$start_wo_thres = date('Y-m-d', strtotime($employee_timein_date)).' '.$check_break_time_for_assumed->work_start_time;
								$start_w_thres = date('Y-m-d H:i:s', strtotime($start_wo_thres.' +'.$grace.' minutes'));
								
								if(strtotime($start_w_thres) >= strtotime($input_timein) && strtotime($start_wo_thres) <= strtotime($input_timein)) {
									$add_date = $input_timein;
								} elseif(strtotime($start_wo_thres) >= strtotime($input_timein)) {
									$add_date = $start_wo_thres;
								} else {
									$add_date = $start_w_thres;
								}
								
								$lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
								$lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_break_time_for_assumed->break_in_min} minutes"));
							}else if($check_work_type == "Flexible Hours"){
								//$grace = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
								if($check_break_time_for_assumed->latest_time_in_allowed == "" || $check_break_time_for_assumed->latest_time_in_allowed == null) {
									$add_date = $employee_timein_date.' '.$new_employee_timein_time;
								} else {
									$add_date = $employee_timein_date.' '.$check_break_time_for_assumed->latest_time_in_allowed;
								}
								
								$h = $is_work->assumed_breaks * 60;
								#$add_date = $employee_timein_date.' '.$new_employee_timein_time;
								$lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
								$lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_break_time_for_assumed->duration_of_lunch_break_per_day} minutes"));
							}
		
							echo json_encode(array(
									"result" => 1,
									"break_in_min" => true,
									"assumed" => true,
									"lunch_out_date" => idates($lunch_out), //date('m/d/Y', strtotime($lunch_out)),
									"lunch_out_time" => date('h:i A', strtotime($lunch_out)),
									"lunch_in_date" => idates($lunch_in), //date('m/d/Y', strtotime($lunch_in)),
									"lunch_in_time" => date('h:i A', strtotime($lunch_in)),
									"lunch_out_hr" => date('h', strtotime($lunch_in)),
									"lunch_out_min" => date('i', strtotime($lunch_in)),
									"lunch_out_ampm" => date('A', strtotime($lunch_in)),
									"lunch_in_hr" => date('h', strtotime($lunch_in)),
									"lunch_in_min" => date('i', strtotime($lunch_in)),
									"lunch_in_ampm" => date('A', strtotime($lunch_in)),
									"existing_log" => $existing_log,
		
		
							));
							return false;
						}
					} else {
						echo json_encode(array(
								"result" => 1,
								"break_in_min" => true,
								"assumed" => false,
								"existing_log" => $existing_log,
						));
						return false;
					}
				}
			} else {
				echo json_encode(array(
						"result" => 1,
						"break_in_min" => false,
						"existing_log" => $existing_log,
				));
				return false;
			}
		}

		public function load_changelog_data()
		{
			
			$employee_time_in_id = $this->input->post('employee_time_in_id');
			$get_timein_info = $this->employee->get_timein_info($this->company_id, $this->emp_id, $employee_time_in_id);
			
			if($employee_time_in_id) {
				if($get_timein_info){
					
					$shift_date = explode(" ",$get_timein_info->date);
					$time_in_date = explode(" ",$get_timein_info->time_in);
					$lunch_out_date = explode(" ",$get_timein_info->lunch_out);
					$lunch_in_date = explode(" ",$get_timein_info->lunch_in);
					$time_out_date = explode(" ",$get_timein_info->time_out);
					
					$break_1_start_date = explode(" ",$get_timein_info->break1_out);
					$break_1_end_date = explode(" ",$get_timein_info->break1_in);
					$break_2_start_date = explode(" ",$get_timein_info->break2_out);
					$break_2_end_date = explode(" ",$get_timein_info->break2_in);
					
					$break_1_start_time = ($get_timein_info->break1_out == $this->zero_time) ? "" : date("h:i A",strtotime($get_timein_info->break1_out));
					$break_1_end_time = ($get_timein_info->break1_in == $this->zero_time) ? "" : date("h:i A",strtotime($get_timein_info->break1_in));
					$break_2_start_time = ($get_timein_info->break2_out == $this->zero_time) ? "" : date("h:i A",strtotime($get_timein_info->break2_out));
					$break_2_end_time = ($get_timein_info->break2_in == $this->zero_time) ? "" : date("h:i A",strtotime($get_timein_info->break2_in));
					
					$tim_in = $time_in_date[0];
					$tim_in = date("h:i A",strtotime($get_timein_info->time_in));
					$lunch_out = ($get_timein_info->lunch_out==$this->zero_time) ? "" : date("h:i A",strtotime($get_timein_info->lunch_out));
					$lunch_in = ($get_timein_info->lunch_in==$this->zero_time) ? "" : date("h:i A",strtotime($get_timein_info->lunch_in));
					$time_out = ($get_timein_info->time_out==$this->zero_time) ? "" : date("h:i A",strtotime($get_timein_info->time_out));
					
					$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$get_timein_info->date);
					$check_break_time = $this->employee->check_break_time($work_schedule_id,$this->company_id,"work_schedule_id", $get_timein_info->date);
					$check_break_time_for_assumed = $this->employee->check_break_time_for_assumed($work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($get_timein_info->date)));
					$check_work_type = $this->employee->work_schedule_type($work_schedule_id, $this->company_id);
					$check_if_excess_logs = $this->employee->check_if_excess_logs($this->emp_id,$this->company_id,$employee_time_in_id);
					$approver_id = $this->employee->get_approver_name_timein($this->emp_id,$this->company_id)->attendance_adjustment_approval_grp;
					
					$tardiness_rule_migrated_v3 = tardiness_rule_migrated_v3($this->company_id,$work_schedule_id);
					$get_schedule_settings = get_schedule_settings_by_workschedule_id($work_schedule_id,$this->company_id,date("l", strtotime($get_timein_info->date)));
					
					if($tardiness_rule_migrated_v3) {
					    $check_break_time = false;
					    // || $get_schedule_settings->enable_additional_breaks == "yes"
					    if($get_schedule_settings->enable_lunch_break == "yes" || $get_schedule_settings->enable_additional_breaks == "yes") {
					        if($get_schedule_settings->track_break_1 == "yes" || $get_schedule_settings->track_break_2 == "yes") {
					            $break_in_min = $get_schedule_settings->break_in_min + $get_schedule_settings->break_1_in_min + $get_schedule_settings->break_2_in_min;
					            if($break_in_min > 0) {
					                $check_break_time = true;
					            }
					        }
					    }
					}
					
					$excess_logs = false;
                    if($check_if_excess_logs) {
                          $excess_logs = true;
                    }
					
					if(!$check_break_time || $excess_logs == true){
						// if there is no break ("0")
						$number_of_breaks_per_day = 0;
					} else {
						// if there is a break ("1")
						$number_of_breaks_per_day = 1;
					}
					
					$locked = "";
					$void = $this->employee->edit_delete_void($this->emp_id,$this->company_id,date("Y-m-d", strtotime($get_timein_info->date)));
					$disabled_btn = false;
					$no_approver_msg_locked = "Payroll for the period affected is locked. No new requests, adjustments or changes can be accepted. Please reach out to your HR or payroll manager to discuss options for this request.";
					$no_approver_msg_closed = "Payroll for the period affected is closed. No new requests, adjustments or changes can be accepted. Please reach out to your HR or payroll manager to discuss options for this request.";
					
					if($approver_id) {
					    if(is_workflow_enabled($this->company_id)) {
					        if($void == "Waiting for approval"){
					            $locked = "Warning : Timesheets locked for payroll processing.";
					        } elseif ($void == "Closed") {
					            $locked = "Warning : The timesheet you are submitting is part of a closed payroll. Your request will be routed to the appropriate approvers for consideration and approval.";
					        }
					    } else {
					        if($void == "Waiting for approval"){
					            $locked = $no_approver_msg_locked;
					            $disabled_btn = true;
					        } elseif ($void == "Closed") {
					            $locked = $no_approver_msg_closed;
					            $disabled_btn = true;
					        }
					    }
					} else {
					    if($void == "Waiting for approval"){
					        $locked = $no_approver_msg_locked;
					        $disabled_btn = true;
					    } elseif ($void == "Closed") {
					        $locked = $no_approver_msg_closed;
					        $disabled_btn = true;
					    }
					}
					
					$current_time_val = date("Y-m-d");
					$startDate = strtotime("{$get_timein_info->date}");
					$endDate = strtotime("{$current_time_val}");
					$interval = $endDate - $startDate;
					$days = floor($interval / (60 * 60 * 24));
					
					$Lunch_break = false;
					$break_1 = false;
					$break_2 = false;
					$lunch_break_hours_started = "";
					$lunch_break_hours_ended = "";
					$break_1_start = "";
					$break_1_end = "";
					$break_2_start = "";
					$break_2_end = "";
					
					if($number_of_breaks_per_day == 0){
						print json_encode(array(
							"result"			   => 1,
							"success"              => 1,
							"no_of_days"           => $days,
							"date"                 => ($shift_date[0] == "") ? "" : date("m/d/Y",strtotime($shift_date[0])),
							"employee_time_in_id"  => $get_timein_info->employee_time_in_id,
							"time_in_date"         => ($time_in_date[0] == "") ? "" : date("m/d/Y",strtotime($time_in_date[0])),
							"lunch_out_date"       => ($lunch_out_date[0] == "") ? "" : date("m/d/Y",strtotime($lunch_out_date[0])),
							"lunch_in_date"        => ($lunch_in_date[0] == "") ? "" : date("m/d/Y",strtotime($lunch_in_date[0])),
							"time_out_date"        => ($time_out_date[0] == "") ? "" : date("m/d/Y",strtotime($time_out_date[0])),
						    
						    "break_1_start_date"   => ($break_1_start_date[0] == "") ? "" : date("m/d/Y",strtotime($break_1_start_date[0])),
						    "break_1_end_date"     => ($break_1_end_date[0] == "") ? "" : date("m/d/Y",strtotime($break_1_end_date[0])),
						    "break_2_start_date"   => ($break_2_start_date[0] == "") ? "" : date("m/d/Y",strtotime($break_2_start_date[0])),
						    "break_2_end_date"     => ($break_2_end_date[0] == "") ? "" : date("m/d/Y",strtotime($break_2_end_date[0])),
						    
						    "break_1_start_time"   => $break_1_start_time,
						    "break_1_end_time"     => $break_1_end_time,
						    "break_2_start_time"   => $break_2_start_time,
						    "break_2_end_time"     => $break_2_end_time,
						    
							"time_in"              => $tim_in,
							"lunch_out"            => $lunch_out,
							"lunch_in"             => $lunch_in,
							"time_out"             => $time_out,
							"break_in_min"         => 0,
							"reason"               => $get_timein_info->reason,
							"time_in_date_entry"   => ($time_in_date[0] == "") ? "" : date("d-M-y",strtotime($time_in_date[0])),
							"lunch_out_date_entry" => ($lunch_out_date[0] == "") ? "" : date("d-M-y",strtotime($lunch_out_date[0])),
							"lunch_in_date_entry"  => ($lunch_in_date[0] == "") ? "" : date("d-M-y",strtotime($lunch_in_date[0])),
							"time_out_date_entry"  => ($time_out_date[0] == "") ? "" : date("d-M-y",strtotime($time_out_date[0])),
						    
						    "break_1_start_date_entry"   => ($break_1_start_date[0] == "") ? "" : date("d-M-y",strtotime($break_1_start_date[0])),
						    "break_1_end_date_entry" => ($break_1_end_date[0] == "") ? "" : date("d-M-y",strtotime($break_1_end_date[0])),
						    "break_2_start_date_entry"  => ($break_2_start_date[0] == "") ? "" : date("d-M-y",strtotime($break_2_start_date[0])),
						    "break_2_end_date_entry"  => ($break_2_end_date[0] == "") ? "" : date("d-M-y",strtotime($break_2_end_date[0])),
						    
							"locked_payroll"       => $locked,
						    "submit_btn"           => $disabled_btn,
						    
						    "break_1"               => $break_1,
						    "break_2"               => $break_2,
						));
						return false;
					} else {
						$is_work = is_break_assumed($work_schedule_id);
					
						if($is_work) {
							if($check_break_time_for_assumed) {
								if($check_work_type == "Uniform Working Days"){
									$grace = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
									#$add_date = $get_timein_info->time_in;
									$add_date = date('Y-m-d', strtotime($get_timein_info->time_in)).' '.$check_break_time_for_assumed->work_start_time;
									$h = $is_work->assumed_breaks * 60 + $grace;
									$lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
									$lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_break_time_for_assumed->break_in_min} minutes"));
									$fritz_break_in_min = $check_break_time_for_assumed->break_in_min;
								}else if($check_work_type == "Flexible Hours"){
									//$grace = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
									if($check_break_time_for_assumed->latest_time_in_allowed == "" || $check_break_time_for_assumed->latest_time_in_allowed == null) {
										$add_date = $get_timein_info->time_in;
									} else {
										$add_date = date("Y-m-d", strtotime($get_timein_info->time_in)).' '.$check_break_time_for_assumed->latest_time_in_allowed;
									}
								
									$h = $is_work->assumed_breaks * 60;
									#$add_date = $employee_timein_date.' '.$new_employee_timein_time;
									$lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
									$lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_break_time_for_assumed->duration_of_lunch_break_per_day} minutes"));
									$fritz_break_in_min = $check_break_time_for_assumed->duration_of_lunch_break_per_day;
								}
								
								print json_encode(array(
									"result"			   => 1,
									"success"				=> 1,
									"no_of_days"			=> $days,
									"date" 					=> ($shift_date[0] == "") ? "" : date("m/d/Y",strtotime($shift_date[0])),
									"employee_time_in_id"	=> $get_timein_info->employee_time_in_id,
									"time_in_date"			=> ($time_in_date[0] == "") ? "" : date("m/d/Y",strtotime($time_in_date[0])),
									"lunch_out_date"		=> date('m/d/Y', strtotime($lunch_out)),
									"lunch_in_date"			=> date('m/d/Y', strtotime($lunch_in)),
									"time_out_date"			=> ($time_out_date[0] == "") ? "" : date("m/d/Y",strtotime($time_out_date[0])),
								    
								    "break_1_start_date"   => ($break_1_start_date[0] == "") ? "" : date("m/d/Y",strtotime($break_1_start_date[0])),
								    "break_1_end_date"     => ($break_1_end_date[0] == "") ? "" : date("m/d/Y",strtotime($break_1_end_date[0])),
								    "break_2_start_date"   => ($break_2_start_date[0] == "") ? "" : date("m/d/Y",strtotime($break_2_start_date[0])),
								    "break_2_end_date"     => ($break_2_end_date[0] == "") ? "" : date("m/d/Y",strtotime($break_2_end_date[0])),
								    
								    "break_1_start_time"   => $break_1_start_time,
								    "break_1_end_time"     => $break_1_end_time,
								    "break_2_start_time"   => $break_2_start_time,
								    "break_2_end_time"     => $break_2_end_time,
								    
									"time_in"				=> $tim_in,
									"lunch_out"				=> date('h:i A', strtotime($lunch_out)),
									"lunch_in"				=> date('h:i A', strtotime($lunch_in)),
									"time_out"				=> $time_out,
									"break_in_min" 			=> $fritz_break_in_min,
									"reason"				=> $get_timein_info->reason,
									"assumed"				=> true,
									"time_in_date_entry"	=> ($time_in_date[0] == "") ? "" : date("d-M-y",strtotime($time_in_date[0])),
									"lunch_out_date_entry"	=> date('d-M-y', strtotime($lunch_out)),
									"lunch_in_date_entry"	=> date('d-M-y', strtotime($lunch_in)),
									"time_out_date_entry"     => ($time_out_date[0] == "") ? "" : date("d-M-y",strtotime($time_out_date[0])),
								    
								    "break_1_start_date_entry"   => ($break_1_start_date[0] == "") ? "" : date("d-M-y",strtotime($break_1_start_date[0])),
								    "break_1_end_date_entry" => ($break_1_end_date[0] == "") ? "" : date("d-M-y",strtotime($break_1_end_date[0])),
								    "break_2_start_date_entry"  => ($break_2_start_date[0] == "") ? "" : date("d-M-y",strtotime($break_2_start_date[0])),
								    "break_2_end_date_entry"  => ($break_2_end_date[0] == "") ? "" : date("d-M-y",strtotime($break_2_end_date[0])),
								    
								    "locked_payroll"          => $locked,
								    "submit_btn"              => $disabled_btn,
								    
								    "break_1"               => $break_1,
								    "break_2"               => $break_2,
								));
								return false;
							}
						} else {
						    
						    if($tardiness_rule_migrated_v3) {
						        if($get_schedule_settings) {
						            if($get_schedule_settings->enable_lunch_break == "yes") {
						                if($get_schedule_settings->track_break_1 == "yes") {
						                    if($get_schedule_settings->break_schedule_1 == "fixed") {
						                        $h = $get_schedule_settings->break_started_after * 60;
						                        $lunch_break_datehours_started = date('Y-m-d', strtotime($get_timein_info->date)).' '.$get_schedule_settings->work_start_time;
						                        $lunch_break_hours_started = date("Y-m-d H:i:s", strtotime($lunch_break_datehours_started." +".$h." minutes"));
						                        $lunch_break_hours_ended = date("Y-m-d H:i:s", strtotime($lunch_break_hours_started." +".$get_schedule_settings->break_in_min." minutes"));
						                    }
						                    
						                    $Lunch_break = true;
						                }
						            }
						            
						            if($get_schedule_settings->enable_additional_breaks == "yes") {
						                if($get_schedule_settings->track_break_2 == "yes") {
						                    if($get_schedule_settings->num_of_additional_breaks > 0) {
						                        if($get_schedule_settings->break_schedule_2 == "fixed") {
						                            if($get_schedule_settings->additional_break_started_after_1 != "") {
						                                $h = $get_schedule_settings->additional_break_started_after_1 * 60;
						                                $lunch_break_datehours_started = date('Y-m-d', strtotime($get_timein_info->date)).' '.$get_schedule_settings->work_start_time;
						                                $break_1_start = date("Y-m-d H:i:s", strtotime($lunch_break_datehours_started." +".$h." minutes"));
						                                $break_1_end = date("Y-m-d H:i:s", strtotime($break_1_start." +".$get_schedule_settings->break_1_in_min." minutes"));
						                            }
						                            
						                            if($get_schedule_settings->additional_break_started_after_2 != "") {
						                                $h = $get_schedule_settings->additional_break_started_after_2 * 60;
						                                $lunch_break_datehours_started = date('Y-m-d', strtotime($get_timein_info->date)).' '.$get_schedule_settings->work_start_time;
						                                $break_2_start = date("Y-m-d H:i:s", strtotime($lunch_break_datehours_started." +".$h." minutes"));
						                                $break_2_end = date("Y-m-d H:i:s", strtotime($break_2_start." +".$get_schedule_settings->break_2_in_min." minutes"));
						                            }
						                        }
						                        
						                        if($get_schedule_settings->num_of_additional_breaks == 2) {
						                            $break_1 = true;
						                            $break_2 = true;
						                        }
						                        
						                        if($get_schedule_settings->num_of_additional_breaks == 1) {
						                            $break_1 = true;
						                        }
						                    }
						                }
						            }
						        }
						       /* echo json_encode(array(
						            "error" => true,
						            "break_in_min" => $Lunch_break, // for lunch break
						            "lunch_break_hours_started" => $lunch_break_hours_started,
						            "lunch_break_hours_ended" => $lunch_break_hours_ended,
						            "break_1" => $break_1,
						            "break_1_hours_started" => $break_1_start,
						            "break_1_hours_ended" => $break_1_end,
						            "break_2" => $break_2,
						            "break_2_hours_started" => $break_2_start,
						            "break_2_hours_ended" => $break_2_end,
						            "existing_log" => $existing_log,
						        ));
						        return false;*/
						        if($Lunch_break == false) {
						            $number_of_breaks_per_day = 0;
						        } else {
						            $number_of_breaks_per_day = 1;
						        }
						        
						        print json_encode(array(
						        	"result"			   => 1,
						            "success"                 => 1,
						            "no_of_days"              => $days,
						            "date"                    => ($shift_date[0] == "") ? "" : date("m/d/Y",strtotime($shift_date[0])),
						            "employee_time_in_id"     => $get_timein_info->employee_time_in_id,
						            "time_in_date"            => ($time_in_date[0] == "") ? "" : date("m/d/Y",strtotime($time_in_date[0])),
						            "lunch_out_date"          => ($lunch_out_date[0] == "") ? "" : date("m/d/Y",strtotime($lunch_out_date[0])),
						            "lunch_in_date"           => ($lunch_in_date[0] == "") ? "" : date("m/d/Y",strtotime($lunch_in_date[0])),
						            "time_out_date"           => ($time_out_date[0] == "") ? "" : date("m/d/Y",strtotime($time_out_date[0])),
						            
						            "break_1_start_date"   => ($break_1_start_date[0] == "") ? "" : date("m/d/Y",strtotime($break_1_start_date[0])),
						            "break_1_end_date"     => ($break_1_end_date[0] == "") ? "" : date("m/d/Y",strtotime($break_1_end_date[0])),
						            "break_2_start_date"   => ($break_2_start_date[0] == "") ? "" : date("m/d/Y",strtotime($break_2_start_date[0])),
						            "break_2_end_date"     => ($break_2_end_date[0] == "") ? "" : date("m/d/Y",strtotime($break_2_end_date[0])),
						            
						            "break_1_start_time"   => $break_1_start_time,
						            "break_1_end_time"     => $break_1_end_time,
						            "break_2_start_time"   => $break_2_start_time,
						            "break_2_end_time"     => $break_2_end_time,
						            
						            "time_in"                 => $tim_in,
						            "lunch_out"               => $lunch_out,
						            "lunch_in"                => $lunch_in,
						            "time_out"                => $time_out,
						            "break_in_min"            => $number_of_breaks_per_day,
						            "reason"	                  => $get_timein_info->reason,
						            "time_in_date_entry"      => ($time_in_date[0] == "") ? "" : date("d-M-y",strtotime($time_in_date[0])),
						            "lunch_out_date_entry"    => ($lunch_out_date[0] == "") ? "" : date("d-M-y",strtotime($lunch_out_date[0])),
						            "lunch_in_date_entry"     => ($lunch_in_date[0] == "") ? "" : date("d-M-y",strtotime($lunch_in_date[0])),
						            "time_out_date_entry"     => ($time_out_date[0] == "") ? "" : date("d-M-y",strtotime($time_out_date[0])),
						            
						            "break_1_start_date_entry"   => ($break_1_start_date[0] == "") ? "" : date("d-M-y",strtotime($break_1_start_date[0])),
						            "break_1_end_date_entry" => ($break_1_end_date[0] == "") ? "" : date("d-M-y",strtotime($break_1_end_date[0])),
						            "break_2_start_date_entry"  => ($break_2_start_date[0] == "") ? "" : date("d-M-y",strtotime($break_2_start_date[0])),
						            "break_2_end_date_entry"  => ($break_2_end_date[0] == "") ? "" : date("d-M-y",strtotime($break_2_end_date[0])),
						            
						            "locked_payroll"          => $locked,
						            "submit_btn"              => $disabled_btn,
						            
						            "break_1"               => $break_1,
						            "break_2"               => $break_2,
						        ));
						        return false;
						    } else {
						        print json_encode(array(
						        	"result"			   => 1,
						            "success"                 => 1,
						            "no_of_days"              => $days,
						            "date"                    => ($shift_date[0] == "") ? "" : date("m/d/Y",strtotime($shift_date[0])),
						            "employee_time_in_id"     => $get_timein_info->employee_time_in_id,
						            "time_in_date"            => ($time_in_date[0] == "") ? "" : date("m/d/Y",strtotime($time_in_date[0])),
						            "lunch_out_date"          => ($lunch_out_date[0] == "") ? "" : date("m/d/Y",strtotime($lunch_out_date[0])),
						            "lunch_in_date"           => ($lunch_in_date[0] == "") ? "" : date("m/d/Y",strtotime($lunch_in_date[0])),
						            "time_out_date"           => ($time_out_date[0] == "") ? "" : date("m/d/Y",strtotime($time_out_date[0])),
						            
						            "break_1_start_date"   => ($break_1_start_date[0] == "") ? "" : date("m/d/Y",strtotime($break_1_start_date[0])),
						            "break_1_end_date"     => ($break_1_end_date[0] == "") ? "" : date("m/d/Y",strtotime($break_1_end_date[0])),
						            "break_2_start_date"   => ($break_2_start_date[0] == "") ? "" : date("m/d/Y",strtotime($break_2_start_date[0])),
						            "break_2_end_date"     => ($break_2_end_date[0] == "") ? "" : date("m/d/Y",strtotime($break_2_end_date[0])),
						            
						            "break_1_start_time"   => $break_1_start_time,
						            "break_1_end_time"     => $break_1_end_time,
						            "break_2_start_time"   => $break_2_start_time,
						            "break_2_end_time"     => $break_2_end_time,
						            
						            "time_in"                 => $tim_in,
						            "lunch_out"               => $lunch_out,
						            "lunch_in"                => $lunch_in,
						            "time_out"                => $time_out,
						            "break_in_min"            => $number_of_breaks_per_day,
						            "reason"	                  => $get_timein_info->reason,
						            "time_in_date_entry"      => ($time_in_date[0] == "") ? "" : date("d-M-y",strtotime($time_in_date[0])),
						            "lunch_out_date_entry"    => ($lunch_out_date[0] == "") ? "" : date("d-M-y",strtotime($lunch_out_date[0])),
						            "lunch_in_date_entry"     => ($lunch_in_date[0] == "") ? "" : date("d-M-y",strtotime($lunch_in_date[0])),
						            "time_out_date_entry"     => ($time_out_date[0] == "") ? "" : date("d-M-y",strtotime($time_out_date[0])),
						            
						            "break_1_start_date_entry"   => ($break_1_start_date[0] == "") ? "" : date("d-M-y",strtotime($break_1_start_date[0])),
						            "break_1_end_date_entry" => ($break_1_end_date[0] == "") ? "" : date("d-M-y",strtotime($break_1_end_date[0])),
						            "break_2_start_date_entry"  => ($break_2_start_date[0] == "") ? "" : date("d-M-y",strtotime($break_2_start_date[0])),
						            "break_2_end_date_entry"  => ($break_2_end_date[0] == "") ? "" : date("d-M-y",strtotime($break_2_end_date[0])),
						            
						            "locked_payroll"          => $locked,
						            "submit_btn"              => $disabled_btn,
						            
						            "break_1"               => $break_1,
						            "break_2"               => $break_2,
						        ));
						        return false;
						    }
						}
					}
				}	
			} else {
				print json_encode(array("success" => 0));
				return false;
			}
			//echo "shit";				
		
		}
		
		public function load_changelog_data_OLD() {
			$employee_time_in_id = $this->input->post('employee_time_in_id');
			$get_timein_info = $this->employee->get_timein_info($this->company_id, $this->emp_id, $employee_time_in_id);
			
			if($employee_time_in_id) {
				if($get_timein_info){
							
					$shift_date = explode(" ",$get_timein_info->date);
					$time_in_date = explode(" ",$get_timein_info->time_in);
					$lunch_out_date = explode(" ",$get_timein_info->lunch_out);
					$lunch_in_date = explode(" ",$get_timein_info->lunch_in);
					$time_out_date = explode(" ",$get_timein_info->time_out);

					$break_1_start_date = explode(" ",$get_timein_info->break1_out);
					$break_1_end_date = explode(" ",$get_timein_info->break1_in);
					$break_2_start_date = explode(" ",$get_timein_info->break2_out);
					$break_2_end_date = explode(" ",$get_timein_info->break2_in);
					
					$break_1_start_time = ($get_timein_info->break1_out == $this->zero_time) ? "" : date("h:i A",strtotime($get_timein_info->break1_out));
					$break_1_end_time = ($get_timein_info->break1_in == $this->zero_time) ? "" : date("h:i A",strtotime($get_timein_info->break1_in));
					$break_2_start_time = ($get_timein_info->break2_out == $this->zero_time) ? "" : date("h:i A",strtotime($get_timein_info->break2_out));
					$break_2_end_time = ($get_timein_info->break2_in == $this->zero_time) ? "" : date("h:i A",strtotime($get_timein_info->break2_in));
											
					$tim_in = $time_in_date[0];
					$tim_in = date("h:i A",strtotime($get_timein_info->time_in));
					$lunch_out = ($get_timein_info->lunch_out==$this->zero_time) ? "" : date("h:i A",strtotime($get_timein_info->lunch_out));
					$lunch_in = ($get_timein_info->lunch_in==$this->zero_time) ? "" : date("h:i A",strtotime($get_timein_info->lunch_in));
					$time_out = ($get_timein_info->time_out==$this->zero_time) ? "" : date("h:i A",strtotime($get_timein_info->time_out));
					
					$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$get_timein_info->date);
					$check_break_time = $this->employee->check_break_time($work_schedule_id,$this->company_id,"work_schedule_id", $get_timein_info->date);
					$check_break_time_for_assumed = $this->employee->check_break_time_for_assumed($work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($get_timein_info->date)));
					
					$check_work_type = $this->employee->work_schedule_type($work_schedule_id, $this->company_id);
					$check_if_excess_logs = $this->employee->check_if_excess_logs($this->emp_id,$this->company_id,$employee_time_in_id);
						
					$excess_logs = false;
                    if($check_if_excess_logs) {	                              
                    	$excess_logs = true;
                    }
						
					if(!$check_break_time || $excess_logs == true){
						// if there is no break ("0")
						$number_of_breaks_per_day = 0;
					} else {
						// if there is a break ("1")
						$number_of_breaks_per_day = 1;
					}
					
					$locked = false;
						
					$void = $this->employee->edit_delete_void($this->emp_id,$this->company_id,date("Y-m-d", strtotime($get_timein_info->date)));
					if($void == "Waiting for approval"){
						$locked = "Warning : Timesheets locked for payroll processing.";
					}
					
					$current_time_val = date("Y-m-d");
					$startDate = strtotime("{$get_timein_info->date}");
					$endDate = strtotime("{$current_time_val}");
					$interval = $endDate - $startDate;
					$days = floor($interval / (60 * 60 * 24));
					
					if($number_of_breaks_per_day == 0){
						print json_encode(array(
							"result"				=> 1,
							"no_of_days"			=> $days,
							"date" 					=> ($shift_date[0] == "") ? "" : idates($shift_date[0]),
							"employee_time_in_id"	=> $get_timein_info->employee_time_in_id,
							"time_in_date"			=> ($time_in_date[0] == "") ? "" : idates($time_in_date[0]),
							"lunch_out_date"		=> ($lunch_out_date[0] == "") ? "" : idates($lunch_out_date[0]),
							"lunch_in_date"			=> ($lunch_in_date[0] == "") ? "" : idates($lunch_in_date[0]),
							"time_out_date"			=> ($time_out_date[0] == "") ? "" : idates($time_out_date[0]),
							"time_in"				=> $tim_in,
							"lunch_out"				=> $lunch_out,
							"lunch_in"				=> $lunch_in,
							"time_out"				=> $time_out,
							"break_in_min" 			=> 0,
							"reason"				=> $get_timein_info->reason,
							"time_in_date_entry"	=> ($time_in_date[0] == "") ? "" : idates($time_in_date[0]),
							"lunch_out_date_entry"	=> ($lunch_out_date[0] == "") ? "" : idates($lunch_out_date[0]),
							"lunch_in_date_entry"	=> ($lunch_in_date[0] == "") ? "" : idates($lunch_in_date[0]),
							"time_out_date_entry"	=> ($time_out_date[0] == "") ? "" : idates($time_out_date[0]),
							"locked_payroll"		=> $locked
								
						));
						return false;
					} else {
						$is_work = is_break_assumed($work_schedule_id);
					
						if($is_work) {
							if($check_break_time_for_assumed) {
								if($check_work_type == "Uniform Working Days"){
									$grace = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
									#$add_date = $get_timein_info->time_in;
									$add_date = date('Y-m-d', strtotime($get_timein_info->time_in)).' '.$check_break_time_for_assumed->work_start_time;
									$h = $is_work->assumed_breaks * 60 + $grace;
									$lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
									$lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_break_time_for_assumed->break_in_min} minutes"));
									$fritz_break_in_min = $check_break_time_for_assumed->break_in_min;
								}else if($check_work_type == "Flexible Hours"){
									//$grace = ($check_break_time_for_assumed->latest_time_in_allowed) ? $check_break_time_for_assumed->latest_time_in_allowed : 0;
									if($check_break_time_for_assumed->latest_time_in_allowed == "" || $check_break_time_for_assumed->latest_time_in_allowed == null) {
										$add_date = $get_timein_info->time_in;
									} else {
										$add_date = date("Y-m-d", strtotime($get_timein_info->time_in)).' '.$check_break_time_for_assumed->latest_time_in_allowed;
									}
								
									$h = $is_work->assumed_breaks * 60;
									#$add_date = $employee_timein_date.' '.$new_employee_timein_time;
									$lunch_out = date('Y-m-d H:i:s',strtotime($add_date. " +{$h} minutes"));
									$lunch_in = date('Y-m-d H:i:s',strtotime($lunch_out. " +{$check_break_time_for_assumed->duration_of_lunch_break_per_day} minutes"));
									$fritz_break_in_min = $check_break_time_for_assumed->duration_of_lunch_break_per_day;
								}
								
								print json_encode(array(
									"result"				=> 1,
									"no_of_days"			=> $days,
									"date" 					=> ($shift_date[0] == "") ? "" : idates($shift_date[0]),
									"employee_time_in_id"	=> $get_timein_info->employee_time_in_id,
									"time_in_date"			=> ($time_in_date[0] == "") ? "" : idates($time_in_date[0]),
									"lunch_out_date"		=> idates($lunch_out),
									"lunch_in_date"			=> idates($lunch_in),
									"time_out_date"			=> ($time_out_date[0] == "") ? "" : idates($time_out_date[0]),
									"time_in"				=> $tim_in,
									"lunch_out"				=> date('h:i A', strtotime($lunch_out)),
									"lunch_in"				=> date('h:i A', strtotime($lunch_in)),
									"time_out"				=> $time_out,
									"break_in_min" 			=> $fritz_break_in_min,
									"reason"				=> $get_timein_info->reason,
									"assumed"				=> true,
									"time_in_date_entry"	=> ($time_in_date[0] == "") ? "" : idates($time_in_date[0]),
									"lunch_out_date_entry"	=> idates($lunch_out),
									"lunch_in_date_entry"	=> idates($lunch_in),
									"time_out_date_entry"	=> ($time_out_date[0] == "") ? "" : idates($time_out_date[0]),
									"locked_payroll"		=> $locked
								));
								return false;
							}
						} else {
							print json_encode(array(
								"result"				=> 1,
								"no_of_days"			=> $days,
								"date" 					=> ($shift_date[0] == "") ? "" : idates($shift_date[0]),
								"employee_time_in_id"	=> $get_timein_info->employee_time_in_id,
								"time_in_date"			=> ($time_in_date[0] == "") ? "" : idates($time_in_date[0]),
								"lunch_out_date"		=> ($lunch_out_date[0] == "") ? "" : idates($lunch_out_date[0]),
								"lunch_in_date"			=> ($lunch_in_date[0] == "") ? "" : idates($lunch_in_date[0]),
								"time_out_date"			=> ($time_out_date[0] == "") ? "" : idates($time_out_date[0]),
								"time_in"				=> $tim_in,
								"lunch_out"				=> $lunch_out,
								"lunch_in"				=> $lunch_in,
								"time_out"				=> $time_out,
								"break_in_min" 			=> $number_of_breaks_per_day,
								"reason"				=> $get_timein_info->reason,
								"time_in_date_entry"	=> ($time_in_date[0] == "") ? "" : idates($time_in_date[0]),
								"lunch_out_date_entry"	=> ($lunch_out_date[0] == "") ? "" : idates($lunch_out_date[0]),
								"lunch_in_date_entry"	=> ($lunch_in_date[0] == "") ? "" : idates($lunch_in_date[0]),
								"time_out_date_entry"	=> ($time_out_date[0] == "") ? "" : idates($time_out_date[0]),
								"locked_payroll"		=> $locked
							));
							return false;
						}
					}
				}
			}
		}
		
		public function get_approvers_name_and_status() {
			$employee_time_in_id = $this->input->post('employee_time_in_id');
			$last_source = $this->input->post('last_source');
			$time_in_status = $this->input->post('time_in_status');
			$change_log_date_filed = $this->input->post('change_log_date_filed');
			$source = $this->input->post('source');
			
			$time_in_info = $this->employee->emp_time_in_information($employee_time_in_id);
			$res = array();
			#if($row->last_source == "Adjusted") {
			if($last_source == null && $time_in_status == null && $change_log_date_filed != null || $last_source == "Adjusted") {
				$time_in_approver = $this->agm->get_approver_name_timein_change_logs($time_in_info->emp_id,$time_in_info->company_id);
				
				$numItems = count($time_in_approver);
				$i = 0;
				$workflow_approvers = workflow_approved_by_level($employee_time_in_id, 'attendance adjustment');
				$emp_timein = $this->employee->get_current_approver($employee_time_in_id);
				
				if($time_in_approver) {
					foreach ($time_in_approver as $la) {
						$last_level = $this->timeins->get_timein_last_hours($this->emp_id, $this->company_id,"0");
						if($last_source == null && $time_in_status == null && $change_log_date_filed != null || $time_in_status == "reject") {
							if($workflow_approvers) {
								if($emp_timein) {
									if($emp_timein->level == $la->level) {
										#echo $la->first_name.' '.$la->last_name.' - (Rejected)</br>';
										$name = $la->first_name.' '.$la->last_name.' - (Rejected)';
									} else if($emp_timein->level > $la->level) {
										#echo $la->first_name.' '.$la->last_name.' - (Approved)</br>';
										$name = $la->first_name.' '.$la->last_name.' - (Approved)';
									} else {
										#echo $la->first_name.' '.$la->last_name.' - (Rejected)</br>';
										$name = $la->first_name.' '.$la->last_name.' - (Rejected)';
									}
								}
							}
						} else {
							if($workflow_approvers) {
								if($emp_timein) {
									if($emp_timein->level == $la->level) {
										#echo $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')</br>';
										$name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
									} else if($emp_timein->level > $la->level) {
										#echo $la->first_name.' '.$la->last_name.' - (Approved)</br>';
										$name = $la->first_name.' '.$la->last_name.' - (Approved)';
									} else {
										#echo $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')</br>';
										$name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
									}
								}
			             		
							} else {
								if($time_in_status == "pending") {
									#echo $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')</br>';
									$name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
								} else {
									$name = "";
								}
							}
						}
						
						$app = array(
								"name" => $name
						);
						
						array_push($res,(object)$app);
					}
					
					echo json_encode($res);
					return false;
				} else {
					return false;
				}
			} else {
				if($source == "EP") {
					$change_time_in_approver = $this->agm->get_approver_name_timein_add_logs($time_in_info->emp_id,$time_in_info->company_id);
					
					$numItems = count($change_time_in_approver);
					$i = 0;
					$workflow_approvers = workflow_approved_by_level($employee_time_in_id, 'add timesheet');
					$x = count($workflow_approvers);
					if($change_time_in_approver) {
						foreach ($change_time_in_approver as $la) {
							$last_level = $this->timeins->get_timein_last_hours($this->emp_id, $this->company_id,"1");
							if($time_in_status == "reject") {
								if($workflow_approvers) {
									if($x > $la->level) {
										#echo $la->first_name.' '.$la->last_name.' - (Approved)</br>';
										$name =  $la->first_name.' '.$la->last_name.' - (Approved)';
									} elseif ($x == $last_level) {
										#echo $la->first_name.' '.$la->last_name.' - (Rejected)</br>';
										$name = $la->first_name.' '.$la->last_name.' - (Rejected)';
									} elseif($x < $la->level) {
										#echo $la->first_name.' '.$la->last_name.' - (Rejected)</br>';
										$name = $la->first_name.' '.$la->last_name.' - (Rejected)';
									} else {
										#echo $la->first_name.' '.$la->last_name.' - (Rejected)</br>';
										$name = $la->first_name.' '.$la->last_name.' - (Rejected)';
									}
								}
							} else {
								if($workflow_approvers) {
									foreach ($workflow_approvers as $wa) {
										if($wa->workflow_level == $la->level) {
											if($time_in_status == "pending") {
												#echo $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')</br>';
												$name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
											} else {
												#echo $la->first_name.' '.$la->last_name.' - (Approved)</br>';
												$name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
											}
										}else if($time_in_status == "pending") {
											#echo $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')</br>';
											$name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
										} else {
											#echo "";
											$name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
										}
									}
								} else {
									if($time_in_status == "pending") {
										#echo $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')</br>';
										$name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
									} else {
										#echo "";
										$name = "";
									}
								}
							}
							
							$app = array(
									"name" => $name
							);
							
							array_push($res,(object)$app);
						}
						
						echo json_encode($res);
						return false;
					} else {
						return false;
					}
				} else {
					if($source == "mobile") {
						$change_time_in_approver = $this->agm->get_approver_name_timein_location($time_in_info->emp_id,$time_in_info->company_id);
						
						$i = 0;
						$workflow_approvers = workflow_approved_by_level($employee_time_in_id, 'mobile clock in');
						$x = count($workflow_approvers);
						if($change_time_in_approver) {
							foreach ($change_time_in_approver as $la) {
								$time_in_status = $time_in_status;
								
								if($time_in_status == 'approve') {
									$time_in_status = "Approved";
								} elseif ($time_in_status == 'reject') {
									$time_in_status = "Rejected";
								}
								
								$name = $la->first_name.' '.$la->last_name.' - ('.$time_in_status.')';
								
								$app = array(
										"name" => $name
								);
								
								array_push($res,(object)$app);
								
							}
							
							echo json_encode($res);
							return false;
						}
					}
				}
			}
		}
	}