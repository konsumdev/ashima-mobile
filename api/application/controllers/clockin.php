<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Clockin extends CI_Controller{
	public function __construct(){
		parent::__construct();
		$this->load->model('employee_model','employee');
		$this->load->model('emp_login_model','elm');
		$this->load->model('login_screen_model');
		
		$this->emp_id = $this->session->userdata('emp_id');
		$this->company_id =$this->employee->check_company_id($this->emp_id);
	}
	
	public function index(){
		$data['emp_no'] = $this->employee->check_emp_no($this->emp_id,$this->company_id);
			
		$currentdate = date('Y-m-d');
		$vx = $this->elm->activate_nightmare_trap($this->company_id,$data['emp_no']);
		
		if($vx){
			$currentdate = $vx['currentdate'];
		}
		
		$data['currentdate'] = $currentdate;
		
		$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$currentdate);
		$your_shifts_now = $this->employee->get_current_shift($work_schedule_id, $this->company_id,$currentdate);
		#p($your_shifts_now);
		$time_list_box = $this->elm->get_time_list($data['emp_no'],$work_schedule_id,$this->company_id,$currentdate);
		
		$time_ins = $this->employee_timein_now($this->emp_id,$this->company_id,$data['emp_no']);
		
		if($time_ins) {
			$your_shifts_now = false;
		}
		
		if($your_shifts_now) {
			$res = array();
			
			foreach ($your_shifts_now as $shift) {
				$curent_time = strtotime(date('Y-m-d H:i:s'));
				$start_shift = strtotime(date('Y-m-d H:i:s', strtotime($currentdate.' '.$shift->start_thres)));
				
				if($curent_time < $start_shift) {
					$current_mins = round(abs($curent_time - $start_shift) / 60);
				} else {
					$current_mins = 0;
				}
				
				$res = array(
						"start" => date('h:i a', strtotime($shift->start)),
						"end" => date('h:i a', strtotime($shift->end)),
						"current_mins" => $current_mins, //date('Y-m-d H:i:s')
						"current_date" => date('Y-m-d H:i:s', strtotime($currentdate.' '.$shift->start_thres)).' '.date('Y-m-d H:i:s')
				);
			}
			
			$clock_in = array(
					"your_shift" => $res,
					"timesheet" => false
			);
			
			echo json_encode($clock_in);
		} else {
			if($time_ins) {
				if($time_list_box) {
					$res = array(
							"timein_date" 	=> ($time_list_box->time_in) ? date('d-M-y', strtotime($time_list_box->time_in)) : null,
							"timein_time" 	=> ($time_list_box->time_in) ? date('h:i a', strtotime($time_list_box->time_in)) : null,
							"msg1"			=> "Do you want to take a break?",
							"lunchout_date"	=> ($time_list_box->lunch_out) ? date('d-M-y', strtotime($time_list_box->lunch_out)) : null,
							"lunchout_time" => ($time_list_box->lunch_out) ? date('h:i a', strtotime($time_list_box->lunch_out)) : null,
							"msg2"			=> "You are currently on break.",
							"msg21"			=> "Ready to go back to work?.",
							"lunchin_date" 	=> ($time_list_box->lunch_in) ? date('d-M-y', strtotime($time_list_box->lunch_in)) : null,
							"lunchin_time" 	=> ($time_list_box->lunch_in) ? date('h:i a', strtotime($time_list_box->lunch_in)) : null,
							"msg3"			=> "Your shift has ended.",
							"msg31"			=> "Clock out now?",
							"timeout_date" 	=> ($time_list_box->time_out) ? date('d-M-y', strtotime($time_list_box->time_out)) : null,
							"timeout_time" 	=> ($time_list_box->time_out) ? date('h:i a', strtotime($time_list_box->time_out)) : null,
					);
				
					$clock_in = array(
							"your_shift" => false,
							"timesheet" => $res
					);
				
					echo json_encode($clock_in);
				} else {
					$clock_in = array(
							"your_shift" => false,
							"timesheet" => false
					);
				
					echo json_encode($clock_in);
				}
			} else {
				
				$clock_in = array(
						"your_shift" => false,
						"timesheet" => false
				);
				
				echo json_encode($clock_in);
				
			}
		}
		
    }

    public function get_latest_time_in() {
        $sel = array(
            "time_in",
            "lunch_out",
            "lunch_in",
            "time_out",
            "change_log_time_in",
            "change_log_lunch_out",
            "change_log_lunch_in",
            "change_log_time_out",
            "time_in_status"
        );
        $date = date("Y-m-d");
        $where = array(
            "emp_id" => $this->emp_id,
            "comp_id" => $this->company_id,
            "status" => "Active",
            "time_in_status !=" => "reject",
            "date" => $date
        );
        $this->db->select($sel);
        $this->db->where($where);
        $q = $this->db->get("employee_time_in");
        $r = $q->row();
        $row = $r;
        if ($row) {
            $timein = $row->time_in;
            $lunchin = $row->lunch_in;
            $lunchout = $row->lunch_out;
            $timeout = $row->time_out;
            if ($row->time_in_status == "pending") {
                $timein = $row->change_log_time_in;
                $lunchin = $row->change_log_lunch_in;
                $lunchout = $row->change_log_lunch_out;
                $timeout = $row->change_log_time_out;
            }
            
            echo json_encode(array(
                'timein_time' => date('h:i a', strtotime($timein)),
                'lunchin_time' => date('h:i a', strtotime($lunchin)),
                'lunchout_time' => date('h:i a', strtotime($lunchout)),
                'timeout_time' => date('h:i a', strtotime($timeout))
            ));
        }
        return false;
    }
    
    public function get_server_date() {
        echo json_encode(array("date"=>date("Y-m-d H:i:s")));
    }

	public function employee_current_shift()
	{
		$today = date("Y-m-d");
		$work_schedule_ids = $this->employee->emp_work_schedule_v2($this->emp_id,$this->company_id,$today);
		if ($work_schedule_ids === FALSE) {
			$clock_in = array(
					"your_shift" => false,
					"timesheet" => false,
					"msg" => 'no work schedule id'
			);
			
			echo json_encode($clock_in);
		}

		// Get current day shift
		$currentdatews = in_array_custom($today, $work_schedule_ids);
		$currentdate = $currentdatews->date;
		$work_schedule_id = $currentdatews->work_schedule_id;
		$employee_shift = $this->employee->get_current_shift($work_schedule_id, $this->company_id,$currentdate);

		if ($employee_shift === FALSE) {
			// Get yesterday shift
			$today = date("Y-m-d", strtotime("-1 day"));
			$currentdatews = in_array_custom($today, $work_schedule_ids);
			$currentdate = $currentdatews->date;
			$work_schedule_id = $currentdatews->work_schedule_id;
			$employee_shift = $this->employee->get_current_shift($work_schedule_id, $this->company_id,$currentdate);

			if ($employee_shift === FALSE) {

				$clock_in = array(
						"your_shift" => false,
						"timesheet" => false,
						"msg" => 'no shift'
				);
				
				echo json_encode($clock_in);
			}
		}

		$tresh = '';
		if (isset($shift->start_thres)) {
			$tresh = $shift->start_thres;
		}

		foreach ($employee_shift as $shift) {
			$curent_time = strtotime(date('Y-m-d H:i:s'));
			$start_shift = strtotime(date('Y-m-d H:i:s', strtotime($currentdate.' '.$tresh)));
			
			if($curent_time < $start_shift) {
				$current_mins = round(abs($curent_time - $start_shift) / 60);
			} else {
				$current_mins = 0;
			}
			
			$res = array(
					"start" => date('h:i a', strtotime($shift->start)),
					"end" => date('h:i a', strtotime($shift->end)),
					"current_mins" => $current_mins, //date('Y-m-d H:i:s')
					"current_date" => date('Y-m-d H:i:s', strtotime($currentdate.' '.$tresh)).' '.date('Y-m-d H:i:s')
			);
		}
		
		$clock_in = array(
				"your_shift" => $res,
				"timesheet" => false
		);
		
		echo json_encode($clock_in);
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
		$date = date('Y-m-d H:i:s');
		$vx = $this->elm->activate_nightmare_trap($comp_id,$emp_no);
		$currentdate = date('Y-m-d');
		if($vx){
			$currentdate = $vx['currentdate'];
		
		}
		
		#$currentdate = $currentdate;
		
		
		$get_timein_now_for_btn = $this->employee->get_timein_now_for_btn($emp_no,$this->company_id);
		
		
		#$current_date = date("Y-m-d H:i:s");
		#$date = $currentdate;
		if($get_timein_now_for_btn) {
			$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$currentdate);
			
			if(!$get_timein_now_for_btn){
					
				if($work_schedule_id == null){
					$work_schedule_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$get_timein_now_for_btn->date);
				}
				$get_timein_now_for_btn = (object) array(
						'work_schedule_id' => $work_schedule_id,
						'time_in' => date('Y-m-d H:i:s'),
						'date' => date('Y-m-d')
				);
					
			}
				
			$check_work_type = $this->employee->work_schedule_type($work_schedule_id, $this->company_id);
			
			if($check_work_type == "Uniform Working Days") {
				if(!$vx) {
					$change_date = date('Y-m-d',strtotime($get_timein_now_for_btn->date." +1 day"));
						
					$workday = date('l',strtotime($change_date));
			
					$work_schedule_id_go = $this->elm->get_tomorrow_shift($this->emp_id, $this->company_id, $change_date);
						
					$workday_settings_start_time_me = $this->elm->check_workday_settings_start_time($workday,$work_schedule_id_go,$this->company_id);
					
					$tomorrow_startime = date('Y-m-d H:i:s',strtotime($change_date. " ".$workday_settings_start_time_me));
					$tomorrow_startime = date('Y-m-d H:i:s',strtotime($tomorrow_startime) - 120 * 60);
					
				} else {
					if($get_timein_now_for_btn->date == $vx['currentdate']){
						$change_date = date('Y-m-d',strtotime($vx['currentdate']." +1 day"));
					}else{
						$change_date = date('Y-m-d',strtotime($vx['currentdate']." -1 day"));
					}
												
					$workday = date('l',strtotime($change_date));
			
					$work_schedule_id_go = $this->elm->get_tomorrow_shift($this->emp_id, $this->company_id, $change_date);
					$workday_settings_start_time_me = $this->elm->check_workday_settings_start_time($workday,$work_schedule_id_go,$this->company_id);
			
					$tomorrow_startime = date('Y-m-d H:i:s',strtotime($change_date. " ".$workday_settings_start_time_me));
					$tomorrow_startime = date('Y-m-d H:i:s',strtotime($tomorrow_startime) - 120 * 60);
				}
			
				$your_date = date('Y-m-d');
				$datediff = strtotime($get_timein_now_for_btn->date) - strtotime($your_date);
				$count_date = floor($datediff/(60*60*24));
			
				#if($count_date == 0){
					if($workday_settings_start_time_me){
						$workday1 = date('l',strtotime($get_timein_now_for_btn->date));
						#$ws1 = $this->elm->get_tomorrow_shift($this->emp_id, $this->company_id, $change_date);
						$ws1 = $this->elm->get_tomorrow_shift($this->emp_id, $this->company_id, $currentdate);
						$wst1 = $this->elm->check_workday_settings_start_time($workday1,$ws1, $this->company_id);
			
						if($workday_settings_start_time_me !="" && $wst1 !=""){
							$st = date('Y-m-d H:i:s',strtotime($get_timein_now_for_btn->date. " ". $wst1));
							$st = date('Y-m-d H:i:s',strtotime($st) - 120 * 60);
			
							$row = $this->elm->get_default_schedule($this->emp_id, $this->company_id,"regular",$work_schedule_id,$date);
							
							if(($date >= $st && $date <=$tomorrow_startime)){
								#return false;
								#$display_time_out = true;
								
								$where = array(
										'date'=> $get_timein_now_for_btn->date,
										'emp_id' =>$emp_id,
										'status'=> 'Active'
								);
								$this->db->where($where);
								$this->db->order_by("time_in","DESC");
								$q = $this->db->get('employee_time_in');
								$r = $q->row();
								return ($r) ? $r : FALSE;
							}else{
								#Scene 6: if user schedule 12am - 4am
								#$display_time_out = false;
								
								$date_st = date('Y-m-d');
								if($wst1 == "00:00:00") {
									$date_st = date('Y-m-d', strtotime($date_st.' +1 day'));
								}
								
								$new_st = date('Y-m-d H:i:s',strtotime($date_st. " ". $wst1));
								$st1 = date('Y-m-d H:i:s',strtotime($new_st.' -2 hours'));
								
								if(($date >= $st1 && $date <= $new_st)) {
									$where = array(
											'date'=> date('Y-m-d', strtotime($date.' +1 day')),
											'emp_id' =>$emp_id,
											'status'=> 'Active'
									);
									$this->db->where($where);
									$this->db->order_by("time_in","DESC");
									$q = $this->db->get('employee_time_in');
									$r = $q->row();
									return ($r) ? $r : FALSE;
								}
								
								$where = array(
								    'date'=> date('Y-m-d', strtotime($date)),
								    'emp_id' =>$emp_id,
								    'status'=> 'Active'
								);
								$this->db->where($where);
								$this->db->order_by("time_in","DESC");
								$q = $this->db->get('employee_time_in');
								
								$r = $q->row();
								return ($r) ? $r : FALSE;
							}
						}else{
								
							#Scen 5: if user has a timeout
							#$display_time_out = false;
							
						    if($get_timein_now_for_btn->time_out == "") {
						        $where = array(
						            'date'=> date('Y-m-d', strtotime($get_timein_now_for_btn->date)),
						            'emp_id' =>$emp_id,
						            'status'=> 'Active'
						        );
						        $this->db->where($where);
						        $this->db->order_by("time_in","DESC");
						        $q = $this->db->get('employee_time_in');
						        $r = $q->row();
						        return ($r) ? $r : FALSE;
						    } else {
						        $where = array(
						            'date'=> date('Y-m-d', strtotime($date)),
						            'emp_id' =>$emp_id,
						            'status'=> 'Active'
						        );
						        $this->db->where($where);
						        $this->db->order_by("time_in","DESC");
						        $q = $this->db->get('employee_time_in');
						        $r = $q->row();
						        return ($r) ? $r : FALSE;
						    }
							
							/*$where = array(
									'date'=> date('Y-m-d', strtotime($date)),
									'emp_id' =>$emp_id,
									'status'=> 'Active'
							);
							$this->db->where($where);
							$this->db->order_by("time_in","DESC");
							$q = $this->db->get('employee_time_in');
							$r = $q->row();
							return ($r) ? $r : FALSE;*/
						}
					}else{
						# if next schedule is empty
						# if user time in in a next day (night shift schedule)
						if($vx){
						    $fritz_current_date = date('Y-m-d', strtotime($date));
						    $change_date = date('Y-m-d',strtotime($fritz_current_date." -1 day"));
						    
						    #night shift 12am
						    if(strtotime($get_timein_now_for_btn->date) == strtotime(date('Y-m-d',strtotime($fritz_current_date." +1 day")))) {
						        $where = array(
						            'date'=> $get_timein_now_for_btn->date,
						            'emp_id' =>$emp_id,
						            'status'=> 'Active'
						        );
						        $this->db->where($where);
						        $this->db->order_by("time_in","DESC");
						        $q = $this->db->get('employee_time_in');
						        $r = $q->row();
						        return ($r) ? $r : FALSE;
						    }
						}else
							$change_date = date('Y-m-d', strtotime($currentdate));
			
							$workday = date('l',strtotime($change_date));
							$work_schedule_id_go = $this->elm->get_tomorrow_shift($this->emp_id, $this->company_id, $change_date);
							$workday_settings_start_time_me = $this->elm->check_workday_settings_start_time($workday,$work_schedule_id_go,$this->company_id);
			
							$tomorrow_startime = date('Y-m-d H:i:s',strtotime($change_date. " ".$workday_settings_start_time_me));
							$tomorrow_startime = date('Y-m-d H:i:s',strtotime($tomorrow_startime) - 120 * 60);
			
							$work_schedule_id_go2 = $this->elm->get_tomorrow_shift($this->emp_id, $this->company_id, $change_date);
							$workday_settings_end_time_me = $this->elm->check_workday_settings_end_time($workday,$work_schedule_id_go2,$this->company_id);
			
							$tomorrow_endtime = date('Y-m-d H:i:s',strtotime($change_date. " ".$workday_settings_end_time_me));
							$tomorrow_endtime = date('Y-m-d H:i:s',strtotime($tomorrow_endtime) + 600 * 60);
			
				
							if(date('A',strtotime($workday_settings_start_time_me)) == "PM" &&  date('A',strtotime($workday_settings_end_time_me)) == "AM"){
								$change_date = date('Y-m-d',strtotime($date." +1 day"));
								$tomorrow_endtime = date('Y-m-d H:i:s',strtotime($change_date. " ".$workday_settings_end_time_me));
												
							}
			
							# Scen 6: check if currentdate is within the start time and end time
							# of today schedule
							
							if($date >= $tomorrow_startime && $date <= $tomorrow_endtime && $get_timein_now_for_btn->date == date('Y-m-d', strtotime($currentdate))){
								#$display_time_out = true;
								
								$where = array(
										'date'=> $get_timein_now_for_btn->date,
										'emp_id' =>$emp_id,
										'status'=> 'Active'
								);
								$this->db->where($where);
								$this->db->order_by("time_in","DESC");
								$q = $this->db->get('employee_time_in');
								$r = $q->row();
								return ($r) ? $r : FALSE;
							}else{
								#$display_time_out = false;
								$check_rest_day = $this->elm->check_rest_day(date("l",strtotime(date('Y-m-d', strtotime($date)))),$work_schedule_id,$this->company_id);
								
								if($check_rest_day) {
									if($get_timein_now_for_btn->time_out == "") {
										$where = array(
												'date'=> $get_timein_now_for_btn->date,
        										    'emp_id' =>$emp_id,
        										    'status'=> 'Active'
										);
										$this->db->where($where);
										$this->db->order_by("time_in","DESC");
										$q = $this->db->get('employee_time_in');
										$r = $q->row();
										return ($r) ? $r : FALSE;
									} else {
										$where = array(
												'date'=> date('Y-m-d', strtotime($date)),
												'emp_id' =>$emp_id,
												'status'=> 'Active'
										);
										$this->db->where($where);
										$this->db->order_by("time_in","DESC");
										$q = $this->db->get('employee_time_in');
										$r = $q->row();
										return ($r) ? $r : FALSE;
									}
								} else {
									$where = array(
											'date'=> date('Y-m-d', strtotime($date)),
											'emp_id' =>$emp_id,
											'status'=> 'Active'
									);
									$this->db->where($where);
									$this->db->order_by("time_in","DESC");
									$q = $this->db->get('employee_time_in');
									$r = $q->row();
									return ($r) ? $r : FALSE;
								}
								
							}
						}
				#}
			}else if($check_work_type == "Flexible Hours"){
				$datediff = strtotime($date) - strtotime($get_timein_now_for_btn->time_in);
				$length =  floor($datediff/(60*60*24));
				
				if($length >1 && $get_timein_now_for_btn->time_in == ""){
				
					#return false;			
					#$this->employee->get_time_ins(date('Y-m-d', strtotime($date)),$emp_id);
					
					$where = array(
							'date'=> date('Y-m-d', strtotime($date)),
							'emp_id' =>$emp_id,
							'status'=> 'Active'
					);
					$this->db->where($where);
					$this->db->order_by("time_in","DESC");
					$q = $this->db->get('employee_time_in');
					$r = $q->row();
					return ($r) ? $r : FALSE;
				}
			
				$m = date('H:i:s',strtotime($get_timein_now_for_btn->time_in));
				$current = date('Y-m-d H:i:s',strtotime($m));					
				$rx2 = date('Y-m-d H:i:s', strtotime($get_timein_now_for_btn->time_in) - 120 * 60);
				$one_day = date('Y-m-d H:i:s',strtotime($rx2. " +1 day"));
				
				#p($get_timein_now_for_btn);
				#echo $date.'@'.$get_timein_now_for_btn->time_in.'@'.date('Y-m-d', strtotime($date)).'@'.$one_day;exit();
				
				if($date >= $get_timein_now_for_btn->time_in &&  $date <= $one_day){
					#return true;
					if($get_timein_now_for_btn->time_out == "") {
						$where = array(
								'date'=> $get_timein_now_for_btn->date,
								'emp_id' =>$emp_id,
								'status'=> 'Active'
						);
						$this->db->where($where);
						$this->db->order_by("time_in","DESC");
						$q = $this->db->get('employee_time_in');
						$r = $q->row();
						return ($r) ? $r : FALSE;
					} else {
						$where = array(
								'date'=> date('Y-m-d', strtotime($date)),
								'emp_id' =>$emp_id,
								'status'=> 'Active'
						);
						$this->db->where($where);
						$this->db->order_by("time_in","DESC");
						$q = $this->db->get('employee_time_in');
						$r = $q->row();
						return ($r) ? $r : FALSE;
					}
					
				}else{
						
					#return false;
					$where = array(
							'date'=> date('Y-m-d', strtotime($date)),
							'emp_id' =>$emp_id,
							'status'=> 'Active'
					);
					$this->db->where($where);
					$this->db->order_by("time_in","DESC");
					$q = $this->db->get('employee_time_in');
					$r = $q->row();
					return ($r) ? $r : FALSE;
				}
				
			} elseif ($check_work_type == "Workshift") {
			    
			    $date = date('Y-m-d H:i:s');
			    $vx = $this->elm->activate_nightmare_trap($comp_id,$emp_no);
			    $currentdate = date('Y-m-d');
			    if($vx){
			        $currentdate = $vx['currentdate'];
			        
			    }
			    
			    $work_sched_id = $this->employee->emp_work_schedule($this->emp_id,$this->company_id,$currentdate);
			    $current_shift = $this->employee->get_current_shift($work_sched_id, $this->company_id,$currentdate);
			    
			    $start = "";
			    if ($current_shift) {
			        foreach ($current_shift as $zz) {
			            $start = date('Y-m-d', strtotime($currentdate)).' '.date('H:i:s', strtotime($zz->start));
			        }
			    }
			    
			    $current_datetime = date('Y-m-d H:i:s');
			    $start_date = date('Y-m-d H:i:s', strtotime($start." -120 minutes"));
			    
			    if($get_timein_now_for_btn->time_out == "") {
			        #if(strtotime($start) >= strtotime($start_date)) {
			        $where = array(
			            #'date'=> date('Y-m-d', strtotime($date)),
			            'date'=> $get_timein_now_for_btn->date,
			            'emp_id' =>$emp_id,
			            'status'=> 'Active'
			        );
			        $this->db->where($where);
			        $this->db->order_by("time_in","DESC");
			        $q = $this->db->get('schedule_blocks_time_in');
			        $r = $q->row();
			        
			        return ($r) ? $r : FALSE;
			        /*} elseif(strtotime($current_datetime) > strtotime($start_date)) {
			         $where = array(
			         'date'=> date('Y-m-d', strtotime($date)),
			         'emp_id' =>$emp_id
			         );
			         $this->db->where($where);
			         $this->db->order_by("time_in","DESC");
			         $q = $this->db->get('schedule_blocks_time_in');
			         $r = $q->row();
			         
			         return ($r) ? $r : FALSE;
			         }*/
			    } else {
			        #if(strtotime($current_datetime) > strtotime($start_date)) {
			        if(strtotime($start) <= strtotime($start_date)) {
			            $where = array(
			                'date'=> date('Y-m-d', strtotime($date)),
			                'emp_id' =>$emp_id,
			                'status'=> 'Active'
			            );
			            $this->db->where($where);
			            $this->db->order_by("time_in","DESC");
			            $q = $this->db->get('schedule_blocks_time_in');
			            $r = $q->row();
			            
			            return ($r) ? $r : FALSE;
			        }
			    }
			    
			    
			    
			}
		}
	}
}