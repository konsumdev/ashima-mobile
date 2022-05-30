<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ManagerDashboard extends CI_Controller{
    var $verify;

    public function __construct(){
        parent::__construct();
        $this->load->model('managerDashboard_model','mdm');
        $this->load->model('employee_model','employee');
        $this->load->model('Manager_tardiness_model','mtm');
        $this->load->model('manager_employee_directory_model','medm');
        $this->load->model('manager_shifts_model','msm');

        $this->company_info = whose_company();

        $this->emp_id = $this->session->userdata('emp_id');
        $this->company_id =$this->employee->check_company_id($this->emp_id);
        $this->account_id = $this->session->userdata('account_id');

    }

    public function employee_head_count(){
        $s = array(
	        "epi.emp_id"
	    );
		$emp_where = array(
				"epi.company_id" => $this->company_id,
				"epi.employee_status" => "Active",
				'edrt.parent_emp_id'    => $this->emp_id
        );
        
		$this->db->select($s);
		$this->db->where($emp_where);
		$this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
		$query = $this->db->get("employee_payroll_information AS epi");
        $row = $query->num_rows();
        
		echo json_encode(
            array(
                "head_count" => $row ? $row : '0'
            )
        );
        
    }

    public function employees_clocked_in(){
        $schedule = $this->mdm->get_current_schedule($this->emp_id,$this->company_id);

        $count_clockedin = 0;
        if($schedule != false){
            $count_clockedin = $this->mdm->count_clocked_in($this->company_id, $schedule->work_schedule_id,$this->emp_id);
        }

        echo json_encode(
            array(
                "clocked_in" => $count_clockedin,
            )
        );
    }

    public function employees_no_show(){

        $schedule = $this->mdm->get_current_schedule($this->emp_id,$this->company_id);

        $count_no_show = 0;
        if($schedule != false){
            $count_no_show = $this->mdm->count_no_show($this->company_id, $schedule->work_schedule_id,$this->emp_id);
        }

        echo json_encode(
            array(
                "count_no_show" => $count_no_show,
            )
        );
    }

    public function employees_out_on_leave(){
        $out_on_leave = $this->mdm->out_of_office($this->company_id, $this->emp_id);
        $count_out_on_leave = $out_on_leave ? count($out_on_leave) : "0";

        echo json_encode(
            array(
                "count_out_on_leave" => $count_out_on_leave,
                "employees" => ($out_on_leave) ? $out_on_leave : array()
            )
        );
    }

    public function todo_leave(){
        $chk_leave = "0";
        $chk_overtime = "0";
        $chk_shift = "0";
        $chk_timesheet = "0";
        $count_leave = "";
        $chk_workflow = "";
        $emp_id = $this->session->userdata('emp_id');

        if (is_workflow_enabled($this->company_id)) {
            $chk_workflow = "1";
        } else {
            $chk_workflow = "0";
        }

        if (check_if_approver($this->session->userdata('emp_id'), "leave")) {
            $chk_leave = "1";
        }else{
            $chk_leave = "0";
        }

        if (check_if_approver($this->session->userdata('emp_id'), "overtime")) {
            $chk_overtime = "1";
        }else{
            $chk_overtime = "0";
        }

        if (check_if_approver($this->session->userdata('emp_id'), "shifts")) {
            $chk_shift = "1";
        }else{
            $chk_shift = "0";
        }

        if (check_if_approver($this->session->userdata('emp_id'), "timein")) {
            $chk_timesheet = "1";
        }else{
            $chk_timesheet = "0";
        }

        $count_timein = todo_pending_timein_counter($emp_id,$this->company_id) + todo_pending_split_timein_counter($emp_id,$this->company_id);
        $count_shift = todo_pending_shifts_request_counter($emp_id,$this->company_id);
        $count_overtime = todo_pending_overtime_counter($emp_id,$this->company_id);
        $count_leave = todo_pending_leave_counter($this->emp_id,$this->company_id);
        

        echo json_encode(
            array(
                "leave" => $chk_leave,
                "overtime" => $chk_overtime,
                "shift" => $chk_shift,
                "timesheet" => $chk_timesheet,
                "chk_workflow" => $chk_workflow,
                "count_leave" => $count_leave,
                "count_overtime" => $count_overtime,
                "count_shift" => $count_shift,
                "count_timein" => $count_timein,
            )
        );
    }

    public function todo_leave_count(){
        $temp = $this->mdm->leave_list($this->emp_id, $this->company_id, "");
        if($temp){
            echo json_encode($temp);
        }else{
            return false;
        }
    }

    public function head_count(){
        @$search = $this->input->post('search');
        $temp = $this->mdm->employee_head_count($this->emp_id, $this->company_id, $search);
        if($temp){
            $res = array();
            foreach($temp as $result){
                $res_tmp = array(
                    "result" => "1",
                    "last_name" => $result->last_name,
                    "first_name" => $result->first_name,
                    "payroll_cloud_id" => $result->payroll_cloud_id,
                    "department_name" => $result->department_name,
                    "profile_image" => $result->profile_image,
                );

                array_push($res, $res_tmp);
            }
            echo json_encode($res);
        }else{
            $res = array(
                "result" => "0"
            );

            echo json_encode($res);
        }
    }

    public function head_count_2(){
        $temp = $this->mdm->employee_head_count($this->emp_id, $this->company_id, "Smith7");
        if($temp){
            $res = array();
            foreach($temp as $result){
                $res_tmp = array(
                    "result" => "1",
                    "last_name" => $result->last_name,
                    "first_name" => $result->first_name,
                    "payroll_cloud_id" => $result->payroll_cloud_id,
                    "department_name" => $result->department_name,
                );

                array_push($res, $res_tmp);
            }
            echo json_encode($res);
        }
    }

    public function missing_employees_timesheet_count($reqdate=''){
        $date_from = date('Y-m-d', strtotime('-1 day'));
        $date_to = date('Y-m-d', strtotime('-1 day'));
        $missing_logs = missing_logs_for_manager($this->emp_id,$this->company_id,date("Y-m-d", strtotime($date_from)),date("Y-m-d", strtotime($date_to)));

        $emp_ids = $this->emp_ids($missing_logs);

        $sched_date = '';
        // if ($reqdate) {
        //     $sched_date = date("Y-m-d", strtotime($reqdate));
        // } else {            
            $sched_date = date("Y-m-d", strtotime($date_from));
        // }

        // for shifts
        $employee_shift_schedule        = $this->medm->employee_assigned_work_schedule($this->company_id, '', $emp_ids);
        $get_work_schedule_default      = $this->medm->employee_work_schedule_via_payroll_group($this->company_id);
        $employee_shift_schedule_regular= $this->medm->employee_work_schedule_regular($this->company_id, $emp_ids);
        $employee_shift_schedule_flex   = $this->medm->employee_flex_work_schedule($this->company_id);
        $employee_schedule_blocks       = $this->medm->employee_schedule_blocks_v2($this->company_id, $emp_ids);
        $work_schedule_details          = $this->medm->work_schedule_details($this->company_id);

        $epis = $this->mdm->get_payroll_group_id($this->company_id, $emp_ids);
        
        $tmp_next_period = next_pay_period($this->emp_id, $this->company_id);
        $next_period = ($tmp_next_period) ? date('d-M-y', strtotime($tmp_next_period->first_payroll_date)) : '~';

        $directory_list = array();
        
        if ($missing_logs) {
            foreach ($missing_logs as $row) {

                $fullname = $row['first_name'].' '.$row['last_name'];
                $pgid = in_array_custom("emp_".$row['emp_id'], $epis);
                $paygrp = ($pgid) ? $pgid->payroll_group_id : 0;
                
                $emp_shifts = $this->get_employee_shifts($this->company_id, $row['emp_id'], $employee_shift_schedule, $get_work_schedule_default,
                    $employee_shift_schedule_regular, $employee_shift_schedule_flex, $employee_schedule_blocks,
                    $paygrp, $work_schedule_details, $sched_date);

                
                $temp_array = array(
                    'id'            => $row['payroll_cloud_id'],
                    'first_name'    => $row['first_name'],
                    'last_name'     => $row['last_name'],
                    'emp_id'        => $row['emp_id'],
                    'account_id'    => $row['account_id'],
                    'profile_pic'   => $row['profile_image'],
                    'company_id'    => $this->company_id,
                    'base_url'      => base_url(),
                    'full_name'     => $fullname,
                    'shifts'        => $emp_shifts,
                    'base_url'      => base_url(),
                    'payroll_cloud_id' => $row['payroll_cloud_id']
                );
                array_push($directory_list, $temp_array);
            }
        }
        

        $res_tmp = array(
            "result" => "1",
            "employees_missing_timesheet_count" => ($missing_logs) ? count($missing_logs) : 0,
            "employees" => $directory_list,
            "next_pay_period" => $next_period,
            "date" => $sched_date
        );

        echo json_encode($res_tmp);
    }

    public function emp_ids($emp_list)
    {
        if (!$emp_list) {
            return false;
        }

        $ids = array();

        if (is_object($emp_list)) {
            foreach ($emp_list as $row) {
                array_push($ids, $row->emp_id);
            }
        } else if (is_array($emp_list)) {
            foreach ($emp_list as $row) {
                array_push($ids, $row['emp_id']);
            }
        }

        return $ids;
    }

    public function get_employee_shifts(
        $company_id, $emp_id, $employee_shift_schedule, $get_work_schedule_default, $employee_shift_schedule_regular, $employee_shift_schedule_flex,
        $employee_schedule_blocks, $payroll_group_id, $work_schedule_details, $sched_date
    )
    {
        $emp_one_week_sched = array();
        
        $payroll_group_id = ($payroll_group_id > 0) ? $payroll_group_id : 0;

        $current_monday = date("Y-m-d",strtotime(" monday this week")-1);
        if ($sched_date) {
            $current_monday = date("Y-m-d",strtotime(" monday this week", strtotime($sched_date))-1);
        }
        
        //for($c=0;$c<7;$c++)
        //{
            //$date_calendar = date("M-d D",strtotime($current_monday." +{$c} day"));
            //$date_counter = date("Y-m-d",strtotime($current_monday." +{$c} day"));
            $todate = date("Y-m-d");
            if ($sched_date) {
                $todate = date("Y-m-d", strtotime($sched_date));
            }

            $date_calendar = date("M-d D", strtotime($todate));
            $date_counter = date("Y-m-d", strtotime($todate));
            
            $weekday = date('l',strtotime($date_calendar));
            $rest_day = FALSE;
            $st = $et = $lta = $str = $nrl = "";
            $shift_type = "";
            $shifts_emp_id = $work_shift_id = $flag_custom = $total_hours = 0;
            $flag = NULL;
            
            $break = "0 mins";
            
            $emp_custom_sched = in_array_custom("{$emp_id}{$date_counter}",$employee_shift_schedule);
            $emp_sched = ($emp_custom_sched != FALSE) ? $emp_custom_sched : FALSE ;
            
            /* CHECK BACKGROUND COLOR */

            //p($emp_id.'-'.$payroll_group_id);
            
            if($emp_sched) {
                
                $flag = 1;
                
                $shifts_emp_id = $emp_sched->shifts_emp_id;
                $work_schedule_id = $emp_sched->work_schedule_id;
                $work_shift_id = $emp_sched->work_shift_id;
                $shift_type = $emp_sched->shift_type;
                $work_schedule_custom = $emp_sched->category_id;
                $shift_name = $emp_sched->shift_name;
                $flag_custom = $emp_sched->flag_custom;
            }else{
                /* DEFAULT WORK SCHEDULE VIA PAYROLL GROUP */
                $default_custom_sched = in_array_custom("{$emp_id}{$payroll_group_id}",$get_work_schedule_default);
                $def_sched = ($default_custom_sched != FALSE) ? $default_custom_sched : FALSE;
                
                if($def_sched) {
                    $flag = 2;
                    $shifts_emp_id = $emp_id;
                    $work_schedule_id = $def_sched->work_schedule_id;
                    $shift_type = $def_sched->shift_type;
                    $work_schedule_custom = $def_sched->work_schedule_custom;
                    $shift_name = $def_sched->shift_name;
                }

            }
            
            if($flag != NULL) {

                $abrv = $sub_work_name = "";

                if($work_schedule_custom != NULL) {

                    $work_sched_details = in_array_custom("{$work_schedule_custom}",$work_schedule_details);
                    $def_sched = ($work_sched_details != FALSE) ? $work_sched_details : FALSE;
                    
                    $sub_work_name = " - ".$shift_name;
                    
                    if($def_sched) {
                        $shift_name = $def_sched->name;
                    }

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
                
                $emp_one_week_sched[1] = $abrv;

                /*  REGULAR SCHEDULE */
                if($shift_type == "Uniform Working Days") {
                    
                    $reg_date = date("l",strtotime($date_counter));
                    $regular_custom_sched = in_array_custom("{$work_schedule_id}{$reg_date}",$employee_shift_schedule_regular);
                    $reg_sched = ($regular_custom_sched != FALSE) ? $regular_custom_sched : FALSE ;
                    
                    if($reg_sched) {
                        
                        $st = date("h:i A",strtotime($reg_sched->work_start_time));
                        $et = date("h:i A",strtotime($reg_sched->work_end_time));
                        
                        $reg_block_holder = $st." - ".$et;
                        $emp_one_week_sched[0] = $reg_block_holder;

                        //array_push($emp_one_week_sched, $reg_block_holder);
                    }
                    
                }
                
                /* FLEXI TIME SCHEDULE */
                
                if($shift_type == "Flexible Hours") {
                    
                    $custom_flex_sched = in_array_custom("{$work_schedule_id}",$employee_shift_schedule_flex);
                    $emp_flex_sched = ($custom_flex_sched != FALSE) ? $custom_flex_sched : FALSE;
                    
                    if($emp_flex_sched) {
                        $lta = $emp_flex_sched->latest_time_in_allowed;
                        $st = ($lta != NULL) ? date("h:i A",strtotime($lta)) :"";
                        $et = "";
                        $total_hours = $emp_flex_sched->total_hours_for_the_day;
                        $nrl = ($emp_flex_sched->not_required_login == 1) ? "No" : "Yes";
                        $break = $emp_flex_sched->duration_of_lunch_break_per_day;
                        
                        $set_break_time = gmdate("H:i:s", $break * 60 );
                        $get_h = date("H",strtotime($set_break_time));
                        $get_m = date("i",strtotime($set_break_time))*1/60;
                        $compute_hm = $get_h+$get_m;
                        $working_hours = $total_hours;
                        $total_hours = floatval($working_hours) - floatval($compute_hm);
                        $total_hrs_f = 60*60*$total_hours;
                        $et_f = ($lta != NULL) ? date("h:i A",strtotime($lta)+$total_hrs_f) :"";
                        $total_hours = number_format($total_hours,2);
                    }
                }
                
                if($shift_type == "Workshift") {
                    $custom_split_sched = in_array_foreach_custom("{$shifts_emp_id}{$date_counter}{$work_schedule_id}",$employee_schedule_blocks);
                    $get_emp_blocks = ($custom_split_sched != FALSE) ? $custom_split_sched : FALSE;
                    
                }
                                
                $rest_day = $this->medm->get_rest_day($company_id,$work_schedule_id,$weekday);

                if($rest_day){
                    $emp_one_week_sched[0] = 'Rest Day';
                    // array_push($emp_one_week_sched, 'Rest Day');
                }
                else
                {
                    if($st != "" && $et != ""){
                        $str = "{$st} - {$et}";
                    }
                    
                    if($shift_type == "Flexible Hours") {
                        
                        if($nrl == "Yes"){
                            $str = "FLEXI - {$st}";
                        }else if($nrl == "No"){
                            $str = "FLEXI";
                        }
                    }
                    
                    // for split
                    if($shift_type == "Workshift" ){
                        
                        if( $get_emp_blocks  ) {
                            $shift_blocks_holder = array();
                            $shift_name_holder = array();
                            
                            foreach( $get_emp_blocks as $row_block ) {
                                $block_name = $row_block->block_name;
                                $st = date("h:i A",strtotime($row_block->start_time));
                                $et = date("h:i A",strtotime($row_block->end_time));
                                
                                $block_range = $st." - ".$et;
                                array_push($shift_blocks_holder, $block_range);
                                array_push($shift_name_holder, 'SS - '.$block_name);
                            }
                            $emp_one_week_sched[0] = $shift_blocks_holder;
                            $emp_one_week_sched[1] = $shift_name_holder;
                            // array_push($emp_one_week_sched, $shift_blocks_holder);
                        }
                        
                    }// else workshift
                    else
                    {
                        $emp_one_week_sched[0] = $str;
                        // array_push($emp_one_week_sched, $str);
                    } // end workshift
                    
                } //end else $rest_day
            } // end  if != null
            
        //}// end for loop
        
        return $emp_one_week_sched;
    }

    /**
     * Get the employees who missed punching in under the manager
     */
    public function team_missed_punches($reqdate=''){

        $period =  next_pay_period_v2($this->emp_id, $this->company_id);
        $emp_timeins = $this->mdm->get_direct_employees_timein($this->emp_id, $this->company_id, $period);
        
        $get_emp_lists = $this->msm->get_employee_list($this->company_id, $this->emp_id);


        $check_employee_work_schedule_v2 = check_employee_work_schedule_v2($this->emp_id, $this->company_id);
        $work_schedule_id_pg = check_employee_work_schedule_else($this->emp_id)->work_schedule_id;

        $count_res = 0;
        $result = array();
        if($get_emp_lists){
            foreach($get_emp_lists as $row){
                $manager_count_missed_punches_v2 = manager_count_missed_punches_v2($row->emp_id, $row->company_id,$check_employee_work_schedule_v2,$work_schedule_id_pg);
                $date = date('Y-m-d', strtotime('-1 day'));
                $emp_time_in = in_array_custom("{$row->emp_id}_{$date}",$emp_timeins);
                if($manager_count_missed_punches_v2 > 0) {
                    $count_res = $count_res + 1;
                    $temp_res = array(
                        "account_id" => $row->account_id,
                        "emp_id" => $row->emp_id,
                        "company_id" => $this->company_id,
                        "first_name" => $row->first_name,
                        "last_name" => $row->last_name,
                        "profile_image" => $row->profile_image,
                        "payroll_cloud_id" => $row->payroll_cloud_id,
                        "base_url" => base_url(),
                        "time_in" => ($emp_time_in) ? $emp_time_in->time_in : '',
                        "lunch_in" => ($emp_time_in) ? $emp_time_in->lunch_in : '',
                        "lunch_out" => ($emp_time_in) ? $emp_time_in->lunch_out : '',
                        "time_out" => ($emp_time_in) ? $emp_time_in->time_out : '',
                    );
                    array_push($result, $temp_res);
                }
            }
        } 

        $res_tmp = array(
            "result" => "1",
            "missed_punches_count" => $count_res,
            "missed_punches" => $result,
            "account_id" => $this->account_id,
        );

        echo json_encode($res_tmp);

    }

    public function shifts_ending(){
        $result = array();
        $shifts_ending = manager_employee_expired_work_schedule($this->company_id, $this->emp_id,2);
        if($shifts_ending){
            $count = 0;

            foreach($shifts_ending as $key=>$row){
                $count = $count + 1;
                if($count <= 2){
                    $get_employee_shifts_sched = $this->mdm->get_employee_shifts_sched($row->emp_id, $row->company_id);

                    $temp_res = array(
                        "account_id" => $row->account_id,
                        "emp_id" => $row->emp_id,
                        "company_id" => $this->company_id,
                        "first_name" => $row->first_name,
                        "last_name" => $row->last_name,
                        "shifts_date" => date('d-M-y', strtotime($get_employee_shifts_sched->valid_from)),
                        "profile_image" => $row->profile_image,
                    );
                    array_push($result, $temp_res);
                }
            }
        }

        $final_result = array(
            "result" => "1",
            "shifts_ending" => $result,
        );

        echo json_encode($final_result);
    }

    function no_shifts_assigned() {
        $total = $total_ws = $total_active_emp = $total_assigned = 0;
        $work_schedule_available = array();
        $employee_assigned = array();

        $query_default_sched = manager_employee_default_schedule($this->company_id,$this->emp_id);
        $query_sched = manager_employee_custom_shifts($this->company_id,$this->emp_id);

        if($query_default_sched) {
            foreach ($query_default_sched as $rows) {
                array_push($work_schedule_available, $rows->work_schedule_id);
                array_push($employee_assigned, $rows->emp_id);
            }
        }

        if($query_sched) {
            foreach ($query_sched as $rows) {
                array_push($work_schedule_available, $rows->work_schedule_id);
                array_push($employee_assigned, $rows->emp_id);
            }
        }

        $total_active_emp = manager_total_active_employees($this->company_id,$this->emp_id);
        $total_assigned = count(array_unique($employee_assigned));
        $total = $total_active_emp - $total_assigned;

        $final_result = array(
            "result" => "1",
            "total" => $total,
        );

        echo json_encode($final_result);

    }

    public function next_pay_date(){
        $next_period = next_pay_period($this->emp_id, $this->company_id);
        $final_result = array(
            "result" => "1",
            "pay_date" => ($next_period) ? date('d-M-y', strtotime($next_period->first_payroll_date)) : '~',
            "cut_off_from" => ($next_period) ? date('d-M-y', strtotime(next_pay_period($this->emp_id, $this->company_id)->cut_off_from)) : '~',
            "cut_off_to" => ($next_period) ? date('d-M-y', strtotime(next_pay_period($this->emp_id, $this->company_id)->cut_off_to)) : '~',
        );

        echo json_encode($final_result);
    }

    public function birthdays(){
        $result = array();
        $now = date("Y-m-d");
        $pc_where = array(
            "cut_off_from <=" => $now,
            "cut_off_to >=" => $now,
            "company_id" => $this->company_id
        );
        $this->db->where($pc_where);
        $pc_query = $this->db->get("payroll_calendar");
        $pc_row = $pc_query->row();

        if($pc_row){
            $where = array(
                "e.company_id" => $this->company_id,
                "epi.employee_status" => "Active",
                "edrt.parent_emp_id" => $this->emp_id
            );
            $this->db->where($where);
            $this->db->where("DATE_FORMAT(e.dob, '%c-%d') BETWEEN DATE_FORMAT('".date("Y-m-d",strtotime($pc_row->cut_off_from))."', '%c-%d') AND DATE_FORMAT('".date("Y-m-d",strtotime($pc_row->cut_off_to))."', '%c-%d')");
            $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
            $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
            $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
            $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
            $this->db->order_by("e.dob", "asc");
            $query = $this->edb->get("employee AS e");
            $q_result = $query->result();
            if($q_result){
                $count = 0;
                foreach($q_result as $row){
                    $count = $count + 1;
                    // if($count <= 2){
                        $temp_res = array(
                            "emp_id" => $row->emp_id,
                            "company_id" => $this->company_id,
                            "account_id" => $row->account_id,
                            "first_name" => $row->first_name,
                            "last_name" => $row->last_name,
                            "profile_image" => $row->profile_image,
                            "date_of_birth" => date('d-M-y', strtotime($row->dob)),
                        );
                        array_push($result, $temp_res);
                    // }

                }
            }
        }

        $final_result = array(
            "result" => ($result) ? 1 : 0,
            "birthdays" => $result,
        );

        echo json_encode($final_result);
    }

    public function anniversaries(){
        $result = array();
        $now = date("Y-m-d");
        $pc_where = array(
            "cut_off_from <=" => $now,
            "cut_off_to >=" => $now,
            "company_id" => $this->company_id
        );
        $this->db->where($pc_where);
        $pc_query = $this->db->get("payroll_calendar");
        $pc_row = $pc_query->row();

        if($pc_row){
            $where = array(
                "e.company_id" => $this->company_id,
                "epi.employee_status" => "Active",
                "edrt.parent_emp_id" => $this->emp_id
            );
            $this->db->where($where);
            $this->db->where("DATE_FORMAT(epi.date_hired, '%c-%d') BETWEEN DATE_FORMAT('".date("Y-m-d",strtotime($pc_row->cut_off_from))."', '%c-%d') AND DATE_FORMAT('".date("Y-m-d",strtotime($pc_row->cut_off_to))."', '%c-%d')");
            $this->edb->join("employee_payroll_information AS epi","epi.emp_id = e.emp_id","LEFT");
            $this->db->join("employee_details_reports_to AS edrt","edrt.emp_id = epi.emp_id","LEFT");
            $this->edb->join("position AS p","p.position_id = epi.position","LEFT");
            $this->edb->join("accounts AS a","a.account_id = e.account_id","LEFT");
            $query = $this->edb->get("employee AS e");
            $q_result = $query->result();
            if($q_result){
                $count = 0;
                foreach($q_result as $row){
                    $count = $count + 1;

                    $num_of_years = date("Y") - date("Y",strtotime($row->date_hired));
                    $year_text = ($num_of_years > 1) ? "Years" : "Year";
                    $company_name = "";#$this->session->userdata("sub_domain2");

                    if($count <= 3){
                        $temp_res = array(
                            "emp_id" => $row->emp_id,
                            "company_id" => $this->company_id,
                            "account_id" => $row->account_id,
                            "first_name" => $row->first_name,
                            "last_name" => $row->last_name,
                            "profile_image" => $row->profile_image,
                            "num_of_years" => $num_of_years,
                            "year_text" => $year_text,
                            "company_name" => $company_name,
                        );
                        array_push($result, $temp_res);
                    }
                }
            }
        }

        $final_result = array(
            "result" => count($result),
            "anniversaries" => $result,
        );

        echo json_encode($final_result);
    }

    public function holidays(){
        $arrs = array();
        // get all fixed holidays
        $where = array(
                "company_id" => $this->company_id,
                "date_type" => "fixed"
        );
        $this->db->where($where);
        $this->db->order_by("date","ASC");
        $query = $this->db->get("holiday");
        $result = $query->result();
    
        if($result){
            foreach($result as $row){
                $year = date("Y");
                $m = date("m",strtotime($row->date));
                $d = date("d",strtotime($row->date));
    
                if(intval($m) < intval(date("m")) || (intval($m) <= intval(date("m")) && intval($d) <= intval(date("d")))){
                    $year = $year + 1;
                }
                $md = date("m-d",strtotime($row->date));
                $holiday = array(
                        "date" => date("Y-m-d",strtotime("{$year}-{$md}")),
                        "name" => $row->holiday_name
                );
                array_push($arrs, (object) $holiday);
            }
        }
    
        // get all movable holidays
        $where2 = array(
                "company_id" => $this->company_id,
                "date_type" => "movable",
                "date >" => date("Y-m-d")
        );
        $this->db->where($where2);
        $this->db->order_by("date","ASC");
        $query2 = $this->db->get("holiday");
        $result2 = $query2->result();
    
        if($result2){
            foreach($result2 as $row){
                $year = date("Y");
                $md = date("m-d",strtotime($row->date));
                $holiday = array(
                        "date" => date("Y-m-d",strtotime("{$year}-{$md}")),
                        "name" => $row->holiday_name
                );
                array_push($arrs, (object) $holiday);
            }
        }

        if (empty($arrs)) {
            $final_result = array(
                "result" => 0,
                "holidays" => array(),
                "month" => null,
                "year" => null,
                "holiday_name" => $hol_name
            );
    
            echo json_encode($final_result);
        }
    
        // sort holidays
        usort($arrs, function($a, $b){
            return strcmp($a->date, $b->date);
        });

        // $sliced_array = ($arrs) ? array_slice($arrs, 0, 2) : array();
        $hol_date = ($arrs) ? $arrs[0]->date : '';
        $month_day = ($hol_date) ? date("d-M", strtotime($hol_date)) : '';
        $yr = ($hol_date) ? date("Y", strtotime($hol_date)) : '';
        $hol_name = ($arrs) ? $arrs[0]->name : '';

        $final_result = array(
            "result" => count($arrs),
            "holidays" => ($arrs) ? $arrs[0] : array(),
            "month" => $month_day,
            "year" => $yr,
            "holiday_name" => $hol_name
        );

        echo json_encode($final_result);
    }

    public function tardiness_count()
    {
        $todate = date("Y-m-d");
        $get_all_tardiness_list = $this->mtm->all_tardiness_list($this->company_id,$this->emp_id,false,"","",$todate);
        $total = ($get_all_tardiness_list) ? count($get_all_tardiness_list) : 0;

        $final_result = array(
            "employees" => $get_all_tardiness_list,
            "tardiness" => $total,
        );

        echo json_encode($final_result);
    }

    public function check_employee_time_in($e_work_schedule_id,$new_start_time,$new_end_time, $employee_timein)
    {
        if ($employee_timein) {
            foreach ($employee_timein as $row) {
                if ($row->work_schedule_id == $e_work_schedule_id) {
                    if (($row->time_in >= $new_start_time) && ($row->time_in <= $new_end_time)){
                        return $row;
                    }
                }
            }
        }
        return false;
    }

    public function get_schedule()
    {
        // Get Schedule
        $get_current_uniform_sched = $this->mdm->get_current_uniform_sched($this->company_id);
        $get_current_flex_sched = $this->mdm->get_current_flex_sched($this->company_id);
        $schedule = $this->mdm->get_current_schedule_v2($this->emp_id,$this->company_id,$get_current_uniform_sched,$get_current_flex_sched);

        $resu = array(
            'name' => ($schedule) ? $schedule->name : '',
            'start' => ($schedule) ? date("g:i a", strtotime($schedule->start)) : '',
            'end' => ($schedule) ? date("g:i a", strtotime($schedule->end)) : '',
            'result' => ($schedule) ? count($schedule) : 0
        );
        echo json_encode($resu);
    }

    public function get_early_birds()
    {
        $current = date("Y-m-d");
        $cut_off_from = date("Y-m-d");
        $cut_off_to =  date("Y-m-d");

        $from = date("Y-m-d");
        $to = date("Y-m-d");

        $params = array();

        $employee_name_row_array = array();
        $employee_first_name_row_array = array();
        $employee_last_name_row_array = array();
        $employee_account_id = array();
        $employee_emp_id = array();
        $employee_payroll_cloud_id = array();
        $employee_position_row_array = array();
        $employee_department_array = array();
        $employee_row_array = array();
        $employee_login_mobile_number_array = array();
        $employee_telephone_number_array = array();
        $employee_time_in_array = array();
        $mins_prior_to_shift_start = array();
        $work_schedule_array = array();
        $work_schedule_name_array = array();
        $emp_time_in_work_schedule_id = array();
        $employees = array();

        // $get_employee_list = $this->mdm->get_employee_list_eb($from,$to,$this->company_id,$this->emp_id);
        
        // if($get_employee_list != FALSE)
        // {
        //     foreach($get_employee_list as $row_employee_list)
        //     {
        //         $employee_row_array[$row_employee_list->employee_time_in_id] = 0;
        //     }
        // }

        $flag_view = FALSE;        
        $checker = array();

        $check_employee_work_schedule = $this->mdm->check_employee_work_schedule($from,$to,$this->company_id);
                       
        foreach($check_employee_work_schedule as $key => $val)
        {
            $res_start_time = "";
            $res_end_time = "";
                
            $stime = $check_employee_work_schedule[$key]["start_time"];
            $etime = $check_employee_work_schedule[$key]["end_time"];
            $e_work_schedule_id = $check_employee_work_schedule[$key]["work_schedule_id"];

            if(date("A",strtotime($stime)) == "PM" && date("A",strtotime($etime)) == "AM" ){
    
                $res_start_time = $from." ".$stime;
                $res_end_time = date("Y-m-d",strtotime($from." +1 day"))." ".$etime;
            }else{
    
                $res_start_time = $from." ".$stime;
                $res_end_time = $from." ".$etime;
            }

            // NEW SCHEDULE
            $new_end_time = $res_start_time;
            $new_start_time = date("Y-m-d H:i:s",strtotime($res_start_time." -2 hours"));

            $check_employee_time_in = $this->mdm->check_employee_time_in_early($e_work_schedule_id,$new_start_time,$new_end_time,$this->company_id,$this->emp_id);
            // $check_employee_time_in = $this->check_employee_time_in($e_work_schedule_id,$new_start_time,$new_end_time, $check_employee_time_in_early);
            // asdlnsa djpasjd asdpojapsdj powqpoeq nfaspdjsdas
            // pretending that im t
            
            if($check_employee_time_in != FALSE){
                
                $flag_view = TRUE;

                foreach($check_employee_time_in as $row_check_time_in){
                    $employee_row_array[$row_check_time_in->employee_time_in_id] = $row_check_time_in->emp_id + 1;
                    $employee_name_row_array[$row_check_time_in->employee_time_in_id] = ucwords("{$row_check_time_in->first_name} {$row_check_time_in->last_name}");
                    $employee_first_name_row_array[$row_check_time_in->employee_time_in_id] = $row_check_time_in->first_name;
                    $employee_last_name_row_array[$row_check_time_in->employee_time_in_id] = $row_check_time_in->last_name;
                    // $employee_position_row_array[$row_check_time_in->employee_time_in_id] = $row_check_time_in->position_name;
                    $employee_account_id[$row_check_time_in->employee_time_in_id] = $row_check_time_in->account_id;
                    $employee_emp_id[$row_check_time_in->employee_time_in_id] = $row_check_time_in->emp_id;
                    $employee_payroll_cloud_id[$row_check_time_in->employee_time_in_id] = $row_check_time_in->payroll_cloud_id;
                    // $employee_department_array[$row_check_time_in->employee_time_in_id] = $row_check_time_in->department_name;
                    $employee_login_mobile_number_array[$row_check_time_in->employee_time_in_id] = $row_check_time_in->login_mobile_number;
                    $employee_telephone_number_array[$row_check_time_in->employee_time_in_id] = $row_check_time_in->telephone_number;
                    $employee_time_in_array[$row_check_time_in->employee_time_in_id] = $row_check_time_in->time_in;
                    $mins_prior_to_shift_start[$row_check_time_in->employee_time_in_id] = (strtotime($res_start_time) - strtotime($row_check_time_in->time_in)) / 60;
                    
                    // EMPLOYEE WORK SCHEDULE ID
                    $emp_time_in_work_schedule_id[$row_check_time_in->employee_time_in_id] = $row_check_time_in->work_schedule_id;

                    $temp = array(
                        'first_name' => $row_check_time_in->first_name,
                        'last_name' => $row_check_time_in->last_name,
                        'payroll_cloud_id' => $row_check_time_in->payroll_cloud_id,
                        'company_id' => $this->company_id,
                        'time_in' => ($row_check_time_in->time_in) ? date("g:i a", strtotime($row_check_time_in->time_in)) : '',
                        'time_in_date' => ($row_check_time_in->time_in) ? date("d-M-y", strtotime($row_check_time_in->time_in)) : '',
                        'minutes_early' => ((strtotime($res_start_time) - strtotime($row_check_time_in->time_in)) / 60)
                    );
                    array_push($employees, $temp);
                }
            }
            
        }
            

        $resu = array(        
            'early_emps' => $employees,
            'count' => ($mins_prior_to_shift_start) ? count($mins_prior_to_shift_start) : 0
        );
        echo json_encode($resu);
    }
}