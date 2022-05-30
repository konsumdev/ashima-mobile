<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Employee Model
 *
 * @category Model
 * @version 3.0
 * @author John Fritz Marquez <fritzified.gamer@gmail.com>
 * @version author 47 <asilomkeith@gmail.com>
 */

class Employee_mobile_model extends CI_Model {
	public function get_timesheet_list_mobile($emp_id, $company_id, $num_rows=false, $status = "", $page="", $limit="") {
		
		if($status) {
			$where = array(
					'et.emp_id'			=> $emp_id,
					'et.comp_id'		=> $company_id,
					//'MONTH(et.date)'	=> $viewMonth,
					//'YEAR(et.date)' 	=> $viewYear,
					'et.status'			=> 'Active',
					'et.time_in_status'	=> $status
			);
		} else {
			$where = array(
					'et.emp_id'			=> $emp_id,
					'et.comp_id'		=> $company_id,
					//'MONTH(et.date)'	=> $viewMonth,
					//'YEAR(et.date)' 	=> $viewYear,
					'et.status'			=> 'Active'
			);
		}
		
		
		$select = array(
                #'*',
                'et.emp_id',
				'et.date AS date',
				'et.time_in AS time_in',
				'et.lunch_in AS lunch_in',
				'et.lunch_out AS lunch_out',
				'et.comp_id AS company_id',
				'et.employee_time_in_id AS employee_time_in_id',
				'et.corrected AS corrected',
				'et.reason AS reason',
				'et.work_schedule_id AS work_schedule_id',
				'et.time_in_status AS time_in_status',
				'ws.work_type_name AS work_type_name',
				'et.tardiness_min AS tardiness_min',
				'et.undertime_min AS undertime_min',
				'et.employee_time_in_id AS time_inId',
				'et.time_out AS time_out',
				'et.location_1 AS location_1',
				'et.total_hours_required AS total_hours',
				'et.total_hours AS total_hours_required',
				'ws.name AS work_sched_name',
				'et.flag_halfday AS flag_halfday',
				'et.notes AS notes',
				'epi.location_base_login_approval_grp AS locationBasedApp',
				'epi.add_logs_approval_grp AS add_logs_approval_grp',
				'epi.attendance_adjustment_approval_grp AS attendance_adjustment_approval_grp',
				'et.source',
				'et.last_source',
				'et.location',
				'ws.break_rules',
				'ws.name',
				'et.absent_min',
				'et.late_min',
				'et.overbreak_min',
				'ws.break_rules',
				'et.change_log_date_filed',
				'et.break1_out',
				'et.break1_in',
				'et.break2_out',
				'et.break2_in',
				'enable_lunch_break',
				'track_break_1',
				'track_break_2',
				'enable_additional_breaks',
                'num_of_additional_breaks',
                'break_schedule_1',
                'break_started_after',
                'et.work_schedule_id',
                'et.rest_day_r_a',
                'et.inclusion_hours',
                'et.holiday_approve',
                'et.location_2',
                'et.location_3',
                'et.location_4',
                'et.location_5',
                'et.location_6',
                'et.location_7',
                'et.location_8'
		);
		
		$this->db->select($select);
		$this->db->where($where);
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = et.emp_id","LEFT");
		$this->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$this->edb->join("work_schedule AS ws","ws.work_schedule_id = et.work_schedule_id","LEFT");
		$this->db->order_by('et.date desc, et.time_in_status asc');
		
		if($num_rows == true) {
			$q = $this->db->get('employee_time_in AS et');
			return $q->num_rows();
		} else {
			$q = $this->db->get('employee_time_in AS et',$limit,$page);
			$r = $q->result();
			
			if($r) {
				$res = array();
                $is_migrated = for_list_tardiness_rule_migrated_v3($company_id);
                $emp_ids = array();
                array_push($emp_ids, $emp_id);

                foreach($r as $app) {
                    $range[] = $app->date;
                }
                sort($range);
                $range_count 						= count($range);

                if($range_count){
                    $min_range 						= $range[0];
                    $max_range 						= $range[$range_count-1];
                    $max_range 						= date("Y-m-d",strtotime($max_range. " +1 day"));
                }

                $all_change_schedule = $this->get_all_change_shifts($company_id, $emp_ids, $max_range, $min_range);
                $leaves = $this->check_employee_leave_applicationv2($company_id, $emp_ids, $min_range, $max_range);
                $holidays= $this->check_is_date_holidayv2($company_id,$min_range,$max_range);
				foreach ($r as $row) {
                    $is_holiday = in_array_custom("date-{$row->date}",$holidays);
                    $is_hol = false;
                    $holiday_class = '';
                    $hol_name = '';
                    $hol_type = '';

                    // holiday rd color
                    if($is_holiday){
                	    if($is_holiday->repeat_type == "no"){
                	        $cur_year = date("Y");
                	        $hol_year = date("Y",strtotime($row->date));
                	        
                	        if($cur_year == $hol_year){
                	            $is_hol = true;
                	        }
                	        else{
                	            $is_hol = false;
                	        }
                	    }
                	    else{
                	        $is_hol = true;
                        }
                        $hol_name = $is_holiday->holiday_name;
                        $hol_type = $is_holiday->hour_type_name;
                    }

                    if($is_hol){
                	    if($row->work_schedule_id == "-1") {
                	        $holiday_class = 'hours-border-rd';
                	    }
                	    elseif($is_holiday->hour_type_name == "Special Holiday") {
                	        $holiday_class = 'hours-border-sp';
                	    } elseif ($is_holiday->hour_type_name == "Regular Holiday") {
                	        $holiday_class = 'hours-border-rg';
                        }                        
                	} elseif($row->work_schedule_id == "-1" || $row->rest_day_r_a == "yes") {
                	    $holiday_class = 'hours-border-rd';
                    }
                    
                    if($row->inclusion_hours == "1"){
                		$holiday_class = 'hours-border-mn';
                	}
                    

                    $color_warning 		= false;
                    if($all_change_schedule){
                        $check_if_change 	= in_array_custom("change_shift_{$row->emp_id}_{$row->date}",$all_change_schedule);

                        if($check_if_change){
                            if($check_if_change->updated_date >= $row->time_in){
                                $color_warning 		= true;
                            }
                        }
                    }

                    $is_resolved = false;
                    $date_c 			= $row->date;
                    $c_date 			= date("Y-m-d");
                    $check_employee_leave_application = in_array_custom($row->date."-".$row->emp_id,$leaves);
                    if($date_c != $c_date){
                        if($check_employee_leave_application){
                            if($check_employee_leave_application->credited != NULL && $check_employee_leave_application->credited != ""){
                                $is_resolved = true;
                            }
                        }
                    }

                    $ti = ($row->time_in) ? date("Y-m-d H:i:s", strtotime($row->time_in)) : '';
                    $th = ($row->total_hours) ? intval($row->total_hours) : 0;
                    $suppose_timeout = ($row->time_in) ? date("Y-m-d H:i:s", strtotime($ti.' +'.$th.' hours')) : '';
                    $shift_has_ended = (date("Y-m-d H:i:s") >= $suppose_timeout) ? true : false;
                    $loc = ($row->location_1 && $row->location_1 != 'undefined') ? $row->location_1 . " | " : "";
                    if ($row->location_2 && $row->location_2 != 'undefined') {
                    	$loc = $loc . $row->location_2 . " | ";
                    }
                    if ($row->location_3 && $row->location_3 != 'undefined') {
                    	$loc = $loc . $row->location_3 . " | ";
                    }
                    if ($row->location_4 && $row->location_4 != 'undefined') {
                    	$loc = $loc . $row->location_4 . " | ";
                    }
                    if ($row->location_5 && $row->location_5 != 'undefined') {
                    	$loc = $loc . $row->location_5 . " | ";
                    }
                    if ($row->location_6 && $row->location_6 != 'undefined') {
                    	$loc = $loc . $row->location_6 . " | ";
                    }
                    if ($row->location_7 && $row->location_8 != 'undefined') {
                    	$loc = $loc . $row->location_7 . " | ";
                    }
                    if ($row->location_8 && $row->location_8 != 'undefined') {
                    	$loc = $loc . $row->location_8;
                    }
					$temp_arr = array(
							'date' 									=> $row->date,
							'time_in_date' 							=> ($row->time_in) ? date('d-M-y', strtotime($row->time_in)) : "",
							'lunch_in_date'							=> ($row->lunch_in) ? date('d-M-y', strtotime($row->lunch_in)) : "",
							'lunch_out_date' 						=> ($row->lunch_out) ? date('d-M-y', strtotime($row->lunch_out)) : "",
							'time_out_date' 						=> ($row->time_out) ? date('d-M-y', strtotime($row->time_out)) : "",
							'time_in' 								=> ($row->time_in) ? date('h:i a', strtotime($row->time_in)) : "",
							'lunch_in' 								=> ($row->lunch_in) ? date('h:i a', strtotime($row->lunch_in)) : "",
							'lunch_out' 							=> ($row->lunch_out) ? date('h:i a', strtotime($row->lunch_out)) : "",
							'time_out' 								=> ($row->time_out) ? date('h:i a', strtotime($row->time_out)) : "",
							'company_id' 							=> $row->company_id,
							'employee_time_in_id' 					=> $row->employee_time_in_id,
							'corrected' 							=> $row->corrected,
							'reason' 								=> $row->reason,
							'work_schedule_id' 						=> $row->work_schedule_id,
							'time_in_status' 						=> $row->time_in_status,
							'work_type_name' 						=> $row->work_type_name,
							'tardiness_min' 						=> $row->tardiness_min,
							'undertime_min'	 						=> $row->undertime_min,
							'time_inId' 							=> $row->time_inId,
							'location_1' 							=> $row->location_1,
							'total_hours' 							=> $row->total_hours,
							'total_hours_required' 					=> $row->total_hours_required,
							'work_sched_name' 						=> $row->work_sched_name,
							'flag_halfday' 							=> $row->flag_halfday,
							'notes' 								=> $row->notes,
							'locationBasedApp' 						=> $row->locationBasedApp,
							'add_logs_approval_grp' 				=> $row->add_logs_approval_grp,
							'attendance_adjustment_approval_grp'	=> $row->attendance_adjustment_approval_grp,
							'source' 								=> $row->source,
							'last_source' 							=> $row->last_source,
							'location' 								=> $loc,
							'break_rules' 							=> $row->break_rules,
							'name' 									=> $row->name,
							'absent_min' 							=> $row->absent_min,
							'late_min' 								=> $row->late_min,
							'overbreak_min' 						=> $row->overbreak_min,
							'break_rules' 							=> $row->break_rules,
							'break1_out'							=> ($row->break1_out) ? date('h:i a', strtotime($row->break1_out)) : "",
							'break1_in'								=> ($row->break1_in) ? date('h:i a', strtotime($row->break1_in)) : "",
							'break2_out'							=> ($row->break2_out) ? date('h:i a', strtotime($row->break2_out)) : "",
							'break2_in'								=> ($row->break2_in) ? date('h:i a', strtotime($row->break2_in)) : "",
							'break1_out_date'						=> ($row->break1_out) ? date('d-M-y', strtotime($row->break1_out)) : "",
							'break1_in_date'						=> ($row->break1_in) ? date('d-M-y', strtotime($row->break1_in)) : "",
							'break2_out_date'						=> ($row->break2_out) ? date('d-M-y', strtotime($row->break2_out)) : "",
							'break2_in_date'						=> ($row->break2_in) ? date('d-M-y', strtotime($row->break2_in)) : "",
							'is_migrated'							=> $is_migrated,
							'enable_lunch_break'					=> $row->enable_lunch_break,
							'track_break_1'							=> $row->track_break_1,
                            'track_break_2'							=> $row->track_break_2,
                            'break_schedule_1'                      => $row->break_schedule_1,
                            'break_started_after'                   => $row->break_started_after,
                            "has_change_sched"                      => $color_warning,
                            "is_resolved"                           => $is_resolved,
                            "suppose_to"                            => $shift_has_ended,
                            "holiday_class"                         => $holiday_class,
                            "rest_day_r_a"                          => $row->rest_day_r_a,
                            "holiday_approve"                       => $row->holiday_approve,
                            "holiday_name"                          => $hol_name,
                            "holiday_type"                          => $hol_type,
                            "is_holiday"                            => $is_hol
					);
					
					array_push($res,$temp_arr);
				}
				
				return $res;
				
				
			}
			return ($r) ? $r : FALSE;
		}
    }

