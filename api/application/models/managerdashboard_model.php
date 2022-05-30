<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ManagerDashboard_model extends CI_Model {

    public function count_clocked_in($company_id, $work_schedule_id, $emp_id){
        $counter = 0;

        $get_date = $this->input->get("date");
        $get_time = $this->input->get("time");

        $current_time = ($get_time == "") ? date("H:i:s") : date("H:i:s",strtotime($get_time));
        $today = ($get_date == "") ? date("Y-m-d") : date("Y-m-d",strtotime($get_date));
        $yesterday = date("Y-m-d",strtotime("{$today} -1 day"));

        $where = array(
            "eti.comp_id" => $company_id,
            "eti.work_schedule_id" => $work_schedule_id,
            "edrt.parent_emp_id" => $emp_id
        );

        $this->db->where($where);
        $this->db->where("(eti.date = '{$today}' OR eti.date = '{$yesterday}') AND eti.time_out IS NULL");
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = eti.emp_id","LEFT");
        $query = $this->db->get("employee_time_in AS eti");
        $result = $query->result();

        if($result){
            // regular work sched
            $reg_array = array();
            $reg_where = array(
                "company_id" => $company_id
            );
            $this->db->where($reg_where);
            $reg_query = $this->db->get("regular_schedule");
            $reg_result = $reg_query->result();
            if($reg_result){
                foreach ($reg_result as $row){
                    array_push($reg_array, array(
                        "work_end_time" => $row->work_end_time,
                        "work_start_time" => $row->work_start_time,
                        "days_of_work" => $row->days_of_work,
                        "custom_flag" => "flag_{$row->work_schedule_id}_".date("l",strtotime($row->days_of_work))
                    ));
                }
            }

            // flexible
            $flex_array = array();
            $flex_where = array(
                "company_id" => $company_id
            );
            $this->db->where($flex_where);
            $flex_query = $this->db->get("flexible_hours");
            $flex_result = $flex_query->result();
            if($flex_result){
                foreach($flex_result as $row){
                    array_push($flex_array, array(
                        "not_required_login" => $row->not_required_login,
                        "total_hours_for_the_day" => $row->total_hours_for_the_day,
                        "latest_time_in_allowed" => $row->latest_time_in_allowed,
                        "custom_flag" => "flag_{$row->work_schedule_id}"
                    ));
                }
            }

            // workshift
            $ws_array = array();
            $ws_where = array(
                "company_id" => $company_id
            );
            $this->db->where($ws_where);
            $ws_query = $this->db->get("schedule_blocks");
            $ws_result = $ws_query->result();
            if($ws_result){
                foreach ($ws_result as $row){
                    array_push($ws_array, array(
                        "start_time" => $row->start_time,
                        "end_time" => $row->end_time,
                        "custom_flag" => "flag_{$row->work_schedule_id}"
                    ));
                }
            }

            foreach ($result as $row){
                $work_schedule_id = $row->work_schedule_id;
                if($work_schedule_id != "" || $work_schedule_id != 0){
                    $workday = get_workday($company_id, $work_schedule_id);

                    if($workday != FALSE){
                        if($workday->workday_type == "Uniform Working Days"){
                            /* $w = array(
		                     "work_schedule_id" => $work_schedule_id,
		                     "days_of_work" => date("l",strtotime($row->date))
		                     );
		                     $this->db->where($w);
		                     $query = $this->db->get("regular_schedule");
		                     $r = $query->row(); */

                            $rs = in_array_custom("flag_{$row->work_schedule_id}_".date("l",strtotime($row->date)), $reg_array);
                            $r = ($rs != false) ? $rs : false;
                            if($r){
                                // same date
                                if(strtotime($row->date) == strtotime($today)){
                                    if(date("A",strtotime($r->work_end_time)) == "PM") {
                                        // ends this day
                                        $end_time = date("Y-m-d H:i:s",strtotime("{$today} {$r->work_end_time}"));
                                    }
                                    else{
                                        // ends next day
                                        $end_time = date("Y-m-d H:i:s",strtotime("{$today} {$r->work_end_time} +1 day"));
                                    }

                                    if(date("Y-m-d H:i:s",strtotime("{$today} {$current_time}")) <= $end_time){
                                        $counter = $counter + 1;
                                    }
                                }

                                // next day 12:01AM
                                if(date("A",strtotime($r->work_start_time)) == "PM" && date("A",strtotime($r->work_end_time)) == "AM" && date("A",strtotime($current_time)) == "AM"){
                                    if(strtotime($current_time) <= strtotime($r->work_end_time)){
                                        $counter = $counter + 1;
                                    }
                                }
                            }
                        }
                        elseif ($workday->workday_type == "Flexible Hours"){
                            /* $w = array(
		                     "work_schedule_id" => $work_schedule_id
		                     );
		                     $this->db->where($w);
		                     $query = $this->db->get("flexible_hours");
		                     $r = $query->row(); */

                            $rs = in_array_custom("flag_{$row->work_schedule_id}", $flex_array);
                            $r = ($rs != false) ? $rs : false;
                            if($r){
                                if($r->not_required_login != 1){
                                    $hours_per_day = $r->total_hours_for_the_day;
                                    $hours = floor($hours_per_day);
                                    $mins = round(60 * ($hours_per_day - $hours));

                                    $start_time = date("H:i:s",strtotime($r->latest_time_in_allowed));
                                    $end_time = date("H:i:s",strtotime("{$start_time} +{$hours} hours +{$mins} minutes"));

                                    // same date
                                    if(strtotime($row->date) == strtotime($today)){
                                        if(date("A",strtotime($end_time)) == "PM") {
                                            // ends this day
                                            $end_time = date("Y-m-d H:i:s",strtotime("{$today} {$end_time}"));
                                        }
                                        else{
                                            // ends next day
                                            $end_time = date("Y-m-d H:i:s",strtotime("{$today} {$end_time} +1 day"));
                                        }

                                        if(date("Y-m-d H:i:s",strtotime("{$today} {$current_time}")) <= $end_time){
                                            $counter = $counter + 1;
                                        }
                                    }

                                    // next day 12:01AM
                                    if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM" && date("A",strtotime($current_time)) == "AM"){
                                        if(strtotime($current_time) <= strtotime($end_time)){
                                            $counter = $counter + 1;
                                        }
                                    }
                                }
                            }
                        }
                        elseif ($workday->workday_type == "Workshift"){
                            /* $w = array(
		                     "work_schedule_id" => $work_schedule_id
		                     );
		                     $this->db->where($w);
		                     $query = $this->db->get("schedule_blocks");
		                     $r = $query->row(); */

                            $rs = in_array_custom("flag_{$row->work_schedule_id}", $ws_array);
                            $r = ($rs != false) ? $rs : false;
                            if($r){
                                $start_time = date("H:i:s",strtotime($r->start_time));
                                $end_time = date("H:i:s",strtotime($r->end_time));

                                // same date
                                if(strtotime($row->date) == strtotime($today)){
                                    if(date("A",strtotime($end_time)) == "PM") {
                                        $end_time = date("Y-m-d H:i:s",strtotime($end_time." -1 day"));
                                    }
                                    else{
                                        $end_time = date("Y-m-d H:i:s",strtotime($end_time));
                                    }

                                    if(date("Y-m-d H:i:s",strtotime("{$today} {$current_time}")) <= $end_time){
                                        $counter = $counter + 1;
                                    }
                                }

                                // next day 12:01AM
                                if(date("A",strtotime($start_time)) == "PM" && date("A",strtotime($end_time)) == "AM" && date("A",strtotime($current_time)) == "AM"){
                                    if(strtotime($current_time) <= strtotime($end_time)){
                                        $counter = $counter + 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $counter;
    }

    public function get_current_schedule($emp_id,$company_id){
        $schedule = array();
        $result = $this->get_schedule_for_tile($emp_id,$company_id);

        /*$where = array(
			"comp_id" => $this->company_id
		);
		$this->db->where($where);
		$query = $this->db->get("work_schedule");
		$result = $query->result();*/



        if($result){
            foreach ($result as $row){
                $work_type = $row->work_type_name;
                $work_schedule_id = $row->work_schedule_id;
                $work_schedule_name = $row->name;
                switch ($work_type){
                    case "Uniform Working Days" :
                        $w = array(
                            "company_id" => $company_id,
                            "work_schedule_id" => $work_schedule_id,
                            //"work_start_time <=" => date("H:i:s"),
                            //"work_end_time >=" => date("H:i:s"),
                            "days_of_work" => date("l")
                        );

                        $this->db->where($w);
                        $q = $this->db->get("regular_schedule");
                        $r = $q->result();
                        if($r){
                            foreach ($r as $reg){
                                $from_date = date("Y-m-d");
                                $to_date = date("Y-m-d");

                                if(date("A", strtotime($reg->work_start_time)) == "PM" && date("A", strtotime($reg->work_end_time)) == "AM" && date("A") == "AM"){
                                    $from_date = date("Y-m-d",strtotime("-1 day"));
                                }

                                $start = date("Y-m-d H:i:s",strtotime("{$from_date} {$reg->work_start_time}"));
                                $end = date("Y-m-d H:i:s",strtotime("{$to_date} {$reg->work_end_time}"));

                                $ars = array(
                                    "name" => $work_schedule_name,
                                    "start" => $start,
                                    "end" => $end,
                                    "work_schedule_id" => $work_schedule_id
                                );
                                if(date("Y-m-d H:i:s",strtotime($ars["start"])) <= date("Y-m-d H:i:s")){
                                    array_push($schedule, (object) $ars);
                                }
                            }
                        }

                        break;
                    case "Flexible Hours" :
                        /* $this->db->select("*","latest_time_in_allowed AS start_time, ADDTIME(latest_time_in_allowed,CONCAT(total_hours_for_the_day,':00:00')) AS end_time",FALSE);
							$time = date("H:i:s");
							$start_time_man_date = date("Y-m-d");
							$end_time_man_date = date("Y-m-d",strtotime("+1 day"));
							
							// ADD
							$this->db->where("(
									(
										latest_time_in_allowed <= '{$time}'
										AND
										ADDTIME(latest_time_in_allowed,CONCAT(total_hours_for_the_day,':00:00')) >= '{$time}'
									)
										OR
									(
										DATE_FORMAT(latest_time_in_allowed, '{$start_time_man_date} %H:%i:%s') <= '{$start_time_man_date} {$time}'
										AND
										DATE_FORMAT(ADDTIME(latest_time_in_allowed,CONCAT(total_hours_for_the_day,':00:00')), '{$end_time_man_date} %H:%i:%s') >= '{$start_time_man_date} {$time}'
										AND
										DATE_FORMAT(latest_time_in_allowed, '%p') = 'PM'
										AND
										DATE_FORMAT(ADDTIME(latest_time_in_allowed,CONCAT(total_hours_for_the_day,':00:00')), '%p') = 'AM'
									)
								)
							"); */

                        $this->db->select("*,latest_time_in_allowed AS start_time, ADDTIME(latest_time_in_allowed,CONCAT(total_hours_for_the_day,':00:00')) AS end_time",FALSE);

                        $w = array(
                            "company_id" => $company_id,
                            "work_schedule_id" => $work_schedule_id,
                            "not_required_login" => "0",
                            //"latest_time_in_allowed <=" => date("H:i:s")
                        );
                        $this->db->where($w);

                        $q = $this->db->get("flexible_hours");
                        $r = $q->result();
                        if($r){

                            /* foreach ($r as $flex){
								$ars = array(
									"name" => $work_schedule_name,
									"start" => $flex->latest_time_in_allowed,
									"end" => $flex->end_time,
									"work_schedule_id" => $flex->work_schedule_id
								);
								array_push($schedule, (object) $ars);
							} */

                            foreach ($r as $flex){
                                $from_date = date("Y-m-d");
                                $to_date = date("Y-m-d");

                                $n_end_tal = $flex->total_hours_for_the_day;
                                $n_hour = floor($n_end_tal);
                                $n_min = round(60*($n_end_tal-$n_hour));

                                $start = date("Y-m-d H:i:s",strtotime("{$from_date} {$flex->latest_time_in_allowed}"));
                                $time_end = date("H:i:s",strtotime("{$start} +{$n_hour} hours +{$n_min} minute"));

                                if(date("A", strtotime($start)) == "PM" && date("A", strtotime($time_end)) == "AM" && date("A") == "AM"){
                                    $from_date = date("Y-m-d",strtotime("-1 day"));
                                    $start = date("Y-m-d H:i:s",strtotime("{$from_date} {$flex->latest_time_in_allowed}"));
                                }

                                $end = date("Y-m-d H:i:s",strtotime("{$to_date} {$time_end}"));
                                $ars = array(
                                    "name" => $work_schedule_name,
                                    "start" => $start,
                                    "end" => $end,
                                    // "end" => date("H:i:s",strtotime("{$start} +{$flex->total_hours_for_the_day} hours")),
                                    #"end" => $flex->end_time,
                                    "work_schedule_id" => $work_schedule_id
                                );
                                if(date("Y-m-d h:i:s") < $end){
                                    if(date("Y-m-d H:i:s",strtotime($ars["start"])) <= date("Y-m-d H:i:s")){
                                        array_push($schedule, (object) $ars);
                                    }
                                }
                            }
                        }

                        break;
                    case "Workshift" :


                        break;
                }
            }
        }

        // sort to get closest start time
        usort($schedule, function($a, $b){
            return strcmp($b->start, $a->start);
        });

        return (count($schedule) > 0) ? $schedule[0] : false;
    }

    public function get_schedule_for_tile($emp_id,$company_id) {
        $result = array();
        $where = array(
            "ess.company_id" => $company_id,
            "ess.status" => "Active",
            "ess.valid_from" => date('Y-m-d'),
            "ess.until" => date('Y-m-d'),
            "edrt.parent_emp_id" => $emp_id,
            "ess.payroll_group_id" => 0
        );

        $this->db->where($where);
        $this->db->group_by("ess.work_schedule_id");
        $this->db->join("work_schedule AS ws","ws.work_schedule_id = ess.work_schedule_id","LEFT");
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = ess.emp_id","LEFT");
        $q = $this->db->get("employee_shifts_schedule AS ess");
        $r = $q->result();

        if($r) {
            return ($r) ? $r : FALSE;
        } else {
            $emp_where = array(
                "epi.company_id" => $company_id,
                "epi.status" => "Active",
                'edrt.parent_emp_id' => $emp_id
            );
            $this->db->where($emp_where);
            $this->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
            $this->db->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
            $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
            $this->db->group_by("pg.work_schedule_id");
            $query = $this->db->get("employee_payroll_information AS epi");
            $row = $query->result();

            if($row) {
                foreach ($r as $zz) {
                    $temp_array = array(
                        "work_schedule_id" => $zz->work_schedule_id,
                        "emp_id" => $zz->emp_id
                    );

                    array_push($result, $temp_array);
                }
            }
            return ($row) ? $row : FALSE;
        }
    }

    public function count_no_show($company_id, $work_schedule_id, $emp_id){
        $counter = 0;
        $is_holiday = $this->check_if_holiday($company_id);
        $filter_work_schedule_id = $work_schedule_id;
        $today = date("Y-m-d");

        $where = array(
            "e.company_id" => $company_id,
            "e.status" => "Active",
            "epi.employee_status" => "Active",
            "epi.timesheet_required" => "yes",
            "epi.status" => "Active",
            "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($where);
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
        $this->db->group_by("e.emp_id");
        $query = $this->edb->get("employee AS e");
        $result = $query->result();

        if($result){
            // get employees time ins start
            $date = $today;
            $w = array(
                "date" => $date,
                "status" => "Active",
                "comp_id" => $company_id
            );
            $this->db->where($w);
            $time_in = $this->db->get("employee_time_in");
            $tr = $time_in->result();

            $today_time_ins = array();
            if($tr){
                foreach($tr as $rr){
                    array_push($today_time_ins, array(
                        "emp_id" => $rr->emp_id,
                    ));
                }
            }

            // uniform working days
            $uwd_array = array();
            $uwd_where = array(
                "company_id" => $company_id,
                "days_of_work" => date("l")
            );
            $this->db->where("((work_start_time <= '".date("H:i:s")."' AND work_end_time >= '".date("H:i:s")."')
				OR
				(
					(DATE_FORMAT(work_start_time, '".date("Y-m-d")." %H:%i:%s') <= '".date("Y-m-d H:i:s")."' AND DATE_FORMAT(work_end_time, '".date("Y-m-d",strtotime("+1 day"))." %H:%i:%s') >= '".date("Y-m-d H:i:s")."')
					OR
					(DATE_FORMAT(work_start_time, '".date("Y-m-d",strtotime("-1 day"))." %H:%i:%s') <= '".date("Y-m-d H:i:s")."' AND DATE_FORMAT(work_end_time, '".date("Y-m-d")." %H:%i:%s') >= '".date("Y-m-d H:i:s")."')
				)
			)");
            $this->db->where($uwd_where);
            $uwd_query = $this->db->get("regular_schedule");
            $uwd_result = $uwd_query->result();
            if($uwd_result){
                foreach($uwd_result as $row){
                    array_push($uwd_array, array(
                        "work_start_time" => $row->work_start_time,
                        "work_end_time" => $row->work_end_time,
                        "latest_time_in_allowed" => $row->latest_time_in_allowed,
                        "custom_flag" => "flag_{$row->work_schedule_id}"
                    ));
                }
            }

            // flexible hours
            $flex_array = array();
            $flex_where = array(
                "not_required_login" => 0,
                "company_id" => $company_id
            );
            $this->db->where($flex_where);
            $this->db->where("latest_time_in_allowed IS NOT NULL");
            $flex_query = $this->db->get("flexible_hours");
            $flex_result = $flex_query->result();
            if($flex_result){
                foreach ($flex_result as $row){
                    array_push($flex_array, array(
                        "total_hours_for_the_day" => $row->total_hours_for_the_day,
                        "latest_time_in_allowed" => $row->latest_time_in_allowed,
                        "custom_flag" => "flag_{$row->work_schedule_id}"
                    ));
                }
            }

            // workshift
            $ws_array = array();
            $ws_where = array(
                "company_id" => $company_id
            );
            $this->db->where($ws_where);
            $ws_query = $this->db->get("schedule_blocks");
            $ws_result = $ws_query->result();
            if($ws_result){
                foreach($ws_result as $row){
                    array_push($flex_array, array(
                        "start_time" => $row->start_time,
                        "end_time" => $row->end_time,
                        "custom_flag" => "flag_{$row->work_schedule_id}"
                    ));
                }
            }

            // shift schedule : custom
            $emp_sched = array();
            $w_date = array(
                "valid_from >="	=> date("Y-m-d"),
                "until <=" => date("Y-m-d")
            );
            $this->db->where($w_date);
            $w = array(
                #"emp_id"=>$emp_id,
                "company_id" => $company_id,
                "payroll_group_id" => 0, // add
                "status" => "Active"
            );
            $this->db->where($w);
            $q = $this->db->get("employee_shifts_schedule");
            $r = $q->result();
            if($r){
                foreach($r as $row){
                    $emp_day_sched = array(
                        "date" => $row->valid_from,
                        "work_schedule_id" => $row->work_schedule_id,
                        "date_emp_id" => "{$row->valid_from}ashima{$row->emp_id}", // add
                        "emp_id" => $row->emp_id
                    );
                    array_push($emp_sched,$emp_day_sched);
                }
            }
            $shift_schedule = $emp_sched;

            foreach($result as $row){
                $emp_id = $row->emp_id;
                #$custom_sched = check_employee_custom_schedule($row->emp_id);
                #$work_schedule_id = ($custom_sched) ? $custom_sched->work_schedule_id : $row->work_schedule_id;

                // CUSTOM WORK SCHEDULE
                $if_custom_work_schedule = in_array_custom(date("Y-m-d")."ashima{$row->emp_id}",$shift_schedule);
                $work_schedule_id = ($if_custom_work_schedule != FALSE) ? $if_custom_work_schedule->work_schedule_id : $row->work_schedule_id ;

                $workday = get_workday($company_id, $work_schedule_id);

                // employee time ins
                $date = $today;
                /* $w = array(
	             "emp_id" => $emp_id,
	             "date" => $date,
	             "status" => "Active"
	             );
	             $this->db->where($w);
	             $time_in = $this->db->get("employee_time_in");
	             $time_in_row = $time_in->row(); */
                $check_time_in = in_array_custom($emp_id, $today_time_ins);
                $time_in_row = ($check_time_in != false) ? true : false;

                if($workday != FALSE){
                    if($workday->workday_type == "Uniform Working Days"){
                        /* $w = array(
	                     "company_id" => $company_id,
	                     "work_schedule_id" => $work_schedule_id,
	                     "days_of_work" => date("l")
	                     );
	                     $this->db->where("((work_start_time <= '".date("H:i:s")."' AND work_end_time >= '".date("H:i:s")."')
	                     OR
	                     (
	                     (DATE_FORMAT(work_start_time, '".date("Y-m-d")." %H:%i:%s') <= '".date("Y-m-d H:i:s")."' AND DATE_FORMAT(work_end_time, '".date("Y-m-d",strtotime("+1 day"))." %H:%i:%s') >= '".date("Y-m-d H:i:s")."')
	                     OR
	                     (DATE_FORMAT(work_start_time, '".date("Y-m-d",strtotime("-1 day"))." %H:%i:%s') <= '".date("Y-m-d H:i:s")."' AND DATE_FORMAT(work_end_time, '".date("Y-m-d")." %H:%i:%s') >= '".date("Y-m-d H:i:s")."')
	                     )
	                     )");
	                     $this->db->where($w);
	                     $query = $this->db->get("regular_schedule");
	                     $r = $query->row(); */

                        $rs = in_array_custom("flag_{$work_schedule_id}", $uwd_array);
                        $r = ($rs != false) ? $rs : false;
                        if($r){
                            //$date = $today;
                            $is_night_shift = false;
                            if(date("A", strtotime($r->work_start_time)) == "PM" && date("A", strtotime($r->work_end_time)) == "AM"){
                                $date = date("Y-m-d",strtotime("{$today} -1 day"));
                                $is_night_shift = true;
                            }
                            //$w = array(
                            //	"emp_id" => $emp_id,
                            //	"date" => $date,
                            //	"status" => "Active"
                            //);
                            //$this->db->where($w);
                            //$time_in = $this->db->get("employee_time_in");
                            //$time_in_row = $time_in->row();
                            if(!$time_in_row){
                                $curr_y = date("Y");
                                $curr_m = date("m");
                                $curr_d = date("d");
                                $curr_dt = date("Y-m-d H:i:s");
                                $start_time = date("Y-m-d H:i:s",strtotime("{$curr_y}-{$curr_m}-{$curr_d} {$r->work_start_time}"));
                                $end_time = date("Y-m-d H:i:s",strtotime("{$curr_y}-{$curr_m}-{$curr_d} {$r->work_end_time}"));

                                if($is_night_shift && date("H:i:s",strtotime($start_time)) <= date("H:i:s",strtotime($curr_dt))){
                                    $curr_dt = date("Y-m-d H:i:s",strtotime("{$curr_dt} -1 day"));
                                }
                                if($is_night_shift){
                                    $start_time = date("Y-m-d H:i:s",strtotime("{$start_time} -1 day"));
                                }


                                if($start_time != "" && $end_time != ""){
                                    $grace = " +{$r->latest_time_in_allowed} minutes";
                                    if(date("Y-m-d H:i:s",strtotime($start_time."".$grace)) <= date("Y-m-d H:i:s",strtotime($curr_dt)) && date("Y-m-d H:i:s",strtotime($curr_dt)) <= date("Y-m-d H:i:s",strtotime($end_time))){
                                        if(!check_employee_on_leave($row->emp_id)){
                                            if($filter_work_schedule_id != ""){
                                                if($work_schedule_id == $filter_work_schedule_id){
                                                    $counter = $counter + 1;
                                                }
                                            }
                                            else{
                                                $counter = $counter + 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    else if($workday->workday_type == "Flexible Hours"){
                        /* $w = array(
	                     "not_required_login"=>0,
	                     "company_id" => $company_id,
	                     "work_schedule_id"=>$work_schedule_id
	                     );
	                     $this->db->where($w);
	                     $this->db->where("latest_time_in_allowed IS NOT NULL");
	                     $q = $this->db->get("flexible_hours");
	                     $r = $q->row(); */

                        $rs = in_array_custom("flag_{$work_schedule_id}", $flex_array);
                        $r = ($rs != false) ? $rs : false;
                        if($r){
                            $emp_id = $row->emp_id;
                            //$w = array(
                            //	"emp_id" => $emp_id,
                            //	"date" => date("Y-m-d"),
                            //	"status" => "Active"
                            //);
                            //$this->db->where($w);
                            //$time_in = $this->db->get("employee_time_in");
                            //$time_in_row = $time_in->row();

                            if(!$time_in_row){
                                $hours_per_day = $r->total_hours_for_the_day;
                                $hours = floor($hours_per_day);
                                $mins = round(60 * ($hours_per_day - $hours));

                                $start_time = $r->latest_time_in_allowed;
                                $end_time = date("H:i:s",strtotime($start_time." +{$hours} hours +{$mins} minutes"));
                                if($start_time != "" && $end_time != ""){
                                    if(strtotime($start_time) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
                                        if(!check_employee_on_leave($row->emp_id)){
                                            if($filter_work_schedule_id != ""){
                                                if($work_schedule_id == $filter_work_schedule_id){
                                                    $counter = $counter + 1;
                                                }
                                            }
                                            else{
                                                $counter = $counter + 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    else if($workday->workday_type == "Workshift"){
                        /* $w = array(
	                     "company_id" => $company_id,
	                     "work_schedule_id" => $work_schedule_id
	                     );
	                     $this->db->where($w);
	                     $q = $this->db->get("schedule_blocks");
	                     $r = $q->row(); */

                        $rs = in_array_custom("flag_{$work_schedule_id}", $ws_array);
                        $r = ($rs != false) ? $rs : false;
                        if($r){
                            $emp_id = $row->emp_id;
                            //$w = array(
                            //	"emp_id" => $emp_id,
                            //	"date" => date("Y-m-d"),
                            ///	"status" => "Active"
                            //);
                            //$this->db->where($w);
                            //$time_in = $this->db->get("employee_time_in");
                            //$time_in_row = $time_in->row();

                            if(!$time_in_row){
                                $start_time = $r->start_time;
                                $end_time = $r->end_time;
                                if($start_time != "" && $end_time != ""){
                                    if(strtotime($start_time) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
                                        if(!check_employee_on_leave($row->emp_id)){
                                            if($filter_work_schedule_id != ""){
                                                if($work_schedule_id == $filter_work_schedule_id){
                                                    $counter = $counter + 1;
                                                }
                                            }
                                            else{
                                                $counter = $counter + 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

            }
        }
        return (!$is_holiday) ? $counter : "0";
    }

    public function get_employee_list_noshow($company_id, $emp_id, $limit = 10, $start = 0, $search = "", $sort_by = "", $sort = "ASC", $filter=""){
        // $konsum_key = konsum_key();
        $sort_array = array(
            "e.last_name",
            "d.department_name",
            "p.position_name"
        );
        $where = array(
            "e.company_id" => $company_id,
            "e.status" => "Active",
            "epi.employee_status" => "Active",
            "epi.timesheet_required" => "yes",
            "epi.status" => "Active",
            "edrt.parent_emp_id" => $emp_id
        );
        $select = array(
                "e.first_name AS first_name",
                "e.last_name AS last_name",
                "a.payroll_cloud_id AS payroll_cloud_id",
                "e.emp_id",
                "pg.work_schedule_id",
                "a.profile_image",
                "rs.work_start_time",
                "rs.work_end_time",
            );
        $select3 = array(
                "e.first_name AS first_name",
                "e.last_name AS last_name",
                "a.payroll_cloud_id AS payroll_cloud_id",
                "e.emp_id",
                "pg.work_schedule_id",
                "a.profile_image",
            );
        
        $this->edb->select($select3);
        $this->db->where($where);
        $this->db->where_in("e.emp_id",$this->filter_employee2($company_id, $emp_id, $search, $filter));
        // if($search != "" && $search != "all"){
        //     $this->db->where("(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}') LIKE '%".$search."%' OR CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) LIKE '%".$search."%')", NULL, FALSE);
        // }
        $this->edb->select($select);
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
        $this->edb->join("regular_schedule AS rs","rs.work_schedule_id = ws.work_schedule_id","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        $this->edb->join("employee_shifts_schedule AS ess","ess.emp_id = e.emp_id","LEFT");
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
        // $this->edb->select($select);
        // $this->db->select(array("epi.payroll_group_id AS pg_id","pg.work_schedule_id AS work_sched_id"));
        // $this->edb->select(array("*","e.emp_id AS emp_id"));
        // if($sort_by != ""){
        //     if(in_array($sort_by, $sort_array)){
        //         if($sort_by == "e.last_name"){
        //             $this->edb->order_by($sort_by,$sort);
        //         }
        //         else{
        //             $this->db->order_by($sort_by,$sort);
        //         }
        //     }
        // }
        // else{
        //     $this->edb->order_by("e.last_name","ASC");
        // }
        $this->db->group_by("e.emp_id");
        $query = $this->edb->get("employee AS e",$limit, $start);
        $result = $query->result();

        $ret_res = array();
        if($result) {
            foreach ($result as $row) {
                $temp_array = array(
                    "first_name" => $row->first_name,
                    "last_name" => $row->last_name,
                    "payroll_cloud_id" => $row->payroll_cloud_id,
                    "emp_id" => $row->emp_id,
                    "work_schedule_id" => $row->work_schedule_id,
                    "profile_image" => ($row->profile_image) ? base_url().'/uploads/companies/'.$company_id.'/'.$row->profile_image : '',
                    "base_url" => base_url(),
                    "work_start_time" => ($row->work_start_time) ? date("h:i A", strtotime($row->work_start_time)) : '',
                    "work_end_time" => ($row->work_end_time) ? date("h:i A", strtotime($row->work_end_time)) : ''
                );

                array_push($ret_res, $temp_array);
            }
        }
        
        return ($ret_res) ? $ret_res : false;
    }

    public function filter_employee2($company_id, $emp_id, $search = "", $filter=""){
        $filter_work_schedule_id = '';//$filter["work_schedule_id"];
        $today = date("Y-m-d");
        $where = array(
            "e.company_id" => $company_id,
            "e.status" => "Active",
            "epi.employee_status" => "Active",
            "epi.status" => "Active",
            "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($where);
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
        $this->db->group_by("e.emp_id");
        $query = $this->edb->get("employee AS e");
        $result = $query->result();
        $filter_emp_id = array();
    
        if($result){
            foreach($result as $row){
                $emp_id = $row->emp_id;
                $custom_sched = check_employee_custom_schedule($row->emp_id);
                $work_schedule_id = ($custom_sched) ? $custom_sched->work_schedule_id : $row->work_schedule_id;
                $workday = get_workday($company_id,$work_schedule_id);
                
                if($workday != FALSE){
                    switch ($workday->workday_type) {
                        case 'Uniform Working Days':
                            $w = array(
                                "company_id" => $company_id,
                                "work_schedule_id" => $work_schedule_id,
                                ///"work_start_time <= " => date("H:i:s"),
                                ///"work_end_time >= " => date("H:i:s"),
                                "days_of_work" => date("l")
                            );
                            $this->db->where("((work_start_time <= '".date("H:i:s")."' AND work_end_time >= '".date("H:i:s")."')
                                OR
                                (
                                    (DATE_FORMAT(work_start_time, '".date("Y-m-d")." %H:%i:%s') <= '".date("Y-m-d H:i:s")."' AND DATE_FORMAT(work_end_time, '".date("Y-m-d",strtotime("+1 day"))." %H:%i:%s') >= '".date("Y-m-d H:i:s")."')
                                    OR
                                    (DATE_FORMAT(work_start_time, '".date("Y-m-d",strtotime("-1 day"))." %H:%i:%s') <= '".date("Y-m-d H:i:s")."' AND DATE_FORMAT(work_end_time, '".date("Y-m-d")." %H:%i:%s') >= '".date("Y-m-d H:i:s")."')
                                )
                            )");
                            $this->db->where($w);
                            $query = $this->db->get("regular_schedule");
                            $r = $query->row();
                            if($r){
                                $date = $today;
                                $is_night_shift = false;
                                //echo date("A", strtotime($r->work_start_time))." == PM && ".date("A", strtotime($r->work_end_time))." == AM<br>";
                                if(date("A", strtotime($r->work_start_time)) == "PM" && date("A", strtotime($r->work_end_time)) == "AM"){
                                    $date = date("Y-m-d",strtotime("{$today} -1 day"));
                                    $is_night_shift = true;
                                }
                                $w = array(
                                    "emp_id" => $emp_id,
                                    "date" => $date,
                                    "status" => "Active"
                                );
                                $this->db->where($w);
                                $time_in = $this->db->get("employee_time_in");
                                $time_in_row = $time_in->row();
                                //last_query();
                                if(!$time_in_row){
                                    //echo $emp_id."<br>";
                                    $curr_y = date("Y");
                                    $curr_m = date("m");
                                    $curr_d = date("d");
                                    $curr_dt = date("Y-m-d H:i:s");
                                    $start_time = date("Y-m-d H:i:s",strtotime("{$curr_y}-{$curr_m}-{$curr_d} {$r->work_start_time}"));
                                    $end_time = date("Y-m-d H:i:s",strtotime("{$curr_y}-{$curr_m}-{$curr_d} {$r->work_end_time}"));
                                    
                                    if($is_night_shift && date("H:i:s",strtotime($start_time)) <= date("H:i:s",strtotime($curr_dt))){
                                        $curr_dt = date("Y-m-d H:i:s",strtotime("{$curr_dt} -1 day"));
                                    }
                                    if($is_night_shift){
                                        $start_time = date("Y-m-d H:i:s",strtotime("{$start_time} -1 day"));
                                    }
                                    
                                    
                                    if($start_time != "" && $end_time != ""){
                                        $grace = " +{$r->latest_time_in_allowed} minutes";
                                        //echo date("H:i:s",strtotime($start_time."".$grace))."<=".date("H:i:s")."&&.".date("H:i:s")."<=".date("H:i:s",strtotime($end_time))."<br>";
                                        //if(strtotime($start_time."".$grace) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
                                        //echo date("Y-m-d H:i:s",strtotime($start_time."".$grace))." <= ";
                                        //echo date("Y-m-d H:i:s",strtotime($curr_dt))." && ";
                                        //echo date("Y-m-d H:i:s",strtotime($curr_dt))." <= ";
                                        //echo date("Y-m-d H:i:s",strtotime($end_time))."<br>";
                                        
                                        if(date("Y-m-d H:i:s",strtotime($start_time."".$grace)) <= date("Y-m-d H:i:s",strtotime($curr_dt)) && date("Y-m-d H:i:s",strtotime($curr_dt)) <= date("Y-m-d H:i:s",strtotime($end_time))){
                                            if(!check_employee_on_leave($row->emp_id)){
                                                //array_push($filter_emp_id, $row->emp_id);
                                                if($filter_work_schedule_id != ""){
                                                    if($work_schedule_id == $filter_work_schedule_id){
                                                        array_push($filter_emp_id, $row->emp_id);
                                                    }
                                                }
                                                else{
                                                    array_push($filter_emp_id, $row->emp_id);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
    
                            break;
                        case 'Flexible Hours':
                                
                            $w = array(
                                "not_required_login"=>0,
                                "company_id" => $company_id,
                                "work_schedule_id"=>$work_schedule_id
                            );
                            $this->db->where($w);
                            $this->db->where("latest_time_in_allowed IS NOT NULL");
                            $q = $this->db->get("flexible_hours");
                            $r = $q->row();
                                
                            if($r){
                                $emp_id = $row->emp_id;
                                $w = array(
                                        "emp_id" => $emp_id,
                                        "date" => date("Y-m-d"),
                                        "status" => "Active"
                                );
                                $this->db->where($w);
                                $time_in = $this->db->get("employee_time_in");
                                $time_in_row = $time_in->row();
    
                                if(!$time_in_row){
                                    $start_time = $r->latest_time_in_allowed;
                                    $end_time = date("H:i:s",strtotime($start_time." +{$r->total_hours_for_the_day} hours"));
                                    if($start_time != "" && $end_time != ""){
                                        if(strtotime($start_time) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
                                            if(!check_employee_on_leave($row->emp_id)){
                                                //array_push($filter_emp_id, $row->emp_id);
                                                if($filter_work_schedule_id != ""){
                                                    if($work_schedule_id == $filter_work_schedule_id){
                                                        array_push($filter_emp_id, $row->emp_id);
                                                    }
                                                }
                                                else{
                                                    array_push($filter_emp_id, $row->emp_id);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                                
                            break;
                                
                        case 'Workshift':
                                
                            $w = array(
                            "company_id" => $company_id,
                            "work_schedule_id"=>$work_schedule_id
                            );
                            $this->db->where($w);
                            $q = $this->db->get("schedule_blocks");
                            $r = $q->row();
    
                            if($r){
                                $emp_id = $row->emp_id;
                                $w = array(
                                        "emp_id" => $emp_id,
                                        "date" => date("Y-m-d"),
                                        "status" => "Active"
                                );
                                $this->db->where($w);
                                $time_in = $this->db->get("employee_time_in");
                                $time_in_row = $time_in->row();
    
                                if(!$time_in_row){
                                    $start_time = $r->start_time;
                                    $end_time = $r->end_time;
                                    if($start_time != "" && $end_time != ""){
                                        if(strtotime($start_time) <= strtotime(date("H:i:s")) && strtotime(date("H:i:s")) <= strtotime($end_time)){
                                            if(!check_employee_on_leave($row->emp_id)){
                                                //array_push($filter_emp_id, $row->emp_id);
                                                //array_push($filter_emp_id, $row->emp_id);
                                                if($filter_work_schedule_id != ""){
                                                    if($work_schedule_id == $filter_work_schedule_id){
                                                        array_push($filter_emp_id, $row->emp_id);
                                                    }
                                                }
                                                else{
                                                    array_push($filter_emp_id, $row->emp_id);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
    
                            break;
                    }
                }
    
            }
        }
        return ($filter_emp_id) ? $filter_emp_id : false;
    }

    public function out_of_office($company_id,$emp_id){
        /*$select = array(
                "e.first_name AS first_name",
                "e.last_name AS last_name",
                "a.payroll_cloud_id AS payroll_cloud_id",
                "ela.date_start",
                "ela.date_end",
                "ela.total_leave_requested",
                "lt.leave_units",
                "a.profile_image",
                "e.company_id",
                "lt.leave_type"
            );
        $where = array(
                "ela.company_id" => $company_id,
                "ela.leave_application_status" => "approve",
                "ela.status" => "Active",
                "DATE(ela.date_start) <=" => date("Y-m-d"),
                "DATE(ela.date_end) >=" => date("Y-m-d"),
                "edrt.parent_emp_id" => $emp_id
        );
        $this->edb->select($select);
        $this->db->where($where);
        $this->db->where("ela.flag_parent IS NOT NULL");
        $this->edb->join("employee AS e","e.emp_id = ela.emp_id","LEFT");
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        $this->edb->join("leave_type AS lt","lt.leave_type_id = ela.leave_type_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
        $query = $this->edb->get("employee_leaves_application AS ela");
        $result = $query->result();
    
        return ($result) ? $result : false;*/




        $now = date("Y-m-d");

        $select = array(
            "e.first_name AS first_name",
            "e.last_name AS last_name",
            "a.payroll_cloud_id AS payroll_cloud_id",
            "ela.date_start",
            "ela.date_end",
            "ela.total_leave_requested",
            "lt.leave_units",
            "a.profile_image",
            "e.company_id",
            "lt.leave_type"
        );

        $where = array(
            "ela.company_id" => $company_id,
            "ela.leave_application_status" => "approve",
            "ela.status" => "Active",
            "DATE(ela.date_start) <=" => $now,
            "DATE(ela.date_end) >=" => $now,
            "ela.flag_parent !=" => "yes",
            "edrt.parent_emp_id" => $emp_id
        );
        
        $this->edb->select($select);
        $this->db->where($where);
        $this->edb->join("leave_type AS lt","lt.leave_type_id = ela.leave_type_id","LEFT");
        $this->edb->join("employee AS e","e.emp_id = ela.emp_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = ela.emp_id","LEFT");
        $query = $this->edb->get("employee_leaves_application AS ela");
        $result = $query->result();

        #return ($result) ? array($result, $query->num_rows()) : array(false, 0);
        return ($result) ? $result : false;
    
    }

    public function leave_list($emp_id, $company_id, $search = ""){
        if(is_numeric($company_id)){
            $where = array(
                "el.company_id" =>$company_id,
                "el.deleted" => '0',
                "ag.emp_id" => $emp_id,
                "el.leave_application_status"=>"pending"
            );

            $where2 = array(
                "al.level >=" => ""
            );

            $select = array(
                "pg.name AS pg_name",
                "empl.remaining_leave_credits AS remaining_c"
            );
            $this->db->select($select);
            $this->db->where($where2);
            $this->edb->where($where);

            $this->edb->join("employee AS e","e.emp_id = el.emp_id","LEFT");
            $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
            $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
            $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
            $this->edb->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
            $this->edb->join("approval_groups_via_groups AS agg","epi.leave_approval_grp = agg.approval_groups_via_groups_id","LEFT");
            $this->edb->join("approval_groups AS ag","ag.approval_groups_via_groups_id = agg.approval_groups_via_groups_id","LEFT");
            $this->edb->join("approval_process AS app","app.approval_process_id = ag.approval_process_id","LEFT");
            $this->edb->join("approval_leave AS al","al.leave_id = el.employee_leaves_application_id","LEFT");
            $this->edb->join("leave_type AS lt","lt.leave_type_id = el.leave_type_id","LEFT");
            $this->edb->join("employee_leaves AS empl","empl.leave_type_id = el.leave_type_id and empl.emp_id = e.emp_id","LEFT");
            $this->db->order_by('el.date_filed', 'DESC');
            $this->db->group_by('el.employee_leaves_application_id');
            $query = $this->edb->get("employee_leaves_application AS el");
            $result = $query->result();
            $query->free_result();
            return $result;
        }else{
            return false;
        }
    }

    public function employee_head_count($emp_id, $company_id, $search = ""){
        if(is_numeric($company_id)){
            $konsum_key = konsum_key();

            $emp_where = array(
                "epi.company_id" => $company_id,
                "epi.status" => "Active",
                "edrt.parent_emp_id" => $emp_id
            );

            if($search != "" && $search != "all"){
                $this->db->where("(
				convert(AES_DECRYPT(a.payroll_cloud_id,'{$konsum_key}')using latin1) LIKE '%".$search."%' OR
				convert(CONCAT(AES_DECRYPT(e.first_name,'{$konsum_key}'),' ',AES_DECRYPT(e.last_name,'{$konsum_key}')) using latin1) LIKE '%".$search."%')", NULL, FALSE);
            }

            $select = array(
                "e.first_name AS first_name",
                "e.last_name AS last_name",
                "a.payroll_cloud_id AS payroll_cloud_id",
            );

            $this->db->select($select);
            $this->db->where($emp_where);
            $this->edb->join("department AS d","epi.department_id = d.dept_id","LEFT");
            $this->edb->join("employee AS e","e.emp_id = epi.emp_id","LEFT");
            $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
            $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
            $query = $this->edb->get("employee_payroll_information AS epi");
            $result = $query->result();
            $query->free_result();
            return $result;
        }
    }

    public function get_all_direct_report_employees($emp_id, $company_id,$limit = 10000) {
        $where = array(
            "edrt.parent_emp_id" => $emp_id,
            "e.company_id" => $company_id,
            "e.status" => "Active"
        );

        $this->edb->where($where);
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
        $q = $this->edb->get("employee AS e");

        $r = $q->result();
        return ($r) ? $r : FALSE;
    }

    public function get_direct_employees_timein($emp_id, $company_id, $period='')
    {
        $select = array(
            'a.payroll_cloud_id',
            'e.last_name',
            'e.first_name',
            'e.emp_id',
            'e.account_id',
            'a.profile_image',
            'e.company_id',
            'employee_time_in.employee_time_in_id', 
            'employee_time_in.work_schedule_id', 
            'employee_time_in.date', 
            'employee_time_in.time_in', 
            'employee_time_in.lunch_out', 
            'employee_time_in.lunch_in', 
            'employee_time_in.break1_out', 
            'employee_time_in.break1_in', 
            'employee_time_in.break2_out', 
            'employee_time_in.break2_in', 
            'employee_time_in.time_out', 
            'employee_time_in.total_hours', 
            'employee_time_in.total_hours_required', 
            'employee_time_in.time_in_status', 
            'employee_time_in.mobile_clockin_status', 
            'employee_time_in.mobile_lunchin_status', 
            'employee_time_in.mobile_lunchout_status', 
            'employee_time_in.mobile_clockout_status', 
            'employee_time_in.mobile_break1_out_status', 
            'employee_time_in.mobile_break2_out_status', 
            'employee_time_in.mobile_break2_in_status', 
            'employee_time_in.mobile_break1_in_status'
        );

        $where = array(
            "edrt.parent_emp_id" => $emp_id,
            "e.company_id" => $company_id,
            "e.status" => "Active"
        );
        if ($period) {
            $fro = $period->cut_off_from;
            $to = $period->cut_off_to;
            $where2 = "(date >= '{$fro}' AND date <= '{$to}')";    
            $this->db->where($where2);
        }
        $wstr = "( time_in_status != 'reject' or time_in_status is NULL )";
        $this->db->where($wstr);
        $this->edb->select($select);
        $this->edb->where($where);
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = e.emp_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","INNER");
        $this->edb->join("employee_time_in","employee_time_in.emp_id = e.emp_id","LEFT");
        $q = $this->edb->get("employee AS e");
        $r = $q->result();

        if ($r) {
            $ret = array();
            foreach ($r as $row) {
                $t = array(
                    'emp_id' => $row->emp_id,
                    'account_id' => $row->account_id,
                    'payroll_cloud_id' => $row->payroll_cloud_id,
                    'last_name' => $row->last_name,
                    'first_name' => $row->first_name,
                    'profile_image' => $row->profile_image,
                    'company_id' => $row->company_id,
                    'employee_time_in_id' => $row->employee_time_in_id,
                    'work_schedule_id' => $row->work_schedule_id,
                    'date' => $row->date,
                    'time_in' => $row->time_in,
                    'lunch_out' => $row->lunch_out,
                    'lunch_in' => $row->lunch_in,
                    'break1_out' => $row->break1_out,
                    'break1_in' => $row->break1_in,
                    'break2_out' => $row->break2_out,
                    'break2_in' => $row->break2_in,
                    'time_out' => $row->time_out,
                    'total_hours' => $row->total_hours,
                    'total_hours_required' => $row->total_hours_required,
                    'time_in_status' => $row->time_in_status,
                    'mobile_clockin_status' => $row->mobile_clockin_status,
                    'mobile_lunchin_status' => $row->mobile_lunchin_status,
                    'mobile_lunchout_status' => $row->mobile_lunchout_status,
                    'mobile_clockout_status' => $row->mobile_clockout_status,
                    'mobile_break1_out_status' => $row->mobile_break1_out_status,
                    'mobile_break2_out_status' => $row->mobile_break2_out_status,
                    'mobile_break2_in_status' => $row->mobile_break2_in_status,
                    'mobile_break1_in_status' => $row->mobile_break1_in_status,
                    'q' => $row->emp_id."_".$row->date
                );
                array_push($ret, $t);
            }
            return $ret;
        }
        return FALSE;
    }

    public function get_employee_shifts_sched($emp_id,$company_id) {
        $where = array(
            "emp_id" => $emp_id,
            "company_id" => $company_id,
            "status" => "Active",
            "payroll_group_id" => 0
        );

        $this->edb->where($where);
        $this->db->order_by("valid_from","DESC");
        $q = $this->edb->get("employee_shifts_schedule");
        $r = $q->row();
        #last_query();
        return ($r) ? $r : FALSE;
    }

    public function get_employee_list_eb($cut_off_from,$cut_off_to,$company_id,$emp_id,$params=NULL)
    {
        // FOR TARDY EMPLOYEE LISTING FLAG
        if($params != NULL){
            if(isset($params["flag_tardy_view"]) != ""){
                $this->db->where("(eti.overbreak_min > 0 OR eti.late_min > 0)");
            }
        }
    
        $w = array(
                "epi.company_id" => $company_id,
                "epi.status"=>"Active",
                "e.status"=>"Active",
                "eti.date >= "=>$cut_off_from,
                "eti.date <= "=>$cut_off_to,
                "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($w);
    
        $this->db->where("eti.time_in IS NOT NULL");
        $this->db->where("eti.time_out IS NOT NULL");
        $this->db->where("(eti.time_in_status = 'approved' OR eti.time_in_status IS NULL)");
    
        $this->edb->join("employee AS e","eti.emp_id = e.emp_id","LEFT");
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
        
        $q = $this->edb->get("employee_time_in AS eti");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function check_employee_work_schedule($from,$to,$company_id,$params = NULL)
    {
        $array = array();
        $time = date("H:i:s");
        $man_date = date("Y-m-d");
        
        // REGULAR SCHEDULE
        #$this->db->select("work_start_time,work_end_time,total_work_hours");
        
        // if($params != NULL){
        //     if($params["work_schedule_id"] != "" && $params["work_schedule_id"] != "All"){
        //         $work_schedule_where = array(
        //             "work_schedule_id" => $params["work_schedule_id"]
        //         );
        //         $this->db->where($work_schedule_where);
        //     }
        // }
        $w = array(
            "company_id"=>$company_id,
            "total_work_hours > "=>0,
            "days_of_work"=>date("l",strtotime($from)),
            "status"=>"Active",
        );
        $this->db->where($w);

        $start_time_man_date = $man_date;

        // PARA RANI, TRAPPING SA NIGHT DIFF SCHEDULE
        if(date("A",strtotime($time)) == "PM"){
            $field_start_time_man_date = $man_date;
            $end_time_man_date = date("Y-m-d",strtotime($man_date." +1 day"));
        }else{
            $field_start_time_man_date = date("Y-m-d",strtotime($man_date." -1 day"));
            $end_time_man_date = $man_date;
        }

        $this->db->where("(
            (
                work_start_time <= '{$time}'
                    AND
                work_end_time >= '{$time}'
            )
                OR
            (
                DATE_FORMAT(work_start_time, '{$field_start_time_man_date} %H:%i:%s') <= '{$start_time_man_date} {$time}'
                    AND
                DATE_FORMAT(work_end_time, '{$end_time_man_date} %H:%i:%s') >= '{$start_time_man_date} {$time}'
                    AND
                DATE_FORMAT(work_start_time, '%p') = 'PM'
                    AND
                DATE_FORMAT(work_end_time, '%p') = 'AM'
            )
            )
        ");

        $this->db->order_by("work_start_time","ASC");
        $q = $this->db->get("regular_schedule");
        $r = $q->result();
        
        if($r){
            foreach($r as $row){
                $schedule = array(
                    "start_time"=>$row->work_start_time,
                    "end_time"=>$row->work_end_time,
                    "total_work_hours"=>$row->total_work_hours,
                    "work_schedule_id"=>$row->work_schedule_id
                );
                
                array_push($array, $schedule);
            }
        }
    
        // SPLIT SCHEDULE
        #$this->db->select("start_time,end_time,total_hours_work_per_block");
        if($params != NULL){
            if($params["work_schedule_id"] != "" && $params["work_schedule_id"] != "All"){
                $work_schedule_where = array(
                    "work_schedule_id" => $params["work_schedule_id"]
                );
                $this->db->where($work_schedule_where);
            }
        }
        $w = array(
            "company_id"=>$company_id,
            "total_hours_work_per_block > "=>0,
            "start_time <= "=>$time,
            "end_time >= "=>$time
            #"start_time <= "=>$time
        );
        $this->db->where($w);
        $this->db->order_by("start_time","ASC");
        $q = $this->db->get("schedule_blocks");
        $r = $q->result();
        if($r){
            foreach($r as $row){
                $schedule = array(
                    "start_time"=>$row->start_time,
                    "end_time"=>$row->end_time,
                    "total_work_hours"=>$row->total_hours_work_per_block,
                    "work_schedule_id"=>$row->work_schedule_id
                );
            
                array_push($array, $schedule);
            }
        }
    
        // FLEXIBLE SCHEDULE
        $this->db->select("*, latest_time_in_allowed AS start_time, ADDTIME(latest_time_in_allowed,CONCAT(floor(total_hours_for_the_day),':',ROUND(60 * (total_hours_for_the_day - floor(total_hours_for_the_day))),':00')) AS end_time, total_hours_for_the_day",FALSE);
        if($params != NULL){
            if($params["work_schedule_id"] != "" && $params["work_schedule_id"] != "All"){
                $work_schedule_where = array(
                        "work_schedule_id" => $params["work_schedule_id"]
                );
                $this->db->where($work_schedule_where);
            }
        }
        $w = array(
            "company_id"=>$company_id,
            "total_hours_for_the_day > "=>0,
        );
        $this->db->where($w);
        $this->db->where("(
            (
                latest_time_in_allowed <= '{$time}'
                    AND
                ADDTIME(latest_time_in_allowed,CONCAT(floor(total_hours_for_the_day),':',ROUND(60 * (total_hours_for_the_day - floor(total_hours_for_the_day))),':00')) >= '{$time}'
            )
                OR
            (
                DATE_FORMAT(latest_time_in_allowed, '{$field_start_time_man_date} %H:%i:%s') <= '{$start_time_man_date} {$time}'
                    AND
                ADDTIME(latest_time_in_allowed,CONCAT(floor(total_hours_for_the_day),':',ROUND(60 * (total_hours_for_the_day - floor(total_hours_for_the_day))),':00')) >= '{$start_time_man_date} {$time}'
                    AND
                DATE_FORMAT(latest_time_in_allowed, '%p') = 'PM'
                    AND
                DATE_FORMAT(ADDTIME(latest_time_in_allowed,CONCAT(total_hours_for_the_day,':00:00')), '%p') = 'AM'
            )
            )
        ");
        $this->db->where("latest_time_in_allowed IS NOT NULL");
        $this->db->order_by("latest_time_in_allowed","ASC");
        $q = $this->db->get("flexible_hours");
        $r = $q->result();
        if($r){
            
            foreach($r as $row){
                #if(strtotime($row->latest_time_in_allowed) <= strtotime($time) && strtotime($row->latest_time_in_allowed." +{$row->total_hours_for_the_day} hours") >= strtotime($time)){
                    $schedule = array(
                        "start_time"=>date("H:i:s",strtotime($row->latest_time_in_allowed)),
                        #"end_time"=> date("H:i:s",strtotime($row->latest_time_in_allowed." +{$row->total_hours_for_the_day} hours")),
                        "end_time"=> date("H:i:s",strtotime($row->end_time)),
                        "total_work_hours"=>$row->total_hours_for_the_day,
                        "work_schedule_id"=>$row->work_schedule_id
                    );
                        
                    array_push($array, $schedule);
                #}
            }
            
        }
    
        return $array;
    
    }

    public function check_employee_time_in_early_v2($company_id,$emp_id)
    {
        // $x = array(
        //     "epi.company_id" => $company_id,
        //     "epi.status"=>"Active",
        //     "e.status"=>"Active",
        //     "eti.time_in >= "=>$res_start_time,
        //     "eti.time_in <= "=>$res_end_time,
        //     "eti.work_schedule_id"=>$work_schedule_id,
        //     "edrt.parent_emp_id" => $emp_id
        // );
        $w = array(
            "epi.company_id" => $company_id,
            "epi.status"=>"Active",
            "e.status"=>"Active",
            "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($w);

        $this->edb->join("employee AS e","eti.emp_id = e.emp_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        // $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        // $this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = eti.emp_id","LEFT");
        
        $this->db->order_by("eti.time_in","ASC");
        
        $q = $this->edb->get("employee_time_in AS eti");
        $r = $q->result();
    }

    public function check_employee_time_in_early($work_schedule_id,$res_start_time,$res_end_time,$company_id,$emp_id,$params=NULL)
    {
        $sort_array = array(
            "e.last_name",
            "d.department_name",
            "p.position_name"
        );
        
        $w = array(
            "epi.company_id" => $company_id,
            "epi.status"=>"Active",
            "e.status"=>"Active",
            "eti.time_in >= "=>$res_start_time,
            "eti.time_in <= "=>$res_end_time,
            "eti.work_schedule_id"=>$work_schedule_id,
            "edrt.parent_emp_id" => $emp_id
        );
        $this->db->where($w);

        $this->edb->join("employee AS e","eti.emp_id = e.emp_id","LEFT");
        $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
        $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
        //$this->edb->join("position AS p","p.position_id = epi.position","LEFT");
        //$this->edb->join("department AS d","d.dept_id = epi.department_id","LEFT");
        $this->edb->join("employee_details_reports_to AS edrt","edrt.emp_id = eti.emp_id","LEFT");
        
        $this->db->order_by("eti.time_in","ASC");
        
        $q = $this->edb->get("employee_time_in AS eti");
        $r = $q->result();
        return ($r) ? $r : FALSE ;
    }

    public function get_workday($company_id,$work_schedule_id)
    {
        $s = array("*","work_type_name AS workday_type");
        $this->db->select($s);
        $where = array(
                'comp_id'      => $company_id,
                "work_schedule_id"=>$work_schedule_id
        );
        $this->db->where($where);
        $q = $this->db->get('work_schedule');
        $result = $q->row();
        $q->free_result();
        return ($result) ? $result : false;
    }

    public function get_uniform_working_day($company_id,$work_schedule_id,$weekday)
    {
        $where = array(
                'company_id'       => $company_id,
                "work_schedule_id"=>$work_schedule_id,
                #'working_day'     => $weekday
                'days_of_work'     => $weekday
        );
        $this->db->where($where);
        #$q = $this->db->get('uniform_working_day');
        $q = $this->db->get('regular_schedule');
        $result = $q->row();
        $q->free_result();
        return ($result) ? $result : false;
    }

    public function get_flexible_hour($company_id,$work_schedule_id)
    {
        $where = array(
                'company_id'       => $company_id,
                "work_schedule_id"=>$work_schedule_id
        );
        $this->db->where($where);
        $q = $this->db->get('flexible_hours');
        $result = $q->row();
        $q->free_result();
        return ($result) ? $result : false;
    }

    public function get_workshift($company_id,$work_schedule_id)
    {
        $s = array(
                "*","sb.total_hours_work_per_block AS total_work_hours"
        );
        $this->db->select($s);
        $where = array(
                'ss.company_id'        => $company_id,
                "ss.work_schedule_id"=>$work_schedule_id
        );
        $this->db->where($where);
        $this->db->join("schedule_blocks AS sb","sb.work_schedule_id = ss.work_schedule_id","LEFT");
        $q = $this->db->get('split_schedule AS ss');
        $result = $q->row();
        $q->free_result();
        return ($result) ? $result : false;
    }

    public function get_current_uniform_sched($company_id) {
        $res = array();
        
        $s = array(
                'work_start_time',
                'work_end_time',
                'work_schedule_id',
                'days_of_work',         
                'company_id',
        );
        
        $w = array(
                "company_id" => $company_id,
        );
        
        $this->db->where($w);
        $q = $this->db->get("regular_schedule");
        $r = $q->result();
        
        if($r){
            foreach ($r as $row) {
                $temp = array(
                        "work_start_time" => $row->work_start_time,
                        "work_end_time" => $row->work_end_time,
                        "work_schedule_id" => $row->work_schedule_id,
                        "days_of_work" => $row->days_of_work,
                        "company_id" => $row->company_id,
                        "filter_query" => "work_schedule_id-{$row->work_schedule_id}{$row->days_of_work}"
                );
        
                array_push($res, $temp);
            }
            return $res;
        }
    }

    public function get_current_flex_sched($company_id) {
        $res = array();
        
        $this->db->select("*,latest_time_in_allowed AS start_time, ADDTIME(latest_time_in_allowed,CONCAT(total_hours_for_the_day,':00:00')) AS end_time",FALSE);
        
        $w = array(
                "company_id" => $company_id,
                "not_required_login" => "0",
        );
        $this->db->where($w);
        
        $q = $this->db->get("flexible_hours");
        $r = $q->result();
        
        if($r){
            foreach ($r as $row) {
                $temp = array(
                        "total_hours_for_the_day" => $row->total_hours_for_the_day,
                        "latest_time_in_allowed" => $row->latest_time_in_allowed,
                        "work_schedule_id" => $row->work_schedule_id,
                        "company_id" => $row->company_id,
                        "not_required_login" => $row->not_required_login,
                        "filter_query" => "work_schedule_id-{$row->work_schedule_id}"
                );
        
                array_push($res, $temp);
            }
            return $res;
        }
    }

    public function get_current_schedule_v2($emp_id,$company_id,$get_current_uniform_sched,$get_current_flex_sched){
        $schedule = array();
        $result = $this->get_schedule_for_tile_v2($emp_id,$company_id);
        
        if($result){
            foreach ($result as $row){
                $work_type = $row->work_type_name;
                $work_schedule_id = $row->work_schedule_id;
                $work_schedule_name = $row->name;
                switch ($work_type){
                    case "Uniform Working Days" :
                        $days_of_work = date("l");
                        
                        $r = in_array_foreach_custom("work_schedule_id-{$work_schedule_id}{$days_of_work}", $get_current_uniform_sched);
                        
                        if($r){
                            foreach ($r as $reg){
                                $from_date = date("Y-m-d");
                                $to_date = date("Y-m-d");
                                    
                                if(date("A", strtotime($reg->work_start_time)) == "PM" && date("A", strtotime($reg->work_end_time)) == "AM" && date("A") == "AM"){
                                    $from_date = date("Y-m-d",strtotime("-1 day"));
                                }
                                
                                $start = date("Y-m-d H:i:s",strtotime("{$from_date} {$reg->work_start_time}"));
                                $end = date("Y-m-d H:i:s",strtotime("{$to_date} {$reg->work_end_time}"));
                                
                                $ars = array(
                                    "name" => $work_schedule_name,
                                    "start" => $start,
                                    "end" => $end,
                                    "work_schedule_id" => $work_schedule_id
                                );
                                if(date("Y-m-d H:i:s",strtotime($ars["start"])) <= date("Y-m-d H:i:s")){
                                    array_push($schedule, (object) $ars);
                                }
                            }
                        }
                        
                    break;
                    case "Flexible Hours" :
                        
                        $r = in_array_foreach_custom("work_schedule_id-{$work_schedule_id}", $get_current_flex_sched);
                        
                        if($r){
                            
                            foreach ($r as $flex){
                                $from_date = date("Y-m-d");
                                $to_date = date("Y-m-d");
                                
                                $n_end_tal = $flex->total_hours_for_the_day;
                                $n_hour = floor($n_end_tal);
                                $n_min = round(60*($n_end_tal-$n_hour));
                                
                                $start = date("Y-m-d H:i:s",strtotime("{$from_date} {$flex->latest_time_in_allowed}"));
                                $time_end = date("H:i:s",strtotime("{$start} +{$n_hour} hours +{$n_min} minute"));
                                
                                if(date("A", strtotime($start)) == "PM" && date("A", strtotime($time_end)) == "AM" && date("A") == "AM"){
                                    $from_date = date("Y-m-d",strtotime("-1 day"));
                                    $start = date("Y-m-d H:i:s",strtotime("{$from_date} {$flex->latest_time_in_allowed}"));
                                }
                                
                                $end = date("Y-m-d H:i:s",strtotime("{$to_date} {$time_end}"));
                                $ars = array(
                                    "name" => $work_schedule_name,
                                    "start" => $start,
                                    "end" => $end,
                                    "work_schedule_id" => $work_schedule_id
                                );
                                if(date("Y-m-d h:i:s") < $end){
                                    if(date("Y-m-d H:i:s",strtotime($ars["start"])) <= date("Y-m-d H:i:s")){
                                        array_push($schedule, (object) $ars);
                                    }
                                }
                            }
                        }
                        
                    break;
                    case "Workshift" :
                        
                        
                    break;
                }
            }
        }
        
        // sort to get closest start time
        usort($schedule, function($a, $b){
            return strcmp($b->start, $a->start);
        });
        
        return (count($schedule) > 0) ? $schedule[0] : false;
    }

    public function get_schedule_for_tile_v2($emp_id,$company_id) {
        $result = array();
        $s = array(
                'ws.work_type_name',
                'ws.work_schedule_id',
                'ws.name'
        );
        $where = array(
                "ess.company_id" => $company_id,
                "ess.status" => "Active",
                "ess.valid_from" => date('Y-m-d'),
                "ess.until" => date('Y-m-d'),
                "edrt.parent_emp_id" => $emp_id,
                "ess.payroll_group_id" => 0
        );
        $this->db->select($s);
        $this->db->where($where);
        $this->db->group_by("ess.work_schedule_id");
        $this->db->join("work_schedule AS ws","ws.work_schedule_id = ess.work_schedule_id","LEFT");
        $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = ess.emp_id","LEFT");
        $q = $this->db->get("employee_shifts_schedule AS ess");
        $r = $q->result();
        
        if($r) {
            return ($r) ? $r : FALSE;
        } else {
            $emp_where = array(
                    "epi.company_id" => $company_id,
                    "epi.status" => "Active",
                    'edrt.parent_emp_id' => $emp_id
            );
            $this->db->where($emp_where);
            $this->db->join("payroll_group AS pg","pg.payroll_group_id = epi.payroll_group_id","LEFT");
            $this->db->join("work_schedule AS ws","ws.work_schedule_id = pg.work_schedule_id","LEFT");
            $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
            $this->db->group_by("pg.work_schedule_id");
            $query = $this->db->get("employee_payroll_information AS epi");
            $row = $query->result();
                
            if($row) {
                foreach ($r as $zz) {
                    $temp_array = array(
                            "work_schedule_id" => $zz->work_schedule_id,
                            "emp_id" => $zz->emp_id
                    );
    
                    array_push($result, $temp_array);
                }
            }
            return ($row) ? $row : FALSE;
        }
    
    }

    public function get_payroll_group_id($company_id, $emp_ids)
    {
        $where = array(
            'company_id' => $company_id
        );
        $this->db->where($where);
        if ($emp_ids) {
            $this->db->where_in('emp_id', $emp_ids);
        }
        $s = array(
          'payroll_group_id',
          'emp_id'  
        );
        $this->db->select($s);
        $q = $this->db->get('employee_payroll_information');
        $r = $q->result();
        
        if ($r) {
            $return_ar = array();
            foreach ($r as $row) {
                $t = array(
                    'payroll_group_id' => $row->payroll_group_id,
                    'emp_id' => $row->emp_id,
                    'q' => "emp_".$row->emp_id
                );
                array_push($return_ar, $t);
            }
            return $return_ar;
        }

        return false;
    }

    public function check_if_holiday($company_id){
		$now = date("Y-m-d");
		$where = array(
			"company_id" => $company_id,
			"MONTH(date)" => date("m"),
			"DAY(date)" => date("d"),
			"status" => "Active"
		);
		$this->db->where($where);
		$query = $this->db->get("holiday");
		$row = $query->row();
		
		return ($row) ? true : false;
	}

}