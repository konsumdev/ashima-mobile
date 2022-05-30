<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Timesheet extends CI_Controller{
	public function __construct(){
		parent::__construct();
	
		$this->load->model('employee_model','employee');
		$this->load->model('employee_work_schedule_model','ews');
		
		$this->emp_id = $this->session->userdata('emp_id');
		$this->company_id = $this->employee->check_company_id($this->emp_id);
		
		$this->work_schedule_id_pg = (check_employee_work_schedule_else($this->emp_id)) ? check_employee_work_schedule_else($this->emp_id)->work_schedule_id : "";
		$this->next_period = next_pay_period_v2($this->emp_id, $this->company_id);
		$this->check_employee_work_schedule_v2 = (check_employee_work_schedule_v2($this->emp_id, $this->company_id)) ? check_employee_work_schedule_v2($this->emp_id, $this->company_id) : "";
	
	}
	
	public function index(){
		
	}
	
	public function missed(){
		if($this->check_employee_work_schedule_v2 && $this->work_schedule_id_pg) {
            
            $today       = date("Y-m-d");
            $min_date    = date("Y-m-d", strtotime($today." -25 days"));
            $max_date    = date("Y-m-d", strtotime($today." +5 days"));
            $get_payslips = get_emp_payslips($this->company_id,date("Y-m-d",strtotime($max_date.' -2 months')));
            $next_period = emp_next_pay_period($this->company_id,$this->emp_id,$get_payslips);
            $emp_check_rest_day = emp_check_rest_day($this->company_id);
            $get_holidays = get_holidays($this->company_id);
            $missed = count_emp_missed_punches($this->emp_id, $this->company_id,$this->check_employee_work_schedule_v2,$this->work_schedule_id_pg,$next_period,$emp_check_rest_day,$get_holidays);
            
			if($missed > 0){
				$period = $this->next_period;
				$final = array();
				if($period){
					$today = date('Y-m-d', strtotime($period->cut_off_from));
					$date = date('Y-m-d', strtotime('-1 day'));
					
					$dateRange = dateRange($today, $date);
					
					foreach ($dateRange as $new_date) {
						$date = $new_date;
						$count = 0;
						
						$work_sched_id1 = in_array_custom("date-{$date}", $this->check_employee_work_schedule_v2);
						if($work_sched_id1) {
							$work_sched_id = $work_sched_id1->work_schedule_id;
						} else {
							$work_sched_id = $this->work_schedule_id_pg;
						}
						
						$is_break_assumed = is_break_assumed($work_sched_id);
						
						if(!$work_sched_id){
							return false; break;
						}
						
						$rest_day = $this->ews->get_rest_day($this->company_id,$work_sched_id,date('l',strtotime($date)));
						$check_holiday = $this->employee->get_holiday_date($date,$this->emp_id,$this->company_id);
						
						$work_schedule_info = $this->ews->work_schedule_info($this->company_id,$work_sched_id,date('l',strtotime($date)));
						$leave = $this->ews->check_employee_leave_application($date, $this->emp_id);
						$leave_check = ($leave) ? TRUE: FALSE;
						if(($work_schedule_info['work_schedule']['shift_name']!= "") && (!$leave_check)){
						    $time_in = check_missed_punches($date, $this->emp_id);
							if($time_in){ 
								if(!$rest_day && !$check_holiday){
									if($time_in->time_in == NULL) $count = $count + 1;
									if(!$rest_day && !$check_holiday){
										if($time_in->time_in == NULL) $count = $count + 1;
										if($work_schedule_info['work_schedule']['break_time'] != 0){
											if($time_in->lunch_out == NULL)  $count = $count + 1;
											if($time_in->lunch_in == NULL)  $count = $count + 1;
										}
									}
									if($time_in->time_out == NULL)  $count = $count + 1;
								}
								
								if($count > 0){
									/*$temp = array(
											"halfday" => $time_in->flag_halfday,
											"time_in_id" => $time_in->employee_time_in_id,
											"time_in_status" => $time_in->time_in_status,
											"date" => date("M j, Y",strtotime($time_in->time_in)),
											"time_in" => ($time_in->time_in == NULL) ? "0" : $time_in->time_in,
											"lunch_out" => ($time_in->lunch_out == NULL) ? "0" : $time_in->lunch_out,
											"lunch_in" => ($time_in->lunch_in == NULL) ? "0" : $time_in->lunch_in,
											"time_out" => ($time_in->time_out == NULL) ? "0" : $time_in->time_out,
											"tardiness" => (($time_in->tardiness_min != "") || $time_in->tardiness_min!= NULL) ? number_format($time_in->tardiness_min,2,'.',',') : '0.00',
											"undertime" => (($time_in->undertime_min != "") || $time_in->undertime_min!= NULL) ? number_format($time_in->undertime_min,2,'.',',') : '0.00',
											"hours" => $time_in->total_hours_required
										);*/
								    
								    
    								    $temp_arr = array(
    								        'date' 									=> $time_in->date,
    								        'time_in_date' 							=> ($time_in->time_in) ? date('d-M-y', strtotime($time_in->time_in)) : "",
    								        'lunch_in_date'							=> ($time_in->lunch_in) ? date('d-M-y', strtotime($time_in->lunch_in)) : "",
    								        'lunch_out_date' 						=> ($time_in->lunch_out) ? date('d-M-y', strtotime($time_in->lunch_out)) : "",
    								        'time_out_date' 						=> ($time_in->time_out) ? date('d-M-y', strtotime($time_in->time_out)) : "",
    								        'time_in' 								=> ($time_in->time_in) ? date('h:i a', strtotime($time_in->time_in)) : "",
    								        'lunch_in' 								=> ($time_in->lunch_in) ? date('h:i a', strtotime($time_in->lunch_in)) : "",
    								        'lunch_out' 							=> ($time_in->lunch_out) ? date('h:i a', strtotime($time_in->lunch_out)) : "",
    								        'time_out' 								=> ($time_in->time_out) ? date('h:i a', strtotime($time_in->time_out)) : "",
    								        'company_id' 							=> $time_in->company_id,
    								        'employee_time_in_id' 					=> $time_in->employee_time_in_id,
    								        'corrected' 							=> $time_in->corrected,
    								        'reason' 								=> $time_in->reason,
    								        'work_schedule_id' 						=> $time_in->work_schedule_id,
    								        'time_in_status' 						=> $time_in->time_in_status,
    								        'work_type_name' 						=> $time_in->work_type_name,
    								        'tardiness_min' 						=> $time_in->tardiness_min,
    								        'undertime_min'	 						=> $time_in->undertime_min,
    								        'time_inId' 							=> $time_in->time_inId,
    								        'location_1' 							=> $time_in->location_1,
    								        'total_hours' 							=> $time_in->total_hours,
    								        'total_hours_required' 					=> $time_in->total_hours_required,
    								        'work_sched_name' 						=> $time_in->work_sched_name,
    								        'flag_halfday' 							=> $time_in->flag_halfday,
    								        'notes' 								=> $time_in->notes,
    								        'locationBasedApp' 						=> $time_in->locationBasedApp,
    								        'add_logs_approval_grp' 				=> $time_in->add_logs_approval_grp,
    								        'attendance_adjustment_approval_grp'	=> $time_in->attendance_adjustment_approval_grp,
    								        'source' 								=> $time_in->source,
    								        'last_source' 							=> $time_in->last_source,
    								        'location' 								=> $time_in->location,
    								        'break_rules' 							=> $time_in->break_rules,
    								        'name' 									=> $time_in->name,
    								        'absent_min' 							=> $time_in->absent_min,
    								        'late_min' 								=> $time_in->late_min,
    								        'overbreak_min' 						=> $time_in->overbreak_min,
    								        'break_rules' 							=> $time_in->break_rules
    								    );
									
    								    array_push($final, $temp_arr);
								}
							}
						}
					}
				}
				echo json_encode(array("result" => "1", "list" => $final));
				return false;
			} else {
			    echo json_encode(array("result" => "0"));
			    return false;
			}
		}
	}
	
}