    public function employee_leaves_app($comp_id,$emp_ids="",$min="",$max=""){
		$row_array  = array();
		$s  = array(
			"credited",
			"shift_date",
			"DATE(date_start) AS date_start",
			"DATE(date_end) AS date_end",
			"emp_id"
			); 
		$w2 = array(
				"company_id" 				=> $comp_id,
				"status" 					=> "Active",
				"leave_application_status"	=> "approve"
		);

		$this->db->select($s);
		if($emp_ids){
			$this->db->where_in("emp_id",$emp_ids);
		}
		$this->db->where($w2);
		if($min && $max){
			$this->db->where("CAST(DATE(date_start) AS date) >= '".$min."' AND CAST(DATE(date_end) AS date) <= '".$max."'");
		}

		$q2 = $this->db->get("employee_leaves_application");

		$r2 = $q2->result();
		
		if($r2){
			foreach ($r2 AS $r){
				$wd = array(
						"emp_id"		=> $r->emp_id,
						"date_start"	=> $r->date_start,
						"date_end"		=> $r->date_end,
						"credited"		=> $r->credited,
						"custom_search"	=> "emps-{$r->emp_id}-{$r->shift_date}",
				);
				array_push($row_array,$wd);
			}
		}
		return $row_array;
	}

    public function check_employee_leave_applicationv2($comp_id,$emp_ids="",$min_range="",$max_rang=""){
		$row_array	= array();
		$leaves 	= $this->employee_leaves_app($comp_id,$emp_ids,$min_range,$max_rang);
		if($leaves){
			$s 		= array(
					"emp_id",
					"date",
					"undertime_min",
					"tardiness_min",
					"absent_min",
					);
			
			$w 		= array(
					"comp_id"	=> $comp_id,
					"status" 	=> "Active"
					);
			$this->db->where($w);
			if($emp_ids){
				$this->db->where_in("emp_id",$emp_ids);
			}
			$this->db->where("(time_in_status = 'approved' OR time_in_status IS NULL)");
			$q = $this->db->get("employee_time_in");
			$r1 = $q->result();
			if($r1){
				$credited = 0;
				foreach ($r1 AS $r){
					$date 	= $r->date;
					$emp_id = $r->emp_id;
					$data 	= array(
							"tardiness"		=> "",
							"undertime"		=> "",
							"absent_min"	=> "",
							"credited"		=> ""
					);
					if($r->time_in != NULL && $r->time_out != NULL){
						$r2 = in_array_custom("emps-{$r->emp_id}-{$r->date}",$leaves);
						
						if($r2){
							$credited = 0;
							$credited = $r2->credited;
							/*
							foreach ($r2 as $r3){
								if(strtotime($r3->date_start) <= strtotime($date) && strtotime($r3->date_end) >= strtotime($date)){
									$credited = $r3->credited;
								}
							}
							*/
							if($r->absent_min > 0){
								$data 	= array(
											"tardiness"		=> "",
											"undertime"		=> "",
											"absent_min"	=> "1",
											"credited"		=> $credited
										);
							}
							else{
								if($r->tardiness_min > 0 || $r->undertime_min > 0 ){
									if($r->tardiness_min > $r->undertime_min){
										$data 	= array(
												"tardiness"		=> "1",
												"undertime"		=> "",
												"absent_min"	=> "",
												"credited"		=> $credited
												);
									}else{
										$data 	= array(
												"tardiness"		=> "",
												"undertime"		=> "1",
												"absent_min"	=> "",
												"credited"		=> $credited
												);
									}
								}
							}
						}
						
					}
					
					$wd = array(
							"date"			=> $date,
							"emp_id"		=> $emp_id,
							"tardiness"		=> $data['tardiness'],
							"undertime"		=> $data['undertime'],
							"absent_min"	=> $data['absent_min'],
							"credited"		=> $data['credited'],
							"custom_search"	=> "{$date}-{$emp_id}",
					);
					array_push($row_array,$wd);
				}
			}
		}
		
		return $row_array;
	}
    
