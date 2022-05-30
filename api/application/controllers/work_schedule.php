<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Work_schedule extends CI_Controller{
	var $verify;
		
	public function __construct(){
	   parent::__construct();	
	   $this->load->model('konsumglobal_jmodel','jmodel');
	   $this->load->model('employee_model','employee');
	   $this->load->model('approval_model','approval');
	   $this->load->model('approval_group_model','agm');
	   $this->load->model('employee_notifications_model','noti');
	   $this->load->model('emp_social_media_model','social');
	   $this->load->model('emp_login_model','elm');
	   $this->load->library('twitteroauth');
	   $this->load->model("approve_shift_model","shifts");
	   $this->load->model("import_timesheets_model","import");
       $this->load->model('login_model','emp_login');
       $this->load->model('employee_v2_model','employee_v2');
	  // $this->company_info = whose_company();
	  	
	   $this->emp_id = $this->session->userdata('emp_id');
	   $this->company_id =$this->employee->check_company_id($this->emp_id);  
	   $this->load->model('employee_work_schedule_model','ews');
	   $this->account_id = $this->session->userdata('account_id');
	   
	}
	public function index(){
		$shift_date = $this->input->post('date');
		$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$shift_date);
		
		$split = $this->elm->check_workday($work_schedule_id,$this->company_id);
		$check_holiday = $this->employee->get_holiday_schedule($shift_date,$this->emp_id,$this->company_id);
		#p($split);
		$server_date = date('Y-m-d');
		if($split){
			if($split->work_type_name == "Workshift"){
				#echo "Split Shift";
				echo json_encode(array("server_date" => $shift_date, "abb" => "Split Shift", "schedule" => false, "holiday" => false));
			} else {
				$workday = date('l',strtotime($shift_date));
				$restday = false;
				 
				if($work_schedule_id == 0 || $work_schedule_id == ""){
					$wsi = $this->import->emp_work_schedule($this->emp_id,$this->company_id,$shift_date);
				}else{
					$wsi = $work_schedule_id;
				}
				 
				$restday = $this->elm->check_rest_day($workday,$wsi,$this->company_id);
				
				if($work_schedule_id == -1 || $restday){
					#echo "Rest Day";
					echo json_encode(array("server_date" => $shift_date, "abb" => "Rest Day", "schedule" => false, "holiday" => false));
				}else{
		
					$work_schedule_custom = NULL;
					$shift_name = NULL;
					$abrv = null;
					$employee_shift_schedule = $this->employee->assigned_work_schedule($this->company_id,$this->emp_id,$shift_date);
					/* CHECK BACKGROUND COLOR */
					if($employee_shift_schedule) {
						$shift_name = $employee_shift_schedule->name;
						$work_schedule_custom = $employee_shift_schedule->category_id;
					}else{
						/* DEFAULT WORK SCHEDULE VIA PAYROLL GROUP */
						 
						$w = array(
								'epi.emp_id'=> $this->emp_id
						);
						$this->db->where($w);
						$q_pg = $this->edb->get('employee_payroll_information AS epi');
						$r_pg = $q_pg->row();
		
						$payroll_group_id = ($r_pg) ? $r_pg->payroll_group_id : FALSE;
						if($payroll_group_id){
							$get_work_schedule_default = $this->employee->assigned_work_schedule_via_payroll_group($payroll_group_id,$this->company_id);
							 
							 
							#$employee_shift_schedule = $this->shifts->assigned_work_schedule_default($payroll_group_id,$this->company_id,$emp_id,$date_counter);
							 
							if($get_work_schedule_default) {
							 
								$shift_name = $get_work_schedule_default->name;
								$work_schedule_custom = $get_work_schedule_default->work_schedule_id;
							}
								 
						}
					}
							 
					$sub_work_name = "";
					if($work_schedule_custom != 0) {
					 
						$cwr = array(
							"work_schedule_id" => $work_schedule_custom,
							"comp_id" => $this->company_id
						);
						$cust_workname = get_table_info("work_schedule",$cwr);
						
						$sub_work_name = " - ".$shift_name;
						$shift_name = ($cust_workname) ? $cust_workname->name : '';
					}
				 
					if($shift_name == "Regular Work Schedule") {
						$abrv = "RW".$sub_work_name;
					}
					if($shift_name == "Compressed Work Schedule") {
							$abrv = "CW".$sub_work_name;
					}
							if($shift_name == "Night Shift Schedule") {
							$abrv = "NS".$sub_work_name;
					}
					if($shift_name == "Flexi Time Schedule") {
							$abrv = "FT".$sub_work_name;
					}
					
					$date = $shift_date ;
					$current_monday = date("Y-m-d",strtotime($date." monday this week"));
					$time_name = "~";
					$shift="";
		
					if($check_holiday) {
						$this_holiday = array(
								"holiday_name" => $check_holiday->holiday_name,
								"hour_type_name" => $check_holiday->hour_type_name
						);
						echo json_encode(array("server_date" => $shift_date, "abb" => false, "schedule" => false, "holiday" => $this_holiday));
					} else {
						 
						$sure = false;
						if($work_schedule_id == 0 || $work_schedule_id == ""){
							$work_schedule_idx = $this->import->emp_work_schedule($this->emp_id,$this->company_id,$shift_date);
						}else{
							$work_schedule_idx = $work_schedule_id;
						}
					 
						$no_schedule = $this->elm->get_workschedule_info_for_no_workschedule($this->emp_id,$this->company_id,$shift_date,$work_schedule_idx);
					 
						if($shift == "" || $no_schedule ){
							echo json_encode(array("server_date" => $shift_date, "abb" => $abrv, "schedule" => $no_schedule, "holiday" => false));
						}
					}
		
				 
				} #end first if
			}
		} else {
			$workday = date('l',strtotime($shift_date));
			$restday = false;
		
			if($work_schedule_id == 0 || $work_schedule_id == ""){
				$wsi = $this->import->emp_work_schedule($this->emp_id,$this->company_id,$shift_date);
			}else{
				$wsi = $work_schedule_id;
			}
		
			$restday = $this->elm->check_rest_day($workday,$wsi,$this->company_id);
		
			if($check_holiday) {
				echo $check_holiday->holiday_name.'<br>';
				echo $check_holiday->hour_type_name;
			} else {
				if($work_schedule_id == -1 || $restday){
					#echo "Rest Day";
					echo json_encode(array("server_date" => $shift_date, "abb" => "Rest Day", "schedule" => false, "holiday" => false));
				} else {
					#echo "Schedule not Available";
					echo json_encode(array("server_date" => $shift_date, "abb" => "Schedule not Available", "schedule" => false, "holiday" => false));
				}
			}
		
		}
	}
	
	public function next_shift(){
		$shift_date1 = $this->input->post('date');
		$shift_date = date("Y-m-d", strtotime($shift_date1." +1 day"));
		$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$shift_date);
		
		$split = $this->elm->check_workday($work_schedule_id,$this->company_id);
		$check_holiday = $this->employee->get_holiday_schedule($shift_date,$this->emp_id,$this->company_id);
        
		$server_date = date('Y-m-d');
		if($split){
			if($split->work_type_name == "Workshift"){
				#echo "Split Shift";
				echo json_encode(array("server_date" => $shift_date, "abb" => "Split Shift", "schedule" => false, "holiday" => false));
			} else {
				$workday = date('l',strtotime($shift_date));
				$restday = false;
				 
				if($work_schedule_id == 0 || $work_schedule_id == ""){
					$wsi = $this->import->emp_work_schedule($this->emp_id,$this->company_id,$shift_date);
				}else{
					$wsi = $work_schedule_id;
				}
				 
				$restday = $this->elm->check_rest_day($workday,$wsi,$this->company_id);
				
				if($work_schedule_id == -1 || $restday){
					#echo "Rest Day";
					echo json_encode(array("server_date" => $shift_date, "abb" => "Rest Day", "schedule" => false, "holiday" => false));
				}else{
		
					$work_schedule_custom = NULL;
					$shift_name = NULL;
					$abrv = null;
					$employee_shift_schedule = $this->employee->assigned_work_schedule($this->company_id,$this->emp_id,$shift_date);
					/* CHECK BACKGROUND COLOR */
					if($employee_shift_schedule) {
						$shift_name = $employee_shift_schedule->name;
						$work_schedule_custom = $employee_shift_schedule->category_id;
					}else{
						/* DEFAULT WORK SCHEDULE VIA PAYROLL GROUP */
						 
						$w = array(
								'epi.emp_id'=> $this->emp_id
						);
						$this->db->where($w);
						$q_pg = $this->edb->get('employee_payroll_information AS epi');
						$r_pg = $q_pg->row();
		
						$payroll_group_id = ($r_pg) ? $r_pg->payroll_group_id : FALSE;
						if($payroll_group_id){
							$get_work_schedule_default = $this->employee->assigned_work_schedule_via_payroll_group($payroll_group_id,$this->company_id);
							 
							 
							#$employee_shift_schedule = $this->shifts->assigned_work_schedule_default($payroll_group_id,$this->company_id,$emp_id,$date_counter);
							 
							if($get_work_schedule_default) {
							 
								$shift_name = $get_work_schedule_default->name;
								$work_schedule_custom = $get_work_schedule_default->work_schedule_id;
							}
								 
						}
					}
							 
					$sub_work_name = "";
					if($work_schedule_custom != 0) {
					 
						$cwr = array(
							"work_schedule_id" => $work_schedule_custom,
							"comp_id" => $this->company_id
						);
						$cust_workname = get_table_info("work_schedule",$cwr);
						
						$sub_work_name = " - ".$shift_name;
						$shift_name = ($cust_workname) ? $cust_workname->name : '';
					}
				 
					if($shift_name == "Regular Work Schedule") {
						$abrv = "RW".$sub_work_name;
					}
					if($shift_name == "Compressed Work Schedule") {
							$abrv = "CW".$sub_work_name;
					}
							if($shift_name == "Night Shift Schedule") {
							$abrv = "NS".$sub_work_name;
					}
					if($shift_name == "Flexi Time Schedule") {
							$abrv = "FT".$sub_work_name;
					}
					
					$date = $shift_date ;
					$current_monday = date("Y-m-d",strtotime($date." monday this week"));
					$time_name = "~";
					$shift="";
		
					if($check_holiday) {
						$this_holiday = array(
								"holiday_name" => $check_holiday->holiday_name,
								"hour_type_name" => $check_holiday->hour_type_name
						);
						echo json_encode(array("server_date" => $shift_date, "abb" => false, "schedule" => false, "holiday" => $this_holiday));
					} else {
						 
						$sure = false;
						if($work_schedule_id == 0 || $work_schedule_id == ""){
							$work_schedule_idx = $this->import->emp_work_schedule($this->emp_id,$this->company_id,$shift_date);
						}else{
							$work_schedule_idx = $work_schedule_id;
						}
					 
						$no_schedule = $this->elm->get_workschedule_info_for_no_workschedule($this->emp_id,$this->company_id,$shift_date,$work_schedule_idx);
					 
						if($shift == "" || $no_schedule ){
							echo json_encode(array("server_date" => $shift_date, "abb" => $abrv, "schedule" => $no_schedule, "holiday" => false));
						}
					}
		
				 
				} #end first if
			}
		} else {
			$workday = date('l',strtotime($shift_date));
			$restday = false;
		
			if($work_schedule_id == 0 || $work_schedule_id == ""){
				$wsi = $this->import->emp_work_schedule($this->emp_id,$this->company_id,$shift_date);
			}else{
				$wsi = $work_schedule_id;
			}
		
			$restday = $this->elm->check_rest_day($workday,$wsi,$this->company_id);
		
			if($check_holiday) {
				echo $check_holiday->holiday_name.'<br>';
				echo $check_holiday->hour_type_name;
			} else {
				if($work_schedule_id == -1 || $restday){
					#echo "Rest Day";
					echo json_encode(array("server_date" => $shift_date, "abb" => "Rest Day", "schedule" => false, "holiday" => false));
				} else {
					#echo "Schedule not Available";
					echo json_encode(array("server_date" => $shift_date, "abb" => "Schedule not Available", "schedule" => false, "holiday" => false));
				}
			}
		
		}
    }
    
    public function get_work_schedule() {
        $all_shifts_except_split = $this->ews->all_shifts_except_split2($this->company_id);
        
        if ($all_shifts_except_split) {
            echo json_encode(array(
                "result" => true,
                "schedules" => $all_shifts_except_split
            ));
            return false;
        } else {
            echo json_encode(array(
                "result" => false,
                "schedules" => ""
            ));
            return false;
        }

        return false;
        if($all_shifts_except_split) {
            if ($all_shifts_except_split['Regular Work Schedule']) {
                foreach ($all_shifts_except_split['Regular Work Schedule'] as $key=>$ases) {
                    $html1 .= "
                        <option value='{$ases['work_schedule_id']}'>{$ases['name']}</option>";
                }
            }
            
            if ($all_shifts_except_split['Compressed Work Schedule']) {
                foreach ($all_shifts_except_split['Compressed Work Schedule'] as $key=>$ases) {
                    $html1 .= "
                        <option value='{$ases['work_schedule_id']}'>{$ases['name']}</option>";
                }
                }

                if ($all_shifts_except_split['Night Shift Schedule']) {
                    foreach ($all_shifts_except_split['Night Shift Schedule'] as $key=>$ases) {
                        $html1 .= "
                        <option value='{$ases['work_schedule_id']}'>{$ases['name']}</option>";
                    }
                }
                
                if ($all_shifts_except_split['Flexi Time Schedule']) {
                    foreach ($all_shifts_except_split['Flexi Time Schedule'] as $key=>$ases) {
                        $html1 .= "
                        <option value='{$ases['work_schedule_id']}'>{$ases['name']}</option>";
                    }
                }
                
            $html .= "<tr class='remove_me_ajax'>
                        <td>
                            <label class='margin-top-9'>Work Schedule</label>
                        </td>
                        <td colspan='5'>
                            <div class='select-bungot'>
                                <select class='select-custom leave_type_cont' name='work_schedule_id' id='work_schedule_id'>
                                    {$html1}
                                </select>
                            </div>
                            <span class='form-error' id='eleave_type'></span>
                        </td>
                    </tr>
                    ";
                    
            echo json_encode(array(
                "result" => true,
                "html" => $html
            ));
            return false;
        } else {
            echo json_encode(array(
                    "result" => false,
                    "html" => ""
            ));
            return false;
        }
    }
	
	public function get_work_schedule_old(){
		$date = $this->input->post('date_from');
		$temp1 = $this->ews->get_work_schedule_cat($this->emp_id,$this->company_id,date('Y-m-d', strtotime($date)));
		$check_employee_work_schedule = $this->ews->check_employee_work_schedule(date('Y-m-d', strtotime($date)),$this->emp_id,$this->company_id);
		
		 $check_employee_leave_application = $this->ews->check_employee_leave_application(date('Y-m-d', strtotime($date)),$this->emp_id);
		 
		 if($check_employee_leave_application != FALSE){
		 	echo json_encode(array(
		 			"error" => true,
		 			"msg" => "You are on leave on these days."
		 	));
		 	return false;
		 } else {
		 	
		 	if($temp1){
		 		
		 		if($temp1->category_id != null) {
		 			$cat_id = $temp1->category_id ;
		 		} else {
		 			$cat_id = $temp1->work_schedule_id ;
		 		}
		 	} else {
		 		$cat_id = $check_employee_work_schedule->work_schedule_id;
		 		$temp2 = $this->ews->get_work_schedule_by_id($this->company_id,$cat_id);
		 			
		 		if($temp2){
		 			if($temp2->category_id != null) {
		 				$cat_id = $temp2->category_id ;
		 			} else {
		 				$cat_id = $temp2->work_schedule_id ;
		 			}
		 		}
		 	}
		 			 	
		 	$get_cat = $this->ews->get_work_schedule_all_cat($this->company_id,$cat_id);
		 	$main_array = array();
		 	if($get_cat) {
		 		
		 		foreach ($get_cat as $key=>$gc) {
		 			if($gc) {
		 				$res = array(
		 					"work_schedule_id" => $gc->work_schedule_id,
		 					"name" => $gc->name
		 				);

		 				array_push($main_array,$res);
		 			}
		 			
		 		}
		 		
	 			echo json_encode(array(
		 			"result" => 1,
		 			"data" => $main_array
	 			));
	 			return false;
		 	} else {
			 	echo json_encode(array(
		 			"result" => false,
		 			"work_schedule_id" => ""
			 	));
		 		return false;
		 	}
		 }
		
	}
	
	public function get_schedule_time(){
		$work_schedule_id = $this->input->post('work_schedule_id');
		$date = $this->input->post('date_from');
		if($work_schedule_id){
            // $temp3 = $this->ews->get_work_schedule_time($work_schedule_id,$this->company_id,date('l', strtotime($date)));
            $temp3 = $this->ews->work_schedule_info($this->company_id,$work_schedule_id,date('l', strtotime($date)));
            
            if ($temp3) {
                $return = array(
                    'start_time' => date('h:i A', strtotime($temp3['work_schedule']['start_time'])),
                    'end_time' => date('h:i A', strtotime($temp3['work_schedule']['end_time'])),
                    'error' => false
                );
            } else {
                $return = array(
                    'start_time' => "",
					'end_time' => "",
					'error' => false
                );
            }
			
			echo json_encode($return);
			return false;
		} else {
			
			$return = array(
					'start_time' => "",
					'end_time' => "",
					'error' => true
			);
			echo json_encode($return);
			return false;
		}
		
		
	}
	
	public function get_schedule_from(){
		if($this->input->post("get_schedule")){
			$date = $this->input->post('date_from');
			$temp = get_schedule($date, $this->emp_id, $this->company_id);
			
			$check_employee_leave_application = $this->ews->check_employee_leave_application(date('Y-m-d', strtotime($date)),$this->emp_id);
				
			if($check_employee_leave_application != FALSE){
				echo json_encode(array(
						"error" => true,
						"msg" => "You are on leave on these days."
				));
				return false;
			} else {
				$return = array(
						'start_time' => date('h:i A', strtotime($temp['start_time'])),
						'end_time' => date('h:i A', strtotime($temp['end_time'])),
						'error' => false
				);
				echo json_encode($return);
				return false;
			}
		}
	}
	
	public function get_year(){
		$array = array(
				'year_now'=> date('Y')
		);
		echo json_encode($array);
	}
	
	public function save_schedule()
	{
        
		if($this->input->post("schedule_change")){
            // check if the application is lock for filing
            $get_lock_payroll_process_settings = get_lock_payroll_process_settings($this->company_id,"shift request");
            if($get_lock_payroll_process_settings) {
                if($get_lock_payroll_process_settings->suspend_all_application == "yes") {
                    $result = array(
                        'error'=>true,
                        'msg'=>$get_lock_payroll_process_settings->application_error,
                    );
                    echo json_encode($result);
                    return false;
                } elseif ($get_lock_payroll_process_settings->ts_recalc == 1) {
                    $result = array(
                        'error'=>true,
                        'msg'=>$get_lock_payroll_process_settings->ts_app_recalculation_err_msg,
                    );
                    echo json_encode($result);
                    return false;
                } elseif ($get_lock_payroll_process_settings->py_recalc == 1) {
                    $result = array(
                        'error'=>true,
                        'msg'=>$get_lock_payroll_process_settings->py_app_recalculation_err_msg,
                    );
                    echo json_encode($result);
                    return false;
                }
            }

			$date_from = $this->input->post("date_from");
			$date_to = $this->input->post("date_to");
			$time_from = $this->input->post("time_from");
            $time_to = $this->input->post("time_to");

            $schedule_from = date("H:i:s",strtotime("{$time_from}"));
            $work_sched_id = $this->input->post("work_schedule_id");
				
            $schedule_to = date("H:i:s",strtotime("{$time_to}"));
            $reason = $this->input->post("reason");
            $continue_submit_ifhas_leaves = $this->input->post("leaves_status");

            /* CHECK EMPLOYEE LOGS */
            $check_emp_time_log = check_employee_timein_logs($this->company_id,$this->emp_id);
            /* END CHECK EMPLOYEE ...  */
            
            /* CHECK LEAVES */
            
            $leaves_array_param = array(
                "company_id" => $this->company_id,
                "date_from" => date("Y-m-d", strtotime($date_from)),
                "date_to" => date("Y-m-d", strtotime($date_to)),
                "emp_id" => $this->emp_id,
                "source" => "request_schedule"
            );
            
            $leave_status = check_employee_leaves_status($leaves_array_param);
            
            $has_leave = false;
            $message_leaves = "";
            
            if($continue_submit_ifhas_leaves == 0 && count($leave_status) > 0) {
                    $has_leave = true;
                    $message_leaves = $leave_status["message"];
            }

            // validation
            if (!trim($date_from)) {
                $result = array(
                    'error'=>true,
                    'msg'=>"Change Date From is required."
                );
                echo json_encode($result);
                return false;
            }
            if (!trim($date_to)) {
                $result = array(
                    'error'=>true,
                    'msg'=>"Change Date To is required."
                );
                echo json_encode($result);
                return false;
            }
            if (!trim($work_sched_id)) {
                $result = array(
                    'error'=>true,
                    'msg'=>"Schedule is required."
                );
                echo json_encode($result);
                return false;
            }
            if (!trim($reason)) {
                $result = array(
                    'error'=>true,
                    'msg'=>"Reason for changing schedule is required."
                );
                echo json_encode($result);
                return false;
            }

			
			// if one of the approver is inactive the approver group will automatically change to default (owner)
            change_approver_to_default($this->emp_id,$this->company_id,"shedule_request_approval_grp",$this->account_id);
            $schedule_request_settings = schedule_request_settings($this->company_id);
            $check_payroll_lock_closed = $this->employee_v2->check_payroll_lock_closed($this->emp_id,$this->company_id,date("Y-m-d",strtotime($date_from)));
            
            /* SET ARRAY TO CHECK EMPLOYEES LOG */
            $new_date = date("Y-m-d", strtotime($date_from));
            $cant_proceed_emp = false;
            $arr_check_emp_time_log = in_array_custom("{$this->emp_id}-{$new_date}", $check_emp_time_log);
            if($arr_check_emp_time_log) {
                $cant_proceed_emp = true;
            }
            /* END SET ARRAY */
            
            $setting_enabled = false;
            if ($schedule_request_settings) {
                $number_of_days = $schedule_request_settings->number_of_days;
                if ($schedule_request_settings->days_lead_time_prior == "enable") {
                    $setting_enabled = true;
                }
            }

            if ($setting_enabled) {
                if(date("Y-m-d",strtotime($date_from)) <= date("Y-m-d",strtotime("+".$number_of_days." days"))){
                    $result = array(
                        'error'=>true,
                        'msg'=>'Sorry, you need to request '.$number_of_days.' days before the desired schedule change date.'
                    );
                    echo json_encode($result);
                    return false;
                }
            }else if($cant_proceed_emp) {
                $result = array(
                        'error'=>true,
                        'msg'=>"Oops. You can not file for today's shift change request while your shift is ongoing.  Please file for a shift change after today's shift ends and you have clocked out."
                );
                echo json_encode($result);
                return false;
            } else {
                if($check_payroll_lock_closed == "Waiting for approval"){
                    $result = array(
                        'error'=>true,
                        'msg'=>'Payroll for the period affected is locked. No new change schedule request can be accepted. Please reach out to your HR or payroll manager to discuss options for this request.'
                    );
                    echo json_encode($result);
                    return false;
                } elseif ($check_payroll_lock_closed == "Closed") {
                    $result = array(
                        'error'=>true,
                        'msg'=>'Payroll for the period affected is closed. No new change schedule request can be accepted. Please reach out to your HR or payroll manager to discuss options for this request.'
                    );
                    echo json_encode($result);
                    return false;
                }
            }
	
			if($has_leave == false) {
					    
                // INSERT EMPLOYEE WORK SCHEDULE
                $val = array(
                    "date_filed" => date("Y-m-d H:i:s"),
                    "emp_id" => $this->emp_id,
                    "company_id" => $this->company_id,
                    "date_from" => date("Y-m-d",strtotime($date_from)),
                    "date_to" => date("Y-m-d",strtotime($date_to)),
                    "work_schedule_id" => $work_sched_id,
                    "start_time" => $schedule_from,
                    "end_time" => ($time_to == "") ? NULL : $schedule_to,
                    "reason" => $reason,
                    "employee_work_schedule_status" => "pending"
                );
                
                $insert = $this->db->insert("employee_work_schedule_application",$val);
                $employee_work_schedul_id = $this->db->insert_id();
                
                // INSERT EMPLOYEE WORK SCHEDULE TOKEN
                $str = 'abcdefghijk123456789';
                $shuffled = str_shuffle($str);
                
                $str2 = 'ABCDEFG1234567890';
                $shuffled2 = str_shuffle($str2);
                
                //$employee_details = get_employee_details_by_empid($this->emp_id);
                $psa_id = $this->session->userdata('psa_id');
                
                $approver_id = $this->employee->get_approver_shifts($this->emp_id,$this->company_id)->shedule_request_approval_grp;
                if($approver_id == "" || $approver_id == 0) {
                    // Employee with no approver will use default workflow approval
                    add_workflow_approval_default_group($this->company_id,$this->account_id); // create default if dont have any
                    $approver_id = get_app_default_approver($this->company_id,"Shifts")->approval_groups_via_groups_id;
                }
                
                $schedule_info = $this->agm->schedule_information($employee_work_schedul_id);
                $fullname = ucfirst($schedule_info->first_name)." ".ucfirst($schedule_info->last_name);
                $shifts_notification = get_notify_settings($approver_id, $this->company_id);
                $get_approval_settings_disable_status = $this->employee->get_approval_settings_disable_status($this->company_id);
                
                if($approver_id) {
                    if(is_workflow_enabled($this->company_id)){
                        $shifts_approver = $this->agm->get_approver_name_shifts($this->emp_id,$this->company_id);
                        if($shifts_approver){
                            if($shifts_notification){
                                $new_level = 1;
                                $lflag = 0;
                                
                                foreach ($shifts_approver as $sa){
                                    $appovers_id = ($sa->emp_id) ? $sa->emp_id : "-99{$this->company_id}";
                                    
                                    $get_approval_group_via_groups_owner = $this->agm->get_approval_group_via_groups_owner($sa->approval_process_id, $sa->company_id, $sa->approval_groups_via_groups_id, $appovers_id);
                                    
                                    if($get_approval_group_via_groups_owner->emp_id == "-99{$this->company_id}"){
                                        $owner_approver = get_approver_owner_info($this->company_id);
                                        $appr_name = ucwords($owner_approver->first_name." ".$owner_approver->last_name);
                                        $appr_account_id = $owner_approver->account_id;
                                        $appr_email = $owner_approver->email;
                                        $appr_id = "-99{$this->company_id}";
                                        $msb_emp_id = "0";
                                    } else {
                                        $appr_name = ucwords($sa->first_name." ".$sa->last_name);
                                        $appr_account_id = $sa->account_id;
                                        $appr_email = $sa->email;
                                        $appr_id = $msb_emp_id = $sa->emp_id;
                                    }
                                    
                                    if($sa->level == $new_level){

                                        // send with link
                                        $new_level = $sa->level;

                                        if($this->company_id == "1"){
                                            if($sa->ns_change_shift_email_flag == "yes"){
                                                $this->send_schedule_notification($shuffled, $employee_work_schedul_id, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "Approver" , "Yes", $shuffled2, $appr_id);
                                            }
                                        }else{
                                            $this->send_schedule_notification($shuffled, $employee_work_schedul_id, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "Approver" , "Yes", $shuffled2, $appr_id);
                                        }

                                        if($shifts_notification->sms_notification == "yes"){
                                            $url = base_url()."approval/work_schedule/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
                                            $sms_message = "{$fullname} has filed a Schedule Change Request.";
                                            send_this_sms_global($this->company_id, $appr_account_id,$sms_message,$psa_id,false);
                                        }

                                        if($shifts_notification->message_board_notification == "yes"){

                                            $url = base_url()."approval/work_schedule/index/".$shuffled."/".$shuffled2."/1".$appr_id."0";
                                            $next_appr_notif_message = "A Change Schedule Request has been filed by {$fullname} and is now waiting for your approval. Click this <a href='{$url}' target='_blank'><strong>link</strong></a> to approve.";
                                            send_to_message_board($psa_id, $msb_emp_id, $this->account_id, $this->company_id, $next_appr_notif_message,"system","warning","",$this->emp_id,"todo_shifts",$employee_work_schedul_id);
                                        }

                                        $lflag = 1;
                                        
                                    }else{
                                        if($this->company_id == "1"){
                                            if($sa->ns_change_shift_email_flag == "yes"){
                                                // send without link
                                                $this->send_schedule_notification($shuffled, $employee_work_schedul_id, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "" , "", "");
                                            }
                                        }else{
                                            $this->send_schedule_notification($shuffled, $employee_work_schedul_id, $this->company_id,$this->emp_id, $appr_email, $appr_name, "", "" , "", "");
                                        }

                                        if($shifts_notification->sms_notification == "yes"){
                                            $sms_message = "A Change Schedule Request has been filed by {$fullname}.";
                                        }

                                        if($shifts_notification->message_board_notification == "yes"){
                                            $next_appr_notif_message = "A Change Schedule Request has been filed by {$fullname}.";
                                            send_to_message_board($psa_id, $appr_id, $this->account_id,$this->company_id, $next_appr_notif_message, "system","information","",$this->emp_id,"todo_shifts",$employee_work_schedul_id);
                                        }
                                    }
                                }
                                
                                ################################ notify payroll admin start ################################
                                if($shifts_notification->notify_payroll_admin == "yes"){
                                    // HRs
                                    $payroll_admin_hr = $this->employee->get_payroll_admin_hr($psa_id);
                                    if($payroll_admin_hr){
                                        foreach ($payroll_admin_hr as $pahr){
                                            $pahr_email = $pahr->email;
                                            $pahr_name = ucwords($pahr->first_name." ".$pahr->last_name);

                                            if($this->company_id == "1"){
                                                if($pahr->ns_change_shift_email_flag == "yes"){
                                                    $this->send_schedule_notification($shuffled, $employee_work_schedul_id, $this->company_id,$this->emp_id, $pahr_email, $pahr_name, "", "" , "", "", "", "Yes");
                                                }
                                            }else{
                                                $this->send_schedule_notification($shuffled, $employee_work_schedul_id, $this->company_id,$this->emp_id, $pahr_email, $pahr_name, "", "" , "", "", "", "Yes");
                                            }
                                        }
                                    }
                                    
                                    // Owner
                                    $pa_owner = get_approver_owner_info($this->company_id);
                                    if($pa_owner){
                                        $pa_owner_name = ucwords($pa_owner->first_name." ".$pa_owner->last_name);
                                        $pa_owner_email = $pa_owner->email;
                                        $pa_owner_account_id = $pa_owner->account_id;

                                        if($this->company_id == "1"){
                                            if($pa_owner->ns_change_shift_email_flag == "yes"){
                                                $this->send_schedule_notification($shuffled, $employee_work_schedul_id, $this->company_id,$this->emp_id, $pa_owner_email, $pa_owner_name, "", "" , "", "", "", "Yes");
                                            }
                                        }else{
                                            $this->send_schedule_notification($shuffled, $employee_work_schedul_id, $this->company_id,$this->emp_id, $pa_owner_email, $pa_owner_name, "", "" , "", "", "", "Yes");
                                        }
                                    }
                                }
                                ################################ notify payroll admin end ################################
                                
                            }else{
                                $new_level = 1;
                                $result = array(
                                    'error'=>false,
                                    'msg'=>'No Hours notifications'
                                );
                                echo json_encode($result);
                                return false;
                            }
                            
                        } else {
                            $new_level = 1;
                            $result = array(
                                'error'=>false,
                                'msg'=>''
                            );
                            echo json_encode($result);
                            return false;
                        }
                        
                        $save_token = array(
                            "employee_work_schedule_application_id"=>$employee_work_schedul_id,
                            "token"=>$shuffled,
                            "company_id"=>$this->company_id,
                            "emp_id"=>$this->emp_id,
                            "approver_id"=>$approver_id,
                            "level"=>$new_level,
                            "token_level"=>$shuffled2
                        );
                        
                        $save_token_q = $this->db->insert("approval_work_schedule",$save_token);
                        
                        if($insert){
                            $result = array(
                                'error'=>false,
                                'msg'=>'Successfully Sent!'
                            );
                            echo json_encode($result);
                            return false;
                            
                        }
                    } else {
                        if($get_approval_settings_disable_status->status == "Inactive") {
                            if($get_approval_settings_disable_status->disabled_application_status == 'approve') {
                                $status = "approved";
                            } elseif ($get_approval_settings_disable_status->disabled_application_status == 'reject') {
                                $status = "rejected";
                            } else {
                                $status = $get_approval_settings_disable_status->disabled_application_status;
                            }
                            // ------------------------------- SCHEDULE REQUEST APPROVE START ------------------------------- //
                            $fields = array(
                                "employee_work_schedule_status" => $status,
                                "approval_date"	=> date("Y-m-d H:i:s")
                            );
                            $where = array(
                                "employee_work_schedule_application_id" => $employee_work_schedul_id,
                                "company_id" => $this->company_id
                            );
                            $this->shifts->update_field("employee_work_schedule_application",$fields,$where);
                            
                            // ------------------------------- SCHEDULE REQUEST APPROVE END ------------------------------- //
                            
                            // ------------------------------- SAVE to employee shift schedule (start) ------------------------------- //
                            $get_work_sched_app_id = $this->shifts->get_emp_work_sched_app($employee_work_schedul_id);
                            
                            if($get_work_sched_app_id) {
                                $delete_old_shift = array(
                                    'company_id' => $get_work_sched_app_id->company_id,
                                    'emp_id' => $get_work_sched_app_id->emp_id,
                                    'valid_from' => $get_work_sched_app_id->date_to,
                                    'until' => $get_work_sched_app_id->date_to,
                                );
                                
                                $this->db->delete('employee_shifts_schedule',$delete_old_shift);
                                
                                $sched_app_data = array(
                                    'company_id' => $get_work_sched_app_id->company_id,
                                    'emp_id' => $get_work_sched_app_id->emp_id,
                                    'valid_from' => $get_work_sched_app_id->date_to,
                                    'until' => $get_work_sched_app_id->date_to,
                                    'work_schedule_id' => $get_work_sched_app_id->work_schedule_id
                                );
                                
                                $this->db->insert('employee_shifts_schedule', $sched_app_data);
                            }
                            
                            // ------------------------------- SAVE to employee shift schedule (end) --------------------------------- //
                            $result = array(
                                'error'=>false,
                                'msg'=>''
                            );
                            echo json_encode($result);
                            return false;
                        }
                    }
                } else {
                    // gi delete ni ky g.pausab nsd ni donna, wala na dapat auto approve.. (Employee with no approver will use default workflow approval)
                    /*// ------------------------------- SCHEDULE REQUEST APPROVE START ------------------------------- //
                     $fields = array(
                     "employee_work_schedule_status" => "approved",
                     "approval_date"	=> date("Y-m-d H:i:s")
                     );
                     $where = array(
                     "employee_work_schedule_application_id" => $employee_work_schedul_id,
                     "company_id" => $this->company_id
                     );
                     $this->shifts->update_field("employee_work_schedule_application",$fields,$where);
                     
                     // ------------------------------- SCHEDULE REQUEST APPROVE END ------------------------------- //
                     
                     // ------------------------------- SAVE to employee shift schedule (start) ------------------------------- //
                     $get_work_sched_app_id = $this->shifts->get_emp_work_sched_app($employee_work_schedul_id);
                     
                     if($get_work_sched_app_id) {
                     $delete_old_shift = array(
                     'company_id' => $get_work_sched_app_id->company_id,
                     'emp_id' => $get_work_sched_app_id->emp_id,
                     'valid_from' => $get_work_sched_app_id->date_to,
                     'until' => $get_work_sched_app_id->date_to,
                     );
                     
                     $this->db->delete('employee_shifts_schedule',$delete_old_shift);
                     
                     $sched_app_data = array(
                     'company_id' => $get_work_sched_app_id->company_id,
                     'emp_id' => $get_work_sched_app_id->emp_id,
                     'valid_from' => $get_work_sched_app_id->date_to,
                     'until' => $get_work_sched_app_id->date_to,
                     'work_schedule_id' => $get_work_sched_app_id->work_schedule_id
                     );
                     
                     $this->db->insert('employee_shifts_schedule', $sched_app_data);
                     }
                     
                     // ------------------------------- SAVE to employee shift schedule (end) --------------------------------- //
                     $result = array(
                     'error'=>false,
                     'msg'=>''
                     );
                     echo json_encode($result);
                     return false;*/
                }
                
            }else{
                $result = array(
                    'error'=>true,
                    'msg'=>'',
                    'has_leaves'=> $message_leaves
                );
                echo json_encode($result);
                return false;
            }
					
		}
		
	}
		
	public function send_schedule_notification($token = NULL, $work_schedule_id = NULL, $comp_id = NULL, $emp_id = NULL, $email = NULL, $approver_full_name = "", $last_approver = "", $who = "" , $withlink = "No", $level_token = "", $appr_id = "", $notify_admin = ""){
		$work_schedule_info = $this->agm->schedule_information($work_schedule_id);
		
		if($work_schedule_info){
			
			$fullname = ucfirst($work_schedule_info->first_name)." ".ucfirst($work_schedule_info->last_name);
			$date_applied = date('F d, Y', strtotime($work_schedule_info->date_filed));
			$date_from = date("F d, Y",strtotime($work_schedule_info->date_from));
			$date_to = date("F d, Y",strtotime($work_schedule_info->date_to));
			$start_time = date("h:i A",strtotime($work_schedule_info->start_time));
			$end_time = date("h:i A",strtotime($work_schedule_info->end_time));
			
			$current_schedule = "No Schedule";
			$flag_date = $work_schedule_info->date_filed;
			$check_employee_work_schedule = $this->ews->check_employee_work_schedule($flag_date,$work_schedule_info->emp_id,$work_schedule_info->company_id);
			if($check_employee_work_schedule != FALSE){
				$work_schedule_id = $check_employee_work_schedule->work_schedule_id;
					
				$weekday = date('l',strtotime($flag_date));
				$rest_day = FALSE;
					
				// check rest day
				if($work_schedule_id!=FALSE){
					/* EMPLOYEE WORK SCHEDULE */
					$rest_day = $this->ews->get_rest_day($work_schedule_info->company_id,$work_schedule_id,$weekday);
				}
					
				if($rest_day){
					$current_schedule = "RD";
				}else{
					$emp_work_schedule_info = $this->ews->work_schedule_info($work_schedule_info->company_id,$work_schedule_id,$weekday);
					if($work_schedule_id && $emp_work_schedule_info){
						$st = ($emp_work_schedule_info["work_schedule"]["start_time"] == "") ? "" : date("h:i a",strtotime($emp_work_schedule_info["work_schedule"]["start_time"]));
						$et = ($emp_work_schedule_info["work_schedule"]["end_time"] == "") ? "" : date("h:i a",strtotime($emp_work_schedule_info["work_schedule"]["end_time"]));
						$shift_name = $emp_work_schedule_info["work_schedule"]["shift_name"];
						if($st != "" && $et != ""){
							$str = "{$st} - {$et}";
						}elseif($st != "" && $et == ""){
							$str = "{$st}";
						}else{
							$str = "Flexible Hours";
						}
							
						$current_schedule = "{$str}";
					}
				}
			}
			
			$subject_line = "Action Required: {$fullname}'s Shift request is awaiting your approval.";
			#$link = '<a href="'.base_url().'approval/work_schedule/index/'.$token.'/'.$level_token.'/1'.$appr_id.'0"><img src="'.base_url().'assets/theme_2015/images/images-emailer/btn-view-schedule-request.png" width="274" height="42" alt=" "></a>';
			$link = '<a style="color:#1172ad; text-decoration:underline; font-size:16px;" href="'.base_url().'approval/work_schedule/index/'.$token.'/'.$level_token.'/1'.$appr_id.'0">Change Schedule Application</a>';
			if($who == "Approver"){
				if($withlink == "No"){
					$link = '';
					$subject_line = "Coming your way, {$fullname}'s Shift request has been submitted.";
				}
			}else{
				$link = "";
				$subject_line = "Coming your way, {$fullname}'s Shift request has been submitted.";
				
				if($notify_admin == "Yes") {
				    $subject_line = "Heads up! {$fullname}'s Shift request has been submitted.";
				}
			}
			
			$font_name = "'Open Sans'";
				
			$config['protocol'] = 'sendmail';
			$config['wordwrap'] = TRUE;
			$config['mailtype'] = 'html';
			$config['charset'] = 'utf-8';
			
			$this->load->library('email',$config);
			$this->email->initialize($config);
			$this->email->set_newline("\r\n");
			$this->email->from(notifications_ashima_email(),'Ashima');
			$this->email->to($email);
			$this->email->subject($subject_line);
				
			$this->email->message('
			<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
			<html lang="en">
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
					<meta name="format-detection" content="telephone=no">
					<title>Change Schedule Request</title>
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
																	<p style="font-size:16px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#000; margin:0">New Change Schedule Request has been filed. Details below:</p>
																</td>
															</tr>
														</table>
													</td>
												</tr>
												<tr>
													<td valign="top" style="padding-top:25px;">
														<table width="100%" border="0" cellspacing="0" cellpadding="0">
															<tr>
																<td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Applicant:</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$fullname.'</td>
															</tr>
															<tr>
																<td style="width:140px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date Applied:</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date_applied.'</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date From:</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date_from.'</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Date To:</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$date_to.'</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Time:</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$start_time.' - '.$end_time.'</td>
															</tr>
															<tr>
																<td style="width:132px; font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:300; color:#666; padding-bottom:18px;" valign="top">Reason:</td>
																<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-bottom:18px;">'.$work_schedule_info->reason.'</td>
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
										<td valign="top" style="font-size:12px; font-family:'.$font_name.', Arial, Helvetica, sans-serif; font-weight:400; color:#000; padding-top:15px;">&copy; '.date('Y').' Konsum Technologies. All Rights Reserved.</td>
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
	
	/**
	 * Send Notification to Approvers
	 * @param unknown_type $work_schedule_info
	 * @param unknown_type $url
	 * @param unknown_type $name
	 * @param unknown_type $email
	 */
	public function send_noti_to_approver($work_schedule_info,$url,$name,$email,$view_link=NULL){
		if($email != NULL){
			$config['protocol'] = 'sendmail';
			$config['wordwrap'] = TRUE;
			$config['mailtype'] = 'html';
			$config['charset'] = 'utf-8';
	
			$this->load->library('email',$config);
			$this->email->initialize($config);
			$this->email->set_newline("\r\n");
			$this->email->from('payroll@konsum.ph','Konsum Payroll System ');
			$this->email->to($email);
	
			$employee_fullname = ucwords("{$work_schedule_info->first_name} {$work_schedule_info->last_name}");
			$this->email->subject("New Schedule Request - {$employee_fullname}");
	
			$link = ($view_link != "") ? '<tr><td>Click <a href="'.$url.'">here</a> to approve or reject the request.</td></tr>' : "" ;
	
			// CURRENT SCHEDULE
			$current_schedule = "No Schedule";
			$flag_date = $work_schedule_info->date_filed;
			$check_employee_work_schedule = $this->ews->check_employee_work_schedule($flag_date,$work_schedule_info->emp_id,$work_schedule_info->company_id);
			if($check_employee_work_schedule != FALSE){
				$work_schedule_id = $check_employee_work_schedule->work_schedule_id;
					
				$weekday = date('l',strtotime($flag_date));
				$rest_day = FALSE;
					
				// check rest day
				if($work_schedule_id!=FALSE){
					/* EMPLOYEE WORK SCHEDULE */
					$rest_day = $this->ews->get_rest_day($work_schedule_info->company_id,$work_schedule_id,$weekday);
				}
					
				if($rest_day){
					$current_schedule = "Rest Day";
				}else{
					$emp_work_schedule_info = $this->ews->work_schedule_info($work_schedule_info->company_id,$work_schedule_id,$weekday);
					if($work_schedule_id && $emp_work_schedule_info){
						$st = ($emp_work_schedule_info["work_schedule"]["start_time"] == "") ? "" : date("h:i a",strtotime($emp_work_schedule_info["work_schedule"]["start_time"]));
						$et = ($emp_work_schedule_info["work_schedule"]["end_time"] == "") ? "" : date("h:i a",strtotime($emp_work_schedule_info["work_schedule"]["end_time"]));
						$shift_name = $emp_work_schedule_info["work_schedule"]["shift_name"];
						if($st != "" && $et != ""){
							$str = "{$st} - {$et}";
						}elseif($st != "" && $et == ""){
							$str = "{$st}";
						}else{
							$str = "Flexible Hours";
						}
							
						$current_schedule = "{$str}";
					}
				}
			}
	
			$name = ucwords("{$name}");
	
			$this->email->message(
					'
					<html>
						<head>
							<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
						</head>
						<body>
							<table>
								<tbody>
									<tr>
										<td>
											Hi '.$name.',
										</td>
									</tr>
									<tr><td>&nbsp;</td></tr>
									<tr><td>New schedule request has been filed by '.$employee_fullname.'. Please see details below.
					
									<tr><td>&nbsp;</td></tr>
									<tr><td>Current Schedule: '.$current_schedule.'</td></tr>
					
									<tr><td>&nbsp;</td></tr>
									<tr><td>Change Date From: '.date("F d, Y",strtotime($work_schedule_info->date_from)).'</td></tr>
					
									<tr><td>&nbsp;</td></tr>
									<tr><td>Change Date To: '.date("F d, Y",strtotime($work_schedule_info->date_to)).'</td></tr>
					
									<tr><td>&nbsp;</td></tr>
									<tr><td>Schedule From: '.date("h:i A",strtotime($work_schedule_info->start_time)).'</td></tr>
					
									<tr><td>&nbsp;</td></tr>
									<tr><td>Schedule To: '.date("h:i A",strtotime($work_schedule_info->end_time)).'</td></tr>
					
									<tr><td>&nbsp;</td></tr>
									<tr><td>Reason: '.$work_schedule_info->reason.'</td></tr>
					
									<tr><td>&nbsp;</td></tr>
					
									'.$link.'
					
									<tr><td>&nbsp;</td></tr>
									<tr><td>&nbsp;</td></tr>
									<tr>
										<td>
											Thank you,<br />Konsum Payroll System
										</td>
									</tr>
								</tbody>
							</table>
						</body>
					</html>
					'
			);
	
			if($this->email->send()){
				$lang_approve = "{$employee_fullname} schedule request has been approved by head as of ".time_only(idates_now());
				add_activity($lang_approve,$work_schedule_info->company_id);
				return TRUE;
			}else{
				return FALSE;
			}
		}else{
			//show_error("Invalid parameter");
			echo json_encode(array("result"=>3,"error_msg"=>"- Invalid parameter."));
			return false;
		}
	}
	
	/**
	 * TWEET on twttier
	 * Enter description here ...
	 * @param int $account_id
	 * @param string $message2
	 * @param string $pm_hr_twitter ang username sa twitter sa hr or pm ni ha
	 */
	public function tweetontwitter($account_id,$message2,$pm_hr_twitter){
		if($account_id) {
			$profile = $this->social->tweet_account($account_id);
			if($profile) {
				$consumer_key = $profile->consumer_key;//"AMwFOney54o2jnU5Cek8RDpHJ";
				$consumer_secret = $profile->consumer_secret;#"MEL5Ztfh241p6dgd9T7Yhjqbqsfu3MhthZxY6CMQ8wfAJDtncB";
				$oauth_access_token  = $profile->oauth_access_token;#"265681837-JfQ7iYxJopy88K1h4ec5nHvbldOsNGwwTJsc6WkS";
				$oauth_access_token_secret  = $profile->oauth_access_token_secret;#"2k1sqmSwzSvkiSsEL41nNknJwVI3qtDKaHGtG2U6vtIjw";
				$this->connection = $this->twitteroauth->create($consumer_key,$consumer_secret,$oauth_access_token,$oauth_access_token_secret);
				if($this->connection){
					/* disable lang ni mao ning katong twitter post sa imong wall
					 $message = array(
					 'status' => urldecode($message2),
					 'possibly_sensitive'=>false
					 );
					 $result = $this->connection->post('statuses/update', $message);
					 **/
					 if($pm_hr_twitter){
					$options = array("screen_name"=>$pm_hr_twitter,"text" =>urldecode($message2));
					$direct = $this->connection->post('direct_messages/new', $options);
							return true;
				}else{
				return false;
				}
				}else{
				return false;
			}
		}else{
			return false;
		}
		}
	}
		
}	