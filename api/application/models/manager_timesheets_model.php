<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Manager_timesheets_model extends CI_Model {

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

    public function all_timesheet_list($company_id,$emp_id,$num_rows=false,$page="",$limit="",$date_from="",$date_to=""){
        $final_result = array();
        if(is_numeric($company_id)){
            #$limit = intval($limit);

            $this->db->order_by('eti.time_in','DESC');
            $where = array(
                'eti.comp_id'   		=> $company_id,
                'eti.status'   			=> 'Active',
                'edrt.parent_emp_id'	=> $emp_id,
                'flag_delete_on_hours' 	=> '0'
            );

            if($date_from !="" && $date_from !="none" && $date_to !="" && $date_to !="none"){
                $this->db->where('eti.date BETWEEN "'.$date_from.'" AND "'.$date_to.'"',NULL,FALSE);
            }

            $this->db->where($where);
            $this->db->where(" (eti.time_in_status ='pending' OR eti.time_in_status ='approved' OR eti.time_in_status IS NULL) ",NULL,FALSE);

            $arr = array(
                'lunch_out'             => 'eti.lunch_out',
                'lunch_in'              => 'eti.lunch_in',
                'time_out'              => 'eti.time_out',
                'time_in'               => 'eti.time_in',
                'total_hours_required'  => 'eti.total_hours_required',
                'total_hours'           => 'eti.total_hours',
                'tardiness_min'         => 'eti.tardiness_min',
                'undertime_min'         => 'eti.undertime_min',
                'absent_min'            => 'eti.absent_min',
                'source'                => 'eti.source',
                'date'                  => 'eti.date',
                'payroll_cloud_id'      => 'a.payroll_cloud_id',
                'work_schedule_id'      => 'eti.work_schedule_id',
                'employee_time_in_id'   => 'eti.employee_time_in_id',
                'comp_id'               => 'eti.comp_id',
                'first_name'            => 'e.first_name',
                'last_name'             => 'e.last_name',
                'emp_id'                => 'eti.emp_id',
                'account_id'            => 'a.account_id',
                'location'              => 'eti.location',
                'profile_image'         => 'a.profile_image',
                'break1_out'            => 'eti.break1_out',
                'break1_in'             => 'eti.break1_in',
                'break2_out'            => 'eti.break2_out',
                'break2_in'             => 'eti.break2_in',
                'late_min'              => 'eti.late_min',
                'overbreak_min'         => 'eti.overbreak_min',
                'time_in_status'        => 'eti.time_in_status',
                'last_source'           => 'eti.last_source',
                'change_log_date_filed' => 'eti.change_log_date_filed',
                'break_rules'           => 'ws.break_rules',
                'name'                  => 'ws.name',
                'work_type_name'        => 'ws.work_type_name',
                'reason'                => 'eti.reason'
            );
            $this->edb->select($arr);
            $this->edb->join('employee AS e','e.emp_id = eti.emp_id',"LEFT");
            $this->edb->join('accounts AS a','a.account_id = e.account_id',"LEFT");
            $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = eti.emp_id","LEFT");
            $this->edb->join("work_schedule AS ws","ws.work_schedule_id = eti.work_schedule_id","LEFT");

            if($num_rows == true) {
                $query = $this->edb->get('employee_time_in AS eti');
                return $query->num_rows();
            }else{
                // $query = $this->edb->get('employee_time_in AS eti',$limit,$page);
                $query = $this->edb->get('employee_time_in AS eti',$limit,$page);
                $result = $query->result();
                $is_migrated = for_list_tardiness_rule_migrated_v3($company_id);                

                if($result){
                    $emp_ids = array();
                    foreach($result as $app) {
                        $emp_ids[] = $app->emp_id;
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

                    foreach($result as $row){
                        $sources = explode(",", $row->source);
                        $source_val = '';
                        $time_in_s      = "";
                        $lunch_out_s    = "";
                        $lunch_in_s     = "";
                        $break1_out_s   = "";
                        $break1_in_s    = "";
                        $break2_out_s   = "";
                        $break2_in_s    = "";
                        $time_out_s     = "";

                        if ($sources) {
                            $source_val = explode("-", $sources[0]);

                            foreach ($sources as $key => $value) {
                                $classify = explode("-", $value);

                                if ($classify) {
                                    $s_type = '';
                                    foreach ($classify as $ind => $val) {
                                        
                                        if ($ind == 0) {
                                            if ($val == 'kiosk') {
                                                $s_type = '(K)';
                                            } elseif ($val == 'desktop') {
                                                $s_type = '(D)';
                                            } elseif ($val == 'mobile') {
                                                $s_type = '(M)';
                                            }
                                        }

                                        if ($ind != 0) {
                                            if($val == "time in"){
                                                $time_in_s = $s_type;
                                            }
                                            if($val == "time out"){
                                                $time_out_s = $s_type;
                                            }
                                            if($val == "lunch out"){
                                                $lunch_out_s = $s_type;
                                            }
                                            if($val == "lunch in"){
                                                $lunch_in_s = $s_type;
                                            }
                                            if($val == "break1 out"){
                                                $break1_out_s = $s_type;
                                            }
                                            if($val == "break1 in"){
                                                $break1_in_s = $s_type;
                                            }
                                            if($val == "break2 out"){
                                                $break2_out_s = $s_type;
                                            }
                                            if($val == "break2 in"){
                                                $break2_in_s = $s_type;
                                            }
                                        }
                                    }
                                }
                            }
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

                        $temp_res = array(
                            "base_url" => base_url(),
                            "emp_id" => $row->emp_id,
                            "company_id" => $company_id,
                            "account_id" => $row->account_id,
                            "first_name" => $row->first_name,
                            "last_name" => $row->last_name,
                            "payroll_cloud_id" => $row->payroll_cloud_id,
                            "profile_image" => $row->profile_image,
                            "total_hours" => $row->total_hours,
                            "time_in_ws" => ($row->time_in) ? date('h:i a', strtotime($row->time_in)) . ' ' . $time_in_s : '',
                            "time_out_ws" => ($row->time_out) ? date('h:i a', strtotime($row->time_out)) . ' ' . $time_out_s : '',
                            "lunch_out_ws" => ($row->lunch_out) ? date('h:i a', strtotime($row->lunch_out)) . ' ' . $lunch_out_s : '',
                            "lunch_in_ws" => ($row->lunch_in) ? date('h:i a', strtotime($row->lunch_in)) . ' ' . $lunch_in_s : '',
                            "time_in" => ($row->time_in) ? date('h:i a', strtotime($row->time_in)) : '',
                            "time_out" => ($row->time_out) ? date('h:i a', strtotime($row->time_out)) : '',
                            "lunch_out" => ($row->lunch_out) ? date('h:i a', strtotime($row->lunch_out)) : '',
                            "lunch_in" => ($row->lunch_in) ? date('h:i a', strtotime($row->lunch_in)) : '',
                            "total_hours_required" => $row->total_hours_required,
                            "date" => $row->date,
                            "source" => $source_val[0],
                            "tardiness_min" => $row->tardiness_min,
                            "undertime_min" => $row->undertime_min,
                            "absent_min" => $row->absent_min,
                            "overbreak_min" => $row->overbreak_min,
                            "location" => $row->location,
                            "break1_out" => ($row->break1_out) ? date('h:i a', strtotime($row->break1_out)) . ' ' . $break1_out_s : '',
                            "break1_in" => ($row->break1_out) ? date('h:i a', strtotime($row->break1_in)) . ' ' . $break1_in_s : '',
                            "break2_out" => ($row->break2_out) ? date('h:i a', strtotime($row->break2_out)) . ' ' . $break2_out_s : '',
                            "break2_in" => ($row->break2_in) ? date('h:i a', strtotime($row->break2_in)) . ' ' . $break2_in_s : '',
                            "late_min" => $row->late_min,
                            "last_source" => $row->last_source,
                            "change_log_date_filed" => $row->change_log_date_filed,
                            "break_rules" => $row->break_rules,
                            "work_sched_name" => $row->name,
                            "work_type_name" => $row->work_type_name,
                            "employee_time_in_id" => $row->employee_time_in_id,
                            "is_migrated" => $is_migrated,
                            "time_in_status" => $row->time_in_status,
                            "location" => $row->location,
                            "reason" => $row->reason,
                            "has_change_sched" => $color_warning,
                            "is_resolved" => $is_resolved,
                            "suppose_to" => $shift_has_ended
                        );
                        array_push($final_result, $temp_res);
                    }
                }
                return ($final_result) ? $final_result : FALSE;
            }

        }else{
            return false;

        }
    }

    public function all_current_timesheet_list($company_id,$emp_id,$num_rows=false,$page="",$limit="",$date_from="",$date_to=""){
        $final_result = array();
        if(is_numeric($company_id)){
            
            $this->db->order_by('eti.time_in','DESC');
            $where = array(
                'eti.comp_id'           => $company_id,
                'eti.status'            => 'Active',
                'edrt.parent_emp_id'    => $emp_id
            );

            if ($date_from) {
                $this->db->where('eti.date', $date_from);
            } else {
                $this->db->where('eti.date', date("Y-m-d"));
            }

            $this->db->where($where);
            $this->db->where(" (eti.time_in_status ='pending' OR eti.time_in_status ='approved' OR eti.time_in_status IS NULL) ",NULL,FALSE);

            $arr = array(
                'lunch_out'             => 'eti.lunch_out',
                'lunch_in'              => 'eti.lunch_in',
                'time_out'              => 'eti.time_out',
                'time_in'               => 'eti.time_in',
                'total_hours_required'  => 'eti.total_hours_required',
                'total_hours'           => 'eti.total_hours',
                'tardiness_min'         => 'eti.tardiness_min',
                'undertime_min'         => 'eti.undertime_min',
                'absent_min'            => 'eti.absent_min',
                'source'                => 'eti.source',
                'date'                  => 'eti.date',
                'payroll_cloud_id'      => 'a.payroll_cloud_id',
                'work_schedule_id'      => 'eti.work_schedule_id',
                'employee_time_in_id'   => 'eti.employee_time_in_id',
                'comp_id'               => 'eti.comp_id',
                'first_name'            => 'e.first_name',
                'last_name'             => 'e.last_name',
                'emp_id'                => 'eti.emp_id',
                'account_id'            => 'a.account_id',
                'location'              => 'eti.location',
                'profile_image'         => 'a.profile_image',
                'break1_out'            => 'eti.break1_out',
                'break1_in'             => 'eti.break1_in',
                'break2_out'            => 'eti.break2_out',
                'break2_in'             => 'eti.break2_in',
                'late_min'              => 'eti.late_min',
                'overbreak_min'         => 'eti.overbreak_min',
                'time_in_status'        => 'eti.time_in_status',
                'last_source'           => 'eti.last_source',
                'change_log_date_filed' => 'eti.change_log_date_filed',
                'break_rules'           => 'ws.break_rules',
                'name'                  => 'ws.name',
                'work_type_name'        => 'ws.work_type_name',
                'reason'                => 'eti.reason'
            );
            $this->edb->select($arr);
            $this->edb->join('employee AS e','e.emp_id = eti.emp_id',"LEFT");
            $this->edb->join('accounts AS a','a.account_id = e.account_id',"LEFT");
            $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = eti.emp_id","LEFT");
            $this->edb->join("work_schedule AS ws","ws.work_schedule_id = eti.work_schedule_id","LEFT");

            if($num_rows == true) {
                $query = $this->edb->get('employee_time_in AS eti');
                return $query->num_rows();
            }else{
                $query = $this->edb->get('employee_time_in AS eti',$limit,$page);
                $result = $query->result();
                $is_migrated = for_list_tardiness_rule_migrated_v3($company_id);

                if($result){
                    $emp_ids = array();
                    foreach($result as $app) {
                        $emp_ids[] = $app->emp_id;
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

                    foreach($result as $row){

                        $sources = explode(",", $row->source);
                        $source_val = '';
                        $time_in_s      = "";
                        $lunch_out_s    = "";
                        $lunch_in_s     = "";
                        $break1_out_s   = "";
                        $break1_in_s    = "";
                        $break2_out_s   = "";
                        $break2_in_s    = "";
                        $time_out_s     = "";

                        if ($sources) {
                            $source_val = explode("-", $sources[0]);
                            
                            foreach ($sources as $key => $value) {
                                $classify = explode("-", $value);

                                if ($classify) {
                                    $s_type = '';
                                    foreach ($classify as $ind => $val) {
                                        
                                        if ($ind == 0) {
                                            if ($val == 'kiosk') {
                                                $s_type = '(K)';
                                            } elseif ($val == 'desktop') {
                                                $s_type = '(D)';
                                            } elseif ($val == 'mobile') {
                                                $s_type = '(M)';
                                            }
                                        }

                                        if ($ind != 0) {
                                            if($val == "time in"){
                                                $time_in_s = $s_type;
                                            }
                                            if($val == "time out"){
                                                $time_out_s = $s_type;
                                            }
                                            if($val == "lunch out"){
                                                $lunch_out_s = $s_type;
                                            }
                                            if($val == "lunch in"){
                                                $lunch_in_s = $s_type;
                                            }
                                            if($val == "break1 out"){
                                                $break1_out_s = $s_type;
                                            }
                                            if($val == "break1 in"){
                                                $break1_in_s = $s_type;
                                            }
                                            if($val == "break2 out"){
                                                $break2_out_s = $s_type;
                                            }
                                            if($val == "break2 in"){
                                                $break2_in_s = $s_type;
                                            }
                                        }
                                    }
                                }
                            }
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

                        $temp_res = array(
                            "base_url" => base_url(),
                            "emp_id" => $row->emp_id,
                            "company_id" => $company_id,
                            "account_id" => $row->account_id,
                            "first_name" => $row->first_name,
                            "last_name" => $row->last_name,
                            "payroll_cloud_id" => $row->payroll_cloud_id,
                            "profile_image" => $row->profile_image,
                            "total_hours" => $row->total_hours,
                            "time_in_ws" => ($row->time_in) ? date('h:i a', strtotime($row->time_in)) . ' ' . $time_in_s : '',
                            "time_out_ws" => ($row->time_out) ? date('h:i a', strtotime($row->time_out)) . ' ' . $time_out_s : '',
                            "lunch_out_ws" => ($row->lunch_out) ? date('h:i a', strtotime($row->lunch_out)) . ' ' . $lunch_out_s : '',
                            "lunch_in_ws" => ($row->lunch_in) ? date('h:i a', strtotime($row->lunch_in)) . ' ' . $lunch_in_s : '',
                            "time_in" => ($row->time_in) ? date('h:i a', strtotime($row->time_in)) : '',
                            "time_out" => ($row->time_out) ? date('h:i a', strtotime($row->time_out)) : '',
                            "lunch_out" => ($row->lunch_out) ? date('h:i a', strtotime($row->lunch_out)) : '',
                            "lunch_in" => ($row->lunch_in) ? date('h:i a', strtotime($row->lunch_in)) : '',
                            "total_hours_required" => $row->total_hours_required,
                            "date" => $row->date,
                            "source" => $source_val[0],
                            "tardiness_min" => $row->tardiness_min,
                            "undertime_min" => $row->undertime_min,
                            "absent_min" => $row->absent_min,
                            "overbreak_min" => $row->overbreak_min,
                            "location" => $row->location,
                            "break1_out" => ($row->break1_out) ? date('h:i a', strtotime($row->break1_out)) . ' ' . $break1_out_s : '',
                            "break1_in" => ($row->break1_out) ? date('h:i a', strtotime($row->break1_in)) . ' ' . $break1_in_s : '',
                            "break2_out" => ($row->break2_out) ? date('h:i a', strtotime($row->break2_out)) . ' ' . $break2_out_s : '',
                            "break2_in" => ($row->break2_in) ? date('h:i a', strtotime($row->break2_in)) . ' ' . $break2_in_s : '',
                            "late_min" => $row->late_min,
                            "last_source" => $row->last_source,
                            "change_log_date_filed" => $row->change_log_date_filed,
                            "break_rules" => $row->break_rules,
                            "work_sched_name" => $row->name,
                            "work_type_name" => $row->work_type_name,
                            "employee_time_in_id" => $row->employee_time_in_id,
                            "is_migrated" => $is_migrated,
                            "time_in_status" => $row->time_in_status,
                            "location" => $row->location,
                            "reason" => $row->reason,
                            "has_change_sched" => $color_warning,
                            "is_resolved" => $is_resolved,
                            "suppose_to" => $shift_has_ended
                        );
                        array_push($final_result, $temp_res);
                    }
                }
                return ($final_result) ? $final_result : FALSE;
            }

        }else{
            return false;

        }
    }

}