    public function get_all_change_shifts($check_company_id,$emp_ids="",$max_date="",$min_date="") {
        $row_array	= array();
		$s = array(
				"emp_id",
				"shift_date",
				"updated_date",
		);
	
		$w = array(
				'company_id'=> $check_company_id,
				'status'=> 'Active',
				'shift_date <='=> $max_date,
				'shift_date >='=> $min_date,
		);

		$this->db->select($s);
		$this->db->where($w);

		if($emp_ids){
			$this->db->where_in('emp_id',$emp_ids);
		}

		$q_pg = $this->db->get('employee_shifts_history');
		$r_pg = $q_pg->result();
	
		if($r_pg){
			foreach ($r_pg as $r1){
				$wd 	= array(
						"emp_id"	=> $r1->emp_id,
						"shift_date"				=> $r1->shift_date,
						"updated_date"				=> $r1->updated_date,
						"custom_search"		=> "change_shift_{$r1->emp_id}_{$r1->shift_date}"
				);
				array_push($row_array,$wd);
			}
		}
		return $row_array;
    }
	
	public function time_in_list_correction($emp_id, $company_id, $num_rows=false, $status = "", $page="", $limit=""){	    
	    $sel = array(
	        "etic.source",
	        "etic.time_in",
	        "etic.time_out",
	        "etic.lunch_out",
	        "etic.lunch_in",
	        "etic.emp_id",
	        "etic.date",
	        "etic.undertime_min",
	        "etic.tardiness_min",
	        "etic.workschedule_id AS work_schedule_id",
	        "etic.employee_time_in_correction_id",
	        "etic.company_id",
	        "etic.late_min",
	        "etic.overbreak_min",
	        "etic.total_hours_required",
	        "etic.total_hours",
	        "etic.absent_min",
	        "etic.date_approved",
	        'ws.break_rules',
	    );
	    
	    $w = array(
	        'etic.emp_id'      => $emp_id,
	        'etic.company_id'  => $company_id,
	        'etic.status'      => 'Active'
	    );
	    
	    $this->db->select($sel);
	    $this->db->where($w);
	    $this->edb->join("employee_payroll_information AS epi","epi.emp_id = etic.emp_id","LEFT");
	    $this->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
	    $this->edb->join("work_schedule AS ws","ws.work_schedule_id = etic.workschedule_id","LEFT");
	    $this->db->order_by('etic.date','DESC');
	    
	    if($page==0){
	        $sql = $this->db->get('employee_time_in_correction AS etic',$limit);
	    }else{
	        $sql = $this->db->get('employee_time_in_correction AS etic',$page,$limit);
	    }
	    
	    if($sql->num_rows() > 0){
	        $results = $sql->result();
	        $sql->free_result();
	        
	        return $results;
	    }else{
	        return FALSE;
	    }
	}
	
