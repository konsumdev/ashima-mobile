<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
ini_set('memory_limit', '1024M');
ini_set('MAX_EXECUTION_TIME', '-1'); 

class Overtime extends CI_Controller{
	var $verify;
		
	public function __construct(){
	   parent::__construct();	
	   $this->load->model('konsumglobal_jmodel','jmodel');
	   $this->load->model('employee_model','employee');
	   $this->load->model('approval_model','approval');
	   $this->load->model('approval_group_model','agm');
	   $this->load->model('todo_overtime_model','todo_ot');
	   $this->load->model("approve_overtime_model","overtime");
	   $this->load->model("employee_mobile_model","mobile");

	   $this->load->model('emp_login_flexible_model','elmf'); // FLEXIBLE HOURS LOGIN MODEL
       $this->load->model('emp_login_model','elm');
       
       $this->load->model('employee_v2_model','employee_v2');
	  
	   $this->emp_id = $this->session->userdata('emp_id');
	   $this->company_id =$this->employee->check_company_id($this->emp_id); 
	   $this->account_id = $this->session->userdata('account_id');
	}	
	
	public function index() {
		$year = date("Y");
		$month = date("m");
		
		$page = $this->input->post('page');
		$limit = $this->input->post('limit');
		$status = $this->input->post('status');
		
		$this->per_page = 10;
			
		$get_overtime_list = $this->mobile->get_overtime_list($this->emp_id,$this->company_id,false,$status,(($page-1) * $this->per_page),$limit);
		$total = ceil($this->mobile->get_overtime_list($this->emp_id,$this->company_id,true) / 10);
		
		if($get_overtime_list) {
			echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "total" => $total,"list" => $get_overtime_list));
			return false;
		} else {
			echo json_encode(array("result" => "0"));
			return false;
		}
	}
	
	public function overtime_correction() {	    
	    $page = $this->input->post('page');
	    $limit = $this->input->post('limit');
	    
	    $this->per_page = 10;
	    
	    $get_overtime_list = $this->mobile->get_overtime_list_correction($this->emp_id,$this->company_id,false,(($page-1) * $this->per_page),$limit);
	    $total = ceil($this->mobile->get_overtime_list_correction($this->emp_id,$this->company_id,true) / 10);
	    
	    if($get_overtime_list) {
	        echo json_encode(array("result" => "1", "page" => $page, "numPages" => $limit, "total" => $total,"list" => $get_overtime_list));
	        return false;
	    } else {
	        echo json_encode(array("result" => "0"));
	        return false;
	    }
	}
	
	public function get_approvers(){
		$approver = $this->agm->get_approver_name_overtime($this->session->userdata('emp_id'), $this->company_id);
		
		$ot_id = $this->input->post('overtime_id');
		
		$row = $this->agm->overtime_information($ot_id);
		
		$overtime_id = $ot_id; //$this->agm->overtime_information($row->overtime_id);
		$final = array();
		if($row->overtime_status == "approved"){
			if($approver){
				$workforce_notification = get_workforce_notification_settings($this->company_id);
				$approval_ot = $this->employee->get_approval_ot($overtime_id);
				$level = $approval_ot->level;
				if($workforce_notification){
					if($level < 0 ){
						echo (get_owner($this->session->userdata('psa_id'))) ? get_owner($this->session->userdata('psa_id')) : "";
					}else{
						if($workforce_notification->option == "choose level notification"){
							for($count = 1 ; $count <= $level; $count++){
								if(check_if_is_level($count, $workforce_notification->workforce_alerts_notification_id)){
									foreach($approver as $app){
										if($app->level == $count){
											$temp = array("label" => "Approved By", "first_name" => $app->first_name, "last_name" => $app->last_name);
											array_push($final, $temp);
										}
									}
								}
							}
						}else{
							for($count = 1 ; $count <= $level; $count++){
								foreach($approver as $app){
									if($app->level == $count){
										$temp = array("label" => "Approved By", "first_name" => $app->first_name, "last_name" => $app->last_name);
										array_push($final, $temp);
									}
								}
							}
						}
					}
				}
			}
		
		}elseif($row->overtime_status == "pending"){
			if($approver){
				$workforce_notification = get_workforce_notification_settings($this->company_id);
				$approval_ot = $this->employee->get_approval_ot($overtime_id);
				$level = $approval_ot->level;
				if($workforce_notification){
					if($level < 0 ){
						echo (get_owner($this->session->userdata('psa_id'))) ? get_owner($this->session->userdata('psa_id')) : "";
					}else{
						$flag = 0;
						for($count = 1 ; $count < $level; $count++){
								
							if($flag == 0){
								$flag = 1;
							}
						}
		
						if($workforce_notification->option == "choose level notification"){
							for($count = 1 ; $count < $level; $count++){
								if(check_if_is_level($count, $workforce_notification->workforce_alerts_notification_id)){
									foreach($approver as $app){
										if($app->level == $count){											
											$temp = array("label" => "Approved By", "first_name" => $app->first_name, "last_name" => $app->last_name);
											array_push($final, $temp);
										}
									}
								}
							}
						}else{
							for($count = 1 ; $count < $level; $count++){
		
								foreach($approver as $app){
									if($app->level == $count){
										$temp = array("label" => "Approved By", "first_name" => $app->first_name, "last_name" => $app->last_name);
										array_push($final, $temp);
									}
								}
									
							}
						}
					}
				}
		
			}
		}elseif($row->overtime_status == "reject"){
			if($approver){				
				$approval_ot = $this->employee->get_approval_ot($overtime_id);
				$level = $approval_ot->level;
				if($level < 0 ){
					echo (get_owner($this->session->userdata('psa_id'))) ? get_owner($this->session->userdata('psa_id')) : "";
				}else{
					foreach($approver as $app){
						if($app->level == $level){
							$temp = array("label" => "Rejected By", "first_name" => $app->first_name, "last_name" => $app->last_name);
							array_push($final, $temp);
						}
					}
				}
			}
		}
		
		echo json_encode($final);
	}
	
	public function server_date() {
		echo json_encode(array("server_date"=> date('Y-m-d')));
	}
	
	public function apply_overtime(){
		$post = $this->input->post();

		$result = array(
            'result'=> 0,
			'error'=>true,
			'msg'=>"Application is temporarily unavailable on mobile app. You may file your application on the browser instead.",
		);
        echo json_encode($result);
        return false;
		
		$start_date = $post['start_date'];
		$end_date = $post['end_date'];
		$purpose = $post['purpose'];
		$resend_overtime_data_id = '';
		
		$startDate = date('Y-m-d', strtotime($start_date));
		$endDate = date('Y-m-d', strtotime($end_date));
		
		$start_time = date("H:i:s",strtotime($start_date));
		$end_time = date("H:i:s",strtotime($end_date));
 		$concat_start_date = date('Y-m-d H:i:s', strtotime($start_date));
 		$concat_end_date = date('Y-m-d H:i:s', strtotime($end_date));
        
        $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id, "overtime",$this->emp_id, date("Y-m-d", strtotime($startDate)));
        // $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id, "overtime");
        if($get_lock_payroll_process_settings) {
            if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                $result = array(
                    'result'=>true,
                    'error'=>true,
                    'msg'=>$get_lock_payroll_process_settings->application_error,
                );
                echo json_encode($result);
                return false;
            } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                $result = array(
                    'result'=>true,
                    'error'=>true,
                    'msg'=>$get_lock_payroll_process_settings->ts_app_recalculation_err_msg,
                );
                echo json_encode($result);
                return false;
            } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                $result = array(
                    'result'=>true,
                    'error'=>true,
                    'msg'=>$get_lock_payroll_process_settings->py_app_recalculation_err_msg,
                );
                echo json_encode($result);
                return false;
            }
        }
         
		if(strtotime($startDate) > strtotime($endDate)){
			$result = array(
                    'result'	=> 0,
                    'error'=>true,
					'msg'		=> "Start Date must not be greater than End date"
			);
			echo json_encode($result);
			return false;
		}elseif(strtotime($startDate) == strtotime($endDate)){
		
		
			if(strtotime($start_time) > strtotime($end_time)){
				$result = array(
                        'result'	=> 0,
                        'error'=>true,
						'msg'		=> "Start Time must not be greater than End Time"
				);
				echo json_encode($result);
				return false;
					
			}
				
		}

		if (!$purpose) {
			$result = array(
                    'result'	=> 0,
                    'error'		=>true,
					'msg'		=> "Please provide a reason."
			);
			echo json_encode($result);
			return false;
		}
			
		$void_v2 = $this->employee_v2->check_payroll_lock_closed($this->emp_id,$this->company_id,date("Y-m-d", strtotime($start_date)));
		// if one of the approver is inactive the approver group will automatically change to default (owner)
		change_approver_to_default($this->emp_id,$this->company_id,"overtime_approval_grp",$this->account_id);

		$employee_details = get_employee_details_by_empid($this->emp_id);
		
		$no_approver_msg_locked = "Payroll for the period affected is locked. No new overtime requests can be accepted. Please reach out to your HR or payroll manager to discuss options for this request.";
		$no_approver_msg_closed = "Payroll for the period affected is closed. No new overtime requests can be accepted. Please reach out to your HR or payroll manager to discuss options for this request.";
		$void = false;//$this->employee->edit_delete_void($this->emp_id,$this->company_id,date("Y-m-d", strtotime($start_date)));
		$locked = "";
		
		if(!$employee_details->overtime_approval_grp || !is_workflow_enabled($this->company_id)) {
		    if($void == "Waiting for approval"){
		        $locked = $no_approver_msg_locked;
		    } elseif ($void == "Closed") {
		        $locked = $no_approver_msg_closed;
		        
		    }
		    
		    if($locked != "") {
		        $result = array(
		            'result' => 0,
		            'error' => true,
		            'msg' => $locked,
		        );
		        
		        echo json_encode($result);
		        return false;
		    }
	 	}
		
		$check_double_timesheet_for_ot = $this->employee->check_double_timesheet_for_ot($this->emp_id, $start_date, $end_date);
		
		$current_date 				= ($check_double_timesheet_for_ot) ? $check_double_timesheet_for_ot->date : $start_date;
		$this->work_schedule_id 	= $this->employee->emp_work_schedule($this->emp_id,$this->company_id,date('Y-m-d', strtotime($current_date)));
		$check_sched 				= $this->employee->check_break_time_for_assumed($this->work_schedule_id,$this->company_id,"work_schedule_id", date('Y-m-d', strtotime($current_date)));
		$check_date_and_time_in 	= $this->employee->check_date_and_time_in(date('Y-m-d', strtotime($current_date)), $this->emp_id, $this->company_id);
		$get_work_sched_work_type 	= $this->get_work_sched_work_type($this->work_schedule_id,$this->company_id);
		$check_rest_day 			= $this->employee->check_rest_day_sched(date("l",strtotime($current_date)),$this->work_schedule_id,$this->company_id);
		$get_uniform_sched_time 	= $this->employee->get_uniform_sched_time($this->work_schedule_id, $this->emp_id, $this->company_id, date("Y-m-d",strtotime($current_date)));
		
		$check_existing_overtime_applied = $this->employee->check_existing_overtime_applied_v2($this->emp_id, $this->company_id,date('Y-m-d', strtotime($start_date)));
		
		if ($get_work_sched_work_type->work_type_name == "Open Shift") {
	        $result = array(
	        	'result'	=> 0,
	            'error'=>true,
	            'msg'=>'Oh snap! Open shift is not supported on mobile app.'
	        );
	        
	        echo json_encode($result);
	        return false;
	    }

		if($get_work_sched_work_type->work_type_name == "Workshift") {
	        $check_double_timesheet_for_ot = $this->employee->check_double_timesheet_for_ot_split($this->emp_id, $concat_start_date, $concat_end_date);
	    }

		if(!$check_double_timesheet_for_ot) {
			$result = array(
					'result'	=> 0,
					'error'=>true,
					'msg'=>"Overtime does not coincide with your timesheet."
			);
			echo json_encode($result);
			return false;
		} else {
			if($check_double_timesheet_for_ot) {
				if($check_double_timesheet_for_ot->time_in_status == "pending") {
					$result = array(
							'result'	=> 0,
							'error'=>true,
							'msg'=>"You have pending timesheet for this date. Please have your approver approve your timesheet first before applying overtime."
					);
					echo json_encode($result);
					return false;
				} elseif ($check_double_timesheet_for_ot->rest_day_r_a == "yes" && $check_double_timesheet_for_ot->flag_rd_include == "no") {
	                $result = array(
	                	'result'	=> 0,
	                    'error' => true,
	                    'msg' => "Oh Snap! It appears that the corresponding timesheet for this overtime request has not been submitted or is pending approval. All time logs on a Rest day requires approval. Please check your timesheet before applying for your overtime."
	                );
	                
	                echo json_encode($result);
	                return false;
	            } elseif ($check_double_timesheet_for_ot->holiday_approve == "yes" && $check_double_timesheet_for_ot->flag_holiday_include == "no") {
	                $result = array(
	                	'result'	=> 0,
	                    'error' => true,
	                    'msg' => "Oh Snap! It appears that the corresponding timesheet for this overtime request has not been submitted or is pending approval. All time logs on a Holiday requires approval. Please check your timesheet before applying for your overtime."
	                );
	                
	                echo json_encode($result);
	                return false;
	            }
			}
		}
		
		
		if($check_existing_overtime_applied) {
			$ot_existed = false;
	        $result = array();
	        foreach ($check_existing_overtime_applied as $row) {
	            $start_datetime_db = date("Y-m-d", strtotime($row->overtime_from)).' '.date("H:i:s", strtotime($row->start_time));
	            $end_datetime_db = date("Y-m-d", strtotime($row->overtime_to)).' '.date("H:i:s", strtotime($row->end_time));
	            
	            if(strtotime($start_datetime_db) <= strtotime($concat_start_date) && strtotime($end_datetime_db) > strtotime($concat_start_date)) {
	                $result = array(
	                    "error"                   => true,
	                    "existing_overtime"       => true,
	                    "overtime_id"             => $row->overtime_id,
	                    "overtime_date_applied"   => $row->overtime_date_applied,
	                    "overtime_from"           => $row->overtime_from,
	                    "overtime_to"             => $row->overtime_to,
	                    "start_time"              => date('h:i A', strtotime($row->start_time)),
	                    "end_time"                => date('h:i A', strtotime($row->end_time)),
	                    "overtime_status"         => $row->overtime_status,
	                    "msg"                 	  => 'You already have an existing overtime filed for this date and time.'
	                );
	                $ot_existed = true;
	            } elseif (strtotime($start_datetime_db) < strtotime($concat_end_date) && strtotime($end_datetime_db) >= strtotime($concat_end_date)) {
	                $result = array(
	                    "error"                   => true,
	                    "existing_overtime"       => true,
	                    "overtime_id"             => $row->overtime_id,
	                    "overtime_date_applied"   => $row->overtime_date_applied,
	                    "overtime_from"           => $row->overtime_from,
	                    "overtime_to"             => $row->overtime_to,
	                    "start_time"              => date('h:i A', strtotime($row->start_time)),
	                    "end_time"                => date('h:i A', strtotime($row->end_time)),
	                    "overtime_status"         => $row->overtime_status,
	                    "msg"                 	  => 'You already have an existing overtime filed for this date and time.'
	                );
	                $ot_existed = true;
	            }
	            
	        }

			if($ot_existed) {
	            echo json_encode($result);
	            return false;
	        }
		}

		$void = false; //$this->employee->edit_delete_void($this->emp_id,$this->company_id,date("Y-m-d", strtotime($current_date)));
		
		if($void == "Waiting for approval"){
		    $flag_payroll_correction = "yes";
		    $disabled_btn = true;
		} elseif ($void == "Closed") {
		    $flag_payroll_correction = "yes";
		} else {
		    $flag_payroll_correction = "no";
		}

		$grace_period = 0;
		$before_shift_ot = false;
		$thres_hold_use = false;
		$rd_flag = false;
        
        $average_working_hours_per_day = 8;
        $this_employee_emp_id = $this->emp_id;
		if($check_date_and_time_in) {

			if($void_v2 == "Closed" && $check_date_and_time_in->holiday_approve == "yes") {
	            $result = array(
	                'error' => true,
	                'msg' => "Post payroll adjustment cannot handle holiday and anything related to holiday adjusmtents."
	            );
	            
	            echo json_encode($result);
	            return false;
	        }

            $time_in = $check_date_and_time_in->time_in;
            $check_if_leave_for_ot = $this->employee->check_if_leave_for_ot($this_employee_emp_id, $this->company_id, date("Y-m-d",strtotime($current_date)));
            
            if($get_work_sched_work_type) {
                if($get_work_sched_work_type->work_type_name == "Uniform Working Days") {
                    if($check_sched) {
	                    $grace_period         = ($check_sched->latest_time_in_allowed) ? $check_sched->latest_time_in_allowed : 0;
	                    $db_start_datetime    = date('Y-m-d', strtotime($current_date)).' '.$check_sched->work_start_time;
	                    $valid_start_time     = date('Y-m-d H:i:s', strtotime($db_start_datetime.' +'.$grace_period.' minutes'));
	                    $total_work_hours	  = $check_sched->total_work_hours * 60;
	                    $db_end_datetime_temp = date('Y-m-d H:i:s', strtotime($valid_start_time.' +'.$total_work_hours.' minutes'));
	                    $db_end_datetime 	  = date('Y-m-d', strtotime($db_end_datetime_temp)).' '.$check_sched->work_end_time;
	                    $valid_end_time       = date('Y-m-d H:i:s', strtotime($db_end_datetime.' +'.$grace_period.' minutes'));
	                } else {
	                     #else {
                        $grace_period      = ($get_uniform_sched_time) ? $get_uniform_sched_time->latest_time_in_allowed : 0;
                        $db_start_datetime = date('Y-m-d', strtotime($current_date)).' '.$get_uniform_sched_time->work_start_time;
                        $valid_start_time  = date('Y-m-d H:i:s', strtotime($db_start_datetime.' +'.$grace_period.' minutes'));

                        $total_work_hours	  = $get_uniform_sched_time->total_work_hours * 60;
	                    $db_end_datetime_temp = date('Y-m-d H:i:s', strtotime($valid_start_time.' +'.$total_work_hours.' minutes'));
	                    $db_end_datetime 	  = date('Y-m-d', strtotime($db_end_datetime_temp)).' '.$get_uniform_sched_time->work_end_time;
                        $valid_end_time    = date('Y-m-d H:i:s', strtotime($db_end_datetime.' +'.$grace_period.' minutes'));
	                    #}
	                }
                    
                    if(strtotime($time_in) >= strtotime($valid_start_time)) {
                        if($check_if_leave_for_ot) {
                            if (strtotime($db_start_datetime) <= strtotime($check_if_leave_for_ot->date_start) && strtotime($valid_start_time) >= strtotime($check_if_leave_for_ot->date_start)) {
                                $get_diff = strtotime($check_if_leave_for_ot->date_start) - strtotime($db_start_datetime);
                                $valid_start_time = date('Y-m-d H:i:s', strtotime($db_start_datetime.' +'.$get_diff.' seconds'));
                                $valid_end_time = date('Y-m-d H:i:s', strtotime($db_end_datetime.' +'.$get_diff.' seconds'));
                                $thres_hold_use = true;
                            } elseif (strtotime($check_if_leave_for_ot->date_start) <= strtotime($db_start_datetime)) {
                                $valid_start_time = $db_start_datetime;
                                $valid_end_time = $db_end_datetime;
                            } else {
                                $valid_start_time = $valid_start_time;
                                $valid_end_time = $valid_end_time;
                                $thres_hold_use = true;
                            }
                        } else {
                            $valid_start_time = $valid_start_time;
                            $valid_end_time = $valid_end_time;
                            $thres_hold_use = true;
                        }
                        
                    } elseif (strtotime($db_start_datetime) <= strtotime($time_in) && strtotime($valid_start_time) >= strtotime($time_in)) {
                        $get_diff = strtotime($time_in) - strtotime($db_start_datetime);
                        $valid_start_time = date('Y-m-d H:i:s', strtotime($db_start_datetime.' +'.$get_diff.' seconds'));
                        $valid_end_time = date('Y-m-d H:i:s', strtotime($db_end_datetime.' +'.$get_diff.' seconds'));
                    } elseif(strtotime($time_in) <= strtotime($db_start_datetime)) {
                        $valid_start_time = $db_start_datetime;
                        $valid_end_time = $db_end_datetime;
                    }
                    
                    $concat_start_date_time = date('Y-m-d H:i:s', strtotime($concat_start_date));
                    $db_start_datetime_time = date('Y-m-d H:i:s', strtotime($db_start_datetime));
                    
                    if(strtotime($concat_start_date_time) < strtotime($db_start_datetime_time)) {
                        $before_shift_ot = true;
                    }                                   
                    
                } elseif ($get_work_sched_work_type->work_type_name == "Flexible Hours") {
                    $hours_worked = $this->elmf->get_hours_worked(date("Y-m-d",strtotime($current_date)), $this_employee_emp_id, $this->work_schedule_id);
                    
                    $check_latest_timein_allowed = $this->elmf->check_lastest_timein_allowed($this->work_schedule_id,$this->company_id);
                    $check_if_leave_for_ot = $this->employee->check_if_leave_for_ot($this_employee_emp_id,$this->company_id,$check_date_and_time_in->date);
                                                                                    
                    if($check_if_leave_for_ot) {
                        if(strtotime($time_in) > strtotime($check_if_leave_for_ot->date_start)) {
                            $db_start_datetime = $check_if_leave_for_ot->date_start;
                        } else {
                            $db_start_datetime = $time_in;
                        }
                    } else {
                        $db_start_datetime = $time_in;
                    }
                    
                    if(!$check_latest_timein_allowed) {
                        
                        $tot_hrs_for_d_day = $hours_worked;
                        
                        
                        $total_hours_for_the_day = number_format($tot_hrs_for_d_day); #$check_sched->total_hours_for_the_day;
                        $valid_end_time = date('Y-m-d H:i:s', strtotime($db_start_datetime.' +'.$total_hours_for_the_day.' hours'));
                        $valid_start_time = date('Y-m-d H:i:s', strtotime($db_start_datetime));
                        
                    } else {
                        $tot_hrs_for_d_day = $check_latest_timein_allowed->total_hours_for_the_day;
                        
                        
                        if(strtotime($concat_start_date) < strtotime($db_start_datetime) && strtotime($concat_end_date) >= strtotime($db_start_datetime)) {
                            $before_shift_ot = true;
                        }

                        
                        $total_hours_for_the_day = $tot_hrs_for_d_day; #$check_sched->total_hours_for_the_day;
                        $valid_end_time = date('Y-m-d H:i:s', strtotime($db_start_datetime.' +'.$total_hours_for_the_day.' hours'));
                        $valid_start_time = date('Y-m-d H:i:s', strtotime($db_start_datetime));
                    }
                } elseif ($get_work_sched_work_type->work_type_name == "Workshift") {
                    $get_blocks_list = $this->elm->get_blocks_list($check_double_timesheet_for_ot->schedule_blocks_id);
                    
                    $db_start_datetime = date('Y-m-d', strtotime($current_date)).' '.$get_blocks_list->start_time;
                    $valid_start_time = date('Y-m-d H:i:s', strtotime($db_start_datetime));
                    $db_end_datetime = date('Y-m-d', strtotime($start_date)).' '.$get_blocks_list->end_time;
                    $valid_end_time = date('Y-m-d H:i:s', strtotime($db_end_datetime));
                    
                    $time_in = $check_double_timesheet_for_ot->time_in;
                    
                    if(strtotime($time_in) >= strtotime($valid_start_time)) {
                        if($check_if_leave_for_ot) {
                            if (strtotime($db_start_datetime) <= strtotime($check_if_leave_for_ot->date_start) && strtotime($valid_start_time) >= strtotime($check_if_leave_for_ot->date_start)) {
                                $get_diff = strtotime($check_if_leave_for_ot->date_start) - strtotime($db_start_datetime);
                                $valid_start_time = date('Y-m-d H:i:s', strtotime($db_start_datetime.' +'.$get_diff.' seconds'));
                                $valid_end_time = date('Y-m-d H:i:s', strtotime($db_end_datetime.' +'.$get_diff.' seconds'));
                                $thres_hold_use = true;
                            } elseif (strtotime($check_if_leave_for_ot->date_start) <= strtotime($db_start_datetime)) {
                                $valid_start_time = $db_start_datetime;
                                $valid_end_time = $db_end_datetime;
                            } else {
                                $valid_start_time = $valid_start_time;
                                $valid_end_time = $valid_end_time;
                                $thres_hold_use = true;
                            }
                        } else {
                            $valid_start_time = $valid_start_time;
                            $valid_end_time = $valid_end_time;
                            $thres_hold_use = true;
                        }
                        
                    } elseif (strtotime($db_start_datetime) <= strtotime($time_in) && strtotime($valid_start_time) >= strtotime($time_in)) {
                        $get_diff = strtotime($time_in) - strtotime($db_start_datetime);
                        $valid_start_time = date('Y-m-d H:i:s', strtotime($db_start_datetime.' +'.$get_diff.' seconds'));
                        $valid_end_time = date('Y-m-d H:i:s', strtotime($db_end_datetime.' +'.$get_diff.' seconds'));
                    } elseif(strtotime($time_in) <= strtotime($db_start_datetime)) {
                        $valid_start_time = $db_start_datetime;
                        $valid_end_time = $db_end_datetime;
                    }
                    
                    $concat_start_date_time = date('Y-m-d H:i:s', strtotime($concat_start_date));
                    $db_start_datetime_time = date('Y-m-d H:i:s', strtotime($db_start_datetime));
                    
                    if(strtotime($concat_start_date_time) < strtotime($db_start_datetime_time)) {
                        $before_shift_ot = true;
                    }
                    
                }
            }

            // if rest day

            if ($check_rest_day) {
                if (isset($get_working_days_settings)) {
                    if ($get_working_days_settings->average_working_hours_per_day != "" || $get_working_days_settings->average_working_hours_per_day != null) {
                        $average_working_hours_per_day = $get_working_days_settings->average_working_hours_per_day;
                    }
                }
                
                $db_start_datetime = date('Y-m-d H:i:s', strtotime($check_date_and_time_in->time_in));
                $db_end_datetime   = date('Y-m-d H:i:s', strtotime($db_start_datetime.' +'.$average_working_hours_per_day.' hours'));
                $valid_start_time  = $db_start_datetime;
                $valid_end_time    = $db_end_datetime;
                
                $rd_flag = true;
            }
            
            $end_time_avail = $valid_end_time;
        }
		
		$get_company_overtime_settings = $this->employee->get_company_overtime_settings($this->company_id);
		
		if($before_shift_ot == false) {
			if($get_company_overtime_settings) {
				$overtime_minimum_min   = number_format($get_company_overtime_settings->overtime_minimum_min);
	            $enable_overtime_cap    = $get_company_overtime_settings->enable_overtime_cap;
	            $overtime_grace_min     = number_format($get_company_overtime_settings->overtime_grace_min);
	            $overtime_settings      = $get_company_overtime_settings->overtime_settings;
	            $overtime_cap_min       = $get_company_overtime_settings->overtime_cap_min;
				
				if($overtime_settings == "yes") {
					// Overtime Grace Period
					if($overtime_grace_min > 0) {
						$setting_cap_time = date('Y-m-d H:i:s', strtotime($end_time_avail.' +'.$overtime_grace_min.' minutes'));
						
						if(strtotime($concat_start_date) < strtotime($setting_cap_time)) {
							$result = array(
									'result' => 0,
									'error'=>true,
									'msg'=>"Your overtime starts ".$overtime_grace_min." minutes after your shift."
							);
							echo json_encode($result);
							return false;
						}
					}
					
					if(strtotime($concat_start_date) < strtotime($end_time_avail)) {
						$result = array(
								'result' => 0,
								'error' => true,
								'msg' => "You cannot file an overtime within your shift."
						);
						echo json_encode($result);
						return false;
					} else {
						// Overtime Minimum Requirement
						$setting_ot_min_minutes = (strtotime($concat_end_date) - strtotime($concat_start_date)) / 60;
		
						if($overtime_minimum_min > $setting_ot_min_minutes) {
							$result = array(
									'result' => 0,
									'error' => true,
									'msg'=> "You cannot file overtime less than ".$overtime_minimum_min." minutes."
							);
							echo json_encode($result);
							return false;
						}
		
						// Overtime Cap
						$setting_ot_max_minutes = (strtotime($concat_end_date) - strtotime($concat_start_date)) / 3600;
						if($enable_overtime_cap == "yes") {
							if($setting_ot_max_minutes > $overtime_cap_min) {
								$result = array(
										'result' => 0,
										'error' => true,
										'msg'=> "You cannot file overtime greater than ".$overtime_cap_min." hours on regular working days."
								);
								echo json_encode($result);
								return false;
							}
						}
					}
				}
			}
			
			if(strtotime($concat_start_date) < strtotime($end_time_avail)) {
				if($thres_hold_use == true && $rd_flag == false) {
					$result = array(
							'result' => 0,
							'error' => true,
							'msg' => "You cannot file an overtime within your shift. Your timesheet for this day uses threshold."
					);
					echo json_encode($result);
					return false;
				} elseif ($rd_flag == true) {
		                $result = array(
		                    'error' => true,
		                    'eend_date' => "You cannot file an overtime within ".$average_working_hours_per_day." hours from your time in."
		                );
		                echo json_encode($result);
		                return false;
	            } else {
					$result = array(
							'result' => 0,
							'error' => true,
							'msg' => "You cannot file an overtime within your shift."
					);
					echo json_encode($result);
					return false;
				}
				
			}
		} else {
			if(strtotime($concat_end_date) > strtotime($valid_start_time)) {
				$result = array(
						'result' => 0,
						'error' => true,
						'msg' => "You cannot file an overtime within your shift."
				);
				echo json_encode($result);
				return false;
			}
		}
		
		// $new_total_hours = (strtotime($end_date." ".$end_time) - strtotime($start_date." ".$start_time)) / 3600;
		$new_total_hours = (strtotime($concat_end_date) - strtotime($concat_start_date)) / 3600;
					
		if($resend_overtime_data_id != "" || $resend_overtime_data_id != null) {
	        $update_where = array(
	            "overtime_id" => $resend_overtime_data_id
	        );
	        
	        $this->db->where($update_where);
	        
	        $update_employee_overtime = array(
	            "emp_id"                 => $this->emp_id,
	            "update_overtime_datetime"=> date("Y-m-d H:i:s"),
	            "overtime_from"          => date("Y-m-d",strtotime($start_date)),
	            "overtime_to"            => date("Y-m-d",strtotime($end_date)),
	            "start_time"             => $start_time,
	            "end_time"               => $end_time,
	            "no_of_hours"            => number_format($new_total_hours,2),
	            "overtime_status"        => "pending",
	            "company_id"             => $this->company_id,
	            "reason"                 => $purpose,
	        );
	        
	        $insert_employee_loan = $this->db->update('employee_overtime_application', $update_employee_overtime);
	        $new_overtime_id = $resend_overtime_data_id;
	    } else {
	        $save_employee_overtime = array(
	            "emp_id"                 => $this->emp_id,
	            "overtime_date_applied"  => date("Y-m-d"),
	            "overtime_from"          => date("Y-m-d",strtotime($start_date)),
	            "overtime_to"            => date("Y-m-d",strtotime($end_date)),
	            "start_time"             => $start_time,
	            "end_time"               => $end_time,
	            "no_of_hours"            => number_format($new_total_hours,2),
	            "with_nsd_hours"         => "",
	            "company_id"             => $this->company_id,
	            "reason"                 => $purpose,
	            "notes"                  => "",
	            "flag_payroll_correction"=> $flag_payroll_correction
	        );
	        
	        $insert_employee_loan = $this->employee->insert_to_table('employee_overtime_application',$save_employee_overtime);
	        $new_overtime_id = $this->db->insert_id();
	    }


		// view last row for overtime application
		$last_row_overtime_application = $this->employee->last_row_overtime_application($this->emp_id,$this->company_id);
		$overtime_info = $this->agm->overtime_information($new_overtime_id);
		$ot_info = $this->employee->overtime_information_v2($new_overtime_id);
		
		if($void_v2 == "Closed" && $ot_info) {
		    $date_insert = array(
		        "overtime_id" => $new_overtime_id,
		        "emp_id" => $ot_info->emp_id,
		        "overtime_date_applied" => $ot_info->overtime_date_applied,
		        "overtime_from" => $ot_info->overtime_from,
		        "overtime_to" => $ot_info->overtime_to,
		        "start_time" => $ot_info->start_time,
		        "end_time" => $ot_info->end_time,
		        "no_of_hours" => $ot_info->no_of_hours,
		        "company_id" => $ot_info->company_id,
		        "reason" => $ot_info->reason,
		        "notes" => $ot_info->notes,
		        "approval_date" => $ot_info->approval_date,
		        "overtime_status" => "pending",
		        "approval_date" => $ot_info->approval_date,
		        "status" => $ot_info->status,
		        "period_from" => $ot_info->period_from,
		        "period_to" => $ot_info->period_to,
		        "flag_open_shift" => $ot_info->flag_open_shift
		    );
		    
		    $this->db->insert('overtimes_close_payroll', $date_insert);
		    $id = $this->db->insert_id();
		    
		    // update for_resend_auto_rejected_id
		    $fields = array(
		        "for_resend_auto_rejected_id" => $id,
		    );
		    
		    $where1 = array(
		        "overtime_id"=>$new_overtime_id,
		        "company_id"=>$ot_info->company_id,
		    );
		    
		    $this->db->where($where1);
		    $this->db->update("employee_overtime_application",$fields);
		}

		/*
		// save token to approval overtime
		$val = $last_row_overtime_application->overtime_id;
		
		// send email notification to approver
		$str = 'abcdefghijk123456789';
		$shuffled = str_shuffle($str);
		
		// generate token level
		$str2 = 'ABCDEFG1234567890';
		$shuffled2 = str_shuffle($str2);
		
		$overtime_info = $this->agm->overtime_information($last_row_overtime_application->overtime_id);
		$ot_info = $this->overtime->overtime_information($this->company_id, $last_row_overtime_application->overtime_id);
		$psa_id = $this->session->userdata('psa_id');
		$employee_details = get_employee_details_by_empid($this->emp_id);
		$emp_name = ucwords($employee_details->first_name." ".$employee_details->last_name);
		$fullname = ucfirst($ot_info->first_name)." ".ucfirst($ot_info->last_name);
		$get_approval_settings_disable_status = $this->employee->get_approval_settings_disable_status($this->company_id);
        
        $approver_id = $this->employee->get_approver_overtime($this->emp_id,$this->company_id)->overtime_approval_grp;
        if($approver_id == "" || $approver_id == 0) {
            // Employee with no approver will use default workflow approval
            add_workflow_approval_default_group($this->company_id,$this->account_id); // create default if dont have any
            $approver_id = get_app_default_approver($this->company_id,"Overtime")->approval_groups_via_groups_id;
        }
                        
		if($approver_id) {
			if(is_workflow_enabled($this->company_id)){
                $overtime_approver = $this->agm->get_approver_name_overtime($this->emp_id,$this->company_id);

				if($overtime_approver != FALSE){
					$workforce_notification = get_notify_settings($employee_details->overtime_approval_grp, $this->company_id);
									
					if($workforce_notification){
						$last_level = 1; //$this->todo_ot->get_overtime_last_level($overtime_info->emp_id);							
						$new_level = 1;
						$lflag = 0;
							
						// with leveling
						if($workforce_notification){
							foreach ($overtime_approver as $oa){
								$appovers_id = ($oa->emp_id) ? $oa->emp_id : "-99{$this->company_id}";
								$get_approval_group_via_groups_owner = $this->agm->get_approval_group_via_groups_owner($oa->approval_process_id, $oa->company_id, $oa->approval_groups_via_groups_id, $appovers_id);
								
								if($get_approval_group_via_groups_owner->emp_id == "-99{$this->company_id}"){
									$owner_approver = get_approver_owner_info($this->company_id);
									$appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
									$appr_account_id = $owner_approver->account_id;
									$appr_email = $owner_approver->email;
									$appr_id = "-99{$this->company_id}";
								} else {
									$appr_name = ucwords($oa->first_name." ".$oa->last_name);
									$appr_account_id = $oa->account_id;
									$appr_email = $oa->email;
									$appr_id = $oa->emp_id;
								}
								
								
								if($oa->level == $new_level){
									// send with link
									$this->send_overtime_notification($shuffled, $last_row_overtime_application->overtime_id, $this->company_id, $overtime_info->emp_id, $appr_email, $appr_name, "", "Approver", "Yes", $shuffled2, $appr_id);
									
									if($workforce_notification->sms_notification == "yes"){
										$url = base_url()."approval/overtime/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
										$sms_message = "Click {$url} to approve {$emp_name}'s overtime.";
										send_this_sms_global($this->company_id, $appr_account_id,$sms_message,$psa_id,false);
									}
									
									if($workforce_notification->message_board_notification == "yes"){
										$token = $this->overtime->get_token($val, $this->company_id, $ot_info->emp_id);
										$url = base_url()."approval/overtime/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
										$next_appr_notif_message = "An overtime application has been filed by {$fullname}.. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
										send_to_message_board($psa_id, $appr_id, $this->emp_id,$this->company_id, $next_appr_notif_message, "system", "warning");
									}
									$lflag = 1;
									
								}else{
									$this->send_overtime_notification($shuffled, $last_row_overtime_application->overtime_id, $this->company_id, $overtime_info->emp_id, $appr_email, $appr_name, "", "", "", "");
									//send email without link to notify
									if($workforce_notification->sms_notification == "yes"){
										$sms_message = "An overtime application filed by {$emp_name}.";
										send_this_sms_global($this->company_id, $appr_account_id,$sms_message,$psa_id,false);
									}
									
									if($workforce_notification->message_board_notification == "yes"){
										$next_appr_notif_message = "An overtime application has been filed by {$fullname}.";
										send_to_message_board($psa_id, $appr_id, $this->emp_id,$this->company_id, $next_appr_notif_message, "system", "warning");
									}
								}
							}
							
							################################ notify payroll admin start ################################
							if($workforce_notification->notify_payroll_admin == "yes"){
							    // HRs
							    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($psa_id);
							    if($payroll_admin_hr){
							        foreach ($payroll_admin_hr as $pahr){
							            $pahr_email = $pahr->email;
							            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);
							            
							            $this->send_overtime_notification($shuffled, $last_row_overtime_application->overtime_id, $this->company_id, $overtime_info->emp_id, $pahr_email, $pahr_name, "", "", "", "", "");
							            
							        }
							    }
							    
							    // Owner
							    $pa_owner = get_approver_owner_info($this->company_id);
							    if($pa_owner){
							        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
							        $pa_owner_email = $pa_owner->email;
							        $pa_owner_account_id = $pa_owner->account_id;
							        
							        $this->send_overtime_notification($shuffled, $last_row_overtime_application->overtime_id, $this->company_id, $overtime_info->emp_id, $pa_owner_name, $pa_owner_email, "", "", "", "", "");
							    }
							}
							################################ notify payroll admin end ################################
						}
						
					}
					
					$save_token = array(
        					    "overtime_id"            => $last_row_overtime_application->overtime_id,
        					    "token"                  => $shuffled,
        					    "comp_id"                => $this->company_id,
        					    "emp_id"                 => $this->emp_id,
        					    "approver_id"            => $this->employee->get_approver_overtime($this->emp_id,$this->company_id)->overtime_approval_grp,
        					    "level"                  => $new_level,
        					    "token_level"            => $shuffled2,
        					    "date_approved_level"    => date('Y-m-d H:i:s'),
        					    "date_reminder_level"    => date('Y-m-d H:i:s')
					);
						
					$save_token_q = $this->db->insert("approval_overtime",$save_token);
					if($insert_employee_loan){
						$result = array(
								'error'		=> false,
								'result' 	=> false,
								'msg'		=> 'Your overtime application has been submitted'
						);
						echo json_encode($result);
						return false;
					}
				}else{
					$new_level = 1;
					
					$save_token = array(
        					    "overtime_id"            => $last_row_overtime_application->overtime_id,
        					    "token"                  => $shuffled,
        					    "comp_id"                => $this->company_id,
        					    "emp_id"                 => $this->emp_id,
        					    "approver_id"            => $this->employee->get_approver_overtime($this->emp_id,$this->company_id)->overtime_approval_grp,
        					    "level"                  => 1,
        					    "token_level"            => $shuffled2,
        					    "date_approved_level"    => date('Y-m-d H:i:s'),
        					    "date_reminder_level"    => date('Y-m-d H:i:s')
					);
							
					$save_token_q = $this->db->insert("approval_overtime",$save_token);
					$result = array(
							'error'		=> false,
							'result' 	=> false,
							'msg'		=> 'Your overtime application has been submitted'
					);
					echo json_encode($result);
					return false;
				}
			}else{

				if($get_approval_settings_disable_status->status == "Inactive") {
						
					if($get_approval_settings_disable_status->disabled_application_status == 'approve') {
						$status = "approved";
					} else {
						$status = $get_approval_settings_disable_status->disabled_application_status;
					}
						
					$fields = array(
							"overtime_status" => $status,
							"approval_date"	=> date("Y-m-d H:i:s")
					);
					$where = array(
							"overtime_id" => $val,
							"company_id" => $this->company_id
					);
					$this->overtime->update_field("employee_overtime_application",$fields,$where);
						
					$result = array(
							'error'				=> false,
							'result' 			=> false,
							'msg'				=> 'Your overtime application has been submitted.'
					);
					echo json_encode($result);
					return false;
				}
			}
		}
		*/

		/** NEW WORKFLOW **/
        // send email notification to approver
        $overtime_approver = get_workflow_approvers("Overtime",$this->company_id,$this->emp_id);
        #p( $overtime_approver);

		// save token to approval overtime
		$val = $new_overtime_id;
		
		// send email notification to approver
		$str = 'abcdefghijk123456789';
		$shuffled = str_shuffle($str);
		
		// generate token level
		$str2 = 'ABCDEFG1234567890';
		$shuffled2 = str_shuffle($str2);
		
		#$ot_info = $this->overtime->overtime_information($this->company_id, $last_row_overtime_application->overtime_id);
		$psa_id = $this->session->userdata('psa_id');
		
		$fullname = ucfirst($ot_info->first_name)." ".ucfirst($ot_info->last_name);

		if($overtime_approver){
			// with leveling
			foreach ($overtime_approver as $la){
				$approver_info = employee_info_by_accnt_id($la->assigned_account_id,$this->company_id);
				#p($approver_info);
                $appr_name = ucwords($approver_info->first_name." ".$approver_info->last_name);
                $appr_account_id = $approver_info->account_id;
                $appr_email = $approver_info->email;
                if(array_key_exists('emp_id', $approver_info)) {
                    $appr_id = $approver_info->emp_id;
                } else {
                	$appr_id = "-99{$this->company_id}";
                }

				if($la->level == 1){
                    ###check email settings if enabled###
					/*if($this->company_id == "1"){
						if($la->ns_overtime_email_flag == "yes"){
							// send with link
						    $this->send_overtime_notification($shuffled, $new_overtime_id, $this->company_id, $overtime_info->emp_id, $appr_email, $appr_name, "", "Approver", "Yes", $shuffled2, $appr_id);
						}
					}else{*/
					    $this->send_overtime_notification($shuffled, $new_overtime_id, $this->company_id, $overtime_info->emp_id, $appr_email, $appr_name, "", "Approver", "Yes", $shuffled2, $appr_id);
					#}
					###end checking email settings if enabled###

					$save_token = array(
					    "overtime_id"            => $new_overtime_id,
						"token"                  => $shuffled,
						"comp_id"                => $this->company_id,
						"emp_id"                 => $this->emp_id,
				        "approver_id"            => $la->parent_workflow_breakdown_id,
						"level"                  => 1,
						"token_level"            => $shuffled2,
						"date_approved_level"    => date('Y-m-d H:i:s'),
						"date_reminder_level"    => date('Y-m-d H:i:s'),
                        "new_workflow_flag"      => "1"
					);
					
					$flag_if_insert_suceess = $this->db->insert("approval_overtime",$save_token);

					if(!$flag_if_insert_suceess) {
				    	$this->db->insert("approval_overtime",$save_token);
				    }

				    $result = array(
							'error'=>false,
							'approver_error' => ""
					);
					echo json_encode($result);
					return false;

				} else {
					$this->send_overtime_notification($shuffled, $new_overtime_id, $this->company_id, $overtime_info->emp_id, $appr_email, $appr_name, "", "", "", "");
				}
			}
		}else{
			/*$new_level = 1;
			
			$save_token = array(
			    "overtime_id"            => $new_overtime_id,
				"token"                  => $shuffled,
				"comp_id"                => $this->company_id,
				"emp_id"                 => $this->emp_id,
		        "approver_id"            => $approver_id,
				"level"                  => 1,
				"token_level"            => $shuffled2,
				"date_approved_level"    => date('Y-m-d H:i:s'),
				"date_reminder_level"    => date('Y-m-d H:i:s')
			);
				
			$flag_if_insert_suceess = $this->db->insert("approval_overtime",$save_token);

			if(!$flag_if_insert_suceess) {
		    	$this->db->insert("approval_overtime",$save_token);
		    }


			$approver_error = "";
			$result = array(
					'error'=>false,
					'approver_error' => $approver_error
			);
			echo json_encode($result);
			return false;*/
		}

		$result = array(
				'error'				=> false,
				'result' 			=> false,
				'msg'				=> 'Overtime application submitted.'
		);
		echo json_encode($result);
		return false;
	}
	
	public function if_payroll_is_locked() {
	    // check if payroll is locked or closed
	    $start_date = $this->input->post('start_date');
	    $locked = "";
	    
	    if($start_date) {
	        // $void = $this->employee->edit_delete_void($this->emp_id,$this->company_id,date("Y-m-d", strtotime($start_date)));
            $void = $this->employee_v2->check_payroll_lock_closed($this->emp_id,$this->company_id,date("Y-m-d", strtotime($start_date)));
            
	        if($void == "Waiting for approval"){
	            $locked = "Overtime locked for payroll processing.";
	        } elseif ($void == "Closed") {
	            $locked = "The overtime you are submitting is part of a closed payroll. Your request will be routed to the appropriate approvers for consideration and approval.";
	        }
	        
	        if($locked != "") {
	            $result = array(
	                'result' => 1,
	                'error' => true,
	                'err_msg' => $locked
	            );
	            
	            echo json_encode($result);
	            return false;
	        }
	    }
	}
	
	
	public function get_work_sched_work_type($work_schedule_id,$comp_id) {
		$where = array(
				'work_schedule_id' => $work_schedule_id,
				'status' => 'Active',
				'comp_id' => $comp_id
		);
	
		$this->db->where($where);
		$q = $this->db->get('work_schedule');
		$r = $q->row();
	
		return ($r) ? $r : FALSE;
	
	
	}
		
	public function calculate_overtime(){

		// display total hours applied
		$start_date = $this->input->post('start_date');
		$end_date = $this->input->post('end_date');
		
		$ot_start = date('Y-m-d H:i:s', strtotime($start_date));
		$ot_end = date('Y-m-d H:i:s', strtotime($end_date));

		$start_time = date("H:i:s",strtotime($ot_start));
		$end_time = date("H:i:s",strtotime($ot_end));
		
		$new_total_hours = (strtotime($ot_end) - strtotime($ot_start)) / 3600;
		
		echo json_encode(array("total_hours"=>($new_total_hours < 0) ? "0.00" : number_format($new_total_hours,2)));
		return false;
	}
	
	public function get_approvers_name_and_status() {
		$overtime_id = $this->input->post('overtime_id');
		$overtime_status = $this->input->post('overtime_status');
		
		$overtime_info = $this->agm->overtime_information($overtime_id);
		$overtime_approver = get_approvers_name_and_status($this->company_id, $this->emp_id, $overtime_id, "overtime"); //$this->agm->get_approver_name_leave($overtime_info->emp_id,$overtime_info->company_id);
		$workflow_approvers = workflow_approved_by_level($overtime_id, 'overtime');
		$x = count($workflow_approvers);
		$res = array();
		
		if($overtime_approver) {
			foreach ($overtime_approver as $la) {
			    if($la->emp_id == "-99{$this->company_id}"){
			        $owner_approver = get_approver_owner_info($this->company_id);
			        $appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
			    } else {
			        $appr_name = ucwords($la->first_name." ".$la->last_name);
			    }
			    
				$last_level = $this->overtime->get_overtime_last_level($this->emp_id, $this->company_id);
				
				if($overtime_status == "reject") {
					if($workflow_approvers){
						if($x > $la->level) {
						    $name = $appr_name.' - (Approved)';
						} elseif ($x == $last_level) {
						    $name = $appr_name.' - (Rejected)';
						} elseif($x < $la->level) {
						    $name = $appr_name.' - (Rejected)';
						} else {
						    $name = $appr_name.' - (Rejected)';
						}
					}
				} else {
					if($workflow_approvers) {
						foreach ($workflow_approvers as $wa) {
							if($wa->workflow_level == $la->level) {
							    $name = $appr_name.' - (Approved)';
							} else if($overtime_status == "pending") {
							    $name = $appr_name.' - ('.$overtime_status.')';
							} else {
								$name = "";
							}
						}
					} else {
						if($overtime_status == "pending") {
						    $name = $appr_name.' - ('.$overtime_status.')';
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
	}
		
	/**
	 * Send notification to Group Approver LEVEL 1
	 */
		
	public function send_overtime_notification($token = NULL, $overtime_id = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "" , $withlink = "No", $level_token = "", $appr_id = ""){
		$ot_info = $this->overtime->overtime_information($this->company_id, $overtime_id);
		$employee_fullname = $this->agm->get_employee_fullname($emp_id,$comp_id);
		
		
		$fullname = ucfirst($ot_info->first_name)." ".ucfirst($ot_info->last_name);
		$date_applied = date("F d, Y",strtotime($ot_info->overtime_date_applied));
		$start_time = date("F d, Y | h:i A",strtotime($ot_info->overtime_from.' '.$ot_info->start_time));
		$end_time = date("F d, Y | h:i A",strtotime($ot_info->overtime_to.' '.$ot_info->end_time));
		$total_hours = $ot_info->no_of_hours;
		$reason = $ot_info->reason;
		
		$link = '<a style="color:#1172ad; text-decoration:underline; font-size:16px;" href="'.base_url().'approval/overtime/index/'.$token.'/'.$level_token.'/1'.$appr_id.'0">View Overtime Application</a>';
		if($who == "Approver"){
			if($withlink == "No"){
				$link = '';
			}
		}else{
			$link = "";
		}
		$config['protocol'] = 'sendmail';
		$config['wordwrap'] = TRUE;
		$config['mailtype'] = 'html';
		$config['charset'] = 'utf-8';
	
		$this->load->library('email',$config);
		$this->email->initialize($config);
		$this->email->set_newline("\r\n");
		$this->email->from(notifications_ashima_email(),'Ashima');
		$this->email->to($email);
		$this->email->subject('Overtime Application - '.$fullname);
		$font_name = "'Open Sans'";
		
	
		
		$this->email->message('
		<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
		<html lang="en">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
				<meta name="format-detection" content="telephone=no">
				<title>Overtime Application</title>
				<style type="text/css">
					.ReadMsgBody {width: 100%; background-color: #ebebeb;}
					.ExternalClass {width: 100%; background-color: #ebebeb;}
					.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height:100%;}
					body {-webkit-text-size-adjust:none;-ms-text-size-adjust:none;font-family:".$font_name.", Arial, Helvetica, sans-serif;}
					body {margin:0;padding:0;}
					table {border-spacing:0;}
					table td {border-collapse:collapse;}
					.yshortcuts a {border-bottom: none !important;}
				</style>
			</head>
			<body>
				<table style="width:100%" width="100%" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td style="padding:30px 0 50px;" valign="top" align="center">
							<table style="width:640px; margin:0 auto;" align="center" width="640" border="0" cellspacing="0" cellpadding="0">
								<tr>
							        <td style="border-bottom:6px solid #ccc; padding-bottom:25px;" valign="top"><img src="'.(newsletter_logo($comp_id)).'" height="62" alt=" "></td>
							    </tr>
								<tr>
									<td valign="top" align="center">
										<table width="580px" style="width:580px; margin:0;" align="center" border="0" cellspacing="0" cellpadding="0">
											<tr>
												<td valign="top" style="padding:25px 0 20px; border-bottom:1px solid #ccc">
													<table style="width:100%;" width="100%" border="0" cellspacing="0" cellpadding="0">
														<tr>
															<td valign="top">
																<h2 style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0 0 10px;">Hi '.$approver_full_name.',</h2>
																<p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">New Overtime application has been filed by '.$fullname.'. Details below:</p>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>
												<td valign="top" style="padding-top:25px;">
													<table width="100%" border="0" cellspacing="0" cellpadding="0">
															<tr>
																<td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date_applied.'</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Purpose of Overtime:</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$reason.'</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Start Date:</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$start_time.'</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">End Date</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$end_time.'</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Total Overtime Filed:</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$total_hours.' Hour(s)</td>
															</tr>
															<tr>
																<td>&nbsp;</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">
																	'.$link.'
																</td>
															</tr>
													</table>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td valign="top" align="center" style="background-color:#f2f2f2; padding:30px 0;">
							<table width="640" style="width:640px;" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; 2015 Konsum Technologies. All Rights Reserved.</td>
									<td valign="top"><img src="'.base_url().'assets/theme_2015/images/images-emailer/icon-newsletter-logo-footer.png" width="145" height="92" alt=" "></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</body>
		</html>
		');
		if($this->email->send()){
			return true;
		}else{
			return false;
		}
	}
}