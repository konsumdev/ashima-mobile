<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dashboard extends CI_Controller{
	
	public function __construct(){
		parent::__construct();
		
		$this->load->model('employee_model','employee');
		$this->load->model('import_timesheets_model','import');
		$this->load->model('employee_work_schedule_model','ews');
		$this->load->model('login_screen_model');
		$this->load->model('emp_login_model','elm');
		$this->load->model('employee_mobile_model','mobile');
		$this->load->model('import_timesheets_model_v2','importv2');
		$this->load->model('employee_v2_model','employee_v2');
		
		$this->emp_id = $this->session->userdata('emp_id');
	  	$this->company_id = $this->employee->check_company_id($this->emp_id);
	  	
	  	$this->work_schedule_id_pg = check_employee_work_schedule_else($this->emp_id)->work_schedule_id;
	  	$this->next_period = next_pay_period_v2($this->emp_id, $this->company_id);
	  	$this->check_employee_work_schedule_v2 = check_employee_work_schedule_v2($this->emp_id, $this->company_id);
	
	}
	
	public function index(){
		$emp_no = $this->employee->check_emp_no($this->emp_id,$this->company_id);		
		
		#$time_ins = get_time_in($this->emp_id, $this->company_id);
		$currentdate = date('Y-m-d');
		$vx = $this->elm->activate_nightmare_trap($this->company_id,$emp_no);
			
		if($vx){
			$currentdate = $vx['currentdate'];
		}
		$currentdate = $currentdate;
		
		$result_array = array();
	
		$emp_work_schedule_id = $this->login_screen_model->emp_work_schedule2($emp_no,$this->company_id,$currentdate);	
		$time_ins = $this->employee_timein_now($this->emp_id,$this->company_id,$emp_no);
		$get_work_sched = $this->get_work_sched_work_type($emp_work_schedule_id,$this->company_id);
		$check_time_in = $this->elm->check_time_log($currentdate,$emp_no,5,$emp_work_schedule_id,false,$this->company_id);
		$get_mobile_time_tracking_setting = $this->mobile->get_mobile_time_tracking_setting($this->company_id);
		
		if($get_mobile_time_tracking_setting->mobile_clockin == 'enable') {
			if($time_ins){
				$break_split = $this->ews->check_breaktime_split($this->company_id, $this->emp_id, $time_ins->date, $time_ins->work_schedule_id);
			
				$break_split_tot = 0;
				if($break_split) {
					$break_split_tot = $break_split->break_in_min;
				}
					
				$check_employee_id = $this->login_screen_model->new_check_employee_id($emp_no,$this->company_id);
				$emp_work_schedule_id = 0;
				if($check_employee_id){
					$emp_work_schedule_id = $this->import->emp_work_schedule($check_employee_id->emp_id,$this->company_id,$currentdate);
						
					if(!$emp_work_schedule_id){
						/* check employee id */
						$emp_work_schedule_id = $this->elm->if_no_workschedule($check_employee_id->emp_id,$this->company_id);
					}
				}
					
				$time_list_box = $this->elm->get_time_list($emp_no,$emp_work_schedule_id,$this->company_id,$currentdate);
					
				$gero = $this->elm->emp_login_lock($emp_work_schedule_id,$this->company_id,$emp_no,$currentdate);
				if($get_work_sched) {
					if ($get_work_sched->work_type_name == 'Workshift') {
						if($time_list_box) {
							if($time_list_box->time_in == NULL ){
								$temp_array = array("label" => "Clock In");
								array_push($result_array, $temp_array);
							} else {
								if(check_if_have_break($this->emp_id, $this->company_id,$currentdate) || $gero){
									if(!is_break_assumed($time_ins->work_schedule_id)) {
										if($time_list_box->lunch_out == NULL){
											$temp_array = array("label" => "Lunch Out");
											array_push($result_array, $temp_array);
										} else {
											if($time_list_box->lunch_in == NULL){
												$temp_array = array("label" => "Lunch In");
												array_push($result_array, $temp_array);
											}else{
												if($time_list_box->time_out == NULL){
													$temp_array = array("label" => "Clock Out");
													array_push($result_array, $temp_array);
												}
											}
										}
									}
								}
			
								if($time_list_box->time_out == NULL){
									if(!check_if_have_break($this->emp_id, $this->company_id,$currentdate) || !$gero){
										if(!$gero) {
											if($check_time_in != true){
												$temp_array = array("label" => "Clock Out");
												array_push($result_array, $temp_array);
											}
										}
									}
								}
							}
						} else {
							$temp_array = array("label" => "Clock In");
							array_push($result_array, $temp_array);
						}
					} else {
						if($time_ins->time_in == NULL ){
							$temp_array = array("label" => "Clock In");
							array_push($result_array, $temp_array);
						}else{
							if(check_if_have_break($this->emp_id, $this->company_id,$currentdate)){
								if(!is_break_assumed($time_ins->work_schedule_id)) {
									if($time_ins->lunch_out == NULL){
										$temp_array = array("label" => "Lunch Out");
										array_push($result_array, $temp_array);
											
									}else{
										if($time_ins->lunch_in == NULL){
											$temp_array = array("label" => "Lunch In");
											array_push($result_array, $temp_array);
										}else{
											if($time_ins->time_out == NULL){
												$temp_array = array("label" => "Clock Out");
												array_push($result_array, $temp_array);
											}
										}
									}
								} else {
									if($time_ins->time_out == NULL){
										$temp_array = array("label" => "Clock Out");
										array_push($result_array, $temp_array);
									}
								}
							}
								
							if($time_ins->time_out == NULL){
								if(!check_if_have_break($this->emp_id, $this->company_id,$currentdate)){
									if($break_split_tot == 0) {
										//if($check_time_in != true){
											$temp_array = array("label" => "Clock Out");
											array_push($result_array, $temp_array);
										//}
									}
								}
							}
						}
					}
				} else {
					if($time_ins->time_in == NULL ){
						$temp_array = array("label" => "Clock In");
						array_push($result_array, $temp_array);
					}else{
						if(check_if_have_break($this->emp_id, $this->company_id,$currentdate)){
							if(!is_break_assumed($time_ins->work_schedule_id)) {
								if($time_ins->lunch_out == NULL){
									$temp_array = array("label" => "Lunch Out");
									array_push($result_array, $temp_array);
								}else{
									if($time_ins->lunch_in == NULL){
										$temp_array = array("label" => "Lunch In");
										array_push($result_array, $temp_array);
									}else{
										if($time_ins->time_out == NULL){
											$temp_array = array("label" => "Clock Out");
											array_push($result_array, $temp_array);
										}
									}
								}
							} else {
								if($time_ins->time_out == NULL){
									$temp_array = array("label" => "Clock Out");
									array_push($result_array, $temp_array);
								}
							}
						}
			
						if($time_ins->time_out == NULL){
							if(!check_if_have_break($this->emp_id, $this->company_id,$currentdate)){
								if($break_split_tot == 0) {
									if($check_time_in != true){
										$temp_array = array("label" => "Clock Out");
										array_push($result_array, $temp_array);
									}
								}
							}
						}
					}
				}
			} else {
				if(check_if_required($this->emp_id, $this->company_id,$currentdate)) {
					$temp_array = array("label" => "Clock In");
					array_push($result_array, $temp_array);
				} else {
					$temp_array = array("label" => "Clock In");
					array_push($result_array, $temp_array);
				}
			}
		}
		
		$mobile_clockin = $get_mobile_time_tracking_setting->mobile_clockin;
		
		$if_not_required_to_Login = if_not_required_to_Login($this->emp_id,$this->company_id);
		$if_not_required_to_Login = ($if_not_required_to_Login) ? true : false;
		
		echo json_encode(array("no_need_clockin" => $if_not_required_to_Login, "clock_in_required" => $result_array, "mobile_clockin" => $mobile_clockin));
		
    }
    
    function employee_mobile_clockguard_v2($emp_id) {
        if (!$emp_id) {
            return false;
        }

        $sel = array(
            'employee_mobile_clockin',
            'employee_mobile_face_id',
            'employee_mobile_require_face_id',
            'mobile_employee_level_restrict'
        );
        
        $whr = array(
            'emp_id' => $emp_id
        );
        // $this->db->select($sel);
        $this->db->where($whr);
        $q = $this->db->get('employee_payroll_information');
        $r = $q->row();
        return ($r) ? $r : false;
    }

	public function get_clockin_settings()
	{
		$emp_id 		= $this->emp_id;
        $emp_no = $this->employee->check_emp_no($this->emp_id,$this->company_id);
        
		$check_emp_info = $this->mobile->new_check_emp_info($emp_no,$this->company_id);		
		$required_to_Login = ($check_emp_info) ? $check_emp_info->timesheet_required : false;
		$if_not_required_to_Login = ($required_to_Login == "no") ? true : false;

		$get_mobile_time_tracking_setting = $this->mobile->get_mobile_time_tracking_setting($this->company_id);
        $mobile_clockin = false;//$get_mobile_time_tracking_setting->mobile_clockin;
        $face_id = false;
        $emp_level_restrict = false;
        
        if ($get_mobile_time_tracking_setting) {
            // employee level
            $ind = $this->employee_mobile_clockguard_v2($emp_id);
            if ($ind) {
                
                if (isset($ind->mobile_employee_level_restrict)) {
                    if ($ind->mobile_employee_level_restrict == "enable") {
                        $emp_level_restrict = true;
                    }
                } else {
                    if ($get_mobile_time_tracking_setting->mobile_employee_restriction == 'enable') {
                        $emp_level_restrict = true;
                    }
                }

                   
            } 
            // check employee level photo capture
            if ($emp_level_restrict) {

                // check if enabled on employee level
                if ($ind->employee_mobile_clockin == 'enable') {
                    $mobile_clockin = true;
                    // check employee level face capture
                    if ($ind->employee_mobile_face_id == 'enable') {
                        $face_id = true;
                    } else {
                        $face_id = false;
                    }
                } else {
                    $mobile_clockin = false;
                }
            } else {
                // global 
                if ($get_mobile_time_tracking_setting->mobile_clockin == 'enable') {
                    $mobile_clockin = true;

                    // check global photo capture
                    if ($get_mobile_time_tracking_setting->mobile_face_id == 'enable') {
                        $face_id = true; 
                    }
                }
            }
        }
		$result_array = array();
		$temp_array = array("label" => "Punch In/Out");
		array_push($result_array, $temp_array);

        echo json_encode(array(
            "no_need_clockin" => "", //$if_not_required_to_Login, 
            "clock_in_required" => $result_array, 
            "mobile_clockin" => $mobile_clockin,
            "photo_capture" => $face_id
        ));
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
	
	public function employee_timein_now($emp_id,$comp_id,$emp_no){
			
		$date = date('Y-m-d');
		$vx = $this->elm->activate_nightmare_trap($comp_id,$emp_no);
			
		if($vx){
			$date = $vx['currentdate'];
	
		}
			
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
	
	function getPayslip(){
		$payslips = get_payslips($this->emp_id, $this->company_id);
		
		$result = array();
		
		if($payslips){
			$count = 0;
			foreach($payslips as $pay){
				if(check_approval_date($pay->payroll_date, $pay->period_from, $pay->period_to, $this->company_id)){
					if($count <=3){
						$count++;
						$temp_array = array("payroll_date" => date('M j, Y',strtotime($pay->payroll_date)));
						
						array_push($result, $temp_array);
					}
				}
			}
		}
		
		echo json_encode($result);
	}
	
	function getMissedPunches() {
        $today       = date("Y-m-d");
        $min_date    = date("Y-m-d", strtotime($today." -25 days"));
        $max_date    = date("Y-m-d", strtotime($today." +5 days"));
        $get_payslips = get_emp_payslips($this->company_id,date("Y-m-d",strtotime($max_date.' -2 months')));
        $next_period = emp_next_pay_period($this->company_id,$this->emp_id,$get_payslips);
        $emp_check_rest_day = emp_check_rest_day($this->company_id);
        $get_holidays = get_holidays($this->company_id);
        $missed = count_emp_missed_punches($this->emp_id, $this->company_id,$this->check_employee_work_schedule_v2,$this->work_schedule_id_pg,$next_period,$emp_check_rest_day,$get_holidays);
        
        echo json_encode(array("punches" => $missed));
	}
	
	function leave_doughnut(){
		$leave_array = array();
        $leave_credits = $this->employee->leave_credits($this->company_id,$this->emp_id);
        		
		if($leave_credits){
			foreach ($leave_credits as $lc){
				$pending_leave = 0;
				$pending = $this->employee->pending_remaining_credits($this->company_id,$this->emp_id);
				if($pending){
					foreach ($pending as $p_app){
						if($p_app->leave_type_id == $lc->leave_type_id){
							///$total_leave_request = ($p_app->total_leave_requested) ? $p_app->total_leave_requested : 0;
							$pending_leave = $pending_leave + $p_app->total_leave_requested;
						}
					}
                }
                $available = ($lc->remaining_leave_credits == '' || $lc->remaining_leave_credits == null) ? $lc->a_leave_units : $lc->remaining_leave_credits;
                $used = (float)$lc->a_leave_credits - (float)$available;
                $used = ($used > 0)? $used : 0;
                
                $indi = array(
						"leaves_id" => $lc->leaves_id,
						"emp_id" => $lc->emp_id,
						"leave_type_id" => $lc->leave_type_id,
                        "a_leave_credits" => $lc->a_leave_credits,
                        "remaining_leave_credits" => $lc->remaining_leave_credits,
                        "used_lc" => $used,
						"leave_type" => $lc->leave_type,
						"pending_leaves" => $pending_leave,
						"a_leave_units" => $lc->a_leave_units
				);
				array_push($leave_array,$indi);
            }

            echo json_encode($leave_array);
		    return false;
        }
        echo json_encode(array(
            "result" => 0
        ));
        return false;
	}
	
	public function next_shift() {
		
		$ns = next_shift($this->emp_id, $this->company_id);
		
		
		if($ns){
			if($ns['flexible'] == true) {
				$flex = 1;
			} else {
				$flex = 0;
			}
			if($ns['flexible']){
				if($ns['start_time'] == ""){
					echo json_encode(array("shift_name" => $ns['shift_name'], "shift_date" => $ns['shift_date'], "start_time" => "", "end_time" => "", "flexible" => $flex, "required_login" => "no"));
					//echo json_encode(nl2br("You are on a {$ns['shift_name']}\nNo clock-in is required"));
					//"shift_name":"flex","shift_date":"2016-03-04","start_time":"08:00:00","end_time":"","flexible":true
				}else{
					//echo date('h:i A', strtotime($next_shift['start_time']));
					//echo json_encode(nl2br("You are on a {$ns['shift_name']}\nLatest clock-in: \n".date('h:i A', strtotime($ns['start_time']))));
					echo json_encode(array(
                        "shift_name" => $ns['shift_name'], 
                        "shift_date" => $ns['shift_date'], 
                        "start_time" => ($ns['start_time']) ? date("h:i A", strtotime($ns['start_time'])) : '', 
                        "end_time" => "", 
                        "flexible" => $flex, 
                        "required_login" => "yes"
                    ));
				}
			}else{
				//echo json_encode(date('h:i A', strtotime($ns['start_time'])).' - '. date('h:i: A', strtotime($ns['end_time'])));
				echo json_encode(array(
                    "shift_name" => $ns['shift_name'], 
                    "shift_date" => $ns['shift_date'], 
                    "start_time" => ($ns['start_time']) ? date("h:i A", strtotime($ns['start_time'])) : '', 
                    "end_time" => ($ns['end_time']) ? date("h:i A", strtotime($ns['end_time'])) : '', 
                    "flexible" => $flex, 
                    "required_login" => ""
                ));
			}
		}else{
			echo json_encode("~");
		}
		
		
 		//echo json_encode($ns);
		
		return false;
	}
	
	public function next_pay_date() {
		$npp = next_pay_period($this->emp_id,$this->company_id);
		
		echo json_encode($npp);
		
		return false;
	}
	
	public function timesheet(){

		$today       = date("Y-m-d");
		$min_date    = date("Y-m-d", strtotime($today." -25 days"));
		$max_date    = date("Y-m-d", strtotime($today." +5 days"));
					
		$data['check_employee_work_schedule_v2'] = check_employee_work_schedule_by_emp_id($this->emp_id, $this->company_id,$min_date,$max_date);
		$data['work_schedule_id_pg']             = check_employee_work_schedule_else($this->emp_id)->work_schedule_id;
		$get_payslips                            = get_emp_payslips($this->company_id,date("Y-m-d",strtotime($max_date.' -2 months')));
		$data['next_period']                     = emp_next_pay_period($this->company_id,$this->emp_id,$get_payslips);
		$data["emp_check_rest_day"]              = emp_check_rest_day($this->company_id);
		$data['get_holidays']                    = $this->employee_v2->check_is_date_holidayv2($this->company_id); //get_holidays($this->company_id);

		$mt = emp_missing_timesheet($this->emp_id, $this->company_id,$data['check_employee_work_schedule_v2'],$data['work_schedule_id_pg'],$data['next_period'],$data["emp_check_rest_day"],$data['get_holidays']);
		$pt = emp_pending_timesheet($this->emp_id, $this->company_id,$data['next_period'],$data['check_employee_work_schedule_v2'],$data['work_schedule_id_pg']);
		$rt = emp_rejected_timesheet($this->emp_id, $this->company_id,$data['check_employee_work_schedule_v2'],$data['work_schedule_id_pg'],$data['next_period'],$data["emp_check_rest_day"]);

		if(if_not_required_to_Login($this->emp_id,$this->company_id)) {
			$mt = 0;
			$pt = 0;
			$rt = 0;
		}
		
		$time = array(
			'missing' => $mt,
			'pending' => $pt,
			'rejected' => $rt
		);
		
		echo json_encode($time);
		
		return false;
	}
	
	public function attendance(){
		
		$ca = count_absences_v2($this->emp_id, $this->company_id,$this->check_employee_work_schedule_v2,$this->work_schedule_id_pg); //count_absences($this->emp_id, $this->company_id);
		$ct = count_tardiness_v2($this->emp_id, $this->company_id);
		$cu = count_undertime_v2($this->emp_id, $this->company_id);
		
		if(if_not_required_to_Login($this->emp_id,$this->company_id)) {
			$ca = 0;
			$ct = 0;
			$cu = 0;
		}
	
		$att = array(
				'absences' => $ca,
				'tardiness' => $ct,
				'undertime' => $cu
		);
	
		echo json_encode($att);
	
		return false;
	}
	
	public function get_birthday(){
		$birthday = $this->employee->get_birthdays_for_supervisor($this->company_id,$this->emp_id);
		
		$birthday = $birthday[0];
		
		if($birthday){
			$result = array();
			foreach ($birthday as $bd){
				$name = ucwords($bd->first_name." ".$bd->last_name);
				$position = ($bd->position_name != "") ? ucwords($bd->position_name) : "~";
				$bday = date("d-M",strtotime($bd->dob));
				$pp = thumb_pic($bd->account_id,$this->company_id);
				#$pro_pic = explode('/', $pp);
				#echo $pp;
				$att = array(
						'name' => $name,
						'position' => $position,
						'bday' => $bday,
						'profile' => $pp,
						'company_id' => $bd->company_id
				);
				
				array_push($result,$att);
			}
			
			echo json_encode($result);
		}
	}
	public function get_anniversary() {
		$anniversaries = $this->employee->get_anniversaries_for_supervisor($this->company_id,$this->emp_id);
		$anniversaries = $anniversaries[0];
		
		if($anniversaries){
			$result = array();
			foreach ($anniversaries as $an){
				$name = ucwords($an->first_name." ".$an->last_name);
				$position = ($an->position_name != "") ? ucwords($an->position_name) : "~";
				$num_of_years = date("Y") - date("Y",strtotime($an->date_hired));
				$year_text = ($num_of_years > 1) ? "Years" : "Year";
				$pp = thumb_pic($an->account_id,$this->company_id);
				#$pro_pic = explode('/', $pp);
				
				$att = array(
						'name' => $name,
						'year' => "{$num_of_years} {$year_text}",
						'profile' => $pp,
						'company_id' => $an->company_id,
						'position' => $position
				);
																	 
				array_push($result,$att);
			}
			
			echo json_encode($result);
		}
	}
	
	public function get_out_of_office() {
		$out_of_office = $this->employee->out_of_office($this->company_id,$this->emp_id);
		#$out_of_office = $out_of_office[0];
		
		if($out_of_office){
			$result = array();
			foreach ($out_of_office as $ooo){
				$name = ucwords($ooo['first_name']." ".$ooo['last_name']);
				$position = ($ooo['position_name'] != "") ? ucwords($ooo['position_name']) : "~";
				$pp = $ooo['profile_image'];
				$pp = thumb_pic($ooo['account_id'],$this->company_id);
				#$pro_pic = explode('/', $pp);
				
				$att = array(
						'name' => $name,
						'position' => $position,
						'profile' => $pp,
						'company_id' => $this->company_id
				);
				
				array_push($result,$att);
			}
			
			echo json_encode($result);
		}
	}
	
}