	public function get_profile_information($emp_id){
		$select = array(
				'e.company_id',
				'e.first_name',
				'e.last_name',
				'a.email',
				'a.profile_image',
				'a.login_mobile_number AS mobile_no',
				'e.address',
				'e.tin',
				'e.hdmf',
				'e.sss',
				'e.phil_health',
				'a.payroll_cloud_id',
				'e.dob',
				'e.gender',
				'e.marital_status',
				'p.position_name',
				'a.payroll_cloud_id',
				'e.citizenship_status',
				'e.state',
				'e.city',
				'a.telephone_number',
				'epi.employee_status',
				'r.rank_name',
				'cc.cost_center_code',
				'd.department_name',
				'proj.project_name',
				'epi.date_hired',
				'epa.card_id',
				'bpa.current_basic_pay',
				'bpa.new_basic_pay',
				'bpa.effective_date',
				'a.flag_primary',
				'a.login_mobile_number',
				'a.login_mobile_number_2',
				'e.umid',
                'a.payroll_system_account_id',
                'e.nickname'
		
		);
		$sel = array(
				'ep.name AS employment_type_name',
				'lo.name AS location_and_offices_name',
		);
		$this->edb->select($select);
		$this->db->select($sel);
		$this->edb->where('e.emp_id',$emp_id);
		$this->edb->join("employee AS e","e.account_id = a.account_id","LEFT");
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
		$this->edb->join("position AS p","p.position_id = epi.position","LEFT");
		$this->edb->join('payroll_group AS pg','epi.payroll_group_id=pg.payroll_group_id','LEFT');
		$this->edb->join('employee_payroll_account_info AS epa','epa.emp_id=e.emp_id','LEFT');
		$this->edb->join('rank AS r','r.rank_id=epi.rank_id','LEFT');
		$this->edb->join('cost_center AS cc','cc.cost_center_id=epi.cost_center','LEFT');
		$this->edb->join('department AS d','d.dept_id = epi.department_id','LEFT');
		$this->db->join('location_and_offices AS lo','lo.location_and_offices_id = epi.location_and_offices_id','LEFT');
		$this->edb->join('project AS proj','proj.project_id = epi.project_id','LEFT');
		$this->db->join('employment_type AS ep','ep.emp_type_id = epi.employment_type','LEFT');
		$this->db->join('basic_pay_adjustment AS bpa','bpa.emp_id = e.emp_id','LEFT');
		$q = $this->edb->get("accounts AS a");
        $r = $q->row();
        if ($r) {
            if ($r->flag_primary == 'login_mobile_number_2') {
                $t = $r->login_mobile_number;
                $r->login_mobile_number = $r->login_mobile_number_2;
                $r->login_mobile_number_2 = $t;
            }

        }
		/* foreach($r as $rr){
			$rr->other_dimension = "https://sv01.ashima.ph";
			#$rr->other_dimension = "http://payrollv3.konsum.local";
			//$rr->other_dimension = "http://ashima.konsum.ph";
			if($rr->effective_date) {
				if(date('Y-m-d') >= $rr->effective_date){
					$basic_pay = $rr->new_basic_pay;
				} else {
					$basic_pay = $rr->current_basic_pay;
				}
			} else {
				$basic_pay = $rr->current_basic_pay;
			}
			
			
			if($rr->flag_primary == 'login_mobile_number'){
				$primary_mobile_number = $rr->login_mobile_number;
				$second_mobile_number = $rr->login_mobile_number_2;
			} elseif ($rr->flag_primary == 'login_mobile_number_2') {
				$primary_mobile_number = $rr->login_mobile_number_2;
				$second_mobile_number = $rr->login_mobile_number;
			} else {
				$primary_mobile_number = "";
				$second_mobile_number = "";
			}
			
			$rr->primary_mobile_number = $primary_mobile_number;
			$rr->secondary_mobile_number = $second_mobile_number;
			$rr->basic_pay = $basic_pay;
		} */
		
		return ($r) ? $r : false;
	}
	
	public function get_employee_compensation_history($comp_id, $emp_id)
	{
		$sel = array(
				'current_basic_pay',
				'compensation_history_id',
				'date_changed',
				'old_basic_pay',
				'effective_date',
				'adjustment_date',
				'adjustment_details'
		);
		
		$ch_where = array(
				"emp_id" => $emp_id,
				"comp_id" => $comp_id
		);
		$this->db->where($ch_where);
		$this->db->order_by('date_changed', 'DESC');
		$q_qry = $this->edb->get('employee_compensation_history');
		$res = $q_qry->result();
		return $res ? $res : false;
	}
	
	public function get_employee_employment_history($employee_id,$employee_payroll_info_id,$company_id){
		$where_this_id = array(
				'eh.company_id'=>$company_id,
				'eh.emp_id'=>$employee_id,
				'eh.employee_payroll_information_id'=>$employee_payroll_info_id,
		);
		$this->db->where($where_this_id);
		$this->db->order_by("eh.employment_history_id", "desc");
		$this->db->join("position AS p","eh.new_job_position = p.position_id","INNER");
		$this->db->join("rank AS r","eh.rank_id = r.rank_id","INNER");
		$this->db->join("employment_type AS et","eh.employment_type = et.emp_type_id","INNER");
		$this->db->join("department AS d","eh.department_id = d.dept_id","INNER");
		$c_payroll_group = $this->db->get('employee_employment_history AS eh');
		$r = $c_payroll_group->result();
		return ($r) ? $r : FALSE ;
	}
	
	public function get_selected_reports_to($emp_id,$company_id){
		$sel = array(
				'a.payroll_cloud_id',
				'e.first_name',
				'e.last_name',
				'd.department_name',
				'a.profile_image'
		);
		
		$w = array(
			"edrt.emp_id"=>$emp_id,
			"edrt.company_id"=>$company_id,
		);
		
		$this->edb->select($sel);
		$this->db->where($w);
		$this->db->group_by('edrt.parent_emp_id');
		$this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","INNER");
		$this->edb->join("department AS d","d.dept_id = epi.department_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$this->edb->join("employee_details_reports_to AS edrt","edrt.parent_emp_id = e.emp_id","INNER");
		$q = $this->edb->get("employee AS e");
		$r = $q->row();
		return ($r) ? $r : FALSE ;
	}
	
	public function get_mobile_time_tracking_setting($comp_id) {
		$where = array(
			'company_id' => $comp_id	
		);
		
		$this->db->where($where);
		$q = $this->edb->get("clock_guard_settings");
		$r = $q->row();
		return ($r) ? $r : FALSE;
	}

	public function new_check_emp_info($emp_no,$comp_id){

		$s_emp 	= array(
				"pi.position",
				"pi.employee_status",
				"pi.timesheet_required",
				"e.first_name",
				"e.last_name",
				"e.emp_id",
				);
		$w_emp 	= array(
				"a.payroll_cloud_id"=>$emp_no,
				"a.user_type_id"=>"5",
				"e.company_id" => $comp_id,
				);
		$this->edb->select($s_emp);
		$this->edb->where($w_emp);
		$this->edb->join("employee_payroll_information AS pi","pi.emp_id = e.emp_id","INNER");
		$this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
		$q_emp = $this->edb->get("employee AS e");
		$q = $q_emp->row();
		return ($q_emp->num_rows() > 0) ? $q_emp->row() : FALSE ;
	}
	
	public function get_data_payslip($company_id,$emp_id, $num_rows=false, $page="", $limit="",$from = "", $to = "")
	{
        $payroll_dates = $this->get_payslips_payperiod($emp_id, $company_id);
        //($row->payroll_date,$row->period_from,$row->period_to,$this->company_id,$row->emp_id, $row->payroll_group_id);
		$sel = array(
            'payroll_date',
            'period_from',
            'period_to',
            'e.emp_id',
            'payroll_group_id',
            'payroll_payslip_id',
            'net_amount'
        );
		$where = array(
			"e.company_id" => $company_id,
			"e.status" => 'Active',
			"a.deleted" => '0',
			"pp.emp_id" => $emp_id,
			"pp.flag_prev_emp_income" => '0'
		);
		
		if($payroll_dates != null){
			$this->db->where_in("pp.payroll_date", $payroll_dates);
		}
		
		if($from != "" && $to != ""){
			$ft = array(
				"pp.payroll_date >=" => date("Y-m-d",strtotime($from)),
				"pp.payroll_date <=" => date("Y-m-d",strtotime($to))
			);
	   		$this->db->where($ft);
          }
        $this->edb->select($sel);
		$this->edb->where($where);
		$this->edb->join('employee AS e','e.emp_id = pp.emp_id','INNER');
		$this->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
		$this->db->order_by("pp.payroll_date","DESC");
		#$query = $this->edb->get('payroll_payslip AS pp',$limit,$page);
		#$result = $query->result();
		
		if($num_rows == true) {
			#$query = $this->edb->get('accounts AS a');
			$query = $this->edb->get('payroll_payslip AS pp');
			return $query->num_rows();
		} else {
			$query = $this->edb->get('payroll_payslip AS pp',$limit,$page);
			$result = $query->result();
				
			return $result;
		}
	}
	
	/**
	 * Check Approval Date
	 * @param unknown_type $payroll_date
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $company_id
	 */
	public function check_approval_date($payroll_date,$period_from,$period_to,$company_id,$emp_id,$payroll_group_id){
		$w = array(
				"prc.payroll_period"	=> $payroll_date,
				"prc.period_from"		=> $period_from,
				"prc.period_to"			=> $period_to,
				"prc.company_id"		=> $company_id,
				"prc.emp_id"			=> $emp_id,
				"prc.status"			=> "Active",
				"dpr.view_status"		=> "Closed",
				"ap.generate_payslip"	=> "Yes"
		);
		$this->db->where($w);
		$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
		$this->db->join("approval_payroll AS ap","ap.token = dpr.token","LEFT");
		$q = $this->db->get("payroll_run_custom AS prc");
		$r = $q->row();
		
		if($r){
			return $r;
		}else{
			$w = array(
					"dpr.pay_period"		=> $payroll_date,
					"dpr.period_from"		=> $period_from,
					"dpr.period_to"			=> $period_to,
					"dpr.company_id"		=> $company_id,
					"dpr.payroll_group_id"	=> $payroll_group_id,
					"dpr.status"			=> "Active",
					"dpr.view_status"		=> "Closed",
					"ap.generate_payslip"	=>	"Yes"
			);
	
			$this->db->where($w);
			$this->db->join("approval_payroll AS ap","ap.token = dpr.token","LEFT");
			$q = $this->db->get("draft_pay_runs AS dpr");
			$r = $q->row();
			return ($r) ? $r : FALSE ;
		}
			
		#return ($q->num_rows() > 0) ? TRUE : FALSE ;
	}
	
	/**
	 * Employee Loans
	 * @param unknown_type $comp_id
	 */
	public function employee_loans($comp_id){
		$w = array(
				"company_id"=>$comp_id,
				"status"=>"Active"
		);
		$this->db->where($w);
		$q = $this->db->get("loan_type");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	/**
	 * Get Employee Loans
	 * @param unknown_type $emp_id
	 * @param unknown_type $deductions_other_deductions_id
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $payroll_period
	 */
	public function get_employee_loans($emp_id,$id,$period_from,$period_to,$payroll_period){
		$w = array(
				"emp_id"=>$emp_id,
				"loan_type_id"=>$id,
				"period_from"=>$period_from,
				"period_to"=>$period_to,
				"payroll_date"=>$payroll_period
		);
		$this->db->where($w);
		$q = $this->db->get("payroll_run_loans");
		$r = $q->result();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	public function get_current_payslip($company_id,$emp_id)
	{
		$where = array(
				"e.company_id" 				=> $company_id,
				"e.status" 					=> 'Active',
				"a.deleted" 				=> '0',
				"pp.emp_id" 				=> $emp_id,
				"pp.flag_prev_emp_income"	=> '0',
				
				
				/*"pp.payroll_date" => '2016-10-31',
				"pp.period_from" => '2016-10-11',
				"pp.period_to" => '2016-10-25'*/
		);
	
		$query = $this->db->where($where);
		$this->edb->join('employee AS e','e.account_id=a.account_id','inner');
		$this->edb->join('payroll_payslip AS pp','e.emp_id=pp.emp_id','inner');
		$this->db->order_by("payroll_date","desc");
		$query = $this->edb->get('accounts AS a');
		$result = $query->row();
	
		return $result;
	
	}
	
	public function get_overtime_list($emp_id,$company_id, $num_rows=false, $status = "",$page="",$limit="") {		
		
		if($status) {			
			$where = array(
					'emp_id' => $emp_id,
					'company_id' => $company_id,
					#'MONTH(overtime_date_applied)' => $month,
					#'YEAR(overtime_date_applied)' => $year
					'overtime_status' => $status,
					'status' => 'Active'
			);
		} else {
			$where = array(
					'emp_id' => $emp_id,
					'company_id' => $company_id,
					#'MONTH(overtime_date_applied)' => $month,
					#'YEAR(overtime_date_applied)' => $year
					'status' => 'Active'
			);
		}
		
		$this->db->where($where);
		$this->db->order_by('overtime_date_applied desc, overtime_id desc');
		
		if($num_rows == true) {
			$q = $this->db->get('employee_overtime_application');
			return $q->num_rows();
		}
		$q = $this->db->get('employee_overtime_application',$limit,$page);
        $r = $q->result();
        if ($r) {
            $formatted = array();
            foreach ($r as $row) {
                array_push($formatted, array(
                    'overtime_id' => $row->overtime_id,
                    'overtime_type_id' => $row->overtime_type_id,
                    'emp_id' => $row->emp_id,
                    'overtime_date_applied' => $row->overtime_date_applied,
                    'overtime_from' => $row->overtime_from,
                    'overtime_to' => $row->overtime_to,
                    'start_time' => ($row->start_time) ? date("h:i A", strtotime($row->start_time)) : '',
                    'end_time' => ($row->end_time) ? date("h:i A", strtotime($row->end_time)) : '',
                    'no_of_hours' => $row->no_of_hours,
                    'with_nsd_hours' => $row->with_nsd_hours,
                    'employee_time_in_id' => $row->employee_time_in_id,
                    'working_type_lite_id' => $row->working_type_lite_id,
                    'company_id' => $row->company_id,
                    'reason' => $row->reason,
                    'notes' => $row->notes,
                    'approval_date' => $row->approval_date,
                    'status' => $row->status,
                    'overtime_status' => $row->overtime_status,
                    'deleted' => $row->deleted,
                    'flag_payroll_correction' => $row->flag_payroll_correction,
                    'created_by' => $row->created_by,
                ));
            }
            return $formatted;
        }
		
		return FALSE;
	}
	
	public function get_overtime_list_correction($emp_id,$company_id, $num_rows=false,$page="",$limit="") {
	    
	    $where = array(
	        'emp_id' => $emp_id,
	        'company_id' => $company_id,
	        'status' => 'Active'
	    );
	    
	    $this->db->where($where);
	    $this->db->order_by('overtime_date_applied','desc');
	    
	    if($num_rows == true) {
	        $q = $this->db->get('employee_overtime_application_correction');
	        return $q->num_rows();
	    }
	    $q = $this->db->get('employee_overtime_application_correction',$limit,$page);
	    $r = $q->result();
	    
	    return ($r) ? $r : FALSE;
	}
	
	public function get_employee_leaves_list($emp_id, $company_id, $num_rows=false, $status = "", $page="", $limit="") {
		if($status) {
			$where = array(
					"e.leave_application_status" => $status,
                    "e.status" => "Active",
                    "e.flag_parent" => "yes"
			);
			$this->db->where($where);
		}
		
		$select = array(
				"empl.remaining_leave_credits AS remaining_c",
				"l.leave_units AS leave_unit"
		);
		
		// $this->db->select($select);
        $this->db->where('e.emp_id',$this->emp_id);
        $this->db->where("(l.flag_is_ml > 1 OR l.flag_is_ml IS NULL)");
		$this->db->join('leave_type AS l','e.leave_type_id = l.leave_type_id','LEFT');
		$this->db->join("employee_leaves AS empl","empl.leave_type_id = l.leave_type_id and empl.emp_id = e.emp_id","LEFT");
		$this->db->join("approval_leave AS al","al.leave_id = e.employee_leaves_application_id","LEFT");
		$this->db->order_by('date_start','DESC');
		$this->db->group_by('e.employee_leaves_application_id');
		
		if($num_rows == true) {
			$q = $this->db->get('employee_leaves_application AS e');
			return $q->num_rows();
		} else {
			$q = $this->db->get('employee_leaves_application AS e',$limit,$page);
			$r = $q->result();
			
			return ($r) ? $r : FALSE;
		}
	}
	
	public function get_employee_leaves_list_correction($emp_id, $company_id, $num_rows=false, $page="", $limit="") {
	
        $where = array(
            "e.status" => "Active"
        );
        
        $this->db->where($where);
	    
	    $select = array(
	        "empl.remaining_leave_credits AS remaining_c",
	        "l.leave_units AS leave_unit"
	    );
	    
	    $this->db->select($select);
	    $this->edb->where('e.emp_id',$this->emp_id);
	    $this->edb->join('leave_type AS l','e.leave_type_id = l.leave_type_id','LEFT');
	    $this->edb->join("employee_leaves AS empl","empl.leave_type_id = l.leave_type_id and empl.emp_id = e.emp_id","LEFT");
	    $this->edb->join("approval_leave AS al","al.leave_id = e.employee_leaves_application_id","LEFT");
	    $this->db->order_by('date_start','DESC');
	    $this->db->group_by('e.employee_leaves_application_id');
	    
	    if($num_rows == true) {
	        $q = $this->db->get('employee_leaves_application_correction AS e');
	        return $q->num_rows();
	    } else {
	        $q = $this->edb->get('employee_leaves_application_correction AS e',$limit,$page);
	        $r = $q->result();
	        
	        return ($r) ? $r : FALSE;
	    }
	}
	
	public function get_company_name($company_id) {
		$s = array(
			"company_name"		
		);
		
		$w = array(
				"company_id" => $company_id
		);
		
		$this->db->select($s);
		$this->db->where($w);
		$q = $this->db->get("company");
		$r = $q->row();
		
		return ($r) ? $r : false;
	}
	
	public function get_basic_pay($company_id,$employee_id)
	{
		$where = array(
				'comp_id' 	 => $company_id,
				'emp_id' 	 => $employee_id,
				'status'	 => 'Active'
		);
		$this->edb->where($where);
		$q = $this->edb->get('basic_pay_adjustment');
		$result = $q->row();
		$q->free_result();
		return ($result) ? $result : false;
	}
	
	public function check_employee_period_type($emp_id,$company_id){
		$s = array(
				"pg.period_type",
				"pg.pay_rate_type"
		);
		$this->db->select($s);
	
		$w = array(
				"epi.emp_id"=>$emp_id,
				"epi.company_id"=>$company_id,
				"epi.status"=>"Active"
		);
		$this->db->where($w);
		$this->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
		$q = $this->db->get("employee_payroll_information AS epi");
		$r = $q->row();
		$q->free_result();
		return ($r) ? $r : FALSE ;
	}
	
	public function rank_total_working_days_in_a_year($emp_id){
	
		$w = array(
				"epi.emp_id"=>$emp_id,
				"epi.status"=>"Active"
		);
		$s = array(
				"*","pcs.working_days_in_a_year AS total_working_days_in_a_year"
		);
		$this->db->select($s);
		$this->db->where($w);
		$this->db->where("pcs.enable_rank","No");
		$this->db->join("payroll_calendar_working_days_settings AS pcs","pcs.company_id = epi.company_id","LEFT");
		$q = $this->db->get("employee_payroll_information AS epi");
		$r = $q->row();
		$q->free_result();
		if($r){
			// COMPANY WIDE (DEFUALT VALUE FOR ALL COMPANY)
			return $r;
		}else{
			// WORKING DAYS DEPENDING ON RANK
			$this->db->where($w);
			$this->db->join("rank_working_days AS rwd","rwd.rank_id = epi.rank_id","INNER");
			$q = $this->db->get("employee_payroll_information AS epi");
			$r = $q->row();
			$q->free_result();
			return ($r) ? $r : FALSE ;
		}
	}
	
	public function get_working_days_settings($company_id){
		$where = array(
				"company_id" => $company_id
		);
		$this->db->where($where);
		$query = $this->db->get("payroll_calendar_working_days_settings");
		$row = $query->row();
	
		return ($row) ? $row : false;
	}
	
	public function average_working_hours_per_day($company_id){
		$w = array(
				"company_id"=>$company_id,
				"status"=>"Active"
				#"average_working_hours_per_day != " => NULL
		);
		$this->db->where($w);
		$this->db->where("average_working_hours_per_day IS NOT NULL");
		$q = $this->db->get("payroll_calendar_working_days_settings");
		$r = $q->row();
		$average_working_hours_per_day = ($r) ? $r->average_working_hours_per_day : 8 ;
		return $average_working_hours_per_day;
	}
	
	public function emp_basic_pay($comp_id,$emp_id,$period){
		$bp = 0;
		// check if employee salary is set
		$basic_pay = $this->get_basic_pay($comp_id,$emp_id);
	
		if (!$basic_pay) {
			return false;
		}
	
		// check new basic pay
		$edate = date('Y-m-d',strtotime($basic_pay->effective_date));
		if (is_object($period)) {
			$pfrom = date('Y-m-d',strtotime($period->period_from));
			$pto   = date('Y-m-d',strtotime($period->period_to));
		} elseif (is_array($period)) {
			$pfrom = date('Y-m-d',strtotime($period['period_from']));
			$pto   = date('Y-m-d',strtotime($period['period_to']));
		}
	
		if ($basic_pay->effective_date) {
			if (($pfrom <= $edate && $pto >= $edate) || $edate <= $pfrom) {
				$bp = $basic_pay->new_basic_pay;
			} else {
				$bp = $basic_pay->current_basic_pay;
			}
		} else {
			$bp = $basic_pay->current_basic_pay;
		}
	
		// FOR FORTNIGHTLY BASIC PAY
		$check_employee_period_type = $this->check_employee_period_type($emp_id,$comp_id);
	
		if($check_employee_period_type != FALSE){
				
			// Get Average Working Hours Per Day
			$average_working_hours_per_day = $this->average_working_hours_per_day($comp_id);
				
			// get total days per year
			$day_per_year = $this->rank_total_working_days_in_a_year($emp_id);
			$day_per_year = ($day_per_year != FALSE) ? $day_per_year->total_working_days_in_a_year / 12 : 0 ;
				
			$employee_period_type = $check_employee_period_type->period_type;
			$employee_pay_rate_type = $check_employee_period_type->pay_rate_type;
				
			if($employee_period_type == "Fortnightly" && $employee_pay_rate_type == "By Month"){ // hourly
				$bp = $bp / $day_per_year;
				$bp = ($bp > 0) ? $bp : 0 ;
			}
		}
	
		return $bp;
	}
	
	public function get_emp_employment_history($employee_id,$company_id){
		$res = array();
		
		$sel = array(
				'eh.employment_history_id',
				'eh.effective_date',
				'p.position_name',
				'eh.current_job_position',
				'eh.job_change_code',
				'eh.department_id',
				'd.department_name',
				'eh.rank_id',
				'r.rank_name',
				'eh.employment_type'
		);
		
		$where_this_id = array(
				'eh.company_id'=>$company_id,
				'eh.emp_id'=>$employee_id
		);
		
		$this->db->select($sel);
		$this->db->where($where_this_id);
		$this->db->order_by("eh.employment_history_id", "desc");
		$this->db->join("position AS p","eh.new_job_position = p.position_id","INNER");
		$this->db->join("rank AS r","eh.rank_id = r.rank_id","INNER");
		$this->db->join("employment_type AS et","eh.employment_type = et.emp_type_id","INNER");
		$this->db->join("department AS d","eh.department_id = d.dept_id","INNER");
		$c_payroll_group = $this->db->get('employee_employment_history AS eh');
		$r = $c_payroll_group->result();
		
		if($r) {
			foreach ($r as $row) {
				$res2 = array();
				
				$employment_type = get_table_info("employment_type",array("emp_type_id"=>$row->employment_type));
				$employment_type_name = "";
				
				if ($employment_type) {
					$employment_type_name = $employment_type->name;
				}
				
				$get_employee_employment_history_reports_to = $this->get_employee_employment_history_reports_to($this->emp_id,$row->employment_history_id,$this->company_id);
				
				$reports_to_name = "";
				
				if ($get_employee_employment_history_reports_to) {
					$reports_to_name = $get_employee_employment_history_reports_to->first_name." ".$get_employee_employment_history_reports_to->last_name;
				}
				
				$prev_position = get_table_info("position",array("position_id"=>$row->current_job_position));
				$prev_position_name = "";
				
				if ($prev_position) {
					$prev_position_name = $prev_position->position_name;
				}
				
				$get_company_name = $this->get_company_name($company_id);
				$company_name = "";
				
				if($get_company_name) {
					$company_name = $get_company_name->company_name;
				}
				
				$temp = array(
						'employment_history_id' => $row->employment_history_id,
						'effective_date' => $row->effective_date,
						'position_name' => $row->position_name,
						'current_job_position' => $row->current_job_position,
						'job_change_code' => $row->job_change_code,
						'department_id' => $row->department_id,
						'department_name' => $row->department_name,
						'rank_id' => $row->rank_id,
						'rank_name' => $row->rank_name,
						'employment_type' => $row->employment_type,
						'employment_type_name' => $employment_type_name,
						'reports_to_name' => $reports_to_name,
						'current_job_position_name' => $prev_position_name,
						'company_name' => $company_name
				);
				
				array_push($res, $temp);
			}
			
			return $res;
		} else {
			return false;
		}
		
	}
	
	public function get_employee_employment_history_reports_to($employee_id,$employment_history_id,$company_id){
		$where_this_id = array(
				'eehr.employment_history_id'=>$employment_history_id,
				'eehr.company_id'=>$company_id,
				'eehr.emp_id'=>$employee_id
		);
		$this->db->where($where_this_id);
		$this->db->group_by('eehr.parent_emp_id');
		$this->edb->join('employee_employment_history_reports_to AS eehr','e.emp_id=eehr.parent_emp_id','INNER');
		$this->db->order_by('eehr.parent_emp_id','desc');
		$c_payroll_group = $this->edb->get('employee AS e');
		$r = $c_payroll_group->row();
		return ($r) ? $r : FALSE ;
	}
	
	public function get_pending_approval_leaves($leave_type_id,$employee_id,$type="pending"){
		$year = date("Y"); #YEAR TODAY
		
		if($leave_type_id && $employee_id){
			$where = array(
				'emp_id'=>$employee_id,
				'company_id'=>$this->company_id,
				'status'=>'Active',
				'year(date_filed)'=>$year,
				'leave_type_id'=>$leave_type_id
			);
			
			if($type == 'pending'){
				$where['leave_application_status'] = 'pending';
			}else if($type =='approve'){
				$where['leave_application_status'] = 'approve';
			}
			
			$this->db->where($where);
			$select = array(
				'sum(total_leave_requested) AS total_request'
			);
			$this->db->select($select);
			$q = $this->db->get('employee_leaves_application');
			$result = $q->row();
			return $result;
		}else{
			return false;
		}
	}
	
	public function get_payslips_payperiod($emp_id, $company_id){
 		$arrs = array();
 	
 		// PAYROLL CUSTOM
 		$w = array(
 			"prc.company_id" => $company_id,
 			"prc.emp_id" => $emp_id,
 			"prc.status" => "Active",
 			"ap.generate_payslip" => "Yes"
 		);
 		$this->db->where($w);
 		$this->db->where("dpr.view_status = 'Closed'");
 		$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
 		$this->db->join("approval_payroll AS ap","ap.token = dpr.token","LEFT");
 		$q = $this->db->get("payroll_run_custom AS prc");
 		$r = $q->result();
 		
 		if($r){
 			foreach($r as $row){
 				array_push($arrs, $row->payroll_period);
 			}
 		}
 	
 		// BY PAYROLL GROUP
 		$w = array(
 			"dpr.company_id" => $company_id,
 			"pp.emp_id" => $emp_id,
 			"dpr.status" => "Active",
 			"ap.generate_payslip" => "Yes"
 		);
 		$this->db->where($w);
 		$this->db->where("dpr.view_status = 'Closed'");
 		$this->db->join("draft_pay_runs AS dpr","pp.payroll_group_id = dpr.payroll_group_id && dpr.pay_period = pp.payroll_date","LEFT");
 		$this->db->join("approval_payroll AS ap","ap.token = dpr.token","LEFT");
 		$q = $this->db->get("payroll_payslip AS pp");
 		$r = $q->result();
 	
 		if($r){
 			foreach($r as $row){
 				array_push($arrs, $row->payroll_date);
 			}
 		}
 		
 		return $arrs;
 	}
 	
 	public function get_new_latest_payslip($company_id,$emp_id)
 	{
        $payroll_dates = $this->get_payslips_payperiod($emp_id, $company_id);
         
        if($payroll_dates == false){
            return false;
        }

        if(!$payroll_dates){
            return false;
        }
 	    
 	    $where = array(
 	        "e.company_id" => $company_id,
 	        "e.status" => 'Active',
 	        "a.deleted" => '0',
 	        "pp.emp_id" => $emp_id,
 	        "pp.flag_prev_emp_income" => '0'
 	    );
 	    
 	    if($payroll_dates != null){
 	        $this->db->where_in("pp.payroll_date", $payroll_dates);
 	    }
 	    
 	    $this->edb->where($where);
 	    $this->edb->join('employee AS e','e.emp_id = pp.emp_id','INNER');
 	    $this->edb->join('accounts AS a','a.account_id = e.account_id','LEFT');
 	    $this->db->order_by("pp.payroll_date","DESC");
 	    
 	    $query = $this->edb->get('payroll_payslip AS pp');
 	    $result = $query->result();

 	    if ($result) {
 	    	foreach ($result as $row) {
				if ($row) {
					$check_approval_date = $this->reverse_check_approval_date($row->payroll_date,$row->period_from,$row->period_to,$this->company_id,$row->emp_id, $row->payroll_group_id);

					if (!$check_approval_date) {
						return (object)$row;
					}
				}
			}
 	    }
 	    
 	    return false;
 	}

 	/**
	 * This function just validates if there is ANY pay run that is not closed
	 * @param unknown_type $payroll_date
	 * @param unknown_type $period_from
	 * @param unknown_type $period_to
	 * @param unknown_type $company_id
	 */
	public function reverse_check_approval_date($payroll_date,$period_from,$period_to,$company_id,$emp_id,$payroll_group_id){
		$w = array(
				"prc.payroll_period"	=> $payroll_date,
				"prc.period_from"		=> $period_from,
				"prc.period_to"			=> $period_to,
				"prc.company_id"		=> $company_id,
				"prc.emp_id"			=> $emp_id,
				"prc.status"			=> "Active",
				"dpr.view_status !="	=> "Closed",
				// "ap.generate_payslip"	=> "Yes"
		);
		$this->db->where($w);
		$this->db->join("draft_pay_runs AS dpr","dpr.draft_pay_run_id = prc.draft_pay_run_id","LEFT");
		$this->db->join("approval_payroll AS ap","ap.token = dpr.token","LEFT");
		$q = $this->db->get("payroll_run_custom AS prc");
		$r = $q->row();
		
		if($r){
			return $r;
		}else{
			$w = array(
					"dpr.pay_period"		=> $payroll_date,
					"dpr.period_from"		=> $period_from,
					"dpr.period_to"			=> $period_to,
					"dpr.company_id"		=> $company_id,
					"dpr.payroll_group_id"	=> $payroll_group_id,
					"dpr.status"			=> "Active",
					"dpr.view_status !="	=> "Closed",
					// "ap.generate_payslip"	=>	"Yes"
			);
	
			$this->db->where($w);
			$this->db->join("approval_payroll AS ap","ap.token = dpr.token","LEFT");
			$q2 = $this->db->get("draft_pay_runs AS dpr");
			$r2 = $q2->row();
			
			return ($r2) ? $r2 : FALSE ;
		}
			
		#return ($q->num_rows() > 0) ? TRUE : FALSE ;
    }
    
    public function check_is_date_holidayv2($company_id,$min="",$max=""){
		
		$row_array 	= array();
		$year_min 	= date("Y");
		$year_max   = date("Y");

		if($min && $max){
			$year_min 	= date("Y",strtotime($min));
			$year_max 	= date("Y",strtotime($max));
		}
		
		$y_gap		= ($year_max - $year_min) + 1;
		$s		= array(
				'h.repeat_type',
				'ht.hour_type_name',
				'h.holiday_name',
				'h.date',
				'h.date_type',
		);
		$this->db->select($s);
		$w		= array(
				"h.company_id" => $company_id
		);
		$this->db->where($w);
		//$this->db->where("(MONTH(h.date) = '{$month}' && DAY(h.date) = '{$day}')");
		$this->db->join("hours_type AS ht","ht.hour_type_id = h.hour_type_id");
		$q = $this->db->get("holiday AS h");
		$r = $q->result();
		
		if($r){
			foreach ($r as $r1){
				$date		= $r1->date;
				
				if($r1->repeat_type == "yes" && $r1->date_type != "movable"){
					
					for($x = ($year_min - 1);$x <= $year_max;$x++){
						
						$month 		= date("m",strtotime($date));
						$day 		= date("d",strtotime($date));
						$year 		= $x;

						$hol_date 	= $year."-".$month."-".$day;
						
						$wd 	= array(
								"repeat_type"		=> $r1->repeat_type,
								"hour_type_name"	=> $r1->hour_type_name,
								"holiday_name"		=> $r1->holiday_name,
								"date"				=> $hol_date,
								"custom_search"		=> "date-{$hol_date}",
						);
						array_push($row_array,$wd);
					}
				}else{
					$wd 	= array(
							"repeat_type"		=> $r1->repeat_type,
							"hour_type_name"	=> $r1->hour_type_name,
							"holiday_name"		=> $r1->holiday_name,
							"date"				=> $r1->date,
							"custom_search"		=> "date-{$r1->date}",
					);
					array_push($row_array,$wd);
				}
			}
		}

		return $row_array;
	}
}